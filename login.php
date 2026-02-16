<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Admin Login
 * ============================================================
 */
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once 'db_connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === '' || $p === '') {
        $error = 'Enter both username and password.';
    } else {
        $st = $conn->prepare("SELECT id,username,password,full_name FROM admin_users WHERE username=? LIMIT 1");
        $st->bind_param('s', $u);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if ($row && password_verify($p, $row['password'])) {
            $_SESSION['admin_id']   = $row['id'];
            $_SESSION['admin_user'] = $row['username'];
            $_SESSION['admin_name'] = $row['full_name'];
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — PHO Palawan Leave Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
<div class="row justify-content-center align-items-center min-vh-100">
<div class="col-sm-8 col-md-5 col-lg-4">

    <div class="text-center mb-4">
        <img src="https://scontent.fmnl13-2.fna.fbcdn.net/v/t39.30808-6/239470877_366781958265636_3035717695931803955_n.jpg?_nc_cat=107&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeH65r2kvwylISgzQDbdHykxqy208_2sh0SrLbTz_ayHRGh8rkZJ4gsiUhRy8dEAVrNNYkJrTb-lYCYrs-HtLJBd&_nc_ohc=mnUECLTMsqIQ7kNvwHaKmP1&_nc_oc=Adk5dH951M3QbhLCILVP5JzqUXB_Y3HllpVaQppI3LB_xxeYkJSiMXWgjLpdZkNabsI&_nc_zt=23&_nc_ht=scontent.fmnl13-2.fna&_nc_gid=B-UCbRP8_2CNNW799nsAmA&oh=00_AfvVkIUxWUDJ50CZ1yI660nRZfkmF779ROMdV5mICCW7Nw&oe=699849A9"
             alt="PHO Palawan Logo" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
        <h5 class="fw-bold text-dark">Provincial Health Office</h5>
        <p class="text-muted small mb-0">Province of Palawan</p>
        <p class="text-muted small">Leave Management &amp; Tracking System</p>
    </div>

    <div class="card shadow border-0">
    <div class="card-body p-4">
        <h6 class="text-center mb-3 fw-bold">Admin Login</h6>
        <?php if($error): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" required autofocus
                       value="<?=htmlspecialchars($_POST['username']??'')?>"></div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required></div>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</button>
        </form>
    </div></div>
    <p class="text-center text-muted small mt-3">Default: <strong>admin</strong> / <strong>admin123</strong></p>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
