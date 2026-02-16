<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — System Reset
 * reset_db.php — Wipe test data & reset ID counters
 * ============================================================
 *
 * Clears:  leave_applications, employees
 * Keeps:   admin_users, departments
 *
 * Protected by a confirmation phrase to prevent accidental use.
 */
require_once 'db_connect.php';

$done    = false;
$error   = '';
$phrase  = 'DELETE ALL';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['confirm'] ?? '');

    if ($input !== $phrase) {
        $error = "Confirmation failed. You must type exactly: <strong>$phrase</strong>";
    } else {
        // Run the reset inside a try/catch
        try {
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");

            $conn->query("TRUNCATE TABLE leave_applications");
            $conn->query("TRUNCATE TABLE employees");

            $conn->query("SET FOREIGN_KEY_CHECKS = 1");

            $done = true;
        } catch (Exception $ex) {
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $error = 'Database error: ' . htmlspecialchars($ex->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>System Reset — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width: 540px;">

    <div class="text-center mb-4">
        <div class="mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;background:#dc3545;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-exclamation-triangle-fill text-white fs-2"></i>
        </div>
        <h4 class="fw-bold text-danger">System Reset</h4>
        <p class="text-muted small">This will permanently delete <strong>all employees</strong> and <strong>all leave records</strong>.<br>
        Your admin login and departments will be preserved.</p>
    </div>

    <?php if ($done): ?>
        <!-- ═══ Success ═══ -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;background:#198754;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-check-circle-fill text-white fs-2"></i>
                </div>
                <h5 class="fw-bold text-success">System is clean and ready for deployment!</h5>
                <p class="text-muted small mb-3">
                    <i class="bi bi-check me-1"></i>leave_applications — truncated, ID reset to 1<br>
                    <i class="bi bi-check me-1"></i>employees — truncated, ID reset to 1<br>
                    <i class="bi bi-shield-check me-1"></i>admin_users — preserved<br>
                    <i class="bi bi-shield-check me-1"></i>departments — preserved
                </p>
                <a href="index.php" class="btn btn-primary"><i class="bi bi-house me-1"></i>Go to Dashboard</a>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══ Confirmation Form ═══ -->
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i><?= $error ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-trash3 me-1 text-danger"></i>Tables to be wiped:</h6>
                <table class="table table-sm small mb-3">
                    <tr><td><i class="bi bi-x-circle text-danger me-1"></i><code>leave_applications</code></td><td class="text-danger">TRUNCATED</td></tr>
                    <tr><td><i class="bi bi-x-circle text-danger me-1"></i><code>employees</code></td><td class="text-danger">TRUNCATED</td></tr>
                    <tr class="table-light"><td><i class="bi bi-shield-check text-success me-1"></i><code>admin_users</code></td><td class="text-success">kept</td></tr>
                    <tr class="table-light"><td><i class="bi bi-shield-check text-success me-1"></i><code>departments</code></td><td class="text-success">kept</td></tr>
                </table>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Type <strong class="text-danger"><?= $phrase ?></strong> to confirm:
                        </label>
                        <input type="text" name="confirm" class="form-control" placeholder="Type here..." autocomplete="off" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-1"></i>Reset System</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel — Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
