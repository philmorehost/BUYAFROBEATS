<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$last_activity_id = 0;

// Set time limit to 0 to keep the stream open
set_time_limit(0);

while (true) {
    // Check for new activity
    $stmt = $core->db()->prepare("SELECT * FROM activity WHERE id > ? ORDER BY id ASC");
    $stmt->execute([$last_activity_id]);
    $activities = $stmt->fetchAll();

    if (!empty($activities)) {
        foreach ($activities as $act) {
            echo "event: activity\n";
            echo "data: " . json_encode($act) . "\n\n";
            $last_activity_id = $act['id'];
        }
    }

    // Check for bid updates (latest bid for each live beat)
    $stmt = $core->db()->query("SELECT id, current_bid, top_bidder, ends_at FROM beats WHERE status = 'live'");
    $beats = $stmt->fetchAll();
    echo "event: update\n";
    echo "data: " . json_encode($beats) . "\n\n";

    // Flush the output buffer
    if (ob_get_level() > 0) ob_flush();
    flush();

    // Sleep for 2 seconds before checking again
    sleep(2);
}
