# Database Setup — Phase 1

This directory contains the database schema files for the **Loan Management System (`loan-mgt`)**.

## Phase 1 Schema: `auth.sql`

`auth.sql` is a self-contained, independently importable SQL file that creates the database and the `users` table required for system authentication and user profile management.

### Table: `users`

| Column | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | No | Auto-increment primary key |
| `name` | `VARCHAR(100)` | No | User's full name |
| `email` | `VARCHAR(150)` | No | Unique email address used for login |
| `phone` | `VARCHAR(30)` | Yes | Contact telephone / mobile number |
| `password` | `VARCHAR(255)` | No | Secure BCRYPT password hash |
| `avatar` | `VARCHAR(255)` | Yes | Filename of uploaded avatar image |
| `role` | `ENUM` | No | `admin`, `manager`, `loan_officer`, `collector` |
| `status` | `ENUM` | No | `active`, `inactive` |
| `last_login` | `DATETIME` | Yes | Timestamp of last successful login |
| `created_at` | `TIMESTAMP` | No | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | No | Auto-updated modification timestamp |

---

## Import Instructions

### Option 1: Using MySQL CLI in XAMPP
```bash
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql
```
*(Press Enter if your XAMPP root password is empty)*

### Option 2: Using phpMyAdmin
1. Open phpMyAdmin: `http://localhost/phpmyadmin/`
2. Click the **Import** tab at the top.
3. Choose file: `database/auth.sql`.
4. Click **Import** at the bottom.

---

## Default Development Credentials

| Field | Value |
| :--- | :--- |
| **Email** | `admin@loanmgt.com` |
| **Password** | `Admin@123456` |
| **Role** | `admin` |
| **Status** | `active` |

---

## Future Modular Schema Strategy
Subsequent phases will introduce their own independent schema files (e.g., `customers.sql`, `loans.sql`, `repayments.sql`). Do not create or import them during Phase 1.
