-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 03, 2022 at 09:15 AM
-- Server version: 5.7.38-41-log
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbn9kakrzufluq`
--

-- --------------------------------------------------------

--
-- Table structure for table `bot_settings`
--

CREATE TABLE `bot_settings` (
  `id` int(11) NOT NULL,
  `time_tolerance` int(11) DEFAULT NULL,
  `location_tolerance` decimal(10,2) NOT NULL,
  `userbreak_req_step` int(1) DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bot_settings`
--

INSERT INTO `bot_settings` (`id`, `time_tolerance`, `location_tolerance`) VALUES
(1, 10, '0.10');

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `branch_lat` varchar(100) DEFAULT NULL,
  `branch_lon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`branch_id`, `branch_name`, `branch_lat`, `branch_lon`) VALUES
(1, 'Branch A', '11.592616', '104.933839'),
(2, 'Branch B', '11.592616', '104.933839');

-- --------------------------------------------------------

--
-- Table structure for table `user_clock_in_out`
--

CREATE TABLE `user_clock_in_out` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `clock_in_day` varchar(10) DEFAULT NULL,
  `clock_in_location_status` varchar(255) DEFAULT NULL,
  `clock_in_lat` varchar(100) DEFAULT NULL,
  `clock_in_lon` varchar(100) DEFAULT NULL,
  `clock_in_location_msg_id` int(11) DEFAULT NULL,
  `clock_in_distance` varchar(55) DEFAULT NULL,
  `clock_in_time_status` varchar(255) DEFAULT NULL,
  `clock_in_time` varchar(55) DEFAULT NULL,
  `work_start_time` varchar(55) DEFAULT NULL,
  `clock_in_selfie_msg_id` varchar(100) DEFAULT NULL,
  `is_clock_in` varchar(100) DEFAULT NULL,
  `created_at` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_clock_in_out`
--

INSERT INTO `user_clock_in_out` (`id`, `user_id`, `clock_in_day`, `clock_in_location_status`, `clock_in_lat`, `clock_in_lon`, `clock_in_location_msg_id`, `clock_in_distance`, `clock_in_time_status`, `clock_in_time`, `work_start_time`, `clock_in_selfie_msg_id`, `is_clock_in`, `created_at`) VALUES
(58, 5551369998, 'Mon', 'OK', '11.59262', '104.933823', 6399, '0', 'LATE', '16:14', '09:00', 'AgACAgUAAxkBAAIZAWLnmYXsXIoTRHhv79vnDjSNVGy2AAIVrzEbzT5BV6J5Pul7gWpmAQADAgADcwADKQQ', 'clock_in', '01/08/2022 16:14:19 pm'),
(59, 5551369998, 'Mon', 'OK', '11.592605', '104.933819', 6409, '0', 'EARLY CLOCK OUT', '16:19', '18:00', 'AgACAgUAAxkBAAIZC2LnmqI_bhOWc951KXW8tzjhFvxWAAIZrzEbzT5BV46U_RRxCLWSAQADAgADcwADKQQ', 'clock_out', '01/08/2022 16:19:14 pm');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `approval_status` varchar(100) DEFAULT NULL,
  `notification_new_user_msg_id` bigint(20) DEFAULT NULL,
  `list_emp_msg_id` int(11) DEFAULT NULL,
  `photo_message_id` bigint(20) DEFAULT NULL,
  `photo_id` varchar(200) DEFAULT NULL,
  `day_selected_msg_id` bigint(20) DEFAULT NULL,
  `set_start_time_msg_id` bigint(20) DEFAULT NULL,
  `set_end_time_msg_id` bigint(20) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `step` varchar(50) DEFAULT NULL,
  `lang` enum('en','kh') DEFAULT 'kh',
  `created_at` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `firstname`, `lastname`, `phone`, `email`, `approval_status`, `notification_new_user_msg_id`, `list_emp_msg_id`, `photo_message_id`, `photo_id`, `day_selected_msg_id`, `set_start_time_msg_id`, `set_end_time_msg_id`, `branch_id`, `branch_name`, `step`, `lang`, `created_at`) VALUES
(79, 12345678911, 'Tonghuor', 'Heng 3', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(80, 12367589112, 'Tonghuor', 'Heng 4', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, 1, 'Branch A', 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(81, 1234567893, 'Tonghuor', 'Heng 5', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(82, 1234567894, 'Tonghuor', 'Heng 6', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(83, 1234567895, 'Tonghuor', 'Heng 7', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(84, 1234567896, 'Tonghuor', 'Heng 8', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(85, 1234567897, 'Tonghuor', 'Heng 9', '+85590692542', NULL, NULL, NULL, NULL, 4359, 'AgACAgUAAxkBAAIRB2Lg-st5fT-UBDEMsFBGMXmNM0DXAAI2sTEbH1kIVwL8A37yV0HCAQADAgADeAADKQQ', NULL, NULL, NULL, NULL, NULL, 'select_lang', 'kh', '27/07/2022 15:43:59 pm'),
(86, 1372368878, 'Tong Huor', 'HENG', '', NULL, NULL, 5777, 6311, NULL, NULL, 5784, 5786, 5787, 1, 'Branch A', 'select_lang', 'kh', '27/07/2022 15:55:50 pm'),
(89, 1278764240, 'Arian', '', '85570907230', 'Arian@posflowkh.com', NULL, NULL, NULL, 5512, 'AgACAgUAAxkBAAIViGLjo_-pSmxm1U-sLnsutqU8lwyRAAKHrzEbrIIBV__-l0tT3OswAQADAgADcwADKQQ', NULL, NULL, NULL, 2, 'Branch B', 'clock_in_done', 'kh', '29/07/2022 16:10:35 pm'),
(93, 5551369998, 'Tonghuor', 'Heng 2', '85590692542', 'Skip and Send', 'approved', NULL, NULL, 6332, 'AgACAgUAAxkBAAIYvGLnljgUv2PmeuWnNOhQ7vGJIJ25AAKMsjEb3l84V1p5MyEiGnEmAQADAgADcwADKQQ', NULL, NULL, NULL, 2, 'Branch B', 'clock_out_done', 'kh', '01/08/2022 16:00:40 pm');

-- --------------------------------------------------------

--
-- Table structure for table `user_working_hour`
--

CREATE TABLE `user_working_hour` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `work_day` varchar(255) NOT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_working_hour`
--

INSERT INTO `user_working_hour` (`id`, `user_id`, `work_day`, `start_time`, `end_time`, `created_at`) VALUES
(526, 5551369998, 'Tue', '09:00', '18:00', '01/08/2022 04:55:30 am'),
(527, 5551369998, 'Wed', '09:00', '18:00', '01/08/2022 04:55:31 am'),
(528, 5551369998, 'Thu', '09:00', '18:00', '01/08/2022 04:55:33 am'),
(529, 5551369998, 'Fri', '09:00', '18:00', '01/08/2022 04:55:34 am'),
(531, 5551369998, 'Mon', '09:00', '18:00', '01/08/2022 05:06:00 am');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_settings`
--
ALTER TABLE `bot_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `user_clock_in_out`
--
ALTER TABLE `user_clock_in_out`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_working_hour`
--
ALTER TABLE `user_working_hour`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bot_settings`
--
ALTER TABLE `bot_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_clock_in_out`
--
ALTER TABLE `user_clock_in_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `user_working_hour`
--
ALTER TABLE `user_working_hour`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=532;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
