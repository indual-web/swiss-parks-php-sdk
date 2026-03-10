# Database changes for Paerke API, from Version 1.0 to 1.0.1
# ------------------------------------------------------------

ALTER TABLE `offer` ADD `park_id` INTEGER NOT NULL AFTER `offer_id`;
UPDATE `api` SET `version` = '1.0.1';



# Database changes for Paerke API, from Version 1.0.1 to 1.0.2
# ------------------------------------------------------------

ALTER TABLE `offer` ADD `park_day` TINYINT(1) NOT NULL AFTER `child_friendly`;
ALTER TABLE `offer` ADD `enjoy_week` TINYINT(1) NOT NULL AFTER `park_day`;

ALTER TABLE `image` CHANGE `url` `original` VARCHAR(255)  NULL  DEFAULT NULL;
ALTER TABLE `image` ADD `small` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `offer_id`;
ALTER TABLE `image` ADD `medium` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `small`;
ALTER TABLE `image` ADD `large` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `medium`;

UPDATE `api` SET `version` = '1.0.2';



# Database changes for Paerke API, from Version 1.0.2 to 1.0.3
# ------------------------------------------------------------

ALTER TABLE `category` ADD `marker` VARCHAR(255) NULL;

UPDATE `category` SET `marker` = 'f9cb14' WHERE `category_id` = 1;
UPDATE `category` SET `marker` = 'f60021' WHERE `category_id` = 19;
UPDATE `category` SET `marker` = '4b88fb' WHERE `category_id` = 20;
UPDATE `category` SET `marker` = 'fbff08' WHERE `category_id` = 21;
UPDATE `category` SET `marker` = 'ac79ff' WHERE `category_id` = 22;
UPDATE `category` SET `marker` = 'ce0018' WHERE `category_id` = 63;
UPDATE `category` SET `marker` = 'f941d7' WHERE `category_id` = 64;
UPDATE `category` SET `marker` = '43bef2' WHERE `category_id` = 65;
UPDATE `category` SET `marker` = 'e17f1d' WHERE `category_id` = 66;
UPDATE `category` SET `marker` = 'aa00ff' WHERE `category_id` = 67;
UPDATE `category` SET `marker` = '2bbc60' WHERE `category_id` = 68;
UPDATE `category` SET `marker` = 'f941d7' WHERE `category_id` = 69;
UPDATE `category` SET `marker` = 'a30047' WHERE `category_id` = 70;
UPDATE `category` SET `marker` = '3384cb' WHERE `category_id` = 71;
UPDATE `category` SET `marker` = 'fbff79' WHERE `category_id` = 72;
UPDATE `category` SET `marker` = '4effff' WHERE `category_id` = 73;
UPDATE `category` SET `marker` = '2bbd61' WHERE `category_id` = 74;
UPDATE `category` SET `marker` = '3384cb' WHERE `category_id` = 101;
UPDATE `category` SET `marker` = '356b7a' WHERE `category_id` = 103;
UPDATE `category` SET `marker` = '2bbc60' WHERE `category_id` = 104;
UPDATE `category` SET `marker` = '2bbd61' WHERE `category_id` = 105;
UPDATE `category` SET `marker` = '84ffa7' WHERE `category_id` = 106;

UPDATE `category` SET `marker` = 'f9cb14' WHERE `parent_id` = 1;
UPDATE `category` SET `marker` = 'f60021' WHERE `parent_id` = 19;
UPDATE `category` SET `marker` = '4b88fb' WHERE `parent_id` = 20;
UPDATE `category` SET `marker` = 'fbff08' WHERE `parent_id` = 21;
UPDATE `category` SET `marker` = 'ac79ff' WHERE `parent_id` = 22;
UPDATE `category` SET `marker` = 'ac79ff' WHERE `parent_id` = 42;
UPDATE `category` SET `marker` = 'ac79ff' WHERE `parent_id` = 43;

UPDATE `category` SET `marker` = 'cccccc' WHERE `marker` IS NULL;

UPDATE `api` SET `version` = '1.0.3';



# Database changes for Paerke API, from Version 1.0.3 to 1.0.4
# ------------------------------------------------------------
ALTER TABLE `offer_i18n` ADD `route_url` VARCHAR(255) NULL;
UPDATE `api` SET `version` = '1.0.4';

ALTER TABLE `category` ADD `sort` INT(11) NOT NULL;
ALTER TABLE `offer_i18n` ADD `location_details` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `price`;
INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('108', '20', 'ff0000', '2445');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('108', 'de', 'Berggasthof');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('108', 'en', 'Farm restaurant');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('108', 'fr', 'M&eacute;tairie / Chalet d\'alpage');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('108', 'it', 'Chalet d\'alpeggio');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('109', '22', 'ff99cc', '2220');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('109', 'de', 'Geologie');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('109', 'en', 'Geology');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('109', 'fr', 'G&eacute;ologie');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('109', 'it', 'Geologia');

# Changes to fixtures for `category` and `category_i18n`
# -> see docs/database.sql for latest content



# Database changes for Paerke API, from Version 1.0.4 to 1.0.5
# ------------------------------------------------------------
ALTER TABLE `api` ADD `last_import` INT(11)  NULL  AFTER `version`;
UPDATE `api` SET `version` = '1.0.5';



# Database changes for Paerke API, from Version 1.0.5 to 1.0.6
# ------------------------------------------------------------
INSERT INTO `category` (`category_id`, `parent_id`, `mapped_id`, `marker`, `sort`) VALUES (110, 21, NULL, 'ff0000', 2518);
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (110, 'de', 'H&uuml;tte');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (110, 'fr', 'Cabane et refuge');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (110, 'it', 'Capanna e Rifugi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (110, 'en', 'Alpine hut and shelter');

INSERT INTO `category` (`category_id`, `parent_id`, `mapped_id`, `marker`, `sort`) VALUES (111, 3, NULL, 'ffff00', 3900);
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (111, 'de', 'Schulklassenangebot');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (111, 'fr', 'Offres pour les classes d\'&eacute;cole');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (111, 'it', 'Offerta per classi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (111, 'en', 'Offer for classes');

INSERT INTO `category` (`category_id`, `parent_id`, `mapped_id`, `marker`, `sort`) VALUES (112, 3, NULL, 'ffff00', 3950);
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (112, 'de', 'Gruppenangebot');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (112, 'fr', 'Offres pour les groupes');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (112, 'it', 'Offerta per gruppi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES (112, 'en', 'Offer for groups');

UPDATE `api` SET `version` = '1.0.6';


# Database changes for Paerke API, from Version 1.0.8 to 1.0.9
# ------------------------------------------------------------
DROP TABLE IF EXISTS `map_layer`;

CREATE TABLE `map_layer` (
  `map_layer_id` INTEGER(11) UNIQUE,
  `url` VARCHAR(255),
  `languages` VARCHAR(255),
  `visible_by_default` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`map_layer_id`)
) ENGINE=InnoDB CHARACTER SET=utf8;

UPDATE `api` SET `version` = '1.0.9';


# Database changes for Paerke API, from Version 1.0.9 to 1.0.10
# ------------------------------------------------------------
ALTER TABLE `offer` ADD `contact_is_park_partner` TINYINT(1)  NULL  AFTER `contact`;
ALTER TABLE `offer` ADD `institution_is_park_partner` TINYINT(1)  NULL  AFTER `institution`;
ALTER TABLE `supplier` ADD `is_park_partner` TINYINT(1)  NULL  AFTER `contact`;

UPDATE `api` SET `version` = '1.0.10';


# Database changes for Paerke API, from Version 1.0.12 to 1.0.13
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

DROP TABLE IF EXISTS `map_layer_i18n`;
CREATE TABLE `map_layer_i18n` (
  `map_layer_id` int(11) NOT NULL DEFAULT '0',
  `language` char(2) NOT NULL DEFAULT '',
  `popup_content` text,
  PRIMARY KEY (`map_layer_id`,`language`),
  CONSTRAINT `map_layer_i18n_ibfk_1` FOREIGN KEY (`map_layer_id`) REFERENCES `map_layer` (`map_layer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE subscription
(
offer_id BIGINT,
subscription_mandatory TINYINT(1),
online_subscription_enabled TINYINT(1),
subscription_contact VARCHAR(255),
subscription_link VARCHAR(255),
subscription_details TEXT,
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;
ALTER TABLE subscription ADD FOREIGN KEY offer_id_idxfk_12 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE `booking` DROP `subscription_mandatory`;
ALTER TABLE `booking` DROP `subscription_contact`;
ALTER TABLE `booking` DROP `subscription_link`;
ALTER TABLE `booking` DROP `subscription_details`;
ALTER TABLE `event` DROP `subscription_mandatory`;
ALTER TABLE `event` DROP `subscription_contact`;
ALTER TABLE `event` DROP `subscription_link`;
ALTER TABLE `event` DROP `subscription_details`;

UPDATE `api` SET `version` = '1.0.13';



# Database changes for Paerke API, from Version 1.0.14 to 1.0.15
# ------------------------------------------------------------
INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('114', '79', '0066ff', '2130');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('114', 'de', 'Parkverwaltung');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('114', 'fr', 'Administration du parc');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('114', 'it', 'Gestione dei parcheggi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('114', 'en', 'Park administration');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('115', '22', 'ff99cc', '2247');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('115', 'de', 'Kirche');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('115', 'fr', 'Église');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('115', 'it', 'Chiesa');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('115', 'en', 'Church');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('116', '22', 'ff99cc', '2248');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('116', 'de', 'Burg');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('116', 'fr', 'Château');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('116', 'it', 'Castello');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('116', 'en', 'Castle');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('117', '20', 'ff7b21', '2447');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('117', 'de', 'Direktverkauf');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('117', 'fr', 'Vente directe');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('117', 'it', 'Vendita diretta');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('117', 'en', 'Direct selling');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('118', '20', 'ff7b21', '2448');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('118', 'de', 'Parkverwaltung');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('118', 'fr', 'Administration du parc');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('118', 'it', 'Gestione dei parcheggi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('118', 'en', 'Park administration');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('119', '100', 'd8d8d8', '2632');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('119', 'de', 'Parkverwaltung');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('119', 'fr', 'Administration du parc');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('119', 'it', 'Gestione dei parcheggi');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('119', 'en', 'Park administration');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('120', '100', 'd8d8d8', '2634');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('120', 'de', 'Bewirtschaftete Alp');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('120', 'fr', 'Alpage exploité');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('120', 'it', 'Alpeggio');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('120', 'en', 'Alpine huts offering regional product');

INSERT INTO `category` (`category_id`, `parent_id`, `marker`, `sort`) VALUES ('121', '100', 'd8d8d8', '2636');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('121', 'de', 'Wassersport');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('121', 'fr', 'Sports aquatique');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('121', 'it', 'Sport acquatico');
INSERT INTO `category_i18n` (`category_id`, `language`, `body`) VALUES ('121', 'en', 'Watersports');

UPDATE `api` SET `version` = '1.0.15';


# Database changes for Paerke API, from Version 1.0.17 to 1.0.18
# ------------------------------------------------------------
ALTER TABLE `offer_i18n` MODIFY `location_details` VARCHAR(1000);
UPDATE `api` SET `version` = '1.0.18';


# Database changes for Paerke API, from Version 1.0.18 to 1.0.19
# ------------------------------------------------------------
ALTER TABLE `subscription` DROP `subscription_details`;

CREATE TABLE subscription_i18n
(
offer_id BIGINT,
language CHAR(2),
subscription_details TEXT,
PRIMARY KEY (offer_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;
ALTER TABLE subscription_i18n ADD FOREIGN KEY offer_id_idxfk_14 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

UPDATE `api` SET `version` = '1.0.19';