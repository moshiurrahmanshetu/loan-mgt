/**
 * Loan Management System (loan-mgt) - Application JavaScript
 * Phase 1: Sidebar Toggle, LocalStorage Persistence, Mobile Navigation & Tooltips
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'loan_mgt_sidebar_collapsed';

    /**
     * Restore saved sidebar state from localStorage for desktop devices.
     */
    function initSidebarState() {
        if (window.innerWidth >= 992) {
            const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
            if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    }

    // Execute state restore immediately to prevent layout flickering
    initSidebarState();

    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        // Ensure backdrop element exists for mobile overlay
        let backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        // Toggle sidebar handler
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.innerWidth < 992) {
                    // Mobile behavior: toggle mobile offcanvas drawer
                    document.body.classList.toggle('sidebar-mobile-open');
                } else {
                    // Desktop behavior: toggle compact icon-only mode & persist
                    document.body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? 'true' : 'false');
                }
            });
        }

        // Close mobile sidebar on backdrop click
        backdrop.addEventListener('click', function () {
            document.body.classList.remove('sidebar-mobile-open');
        });

        // Close mobile sidebar when Escape key is pressed
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-mobile-open')) {
                document.body.classList.remove('sidebar-mobile-open');
            }
        });

        // Handle window resize cleanly
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                document.body.classList.remove('sidebar-mobile-open');
                const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
                if (isCollapsed) {
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        });

        // Initialize Bootstrap 5 Tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    container: 'body'
                });
            });
        }

        // Auto-hide alert messages after 5 seconds if desired
        const autoDismissAlerts = document.querySelectorAll('.alert-dismissible:not(.alert-persistent)');
        if (autoDismissAlerts.length > 0 && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            setTimeout(function () {
                autoDismissAlerts.forEach(function (alertEl) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                });
            }, 6000);
        }
    });
})();
