-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 04:11 AM
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
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  `default_flag` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Address table insert with lat/lng included
-- ============================================

INSERT INTO address 
(address_id, user_id, address_line1, address_line2, city, state, postcode, country, default_flag, created_at, latitude, longitude)
VALUES
('ADRS0001','ME0001','123 Main Street','Unit 1A','Kuala Lumpur','W.P. Kuala Lumpur','50000','Malaysia',1,'2025-11-01 09:05:00',3.1390,101.6869),
('ADRS0002','ME0002','456 Market Road','','Shah Alam','Selangor','40000','Malaysia',1,'2025-11-02 10:15:00',3.0738,101.5183),
('ADRS0003','ME0003','789 Hill Street','Apt 12B','Penang','Penang','10050','Malaysia',1,'2025-11-03 11:20:00',5.4164,100.3327),
('ADRS0004','ME0004','321 River Lane','','Kota Kinabalu','Sabah','88000','Malaysia',1,'2025-11-04 12:25:00',5.9804,116.0735),
('ADRS0005','ME0005','654 Garden Avenue','Unit 5C','Ipoh','Perak','30000','Malaysia',1,'2025-11-05 13:30:00',4.5975,101.0901),
('ADRS0006','ME0006','987 Sunset Blvd','','Petaling Jaya','Selangor','46000','Malaysia',1,'2025-11-06 14:35:00',3.1073,101.6067),
('ADRS0007','ME0007','246 Sunrise Street','Unit 7B','Melaka','Melaka','75000','Malaysia',1,'2025-11-07 15:40:00',2.1896,102.2501),
('ADRS0008','ME0008','135 Ocean Road','','Ampang','Selangor','68000','Malaysia',1,'2025-11-08 16:45:00',3.1448,101.7415),
('ADRS0009','ME0009','864 Mountain Lane','','Penang','Penang','10080','Malaysia',1,'2025-11-09 17:50:00',5.4164,100.3327),
('ADRS0010','ME0010','753 Forest Street','Unit 10C','Kuala Lumpur','W.P. Kuala Lumpur','50450','Malaysia',1,'2025-11-10 18:55:00',3.1390,101.6869),
('ADRS0011','ME0011','951 Riverbank Rd','','Putrajaya','W.P. Putrajaya','62000','Malaysia',1,'2025-11-11 09:00:00',2.9264,101.6964),
('ADRS0012','ME0012','159 Hilltop Ave','Unit 12B','Penang','Penang','10100','Malaysia',1,'2025-11-12 10:05:00',5.4164,100.3327),
('ADRS0013','ME0013','357 Valley Street','Unit 13C','Kota Bharu','Kelantan','15000','Malaysia',1,'2025-11-13 11:10:00',6.1250,102.2383),
('ADRS0014','ME0014','753 Garden Lane','','Pahang','Pahang','25000','Malaysia',1,'2025-11-14 12:15:00',3.8071,103.3262),
('ADRS0015','ME0015','951 Lakeview Blvd','Unit 15A','Selangor','Selangor','43000','Malaysia',1,'2025-11-15 13:20:00',3.0738,101.5183),
('ADRS0016','ME0016','900 Blue Street','','Kuala Lumpur','W.P. Kuala Lumpur','50450','Malaysia',1,'2025-11-01 09:10:00',3.1390,101.6869),
('ADRS0017','ME0017','399 Star Street','','Penang','Penang','10050','Malaysia',1,'2025-11-02 09:15:00',5.4164,100.3327),
('ADRS0018','ME0018','15 Relax Street','','Kota Bharu','Kelantan','15000','Malaysia',1,'2025-11-03 09:20:00',6.1250,102.2383),
('ADRS0019','ME0019','88 Rich Street','','Ampang','Selangor','68000','Malaysia',1,'2025-11-04 09:25:00',3.1448,101.7415),
('ADRS0020','ME0020','78 Sleep Street','','Petaling Jaya','Selangor','46000','Malaysia',1,'2025-11-05 09:30:00',3.1073,101.6067);

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
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
('CA0001', 'Glasses'),
('CA0002', 'Sunglasses'),
('CA0003', 'Contact Lens'),
('CA0004', 'Kids');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`verification_id`, `user_id`, `token`, `expiry`, `is_used`, `created_at`) VALUES
(1, 'ME0001', 'a3f9c1d4e7b2178a90bcfe1234abcd56ef90781234cd56ef78ab9012cd45ef67', '2025-11-02 09:00:00', 1, '2025-11-01 09:00:00'),
(2, 'ME0002', 'b7e4d9c3a1f2567890ab34cd56ef7812cd90ef34567812abcd34ef5678cd12ef', '2025-11-03 10:10:00', 1, '2025-11-02 10:10:00'),
(3, 'ME0003', 'c1d2e3f4a5b67890cd12ef34ab56cd7890ef12cd34ab56ef7890cd12ef34ab56', '2025-11-04 11:15:00', 0, '2025-11-03 11:15:00'),
(4, 'ME0004', 'd4c3b2e1f0a98765cd12ef7890ab34ef56cd78ab12ef34cd5678ab12ef90cd34', '2025-11-05 12:20:00', 1, '2025-11-04 12:20:00'),
(5, 'ME0005', 'e5f6a7b8c9d01234ef90ab12cd34ef78ab12cd56ef7890ab34cd12ef5678cd90', '2025-11-06 13:25:00', 1, '2025-11-05 13:25:00'),
(6, 'ME0006', 'f9e8d7c6b5a40123cd90ef12ab34cd56ef78ab34cd12ef56ab78cd90ef12ab34', '2025-11-07 14:30:00', 0, '2025-11-06 14:30:00'),
(7, 'ME0007', 'a1b2c3d4e5f67890cd12ef345678ab12ef90cd34ab56ef7890cd12ef34ab5678', '2025-11-08 15:35:00', 1, '2025-11-07 15:35:00'),
(8, 'ME0008', 'b2c3d4e5f6a78901ef34cd12ab56ef78cd12ef34ab56cd78ef9012cd34ab5678', '2025-11-09 16:40:00', 1, '2025-11-08 16:40:00'),
(9, 'ME0009', 'c3d4e5f6a7b89012cd34ef56ab78cd90ef12ab34cd56ef78ab90cd12ef3456ab', '2025-11-10 17:45:00', 0, '2025-11-09 17:45:00'),
(10, 'ME0010', 'd4e5f6a7b8c90123ef56ab12cd90ef34ab56cd78ef12ab90cd34ef78ab12cd34', '2025-11-11 18:50:00', 1, '2025-11-10 18:50:00'),
(11, 'ME0011', 'e5f6a7b8c9d01234ab78cd56ef12cd34ef90ab12cd56ef78cd12ef34ab7890cd', '2025-11-12 09:55:00', 1, '2025-11-11 09:55:00'),
(12, 'ME0012', 'f6a7b8c9d0e12345cd12ef90ab34cd78ef12ab34cd90ef78ab34cd12ef56ab78', '2025-11-13 10:00:00', 1, '2025-11-12 10:00:00'),
(13, 'ME0013', 'a7b8c9d0e1f23456ef12cd34ab56ef90cd34ab56ef9012cd78ab34ef56cd12ef', '2025-11-14 11:05:00', 0, '2025-11-13 11:05:00'),
(14, 'ME0014', 'b8c9d0e1f2a34567ab12cd34ef90ab56cd12ef34ab78cd56ef3412ab56cd78ef', '2025-11-15 12:10:00', 1, '2025-11-14 12:10:00'),
(15, 'ME0015', 'c9d0e1f2a3b45678cd34ab12ef56cd90ab12ef34cd78ab56ef12cd34ab78ef90', '2025-11-16 13:15:00', 1, '2025-11-15 13:15:00'),
(16, 'AD0001', 'a1c2e3f4b5d67890ef12cd34ab56ef78cd12ab90ef34cd56ab78ef12cd3456ef', '2025-11-02 09:00:00', 1, '2025-11-01 09:00:00'),
(17, 'AD0002', 'b1d2f3e4c5a78901cd34ef12ab56cd78ef90ab12cd34ef56ab12ef34cd5678ab', '2025-11-03 09:10:00', 1, '2025-11-02 09:10:00'),
(18, 'AD0003', 'c1e2f3a4d5b89012ef56cd34ab78ef12cd34ab56ef12cd90ab56ef34cd12ab78', '2025-11-04 09:20:00', 1, '2025-11-03 09:20:00'),
(19, 'AD0004', 'd1f2a3e4c5b90123ab78cd56ef34cd12ab56ef90cd12ef78ab34cd12ef78cd34', '2025-11-05 09:30:00', 1, '2025-11-04 09:30:00'),
(20, 'AD0005', 'e1a2b3c4d5f01234cd12ef78ab34cd56ef12ab34cd90ef56ab78cd12ef34ab90', '2025-11-06 09:40:00', 1, '2025-11-05 09:40:00');

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
  `cancelled_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `user_id`, `address_id`, `order_date`, `total_amount`, `status`, `cancelled_reason`) VALUES
('OR0001', 'ME0001', 'ADRS0001', '2025-11-01 02:00:00', 630, 'completed', NULL),
('OR0002', 'ME0002', 'ADRS0002', '2025-11-02 03:00:00', 420, 'completed', NULL),
('OR0003', 'ME0003', 'ADRS0003', '2025-11-03 04:00:00', 210, 'cancelled', 'Ordered by mistake'),
('OR0004', 'ME0004', 'ADRS0004', '2025-11-04 05:00:00', 1050, 'completed', NULL),
('OR0005', 'ME0005', 'ADRS0005', '2025-11-05 06:00:00', 840, 'shipped', NULL),
('OR0006', 'ME0006', 'ADRS0006', '2025-11-06 07:00:00', 315, 'completed', NULL),
('OR0007', 'ME0007', 'ADRS0007', '2025-11-07 08:00:00', 525, 'completed', NULL),
('OR0008', 'ME0008', 'ADRS0008', '2025-11-08 09:00:00', 735, 'completed', NULL),
('OR0009', 'ME0009', 'ADRS0009', '2025-11-09 10:00:00', 420, 'shipped', NULL),
('OR0010', 'ME0010', 'ADRS0010', '2025-11-10 11:00:00', 630, 'completed', NULL),
('OR0011', 'ME0011', 'ADRS0011', '2025-11-11 01:00:00', 210, 'cancelled', 'Changed mind'),
('OR0012', 'ME0012', 'ADRS0012', '2025-11-12 02:30:00', 840, 'shipped', NULL),
('OR0013', 'ME0013', 'ADRS0013', '2025-11-13 03:15:00', 1050, 'completed', NULL),
('OR0014', 'ME0014', 'ADRS0014', '2025-11-14 04:45:00', 315, 'completed', NULL),
('OR0015', 'ME0015', 'ADRS0015', '2025-11-15 05:20:00', 525, 'shipped', NULL),
('OR0016', 'ME0001', 'ADRS0001', '2025-11-05 02:00:00', 735, 'completed', NULL),
('OR0017', 'ME0002', 'ADRS0002', '2025-11-06 03:00:00', 420, 'completed', NULL),
('OR0018', 'ME0003', 'ADRS0003', '2025-11-07 04:00:00', 210, 'completed', NULL),
('OR0019', 'ME0004', 'ADRS0004', '2025-11-08 05:00:00', 630, 'shipped', NULL),
('OR0020', 'ME0005', 'ADRS0005', '2025-11-09 06:00:00', 1050, 'completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_history`
--

CREATE TABLE `order_history` (
  `history_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `status` varchar(100) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `changed_by` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_history`
--

INSERT INTO `order_history` (`history_id`, `order_id`, `status`, `changed_at`, `changed_by`) VALUES
('HIS0001', 'OR0001', 'processing', '2025-11-01 02:00:00', 'ME0001'),
('HIS0002', 'OR0001', 'shipped', '2025-11-01 06:00:00', 'AD0001'),
('HIS0003', 'OR0001', 'completed', '2025-11-02 01:00:00', 'AD0001'),
('HIS0004', 'OR0002', 'processing', '2025-11-02 03:00:00', 'ME0002'),
('HIS0005', 'OR0002', 'completed', '2025-11-03 02:00:00', 'AD0002'),
('HIS0006', 'OR0003', 'processing', '2025-11-03 04:00:00', 'ME0003'),
('HIS0007', 'OR0003', 'cancelled', '2025-11-03 04:30:00', 'ME0003'),
('HIS0008', 'OR0004', 'processing', '2025-11-04 05:00:00', 'ME0004'),
('HIS0009', 'OR0004', 'shipped', '2025-11-04 07:00:00', 'AD0003'),
('HIS0010', 'OR0004', 'completed', '2025-11-05 02:00:00', 'AD0003'),
('HIS0011', 'OR0005', 'processing', '2025-11-05 06:00:00', 'ME0005'),
('HIS0012', 'OR0005', 'shipped', '2025-11-05 10:00:00', 'AD0002'),
('HIS0013', 'OR0006', 'processing', '2025-11-06 07:00:00', 'ME0006'),
('HIS0014', 'OR0006', 'completed', '2025-11-07 02:00:00', 'AD0004'),
('HIS0015', 'OR0007', 'processing', '2025-11-07 08:00:00', 'ME0007'),
('HIS0016', 'OR0007', 'completed', '2025-11-08 01:00:00', 'AD0001'),
('HIS0017', 'OR0008', 'processing', '2025-11-08 09:00:00', 'ME0008'),
('HIS0018', 'OR0008', 'completed', '2025-11-10 03:00:00', 'AD0005'),
('HIS0019', 'OR0009', 'processing', '2025-11-09 10:00:00', 'ME0009'),
('HIS0020', 'OR0009', 'shipped', '2025-11-10 00:00:00', 'AD0003'),
('HIS0021', 'OR0010', 'processing', '2025-11-10 11:00:00', 'ME0010'),
('HIS0022', 'OR0010', 'completed', '2025-11-11 01:00:00', 'AD0004'),
('HIS0023', 'OR0011', 'processing', '2025-11-11 01:00:00', 'ME0011'),
('HIS0024', 'OR0011', 'cancelled', '2025-11-11 01:30:00', 'ME0011'),
('HIS0025', 'OR0012', 'processing', '2025-11-12 02:30:00', 'ME0012'),
('HIS0026', 'OR0012', 'shipped', '2025-11-12 07:00:00', 'AD0002'),
('HIS0027', 'OR0013', 'processing', '2025-11-13 03:15:00', 'ME0013'),
('HIS0028', 'OR0013', 'completed', '2025-11-14 01:00:00', 'AD0005'),
('HIS0029', 'OR0014', 'processing', '2025-11-14 04:45:00', 'ME0014'),
('HIS0030', 'OR0014', 'completed', '2025-11-15 01:00:00', 'AD0001'),
('HIS0031', 'OR0015', 'processing', '2025-11-15 05:20:00', 'ME0015'),
('HIS0032', 'OR0015', 'shipped', '2025-11-16 02:00:00', 'AD0003'),
('HIS0033', 'OR0016', 'processing', '2025-11-05 02:00:00', 'ME0001'),
('HIS0034', 'OR0016', 'completed', '2025-11-06 02:00:00', 'AD0002'),
('HIS0035', 'OR0017', 'processing', '2025-11-06 03:00:00', 'ME0002'),
('HIS0036', 'OR0017', 'completed', '2025-11-07 01:00:00', 'AD0004'),
('HIS0037', 'OR0018', 'processing', '2025-11-07 04:00:00', 'ME0003'),
('HIS0038', 'OR0018', 'completed', '2025-11-08 01:00:00', 'AD0001'),
('HIS0039', 'OR0019', 'processing', '2025-11-08 05:00:00', 'ME0004'),
('HIS0040', 'OR0019', 'shipped', '2025-11-09 02:00:00', 'AD0005'),
('HIS0041', 'OR0020', 'processing', '2025-11-09 06:00:00', 'ME0005'),
('HIS0042', 'OR0020', 'completed', '2025-11-10 02:00:00', 'AD0003');

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
  `rating_video` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rating_video`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `product_qty`, `price`, `subtotal`, `user_rating`, `user_comment`, `rated_at`, `rating_photo`, `rating_video`) VALUES
('OI0001', 'OR0001', 'PR0001', 2, 210, 420, 5, 'Very comfortable and lightweight!', '2025-11-03 02:00:00', '[\"pr0001_review.jpg\"]', NULL),
('OI0002', 'OR0001', 'PR0002', 2, 105, 210, 4, 'Stylish frame, worth the price.', '2025-11-03 02:05:00', '[\"pr0002_review.jpg\"]', NULL),
('OI0003', 'OR0002', 'PR0003', 1, 420, 420, 5, 'High-quality and looks premium.', '2025-11-04 03:30:00', '[\"pr0003_review.jpg\"]', NULL),
('OI0004', 'OR0003', 'PR0001', 1, 210, 210, NULL, NULL, NULL, NULL, NULL),
('OI0005', 'OR0004', 'PR0004', 3, 350, 1050, 4, 'Great fit, slightly tight at first.', '2025-11-06 04:00:00', '[\"pr0004_review.jpg\"]', NULL),
('OI0006', 'OR0005', 'PR0002', 4, 105, 420, 4, 'Good product, delivery was fast.', '2025-11-07 07:00:00', '[\"pr0002_review2.jpg\"]', NULL),
('OI0007', 'OR0006', 'PR0003', 1, 420, 420, 5, 'Excellent clarity and quality.', '2025-11-07 10:20:00', '[\"pr0003_review2.jpg\"]', NULL),
('OI0008', 'OR0007', 'PR0001', 2, 210, 420, 5, 'My favourite daily glasses!', '2025-11-08 02:30:00', '[\"pr0001_review2.jpg\"]', NULL),
('OI0009', 'OR0008', 'PR0004', 2, 350, 700, 4, 'Good design, fits well.', '2025-11-11 02:00:00', '[\"pr0004_review2.jpg\"]', NULL),
('OI0010', 'OR0009', 'PR0002', 4, 105, 420, 4, 'Nice and sturdy.', '2025-11-10 10:30:00', NULL, NULL),
('OI0011', 'OR0010', 'PR0003', 1, 420, 420, 5, 'Very clear lens, happy with purchase.', '2025-11-12 01:00:00', '[\"pr0003_review3.jpg\"]', NULL),
('OI00118', 'OR0017', 'PR0002', 4, 105, 420, 5, 'Recommended! Very durable.', '2025-11-07 04:00:00', '[\"pr0002_review4.jpg\"]', NULL),
('OI0012', 'OR0011', 'PR0001', 1, 210, 210, NULL, NULL, NULL, NULL, NULL),
('OI0013', 'OR0012', 'PR0004', 2, 350, 700, 4, 'Design is nice, quality is good.', '2025-11-13 08:00:00', NULL, NULL),
('OI0014', 'OR0013', 'PR0002', 5, 105, 525, 5, 'Bought multiple as gifts, all good!', '2025-11-14 03:00:00', '[\"pr0002_review3.jpg\"]', NULL),
('OI0015', 'OR0014', 'PR0003', 1, 420, 420, 4, 'Comfortable to wear whole day.', '2025-11-15 03:30:00', NULL, NULL),
('OI0016', 'OR0015', 'PR0001', 2, 210, 420, 5, 'Great for work and study.', '2025-11-17 01:15:00', '[\"pr0001_review3.jpg\"]', NULL),
('OI0017', 'OR0016', 'PR0004', 2, 350, 700, 4, 'Good frame quality.', '2025-11-06 04:30:00', NULL, NULL),
('OI0019', 'OR0019', 'PR0003', 1, 420, 420, 4, 'Happy with the purchase.', '2025-11-10 04:30:00', NULL, NULL),
('OI0020', 'OR0020', 'PR0001', 3, 210, 630, 5, 'Exactly as advertised!', '2025-11-09 10:30:00', '[\"pr0001_review4.jpg\"]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `amount` float NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method_id` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `failed_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `amount`, `transaction_date`, `payment_method_id`, `status`, `failed_reason`) VALUES
('PAY0001', 'OR0001', 630, '2025-11-01 02:02:00', 'PM0001', 'succeeded', NULL),
('PAY0002', 'OR0002', 420, '2025-11-02 03:02:00', 'PM0003', 'succeeded', NULL),
('PAY0003', 'OR0004', 1050, '2025-11-04 05:02:00', 'PM0005', 'succeeded', NULL),
('PAY0004', 'OR0006', 315, '2025-11-06 07:05:00', 'PM0007', 'succeeded', NULL),
('PAY0005', 'OR0007', 525, '2025-11-07 08:02:00', 'PM0008', 'succeeded', NULL),
('PAY0006', 'OR0008', 735, '2025-11-08 09:03:00', 'PM0009', 'succeeded', NULL),
('PAY0007', 'OR0010', 630, '2025-11-10 11:02:00', 'PM0010', 'succeeded', NULL),
('PAY0008', 'OR0012', 840, '2025-11-12 02:32:00', 'PM0003', 'succeeded', NULL),
('PAY0009', 'OR0013', 1050, '2025-11-13 03:17:00', 'PM0004', 'succeeded', NULL),
('PAY0010', 'OR0014', 315, '2025-11-14 04:47:00', 'PM0005', 'succeeded', NULL),
('PAY0011', 'OR0016', 735, '2025-11-05 02:02:00', 'PM0001', 'succeeded', NULL),
('PAY0012', 'OR0017', 420, '2025-11-06 03:02:00', 'PM0003', 'succeeded', NULL),
('PAY0013', 'OR0018', 210, '2025-11-07 04:02:00', 'PM0004', 'succeeded', NULL),
('PAY0014', 'OR0020', 1050, '2025-11-09 06:02:00', 'PM0006', 'succeeded', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `payment_method_id` varchar(10) NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `provider` varchar(20) NOT NULL DEFAULT 'stripe',
  `token` varchar(60) NOT NULL,
  `brand` varchar(20) NOT NULL,
  `last4` char(4) NOT NULL,
  `expiry_month` tinyint(4) NOT NULL,
  `exiry_year` smallint(6) NOT NULL,
  `is_default` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`payment_method_id`, `user_id`, `provider`, `token`, `brand`, `last4`, `expiry_month`, `exiry_year`, `is_default`, `created_at`) VALUES
('PM0001', 'ME0001', 'stripe', 'pm_test_0001', 'Visa', '4242', 11, 2030, 1, '2025-11-01 01:00:00'),
('PM0002', 'ME0001', 'stripe', 'pm_test_0002', 'Mastercard', '4444', 8, 2031, 0, '2025-11-01 01:05:00'),
('PM0003', 'ME0002', 'stripe', 'pm_test_0003', 'Visa', '4242', 10, 2030, 1, '2025-11-02 02:20:00'),
('PM0004', 'ME0003', 'stripe', 'pm_test_0004', 'Visa', '4242', 9, 2030, 1, '2025-11-03 03:30:00'),
('PM0005', 'ME0004', 'stripe', 'pm_test_0005', 'Mastercard', '4444', 12, 2032, 1, '2025-11-04 04:40:00'),
('PM0006', 'ME0005', 'stripe', 'pm_test_0006', 'Visa', '4242', 11, 2032, 1, '2025-11-05 05:40:00'),
('PM0007', 'ME0006', 'stripe', 'pm_test_0007', 'Visa', '4242', 4, 2030, 1, '2025-11-06 06:40:00'),
('PM0008', 'ME0007', 'stripe', 'pm_test_0008', 'Mastercard', '4444', 1, 2032, 1, '2025-11-07 07:45:00'),
('PM0009', 'ME0008', 'stripe', 'pm_test_0009', 'Visa', '4242', 2, 2033, 1, '2025-11-08 08:50:00'),
('PM0010', 'ME0009', 'stripe', 'pm_test_0010', 'Visa', '4242', 5, 2031, 1, '2025-11-09 09:55:00');

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
  `pdf_genearted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`receipt_id`, `order_id`, `issued_to`, `issued_at`, `delivery_method`, `email_sent`, `pdf_genearted`) VALUES
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
  `status` varchar(10) NOT NULL DEFAULT 'Inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `email`, `password`, `name`, `gender`, `phone`, `date_of_birth`, `photo`, `registration_date`, `status`) VALUES
('AD0001', 'Admin', 'admin1@example.com', '145e65c74ed9de4ed01adc3b01b09667f12517b5', 'Admin Yeap', 'F', '198765432', '1985-05-05', 'admin1.jpg', '2025-12-06 02:57:00', 'Active'),
('AD0002', 'Admin', 'admin2@example.com', '7b2fc68474634c30afacecaa609d0c2d101c1c76', 'Admin Lim', 'M', '198765432', '1980-12-12', 'admin2.jpg', '2025-12-06 02:57:00', 'Active'),
('AD0003', 'Admin', 'admin3@example.com', 'dfd964e2897285b15954a244b06f3e0dd6394cda', 'Admin Quak', 'F', '198765432', '1979-07-07', 'admin3.jpg', '2025-12-06 02:57:00', 'Active'),
('AD0004', 'Admin', 'admin4@example.com', '989c21af902e9628345bc12eb0dc121e588d2bd3', 'Admin Ng', 'M', '198765432', '1982-09-09', 'admin4.jpg', '2025-12-06 02:57:00', 'Active'),
('AD0005', 'Admin', 'admin5@example.com', 'b45b40c6cddb8f6c74f12e45742a1eab42bfab08', 'Admin Low', 'F', '198765435', '1986-03-03', 'admin5.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0001', 'Member', 'john.doe@example.com', '511058841d5481a496d38bc9929f409ec4a2331e', 'John Doe', 'M', '123456780', '1990-05-12', 'john1.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0002', 'Member', 'mary.lee@example.com', 'a1a0cc2c2d4491e06a9f5f84cae83890cacd278d', 'Mary Lee', 'F', '123456780', '1992-08-23', 'mary2.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0003', 'Member', 'bob.tan@example.com', '22387fbd6d03b936864fe3da8ba5226b0dd9435c', 'Bob Tan', 'M', '123456780', '1988-01-05', 'bob3.jpg', '2025-12-06 02:57:00', 'Inactive'),
('ME0004', 'Member', 'alice.wong@example.com', 'c854b4a07e81f3ad744f95b7ce2d42aaefd2b45a', 'Alice Wong', 'F', '123456780', '1995-02-14', 'alice4.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0005', 'Member', 'david.chan@example.com', 'ffbfa29f878ea8325468c94a7cb449d042d69aa5', 'David Chan', 'M', '123456780', '1991-07-30', 'david5.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0006', 'Member', 'susan.koh@example.com', '73abc851e309c65dd4b21b44ab346b7b223cd4ad', 'Susan Koh', 'F', '123456780', '1993-09-12', 'susan6.jpg', '2025-12-06 02:57:00', 'Inactive'),
('ME0007', 'Member', 'kevin.lim@example.com', '34d3099486c59bca3ce4ed1c4e9a5b07c5579fc6', 'Kevin Lim', 'M', '123456780', '1989-12-01', 'kevin7.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0008', 'Member', 'kelly.ng@example.com', 'e17a55684169a48f10a44036fef70ee01fe48de1', 'Kelly Ng', 'F', '123456780', '1994-03-22', 'kelly8.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0009', 'Member', 'eric.tan@example.com', '337b624810436feee21279edd075097b89cf3cfb', 'Eric Tan', 'M', '123456789', '1990-06-18', 'eric9.jpg', '2025-12-06 02:57:00', 'Inactive'),
('ME0010', 'Member', 'amy.lim@example.com', '174bd7d853dad98c5be59e1b6d78b9c16d70a927', 'Amy Lim', 'F', '123456781', '1992-11-05', 'amy10.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0011', 'Member', 'ronald.lee@example.com', '7e38fee792b2e41257161fa771f29747cd8af0c3', 'Ronald Lee', 'M', '123456781', '1987-04-09', 'ronald11.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0012', 'Member', 'julia.tan@example.com', '3148e80228df5505ba0e0d281d44e64dd50e8631', 'Julia Wong', 'F', '111111222', '1993-08-12', 'julia12.jpg', '2025-12-06 02:57:55', 'Active'),
('ME0013', 'Member', 'brian.choo@example.com', 'bd6025efbb89e21faf51418e41240de1c72be78f', 'Brian Choo', 'M', '123456783', '1991-10-20', 'brian13.jpg', '2025-12-06 02:57:00', 'Inactive'),
('ME0014', 'Member', 'rachel.koh@example.com', '1a82dc34f298fb617aba847c53b28dfebea78321', 'Rachel Koh', 'F', '123456781', '1994-01-25', 'rachel14.jpg', '2025-12-06 02:57:00', 'Active'),
('ME0015', 'Member', 'steven.lim@example.com', '6f027d959098a3a347d8aa528f2419a7cd498682', '', 'M', '123456781', '1989-07-11', 'steven15.jpg', '2025-12-06 02:58:21', 'Active'),
('ME0016', 'Member', 'member16@gmail.com', '8cb2237d0679ca88db6464eac60da96345513964', 'Wong Kom', 'f', '192278833', '2015-12-16', 'aaw.png', '2025-12-06 02:57:19', 'Active'),
('ME0017', 'Member', '1000@gmail.com', 'a642a77abd7d4f51bf9226ceaf891fcbb5b299b8', '1000', 'F', '199999912', '1999-06-18', '6932eafc5366c.jpg', '2025-12-06 02:58:33', 'Inactive'),
('ME0018', 'Member', '44444@gmail.com', '04f081741466827161bede82a374af0ec9a39e31', '44444444', 'M', '192233333', '1996-10-17', '6933812f9e2d9.jpg', '2025-12-06 02:58:41', 'Inactive'),
('ME0019', 'Member', '9999@gmail.com', 'd528fca3b163c05703e88b5285440bec28ecf185', '9999', 'F', '111111111', '2001-07-17', '69338c8b37cfe.jpg', '2025-12-06 02:58:55', 'Active'),
('ME0020', 'Member', '5555@gmail.com', '19dd466e43cdbd3833abc0609eba6d8786f9b342', '55555555', 'F', '55555555', '1999-12-17', '69339e0d34e5d.jpg', '2025-12-05 16:00:00', 'Inactive');

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
  ADD KEY `order_id` (`order_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`payment_method_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`);

--
-- Constraints for table `order_history`
--
ALTER TABLE `order_history`
  ADD CONSTRAINT `order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `order_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`payment_method_id`);

--
-- Constraints for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD CONSTRAINT `payment_method_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `receipt`
--
ALTER TABLE `receipt`
  ADD CONSTRAINT `receipt_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `receipt_ibfk_2` FOREIGN KEY (`issued_to`) REFERENCES `users` (`user_id`);

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
