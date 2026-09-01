-- ==========================================================
-- Loan Management System (loan-mgt)
-- Phase 1: Authentication & User Management Database Schema
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `loan_mgt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `loan_mgt`;

-- ----------------------------------------------------------
-- Table structure for table `users`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) NULL DEFAULT NULL,
    `role` ENUM('admin', 'manager', 'loan_officer', 'collector') NOT NULL DEFAULT 'loan_officer',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Default Administrator Account
-- Email: admin@loanmgt.com
-- Password: Admin@123456
-- Role: admin
-- Status: active
-- ----------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `avatar`, `role`, `status`, `created_at`)
VALUES (
    'System Administrator',
    'admin@loanmgt.com',
    '+1 (555) 019-2834',
    '$2y$10$ZVZtArdJQ0NYNKXBxxHH5uIDMB6QfRCcqG1d.pBYgX.eQM95IndIq',
    NULL,
    'admin',
    'active',
    NOW()
)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);
