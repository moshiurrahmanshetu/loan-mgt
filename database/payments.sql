-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 5: Payment Collection & Repayment Management Schema
--
-- IMPORTANT:
-- This file must be imported AFTER:
--   1. database/auth.sql
--   2. database/customers.sql
--   3. database/loan_products.sql
--   4. database/loans.sql
--   5. database/disbursement.sql
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- 1. Extend `loans.status` ENUM to support 'completed'
-- ----------------------------------------------------------
ALTER TABLE `loans` 
    MODIFY COLUMN `status` ENUM('draft', 'pending', 'approved', 'active', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending';

-- ----------------------------------------------------------
-- 2. Create `loan_payments` table for Repayment Transaction Ledger
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_reference` VARCHAR(30) NOT NULL UNIQUE,
    `loan_id` INT UNSIGNED NOT NULL,
    `installment_id` INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `payment_method` ENUM('cash', 'bank_transfer', 'mobile_banking') NOT NULL DEFAULT 'cash',
    `notes` TEXT NULL DEFAULT NULL,
    `collected_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payments_ref` (`payment_reference`),
    INDEX `idx_payments_loan` (`loan_id`),
    INDEX `idx_payments_installment` (`installment_id`),
    INDEX `idx_payments_customer` (`customer_id`),
    INDEX `idx_payments_date` (`payment_date`),
    INDEX `idx_payments_collected_by` (`collected_by`),
    CONSTRAINT `fk_payments_loan_id` 
        FOREIGN KEY (`loan_id`) 
        REFERENCES `loans` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_installment_id` 
        FOREIGN KEY (`installment_id`) 
        REFERENCES `loan_installments` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_collected_by` 
        FOREIGN KEY (`collected_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
