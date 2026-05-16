-- BEATZAZA v3.0 Migration Script

-- 1. Update Beats table for new storage model
ALTER TABLE `beats` 
ADD COLUMN `license_url` VARCHAR(500) AFTER `stems_url`,
ADD COLUMN `reserve_price` DECIMAL(10, 2) DEFAULT 0 AFTER `starting_bid`;

-- 2. Update Sales table for Payment Cascade
ALTER TABLE `sales`
ADD COLUMN `cascade_json` LONGTEXT AFTER `payment_status`,
ADD COLUMN `claimant_index` INT DEFAULT 0 AFTER `cascade_json`,
ADD COLUMN `drive_shared_at` TIMESTAMP NULL AFTER `expires_at`,
MODIFY COLUMN `payment_status` ENUM('pending', 'completed', 'failed', 'expired', 'cascaded') DEFAULT 'pending';

-- 3. Update Users table for Google Auth and Handles
ALTER TABLE `users`
ADD COLUMN `handle` VARCHAR(50) UNIQUE AFTER `username`,
ADD COLUMN `google_id` VARCHAR(255) UNIQUE AFTER `id`,
ADD COLUMN `avatar_url` VARCHAR(500) AFTER `email`,
MODIFY COLUMN `password` VARCHAR(255) NULL; -- Allow NULL for Google Auth users

-- 4. Settings defaults for v3.0
INSERT INTO `settings` (`key`, `value`) VALUES 
('auction_duration_min', '30'),
('auction_anti_snipe_min', '2'),
('auction_min_increment', '5'),
('google_drive_enabled', '0'),
('plisio_enabled', '0'),
('auto_cascade_enabled', '1'),
('payment_window_hours', '24')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
