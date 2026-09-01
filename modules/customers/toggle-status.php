<?php
/**
 * Toggle Customer Status Handler (Active / Inactive)
 * Loan Management System (loan-mgt) - Phase 2
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

// 1. Authorization Guard (Admin & Manager Only)
if (!can_toggle_customer_status()) {
    set_flash('danger', 'Unauthorized: You do not have permission to alter customer account status.');
    redirect('modules/customers/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try toggling the customer status again.');
    redirect('modules/customers/index.php');
}

$customerId = (int)($_POST['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer specified.');
    redirect('modules/customers/index.php');
}

$redirectTo = trim($_POST['redirect_to'] ?? '');

try {
    $db = get_db_connection();

    // 3. Fetch Current Customer Status
    $stmt = $db->prepare('SELECT id, full_name, customer_code, status FROM customers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('danger', 'Customer record not found.');
        redirect('modules/customers/index.php');
    }

    $newStatus = ($customer['status'] === 'active') ? 'inactive' : 'active';

    // 4. Update Status in Database
    $updateStmt = $db->prepare('UPDATE customers SET status = :status, updated_at = NOW() WHERE id = :id');
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id'     => $customerId
    ]);

    $statusLabel = ucfirst($newStatus);
    set_flash('success', 'Customer <strong>' . e($customer['full_name']) . '</strong> (' . e($customer['customer_code']) . ') status changed to <strong>' . $statusLabel . '</strong>.');

    if (!empty($redirectTo) && strpos($redirectTo, 'index.php') !== false) {
        redirect($redirectTo);
    } else {
        redirect('modules/customers/view.php?id=' . $customerId);
    }

} catch (Exception $e) {
    error_log('Customer status toggle error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while changing customer status.');
    redirect('modules/customers/index.php');
}
