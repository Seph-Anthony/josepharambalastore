-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 10:12 PM
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
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `guest_address` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status_id` int(20) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `guest_name`, `guest_phone`, `guest_address`, `total`, `status_id`, `created_at`, `updated_at`) VALUES
(3, 5, NULL, NULL, NULL, 90.00, 1, '2025-05-20 15:27:22', '2025-05-22 14:29:07'),
(4, 5, NULL, NULL, NULL, 555.00, 1, '2025-05-21 20:07:02', '2025-05-21 20:07:02'),
(5, 5, NULL, NULL, NULL, 555.00, 1, '2025-05-22 06:03:11', '2025-05-22 06:03:11'),
(6, 5, NULL, NULL, NULL, 555.00, 1, '2025-05-22 06:11:44', '2025-05-22 06:11:44'),
(7, 5, NULL, NULL, NULL, 555.00, 2, '2025-05-22 06:20:22', '2025-05-22 15:08:00'),
(8, 7, NULL, NULL, NULL, 150.00, 1, '2025-05-22 06:37:16', '2025-05-22 06:37:16'),
(9, 7, NULL, NULL, NULL, 600.00, 1, '2025-05-22 06:37:59', '2025-05-22 06:37:59'),
(10, 8, NULL, NULL, NULL, 555.00, 2, '2025-05-22 06:44:57', '2025-05-22 14:52:20'),
(11, 5, NULL, NULL, NULL, 67.00, 1, '2025-05-22 06:49:49', '2025-05-22 14:29:14'),
(12, 4, NULL, NULL, NULL, 600.00, 1, '2025-05-22 14:55:50', '2025-05-22 14:55:50'),
(13, 9, NULL, NULL, NULL, 1110.00, 1, '2025-05-22 15:16:16', '2025-05-22 15:16:16'),
(14, 5, NULL, NULL, NULL, 1267.00, 1, '2025-05-22 17:20:29', '2025-05-22 17:20:29'),
(15, 5, NULL, NULL, NULL, 2310.00, 1, '2025-05-22 17:37:18', '2025-05-22 17:37:18'),
(16, 5, NULL, NULL, NULL, 600.00, 1, '2025-05-22 18:04:09', '2025-05-22 18:04:09'),
(17, 6, NULL, NULL, NULL, 67.00, 1, '2025-05-22 20:10:38', '2025-05-22 20:10:38');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `seller_id` int(10) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `seller_id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(3, 4, 6, 9, 1, 555.00, 555.00),
(4, 4, 7, 9, 1, 555.00, 555.00),
(5, 5, 8, 8, 1, 150.00, 150.00),
(6, 4, 9, 11, 1, 600.00, 600.00),
(7, 4, 10, 9, 1, 555.00, 555.00),
(8, 8, 11, 12, 1, 67.00, 67.00),
(9, 4, 12, 11, 1, 600.00, 600.00),
(10, 4, 13, 9, 2, 555.00, 1110.00),
(11, 4, 14, 11, 2, 600.00, 1200.00),
(12, 8, 14, 12, 1, 67.00, 67.00),
(13, 4, 15, 9, 2, 555.00, 1110.00),
(14, 4, 15, 11, 2, 600.00, 1200.00),
(15, 4, 16, 11, 1, 600.00, 600.00),
(16, 8, 17, 12, 1, 67.00, 67.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_statuses`
--

CREATE TABLE `order_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_statuses`
--

INSERT INTO `order_statuses` (`id`, `name`) VALUES
(2, 'Delivered'),
(1, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(10) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `description`, `price`, `slug`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Rexona', '50ml ice cooling mentol rexona', 75.00, 'rexona', 'uploads/andres bonifacio enhanced.jpg', '2025-05-19 17:27:20', '2025-05-20 14:24:22'),
(2, 4, 2, 'Oishi', 'Best Snacks to be enjoyed when bored!', 15.00, 'oishi', 'uploads/40be362cf1c2bd639f331cb906faec62.jpg', '2025-05-19 18:38:10', '2025-05-20 14:24:25'),
(3, 1, 4, 'Nike Air Jordan Volume 5', 'Best Casual Shoes that you will ever have! fits in every style of clothing lez gaaaaw', 500.00, 'nike-air-jordan-volume-5', 'uploads/testingimage.jpg', '2025-05-19 18:39:33', '2025-05-20 14:24:27'),
(4, 1, 3, 'Wrench', 'Tools for your everyday usage!', 100.00, 'wrench', 'uploads/totalorder.png', '2025-05-19 18:40:38', '2025-05-20 14:24:29'),
(5, 4, 3, 'Charger Portable Type C', 'Use this to Charge your Phone as the name of the Product Suggest', 200.00, 'charger-portable-type-c', 'uploads/images.jpeg', '2025-05-19 18:42:44', '2025-05-20 14:24:31'),
(6, 3, 5, '50 ounces of diamond ring', 'wanna look good? try this ', 1000.00, '50-ounces-of-diamond-ring', 'uploads/70158a48cfd826c10d867541fa6a743d.jpg', '2025-05-19 18:43:28', '2025-05-20 14:24:52'),
(7, 4, 4, 'Nike Running Shoes (GREEN)', 'Size 45', 500.00, 'nike-running-shoes-(green)', 'uploads/kauban.png', '2025-05-19 18:44:56', '2025-05-20 14:24:55'),
(8, 5, 4, 'Slippers', 'Size 45', 150.00, 'slippers', 'uploads/299501f5a13e62fd9c9c9f77945ae80c.jpg', '2025-05-19 18:46:47', '2025-05-20 14:24:58'),
(9, 4, 5, 'testing', '53232', 555.00, 'testing', 'uploads/682ca946adf52_addthis.png', '2025-05-20 16:09:42', '2025-05-20 16:09:42'),
(11, 4, 4, 'gemao shoes', 'this is a great shoes!', 600.00, 'gemao-shoes', 'uploads/682ccf514322e_businessman.png', '2025-05-20 18:52:01', '2025-05-20 18:52:01'),
(12, 8, 2, 'Tanduay', '200 ml best seller', 67.00, 'tanduay', 'uploads/682ec8bfcddfc_people.png', '2025-05-22 06:48:31', '2025-05-22 06:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`) VALUES
(5, 'Accessories'),
(4, 'Clothing and Shoes'),
(2, 'Food and Beverage'),
(1, 'Personal Wellness'),
(3, 'Utilities and Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_type_id` int(10) NOT NULL DEFAULT 1,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `user_type_id`, `email`, `password`, `address`, `phone`, `birthdate`, `created_at`, `updated_at`) VALUES
(1, 'Joseph Anthony', 1, 'joseph@gmail.com', '$2y$10$EaGOy4tFfjsv/0cQzhd6ceSobrUEaYYFZTE7pnuTKxiaVM8E1jsv.', NULL, NULL, NULL, '2025-05-19 17:11:56', '2025-05-19 17:11:56'),
(3, 'Arambala', 1, 'anthony@gmail.com', '$2y$10$cR8BGw1/0gQVWjPr8Z3/ku5V/RvibUPfNUAWuXOkWR9juuidGdAcO', NULL, NULL, NULL, '2025-05-19 17:14:45', '2025-05-19 17:14:45'),
(4, 'Zennia Arambala', 2, 'zennia@gmail.com', '$2y$10$COdnUAnwUQMhswdOPBKjwOEC328X8kEp.rvqyyWlcVpfB2SLURorW', 'Minglanilla Ward 2', '09456334331', '2005-05-20', '2025-05-19 17:16:04', '2025-05-20 14:49:52'),
(5, 'Lloyd Arambala', 1, 'lloyd@gmail.com', '$2y$10$Sm055kovwJEutQwhxTaAsOLfc0RznJ4AMSbta9Uw8XfMB8UhwBIGm', NULL, NULL, NULL, '2025-05-19 17:31:41', '2025-05-20 14:49:58'),
(6, 'Mike Arambala', 3, 'mike@gmail.com', '$2y$10$RR10eWX6b.TRtFieN4BfKOkvmzLTLDZlVzoXdcfFfSVi1MiHnuo5a', 'Minglanilla Ward 2', '09786675663', '1999-05-05', '2025-05-21 08:55:41', '2025-05-21 14:56:14'),
(7, 'Peter Arambala', 1, 'peter@gmail.com', '$2y$10$7jwjAIfjmqDjTzUmE/0Lou5ikNVlKca9GO.sfBJvCE06Mgd19j.wG', 'Ward 3 Minglanilla Cebu', '09337762344', '5552-05-05', '2025-05-22 00:36:50', '2025-05-22 00:36:50'),
(8, 'Caloy Arambala', 2, 'caloy@gmail.com', '$2y$10$ZAQkZuRXPsCRq9yggbBqc.3HOsEx1wOt0yurlRAUjD6uZV/KCsGf2', 'Ward 2 Minglanilla Cebu', '09456334331', '2006-05-05', '2025-05-22 00:43:27', '2025-05-22 06:45:22'),
(9, 'Testing testing', 1, 'test@gmail.com', '$2y$10$cYAIHlVXKIj4tQlGIMCr5.U4wWvaHoPJqSucyXI.DZOI.UNsMsznW', 'Ward 3 Minglanilla Cebu', '09876456321', '2007-05-05', '2025-05-22 06:35:41', '2025-05-22 06:35:41'),
(10, 'Larry Josh', 2, 'larry@gmail.com', '$2y$10$3v.ypczXSLYiUEYEd7IrAOc.M/bZ5.nZSwydxvlwvu4Zh5PXSLgz2', 'Ward 4 Minglanilla Cebu', '09337762344', '2001-07-07', '2025-05-22 12:59:36', '2025-05-22 12:59:36'),
(11, 'John Mason', 3, 'john@gmail.com', '$2y$10$oAmEQ2GU5SKxinJRkAoGk.UXtr8ob/iOsOgG5Hw.yxx2uE0sjECMa', 'Ward 4 Minglanilla Cebu', '09888976567', '2000-05-05', '2025-05-22 13:01:30', '2025-05-22 13:01:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `name`) VALUES
(3, 'Admin'),
(1, 'Customer'),
(2, 'Seller');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_order_status` (`status_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `sell_id` (`seller_id`);

--
-- Indexes for table `order_statuses`
--
ALTER TABLE `order_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_product_seller` (`seller_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_statuses`
--
ALTER TABLE `order_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_status` FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sell_id` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `user_type_id` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
