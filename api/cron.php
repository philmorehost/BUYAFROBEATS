<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
$auction = new Auction($core);

// Set high timeout for maintenance
set_time_limit(300);
ignore_user_abort(true);

$last_check = (int)$core->setting('last_auction_check', 0);

// Run maintenance every 60 seconds
if (time() - $last_check >= 60) {
    $core->update_setting('last_auction_check', time());
    
    // 1. Process finished auctions (Assign winners)
    $auction->check_for_winners();
    
    // 2. Check for expired payment windows (Trigger Cascade)
    $stmt = $core->db()->query("SELECT delivery_id FROM sales WHERE payment_status = 'pending' AND expires_at < UTC_TIMESTAMP()");
    $expired_sales = $stmt->fetchAll();
    foreach ($expired_sales as $sale) {
        $auction->advance_cascade($sale['delivery_id']);
    }
    
    // 3. Clear activity older than 30 days
    $core->db()->query("DELETE FROM activity WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)");
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'BEATZAZA maintenance completed.']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Skipped: Too soon.']);
}
