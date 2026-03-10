<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| DB migrations
|
*/


class ParksMigration
{


	/**
	 * API
	 */
	public $api;


	/**
	 * API releases
	 */
	private $releases = [
		2,
		3,
		4,
		5,
		6,
		7,
		8,
		9, 9.1,
		10,
		11,
		12,
		13,
		14,
		15,
		16,
		17,
		18,
		19,
		20,
		21, 21.1,
	];


	/**
	 * Migration version from
	 */
	private $version_from;


	/**
	 * Migration version to
	 */
	private $version_to;



	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $api
	 * @return void
	 */
	function __construct($api)
	{

		// Api instance
		$this->api = $api;

	}



	/**
	 * Start migration
	 *
	 * @access public
	 * @return void
	 */
	public function start()
	{

		// Version from
		$query_api = mysqli_fetch_assoc($this->api->db->get('api'));
		$this->version_from = floatval($query_api['version']);

		// Version to
		$this->version_to = API_VERSION;

		// Update by releases
		foreach ($this->releases as $release) {
			if ($release > $this->version_from) {
				$this->migrate_to($release);
			}
		}

	}



	/**
	 * Migrate to specified version
	 *
	 * @access public
	 * @param float $version_to
	 * @return void
	 */
	public function migrate_to($version_to)
	{
		if ($version_to > 0) {
			$this->api->logger->info('Starting migration from version ' . $this->version_from . ' to version ' . $version_to . '...');
			switch ($version_to) {
				default:
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate version 1 to version 2
				|--------------------------------------------------------------------------
				|
				*/
				case 2:
					$this->api->db->query("ALTER TABLE offer_route ADD PRIMARY KEY (offer_id);");
					$this->api->db->query("CREATE TABLE `project` (
										  `offer_id` bigint(20) NOT NULL DEFAULT '0',
										  `duration_from` int(11) DEFAULT NULL,
										  `duration_to` int(11) DEFAULT NULL,
										  `status` tinyint(1) DEFAULT NULL,
										  `poi` text,
										  PRIMARY KEY (`offer_id`),
										  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
										  ) DEFAULT CHARSET=utf8;
					");

					// Create new main category
					$this->api->db->insert('category', array('category_id' => CATEGORY_PROJECT, 'parent_id' => 0, 'marker' => '454545', 'sort' => 5000));
					$this->api->db->insert('category_i18n', array('category_id' => CATEGORY_PROJECT, 'language' => 'de', 'body' => 'Projekt'));
					$this->api->db->insert('category_i18n', array('category_id' => CATEGORY_PROJECT, 'language' => 'fr', 'body' => 'Projet'));
					$this->api->db->insert('category_i18n', array('category_id' => CATEGORY_PROJECT, 'language' => 'it', 'body' => 'Progetto'));
					$this->api->db->insert('category_i18n', array('category_id' => CATEGORY_PROJECT, 'language' => 'en', 'body' => 'Project'));

					// Create sub categories
					$this->api->db->insert('category', array('category_id' => 1001, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'ffcc00', 'sort' => 5010));
					$this->api->db->insert('category_i18n', array('category_id' => 1001, 'language' => 'de', 'body' => 'Natur / Landschaft'));
					$this->api->db->insert('category_i18n', array('category_id' => 1001, 'language' => 'fr', 'body' => 'Nature / paysage'));
					$this->api->db->insert('category_i18n', array('category_id' => 1001, 'language' => 'it', 'body' => 'Natura / Paesaggio'));
					$this->api->db->insert('category_i18n', array('category_id' => 1001, 'language' => 'en', 'body' => 'Nature / Landscape'));

					$this->api->db->insert('category', array('category_id' => 1002, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'cccccc', 'sort' => 5020));
					$this->api->db->insert('category_i18n', array('category_id' => 1002, 'language' => 'de', 'body' => 'Biodiversität'));
					$this->api->db->insert('category_i18n', array('category_id' => 1002, 'language' => 'fr', 'body' => 'Biodiversité'));
					$this->api->db->insert('category_i18n', array('category_id' => 1002, 'language' => 'it', 'body' => 'Biodiversità'));
					$this->api->db->insert('category_i18n', array('category_id' => 1002, 'language' => 'en', 'body' => 'Biodiversity'));

					$this->api->db->insert('category', array('category_id' => 1003, 'parent_id' => CATEGORY_PROJECT, 'marker' => '0066ff', 'sort' => 5030));
					$this->api->db->insert('category_i18n', array('category_id' => 1003, 'language' => 'de', 'body' => 'Landwirtschaft'));
					$this->api->db->insert('category_i18n', array('category_id' => 1003, 'language' => 'fr', 'body' => 'Agriculture'));
					$this->api->db->insert('category_i18n', array('category_id' => 1003, 'language' => 'it', 'body' => 'Agricoltura'));
					$this->api->db->insert('category_i18n', array('category_id' => 1003, 'language' => 'en', 'body' => 'Agriculture'));

					$this->api->db->insert('category', array('category_id' => 1004, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'ff99cc', 'sort' => 5040));
					$this->api->db->insert('category_i18n', array('category_id' => 1004, 'language' => 'de', 'body' => 'Forstwirtschaft'));
					$this->api->db->insert('category_i18n', array('category_id' => 1004, 'language' => 'fr', 'body' => 'Sylviculture'));
					$this->api->db->insert('category_i18n', array('category_id' => 1004, 'language' => 'it', 'body' => 'Economia forestale'));
					$this->api->db->insert('category_i18n', array('category_id' => 1004, 'language' => 'en', 'body' => 'Forestry'));

					$this->api->db->insert('category', array('category_id' => 1005, 'parent_id' => CATEGORY_PROJECT, 'marker' => '93dbff', 'sort' => 5050));
					$this->api->db->insert('category_i18n', array('category_id' => 1005, 'language' => 'de', 'body' => 'Energie'));
					$this->api->db->insert('category_i18n', array('category_id' => 1005, 'language' => 'fr', 'body' => 'Énergie'));
					$this->api->db->insert('category_i18n', array('category_id' => 1005, 'language' => 'it', 'body' => 'Energia'));
					$this->api->db->insert('category_i18n', array('category_id' => 1005, 'language' => 'en', 'body' => 'Energy'));

					$this->api->db->insert('category', array('category_id' => 1006, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'ff7b21', 'sort' => 5060));
					$this->api->db->insert('category_i18n', array('category_id' => 1006, 'language' => 'de', 'body' => 'Raumplanung'));
					$this->api->db->insert('category_i18n', array('category_id' => 1006, 'language' => 'fr', 'body' => 'Aménagement du territoire'));
					$this->api->db->insert('category_i18n', array('category_id' => 1006, 'language' => 'it', 'body' => 'Pianificazione territoriale'));
					$this->api->db->insert('category_i18n', array('category_id' => 1006, 'language' => 'en', 'body' => 'Spatial planning'));

					$this->api->db->insert('category', array('category_id' => 1007, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'd8d8d8', 'sort' => 5070));
					$this->api->db->insert('category_i18n', array('category_id' => 1007, 'language' => 'de', 'body' => 'Tourismus'));
					$this->api->db->insert('category_i18n', array('category_id' => 1007, 'language' => 'fr', 'body' => 'Tourisme'));
					$this->api->db->insert('category_i18n', array('category_id' => 1007, 'language' => 'it', 'body' => 'Turismo'));
					$this->api->db->insert('category_i18n', array('category_id' => 1007, 'language' => 'en', 'body' => 'Tourism'));

					$this->api->db->insert('category', array('category_id' => 1008, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'ffff00', 'sort' => 5080));
					$this->api->db->insert('category_i18n', array('category_id' => 1008, 'language' => 'de', 'body' => 'Mobilität'));
					$this->api->db->insert('category_i18n', array('category_id' => 1008, 'language' => 'fr', 'body' => 'Mobilité'));
					$this->api->db->insert('category_i18n', array('category_id' => 1008, 'language' => 'it', 'body' => 'Mobilità'));
					$this->api->db->insert('category_i18n', array('category_id' => 1008, 'language' => 'en', 'body' => 'Mobility'));

					$this->api->db->insert('category', array('category_id' => 1009, 'parent_id' => CATEGORY_PROJECT, 'marker' => '00501f', 'sort' => 5090));
					$this->api->db->insert('category_i18n', array('category_id' => 1009, 'language' => 'de', 'body' => 'Besucherlenkung'));
					$this->api->db->insert('category_i18n', array('category_id' => 1009, 'language' => 'fr', 'body' => 'Gestion des visiteurs'));
					$this->api->db->insert('category_i18n', array('category_id' => 1009, 'language' => 'it', 'body' => 'Visite guidate'));
					$this->api->db->insert('category_i18n', array('category_id' => 1009, 'language' => 'en', 'body' => 'Visitor management'));

					$this->api->db->insert('category', array('category_id' => 1010, 'parent_id' => CATEGORY_PROJECT, 'marker' => '974807', 'sort' => 5100));
					$this->api->db->insert('category_i18n', array('category_id' => 1010, 'language' => 'de', 'body' => 'Zugänglichkeit'));
					$this->api->db->insert('category_i18n', array('category_id' => 1010, 'language' => 'fr', 'body' => 'Accessibilité '));
					$this->api->db->insert('category_i18n', array('category_id' => 1010, 'language' => 'it', 'body' => 'Accessibilità'));
					$this->api->db->insert('category_i18n', array('category_id' => 1010, 'language' => 'en', 'body' => 'Accessibility'));

					$this->api->db->insert('category', array('category_id' => 1011, 'parent_id' => CATEGORY_PROJECT, 'marker' => '7dd53b', 'sort' => 5110));
					$this->api->db->insert('category_i18n', array('category_id' => 1011, 'language' => 'de', 'body' => 'Bildung'));
					$this->api->db->insert('category_i18n', array('category_id' => 1011, 'language' => 'fr', 'body' => 'Education'));
					$this->api->db->insert('category_i18n', array('category_id' => 1011, 'language' => 'it', 'body' => 'Formazione'));
					$this->api->db->insert('category_i18n', array('category_id' => 1011, 'language' => 'en', 'body' => 'Education'));

					$this->api->db->insert('category', array('category_id' => 1012, 'parent_id' => CATEGORY_PROJECT, 'marker' => '699fd6', 'sort' => 5120));
					$this->api->db->insert('category_i18n', array('category_id' => 1012, 'language' => 'de', 'body' => 'Kultur'));
					$this->api->db->insert('category_i18n', array('category_id' => 1012, 'language' => 'fr', 'body' => 'Culture'));
					$this->api->db->insert('category_i18n', array('category_id' => 1012, 'language' => 'it', 'body' => 'Cultura'));
					$this->api->db->insert('category_i18n', array('category_id' => 1012, 'language' => 'en', 'body' => 'Culture'));

					$this->api->db->insert('category', array('category_id' => 1013, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'c79602', 'sort' => 5130));
					$this->api->db->insert('category_i18n', array('category_id' => 1013, 'language' => 'de', 'body' => 'Regionale Produkte'));
					$this->api->db->insert('category_i18n', array('category_id' => 1013, 'language' => 'fr', 'body' => 'Produits régionaux'));
					$this->api->db->insert('category_i18n', array('category_id' => 1013, 'language' => 'it', 'body' => 'Prodotti regionali'));
					$this->api->db->insert('category_i18n', array('category_id' => 1013, 'language' => 'en', 'body' => 'Regional products'));

					$this->api->db->insert('category', array('category_id' => 1014, 'parent_id' => CATEGORY_PROJECT, 'marker' => 'ff00ff', 'sort' => 5140));
					$this->api->db->insert('category_i18n', array('category_id' => 1014, 'language' => 'de', 'body' => 'Gesundheit / Wellness'));
					$this->api->db->insert('category_i18n', array('category_id' => 1014, 'language' => 'fr', 'body' => 'Santé / bien-être'));
					$this->api->db->insert('category_i18n', array('category_id' => 1014, 'language' => 'it', 'body' => 'Benessere / wellness'));
					$this->api->db->insert('category_i18n', array('category_id' => 1014, 'language' => 'en', 'body' => 'Health / wellness'));

					$this->api->db->insert('category', array('category_id' => 1015, 'parent_id' => CATEGORY_PROJECT, 'marker' => '9b7bb4', 'sort' => 5150));
					$this->api->db->insert('category_i18n', array('category_id' => 1015, 'language' => 'de', 'body' => 'Nachhaltigkeit'));
					$this->api->db->insert('category_i18n', array('category_id' => 1015, 'language' => 'fr', 'body' => 'Durabilité'));
					$this->api->db->insert('category_i18n', array('category_id' => 1015, 'language' => 'it', 'body' => 'Sostenibilità'));
					$this->api->db->insert('category_i18n', array('category_id' => 1015, 'language' => 'en', 'body' => 'Sustainability'));

					$this->api->db->insert('category', array('category_id' => 1016, 'parent_id' => CATEGORY_PROJECT, 'marker' => '376091', 'sort' => 5160));
					$this->api->db->insert('category_i18n', array('category_id' => 1016, 'language' => 'de', 'body' => 'Dienstleistungen'));
					$this->api->db->insert('category_i18n', array('category_id' => 1016, 'language' => 'fr', 'body' => 'Services'));
					$this->api->db->insert('category_i18n', array('category_id' => 1016, 'language' => 'it', 'body' => 'Servizi'));
					$this->api->db->insert('category_i18n', array('category_id' => 1016, 'language' => 'en', 'body' => 'Services'));

					$this->api->db->insert('category', array('category_id' => 1017, 'parent_id' => CATEGORY_PROJECT, 'marker' => '7030a0', 'sort' => 5170));
					$this->api->db->insert('category_i18n', array('category_id' => 1017, 'language' => 'de', 'body' => 'Transnationale Projekte'));
					$this->api->db->insert('category_i18n', array('category_id' => 1017, 'language' => 'fr', 'body' => 'Projets transfrontaliers'));
					$this->api->db->insert('category_i18n', array('category_id' => 1017, 'language' => 'it', 'body' => 'Progetti transnazionali'));
					$this->api->db->insert('category_i18n', array('category_id' => 1017, 'language' => 'en', 'body' => 'Transnational projects'));

					// Move research category
					$this->api->db->query("UPDATE `category` SET `category`.`sort` = 6000 WHERE `category_id` = 5000 LIMIT 1;");
					$this->api->db->query("UPDATE `category` SET `category`.`sort` = 6010 WHERE `category_id` = 5001 LIMIT 1;");
					$this->api->db->query("UPDATE `category` SET `category`.`sort` = 6020 WHERE `category_id` = 5002 LIMIT 1;");

					break;


				/*
				|--------------------------------------------------------------------------
				| Migrate version 4 to version 5
				|--------------------------------------------------------------------------
				|
				*/
				case 5:
					$this->api->db->query("ALTER TABLE `offer` ADD COLUMN `is_hint` TINYINT(1) AFTER `park`;");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate version 5 to version 6
				|--------------------------------------------------------------------------
				|
				*/
				case 6:

					// Create new keywords field for offers
					$this->api->db->query("ALTER TABLE `offer` ADD COLUMN `keywords` VARCHAR(150) AFTER `longitude`;");

					// Create category: event -> presentation
					$this->api->db->insert('category', array('category_id' => 122, 'parent_id' => 1, 'marker' => 'ffcc00', 'sort' => 1025));
					$this->api->db->insert('category_i18n', array('category_id' => 122, 'language' => 'de', 'body' => 'Vortrag'));
					$this->api->db->insert('category_i18n', array('category_id' => 122, 'language' => 'fr', 'body' => 'Présentation'));
					$this->api->db->insert('category_i18n', array('category_id' => 122, 'language' => 'it', 'body' => 'Presentatzione'));
					$this->api->db->insert('category_i18n', array('category_id' => 122, 'language' => 'en', 'body' => 'Presentation'));

					// Create category: product -> producer
					$this->api->db->insert('category', array('category_id' => 123, 'parent_id' => 19, 'marker' => '93dbff', 'sort' => 2312));
					$this->api->db->insert('category_i18n', array('category_id' => 123, 'language' => 'de', 'body' => 'Produzent'));
					$this->api->db->insert('category_i18n', array('category_id' => 123, 'language' => 'fr', 'body' => 'Producteur'));
					$this->api->db->insert('category_i18n', array('category_id' => 123, 'language' => 'it', 'body' => 'Produttore'));
					$this->api->db->insert('category_i18n', array('category_id' => 123, 'language' => 'en', 'body' => 'Producer'));

					// Create category: product -> shop for regional products
					$this->api->db->insert('category', array('category_id' => 124, 'parent_id' => 19, 'marker' => '93dbff', 'sort' => 2314));
					$this->api->db->insert('category_i18n', array('category_id' => 124, 'language' => 'de', 'body' => 'Verkauf Regionalprodukte'));
					$this->api->db->insert('category_i18n', array('category_id' => 124, 'language' => 'fr', 'body' => 'Vente du produits regionaux'));
					$this->api->db->insert('category_i18n', array('category_id' => 124, 'language' => 'it', 'body' => 'Vendita di prodotti regionali'));
					$this->api->db->insert('category_i18n', array('category_id' => 124, 'language' => 'en', 'body' => 'Shop for regional products'));

					// Create category: product -> gifts
					$this->api->db->insert('category', array('category_id' => 125, 'parent_id' => 19, 'marker' => '93dbff', 'sort' => 2316));
					$this->api->db->insert('category_i18n', array('category_id' => 125, 'language' => 'de', 'body' => 'Geschenke'));
					$this->api->db->insert('category_i18n', array('category_id' => 125, 'language' => 'fr', 'body' => 'Cadeaux'));
					$this->api->db->insert('category_i18n', array('category_id' => 125, 'language' => 'it', 'body' => 'Regali'));
					$this->api->db->insert('category_i18n', array('category_id' => 125, 'language' => 'en', 'body' => 'Gifts'));

					// Update category: product -> further
					$this->api->db->update('category_i18n', array('body' => 'Weitere Produkte'), array('category_id' => 27, 'language' => 'de'));

					// Update category: gastronomy/accommodation -> restaurant
					$this->api->db->update('category_i18n', array('body' => 'Restaurant'), array('category_id' => 28, 'language' => 'de'));
					$this->api->db->update('category_i18n', array('body' => 'Restaurant'), array('category_id' => 28, 'language' => 'fr'));
					$this->api->db->update('category_i18n', array('body' => 'Ristorante'), array('category_id' => 28, 'language' => 'it'));
					$this->api->db->update('category_i18n', array('body' => 'Restaurant'), array('category_id' => 28, 'language' => 'en'));

					// Create category: gastronomy/accommodation -> cafe
					$this->api->db->insert('category', array('category_id' => 126, 'parent_id' => 20, 'marker' => 'ff7b21', 'sort' => 2415));
					$this->api->db->insert('category_i18n', array('category_id' => 126, 'language' => 'de', 'body' => 'Cafe'));
					$this->api->db->insert('category_i18n', array('category_id' => 126, 'language' => 'fr', 'body' => 'Café'));
					$this->api->db->insert('category_i18n', array('category_id' => 126, 'language' => 'it', 'body' => 'Caffè'));
					$this->api->db->insert('category_i18n', array('category_id' => 126, 'language' => 'en', 'body' => 'Cafe'));

					// Check all cafe offers coming from restaurant
					$q_category_link = $this->api->db->get('category_link', array('category_id' => 28));
					if (mysqli_num_rows($q_category_link) > 0) {
						while ($row = mysqli_fetch_object($q_category_link)) {
							$this->api->db->insert('category_link', array('offer_id' => $row->offer_id, 'category_id' => 126));
						}
					}

					// Create category: infrastructure -> swimming pool/lake
					$this->api->db->insert('category', array('category_id' => 127, 'parent_id' => 100, 'marker' => 'd8d8d8', 'sort' => 2631));
					$this->api->db->insert('category_i18n', array('category_id' => 127, 'language' => 'de', 'body' => 'Schwimmbad/Badesee'));
					$this->api->db->insert('category_i18n', array('category_id' => 127, 'language' => 'fr', 'body' => 'Piscine/Lac de baignade'));
					$this->api->db->insert('category_i18n', array('category_id' => 127, 'language' => 'it', 'body' => 'Piscina/Lago balneabile'));
					$this->api->db->insert('category_i18n', array('category_id' => 127, 'language' => 'en', 'body' => 'Swimming pool/Swimming lake'));

					// Update category sort: infrastructure -> watersports
					$this->api->db->update('category', array('sort' => 2633), array('category_id' => 119));

					// Create category: infrastructure -> funicular
					$this->api->db->insert('category', array('category_id' => 128, 'parent_id' => 100, 'marker' => 'd8d8d8', 'sort' => 2632));
					$this->api->db->insert('category_i18n', array('category_id' => 128, 'language' => 'de', 'body' => 'Seilbahn'));
					$this->api->db->insert('category_i18n', array('category_id' => 128, 'language' => 'fr', 'body' => 'Funiculaire'));
					$this->api->db->insert('category_i18n', array('category_id' => 128, 'language' => 'it', 'body' => 'Funicolare'));
					$this->api->db->insert('category_i18n', array('category_id' => 128, 'language' => 'en', 'body' => 'Funicular'));

					// Create category: infrastructure -> climbing
					$this->api->db->insert('category', array('category_id' => 129, 'parent_id' => 100, 'marker' => 'd8d8d8', 'sort' => 2638));
					$this->api->db->insert('category_i18n', array('category_id' => 129, 'language' => 'de', 'body' => 'Klettern'));
					$this->api->db->insert('category_i18n', array('category_id' => 129, 'language' => 'fr', 'body' => 'Escalade'));
					$this->api->db->insert('category_i18n', array('category_id' => 129, 'language' => 'it', 'body' => 'Arrampicata'));
					$this->api->db->insert('category_i18n', array('category_id' => 129, 'language' => 'en', 'body' => 'Climbing'));

					// Create category: infrastructure -> skating rink
					$this->api->db->insert('category', array('category_id' => 130, 'parent_id' => 100, 'marker' => 'd8d8d8', 'sort' => 2643));
					$this->api->db->insert('category_i18n', array('category_id' => 130, 'language' => 'de', 'body' => 'Eisbahn'));
					$this->api->db->insert('category_i18n', array('category_id' => 130, 'language' => 'fr', 'body' => 'Patinoire'));
					$this->api->db->insert('category_i18n', array('category_id' => 130, 'language' => 'it', 'body' => 'Pista di ghiaccio'));
					$this->api->db->insert('category_i18n', array('category_id' => 130, 'language' => 'en', 'body' => 'Skating rink'));

					// Create category: infrastructure -> cross-country skiing
					$this->api->db->insert('category', array('category_id' => 131, 'parent_id' => 100, 'marker' => 'd8d8d8', 'sort' => 2647));
					$this->api->db->insert('category_i18n', array('category_id' => 131, 'language' => 'de', 'body' => 'Langlauf'));
					$this->api->db->insert('category_i18n', array('category_id' => 131, 'language' => 'fr', 'body' => 'Ski de fond'));
					$this->api->db->insert('category_i18n', array('category_id' => 131, 'language' => 'it', 'body' => 'Sci di fondo'));
					$this->api->db->insert('category_i18n', array('category_id' => 131, 'language' => 'en', 'body' => 'Cross-country skiing'));

					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate version 6 to version 7
				|--------------------------------------------------------------------------
				|
				*/
				case 7:
					// Remove available_from/to
					$this->api->db->query("ALTER TABLE `product` DROP COLUMN `available_from`;");
					$this->api->db->query("ALTER TABLE `product` DROP COLUMN `available_to`;");

					// Update category: veranstaltungen
					$this->api->db->update('category', array('marker' => '362782'), array('category_id' => 1));
					// Update sub-categories: veranstaltungen
					$this->api->db->update('category', array('marker' => '362782'), array('parent_id' => 1));

					// Update category: informationen
					$this->api->db->update('category', array('marker' => '79BCDA'), array('category_id' => 79));
					// Update sub-categories: informationen
					$this->api->db->update('category', array('marker' => '79BCDA'), array('parent_id' => 79));

					// Update category: sehensw
					$this->api->db->update('category', array('marker' => '00625C'), array('category_id' => 22));
					// Update sub-categories: sehensw
					$this->api->db->update('category', array('marker' => '00625C'), array('parent_id' => 22));

					// Update category: reg prod
					$this->api->db->update('category', array('marker' => '8CCAAD'), array('category_id' => 19));
					// Update sub-categories: reg prod
					$this->api->db->update('category', array('marker' => '8CCAAD'), array('parent_id' => 19));

					// Update category: verpflegung
					$this->api->db->update('category', array('marker' => 'F3975F'), array('category_id' => 20));
					// Update sub-categories: verpflegung
					$this->api->db->update('category', array('marker' => 'F3975F'), array('parent_id' => 20));

					// Update category: beherbergung
					$this->api->db->update('category', array('marker' => 'C62945'), array('category_id' => 21));
					// Update sub-categories: beherbergung
					$this->api->db->update('category', array('marker' => 'C62945'), array('parent_id' => 21));

					// Update category: infra
					$this->api->db->update('category', array('marker' => 'D18391'), array('category_id' => 100));
					// Update sub-categories: infra
					$this->api->db->update('category', array('marker' => 'D18391'), array('parent_id' => 100));

					// Update category: pauschaulang
					$this->api->db->update('category', array('marker' => 'FCE237'), array('category_id' => 3));
					// Update sub-categories: pauschalang
					$this->api->db->update('category', array('marker' => 'FCE237'), array('parent_id' => 3));

					// Update category: sommerakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 50));
					// Update sub-categories: sommerakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 50));

					// Update category: winterakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 51));
					// Update sub-categories: winterakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 51));

					// Update category: forschung
					$this->api->db->update('category', array('marker' => '3A5BA6'), array('category_id' => 5000));
					// Update sub-categories: forschung
					$this->api->db->update('category', array('marker' => '3A5BA6'), array('parent_id' => 5000));

					// Update category: themenweg
					$this->api->db->update('category', array('marker' => '92D255'), array('category_id' => 63));
					// Update sub-categories: themenweg
					$this->api->db->update('category', array('marker' => '92D255'), array('parent_id' => 63));

					// Update category: wanderung
					$this->api->db->update('category', array('marker' => '4BA234'), array('category_id' => 64));
					// Update sub-categories: wanderung
					$this->api->db->update('category', array('marker' => '4BA234'), array('parent_id' => 64));

					// Update category: veloroute
					$this->api->db->update('category', array('marker' => '449BD5'), array('category_id' => 65));
					// Update sub-categories: veloroute
					$this->api->db->update('category', array('marker' => '449BD5'), array('parent_id' => 65));

					// Update category: e-bike routen
					$this->api->db->update('category', array('marker' => '2677C0'), array('category_id' => 78));
					// Update sub-categories: e-bike routen
					$this->api->db->update('category', array('marker' => '2677C0'), array('parent_id' => 78));

					// Update category: Mountainbiketour
					$this->api->db->update('category', array('marker' => 'FACA4D'), array('category_id' => 66));
					// Update sub-categories: Mountainbiketour
					$this->api->db->update('category', array('marker' => 'FACA4D'), array('parent_id' => 66));

					// Update category: reittouren
					$this->api->db->update('category', array('marker' => 'C09732'), array('category_id' => 82));
					// Update sub-categories: reittouren
					$this->api->db->update('category', array('marker' => 'C09732'), array('parent_id' => 82));

					// Update category: weitere routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 68));
					// Update sub-categories: weitere routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 68));

					// Update category: schneeschuhtour
					$this->api->db->update('category', array('marker' => 'BE2F7E'), array('category_id' => 69));
					// Update sub-categories: schneeschuhtour
					$this->api->db->update('category', array('marker' => 'BE2F7E'), array('parent_id' => 69));

					// Update category: Winterwanderung
					$this->api->db->update('category', array('marker' => 'EC7AAD'), array('category_id' => 70));
					// Update sub-categories: Winterwanderung
					$this->api->db->update('category', array('marker' => 'EC7AAD'), array('parent_id' => 70));

					// Update category: Langlaufstrecke
					$this->api->db->update('category', array('marker' => '60C1E9'), array('category_id' => 73));
					// Update sub-categories: Langlaufstrecke
					$this->api->db->update('category', array('marker' => '60C1E9'), array('parent_id' => 73));

					// Update category: Schlittelweg
					$this->api->db->update('category', array('marker' => '9E7967'), array('category_id' => 74));
					// Update sub-categories: Schlittelweg
					$this->api->db->update('category', array('marker' => '9E7967'), array('parent_id' => 74));

					// Update category: Weitere Routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 75));
					// Update sub-categories: Weitere Routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 75));

					break;


				/*
				|--------------------------------------------------------------------------
				| Migrate version 7 to version 8
				| «Pärke entdecken» step
				|--------------------------------------------------------------------------
				|
				*/
				case 8:
					// Update category: summer activities
					$this->api->db->update('category_i18n', array('body' => "Sommerrouten"), array('category_id' => 50, 'language' => 'de'));
					$this->api->db->update('category_i18n', array('body' => "Itin&eacute;raires d'&eacute;t&eacute;"), array('category_id' => 50, 'language' => 'fr'));
					$this->api->db->update('category_i18n', array('body' => "Itinerari estivi"), array('category_id' => 50, 'language' => 'it'));
					$this->api->db->update('category_i18n', array('body' => "Summer routes"), array('category_id' => 50, 'language' => 'en'));

					// Update category: winter activities
					$this->api->db->update('category_i18n', array('body' => "Winterrouten"), array('category_id' => 51, 'language' => 'de'));
					$this->api->db->update('category_i18n', array('body' => "Itin&eacute;raires d'hiver"), array('category_id' => 51, 'language' => 'fr'));
					$this->api->db->update('category_i18n', array('body' => "Itinerari invernali"), array('category_id' => 51, 'language' => 'it'));
					$this->api->db->update('category_i18n', array('body' => "Winter routes"), array('category_id' => 51, 'language' => 'en'));

					// Update category: veranstaltungen
					$this->api->db->update('category', array('marker' => '362782'), array('category_id' => 1));
					// Update sub-categories: veranstaltungen
					$this->api->db->update('category', array('marker' => '362782'), array('parent_id' => 1));

					// Update category: informationen
					$this->api->db->update('category', array('marker' => '79BCDA'), array('category_id' => 79));
					// Update sub-categories: informationen
					$this->api->db->update('category', array('marker' => '79BCDA'), array('parent_id' => 79));

					// Update category: sehensw
					$this->api->db->update('category', array('marker' => '00625C'), array('category_id' => 22));
					// Update sub-categories: sehensw
					$this->api->db->update('category', array('marker' => '00625C'), array('parent_id' => 22));

					// Update category: reg prod
					$this->api->db->update('category', array('marker' => '8CCAAD'), array('category_id' => 19));
					// Update sub-categories: reg prod
					$this->api->db->update('category', array('marker' => '8CCAAD'), array('parent_id' => 19));

					// Update category: verpflegung
					$this->api->db->update('category', array('marker' => 'F3975F'), array('category_id' => 20));
					// Update sub-categories: verpflegung
					$this->api->db->update('category', array('marker' => 'F3975F'), array('parent_id' => 20));

					// Update category: beherbergung
					$this->api->db->update('category', array('marker' => 'C62945'), array('category_id' => 21));
					// Update sub-categories: beherbergung
					$this->api->db->update('category', array('marker' => 'C62945'), array('parent_id' => 21));

					// Update category: infra
					$this->api->db->update('category', array('marker' => 'D18391'), array('category_id' => 100));
					// Update sub-categories: infra
					$this->api->db->update('category', array('marker' => 'D18391'), array('parent_id' => 100));

					// Update category: pauschaulang
					$this->api->db->update('category', array('marker' => 'FCE237'), array('category_id' => 3));
					// Update sub-categories: pauschalang
					$this->api->db->update('category', array('marker' => 'FCE237'), array('parent_id' => 3));

					// Update category: sommerakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 50));
					// Update sub-categories: sommerakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 50));

					// Update category: winterakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 51));
					// Update sub-categories: winterakti
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 51));

					// Update category: forschung
					$this->api->db->update('category', array('marker' => '3A5BA6'), array('category_id' => 5000));
					// Update sub-categories: forschung
					$this->api->db->update('category', array('marker' => '3A5BA6'), array('parent_id' => 5000));

					// Update category: themenweg
					$this->api->db->update('category', array('marker' => '92D255'), array('category_id' => 63));
					// Update sub-categories: themenweg
					$this->api->db->update('category', array('marker' => '92D255'), array('parent_id' => 63));

					// Update category: wanderung
					$this->api->db->update('category', array('marker' => '4BA234'), array('category_id' => 64));
					// Update sub-categories: wanderung
					$this->api->db->update('category', array('marker' => '4BA234'), array('parent_id' => 64));

					// Update category: veloroute
					$this->api->db->update('category', array('marker' => '449BD5'), array('category_id' => 65));
					// Update sub-categories: veloroute
					$this->api->db->update('category', array('marker' => '449BD5'), array('parent_id' => 65));

					// Update category: e-bike routen
					$this->api->db->update('category', array('marker' => '2677C0'), array('category_id' => 78));
					// Update sub-categories: e-bike routen
					$this->api->db->update('category', array('marker' => '2677C0'), array('parent_id' => 78));

					// Update category: Mountainbiketour
					$this->api->db->update('category', array('marker' => 'FACA4D'), array('category_id' => 66));
					// Update sub-categories: Mountainbiketour
					$this->api->db->update('category', array('marker' => 'FACA4D'), array('parent_id' => 66));

					// Update category: reittouren
					$this->api->db->update('category', array('marker' => 'C09732'), array('category_id' => 82));
					// Update sub-categories: reittouren
					$this->api->db->update('category', array('marker' => 'C09732'), array('parent_id' => 82));

					// Update category: weitere routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 68));
					// Update sub-categories: weitere routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 68));

					// Update category: schneeschuhtour
					$this->api->db->update('category', array('marker' => 'BE2F7E'), array('category_id' => 69));
					// Update sub-categories: schneeschuhtour
					$this->api->db->update('category', array('marker' => 'BE2F7E'), array('parent_id' => 69));

					// Update category: Winterwanderung
					$this->api->db->update('category', array('marker' => 'EC7AAD'), array('category_id' => 70));
					// Update sub-categories: Winterwanderung
					$this->api->db->update('category', array('marker' => 'EC7AAD'), array('parent_id' => 70));

					// Update category: Langlaufstrecke
					$this->api->db->update('category', array('marker' => '60C1E9'), array('category_id' => 73));
					// Update sub-categories: Langlaufstrecke
					$this->api->db->update('category', array('marker' => '60C1E9'), array('parent_id' => 73));

					// Update category: Schlittelweg
					$this->api->db->update('category', array('marker' => '9E7967'), array('category_id' => 74));
					// Update sub-categories: Schlittelweg
					$this->api->db->update('category', array('marker' => '9E7967'), array('parent_id' => 74));

					// Update category: Weitere Routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('category_id' => 75));
					// Update sub-categories: Weitere Routen
					$this->api->db->update('category', array('marker' => 'C6C6C6'), array('parent_id' => 75));

					// Update category: summer activities
					$this->api->db->update('category_i18n', array('body' => "Sommerrouten"), array('category_id' => 50, 'language' => 'de'));
					$this->api->db->update('category_i18n', array('body' => "Itin&eacute;raires d'&eacute;t&eacute;"), array('category_id' => 50, 'language' => 'fr'));
					$this->api->db->update('category_i18n', array('body' => "Itinerari estivi"), array('category_id' => 50, 'language' => 'it'));
					$this->api->db->update('category_i18n', array('body' => "Summer routes"), array('category_id' => 50, 'language' => 'en'));

					// Update category: winter activities
					$this->api->db->update('category_i18n', array('body' => "Winterrouten"), array('category_id' => 51, 'language' => 'de'));
					$this->api->db->update('category_i18n', array('body' => "Itin&eacute;raires d'hiver"), array('category_id' => 51, 'language' => 'fr'));
					$this->api->db->update('category_i18n', array('body' => "Itinerari invernali"), array('category_id' => 51, 'language' => 'it'));
					$this->api->db->update('category_i18n', array('body' => "Winter routes"), array('category_id' => 51, 'language' => 'en'));

					// Update target groups
					$this->api->db->query("ALTER TABLE `target_group` ADD COLUMN `sort` int(11) DEFAULT NULL;");
					$this->api->db->update('target_group', array('sort' => 0), array('target_group_id' => 1));
					$this->api->db->update('target_group', array('sort' => 35), array('target_group_id' => 2));
					$this->api->db->update('target_group', array('sort' => 37), array('target_group_id' => 3));
					$this->api->db->update('target_group', array('sort' => 10), array('target_group_id' => 4));
					$this->api->db->update('target_group', array('sort' => 20), array('target_group_id' => 5));
					$this->api->db->update('target_group', array('sort' => 30), array('target_group_id' => 6));
					$this->api->db->update('target_group', array('sort' => 50), array('target_group_id' => 7));
					$this->api->db->update('target_group', array('sort' => 60), array('target_group_id' => 8));
					$this->api->db->update('target_group', array('sort' => 70), array('target_group_id' => 9));
					$this->api->db->update('target_group', array('sort' => 80), array('target_group_id' => 10));
					$this->api->db->update('target_group', array('sort' => 38), array('target_group_id' => 11));

					// Update accommodation
					$this->api->db->query("ALTER TABLE `accommodation` ADD COLUMN `is_park_partner` TINYINT DEFAULT 0;");

					// Add primary key to offer_date table
					$this->api->db->query("DROP TABLE IF EXISTS `offer_date`;");
					$this->api->db->query("
						CREATE TABLE `offer_date` (
						  `offer_date_id` BIGINT NOT NULL AUTO_INCREMENT,
						  `offer_id` BIGINT DEFAULT NULL,
						  `date_from` DATETIME DEFAULT NULL,
						  `date_to` DATETIME DEFAULT NULL,
						  PRIMARY KEY (`offer_date_id`),
						  KEY `offer_id_idxfk_6` (`offer_id`),
						  CONSTRAINT `offer_date_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE
						) DEFAULT CHARSET=utf8;
					");

					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate version 8 to version 9
				| Accessibilities from Pro Infirmis
				|--------------------------------------------------------------------------
				|
				*/
				case 9:
					$this->api->db->query("
							CREATE TABLE `accessibility_pictogram`
							(
							`accessibility_pictogram_id` BIGINT NOT NULL,
							`pictogram_source` VARCHAR(1000),
							`name_de` VARCHAR(500),
							`name_en` VARCHAR(500),
							`name_fr` VARBINARY(500),
							`name_it` VARCHAR(500),
							`detail_link` VARCHAR(500),
							PRIMARY KEY (`accessibility_pictogram_id`)
							) CHARACTER SET=utf8;
						");
					$this->api->db->query("
							CREATE TABLE `accessibility`
							(
							`offer_id` BIGINT,
							`accessibility_pictogram_id` BIGINT NOT NULL,
							`poi_detail_link` VARCHAR(500),
							PRIMARY KEY (`offer_id`, `accessibility_pictogram_id`)
							) CHARACTER SET=utf8;
						");
					$this->api->db->query("
							ALTER TABLE `accessibility` ADD FOREIGN KEY offer_id_idxfk_34 (`offer_id`) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE;
						");

					// Reset target group foreign keys
					$this->api->db->query("
							ALTER TABLE `target_group_link` DROP FOREIGN KEY `target_group_link_ibfk_2`;
						");
					$this->api->db->query("
							ALTER TABLE `target_group_i18n` DROP FOREIGN KEY `target_group_i18n_ibfk_1`;
						");

					// Create new institution_location field
					$this->api->db->query("
							ALTER TABLE `offer` ADD COLUMN `institution_location` VARCHAR(500) NULL AFTER `institution`;
						");

					// Sync target groups
					$this->api->import->sync_target_groups();

					// Migrate/relink target group: Schulklassen Primarstufe
					$q_tg_link = $this->api->db->get('target_group_link', array('target_group_id' => 7));
					if (mysqli_num_rows($q_tg_link) > 0) {
						while ($row = mysqli_fetch_object($q_tg_link)) {
							$this->api->db->insert('target_group_link', array('offer_id' => $row->offer_id, 'target_group_id' => 12));
						}
					}

					// Rename category: bookable offer (only german)
					$this->api->db->query("
							UPDATE `category_i18n` SET `body` = 'Buchbares Angebot' WHERE `category_id` = 3 AND `language` = 'de' LIMIT 1
						");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 12
				| Activities: Routes, time required in minutes
				|--------------------------------------------------------------------------
				|
				*/
				case 12:
					$this->api->db->query("
							ALTER TABLE `activity` ADD COLUMN `time_required_minutes` INTEGER AFTER `time_required`;
						");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 13
				| Activities and product season months
				|--------------------------------------------------------------------------
				|
				*/
				case 13:
					$this->api->db->query("
							ALTER TABLE `activity` ADD COLUMN `season_months` VARCHAR(50) NULL;
						");
					$this->api->db->query("
							ALTER TABLE `product` ADD COLUMN `season_months` VARCHAR(50) NULL;
						");
					$this->api->db->query("
							ALTER TABLE `offer` DROP COLUMN `park_day`;
						");
					$this->api->db->query("
							ALTER TABLE `offer` DROP COLUMN `enjoy_week`;
						");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 14
				| Booking season months
				|--------------------------------------------------------------------------
				|
				*/
				case 14:
					$this->api->db->query("
							ALTER TABLE `booking` ADD COLUMN `season_months` VARCHAR(50) NULL;
						");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 15
				| Internal informations
				|--------------------------------------------------------------------------
				|
				*/
				case 15:
					$this->api->db->query("
							CREATE TABLE `hyperlink_intern`
							(
								`offer_id` BIGINT,
								`language` CHAR(2),
								`title` VARCHAR(255),
								`url` VARCHAR(255)
							) CHARACTER SET=utf8; 
						");
					$this->api->db->query("
							CREATE TABLE document_intern
							(
								`offer_id` BIGINT,
								`language` CHAR(2),
								`title` VARCHAR(255),
								`url` VARCHAR(255)
							) CHARACTER SET=utf8; 
						");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `costs` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `funding` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `partner` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `remarks` TEXT;");
					$this->api->db->query("ALTER TABLE `hyperlink_intern` ADD FOREIGN KEY offer_id_idxfk_36 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;");
					$this->api->db->query("ALTER TABLE `document_intern` ADD FOREIGN KEY offer_id_idxfk_37 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 16
				| Online Shop
				|--------------------------------------------------------------------------
				|
				*/
				case 16:
					$this->api->db->query("
							CREATE TABLE `product_article`
							(
								`product_article_id` BIGINT NOT NULL AUTO_INCREMENT,
								`offer_id` BIGINT,
								`supplier_contact` TEXT,
							PRIMARY KEY (`product_article_id`)
							) CHARACTER SET=utf8;
						");
					$this->api->db->query("
							CREATE TABLE `product_article_i18n`
							(
								`product_article_id` BIGINT,
								`language` CHAR(2),
								`article_title` VARCHAR(1000),
								`article_description` TEXT,
								`article_ingredients` TEXT,
							PRIMARY KEY (`product_article_id`,`language`)
							) CHARACTER SET=utf8;
						");
					$this->api->db->query("
							CREATE TABLE product_article_label
							(
								`product_article_id` BIGINT,
								`label_id` INT,
								`language` CHAR(2),
								`label_title` VARCHAR(1000),
								`label_url` VARCHAR(2000),
								`label_icon` VARCHAR(2000),
							PRIMARY KEY (`product_article_id`,`label_id`,`language`)
							) CHARACTER SET=utf8;
						");

					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `online_shop_payment_terms` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `online_shop_delivery_conditions` TEXT;");
					$this->api->db->query("ALTER TABLE `product` ADD COLUMN `online_shop_enabled` TINYINT;");
					$this->api->db->query("ALTER TABLE `product` ADD COLUMN `online_shop_price` FLOAT(10,2);");

					$this->api->db->query("ALTER TABLE `product_article` ADD FOREIGN KEY offer_id_idxfk_40 (offer_id) REFERENCES `offer` (`offer_id`) ON DELETE CASCADE;");
					$this->api->db->query("ALTER TABLE `product_article_i18n` ADD FOREIGN KEY product_article_id_idxfk (product_article_id) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE;");
					$this->api->db->query("ALTER TABLE `product_article_label` ADD FOREIGN KEY product_article_id_idxfk_2 (`product_article_id`) REFERENCES `product_article` (`product_article_id`) ON DELETE CASCADE;");

					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `title` `title` VARCHAR(1000) NULL DEFAULT NULL;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `abstract` `abstract` VARCHAR(1000) NULL DEFAULT NULL;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `description_medium` `description_medium` VARCHAR(1000) NULL DEFAULT NULL;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `description_long` `description_long` VARCHAR(1500) NULL DEFAULT NULL;");

					$this->api->db->query("ALTER TABLE `activity` ADD COLUMN `route_condition_id` TINYINT;");
					$this->api->db->query("ALTER TABLE `activity` ADD COLUMN `route_condition_color` VARCHAR(255);");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `route_condition` VARCHAR(500);");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `route_condition_details` VARCHAR(500);");
					$this->api->db->query("ALTER TABLE `activity` DROP COLUMN `route_condition`;");
					$this->api->db->query("ALTER TABLE `activity` DROP COLUMN `route_condition_details`;");

					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 18
				| Accessibility
				|--------------------------------------------------------------------------
				|
				*/
				case 18:

					$this->api->db->query("DROP TABLE `accessibility`;");
					$this->api->db->query("DROP TABLE `accessibility_pictogram`;");

					$this->api->db->query("
								CREATE TABLE `accessibility`
								(
									`accessibility_id` BIGINT UNSIGNED NOT NULL,
									`offer_id` BIGINT NOT NULL,
									`ginto_id` VARCHAR(255),
									`ginto_icon` VARCHAR(1000),
									`ginto_link` VARCHAR(1000),
								PRIMARY KEY (accessibility_id,offer_id)
								) ENGINE=InnoDB CHARACTER SET=utf8;
							");

					$this->api->db->query("
								CREATE TABLE `accessibility_rating`
								(
									`accessibility_rating_id` BIGINT NOT NULL,
									`accessibility_id` BIGINT UNSIGNED NOT NULL,
									`description_de` VARCHAR(500),
									`description_fr` VARCHAR(500),
									`description_it` VARBINARY(500),
									`description_en` VARCHAR(500),
									`icon_url` VARCHAR(1000),
									PRIMARY KEY (accessibility_rating_id)
								) ENGINE=InnoDB CHARACTER SET=utf8;
							");

					$this->api->db->query("ALTER TABLE `accessibility_rating` ADD FOREIGN KEY accessibility_id_idxfk (accessibility_id) REFERENCES accessibility (accessibility_id) ON DELETE CASCADE;");
					$this->api->db->query("ALTER TABLE `accessibility` ADD FOREIGN KEY offer_id_idxfk_10 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;");

					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 19
				| Linked routes
				|--------------------------------------------------------------------------
				|
				*/
				case 19:
					$this->api->db->query("UPDATE `activity` SET `poi` = CONCAT(`poi`, ',');");
					$this->api->db->query("ALTER TABLE `offer` CHANGE `is_hint` `is_hint` TINYINT(1)  NOT NULL  DEFAULT 0;");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 20
				| Accessibilities
				|--------------------------------------------------------------------------
				|
				*/
				case 20:

					// Accessibility dropdown
					$this->api->db->query("
						CREATE TABLE `accessibility_dropdown`
						(
							`accessibility_dropdown_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
							`icon_url` VARCHAR(1000),
							PRIMARY KEY (`accessibility_dropdown_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;
					");

					// New i18n text lengths
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `title` `title` VARCHAR(1000);");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `abstract` `abstract` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `description_medium` `description_medium` TEXT;"); 
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `description_long` `description_long` TEXT;"); 
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `location_details` `location_details` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `route_url` `route_url` TEXT;"); 
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `route_condition` `route_condition` TEXT;");
					$this->api->db->query("ALTER TABLE `offer_i18n` CHANGE `route_condition_details` `route_condition_details` TEXT;");

					// Fields of activity
					$this->api->db->query("
						CREATE TABLE `field_of_activity_link`
						(
							`offer_id` BIGINT,
							`field_of_activity_id` INTEGER,
							PRIMARY KEY (offer_id,field_of_activity_id)
						) ENGINE=InnoDB CHARACTER SET=utf8;
					");

					$this->api->db->query("
						CREATE TABLE `field_of_activity`
						(
							`field_of_activity_id` INTEGER AUTO_INCREMENT UNIQUE ,
							`sort` INTEGER,
							PRIMARY KEY (`field_of_activity_id`)
						) ENGINE=InnoDB CHARACTER SET=utf8;
					");

					$this->api->db->query("
						CREATE TABLE `field_of_activity_i18n`
						(
							`field_of_activity_id` INTEGER,
							`language` CHAR(2),
							`body` VARCHAR(255),
							PRIMARY KEY (field_of_activity_id,language)
						) ENGINE=InnoDB CHARACTER SET=utf8; 
					");

					$this->api->db->query("ALTER TABLE `field_of_activity_i18n` ADD FOREIGN KEY field_of_activity_id_idxfk (field_of_activity_id) REFERENCES field_of_activity (field_of_activity_id) ON DELETE CASCADE;"); 
					$this->api->db->query("ALTER TABLE `field_of_activity_link` ADD FOREIGN KEY offer_id_idxfk_42 (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;");

					// New project i18n data
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `project_initial_situation` TEXT AFTER `online_shop_delivery_conditions`;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `project_goal` TEXT AFTER `project_initial_situation`;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `project_further_information` TEXT AFTER `project_goal`;");
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `project_partner` TEXT AFTER `project_further_information`;");

					// Project duration
					$this->api->db->query("ALTER TABLE `project` ADD COLUMN `duration_from_month` TINYINT  AFTER `duration_from`;");
					$this->api->db->query("ALTER TABLE `project` ADD COLUMN `duration_to_month` TINYINT  AFTER `duration_to`;");

					// Webshop: Article informations
					$this->api->db->query("ALTER TABLE product_article ADD COLUMN is_food TINYINT;");
					$this->api->db->query("ALTER TABLE product_article_i18n ADD COLUMN article_allergens TEXT;");
					$this->api->db->query("ALTER TABLE product_article_i18n ADD COLUMN article_nutritional_values TEXT;");
					$this->api->db->query("ALTER TABLE product_article_i18n ADD COLUMN article_identity_label TEXT;");
					$this->api->db->query("ALTER TABLE product_article_i18n ADD COLUMN article_quantity_indication TEXT;");
					break;

				/*
				|--------------------------------------------------------------------------
				| Migrate to version 21
				| Municipalities
				|--------------------------------------------------------------------------
				|
				*/
				case 21:

					$this->api->db->query("
						CREATE TABLE `municipality`
						(
						`municipality_id` INTEGER NOT NULL,
						`park_id` INTEGER NOT NULL,
						`municipality` VARCHAR(255) NOT NULL,
						PRIMARY KEY (`municipality_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;
					");

					$this->api->db->query("
						CREATE TABLE `offer_municipality_link` (
						`offer_id` BIGINT NOT NULL,
						`municipality_id` INTEGER NOT NULL,
						PRIMARY KEY (`offer_id`,`municipality_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;
					");

					$this->api->db->query("ALTER TABLE offer_municipality_link ADD FOREIGN KEY offer_id_idxfk (offer_id) REFERENCES offer (offer_id) ON DELETE CASCADE;");
					$this->api->db->query("ALTER TABLE offer_municipality_link ADD FOREIGN KEY municipality_id_idxfk (municipality_id) REFERENCES municipality (municipality_id) ON DELETE CASCADE;");
					break;


				/*
				|--------------------------------------------------------------------------
				| Migrate to version 22
				| New interactive map
				|--------------------------------------------------------------------------
				|
				*/
				case 22:

					// Drop route table
					$this->api->db->query("DROP TABLE `offer_route`;");

					// Update map layer table
					$this->api->db->query("ALTER TABLE map_layer ADD COLUMN layer_category VARCHAR(255) AFTER languages;"); 
					$this->api->db->query("ALTER TABLE map_layer CHANGE popup_logo popup_logo VARCHAR(1000) DEFAULT '1';"); 
					$this->api->db->query("ALTER TABLE map_layer DROP COLUMN popup_logo_width;"); 
					$this->api->db->query("ALTER TABLE map_layer DROP COLUMN popup_logo_height;"); 
					$this->api->db->query("ALTER TABLE map_layer_i18n ADD COLUMN layer_title VARCHAR(500);");

					// Project results
					$this->api->db->query("ALTER TABLE `offer_i18n` ADD COLUMN `project_results` TEXT AFTER `project_further_information`;");

					// Misc
					$this->api->db->query("ALTER TABLE category CHANGE stnet_id stnet_id VARCHAR(10) NULL DEFAULT NULL;");
					$this->api->db->query("ALTER TABLE offer CHANGE is_hint is_hint TINYINT(1) NULL DEFAULT NULL;");

					break;

			}

		} else {

			// Show message
			$message = 'No valid version number set.';
			echo $message;

			// Log error
			$this->api->logger->error($message);
		}
	}


	
}