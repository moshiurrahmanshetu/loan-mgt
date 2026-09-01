<?php
/**
 * Root Application Router / Entrypoint
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/includes/install.php';

// First-Run Check: Route uninstalled package visits to Installer Wizard
require_installed();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
} else {
    redirect('auth/login.php');
}
