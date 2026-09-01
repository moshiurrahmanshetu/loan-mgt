# Loan Management System (`loan-mgt`) — Phase 1

A robust, enterprise-grade **Loan Management System** built with **Raw PHP 8+**, **MySQL**, **Bootstrap 5**, and **Bootstrap Icons**.

---

## 1. Project Purpose & Architecture

`loan-mgt` is designed for financial institutions, microfinance organizations, and credit unions to manage loan origination, customer portfolios, repayment schedules, risk assessment, and financial compliance.

This codebase is architected using **pure native PHP 8+ and MySQL** without external PHP frameworks (no Laravel, Symfony, or CodeIgniter), strictly adhering to enterprise security baselines, modular database schemas, and clean architectural separation.

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
│   ├── app.php                 # App name, dynamic BASE_URL detection, upload paths
│   ├── database.php            # Centralized PDO connection with exception handling
│   └── session.php             # Secure session start, security options, regenerate helper
├── database/
│   ├── auth.sql                # Standalone importable users table & default admin seed
│   └── README.md               # Database setup and import instructions
├── includes/
│   ├── header.php              # HTML head, CSS imports, meta tags, security headers
│   ├── footer.php              # Footer markup, JS bundle imports
│   ├── navbar.php              # Topbar with sidebar toggle, user badge, profile dropdown
│   ├── sidebar.php             # Responsive collapsible sidebar with active/coming-soon items
│   ├── auth-check.php          # Protected page access guard & no-cache headers
│   ├── guest-check.php         # Guest guard (redirects authenticated users to dashboard)
│   ├── functions.php           # Security helpers (e(), csrf_*, auth_user(), redirect())
│   └── flash.php               # Session-based alert banner system
├── modules/
│   ├── dashboard/
│   │   └── index.php           # Protected system dashboard with user metrics & phase notice
│   └── profile/
│       ├── index.php           # Profile view (details, security, avatar)
│       ├── update.php          # POST handler for updating name/email/phone
│       ├── change-password.php # POST handler for validating and updating password
│       └── upload-avatar.php   # POST handler for secure avatar upload & image validation
├── uploads/
│   └── avatars/
│       └── .htaccess           # Security: block PHP execution and disable directory listing
├── .htaccess                   # Root security & rewrite protection
├── index.php                   # Root redirect router (auth -> dashboard or login)
└── README.md                   # Full documentation with setup, credentials, and test flows
```

---

## 5. Database Setup & SQL Import

The database schema is structured modularly. Phase 1 requires only `database/auth.sql`.

### Option A: Using XAMPP / Command Line
Run the following command in your terminal:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -p < database/auth.sql
```
*(Press Enter when prompted for password if using default XAMPP credentials)*

### Option B: Using phpMyAdmin
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`
2. Click on the **Import** tab.
3. Click **Choose File** and select `c:/xampp/htdocs/loan-mgt/database/auth.sql`.
4. Click **Import** at the bottom of the page.

The SQL file automatically creates the `loan_mgt` database and the `users` table with the default administrator account.

---

## 6. Database & Application Configuration

### Database Credentials (`config/database.php`)
Credentials are centralized in `config/database.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'loan_mgt');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```
Modify these constants if your MySQL server uses different credentials or ports.

### Base URL Configuration (`config/app.php`)
`config/app.php` automatically determines the application's base URL dynamically from `$_SERVER['HTTP_HOST']` and document root paths. It works seamlessly whether deployed at `http://localhost/loan-mgt/` or a custom virtual host like `http://loan-mgt.test/`.

---

## 7. Default Administrator Credentials

| Field | Value |
| :--- | :--- |
| **Email Address** | `admin@loanmgt.com` |
| **Password** | `Admin@123456` |
| **Account Role** | `admin` |
| **Account Status** | `active` |

---

## 8. User Operations Guide

### A. How to Sign In
1. Navigate to `http://localhost/loan-mgt/` in your web browser.
2. You will be routed to `http://localhost/loan-mgt/auth/login.php`.
3. Enter `admin@loanmgt.com` and `Admin@123456`.
4. Click **Sign In to Account**.
5. Upon successful authentication, your session ID is regenerated and you are redirected to the Dashboard (`modules/dashboard/index.php`).

### B. How to Update Profile Details
1. Click your name/avatar in the top navigation bar and select **My Profile** (or go to `modules/profile/index.php`).
2. Update your **Full Name**, **Email Address**, or **Phone Number**.
3. Click **Save Changes**.
4. The system validates uniqueness and formatting, updates the database, refreshes the active session, and displays a confirmation flash message.

### C. How to Upload a Profile Avatar
1. On the **My Profile** page, locate the **Update Photo** card.
2. Choose an image from your computer (Accepted formats: **JPG**, **PNG**, **WebP**; Maximum size: **2MB**).
3. Click **Upload Avatar**.
4. The backend securely checks the file MIME type via `finfo`, moves the image into `uploads/avatars/` with a cryptographically random filename, updates the user record, and instantly updates the avatar across the navbar and profile cards.

### D. How to Change Password
1. On the **My Profile** page, scroll to the **Security & Password** section.
2. Enter your **Current Password**.
3. Enter a **New Password** (minimum 8 characters) and repeat it in the **Confirm New Password** field.
4. Click **Update Password**.
5. The backend validates your current password hash using `password_verify()` and saves the new BCRYPT hash securely.

### E. How to Sign Out
1. Click the top-right profile dropdown and choose **Logout** (or visit `auth/logout.php`).
2. Your session is completely destroyed, authentication cookies cleared, and you are redirected to the login view.

---

## 9. Phase 1 Scope vs. Future Phases

### Current Phase 1 Features:
* **Project Foundation & Security Sandbox**
* **Standalone Database Schema & Admin Seed (`auth.sql`)**
* **Raw PHP 8+ PDO Connection Layer with Prepared Statements**
* **Safe Session Management & Anti-Fixation Regeneration**
* **Authentication Engine (Login, Logout, Inactive Account Lockout)**
* **CSRF Token Protection on All State-Changing Forms**
* **Flash Alert Message Queue**
* **Corporate UI Theme (Dark Charcoal Sidebar, Solid Colors, Zero Gradients)**
* **Collapsible Desktop Sidebar with `localStorage` State Persistence**
* **Mobile-Responsive Offcanvas Sidebar with Backdrop Overlay**
* **Profile Management (Name, Email, Phone, Uniqueness Validation)**
* **Secure Avatar Upload Sandbox with Extension & MIME Type Verification**
* **Password Change Workflow with BCRYPT Verification**

### Upcoming Phases (Future Roadmap):
* **Phase 2**: Customer Portfolio & KYC Management (`customers.sql`)
* **Phase 3**: Loan Products & Loan Application Engine (`loans.sql`)
* **Phase 4**: Loan Approval, Underwriting & Disbursement Workflows
* **Phase 5**: Installments, Repayments & Collection Tracking (`repayments.sql`)
* **Phase 6**: Arrears & Delinquency Management
* **Phase 7**: Financial Reporting, Portfolio Analytics & Audit Logging