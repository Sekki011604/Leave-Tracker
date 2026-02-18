<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Employee Management
 * ============================================================
 * Full CRUD: Add, Edit, Toggle Active/Inactive, Delete.
 * Fields: Name, Position, Department (text).
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

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
    $action     = $_POST['action'] ?? 'add';
    $title      = trim($_POST['title']       ?? '');
    $firstName  = trim($_POST['first_name']  ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $suffix     = trim($_POST['suffix']      ?? '');
    $pos        = trim($_POST['position']    ?? '');
    $dept       = trim($_POST['department']  ?? '');

    if ($firstName === '' || $lastName === '') {
        $error = 'First Name and Last Name are required.';
    } elseif ($pos === '') {
        $error = 'Position is required.';
    } elseif ($dept === '') {
        $error = 'Department is required.';
    } else {
        if ($action === 'update') {
            $uid = (int)$_POST['id'];
            $st = $conn->prepare("UPDATE employees SET title=?, first_name=?, middle_name=?, last_name=?, suffix=?, position=?, department=? WHERE id=?");
            $st->bind_param('sssssssi', $title, $firstName, $middleName, $lastName, $suffix, $pos, $dept, $uid);
            if ($st->execute()) { setFlash('success','Employee updated.'); header('Location: employees.php'); exit; }
            $error = 'Update failed.';
        } else {
            $st = $conn->prepare("INSERT INTO employees (title, first_name, middle_name, last_name, suffix, position, department) VALUES (?,?,?,?,?,?,?)");
            $st->bind_param('sssssss', $title, $firstName, $middleName, $lastName, $suffix, $pos, $dept);
            if ($st->execute()) { setFlash('success','Employee added.'); header('Location: employees.php'); exit; }
            $error = 'Insert failed: ' . $conn->error;
        }
    }
    if ($error && $action === 'update') {
        $editMode = true;
        $editData = ['id' => $_POST['id'],
                     'title' => $title, 'first_name' => $firstName, 'middle_name' => $middleName,
                     'last_name' => $lastName, 'suffix' => $suffix,
                     'position' => $pos, 'department' => $dept];
    }
}

/* ── Fetch employees ───────────────────────────────────────── */
$search = trim($_GET['q'] ?? '');
$fStatus = $_GET['status'] ?? '';
$sort    = $_GET['sort'] ?? 'last';
$sql = "SELECT * FROM employees e WHERE 1=1";
$p = []; $t = '';
if ($search !== '') { $like="%$search%"; $sql .= " AND (CONCAT_WS(' ',e.title,e.first_name,e.middle_name,e.last_name,e.suffix) LIKE ? OR e.position LIKE ?)"; $p[]=$like; $p[]=$like; $t .= 'ss'; }
if ($fStatus !== '') { $sql .= " AND e.status=?"; $p[]=$fStatus; $t .= 's'; }
$sql .= ($sort === 'first') ? " ORDER BY e.first_name ASC, e.last_name ASC" : " ORDER BY e.last_name ASC, e.first_name ASC";
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
<link href="assets/css/custom.css" rel="stylesheet">
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
        <a href="employees.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        <span class="ms-2 border-start ps-2">
            <a href="employees.php?sort=last<?=$search?'&q='.urlencode($search):''?><?=$fStatus?'&status='.urlencode($fStatus):''?>" class="btn btn-sm <?=$sort!=='first'?'btn-primary':'btn-outline-primary'?> me-1" title="Sort by Last Name"><i class="bi bi-sort-alpha-down me-1"></i>Last Name</a>
            <a href="employees.php?sort=first<?=$search?'&q='.urlencode($search):''?><?=$fStatus?'&status='.urlencode($fStatus):''?>" class="btn btn-sm <?=$sort==='first'?'btn-primary':'btn-outline-primary'?>" title="Sort by First Name"><i class="bi bi-sort-alpha-down me-1"></i>First Name</a>
        </span></div>
</form></div></div>

<!-- Table -->
<div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover table-striped table-sm mb-0">
<thead class="table-dark"><tr>
    <th>#</th><th>Employee Name</th><th>Position</th><th>Department</th>
    <th class="text-center">Status</th><th class="text-center" style="width:170px">Actions</th>
</tr></thead>
<tbody>
<?php if ($emps->num_rows===0): ?>
    <tr><td colspan="6" class="text-center py-4 text-muted">No employees found.</td></tr>
<?php else: $n=1; while($r=$emps->fetch_assoc()): ?>
<tr>
    <td class="text-muted"><?=$n++?></td>
    <td class="fw-semibold"><?=h(fullName($r))?></td>
    <td class="small"><?=h($r['position']??'—')?></td>
    <td class="small"><?=h($r['department']??'—')?></td>

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
<div class="modal fade" id="empModal" tabindex="-1"><div class="modal-dialog">
<div class="modal-content"><form method="POST" action="employees.php">
<input type="hidden" name="action" value="<?=$editMode?'update':'add'?>">
<?php if($editMode): ?><input type="hidden" name="id" value="<?=$editData['id']?>"><?php endif; ?>

<div class="modal-header">
    <h5 class="modal-title"><i class="bi bi-<?=$editMode?'pencil-square':'person-plus'?> me-2"></i><?=$editMode?'Edit Employee':'Add New Employee'?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <?php if($error): ?><div class="alert alert-danger py-2 small"><?=h($error)?></div><?php endif; ?>

    <?php
        // Resolve field values (directly from DB columns or POST)
        if ($editMode) {
            $_tt = $editData['title']       ?? '';
            $_fn = $editData['first_name']  ?? '';
            $_mn = $editData['middle_name'] ?? '';
            $_ln = $editData['last_name']   ?? '';
            $_sx = $editData['suffix']      ?? '';
        } else {
            $_tt = $_POST['title']       ?? '';
            $_fn = $_POST['first_name']  ?? '';
            $_mn = $_POST['middle_name'] ?? '';
            $_ln = $_POST['last_name']   ?? '';
            $_sx = $_POST['suffix']      ?? '';
        }
    ?>
    <div class="row g-3">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?=h($_tt)?>" placeholder="e.g. Dr.">
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="form-control" required
                   value="<?=h($_fn)?>" placeholder="Juan">
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Middle Name</label>
            <input type="text" name="middle_name" class="form-control"
                   value="<?=h($_mn)?>" placeholder="Andres">
        </div>
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="last_name" class="form-control" required
                   value="<?=h($_ln)?>" placeholder="Dela Cruz">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Suffix</label>
            <input type="text" name="suffix" class="form-control"
                   value="<?=h($_sx)?>" placeholder="e.g. Jr., III">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Position / Designation <span class="text-danger">*</span></label>
            <input type="text" name="position" class="form-control" required
                   value="<?=h($editMode?($editData['position']??''):($_POST['position']??''))?>" placeholder="e.g. Nurse II">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
            <input type="text" name="department" class="form-control" required
                   value="<?=h($editMode?($editData['department']??''):($_POST['department']??''))?>" placeholder="e.g. Rural Health Unit">
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
