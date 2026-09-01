<?php
/**
 * Master Header Layout
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';

// Fetch current user payload
$currentUser = auth_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo e($pageTitle); ?> — <?php echo e(APP_NAME); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/default-avatar.svg'); ?>">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>">
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap-icons/bootstrap-icons.min.css'); ?>">
    
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">

    <!-- Sidebar State Preload (Avoids layout shifts) -->
    <script>
        (function() {
            try {
                if (window.innerWidth >= 992 && localStorage.getItem('loan_mgt_sidebar_collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.classList.add('sidebar-collapsed');
                    });
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
<div id="app-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <div id="main-wrapper">
        <?php require_once __DIR__ . '/navbar.php'; ?>
        <main id="content-area">
            <?php display_flash(); ?>
