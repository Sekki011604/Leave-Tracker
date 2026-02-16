<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * save_leave.php — Record leave application (Simple Logbook)
 * ============================================================
 *
 * Logic:
 *   1. Validate inputs.
 *   2. Insert leave_applications record with all Form 6 fields.
 *   3. Redirect with flash message.
 *
 * No credit checking or balance deduction — purely a logbook.
 */
require_once 'auth.php';
require_once 'db_connect.php';
require_once 'helpers.php';

/* ── Only accept POST ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: encode_leave.php');
    exit;
}

/* ── Gather inputs ─────────────────────────────────────────── */
$empId        = (int)   ($_POST['employee_id']   ?? 0);
$leaveType    = trim(   $_POST['leave_type']     ?? '');
$otherType    = trim(   $_POST['other_leave_type'] ?? '');
$vacDetail    =         $_POST['vacation_detail']  ?? null;
$vacLocation  = trim(   $_POST['vacation_location'] ?? '');
$sickDetail   =         $_POST['sick_detail']      ?? null;
$sickIllness  = trim(   $_POST['sick_illness']     ?? '');
$studyDetail  =         $_POST['study_detail']     ?? null;
$otherDetail  = trim(   $_POST['other_detail']     ?? '');
$dateStart    =         $_POST['date_start']       ?? '';
$dateEnd      =         $_POST['date_end']         ?? '';
$workingDays  = floatval($_POST['working_days']    ?? 0);
$commutation  =         $_POST['commutation']      ?? 'Not Requested';
$remarks      = trim(   $_POST['remarks']          ?? '');
$recordedBy   =         $_SESSION['admin_name']    ?? 'Admin';

/* ── Validate ──────────────────────────────────────────────── */
$errors = [];
if (!$empId)                          $errors[] = 'Select an employee.';
if ($leaveType === '')                $errors[] = 'Select a leave type.';
if ($dateStart === '' || $dateEnd==='') $errors[] = 'Select start and end dates.';
if ($dateStart > $dateEnd)            $errors[] = 'Start date is after end date.';
if ($workingDays <= 0)                $errors[] = 'Working days must be > 0.';

if (!empty($errors)) {
    setFlash('danger', implode(' ', $errors));
    header('Location: encode_leave.php');
    exit;
}

/* ── Verify employee exists & is active ────────────────────── */
$st = $conn->prepare("SELECT id FROM employees WHERE id=? AND status='Active'");
$st->bind_param('i', $empId);
$st->execute();
$emp = $st->get_result()->fetch_assoc();
$st->close();

if (!$emp) {
    setFlash('danger', 'Employee not found or inactive.');
    header('Location: encode_leave.php');
    exit;
}

/* ── Insert leave application ──────────────────────────────── */
try {
    $ins = $conn->prepare(
        "INSERT INTO leave_applications
            (employee_id, leave_type, other_leave_type,
             vacation_detail, vacation_location,
             sick_detail, sick_illness,
             study_detail, other_detail,
             date_start, date_end, working_days,
             commutation,
             status, recorded_by, remarks)
         VALUES (?,?,?, ?,?, ?,?, ?,?, ?,?,?, ?, 'Pending',?,?)"
    );

    $ins->bind_param(
        'issssssssssdsss',
        $empId, $leaveType, $otherType,
        $vacDetail, $vacLocation,
        $sickDetail, $sickIllness,
        $studyDetail, $otherDetail,
        $dateStart, $dateEnd, $workingDays,
        $commutation,
        $recordedBy, $remarks
    );
    $ins->execute();
    $ins->close();

    $msg = 'Leave application saved — '
         . $workingDays . ' day(s) of ' . $leaveType
         . ' (' . date('M d', strtotime($dateStart)) . ' – ' . date('M d, Y', strtotime($dateEnd)) . ').';
    setFlash('success', $msg);
    header('Location: index.php');
    exit;

} catch (Exception $ex) {
    setFlash('danger', 'Database error: ' . $ex->getMessage());
    header('Location: encode_leave.php');
    exit;
}
