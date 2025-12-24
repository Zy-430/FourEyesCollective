-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 06:07 PM
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
-- Database: `foureyescollective`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` varchar(8) NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  `default_flag` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `user_id`, `recipient_name`, `address_line1`, `address_line2`, `city`, `state`, `postcode`, `country`, `default_flag`, `created_at`) VALUES
('ADRS0001', 'ME0001', 'Mary Lee', '123 Main Street', 'Unit 1A', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50000', 'Malaysia', 1, '2025-11-01 09:05:00'),
('ADRS0002', 'ME0002', 'John Tan', '456 Market Road', '', 'Shah Alam', 'Selangor', '40000', 'Malaysia', 1, '2025-11-02 10:15:00'),
('ADRS0003', 'ME0003', 'Lim Wei', '789 Hill Street', 'Apt 12B', 'Penang', 'Penang', '10050', 'Malaysia', 1, '2025-11-03 11:20:00'),
('ADRS0004', 'ME0004', 'Nur Aini', '321 River Lane', '', 'Kota Kinabalu', 'Sabah', '88000', 'Malaysia', 1, '2025-11-04 12:25:00'),
('ADRS0005', 'ME0005', 'Ahmad Faiz', '654 Garden Avenue', 'Unit 5C', 'Ipoh', 'Perak', '30000', 'Malaysia', 1, '2025-11-05 13:30:00'),
('ADRS0006', 'ME0006', 'Siti Hawa', '987 Sunset Blvd', '', 'Petaling Jaya', 'Selangor', '46000', 'Malaysia', 1, '2025-11-06 14:35:00'),
('ADRS0007', 'ME0007', 'Lee Chong', '246 Sunrise Street', 'Unit 7B', 'Melaka', 'Melaka', '75000', 'Malaysia', 1, '2025-11-07 15:40:00'),
('ADRS0008', 'ME0008', 'Aiman Rahman', '135 Ocean Road', '', 'Ampang', 'Selangor', '68000', 'Malaysia', 1, '2025-11-08 16:45:00'),
('ADRS0009', 'ME0009', 'Tan Mei Ling', '864 Mountain Lane', '', 'Penang', 'Penang', '10080', 'Malaysia', 1, '2025-11-09 17:50:00'),
('ADRS0010', 'ME0010', 'Kumar Raj', '753 Forest Street', 'Unit 10C', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50450', 'Malaysia', 1, '2025-11-10 18:55:00'),
('ADRS0011', 'ME0011', 'Farah Naz', '951 Riverbank Rd', '', 'Putrajaya', 'W.P. Putrajaya', '62000', 'Malaysia', 1, '2025-11-11 09:00:00'),
('ADRS0012', 'ME0012', 'Lim Hui', '159 Hilltop Ave', 'Unit 12B', 'Penang', 'Penang', '10100', 'Malaysia', 1, '2025-11-12 10:05:00'),
('ADRS0013', 'ME0013', 'Aizat Amin', '357 Valley Street', 'Unit 13C', 'Kota Bharu', 'Kelantan', '15000', 'Malaysia', 1, '2025-11-13 11:10:00'),
('ADRS0014', 'ME0014', 'Hani Syazwani', '753 Garden Lane', '', 'Pahang', 'Pahang', '25000', 'Malaysia', 1, '2025-11-14 12:15:00'),
('ADRS0015', 'ME0015', 'Lim Siew', '951 Lakeview Blvd', 'Unit 15A', 'Selangor', 'Selangor', '43000', 'Malaysia', 1, '2025-11-15 13:20:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
