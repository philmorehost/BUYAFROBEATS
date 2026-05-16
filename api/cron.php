<?php
// Maintenance script to be run via server cron every minute
// Example: * * * * * php /path/to/api/cron.php > /dev/null 2>&1

require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

// Ensure this is not running too frequently if called via web
// (Though it's designed for CLI)
if (php_sapi_name() !== 'cli') {
    // Basic security for web-based trigger
    $token = $_GET['token'] ?? '';
    if ($token !== AUTH_SALT) {
        header("HTTP/1.1 403 Forbidden");
        exit("Invalid token");
    }
}

$core = Core::get_instance();
$auction = new Auction($core);

// 1. Process Finished Auctions (Winner Detection)
$auction->check_for_winners();

// 2. Cleanup & Reversals (24h Window)
$auction->cleanup_sold_beats();

echo "Cron maintenance completed at " . date('Y-m-d H:i:s') . " UTC\n";
