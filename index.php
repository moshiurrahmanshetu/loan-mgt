<?php
/**
 * Root Application Router / Entrypoint
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
} else {
    redirect('auth/login.php');
}
