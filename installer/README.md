# Production Installer & Setup Guide

This document describes the architecture, operation, and reinstallation procedure for the **Loan Management System (`loan-mgt`)** commercial installation wizard.

---

## 1. Overview & Installation Workflow

The web-based installer provides an automated, zero-configuration setup experience designed for shared hosting (cPanel, DirectAdmin), VPS, and dedicated servers running Apache/Nginx with PHP 8.0+ and MySQL.

### 5-Step Setup Sequence:

1. **Step 1: Environment & Requirements Audit (`requirements.php`)**
   - Verifies PHP version ($\ge 8.0.0$)
   - Checks required extensions (`pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`, `ctype`)
   - Checks write permissions for `config/` and `uploads/`
2. **Step 2: Database Configuration & Connection Test (`database.php`)**
   - Inputs MySQL host, port, database name, username, and password
   - Performs a real-time PDO connection test with sanitized error reporting
3. **Step 3: Database Schema Import (`import.php`)**
   - Automatically imports the included `database/install.sql` package containing all 10 relational tables, 32 granular permissions, 4 system roles, and loan product templates
   - Also supports uploading a custom compatible `.sql` file
4. **Step 4: Administrator Account & Branding Setup (`admin.php`)**
   - Creates the primary Super Administrator account with BCRYPT password hashing and assigns the `Administrator` role
   - Configures initial organizational details (Company Name, System Brand Name, Currency Symbol, Timezone)
   - Generates `config/database.php`
   - Creates `config/installed.lock`
5. **Step 5: Completion & Verification (`complete.php`)**
   - Runs final health check verification
   - Provides direct link to the application login screen

---

## 2. Security & Installation Lock

### Permanent Lock Enforcement:
* Once installation completes, the installer creates the file:
  ```text
  config/installed.lock
  ```
* All installer endpoints (`installer/index.php`, `installer/requirements.php`, `installer/database.php`, `installer/import.php`, `installer/admin.php`, `installer/complete.php`) check for the lock and immediately refuse execution, redirecting to `installer/lock.php`.
* The `.htaccess` configuration blocks direct HTTP access to `*.lock` and `*.sql` files.
* Sensitive database credentials are removed from session memory upon installation completion.

---

## 3. How to Reinstall the Application

> [!WARNING]
> Reinstallation will reinitialize application tables and overwrite configuration. Always perform a full backup before reinstalling.

To safely reinstall the product:

### Step 1: Backup Data
1. Export a complete backup of your existing MySQL database using phpMyAdmin or mysqldump:
   ```bash
   mysqldump -u [username] -p [database_name] > backup_before_reinstall.sql
   ```
2. Backup all uploaded media located in `uploads/` (avatars, customer documents, system logos).

### Step 2: Clean the Database
1. Open your database management tool (such as phpMyAdmin or MySQL CLI).
2. Drop or truncate existing application tables, or create a new empty database for the fresh installation.

### Step 3: Remove the Installation Lock
1. Connect to your server via FTP, File Manager, or SSH.
2. Delete the installation lock file:
   ```text
   config/installed.lock
   ```

### Step 4: Run the Installer
1. Open your browser and navigate to the application URL:
   ```text
   https://yourdomain.com/loan-mgt/
   ```
2. The installer wizard will automatically launch. Follow Steps 1–5 to complete the fresh setup.

---

## 4. Troubleshooting

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| **"Requirements Failed: config/ not writable"** | File system permissions restrict Apache from writing configuration files. | Ensure the `config/` and `uploads/` directories have writable permissions (e.g. `chmod 755` or `chmod 775`). |
| **"Unable to connect to the database"** | Incorrect MySQL credentials, host, or the database does not exist. | Create the database in your hosting control panel and verify username and password. |
| **"Installation Locked" screen appears** | `config/installed.lock` is present. | If you wish to re-run setup, follow the Reinstallation procedure above to remove the lock file. |
| **SQL import error during installation** | Database user lacks `CREATE TABLE` / `ALTER TABLE` privileges. | Grant full privileges to the database user on the target database in cPanel/MySQL. |
