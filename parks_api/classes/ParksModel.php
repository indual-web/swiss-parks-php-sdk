<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Main model
|
*/


class ParksModel
{


	/**
	 * API
	 */
	public $api;


	/**
	 * Offer levels for difficulty
	 */
	public $levels;


	/**
	 * Target groups
	 */
	public $target_groups = [];


	/**
	 * All categories
	 */
	public $categories = [];



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

		// Set levels
		$this->levels = array(
			0 => '',
			1 => $this->api->lang->get('offer_easy'),
			2 => $this->api->lang->get('offer_average'),
			3 => $this->api->lang->get('offer_difficult')
		);

		// Set target groups
		$q_target_group = $this->api->db->get('target_group', array('language' => $this->api->lang->lang_id), array('target_group_i18n' => 'target_group.target_group_id = target_group_i18n.target_group_id'), NULL, NULL, NULL, NULL, 'target_group.sort');
		if (mysqli_num_rows($q_target_group) > 0) {
			while ($row = mysqli_fetch_object($q_target_group)) {
				$this->target_groups[$row->target_group_id] = $row->body;
			}
		}

		// Set categories
		$q_category = $this->api->db->get('category', array('language' => $this->api->lang->lang_id), array('category_i18n' => 'category.category_id = category_i18n.category_id'));
		if (mysqli_num_rows($q_category) > 0) {
			while ($row = mysqli_fetch_object($q_category)) {
				$this->categories[$row->category_id] = $row;
			}
		}
	}



	/**
	 * Checks if an offer with the given id exists
	 *
	 * @access public
	 * @param int $offer_id
	 * @return bool
	 */
	public function offer_exists($offer_id)
	{
		$q_offer = $this->api->db->query("SELECT offer_id FROM offer WHERE offer_id = " . $offer_id);
		if (mysqli_num_rows($q_offer) > 0) {
			return true;
		}

		return false;
	}



	/**
	 * Get Custom Layers
	 * [Deprecated with new map]
	 * 
	 * @access public
	 * @return mixed
	 */
	public function get_custom_layers()
	{

		$q_layers = $this->api->db->query("
			SELECT * 
			FROM map_layer 
			WHERE languages LIKE '%" . $this->api->lang->lang_id . "%'
		");

		if (mysqli_num_rows($q_layers) > 0) {
			$layers = [];

			while ($row = mysqli_fetch_object($q_layers)) {

				if (empty($row->map_layer_id)) {
					continue;
				}

				// Get i18n data
				$q_layers_i18n = $this->api->db->query("
					SELECT * 
					FROM map_layer_i18n 
					WHERE map_layer_id = " . $row->map_layer_id . "
				");

				if (mysqli_num_rows($q_layers_i18n) > 0) {
					
					$layer_i18n = [];

					// Parse content for each language
					while ($row_i18n = mysqli_fetch_object($q_layers_i18n)) {
						if (! empty($row_i18n->language)) {

							// Popup content
							if (! empty($row_i18n->popup_content)) {
								$layer_i18n[$row_i18n->language]['popup_content'] = $row_i18n->popup_content;
							}

							// Layer title
							if (! empty($row_i18n->layer_title)) {
								$layer_i18n[$row_i18n->language]['layer_title'] = $row_i18n->layer_title;
							}

						}
					}

					// Add i18n data to row
					$row->i18n = $layer_i18n;
					
				}

				$layers[$row->map_layer_id] = $row;
			}
			return $layers;
		}

		return false;
	}



	/**
	 * Get offers
	 *
	 * @param array $filter
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param bool $return_minimal
	 * @param bool $only_count_categories
	 * @param bool $map_mode
	 * @param bool $return_only_categories
	 * @param bool $ignore_hint_order
	 * @param bool $order_by_rand
	 * @return mixed
	 */
	public function filter_offers($filter, $limit = NULL, $offset = NULL, $return_minimal = false, $only_count_categories = false, $map_mode = false, $return_only_categories = false, $ignore_hint_order = false, $order_by_rand = false, $return_only_parks = false)
	{

		// Populate date filter
		$filter_date_from = ! empty($filter['date_from']) ? $this->_get_datetime($filter['date_from']) : '';
		$filter_date_to = ! empty($filter['date_to']) ? $this->_get_datetime($filter['date_to']) : '';

		// Select offers
		$select = "
			SELECT
				SQL_CALC_FOUND_ROWS main_offer.*,
				offer_i18n.*,
				offer_i18n.language AS language,
				activity.*,
				booking.*,
				event.*,
				product.*,
				project.*,
				subscription.*,
				MIN(offer_date.date_from) AS date_from,
				MAX(offer_date.date_to) AS date_to,
				main_offer.offer_id,
				
				MIN(
					CASE
						WHEN c3.category_id IS NOT NULL THEN c3.category_id
						WHEN c2.category_id IS NOT NULL THEN c2.category_id
						WHEN c1.category_id IS NOT NULL THEN c1.category_id
						ELSE NULL
					END
				) AS main_category_id,

				CASE 
					MIN(
						CASE
							WHEN c3.category_id IS NOT NULL THEN c3.category_id
							WHEN c2.category_id IS NOT NULL THEN c2.category_id
							WHEN c1.category_id IS NOT NULL THEN c1.category_id
							ELSE NULL
						END
					)
					WHEN " . CATEGORY_EVENT . " THEN event.public_transport_stop
					WHEN " . CATEGORY_PRODUCT . " THEN product.public_transport_stop
					WHEN " . CATEGORY_BOOKING . " THEN booking.public_transport_stop
				END AS public_transport_stop,
			
				CASE 
					MIN(
						CASE
							WHEN c3.category_id IS NOT NULL THEN c3.category_id
							WHEN c2.category_id IS NOT NULL THEN c2.category_id
							WHEN c1.category_id IS NOT NULL THEN c1.category_id
							ELSE NULL
						END
					)
					WHEN " . CATEGORY_BOOKING . " THEN booking.season_months
					WHEN " . CATEGORY_PRODUCT . " THEN product.season_months
					WHEN " . CATEGORY_ACTIVITY . " THEN activity.season_months
				END AS season_months,

				CASE 
					WHEN 
						MIN(
							CASE
								WHEN c3.category_id IS NOT NULL THEN c3.category_id
								WHEN c2.category_id IS NOT NULL THEN c2.category_id
								WHEN c1.category_id IS NOT NULL THEN c1.category_id
								ELSE NULL
							END
						) = " . CATEGORY_EVENT . "
						AND MIN(offer_date.date_from) < " . (! empty($filter_date_from) ? "'" . $filter_date_from . "'" : "NOW()") . "
						AND MIN(offer_date.date_from) IS NOT NULL
						THEN " . (! empty($filter_date_from) ? "'" . $filter_date_from . "'" : "CURDATE()") . "
					WHEN 
						MIN(
							CASE
								WHEN c3.category_id IS NOT NULL THEN c3.category_id
								WHEN c2.category_id IS NOT NULL THEN c2.category_id
								WHEN c1.category_id IS NOT NULL THEN c1.category_id
								ELSE NULL
							END
						) = " . CATEGORY_EVENT . "
						THEN DATE_FORMAT(MIN(offer_date.date_from), '%Y-%m-%d')
					ELSE 
						MIN(offer_i18n.title)
				END AS start_date,	

				TIMESTAMPDIFF(hour, MIN(offer_date.date_from), MAX(offer_date.date_to)) AS duration,
				IFNULL( DATEDIFF(MAX(offer_date.date_to), MIN(offer_date.date_from) ), 0) AS date_difference,

				CASE 
					WHEN 
						MIN(offer_date.date_from) IS NOT NULL
						AND (
							DATE_FORMAT(MIN(offer_date.date_from), '%Y-%m-%d') = DATE_FORMAT(MAX(offer_date.date_to), '%Y-%m-%d')
							OR MAX(offer_date.date_to) = '0000-00-00 00:00:00'
							OR MAX(offer_date.date_to) IS NULL
						)
						AND MIN(
							CASE
								WHEN c3.category_id IS NOT NULL THEN c3.category_id
								WHEN c2.category_id IS NOT NULL THEN c2.category_id
								WHEN c1.category_id IS NOT NULL THEN c1.category_id
								ELSE NULL
							END
						) = " . CATEGORY_EVENT . "
					THEN GROUP_CONCAT(DISTINCT DATE_FORMAT(offer_date.date_from, '%H:%i'), ' - ', DATE_FORMAT(offer_date.date_to, '%H:%i'))
					ELSE ''
				END AS times,

				CASE 
					MIN(
						CASE
							WHEN c3.category_id IS NOT NULL THEN c3.category_id
							WHEN c2.category_id IS NOT NULL THEN c2.category_id
							WHEN c1.category_id IS NOT NULL THEN c1.category_id
							ELSE NULL
						END
					)
					WHEN " . CATEGORY_ACTIVITY . " THEN activity.poi
					WHEN " . CATEGORY_PROJECT . " THEN project.poi
				END AS poi,

				GROUP_CONCAT(DISTINCT accessibility_rating.icon_url SEPARATOR ', ') AS icon_urls

			FROM offer main_offer
		";

		// Alternative mode: count categories
		if ($only_count_categories == true) {
			$select = "
				SELECT
					COUNT(event.offer_id) as event_count,
					COUNT(booking.offer_id) as booking_count,
					COUNT(activity.offer_id) as activity_count,
					COUNT(product.offer_id) as product_count,
					COUNT(project.offer_id) as project_count
				FROM offer main_offer
			";
		}

		// Alternative mode: get only categories
		else if ($return_only_categories == true) {
			$select = "
				SELECT category_link.category_id
				FROM offer main_offer
			";
		}
		// Alternative mode: get only parks
		else if ($return_only_parks == true) {
			$select = "
				SELECT main_offer.park_id, MIN(main_offer.park) AS park
				FROM offer main_offer
			";
		}

		// Inner joins
		$join = "
			INNER JOIN offer_i18n ON main_offer.offer_id = offer_i18n.offer_id
			INNER JOIN category_link ON category_link.offer_id = main_offer.offer_id
			INNER JOIN category c1 ON c1.category_id = category_link.category_id
			LEFT JOIN category c2 ON c1.parent_id = c2.category_id
			LEFT JOIN category c3 ON c2.parent_id = c3.category_id
		";

		// Left joins
		$join .= "
			LEFT OUTER JOIN event ON event.offer_id = main_offer.offer_id
			LEFT OUTER JOIN booking ON booking.offer_id = main_offer.offer_id
			LEFT OUTER JOIN activity ON activity.offer_id = main_offer.offer_id
			LEFT OUTER JOIN product ON product.offer_id = main_offer.offer_id
			LEFT OUTER JOIN project ON project.offer_id = main_offer.offer_id
			LEFT OUTER JOIN subscription ON subscription.offer_id = main_offer.offer_id
			LEFT OUTER JOIN subscription_i18n ON subscription_i18n.offer_id = main_offer.offer_id AND subscription_i18n.language = '" . $this->api->lang->lang_id . "'
			LEFT OUTER JOIN offer_date ON main_offer.offer_id = offer_date.offer_id
			LEFT OUTER JOIN accessibility ON accessibility.offer_id = main_offer.offer_id
			LEFT OUTER JOIN accessibility_rating ON accessibility_rating.accessibility_id = accessibility.accessibility_id
		";

		// Where conditions
		$where = [];
		
		// Filter: categories
		$categories = (array)($filter['categories'] ?? []);
		$categories = array_filter($categories, fn($id) => $id !== '');
		if (! empty($categories)) {
			$category_conditions = [];
			foreach ($categories as $category_id) {
				$category_conditions[] = "
					(
						c1.category_id = " . $category_id . "
						OR c2.category_id = " . $category_id . "
						OR c3.category_id = " . $category_id . "
					)
				";
			}
			$where[] = '(' . implode(' OR ', $category_conditions) . ')';
		}

		// Filter: is hint
		if (isset($filter['is_hint'])) {
			if ($filter['is_hint'] == 1) {
				$where[] = "main_offer.is_hint = 1";
			} else {
				$where[] = "
					(
						main_offer.is_hint = 0
						OR main_offer.is_hint IS NULL
					)
				";
			}
		}

		// Filter: contact_is_park_partner
		if (isset($filter['contact_is_park_partner'])) {
			if ($filter['contact_is_park_partner'] == 1) {
				$where[] = "main_offer.contact_is_park_partner = 1";
			} else {
				$where[] = "
					(
						main_offer.contact_is_park_partner = 0
						OR main_offer.contact_is_park_partner IS NULL
					)
				";
			}
		}

		// Filter: offers_is_park_event
		if (isset($filter['offers_is_park_event'])) {
			if ($filter['offers_is_park_event'] == true) {
				$where[] = "event.is_park_event = 1";
			} else {
				$where[] = "
					(
						event.is_park_event = 0
						OR event.is_park_event IS NULL
					)
				";
			}
		}

		// Filter: has accessibility informations
		if (! empty($filter['has_accessibility_informations'])) {
			$where[] = "accessibility.accessibility_id IS NOT NULL";
		}

		// Filter: accessilibity ratiing
		if (! empty($filter['accessibilities'])) {
			foreach ($filter['accessibilities'] as $accessibility_id) {
				if ($accessibility_id == GINTO_INFOS_AVAILABLE) {
					$where[] = "accessibility.accessibility_id IS NOT NULL";
				} else {
					$having[] = "icon_urls LIKE '%" . $accessibility_id . "%'";
				}
			}
		}

		// Select language and show every entry, no matter which language is available
		if (empty($filter['force_language']) && isset($this->api->config['language_independence']) && ($this->api->config['language_independence'] == true) && isset($this->api->config['language_priority'])) {

			// Get language priorities
			$language_priority = $this->api->config['language_priority'][$this->api->lang->lang_id];
			if (! empty($language_priority) && is_array($language_priority)) {

				$where[] = "
					offer_i18n.language = (
						SELECT IF (sub_offer_i18n.language IS NOT NULL, sub_offer_i18n.language, (
								SELECT IF (sub_offer_i18n.language IS NOT NULL, sub_offer_i18n.language, (
										SELECT
											IF (sub_offer_i18n.language IS NOT NULL, sub_offer_i18n.language, ((
												SELECT IF (sub_offer_i18n.language IS NOT NULL, sub_offer_i18n.language, 'de')
												FROM offer sub_offer
												LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[2] . "'
												WHERE main_offer.offer_id = sub_offer.offer_id)
											))
										FROM offer sub_offer
										LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[1] . "'
										WHERE main_offer.offer_id = sub_offer.offer_id
									))
								FROM offer sub_offer
								LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[0] . "'
								WHERE main_offer.offer_id = sub_offer.offer_id
							))
						FROM offer sub_offer
						LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $this->api->lang->lang_id . "'
						WHERE main_offer.offer_id = sub_offer.offer_id
					)
				";
			}
		}

		// Select only offers which are available in the current language
		else {
			$where[] = "offer_i18n.language = '" . ($filter['force_language'] ?: $this->api->lang->lang_id) . "'";
		}

		// Filter: offer settings
		if (! empty($filter['offer_settings'])) {
			foreach ($filter['offer_settings'] as $key => $value) {
				$where[] = "main_offer." . $key . " = '" . $value . "'";
			}
		}

		// Filter: target groups
		if (! empty($filter['target_groups']) || ! empty($this->api->system_filter['target_groups'])) {
			$join .= "
				LEFT JOIN target_group_link ON target_group_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . implode(',', $filter['target_groups']) . "))";
			}
			if (! empty($this->api->system_filter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . implode(',', $this->api->system_filter['target_groups']) . "))";
			}
		}

		// Filter: fields of activity
		if (! empty($filter['fields_of_activity']) || ! empty($this->api->system_filter['fields_of_activity'])) {
			$join .= "
				LEFT JOIN field_of_activity_link ON field_of_activity_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . implode(',', $filter['fields_of_activity']) . "))";
			}
			if (! empty($this->api->system_filter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . implode(',', $this->api->system_filter['fields_of_activity']) . "))";
			}
		}

		// Filter: municipalities
		if (! empty($filter['municipalities'])) {
			$join .= "
				LEFT OUTER JOIN offer_municipality_link ON offer_municipality_link.offer_id = main_offer.offer_id
			";
			if (is_array($filter['municipalities'])) {
				$where[] = "(offer_municipality_link.municipality_id IN (" . implode(', ', $filter['municipalities']) . "))";
			}
			else {
				$where[] = "offer_municipality_link.municipality_id = " . $filter['municipalities'];
			}
		}

		// Filter: park or user
		if (! empty($filter['park_id'])) {
			if (! is_array($filter['park_id'])) {
				$where[] = "main_offer.park_id = " . intval($filter['park_id']);
			} else {
				$where_in_park_ids = implode(",", $filter['park_id']);
				$where[] = "main_offer.park_id IN (" . $where_in_park_ids . ")";
			}
		}
		if (! empty($filter['user'])) {
			$where[] = "main_offer.park = '" . $filter['user'] . "'";
		}

		// Filter: exlcude parks
		if (! empty($filter['exclude_park_ids']) && is_array($filter['exclude_park_ids'])) {
			$where[] = "main_offer.park NOT IN (" . implode(",", $filter['exclude_park_ids']) . ")";
		}

		// Filter: offers of today
		if (isset($filter['offers_of_today']) && ($filter['offers_of_today'] == true)) {
			$now = new DateTime();
			$filter['date_from'] = $now->format('Y-m-d');
			$filter['date_to'] = $now->format('Y-m-d');
		}

		// Filter: dates
		if (! empty($filter['date_from']) || ! empty($filter['date_to'])) {

			// Only filter dates on events
			$having[] = " (main_category_id = " . CATEGORY_EVENT . ") ";

			// Check time span
			if (! empty($filter_date_from) && ! empty($filter_date_to)) {

				// First, check if this offer date is between the filter dates
				// Second, check if this date is in the filter time span
				$where[] = "
					(
						(
							'" . $filter_date_from . "' BETWEEN date_from AND date_to
							OR
							'" . $filter_date_to . "' BETWEEN date_from AND date_to
						)

						OR

						(
							date_from >= DATE('" . $filter_date_from . "') AND date_from < DATE_ADD('" . $filter_date_to . "', INTERVAL 1 DAY)
							OR
							date_to >= DATE('" . $filter_date_from . "') AND date_to < DATE_ADD('" . $filter_date_to . "', INTERVAL 1 DAY)
						)
					)
				";
			}

			// Check only date from
			else if (! empty($filter_date_from)) {
				$where[] = " ( offer_date.date_from >= '" . $filter_date_from . "' OR offer_date.date_to >= '" . $filter_date_from . "' ) ";
			}

			// Check only date to
			else if (! empty($filter_date_to)) {
				$where[] = "
					(

						# its a timespan:
						# check only date_to (add 1 day)
						(
							date_to IS NOT NULL
							AND
							date_to != '0000-00-00 00:00:00'
							AND
							date_to < '" . $filter_date_to . " 23:59:59'
							AND
							date_to >= NOW()
						)

						OR

						# or, its only a date_from
						# check date from (add 1 day)
						(
							(
								date_to IS NULL
								OR
								date_to = '0000-00-00 00:00:00'
							)
							AND
							date_from < '" . $filter_date_to . " 00:00:00'
							AND
							date_from >= NOW()
						)

					) 
				";
			}
		} else {
			// Show only coming events
			$having[] = "
				(
					date_from IS NULL
					OR date_from >= NOW()
					OR date_to >= NOW()
					OR main_category_id != " . CATEGORY_EVENT . "
				)
			";
		}

		// Filter: search words
		if (isset($filter['search']) && ($filter['search'] != '')) {
			$search_words = explode(' ', $filter['search']);
			if (! empty($search_words)) {
				$search_query = "(";
				foreach ($search_words as $word) {
					$search_query .= "(
								(main_offer.offer_id = '" . $word . "')
								OR main_offer.keywords LIKE '%" . $word . "%'
								OR (offer_i18n.title LIKE '%" . $word . "%')
								OR (offer_i18n.abstract LIKE '%" . $word . "%')
								OR (offer_i18n.description_medium LIKE '%" . $word . "%')
								OR (offer_i18n.description_long LIKE '%" . $word . "%')
								OR (offer_i18n.details LIKE '%" . $word . "%')
								OR (offer_i18n.price LIKE '%" . $word . "%')
								OR (offer_i18n.location_details LIKE '%" . $word . "%')
								OR (offer_i18n.opening_hours LIKE '%" . $word . "%')
								OR (offer_i18n.benefits LIKE '%" . $word . "%')
								OR (offer_i18n.requirements LIKE '%" . $word . "%')
								OR (offer_i18n.additional_informations LIKE '%" . $word . "%')
								OR (offer_i18n.catering_informations LIKE '%" . $word . "%')
								OR (offer_i18n.material_rent LIKE '%" . $word . "%')
								OR (offer_i18n.safety_instructions LIKE '%" . $word . "%')
								OR (offer_i18n.signalization LIKE '%" . $word . "%')
								OR (offer_i18n.other_infrastructure LIKE '%" . $word . "%')
								OR (offer_i18n.project_initial_situation LIKE '%" . $word . "%')
								OR (offer_i18n.project_goal LIKE '%" . $word . "%')
								OR (offer_i18n.project_further_information LIKE '%" . $word . "%')
								OR (offer_i18n.project_results LIKE '%" . $word . "%')
								OR (offer_i18n.project_partner LIKE '%" . $word . "%')
							) AND ";
				}
				$search_query = substr($search_query, 0, -4) . ")";
				$where[] = $search_query;
			}
		}

		// Filter: keywords
		if (isset($filter['keywords']) && ($filter['keywords'] != '')) {
			$keywords = explode(' ', $filter['keywords']);
			if (! empty($keywords)) {
				$restriction = 'OR';
				if ($this->api->config['filter_keywords_with_and'] == true) {
					$restriction = 'AND';
				}
				$keywords_query = "(";
				foreach ($keywords as $word) {
					$keywords_query .= " main_offer.keywords LIKE '%" . $word . "%' " . $restriction . " ";
				}
				$keywords_query = substr($keywords_query, 0, -4) . ")";
				$where[] = $keywords_query;
			}
		}

		// Filter: route length
		if (! empty($filter['route_length_min']) && intval($filter['route_length_min']) != 0) {
			$where[] = " (activity.route_length >= " . intval($filter['route_length_min']) . ")";
		}

		// Filter: route max length
		if (! empty($filter['route_length_max']) && intval($filter['route_length_max']) != 50) {
			$where[] = " (activity.route_length <= " . intval($filter['route_length_max']) . ")";
		}

		// Filter: time required
		if (! empty($filter['time_required'])) {
			if (is_array($filter['time_required'])) {

				// Time minutes
				$where_time_required = [];
				$time_category_minutes = array(
					'< 2h' => '(activity.time_required_minutes <= 120)',
					'2 - 4h' => '((activity.time_required_minutes > 120) AND (activity.time_required_minutes <= 240))',
					'4 - 6h' => '((activity.time_required_minutes > 240) AND (activity.time_required_minutes <= 360))',
					'> 6h' => '(activity.time_required_minutes > 360)',
				);

				// Prepare filter data
				foreach ($filter['time_required'] as $key => $value) {

					// Decode html
					$filter['time_required'][$key] = html_entity_decode($value);

					// Set time condition
					if (! empty($time_category_minutes[$value])) {
						$where_time_required[] = "
							(
								(
									activity.time_required_minutes IS NULL
									AND
									activity.time_required = '" . $value . "'
								)
								OR
								(
									activity.time_required_minutes IS NOT NULL
									AND
									activity.time_required_minutes <> 0
									AND
									" . $time_category_minutes[$value] . "
								)
							)
						";
					}
				}

				if (! empty($where_time_required)) {
					$where[] = '(' . implode(' OR ', $where_time_required) . ')';
				}
			}
		}

		// Filter: level_technics
		if (! empty($filter['level_technics'])) {
			if (is_array($filter['level_technics'])) {
				$where_in_level_technics = implode(",", $filter['level_technics']);
				$where[] = " (activity.level_technics IN (" . $where_in_level_technics . "))";
			} else {
				$where[] = " (activity.level_technics = " . $filter['level_technics'] . ")";
			}
		}

		// Filter: time_required
		if (! empty($filter['level_condition'])) {
			if (is_array($filter['level_condition'])) {
				$where_in_level_condition = implode(",", $filter['level_condition']);
				$where[] = " (activity.level_condition IN (" . $where_in_level_condition . "))";
			} else {
				$where[] = " (activity.level_condition = " . $filter['level_condition'] . "L)";
			}
		}

		// Filter: project status
		if (! empty($filter['project_status'])) {
			if (is_array($filter['project_status'])) {
				$where_in_level_condition = implode(",", $filter['project_status']);
				$where[] = " (project.status IN (" . $where_in_level_condition . "))";
			} else {
				$where[] = " (project.status = " . $filter['project_status'] . "L)";
			}
		}

		// Filter: additional infos
		$where_additional = [];
		if (isset($filter['online_shop_enabled']) && $filter['online_shop_enabled'] == 1) {
			$where_additional[] = "product.online_shop_enabled = 1";
		}
		if (isset($filter['barrier_free']) && $filter['barrier_free'] == 1) {
			$where_additional[] = "main_offer.barrier_free = 1";
		}
		if (isset($filter['learning_opportunity']) && $filter['learning_opportunity'] == 1) {
			$where_additional[] = "main_offer.learning_opportunity = 1";
		}
		if (isset($filter['child_friendly']) && $filter['child_friendly'] == 1) {
			$where_additional[] = "main_offer.child_friendly = 1";
		}
		if (! empty($where_additional)) {
			$where[] = implode(' OR ', $where_additional);
		}

		// Filter: offers
		if (! empty($filter['offers'])) {
			if (! is_array($filter['offers'])) {
				$filter['offers'] = array($filter['offers']);
			}
			if (! empty($filter['offers'])) {
				$where_offer = "";
				foreach ($filter['offers'] as $offer) {
					$where_offer .= " main_offer.offer_id = " . $offer . " OR ";
				}
				$where_offer = substr($where_offer, 0, -4);
				$where[] = "(" . $where_offer . ")";
			}
		}

		// Init where
		if (! empty($where) && is_array($where)) {
			$where = " WHERE " . implode(" AND ", $where);
		} else {
			$where = "";
		}

		// Init having
		if (! empty($having) && is_array($having) && ($only_count_categories == false) && ($return_only_categories == false) && ($return_only_parks == false)) {
			$having = " HAVING " . implode(" AND ", $having);
		} else {
			$having = "";
		}

		// Group and order by
		$group_by = $order_by = "";
		if ($return_only_parks == true) {
			$group_by = " GROUP BY main_offer.park_id ";
			$order_by = " ORDER BY park ASC ";
		}
		else if (($only_count_categories == false) && ($return_only_categories == false)) {

			// Group by
			$group_by = "
				GROUP BY 
					CASE 
						WHEN (
							offer_date.date_from IS NOT NULL 
							AND CASE
								WHEN c3.category_id IS NOT NULL THEN c3.category_id
								WHEN c2.category_id IS NOT NULL THEN c2.category_id
								WHEN c1.category_id IS NOT NULL THEN c1.category_id
								ELSE NULL
							END IN (" . CATEGORY_EVENT . ")
						)
						THEN DATE_FORMAT(offer_date.date_from, '%Y-%m-%d')
						ELSE main_offer.offer_id
					END,
					main_offer.offer_id,
					offer_i18n.language
			";

			// Order by
			if ($order_by_rand === true) {
				$order_by = " ORDER BY RAND() ";
			} elseif ($ignore_hint_order === true) {
				$order_by = "
					ORDER BY 
						start_date, 
						date_difference ASC, 
						MIN(offer_date.date_from) ASC, 
						duration ASC, 
						CAST(MAX(offer_date.date_to) AS DATE) ASC,
						MIN(offer_i18n.title) ASC
				";
			} else {
				$order_by = "
					ORDER BY
						is_hint DESC, 
						CASE
							WHEN 
								MIN(
									CASE
										WHEN c3.category_id IS NOT NULL THEN c3.category_id
										WHEN c2.category_id IS NOT NULL THEN c2.category_id
										WHEN c1.category_id IS NOT NULL THEN c1.category_id
										ELSE NULL
									END
								) IN (" . CATEGORY_PROJECT . ", " . CATEGORY_RESEARCH . ")
							THEN project.duration_from 
							ELSE NULL
							END DESC,
						CASE 
							WHEN 
								MIN(offer_date.date_from) IS NOT NULL 
								AND 
								MIN(
									CASE
										WHEN c3.category_id IS NOT NULL THEN c3.category_id
										WHEN c2.category_id IS NOT NULL THEN c2.category_id
										WHEN c1.category_id IS NOT NULL THEN c1.category_id
										ELSE NULL
									END
								) = " . CATEGORY_EVENT . " 
							THEN MIN(offer_date.date_from)
							ELSE MIN(offer_i18n.title)
							END,
						MIN(offer_i18n.title) ASC
				";
			}

		}

		// Limit query
		$limit_sql = "";
		if (!is_null($limit) && is_numeric($limit) && ($limit > 0)) {
			$limit_sql = " LIMIT " . (isset($offset) && is_numeric($offset) ? intval($offset) . ', ' : '') . intval($limit);
		}

		// Run query
		$q_offers = $this->api->db->query($select . $join . $where . $group_by . $having . $order_by . $limit_sql);

		// Return only count of offers
		if ($only_count_categories == true) {
			return mysqli_fetch_object($q_offers);
		}

		// Return only linked categories
		elseif ($return_only_categories == true) {
			return $q_offers;
		}
		// Return only parks
		elseif ($return_only_parks == true) {
			return $q_offers;
		}
		// Return offer data
		else if (mysqli_num_rows($q_offers) > 0) {

			// Get total
			$total_query = $this->api->db->query("SELECT FOUND_ROWS()");
			$result_array = mysqli_fetch_array($total_query);

			// Get offers
			$offers = array(
				'data' => array(),
				'total' => array_shift($result_array)
			);

			while ($offer = mysqli_fetch_object($q_offers)) {
				if (! empty($offer) && ($offer->offer_id > 0)) {
					$offers['data'][] = $this->get_offer($offer, $return_minimal, $map_mode);
				}
			}

			return $offers;
		}

		return false;
	}



	/**
	 * Get offer with additional infos
	 *
	 * @param mixed $offer
	 * @param boolean $return_minimal
	 * @param boolean $map_mode
	 * @return mixed
	 */
	public function get_offer($offer, $return_minimal = false, $map_mode = false)
	{

		// Get offer if needed
		if (is_numeric($offer) && ! is_object($offer)) {

			// Filter by offer id
			$filter = array('offers' => $offer);

			// Get offer main data
			$offer = $this->filter_offers($filter, 1, 0);
			if (isset($offer['data']) && is_array($offer['data'])) {
				$offer = array_shift($offer['data']);
			} else {
				$offer = [];
			}
		}

		// Get additional offer informations
		if (is_object($offer) && ! empty($offer) && ($offer->offer_id > 0)) {

			// Get offer category links
			$offer->root_category = NULL;
			$q_categories = $this->api->db->get('category_link', array('offer_id' => $offer->offer_id));
			if (mysqli_num_rows($q_categories) > 0) {
				$offer->categories = [];
				while ($row = mysqli_fetch_object($q_categories)) {
					$offer->categories[$row->category_id] = $this->get_category($row->category_id);
					if (!$offer->root_category) {
						$offer->root_category = $this->_get_root_category($row->category_id);
					}
				}
			}

			// Get offer target groups
			if ($map_mode == false) {
				$q_target_groups = $this->api->db->get('target_group_link', array('offer_id' => $offer->offer_id));
				if (mysqli_num_rows($q_target_groups) > 0) {

					// Get target group links
					$target_group_links = [];
					while ($row = mysqli_fetch_object($q_target_groups)) {
						if (! empty($row->target_group_id) && array_key_exists($row->target_group_id, $this->target_groups)) {
							$target_group_links[] = $row->target_group_id;
						}
					}

					// Sort target groups
					$offer->target_groups = [];
					if (! empty($this->target_groups) && ! empty($target_group_links)) {
						foreach ($this->target_groups as $target_group_id => $target_group) {
							if (in_array($target_group_id, $target_group_links)) {
								$offer->target_groups[$target_group_id] = $target_group;
							}
						}
					}
				}
			}

			// Get dates
			$offer->dates = [];
			if (($map_mode == false) || (($map_mode == true) && in_array($offer->root_category, [CATEGORY_EVENT, CATEGORY_RESEARCH]))) {
				$q_dates = $this->api->db->get(
					'offer_date',
					array('offer_id' => $offer->offer_id),
					NULL,
					array("DATE_FORMAT(date_from, '" . $this->api->config['mysql_date_format'] . "')" => 'date_from', "DATE_FORMAT(date_to, '" . $this->api->config['mysql_date_format'] . "')" => 'date_to'),
					NULL,
					NULL,
					NULL,
					"DATE_FORMAT(date_from, '%Y-%m-%d %H:%i')"
				);
				if (mysqli_num_rows($q_dates) > 0) {
					while ($row = mysqli_fetch_object($q_dates)) {
						$offer->dates[] = $row;
					}
				}
			}

			// Get images
			$q_images = $this->api->db->get('image', array('offer_id' => $offer->offer_id));
			if (mysqli_num_rows($q_images) > 0) {
				$offer->images = [];
				while ($row = mysqli_fetch_object($q_images)) {
					$offer->images[] = $row;
				}
			}

			// Get extended data
			if ($return_minimal == false) {

				// Documents
				if ($map_mode == false) {
					$q_documents = $this->api->db->get('document', array('offer_id' => $offer->offer_id, 'language' => $this->api->lang->lang_id));
					if (mysqli_num_rows($q_documents) > 0) {
						$offer->documents = [];
						while ($row = mysqli_fetch_object($q_documents)) {
							$offer->documents[] = $row;
						}
					}
				}

				// Documents intern
				if ($map_mode == false) {
					$q_documents = $this->api->db->get('document_intern', array('offer_id' => $offer->offer_id, 'language' => $this->api->lang->lang_id));
					if (mysqli_num_rows($q_documents) > 0) {
						$offer->documents_intern = [];
						while ($row = mysqli_fetch_object($q_documents)) {
							$offer->documents_intern[] = $row;
						}
					}
				}

				// Hyperlinks
				if ($map_mode == false) {
					$q_hyperlinks = $this->api->db->get('hyperlink', array('offer_id' => $offer->offer_id, 'language' => $this->api->lang->lang_id));
					if (mysqli_num_rows($q_hyperlinks) > 0) {
						$offer->hyperlinks = [];
						while ($row = mysqli_fetch_object($q_hyperlinks)) {
							$offer->hyperlinks[] = $row;
						}
					}
				}

				// Hyperlinks intern
				if ($map_mode == false) {
					$q_hyperlinks = $this->api->db->get('hyperlink_intern', array('offer_id' => $offer->offer_id, 'language' => $this->api->lang->lang_id));
					if (mysqli_num_rows($q_hyperlinks) > 0) {
						$offer->hyperlinks_intern = [];
						while ($row = mysqli_fetch_object($q_hyperlinks)) {
							$offer->hyperlinks_intern[] = $row;
						}
					}
				}

				// Accessibilities
				if ($map_mode == false) {

					// Get main accessibility data
					$q_accessibilities = $this->api->db->query("
						SELECT *
						FROM accessibility
						WHERE accessibility.offer_id = " . $offer->offer_id . "
						LIMIT 1
					");
					if (mysqli_num_rows($q_accessibilities) > 0) {

						// Get accessibility data
						$offer->accessibilities = mysqli_fetch_object($q_accessibilities);

						// Get accessibility ratings
						$q_ratings = $this->api->db->query("
							SELECT *
							FROM accessibility_rating
							WHERE accessibility_rating.accessibility_id = " . $offer->accessibilities->accessibility_id . "
						");
						if (mysqli_num_rows($q_accessibilities) > 0) {
							while ($rating = mysqli_fetch_object($q_ratings)) {
								$offer->accessibilities->ratings[] = $rating;
							}
						}
					}
				}
			}

			// Product
			if (($offer->root_category == CATEGORY_PRODUCT) && ($map_mode == false)) {

				// Suppliers
				$q_suppliers = $this->api->db->get('supplier', array('offer_id' => $offer->offer_id));
				if (mysqli_num_rows($q_suppliers) > 0) {
					$offer->suppliers = [];
					while ($row = mysqli_fetch_object($q_suppliers)) {
						$offer->suppliers[] = array('contact' => $row->contact, 'is_park_partner' => $row->is_park_partner);
					}
				}

				// Online shop: product articles
				if (($offer->online_shop_enabled == true) && ($return_minimal == false)) {

					// Get language priorities
					$language_priority = $this->api->config['language_priority'][$this->api->lang->lang_id];
					if (! empty($language_priority) && is_array($language_priority)) {

						// Get articles including i18n and respecting language priorities
						$q_articles = $this->api->db->query("

							SELECT *
							FROM product_article AS main_article
							INNER JOIN product_article_i18n main_article_i18n ON main_article_i18n.product_article_id = main_article.product_article_id
							WHERE 
								main_article.offer_id = " . $offer->offer_id . "
								AND main_article_i18n.language = (
													
									SELECT IF (sub_article_i18n.language IS NOT NULL, sub_article_i18n.language, (
										SELECT IF (sub_article_i18n.language IS NOT NULL, sub_article_i18n.language, (
											SELECT
												IF (sub_article_i18n.language IS NOT NULL, sub_article_i18n.language, ((
													SELECT IF (sub_article_i18n.language IS NOT NULL, sub_article_i18n.language, 'de')
													FROM product_article AS sub_article
													LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[2] . "'
													WHERE main_article.product_article_id = sub_article.product_article_id)
												))
											FROM product_article AS sub_article
											LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[1] . "'
											WHERE main_article.product_article_id = sub_article.product_article_id
										))
										FROM product_article AS sub_article
										LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[0] . "'
										WHERE main_article.product_article_id = sub_article.product_article_id
									))
									FROM product_article AS sub_article
									LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $this->api->lang->lang_id . "'
									WHERE main_article.product_article_id = sub_article.product_article_id
									
								)

						");

						// Iterate each article
						if (mysqli_num_rows($q_articles) > 0) {
							$offer->articles = [];
							while ($article = mysqli_fetch_object($q_articles)) {

								// Set article labels
								$q_article_labels = $this->api->db->query("
									SELECT *
									FROM product_article_label
									WHERE 
										product_article_id = " . $article->product_article_id . "
										AND language = '" . $this->api->lang->lang_id . "'
								");
								if (mysqli_num_rows($q_article_labels) > 0) {
									$article->labels = [];
									while ($article_label = mysqli_fetch_object($q_article_labels)) {
										$article->labels[] = $article_label;
									}
								}

								// Set article
								$offer->articles[] = $article;
							}
						}
					}
				}
			}

			// Booking
			else if (($offer->root_category == CATEGORY_BOOKING) && ($map_mode == false)) {

				// Accommodations
				$q_accommodations = $this->api->db->get('accommodation', array('offer_id' => $offer->offer_id));
				if (mysqli_num_rows($q_accommodations) > 0) {
					$offer->accommodations = [];

					while ($row = mysqli_fetch_object($q_accommodations)) {
						$offer->accommodations[] = array('contact' => $row->contact, 'is_park_partner' => $row->is_park_partner);
					}
				}
			}

			// Project and research
			else if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
				$offer->project_status = $offer->status;
			}

			// Poi
			if (($return_minimal == false) && ($map_mode == false)) {
				if (! empty($offer->poi) && ($offer->poi != '') && ! is_array($offer->poi)) {
					$poi = explode(',', $offer->poi);

					// Check if offer exists
					$existing_poi = [];
					if (! empty($poi) && is_array($poi)) {
						foreach ($poi as $offer_id) {
							if (! empty($offer_id)) {
								$q_poi = $this->api->db->get('offer', array('offer_id' => $offer_id));
								if ($q_poi->num_rows == 1) {
									$existing_poi[] = $offer_id;
								}
							}
						}
					}

					$offer->poi = $existing_poi;
				}
			}

			// Linked routes
			if (($return_minimal == false) && ($map_mode == false)) {
				$linked_routes = [];
				$q_linked_routes = $this->api->db->query("
					SELECT offer_id
					FROM activity
					WHERE 
						poi LIKE '" . $offer->offer_id . ",%'
						OR poi LIKE '%," . $offer->offer_id . ",%'
				");
				if (mysqli_num_rows($q_linked_routes) > 0) {
					while ($linked_route = mysqli_fetch_assoc($q_linked_routes)) {
						if (! empty($linked_route['offer_id'])) {
							$q_poi = $this->api->db->get('offer', array('offer_id' => $linked_route['offer_id']));
							if ($q_poi->num_rows == 1) {
								$linked_routes[] = $linked_route['offer_id'];
							}
						}
					}
				}
				$offer->linked_routes = $linked_routes;
			}

			return $offer;
		}

		return false;
	}



	/**
	 * Get category by ID
	 *
	 * @access public
	 * @param int $category_id
	 * @return mixed
	 */
	public function get_category($category_id)
	{
		if (array_key_exists($category_id, $this->categories)) {
			return $this->categories[$category_id];
		}
		
		return false;
	}



	/**
	 * Get path for a category
	 *
	 * @access public
	 * @param int $category_id
	 * @return array
	 */
	public function get_category_path($category_id)
	{
		if (! empty($category_id)) {
			$path = [];

			while ($category_id > 0) {
				$category = $this->categories[$category_id];
				$category_id = $category->parent_id;
				$path[] = $category->category_id;
			}

			return $path;
		}

		return [];
	}



	/**
	 * Get all categories
	 *
	 * @access public
	 * @return array
	 */
	public function get_all_categories($filter = [])
	{
		$q_categories_string = "
			SELECT
				c1.category_id as category_id,
				c1.parent_id,
				category_i18n.body,
				c1.marker,
				CONCAT_WS(',', c1.parent_id, c2.parent_id, c3.parent_id, c4.parent_id) AS parents,
				c1.sort
			FROM category c1
			INNER JOIN category_i18n ON category_i18n.category_id = c1.category_id AND category_i18n.language = '" . $this->api->lang->lang_id . "'
			LEFT JOIN category c2 ON c1.parent_id = c2.category_id
			LEFT JOIN category c3 ON c2.parent_id = c3.category_id
			LEFT JOIN category c4 ON c3.parent_id = c4.category_id
		";

		// Additional information
		if (! empty($filter) && count($filter) > 0) {

			$q_categories_string .= "
				LEFT JOIN category_link ON category_link.category_id = c1.category_id
				LEFT JOIN offer ON category_link.offer_id = offer.offer_id
				LEFT JOIN event ON event.offer_id = offer.offer_id
				WHERE 1
			";

			$q_additional = $this->_prepare_additional_infos($filter);
			if (! empty($q_additional)) {
				$q_categories_string .= "AND (1 " . $q_additional . ") OR (c1.parent_id = 0)";
			}
		}

		$q_categories_string .= "GROUP BY c1.category_id ";
		$q_categories_string .= "ORDER BY c1.sort ";

		$q_categories = $this->api->db->query($q_categories_string);

		if (mysqli_num_rows($q_categories) > 0) {
			$categories = [];

			while ($row = mysqli_fetch_object($q_categories)) {
				$categories[$row->category_id] = (object) array(
					'category_id' => $row->category_id,
					'parent_id' => $row->parent_id,
					'language' => $this->api->lang->lang_id,
					'body' => $row->body,
					'marker' => $row->marker,
					'parents' => explode(",", $row->parents),
					'sort' => $row->sort
				);
			}

			return $categories;
		}

		return [];
	}



	/**
	 * Get all category links
	 *
	 * @access public
	 * @return mixed
	 */
	public function get_all_category_links()
	{
		$q_category_links = $this->api->db->query("SELECT category_id FROM category_link GROUP BY category_link.category_id");

		if (mysqli_num_rows($q_category_links) > 0) {
			
			// Add main categories
			$category_links = [
				CATEGORY_EVENT,
				CATEGORY_PRODUCT,
				CATEGORY_BOOKING,
				CATEGORY_ACTIVITY,
				CATEGORY_PROJECT,
				CATEGORY_RESEARCH
			];

			while ($row = mysqli_fetch_object($q_category_links)) {
				$category_links[] = $row->category_id;
			}

			return $category_links;
		}

		return false;
	}



	/**
	 * Get all users/parks
	 *
	 * @param array $categories
	 * @param array $filter
	 * @return mixed
	 */
	public function get_all_users($categories = [], $filter = [])
	{
		if (empty($categories)) {
			$categories = $this->get_all_category_links();
		}

		if (! isset($filter['categories']) && is_array($categories) && ! empty($categories)) {
			$filter['categories'] = $categories;
		}

		$q_users = $this->filter_offers($filter, NULL, NULL, true, false, false, false, true, false, true);

		if ($q_users && (mysqli_num_rows($q_users) > 0)) {
			$users = [];

			while ($row = mysqli_fetch_object($q_users)) {
				$users[$row->park_id] = $row->park;
			}

			return $users;
		}

		return false;
	}



	/**
	 * Get full category tree
	 *
	 * @access public
	 * @return array
	 */
	public function get_category_tree()
	{
		$categories = [];
		$q_categories = $this->api->db->query("

			SELECT
				category.category_id,
				IF (category.parent_id = 0, category_i18n.body,
					IF (c2.parent_id = 0, CONCAT('--- ', category_i18n.body),
						IF (c3.parent_id = 0, CONCAT('------ ', category_i18n.body), CONCAT('--------- ', category_i18n.body))
					)
				) AS body,
				category.sort,
				category.alpstein_id,
				category.marker

				FROM category
				LEFT JOIN category AS c2 ON category.parent_id = c2.category_id
				LEFT JOIN category AS c3 ON c2.parent_id = c3.category_id
				INNER JOIN category_i18n ON category_i18n.category_id = category.category_id

				WHERE category_i18n.language = '" . $this->api->lang->lang_id . "'
				ORDER BY category.sort

		");

		if (mysqli_num_rows($q_categories) > 0) {
			while ($category = mysqli_fetch_object($q_categories)) {
				$categories[$category->category_id] = $category->body;
			}
		}

		return $categories;
	}



	/**
	 * Sync target groups
	 *
	 * @access public
	 * @param array $target_groups
	 * @return bool
	 */
	public function sync_target_groups($target_groups)
	{
		if (! empty($target_groups) && is_array($target_groups)) {

			// Delete existing target groups
			$this->api->db->delete('target_group');
			$this->api->db->delete('target_group_i18n');

			// Insert target groups
			foreach ($target_groups as $lang => $items) {
				$sort = 0;
				foreach ($items as $target_group_id => $target_group) {
					$sort++;

					// Table target_group – only once
					if ($lang == 'de') {
						$this->api->db->insert('target_group', array(
							'target_group_id' => $target_group_id,
							'sort' => $sort,
						));
					}

					// Table target_group_i18n
					$this->api->db->insert('target_group_i18n', array(
						'target_group_id' => $target_group_id,
						'language' => $lang,
						'body' => $target_group,
					));
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Sync categories
	 *
	 * @access public
	 * @param array $categories
	 * @return bool
	 */
	public function sync_categories($categories)
	{
		if (! empty($categories) && is_array($categories)) {

			// Delete existing categories
			$this->api->db->delete('category');
			$this->api->db->delete('category_i18n');

			// Insert categories
			foreach ($categories as $lang => $items) {
				foreach ($items as $category_id => $category) {

					// Table category – only once
					if ($lang == 'de') {
						$this->api->db->insert('category', array(
							'category_id' => $category_id,
							'parent_id' => $category['parent_id'],
							'stnet_id' => $category['stnet_id'],
							'alpstein_id' => $category['alpstein_id'],
							'contact_visible_for_alpstein' => $category['contact_visible_for_alpstein'],
							'marker' => $category['marker'],
							'sort' => $category['sort'],
						));
					}

					// Table category_i18n
					$this->api->db->insert('category_i18n', array(
						'category_id' => $category_id,
						'language' => $lang,
						'body' => $category['body'],
					));
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Sync fields of activity
	 *
	 * @access public
	 * @param array $fields_of_activity
	 * @return bool
	 */
	public function sync_fields_of_activity($fields_of_activity)
	{
		if (! empty($fields_of_activity) && is_array($fields_of_activity)) {

			// Delete existing fields of activity
			$this->api->db->delete('field_of_activity');
			$this->api->db->delete('field_of_activity_i18n');

			// Insert fields of activity
			foreach ($fields_of_activity as $lang => $items) {
				$sort = 0;
				foreach ($items as $field_of_activity_id => $field_of_activity) {
					$sort++;

					// Table field_of_activity – only once
					if ($lang == 'de') {
						$this->api->db->insert('field_of_activity', array(
							'field_of_activity_id' => $field_of_activity_id,
							'sort' => $sort,
						));
					}

					// Table field_of_activity_i18n
					$this->api->db->insert('field_of_activity_i18n', array(
						'field_of_activity_id' => $field_of_activity_id,
						'language' => $lang,
						'body' => $field_of_activity,
					));
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Get municipalities
	 * 
	 * @param array $filter
	 * @return array
	 */
	public function get_municipalities($filter = [])
	{

		// Get the municipalities
		$q_municipalities = $this->api->db->get('municipality', $filter);

		// Return empty array if there are no municipalities
		if (mysqli_num_rows($q_municipalities) <= 0) {
			return [];
		}
		
		// Format municipalities
		$municipalities = [];
		while ($row = mysqli_fetch_array($q_municipalities)) {
			$municipalities[$row['municipality_id']] = $row['municipality'];
		}

		// Sort municipalities by name
		asort($municipalities);

		// Return municipalities
		return $municipalities;
	}



	/**
	 * Private method for retrieving the root category
	 *
	 * @param int $category_id
	 * @return integer|bool
	 */
	private function _get_root_category($category_id)
	{
		if (! empty($category_id)) {

			while ($category_id > 0) {
				$category = $this->categories[$category_id];
				$category_id = $category->parent_id;
				$last_category_id = $category->category_id;
			}

			return (int)$last_category_id;
		}

		return false;
	}



	/**
	 * Get date time
	 *
	 * @access private
	 * @param mixed $value
	 * @param string $format (default: 'Y-m-d H:i:s')
	 * @return string
	 */
	private function _get_datetime($value, $format = 'Y-m-d H:i:s')
	{
		$datetime = new DateTime($value);
		return $datetime->format($format);
	}



	/**
	 * Prepare additional infos for sql query
	 *
	 * @access private
	 * @param array $filter
	 * @return string
	 */
	private function _prepare_additional_infos($filter)
	{
		$additional_query = '';
		if (! empty($filter) && is_array($filter)) {
			foreach ($filter as $key => $value) {

				if (! is_array($value) && ! empty($value)) {
					$key = str_replace('offers_', '', $key);
					if (! empty($key) && is_string($key)) {
						switch ($key) {

							case 'keywords':
								$additional_query .= " AND offer.keywords LIKE '%" . $value . "%'";
								break;

							case 'filter_hints':
							case 'offers_filter_hints':
								$additional_query .= " AND offer.is_hint = '" . $value . "'";
								break;

							case 'contact_is_park_partner':
								$additional_query .= " AND offer.contact_is_park_partner = '" . $value . "'";
								break;

							case 'search':
							case 'order_by':
							case 'has_accessibility_informations':
							case 'online_shop_enabled':
								break;

							case 'is_park_event':
								$additional_query .= " AND event.is_park_event = '" . $value . "'";
								break;

							default:
								if (! empty($key) && is_string($key) && (strlen($key) > 2)) {
									$additional_query .= " AND offer." . $key . " = '" . $value . "'";
								}
								break;
						}
					}
				}
			}
		}

		return $additional_query;
	}



	/**
	 * Get dropdown list with all accessiblity options
	 *
	 * @return array
	 */
	function get_accessibility_list()
	{

		// Init
		$accessibilites = [];
		$filter_options = [];

		// Get all possible dropdown options
		$options = $this->api->db->query("
			SELECT *
			FROM accessibility_dropdown
		");
		if (mysqli_num_rows($options) > 0) {
			while ($option = mysqli_fetch_array($options)) {
				$filter_options[] = $option['icon_url'];
			}
		}

		// Get accessiblity options
		$select_result = $this->api->db->query("
			SELECT 
				icon_url, 
				MIN(description_" . $this->api->lang->lang_id . ") AS description
			FROM accessibility_rating
			GROUP BY icon_url
			ORDER BY description
		");

		// Populate accessibilities
		if (mysqli_num_rows($select_result) > 0) {
			$accessibilites[GINTO_INFOS_AVAILABLE] = $this->api->lang->get('offer_accessibility_available');
			while ($row = mysqli_fetch_array($select_result)) {
				if (in_array($row['icon_url'], $filter_options)) {
					$accessibilites[$row['icon_url']] = $row['description'];
				}
			}
		}

		return $accessibilites;
	}




	/**
	 * Sync accessibility dropdown list
	 *
	 * @access public
	 * @param array $options
	 * @return bool
	 */
	public function sync_accessibilities($options)
	{
		if (! empty($options) && is_array($options)) {

			// Delete existing list
			$this->api->db->delete('accessibility_dropdown');

			// Iterate each option
			foreach ($options as $option) {

				// Insert option
				$this->api->db->insert('accessibility_dropdown', array(
					'icon_url' => $option,
				));
			}

			return true;
		}

		return false;
	}



}