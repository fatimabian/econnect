-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 02:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `econnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangay_admins`
--

CREATE TABLE `barangay_admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_admins`
--

INSERT INTO `barangay_admins` (`id`, `full_name`, `barangay`, `username`, `password`, `created_at`, `email`) VALUES
(1, 'Fatima Bian', 'Bagong Pook', 'adminBagongPook', '$2y$10$rGyqHdnNvuSvQEg/COvY2.mmDKA5wOStdL5NKa2MpGBKdnN5nprZG', '2025-11-24 02:55:39', 'arnigofatimabian@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `collection_crew`
--

CREATE TABLE `collection_crew` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT '',
  `phone` varchar(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection_crew`
--

INSERT INTO `collection_crew` (`id`, `full_name`, `barangay`, `username`, `status`, `password`, `email`, `phone`) VALUES
(4, 'Fatima Bian', 'Bagong Pook', 'admincrew', 'Active', '$2y$10$yFxAqeH8seNlVh4s72m0dO6h360kH9e9bYyLE67wzuzwKlDO9JPRy', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `collection_schedule`
--

CREATE TABLE `collection_schedule` (
  `id` int(11) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection_schedule`
--

INSERT INTO `collection_schedule` (`id`, `barangay`, `date`, `time`) VALUES
(6, 'Bagong Pook', '2025-11-26', '09:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `full_name`, `email`, `phone`, `message`, `status`, `created_at`) VALUES
(8, 'Fatima Bian Ramirez Arnigo', 'arnigofatimabian@gmail.com', '+639953092014', 'Subject: ewan\nAddress: Purok 6, Bagong Pook, ROSARIO (BATANGAS)\nMessage: dikodin alam', 'Pending', '2025-11-24 09:06:26'),
(9, 'Fatima Bian Ramirez Arnigo', 'arnigofatimabian@gmail.com', '+639953092014', 'Subject: dhhfud\nAddress: Purok 6, Bagong Pook, ROSARIO (BATANGAS)\nMessage: fduafhbd', 'Resolved', '2025-11-24 12:00:23'),
(10, 'Fatima Bian Ramirez Arnigo', 'arnigofatimabian@gmail.com', '+639953092014', 'Subject: fdbfhdbaffdhbfhdsbb\nAddress: Purok 6, Bagong Pook, ROSARIO (BATANGAS)\nMessage: fdsfhdishfsdj', 'Pending', '2025-11-24 12:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `crew_inbox`
--

CREATE TABLE `crew_inbox` (
  `id` int(11) NOT NULL,
  `crew_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crew_inbox`
--

INSERT INTO `crew_inbox` (`id`, `crew_id`, `admin_id`, `message`, `status`, `created_at`) VALUES
(5, 4, 1, 'jffnff', 'Read', '2025-11-24 13:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `crew_reports`
--

CREATE TABLE `crew_reports` (
  `id` int(11) NOT NULL,
  `crew_id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crew_reports`
--

INSERT INTO `crew_reports` (`id`, `crew_id`, `report_title`, `report_message`, `created_at`) VALUES
(1, 4, 'klfsdhfiuds', 'klfdsjofjdsfsd', '2025-11-24 12:28:22'),
(2, 4, 'gfgsklgsddsjlngjds', 'jjsduighjksdg', '2025-11-24 12:28:42');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id`, `name`, `username`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Super Admin', 'superAdmin', 'arnigofatimabian@gmail.com', '09953092014', '1superadmin', '2025-11-23 12:29:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `region` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `street` varchar(100) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `full_name` varchar(200) GENERATED ALWAYS AS (concat(`fname`,' ',ifnull(`mname`,''),' ',`lname`,' ',ifnull(`suffix`,''))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `mname`, `lname`, `suffix`, `contact`, `email`, `region`, `province`, `city`, `barangay`, `street`, `zip`, `username`, `password`, `created_at`, `status`) VALUES
(2, 'Fatima Bian', 'Ramirez', 'Arnigo', '', '+639953092014', 'arnigofatimabian@gmail.com', '4A', 'Batangas', 'ROSARIO (BATANGAS)', 'Bagong Pook', 'Purok 6', '4225', 'Fatima', '$2y$10$plxlS9ZIVOpDSSJzuLW5SO4B9IVEnBnuyxr.mOchZuCa34.piQ4We', '2025-11-23 22:28:08', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `user_inbox`
--

CREATE TABLE `user_inbox` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_inbox`
--

INSERT INTO `user_inbox` (`id`, `admin_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(1, 1, 2, 'dsafasfasfsa mds v', 'Unread', '2025-11-24 11:54:16'),
(2, 1, 2, 'nefkjdsbfhds', 'Unread', '2025-11-24 12:11:03'),
(3, 1, 2, 'fsdds', 'Unread', '2025-11-24 12:19:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay_admins`
--
ALTER TABLE `barangay_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`username`);

--
-- Indexes for table `collection_crew`
--
ALTER TABLE `collection_crew`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collection_schedule`
--
ALTER TABLE `collection_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crew_inbox`
--
ALTER TABLE `crew_inbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crew_id` (`crew_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `crew_reports`
--
ALTER TABLE `crew_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crew_id` (`crew_id`);

--
-- Indexes for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact` (`contact`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_inbox`
--
ALTER TABLE `user_inbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangay_admins`
--
ALTER TABLE `barangay_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `collection_crew`
--
ALTER TABLE `collection_crew`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `collection_schedule`
--
ALTER TABLE `collection_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `crew_inbox`
--
ALTER TABLE `crew_inbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `crew_reports`
--
ALTER TABLE `crew_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_inbox`
--
ALTER TABLE `user_inbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crew_inbox`
--
ALTER TABLE `crew_inbox`
  ADD CONSTRAINT `crew_inbox_ibfk_1` FOREIGN KEY (`crew_id`) REFERENCES `collection_crew` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `crew_inbox_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `barangay_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crew_reports`
--
ALTER TABLE `crew_reports`
  ADD CONSTRAINT `crew_reports_ibfk_1` FOREIGN KEY (`crew_id`) REFERENCES `collection_crew` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_inbox`
--
ALTER TABLE `user_inbox`
  ADD CONSTRAINT `user_inbox_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `barangay_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_inbox_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
