<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * employee_ledger.php — Employee Leave Ledger (Simple Logbook)
 * ============================================================
 * Shows all leave records for an employee in chronological order.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

$empId = (int)($_GET['id'] ?? 0);

// All employees for selector
$allEmps = $conn->query("SELECT id, title, first_name, middle_name, last_name, suffix FROM employees ORDER BY last_name, first_name");

$employee = null;
$logs     = [];

if ($empId > 0) {
    $st = $conn->prepare("SELECT * FROM employees WHERE id=?");
    $st->bind_param('i', $empId);
    $st->execute();
    $employee = $st->get_result()->fetch_assoc();

    if ($employee) {
        $lg = $conn->prepare(
            "SELECT * FROM leave_applications WHERE employee_id=? ORDER BY start_date ASC"
        );
        $lg->bind_param('i', $empId);
        $lg->execute();
        $res = $lg->get_result();
        while ($r = $res->fetch_assoc()) $logs[] = $r;
    }
}

// Stats
$totalVLDays = 0; $totalSLDays = 0; $totalOther = 0;
foreach ($logs as $l) {
    if ($l['leave_type'] === 'Vacation Leave')        $totalVLDays += $l['working_days'];
    elseif ($l['leave_type'] === 'Sick Leave')        $totalSLDays += $l['working_days'];
    else                                              $totalOther  += $l['working_days'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leave Ledger — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container-fluid py-4">
<?= renderFlash() ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-journal-bookmark me-2"></i>Employee Leave Ledger</h4>
    <small class="text-muted">Employee Leave Ledger</small></div>
    <?php if($employee): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <?php endif; ?>
</div>

<!-- Selector -->
<div class="card shadow-sm border-0 mb-4"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-6">
        <label class="form-label small fw-semibold mb-1">Select Employee</label>
        <select name="id" class="form-select" onchange="this.form.submit()">
            <option value="">— Choose an employee —</option>
            <?php $allEmps->data_seek(0); while($e=$allEmps->fetch_assoc()): ?>
            <option value="<?=$e['id']?>" <?=$empId==$e['id']?'selected':''?>><?=h(fullName($e))?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>View</button></div>
</form></div></div>

<?php if ($employee): ?>

<!-- ═══ Profile + Summary ═══ -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-circle flex-shrink-0"><i class="bi bi-person-fill fs-2"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1"><?=h(fullName($employee))?></h5>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" style="width:100px">Position</td><td class="fw-semibold"><?=h($employee['position']??'—')?></td></tr>
                            <tr><td class="text-muted">Department</td><td class="fw-semibold"><?=h($employee['department']??'—')?></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-<?=$employee['status']==='Active'?'success':'secondary'?>"><?=h($employee['status'])?></span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="col-lg-7">
        <div class="row g-3 h-100">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Vacation Leave</div>
                        <div class="fs-2 fw-bold text-primary"><?=number_format($totalVLDays,1)?></div>
                        <div class="text-muted small">day(s) recorded</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-danger text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Sick Leave</div>
                        <div class="fs-2 fw-bold text-danger"><?=number_format($totalSLDays,1)?></div>
                        <div class="text-muted small">day(s) recorded</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Other Leave</div>
                        <div class="fs-2 fw-bold"><?=number_format($totalOther,1)?></div>
                        <div class="text-muted small">day(s) recorded</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Leave History Ledger ═══ -->
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i>Leave History</h6>
    <span class="badge bg-light text-dark"><?=count($logs)?> record(s)</span>
</div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover table-striped mb-0">
<thead class="table-dark">
    <tr>
        <th class="text-center">#</th>
        <th>Period</th>
        <th>Leave Type</th>
        <th class="text-center">Days</th>
        <th class="text-center">Status</th>
        <th class="text-center">View</th>
    </tr>
</thead>
<tbody>
<?php if (empty($logs)): ?>
    <tr><td colspan="6" class="text-center py-4 text-muted">No leave records for this employee.</td></tr>
<?php else: $n=1; foreach($logs as $l): ?>
    <tr>
        <td class="text-center text-muted"><?=$n++?></td>
        <td class="small text-nowrap"><?=date('M d',strtotime($l['start_date']))?> – <?=date('M d, Y',strtotime($l['end_date']))?></td>
        <td><span class="badge bg-<?=leaveTypeBadge($l['leave_type'])?>"><?=h($l['leave_type'])?></span></td>
        <td class="text-center"><span class="badge bg-dark"><?=$l['working_days']?></span></td>
        <td class="text-center"><span class="badge bg-<?=statusBadge($l['status'])?>"><?=h($l['status'])?></span></td>
        <td class="text-center"><a href="view_leave.php?id=<?=$l['id']?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div></div>

<?php elseif ($empId > 0): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Employee not found.</div>
<?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-bookmark fs-1 d-block mb-3"></i>
        <h5>Select an employee above to view their Leave Ledger.</h5>
    </div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
