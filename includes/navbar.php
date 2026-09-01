<?php
/**
 * Master Top Navbar Component
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/functions.php';

$currentUser = auth_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$avatarUrl = get_avatar_url($currentUser['avatar'] ?? null, $currentUser['name'] ?? 'User');
$roleLabel = get_role_label($currentUser['role'] ?? 'loan_officer');
?>
<header id="top-navbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center p-2" id="sidebarToggle" aria-label="Toggle Sidebar" title="Toggle Navigation Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <h1 class="navbar-page-title"><?php echo e($pageTitle); ?></h1>
    </div>

    <div class="d-flex align-items-center">
        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="user-nav-dropdown dropdown-toggle d-flex align-items-center gap-2" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($currentUser['name'] ?? 'Avatar'); ?>" class="avatar-img">
                <div class="user-nav-info d-none d-md-flex">
                    <span class="user-nav-name"><?php echo e($currentUser['name'] ?? 'User'); ?></span>
                    <span class="user-nav-role"><?php echo e($roleLabel); ?></span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuDropdown">
                <li class="px-3 py-2 border-bottom d-md-none">
                    <div class="fw-semibold text-dark"><?php echo e($currentUser['name'] ?? 'User'); ?></div>
                    <div class="small text-muted"><?php echo e($roleLabel); ?></div>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo url('modules/profile/index.php'); ?>">
                        <i class="bi bi-person me-2 text-primary"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo url('modules/profile/index.php#password-section'); ?>">
                        <i class="bi bi-shield-lock me-2 text-warning"></i>
                        <span>Change Password</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center text-danger" href="<?php echo url('auth/logout.php'); ?>">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
