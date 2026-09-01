<?php
/**
 * Application Configuration
 * Loan Management System (loan-mgt) - Phase 1
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Application Metadata
define('APP_NAME', 'Loan Management System');
define('APP_SHORT_NAME', 'LoanMgt');
define('APP_VERSION', '5.0.0 (Phase 5)');
define('APP_ENV', 'development'); // 'development' or 'production'

// Calculate Base URL dynamically for portability across XAMPP, virtual hosts, and production
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', ROOT_PATH);
        
        $subDir = str_replace($docRoot, '', $appRoot);
        $subDir = trim($subDir, '/');
        $basePath = !empty($subDir) ? '/' . $subDir : '';
        
        define('BASE_URL', rtrim($protocol . $host . $basePath, '/'));
    } else {
        define('BASE_URL', 'http://localhost/loan-mgt');
    }
}

// Uploads Paths and URLs
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');

// User Avatars (Phase 1)
define('AVATAR_UPLOAD_DIR', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'avatars');
define('AVATAR_UPLOAD_URL', BASE_URL . '/uploads/avatars');
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_AVATAR_MIMES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_AVATAR_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Customer Photos (Phase 2)
define('CUSTOMER_UPLOAD_DIR', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'customers');
define('CUSTOMER_UPLOAD_URL', BASE_URL . '/uploads/customers');
define('MAX_CUSTOMER_PHOTO_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_CUSTOMER_PHOTO_MIMES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_CUSTOMER_PHOTO_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
