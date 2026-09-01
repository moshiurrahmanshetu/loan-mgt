# Loan Management System (`loan-mgt`) — Phase 2

A robust, enterprise-grade **Loan Management System** built with **Raw PHP 8+**, **MySQL**, **Bootstrap 5**, and **Bootstrap Icons**.

---

## 1. Project Purpose & Architecture

`loan-mgt` is designed for financial institutions, microfinance organizations, and credit unions to manage loan origination, customer portfolios, repayment schedules, risk assessment, and financial compliance.

This codebase is architected using **pure native PHP 8+ and MySQL** without external PHP frameworks (no Laravel, Symfony, or CodeIgniter), strictly adhering to enterprise security baselines, modular database schemas, role-based authorization, and clean architectural separation.

---

## 2. Technology Stack

* **Backend**: Raw PHP 8.1+
* **Database**: MySQL 5.7+ / MariaDB 10.4+ (InnoDB, UTF-8 `utf8mb4_unicode_ci`)
* **Database Access**: PHP Data Objects (PDO) with Prepared Statements
* **Frontend**: HTML5, CSS3, JavaScript (ES6+)
* **UI Framework**: Bootstrap 5.3.3 (Bundled locally)
* **Icons**: Bootstrap Icons 1.11.3 (Bundled locally)
* **Session & Security**: Native PHP Session with BCRYPT Hashing, CSRF Protection, and Upload Sandbox

---

## 3. System Requirements

* Web Server: Apache 2.4+ / Nginx (XAMPP, WampServer, or LAMP)
* PHP: Version 8.0 or higher (PHP 8.1+ recommended)
  * Extensions required: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `session`
* MySQL / MariaDB Server

---

## 4. Strict Project Structure

```text
loan-mgt/
├── assets/
│   ├── css/
│   │   └── style.css           # Custom business UI styles, layout, sidebar collapse
│   ├── js/
│   │   └── app.js              # Sidebar toggle, localStorage persistence, tooltips
│   ├── images/
│   │   └── default-avatar.svg  # Local SVG fallback avatar
│   └── vendor/
│       ├── bootstrap/          # Bootstrap 5 CSS & Bundle JS
│       └── bootstrap-icons/    # Bootstrap Icons CSS & Font files
├── auth/
│   ├── login.php               # Login page
│   ├── authenticate.php        # Login submission processing & validation
│   ├── logout.php              # Session destruction & redirect
│   └── forgot-password.php     # Forgot password view & instructions
├── config/
│   ├── app.php                 # App name, dynamic BASE_URL, upload paths
│   ├── database.php            # Centralized PDO connection with exception handling
│   └── session.php             # Secure session start, security options, regenerate helper
├── database/
│   ├── auth.sql                # Phase 1: Standalone users table & default admin seed
│   ├── customers.sql           # Phase 2: Customers table with FK referencing users.id
│   └── README.md               # Database setup and import instructions
├── includes/
│   ├── header.php              # HTML head, CSS imports, meta tags, security headers
│   ├── footer.php              # Footer markup, JS bundle imports
│   ├── navbar.php              # Topbar with sidebar toggle, user badge, profile dropdown
│   ├── sidebar.php             # Responsive collapsible sidebar with active Phase 1 & 2 items
│   ├── auth-check.php          # Protected page access guard & no-cache headers
│   ├── guest-check.php         # Guest guard (redirects authenticated users to dashboard)
│   ├── functions.php           # Security helpers, CSRF, auth checks, customer code generator
│   └── flash.php               # Session-based alert banner system
├── modules/
│   ├── dashboard/
│   │   └── index.php           # System dashboard with live customer metrics & phase roadmap
│   ├── profile/
│   │   ├── index.php           # User profile view (details, security, avatar)
│   │   ├── update.php          # POST handler for updating name/email/phone
│   │   ├── change-password.php # POST handler for validating and updating password
│   │   └── upload-avatar.php   # POST handler for secure avatar upload & image validation
│   └── customers/
│       ├── index.php           # Customer portfolio listing, search, status filter & pagination
│       ├── create.php          # Customer registration form
│       ├── store.php           # Customer registration POST handler & server-side validation
│       ├── view.php            # Customer details profile & future loan area
│       ├── edit.php            # Customer profile editing form
│       ├── update.php          # Customer update POST handler & photo replacement
│       ├── delete.php          # Safe customer deletion POST handler (Admin only)
│       └── toggle-status.php   # Customer status activation toggle handler (Admin/Manager)
├── uploads/
│   ├── avatars/
│   │   └── .htaccess           # Security: Block script execution & disable directory listing
│   └── customers/
│       └── .htaccess           # Security: Block script execution in customer photos folder
├── .htaccess                   # Root security & rewrite protection
├── index.php                   # Root redirect router (auth -> dashboard or login)
└── README.md                   # Full documentation with setup, credentials, and test flows
```

---

## 5. Database Setup & Import Sequence

The database schema is structured modularly. Import the schema files in this exact sequence:

### Option A: Using XAMPP / Command Line
```bash
# 1. Import Phase 1: Authentication & Users Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql

# 2. Import Phase 2: Customers Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/customers.sql
```
*(Press Enter when prompted for password if using default XAMPP credentials)*

### Option B: Using phpMyAdmin
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`
2. Click on the **Import** tab.
3. Import `database/auth.sql` first.
4. Import `database/customers.sql` second.

---

## 6. Database & Application Configuration

### Database Credentials (`config/database.php`)
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'loan_mgt');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### Base URL Configuration (`config/app.php`)
`config/app.php` automatically determines the application's base URL dynamically from `$_SERVER['HTTP_HOST']` and document root paths.

---

## 7. Default Administrator Credentials

| Field | Value |
| :--- | :--- |
| **Email Address** | `admin@loanmgt.com` |
| **Password** | `Admin@123456` |
| **Account Role** | `admin` |
| **Account Status** | `active` |

---

## 8. Role-Based Permissions Matrix

| Module / Action | Admin | Manager | Loan Officer | Collector |
| :--- | :---: | :---: | :---: | :---: |
| **Sign In / Out & Session** | Yes | Yes | Yes | Yes |
| **Dashboard Access** | Yes | Yes | Yes | Yes |
| **Manage Own Profile & Password** | Yes | Yes | Yes | Yes |
| **View Customer Portfolio (`index.php`, `view.php`)** | Yes | Yes | Yes | Yes |
| **Search & Filter Customers** | Yes | Yes | Yes | Yes |
| **Register Customer (`create.php`, `store.php`)** | Yes | Yes | Yes | No |
| **Edit Customer (`edit.php`, `update.php`)** | Yes | Yes | Yes | No |
| **Activate / Deactivate Status (`toggle-status.php`)** | Yes | Yes | No | No |
| **Delete Customer (`delete.php`)** | Yes | No | No | No |

---

## 9. Customer Management User Guide

### A. Viewing & Searching Customers
1. In the sidebar, click **Customers** (or navigate to `modules/customers/index.php`).
2. Search by **Customer Code** (e.g. `CUS-000001`), **Full Name**, **Phone Number**, or **Email**.
3. Filter by status: **All Statuses**, **Active Customers**, or **Inactive Customers**.
4. Use the pagination controls to navigate large datasets without reloading unnecessary records.

### B. Registering a New Customer
1. Click **Add New Customer** at the top right of the customer list.
2. Complete the multi-section form:
   - **Personal Information**: Full Name (*), Primary Phone (*), Email, Date of Birth, Gender.
   - **Residential Address**: Street Address, City / District.
   - **Employment & Financial**: Occupation, Monthly Income.
   - **Emergency Contact**: Contact Person Name, Phone Number.
   - **Photo Upload**: Optional customer photo (JPG, PNG, WebP, max 2MB).
   - **Status**: Active or Inactive.
3. Click **Register Customer**.
4. The system auto-generates a unique sequential code (`CUS-000001`, `CUS-000002`, ...), stores the record securely, and redirects to the customer details profile.

### C. Viewing Customer Profile
1. Click the **View** icon (or click the customer's name / code) from the table.
2. The customer profile displays full contact details, age computation, residential address, financial metrics, emergency guarantor contact, and system audit metadata (Created By, Created Date, Updated Date).
3. A clean placeholder section reserves space for the future loan origination engine.

### D. Editing Customer Profile
1. Click **Edit Profile** from the table or profile page.
2. Modify any personal, address, financial, or emergency contact fields.
3. The customer code is protected and read-only.
4. Replace or remove the customer photo safely.
5. Click **Save Changes** to update the database and disk storage.

### E. Toggling Customer Status
1. Click the status toggle button in the table or customer profile.
2. Confirm the action to switch between **Active** and **Inactive**.
3. Inactive borrowers cannot be issued loans in future phases.

### F. Deleting a Customer (Admin Only)
1. System Administrators can click the **Delete** button.
2. Confirm the prompt to remove the record.
3. The customer record and associated photo file are deleted safely from the system.

---

## 10. Phase Roadmap

* **Phase 1 (Completed)**: Foundation, Authentication, Session Guards, CSRF, Common Layouts, Profile & Password Security.
* **Phase 2 (Completed)**: Customer Management Module, Sequential Code Generation, Search/Filter/Pagination, Photo Upload Sandbox, Role Restrictions.
* **Phase 3 (Upcoming)**: Loan Products & Loan Application Engine (`loans.sql`).
* **Phase 4 (Upcoming)**: Loan Underwriting, Approval Workflows & Disbursement.
* **Phase 5 (Upcoming)**: Installment Schedules, Repayments & Collection Tracking (`repayments.sql`).
* **Phase 6 (Upcoming)**: Arrears & Delinquency Management.
* **Phase 7 (Upcoming)**: Financial Reporting & Audit Analytics.