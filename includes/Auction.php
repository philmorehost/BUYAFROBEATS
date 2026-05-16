<?php
namespace BAF;

class Auction {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function get_live_beats($genre = 'All', $search = '') {
        // Include live beats and beats sold in the last 24 hours
        $sql = "SELECT * FROM beats WHERE (status = 'live' OR (status = 'sold' AND ends_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)))";
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

        $sql .= " ORDER BY status ASC, created_at DESC";
        $stmt = $this->core->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function get_leaderboard($limit = 6) {
        // Optimized: Single query with JOIN for bid counts
        $sql = "SELECT b.*, COUNT(bi.id) as bid_count,
                (
                    COUNT(bi.id) * 10 + 
                    b.current_bid * 0.1 + 
                    IF(b.ends_at IS NOT NULL AND TIMESTAMPDIFF(MINUTE, NOW(), b.ends_at) < 5, 50, 0)
                ) as score 
                FROM beats b 
                LEFT JOIN bids bi ON b.id = bi.beat_id
                WHERE b.status = 'live' 
                GROUP BY b.id 
                ORDER BY score DESC 
                LIMIT " . (int)$limit;
        
        $stmt = $this->core->db()->query($sql);
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

    public function cleanup_sold_beats() {
        $db = $this->core->db();
        
        // 1. SITE REMOVAL (24 Hours after sale)
        // Mark completed sales as expired so they disappear from the live catalog.
        $stmt = $db->prepare("UPDATE beats b 
                              JOIN sales s ON b.id = s.beat_id 
                              SET b.status = 'expired' 
                              WHERE b.status = 'sold' 
                              AND s.payment_status = 'completed' 
                              AND s.sold_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();

        // 2. DOWNLOAD REVOCATION (7 Days after sale)
        // Mark long-expired completed purchases as archived so downstream download access is disabled.
        $stmt = $db->prepare("UPDATE sales SET payment_status = 'archived' 
                              WHERE payment_status = 'completed' 
                              AND sold_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();

        // 3. UNPAID SALES: return unsold beats to market after the payment window.
        $stmt = $db->prepare("SELECT b.*, s.id as sale_id, s.cascade_chain, s.current_claimant_index 
                              FROM beats b 
                              JOIN sales s ON b.id = s.beat_id 
                              WHERE b.status = 'sold' 
                              AND s.payment_status != 'completed' 
                              AND (s.sold_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR) OR (s.expires_at IS NOT NULL AND s.expires_at <= NOW()))");
        $stmt->execute();
        $unpaid_beats = $stmt->fetchAll();

        foreach ($unpaid_beats as $beat) {
            $db->beginTransaction();
            try {
                $chain = json_decode($beat['cascade_chain'], true);
                $next_index = $beat['current_claimant_index'] + 1;

                if ($chain && isset($chain[$next_index])) {
                    // Advance cascade to the next bidder and give them a fresh payment window.
                    $next_claimant = $chain[$next_index];
                    $next_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    $stmt = $db->prepare("UPDATE sales SET 
                                            current_claimant_index = ?, 
                                            winner_handle = ?, 
                                            winner_email = ?, 
                                            price = ?, 
                                            plisio_invoice_id = NULL, 
                                            plisio_invoice_url = NULL, 
                                            expires_at = ?, 
                                            sold_at = NOW() 
                                          WHERE id = ?");
                    $stmt->execute([$next_index, $next_claimant['handle'], $next_claimant['email'], $next_claimant['amount'], $next_expires, $beat['sale_id']]);
                    
                    // Update the beat display for the replacement claimant.
                    $stmt = $db->prepare("UPDATE beats SET top_bidder = ?, current_bid = ? WHERE id = ?");
                    $stmt->execute([$next_claimant['handle'], $next_claimant['amount'], $beat['id']]);

                    // Notify the new claimant that they now have the winning position.
                    require_once __DIR__ . '/Email.php';
                    $email_svc = new \BAF\Email($this->core);
                    $email_svc->notify_win_payment($next_claimant['email'], $beat['title'], $next_claimant['amount'], $beat['delivery_id']);
                } else {
                    // No alternate bidder remains: reverse beat back to market.
                    $stmt = $db->prepare("UPDATE beats SET status = 'live', top_bidder = NULL, current_bid = starting_bid, ends_at = NULL WHERE id = ?");
                    $stmt->execute([$beat['id']]);

                    $stmt = $db->prepare("DELETE FROM sales WHERE id = ?");
                    $stmt->execute([$beat['sale_id']]);
                }
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
            }
        }
    }

    public function check_for_winners() {
        $db = $this->core->db();
        $now = date('Y-m-d H:i:s');
        
        $stmt = $db->prepare("SELECT * FROM beats WHERE status = 'live' AND ends_at <= ? AND top_bidder IS NOT NULL");
        $stmt->execute([$now]);
        $expired = $stmt->fetchAll();

        foreach ($expired as $beat) {
            $db->beginTransaction();
            try {
                // Mark as sold
                $stmt = $db->prepare("UPDATE beats SET status = 'sold' WHERE id = ?");
                $stmt->execute([$beat['id']]);

                // Generate cascade chain: highest bid per unique bidder
                $stmt = $db->prepare("SELECT bidder_handle as handle, bidder_email as email, MAX(amount) as amount 
                                      FROM bids WHERE beat_id = ? 
                                      GROUP BY bidder_handle, bidder_email 
                                      ORDER BY amount DESC");
                $stmt->execute([$beat['id']]);
                $chain = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                $winner = $chain[0];
                $delivery_id = 'BAF-' . strtoupper(bin2hex(random_bytes(4)));
                $download_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $db->prepare("INSERT INTO sales (beat_id, delivery_id, winner_handle, winner_email, price, download_token, expires_at, cascade_chain, current_claimant_index) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([
                    $beat['id'], $delivery_id, $winner['handle'], $winner['email'], 
                    $winner['amount'], $download_token, $expires_at, json_encode($chain)
                ]);

                // Log activity
                $stmt = $db->prepare("INSERT INTO activity (type, beat_id, user_handle, amount, message) VALUES ('won', ?, ?, ?, ?)");
                $msg = "won the auction for " . $beat['title'] . " at $" . number_format($winner['amount'], 2);
                $stmt->execute([$beat['id'], $winner['handle'], $winner['amount'], $msg]);

                $db->commit();
                
                // Trigger Winning Email
                require_once __DIR__ . '/Email.php';
                $email_svc = new \BAF\Email($this->core);
                $email_svc->notify_win_payment($winner['email'], $beat['title'], $winner['amount'], $delivery_id);
                
            } catch (\Exception $e) {
                $db->rollBack();
            }
        }
    }
}
