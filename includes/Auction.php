<?php
namespace BAF;

class Auction {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function get_live_beats($genre = 'All', $search = '') {
        // PRD: Sold beats are removed from the marketplace within 1 second of auction close.
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
        // PRD Score: (Bids * 10) + (Price * 0.1) + (Urgency Boost if < 5m)
        $stmt = $this->core->db()->query("SELECT b.*, 
            ( 
                (SELECT COUNT(*) FROM bids WHERE beat_id = b.id) * 10 + 
                current_bid * 0.1 + 
                IF(ends_at IS NOT NULL AND TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), ends_at) < 300, 50, 0)
            ) as score 
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
            $min_increment = (int)$this->core->setting('auction_min_increment', 5);
            $min_required = empty($beat['top_bidder']) ? $beat['starting_bid'] : $beat['current_bid'] + $min_increment;

            if ($amount < $min_required) {
                throw new \Exception("Minimum bid required is $" . number_format($min_required, 2));
            }

            // Handle check
            $handle = ltrim($handle, '@');
            if ($handle === ltrim($beat['top_bidder'], '@')) {
                throw new \Exception("You are already the top bidder.");
            }

            // Anti-snipe: If bid in last 2 mins, reset to 2 mins
            $now = time();
            $duration = (int)$this->core->setting('auction_duration_min', 30) * 60;
            $anti_snipe = (int)$this->core->setting('auction_anti_snipe_min', 2) * 60;
            
            $ends_at = $beat['ends_at'] ? strtotime($beat['ends_at']) : ($now + $duration);
            
            if ($ends_at - $now < $anti_snipe) {
                $ends_at = $now + $anti_snipe;
            }

            // Update beat
            $stmt = $db->prepare("UPDATE beats SET current_bid = ?, top_bidder = ?, ends_at = ? WHERE id = ?");
            $stmt->execute([$amount, '@' . $handle, date('Y-m-d H:i:s', $ends_at), $beat_id]);

            // Insert bid
            $stmt = $db->prepare("INSERT INTO bids (beat_id, bidder_handle, bidder_email, amount, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$beat_id, '@' . $handle, $email, $amount, $ip]);

            // Log activity
            $stmt = $db->prepare("INSERT INTO activity (type, beat_id, user_handle, amount, message) VALUES ('bid', ?, ?, ?, ?)");
            $msg = "placed a bid of $" . number_format($amount, 2) . " on " . $beat['title'];
            $stmt->execute([$beat_id, '@' . $handle, $amount, $msg]);

            $db->commit();
            return ['success' => true, 'ends_at' => $ends_at];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function get_bids($beat_id) {
        $stmt = $this->core->db()->prepare("SELECT * FROM bids WHERE beat_id = ? ORDER BY created_at DESC");
        $stmt->execute([$beat_id]);
        return $stmt->fetchAll();
    }

    /**
     * Builds the Payment Cascade chain for a finished auction.
     * Takes each unique bidder's best bid, sorted high -> low.
     */
    public function build_cascade_chain($beat_id) {
        $stmt = $this->core->db()->prepare("
            SELECT bidder_handle, bidder_email, MAX(amount) as best_bid 
            FROM bids 
            WHERE beat_id = ? 
            GROUP BY bidder_handle, bidder_email 
            ORDER BY best_bid DESC
        ");
        $stmt->execute([$beat_id]);
        return $stmt->fetchAll();
    }

    public function advance_cascade($delivery_id) {
        $db = $this->core->db();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM sales WHERE delivery_id = ? FOR UPDATE");
            $stmt->execute([$delivery_id]);
            $sale = $stmt->fetch();

            if (!$sale || $sale['payment_status'] === 'completed') {
                return false;
            }

            $cascade = json_decode($sale['cascade_json'], true);
            $next_index = $sale['claimant_index'] + 1;

            if ($next_index >= count($cascade)) {
                // End of the chain
                $stmt = $db->prepare("UPDATE sales SET payment_status = 'failed' WHERE delivery_id = ?");
                $stmt->execute([$delivery_id]);
                $db->commit();
                return false;
            }

            $next_claimant = $cascade[$next_index];
            $payment_window = (int)$this->core->setting('payment_window_hours', 24);
            $expires_at = date('Y-m-d H:i:s', strtotime("+$payment_window hours"));

            $stmt = $db->prepare("UPDATE sales SET 
                winner_handle = ?, 
                winner_email = ?, 
                price = ?, 
                claimant_index = ?, 
                payment_status = 'pending', 
                expires_at = ?,
                plisio_invoice_id = NULL,
                plisio_invoice_url = NULL 
                WHERE delivery_id = ?");
            
            $stmt->execute([
                $next_claimant['bidder_handle'],
                $next_claimant['bidder_email'],
                $next_claimant['best_bid'],
                $next_index,
                $expires_at,
                $delivery_id
            ]);

            $db->commit();

            // Notify the new winner (Silent re-offer)
            require_once __DIR__ . '/Email.php';
            $email_svc = new Email($this->core);
            $email_svc->notify_win_payment($next_claimant['bidder_email'], 'Your Beat', $next_claimant['best_bid'], $delivery_id);

            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
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
                // 1. Build cascade chain
                $chain = $this->build_cascade_chain($beat['id']);
                if (empty($chain)) {
                    $stmt = $db->prepare("UPDATE beats SET status = 'expired' WHERE id = ?");
                    $stmt->execute([$beat['id']]);
                    $db->commit();
                    continue;
                }

                // 2. Mark as sold
                $stmt = $db->prepare("UPDATE beats SET status = 'sold' WHERE id = ?");
                $stmt->execute([$beat['id']]);

                // 3. Create Sale record with first claimant
                $delivery_id = 'BZZ-' . strtoupper(bin2hex(random_bytes(4)));
                $download_token = bin2hex(random_bytes(32));
                $payment_window = (int)$this->core->setting('payment_window_hours', 24);
                $expires_at = date('Y-m-d H:i:s', strtotime("+$payment_window hours"));

                $winner = $chain[0];

                $stmt = $db->prepare("INSERT INTO sales (beat_id, delivery_id, winner_handle, winner_email, price, download_token, cascade_json, claimant_index, expires_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
                
                $stmt->execute([
                    $beat['id'], 
                    $delivery_id, 
                    $winner['bidder_handle'], 
                    $winner['bidder_email'], 
                    $winner['best_bid'], 
                    $download_token, 
                    json_encode($chain),
                    $expires_at
                ]);

                // 4. Log activity (Original Top Bid)
                $stmt = $db->prepare("INSERT INTO activity (type, beat_id, user_handle, amount, message) VALUES ('won', ?, ?, ?, ?)");
                $msg = "won the auction for " . $beat['title'] . " at $" . number_format($winner['best_bid'], 2);
                $stmt->execute([$beat['id'], $winner['bidder_handle'], $winner['best_bid'], $msg]);

                $db->commit();
                
                // 5. Trigger Winning Email
                require_once __DIR__ . '/Email.php';
                $email_svc = new Email($this->core);
                $email_svc->notify_win_payment($winner['bidder_email'], $beat['title'], $winner['best_bid'], $delivery_id);
                
            } catch (\Exception $e) {
                $db->rollBack();
            }
        }
    }
}
