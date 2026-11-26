-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 26, 2026 at 10:54 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u579509174_mrclinicpro`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'booked',
  `reminder_sent_at` datetime DEFAULT NULL,
  `confirmation_sent_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `client_id`, `staff_id`, `service_id`, `location_id`, `start_time`, `end_time`, `status`, `reminder_sent_at`, `confirmation_sent_at`, `notes`, `cancellation_reason`, `created_at`, `updated_at`) VALUES
(268, 88, 59, 3, 62, '2026-08-20 11:15:00', '2026-08-20 12:15:00', 'pending', NULL, NULL, NULL, NULL, '2026-08-19 12:10:04', '2026-08-19 12:10:04'),
(269, 88, 59, 6, 62, '2026-08-19 08:15:00', '2026-08-19 08:45:00', 'completed', NULL, NULL, NULL, NULL, '2026-08-19 12:16:55', '2026-08-19 12:19:10'),
(270, 88, 59, 3, 62, '2026-09-02 10:15:00', '2026-09-02 11:15:00', 'completed', NULL, NULL, NULL, NULL, '2026-09-02 11:10:45', '2026-09-02 11:11:49'),
(271, 88, 59, 3, 62, '2026-09-11 11:00:00', '2026-09-11 12:00:00', 'completed', NULL, '2026-09-11 10:49:36', NULL, NULL, '2026-09-11 10:49:35', '2026-09-11 10:49:48'),
(272, 88, 59, 3, 62, '2026-09-11 12:00:00', '2026-09-11 13:00:00', 'completed', NULL, '2026-09-11 10:54:35', NULL, NULL, '2026-09-11 10:54:34', '2026-09-11 10:54:49'),
(273, 88, 59, 3, 62, '2026-09-15 11:00:00', '2026-09-15 12:00:00', 'no_show', NULL, '2026-09-15 11:33:54', NULL, NULL, '2026-09-15 11:33:51', '2026-09-15 12:00:04'),
(274, 89, 59, 66, 62, '2026-09-15 16:30:00', '2026-09-15 17:15:00', 'no_show', NULL, '2026-09-15 15:49:00', NULL, NULL, '2026-09-15 15:48:58', '2026-09-15 17:15:03'),
(275, 90, 59, 66, 62, '2026-09-15 17:30:00', '2026-09-15 18:15:00', 'no_show', NULL, '2026-09-15 16:13:05', NULL, NULL, '2026-09-15 16:13:02', '2026-09-15 18:15:03'),
(276, 91, 60, 2, 62, '2026-09-15 16:00:00', '2026-09-15 16:30:00', 'no_show', NULL, '2026-09-15 16:16:45', NULL, NULL, '2026-09-15 16:16:43', '2026-09-15 16:30:04'),
(277, 91, 60, 2, 62, '2026-09-15 16:00:00', '2026-09-15 16:30:00', 'completed', NULL, '2026-09-15 20:13:23', NULL, NULL, '2026-09-15 20:13:21', '2026-09-15 20:13:43'),
(278, 89, 60, 2, 62, '2026-09-24 16:30:00', '2026-09-24 17:00:00', 'no_show', '2026-09-23 15:30:06', '2026-09-15 21:35:19', NULL, NULL, '2026-09-15 21:35:17', '2026-09-24 17:00:03'),
(279, 88, 59, 3, 62, '2026-09-22 17:00:00', '2026-09-22 18:00:00', 'completed', NULL, '2026-09-22 10:11:31', NULL, NULL, '2026-09-22 10:11:29', '2026-09-22 10:11:53'),
(280, 88, 59, 3, 62, '2026-09-22 18:00:00', '2026-09-22 19:00:00', 'no_show', NULL, '2026-09-22 10:16:58', NULL, NULL, '2026-09-22 10:16:56', '2026-09-22 10:28:12'),
(281, 88, 60, 1, 62, '2026-09-22 17:00:00', '2026-09-22 17:45:00', 'no_show', NULL, '2026-09-22 10:19:52', NULL, NULL, '2026-09-22 10:19:51', '2026-09-22 17:45:03'),
(282, 91, 60, 2, 62, '2026-09-22 16:00:00', '2026-09-22 16:30:00', 'no_show', NULL, '2026-09-22 20:03:45', NULL, NULL, '2026-09-22 20:03:43', '2026-09-22 20:15:03'),
(283, 92, 59, 3, 62, '2026-09-24 18:00:00', '2026-09-24 19:00:00', 'no_show', '2026-09-23 17:00:06', '2026-09-22 20:09:03', NULL, NULL, '2026-09-22 20:09:01', '2026-09-24 19:00:04'),
(284, 91, 60, 2, 62, '2026-09-24 16:00:00', '2026-09-24 16:30:00', 'no_show', NULL, '2026-09-24 19:53:47', NULL, NULL, '2026-09-24 19:53:43', '2026-09-24 20:00:04'),
(285, 93, 60, 2, 62, '2026-09-24 17:00:00', '2026-09-24 17:30:00', 'no_show', NULL, '2026-09-24 19:59:11', NULL, NULL, '2026-09-24 19:59:10', '2026-09-25 06:16:31'),
(286, 94, 60, 2, 62, '2026-09-24 17:30:00', '2026-09-24 18:00:00', 'no_show', NULL, '2026-09-24 20:02:25', NULL, NULL, '2026-09-24 20:02:23', '2026-09-24 20:15:04'),
(287, 89, 60, 2, 62, '2026-10-01 16:30:00', '2026-10-01 17:00:00', 'booked', '2026-09-30 15:30:05', '2026-09-24 21:53:17', NULL, NULL, '2026-09-24 21:53:15', '2026-09-30 15:30:05'),
(288, 89, 60, 2, 62, '2026-10-08 16:30:00', '2026-10-08 17:00:00', 'booked', '2026-10-07 15:30:09', '2026-09-24 21:53:54', NULL, NULL, '2026-09-24 21:53:52', '2026-10-07 15:30:09'),
(289, 95, 59, 3, 62, '2026-09-26 10:30:00', '2026-09-26 11:30:00', 'booked', '2026-09-25 11:00:05', '2026-09-25 10:51:35', NULL, NULL, '2026-09-25 10:51:33', '2026-09-25 11:00:05'),
(290, 96, 59, 66, 62, '2026-09-25 10:30:00', '2026-09-25 11:15:00', 'completed', NULL, '2026-09-25 10:54:14', NULL, NULL, '2026-09-25 10:54:13', '2026-09-25 10:38:58'),
(291, 97, 59, 3, 62, '2026-10-02 11:30:00', '2026-10-02 12:30:00', 'booked', '2026-10-01 10:30:05', '2026-09-25 12:21:41', NULL, NULL, '2026-09-25 12:21:38', '2026-10-01 10:30:05'),
(292, 98, 59, 3, 62, '2026-10-16 10:00:00', '2026-10-16 11:00:00', 'cancelled', NULL, '2026-10-16 06:56:23', NULL, 'Not Make it', '2026-10-16 06:56:22', '2026-10-16 10:36:47'),
(293, 96, 59, 66, 62, '2026-10-16 10:30:00', '2026-10-16 11:15:00', 'completed', NULL, '2026-10-16 10:42:40', NULL, NULL, '2026-10-16 10:42:39', '2026-10-16 11:33:35'),
(294, 100, 59, 3, 62, '2026-10-16 12:00:00', '2026-10-16 13:00:00', 'completed', NULL, '2026-10-16 12:06:36', NULL, NULL, '2026-10-16 12:06:34', '2026-10-17 12:12:25'),
(295, 101, 59, 3, 62, '2026-10-22 15:00:00', '2026-10-22 16:00:00', 'booked', '2026-10-21 14:00:06', '2026-10-19 11:41:54', NULL, NULL, '2026-10-19 11:41:52', '2026-10-21 14:00:06'),
(297, 88, 59, 3, 62, '2026-10-27 10:00:00', '2026-10-27 11:00:00', 'completed', NULL, '2026-10-27 07:49:29', NULL, NULL, '2026-10-27 07:49:28', '2026-10-27 07:49:58'),
(298, 102, 60, 2, 62, '2026-10-27 16:00:00', '2026-10-27 16:45:00', 'completed', NULL, '2026-10-27 11:45:42', NULL, NULL, '2026-10-27 11:45:41', '2026-10-28 06:40:12'),
(299, 91, 60, 2, 62, '2026-10-27 16:00:00', '2026-10-27 16:45:00', 'booked', NULL, '2026-10-27 11:46:21', NULL, NULL, '2026-10-27 11:46:19', '2026-10-27 11:46:37'),
(300, 103, 60, 69, 62, '2026-10-27 18:00:00', '2026-10-27 18:45:00', 'completed', NULL, '2026-10-27 11:51:03', NULL, NULL, '2026-10-27 11:51:02', '2026-10-28 06:54:20'),
(301, 104, 59, 3, 62, '2026-10-27 18:00:00', '2026-10-27 19:00:00', 'completed', NULL, '2026-10-27 11:56:20', NULL, NULL, '2026-10-27 11:56:19', '2026-10-30 06:18:39'),
(302, 102, 60, 1, 62, '2026-10-27 16:45:00', '2026-10-27 17:30:00', 'completed', NULL, '2026-10-27 16:53:54', NULL, NULL, '2026-10-27 16:53:53', '2026-10-28 06:41:38'),
(303, 91, 60, 2, 62, '2026-10-29 16:00:00', '2026-10-29 16:45:00', 'booked', NULL, '2026-10-29 15:48:43', NULL, NULL, '2026-10-29 15:48:38', '2026-10-29 15:48:43'),
(304, 94, 60, 69, 62, '2026-10-29 17:30:00', '2026-10-29 18:15:00', 'booked', NULL, '2026-10-29 15:49:15', NULL, NULL, '2026-10-29 15:49:14', '2026-10-29 15:49:58'),
(305, 93, 60, 2, 62, '2026-10-29 17:00:00', '2026-10-29 17:45:00', 'booked', NULL, '2026-10-29 15:54:17', NULL, NULL, '2026-10-29 15:54:16', '2026-10-29 15:54:17'),
(306, 103, 60, 69, 62, '2026-10-29 18:00:00', '2026-10-29 18:45:00', 'booked', NULL, '2026-10-29 15:54:48', NULL, NULL, '2026-10-29 15:54:47', '2026-10-29 15:54:48'),
(307, 8, 59, 72, 62, '2026-10-30 09:00:00', '2026-10-30 09:45:00', 'completed', NULL, '2026-10-30 06:22:30', NULL, NULL, '2026-10-30 06:22:28', '2026-10-30 06:22:39'),
(308, 8, 66, 72, 62, '2026-10-30 09:45:00', '2026-10-30 10:30:00', 'completed', NULL, '2026-10-30 06:27:29', NULL, NULL, '2026-10-30 06:27:28', '2026-10-30 06:27:29'),
(309, 106, 59, 3, 62, '2026-10-31 11:00:00', '2026-10-31 12:00:00', 'completed', '2026-10-30 11:00:06', '2026-10-30 10:45:20', NULL, NULL, '2026-10-30 10:45:16', '2026-10-31 13:52:19'),
(310, 107, 59, 3, 62, '2026-10-31 13:00:00', '2026-10-31 14:00:00', 'cancelled', '2026-10-30 12:00:06', '2026-10-30 10:50:14', NULL, 'Not able to Make it', '2026-10-30 10:50:12', '2026-10-31 10:39:47'),
(311, 105, 66, 74, 62, '2026-10-29 20:00:00', '2026-10-29 20:30:00', 'completed', NULL, NULL, NULL, NULL, '2026-10-30 10:57:01', '2026-11-01 15:39:25'),
(312, 108, 59, 3, 62, '2026-10-30 13:00:00', '2026-10-30 14:00:00', 'completed', NULL, '2026-10-30 11:34:19', NULL, NULL, '2026-10-30 11:34:16', '2026-10-31 13:56:23'),
(313, 96, 59, 66, 62, '2026-10-30 10:30:00', '2026-10-30 11:15:00', 'booked', NULL, '2026-10-30 11:42:08', NULL, NULL, '2026-10-30 11:42:07', '2026-10-30 11:42:08'),
(314, 109, 60, 1, 62, '2026-11-03 17:30:00', '2026-11-03 18:15:00', 'booked', '2026-11-02 16:30:05', '2026-10-30 13:31:16', NULL, NULL, '2026-10-30 13:31:14', '2026-11-02 16:30:05'),
(315, 110, 59, 3, 62, '2026-10-31 12:00:00', '2026-10-31 13:00:00', 'completed', NULL, '2026-10-31 13:52:05', NULL, NULL, '2026-10-31 13:52:03', '2026-10-31 13:53:50'),
(316, 111, 60, 1, 62, '2026-11-05 18:00:00', '2026-11-05 18:45:00', 'booked', '2026-11-04 17:00:07', '2026-11-03 16:51:32', NULL, NULL, '2026-11-03 16:51:31', '2026-11-04 17:00:07'),
(317, 109, 60, 2, 62, '2026-11-05 17:30:00', '2026-11-05 18:15:00', 'booked', '2026-11-04 16:30:07', '2026-11-03 19:25:37', NULL, NULL, '2026-11-03 19:25:36', '2026-11-04 16:30:07'),
(318, 112, 60, 1, 62, '2026-11-05 17:00:00', '2026-11-05 17:45:00', 'booked', NULL, NULL, NULL, NULL, '2026-11-04 11:35:40', '2026-11-04 11:35:40'),
(319, 113, 59, 3, 62, '2026-11-14 13:00:00', '2026-11-14 14:00:00', 'booked', '2026-11-13 12:00:07', '2026-11-05 14:26:13', NULL, NULL, '2026-11-05 14:26:11', '2026-11-13 12:00:07'),
(320, 114, 59, 3, 62, '2026-11-05 15:00:00', '2026-11-05 16:00:00', 'booked', NULL, '2026-11-05 14:32:11', NULL, NULL, '2026-11-05 14:32:10', '2026-11-05 14:32:11'),
(321, 109, 59, 66, 62, '2026-11-10 17:00:00', '2026-11-10 17:45:00', 'booked', '2026-11-09 16:00:05', '2026-11-05 18:38:02', NULL, NULL, '2026-11-05 18:38:00', '2026-11-09 16:00:05'),
(322, 115, 66, 74, 62, '2026-10-27 20:00:00', '2026-10-27 20:30:00', 'booked', NULL, '2026-11-10 21:16:13', NULL, NULL, '2026-11-10 21:16:11', '2026-11-10 21:16:13'),
(323, 109, 60, 2, 62, '2026-11-12 17:30:00', '2026-11-12 18:15:00', 'booked', NULL, '2026-11-11 21:54:00', NULL, NULL, '2026-11-11 21:53:59', '2026-11-11 21:54:00'),
(324, 111, 59, 3, 62, '2026-11-14 12:00:00', '2026-11-14 13:00:00', 'booked', '2026-11-13 12:45:19', '2026-11-13 12:30:26', NULL, NULL, '2026-11-13 12:30:21', '2026-11-13 12:45:19'),
(325, 116, 59, 3, 62, '2026-11-19 15:00:00', '2026-11-19 16:00:00', 'booked', '2026-11-18 14:00:07', '2026-11-18 06:45:56', NULL, NULL, '2026-11-18 06:45:54', '2026-11-18 14:00:07'),
(326, 117, 66, 74, 62, '2026-11-15 20:00:00', '2026-11-15 20:30:00', 'completed', NULL, '2026-11-18 15:31:29', NULL, NULL, '2026-11-18 15:31:28', '2026-11-18 15:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `business_settings`
--

CREATE TABLE `business_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(191) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_settings`
--

INSERT INTO `business_settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES
(4, 'business_name', 'mrclinicpro', 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(5, 'business_email', NULL, 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(6, 'business_phone', NULL, 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(7, 'business_address', NULL, 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(8, 'currency', 'CAD', 'defaults', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(9, 'timezone', 'America/Toronto', 'defaults', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(10, 'date_format', 'M j, Y', 'defaults', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(11, 'time_format', 'g:i A', 'defaults', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(12, 'appointment_interval', '30', 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(13, 'default_appointment_status', 'pending', 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47'),
(14, 'invoice_prefix', 'INV', 'general', '2026-10-27 07:56:47', '2026-10-27 07:56:47');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `client_code` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `gender` varchar(191) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `alternate_phone` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `address_line1` varchar(191) DEFAULT NULL,
  `address_line2` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postal_code` varchar(191) DEFAULT NULL,
  `emergency_contact` varchar(191) DEFAULT NULL,
  `emergency_phone` varchar(191) DEFAULT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `client_since` date DEFAULT NULL,
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `last_name`, `name`, `client_code`, `email`, `gender`, `dob`, `phone`, `alternate_phone`, `city`, `address_line1`, `address_line2`, `state`, `country`, `postal_code`, `emergency_contact`, `emergency_phone`, `photo`, `status`, `notes`, `client_since`, `is_vip`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'Alice Johnson', NULL, 'alice@example.com', NULL, NULL, '555-2002', NULL, 'New York', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'Prefers morning appointments.', '2023-01-15', 0, '2026-02-05 02:25:16', '2026-02-21 04:17:32'),
(2, NULL, NULL, 'Bob Williams', NULL, 'bob@example.com', NULL, NULL, '555-2002', NULL, 'Brooklyn', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'Allergic to latex.', '2023-03-20', 0, '2026-02-05 02:25:16', '2026-02-05 02:25:16'),
(3, NULL, NULL, 'Charlie Brown', NULL, 'charlie@example.com', NULL, NULL, '555-2003', NULL, 'Queens', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'New patient.', '2023-05-10', 0, '2026-02-05 02:25:16', '2026-02-05 02:25:16'),
(4, NULL, NULL, 'sanjay', NULL, 'samjay@gmail.com', NULL, NULL, '9865327401', NULL, 'Chennai', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'Client', '2026-02-06', 0, '2026-02-05 23:15:09', '2026-02-05 23:15:09'),
(5, NULL, NULL, 'New', NULL, 'New@gmail.com', NULL, NULL, '9685712340', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, 0, '2026-02-17 00:46:45', '2026-02-17 00:46:45'),
(6, NULL, NULL, 'Alex Jhon', NULL, 'aj@gmail.com', NULL, NULL, '07871901740', NULL, 'Madurai', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'Alex Jhon', '2026-02-17', 0, '2026-02-17 04:53:10', '2026-02-17 04:53:10'),
(7, NULL, NULL, 'test', NULL, 'test@gmail.com', NULL, NULL, '9873216540', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'demo', '2026-01-23', 0, '2026-02-17 22:57:50', '2026-02-17 22:57:50'),
(8, NULL, NULL, 'muruga', NULL, 'murugasundaram12@gmail.com', NULL, NULL, '9825631470', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, 0, '2026-02-18 00:42:52', '2026-08-01 05:17:53'),
(9, NULL, NULL, 'guru', NULL, 'guru@gmail.com', NULL, NULL, '9657483210', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, 0, '2026-03-11 02:09:02', '2026-03-11 02:09:02'),
(10, NULL, NULL, 'John', NULL, 'john@gmail.com', NULL, NULL, '8124637120', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'dsa', '2001-06-14', 0, '2026-05-12 23:55:07', '2026-05-12 23:55:07'),
(70, NULL, NULL, 'udhay', NULL, 'udhaympt@gmail.com', NULL, NULL, '6474085619', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, 0, '2026-08-06 11:06:14', '2026-08-06 11:06:14'),
(88, 'yoga', 'Jothi', 'yoga Jothi', 'CLI-00071', 'yogajothib@gmail.com', 'female', NULL, '4168243778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-08-19', 0, '2026-08-19 12:09:47', '2026-08-19 12:09:47'),
(89, 'Rajendran', 'Pujali Kanneppan', 'Rajendran Pujali Kanneppan', 'CLI-00089', 'pkrajebdran279@gmail.com', 'male', '1982-06-30', '+1 (437) 970-2705', NULL, 'Brampton', '1009', '8 Silver Maple Crt', 'ON', 'Canada', 'L6T 4N6', NULL, NULL, NULL, 'active', NULL, '2026-09-15', 0, '2026-09-15 15:48:13', '2026-09-15 21:36:40'),
(90, 'Suresh', 'Kootha Pandurangan', 'Suresh Kootha Pandurangan', 'CLI-00090', 'kpsureshincanada@gmail.com', 'male', '1985-06-10', '(647) 535-1504', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-15', 0, '2026-09-15 16:12:43', '2026-09-15 16:12:43'),
(91, 'Augustine', 'Gayle 503', 'Augustine Gayle 503', 'CLI-00091', 'dkrehab1@gmail.com', 'female', NULL, '(905) 216-1880', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-15', 0, '2026-09-15 16:16:32', '2026-09-15 16:16:32'),
(92, 'Marie', 'Benjamin', 'Marie Benjamin', 'CLI-00092', 'mbenjamin1971@hotmail.com', 'female', NULL, '(416) 841-9481', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-22', 0, '2026-09-22 20:08:49', '2026-09-22 20:08:49'),
(93, 'Roosevelt', 'Minott 203', 'Roosevelt Minott 203', 'CLI-00093', NULL, 'male', NULL, NULL, NULL, 'Brampton', '30 Peel Centre Drive', NULL, 'ON', NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-24', 0, '2026-09-24 19:58:59', '2026-09-24 19:58:59'),
(94, 'Beatrice', 'Walker 320', 'Beatrice Walker 320', 'CLI-00094', NULL, 'female', NULL, NULL, NULL, 'Brampton', '30 Peel Centre Drive', NULL, 'ON', NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-24', 0, '2026-09-24 20:01:54', '2026-09-24 20:01:54'),
(95, 'Sriram', 'Kesavan', 'Sriram Kesavan', 'CLI-00095', 'sriram.kesavan1@gmail.com', 'male', NULL, '+1 (647) 633-2774', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-25', 0, '2026-09-25 10:51:21', '2026-09-25 10:51:21'),
(96, 'James', 'Edmonds', 'James Edmonds', 'CLI-00096', NULL, 'male', NULL, NULL, NULL, 'Brampton', '30 Peel Centre Drive', NULL, 'ON', NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-25', 0, '2026-09-25 10:54:07', '2026-09-25 10:54:07'),
(97, 'Kemesha', 'Vaz', 'Kemesha Vaz', 'CLI-00097', 'wendy2randy@hotmail.com', 'female', NULL, '+1 (416) 660-2102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-09-25', 0, '2026-09-25 12:21:28', '2026-09-25 12:21:28'),
(98, 'Ambal', 'RAGu', 'Ambal RAGu', 'CLI-00098', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-10-16', 0, '2026-10-16 06:55:43', '2026-10-16 06:55:43'),
(100, 'Harshil', 'Singh', 'Harshil Singh', 'CLI-00099', 'harshilsingh3108@gmail.com', 'female', '2008-03-31', '+1 (416) 648-0980', NULL, 'Brampton', '78 Berkwood Hollow', NULL, 'ON', 'Canada', 'L64 3A5', NULL, NULL, NULL, 'active', NULL, '2026-10-16', 0, '2026-10-16 12:06:26', '2026-10-16 12:06:26'),
(101, 'Stephanie', 'Trinh', 'Stephanie Trinh', 'CLI-00101', 'tstephanie.trinh@gmail.com', 'female', '1992-04-26', '+1 (647) 328-7734', NULL, 'Brampton', '191 Tiller Trail', NULL, 'ON', NULL, 'L6X 4T1', NULL, NULL, NULL, 'active', NULL, '2026-10-19', 0, '2026-10-19 11:41:38', '2026-10-19 11:41:38'),
(102, 'Shanmugapriya', 'Theyvasagayam', 'Shanmugapriya Theyvasagayam', 'CLI-00102', NULL, 'female', '1982-05-10', '+1 (416) 418-6155', NULL, 'Brampton', '128 Earth Star Trail', NULL, 'ON', 'Canada', 'L6R2N8', NULL, NULL, NULL, 'active', NULL, '2026-10-27', 0, '2026-10-27 11:45:32', '2026-10-27 11:45:32'),
(103, 'Hubert', 'Heslop (626)', 'Hubert Heslop (626)', 'CLI-00103', NULL, 'male', '1933-05-29', NULL, NULL, 'Brampton', '30 Peel Centre Drive', NULL, 'ON', 'Canada', 'L6T4G8', NULL, NULL, NULL, 'active', NULL, '2026-10-27', 0, '2026-10-27 11:50:56', '2026-10-27 11:50:56'),
(104, 'Ekansh', 'Mathew', 'Ekansh Mathew', 'CLI-00104', 'ekansh1819@gmail.com', 'male', '1995-02-16', '+1 (647) 214-1819', NULL, 'Peterborough', '415 Florence Dr', NULL, 'ON', 'Canada', 'K9J 6X3', NULL, NULL, NULL, 'active', NULL, '2026-10-27', 0, '2026-10-27 11:56:10', '2026-10-27 11:56:10'),
(105, 'Anisha', 'Prasoon', 'Anisha Prasoon', 'CLI-00105', NULL, 'female', NULL, '+1 (416) 558-1307', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-10-30', 0, '2026-10-30 10:36:04', '2026-10-30 10:36:04'),
(106, 'Leena', 'Sehdev', 'Leena Sehdev', 'CLI-00106', 'leena.sehdev@yahoo.ca', 'female', '1978-07-27', '+1 (647) 524-1777', NULL, 'Brampton', '24 Hanover Rd', 'Unit 808', 'ON', 'Canada', 'L6S 5KB', NULL, NULL, NULL, 'active', NULL, '2026-10-30', 0, '2026-10-30 10:44:20', '2026-10-30 10:44:20'),
(107, 'Amutha', 'Sothirajah', 'Amutha Sothirajah', 'CLI-00107', 'amtha.rajah@gmail.com', 'female', '1978-09-13', '+1 (647) 404-4668', NULL, 'Caledon', '20 Deer Ridge Trails', NULL, 'ON', NULL, 'L7C 3Z6', NULL, NULL, NULL, 'active', NULL, '2026-10-30', 0, '2026-10-30 10:49:04', '2026-10-30 10:49:04'),
(108, 'Benat', 'Mariyanayagam', 'Benat Mariyanayagam', 'CLI-00108', 'benat2002@gmail.com', 'male', '1972-03-12', '+1 (647) 886-2847', NULL, 'Brampton', '34 Haverty Trl', NULL, 'ON', 'Canada', 'L7A 0S2', NULL, NULL, NULL, 'active', NULL, '2026-10-30', 0, '2026-10-30 10:51:25', '2026-10-30 10:51:25'),
(109, 'Shantheswary', 'Piragash', 'Shantheswary Piragash', 'CLI-00109', 'shanthy101@hotmail.com', 'female', '1982-11-26', '+1 (416) 220-6641', NULL, 'Tottenham', '76 Pridham Place', NULL, 'ON', NULL, 'L0G 1WD', NULL, NULL, NULL, 'active', NULL, '2026-10-30', 0, '2026-10-30 13:30:09', '2026-10-30 13:30:09'),
(110, 'Suryakumar', 'Subramani', 'Suryakumar Subramani', 'CLI-00110', 'suryakumarcanada@gmail.com', 'male', '1995-04-20', '+1 (437) 660-6745', NULL, 'Brampton', '10 Lisaa Street', '1407', 'ON', NULL, 'L6T 4N4', NULL, NULL, NULL, 'active', NULL, '2026-10-31', 0, '2026-10-31 13:51:57', '2026-10-31 13:51:57'),
(111, 'Deepak', 'Patel', 'Deepak Patel', 'CLI-00111', 'dpaknalini61@gmail.com', NULL, NULL, '+1 (647) 606-9150', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-11-03', 0, '2026-11-03 16:51:19', '2026-11-03 16:51:19'),
(112, 'Fredricka', 'Jeminson', 'Fredricka Jeminson', 'CLI-00112', NULL, 'female', NULL, '+1 (416) 407-9110', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-11-04', 0, '2026-11-04 11:35:30', '2026-11-04 11:35:30'),
(113, 'Rajeshwar', 'Butte', 'Rajeshwar Butte', 'CLI-00113', 'rajeshwar.butte@gmai.com', 'male', NULL, '+1 (647) 402-0569', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-11-05', 0, '2026-11-05 14:26:04', '2026-11-05 14:26:04'),
(114, 'Nupur', 'Dave', 'Nupur Dave', 'CLI-00114', 'nupurdave0218@gmail.com', 'female', NULL, '+1 (416) 931-9662', NULL, 'Mississauga', '5299 Fallingbrook Drive', NULL, 'ON', NULL, 'L5V 1N8', NULL, NULL, NULL, 'active', NULL, '2026-11-05', 0, '2026-11-05 14:32:05', '2026-11-05 14:32:05'),
(115, 'Srithirumalai', 'Santhanakrishnan', 'Srithirumalai Santhanakrishnan', 'CLI-00115', 'srithirumalai1991@gmail.com', 'male', NULL, '+1 (437) 872-8173', NULL, 'Brampton', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-11-10', 0, '2026-11-10 21:16:04', '2026-11-10 21:16:04'),
(116, 'Simranjot', 'Singh Naroo', 'Simranjot Singh Naroo', 'CLI-00116', 'simranjotnaroo@yahoo.ca', 'male', NULL, '+1 (437) 216-9197', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-11-18', 0, '2026-11-18 06:45:45', '2026-11-18 06:45:45'),
(117, 'Jasdeep Singh', 'Jasdeep Singh', 'Jasdeep Singh Jasdeep Singh', 'CLI-00117', NULL, 'male', '1987-12-21', '+1 (905) 783-2845', NULL, 'Brampton', '140 Brisdale Dr', NULL, 'ON', NULL, 'L7A2H3', NULL, NULL, NULL, 'active', NULL, '2026-11-18', 0, '2026-11-18 15:31:13', '2026-11-18 15:31:13');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `name`, `description`, `fields`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Intake Form', 'New patient intake form', '\"{\\\"questions\\\":[\\\"Name\\\",\\\"Medical History\\\",\\\"Allergies\\\"]}\"', 1, '2026-02-05 02:25:18', '2026-02-05 02:25:18'),
(2, 'COVID-19 Screening', 'Mandatory screening before entry', '\"{\\\"questions\\\":[\\\"Symptoms?\\\",\\\"Contact with positive case?\\\"]}\"', 1, '2026-02-05 02:25:18', '2026-02-05 02:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `form_records`
--

CREATE TABLE `form_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `form_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `submitted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`submitted_data`)),
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `form_records`
--

INSERT INTO `form_records` (`id`, `form_id`, `client_id`, `submitted_data`, `submitted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '\"{\\\"Name\\\":\\\"Alice Johnson\\\",\\\"Medical History\\\":\\\"None\\\",\\\"Allergies\\\":\\\"Peanuts\\\"}\"', '2026-02-05 07:55:18', '2026-02-05 02:25:18', '2026-02-05 02:25:18'),
(2, 1, 1, '\"{\\\"Name\\\":\\\"Alice Johnson\\\",\\\"Medical History\\\":\\\"None\\\",\\\"Allergies\\\":\\\"Peanuts\\\"}\"', '2026-02-05 07:57:04', '2026-02-05 02:27:04', '2026-02-05 02:27:04');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_companies`
--

CREATE TABLE `insurance_companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_companies`
--

INSERT INTO `insurance_companies` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Sunlife', '2026-10-29 16:06:43', '2026-10-29 16:06:43'),
(2, 'Manulife Financial', '2026-10-29 16:07:31', '2026-10-29 16:07:31'),
(3, 'Green Shield', '2026-10-29 16:08:09', '2026-10-29 16:08:09'),
(4, 'Blue Cross', '2026-10-29 16:12:11', '2026-10-29 16:12:11'),
(5, 'Claim Secure', '2026-10-29 16:13:02', '2026-10-29 16:13:02'),
(6, 'Dejardins', '2026-10-29 16:13:38', '2026-10-29 16:13:38'),
(7, 'Empire Life', '2026-10-29 16:16:30', '2026-10-29 16:16:30');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_information`
--

CREATE TABLE `insurance_information` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `insurance_company_id` bigint(20) UNSIGNED NOT NULL,
  `policy_id` varchar(191) DEFAULT NULL,
  `member_id_or_contract_number` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appointment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quotation_id` bigint(20) DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(191) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(191) NOT NULL DEFAULT 'outstanding',
  `issued_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `appointment_id`, `quotation_id`, `client_id`, `staff_id`, `invoice_number`, `subtotal`, `discount_amount`, `total_amount`, `tax_amount`, `paid_amount`, `status`, `issued_date`, `due_date`, `created_at`, `updated_at`) VALUES
(66, 269, NULL, 88, 59, 'INV-20260819-0001', NULL, NULL, 101.70, 0.00, 101.70, 'paid', '2026-08-19', NULL, '2026-08-19 12:22:36', '2026-08-19 12:25:07'),
(67, 270, NULL, 88, 59, 'INV-20260902-0067', NULL, NULL, 101.70, 0.00, 101.70, 'paid', '2026-09-02', '2026-09-16', '2026-09-02 11:11:49', '2026-09-02 11:12:29'),
(68, 271, NULL, 88, 59, 'INV-20260911-0068', NULL, NULL, 101.70, 0.00, 101.70, 'paid', '2026-09-11', '2026-09-25', '2026-09-11 10:49:48', '2026-09-11 10:51:23'),
(69, 272, NULL, 88, 59, 'INV-20260911-0069', NULL, NULL, 101.70, 0.00, 0.00, 'outstanding', '2026-09-11', '2026-09-25', '2026-09-11 10:54:49', '2026-09-11 10:54:49'),
(70, 277, NULL, 91, 60, 'INV-20260915-0070', NULL, NULL, 70.00, 0.00, 0.00, 'outstanding', '2026-09-15', '2026-09-29', '2026-09-15 20:13:43', '2026-09-15 20:13:43'),
(71, 279, NULL, 88, 59, 'INV-20260922-0071', NULL, NULL, 101.70, 0.00, 0.00, 'outstanding', '2026-09-22', '2026-10-06', '2026-09-22 10:11:53', '2026-09-22 10:11:53'),
(72, 290, NULL, 96, 59, 'INV-20260925-0072', NULL, NULL, 79.10, 0.00, 79.10, 'paid', '2026-09-25', '2026-10-09', '2026-09-25 10:38:58', '2026-10-01 06:25:51'),
(73, 294, NULL, 100, 59, 'INV-20261021-0073', 101.70, 0.00, 101.70, 0.00, 0.00, 'outstanding', '2026-10-21', NULL, '2026-10-21 00:34:30', '2026-10-21 00:34:30'),
(74, 293, NULL, 96, 59, 'INV-20261021-0074', 79.10, 0.00, 79.10, 0.00, 0.00, 'outstanding', '2026-10-21', NULL, '2026-10-21 00:35:16', '2026-10-21 00:35:16'),
(76, 297, NULL, 88, 59, 'INV-20261027-0075', 101.70, 0.00, 101.70, 0.00, 101.70, 'paid', '2026-10-27', '2026-11-10', '2026-10-27 07:49:58', '2026-10-27 07:51:16'),
(77, 298, NULL, 102, 60, 'INV-20261028-0077', 70.00, 0.00, 70.00, 0.00, 0.00, 'outstanding', '2026-10-28', '2026-11-11', '2026-10-28 06:40:12', '2026-10-28 06:40:12'),
(78, 302, NULL, 102, 60, 'INV-20261028-0078', 90.00, 0.00, 90.00, 0.00, 90.00, 'paid', '2026-10-28', '2026-11-11', '2026-10-28 06:41:38', '2026-10-28 06:42:18'),
(79, 300, NULL, 103, 60, 'INV-20261028-0079', 50.00, 0.00, 50.00, 0.00, 0.00, 'outstanding', '2026-10-28', '2026-11-11', '2026-10-28 06:54:20', '2026-10-28 06:54:20'),
(80, 301, NULL, 104, 59, 'INV-20261030-0080', 101.70, 0.00, 101.70, 0.00, 0.00, 'outstanding', '2026-10-30', '2026-11-13', '2026-10-30 06:18:39', '2026-10-30 06:18:39'),
(81, 307, NULL, 8, 59, 'INV-20261030-0081', 500.00, 0.00, 500.00, 0.00, 0.00, 'outstanding', '2026-10-30', '2026-11-13', '2026-10-30 06:22:39', '2026-10-30 06:22:39'),
(82, 308, NULL, 8, 66, 'INV-20261030-0082', 500.00, 0.00, 500.00, 0.00, 0.00, 'outstanding', '2026-10-30', '2026-11-13', '2026-10-30 06:27:28', '2026-10-30 06:27:28'),
(83, 309, NULL, 106, 59, 'INV-20261031-0083', 101.70, 0.00, 101.70, 0.00, 101.70, 'paid', '2026-10-31', '2026-11-14', '2026-10-31 13:52:19', '2026-10-31 13:53:11'),
(84, 315, NULL, 110, 59, 'INV-20261031-0084', 101.70, 0.00, 101.70, 0.00, 101.70, 'paid', '2026-10-31', '2026-11-14', '2026-10-31 13:53:50', '2026-10-31 13:54:46'),
(85, 312, NULL, 108, 59, 'INV-20261031-0085', 101.70, 0.00, 101.70, 0.00, 101.70, 'paid', '2026-10-31', '2026-11-14', '2026-10-31 13:56:23', '2026-10-31 13:58:24'),
(86, 311, NULL, 105, 66, 'INV-20261101-0086', 0.00, 0.00, 0.00, 0.00, 0.00, 'outstanding', '2026-11-01', '2026-11-15', '2026-11-01 15:39:25', '2026-11-01 15:39:25'),
(87, 326, NULL, 117, 66, 'INV-20261118-0087', 203.40, 0.00, 203.40, 0.00, 0.00, 'outstanding', '2026-11-18', '2026-12-02', '2026-11-18 15:31:44', '2026-11-18 15:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quotation_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `service_id`, `staff_id`, `quotation_item_id`, `description`, `quantity`, `unit_price`, `discount_amount`, `tax_rate`, `tax_amount`, `line_total`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 73, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-21 00:34:30', '2026-10-21 00:34:30'),
(2, 74, 66, 59, NULL, 'Massage Therapy Treatment', 1.00, 79.10, 0.00, 0.00, 0.00, 79.10, 1, '2026-10-21 00:35:16', '2026-10-21 00:35:16'),
(4, 76, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-27 07:49:58', '2026-10-27 07:49:58'),
(5, 77, 2, 60, NULL, 'Physiotherapy Follow-up', 1.00, 70.00, 0.00, 0.00, 0.00, 70.00, 1, '2026-10-28 06:40:12', '2026-10-28 06:40:12'),
(6, 78, 1, 60, NULL, 'Initial Assessment', 1.00, 90.00, 0.00, 0.00, 0.00, 90.00, 1, '2026-10-28 06:41:38', '2026-10-28 06:41:38'),
(7, 79, 69, 60, NULL, 'Physiotherapy -Senior Discount', 1.00, 50.00, 0.00, 0.00, 0.00, 50.00, 1, '2026-10-28 06:54:20', '2026-10-28 06:54:20'),
(8, 80, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-30 06:18:39', '2026-10-30 06:18:39'),
(9, 81, 72, 59, NULL, 'Orthotics', 1.00, 500.00, 0.00, 0.00, 0.00, 500.00, 1, '2026-10-30 06:22:39', '2026-10-30 06:22:39'),
(10, 82, 72, 66, NULL, 'Orthotics', 1.00, 500.00, 0.00, 0.00, 0.00, 500.00, 1, '2026-10-30 06:27:28', '2026-10-30 06:27:28'),
(11, 83, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-31 13:52:19', '2026-10-31 13:52:19'),
(12, 84, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-31 13:53:50', '2026-10-31 13:53:50'),
(13, 85, 3, 59, NULL, 'Massage Therapy Treatment', 1.00, 101.70, 0.00, 0.00, 0.00, 101.70, 1, '2026-10-31 13:56:23', '2026-10-31 13:56:23'),
(14, 86, 74, 66, NULL, 'Orthopedic Footwear', 1.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-11-01 15:39:25', '2026-11-01 15:39:25'),
(15, 87, 74, 66, NULL, 'Orthopedic Footwear', 1.00, 203.40, 0.00, 0.00, 0.00, 203.40, 1, '2026-11-18 15:31:44', '2026-11-18 15:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `address` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `timezone` varchar(191) NOT NULL DEFAULT 'UTC',
  `color` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `phone`, `email`, `timezone`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(62, 'DK Rehab', '30 Peel Center Drive\r\nBrampton. ON L6T 4G8', '905-216-1880', 'dkrehab1@gmail.com', 'America/Toronto', '#48e5cb', 1, '2026-08-19 11:31:34', '2026-08-21 05:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_02_05_071258_create_locations_table', 1),
(6, '2026_02_05_071259_create_staff_table', 1),
(7, '2026_02_05_071300_create_clients_table', 1),
(8, '2026_02_05_071301_create_service_categories_table', 1),
(9, '2026_02_05_071301_create_services_table', 1),
(10, '2026_02_05_071302_create_packages_table', 1),
(11, '2026_02_05_071303_create_appointments_table', 1),
(12, '2026_02_05_071304_create_invoices_table', 1),
(13, '2026_02_05_071304_create_payment_records_table', 1),
(14, '2026_02_05_071305_create_forms_table', 1),
(15, '2026_02_05_071305_create_payroll_table', 1),
(16, '2026_02_05_071306_create_business_settings_table', 1),
(17, '2026_02_05_071306_create_form_records_table', 1),
(18, '2026_02_05_071307_create_staff_schedules_table', 1),
(19, '2026_02_05_071308_create_package_services_table', 1),
(20, '2026_02_07_000000_add_salary_columns_to_payroll_table', 2),
(21, '2026_02_07_000001_add_salary_to_staff_table', 2),
(22, '2026_02_17_120000_add_working_date_to_staff_schedules_table', 2),
(23, '2026_07_30_000001_create_subscription_plans_table', 3),
(24, '2026_07_30_000002_create_subscriptions_table', 3),
(25, '2026_08_03_052406_add_reminder_sent_at_to_appointments_table', 4),
(26, '2026_08_06_180000_enhance_schedules_and_clients_tables', 5);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `validity_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `validity_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '5 Session Pack', 'Save $50 on 5 sessions.', 450.00, 60, 1, '2026-02-05 02:25:17', '2026-02-05 02:25:17'),
(2, '10 Session Pack', 'Save $150 on 10 sessions.', 850.00, 90, 1, '2026-02-05 02:25:17', '2026-02-05 02:25:17'),
(3, '5 Session Pack', 'Save $50 on 5 sessions.', 450.00, 60, 1, '2026-02-05 02:26:37', '2026-02-05 02:26:37'),
(4, '10 Session Pack', 'Save $150 on 10 sessions.', 850.00, 90, 1, '2026-02-05 02:26:37', '2026-02-05 02:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--

CREATE TABLE `payment_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(191) NOT NULL,
  `primary_method` varchar(191) DEFAULT NULL,
  `secondary_method` varchar(191) DEFAULT NULL,
  `primary_amount` decimal(10,2) DEFAULT NULL,
  `secondary_amount` decimal(10,2) DEFAULT NULL,
  `cash_amount` decimal(10,2) DEFAULT NULL,
  `card_amount` decimal(10,2) DEFAULT NULL,
  `e_transfer_amount` decimal(10,2) DEFAULT NULL,
  `insurance_amount` decimal(10,2) DEFAULT NULL,
  `card_brand` varchar(191) DEFAULT NULL,
  `cardholder_name` varchar(191) DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `transaction_reference` varchar(191) DEFAULT NULL,
  `e_transfer_reference` varchar(191) DEFAULT NULL,
  `sender_name` varchar(191) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `insurance_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `insurance_information_id` bigint(20) UNSIGNED DEFAULT NULL,
  `policy_id` varchar(191) DEFAULT NULL,
  `member_id_or_contract_number` varchar(191) DEFAULT NULL,
  `claim_reference` varchar(191) DEFAULT NULL,
  `amount_submitted` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` date NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_records`
--

INSERT INTO `payment_records` (`id`, `invoice_id`, `amount`, `payment_method`, `primary_method`, `secondary_method`, `primary_amount`, `secondary_amount`, `cash_amount`, `card_amount`, `e_transfer_amount`, `insurance_amount`, `card_brand`, `cardholder_name`, `card_last_four`, `transaction_reference`, `e_transfer_reference`, `sender_name`, `transfer_date`, `insurance_company_id`, `insurance_information_id`, `policy_id`, `member_id_or_contract_number`, `claim_reference`, `amount_submitted`, `notes`, `payment_date`, `transaction_id`, `created_at`, `updated_at`) VALUES
(40, 66, 101.70, 'cash', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-19', NULL, '2026-08-19 12:25:07', '2026-08-19 12:25:07'),
(41, 67, 101.70, 'cash', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02', NULL, '2026-09-02 11:12:29', '2026-09-02 11:12:29'),
(42, 68, 101.70, 'cash', NULL, NULL, NULL, NULL, 101.70, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-11', NULL, '2026-09-11 10:51:23', '2026-09-11 10:51:23'),
(43, 72, 15.82, 'cash', NULL, NULL, NULL, NULL, 15.82, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25', NULL, '2026-09-25 11:01:20', '2026-09-25 11:01:20'),
(44, 72, 63.28, 'insurance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 63.28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-01', NULL, '2026-10-01 06:25:51', '2026-10-01 06:25:51'),
(45, 76, 50.00, 'insurance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-27', NULL, '2026-10-27 07:50:50', '2026-10-27 07:50:50'),
(46, 76, 51.70, 'cash', NULL, NULL, NULL, NULL, 51.70, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-27', NULL, '2026-10-27 07:51:16', '2026-10-27 07:51:16'),
(47, 78, 90.00, 'insurance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 90.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-28', NULL, '2026-10-28 06:42:18', '2026-10-28 06:42:18'),
(48, 83, 91.53, 'insurance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 91.53, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31', NULL, '2026-10-31 13:52:56', '2026-10-31 13:52:56'),
(49, 83, 10.17, 'cash', NULL, NULL, NULL, NULL, 10.17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31', NULL, '2026-10-31 13:53:11', '2026-10-31 13:53:11'),
(50, 84, 91.53, 'insurance', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 91.53, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31', NULL, '2026-10-31 13:54:26', '2026-10-31 13:54:26'),
(51, 84, 10.17, 'cash', NULL, NULL, NULL, NULL, 10.17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31', NULL, '2026-10-31 13:54:46', '2026-10-31 13:54:46'),
(52, 85, 101.70, 'card', NULL, NULL, NULL, NULL, NULL, 101.70, NULL, NULL, 'Other', NULL, '4792', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Interac', '2026-10-31', NULL, '2026-10-31 13:58:24', '2026-10-31 13:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `salary_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_hours` decimal(8,2) NOT NULL,
  `total_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_payout` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_type` varchar(191) NOT NULL DEFAULT 'transfer',
  `notes` text DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `appointment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `issued_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_number`, `client_id`, `staff_id`, `created_by`, `appointment_id`, `status`, `issued_date`, `valid_until`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `notes`, `terms`, `created_at`, `updated_at`) VALUES
(1, 'QUO-20261030-0001', 8, 66, 61, 308, 'draft', '2026-10-30', '2026-11-29', 500.00, 0.00, 0.00, 500.00, 'shoes', NULL, '2026-10-30 06:30:42', '2026-10-30 06:30:42'),
(2, 'QUO-20261101-0002', 105, 66, 61, 311, 'draft', '2026-10-29', '2026-12-01', 203.40, 0.00, 0.00, 203.40, 'Brooks Adrenaline GTS25 /US 8  Women\'s WIDE\r\nOrthopedic Footwear with Stiff Heel Counter ,Arch support, Stiff Shank  Rigid Fore Foot Rocker Bottom sole with Extra depth and width.\r\n****ESTIMATE ONLY****', NULL, '2026-11-01 16:03:52', '2026-11-01 16:06:34'),
(3, 'QUO-20261118-0003', 117, 66, 61, NULL, 'draft', '2026-11-18', '2026-12-18', 237.30, 0.00, 0.00, 237.30, 'Brooks Ghost 18 GTX  /US 10 Men\'s WIDE\r\nOrthopedic Footwear with a Stiff Heel Counter, Arch support, stiff shank, rigid forefoot, and rocker-bottom sole with Extra depth and width.\r\n****ESTIMATE ONLY****', NULL, '2026-11-18 15:45:18', '2026-11-18 15:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `service_id`, `staff_id`, `description`, `quantity`, `unit_price`, `discount_amount`, `tax_rate`, `tax_amount`, `line_total`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 72, 66, 'Orthotics', 1.00, 500.00, 0.00, 0.00, 0.00, 500.00, 1, '2026-10-30 06:30:42', '2026-10-30 06:30:42'),
(3, 2, 74, 66, 'Orthopedic Footwear', 1.00, 203.40, 0.00, 0.00, 0.00, 203.40, 1, '2026-11-01 16:06:34', '2026-11-01 16:06:34'),
(6, 3, 75, 66, 'Orthopedic Footwear', 1.00, 237.30, 0.00, 0.00, 0.00, 237.30, 1, '2026-11-18 15:49:21', '2026-11-18 15:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'in_person',
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `buffer_minutes` int(11) NOT NULL DEFAULT 0,
  `color` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_category_id`, `name`, `description`, `type`, `price`, `duration_minutes`, `buffer_minutes`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Physiotherapy Initial Assessment', 'Comprehensive initial evaluation.', 'in_person', 90.00, 45, 0, '#ff387e', 1, '2026-02-05 02:25:17', '2026-10-30 11:52:40'),
(2, 1, 'Physiotherapy Follow-up', 'Routine follow-up session.', 'in_person', 70.00, 45, 0, '#9238ff', 1, '2026-02-05 02:25:17', '2026-10-30 11:53:06'),
(3, 2, 'Massage Therapy Treatment', 'Intense massage for muscle recovery.', 'in_person', 101.70, 60, 0, '#38ff84', 1, '2026-02-05 02:25:17', '2026-10-30 11:53:24'),
(6, 2, 'Massage Therapy Treatment', 'Services', 'in_person', 62.15, 30, 0, '#6dff38', 1, '2026-02-17 04:54:11', '2026-10-30 11:53:38'),
(66, 2, 'Massage Therapy Treatment', NULL, 'in_person', 79.10, 45, 0, '#deff38', 1, '2026-08-19 11:41:43', '2026-10-30 11:53:54'),
(67, 2, 'Massage Therapy Treatment', NULL, 'in_person', 150.00, 90, 0, '#38fff2', 1, '2026-08-19 11:42:20', '2026-10-30 11:54:37'),
(68, 2, 'Massage Therapy Treatment -Cupping', NULL, 'in_person', 120.00, 60, 0, '#383fff', 1, '2026-08-19 11:43:01', '2026-10-30 11:54:52'),
(69, 1, 'Physiotherapy -Senior Discount', NULL, 'in_person', 50.00, 45, 0, '#9238ff', 1, '2026-09-15 20:27:58', '2026-10-30 11:55:04'),
(70, 8, 'Massage Therapy Treatment -Private', NULL, 'in_person', 80.00, 60, 0, '#ff8838', 1, '2026-10-16 10:47:46', '2026-10-30 11:55:24'),
(71, 1, 'Physiotherapy -Private', 'Physiotherapy -Private', 'in_person', 60.00, 45, 0, '#8838ff', 1, '2026-10-16 10:50:09', '2026-10-30 11:55:52'),
(72, 5, 'Orthotics', 'Custom Made Orthotics', 'in_person', 500.00, 45, 0, '#3874ff', 1, '2026-10-16 10:51:12', '2026-10-30 11:56:16'),
(73, 7, 'Compression Stockings', '20-30mmHG', 'in_person', 150.00, 60, 0, '#3699ff', 1, '2026-10-16 10:53:34', '2026-10-16 10:53:34'),
(74, 6, 'Orthopedic Footwear', 'Brooks Adrenaline GTS25 /US 8  Women\'s WIDE\r\nOrthopedic Footwear with Stiff Heel Counter ,Arch support, Stiff Shank  Rigid Fore Foot Rocker Bottom sole with Extra depth and width.\r\n****ESTIMATE ONLY****', 'in_person', 203.40, 30, 0, '#ff9238', 1, '2026-10-30 06:07:10', '2026-11-01 16:02:03'),
(75, 6, 'Orthopedic Footwear', 'Brooks Ghost 18  /US 10  Men WIDE\r\nOrthopedic Footwear with a Stiff Heel Counter, Arch support, stiff shank, rigid forefoot, and rocker-bottom sole with Extra depth and width.\r\n****ESTIMATE ONLY****', 'in_person', 237.30, 30, 0, '#3699ff', 1, '2026-11-18 15:44:29', '2026-11-18 15:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Physiotherapy', 'Physical therapy treatments.', '2026-02-05 02:25:16', '2026-02-05 02:25:16'),
(2, 'Massage Therapy', 'Therapeutic massage services.', '2026-02-05 02:25:17', '2026-09-14 11:10:13'),
(3, 'Acupuncture', 'Traditional Chinese medicine.', '2026-02-05 02:25:17', '2026-02-05 02:25:17'),
(4, 'Pedorthist Service', 'Orthotic Assessment', '2026-09-14 11:12:04', '2026-09-14 11:15:07'),
(5, 'Orthotic Service', 'Orthotic Dispense', '2026-09-14 11:14:07', '2026-09-14 11:14:07'),
(6, 'Orthotic Footwear', 'Orthotic Footwear', '2026-09-14 11:16:05', '2026-09-14 11:16:05'),
(7, 'Compression Stockings', 'Compression Stockings', '2026-09-25 10:57:16', '2026-09-25 10:57:16'),
(8, 'Massage Therapy Treatment -Private', 'Therapeutic Massage', '2026-10-16 10:46:20', '2026-10-16 10:47:02');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `registration_number` varchar(191) DEFAULT NULL,
  `designation` varchar(191) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `access_level` varchar(191) NOT NULL DEFAULT 'staff',
  `category` varchar(191) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `password` varchar(191) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `location_id`, `name`, `email`, `phone`, `registration_number`, `designation`, `bio`, `color`, `access_level`, `category`, `salary`, `password`, `last_login_at`, `remember_token`, `is_active`, `created_at`, `updated_at`) VALUES
(59, 62, 'Udhayakumar Natarajan RMT', 'udhaympt@gmail.com', NULL, 'X815', 'Massage Therapist', NULL, NULL, 'practitioner', 'Massage Therapy', 0.00, '$2y$12$Elj5TE0LBE.aj7SQ9YrWseQnL/E0geJvezaSR3SWN5AlNW4Yg0K7m', NULL, NULL, 1, '2026-08-19 11:58:18', '2026-10-30 06:33:22'),
(60, 62, 'Vimal Reena Muthuswamy Sukumaran', NULL, NULL, 'Reg No: 19607', 'Physiotherapist', NULL, NULL, 'practitioner', 'Physiotherapy', 0.00, '$2y$10$LNdY4RkmhIE8nUAl44CGMuQcU.x3xlowFTK8H.jfDF3yR8C13GJ4.', NULL, NULL, 1, '2026-08-19 12:05:24', '2026-10-30 11:47:53'),
(61, 62, 'Udhayakumar Natarajan', 'udhayakumarN@gmail.com', NULL, 'X815', 'Massage Therapist', NULL, NULL, 'admin', 'Massage Therapy', NULL, '$2y$12$63O0V7IM9dgak3pMZhpfoOL1qtD4PrYUrvR6wTzt5wJzU4igfGwea', '2026-11-26 05:20:49', 'G2vmGIkPrgbIVMX4X5AheXLo0bReTOAbnmSKQUaOneRAYlSPotjnhHHmfTUm', 1, '2026-08-19 11:58:18', '2026-11-26 05:20:49'),
(62, 62, 'Maria Hadjiapostoli', 'maria-hadji@hotmail.com', NULL, 'J539', 'Massage Therapist', NULL, NULL, 'practitioner', 'Massage Therapy', 0.00, NULL, NULL, NULL, 1, '2026-09-25 10:43:00', '2026-09-25 10:46:33'),
(66, 62, 'Udhayakumar Natarajan C Ped (C)', 'udhaympt@gmail.com', NULL, '25447', 'Certified Pedorthist C Ped (C)', NULL, NULL, 'practitioner', NULL, 0.00, NULL, NULL, NULL, 1, '2026-10-30 06:24:40', '2026-10-30 11:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `staff_categories`
--

CREATE TABLE `staff_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `service_category_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_categories`
--

INSERT INTO `staff_categories` (`id`, `staff_id`, `service_category_id`, `created_at`, `updated_at`) VALUES
(1, 59, 2, '2026-10-02 19:10:14', '2026-10-02 19:10:14'),
(2, 60, 1, '2026-10-02 19:10:14', '2026-10-02 19:10:14'),
(3, 61, 2, '2026-10-02 19:10:14', '2026-10-02 19:10:14'),
(4, 62, 2, '2026-10-02 19:10:14', '2026-10-02 19:10:14'),
(10, 59, 8, NULL, NULL),
(13, 66, 7, NULL, NULL),
(14, 66, 6, NULL, NULL),
(15, 66, 5, NULL, NULL),
(16, 66, 4, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff_schedules`
--

CREATE TABLE `staff_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` varchar(191) NOT NULL,
  `working_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_working` tinyint(1) NOT NULL DEFAULT 1,
  `recurrence_type` varchar(191) NOT NULL DEFAULT 'one_time',
  `recurrence_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurrence_days`)),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `recurrence_group_id` varchar(191) DEFAULT NULL,
  `breaks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breaks`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_schedules`
--

INSERT INTO `staff_schedules` (`id`, `staff_id`, `day_of_week`, `working_date`, `start_time`, `end_time`, `is_working`, `recurrence_type`, `recurrence_days`, `start_date`, `end_date`, `recurrence_group_id`, `breaks`, `created_at`, `updated_at`) VALUES
(171, 60, '4', '2026-08-20', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(172, 60, '2', '2026-08-25', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(173, 60, '4', '2026-08-27', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(174, 60, '2', '2026-09-01', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(175, 60, '4', '2026-09-03', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(176, 60, '2', '2026-09-08', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(177, 60, '4', '2026-09-10', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(178, 60, '2', '2026-09-15', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(179, 60, '4', '2026-09-17', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(180, 60, '2', '2026-09-22', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(181, 60, '4', '2026-09-24', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(182, 60, '2', '2026-09-29', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(183, 60, '4', '2026-10-01', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(184, 60, '2', '2026-10-06', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(185, 60, '4', '2026-10-08', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(186, 60, '2', '2026-10-13', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(187, 60, '4', '2026-10-15', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(188, 60, '2', '2026-10-20', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(189, 60, '4', '2026-10-22', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(190, 60, '2', '2026-10-27', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(191, 60, '4', '2026-10-29', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(192, 60, '2', '2026-11-03', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(193, 60, '4', '2026-11-05', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(194, 60, '2', '2026-11-10', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(195, 60, '4', '2026-11-12', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(196, 60, '2', '2026-11-17', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(197, 60, '4', '2026-11-19', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(198, 60, '2', '2026-11-24', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(199, 60, '4', '2026-11-26', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(200, 60, '2', '2026-12-01', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(201, 60, '4', '2026-12-03', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(202, 60, '2', '2026-12-08', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(203, 60, '4', '2026-12-10', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(204, 60, '2', '2026-12-15', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(205, 60, '4', '2026-12-17', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(206, 60, '2', '2026-12-22', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(207, 60, '4', '2026-12-24', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(208, 60, '2', '2026-12-29', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(209, 60, '4', '2026-12-31', '16:00:00', '20:00:00', 1, 'weekly', '{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'd014a568-e7ea-4476-8920-e07a13fda5fc', NULL, '2026-08-19 12:06:25', '2026-08-19 12:06:25'),
(224, 62, '2', '2026-10-27', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(225, 62, '3', '2026-10-28', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(226, 62, '4', '2026-10-29', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(227, 62, '5', '2026-10-30', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(228, 62, '6', '2026-10-31', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(229, 62, '0', '2026-11-01', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(230, 62, '1', '2026-11-02', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(231, 62, '2', '2026-11-03', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(232, 62, '3', '2026-11-04', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(233, 62, '4', '2026-11-05', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(234, 62, '5', '2026-11-06', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(235, 62, '6', '2026-11-07', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(236, 62, '0', '2026-11-08', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(237, 62, '1', '2026-11-09', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(238, 62, '2', '2026-11-10', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(239, 62, '3', '2026-11-11', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(240, 62, '4', '2026-11-12', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(241, 62, '5', '2026-11-13', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(242, 62, '6', '2026-11-14', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(243, 62, '0', '2026-11-15', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(244, 62, '1', '2026-11-16', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(245, 62, '2', '2026-11-17', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(246, 62, '3', '2026-11-18', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(247, 62, '4', '2026-11-19', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(248, 62, '5', '2026-11-20', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(249, 62, '6', '2026-11-21', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(250, 62, '0', '2026-11-22', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(251, 62, '1', '2026-11-23', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(252, 62, '2', '2026-11-24', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(253, 62, '3', '2026-11-25', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(254, 62, '4', '2026-11-26', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(255, 62, '5', '2026-11-27', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(256, 62, '6', '2026-11-28', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(257, 62, '0', '2026-11-29', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(258, 62, '1', '2026-11-30', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(259, 62, '2', '2026-12-01', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(260, 62, '3', '2026-12-02', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(261, 62, '4', '2026-12-03', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(262, 62, '5', '2026-12-04', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(263, 62, '6', '2026-12-05', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(264, 62, '0', '2026-12-06', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(265, 62, '1', '2026-12-07', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(266, 62, '2', '2026-12-08', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(267, 62, '3', '2026-12-09', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(268, 62, '4', '2026-12-10', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(269, 62, '5', '2026-12-11', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(270, 62, '6', '2026-12-12', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(271, 62, '0', '2026-12-13', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(272, 62, '1', '2026-12-14', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(273, 62, '2', '2026-12-15', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(274, 62, '3', '2026-12-16', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(275, 62, '4', '2026-12-17', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(276, 62, '5', '2026-12-18', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(277, 62, '6', '2026-12-19', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(278, 62, '0', '2026-12-20', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(279, 62, '1', '2026-12-21', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(280, 62, '2', '2026-12-22', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(281, 62, '3', '2026-12-23', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(282, 62, '4', '2026-12-24', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(283, 62, '5', '2026-12-25', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(284, 62, '6', '2026-12-26', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(285, 62, '0', '2026-12-27', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(286, 62, '1', '2026-12-28', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(287, 62, '2', '2026-12-29', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(288, 62, '3', '2026-12-30', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(289, 62, '4', '2026-12-31', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(290, 62, '5', '2027-01-01', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(291, 62, '6', '2027-01-02', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(292, 62, '0', '2027-01-03', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(293, 62, '1', '2027-01-04', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(294, 62, '2', '2027-01-05', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(295, 62, '3', '2027-01-06', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(296, 62, '4', '2027-01-07', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(297, 62, '5', '2027-01-08', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(298, 62, '6', '2027-01-09', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(299, 62, '0', '2027-01-10', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(300, 62, '1', '2027-01-11', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(301, 62, '2', '2027-01-12', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(302, 62, '3', '2027-01-13', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(303, 62, '4', '2027-01-14', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(304, 62, '5', '2027-01-15', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(305, 62, '6', '2027-01-16', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(306, 62, '0', '2027-01-17', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(307, 62, '1', '2027-01-18', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(308, 62, '2', '2027-01-19', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(309, 62, '3', '2027-01-20', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(310, 62, '4', '2027-01-21', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(311, 62, '5', '2027-01-22', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(312, 62, '6', '2027-01-23', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(313, 62, '0', '2027-01-24', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(314, 62, '1', '2027-01-25', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(315, 62, '2', '2027-01-26', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(316, 62, '3', '2027-01-27', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(317, 62, '4', '2027-01-28', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(318, 62, '5', '2027-01-29', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(319, 62, '6', '2027-01-30', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(320, 62, '0', '2027-01-31', '09:00:00', '22:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-27', '2027-01-31', '988851fd-9f1c-4be3-b712-ab7b3df8e482', NULL, '2026-10-27 16:19:36', '2026-10-27 16:19:36'),
(322, 59, '3', '2026-08-19', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(323, 59, '4', '2026-08-20', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(324, 59, '0', '2026-08-23', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(325, 59, '1', '2026-08-24', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(326, 59, '2', '2026-08-25', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(327, 59, '3', '2026-08-26', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(328, 59, '4', '2026-08-27', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(329, 59, '0', '2026-08-30', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(330, 59, '1', '2026-08-31', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(331, 59, '2', '2026-09-01', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(332, 59, '3', '2026-09-02', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(333, 59, '4', '2026-09-03', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:24', '2026-10-30 06:11:24'),
(334, 59, '0', '2026-09-06', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(335, 59, '1', '2026-09-07', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(336, 59, '2', '2026-09-08', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(337, 59, '3', '2026-09-09', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(338, 59, '4', '2026-09-10', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(339, 59, '0', '2026-09-13', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(340, 59, '1', '2026-09-14', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(341, 59, '2', '2026-09-15', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(342, 59, '3', '2026-09-16', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(343, 59, '4', '2026-09-17', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(344, 59, '0', '2026-09-20', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(345, 59, '1', '2026-09-21', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:25', '2026-10-30 06:11:25'),
(346, 59, '2', '2026-09-22', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(347, 59, '3', '2026-09-23', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(348, 59, '4', '2026-09-24', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(349, 59, '0', '2026-09-27', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(350, 59, '1', '2026-09-28', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(351, 59, '2', '2026-09-29', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(352, 59, '3', '2026-09-30', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(353, 59, '4', '2026-10-01', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(354, 59, '0', '2026-10-04', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(355, 59, '1', '2026-10-05', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(356, 59, '2', '2026-10-06', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(357, 59, '3', '2026-10-07', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(358, 59, '4', '2026-10-08', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26');
INSERT INTO `staff_schedules` (`id`, `staff_id`, `day_of_week`, `working_date`, `start_time`, `end_time`, `is_working`, `recurrence_type`, `recurrence_days`, `start_date`, `end_date`, `recurrence_group_id`, `breaks`, `created_at`, `updated_at`) VALUES
(359, 59, '0', '2026-10-11', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(360, 59, '1', '2026-10-12', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(361, 59, '2', '2026-10-13', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(362, 59, '3', '2026-10-14', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(363, 59, '4', '2026-10-15', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(364, 59, '0', '2026-10-18', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(365, 59, '1', '2026-10-19', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(366, 59, '2', '2026-10-20', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(367, 59, '3', '2026-10-21', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(368, 59, '4', '2026-10-22', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:26', '2026-10-30 06:11:26'),
(369, 59, '0', '2026-10-25', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:27', '2026-10-30 06:11:27'),
(370, 59, '1', '2026-10-26', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:27', '2026-10-30 06:11:27'),
(371, 59, '2', '2026-10-27', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:27', '2026-10-30 06:11:27'),
(372, 59, '3', '2026-10-28', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:27', '2026-10-30 06:11:27'),
(373, 59, '4', '2026-10-29', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(374, 59, '0', '2026-11-01', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(375, 59, '1', '2026-11-02', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(376, 59, '2', '2026-11-03', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(377, 59, '3', '2026-11-04', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(378, 59, '4', '2026-11-05', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(379, 59, '0', '2026-11-08', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(380, 59, '1', '2026-11-09', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(381, 59, '2', '2026-11-10', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(382, 59, '3', '2026-11-11', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(383, 59, '4', '2026-11-12', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(384, 59, '0', '2026-11-15', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(385, 59, '1', '2026-11-16', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(386, 59, '2', '2026-11-17', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(387, 59, '3', '2026-11-18', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(388, 59, '4', '2026-11-19', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(389, 59, '0', '2026-11-22', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(390, 59, '1', '2026-11-23', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(391, 59, '2', '2026-11-24', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(392, 59, '3', '2026-11-25', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(393, 59, '4', '2026-11-26', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(394, 59, '0', '2026-11-29', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(395, 59, '1', '2026-11-30', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(396, 59, '2', '2026-12-01', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(397, 59, '3', '2026-12-02', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(398, 59, '4', '2026-12-03', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(399, 59, '0', '2026-12-06', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(400, 59, '1', '2026-12-07', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(401, 59, '2', '2026-12-08', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(402, 59, '3', '2026-12-09', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(403, 59, '4', '2026-12-10', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(404, 59, '0', '2026-12-13', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(405, 59, '1', '2026-12-14', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(406, 59, '2', '2026-12-15', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(407, 59, '3', '2026-12-16', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(408, 59, '4', '2026-12-17', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(409, 59, '0', '2026-12-20', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(410, 59, '1', '2026-12-21', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(411, 59, '2', '2026-12-22', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(412, 59, '3', '2026-12-23', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(413, 59, '4', '2026-12-24', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(414, 59, '0', '2026-12-27', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(415, 59, '1', '2026-12-28', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(416, 59, '2', '2026-12-29', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(417, 59, '3', '2026-12-30', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(418, 59, '4', '2026-12-31', '10:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"0\",\"1\",\"2\",\"3\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', 'ebc32b98-059c-4b05-8ac8-4952df272c6f', NULL, '2026-10-30 06:11:28', '2026-10-30 06:11:28'),
(419, 59, '5', '2026-08-21', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(420, 59, '6', '2026-08-22', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(421, 59, '5', '2026-08-28', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(422, 59, '6', '2026-08-29', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(423, 59, '5', '2026-09-04', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(424, 59, '6', '2026-09-05', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(425, 59, '5', '2026-09-11', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(426, 59, '6', '2026-09-12', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(427, 59, '5', '2026-09-18', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(428, 59, '6', '2026-09-19', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(429, 59, '5', '2026-09-25', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(430, 59, '6', '2026-09-26', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(431, 59, '5', '2026-10-02', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(432, 59, '6', '2026-10-03', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(433, 59, '5', '2026-10-09', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(434, 59, '6', '2026-10-10', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(435, 59, '5', '2026-10-16', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(436, 59, '6', '2026-10-17', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(437, 59, '5', '2026-10-23', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(438, 59, '6', '2026-10-24', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(439, 59, '5', '2026-10-30', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(440, 59, '6', '2026-10-31', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(441, 59, '5', '2026-11-06', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(442, 59, '6', '2026-11-07', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(443, 59, '5', '2026-11-13', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(444, 59, '6', '2026-11-14', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(445, 59, '5', '2026-11-20', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(446, 59, '6', '2026-11-21', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(447, 59, '5', '2026-11-27', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(448, 59, '6', '2026-11-28', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(449, 59, '5', '2026-12-04', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(450, 59, '6', '2026-12-05', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(451, 59, '5', '2026-12-11', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(452, 59, '6', '2026-12-12', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(453, 59, '5', '2026-12-18', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(454, 59, '6', '2026-12-19', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(455, 59, '5', '2026-12-25', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(456, 59, '6', '2026-12-26', '08:00:00', '21:00:00', 1, 'weekly', '{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-08-19', '2026-12-31', '5c6dc2dc-f3f9-48d5-958e-b6537de58a7d', NULL, '2026-10-30 06:14:15', '2026-10-30 06:14:15'),
(584, 66, '4', '2026-10-01', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(585, 66, '5', '2026-10-02', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(586, 66, '6', '2026-10-03', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(587, 66, '0', '2026-10-04', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(588, 66, '1', '2026-10-05', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(589, 66, '2', '2026-10-06', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(590, 66, '3', '2026-10-07', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(591, 66, '4', '2026-10-08', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(592, 66, '5', '2026-10-09', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(593, 66, '6', '2026-10-10', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(594, 66, '0', '2026-10-11', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(595, 66, '1', '2026-10-12', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(596, 66, '2', '2026-10-13', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(597, 66, '3', '2026-10-14', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(598, 66, '4', '2026-10-15', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(599, 66, '5', '2026-10-16', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(600, 66, '6', '2026-10-17', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(601, 66, '0', '2026-10-18', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(602, 66, '1', '2026-10-19', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(603, 66, '2', '2026-10-20', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(604, 66, '3', '2026-10-21', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(605, 66, '4', '2026-10-22', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(606, 66, '5', '2026-10-23', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(607, 66, '6', '2026-10-24', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(608, 66, '0', '2026-10-25', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(609, 66, '1', '2026-10-26', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(610, 66, '2', '2026-10-27', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(611, 66, '3', '2026-10-28', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(612, 66, '4', '2026-10-29', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(613, 66, '5', '2026-10-30', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(614, 66, '6', '2026-10-31', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(615, 66, '0', '2026-11-01', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(616, 66, '1', '2026-11-02', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(617, 66, '2', '2026-11-03', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(618, 66, '3', '2026-11-04', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(619, 66, '4', '2026-11-05', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(620, 66, '5', '2026-11-06', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(621, 66, '6', '2026-11-07', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(622, 66, '0', '2026-11-08', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(623, 66, '1', '2026-11-09', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(624, 66, '2', '2026-11-10', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(625, 66, '3', '2026-11-11', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(626, 66, '4', '2026-11-12', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(627, 66, '5', '2026-11-13', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(628, 66, '6', '2026-11-14', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(629, 66, '0', '2026-11-15', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(630, 66, '1', '2026-11-16', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(631, 66, '2', '2026-11-17', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(632, 66, '3', '2026-11-18', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(633, 66, '4', '2026-11-19', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(634, 66, '5', '2026-11-20', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(635, 66, '6', '2026-11-21', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(636, 66, '0', '2026-11-22', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(637, 66, '1', '2026-11-23', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(638, 66, '2', '2026-11-24', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(639, 66, '3', '2026-11-25', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(640, 66, '4', '2026-11-26', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(641, 66, '5', '2026-11-27', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(642, 66, '6', '2026-11-28', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(643, 66, '0', '2026-11-29', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(644, 66, '1', '2026-11-30', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(645, 66, '2', '2026-12-01', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(646, 66, '3', '2026-12-02', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(647, 66, '4', '2026-12-03', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(648, 66, '5', '2026-12-04', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(649, 66, '6', '2026-12-05', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(650, 66, '0', '2026-12-06', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(651, 66, '1', '2026-12-07', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(652, 66, '2', '2026-12-08', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(653, 66, '3', '2026-12-09', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(654, 66, '4', '2026-12-10', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(655, 66, '5', '2026-12-11', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(656, 66, '6', '2026-12-12', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34');
INSERT INTO `staff_schedules` (`id`, `staff_id`, `day_of_week`, `working_date`, `start_time`, `end_time`, `is_working`, `recurrence_type`, `recurrence_days`, `start_date`, `end_date`, `recurrence_group_id`, `breaks`, `created_at`, `updated_at`) VALUES
(657, 66, '0', '2026-12-13', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(658, 66, '1', '2026-12-14', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(659, 66, '2', '2026-12-15', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(660, 66, '3', '2026-12-16', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(661, 66, '4', '2026-12-17', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(662, 66, '5', '2026-12-18', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(663, 66, '6', '2026-12-19', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(664, 66, '0', '2026-12-20', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(665, 66, '1', '2026-12-21', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(666, 66, '2', '2026-12-22', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(667, 66, '3', '2026-12-23', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(668, 66, '4', '2026-12-24', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(669, 66, '5', '2026-12-25', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(670, 66, '6', '2026-12-26', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(671, 66, '0', '2026-12-27', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(672, 66, '1', '2026-12-28', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(673, 66, '2', '2026-12-29', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(674, 66, '3', '2026-12-30', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34'),
(675, 66, '4', '2026-12-31', '09:00:00', '21:00:00', 1, 'daily', '{\"weekly_days\":null,\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}', '2026-10-01', '2026-12-31', 'c50d6b95-ed3d-4827-acfc-0a14a547ab58', NULL, '2026-10-30 10:52:34', '2026-10-30 10:52:34');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'trial',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(191) NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` varchar(191) NOT NULL DEFAULT 'monthly',
  `staff_limit` int(10) UNSIGNED DEFAULT NULL,
  `location_limit` int(10) UNSIGNED DEFAULT NULL,
  `appointment_limit` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `description`, `price`, `billing_cycle`, `staff_limit`, `location_limit`, `appointment_limit`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Starter', 'Small teams getting started.', 0.00, 'monthly', 3, 1, 100, 1, '2026-08-03 11:18:15', '2026-08-03 11:18:15'),
(2, 'Business', 'Growing service businesses.', 29.00, 'monthly', 15, 5, 1000, 1, '2026-08-03 11:18:15', '2026-08-03 11:18:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@example.com', NULL, '$2y$12$IbyZ8Z8epg/opfv3a.ayQe8OmF4UaetUXIUZeK0CrrvtWjA1XhNwS', NULL, '2026-02-05 02:25:16', '2026-02-05 02:25:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_client_id_foreign` (`client_id`),
  ADD KEY `appointments_staff_id_foreign` (`staff_id`),
  ADD KEY `appointments_service_id_foreign` (`service_id`),
  ADD KEY `appointments_location_id_foreign` (`location_id`);

--
-- Indexes for table `business_settings`
--
ALTER TABLE `business_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_settings_key_unique` (`key`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clients_client_code_unique` (`client_code`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `form_records`
--
ALTER TABLE `form_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_records_form_id_foreign` (`form_id`),
  ADD KEY `form_records_client_id_foreign` (`client_id`);

--
-- Indexes for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insurance_information`
--
ALTER TABLE `insurance_information`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_appointment_id_foreign` (`appointment_id`),
  ADD KEY `invoices_client_id_foreign` (`client_id`),
  ADD KEY `invoices_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_items_invoice_id_sort_order_index` (`invoice_id`,`sort_order`),
  ADD KEY `invoice_items_quotation_item_id_index` (`quotation_item_id`),
  ADD KEY `invoice_items_service_id_index` (`service_id`),
  ADD KEY `invoice_items_staff_id_index` (`staff_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_services_package_id_foreign` (`package_id`),
  ADD KEY `package_services_service_id_foreign` (`service_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_records_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotations_quotation_number_unique` (`quotation_number`),
  ADD KEY `quotations_created_by_foreign` (`created_by`),
  ADD KEY `quotations_appointment_id_foreign` (`appointment_id`),
  ADD KEY `quotations_client_id_status_index` (`client_id`,`status`),
  ADD KEY `quotations_staff_id_status_index` (`staff_id`,`status`),
  ADD KEY `quotations_status_valid_until_index` (`status`,`valid_until`),
  ADD KEY `quotations_issued_date_index` (`issued_date`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_items_quotation_id_sort_order_index` (`quotation_id`,`sort_order`),
  ADD KEY `quotation_items_service_id_index` (`service_id`),
  ADD KEY `quotation_items_staff_id_index` (`staff_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_service_category_id_foreign` (`service_category_id`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_location_id_foreign` (`location_id`);

--
-- Indexes for table `staff_categories`
--
ALTER TABLE `staff_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_categories_staff_id_service_category_id_unique` (`staff_id`,`service_category_id`),
  ADD KEY `staff_categories_service_category_id_foreign` (`service_category_id`);

--
-- Indexes for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_schedules_staff_working_date_idx` (`staff_id`,`working_date`),
  ADD KEY `staff_schedules_group_idx` (`recurrence_group_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_staff_id_foreign` (`staff_id`),
  ADD KEY `subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;

--
-- AUTO_INCREMENT for table `business_settings`
--
ALTER TABLE `business_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `form_records`
--
ALTER TABLE `form_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `insurance_information`
--
ALTER TABLE `insurance_information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `package_services`
--
ALTER TABLE `package_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `staff_categories`
--
ALTER TABLE `staff_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=676;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_records`
--
ALTER TABLE `form_records`
  ADD CONSTRAINT `form_records_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form_records_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `invoices_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_quotation_item_id_foreign` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_items_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_services`
--
ALTER TABLE `package_services`
  ADD CONSTRAINT `package_services_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_categories`
--
ALTER TABLE `staff_categories`
  ADD CONSTRAINT `staff_categories_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_categories_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD CONSTRAINT `staff_schedules_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
