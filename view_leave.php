<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * view_leave.php — View a single leave application (read-only)
 * ============================================================
 * Full CS Form 6 detail view with approve/disapprove actions.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: leave_history.php'); exit; }

/* ── Handle Approve / Disapprove ───────────────────────────── */
if (isset($_GET['action'])) {
    $act = $_GET['action'];
    if ($act === 'approve') {
        $conn->query("UPDATE leave_applications SET status='Approved' WHERE id=$id");
        setFlash('success', "Leave application #$id approved.");
    } elseif ($act === 'disapprove') {
        // Restore credits
        $la = $conn->query("SELECT employee_id,charge_to,days_deducted FROM leave_applications WHERE id=$id AND status='Pending'")->fetch_assoc();
        if ($la && $la['days_deducted'] > 0 && $la['charge_to'] !== 'Leave Without Pay') {
            $col = $la['charge_to'] === 'Sick Leave' ? 'sick_leave_balance' : 'vacation_leave_balance';
            $conn->query("UPDATE employees SET $col=$col+{$la['days_deducted']} WHERE id={$la['employee_id']}");
        }
        $conn->query("UPDATE leave_applications SET status='Disapproved', days_deducted=0 WHERE id=$id");
        setFlash('warning', "Leave application #$id disapproved. Credits restored.");
    }
    header("Location: view_leave.php?id=$id"); exit;
}

/* ── Delete leave record ──────────────────────────────────── */
if (isset($_GET['delete'])) {
    $la = $conn->query("SELECT employee_id,charge_to,days_deducted,status FROM leave_applications WHERE id=$id")->fetch_assoc();
    if ($la) {
        // Restore credits if was deducted and not already disapproved
        if ($la['days_deducted'] > 0 && $la['status'] !== 'Disapproved' && $la['charge_to'] !== 'Leave Without Pay') {
            $col = $la['charge_to'] === 'Sick Leave' ? 'sick_leave_balance' : 'vacation_leave_balance';
            $conn->query("UPDATE employees SET $col=$col+{$la['days_deducted']} WHERE id={$la['employee_id']}");
        }
        $conn->query("DELETE FROM leave_applications WHERE id=$id");
        setFlash('success', 'Leave record deleted. Credits restored.');
        header('Location: leave_history.php'); exit;
    }
}

/* ── Fetch the record ──────────────────────────────────────── */
$st = $conn->prepare(
    "SELECT la.*, e.employee_name, e.position, e.salary, d.name AS dept
     FROM leave_applications la
     JOIN employees e ON e.id=la.employee_id
     LEFT JOIN departments d ON d.id=e.department_id
     WHERE la.id=?"
);
$st->bind_param('i', $id);
$st->execute();
$la = $st->get_result()->fetch_assoc();
if (!$la) { setFlash('danger','Record not found.'); header('Location: leave_history.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>View Leave #<?=$id?> — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container py-4" style="max-width:900px">
<?= renderFlash() ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Leave Application #<?=$id?></h4>
    </div>
    <div class="d-flex gap-1">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        <a href="leave_history.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
<div class="card-body">

<!-- Employee Info -->
<div class="row mb-4 pb-3 border-bottom">
    <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0 small">
            <tr><td class="text-muted" style="width:120px">Employee</td><td class="fw-bold"><?=h($la['employee_name'])?></td></tr>
            <tr><td class="text-muted">Position</td><td><?=h($la['position']??'—')?></td></tr>
            <tr><td class="text-muted">Department</td><td><?=h($la['dept']??'—')?></td></tr>
            <tr><td class="text-muted">Salary</td><td><?=peso($la['salary'])?></td></tr>
        </table>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="mb-2"><span class="badge bg-<?=statusBadge($la['status'])?> fs-6"><?=h($la['status'])?></span></div>
        <div class="small text-muted">Recorded: <?=date('M d, Y h:i A',strtotime($la['created_at']))?></div>
        <div class="small text-muted">By: <?=h($la['recorded_by']??'—')?></div>
    </div>
</div>

<!-- §6-A  Leave Type -->
<h6 class="fw-bold text-primary mb-2"><i class="bi bi-tag me-1"></i>Leave Type</h6>
<div class="mb-3 ms-3">
    <span class="badge bg-<?=leaveTypeBadge($la['leave_type'])?> fs-6"><?=h($la['leave_type'])?></span>
    <?php if($la['other_leave_type']): ?><span class="ms-2 text-muted">(<?=h($la['other_leave_type'])?>)</span><?php endif; ?>
</div>

<!-- §6-B  Details -->
<h6 class="fw-bold text-primary mb-2"><i class="bi bi-card-text me-1"></i>Specific Details</h6>
<div class="mb-3 ms-3 small">
    <?php if($la['vacation_detail']): ?>
        <p class="mb-1"><strong>Location:</strong> <?=h($la['vacation_detail'])?> <?=$la['vacation_location']?'— '.h($la['vacation_location']):''?></p>
    <?php endif; ?>
    <?php if($la['sick_detail']): ?>
        <p class="mb-1"><strong>Illness:</strong> <?=h($la['sick_detail'])?> <?=$la['sick_illness']?'— '.h($la['sick_illness']):''?></p>
    <?php endif; ?>
    <?php if($la['study_detail']): ?>
        <p class="mb-1"><strong>Study:</strong> <?=h($la['study_detail'])?></p>
    <?php endif; ?>
    <?php if($la['other_detail']): ?>
        <p class="mb-1"><strong>Details:</strong> <?=h($la['other_detail'])?></p>
    <?php endif; ?>
    <?php if(!$la['vacation_detail'] && !$la['sick_detail'] && !$la['study_detail'] && !$la['other_detail']): ?>
        <span class="text-muted">No additional details.</span>
    <?php endif; ?>
</div>

<!-- §6-C  Dates -->
<h6 class="fw-bold text-primary mb-2"><i class="bi bi-calendar-range me-1"></i>Duration</h6>
<div class="mb-3 ms-3">
    <div class="row g-3">
        <div class="col-auto"><div class="border rounded p-2 small"><span class="text-muted d-block">Start</span><strong><?=date('F d, Y',strtotime($la['date_start']))?></strong></div></div>
        <div class="col-auto"><div class="border rounded p-2 small"><span class="text-muted d-block">End</span><strong><?=date('F d, Y',strtotime($la['date_end']))?></strong></div></div>
        <div class="col-auto"><div class="border rounded p-2 small text-center"><span class="text-muted d-block">Working Days</span><strong class="fs-5"><?=$la['working_days']?></strong></div></div>
    </div>
</div>

<!-- §6-D  Commutation -->
<h6 class="fw-bold text-primary mb-2"><i class="bi bi-cash-stack me-1"></i>Commutation Request</h6>
<div class="mb-3 ms-3"><span class="badge bg-<?=$la['commutation']==='Requested'?'info':'secondary'?>"><?=h($la['commutation'])?></span></div>

<!-- Credit Impact -->
<h6 class="fw-bold text-dark mb-2"><i class="bi bi-database me-1"></i>Credit Impact</h6>
<div class="mb-3 ms-3">
    <table class="table table-sm table-bordered small" style="max-width:500px">
        <tr><td class="text-muted">Charged To</td><td class="fw-bold"><?=h($la['charge_to'])?></td></tr>
        <tr><td class="text-muted">Days Deducted</td><td class="fw-bold"><?=number_format($la['days_deducted'],3)?></td></tr>
        <tr class="table-light"><td class="text-muted">VL Balance</td><td><?=number_format($la['balance_before_vl']??0,3)?> → <strong class="text-primary"><?=number_format($la['balance_after_vl']??0,3)?></strong></td></tr>
        <tr class="table-light"><td class="text-muted">SL Balance</td><td><?=number_format($la['balance_before_sl']??0,3)?> → <strong class="text-danger"><?=number_format($la['balance_after_sl']??0,3)?></strong></td></tr>
    </table>
</div>

<?php if($la['remarks']): ?>
<h6 class="fw-bold text-dark mb-2"><i class="bi bi-chat-left-text me-1"></i>Remarks</h6>
<div class="mb-3 ms-3 small"><?=nl2br(h($la['remarks']))?></div>
<?php endif; ?>

</div><!-- /card-body -->

<!-- Action Footer -->
<div class="card-footer bg-white d-flex flex-wrap gap-2">
    <?php if($la['status']==='Pending'): ?>
        <a href="view_leave.php?id=<?=$id?>&action=approve" class="btn btn-success" onclick="return confirm('Approve this leave?')">
            <i class="bi bi-check-circle me-1"></i>Approve</a>
        <a href="view_leave.php?id=<?=$id?>&action=disapprove" class="btn btn-danger" onclick="return confirm('Disapprove and restore credits?')">
            <i class="bi bi-x-circle me-1"></i>Disapprove</a>
    <?php endif; ?>
    <a href="employee_ledger.php?id=<?=$la['employee_id']?>" class="btn btn-outline-info"><i class="bi bi-journal-bookmark me-1"></i>View Ledger</a>
    <a href="view_leave.php?id=<?=$id?>&delete=1" class="btn btn-outline-danger ms-auto" onclick="return confirm('Delete this record and restore credits?')">
        <i class="bi bi-trash me-1"></i>Delete</a>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
