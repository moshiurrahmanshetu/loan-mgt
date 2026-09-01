<?php
/**
 * Loan Disbursement View & Confirmation
 * Loan Management System (loan-mgt) - Phase 4
 */

$pageTitle = 'Disburse Loan';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Authorization Guard: Only Admin and Manager can disburse loans
if (!can_disburse_loans()) {
    set_flash('danger', 'Unauthorized: Only Administrators and Loan Managers have disbursement authorization.');
    redirect('modules/loans/index.php');
}

$loanId = (int)($_GET['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan application specified.');
    redirect('modules/loans/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT l.*, 
           c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, c.occupation AS customer_occupation,
           lp.name AS product_name, lp.product_code
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    WHERE l.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

// 2. Status Eligibility Guard: Only 'approved' loans can be disbursed
if ($loan['status'] !== 'approved') {
    if ($loan['status'] === 'active') {
        set_flash('info', 'This loan has already been disbursed and is currently Active.');
    } else {
        set_flash('danger', 'Cannot disburse loan: Only approved applications are eligible for disbursement (Current status: ' . ucfirst($loan['status']) . ').');
    }
    redirect('modules/loans/view.php?id=' . $loanId);
}

// Check if already disbursed
if (!empty($loan['disbursement_date'])) {
    set_flash('warning', 'This loan already has a recorded disbursement date.');
    redirect('modules/loans/view.php?id=' . $loanId);
}

$today = date('Y-m-d');
$schedulePreview = generate_repayment_schedule($loan, $today);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/index.php'); ?>" class="text-decoration-none text-muted">Loan Applications</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="text-decoration-none text-muted"><?php echo e($loan['loan_number']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Disbursement</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Disburse Loan: <?php echo e($loan['loan_number']); ?></h2>
            <span class="badge bg-success">Approved File</span>
        </div>
    </div>

    <div>
        <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Loan Details
        </a>
    </div>
</div>

<form action="<?php echo url('modules/loans/process-disbursement.php'); ?>" method="POST" id="disbursementForm" autocomplete="off" onsubmit="return confirm('Are you sure you want to finalize disbursement for <?php echo format_currency($loan['requested_amount']); ?>? This will activate the loan and lock the repayment schedule.');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">

    <div class="row g-4">
        <!-- Left Column: Contract Terms & Disbursement Details Form -->
        <div class="col-12 col-lg-7">
            <!-- 1. Contract Terms Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i> Approved Contract Summary</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 small">
                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Borrower</span>
                            <span class="fw-bold text-dark fs-6"><?php echo e($loan['customer_name']); ?></span>
                            <div class="text-muted font-monospace"><?php echo e($loan['customer_code']); ?> &bull; <?php echo e($loan['customer_phone']); ?></div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Loan Product</span>
                            <span class="fw-bold text-dark fs-6"><?php echo e($loan['product_name'] ?? 'Product'); ?></span>
                            <div class="text-muted"><?php echo e(get_interest_method_label($loan['interest_method'])); ?> (<?php echo number_format($loan['interest_rate'], 2); ?>%)</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Approved Principal</span>
                            <span class="fw-bold text-success fs-5"><?php echo format_currency($loan['requested_amount']); ?></span>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Term & Frequency</span>
                            <span class="fw-semibold text-dark"><?php echo (int)$loan['term'] . ' ' . ucfirst($loan['term_unit']); ?> (<?php echo e(get_frequency_label($loan['repayment_frequency'])); ?>)</span>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Upfront Processing Fee</span>
                            <span class="fw-semibold text-danger"><?php echo format_currency($loan['processing_fee_amount']); ?> (<?php echo number_format($loan['processing_fee_rate'], 2); ?>%)</span>
                            <div class="text-muted" style="font-size: 0.75rem;">Collected separately from repayment schedule.</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Estimated Total Repayment</span>
                            <span class="fw-bold text-dark fs-6"><?php echo format_currency($loan['estimated_total_payable']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Disbursement Execution Parameters Card -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i> Disbursement Execution Parameters</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Disbursed Principal Amount ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-light fw-bold text-dark font-monospace" value="<?php echo number_format($loan['requested_amount'], 2, '.', ''); ?>" readonly disabled>
                            </div>
                            <div class="form-text small">Authoritative approved loan principal.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="disbursement_date" class="form-label fw-semibold">Disbursement Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="disbursement_date" name="disbursement_date" value="<?php echo $today; ?>" required max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            <div class="form-text small">First installment due date will be calculated from this date.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="disbursement_method" class="form-label fw-semibold">Disbursement Channel / Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="disbursement_method" name="disbursement_method" required>
                                <option value="bank_transfer" selected>Bank Transfer</option>
                                <option value="cash">Cash Voucher</option>
                                <option value="mobile_banking">Mobile Banking / Wallet</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="reference_number" class="form-label fw-semibold">Payment / Transaction Ref #</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="e.g. TRX-982342 / Check #104">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="disbursement_notes" class="form-label fw-semibold">Disbursement Remarks</label>
                        <textarea class="form-control" id="disbursement_notes" name="disbursement_notes" rows="2" placeholder="Optional notes regarding disbursement authorization..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button Card -->
            <div class="card shadow-sm mb-0">
                <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i> Confirm Disbursement & Activate Loan
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Generated Installments Schedule Preview -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm mb-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calendar2-check me-2 text-primary"></i> Repayment Schedule Preview</h3>
                    <span class="badge bg-light text-dark border"><?php echo $schedulePreview['count']; ?> Installments</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light text-muted text-uppercase sticky-top" style="font-size: 0.75rem;">
                                <tr>
                                    <th class="ps-3 py-2">#</th>
                                    <th class="py-2">Due Date</th>
                                    <th class="py-2 text-end">Principal</th>
                                    <th class="py-2 text-end">Interest</th>
                                    <th class="pe-3 py-2 text-end">Installment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedulePreview['installments'] as $inst): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace text-muted"><?php echo $inst['installment_number']; ?></td>
                                        <td class="text-nowrap"><?php echo date('M d, Y', strtotime($inst['due_date'])); ?></td>
                                        <td class="text-end text-dark"><?php echo format_currency($inst['principal_amount']); ?></td>
                                        <td class="text-end text-muted"><?php echo format_currency($inst['interest_amount']); ?></td>
                                        <td class="pe-3 text-end fw-semibold text-dark"><?php echo format_currency($inst['installment_amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold sticky-bottom">
                                <tr>
                                    <td colspan="2" class="ps-3">Totals:</td>
                                    <td class="text-end"><?php echo format_currency($schedulePreview['total_principal']); ?></td>
                                    <td class="text-end"><?php echo format_currency($schedulePreview['total_interest']); ?></td>
                                    <td class="pe-3 text-end text-primary"><?php echo format_currency($schedulePreview['total_payable']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small p-3">
                    <i class="bi bi-info-circle me-1"></i> Exact cent rounding applied. Total installment amounts equal approved total payable of <?php echo format_currency($schedulePreview['total_payable']); ?>.
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
