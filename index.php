<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Admin Dashboard
 * ============================================================
 * Shows: statistics, today's on-leave, low-credit alerts,
 *        and the most recent leave applications.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

$today = date('Y-m-d');

/* ── Summary Stats ─────────────────────────────────────────── */
$totalEmp   = (int) $conn->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
$totalApps  = (int) $conn->query("SELECT COUNT(*) c FROM leave_applications")->fetch_assoc()['c'];
$pendingCt  = (int) $conn->query("SELECT COUNT(*) c FROM leave_applications WHERE status='Pending'")->fetch_assoc()['c'];

// On leave today
$sqlToday = "SELECT la.*, e.title, e.first_name, e.middle_name, e.last_name, e.suffix,
                    e.position, e.department
             FROM leave_applications la
             JOIN employees e ON e.id=la.employee_id
             WHERE ? BETWEEN la.start_date AND la.end_date
               AND la.status IN ('Pending','Approved')
             ORDER BY e.last_name, e.first_name";
$stToday = $conn->prepare($sqlToday);
$stToday->bind_param('s', $today);
$stToday->execute();
$onLeave = $stToday->get_result();
$onLeaveCt = $onLeave->num_rows;

// Recent 15 applications
$recent = $conn->query(
    "SELECT la.*, e.title, e.first_name, e.middle_name, e.last_name, e.suffix,
            e.department
     FROM leave_applications la
     JOIN employees e ON e.id=la.employee_id
     ORDER BY la.date_filed DESC LIMIT 15"
);

/* ── Handle quick Approve / Disapprove from dashboard ──────── */
if (isset($_GET['approve'])) {
    $aid = (int)$_GET['approve'];
    $conn->query("UPDATE leave_applications SET status='Approved' WHERE id=$aid");
    setFlash('success','Leave application #'.$aid.' approved.');
    header('Location: index.php'); exit;
}
if (isset($_GET['disapprove'])) {
    $did = (int)$_GET['disapprove'];
    $conn->query("UPDATE leave_applications SET status='Disapproved' WHERE id=$did");
    setFlash('warning','Leave application #'.$did.' disapproved.');
    header('Location: index.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — PHO Palawan Leave Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container-fluid py-4">
<?= renderFlash() ?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
        <small class="text-muted">Provincial Health Office — Palawan &middot; <?= date('F j, Y') ?></small>
    </div>
    <a href="encode_leave.php" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Encode Leave (CS Form 6)</a>
</div>

<!-- ═══ Stat Cards ═══ -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-muted small text-uppercase fw-semibold">Active Employees</div><div class="fs-3 fw-bold"><?=$totalEmp?></div></div>
                <span class="stat-icon bg-primary text-white"><i class="bi bi-people"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm border-start border-4 border-warning">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-muted small text-uppercase fw-semibold">On Leave Today</div><div class="fs-3 fw-bold"><?=$onLeaveCt?></div></div>
                <span class="stat-icon bg-warning text-white"><i class="bi bi-calendar-x"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm border-start border-4 border-info">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-muted small text-uppercase fw-semibold">Pending Applications</div><div class="fs-3 fw-bold"><?=$pendingCt?></div></div>
                <span class="stat-icon bg-info text-white"><i class="bi bi-hourglass-split"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm border-start border-4 border-success">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-muted small text-uppercase fw-semibold">Total Applications</div><div class="fs-3 fw-bold"><?=$totalApps?></div></div>
                <span class="stat-icon bg-success text-white"><i class="bi bi-journal-text"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

<!-- ═══ On Leave Today ═══ -->
<div class="col-12">
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-x text-warning me-2"></i>On Leave Today</h6>
        <span class="badge bg-warning text-dark"><?=$onLeaveCt?></span>
    </div>
    <div class="card-body p-0">
    <?php if($onLeaveCt): ?>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-dark"><tr><th>Employee</th><th>Dept</th><th>Type</th><th>Period</th></tr></thead>
        <tbody>
        <?php $onLeave->data_seek(0); while($r=$onLeave->fetch_assoc()): ?>
        <tr>
            <td class="fw-semibold"><?=h(fullName($r))?></td>
            <td class="small text-muted"><?=h($r['department']??'')?></td>
            <td><span class="badge bg-<?=leaveTypeBadge($r['leave_type'])?>"><?=h($r['leave_type'])?></span></td>
            <td class="small"><?=date('M d',strtotime($r['start_date']))?> – <?=date('M d',strtotime($r['end_date']))?></td>
        </tr>
        <?php endwhile; ?>
        </tbody></table></div>
    <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>No one on leave today.</div>
    <?php endif; ?>
    </div>
</div>
</div>
</div><!-- /row -->

<!-- ═══ Recent Applications ═══ -->
<div class="card shadow-sm border-0 mt-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Recent Leave Applications</h6>
    <a href="leave_history.php" class="btn btn-sm btn-outline-primary">View All</a>
</div>
<div class="card-body p-0">
<div class="table-responsive"><table class="table table-sm table-hover table-striped mb-0">
<thead class="table-dark"><tr>
    <th>#</th><th>Employee</th><th>Leave Type</th><th>Dates</th><th class="text-center">Days</th>
    <th class="text-center">Status</th><th class="text-center">Actions</th>
</tr></thead>
<tbody>
<?php if($recent->num_rows===0): ?>
    <tr><td colspan="7" class="text-center py-4 text-muted">No leave applications yet. <a href="encode_leave.php">Encode the first one.</a></td></tr>
<?php else: $n=1; while($r=$recent->fetch_assoc()): ?>
    <tr>
        <td class="text-muted"><?=$n++?></td>
        <td class="fw-semibold"><?=h(fullName($r))?></td>
        <td><span class="badge bg-<?=leaveTypeBadge($r['leave_type'])?>"><?=h($r['leave_type'])?></span></td>
        <td class="small"><?=date('M d',strtotime($r['start_date']))?> – <?=date('M d, Y',strtotime($r['end_date']))?></td>
        <td class="text-center"><span class="badge bg-dark"><?=$r['working_days']?></span></td>
        <td class="text-center"><span class="badge bg-<?=statusBadge($r['status'])?>"><?=h($r['status'])?></span></td>
        <td class="text-center text-nowrap">
            <?php if($r['status']==='Pending'): ?>
                <a href="index.php?approve=<?=$r['id']?>" class="btn btn-sm btn-outline-success" title="Approve"
                   onclick="return confirm('Approve this leave?')"><i class="bi bi-check-lg"></i></a>
                <a href="index.php?disapprove=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" title="Disapprove"
                   onclick="return confirm('Disapprove this leave?')"><i class="bi bi-x-lg"></i></a>
            <?php else: ?>
                <span class="text-muted small">—</span>
            <?php endif; ?>
            <a href="view_leave.php?id=<?=$r['id']?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
        </td>
    </tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</div></div>

</div><!-- /container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
