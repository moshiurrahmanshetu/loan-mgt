-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 8: Users Schema Extension & Role Linkage
-- ==========================================================

USE `loan_mgt`;

-- ----------------------------------------------------------
-- Extend users table with username and foreign key role_id
-- ----------------------------------------------------------
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `username` VARCHAR(50) NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `role_id` INT UNSIGNED NULL AFTER `role`;

-- Set unique index on username
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @indexname = 'idx_users_username';
SET @precexists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname);
SET @query = IF(@precexists = 0, 'ALTER TABLE `users` ADD UNIQUE INDEX `idx_users_username` (`username`)', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Link existing user roles to roles.id based on existing role slug
UPDATE `users` u
JOIN `roles` r ON u.role = r.slug
SET u.role_id = r.id
WHERE u.role_id IS NULL;

-- Populate default username for default admin if empty
UPDATE `users`
SET `username` = 'admin'
WHERE `email` = 'admin@loanmgt.com' AND (`username` IS NULL OR `username` = '');

-- Add Foreign Key constraint for role_id -> roles.id (ON DELETE RESTRICT)
SET @fkexists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND CONSTRAINT_NAME = 'fk_users_role');
SET @query_fk = IF(@fkexists = 0, 'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt_fk FROM @query_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;
