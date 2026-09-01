# Loan Management System (`loan-mgt`) — Phase 6

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
│   ├── customers.sql           # Step 2: Customers schema (FK to users.id)
│   ├── loan_products.sql       # Step 3: Loan product templates (FK to users.id)
│   ├── loans.sql               # Step 4: Loan applications & contract snapshot (FK to customers, loan_products, users)
│   ├── disbursement.sql        # Step 5: Loan disbursement columns & loan_installments schedule table
│   ├── payments.sql            # Step 6: Loan completion status & loan_payments transactions ledger
│   └── README.md               # Database setup and 6-step import sequence
├── includes/
│   ├── header.php              # HTML head, CSS imports, meta tags, security headers
│   ├── footer.php              # Footer markup, JS bundle imports
│   ├── navbar.php              # Topbar with sidebar toggle, user badge, profile dropdown
│   ├── sidebar.php             # Responsive collapsible sidebar (Dashboard, Customers, Loan Products, Loans, Repayments, Reports)
│   ├── auth-check.php          # Protected page access guard & no-cache headers
│   ├── guest-check.php         # Guest guard (redirects authenticated users to dashboard)
│   ├── functions.php           # Security helpers, CSRF, auth checks, number generators, payment, schedule math, report access & CSV sanitization
│   └── flash.php               # Session-based alert banner system
├── modules/
│   ├── dashboard/
│   │   └── index.php           # System dashboard with live loan, customer, repayment & delinquency metrics
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
│   └── reports/
│       ├── index.php           # Reports Central Dashboard with 9 KPI cards & product breakdowns
│       ├── loan-report.php     # Loan Applications & Portfolio report with status/product/date filters
│       ├── disbursement-report.php # Capital disbursements released by channel and authorizing officer
│       ├── repayment-report.php    # Collections transactions ledger by channel and collector
│       ├── overdue-report.php      # Delinquent installments tracking with aging bands & days overdue
│       ├── customer-report.php     # Borrower summary & lifetime borrowing vs repayments
│       ├── portfolio-report.php    # Financial reconciliation, expected revenue, and status breakdown
│       ├── print.php           # Universal print template (@media print stylesheet)
│       └── export-csv.php      # Universal CSV export handler with formula injection sanitization
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

# 3. Import Phase 3: Loan Products Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/loan_products.sql

# 4. Import Phase 3: Loan Applications Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/loans.sql

# 5. Import Phase 4: Disbursement & Repayment Schedule Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/disbursement.sql

# 6. Import Phase 5: Repayments & Payment Collection Schema
C:\xampp\mysql\bin\mysql.exe -u root -p < database/payments.sql
```
*(Phase 6 does not require additional SQL schema tables; all reports query existing live transactional data).*

---

## 6. Default Administrator Credentials

| Field | Value |
| :--- | :--- |
| **Email Address** | `admin@loanmgt.com` |
| **Password** | `Admin@123456` |
| **Account Role** | `admin` |
| **Account Status** | `active` |

---

## 7. Role-Based Permissions Matrix

| Module / Action | Admin | Manager | Loan Officer | Collector |
| :--- | :---: | :---: | :---: | :---: |
| **Sign In / Out & Session** | Yes | Yes | Yes | Yes |
| **Dashboard Access** | Yes | Yes | Yes | Yes |
| **Manage Own Profile & Password** | Yes | Yes | Yes | Yes |
| **View Customer Portfolio (`customers/`)** | Yes | Yes | Yes | Yes |
| **Register / Edit Customer** | Yes | Yes | Yes | No |
| **Activate / Deactivate Customer** | Yes | Yes | No | No |
| **Delete Customer** | Yes | No | No | No |
| **View Loan Products (`loan-products/`)** | Yes | Yes | Yes | Yes |
| **Create / Edit / Toggle Loan Products** | Yes | Yes | No | No |
| **Delete Loan Product (Safe check)** | Yes | No | No | No |
| **View Loan Applications (`loans/`)** | Yes | Yes | Yes | Yes |
| **Originate Loan Application (`create.php`)** | Yes | Yes | Yes | No |
| **Edit Draft Loan Application** | Yes | Yes | Yes (Own) | No |
| **Edit Pending Loan Application** | Yes | Yes | No | No |
| **Approve / Reject Loan Application** | Yes | Yes | No | No |
| **Self-Approval of Own Originated Loan** | **Blocked** | **Blocked** | **Blocked** | **Blocked** |
| **Cancel Draft / Pending Loan** | Yes | Yes | Yes (Own) | No |
| **Disburse Approved Loan (`disburse.php`)** | Yes | Yes | No | No |
| **View Repayment Schedule (`schedule.php`)** | Yes | Yes | Yes | Yes |
| **View Repayments Dashboard (`repayments/`)** | Yes | Yes | Yes | Yes |
| **Collect Repayment (`collect.php`)** | Yes | Yes | **Blocked** | **Yes** |
| **View Payment History & Print Receipts** | Yes | Yes | Yes | Yes |
| **View Overdue Delinquency Tracking** | Yes | Yes | Yes | Yes |
| **Reports Central Dashboard (`reports/index.php`)** | Yes | Yes | Yes | Yes |
| **Loan Applications Report (`reports/loan-report.php`)** | Yes | Yes | Yes | **Blocked** |
| **Disbursement Report (`reports/disbursement-report.php`)** | Yes | Yes | **Blocked** | **Blocked** |
| **Repayment Report (`reports/repayment-report.php`)** | Yes | Yes | **Blocked** | Yes |
| **Overdue Delinquency Report (`reports/overdue-report.php`)** | Yes | Yes | **Blocked** | Yes |
| **Customer Summary Report (`reports/customer-report.php`)** | Yes | Yes | Yes | Yes |
| **Portfolio Summary Report (`reports/portfolio-report.php`)** | Yes | Yes | Yes | **Blocked** |
| **CSV Export & Clean Print Layout** | Yes | Yes | Yes (Allowed) | Yes (Allowed) |

---

## 8. Reports & Analytics Features

### Available Reports
1. **Loan Applications Report**: Tracks all originated loans with status, interest methods, terms, and total payable valuations.
2. **Disbursement Report**: Audits capital outflows released to borrowers categorized by channel and authorizing officer.
3. **Repayment & Collections Report**: Transaction ledger of received payments with receipt references and collector stamps.
4. **Overdue Delinquency Report**: Monitors installments where `due_date < CURDATE() AND remaining_amount > 0` with dynamic days overdue.
5. **Customer Summary Report**: Profiles borrower history, active loan counts, total capital borrowed, and remaining debt.
6. **Portfolio Financial Summary**: Reconciles overall portfolio yield, expected interest revenue, active balance, and channel allocations.

### Security & Sanitization
* **Date Validation**: Ensures `from_date <= to_date` across all filter forms.
* **CSV Injection Protection**: Sanitizes any cell starting with `=`, `+`, `-`, `@` with a leading single quote `'` to block spreadsheet formula execution.
* **Direct URL Authorization**: Restricts access server-side; unauthorized roles are blocked from direct URL access.
* **Print Stylesheet**: Clean print-optimized layouts using `@media print` without navigation or action buttons.

---

## 9. Phase Roadmap

* **Phase 1 (Completed)**: Foundation, Authentication, Session Guards, CSRF, Common Layouts, Profile & Password Security.
* **Phase 2 (Completed)**: Customer Management Module, Sequential Code Generation, Search/Filter/Pagination, Photo Upload Sandbox, Role Restrictions.
* **Phase 3 (Completed)**: Loan Products Management, Loan Application Origination, Contract Snapshots, Underwriting Workflow & Self-Approval Prevention.
* **Phase 4 (Completed)**: Loan Disbursement, Repayment Schedule Generation, Exact Cent Rounding, Concurrency Safety, and Loan Activation.
* **Phase 5 (Completed)**: Payment Collection, Partial & Full Payments, Overdue Tracking, Automatic Loan Completion & Printable Receipts.
* **Phase 6 (Completed)**: Reports Central Dashboard, 6 Operational Reports, Filter Persistence, Print Layout, and CSV Export.
* **Phase 7 (Upcoming)**: Arrears Management & Automated Penalty Calculations.