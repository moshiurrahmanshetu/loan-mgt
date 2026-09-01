-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 3: Loan Products Database Schema
--
-- IMPORTANT:
-- This file must be imported AFTER database/auth.sql has been
-- imported, as it establishes a Foreign Key referencing `users.id`.
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `loan_products`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `minimum_amount` DECIMAL(12, 2) NOT NULL DEFAULT 1000.00,
    `maximum_amount` DECIMAL(12, 2) NOT NULL DEFAULT 50000.00,
    `interest_rate` DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    `interest_method` ENUM('flat', 'reducing_balance') NOT NULL DEFAULT 'flat',
    `term_min` INT UNSIGNED NOT NULL DEFAULT 1,
    `term_max` INT UNSIGNED NOT NULL DEFAULT 12,
    `term_unit` ENUM('days', 'weeks', 'months') NOT NULL DEFAULT 'months',
    `repayment_frequency` ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL DEFAULT 'monthly',
    `processing_fee` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_loan_products_code` (`product_code`),
    INDEX `idx_loan_products_status` (`status`),
    INDEX `idx_loan_products_created_by` (`created_by`),
    CONSTRAINT `fk_loan_products_created_by` 
        FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Sample Seed Data for Loan Products
-- ----------------------------------------------------------
INSERT INTO `loan_products` (
    `product_code`, `name`, `description`, `minimum_amount`, 
    `maximum_amount`, `interest_rate`, `interest_method`, `term_min`, 
    `term_max`, `term_unit`, `repayment_frequency`, `processing_fee`, 
    `status`, `created_by`, `created_at`
)
VALUES 
(
    'LP-001',
    'Personal Micro Loan',
    'Short-term unsecured personal credit for salaried individuals and small wage earners.',
    500.00,
    10000.00,
    12.00,
    'flat',
    3,
    12,
    'months',
    'monthly',
    1.50,
    'active',
    1,
    NOW()
),
(
    'LP-002',
    'Small Business Growth Loan',
    'Working capital financing for retail shops, traders, and small enterprise expansion.',
    5000.00,
    50000.00,
    9.50,
    'flat',
    6,
    36,
    'months',
    'monthly',
    2.00,
    'active',
    1,
    NOW()
),
(
    'LP-003',
    'Emergency Quick Cash',
    'Fast-track short duration emergency assistance for urgent healthcare or family expenses.',
    200.00,
    2500.00,
    15.00,
    'flat',
    1,
    6,
    'months',
    'monthly',
    1.00,
    'active',
    1,
    NOW()
),
(
    'LP-004',
    'Agricultural Seasonal Credit',
    'Seasonal agricultural loan for seeds, fertilizers, and equipment with flexible term matching.',
    1000.00,
    20000.00,
    8.00,
    'reducing_balance',
    3,
    18,
    'months',
    'monthly',
    1.00,
    'inactive',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE `product_code` = VALUES(`product_code`);
