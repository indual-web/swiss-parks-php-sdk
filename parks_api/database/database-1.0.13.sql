/* SQLEditor (MySQL (2))*/

CREATE TABLE api
(
initialized TINYINT(1) DEFAULT 0,
version VARCHAR(20) DEFAULT '1.0',
last_import INTEGER
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE category
(
category_id INTEGER AUTO_INCREMENT UNIQUE,
parent_id INTEGER,
marker VARCHAR(255),
sort INTEGER,
PRIMARY KEY (category_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE category_i18n
(
category_id INTEGER,
language CHAR(2),
body VARCHAR(255),
PRIMARY KEY (category_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE map_layer
(
map_layer_id INTEGER(11) AUTO_INCREMENT UNIQUE,
url VARCHAR(255),
languages VARCHAR(255),
layer_position TINYINT(4),
visible_by_default TINYINT(1) DEFAULT 0,
popup_title VARCHAR(255),
popup_logo VARCHAR(255) DEFAULT '1',
popup_logo_width INTEGER,
popup_logo_height INTEGER,
PRIMARY KEY (map_layer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE map_layer_i18n
(
map_layer_id INTEGER(11),
language CHAR(2),
popup_content VARCHAR(1000),
PRIMARY KEY (map_layer_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE offer
(
offer_id BIGINT AUTO_INCREMENT UNIQUE,
park_id INTEGER NOT NULL,
park TEXT NOT NULL,
institution TEXT,
contact TEXT,
barrier_free TINYINT(1),
learning_opportunity TINYINT(1),
child_friendly TINYINT(1),
park_day TINYINT(1),
enjoy_week TINYINT(1),
latitude FLOAT(10,6),
longitude FLOAT(10,6),
modified_at DATETIME,
created_at DATETIME,
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE accommodation
(
offer_id BIGINT,
contact TEXT
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE activity
(
offer_id BIGINT,
start_place_info TEXT,
start_place_altitude INTEGER,
goal_place_info TEXT,
goal_place_altitude INTEGER,
route_length DECIMAL(7,2),
public_transport_start VARCHAR(255),
public_transport_stop VARCHAR(255),
altitude_differential INTEGER,
altitude_ascent INTEGER,
altitude_descent INTEGER,
time_required VARCHAR(255),
level_technics TINYINT(1),
level_condition TINYINT(1),
has_playground TINYINT(1),
has_picnic_place TINYINT(1),
has_fireplace TINYINT(1),
has_washrooms TINYINT(1),
poi TEXT,
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE category_link
(
offer_id BIGINT,
category_id INTEGER,
PRIMARY KEY (offer_id,category_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE document
(
offer_id BIGINT,
language CHAR(2),
title VARCHAR(255),
url VARCHAR(255)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE offer_date
(
offer_id BIGINT,
date_from DATETIME,
date_to DATETIME
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE event
(
offer_id BIGINT,
is_park_event TINYINT(1),
is_park_partner_event TINYINT(1),
public_transport_stop VARCHAR(255),
kind_of_event VARCHAR(255),
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE image
(
offer_id BIGINT,
small VARCHAR(255),
medium VARCHAR(255),
large VARCHAR(255),
original VARCHAR(255),
copyright VARCHAR(255)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE booking
(
offer_id BIGINT,
is_park_partner TINYINT(1),
min_group_subscriber INTEGER,
max_group_subscriber INTEGER,
min_individual_subscriber INTEGER,
max_individual_subscriber INTEGER,
public_transport_stop VARCHAR(255),
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE hyperlink
(
offer_id BIGINT,
language CHAR(2),
title VARCHAR(255),
url VARCHAR(255)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE offer_i18n
(
offer_id BIGINT,
language CHAR(2),
title VARCHAR(255),
abstract VARCHAR(50),
description_medium VARCHAR(250),
description_long VARCHAR(1000),
details TEXT,
price TEXT,
location_details VARCHAR(255),
opening_hours TEXT,
benefits TEXT,
requirements TEXT,
additional_informations TEXT,
catering_informations TEXT,
material_rent TEXT,
safety_instructions TEXT,
signalization TEXT,
route_url VARCHAR(255),
other_infrastructure TEXT,
PRIMARY KEY (offer_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE offer_route
(
offer_id BIGINT,
latitude FLOAT(10,6),
longitude FLOAT(10,6),
sort INTEGER
) ENGINE=InnoDB;

CREATE TABLE product
(
offer_id BIGINT,
available_from DATETIME,
available_to DATETIME,
public_transport_stop VARCHAR(255),
number_of_rooms INTEGER,
has_conference_room TINYINT(1),
has_playground TINYINT(1),
has_picnic_place TINYINT(1),
has_fireplace TINYINT(1),
has_washrooms TINYINT(1),
PRIMARY KEY (offer_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

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

CREATE TABLE supplier
(
offer_id BIGINT,
contact TEXT
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE target_group
(
target_group_id INTEGER,
PRIMARY KEY (target_group_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE target_group_i18n
(
target_group_id INTEGER,
language CHAR(2),
body VARCHAR(255),
PRIMARY KEY (target_group_id,language)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE target_group_link
(
offer_id BIGINT,
target_group_id INTEGER,
PRIMARY KEY (offer_id,target_group_id)
) ENGINE=InnoDB CHARACTER SET=utf8;

ALTER TABLE category_i18n ADD FOREIGN KEY category_id_idxfk (category_id) REFERENCES category (category_id) ON DELETE CASCADE;

ALTER TABLE map_layer_i18n ADD FOREIGN KEY map_layer_id_idxfk (map_layer_id) REFERENCES map_layer (map_layer_id);

ALTER TABLE accommodation ADD FOREIGN KEY offer_id_idxfk (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE activity ADD FOREIGN KEY offer_id_idxfk_1 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE category_link ADD FOREIGN KEY offer_id_idxfk_2 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE category_link ADD FOREIGN KEY category_id_idxfk_1 (category_id) REFERENCES category (category_id) ON DELETE CASCADE;

ALTER TABLE document ADD FOREIGN KEY offer_id_idxfk_3 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE offer_date ADD FOREIGN KEY offer_id_idxfk_4 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE event ADD FOREIGN KEY offer_id_idxfk_5 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE image ADD FOREIGN KEY offer_id_idxfk_6 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE booking ADD FOREIGN KEY offer_id_idxfk_7 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE hyperlink ADD FOREIGN KEY offer_id_idxfk_8 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE offer_i18n ADD FOREIGN KEY offer_id_idxfk_9 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE offer_route ADD FOREIGN KEY offer_id_idxfk_10 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE product ADD FOREIGN KEY offer_id_idxfk_11 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE subscription ADD FOREIGN KEY offer_id_idxfk_12 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE supplier ADD FOREIGN KEY offer_id_idxfk_13 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE target_group_i18n ADD FOREIGN KEY target_group_id_idxfk (target_group_id) REFERENCES target_group (target_group_id) ON DELETE CASCADE;

ALTER TABLE target_group_link ADD FOREIGN KEY offer_id_idxfk_14 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;

ALTER TABLE target_group_link ADD FOREIGN KEY target_group_id_idxfk_1 (target_group_id) REFERENCES target_group (target_group_id) ON DELETE CASCADE;
