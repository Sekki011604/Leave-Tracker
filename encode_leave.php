<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * encode_leave.php — Digital Civil Service Form No. 6
 * ============================================================
 * Dynamic inputs:
 *   Vacation  → Within PH / Abroad + location
 *   Sick      → In Hospital / Out Patient + illness
 *   Study     → Masters / Board Exam / Other
 *   Others    → free-text detail
 * Business-day auto-calculation (JS + PHP verification).
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

// Active employees for dropdown
$employees = $conn->query(
    "SELECT e.id, e.employee_name, e.position, e.vacation_leave_balance, e.sick_leave_balance, d.name AS dept
     FROM employees e
     LEFT JOIN departments d ON d.id=e.department_id
     WHERE e.status='Active'
     ORDER BY e.employee_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Encode Leave — PHO Palawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container py-4" style="max-width:860px">
<?= renderFlash() ?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2"></i>Encode Leave Application</h4>
    <p class="text-muted mb-0">Fill out the details below to record a new leave.</p>
</div>

<form method="POST" action="save_leave.php" id="leaveForm" autocomplete="off">

<!-- ═══════════════════════════════════════════════════════════
     CARD 1 — Employee Selection
     ═══════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white py-2">
        <strong><i class="bi bi-person me-1"></i>Employee Selection</strong>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Employee Name <span class="text-danger">*</span></label>
            <select name="employee_id" id="selEmp" class="form-select" required>
                <option value="">— Choose Employee —</option>
                <?php while($e=$employees->fetch_assoc()): ?>
                <option value="<?=$e['id']?>"
                        data-pos="<?=h($e['position']??'')?>"
                        data-dept="<?=h($e['dept']??'')?>"
                        data-vl="<?=$e['vacation_leave_balance']?>"
                        data-sl="<?=$e['sick_leave_balance']?>"><?=h($e['employee_name'])?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <!-- Info readout -->
        <div id="empInfo" class="d-none">
            <div class="row g-2">
                <div class="col-sm-4"><div class="border rounded p-2 small"><span class="text-muted d-block">Position</span><strong id="infoPos">—</strong></div></div>
                <div class="col-sm-4"><div class="border rounded p-2 small"><span class="text-muted d-block">Department</span><strong id="infoDept">—</strong></div></div>
                <div class="col-sm-2"><div class="border rounded p-2 small text-center"><span class="text-muted d-block">VL Bal.</span><strong id="infoVL" class="text-primary">—</strong></div></div>
                <div class="col-sm-2"><div class="border rounded p-2 small text-center"><span class="text-muted d-block">SL Bal.</span><strong id="infoSL" class="text-danger">—</strong></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     CARD 2 — Application Details
     ═══════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white py-2">
        <strong><i class="bi bi-file-earmark-text me-1"></i>Application Details</strong>
    </div>
    <div class="card-body">

        <!-- Leave Type -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
                <select name="leave_type" id="selType" class="form-select" required>
                    <option value="">— Select Leave Type —</option>
                    <?php foreach(leaveTypes() as $lt): ?>
                    <option value="<?=h($lt)?>"><?=h($lt)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <div id="blkOtherType" class="d-none">
                    <label class="form-label small fw-semibold">Specify (Others)</label>
                    <input type="text" name="other_leave_type" class="form-control" placeholder="e.g. Monetization of Leave Credits">
                </div>
            </div>
        </div>

        <!-- Conditional detail blocks -->
        <div id="blkVacation" class="d-none mb-3 p-3 bg-light rounded">
            <label class="form-label small fw-semibold">Vacation Location</label>
            <div class="d-flex gap-3 mb-2">
                <div class="form-check"><input class="form-check-input" type="radio" name="vacation_detail" value="Within the Philippines" id="vacPH">
                    <label class="form-check-label" for="vacPH">Within the Philippines</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="vacation_detail" value="Abroad" id="vacAbroad">
                    <label class="form-check-label" for="vacAbroad">Abroad</label></div>
            </div>
            <input type="text" name="vacation_location" class="form-control form-control-sm" placeholder="Specify location (optional)">
        </div>

        <div id="blkSick" class="d-none mb-3 p-3 bg-light rounded">
            <label class="form-label small fw-semibold">Illness Details</label>
            <div class="d-flex gap-3 mb-2">
                <div class="form-check"><input class="form-check-input" type="radio" name="sick_detail" value="In Hospital" id="sickHosp">
                    <label class="form-check-label" for="sickHosp">In Hospital</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="sick_detail" value="Out Patient" id="sickOP">
                    <label class="form-check-label" for="sickOP">Out Patient</label></div>
            </div>
            <input type="text" name="sick_illness" class="form-control form-control-sm" placeholder="Specify illness (optional)">
        </div>

        <div id="blkStudy" class="d-none mb-3 p-3 bg-light rounded">
            <label class="form-label small fw-semibold">Study Purpose</label>
            <div class="form-check"><input class="form-check-input" type="radio" name="study_detail" value="Masters Degree" id="studyMS">
                <label class="form-check-label" for="studyMS">Completion of Master's Degree</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="study_detail" value="Board Exam Review" id="studyBrd">
                <label class="form-check-label" for="studyBrd">BAR / Board Examination Review</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="study_detail" value="Other" id="studyOth">
                <label class="form-check-label" for="studyOth">Other (specify below)</label></div>
        </div>

        <div id="blkOtherDetail" class="d-none mb-3">
            <label class="form-label small fw-semibold">Additional Details</label>
            <textarea name="other_detail" class="form-control" rows="2"></textarea>
        </div>

        <div id="blkNoDetail" class="text-muted small py-2 text-center">
            <i class="bi bi-info-circle me-1"></i>Select a Leave Type to see detail fields.
        </div>

        <hr class="my-3">

        <!-- Duration & Dates -->
        <h6 class="fw-semibold mb-3"><i class="bi bi-calendar-range me-2 text-primary"></i>Duration &amp; Dates</h6>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="date_start" id="dateStart" class="form-control" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                <input type="date" name="date_end" id="dateEnd" class="form-control" required>
            </div>
        </div>

        <div id="dayCalc" class="alert alert-info py-2 small d-none">
            <i class="bi bi-calculator me-1"></i>
            <strong>Working Days:</strong> <span id="calcDays" class="fw-bold fs-5">0</span>
            <span id="calcWarn" class="text-danger d-none ms-2 fw-bold">(Exceeds available credits!)</span>
        </div>
        <div class="mb-0">
            <label class="form-label small fw-semibold">Working Days <span class="text-muted fw-normal">(auto-calculated, editable)</span></label>
            <input type="number" name="working_days" id="workingDays" class="form-control" step="0.5" min="0.5" required value="">
            <div class="form-text">Auto-filled from dates (Mon–Fri). Override for half-days.</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     CARD 3 — Credit Deduction
     ═══════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-2">
        <strong><i class="bi bi-database me-1"></i>Credit Deduction</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Charge Leave To <span class="text-danger">*</span></label>
                <select name="charge_to" id="selCharge" class="form-select" required>
                    <option value="Vacation Leave">Vacation Leave Balance</option>
                    <option value="Sick Leave">Sick Leave Balance</option>
                    <option value="Leave Without Pay">Leave Without Pay (no deduction)</option>
                </select>
                <div class="form-text">Auto-set based on leave type. Override for LWOP or special cases.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Commutation</label>
                <div class="mt-1">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="commutation" value="Requested" id="comYes">
                        <label class="form-check-label" for="comYes">Requested</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="commutation" value="Not Requested" id="comNo" checked>
                        <label class="form-check-label" for="comNo">Not Requested</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label small fw-semibold">Remarks <span class="text-muted fw-normal">(Optional)</span></label>
            <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
        </div>
    </div>
</div>

<!-- Submit -->
<div class="d-grid gap-2 mb-4">
    <button type="submit" class="btn btn-primary btn-lg">
        <i class="bi bi-save me-1"></i>Save Leave Application
    </button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selEmp   = document.getElementById('selEmp');
    const selType  = document.getElementById('selType');
    const selCharge= document.getElementById('selCharge');
    const dateS    = document.getElementById('dateStart');
    const dateE    = document.getElementById('dateEnd');
    const dayCalc  = document.getElementById('dayCalc');
    const calcDays = document.getElementById('calcDays');
    const calcWarn = document.getElementById('calcWarn');
    const wdInput  = document.getElementById('workingDays');
    const empInfo  = document.getElementById('empInfo');

    // Detail blocks
    const blocks = {
        vacation: document.getElementById('blkVacation'),
        sick:     document.getElementById('blkSick'),
        study:    document.getElementById('blkStudy'),
        otherD:   document.getElementById('blkOtherDetail'),
        otherT:   document.getElementById('blkOtherType'),
        none:     document.getElementById('blkNoDetail'),
    };

    let empVL = 0, empSL = 0;

    /* ── Employee select ─────────────────────────────────── */
    selEmp.addEventListener('change', () => {
        const opt = selEmp.selectedOptions[0];
        if (selEmp.value) {
            empVL = parseFloat(opt.dataset.vl);
            empSL = parseFloat(opt.dataset.sl);
            document.getElementById('infoPos').textContent  = opt.dataset.pos || '—';
            document.getElementById('infoDept').textContent = opt.dataset.dept || '—';
            document.getElementById('infoVL').textContent   = empVL.toFixed(3);
            document.getElementById('infoSL').textContent   = empSL.toFixed(3);
            empInfo.classList.remove('d-none');
        } else {
            empInfo.classList.add('d-none');
        }
        recheckWarn();
    });

    /* ── Leave type → show / hide detail blocks ──────────── */
    selType.addEventListener('change', () => {
        const t = selType.value;
        Object.values(blocks).forEach(b => b.classList.add('d-none'));

        if (t === 'Vacation Leave' || t === 'Mandatory/Forced Leave' || t === 'Special Privilege Leave') {
            blocks.vacation.classList.remove('d-none');
        } else if (t === 'Sick Leave') {
            blocks.sick.classList.remove('d-none');
        } else if (t === 'Study Leave') {
            blocks.study.classList.remove('d-none');
            blocks.otherD.classList.remove('d-none');
        } else if (t === 'Others') {
            blocks.otherT.classList.remove('d-none');
            blocks.otherD.classList.remove('d-none');
        } else if (t) {
            blocks.otherD.classList.remove('d-none');   // generic remarks
        } else {
            blocks.none.classList.remove('d-none');
        }

        // Auto-set Charge To
        if (t === 'Sick Leave') {
            selCharge.value = 'Sick Leave';
        } else {
            selCharge.value = 'Vacation Leave';
        }
        recheckWarn();
    });

    /* ── Date change → compute business days ─────────────── */
    dateS.addEventListener('change', recalc);
    dateE.addEventListener('change', recalc);
    selCharge.addEventListener('change', recheckWarn);

    function countBD(s, e) {
        let c = 0, d = new Date(s), end = new Date(e);
        while (d <= end) { if (d.getDay()!==0 && d.getDay()!==6) c++; d.setDate(d.getDate()+1); }
        return c;
    }

    function recalc() {
        if (dateS.value && dateE.value) {
            const days = countBD(dateS.value, dateE.value);
            calcDays.textContent = days;
            wdInput.value = days;
            dayCalc.classList.remove('d-none');
            recheckWarn();
        } else {
            dayCalc.classList.add('d-none');
        }
    }

    function recheckWarn() {
        const days = parseFloat(wdInput.value) || 0;
        const charge = selCharge.value;
        let avail = charge === 'Sick Leave' ? empSL : empVL;
        if (charge === 'Leave Without Pay') avail = 9999;
        calcWarn.classList.toggle('d-none', days <= avail || !selEmp.value);
    }

    wdInput.addEventListener('input', recheckWarn);
});
</script>
</body>
</html>
