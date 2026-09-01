# Database Setup & Schema Guide

This directory contains the modular database schema files for the **Loan Management System (`loan-mgt`)**.

---

## Required SQL Import Sequence

To ensure database integrity and foreign key compatibility, SQL files MUST be imported in this exact order:

```text
Step 1: database/auth.sql       (Creates database & users table)
Step 2: database/customers.sql  (Creates customers table with FK to users.id)
```

---

## 1. Phase 1 Schema: `auth.sql`

Creates the `loan_mgt` database and the `users` table for system authentication and administrative access.

### Table: `users`

| Column | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | No | Primary Key (Auto-Increment) |
| `name` | `VARCHAR(100)` | No | User's full name |
| `email` | `VARCHAR(150)` | No | Unique email address used for authentication |
| `phone` | `VARCHAR(30)` | Yes | Contact telephone / mobile number |
| `password` | `VARCHAR(255)` | No | BCRYPT password hash |
| `avatar` | `VARCHAR(255)` | Yes | Avatar image filename in `uploads/avatars/` |
| `role` | `ENUM` | No | `admin`, `manager`, `loan_officer`, `collector` |
| `status` | `ENUM` | No | `active`, `inactive` |
| `last_login` | `DATETIME` | Yes | Timestamp of last successful sign-in |
| `created_at` | `TIMESTAMP` | No | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | No | Auto-updated modification timestamp |

---

## 2. Phase 2 Schema: `customers.sql`

Creates the `customers` table for borrower profile management, contact details, financial records, and emergency contact verification.

### Table: `customers`

| Column | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | No | Primary Key (Auto-Increment) |
| `customer_code` | `VARCHAR(20)` | No | Unique system-generated code (e.g. `CUS-000001`) |
| `full_name` | `VARCHAR(100)` | No | Customer full name |
| `phone` | `VARCHAR(30)` | No | Primary telephone / mobile contact |
| `email` | `VARCHAR(150)` | Yes | Contact email address |
| `date_of_birth` | `DATE` | Yes | Date of birth for age & eligibility verification |
| `gender` | `ENUM` | Yes | `male`, `female`, `other` |
| `address` | `TEXT` | Yes | Residential street address |
| `city` | `VARCHAR(50)` | Yes | City or municipality |
| `occupation` | `VARCHAR(100)` | Yes | Profession / Business / Employment |
| `monthly_income` | `DECIMAL(12,2)` | Yes | Declared monthly income (e.g. `4500.00`) |
| `emergency_contact_name` | `VARCHAR(100)` | Yes | Name of guarantor or emergency contact |
| `emergency_contact_phone` | `VARCHAR(30)` | Yes | Telephone of emergency contact |
| `photo` | `VARCHAR(255)` | Yes | Photo filename stored in `uploads/customers/` |
| `status` | `ENUM` | No | `active`, `inactive` (Default: `active`) |
| `created_by` | `INT UNSIGNED` | Yes | FK referencing `users(id)` |
| `created_at` | `TIMESTAMP` | No | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | No | Auto-updated modification timestamp |

### Foreign Key Relational Rule
```sql
CONSTRAINT `fk_customers_created_by`
    FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
```

---

## How to Import via CLI

```bash
# 1. Import Phase 1 Auth Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql

# 2. Import Phase 2 Customers Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/customers.sql
```

---

## Default Development Credentials

| Field | Value |
| :--- | :--- |
| **Email** | `admin@loanmgt.com` |
| **Password** | `Admin@123456` |
| **Role** | `admin` |
| **Status** | `active` |
