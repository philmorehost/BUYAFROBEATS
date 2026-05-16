<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$db = $core->db();

echo "Checking indexes...\n";

$tables = ['beats', 'bids', 'sales', 'activity'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $db->query("SHOW INDEX FROM $table");
    while ($row = $stmt->fetch()) {
        echo "Index: {$row['Key_name']} on Column: {$row['Column_name']}\n";
    }
}
