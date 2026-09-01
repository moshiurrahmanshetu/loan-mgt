<?php
/**
 * Dashboard View
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Fetch fresh user data from database
$currentUser = auth_user();
$db = get_db_connection();
$stmt = $db->prepare('SELECT id, name, email, phone, role, status, last_login, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $currentUser['id']]);
$userRecord = $stmt->fetch() ?: $currentUser;

$roleLabel = get_role_label($userRecord['role'] ?? 'loan_officer');
$roleBadgeClass = 'badge-role-' . ($userRecord['role'] ?? 'loan_officer');
$lastLoginDisplay = !empty($userRecord['last_login']) 
    ? date('F j, Y, g:i a', strtotime($userRecord['last_login'])) 
    : 'First session recorded';

$today = date('Y-m-d');

// Fetch Customer metrics
$totalCustStmt = $db->query('SELECT COUNT(*) FROM customers');
$totalCustomers = (int)$totalCustStmt->fetchColumn();

// Fetch Loan metrics
$totalLoansStmt = $db->query('SELECT COUNT(*) FROM loans');
$totalLoans = (int)$totalLoansStmt->fetchColumn();

$pendingLoansStmt = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'");
$pendingLoans = (int)$pendingLoansStmt->fetchColumn();

$activeLoansStmt = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'");
$activeLoans = (int)$activeLoansStmt->fetchColumn();

$disbursedVolumeStmt = $db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed')");
$disbursedVolume = (float)$disbursedVolumeStmt->fetchColumn();

// Fetch Repayment metrics
$totalOutstandingStmt = $db->query("
    SELECT COALESCE(SUM(remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.remaining_amount > 0
");
$totalOutstanding = (float)$totalOutstandingStmt->fetchColumn();

$todayCollectionStmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE payment_date = '{$today}'");
$todayCollection = (float)$todayCollectionStmt->fetchColumn();

$overdueCountStmt = $db->query("
    SELECT COUNT(*) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
");
$overdueCount = (int)$overdueCountStmt->fetchColumn();

$activeProductsStmt = $db->query("SELECT COUNT(*) FROM loan_products WHERE status = 'active'");
$activeProducts = (int)$activeProductsStmt->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card mb-4 border-0 shadow-sm" style="background-color: #ffffff; border-left: 4px solid var(--primary-color) !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="h4 mb-0 fw-bold text-dark">Welcome, <?php echo e($userRecord['name']); ?></h2>
                    <span class="badge badge-role <?php echo $roleBadgeClass; ?>"><?php echo e($roleLabel); ?></span>
                </div>
                <p class="text-muted mb-0 small">
                    You are logged in to the <strong><?php echo e(APP_NAME); ?></strong> enterprise management portal.
                </p>
            </div>
            <div class="text-md-end">
                <span class="small text-muted d-block">Last Login Session</span>
                <span class="fw-semibold text-dark small"><i class="bi bi-clock-history me-1 text-primary"></i> <?php echo e($lastLoginDisplay); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- System & Portfolio Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Active Loans & Outstanding Portfolio -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Portfolio</span>
                    <i class="bi bi-check2-circle text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($activeLoans); ?> Active</div>
                <div class="small text-muted">Outstanding: <span class="fw-semibold text-danger"><?php echo format_currency($totalOutstanding); ?></span></div>
            </div>
        </div>
    </div>

    <!-- Today's Collections -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Today's Collections</span>
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-success"><?php echo format_currency($todayCollection); ?></div>
                <div class="small text-muted">Received today (<?php echo date('M d'); ?>)</div>
            </div>
        </div>
    </div>

    <!-- Overdue Delinquency -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Overdue Risk</span>
                    <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-danger"><?php echo number_format($overdueCount); ?> Installments</div>
                <div class="small text-muted">Past scheduled due date</div>
            </div>
        </div>
    </div>

    <!-- Total Borrowers -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Registered Borrowers</span>
                    <i class="bi bi-people-fill text-info fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalCustomers); ?></div>
                <div class="small text-muted">Customer profiles on file</div>
            </div>
        </div>
    </div>
</div>

<!-- Architecture & Phase Information Notice -->
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-0">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-layers text-primary fs-5"></i>
                    <h3 class="h6 mb-0 fw-bold">System Scope & Phase Roadmap</h3>
                </div>
                <span class="badge bg-primary px-2.5 py-1.5">Phase 5 Active</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">
                    Welcome to the <strong>Loan Management System (loan-mgt)</strong>. The system is currently running <strong>Phase 5: Payment Collection & Repayment Management</strong>.
                </p>
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Repayment & Collection Summary</div>
                    <p class="text-muted small mb-0">
                        Full payment, partial payment, overpayment protection, overdue delinquency tracking, automatic loan completion, and printable transaction receipts are operational with complete role-based segregation.
                    </p>
                </div>

                <h4 class="h6 fw-bold text-dark mb-3">Implemented Modules & Capabilities</h4>
                <div class="row g-2 small">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Authentication & Session System (Phase 1)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Customer Profile CRUD & Sandbox (Phase 2)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Loan Product Templates & Limits (Phase 3)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Loan Origination & Snapshot Storage (Phase 3)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Underwriting Dual-Control Approval (Phase 3)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Loan Disbursement & Concurrency Lock (Phase 4)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Repayment Schedule & Exact Rounding (Phase 4)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Payment Collection & Partial Payments (Phase 5)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Overdue Delinquency Tracking (Phase 5)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Printable Payment Receipts (Phase 5)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Sidebar Card -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i> Operations Quick Links</h4>
            </div>
            <div class="list-group list-group-flush small">
                <a href="<?php echo url('modules/repayments/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Repayment & Collection Center</strong>
                        <span class="text-muted">Manage active loan repayments</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Overdue Delinquency (<?php echo $overdueCount; ?>)</strong>
                        <span class="text-muted">Review delinquent installments</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?php echo url('modules/repayments/payment-history.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Payment Transactions History</strong>
                        <span class="text-muted">View collection receipts & audit log</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?php echo url('modules/loans/index.php?status=approved'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Approved Loans (Pending Disbursement)</strong>
                        <span class="text-muted">Release funds & activate schedules</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <?php if (can_create_loans()): ?>
                    <a href="<?php echo url('modules/loans/create.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                        <div>
                            <strong class="d-block text-dark">New Loan Application</strong>
                            <span class="text-muted">Originate loan application</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
