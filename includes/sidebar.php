<?php
/**
 * Master Sidebar Navigation Component
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/functions.php';

$activeNav = $activeNav ?? 'dashboard';
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
        
        <!-- Dashboard (Active in Phase 1) -->
        <li class="sidebar-item <?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/dashboard/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-heading"><span>Loan Operations</span></li>
        
        <!-- Customers (Active in Phase 2) -->
        <li class="sidebar-item <?php echo $activeNav === 'customers' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/customers/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Customers">
                <i class="bi bi-people"></i>
                <span>Customers</span>
            </a>
        </li>

        <!-- Loan Products (Active in Phase 3) -->
        <li class="sidebar-item <?php echo $activeNav === 'loan-products' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Loan Products">
                <i class="bi bi-tags"></i>
                <span>Loan Products</span>
            </a>
        </li>

        <!-- Loans (Active in Phase 3) -->
        <li class="sidebar-item <?php echo $activeNav === 'loans' ? 'active' : ''; ?>">
            <a href="<?php echo url('modules/loans/index.php'); ?>" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Loan Applications">
                <i class="bi bi-cash-stack"></i>
                <span>Loans</span>
            </a>
        </li>

        <!-- Repayments (Phase 4 Coming Soon) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="Repayments (Phase 4)">
                <i class="bi bi-calendar2-check"></i>
                <span>Repayments</span>
                <span class="sidebar-badge">Soon</span>
            </a>
        </li>

        <li class="sidebar-heading"><span>Management</span></li>

        <!-- Reports (Future Phase) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="Reports (Upcoming)">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reports</span>
                <span class="sidebar-badge">Soon</span>
            </a>
        </li>

        <!-- Users Management (Future Phase) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="User Management (Upcoming)">
                <i class="bi bi-person-badge"></i>
                <span>Users</span>
                <span class="sidebar-badge">Soon</span>
            </a>
        </li>

        <!-- Settings (Future Phase) -->
        <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings (Upcoming)">
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
