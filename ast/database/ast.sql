-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2025 at 08:01 AM
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
-- Database: `ast`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `user_type` enum('marketing_manager','admin','user') NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `password`) VALUES
(1, 'admin', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `pid` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `image` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `pid`, `name`, `price`, `quantity`, `image`, `subtotal`) VALUES
(26, 6, 2, 'tysm', 912, 1, 'home-img-1.png', 0.00),
(46, 8, 4, 'Casing', 100, 2, 'casing.png', 200.00),
(47, 8, 6, 'Ryzen 5 3600', 200, 5, 'cpu.jpg', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `title` varchar(15) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `name`, `title`, `description`) VALUES
(1, 'Monitors', NULL, 'Monitors'),
(2, 'Processor', NULL, 'aaa'),
(3, 'POS', NULL, 'it'),
(4, 'CCTV', NULL, 'Camera'),
(5, 'santha', NULL, 'mmmmmm');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(10) NOT NULL,
  `session_id` int(10) DEFAULT NULL,
  `sender_type` enum('customer','csr') NOT NULL,
  `sender_id` int(10) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `session_id` int(10) NOT NULL,
  `customer_id` int(10) DEFAULT NULL,
  `csr_id` int(10) DEFAULT NULL,
  `status` enum('waiting','active','ended') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_reports`
--

CREATE TABLE `compliance_reports` (
  `report_id` int(10) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `report_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `risk_level` enum('low','medium','high') NOT NULL,
  `status` enum('draft','reviewed','finalized') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csrs`
--

CREATE TABLE `csrs` (
  `csr_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(10) NOT NULL,
  `first_name` varchar(18) NOT NULL,
  `last_name` varchar(20) NOT NULL,
  `email` varchar(40) NOT NULL,
  `phone` int(10) DEFAULT NULL,
  `password` varchar(20) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `cart_id` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `address`, `cart_id`, `created_at`) VALUES
(1, 'Madhuka', 'Aththanayaka', 'madhukaaththanayaka@gmail.com', 758973807, '$2y$10$dA394r75jn.My', '43\r\nBibila Road, Hulandawa', NULL, '2025-02-24 04:32:30');

-- --------------------------------------------------------

--
-- Table structure for table `customer_inquiries`
--

CREATE TABLE `customer_inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_sales_representatives`
--

CREATE TABLE `customer_sales_representatives` (
  `csr_id` int(10) NOT NULL,
  `name` varchar(30) NOT NULL,
  `expertise` varchar(50) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `admin_id` int(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_sales_representatives`
--

INSERT INTO `customer_sales_representatives` (`csr_id`, `name`, `expertise`, `is_available`, `admin_id`, `password`) VALUES
(1, 'Madhuka', 'Danna ', 1, NULL, '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `customer_support_tickets`
--

CREATE TABLE `customer_support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `csr_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_support_tickets`
--

INSERT INTO `customer_support_tickets` (`ticket_id`, `csr_id`, `subject`, `description`, `priority`, `status`, `created_at`, `updated_at`, `user_id`) VALUES
(6, NULL, 'sdas', 'sdasd', 'medium', '', '2025-02-28 04:24:11', '2025-02-28 04:24:11', 8);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_agents`
--

CREATE TABLE `delivery_agents` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_agents`
--

INSERT INTO `delivery_agents` (`id`, `name`, `phone`, `password`) VALUES
(4, 'Madhuka', '0711234567', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_assignments`
--

CREATE TABLE `delivery_assignments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `delivery_agent_id` int(11) NOT NULL,
  `status` enum('pending','picked_up','delivered','cancelled') DEFAULT 'pending',
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_assignments`
--

INSERT INTO `delivery_assignments` (`id`, `order_id`, `delivery_agent_id`, `status`, `picked_up_at`, `delivered_at`) VALUES
(4, 7, 4, 'delivered', '2025-03-04 14:03:13', '2025-03-04 14:03:14'),
(5, 8, 4, 'delivered', '2025-03-04 14:03:15', '2025-03-04 14:03:16'),
(6, 11, 4, 'delivered', '2025-03-04 14:03:17', '2025-03-04 14:03:18'),
(7, 10, 4, 'delivered', '2025-03-04 14:26:33', '2025-03-08 17:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `financial_auditors`
--

CREATE TABLE `financial_auditors` (
  `auditor_id` int(10) NOT NULL,
  `certification` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `admin_id` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_auditors`
--

INSERT INTO `financial_auditors` (`auditor_id`, `certification`, `name`, `password`, `phone`, `admin_id`) VALUES
(1, 'MBBS', 'Madhuka', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2', '0711234567', 1);

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_logs`
--

CREATE TABLE `financial_audit_logs` (
  `log_id` int(10) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `log_type` enum('transaction','compliance','risk_assessment','report_generation') NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `severity` enum('low','medium','high') DEFAULT 'low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

CREATE TABLE `financial_reports` (
  `report_id` int(10) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `report_type` enum('monthly','quarterly','annual','special') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_revenue` decimal(15,2) NOT NULL,
  `total_expenses` decimal(15,2) NOT NULL,
  `net_profit` decimal(15,2) NOT NULL,
  `summary` text NOT NULL,
  `status` enum('draft','reviewed','finalized') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiry_responses`
--

CREATE TABLE `inquiry_responses` (
  `response_id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `csr_id` int(11) NOT NULL,
  `response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `quantity` int(100) NOT NULL DEFAULT 0,
  `storage_location` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `product_id`, `supplier_id`, `quantity`, `storage_location`, `updated_at`, `last_updated`) VALUES
(2, 7, 1, 0, NULL, '2025-03-04 05:31:16', '2025-03-04 05:31:16'),
(3, 1, 1, 0, NULL, '2025-03-04 05:31:20', '2025-03-04 05:31:20'),
(4, 6, 1, 20, NULL, '2025-02-25 13:34:35', '2025-02-25 13:41:19'),
(5, 2, 1, 12, NULL, '2025-03-04 14:09:40', '2025-03-04 14:09:40'),
(6, 4, 1, 9, NULL, '2025-03-04 05:31:28', '2025-03-04 05:31:28'),
(7, 2, 1, 12, NULL, '2025-03-04 14:09:40', '2025-03-04 14:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `log_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `previous_quantity` int(10) NOT NULL,
  `new_quantity` int(10) NOT NULL,
  `change_type` enum('add','remove') NOT NULL,
  `reason` text NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`log_id`, `supplier_id`, `product_id`, `previous_quantity`, `new_quantity`, `change_type`, `reason`, `notes`, `logged_at`) VALUES
(1, 1, 7, 0, 75, 'add', 'Restock request approved', '', '2025-02-25 13:09:22'),
(2, 1, 7, 75, 150, 'add', 'Restock request approved', '', '2025-02-25 13:17:00'),
(3, 1, 1, 0, 10, 'add', 'Restock request approved', 'ok', '2025-02-25 13:17:06'),
(4, 1, 1, 10, 20, 'add', 'Restock request approved', 'ok', '2025-02-25 13:21:47'),
(5, 1, 6, 0, 5, 'add', 'Restock request approved', '', '2025-02-25 13:21:52'),
(6, 1, 6, 5, 10, 'add', 'Restock request approved', '', '2025-02-25 13:22:17'),
(7, 1, 6, 10, 15, 'add', 'Restock request approved', '', '2025-02-25 13:22:21'),
(8, 1, 6, 15, 20, 'add', 'Restock request approved', '', '2025-02-25 13:22:47'),
(9, 1, 2, 0, 8, 'add', 'Restock request approved', '', '2025-02-25 13:22:50'),
(10, 1, 2, 8, 16, 'add', 'Restock request approved', '', '2025-02-25 13:24:28'),
(11, 1, 2, 16, 24, 'add', 'Restock request approved', '', '2025-02-25 13:28:40'),
(12, 1, 2, 24, 32, 'add', 'Restock request approved', '', '2025-02-25 13:29:34'),
(13, 1, 4, 0, 10, 'add', 'Restock request approved', '', '2025-02-25 13:29:37'),
(14, 1, 6, 10, 20, 'add', 'Restock request approved', '', '2025-02-25 13:34:35'),
(15, 1, 4, 10, 20, 'add', 'Restock request approved', '', '2025-02-25 13:34:38'),
(16, 1, 1, 2, 20, 'add', 'Restock request approved', '', '2025-02-25 13:34:40'),
(17, 1, 7, 0, 20, 'add', 'Restock request approved', '', '2025-02-25 13:34:42'),
(18, 1, 2, 2, 20, 'add', 'yyyy', NULL, '2025-03-04 05:38:40'),
(19, 1, 2, 2, 12, 'add', 'Restock request approved', '', '2025-03-04 14:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `created_by` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points_history`
--

CREATE TABLE `loyalty_points_history` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `previous_points` decimal(10,2) NOT NULL,
  `points_change` decimal(10,2) NOT NULL,
  `new_points` decimal(10,2) NOT NULL,
  `previous_tier` enum('bronze','silver','gold','platinum') NOT NULL,
  `new_tier` enum('bronze','silver','gold','platinum') NOT NULL,
  `reason` text NOT NULL,
  `adjusted_by` int(10) NOT NULL,
  `adjusted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_program`
--

CREATE TABLE `loyalty_program` (
  `program_id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `tier` enum('bronze','silver','gold','platinum') NOT NULL,
  `points_multiplier` decimal(3,2) NOT NULL,
  `manager_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_tiers`
--

CREATE TABLE `loyalty_tiers` (
  `tier` enum('bronze','silver','gold','platinum') NOT NULL,
  `points_threshold` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_tiers`
--

INSERT INTO `loyalty_tiers` (`tier`, `points_threshold`) VALUES
('bronze', 0.00),
('silver', 500.00),
('gold', 1500.00),
('platinum', 3000.00);

-- --------------------------------------------------------

--
-- Table structure for table `manager_settings`
--

CREATE TABLE `manager_settings` (
  `settings_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `low_stock_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `new_order_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `review_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager_settings`
--

INSERT INTO `manager_settings` (`settings_id`, `manager_id`, `low_stock_alerts`, `new_order_alerts`, `review_alerts`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2025-03-04 05:36:47', '2025-03-04 05:36:47');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_campaigns`
--

CREATE TABLE `marketing_campaigns` (
  `campaign_id` int(10) NOT NULL,
  `name` varchar(40) NOT NULL,
  `description` varchar(255) NOT NULL,
  `target_audience` varchar(20) NOT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `manager_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_campaigns`
--

INSERT INTO `marketing_campaigns` (`campaign_id`, `name`, `description`, `target_audience`, `status`, `start_date`, `end_date`, `budget`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Gota go home', 'Aragalaya v1.0', 'New Customers', 'pending', '2025-03-20', '2025-03-23', 250.00, 1, '2025-03-01 07:40:48', '2025-03-01 07:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_manager`
--

CREATE TABLE `marketing_manager` (
  `manager_id` int(10) NOT NULL,
  `expertise` varchar(50) NOT NULL,
  `name` varchar(20) NOT NULL,
  `admin_id` int(10) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_manager`
--

INSERT INTO `marketing_manager` (`manager_id`, `expertise`, `name`, `admin_id`, `password`) VALUES
(1, 'Danna ', 'Madhuka', 1, '94b55628f61cbc9ac0966d70f858702e6bf4b5da');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `number` varchar(12) NOT NULL,
  `message` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `number`, `message`) VALUES
(1, 2, 'sdasfs', 'fasfas@gmail.com', '12312', 'fasfas'),
(2, 0, 'tysm', '232@gmail.com', '2312321', 'Hello how are you'),
(3, 3, 'Thevindu', 'itsthw9@gmail.com', '0705228470', 'Hello there');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `number` varchar(10) NOT NULL,
  `email` varchar(50) NOT NULL,
  `method` varchar(50) NOT NULL,
  `address` varchar(500) NOT NULL,
  `total_products` varchar(1000) NOT NULL,
  `total_price` int(100) NOT NULL,
  `placed_on` date NOT NULL DEFAULT current_timestamp(),
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','picked_up','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `number`, `email`, `method`, `address`, `total_products`, `total_price`, `placed_on`, `payment_status`, `order_status`, `created_at`, `updated_at`) VALUES
(2, 2, 'casdasd', '12312', 'safasfas@gmail.com', 'paytm', 'flat no. sfasfas, fasfas, fasfas, fasfas, fsafas - 21312', 'tysm (900 x 1) - ', 900, '2025-02-02', 'completed', 'delivered', '2025-02-22 08:28:23', '2025-03-04 14:02:37'),
(7, 6, 'dsd', '123123', 'asdas@gmail.com', 'cash on delivery', 'flat no. 2312, dsa, adas, sdadas, sdsad - 123123', 'tysm (900 x 1)', 900, '2025-02-07', 'completed', 'delivered', '2025-02-22 08:28:23', '2025-03-04 14:03:14'),
(8, 6, 'casdas', '213123', 'das@gmail.com', 'cash on delivery', 'flat no. 321, 323, 232, 323, 123 - 23213', 'tysm (900 x 1), mail (123 x 1)', 1023, '2025-02-07', 'completed', 'delivered', '2025-02-22 08:28:23', '2025-03-04 14:03:16'),
(9, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'Headset (420 x 1), tysm (912 x 1), Casing (100 x 1)', 1432, '2025-02-24', 'pending', '', '2025-02-24 04:51:40', '2025-03-04 03:53:29'),
(10, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'tysm (912 x 1), Headset (420 x 1), Ryzen 5 3600 (200 x 4)', 2132, '2025-03-04', 'completed', 'delivered', '2025-03-04 13:54:18', '2025-03-08 17:45:19'),
(11, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'tysm (912 x 1), Headset (420 x 90)', 38712, '2025-03-04', 'completed', 'delivered', '2025-03-04 13:54:46', '2025-03-04 14:03:18'),
(12, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'Casing (100 x 1), Ryzen 5 3600 (200 x 1), Headset (420 x 25)', 10800, '2025-03-04', 'pending', '', '2025-03-04 13:58:04', '2025-03-04 13:58:11'),
(13, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'tysm (912 x 1), Casing (100 x 1), Ryzen 5 3600 (200 x 1)', 1212, '2025-03-04', 'completed', '', '2025-03-04 13:59:50', '2025-03-08 05:32:48'),
(14, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'tysm (912 x 1), Casing (100 x 1), Headset (420 x 1)', 1432, '2025-03-04', 'pending', 'pending', '2025-03-04 14:35:10', '2025-03-04 14:35:10'),
(15, 8, 'Malwana Madhuka Mals', '0758973807', 'madhukaaththanayaka@gmail.com', 'cash on delivery', 'flat no. 43, Bibila Road, Hulandawa, Monaragala, Uva, Sri Lanka - 91000', 'Headset (420 x 1), tysm (912 x 1)', 1332, '2025-03-04', 'pending', '', '2025-03-04 16:23:15', '2025-03-09 04:42:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_logs`
--

CREATE TABLE `payment_gateway_logs` (
  `log_id` int(10) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `transaction_type` enum('payment','refund','chargeback') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('success','failed','pending','disputed') NOT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(100) NOT NULL,
  `category_id` int(10) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `details` varchar(500) NOT NULL,
  `price` int(10) NOT NULL,
  `image_01` varchar(100) NOT NULL,
  `image_02` varchar(100) NOT NULL,
  `image_03` varchar(100) NOT NULL,
  `status` enum('active','discontinued') DEFAULT 'active',
  `discontinued_reason` text DEFAULT NULL,
  `discontinued_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `details`, `price`, `image_01`, `image_02`, `image_03`, `status`, `discontinued_reason`, `discontinued_at`) VALUES
(1, NULL, 'Headset', 'Hello', 420, 'home-img-3.png', 'Retail markdown-amico.png', 'home-img-3.png', 'active', NULL, NULL),
(2, NULL, 'tysm', 'This is phone please buy it', 912, 'home-img-1.png', 'home-img-1.png', 'home-img-1.png', 'active', NULL, NULL),
(4, NULL, 'Casing', 'Casing please buy it very cheap', 100, 'casing.png', 'casing.png', 'casing.png', 'active', NULL, NULL),
(6, 2, 'Ryzen 5 3600', 'Supiriyk', 200, 'cpu.jpg', 'cpu.jpg', 'cpu.jpg', 'active', NULL, NULL),
(7, NULL, 'i7 11th gen', 's4ndthdrbhrtdh ', 250, 'cpu.jpg', 'cpu.jpg', 'cpu.jpg', 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_analytics`
--

CREATE TABLE `product_analytics` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `views` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `purchases` int(11) DEFAULT 0,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_manager`
--

CREATE TABLE `product_manager` (
  `manager_id` int(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `expertise` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin_id` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_manager`
--

INSERT INTO `product_manager` (`manager_id`, `name`, `expertise`, `password`, `admin_id`, `created_at`, `updated_at`) VALUES
(1, 'Madhuka', 'Danna ', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2', NULL, '2025-02-23 03:54:33', '2025-02-23 03:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `product_promotions`
--

CREATE TABLE `product_promotions` (
  `product_id` int(10) NOT NULL,
  `promotion_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'inactive',
  `campaign_id` int(10) DEFAULT NULL,
  `manager_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `request_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `requested_quantity` int(10) NOT NULL,
  `request_notes` text DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restock_requests`
--

INSERT INTO `restock_requests` (`request_id`, `supplier_id`, `product_id`, `requested_quantity`, `request_notes`, `response_notes`, `status`, `created_at`, `updated_at`) VALUES
(14, 1, 7, 20, '', NULL, 'pending', '2025-03-04 05:32:22', '2025-03-04 05:32:22'),
(15, 1, 1, 20, 'Quick restock from low stock management', NULL, 'pending', '2025-03-04 05:32:32', '2025-03-04 05:32:32'),
(16, 1, 2, 10, 'kkk', '', 'approved', '2025-03-04 13:22:50', '2025-03-04 14:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `return_item_id` int(10) NOT NULL,
  `return_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_request`
--

CREATE TABLE `return_request` (
  `return_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `requested_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_date` datetime DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `manager_response` text DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `review_text`, `created_at`, `updated_at`, `manager_response`, `response_date`) VALUES
(1, 2, 6, 3, 'Very bad', '2025-02-08 04:35:59', '2025-02-08 05:19:49', NULL, NULL),
(2, 2, 6, 5, 'Good one', '2025-02-08 04:36:08', '2025-02-08 05:19:49', NULL, NULL),
(3, 4, 6, 3, 'Quite expensivesd', '2025-02-08 04:38:07', '2025-02-08 05:24:55', NULL, NULL),
(4, 4, 6, 3, 'Quite expensivesd', '2025-02-08 04:40:09', '2025-02-08 05:24:55', NULL, NULL),
(5, 4, 6, 3, 'Quite expensivesd', '2025-02-08 04:40:19', '2025-02-08 05:24:55', NULL, NULL),
(6, 4, 6, 3, 'Quite expensivesd', '2025-02-08 04:40:54', '2025-02-08 05:24:55', NULL, NULL),
(15, 1, 6, 5, 'Why', '2025-02-08 05:25:13', '2025-02-08 05:27:41', NULL, NULL),
(20, 2, 8, 2, 'ok but not that much good i expected', '2025-03-08 08:05:41', '2025-03-08 08:05:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `risk_assessments`
--

CREATE TABLE `risk_assessments` (
  `assessment_id` int(10) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `assessment_date` date NOT NULL,
  `supplier_id` int(10) DEFAULT NULL,
  `risk_category` enum('financial','operational','compliance','reputational') NOT NULL,
  `risk_score` decimal(5,2) NOT NULL,
  `mitigation_strategy` text NOT NULL,
  `status` enum('identified','in_progress','mitigated') DEFAULT 'identified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_change_requests`
--

CREATE TABLE `shift_change_requests` (
  `request_id` int(10) NOT NULL,
  `staff_id` int(10) NOT NULL,
  `current_shift` enum('morning','afternoon','night') NOT NULL,
  `requested_shift` enum('morning','afternoon','night') NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

CREATE TABLE `staff_tasks` (
  `task_id` int(10) NOT NULL,
  `staff_id` int(10) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_alert`
--

CREATE TABLE `stock_alert` (
  `alert_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `threshold` int(10) NOT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(10) NOT NULL,
  `name` varchar(18) NOT NULL,
  `phone` int(10) NOT NULL,
  `email` varchar(40) NOT NULL,
  `address` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `phone`, `email`, `address`, `password`) VALUES
(1, 'Madhuka', 711234567, 'madhuka@gmail.com', 'No.43,Bibila Road', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_categories`
--

CREATE TABLE `supplier_categories` (
  `category_id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_category_assignments`
--

CREATE TABLE `supplier_category_assignments` (
  `assignment_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `category_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_communications`
--

CREATE TABLE `supplier_communications` (
  `communication_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `admin_id` int(10) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_communication_threads`
--

CREATE TABLE `supplier_communication_threads` (
  `thread_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `admin_id` int(10) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_compliance`
--

CREATE TABLE `supplier_compliance` (
  `compliance_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `certification_type` varchar(100) NOT NULL,
  `certification_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','expired','pending') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_contacts`
--

CREATE TABLE `supplier_contacts` (
  `contact_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_contracts`
--

CREATE TABLE `supplier_contracts` (
  `contract_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `terms` text NOT NULL,
  `status` enum('active','pending','expired','terminated') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_documents`
--

CREATE TABLE `supplier_documents` (
  `document_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `document_type` enum('contract','license','certificate','invoice','other') NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_orders`
--

CREATE TABLE `supplier_orders` (
  `supplier_order_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_delivery` datetime NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_order_items`
--

CREATE TABLE `supplier_order_items` (
  `order_item_id` int(10) NOT NULL,
  `supplier_order_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `payment_id` int(10) NOT NULL,
  `supplier_order_id` int(10) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) NOT NULL,
  `transaction_reference` varchar(100) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_performance`
--

CREATE TABLE `supplier_performance` (
  `performance_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `total_orders` int(10) DEFAULT 0,
  `completed_orders` int(10) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `last_evaluated` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_price_history`
--

CREATE TABLE `supplier_price_history` (
  `history_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `reason_for_change` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_quality_metrics`
--

CREATE TABLE `supplier_quality_metrics` (
  `metric_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `delivery_time` int(10) NOT NULL,
  `defect_rate` decimal(5,2) DEFAULT 0.00,
  `response_time` int(10) NOT NULL,
  `quality_score` decimal(5,2) DEFAULT 0.00,
  `evaluation_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ratings`
--

CREATE TABLE `supplier_ratings` (
  `rating_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comments` text DEFAULT NULL,
  `review_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_orders`
--

CREATE TABLE `supply_orders` (
  `supply_order_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_delivery` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_orders`
--

INSERT INTO `supply_orders` (`supply_order_id`, `supplier_id`, `order_date`, `expected_delivery`, `total_amount`, `status`, `notes`) VALUES
(1, 1, '2025-02-24 01:38:05', '2025-02-25', 200.00, 'completed', 'aaaaaaaaaaaaaaaaaaaaaaaa');

-- --------------------------------------------------------

--
-- Table structure for table `supply_order_items`
--

CREATE TABLE `supply_order_items` (
  `supply_order_item_id` int(10) NOT NULL,
  `supply_order_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(100) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_order_items`
--

INSERT INTO `supply_order_items` (`supply_order_item_id`, `supply_order_id`, `product_id`, `quantity`, `unit_cost`, `subtotal`) VALUES
(1, 1, 1, 20, 10.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(10) NOT NULL,
  `customer_id` int(10) DEFAULT NULL,
  `csr_id` int(10) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`ticket_id`, `customer_id`, `csr_id`, `subject`, `description`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Ammo seen eka', 'aaaaa', 'in_progress', 'high', '2025-02-24 04:33:09', '2025-02-25 03:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_history`
--

CREATE TABLE `ticket_history` (
  `history_id` int(10) NOT NULL,
  `ticket_id` int(10) DEFAULT NULL,
  `csr_id` int(10) DEFAULT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_history`
--

INSERT INTO `ticket_history` (`history_id`, `ticket_id`, `csr_id`, `old_status`, `new_status`, `created_at`) VALUES
(1, 1, 1, 'pending', 'in_progress', '2025-02-25 03:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `response_id` int(10) NOT NULL,
  `ticket_id` int(10) DEFAULT NULL,
  `csr_id` int(10) DEFAULT NULL,
  `response` text NOT NULL,
  `type` enum('reply','note') DEFAULT 'reply',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_responses`
--

INSERT INTO `ticket_responses` (`response_id`, `ticket_id`, `csr_id`, `response`, `type`, `created_at`, `user_id`) VALUES
(1, 1, 1, 'Ticket created with high priority', 'note', '2025-02-24 04:33:09', NULL),
(4, 6, 1, 'sfas', 'reply', '2025-02-28 04:40:22', NULL),
(5, 6, 1, 'Hi Bye', 'reply', '2025-02-28 04:40:32', NULL),
(11, 7, 1, 'This ticket has been resolved. Please let us know if you need any further assistance.', '', '2025-02-28 06:15:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_audit_trails`
--

CREATE TABLE `transaction_audit_trails` (
  `trail_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `auditor_id` int(10) NOT NULL,
  `transaction_stage` enum('initiated','processed','verified','completed','flagged') NOT NULL,
  `notes` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
(2, 'ThevinduW', 'itsthw9@gmail.com', '784e9240155834852dff458a730cceb50229df32'),
(4, 'asdas', 'thevinduh21@gmail.com', '40bd001563085fc35165329ea1ff5c5ecbdbbeef'),
(5, 'mc', 'demo@gmail.com', '7c222fb2927d828af22f592134e8932480637c0d'),
(6, 'ThevinduH', 'th@gmail.com', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2'),
(8, 'Madhuka', 'madhuka@gmail.com', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2'),
(9, 'Customer1', 'customer1@gmail.com', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2'),
(10, 'User2', 'user2@gmail.com', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `user_loyalty_points`
--

CREATE TABLE `user_loyalty_points` (
  `user_id` int(10) NOT NULL,
  `points` decimal(10,2) DEFAULT 0.00,
  `tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_staff`
--

CREATE TABLE `warehouse_staff` (
  `staff_id` int(10) NOT NULL,
  `name` varchar(30) NOT NULL,
  `phone` int(10) NOT NULL,
  `shift` varchar(20) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_staff`
--

INSERT INTO `warehouse_staff` (`staff_id`, `name`, `phone`, `shift`, `is_available`, `password`) VALUES
(1, 'Madhuka', 711234567, 'morning', 1, '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `pid` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `pid`, `name`, `price`, `image`) VALUES
(18, 6, 4, 'Casing', 100, 'casing.png'),
(22, 8, 1, 'Headset', 420, 'home-img-3.png'),
(23, 8, 2, 'tysm', 912, 'home-img-1.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `csr_id` (`csr_id`);

--
-- Indexes for table `compliance_reports`
--
ALTER TABLE `compliance_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `csrs`
--
ALTER TABLE `csrs`
  ADD PRIMARY KEY (`csr_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_customer_inquiries_user` (`user_id`);

--
-- Indexes for table `customer_sales_representatives`
--
ALTER TABLE `customer_sales_representatives`
  ADD PRIMARY KEY (`csr_id`);

--
-- Indexes for table `customer_support_tickets`
--
ALTER TABLE `customer_support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `fk_csr` (`csr_id`),
  ADD KEY `fk_customer_support_tickets_user` (`user_id`);

--
-- Indexes for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_assignment` (`order_id`),
  ADD KEY `fk_delivery_agent` (`delivery_agent_id`);

--
-- Indexes for table `financial_auditors`
--
ALTER TABLE `financial_auditors`
  ADD PRIMARY KEY (`auditor_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `financial_audit_logs`
--
ALTER TABLE `financial_audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `inquiry_responses`
--
ALTER TABLE `inquiry_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `inquiry_id` (`inquiry_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `loyalty_points_history`
--
ALTER TABLE `loyalty_points_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `adjusted_by` (`adjusted_by`);

--
-- Indexes for table `loyalty_program`
--
ALTER TABLE `loyalty_program`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  ADD PRIMARY KEY (`tier`);

--
-- Indexes for table `manager_settings`
--
ALTER TABLE `manager_settings`
  ADD PRIMARY KEY (`settings_id`),
  ADD UNIQUE KEY `manager_id` (`manager_id`);

--
-- Indexes for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD PRIMARY KEY (`campaign_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `marketing_manager`
--
ALTER TABLE `marketing_manager`
  ADD PRIMARY KEY (`manager_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment_gateway_logs`
--
ALTER TABLE `payment_gateway_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_analytics`
--
ALTER TABLE `product_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_manager`
--
ALTER TABLE `product_manager`
  ADD PRIMARY KEY (`manager_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `product_promotions`
--
ALTER TABLE `product_promotions`
  ADD PRIMARY KEY (`product_id`,`promotion_id`),
  ADD KEY `promotion_id` (`promotion_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`return_item_id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `return_request`
--
ALTER TABLE `return_request`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `auditor_id` (`auditor_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `stock_alert`
--
ALTER TABLE `stock_alert`
  ADD PRIMARY KEY (`alert_id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `supplier_categories`
--
ALTER TABLE `supplier_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `supplier_category_assignments`
--
ALTER TABLE `supplier_category_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `supplier_communications`
--
ALTER TABLE `supplier_communications`
  ADD PRIMARY KEY (`communication_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `supplier_communication_threads`
--
ALTER TABLE `supplier_communication_threads`
  ADD PRIMARY KEY (`thread_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `supplier_compliance`
--
ALTER TABLE `supplier_compliance`
  ADD PRIMARY KEY (`compliance_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_contacts`
--
ALTER TABLE `supplier_contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_contracts`
--
ALTER TABLE `supplier_contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD PRIMARY KEY (`supplier_order_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_order_items`
--
ALTER TABLE `supplier_order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `supplier_order_id` (`supplier_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `supplier_order_id` (`supplier_order_id`);

--
-- Indexes for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  ADD PRIMARY KEY (`performance_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_price_history`
--
ALTER TABLE `supplier_price_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `supplier_quality_metrics`
--
ALTER TABLE `supplier_quality_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_ratings`
--
ALTER TABLE `supplier_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD PRIMARY KEY (`supply_order_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supply_order_items`
--
ALTER TABLE `supply_order_items`
  ADD PRIMARY KEY (`supply_order_item_id`),
  ADD KEY `supply_order_id` (`supply_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `csr_id` (`csr_id`);

--
-- Indexes for table `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `csr_id` (`csr_id`);

--
-- Indexes for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `csr_id` (`csr_id`),
  ADD KEY `fk_ticket_responses_user` (`user_id`);

--
-- Indexes for table `transaction_audit_trails`
--
ALTER TABLE `transaction_audit_trails`
  ADD PRIMARY KEY (`trail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_loyalty_points`
--
ALTER TABLE `user_loyalty_points`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `warehouse_staff`
--
ALTER TABLE `warehouse_staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `session_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_reports`
--
ALTER TABLE `compliance_reports`
  MODIFY `report_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csrs`
--
ALTER TABLE `csrs`
  MODIFY `csr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_sales_representatives`
--
ALTER TABLE `customer_sales_representatives`
  MODIFY `csr_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_support_tickets`
--
ALTER TABLE `customer_support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `financial_auditors`
--
ALTER TABLE `financial_auditors`
  MODIFY `auditor_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `financial_audit_logs`
--
ALTER TABLE `financial_audit_logs`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `report_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiry_responses`
--
ALTER TABLE `inquiry_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_points_history`
--
ALTER TABLE `loyalty_points_history`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_program`
--
ALTER TABLE `loyalty_program`
  MODIFY `program_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manager_settings`
--
ALTER TABLE `manager_settings`
  MODIFY `settings_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  MODIFY `campaign_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketing_manager`
--
ALTER TABLE `marketing_manager`
  MODIFY `manager_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateway_logs`
--
ALTER TABLE `payment_gateway_logs`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_analytics`
--
ALTER TABLE `product_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_manager`
--
ALTER TABLE `product_manager`
  MODIFY `manager_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promotion_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `request_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `return_item_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_request`
--
ALTER TABLE `return_request`
  MODIFY `return_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  MODIFY `assessment_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  MODIFY `request_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  MODIFY `task_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_alert`
--
ALTER TABLE `stock_alert`
  MODIFY `alert_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_categories`
--
ALTER TABLE `supplier_categories`
  MODIFY `category_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_category_assignments`
--
ALTER TABLE `supplier_category_assignments`
  MODIFY `assignment_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_communications`
--
ALTER TABLE `supplier_communications`
  MODIFY `communication_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_communication_threads`
--
ALTER TABLE `supplier_communication_threads`
  MODIFY `thread_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_compliance`
--
ALTER TABLE `supplier_compliance`
  MODIFY `compliance_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_contacts`
--
ALTER TABLE `supplier_contacts`
  MODIFY `contact_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_contracts`
--
ALTER TABLE `supplier_contracts`
  MODIFY `contract_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  MODIFY `document_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  MODIFY `supplier_order_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_order_items`
--
ALTER TABLE `supplier_order_items`
  MODIFY `order_item_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `payment_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  MODIFY `performance_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_price_history`
--
ALTER TABLE `supplier_price_history`
  MODIFY `history_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_quality_metrics`
--
ALTER TABLE `supplier_quality_metrics`
  MODIFY `metric_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_ratings`
--
ALTER TABLE `supplier_ratings`
  MODIFY `rating_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supply_orders`
--
ALTER TABLE `supply_orders`
  MODIFY `supply_order_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supply_order_items`
--
ALTER TABLE `supply_order_items`
  MODIFY `supply_order_item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket_history`
--
ALTER TABLE `ticket_history`
  MODIFY `history_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  MODIFY `response_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transaction_audit_trails`
--
ALTER TABLE `transaction_audit_trails`
  MODIFY `trail_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `warehouse_staff`
--
ALTER TABLE `warehouse_staff`
  MODIFY `staff_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`);

--
-- Constraints for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `chat_sessions_ibfk_2` FOREIGN KEY (`csr_id`) REFERENCES `customer_sales_representatives` (`csr_id`);

--
-- Constraints for table `compliance_reports`
--
ALTER TABLE `compliance_reports`
  ADD CONSTRAINT `compliance_reports_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  ADD CONSTRAINT `customer_inquiries_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customer_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_support_tickets`
--
ALTER TABLE `customer_support_tickets`
  ADD CONSTRAINT `fk_csr` FOREIGN KEY (`csr_id`) REFERENCES `csrs` (`csr_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD CONSTRAINT `fk_delivery_agent` FOREIGN KEY (`delivery_agent_id`) REFERENCES `delivery_agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_auditors`
--
ALTER TABLE `financial_auditors`
  ADD CONSTRAINT `financial_auditors_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `financial_audit_logs`
--
ALTER TABLE `financial_audit_logs`
  ADD CONSTRAINT `financial_audit_logs_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD CONSTRAINT `financial_reports_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiry_responses`
--
ALTER TABLE `inquiry_responses`
  ADD CONSTRAINT `inquiry_responses_ibfk_1` FOREIGN KEY (`inquiry_id`) REFERENCES `customer_inquiries` (`inquiry_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_log_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD CONSTRAINT `knowledge_base_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `customer_sales_representatives` (`csr_id`);

--
-- Constraints for table `loyalty_points_history`
--
ALTER TABLE `loyalty_points_history`
  ADD CONSTRAINT `loyalty_points_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loyalty_points_history_ibfk_2` FOREIGN KEY (`adjusted_by`) REFERENCES `marketing_manager` (`manager_id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_program`
--
ALTER TABLE `loyalty_program`
  ADD CONSTRAINT `loyalty_program_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `marketing_manager` (`manager_id`);

--
-- Constraints for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD CONSTRAINT `marketing_campaigns_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `marketing_manager` (`manager_id`);

--
-- Constraints for table `marketing_manager`
--
ALTER TABLE `marketing_manager`
  ADD CONSTRAINT `marketing_manager_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_gateway_logs`
--
ALTER TABLE `payment_gateway_logs`
  ADD CONSTRAINT `payment_gateway_logs_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `product_analytics`
--
ALTER TABLE `product_analytics`
  ADD CONSTRAINT `product_analytics_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_manager`
--
ALTER TABLE `product_manager`
  ADD CONSTRAINT `product_manager_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_promotions`
--
ALTER TABLE `product_promotions`
  ADD CONSTRAINT `product_promotions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_promotions_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`);

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`campaign_id`),
  ADD CONSTRAINT `promotions_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `marketing_manager` (`manager_id`);

--
-- Constraints for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD CONSTRAINT `restock_requests_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restock_requests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `return_request` (`return_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `return_request`
--
ALTER TABLE `return_request`
  ADD CONSTRAINT `return_request_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_request_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  ADD CONSTRAINT `risk_assessments_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_assessments_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  ADD CONSTRAINT `shift_change_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `warehouse_staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD CONSTRAINT `staff_tasks_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `warehouse_staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_alert`
--
ALTER TABLE `stock_alert`
  ADD CONSTRAINT `stock_alert_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_category_assignments`
--
ALTER TABLE `supplier_category_assignments`
  ADD CONSTRAINT `supplier_category_assignments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_category_assignments_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `supplier_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_communications`
--
ALTER TABLE `supplier_communications`
  ADD CONSTRAINT `supplier_communications_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_communications_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_communication_threads`
--
ALTER TABLE `supplier_communication_threads`
  ADD CONSTRAINT `supplier_communication_threads_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_communication_threads_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_compliance`
--
ALTER TABLE `supplier_compliance`
  ADD CONSTRAINT `supplier_compliance_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_contacts`
--
ALTER TABLE `supplier_contacts`
  ADD CONSTRAINT `supplier_contacts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_contracts`
--
ALTER TABLE `supplier_contracts`
  ADD CONSTRAINT `supplier_contracts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD CONSTRAINT `supplier_documents_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD CONSTRAINT `supplier_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_order_items`
--
ALTER TABLE `supplier_order_items`
  ADD CONSTRAINT `supplier_order_items_ibfk_1` FOREIGN KEY (`supplier_order_id`) REFERENCES `supplier_orders` (`supplier_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_order_id`) REFERENCES `supplier_orders` (`supplier_order_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  ADD CONSTRAINT `supplier_performance_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_price_history`
--
ALTER TABLE `supplier_price_history`
  ADD CONSTRAINT `supplier_price_history_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_price_history_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_quality_metrics`
--
ALTER TABLE `supplier_quality_metrics`
  ADD CONSTRAINT `supplier_quality_metrics_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_ratings`
--
ALTER TABLE `supplier_ratings`
  ADD CONSTRAINT `supplier_ratings_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_ratings_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `supplier_orders` (`supplier_order_id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD CONSTRAINT `supply_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_order_items`
--
ALTER TABLE `supply_order_items`
  ADD CONSTRAINT `supply_order_items_ibfk_1` FOREIGN KEY (`supply_order_id`) REFERENCES `supply_orders` (`supply_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`csr_id`) REFERENCES `customer_sales_representatives` (`csr_id`);

--
-- Constraints for table `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD CONSTRAINT `ticket_history_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`),
  ADD CONSTRAINT `ticket_history_ibfk_2` FOREIGN KEY (`csr_id`) REFERENCES `customer_sales_representatives` (`csr_id`);

--
-- Constraints for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD CONSTRAINT `fk_ticket_responses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ticket_responses_ibfk_2` FOREIGN KEY (`csr_id`) REFERENCES `customer_sales_representatives` (`csr_id`);

--
-- Constraints for table `transaction_audit_trails`
--
ALTER TABLE `transaction_audit_trails`
  ADD CONSTRAINT `transaction_audit_trails_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_audit_trails_ibfk_2` FOREIGN KEY (`auditor_id`) REFERENCES `financial_auditors` (`auditor_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_loyalty_points`
--
ALTER TABLE `user_loyalty_points`
  ADD CONSTRAINT `user_loyalty_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
