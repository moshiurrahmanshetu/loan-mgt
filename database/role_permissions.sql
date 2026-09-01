-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 8: Role Permissions Junction Schema
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `role_permissions`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
    INDEX `idx_rp_role` (`role_id`),
    INDEX `idx_rp_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed Default Role Permissions Mappings
-- ----------------------------------------------------------

-- 1. Administrator: All Permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'admin';

-- 2. Loan Manager Permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.slug IN (
    'customers.view', 'customers.create', 'customers.edit',
    'loan_products.view', 'loan_products.create', 'loan_products.edit',
    'loans.view', 'loans.create', 'loans.edit', 'loans.approve', 'loans.reject',
    'disbursements.view', 'disbursements.create',
    'repayments.view', 'repayments.create',
    'overdue.view',
    'reports.view', 'reports.export'
)
WHERE r.slug = 'manager';

-- 3. Loan Officer Permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.slug IN (
    'customers.view', 'customers.create', 'customers.edit',
    'loan_products.view',
    'loans.view', 'loans.create', 'loans.edit',
    'repayments.view',
    'overdue.view',
    'reports.view'
)
WHERE r.slug = 'loan_officer';

-- 4. Debt Collector Permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.slug IN (
    'customers.view',
    'loans.view',
    'repayments.view', 'repayments.create',
    'overdue.view',
    'reports.view'
)
WHERE r.slug = 'collector';
