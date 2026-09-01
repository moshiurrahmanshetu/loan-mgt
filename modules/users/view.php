<?php
/**
 * User Account Profile Details View
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'User Profile Details';
$activeNav = 'users';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('users.view');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    set_flash('danger', 'Invalid user account requested.');
    redirect('modules/users/index.php');
}

$db = get_db_connection();

// 2. Fetch User Record with Role Information
$stmt = $db->prepare('
    SELECT u.*, 
           r.id AS role_id, r.name AS role_name, r.slug AS role_slug, r.description AS role_description, r.is_system AS role_is_system
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.id = :id
    LIMIT 1
');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

$currentId = auth_id();
$isSelf    = ($userId === (int)$currentId);
$roleSlug  = $user['role_slug'] ?? $user['role'];
$roleName  = $user['role_name'] ?? get_role_label($roleSlug);
$avatarUrl = get_avatar_url($user['avatar'] ?? null, $user['name']);

// 3. Fetch User's Role Permissions List
$permStmt = $db->prepare("
    SELECT p.name, p.slug, p.module
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = :rid
    ORDER BY p.module ASC, p.name ASC
");
$permStmt->execute([':rid' => $user['role_id']]);
$userPermissions = $permStmt->fetchAll();

// 4. Activity Statistics
$originatedLoansCount = (int)$db->query("SELECT COUNT(*) FROM loans WHERE created_by = {$userId}")->fetchColumn();
$approvedLoansCount   = (int)$db->query("SELECT COUNT(*) FROM loans WHERE approved_by = {$userId}")->fetchColumn();
$collectedPaymentsSum = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE collected_by = {$userId}")->fetchColumn();
$collectedCount       = (int)$db->query("SELECT COUNT(*) FROM loan_payments WHERE collected_by = {$userId}")->fetchColumn();

$canEdit   = has_permission('users.edit');
$canDelete = has_permission('users.delete');

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/index.php'); ?>" class="text-decoration-none text-muted">User Management</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($user['name']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0"><?php echo e($user['name']); ?></h2>
            <span class="badge badge-role badge-role-<?php echo e($roleSlug); ?>"><?php echo e($roleName); ?></span>
            <?php if ($user['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
            <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <?php if ($canEdit): ?>
            <a href="<?php echo url('modules/users/edit.php?id=' . $user['id']); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Account
            </a>
            <a href="<?php echo url('modules/users/change-password.php?id=' . $user['id']); ?>" class="btn btn-warning btn-sm text-dark">
                <i class="bi bi-shield-lock me-1"></i> Reset Password
            </a>
            <?php if (!$isSelf): ?>
                <form action="<?php echo url('modules/users/toggle-status.php'); ?>" method="POST" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                    <button type="submit" class="btn btn-outline-<?php echo $user['status'] === 'active' ? 'danger' : 'success'; ?> btn-sm">
                        <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?> me-1"></i>
                        <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: User Profile Overview Card -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-4 text-center">
            <div class="card-body p-4">
                <div class="mb-3">
                    <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($user['name']); ?>" class="rounded-circle shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <h3 class="h5 fw-bold text-dark mb-0"><?php echo e($user['name']); ?></h3>
                <div class="text-muted font-monospace small mb-2">@<?php echo e($user['username'] ?: 'user_' . $user['id']); ?></div>
                <div class="mb-3">
                    <span class="badge badge-role badge-role-<?php echo e($roleSlug); ?> px-3 py-1.5"><?php echo e($roleName); ?></span>
                </div>

                <div class="text-start border-top pt-3 small">
                    <div class="d-flex justify-content-between py-1.5 border-bottom">
                        <span class="text-muted">Email Address:</span>
                        <strong class="text-dark"><?php echo e($user['email']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1.5 border-bottom">
                        <span class="text-muted">Phone Number:</span>
                        <strong class="text-dark font-monospace"><?php echo e($user['phone'] ?: '—'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1.5 border-bottom">
                        <span class="text-muted">Account Status:</span>
                        <span>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-1.5 border-bottom">
                        <span class="text-muted">Registered On:</span>
                        <strong class="text-dark"><?php echo format_date($user['created_at']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1.5">
                        <span class="text-muted">Last Active:</span>
                        <strong class="text-dark"><?php echo !empty($user['last_login']) ? format_date($user['last_login'], 'M d, Y g:i A') : 'Never'; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Ledger Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i> Staff Activity Ledger</h4>
            </div>
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div>
                        <div class="fw-semibold text-dark small">Originated Applications</div>
                        <span class="text-muted" style="font-size: 0.75rem;">Loans authored</span>
                    </div>
                    <span class="h6 fw-bold text-primary font-monospace mb-0"><?php echo number_format($originatedLoansCount); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div>
                        <div class="fw-semibold text-dark small">Underwriting Approvals</div>
                        <span class="text-muted" style="font-size: 0.75rem;">Authorized credit reviews</span>
                    </div>
                    <span class="h6 fw-bold text-info font-monospace mb-0"><?php echo number_format($approvedLoansCount); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2">
                    <div>
                        <div class="fw-semibold text-dark small">Collections Realized</div>
                        <span class="text-muted" style="font-size: 0.75rem;"><?php echo number_format($collectedCount); ?> receipts collected</span>
                    </div>
                    <span class="h6 fw-bold text-success font-monospace mb-0"><?php echo format_currency($collectedPaymentsSum); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Assigned Role & Permissions Matrix -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i> Assigned Role & Authorization Permissions</h3>
                    <p class="small text-muted mb-0">Permissions granted via the <strong><?php echo e($roleName); ?></strong> role.</p>
                </div>
                <?php if (has_permission('roles.edit') && !empty($user['role_id'])): ?>
                    <a href="<?php echo url('modules/roles/permissions.php?id=' . $user['role_id']); ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear me-1"></i> Edit Role Permissions
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($roleSlug === 'admin'): ?>
                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="bi bi-shield-fill-check fs-4 me-3 text-info"></i>
                        <div>
                            <strong class="d-block">Full System Administrative Authority</strong>
                            <span class="small">The Administrator role has unrestricted access to all 29 system permissions across all modules.</span>
                        </div>
                    </div>
                <?php elseif (!empty($userPermissions)): ?>
                    <?php
                    // Group permissions by module
                    $grouped = [];
                    foreach ($userPermissions as $p) {
                        $grouped[$p['module']][] = $p;
                    }
                    ?>
                    <div class="row g-3">
                        <?php foreach ($grouped as $moduleName => $perms): ?>
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h4 class="h6 fw-bold text-dark border-bottom pb-2 mb-2 d-flex align-items-center justify-content-between">
                                        <span><?php echo e($moduleName); ?></span>
                                        <span class="badge bg-white text-dark border"><?php echo count($perms); ?></span>
                                    </h4>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($perms as $perm): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small py-1.5 px-2">
                                                <i class="bi bi-check2 me-1"></i> <?php echo e($perm['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-shield-x fs-3 d-block mb-2 text-muted"></i>
                        <p class="small mb-0">No permissions are currently associated with this role.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
