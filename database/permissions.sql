-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 8: Permissions Schema
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `permissions`
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
-- Seed System Granular Permissions
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
