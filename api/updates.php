<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

$core = Core::get_instance();
$last_activity_id = $_GET['last_id'] ?? 0;

// CRITICAL: Release the session lock so other pages can load
// while this infinite loop is running.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Poll for changes - Limit execution time to 20 seconds to prevent worker exhaustion
$start_time = time();
while (time() - $start_time < 20) {
    if (connection_aborted()) break;

    $db = $core->db();
    
    // Check for new activity
    try {
        $stmt = $db->prepare("SELECT a.*, b.title, b.current_bid, b.ends_at, b.status as beat_status 
                              FROM activity a 
                              JOIN beats b ON a.beat_id = b.id 
                              WHERE a.id > ? 
                              ORDER BY a.id ASC LIMIT 10");
        $stmt->execute([$last_activity_id]);
        $activities = $stmt->fetchAll();
    } catch (\Exception $e) {
        $activities = [];
    }

    if (!empty($activities)) {
        foreach ($activities as $act) {
            echo "event: activity\n";
            echo "data: " . json_encode($act) . "\n\n";
            $last_activity_id = $act['id'];
        }
    }

    // Always push heartbeat to keep connection alive
    echo "event: sync\n";
    echo "data: " . json_encode(['server_time' => time()]) . "\n\n";

    if (ob_get_level() > 0) ob_flush();
    flush();

    sleep(3); 
}
// Browser will automatically reconnect after 20 seconds, releasing the PHP process in between.
