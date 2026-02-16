<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Employee Management
 * ============================================================
 * Full CRUD: Add, Edit, Toggle Active/Inactive, Delete.
 * Fields: Name, Position, Salary, Department, VL/SL Balances.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

// Departments for dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");

$error    = '';
$editMode = false;
$editData = null;

/* ── Delete ────────────────────────────────────────────────── */
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $chk = $conn->query("SELECT COUNT(*) c FROM leave_applications WHERE employee_id=$did")->fetch_assoc()['c'];
    if ($chk > 0) {
        setFlash('warning', "Cannot delete: employee has $chk leave record(s). Deactivate instead.");
    } else {
        $conn->query("DELETE FROM employees WHERE id=$did");
        setFlash('success', 'Employee deleted.');
    }
    header('Location: employees.php'); exit;
}

/* ── Toggle status ─────────────────────────────────────────── */
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $conn->query("UPDATE employees SET status=IF(status='Active','Inactive','Active') WHERE id=$tid");
    setFlash('success', 'Employee status updated.');
    header('Location: employees.php'); exit;
}

/* ── Edit load ─────────────────────────────────────────────── */
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $st = $conn->prepare("SELECT * FROM employees WHERE id=?");
    $st->bind_param('i', $eid);
    $st->execute();
    $editData = $st->get_result()->fetch_assoc();
    if ($editData) $editMode = true;
}

/* ── Add / Update POST ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'add';
    $name     = trim($_POST['employee_name'] ?? '');
    $pos      = trim($_POST['position'] ?? '');
    $salary   = floatval($_POST['salary'] ?? 0);
    $deptId   = (int)($_POST['department_id'] ?? 0);
    $vlBal    = floatval($_POST['vacation_leave_balance'] ?? 15);
    $slBal    = floatval($_POST['sick_leave_balance'] ?? 15);
    $dateH    = $_POST['date_hired'] ?? null;
    if ($dateH === '') $dateH = null;

    if ($name === '') {
        $error = 'Employee name is required.';
    } else {
        if ($action === 'update') {
            $uid = (int)$_POST['id'];
            $st = $conn->prepare("UPDATE employees SET employee_name=?, position=?, salary=?, department_id=?, vacation_leave_balance=?, sick_leave_balance=?, date_hired=? WHERE id=?");
            $st->bind_param('ssdiidsi', $name, $pos, $salary, $deptId, $vlBal, $slBal, $dateH, $uid);
            if ($st->execute()) { setFlash('success','Employee updated.'); header('Location: employees.php'); exit; }
            $error = 'Update failed.';
        } else {
            $st = $conn->prepare("INSERT INTO employees (employee_name, position, salary, department_id, vacation_leave_balance, sick_leave_balance, date_hired) VALUES (?,?,?,?,?,?,?)");
            $st->bind_param('ssdiids', $name, $pos, $salary, $deptId, $vlBal, $slBal, $dateH);
            if ($st->execute()) { setFlash('success','Employee added.'); header('Location: employees.php'); exit; }
            $error = 'Insert failed: ' . $conn->error;
        }
    }
    if ($error && $action === 'update') {
        $editMode = true;
        $editData = ['id' => $_POST['id'], 'employee_name' => $name, 'position' => $pos, 'salary' => $salary,
                     'department_id' => $deptId, 'vacation_leave_balance' => $vlBal, 'sick_leave_balance' => $slBal, 'date_hired' => $dateH];
    }
}

/* ── Fetch employees ───────────────────────────────────────── */
$search = trim($_GET['q'] ?? '');
$fStatus = $_GET['status'] ?? '';
$sql = "SELECT e.*, d.name AS dept FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE 1=1";
$p = []; $t = '';
if ($search !== '') { $like="%$search%"; $sql .= " AND (e.employee_name LIKE ? OR e.position LIKE ?)"; $p[]=$like; $p[]=$like; $t .= 'ss'; }
if ($fStatus !== '') { $sql .= " AND e.status=?"; $p[]=$fStatus; $t .= 's'; }
$sql .= " ORDER BY e.employee_name";
$st = $conn->prepare($sql);
if ($t !== '') $st->bind_param($t, ...$p);
$st->execute();
$emps = $st->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employees — PHO Palawan Leave Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container-fluid py-4">
<?= renderFlash() ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Employee Management</h4>
    <small class="text-muted">Add, edit, or deactivate employee records</small></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#empModal"><i class="bi bi-person-plus me-1"></i>Add Employee</button>
</div>

<!-- Search -->
<div class="card shadow-sm border-0 mb-3"><div class="card-body py-2">
<form method="GET" class="row g-2 align-items-center">
    <div class="col-md-5"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Search name or position..." value="<?=h($search)?>"></div></div>
    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">All Status</option>
        <option value="Active" <?=$fStatus==='Active'?'selected':''?>>Active</option>
        <option value="Inactive" <?=$fStatus==='Inactive'?'selected':''?>>Inactive</option></select></div>
    <div class="col-md-4"><button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="employees.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a></div>
</form></div></div>

<!-- Table -->
<div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover table-striped table-sm mb-0">
<thead class="table-dark"><tr>
    <th>#</th><th>Employee Name</th><th>Position</th><th>Department</th>
    <th class="text-end">Salary</th>
    <th class="text-center">VL Bal.</th><th class="text-center">SL Bal.</th>
    <th class="text-center">Status</th><th class="text-center" style="width:170px">Actions</th>
</tr></thead>
<tbody>
<?php if ($emps->num_rows===0): ?>
    <tr><td colspan="9" class="text-center py-4 text-muted">No employees found.</td></tr>
<?php else: $n=1; while($r=$emps->fetch_assoc()):
    $vlC = $r['vacation_leave_balance']<=3 ? ($r['vacation_leave_balance']<=0?'text-danger fw-bold':'text-warning fw-bold') : '';
    $slC = $r['sick_leave_balance']<=3 ? ($r['sick_leave_balance']<=0?'text-danger fw-bold':'text-warning fw-bold') : '';
?>
<tr>
    <td class="text-muted"><?=$n++?></td>
    <td class="fw-semibold"><?=h($r['employee_name'])?></td>
    <td class="small"><?=h($r['position']??'—')?></td>
    <td class="small"><?=h($r['dept']??'—')?></td>
    <td class="text-end small"><?=peso($r['salary'])?></td>
    <td class="text-center <?=$vlC?>"><?=number_format($r['vacation_leave_balance'],3)?></td>
    <td class="text-center <?=$slC?>"><?=number_format($r['sick_leave_balance'],3)?></td>
    <td class="text-center"><span class="badge bg-<?=$r['status']==='Active'?'success':'secondary'?>"><?=h($r['status'])?></span></td>
    <td class="text-center text-nowrap">
        <a href="employee_ledger.php?id=<?=$r['id']?>" class="btn btn-sm btn-outline-info" title="Ledger"><i class="bi bi-journal-bookmark"></i></a>
        <a href="employees.php?edit=<?=$r['id']?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
        <a href="employees.php?toggle=<?=$r['id']?>" class="btn btn-sm btn-outline-warning" title="Toggle"
           onclick="return confirm('Toggle status?')"><i class="bi bi-toggle-on"></i></a>
        <a href="employees.php?delete=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" title="Delete"
           onclick="return confirm('Delete this employee?')"><i class="bi bi-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody></table></div></div>
<div class="card-footer bg-white text-muted small">Total: <strong><?=$emps->num_rows?></strong> employee(s)</div>
</div>
</div>

<!-- ═══ Add / Edit Modal ═══ -->
<div class="modal fade" id="empModal" tabindex="-1"><div class="modal-dialog modal-lg">
<div class="modal-content"><form method="POST" action="employees.php">
<input type="hidden" name="action" value="<?=$editMode?'update':'add'?>">
<?php if($editMode): ?><input type="hidden" name="id" value="<?=$editData['id']?>"><?php endif; ?>

<div class="modal-header">
    <h5 class="modal-title"><i class="bi bi-<?=$editMode?'pencil-square':'person-plus'?> me-2"></i><?=$editMode?'Edit Employee':'Add New Employee'?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <?php if($error): ?><div class="alert alert-danger py-2 small"><?=h($error)?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Employee Name <span class="text-danger">*</span></label>
            <input type="text" name="employee_name" class="form-control" required
                   value="<?=h($editMode?$editData['employee_name']:($_POST['employee_name']??''))?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Position / Designation</label>
            <input type="text" name="position" class="form-control"
                   value="<?=h($editMode?($editData['position']??''):($_POST['position']??''))?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Department</label>
            <select name="department_id" class="form-select">
                <option value="0">— None —</option>
                <?php $departments->data_seek(0); while($d=$departments->fetch_assoc()): ?>
                <option value="<?=$d['id']?>" <?=($editMode?$editData['department_id']:($_POST['department_id']??0))==$d['id']?'selected':''?>>
                    <?=h($d['name'])?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Monthly Salary</label>
            <div class="input-group"><span class="input-group-text">₱</span>
            <input type="number" name="salary" class="form-control" step="0.01" min="0"
                   value="<?=h($editMode?$editData['salary']:($_POST['salary']??'0'))?>"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Date Hired</label>
            <input type="date" name="date_hired" class="form-control"
                   value="<?=h($editMode?($editData['date_hired']??''):($_POST['date_hired']??''))?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Vacation Leave Balance</label>
            <input type="number" name="vacation_leave_balance" class="form-control" step="0.125" min="0"
                   value="<?=h($editMode?$editData['vacation_leave_balance']:($_POST['vacation_leave_balance']??'15'))?>">
            <div class="form-text">Default: 15.000 days</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Sick Leave Balance</label>
            <input type="number" name="sick_leave_balance" class="form-control" step="0.125" min="0"
                   value="<?=h($editMode?$editData['sick_leave_balance']:($_POST['sick_leave_balance']??'15'))?>">
            <div class="form-text">Default: 15.000 days</div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?=$editMode?'Update':'Save Employee'?></button>
</div>
</form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($editMode || ($error && $_SERVER['REQUEST_METHOD']==='POST')): ?>
document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('empModal')).show());
<?php endif; ?>
</script>
</body>
</html>
