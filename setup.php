<?php
/**
 * ============================================================
 * PHO-Palawan Leave Management System
 * setup.php — One-time database setup
 * ============================================================
 * Run this via browser:  http://localhost/PHO/Leave%20Tracker/setup.php
 * Or via CLI:            php setup.php
 *
 * What it does:
 *  1. Creates the database "palawan_leave_db" and all tables
 *  2. Seeds departments
 *  3. Sets a proper bcrypt hash for the admin password (admin123)
 *
 * After running, delete this file or restrict access.
 * ============================================================
 */

$isCLI = php_sapi_name() === 'cli';
function out($msg, $isCLI) {
    echo $isCLI ? strip_tags($msg) . "\n" : $msg . "<br>";
}

if (!$isCLI) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup — PHO Palawan</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body class="bg-light"><div class="container py-5" style="max-width:700px"><div class="card shadow-sm"><div class="card-body">';
    echo '<h4 class="fw-bold mb-3">PHO-Palawan Leave System — Setup</h4>';
}

/* ── Connect WITHOUT specifying a database ─────────────────── */
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    out('<span class="text-danger">MySQL connection failed: ' . $conn->connect_error . '</span>', $isCLI);
    exit(1);
}
out('<span class="text-success">✓</span> Connected to MySQL.', $isCLI);

/* ── Read and execute the SQL schema ──────────────────────── */
$sqlFile = __DIR__ . '/palawan_leave_db.sql';
if (!file_exists($sqlFile)) {
    out('<span class="text-danger">✗ SQL file not found: ' . $sqlFile . '</span>', $isCLI);
    exit(1);
}

$sql = file_get_contents($sqlFile);

// Remove comment lines (-- ...) so multi_query doesn't choke
$sql = preg_replace('/^\s*--.*$/m', '', $sql);

$conn->multi_query($sql);
// Consume all result sets from multi_query
$error = '';
do {
    if ($conn->errno) { $error = $conn->error; break; }
    if ($result = $conn->store_result()) { $result->free(); }
} while ($conn->next_result());

if ($error) {
    out('<span class="text-danger">✗ SQL error: ' . htmlspecialchars($error) . '</span>', $isCLI);
    exit(1);
}
out('<span class="text-success">✓</span> Database <strong>palawan_leave_db</strong> created with all tables.', $isCLI);

/* ── Now connect to the new database to update password ─── */
$conn->close();
$conn = new mysqli('localhost', 'root', '', 'palawan_leave_db');
if ($conn->connect_error) {
    out('<span class="text-danger">✗ Could not connect to palawan_leave_db: ' . $conn->connect_error . '</span>', $isCLI);
    exit(1);
}

/* ── Generate bcrypt hash for admin123 ───────────────────── */
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->bind_param('s', $hash);
$stmt->execute();
out('<span class="text-success">✓</span> Admin password hash set. <code>admin / admin123</code>', $isCLI);

/* ── Verify tables exist ─────────────────────────────────── */
$tables = ['admin_users', 'departments', 'employees', 'leave_applications'];
$result = $conn->query("SHOW TABLES");
$found = [];
while ($row = $result->fetch_row()) { $found[] = $row[0]; }
foreach ($tables as $t) {
    $ok = in_array($t, $found);
    out(($ok ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>') . " Table <strong>$t</strong>", $isCLI);
}

/* ── Count seeded departments ────────────────────────────── */
$cnt = $conn->query("SELECT COUNT(*) AS c FROM departments")->fetch_assoc()['c'];
out("<span class='text-success'>✓</span> Departments seeded: <strong>$cnt</strong>", $isCLI);

$conn->close();

out('', $isCLI);
out('<span class="text-info fw-bold">Setup complete!</span> You can now log in at <a href="login.php">login.php</a>.', $isCLI);
out('<span class="text-warning small">⚠ Delete or rename this file after setup.</span>', $isCLI);

if (!$isCLI) {
    echo '</div></div></div></body></html>';
}
