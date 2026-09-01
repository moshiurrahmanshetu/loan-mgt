<?php
/**
 * Delete Customer Handler
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

// 1. Authorization Guard (Admin Only)
if (!can_delete_customers()) {
    set_flash('danger', 'Unauthorized: Only System Administrators can delete customer records.');
    redirect('modules/customers/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try deleting the customer again.');
    redirect('modules/customers/index.php');
}

$customerId = (int)($_POST['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer specified.');
    redirect('modules/customers/index.php');
}

try {
    $db = get_db_connection();

    // 3. Fetch Customer Details for Cleanup & Notice
    $stmt = $db->prepare('SELECT id, full_name, customer_code, photo FROM customers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('danger', 'Customer record not found.');
        redirect('modules/customers/index.php');
    }

    // 4. Delete Record from Database
    $deleteStmt = $db->prepare('DELETE FROM customers WHERE id = :id');
    $deleteStmt->execute([':id' => $customerId]);

    // 5. Clean up associated photo file
    if (!empty($customer['photo'])) {
        $photoPath = CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $customer['photo'];
        if (file_exists($photoPath) && is_file($photoPath)) {
            @unlink($photoPath);
        }
    }

    set_flash('success', 'Customer <strong>' . e($customer['full_name']) . '</strong> (' . e($customer['customer_code']) . ') has been permanently deleted.');
    redirect('modules/customers/index.php');

} catch (Exception $e) {
    error_log('Customer delete error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while deleting the customer record. The record may have relational constraints.');
    redirect('modules/customers/index.php');
}
