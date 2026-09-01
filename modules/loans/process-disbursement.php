<?php
/**
 * Process Loan Disbursement Handler
 * Loan Management System (loan-mgt) - Phase 4
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

// 1. Authorization Guard
if (!can_disburse_loans()) {
    set_flash('danger', 'Unauthorized: Only Administrators and Loan Managers have disbursement authorization.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try submitting the disbursement form again.');
    redirect('modules/loans/disburse.php?id=' . $loanId);
}

// 3. Extract and Validate Input Parameters
$disbursementDate   = trim($_POST['disbursement_date'] ?? date('Y-m-d'));
$disbursementMethod = trim($_POST['disbursement_method'] ?? 'bank_transfer');
$referenceNumber    = trim($_POST['reference_number'] ?? '');
$disbursementNotes  = trim($_POST['disbursement_notes'] ?? '');

$errors = [];

if (empty($disbursementDate) || !strtotime($disbursementDate)) {
    $errors[] = 'A valid disbursement date is required.';
}

$validMethods = ['cash', 'bank_transfer', 'mobile_banking'];
if (!in_array($disbursementMethod, $validMethods, true)) {
    $errors[] = 'Invalid disbursement channel/method selected.';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/loans/disburse.php?id=' . $loanId);
}

$db = get_db_connection();

try {
    // 4. Atomic Database Transaction with Row Locking
    $db->beginTransaction();

    // Lock the loan row for update to prevent race conditions and duplicate disbursement
    $stmt = $db->prepare('SELECT * FROM loans WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => $loanId]);
    $loan = $stmt->fetch();

    if (!$loan) {
        $db->rollBack();
        set_flash('danger', 'Loan application record not found.');
        redirect('modules/loans/index.php');
    }

    // Strict Status Check: Only 'approved' loans are eligible
    if ($loan['status'] !== 'approved') {
        $db->rollBack();
        if ($loan['status'] === 'active') {
            set_flash('warning', 'This loan has already been disbursed and activated.');
        } else {
            set_flash('danger', 'Cannot disburse loan: Application is in ' . ucfirst($loan['status']) . ' status.');
        }
        redirect('modules/loans/view.php?id=' . $loanId);
    }

    // Check for prior disbursement audit stamp
    if (!empty($loan['disbursement_date'])) {
        $db->rollBack();
        set_flash('warning', 'Disbursement rejected: Loan already has a recorded disbursement date.');
        redirect('modules/loans/view.php?id=' . $loanId);
    }

    // Check for existing installment records
    $checkInst = $db->prepare('SELECT COUNT(*) FROM loan_installments WHERE loan_id = :id');
    $checkInst->execute([':id' => $loanId]);
    if ((int)$checkInst->fetchColumn() > 0) {
        $db->rollBack();
        set_flash('danger', 'Disbursement rejected: Repayment schedule already exists for this loan.');
        redirect('modules/loans/view.php?id=' . $loanId);
    }

    // 5. Generate Exact-Cent Repayment Schedule from Stored Snapshot
    $schedule = generate_repayment_schedule($loan, $disbursementDate);

    // 6. Bulk Insert Generated Installments
    $insertInstStmt = $db->prepare('
        INSERT INTO loan_installments (
            loan_id, installment_number, due_date,
            principal_amount, interest_amount, installment_amount,
            paid_amount, remaining_amount, status, created_at
        ) VALUES (
            :loan_id, :num, :due_date,
            :principal, :interest, :amount,
            :paid, :remaining, :status, NOW()
        )
    ');

    foreach ($schedule['installments'] as $item) {
        $insertInstStmt->execute([
            ':loan_id'   => $loanId,
            ':num'       => $item['installment_number'],
            ':due_date'  => $item['due_date'],
            ':principal' => $item['principal_amount'],
            ':interest'  => $item['interest_amount'],
            ':amount'    => $item['installment_amount'],
            ':paid'      => 0.00,
            ':remaining' => $item['installment_amount'],
            ':status'    => 'pending',
        ]);
    }

    // 7. Update Loan to 'active' and Store Disbursement Audit Fields
    $updateLoanStmt = $db->prepare('
        UPDATE loans SET 
            status = "active",
            disbursement_date = :disb_date,
            disbursed_amount = :disb_amt,
            disbursement_method = :disb_method,
            disbursed_by = :disb_by,
            disbursed_at = NOW(),
            updated_at = NOW()
        WHERE id = :id AND status = "approved"
    ');

    $updateLoanStmt->execute([
        ':disb_date'   => $disbursementDate,
        ':disb_amt'    => (float)$loan['requested_amount'],
        ':disb_method' => $disbursementMethod,
        ':disb_by'     => auth_id(),
        ':id'          => $loanId
    ]);

    $db->commit();

    set_flash('success', 'Loan <strong>' . e($loan['loan_number']) . '</strong> has been disbursed successfully for <strong>' . format_currency($loan['requested_amount']) . '</strong> (' . e(get_disbursement_method_label($disbursementMethod)) . '). Repayment schedule with ' . $schedule['count'] . ' installments has been generated and activated.');
    redirect('modules/loans/view.php?id=' . $loanId);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Disbursement processing error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred during loan disbursement: ' . $e->getMessage());
    redirect('modules/loans/disburse.php?id=' . $loanId);
}
