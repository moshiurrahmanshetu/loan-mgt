<?php
/**
 * Payment Collection Form
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Collect Repayment';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard: Only Admin, Manager, and Collector can collect repayments
if (!can_collect_payments()) {
    set_flash('danger', 'Unauthorized: Only Administrators, Managers, and Debt Collectors have payment collection authorization.');
    redirect('modules/repayments/index.php');
}

$loanId        = (int)($_GET['loan_id'] ?? $_GET['id'] ?? 0);
$installmentId = (int)($_GET['installment_id'] ?? 0);

if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan account specified.');
    redirect('modules/repayments/index.php');
}

$db = get_db_connection();

// 2. Fetch Loan Details
$loanStmt = $db->prepare('
    SELECT l.*, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, lp.name AS product_name
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    WHERE l.id = :id
    LIMIT 1
');
$loanStmt->execute([':id' => $loanId]);
$loan = $loanStmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan account record not found.');
    redirect('modules/repayments/index.php');
}

// 3. Status Eligibility Check: Only 'active' loans can receive payments
if ($loan['status'] !== 'active') {
    if ($loan['status'] === 'completed') {
        set_flash('info', 'This loan is fully settled and Completed. No further payments can be collected.');
    } else {
        set_flash('danger', 'Payment rejected: Only active loans can receive repayments (Current status: ' . ucfirst($loan['status']) . ').');
    }
    redirect('modules/repayments/view.php?loan_id=' . $loanId);
}

// 4. Fetch Target Installment
if ($installmentId > 0) {
    $instStmt = $db->prepare('SELECT * FROM loan_installments WHERE id = :inst_id AND loan_id = :loan_id LIMIT 1');
    $instStmt->execute([':inst_id' => $installmentId, ':loan_id' => $loanId]);
    $targetInstallment = $instStmt->fetch();
} else {
    // Select earliest unpaid installment
    $instStmt = $db->prepare('SELECT * FROM loan_installments WHERE loan_id = :loan_id AND remaining_amount > 0 ORDER BY installment_number ASC LIMIT 1');
    $instStmt->execute([':loan_id' => $loanId]);
    $targetInstallment = $instStmt->fetch();
}

if (!$targetInstallment) {
    set_flash('info', 'All installments for this loan have been fully settled.');
    redirect('modules/repayments/view.php?loan_id=' . $loanId);
}

if ((float)$targetInstallment['remaining_amount'] <= 0) {
    set_flash('warning', 'Installment #' . $targetInstallment['installment_number'] . ' is already fully paid.');
    redirect('modules/repayments/view.php?loan_id=' . $loanId);
}

// Fetch all unpaid installments for dropdown switcher
$allUnpaidStmt = $db->prepare('SELECT id, installment_number, due_date, remaining_amount, installment_amount FROM loan_installments WHERE loan_id = :loan_id AND remaining_amount > 0 ORDER BY installment_number ASC');
$allUnpaidStmt->execute([':loan_id' => $loanId]);
$unpaidInstallments = $allUnpaidStmt->fetchAll();

$today = date('Y-m-d');
$remainingBalance = (float)$targetInstallment['remaining_amount'];

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-muted">Repayments</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/view.php?loan_id=' . $loan['id']); ?>" class="text-decoration-none text-muted"><?php echo e($loan['loan_number']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Collect Payment</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Collect Repayment: <?php echo e($loan['loan_number']); ?></h2>
            <span class="badge bg-success">Installment #<?php echo $targetInstallment['installment_number']; ?></span>
        </div>
    </div>

    <div>
        <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Ledger
        </a>
    </div>
</div>

<form action="<?php echo url('modules/repayments/process-payment.php'); ?>" method="POST" id="repaymentForm" autocomplete="off" onsubmit="return confirm('Are you sure you want to record this payment of $' + parseFloat(document.getElementById('amount').value || 0).toFixed(2) + '?');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="loan_id" value="<?php echo (int)$loan['id']; ?>">
    <input type="hidden" name="installment_id" value="<?php echo (int)$targetInstallment['id']; ?>">
    <input type="hidden" name="customer_id" value="<?php echo (int)$loan['customer_id']; ?>">

    <div class="row g-4">
        <!-- Left Column: Borrower Summary & Installment Selector -->
        <div class="col-12 col-lg-5">
            <!-- Borrower & Loan Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i> Borrower & Account Details</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 small">
                        <div class="col-12">
                            <span class="text-muted d-block">Borrower Name</span>
                            <span class="fw-bold text-dark fs-6"><?php echo e($loan['customer_name']); ?></span>
                            <div class="text-muted font-monospace"><?php echo e($loan['customer_code']); ?> &bull; <?php echo e($loan['customer_phone']); ?></div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Loan Product</span>
                            <span class="fw-semibold text-dark"><?php echo e($loan['product_name'] ?? 'Product'); ?></span>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Interest Rate</span>
                            <span class="fw-semibold text-dark"><?php echo number_format($loan['interest_rate'], 2); ?>% (<?php echo e(get_interest_method_label($loan['interest_method'])); ?>)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Target Installment Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i> Target Installment Breakdown</h3>
                    <?php if (count($unpaidInstallments) > 1): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Switch Installment
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($unpaidInstallments as $ui): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $ui['id'] == $targetInstallment['id'] ? 'active' : ''; ?>" href="<?php echo url('modules/repayments/collect.php?loan_id=' . $loan['id'] . '&installment_id=' . $ui['id']); ?>">
                                            Inst #<?php echo $ui['installment_number']; ?> (Due: <?php echo date('M d', strtotime($ui['due_date'])); ?> &bull; Rem: <?php echo format_currency($ui['remaining_amount']); ?>)
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 small">
                        <div class="col-6">
                            <span class="text-muted d-block">Installment Number</span>
                            <span class="fw-bold text-dark fs-6">#<?php echo $targetInstallment['installment_number']; ?></span>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">Scheduled Due Date</span>
                            <span class="fw-semibold text-dark"><?php echo date('F j, Y', strtotime($targetInstallment['due_date'])); ?></span>
                            <?php if ($targetInstallment['due_date'] < $today): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php elseif ($targetInstallment['due_date'] === $today): ?>
                                <span class="badge bg-warning text-dark">Due Today</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">Installment Total</span>
                            <span class="fw-bold text-dark"><?php echo format_currency($targetInstallment['installment_amount']); ?></span>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">Previously Paid</span>
                            <span class="fw-semibold text-success"><?php echo format_currency($targetInstallment['paid_amount']); ?></span>
                        </div>

                        <div class="col-12 pt-2 border-top">
                            <span class="text-muted d-block">Remaining Due (Before Payment)</span>
                            <span class="h4 fw-bold text-danger mb-0"><?php echo format_currency($remainingBalance); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Payment Input & Execution Form -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i> Payment Receipt Parameters</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="amount" class="form-label fw-semibold">Payment Amount ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0.01" max="<?php echo number_format($remainingBalance, 2, '.', ''); ?>" class="form-control fw-bold fs-5 text-dark" id="amount" name="amount" value="<?php echo number_format($remainingBalance, 2, '.', ''); ?>" required>
                            </div>
                            <div class="form-text small">
                                Max allowed: <span class="fw-bold text-danger"><?php echo format_currency($remainingBalance); ?></span>. Overpayment is blocked.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="payment_date" class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo $today; ?>" required max="<?php echo date('Y-m-d'); ?>">
                            <div class="form-text small">Official date payment was received.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="cash" selected>Cash Voucher / Counter</option>
                                <option value="bank_transfer">Bank Wire / Deposit</option>
                                <option value="mobile_banking">Mobile Banking / Digital Wallet</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="reference_note" class="form-label fw-semibold">External Reference #</label>
                            <input type="text" class="form-control" id="reference_note" name="reference_note" placeholder="e.g. Deposit Slip / Bank Txn #">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label fw-semibold">Collection Remarks / Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional notes for this repayment entry..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Quick Action & Confirmation Card -->
            <div class="card shadow-sm mb-0">
                <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-success px-4 py-2.5 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Record Payment & Issue Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
