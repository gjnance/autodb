-- MySQL dump 10.13  Distrib 8.0.33, for Linux (x86_64)
--
-- Host: autodb-mysql-server.mysql.database.azure.com    Database: autodb
-- ------------------------------------------------------
-- Server version	5.7.42-log

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

-- Use the database
USE autodb;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_last` varchar(128) NOT NULL DEFAULT '',
  `name_first` varchar(128) NOT NULL DEFAULT '',
  `phone_mobile` varchar(32) DEFAULT '',
  `phone_home` varchar(32) DEFAULT '',
  `phone_work` varchar(32) DEFAULT '',
  `phone_fax` varchar(32) DEFAULT NULL,
  `street` tinytext,
  `locality_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `postcode` varchar(16) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `email1` varchar(128) DEFAULT '',
  `email2` varchar(128) DEFAULT '',
  `birthdate` date DEFAULT NULL,
  `notes` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=261 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (231,'Brown','Charlie','612-345-6789','612-345-6790','612-345-6791','612-345-6792','77 Birch Street',1,1,'55101',1,'charlie@peanuts.com','cbrown@peanuts.com','1950-10-02','Has a dog named Snoopy.'),(232,'Jones','Calvin','513-555-1234','513-555-1235','513-555-1236','513-555-1237','450 Spaceman Spiff Drive',1,1,'45220',1,'calvin@calvinandhobbes.com','cjones@calvinandhobbes.com','1985-11-18','Has an imaginary tiger friend named Hobbes.'),(233,'Andrews','Archie','413-555-7890','413-555-7891','413-555-7892','413-555-7893','123 Riverdale Lane',1,1,'01002',1,'archie@archiecomics.com','aandrews@archiecomics.com','1941-12-22','Has a love triangle with Betty and Veronica.'),(234,'McClure','Dilbert','408-555-4567','408-555-4568','408-555-4569','408-555-4570','987 Cubicle Court',1,1,'95002',1,'dilbert@dilbert.com','dmcclure@dilbert.com','1989-04-16','Lives with Dogbert and works in a cubicle.'),(235,'Flagston','Hi','415-555-8910','415-555-8911','415-555-8912','415-555-8913','123 Main Street',1,1,'94101',1,'hi@hiandlois.com','hflagston@hiandlois.com','1954-10-18','Lives with wife Lois and four children.'),(236,'Lockhorn','Leroy','718-555-2345','718-555-2346','718-555-2347','718-555-2348','12 Suburbia Avenue',1,1,'10001',1,'leroy@lockhorns.com','llockhorn@lockhorns.com','1968-09-09','Often fights with his wife Loretta.'),(237,'Bumstead','Dagwood','914-555-6789','914-555-6790','914-555-6791','914-555-6792','333 Blondie Street',1,1,'10601',1,'dagwood@blondie.com','dbumstead@blondie.com','1930-09-08','Known for his tall sandwiches and napping on the couch.'),(238,'Wilson','Dennis','213-555-1234','213-555-1235','213-555-1236','213-555-1237','56 Menace Way',1,1,'90012',1,'dennis@dennisthemenace.com','dwilson@dennisthemenace.com','1951-03-12','Known as the menace of the neighborhood.'),(239,'Doonesbury','Mike','802-555-7890','802-555-7891','802-555-7892','802-555-7893','777 Walden Street',1,1,'05601',1,'mike@doonesbury.com','mdoonesbury@doonesbury.com','1970-10-26','A politically-minded individual with a witty sense of humor.'),(240,'Oyl','Olive','415-555-4567','415-555-4568','415-555-4569','415-555-4570','999 Popeye Place',1,1,'94101',1,'olive@popeye.com','ooyl@popeye.com','1919-01-17','The longtime girlfriend of Popeye.'),(241,'Patterson','Elly','415-555-8911','415-555-8912','415-555-8913','415-555-8914','33 For Better Or For Worse Lane',1,1,'94101',1,'elly@fbowf.com','epatterson@fbowf.com','1979-09-09','A loving mother and wife in the Patterson family.'),(242,'Valiant','Prince','415-555-1235','415-555-1236','415-555-1237','415-555-1238','888 King Features Blvd',1,1,'94101',1,'prince@valiant.com','pvaliant@valiant.com','1937-02-13','A bold knight in the days of King Arthur.'),(243,'Fooker','Jason','408-555-2346','408-555-2347','408-555-2348','408-555-2349','456 GPF Street',1,1,'95002',1,'jason@gpf-comics.com','jfooker@gpf-comics.com','1998-11-02','A key character in the tech-focused comic strip GPF.'),(244,'Buckles','Get','703-555-7892','703-555-7893','703-555-7894','703-555-7895','222 Doggie Drive',1,1,'20001',1,'get@getfuzzy.com','gbuckles@getfuzzy.com','1999-09-06','The anthropomorphic pet dog of the comic strip Get Fuzzy.'),(245,'Chance','Tank','701-555-1238','701-555-1239','701-555-1240','701-555-1241','999 Gridiron Street',1,1,'58102',1,'tank@tankmcnamara.com','tchance@tankmcnamara.com','1974-07-01','A former professional football player turned sportscaster.'),(246,'Thornapple','Brutus','216-555-4568','216-555-4569','216-555-4570','216-555-4571','1111 Born Loser Road',1,1,'44101',1,'brutus@bornloser.com','bthornapple@bornloser.com','1965-05-10','The hapless and luckless protagonist of The Born Loser.'),(247,'Duplex','Eno','805-555-8914','805-555-8915','805-555-8916','805-555-8917','666 Dog Street',1,1,'93101',1,'eno@duplex.com','eduplex@duplex.com','1980-02-04','The misanthropic dog owner of The Duplex.'),(248,'Madison','Amber','212-555-2340','212-555-2341','212-555-2342','212-555-2343','444 Luann Avenue',1,1,'10001',1,'amber@luann.com','amadison@luann.com','1985-03-17','The popular girl in Luann, an American syndicated comic strip.'),(249,'Funky','Winkerbean','216-555-6782','216-555-6783','216-555-6784','216-555-6785','111 Westview Street',1,1,'44101',1,'funky@funky.com','fwinkerbean@funky.com','1972-03-27','The title character of the long-running comic strip Funky Winkerbean.'),(250,'Binkley','Michael','408-555-7896','408-555-7897','408-555-7898','408-555-7899','333 Bloom County Blvd',1,1,'95002',1,'michael@bloomcounty.com','mbinkley@bloomcounty.com','1980-12-08','A major character from the comic strip Bloom County.'),(251,'Jughead','Jones','413-555-5678','413-555-5679','413-555-5680','413-555-5681','999 Burger Street',1,1,'01002',1,'jughead@archiecomics.com','jjones@archiecomics.com','1941-12-22','Loves to eat and is best friends with Archie Andrews.'),(252,'Zonker','Harris','802-555-2345','802-555-2346','802-555-2347','802-555-2348','222 Walden Street',1,1,'05601',1,'zonker@doonesbury.com','zharris@doonesbury.com','1970-10-26','Known for his laid-back perspective in the comic strip Doonesbury.'),(253,'Ketcham','Dennis','213-555-4567','213-555-4568','213-555-4569','213-555-4570','56 Menace Way',1,1,'90012',1,'dennis@dennisthemenace.com','dketcham@dennisthemenace.com','1951-03-12','A young boy who always means well, but often gets into trouble.'),(254,'Griffith','Zippy','415-555-7891','415-555-7892','415-555-7893','415-555-7894','123 Dingburg Street',1,1,'94101',1,'zippy@zippythepinhead.com','zgriffith@zippythepinhead.com','1971-03-07','A microcephalic with a joyous, enthusiastic, and unfocused personality.'),(255,'Dinkle','Harold','513-555-1237','513-555-1238','513-555-1239','513-555-1240','456 Scapegoat Street',1,1,'45220',1,'harold@funkywinkerbean.com','hdinkle@funkywinkerbean.com','1972-03-27','Former band director at Westview High School.'),(256,'Bumstead','Blondie','914-555-6783','914-555-6784','914-555-6785','914-555-6786','333 Blondie Street',1,1,'10601',1,'blondie@blondie.com','bbumstead@blondie.com','1930-09-08','Dagwood Bumstead\'s patient and understanding wife.'),(257,'Trudeau','Doonesbury','802-555-3456','802-555-3457','802-555-3458','802-555-3459','777 Walden Street',1,1,'05601',1,'doonesbury@doonesbury.com','dtrudeau@doonesbury.com','1970-10-26','A character from the comic strip Doonesbury, known for its political and social commentary.'),(258,'Smith','Cathy','408-555-6789','408-555-6790','408-555-6791','408-555-6792','321 Women\'s Issues Lane',1,1,'95002',1,'cathy@cathy.com','csmith@cathy.com','1976-11-22','Known for her struggles with the four basic guilt groups: food, love, mom, and work.'),(259,'Wilson','Gahan','312-555-2341','312-555-2342','312-555-2343','312-555-2344','123 Spooky Street',1,1,'60601',1,'gahan@spooky.com','gwilson@spooky.com','1964-01-01','Known for his uniquely eerie cartoons.'),(260,'Mauldin','Willie','212-555-4560','212-555-4561','212-555-4562','212-555-4563','777 Up Front Street',1,1,'10001',1,'willie@upfront.com','mmauldin@upfront.com','1945-01-01','One of two World War II infantrymen in the comic strip Up Front.');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Comic Stripia','CS');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localities`
--

DROP TABLE IF EXISTS `localities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `localities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locality` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localities`
--

LOCK TABLES `localities` WRITE;
/*!40000 ALTER TABLE `localities` DISABLE KEYS */;
INSERT INTO `localities` VALUES (1,'Comic Town');
/*!40000 ALTER TABLE `localities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (1,'Imagination Land');
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `autodb_rules`
--

LOCK TABLES `autodb_rules` WRITE;
/*!40000 ALTER TABLE `autodb_rules` DISABLE KEYS */;
INSERT INTO `autodb_rules` VALUES ('autodb.contacts','country_id','','','','autodb.countries','id','name'),('autodb.contacts','locality_id','','','','autodb.localities','id','locality'),('autodb.contacts','region_id','','','','autodb.regions','id','region');
/*!40000 ALTER TABLE `autodb_rules` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;