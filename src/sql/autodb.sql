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

-- Create the database
CREATE DATABASE IF NOT EXISTS autodb;

-- Use the database
USE autodb;

--
-- Table structure for table `autodb_prefs`
--

DROP TABLE IF EXISTS `autodb_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `autodb_prefs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dbtable` varchar(128) DEFAULT NULL,
  `var` varchar(64) DEFAULT NULL,
  `value` varchar(64) DEFAULT NULL,
  `user` varchar(64) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autodb_rules`
--

DROP TABLE IF EXISTS `autodb_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `autodb_rules` (
  `adb_t1` varchar(128) NOT NULL DEFAULT '',
  `adb_t1_relcol` varchar(128) NOT NULL DEFAULT '',
  `adb_t2_remhost` varchar(255) DEFAULT NULL,
  `adb_t2_rempass` varchar(64) DEFAULT NULL,
  `adb_t2_remuser` varchar(64) DEFAULT NULL,
  `adb_t2` varchar(128) NOT NULL DEFAULT '',
  `adb_t2_relcol` varchar(128) NOT NULL DEFAULT '',
  `adb_t2_dspcol` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`adb_t1`,`adb_t1_relcol`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;