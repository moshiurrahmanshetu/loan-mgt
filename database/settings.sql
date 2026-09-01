-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 8: System Settings Schema
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `settings`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL DEFAULT NULL,
    `setting_type` ENUM('text', 'number', 'boolean', 'image') NOT NULL DEFAULT 'text',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed Default System Settings
-- ----------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES 
    ('company_name', 'Loan Management System', 'text'),
    ('system_name', 'LoanMgt', 'text'),
    ('company_phone', '+1 (555) 019-2834', 'text'),
    ('company_email', 'info@loanmgt.com', 'text'),
    ('company_address', '100 Financial Plaza, Suite 400, New York, NY 10005', 'text'),
    ('currency_symbol', '$', 'text'),
    ('currency_code', 'USD', 'text'),
    ('timezone', 'America/New_York', 'text'),
    ('date_format', 'M d, Y', 'text'),
    ('system_logo', NULL, 'image')
ON DUPLICATE KEY UPDATE `setting_type` = VALUES(`setting_type`);
