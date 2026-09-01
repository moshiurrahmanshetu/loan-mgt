<?php
/**
 * System Settings Update POST Handler
 * Loan Management System (loan-mgt) - Phase 8
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Method & Permission Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/settings/index.php');
}

require_permission('settings.edit');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch).');
    redirect('modules/settings/index.php');
}

$db = get_db_connection();

// 3. Collect & Sanitize Inputs
$companyName    = trim($_POST['company_name'] ?? '');
$systemName     = trim($_POST['system_name'] ?? '');
$companyEmail   = trim($_POST['company_email'] ?? '');
$companyPhone   = trim($_POST['company_phone'] ?? '');
$companyAddress = trim($_POST['company_address'] ?? '');
$currencySymbol = trim($_POST['currency_symbol'] ?? '$');
$currencyCode   = trim($_POST['currency_code'] ?? 'USD');
$timezone       = trim($_POST['timezone'] ?? 'America/New_York');
$dateFormat     = trim($_POST['date_format'] ?? 'M d, Y');
$removeLogo     = !empty($_POST['remove_logo']);

// 4. Validate
$errors = [];

if (empty($companyName)) {
    $errors[] = 'Company name is required.';
}
if (empty($systemName)) {
    $errors[] = 'System brand name is required.';
}
if (empty($companyEmail) || !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid company contact email.';
}
if (empty($currencySymbol)) {
    $errors[] = 'Currency symbol is required.';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/settings/index.php');
}

// 5. Update Text Settings
update_setting('company_name', $companyName, 'text');
update_setting('system_name', $systemName, 'text');
update_setting('company_email', $companyEmail, 'text');
update_setting('company_phone', $companyPhone, 'text');
update_setting('company_address', $companyAddress, 'text');
update_setting('currency_symbol', $currencySymbol, 'text');
update_setting('currency_code', $currencyCode, 'text');
update_setting('timezone', $timezone, 'text');
update_setting('date_format', $dateFormat, 'text');

// 6. Handle Logo Removal
$currentLogo = get_setting('system_logo');
if ($removeLogo && !empty($currentLogo)) {
    $oldPath = SETTINGS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $currentLogo;
    if (file_exists($oldPath)) {
        @unlink($oldPath);
    }
    update_setting('system_logo', null, 'image');
}

// 7. Handle New Logo Upload
if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['system_logo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > MAX_LOGO_SIZE) {
            set_flash('warning', 'Logo file size exceeds the 2MB limit.');
            redirect('modules/settings/index.php');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mime, ALLOWED_LOGO_MIMES, true) || !in_array($ext, ALLOWED_LOGO_EXTENSIONS, true)) {
            set_flash('danger', 'Invalid logo image format. Allowed: JPG, PNG, WEBP.');
            redirect('modules/settings/index.php');
        }

        if (!is_dir(SETTINGS_UPLOAD_DIR)) {
            mkdir(SETTINGS_UPLOAD_DIR, 0755, true);
        }

        $logoFilename = 'logo_' . uniqid('', true) . '.' . $ext;
        $destination  = SETTINGS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $logoFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Remove previous logo file
            if (!empty($currentLogo)) {
                $oldPath = SETTINGS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $currentLogo;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            update_setting('system_logo', $logoFilename, 'image');
        } else {
            set_flash('danger', 'Failed to save logo file to server disk.');
            redirect('modules/settings/index.php');
        }
    }
}

set_flash('success', 'System configuration and preferences saved successfully.');
redirect('modules/settings/index.php');
