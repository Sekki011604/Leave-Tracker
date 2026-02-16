-- ============================================================
-- PHO-Palawan  |  Leave Management & Tracking System
-- Digital Civil Service Form No. 6
-- ============================================================
-- Run once via  http://localhost/PHO/Leave%20Tracker/setup.php
-- or import through phpMyAdmin.
-- ============================================================

DROP DATABASE IF EXISTS palawan_leave_db;

CREATE DATABASE palawan_leave_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE palawan_leave_db;

-- ============================================================
-- 1.  ADMIN / HR OFFICER  (single-user access)
-- ============================================================
CREATE TABLE admin_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(120) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- password = admin123  (bcrypt — will be replaced by setup.php)
INSERT INTO admin_users (username, password, full_name) VALUES
('admin', '--placeholder--', 'HR Officer – PHO Palawan');

-- ============================================================
-- 2.  DEPARTMENTS  (lookup table)
-- ============================================================
CREATE TABLE departments (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO departments (name) VALUES
('Office of the Provincial Health Officer'),
('Administrative Division'),
('Epidemiology & Surveillance Unit'),
('Health Promotion Unit'),
('Local Health Support Division'),
('Dental Health Unit'),
('Nutrition Unit'),
('Environmental & Occupational Health Unit'),
('Pharmacy Unit'),
('Laboratory Unit'),
('Human Resource Unit'),
('Finance Unit'),
('Records Unit'),
('Rural Health Unit'),
('District Hospital'),
('General');

-- ============================================================
-- 3.  EMPLOYEES
-- ============================================================
CREATE TABLE employees (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    employee_name           VARCHAR(150) NOT NULL,
    position                VARCHAR(120) DEFAULT NULL,
    salary                  DECIMAL(12,2) DEFAULT 0.00,
    department_id           INT          DEFAULT NULL,

    -- CS Form 6 §7A — separate credit balances
    vacation_leave_balance  DECIMAL(6,3) NOT NULL DEFAULT 15.000,
    sick_leave_balance      DECIMAL(6,3) NOT NULL DEFAULT 15.000,

    status                  ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    date_hired              DATE DEFAULT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4.  LEAVE APPLICATIONS  (Digital CS Form No. 6)
-- ============================================================
CREATE TABLE leave_applications (
    id                  INT AUTO_INCREMENT PRIMARY KEY,

    employee_id         INT          NOT NULL,

    -- ---- Form §6-A : TYPE OF LEAVE --------------------------
    leave_type          VARCHAR(60)  NOT NULL,
    -- Allowed values enforced by app:
    --   Vacation Leave, Mandatory/Forced Leave, Sick Leave,
    --   Maternity Leave, Paternity Leave, Special Privilege Leave,
    --   Solo Parent Leave, Study Leave, 10-Day VAWC Leave,
    --   Rehabilitation Leave, Special Benefits for Women,
    --   Calamity Leave, Adoption Leave, Others
    other_leave_type    VARCHAR(150) DEFAULT NULL,

    -- ---- Form §6-B : DETAILS OF LEAVE ----------------------
    --   Vacation / SPL
    vacation_detail     ENUM('Within the Philippines','Abroad') DEFAULT NULL,
    vacation_location   VARCHAR(255) DEFAULT NULL,

    --   Sick
    sick_detail         ENUM('In Hospital','Out Patient') DEFAULT NULL,
    sick_illness        VARCHAR(255) DEFAULT NULL,

    --   Study
    study_detail        ENUM('Masters Degree','Board Exam Review','Other') DEFAULT NULL,

    --   Others / Monetization
    other_detail        TEXT         DEFAULT NULL,

    -- ---- Form §6-C : INCLUSIVE DATES / DAYS -----------------
    date_start          DATE         NOT NULL,
    date_end            DATE         NOT NULL,
    working_days        DECIMAL(5,1) NOT NULL DEFAULT 1.0,

    -- ---- Form §6-D : COMMUTATION ----------------------------
    commutation         ENUM('Requested','Not Requested') NOT NULL DEFAULT 'Not Requested',

    -- ---- Credit deduction tracking --------------------------
    charge_to           ENUM('Vacation Leave','Sick Leave','Leave Without Pay') NOT NULL DEFAULT 'Vacation Leave',
    days_deducted       DECIMAL(5,1) NOT NULL DEFAULT 0.0,
    balance_before_vl   DECIMAL(6,3) DEFAULT NULL,
    balance_before_sl   DECIMAL(6,3) DEFAULT NULL,
    balance_after_vl    DECIMAL(6,3) DEFAULT NULL,
    balance_after_sl    DECIMAL(6,3) DEFAULT NULL,

    -- ---- Recommendation / Approval --------------------------
    status              ENUM('Pending','Approved','Disapproved') NOT NULL DEFAULT 'Pending',
    disapproval_reason  TEXT         DEFAULT NULL,
    approved_days       DECIMAL(5,1) DEFAULT NULL,
    approved_by         VARCHAR(150) DEFAULT NULL,

    -- ---- Meta -----------------------------------------------
    recorded_by         VARCHAR(120) DEFAULT NULL,
    remarks             TEXT         DEFAULT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index for common queries
CREATE INDEX idx_leave_employee   ON leave_applications(employee_id);
CREATE INDEX idx_leave_dates      ON leave_applications(date_start, date_end);
CREATE INDEX idx_leave_status     ON leave_applications(status);
