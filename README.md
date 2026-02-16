# PHO-Palawan Leave Management & Tracking System
### Digital Civil Service Form No. 6

A single-user web-based **Leave Tracker / Digital Logbook** built for the **Provincial Health Office of Palawan** using XAMPP (PHP + MySQL/MariaDB).

---

## Features

| Feature | Description |
|---------|-------------|
| CS Form 6 Compliance | Leave types, details, commutation, and credit tracking follow Civil Service Form No. 6 |
| Separate VL / SL Balances | Vacation Leave and Sick Leave credits tracked independently |
| 14 Leave Types | Vacation, Mandatory/Forced, Sick, Maternity, Paternity, Special Privilege, Solo Parent, Study, 10-Day VAWC, Rehabilitation, Special Benefits for Women, Calamity, Adoption, Others |
| Dynamic Form Inputs | Form fields change based on selected leave type (vacation location, illness details, study purpose, etc.) |
| Approve / Disapprove | Pending applications can be approved or disapproved; disapproval restores deducted credits |
| Leave Without Pay | Override charge to LWOP — no credit deduction |
| Section 7A Ledger | Per-employee Certificate of Leave Credits with dual VL/SL columns |
| Dashboard | At-a-glance stats, on-leave-today, low-credit alerts, recent applications |
| Employee CRUD | Full employee management with department assignment |
| Print-Ready | Print button on ledger and leave detail pages |

---

## Requirements

- **XAMPP** (Apache + MySQL/MariaDB + PHP 8+)
- A modern web browser

---

## Installation

1. Copy the `Leave Tracker` folder into `xampp/htdocs/PHO/`
2. Start **Apache** and **MySQL** from the XAMPP Control Panel
3. Open your browser and go to:
   ```
   http://localhost/PHO/Leave%20Tracker/setup.php
   ```
4. Setup will create the database, tables, seed departments, and set the admin password
5. After setup, navigate to:
   ```
   http://localhost/PHO/Leave%20Tracker/login.php
   ```
6. **Delete or rename `setup.php`** after installation

---

## Default Login

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `admin123` |

---

## File Structure

```
Leave Tracker/
├── assets/
│   └── css/
│       └── style.css           # Custom styles (PHO color palette, print)
├── auth.php                    # Session guard
├── db_connect.php              # MySQLi connection
├── employees.php               # Employee CRUD
├── employee_ledger.php         # Section 7A — Certificate of Leave Credits
├── encode_leave.php            # CS Form 6 — Encode new leave application
├── helpers.php                 # Leave types, business-day calc, utilities
├── index.php                   # Dashboard (stats, approve/disapprove)
├── leave_history.php           # Filterable leave history masterlist
├── login.php                   # Admin login page
├── logout.php                  # Session destroy
├── navbar.php                  # Shared navigation bar
├── palawan_leave_db.sql        # Database schema & seed data
├── save_leave.php              # POST handler — saves leave & deducts credits
├── setup.php                   # One-time DB setup script
├── view_leave.php              # Detailed view of a leave application
└── README.md                   # This file
```

---

## Database: `palawan_leave_db`

| Table | Purpose |
|-------|---------|
| `admin_users` | HR Officer login (single user) |
| `departments` | 16 PHO-Palawan departments |
| `employees` | Staff with separate VL & SL balances |
| `leave_applications` | All CS Form 6 fields, balance snapshots, status |

---

## Tech Stack

- **PHP 8+** (procedural, no framework)
- **MySQL / MariaDB** via mysqli
- **Bootstrap 5.3.2** (CDN)
- **Bootstrap Icons 1.11.3** (CDN)

---

*Provincial Health Office of Palawan — Leave Management System*
