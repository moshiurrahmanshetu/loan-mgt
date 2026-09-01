-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 2: Customer Management Database Schema
--
-- IMPORTANT:
-- This file must be imported AFTER database/auth.sql has been
-- imported, as it establishes a Foreign Key referencing `users.id`.
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `customers`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) NULL DEFAULT NULL,
    `date_of_birth` DATE NULL DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') NULL DEFAULT NULL,
    `address` TEXT NULL DEFAULT NULL,
    `city` VARCHAR(50) NULL DEFAULT NULL,
    `occupation` VARCHAR(100) NULL DEFAULT NULL,
    `monthly_income` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `emergency_contact_name` VARCHAR(100) NULL DEFAULT NULL,
    `emergency_contact_phone` VARCHAR(30) NULL DEFAULT NULL,
    `photo` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customers_code` (`customer_code`),
    INDEX `idx_customers_phone` (`phone`),
    INDEX `idx_customers_email` (`email`),
    INDEX `idx_customers_status` (`status`),
    INDEX `idx_customers_created_by` (`created_by`),
    CONSTRAINT `fk_customers_created_by` 
        FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Sample Seed Data for Testing (Phase 2 Verification)
-- ----------------------------------------------------------
INSERT INTO `customers` (
    `customer_code`, `full_name`, `phone`, `email`, `date_of_birth`, 
    `gender`, `address`, `city`, `occupation`, `monthly_income`, 
    `emergency_contact_name`, `emergency_contact_phone`, `photo`, 
    `status`, `created_by`, `created_at`
)
VALUES 
(
    'CUS-000001',
    'Rahim Uddin',
    '+1 (555) 234-5678',
    'rahim.uddin@example.com',
    '1985-04-12',
    'male',
    '742 Evergreen Terrace, Sector 4',
    'Dhaka',
    'Senior Software Engineer',
    4500.00,
    'Fatema Begum',
    '+1 (555) 234-5679',
    NULL,
    'active',
    1,
    NOW()
),
(
    'CUS-000002',
    'Sarah Jenkins',
    '+1 (555) 345-6789',
    'sarah.j@example.com',
    '1992-09-24',
    'female',
    '128 Oakridge Lane, Apt 3B',
    'Chittagong',
    'Retail Business Owner',
    3200.00,
    'Michael Jenkins',
    '+1 (555) 345-0011',
    NULL,
    'active',
    1,
    NOW()
),
(
    'CUS-000003',
    'Tariqul Islam',
    '+1 (555) 456-7890',
    'tariqul.islam@example.com',
    '1988-11-05',
    'male',
    '55 Green Road, Dhanmondi',
    'Dhaka',
    'Civil Contractor',
    5800.00,
    'Nasrin Akter',
    '+1 (555) 456-9988',
    NULL,
    'active',
    1,
    NOW()
),
(
    'CUS-000004',
    'Emily Carter',
    '+1 (555) 567-8901',
    'emily.carter@example.com',
    '1995-02-18',
    'female',
    '88 Park Avenue, Suite 10',
    'Sylhet',
    'Marketing Consultant',
    2800.00,
    'David Carter',
    '+1 (555) 567-1122',
    NULL,
    'inactive',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE `customer_code` = VALUES(`customer_code`);
