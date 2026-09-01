<?php
/**
 * Master Footer Layout
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/functions.php';
?>
        </main>
        <footer id="main-footer">
            <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    <span>&copy; <?php echo date('Y'); ?> <strong><?php echo e(APP_NAME); ?></strong>. All rights reserved.</span>
                </div>
                <div class="text-muted small">
                    <span>Phase 1 — Project Foundation & Auth</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>

<!-- Application JS -->
<script src="<?php echo asset('js/app.js'); ?>"></script>

</body>
</html>
