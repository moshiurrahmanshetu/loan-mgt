# Database Setup & Schema Guide

This directory contains the modular, independently importable database schema files for the **Loan Management System (`loan-mgt`)**.

---

## Required SQL Import Sequence

To ensure database integrity and foreign key compatibility, SQL files MUST be imported in this exact order:

```text
Step 1: database/auth.sql           (Creates database & users table)
Step 2: database/customers.sql      (Creates customers table with FK to users.id)
Step 3: database/loan_products.sql  (Creates loan_products table with FK to users.id)
Step 4: database/loans.sql          (Creates loans table with FK to customers, loan_products, users)
Step 5: database/disbursement.sql   (Extends loans table & creates loan_installments with FK to loans.id)
Step 6: database/payments.sql       (Extends loans status & creates loan_payments with FK to loans, installments, customers, users)
```

---

## 1. Phase 1 Schema: `auth.sql`

Creates the `loan_mgt` database and the `users` table for system authentication and administrative access.

### Table: `users`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `name` VARCHAR(100) NOT NULL
* `email` VARCHAR(150) NOT NULL UNIQUE
* `phone` VARCHAR(30) NULL
* `password` VARCHAR(255) NOT NULL (BCRYPT hash)
* `avatar` VARCHAR(255) NULL
* `role` ENUM('admin', 'manager', 'loan_officer', 'collector') NOT NULL DEFAULT 'loan_officer'
* `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
* `last_login` DATETIME NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 2. Phase 2 Schema: `customers.sql`

Creates the `customers` table for borrower profile management, KYC, and guarantor contacts.

### Table: `customers`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `customer_code` VARCHAR(20) NOT NULL UNIQUE (e.g. `CUS-000001`)
* `full_name` VARCHAR(100) NOT NULL
* `phone` VARCHAR(30) NOT NULL
* `email` VARCHAR(150) NULL
* `date_of_birth` DATE NULL
* `gender` ENUM('male', 'female', 'other') NULL
* `address` TEXT NULL
* `city` VARCHAR(50) NULL
* `occupation` VARCHAR(100) NULL
* `monthly_income` DECIMAL(12, 2) DEFAULT 0.00
* `emergency_contact_name` VARCHAR(100) NULL
* `emergency_contact_phone` VARCHAR(30) NULL
* `photo` VARCHAR(255) NULL
* `status` ENUM('active', 'inactive') DEFAULT 'active'
* `created_by` INT UNSIGNED NULL (FK -> `users.id`)
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 3. Phase 3 Schema: `loan_products.sql`

Creates the `loan_products` table defining product rules, interest methods, limits, and processing fee templates.

### Table: `loan_products`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `product_code` VARCHAR(20) NOT NULL UNIQUE (e.g. `LP-001`)
* `name` VARCHAR(100) NOT NULL
* `description` TEXT NULL
* `minimum_amount` DECIMAL(12, 2) NOT NULL DEFAULT 1000.00
* `maximum_amount` DECIMAL(12, 2) NOT NULL DEFAULT 50000.00
* `interest_rate` DECIMAL(5, 2) NOT NULL DEFAULT 10.00 (Percentage)
* `interest_method` ENUM('flat', 'reducing_balance') NOT NULL DEFAULT 'flat'
* `term_min` INT UNSIGNED NOT NULL DEFAULT 1
* `term_max` INT UNSIGNED NOT NULL DEFAULT 12
* `term_unit` ENUM('days', 'weeks', 'months') NOT NULL DEFAULT 'months'
* `repayment_frequency` ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL DEFAULT 'monthly'
* `processing_fee` DECIMAL(5, 2) NOT NULL DEFAULT 0.00 (%)
* `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
* `created_by` INT UNSIGNED NULL (FK -> `users.id`)
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 4. Phase 3 Schema: `loans.sql`

Creates the `loans` table storing loan applications, product parameter snapshots, and approval audit trails.

### Table: `loans`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `loan_number` VARCHAR(30) NOT NULL UNIQUE (e.g. `LN-000001`)
* `customer_id` INT UNSIGNED NOT NULL (FK -> `customers.id`, RESTRICT on delete)
* `loan_product_id` INT UNSIGNED NOT NULL (FK -> `loan_products.id`, RESTRICT on delete)
* `requested_amount` DECIMAL(12, 2) NOT NULL
* `interest_rate` DECIMAL(5, 2) NOT NULL (Snapshot)
* `interest_method` ENUM('flat', 'reducing_balance') NOT NULL (Snapshot)
* `term` INT UNSIGNED NOT NULL (Snapshot)
* `term_unit` ENUM('days', 'weeks', 'months') NOT NULL (Snapshot)
* `repayment_frequency` ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL (Snapshot)
* `processing_fee_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00 (Snapshot)
* `processing_fee_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `estimated_interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `estimated_total_payable` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `purpose` TEXT NULL
* `application_date` DATE NOT NULL
* `status` ENUM('draft', 'pending', 'approved', 'active', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'
* `notes` TEXT NULL
* `rejection_reason` TEXT NULL
* `created_by` INT UNSIGNED NULL (FK -> `users.id`)
* `approved_by` INT UNSIGNED NULL (FK -> `users.id`)
* `approved_at` DATETIME NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 5. Phase 4 Schema: `disbursement.sql`

Extends the `loans` table with disbursement audit fields and creates `loan_installments` for repayment schedules.

### Extended Columns on `loans`:
* `status`: Extended with `'active'`
* `disbursement_date` DATE NULL
* `disbursed_amount` DECIMAL(12, 2) NULL
* `disbursement_method` ENUM('cash', 'bank_transfer', 'mobile_banking') NULL
* `disbursed_by` INT UNSIGNED NULL (FK -> `users.id`)
* `disbursed_at` DATETIME NULL

### Table: `loan_installments`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `loan_id` INT UNSIGNED NOT NULL (FK -> `loans.id` ON DELETE CASCADE)
* `installment_number` INT UNSIGNED NOT NULL
* `due_date` DATE NOT NULL
* `principal_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `installment_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `remaining_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
* `status` ENUM('pending', 'paid', 'partial', 'overdue') NOT NULL DEFAULT 'pending'
* `paid_date` DATE NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* Unique Constraint: `(loan_id, installment_number)`

---

## 6. Phase 5 Schema: `payments.sql`

Extends `loans.status` with `'completed'` and creates `loan_payments` table for recording repayment transactions.

### Table: `loan_payments`
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `payment_reference` VARCHAR(30) NOT NULL UNIQUE (e.g. `PAY-000001`)
* `loan_id` INT UNSIGNED NOT NULL (FK -> `loans.id` ON DELETE RESTRICT)
* `installment_id` INT UNSIGNED NOT NULL (FK -> `loan_installments.id` ON DELETE RESTRICT)
* `customer_id` INT UNSIGNED NOT NULL (FK -> `customers.id` ON DELETE RESTRICT)
* `payment_date` DATE NOT NULL
* `amount` DECIMAL(12, 2) NOT NULL
* `payment_method` ENUM('cash', 'bank_transfer', 'mobile_banking') NOT NULL DEFAULT 'cash'
* `notes` TEXT NULL
* `collected_by` INT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE RESTRICT)
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 7. Phase 6: Reports & Financial Analytics (No Schema Migration Required)

Phase 6 implements reporting, portfolio summaries, overdue delinquency tracking, and CSV export without altering database schemas or creating redundant report snapshot tables. All reports query the live, transactional tables created in Phases 1–5 (`users`, `customers`, `loan_products`, `loans`, `loan_installments`, and `loan_payments`) utilizing prepared statements, efficient indexing, and aggregation.

---

## 8. Phase 8 Schema: Roles, Permissions, Role Permissions & Settings

### Table: `roles` (`roles.sql`)
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `name` VARCHAR(100) NOT NULL
* `slug` VARCHAR(50) NOT NULL UNIQUE
* `description` TEXT NULL
* `is_system` TINYINT(1) NOT NULL DEFAULT 0
* `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Table: `permissions` (`permissions.sql`)
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `name` VARCHAR(100) NOT NULL
* `slug` VARCHAR(100) NOT NULL UNIQUE
* `module` VARCHAR(50) NOT NULL
* `description` VARCHAR(255) NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Table: `role_permissions` (`role_permissions.sql`)
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `role_id` INT UNSIGNED NOT NULL (FK -> `roles.id` ON DELETE CASCADE)
* `permission_id` INT UNSIGNED NOT NULL (FK -> `permissions.id` ON DELETE CASCADE)
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* Unique Constraint: `(role_id, permission_id)`

### Table: `settings` (`settings.sql`)
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `setting_key` VARCHAR(100) NOT NULL UNIQUE
* `setting_value` TEXT NULL
* `setting_type` ENUM('text', 'number', 'boolean', 'image') NOT NULL DEFAULT 'text'
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Users Table Extension (`users.sql`)
* Adds `username` VARCHAR(50) NULL UNIQUE
* Adds `role_id` INT UNSIGNED NULL (FK -> `roles.id` ON DELETE RESTRICT)

---

## How to Import via CLI (Fresh Database)

```bash
# 1. Import Phase 1: Authentication & Users Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql

# 2. Import Phase 8 Roles & Permissions (Before Dependent Tables)
C:\xampp\mysql\bin\mysql.exe -u root -p < database/roles.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database/permissions.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database/role_permissions.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database/settings.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database/users.sql

# 3. Import Phase 2: Customers Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/customers.sql

# 4. Import Phase 3: Loan Products Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/loan_products.sql

# 5. Import Phase 3: Loan Applications Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/loans.sql

# 6. Import Phase 4: Disbursement & Repayment Schedule Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/disbursement.sql

# 7. Import Phase 5: Repayments & Payment Collection Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/payments.sql
```
