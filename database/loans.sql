-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 3: Loan Applications Database Schema
--
-- IMPORTANT:
-- This file must be imported AFTER:
--   1. database/auth.sql
--   2. database/customers.sql
--   3. database/loan_products.sql
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `loans`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `loan_number` VARCHAR(30) NOT NULL UNIQUE,
    `customer_id` INT UNSIGNED NOT NULL,
    `loan_product_id` INT UNSIGNED NOT NULL,
    `requested_amount` DECIMAL(12, 2) NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL,
    `interest_method` ENUM('flat', 'reducing_balance') NOT NULL,
    `term` INT UNSIGNED NOT NULL,
    `term_unit` ENUM('days', 'weeks', 'months') NOT NULL,
    `repayment_frequency` ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL,
    `processing_fee_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `processing_fee_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `estimated_interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `estimated_total_payable` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `purpose` TEXT NULL DEFAULT NULL,
    `application_date` DATE NOT NULL,
    `status` ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL DEFAULT NULL,
    `rejection_reason` TEXT NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `approved_by` INT UNSIGNED NULL DEFAULT NULL,
    `approved_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_loans_number` (`loan_number`),
    INDEX `idx_loans_customer_id` (`customer_id`),
    INDEX `idx_loans_product_id` (`loan_product_id`),
    INDEX `idx_loans_status` (`status`),
    INDEX `idx_loans_application_date` (`application_date`),
    INDEX `idx_loans_created_by` (`created_by`),
    INDEX `idx_loans_approved_by` (`approved_by`),
    CONSTRAINT `fk_loans_customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_loans_loan_product_id` 
        FOREIGN KEY (`loan_product_id`) 
        REFERENCES `loan_products` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_loans_created_by` 
        FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_loans_approved_by` 
        FOREIGN KEY (`approved_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Sample Seed Data for Loan Applications (Testing & Verification)
-- ----------------------------------------------------------
INSERT INTO `loans` (
    `loan_number`, `customer_id`, `loan_product_id`, `requested_amount`, 
    `interest_rate`, `interest_method`, `term`, `term_unit`, 
    `repayment_frequency`, `processing_fee_rate`, `processing_fee_amount`, 
    `estimated_interest_amount`, `estimated_total_payable`, `purpose`, 
    `application_date`, `status`, `notes`, `rejection_reason`, 
    `created_by`, `approved_by`, `approved_at`, `created_at`
)
VALUES 
(
    'LN-000001',
    1, -- Rahim Uddin
    1, -- Personal Micro Loan
    5000.00,
    12.00,
    'flat',
    12,
    'months',
    'monthly',
    1.50,
    75.00,
    600.00,
    5600.00,
    'Home IT equipment and workspace upgrades for remote engineering work.',
    CURDATE(),
    'approved',
    'Applicant has strong verifiable income and verified employment record.',
    NULL,
    1,
    1,
    NOW(),
    NOW()
),
(
    'LN-000002',
    2, -- Sarah Jenkins
    2, -- Small Business Growth Loan
    25000.00,
    9.50,
    'flat',
    24,
    'months',
    'monthly',
    2.00,
    500.00,
    2375.00,
    27375.00,
    'Inventory procurement and point-of-sale hardware for apparel boutique.',
    CURDATE(),
    'pending',
    'Documents submitted, pending branch manager review.',
    NULL,
    1,
    NULL,
    NULL,
    NOW()
),
(
    'LN-000003',
    3, -- Tariqul Islam
    3, -- Emergency Quick Cash
    1500.00,
    15.00,
    'flat',
    6,
    'months',
    'monthly',
    1.00,
    15.00,
    225.00,
    1725.00,
    'Urgent medical diagnostics and family emergency support.',
    CURDATE(),
    'draft',
    'Draft application created by loan officer, awaiting guarantor signature.',
    NULL,
    1,
    NULL,
    NULL,
    NOW()
)
ON DUPLICATE KEY UPDATE `loan_number` = VALUES(`loan_number`);
