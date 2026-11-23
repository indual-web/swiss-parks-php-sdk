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
	 * SDK instance
	 */
	public ParksSDK $sdk;


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
	public function __construct(ParksSDK $sdk)
	{

		$this->sdk = $sdk;

		// Set levels
		$this->levels = [
			0 => '',
			1 => $this->sdk->lang->get('offer_easy'),
			2 => $this->sdk->lang->get('offer_average'),
			3 => $this->sdk->lang->get('offer_difficult'),
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

		$qFields = $this->sdk->db->get(
			'field_of_activity',
			['language' => $this->sdk->lang->langId],
			['field_of_activity_i18n' => 'field_of_activity.field_of_activity_id = field_of_activity_i18n.field_of_activity_id'],
			null,
			null,
			null,
			null,
			'field_of_activity.sort'
		);

		if ($qFields && $qFields->numRows > 0) {
			while ($row = $qFields->fetchObject()) {
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

		$qTargetGroup = $this->sdk->db->get('target_group', ['language' => $this->sdk->lang->langId], ['target_group_i18n' => 'target_group.target_group_id = target_group_i18n.target_group_id'], null, null, null, null, 'target_group.sort');
		if ($qTargetGroup->numRows > 0) {
			while ($row = $qTargetGroup->fetchObject()) {
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

		$qCategory = $this->sdk->db->get('category', ['language' => $this->sdk->lang->langId], ['category_i18n' => 'category.category_id = category_i18n.category_id']);
		if ($qCategory->numRows > 0) {
			while ($row = $qCategory->fetchObject()) {
				$this->categories[$row->category_id] = $row;
			}
		}

	}



	/**
	 * Checks if an offer with the given id exists
	 */
	public function offerExists(int $offerId): bool
	{

		$qOffer = $this->sdk->db->query("SELECT offer_id FROM offer WHERE offer_id = " . $offerId);
		if ($qOffer->numRows > 0) {
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

		$qLayers = $this->sdk->db->query("
			SELECT * 
			FROM map_layer 
			WHERE languages LIKE '%" . $this->sdk->lang->langId . "%'
		");

		if ($qLayers->numRows > 0) {
			$layers = [];

			while ($row = $qLayers->fetchObject()) {

				if (empty($row->map_layer_id)) {
					continue;
				}

				// Get i18n data
				$qLayersI18n = $this->sdk->db->query("
					SELECT * 
					FROM map_layer_i18n 
					WHERE map_layer_id = " . $row->map_layer_id . "
				");

				if ($qLayersI18n->numRows > 0) {

					$layerI18n = [];

					// Parse content for each language
					while ($rowI18n = $qLayersI18n->fetchObject()) {
						if (! empty($rowI18n->language)) {

							// Service URL
							if (! empty($rowI18n->url)) {
								$layerI18n[$rowI18n->language]['url'] = $rowI18n->url;
							}

							// Popup title
							if (! empty($rowI18n->popup_title)) {
								$layerI18n[$rowI18n->language]['popup_title'] = $rowI18n->popup_title;
							}

							// Popup content
							if (! empty($rowI18n->popup_content)) {
								$layerI18n[$rowI18n->language]['popup_content'] = $rowI18n->popup_content;
							}

							// Layer title
							if (! empty($rowI18n->layer_title)) {
								$layerI18n[$rowI18n->language]['layer_title'] = $rowI18n->layer_title;
							}

						}
					}

					// Add i18n data to row
					$row->i18n = $layerI18n;

				}

				// Resolve service URL for the current language (fallback de → fr → it → en)
				$urls = [];
				foreach (($row->i18n ?? []) as $language => $fields) {
					if (! empty($fields['url'])) {
						$urls[$language] = $fields['url'];
					}
				}
				$row->url = ParksMap::resolveI18nUrl($urls, $this->sdk->lang->langId, $row->languages ?? '');

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
		bool $returnMinimal = false,
		bool $mapMode = false,
		bool $ignoreHintOrder = false,
		bool $orderByRand = false
	): array|false {

		$result = $this->queryOffers($filter, $limit, $offset, $returnMinimal, $mapMode, $ignoreHintOrder, $orderByRand, 'list');

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
		foreach ($meta['category_ids'] as $categoryId) {
			$rows[] = ['category_id' => $categoryId];
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
		$cacheKey = md5(serialize($filter));
		if ($this->filterMetaCacheKey === $cacheKey) {
			return $this->filterMetaCache;
		}

		$result = $this->queryOffers($filter, null, null, false, false, false, false, 'filter_meta');
		if (! ($result instanceof stdClass)) {
			$this->filterMetaCacheKey = $cacheKey;
			$this->filterMetaCache = false;

			return false;
		}

		// category_ids arrive as a comma-separated GROUP_CONCAT string
		$categoryIds = [];
		if (! empty($result->category_ids)) {
			foreach (explode(',', (string) $result->category_ids) as $categoryId) {
				$categoryId = (int) $categoryId;
				if ($categoryId > 0) {
					$categoryIds[$categoryId] = $categoryId;
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
			'category_ids' => $categoryIds,
			'parks' => $parks,
		];

		$this->filterMetaCacheKey = $cacheKey;
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
		bool $returnMinimal,
		bool $mapMode,
		bool $ignoreHintOrder,
		bool $orderByRand,
		string $mode
	): ParksSQLiteResult|stdClass|array|false {

		// Populate date filter
		$filterDateFrom = ! empty($filter['date_from']) ? $this->formatDatetime($filter['date_from']) : '';
		$filterDateTo = ! empty($filter['date_to']) ? $this->formatDatetime($filter['date_to']) : '';

		// Unparsable dates are ignored instead of limiting the list to events
		if ($filterDateFrom === '') {
			unset($filter['date_from']);
		}
		if ($filterDateTo === '') {
			unset($filter['date_to']);
		}

		// Accessibility joins only when filtering by accessibility
		$needsAccessibilityJoin = (
			(isset($filter['has_accessibility_informations']) && ($filter['has_accessibility_informations'] == true))
			|| ! empty($filter['accessibilities'])
		);
		$needsAccessibilityRatingJoin = false;
		if (! empty($filter['accessibilities'])) {
			foreach ($filter['accessibilities'] as $accessibilityId) {
				if ($accessibilityId != GINTO_INFOS_AVAILABLE) {
					$needsAccessibilityRatingJoin = true;
					break;
				}
			}
		}

		// Subscription columns only for full detail rows
		$needsSubscriptionJoin = (($mode === 'list') && ($returnMinimal == false) && ($mapMode == false));
		$subscriptionSelect = $needsSubscriptionJoin ? "subscription.*," : "";
		$iconUrlsSelect = $needsAccessibilityRatingJoin
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
				" . $subscriptionSelect . "
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
						AND MIN(offer_date.date_from) < " . (! empty($filterDateFrom) ? "'" . $filterDateFrom . "'" : "NOW()") . "
						AND MIN(offer_date.date_from) IS NOT NULL
						THEN " . (! empty($filterDateFrom) ? "'" . $filterDateFrom . "'" : "CURDATE()") . "
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

				" . $iconUrlsSelect . "

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

		if ($needsSubscriptionJoin) {
			$join .= "
				LEFT OUTER JOIN subscription ON subscription.offer_id = main_offer.offer_id
				LEFT OUTER JOIN subscription_i18n ON subscription_i18n.offer_id = main_offer.offer_id AND subscription_i18n.language = '" . $this->sdk->lang->langId . "'
			";
		}

		if ($needsAccessibilityJoin) {
			$join .= "
				LEFT OUTER JOIN accessibility ON accessibility.offer_id = main_offer.offer_id
			";
		}
		if ($needsAccessibilityRatingJoin) {
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
			$categoryConditions = [];
			foreach ($categories as $categoryId) {
				$categoryId = intval($categoryId);
				$categoryConditions[] = "
					(
						c1.category_id = " . $categoryId . "
						OR c2.category_id = " . $categoryId . "
						OR c3.category_id = " . $categoryId . "
					)
				";
			}
			$where[] = '(' . implode(' OR ', $categoryConditions) . ')';
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
			foreach ($filter['accessibilities'] as $accessibilityId) {
				if ($accessibilityId == GINTO_INFOS_AVAILABLE) {
					$where[] = "accessibility.accessibility_id IS NOT NULL";
				} else {
					$having[] = "icon_urls LIKE '%" . $this->sdk->db->escape($accessibilityId) . "%'";
				}
			}
		}

		// Select language and show every entry, no matter which language is available
		if (empty($filter['force_language']) && isset($this->sdk->config['language_independence']) && ($this->sdk->config['language_independence'] == true) && isset($this->sdk->config['language_priority'])) {

			// Get language priorities
			$languagePriority = $this->sdk->config['language_priority'][$this->sdk->lang->langId];
			if (! empty($languagePriority) && is_array($languagePriority)) {

				$where[] = "
					offer_i18n.language = (
						SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE (
								SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE (
										SELECT
											CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE ((
												SELECT CASE WHEN sub_offer_i18n.language IS NOT NULL THEN sub_offer_i18n.language ELSE 'de' END
												FROM offer sub_offer
												LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $languagePriority[2] . "'
												WHERE main_offer.offer_id = sub_offer.offer_id)
											) END
										FROM offer sub_offer
										LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $languagePriority[1] . "'
										WHERE main_offer.offer_id = sub_offer.offer_id
									) END
								FROM offer sub_offer
								LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $languagePriority[0] . "'
								WHERE main_offer.offer_id = sub_offer.offer_id
							) END
						FROM offer sub_offer
						LEFT JOIN offer_i18n sub_offer_i18n ON sub_offer_i18n.offer_id = sub_offer.offer_id AND sub_offer_i18n.language = '" . $this->sdk->lang->langId . "'
						WHERE main_offer.offer_id = sub_offer.offer_id
					)
				";
			}
		}

		// Select only offers which are available in the current language
		else {
			$where[] = "offer_i18n.language = '" . $this->sdk->db->escape($filter['force_language'] ?: $this->sdk->lang->langId) . "'";
		}

		// Filter: offer settings
		if (! empty($filter['offer_settings'])) {
			foreach ($filter['offer_settings'] as $key => $value) {

				// Keys are column names and cannot be escaped, so allow plain identifiers only
				if (preg_match('/^\w+$/', (string) $key)) {
					$where[] = "main_offer." . $key . " = '" . $this->sdk->db->escape($value) . "'";
				}
			}
		}

		// Filter: target groups
		if (! empty($filter['target_groups']) || ! empty($this->sdk->systemFilter['target_groups'])) {
			$join .= "
				LEFT JOIN target_group_link ON target_group_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . $this->intList($filter['target_groups']) . "))";
			}
			if (! empty($this->sdk->systemFilter['target_groups'])) {
				$where[] = "(target_group_link.target_group_id IN (" . $this->intList($this->sdk->systemFilter['target_groups']) . "))";
			}
		}

		// Filter: fields of activity
		if (! empty($filter['fields_of_activity']) || ! empty($this->sdk->systemFilter['fields_of_activity'])) {
			$join .= "
				LEFT JOIN field_of_activity_link ON field_of_activity_link.offer_id = main_offer.offer_id
			";
			if (! empty($filter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . $this->intList($filter['fields_of_activity']) . "))";
			}
			if (! empty($this->sdk->systemFilter['fields_of_activity'])) {
				$where[] = "(field_of_activity_link.field_of_activity_id IN (" . $this->intList($this->sdk->systemFilter['fields_of_activity']) . "))";
			}
		}

		// Filter: municipalities
		if (! empty($filter['municipalities'])) {
			$join .= "
				LEFT OUTER JOIN offer_municipality_link ON offer_municipality_link.offer_id = main_offer.offer_id
			";
			if (is_array($filter['municipalities'])) {
				$where[] = "(offer_municipality_link.municipality_id IN (" . $this->intList($filter['municipalities']) . "))";
			}
			else {
				$where[] = "offer_municipality_link.municipality_id = " . intval($filter['municipalities']);
			}
		}

		// Filter: park or user
		if (! empty($filter['park_id'])) {
			if (! is_array($filter['park_id'])) {
				$where[] = "main_offer.park_id = " . intval($filter['park_id']);
			} else {
				$where[] = "main_offer.park_id IN (" . $this->intList($filter['park_id']) . ")";
			}
		}
		if (! empty($filter['user'])) {
			$where[] = "main_offer.park = '" . $this->sdk->db->escape($filter['user']) . "'";
		}

		// Filter: exlcude parks
		if (! empty($filter['exclude_park_ids']) && is_array($filter['exclude_park_ids'])) {
			$where[] = "main_offer.park_id NOT IN (" . $this->intList($filter['exclude_park_ids']) . ")";
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
			if (! empty($filterDateFrom) && ! empty($filterDateTo)) {

				// First, check if this offer date is between the filter dates
				// Second, check if this date is in the filter time span
				$where[] = "
					(
						(
							'" . $filterDateFrom . "' BETWEEN date_from AND date_to
							OR
							'" . $filterDateTo . "' BETWEEN date_from AND date_to
						)

						OR

						(
							date_from >= DATE('" . $filterDateFrom . "') AND date_from < datetime('" . $filterDateTo . "', '+1 day')
							OR
							date_to >= DATE('" . $filterDateFrom . "') AND date_to < datetime('" . $filterDateTo . "', '+1 day')
						)
					)
				";
			}

			// Check only date from
			else if (! empty($filterDateFrom)) {
				$where[] = " ( offer_date.date_from >= '" . $filterDateFrom . "' OR offer_date.date_to >= '" . $filterDateFrom . "' ) ";
			}

			// Check only date to
			else if (! empty($filterDateTo)) {
				$where[] = "
					(

						-- its a timespan:
						-- check only date_to (add 1 day)
						(
							date_to IS NOT NULL
							AND
							date_to != '0000-00-00 00:00:00'
							AND
							date_to < '" . $filterDateTo . " 23:59:59'
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
							date_from < '" . $filterDateTo . " 00:00:00'
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
			$searchWords = array_values(array_filter(explode(' ', $filter['search']), fn($word) => $word !== ''));
			if (! empty($searchWords)) {
				$ftsMatch = $this->buildFtsMatchQuery($searchWords);
				if ($ftsMatch !== '' && $this->sdk->db->isOfferFtsAvailable()) {

					// Numeric tokens also match offer_id directly (handy for support lookups)
					$idMatches = [];
					foreach ($searchWords as $word) {
						if (ctype_digit($word)) {
							$idMatches[] = "main_offer.offer_id = " . intval($word);
						}
					}
					$ftsClause = "main_offer.offer_id IN (
						SELECT offer_fts.offer_id
						FROM offer_fts
						WHERE offer_fts MATCH '" . $this->sdk->db->escape($ftsMatch) . "'
							AND offer_fts.language = offer_i18n.language
					)";
					$where[] = ! empty($idMatches)
						? '(' . implode(' OR ', $idMatches) . ' OR ' . $ftsClause . ')'
						: $ftsClause;
				} else {

					// No FTS table: AND across words, OR across many i18n/keyword columns
					$searchQuery = "(";
					foreach ($searchWords as $word) {
						$word = $this->sdk->db->escape($word);
						$searchQuery .= "(
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
					$searchQuery = substr($searchQuery, 0, -4) . ")";
					$where[] = $searchQuery;
				}
			}
		}

		// Filter: keywords
		if (isset($filter['keywords']) && ($filter['keywords'] != '')) {
			$keywords = explode(' ', $filter['keywords']);
			if (! empty($keywords)) {
				$restriction = 'OR';
				if ($this->sdk->config['filter_keywords_with_and'] == true) {
					$restriction = 'AND';
				}
				$keywordsQuery = "(";
				foreach ($keywords as $word) {
					$keywordsQuery .= " main_offer.keywords LIKE '%" . $this->sdk->db->escape($word) . "%' " . $restriction . " ";
				}
				$keywordsQuery = substr($keywordsQuery, 0, -4) . ")";
				$where[] = $keywordsQuery;
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
				$whereTimeRequired = [];
				$timeCategoryMinutes = [
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
					if (! empty($timeCategoryMinutes[$value])) {
						$whereTimeRequired[] = "
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
									" . $timeCategoryMinutes[$value] . "
								)
							)
						";
					}
				}

				if (! empty($whereTimeRequired)) {
					$where[] = '(' . implode(' OR ', $whereTimeRequired) . ')';
				}
			}
		}

		// Filter: level_technics
		if (! empty($filter['level_technics'])) {
			if (is_array($filter['level_technics'])) {
				$where[] = " (activity.level_technics IN (" . $this->intList($filter['level_technics']) . "))";
			} else {
				$where[] = " (activity.level_technics = " . intval($filter['level_technics']) . ")";
			}
		}

		// Filter: time_required
		if (! empty($filter['level_condition'])) {
			if (is_array($filter['level_condition'])) {
				$where[] = " (activity.level_condition IN (" . $this->intList($filter['level_condition']) . "))";
			} else {
				$where[] = " (activity.level_condition = " . intval($filter['level_condition']) . ")";
			}
		}

		// Filter: project status
		if (! empty($filter['project_status'])) {
			if (is_array($filter['project_status'])) {
				$where[] = " (project.status IN (" . $this->intList($filter['project_status']) . "))";
			} else {
				$where[] = " (project.status = " . intval($filter['project_status']) . ")";
			}
		}

		// Filter: additional infos
		$whereAdditional = [];
		if (isset($filter['online_shop_enabled']) && ($filter['online_shop_enabled'] == true)) {
			$whereAdditional[] = "product.online_shop_enabled = 1";
		}
		if (isset($filter['barrier_free']) && ($filter['barrier_free'] == true)) {
			$whereAdditional[] = "main_offer.barrier_free = 1";
		}
		if (isset($filter['learning_opportunity']) && ($filter['learning_opportunity'] == true)) {
			$whereAdditional[] = "main_offer.learning_opportunity = 1";
		}
		if (isset($filter['child_friendly']) && ($filter['child_friendly'] == true)) {
			$whereAdditional[] = "main_offer.child_friendly = 1";
		}
		if (! empty($whereAdditional)) {
			$where[] = implode(' OR ', $whereAdditional);
		}

		// Filter: offers
		if (! empty($filter['offers'])) {
			if (! is_array($filter['offers'])) {
				$filter['offers'] = [$filter['offers']];
			}
			if (! empty($filter['offers'])) {
				$whereOffer = "";
				foreach ($filter['offers'] as $offer) {
					$whereOffer .= " main_offer.offer_id = " . intval($offer) . " OR ";
				}
				$whereOffer = substr($whereOffer, 0, -4);
				$where[] = "(" . $whereOffer . ")";
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
		$groupBy = $orderBy = "";
		if ($mode === 'parks') {
			$groupBy = " GROUP BY main_offer.park_id ";
			$orderBy = " ORDER BY park ASC ";
		}
		else if ($mode === 'list') {

			// Group by
			$groupBy = "
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
			if ($orderByRand === true) {
				$orderBy = " ORDER BY RAND() ";
			} elseif ($ignoreHintOrder === true) {
				$orderBy = "
					ORDER BY 
						start_date, 
						date_difference ASC, 
						MIN(offer_date.date_from) ASC, 
						duration ASC, 
						date(MAX(offer_date.date_to)) ASC,
						MIN(offer_i18n.title) ASC
				";
			} else {
				$orderBy = "
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
		$limitSql = "";
		if (!is_null($limit) && is_numeric($limit) && ($limit > 0)) {
			$limitSql = " LIMIT " . (isset($offset) && is_numeric($offset) ? intval($offset) . ', ' : '') . intval($limit);
		}

		// Run query
		$qOffers = $this->sdk->db->query($select . $join . $where . $groupBy . $having . $orderBy . $limitSql);

		if ($mode === 'filter_meta') {
			if ($qOffers && $qOffers->numRows > 0) {
				return $qOffers->fetchObject();
			}

			return false;
		}

		if ($mode === 'parks') {
			return $qOffers;
		}

		if ($qOffers->numRows > 0) {

			$offers = [
				'data' => [],
				'total' => 0
			];

			while ($offer = $qOffers->fetchObject()) {
				if (! empty($offer) && ($offer->offer_id > 0)) {
					$offers['data'][] = $offer;
				}
			}

			if (empty($offers['data'])) {
				return false;
			}

			// Paginated lists use a dedicated count (same grouping as the list query)
			if (! is_null($limit) && is_numeric($limit) && ($limit > 0)) {
				$countSelect = "
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
						" . $iconUrlsSelect . "
					FROM offer main_offer
				";
				$qCount = $this->sdk->db->query(
					"SELECT COUNT(*) AS total FROM (" . $countSelect . $join . $where . $groupBy . $having . ") AS counted"
				);
				$offers['total'] = ($qCount && $qCount->numRows > 0) ? (int) $qCount->fetchObject()->total : count($offers['data']);
			} else {
				$offers['total'] = count($offers['data']);
			}

			$offers['data'] = $this->enrichOffersBatch($offers['data'], $returnMinimal, $mapMode);

			return $offers;
		}

		return false;
	}



	/**
	 * Get offer with additional infos
	 */
	public function getOffer(int|object $offer, bool $returnMinimal = false, bool $mapMode = false): object|false
	{

		// Load by id through the shared list path (already batch-enriched)
		if (is_numeric($offer) && ! is_object($offer)) {
			$result = $this->filterOffers(['offers' => $offer], 1, 0, $returnMinimal, $mapMode);
			if (isset($result['data']) && is_array($result['data']) && ! empty($result['data'])) {
				return array_shift($result['data']);
			}

			return false;
		}

		if (is_object($offer) && ! empty($offer) && ($offer->offer_id > 0)) {
			$enriched = $this->enrichOffersBatch([$offer], $returnMinimal, $mapMode);

			return $enriched[0] ?? false;
		}

		return false;
	}



	/**
	 * Batch-hydrate related offer data (replaces per-offer N+1 queries)
	 */
	private function enrichOffersBatch(array $offers, bool $returnMinimal = false, bool $mapMode = false): array
	{

		if (empty($offers)) {
			return [];
		}

		$offerIds = [];
		foreach ($offers as $offer) {
			$offerId = (int) ($offer->offer_id ?? 0);
			if ($offerId > 0) {
				$offerIds[] = $offerId;
			}
		}
		$offerIds = array_values(array_unique($offerIds));
		if (empty($offerIds)) {
			return $offers;
		}

		$idsSql = implode(',', $offerIds);

		// Categories (always needed, including map mode)
		$categoriesByOffer = [];
		$qCategories = $this->sdk->db->query("
			SELECT offer_id, category_id
			FROM category_link
			WHERE offer_id IN (" . $idsSql . ")
		");
		if ($qCategories && $qCategories->numRows > 0) {
			while ($row = $qCategories->fetchObject()) {
				$categoriesByOffer[(int) $row->offer_id][] = (int) $row->category_id;
			}
		}

		// Map overview only needs offer_id + categories for the map config
		if ($mapMode == true) {
			foreach ($offers as $offer) {
				$offer->root_category = null;
				$offer->categories = [];
				foreach ($categoriesByOffer[(int) $offer->offer_id] ?? [] as $categoryId) {
					$category = $this->getCategory($categoryId);
					if ($category !== false) {
						$offer->categories[$categoryId] = $category;
					}
					if (! $offer->root_category) {
						$offer->root_category = $this->getRootCategory($categoryId);
					}
				}
				if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
					$offer->project_status = $offer->status ?? null;
				}
			}

			return $offers;
		}

		// Target groups
		$targetGroupIdsByOffer = [];
		$qTargetGroups = $this->sdk->db->query("
			SELECT offer_id, target_group_id
			FROM target_group_link
			WHERE offer_id IN (" . $idsSql . ")
		");
		if ($qTargetGroups && $qTargetGroups->numRows > 0) {
			while ($row = $qTargetGroups->fetchObject()) {
				if (! empty($row->target_group_id) && array_key_exists($row->target_group_id, $this->targetGroups)) {
					$targetGroupIdsByOffer[(int) $row->offer_id][] = (int) $row->target_group_id;
				}
			}
		}

		// Dates
		$datesByOffer = [];
		$dateFormat = $this->sdk->config['db_date_format'];
		$qDates = $this->sdk->db->query("
			SELECT
				offer_id,
				DATE_FORMAT(date_from, '" . $dateFormat . "') AS date_from,
				DATE_FORMAT(date_to, '" . $dateFormat . "') AS date_to
			FROM offer_date
			WHERE offer_id IN (" . $idsSql . ")
			ORDER BY DATE_FORMAT(date_from, '%Y-%m-%d %H:%i')
		");
		if ($qDates && $qDates->numRows > 0) {
			while ($row = $qDates->fetchObject()) {
				$datesByOffer[(int) $row->offer_id][] = $row;
			}
		}

		// Images
		$imagesByOffer = [];
		$qImages = $this->sdk->db->query("
			SELECT *
			FROM image
			WHERE offer_id IN (" . $idsSql . ")
		");
		if ($qImages && $qImages->numRows > 0) {
			while ($row = $qImages->fetchObject()) {
				$imagesByOffer[(int) $row->offer_id][] = $row;
			}
		}

		// Attach batch-loaded relations; keep target groups in catalogue sort order
		foreach ($offers as $offer) {
			$offerId = (int) $offer->offer_id;

			$offer->root_category = null;
			$offer->categories = [];
			foreach ($categoriesByOffer[$offerId] ?? [] as $categoryId) {
				$category = $this->getCategory($categoryId);
				if ($category !== false) {
					$offer->categories[$categoryId] = $category;
				}
				if (! $offer->root_category) {
					$offer->root_category = $this->getRootCategory($categoryId);
				}
			}

			$offer->targetGroups = [];
			$targetGroupLinks = $targetGroupIdsByOffer[$offerId] ?? [];
			if (! empty($this->targetGroups) && ! empty($targetGroupLinks)) {
				foreach ($this->targetGroups as $targetGroupId => $targetGroup) {
					if (in_array($targetGroupId, $targetGroupLinks)) {
						$offer->targetGroups[$targetGroupId] = $targetGroup;
					}
				}
			}

			$offer->dates = $datesByOffer[$offerId] ?? [];
			$offer->images = $imagesByOffer[$offerId] ?? [];

			// Status is only meaningful for project/research root categories
			if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
				$offer->project_status = $offer->status ?? null;
			}

			if ($returnMinimal == false) {
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
		$qDocuments = $this->sdk->db->get('document', ['offer_id' => $offer->offer_id, 'language' => $this->sdk->lang->langId]);
		if ($qDocuments->numRows > 0) {
			$offer->documents = [];
			while ($row = $qDocuments->fetchObject()) {
				$offer->documents[] = $row;
			}
		}

		// Documents intern
		$qDocuments = $this->sdk->db->get('document_intern', ['offer_id' => $offer->offer_id, 'language' => $this->sdk->lang->langId]);
		if ($qDocuments->numRows > 0) {
			$offer->documents_intern = [];
			while ($row = $qDocuments->fetchObject()) {
				$offer->documents_intern[] = $row;
			}
		}

		// Hyperlinks
		$qHyperlinks = $this->sdk->db->get('hyperlink', ['offer_id' => $offer->offer_id, 'language' => $this->sdk->lang->langId]);
		if ($qHyperlinks->numRows > 0) {
			$offer->hyperlinks = [];
			while ($row = $qHyperlinks->fetchObject()) {
				$offer->hyperlinks[] = $row;
			}
		}

		// Hyperlinks intern
		$qHyperlinks = $this->sdk->db->get('hyperlink_intern', ['offer_id' => $offer->offer_id, 'language' => $this->sdk->lang->langId]);
		if ($qHyperlinks->numRows > 0) {
			$offer->hyperlinks_intern = [];
			while ($row = $qHyperlinks->fetchObject()) {
				$offer->hyperlinks_intern[] = $row;
			}
		}

		// Accessibilities
		$qAccessibilities = $this->sdk->db->query("
			SELECT *
			FROM accessibility
			WHERE accessibility.offer_id = " . $offer->offer_id . "
			LIMIT 1
		");
		if ($qAccessibilities->numRows > 0) {
			$offer->accessibilities = $qAccessibilities->fetchObject();
			$qRatings = $this->sdk->db->query("
				SELECT *
				FROM accessibility_rating
				WHERE accessibility_rating.accessibility_id = " . $offer->accessibilities->accessibility_id . "
			");
			if ($qRatings->numRows > 0) {
				while ($rating = $qRatings->fetchObject()) {
					$offer->accessibilities->ratings[] = $rating;
				}
			}
		}

		// Product
		if ($offer->root_category == CATEGORY_PRODUCT) {

			$qSuppliers = $this->sdk->db->get('supplier', ['offer_id' => $offer->offer_id]);
			if ($qSuppliers->numRows > 0) {
				$offer->suppliers = [];
				while ($row = $qSuppliers->fetchObject()) {
					$offer->suppliers[] = ['contact' => $row->contact, 'is_park_partner' => $row->is_park_partner];
				}
			}

			if ($offer->online_shop_enabled == true) {
				$languagePriority = $this->sdk->config['language_priority'][$this->sdk->lang->langId];
				if (! empty($languagePriority) && is_array($languagePriority)) {

					// Nested CASE picks the best available article language for the current UI lang
					$qArticles = $this->sdk->db->query("

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
												LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $languagePriority[2] . "'
												WHERE main_article.product_article_id = sub_article.product_article_id)
											) END
										FROM product_article AS sub_article
										LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $languagePriority[1] . "'
										WHERE main_article.product_article_id = sub_article.product_article_id
									) END
									FROM product_article AS sub_article
									LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $languagePriority[0] . "'
									WHERE main_article.product_article_id = sub_article.product_article_id
								) END
								FROM product_article AS sub_article
								LEFT JOIN product_article_i18n sub_article_i18n ON sub_article_i18n.product_article_id = sub_article.product_article_id AND sub_article_i18n.language = '" . $this->sdk->lang->langId . "'
								WHERE main_article.product_article_id = sub_article.product_article_id
								
							)

					");

					if ($qArticles->numRows > 0) {
						$offer->articles = [];
						while ($article = $qArticles->fetchObject()) {
							$qArticleLabels = $this->sdk->db->query("
								SELECT *
								FROM product_article_label
								WHERE 
									product_article_id = " . $article->product_article_id . "
									AND language = '" . $this->sdk->lang->langId . "'
							");
							if ($qArticleLabels->numRows > 0) {
								$article->labels = [];
								while ($articleLabel = $qArticleLabels->fetchObject()) {
									$article->labels[] = $articleLabel;
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
			$qAccommodations = $this->sdk->db->get('accommodation', ['offer_id' => $offer->offer_id]);
			if ($qAccommodations->numRows > 0) {
				$offer->accommodations = [];
				while ($row = $qAccommodations->fetchObject()) {
					$offer->accommodations[] = ['contact' => $row->contact, 'is_park_partner' => $row->is_park_partner];
				}
			}
		}

		// POI CSV may reference deleted offers; keep only IDs that still exist
		if (! empty($offer->poi) && ($offer->poi != '') && ! is_array($offer->poi)) {
			$poi = explode(',', $offer->poi);
			$existingPoi = [];
			if (! empty($poi) && is_array($poi)) {
				$poiIds = array_values(array_filter(array_map('intval', $poi)));
				if (! empty($poiIds)) {
					$qPoi = $this->sdk->db->query("
						SELECT offer_id
						FROM offer
						WHERE offer_id IN (" . implode(',', $poiIds) . ")
					");
					if ($qPoi && $qPoi->numRows > 0) {
						while ($row = $qPoi->fetchObject()) {
							$existingPoi[] = (string) $row->offer_id;
						}
					}
				}
			}
			$offer->poi = $existingPoi;
		}

		// Activities that list this offer in their poi CSV are reverse-linked routes
		$linkedRoutes = [];
		$qLinkedRoutes = $this->sdk->db->query("
			SELECT offer_id
			FROM activity
			WHERE 
				poi LIKE '" . $offer->offer_id . ",%'
				OR poi LIKE '%," . $offer->offer_id . ",%'
				OR poi LIKE '%," . $offer->offer_id . "'
				OR poi = '" . $offer->offer_id . "'
		");
		if ($qLinkedRoutes->numRows > 0) {
			$candidateIds = [];
			while ($linkedRoute = $qLinkedRoutes->fetchAssoc()) {
				if (! empty($linkedRoute['offer_id'])) {
					$candidateIds[] = (int) $linkedRoute['offer_id'];
				}
			}
			$candidateIds = array_values(array_unique($candidateIds));

			// Drop candidates whose offer row was removed after the activity link was stored
			if (! empty($candidateIds)) {
				$qPoi = $this->sdk->db->query("
					SELECT offer_id
					FROM offer
					WHERE offer_id IN (" . implode(',', $candidateIds) . ")
				");
				if ($qPoi && $qPoi->numRows > 0) {
					while ($row = $qPoi->fetchObject()) {
						$linkedRoutes[] = (string) $row->offer_id;
					}
				}
			}
		}
		$offer->linked_routes = $linkedRoutes;
	}



	/**
	 * Get category by ID
	 */
	public function getCategory(int $categoryId): object|false
	{

		if (array_key_exists($categoryId, $this->categories)) {
			return $this->categories[$categoryId];
		}
		
		return false;
	}



	/**
	 * Get path for a category
	 */
	public function getCategoryPath(int $categoryId): array
	{

		if (! empty($categoryId)) {
			$path = [];

			while ($categoryId > 0) {
				$category = $this->getCategoryRecord($categoryId);
				if ($category === false) {
					break;
				}

				$categoryId = (int) $category->parent_id;
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

		$qCategoriesString = "
			SELECT
				c1.category_id as category_id,
				c1.parent_id,
				category_i18n.body,
				c1.marker,
				CONCAT_WS(',', c1.parent_id, c2.parent_id, c3.parent_id, c4.parent_id) AS parents,
				c1.sort
			FROM category c1
			INNER JOIN category_i18n ON category_i18n.category_id = c1.category_id AND category_i18n.language = '" . $this->sdk->lang->langId . "'
			LEFT JOIN category c2 ON c1.parent_id = c2.category_id
			LEFT JOIN category c3 ON c2.parent_id = c3.category_id
			LEFT JOIN category c4 ON c3.parent_id = c4.category_id
		";

		// Additional information
		if (! empty($filter) && count($filter) > 0) {

			$qCategoriesString .= "
				LEFT JOIN category_link ON category_link.category_id = c1.category_id
				LEFT JOIN offer ON category_link.offer_id = offer.offer_id
				LEFT JOIN event ON event.offer_id = offer.offer_id
				WHERE 1
			";

			$qAdditional = $this->buildAdditionalInfosSql($filter);
			if (! empty($qAdditional)) {

				// Keep root categories visible even when no child matches the filter
				$qCategoriesString .= "AND (1 " . $qAdditional . ") OR (c1.parent_id = 0)";
			}
		}

		$qCategoriesString .= "GROUP BY c1.category_id ";
		$qCategoriesString .= "ORDER BY c1.sort ";

		$qCategories = $this->sdk->db->query($qCategoriesString);

		if ($qCategories->numRows > 0) {
			$categories = [];

			while ($row = $qCategories->fetchObject()) {
				$categories[$row->category_id] = (object) [
					'category_id' => $row->category_id,
					'parent_id' => $row->parent_id,
					'language' => $this->sdk->lang->langId,
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

		$qCategoryLinks = $this->sdk->db->query("SELECT category_id FROM category_link GROUP BY category_link.category_id");

		if ($qCategoryLinks->numRows > 0) {
			
			// Add main categories
			$categoryLinks = [
				CATEGORY_EVENT,
				CATEGORY_PRODUCT,
				CATEGORY_BOOKING,
				CATEGORY_ACTIVITY,
				CATEGORY_PROJECT,
				CATEGORY_RESEARCH
			];

			while ($row = $qCategoryLinks->fetchObject()) {
				$categoryLinks[] = $row->category_id;
			}

			return $categoryLinks;
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

		$qUsers = $this->getFilterParks($filter);

		if ($qUsers && ($qUsers->numRows > 0)) {
			$users = [];

			while ($row = $qUsers->fetchObject()) {
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
		$qCategories = $this->sdk->db->query("

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

				WHERE category_i18n.language = '" . $this->sdk->lang->langId . "'
				ORDER BY category.sort

		");

		if ($qCategories->numRows > 0) {
			while ($category = $qCategories->fetchObject()) {
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
			$this->sdk->db->delete('target_group');
			$this->sdk->db->delete('target_group_i18n');

			// Insert target groups
			foreach ($targetGroups as $lang => $items) {
				$sort = 0;
				foreach ($items as $targetGroupId => $targetGroup) {
					$sort++;

					// Table target_group – only once
					if ($lang == 'de') {
						$this->sdk->db->insert('target_group', [
							'target_group_id' => $targetGroupId,
							'sort' => $sort,
						]);
					}

					// Table target_group_i18n
					$this->sdk->db->insert('target_group_i18n', [
						'target_group_id' => $targetGroupId,
						'language' => $lang,
						'body' => $targetGroup,
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
			$this->sdk->db->delete('category');
			$this->sdk->db->delete('category_i18n');

			// Insert categories
			foreach ($categories as $lang => $items) {
				foreach ($items as $categoryId => $category) {

					// Table category – only once
					if ($lang == 'de') {
						$this->sdk->db->insert('category', [
							'category_id' => $categoryId,
							'parent_id' => $category['parent_id'],
							'stnet_id' => $category['stnet_id'],
							'alpstein_id' => $category['alpstein_id'],
							'contact_visible_for_alpstein' => $category['contact_visible_for_alpstein'],
							'marker' => $category['marker'],
							'sort' => $category['sort'],
						]);
					}

					// Table category_i18n
					$this->sdk->db->insert('category_i18n', [
						'category_id' => $categoryId,
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
			$this->sdk->db->delete('field_of_activity');
			$this->sdk->db->delete('field_of_activity_i18n');

			// Insert fields of activity
			foreach ($fieldsOfActivity as $lang => $items) {
				$sort = 0;
				foreach ($items as $fieldOfActivityId => $fieldOfActivity) {
					$sort++;

					// Table field_of_activity – only once
					if ($lang == 'de') {
						$this->sdk->db->insert('field_of_activity', [
							'field_of_activity_id' => $fieldOfActivityId,
							'sort' => $sort,
						]);
					}

					// Table field_of_activity_i18n
					$this->sdk->db->insert('field_of_activity_i18n', [
						'field_of_activity_id' => $fieldOfActivityId,
						'language' => $lang,
						'body' => $fieldOfActivity,
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
		$qMunicipalities = $this->sdk->db->get('municipality', $filter);

		// Return empty array if there are no municipalities
		if ($qMunicipalities->numRows <= 0) {
			return [];
		}
		
		// Format municipalities
		$municipalities = [];
		while ($row = $qMunicipalities->fetchArray()) {
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
	private function getRootCategory(int $categoryId): int|false
	{

		if (empty($categoryId)) {
			return false;
		}

		$lastCategoryId = false;

		while ($categoryId > 0) {
			$category = $this->getCategoryRecord($categoryId);
			if ($category === false) {
				break;
			}

			$categoryId = (int) $category->parent_id;
			$lastCategoryId = (int) $category->category_id;
		}

		return $lastCategoryId;
	}



	/**
	 * Get category record from cache or database
	 */
	private function getCategoryRecord(int $categoryId): object|false
	{

		if (array_key_exists($categoryId, $this->categories)) {
			return $this->categories[$categoryId];
		}

		$qCategory = $this->sdk->db->get('category', ['category_id' => $categoryId]);
		if ($qCategory->numRows <= 0) {
			return false;
		}

		$category = $qCategory->fetchObject();
		$this->categories[$categoryId] = $category;

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
	 * Format date/time value for SQL filters, empty string for unparsable values
	 */
	private function formatDatetime(mixed $value, string $format = 'Y-m-d H:i:s'): string
	{

		$datetime = is_string($value) ? date_create($value) : false;

		return ($datetime !== false) ? $datetime->format($format) : '';
	}



	/**
	 * Comma-separated integer list for SQL IN clauses ("0" when nothing is left, which matches no ID)
	 */
	private function intList(mixed $value): string
	{

		$ids = array_map('intval', array_filter((array) $value, 'is_scalar'));

		return ! empty($ids) ? implode(',', $ids) : '0';
	}


	/**
	 * Build additional infos SQL fragments from filter flags
	 */
	private function buildAdditionalInfosSql(array $filter): string
	{

		$additionalQuery = '';
		if (! empty($filter) && is_array($filter)) {
			foreach ($filter as $key => $value) {

				if (! is_array($value) && ! empty($value)) {
					$value = $this->sdk->db->escape($value);
					$key = str_replace('offers_', '', $key);
					if (! empty($key) && is_string($key)) {
						switch ($key) {

							case 'keywords':
								$additionalQuery .= " AND offer.keywords LIKE '%" . $value . "%'";
								break;

							case 'filter_hints':
							case 'offers_filter_hints':
								$additionalQuery .= " AND offer.is_hint = '" . $value . "'";
								break;

							case 'contact_is_park_partner':
								$additionalQuery .= " AND offer.contact_is_park_partner = '" . $value . "'";
								break;

							// Handled elsewhere or not expressible as a simple offer column
							case 'search':
							case 'order_by':
							case 'has_accessibility_informations':
							case 'online_shop_enabled':
								break;

							case 'is_park_event':
								$additionalQuery .= " AND event.is_park_event = '" . $value . "'";
								break;

							default:

								// Remaining keys map 1:1 to offer columns; plain identifiers only, since they cannot be escaped
								if (! empty($key) && is_string($key) && (strlen($key) > 2) && preg_match('/^\w+$/', $key)) {
									$additionalQuery .= " AND offer." . $key . " = '" . $value . "'";
								}
								break;
						}
					}
				}
			}
		}

		return $additionalQuery;
	}



	/**
	 * Get dropdown list with all accessiblity options
	 */
	public function getAccessibilityList(): array
	{

		// Init
		$accessibilites = [];
		$filterOptions = [];

		// Get all possible dropdown options
		$options = $this->sdk->db->query("
			SELECT *
			FROM accessibility_dropdown
		");
		if ($options->numRows > 0) {
			while ($option = $options->fetchArray()) {
				$filterOptions[] = $option['icon_url'];
			}
		}

		// Get accessiblity options
		$selectResult = $this->sdk->db->query("
			SELECT 
				icon_url, 
				MIN(description_" . $this->sdk->lang->langId . ") AS description
			FROM accessibility_rating
			GROUP BY icon_url
			ORDER BY description
		");

		// Populate accessibilities
		if ($selectResult->numRows > 0) {
			$accessibilites[GINTO_INFOS_AVAILABLE] = $this->sdk->lang->get('offer_accessibility_available');
			while ($row = $selectResult->fetchArray()) {
				if (in_array($row['icon_url'], $filterOptions)) {
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
			$this->sdk->db->delete('accessibility_dropdown');

			// Iterate each option
			foreach ($options as $option) {

				// Insert option
				$this->sdk->db->insert('accessibility_dropdown', [
					'icon_url' => $option,
				]);
			}

			return true;
		}

		return false;
	}


}
