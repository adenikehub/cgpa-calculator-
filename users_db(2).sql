-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 12:24 AM
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
-- Database: `users_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cgpa_results`
--

CREATE TABLE `cgpa_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matric_no` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `degree_class` varchar(50) DEFAULT NULL,
  `total_units` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT NULL,
  `semesters` int(11) DEFAULT NULL,
  `calculated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cgpa_results`
--

INSERT INTO `cgpa_results` (`id`, `user_id`, `matric_no`, `name`, `department`, `cgpa`, `degree_class`, `total_units`, `total_points`, `semesters`, `calculated_at`) VALUES
(1, 6, '124/23/2/0180', 'Adeshina Fahidah', 'Accounting and Economics', 4.50, '1st Class', 0, 0, 2, '2026-05-04 15:45:58'),
(2, 6, '124/23/2/0180', 'Adeshina Fahidah', 'Accounting and Economics', 4.50, '1st Class', 0, 0, 2, '2026-05-04 16:07:07'),
(3, 6, '124/23/2/0180', 'Adeshina Fahidah', 'Accounting and Economics', 4.50, '1st Class', 0, 0, 2, '2026-05-04 16:17:21'),
(4, 6, '124/23/2/0180', 'Adeshina Fahidah', 'Accounting and Economics', 4.50, '1st Class', 0, 0, 2, '2026-05-04 16:22:45'),
(5, 6, '124/23/2/0180', 'Adeshina Fahidah', 'Accounting and Economics', 4.50, '1st Class', 0, 0, 2, '2026-05-04 20:27:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `matric_no` varchar(50) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `role` enum('student','admin') NOT NULL,
  `department` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `matric_no`, `email`, `password`, `role`, `department`) VALUES
(1, 'george john', '125/24/2/0017', 'ikumapayiadenike77@gmail.com', '$2y$10$j5Ksn4gqFxb2/F9d0Rh1aekenD5HM0c4OKPOJQVRtlsMnQTUE4Yw6', 'student', 'computer sciences'),
(3, 'adenike nikky', '125/23/1/0015', 'nikky@gmail.com', '$2y$10$NtxzF7DyOVDSbooL/H0aSO4.cGUMKeAvd4cuhR57qCHjl1AiFdVzu', 'student', 'environmental'),
(4, 'lucky learn', 'luck098', 'luck@gmail.com', '$2y$10$ld5H8zfNnRoXPq.b7p3CgeNruJ31xnzLcbLNVh4RCToetXTLHiJYi', 'admin', 'learning and practicing'),
(5, 'george john', '125/24/1/099', 'john@gmail.com', '$2y$10$qLdbI3ojZlsAKaBs2Bz3geMBFbJhB2WCSNkbngeE4OsVqsrSchNF.', 'student', 'SVG'),
(6, 'Adeshina Fahidah', '124/23/2/0180', 'fath@gmail.com', '$2y$10$.Os/DltGWyeTgr0qJ5m3FuZpaa/hpuQBSd/OuQURvS2fv38Ccf/gG', 'student', 'Accounting and Economics'),
(7, 'Olawale Akanni', '126/25/1/0249', 'akanni@gmail.com', '$2y$10$jC3CrUsbK.PdXYs4moz7Ju9.cEeBp7yxDdI//LvGjbtIJGpvF8ZVW', 'student', 'Biochemistry'),
(8, 'Olawale Fashola', '126/25/1/0243', 'fash@gmail.com', '$2y$10$4aK.CincZhbeRqojFI.0PO6bhtjXdn.WHnfYNxRqrFUXbrmT2DdM6', 'admin', 'Mathematics');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cgpa_results`
--
ALTER TABLE `cgpa_results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `matric_no_unique` (`matric_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cgpa_results`
--
ALTER TABLE `cgpa_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
