<?php
/**
 * Loan Repayment Ledger & Schedule View
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Loan Repayment File';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$loanId = (int)($_GET['loan_id'] ?? $_GET['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan account specified.');
    redirect('modules/repayments/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT l.*, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, c.address AS customer_address, c.city AS customer_city,
           lp.name AS product_name, lp.product_code,
           ud.name AS disburser_name
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    LEFT JOIN users ud ON l.disbursed_by = ud.id 
    WHERE l.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan record not found.');
    redirect('modules/repayments/index.php');
}

// Fetch Installments
$instStmt = $db->prepare('SELECT * FROM loan_installments WHERE loan_id = :id ORDER BY installment_number ASC');
$instStmt->execute([':id' => $loanId]);
$installments = $instStmt->fetchAll();

// Fetch Payment History
$paymentsStmt = $db->prepare('
    SELECT p.*, u.name AS collector_name, li.installment_number
    FROM loan_payments p
    LEFT JOIN users u ON p.collected_by = u.id
    LEFT JOIN loan_installments li ON p.installment_id = li.id
    WHERE p.loan_id = :id
    ORDER BY p.id DESC
');
$paymentsStmt->execute([':id' => $loanId]);
$payments = $paymentsStmt->fetchAll();

// Compute Financial Aggregates
$totalPrincipal   = 0.0;
$totalInterest    = 0.0;
$totalInstallment = 0.0;
$totalPaid        = 0.0;
$totalRemaining   = 0.0;

$today = date('Y-m-d');
$nextPayableInstallmentId = null;

foreach ($installments as $inst) {
    $totalPrincipal   += (float)$inst['principal_amount'];
    $totalInterest    += (float)$inst['interest_amount'];
    $totalInstallment += (float)$inst['installment_amount'];
    $totalPaid        += (float)$inst['paid_amount'];
    $totalRemaining   += (float)$inst['remaining_amount'];

    if ($nextPayableInstallmentId === null && (float)$inst['remaining_amount'] > 0) {
        $nextPayableInstallmentId = (int)$inst['id'];
    }
}

$canCollect = can_collect_payments() && $loan['status'] === 'active' && $totalRemaining > 0;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-muted">Repayments</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($loan['loan_number']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Repayment Ledger: <?php echo e($loan['loan_number']); ?></h2>
            <span class="text-muted">&bull;</span>
            <span class="fw-semibold text-secondary"><?php echo e($loan['customer_name']); ?></span>
            <?php echo get_loan_status_badge($loan['status']); ?>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/repayments/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Repayments
        </a>
        <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i> Loan Application File
        </a>
        <a href="<?php echo url('modules/loans/schedule.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print Schedule
        </a>
        <?php if ($canCollect && $nextPayableInstallmentId !== null): ?>
            <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $loan['id'] . '&installment_id=' . $nextPayableInstallmentId); ?>" class="btn btn-success fw-semibold">
                <i class="bi bi-cash me-1"></i> Collect Next Due Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Account Summary Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-badge-fill me-2 text-primary"></i> Account & Contract Summary</h3>
        <span class="badge bg-light text-dark border font-monospace"><?php echo e($loan['customer_code']); ?></span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 small">
            <div class="col-12 col-sm-6 col-md-3">
                <span class="text-muted d-block">Borrower Name</span>
                <strong class="text-dark fs-6"><?php echo e($loan['customer_name']); ?></strong>
                <div class="text-muted"><?php echo e($loan['customer_phone']); ?></div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <span class="text-muted d-block">Loan Product & Terms</span>
                <span class="fw-semibold text-dark"><?php echo e($loan['product_name'] ?? 'Product'); ?></span>
                <div class="text-muted"><?php echo number_format($loan['interest_rate'], 2); ?>% <?php echo e(get_interest_method_label($loan['interest_method'])); ?> (<?php echo (int)$loan['term'] . ' ' . ucfirst($loan['term_unit']); ?>)</div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <span class="text-muted d-block">Disbursed Principal</span>
                <span class="fw-bold text-dark fs-6"><?php echo format_currency($loan['disbursed_amount'] ?? $loan['requested_amount']); ?></span>
                <div class="text-muted">On <?php echo !empty($loan['disbursement_date']) ? date('M d, Y', strtotime($loan['disbursement_date'])) : '-'; ?></div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <span class="text-muted d-block">Current Outstanding Balance</span>
                <span class="fw-bold <?php echo $totalRemaining > 0 ? 'text-danger' : 'text-success'; ?> fs-5">
                    <?php echo format_currency($totalRemaining); ?>
                </span>
                <div class="text-muted">Total Paid: <span class="text-success fw-semibold"><?php echo format_currency($totalPaid); ?></span> / <?php echo format_currency($totalInstallment); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Installments Amortization Ledger Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-check-fill me-2 text-primary"></i> Installment Repayment Schedule</h4>
        <span class="badge bg-light text-dark border"><?php echo count($installments); ?> Total Installments</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 60px;">#</th>
                        <th class="py-3">Due Date</th>
                        <th class="py-3 text-end">Principal</th>
                        <th class="py-3 text-end">Interest</th>
                        <th class="py-3 text-end">Installment</th>
                        <th class="py-3 text-end">Paid Amount</th>
                        <th class="py-3 text-end">Remaining</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end" style="width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                        <?php 
                            $isOverdue = ($inst['due_date'] < $today && (float)$inst['remaining_amount'] > 0 && $inst['status'] !== 'paid');
                            $effectiveStatus = $isOverdue ? 'overdue' : $inst['status'];
                        ?>
                        <tr>
                            <td class="ps-3 font-monospace text-muted fw-bold">
                                <?php echo $inst['installment_number']; ?>
                            </td>
                            <td class="fw-semibold text-dark text-nowrap">
                                <?php echo date('M d, Y', strtotime($inst['due_date'])); ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Late</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-dark">
                                <?php echo format_currency($inst['principal_amount']); ?>
                            </td>
                            <td class="text-end text-muted">
                                <?php echo format_currency($inst['interest_amount']); ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                <?php echo format_currency($inst['installment_amount']); ?>
                            </td>
                            <td class="text-end text-success fw-semibold">
                                <?php echo format_currency($inst['paid_amount']); ?>
                            </td>
                            <td class="text-end fw-semibold <?php echo (float)$inst['remaining_amount'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo format_currency($inst['remaining_amount']); ?>
                            </td>
                            <td class="text-center">
                                <?php echo get_installment_status_badge($effectiveStatus); ?>
                            </td>
                            <td class="pe-3 text-end">
                                <?php if ($loan['status'] === 'active' && can_collect_payments() && (float)$inst['remaining_amount'] > 0): ?>
                                    <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $loan['id'] . '&installment_id=' . $inst['id']); ?>" class="btn btn-sm btn-success text-nowrap">
                                        <i class="bi bi-cash me-1"></i> Collect
                                    </a>
                                <?php elseif ($inst['status'] === 'paid'): ?>
                                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Settled</span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                    <tr>
                        <td colspan="2" class="ps-3 py-3 text-dark text-uppercase small">Grand Totals:</td>
                        <td class="text-end py-3 text-dark"><?php echo format_currency($totalPrincipal); ?></td>
                        <td class="text-end py-3 text-muted"><?php echo format_currency($totalInterest); ?></td>
                        <td class="text-end py-3 text-primary fs-6"><?php echo format_currency($totalInstallment); ?></td>
                        <td class="text-end py-3 text-success"><?php echo format_currency($totalPaid); ?></td>
                        <td class="text-end py-3 text-danger fs-6"><?php echo format_currency($totalRemaining); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Payment History Transactions Log Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="h6 mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i> Payment Receipts & Transaction Log</h4>
        <span class="badge bg-light text-dark border"><?php echo count($payments); ?> Transactions</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($payments)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3">Receipt Reference</th>
                            <th class="py-3">Payment Date</th>
                            <th class="py-3">Installment #</th>
                            <th class="py-3 text-end">Amount Paid</th>
                            <th class="py-3">Payment Channel</th>
                            <th class="py-3">Collected By</th>
                            <th class="pe-3 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">
                                    <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $p['payment_reference']); ?>" class="text-decoration-none">
                                        <?php echo e($p['payment_reference']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                <td><span class="badge bg-light text-dark border">Installment #<?php echo $p['installment_number']; ?></span></td>
                                <td class="text-end fw-bold text-success"><?php echo format_currency($p['amount']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo e(get_payment_method_label($p['payment_method'])); ?></span></td>
                                <td class="small text-muted"><?php echo e($p['collector_name'] ?? 'System'); ?></td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $p['payment_reference']); ?>" class="btn btn-sm btn-outline-secondary" title="View Printable Receipt">
                                        <i class="bi bi-receipt me-1"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-journal-x fs-3 d-block mb-2 text-muted"></i>
                <p class="mb-0 small">No payments have been recorded for this loan yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
