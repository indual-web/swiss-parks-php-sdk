<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Main SDK class
|
*/


class ParksAPI
{


	/**
	 * API info
	 */
	public ?object $api = null;


	/**
	 * API hash
	 */
	public string $hash = '';


	/**
	 * Park ID
	 */
	public ?int $parkId = null;


	/**
	 * Configuration
	 */
	public array $config = [];


	/**
	 * Language
	 */
	public string $langId = '';
	public ?ParksLanguage $lang = null;


	/**
	 * Model object
	 */
	public ?ParksModel $model = null;


	/**
	 * Import object
	 */
	public ?ParksImport $import = null;


	/**
	 * Database object
	 */
	public ?ParksSQLite $db = null;


	/**
	 * Logger object
	 */
	public ?ParksLog $logger = null;


	/**
	 * Template
	 */
	public array $template = [];


	/**
	 * View object
	 */
	public ?ParksView $view = null;


	/**
	 * Single offer view mode
	 */
	public bool $singleMode = false;


	/**
	 * Map options
	 */
	public array $mapOptions = [];


	/**
	 * View mode
	 * true: Returns the SDK output as string
	 * false: Echoes the output directly
	 */
	public bool $returnOutput = false;


	/**
	 * Filter object and data
	 */
	public bool $filter = false;
	public array $filterData = [];
	public array $systemFilter = [];
	public bool $filterHideUsers = false;
	public bool $filterDisplayKeywords = false;


	/**
	 * Pagination
	 */
	public int $page = 1;
	public int $total = 0;


	/**
	 * Session
	 */
	public string $sessionName = '';


	/**
	 * Favorites
	 */
	public string $favoritesCookieName = '';
	public array $favorites = [];



	/**
	 * Constructor
	 */
	public function __construct(
		string $language = '',
		string $hash = '',
		string $page_url = '',
		bool $bootstrap_rendering = true,
		bool $bootstrap_setup = true
	) {

		// Get config
		$this->config = $this->getConfig();

		// Check config
		if (! empty($this->config)) {

			// Get park id
			$this->parkId = ! empty($this->config['park_id']) ? $this->config['park_id'] : null;

			// Set absolute path
			$dirname = dirname(__FILE__);
			$this->config['absolute_path'] = substr($dirname, 0, strrpos($dirname, '/'));

			// Set data hash
			$this->hash = ! empty($hash) ? $hash : $this->config['api_hash'];
			$this->returnOutput = isset($this->config['return_output']) ? $this->config['return_output'] : false;

			// Instance of ParksLanguage
			$this->lang = new ParksLanguage($language, $this);
			$this->langId = $this->lang->langId;

			// Instance of ParksLog
			$this->logger = new ParksLog($this);

			// Instance of ParksSQLite
			$this->db = new ParksSQLite($this);

			// Instance of ParksModel
			$this->model = new ParksModel($this);

			// Instance of ParksImport
			$this->import = new ParksImport($this);

			// Load system config
			$this->loadSystemConfig();

			if ($bootstrap_setup) {
				$this->setup();
			}

			if ($bootstrap_rendering) {

				// Instance of ParksView
				if (class_exists($this->config['class_view'])) {
					$this->view = new $this->config['class_view']($this);
				} else {
					$this->abort('The custom view file does not exist.');
				}

				// Init seo urls
				$page_slug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
				$reset_slug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

				// Init session
				if (! empty($this->config['use_sessions'])) {

					$session_url = $page_url;

					if ($session_url == '') {

						// Set session name for seo urls
						$session_url = $this->view->scriptUrl;
						if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && ! empty($session_url)) {

							// Clean url from double slashes
							$session_url = str_replace('//', '/', $session_url);

							// Clean url from page param
							$session_url = preg_replace('/\/' . $page_slug . '\/(\d*)/m', '', $session_url);

							// Clean url from reset param
							$session_url = str_replace('/' . $reset_slug, '', $session_url);

							// Remove last slash
							$session_url = rtrim($session_url, '/');

						}
						
					}

					// Set session
					$this->sessionName = $this->config['session_name'] . '_' . md5($session_url ?? 'default');

					// Get favorites
					$this->favoritesCookieName = $this->config['session_name'] . '_favorites';
					if (! empty($_COOKIE[$this->favoritesCookieName])) {
						$this->favorites = unserialize($_COOKIE[$this->favoritesCookieName], ['allowed_classes' => false]) ?: [];
					}

				}

				// Init reset filter
				$reset_filter = false;

				// Reset filter with seo urls
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
					if (! empty($this->view->scriptUrl) && strstr($this->view->scriptUrl, '/' . $reset_slug)) {
						$reset_filter = true;
					}
				}

				// Reset filter with default urls
				elseif (isset($_GET[$this->config['url_param_prefix'] . 'reset'])) {
					$reset_filter = true;
				}

				// Reset or init filter
				if ($reset_filter === true) {
					$this->resetFilter();
				} else {
					$this->initFilter();
				}

				// Load template
				$this->loadTemplate();
			}
		}
	}



	/**
	 * CLI bootstrap without page rendering (sync, migrate helpers)
	 */
	public static function forScript(): self
	{
		return new self('', '', '', false);
	}



	/**
	 * CLI bootstrap for migrate.php (no rendering, no _setup)
	 */
	public static function forMigration(): self
	{
		return new self('', '', '', false, false);
	}



	/**
	 * Echo or return HTML depending on output mode
	 */
	private function output(string $output): ?string
	{

		if ($this->returnOutput === true) {
			return $output;
		}

		echo $output;

		return null;
	}



	/**
	 * Updates database from XML export
	 */
	public function update($force = false): bool
	{

		if (PHP_SAPI !== 'cli') {
			header('Content-Type: application/json');
		}

		try {

			// Get import urls
			$xml = $this->config['xml_export_offer_url'] . $this->hash;
			$xml_map_layer = $this->config['xml_export_map_layer_url'] . $this->hash;
			$xml_active_offers = $this->config['xml_export_active_offers'] . $this->hash;

			// Import data from XML into database
			$import_ok = $this->import->import($xml, $force);
			$map_layers_ok = $this->import->importMapLayers($xml_map_layer);
			$cleanup_ok = $this->import->cleanUpOffers($xml_active_offers);

			if ($import_ok) {
				$this->updateApiVersion();

				$message = 'The offers were successfully synchronised.';
				if (! $map_layers_ok) {
					$message .= ' Map layer import failed.';
				}
				if (! $cleanup_ok) {
					$message .= ' Inactive offer cleanup failed.';
				}

				$this->outputUpdateJson(true, $message);

				return true;
			}

			$this->outputUpdateJson(false, 'Offer import failed. Check logs and api_hash configuration.');

			return false;


		} catch (Exception $e) {

			$this->outputUpdateJson(false, 'ADB sync exception: ' . $e->getMessage());

			return false;

		}

	}



	/**
	 * Migrate database updates
	 * Rebuilds the database from scratch and runs a full import
	 */
	public function migrate(): bool
	{

		// Drop and recreate database file with current schema
		$this->db->recreate();

		// Re-run setup (triggers a full import)
		$this->api = null;
		$this->setup();

		$success = ! empty($this->api->initialized);

		if ($success && ! $this->logMigration(SDK_VERSION)) {
			$this->logger->info('Migration log could not be sent to the central server.');
		}

		return $success;

	}



	/**
	 * Load template
	 */
	public function loadTemplate(): void
	{

		// Load detail template
		if (file_exists(__DIR__ . '/../templates/' . $this->config['template_folder'] . '/detail.tpl')) {
			$this->template['detail'] = file_get_contents(__DIR__ . '/../templates/' . $this->config['template_folder'] . '/detail.tpl');
			$this->template['detail'] = preg_replace("/<!--(.*?)-->/s", "", $this->template['detail']);
		}

		// Load filter template
		if (file_exists(__DIR__ . '/../templates/' . $this->config['template_folder'] . '/filter.tpl')) {
			$this->template['filter'] = file_get_contents(__DIR__ . '/../templates/' . $this->config['template_folder'] . '/filter.tpl');
			$this->template['filter'] = preg_replace("/<!--(.*?)-->/s", "", $this->template['filter']);
		}
	}



	/**
	 * Compile template
	 */
	public function compileTemplate(string $section, array $template_data = [], array $conditions = []): string
	{
		if (! empty($section)) {

			// Load section
			$return = $this->template[$section];

			// Compile template main category conditions
			foreach ($this->config['template_conditions'] as $condition_tag) {
				preg_match_all("/\[" . $condition_tag . "\@start\](.*?)\[" . $condition_tag . "\@stop\]/s", $return, $condition_found);
				if (! empty($condition_found[0])) {
					foreach ($condition_found[0] as $condition_found) {
						if (array_key_exists($condition_tag, $conditions) && ! empty($conditions[$condition_tag])) {
							$remove_condition = $condition_found;
							$remove_condition = str_replace("[" . $condition_tag . "@start]", '', $remove_condition);
							$remove_condition = str_replace("[" . $condition_tag . "@stop]", '', $remove_condition);
							$return = str_replace($condition_found, $remove_condition, $return);
						} else {
							$return = str_replace($condition_found, '', $return);
						}
					}
				}
			}

			// Compile tags
			foreach ($this->config['template_tags'] as $tag) {

				// ISSET
				preg_match_all("/\[ISSET\(" . $tag . "\)\@start\](.*?)\[ISSET\(" . $tag . "\)@stop\]/s", $return, $condition_found);
				if (! empty($condition_found[0])) {
					foreach ($condition_found[0] as $condition_found) {
						if (array_key_exists($tag, $template_data) && ! empty($template_data[$tag])) {
							$remove_condition = $condition_found;
							$remove_condition = str_replace("[ISSET(" . $tag . ")@start]", '', $remove_condition);
							$remove_condition = str_replace("[ISSET(" . $tag . ")@stop]", '', $remove_condition);
							$return = str_replace($condition_found, $remove_condition, $return);
						} else {
							$return = str_replace($condition_found, '', $return);
						}
					}
				}

				// NOTISSET
				preg_match_all("/\[NOTISSET\(" . $tag . "\)\@start\](.*?)\[NOTISSET\(" . $tag . "\)@stop\]/s", $return, $condition_found);
				if (! empty($condition_found[0])) {
					foreach ($condition_found[0] as $condition_found) {
						if (!array_key_exists($tag, $template_data) || empty($template_data[$tag])) {
							$remove_condition = $condition_found;
							$remove_condition = str_replace("[NOTISSET(" . $tag . ")@start]", '', $remove_condition);
							$remove_condition = str_replace("[NOTISSET(" . $tag . ")@stop]", '', $remove_condition);
							$return = str_replace($condition_found, $remove_condition, $return);
						} else {
							$return = str_replace($condition_found, '', $return);
						}
					}
				}
			}

			// Compile language labels
			preg_match_all("/__LANG\[(.+)\]__/i", $return, $lang_vars);
			if (! empty($lang_vars[1])) {
				foreach ($lang_vars[1] as $lang_label) {
					$lang_content = $this->lang->get($lang_label);
					if ($lang_content == $lang_label) {
						$lang_content = '';
					}
					$return = str_replace('__LANG[' . $lang_label . ']__', $lang_content, $return);
				}
			}

			// Compile template tags
			if (! empty($this->config['template_tags'])) {
				foreach ($this->config['template_tags'] as $tag) {
					$tag_content = '';
					if (array_key_exists($tag, $template_data) && ! empty($template_data[$tag])) {
						$tag_content = $template_data[$tag];
					}
					$return = str_replace('__' . $tag . '__', $tag_content, $return);
				}
			}

			return $return;
		}

		return '';
	}



	/**
	 * Displays filter for offers
	 */
	public function showOffersFilter(array $categories = [], array $filter = [], ?int $parkId = null): ?string
	{

		// System filter: target groups
		if (! empty($filter['system_filter']['target_groups'])) {
			$this->systemFilter['target_groups'] = $filter['system_filter']['target_groups'];
			unset($filter['system_filter']['target_groups']);
		}

		// System filter: fields of activity
		if (! empty($filter['system_filter']['fields_of_activity'])) {
			$this->systemFilter['fields_of_activity'] = $filter['system_filter']['fields_of_activity'];
			unset($filter['system_filter']['fields_of_activity']);
		}

		// Init option to hide user filter
		if (! empty($filter['hide_user_filter']) && ($filter['hide_user_filter'] === true)) {
			$this->filterHideUsers = true;
			unset($filter['hide_user_filter']);
		}

		// Init option filter display with keywords
		if (! empty($filter['show_keywords_filter']) && ($filter['show_keywords_filter'] === true)) {
			$this->filterDisplayKeywords = true;
			unset($filter['show_keywords_filter']);
		}

		// Set park id
		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		if (! $this->isOfferDetail() || $this->config['always_show_filter'] == true) {

			// One meta query: type counts, category IDs, parks
			$event_count = 0;
			$booking_count = 0;
			$activity_count = 0;
			$product_count = 0;
			$projects_count = 0;
			$flat_categories = [];
			$users = [];

			$filter_for_meta = $this->normalizeOffersFilter($parkId, $categories, $filter, true);
			$filter_meta = $this->model->getFilterMeta($filter_for_meta);
			if (! empty($filter_meta)) {
				$event_count = $filter_meta['event_count'];
				$booking_count = $filter_meta['booking_count'];
				$activity_count = $filter_meta['activity_count'];
				$product_count = $filter_meta['product_count'];
				$projects_count = $filter_meta['project_count'];
				$flat_categories = $filter_meta['category_ids'];
				if (empty($this->parkId) && ($this->filterHideUsers == false)) {
					$users = $filter_meta['parks'];
				}
			}

			// Categories has to be an array
			$categories = is_array($flat_categories) ? $flat_categories : [$flat_categories];

			// Check, if categories are not empty
			$first = reset($categories);
			if (count($categories) == 0 || (count($categories) == 1 && empty($first))) {
				$categories = false;
			}

			// Group and flatten categories for select field
			$categories = $this->groupCategoriesByParent($categories);
			$categories = $this->flattenCategoriesForSelect($categories);

			// Check if event filter (date-from, date-to) should be displayed
			$show_event_filter = false;
			if ($event_count > 0) {
				$show_event_filter = true;
			}

			// Check if route filter should be displayed (has enough routes, and is activated)
			$show_route_filter = false;
			if (($this->config['show_route_filter'] == true) && ($activity_count > 0)) {
				$show_route_filter = true;
			}

			// Check if project filter should be displayed (has enough projects, and is activated)
			$show_project_filter = false;
			if ($projects_count > 0) {
				$show_project_filter = true;
			}

			// Check if only projects exists in export
			$projectsOnly = false;
			if ($projects_count > 0) {
				if (($event_count == 0) && ($booking_count == 0) && ($activity_count == 0) && ($product_count == 0)) {
					$projectsOnly = true;
				}
			}

			// Load view
			$params = [
				'categories' => $categories,
				'selected' => $this->filterData,
				'filter' => $this->filter,
				'users' => $users ?? [],
				'show_event_filter' => $show_event_filter,
				'show_route_filter' => $show_route_filter,
				'show_target_group_filter' => $this->config['show_target_group_filter'],
				'show_fields_of_activity_filter' => $this->config['show_fields_of_activity_filter'],
				'show_accessibility_filter' => $this->config['show_accessibility_filter'],
				'show_project_filter' => $show_project_filter,
				'hide_accessibility_filter' => $filter['hide_accessibility_filter'] ?? false,
				'projects_only' => $projectsOnly,
				'show_municipality_filter' => $this->config['show_municipality_filter'] ?? true,
				'park_id' => $parkId,
			];

			return $this->view->filter($params);
		}

		return null;
	}



	/**
	 * Displays list of offers (optionally filtered)
	 */
	public function showOffersList(array $categories = [], array $filter = [], ?int $parkId = null): ?string
	{

		// System filter: target groups
		if (! empty($filter['system_filter']['target_groups'])) {
			$this->systemFilter['target_groups'] = $filter['system_filter']['target_groups'];
			unset($filter['system_filter']['target_groups']);
		}

		// System filter: fields of activity
		if (! empty($filter['system_filter']['fields_of_activity'])) {
			$this->systemFilter['fields_of_activity'] = $filter['system_filter']['fields_of_activity'];
			unset($filter['system_filter']['fields_of_activity']);
		}

		// Set park id
		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		if ($this->isOfferDetail() == false) {
			$this->page = 1;

			// Handle seo urls
			$page_slug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
			if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $page_slug . '/')) {

				// Split url by slashes
				$seo_url = explode('/', $this->view->scriptUrl);

				// Get page id
				$page_segment_pos = count($seo_url) - 1;
				$this->page = intval($seo_url[$page_segment_pos]);
			}

			// Default urls
			else {
				$param_name = $this->config['url_param_prefix'] . 'page';
				$form_param_name = $this->config['form_prefix'] . 'page';

				// Prefer url_param_prefix
				if (isset($_GET[$param_name]) && $_GET[$param_name] >= 1) {
					$this->page = $_GET[$param_name];
				}

				// Fallback for older form_prefix bookmarks
				elseif (
					$form_param_name !== $param_name
					&& isset($_GET[$form_param_name])
					&& $_GET[$form_param_name] >= 1
				) {
					$this->page = $_GET[$form_param_name];
				}
			}

			// Special case: exclude offers from user SAJA in offers list on parks.swiss
			if (empty($parkId) && strstr($_SERVER['HTTP_HOST'], 'parks.swiss')) {
				$filter['exclude_park_ids'] = [37];
			}

			// Filter keywords by param
			if ((isset($_REQUEST['keyword']) || ! empty($_SESSION[$this->sessionName]['keyword'])) && ! empty($this->config['keyword_filter'][$this->langId])) {

				$get_keyword = '';

				// Save selected keyword into session
				if (isset($_REQUEST['keyword'])) {
					$_SESSION[$this->sessionName]['keyword'] = $_REQUEST['keyword'];
					$get_keyword = $_REQUEST['keyword'];
				}

				// Get selected keyword from session
				else if (! empty($_SESSION[$this->sessionName]['keyword'])) {
					$get_keyword = $_SESSION[$this->sessionName]['keyword'];
				}

				// Prepare keyword, replace special chars
				$get_keyword = str_replace("---", "*", $get_keyword);
				$get_keyword = str_replace("-_-", "/", $get_keyword);

				// Check keyword
				if (in_array($get_keyword, $this->config['keyword_filter'][$this->langId])) {
					$filter['keywords'] = $get_keyword;
				}
			}

			// Get offers and total
			$offers = $this->getOffersList($parkId, $categories, $this->page, $this->config['offers_per_page'], $filter, false, true);

			// Set total
			$this->total = ! empty($offers['total']) ? ceil($offers['total'] / $this->config['offers_per_page']) : 0;

			// Show offers list
			return $this->view->listOffers($offers);
		}

		return '';
	}



	/**
	 * Displays map of offers (optionally filtered)
	 */
	public function showOffersMap(array $categories = [], array $filter = [], ?int $parkId = null): string
	{

		// System filter: target groups
		if (! empty($filter['system_filter']['target_groups'])) {
			$this->systemFilter['target_groups'] = $filter['system_filter']['target_groups'];
			unset($filter['system_filter']['target_groups']);
		}

		// System filter: fields of activity
		if (! empty($filter['system_filter']['fields_of_activity'])) {
			$this->systemFilter['fields_of_activity'] = $filter['system_filter']['fields_of_activity'];
			unset($filter['system_filter']['fields_of_activity']);
		}

		// Set park id
		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		if (! $this->isOfferDetail()) {

			// Set park
			$this->setSelectedPark($parkId);

			// Get offers and total
			$offers = $this->getOffersList($parkId, $categories, null, null, $filter, false, false, true);
			return $this->loadMapsApi() . $this->view->getOverviewMap($offers['data'] ?? []);
		}

		return '';
	}



	/**
	 * Displays list of poi (get by id)
	 */
	public function showOfferPoiList(?array $poi = null): array
	{

		if (! is_array($poi) || empty($poi)) {
			return [];
		}

		$result = $this->model->filterOffers(['offers' => $poi]);

		return is_array($result) ? ($result['data'] ?? []) : [];
	}



	/**
	 * Show offers total
	 */
	public function showTotal(): ?string
	{

		if ($this->total > 0) {
			$label = ($this->total == 1) ? $this->lang->get('offer') : $this->lang->get('offers');
			$total = number_format((int) $this->total, 0, '.', "'") . ' ' . $label;

			return $this->output($total);
		}

		return null;
	}



	/**
	 * Displays pagination
	 */
	public function showOffersPagination(): ?string
	{
		if (! $this->isOfferDetail()) {
			return $this->view->pagination($this->page, $this->total);
		}

		return '';
	}



	/**
	 * Display detail of offer
	 */
	public function showOfferDetail(?int $single_offer_id = null): string
	{

		if ($this->isOfferDetail() || ! empty($single_offer_id)) {

			// Init
			$original_offer_id = 0;
			$param_name = $this->config['url_param_prefix'] . 'offer';
			$detail_slug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
			$poi_slug = ! empty($this->config['seo_url_poi_slug']) ? $this->config['seo_url_poi_slug'] : '';

			// Single offer mode
			if (! empty($single_offer_id) && (intval($single_offer_id) > 0)) {
				$this->singleMode = true;
				$offer_id = intval($single_offer_id);
			}
			// Default mode
			else {

				// Handle seo urls
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $detail_slug . '/')) {

					// Remove slash on last string position
					$scriptUrl = rtrim($this->view->scriptUrl, '/');

					// Split url by slashes
					$seo_url = explode('/', $scriptUrl);

					// Handle poi details
					if (in_array($poi_slug, $seo_url)) {

						// Get original offer id
						$last_segment_position = count($seo_url) - 1;
						$original_offer_id = intval($seo_url[$last_segment_position]);

						// Remove poi id from seo url
						$seo_url = array_slice($seo_url, 0, -2);

					}

					// Split last url segment by dashes
					$last_segment_position = count($seo_url) - 1;
					$title_slug = explode('-', $seo_url[$last_segment_position]);

					// Get offer id from last url segment
					$offer_id_position = count($title_slug) - 1;
					$offer_id = intval($title_slug[$offer_id_position]);
				}

				// Default urls
				else {
					$offer_id = intval($_GET[$param_name]);
				}
			}

			// Check offer id
			if (! empty($offer_id)) {

				// Get offer
				$offer = $this->model->getOffer($offer_id);
				if (! empty($offer)) {

					// Set selected park
					$this->setSelectedPark($offer->parkId);

					// Load view
					$return = $this->loadMapsApi();
					$return .= $this->view->detail($offer, $original_offer_id);

					return $return;
					
				} else {

					// Soft errors: let CMS decide how to render missing offers
					if (! empty($this->config['soft_errors'])) {
						return '';
					}

					// Show 404 error
					header('HTTP/1.0 404 Not Found');
					echo '
						<h1>404 Not Found</h1>
						The page that you have requested could not be found.
					';
					exit();
				}
			}
		}

		return '';
	}



	/**
	 * Display all visitor favorites
	 */
	public function showFavorites(): ?string
	{

		// Offer detail
		if ($this->isOfferDetail()) {
			return $this->showOfferDetail();
		}

		// Offer list
		if (! empty($this->favorites)) {
			return $this->showOffersList([], ['offers' => $this->favorites]);
		}

		// No favorites set
		$message = '<p class="no_results">' . $this->lang->get('favorites_empty') . '</p>';

		return $this->output($message);
	}



	/**
	 * Toggle favorite state
	 * Add or remove favorite from list
	 */
	public function toggleFavorite(int $offer_id): bool
	{
		if (($offer_id > 0) && ($this->config['use_sessions'] == true)) {

			// Remove offer from favorites
			if (in_array($offer_id, $this->favorites)) {
				unset($this->favorites[$offer_id]);
				$return = false;
			}

			// Add new favorite
			else {
				$this->favorites[$offer_id] = $offer_id;
				$return = true;
			}

			// Save cookie
			setcookie($this->favoritesCookieName, serialize($this->favorites), time() + (60 * 60 * 24 * 365), '/');

			return $return;
		}

		return false;
	}



	/**
	 * Empty favorites
	 */
	public function cleanFavorites(): void
	{

		// Empty cookie
		setcookie($this->favoritesCookieName, '', time(), '/');

	}



	/**
	 * Returns if detail page should be displayed
	 */
	public function isOfferDetail(): bool
	{

		// Init
		$is_offer_detail = false;
		$param_name = $this->config['url_param_prefix'] . 'offer';
		$offer_id = ! empty($_GET[$param_name]) ? intval($_GET[$param_name]) : null;
		$detail_slug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';

		// Check seo url
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $detail_slug . '/')) {
			$is_offer_detail = true;
		} elseif (($offer_id > 0) && $this->model->offerExists($offer_id)) {
			$is_offer_detail = true;
		}

		return $is_offer_detail;
	}



	/**
	 * Returns if filter is active
	 */
	public function isFilterActivated(): bool
	{
		return $this->filter;
	}



	/**
	 * Returns filter data
	 */
	public function getFilterData(): array
	{
		return $this->filterData;
	}



	/**
	 * Get list of offers
	 */
	public function getOffersList(
		?int $parkId = null,
		array $categories = [],
		?int $page = null,
		?int $limit = null,
		array $filter = [],
		bool $ignore_filter = false,
		bool $return_minimal = false,
		bool $map_mode = false
	): array {

		$filter = $this->normalizeOffersFilter($parkId, $categories, $filter, $ignore_filter);

		$offset = 0;
		if (! empty($page) && is_numeric($page)) {
			$offset = ($page - 1) * $this->config['offers_per_page'];
		}

		$result = $this->model->filterOffers($filter, $limit, $offset, $return_minimal, $map_mode);

		return is_array($result) ? $result : ['data' => [], 'total' => 0];
	}



	/**
	 * Count offers grouped by main category type
	 */
	public function countOffersByCategory(
		?int $parkId = null,
		array $categories = [],
		array $filter = []
	): stdClass|false {

		$filter = $this->normalizeOffersFilter($parkId, $categories, $filter, true);

		return $this->model->countOffersByCategory($filter);
	}



	/**
	 * Get category IDs that have at least one matching offer
	 */
	public function getFilterCategories(
		?int $parkId = null,
		array $categories = [],
		array $filter = []
	): ParksSQLiteResult|false {

		$filter = $this->normalizeOffersFilter($parkId, $categories, $filter, true);

		return $this->model->getFilterCategories($filter);
	}



	/**
	 * Get offer details
	 */
	public function getOfferDetail(int $offer_id): object|false
	{
		return $this->model->getOffer($offer_id);
	}



	/**
	 * Get list of categories
	 */
	public function getCategoriesList(): array
	{
		return $this->model->getAllCategories();
	}



	/**
	 * Get list of categories preformatted for select inputs
	 */
	public function getCategoriesListForSelect(): array
	{
		$categories = $this->model->getCategoryTree();
		return $categories;
	}



	/**
	 * Setup process
	 */
	private function setup(): void
	{

		$this->securityChecks();

		$q_api = $this->db->get('api');

		if ($q_api) {
			if ($q_api->numRows > 0) {
				$this->api = $q_api->fetchObject();
			} else {
				$this->api = (object) [
					'initialized' => 0,
					'version' => SDK_VERSION,
				];

				$this->db->insert('api', (array)$this->api);
			}

			if (version_compare((string) SDK_VERSION, (string) $this->api->version, '>')) {
				$this->logger->info('The current SDK version is out of date. Please run: bash bin/migrate.sh');
			}

			if (! $this->api->initialized) {

				// Initialize SDK
				$xml = $this->config['xml_export_offer_url'] . $this->hash;
				$xml_map_layer =  $this->config['xml_export_map_layer_url'] . $this->hash;

				// Import data from XML into database
				$import_ok = $this->import->import($xml);
				$this->import->importMapLayers($xml_map_layer);

				if ($import_ok) {
					$this->model->reloadTargetGroups();
					$this->model->reloadFieldsOfActivity();
					$this->model->reloadCategories();

					$this->api->initialized = 1;
					$this->db->update('api', [
						'initialized' => 1,
						'version' => SDK_VERSION,
					]);
					$this->api->version = SDK_VERSION;
				} else {
					$this->logger->error('Initial import failed. SDK is not initialized. Check api_hash and run: bash bin/sync.sh --force (or php bin/sync.php --force)');
				}
			}
		} else {
			throw new RuntimeException('SDK could not be initialized.');
		}
	}



	/**
	 * Set current selected park
	 */
	public function setSelectedPark(?int $parkId = null): void
	{

		// Init park id
		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		if ($this->config['use_sessions'] == true) {
			if (! empty($parkId) && ($parkId > 0) && ! empty($this->config['parks'][$parkId])) {
				$_SESSION[$this->sessionName]['selectedPark'] = $this->config['parks'][$parkId];
			} else {
				unset($_SESSION[$this->sessionName]['selectedPark']);
			}
		}
	}



	/**
	 * Validate hash
	 */
	public function validateHash(): bool
	{
		$validate = file_get_contents($this->config['xml_export_url'] . 'validate/' . $this->hash);

		if ($validate == "Valid") {
			return true;
		} else if (! empty($this->hash) && strlen($this->hash) >= 32) {
			return true;
		}

		return false;
	}



	/**
	 * Init filter
	 */
	public function initFilter(): void
	{

		// Init
		$param_name = $this->config['form_prefix'] . 'filter';
		$post_data = isset($_POST[$param_name]) ? $_POST[$param_name] : null;

		// Prepare URL params
		$get_params = false;
		$allowed_get_params = ['categories', 'target_groups', 'fields_of_activity', 'accessibilities'];
		foreach ($allowed_get_params as $param) {
			if (! empty($_GET[$param])) {
				$get_params = true;
			}
		}

		// Set filter by all types
		$fields = ['categories', 'date_from', 'date_to', 'search', 'park_id', 'time_required', 'level_technics', 'level_condition', 'route_length_min', 'route_length_max', 'project_status', 'target_groups', 'fields_of_activity', 'municipalities', 'accessibilities'];
		foreach ($fields as $field) {

			// Get categories from URL
			if (empty($post_data) && ($get_params === true) && in_array($field, $allowed_get_params)) {
				foreach ($allowed_get_params as $param) {
					if (! empty($_GET[$param])) {

						// Prepare get params
						$get_values = explode(',', $_GET[$param]);
						foreach ($get_values as $key => $value) {

							// Only allow integers
							if ($value > 0) {
								$get_values[$key] = intval($value);
							}
							// Otherwise, remove param
							else {
								unset($get_values[$key]);
							}
						}

						// Set filter by params
						if (! empty($get_values)) {
							$this->filter = true;
							$this->filterData[$param] = $get_values;
						}
					}
				}
			}

			// Post data
			elseif (! empty($post_data)) {
				$this->filter = true;
				$this->filterData[$field] = isset($post_data[$field]) ? $post_data[$field] : null;
			}

			// Session data
			elseif ($this->config['use_sessions'] && (isset($_SESSION[$this->sessionName]['filter'][$field]) && ! empty($_SESSION[$this->sessionName]['filter'][$field]))) {
				$this->filter = true;
				$this->filterData[$field] = $_SESSION[$this->sessionName]['filter'][$field];
			}
		}

		// Set filter in session
		if ($this->config['use_sessions']) {
			$_SESSION[$this->sessionName]['filter'] = $this->filterData;
		}

		// Reload page
		if (! empty($post_data) || ($get_params === true)) {
			$this->reloadPage();
		}
	}



	/**
	 * Reset filter
	 */
	public function resetFilter(): void
	{
		$this->filterData = [];
		if ($this->config['use_sessions']) {
			unset($_SESSION[$this->sessionName]['filter']);
		}
	}



	/**
	 * Reload page
	 */
	protected function reloadPage(bool $reset_mode = false): never
	{

		// SEO URLs
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
			header('Location: ' . $this->view->scriptUrl);
		}

		// No SEO URLs
		else {

			// Set URL
			$url = $this->view->scriptUrlWithParams;

			// Remove reset param
			if ($reset_mode === true) {
				$url = str_replace(['?reset=1', '&reset=1'], '', $url);
			}

			// Redirect
			header('Location: ' . $url);
		}

		exit;
	}



	/**
	 * Get offers
	 */
	public function getOffers(
		?int $parkId = null,
		array $categories = []
	): array {

		return $this->getOffersList($parkId, $categories);
	}



	/**
	 * Normalize filter array for offer queries
	 */
	private function normalizeOffersFilter(
		?int $parkId = null,
		array $categories = [],
		array $additional_filter = [],
		bool $ignore_filter = false
	): array {

		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		$filter = [];

		if ($ignore_filter == false) {
			foreach ($this->filterData as $field => $value) {
				if (! empty($value)) {
					$fieldname = substr($field, strlen($this->config['form_prefix']), strlen($field));
					$filter[$fieldname] = $value;
				}
			}
		}
		if (! empty($categories) && ! isset($filter['categories'])) {
			$filter['categories'] = $categories;
		}

		// Offer settings
		if (! empty($additional_filter['offer_settings'])) {
			$filter['offer_settings'] = $additional_filter['offer_settings'];
		}

		// Target groups
		if (! empty($additional_filter['target_groups'])) {
			$filter['target_groups'] = $additional_filter['target_groups'];
		}

		// Fields of activity
		if (! empty($additional_filter['fields_of_activity'])) {
			$filter['fields_of_activity'] = $additional_filter['fields_of_activity'];
		}

		// Accessibilities
		if (! empty($additional_filter['accessibilities'])) {
			$filter['accessibilities'] = $additional_filter['accessibilities'];
		}

		// Search words
		if (! empty($additional_filter['search'])) {
			$filter['search'] = $additional_filter['search'];
		}

		// Exclude park ids
		if (! empty($additional_filter['exclude_park_ids']) && is_array($additional_filter['exclude_park_ids'])) {
			$filter['exclude_park_ids'] = $additional_filter['exclude_park_ids'];
		}

		// Order_by
		if (! empty($additional_filter['order_by'])) {
			$filter['order_by'] = $additional_filter['order_by'];
		}

		// TextOfferSearch
		if (! empty($additional_filter['textOfferSearch'])) {
			$filter['search'] .= $additional_filter['textOfferSearch'];
		}

		// Online_shop_enabled
		if (! empty($additional_filter['online_shop_enabled'])) {
			$filter['online_shop_enabled'] = $additional_filter['online_shop_enabled'];
		}

		// Offers_barrier_free
		if (! empty($additional_filter['offers_barrier_free'])) {
			$filter['barrier_free'] = $additional_filter['offers_barrier_free'];
		}

		// Offers_learning_opportunity
		if (! empty($additional_filter['offers_learning_opportunity'])) {
			$filter['learning_opportunity'] = $additional_filter['offers_learning_opportunity'];
		}

		// Offers_child_friendly
		if (! empty($additional_filter['offers_child_friendly'])) {
			$filter['child_friendly'] = $additional_filter['offers_child_friendly'];
		}

		// Offer_filter_hints
		if (isset($additional_filter['offers_filter_hints'])) {
			$filter['is_hint'] = $additional_filter['offers_filter_hints'];
		}

		// Keywords
		if (isset($additional_filter['keywords'])) {
			$filter['keywords'] = $additional_filter['keywords'];
		}

		// Contact_is_park_partner
		if (isset($additional_filter['contact_is_park_partner'])) {
			$filter['contact_is_park_partner'] = $additional_filter['contact_is_park_partner'];
		}

		// Is_park_event
		if (isset($additional_filter['offers_is_park_event'])) {
			$filter['offers_is_park_event'] = $additional_filter['offers_is_park_event'];
		}

		// Municipality
		if (! empty($additional_filter['municipalities'])) {
			$filter['municipalities'] = $additional_filter['municipalities'];
		}

		// Has accessibility informations
		if (isset($additional_filter['has_accessibility_informations'])) {
			$filter['has_accessibility_informations'] = $additional_filter['has_accessibility_informations'];
		}

		// Offers
		if (isset($additional_filter['offers'])) {
			$filter['offers'] = $additional_filter['offers'];
		}

		// Offer restrictions
		if (! empty($additional_filter['offerRestriction']) && ($additional_filter['offerRestriction'] == 'today')) {
			$filter['offers_of_today'] = true;
		}

		// Park id
		if (! empty($parkId)) {
			$filter['park_id'] = $parkId;
		}

		return $filter;
	}



	/**
	 * Load map api
	 */
	public function loadMapsApi(): ?string
	{
		if ($this->config['prevent_css_js_include'] == false) {

			$map_url = rtrim($this->config['interactive_map_url'] ?? 'https://map.parks.swiss', '/');
			$output = '
				<link rel="stylesheet" href="' . $map_url . '/dist/css/style.css" />
				<script type="module" src="' . $map_url . '/dist/js/swiss-parks-map.umd.js"></script>
			';

			return $this->output($output);
		}

		return '';
	}



	/**
	 * Flatten category tree for select-field options
	 */
	public function flattenCategoriesForSelect(array $categories, int $index = 0, int $level = 0): array
	{
		$return = [];

		// Get main category on top
		$main_category = [];
		if (isset($categories[0]) && is_array($categories[0])) {
			$main_category = array_keys($categories[0]);
			$main_category = reset($main_category);
		}

		// Special cases
		if (($level == 0) && ($index == 0) && ($main_category == CATEGORY_PRODUCT) && isset($categories[CATEGORY_GASTRONOMY_AND_ACCOMMODATION]) && (count($categories[CATEGORY_PRODUCT]) == 1)) {
			$index = CATEGORY_GASTRONOMY_AND_ACCOMMODATION;
		}

		if (isset($categories[$index]) && ! empty($categories[$index])) {
			foreach ($categories[$index] as $id => $category) {
				if (
					(($level != 0) || in_array($id, [CATEGORY_ACTIVITY, CATEGORY_PRODUCT]))
					&&
					(($level != 1) || ! in_array($index, [CATEGORY_ACTIVITY, CATEGORY_PRODUCT]))
					&&
					(($level != 2) || ! in_array($index, [CATEGORY_GASTRONOMY_AND_ACCOMMODATION]))
				) {
					$return[$id] = $category;
				}

				if (isset($categories[$id])) {
					$childs = $this->flattenCategoriesForSelect($categories, $id, $level + 1);
					if (! empty($childs)) {
						if (
							(($level == 0) && ! in_array($id, [CATEGORY_ACTIVITY, CATEGORY_PRODUCT]))
							||
							(($level == 1) && in_array($index, [CATEGORY_ACTIVITY, CATEGORY_PRODUCT]))
							||
							(($level == 2) && in_array($index, [CATEGORY_GASTRONOMY_AND_ACCOMMODATION]))
						) {
							$return[$category] = $childs;
						} else {
							$return += $childs;
						}
					}
				}
			}
		}

		return $return;
	}



	/**
	 * Group categories by parent id for filter UI
	 */
	public function groupCategoriesByParent(array|false $categories = false): array
	{
		$return = [];
		$parents = [];

		// Get all categories
		$all_categories = $this->model->getAllCategories();

		// Get all parents
		if (is_array($categories) && ! empty($categories)) {
			foreach ($categories as $category_id) {
				if (($category_id > 0) && isset($all_categories[$category_id]) && ! empty($all_categories[$category_id])) {
					$parents = array_merge($parents, $all_categories[$category_id]->parents);
				}
			}
		}

		// Return structured categories
		if (is_array($all_categories) && ! empty($all_categories)) {
			foreach ($all_categories as $category_id => $category) {
				// Set parent id
				$parent_id = $category->parent_id ? $category->parent_id : 0;

				// Add category
				if (($categories == false) || (in_array($category_id, $categories) || in_array($category_id, $parents))) {
					$return[$parent_id][$category_id] = $category->body;
				}
			}
		}

		// Return flat (non structured) categories
		else {
			$return = $categories;
		}

		return $return;
	}



	/**
	 * Security checks
	 */
	public function securityChecks(): void
	{

		// Nothing to sanitize
		if (empty($_GET) || ! is_array($_GET)) {
			return;
		}

		// Prefixes used by SDK query/form params
		$url_prefix = ! empty($this->config['url_param_prefix']) ? (string) $this->config['url_param_prefix'] : '';
		$form_prefix = ! empty($this->config['form_prefix']) ? (string) $this->config['form_prefix'] : '';

		// Known unprefixed SDK keys (CMS query vars stay untouched)
		$known_keys = [
			'offer',
			'page',
			'reset',
			'poi',
			'tab',
			'keyword',
			'filter',
			'force_back_to_referer',
			'original_category',
		];

		foreach ($_GET as $key => $value) {
			if (! is_string($value)) {
				continue;
			}

			// Allowlist: known keys or prefixed SDK params
			$key_string = (string) $key;
			$is_sdk_key = in_array($key_string, $known_keys, true)
				|| ($url_prefix !== '' && str_starts_with($key_string, $url_prefix))
				|| ($form_prefix !== '' && str_starts_with($key_string, $form_prefix));

			if (! $is_sdk_key) {
				continue;
			}

			// Sql injection protection
			$tmp = str_replace(["*", "'", '"', ";", "="], "", $value);

			// Cross site scripting protection
			$tmp = str_replace(["<", ">"], ["&lt;", "&gt;"], $tmp);

			// Escape html entities
			$tmp = htmlentities($tmp, ENT_QUOTES);

			$_GET[$key] = $tmp;
		}
	}



	/**
	 * Add slashes
	 */
	public function addSlashes(mixed &$item, mixed $key): void
	{
		$item = (! is_array($item) ? addslashes($item) : $item);
	}



	/**
	 * Get SDK configuration
	 */
	public function getConfig(): array
	{

		// Load config file
		$config_dir = realpath(dirname(__FILE__)) . '/../config';
		if (file_exists($config_path = $config_dir . '/config.php')) {
			require($config_path);
			if (file_exists($config_dir . '/config.local.php')) {
				require($config_dir . '/config.local.php');
			}
		} else {
			echo 'The configuration file does not exist.';
			exit();
		}

		// Check config data
		if (! isset($config) || ! is_array($config)) {
			echo 'Your config file does not appear to be formatted correctly.';
			exit();
		}

		return $config;
	}



	/**
	 * Abort with exit, or throw when soft_errors is enabled
	 */
	public function abort(string $message): void
	{

		// CMS / embed mode
		if (! empty($this->config['soft_errors'])) {
			throw new RuntimeException($message);
		}

		// Standalone mode
		echo $message;
		exit();
	}



	/**
	 * Set view script URLs (path used for forms/links; query string optional)
	 */
	public function setScriptUrl(string $url): void
	{

		// View not bootstrapped (CLI / forScript)
		if (empty($this->view)) {
			return;
		}

		// Keep full URL including query string
		$this->view->scriptUrlWithParams = $url;

		// Strip query string for form/action base path
		$param_start = strpos($url, '?');
		$this->view->scriptUrl = ($param_start > 0)
			? substr($url, 0, $param_start)
			: $url;
	}


	/**
	 * Load system config and merge with user config
	 */
	private function loadSystemConfig(): void
	{

		// Base url (overridable via config / config.local.php)
		if (empty($this->config['base_url'])) {
			$this->config['base_url'] = 'https://angebote.paerke.ch/';
		}
		$this->config['base_url'] = rtrim($this->config['base_url'], '/') . '/';

		// Xml import urls
		$this->config['xml_export_url'] = $this->config['base_url'] . "export/";
		$this->config['xml_export_offer_url'] = $this->config['base_url'] . "export/xml/";
		$this->config['xml_export_map_layer_url'] = $this->config['base_url'] . "export/xml/map_layers/";
		$this->config['xml_export_active_offers'] = $this->config['base_url'] . "export/xml/active_offers/";
		$this->config['json_export_target_groups'] = $this->config['base_url'] . "export/json/target_groups";
		$this->config['json_export_categories'] = $this->config['base_url'] . "export/json/categories";
		$this->config['json_export_accessibilities'] = $this->config['base_url'] . "export/json/accessibilities";
		$this->config['json_export_fields_of_activity'] = $this->config['base_url'] . "export/json/fields_of_activity";

		// Sbb link
		$this->config['min_chars_sbb_link'] = 3;

		// Project status
		$this->config['project_status_de'] = [
			1 => 'Geplant',
			2 => 'Laufend',
			3 => 'Abgeschlossen',
		];
		$this->config['project_status_fr'] = [
			1 => 'Planifié',
			2 => 'En cours',
			3 => 'Terminé',
		];
		$this->config['project_status_it'] = [
			1 => 'Pianificato,',
			2 => 'In corso',
			3 => 'Concluso',
		];
		$this->config['project_status_en'] = [
			1 => 'Planned',
			2 => 'Ongoing',
			3 => 'Finished',
		];

		// Parks
		$this->config['parks'] = [
			2  => 'lpb',
			3  => 'snp',
			4  => 'jpa',
			5  => 'prc',
			6  => 'die',
			8  => 'frg',
			9  => 'bvm',
			10 => 'ela',
			11 => 'ube',
			12 => 'npt',
			13 => 'gpe',
			14 => 'wpz',
			15 => 'npb',
			16 => 'prd',
			17 => 'adu',
			18 => 'pnl',
			19 => 'pjv',
			20 => 'npf',
			27 => 'nps',
			28 => 'npn',
			33 => 'sar',
			34 => 'pdj',
			37 => 'wja',
			43 => 'cal',
			48 => 'pvt'
		];

		// Route times
		$this->config['route_times'] = [
			$this->lang->get('offer_time_required_2h'),
			$this->lang->get('offer_time_required_2_4h'),
			$this->lang->get('offer_time_required_4_6h'),
			$this->lang->get('offer_time_required_6h')
		];

		// Route levels
		$this->config['route_levels'] = [
			1 => $this->lang->get('offer_easy'),
			2 => $this->lang->get('offer_average'),
			3 => $this->lang->get('offer_difficult')
		];

		// Hidden categories on overview and detail page
		$this->config['hidden_categories'] = [
			CATEGORY_EVENT,
			CATEGORY_PRODUCT,
			CATEGORY_BOOKING,
			CATEGORY_ACTIVITY,
			CATEGORY_PROJECT,
			CATEGORY_RESEARCH,
			CATEGORY_GASTRONOMY_AND_ACCOMMODATION,
			CATEGORY_REGIONAL_PRODUCT,
			CATEGORY_GASTRONOMY,
			CATEGORY_ACCOMMODATION,
			CATEGORY_SIGHT,
			CATEGORY_SUMMER_ACTIVITIES,
			CATEGORY_WINTER_ACTIVITIES,
			CATEGORY_INFORMATION,
			CATEGORY_INFRASTRUCTURE,
		];

		// Template tags
		$this->config['template_tags'] = [
			'OFFER_TITLE',
			'OFFER_SHORT_INFO',
			'OFFER_CATEGORIES',
			'OFFER_IMAGES',
			'OFFER_ABSTRACT',
			'OFFER_DESCRIPTION',
			'OFFER_ADDITIONAL_INFO',
			'OFFER_DATES',
			'OFFER_DOCUMENTS',
			'OFFER_EVENT_DETAIL',
			'OFFER_PRODUCT_DETAIL',
			'OFFER_BOOKING_DETAIL',
			'OFFER_ACTIVITY_DETAIL',
			'OFFER_LINKS',
			'OFFER_ACCESSIBILITIES',
			'OFFER_TARGET_GROUPS',
			'OFFER_SUPPLIERS',
			'OFFER_INSTITUTION',
			'OFFER_CONTACT',
			'OFFER_POI_LIST',
			'OFFER_ROUTE_LIST',
			'OFFER_EVENT_LOCATION',
			'OFFER_EVENT_LOCATION_SHORT',
			'OFFER_EVENT_LOCATION_DETAILS',
			'OFFER_EVENT_TRANSPORT',
			'OFFER_EVENT_DATE_DETAILS',
			'OFFER_EVENT_PRICE',
			'OFFER_PRODUCT_OPENING_HOURS',
			'OFFER_PRODUCT_PUBLIC_TRANSPORT',
			'OFFER_PRODUCT_PRICE',
			'OFFER_PRODUCT_INFRASTRUCTURE',
			'OFFER_ONLINE_SHOP_CHECKOUT_BUTTON',
			'OFFER_BOOKING_GROUPS',
			'OFFER_BOOKING_TRANSPORT',
			'OFFER_BOOKING_BENEFITS',
			'OFFER_BOOKING_REQUIREMENTS',
			'OFFER_BOOKING_PRICE',
			'OFFER_BOOKING_ACCOMMODATIONS',
			'OFFER_ACTIVITY_ROUTE',
			'OFFER_ACTIVITY_ARRIVAL',
			'OFFER_ACTIVITY_PRICE',
			'OFFER_ACTIVITY_CATERING',
			'OFFER_ACTIVITY_MATERIAL_RENT',
			'OFFER_ACTIVITY_SAFETY_INSTRUCTIONS',
			'OFFER_ACTIVITY_SIGNALIZATION',
			'OFFER_ACTIVITY_DATES',
			'OFFER_ACTIVITY_INFRASTRUCTURE',
			'OFFER_PROJECT_DURATION',
			'OFFER_PROJECT_STATUS',
			'OFFER_SUBSCRIPTION',
			'OFFER_INTERNAL_INFOS',
			'OFFER_PROJECT_DETAIL',
			'OFFER_MAP',
			'OFFER_PRINT_LINK',
			'OFFER_BACK_LINK',
			'OFFER_KEYWORDS',
			'FILTER_TEXT_SEARCH',
			'FILTER_CATEGORIES',
			'FILTER_DATES',
			'FILTER_TARGET_GROUPS',
			'FILTER_FIELDS_OF_ACTIVITY',
			'FILTER_MUNICIPALITIES',
			'FILTER_ACCESSIBILITIES',
			'FILTER_PARKS',
			'FILTER_PROJECT',
			'FILTER_ROUTE_LENGTH',
			'FILTER_ROUTE_TIME',
			'FILTER_ROUTE_CONDITION',
			'FILTER_KEYWORDS',
			'FILTER_SHOW_LINK',
			'FILTER_FORM_START',
			'FILTER_FORM_STOP',
			'FILTER_RESET_BUTTON',
			'FILTER_SUBMIT_BUTTON'
		];

		// Template conditions
		$this->config['template_conditions'] = [
			'OFFER_EVENT',
			'OFFER_PRODUCT',
			'OFFER_BOOKING',
			'OFFER_ACTIVITY',
			'OFFER_PROJECT',
		];
	}



	/**
	 * Load external xml source
	 *
	 * Downloads to a temp file first so curl does not keep a second full copy in memory
	 * alongside SimpleXML (full exports can exceed the default 128M limit).
	 */
	public function loadExternalXml(string $url): SimpleXMLElement|false
	{

		if ($url === '') {
			return false;
		}

		if (PHP_SAPI === 'cli') {
			@ini_set('memory_limit', '512M');
		}

		$tmp_path = tempnam(sys_get_temp_dir(), 'parks-xml-');
		if ($tmp_path === false) {
			return false;
		}

		$fp = fopen($tmp_path, 'w+b');
		if ($fp === false) {
			@unlink($tmp_path);
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);

		$verify_ssl = $this->config['curl_verify_ssl'] ?? true;
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify_ssl ? 2 : 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);

		$ok = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		unset($ch);
		fclose($fp);

		if (($ok === false) || ($curl_errno !== 0) || ($http_code >= 400) || (filesize($tmp_path) === 0)) {
			@unlink($tmp_path);
			return false;
		}

		$xml = simplexml_load_file($tmp_path);
		@unlink($tmp_path);

		return ($xml !== false) ? $xml : false;
	}



	/**
	 * Update stored SDK version after a successful import or migration
	 */
	private function updateApiVersion(): void
	{

		$this->db->update('api', ['version' => SDK_VERSION]);

		if (! empty($this->api)) {
			$this->api->version = SDK_VERSION;
		}

	}



	/**
	 * Output JSON status for update scripts
	 */
	private function outputUpdateJson(bool $status, string $message): void
	{

		echo json_encode([
			'status' => $status,
			'message' => $message,
			'messsage' => $message,
		]);

	}



	/**
	 * Report successful migration to central log server
	 *
	 * Payload must match log_api_migration() on angebote.paerke.ch
	 * (required: api_version, url, hash). Body is signed with HMAC-SHA256
	 * using api_hash (header X-Api-Migration-Signature).
	 */
	public function logMigration(int $version_to = 0): bool
	{

		// Central log endpoint is reached via HTTPS POST
		if (! function_exists('curl_init')) {
			return false;
		}

		// api_hash is required: used as HMAC key and verified server-side against export configs
		$api_hash = (string) ($this->config['api_hash'] ?? '');
		$api_version = (string) ($version_to > 0 ? $version_to : SDK_VERSION);

		// Prefer the current request URL; fall back to hostname when run from CLI
		$site_url = (! empty($_SERVER['HTTP_HOST']) && ! empty($_SERVER['REQUEST_URI']))
			? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			: ((gethostname() ?: 'CLI') . ' (CLI)');

		if ($api_hash === '' || $api_version === '' || $site_url === '') {
			return false;
		}

		// Payload contract must stay in sync with log_api_migration() on angebote.paerke.ch
		$payload = [
			'api_version' => $api_version,
			'php_version' => PHP_VERSION,
			'url' => $site_url,
			'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
			'hash' => $api_hash,
			'park_id' => $this->config['park_id'] ?? '',
			'file_path' => $_SERVER['SCRIPT_FILENAME'] ?? __FILE__,
		];

		// Sign the exact JSON bytes that will be posted (server verifies the same string)
		$body = json_encode($payload);
		if ($body === false) {
			return false;
		}

		$signature = hash_hmac('sha256', $body, $api_hash);
		$url = $this->config['migration_log_url'] ?? 'https://angebote.paerke.ch/migrate/log_api_migration';
		$verify_ssl = $this->config['curl_verify_ssl'] ?? true;

		$ch = curl_init();

		if ($ch === false) {
			return false;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			// Digest of $body with api_hash; must match server-side hash_hmac()
			'X-Api-Migration-Signature: ' . $signature,
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER'] ?? '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify_ssl ? 2 : 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);

		$response = curl_exec($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$success = ($response !== false) && ($http_code >= 200) && ($http_code < 300);

		// Logging failure must not fail the migration itself
		if (! $success) {
			$error = curl_error($ch) ?: ('HTTP ' . $http_code);
			$this->logger->info('Migration log request failed: ' . $error);
		}

		return $success;

	}


}