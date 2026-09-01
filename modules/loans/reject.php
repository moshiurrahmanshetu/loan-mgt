<?php
/**
 * Reject Loan Application Handler
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

// 1. Authorization Check: Only Admin and Manager can reject loans
if (!can_approve_loans()) {
    set_flash('danger', 'Unauthorized: Only Administrators and Loan Managers can review credit applications.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try submitting again.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

$reason = trim($_POST['rejection_reason'] ?? '');
if (empty($reason)) {
    set_flash('danger', 'A rejection reason is required to record the underwriting decision.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

$db = get_db_connection();

$stmt = $db->prepare('SELECT id, loan_number, customer_id, status, created_by FROM loans WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

// 3. Status Check: Must be in 'pending' status
if ($loan['status'] !== 'pending') {
    set_flash('danger', 'Cannot reject loan: Only pending applications can be reviewed (Current status: ' . ucfirst($loan['status']) . ').');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// 4. Critical Segregation Policy: Creator cannot review own loan
$currentUserId = auth_id();
if ($currentUserId !== null && (int)$loan['created_by'] === (int)$currentUserId) {
    set_flash('danger', 'Policy Violation: You cannot review or reject a loan application that you originated.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

try {
    // 5. Atomic Transaction
    $db->beginTransaction();

    $rejectStmt = $db->prepare('
        UPDATE loans SET 
            status = "rejected",
            rejection_reason = :reason,
            approved_by = :reviewer,
            approved_at = NOW(),
            updated_at = NOW()
        WHERE id = :id AND status = "pending"
    ');

    $rejectStmt->execute([
        ':reason'   => $reason,
        ':reviewer' => $currentUserId,
        ':id'       => $loanId
    ]);

    $db->commit();

    set_flash('warning', 'Loan application <strong>' . e($loan['loan_number']) . '</strong> has been rejected.');
    redirect('modules/loans/view.php?id=' . $loanId);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Loan reject error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while rejecting the loan application.');
    redirect('modules/loans/view.php?id=' . $loanId);
}
