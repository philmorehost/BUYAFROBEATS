<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
$auction = new Auction($core);

// Set high timeout and ignore user abort for maintenance
set_time_limit(300);
ignore_user_abort(true);

$last_check = (int)$core->setting('last_auction_check', 0);

// Only run if 60 seconds have passed
if (time() - $last_check > 60) {
    // Record that we are starting to prevent concurrent runs
    $core->update_setting('last_auction_check', time());
    
    // Process winners (sends emails, can be slow)
    $auction->check_for_winners();
    
    // Cleanup files (can be slow)
    $auction->cleanup_sold_beats();
    
    echo json_encode(['success' => true, 'message' => 'Maintenance tasks completed.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Skipped: Too soon.']);
}
