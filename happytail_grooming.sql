-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Host: localhost:3307
-- Generation Time: Dec 27, 2025 at 07:34 PM
-- Server version: 5.6.21
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `happytail_grooming`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
`appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `cat_id`, `service_id`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(1, 2, 1, 1, '2025-12-23', '21:15:00', 'Cancelled', '2025-12-22 13:19:21'),
(2, 2, 1, 4, '2025-12-28', '13:53:00', 'Completed', '2025-12-26 17:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `cats`
--

CREATE TABLE IF NOT EXISTS `cats` (
`cat_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cat_name` varchar(50) NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `special_notes` text
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cats`
--

INSERT INTO `cats` (`cat_id`, `customer_id`, `cat_name`, `breed`, `age`, `special_notes`) VALUES
(1, 2, 'Pijan', 'Persian', 5, ''),
(2, 2, 'oyen', 'Tiger', 3, 'Ganas');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE IF NOT EXISTS `customers` (
`customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `phone`, `address`) VALUES
(1, 3, '0123456789', '12J, Jalan Kota Raja, Shahbandaraya, 41000 Klang, Selangor'),
(2, 4, '01117695765', '43J, BATU 1 1/2, JALAN KOTA RAJA, SHAHBANDARAYA, 41000 KLANG, SELANGOR');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE IF NOT EXISTS `services` (
`service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT '60' COMMENT 'Duration in minutes',
  `description` text
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `price`, `duration`, `description`) VALUES
(1, 'Basic Grooming', '50.00', 60, 'Bath, brush, nail trim, and ear cleaning for a clean and healthy cat'),
(2, 'Full Grooming', '80.00', 90, 'Complete grooming package including haircut, bath, and all basic services'),
(3, 'Nail Trim Only', '15.00', 15, 'Quick and gentle nail trimming service for your cat''s comfort'),
(4, 'Bath & Brush', '35.00', 45, 'Professional bathing and brushing to keep your cat''s coat shiny and healthy'),
(5, 'Premium Package', '120.00', 120, 'Luxury grooming with special treatments, aromatherapy, and premium products');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@happytail.com', 'admin123', 'admin', '2025-12-22 09:34:34'),
(2, 'Qolbie', 'staff@happytail.com', 'staff123', 'staff', '2025-12-22 09:34:34'),
(3, 'Naim', 'naim@gmail.com', 'customer123', 'customer', '2025-12-22 09:34:34'),
(4, 'Anna', 'try@gmail.com', '123456', 'customer', '2025-12-22 11:38:40'),
(5, 'Jatul', 'jatul@gmail.com', '$2y$10$EXjQYNrBcxJMlSt4zCpEtO7yB0/VRMeeLGXaWn7Q9F.jjEJg60pzy', 'staff', '2025-12-26 19:26:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
 ADD PRIMARY KEY (`appointment_id`), ADD KEY `cat_id` (`cat_id`), ADD KEY `service_id` (`service_id`), ADD KEY `fk_appointments_customer` (`customer_id`);

--
-- Indexes for table `cats`
--
ALTER TABLE `cats`
 ADD PRIMARY KEY (`cat_id`), ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
 ADD PRIMARY KEY (`customer_id`), ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
 ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
 ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `cats`
--
ALTER TABLE `cats`
MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `cats` (`cat_id`),
ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
ADD CONSTRAINT `fk_appointments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `cats`
--
ALTER TABLE `cats`
ADD CONSTRAINT `cats_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
