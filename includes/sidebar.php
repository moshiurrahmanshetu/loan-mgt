<?php
/**
 * Master Sidebar Navigation Component
 * Loan Management System (loan-mgt) - Phase 6
 */

require_once __DIR__ . '/functions.php';

$activeNav = $activeNav ?? 'dashboard';
$userRole  = $_SESSION['user_role'] ?? 'loan_officer';
$canViewReports = has_role(['admin', 'manager', 'loan_officer', 'collector']);
?>
<aside id="sidebar" aria-label="Main Navigation">
    <div class="sidebar-header">
        <a href="<?php echo url('modules/dashboard/index.php'); ?>" class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-bank2"></i>
            </div>
            <span>LoanMgt</span>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-heading"><span>Main Menu</span></li>
        
        <!-- Dashboard (Active) -->
        <li class="sidebar-item <?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/dashboard/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-heading"><span>Loan Operations</span></li>
        
        <!-- Customers (Active) -->
        <li class="sidebar-item <?php echo $activeNav === 'customers' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/customers/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Customers">
                <i class="bi bi-people"></i>
                <span>Customers</span>
            </a>
        </li>

        <!-- Loan Products (Active) -->
        <li class="sidebar-item <?php echo $activeNav === 'loan-products' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Loan Products">
                <i class="bi bi-tags"></i>
                <span>Loan Products</span>
            </a>
        </li>

        <!-- Loans (Active) -->
        <li class="sidebar-item <?php echo $activeNav === 'loans' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/loans/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Loan Applications">
                <i class="bi bi-cash-stack"></i>
                <span>Loans</span>
            </a>
        </li>

        <!-- Repayments (Active) -->
        <li class="sidebar-item <?php echo $activeNav === 'repayments' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/repayments/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Repayment & Collection">
                <i class="bi bi-calendar2-check"></i>
                <span>Repayments</span>
            </a>
        </li>

        <li class="sidebar-heading"><span>Analytics & Reports</span></li>

        <!-- Reports (Active in Phase 6) -->
        <?php if ($canViewReports): ?>
            <li class="sidebar-item <?php echo $activeNav === 'reports' ? 'active' : ''; ?>">
                <a href="<?php echo url('modules/reports/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Financial & Operational Reports">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="sidebar-heading"><span>Management</span></li>

        <!-- Users Management (Future Phase Safe Placeholder) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" aria-disabled="true" data-bs-toggle="tooltip" data-bs-placement="right" title="User Management (Upcoming)">
                <i class="bi bi-person-badge"></i>
                <span>Users</span>
                <span class="sidebar-badge">Soon</span>
            </a>
        </li>

        <!-- Settings (Future Phase Safe Placeholder) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" aria-disabled="true" data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings (Upcoming)">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
                <span class="sidebar-badge">Soon</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <span><i class="bi bi-shield-check me-1 text-success"></i> <?php echo e(APP_VERSION); ?></span>
        </div>
    </div>
</aside>
