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
$sqlToday = "SELECT la.*, e.employee_name, e.position, d.name AS dept
             FROM leave_applications la
             JOIN employees e ON e.id=la.employee_id
             LEFT JOIN departments d ON d.id=e.department_id
             WHERE ? BETWEEN la.date_start AND la.date_end
               AND la.status IN ('Pending','Approved')
             ORDER BY e.employee_name";
$stToday = $conn->prepare($sqlToday);
$stToday->bind_param('s', $today);
$stToday->execute();
$onLeave = $stToday->get_result();
$onLeaveCt = $onLeave->num_rows;

// Low VL (≤3)
$lowVL = $conn->query("SELECT employee_name, vacation_leave_balance AS bal FROM employees WHERE vacation_leave_balance<=3 AND status='Active' ORDER BY vacation_leave_balance ASC");

// Low SL (≤3)
$lowSL = $conn->query("SELECT employee_name, sick_leave_balance AS bal FROM employees WHERE sick_leave_balance<=3 AND status='Active' ORDER BY sick_leave_balance ASC");

// Recent 15 applications
$recent = $conn->query(
    "SELECT la.*, e.employee_name, d.name AS dept
     FROM leave_applications la
     JOIN employees e ON e.id=la.employee_id
     LEFT JOIN departments d ON d.id=e.department_id
     ORDER BY la.created_at DESC LIMIT 15"
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
    // When disapproving, restore previously deducted credits
    $laRow = $conn->query("SELECT employee_id, charge_to, days_deducted FROM leave_applications WHERE id=$did AND status='Pending'")->fetch_assoc();
    if ($laRow && $laRow['days_deducted'] > 0) {
        $col = $laRow['charge_to'] === 'Sick Leave' ? 'sick_leave_balance' : 'vacation_leave_balance';
        if ($laRow['charge_to'] !== 'Leave Without Pay') {
            $conn->query("UPDATE employees SET $col = $col + {$laRow['days_deducted']} WHERE id={$laRow['employee_id']}");
        }
    }
    $conn->query("UPDATE leave_applications SET status='Disapproved', days_deducted=0 WHERE id=$did");
    setFlash('warning','Leave application #'.$did.' disapproved. Credits restored.');
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
<div class="col-lg-6">
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-x text-warning me-2"></i>On Leave Today</h6>
        <span class="badge bg-warning text-dark"><?=$onLeaveCt?></span>
    </div>
    <div class="card-body p-0">
    <?php if($onLeaveCt): ?>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>Employee</th><th>Dept</th><th>Type</th><th>Period</th></tr></thead>
        <tbody>
        <?php $onLeave->data_seek(0); while($r=$onLeave->fetch_assoc()): ?>
        <tr>
            <td class="fw-semibold"><?=h($r['employee_name'])?></td>
            <td class="small text-muted"><?=h($r['dept']??'')?></td>
            <td><span class="badge bg-<?=leaveTypeBadge($r['leave_type'])?>"><?=h($r['leave_type'])?></span></td>
            <td class="small"><?=date('M d',strtotime($r['date_start']))?> – <?=date('M d',strtotime($r['date_end']))?></td>
        </tr>
        <?php endwhile; ?>
        </tbody></table></div>
    <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>No one on leave today.</div>
    <?php endif; ?>
    </div>
</div>
</div>

<!-- ═══ Low Credit Alerts ═══ -->
<div class="col-lg-6">
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Low Credit Alerts (&le; 3 days)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>Employee</th><th class="text-end">VL Balance</th><th class="text-end">SL Balance</th></tr></thead>
        <tbody>
        <?php
        // Merge both lists into one set keyed by name
        $alerts = [];
        $lowVL->data_seek(0);
        while($r=$lowVL->fetch_assoc()) $alerts[$r['employee_name']]['vl'] = $r['bal'];
        $lowSL->data_seek(0);
        while($r=$lowSL->fetch_assoc()) $alerts[$r['employee_name']]['sl'] = $r['bal'];
        if(empty($alerts)): ?>
            <tr><td colspan="3" class="text-center py-4 text-muted"><i class="bi bi-shield-check text-success me-1"></i>All employees have sufficient credits.</td></tr>
        <?php else:
            ksort($alerts);
            foreach($alerts as $name=>$b):
                $vl = $b['vl'] ?? null;
                $sl = $b['sl'] ?? null;
        ?>
        <tr>
            <td class="fw-semibold"><?=h($name)?></td>
            <td class="text-end <?= $vl!==null && $vl<=3 ? ($vl<=0?'text-danger fw-bold':'text-warning fw-bold') : ''?>">
                <?= $vl!==null ? number_format($vl,3) : '—' ?>
            </td>
            <td class="text-end <?= $sl!==null && $sl<=3 ? ($sl<=0?'text-danger fw-bold':'text-warning fw-bold') : ''?>">
                <?= $sl!==null ? number_format($sl,3) : '—' ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
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
    <th>Charged To</th><th class="text-center">Status</th><th class="text-center">Actions</th>
</tr></thead>
<tbody>
<?php if($recent->num_rows===0): ?>
    <tr><td colspan="8" class="text-center py-4 text-muted">No leave applications yet. <a href="encode_leave.php">Encode the first one.</a></td></tr>
<?php else: $n=1; while($r=$recent->fetch_assoc()): ?>
    <tr>
        <td class="text-muted"><?=$n++?></td>
        <td class="fw-semibold"><?=h($r['employee_name'])?></td>
        <td><span class="badge bg-<?=leaveTypeBadge($r['leave_type'])?>"><?=h($r['leave_type'])?></span></td>
        <td class="small"><?=date('M d',strtotime($r['date_start']))?> – <?=date('M d, Y',strtotime($r['date_end']))?></td>
        <td class="text-center"><span class="badge bg-dark"><?=$r['working_days']?></span></td>
        <td class="small"><?=h($r['charge_to'])?></td>
        <td class="text-center"><span class="badge bg-<?=statusBadge($r['status'])?>"><?=h($r['status'])?></span></td>
        <td class="text-center text-nowrap">
            <?php if($r['status']==='Pending'): ?>
                <a href="index.php?approve=<?=$r['id']?>" class="btn btn-sm btn-outline-success" title="Approve"
                   onclick="return confirm('Approve this leave?')"><i class="bi bi-check-lg"></i></a>
                <a href="index.php?disapprove=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" title="Disapprove"
                   onclick="return confirm('Disapprove and restore credits?')"><i class="bi bi-x-lg"></i></a>
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
