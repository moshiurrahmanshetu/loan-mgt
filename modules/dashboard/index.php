<?php
/**
 * Dashboard View
 * Loan Management System (loan-mgt) - Phase 3
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

// Fetch Phase 3 Loan and Product summary metrics
$totalLoansStmt = $db->query('SELECT COUNT(*) FROM loans');
$totalLoans = (int)$totalLoansStmt->fetchColumn();

$pendingLoansStmt = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'");
$pendingLoans = (int)$pendingLoansStmt->fetchColumn();

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

    <!-- Total Loans Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Loan Applications</span>
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalLoans); ?></div>
                <div class="small text-muted">Total originated applications</div>
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

    <!-- Active Products Card -->
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
                <span class="badge bg-primary px-2.5 py-1.5">Phase 3 Active</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">
                    Welcome to the <strong>Loan Management System (loan-mgt)</strong>. The system is currently running <strong>Phase 3: Loan Products & Loan Application Management</strong>.
                </p>
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Underwriting & Governance Summary</div>
                    <p class="text-muted small mb-0">
                        Loan product rules, application origination, and underwriting approval/rejection workflows are live with self-approval segregation controls. Loan disbursement, installment schedules, and repayment collections are scheduled for subsequent phases.
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
                            <span>Underwriting Approval & Rejection Workflow (Phase 3)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Self-Approval Prevention & Segregation of Duties</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts Card -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning fs-5"></i>
                    <h3 class="h6 mb-0 fw-bold">Quick Actions</h3>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="list-group list-group-flush">
                    <?php if (can_create_loans()): ?>
                        <a href="<?php echo url('modules/loans/create.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-plus-circle text-primary fs-5"></i>
                                <div>
                                    <div class="fw-semibold text-dark small">New Loan Application</div>
                                    <div class="text-muted small">Originate borrower credit</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo url('modules/loans/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-success fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">Loan Portfolio</div>
                                <div class="text-muted small">View, review & filter applications</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-tags text-info fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">Loan Products</div>
                                <div class="text-muted small">Lending templates & interest rules</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="<?php echo url('modules/customers/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people text-secondary fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">Customer Portfolio</div>
                                <div class="text-muted small">Manage registered borrowers</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
