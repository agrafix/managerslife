-- MySQL dump 10.13  Distrib 5.5.40, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: managerslife
-- ------------------------------------------------------
-- Server version	5.5.40-0ubuntu0.12.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bank_account`
--

DROP TABLE IF EXISTS `bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `balance` bigint(19) unsigned NOT NULL DEFAULT '0',
  `lastCalc` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `bank_account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=742 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_locker`
--

DROP TABLE IF EXISTS `bank_locker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_locker` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `amount` tinyint(3) unsigned DEFAULT NULL,
  `param` set('1') COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `item_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  KEY `index_foreignkey_item` (`item_id`),
  CONSTRAINT `bank_locker_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `bank_locker_ibfk_4` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blackjack`
--

DROP TABLE IF EXISTS `blackjack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blackjack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_cards` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dealer_cards` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `bid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `blackjack_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_message`
--

DROP TABLE IF EXISTS `chat_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `map` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `visible_for_id` int(11) unsigned DEFAULT NULL,
  `player_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`visible_for_id`),
  KEY `player_id` (`player_id`),
  CONSTRAINT `chat_message_ibfk_1` FOREIGN KEY (`visible_for_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `chat_message_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=611 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `balance` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `lastCalc` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `company_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=445 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_crule`
--

DROP TABLE IF EXISTS `company_crule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_crule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `crule_id` int(11) unsigned DEFAULT NULL,
  `company_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_e85a398c83e06f840707003b2d93d8ba5132057b` (`company_id`,`crule_id`),
  KEY `index_for_company_crule_crule_id` (`crule_id`),
  KEY `index_for_company_crule_company_id` (`company_id`),
  CONSTRAINT `company_crule_ibfk_1` FOREIGN KEY (`crule_id`) REFERENCES `crule` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_crule_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_machines`
--

DROP TABLE IF EXISTS `company_machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_machines` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `calculator` tinyint(3) unsigned DEFAULT NULL,
  `smartphone` tinyint(3) unsigned DEFAULT NULL,
  `notebook` tinyint(3) unsigned DEFAULT NULL,
  `fridge` tinyint(3) unsigned DEFAULT NULL,
  `chair` tinyint(3) unsigned DEFAULT NULL,
  `table` tinyint(3) unsigned DEFAULT NULL,
  `pants` tinyint(3) unsigned DEFAULT NULL,
  `shirt` tinyint(3) unsigned DEFAULT NULL,
  `magazine` tinyint(3) unsigned DEFAULT NULL,
  `silicon` tinyint(3) unsigned DEFAULT NULL,
  `plastic` tinyint(3) unsigned DEFAULT NULL,
  `color` tinyint(3) unsigned DEFAULT NULL,
  `cloth` tinyint(3) unsigned DEFAULT NULL,
  `paper` tinyint(3) unsigned DEFAULT NULL,
  `chip` tinyint(3) unsigned DEFAULT NULL,
  `display` tinyint(3) unsigned DEFAULT NULL,
  `company_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_company` (`company_id`),
  CONSTRAINT `company_machines_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=444 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_order`
--

DROP TABLE IF EXISTS `company_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned DEFAULT NULL,
  `order_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_bacb16ed96e74085cf1e18a1842ebce8283b7376` (`company_id`,`order_id`),
  KEY `index_for_company_order_company_id` (`company_id`),
  KEY `index_for_company_order_order_id` (`order_id`),
  CONSTRAINT `company_order_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_order_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_products`
--

DROP TABLE IF EXISTS `company_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `calculator` int(11) unsigned NOT NULL DEFAULT '0',
  `smartphone` int(11) unsigned NOT NULL DEFAULT '0',
  `notebook` int(11) unsigned NOT NULL DEFAULT '0',
  `fridge` int(11) unsigned NOT NULL DEFAULT '0',
  `chair` int(11) unsigned NOT NULL DEFAULT '0',
  `table` int(11) unsigned NOT NULL DEFAULT '0',
  `pants` int(11) unsigned NOT NULL DEFAULT '0',
  `shirt` int(11) unsigned NOT NULL DEFAULT '0',
  `magazine` int(11) unsigned NOT NULL DEFAULT '0',
  `company_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_company` (`company_id`),
  CONSTRAINT `company_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=444 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_quest`
--

DROP TABLE IF EXISTS `company_quest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_quest` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `valid_until` int(11) unsigned DEFAULT NULL,
  `completed` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1257 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_quest_user`
--

DROP TABLE IF EXISTS `company_quest_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_quest_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `company_quest_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_51127753cbe5a49057f3f2ffe428f0adebaf6cae` (`company_quest_id`,`user_id`),
  KEY `index_for_company_quest_user_user_id` (`user_id`),
  KEY `index_for_company_quest_user_company_quest_id` (`company_quest_id`),
  CONSTRAINT `company_quest_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_quest_user_ibfk_2` FOREIGN KEY (`company_quest_id`) REFERENCES `company_quest` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1255 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_ress`
--

DROP TABLE IF EXISTS `company_ress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_ress` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned DEFAULT NULL,
  `glas` int(11) unsigned NOT NULL DEFAULT '0',
  `gold` int(11) unsigned NOT NULL DEFAULT '0',
  `aluminium` int(11) unsigned NOT NULL DEFAULT '0',
  `sand` int(11) unsigned NOT NULL DEFAULT '0',
  `oil` int(11) unsigned NOT NULL DEFAULT '0',
  `wool` int(11) unsigned NOT NULL DEFAULT '0',
  `cotton` int(11) unsigned NOT NULL DEFAULT '0',
  `wood` int(11) unsigned NOT NULL DEFAULT '0',
  `silicon` int(11) unsigned NOT NULL DEFAULT '0',
  `plastic` int(11) unsigned NOT NULL DEFAULT '0',
  `color` int(11) unsigned NOT NULL DEFAULT '0',
  `cloth` int(11) unsigned NOT NULL DEFAULT '0',
  `paper` int(11) unsigned NOT NULL DEFAULT '0',
  `chip` int(11) unsigned NOT NULL DEFAULT '0',
  `display` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_company` (`company_id`),
  CONSTRAINT `company_ress_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=444 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cron_log`
--

DROP TABLE IF EXISTS `cron_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) unsigned DEFAULT NULL,
  `function` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `msg` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=748 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `crule`
--

DROP TABLE IF EXISTS `crule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_limit` int(11) unsigned NOT NULL DEFAULT '0',
  `r_price` int(11) unsigned NOT NULL DEFAULT '0',
  `until` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `homepage_posts`
--

DROP TABLE IF EXISTS `homepage_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `homepage_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `homepage_posts_user`
--

DROP TABLE IF EXISTS `homepage_posts_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `homepage_posts_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `homepage_posts_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_2829e57c30e737732b904003339ed38aa212ac47` (`homepage_posts_id`,`user_id`),
  KEY `index_for_homepage_posts_user_homepage_posts_id` (`homepage_posts_id`),
  KEY `index_for_homepage_posts_user_user_id` (`user_id`),
  CONSTRAINT `homepage_posts_user_ibfk_1` FOREIGN KEY (`homepage_posts_id`) REFERENCES `homepage_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homepage_posts_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `amount` tinyint(3) unsigned DEFAULT NULL,
  `param` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `item_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  KEY `index_foreignkey_item` (`item_id`),
  CONSTRAINT `inventory_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `inventory_ibfk_4` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1043 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usable` tinyint(3) unsigned DEFAULT NULL,
  `can_carry` tinyint(3) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` bigint(19) unsigned NOT NULL DEFAULT '0',
  `usable_link_desc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_map_npc`
--

DROP TABLE IF EXISTS `item_map_npc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_map_npc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `map_npc_id` int(11) unsigned DEFAULT NULL,
  `item_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_47263d62695b4ef366d3cf8d94ff66ca57760daf` (`item_id`,`map_npc_id`),
  KEY `index_for_item_map_npc_map_npc_id` (`map_npc_id`),
  KEY `index_for_item_map_npc_item_id` (`item_id`),
  CONSTRAINT `item_map_npc_ibfk_1` FOREIGN KEY (`map_npc_id`) REFERENCES `map_npc` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_map_npc_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_npc`
--

DROP TABLE IF EXISTS `map_npc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_npc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `x` tinyint(3) unsigned DEFAULT NULL,
  `y` tinyint(3) unsigned DEFAULT NULL,
  `map` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `can_walk` tinyint(3) unsigned DEFAULT NULL,
  `characterImage` tinyint(3) unsigned DEFAULT NULL,
  `lookDirection` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_object`
--

DROP TABLE IF EXISTS `map_object`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_object` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `x` tinyint(3) unsigned NOT NULL,
  `y` tinyint(3) unsigned NOT NULL,
  `map` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_position`
--

DROP TABLE IF EXISTS `map_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_position` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `x` int(11) unsigned DEFAULT NULL,
  `y` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `map` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `map_position_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `market_price`
--

DROP TABLE IF EXISTS `market_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `market_price` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  `value` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `r_amount` int(11) unsigned NOT NULL DEFAULT '0',
  `price` int(11) unsigned DEFAULT NULL,
  `date` int(11) unsigned DEFAULT NULL,
  `automatic` tinyint(3) unsigned DEFAULT NULL,
  `a_expires` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=716 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_log`
--

DROP TABLE IF EXISTS `payment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `errorID` tinyint(3) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` tinyint(5) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `function` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `poker_message`
--

DROP TABLE IF EXISTS `poker_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poker_message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) unsigned NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `poker_player`
--

DROP TABLE IF EXISTS `poker_player`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poker_player` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cards` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bid` int(11) unsigned DEFAULT NULL,
  `all_in` tinyint(3) unsigned DEFAULT NULL,
  `all_in_amount` int(11) unsigned DEFAULT NULL,
  `game_state` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `poker_player_poker_round`
--

DROP TABLE IF EXISTS `poker_player_poker_round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poker_player_poker_round` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `poker_player_id` int(11) unsigned DEFAULT NULL,
  `poker_round_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_16efa212f675267c63ae85998fb4edd68f5719ba` (`poker_player_id`,`poker_round_id`),
  KEY `index_for_poker_player_poker_round_poker_player_id` (`poker_player_id`),
  KEY `index_for_poker_player_poker_round_poker_round_id` (`poker_round_id`),
  CONSTRAINT `poker_player_poker_round_ibfk_1` FOREIGN KEY (`poker_player_id`) REFERENCES `poker_player` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poker_player_poker_round_ibfk_2` FOREIGN KEY (`poker_round_id`) REFERENCES `poker_round` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `poker_player_user`
--

DROP TABLE IF EXISTS `poker_player_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poker_player_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `poker_player_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_e2808156853ae2aa21147b417aaab58db3dea303` (`poker_player_id`,`user_id`),
  KEY `index_for_poker_player_user_poker_player_id` (`poker_player_id`),
  KEY `index_for_poker_player_user_user_id` (`user_id`),
  CONSTRAINT `poker_player_user_ibfk_1` FOREIGN KEY (`poker_player_id`) REFERENCES `poker_player` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poker_player_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `poker_round`
--

DROP TABLE IF EXISTS `poker_round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poker_round` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `step` tinyint(3) unsigned DEFAULT NULL,
  `cards` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ttl` int(11) unsigned DEFAULT NULL,
  `current_player_id` int(11) unsigned DEFAULT NULL,
  `global_pot` int(11) unsigned NOT NULL DEFAULT '0',
  `current_player` set('1') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_poker_player` (`current_player_id`),
  CONSTRAINT `poker_round_ibfk_1` FOREIGN KEY (`current_player_id`) REFERENCES `poker_player` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quests_npc`
--

DROP TABLE IF EXISTS `quests_npc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quests_npc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `startnpc_id` int(11) unsigned NOT NULL,
  `stopnpc_id` int(11) unsigned NOT NULL,
  `quest_id` int(11) unsigned NOT NULL,
  `accepted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `accept_time` int(11) unsigned NOT NULL DEFAULT '0',
  `complete_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_map_npc` (`startnpc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1944 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quests_npc_user`
--

DROP TABLE IF EXISTS `quests_npc_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quests_npc_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quests_npc_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_93a64c197d26432def54369c3bf4a34f23e0a8da` (`quests_npc_id`,`user_id`),
  KEY `index_for_quests_npc_user_quests_npc_id` (`quests_npc_id`),
  KEY `index_for_quests_npc_user_user_id` (`user_id`),
  CONSTRAINT `quests_npc_user_ibfk_1` FOREIGN KEY (`quests_npc_id`) REFERENCES `quests_npc` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quests_npc_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1938 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `start_time` int(11) unsigned DEFAULT NULL,
  `expires` int(11) unsigned DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `register_date` int(11) unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(3) unsigned DEFAULT NULL,
  `activation_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `referee_id` int(11) unsigned DEFAULT NULL,
  `referee_awarded` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `characterImage` tinyint(3) unsigned DEFAULT NULL,
  `cash` bigint(19) unsigned NOT NULL DEFAULT '0',
  `xp` int(11) unsigned NOT NULL DEFAULT '0',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `last_pass_reset` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`referee_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`referee_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1175 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_premium`
--

DROP TABLE IF EXISTS `user_premium`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_premium` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `points` int(11) unsigned DEFAULT NULL,
  `until` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `auto` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user` (`user_id`),
  CONSTRAINT `user_premium_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1060 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_right`
--

DROP TABLE IF EXISTS `user_right`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_right` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_user_right`
--

DROP TABLE IF EXISTS `user_user_right`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_user_right` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `user_right_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_105fc315d1af51844e6c3aae0545c4016446de3d` (`user_id`,`user_right_id`),
  KEY `index_for_user_user_right_user_id` (`user_id`),
  KEY `index_for_user_user_right_user_right_id` (`user_right_id`),
  CONSTRAINT `user_user_right_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_user_right_ibfk_2` FOREIGN KEY (`user_right_id`) REFERENCES `user_right` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-02-04 17:19:57
