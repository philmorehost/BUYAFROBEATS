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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `stems_path` VARCHAR(255) AFTER `sample_path`;",
    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `audio_url` VARCHAR(500) AFTER `audio_path`;",
    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `sample_url` VARCHAR(500) AFTER `sample_path`;",
    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `stems_url` VARCHAR(500) AFTER `stems_path`;",
    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `license_url` VARCHAR(500) AFTER `stems_url`;",
    "ALTER TABLE `beats` ADD COLUMN IF NOT EXISTS `google_drive_folder_id` VARCHAR(100) AFTER `license_url`;",
    
    // Indexes for performance
    "ALTER TABLE `beats` ADD INDEX IF NOT EXISTS `idx_status_genre` (`status`, `genre`);",
    "ALTER TABLE `beats` ADD INDEX IF NOT EXISTS `idx_ends_at` (`ends_at`);",
    
    // Sales and Activity
    "CREATE TABLE IF NOT EXISTS `activity` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `beat_id` INT,
        `user_handle` VARCHAR(100),
        `type` ENUM('bid', 'sold', 'joined') NOT NULL,
        `message` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "ALTER TABLE `sales` ADD COLUMN IF NOT EXISTS `plisio_invoice_id` VARCHAR(100) AFTER `payment_status`;",
    "ALTER TABLE `sales` ADD COLUMN IF NOT EXISTS `plisio_invoice_url` VARCHAR(500) AFTER `plisio_invoice_id`;"
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

// Default Settings
$default_settings = [
    'google_drive_client_id' => '',
    'google_drive_client_secret' => '',
    'google_drive_refresh_token' => '',
    'google_drive_parent_folder' => '',
    'plisio_api_key' => '',
    'plisio_white_label_key' => '',
    'admin_email' => 'admin@beatzaza.com'
];

foreach ($default_settings as $key => $val) {
    $stmt = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
    $stmt->execute([$key, $val]);
}

echo "</ul>";
echo "<p><b>Update complete!</b> you can now use the CMS, FAQs, and Newsletter features.</p>";
echo "<p><a href='index'>Go to Homepage</a> | <a href='admin/index'>Go to Admin</a></p>";
echo "<p style='color:gray; font-size:12px;'>Please delete this file (update.php) after use for security.</p>";
