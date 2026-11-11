<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksModel
{


	/**
	 * API
	 */
	public ParksAPI $api;


	/**
	 * Offer levels for difficulty
	 */
	public array $levels;


	/**
	 * Target groups
	 */
	public array $targetGroups = [];


	/**
	 * Fields of activity
	 */
	public array $fieldsOfActivity = [];


	/**
	 * All categories
	 */
	public array $categories = [];


	/**
	 * Request-local cache for get_filter_meta()
	 */
	private ?string $filterMetaCacheKey = null;
	private array|false|null $filterMetaCache = null;



	/**
	 * Constructor
	 */
	public function __construct(ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Set levels
		$this->levels = [
			0 => '',
			1 => $this->api->lang->get('offer_easy'),
			2 => $this->api->lang->get('offer_average'),
			3 => $this->api->lang->get('offer_difficult'),
		];

		$this->reloadTargetGroups();
		$this->reloadFieldsOfActivity();
		$this->reloadCategories();
	}



	/**
	 * Reload fields of activity from database
	 */
	public function reloadFieldsOfActivity(): void
	{

		$this->fieldsOfActivity = [];

		$q_fields = $this->api->db->get(
			'field_of_activity',
			['language' => $this->api->lang->langId],
			['field_of_activity_i18n' => 'field_of_activity.field_of_activity_id = field_of_activity_i18n.field_of_activity_id'],
			null,
			null,
			null,
			null,
			'field_of_activity.sort'
		);

		if ($q_fields && $q_fields->numRows > 0) {
			while ($row = $q_fields->fetchObject()) {
				$this->fieldsOfActivity[$row->field_of_activity_id] = $row->body;
			}
		}

	}



	/**
	 * Reload target groups from database
	 */
	public function reloadTargetGroups(): void
	{

		$this->targetGroups = [];

		$q_target_group = $this->api->db->get('target_group', ['language' => $this->api->lang->langId], ['target_group_i18n' => 'target_group.target_group_id = target_group_i18n.target_group_id'], null, null, null, null, 'target_group.sort');
		if ($q_target_group->numRows > 0) {
			while ($row = $q_target_group->fetchObject()) {
				$this->targetGroups[$row->target_group_id] = $row->body;
			}
		}

	}



	/**
	 * Reload categories from database
	 */
	public function reloadCategories(): void
	{

		$this->categories = [];

		$q_category = $this->api->db->get('category', ['language' => $this->api->lang->langId], ['category_i18n' => 'category.category_id = category_i18n.category_id']);
		if ($q_category->numRows > 0) {
			while ($row = $q_category->fetchObject()) {
				$this->categories[$row->category_id] = $row;
			}
		}

	}



	/**
	 * Checks if an offer with the given id exists
	 */
	public function offerExists(int $offer_id): bool
	{

		$q_offer = $this->api->db->query("SELECT offer_id FROM offer WHERE offer_id = " . $offer_id);
		if ($q_offer->numRows > 0) {
			return true;
		}

		return false;
	}



	/**
	 * Get Custom Layers
	 * [Deprecated with new map]
	 */
	public function getCustomLayers(): array|false
	{

		$q_layers = $this->api->db->query("
			SELECT * 
			FROM map_layer 
			WHERE languages LIKE '%" . $this->api->lang->langId . "%'
		");

		if ($q_layers->numRows > 0) {
			$layers = [];

			while ($row = $q_layers->fetchObject()) {

				if (empty($row->map_layer_id)) {
					continue;
				}

				// Get i18n data
				$q_layers_i18n = $this->api->db->query("
					SELECT * 
					FROM map_layer_i18n 
					WHERE map_layer_id = " . $row->map_layer_id . "
				");

				if ($q_layers_i18n->numRows > 0) {

					$layer_i18n = [];

					// Parse content for each language
					while ($row_i18n = $q_layers_i18n->fetchObject()) {
						if (! empty($row_i18n->language)) {

							// Service URL
							if (! empty($row_i18n->url)) {
								$layer_i18n[$row_i18n->language]['url'] = $row_i18n->url;
							}

							// Popup title
							if (! empty($row_i18n->popup_title)) {
								$layer_i18n[$row_i18n->language]['popup_title'] = $row_i18n->popup_title;
							}

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

				// Resolve service URL for the current language (fallback de → fr → it → en)
				$urls = [];
				foreach (($row->i18n ?? []) as $language => $fields) {
					if (! empty($fields['url'])) {
						$urls[$language] = $fields['url'];
					}
				}
				$row->url = ParksMap::resolveI18nUrl($urls, $this->api->lang->langId, $row->languages ?? '');

				$layers[$row->map_layer_id] = $row;
			}
			return $layers;
		}

		return false;
	}



	/**
	 * Get offers matching filter criteria
	 */
	public function filterOffers(
		array $filter,
		?int $limit = null,
		?int $offset = null,
		bool $return_minimal = false,
		bool $map_mode = false,
		bool $ignore_hint_order = false,
		bool $order_by_rand = false
	): array|false {

		$result = $this->queryOffers($filter, $limit, $offset, $return_minimal, $map_mode, $ignore_hint_order, $order_by_rand, 'list');

		return is_array($result) ? $result : false;
	}



	/**
	 * Count offers grouped by main category type
	 */
	public function countOffersByCategory(array $filter): stdClass|false
	{

		$meta = $this->getFilterMeta($filter);
		if ($meta === false) {
			return false;
		}

		return (object) [
			'event_count' => $meta['event_count'],
			'booking_count' => $meta['booking_count'],
			'activity_count' => $meta['activity_count'],
			'product_count' => $meta['product_count'],
			'project_count' => $meta['project_count'],
		];
	}



	/**
	 * Get category IDs that have at least one matching offer
	 */
	public function getFilterCategories(array $filter): ParksSQLiteResult|false
	{

		$meta = $this->getFilterMeta($filter);
		if ($meta === false) {
			return false;
		}

		$rows = [];
		foreach ($meta['category_ids'] as $category_id) {
			$rows[] = ['category_id' => $category_id];
		}

		return new ParksSQLiteResult($rows);
	}



	/**
	 * Get parks that have at least one matching offer
	 */
	public function getFilterParks(array $filter): ParksSQLiteResult|false
	{

		$result = $this->queryOffers($filter, null, null, false, false, true, false, 'parks');

		return ($result instanceof ParksSQLiteResult) ? $result : false;
	}



	/**
	 * One query for filter UI: type counts, category IDs, parks
	 */
	public function getFilterMeta(array $filter): array|false
	{

		// Reuse the last meta payload when the filter has not changed
		$cache_key = md5(serialize($filter));
		if ($this->filterMetaCacheKey === $cache_key) {
			return $this->filterMetaCache;
		}

		$result = $this->queryOffers($filter, null, null, false, false, false, false, 'filter_meta');
		if (! ($result instanceof stdClass)) {
			$this->filterMetaCacheKey = $cache_key;
			$this->filterMetaCache = false;

			return false;
		}

		// category_ids arrive as a comma-separated GROUP_CONCAT string
		$category_ids = [];
		if (! empty($result->category_ids)) {
			foreach (explode(',', (string) $result->category_ids) as $category_id) {
				$category_id = (int) $category_id;
				if ($category_id > 0) {
					$category_ids[$category_id] = $category_id;
				}
			}
		}

		// park_pairs use RS/US separators from GROUP_CONCAT(park_id || US || name)
		$parks = [];
		if (! empty($result->park_pairs)) {
			foreach (explode("\x1e", (string) $result->park_pairs) as $pair) {
				if ($pair === '') {
					continue;
				}
				[$parkId, $park] = array_pad(explode("\x1f", $pair, 2), 2, '');
				$parkId = (int) $parkId;
				if ($parkId > 0 && $park !== '') {
					$parks[$parkId] = $park;
				}
			}
		}

		$meta = [
			'event_count' => (int) ($result->event_count ?? 0),
			'booking_count' => (int) ($result->booking_count ?? 0),
			'activity_count' => (int) ($result->activity_count ?? 0),
			'product_count' => (int) ($result->product_count ?? 0),
			'project_count' => (int) ($result->project_count ?? 0),
			'category_ids' => $category_ids,
			'parks' => $parks,
		];

		$this->filterMetaCacheKey = $cache_key;
		$this->filterMetaCache = $meta;

		return $meta;
	}



	/**
	 * Build and run offers query (internal)
	 */
	private function queryOffers(
		array $filter,
		?int $limit,
		?int $offset,
		bool $return_minimal,
		bool $map_mode,
		bool $ignore_hint_order,
		bool $order_by_rand,
		string $mode
	): ParksSQLiteResult|stdClass|array|false {

		// Populate date filter
		$filter_date_from = ! empty($filter['date_from']) ? $this->formatDatetime($filter['date_from']) : '';
		$filter_date_to = ! empty($filter['date_to']) ? $this->formatDatetime($filter['date_to']) : '';

		// Accessibility joins only when filtering by accessibility
		$needs_accessibility_join = (
			(isset($filter['has_accessibility_informations']) && ($filter['has_accessibility_informations'] == true))
			|| ! empty($filter['accessibilities'])
		);
		$needs_accessibility_rating_join = false;
		if (! empty($filter['accessibilities'])) {
			foreach ($filter['accessibilities'] as $accessibility_id) {
				if ($accessibility_id != GINTO_INFOS_AVAILABLE) {
					$needs_accessibility_rating_join = true;
					break;
				}
			}
		}

		// Subscription columns only for full detail rows
		$needs_subscription_join = (($mode === 'list') && ($return_minimal == false) && ($map_mode == false));
		$subscription_select = $needs_subscription_join ? "subscription.*," : "";
		$icon_urls_select = $needs_accessibility_rating_join
			? "GROUP_CONCAT(DISTINCT accessibility_rating.icon_url) AS icon_urls"
			: "NULL AS icon_urls";

		// Select offers
		$select = "
			SELECT
				main_offer.*,
				offer_i18n.*,
				offer_i18n.language AS language,
				activity.*,
				booking.*,
				event.*,
				product.*,
				project.*,
				" . $subscription_select . "
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

				CAST((strftime('%s', MAX(offer_date.date_to)) - strftime('%s', MIN(offer_date.date_from))) / 3600 AS INTEGER) AS duration,
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
					THEN GROUP_CONCAT(DISTINCT DATE_FORMAT(offer_date.date_from, '%H:%i') || ' - ' || DATE_FORMAT(offer_date.date_to, '%H:%i'))
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

				" . $icon_urls_select . "

			FROM offer main_offer
		";

		// Alternative mode: filter UI meta (counts + categories + parks in one pass)
		if ($mode === 'filter_meta') {
			$select = "
				SELECT
					COUNT(event.offer_id) as event_count,
					COUNT(booking.offer_id) as booking_count,
					COUNT(activity.offer_id) as activity_count,
					COUNT(product.offer_id) as product_count,
					COUNT(project.offer_id) as project_count,
					GROUP_CONCAT(DISTINCT category_link.category_id) AS category_ids,
					GROUP_CONCAT(DISTINCT main_offer.park_id || char(31) || main_offer.park, char(30)) AS park_pairs
				FROM offer main_offer
			";
		}

		// Alternative mode: get only parks
		else if ($mode === 'parks') {
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

		// Left joins (subscription only when full detail rows are needed)
		$join .= "
			LEFT OUTER JOIN event ON event.offer_id = main_offer.offer_id
			LEFT OUTER JOIN booking ON booking.offer_id = main_offer.offer_id
			LEFT OUTER JOIN activity ON activity.offer_id = main_offer.offer_id
			LEFT OUTER JOIN product ON product.offer_id = main_offer.offer_id
			LEFT OUTER JOIN project ON project.offer_id = main_offer.offer_id
			LEFT OUTER JOIN offer_date ON main_offer.offer_id = offer_date.offer_id
		";

		if ($needs_subscription_join) {
			$join .= "
				LEFT OUTER JOIN subscription ON subscription.offer_id = main_offer.offer_id
				LEFT OUTER JOIN subscription_i18n ON subscription_i18n.offer_id = main_offer.offer_id AND subscription_i18n.language = '" . $this->api->lang->langId . "'
			";
		}

		if ($needs_accessibility_join) {
			$join .= "
				LEFT OUTER JOIN accessibility ON accessibility.offer_id = main_offer.offer_id
			";
		}
		if ($needs_accessibility_rating_join) {
			$join .= "
				LEFT OUTER JOIN accessibility_rating ON accessibility_rating.accessibility_id = accessibility.accessibility_id
			";
		}

		// Where conditions
		$where = [];
		$having = [];

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
			if ($filter['is_hint'] == true) {
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
			if ($filter['contact_is_park_partner'] == true) {
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
		if (isset($filter['has_accessibility_informations']) && ($filter['has_accessibility_informations'] == true)) {
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
			$language_priority = $this->api->config['language_priority'][$this->api->lang->langId];
			if (! empty($language_priority) && is_array($language_priority)) {

				$where[] = "
					offer_i18n.language = (
						SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE (
								SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE (
										SELECT
											CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE ((
												SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE 'de' END
												FROM offer sub_offer
												LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[2] . "'
												WHERE main_offer.offer_id = sub_offer.offer_id)
											) END
										FROM offer sub_offer
										LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[1] . "'
										WHERE main_offer.offer_id = sub_offer.offer_id
									) END
								FROM offer sub_offer
								LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $language_priority[0] . "'
								WHERE main_offer.offer_id = sub_offer.offer_id
							) END
						FROM offer sub_offer
						LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $this->api->lang->langId . "'
						WHERE main_offer.offer_id = sub_offer.offer_id
					)
				";
			}
		}

		// Select only offers which are available in the current language
		else {
			$where[] = "offer_i18n.language = '" . ($filter['force_language'] ?: $this->api->lang->langId) . "'";
		}

		// Filter: offer settings
		if (! empty($filter['offer_settings'])) {
			foreach ($filter['offer_settings'] as $key => $value) {
				$where[] = "main_offer." . $key . " = '" . $value . "'";
			}
		}

		// Filter: target groups
		if (! empty($filter['target_groups']) || ! empty($this->api->systemFilter['target_groups'])) {
			$join .= "
				LEFT JOIN target_group_link ON target_group_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . implode(',', $filter['target_groups']) . "))";
			}
			if (! empty($this->api->systemFilter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . implode(',', $this->api->systemFilter['target_groups']) . "))";
			}
		}

		// Filter: fields of activity
		if (! empty($filter['fields_of_activity']) || ! empty($this->api->systemFilter['fields_of_activity'])) {
			$join .= "
				LEFT JOIN field_of_activity_link ON field_of_activity_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . implode(',', $filter['fields_of_activity']) . "))";
			}
			if (! empty($this->api->systemFilter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . implode(',', $this->api->systemFilter['fields_of_activity']) . "))";
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
							date_from >= DATE('" . $filter_date_from . "') AND date_from < datetime('" . $filter_date_to . "', '+1 day')
							OR
							date_to >= DATE('" . $filter_date_from . "') AND date_to < datetime('" . $filter_date_to . "', '+1 day')
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

						-- its a timespan:
						-- check only date_to (add 1 day)
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

						-- or, its only a date_from
						-- check date from (add 1 day)
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
					OR CONCAT(DATE(date_from), ' 23:59:59') >= NOW()
					OR (
						date_to IS NOT NULL
						AND date_to != '0000-00-00 00:00:00'
						AND CONCAT(DATE(date_to), ' 23:59:59') >= NOW()
					)
					OR main_category_id != " . CATEGORY_EVENT . "
				)
			";
		}

		// Filter: search words (FTS5 when available, LIKE fallback otherwise)
		if (isset($filter['search']) && ($filter['search'] != '')) {
			$search_words = array_values(array_filter(explode(' ', $filter['search']), fn($word) => $word !== ''));
			if (! empty($search_words)) {
				$fts_match = $this->buildFtsMatchQuery($search_words);
				if ($fts_match !== '' && $this->api->db->isOfferFtsAvailable()) {

					// Numeric tokens also match offer_id directly (handy for support lookups)
					$id_matches = [];
					foreach ($search_words as $word) {
						if (ctype_digit($word)) {
							$id_matches[] = "main_offer.offer_id = " . intval($word);
						}
					}
					$fts_clause = "main_offer.offer_id IN (
						SELECT offer_fts.offer_id
						FROM offer_fts
						WHERE offer_fts MATCH '" . $this->api->db->escape($fts_match) . "'
							AND offer_fts.language = offer_i18n.language
					)";
					$where[] = ! empty($id_matches)
						? '(' . implode(' OR ', $id_matches) . ' OR ' . $fts_clause . ')'
						: $fts_clause;
				} else {

					// No FTS table: AND across words, OR across many i18n/keyword columns
					$search_query = "(";
					foreach ($search_words as $word) {
						$word = $this->api->db->escape($word);
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
				$time_category_minutes = [
					'< 2h' => '(activity.time_required_minutes <= 120)',
					'2 - 4h' => '((activity.time_required_minutes > 120) AND (activity.time_required_minutes <= 240))',
					'4 - 6h' => '((activity.time_required_minutes > 240) AND (activity.time_required_minutes <= 360))',
					'> 6h' => '(activity.time_required_minutes > 360)',
				];

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
		if (isset($filter['online_shop_enabled']) && ($filter['online_shop_enabled'] == true)) {
			$where_additional[] = "product.online_shop_enabled = 1";
		}
		if (isset($filter['barrier_free']) && ($filter['barrier_free'] == true)) {
			$where_additional[] = "main_offer.barrier_free = 1";
		}
		if (isset($filter['learning_opportunity']) && ($filter['learning_opportunity'] == true)) {
			$where_additional[] = "main_offer.learning_opportunity = 1";
		}
		if (isset($filter['child_friendly']) && ($filter['child_friendly'] == true)) {
			$where_additional[] = "main_offer.child_friendly = 1";
		}
		if (! empty($where_additional)) {
			$where[] = implode(' OR ', $where_additional);
		}

		// Filter: offers
		if (! empty($filter['offers'])) {
			if (! is_array($filter['offers'])) {
				$filter['offers'] = [$filter['offers']];
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
		if (! empty($having) && is_array($having) && ($mode === 'list')) {
			$having = " HAVING " . implode(" AND ", $having);
		} else {
			$having = "";
		}

		// Group and order by
		$group_by = $order_by = "";
		if ($mode === 'parks') {
			$group_by = " GROUP BY main_offer.park_id ";
			$order_by = " ORDER BY park ASC ";
		}
		else if ($mode === 'list') {

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
						date(MAX(offer_date.date_to)) ASC,
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

		if ($mode === 'filter_meta') {
			if ($q_offers && $q_offers->numRows > 0) {
				return $q_offers->fetchObject();
			}

			return false;
		}

		if ($mode === 'parks') {
			return $q_offers;
		}

		if ($q_offers->numRows > 0) {

			$offers = [
				'data' => [],
				'total' => 0
			];

			while ($offer = $q_offers->fetchObject()) {
				if (! empty($offer) && ($offer->offer_id > 0)) {
					$offers['data'][] = $offer;
				}
			}

			if (empty($offers['data'])) {
				return false;
			}

			// Paginated lists use a dedicated count (same grouping as the list query)
			if (! is_null($limit) && is_numeric($limit) && ($limit > 0)) {
				$count_select = "
					SELECT
						main_offer.offer_id,
						MIN(
							CASE
								WHEN c3.category_id IS NOT NULL THEN c3.category_id
								WHEN c2.category_id IS NOT NULL THEN c2.category_id
								WHEN c1.category_id IS NOT NULL THEN c1.category_id
								ELSE NULL
							END
						) AS main_category_id,
						MIN(offer_date.date_from) AS date_from,
						" . $icon_urls_select . "
					FROM offer main_offer
				";
				$q_count = $this->api->db->query(
					"SELECT COUNT(*) AS total FROM (" . $count_select . $join . $where . $group_by . $having . ") AS counted"
				);
				$offers['total'] = ($q_count && $q_count->numRows > 0) ? (int) $q_count->fetchObject()->total : count($offers['data']);
			} else {
				$offers['total'] = count($offers['data']);
			}

			$offers['data'] = $this->enrichOffersBatch($offers['data'], $return_minimal, $map_mode);

			return $offers;
		}

		return false;
	}



	/**
	 * Get offer with additional infos
	 */
	public function getOffer(int|object $offer, bool $return_minimal = false, bool $map_mode = false): object|false
	{

		// Load by id through the shared list path (already batch-enriched)
		if (is_numeric($offer) && ! is_object($offer)) {
			$result = $this->filterOffers(['offers' => $offer], 1, 0, $return_minimal, $map_mode);
			if (isset($result['data']) && is_array($result['data']) && ! empty($result['data'])) {
				return array_shift($result['data']);
			}

			return false;
		}

		if (is_object($offer) && ! empty($offer) && ($offer->offer_id > 0)) {
			$enriched = $this->enrichOffersBatch([$offer], $return_minimal, $map_mode);

			return $enriched[0] ?? false;
		}

		return false;
	}



	/**
	 * Batch-hydrate related offer data (replaces per-offer N+1 queries)
	 */
	private function enrichOffersBatch(array $offers, bool $return_minimal = false, bool $map_mode = false): array
	{

		if (empty($offers)) {
			return [];
		}

		$offer_ids = [];
		foreach ($offers as $offer) {
			$offer_id = (int) ($offer->offer_id ?? 0);
			if ($offer_id > 0) {
				$offer_ids[] = $offer_id;
			}
		}
		$offer_ids = array_values(array_unique($offer_ids));
		if (empty($offer_ids)) {
			return $offers;
		}

		$ids_sql = implode(',', $offer_ids);

		// Categories (always needed, including map mode)
		$categories_by_offer = [];
		$q_categories = $this->api->db->query("
			SELECT offer_id, category_id
			FROM category_link
			WHERE offer_id IN (" . $ids_sql . ")
		");
		if ($q_categories && $q_categories->numRows > 0) {
			while ($row = $q_categories->fetchObject()) {
				$categories_by_offer[(int) $row->offer_id][] = (int) $row->category_id;
			}
		}

		// Map overview only needs offer_id + categories for the map config
		if ($map_mode == true) {
			foreach ($offers as $offer) {
				$offer->root_category = null;
				$offer->categories = [];
				foreach ($categories_by_offer[(int) $offer->offer_id] ?? [] as $category_id) {
					$category = $this->getCategory($category_id);
					if ($category !== false) {
						$offer->categories[$category_id] = $category;
					}
					if (! $offer->root_category) {
						$offer->root_category = $this->getRootCategory($category_id);
					}
				}
				if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
					$offer->project_status = $offer->status ?? null;
				}
			}

			return $offers;
		}

		// Target groups
		$target_group_ids_by_offer = [];
		$q_target_groups = $this->api->db->query("
			SELECT offer_id, target_group_id
			FROM target_group_link
			WHERE offer_id IN (" . $ids_sql . ")
		");
		if ($q_target_groups && $q_target_groups->numRows > 0) {
			while ($row = $q_target_groups->fetchObject()) {
				if (! empty($row->target_group_id) && array_key_exists($row->target_group_id, $this->targetGroups)) {
					$target_group_ids_by_offer[(int) $row->offer_id][] = (int) $row->target_group_id;
				}
			}
		}

		// Dates
		$dates_by_offer = [];
		$date_format = $this->api->config['db_date_format'];
		$q_dates = $this->api->db->query("
			SELECT
				offer_id,
				DATE_FORMAT(date_from, '" . $date_format . "') AS date_from,
				DATE_FORMAT(date_to, '" . $date_format . "') AS date_to
			FROM offer_date
			WHERE offer_id IN (" . $ids_sql . ")
			ORDER BY DATE_FORMAT(date_from, '%Y-%m-%d %H:%i')
		");
		if ($q_dates && $q_dates->numRows > 0) {
			while ($row = $q_dates->fetchObject()) {
				$dates_by_offer[(int) $row->offer_id][] = $row;
			}
		}

		// Images
		$images_by_offer = [];
		$q_images = $this->api->db->query("
			SELECT *
			FROM image
			WHERE offer_id IN (" . $ids_sql . ")
		");
		if ($q_images && $q_images->numRows > 0) {
			while ($row = $q_images->fetchObject()) {
				$images_by_offer[(int) $row->offer_id][] = $row;
			}
		}

		// Attach batch-loaded relations; keep target groups in catalogue sort order
		foreach ($offers as $offer) {
			$offer_id = (int) $offer->offer_id;

			$offer->root_category = null;
			$offer->categories = [];
			foreach ($categories_by_offer[$offer_id] ?? [] as $category_id) {
				$category = $this->getCategory($category_id);
				if ($category !== false) {
					$offer->categories[$category_id] = $category;
				}
				if (! $offer->root_category) {
					$offer->root_category = $this->getRootCategory($category_id);
				}
			}

			$offer->targetGroups = [];
			$target_group_links = $target_group_ids_by_offer[$offer_id] ?? [];
			if (! empty($this->targetGroups) && ! empty($target_group_links)) {
				foreach ($this->targetGroups as $target_group_id => $target_group) {
					if (in_array($target_group_id, $target_group_links)) {
						$offer->targetGroups[$target_group_id] = $target_group;
					}
				}
			}

			$offer->dates = $dates_by_offer[$offer_id] ?? [];
			$offer->images = $images_by_offer[$offer_id] ?? [];

			// Status is only meaningful for project/research root categories
			if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
				$offer->project_status = $offer->status ?? null;
			}

			if ($return_minimal == false) {
				$this->enrichOfferExtended($offer);
			}
		}

		return $offers;
	}



	/**
	 * Load detail-only relations for a single offer (documents, links, shop, poi, …)
	 */
	private function enrichOfferExtended(object $offer): void
	{

		// Documents
		$q_documents = $this->api->db->get('document', ['offer_id' => $offer->offer_id, 'language' => $this->api->lang->langId]);
		if ($q_documents->numRows > 0) {
			$offer->documents = [];
			while ($row = $q_documents->fetchObject()) {
				$offer->documents[] = $row;
			}
		}

		// Documents intern
		$q_documents = $this->api->db->get('document_intern', ['offer_id' => $offer->offer_id, 'language' => $this->api->lang->langId]);
		if ($q_documents->numRows > 0) {
			$offer->documents_intern = [];
			while ($row = $q_documents->fetchObject()) {
				$offer->documents_intern[] = $row;
			}
		}

		// Hyperlinks
		$q_hyperlinks = $this->api->db->get('hyperlink', ['offer_id' => $offer->offer_id, 'language' => $this->api->lang->langId]);
		if ($q_hyperlinks->numRows > 0) {
			$offer->hyperlinks = [];
			while ($row = $q_hyperlinks->fetchObject()) {
				$offer->hyperlinks[] = $row;
			}
		}

		// Hyperlinks intern
		$q_hyperlinks = $this->api->db->get('hyperlink_intern', ['offer_id' => $offer->offer_id, 'language' => $this->api->lang->langId]);
		if ($q_hyperlinks->numRows > 0) {
			$offer->hyperlinks_intern = [];
			while ($row = $q_hyperlinks->fetchObject()) {
				$offer->hyperlinks_intern[] = $row;
			}
		}

		// Accessibilities
		$q_accessibilities = $this->api->db->query("
			SELECT *
			FROM accessibility
			WHERE accessibility.offer_id = " . $offer->offer_id . "
			LIMIT 1
		");
		if ($q_accessibilities->numRows > 0) {
			$offer->accessibilities = $q_accessibilities->fetchObject();
			$q_ratings = $this->api->db->query("
				SELECT *
				FROM accessibility_rating
				WHERE accessibility_rating.accessibility_id = " . $offer->accessibilities->accessibility_id . "
			");
			if ($q_ratings->numRows > 0) {
				while ($rating = $q_ratings->fetchObject()) {
					$offer->accessibilities->ratings[] = $rating;
				}
			}
		}

		// Product
		if ($offer->root_category == CATEGORY_PRODUCT) {

			$q_suppliers = $this->api->db->get('supplier', ['offer_id' => $offer->offer_id]);
			if ($q_suppliers->numRows > 0) {
				$offer->suppliers = [];
				while ($row = $q_suppliers->fetchObject()) {
					$offer->suppliers[] = ['contact' => $row->contact, 'is_park_partner' => $row->is_park_partner];
				}
			}

			if ($offer->online_shop_enabled == true) {
				$language_priority = $this->api->config['language_priority'][$this->api->lang->langId];
				if (! empty($language_priority) && is_array($language_priority)) {

					// Nested CASE picks the best available article language for the current UI lang
					$q_articles = $this->api->db->query("

						SELECT *
						FROM product_article AS main_article
						INNER JOIN product_article_i18n main_article_i18n ON main_article_i18n.product_article_id = main_article.product_article_id
						WHERE 
							main_article.offer_id = " . $offer->offer_id . "
							AND main_article_i18n.language = (
												
								SELECT CASE WHEN sub_article_i18n.language IS NOT NULL THEN sub_article_i18n.language ELSE (
									SELECT CASE WHEN sub_article_i18n.language IS NOT NULL THEN sub_article_i18n.language ELSE (
										SELECT
											CASE WHEN sub_article_i18n.language IS NOT NULL THEN sub_article_i18n.language ELSE ((
												SELECT CASE WHEN sub_article_i18n.language IS NOT NULL THEN sub_article_i18n.language ELSE 'de' END
												FROM product_article AS sub_article
												LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[2] . "'
												WHERE main_article.product_article_id = sub_article.product_article_id)
											) END
										FROM product_article AS sub_article
										LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[1] . "'
										WHERE main_article.product_article_id = sub_article.product_article_id
									) END
									FROM product_article AS sub_article
									LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $language_priority[0] . "'
									WHERE main_article.product_article_id = sub_article.product_article_id
								) END
								FROM product_article AS sub_article
								LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $this->api->lang->langId . "'
								WHERE main_article.product_article_id = sub_article.product_article_id
								
							)

					");

					if ($q_articles->numRows > 0) {
						$offer->articles = [];
						while ($article = $q_articles->fetchObject()) {
							$q_article_labels = $this->api->db->query("
								SELECT *
								FROM product_article_label
								WHERE 
									product_article_id = " . $article->product_article_id . "
									AND language = '" . $this->api->lang->langId . "'
							");
							if ($q_article_labels->numRows > 0) {
								$article->labels = [];
								while ($article_label = $q_article_labels->fetchObject()) {
									$article->labels[] = $article_label;
								}
							}
							$offer->articles[] = $article;
						}
					}
				}
			}
		}

		// Booking
		else if ($offer->root_category == CATEGORY_BOOKING) {
			$q_accommodations = $this->api->db->get('accommodation', ['offer_id' => $offer->offer_id]);
			if ($q_accommodations->numRows > 0) {
				$offer->accommodations = [];
				while ($row = $q_accommodations->fetchObject()) {
					$offer->accommodations[] = ['contact' => $row->contact, 'is_park_partner' => $row->is_park_partner];
				}
			}
		}

		// POI CSV may reference deleted offers; keep only IDs that still exist
		if (! empty($offer->poi) && ($offer->poi != '') && ! is_array($offer->poi)) {
			$poi = explode(',', $offer->poi);
			$existing_poi = [];
			if (! empty($poi) && is_array($poi)) {
				$poi_ids = array_values(array_filter(array_map('intval', $poi)));
				if (! empty($poi_ids)) {
					$q_poi = $this->api->db->query("
						SELECT offer_id
						FROM offer
						WHERE offer_id IN (" . implode(',', $poi_ids) . ")
					");
					if ($q_poi && $q_poi->numRows > 0) {
						while ($row = $q_poi->fetchObject()) {
							$existing_poi[] = (string) $row->offer_id;
						}
					}
				}
			}
			$offer->poi = $existing_poi;
		}

		// Activities that list this offer in their poi CSV are reverse-linked routes
		$linked_routes = [];
		$q_linked_routes = $this->api->db->query("
			SELECT offer_id
			FROM activity
			WHERE 
				poi LIKE '" . $offer->offer_id . ",%'
				OR poi LIKE '%," . $offer->offer_id . ",%'
				OR poi LIKE '%," . $offer->offer_id . "'
				OR poi = '" . $offer->offer_id . "'
		");
		if ($q_linked_routes->numRows > 0) {
			$candidate_ids = [];
			while ($linked_route = $q_linked_routes->fetchAssoc()) {
				if (! empty($linked_route['offer_id'])) {
					$candidate_ids[] = (int) $linked_route['offer_id'];
				}
			}
			$candidate_ids = array_values(array_unique($candidate_ids));

			// Drop candidates whose offer row was removed after the activity link was stored
			if (! empty($candidate_ids)) {
				$q_poi = $this->api->db->query("
					SELECT offer_id
					FROM offer
					WHERE offer_id IN (" . implode(',', $candidate_ids) . ")
				");
				if ($q_poi && $q_poi->numRows > 0) {
					while ($row = $q_poi->fetchObject()) {
						$linked_routes[] = (string) $row->offer_id;
					}
				}
			}
		}
		$offer->linked_routes = $linked_routes;
	}



	/**
	 * Get category by ID
	 */
	public function getCategory(int $category_id): object|false
	{

		if (array_key_exists($category_id, $this->categories)) {
			return $this->categories[$category_id];
		}
		
		return false;
	}



	/**
	 * Get path for a category
	 */
	public function getCategoryPath(int $category_id): array
	{

		if (! empty($category_id)) {
			$path = [];

			while ($category_id > 0) {
				$category = $this->getCategoryRecord($category_id);
				if ($category === false) {
					break;
				}

				$category_id = (int) $category->parent_id;
				$path[] = (int) $category->category_id;
			}

			return $path;
		}

		return [];
	}



	/**
	 * Get all categories
	 */
	public function getAllCategories(array $filter = []): array
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
			INNER JOIN category_i18n ON category_i18n.category_id = c1.category_id AND category_i18n.language = '" . $this->api->lang->langId . "'
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

			$q_additional = $this->buildAdditionalInfosSql($filter);
			if (! empty($q_additional)) {

				// Keep root categories visible even when no child matches the filter
				$q_categories_string .= "AND (1 " . $q_additional . ") OR (c1.parent_id = 0)";
			}
		}

		$q_categories_string .= "GROUP BY c1.category_id ";
		$q_categories_string .= "ORDER BY c1.sort ";

		$q_categories = $this->api->db->query($q_categories_string);

		if ($q_categories->numRows > 0) {
			$categories = [];

			while ($row = $q_categories->fetchObject()) {
				$categories[$row->category_id] = (object) [
					'category_id' => $row->category_id,
					'parent_id' => $row->parent_id,
					'language' => $this->api->lang->langId,
					'body' => $row->body,
					'marker' => $row->marker,
					'parents' => explode(",", $row->parents),
					'sort' => $row->sort
				];
			}

			return $categories;
		}

		return [];
	}



	/**
	 * Get all category links
	 */
	public function getAllCategoryLinks(): array|false
	{

		$q_category_links = $this->api->db->query("SELECT category_id FROM category_link GROUP BY category_link.category_id");

		if ($q_category_links->numRows > 0) {
			
			// Add main categories
			$category_links = [
				CATEGORY_EVENT,
				CATEGORY_PRODUCT,
				CATEGORY_BOOKING,
				CATEGORY_ACTIVITY,
				CATEGORY_PROJECT,
				CATEGORY_RESEARCH
			];

			while ($row = $q_category_links->fetchObject()) {
				$category_links[] = $row->category_id;
			}

			return $category_links;
		}

		return false;
	}



	/**
	 * Get all users/parks
	 */
	public function getAllUsers(array $categories = [], array $filter = []): array|false
	{

		if (empty($categories)) {
			$categories = $this->getAllCategoryLinks();
		}

		if (! isset($filter['categories']) && is_array($categories) && ! empty($categories)) {
			$filter['categories'] = $categories;
		}

		$q_users = $this->getFilterParks($filter);

		if ($q_users && ($q_users->numRows > 0)) {
			$users = [];

			while ($row = $q_users->fetchObject()) {
				$users[$row->park_id] = $row->park;
			}

			return $users;
		}

		return false;
	}



	/**
	 * Get full category tree
	 */
	public function getCategoryTree(): array
	{

		$categories = [];
		$q_categories = $this->api->db->query("

			SELECT
				category.category_id,
				CASE WHEN category.parent_id = 0 THEN category_i18n.body
					ELSE CASE WHEN c2.parent_id = 0 THEN CONCAT('--- ', category_i18n.body)
						ELSE CASE WHEN c3.parent_id = 0 THEN CONCAT('------ ', category_i18n.body) ELSE CONCAT('--------- ', category_i18n.body) END
					END
				END AS body,
				category.sort,
				category.alpstein_id,
				category.marker

				FROM category
				LEFT JOIN category AS c2 ON category.parent_id = c2.category_id
				LEFT JOIN category AS c3 ON c2.parent_id = c3.category_id
				INNER JOIN category_i18n ON category_i18n.category_id = category.category_id

				WHERE category_i18n.language = '" . $this->api->lang->langId . "'
				ORDER BY category.sort

		");

		if ($q_categories->numRows > 0) {
			while ($category = $q_categories->fetchObject()) {
				$categories[$category->category_id] = $category->body;
			}
		}

		return $categories;
	}



	/**
	 * Sync target groups
	 */
	public function syncTargetGroups(array $targetGroups): bool
	{

		if (! empty($targetGroups) && is_array($targetGroups)) {

			// Delete existing target groups
			$this->api->db->delete('target_group');
			$this->api->db->delete('target_group_i18n');

			// Insert target groups
			foreach ($targetGroups as $lang => $items) {
				$sort = 0;
				foreach ($items as $target_group_id => $target_group) {
					$sort++;

					// Table target_group – only once
					if ($lang == 'de') {
						$this->api->db->insert('target_group', [
							'target_group_id' => $target_group_id,
							'sort' => $sort,
						]);
					}

					// Table target_group_i18n
					$this->api->db->insert('target_group_i18n', [
						'target_group_id' => $target_group_id,
						'language' => $lang,
						'body' => $target_group,
					]);
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Sync categories
	 */
	public function syncCategories(array $categories): bool
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
						$this->api->db->insert('category', [
							'category_id' => $category_id,
							'parent_id' => $category['parent_id'],
							'stnet_id' => $category['stnet_id'],
							'alpstein_id' => $category['alpstein_id'],
							'contact_visible_for_alpstein' => $category['contact_visible_for_alpstein'],
							'marker' => $category['marker'],
							'sort' => $category['sort'],
						]);
					}

					// Table category_i18n
					$this->api->db->insert('category_i18n', [
						'category_id' => $category_id,
						'language' => $lang,
						'body' => $category['body'],
					]);
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Sync fields of activity
	 */
	public function syncFieldsOfActivity(array $fieldsOfActivity): bool
	{

		if (! empty($fieldsOfActivity) && is_array($fieldsOfActivity)) {

			// Delete existing fields of activity
			$this->api->db->delete('field_of_activity');
			$this->api->db->delete('field_of_activity_i18n');

			// Insert fields of activity
			foreach ($fieldsOfActivity as $lang => $items) {
				$sort = 0;
				foreach ($items as $field_of_activity_id => $field_of_activity) {
					$sort++;

					// Table field_of_activity – only once
					if ($lang == 'de') {
						$this->api->db->insert('field_of_activity', [
							'field_of_activity_id' => $field_of_activity_id,
							'sort' => $sort,
						]);
					}

					// Table field_of_activity_i18n
					$this->api->db->insert('field_of_activity_i18n', [
						'field_of_activity_id' => $field_of_activity_id,
						'language' => $lang,
						'body' => $field_of_activity,
					]);
				}
			}

			return true;
		}

		return false;
	}



	/**
	 * Get municipalities
	 */
	public function getMunicipalities(array $filter = []): array
	{

		// Get the municipalities
		$q_municipalities = $this->api->db->get('municipality', $filter);

		// Return empty array if there are no municipalities
		if ($q_municipalities->numRows <= 0) {
			return [];
		}
		
		// Format municipalities
		$municipalities = [];
		while ($row = $q_municipalities->fetchArray()) {
			$municipalities[$row['municipality_id']] = $row['municipality'];
		}

		// Sort municipalities by name
		asort($municipalities);

		// Return municipalities
		return $municipalities;
	}



	/**
	 * Private method for retrieving the root category
	 */
	private function getRootCategory(int $category_id): int|false
	{

		if (empty($category_id)) {
			return false;
		}

		$last_category_id = false;

		while ($category_id > 0) {
			$category = $this->getCategoryRecord($category_id);
			if ($category === false) {
				break;
			}

			$category_id = (int) $category->parent_id;
			$last_category_id = (int) $category->category_id;
		}

		return $last_category_id;
	}



	/**
	 * Get category record from cache or database
	 */
	private function getCategoryRecord(int $category_id): object|false
	{

		if (array_key_exists($category_id, $this->categories)) {
			return $this->categories[$category_id];
		}

		$q_category = $this->api->db->get('category', ['category_id' => $category_id]);
		if ($q_category->numRows <= 0) {
			return false;
		}

		$category = $q_category->fetchObject();
		$this->categories[$category_id] = $category;

		return $category;
	}



	/**
	 * Build FTS5 MATCH expression (AND + prefix) from search words
	 */
	private function buildFtsMatchQuery(array $words): string
	{

		$parts = [];
		foreach ($words as $word) {
			$word = trim((string) $word);
			if ($word === '') {
				continue;
			}

			// Strip FTS operators/punctuation so user input cannot break MATCH syntax
			$word = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $word) ?? '';
			$word = trim($word);
			if ($word === '') {
				continue;
			}

			// Quoted token + trailing * = phrase prefix match; words combined with AND
			$parts[] = '"' . str_replace('"', '', $word) . '"*';
		}

		return implode(' AND ', $parts);
	}



	/**
	 * Format date/time value for SQL filters
	 */
	private function formatDatetime(mixed $value, string $format = 'Y-m-d H:i:s'): string
	{

		$datetime = new DateTime($value);
		return $datetime->format($format);
	}


	/**
	 * Build additional infos SQL fragments from filter flags
	 */
	private function buildAdditionalInfosSql(array $filter): string
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

							// Handled elsewhere or not expressible as a simple offer column
							case 'search':
							case 'order_by':
							case 'has_accessibility_informations':
							case 'online_shop_enabled':
								break;

							case 'is_park_event':
								$additional_query .= " AND event.is_park_event = '" . $value . "'";
								break;

							default:

								// Remaining keys map 1:1 to offer columns (callers must pass known fields only)
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
	 */
	public function getAccessibilityList(): array
	{

		// Init
		$accessibilites = [];
		$filter_options = [];

		// Get all possible dropdown options
		$options = $this->api->db->query("
			SELECT *
			FROM accessibility_dropdown
		");
		if ($options->numRows > 0) {
			while ($option = $options->fetchArray()) {
				$filter_options[] = $option['icon_url'];
			}
		}

		// Get accessiblity options
		$select_result = $this->api->db->query("
			SELECT 
				icon_url, 
				MIN(description_" . $this->api->lang->langId . ") AS description
			FROM accessibility_rating
			GROUP BY icon_url
			ORDER BY description
		");

		// Populate accessibilities
		if ($select_result->numRows > 0) {
			$accessibilites[GINTO_INFOS_AVAILABLE] = $this->api->lang->get('offer_accessibility_available');
			while ($row = $select_result->fetchArray()) {
				if (in_array($row['icon_url'], $filter_options)) {
					$accessibilites[$row['icon_url']] = $row['description'];
				}
			}
		}

		return $accessibilites;
	}



	/**
	 * Sync accessibility dropdown list
	 */
	public function syncAccessibilities(array $options): bool
	{

		if (! empty($options) && is_array($options)) {

			// Delete existing list
			$this->api->db->delete('accessibility_dropdown');

			// Iterate each option
			foreach ($options as $option) {

				// Insert option
				$this->api->db->insert('accessibility_dropdown', [
					'icon_url' => $option,
				]);
			}

			return true;
		}

		return false;
	}


}
