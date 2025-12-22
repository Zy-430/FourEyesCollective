-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 07:01 PM
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
CREATE DATABASE IF NOT EXISTS `foureyescollective` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `foureyescollective`;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `user_id`, `recipient_name`, `address_line1`, `address_line2`, `city`, `state`, `postcode`, `country`, `default_flag`, `created_at`, `latitude`, `longitude`) VALUES
('ADRS0001', 'ME0001', 'Mary Lee', '123 Main Street', 'Unit 1A', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50000', 'Malaysia', 1, '2025-11-01 09:05:00', 3.13900000, 101.68690000),
('ADRS0002', 'ME0002', 'John Tan', '456 Market Road', '', 'Shah Alam', 'Selangor', '40000', 'Malaysia', 1, '2025-11-02 10:15:00', 3.07380000, 101.51830000),
('ADRS0003', 'ME0003', 'Lim Wei', '789 Hill Street', 'Apt 12B', 'Penang', 'Penang', '10050', 'Malaysia', 1, '2025-11-03 11:20:00', 5.41640000, 100.33270000),
('ADRS0004', 'ME0004', 'Nur Aini', '321 River Lane', '', 'Kota Kinabalu', 'Sabah', '88000', 'Malaysia', 1, '2025-11-04 12:25:00', 5.98040000, 116.07350000),
('ADRS0005', 'ME0005', 'Ahmad Faiz', '654 Garden Avenue', 'Unit 5C', 'Ipoh', 'Perak', '30000', 'Malaysia', 1, '2025-11-05 13:30:00', 4.59750000, 101.09010000),
('ADRS0006', 'ME0006', 'Siti Hawa', '987 Sunset Blvd', '', 'Petaling Jaya', 'Selangor', '46000', 'Malaysia', 1, '2025-11-06 14:35:00', 3.10730000, 101.60670000),
('ADRS0007', 'ME0007', 'Lee Chong', '246 Sunrise Street', 'Unit 7B', 'Melaka', 'Melaka', '75000', 'Malaysia', 1, '2025-11-07 15:40:00', 2.18960000, 102.25010000),
('ADRS0008', 'ME0008', 'Aiman Rahman', '135 Ocean Road', '', 'Ampang', 'Selangor', '68000', 'Malaysia', 1, '2025-11-08 16:45:00', 3.14480000, 101.74150000),
('ADRS0009', 'ME0009', 'Tan Mei Ling', '864 Mountain Lane', '', 'Penang', 'Penang', '10080', 'Malaysia', 1, '2025-11-09 17:50:00', 5.41640000, 100.33270000),
('ADRS0010', 'ME0010', 'Kumar Raj', '753 Forest Street', 'Unit 10C', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50450', 'Malaysia', 1, '2025-11-10 18:55:00', 3.13900000, 101.68690000),
('ADRS0011', 'ME0011', 'Farah Naz', '951 Riverbank Rd', '', 'Putrajaya', 'W.P. Putrajaya', '62000', 'Malaysia', 1, '2025-11-11 09:00:00', 2.92640000, 101.69640000),
('ADRS0012', 'ME0012', 'Lim Hui', '159 Hilltop Ave', 'Unit 12B', 'Penang', 'Penang', '10100', 'Malaysia', 1, '2025-11-12 10:05:00', 5.41640000, 100.33270000),
('ADRS0013', 'ME0013', 'Aizat Amin', '357 Valley Street', 'Unit 13C', 'Kota Bharu', 'Kelantan', '15000', 'Malaysia', 1, '2025-11-13 11:10:00', 6.12500000, 102.23830000),
('ADRS0014', 'ME0014', 'Hani Syazwani', '753 Garden Lane', '', 'Pahang', 'Pahang', '25000', 'Malaysia', 1, '2025-11-14 12:15:00', 3.80710000, 103.32620000),
('ADRS0015', 'ME0015', 'Lim Siew', '951 Lakeview Blvd', 'Unit 15A', 'Selangor', 'Selangor', '43000', 'Malaysia', 1, '2025-11-15 13:20:00', 3.07380000, 101.51830000);

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `cart_item_id` varchar(10) NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `product_id` varchar(6) NOT NULL,
  `product_qty` int(11) NOT NULL,
  `item_status` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `abandon_at` timestamp NULL DEFAULT NULL,
  `checkout_at` timestamp NULL DEFAULT NULL,
  `order_item_id` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item`
--

INSERT INTO `cart_item` (`cart_item_id`, `user_id`, `product_id`, `product_qty`, `item_status`, `created_at`, `abandon_at`, `checkout_at`, `order_item_id`) VALUES
('CI0001', 'ME0001', 'PR0001', 2, 'checkout', '2025-11-01 01:10:00', NULL, '2025-11-01 02:00:00', 'OI0001'),
('CI0002', 'ME0001', 'PR0002', 2, 'checkout', '2025-11-01 01:12:00', NULL, '2025-11-01 02:00:00', 'OI0002'),
('CI0003', 'ME0002', 'PR0003', 1, 'checkout', '2025-11-02 02:20:00', NULL, '2025-11-02 03:00:00', 'OI0003'),
('CI0004', 'ME0003', 'PR0001', 1, 'checkout', '2025-11-03 03:30:00', NULL, '2025-11-03 04:00:00', 'OI0004'),
('CI0005', 'ME0004', 'PR0004', 3, 'checkout', '2025-11-04 04:40:00', NULL, '2025-11-04 05:00:00', 'OI0005'),
('CI0006', 'ME0005', 'PR0002', 4, 'checkout', '2025-11-05 05:40:00', NULL, '2025-11-05 06:00:00', 'OI0006'),
('CI0007', 'ME0006', 'PR0003', 1, 'checkout', '2025-11-06 06:40:00', NULL, '2025-11-06 07:00:00', 'OI0007'),
('CI0008', 'ME0007', 'PR0001', 2, 'checkout', '2025-11-07 07:45:00', NULL, '2025-11-07 08:00:00', 'OI0008'),
('CI0009', 'ME0008', 'PR0004', 2, 'checkout', '2025-11-08 08:50:00', NULL, '2025-11-08 09:00:00', 'OI0009'),
('CI0010', 'ME0009', 'PR0002', 4, 'checkout', '2025-11-09 09:55:00', NULL, '2025-11-09 10:00:00', 'OI0010'),
('CI0011', 'ME0010', 'PR0003', 1, 'checkout', '2025-12-06 01:47:34', NULL, '2025-12-06 01:47:34', NULL),
('CI0012', 'ME0011', 'PR0001', 1, 'abandoned', '2025-11-11 00:50:00', '2025-11-11 01:10:00', NULL, NULL),
('CI0013', 'ME0012', 'PR0004', 1, 'abandoned', '2025-12-06 00:56:21', '2025-12-06 00:56:21', NULL, NULL),
('CI0014', 'ME0013', 'PR0002', 2, 'abandoned', '2025-11-13 02:00:00', '2025-11-13 02:20:00', NULL, NULL),
('CI0015', 'ME0014', 'PR0003', 1, 'in_cart', '2025-11-14 03:30:00', NULL, NULL, NULL),
('CI0016', 'ME0015', 'PR0001', 3, 'abandoned', '2025-11-15 04:00:00', '2025-11-15 04:40:00', NULL, NULL),
('CI0017', 'ME0010', 'PR0003', 1, 'checkout', '2025-11-10 02:00:00', NULL, '2025-11-10 11:00:00', 'OI0011'),
('CI0018', 'ME0011', 'PR0001', 1, 'checkout', '2025-11-11 02:00:00', NULL, '2025-11-11 01:00:00', 'OI0012'),
('CI0019', 'ME0012', 'PR0004', 2, 'checkout', '2025-11-11 02:30:00', NULL, '2025-11-12 02:30:00', 'OI0013'),
('CI0020', 'ME0013', 'PR0002', 5, 'checkout', '2025-11-12 03:15:00', NULL, '2025-11-13 03:00:00', 'OI0014'),
('CI0021', 'ME0014', 'PR0003', 1, 'checkout', '2025-11-12 04:00:00', NULL, '2025-11-14 04:30:00', 'OI0015'),
('CI0022', 'ME0015', 'PR0001', 2, 'checkout', '2025-11-10 02:00:00', NULL, '2025-11-15 05:20:00', 'OI0016'),
('CI0023', 'ME0001', 'PR0004', 2, 'checkout', '2025-11-05 01:20:00', NULL, '2025-11-05 02:00:00', 'OI0017'),
('CI0024', 'ME0002', 'PR0002', 4, 'checkout', '2025-11-06 02:10:00', NULL, '2025-11-06 03:00:00', 'OI0011'),
('CI0025', 'ME0003', 'PR0003', 1, 'checkout', '2025-11-07 03:30:00', NULL, '2025-11-07 04:00:00', 'OI0019'),
('CI0026', 'ME0005', 'PR0001', 3, 'checkout', '2025-11-09 05:20:00', NULL, '2025-11-09 06:00:00', 'OI0020'),
('CI0027', 'ME0012', 'PR0001', 1, 'in_cart', '2025-12-06 00:56:29', NULL, NULL, NULL),
('CI0028', 'ME0010', 'PR0001', 3, 'abandoned', '2025-12-06 01:46:54', '2025-12-06 01:46:54', NULL, NULL),
('CI0029', 'ME0010', 'PR0006', 1, 'abandoned', '2025-12-06 01:46:51', '2025-12-06 01:46:51', NULL, NULL),
('CI0030', 'ME0010', 'PR0011', 1, 'checkout', '2025-12-06 01:47:24', NULL, '2025-12-06 01:47:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` varchar(6) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `folder` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `folder`) VALUES
('CA0001', 'Glasses', 'glasses'),
('CA0002', 'Sunglasses', 'sunglasses'),
('CA0003', 'Contact Lens', 'contactlens'),
('CA0004', 'Kids', 'kids');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `verification_id` int(11) NOT NULL,
  `user_id` char(6) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT 'verification',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`verification_id`, `user_id`, `token`, `expiry`, `is_used`, `type`, `created_at`) VALUES
(1, 'ME0001', 'a3f9c1d4e7b2178a90bcfe1234abcd56ef90781234cd56ef78ab9012cd45ef67', '2025-11-02 09:00:00', 1, 'verification', '2025-11-01 09:00:00'),
(2, 'ME0002', 'b7e4d9c3a1f2567890ab34cd56ef7812cd90ef34567812abcd34ef5678cd12ef', '2025-11-03 10:10:00', 1, 'verification', '2025-11-02 10:10:00'),
(3, 'ME0003', 'c1d2e3f4a5b67890cd12ef34ab56cd7890ef12cd34ab56ef7890cd12ef34ab56', '2025-11-04 11:15:00', 0, 'verification', '2025-11-03 11:15:00'),
(4, 'ME0004', 'd4c3b2e1f0a98765cd12ef7890ab34ef56cd78ab12ef34cd5678ab12ef90cd34', '2025-11-05 12:20:00', 1, 'verification', '2025-11-04 12:20:00'),
(5, 'ME0005', 'e5f6a7b8c9d01234ef90ab12cd34ef78ab12cd56ef7890ab34cd12ef5678cd90', '2025-11-06 13:25:00', 1, 'verification', '2025-11-05 13:25:00'),
(6, 'ME0006', 'f9e8d7c6b5a40123cd90ef12ab34cd56ef78ab34cd12ef56ab78cd90ef12ab34', '2025-11-07 14:30:00', 0, 'verification', '2025-11-06 14:30:00'),
(7, 'ME0007', 'a1b2c3d4e5f67890cd12ef345678ab12ef90cd34ab56ef7890cd12ef34ab5678', '2025-11-08 15:35:00', 1, 'verification', '2025-11-07 15:35:00'),
(8, 'ME0008', 'b2c3d4e5f6a78901ef34cd12ab56ef78cd12ef34ab56cd78ef9012cd34ab5678', '2025-11-09 16:40:00', 1, 'verification', '2025-11-08 16:40:00'),
(9, 'ME0009', 'c3d4e5f6a7b89012cd34ef56ab78cd90ef12ab34cd56ef78ab90cd12ef3456ab', '2025-11-10 17:45:00', 0, 'verification', '2025-11-09 17:45:00'),
(10, 'ME0010', 'd4e5f6a7b8c90123ef56ab12cd90ef34ab56cd78ef12ab90cd34ef78ab12cd34', '2025-11-11 18:50:00', 1, 'verification', '2025-11-10 18:50:00'),
(11, 'ME0011', 'e5f6a7b8c9d01234ab78cd56ef12cd34ef90ab12cd56ef78cd12ef34ab7890cd', '2025-11-12 09:55:00', 1, 'verification', '2025-11-11 09:55:00'),
(12, 'ME0012', 'f6a7b8c9d0e12345cd12ef90ab34cd78ef12ab34cd90ef78ab34cd12ef56ab78', '2025-11-13 10:00:00', 1, 'verification', '2025-11-12 10:00:00'),
(13, 'ME0013', 'a7b8c9d0e1f23456ef12cd34ab56ef90cd34ab56ef9012cd78ab34ef56cd12ef', '2025-11-14 11:05:00', 0, 'verification', '2025-11-13 11:05:00'),
(14, 'ME0014', 'b8c9d0e1f2a34567ab12cd34ef90ab56cd12ef34ab78cd56ef3412ab56cd78ef', '2025-11-15 12:10:00', 1, 'verification', '2025-11-14 12:10:00'),
(15, 'ME0015', 'c9d0e1f2a3b45678cd34ab12ef56cd90ab12ef34cd78ab56ef12cd34ab78ef90', '2025-11-16 13:15:00', 1, 'verification', '2025-11-15 13:15:00'),
(16, 'AD0001', 'a1c2e3f4b5d67890ef12cd34ab56ef78cd12ab90ef34cd56ab78ef12cd3456ef', '2025-11-02 09:00:00', 1, 'verification', '2025-11-01 09:00:00'),
(17, 'AD0002', 'b1d2f3e4c5a78901cd34ef12ab56cd78ef90ab12cd34ef56ab12ef34cd5678ab', '2025-11-03 09:10:00', 1, 'verification', '2025-11-02 09:10:00'),
(18, 'AD0003', 'c1e2f3a4d5b89012ef56cd34ab78ef12cd34ab56ef12cd90ab56ef34cd12ab78', '2025-11-04 09:20:00', 1, 'verification', '2025-11-03 09:20:00'),
(19, 'AD0004', 'd1f2a3e4c5b90123ab78cd56ef34cd12ab56ef90cd12ef78ab34cd12ef78cd34', '2025-11-05 09:30:00', 1, 'verification', '2025-11-04 09:30:00'),
(20, 'AD0005', 'e1a2b3c4d5f01234cd12ef78ab34cd56ef12ab34cd90ef56ab78cd12ef34ab90', '2025-11-06 09:40:00', 1, 'verification', '2025-11-05 09:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `order_id` varchar(6) NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `address_id` varchar(8) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` float NOT NULL,
  `status` varchar(20) NOT NULL,
  `cancelled_reason` varchar(255) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `user_id`, `address_id`, `order_date`, `total_amount`, `status`, `cancelled_reason`, `delivered_at`) VALUES
('OR0001', 'ME0001', 'ADRS0001', '2025-11-01 02:00:00', 630, 'completed', NULL, '2025-11-02 10:00:00'),
('OR0002', 'ME0002', 'ADRS0002', '2025-11-02 03:00:00', 420, 'delivered', NULL, '2025-11-03 12:00:00'),
('OR0003', 'ME0003', 'ADRS0003', '2025-11-03 04:00:00', 210, 'cancelled', 'Ordered by mistake', NULL),
('OR0004', 'ME0004', 'ADRS0004', '2025-11-04 05:00:00', 1050, 'cancelled', NULL, NULL),
('OR0005', 'ME0005', 'ADRS0005', '2025-11-05 06:00:00', 420, 'delivered', NULL, '2025-11-06 11:00:00'),
('OR0006', 'ME0006', 'ADRS0006', '2025-11-06 07:00:00', 420, 'delivered', NULL, '2025-11-07 15:00:00'),
('OR0007', 'ME0007', 'ADRS0007', '2025-11-07 08:00:00', 420, 'shipped', NULL, NULL),
('OR0008', 'ME0008', 'ADRS0008', '2025-11-08 09:00:00', 700, 'completed', NULL, '2025-11-10 11:00:00'),
('OR0009', 'ME0009', 'ADRS0009', '2025-11-09 10:00:00', 420, 'cancelled', NULL, NULL),
('OR0010', 'ME0010', 'ADRS0010', '2025-11-10 11:00:00', 420, 'completed', NULL, '2025-11-11 13:00:00'),
('OR0011', 'ME0011', 'ADRS0011', '2025-11-11 01:00:00', 210, 'delivered', NULL, '2025-11-12 08:00:00'),
('OR0012', 'ME0012', 'ADRS0012', '2025-11-12 02:30:00', 700, 'shipped', NULL, NULL),
('OR0013', 'ME0013', 'ADRS0013', '2025-11-13 03:15:00', 525, 'completed', NULL, '2025-11-14 10:00:00'),
('OR0014', 'ME0014', 'ADRS0014', '2025-11-14 04:45:00', 420, 'cancelled', NULL, NULL),
('OR0015', 'ME0015', 'ADRS0015', '2025-11-15 05:20:00', 420, 'delivered', NULL, '2025-11-17 12:00:00'),
('OR0016', 'ME0001', 'ADRS0001', '2025-11-05 02:00:00', 700, 'completed', NULL, '2025-11-06 12:00:00'),
('OR0017', 'ME0002', 'ADRS0002', '2025-11-06 03:00:00', 420, 'shipped', NULL, NULL),
('OR0018', 'ME0003', 'ADRS0003', '2025-11-07 04:00:00', 420, 'completed', NULL, '2025-11-08 07:00:00'),
('OR0019', 'ME0004', 'ADRS0004', '2025-11-08 05:00:00', 420, 'delivered', NULL, '2025-11-20 10:00:00'),
('OR0020', 'ME0005', 'ADRS0005', '2025-11-09 06:00:00', 630, 'cancelled', NULL, NULL),
('OR0021', 'ME0001', 'ADRS0001', '2025-11-21 09:00:00', 420, 'pending', NULL, NULL),
('OR0022', 'ME0002', 'ADRS0002', '2025-11-22 10:30:00', 315, 'pending', NULL, NULL),
('OR0023', 'ME0003', 'ADRS0003', '2025-11-23 14:15:00', 420, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_history`
--

CREATE TABLE `order_history` (
  `history_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `status` varchar(100) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `changed_by` varchar(10) NOT NULL,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_history`
--

INSERT INTO `order_history` (`history_id`, `order_id`, `status`, `changed_at`, `changed_by`, `message`) VALUES
('HIS0001', 'OR0001', 'pending', '2025-11-01 02:00:00', 'ME0001', 'Your order has been placed.'),
('HIS0002', 'OR0001', 'shipped', '2025-11-01 06:00:00', 'AD0001', 'Your parcel has been picked up.'),
('HIS0003', 'OR0001', 'delivered', '2025-11-02 10:00:00', 'AD0001', 'Your order has been delivered.'),
('HIS0004', 'OR0001', 'completed', '2025-11-05 02:00:00', 'AD0001', 'Order completed. Thank you!'),
('HIS0005', 'OR0002', 'pending', '2025-11-02 03:00:00', 'ME0002', 'Your order has been placed.'),
('HIS0006', 'OR0002', 'shipped', '2025-11-02 08:00:00', 'AD0002', 'Your parcel has been picked up.'),
('HIS0007', 'OR0002', 'delivered', '2025-11-03 12:00:00', 'AD0002', 'Your order has been delivered.'),
('HIS0008', 'OR0003', 'pending', '2025-11-03 04:00:00', 'ME0003', 'Your order has been placed.'),
('HIS0009', 'OR0003', 'cancelled', '2025-11-03 10:00:00', 'ME0003', 'Your order has been cancelled.'),
('HIS0010', 'OR0004', 'pending', '2025-11-04 05:00:00', 'ME0004', 'Your order has been placed.'),
('HIS0011', 'OR0004', 'cancelled', '2025-11-04 06:00:00', 'ME0004', 'Your order has been cancelled.'),
('HIS0012', 'OR0005', 'pending', '2025-11-05 06:00:00', 'ME0005', 'Your order has been placed.'),
('HIS0013', 'OR0005', 'shipped', '2025-11-05 10:00:00', 'AD0002', 'Your parcel has been picked up.'),
('HIS0014', 'OR0005', 'delivered', '2025-11-06 11:00:00', 'AD0002', 'Your order has been delivered.'),
('HIS0015', 'OR0006', 'pending', '2025-11-06 07:00:00', 'ME0006', 'Your order has been placed.'),
('HIS0016', 'OR0006', 'shipped', '2025-11-06 12:00:00', 'AD0004', 'Your parcel has been picked up.'),
('HIS0017', 'OR0006', 'delivered', '2025-11-07 15:00:00', 'AD0004', 'Your order has been delivered.'),
('HIS0018', 'OR0007', 'pending', '2025-11-07 08:00:00', 'ME0007', 'Your order has been placed.'),
('HIS0019', 'OR0007', 'shipped', '2025-11-07 14:00:00', 'AD0001', 'Your parcel has been picked up.'),
('HIS0020', 'OR0008', 'pending', '2025-11-08 09:00:00', 'ME0008', 'Your order has been placed.'),
('HIS0021', 'OR0008', 'shipped', '2025-11-08 13:00:00', 'AD0005', 'Your parcel has been picked up.'),
('HIS0022', 'OR0008', 'delivered', '2025-11-10 11:00:00', 'AD0005', 'Your order has been delivered.'),
('HIS0023', 'OR0008', 'completed', '2025-11-10 11:30:00', 'AD0005', 'Order completed. Thank you!'),
('HIS0024', 'OR0009', 'pending', '2025-11-09 10:00:00', 'ME0009', 'Your order has been placed.'),
('HIS0025', 'OR0009', 'cancelled', '2025-11-09 12:00:00', 'ME0009', 'Your order has been cancelled.'),
('HIS0026', 'OR0010', 'pending', '2025-11-10 11:00:00', 'ME0010', 'Your order has been placed.'),
('HIS0027', 'OR0010', 'shipped', '2025-11-10 16:00:00', 'AD0004', 'Your parcel has been picked up.'),
('HIS0028', 'OR0010', 'delivered', '2025-11-11 09:00:00', 'AD0004', 'Your order has been delivered.'),
('HIS0029', 'OR0010', 'completed', '2025-11-12 07:00:00', 'AD0004', 'Order completed. Thank you!'),
('HIS0030', 'OR0011', 'pending', '2025-11-11 01:00:00', 'ME0011', 'Your order has been placed.'),
('HIS0031', 'OR0011', 'shipped', '2025-11-11 04:00:00', 'AD0003', 'Your parcel has been picked up.'),
('HIS0032', 'OR0011', 'delivered', '2025-11-12 08:00:00', 'AD0003', 'Your order has been delivered.'),
('HIS0033', 'OR0012', 'pending', '2025-11-12 02:30:00', 'ME0012', 'Your order has been placed.'),
('HIS0034', 'OR0012', 'shipped', '2025-11-12 07:00:00', 'AD0002', 'Your parcel has been picked up.'),
('HIS0035', 'OR0013', 'pending', '2025-11-13 03:15:00', 'ME0013', 'Your order has been placed.'),
('HIS0036', 'OR0013', 'shipped', '2025-11-13 10:00:00', 'AD0005', 'Your parcel has been picked up.'),
('HIS0037', 'OR0013', 'delivered', '2025-11-14 10:00:00', 'AD0005', 'Your order has been delivered.'),
('HIS0038', 'OR0013', 'completed', '2025-11-15 09:00:00', 'AD0005', 'Order completed. Thank you!'),
('HIS0039', 'OR0014', 'pending', '2025-11-14 04:45:00', 'ME0014', 'Your order has been placed.'),
('HIS0040', 'OR0014', 'cancelled', '2025-11-14 06:00:00', 'ME0014', 'Your order has been cancelled.'),
('HIS0041', 'OR0015', 'pending', '2025-11-15 05:20:00', 'ME0015', 'Your order has been placed.'),
('HIS0042', 'OR0015', 'shipped', '2025-11-16 02:00:00', 'AD0003', 'Your parcel has been picked up.'),
('HIS0043', 'OR0015', 'delivered', '2025-11-17 12:00:00', 'AD0003', 'Your order has been delivered.'),
('HIS0044', 'OR0016', 'pending', '2025-11-16 03:00:00', 'ME0016', 'Your order has been placed.'),
('HIS0045', 'OR0016', 'shipped', '2025-11-16 09:00:00', 'AD0002', 'Your parcel has been picked up.'),
('HIS0046', 'OR0016', 'delivered', '2025-11-17 10:00:00', 'AD0002', 'Your order has been delivered.'),
('HIS0047', 'OR0016', 'completed', '2025-11-18 08:00:00', 'AD0002', 'Order completed. Thank you!'),
('HIS0048', 'OR0017', 'pending', '2025-11-17 04:00:00', 'ME0017', 'Your order has been placed.'),
('HIS0049', 'OR0017', 'shipped', '2025-11-17 12:00:00', 'AD0001', 'Your parcel has been picked up.'),
('HIS0050', 'OR0018', 'pending', '2025-11-18 06:00:00', 'ME0018', 'Your order has been placed.'),
('HIS0051', 'OR0018', 'shipped', '2025-11-18 11:00:00', 'AD0004', 'Your parcel has been picked up.'),
('HIS0052', 'OR0018', 'delivered', '2025-11-19 09:00:00', 'AD0004', 'Your order has been delivered.'),
('HIS0053', 'OR0018', 'completed', '2025-11-20 08:00:00', 'AD0004', 'Order completed. Thank you!'),
('HIS0054', 'OR0019', 'pending', '2025-11-19 05:00:00', 'ME0019', 'Your order has been placed.'),
('HIS0055', 'OR0019', 'shipped', '2025-11-19 12:00:00', 'AD0005', 'Your parcel has been picked up.'),
('HIS0056', 'OR0019', 'delivered', '2025-11-20 10:00:00', 'AD0005', 'Your order has been delivered.'),
('HIS0057', 'OR0020', 'pending', '2025-11-20 06:00:00', 'ME0020', 'Your order has been placed.'),
('HIS0058', 'OR0020', 'cancelled', '2025-11-20 08:00:00', 'ME0020', 'Your order has been cancelled.'),
('HIS0059', 'OR0021', 'pending', '2025-11-21 09:00:00', 'ME0001', 'Your order has been placed.'),
('HIS0060', 'OR0022', 'pending', '2025-11-22 10:30:00', 'ME0002', 'Your order has been placed.'),
('HIS0061', 'OR0023', 'pending', '2025-11-23 14:15:00', 'ME0003', 'Your order has been placed.');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` varchar(8) NOT NULL,
  `order_id` varchar(6) NOT NULL,
  `product_id` varchar(6) NOT NULL,
  `product_qty` int(11) NOT NULL,
  `price` float NOT NULL,
  `subtotal` float NOT NULL,
  `user_rating` int(11) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  `rated_at` timestamp NULL DEFAULT NULL,
  `rating_photo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rating_photo`)),
  `rating_video` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rating_video`)),
  `review_status` enum('visible','hidden') DEFAULT 'visible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `product_qty`, `price`, `subtotal`, `user_rating`, `user_comment`, `rated_at`, `rating_photo`, `rating_video`, `review_status`) VALUES
('OI0001', 'OR0001', 'PR0001', 2, 210, 420, 5, 'Very comfortable and lightweight!', '2025-11-03 02:00:00', '[\"pr0001_review.jpg\"]', NULL, 'visible'),
('OI0002', 'OR0001', 'PR0002', 2, 105, 210, 4, 'Stylish frame, worth the price.', '2025-11-03 02:05:00', '[\"pr0002_review.jpg\"]', NULL, 'visible'),
('OI0003', 'OR0002', 'PR0003', 1, 420, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0004', 'OR0003', 'PR0001', 1, 210, 210, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0005', 'OR0004', 'PR0004', 3, 350, 1050, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0006', 'OR0005', 'PR0002', 4, 105, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0007', 'OR0006', 'PR0003', 1, 420, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0008', 'OR0007', 'PR0001', 2, 210, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0009', 'OR0008', 'PR0004', 2, 350, 700, 4, 'Good design, fits well.', '2025-11-11 02:00:00', '[\"pr0004_review2.jpg\"]', NULL, 'hidden'),
('OI0010', 'OR0009', 'PR0002', 4, 105, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0011', 'OR0010', 'PR0003', 1, 420, 420, 5, 'Very clear lens, happy with purchase.', '2025-11-12 01:00:00', '[\"pr0003_review3.jpg\"]', NULL, 'visible'),
('OI0012', 'OR0011', 'PR0001', 1, 210, 210, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0013', 'OR0012', 'PR0004', 2, 350, 700, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0014', 'OR0013', 'PR0002', 5, 105, 525, 5, 'Bought multiple as gifts, all good!', '2025-11-14 03:00:00', '[\"pr0002_review3.jpg\"]', NULL, 'visible'),
('OI0015', 'OR0014', 'PR0003', 1, 420, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0016', 'OR0015', 'PR0001', 2, 210, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0017', 'OR0016', 'PR0004', 2, 350, 700, 4, 'Good frame quality.', '2025-11-06 04:30:00', NULL, NULL, 'hidden'),
('OI0018', 'OR0017', 'PR0002', 4, 105, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0019', 'OR0018', 'PR0003', 1, 420, 420, 4, 'Happy with the purchase.', '2025-11-08 07:30:00', NULL, NULL, 'visible'),
('OI0020', 'OR0019', 'PR0003', 1, 420, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0021', 'OR0020', 'PR0001', 3, 210, 630, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0022', 'OR0021', 'PR0001', 2, 210, 420, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0023', 'OR0022', 'PR0002', 3, 105, 315, NULL, NULL, NULL, NULL, NULL, 'visible'),
('OI0024', 'OR0023', 'PR0003', 1, 420, 420, NULL, NULL, NULL, NULL, NULL, 'visible');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `amount` float NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent` varchar(255) DEFAULT NULL,
  `stripe_payment_method` varchar(255) DEFAULT NULL,
  `payment_method_type` varchar(20) NOT NULL,
  `card_brand` varchar(20) DEFAULT NULL,
  `card_funding` varchar(10) DEFAULT NULL,
  `last4` varchar(4) DEFAULT NULL,
  `bank_name` varchar(30) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `failed_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `amount`, `transaction_date`, `stripe_session_id`, `stripe_payment_intent`, `stripe_payment_method`, `payment_method_type`, `card_brand`, `card_funding`, `last4`, `bank_name`, `status`, `failed_reason`) VALUES
('PAY0001', 'OR0001', 630, '2025-11-01 02:02:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0002', 'OR0002', 420, '2025-11-02 03:02:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0003', 'OR0004', 1050, '2025-11-04 05:02:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0004', 'OR0006', 420, '2025-11-06 07:05:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0005', 'OR0007', 420, '2025-11-07 08:02:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0006', 'OR0008', 735, '2025-11-08 09:03:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0007', 'OR0010', 420, '2025-11-10 11:02:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0008', 'OR0012', 700, '2025-11-12 02:32:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0009', 'OR0013', 525, '2025-11-13 03:17:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0010', 'OR0014', 420, '2025-11-14 04:47:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0011', 'OR0003', 210, '2025-11-03 04:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0012', 'OR0005', 420, '2025-11-05 06:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0013', 'OR0009', 420, '2025-11-09 10:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0014', 'OR0011', 210, '2025-11-11 01:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0015', 'OR0015', 420, '2025-11-15 05:20:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0016', 'OR0016', 700, '2025-11-05 02:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0017', 'OR0017', 420, '2025-11-06 03:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0018', 'OR0018', 420, '2025-11-07 04:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0019', 'OR0019', 420, '2025-11-08 05:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0020', 'OR0020', 630, '2025-11-09 06:00:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0021', 'OR0021', 420, '2025-11-21 09:01:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0022', 'OR0022', 315, '2025-11-22 10:31:00', '', '', '', '', '', '', '', '', 'succeeded', NULL),
('PAY0023', 'OR0023', 420, '2025-11-23 14:16:00', '', '', '', '', '', '', '', '', 'succeeded', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` varchar(6) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_description` text NOT NULL,
  `product_price` float NOT NULL,
  `product_stock` int(11) NOT NULL,
  `product_image` varchar(255) NOT NULL,
  `category_id` varchar(6) NOT NULL,
  `product_status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `product_description`, `product_price`, `product_stock`, `product_image`, `category_id`, `product_status`) VALUES
('PR0001', 'SmartBuy Collection', 'A lightweight everyday frame designed for long hours of comfort, perfect for study, work, or casual wear.', 210, 100, 'SmartBuy Collection.JPG', 'CA0001', 1),
('PR0002', 'Ralph by Ralph Lauren', 'Sleek and modern design that enhances your facial features while offering reliable durability.', 350, 100, 'Ralph by Ralph Lauren.JPG', 'CA0001', 1),
('PR0003', 'Ashley Lala', 'High-quality acetate material that ensures long-lasting use while maintaining a premium feel.', 280, 100, 'Ashley Lala.JPG', 'CA0001', 1),
('PR0004', 'DNXY', 'Flexible arms designed for comfort, making this the ideal daily eyewear choice.', 300, 100, 'DNXY.JPG', 'CA0001', 1),
('PR0005', 'WH8SH', 'A fashionable rectangular frame suitable for both formal and casual outfits.', 190, 100, 'WH8SH.JPG', 'CA0001', 1),
('PR0006', 'Duckly', 'Durable and lightweight, offering a perfect balance of style and practicality.', 400, 100, 'Duckly 6.PNG, 6a.PNG, 6b.PNG', 'CA0001', 1),
('PR0007', 'RedBean RB2130', 'Designed for outdoor adventures, offering maximum sun protection with a trendy finish.', 350, 100, 'RedBean RB2130.JPG', 'CA0002', 1),
('PR0008', 'RedBean RB2133', 'A classic aviator shape that never goes out of style, perfect for everyday sun use.', 450, 100, 'RedBean RB2133.JPG', 'CA0002', 1),
('PR0009', 'RedBean RB2120', 'UV400-protected lenses that shield your eyes from harmful rays while keeping you stylish.', 430, 100, 'RedBean RB2120.JPG', 'CA0002', 1),
('PR0010', 'RedBean RB2139', 'Sleek metal bridge design for a clean, minimalist aesthetic.', 320, 100, 'RedBean RB2139.JPG', 'CA0002', 1),
('PR0011', 'RedBean RB2125', 'Retro round sunglasses that bring back vintage charm with modern comfort.', 450, 100, 'RedBean RB2125.JPG', 'CA0002', 1),
('PR0012', 'FreshKon Charmante Daily', 'Moisture-rich lenses designed to keep your eyes fresh even after long hours of screen time.', 80, 100, 'FreshKon Charmante Daily 1.JPG, 1a.JPG', 'CA0003', 1),
('PR0013', 'Acuvue Moist Daily', 'Daily wear lenses that offer exceptional comfort and natural color enhancement.', 70, 100, 'Acuvue Moist Daily.JPG', 'CA0003', 1),
('PR0014', 'Bincon Monthly', 'Natural color series that enhances your eye tone subtly and beautifully.', 70, 100, 'Bincon Monthly 5.JPG, 5a.JPG', 'CA0003', 1),
('PR0015', 'FreshKon ALLURING EYES Monthly', 'Monthly disposable lenses perfect for frequent wearers who want convenience and comfort.', 85, 100, 'FreshKon ALLURING EYES Monthly 4.JPG, 4a.JPG', 'CA0003', 1),
('PR0016', 'FreshKon COLORS FUSION Monthly', 'High-oxygen permeable material to keep your eyes hydrated and healthy.', 80, 100, 'FreshKon COLORS FUSION Monthly 2.JPG, 2a.JPG', 'CA0003', 1),
('PR0017', 'moody Mirror Mirror Monthly', 'Natural-looking lenses designed to subtly enhance your real eye color.', 60, 100, 'moody Mirror Mirror Monthly 6.JPEG, 6a.JPEG, 6b.JPEG', 'CA0003', 1),
('PR0018', 'RedBean RB1007', 'Soft nose pads and rounded edges to ensure maximum comfort and safety.', 250, 100, 'RedBean RB1007.JPG', 'CA0004', 1),
('PR0019', 'ToHi TH1003', 'Lightweight and flexible frame specially designed for active kids.', 200, 100, 'ToHi TH1003.JPG', 'CA0004', 1),
('PR0020', 'ToHi TH1002', 'Vibrant colors to make wearing glasses fun and exciting for children.', 200, 100, 'ToHi TH1002.JPG', 'CA0004', 1),
('PR0021', 'ToHi TH1001', 'Combination of durability and cute design, perfect for school and play.', 200, 100, 'ToHi TH1001.JPG', 'CA0004', 1),
('PR0022', 'Joy JY1009', 'Lightweight polycarbonate frame that provides safety and comfort for growing kids.', 180, 100, 'Joy JY1009.JPG', 'CA0004', 1);

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `receipt_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `issued_to` varchar(10) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_method` varchar(50) NOT NULL,
  `email_sent` tinyint(1) NOT NULL,
  `pdf_generated` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`receipt_id`, `order_id`, `issued_to`, `issued_at`, `delivery_method`, `email_sent`, `pdf_generated`) VALUES
('ER0001', 'OR0001', 'ME0001', '2025-11-02 01:05:00', 'email', 1, 1),
('ER0002', 'OR0002', 'ME0002', '2025-11-03 02:05:00', 'pdf', 0, 1),
('ER0003', 'OR0004', 'ME0004', '2025-11-05 02:10:00', 'email', 1, 1),
('ER0004', 'OR0005', 'ME0005', '2025-11-06 06:05:00', 'pdf', 0, 1),
('ER0005', 'OR0006', 'ME0006', '2025-11-07 02:15:00', 'email', 1, 1),
('ER0006', 'OR0007', 'ME0007', '2025-11-08 01:10:00', 'pdf', 0, 1),
('ER0007', 'OR0008', 'ME0008', '2025-11-10 03:10:00', 'email', 1, 1),
('ER0008', 'OR0009', 'ME0009', '2025-11-10 04:10:00', 'pdf', 0, 1),
('ER0009', 'OR0010', 'ME0010', '2025-11-11 01:05:00', 'email', 1, 1),
('ER0010', 'OR0012', 'ME0012', '2025-11-12 07:10:00', 'pdf', 0, 1),
('ER0011', 'OR0013', 'ME0013', '2025-11-14 01:10:00', 'email', 1, 1),
('ER00118', 'OR0020', 'ME0005', '2025-11-10 02:15:00', 'email', 1, 1),
('ER0012', 'OR0014', 'ME0014', '2025-11-15 01:10:00', 'pdf', 0, 1),
('ER0013', 'OR0015', 'ME0015', '2025-11-16 02:10:00', 'email', 1, 1),
('ER0014', 'OR0016', 'ME0001', '2025-11-06 02:15:00', 'email', 1, 1),
('ER0015', 'OR0017', 'ME0002', '2025-11-07 01:10:00', 'pdf', 0, 1),
('ER0016', 'OR0018', 'ME0003', '2025-11-08 01:15:00', 'email', 1, 1),
('ER0017', 'OR0019', 'ME0004', '2025-11-09 02:15:00', 'pdf', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `token_id` varchar(100) NOT NULL,
  `expire` datetime NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `type` varchar(50) DEFAULT 'password_reset'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token`
--

INSERT INTO `token` (`token_id`, `expire`, `user_id`, `type`) VALUES
('9f2dd48d0441b3f0e7999f71d5905dd9125664d4', '2025-12-17 20:27:56', 'ME0011', 'password_reset');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(6) NOT NULL,
  `role` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` char(1) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `date_of_birth` date NOT NULL,
  `photo` varchar(255) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(10) NOT NULL DEFAULT 'Inactive',
  `failed_attempts` int(11) DEFAULT 0,
  `lock_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `email`, `password`, `name`, `gender`, `phone`, `date_of_birth`, `photo`, `registration_date`, `status`, `failed_attempts`, `lock_until`) VALUES
('AD0001', 'Admin', 'admin1@gmail.com', '$2y$10$ELrFywiho7PikrA55mZxAOWcV/A4yMfjDqim3wjrqbIfGro1HMz3C', 'Admin Yeap', 'F', '198765432', '1985-05-05', 'admin1.jpg', '2025-12-17 11:17:02', 'Active', 0, NULL),
('AD0002', 'Admin', 'admin2@gmail.com', '$2y$10$JQ8eSFp4flLCP4kM6egYhuFdJ55Xg45RJTEmNTzPs3Gry6dENF9L.', 'Admin Lim', 'M', '198765432', '1980-12-12', 'admin2.jpg', '2025-12-17 11:17:33', 'Inactive', 0, NULL),
('AD0003', 'Admin', 'admin3@gmail.com', '$2y$10$kuljHiW5kES93KmX0.YdCe5hezj1ydd29dmkuGYE6bdoB4c6joCva', 'Admin Quak', 'F', '198765432', '1979-07-07', 'admin3.jpg', '2025-12-17 11:07:07', 'Active', 0, NULL),
('AD0004', 'Admin', 'admin4@gmail.com', '$2y$10$DsPwx0n5VTBw3MydW8EUNu06OqFMS/5HE76EzWGZnFzGs4DY9CIAG', 'Admin Ng', 'M', '198765432', '1982-09-09', 'admin4.jpg', '2025-12-17 11:07:46', 'Active', 0, NULL),
('AD0005', 'Admin', 'admin5@gmail.com', '$2y$10$tHo3dkcP.TI.To1YrnEOX.lAA2TcIcKI1xuY51b0vAZSsA3yVw7AS', 'Admin Low', 'F', '198765435', '1986-03-03', 'admin5.jpg', '2025-12-17 11:08:05', 'Active', 0, NULL),
('ME0001', 'Member', 'john.doe@gmail.com', '$2y$10$DHc0qbmlmTxdFxFq1m1l5..B5L/fC30CNb96RTTrlExmdJRK1Ts7u', 'John Doe', 'M', '123456780', '1990-05-12', 'john1.jpg', '2025-12-17 11:08:28', 'Active', 0, NULL),
('ME0002', 'Member', 'mary.lee@gmail.com', '$2y$10$4qizTXT0p9RwOhbVWzwoNuhc0S0dizE9cIjX7S8SivM4LOL3Lfzli', 'Mary Lee', 'F', '123456780', '1992-08-23', 'mary2.jpg', '2025-12-17 11:17:43', 'Inactive', 0, NULL),
('ME0003', 'Member', 'bob.tan@gmail.com', '$2y$10$IkSukzHMSH.04P4nHpi1ke12t30/keczTJue4hVaO97PFxT/9GMi6', 'Bob Tan', 'M', '123456780', '1988-01-05', 'bob3.jpg', '2025-12-17 11:08:51', 'Active', 0, NULL),
('ME0004', 'Member', 'alice.wong@gmail.com', '$2y$10$ht4ZnUrSq/IPpNYAwvYYN.iictp6xNZpuMJax35M2OP6yWDdwdsGu', 'Alice Wong', 'F', '123456780', '1995-02-14', 'alice4.jpg', '2025-12-17 11:09:04', 'Active', 0, NULL),
('ME0005', 'Member', 'david.chan@gmail.com', '$2y$10$V/2kOvUVO9/OPhP6HY2JCOVc7RJd/ydpbx2VxjWT5jHJJVc.FI17u', 'David Chan', 'M', '123456780', '1991-07-30', 'david5.jpg', '2025-12-17 11:09:15', 'Active', 0, NULL),
('ME0006', 'Member', 'susan.koh@gmail.com', '$2y$10$5FMYny7uUaj0KcXwwhz1neAGXBoK1tq4dlRsfLaiTGe9JRVmDZ67i', 'Susan Koh', 'F', '123456780', '1993-09-12', 'susan6.jpg', '2025-12-17 11:17:39', 'Inactive', 0, NULL),
('ME0007', 'Member', 'kevin.lim@gmail.com', '$2y$10$e1dNXWUoNStuNqMhx/.lR.XS.ztiZFk4AirMdisMMqA2WmDkVa5jG', 'Kevin Lim', 'M', '123456780', '1989-12-01', 'kevin7.jpg', '2025-12-17 11:09:41', 'Active', 0, NULL),
('ME0008', 'Member', 'kelly.ng@gmail.com', '$2y$10$QrA.LOaF7sQNWS1sJWvGC.peGNizobOC1QbkS0hP24nPtk9dNB5Na', 'Kelly Ng', 'F', '123456780', '1994-03-22', 'kelly8.jpg', '2025-12-17 11:09:56', 'Active', 0, NULL),
('ME0009', 'Member', 'eric.tan@gmail.com', '$2y$10$S/9tRqisc4.r6DGNYyDyIuT941pzm4TeBJUIsphg708jm0aYj2am.', 'Eric Tan', 'M', '123456789', '1990-06-18', 'eric9.jpg', '2025-12-17 11:10:07', 'Active', 0, NULL),
('ME0010', 'Member', 'amy.lim@gmail.com', '$2y$10$9mprAg3Rc4O1A3H3LhhxFuNs.lQY8Mi6j5IA990ji76LBrcbgvdTm', 'Amy Lim', 'F', '123456781', '1992-11-05', 'amy10.jpg', '2025-12-17 11:10:18', 'Active', 0, NULL),
('ME0011', 'Member', 'ronald.lee@gmail.com', '$2y$10$K6uyxwayzJoYJvzmcZeHv.RTFasamtxpAgtBB/rbBPSRE9gY0S.FW', 'Ronald Lee', 'M', '123456781', '1987-04-09', 'ronald11.jpg', '2025-12-17 11:10:31', 'Active', 0, NULL),
('ME0012', 'Member', 'julia.tan@gmail.com', '$2y$10$eqyNA.28q5j2.bRGuK31ReSrX6cq6Vv55F9sNArzObi8.M0e3Bjau', 'Julia Wong', 'F', '111111222', '1993-08-12', 'julia12.jpg', '2025-12-17 11:10:43', 'Active', 0, NULL),
('ME0013', 'Member', 'brian.choo@gmail.com', '$2y$10$fSSy3Z6T.TBmAMLPV9aK8esDBm1KBq1EvUgDiapIMwoNLcdBGcj0u', 'Brian Choo', 'M', '123456783', '1991-10-20', 'brian13.jpg', '2025-12-17 11:10:55', 'Active', 0, NULL),
('ME0014', 'Member', 'rachel.koh@gmail.com', '$2y$10$NCgY4Ok1mqyuqB6n4XXct.LFCaXrQs8ed9W4S8E9/oKSHgpnxN0Q6', 'Rachel Koh', 'F', '123456781', '1994-01-25', 'rachel14.jpg', '2025-12-17 11:11:07', 'Active', 0, NULL),
('ME0015', 'Member', 'steven.lim@gmail.com', '$2y$10$U2gk2.ZM.JU8icu/uYlwXOJUZ43w98XNnupyviOgIyEvBZLD80BkS', 'Steven Lim', 'M', '123456781', '1989-07-11', 'steven15.jpg', '2025-12-17 16:17:28', 'Inactive', 0, NULL),
('ME0018', 'Member', 'AliciaJia@gmail.com', '$2y$10$PpLEex7qH9hz8FLd5Xhh/.QptZULkNikWZ9HopxcLVcAVbnTVldui', 'Alicia Jia', 'F', '192233333', '1996-10-17', '6933812f9e2d9.jpg', '2025-12-17 11:14:41', 'Active', 0, NULL),
('ME0019', 'Member', 'QianEn12@gmail.com', '$2y$10$.a0OIWQf1nH8Ft955r8jKet4YKL2V.6UI3kr.OPWg7lL174r2mtNG', 'Qian En', 'F', '11909922', '2001-07-17', '69338c8b37cfe.jpg', '2025-12-17 11:16:04', 'Active', 0, NULL),
('ME0020', 'Member', 'NicoleLee@gmail.com', '$2y$10$h77gVnsUYGvCzZ9yglS3aewhHQC5yDslofgozBh9BAIkWu6h7r3qm', 'Nicole Lee', 'F', '19877534', '1999-12-17', '69339e0d34e5d.jpg', '2025-12-17 17:42:35', 'Active', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` varchar(10) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `removed_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `added_at`, `removed_at`) VALUES
('W0006', 'ME0007', 'PR0001', '2025-11-07 08:20:00', NULL),
('W0007', 'ME0008', 'PR0009', '2025-11-08 09:20:00', NULL),
('W0008', 'ME0009', 'PR0004', '2025-11-09 10:15:00', NULL),
('W0009', 'ME0010', 'PR0006', '2025-11-10 11:40:00', NULL),
('WL0001', 'ME0001', 'PR0005', '2025-11-01 01:30:00', NULL),
('WL0002', 'ME0002', 'PR0007', '2025-11-02 02:55:00', NULL),
('WL0003', 'ME0003', 'PR0002', '2025-11-03 03:50:00', NULL),
('WL0004', 'ME0004', 'PR0008', '2025-11-04 05:10:00', NULL),
('WL0005', 'ME0005', 'PR0003', '2025-11-05 06:10:00', NULL);

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
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_item_id` (`order_item_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `order_history`
--
ALTER TABLE `order_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `issued_to` (`issued_to`);

--
-- Indexes for table `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `wishlist_ibfk_2` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `cart_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `cart_item_ibfk_3` FOREIGN KEY (`order_item_id`) REFERENCES `order_item` (`order_item_id`);

--
-- Constraints for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD CONSTRAINT `email_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
