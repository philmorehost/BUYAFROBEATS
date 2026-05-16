<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$db = $core->db();

try {
    $db->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) DEFAULT 1 AFTER role");
    echo "Successfully added email_notifications column to users table.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
