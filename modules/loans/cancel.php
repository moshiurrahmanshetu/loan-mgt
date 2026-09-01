<?php
/**
 * Cancel Loan Application Handler
 * Loan Management System (loan-mgt) - Phase 3
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/loans/index.php');
}

$loanId = (int)($_POST['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan application specified.');
    redirect('modules/loans/index.php');
}

// 1. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try again.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

$db = get_db_connection();

$stmt = $db->prepare('SELECT id, loan_number, status, created_by FROM loans WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

// 2. Status Check: Only draft or pending can be cancelled
if (!in_array($loan['status'], ['draft', 'pending'], true)) {
    set_flash('danger', 'Cannot cancel application in current status (' . ucfirst($loan['status']) . ').');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// 3. Authorization Check: Admin, Manager, or Creator
$currentUserId = auth_id();
$isCreator     = ($currentUserId !== null && (int)$loan['created_by'] === (int)$currentUserId);
if (!can_approve_loans() && !$isCreator) {
    set_flash('danger', 'Unauthorized: You do not have permission to cancel this loan application.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

try {
    $cancelStmt = $db->prepare('UPDATE loans SET status = "cancelled", updated_at = NOW() WHERE id = :id');
    $cancelStmt->execute([':id' => $loanId]);

    set_flash('warning', 'Loan application <strong>' . e($loan['loan_number']) . '</strong> has been cancelled.');
    redirect('modules/loans/view.php?id=' . $loanId);

} catch (Exception $e) {
    error_log('Loan cancel error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while cancelling the loan application.');
    redirect('modules/loans/view.php?id=' . $loanId);
}
