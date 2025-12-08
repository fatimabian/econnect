-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 03:54 PM
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
  `barangay` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_by_super_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT '',
  `phone_number` varchar(15) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_admins`
--

INSERT INTO `barangay_admins` (`id`, `full_name`, `barangay`, `username`, `password`, `created_by_super_admin_id`, `created_at`, `email`, `phone_number`, `status`) VALUES
(1, 'Fatima Bian', 'Bagong Pook', 'adminBagongPook', '$2y$10$rGyqHdnNvuSvQEg/COvY2.mmDKA5wOStdL5NKa2MpGBKdnN5nprZG', NULL, '2025-11-24 02:55:39', 'arnigofatimabian@gmail.com', '09953092014', 'Active'),
(23, '', 'Poblacion A', 'pobA', '$2y$10$wPMM75zgYadjPXWwXR.MXeLacKp/jhtF/cFDvruEw14ry8sru3vMS', NULL, '2025-12-06 15:47:40', '', '+639953092014', 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `collection_crew`
--

CREATE TABLE `collection_crew` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT '',
  `created_by_super_admin_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection_crew`
--

INSERT INTO `collection_crew` (`id`, `full_name`, `barangay`, `username`, `status`, `password`, `email`, `created_by_super_admin_id`, `phone`) VALUES
(4, 'Fatima Bian A', 'Bagong Pook', 'crew1', 'Active', '$2y$10$yFxAqeH8seNlVh4s72m0dO6h360kH9e9bYyLE67wzuzwKlDO9JPRy', 'arnigofatimabian@gmail.com', NULL, '09953092014'),
(14, '', 'Poblacion A', 'crew2', 'Inactive', '$2y$10$s/dKR2D47yhkUAuXUK.Xnu8BhqgLBuUlsyMs3ZciDVc2ZE6UJcLV2', '', NULL, '+639953092014');

-- --------------------------------------------------------

--
-- Table structure for table `collection_schedule`
--

CREATE TABLE `collection_schedule` (
  `id` int(11) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `created_by_admin_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collection_status`
--

CREATE TABLE `collection_status` (
  `id` int(11) NOT NULL,
  `crew_id` int(11) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `status` enum('On the Way','Delayed','Collection Started','Collection Completed') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `completed_pickups`
--

CREATE TABLE `completed_pickups` (
  `id` int(11) NOT NULL,
  `crew_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_notifications`
--

CREATE TABLE `sms_notifications` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Sent','Failed') DEFAULT 'Pending',
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notification_type` enum('reschedule','reminder','other') DEFAULT 'other',
  `scheduled_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id`, `name`, `username`, `city`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Super Admin', 'superAdminRosario', 'Rosario', 'arnigofatimabian@gmail.com', '09953092014', '$2y$10$q4lvB6sFWm3YA1QF3OKKFebBJLuln2Wz39uXaKSHx4/rnjKfKv3uu', '2025-11-23 12:29:21');

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
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `full_name` varchar(200) GENERATED ALWAYS AS (concat(`fname`,' ',ifnull(`mname`,''),' ',`lname`,' ',ifnull(`suffix`,''))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `mname`, `lname`, `suffix`, `contact`, `email`, `region`, `province`, `city`, `barangay`, `street`, `zip`, `username`, `password`, `created_at`, `status`) VALUES
(2, 'Fatima Bian', 'Ramirez', 'Arnigo', '', '09953092014', 'arnigofatimabian@gmail.com', '4A', 'Batangas', 'ROSARIO (BATANGAS)', 'Bagong Pook', 'Purok 6', '4225', 'Fatima', '$2y$10$YSRAL.kvrwEvnRkQeGWdOubOpqxlADM1.HJHWZ.KwZMQ7eLp1koQ.', '2025-11-23 22:28:08', 'Active'),
(5, 'Sofia', 'Ramirez', 'Arnigo', '', '09929160342', 'sofiaarnigo7@gmail.com', '4A', 'BATANGAS', 'ROSARIO', 'BAGONG POOK', '', '', 'Kristine', '$2y$10$jdagilVDZQS03KyUiWqMjen2rv/sk054vVqwJmswbMFsxqfKdjO7q', '2025-12-06 12:39:50', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay_admins`
--
ALTER TABLE `barangay_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`username`),
  ADD KEY `fk_barangay_admin_creator` (`created_by_super_admin_id`);

--
-- Indexes for table `collection_crew`
--
ALTER TABLE `collection_crew`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_crew_creator` (`created_by_super_admin_id`);

--
-- Indexes for table `collection_schedule`
--
ALTER TABLE `collection_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_creator_admin` (`created_by_admin_id`);

--
-- Indexes for table `collection_status`
--
ALTER TABLE `collection_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_status_crew` (`crew_id`),
  ADD KEY `fk_status_schedule` (`schedule_id`);

--
-- Indexes for table `completed_pickups`
--
ALTER TABLE `completed_pickups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_completed_crew` (`crew_id`);

--
-- Indexes for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_schedule` (`schedule_id`),
  ADD KEY `fk_notification_user` (`user_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangay_admins`
--
ALTER TABLE `barangay_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `collection_crew`
--
ALTER TABLE `collection_crew`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `collection_schedule`
--
ALTER TABLE `collection_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `collection_status`
--
ALTER TABLE `collection_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `completed_pickups`
--
ALTER TABLE `completed_pickups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barangay_admins`
--
ALTER TABLE `barangay_admins`
  ADD CONSTRAINT `fk_barangay_admin_creator` FOREIGN KEY (`created_by_super_admin_id`) REFERENCES `super_admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `collection_crew`
--
ALTER TABLE `collection_crew`
  ADD CONSTRAINT `fk_crew_creator` FOREIGN KEY (`created_by_super_admin_id`) REFERENCES `super_admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `collection_schedule`
--
ALTER TABLE `collection_schedule`
  ADD CONSTRAINT `fk_schedule_creator_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `barangay_admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `collection_status`
--
ALTER TABLE `collection_status`
  ADD CONSTRAINT `fk_status_crew` FOREIGN KEY (`crew_id`) REFERENCES `collection_crew` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `collection_schedule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `completed_pickups`
--
ALTER TABLE `completed_pickups`
  ADD CONSTRAINT `fk_completed_crew` FOREIGN KEY (`crew_id`) REFERENCES `collection_crew` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD CONSTRAINT `fk_notification_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `collection_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_notifications_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `collection_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
