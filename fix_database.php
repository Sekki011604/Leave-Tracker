<?php
// fix_database.php - One click database cleaner
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "palawan_leave_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Starting Database Repair...</h1>";

$sql_commands = [
    // 1. Patayin muna ang foreign key checks para walang error
    "SET FOREIGN_KEY_CHECKS = 0",

    // 2. Linisin ang mga tables (Start Fresh)
    "TRUNCATE TABLE employees",
    "TRUNCATE TABLE leave_applications",
    
    // 3. Tanggalin ang Foreign Key sa employees (kung meron)
    "ALTER TABLE employees DROP FOREIGN KEY IF EXISTS employees_ibfk_1",

    // 4. Baguhin ang structure ng Employees Table (Logbook Style)
    //    Ginagawang TEXT ang department, at tinatanggal ang salary/balances
    "ALTER TABLE employees 
     MODIFY COLUMN department VARCHAR(255) NOT NULL,
     DROP COLUMN IF EXISTS department_id,
     DROP COLUMN IF EXISTS monthly_salary,
     DROP COLUMN IF EXISTS date_hired,
     DROP COLUMN IF EXISTS vl_balance,
     DROP COLUMN IF EXISTS sl_balance",

    // 5. Ibalik ang foreign key checks
    "SET FOREIGN_KEY_CHECKS = 1"
];

foreach ($sql_commands as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success: " . htmlspecialchars(substr($sql, 0, 50)) . "...</p>";
    } else {
        // Ignore "DROP" errors kung wala naman yung column
        echo "<p style='color:orange'>Notice: " . $conn->error . "</p>";
    }
}

echo "<h2>✅ Database Fixed! Pwede mo na i-delete ang file na ito.</h2>";
$conn->close();
?>