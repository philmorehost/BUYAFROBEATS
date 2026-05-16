<?php
require_once __DIR__ . '/../includes/Core.php';

use BAF\Core;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

$core = Core::get_instance();
$last_id = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? (int)$_SERVER['HTTP_LAST_EVENT_ID'] : 0;

// Set high timeout
set_time_limit(0);

while (true) {
    // Check for new bids or activities in the last 30 seconds
    $stmt = $core->db()->prepare("SELECT * FROM activity WHERE id > ? ORDER BY id ASC LIMIT 10");
    $stmt->execute([$last_id]);
    $activities = $stmt->fetchAll();

    foreach ($activities as $act) {
        $last_id = $act['id'];
        echo "id: $last_id\n";
        echo "data: " . json_encode($act) . "\n\n";
    }

    if (ob_get_level() > 0) ob_flush();
    flush();

    if (connection_aborted()) break;

    sleep(2); // Poll every 2 seconds
}
