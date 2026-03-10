/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| API database
|
*/

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Export table accessibility
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accessibility`;

CREATE TABLE `accessibility` (
  `accessibility_id` bigint(20) unsigned NOT NULL,
  `offer_id` bigint(20) NOT NULL,
  `ginto_id` varchar(255) DEFAULT NULL,
  `ginto_icon` varchar(1000) DEFAULT NULL,
  `ginto_link` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`accessibility_id`,`offer_id`),
  KEY `offer_id_idxfk_10` (`offer_id`),
  CONSTRAINT `accessibility_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table accessibility_rating
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accessibility_rating`;

CREATE TABLE `accessibility_rating` (
  `accessibility_rating_id` bigint(20) NOT NULL,
  `accessibility_id` bigint(20) unsigned NOT NULL,
  `description_de` varchar(500) DEFAULT NULL,
  `description_fr` varchar(500) DEFAULT NULL,
  `description_it` varbinary(500) DEFAULT NULL,
  `description_en` varchar(500) DEFAULT NULL,
  `icon_url` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`accessibility_rating_id`),
  KEY `accessibility_id_idxfk` (`accessibility_id`),
  CONSTRAINT `accessibility_rating_ibfk_1` FOREIGN KEY (`accessibility_id`) REFERENCES `accessibility` (`accessibility_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




# Export table accommodation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accommodation`;

CREATE TABLE `accommodation` (
  `offer_id` bigint(20) DEFAULT NULL,
  `contact` text,
  `is_park_partner` tinyint(4) DEFAULT '0',
  KEY `offer_id_idxfk` (`offer_id`),
  CONSTRAINT `accommodation_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table activity
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity`;

CREATE TABLE `activity` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `start_place_info` text,
  `start_place_altitude` int(11) DEFAULT NULL,
  `goal_place_info` text,
  `goal_place_altitude` int(11) DEFAULT NULL,
  `route_length` decimal(7,2) DEFAULT NULL,
  `untarred_route_length` decimal(7,2) DEFAULT NULL,
  `public_transport_start` varchar(255) DEFAULT NULL,
  `public_transport_stop` varchar(255) DEFAULT NULL,
  `altitude_differential` int(11) DEFAULT NULL,
  `altitude_ascent` int(11) DEFAULT NULL,
  `altitude_descent` int(11) DEFAULT NULL,
  `time_required` varchar(255) DEFAULT NULL,
  `time_required_minutes` int(11) DEFAULT NULL,
  `level_technics` tinyint(1) DEFAULT NULL,
  `level_condition` tinyint(1) DEFAULT NULL,
  `has_playground` tinyint(1) DEFAULT NULL,
  `has_picnic_place` tinyint(1) DEFAULT NULL,
  `has_fireplace` tinyint(1) DEFAULT NULL,
  `has_washrooms` tinyint(1) DEFAULT NULL,
  `poi` text,
  `season_months` varchar(50) DEFAULT NULL,
  `route_condition_id` TINYINT,
  `route_condition_color` VARCHAR(255),
  `route_condition` VARCHAR(500),
  `route_condition_details` VARCHAR(500),
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `activity_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table api
# ------------------------------------------------------------

DROP TABLE IF EXISTS `api`;

CREATE TABLE `api` (
  `initialized` tinyint(1) DEFAULT '0',
  `version` varchar(20) DEFAULT '1.0',
  `last_import` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `api` WRITE;
/*!40000 ALTER TABLE `api` DISABLE KEYS */;

INSERT INTO `api` (`initialized`, `version`, `last_import`)
VALUES
	(1,'19',NULL);

/*!40000 ALTER TABLE `api` ENABLE KEYS */;
UNLOCK TABLES;


# Export table booking
# ------------------------------------------------------------

DROP TABLE IF EXISTS `booking`;

CREATE TABLE `booking` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `is_park_partner` tinyint(1) DEFAULT NULL,
  `min_group_subscriber` int(11) DEFAULT NULL,
  `max_group_subscriber` int(11) DEFAULT NULL,
  `min_individual_subscriber` int(11) DEFAULT NULL,
  `max_individual_subscriber` int(11) DEFAULT NULL,
  `public_transport_stop` varchar(255) DEFAULT NULL,
  `season_months` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `stnet_id` char(1) DEFAULT NULL,
  `alpstein_id` varchar(255) DEFAULT NULL,
  `contact_visible_for_alpstein` tinyint(4) DEFAULT '1',
  `marker` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_id` (`category_id`),
  UNIQUE KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table category_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_i18n`;

CREATE TABLE `category_i18n` (
  `category_id` int(11) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `body` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`,`language`),
  CONSTRAINT `category_i18n_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table category_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_link`;

CREATE TABLE `category_link` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`offer_id`,`category_id`),
  KEY `category_id_idxfk_1` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table document
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document`;

CREATE TABLE `document` (
  `offer_id` bigint(20) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  KEY `offer_id_idxfk_4` (`offer_id`),
  CONSTRAINT `document_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table document
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_intern`;

CREATE TABLE `document_intern` (
  `offer_id` bigint(20) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  KEY `offer_id_idxfk_37` (`offer_id`),
  CONSTRAINT `document_intern_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `event`;

CREATE TABLE `event` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `is_park_event` tinyint(1) DEFAULT NULL,
  `is_park_partner_event` tinyint(1) DEFAULT NULL,
  `public_transport_stop` varchar(255) DEFAULT NULL,
  `kind_of_event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table hyperlink
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hyperlink`;

CREATE TABLE `hyperlink` (
  `offer_id` bigint(20) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  KEY `offer_id_idxfk_9` (`offer_id`),
  CONSTRAINT `hyperlink_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table hyperlink_intern
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hyperlink_intern`;

CREATE TABLE `hyperlink_intern` (
  `offer_id` bigint(20) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  KEY `offer_id_idxfk_36` (`offer_id`),
  CONSTRAINT `hyperlink_intern_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image`;

CREATE TABLE `image` (
  `offer_id` bigint(20) DEFAULT NULL,
  `small` varchar(255) DEFAULT NULL,
  `medium` varchar(255) DEFAULT NULL,
  `large` varchar(255) DEFAULT NULL,
  `original` varchar(255) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  KEY `offer_id_idxfk_11` (`offer_id`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table map_layer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `map_layer`;

CREATE TABLE `map_layer` (
  `map_layer_id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `languages` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `layer_position` tinyint(4) DEFAULT NULL,
  `visible_by_default` tinyint(4) DEFAULT NULL,
  `popup_title` varchar(255) DEFAULT NULL,
  `popup_logo` varchar(255) DEFAULT NULL,
  `popup_logo_width` int(11) DEFAULT NULL,
  `popup_logo_height` int(11) DEFAULT NULL,
  PRIMARY KEY (`map_layer_id`),
  UNIQUE KEY `map_layer_id` (`map_layer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table map_layer_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `map_layer_i18n`;

CREATE TABLE `map_layer_i18n` (
  `map_layer_id` int(11) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `popup_content` text,
  PRIMARY KEY (`map_layer_id`,`language`),
  CONSTRAINT `map_layer_i18n_ibfk_1` FOREIGN KEY (`map_layer_id`) REFERENCES `map_layer` (`map_layer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table offer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer`;

CREATE TABLE `offer` (
  `offer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `park_id` int(11) NOT NULL,
  `park` text NOT NULL,
  `is_hint` tinyint(1) DEFAULT NULL,
  `institution` text,
  `institution_location` varchar(500) DEFAULT NULL,
  `institution_is_park_partner` tinyint(1) DEFAULT NULL,
  `contact` text,
  `contact_is_park_partner` tinyint(1) DEFAULT NULL,
  `barrier_free` tinyint(1) DEFAULT NULL,
  `learning_opportunity` tinyint(1) DEFAULT NULL,
  `child_friendly` tinyint(1) DEFAULT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `keywords` varchar(150) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  UNIQUE KEY `offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table offer_date
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_date`;

CREATE TABLE `offer_date` (
  `offer_date_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) DEFAULT NULL,
  `date_from` datetime DEFAULT NULL,
  `date_to` datetime DEFAULT NULL,
  PRIMARY KEY (`offer_date_id`),
  KEY `offer_id_idxfk_6` (`offer_id`),
  CONSTRAINT `offer_date_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table offer_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_i18n`;

CREATE TABLE `offer_i18n` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `title` varchar(1000) DEFAULT NULL,
  `abstract` varchar(1000) DEFAULT NULL,
  `description_medium` varchar(1000) DEFAULT NULL,
  `description_long` varchar(1500) DEFAULT NULL,
  `details` text,
  `price` text,
  `location_details` varchar(1000) DEFAULT NULL,
  `opening_hours` text,
  `benefits` text,
  `requirements` text,
  `additional_informations` text,
  `catering_informations` text,
  `material_rent` text,
  `safety_instructions` text,
  `signalization` text,
  `other_infrastructure` text,
  `route_url` varchar(255) DEFAULT NULL,
  `costs` text,
  `funding` text,
  `partner` text,
  `remarks` text,
  `online_shop_payment_terms` text,
  `online_shop_delivery_conditions` text,
  PRIMARY KEY (`offer_id`,`language`),
  CONSTRAINT `offer_i18n_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table offer_route
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_route`;

CREATE TABLE `offer_route` (
  `offer_id` bigint(20) DEFAULT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  KEY `offer_id_idxfk_8` (`offer_id`),
  CONSTRAINT `offer_route_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table product
# ------------------------------------------------------------

CREATE TABLE `product` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `public_transport_stop` varchar(255) DEFAULT NULL,
  `number_of_rooms` int(11) DEFAULT NULL,
  `has_conference_room` tinyint(1) DEFAULT NULL,
  `has_playground` tinyint(1) DEFAULT NULL,
  `has_picnic_place` tinyint(1) DEFAULT NULL,
  `has_fireplace` tinyint(1) DEFAULT NULL,
  `has_washrooms` tinyint(1) DEFAULT NULL,
  `season_months` varchar(50) DEFAULT NULL,
  `online_shop_enabled` tinyint(4) DEFAULT NULL,
  `online_shop_price` float(10,2) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table product_article
# ------------------------------------------------------------

CREATE TABLE `product_article` (
  `product_article_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) DEFAULT NULL,
  `supplier_contact` text,
  PRIMARY KEY (`product_article_id`),
  KEY `offer_id_idxfk_40` (`offer_id`),
  CONSTRAINT `product_article_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table product_article_i18n
# ------------------------------------------------------------

CREATE TABLE `product_article_i18n` (
  `product_article_id` bigint(20) NOT NULL,
  `language` char(2) NOT NULL,
  `article_title` varchar(1000) DEFAULT NULL,
  `article_description` text,
  `article_ingredients` text,
  PRIMARY KEY (`product_article_id`,`language`),
  CONSTRAINT `product_article_i18n_ibfk_1` FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table product_article_label
# ------------------------------------------------------------

CREATE TABLE `product_article_label` (
  `product_article_id` bigint(20) NOT NULL,
  `label_id` int(11) NOT NULL,
  `language` char(2) NOT NULL,
  `label_title` varchar(1000) DEFAULT NULL,
  `label_url` varchar(2000) DEFAULT NULL,
  `label_icon` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`product_article_id`,`label_id`,`language`),
  CONSTRAINT `product_article_label_ibfk_1` FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table project
# ------------------------------------------------------------

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `duration_from` int(11) DEFAULT NULL,
  `duration_to` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `poi` text,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table subscription
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscription`;

CREATE TABLE `subscription` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `subscription_mandatory` tinyint(1) DEFAULT NULL,
  `online_subscription_enabled` tinyint(1) DEFAULT NULL,
  `subscription_contact` varchar(255) DEFAULT NULL,
  `subscription_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table subscription_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscription_i18n`;

CREATE TABLE `subscription_i18n` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `subscription_details` text,
  PRIMARY KEY (`offer_id`,`language`),
  CONSTRAINT `subscription_i18n_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table supplier
# ------------------------------------------------------------

DROP TABLE IF EXISTS `supplier`;

CREATE TABLE `supplier` (
  `offer_id` bigint(20) DEFAULT NULL,
  `contact` text,
  `is_park_partner` tinyint(1) DEFAULT NULL,
  KEY `offer_id_idxfk_10` (`offer_id`),
  CONSTRAINT `supplier_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table target_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group`;

CREATE TABLE `target_group` (
  `target_group_id` int(11) NOT NULL DEFAULT '0',
  `sort` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table target_group_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_i18n`;

CREATE TABLE `target_group_i18n` (
  `target_group_id` int(11) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `body` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`target_group_id`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export table target_group_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_link`;

CREATE TABLE `target_group_link` (
  `offer_id` bigint(20) NOT NULL DEFAULT '0',
  `target_group_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`offer_id`,`target_group_id`),
  KEY `target_group_id_idxfk` (`target_group_id`),
  CONSTRAINT `target_group_link_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;