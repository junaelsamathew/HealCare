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
-- Table structure for table `ambulance_contacts`
--

DROP TABLE IF EXISTS `ambulance_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ambulance_contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `driver_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL COMMENT 'Basic / Advanced Life Support',
  `availability` varchar(20) DEFAULT 'Available' COMMENT 'Available / On Duty / Off Duty',
  `location` varchar(100) DEFAULT NULL COMMENT 'Current location or base location',
  `emergency_level` varchar(20) DEFAULT 'Standard' COMMENT 'Standard / Critical',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`contact_id`),
  KEY `idx_availability` (`availability`),
  KEY `idx_phone_number` (`phone_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ambulance_contacts`
--

LOCK TABLES `ambulance_contacts` WRITE;
/*!40000 ALTER TABLE `ambulance_contacts` DISABLE KEYS */;
INSERT INTO `ambulance_contacts` VALUES (1,'Rajesh Kumar','9876543210','KA-01-AB-1234','Advanced Life Support','Available',NULL,'Standard','2026-01-11 20:34:30','2026-01-11 20:34:30'),(2,'Suresh Babu','9876543211','KA-02-CD-5678','Basic','Available',NULL,'Standard','2026-01-11 20:34:30','2026-01-11 20:34:30'),(3,'Mahesh Reddy','9876543212','KA-03-EF-9012','Advanced Life Support','On Duty',NULL,'Standard','2026-01-11 20:34:30','2026-01-11 20:34:30'),(4,'Rajesh Kumar','9876543210','KA-01-AB-1234','Advanced Life Support','Available',NULL,'Standard','2026-01-11 20:42:58','2026-01-11 20:42:58'),(5,'Suresh Babu','9876543211','KA-02-CD-5678','Basic','Available',NULL,'Standard','2026-01-11 20:42:58','2026-01-11 20:42:58'),(6,'Mahesh Reddy','9876543212','KA-03-EF-9012','Advanced Life Support','On Duty',NULL,'Standard','2026-01-11 20:42:58','2026-01-11 20:42:58'),(7,'Rajesh Kumar','9876543210','KA-01-AB-1234','Advanced Life Support','Available',NULL,'Standard','2026-01-11 22:44:39','2026-01-11 22:44:39'),(8,'Suresh Babu','9876543211','KA-02-CD-5678','Basic','Available',NULL,'Standard','2026-01-11 22:44:39','2026-01-11 22:44:39'),(9,'Mahesh Reddy','9876543212','KA-03-EF-9012','Advanced Life Support','On Duty',NULL,'Standard','2026-01-11 22:44:39','2026-01-11 22:44:39');
/*!40000 ALTER TABLE `ambulance_contacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 22:45:19
