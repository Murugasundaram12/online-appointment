-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: management_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'booked',
  `reminder_sent_at` datetime DEFAULT NULL,
  `confirmation_sent_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_client_id_foreign` (`client_id`),
  KEY `appointments_staff_id_foreign` (`staff_id`),
  KEY `appointments_service_id_foreign` (`service_id`),
  KEY `appointments_location_id_foreign` (`location_id`),
  CONSTRAINT `appointments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (268,88,59,3,62,'2026-08-20 11:15:00','2026-08-20 12:15:00','pending',NULL,NULL,NULL,NULL,'2026-08-19 12:10:04','2026-08-19 12:10:04'),(269,88,59,6,62,'2026-08-19 08:15:00','2026-08-19 08:45:00','completed',NULL,NULL,NULL,NULL,'2026-08-19 12:16:55','2026-08-19 12:19:10'),(270,88,59,3,62,'2026-09-02 10:15:00','2026-09-02 11:15:00','completed',NULL,NULL,NULL,NULL,'2026-09-02 11:10:45','2026-09-02 11:11:49'),(271,88,59,3,62,'2026-09-11 11:00:00','2026-09-11 12:00:00','completed',NULL,'2026-09-11 10:49:36',NULL,NULL,'2026-09-11 10:49:35','2026-09-11 10:49:48'),(272,88,59,3,62,'2026-09-11 12:00:00','2026-09-11 13:00:00','completed',NULL,'2026-09-11 10:54:35',NULL,NULL,'2026-09-11 10:54:34','2026-09-11 10:54:49'),(273,88,59,3,62,'2026-09-15 11:00:00','2026-09-15 12:00:00','no_show',NULL,'2026-09-15 11:33:54',NULL,NULL,'2026-09-15 11:33:51','2026-09-15 12:00:04'),(274,89,59,66,62,'2026-09-15 16:30:00','2026-09-15 17:15:00','no_show',NULL,'2026-09-15 15:49:00',NULL,NULL,'2026-09-15 15:48:58','2026-09-15 17:15:03'),(275,90,59,66,62,'2026-09-15 17:30:00','2026-09-15 18:15:00','no_show',NULL,'2026-09-15 16:13:05',NULL,NULL,'2026-09-15 16:13:02','2026-09-15 18:15:03'),(276,91,60,2,62,'2026-09-15 16:00:00','2026-09-15 16:30:00','no_show',NULL,'2026-09-15 16:16:45',NULL,NULL,'2026-09-15 16:16:43','2026-09-15 16:30:04'),(277,91,60,2,62,'2026-09-15 16:00:00','2026-09-15 16:30:00','completed',NULL,'2026-09-15 20:13:23',NULL,NULL,'2026-09-15 20:13:21','2026-09-15 20:13:43'),(278,89,60,2,62,'2026-09-24 16:30:00','2026-09-24 17:00:00','no_show','2026-09-23 15:30:06','2026-09-15 21:35:19',NULL,NULL,'2026-09-15 21:35:17','2026-09-24 17:00:03'),(279,88,59,3,62,'2026-09-22 17:00:00','2026-09-22 18:00:00','completed',NULL,'2026-09-22 10:11:31',NULL,NULL,'2026-09-22 10:11:29','2026-09-22 10:11:53'),(280,88,59,3,62,'2026-09-22 18:00:00','2026-09-22 19:00:00','no_show',NULL,'2026-09-22 10:16:58',NULL,NULL,'2026-09-22 10:16:56','2026-09-22 10:28:12'),(281,88,60,1,62,'2026-09-22 17:00:00','2026-09-22 17:45:00','no_show',NULL,'2026-09-22 10:19:52',NULL,NULL,'2026-09-22 10:19:51','2026-09-22 17:45:03'),(282,91,60,2,62,'2026-09-22 16:00:00','2026-09-22 16:30:00','no_show',NULL,'2026-09-22 20:03:45',NULL,NULL,'2026-09-22 20:03:43','2026-09-22 20:15:03'),(283,92,59,3,62,'2026-09-24 18:00:00','2026-09-24 19:00:00','no_show','2026-09-23 17:00:06','2026-09-22 20:09:03',NULL,NULL,'2026-09-22 20:09:01','2026-09-24 19:00:04'),(284,91,60,2,62,'2026-09-24 16:00:00','2026-09-24 16:30:00','no_show',NULL,'2026-09-24 19:53:47',NULL,NULL,'2026-09-24 19:53:43','2026-09-24 20:00:04'),(285,93,60,2,62,'2026-09-24 17:00:00','2026-09-24 17:30:00','no_show',NULL,'2026-09-24 19:59:11',NULL,NULL,'2026-09-24 19:59:10','2026-09-25 06:16:31'),(286,94,60,2,62,'2026-09-24 17:30:00','2026-09-24 18:00:00','no_show',NULL,'2026-09-24 20:02:25',NULL,NULL,'2026-09-24 20:02:23','2026-09-24 20:15:04'),(287,89,60,2,62,'2026-10-01 16:30:00','2026-10-01 17:00:00','booked','2026-09-30 15:30:05','2026-09-24 21:53:17',NULL,NULL,'2026-09-24 21:53:15','2026-09-30 15:30:05'),(288,89,60,2,62,'2026-10-08 16:30:00','2026-10-08 17:00:00','booked','2026-10-07 15:30:09','2026-09-24 21:53:54',NULL,NULL,'2026-09-24 21:53:52','2026-10-07 15:30:09'),(289,95,59,3,62,'2026-09-26 10:30:00','2026-09-26 11:30:00','booked','2026-09-25 11:00:05','2026-09-25 10:51:35',NULL,NULL,'2026-09-25 10:51:33','2026-09-25 11:00:05'),(290,96,59,66,62,'2026-09-25 10:30:00','2026-09-25 11:15:00','completed',NULL,'2026-09-25 10:54:14',NULL,NULL,'2026-09-25 10:54:13','2026-09-25 10:38:58'),(291,97,59,3,62,'2026-10-02 11:30:00','2026-10-02 12:30:00','booked','2026-10-01 10:30:05','2026-09-25 12:21:41',NULL,NULL,'2026-09-25 12:21:38','2026-10-01 10:30:05'),(292,98,59,3,62,'2026-10-16 10:00:00','2026-10-16 11:00:00','cancelled',NULL,'2026-10-16 06:56:23',NULL,'Not Make it','2026-10-16 06:56:22','2026-10-16 10:36:47'),(293,96,59,66,62,'2026-10-16 10:30:00','2026-10-16 11:15:00','completed',NULL,'2026-10-16 10:42:40',NULL,NULL,'2026-10-16 10:42:39','2026-10-16 11:33:35'),(294,100,59,3,62,'2026-10-16 12:00:00','2026-10-16 13:00:00','completed',NULL,'2026-10-16 12:06:36',NULL,NULL,'2026-10-16 12:06:34','2026-10-17 12:12:25'),(295,101,59,3,62,'2026-10-22 15:00:00','2026-10-22 16:00:00','booked',NULL,'2026-10-19 11:41:54',NULL,NULL,'2026-10-19 11:41:52','2026-10-19 11:41:54');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_settings`
--

DROP TABLE IF EXISTS `business_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(191) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_settings`
--

LOCK TABLES `business_settings` WRITE;
/*!40000 ALTER TABLE `business_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_client_code_unique` (`client_code`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,NULL,NULL,'Alice Johnson',NULL,'alice@example.com',NULL,NULL,'555-2002',NULL,'New York',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','Prefers morning appointments.','2023-01-15',0,'2026-02-05 02:25:16','2026-02-21 04:17:32'),(2,NULL,NULL,'Bob Williams',NULL,'bob@example.com',NULL,NULL,'555-2002',NULL,'Brooklyn',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','Allergic to latex.','2023-03-20',0,'2026-02-05 02:25:16','2026-02-05 02:25:16'),(3,NULL,NULL,'Charlie Brown',NULL,'charlie@example.com',NULL,NULL,'555-2003',NULL,'Queens',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','New patient.','2023-05-10',0,'2026-02-05 02:25:16','2026-02-05 02:25:16'),(4,NULL,NULL,'sanjay',NULL,'samjay@gmail.com',NULL,NULL,'9865327401',NULL,'Chennai',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','Client','2026-02-06',0,'2026-02-05 23:15:09','2026-02-05 23:15:09'),(5,NULL,NULL,'New',NULL,'New@gmail.com',NULL,NULL,'9685712340',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,0,'2026-02-17 00:46:45','2026-02-17 00:46:45'),(6,NULL,NULL,'Alex Jhon',NULL,'aj@gmail.com',NULL,NULL,'07871901740',NULL,'Madurai',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','Alex Jhon','2026-02-17',0,'2026-02-17 04:53:10','2026-02-17 04:53:10'),(7,NULL,NULL,'test',NULL,'test@gmail.com',NULL,NULL,'9873216540',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','demo','2026-01-23',0,'2026-02-17 22:57:50','2026-02-17 22:57:50'),(8,NULL,NULL,'muruga',NULL,'murugasundaram12@gmail.com',NULL,NULL,'9825631470',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,0,'2026-02-18 00:42:52','2026-08-01 05:17:53'),(9,NULL,NULL,'guru',NULL,'guru@gmail.com',NULL,NULL,'9657483210',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,0,'2026-03-11 02:09:02','2026-03-11 02:09:02'),(10,NULL,NULL,'John',NULL,'john@gmail.com',NULL,NULL,'8124637120',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','dsa','2001-06-14',0,'2026-05-12 23:55:07','2026-05-12 23:55:07'),(70,NULL,NULL,'udhay',NULL,'udhaympt@gmail.com',NULL,NULL,'6474085619',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,0,'2026-08-06 11:06:14','2026-08-06 11:06:14'),(88,'yoga','Jothi','yoga Jothi','CLI-00071','yogajothib@gmail.com','female',NULL,'4168243778',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-08-19',0,'2026-08-19 12:09:47','2026-08-19 12:09:47'),(89,'Rajendran','Pujali Kanneppan','Rajendran Pujali Kanneppan','CLI-00089','pkrajebdran279@gmail.com','male','1982-06-30','+1 (437) 970-2705',NULL,'Brampton','1009','8 Silver Maple Crt','ON','Canada','L6T 4N6',NULL,NULL,NULL,'active',NULL,'2026-09-15',0,'2026-09-15 15:48:13','2026-09-15 21:36:40'),(90,'Suresh','Kootha Pandurangan','Suresh Kootha Pandurangan','CLI-00090','kpsureshincanada@gmail.com','male','1985-06-10','(647) 535-1504',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-15',0,'2026-09-15 16:12:43','2026-09-15 16:12:43'),(91,'Augustine','Gayle 503','Augustine Gayle 503','CLI-00091','dkrehab1@gmail.com','female',NULL,'(905) 216-1880',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-15',0,'2026-09-15 16:16:32','2026-09-15 16:16:32'),(92,'Marie','Benjamin','Marie Benjamin','CLI-00092','mbenjamin1971@hotmail.com','female',NULL,'(416) 841-9481',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-22',0,'2026-09-22 20:08:49','2026-09-22 20:08:49'),(93,'Roosevelt','Minott 203','Roosevelt Minott 203','CLI-00093',NULL,'male',NULL,NULL,NULL,'Brampton','30 Peel Centre Drive',NULL,'ON',NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-24',0,'2026-09-24 19:58:59','2026-09-24 19:58:59'),(94,'Beatrice','Walker 320','Beatrice Walker 320','CLI-00094',NULL,'female',NULL,NULL,NULL,'Brampton','30 Peel Centre Drive',NULL,'ON',NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-24',0,'2026-09-24 20:01:54','2026-09-24 20:01:54'),(95,'Sriram','Kesavan','Sriram Kesavan','CLI-00095','sriram.kesavan1@gmail.com','male',NULL,'+1 (647) 633-2774',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-25',0,'2026-09-25 10:51:21','2026-09-25 10:51:21'),(96,'James','Edmonds','James Edmonds','CLI-00096',NULL,'male',NULL,NULL,NULL,'Brampton','30 Peel Centre Drive',NULL,'ON',NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-25',0,'2026-09-25 10:54:07','2026-09-25 10:54:07'),(97,'Kemesha','Vaz','Kemesha Vaz','CLI-00097','wendy2randy@hotmail.com','female',NULL,'+1 (416) 660-2102',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-25',0,'2026-09-25 12:21:28','2026-09-25 12:21:28'),(98,'Ambal','RAGu','Ambal RAGu','CLI-00098',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-10-16',0,'2026-10-16 06:55:43','2026-10-16 06:55:43'),(100,'Harshil','Singh','Harshil Singh','CLI-00099','harshilsingh3108@gmail.com','female','2008-03-31','+1 (416) 648-0980',NULL,'Brampton','78 Berkwood Hollow',NULL,'ON','Canada','L64 3A5',NULL,NULL,NULL,'active',NULL,'2026-10-16',0,'2026-10-16 12:06:26','2026-10-16 12:06:26'),(101,'Stephanie','Trinh','Stephanie Trinh','CLI-00101','tstephanie.trinh@gmail.com','female','1992-04-26','+1 (647) 328-7734',NULL,'Brampton','191 Tiller Trail',NULL,'ON',NULL,'L6X 4T1',NULL,NULL,NULL,'active',NULL,'2026-10-19',0,'2026-10-19 11:41:38','2026-10-19 11:41:38');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_records`
--

DROP TABLE IF EXISTS `form_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `submitted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`submitted_data`)),
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_records_form_id_foreign` (`form_id`),
  KEY `form_records_client_id_foreign` (`client_id`),
  CONSTRAINT `form_records_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_records_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_records`
--

LOCK TABLES `form_records` WRITE;
/*!40000 ALTER TABLE `form_records` DISABLE KEYS */;
INSERT INTO `form_records` VALUES (1,1,1,'\"{\\\"Name\\\":\\\"Alice Johnson\\\",\\\"Medical History\\\":\\\"None\\\",\\\"Allergies\\\":\\\"Peanuts\\\"}\"','2026-02-05 07:55:18','2026-02-05 02:25:18','2026-02-05 02:25:18'),(2,1,1,'\"{\\\"Name\\\":\\\"Alice Johnson\\\",\\\"Medical History\\\":\\\"None\\\",\\\"Allergies\\\":\\\"Peanuts\\\"}\"','2026-02-05 07:57:04','2026-02-05 02:27:04','2026-02-05 02:27:04');
/*!40000 ALTER TABLE `form_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (1,'Intake Form','New patient intake form','\"{\\\"questions\\\":[\\\"Name\\\",\\\"Medical History\\\",\\\"Allergies\\\"]}\"',1,'2026-02-05 02:25:18','2026-02-05 02:25:18'),(2,'COVID-19 Screening','Mandatory screening before entry','\"{\\\"questions\\\":[\\\"Symptoms?\\\",\\\"Contact with positive case?\\\"]}\"',1,'2026-02-05 02:25:18','2026-02-05 02:25:18');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_companies`
--

DROP TABLE IF EXISTS `insurance_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_companies`
--

LOCK TABLES `insurance_companies` WRITE;
/*!40000 ALTER TABLE `insurance_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_information`
--

DROP TABLE IF EXISTS `insurance_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_information` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `insurance_company_id` bigint(20) unsigned NOT NULL,
  `policy_id` varchar(191) DEFAULT NULL,
  `member_id_or_contract_number` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_information`
--

LOCK TABLES `insurance_information` WRITE;
/*!40000 ALTER TABLE `insurance_information` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `quotation_item_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_sort_order_index` (`invoice_id`,`sort_order`),
  KEY `invoice_items_quotation_item_id_index` (`quotation_item_id`),
  KEY `invoice_items_service_id_index` (`service_id`),
  KEY `invoice_items_staff_id_index` (`staff_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_quotation_item_id_foreign` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(191) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(191) NOT NULL DEFAULT 'outstanding',
  `issued_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_appointment_id_foreign` (`appointment_id`),
  KEY `invoices_client_id_foreign` (`client_id`),
  KEY `invoices_staff_id_foreign` (`staff_id`),
  CONSTRAINT `invoices_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `invoices_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (66,269,88,59,'INV-20260819-0001',101.70,101.70,'paid','2026-08-19',NULL,'2026-08-19 12:22:36','2026-08-19 12:25:07'),(67,270,88,59,'INV-20260902-0067',101.70,101.70,'paid','2026-09-02','2026-09-16','2026-09-02 11:11:49','2026-09-02 11:12:29'),(68,271,88,59,'INV-20260911-0068',101.70,101.70,'paid','2026-09-11','2026-09-25','2026-09-11 10:49:48','2026-09-11 10:51:23'),(69,272,88,59,'INV-20260911-0069',101.70,0.00,'outstanding','2026-09-11','2026-09-25','2026-09-11 10:54:49','2026-09-11 10:54:49'),(70,277,91,60,'INV-20260915-0070',70.00,0.00,'outstanding','2026-09-15','2026-09-29','2026-09-15 20:13:43','2026-09-15 20:13:43'),(71,279,88,59,'INV-20260922-0071',101.70,0.00,'outstanding','2026-09-22','2026-10-06','2026-09-22 10:11:53','2026-09-22 10:11:53'),(72,290,96,59,'INV-20260925-0072',79.10,79.10,'paid','2026-09-25','2026-10-09','2026-09-25 10:38:58','2026-10-01 06:25:51');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `address` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `timezone` varchar(191) NOT NULL DEFAULT 'UTC',
  `color` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (62,'DK Rehab','30 Peel Center Drive\r\nBrampton. ON L6T 4G8','905-216-1880','dkrehab1@gmail.com','America/Toronto','#48e5cb',1,'2026-08-19 11:31:34','2026-08-21 05:21:15');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2026_02_05_071258_create_locations_table',1),(6,'2026_02_05_071259_create_staff_table',1),(7,'2026_02_05_071300_create_clients_table',1),(8,'2026_02_05_071301_create_service_categories_table',1),(9,'2026_02_05_071301_create_services_table',1),(10,'2026_02_05_071302_create_packages_table',1),(11,'2026_02_05_071303_create_appointments_table',1),(12,'2026_02_05_071304_create_invoices_table',1),(13,'2026_02_05_071304_create_payment_records_table',1),(14,'2026_02_05_071305_create_forms_table',1),(15,'2026_02_05_071305_create_payroll_table',1),(16,'2026_02_05_071306_create_business_settings_table',1),(17,'2026_02_05_071306_create_form_records_table',1),(18,'2026_02_05_071307_create_staff_schedules_table',1),(19,'2026_02_05_071308_create_package_services_table',1),(20,'2026_02_07_000000_add_salary_columns_to_payroll_table',2),(21,'2026_02_07_000001_add_salary_to_staff_table',2),(22,'2026_02_17_120000_add_working_date_to_staff_schedules_table',2),(23,'2026_07_30_000001_create_subscription_plans_table',3),(24,'2026_07_30_000002_create_subscriptions_table',3),(25,'2026_08_03_052406_add_reminder_sent_at_to_appointments_table',4),(26,'2026_08_06_180000_enhance_schedules_and_clients_tables',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_services`
--

DROP TABLE IF EXISTS `package_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_services_package_id_foreign` (`package_id`),
  KEY `package_services_service_id_foreign` (`service_id`),
  CONSTRAINT `package_services_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_services`
--

LOCK TABLES `package_services` WRITE;
/*!40000 ALTER TABLE `package_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `package_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `validity_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'5 Session Pack','Save $50 on 5 sessions.',450.00,60,1,'2026-02-05 02:25:17','2026-02-05 02:25:17'),(2,'10 Session Pack','Save $150 on 10 sessions.',850.00,90,1,'2026-02-05 02:25:17','2026-02-05 02:25:17'),(3,'5 Session Pack','Save $50 on 5 sessions.',450.00,60,1,'2026-02-05 02:26:37','2026-02-05 02:26:37'),(4,'10 Session Pack','Save $150 on 10 sessions.',850.00,90,1,'2026-02-05 02:26:37','2026-02-05 02:26:37');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_records`
--

DROP TABLE IF EXISTS `payment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
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
  `insurance_company_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_information_id` bigint(20) unsigned DEFAULT NULL,
  `policy_id` varchar(191) DEFAULT NULL,
  `member_id_or_contract_number` varchar(191) DEFAULT NULL,
  `claim_reference` varchar(191) DEFAULT NULL,
  `amount_submitted` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` date NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_records_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_records_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_records`
--

LOCK TABLES `payment_records` WRITE;
/*!40000 ALTER TABLE `payment_records` DISABLE KEYS */;
INSERT INTO `payment_records` VALUES (40,66,101.70,'cash',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-19',NULL,'2026-08-19 12:25:07','2026-08-19 12:25:07'),(41,67,101.70,'cash',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-02',NULL,'2026-09-02 11:12:29','2026-09-02 11:12:29'),(42,68,101.70,'cash',NULL,NULL,NULL,NULL,101.70,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-11',NULL,'2026-09-11 10:51:23','2026-09-11 10:51:23'),(43,72,15.82,'cash',NULL,NULL,NULL,NULL,15.82,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-25',NULL,'2026-09-25 11:01:20','2026-09-25 11:01:20'),(44,72,63.28,'insurance',NULL,NULL,NULL,NULL,NULL,NULL,NULL,63.28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-01',NULL,'2026-10-01 06:25:51','2026-10-01 06:25:51');
/*!40000 ALTER TABLE `payment_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_staff_id_foreign` (`staff_id`),
  CONSTRAINT `payroll_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_items_quotation_id_sort_order_index` (`quotation_id`,`sort_order`),
  KEY `quotation_items_service_id_index` (`service_id`),
  KEY `quotation_items_staff_id_index` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(50) NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_quotation_number_unique` (`quotation_number`),
  KEY `quotations_created_by_foreign` (`created_by`),
  KEY `quotations_appointment_id_foreign` (`appointment_id`),
  KEY `quotations_client_id_status_index` (`client_id`,`status`),
  KEY `quotations_staff_id_status_index` (`staff_id`,`status`),
  KEY `quotations_status_valid_until_index` (`status`,`valid_until`),
  KEY `quotations_issued_date_index` (`issued_date`),
  CONSTRAINT `quotations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
INSERT INTO `service_categories` VALUES (1,'Physiotherapy','Physical therapy treatments.','2026-02-05 02:25:16','2026-02-05 02:25:16'),(2,'Massage Therapy','Therapeutic massage services.','2026-02-05 02:25:17','2026-09-14 11:10:13'),(3,'Acupuncture','Traditional Chinese medicine.','2026-02-05 02:25:17','2026-02-05 02:25:17'),(4,'Pedorthist Service','Orthotic Assessment','2026-09-14 11:12:04','2026-09-14 11:15:07'),(5,'Orthotic Service','Orthotic Dispense','2026-09-14 11:14:07','2026-09-14 11:14:07'),(6,'Orthotic Footwear','Orthotic Footwear','2026-09-14 11:16:05','2026-09-14 11:16:05'),(7,'Compression Stockings','Compression Stockings','2026-09-25 10:57:16','2026-09-25 10:57:16'),(8,'Massage Therapy Treatment -Private','Therapeutic Massage','2026-10-16 10:46:20','2026-10-16 10:47:02');
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'in_person',
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `buffer_minutes` int(11) NOT NULL DEFAULT 0,
  `color` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_service_category_id_foreign` (`service_category_id`),
  CONSTRAINT `services_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,'Initial Assessment','Comprehensive initial evaluation.','in_person',90.00,45,0,NULL,1,'2026-02-05 02:25:17','2026-08-19 11:39:20'),(2,1,'Physiotherapy Follow-up','Routine follow-up session.','in_person',70.00,45,0,'#3699ff',1,'2026-02-05 02:25:17','2026-10-16 10:44:10'),(3,2,'Massage Therapy Treatment','Intense massage for muscle recovery.','in_person',101.70,60,0,NULL,1,'2026-02-05 02:25:17','2026-08-19 11:40:07'),(6,2,'Massage Therapy Treatment','Services','in_person',62.15,30,0,NULL,1,'2026-02-17 04:54:11','2026-09-15 15:38:15'),(66,2,'Massage Therapy Treatment',NULL,'in_person',79.10,45,0,NULL,1,'2026-08-19 11:41:43','2026-09-15 15:38:29'),(67,2,'Massage Therapy Treatment',NULL,'in_person',150.00,90,0,NULL,1,'2026-08-19 11:42:20','2026-08-19 11:42:20'),(68,2,'Massage Therapy Treatment -Cupping',NULL,'in_person',120.00,60,0,NULL,1,'2026-08-19 11:43:01','2026-08-19 11:43:01'),(69,1,'Physiotherapy -Senior Discount',NULL,'in_person',50.00,45,0,NULL,1,'2026-09-15 20:27:58','2026-09-15 20:27:58'),(70,8,'Massage Therapy Treatment -Private',NULL,'in_person',80.00,60,0,'#3699ff',1,'2026-10-16 10:47:46','2026-10-16 10:47:46'),(71,1,'Physiotherapy -Private','Physiotherapy -Private','in_person',60.00,45,0,'#3699ff',1,'2026-10-16 10:50:09','2026-10-16 10:50:09'),(72,5,'Orthotics','Custom Made Orthotics','in_person',500.00,45,0,'#3699ff',1,'2026-10-16 10:51:12','2026-10-16 10:51:54'),(73,7,'Compression Stockings','20-30mmHG','in_person',150.00,60,0,'#3699ff',1,'2026-10-16 10:53:34','2026-10-16 10:53:34');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_email_unique` (`email`),
  KEY `staff_location_id_foreign` (`location_id`),
  CONSTRAINT `staff_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (59,62,'Udhayakumar Natarajan','udhaympt@gmail.com',NULL,'X815','Massage Therapist',NULL,NULL,'practitioner','Massage Therapy',0.00,'$2y$12$Elj5TE0LBE.aj7SQ9YrWseQnL/E0geJvezaSR3SWN5AlNW4Yg0K7m',NULL,NULL,1,'2026-08-19 11:58:18','2026-10-06 06:25:43'),(60,62,'Vimal Reena Muthuswamy Sukumaran','yogajothib@gmail.com',NULL,'Reg No: 19607','Physiotherapist',NULL,NULL,'practitioner','Physiotherapy',NULL,'$2y$10$LNdY4RkmhIE8nUAl44CGMuQcU.x3xlowFTK8H.jfDF3yR8C13GJ4.',NULL,NULL,1,'2026-08-19 12:05:24','2026-09-15 11:14:07'),(61,62,'Udhayakumar Natarajan','udhayakumarN@gmail.com',NULL,'X815','Massage Therapist',NULL,NULL,'admin','Massage Therapy',NULL,'$2y$12$63O0V7IM9dgak3pMZhpfoOL1qtD4PrYUrvR6wTzt5wJzU4igfGwea','2026-10-19 18:51:30','HlEBbsuccBqdrMP6uVeZUxfE93PtQohwowBwmpVKS4EQLzgok33dezJqXsuM',1,'2026-08-19 11:58:18','2026-10-19 18:51:30'),(62,62,'Maria Hadjiapostoli','maria-hadji@hotmail.com',NULL,'J539','Massage Therapist',NULL,NULL,'practitioner','Massage Therapy',0.00,NULL,NULL,NULL,1,'2026-09-25 10:43:00','2026-09-25 10:46:33');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_categories`
--

DROP TABLE IF EXISTS `staff_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `service_category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_categories_staff_id_service_category_id_unique` (`staff_id`,`service_category_id`),
  KEY `staff_categories_service_category_id_foreign` (`service_category_id`),
  CONSTRAINT `staff_categories_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_categories_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_categories`
--

LOCK TABLES `staff_categories` WRITE;
/*!40000 ALTER TABLE `staff_categories` DISABLE KEYS */;
INSERT INTO `staff_categories` VALUES (1,59,2,'2026-10-02 19:10:14','2026-10-02 19:10:14'),(2,60,1,'2026-10-02 19:10:14','2026-10-02 19:10:14'),(3,61,2,'2026-10-02 19:10:14','2026-10-02 19:10:14'),(4,62,2,'2026-10-02 19:10:14','2026-10-02 19:10:14'),(10,59,8,NULL,NULL);
/*!40000 ALTER TABLE `staff_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_schedules`
--

DROP TABLE IF EXISTS `staff_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_schedules_staff_working_date_idx` (`staff_id`,`working_date`),
  KEY `staff_schedules_group_idx` (`recurrence_group_id`),
  CONSTRAINT `staff_schedules_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_schedules`
--

LOCK TABLES `staff_schedules` WRITE;
/*!40000 ALTER TABLE `staff_schedules` DISABLE KEYS */;
INSERT INTO `staff_schedules` VALUES (94,59,'4','2026-08-20','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(95,59,'2','2026-08-25','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(96,59,'4','2026-08-27','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(97,59,'2','2026-09-01','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(98,59,'4','2026-09-03','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(99,59,'2','2026-09-08','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(100,59,'4','2026-09-10','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(101,59,'2','2026-09-15','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(102,59,'4','2026-09-17','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(103,59,'2','2026-09-22','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(104,59,'4','2026-09-24','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(105,59,'2','2026-09-29','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(106,59,'4','2026-10-01','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(107,59,'2','2026-10-06','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(108,59,'4','2026-10-08','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(109,59,'2','2026-10-13','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(110,59,'4','2026-10-15','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(111,59,'2','2026-10-20','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(112,59,'4','2026-10-22','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(113,59,'2','2026-10-27','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(114,59,'4','2026-10-29','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(115,59,'2','2026-11-03','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(116,59,'4','2026-11-05','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(117,59,'2','2026-11-10','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(118,59,'4','2026-11-12','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(119,59,'2','2026-11-17','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(120,59,'4','2026-11-19','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(121,59,'2','2026-11-24','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(122,59,'4','2026-11-26','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(123,59,'2','2026-12-01','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(124,59,'4','2026-12-03','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(125,59,'2','2026-12-08','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(126,59,'4','2026-12-10','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(127,59,'2','2026-12-15','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(128,59,'4','2026-12-17','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(129,59,'2','2026-12-22','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(130,59,'4','2026-12-24','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(131,59,'2','2026-12-29','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(132,59,'4','2026-12-31','10:00:00','21:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','ebc32b98-059c-4b05-8ac8-4952df272c6f',NULL,'2026-08-19 12:02:27','2026-08-19 12:02:27'),(133,59,'5','2026-08-21','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(134,59,'6','2026-08-22','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(135,59,'5','2026-08-28','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(136,59,'6','2026-08-29','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(137,59,'5','2026-09-04','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(138,59,'6','2026-09-05','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(139,59,'5','2026-09-11','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(140,59,'6','2026-09-12','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(141,59,'5','2026-09-18','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(142,59,'6','2026-09-19','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(143,59,'5','2026-09-25','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(144,59,'6','2026-09-26','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(145,59,'5','2026-10-02','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(146,59,'6','2026-10-03','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(147,59,'5','2026-10-09','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(148,59,'6','2026-10-10','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(149,59,'5','2026-10-16','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(150,59,'6','2026-10-17','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(151,59,'5','2026-10-23','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(152,59,'6','2026-10-24','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(153,59,'5','2026-10-30','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(154,59,'6','2026-10-31','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(155,59,'5','2026-11-06','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(156,59,'6','2026-11-07','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(157,59,'5','2026-11-13','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(158,59,'6','2026-11-14','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(159,59,'5','2026-11-20','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(160,59,'6','2026-11-21','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(161,59,'5','2026-11-27','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(162,59,'6','2026-11-28','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(163,59,'5','2026-12-04','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(164,59,'6','2026-12-05','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(165,59,'5','2026-12-11','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(166,59,'6','2026-12-12','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(167,59,'5','2026-12-18','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(168,59,'6','2026-12-19','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(169,59,'5','2026-12-25','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(170,59,'6','2026-12-26','10:00:00','13:00:00',1,'weekly','{\"weekly_days\":[\"5\",\"6\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','5c6dc2dc-f3f9-48d5-958e-b6537de58a7d',NULL,'2026-08-19 12:03:36','2026-08-19 12:03:36'),(171,60,'4','2026-08-20','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(172,60,'2','2026-08-25','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(173,60,'4','2026-08-27','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(174,60,'2','2026-09-01','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(175,60,'4','2026-09-03','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(176,60,'2','2026-09-08','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(177,60,'4','2026-09-10','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(178,60,'2','2026-09-15','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(179,60,'4','2026-09-17','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(180,60,'2','2026-09-22','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(181,60,'4','2026-09-24','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(182,60,'2','2026-09-29','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(183,60,'4','2026-10-01','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(184,60,'2','2026-10-06','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(185,60,'4','2026-10-08','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(186,60,'2','2026-10-13','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(187,60,'4','2026-10-15','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(188,60,'2','2026-10-20','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(189,60,'4','2026-10-22','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(190,60,'2','2026-10-27','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(191,60,'4','2026-10-29','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(192,60,'2','2026-11-03','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(193,60,'4','2026-11-05','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(194,60,'2','2026-11-10','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(195,60,'4','2026-11-12','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(196,60,'2','2026-11-17','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(197,60,'4','2026-11-19','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(198,60,'2','2026-11-24','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(199,60,'4','2026-11-26','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(200,60,'2','2026-12-01','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(201,60,'4','2026-12-03','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(202,60,'2','2026-12-08','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(203,60,'4','2026-12-10','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(204,60,'2','2026-12-15','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(205,60,'4','2026-12-17','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(206,60,'2','2026-12-22','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(207,60,'4','2026-12-24','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(208,60,'2','2026-12-29','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(209,60,'4','2026-12-31','16:00:00','20:00:00',1,'weekly','{\"weekly_days\":[\"2\",\"4\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-12-31','d014a568-e7ea-4476-8920-e07a13fda5fc',NULL,'2026-08-19 12:06:25','2026-08-19 12:06:25'),(210,59,'3','2026-08-19','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(211,59,'3','2026-08-26','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(212,59,'3','2026-09-02','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(213,59,'3','2026-09-09','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(214,59,'3','2026-09-16','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(215,59,'3','2026-09-23','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(216,59,'3','2026-09-30','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(217,59,'3','2026-10-07','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(218,59,'3','2026-10-14','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(219,59,'3','2026-10-21','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(220,59,'3','2026-10-28','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(221,59,'3','2026-11-04','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(222,59,'3','2026-11-11','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02'),(223,59,'3','2026-11-18','07:00:00','17:00:00',1,'weekly','{\"weekly_days\":[\"3\"],\"monthly_day\":\"15\",\"yearly_month\":\"1\",\"yearly_day\":\"10\"}','2026-08-19','2026-11-19','943f5325-2919-4001-8479-014bdefffed4',NULL,'2026-08-19 12:13:02','2026-08-19 12:13:02');
/*!40000 ALTER TABLE `staff_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` varchar(191) NOT NULL DEFAULT 'monthly',
  `staff_limit` int(10) unsigned DEFAULT NULL,
  `location_limit` int(10) unsigned DEFAULT NULL,
  `appointment_limit` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'Starter','Small teams getting started.',0.00,'monthly',3,1,100,1,'2026-08-03 11:18:15','2026-08-03 11:18:15'),(2,'Business','Growing service businesses.',29.00,'monthly',15,5,1000,1,'2026-08-03 11:18:15','2026-08-03 11:18:15');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'trial',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(191) NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_staff_id_foreign` (`staff_id`),
  KEY `subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  CONSTRAINT `subscriptions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@example.com',NULL,'$2y$12$IbyZ8Z8epg/opfv3a.ayQe8OmF4UaetUXIUZeK0CrrvtWjA1XhNwS',NULL,'2026-02-05 02:25:16','2026-02-05 02:25:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'management_system'
--

--
-- Dumping routines for database 'management_system'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-20  9:55:36
