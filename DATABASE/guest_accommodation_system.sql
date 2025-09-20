-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 12:41 PM
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
-- Database: `guest_accommodation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_Email` varchar(255) NOT NULL,
  `Admin_Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`Admin_ID`, `Admin_Email`, `Admin_Password`) VALUES
(3, 'administrator@gmail.com', '$2y$10$TED48TAtLQQfyjT.uf3ubeHXJncitFUpuSBtunA5rf2owAMJ2PfTC');

-- --------------------------------------------------------

--
-- Table structure for table `amenity`
--

CREATE TABLE `amenity` (
  `Amenity_ID` int(11) NOT NULL,
  `Amenity_Name` varchar(255) NOT NULL,
  `Amenity_Desc` varchar(255) NOT NULL,
  `Amenity_Cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amenity`
--

INSERT INTO `amenity` (`Amenity_ID`, `Amenity_Name`, `Amenity_Desc`, `Amenity_Cost`) VALUES
(5, 'Wi-Fi', 'Add Wi-Fi', 500.00),
(6, 'Breakfast', 'Add Breakfast', 1000.00),
(7, 'Spa', 'Add Spa', 800.00),
(8, 'ATV', 'Add ATW', 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `amenityprices`
--

CREATE TABLE `amenityprices` (
  `AP_ID` int(11) NOT NULL,
  `Amenity_ID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `PromValidF` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PromValidT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amenityprices`
--

INSERT INTO `amenityprices` (`AP_ID`, `Amenity_ID`, `Price`, `PromValidF`, `PromValidT`) VALUES
(2, 5, 100.00, '2025-06-22 07:18:00', '2025-07-22 07:18:00');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `Booking_ID` int(11) NOT NULL,
  `Cust_ID` int(11) NOT NULL,
  `Emp_ID` int(11) DEFAULT NULL,
  `Booking_IN` timestamp NOT NULL DEFAULT current_timestamp(),
  `Booking_Out` timestamp NULL DEFAULT NULL,
  `Booking_Cost` decimal(10,2) NOT NULL,
  `Booking_Status` varchar(255) NOT NULL,
  `Guests` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`Booking_ID`, `Cust_ID`, `Emp_ID`, `Booking_IN`, `Booking_Out`, `Booking_Cost`, `Booking_Status`, `Guests`) VALUES
(13, 12, 3, '2025-06-19 16:00:00', '2025-06-20 16:00:00', 2000.00, 'Paid', 5),
(14, 13, 3, '2025-06-22 16:00:00', '2025-06-23 16:00:00', 2000.00, 'Paid', 5),
(15, 12, 3, '2025-06-25 16:00:00', '2025-06-26 16:00:00', 1600.00, 'Paid', 5),
(16, 12, 3, '2025-06-13 16:00:00', '2025-06-14 16:00:00', 2000.00, 'Paid', 5),
(17, 14, 3, '2025-06-24 16:00:00', '2025-06-25 16:00:00', 2600.00, 'Paid', 10),
(18, 14, NULL, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 4300.00, 'Paid', 10),
(19, 12, NULL, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 1500.00, 'Paid', 2),
(20, 12, NULL, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 6300.00, 'Paid', 5),
(21, 12, 3, '2025-06-22 16:00:00', '2025-06-23 16:00:00', 1600.00, 'Paid', 5),
(22, 12, NULL, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 7300.00, 'Paid', 10),
(23, 15, 3, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 6300.00, 'Pending', 10),
(24, 15, 3, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 6300.00, 'Pending', 10),
(25, 15, 3, '2025-06-12 16:00:00', '2025-06-13 16:00:00', 6300.00, 'Pending', 10);

-- --------------------------------------------------------

--
-- Table structure for table `bookingamenity`
--

CREATE TABLE `bookingamenity` (
  `BA_ID` int(11) NOT NULL,
  `Amenity_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `RA_Quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookingamenity`
--

INSERT INTO `bookingamenity` (`BA_ID`, `Amenity_ID`, `Booking_ID`, `RA_Quantity`) VALUES
(20, 5, 13, 0),
(21, 5, 14, 0),
(22, 5, 15, 0),
(23, 5, 16, 0),
(24, 5, 17, 0),
(25, 7, 18, 0),
(26, 5, 20, 0),
(27, 6, 20, 0),
(28, 7, 20, 0),
(29, 8, 20, 0),
(30, 5, 21, 0),
(31, 5, 22, 0),
(32, 6, 22, 0),
(33, 7, 22, 0),
(34, 8, 22, 0),
(35, 5, 23, 0),
(36, 6, 23, 0),
(37, 7, 23, 0),
(38, 8, 23, 0),
(39, 5, 24, 0),
(40, 6, 24, 0),
(41, 7, 24, 0),
(42, 8, 24, 0),
(43, 5, 25, 0),
(44, 6, 25, 0),
(45, 7, 25, 0),
(46, 8, 25, 0);

-- --------------------------------------------------------

--
-- Table structure for table `bookingroom`
--

CREATE TABLE `bookingroom` (
  `BR_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Room_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookingroom`
--

INSERT INTO `bookingroom` (`BR_ID`, `Booking_ID`, `Room_ID`) VALUES
(13, 13, 4),
(14, 14, 4),
(15, 15, 4),
(16, 16, 4),
(17, 17, 3),
(18, 18, 3),
(19, 19, 4),
(20, 20, 5),
(21, 21, 4),
(22, 22, 3),
(23, 23, 5),
(24, 24, 5),
(25, 25, 4);

-- --------------------------------------------------------

--
-- Table structure for table `bookingservice`
--

CREATE TABLE `bookingservice` (
  `BS_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Service_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookingservice`
--

INSERT INTO `bookingservice` (`BS_ID`, `Booking_ID`, `Service_ID`) VALUES
(7, 18, 2),
(8, 20, 2),
(9, 22, 2),
(10, 23, 2),
(11, 24, 2),
(12, 25, 2);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Cust_ID` int(11) NOT NULL,
  `Cust_FN` varchar(255) NOT NULL,
  `Cust_LN` varchar(255) NOT NULL,
  `Cust_Email` varchar(255) NOT NULL,
  `Cust_Phone` bigint(20) NOT NULL,
  `Cust_Password` varchar(255) NOT NULL,
  `is_banned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`Cust_ID`, `Cust_FN`, `Cust_LN`, `Cust_Email`, `Cust_Phone`, `Cust_Password`, `is_banned`) VALUES
(12, 'Jhiro Ramir', 'Tool', 'jhiroramir@gmail.com', 9151046166, '$2y$10$MorQ9KvtlqK/Cf7FkF60BuWvcYfRH6RBAoaa.rgmrNdZ324JclYMy', 0),
(13, 'Carl', 'Rocafor', 'carl@gmail.com', 9151046199, '$2y$10$U7eHRjSezt8jQl0FU6KI5uMfAKQ7tmnU7Ry2luR.Bwm2ItLfkeWvC', 0),
(14, 'Timothy', 'Barachael', 'timo@gmail.com', 9151046167, '$2y$10$pD7I58aOBIFp0giYT90ndeyHI1FyMxWxouzt8Xv3QA6fbc2o.HFNi', 0),
(15, 'gero', 'quita', 'gero@gmail.com', 9151046118, '$2y$10$7APVNhSPgVntpykystFXxeD3SNHrcH/L26/bXeK6DSLN8iNHXT4t.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `Emp_ID` int(11) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Emp_FN` varchar(255) NOT NULL,
  `Emp_LN` varchar(255) NOT NULL,
  `Emp_Email` varchar(255) NOT NULL,
  `Emp_Phone` bigint(20) NOT NULL,
  `Emp_Role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`Emp_ID`, `Admin_ID`, `Emp_FN`, `Emp_LN`, `Emp_Email`, `Emp_Phone`, `Emp_Role`) VALUES
(3, 3, 'Timothy', 'Barachael', 'timo@gmail.com', 9151046167, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `Feed_ID` int(11) NOT NULL,
  `Cust_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Feed_Rating` decimal(6,2) NOT NULL,
  `Feed_Comment` text NOT NULL,
  `Feed_DOF` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Payment_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Payment_Amount` decimal(10,2) NOT NULL,
  `Payment_Method` varchar(255) NOT NULL,
  `Receipt_Image` varchar(255) NOT NULL,
  `Payment_DOF` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Booking_ID`, `Payment_Date`, `Payment_Amount`, `Payment_Method`, `Receipt_Image`, `Payment_DOF`) VALUES
(9, 17, '2025-06-13 08:35:46', 2600.00, 'GCash', '', '2025-06-13 16:35:46'),
(10, 18, '2025-06-13 08:43:42', 4300.00, 'GCash', '', '2025-06-13 17:01:56'),
(13, 15, '2025-06-13 09:02:36', 1600.00, 'GCash', '', '2025-06-13 17:03:27'),
(14, 13, '2025-06-13 09:06:55', 2000.00, 'GCash', '', '2025-06-13 17:07:35'),
(15, 16, '2025-06-13 09:11:50', 2000.00, 'GCash', 'recite_1749805910.jpg', '2025-06-13 17:11:50'),
(16, 19, '2025-06-13 09:15:33', 1500.00, 'GCash', 'recite_1749806133.jpg', '2025-06-13 17:15:33'),
(17, 20, '2025-06-13 09:21:39', 6300.00, 'GCash', 'recite_1749806499.jpeg', '2025-06-13 17:21:39'),
(18, 21, '2025-06-13 09:28:16', 1600.00, 'GCash', 'recite_1749806896.jpg', '2025-06-13 17:28:16'),
(19, 22, '2025-06-13 09:39:07', 7300.00, 'GCash', 'recite_1749807547.jpg', '2025-06-13 17:39:07');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `Room_ID` int(11) NOT NULL,
  `Room_Type` varchar(255) NOT NULL,
  `Room_Rate` decimal(10,2) NOT NULL,
  `Room_Cap` int(11) NOT NULL,
  `Room_Status` varchar(20) NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`Room_ID`, `Room_Type`, `Room_Rate`, `Room_Cap`, `Room_Status`) VALUES
(3, 'Deluxe', 2500.00, 20, 'Unavailable'),
(4, 'Pool', 1500.00, 20, 'Available'),
(5, 'Family', 1500.00, 10, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `roomprices`
--

CREATE TABLE `roomprices` (
  `RP_ID` int(11) NOT NULL,
  `Room_ID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `PromValidF` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PromValidT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `Service_ID` int(11) NOT NULL,
  `Service_Name` varchar(255) NOT NULL,
  `Service_Desc` varchar(255) NOT NULL,
  `Service_Cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`Service_ID`, `Service_Name`, `Service_Desc`, `Service_Cost`) VALUES
(2, 'Pick up from home', 'A shuttle will pick you up at your destination', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `serviceprices`
--

CREATE TABLE `serviceprices` (
  `SP_ID` int(11) NOT NULL,
  `Service_ID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `PromValidF` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PromValidT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Admin_Email` (`Admin_Email`);

--
-- Indexes for table `amenity`
--
ALTER TABLE `amenity`
  ADD PRIMARY KEY (`Amenity_ID`);

--
-- Indexes for table `amenityprices`
--
ALTER TABLE `amenityprices`
  ADD PRIMARY KEY (`AP_ID`),
  ADD KEY `Amenity_ID` (`Amenity_ID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`Booking_ID`),
  ADD KEY `Cust_ID` (`Cust_ID`),
  ADD KEY `Emp_ID` (`Emp_ID`);

--
-- Indexes for table `bookingamenity`
--
ALTER TABLE `bookingamenity`
  ADD PRIMARY KEY (`BA_ID`),
  ADD KEY `Amenity_ID` (`Amenity_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `bookingroom`
--
ALTER TABLE `bookingroom`
  ADD PRIMARY KEY (`BR_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`),
  ADD KEY `Room_ID` (`Room_ID`);

--
-- Indexes for table `bookingservice`
--
ALTER TABLE `bookingservice`
  ADD PRIMARY KEY (`BS_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`),
  ADD KEY `Service_ID` (`Service_ID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`Cust_ID`),
  ADD UNIQUE KEY `Cust_Email` (`Cust_Email`),
  ADD UNIQUE KEY `Cust_Phone` (`Cust_Phone`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`Emp_ID`),
  ADD UNIQUE KEY `Emp_Email` (`Emp_Email`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`Feed_ID`),
  ADD KEY `Cust_ID` (`Cust_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD UNIQUE KEY `unique_booking` (`Booking_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`Room_ID`);

--
-- Indexes for table `roomprices`
--
ALTER TABLE `roomprices`
  ADD PRIMARY KEY (`RP_ID`),
  ADD KEY `Room_ID` (`Room_ID`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`Service_ID`);

--
-- Indexes for table `serviceprices`
--
ALTER TABLE `serviceprices`
  ADD PRIMARY KEY (`SP_ID`),
  ADD KEY `Service_ID` (`Service_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `amenity`
--
ALTER TABLE `amenity`
  MODIFY `Amenity_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `amenityprices`
--
ALTER TABLE `amenityprices`
  MODIFY `AP_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `Booking_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `bookingamenity`
--
ALTER TABLE `bookingamenity`
  MODIFY `BA_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `bookingroom`
--
ALTER TABLE `bookingroom`
  MODIFY `BR_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `bookingservice`
--
ALTER TABLE `bookingservice`
  MODIFY `BS_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Cust_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `Emp_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `Feed_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `Room_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roomprices`
--
ALTER TABLE `roomprices`
  MODIFY `RP_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `Service_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `serviceprices`
--
ALTER TABLE `serviceprices`
  MODIFY `SP_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `amenityprices`
--
ALTER TABLE `amenityprices`
  ADD CONSTRAINT `amenityprices_ibfk_1` FOREIGN KEY (`Amenity_ID`) REFERENCES `amenity` (`Amenity_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`Cust_ID`) REFERENCES `customer` (`Cust_ID`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`Emp_ID`) REFERENCES `employee` (`Emp_ID`);

--
-- Constraints for table `bookingamenity`
--
ALTER TABLE `bookingamenity`
  ADD CONSTRAINT `bookingamenity_ibfk_1` FOREIGN KEY (`Amenity_ID`) REFERENCES `amenity` (`Amenity_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookingamenity_ibfk_2` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bookingroom`
--
ALTER TABLE `bookingroom`
  ADD CONSTRAINT `bookingroom_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookingroom_ibfk_2` FOREIGN KEY (`Room_ID`) REFERENCES `room` (`Room_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bookingservice`
--
ALTER TABLE `bookingservice`
  ADD CONSTRAINT `bookingservice_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookingservice_ibfk_2` FOREIGN KEY (`Service_ID`) REFERENCES `service` (`Service_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`Admin_ID`) REFERENCES `administrator` (`Admin_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`Cust_ID`) REFERENCES `customer` (`Cust_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`);

--
-- Constraints for table `roomprices`
--
ALTER TABLE `roomprices`
  ADD CONSTRAINT `roomprices_ibfk_1` FOREIGN KEY (`Room_ID`) REFERENCES `room` (`Room_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `serviceprices`
--
ALTER TABLE `serviceprices`
  ADD CONSTRAINT `serviceprices_ibfk_1` FOREIGN KEY (`Service_ID`) REFERENCES `service` (`Service_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
