<?php
/**
 * Edit User Account Form
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Edit User Account';
$activeNav = 'users';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('users.edit');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    set_flash('danger', 'Invalid user account ID.');
    redirect('modules/users/index.php');
}

$db = get_db_connection();

// 2. Fetch User Record
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

// 3. Fetch Active Roles
$roles = $db->query("SELECT id, name, slug, description FROM roles WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$currentId = auth_id();
$isSelf    = ($userId === (int)$currentId);
$avatarUrl = get_avatar_url($user['avatar'] ?? null, $user['name']);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/index.php'); ?>" class="text-decoration-none text-muted">User Management</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="text-decoration-none text-muted"><?php echo e($user['name']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Account</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Edit User Account</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i> View Profile
        </a>
        <a href="<?php echo url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Users Directory
        </a>
    </div>
</div>

<?php if ($isSelf): ?>
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>
            <strong>Self-Account Notice:</strong> You are editing your currently authenticated profile. Your role and account status cannot be deactivated from this view.
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i> Modify Staff Account Details</h3>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/users/update.php'); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">1. Personal & Contact Information</h4>
                        </div>

                        <!-- Full Name -->
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required value="<?php echo e($user['name']); ?>">
                        </div>

                        <!-- Username -->
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" name="username" id="username" class="form-control" required value="<?php echo e($user['username'] ?: 'user_' . $user['id']); ?>" pattern="^[a-zA-Z0-9_\.]{3,30}$" title="3-30 characters, letters, numbers, underscores and periods only">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required value="<?php echo e($user['email']); ?>">
                        </div>

                        <!-- Phone -->
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo e($user['phone'] ?? ''); ?>" placeholder="e.g. +1 (555) 234-5678">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">2. Role Assignment & Account Status</h4>
                        </div>

                        <!-- Assigned Role -->
                        <div class="col-12 col-md-6">
                            <label for="role_id" class="form-label small fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-select" required <?php echo $isSelf ? 'disabled' : ''; ?>>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo (int)$r['id']; ?>" <?php echo (int)$user['role_id'] === (int)$r['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($r['name']); ?> (<?php echo e($r['slug']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isSelf): ?>
                                <input type="hidden" name="role_id" value="<?php echo (int)$user['role_id']; ?>">
                                <div class="form-text text-muted">You cannot change your own administrative role.</div>
                            <?php else: ?>
                                <div class="form-text">Controls system permissions and authorization for this account.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Account Status -->
                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label small fw-semibold">Account Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required <?php echo $isSelf ? 'disabled' : ''; ?>>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active (Can Authenticate)</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Access Blocked)</option>
                            </select>
                            <?php if ($isSelf): ?>
                                <input type="hidden" name="status" value="active">
                                <div class="form-text text-muted">You cannot deactivate your own active session.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">3. Profile Avatar Photo</h4>
                        </div>

                        <!-- Current Avatar Preview -->
                        <div class="col-12 col-md-3 text-center">
                            <label class="form-label small fw-semibold d-block">Current Avatar</label>
                            <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($user['name']); ?>" class="rounded-circle shadow-sm border" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>

                        <!-- Avatar Upload -->
                        <div class="col-12 col-md-9">
                            <label for="avatar" class="form-label small fw-semibold">Replace Photo (Optional)</label>
                            <input type="file" name="avatar" id="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Allowed formats: JPG, PNG, WEBP. Max size: 2MB. Leave blank to keep current photo.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?php echo url('modules/users/change-password.php?id=' . $user['id']); ?>" class="btn btn-outline-warning text-dark btn-sm">
                            <i class="bi bi-shield-lock me-1"></i> Reset Password
                        </a>
                        <div class="d-flex gap-2">
                            <a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
