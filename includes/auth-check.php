<?php
/**
 * Authentication Guard
 * Loan Management System (loan-mgt) - Phase 1
 *
 * Protects pages from unauthenticated access.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';

// Prevent browser from caching protected views
send_security_headers(true);

if (!is_logged_in()) {
    set_flash('danger', 'Your session has expired or you are not authorized. Please log in to continue.');
    redirect('auth/login.php');
}
