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
-- Table structure for table `pharmacy_stock`
--

DROP TABLE IF EXISTS `pharmacy_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(100) NOT NULL,
  `medicine_type` varchar(50) DEFAULT NULL COMMENT 'Tablet / Syrup / Injection / Capsule',
  `manufacturer` varchar(100) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 50 COMMENT 'Minimum stock level for alerts',
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `location` varchar(50) DEFAULT NULL COMMENT 'Shelf location',
  `last_restocked_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`stock_id`),
  KEY `idx_medicine_name` (`medicine_name`),
  KEY `idx_quantity` (`quantity`),
  KEY `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock`
--

LOCK TABLES `pharmacy_stock` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock` DISABLE KEYS */;
INSERT INTO `pharmacy_stock` VALUES (1,'Paracetamol 500mg','Tablet','Cipla',NULL,NULL,25,100,2.50,NULL,NULL,'2026-01-11 20:34:30','2026-01-11 20:34:30'),(2,'Amoxicillin 250mg','Capsule','Sun Pharma',NULL,NULL,30,100,5.00,NULL,NULL,'2026-01-11 20:34:30','2026-01-11 20:34:30'),(3,'Ibuprofen 400mg','Tablet','Dr. Reddy',NULL,NULL,15,50,3.75,NULL,NULL,'2026-01-11 20:34:30','2026-01-11 20:34:30'),(4,'Paracetamol 500mg','Tablet','Cipla',NULL,NULL,25,100,2.50,NULL,NULL,'2026-01-11 20:42:58','2026-01-11 20:42:58'),(5,'Amoxicillin 250mg','Capsule','Sun Pharma',NULL,NULL,30,100,5.00,NULL,NULL,'2026-01-11 20:42:58','2026-01-11 20:42:58'),(6,'Ibuprofen 400mg','Tablet','Dr. Reddy',NULL,NULL,15,50,3.75,NULL,NULL,'2026-01-11 20:42:58','2026-01-11 20:42:58'),(7,'Paracetamol 500mg','Tablet','Cipla',NULL,NULL,25,100,2.50,NULL,NULL,'2026-01-11 22:44:39','2026-01-11 22:44:39'),(8,'Amoxicillin 250mg','Capsule','Sun Pharma',NULL,NULL,30,100,5.00,NULL,NULL,'2026-01-11 22:44:39','2026-01-11 22:44:39'),(9,'Ibuprofen 400mg','Tablet','Dr. Reddy',NULL,NULL,15,50,3.75,NULL,NULL,'2026-01-11 22:44:39','2026-01-11 22:44:39');
/*!40000 ALTER TABLE `pharmacy_stock` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 22:45:22
