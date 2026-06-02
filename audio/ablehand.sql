-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 17, 2025 at 04:33 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ablehand`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `seen_at` datetime DEFAULT NULL,
  `seen_by` int DEFAULT NULL,
  `seen_time` datetime DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `patient_id_2` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `created_at`, `seen`, `seen_at`, `seen_by`, `seen_time`) VALUES
(64, 107, 8, '2025-07-07', '18:40:00', '', '2025-07-07 17:41:12', 0, NULL, NULL, NULL),
(65, 110, 8, '2025-07-08', '13:00:00', '', '2025-07-08 11:59:09', 0, NULL, NULL, NULL),
(66, 141, 115, '2025-07-17', '11:06:00', 'Pending', '2025-07-17 10:16:39', 0, NULL, NULL, NULL),
(67, 142, 115, '2025-07-17', '11:38:00', 'Pending', '2025-07-17 10:38:55', 0, NULL, NULL, NULL),
(68, 143, 8, '2025-07-12', '00:30:00', '', '2025-07-17 11:19:28', 0, NULL, NULL, NULL),
(69, 144, 115, '2025-07-17', '12:50:00', 'Pending', '2025-07-17 11:50:19', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` enum('CREATE','READ','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `accessed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billings`
--

DROP TABLE IF EXISTS `billings`;
CREATE TABLE IF NOT EXISTS `billings` (
  `billing_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `alert_seen` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`billing_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `billings`
--

INSERT INTO `billings` (`billing_id`, `patient_id`, `service_id`, `status`, `paid_at`, `alert_seen`) VALUES
(55, 140, 20, 'paid', '2025-07-15 05:56:43', 0),
(56, 140, 21, 'paid', '2025-07-15 06:09:33', 0),
(57, 140, 14, 'paid', '2025-07-15 06:09:33', 1),
(58, 140, 10, 'paid', '2025-07-15 06:09:33', 0),
(59, 140, 22, 'paid', '2025-07-15 06:16:36', 0),
(60, 140, 18, 'paid', '2025-07-15 06:17:10', 1),
(61, 140, 24, 'paid', '2025-07-15 06:19:36', 0),
(62, 140, 19, 'paid', '2025-07-15 06:19:36', 0),
(63, 140, 26, 'paid', '2025-07-15 06:29:04', 1),
(64, 143, 28, 'paid', '2025-07-17 05:54:44', 0);

-- --------------------------------------------------------

--
-- Table structure for table `billing_officers`
--

DROP TABLE IF EXISTS `billing_officers`;
CREATE TABLE IF NOT EXISTS `billing_officers` (
  `officer_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`officer_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_services`
--

DROP TABLE IF EXISTS `bill_services`;
CREATE TABLE IF NOT EXISTS `bill_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_id` (`user_id`),
  KEY `fk_role_id` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bill_services`
--

INSERT INTO `bill_services` (`id`, `service_name`, `cost`, `user_id`, `role_id`) VALUES
(7, 'Lab', 15000.00, NULL, 6),
(19, 'Consultation', 678.00, NULL, 2),
(10, 'Drugs', 6000.00, NULL, 5),
(18, 'Nursing', 7000.00, NULL, 3),
(17, 'Consultation', 5000.00, NULL, 2),
(14, 'Nursing', 798.00, NULL, 3),
(16, 'Consultation', 60000.00, NULL, 2),
(20, 'Consultation', 8500.00, NULL, 2),
(21, 'Consultation', 15000.00, NULL, 2),
(22, 'Consultation', 8500.00, NULL, 2),
(23, 'Lab', 35000.00, NULL, 6),
(24, 'Lab', 35000.00, NULL, 6),
(25, 'Nursing', 10000.00, NULL, 3),
(26, 'Nursing', 10000.00, NULL, 3),
(27, 'Nursing', 10000.00, NULL, 3),
(28, 'Drug Counseling & Education', 8000.00, NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `cashiers`
--

DROP TABLE IF EXISTS `cashiers`;
CREATE TABLE IF NOT EXISTS `cashiers` (
  `cashier_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cashier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
CREATE TABLE IF NOT EXISTS `consultations` (
  `consultation_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(50) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `temperature` varchar(10) DEFAULT NULL,
  `pulse` varchar(10) DEFAULT NULL,
  `respiratory_rate` varchar(10) DEFAULT NULL,
  `chief_complaint` text NOT NULL,
  `history` text,
  `exam_findings` text,
  `diagnosis` text NOT NULL,
  `investigations` text,
  `treatment_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `consultation_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `physical_exam` text,
  `doctor_signature` varchar(255) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `pulse_rate` int DEFAULT NULL,
  `respiration_rate` int DEFAULT NULL,
  `oxygen_saturation` int DEFAULT NULL,
  `pain_level` int DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `blood_sugar` decimal(5,2) DEFAULT NULL,
  `consciousness_level` varchar(20) DEFAULT NULL,
  `vitals_time` time DEFAULT NULL,
  `symptoms_notes` text,
  PRIMARY KEY (`consultation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`consultation_id`, `patient_id`, `patient_name`, `bp`, `temperature`, `pulse`, `respiratory_rate`, `chief_complaint`, `history`, `exam_findings`, `diagnosis`, `investigations`, `treatment_plan`, `doctor_name`, `consultation_date`, `created_at`, `physical_exam`, `doctor_signature`, `blood_pressure`, `pulse_rate`, `respiration_rate`, `oxygen_saturation`, `pain_level`, `height_cm`, `weight_kg`, `bmi`, `blood_sugar`, `consciousness_level`, `vitals_time`, `symptoms_notes`) VALUES
(2, '44', '', '456', '23', '44', '45', 'yuio', NULL, NULL, 'hhilyh.j', 'ggjh', 'tgujj', '', '2025-06-25', '2025-06-25 08:53:34', 'rkyh,n', 'umoh', NULL, NULL, NULL, 456, 10, 234.00, 345.00, 63.01, 6.00, 'Alert', '12:23:00', 'rtyt'),
(3, '44', '', '345', '345', '45', '54', 'malaria', NULL, NULL, 'better', 'ghh', 'wwee', '', '2025-06-27', '2025-06-27 12:11:09', 'okon', 'umoh', NULL, NULL, NULL, 234, 9, 34.00, 56.00, 484.43, 345.00, 'Verbal', '12:45:00', '345'),
(4, '48', '', '567', '45', '234', '123', 'okon', NULL, NULL, 'okon', 'ok', 'umoh', '', '2025-06-27', '2025-06-27 21:21:00', 'umoh', 'umoh', NULL, NULL, NULL, 345, 9, 345.00, 345.00, 234.00, 345.00, 'Verbal', '12:03:00', 'ree'),
(8, '107', '', '234', '2', '3', '4', 'rfff', NULL, NULL, 'rtyyu', 'rtuujj', 'trfnfv', '', '2025-07-08', '2025-07-08 08:40:54', 'rgggg', 'umoh', NULL, NULL, NULL, 234, 5, 34.00, 456.00, 484.43, 345.00, 'Unresponsive', '10:38:00', 'ovjvjnvn'),
(9, '107', '', '234', '2', '3', '4', 'rfff', NULL, NULL, 'rtyyu', 'rtuujj', 'trfnfv', '', '2025-07-08', '2025-07-08 08:44:13', 'rgggg', 'umoh', NULL, NULL, NULL, 234, 5, 34.00, 456.00, 484.43, 345.00, 'Unresponsive', '10:38:00', 'ovjvjnvn'),
(11, '107', '', '234', '2', '3', '4', 'rfff', NULL, NULL, 'rtyyu', 'rtuujj', 'trfnfv', '', '2025-07-08', '2025-07-08 09:22:42', 'rgggg', 'umoh', NULL, NULL, NULL, 234, 5, 34.00, 456.00, 484.43, 345.00, 'Unresponsive', '10:38:00', 'ovjvjnvn'),
(12, '107', '', '234', '2', '3', '4', 'rfff', NULL, NULL, 'rtyyu', 'rtuujj', 'trfnfv', '', '2025-07-08', '2025-07-08 11:01:16', 'rgggg', 'umoh', NULL, NULL, NULL, 234, 5, 34.00, 456.00, 484.43, 345.00, 'Unresponsive', '10:38:00', 'ovjvjnvn'),
(13, '107', '', '567', '1', '211', '2', 'COUGH', NULL, NULL, 'COUGH', 'ERR', 'IUUFJH', '', '2025-07-08', '2025-07-08 11:03:35', 'NOT OK', 'umoh', NULL, NULL, NULL, 345, 9, 345.00, 344.00, 455.00, 345.00, 'Pain', '12:02:00', 'TRY'),
(14, '107', '', '567', '1', '211', '2', 'COUGH', NULL, NULL, 'COUGH', 'ERR', 'IUUFJH', '', '2025-07-08', '2025-07-08 11:12:26', 'NOT OK', 'umoh', NULL, NULL, NULL, 345, 9, 345.00, 344.00, 455.00, 345.00, 'Pain', '12:02:00', 'TRY'),
(15, '107', '', '234', '12', '12', '23', 'YYHH', NULL, NULL, 'YDHHDH', 'GDGGDG', 'YDHJHDUJ', '', '2025-07-08', '2025-07-08 11:14:32', 'UUUUDJ', 'umoh', NULL, NULL, NULL, 34, 9, 234.00, 123.00, 234.00, 234.00, 'Pain', '12:13:00', 'YYGWE'),
(16, '107', '', '234', '12', '12', '23', 'YYHH', NULL, NULL, 'YDHHDH', 'GDGGDG', 'YDHJHDUJ', '', '2025-07-08', '2025-07-08 11:22:13', 'UUUUDJ', 'umoh', NULL, NULL, NULL, 34, 9, 234.00, 123.00, 234.00, 234.00, 'Pain', '12:13:00', 'YYGWE'),
(17, '107', '', '234', '12', '12', '23', 'YYHH', NULL, NULL, 'YDHHDH', 'GDGGDG', 'YDHJHDUJ', '', '2025-07-08', '2025-07-08 11:25:35', 'UUUUDJ', 'umoh', NULL, NULL, NULL, 34, 9, 234.00, 123.00, 234.00, 234.00, 'Pain', '12:13:00', 'YYGWE'),
(18, '107', '', '234', '12', '12', '23', 'YYHH', NULL, NULL, 'YDHHDH', 'GDGGDG', 'YDHJHDUJ', '', '2025-07-08', '2025-07-08 11:29:47', 'UUUUDJ', 'umoh', NULL, NULL, NULL, 34, 9, 234.00, 123.00, 234.00, 234.00, 'Pain', '12:13:00', 'YYGWE'),
(19, '107', '', '234', '12', '12', '23', 'YYHH', NULL, NULL, 'YDHHDH', 'GDGGDG', 'YDHJHDUJ', '', '2025-07-08', '2025-07-08 11:32:21', 'UUUUDJ', 'umoh', NULL, NULL, NULL, 34, 9, 234.00, 123.00, 234.00, 234.00, 'Pain', '12:13:00', 'YYGWE'),
(20, '107', '', '234', '12', '32', '34', 'uuri', NULL, NULL, 'jpjfjjk', 'iffjjjjj', 'fjdfjdjjj', '', '2025-07-10', '2025-07-10 14:17:32', 'jfjfjfjj', 'umoh', NULL, NULL, NULL, 234, 8, 34.00, 45.00, 45.00, 34.00, 'Verbal', '12:05:00', 'eeffdjljdf'),
(21, '143', '', '', '', '', '', 'umo', NULL, NULL, 'iioo', 'uuii', 'yun', '', '2025-07-17', '2025-07-17 11:24:09', 'iooj', 'umoh', NULL, NULL, NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, '', '00:00:00', ''),
(22, '141', '', '', '', '', '', 'HEADACHE', NULL, NULL, 'MAL', 'MPS', 'FBC', '', '2025-07-17', '2025-07-17 11:59:10', 'NAD', 'DR', NULL, NULL, NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, '', '00:00:00', ''),
(23, '144', '', '', '', '', '', 'FRONTAL HEADACHE\r\nWEAKNESS OF THE BODY \r\nINSOMNIA', NULL, NULL, 'RESISTANT MALARIA/ R/O SEPSIS', 'DO MPS ', 'FOR MPS/FBC', '', '2025-07-17', '2025-07-17 12:16:55', 'PMHX PATIENT WAS TREATED FOR MAL/SEPSIS ON 09TH OF THESE MONTH.SYMPTOMS PERSIST\r\nO/E  STABLE, WELL HEDRATED, AFEBRILE, NOT PALE, \r\nCHEST CLININCALLY CLEAR\r\nABDOMEN SOFT MWR. \r\nOTHER SYSTEMIC FINDINGS ARE ESSENTIALLY NORMAL', 'DR ATUMIYE', NULL, NULL, NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, '', '00:00:00', '');

-- --------------------------------------------------------

--
-- Table structure for table `dispensed_medicines`
--

DROP TABLE IF EXISTS `dispensed_medicines`;
CREATE TABLE IF NOT EXISTS `dispensed_medicines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `medicine_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `dispense_date` date DEFAULT NULL,
  `prescribed_by` varchar(100) DEFAULT NULL,
  `dispensed_by` varchar(100) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `medicine_id` (`medicine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dispensed_medicines`
--

INSERT INTO `dispensed_medicines` (`id`, `patient_id`, `medicine_id`, `quantity`, `dispense_date`, `prescribed_by`, `dispensed_by`, `notes`) VALUES
(1, 13, 2, 1, NULL, 'UMOH', 'UMOH', 'OK'),
(2, 14, 1, 4, NULL, 'UMOH', 'UMOH', 'IU'),
(3, 35, 1, 3, NULL, 'umoh', 'ioop', 'good'),
(4, 35, 1, 1, NULL, 'UMOH', 'UMOH', 'umoh'),
(5, 35, 1, 1, NULL, 'UMOH', 'UMOH', 'umoh'),
(6, 35, 1, 1, NULL, 'UMOH', 'UMOH', 'umoh'),
(7, 34, 2, 1, NULL, 'Patrick', 'Akpan', 'ok'),
(8, 34, 2, 2, NULL, '2', 'Akpan', 'ewwee'),
(9, 34, 2, 2, NULL, '2', 'Akpan', 'ewwee'),
(10, 43, 5, 8, NULL, 'OKON', 'JON', 'OKON'),
(11, 43, 5, 2, NULL, 'mr. irodia', 'Akpan', 'yh');

-- --------------------------------------------------------

--
-- Table structure for table `drug_chart`
--

DROP TABLE IF EXISTS `drug_chart`;
CREATE TABLE IF NOT EXISTS `drug_chart` (
  `chart_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `drug_name` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `route` varchar(50) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `time_administered` time DEFAULT NULL,
  `prescribed_by` int DEFAULT NULL,
  `administered_by` int DEFAULT NULL,
  `hmo_covered` tinyint(1) DEFAULT '0',
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Prescribed','Ongoing','Completed','Discontinued') DEFAULT 'Prescribed',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `seen_by_pharmacist` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`chart_id`),
  KEY `patient_id` (`patient_id`),
  KEY `prescribed_by` (`prescribed_by`),
  KEY `administered_by` (`administered_by`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `drug_chart`
--

INSERT INTO `drug_chart` (`chart_id`, `patient_id`, `drug_name`, `dosage`, `route`, `frequency`, `duration`, `start_date`, `end_date`, `time_administered`, `prescribed_by`, `administered_by`, `hmo_covered`, `batch_number`, `expiry_date`, `status`, `notes`, `created_at`, `seen_by_pharmacist`) VALUES
(1, 35, 'drug', '6', 'ewa', '12', '5 months', '2025-06-12', '2025-05-04', NULL, 0, NULL, 0, NULL, NULL, 'Prescribed', 'okon', '2025-06-24 05:54:13', 0),
(2, 34, 'umoh', '234', '234', '23', '5 months', '2025-06-12', '2025-07-12', NULL, 0, NULL, 0, NULL, NULL, 'Prescribed', 'okon', '2025-06-24 06:00:10', 0),
(3, 104, 'paracetamol', '4', '8', '7', '4 months', '2025-07-04', '2025-07-06', NULL, 8, NULL, 0, NULL, NULL, 'Prescribed', 'uyiii', '2025-07-02 21:26:41', 0),
(4, 43, 'panadol extra', '1', 'oral', 'twice daily', '5 days', '2025-07-09', '2025-07-12', NULL, 8, NULL, 0, NULL, NULL, 'Prescribed', 'make you complete your Medication', '2025-07-02 22:00:52', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ehr`
--

DROP TABLE IF EXISTS `ehr`;
CREATE TABLE IF NOT EXISTS `ehr` (
  `ehr_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `diagnosis` text,
  `doctor_notes` text,
  `prescribed_medications` text,
  `vitals` text,
  `visit_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `doctor_id` int DEFAULT NULL,
  PRIMARY KEY (`ehr_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hmos`
--

DROP TABLE IF EXISTS `hmos`;
CREATE TABLE IF NOT EXISTS `hmos` (
  `hmo_id` int NOT NULL AUTO_INCREMENT,
  `hmo_name` varchar(500) NOT NULL,
  `hmo_code` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`hmo_id`),
  UNIQUE KEY `hmo_code` (`hmo_code`)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hmos`
--

INSERT INTO `hmos` (`hmo_id`, `hmo_name`, `hmo_code`, `country`) VALUES
(1, 'AXA Mansard', 'AXA001', 'Nigeria'),
(2, 'Hygeia HMO', 'HYG002', 'Nigeria'),
(3, 'Reliance HMO', 'REL003', 'Nigeria'),
(4, 'Avon HMO', 'AVN004', 'Nigeria'),
(5, 'Kaiser Permanente', 'KP001', 'United States'),
(6, 'Blue Cross Blue Shield', 'BCBS002', 'United States'),
(7, 'Cigna', 'CIG003', 'United States'),
(8, 'Bupa Global', 'BUPA001', 'United Kingdom'),
(9, 'Discovery Health', 'DISC002', 'South Africa'),
(10, 'Medibank', 'MDBK001', 'Australia'),
(11, 'Sun Life Health', 'SUNL001', 'Canada'),
(12, 'Total Health Trust', 'THT005', 'Nigeria'),
(13, 'Integrated Healthcare Limited', 'IHL006', 'Nigeria'),
(14, 'MetroHealth HMO', 'MH007', 'Nigeria'),
(15, 'Greenbay HMO', 'GB008', 'Nigeria'),
(16, 'Aetna', 'AET004', 'United States'),
(17, 'UnitedHealthcare', 'UHC005', 'United States'),
(18, 'Humana', 'HUM006', 'United States'),
(19, 'Health Net', 'HN007', 'United States'),
(20, 'AXA PPP Healthcare', 'AXAPP001', 'United Kingdom'),
(21, 'VitalityHealth', 'VIT002', 'United Kingdom'),
(22, 'Manulife Health', 'MAN003', 'Canada'),
(23, 'Green Shield Canada', 'GSC004', 'Canada'),
(24, 'nib Health Funds', 'NIB005', 'Australia'),
(25, 'Bupa Australia', 'BUPAAUS006', 'Australia'),
(26, 'Star Health Insurance', 'STARIN001', 'India'),
(27, 'Religare Health Insurance', 'RELIND002', 'India'),
(28, 'Max Bupa Health Insurance', 'MAXBUPA003', 'India'),
(29, 'Bonitas Medical Fund', 'BON004', 'South Africa'),
(30, 'Momentum Health', 'MOM005', 'South Africa'),
(31, 'Integrated Healthcare Ltd', 'IHL009', 'Nigeria'),
(32, 'Clearline HMO', 'CLR010', 'Nigeria'),
(33, 'Novo Health Africa', 'NHA011', 'Nigeria'),
(34, 'Daman Health', 'DAM021', 'UAE'),
(35, 'Tawuniya', 'TAW022', 'Saudi Arabia'),
(36, 'Bupa Arabia', 'BPAR023', 'Saudi Arabia'),
(37, 'Nextcare', 'NXC024', 'UAE'),
(38, 'Mediplan Healthcare', 'MED025', 'Nigeria'),
(39, 'Oceanic Health Limited', 'OHL026', 'Nigeria'),
(40, 'Zenith Medicare', 'ZEN027', 'Nigeria'),
(41, 'Oscar Health', 'OSC028', 'United States'),
(42, 'Bright Health', 'BHT029', 'United States'),
(43, 'EmblemHealth', 'EMB030', 'United States'),
(44, 'National Friendly', 'NF031', 'United Kingdom'),
(45, 'WPA Healthcare', 'WPA032', 'United Kingdom'),
(46, 'Aditya Birla Health Insurance', 'ABHI033', 'India'),
(47, 'ICICI Lombard Health', 'ICICIL034', 'India'),
(48, 'Blue Cross Canada', 'BCC035', 'Canada'),
(49, 'Desjardins Health Insurance', 'DSJ036', 'Canada'),
(50, 'Teachers Health', 'THA037', 'Australia'),
(51, 'GMHBA Health', 'GMH038', 'Australia'),
(52, 'KeyHealth Medical Scheme', 'KHSA039', 'South Africa'),
(53, 'Fedhealth', 'FED040', 'South Africa'),
(54, 'AXA Gulf', 'AXAG041', 'UAE'),
(55, 'Globemed Lebanon', 'GML042', 'Lebanon'),
(56, 'Swift HMO', 'SWF043', 'Nigeria'),
(57, 'Royal Exchange HMO', 'REH044', 'Nigeria'),
(58, 'Prepaid Medicare Services', 'PPMS045', 'Nigeria'),
(59, 'Fallon Health', 'FAL046', 'United States'),
(60, 'Geisinger Health Plan', 'GHP047', 'United States'),
(61, 'Capital District Physicians’ Health Plan (CDPHP)', 'CDPHP048', 'United States'),
(62, 'CS Healthcare', 'CSH049', 'United Kingdom'),
(63, 'Freedom Health Insurance', 'FHI050', 'United Kingdom'),
(64, 'SBI General Health Insurance', 'SBIGH051', 'India'),
(65, 'Oriental Insurance Company', 'OIC052', 'India'),
(66, 'Medavie Blue Cross', 'MBC053', 'Canada'),
(67, 'SSQ Insurance', 'SSQ054', 'Canada'),
(68, 'Frank Health Insurance', 'FRK055', 'Australia'),
(69, 'Peoplecare Health', 'PPL056', 'Australia'),
(70, 'Resolution Health', 'RSH057', 'South Africa'),
(71, 'CompCare Wellness', 'CCW058', 'South Africa'),
(72, 'Saudi German Health', 'SGH059', 'Saudi Arabia'),
(73, 'Almadallah Healthcare', 'ALM060', 'UAE'),
(74, 'AXA Mansard Health', 'HMO004', 'Nigeria'),
(75, 'Leadway Health', 'HMO005', 'Nigeria'),
(76, 'Total Health Trust (THT)', 'HMO006', 'Nigeria'),
(77, 'Redcare HMO', 'HMO007', 'Nigeria'),
(78, 'AIICO Multishield', 'HMO008', 'Nigeria'),
(79, 'Hallmark HMO', 'HMO009', 'Nigeria'),
(80, 'ProHealth HMO', 'HMO010', 'Nigeria'),
(81, 'Bastion HMO', 'HMO011', 'Nigeria'),
(82, 'Wellness HMO', 'HMO013', 'Nigeria'),
(83, 'Health Partners HMO', 'HMO014', 'Nigeria'),
(84, 'Anchor HMO', 'HMO015', 'Nigeria'),
(85, 'Mediplan Healthcare Ltd', 'HMO017', 'Nigeria'),
(86, 'Defence Health Maintenance Ltd (DHML)', 'HMO018', 'Nigeria'),
(87, 'United Healthcare International Ltd', 'HMO020', 'Nigeria'),
(88, 'Marina Medical Services', 'HMO022', 'Nigeria'),
(89, 'Princeton HMO', 'HMO023', 'Nigeria'),
(90, 'Prepaid Medicare Services Ltd', 'HMO024', 'Nigeria'),
(91, 'Oceanic Health', 'HMO026', 'Nigeria'),
(92, 'Staco Healthcare Ltd', 'HMO027', 'Nigeria'),
(93, 'Maayoit Healthcare', 'HMO028', 'Nigeria'),
(94, 'Garki HMO', 'HMO029', 'Nigeria'),
(95, 'Premier Medicaid', 'HMO031', 'Nigeria'),
(96, 'Ultimate Health Management Services', 'HMO032', 'Nigeria'),
(97, 'Songhai Health Trust Ltd', 'HMO033', 'Nigeria'),
(98, 'Royal Health Maintenance', 'HMO034', 'Nigeria'),
(99, 'Police HMO (NHIS for police officers)', 'HMO035', 'Nigeria'),
(100, 'FedServ HMO (for federal workers)', 'HMO036', 'Nigeria'),
(101, 'Redcare HMO', 'HMO001', 'Nigeria'),
(102, 'AIICO Multishield', 'HMO002', 'Nigeria'),
(103, 'Hallmark HMO', 'HMO003', 'Nigeria'),
(104, 'Redcare HMO', 'HMO0028', 'Nigeria');

-- --------------------------------------------------------

--
-- Table structure for table `hos_bills`
--

DROP TABLE IF EXISTS `hos_bills`;
CREATE TABLE IF NOT EXISTS `hos_bills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `date_billed` datetime DEFAULT CURRENT_TIMESTAMP,
  `printed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hos_bills`
--

INSERT INTO `hos_bills` (`id`, `patient_id`, `service_id`, `quantity`, `date_billed`, `printed`) VALUES
(1, 14, 1, 1, '2025-05-22 16:50:23', 1),
(2, 29, 1, 3, '2025-05-26 13:56:50', 1),
(3, 30, 1, 1, '2025-05-27 12:18:21', 1),
(4, 30, 1, 1, '2025-05-27 12:19:43', 1),
(5, 30, 1, 1, '2025-05-27 14:50:39', 1),
(6, 30, 1, 1, '2025-05-27 14:51:41', 1),
(7, 30, 1, 1, '2025-05-27 14:53:23', 1),
(8, 30, 1, 1, '2025-05-27 15:13:00', 1),
(9, 30, 7, 1, '2025-05-27 15:14:25', 1),
(10, 30, 7, 1, '2025-05-27 15:17:12', 1),
(11, 30, 7, 1, '2025-05-27 15:18:08', 1),
(12, 30, 7, 1, '2025-05-27 15:58:09', 1),
(13, 14, 7, 1, '2025-05-27 17:05:19', 1),
(14, 29, 9, 1, '2025-05-27 19:37:23', 1),
(15, 14, 7, 1, '2025-05-28 08:14:31', 0),
(16, 28, 10, 1, '2025-05-28 08:18:59', 1),
(17, 28, 10, 1, '2025-05-28 08:20:25', 1),
(18, 29, 13, 3, '2025-05-28 08:40:19', 1),
(19, 29, 14, 1, '2025-05-28 08:46:40', 1),
(20, 29, 2, 1, '2025-05-28 08:53:41', 1),
(21, 28, 14, 1, '2025-05-28 08:55:22', 1),
(22, 28, 1, 1, '2025-05-28 08:56:06', 0),
(23, 22, 15, 2, '2025-05-29 12:49:48', 1),
(24, 28, 2, 1, '2025-05-29 12:50:47', 0),
(25, 33, 1, 2, '2025-05-29 13:52:17', 1),
(26, 17, 6, 1, '2025-05-29 13:55:55', 1),
(27, 16, 2, 1, '2025-05-29 14:02:49', 1),
(28, 22, 1, 2, '2025-05-29 15:00:25', 1),
(29, 22, 2, 1, '2025-05-29 15:05:40', 1),
(30, 33, 6, 1, '2025-05-29 15:06:54', 1),
(31, 34, 7, 1, '2025-06-20 10:56:40', 1),
(32, 42, 14, 1, '2025-06-21 22:21:12', 1),
(33, 41, 16, 1, '2025-06-21 22:57:54', 1),
(34, 42, 10, 1, '2025-06-22 18:22:32', 1),
(35, 42, 16, 1, '2025-06-22 18:24:42', 1),
(36, 43, 16, 4, '2025-06-22 19:33:54', 1),
(37, 48, 16, 1, '2025-06-22 22:39:11', 1),
(38, 101, 10, 1, '2025-06-26 09:12:13', 1),
(39, 41, 17, 1, '2025-06-26 09:24:58', 1),
(40, 43, 17, 2, '2025-06-26 10:03:31', 1),
(41, 45, 17, 2, '2025-06-26 10:18:34', 1),
(42, 46, 16, 1, '2025-06-26 10:31:21', 1),
(43, 46, 7, 1, '2025-06-26 10:31:34', 1),
(44, 46, 10, 1, '2025-06-26 10:31:48', 1),
(45, 46, 14, 1, '2025-06-26 10:32:00', 1),
(46, 100, 16, 1, '2025-06-26 10:43:25', 1),
(47, 35, 10, 1, '2025-06-26 12:21:13', 1),
(48, 39, 10, 1, '2025-06-26 12:25:07', 1),
(49, 45, 14, 67, '2025-06-26 12:43:24', 1),
(50, 35, 16, 9000, '2025-06-26 12:46:17', 1),
(51, 35, 19, 1, '2025-06-28 13:35:40', 1),
(52, 43, 19, 1, '2025-06-28 13:43:38', 1),
(53, 100, 19, 2, '2025-06-28 13:45:46', 1),
(54, 39, 7, 24, '2025-06-28 14:04:29', 1),
(55, 40, 7, 12, '2025-06-28 14:09:21', 1),
(56, 34, 10, 34, '2025-06-28 20:44:41', 1),
(57, 40, 10, 2, '2025-07-07 06:44:53', 1),
(58, 48, 10, 1, '2025-07-07 06:45:53', 1),
(59, 34, 16, 3, '2025-07-07 08:46:46', 1),
(60, 48, 17, 3, '2025-07-07 08:54:27', 1),
(61, 40, 16, 1, '2025-07-07 09:00:28', 1),
(62, 34, 19, 2, '2025-07-07 09:02:05', 1),
(63, 107, 19, 1, '2025-07-07 18:57:53', 1),
(64, 110, 7, 1, '2025-07-10 15:23:13', 1),
(65, 110, 19, 1, '2025-07-10 15:23:22', 1),
(66, 110, 16, 1, '2025-07-10 15:23:31', 1),
(67, 110, 18, 1, '2025-07-10 15:23:40', 1),
(68, 140, 20, 1, '2025-07-15 05:56:20', 1),
(69, 140, 21, 1, '2025-07-15 06:08:26', 1),
(70, 140, 14, 1, '2025-07-15 06:08:56', 1),
(71, 140, 10, 1, '2025-07-15 06:09:17', 1),
(72, 140, 22, 2, '2025-07-15 06:16:16', 0),
(73, 140, 18, 1, '2025-07-15 06:17:00', 0),
(74, 140, 24, 1, '2025-07-15 06:18:59', 0),
(75, 140, 19, 1, '2025-07-15 06:19:19', 0),
(76, 140, 26, 1, '2025-07-15 06:28:53', 0),
(77, 143, 28, 1, '2025-07-17 05:54:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `injections`
--

DROP TABLE IF EXISTS `injections`;
CREATE TABLE IF NOT EXISTS `injections` (
  `injection_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `notes` text,
  `injection_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`injection_id`),
  KEY `patient_id` (`patient_id`),
  KEY `medicine_id` (`medicine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance`
--

DROP TABLE IF EXISTS `insurance`;
CREATE TABLE IF NOT EXISTS `insurance` (
  `insurance_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `policy_number` varchar(50) NOT NULL,
  `group_number` varchar(50) DEFAULT NULL,
  `coverage_details` text,
  `claim_status` enum('Pending','Approved','Rejected','In Review') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`insurance_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `service_description` text,
  `amount` decimal(10,2) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  PRIMARY KEY (`invoice_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_orders`
--

DROP TABLE IF EXISTS `lab_orders`;
CREATE TABLE IF NOT EXISTS `lab_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `test_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent_to_lab','sent_to_cashier','paid','seen') DEFAULT 'pending',
  `ordered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `requested_by` varchar(255) DEFAULT NULL,
  `completed_by` int DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_orders`
--

INSERT INTO `lab_orders` (`id`, `patient_id`, `test_name`, `status`, `ordered_at`, `requested_by`, `completed_by`, `completed_at`) VALUES
(1, 143, 'Full Blood Count', '', '2025-07-17 14:47:48', '115', 60, '2025-07-17 09:30:56'),
(2, 143, 'Urinalysis', '', '2025-07-17 14:47:48', '115', 60, '2025-07-17 09:31:43'),
(3, 143, 'Malaria Parasite', 'pending', '2025-07-17 14:47:48', '115', NULL, NULL),
(4, 143, 'HIV Test', '', '2025-07-17 15:19:30', '115', 60, '2025-07-17 09:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

DROP TABLE IF EXISTS `lab_tests`;
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `lab_test_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `test_name` varchar(100) DEFAULT NULL,
  `test_date` date DEFAULT NULL,
  `result` text,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `report_file` text,
  `requested_by` varchar(100) DEFAULT NULL,
  `appointment_id` int DEFAULT NULL,
  PRIMARY KEY (`lab_test_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`lab_test_id`, `patient_id`, `test_name`, `test_date`, `result`, `status`, `report_file`, `requested_by`, `appointment_id`) VALUES
(30, 110, 'high blood pressure', '2025-07-08', 'POSITIVE', 'Completed', 'uploads/1693463024290.jpg', 'doctor', NULL),
(28, 109, 'high blood pressure', '2025-05-12', 'POSITIVE', 'Completed', 'uploads/1693463024290.jpg', 'Lab tecnician', NULL),
(29, 109, 'high blood pressure', '2025-07-08', 'POSITIVE', 'Completed', 'uploads/1693463024290.jpg', 'Lab tecnician', NULL),
(27, 109, 'high blood pressure', '2025-05-22', 'POSITIVE', 'Completed', 'uploads/DSC_0624 - Copy.jpg', 'Lab tecnician', NULL),
(26, 109, 'high blood pressure', '2025-07-12', 'positive', 'Completed', 'uploads/1693463024290.jpg', 'Lab tecnician', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests_catalog`
--

DROP TABLE IF EXISTS `lab_tests_catalog`;
CREATE TABLE IF NOT EXISTS `lab_tests_catalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_tests_catalog`
--

INSERT INTO `lab_tests_catalog` (`id`, `test_name`) VALUES
(1, 'Full Blood Count'),
(2, 'Urinalysis'),
(3, 'Malaria Parasite'),
(4, 'Blood Sugar'),
(5, 'HIV Test');

-- --------------------------------------------------------

--
-- Table structure for table `login_activity`
--

DROP TABLE IF EXISTS `login_activity`;
CREATE TABLE IF NOT EXISTS `login_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `status` enum('success','failed') DEFAULT 'success',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text,
  `email` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `login_state` enum('Online','Offline') DEFAULT 'Offline',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_activity`
--

INSERT INTO `login_activity` (`id`, `user_id`, `full_name`, `role_id`, `login_time`, `logout_time`, `status`, `ip_address`, `user_agent`, `email`, `duration`, `login_state`) VALUES
(37, 8, 'mr. irodia', 2, '2025-07-14 04:40:05', NULL, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'irodialucky@gmail.com', NULL, 'Online'),
(36, 11, 'Rose', 3, '2025-07-12 21:28:38', '2025-07-14 05:39:53', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'rose@gmail.com', '7 hrs 11 mins', 'Offline'),
(35, 11, 'Rose', 3, '2025-07-11 23:58:17', NULL, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'rose@gmail.com', NULL, 'Online'),
(34, 12, 'james', 5, '2025-07-11 23:52:22', '2025-07-12 00:58:07', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'james@gmail.com', '0 hrs 5 mins', 'Offline'),
(33, 9, 'admin', 1, '2025-07-11 22:34:45', '2025-07-14 15:30:11', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'admin@gmail.com', '15 hrs 55 mins', 'Offline'),
(31, 11, 'Rose', 3, '2025-07-11 06:40:55', NULL, 'success', '10.245.83.133', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36', 'rose@gmail.com', NULL, 'Online'),
(32, 8, 'mr. irodia', 2, '2025-07-11 11:59:27', NULL, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'irodialucky@gmail.com', NULL, 'Online'),
(30, 9, 'admin', 1, '2025-07-11 05:47:18', '2025-07-11 07:39:13', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'admin@gmail.com', '0 hrs 51 mins', 'Offline'),
(29, 9, 'admin', 1, '2025-07-11 05:30:25', '2025-07-11 12:59:15', 'success', '10.245.83.133', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36', 'admin@gmail.com', '6 hrs 28 mins', 'Offline'),
(28, 8, 'mr. irodia', 2, '2025-07-11 04:52:36', '2025-07-11 06:47:09', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'irodialucky@gmail.com', '0 hrs 54 mins', 'Offline'),
(38, 9, 'admin', 1, '2025-07-15 10:29:19', '2025-07-15 03:29:33', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'admin@gmail.com', '0 hrs 0 mins', 'Offline'),
(39, 105, 'BINDE', 6, '2025-07-15 11:49:05', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(40, 105, 'BINDE', 6, '2025-07-15 11:57:33', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(41, 105, 'BINDE', 6, '2025-07-15 12:12:19', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(42, 106, 'CHIAHA AMARACHI NORA', 1, '2025-07-15 12:16:58', '2025-07-15 07:43:27', 'success', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', '2 hrs 26 mins', 'Offline'),
(43, 105, 'BINDE', 6, '2025-07-15 12:44:51', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(44, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-15 12:51:56', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(45, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-15 13:14:04', NULL, 'success', '192.168.0.174', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(46, 105, 'BINDE', 6, '2025-07-15 13:20:12', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(47, 105, 'BINDE', 6, '2025-07-15 13:41:13', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(48, 110, 'cletus edeh faith', 3, '2025-07-15 14:16:56', '2025-07-15 07:29:46', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'fcletus402@gmail.com', '0 hrs 12 mins', 'Offline'),
(49, 110, 'cletus edeh faith', 3, '2025-07-15 14:30:55', NULL, 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'fcletus402@gmail.com', NULL, 'Online'),
(50, 11, 'Rose', 3, '2025-07-15 14:31:22', '2025-07-15 07:34:55', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'rose@gmail.com', '0 hrs 3 mins', 'Offline'),
(51, 9, 'admin', 1, '2025-07-15 14:35:05', '2025-07-15 07:35:28', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'admin@gmail.com', '0 hrs 0 mins', 'Offline'),
(52, 60, 'umoh ekpo Umoh', 6, '2025-07-15 14:35:59', '2025-07-15 07:36:34', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'umoh@gmail.com', '0 hrs 0 mins', 'Offline'),
(53, 8, 'mr. irodia', 2, '2025-07-15 14:37:00', '2025-07-15 08:50:39', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'irodialucky@gmail.com', '1 hrs 13 mins', 'Offline'),
(54, 11, 'Rose', 3, '2025-07-15 14:43:46', NULL, 'success', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'rose@gmail.com', NULL, 'Online'),
(55, 105, 'BINDE', 6, '2025-07-15 14:59:24', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(56, 11, 'Rose', 3, '2025-07-15 15:19:32', '2025-07-15 08:27:05', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'rose@gmail.com', '0 hrs 7 mins', 'Offline'),
(57, 111, 'uchechi', 5, '2025-07-15 15:32:36', '2025-07-15 08:49:56', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'uchechipope2023@gmail.com', '0 hrs 17 mins', 'Offline'),
(58, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-15 15:43:21', NULL, 'success', '192.168.0.174', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(59, 12, 'james', 5, '2025-07-15 15:51:02', NULL, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'james@gmail.com', NULL, 'Online'),
(60, 12, 'james', 5, '2025-07-15 15:53:22', '2025-07-15 09:33:31', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'james@gmail.com', '0 hrs 40 mins', 'Offline'),
(61, 112, 'kemuel bashitapwa', 3, '2025-07-15 16:38:46', '2025-07-16 02:25:03', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'bashitapwa@gmail.com', '16 hrs 46 mins', 'Offline'),
(62, 105, 'BINDE', 6, '2025-07-15 16:46:04', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(63, 105, 'BINDE', 6, '2025-07-16 08:42:12', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(64, 110, 'cletus edeh faith', 3, '2025-07-16 09:26:18', NULL, 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'fcletus402@gmail.com', NULL, 'Online'),
(65, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:29:01', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(66, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:29:16', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(67, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:32:58', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(68, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:34:28', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(69, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-16 09:34:57', '2025-07-16 07:55:55', 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', '5 hrs 20 mins', 'Offline'),
(70, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:39:05', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(71, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 09:44:32', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(72, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 10:55:16', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(73, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 10:55:39', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(74, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 10:55:56', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(75, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 10:58:08', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(76, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 14:51:10', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(77, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 14:51:31', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(78, 107, 'CHIAHA AMARACHI NORA', 7, '2025-07-16 14:56:24', NULL, 'failed', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', NULL, 'Offline'),
(79, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-16 15:08:38', NULL, 'failed', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Offline'),
(80, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-16 15:09:07', NULL, 'success', '192.168.0.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'nchiaha980@gmail.com', NULL, 'Online'),
(81, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-16 15:19:39', '2025-07-17 01:47:15', 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', '17 hrs 27 mins', 'Offline'),
(82, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-17 08:47:25', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(83, 105, 'BINDE', 6, '2025-07-17 08:51:27', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'nandulbinde@gmail.com', NULL, 'Online'),
(84, 115, 'Dr Moses Atumiye', 2, '2025-07-17 08:56:51', NULL, 'success', '192.168.0.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'atumiyemoses@gmail.com', NULL, 'Online'),
(85, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-17 08:58:30', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', NULL, 'Online'),
(86, 116, 'Chisom Okoye', 3, '2025-07-17 09:15:44', NULL, 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'okoyecheesom@gmail.com', NULL, 'Online'),
(87, 116, 'Chisom Okoye', 3, '2025-07-17 09:27:29', '2025-07-17 03:23:15', 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'okoyecheesom@gmail.com', '0 hrs 55 mins', 'Offline'),
(88, 114, 'MARY-ANN AKUNEBU', 6, '2025-07-17 09:39:20', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'maryannakunebu@gmail.com', NULL, 'Online'),
(89, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-17 10:00:59', '2025-07-17 03:04:33', 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', '0 hrs 3 mins', 'Offline'),
(90, 116, 'Chisom Okoye', 3, '2025-07-17 10:05:29', '2025-07-17 03:15:49', 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'okoyecheesom@gmail.com', '0 hrs 10 mins', 'Offline'),
(91, 115, 'Dr Moses Atumiye', 2, '2025-07-17 10:14:34', '2025-07-17 08:50:12', 'success', '192.168.0.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'atumiyemoses@gmail.com', '5 hrs 35 mins', 'Offline'),
(92, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-17 10:15:54', '2025-07-17 03:48:16', 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', '0 hrs 32 mins', 'Offline'),
(93, 116, 'Chisom Okoye', 3, '2025-07-17 10:23:24', NULL, 'success', '192.168.0.175', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'okoyecheesom@gmail.com', NULL, 'Online'),
(94, 114, 'MARY-ANN AKUNEBU', 6, '2025-07-17 10:24:33', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19043', 'maryannakunebu@gmail.com', NULL, 'Online'),
(95, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-17 10:48:26', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(96, 60, 'umoh ekpo Umoh', 6, '2025-07-17 11:10:31', '2025-07-17 04:15:32', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'umoh@gmail.com', '0 hrs 5 mins', 'Offline'),
(97, 9, 'admin', 1, '2025-07-17 11:15:49', '2025-07-17 04:19:35', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'admin@gmail.com', '0 hrs 3 mins', 'Offline'),
(98, 8, 'mr. irodia', 2, '2025-07-17 11:19:58', NULL, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'irodialucky@gmail.com', NULL, 'Online'),
(99, 113, 'CHIAHA AMARACHI NORA', 1, '2025-07-17 11:43:18', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'nchiaha980@gmail.com', NULL, 'Online'),
(100, 60, 'umoh ekpo Umoh', 6, '2025-07-17 12:23:45', '2025-07-17 05:24:44', 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'umoh@gmail.com', '0 hrs 0 mins', 'Offline'),
(101, 114, 'MARY-ANN AKUNEBU', 6, '2025-07-17 12:27:05', NULL, 'success', '192.168.0.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'maryannakunebu@gmail.com', NULL, 'Online'),
(102, 109, 'AGBOOLA ADEOLA ELIZABETH', 4, '2025-07-17 12:52:47', NULL, 'success', '192.168.0.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'agboolaadeolaelizabeth@gmail.com', NULL, 'Online'),
(103, 60, 'umoh ekpo Umoh', 6, '2025-07-17 15:50:31', NULL, 'success', '192.168.0.117', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'umoh@gmail.com', NULL, 'Online');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

DROP TABLE IF EXISTS `medical_history`;
CREATE TABLE IF NOT EXISTS `medical_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `chief_complaint` text,
  `hpi_onset` text,
  `hpi_location` text,
  `hpi_severity` text,
  `hpi_aggravating` text,
  `hpi_associated` text,
  `pmh_diseases` text,
  `pmh_surgeries` text,
  `pmh_allergies` text,
  `medications_current` text,
  `medications_otc` text,
  `family_history` text,
  `social_occupation` text,
  `social_marital` text,
  `social_tobacco` varchar(3) DEFAULT NULL,
  `social_alcohol` varchar(3) DEFAULT NULL,
  `social_drugs` varchar(3) DEFAULT NULL,
  `lifestyle` text,
  `review_general` text,
  `review_cardio` text,
  `review_respiratory` text,
  `review_gi` text,
  `review_gu` text,
  `review_neuro` text,
  `review_msk` text,
  `review_psych` text,
  `review_skin` text,
  `pe_bp` varchar(10) DEFAULT NULL,
  `pe_hr` varchar(10) DEFAULT NULL,
  `pe_rr` varchar(10) DEFAULT NULL,
  `pe_temp` varchar(10) DEFAULT NULL,
  `pe_spo2` varchar(10) DEFAULT NULL,
  `pe_general` text,
  `pe_heent` text,
  `pe_chest` text,
  `pe_cv` text,
  `pe_abdomen` text,
  `pe_neuro` text,
  `pe_extremities` text,
  `diagnosis` text,
  `plan_investigations` text,
  `plan_medications` text,
  `plan_referrals` text,
  `plan_education` text,
  `physician_name` text,
  `physician_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_medical_history_patient` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medical_history`
--

INSERT INTO `medical_history` (`id`, `patient_id`, `full_name`, `dob`, `age`, `gender`, `photo`, `address`, `phone`, `visit_date`, `chief_complaint`, `hpi_onset`, `hpi_location`, `hpi_severity`, `hpi_aggravating`, `hpi_associated`, `pmh_diseases`, `pmh_surgeries`, `pmh_allergies`, `medications_current`, `medications_otc`, `family_history`, `social_occupation`, `social_marital`, `social_tobacco`, `social_alcohol`, `social_drugs`, `lifestyle`, `review_general`, `review_cardio`, `review_respiratory`, `review_gi`, `review_gu`, `review_neuro`, `review_msk`, `review_psych`, `review_skin`, `pe_bp`, `pe_hr`, `pe_rr`, `pe_temp`, `pe_spo2`, `pe_general`, `pe_heent`, `pe_chest`, `pe_cv`, `pe_abdomen`, `pe_neuro`, `pe_extremities`, `diagnosis`, `plan_investigations`, `plan_medications`, `plan_referrals`, `plan_education`, `physician_name`, `physician_date`, `created_at`, `attachment`) VALUES
(14, '35', 'Emmanuel inya', '1994-04-27', 24, 'Male', NULL, 'BUKAN SIDI', '08152728879', '2025-06-18', 'doctor', 'onset', 'location', '10', 'relieving factor', 'Associated Symtop', 'hypertension', 'Surgeries', 'Allegries', 'current', 'OTc Drug', 'Father: Diabetes\r\nMother: Cancer\r\nSiblings: Asthma\r\nOthers: Grandpa\r\nConditions: Diabetes', 'student', 'single', 'Yes', 'Yes', 'Yes', 'diet', 'Fever', 'Chest pain', 'Cough', 'Nausea', 'Frequency', 'Headache', 'Joint pain', 'Anxiety', 'Rashes', 'BP', 'HR', 'RR', 'Temp', 'SpO2', 'General', 'HEENT', 'Chief', 'CV', 'Abdomen', 'Neuro', 'Extremities', 'yes', 'Labs', 'yes', 'akpan', 'Yes', 'Akpan', '2025-06-18', '2025-06-18 05:58:18', '6853c0276ea57_1693463024290.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `medical_historys`
--

DROP TABLE IF EXISTS `medical_historys`;
CREATE TABLE IF NOT EXISTS `medical_historys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `patient_id` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `age` int NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `chief_complaint` text,
  `hpi` text,
  `pmh` text,
  `surgical_history` json DEFAULT NULL,
  `medications` json DEFAULT NULL,
  `allergies` text,
  `family_history` text,
  `social_history` json DEFAULT NULL,
  `immunization` json DEFAULT NULL,
  `ros` json DEFAULT NULL,
  `obstetric` json DEFAULT NULL,
  `physical_exam` json DEFAULT NULL,
  `assessment_plan` text,
  `clinician_name` varchar(255) DEFAULT NULL,
  `clinician_designation` varchar(100) DEFAULT NULL,
  `clinician_date` date DEFAULT NULL,
  `clinician_signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medical_historys`
--

INSERT INTO `medical_historys` (`id`, `full_name`, `patient_id`, `dob`, `age`, `gender`, `photo`, `marital_status`, `occupation`, `address`, `phone`, `visit_date`, `chief_complaint`, `hpi`, `pmh`, `surgical_history`, `medications`, `allergies`, `family_history`, `social_history`, `immunization`, `ros`, `obstetric`, `physical_exam`, `assessment_plan`, `clinician_name`, `clinician_designation`, `clinician_date`, `clinician_signature`, `created_at`) VALUES
(4, 'jere', 'jere383202', '1995-06-12', 56, 'Male', NULL, 'Married', 'student', 'BUKAN SIDI', '+2348134843148', '2025-08-12', 'chest pain', 'onset', NULL, '[{\"date\": \"2025-02-12\", \"name\": \"yes\", \"complications\": \"yes\"}]', '[{\"name\": \"kong\", \"dosage\": \"2\", \"duration\": \"2 months\", \"frequency\": \"twice a day\", \"indication\": \"yes\"}]', 'No Known Drug Allergies', 'Hypertension', '{\"drugs\": \"Yes\", \"alcohol\": \"Yes\", \"smoking\": \"Yes\", \"partners\": \"4\", \"protection\": \"Always\", \"work_hazards\": \"good\", \"drugs_specify\": \"gan\", \"smoking_packs\": \"1\", \"alcohol_details\": \"2\", \"sexual_activity\": \"Active\"}', '{\"others\": \"yes\", \"covid19\": \"Yes\", \"tetanus\": \"Yes\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"Yes\", \"tetanus_date\": \"2025-07-08\"}', '{\"gi\": \"ok\", \"gu\": \"yes\", \"ns\": \"ok\", \"msk\": \"ok\", \"derm\": \"ok\", \"resp\": \"yes\", \"psych\": \"ok\", \"cardio\": \"okon\", \"general\": \"okon\", \"gi_desc\": \"ok\", \"gu_desc\": \"yes\", \"ns_desc\": \"ok\", \"msk_desc\": \"ok\", \"derm_desc\": \"ok\", \"resp_desc\": \"yes\", \"psych_desc\": \"ok\", \"cardio_desc\": \"ok\", \"general_desc\": \"yes\"}', '{\"lmp\": \"\", \"para\": \"1\", \"type\": \"yes\", \"cycle\": \"Regular\", \"gravida\": \"1\", \"abortions\": \"1\", \"contraceptive_use\": \"No\"}', '{\"bp\": \"120\", \"hr\": \"66\", \"rr\": \"56\", \"bmi\": \"655\", \"spo2\": \"67\", \"temp\": \"28\", \"height\": \"677\", \"weight\": \"78\"}', 'ffgjgjh', 'umoh ekpo Umoh', 'doctor', '2025-07-24', 'umoh', '2025-07-05 20:07:30'),
(5, 'Morgan inalegwu', 'morgan2721', '2016-07-05', 17, 'Male', NULL, 'Married', 'student', 'AKWA IBOM STATE', '+2348064843148', '0000-00-00', 'chest', 'duration', NULL, '[{\"date\": \"2025-07-24\", \"name\": \"yes\", \"complications\": \"yes\"}]', '[{\"name\": \"kong\", \"dosage\": \"2\", \"duration\": \"2 months\", \"frequency\": \"twice a day\", \"indication\": \"yes\"}]', 'No Known Drug Allergies', 'Hypertension', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"Yes\", \"partners\": \"1\", \"protection\": \"Always\", \"work_hazards\": \"good\", \"drugs_specify\": \"\", \"smoking_packs\": \"\", \"alcohol_details\": \"\", \"sexual_activity\": \"Active\"}', '{\"others\": \"\", \"covid19\": \"No\", \"tetanus\": \"No\", \"childhood\": \"Not Sure\", \"hepatitis_b\": \"No\", \"tetanus_date\": \"\"}', '{\"gi\": \"\", \"gu\": \"\", \"ns\": \"\", \"msk\": \"\", \"derm\": \"\", \"resp\": \"\", \"psych\": \"\", \"cardio\": \"\", \"general\": \"\", \"gi_desc\": \"\", \"gu_desc\": \"\", \"ns_desc\": \"\", \"msk_desc\": \"\", \"derm_desc\": \"\", \"resp_desc\": \"\", \"psych_desc\": \"\", \"cardio_desc\": \"\", \"general_desc\": \"\"}', '{\"lmp\": \"\", \"para\": \"\", \"type\": \"\", \"cycle\": \"Regular\", \"gravida\": \"\", \"abortions\": \"\", \"contraceptive_use\": \"No\"}', '{\"bp\": \"120\", \"hr\": \"66\", \"rr\": \"56\", \"bmi\": \"655\", \"spo2\": \"67\", \"temp\": \"28\", \"height\": \"677\", \"weight\": \"78\"}', 'take all', 'umoh ekpo Umoh', 'doctor', '2025-07-24', 'umoh', '2025-07-05 20:23:44'),
(6, 'Obong', 'obong28669', '2025-01-15', 17, 'Male', NULL, 'Single', 'student', 'AKWA IBOM STATE', '+2348064843148', '2025-07-06', 'chest', 'onset', NULL, '[{\"date\": \"2025-07-24\", \"name\": \"yes\", \"complications\": \"yes\"}]', '[{\"name\": \"kong\", \"dosage\": \"2\", \"duration\": \"2 months\", \"frequency\": \"twice a day\", \"indication\": \"45\"}]', 'No Known Drug Allergies', 'Hypertension', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"Yes\", \"partners\": \"7\", \"protection\": \"Always\", \"work_hazards\": \"good\", \"drugs_specify\": \"gan\", \"smoking_packs\": \"1\", \"alcohol_details\": \"2\", \"sexual_activity\": \"Active\"}', '{\"others\": \"yes\", \"covid19\": \"Yes\", \"tetanus\": \"Yes\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"Yes\", \"tetanus_date\": \"2025-07-12\"}', '{\"gi\": \"ok\", \"gu\": \"yes\", \"ns\": \"ok\", \"msk\": \"ok\", \"derm\": \"ok\", \"resp\": \"yes\", \"psych\": \"ok\", \"cardio\": \"okon\", \"general\": \"okon\", \"gi_desc\": \"ok\", \"gu_desc\": \"yes\", \"ns_desc\": \"ok\", \"msk_desc\": \"ok\", \"derm_desc\": \"ok\", \"resp_desc\": \"yes\", \"psych_desc\": \"ok\", \"cardio_desc\": \"ok\", \"general_desc\": \"yes\"}', '{\"lmp\": \"2025-07-09\", \"para\": \"6\", \"type\": \"yes\", \"cycle\": \"Irregular\", \"gravida\": \"4\", \"abortions\": \"6\", \"contraceptive_use\": \"Yes\"}', '{\"bp\": \"120\", \"hr\": \"66\", \"rr\": \"56\", \"bmi\": \"678\", \"spo2\": \"567\", \"temp\": \"28\", \"height\": \"677\", \"weight\": \"78\"}', 'once', 'Mr. Irodia lucky', 'doctor', '2025-07-06', 'umoh', '2025-07-06 22:31:12'),
(7, 'Obong', 'obong28669', '2025-01-15', 17, 'Male', '../uploads/1750626598_IMG_20250405_165001_370~2.jpg', 'Single', 'student', 'AKWA IBOM STATE', '+2348064843148', '2025-07-06', 'chest', 'onset', NULL, '[{\"date\": \"2025-07-24\", \"name\": \"yes\", \"complications\": \"yes\"}]', '[{\"name\": \"kong\", \"dosage\": \"2\", \"duration\": \"2 months\", \"frequency\": \"twice a day\", \"indication\": \"45\"}]', 'No Known Drug Allergies', 'Hypertension', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"Yes\", \"partners\": \"7\", \"protection\": \"Always\", \"work_hazards\": \"good\", \"drugs_specify\": \"gan\", \"smoking_packs\": \"1\", \"alcohol_details\": \"2\", \"sexual_activity\": \"Active\"}', '{\"others\": \"yes\", \"covid19\": \"Yes\", \"tetanus\": \"Yes\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"Yes\", \"tetanus_date\": \"2025-07-12\"}', '{\"gi\": \"ok\", \"gu\": \"yes\", \"ns\": \"ok\", \"msk\": \"ok\", \"derm\": \"ok\", \"resp\": \"yes\", \"psych\": \"ok\", \"cardio\": \"okon\", \"general\": \"okon\", \"gi_desc\": \"ok\", \"gu_desc\": \"yes\", \"ns_desc\": \"ok\", \"msk_desc\": \"ok\", \"derm_desc\": \"ok\", \"resp_desc\": \"yes\", \"psych_desc\": \"ok\", \"cardio_desc\": \"ok\", \"general_desc\": \"yes\"}', '{\"lmp\": \"2025-07-09\", \"para\": \"6\", \"type\": \"yes\", \"cycle\": \"Irregular\", \"gravida\": \"4\", \"abortions\": \"6\", \"contraceptive_use\": \"Yes\"}', '{\"bp\": \"120\", \"hr\": \"66\", \"rr\": \"56\", \"bmi\": \"678\", \"spo2\": \"567\", \"temp\": \"28\", \"height\": \"677\", \"weight\": \"78\"}', 'once', 'Mr. Irodia lucky', 'doctor', '2025-07-06', 'umoh', '2025-07-06 22:33:56'),
(8, 'Obong', 'obong28669', '2025-01-15', 17, 'Male', '../uploads/1750626598_IMG_20250405_165001_370~2.jpg', 'Single', 'student', 'AKWA IBOM STATE', '+2348064843148', '2025-07-06', 'chest', 'onset', NULL, '[{\"date\": \"2025-07-24\", \"name\": \"yes\", \"complications\": \"yes\"}]', '[{\"name\": \"kong\", \"dosage\": \"2\", \"duration\": \"2 months\", \"frequency\": \"twice a day\", \"indication\": \"45\"}]', 'No Known Drug Allergies', 'Hypertension', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"Yes\", \"partners\": \"7\", \"protection\": \"Always\", \"work_hazards\": \"good\", \"drugs_specify\": \"gan\", \"smoking_packs\": \"1\", \"alcohol_details\": \"2\", \"sexual_activity\": \"Active\"}', '{\"others\": \"yes\", \"covid19\": \"Yes\", \"tetanus\": \"Yes\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"Yes\", \"tetanus_date\": \"2025-07-12\"}', '{\"gi\": \"ok\", \"gu\": \"yes\", \"ns\": \"ok\", \"msk\": \"ok\", \"derm\": \"ok\", \"resp\": \"yes\", \"psych\": \"ok\", \"cardio\": \"okon\", \"general\": \"okon\", \"gi_desc\": \"ok\", \"gu_desc\": \"yes\", \"ns_desc\": \"ok\", \"msk_desc\": \"ok\", \"derm_desc\": \"ok\", \"resp_desc\": \"yes\", \"psych_desc\": \"ok\", \"cardio_desc\": \"ok\", \"general_desc\": \"yes\"}', '{\"lmp\": \"2025-07-09\", \"para\": \"6\", \"type\": \"yes\", \"cycle\": \"Irregular\", \"gravida\": \"4\", \"abortions\": \"6\", \"contraceptive_use\": \"Yes\"}', '{\"bp\": \"120\", \"hr\": \"66\", \"rr\": \"56\", \"bmi\": \"678\", \"spo2\": \"567\", \"temp\": \"28\", \"height\": \"677\", \"weight\": \"78\"}', 'once', 'Mr. Irodia lucky', 'doctor', '2025-07-06', 'umoh', '2025-07-06 22:34:01'),
(9, 'Ekpo', 'ekpo312831', '1997-04-12', 28, 'Male', '../uploads/1751908469_IMG_20231027_175855_821.jpg', 'Married', '', 'NO. 1 REGGY EMMANUEL RD., BY MOPOL STATION, ARMY SCHEME QUATERS, KUBWA', '08064843908', '0000-00-00', '', '', NULL, '[]', '[]', '', '', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"No\", \"partners\": \"\", \"protection\": \"Always\", \"work_hazards\": \"\", \"drugs_specify\": \"\", \"smoking_packs\": \"\", \"alcohol_details\": \"\", \"sexual_activity\": \"Active\"}', '{\"others\": \"\", \"covid19\": \"No\", \"tetanus\": \"No\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"No\", \"tetanus_date\": \"\"}', '{\"gi\": \"\", \"gu\": \"\", \"ns\": \"\", \"msk\": \"\", \"derm\": \"\", \"resp\": \"\", \"psych\": \"\", \"cardio\": \"\", \"general\": \"\", \"gi_desc\": \"\", \"gu_desc\": \"\", \"ns_desc\": \"\", \"msk_desc\": \"\", \"derm_desc\": \"\", \"resp_desc\": \"\", \"psych_desc\": \"\", \"cardio_desc\": \"\", \"general_desc\": \"\"}', '{\"lmp\": \"\", \"para\": \"\", \"type\": \"\", \"cycle\": \"Regular\", \"gravida\": \"\", \"abortions\": \"\", \"contraceptive_use\": \"No\"}', '{\"bp\": \"\", \"hr\": \"\", \"rr\": \"\", \"bmi\": \"\", \"spo2\": \"\", \"temp\": \"\", \"height\": \"\", \"weight\": \"\"}', '', '', '', '0000-00-00', '', '2025-07-11 07:02:21'),
(10, 'Godstime', 'godstime35', '1990-06-12', 35, 'Male', '../uploads/1752350505_1693463024290.jpg', 'Married', '', 'kubwa', '09071703491', '0000-00-00', '', '', NULL, '[]', '[]', '', '', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"No\", \"partners\": \"\", \"protection\": \"Always\", \"work_hazards\": \"\", \"drugs_specify\": \"\", \"smoking_packs\": \"\", \"alcohol_details\": \"\", \"sexual_activity\": \"Active\"}', '{\"others\": \"\", \"covid19\": \"No\", \"tetanus\": \"No\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"No\", \"tetanus_date\": \"\"}', '{\"gi\": \"\", \"gu\": \"\", \"ns\": \"\", \"msk\": \"\", \"derm\": \"\", \"resp\": \"\", \"psych\": \"\", \"cardio\": \"\", \"general\": \"\", \"gi_desc\": \"\", \"gu_desc\": \"\", \"ns_desc\": \"\", \"msk_desc\": \"\", \"derm_desc\": \"\", \"resp_desc\": \"\", \"psych_desc\": \"\", \"cardio_desc\": \"\", \"general_desc\": \"\"}', '{\"lmp\": \"\", \"para\": \"\", \"type\": \"\", \"cycle\": \"Regular\", \"gravida\": \"\", \"abortions\": \"\", \"contraceptive_use\": \"No\"}', '{\"bp\": \"\", \"hr\": \"\", \"rr\": \"\", \"bmi\": \"\", \"spo2\": \"\", \"temp\": \"\", \"height\": \"\", \"weight\": \"\"}', '', '', '', '0000-00-00', '', '2025-07-15 16:46:15'),
(11, 'Bello Rafatu Omaiyoza', 'bello69742', '1995-10-12', 29, 'Female', '', 'Single', '', 'Behind Health Center Karimo,Abuja', '08036431527', '0000-00-00', 'cold,headache,body pain', '', NULL, '[]', '[{\"name\": \"Tab Act 80/480 \", \"dosage\": \"1\", \"duration\": \"3\", \"frequency\": \"2\", \"indication\": \"malaria treatment\"}]', '', '', '{\"drugs\": \"No\", \"alcohol\": \"No\", \"smoking\": \"No\", \"partners\": \"\", \"protection\": \"Always\", \"work_hazards\": \"\", \"drugs_specify\": \"\", \"smoking_packs\": \"\", \"alcohol_details\": \"\", \"sexual_activity\": \"Active\"}', '{\"others\": \"\", \"covid19\": \"No\", \"tetanus\": \"No\", \"childhood\": \"Up to Date\", \"hepatitis_b\": \"No\", \"tetanus_date\": \"\"}', '{\"gi\": \"\", \"gu\": \"\", \"ns\": \"\", \"msk\": \"\", \"derm\": \"\", \"resp\": \"\", \"psych\": \"\", \"cardio\": \"\", \"general\": \"\", \"gi_desc\": \"\", \"gu_desc\": \"\", \"ns_desc\": \"\", \"msk_desc\": \"\", \"derm_desc\": \"\", \"resp_desc\": \"\", \"psych_desc\": \"\", \"cardio_desc\": \"\", \"general_desc\": \"\"}', '{\"lmp\": \"\", \"para\": \"\", \"type\": \"\", \"cycle\": \"Regular\", \"gravida\": \"\", \"abortions\": \"\", \"contraceptive_use\": \"No\"}', '{\"bp\": \"\", \"hr\": \"\", \"rr\": \"\", \"bmi\": \"\", \"spo2\": \"\", \"temp\": \"\", \"height\": \"\", \"weight\": \"\"}', '', '', '', '0000-00-00', '', '2025-07-17 10:46:03');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE IF NOT EXISTS `medical_records` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `diagnosis` text,
  `notes` text,
  `attachments` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `treatment` text NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `chief_complaint` text,
  `hpi_onset` text,
  `hpi_location` text,
  `hpi_severity` text,
  `hpi_aggravating` text,
  `hpi_associated` text,
  `pmh_diseases` text,
  `pmh_surgeries` text,
  `pmh_allergies` text,
  `medications_current` text,
  `medications_otc` text,
  `family_history` text,
  `social_occupation` text,
  `social_marital` varchar(50) DEFAULT NULL,
  `social_tobacco` varchar(50) DEFAULT NULL,
  `social_alcohol` varchar(50) DEFAULT NULL,
  `social_drugs` varchar(50) DEFAULT NULL,
  `lifestyle` text,
  `review_general` text,
  `review_cardio` text,
  `review_respiratory` text,
  `review_gi` text,
  `review_gu` text,
  `review_neuro` text,
  `review_msk` text,
  `review_psych` text,
  `review_skin` text,
  `pe_bp` varchar(10) DEFAULT NULL,
  `pe_hr` varchar(10) DEFAULT NULL,
  `pe_rr` varchar(10) DEFAULT NULL,
  `pe_temp` varchar(10) DEFAULT NULL,
  `pe_spo2` varchar(10) DEFAULT NULL,
  `pe_general` text,
  `pe_heent` text,
  `pe_chest` text,
  `pe_cv` text,
  `pe_abdomen` text,
  `pe_neuro` text,
  `pe_extremities` text,
  `plan_investigations` text,
  `plan_medications` text,
  `plan_referrals` text,
  `plan_education` text,
  `physician_name` varchar(255) DEFAULT NULL,
  `physician_date` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `patient_id_2` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `doctor_id`, `diagnosis`, `notes`, `attachments`, `created_at`, `treatment`, `full_name`, `dob`, `age`, `gender`, `address`, `phone`, `visit_date`, `chief_complaint`, `hpi_onset`, `hpi_location`, `hpi_severity`, `hpi_aggravating`, `hpi_associated`, `pmh_diseases`, `pmh_surgeries`, `pmh_allergies`, `medications_current`, `medications_otc`, `family_history`, `social_occupation`, `social_marital`, `social_tobacco`, `social_alcohol`, `social_drugs`, `lifestyle`, `review_general`, `review_cardio`, `review_respiratory`, `review_gi`, `review_gu`, `review_neuro`, `review_msk`, `review_psych`, `review_skin`, `pe_bp`, `pe_hr`, `pe_rr`, `pe_temp`, `pe_spo2`, `pe_general`, `pe_heent`, `pe_chest`, `pe_cv`, `pe_abdomen`, `pe_neuro`, `pe_extremities`, `plan_investigations`, `plan_medications`, `plan_referrals`, `plan_education`, `physician_name`, `physician_date`, `attachment`) VALUES
(1, 9, 8, 'malaria  and typhoid', 'see the phamarcist ', NULL, '2025-05-01 12:03:14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 10, 8, 'uyyttt', 'ttt6ttt', 'uploads/6813685a13dea_IMG_20250420_113527_657.jpg', '2025-05-01 12:26:02', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 9, 7, 'heasde', 'yuuui', 'IMG_20250419_093619125_BURST0003.jpg', '2025-05-01 14:28:47', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 9, 5, 'oopp', 'uyttr', 'access relationship.pdf', '2025-05-01 15:01:36', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 10, 8, 'uuretsgdcvnhm,njm', 'uhhnlkj', 'Mr Dan 330001.pdf', '2025-05-01 15:06:26', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 9, 8, 'oooopoiu', 'baeb', 'Mr Dan 33.pdf', '2025-05-01 15:10:06', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 35, 11, 'kwashiokor', 'taken', '1693463024290.jpg', '2025-06-15 18:57:06', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 14, 38, 'uuummmm', 'yuuiiooooo', NULL, '2025-05-17 20:38:36', 'tyuuuiiiii', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 14, 38, 'uii', 'opppp', NULL, '2025-05-17 20:42:34', 'ooooo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 14, 38, 'uii', 'opppp', NULL, '2025-05-17 22:00:21', 'ooooo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 13, 38, 'oppppppppppppppppwewwwe', 'uutytuutuututuu', NULL, '2025-05-18 19:28:09', 'okonnnnnnn', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 13, 38, 'oppppppppppppppppwewwwe', 'uutytuutuututuu', NULL, '2025-05-18 19:32:35', 'okonnnnnnn', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 16, 38, 'umoh', 'goodluck', NULL, '2025-05-18 21:12:52', '4556667', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 16, 38, 'uuuu', 'haba', '', '2025-05-18 22:22:44', 'dddd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 15, 15, 'cool', 'fine', 'Administer Medication.pdf', '2025-05-29 19:48:19', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 22, 15, 'yuuioo', 'ttree', 'ablehand.sql', '2025-05-27 20:05:18', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 34, 11, 'Malaphoid', 'assesed', '1693463024290.jpg', '2025-06-15 18:58:06', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

DROP TABLE IF EXISTS `medicines`;
CREATE TABLE IF NOT EXISTS `medicines` (
  `medicine_id` int NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `description` text,
  `stock` int DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`medicine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `medicine_name`, `description`, `stock`, `expiry_date`, `price`) VALUES
(1, 'paracetamol', 'take once', 13, '2025-08-27', 250.00),
(2, 'panadol extra', 'take once', 18, '2025-08-27', 850.00),
(3, '', '1 MORNING, I EVENNING, 1 AFTERNOON.', 12, '2025-06-29', 12345.00),
(4, '', '1 MORNING, I EVENNING, 1 AFTERNOON.', 12, '2025-06-29', 12345.00),
(5, 'panadol extra', '2 m, 2a, 2 Night.', 13, '2025-06-23', 5675.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `is_read`, `sent_at`) VALUES
(1, 9, 'oko', 0, '2025-05-06 11:32:21'),
(2, 15, 'UMOH', 0, '2025-05-20 06:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `nursing_orders`
--

DROP TABLE IF EXISTS `nursing_orders`;
CREATE TABLE IF NOT EXISTS `nursing_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `procedure_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent_to_nurse','sent_to_cashier','paid','seen') DEFAULT 'pending',
  `ordered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `requested_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nursing_orders`
--

INSERT INTO `nursing_orders` (`id`, `patient_id`, `procedure_name`, `status`, `ordered_at`, `requested_by`) VALUES
(1, 143, 'Vitamin C', 'pending', '2025-07-17 15:02:52', '115'),
(2, 143, 'Catheterization', 'pending', '2025-07-17 15:06:33', '115');

-- --------------------------------------------------------

--
-- Table structure for table `nursing_procedures_catalog`
--

DROP TABLE IF EXISTS `nursing_procedures_catalog`;
CREATE TABLE IF NOT EXISTS `nursing_procedures_catalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `procedure_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nursing_procedures_catalog`
--

INSERT INTO `nursing_procedures_catalog` (`id`, `procedure_name`) VALUES
(1, 'Wound Dressing'),
(2, 'IV Fluids'),
(3, 'Catheterization'),
(4, 'Vitamin C');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` text NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`) VALUES
(5, 'umohek@gmail.com', '94fb5eb2d6ca1f065c8e5fa80d78b2c6880329bf4dfabaf60f8bda9b6986b6da', '2025-05-28 10:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
CREATE TABLE IF NOT EXISTS `patients` (
  `patient_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `age` int DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `registered_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `patient_pin` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `patient_type` enum('Regular','HMO') NOT NULL DEFAULT 'Regular',
  `patient_status` enum('Outpatient','Inpatient') NOT NULL DEFAULT 'Outpatient',
  `hmo_name` varchar(255) DEFAULT NULL,
  `has_dispensation` tinyint(1) DEFAULT '0',
  `ssn` varchar(30) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`patient_id`),
  KEY `registered_by` (`registered_by`),
  KEY `full_name` (`full_name`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `full_name`, `dob`, `gender`, `age`, `address`, `phone`, `email`, `registered_by`, `created_at`, `patient_pin`, `photo`, `user_id`, `patient_type`, `patient_status`, `hmo_name`, `has_dispensation`, `ssn`, `language`, `marital_status`, `registration_date`) VALUES
(141, 'Agu Precious Amarachukwu', '1999-09-25', 'Female', 25, 'plot 829 mabushi district abuja', '09078885674', 'precious@quidax.com', 113, '2025-07-17 10:02:26', 'agu524537', '', 117, 'HMO', 'Outpatient', 'AXA Mansard', 0, NULL, 'igbo', 'Single', '2025-07-17 10:02:26'),
(142, 'Bello Rafatu Omaiyoza', '1995-10-12', 'Female', 29, 'Behind Health Center Karimo,Abuja', '08036431527', 'bellorafatu552@gmail.com', 113, '2025-07-17 10:33:02', 'bello69742', '', 118, 'HMO', 'Outpatient', 'Bastion', 0, NULL, 'Ebira', 'Single', '2025-07-17 10:33:02'),
(143, 'Umoh umoh', '1998-04-07', 'Male', 27, 'kubwa', '07063471860', 'um@gmail.com', 9, '2025-07-17 11:17:40', 'umoh615052', '', 119, 'Regular', 'Outpatient', NULL, 0, NULL, 'English', 'Single', '2025-07-17 11:17:40'),
(144, 'Drisu Ajuma', '1989-02-05', 'Female', 36, '7th avenue, gwarimpa', '08066792155', 'ajumadrisu@gmail.com', 113, '2025-07-17 11:49:14', 'drisu40189', '', 120, 'HMO', 'Outpatient', 'AXA Mansard', 0, NULL, 'Ebira', 'Single', '2025-07-17 11:49:14');

-- --------------------------------------------------------

--
-- Table structure for table `patient_orders`
--

DROP TABLE IF EXISTS `patient_orders`;
CREATE TABLE IF NOT EXISTS `patient_orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `service_type` enum('lab','procedure','pharmacy') NOT NULL,
  `details` text NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_by` varchar(100) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `is_sent_to_cashier` tinyint DEFAULT '0',
  `is_paid` tinyint DEFAULT '0',
  `is_seen_by_doctor` tinyint DEFAULT '0',
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_orders`
--

INSERT INTO `patient_orders` (`order_id`, `patient_id`, `service_type`, `details`, `status`, `created_by`, `created_at`, `completed_by`, `completed_at`, `report_file`, `is_sent_to_cashier`, `is_paid`, `is_seen_by_doctor`) VALUES
(65, 107, 'pharmacy', 'Amoxicillin - 9000', 'pending', 8, '2025-07-10 15:17:32', NULL, NULL, NULL, 0, 0, 0),
(64, 107, 'procedure', 'IV Fluids', 'completed', 8, '2025-07-10 15:17:32', '11', '2025-07-15 14:34:38', NULL, 0, 0, 0),
(63, 107, 'procedure', 'Wound Dressing', 'completed', 8, '2025-07-10 15:17:32', '112', '2025-07-15 16:38:58', NULL, 0, 0, 0),
(62, 107, 'lab', 'Malaria Parasite', 'pending', 8, '2025-07-10 15:17:32', NULL, NULL, NULL, 0, 0, 0),
(60, 107, 'lab', 'Full Blood Count', 'completed', 8, '2025-07-10 15:17:32', '105', '2025-07-15 12:13:06', NULL, 0, 0, 0),
(61, 107, 'lab', 'Urinalysis', 'completed', 8, '2025-07-10 15:17:32', '105', '2025-07-15 16:46:48', NULL, 0, 0, 0),
(66, 143, 'lab', 'Full Blood Count', 'completed', 8, '2025-07-17 04:24:10', '60', '2025-07-17 12:24:14', NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

DROP TABLE IF EXISTS `payment_history`;
CREATE TABLE IF NOT EXISTS `payment_history` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_inventory`
--

DROP TABLE IF EXISTS `pharmacy_inventory`;
CREATE TABLE IF NOT EXISTS `pharmacy_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `quantity` int NOT NULL,
  `expiration_date` date NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacy_inventory`
--

INSERT INTO `pharmacy_inventory` (`id`, `name`, `quantity`, `expiration_date`, `price`, `supplier`, `batch_number`) VALUES
(1, 'vitamin c', 123, '2025-12-23', 100.00, 'Michael', '12345');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_medicines`
--

DROP TABLE IF EXISTS `pharmacy_medicines`;
CREATE TABLE IF NOT EXISTS `pharmacy_medicines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacy_medicines`
--

INSERT INTO `pharmacy_medicines` (`id`, `medicine_name`) VALUES
(1, 'Paracetamol'),
(2, 'Amoxicillin'),
(3, 'Ibuprofen'),
(4, 'Ciprofloxacin'),
(5, 'Novagil');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_orders`
--

DROP TABLE IF EXISTS `pharmacy_orders`;
CREATE TABLE IF NOT EXISTS `pharmacy_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `medicine_name` varchar(255) DEFAULT NULL,
  `dosage` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent_to_pharmacy','sent_to_cashier','paid','seen') DEFAULT 'pending',
  `ordered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `requested_by` varchar(255) DEFAULT NULL,
  `is_sent_to_cashier` tinyint(1) DEFAULT '0',
  `is_paid` tinyint(1) DEFAULT '0',
  `is_seen_by_doctor` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacy_orders`
--

INSERT INTO `pharmacy_orders` (`id`, `patient_id`, `medicine_name`, `dosage`, `status`, `ordered_at`, `requested_by`, `is_sent_to_cashier`, `is_paid`, `is_seen_by_doctor`) VALUES
(1, 143, 'Paracetamol', '455', 'pending', '2025-07-17 15:28:24', '115', 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `prescription_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `prescription_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `medicine_id` int DEFAULT NULL,
  `appointment_id` int DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`prescription_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `fk_medicine` (`medicine_id`),
  KEY `fk_prescriptions_appointment` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `patient_id`, `doctor_id`, `prescription_date`, `notes`, `created_at`, `medicine_id`, `appointment_id`, `summary`) VALUES
(1, 9, 8, '2025-05-06', 'Administered on 2025-05-06 01:30:42', '2025-05-06 01:30:42', 1, NULL, NULL),
(2, 10, 8, '2025-05-05', 'oooo', '2025-05-05 18:30:12', 2, NULL, NULL),
(5, 12, 8, '2025-05-05', 'ok', '2025-05-05 19:51:25', 2, NULL, NULL),
(11, 14, 8, '2025-05-29', 'Administered on 2025-05-29 21:10:18', '2025-05-29 21:10:18', 1, NULL, NULL),
(12, 25, 8, '2025-05-29', 'koool', '2025-05-29 19:56:57', 2, NULL, NULL),
(13, 29, 15, '2025-05-29', 'all', '2025-05-29 20:44:58', 1, NULL, NULL),
(14, 35, 8, '2025-06-29', 'Administered on 2025-06-29 13:57:21', '2025-06-29 13:57:21', 1, NULL, NULL),
(15, 35, 15, '2025-06-29', 'Administered on 2025-06-29 13:57:31', '2025-06-29 13:57:31', 1, NULL, NULL),
(16, 43, 8, '2025-06-29', 'uooi', '2025-06-29 13:06:34', 5, NULL, NULL),
(17, 41, 8, '2025-06-29', 'tuuoo', '2025-06-29 13:07:31', 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
CREATE TABLE IF NOT EXISTS `prescription_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int DEFAULT NULL,
  `medicine_id` int DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `medicine_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `prescription_id_2` (`prescription_id`),
  KEY `medicine_id_2` (`medicine_id`),
  KEY `prescription_id_3` (`prescription_id`),
  KEY `medicine_id_3` (`medicine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`item_id`, `prescription_id`, `medicine_id`, `dosage`, `duration`, `medicine_name`) VALUES
(1, 1, 1, '4', '40', NULL),
(2, 1, 1, '1', '34', NULL),
(3, 5, 2, 'yuuii', '76', NULL),
(4, 7, 1, '4', 'for 1 month', NULL),
(5, 10, 2, '4', '40', NULL),
(6, 11, 1, '4', '30', NULL),
(7, 12, 2, '4', '30', NULL),
(8, 14, 1, '4', '2 months', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(255) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'doctor'),
(3, 'nurse'),
(4, 'patient'),
(5, 'pharmacist'),
(6, 'lab_technician'),
(7, 'receptionist'),
(8, 'Cashier'),
(9, 'Radiologist'),
(10, 'Cleaner');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE IF NOT EXISTS `shifts` (
  `shift_id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `user_id` int DEFAULT NULL,
  `shift_date` date DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`shift_id`),
  KEY `fk_user` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`shift_id`, `shift_name`, `start_time`, `end_time`, `user_id`, `shift_date`, `notes`) VALUES
(17, 'Afternoon (2PM - 8PM)', '14:00:00', '20:00:00', 8, '2025-07-03', 'POA'),
(15, 'Night (8PM - 8AM)', '20:00:00', '08:00:00', 12, '2025-05-24', 'on Saturday'),
(16, 'Morning (8AM - 2PM)', '08:00:00', '14:00:00', 8, '2025-07-02', 'OKOK'),
(14, 'Afternoon (2PM - 8PM)', '14:00:00', '20:00:00', 24, '2025-05-23', 'ok ma ma'),
(11, 'Morning (8AM - 2PM)', '08:00:00', '14:00:00', 8, '2025-05-02', 'ok you do the job well'),
(12, 'Night (8PM - 8AM)', '08:00:00', '14:00:00', 15, '2025-05-12', '222'),
(13, 'Morning (8AM - 2PM)', '08:00:00', '14:00:00', 15, '2025-05-12', 'ok');

-- --------------------------------------------------------

--
-- Table structure for table `shift_assignments`
--

DROP TABLE IF EXISTS `shift_assignments`;
CREATE TABLE IF NOT EXISTS `shift_assignments` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `shift_id` int NOT NULL,
  `shift_date` date NOT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `user_id` (`user_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

DROP TABLE IF EXISTS `treatments`;
CREATE TABLE IF NOT EXISTS `treatments` (
  `treatment_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `notes` text,
  `treatment_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `treatment_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`treatment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `medicine_id` (`medicine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `treatments`
--

INSERT INTO `treatments` (`treatment_id`, `patient_id`, `medicine_id`, `notes`, `treatment_date`, `created_at`, `treatment_name`) VALUES
(1, 33, 2, '2ml', '2025-05-29 00:00:00', '2025-05-29 23:21:44', 'Injection'),
(2, 33, 2, '567', '2025-05-29 23:47:48', '2025-05-29 23:47:48', 'Injection'),
(3, 33, 2, '567', '2025-05-29 23:49:27', '2025-05-29 23:49:27', 'Injection'),
(4, 33, 2, '567', '2025-05-29 23:53:27', '2025-05-29 23:53:27', 'Injection'),
(5, 33, 2, '567', '2025-05-29 23:56:36', '2025-05-29 23:56:36', 'Injection'),
(6, 33, 1, '21', '2025-05-29 23:57:00', '2025-05-29 23:57:00', 'Injection'),
(7, 28, 2, '33', '2025-05-29 00:00:00', '2025-05-29 23:57:29', 'IV Drip'),
(8, 33, 1, '21', '2025-05-29 23:57:35', '2025-05-29 23:57:35', 'Injection'),
(9, 28, 2, '23', '2025-05-29 23:58:00', '2025-05-29 23:58:00', 'IV Drip'),
(10, 28, 1, '2', '2025-05-29 23:58:19', '2025-05-29 23:58:19', 'IV Drip'),
(11, 17, 2, 'Wer', '2025-06-13 00:00:00', '2025-06-13 10:48:05', 'Injection'),
(12, 35, 1, '76', '2025-06-28 15:29:04', '2025-06-28 15:29:04', 'Injection'),
(13, 35, 1, '76', '2025-06-28 20:08:30', '2025-06-28 20:08:30', 'Injection'),
(14, 34, 1, 'oko', '2025-06-28 00:00:00', '2025-06-28 21:23:47', 'IV Drip'),
(15, 35, 1, '76', '2025-06-29 15:12:34', '2025-06-29 15:12:34', 'Injection'),
(16, 34, 5, 'tty', '2025-06-29 15:13:03', '2025-06-29 15:13:03', 'IV Drip'),
(17, 34, 5, '', '2025-06-29 15:13:04', '2025-06-29 15:13:04', 'IV Drip'),
(18, 34, 5, '', '2025-06-29 15:13:06', '2025-06-29 15:13:06', 'IV Drip'),
(19, 43, 5, 'rtt', '2025-06-29 15:13:45', '2025-06-29 15:13:45', 'Injection'),
(20, 34, 5, '', '2025-06-29 15:13:56', '2025-06-29 15:13:56', 'IV Drip'),
(21, 40, 2, 'ppoo', '2025-06-28 00:00:00', '2025-06-29 15:16:06', 'IV Drip'),
(22, 43, 5, 'uuy', '2025-06-29 15:16:52', '2025-06-29 15:16:52', 'IV Drip');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `role_id` int DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `fk_patient_id` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `full_name`, `email`, `password`, `phone`, `created_at`, `profile_image`, `patient_id`) VALUES
(60, 6, 'umoh ekpo Umoh', 'umoh@gmail.com', '$2y$10$FxfJ47iBbuVlL30ihKIBCO/IP88V.DcS40xdmR248lsI24LmsVvD6', '08142728873', '2025-06-26 12:10:28', NULL, NULL),
(12, 5, 'james', 'james@gmail.com', '$2y$10$oJfc2dFa7gomdTPAqwjZfea65zcZ6lX5XvF4oSJKF712Y6j/oqCWW', '08164567894', '2025-05-04 12:39:54', NULL, NULL),
(11, 3, 'Rose', 'rose@gmail.com', '$2y$10$bX6Otnc9yYLzW1xgUK57cOBX6IP0ydHJkjsDU/X6cS0TqW.0zQHJi', '08152728873', '2025-05-04 12:38:48', NULL, NULL),
(8, 2, 'mr. irodia', 'irodialucky@gmail.com', '$2y$10$UgSwuzb3vYdFttUStg0Gk./bZ3HWr.Jc7jczsTHbkGuXYtjLspn4G', '07031199173', '2025-04-30 13:36:33', NULL, NULL),
(9, 1, 'admin', 'admin@gmail.com', '$2y$10$dc/ayin5M90uPo5ah7J0dO2wM4BvlQrvAbeX1xYXr50QdLzEOw63W', '09876543211', '2025-05-01 17:50:06', NULL, NULL),
(112, 3, 'kemuel bashitapwa', 'bashitapwa@gmail.com', '$2y$10$8HxV.2QMJVttNXxSUNZnEuui50ZSoLb/zVU9CkGCtIyYvnSY2J7Yu', '08138243449', '2025-07-15 16:37:58', NULL, NULL),
(115, 2, 'Dr Moses Atumiye', 'atumiyemoses@gmail.com', '$2y$10$fewWTLkUSHX37laP1ZtxXuri8/VipHGBRRHWrwbF4t7wquOBe50jC', '07063471861', '2025-07-17 08:56:38', NULL, NULL),
(116, 3, 'Chisom Okoye', 'okoyecheesom@gmail.com', '$2y$10$MhL9HHgI2xPFdCmN3iZdbOAFEHm7HJYib/cOa.1hMKTSCJrYTQWAO', '09159546155', '2025-07-17 09:15:10', NULL, NULL),
(117, 7, 'Agu Precious Amarachukwu', 'precious@quidax.com', '$2y$10$gR3ZV5AnemmcMZ31oxwFl.vjIQH4xQTURn83cEaYq7erwyA4/NALC', '09078885674', '2025-07-17 10:02:26', '', NULL),
(118, 7, 'Bello Rafatu Omaiyoza', 'bellorafatu552@gmail.com', '$2y$10$ipM8l8IL923l9u0pg7o5.eF7xtnQQI1WRDVVl/2Eqhe7qYZf0FvzG', '08036431527', '2025-07-17 10:33:02', '', NULL),
(119, 7, 'Umoh umoh', 'um@gmail.com', '$2y$10$wvNSZKqCsP1Q4lWllrNpG.0HiFx2yeY4LQemA3BZTSoq/B5nacnUq', '07063471860', '2025-07-17 11:17:40', '', NULL),
(120, 7, 'Drisu Ajuma', 'ajumadrisu@gmail.com', '$2y$10$0pcCfM6W2S8Xmf8KtME7zOyIaGm0LGZL.yZCyTVJKBBf37mwqqZHe', '08066792155', '2025-07-17 11:49:14', '', NULL),
(105, 6, 'BINDE', 'nandulbinde@gmail.com', '$2y$10$8hYIwyEMBP/LXSVDFLRb7eJz8KQlIfOyrwgOZMw.fSF31qWKwlLpu', '07064725570', '2025-07-15 11:48:24', NULL, NULL),
(114, 6, 'MARY-ANN AKUNEBU', 'maryannakunebu@gmail.com', '$2y$10$1v3EdbXZqp9kpWqEZR7d5.Bq7fNSl7G62MVyvzTf4Qztx2JFl/nd2', '07033662727', '2025-07-17 08:50:41', NULL, NULL),
(113, 1, 'CHIAHA AMARACHI NORA', 'nchiaha980@gmail.com', '$2y$10$sByfgbSyaHjNxSEJ6gEkiurQPeoFctVBK0FPZdac8CgF.HZhP2K76', '07064671499', '2025-07-16 15:08:28', NULL, NULL),
(109, 4, 'AGBOOLA ADEOLA ELIZABETH', 'agboolaadeolaelizabeth@gmail.com', '$2y$10$99IsuCbkNULYuFCdLmav9ubyaUnYKXywVLb47qqAZ.l2/Te9E49QK', '08135445752', '2025-07-15 12:51:11', NULL, NULL),
(110, 3, 'cletus edeh faith', 'fcletus402@gmail.com', '$2y$10$ap25HuWlpMEsY48.radqrOxcvvdz9dE6W3iVvQxXqJOkAEoy/wCwS', '09060504651', '2025-07-15 14:15:44', NULL, NULL),
(111, 5, 'uchechi', 'uchechipope2023@gmail.com', '$2y$10$YPh0yvTf./vc59/Von0WMuA6EcW8LSHM9x/O31PkbXkBqqhHQcDDC', '08165664476', '2025-07-15 15:32:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_shifts`
--

DROP TABLE IF EXISTS `user_shifts`;
CREATE TABLE IF NOT EXISTS `user_shifts` (
  `user_shift_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `shift_id` int DEFAULT NULL,
  `shift_date` date DEFAULT NULL,
  `note` text,
  `confirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user_shift_id`),
  KEY `user_id` (`user_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_shifts`
--

INSERT INTO `user_shifts` (`user_shift_id`, `user_id`, `shift_id`, `shift_date`, `note`, `confirmed`) VALUES
(1, 15, 10, '2025-05-19', 'be there on time', 0),
(2, 8, 11, '2025-05-02', 'ok you do the job well', 0),
(3, 15, 12, '0000-00-00', '222', 0),
(4, 15, 13, '2025-05-12', 'ok', 0),
(5, 24, 14, '2025-05-23', 'ok ma ma', 0),
(6, 12, 15, '2025-05-24', 'on Saturday', 0),
(7, 8, 16, '2025-07-02', 'OKOK', 0),
(8, 8, 17, '2025-07-03', 'POA', 0);

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

DROP TABLE IF EXISTS `vital_signs`;
CREATE TABLE IF NOT EXISTS `vital_signs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `temperature` decimal(4,1) NOT NULL,
  `pulse_rate` int NOT NULL,
  `respiration_rate` int NOT NULL,
  `blood_pressure` varchar(10) NOT NULL,
  `oxygen_saturation` int NOT NULL,
  `pain_level` int DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `blood_sugar` decimal(5,2) DEFAULT NULL,
  `consciousness_level` varchar(20) DEFAULT NULL,
  `symptoms_notes` text,
  `vitals_time` time DEFAULT NULL,
  `recorded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `recorded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_patient` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vital_signs`
--

INSERT INTO `vital_signs` (`id`, `patient_id`, `temperature`, `pulse_rate`, `respiration_rate`, `blood_pressure`, `oxygen_saturation`, `pain_level`, `height_cm`, `weight_kg`, `bmi`, `blood_sugar`, `consciousness_level`, `symptoms_notes`, `vitals_time`, `recorded_at`, `recorded_by`, `created_at`) VALUES
(16, 35, 345.0, 34, 456, '120', 456, 10, 45.00, 45.00, NULL, NULL, NULL, NULL, NULL, '2025-06-18 07:02:48', '11', '2025-06-18 06:02:48'),
(15, 35, 345.0, 34, 456, '120', 456, 10, 45.00, 45.00, NULL, NULL, NULL, NULL, NULL, '2025-06-18 07:00:08', '11', '2025-06-18 06:00:08'),
(14, 35, 345.0, 34, 456, '120', 456, 10, 45.00, 45.00, NULL, NULL, NULL, NULL, NULL, '2025-06-18 06:07:43', '11', '2025-06-18 05:07:43'),
(17, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:01:32', '11', '2025-06-23 16:01:32'),
(18, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:10:44', '11', '2025-06-23 16:10:44'),
(19, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:11:52', '11', '2025-06-23 16:11:52'),
(20, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:12:26', '11', '2025-06-23 16:12:26'),
(21, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:24:47', '11', '2025-06-23 16:24:47'),
(22, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:25:28', '11', '2025-06-23 16:25:28'),
(23, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:41:10', '11', '2025-06-23 16:41:10'),
(24, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:45:26', '11', '2025-06-23 16:45:26'),
(25, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:46:10', '11', '2025-06-23 16:46:10'),
(26, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:46:53', '11', '2025-06-23 16:46:53'),
(27, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:47:21', '11', '2025-06-23 16:47:21'),
(28, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:47:35', '11', '2025-06-23 16:47:35'),
(29, 44, 12.0, 12, 12, '120', 23, 10, 123.00, 999.99, 815.65, 123.00, 'Verbal', 'ok', '12:34:00', '2025-06-23 17:47:54', '11', '2025-06-23 16:47:54'),
(30, 35, 122.0, 45, 234, '56', 567, 10, 134.00, 345.00, 192.10, 234.00, 'Verbal', 'NOTED', '12:30:00', '2025-06-23 19:03:17', '11', '2025-06-23 18:03:17'),
(31, 141, 36.7, 134, 22, '120/80', 92, 2, 0.00, 87.10, 0.00, 0.00, 'Alert', 'weakness and fast heartbeat', '11:07:00', '2025-07-17 03:10:56', '116', '2025-07-17 10:10:56'),
(32, 142, 36.2, 101, 20, '120/80', 98, 0, 0.00, 52.90, 0.00, 0.00, 'Alert', '', '11:39:00', '2025-07-17 03:38:16', '116', '2025-07-17 10:38:16'),
(33, 144, 36.6, 77, 18, '110/90', 99, 0, 0.00, 53.30, 0.00, 0.00, 'Alert', '', '12:57:00', '2025-07-17 04:58:08', '116', '2025-07-17 11:58:08');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
