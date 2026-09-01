<?php
/**
 * Portfolio Financial Summary & Reconciliation Report
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Portfolio Financial Summary';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('portfolio')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view the Portfolio Report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();
$today = date('Y-m-d');

// 2. High-level Portfolio Accounting Metrics
$totalDisbursed = (float)$db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();
$totalInterestExpected = (float)$db->query("SELECT COALESCE(SUM(estimated_interest_amount), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();
$totalContractPayable = (float)$db->query("SELECT COALESCE(SUM(estimated_total_payable), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();
$totalCollections = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments")->fetchColumn();

$totalOutstanding = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.remaining_amount > 0
")->fetchColumn();

$overdueExposure = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
")->fetchColumn();

$recoveryRate = ($totalContractPayable > 0) ? round(($totalCollections / $totalContractPayable) * 100, 2) : 0.0;

// 3. Status Distribution Breakdown Table
$statusBreakdown = $db->query("
    SELECT status,
           COUNT(*) AS total_count,
           COALESCE(SUM(requested_amount), 0) AS total_requested,
           COALESCE(SUM(disbursed_amount), 0) AS total_disbursed,
           COALESCE(SUM(estimated_total_payable), 0) AS total_payable
    FROM loans
    GROUP BY status
    ORDER BY FIELD(status, 'active', 'completed', 'approved', 'pending', 'draft', 'rejected', 'cancelled')
")->fetchAll();

// 4. Product-level Performance Breakdown Table
$productBreakdown = $db->query("
    SELECT lp.id, lp.product_code, lp.name AS product_name, lp.interest_rate, lp.interest_method,
           COUNT(l.id) AS total_loans,
           COALESCE(SUM(CASE WHEN l.status IN ('active', 'completed') THEN l.disbursed_amount ELSE 0 END), 0) AS total_disbursed,
           COALESCE(SUM(CASE WHEN l.status IN ('active', 'completed') THEN l.estimated_interest_amount ELSE 0 END), 0) AS total_interest,
           COALESCE(SUM(CASE WHEN l.status IN ('active', 'completed') THEN l.estimated_total_payable ELSE 0 END), 0) AS total_payable,
           COALESCE(SUM(CASE WHEN l.status = 'active' THEN (SELECT SUM(li.remaining_amount) FROM loan_installments li WHERE li.loan_id = l.id) ELSE 0 END), 0) AS total_outstanding
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.loan_product_id
    GROUP BY lp.id
    ORDER BY total_disbursed DESC
")->fetchAll();

// 5. Payment Methods Allocation
$channelBreakdown = $db->query("
    SELECT payment_method,
           COUNT(*) AS payment_count,
           COALESCE(SUM(amount), 0) AS total_collected
    FROM loan_payments
    GROUP BY payment_method
    ORDER BY total_collected DESC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/reports/index.php'); ?>" class="text-decoration-none text-muted">Reports</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Portfolio Summary</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Financial Portfolio Summary & Audit</h2>
            <span class="badge bg-primary">Portfolio Health</span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Reports Dashboard
        </a>
        <a href="<?php echo url('modules/reports/print.php?report=portfolio'); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Summary
        </a>
    </div>
</div>

<!-- Key Financial KPI Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Capital Disbursed -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #0284c7 !important;">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Total Principal Disbursed</span>
                <span class="h4 fw-bold text-dark font-monospace mb-1 d-block"><?php echo format_currency($totalDisbursed); ?></span>
                <span class="small text-muted">Total capital principal released</span>
            </div>
        </div>
    </div>

    <!-- Collections Realized -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Total Repayments Collected</span>
                <span class="h4 fw-bold text-success font-monospace mb-1 d-block"><?php echo format_currency($totalCollections); ?></span>
                <span class="small text-muted">Portfolio Collection Yield: <strong><?php echo $recoveryRate; ?>%</strong></span>
            </div>
        </div>
    </div>

    <!-- Current Outstanding -->
    <div class="col-12 col-sm-12 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #dc2626 !important;">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Current Active Outstanding</span>
                <span class="h4 fw-bold text-danger font-monospace mb-1 d-block"><?php echo format_currency($totalOutstanding); ?></span>
                <span class="small text-muted">Delinquency Exposure: <strong class="text-danger"><?php echo format_currency($overdueExposure); ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Accounting Reconciliation Strip -->
<div class="card shadow-sm mb-4 bg-light border">
    <div class="card-body p-3">
        <div class="row g-3 text-center text-sm-start small">
            <div class="col-12 col-sm-4 border-end">
                <span class="text-muted d-block">Expected Interest Revenue</span>
                <strong class="text-dark font-monospace fs-6"><?php echo format_currency($totalInterestExpected); ?></strong>
            </div>
            <div class="col-12 col-sm-4 border-end">
                <span class="text-muted d-block">Total Contract Valuation (Payable)</span>
                <strong class="text-dark font-monospace fs-6"><?php echo format_currency($totalContractPayable); ?></strong>
            </div>
            <div class="col-12 col-sm-4">
                <span class="text-muted d-block">Overdue Delinquency Exposure</span>
                <strong class="text-danger font-monospace fs-6"><?php echo format_currency($overdueExposure); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- 1. Product-Level Performance Breakdown Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h3 class="h6 mb-0 fw-bold"><i class="bi bi-tags-fill me-2 text-primary"></i> Lending Product Portfolio Breakdown</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Product Name</th>
                        <th class="py-3">Code</th>
                        <th class="py-3">Interest Terms</th>
                        <th class="py-3 text-center">Originated Loans</th>
                        <th class="py-3 text-end">Disbursed Principal</th>
                        <th class="py-3 text-end">Expected Interest</th>
                        <th class="py-3 text-end">Total Payable</th>
                        <th class="pe-3 py-3 text-end">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productBreakdown)): ?>
                        <?php foreach ($productBreakdown as $row): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark"><?php echo e($row['product_name']); ?></td>
                                <td class="font-monospace text-muted"><?php echo e($row['product_code']); ?></td>
                                <td class="small text-muted"><?php echo number_format($row['interest_rate'], 2); ?>% (<?php echo e(get_interest_method_label($row['interest_method'])); ?>)</td>
                                <td class="text-center fw-bold"><?php echo number_format($row['total_loans']); ?></td>
                                <td class="text-end font-monospace"><?php echo format_currency($row['total_disbursed']); ?></td>
                                <td class="text-end font-monospace text-muted"><?php echo format_currency($row['total_interest']); ?></td>
                                <td class="text-end font-monospace text-success fw-semibold"><?php echo format_currency($row['total_payable']); ?></td>
                                <td class="pe-3 text-end font-monospace fw-bold <?php echo (float)$row['total_outstanding'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo format_currency($row['total_outstanding']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No product performance records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 2. Loan Status Distribution Table -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm mb-4 h-100">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i> Application Status Distribution</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-3">Status</th>
                                <th class="py-3 text-center">Count</th>
                                <th class="py-3 text-end">Requested Volume</th>
                                <th class="pe-3 py-3 text-end">Disbursed Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusBreakdown as $sb): ?>
                                <tr>
                                    <td class="ps-3"><?php echo get_loan_status_badge($sb['status']); ?></td>
                                    <td class="text-center fw-bold"><?php echo number_format($sb['total_count']); ?></td>
                                    <td class="text-end font-monospace"><?php echo format_currency($sb['total_requested']); ?></td>
                                    <td class="pe-3 text-end font-monospace fw-semibold text-dark"><?php echo format_currency($sb['total_disbursed']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Payment Channels Breakdown -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm mb-4 h-100">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-credit-card-2-front me-2 text-primary"></i> Repayment Collections by Channel</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-3">Payment Channel</th>
                                <th class="py-3 text-center">Receipts</th>
                                <th class="pe-3 py-3 text-end">Total Collections</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($channelBreakdown)): ?>
                                <?php foreach ($channelBreakdown as $cb): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">
                                            <?php echo e(get_payment_method_label($cb['payment_method'])); ?>
                                        </td>
                                        <td class="text-center fw-bold"><?php echo number_format($cb['payment_count']); ?></td>
                                        <td class="pe-3 text-end font-monospace fw-bold text-success">
                                            <?php echo format_currency($cb['total_collected']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No repayment collections recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
