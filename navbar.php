<?php
/**
 * Shared navigation bar — included on every protected page.
 */
$_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-pho shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="https://scontent.fmnl13-2.fna.fbcdn.net/v/t39.30808-6/239470877_366781958265636_3035717695931803955_n.jpg?_nc_cat=107&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeH65r2kvwylISgzQDbdHykxqy208_2sh0SrLbTz_ayHRGh8rkZJ4gsiUhRy8dEAVrNNYkJrTb-lYCYrs-HtLJBd&_nc_ohc=mnUECLTMsqIQ7kNvwHaKmP1&_nc_oc=Adk5dH951M3QbhLCILVP5JzqUXB_Y3HllpVaQppI3LB_xxeYkJSiMXWgjLpdZkNabsI&_nc_zt=23&_nc_ht=scontent.fmnl13-2.fna&_nc_gid=B-UCbRP8_2CNNW799nsAmA&oh=00_AfvVkIUxWUDJ50CZ1yI660nRZfkmF779ROMdV5mICCW7Nw&oe=699849A9"
                 alt="PHO Palawan Logo" class="d-inline-block align-text-top rounded-circle" style="height: 40px; width: auto;">
            <span class="fw-bold d-none d-sm-inline">PHO-Palawan Leave Tracker</span>
            <span class="fw-bold d-sm-none">PHO Leave</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $_page==='index.php'?'active':'' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $_page==='encode_leave.php'?'active':'' ?>" href="encode_leave.php">
                        <i class="bi bi-pencil-square me-1"></i>Encode Leave</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $_page==='employees.php'?'active':'' ?>" href="employees.php">
                        <i class="bi bi-people me-1"></i>Employees</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $_page==='leave_history.php'?'active':'' ?>" href="leave_history.php">
                        <i class="bi bi-clock-history me-1"></i>Leave History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $_page==='employee_ledger.php'?'active':'' ?>" href="employee_ledger.php">
                        <i class="bi bi-journal-bookmark me-1"></i>Leave Ledger</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= h($_SESSION['admin_name'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
