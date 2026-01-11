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

-- Dump completed on 2026-01-11 22:45:22
