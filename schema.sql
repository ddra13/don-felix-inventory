-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 10:57 AM
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
-- Database: `don_felix_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` enum('Meat & Poultry','Produce','Dry Goods & Seasonings','Packaging & Disposables') NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` enum('kg','grams','pieces','liters','packs','sacks') NOT NULL DEFAULT 'pieces',
  `min_alert` decimal(10,2) NOT NULL DEFAULT 5.00,
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `category`, `current_stock`, `unit`, `min_alert`, `cost_per_unit`, `updated_at`) VALUES
(1, 'Pork Mask / Belly (Boiled & Chopped)', 'Meat & Poultry', 25.00, 'kg', 8.00, 260.00, '2026-11-10 11:31:34'),
(2, 'Pork Chop Cuts', 'Meat & Poultry', 15.00, 'kg', 5.00, 280.00, '2026-11-10 11:31:34'),
(3, 'Fresh Eggs', 'Produce', 90.00, 'pieces', 30.00, 9.00, '2026-11-10 11:31:34'),
(4, 'Red Onions', 'Produce', 10.00, 'kg', 3.00, 140.00, '2026-11-11 09:47:43'),
(5, 'Sizzling Gravy Mix', 'Dry Goods & Seasonings', 8.00, 'liters', 2.00, 85.00, '2026-11-10 11:31:34'),
(6, 'Cooking Oil', 'Dry Goods & Seasonings', 20.00, 'liters', 5.00, 75.00, '2026-11-10 11:31:34'),
(7, 'Calamansi', 'Produce', 4.00, 'kg', 2.00, 90.00, '2026-11-10 11:31:34'),
(8, 'White Rice', 'Dry Goods & Seasonings', 50.00, 'kg', 15.00, 52.00, '2026-11-11 09:47:24'),
(9, 'Takeout Box (Plastic)', 'Packaging & Disposables', 150.00, 'pieces', 50.00, 6.50, '2026-11-11 09:47:07');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `action_type` enum('STOCK IN','STOCK OUT','POS SALE','WASTE/SPOILAGE') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'units',
  `logged_by` varchar(100) NOT NULL DEFAULT 'System',
  `notes` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `dish_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `dish_name`, `category`, `price`, `is_available`) VALUES
(1, 'Sizzling Sisig with Rice & Egg', 'Sizzling Meals', 139.00, 1),
(2, 'Sizzling Pork Chop with Rice', 'Sizzling Meals', 149.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(1, 1, 1, 0.18),
(2, 1, 3, 1.00),
(3, 1, 4, 0.03),
(4, 1, 8, 0.15),
(5, 2, 2, 0.20),
(6, 2, 5, 0.08),
(7, 2, 8, 0.15);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Staff','Client') NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Don Felix Owner', 'owner', '$2y$10$wT5H8gN4g8Wl8c9qK2w5.exH6F0IvhzG/tV6H9t1dM6Q9C5F9aK9S', 'Admin', '2026-11-10 11:31:34'),
(2, 'Kitchen Staff', 'staff', '$2y$10$lQx1D7qYq1M.6qYkC8eZ8.iQjK9sU/zV2m0X8N9W5B2Q7E4R3T6Y2', 'Staff', '2026-11-10 11:31:34'),
(3, 'Don Felix', 'admin', '$2y$10$.NDSwK7sGKotoZzt6i6cyOsxpROIKrB2KJhNcquAIcytFDnv/gVjS', 'Admin', '2026-11-10 12:30:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_item_id` (`menu_item_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
