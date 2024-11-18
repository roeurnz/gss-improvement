-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 02, 2023 at 08:24 AM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `freelancer_ezzebot_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `bot_admin`
--

CREATE TABLE `bot_admin` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `admin_name` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `step` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `temp` text CHARACTER SET utf8 DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_cron`
--

CREATE TABLE `bot_cron` (
  `id` int(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `cron_file` varchar(50) NOT NULL,
  `cron_command` varchar(200) NOT NULL,
  `cron_config` text NOT NULL,
  `cron_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bot_cron`
--

INSERT INTO `bot_cron` (`id`, `title`, `cron_file`, `cron_command`, `cron_config`, `cron_active`, `last_run`) VALUES
(1, 'report-absent', 'report.php', 'daily-presence', '{\"params\":[],\"runtime\":{\"schedule\":\"daily\",\"time\":\"everyminutes\"}}', 1, '2023-05-02 13:18:01'),
(2, 'report-attendance', 'report.php', 'attendance', '{\"params\":{\"time\":\"daily\"},\"runtime\":{\"schedule\":\"daily\",\"time\":\"17:15\"}}', 0, '0000-00-00 00:00:00'),
(3, 'report-weekly', 'report.php', 'attendance', '{\"params\":{\"time\":\"weekly\"},\"runtime\":{\"schedule\":\"weekly\",\"time\":\"23:50\"}}', 0, '0000-00-00 00:00:00'),
(4, 'report-monthly', 'report.php', 'attendance', '{\"params\":{\"time\":\"monthly\"},\"runtime\":{\"schedule\":\"monthly\",\"time\":\"23:50\"}}', 0, '0000-00-00 00:00:00'),
(5, 'dead-man', 'dead-man-cron.php', 'run', '{\"params\":{\"time\":\"daily\"},\"runtime\":{\"schedule\":\"daily\",\"time\":\"everyminutes\"}}', 1, '2023-05-02 13:18:01'),
(6, 'report-not-clocked-out', 'report.php', 'daily-clock-out', '{\"params\":[],\"runtime\":{\"schedule\":\"daily\",\"time\":\"everyminutes\"}}', 1, '2023-05-02 13:18:01'),
(7, 'clock-out-reminder', 'reminder.php', 'clock-out', '{\"params\":[],\"runtime\":{\"schedule\":\"daily\",\"time\":\"everyminutes\"}}', 1, '2023-05-02 13:18:01'),
(8, 'daily-report', 'daily-report.php', 'daily-report', '{\"params\":{\"time\":\"daily\"},\"runtime\":{\"schedule\":\"daily\",\"time\":\"07:00\"},\"additional\":{\"data-hour-range\":24}}', 1, '2023-05-02 07:00:07'),
(9, 'schedule-message', 'schedule-message.php', 'run-s-message', '{\"params\":[],\"runtime\":{\"schedule\":\"daily\",\"time\":\"everyminutes\"}}', 1, '2023-05-02 13:18:01');

-- --------------------------------------------------------

--
-- Table structure for table `bot_settings`
--

CREATE TABLE `bot_settings` (
  `id` int(11) NOT NULL,
  `time_tolerance` int(11) DEFAULT NULL,
  `location_tolerance` decimal(10,3) NOT NULL,
  `userbreak_req_step` int(1) NOT NULL,
  `clockuser_req_step` int(1) NOT NULL,
  `dead_man_feature` tinyint(1) NOT NULL DEFAULT 0,
  `dead_man_task_time` int(3) NOT NULL DEFAULT 30,
  `welcome_msg` text NOT NULL,
  `welcome_img` varchar(100) NOT NULL,
  `company_email` varchar(50) NOT NULL,
  `company_phone` varchar(30) NOT NULL,
  `module_visit` tinyint(1) NOT NULL DEFAULT 0,
  `module_alert` tinyint(1) NOT NULL DEFAULT 0,
  `module_break` tinyint(1) NOT NULL DEFAULT 0,
  `clockout_reminder_interval` int(3) NOT NULL,
  `clockout_reminder_timeout` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bot_settings`
--

INSERT INTO `bot_settings` (`id`, `time_tolerance`, `location_tolerance`, `userbreak_req_step`, `clockuser_req_step`, `dead_man_feature`, `dead_man_task_time`, `welcome_msg`, `welcome_img`, `company_email`, `company_phone`, `module_visit`, `module_alert`, `module_break`, `clockout_reminder_interval`, `clockout_reminder_timeout`) VALUES
(1, 10, '0.025', 1, 0, 1, 10, '{\"en\":\"Welcome to E TRAX time attendance bot. Please use start button or \\/start to join our system.\",\"kh\":\"សូមស្វាគមន៍មកកាន់ E TRAX time attendance bot។ សូមប្រើប៊ូតុងចាប់ផ្តើម ឬ \\/start ដើម្បីចាប់តើមចូលប្រើប្រាស់កម្មវិធី។\",\"type\":\"video\"}', 'BAACAgUAAxkBAAJumGQAAUxJa6Y-HYa7ToyHdUwWVYCuugACtggAAiPsAAFUxF9yiytuv3YuBA', 'etrax@gmail.com', '+85587870595', 1, 1, 1, 60, 10);

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

-- --------------------------------------------------------

--
-- Table structure for table `reminder`
--

CREATE TABLE `reminder` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `reply` tinyint(1) NOT NULL DEFAULT 0,
  `response` varchar(30) DEFAULT NULL,
  `reminder_msg` text DEFAULT NULL,
  `reminder_button` text DEFAULT NULL,
  `reminder_num` int(2) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `request_reply_log`
--

CREATE TABLE `request_reply_log` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `user_request` text DEFAULT NULL,
  `bot_reply` text DEFAULT NULL,
  `api_request_url` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `wrong_reply_user_stat` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_messages`
--

CREATE TABLE `scheduled_messages` (
  `id` int(10) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `destination` text NOT NULL,
  `media_type` varchar(20) NOT NULL,
  `media` varchar(200) NOT NULL,
  `runtime` tinyint(1) NOT NULL,
  `last_run` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_messages_time`
--

CREATE TABLE `scheduled_messages_time` (
  `id` int(11) NOT NULL,
  `message_id` int(10) NOT NULL,
  `day` varchar(10) NOT NULL,
  `time` varchar(10) NOT NULL,
  `is_run` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_break`
--

CREATE TABLE `user_break` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `break_day` varchar(10) CHARACTER SET utf8 NOT NULL,
  `break_time` time NOT NULL,
  `location_status` varchar(20) CHARACTER SET utf8 NOT NULL,
  `location_lat` varchar(20) CHARACTER SET utf8 NOT NULL,
  `location_lon` varchar(20) CHARACTER SET utf8 NOT NULL,
  `location_msg_id` int(11) NOT NULL,
  `location_distance` varchar(20) CHARACTER SET utf8 NOT NULL,
  `selfie_msg_id` varchar(100) CHARACTER SET utf8 NOT NULL,
  `break_action` varchar(15) CHARACTER SET utf8 NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `clock_in_time` time DEFAULT NULL,
  `work_start_time` time DEFAULT NULL,
  `clock_in_selfie_msg_id` varchar(100) DEFAULT NULL,
  `is_clock_in` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_daily_tasks`
--

CREATE TABLE `user_daily_tasks` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `task_start` datetime NOT NULL,
  `task_end` datetime NOT NULL,
  `task_send` tinyint(1) NOT NULL DEFAULT 0,
  `task_reply` tinyint(1) NOT NULL DEFAULT 0,
  `task_status` varchar(25) NOT NULL,
  `reply_time` varchar(30) DEFAULT NULL,
  `reply_location` varchar(100) DEFAULT NULL,
  `reply_location_status` varchar(25) DEFAULT NULL,
  `reply_location_distance` varchar(10) DEFAULT NULL,
  `reply_location_msg_id` int(10) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `tg_username` varchar(50) DEFAULT NULL,
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
  `is_step_complete` tinyint(1) NOT NULL DEFAULT 1,
  `lang` enum('en','kh') DEFAULT 'en',
  `trigger_alarm` tinyint(1) NOT NULL DEFAULT 0,
  `jobdesc` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `can_break` tinyint(1) NOT NULL DEFAULT 0,
  `break_step` tinyint(1) NOT NULL DEFAULT 0,
  `can_visit` tinyint(1) NOT NULL DEFAULT 0,
  `visit_alert` tinyint(1) NOT NULL DEFAULT 0,
  `ping_module` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` bigint(20) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_visits`
--

CREATE TABLE `user_visits` (
  `id` int(10) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `visit_day` varchar(10) NOT NULL,
  `visit_time` datetime NOT NULL,
  `visit_lat` varchar(20) NOT NULL,
  `visit_lon` varchar(20) NOT NULL,
  `visit_location_msg_id` int(10) NOT NULL,
  `visit_selfie_msg_id` varchar(100) NOT NULL,
  `visit_notes` text NOT NULL,
  `visit_action` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_working_hour`
--

CREATE TABLE `user_working_hour` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `work_day` varchar(255) NOT NULL,
  `start_time` varchar(8) DEFAULT NULL,
  `end_time` varchar(8) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_admin`
--
ALTER TABLE `bot_admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_cron`
--
ALTER TABLE `bot_cron`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `reminder`
--
ALTER TABLE `reminder`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_reply_log`
--
ALTER TABLE `request_reply_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scheduled_messages_time`
--
ALTER TABLE `scheduled_messages_time`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_break`
--
ALTER TABLE `user_break`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_clock_in_out`
--
ALTER TABLE `user_clock_in_out`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_daily_tasks`
--
ALTER TABLE `user_daily_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_visits`
--
ALTER TABLE `user_visits`
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
-- AUTO_INCREMENT for table `bot_admin`
--
ALTER TABLE `bot_admin`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_cron`
--
ALTER TABLE `bot_cron`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bot_settings`
--
ALTER TABLE `bot_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminder`
--
ALTER TABLE `reminder`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_reply_log`
--
ALTER TABLE `request_reply_log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_messages_time`
--
ALTER TABLE `scheduled_messages_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_break`
--
ALTER TABLE `user_break`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_clock_in_out`
--
ALTER TABLE `user_clock_in_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_daily_tasks`
--
ALTER TABLE `user_daily_tasks`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_visits`
--
ALTER TABLE `user_visits`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_working_hour`
--
ALTER TABLE `user_working_hour`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
