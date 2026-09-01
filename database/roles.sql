-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 8: Roles Schema
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `roles`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_roles_slug` (`slug`),
    INDEX `idx_roles_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed Default System Roles
-- ----------------------------------------------------------
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`, `status`)
VALUES 
    ('Administrator', 'admin', 'Full administrative authority across all system features, users, roles, and settings.', 1, 'active'),
    ('Loan Manager', 'manager', 'Operational management, underwriting loan approvals/rejections, capital disbursements, and reports.', 1, 'active'),
    ('Loan Officer', 'loan_officer', 'Customer onboarding, loan application origination, and portfolio monitoring.', 1, 'active'),
    ('Debt Collector', 'collector', 'Repayment collections, payment receipts, and overdue delinquency tracking.', 1, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `is_system` = VALUES(`is_system`);
