# Loan Management System (`loan-mgt`) — Phase 8

A robust, enterprise-grade **Loan Management System** built with **Raw PHP 8+**, **MySQL**, **Bootstrap 5**, and **Bootstrap Icons**.

---

## 1. Project Purpose & Architecture

`loan-mgt` is designed for financial institutions, microfinance organizations, and credit unions to manage loan origination, customer portfolios, repayment schedules, risk assessment, collection operations, and financial compliance.

This codebase is architected using **pure native PHP 8+ and MySQL** without external PHP frameworks (no Laravel, Symfony, or CodeIgniter), strictly adhering to enterprise security baselines, modular database schemas, role-based authorization, and clean architectural separation.

---

## 2. Technology Stack

* **Backend**: Raw PHP 8.1+
* **Database**: MySQL 5.7+ / MariaDB 10.4+ (InnoDB, UTF-8 `utf8mb4_unicode_ci`)
* **Database Access**: PHP Data Objects (PDO) with Prepared Statements & Transactions
* **Frontend**: HTML5, CSS3, JavaScript (ES6+)
* **UI Framework**: Bootstrap 5.3.3 (Bundled locally)
* **Icons**: Bootstrap Icons 1.11.3 (Bundled locally)
* **Session & Security**: Native PHP Session with BCRYPT Hashing, CSRF Protection, Upload Sandbox & Role Permission Gates

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
│   │   └── style.css           # Custom business UI styles, layout, sidebar collapse, badges
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
│   ├── app.php                 # App name, dynamic BASE_URL, upload paths, version
│   ├── database.php            # Centralized PDO connection with exception handling
│   └── session.php             # Secure session start, security options, regenerate helper
├── database/
│   ├── auth.sql                # Step 1: Authentication & users schema + default admin
│   ├── roles.sql               # Step 2: Roles schema & default system roles
│   ├── permissions.sql         # Step 3: Granular 29-permission system definitions
│   ├── role_permissions.sql   # Step 4: Role-permission junction & default mapping
│   ├── settings.sql            # Step 5: Key-value system configuration schema
│   ├── users.sql               # Step 6: Users schema extension & FK role_id linkage
│   ├── customers.sql           # Step 7: Customers schema (FK to users.id)
│   ├── loan_products.sql       # Step 8: Loan product templates (FK to users.id)
│   ├── loans.sql               # Step 9: Loan applications & contract snapshot (FK to customers, loan_products, users)
│   ├── disbursement.sql        # Step 10: Loan disbursement columns & loan_installments schedule table
│   ├── payments.sql            # Step 11: Loan completion status & loan_payments transactions ledger
│   └── README.md               # Database setup and import sequence
├── includes/
│   ├── header.php              # HTML head, CSS imports, meta tags, security headers, dynamic branding
│   ├── footer.php              # Footer markup, JS bundle imports
│   ├── navbar.php              # Topbar with sidebar toggle, user badge, profile dropdown
│   ├── sidebar.php             # Collapsible sidebar (Dashboard, Customers, Products, Loans, Repayments, Reports, Users, Roles, Settings)
│   ├── auth-check.php          # Protected page access guard, real-time user status & role sync
│   ├── guest-check.php         # Guest guard (redirects authenticated users to dashboard)
│   ├── functions.php           # Security helpers, CSRF, auth checks, permissions, settings & formatting
│   └── flash.php               # Session-based alert banner system
├── modules/
│   ├── dashboard/
│   │   └── index.php           # Executive operations dashboard with live KPI metrics & 6-month activity
│   ├── profile/
│   │   ├── index.php           # User profile view (details, security, avatar)
│   │   ├── update.php          # POST handler for updating name/email/phone
│   │   ├── change-password.php # POST handler for validating and updating password
│   │   └── upload-avatar.php   # POST handler for secure avatar upload & image validation
│   ├── customers/
│   │   ├── index.php           # Customer portfolio listing, search, status filter & pagination
│   │   ├── create.php          # Customer registration form
│   │   ├── store.php           # Customer registration POST handler & server-side validation
│   │   ├── view.php            # Customer details profile, KYC data & linked loan history
│   │   ├── edit.php            # Customer profile editing form
│   │   ├── update.php          # Customer update POST handler & photo replacement
│   │   ├── delete.php          # Safe customer deletion POST handler (Admin only)
│   │   └── toggle-status.php   # Customer status activation toggle handler (Admin/Manager)
│   ├── loan-products/
│   │   ├── index.php           # Loan products catalog, status filter & pagination
│   │   ├── create.php          # Add loan product form with lending rules
│   │   ├── store.php           # Product creation POST handler & server validation
│   │   ├── view.php            # Product rules details & linked portfolio summary
│   │   ├── edit.php            # Edit product rules form
│   │   ├── update.php          # Update product POST handler
│   │   ├── delete.php          # Safe product deletion (blocked if referenced by loans)
│   │   └── toggle-status.php   # Toggle product active/inactive status
│   ├── loans/
│   │   ├── index.php           # Loan applications listing, search, status filter & pagination
│   │   ├── create.php          # Interactive loan application form with real-time preview
│   │   ├── store.php           # Application POST handler (Draft vs Submit, full server validation)
│   │   ├── view.php            # Comprehensive loan file, snapshot terms, disbursement audit & schedule
│   │   ├── edit.php            # Edit application form (Draft / Pending only)
│   │   ├── update.php          # Update application POST handler
│   │   ├── cancel.php          # Cancel draft/pending application handler
│   │   ├── approve.php         # Underwriting approval handler (Admin/Manager, self-approval blocked)
│   │   ├── reject.php          # Underwriting rejection handler (records reason)
│   │   ├── disburse.php        # Disbursement confirmation screen with parameters & preview
│   │   ├── process-disbursement.php # Atomic POST handler: row lock, status activation & schedule insert
│   │   └── schedule.php        # Standalone printable repayment schedule view
│   ├── repayments/
│   │   ├── index.php           # Repayment & collection dashboard, active portfolio & quick action links
│   │   ├── view.php            # Loan repayment file, installment amortization ledger & payment history
│   │   ├── collect.php         # Payment collection form with installment switcher & balance check
│   │   ├── process-payment.php # Transactional POST handler: overpayment guard, balance update & loan completion
│   │   ├── receipt.php         # Official printable payment receipt (@media print layout)
│   │   ├── payment-history.php # Global payment transactions history with date/method filters & search
│   │   └── overdue.php         # Overdue delinquent installments tracking with days-late calculation
│   ├── reports/
│   │   ├── index.php           # Reports Central Dashboard with 9 KPI cards & product breakdowns
│   │   ├── loan-report.php     # Loan Applications & Portfolio report with status/product/date filters
│   │   ├── disbursement-report.php # Capital disbursements released by channel and authorizing officer
│   │   ├── repayment-report.php    # Collections transactions ledger by channel and collector
│   │   ├── overdue-report.php      # Delinquent installments tracking with aging bands & days overdue
│   │   ├── customer-report.php     # Borrower summary & lifetime borrowing vs repayments
│   │   ├── portfolio-report.php    # Financial reconciliation, expected revenue, and status breakdown
│   │   ├── print.php           # Universal print template (@media print stylesheet)
│   │   └── export-csv.php      # Universal CSV export handler with formula injection sanitization
│   ├── users/
│   │   ├── index.php           # Staff user directory, search, role filter, status filter, pagination
│   │   ├── create.php          # Register new staff user form
│   │   ├── store.php           # User creation POST handler with password hash & avatar upload
│   │   ├── view.php            # Staff user profile, KYC, granted permissions, activity ledger
│   │   ├── edit.php            # Edit user account details & role assignment
│   │   ├── update.php          # User update POST handler with uniqueness checks
│   │   ├── change-password.php # Dedicated administrative password reset view & handler
│   │   ├── toggle-status.php   # Account activation/deactivation POST handler with self-protection
│   │   └── delete.php          # Safe deletion POST handler with foreign audit check
│   ├── roles/
│   │   ├── index.php           # System & custom roles catalog, assigned users count & perms count
│   │   ├── create.php          # Add custom role form
│   │   ├── store.php           # Custom role POST handler with slug uniqueness check
│   │   ├── view.php            # Role overview, assigned staff accounts, and granted permissions
│   │   ├── edit.php            # Edit custom role attributes
│   │   ├── update.php          # Role update POST handler
│   │   ├── permissions.php     # Grouped permission assignment matrix with JS select all/none
│   │   ├── save-permissions.php# Transactional permission assignment POST handler
│   │   └── delete.php          # Role delete handler with system role & user assignment protection
│   ├── permissions/
│   │   └── index.php           # Read-only matrix of 29 system capabilities across 11 modules
│   └── settings/
│       ├── index.php           # System configuration: Organization Info, Regional, Branding & Logo
│       └── update.php          # Settings update POST handler with logo upload sandbox
├── uploads/
│   ├── avatars/
│   │   └── .htaccess           # Security: Block script execution & disable directory listing
│   ├── customers/
│   │   └── .htaccess           # Security: Block script execution in customer photos folder
│   └── settings/
│       └── .htaccess           # Security: Block script execution in logo & branding folder
├── .htaccess                   # Root security & rewrite protection
├── index.php                   # Root redirect router (auth -> dashboard or login)
└── README.md                   # Full documentation with setup, credentials, and test flows
```

---

## 5. Database Setup & Import Sequence

The database schema is structured modularly. Import the schema files in this exact sequence:

```bash
# 1. Import Phase 1: Authentication & Users Base Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql

# 2. Import Phase 8: Roles, Permissions, Role Permissions & Settings
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

---

## 6. Default Administrator Credentials

| Field | Value |
| :--- | :--- |
| **Email Address** | `admin@loanmgt.com` |
| **Username** | `admin` |
| **Password** | `Admin@123456` |
| **Account Role** | `Administrator (admin)` |
| **Account Status** | `active` |

---

## 7. Granular Permissions Model (29 System Capabilities)

| Module | Permission Slugs & Capabilities |
| :--- | :--- |
| **Customers** | `customers.view`, `customers.create`, `customers.edit`, `customers.delete` |
| **Loan Products** | `loan_products.view`, `loan_products.create`, `loan_products.edit`, `loan_products.delete` |
| **Loans** | `loans.view`, `loans.create`, `loans.edit`, `loans.delete`, `loans.approve`, `loans.reject` |
| **Disbursement** | `disbursements.view`, `disbursements.create` |
| **Repayments** | `repayments.view`, `repayments.create` |
| **Overdue** | `overdue.view` |
| **Reports** | `reports.view`, `reports.export` |
| **Users** | `users.view`, `users.create`, `users.edit`, `users.delete` |
| **Roles** | `roles.view`, `roles.create`, `roles.edit`, `roles.delete` |
| **Permissions** | `permissions.view` |
| **Settings** | `settings.view`, `settings.edit` |

---

## 8. Role-Based Permissions Matrix

| Capability / Module | Admin | Manager | Loan Officer | Collector | Custom Roles |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Dashboard Access** | Yes | Yes | Yes | Yes | Based on perms |
| **Customer Portfolio** | Full (CRUD) | View/Create/Edit | View/Create/Edit | View Only | Configurable |
| **Loan Products** | Full (CRUD) | View/Create/Edit | View Only | No | Configurable |
| **Loan Applications** | Full (Originate/Approve) | Full (Originate/Approve) | Originate/Edit Drafts | View Only | Configurable |
| **Self-Approval Block** | **Enforced** | **Enforced** | **Enforced** | **Enforced** | **Enforced** |
| **Loan Disbursement** | Full | Full | Blocked | Blocked | Configurable |
| **Payment Collection** | Full | Full | Blocked | Full | Configurable |
| **Delinquency Tracking** | Yes | Yes | Yes | Yes | Configurable |
| **Reports & CSV Export** | Full | Full | View Allowed | View Allowed | Configurable |
| **User Directory (`users/`)** | Full (CRUD) | Blocked | Blocked | Blocked | Configurable |
| **Role Matrix (`roles/`)** | Full (Configure) | Blocked | Blocked | Blocked | Configurable |
| **System Settings (`settings/`)** | Full (Manage) | Blocked | Blocked | Blocked | Configurable |

---

## 9. Phase Roadmap

* **Phase 1 (Completed)**: Foundation, Authentication, Session Guards, CSRF, Common Layouts, Profile & Password Security.
* **Phase 2 (Completed)**: Customer Management Module, Sequential Code Generation, Search/Filter/Pagination, Photo Upload Sandbox, Role Restrictions.
* **Phase 3 (Completed)**: Loan Products Management, Loan Application Origination, Contract Snapshots, Underwriting Workflow & Self-Approval Prevention.
* **Phase 4 (Completed)**: Loan Disbursement, Repayment Schedule Generation, Exact Cent Rounding, Concurrency Safety, and Loan Activation.
* **Phase 5 (Completed)**: Payment Collection, Partial & Full Payments, Overdue Tracking, Automatic Loan Completion & Printable Receipts.
* **Phase 6 (Completed)**: Reports Central Dashboard, 6 Operational Reports, Filter Persistence, Print Layout, and CSV Export.
* **Phase 7 (Completed)**: Dynamic Professional Dashboard, Trailing 6-Month Activity, Overdue Alerts, and Role-Adapted Overview.
* **Phase 8 (Completed)**: User Management, Custom Role CRUD, 29 Granular Permissions, Transactional Permissions Matrix, Dynamic Settings & Branding.
* **Phase 9 (Upcoming)**: Arrears Management & Automated Penalty Calculations.