<?php
/**
 * Standalone Repayment Schedule View
 * Loan Management System (loan-mgt) - Phase 4
 */

$pageTitle = 'Repayment Schedule';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$loanId = (int)($_GET['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan specified.');
    redirect('modules/loans/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT l.*, 
           c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, c.address AS customer_address, c.city AS customer_city,
           lp.name AS product_name, lp.product_code,
           u.name AS creator_name,
           ud.name AS disburser_name
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    LEFT JOIN users u ON l.created_by = u.id 
    LEFT JOIN users ud ON l.disbursed_by = ud.id 
    WHERE l.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan record not found.');
    redirect('modules/loans/index.php');
}

// Fetch Installments
$instStmt = $db->prepare('SELECT * FROM loan_installments WHERE loan_id = :id ORDER BY installment_number ASC');
$instStmt->execute([':id' => $loanId]);
$installments = $instStmt->fetchAll();

if (empty($installments)) {
    set_flash('warning', 'No repayment schedule has been generated for this loan yet.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// Compute Totals
$totalPrincipal   = 0.0;
$totalInterest    = 0.0;
$totalInstallment = 0.0;
$totalPaid        = 0.0;
$totalRemaining   = 0.0;

foreach ($installments as $inst) {
    $totalPrincipal   += (float)$inst['principal_amount'];
    $totalInterest    += (float)$inst['interest_amount'];
    $totalInstallment += (float)$inst['installment_amount'];
    $totalPaid        += (float)$inst['paid_amount'];
    $totalRemaining   += (float)$inst['remaining_amount'];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Print-Only Header Styles -->
<style>
@media print {
    .navbar, .sidebar, .breadcrumb, .btn, .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/index.php'); ?>" class="text-decoration-none text-muted">Loan Applications</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="text-decoration-none text-muted"><?php echo e($loan['loan_number']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Repayment Schedule</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Repayment Schedule: <?php echo e($loan['loan_number']); ?></h2>
            <?php echo get_loan_status_badge($loan['status']); ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
            <i class="bi bi-printer me-1"></i> Print Schedule
        </button>
        <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Loan Details
        </a>
    </div>
</div>

<!-- Loan Summary Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i> Borrower & Contract Parameters</h3>
        <span class="badge bg-light text-dark border font-monospace"><?php echo e($loan['loan_number']); ?></span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 small">
            <div class="col-12 col-md-3">
                <span class="text-muted d-block">Borrower Name</span>
                <strong class="text-dark fs-6"><?php echo e($loan['customer_name']); ?></strong>
                <div class="text-muted font-monospace"><?php echo e($loan['customer_code']); ?></div>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-muted d-block">Primary Contact</span>
                <span class="text-dark fw-semibold"><?php echo e($loan['customer_phone']); ?></span>
                <div class="text-muted"><?php echo e($loan['customer_city'] ?: 'City Not Specified'); ?></div>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-muted d-block">Disbursed Principal</span>
                <span class="fw-bold text-success fs-6"><?php echo format_currency($loan['disbursed_amount'] ?? $loan['requested_amount']); ?></span>
                <div class="text-muted"><?php echo !empty($loan['disbursement_date']) ? 'On ' . date('M d, Y', strtotime($loan['disbursement_date'])) : '-'; ?></div>
            </div>
            <div class="col-12 col-md-3">
                <span class="text-muted d-block">Contract Terms</span>
                <span class="fw-semibold text-dark"><?php echo number_format($loan['interest_rate'], 2); ?>% <?php echo e(get_interest_method_label($loan['interest_method'])); ?></span>
                <div class="text-muted"><?php echo (int)$loan['term'] . ' ' . ucfirst($loan['term_unit']); ?> (<?php echo e(get_frequency_label($loan['repayment_frequency'])); ?>)</div>
            </div>
        </div>
    </div>
</div>

<!-- Installment Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-check-fill me-2 text-primary"></i> Installment Amortization Schedule</h4>
        <span class="badge bg-light text-dark border"><?php echo count($installments); ?> Total Installments</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 70px;">#</th>
                        <th class="py-3">Due Date</th>
                        <th class="py-3 text-end">Principal</th>
                        <th class="py-3 text-end">Interest</th>
                        <th class="py-3 text-end">Installment</th>
                        <th class="py-3 text-end">Paid Amount</th>
                        <th class="py-3 text-end">Remaining</th>
                        <th class="pe-3 py-3 text-center" style="width: 110px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                        <tr>
                            <td class="ps-3 font-monospace text-muted fw-semibold">
                                <?php echo $inst['installment_number']; ?>
                            </td>
                            <td class="fw-semibold text-dark text-nowrap">
                                <?php echo date('M d, Y', strtotime($inst['due_date'])); ?>
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
                            <td class="text-end text-success">
                                <?php echo format_currency($inst['paid_amount']); ?>
                            </td>
                            <td class="text-end fw-semibold text-danger">
                                <?php echo format_currency($inst['remaining_amount']); ?>
                            </td>
                            <td class="pe-3 text-center">
                                <span class="badge badge-status-pending">
                                    <?php echo e(ucfirst($inst['status'])); ?>
                                </span>
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
                        <td class="pe-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-muted small p-3">
        <i class="bi bi-info-circle me-1"></i> Exact cent rounding applied. All installments are scheduled and ready for payment processing in subsequent repayment management phases.
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
