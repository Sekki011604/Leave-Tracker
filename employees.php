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
    $action   = $_POST['action'] ?? 'add';
    $firstName  = trim($_POST['first_name']  ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');

    // Concatenate into government format: "Last Name, First Name Middle Name"
    if ($middleName !== '') {
        $name = $lastName . ', ' . $firstName . ' ' . $middleName;
    } else {
        $name = $lastName . ', ' . $firstName;
    }

    $pos      = trim($_POST['position'] ?? '');
    $dept     = trim($_POST['department'] ?? '');

    if ($firstName === '' || $lastName === '') {
        $error = 'First Name and Last Name are required.';
    } elseif ($pos === '') {
        $error = 'Position is required.';
    } elseif ($dept === '') {
        $error = 'Department is required.';
    } else {
        if ($action === 'update') {
            $uid = (int)$_POST['id'];
            $st = $conn->prepare("UPDATE employees SET employee_name=?, position=?, department=? WHERE id=?");
            $st->bind_param('sssi', $name, $pos, $dept, $uid);
            if ($st->execute()) { setFlash('success','Employee updated.'); header('Location: employees.php'); exit; }
            $error = 'Update failed.';
        } else {
            $st = $conn->prepare("INSERT INTO employees (employee_name, position, department) VALUES (?,?,?)");
            $st->bind_param('sss', $name, $pos, $dept);
            if ($st->execute()) { setFlash('success','Employee added.'); header('Location: employees.php'); exit; }
            $error = 'Insert failed: ' . $conn->error;
        }
    }
    if ($error && $action === 'update') {
        $editMode = true;
        $editData = ['id' => $_POST['id'], 'employee_name' => $name,
                     '_first' => $firstName, '_middle' => $middleName, '_last' => $lastName,
                     'position' => $pos, 'department' => $dept];
    }
}

/*
 * Helper: parse "Last, First M." back into parts (for edit mode).
 * Returns [first, middle, last].
 */
function parseEmployeeName(?string $full): array {
    if (!$full) return ['', '', ''];
    // Expected: "LastName, FirstName M." or "LastName, FirstName"
    if (strpos($full, ',') !== false) {
        [$last, $rest] = array_map('trim', explode(',', $full, 2));
        $parts = preg_split('/\s+/', $rest);
        $first = $parts[0] ?? '';
        $mid   = isset($parts[1]) ? rtrim($parts[1], '.') : '';
        return [$first, $mid, $last];
    }
    // Fallback: space-separated
    $parts = preg_split('/\s+/', $full);
    if (count($parts) >= 3) return [$parts[0], $parts[1], $parts[2]];
    if (count($parts) === 2) return [$parts[0], '', $parts[1]];
    return [$full, '', ''];
}

/* ── Fetch employees ───────────────────────────────────────── */
$search = trim($_GET['q'] ?? '');
$fStatus = $_GET['status'] ?? '';
$sql = "SELECT * FROM employees e WHERE 1=1";
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
        <a href="employees.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a></div>
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
    <td class="fw-semibold"><?=h($r['employee_name'])?></td>
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
        // Resolve name parts for the form
        if ($editMode && isset($editData['_first'])) {
            // Came from a failed POST — parts already available
            $_fn = $editData['_first']; $_mn = $editData['_middle']; $_ln = $editData['_last'];
        } elseif ($editMode) {
            // Loaded from DB — parse the stored full name
            [$_fn, $_mn, $_ln] = parseEmployeeName($editData['employee_name'] ?? '');
        } else {
            $_fn = $_POST['first_name']  ?? '';
            $_mn = $_POST['middle_name'] ?? '';
            $_ln = $_POST['last_name']   ?? '';
        }
    ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="form-control" required
                   value="<?=h($_fn)?>" placeholder="Juan">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Middle Name</label>
            <input type="text" name="middle_name" class="form-control"
                   value="<?=h($_mn)?>" placeholder="Andres">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="last_name" class="form-control" required
                   value="<?=h($_ln)?>" placeholder="Dela Cruz">
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
