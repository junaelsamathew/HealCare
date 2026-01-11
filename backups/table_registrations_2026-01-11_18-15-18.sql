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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 22:45:22
