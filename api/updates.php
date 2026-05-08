<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

$core = Core::get_instance();
$last_activity_id = $_GET['last_id'] ?? 0;

// Poll for changes
while (true) {
    if (connection_aborted()) break;

    $db = $core->db();
    
    // Check for new activity
    $stmt = $db->prepare("SELECT a.*, b.title, b.current_bid, b.ends_at, b.status as beat_status 
                          FROM activity a 
                          JOIN beats b ON a.beat_id = b.id 
                          WHERE a.id > ? 
                          ORDER BY a.id ASC LIMIT 10");
    $stmt->execute([$last_activity_id]);
    $activities = $stmt->fetchAll();

    if (!empty($activities)) {
        foreach ($activities as $act) {
            echo "event: activity\n";
            echo "data: " . json_encode($act) . "\n\n";
            $last_activity_id = $act['id'];
        }
    }

    // Always push heartbeat/timestamp to keep connection alive and sync clocks
    echo "event: sync\n";
    echo "data: " . json_encode(['server_time' => time()]) . "\n\n";

    ob_flush();
    flush();

    sleep(2); // Poll every 2 seconds
}
