<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System — Database Connection
 * ============================================================
 * XAMPP defaults.  Adjust DB_PASS if your root has a password.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'palawan_leave_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(
        '<div style="font-family:Segoe UI,sans-serif;padding:40px;text-align:center">'
      . '<h3 style="color:#c0392b">Database Connection Failed</h3>'
      . '<p>' . htmlspecialchars($conn->connect_error) . '</p>'
      . '<p>Ensure XAMPP MySQL is running and <code>palawan_leave_db</code> exists.</p>'
      . '<p><a href="setup.php">Run Setup</a></p></div>'
    );
}

$conn->set_charset('utf8mb4');
