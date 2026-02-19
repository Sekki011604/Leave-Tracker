-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 04:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `palawan_leave_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$XpzbFxGIH6NElVeuHqqAReIQTNpT3li9tc3c9EicQwupQzQ07tO2q', 'Admin Officer', '2026-02-13 16:40:28');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(2, 'Administrative Division'),
(6, 'Dental Health Unit'),
(15, 'District Hospital'),
(8, 'Environmental & Occupational Health Unit'),
(3, 'Epidemiology & Surveillance Unit'),
(12, 'Finance Unit'),
(16, 'General'),
(4, 'Health Promotion Unit'),
(11, 'Human Resource Unit'),
(10, 'Laboratory Unit'),
(5, 'Local Health Support Division'),
(7, 'Nutrition Unit'),
(1, 'Office of the Provincial Health Officer'),
(9, 'Pharmacy Unit'),
(13, 'Records Unit'),
(14, 'Rural Health Unit');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `title`, `first_name`, `middle_name`, `last_name`, `suffix`, `position`, `department`, `status`) VALUES
(1, 'Dr.', 'Faye Erika', '', 'Querijero-Labrador', '', 'PHO-II', 'PHO Permanent Staff', 'Active'),
(2, 'Dr.', 'Mary Ann', 'H.', 'Navarro', '', 'PHO-I', 'PHO Permanent Staff', 'Active'),
(3, 'Dr.', 'Justyne', '', 'Barbosa', '', 'Medical Specialist IV', 'PHO Permanent Staff', 'Active'),
(4, '', 'Rico Laurence', 'D.', 'Cayabyab', '', 'SAO', 'PHO Permanent Staff', 'Active'),
(5, 'Dr.', 'Lilian', 'T.', 'Arcinas', '', 'Dentist-III', 'PHO Permanent Staff', 'Active'),
(6, '', 'Meyrick', 'J.', 'Garces', '', 'Nurse- IV', 'PHO Permanent Staff', 'Active'),
(7, 'Dr.', 'Reynaldo', 'E.', 'Magpantay', 'Jr.', 'Dentist-II', 'PHO Permanent Staff', 'Active'),
(8, 'Dr.', 'Celestina', '', 'Timoteo', '', 'Dentist-II', 'PHO Permanent Staff', 'Active'),
(9, '', 'Aileen', '', 'Balderian', '', 'Nurse- II', 'PHO Permanent Staff', 'Active'),
(10, '', 'Jenevil', '', 'Tombaga', '', 'Nurse- II', 'PHO Permanent Staff', 'Active'),
(11, '', 'Eileen Grace', '', 'Macabihag', '', 'Nurse II', 'PHO Permanent Staff', 'Active'),
(12, '', 'Lora Lou', '', 'Jagmis', '', 'Nurse II', 'PHO Permanent Staff', 'Active'),
(13, '', 'Mary Charrie', '', 'Mapanao', '', 'Nurse II', 'PHO Permanent Staff', 'Active'),
(14, '', 'Trixia', '', 'Roy', '', 'Nurse II', 'PHO Permanent Staff', 'Active'),
(15, '', 'Mina', '', 'Lim', '', 'Nutri-Diet. II', 'PHO Permanent Staff', 'Active'),
(16, '', 'Cyrus Cecilio', 'A.', 'Caabay', '', 'Med Tech-II', 'PHO Permanent Staff', 'Active'),
(17, '', 'Biverly Jane', 'B.', 'Pagayona', '', 'Med Tech- II', 'PHO Permanent Staff', 'Active'),
(18, '', 'Darelle Jaide', '', 'Sanchez', '', 'Nutritionist Dietitian II', 'PHO Permanent Staff', 'Active'),
(19, '', 'Manilyn', 'M.', 'De Guzman', '', 'Nurse I', 'PHO Permanent Staff', 'Active'),
(20, '', 'Anne France', 'S.', 'Imperial', '', 'Nutritionist Dietitian II', 'PHO Permanent Staff', 'Active'),
(21, '', 'Elizabeth', '', 'Calaor', '', 'Pharmacist- II', 'PHO Permanent Staff', 'Active'),
(22, '', 'Danika', '', 'Omapas', '', 'HEPO II', 'PHO Permanent Staff', 'Active'),
(23, '', 'Martin Adrian', 'C.', 'De Guzman', '', 'HEPO II', 'PHO Permanent Staff', 'Active'),
(24, '', 'Benny', 'D.', 'Osorio', '', 'Statistician- I', 'PHO Permanent Staff', 'Active'),
(25, '', 'Nelson', 'C.', 'Virgo', '', 'San Inspector- III', 'PHO Permanent Staff', 'Active'),
(26, '', 'Elvie', '', 'Redon- Lao', '', 'Midwife- II', 'PHO Permanent Staff', 'Active'),
(27, '', 'Leonor', 'B.', 'Maru', '', 'Midwife- II', 'PHO Permanent Staff', 'Active'),
(28, '', 'May', '', 'Verano', '', 'Midwife- II', 'PHO Permanent Staff', 'Active'),
(29, '', 'Diana Mei', '', 'Dangue', '', 'Midwife II', 'PHO Permanent Staff', 'Active'),
(30, '', 'Mykeanne Maej\'g', '', 'Gualdrapa', '', 'Med Tech I', 'PHO Permanent Staff', 'Active'),
(31, '', 'Ferdinand', '', 'Roque', '', 'Med Tech I', 'PHO Permanent Staff', 'Active'),
(32, '', 'Luzviminda', '', 'Endaya', '', 'Midwife II', 'PHO Permanent Staff', 'Active'),
(33, '', 'Maricel', 'C.', 'Solomon', '', 'Admin Asst. IV', 'PHO Permanent Staff', 'Active'),
(34, '', 'Eric Stephen', '', 'Arias', '', 'Admin Asst. III', 'PHO Permanent Staff', 'Active'),
(35, '', 'Richard', '', 'Roy', '', 'Admin Asst III', 'PHO Permanent Staff', 'Active'),
(36, '', 'Joy Anne May', '', 'Arevalo', '', 'Admin Asst. I', 'PHO Permanent Staff', 'Active'),
(37, '', 'Camille', '', 'Delos Santos', '', 'Admin Asst. I', 'PHO Permanent Staff', 'Active'),
(38, '', 'Danielle Anne', 'D.', 'Galaroza', '', 'Admin. Asst. I', 'PHO Permanent Staff', 'Active'),
(39, '', 'Edward', '', 'Tapang', '', 'Admin. Asst. I', 'PHO Permanent Staff', 'Active'),
(40, '', 'Janette', '', 'Ventura', '', 'Nsg. Attendant- I', 'PHO Permanent Staff', 'Active'),
(41, '', 'Glory Angelie', '', 'Abayan', '', 'Nsg. Attendant I', 'PHO Permanent Staff', 'Active'),
(42, '', 'Jesusa', 'O.', 'Argawanon', '', 'Nsg. Attendant I', 'PHO Permanent Staff', 'Active'),
(43, '', 'Nelson', '', 'Badenas', '', 'Admin Aide IV', 'PHO Permanent Staff', 'Active'),
(44, '', 'Alexis John', '', 'Dimla', '', 'Dental Aide', 'PHO Permanent Staff', 'Active'),
(45, '', 'Prince Kenneth', '', 'Argawanon', '', 'Dental Aide', 'PHO Permanent Staff', 'Active'),
(46, '', 'Emy', '', 'Dagaraga', '', 'Nsg. Attendant I', 'PHO Permanent Staff', 'Active'),
(47, '', 'Karen Joy', 'S.', 'Sadang', '', 'Dental Aide', 'PHO Permanent Staff', 'Active'),
(48, '', 'Saldy', '', 'Lucero', '', 'Admin Aide- III', 'PHO Permanent Staff', 'Active'),
(49, '', 'Eleazar', 'A.', 'Tablazon', '', 'Admin Aide- III', 'PHO Permanent Staff', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(100) NOT NULL,
  `other_leave_type` varchar(100) DEFAULT NULL,
  `vacation_detail` varchar(255) DEFAULT NULL,
  `vacation_location` varchar(255) DEFAULT NULL,
  `sick_detail` varchar(255) DEFAULT NULL,
  `sick_location` varchar(255) DEFAULT NULL,
  `study_detail` varchar(255) DEFAULT NULL,
  `other_detail` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `working_days` decimal(10,1) DEFAULT NULL,
  `date_filed` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `commutation` varchar(50) DEFAULT 'Not Requested',
  `encoded_by` varchar(100) DEFAULT NULL,
  `sick_illness` varchar(255) DEFAULT NULL,
  `women_special_detail` varchar(255) DEFAULT NULL,
  `recorded_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `recommended_by` varchar(100) DEFAULT NULL,
  `disapproval_reason` varchar(255) DEFAULT NULL,
  `entity_name` varchar(100) DEFAULT NULL,
  `fund_cluster` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
