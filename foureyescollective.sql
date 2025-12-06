-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 12:24 PM
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
  `postcoed` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  `default_flag` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `user_id`, `address_line1`, `address_line2`, `city`, `state`, `postcoed`, `country`, `default_flag`, `created_at`) VALUES
('ADRS0001', 'ME0001', '123 Main Street', 'Unit 1A', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50000', 'Malaysia', 1, '2025-11-01 01:05:00'),
('ADRS0002', 'ME0002', '456 Market Road', '', 'Shah Alam', 'Selangor', '40000', 'Malaysia', 1, '2025-11-02 02:15:00'),
('ADRS0003', 'ME0003', '789 Hill Street', 'Apt 12B', 'Penang', 'Penang', '10050', 'Malaysia', 1, '2025-11-03 03:20:00'),
('ADRS0004', 'ME0004', '321 River Lane', '', 'Kota Kinabalu', 'Sabah', '88000', 'Malaysia', 1, '2025-11-04 04:25:00'),
('ADRS0005', 'ME0005', '654 Garden Avenue', 'Unit 5C', 'Ipoh', 'Perak', '30000', 'Malaysia', 1, '2025-11-05 05:30:00'),
('ADRS0006', 'ME0006', '987 Sunset Blvd', '', 'Petaling Jaya', 'Selangor', '46000', 'Malaysia', 1, '2025-11-06 06:35:00'),
('ADRS0007', 'ME0007', '246 Sunrise Street', 'Unit 7B', 'Melaka', 'Melaka', '75000', 'Malaysia', 1, '2025-11-07 07:40:00'),
('ADRS0008', 'ME0008', '135 Ocean Road', '', 'Ampang', 'Selangor', '68000', 'Malaysia', 1, '2025-11-08 08:45:00'),
('ADRS0009', 'ME0009', '864 Mountain Lane', '', 'Penang', 'Penang', '10080', 'Malaysia', 1, '2025-11-09 09:50:00'),
('ADRS0010', 'ME0010', '753 Forest Street', 'Unit 10C', 'Kuala Lumpur', 'W.P. Kuala Lumpur', '50450', 'Malaysia', 1, '2025-11-10 10:55:00'),
('ADRS0011', 'ME0011', '951 Riverbank Rd', '', 'Putrajaya', 'W.P. Putrajaya', '62000', 'Malaysia', 1, '2025-11-11 01:00:00'),
('ADRS0012', 'ME0012', '159 Hilltop Ave', 'Unit 12B', 'Penang', 'Penang', '10100', 'Malaysia', 1, '2025-11-12 02:05:00'),
('ADRS0013', 'ME0013', '357 Valley Street', 'Unit 13C', 'Kota Bharu', 'Kelantan', '15000', 'Malaysia', 1, '2025-11-13 03:10:00'),
('ADRS0014', 'ME0014', '753 Garden Lane', '', 'Pahang', 'Pahang', '25000', 'Malaysia', 1, '2025-11-14 04:15:00'),
('ADRS0015', 'ME0015', '951 Lakeview Blvd', 'Unit 15A', 'Selangor', 'Selangor', '43000', 'Malaysia', 1, '2025-11-15 05:20:00');

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
('CI0011', 'ME0010', 'PR0003', 1, 'in_cart', '2025-11-11 10:00:00', NULL, NULL, NULL),
('CI0012', 'ME0011', 'PR0001', 1, 'abandoned', '2025-11-11 00:50:00', '2025-11-11 01:10:00', NULL, NULL),
('CI0013', 'ME0012', 'PR0004', 1, 'in_cart', '2025-11-12 01:20:00', NULL, NULL, NULL),
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
('CI0026', 'ME0005', 'PR0001', 3, 'checkout', '2025-11-09 05:20:00', NULL, '2025-11-09 06:00:00', 'OI0020');

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
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` char(1) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `photo` varchar(255) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `email`, `password`, `name`, `gender`, `phone`, `date_of_birth`, `photo`, `registration_date`, `status`) VALUES
('AD0001', 'admin', 'admin1@example.com', '$2y$10$KqD2gV5rJ1xT4pP9lF3N.PMe6aNkRs1uG5oB0wLpJ3kH7qT2vC8y', 'Admin Yeap', 'F', '1987654321', '1985-05-05', 'admin1.jpg', '2025-11-01 01:00:00', 'active'),
('AD0002', 'admin', 'admin2@example.com', '$2y$10$PfT8qR3vL0xP5rJ2kB7M.PMe4aDjRs9uG6oB1wLpJ8kH2qT3vC0y', 'Admin Lim', 'M', '1987654322', '1980-12-12', 'admin2.jpg', '2025-11-02 01:10:00', 'active'),
('AD0003', 'admin', 'admin3@example.com', '$2y$10$XfV4bK1lP2tQ9rJ6dH6N.PMe3yGkRs5uG1oB2wLpJ0kH8qT4vC7y', 'Admin Quak', 'F', '1987654323', '1979-07-07', 'admin3.jpg', '2025-11-03 01:20:00', 'active'),
('AD0004', 'admin', 'admin4@example.com', '$2y$10$VjN5wB2kP1tL6rX3fC3N.PMe7aGjRs4uH9oB2wLpJ1kH5qT6vC3y', 'Admin Ng', 'M', '1987654324', '1982-09-09', 'admin4.jpg', '2025-11-04 01:30:00', 'active'),
('AD0005', 'admin', 'admin5@example.com', '$2y$10$BfQ9wL3jR5xV2pT7kP7M.PMe1aHjRs0uG6oB3wLpJ9kH2qT4vC5y', 'Admin Low', 'F', '1987654325', '1986-03-03', 'admin5.jpg', '2025-11-05 01:40:00', 'active'),
('ME0001', 'member', 'john.doe@example.com', '$2y$10$6tEJ2vE7tK8I9sP1xQ4H.OOe7mBfJc2rG9vQ1bPqA5lX2yR0K3u', 'John Doe', 'M', '1234567801', '1990-05-12', 'john1.jpg', '2025-11-01 01:00:00', 'active'),
('ME0002', 'member', 'mary.lee@example.com', '$2y$10$ZqH8rT1kL5aC7xD3vS2F.QOe6yXbUj9rP4tV2mNqW3kJ0pL1hC5e', 'Mary Lee', 'F', '1234567802', '1992-08-23', 'mary2.jpg', '2025-11-02 02:10:00', 'active'),
('ME0003', 'member', 'bob.tan@example.com', '$2y$10$VxR4pL2jF8qH5dT1kC6N.PMe9aYbRj3uS7wK1fGvL0oX2tQ3dM8y', 'Bob Tan', 'M', '1234567803', '1988-01-05', 'bob3.jpg', '2025-11-03 03:15:00', 'inactive'),
('ME0004', 'member', 'alice.wong@example.com', '$2y$10$QpN7vH3kJ1sR4mT9xD2B.ORe8yFqUk5tC6aL1jGvP0wS3oM4eX9z', 'Alice Wong', 'F', '1234567804', '1995-02-14', 'alice4.jpg', '2025-11-04 04:20:00', 'active'),
('ME0005', 'member', 'david.chan@example.com', '$2y$10$FhL9rQ2pV5dK8mX1cT4Y.PMe7aNjRs3uG6oB0wLpJ9kH2qT1vC5y', 'David Chan', 'M', '1234567805', '1991-07-30', 'david5.jpg', '2025-11-05 05:25:00', 'active'),
('ME0006', 'member', 'susan.koh@example.com', '$2y$10$ZkP5vH3rJ1sQ7mT4xD2B.ORe9yFkUm5tC6aL1jGvP0wS3oM4eX9z', 'Susan Koh', 'F', '1234567806', '1993-09-12', 'susan6.jpg', '2025-11-06 06:30:00', 'inactive'),
('ME0007', 'member', 'kevin.lim@example.com', '$2y$10$KpQ2rT1vL5dH8mX3cT4Y.PMe6aNjRs2uG7oB0wLpJ9kH1qT3vC5y', 'Kevin Lim', 'M', '1234567807', '1989-12-01', 'kevin7.jpg', '2025-11-07 07:35:00', 'active'),
('ME0008', 'member', 'kelly.ng@example.com', '$2y$10$DqL8xF1tV5rJ2kP3cY3Q.PMe4aBjRs9uG1oB2wLpJ5kH7qT0vC8y', 'Kelly Ng', 'F', '1234567808', '1994-03-22', 'kelly8.jpg', '2025-11-08 08:40:00', 'active'),
('ME0009', 'member', 'eric.tan@example.com', '$2y$10$WjV4bK7xP2tQ9rL6fH6N.PMe3yGkUm5tC1aL0jGvP8wS2oM3eX7z', 'Eric Tan', 'M', '1234567809', '1990-06-18', 'eric9.jpg', '2025-11-09 09:45:00', 'inactive'),
('ME0010', 'member', 'amy.lim@example.com', '$2y$10$QkH9fD3rL7pV2bT5yW1M.PMe2aNkRs0uG6oB3wLpJ8kH4qT1vC9y', 'Amy Lim', 'F', '1234567810', '1992-11-05', 'amy10.jpg', '2025-11-10 10:50:00', 'active'),
('ME0011', 'member', 'ronald.lee@example.com', '$2y$10$VdK2pG7tQ4jR1mW6xF5N.PMe1aLjRs0uG9oB2wLpJ3kH5qT6vC8y', 'Ronald Lee', 'M', '1234567811', '1987-04-09', 'ronald11.jpg', '2025-11-11 01:55:00', 'active'),
('ME0012', 'member', 'julia.tan@example.com', '$2y$10$BfQ5wL1jR9xV3pT2kK7M.PMe4aHjRs6uG1oB0wLpJ9kH2qT3vC7y', 'Julia Tan', 'F', '1234567812', '1993-08-12', 'julia12.jpg', '2025-11-12 02:00:00', 'active'),
('ME0013', 'member', 'brian.choo@example.com', '$2y$10$NwT3qK7vL1xR5jD9kF2C.PMe0aBjRs8uG3oB6wLpJ1kH4qT7vC9y', 'Brian Choo', 'M', '1234567813', '1991-10-20', 'brian13.jpg', '2025-11-13 03:05:00', 'inactive'),
('ME0014', 'member', 'rachel.koh@example.com', '$2y$10$GkR8jX2tF5lV0pK3nP7Y.PMe9aBsQe6uH2oB5wLpJ3kH1qT4vC8y', 'Rachel Koh', 'F', '1234567814', '1994-01-25', 'rachel14.jpg', '2025-11-14 04:10:00', 'active'),
('ME0015', 'member', 'steven.lim@example.com', '$2y$10$SxH1cV7lD4jR9kQ2fN5C.PMe8aPjRs3uG6oB1wLpJ7kH2qT0vC4y', 'Steven Lim', 'M', '1234567815', '1989-07-11', 'steven15.jpg', '2025-11-15 05:15:00', 'active');

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
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `wishlist_ibfk_2` (`product_id`);

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
