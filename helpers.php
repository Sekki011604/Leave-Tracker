<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Helper Functions
 * ============================================================
 */

/* ── Build display name from split columns ─────────────────── */
function fullName(array $row): string
{
    return trim(implode(' ', array_filter([
        $row['title'] ?? '',
        $row['first_name'] ?? '',
        $row['middle_name'] ?? '',
        $row['last_name'] ?? '',
        $row['suffix'] ?? '',
    ])));
}

/* ── All CS Form 6 Leave Types ────────────────────────────── */
function leaveTypes(): array
{
    return [
        'Vacation Leave',
        'Mandatory/Forced Leave',
        'Sick Leave',
        'Maternity Leave',
        'Paternity Leave',
        'Special Privilege Leave',
        'Solo Parent Leave',
        'Study Leave',
        '10-Day VAWC Leave',
        'Rehabilitation Leave',
        'Special Benefits for Women',
        'Calamity Leave',
        'Adoption Leave',
        'Others',
    ];
}

/**
 * Which balance column a leave type is normally charged to.
 * Admin can override to 'Leave Without Pay' on the form.
 */
function defaultChargeFor(string $type): string
{
    return match ($type) {
        'Sick Leave'  => 'Sick Leave',
        default       => 'Vacation Leave',
    };
}

/* ── Count business days (Mon–Fri) inclusive ───────────────── */
function countBusinessDays(string $start, string $end): int
{
    $begin  = new DateTime($start);
    $finish = new DateTime($end);
    $finish->modify('+1 day');

    $days = 0;
    $period = new DatePeriod($begin, new DateInterval('P1D'), $finish);
    foreach ($period as $d) {
        if ((int)$d->format('N') < 6) $days++;
    }
    return $days;
}

/* ── Flash messages ───────────────────────────────────────── */
function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function renderFlash(): string
{
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $ico = match ($f['type']) {
        'success' => 'check-circle-fill',
        'danger'  => 'exclamation-triangle-fill',
        'warning' => 'exclamation-circle-fill',
        default   => 'info-circle-fill',
    };
    return '<div class="alert alert-' . h($f['type']) . ' alert-dismissible fade show py-2 mb-3" role="alert">'
         . '<i class="bi bi-' . $ico . ' me-1"></i>' . h($f['msg'])
         . '<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button></div>';
}

/* ── Output sanitiser ─────────────────────────────────────── */
function h(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/* ── Format peso ──────────────────────────────────────────── */
function peso(float $v): string
{
    return '₱ ' . number_format($v, 2);
}

/* ── Badge colour for leave type ──────────────────────────── */
function leaveTypeBadge(string $type): string
{
    return match ($type) {
        'Vacation Leave'            => 'success',
        'Sick Leave'                => 'info text-dark',
        'Mandatory/Forced Leave'    => 'secondary',
        'Maternity Leave'           => 'danger',
        'Paternity Leave'           => 'primary',
        'Special Privilege Leave'   => 'primary',
        'Solo Parent Leave'         => 'warning text-dark',
        'Study Leave'               => 'dark',
        'Calamity Leave'            => 'danger',
        default                     => 'primary',
    };
}

/* ── Badge colour for status ──────────────────────────────── */
function statusBadge(string $s): string
{
    return match ($s) {
        'Approved'    => 'success',
        'Disapproved' => 'danger',
        default       => 'warning',
    };
}
