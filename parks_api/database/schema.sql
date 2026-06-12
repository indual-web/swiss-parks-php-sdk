/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| API database (SQLite schema)
|
*/


-- Table api
-- ------------------------------------------------------------

CREATE TABLE `api` (
  `initialized` INTEGER DEFAULT 0,
  `version` TEXT DEFAULT '1.0',
  `last_import` INTEGER DEFAULT NULL
);


-- Table offer
-- ------------------------------------------------------------

CREATE TABLE `offer` (
  `offer_id` INTEGER PRIMARY KEY,
  `park_id` INTEGER NOT NULL,
  `park` TEXT NOT NULL,
  `is_hint` INTEGER DEFAULT NULL,
  `institution` TEXT,
  `institution_location` TEXT DEFAULT NULL,
  `institution_is_park_partner` INTEGER DEFAULT NULL,
  `contact` TEXT,
  `contact_is_park_partner` INTEGER DEFAULT NULL,
  `barrier_free` INTEGER DEFAULT NULL,
  `learning_opportunity` INTEGER DEFAULT NULL,
  `child_friendly` INTEGER DEFAULT NULL,
  `latitude` REAL DEFAULT NULL,
  `longitude` REAL DEFAULT NULL,
  `keywords` TEXT DEFAULT NULL,
  `modified_at` TEXT DEFAULT NULL,
  `created_at` TEXT DEFAULT NULL
);


-- Table offer_i18n
-- ------------------------------------------------------------

CREATE TABLE `offer_i18n` (
  `offer_id` INTEGER NOT NULL DEFAULT 0,
  `language` TEXT NOT NULL DEFAULT '',
  `title` TEXT DEFAULT NULL,
  `abstract` TEXT,
  `description_medium` TEXT,
  `description_long` TEXT,
  `details` TEXT,
  `price` TEXT,
  `location_details` TEXT DEFAULT NULL,
  `opening_hours` TEXT,
  `benefits` TEXT,
  `requirements` TEXT,
  `additional_informations` TEXT,
  `catering_informations` TEXT,
  `material_rent` TEXT,
  `safety_instructions` TEXT,
  `signalization` TEXT,
  `other_infrastructure` TEXT,
  `route_url` TEXT DEFAULT NULL,
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
  `route_condition` TEXT,
  `route_condition_details` TEXT,
  PRIMARY KEY (`offer_id`, `language`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table offer_date
-- ------------------------------------------------------------

CREATE TABLE `offer_date` (
  `offer_date_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `offer_id` INTEGER DEFAULT NULL,
  `date_from` TEXT DEFAULT NULL,
  `date_to` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_offer_date_offer_id` ON `offer_date` (`offer_id`);


-- Table category
-- ------------------------------------------------------------

CREATE TABLE `category` (
  `category_id` INTEGER PRIMARY KEY,
  `parent_id` INTEGER DEFAULT NULL,
  `stnet_id` TEXT DEFAULT NULL,
  `alpstein_id` TEXT DEFAULT NULL,
  `contact_visible_for_alpstein` INTEGER DEFAULT 1,
  `marker` TEXT DEFAULT NULL,
  `sort` INTEGER NOT NULL,
  UNIQUE (`sort`)
);


-- Table category_i18n
-- No foreign key: rows are managed explicitly by the taxonomy sync
-- ------------------------------------------------------------

CREATE TABLE `category_i18n` (
  `category_id` INTEGER NOT NULL DEFAULT 0,
  `language` TEXT NOT NULL DEFAULT '',
  `body` TEXT DEFAULT NULL,
  PRIMARY KEY (`category_id`, `language`)
);


-- Table category_link
-- No foreign key on category_id: links must survive the taxonomy sync
-- ------------------------------------------------------------

CREATE TABLE `category_link` (
  `offer_id` INTEGER NOT NULL DEFAULT 0,
  `category_id` INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (`offer_id`, `category_id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_category_link_category_id` ON `category_link` (`category_id`);


-- Table document
-- ------------------------------------------------------------

CREATE TABLE `document` (
  `offer_id` INTEGER DEFAULT NULL,
  `language` TEXT DEFAULT NULL,
  `title` TEXT DEFAULT NULL,
  `url` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_document_offer_id` ON `document` (`offer_id`);


-- Table document_intern
-- ------------------------------------------------------------

CREATE TABLE `document_intern` (
  `offer_id` INTEGER DEFAULT NULL,
  `language` TEXT DEFAULT NULL,
  `title` TEXT DEFAULT NULL,
  `url` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_document_intern_offer_id` ON `document_intern` (`offer_id`);


-- Table hyperlink
-- ------------------------------------------------------------

CREATE TABLE `hyperlink` (
  `offer_id` INTEGER DEFAULT NULL,
  `language` TEXT DEFAULT NULL,
  `title` TEXT DEFAULT NULL,
  `url` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_hyperlink_offer_id` ON `hyperlink` (`offer_id`);


-- Table hyperlink_intern
-- ------------------------------------------------------------

CREATE TABLE `hyperlink_intern` (
  `offer_id` INTEGER DEFAULT NULL,
  `language` TEXT DEFAULT NULL,
  `title` TEXT DEFAULT NULL,
  `url` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_hyperlink_intern_offer_id` ON `hyperlink_intern` (`offer_id`);


-- Table image
-- ------------------------------------------------------------

CREATE TABLE `image` (
  `offer_id` INTEGER DEFAULT NULL,
  `small` TEXT DEFAULT NULL,
  `medium` TEXT DEFAULT NULL,
  `large` TEXT DEFAULT NULL,
  `original` TEXT DEFAULT NULL,
  `copyright` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_image_offer_id` ON `image` (`offer_id`);


-- Table event
-- ------------------------------------------------------------

CREATE TABLE `event` (
  `offer_id` INTEGER PRIMARY KEY,
  `is_park_event` INTEGER DEFAULT NULL,
  `is_park_partner_event` INTEGER DEFAULT NULL,
  `public_transport_stop` TEXT DEFAULT NULL,
  `kind_of_event` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table product
-- ------------------------------------------------------------

CREATE TABLE `product` (
  `offer_id` INTEGER PRIMARY KEY,
  `public_transport_stop` TEXT DEFAULT NULL,
  `number_of_rooms` INTEGER DEFAULT NULL,
  `has_conference_room` INTEGER DEFAULT NULL,
  `has_playground` INTEGER DEFAULT NULL,
  `has_picnic_place` INTEGER DEFAULT NULL,
  `has_fireplace` INTEGER DEFAULT NULL,
  `has_washrooms` INTEGER DEFAULT NULL,
  `season_months` TEXT DEFAULT NULL,
  `online_shop_enabled` INTEGER DEFAULT NULL,
  `online_shop_price` REAL DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table booking
-- ------------------------------------------------------------

CREATE TABLE `booking` (
  `offer_id` INTEGER PRIMARY KEY,
  `is_park_partner` INTEGER DEFAULT NULL,
  `min_group_subscriber` INTEGER DEFAULT NULL,
  `max_group_subscriber` INTEGER DEFAULT NULL,
  `min_individual_subscriber` INTEGER DEFAULT NULL,
  `max_individual_subscriber` INTEGER DEFAULT NULL,
  `public_transport_stop` TEXT DEFAULT NULL,
  `season_months` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table activity
-- ------------------------------------------------------------

CREATE TABLE `activity` (
  `offer_id` INTEGER PRIMARY KEY,
  `start_place_info` TEXT,
  `start_place_altitude` INTEGER DEFAULT NULL,
  `goal_place_info` TEXT,
  `goal_place_altitude` INTEGER DEFAULT NULL,
  `route_length` REAL DEFAULT NULL,
  `untarred_route_length` REAL DEFAULT NULL,
  `public_transport_start` TEXT DEFAULT NULL,
  `public_transport_stop` TEXT DEFAULT NULL,
  `altitude_differential` INTEGER DEFAULT NULL,
  `altitude_ascent` INTEGER DEFAULT NULL,
  `altitude_descent` INTEGER DEFAULT NULL,
  `time_required` TEXT DEFAULT NULL,
  `time_required_minutes` INTEGER DEFAULT NULL,
  `level_technics` INTEGER DEFAULT NULL,
  `level_condition` INTEGER DEFAULT NULL,
  `has_playground` INTEGER DEFAULT NULL,
  `has_picnic_place` INTEGER DEFAULT NULL,
  `has_fireplace` INTEGER DEFAULT NULL,
  `has_washrooms` INTEGER DEFAULT NULL,
  `poi` TEXT,
  `season_months` TEXT DEFAULT NULL,
  `route_condition_id` INTEGER,
  `route_condition_color` TEXT,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table project
-- ------------------------------------------------------------

CREATE TABLE `project` (
  `offer_id` INTEGER PRIMARY KEY,
  `duration_from` INTEGER DEFAULT NULL,
  `duration_from_month` INTEGER DEFAULT NULL,
  `duration_to` INTEGER DEFAULT NULL,
  `duration_to_month` INTEGER DEFAULT NULL,
  `status` INTEGER DEFAULT NULL,
  `poi` TEXT,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table subscription
-- ------------------------------------------------------------

CREATE TABLE `subscription` (
  `offer_id` INTEGER PRIMARY KEY,
  `subscription_mandatory` INTEGER DEFAULT NULL,
  `online_subscription_enabled` INTEGER DEFAULT NULL,
  `subscription_contact` TEXT DEFAULT NULL,
  `subscription_link` TEXT DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table subscription_i18n
-- ------------------------------------------------------------

CREATE TABLE `subscription_i18n` (
  `offer_id` INTEGER NOT NULL DEFAULT 0,
  `language` TEXT NOT NULL DEFAULT '',
  `subscription_details` TEXT,
  PRIMARY KEY (`offer_id`, `language`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table supplier
-- ------------------------------------------------------------

CREATE TABLE `supplier` (
  `offer_id` INTEGER DEFAULT NULL,
  `contact` TEXT,
  `is_park_partner` INTEGER DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_supplier_offer_id` ON `supplier` (`offer_id`);


-- Table accommodation
-- ------------------------------------------------------------

CREATE TABLE `accommodation` (
  `offer_id` INTEGER DEFAULT NULL,
  `contact` TEXT,
  `is_park_partner` INTEGER DEFAULT 0,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_accommodation_offer_id` ON `accommodation` (`offer_id`);


-- Table target_group
-- ------------------------------------------------------------

CREATE TABLE `target_group` (
  `target_group_id` INTEGER PRIMARY KEY,
  `sort` INTEGER DEFAULT NULL
);


-- Table target_group_i18n
-- No foreign key: rows are managed explicitly by the taxonomy sync
-- ------------------------------------------------------------

CREATE TABLE `target_group_i18n` (
  `target_group_id` INTEGER NOT NULL DEFAULT 0,
  `language` TEXT NOT NULL DEFAULT '',
  `body` TEXT DEFAULT NULL,
  PRIMARY KEY (`target_group_id`, `language`)
);


-- Table target_group_link
-- ------------------------------------------------------------

CREATE TABLE `target_group_link` (
  `offer_id` INTEGER NOT NULL DEFAULT 0,
  `target_group_id` INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (`offer_id`, `target_group_id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_target_group_link_target_group_id` ON `target_group_link` (`target_group_id`);


-- Tables: Fields of activity
-- No foreign keys on taxonomy tables: managed explicitly by the sync
-- ------------------------------------------------------------

CREATE TABLE `field_of_activity` (
  `field_of_activity_id` INTEGER PRIMARY KEY,
  `sort` INTEGER
);

CREATE TABLE `field_of_activity_i18n` (
  `field_of_activity_id` INTEGER NOT NULL,
  `language` TEXT NOT NULL,
  `body` TEXT,
  PRIMARY KEY (`field_of_activity_id`, `language`)
);

CREATE TABLE `field_of_activity_link` (
  `offer_id` INTEGER NOT NULL,
  `field_of_activity_id` INTEGER NOT NULL,
  PRIMARY KEY (`offer_id`, `field_of_activity_id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table accessibility
-- ------------------------------------------------------------

CREATE TABLE `accessibility` (
  `accessibility_id` INTEGER NOT NULL,
  `offer_id` INTEGER NOT NULL,
  `ginto_id` TEXT DEFAULT NULL,
  `ginto_icon` TEXT DEFAULT NULL,
  `ginto_link` TEXT DEFAULT NULL,
  PRIMARY KEY (`accessibility_id`, `offer_id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_accessibility_offer_id` ON `accessibility` (`offer_id`);


-- Table accessibility_rating
-- No foreign key possible (accessibility_id is not unique on its own),
-- cleanup is handled by the trigger below
-- ------------------------------------------------------------

CREATE TABLE `accessibility_rating` (
  `accessibility_rating_id` INTEGER PRIMARY KEY,
  `accessibility_id` INTEGER NOT NULL,
  `description_de` TEXT DEFAULT NULL,
  `description_fr` TEXT DEFAULT NULL,
  `description_it` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `icon_url` TEXT DEFAULT NULL
);

CREATE INDEX `idx_accessibility_rating_accessibility_id` ON `accessibility_rating` (`accessibility_id`);

CREATE TRIGGER `trg_accessibility_after_delete`
AFTER DELETE ON `accessibility`
BEGIN
  DELETE FROM `accessibility_rating`
  WHERE `accessibility_id` = OLD.`accessibility_id`
    AND NOT EXISTS (SELECT 1 FROM `accessibility` WHERE `accessibility_id` = OLD.`accessibility_id`);
END;


-- Table accessibility_dropdown
-- ------------------------------------------------------------

CREATE TABLE `accessibility_dropdown` (
  `accessibility_dropdown_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `icon_url` TEXT
);


-- Table municipality
-- ------------------------------------------------------------

CREATE TABLE `municipality` (
  `municipality_id` INTEGER PRIMARY KEY,
  `park_id` INTEGER NOT NULL,
  `municipality` TEXT NOT NULL
);


-- Table offer_municipality_link
-- ------------------------------------------------------------

CREATE TABLE `offer_municipality_link` (
  `offer_id` INTEGER NOT NULL,
  `municipality_id` INTEGER NOT NULL,
  PRIMARY KEY (`offer_id`, `municipality_id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);


-- Table map_layer
-- ------------------------------------------------------------

CREATE TABLE `map_layer` (
  `map_layer_id` INTEGER PRIMARY KEY,
  `url` TEXT DEFAULT NULL,
  `languages` TEXT DEFAULT NULL,
  `layer_category` TEXT DEFAULT NULL,
  `layer_position` INTEGER DEFAULT NULL,
  `visible_by_default` INTEGER DEFAULT NULL,
  `popup_title` TEXT DEFAULT NULL,
  `popup_logo` TEXT DEFAULT NULL
);


-- Table map_layer_i18n
-- ------------------------------------------------------------

CREATE TABLE `map_layer_i18n` (
  `map_layer_id` INTEGER NOT NULL DEFAULT 0,
  `language` TEXT NOT NULL DEFAULT '',
  `popup_content` TEXT,
  `layer_title` TEXT DEFAULT NULL,
  PRIMARY KEY (`map_layer_id`, `language`),
  FOREIGN KEY (`map_layer_id`) REFERENCES `map_layer` (`map_layer_id`) ON DELETE CASCADE
);


-- Table product_article
-- ------------------------------------------------------------

CREATE TABLE `product_article` (
  `product_article_id` INTEGER PRIMARY KEY,
  `offer_id` INTEGER DEFAULT NULL,
  `supplier_contact` TEXT,
  `is_food` INTEGER DEFAULT NULL,
  FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
);

CREATE INDEX `idx_product_article_offer_id` ON `product_article` (`offer_id`);


-- Table product_article_i18n
-- ------------------------------------------------------------

CREATE TABLE `product_article_i18n` (
  `product_article_id` INTEGER NOT NULL,
  `language` TEXT NOT NULL,
  `article_title` TEXT DEFAULT NULL,
  `article_description` TEXT,
  `article_ingredients` TEXT,
  `article_allergens` TEXT,
  `article_nutritional_values` TEXT,
  `article_identity_label` TEXT,
  `article_quantity_indication` TEXT,
  PRIMARY KEY (`product_article_id`, `language`),
  FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
);


-- Table product_article_label
-- ------------------------------------------------------------

CREATE TABLE `product_article_label` (
  `product_article_id` INTEGER NOT NULL,
  `label_id` INTEGER NOT NULL,
  `language` TEXT NOT NULL,
  `label_title` TEXT DEFAULT NULL,
  `label_url` TEXT DEFAULT NULL,
  `label_icon` TEXT DEFAULT NULL,
  PRIMARY KEY (`product_article_id`, `label_id`, `language`),
  FOREIGN KEY (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE
);
