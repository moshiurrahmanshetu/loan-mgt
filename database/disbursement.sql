-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 4: Loan Disbursement & Repayment Schedule Schema
--
-- IMPORTANT:
-- This file must be imported AFTER:
--   1. database/auth.sql
--   2. database/customers.sql
--   3. database/loan_products.sql
--   4. database/loans.sql
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- 1. Extend `loans` table with status 'active' and disbursement audit fields
-- ----------------------------------------------------------
ALTER TABLE `loans` 
    MODIFY COLUMN `status` ENUM('draft', 'pending', 'approved', 'active', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN `disbursement_date` DATE NULL DEFAULT NULL AFTER `application_date`,
    ADD COLUMN `disbursed_amount` DECIMAL(12, 2) NULL DEFAULT NULL AFTER `disbursement_date`,
    ADD COLUMN `disbursement_method` ENUM('cash', 'bank_transfer', 'mobile_banking') NULL DEFAULT NULL AFTER `disbursed_amount`,
    ADD COLUMN `disbursed_by` INT UNSIGNED NULL DEFAULT NULL AFTER `disbursement_method`,
    ADD COLUMN `disbursed_at` DATETIME NULL DEFAULT NULL AFTER `disbursed_by`,
    ADD INDEX `idx_loans_disbursement_date` (`disbursement_date`),
    ADD INDEX `idx_loans_disbursed_by` (`disbursed_by`),
    ADD CONSTRAINT `fk_loans_disbursed_by` 
        FOREIGN KEY (`disbursed_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE;

-- ----------------------------------------------------------
-- 2. Create `loan_installments` table for Repayment Schedules
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_installments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT UNSIGNED NOT NULL,
    `installment_number` INT UNSIGNED NOT NULL,
    `due_date` DATE NOT NULL,
    `principal_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `installment_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `remaining_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'paid', 'partial', 'overdue') NOT NULL DEFAULT 'pending',
    `paid_date` DATE NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_loan_installment` (`loan_id`, `installment_number`),
    INDEX `idx_installments_loan_id` (`loan_id`),
    INDEX `idx_installments_due_date` (`due_date`),
    INDEX `idx_installments_status` (`status`),
    CONSTRAINT `fk_installments_loan_id` 
        FOREIGN KEY (`loan_id`) 
        REFERENCES `loans` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
