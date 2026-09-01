<?php
/**
 * Loan Application View
 * Loan Management System (loan-mgt) - Phase 4
 */

$pageTitle = 'Loan Details';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$loanId = (int)($_GET['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan application specified.');
    redirect('modules/loans/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT l.*, 
           c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, c.address AS customer_address, c.city AS customer_city,
           c.occupation AS customer_occupation, c.monthly_income AS customer_income,
           c.emergency_contact_name, c.emergency_contact_phone, c.photo AS customer_photo,
           c.status AS customer_status,
           lp.name AS current_product_name, lp.product_code AS current_product_code,
           u.name AS creator_name, u.role AS creator_role,
           ua.name AS approver_name, ua.role AS approver_role,
           ud.name AS disburser_name, ud.role AS disburser_role
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    LEFT JOIN users u ON l.created_by = u.id 
    LEFT JOIN users ua ON l.approved_by = ua.id 
    LEFT JOIN users ud ON l.disbursed_by = ud.id 
    WHERE l.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

$currentUserId   = auth_id();
$isCreator       = ($currentUserId !== null && (int)$loan['created_by'] === (int)$currentUserId);
$canApproveRole  = can_approve_loans();
$canDisburseRole = can_disburse_loans();
$isPending       = ($loan['status'] === 'pending');
$isDraft         = ($loan['status'] === 'draft');
$isApproved      = ($loan['status'] === 'approved');
$isActive        = ($loan['status'] === 'active');
$isRejected      = ($loan['status'] === 'rejected');
$isCancelled     = ($loan['status'] === 'cancelled');

// Self-approval check rule (Section 33: "Do not allow the person who created an application to approve the same application")
$canApproveThisLoan = $canApproveRole && $isPending && !$isCreator;
$selfApprovalBlocked = $canApproveRole && $isPending && $isCreator;

$isEditable = can_edit_loan($loan, $currentUserId);
$customerPhotoUrl = get_customer_photo_url($loan['customer_photo'], $loan['customer_name']);

// Fetch installments if active
$installments = [];
$totalPrincipal = 0.0;
$totalInterest = 0.0;
$totalInstallment = 0.0;
$totalPaid = 0.0;
$totalRemaining = 0.0;

if ($isActive) {
    $instStmt = $db->prepare('SELECT * FROM loan_installments WHERE loan_id = :id ORDER BY installment_number ASC');
    $instStmt->execute([':id' => $loanId]);
    $installments = $instStmt->fetchAll();

    foreach ($installments as $inst) {
        $totalPrincipal   += (float)$inst['principal_amount'];
        $totalInterest    += (float)$inst['interest_amount'];
        $totalInstallment += (float)$inst['installment_amount'];
        $totalPaid        += (float)$inst['paid_amount'];
        $totalRemaining   += (float)$inst['remaining_amount'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/index.php'); ?>" class="text-decoration-none text-muted">Loan Applications</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($loan['loan_number']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0"><?php echo e($loan['loan_number']); ?></h2>
            <span class="text-muted fs-6">&bull;</span>
            <span class="fs-6 fw-semibold text-secondary"><?php echo e($loan['customer_name']); ?></span>
            <?php echo get_loan_status_badge($loan['status']); ?>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Loan Portfolio
        </a>

        <?php if ($isApproved && $canDisburseRole): ?>
            <a href="<?php echo url('modules/loans/disburse.php?id=' . $loan['id']); ?>" class="btn btn-primary">
                <i class="bi bi-cash-coin me-1"></i> Disburse Loan
            </a>
        <?php endif; ?>

        <?php if ($isActive): ?>
            <a href="<?php echo url('modules/loans/schedule.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-calendar-check me-1"></i> View Schedule
            </a>
        <?php endif; ?>

        <?php if ($isEditable): ?>
            <a href="<?php echo url('modules/loans/edit.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit Application
            </a>
        <?php endif; ?>

        <?php if ($isDraft && ($isCreator || can_approve_loans())): ?>
            <form action="<?php echo url('modules/loans/update.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Submit this draft loan for formal approval?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">
                <input type="hidden" name="action" value="submit">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i> Submit for Review
                </button>
            </form>
        <?php endif; ?>

        <?php if ($isDraft || $isPending): ?>
            <?php if (can_approve_loans() || $isCreator): ?>
                <form action="<?php echo url('modules/loans/cancel.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this application?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i> Cancel Application
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Self Approval Restriction Notice -->
<?php if ($selfApprovalBlocked): ?>
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-shield-lock-fill fs-4 me-3"></i>
        <div>
            <strong>Self-Approval Segregation Policy:</strong> You originated this loan application. In compliance with internal underwriting controls, approval or rejection must be executed by another Administrator or Loan Manager.
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Customer Profile & Audit Info -->
    <div class="col-12 col-lg-4">
        <!-- Borrower Info Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i> Borrower Profile</h3>
                <a href="<?php echo url('modules/customers/view.php?id=' . $loan['customer_id']); ?>" class="small text-decoration-none" title="View Full Customer File">
                    View File &rarr;
                </a>
            </div>
            <div class="card-body p-4 text-center">
                <img src="<?php echo e($customerPhotoUrl); ?>" alt="<?php echo e($loan['customer_name']); ?>" class="avatar-img-lg mb-3">
                <h3 class="h6 fw-bold text-dark mb-1"><?php echo e($loan['customer_name']); ?></h3>
                <span class="badge bg-light text-dark border font-monospace mb-3"><?php echo e($loan['customer_code']); ?></span>

                <div class="text-start small border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Primary Phone:</span>
                        <span class="fw-semibold text-dark"><?php echo e($loan['customer_phone']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Email Address:</span>
                        <span class="text-dark"><?php echo e($loan['customer_email'] ?: 'None'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Occupation:</span>
                        <span class="text-dark"><?php echo e($loan['customer_occupation'] ?: 'Not Specified'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Monthly Income:</span>
                        <span class="fw-bold text-success"><?php echo format_currency($loan['customer_income']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Location:</span>
                        <span class="text-dark"><?php echo e($loan['customer_city'] ?: 'Not Specified'); ?></span>
                    </div>
                </div>

                <?php if (!empty($loan['emergency_contact_name'])): ?>
                    <div class="text-start small border-top pt-3 mt-3">
                        <span class="fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.75rem;">Guarantor / Emergency Contact</span>
                        <div class="fw-semibold text-dark"><?php echo e($loan['emergency_contact_name']); ?></div>
                        <div class="text-muted"><?php echo e($loan['emergency_contact_phone'] ?: 'No Phone'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Application & Disbursement Audit Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i> Contract Audit Trail</h4>
            </div>
            <div class="card-body p-3 small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Originated By:</span>
                    <span class="fw-semibold text-dark">
                        <?php echo !empty($loan['creator_name']) ? e($loan['creator_name']) . ' (' . e(get_role_label($loan['creator_role'] ?? '')) . ')' : 'System'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Application Date:</span>
                    <span class="text-dark"><?php echo date('F j, Y', strtotime($loan['application_date'])); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created Timestamp:</span>
                    <span class="text-dark"><?php echo date('M d, Y g:i a', strtotime($loan['created_at'])); ?></span>
                </div>

                <?php if ($isApproved || $isActive): ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Approved By:</span>
                        <span class="fw-bold text-success">
                            <?php echo !empty($loan['approver_name']) ? e($loan['approver_name']) . ' (' . e(get_role_label($loan['approver_role'] ?? '')) . ')' : 'Management'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Approval Date:</span>
                        <span class="text-success fw-semibold">
                            <?php echo !empty($loan['approved_at']) ? date('M d, Y g:i a', strtotime($loan['approved_at'])) : '-'; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($isActive): ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Disbursed By:</span>
                        <span class="fw-bold text-primary">
                            <?php echo !empty($loan['disburser_name']) ? e($loan['disburser_name']) . ' (' . e(get_role_label($loan['disburser_role'] ?? '')) . ')' : 'Finance Dept'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Disbursement Date:</span>
                        <span class="text-dark fw-semibold">
                            <?php echo !empty($loan['disbursement_date']) ? date('F j, Y', strtotime($loan['disbursement_date'])) : '-'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Channel:</span>
                        <span class="badge bg-light text-dark border">
                            <?php echo e(get_disbursement_method_label($loan['disbursement_method'] ?? 'cash')); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($isRejected): ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Decision:</span>
                        <span class="fw-bold text-danger">Application Rejected</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Rejection Reason:</span>
                        <span class="text-danger"><?php echo e($loan['rejection_reason'] ?: 'None stated'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Loan Terms, Financial Breakdown, and Schedule Engine -->
    <div class="col-12 col-lg-8">
        <!-- Underwriting Decision Panel (for Admin/Manager when Pending) -->
        <?php if ($canApproveThisLoan): ?>
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10 text-dark py-3">
                    <h4 class="h6 mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-warning"></i> Underwriting Decision Panel</h4>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        Review the applicant's requested terms and financial breakdown below. As an authorized loan officer/manager, record your underwriting determination:
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <form action="<?php echo url('modules/loans/approve.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Approve loan application <?php echo e($loan['loan_number']); ?> for <?php echo format_currency($loan['requested_amount']); ?>?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">
                            <button type="submit" class="btn btn-success px-4 fw-semibold">
                                <i class="bi bi-check-circle-fill me-1"></i> Approve Loan
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal" data-bs-target="#rejectLoanModal">
                            <i class="bi bi-x-circle me-1"></i> Reject Application
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div class="modal fade" id="rejectLoanModal" tabindex="-1" aria-labelledby="rejectLoanModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="<?php echo url('modules/loans/reject.php'); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h6 fw-bold" id="rejectLoanModalLabel">Reject Loan Application</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-3">
                                    Please enter the underwriting rationale or compliance reason for rejecting loan <strong><?php echo e($loan['loan_number']); ?></strong>.
                                </p>
                                <div class="mb-3">
                                    <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="e.g. Insufficient debt service coverage ratio, unverifiable income source..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Disbursement Callout for Approved Loans -->
        <?php if ($isApproved && $canDisburseRole): ?>
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success bg-opacity-10 text-success py-3 d-flex justify-content-between align-items-center">
                    <h4 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i> Ready for Disbursement</h4>
                    <span class="badge bg-success">Underwriting Approved</span>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <div>
                        <p class="small text-dark mb-1 fw-semibold">
                            Loan application approved for <?php echo format_currency($loan['requested_amount']); ?>.
                        </p>
                        <p class="small text-muted mb-0">
                            Proceed to finalize payment release, choose disbursement channel, and activate the installment schedule.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/loans/disburse.php?id=' . $loan['id']); ?>" class="btn btn-primary px-4 py-2 text-nowrap fw-semibold">
                            <i class="bi bi-cash-coin me-1"></i> Disburse Loan
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Contract Terms Snapshot -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-bookmark-star-fill me-2 text-primary"></i> Contract Terms Snapshot</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Loan Product</span>
                        <span class="fw-bold text-dark fs-6">
                            <?php echo e($loan['current_product_name'] ?? 'Custom Product'); ?>
                        </span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Contract Interest Rate</span>
                        <span class="fw-bold text-primary fs-6"><?php echo number_format($loan['interest_rate'], 2); ?>%</span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Interest Calculation Method</span>
                        <span class="fw-semibold text-dark"><?php echo e(get_interest_method_label($loan['interest_method'])); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Repayment Frequency</span>
                        <span class="fw-semibold text-dark"><?php echo e(get_frequency_label($loan['repayment_frequency'])); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Term Duration</span>
                        <span class="fw-semibold text-dark"><?php echo (int)$loan['term'] . ' ' . ucfirst($loan['term_unit']); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Processing Fee Rate</span>
                        <span class="fw-semibold text-dark"><?php echo number_format($loan['processing_fee_rate'], 2); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i> Financial Calculation Breakdown</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <span class="text-muted small d-block mb-1">Requested Principal</span>
                            <span class="h5 fw-bold text-dark mb-0"><?php echo format_currency($loan['requested_amount']); ?></span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <span class="text-muted small d-block mb-1">Estimated Interest</span>
                            <span class="h5 fw-bold text-primary mb-0"><?php echo format_currency($loan['estimated_interest_amount']); ?></span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <span class="text-muted small d-block mb-1">Processing Fee</span>
                            <span class="h5 fw-bold text-danger mb-0"><?php echo format_currency($loan['processing_fee_amount']); ?></span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded text-center border border-primary">
                            <span class="text-muted small d-block mb-1">Est. Total Payable</span>
                            <span class="h5 fw-bold text-dark mb-0"><?php echo format_currency($loan['estimated_total_payable']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Repayment Schedule Section (Phase 4) -->
        <?php if ($isActive && !empty($installments)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-check-fill me-2 text-primary"></i> Active Repayment Schedule</h4>
                    <a href="<?php echo url('modules/loans/schedule.php?id=' . $loan['id']); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-printer me-1"></i> Print Full Schedule
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light text-muted text-uppercase sticky-top" style="font-size: 0.75rem;">
                                <tr>
                                    <th class="ps-3 py-2">#</th>
                                    <th class="py-2">Due Date</th>
                                    <th class="py-2 text-end">Principal</th>
                                    <th class="py-2 text-end">Interest</th>
                                    <th class="py-2 text-end">Installment</th>
                                    <th class="py-2 text-end">Paid</th>
                                    <th class="py-2 text-end">Remaining</th>
                                    <th class="pe-3 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installments as $inst): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace text-muted"><?php echo $inst['installment_number']; ?></td>
                                        <td class="text-nowrap"><?php echo date('M d, Y', strtotime($inst['due_date'])); ?></td>
                                        <td class="text-end"><?php echo format_currency($inst['principal_amount']); ?></td>
                                        <td class="text-end text-muted"><?php echo format_currency($inst['interest_amount']); ?></td>
                                        <td class="text-end fw-semibold text-dark"><?php echo format_currency($inst['installment_amount']); ?></td>
                                        <td class="text-end text-success"><?php echo format_currency($inst['paid_amount']); ?></td>
                                        <td class="text-end fw-semibold text-danger"><?php echo format_currency($inst['remaining_amount']); ?></td>
                                        <td class="pe-3 text-center">
                                            <span class="badge badge-status-pending"><?php echo e(ucfirst($inst['status'])); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold sticky-bottom">
                                <tr>
                                    <td colspan="2" class="ps-3">Totals:</td>
                                    <td class="text-end"><?php echo format_currency($totalPrincipal); ?></td>
                                    <td class="text-end"><?php echo format_currency($totalInterest); ?></td>
                                    <td class="text-end text-primary"><?php echo format_currency($totalInstallment); ?></td>
                                    <td class="text-end text-success"><?php echo format_currency($totalPaid); ?></td>
                                    <td class="text-end text-danger"><?php echo format_currency($totalRemaining); ?></td>
                                    <td class="pe-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Purpose & Remarks -->
        <div class="card shadow-sm mb-0">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-file-text me-2 text-primary"></i> Application Purpose & Remarks</h4>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Declared Loan Purpose</span>
                    <p class="text-dark mb-0">
                        <?php echo !empty($loan['purpose']) ? nl2br(e($loan['purpose'])) : '<span class="text-muted fst-italic">No specific purpose stated.</span>'; ?>
                    </p>
                </div>

                <div class="mb-0">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Underwriter Remarks / Notes</span>
                    <p class="text-dark mb-0">
                        <?php echo !empty($loan['notes']) ? nl2br(e($loan['notes'])) : '<span class="text-muted fst-italic">No underwriting notes recorded.</span>'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
