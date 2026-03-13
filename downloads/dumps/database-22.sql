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


# Table accessibility
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accessibility`;

CREATE TABLE `accessibility` (
  `accessibility_id` BIGINT unsigned NOT NULL,
  `offer_id` BIGINT NOT NULL,
  `ginto_id` VARCHAR(255) DEFAULT NULL,
  `ginto_icon` VARCHAR(1000) DEFAULT NULL,
  `ginto_link` VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`accessibility_id`,`offer_id`),
  KEY `offer_id_idxfk_10` (`offer_id`),
  CONSTRAINT `accessibility_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table accessibility_rating
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accessibility_rating`;

CREATE TABLE `accessibility_rating` (
  `accessibility_rating_id` BIGINT NOT NULL,
  `accessibility_id` BIGINT unsigned NOT NULL,
  `description_de` VARCHAR(500) DEFAULT NULL,
  `description_fr` VARCHAR(500) DEFAULT NULL,
  `description_it` varbinary(500) DEFAULT NULL,
  `description_en` VARCHAR(500) DEFAULT NULL,
  `icon_url` VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`accessibility_rating_id`),
  KEY `accessibility_id_idxfk` (`accessibility_id`),
  CONSTRAINT `accessibility_rating_ibfk_1` FOREIGN KEY (`accessibility_id`) REFERENCES `accessibility` (`accessibility_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table accessibility_dropdown
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accessibility_dropdown`;

CREATE TABLE `accessibility_dropdown`
(
  `accessibility_dropdown_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon_url` VARCHAR(1000),
  PRIMARY KEY (`accessibility_dropdown_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table accommodation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accommodation`;

CREATE TABLE `accommodation` (
  `offer_id` BIGINT DEFAULT NULL,
  `contact` TEXT,
  `is_park_partner` TINYINT DEFAULT '0',
  KEY `offer_id_idxfk` (`offer_id`),
  CONSTRAINT `accommodation_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table activity
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity`;

CREATE TABLE `activity` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `start_place_info` TEXT,
  `start_place_altitude` INTEGER DEFAULT NULL,
  `goal_place_info` TEXT,
  `goal_place_altitude` INTEGER DEFAULT NULL,
  `route_length` DECIMAL(7,2) DEFAULT NULL,
  `untarred_route_length` DECIMAL(7,2) DEFAULT NULL,
  `public_transport_start` VARCHAR(255) DEFAULT NULL,
  `public_transport_stop` VARCHAR(255) DEFAULT NULL,
  `altitude_differential` INTEGER DEFAULT NULL,
  `altitude_ascent` INTEGER DEFAULT NULL,
  `altitude_descent` INTEGER DEFAULT NULL,
  `time_required` VARCHAR(255) DEFAULT NULL,
  `time_required_minutes` INTEGER DEFAULT NULL,
  `level_technics` TINYINT DEFAULT NULL,
  `level_condition` TINYINT DEFAULT NULL,
  `has_playground` TINYINT DEFAULT NULL,
  `has_picnic_place` TINYINT DEFAULT NULL,
  `has_fireplace` TINYINT DEFAULT NULL,
  `has_washrooms` TINYINT DEFAULT NULL,
  `poi` TEXT,
  `season_months` VARCHAR(50) DEFAULT NULL,
  `route_condition_id` TINYINT,
  `route_condition_color` VARCHAR(255),
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `activity_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table api
# ------------------------------------------------------------

DROP TABLE IF EXISTS `api`;

CREATE TABLE `api` (
  `initialized` TINYINT DEFAULT '0',
  `version` VARCHAR(20) DEFAULT '1.0',
  `last_import` INTEGER DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `api` WRITE;
/*!40000 ALTER TABLE `api` DISABLE KEYS */;

INSERT INTO `api` (`initialized`, `version`, `last_import`)
VALUES
	(1,'22',NULL);

/*!40000 ALTER TABLE `api` ENABLE KEYS */;
UNLOCK TABLES;


# Table booking
# ------------------------------------------------------------

DROP TABLE IF EXISTS `booking`;

CREATE TABLE `booking` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `is_park_partner` TINYINT DEFAULT NULL,
  `min_group_subscriber` INTEGER DEFAULT NULL,
  `max_group_subscriber` INTEGER DEFAULT NULL,
  `min_individual_subscriber` INTEGER DEFAULT NULL,
  `max_individual_subscriber` INTEGER DEFAULT NULL,
  `public_transport_stop` VARCHAR(255) DEFAULT NULL,
  `season_months` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `category_id` INTEGER NOT NULL AUTO_INCREMENT,
  `parent_id` INTEGER DEFAULT NULL,
  `stnet_id` VARCHAR(10) DEFAULT NULL,
  `alpstein_id` VARCHAR(255) DEFAULT NULL,
  `contact_visible_for_alpstein` TINYINT DEFAULT '1',
  `marker` VARCHAR(255) DEFAULT NULL,
  `sort` INTEGER NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_id` (`category_id`),
  UNIQUE KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table category_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_i18n`;

CREATE TABLE `category_i18n` (
  `category_id` INTEGER NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `body` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`,`language`),
  CONSTRAINT `category_i18n_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table category_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_link`;

CREATE TABLE `category_link` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `category_id` INTEGER NOT NULL DEFAULT '0',
  PRIMARY KEY (`offer_id`,`category_id`),
  KEY `category_id_idxfk_1` (`category_id`),
  CONSTRAINT `category_link_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE,
  CONSTRAINT `category_link_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table document
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document`;

CREATE TABLE `document` (
  `offer_id` BIGINT DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  KEY `offer_id_idxfk_4` (`offer_id`),
  CONSTRAINT `document_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table document
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_intern`;

CREATE TABLE `document_intern` (
  `offer_id` BIGINT DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  KEY `offer_id_idxfk_37` (`offer_id`),
  CONSTRAINT `document_intern_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `event`;

CREATE TABLE `event` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `is_park_event` TINYINT DEFAULT NULL,
  `is_park_partner_event` TINYINT DEFAULT NULL,
  `public_transport_stop` VARCHAR(255) DEFAULT NULL,
  `kind_of_event` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Tables: Fields of activity
# ------------------------------------------------------------

CREATE TABLE `field_of_activity_link` (
  `offer_id` BIGINT,
  `field_of_activity_id` INTEGER,
  PRIMARY KEY (offer_id,field_of_activity_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `field_of_activity`
(
  `field_of_activity_id` INTEGER AUTO_INCREMENT UNIQUE ,
  `sort` INTEGER,
  PRIMARY KEY (`field_of_activity_id`)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `field_of_activity_i18n`
(
  `field_of_activity_id` INTEGER,
  `language` CHAR(2),
  `body` VARCHAR(255),
  PRIMARY KEY (field_of_activity_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;

ALTER TABLE `field_of_activity_i18n` ADD FOREIGN KEY field_of_activity_id_idxfk (field_of_activity_id) REFERENCES field_of_activity (field_of_activity_id) ON DELETE CASCADE;
ALTER TABLE `field_of_activity_link` ADD FOREIGN KEY offer_id_idxfk_42 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;




# Table hyperlink
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hyperlink`;

CREATE TABLE `hyperlink` (
  `offer_id` BIGINT DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  KEY `offer_id_idxfk_9` (`offer_id`),
  CONSTRAINT `hyperlink_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table hyperlink_intern
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hyperlink_intern`;

CREATE TABLE `hyperlink_intern` (
  `offer_id` BIGINT DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  KEY `offer_id_idxfk_36` (`offer_id`),
  CONSTRAINT `hyperlink_intern_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image`;

CREATE TABLE `image` (
  `offer_id` BIGINT DEFAULT NULL,
  `small` VARCHAR(255) DEFAULT NULL,
  `medium` VARCHAR(255) DEFAULT NULL,
  `large` VARCHAR(255) DEFAULT NULL,
  `original` VARCHAR(255) DEFAULT NULL,
  `copyright` VARCHAR(255) DEFAULT NULL,
  KEY `offer_id_idxfk_11` (`offer_id`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table map_layer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `map_layer`;

CREATE TABLE `map_layer` (
  `map_layer_id` INTEGER NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL,
  `languages` VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL,
  `layer_position` TINYINT DEFAULT NULL,
  `visible_by_default` TINYINT DEFAULT NULL,
  `popup_title` VARCHAR(255) DEFAULT NULL,
  `popup_logo` VARCHAR(255) DEFAULT NULL,
  `popup_logo_width` INTEGER DEFAULT NULL,
  `popup_logo_height` INTEGER DEFAULT NULL,
  PRIMARY KEY (`map_layer_id`),
  UNIQUE KEY `map_layer_id` (`map_layer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table map_layer_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `map_layer_i18n`;

CREATE TABLE `map_layer_i18n` (
  `map_layer_id` INTEGER NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `popup_content` TEXT,
  PRIMARY KEY (`map_layer_id`,`language`),
  CONSTRAINT `map_layer_i18n_ibfk_1` FOREIGN KEY (`map_layer_id`) REFERENCES `map_layer` (`map_layer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table offer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer`;

CREATE TABLE `offer` (
  `offer_id` BIGINT NOT NULL AUTO_INCREMENT,
  `park_id` INTEGER NOT NULL,
  `park` text NOT NULL,
  `is_hint` TINYINT DEFAULT NULL,
  `institution` TEXT,
  `institution_location` VARCHAR(500) DEFAULT NULL,
  `institution_is_park_partner` TINYINT DEFAULT NULL,
  `contact` TEXT,
  `contact_is_park_partner` TINYINT DEFAULT NULL,
  `barrier_free` TINYINT DEFAULT NULL,
  `learning_opportunity` TINYINT DEFAULT NULL,
  `child_friendly` TINYINT DEFAULT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `keywords` VARCHAR(150) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  UNIQUE KEY `offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table offer_date
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_date`;

CREATE TABLE `offer_date` (
  `offer_date_id` BIGINT NOT NULL AUTO_INCREMENT,
  `offer_id` BIGINT DEFAULT NULL,
  `date_from` datetime DEFAULT NULL,
  `date_to` datetime DEFAULT NULL,
  PRIMARY KEY (`offer_date_id`),
  KEY `offer_id_idxfk_6` (`offer_id`),
  CONSTRAINT `offer_date_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table offer_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_i18n`;

CREATE TABLE `offer_i18n` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `title` VARCHAR(1000) DEFAULT NULL,
  `abstract` TEXT,
  `description_medium` TEXT,
  `description_long` TEXT,
  `details` TEXT,
  `price` TEXT,
  `location_details` text DEFAULT NULL,
  `opening_hours` TEXT,
  `benefits` TEXT,
  `requirements` TEXT,
  `additional_informations` TEXT,
  `catering_informations` TEXT,
  `material_rent` TEXT,
  `safety_instructions` TEXT,
  `signalization` TEXT,
  `other_infrastructure` TEXT,
  `route_url` text DEFAULT NULL,
  `costs` TEXT,
  `funding` TEXT,
  `partner` TEXT,
  `remarks` TEXT,
  `online_shop_payment_terms` TEXT,
  `online_shop_delivery_conditions` TEXT,
  `project_initial_situation` TEXT,
	`project_goal` TEXT,
	`project_further_information` TEXT,
	`project_results` TEXT,
	`project_partner` TEXT,
  `route_condition` VARCHAR(500),
  `route_condition_details` VARCHAR(500),
  PRIMARY KEY (`offer_id`,`language`),
  CONSTRAINT `offer_i18n_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table offer_route
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_route`;

CREATE TABLE `offer_route` (
  `offer_id` BIGINT DEFAULT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `sort` INTEGER DEFAULT NULL,
  KEY `offer_id_idxfk_8` (`offer_id`),
  CONSTRAINT `offer_route_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table product
# ------------------------------------------------------------

CREATE TABLE `product` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `public_transport_stop` VARCHAR(255) DEFAULT NULL,
  `number_of_rooms` INTEGER DEFAULT NULL,
  `has_conference_room` TINYINT DEFAULT NULL,
  `has_playground` TINYINT DEFAULT NULL,
  `has_picnic_place` TINYINT DEFAULT NULL,
  `has_fireplace` TINYINT DEFAULT NULL,
  `has_washrooms` TINYINT DEFAULT NULL,
  `season_months` VARCHAR(50) DEFAULT NULL,
  `online_shop_enabled` TINYINT DEFAULT NULL,
  `online_shop_price` float(10,2) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table product_article
# ------------------------------------------------------------

CREATE TABLE `product_article` (
  `product_article_id` BIGINT NOT NULL AUTO_INCREMENT,
  `offer_id` BIGINT DEFAULT NULL,
  `supplier_contact` TEXT,
  `is_food` TINYINT DEFAULT NULL,
  PRIMARY KEY (`product_article_id`),
  KEY `offer_id_idxfk_40` (`offer_id`),
  CONSTRAINT `product_article_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table product_article_i18n
# ------------------------------------------------------------

CREATE TABLE `product_article_i18n` (
  `product_article_id` BIGINT NOT NULL,
  `language` char(2) NOT NULL,
  `article_title` VARCHAR(1000) DEFAULT NULL,
  `article_description` TEXT,
  `article_ingredients` TEXT,
  `article_allergens` TEXT,
  `article_nutritional_values` TEXT,
  `article_identity_label` TEXT,
  `article_quantity_indication` TEXT,
  PRIMARY KEY (`product_article_id`,`language`),
  CONSTRAINT `product_article_i18n_ibfk_1` FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table product_article_label
# ------------------------------------------------------------

CREATE TABLE `product_article_label` (
  `product_article_id` BIGINT NOT NULL,
  `label_id` INTEGER NOT NULL,
  `language` char(2) NOT NULL,
  `label_title` VARCHAR(1000) DEFAULT NULL,
  `label_url` VARCHAR(2000) DEFAULT NULL,
  `label_icon` VARCHAR(2000) DEFAULT NULL,
  PRIMARY KEY (`product_article_id`,`label_id`,`language`),
  CONSTRAINT `product_article_label_ibfk_1` FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table project
# ------------------------------------------------------------

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `duration_from` INTEGER DEFAULT NULL,
  `duration_from_month` tinyint DEFAULT NULL,
  `duration_to` INTEGER DEFAULT NULL,
  `duration_to_month` tinyint DEFAULT NULL,
  `status` TINYINT DEFAULT NULL,
  `poi` TEXT,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table subscription
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscription`;

CREATE TABLE `subscription` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `subscription_mandatory` TINYINT DEFAULT NULL,
  `online_subscription_enabled` TINYINT DEFAULT NULL,
  `subscription_contact` VARCHAR(255) DEFAULT NULL,
  `subscription_link` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table subscription_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscription_i18n`;

CREATE TABLE `subscription_i18n` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `subscription_details` TEXT,
  PRIMARY KEY (`offer_id`,`language`),
  CONSTRAINT `subscription_i18n_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table supplier
# ------------------------------------------------------------

DROP TABLE IF EXISTS `supplier`;

CREATE TABLE `supplier` (
  `offer_id` BIGINT DEFAULT NULL,
  `contact` TEXT,
  `is_park_partner` TINYINT DEFAULT NULL,
  KEY `offer_id_idxfk_10` (`offer_id`),
  CONSTRAINT `supplier_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table target_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group`;

CREATE TABLE `target_group` (
  `target_group_id` INTEGER NOT NULL DEFAULT '0',
  `sort` INTEGER DEFAULT NULL,
  PRIMARY KEY (`target_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table target_group_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_i18n`;

CREATE TABLE `target_group_i18n` (
  `target_group_id` INTEGER NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `body` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`target_group_id`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table target_group_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_link`;

CREATE TABLE `target_group_link` (
  `offer_id` BIGINT NOT NULL DEFAULT '0',
  `target_group_id` INTEGER NOT NULL DEFAULT '0',
  PRIMARY KEY (`offer_id`,`target_group_id`),
  KEY `target_group_id_idxfk` (`target_group_id`),
  CONSTRAINT `target_group_link_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table municipality
# ------------------------------------------------------------

DROP TABLE IF EXISTS `municipality`;

CREATE TABLE `municipality`
(
  `municipality_id` INTEGER NOT NULL,
  `park_id` INTEGER NOT NULL,
  `municipality` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`municipality_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Table offer_municipality_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_municipality_link`;

CREATE TABLE `offer_municipality_link` (
  `offer_id` BIGINT NOT NULL,
  `municipality_id` INTEGER NOT NULL,
  PRIMARY KEY (`offer_id`,`municipality_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE offer_municipality_link ADD FOREIGN KEY offer_id_idxfk (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;
ALTER TABLE offer_municipality_link ADD FOREIGN KEY municipality_id_idxfk (municipality_id) REFERENCES municipality (municipality_id) ON DELETE CASCADE;