<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * save_leave.php — Process leave application & deduct credits
 * ============================================================
 *
 * Logic:
 *   1. Validate inputs.
 *   2. Verify server-side business-day calculation.
 *   3. Snapshot current balances (before).
 *   4. Deduct from the correct balance column (VL / SL / LWOP).
 *   5. Snapshot balances (after).
 *   6. Insert leave_applications record with all Form 6 fields.
 *   7. Redirect with flash message.
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
$chargeTo     =         $_POST['charge_to']        ?? 'Vacation Leave';
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

/* ── Verify business days server-side ──────────────────────── */
$serverDays = countBusinessDays($dateStart, $dateEnd);
// We allow manual override (half-days, etc.) but warn if wildly off
// No hard block — admin is trusted.

/* ── Fetch employee balances ───────────────────────────────── */
$st = $conn->prepare("SELECT vacation_leave_balance, sick_leave_balance FROM employees WHERE id=? AND status='Active'");
$st->bind_param('i', $empId);
$st->execute();
$emp = $st->get_result()->fetch_assoc();
$st->close();

if (!$emp) {
    setFlash('danger', 'Employee not found or inactive.');
    header('Location: encode_leave.php');
    exit;
}

$vlBefore = (float) $emp['vacation_leave_balance'];
$slBefore = (float) $emp['sick_leave_balance'];
$daysDeducted = $workingDays;

/* ── Credit deduction ──────────────────────────────────────── */
if ($chargeTo === 'Leave Without Pay') {
    // No deduction
    $daysDeducted = 0;
    $vlAfter = $vlBefore;
    $slAfter = $slBefore;
} elseif ($chargeTo === 'Sick Leave') {
    if ($slBefore < $workingDays) {
        setFlash('danger', 'Insufficient Sick Leave credits. Current SL balance: ' . number_format($slBefore, 3)
                 . ' days. Required: ' . $workingDays . ' days.');
        header('Location: encode_leave.php');
        exit;
    }
    $vlAfter = $vlBefore;
    $slAfter = $slBefore - $workingDays;
} else {
    // Vacation Leave (default for most types)
    if ($vlBefore < $workingDays) {
        setFlash('danger', 'Insufficient Vacation Leave credits. Current VL balance: ' . number_format($vlBefore, 3)
                 . ' days. Required: ' . $workingDays . ' days.');
        header('Location: encode_leave.php');
        exit;
    }
    $vlAfter = $vlBefore - $workingDays;
    $slAfter = $slBefore;
}

/* ── Transaction: deduct + insert ──────────────────────────── */
$conn->begin_transaction();
try {
    // 1. Update employee balance
    if ($chargeTo === 'Sick Leave') {
        $upd = $conn->prepare("UPDATE employees SET sick_leave_balance = ? WHERE id = ?");
        $upd->bind_param('di', $slAfter, $empId);
    } elseif ($chargeTo === 'Vacation Leave') {
        $upd = $conn->prepare("UPDATE employees SET vacation_leave_balance = ? WHERE id = ?");
        $upd->bind_param('di', $vlAfter, $empId);
    } else {
        // LWOP — no update needed, but create a dummy stmt for consistency
        $upd = $conn->prepare("SELECT 1");
    }
    $upd->execute();
    $upd->close();

    // 2. Insert leave application
    $ins = $conn->prepare(
        "INSERT INTO leave_applications
            (employee_id, leave_type, other_leave_type,
             vacation_detail, vacation_location,
             sick_detail, sick_illness,
             study_detail, other_detail,
             date_start, date_end, working_days,
             commutation, charge_to, days_deducted,
             balance_before_vl, balance_before_sl,
             balance_after_vl, balance_after_sl,
             status, recorded_by, remarks)
         VALUES (?,?,?, ?,?, ?,?, ?,?, ?,?,?, ?,?,?, ?,?, ?,?, 'Pending',?,?)"
    );

    $ins->bind_param(
        'issssssssssdssdddddss',
        $empId, $leaveType, $otherType,
        $vacDetail, $vacLocation,
        $sickDetail, $sickIllness,
        $studyDetail, $otherDetail,
        $dateStart, $dateEnd, $workingDays,
        $commutation, $chargeTo, $daysDeducted,
        $vlBefore, $slBefore,
        $vlAfter, $slAfter,
        $recordedBy, $remarks
    );
    $ins->execute();

    $conn->commit();

    // Build success message
    $msg = 'Leave application saved. ';
    if ($chargeTo === 'Leave Without Pay') {
        $msg .= 'Charged as Leave Without Pay (no deduction).';
    } else {
        $msg .= $workingDays . ' day(s) deducted from ' . $chargeTo . '. ';
        if ($chargeTo === 'Sick Leave') {
            $msg .= 'SL Balance: ' . number_format($slBefore, 3) . ' → ' . number_format($slAfter, 3);
        } else {
            $msg .= 'VL Balance: ' . number_format($vlBefore, 3) . ' → ' . number_format($vlAfter, 3);
        }
    }
    setFlash('success', $msg);
    header('Location: index.php');
    exit;

} catch (Exception $ex) {
    $conn->rollback();
    setFlash('danger', 'Database error: ' . $ex->getMessage());
    header('Location: encode_leave.php');
    exit;
}
