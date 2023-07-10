-- MySQL dump 10.9
--
-- Host: mysql3.websitesource.net    Database: gnance_fb
-- ------------------------------------------------------
-- Server version	4.1.21-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `animals`
--

DROP TABLE IF EXISTS `animals`;
CREATE TABLE `animals` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `species_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) default NULL,
  `birthdate` date default NULL,
  `gender` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `animals`
--

LOCK TABLES `animals` WRITE;
/*!40000 ALTER TABLE `animals` DISABLE KEYS */;
INSERT INTO `animals` VALUES (17,6,'Fleur','2006-06-27',2),(18,5,'Fjora','2003-09-25',2);
/*!40000 ALTER TABLE `animals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diets`
--

DROP TABLE IF EXISTS `diets`;
CREATE TABLE `diets` (
  `id` int(11) NOT NULL auto_increment,
  `animal_id` int(10) unsigned NOT NULL default '0',
  `food_type_id` int(10) unsigned NOT NULL default '0',
  `weight` double unsigned NOT NULL default '0',
  `date` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `diets`
--

LOCK TABLES `diets` WRITE;
/*!40000 ALTER TABLE `diets` DISABLE KEYS */;
INSERT INTO `diets` VALUES (1,17,0,3,NULL),(2,17,6,4,NULL),(3,17,8,30,'2006-10-24');
/*!40000 ALTER TABLE `diets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eigil_coughing`
--

DROP TABLE IF EXISTS `eigil_coughing`;
CREATE TABLE `eigil_coughing` (
  `id` int(11) NOT NULL auto_increment,
  `observed` datetime NOT NULL default '0000-00-00 00:00:00',
  `coughs` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `eigil_coughing`
--

LOCK TABLES `eigil_coughing` WRITE;
/*!40000 ALTER TABLE `eigil_coughing` DISABLE KEYS */;
INSERT INTO `eigil_coughing` VALUES (2,'0000-00-00 00:00:00',7);
/*!40000 ALTER TABLE `eigil_coughing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feeds`
--

DROP TABLE IF EXISTS `feeds`;
CREATE TABLE `feeds` (
  `session_id` int(10) unsigned NOT NULL default '0',
  `animal_id` int(10) unsigned NOT NULL default '0',
  `food_type_id` int(10) unsigned NOT NULL default '0',
  `weight` double unsigned NOT NULL default '0',
  PRIMARY KEY  (`session_id`,`animal_id`,`food_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `feeds`
--

LOCK TABLES `feeds` WRITE;
/*!40000 ALTER TABLE `feeds` DISABLE KEYS */;
INSERT INTO `feeds` VALUES (749,18,6,2),(750,18,7,200);
/*!40000 ALTER TABLE `feeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_types`
--

DROP TABLE IF EXISTS `food_types`;
CREATE TABLE `food_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `description` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `food_types`
--

LOCK TABLES `food_types` WRITE;
/*!40000 ALTER TABLE `food_types` DISABLE KEYS */;
INSERT INTO `food_types` VALUES (6,'Mackerel'),(7,'sprat'),(8,'blue whiting');
/*!40000 ALTER TABLE `food_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genders`
--

DROP TABLE IF EXISTS `genders`;
CREATE TABLE `genders` (
  `id` int(11) NOT NULL auto_increment,
  `gender` varchar(8) NOT NULL default '',
  `abbr` char(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `genders`
--

LOCK TABLES `genders` WRITE;
/*!40000 ALTER TABLE `genders` DISABLE KEYS */;
INSERT INTO `genders` VALUES (1,'Male','M'),(2,'Female','F');
/*!40000 ALTER TABLE `genders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `short_name` varchar(16) default NULL,
  `species_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_info`
--

DROP TABLE IF EXISTS `nutrition_info`;
CREATE TABLE `nutrition_info` (
  `id` int(11) NOT NULL auto_increment,
  `food_type_id` int(11) NOT NULL default '0',
  `kcal_per_g` double NOT NULL default '0',
  `date` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `nutrition_info`
--

LOCK TABLES `nutrition_info` WRITE;
/*!40000 ALTER TABLE `nutrition_info` DISABLE KEYS */;
INSERT INTO `nutrition_info` VALUES (5,7,10,'2006-10-23'),(6,6,230,'2006-10-23');
/*!40000 ALTER TABLE `nutrition_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance`
--

DROP TABLE IF EXISTS `performance`;
CREATE TABLE `performance` (
  `id` int(11) NOT NULL auto_increment,
  `performance` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `performance`
--

LOCK TABLES `performance` WRITE;
/*!40000 ALTER TABLE `performance` DISABLE KEYS */;
INSERT INTO `performance` VALUES (5,'ball jump'),(6,'roar'),(7,'growl'),(8,'spin'),(9,'shape');
/*!40000 ALTER TABLE `performance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `time` datetime default '0000-00-00 00:00:00',
  `species_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=752 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (749,'2006-09-25 07:00:00',5),(750,'2006-10-23 23:27:00',5),(751,'2006-10-23 13:29:31',5);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `species`
--

DROP TABLE IF EXISTS `species`;
CREATE TABLE `species` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `genus` varchar(255) NOT NULL default '',
  `species` varchar(255) NOT NULL default '',
  `common_name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `species`
--

LOCK TABLES `species` WRITE;
/*!40000 ALTER TABLE `species` DISABLE KEYS */;
INSERT INTO `species` VALUES (5,'Phocaena','phocaena','Harbor Porpoise'),(6,'Phoca','vitulina','Harbor Seal');
/*!40000 ALTER TABLE `species` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
CREATE TABLE `trainers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `username` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
INSERT INTO `trainers` VALUES (5,'Colleen',''),(6,'Tiffany',''),(7,'Marie Anne','');
/*!40000 ALTER TABLE `trainers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training`
--

DROP TABLE IF EXISTS `training`;
CREATE TABLE `training` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `session_id` int(10) unsigned NOT NULL default '0',
  `animal_id` int(11) NOT NULL default '0',
  `trainer_id` int(10) unsigned NOT NULL default '0',
  `performance` int(11) NOT NULL default '0',
  `description` mediumtext,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `training`
--

LOCK TABLES `training` WRITE;
/*!40000 ALTER TABLE `training` DISABLE KEYS */;
INSERT INTO `training` VALUES (12,749,17,6,7,'');
/*!40000 ALTER TABLE `training` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

