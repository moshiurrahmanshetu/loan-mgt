<?php
/**
 * Dashboard View
 * Loan Management System (loan-mgt) - Phase 1
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

// Fetch customer summary metrics for Phase 2
$totalCustStmt = $db->query('SELECT COUNT(*) FROM customers');
$totalCustomers = (int)$totalCustStmt->fetchColumn();

$activeCustStmt = $db->query("SELECT COUNT(*) FROM customers WHERE status = 'active'");
$activeCustomers = (int)$activeCustStmt->fetchColumn();

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
                    You are logged in to the <strong><?php echo e(APP_NAME); ?></strong> administrative portal.
                </p>
            </div>
            <div class="text-md-end">
                <span class="small text-muted d-block">Last Login Session</span>
                <span class="fw-semibold text-dark small"><i class="bi bi-clock-history me-1 text-primary"></i> <?php echo e($lastLoginDisplay); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- System & Customer Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Total Customers Card (Phase 2 Metric) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Borrowers</span>
                    <i class="bi bi-people-fill text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalCustomers); ?></div>
                <div class="small text-muted">Registered customer profiles</div>
            </div>
        </div>
    </div>

    <!-- Active Customers Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Customers</span>
                    <span class="badge badge-status-active">Active</span>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($activeCustomers); ?></div>
                <div class="small text-muted">Eligible for loan issuance</div>
            </div>
        </div>
    </div>

    <!-- User Role Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Your Access Level</span>
                    <i class="bi bi-person-badge text-primary fs-5"></i>
                </div>
                <div class="h5 mb-1 fw-bold text-dark"><?php echo e($roleLabel); ?></div>
                <div class="small text-muted">Customer & security rules applied</div>
            </div>
        </div>
    </div>

    <!-- Session Guard Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Session Guard</span>
                    <i class="bi bi-shield-lock-fill text-primary fs-5"></i>
                </div>
                <div class="h5 mb-1 fw-bold text-dark">Protected</div>
                <div class="small text-muted">CSRF & Session lock active</div>
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
                <span class="badge bg-primary px-2.5 py-1.5">Phase 2 Active</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">
                    Welcome to the <strong>Loan Management System (loan-mgt)</strong>. The system is currently running <strong>Phase 2: Customer Management Module</strong>.
                </p>
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Roadmap Notice</div>
                    <p class="text-muted small mb-0">
                        Borrower profiles, contact registries, and emergency contact verifications are now live. Loan Products, Loan Origination, Approval Workflows, and Repayment Schedules will be enabled in subsequent modular phases.
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
                            <span>Admin Profile & Password Security (Phase 1)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Customer Profile CRUD & Server Search (Phase 2)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Customer Photo Sandbox with .htaccess (Phase 2)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Sequential Code Generator (CUS-XXXXXX)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Role-Based Access Enforcement (Admin/Mgr/Officer/Collector)</span>
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
                    <a href="<?php echo url('modules/customers/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people text-primary fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">Customer Portfolio</div>
                                <div class="text-muted small">View, filter & search borrowers</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <?php if (can_manage_customers()): ?>
                        <a href="<?php echo url('modules/customers/create.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-plus text-success fs-5"></i>
                                <div>
                                    <div class="fw-semibold text-dark small">Add Customer</div>
                                    <div class="text-muted small">Register new borrower</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo url('modules/profile/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-primary fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">My Profile</div>
                                <div class="text-muted small">Update administrator details</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="<?php echo url('auth/logout.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2.5 text-danger">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-right text-danger fs-5"></i>
                            <div>
                                <div class="fw-semibold small">End Session</div>
                                <div class="text-muted small">Logout safely</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
