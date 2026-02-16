<?php
/**
 * ============================================================
 * Auth guard — include at the top of every protected page.
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
