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
        echo "<li style='color:red;'>Error executing query: " . $e->getMessage() . "</li>";
    }
}

// Special check for stems_path as ALTER TABLE IF NOT EXISTS is not supported in older MySQL
try {
    $check = $db->query("SHOW COLUMNS FROM `beats` LIKE 'stems_path'");
    if (!$check->fetch()) {
        $db->exec("ALTER TABLE `beats` ADD COLUMN `stems_path` VARCHAR(255) AFTER `sample_path`;");
        echo "<li style='color:green;'>Success: stems_path column added to beats table.</li>";
    } else {
        echo "<li style='color:blue;'>Info: stems_path column already exists.</li>";
    }
} catch (\PDOException $e) {
    echo "<li style='color:red;'>Error updating beats table: " . $e->getMessage() . "</li>";
}

// Add URL columns for external file support
try {
    $check = $db->query("SHOW COLUMNS FROM `beats` LIKE 'audio_url'");
    if (!$check->fetch()) {
        $db->exec("ALTER TABLE `beats` 
            ADD COLUMN `audio_url` VARCHAR(500) AFTER `audio_path`, 
            ADD COLUMN `sample_url` VARCHAR(500) AFTER `sample_path`, 
            ADD COLUMN `stems_url` VARCHAR(500) AFTER `stems_path`,
            MODIFY COLUMN `audio_path` VARCHAR(255) NULL,
            MODIFY COLUMN `sample_path` VARCHAR(255) NULL,
            MODIFY COLUMN `stems_path` VARCHAR(255) NULL;");
        echo "<li style='color:green;'>Success: URL columns added and paths made nullable.</li>";
    } else {
        echo "<li style='color:blue;'>Info: URL columns already exist.</li>";
    }
} catch (\PDOException $e) {
    echo "<li style='color:red;'>Error adding URL columns: " . $e->getMessage() . "</li>";
}

// Add indexes for performance
try {
    $db->exec("ALTER TABLE `beats` ADD INDEX `idx_status` (`status`), ADD INDEX `idx_ends_at` (`ends_at`);");
    echo "<li style='color:green;'>Success: Performance indexes added to beats table.</li>";
} catch (\PDOException $e) {
    // Indexes might already exist
    echo "<li style='color:blue;'>Info: Performance indexes already exist or could not be added.</li>";
}


echo "</ul>";
echo "<p><b>Update complete!</b> you can now use the CMS, FAQs, and Newsletter features.</p>";
echo "<p><a href='index'>Go to Homepage</a> | <a href='admin/index'>Go to Admin</a></p>";
echo "<p style='color:gray; font-size:12px;'>Please delete this file (update.php) after use for security.</p>";
