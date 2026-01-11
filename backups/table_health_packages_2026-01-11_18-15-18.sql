-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: healcare
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `health_packages`
--

DROP TABLE IF EXISTS `health_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_packages` (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `package_description` text DEFAULT NULL,
  `included_tests` text DEFAULT NULL COMMENT 'List of tests included in the package',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` int(11) DEFAULT 0,
  `discounted_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `validity_days` int(11) DEFAULT 30 COMMENT 'Package validity in days',
  `status` varchar(20) DEFAULT 'Active' COMMENT 'Active / Inactive',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`package_id`),
  KEY `idx_package_name` (`package_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_packages`
--

LOCK TABLES `health_packages` WRITE;
/*!40000 ALTER TABLE `health_packages` DISABLE KEYS */;
INSERT INTO `health_packages` VALUES (1,'Basic Health Checkup','Complete basic health screening','CBC, Blood Sugar, Blood Pressure, Urine Test',1500.00,20,1200.00,30,'Active','2026-01-11 20:34:30','2026-01-11 20:34:30'),(2,'Comprehensive Health Package','Advanced health screening with imaging','CBC, Lipid Profile, Kidney Function, Liver Function, ECG, X-Ray',3500.00,25,2625.00,30,'Active','2026-01-11 20:34:30','2026-01-11 20:34:30'),(3,'Diabetes Care Package','Complete diabetes monitoring','HbA1c, Fasting Sugar, Post-Prandial Sugar, Kidney Function',2000.00,15,1700.00,30,'Active','2026-01-11 20:34:30','2026-01-11 20:34:30'),(4,'Basic Health Checkup','Complete basic health screening','CBC, Blood Sugar, Blood Pressure, Urine Test',1500.00,20,1200.00,30,'Active','2026-01-11 20:42:58','2026-01-11 20:42:58'),(5,'Comprehensive Health Package','Advanced health screening with imaging','CBC, Lipid Profile, Kidney Function, Liver Function, ECG, X-Ray',3500.00,25,2625.00,30,'Active','2026-01-11 20:42:58','2026-01-11 20:42:58'),(6,'Diabetes Care Package','Complete diabetes monitoring','HbA1c, Fasting Sugar, Post-Prandial Sugar, Kidney Function',2000.00,15,1700.00,30,'Active','2026-01-11 20:42:58','2026-01-11 20:42:58'),(7,'Basic Health Checkup','Complete basic health screening','CBC, Blood Sugar, Blood Pressure, Urine Test',1500.00,20,1200.00,30,'Active','2026-01-11 22:44:39','2026-01-11 22:44:39'),(8,'Comprehensive Health Package','Advanced health screening with imaging','CBC, Lipid Profile, Kidney Function, Liver Function, ECG, X-Ray',3500.00,25,2625.00,30,'Active','2026-01-11 22:44:39','2026-01-11 22:44:39'),(9,'Diabetes Care Package','Complete diabetes monitoring','HbA1c, Fasting Sugar, Post-Prandial Sugar, Kidney Function',2000.00,15,1700.00,30,'Active','2026-01-11 22:44:39','2026-01-11 22:44:39');
/*!40000 ALTER TABLE `health_packages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 22:45:20
