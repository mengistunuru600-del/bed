-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 05:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bed_reservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

CREATE TABLE `beds` (
  `id` int(6) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL,
  `status` enum('available','occupied') DEFAULT 'available',
  `room_size` varchar(20) DEFAULT 'Standard',
  `accessories` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beds`
--

INSERT INTO `beds` (`id`, `name`, `status`, `room_size`, `accessories`) VALUES
(1, 'Bed 1', 'available', 'Single', 'WiFi, TV, Mini Fridge, Air Conditioning'),
(2, 'Bed 2', 'occupied', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony'),
(3, 'Bed 3', 'available', 'Single', 'WiFi, TV, Air Conditioning'),
(4, 'Bed 4', 'occupied', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Kitchenette'),
(5, 'Bed 5', 'available', 'Suite', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony, Kitchenette, Sofa'),
(6, 'bed 6', 'available', 'Double', ''),
(7, 'Bed 1', 'available', 'Single', 'WiFi, TV, Mini Fridge, Air Conditioning'),
(8, 'Bed 2', 'available', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony'),
(9, 'Bed 3', 'available', 'Single', 'WiFi, TV, Air Conditioning'),
(10, 'Bed 4', 'available', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Kitchenette'),
(11, 'Bed 5', 'available', 'Suite', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony, Kitchenette, Sofa');

-- --------------------------------------------------------

--
-- Table structure for table `police_audit_log`
--

CREATE TABLE `police_audit_log` (
  `id` int(6) UNSIGNED NOT NULL,
  `officer_id` int(6) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(6) UNSIGNED NOT NULL,
  `bed_id` int(6) UNSIGNED DEFAULT NULL,
  `guest_name` varchar(50) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_requests`
--

CREATE TABLE `reservation_requests` (
  `id` int(6) UNSIGNED NOT NULL,
  `customer_id` int(6) UNSIGNED DEFAULT NULL,
  `bed_id` int(6) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `picture_path` varchar(255) DEFAULT NULL,
  `username` varchar(30) DEFAULT NULL,
  `password` varchar(10) DEFAULT NULL,
  `sms_sent` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved_by_receptionist','approved_by_manager','confirmed','rejected','cancelled') DEFAULT 'pending',
  `receptionist_id` int(6) UNSIGNED DEFAULT NULL,
  `manager_id` int(6) UNSIGNED DEFAULT NULL,
  `receptionist_notes` text DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `payment_method` enum('cbe','telebirr') DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_flags`
--

CREATE TABLE `security_flags` (
  `id` int(6) UNSIGNED NOT NULL,
  `reservation_id` int(6) UNSIGNED NOT NULL,
  `officer_id` int(6) UNSIGNED NOT NULL,
  `reason` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','resolved','dismissed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','receptionist','manager','admin') DEFAULT 'customer',
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `phone`, `created_at`) VALUES
(1, 'admin', 'admin@bed.com', '$2y$10$fcmC6eFsTZ0kMFRw7COi8eLwzFb58n/JYTrgo143Nmv6de.rs6/AW', 'admin', 'System Administrator', NULL, '2026-03-08 19:16:57'),
(2, 'manager', 'manager@bed.com', '$2y$10$MTFufXmiuWmvcKWikpcFc.y8V1l701uHUx.Puc2SpReCN1xecxqXK', 'manager', 'Hotel Manager', NULL, '2026-03-08 19:16:57'),
(3, 'reception', 'reception@bed.com', '$2y$10$OkIZvftVCNZf.ZFCQ6nmsuaokv.otTI6FgkGIRra9yUabAErPINW2', 'receptionist', 'Front Desk Receptionist', NULL, '2026-03-08 19:16:57'),
(4, 'mulu', 'dagmesun@gmail.com', '$2y$10$g.fjwiKBiNJwQ1eWmx8kMuWB71OX/duUS/zrwRWBU9.q0OmJu/kUi', 'customer', NULL, NULL, '2026-03-08 19:24:32'),
(5, 'muluw', 'dagmesueh@gmail.com', '$2y$10$Gpshw55mBCKBdHujs.H9BeB0PsZWtLmYzYgEuR2CKDOg3Gu83KWsq', 'customer', NULL, NULL, '2026-03-08 19:49:53'),
(6, 'vvbb', 'vvbbgt@gmail.com', '$2y$10$jLNa4e3ETKJQJI/nR6UQ5uMGGny/Y8AfCDVjGqA1rcAwy7adjtghO', 'customer', NULL, NULL, '2026-03-08 21:23:14'),
(7, 'hee', 'lreges1@gmail.com', '$2y$10$a8VDgJ2gFr9.q8FyYzU/6e3hrzOrvh6f/X1RT5pgJV2gqmS/fP0iC', 'customer', NULL, NULL, '2026-03-09 16:44:02'),
(8, 'kedir', 'lregesa@gmail.com', '$2y$10$pkEfTo6eycEYaPvvARefiuuohlabniSXIkXdfpv2LJSj5usCRWC.e', 'customer', NULL, NULL, '2026-03-09 16:45:50'),
(9, 'police', 'lreges@gmail.com', '$2y$10$X8f1aqHRXaXzFdDgVlsZIewY0NhbyLIOuuGiUnqh8DR./iQ2rv0DO', '', 'police', '900000', '2026-03-09 17:30:00'),
(14, 'police1', 'police1@gmail.com', '$2y$10$AfkjYV6v1okBO/QmGtG1pOX6X3N4yf3ECekT8u.avkU5hC9hztJvm', '', 'police1', '0917516532', '2026-03-12 13:36:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beds`
--
ALTER TABLE `beds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `police_audit_log`
--
ALTER TABLE `police_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bed_id` (`bed_id`);

--
-- Indexes for table `reservation_requests`
--
ALTER TABLE `reservation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `bed_id` (`bed_id`),
  ADD KEY `receptionist_id` (`receptionist_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `security_flags`
--
ALTER TABLE `security_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beds`
--
ALTER TABLE `beds`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `police_audit_log`
--
ALTER TABLE `police_audit_log`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_requests`
--
ALTER TABLE `reservation_requests`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_flags`
--
ALTER TABLE `security_flags`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `police_audit_log`
--
ALTER TABLE `police_audit_log`
  ADD CONSTRAINT `police_audit_log_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`);

--
-- Constraints for table `reservation_requests`
--
ALTER TABLE `reservation_requests`
  ADD CONSTRAINT `reservation_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservation_requests_ibfk_2` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`),
  ADD CONSTRAINT `reservation_requests_ibfk_3` FOREIGN KEY (`receptionist_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservation_requests_ibfk_4` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `security_flags`
--
ALTER TABLE `security_flags`
  ADD CONSTRAINT `security_flags_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservation_requests` (`id`),
  ADD CONSTRAINT `security_flags_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
