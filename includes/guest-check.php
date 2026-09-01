<?php
/**
 * Guest Guard
 * Loan Management System (loan-mgt) - Phase 1
 *
 * Redirects already logged-in users away from guest pages (e.g. login).
 */

require_once __DIR__ . '/functions.php';

// Send standard security headers
send_security_headers(false);

if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
}
