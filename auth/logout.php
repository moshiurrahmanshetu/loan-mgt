<?php
/**
 * Logout Handler
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Clear session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start a fresh session to deliver logout notice
session_name('LOAN_MGT_SESS');
session_start();

require_once __DIR__ . '/../includes/flash.php';
set_flash('info', 'You have been successfully logged out.');

redirect('auth/login.php');
