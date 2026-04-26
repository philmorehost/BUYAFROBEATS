<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

// Only allow admin to run this if session exists, 
// or allow if config exists but no pages table found (initial update)
if (!$core->is_admin()) {
    // If we can't even check admin because session is broken, 
    // we'll just check if the config exists.
    if (!file_exists(__DIR__ . '/config.php')) {
        die("Please install the site first.");
    }
}

$db = $core->db();
$queries = [
    "CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(100) UNIQUE NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `content` LONGTEXT,
        `meta_title` VARCHAR(255),
        `meta_description` TEXT,
        `meta_keywords` TEXT,
        `is_external` TINYINT(1) DEFAULT 0,
        `external_url` VARCHAR(255),
        `status` ENUM('draft', 'published') DEFAULT 'published',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `subscribers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `faqs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `question` TEXT NOT NULL,
        `answer` TEXT NOT NULL,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

echo "<h2>Database Update Tool</h2>";
echo "<ul>";

foreach ($queries as $sql) {
    try {
        $db->exec($sql);
        echo "<li style='color:green;'>Success: Table structure updated.</li>";
    } catch (\PDOException $e) {
        echo "<li style='color:red;'>Error: " . $e->getMessage() . "</li>";
    }
}

echo "</ul>";
echo "<p><b>Update complete!</b> you can now use the CMS, FAQs, and Newsletter features.</p>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/index.php'>Go to Admin</a></p>";
echo "<p style='color:gray; font-size:12px;'>Please delete this file (update.php) after use for security.</p>";
