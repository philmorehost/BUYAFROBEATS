CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `beats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `bpm` INT,
    `key_sig` VARCHAR(20),
    `genre` VARCHAR(50),
    `duration` VARCHAR(10),
    `starting_bid` DECIMAL(10, 2) NOT NULL,
    `current_bid` DECIMAL(10, 2) NOT NULL,
    `top_bidder` VARCHAR(50),
    `audio_path` VARCHAR(255) NOT NULL,
    `sample_path` VARCHAR(255),
    `ends_at` TIMESTAMP NULL,
    `status` ENUM('live', 'sold', 'expired') DEFAULT 'live',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bids` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `beat_id` INT NOT NULL,
    `bidder_handle` VARCHAR(50) NOT NULL,
    `bidder_email` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`beat_id`) REFERENCES `beats`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `beat_id` INT NOT NULL,
    `delivery_id` VARCHAR(50) UNIQUE NOT NULL,
    `winner_handle` VARCHAR(50) NOT NULL,
    `winner_email` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `download_token` VARCHAR(64) UNIQUE,
    `payment_status` ENUM('pending', 'completed', 'failed', 'expired') DEFAULT 'pending',
    `plisio_invoice_id` VARCHAR(100),
    `plisio_invoice_url` TEXT,
    `expires_at` TIMESTAMP NULL,
    `sold_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`beat_id`) REFERENCES `beats`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `activity` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('bid', 'won', 'listing') NOT NULL,
    `beat_id` INT,
    `user_handle` VARCHAR(50),
    `amount` DECIMAL(10, 2),
    `message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
