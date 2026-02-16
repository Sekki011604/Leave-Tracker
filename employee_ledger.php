<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * employee_ledger.php — Certificate of Leave Credits (§7A)
 * ============================================================
 * Replicates Section 7A of CS Form No. 6:
 *   Previous Balance  →  Less: This Application  →  Remaining Balance
 * Separately for Vacation Leave and Sick Leave.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

$empId = (int)($_GET['id'] ?? 0);

// All employees for selector
$allEmps = $conn->query("SELECT id, employee_name FROM employees ORDER BY employee_name");

$employee = null;
$logs     = [];

if ($empId > 0) {
    $st = $conn->prepare(
        "SELECT e.*, d.name AS dept FROM employees e
         LEFT JOIN departments d ON d.id=e.department_id WHERE e.id=?"
    );
    $st->bind_param('i', $empId);
    $st->execute();
    $employee = $st->get_result()->fetch_assoc();

    if ($employee) {
        $lg = $conn->prepare(
            "SELECT * FROM leave_applications WHERE employee_id=? ORDER BY date_start ASC"
        );
        $lg->bind_param('i', $empId);
        $lg->execute();
        $res = $lg->get_result();
        while ($r = $res->fetch_assoc()) $logs[] = $r;
    }
}

// Stats
$totalVLUsed = 0; $totalSLUsed = 0; $totalLWOP = 0;
foreach ($logs as $l) {
    if ($l['charge_to'] === 'Vacation Leave')   $totalVLUsed += $l['days_deducted'];
    if ($l['charge_to'] === 'Sick Leave')       $totalSLUsed += $l['days_deducted'];
    if ($l['charge_to'] === 'Leave Without Pay') $totalLWOP  += $l['working_days'];
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
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container-fluid py-4">
<?= renderFlash() ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-journal-bookmark me-2"></i>Employee Leave Ledger</h4>
    <small class="text-muted">Certificate of Leave Credits — CS Form No. 6, Section 7A</small></div>
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
            <option value="<?=$e['id']?>" <?=$empId==$e['id']?'selected':''?>><?=h($e['employee_name'])?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>View</button></div>
</form></div></div>

<?php if ($employee): ?>

<!-- ═══ Profile + Balance Summary ═══ -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-circle flex-shrink-0"><i class="bi bi-person-fill fs-2"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1"><?=h($employee['employee_name'])?></h5>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" style="width:100px">Position</td><td class="fw-semibold"><?=h($employee['position']??'—')?></td></tr>
                            <tr><td class="text-muted">Department</td><td class="fw-semibold"><?=h($employee['dept']??'—')?></td></tr>
                            <tr><td class="text-muted">Salary</td><td class="fw-semibold"><?=peso($employee['salary'])?></td></tr>
                            <tr><td class="text-muted">Date Hired</td><td class="fw-semibold"><?=$employee['date_hired']?date('M d, Y',strtotime($employee['date_hired'])):'—'?></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-<?=$employee['status']==='Active'?'success':'secondary'?>"><?=h($employee['status'])?></span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="col-lg-7">
        <div class="row g-3 h-100">
            <!-- VL -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Vacation Leave</div>
                        <div class="fs-2 fw-bold text-primary"><?=number_format($employee['vacation_leave_balance'],3)?></div>
                        <div class="text-muted small">days remaining</div>
                    </div>
                </div>
            </div>
            <!-- SL -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-danger text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Sick Leave</div>
                        <div class="fs-2 fw-bold text-danger"><?=number_format($employee['sick_leave_balance'],3)?></div>
                        <div class="text-muted small">days remaining</div>
                    </div>
                </div>
            </div>
            <!-- Total Used -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold">Total Used</div>
                        <div class="fs-4 fw-bold"><?=number_format($totalVLUsed,3)?> <small class="text-primary">VL</small></div>
                        <div class="fs-4 fw-bold"><?=number_format($totalSLUsed,3)?> <small class="text-danger">SL</small></div>
                        <?php if($totalLWOP > 0): ?>
                        <div class="small text-muted mt-1"><?=number_format($totalLWOP,1)?> day(s) LWOP</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ §7A — Certificate of Leave Credits Ledger ═══ -->
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i>7.A — Certification of Leave Credits</h6>
    <span class="badge bg-light text-dark"><?=count($logs)?> record(s)</span>
</div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover table-striped mb-0">
<thead class="table-dark">
    <tr>
        <th rowspan="2" class="align-middle text-center">#</th>
        <th rowspan="2" class="align-middle">Period</th>
        <th rowspan="2" class="align-middle">Leave Type</th>
        <th rowspan="2" class="align-middle text-center">Working<br>Days</th>
        <th rowspan="2" class="align-middle">Charged To</th>
        <th colspan="3" class="text-center border-start text-primary">Vacation Leave</th>
        <th colspan="3" class="text-center border-start text-danger">Sick Leave</th>
        <th rowspan="2" class="align-middle text-center">Status</th>
    </tr>
    <tr>
        <th class="text-center border-start small">Before</th>
        <th class="text-center small">Deducted</th>
        <th class="text-center small">After</th>
        <th class="text-center border-start small">Before</th>
        <th class="text-center small">Deducted</th>
        <th class="text-center small">After</th>
    </tr>
</thead>
<tbody>
<?php if (empty($logs)): ?>
    <tr><td colspan="12" class="text-center py-4 text-muted">No leave records for this employee.</td></tr>
<?php else: $n=1; foreach($logs as $l):
    $vlDed = $l['charge_to']==='Vacation Leave' ? $l['days_deducted'] : 0;
    $slDed = $l['charge_to']==='Sick Leave'     ? $l['days_deducted'] : 0;
?>
    <tr>
        <td class="text-center text-muted"><?=$n++?></td>
        <td class="small text-nowrap"><?=date('M d',strtotime($l['date_start']))?> – <?=date('M d, Y',strtotime($l['date_end']))?></td>
        <td><span class="badge bg-<?=leaveTypeBadge($l['leave_type'])?>"><?=h($l['leave_type'])?></span></td>
        <td class="text-center"><span class="badge bg-dark"><?=$l['working_days']?></span></td>
        <td class="small"><?=h($l['charge_to'])?></td>
        <!-- VL columns -->
        <td class="text-center border-start small"><?=number_format($l['balance_before_vl']??0,3)?></td>
        <td class="text-center small fw-bold <?=$vlDed>0?'text-primary':''?>"><?=$vlDed > 0 ? number_format($vlDed,3) : '—'?></td>
        <td class="text-center small"><?=number_format($l['balance_after_vl']??0,3)?></td>
        <!-- SL columns -->
        <td class="text-center border-start small"><?=number_format($l['balance_before_sl']??0,3)?></td>
        <td class="text-center small fw-bold <?=$slDed>0?'text-danger':''?>"><?=$slDed > 0 ? number_format($slDed,3) : '—'?></td>
        <td class="text-center small"><?=number_format($l['balance_after_sl']??0,3)?></td>
        <td class="text-center"><span class="badge bg-<?=statusBadge($l['status'])?>"><?=h($l['status'])?></span></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
<?php if(!empty($logs)): ?>
<tfoot class="table-light">
    <tr class="fw-bold">
        <td colspan="5" class="text-end">Current Remaining Balance →</td>
        <td colspan="3" class="text-center border-start text-primary fs-6"><?=number_format($employee['vacation_leave_balance'],3)?></td>
        <td colspan="3" class="text-center border-start text-danger fs-6"><?=number_format($employee['sick_leave_balance'],3)?></td>
        <td></td>
    </tr>
</tfoot>
<?php endif; ?>
</table>
</div></div></div>

<?php elseif ($empId > 0): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Employee not found.</div>
<?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-bookmark fs-1 d-block mb-3"></i>
        <h5>Select an employee above to view their Leave Credits Ledger.</h5>
    </div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
