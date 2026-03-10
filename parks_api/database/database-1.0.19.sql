/*
|---------------------------------------------------------------
| parks.swiss API
| http://angebote.paerke.ch/api
|
| API Version 1.0.14
|---------------------------------------------------------------
*/


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Export accommodation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accommodation`;

CREATE TABLE `accommodation` (
  `offer_id` bigint(20) default NULL,
  `contact` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export activity
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity`;

CREATE TABLE `activity` (
  `offer_id` bigint(20) NOT NULL default '0',
  `start_place_info` text,
  `start_place_altitude` int(11) default NULL,
  `goal_place_info` text,
  `goal_place_altitude` int(11) default NULL,
  `route_length` decimal(7,2) default NULL,
  `untarred_route_length` decimal(7,2) default NULL,
  `public_transport_start` varchar(255) default NULL,
  `public_transport_stop` varchar(255) default NULL,
  `altitude_differential` int(11) default NULL,
  `altitude_ascent` int(11) default NULL,
  `altitude_descent` int(11) default NULL,
  `time_required` varchar(255) default NULL,
  `level_technics` tinyint(1) default NULL,
  `level_condition` tinyint(1) default NULL,
  `has_playground` tinyint(1) default NULL,
  `has_picnic_place` tinyint(1) default NULL,
  `has_fireplace` tinyint(1) default NULL,
  `has_washrooms` tinyint(1) default NULL,
  `poi` text default NULL,
  PRIMARY KEY  (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export api
# ------------------------------------------------------------

DROP TABLE IF EXISTS `api`;

CREATE TABLE `api` (
  `initialized` tinyint(1) default '0',
  `version` varchar(20) default '1.0',
  `last_import` int(11) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `api` WRITE;
/*!40000 ALTER TABLE `api` DISABLE KEYS */;

INSERT INTO `api` (`initialized`, `version`, `last_import`)
VALUES
	(1,'1.0.19',0);

/*!40000 ALTER TABLE `api` ENABLE KEYS */;
UNLOCK TABLES;


# Export booking
# ------------------------------------------------------------

DROP TABLE IF EXISTS `booking`;

CREATE TABLE `booking` (
  `offer_id` bigint(20) NOT NULL default '0',
  `is_park_partner` tinyint(1) default NULL,
  `min_group_subscriber` int(11) default NULL,
  `max_group_subscriber` int(11) default NULL,
  `min_individual_subscriber` int(11) default NULL,
  `max_individual_subscriber` int(11) default NULL,
  `public_transport_stop` varchar(255) default NULL,
  PRIMARY KEY  (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Export category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL auto_increment,
  `parent_id` int(11) default NULL,
  `mapped_id` char(1) default NULL,
  `marker` varchar(255) default NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY  (`category_id`),
  UNIQUE KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;

INSERT INTO `category` (`category_id`, `parent_id`, `mapped_id`, `marker`, `sort`)
VALUES
	(1,0,NULL,'ffcc00',1000),
	(2,0,NULL,'cccccc',2000),
	(3,0,NULL,'ffff00',3000),
	(4,0,NULL,'cccccc',4000),
	(5,1,'D','ffcc00',1010),
	(6,1,'I','ffcc00',1020),
	(7,1,'A','ffcc00',1030),
	(8,1,'B','ffcc00',1040),
	(9,1,'C','ffcc00',1050),
	(10,1,'D','ffcc00',1060),
	(11,1,'E','ffcc00',1070),
	(12,1,'F','ffcc00',1080),
	(13,1,'G','ffcc00',1090),
	(14,1,'H','ffcc00',1100),
	(15,1,'I','ffcc00',1110),
	(16,1,'K','ffcc00',1120),
	(17,1,'J','ffcc00',1130),
	(18,1,NULL,'ffcc00',1140),
	(19,2,NULL,'93dbff',2300),
	(20,2,NULL,'ff7b21',2400),
	(21,2,NULL,'ff0000',2500),
	(22,2,NULL,'ff99cc',2200),
	(25,19,NULL,'93dbff',2310),
	(26,19,NULL,'93dbff',2320),
	(27,19,NULL,'93dbff',2330),
	(28,20,NULL,'ff7b21',2410),
	(29,20,NULL,'ff7b21',2420),
	(30,20,NULL,'ff7b21',2430),
	(31,20,NULL,'ff7b21',2440),
	(32,20,NULL,'ff7b21',2450),
	(33,21,NULL,'ff0000',2505),
	(34,21,NULL,'ff0000',2510),
	(35,21,NULL,'ff0000',2515),
	(36,21,NULL,'ff0000',2520),
	(37,21,NULL,'ff0000',2525),
	(38,21,NULL,'ff0000',2530),
	(39,21,NULL,'ff0000',2535),
	(40,21,NULL,'ff0000',2540),
	(41,21,NULL,'ff0000',2545),
	(44,3,NULL,'ffff00',3100),
	(45,3,NULL,'ffff00',3200),
	(46,3,NULL,'ffff00',3300),
	(47,3,NULL,'ffff00',3400),
	(48,3,NULL,'ffff00',3500),
	(49,3,NULL,'ffff00',3600),
	(50,4,NULL,'cccccc',4100),
	(51,4,NULL,'cccccc',4200),
	(53,22,NULL,'ff99cc',2210),
	(54,22,NULL,'ff99cc',2215),
	(55,22,NULL,'ff99cc',2220),
	(56,22,NULL,'ff99cc',2225),
	(57,22,NULL,'ff99cc',2230),
	(59,22,NULL,'ff99cc',2235),
	(60,22,NULL,'ff99cc',2240),
	(61,22,NULL,'ff99cc',2250),
	(62,22,NULL,'ff99cc',2255),
	(63,50,NULL,'974807',4110),
	(64,50,NULL,'7dd53b',4120),
	(65,50,NULL,'699fd6',4130),
	(66,50,NULL,'c79602',4140),
	(67,50,NULL,'9b7bb4',4160),
	(68,50,NULL,'7030a0',4180),
	(69,51,NULL,'ff00ff',4210),
	(70,51,NULL,'699fd6',4220),
	(71,79,NULL,'0066ff',2110),
	(72,51,NULL,'c79602',4230),
	(73,51,NULL,'9b7bb4',4240),
	(74,51,NULL,'974807',4250),
	(75,51,NULL,'7030a0',4260),
	(78,50,NULL,'699fd6',4150),
	(79,2,NULL,'0066ff',2100),
	(80,22,NULL,'ff99cc',2245),
	(81,100,NULL,'d8d8d8',2620),
	(82,50,NULL,'376091',4170),
	(100,2,NULL,'d8d8d8',2600),
	(101,79,NULL,'0066ff',2120),
	(102,22,NULL,'ff99cc',2205),
	(103,100,NULL,'d8d8d8',2610),
	(104,100,NULL,'d8d8d8',2630),
	(105,100,NULL,'d8d8d8',2640),
	(106,100,NULL,'d8d8d8',2650),
	(107,3,NULL,'ffff00',3800),
	(108,20,NULL,'ff7b21',2445),
	(109,22,NULL,'ff99cc',2220),
	(110,21,NULL,'ff0000',2518),
	(111,3,NULL,'ffff00',3900),
	(112,3,NULL,'ffff00',3950),
	(113,22,NULL,'ff99cc',2253);

/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;


# Export category_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_i18n`;

CREATE TABLE `category_i18n` (
  `category_id` int(11) NOT NULL default '0',
  `language` char(2) NOT NULL default '',
  `body` varchar(255) default NULL,
  PRIMARY KEY  (`category_id`,`language`),
  CONSTRAINT `category_i18n_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `category_i18n` WRITE;
/*!40000 ALTER TABLE `category_i18n` DISABLE KEYS */;

INSERT INTO `category_i18n` (`category_id`, `language`, `body`)
VALUES
	(1,'de','Veranstaltung'),
	(1,'en','Event'),
	(1,'fr','Ev&eacute;nement'),
	(1,'it','Evento'),
	(2,'de','Produkt / Verpflegung / Beherbergung / Sehenswürdigkeit'),
	(2,'en','Product / Gastronomy / Accomodation / Sight/Place of interest'),
	(2,'fr','Produit / Restauration / H&eacute;bergement / Curiosit&eacute;'),
	(2,'it','Prodotto / approvvigionamento / alloggio / punto d\'interesse'),
	(3,'de','Pauschalangebote'),
	(3,'en','Bookable offer'),
	(3,'fr','Offre r&eacute;servable'),
	(3,'it','Offerta prenotabile'),
	(4,'de','Aktivitäten'),
	(4,'en','Activities'),
	(4,'fr','Activit&eacute;s'),
	(4,'it','Attività'),
	(5,'de','Natur / Landschaft'),
	(5,'en','Nature / Landscape'),
	(5,'fr','Nature / paysage'),
	(5,'it','Natura / paesaggio'),
	(6,'de','Exkursion / geführte Wanderung'),
	(6,'en','Excursion / Guided tour'),
	(6,'fr','Excursion / randonn&eacute;e accompagn&eacute;e'),
	(6,'it','Escursione / gita guidata'),
	(7,'de','Konzert / Musical'),
	(7,'en','Concert / Musical'),
	(7,'fr','Concert / com&eacute;die musicale'),
	(7,'it','Concerto / musical'),
	(8,'de','Theater / Kino'),
	(8,'en','Theater / Cinema'),
	(8,'fr','Th&eacute;&acirc;tre / cin&eacute;ma'),
	(8,'it','Teatro / cinema'),
	(9,'de','Kunst / Handwerk'),
	(9,'en','Art / Artisanry'),
	(9,'fr','Art / artisanat'),
	(9,'it','Arte / artigianato'),
	(10,'de','Messe / Ausstellung'),
	(10,'en','Fair / Exhibition'),
	(10,'fr','Foire / Manifestation'),
	(10,'it','Fiera / esposizione'),
	(11,'de','Brauchtum / Markt'),
	(11,'en','Custom / Market'),
	(11,'fr','Coutumes / March&eacute;'),
	(11,'it','Usanza / mercato'),
	(12,'de','Festival / Fest'),
	(12,'en','Festival / Festivity'),
	(12,'fr','Festival / F&ecirc;te'),
	(12,'it','Festival / festa'),
	(13,'de','Kongress / Kurs / Seminar'),
	(13,'en','Congress / Course / Seminar'),
	(13,'fr','Congr&egrave;s / cours / s&eacute;minaire'),
	(13,'it','Congresso / corso / seminario'),
	(14,'de','Sport'),
	(14,'en','Sport'),
	(14,'fr','Sport'),
	(14,'it','Sport'),
	(15,'de','Kinderprogramm'),
	(15,'en','Children\'s programme'),
	(15,'fr','Programme pour enfants'),
	(15,'it','Programma per bambini'),
	(16,'de','Kulinarik'),
	(16,'en','Gastronomy'),
	(16,'fr','Gastronomie'),
	(16,'it','Gastronomia'),
	(17,'de','Versammlung'),
	(17,'en','Meeting'),
	(17,'fr','Assembl&eacute;e'),
	(17,'it','Collezione'),
	(18,'de','Weitere'),
	(18,'en','Further'),
	(18,'fr','Autre'),
	(18,'it','Altro'),
	(19,'de','Regionales Produkt'),
	(19,'en','Regional product'),
	(19,'fr','Produit r&eacute;gional'),
	(19,'it','Prodotto regionale'),
	(20,'de','Verpflegung'),
	(20,'en','Gastronomy'),
	(20,'fr','Restauration'),
	(20,'it','Approvvigionamento'),
	(21,'de','Beherbergung'),
	(21,'en','Accomodation'),
	(21,'fr','H&eacute;bergement'),
	(21,'it','Alloggio'),
	(22,'de','Sehenswürdigkeit'),
	(22,'en','Sight / Place of interest'),
	(22,'fr','Curiosit&eacute;'),
	(22,'it','Punto d\'interesse'),
	(25,'de','Nahrungsmittel'),
	(25,'en','Aliment'),
	(25,'fr','Produits alimentaires'),
	(25,'it','Alimentari'),
	(26,'de','Handwerk'),
	(26,'en','Artisanry'),
	(26,'fr','Artisanat'),
	(26,'it','Artigianato'),
	(27,'de','Weitere'),
	(27,'en','Further'),
	(27,'fr','Autre'),
	(27,'it','Altro'),
	(28,'de','Restaurant / Tea-Room / Cafe'),
	(28,'en','Restaurant / Tea-Room / Cafe'),
	(28,'fr','Restaurant / Tea-Room / Caf&eacute;'),
	(28,'it','Ristorante / Tea-Room / Caffè'),
	(29,'de','Laden'),
	(29,'en','Shop'),
	(29,'fr','Boutique'),
	(29,'it','Negozio'),
	(30,'de','Picknickstelle'),
	(30,'en','Picnic-place'),
	(30,'fr','Place de pique-nique'),
	(30,'it','Spiazzo per picnic'),
	(31,'de','Feuerstelle'),
	(31,'en','Fireplace'),
	(31,'fr','Foyer pour feu'),
	(31,'it','Spiazzo per accendere fuochi'),
	(32,'de','Weitere'),
	(32,'en','Further'),
	(32,'fr','Autre'),
	(32,'it','Altro'),
	(33,'de','Hotel'),
	(33,'en','Hotel'),
	(33,'fr','H&ocirc;tel'),
	(33,'it','Hotel'),
	(34,'de','Pension'),
	(34,'en','Guest house'),
	(34,'fr','Pension'),
	(34,'it','Pensione'),
	(35,'de','Bed and Breakfast'),
	(35,'en','Bed and Breakfast'),
	(35,'fr','Bed and Breakfast'),
	(35,'it','Bed and Breakfast'),
	(36,'de','Jugendherberge / Backpackers'),
	(36,'en','Youth hostel / Backpackers'),
	(36,'fr','Auberge de jeunesse / Backpackers'),
	(36,'it','Albergo della gioventù / Backpackers'),
	(37,'de','Gruppenunterkunft'),
	(37,'en','Group accomodation'),
	(37,'fr','H&eacute;bergement de groupe'),
	(37,'it','Alloggio per gruppi'),
	(38,'de','Agrotourismus'),
	(38,'en','Agrotourism'),
	(38,'fr','Agrotourisme'),
	(38,'it','Agroturismo'),
	(39,'de','Zimmer / Ferienwohnung'),
	(39,'en','Room / Holiday flat'),
	(39,'fr','Chambre / appartement de vacances'),
	(39,'it','Camera / Appartamento di vacanza'),
	(40,'de','Zeltplatz / Camping'),
	(40,'en','Camping site / Camping'),
	(40,'fr','Emplacement pour tente / Camping'),
	(40,'it','Campeggio'),
	(41,'de','Weitere'),
	(41,'en','Further'),
	(41,'fr','Autre'),
	(41,'it','Altro'),
	(44,'de','Naturerlebnis'),
	(44,'en','Nature experience'),
	(44,'fr','D&eacute;couverte nature'),
	(44,'it','Esperienza nella natura'),
	(45,'de','Kulturerlebnis'),
	(45,'en','Culture experience'),
	(45,'fr','D&eacute;couverte culture'),
	(45,'it','Esperienza culturale'),
	(46,'de','Kulinarik'),
	(46,'en','Culinary'),
	(46,'fr','Activit&eacute; culinaire'),
	(46,'it','Gastronomia'),
	(47,'de','Freiwilligeneinsatz'),
	(47,'en','Volunteering'),
	(47,'fr','Engagement de b&eacute;n&eacute;voles'),
	(47,'it','Volontariato'),
	(48,'de','Sport / Freizeit'),
	(48,'en','Sport / Free-time'),
	(48,'fr','Sport / Loisir'),
	(48,'it','Sport / tempo libero'),
	(49,'de','Geführte Tour / Exkursion'),
	(49,'en','Guided tour / Excursion'),
	(49,'fr','Itin&eacute;raire accompagn&eacute; / Excursion'),
	(49,'it','Tour organizzato / escursione'),
	(50,'de','Sommeraktivitäten'),
	(50,'en','Summer-Activities'),
	(50,'fr','Activit&eacute;s estivales'),
	(50,'it','Attività estiva'),
	(51,'de','Winteraktivitäten'),
	(51,'en','Winter-Activities'),
	(51,'fr','Activit&eacute;s hivernales'),
	(51,'it','Attività invernale'),
	(53,'de','Naturdenkmal'),
	(53,'en','Natural monument'),
	(53,'fr','Curiosit&eacute; naturelle'),
	(53,'it','Monumento naturale'),
	(54,'de','Fauna'),
	(54,'en','Fauna'),
	(54,'fr','Faune'),
	(54,'it','Fauna'),
	(55,'de','Flora'),
	(55,'en','Flora'),
	(55,'fr','Flore'),
	(55,'it','Flora'),
	(56,'de','Naturlandschaft / Habitat'),
	(56,'en','Natural landscape / Habitat'),
	(56,'fr','Paysage naturel / habitat'),
	(56,'it','Paesaggio naturale / habitat naturale'),
	(57,'de','Kulturlandschaft'),
	(57,'en','Cultural landscape'),
	(57,'fr','Paysage rural'),
	(57,'it','Paesaggio culturale'),
	(59,'de','Ortsbild'),
	(59,'en','Site'),
	(59,'fr','Site culturel'),
	(59,'it','Insediamento'),
	(60,'de','Baudenkmal'),
	(60,'en','Historical building'),
	(60,'fr','Construction &agrave; caract&egrave;re historique'),
	(60,'it','Costruzione storica'),
	(61,'de','Museum / Ausstellung'),
	(61,'en','Museum / Exhibition'),
	(61,'fr','Mus&eacute;e / Exposition'),
	(61,'it','Museo / Esposizione'),
	(62,'de','Weitere'),
	(62,'en','Further'),
	(62,'fr','Autre'),
	(62,'it','Altro'),
	(63,'de','Themenweg'),
	(63,'en','Theme-Trail'),
	(63,'fr','Parcours &agrave; th&egrave;me'),
	(63,'it','Percorso tematico'),
	(64,'de','Wanderung'),
	(64,'en','Hikingroute'),
	(64,'fr','Randonn&eacute;e'),
	(64,'it','Escursione'),
	(65,'de','Veloroute'),
	(65,'en','Cyclingroute'),
	(65,'fr','Parcours v&eacute;lo'),
	(65,'it','Percorso ciclabile'),
	(66,'de','Mountainbiketour'),
	(66,'en','Mountainbikeroute'),
	(66,'fr','Parcours VTT'),
	(66,'it','Percorso per MBT'),
	(67,'de','Skatingtour'),
	(67,'en','Skatingroute'),
	(67,'fr','Parcours pour patins &agrave; roulettes'),
	(67,'it','Percorso per skating'),
	(68,'de','Weitere'),
	(68,'en','Other'),
	(68,'fr','Autre'),
	(68,'it','Altro'),
	(69,'de','Schneeschuhtour'),
	(69,'en','Snow shoe-route'),
	(69,'fr','Parcours en raquettes'),
	(69,'it','Sentiero per ciaspole'),
	(70,'de','Winterwanderung'),
	(70,'en','Winterhikingroute'),
	(70,'fr','Randonn&eacute;e hivernale'),
	(70,'it','Escursione invernale'),
	(71,'de','Besucherzentrum'),
	(71,'en','Visitorcenter'),
	(71,'fr','Centre de visiteurs'),
	(71,'it','Centro visita'),
	(72,'de','Tourenskiroute'),
	(72,'en','Tourenskiroute'),
	(72,'fr','Parcours de ski de randonn&eacute;e'),
	(72,'it','Sentiero per pelli di foca'),
	(73,'de','Langlaufstrecke'),
	(73,'en','Crosscountryroute'),
	(73,'fr','Piste de ski de fonds'),
	(73,'it','Percorso per sci di fondo'),
	(74,'de','Schlittelweg'),
	(74,'en','Sledgetrail'),
	(74,'fr','Piste de luge'),
	(74,'it','Piste de luge'),
	(75,'de','Weitere'),
	(75,'en','Other'),
	(75,'fr','Autre'),
	(75,'it','Altro'),
	(78,'de','E-Bike Routen'),
	(78,'en','E-bike itineraries'),
	(78,'fr','Itin&eacute;raires E-Bike'),
	(78,'it','Itinerari per biciclette elettriche'),
	(79,'de','Information'),
	(79,'en','Information'),
	(79,'fr','Information'),
	(79,'it','Informazione'),
	(80,'de','Historischer Ort'),
	(80,'en','Historical place'),
	(80,'fr','Lieu historique'),
	(80,'it','Luogo d\'importanza storica'),
	(81,'de','Spielplatz'),
	(81,'en','Playground'),
	(81,'fr','Aire de jeu'),
	(81,'it','Parco giochi'),
	(82,'de','Reitroute'),
	(82,'en','Bridal path'),
	(82,'fr','Sentier &eacute;questre'),
	(82,'it','Sentiero equestre'),
	(100,'de','Infrastruktur'),
	(100,'en','Infrastructure'),
	(100,'fr','Infrastructure'),
	(100,'it','Infrastruttura'),
	(101,'de','Informationsstelle'),
	(101,'en','Informationcenter'),
	(101,'fr','Centre d\'information'),
	(101,'it','Ufficio informazione'),
	(102,'de','Aussichtspunkt'),
	(102,'en','Point of view'),
	(102,'fr','Point de vue'),
	(102,'it','Belvedere'),
	(103,'de','Sportgeräteverleih'),
	(103,'en','Rental for sports equipment'),
	(103,'fr','Location de mat&eacute;riel de sport'),
	(103,'it','Noleggio materiale sportivo'),
	(104,'de','Sommerinfrastruktur'),
	(104,'en','Infrastructure Summer'),
	(104,'fr','Infrastructure d\'&eacute;t&eacute;'),
	(104,'it','Infrastrutture estive'),
	(105,'de','Winterinfrastruktur'),
	(105,'en','Infrastructure Winter'),
	(105,'fr','Infrastructure d\'hiver'),
	(105,'it','Infrastrutture invernali'),
	(106,'de','Skigebiet'),
	(106,'en','Skiing-region'),
	(106,'fr','Domaine skiable'),
	(106,'it','Regione sciistica'),
	(107,'de','Klassenlager'),
	(107,'en','School camp'),
	(107,'fr','Camp pour classes d\'&eacute;cole'),
	(107,'it','Settimane scolastiche'),
	(108,'de','Berggasthof'),
	(108,'en','Farm restaurant'),
	(108,'fr','M&eacute;tairie / Chalet d\'alpage'),
	(108,'it','Chalet d\'alpeggio'),
	(109,'de','Geologie'),
	(109,'en','Geology'),
	(109,'fr','G&eacute;ologie'),
	(109,'it','Geologia'),
	(110,'de','H&uuml;tte'),
	(110,'en','Alpine hut and shelter'),
	(110,'fr','Cabane et refuge'),
	(110,'it','Capanna e Rifugi'),
	(111,'de','Schulklassenangebot'),
	(111,'en','Offer for classes'),
	(111,'fr','Offres pour les classes d\'&eacute;cole'),
	(111,'it','Offerta per classi'),
	(112,'de','Gruppenangebot'),
	(112,'en','Offer for groups'),
	(112,'fr','Offres pour les groupes'),
	(112,'it','Offerta per gruppi'),
	(113,'de','Gew&auml;sser'),
	(113,'en','Waters'),
	(113,'fr','Eaux'),
	(113,'it','Acque');

/*!40000 ALTER TABLE `category_i18n` ENABLE KEYS */;
UNLOCK TABLES;


# Export category_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_link`;

CREATE TABLE `category_link` (
  `offer_id` bigint(20) NOT NULL default '0',
  `category_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`offer_id`,`category_id`),
  KEY `category_id_idxfk_1` (`category_id`),
  CONSTRAINT `category_link_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `category_link_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export document
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document`;

CREATE TABLE `document` (
  `offer_id` bigint(20) default NULL,
  `language` char(2) default NULL,
  `title` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  KEY `offer_id_idxfk_3` (`offer_id`),
  CONSTRAINT `document_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `event`;

CREATE TABLE `event` (
  `offer_id` bigint(20) NOT NULL default '0',
  `is_park_event` tinyint(1) default NULL,
  `is_park_partner_event` tinyint(1) default NULL,
  `public_transport_stop` varchar(255) default NULL,
  `kind_of_event` varchar(255) default NULL,
  PRIMARY KEY  (`offer_id`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Export hyperlink
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hyperlink`;

CREATE TABLE `hyperlink` (
  `offer_id` bigint(20) default NULL,
  `language` char(2) default NULL,
  `title` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  KEY `offer_id_idxfk_13` (`offer_id`),
  CONSTRAINT `hyperlink_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image`;

CREATE TABLE `image` (
  `offer_id` bigint(20) default NULL,
  `small` varchar(255) default NULL,
  `medium` varchar(255) default NULL,
  `large` varchar(255) default NULL,
  `original` varchar(255) default NULL,
  `copyright` varchar(255) default NULL,
  KEY `offer_id_idxfk_6` (`offer_id`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export offer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer`;

CREATE TABLE `offer` (
  `offer_id` bigint(20) NOT NULL auto_increment,
  `park_id` int(11) NOT NULL,
  `park` text NOT NULL,
  `institution` text,
  `institution_is_park_partner` tinyint(1) default NULL,
  `contact` text,
  `contact_is_park_partner` tinyint(1) default NULL,
  `barrier_free` tinyint(1) default NULL,
  `learning_opportunity` tinyint(1) default NULL,
  `child_friendly` tinyint(1) default NULL,
  `park_day` tinyint(1) default NULL,
  `enjoy_week` tinyint(1) default NULL,
  `latitude` float(10,6) default NULL,
  `longitude` float(10,6) default NULL,
  `modified_at` datetime default NULL,
  `created_at` datetime default NULL,
  PRIMARY KEY  (`offer_id`),
  UNIQUE KEY `offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export offer_date
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_date`;

CREATE TABLE `offer_date` (
  `offer_id` bigint(20) default NULL,
  `date_from` datetime default NULL,
  `date_to` datetime default NULL,
  KEY `offer_id_idxfk_4` (`offer_id`),
  CONSTRAINT `offer_date_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export offer_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_i18n`;

CREATE TABLE `offer_i18n` (
  `offer_id` bigint(20) NOT NULL default '0',
  `language` char(2) NOT NULL default '',
  `title` varchar(255) default NULL,
  `abstract` varchar(50) default NULL,
  `description_medium` varchar(250) default NULL,
  `description_long` varchar(1000) default NULL,
  `details` text,
  `price` text,
  `location_details` varchar(1000) default NULL,
  `opening_hours` text,
  `benefits` text,
  `requirements` text,
  `additional_informations` text,
  `catering_informations` text,
  `material_rent` text,
  `safety_instructions` text,
  `signalization` text,
  `other_infrastructure` text,
  `route_url` varchar(255) default NULL,
  PRIMARY KEY  (`offer_id`,`language`),
  CONSTRAINT `offer_i18n_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export offer_route
# ------------------------------------------------------------

DROP TABLE IF EXISTS `offer_route`;

CREATE TABLE `offer_route` (
  `offer_id` bigint(20) default NULL,
  `latitude` float(10,6) default NULL,
  `longitude` float(10,6) default NULL,
  `sort` int(11) default NULL,
  KEY `offer_id_idxfk_9` (`offer_id`),
  CONSTRAINT `offer_route_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export product
# ------------------------------------------------------------

DROP TABLE IF EXISTS `product`;

CREATE TABLE `product` (
  `offer_id` bigint(20) NOT NULL default '0',
  `available_from` datetime default NULL,
  `available_to` datetime default NULL,
  `public_transport_stop` varchar(255) default NULL,
  `number_of_rooms` int(11) default NULL,
  `has_conference_room` tinyint(1) default NULL,
  `has_playground` tinyint(1) default NULL,
  `has_picnic_place` tinyint(1) default NULL,
  `has_fireplace` tinyint(1) default NULL,
  `has_washrooms` tinyint(1) default NULL,
  PRIMARY KEY  (`offer_id`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export supplier
# ------------------------------------------------------------

DROP TABLE IF EXISTS `supplier`;

CREATE TABLE `supplier` (
  `offer_id` bigint(20) default NULL,
  `contact` text,
  `is_park_partner` tinyint(1) default NULL,
  KEY `offer_id_idxfk_11` (`offer_id`),
  CONSTRAINT `supplier_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export target_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group`;

CREATE TABLE `target_group` (
  `target_group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`target_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `target_group` WRITE;
/*!40000 ALTER TABLE `target_group` DISABLE KEYS */;

INSERT INTO `target_group` (`target_group_id`)
VALUES
	(1),
	(2),
	(3),
	(4),
	(5),
	(6),
	(7),
	(8),
	(9),
	(10),
	(11);

/*!40000 ALTER TABLE `target_group` ENABLE KEYS */;
UNLOCK TABLES;


# Export target_group_i18n
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_i18n`;

CREATE TABLE `target_group_i18n` (
  `target_group_id` int(11) NOT NULL default '0',
  `language` char(2) NOT NULL default '',
  `body` varchar(255) default NULL,
  PRIMARY KEY  (`target_group_id`,`language`),
  CONSTRAINT `target_group_i18n_ibfk_1` FOREIGN KEY (`target_group_id`) REFERENCES `target_group` (`target_group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `target_group_i18n` WRITE;
/*!40000 ALTER TABLE `target_group_i18n` DISABLE KEYS */;

INSERT INTO `target_group_i18n` (`target_group_id`, `language`, `body`)
VALUES
	(1,'de','Erwachsene'),
	(1,'en','Adults'),
	(1,'fr','Adultes'),
	(1,'it','Adulti'),
	(2,'de','50+'),
	(2,'en','50+'),
	(2,'fr','50+'),
	(2,'it','50+'),
	(3,'de','Familien'),
	(3,'en','Families'),
	(3,'fr','Familles'),
	(3,'it','Famiglie'),
	(4,'de','Kinder < 6 Jahre'),
	(4,'en','Children < 6 years'),
	(4,'fr','Enfants < 6 ans'),
	(4,'it','Bambini < 6 anni'),
	(5,'de','Kinder von 7-12 Jahre'),
	(5,'en','Children from 7-12 years'),
	(5,'fr','Enfants de 7-12 ans'),
	(5,'it','Bambini da 7 a 12 anni'),
	(6,'de','Kinder >12 Jahre'),
	(6,'en','Children >12 years'),
	(6,'fr','Enfants >12 ans'),
	(6,'it','Bambini > 12 anni'),
	(7,'de','Schulklassen Primarstufe'),
	(7,'en','Primary School Classes'),
	(7,'fr','Classes d\'Ècole niveau primaire'),
	(7,'it','Classi scuole elementari'),
	(8,'de','Schulklassen Sekundarstufe I'),
	(8,'en','Lower Secondary School Classes'),
	(8,'fr','Classes d\'Ècole niveau secondaire I'),
	(8,'it','Classi scuole medie'),
	(9,'de','Schulklassen Sekundarstufe II'),
	(9,'en','Upper Secondary School Classes'),
	(9,'fr','Classes d\'Ècole niveau secondaire II'),
	(9,'it','Classi liceali / scuole superiori'),
	(10,'de','Vereine/Firmen'),
	(10,'en','Association/Companies'),
	(10,'fr','Associations/entreprises'),
	(10,'it','Associazioni/imprese'),
	(11,'de','Gruppen'),
	(11,'en','Groups'),
	(11,'fr','Groupes'),
	(11,'it','Gruppi');

/*!40000 ALTER TABLE `target_group_i18n` ENABLE KEYS */;
UNLOCK TABLES;


# Export target_group_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_group_link`;

CREATE TABLE `target_group_link` (
  `offer_id` bigint(20) NOT NULL default '0',
  `target_group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`offer_id`,`target_group_id`),
  KEY `target_group_id_idxfk_1` (`target_group_id`),
  CONSTRAINT `target_group_link_ibfk_2` FOREIGN KEY (`target_group_id`) REFERENCES `target_group` (`target_group_id`) ON DELETE CASCADE,
  CONSTRAINT `target_group_link_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Export map_layer
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



# Export map_layer_i18n
# ------------------------------------------------------------

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

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;