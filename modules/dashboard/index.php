<?php
/**
 * Dashboard View
 * Loan Management System (loan-mgt) - Phase 4
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

// Fetch Phase 2 Customer summary metrics
$totalCustStmt = $db->query('SELECT COUNT(*) FROM customers');
$totalCustomers = (int)$totalCustStmt->fetchColumn();

// Fetch Phase 3 & 4 Loan metrics
$totalLoansStmt = $db->query('SELECT COUNT(*) FROM loans');
$totalLoans = (int)$totalLoansStmt->fetchColumn();

$pendingLoansStmt = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'");
$pendingLoans = (int)$pendingLoansStmt->fetchColumn();

$activeLoansStmt = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'");
$activeLoans = (int)$activeLoansStmt->fetchColumn();

$disbursedVolumeStmt = $db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status = 'active'");
$disbursedVolume = (float)$disbursedVolumeStmt->fetchColumn();

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
    <!-- Total Customers Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Borrowers</span>
                    <i class="bi bi-people-fill text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalCustomers); ?></div>
                <div class="small text-muted">Registered customer profiles</div>
            </div>
        </div>
    </div>

    <!-- Active Disbursed Loans Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Portfolio</span>
                    <i class="bi bi-check2-circle text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-success"><?php echo number_format($activeLoans); ?> Active</div>
                <div class="small text-muted">Disbursed: <?php echo format_currency($disbursedVolume); ?></div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Pending Review</span>
                    <span class="badge badge-status-pending">Underwriting</span>
                </div>
                <div class="h4 mb-1 fw-bold text-warning"><?php echo number_format($pendingLoans); ?></div>
                <div class="small text-muted">Awaiting credit decision</div>
            </div>
        </div>
    </div>

    <!-- Loan Products Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Products</span>
                    <i class="bi bi-tags-fill text-info fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($activeProducts); ?></div>
                <div class="small text-muted">Available loan templates</div>
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
                <span class="badge bg-primary px-2.5 py-1.5">Phase 4 Active</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">
                    Welcome to the <strong>Loan Management System (loan-mgt)</strong>. The system is currently running <strong>Phase 4: Loan Disbursement & Repayment Schedule</strong>.
                </p>
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Underwriting & Disbursement Summary</div>
                    <p class="text-muted small mb-0">
                        Loan origination, underwriting approval workflows, atomic loan disbursement with concurrency locking, and mathematical repayment schedule generation with exact cent rounding are fully operational. Payment collections and overdue tracking are scheduled for subsequent phases.
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
                            <span>Installment Amortization Views (Phase 4)</span>
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
                <?php if (can_create_loans()): ?>
                    <a href="<?php echo url('modules/loans/create.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                        <div>
                            <strong class="d-block text-dark">New Loan Application</strong>
                            <span class="text-muted">Originate loan with live calculation</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>

                <a href="<?php echo url('modules/loans/index.php?status=approved'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Approved Loans (Ready for Disbursement)</strong>
                        <span class="text-muted">Process payment release & schedules</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?php echo url('modules/loans/index.php?status=active'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Active Loan Portfolio</strong>
                        <span class="text-muted">View active loans and repayment schedules</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?php echo url('modules/customers/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong class="d-block text-dark">Customer Directory</strong>
                        <span class="text-muted">Browse customer KYC records</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <?php if (can_manage_loan_products()): ?>
                    <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3">
                        <div>
                            <strong class="d-block text-dark">Loan Products Catalog</strong>
                            <span class="text-muted">Manage product parameters & limits</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
