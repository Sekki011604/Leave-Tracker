<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Full Leave History
 * ============================================================
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

$fEmp    = $_GET['employee'] ?? '';
$fType   = $_GET['type'] ?? '';
$fStatus = $_GET['status'] ?? '';
$fYear   = $_GET['year'] ?? '';

$sql = "SELECT la.*, e.employee_name, d.name AS dept
        FROM leave_applications la
        JOIN employees e ON e.id=la.employee_id
        LEFT JOIN departments d ON d.id=e.department_id WHERE 1=1";
$p=[]; $t='';
if($fEmp!=='')   { $sql.=" AND la.employee_id=?"; $p[]=(int)$fEmp; $t.='i'; }
if($fType!=='')  { $sql.=" AND la.leave_type=?";   $p[]=$fType;     $t.='s'; }
if($fStatus!=='') { $sql.=" AND la.status=?";       $p[]=$fStatus;   $t.='s'; }
if($fYear!=='')  { $sql.=" AND YEAR(la.date_start)=?"; $p[]=(int)$fYear; $t.='i'; }
$sql .= " ORDER BY la.date_start DESC";
$st = $conn->prepare($sql);
if ($t !== '') {
    // Build a reference array for bind_param (works on all PHP versions)
    $bindArgs = [$t];
    for ($i = 0; $i < count($p); $i++) {
        $bindArgs[] = &$p[$i];
    }
    call_user_func_array([$st, 'bind_param'], $bindArgs);
}
$st->execute();
$logs = $st->get_result();

$empList=$conn->query("SELECT id,employee_name FROM employees ORDER BY employee_name");
$years=$conn->query("SELECT DISTINCT YEAR(date_start) y FROM leave_applications ORDER BY y DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leave History — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container-fluid py-4">
<?= renderFlash() ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Leave History</h4>
    <small class="text-muted">All leave applications across the office</small></div>
    <a href="encode_leave.php" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Encode Leave</a>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-3"><div class="card-body py-2">
<form method="GET" action="leave_history.php" class="row g-2 align-items-end">
    <div class="col-md-3">
        <label class="form-label small mb-1">Employee</label>
        <select name="employee" class="form-select form-select-sm"><option value="">All</option>
        <?php while($e=$empList->fetch_assoc()): ?><option value="<?=$e['id']?>" <?=$fEmp==$e['id']?'selected':''?>><?=h($e['employee_name'])?></option><?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Leave Type</label>
        <select name="type" class="form-select form-select-sm"><option value="">All Types</option>
        <?php foreach(leaveTypes() as $lt): ?><option value="<?=h($lt)?>" <?=$fType===$lt?'selected':''?>><?=h($lt)?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm"><option value="">All</option>
        <option value="Pending" <?=$fStatus==='Pending'?'selected':''?>>Pending</option>
        <option value="Approved" <?=$fStatus==='Approved'?'selected':''?>>Approved</option>
        <option value="Disapproved" <?=$fStatus==='Disapproved'?'selected':''?>>Disapproved</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Year</label>
        <select name="year" class="form-select form-select-sm"><option value="">All Years</option>
        <?php while($yr=$years->fetch_assoc()): ?><option value="<?=$yr['y']?>" <?=$fYear==$yr['y']?'selected':''?>><?=$yr['y']?></option><?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="leave_history.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
    </div>
</form></div></div>

<!-- Table -->
<div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover table-striped mb-0">
<thead class="table-dark"><tr>
    <th>#</th><th>Employee</th><th>Dept</th><th>Leave Type</th><th>Period</th>
    <th class="text-center">Days</th><th>Charged To</th><th class="text-center">Status</th>
    <th>Commutation</th><th class="text-center">View</th>
</tr></thead>
<tbody>
<?php if($logs->num_rows===0): ?>
    <tr><td colspan="10" class="text-center py-4 text-muted">No records found.</td></tr>
<?php else: $n=1; while($r=$logs->fetch_assoc()): ?>
<tr>
    <td class="text-muted"><?=$n++?></td>
    <td class="fw-semibold"><?=h($r['employee_name'])?></td>
    <td class="small"><?=h($r['dept']??'')?></td>
    <td><span class="badge bg-<?=leaveTypeBadge($r['leave_type'])?>"><?=h($r['leave_type'])?></span></td>
    <td class="small text-nowrap"><?=date('M d',strtotime($r['date_start']))?> – <?=date('M d, Y',strtotime($r['date_end']))?></td>
    <td class="text-center"><span class="badge bg-dark"><?=$r['working_days']?></span></td>
    <td class="small"><?=h($r['charge_to'])?></td>
    <td class="text-center"><span class="badge bg-<?=statusBadge($r['status'])?>"><?=h($r['status'])?></span></td>
    <td class="small"><?=h($r['commutation'])?></td>
    <td class="text-center"><a href="view_leave.php?id=<?=$r['id']?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
</tr>
<?php endwhile; endif; ?>
</tbody></table></div></div>
<div class="card-footer bg-white text-muted small">Showing <strong><?=$logs->num_rows?></strong> record(s)</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
