<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;

use Exception;
use RuntimeException;
use SimpleXMLElement;
use stdClass;


class SwissParksSdk
{


	/**
	 * SDK release version (matches the vX.Y git tag)
	 */
	public const VERSION = '22.0';


	/**
	 * Offer has GINTO accessibility infos
	 */
	public const GINTO_INFOS_AVAILABLE = 1;


	/**
	 * Row from the meta table
	 */
	public ?object $meta = null;


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
	public ?Language $lang = null;


	/**
	 * Model object
	 */
	public ?Model $model = null;


	/**
	 * Import object
	 */
	public ?Import $import = null;


	/**
	 * Database object
	 */
	public ?SQLite $db = null;


	/**
	 * Logger object
	 */
	public ?Log $logger = null;


	/**
	 * Template
	 */
	public array $template = [];


	/**
	 * View object
	 */
	public ?View $view = null;


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
		string $pageUrl = '',
		bool $bootstrapRendering = true,
		bool $bootstrapSetup = true
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

			// Instance of Language
			$this->lang = new Language($language, $this);
			$this->langId = $this->lang->langId;

			// Instance of Log
			$this->logger = new Log($this);

			// Instance of SQLite
			$this->db = new SQLite($this);

			// Instance of Model
			$this->model = new Model($this);

			// Instance of Import
			$this->import = new Import($this);

			// Load system config
			$this->loadSystemConfig();

			if ($bootstrapSetup) {
				$this->setup();
			}

			if ($bootstrapRendering) {

				// Instance of View
				if (class_exists($this->config['class_view'])) {
					$this->view = new $this->config['class_view']($this);
				} else {
					$this->abort('The custom view file does not exist.');
				}

				// Init seo urls
				$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
				$resetSlug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

				// Init session
				if (! empty($this->config['use_sessions'])) {

					// Started here (not on include) so Composer projects do not open a session on every request
					if (session_status() == PHP_SESSION_NONE && ! headers_sent()) {
						session_start();
					}

					$sessionUrl = $pageUrl;

					if ($sessionUrl == '') {

						// Set session name for seo urls
						$sessionUrl = $this->view->scriptUrl;
						if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && ! empty($sessionUrl)) {

							// Clean url from double slashes
							$sessionUrl = str_replace('//', '/', $sessionUrl);

							// Clean url from page param
							$sessionUrl = preg_replace('/\/' . $pageSlug . '\/(\d*)/m', '', $sessionUrl);

							// Clean url from reset param
							$sessionUrl = str_replace('/' . $resetSlug, '', $sessionUrl);

							// Remove last slash
							$sessionUrl = rtrim($sessionUrl, '/');

						}
						
					}

					// Set session
					$this->sessionName = $this->config['session_name'] . '_' . md5($sessionUrl ?? 'default');

					// Get favorites
					$this->favoritesCookieName = $this->config['session_name'] . '_favorites';
					if (! empty($_COOKIE[$this->favoritesCookieName])) {
						$this->favorites = Favorites::parseCookie((string) $_COOKIE[$this->favoritesCookieName]);
					}

				}

				// Init reset filter
				$resetFilter = false;

				// Reset filter with seo urls
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
					if (! empty($this->view->scriptUrl) && strstr($this->view->scriptUrl, '/' . $resetSlug)) {
						$resetFilter = true;
					}
				}

				// Reset filter with default urls
				elseif (isset($_GET[$this->config['url_param_prefix'] . 'reset'])) {
					$resetFilter = true;
				}

				// Reset or init filter
				if ($resetFilter === true) {
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

		try {

			// Get import urls
			$xml = $this->config['xml_export_offer_url'] . $this->hash;
			$xmlMapLayer = $this->config['xml_export_map_layer_url'] . $this->hash;
			$xmlActiveOffers = $this->config['xml_export_active_offers'] . $this->hash;

			// Import data from XML into database
			$importOk = $this->import->import($xml, $force);
			$mapLayersOk = $this->import->importMapLayers($xmlMapLayer);
			$cleanupOk = $this->import->cleanUpOffers($xmlActiveOffers);

			if ($importOk) {
				$this->updateMetaVersion();

				// Offer total for the summary line (not the per-run imported count)
				$offerTotal = 0;
				$offerCountResult = $this->db->query('SELECT COUNT(*) AS total FROM offer');
				if ($offerCountResult !== false) {
					$offerCountRow = $offerCountResult->fetchAssoc();
					$offerTotal = (int) ($offerCountRow['total'] ?? 0);
				}

				$details = [
					'mode=' . ($this->import->lastImportForced ? 'full' : 'incremental'),
					'imported=' . $this->import->lastImportedOffers,
					'deleted=' . $this->import->lastDeletedOffers,
					'offers=' . $offerTotal,
					'map_layers=' . ($mapLayersOk ? (string) $this->import->lastImportedMapLayers : 'fail'),
					'cleanup=' . ($cleanupOk ? (string) $this->import->lastCleanupDeletedOffers : 'fail'),
				];
				$this->outputCliStatus('sync', true, implode(', ', $details));

				return true;
			}

			$this->outputCliStatus('sync', false, 'Offer import failed. Check logs and api_hash configuration.');

			return false;


		} catch (Exception $e) {

			$this->outputCliStatus('sync', false, 'ADB sync exception: ' . $e->getMessage());

			return false;

		}

	}



	/**
	 * Migrate database updates
	 * Rebuilds the database from scratch and runs a full import
	 */
	public function migrate(): bool
	{

		// Read before recreate() deletes the database, so the migration log can report the version jump
		$previousVersion = (string) ($this->getStoredMeta()->version ?? '');

		// Drop and recreate database file with current schema
		$this->db->recreate();

		// Re-run setup (triggers a full import)
		$this->meta = null;
		$this->setup();

		$success = ! empty($this->meta->initialized);

		if ($success) {

			// Offer total for the summary line (same pattern as update())
			$offerTotal = 0;
			$offerCountResult = $this->db->query('SELECT COUNT(*) AS total FROM offer');
			if ($offerCountResult !== false) {
				$offerCountRow = $offerCountResult->fetchAssoc();
				$offerTotal = (int) ($offerCountRow['total'] ?? 0);
			}

			$migrationDetails = [
				'previous_version' => $previousVersion,
				'imported_offers' => $this->import->lastImportedOffers,
				'offers' => $offerTotal,
			];
			if (! $this->logMigration($migrationDetails)) {
				$this->logger->info('Migration log could not be sent to the central server.');
			}

			$details = [
				'sdk_version=' . self::VERSION,
				'imported=' . $this->import->lastImportedOffers,
				'offers=' . $offerTotal,
				'map_layers=' . $this->import->lastImportedMapLayers,
			];
			$this->outputCliStatus('migrate', true, implode(', ', $details));

		} else {
			$this->outputCliStatus('migrate', false, 'Import failed. Check logs and api_hash configuration.');
		}

		return $success;

	}



	/**
	 * Whether the database is missing, not imported yet or older than VERSION
	 */
	public function isMigrationNeeded(): bool
	{

		$meta = $this->getStoredMeta();

		return empty($meta->initialized) || version_compare(self::VERSION, (string) $meta->version, '>');
	}



	/**
	 * Meta row as stored in the database (null when the database has none yet)
	 */
	private function getStoredMeta(): ?object
	{

		$qMeta = $this->db->get('meta');

		return ($qMeta && $qMeta->numRows > 0) ? $qMeta->fetchObject() : null;
	}



	/**
	 * Load template
	 */
	public function loadTemplate(): void
	{

		// Absolute paths point to an own template folder outside the SDK (e.g. outside vendor/)
		$templateDir = $this->config['template_folder'];
		if (substr($templateDir, 0, 1) !== '/') {
			$templateDir = __DIR__ . '/../templates/' . $templateDir;
		}

		// Load detail template
		if (file_exists($templateDir . '/detail.tpl')) {
			$this->template['detail'] = file_get_contents($templateDir . '/detail.tpl');
			$this->template['detail'] = preg_replace("/<!--(.*?)-->/s", "", $this->template['detail']);
		}

		// Load filter template
		if (file_exists($templateDir . '/filter.tpl')) {
			$this->template['filter'] = file_get_contents($templateDir . '/filter.tpl');
			$this->template['filter'] = preg_replace("/<!--(.*?)-->/s", "", $this->template['filter']);
		}
	}



	/**
	 * Compile template
	 */
	public function compileTemplate(string $section, array $templateData = [], array $conditions = []): string
	{
		if (! empty($section)) {

			// Load section
			$return = $this->template[$section];

			// Compile template main category conditions
			foreach ($this->config['template_conditions'] as $conditionTag) {
				preg_match_all("/\[" . $conditionTag . "\@start\](.*?)\[" . $conditionTag . "\@stop\]/s", $return, $conditionFound);
				if (! empty($conditionFound[0])) {
					foreach ($conditionFound[0] as $conditionFound) {
						if (array_key_exists($conditionTag, $conditions) && ! empty($conditions[$conditionTag])) {
							$removeCondition = $conditionFound;
							$removeCondition = str_replace("[" . $conditionTag . "@start]", '', $removeCondition);
							$removeCondition = str_replace("[" . $conditionTag . "@stop]", '', $removeCondition);
							$return = str_replace($conditionFound, $removeCondition, $return);
						} else {
							$return = str_replace($conditionFound, '', $return);
						}
					}
				}
			}

			// Compile tags
			foreach ($this->config['template_tags'] as $tag) {

				// ISSET
				preg_match_all("/\[ISSET\(" . $tag . "\)\@start\](.*?)\[ISSET\(" . $tag . "\)@stop\]/s", $return, $conditionFound);
				if (! empty($conditionFound[0])) {
					foreach ($conditionFound[0] as $conditionFound) {
						if (array_key_exists($tag, $templateData) && ! empty($templateData[$tag])) {
							$removeCondition = $conditionFound;
							$removeCondition = str_replace("[ISSET(" . $tag . ")@start]", '', $removeCondition);
							$removeCondition = str_replace("[ISSET(" . $tag . ")@stop]", '', $removeCondition);
							$return = str_replace($conditionFound, $removeCondition, $return);
						} else {
							$return = str_replace($conditionFound, '', $return);
						}
					}
				}

				// NOTISSET
				preg_match_all("/\[NOTISSET\(" . $tag . "\)\@start\](.*?)\[NOTISSET\(" . $tag . "\)@stop\]/s", $return, $conditionFound);
				if (! empty($conditionFound[0])) {
					foreach ($conditionFound[0] as $conditionFound) {
						if (!array_key_exists($tag, $templateData) || empty($templateData[$tag])) {
							$removeCondition = $conditionFound;
							$removeCondition = str_replace("[NOTISSET(" . $tag . ")@start]", '', $removeCondition);
							$removeCondition = str_replace("[NOTISSET(" . $tag . ")@stop]", '', $removeCondition);
							$return = str_replace($conditionFound, $removeCondition, $return);
						} else {
							$return = str_replace($conditionFound, '', $return);
						}
					}
				}
			}

			// Compile language labels
			preg_match_all("/__LANG\[(.+)\]__/i", $return, $langVars);
			if (! empty($langVars[1])) {
				foreach ($langVars[1] as $langLabel) {
					$langContent = $this->lang->get($langLabel);
					if ($langContent == $langLabel) {
						$langContent = '';
					}
					$return = str_replace('__LANG[' . $langLabel . ']__', $langContent, $return);
				}
			}

			// Compile template tags
			if (! empty($this->config['template_tags'])) {
				foreach ($this->config['template_tags'] as $tag) {
					$tagContent = '';
					if (array_key_exists($tag, $templateData) && ! empty($templateData[$tag])) {
						$tagContent = $templateData[$tag];
					}
					$return = str_replace('__' . $tag . '__', $tagContent, $return);
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
			$eventCount = 0;
			$bookingCount = 0;
			$activityCount = 0;
			$productCount = 0;
			$projectsCount = 0;
			$flatCategories = [];
			$users = [];

			$filterForMeta = $this->normalizeOffersFilter($parkId, $categories, $filter, true);
			$filterMeta = $this->model->getFilterMeta($filterForMeta);
			if (! empty($filterMeta)) {
				$eventCount = $filterMeta['event_count'];
				$bookingCount = $filterMeta['booking_count'];
				$activityCount = $filterMeta['activity_count'];
				$productCount = $filterMeta['product_count'];
				$projectsCount = $filterMeta['project_count'];
				$flatCategories = $filterMeta['category_ids'];
				if (empty($this->parkId) && ($this->filterHideUsers == false)) {
					$users = $filterMeta['parks'];
				}
			}

			// Categories has to be an array
			$categories = is_array($flatCategories) ? $flatCategories : [$flatCategories];

			// Check, if categories are not empty
			$first = reset($categories);
			if (count($categories) == 0 || (count($categories) == 1 && empty($first))) {
				$categories = false;
			}

			// Group and flatten categories for select field
			$categories = $this->groupCategoriesByParent($categories);
			$categories = $this->flattenCategoriesForSelect($categories);

			// Check if event filter (date-from, date-to) should be displayed
			$showEventFilter = false;
			if ($eventCount > 0) {
				$showEventFilter = true;
			}

			// Check if route filter should be displayed (has enough routes, and is activated)
			$showRouteFilter = false;
			if (($this->config['show_route_filter'] == true) && ($activityCount > 0)) {
				$showRouteFilter = true;
			}

			// Check if project filter should be displayed (has enough projects, and is activated)
			$showProjectFilter = false;
			if ($projectsCount > 0) {
				$showProjectFilter = true;
			}

			// Check if only projects exists in export
			$projectsOnly = false;
			if ($projectsCount > 0) {
				if (($eventCount == 0) && ($bookingCount == 0) && ($activityCount == 0) && ($productCount == 0)) {
					$projectsOnly = true;
				}
			}

			// Load view
			$params = [
				'categories' => $categories,
				'selected' => $this->filterData,
				'filter' => $this->filter,
				'users' => $users ?? [],
				'show_event_filter' => $showEventFilter,
				'show_route_filter' => $showRouteFilter,
				'show_target_group_filter' => $this->config['show_target_group_filter'],
				'show_fields_of_activity_filter' => $this->config['show_fields_of_activity_filter'],
				'show_accessibility_filter' => $this->config['show_accessibility_filter'],
				'show_project_filter' => $showProjectFilter,
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
			$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
			if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $pageSlug . '/')) {

				// Split url by slashes
				$seoUrl = explode('/', $this->view->scriptUrl);

				// Get page id
				$pageSegmentPos = count($seoUrl) - 1;
				$this->page = intval($seoUrl[$pageSegmentPos]);
			}

			// Default urls
			else {
				$paramName = $this->config['url_param_prefix'] . 'page';
				$formParamName = $this->config['form_prefix'] . 'page';

				// Prefer url_param_prefix
				if (isset($_GET[$paramName]) && $_GET[$paramName] >= 1) {
					$this->page = $_GET[$paramName];
				}

				// Fallback for older form_prefix bookmarks
				elseif (
					$formParamName !== $paramName
					&& isset($_GET[$formParamName])
					&& $_GET[$formParamName] >= 1
				) {
					$this->page = $_GET[$formParamName];
				}
			}

			// Special case: exclude offers from user SAJA in offers list on parks.swiss
			if (empty($parkId) && strstr($_SERVER['HTTP_HOST'], 'parks.swiss')) {
				$filter['exclude_park_ids'] = [37];
			}

			// Filter keywords by param
			if ((isset($_REQUEST['keyword']) || ! empty($_SESSION[$this->sessionName]['keyword'])) && ! empty($this->config['keyword_filter'][$this->langId])) {

				$getKeyword = '';

				// Save selected keyword into session
				if (isset($_REQUEST['keyword'])) {
					$_SESSION[$this->sessionName]['keyword'] = $_REQUEST['keyword'];
					$getKeyword = $_REQUEST['keyword'];
				}

				// Get selected keyword from session
				else if (! empty($_SESSION[$this->sessionName]['keyword'])) {
					$getKeyword = $_SESSION[$this->sessionName]['keyword'];
				}

				// Prepare keyword, replace special chars
				$getKeyword = str_replace("---", "*", $getKeyword);
				$getKeyword = str_replace("-_-", "/", $getKeyword);

				// Check keyword
				if (in_array($getKeyword, $this->config['keyword_filter'][$this->langId])) {
					$filter['keywords'] = $getKeyword;
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
			return $this->loadMapAssets() . $this->view->getOverviewMap($offers['data'] ?? []);
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
	public function showOfferDetail(?int $singleOfferId = null): string
	{

		if ($this->isOfferDetail() || ! empty($singleOfferId)) {

			// Init
			$originalOfferId = 0;
			$paramName = $this->config['url_param_prefix'] . 'offer';
			$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';

			// Single offer mode
			if (! empty($singleOfferId) && (intval($singleOfferId) > 0)) {
				$this->singleMode = true;
				$offerId = intval($singleOfferId);
			}
			// Default mode
			else {

				// Handle seo urls
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $detailSlug . '/')) {
					$parsedDetail = $this->parseOfferDetailFromUrl($this->view->scriptUrl);
					$offerId = $parsedDetail['offer_id'];
					$originalOfferId = $parsedDetail['poi_id'];
				}

				// Default urls
				else {
					$offerId = intval($_GET[$paramName]);
				}
			}

			// Check offer id
			if (! empty($offerId)) {

				// Get offer
				$offer = $this->model->getOffer($offerId);
				if (! empty($offer)) {

					// Set selected park
					$this->setSelectedPark($offer->park_id);

					// Load view
					$return = $this->loadMapAssets();
					$return .= $this->view->detail($offer, $originalOfferId);

					return $return;
					
				} else {

					// Soft errors: let CMS decide how to render missing offers
					if (! empty($this->config['soft_errors'])) {
						return '';
					}

					$this->sendOfferDetailNotFound();
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
	public function toggleFavorite(int $offerId): bool
	{
		if (($offerId > 0) && ($this->config['use_sessions'] == true)) {

			// Remove offer from favorites
			if (in_array($offerId, $this->favorites)) {
				unset($this->favorites[$offerId]);
				$return = false;
			}

			// Add new favorite
			else {
				$this->favorites[$offerId] = $offerId;
				$return = true;
			}

			$this->saveFavoritesCookie($this->favorites);

			return $return;
		}

		return false;
	}



	/**
	 * Empty favorites
	 */
	public function cleanFavorites(): void
	{

		$this->favorites = [];
		$this->saveFavoritesCookie([]);

	}



	/**
	 * Persist favorites cookie as JSON
	 */
	private function saveFavoritesCookie(array $favorites): void
	{

		$empty = empty($favorites);
		setcookie(
			$this->favoritesCookieName,
			$empty ? '' : Favorites::encodeCookie($favorites),
			Favorites::cookieOptions($empty, Favorites::requestIsSecure())
		);
	}



	/**
	 * Returns if detail page should be displayed
	 */
	public function isOfferDetail(): bool
	{

		// Init
		$isOfferDetail = false;
		$paramName = $this->config['url_param_prefix'] . 'offer';
		$offerId = ! empty($_GET[$paramName]) ? intval($_GET[$paramName]) : null;
		$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';

		// Check seo url
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) && strstr($this->view->scriptUrl, '/' . $detailSlug . '/')) {
			$isOfferDetail = true;
		} elseif (($offerId > 0) && $this->model->offerExists($offerId)) {
			$isOfferDetail = true;
		}

		return $isOfferDetail;
	}



	/**
	 * Redirect SEO detail URLs to their canonical path (301) and answer unknown offers with 404
	 * Call before any output; returns false only for unknown offers with soft_errors
	 */
	public function enforceOfferDetailCanonicalUrl(string $pageBaseUrl = ''): bool
	{

		$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
		$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';

		if (empty($this->config['seo_urls']) || ($this->config['seo_urls'] !== true) || ! strstr($this->view->scriptUrl, '/' . $detailSlug . '/')) {
			return true;
		}

		$parsedDetail = $this->parseOfferDetailFromUrl($this->view->scriptUrl);
		$offer = ($parsedDetail['offer_id'] > 0) ? $this->model->getOffer($parsedDetail['offer_id'], true) : false;
		if (empty($offer) || empty($offer->title)) {
			return $this->sendOfferDetailNotFound();
		}

		$currentPath = $this->normalizeUrlPath($this->view->scriptUrl);

		// Trusted page URL from the CMS, so junk prefixes never become canonical
		if ($pageBaseUrl !== '') {
			$basePath = $this->normalizeUrlPath($pageBaseUrl);
		}

		// Fallback: current path without pagination and everything from the detail segment on
		else {
			$basePath = preg_replace('/\/' . preg_quote($pageSlug, '/') . '\/\d+/', '', $currentPath);
			$basePath = (string) strstr($basePath, '/' . $detailSlug . '/', true);
		}

		// Base path is already normalized; re-parsing the decoded path would cut it at a literal "?" or "#"
		$canonicalPath = $basePath . $this->view->getSeoDetailUrl($parsedDetail['offer_id'], $offer->title, $parsedDetail['poi_id'] ?: null, false);
		if ($currentPath === $canonicalPath) {
			return true;
		}

		// Encode each segment so the Location stays a relative path without "\", control chars or a "//host" prefix
		$location = implode('/', array_map('rawurlencode', explode('/', $canonicalPath)));
		if ($this->sendHeader('Location: ' . $location, 301)) {
			$this->terminate();
		}

		return true;
	}



	/**
	 * Extract offer id and optional POI id from an SEO detail URL
	 */
	private function parseOfferDetailFromUrl(string $url): array
	{

		$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
		$poiSlug = ! empty($this->config['seo_url_poi_slug']) ? $this->config['seo_url_poi_slug'] : '';
		$poiId = 0;

		$path = $this->normalizeUrlPath($url);
		if (! strstr($path, '/' . $detailSlug . '/')) {
			return ['offer_id' => 0, 'poi_id' => 0];
		}

		// POI URLs end with /{poi_slug}/{id} after the offer segment
		$segments = explode('/', $path);
		if (in_array($poiSlug, $segments, true)) {
			$poiId = intval(end($segments));
			$segments = array_slice($segments, 0, -2);
		}

		// Offer id is the number after the last dash of the title slug
		$titleSlug = explode('-', (string) end($segments));

		return ['offer_id' => intval(end($titleSlug)), 'poi_id' => $poiId];
	}



	/**
	 * Get the decoded URL path with single slashes and without trailing slash
	 */
	private function normalizeUrlPath(string $url): string
	{

		$path = parse_url($url, PHP_URL_PATH);

		// Malformed URLs such as "///host/path" make parse_url fail
		if ($path === false) {
			$path = strtok((string) strtok($url, '?'), '&');
		}

		// Collapse slashes after decoding so "%2F%2F" or "///" cannot form a "//host" prefix
		$path = preg_replace('#/+#', '/', '/' . rawurldecode((string) $path));

		return rtrim($path, '/');
	}



	/**
	 * Answer an unknown offer with 404
	 * Prints a plain page and exits by default; with soft_errors returns false so the CMS can render its own 404 page
	 */
	private function sendOfferDetailNotFound(): bool
	{

		$this->sendHeader('HTTP/1.0 404 Not Found', 404);
		if (! empty($this->config['soft_errors'])) {
			return false;
		}

		$this->terminate('<h1>404 Not Found</h1> The page that you have requested could not be found.');
	}



	/**
	 * Send an HTTP header unless output has already started
	 */
	protected function sendHeader(string $header, int $statusCode): bool
	{

		if (headers_sent()) {
			return false;
		}

		header($header, true, $statusCode);

		return true;
	}



	/**
	 * Print output and end the request
	 */
	protected function terminate(string $output = ''): never
	{

		echo $output;
		exit();
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
		bool $ignoreFilter = false,
		bool $returnMinimal = false,
		bool $mapMode = false
	): array {

		$filter = $this->normalizeOffersFilter($parkId, $categories, $filter, $ignoreFilter);

		$offset = 0;
		if (! empty($page) && is_numeric($page)) {
			$offset = ($page - 1) * $this->config['offers_per_page'];
		}

		$result = $this->model->filterOffers($filter, $limit, $offset, $returnMinimal, $mapMode);

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
	): SQLiteResult|false {

		$filter = $this->normalizeOffersFilter($parkId, $categories, $filter, true);

		return $this->model->getFilterCategories($filter);
	}



	/**
	 * Get offer details
	 */
	public function getOfferDetail(int $offerId): object|false
	{
		return $this->model->getOffer($offerId);
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

		$qMeta = $this->db->get('meta');

		if ($qMeta) {
			if ($qMeta->numRows > 0) {
				$this->meta = $qMeta->fetchObject();
			} else {

				// First boot: create the meta row so later requests can detect init state
				$this->meta = (object) [
					'initialized' => 0,
					'version' => self::VERSION,
				];

				$this->db->insert('meta', (array)$this->meta);
			}

			// Schema may already be newer than the stored version; migrate is a separate step
			if (version_compare((string) self::VERSION, (string) $this->meta->version, '>')) {
				$this->logger->info('The current SDK version is out of date. Please run: bash bin/migrate.sh');
			}

			// Without an API hash there is nothing to fetch; skip instead of failing on every request
			if (! $this->meta->initialized && ! empty($this->hash)) {

				// First successful import marks the SDK as initialized
				$xml = $this->config['xml_export_offer_url'] . $this->hash;
				$xmlMapLayer =  $this->config['xml_export_map_layer_url'] . $this->hash;

				$importOk = $this->import->import($xml);
				$this->import->importMapLayers($xmlMapLayer);

				if ($importOk) {
					$this->model->reloadTargetGroups();
					$this->model->reloadFieldsOfActivity();
					$this->model->reloadCategories();

					$this->meta->initialized = 1;
					$this->db->update('meta', [
						'initialized' => 1,
						'version' => self::VERSION,
					]);
					$this->meta->version = self::VERSION;
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
	private function setSelectedPark(?int $parkId = null): void
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
	 * Init filter
	 */
	private function initFilter(): void
	{

		// Init
		$paramName = $this->config['form_prefix'] . 'filter';
		$postData = isset($_POST[$paramName]) ? $_POST[$paramName] : null;

		// Prepare URL params
		$getParams = false;
		$allowedGetParams = ['categories', 'target_groups', 'fields_of_activity', 'accessibilities'];
		foreach ($allowedGetParams as $param) {
			if (! empty($_GET[$param])) {
				$getParams = true;
			}
		}

		// Set filter by all types
		$fields = ['categories', 'date_from', 'date_to', 'search', 'park_id', 'time_required', 'level_technics', 'level_condition', 'route_length_min', 'route_length_max', 'project_status', 'target_groups', 'fields_of_activity', 'municipalities', 'accessibilities'];
		foreach ($fields as $field) {

			// Get categories from URL
			if (empty($postData) && ($getParams === true) && in_array($field, $allowedGetParams)) {
				foreach ($allowedGetParams as $param) {
					if (! empty($_GET[$param]) && is_string($_GET[$param])) {
						$getValues = $this->sanitizeFilterValue($param, explode(',', $_GET[$param]));

						// Set filter by params
						if (! empty($getValues)) {
							$this->filter = true;
							$this->filterData[$param] = $getValues;
						}
					}
				}
			}

			// Post data
			elseif (! empty($postData)) {
				$this->filter = true;
				$this->filterData[$field] = isset($postData[$field]) ? $this->sanitizeFilterValue($field, $postData[$field]) : null;
			}

			// Session data (sanitized again, it may stem from requests before this check existed)
			elseif ($this->config['use_sessions'] && (isset($_SESSION[$this->sessionName]['filter'][$field]) && ! empty($_SESSION[$this->sessionName]['filter'][$field]))) {
				$this->filter = true;
				$this->filterData[$field] = $this->sanitizeFilterValue($field, $_SESSION[$this->sessionName]['filter'][$field]);
			}
		}

		// Set filter in session
		if ($this->config['use_sessions']) {
			$_SESSION[$this->sessionName]['filter'] = $this->filterData;
		}

		// Reload page
		if (! empty($postData) || ($getParams === true)) {
			$this->reloadPage();
		}
	}



	/**
	 * Normalize a visitor filter value (POST or session) to the type the model expects; null drops it
	 */
	private function sanitizeFilterValue(string $field, mixed $value): mixed
	{

		switch ($field) {

			// Checkbox ID lists: positive integers only, always an array for the filter view
			case 'categories':
			case 'park_id':
			case 'target_groups':
			case 'fields_of_activity':
			case 'municipalities':
			case 'level_technics':
			case 'level_condition':
			case 'project_status':
				$ids = array_values(array_filter(array_map('intval', array_filter((array) $value, 'is_scalar')), fn($id) => $id > 0));
				return ! empty($ids) ? $ids : null;

			// Accessibility options are icon URLs (or self::GINTO_INFOS_AVAILABLE), not IDs; the model escapes them
			case 'accessibilities':
				$values = array_values(array_filter(array_map('strval', array_filter((array) $value, 'is_scalar')), fn($option) => $option !== ''));
				return ! empty($values) ? $values : null;

			case 'route_length_min':
			case 'route_length_max':
				return is_scalar($value) ? intval($value) : null;

			// Same parser the model uses, so only dates it can handle are kept
			case 'date_from':
			case 'date_to':
				return (is_string($value) && (date_create($value) !== false)) ? $value : null;

			// Options are rendered decoded from route_times, so compare against the decoded labels
			case 'time_required':
				$allowed = array_map('html_entity_decode', $this->config['route_times'] ?? []);
				$values = array_values(array_intersect(array_filter((array) $value, 'is_scalar'), $allowed));
				return ! empty($values) ? $values : null;

			case 'search':
				return is_scalar($value) ? mb_substr((string) $value, 0, 200) : null;
		}

		return null;
	}



	/**
	 * Reset filter
	 */
	private function resetFilter(): void
	{
		$this->filterData = [];
		if ($this->config['use_sessions']) {
			unset($_SESSION[$this->sessionName]['filter']);
		}
	}



	/**
	 * Reload page
	 */
	protected function reloadPage(bool $resetMode = false): never
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
			if ($resetMode === true) {
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
		array $additionalFilter = [],
		bool $ignoreFilter = false
	): array {

		if (empty($parkId)) {
			$parkId = $this->parkId;
		}

		$filter = [];

		if ($ignoreFilter == false) {
			foreach ($this->filterData as $field => $value) {
				if (! empty($value)) {
					$filter[$field] = $value;
				}
			}
		}
		if (! empty($categories) && ! isset($filter['categories'])) {
			$filter['categories'] = $categories;
		}

		// Offer settings
		if (! empty($additionalFilter['offer_settings'])) {
			$filter['offer_settings'] = $additionalFilter['offer_settings'];
		}

		// Target groups
		if (! empty($additionalFilter['target_groups'])) {
			$filter['target_groups'] = $additionalFilter['target_groups'];
		}

		// Fields of activity
		if (! empty($additionalFilter['fields_of_activity'])) {
			$filter['fields_of_activity'] = $additionalFilter['fields_of_activity'];
		}

		// Accessibilities
		if (! empty($additionalFilter['accessibilities'])) {
			$filter['accessibilities'] = $additionalFilter['accessibilities'];
		}

		// Search words
		if (! empty($additionalFilter['search'])) {
			$filter['search'] = $additionalFilter['search'];
		}

		// Exclude park ids
		if (! empty($additionalFilter['exclude_park_ids']) && is_array($additionalFilter['exclude_park_ids'])) {
			$filter['exclude_park_ids'] = $additionalFilter['exclude_park_ids'];
		}

		// Order_by
		if (! empty($additionalFilter['order_by'])) {
			$filter['order_by'] = $additionalFilter['order_by'];
		}

		// TextOfferSearch
		if (! empty($additionalFilter['textOfferSearch'])) {
			$filter['search'] .= $additionalFilter['textOfferSearch'];
		}

		// Online_shop_enabled
		if (! empty($additionalFilter['online_shop_enabled'])) {
			$filter['online_shop_enabled'] = $additionalFilter['online_shop_enabled'];
		}

		// Offers_barrier_free
		if (! empty($additionalFilter['offers_barrier_free'])) {
			$filter['barrier_free'] = $additionalFilter['offers_barrier_free'];
		}

		// Offers_learning_opportunity
		if (! empty($additionalFilter['offers_learning_opportunity'])) {
			$filter['learning_opportunity'] = $additionalFilter['offers_learning_opportunity'];
		}

		// Offers_child_friendly
		if (! empty($additionalFilter['offers_child_friendly'])) {
			$filter['child_friendly'] = $additionalFilter['offers_child_friendly'];
		}

		// Offer_filter_hints
		if (isset($additionalFilter['offers_filter_hints'])) {
			$filter['is_hint'] = $additionalFilter['offers_filter_hints'];
		}

		// Keywords
		if (isset($additionalFilter['keywords'])) {
			$filter['keywords'] = $additionalFilter['keywords'];
		}

		// Contact_is_park_partner
		if (isset($additionalFilter['contact_is_park_partner'])) {
			$filter['contact_is_park_partner'] = $additionalFilter['contact_is_park_partner'];
		}

		// Is_park_event
		if (isset($additionalFilter['offers_is_park_event'])) {
			$filter['offers_is_park_event'] = $additionalFilter['offers_is_park_event'];
		}

		// Municipality
		if (! empty($additionalFilter['municipalities'])) {
			$filter['municipalities'] = $additionalFilter['municipalities'];
		}

		// Has accessibility informations
		if (isset($additionalFilter['has_accessibility_informations'])) {
			$filter['has_accessibility_informations'] = $additionalFilter['has_accessibility_informations'];
		}

		// Offers
		if (isset($additionalFilter['offers'])) {
			$filter['offers'] = $additionalFilter['offers'];
		}

		// Offer restrictions
		if (! empty($additionalFilter['offerRestriction']) && ($additionalFilter['offerRestriction'] == 'today')) {
			$filter['offers_of_today'] = true;
		}

		// Park id
		if (! empty($parkId)) {
			$filter['park_id'] = $parkId;
		}

		return $filter;
	}



	/**
	 * Load map CSS/JS assets
	 */
	private function loadMapAssets(): ?string
	{
		if ($this->config['prevent_css_js_include'] == false) {

			$mapUrl = rtrim($this->config['interactive_map_url'] ?? 'https://map.parks.swiss', '/');
			$output = '
				<link rel="stylesheet" href="' . $mapUrl . '/dist/css/style.css" />
				<script type="module" src="' . $mapUrl . '/dist/js/swiss-parks-map.umd.js"></script>
			';

			return $this->output($output);
		}

		return '';
	}



	/**
	 * Flatten category tree for select-field options
	 */
	private function flattenCategoriesForSelect(array $categories, int $index = 0, int $level = 0): array
	{

		$return = [];

		// Detect the top-level root category for special Product handling below
		$mainCategory = [];
		if (isset($categories[0]) && is_array($categories[0])) {
			$mainCategory = array_keys($categories[0]);
			$mainCategory = reset($mainCategory);
		}

		// Product-only trees start under Gastronomy-and-Accommodation for the select UI
		if (($level == 0) && ($index == 0) && ($mainCategory == Category::PRODUCT) && isset($categories[Category::GASTRONOMY_AND_ACCOMMODATION]) && (count($categories[Category::PRODUCT]) == 1)) {
			$index = Category::GASTRONOMY_AND_ACCOMMODATION;
		}

		if (isset($categories[$index]) && ! empty($categories[$index])) {
			foreach ($categories[$index] as $id => $category) {

				// Level rules: skip intermediate Activity/Product/Gastronomy nodes in the flat list
				if (
					(($level != 0) || in_array($id, [Category::ACTIVITY, Category::PRODUCT]))
					&&
					(($level != 1) || ! in_array($index, [Category::ACTIVITY, Category::PRODUCT]))
					&&
					(($level != 2) || ! in_array($index, [Category::GASTRONOMY_AND_ACCOMMODATION]))
				) {
					$return[$id] = $category;
				}

				if (isset($categories[$id])) {
					$childs = $this->flattenCategoriesForSelect($categories, $id, $level + 1);
					if (! empty($childs)) {

						// Nest children under a label for Activity/Product/Gastronomy branches; otherwise merge flat
						if (
							(($level == 0) && ! in_array($id, [Category::ACTIVITY, Category::PRODUCT]))
							||
							(($level == 1) && in_array($index, [Category::ACTIVITY, Category::PRODUCT]))
							||
							(($level == 2) && in_array($index, [Category::GASTRONOMY_AND_ACCOMMODATION]))
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
	private function groupCategoriesByParent(array|false $categories = false): array
	{
		$return = [];
		$parents = [];

		// Get all categories
		$allCategories = $this->model->getAllCategories();

		// Get all parents
		if (is_array($categories) && ! empty($categories)) {
			foreach ($categories as $categoryId) {
				if (($categoryId > 0) && isset($allCategories[$categoryId]) && ! empty($allCategories[$categoryId])) {
					$parents = array_merge($parents, $allCategories[$categoryId]->parents);
				}
			}
		}

		// Return structured categories
		if (is_array($allCategories) && ! empty($allCategories)) {
			foreach ($allCategories as $categoryId => $category) {
				// Set parent id
				$parentId = $category->parent_id ? $category->parent_id : 0;

				// Add category
				if (($categories == false) || (in_array($categoryId, $categories) || in_array($categoryId, $parents))) {
					$return[$parentId][$categoryId] = $category->body;
				}
			}
		}

		// Return flat (non structured) categories, empty array before the first import
		else {
			$return = is_array($categories) ? $categories : [];
		}

		return $return;
	}



	/**
	 * Security checks
	 */
	private function securityChecks(): void
	{

		// Nothing to sanitize
		if (empty($_GET) || ! is_array($_GET)) {
			return;
		}

		// Prefixes used by SDK query/form params
		$urlPrefix = ! empty($this->config['url_param_prefix']) ? (string) $this->config['url_param_prefix'] : '';
		$formPrefix = ! empty($this->config['form_prefix']) ? (string) $this->config['form_prefix'] : '';

		// Known unprefixed SDK keys (CMS query vars stay untouched)
		$knownKeys = [
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
			$keyString = (string) $key;
			$isSdkKey = in_array($keyString, $knownKeys, true)
				|| ($urlPrefix !== '' && str_starts_with($keyString, $urlPrefix))
				|| ($formPrefix !== '' && str_starts_with($keyString, $formPrefix));

			if (! $isSdkKey) {
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
	 * Get SDK configuration: defaults, then config.local.php, then the project config
	 */
	private function getConfig(): array
	{

		$configDir = __DIR__ . '/../config';
		$files = [$configDir . '/config.php'];
		if (file_exists($configDir . '/config.local.php')) {
			$files[] = $configDir . '/config.local.php';
		}

		// Project config for Composer installations (constant wins over environment variable)
		$projectConfig = defined('SWISS_PARKS_SDK_CONFIG') ? (string) SWISS_PARKS_SDK_CONFIG : (string) getenv('SWISS_PARKS_SDK_CONFIG');
		if ($projectConfig !== '') {
			$files[] = $projectConfig;
		}

		// Each file returns only its own keys; later files replace whole keys
		$config = [];
		foreach ($files as $file) {
			$values = is_file($file) ? require $file : null;
			if (! is_array($values)) {

				// $this->config is still empty here, so honour soft_errors from what is loaded so far
				$this->config = $config;
				$this->abort('The configuration file ' . $file . ' does not exist or does not return an array.');
			}
			$config = array_replace($config, $values);
		}

		return $config;
	}



	/**
	 * Use the project config from a --config=<path> CLI argument
	 */
	public static function useConfigFromArgv(array $argv): void
	{

		foreach ($argv as $arg) {
			if (str_starts_with($arg, '--config=') && ! defined('SWISS_PARKS_SDK_CONFIG')) {
				$path = substr($arg, strlen('--config='));
				define('SWISS_PARKS_SDK_CONFIG', realpath($path) ?: $path);
			}
		}
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
		$url = View::encodeUnsafeUrlChars($url);
		$this->view->scriptUrlWithParams = $url;

		// Strip query string for form/action base path
		$paramStart = strpos($url, '?');
		$this->view->scriptUrl = ($paramStart > 0)
			? substr($url, 0, $paramStart)
			: $url;
	}


	/**
	 * Load system config and merge with user config
	 */
	private function loadSystemConfig(): void
	{

		// Base url (overridable via config / config.local.php)
		if (empty($this->config['base_url'])) {
			$this->config['base_url'] = 'https://hub.parks.swiss/';
		}
		$this->config['base_url'] = rtrim($this->config['base_url'], '/') . '/';

		// Xml import urls
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
			Category::EVENT,
			Category::PRODUCT,
			Category::BOOKING,
			Category::ACTIVITY,
			Category::PROJECT,
			Category::RESEARCH,
			Category::GASTRONOMY_AND_ACCOMMODATION,
			Category::REGIONAL_PRODUCT,
			Category::GASTRONOMY,
			Category::ACCOMMODATION,
			Category::SIGHT,
			Category::SUMMER_ACTIVITIES,
			Category::WINTER_ACTIVITIES,
			Category::INFORMATION,
			Category::INFRASTRUCTURE,
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

		// CLI sync of large exports needs a higher ceiling than typical web requests
		if (PHP_SAPI === 'cli') {
			@ini_set('memory_limit', '512M');
		}

		// Stream into a temp file so the full XML is not held in PHP memory twice
		$tmpPath = tempnam(sys_get_temp_dir(), 'parks-xml-');
		if ($tmpPath === false) {
			return false;
		}

		$fp = fopen($tmpPath, 'w+b');
		if ($fp === false) {
			@unlink($tmpPath);
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);

		// No total timeout (large exports take long), but abort stalled transfers so CLI syncs without time limit cannot hang forever
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
		curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 60);

		// Integrators may disable verify for local/dev endpoints via curl_verify_ssl
		$verifySsl = $this->config['curl_verify_ssl'] ?? true;
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);

		$ok = curl_exec($ch);
		$curlErrno = curl_errno($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		unset($ch);
		fclose($fp);

		// Treat transport errors, HTTP failures and empty bodies as missing XML
		if (($ok === false) || ($curlErrno !== 0) || ($httpCode >= 400) || (filesize($tmpPath) === 0)) {
			@unlink($tmpPath);
			return false;
		}

		$xml = simplexml_load_file($tmpPath);
		@unlink($tmpPath);

		return ($xml !== false) ? $xml : false;
	}



	/**
	 * Update stored SDK version after a successful import or migration
	 */
	private function updateMetaVersion(): void
	{

		$this->db->update('meta', ['version' => self::VERSION]);

		if (! empty($this->meta)) {
			$this->meta->version = self::VERSION;
		}

	}



	/**
	 * Plain-text status line for CLI scripts (sync, migrate)
	 */
	private function outputCliStatus(string $command, bool $ok, string $details): void
	{

		echo ($ok ? $command . ': ok' : $command . ': fail') . ' (' . $details . ')' . PHP_EOL;

	}



	/**
	 * Report successful migration to central log server
	 *
	 * Payload must match log_sdk_migration() on hub.parks.swiss
	 * (required: sdk_version, url, hash). Body is signed with HMAC-SHA256
	 * using api_hash (header X-Api-Migration-Signature).
	 * $details adds migration facts (previous_version, imported_offers, offers).
	 */
	public function logMigration(array $details = []): bool
	{

		// Central log endpoint is reached via HTTPS POST
		if (! function_exists('curl_init')) {
			return false;
		}

		// api_hash is required: used as HMAC key and verified server-side against export configs
		$apiHash = (string) ($this->config['api_hash'] ?? '');

		// Prefer the current request URL; fall back to hostname when run from CLI
		$siteUrl = (! empty($_SERVER['HTTP_HOST']) && ! empty($_SERVER['REQUEST_URI']))
			? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			: ((gethostname() ?: 'CLI') . ' (CLI)');

		if ($apiHash === '' || $siteUrl === '') {
			return false;
		}

		// Payload contract must stay in sync with log_sdk_migration() on hub.parks.swiss
		$payload = [
			'sdk_version' => self::VERSION,
			'php_version' => PHP_VERSION,
			'url' => $siteUrl,
			'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
			'hash' => $apiHash,
			'park_id' => $this->config['park_id'] ?? '',
			'file_path' => $_SERVER['SCRIPT_FILENAME'] ?? __FILE__,
			'php_sapi' => PHP_SAPI,
			'os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
			// Composer installs the SDK below vendor/, ZIP installs copy swiss-parks-sdk/ into the site
			'install_type' => str_contains(__DIR__, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) ? 'composer' : 'zip',
		] + $details;

		// Sign the exact JSON bytes that will be posted (server verifies the same string)
		$body = json_encode($payload);
		if ($body === false) {
			return false;
		}

		$signature = hash_hmac('sha256', $body, $apiHash);
		$url = $this->config['migration_log_url'] ?? 'https://hub.parks.swiss/migrate/log_sdk_migration';
		$verifySsl = $this->config['curl_verify_ssl'] ?? true;

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
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$success = ($response !== false) && ($httpCode >= 200) && ($httpCode < 300);

		// Logging failure must not fail the migration itself
		if (! $success) {
			$error = curl_error($ch) ?: ('HTTP ' . $httpCode);
			$this->logger->info('Migration log request failed: ' . $error);
		}

		return $success;

	}


}
