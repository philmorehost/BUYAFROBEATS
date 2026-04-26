<?php
namespace BAF;

class Auction {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function get_live_beats($genre = 'All', $search = '') {
        $sql = "SELECT * FROM beats WHERE status = 'live'";
        $params = [];

        if ($genre !== 'All') {
            $sql .= " AND genre = ?";
            $params[] = $genre;
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR genre LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->core->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function get_leaderboard($limit = 6) {
        // Logic similar to React: Score based on bids, current price, and urgency
        $stmt = $this->core->db()->query("SELECT *, 
            ( (SELECT COUNT(*) FROM bids WHERE beat_id = b.id) * 10 + current_bid * 0.1 ) as score 
            FROM beats b WHERE status = 'live' ORDER BY score DESC LIMIT $limit");
        return $stmt->fetchAll();
    }

    public function place_bid($beat_id, $handle, $email, $amount, $ip) {
        $db = $this->core->db();
        $db->beginTransaction();

        try {
            // Lock for update
            $stmt = $db->prepare("SELECT * FROM beats WHERE id = ? FOR UPDATE");
            $stmt->execute([$beat_id]);
            $beat = $stmt->fetch();

            if (!$beat || $beat['status'] !== 'live') {
                throw new \Exception("Auction is no longer live.");
            }

            // Check if expired
            if ($beat['ends_at'] && strtotime($beat['ends_at']) <= time()) {
                throw new \Exception("Auction has ended.");
            }

            // Validation
            $min_increment = 5;
            $min_required = empty($beat['top_bidder']) ? $beat['starting_bid'] : $beat['current_bid'] + $min_increment;

            if ($amount < $min_required) {
                throw new \Exception("Minimum bid required is $" . number_format($min_required, 2));
            }

            if ($handle === $beat['top_bidder']) {
                throw new \Exception("You are already the top bidder.");
            }

            // Anti-snipe: If bid in last 2 mins, extend by 2 mins
            $now = time();
            $ends_at = $beat['ends_at'] ? strtotime($beat['ends_at']) : ($now + 1800); // Start 30min on 1st bid
            
            if ($ends_at - $now < 120) {
                $ends_at = $now + 120;
            }

            // Update beat
            $stmt = $db->prepare("UPDATE beats SET current_bid = ?, top_bidder = ?, ends_at = ?, status = 'live' WHERE id = ?");
            $stmt->execute([$amount, $handle, date('Y-m-d H:i:s', $ends_at), $beat_id]);

            // Insert bid
            $stmt = $db->prepare("INSERT INTO bids (beat_id, bidder_handle, bidder_email, amount, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$beat_id, $handle, $email, $amount, $ip]);

            // Log activity
            $stmt = $db->prepare("INSERT INTO activity (type, beat_id, user_handle, amount, message) VALUES ('bid', ?, ?, ?, ?)");
            $msg = "placed a bid of $" . number_format($amount, 2) . " on " . $beat['title'];
            $stmt->execute([$beat_id, $handle, $amount, $msg]);

            $db->commit();
            return ['success' => true, 'ends_at' => $ends_at];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function check_for_winners() {
        $db = $this->core->db();
        $now = date('Y-m-d H:i:s');
        
        // Find live beats that have expired and have at least one bid
        $stmt = $db->prepare("SELECT * FROM beats WHERE status = 'live' AND ends_at <= ? AND top_bidder IS NOT NULL");
        $stmt->execute([$now]);
        $expired = $stmt->fetchAll();

        foreach ($expired as $beat) {
            $db->beginTransaction();
            try {
                // Mark as sold
                $stmt = $db->prepare("UPDATE beats SET status = 'sold' WHERE id = ?");
                $stmt->execute([$beat['id']]);

                // Generate delivery record
                $delivery_id = 'BAF-' . strtoupper(bin2hex(random_bytes(4)));
                $download_token = bin2hex(random_bytes(32));
                
                // Get winner email from latest bid
                $stmt = $db->prepare("SELECT bidder_email FROM bids WHERE beat_id = ? ORDER BY amount DESC LIMIT 1");
                $stmt->execute([$beat['id']]);
                $winner_email = $stmt->fetchColumn();

                $stmt = $db->prepare("INSERT INTO sales (beat_id, delivery_id, winner_handle, winner_email, price, download_token, expires_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
                $stmt->execute([$beat['id'], $delivery_id, $beat['top_bidder'], $winner_email, $beat['current_bid'], $download_token, $expires_at]);

                // Log activity
                $stmt = $db->prepare("INSERT INTO activity (type, beat_id, user_handle, amount, message) VALUES ('won', ?, ?, ?, ?)");
                $msg = "won the auction for " . $beat['title'] . " at $" . number_format($beat['current_bid'], 2);
                $stmt->execute([$beat['id'], $beat['top_bidder'], $beat['current_bid'], $msg]);

                $db->commit();
                
                // Trigger Winning Email
                require_once __DIR__ . '/Email.php';
                $email_svc = new \BAF\Email($this->core);
                $email_svc->notify_win($winner_email, $beat['title'], $beat['current_bid'], $delivery_id, $download_token);
                
            } catch (\Exception $e) {
                $db->rollBack();
            }
        }
    }
}
