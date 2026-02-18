<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Reset Leave Records
 * ============================================================
 * Truncates leave_applications table (resets ID to 1).
 * Does NOT touch the employees table.
 */
require_once 'auth.php';
require_once 'db_connect.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $result = $conn->query("TRUNCATE TABLE leave_applications");
    if ($result) {
        $success = true;
    } else {
        $error = 'Reset failed: ' . $conn->error;
    }
}

$count = (int) $conn->query("SELECT COUNT(*) c FROM leave_applications")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Leave Records — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px">

    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white text-center">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Leave Records</h5>
        </div>
        <div class="card-body text-center">

            <?php if ($success): ?>
                <div class="alert alert-success py-3">
                    <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
                    <strong>All leave records have been cleared.</strong><br>
                    <span class="small text-muted">The ID counter has been reset to 1. Your 49 employees are untouched.</span>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="mb-4">
                    <i class="bi bi-database-x text-danger d-block" style="font-size:3rem"></i>
                    <p class="mt-3 mb-1">This will <strong>permanently delete all <?= $count ?> leave record(s)</strong> from the <code>leave_applications</code> table.</p>
                    <p class="text-muted small mb-0">The <code>employees</code> table will NOT be affected.</p>
                </div>
                <form method="POST">
                    <button type="submit" name="confirm_reset" class="btn btn-danger btn-lg"
                            onclick="return confirm('⚠️ Are you sure you want to DELETE ALL leave records? This cannot be undone!')">
                        <i class="bi bi-trash3-fill me-2"></i>Reset All Leave Records
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-4">
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
            </div>
        </div>
    </div>

</div>
</body>
</html>
