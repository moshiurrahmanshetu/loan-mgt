-- ==========================================================
-- Loan Management System (loan-mgt)
-- Master Fresh Installation Database Package
-- Version: 9.0.0
--
-- This script contains all core database tables, constraints, 
-- and initial seed data in strict foreign-key dependency order.
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. Table structure for table `permissions`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_permissions_slug` (`slug`),
    INDEX `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed 32 System Granular Permissions across 11 Modules
-- ----------------------------------------------------------
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`)
VALUES 
    -- Customer Management
    ('View Customers', 'customers.view', 'Customers', 'View customer portfolio, borrower listings, and profile details.'),
    ('Create Customer', 'customers.create', 'Customers', 'Register new borrower customers and upload KYC photos.'),
    ('Edit Customer', 'customers.edit', 'Customers', 'Modify existing borrower information and toggle active status.'),
    ('Delete Customer', 'customers.delete', 'Customers', 'Delete customer records with no active financial obligations.'),

    -- Loan Product Management
    ('View Loan Products', 'loan_products.view', 'Loan Products', 'View lending product catalog and lending parameters.'),
    ('Create Loan Product', 'loan_products.create', 'Loan Products', 'Define new loan product templates and interest formulas.'),
    ('Edit Loan Product', 'loan_products.edit', 'Loan Products', 'Update lending limits, interest rates, and loan terms.'),
    ('Delete Loan Product', 'loan_products.delete', 'Loan Products', 'Delete unused loan products with no active loan attachments.'),

    -- Loan Application Management
    ('View Loans', 'loans.view', 'Loans', 'View loan applications, contract snapshots, and loan details.'),
    ('Create Loan Application', 'loans.create', 'Loans', 'Originate new loan applications on behalf of borrowers.'),
    ('Edit Loan Application', 'loans.edit', 'Loans', 'Modify draft or pending loan applications.'),
    ('Delete/Cancel Loan', 'loans.delete', 'Loans', 'Cancel or delete draft loan applications.'),
    ('Approve Loan', 'loans.approve', 'Loans', 'Perform underwriting credit approvals for pending loans.'),
    ('Reject Loan', 'loans.reject', 'Loans', 'Reject underwriting credit requests with recorded reasons.'),

    -- Loan Capital Disbursement
    ('View Disbursements', 'disbursements.view', 'Disbursement', 'View capital disbursement histories and generated repayment schedules.'),
    ('Disburse Loan', 'disbursements.create', 'Disbursement', 'Authorize fund release, execute capital disbursement, and activate repayment schedules.'),

    -- Repayment & Payment Collection
    ('View Repayments', 'repayments.view', 'Repayments', 'View repayment ledgers, installment schedules, and payment histories.'),
    ('Collect Payment', 'repayments.create', 'Repayments', 'Process payment collections, partial payments, and issue receipts.'),

    -- Overdue Delinquency Tracking
    ('View Overdue Tracking', 'overdue.view', 'Overdue', 'Monitor past-due delinquent installments and aging bands.'),

    -- Analytics & Financial Reports
    ('View Reports', 'reports.view', 'Reports', 'Access reports dashboard and view operational financial summaries.'),
    ('Export Reports', 'reports.export', 'Reports', 'Export financial reports to CSV files and access clean print layouts.'),

    -- User Management
    ('View Users', 'users.view', 'Users', 'Browse staff user accounts, directory listings, and profiles.'),
    ('Create User', 'users.create', 'Users', 'Register new staff users and assign account roles.'),
    ('Edit User', 'users.edit', 'Users', 'Update staff user profiles, role assignments, and passwords.'),
    ('Delete User', 'users.delete', 'Users', 'Deactivate or delete staff user accounts.'),

    -- Role Management
    ('View Roles', 'roles.view', 'Roles', 'View system and custom roles, descriptions, and assigned counts.'),
    ('Create Role', 'roles.create', 'Roles', 'Define new custom operational roles.'),
    ('Edit Role', 'roles.edit', 'Roles', 'Modify custom roles and assign module permissions.'),
    ('Delete Role', 'roles.delete', 'Roles', 'Delete custom roles when no users are assigned.'),

    -- Permission Management
    ('View Permissions', 'permissions.view', 'Permissions', 'View granular permission matrix and module descriptions.'),

    -- Settings Management
    ('View Settings', 'settings.view', 'Settings', 'View system settings, organization details, and localization.'),
    ('Edit Settings', 'settings.edit', 'Settings', 'Update system branding, logos, currency symbols, and regional settings.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`), `description` = VALUES(`description`);

-- ----------------------------------------------------------
-- 2. Table structure for table `roles`
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
-- Seed 4 Core Protected System Roles
-- ----------------------------------------------------------
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `status`)
VALUES 
    (1, 'Administrator', 'admin', 'Full unrestricted administrative access across all modules and configuration settings.', 1, 'active'),
    (2, 'Loan Manager', 'manager', 'Oversees loan underwriting, performs credit approvals, authorizes disbursements, and analyzes portfolio reports.', 1, 'active'),
    (3, 'Loan Officer', 'loan_officer', 'Customer onboarding, loan application origination, and borrower file management.', 1, 'active'),
    (4, 'Debt Collector', 'collector', 'Collection management, payment receipt processing, and overdue tracking operations.', 1, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `is_system` = VALUES(`is_system`);

-- ----------------------------------------------------------
-- 3. Table structure for table `role_permissions`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
    INDEX `idx_rp_role_id` (`role_id`),
    INDEX `idx_rp_permission_id` (`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed Role Permission Mappings
-- ----------------------------------------------------------
-- Admin: Receives all permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Manager: Customers, Products, Loans, Disbursement, Repayments, Overdue, Reports
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`
WHERE `slug` IN (
    'customers.view', 'customers.create', 'customers.edit',
    'loan_products.view', 'loan_products.create', 'loan_products.edit',
    'loans.view', 'loans.create', 'loans.edit', 'loans.approve', 'loans.reject',
    'disbursements.view', 'disbursements.create',
    'repayments.view', 'repayments.create',
    'overdue.view',
    'reports.view', 'reports.export'
);

-- Loan Officer: Customers, Loan Products (view), Loans (view/create/edit), Repayments (view), Reports (view)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions`
WHERE `slug` IN (
    'customers.view', 'customers.create', 'customers.edit',
    'loan_products.view',
    'loans.view', 'loans.create', 'loans.edit',
    'repayments.view',
    'overdue.view',
    'reports.view'
);

-- Collector: Customers (view), Repayments (view/collect), Overdue (view), Reports (view)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions`
WHERE `slug` IN (
    'customers.view',
    'repayments.view', 'repayments.create',
    'overdue.view',
    'reports.view'
);

-- ----------------------------------------------------------
-- 4. Table structure for table `settings`
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
-- Seed Initial System Settings
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

-- ----------------------------------------------------------
-- 5. Table structure for table `users`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) NULL DEFAULT NULL,
    `role` ENUM('admin', 'manager', 'loan_officer', 'collector') NOT NULL DEFAULT 'loan_officer',
    `role_id` INT UNSIGNED NULL DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_role_id` (`role_id`),
    INDEX `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Table structure for table `customers`
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
-- 7. Table structure for table `loan_products`
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
-- Seed Default Standard Loan Product Templates
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
    NULL,
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
    NULL,
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
    NULL,
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
    'active',
    NULL,
    NOW()
)
ON DUPLICATE KEY UPDATE `product_code` = VALUES(`product_code`);

-- ----------------------------------------------------------
-- 8. Table structure for table `loans`
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
    `disbursement_date` DATE NULL DEFAULT NULL,
    `disbursed_amount` DECIMAL(12, 2) NULL DEFAULT NULL,
    `disbursement_method` ENUM('cash', 'bank_transfer', 'mobile_banking') NULL DEFAULT NULL,
    `disbursed_by` INT UNSIGNED NULL DEFAULT NULL,
    `disbursed_at` DATETIME NULL DEFAULT NULL,
    `status` ENUM('draft', 'pending', 'approved', 'active', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
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
    INDEX `idx_loans_disbursement_date` (`disbursement_date`),
    INDEX `idx_loans_created_by` (`created_by`),
    INDEX `idx_loans_approved_by` (`approved_by`),
    INDEX `idx_loans_disbursed_by` (`disbursed_by`),
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
        ON UPDATE CASCADE,
    CONSTRAINT `fk_loans_disbursed_by` 
        FOREIGN KEY (`disbursed_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Table structure for table `loan_installments`
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

-- ----------------------------------------------------------
-- 10. Table structure for table `loan_payments`
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

SET FOREIGN_KEY_CHECKS = 1;
