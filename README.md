# Loan Management System (`loan-mgt`) — Phase 9

A robust, enterprise-grade **Loan Management System** built with **Raw PHP 8+**, **MySQL**, **Bootstrap 5**, and **Bootstrap Icons**.

---

## 1. Project Purpose & Architecture

`loan-mgt` is designed for financial institutions, microfinance organizations, and credit unions to manage loan origination, customer portfolios, repayment schedules, risk assessment, collection operations, and financial compliance.

This codebase is architected using **pure native PHP 8+ and MySQL** without external PHP frameworks (no Laravel, Symfony, or CodeIgniter), strictly adhering to enterprise security baselines, modular database schemas, role-based authorization, zero-configuration web installer, and clean architectural separation.

---

## 2. Technology Stack

* **Backend**: Raw PHP 8.1+
* **Database**: MySQL 5.7+ / MariaDB 10.4+ (InnoDB, UTF-8 `utf8mb4_unicode_ci`)
* **Database Access**: PHP Data Objects (PDO) with Prepared Statements & Transactions
* **Frontend**: HTML5, CSS3, JavaScript (ES6+)
* **UI Framework**: Bootstrap 5.3.3 (Bundled locally)
* **Icons**: Bootstrap Icons 1.11.3 (Bundled locally)
* **Installation**: Self-contained 5-step Web Installation Wizard with `config/installed.lock` enforcement
* **Session & Security**: Native PHP Session with BCRYPT Hashing, CSRF Protection, Upload Sandbox & Role Permission Gates

---

## 3. System Requirements

* **Web Server**: Apache 2.4+ / Nginx (XAMPP, WampServer, cPanel, or LAMP)
* **PHP Version**: 8.0 or higher (PHP 8.1+ recommended)
  * Extensions required: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`, `ctype`, `session`
* **Database**: MySQL 5.7+ / MariaDB 10.4+
* **Writable Directories**: `config/`, `uploads/`, `uploads/avatars/`, `uploads/customers/`, `uploads/settings/`

---

## 4. Commercial Buyer Installation Guide

Deploying the application onto shared hosting, cPanel, or VPS requires **zero manual PHP or `.env` configuration**:

1. **Upload & Extract**:
   Upload the `loan-mgt.zip` package to your web server root or subdirectory (e.g. `public_html/` or `public_html/loan-mgt/`) and extract it.
2. **Create MySQL Database**:
   Log in to your hosting control panel (cPanel / DirectAdmin / phpMyAdmin) and create an empty MySQL database and database user with full privileges.
3. **Open Application URL**:
   Open your web browser and navigate to the application URL (e.g. `https://yourdomain.com/` or `https://yourdomain.com/loan-mgt/`). The system will automatically launch the **Installation Wizard**.
4. **Step 1 — Verify Requirements**:
   Confirm all PHP extensions and directory permissions pass the environment audit.
5. **Step 2 — Database Credentials**:
   Enter your MySQL Host, Port, Database Name, Username, and Password. Click **Test Database Connection** to verify connectivity.
6. **Step 3 — Import Database**:
   Select **Use Bundled Master SQL Package** (`database/install.sql`) and click **Import Database & Continue**.
7. **Step 4 — Administrator Account**:
   Fill in the primary Administrator name, username, email address, password, and organizational preferences (Company Name, System Brand Name, Currency Symbol, Timezone).
8. **Step 5 — Complete Installation & Lock**:
   The installer generates `config/database.php`, secures `config/installed.lock`, and redirects to the completion screen.
9. **Login**:
   Click **Go to Application Login** and sign in with your administrative credentials.

---

## 5. Reinstallation Guide

> [!WARNING]
> Reinstallation will reinitialize application tables and overwrite configuration. Always perform a full database and uploads backup first.

If you ever need to perform a fresh reinstallation:

1. **Backup Database & Uploads**:
   Export your database using `mysqldump` or phpMyAdmin, and copy the `uploads/` directory to a safe location.
2. **Clean MySQL Database**:
   Drop or empty the existing application tables in your database.
3. **Remove the Installation Lock**:
   Connect via FTP/SSH and delete the lock file:
   ```text
   config/installed.lock
   ```
4. **Launch Installer**:
   Visit the application URL in your browser to restart the setup wizard.

---

## 6. Strict Project Structure

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
│   ├── login.php               # Login page with install guard
│   ├── authenticate.php        # Login submission processing & validation
│   ├── logout.php              # Session destruction & redirect
│   └── forgot-password.php     # Forgot password view & instructions
├── config/
│   ├── app.php                 # App name, dynamic BASE_URL, upload paths, version
│   ├── database.php            # Auto-generated centralized PDO connection
│   ├── session.php             # Secure session start, security options, regenerate helper
│   └── installed.lock          # Installation lock (created upon setup completion)
├── database/
│   ├── install.sql             # Master fresh-install SQL package (10 tables, 32 perms, 4 roles)
│   ├── auth.sql                # Development schema: Base users table
│   ├── roles.sql               # Development schema: Roles table & system seeds
│   ├── permissions.sql         # Development schema: 32 granular permissions
│   ├── role_permissions.sql   # Development schema: Role permissions junction
│   ├── settings.sql            # Development schema: Key-value settings table
│   ├── users.sql               # Development schema: Users table extension
│   ├── customers.sql           # Development schema: Customers table
│   ├── loan_products.sql       # Development schema: Loan product templates
│   ├── loans.sql               # Development schema: Loan applications & contract snapshots
│   ├── disbursement.sql        # Development schema: Disbursement & installments
│   ├── payments.sql            # Development schema: Repayment transactions ledger
│   └── README.md               # Database setup and master SQL package guide
├── includes/
│   ├── header.php              # HTML head, CSS imports, meta tags, security headers, dynamic branding
│   ├── footer.php              # Footer markup, JS bundle imports
│   ├── navbar.php              # Topbar with sidebar toggle, user badge, profile dropdown
│   ├── sidebar.php             # Collapsible sidebar navigation
│   ├── auth-check.php          # Protected page access guard, real-time user status & role sync
│   ├── guest-check.php         # Guest guard (redirects authenticated users to dashboard)
│   ├── install.php             # Installation state, lock checks, config writer & SQL parser
│   ├── functions.php           # Security helpers, CSRF, auth checks, permissions, settings & formatting
│   └── flash.php               # Session-based alert banner system
├── installer/
│   ├── index.php               # Wizard welcome & installation checklist
│   ├── requirements.php        # Step 1: System requirements & directory permissions check
│   ├── database.php            # Step 2: Database connection configuration & live test
│   ├── import.php              # Step 3: Master install.sql schema execution & custom upload
│   ├── admin.php               # Step 4: Administrator creation & organizational setup
│   ├── complete.php            # Step 5: Final installation confirmation & health audit
│   ├── lock.php                # Protected locked screen preventing re-installation
│   ├── assets/
│   │   └── css/
│   │       └── installer.css   # Clean, professional wizard styling (NO gradients)
│   └── README.md               # Commercial installer documentation & reinstallation guide
├── modules/
│   ├── dashboard/              # Dynamic operations dashboard & KPI metrics
│   ├── profile/                # User profile, security, and avatar management
│   ├── customers/              # Customer portfolio management, KYC, and photo upload
│   ├── loan-products/          # Lending product catalog and parameters
│   ├── loans/                  # Loan origination, contract snapshots, underwriting, disbursement
│   ├── repayments/             # Repayment collections, partial payments, receipts, overdue
│   ├── reports/                # 6 operational & financial reports, CSV export, print layout
│   ├── users/                  # Staff user directory, account CRUD, password reset
│   ├── roles/                  # Role catalog, custom role creation, permissions matrix
│   ├── permissions/            # Read-only 32 granular permissions matrix
│   └── settings/               # System configuration, localization, branding & logo upload
├── uploads/
│   ├── avatars/
│   │   └── .htaccess           # Security: Block script execution & disable directory listing
│   ├── customers/
│   │   └── .htaccess           # Security: Block script execution in customer photos folder
│   └── settings/
│       └── .htaccess           # Security: Block script execution in logo & branding folder
├── .htaccess                   # Root security, rewrite protection, deny direct access to *.sql/*.lock
├── index.php                   # Root entrypoint with install guard
└── README.md                   # Full product documentation
```

---

## 7. Granular Permissions Model (32 System Capabilities)

| Module | Granular Capabilities |
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

## 8. Phase Roadmap

* **Phase 1 (Completed)**: Foundation, Authentication, Session Guards, CSRF, Common Layouts, Profile & Password Security.
* **Phase 2 (Completed)**: Customer Management Module, Sequential Code Generation, Search/Filter/Pagination, Photo Upload Sandbox, Role Restrictions.
* **Phase 3 (Completed)**: Loan Products Management, Loan Application Origination, Contract Snapshots, Underwriting Workflow & Self-Approval Prevention.
* **Phase 4 (Completed)**: Loan Disbursement, Repayment Schedule Generation, Exact Cent Rounding, Concurrency Safety, and Loan Activation.
* **Phase 5 (Completed)**: Payment Collection, Partial & Full Payments, Overdue Tracking, Automatic Loan Completion & Printable Receipts.
* **Phase 6 (Completed)**: Reports Central Dashboard, 6 Operational Reports, Filter Persistence, Print Layout, and CSV Export.
* **Phase 7 (Completed)**: Dynamic Professional Dashboard, Trailing 6-Month Activity, Overdue Alerts, and Role-Adapted Overview.
* **Phase 8 (Completed)**: User Management, Custom Role CRUD, 32 Granular Permissions, Transactional Permissions Matrix, Dynamic Settings & Branding.
* **Phase 9 (Completed)**: Production Installer, Master Database Packaging (`install.sql`), Installation Lock (`installed.lock`), Dynamic Config Generation, and Reinstallation Safeguards.