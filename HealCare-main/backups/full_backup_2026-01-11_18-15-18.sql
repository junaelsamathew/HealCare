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
-- Current Database: `healcare`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `healcare` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `healcare`;

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

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `patient_name` varchar(100) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `appointment_type` varchar(50) DEFAULT 'Walk-in',
  `reason` varchar(255) DEFAULT NULL,
  `token_no` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `payment_status` varchar(20) DEFAULT 'Pending',
  `queue_number` int(11) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (2,35,'',25,'Pediatrics','2026-01-11 00:00:00','16:00:00','Walk-in',NULL,NULL,'Approved','Paid',26,200.00,'2026-01-11 16:55:30');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing`
--

DROP TABLE IF EXISTS `billing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `bill_type` varchar(50) DEFAULT 'OP' COMMENT 'OP / IP / Lab / Pharmacy',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `doctor_id` int(11) DEFAULT NULL,
  `payment_mode` varchar(30) DEFAULT NULL COMMENT 'Cash / Card / UPI / Online',
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  `bill_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`bill_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_appointment` (`appointment_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_bill_date` (`bill_date`),
  CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `billing_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing`
--

LOCK TABLES `billing` WRITE;
/*!40000 ALTER TABLE `billing` DISABLE KEYS */;
INSERT INTO `billing` VALUES (1,35,2,'Consultation',200.00,25,'UPI','Paid','2026-01-11','2026-01-11 22:25:30');
/*!40000 ALTER TABLE `billing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canteen_cart`
--

DROP TABLE IF EXISTS `canteen_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canteen_cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `canteen_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `canteen_cart_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `canteen_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canteen_cart`
--

LOCK TABLES `canteen_cart` WRITE;
/*!40000 ALTER TABLE `canteen_cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `canteen_cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canteen_menu`
--

DROP TABLE IF EXISTS `canteen_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canteen_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `item_category` varchar(50) DEFAULT NULL COMMENT 'Breakfast / Lunch / Dinner / Snacks / Beverages',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `availability` varchar(20) DEFAULT 'Available' COMMENT 'Available / Out of Stock',
  `diet_type` varchar(30) DEFAULT NULL COMMENT 'Veg / Non-Veg / Vegan / Liquid',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  KEY `idx_item_category` (`item_category`),
  KEY `idx_availability` (`availability`)
) ENGINE=InnoDB AUTO_INCREMENT=274 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canteen_menu`
--

LOCK TABLES `canteen_menu` WRITE;
/*!40000 ALTER TABLE `canteen_menu` DISABLE KEYS */;
INSERT INTO `canteen_menu` VALUES (185,'Idli','Breakfast',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=300'),(186,'Dosa','Breakfast',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=300'),(187,'Masala Dosa','Breakfast',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1630406184470-7fd4440ef826?w=300'),(188,'Vada','Breakfast',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1626132646529-5003c40787a7?w=300'),(189,'Upma','Breakfast',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=300'),(190,'Poori','Breakfast',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?w=300'),(191,'Chapati','Breakfast',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1626074353765-517a681e40be?w=300'),(192,'Pongal','Breakfast',NULL,45.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=300'),(193,'Bread Toast','Breakfast',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'),(194,'Bread Butter','Breakfast',NULL,35.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1593001874117-c99c5edbb862?w=300'),(195,'Bread Jam','Breakfast',NULL,35.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1594627044644-f49c73045d1b?w=400'),(196,'Vegetable Sandwich','Breakfast',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400'),(197,'Egg Omelette','Breakfast',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400'),(198,'Boiled Egg','Breakfast',NULL,15.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'),(199,'Egg Bhurji','Breakfast',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1626776878891-628dcd98114f?w=400'),(200,'Egg Sandwich','Breakfast',NULL,70.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'),(201,'Plain Rice','Lunch',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(202,'Vegetable Rice','Lunch',NULL,80.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(203,'Lemon Rice','Lunch',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(204,'Curd Rice','Lunch',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(205,'Tomato Rice','Lunch',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(206,'Sambar Rice','Lunch',NULL,65.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(207,'Veg Fried Rice','Lunch',NULL,100.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'),(208,'Butter Roti','Lunch',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1626074353765-517a681e40be?w=400'),(209,'Parotta','Lunch',NULL,25.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1616070829624-88405a400cc3?w=400'),(210,'Naan','Lunch',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=400'),(211,'Sambar','Lunch',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400'),(212,'Rasam','Lunch',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400'),(213,'Vegetable Curry','Lunch',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400'),(214,'Dal Fry','Lunch',NULL,70.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400'),(215,'Paneer Butter Masala','Lunch',NULL,150.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=400'),(216,'Mixed Veg Curry','Lunch',NULL,80.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400'),(217,'Chicken Curry','Lunch',NULL,180.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1603894584115-f73f2ec851ad?w=400'),(218,'Chicken Fry','Lunch',NULL,160.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1562967914-608f82629710?w=400'),(219,'Fish Curry','Lunch',NULL,200.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400'),(220,'Fish Fry','Lunch',NULL,180.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400'),(221,'Egg Curry','Lunch',NULL,90.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400'),(222,'Egg Fried Rice','Dinner',NULL,120.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'),(223,'Chicken Fried Rice','Dinner',NULL,150.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'),(224,'Maggi','Dinner',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=400'),(225,'Vegetable Soup','Snacks',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400'),(226,'Chicken Soup','Snacks',NULL,90.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400'),(227,'Sandwich','Snacks',NULL,70.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400'),(228,'Samosa','Snacks',NULL,15.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(229,'Veg Cutlet','Snacks',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(230,'Veg Puff','Snacks',NULL,25.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(231,'Bonda','Snacks',NULL,15.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(232,'Pakoda','Snacks',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(233,'French Fries','Snacks',NULL,90.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1630384066202-1777b760b11d?w=400'),(234,'Chips','Snacks',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=400'),(235,'Chicken Puff','Snacks',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(236,'Chicken Cutlet','Snacks',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(237,'Egg Puff','Snacks',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),(238,'Tea','Beverages',NULL,15.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'),(239,'Coffee','Beverages',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400'),(240,'Boost','Beverages',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'),(241,'Horlicks','Beverages',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'),(242,'Lemon Juice','Beverages',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'),(243,'Lime Soda','Beverages',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'),(244,'Fresh Fruit Juice','Beverages',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'),(245,'Buttermilk','Beverages',NULL,25.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'),(246,'Lassi','Beverages',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'),(247,'Soft Drinks','Beverages',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400'),(248,'Gulab Jamun','Desserts',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'),(249,'Jalebi','Desserts',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'),(250,'Laddu','Desserts',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'),(251,'Ice Cream','Desserts',NULL,80.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=400'),(252,'Fruit Salad','Desserts',NULL,100.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=400'),(253,'Payasam','Desserts',NULL,70.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'),(254,'Apple','Desserts',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400'),(255,'Banana','Desserts',NULL,5.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1571771894821-ad996211fdf4?w=400'),(256,'Orange','Desserts',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=400'),(257,'Papaya','Desserts',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1517282004299-31405021e16f?w=400'),(258,'Watermelon','Desserts',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589927986089-35812388d1f4?w=400'),(259,'Soft Rice','Patient Special',NULL,40.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(260,'Plain Khichdi','Patient Special',NULL,60.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'),(261,'Steamed Vegetables','Patient Special',NULL,70.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'),(262,'Oats Porridge','Patient Special',NULL,80.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'),(263,'Ragi Kanji','Patient Special',NULL,50.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'),(264,'Pickle','Snacks',NULL,5.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'),(265,'Papad','Snacks',NULL,10.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'),(266,'Curd','Snacks',NULL,20.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'),(267,'Ghee','Snacks',NULL,30.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'),(268,'Sauce','Snacks',NULL,5.00,'Available',NULL,'2026-01-11 21:13:06','2026-01-11 21:13:06','https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'),(269,'Oatmeal Porridge','Breakfast','Healthy oatmeal with fruits',80.00,'Available','Veg','2026-01-11 22:44:39','2026-01-11 22:44:39',NULL),(270,'Veg Clear Soup','Lunch','Light vegetable soup',60.00,'Available','Veg','2026-01-11 22:44:39','2026-01-11 22:44:39',NULL),(271,'Brown Rice','Lunch','Steamed brown rice',50.00,'Available','Veg','2026-01-11 22:44:39','2026-01-11 22:44:39',NULL),(272,'Grilled Chicken','Lunch','Grilled chicken breast',150.00,'Available','Non-Veg','2026-01-11 22:44:39','2026-01-11 22:44:39',NULL),(273,'Fresh Fruit Juice','Beverages','Seasonal fresh juice',40.00,'Available','Veg','2026-01-11 22:44:39','2026-01-11 22:44:39',NULL);
/*!40000 ALTER TABLE `canteen_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canteen_orders`
--

DROP TABLE IF EXISTS `canteen_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canteen_orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `order_date` date NOT NULL,
  `order_time` time NOT NULL,
  `delivery_location` varchar(100) DEFAULT NULL COMMENT 'Ward / Bed number',
  `order_status` varchar(20) DEFAULT 'Pending' COMMENT 'Pending / Preparing / Delivered / Cancelled',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `menu_id` (`menu_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_order_date` (`order_date`),
  KEY `idx_order_status` (`order_status`),
  CONSTRAINT `canteen_orders_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `canteen_orders_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `canteen_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canteen_orders`
--

LOCK TABLES `canteen_orders` WRITE;
/*!40000 ALTER TABLE `canteen_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `canteen_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canteen_staff`
--

DROP TABLE IF EXISTS `canteen_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canteen_staff` (
  `canteenstaff_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`canteenstaff_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `canteen_staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canteen_staff`
--

LOCK TABLES `canteen_staff` WRITE;
/*!40000 ALTER TABLE `canteen_staff` DISABLE KEYS */;
INSERT INTO `canteen_staff` VALUES (1,32,NULL,NULL,NULL,'Active');
/*!40000 ALTER TABLE `canteen_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaint_logs`
--

DROP TABLE IF EXISTS `complaint_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `complaint_logs` (
  `complaint_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `complaint_type` varchar(50) DEFAULT NULL COMMENT 'Service / Staff / Facility / Medical / Billing',
  `complaint_subject` varchar(200) NOT NULL,
  `complaint_description` text NOT NULL,
  `complaint_date` date NOT NULL,
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Admin or staff member handling the complaint',
  `status` varchar(20) DEFAULT 'Open' COMMENT 'Open / In Progress / Resolved / Closed',
  `resolution` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`complaint_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_complaint_type` (`complaint_type`),
  KEY `idx_status` (`status`),
  KEY `idx_complaint_date` (`complaint_date`),
  CONSTRAINT `complaint_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `complaint_logs_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_logs`
--

LOCK TABLES `complaint_logs` WRITE;
/*!40000 ALTER TABLE `complaint_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `complaint_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_leaves`
--

DROP TABLE IF EXISTS `doctor_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_leaves` (
  `leave_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`leave_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `doctor_leaves_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_leaves`
--

LOCK TABLES `doctor_leaves` WRITE;
/*!40000 ALTER TABLE `doctor_leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('Available','Not Available') DEFAULT 'Available',
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `doctor_id` (`doctor_id`,`day_of_week`),
  CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 200.00,
  `availability_status` enum('Available','On Leave','Busy') DEFAULT 'Available',
  PRIMARY KEY (`doctor_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (13,18,'General Medicine','MBBS, MD',10,'General Medicine / Cardiovascular',NULL,'Senior Consultant',200.00,'Available'),(14,19,'Gynecology','MBBS, MD',10,'Gynecology',NULL,'Senior Consultant',200.00,'Available'),(15,20,'Orthopedics','MBBS, MD',10,'Orthopedics (Bones)',NULL,'Senior Consultant',200.00,'Available'),(16,21,'ENT','MBBS, MD',10,'ENT',NULL,'Senior Consultant',200.00,'Available'),(17,22,'Ophthalmology','MBBS, MD',10,'Ophthalmology',NULL,'Senior Consultant',200.00,'Available'),(18,23,'Dermatology','MBBS, MD',10,'Dermatology',NULL,'Senior Consultant',200.00,'Available'),(19,24,'General Medicine','MBBS, MD',10,'General Medicine / Cardiovascular',NULL,'Senior Consultant',200.00,'Available'),(20,25,'Pediatrics','MBBS, MD',10,'Pediatrics',NULL,'Senior Consultant',200.00,'Available');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

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

--
-- Table structure for table `lab_staff`
--

DROP TABLE IF EXISTS `lab_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_staff` (
  `labstaff_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_type` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`labstaff_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `lab_staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_staff`
--

LOCK TABLES `lab_staff` WRITE;
/*!40000 ALTER TABLE `lab_staff` DISABLE KEYS */;
INSERT INTO `lab_staff` VALUES (1,29,'Pathology',NULL,NULL,NULL,NULL,NULL,'Active'),(2,33,'Radiology',NULL,NULL,NULL,NULL,NULL,'Active');
/*!40000 ALTER TABLE `lab_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_tests`
--

DROP TABLE IF EXISTS `lab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_tests` (
  `labtest_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `test_name` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `test_type` varchar(50) DEFAULT NULL COMMENT 'Blood / X-Ray / Scan / Pathology',
  `sample_collected` varchar(10) DEFAULT 'No',
  `test_date` date DEFAULT NULL,
  `result` text DEFAULT NULL,
  `report_path` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `labstaff_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending' COMMENT 'Pending / In Progress / Completed',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`labtest_id`),
  KEY `labstaff_id` (`labstaff_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_test_date` (`test_date`),
  KEY `idx_appointment` (`appointment_id`),
  CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `lab_tests_ibfk_3` FOREIGN KEY (`labstaff_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_tests`
--

LOCK TABLES `lab_tests` WRITE;
/*!40000 ALTER TABLE `lab_tests` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manual_reports`
--

DROP TABLE IF EXISTS `manual_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `report_title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manual_reports`
--

LOCK TABLES `manual_reports` WRITE;
/*!40000 ALTER TABLE `manual_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `manual_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `lab_test_required` varchar(10) DEFAULT 'No',
  `follow_up_date` date DEFAULT NULL,
  `record_status` varchar(20) DEFAULT 'Open',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_appointment` (`appointment_id`),
  KEY `idx_record_status` (`record_status`),
  CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `medical_records_ibfk_4` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_records`
--

LOCK TABLES `medical_records` WRITE;
/*!40000 ALTER TABLE `medical_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nurses`
--

DROP TABLE IF EXISTS `nurses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nurses` (
  `nurse_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`nurse_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nurses`
--

LOCK TABLES `nurses` WRITE;
/*!40000 ALTER TABLE `nurses` DISABLE KEYS */;
INSERT INTO `nurses` VALUES (1,28,'General',NULL,NULL,NULL,NULL,NULL,'Active');
/*!40000 ALTER TABLE `nurses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_medical_records`
--

DROP TABLE IF EXISTS `patient_medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `patient_type` enum('Inpatient','Outpatient') DEFAULT 'Outpatient',
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription_ref` text DEFAULT NULL,
  `lab_test_required` varchar(10) DEFAULT 'No',
  `attending_doctor` varchar(100) DEFAULT NULL,
  `visit_date` date DEFAULT curdate(),
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_medical_records`
--

LOCK TABLES `patient_medical_records` WRITE;
/*!40000 ALTER TABLE `patient_medical_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_medical_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_profiles`
--

DROP TABLE IF EXISTS `patient_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_profiles` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `registered_date` date DEFAULT curdate(),
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `patient_code` (`patient_code`),
  CONSTRAINT `patient_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_profiles`
--

LOCK TABLES `patient_profiles` WRITE;
/*!40000 ALTER TABLE `patient_profiles` DISABLE KEYS */;
INSERT INTO `patient_profiles` VALUES (2,16,'HC-P-2026-0016','JUNA ELSA MATHEW INT MCA 2023-2028',NULL,NULL,NULL,NULL,NULL,'2026-01-11','Active'),(5,35,'HC-P-2026-6325','ANNA BEN TOM','Female',NULL,NULL,'09539045609','Odackal(H),Inchiyani P.O inchiyani','2026-01-11','Active');
/*!40000 ALTER TABLE `patient_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_vitals`
--

DROP TABLE IF EXISTS `patient_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_vitals` (
  `vital_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `heart_rate` varchar(20) DEFAULT NULL,
  `blood_pressure_systolic` varchar(20) DEFAULT NULL,
  `blood_pressure_diastolic` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `spo2` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vital_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_vitals_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_vitals`
--

LOCK TABLES `patient_vitals` WRITE;
/*!40000 ALTER TABLE `patient_vitals` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_vitals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Cash / Card / UPI / Net Banking / Insurance',
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Success','Pending','Failed') DEFAULT 'Pending',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'Bank or payment gateway transaction reference',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `idx_bill` (`bill_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,35,'2026-01-11','UPI',200.00,'Success','TXN-523547','2026-01-11 22:31:21');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pharmacists`
--

DROP TABLE IF EXISTS `pharmacists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacists` (
  `pharmacist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`pharmacist_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pharmacists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacists`
--

LOCK TABLES `pharmacists` WRITE;
/*!40000 ALTER TABLE `pharmacists` DISABLE KEYS */;
INSERT INTO `pharmacists` VALUES (1,30,NULL,NULL,NULL,NULL,NULL,'Active');
/*!40000 ALTER TABLE `pharmacists` ENABLE KEYS */;
UNLOCK TABLES;

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

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `prescription_date` date NOT NULL,
  `medicine_details` text NOT NULL,
  `dosage` text DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`prescription_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_prescription_date` (`prescription_date`),
  CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receptionists`
--

DROP TABLE IF EXISTS `receptionists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receptionists` (
  `receptionist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `desk_no` varchar(20) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `date_of_join` date DEFAULT NULL,
  `language_known` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`receptionist_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `receptionists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receptionists`
--

LOCK TABLES `receptionists` WRITE;
/*!40000 ALTER TABLE `receptionists` DISABLE KEYS */;
INSERT INTO `receptionists` VALUES (1,31,NULL,NULL,NULL,NULL,NULL,NULL,'Active');
/*!40000 ALTER TABLE `receptionists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `user_type` varchar(20) NOT NULL,
  `staff_type` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `address` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `highest_qualification` varchar(255) DEFAULT NULL,
  `total_experience` varchar(100) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `dept_preference` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `computer_knowledge` varchar(10) DEFAULT NULL,
  `languages_known` varchar(255) DEFAULT NULL,
  `front_desk_exp` varchar(10) DEFAULT NULL,
  `food_handling` varchar(10) DEFAULT NULL,
  `shift_preference` varchar(20) DEFAULT NULL,
  `canteen_job_role` varchar(100) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `additional_details` text DEFAULT NULL,
  `registered_date` date DEFAULT curdate(),
  `relevant_experience` varchar(100) DEFAULT NULL,
  `qualification_details` text DEFAULT NULL,
  `app_id` varchar(50) DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (7,'JUNA ELSA MATHEW INT MCA 2023-2028','junaelsamathew2028@mca.ajce.in',NULL,'$2y$10$GlKQDdo8NOJ39vvT3xhWbeZwg9EZ1DFzl5BnzQDGRurDq3KTM5FuO','patient',NULL,'Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(9,'Dr. Mary Mariam','mary.mariam@healcare.com',NULL,'$2y$10$IqkY7QUpiiad25yCoS8ihOIOSLzLb1j0ezxJfolumCYnvKwkN.0uW','doctor','Doctor','Approved',NULL,'images/doctor-10.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(10,'Dr. Leena Jose','leena.jose@healcare.com',NULL,'$2y$10$OEp2PyKZRsjp2UBegqWLlO6Pm0sgrICGSl4EIDuMCOqTTFI3aSQu.','doctor','Doctor','Approved',NULL,'images/doctor-8.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(11,'Dr. Jacob Mathew','jacob.mathew@healcare.com',NULL,'$2y$10$a817mYUGZaRIBZrBLFoPiOSjR7V7vnQb1cX1KlaMdhQBtYUxceej.','doctor','Doctor','Approved',NULL,'images/doctor-5.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(12,'Dr. Krishnan Manoj','krishnan.manoj@healcare.com',NULL,'$2y$10$fdWC2HPPp5Halwk4MJ6ZseNZYAqXY/D1hf3Vse6qsE/sZ8GQGppfC','doctor','Doctor','Approved',NULL,'images/doctor-1.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(13,'Dr. June Antony','june.antony@healcare.com',NULL,'$2y$10$7ot5UnEJXb2zWzOKeNG/WOFnMkVL6ZgFh00urPtiakVLxxBe8ZXU2','doctor','Doctor','Approved',NULL,'images/doctor-9.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(14,'Dr. Alan Thomas','alan.thomas@healcare.com',NULL,'$2y$10$idABnzbBZwvcoOHmvpo0G.v/E0FVPXVaRP1iOZBFnSfv2IYDluQpy','doctor','Doctor','Approved',NULL,'images/doctor-6.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(15,'Dr. Suresh Kumar','suresh.k@healcare.com',NULL,'$2y$10$HtA3SJuw0TGu2MYP6cjC8OD9Pz6FBbGyw3HScZMlJ1ACmkkgKTFWa','doctor','Doctor','Approved',NULL,'images/doctor-3.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(16,'Maria Vineeth','maria.vineeth@healcare.com',NULL,'$2y$10$J.sWn0lciWteu2VtSArbnu7NeXGfdjfJ7wA9Tl7Q6KHncsNUAGmoK','doctor','Doctor','Approved',NULL,'images/doctor-7.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(17,'Meenu Thomas','meenu.thomas@healcare.com',NULL,'$2y$10$l5ADd3NMtUM7S/doooON8Ova2CGpHmS/Ro8i.wyp/HDIvAULPWEyK','doctor','Doctor','Approved',NULL,'images/doctor-4.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(18,'Kurian Thomas','kurian.thomas@healcare.com',NULL,'$2y$10$54sYtEZixp0u02BlRhD6F.VJompVlEFqNAJK6xwpKnVXg3TjzzQZi','doctor','Doctor','Approved',NULL,'images/doctor-2.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(19,'Gigi Tony','gigi.tony@healcare.com',NULL,'$2y$10$OPlT8gCffILBvUZQhrRK5OJXFjkral..sMIpXX0TW8AIyHIjThn/y','staff','Nurse','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(20,'Ciya John','ciya.john@healcare.com',NULL,'$2y$10$.7lCXX1i7WD1PT.o80Hz4OQcxJuXmeyc.t9.cpRFX3076JQhQE0Ka','staff','Lab Staff','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(21,'Mini Jose','mini.jose@healcare.com',NULL,'$2y$10$veK148K7OTw0NhECha33wuvzSjVeawdE8D1w8s6OlhnpCUw2cqYWm','staff','Pharmacist','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(22,'Ancy James','ancy.james@healcare.com',NULL,'$2y$10$0zMxAr/jMNC.oIzkNH45ruly3F7pf4eAwpGRQBvxs1coNzlF6Oqc6','staff','Receptionist','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(23,'Riya Shibu','riya.shibu@healcare.com',NULL,'$2y$10$3ptNIbM8asEma0TpX6KmeOAZTcfUyygKla/inVQlh5Pjjrrk4q3HS','staff','Canteen Staff','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(24,'Kevin Manuel','kevin.manuel@healcare.com',NULL,'$2y$10$BKTIoymhGhDS1Aer1H1wA.O7uV39d3S0oT2mAXRkv599gxcOxv1Jq','staff','Lab Staff','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL),(26,'ANNA BEN TOM','junaelsamathew@gmail.com','09539045609','$2y$10$DBBqrDjLFMo8PfKjSKRJv.4PrTmS2O3BmLtJLkMk1cMnwJ2ask0ka','patient',NULL,'Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-11',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `report_type` varchar(100) DEFAULT NULL COMMENT 'Lab / Medical / Radiology / Pathology / Prescription',
  `generated_date` date NOT NULL,
  `doctor_id` int(11) DEFAULT NULL COMMENT 'Doctor who requested/generated the report',
  `lab_id` int(11) DEFAULT NULL COMMENT 'References Lab(lab_id), if it is a lab-related report',
  `diagnosis` text DEFAULT NULL COMMENT 'Diagnosis mentioned in the report (if applicable)',
  `status` varchar(50) DEFAULT 'Pending' COMMENT 'Pending / Completed / Reviewed',
  `report_file` varchar(255) DEFAULT NULL COMMENT 'File path or URL if the report is stored digitally',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_status` (`status`),
  KEY `idx_generated_date` (`generated_date`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `permissions` text DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Active',
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'admin','admin@gmail.com','$2y$10$2AwnB8tVvyl8ak2LhuY3YeBDyjerYRpwutfnKzaib61G/q5BQNaaq','admin',NULL,0,'Active',NULL,'2026-01-11 20:15:50',NULL),(16,7,'junaelsamathew2028','junaelsamathew2028@mca.ajce.in','$2y$10$GlKQDdo8NOJ39vvT3xhWbeZwg9EZ1DFzl5BnzQDGRurDq3KTM5FuO','patient',NULL,0,'Active',NULL,'2026-01-11 21:05:56',NULL),(18,9,'mary.mariam','mary.mariam@healcare.com','$2y$10$IqkY7QUpiiad25yCoS8ihOIOSLzLb1j0ezxJfolumCYnvKwkN.0uW','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(19,10,'leena.jose','leena.jose@healcare.com','$2y$10$OEp2PyKZRsjp2UBegqWLlO6Pm0sgrICGSl4EIDuMCOqTTFI3aSQu.','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(20,11,'jacob.mathew','jacob.mathew@healcare.com','$2y$10$a817mYUGZaRIBZrBLFoPiOSjR7V7vnQb1cX1KlaMdhQBtYUxceej.','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(21,12,'krishnan.manoj','krishnan.manoj@healcare.com','$2y$10$fdWC2HPPp5Halwk4MJ6ZseNZYAqXY/D1hf3Vse6qsE/sZ8GQGppfC','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(22,13,'june.antony','june.antony@healcare.com','$2y$10$7ot5UnEJXb2zWzOKeNG/WOFnMkVL6ZgFh00urPtiakVLxxBe8ZXU2','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(23,14,'alan.thomas','alan.thomas@healcare.com','$2y$10$idABnzbBZwvcoOHmvpo0G.v/E0FVPXVaRP1iOZBFnSfv2IYDluQpy','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(24,15,'suresh.k','suresh.k@healcare.com','$2y$10$HtA3SJuw0TGu2MYP6cjC8OD9Pz6FBbGyw3HScZMlJ1ACmkkgKTFWa','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(25,16,'maria.vineeth','maria.vineeth@healcare.com','$2y$10$J.sWn0lciWteu2VtSArbnu7NeXGfdjfJ7wA9Tl7Q6KHncsNUAGmoK','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:35',NULL),(26,17,'meenu.thomas','meenu.thomas@healcare.com','$2y$10$l5ADd3NMtUM7S/doooON8Ova2CGpHmS/Ro8i.wyp/HDIvAULPWEyK','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(27,18,'kurian.thomas','kurian.thomas@healcare.com','$2y$10$54sYtEZixp0u02BlRhD6F.VJompVlEFqNAJK6xwpKnVXg3TjzzQZi','doctor',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(28,19,'gigi.tony','gigi.tony@healcare.com','$2y$10$OPlT8gCffILBvUZQhrRK5OJXFjkral..sMIpXX0TW8AIyHIjThn/y','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(29,20,'ciya.john','ciya.john@healcare.com','$2y$10$.7lCXX1i7WD1PT.o80Hz4OQcxJuXmeyc.t9.cpRFX3076JQhQE0Ka','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(30,21,'mini.jose','mini.jose@healcare.com','$2y$10$veK148K7OTw0NhECha33wuvzSjVeawdE8D1w8s6OlhnpCUw2cqYWm','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(31,22,'ancy.james','ancy.james@healcare.com','$2y$10$0zMxAr/jMNC.oIzkNH45ruly3F7pf4eAwpGRQBvxs1coNzlF6Oqc6','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(32,23,'riya.shibu','riya.shibu@healcare.com','$2y$10$3ptNIbM8asEma0TpX6KmeOAZTcfUyygKla/inVQlh5Pjjrrk4q3HS','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(33,24,'kevin.manuel','kevin.manuel@healcare.com','$2y$10$BKTIoymhGhDS1Aer1H1wA.O7uV39d3S0oT2mAXRkv599gxcOxv1Jq','staff',NULL,0,'Active',NULL,'2026-01-11 21:25:36',NULL),(35,26,'P27064','junaelsamathew@gmail.com','$2y$10$DBBqrDjLFMo8PfKjSKRJv.4PrTmS2O3BmLtJLkMk1cMnwJ2ask0ka','patient',NULL,0,'Active',NULL,'2026-01-11 22:25:30',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
