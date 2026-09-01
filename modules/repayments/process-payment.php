<?php
/**
 * Process Repayment Payment Handler
 * Loan Management System (loan-mgt) - Phase 5
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/repayments/index.php');
}

// 1. Authorization Guard
if (!can_collect_payments()) {
    set_flash('danger', 'Unauthorized: You do not have permission to collect loan repayments.');
    redirect('modules/repayments/index.php');
}

$loanId        = (int)($_POST['loan_id'] ?? 0);
$installmentId = (int)($_POST['installment_id'] ?? 0);

if ($loanId <= 0 || $installmentId <= 0) {
    set_flash('danger', 'Invalid loan or installment parameter.');
    redirect('modules/repayments/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please resubmit the payment collection form.');
    redirect('modules/repayments/collect.php?loan_id=' . $loanId . '&installment_id=' . $installmentId);
}

// 3. Extract and Validate Input Parameters
$amount        = round((float)($_POST['amount'] ?? 0), 2);
$paymentDate   = trim($_POST['payment_date'] ?? date('Y-m-d'));
$paymentMethod = trim($_POST['payment_method'] ?? 'cash');
$referenceNote = trim($_POST['reference_note'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

if (!empty($referenceNote)) {
    $notes = trim($notes . "\nRef: " . $referenceNote);
}

$errors = [];

if ($amount <= 0.00) {
    $errors[] = 'Payment amount must be greater than $0.00.';
}

if (empty($paymentDate) || !strtotime($paymentDate)) {
    $errors[] = 'A valid payment date is required.';
}

$validMethods = ['cash', 'bank_transfer', 'mobile_banking'];
if (!in_array($paymentMethod, $validMethods, true)) {
    $errors[] = 'Invalid payment channel/method selected.';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/repayments/collect.php?loan_id=' . $loanId . '&installment_id=' . $installmentId);
}

$db = get_db_connection();

try {
    // 4. Atomic Database Transaction with Row Locking
    $db->beginTransaction();

    // Lock loan row
    $loanStmt = $db->prepare('SELECT * FROM loans WHERE id = :id FOR UPDATE');
    $loanStmt->execute([':id' => $loanId]);
    $loan = $loanStmt->fetch();

    if (!$loan) {
        $db->rollBack();
        set_flash('danger', 'Loan account record not found.');
        redirect('modules/repayments/index.php');
    }

    if ($loan['status'] !== 'active') {
        $db->rollBack();
        set_flash('danger', 'Cannot collect payment: Loan status is currently ' . ucfirst($loan['status']) . '.');
        redirect('modules/repayments/view.php?loan_id=' . $loanId);
    }

    // Lock target installment row
    $instStmt = $db->prepare('SELECT * FROM loan_installments WHERE id = :id AND loan_id = :loan_id FOR UPDATE');
    $instStmt->execute([':id' => $installmentId, ':loan_id' => $loanId]);
    $inst = $instStmt->fetch();

    if (!$inst) {
        $db->rollBack();
        set_flash('danger', 'Target installment record not found.');
        redirect('modules/repayments/view.php?loan_id=' . $loanId);
    }

    $currentRemaining = (float)$inst['remaining_amount'];

    if ($currentRemaining <= 0.00) {
        $db->rollBack();
        set_flash('warning', 'Installment #' . $inst['installment_number'] . ' is already fully settled.');
        redirect('modules/repayments/view.php?loan_id=' . $loanId);
    }

    // Strict Overpayment Check
    if ($amount > $currentRemaining) {
        $db->rollBack();
        set_flash('danger', 'Payment rejected: Payment amount of ' . format_currency($amount) . ' exceeds the remaining installment balance of ' . format_currency($currentRemaining) . '.');
        redirect('modules/repayments/collect.php?loan_id=' . $loanId . '&installment_id=' . $installmentId);
    }

    // 5. Generate Sequential Payment Reference
    $paymentRef = generate_payment_reference($db);

    // 6. Insert into loan_payments Ledger
    $insPayment = $db->prepare('
        INSERT INTO loan_payments (
            payment_reference, loan_id, installment_id, customer_id,
            payment_date, amount, payment_method, notes, collected_by, created_at
        ) VALUES (
            :ref, :loan_id, :inst_id, :cust_id,
            :pdate, :amount, :method, :notes, :collector, NOW()
        )
    ');
    $insPayment->execute([
        ':ref'       => $paymentRef,
        ':loan_id'   => $loanId,
        ':inst_id'   => $installmentId,
        ':cust_id'   => (int)$loan['customer_id'],
        ':pdate'     => $paymentDate,
        ':amount'    => $amount,
        ':method'    => $paymentMethod,
        ':notes'     => $notes ?: null,
        ':collector' => auth_id(),
    ]);

    // 7. Update Installment Balances and Status
    $newPaidAmount      = round((float)$inst['paid_amount'] + $amount, 2);
    $newRemainingAmount = round($currentRemaining - $amount, 2);
    $newStatus          = ($newRemainingAmount <= 0.00) ? 'paid' : 'partial';
    $paidDate           = ($newRemainingAmount <= 0.00) ? $paymentDate : $inst['paid_date'];

    $updateInst = $db->prepare('
        UPDATE loan_installments SET 
            paid_amount = :paid,
            remaining_amount = :remaining,
            status = :status,
            paid_date = :paid_date,
            updated_at = NOW()
        WHERE id = :id
    ');
    $updateInst->execute([
        ':paid'      => $newPaidAmount,
        ':remaining' => $newRemainingAmount,
        ':status'    => $newStatus,
        ':paid_date' => $paidDate,
        ':id'        => $installmentId,
    ]);

    // 8. Loan Completion Check: If all installments are paid, transition loan to 'completed'
    $unpaidCountStmt = $db->prepare('SELECT COUNT(*) FROM loan_installments WHERE loan_id = :loan_id AND remaining_amount > 0');
    $unpaidCountStmt->execute([':loan_id' => $loanId]);
    $unpaidCount = (int)$unpaidCountStmt->fetchColumn();

    $isLoanCompleted = false;
    if ($unpaidCount === 0) {
        $completeLoanStmt = $db->prepare('UPDATE loans SET status = "completed", updated_at = NOW() WHERE id = :id');
        $completeLoanStmt->execute([':id' => $loanId]);
        $isLoanCompleted = true;
    }

    $db->commit();

    $successMsg = 'Payment of <strong>' . format_currency($amount) . '</strong> recorded successfully under reference <strong>' . e($paymentRef) . '</strong>.';
    if ($isLoanCompleted) {
        $successMsg .= ' <strong>Congratulations! All installments are settled and this loan is now COMPLETED.</strong>';
    }

    set_flash('success', $successMsg);
    redirect('modules/repayments/receipt.php?ref=' . $paymentRef);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Repayment collection error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred during payment processing: ' . $e->getMessage());
    redirect('modules/repayments/collect.php?loan_id=' . $loanId . '&installment_id=' . $installmentId);
}
