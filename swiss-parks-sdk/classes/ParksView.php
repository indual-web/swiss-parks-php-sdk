<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksView
{


	/**
	 * SDK instance
	 */
	public ParksSDK $sdk;


	/**
	 * Configuration
	 */
	public array $config;


	/**
	 * Script url
	 */
	public string $scriptUrl = '';
	public string $scriptUrlWithParams = '';


	/**
	 * Misc
	 */
	public bool $projectsOnly = false;
	public string $sbbLink;


	/**
	 * Detail template tags of each offer type
	 */
	public array $detailTemplateTags = [];


	/**
	 * View mode
	 * true: Returns the SDK output as string
	 * false: Echoes the output directly
	 */
	public bool $returnOutput;


	/**
	 * Specific target groups
	 */
	public array $specificTargetGroups;



	/**
	 * Constructor
	 */
	public function __construct(ParksSDK $sdk)
	{

		$this->sdk = $sdk;

		// Load SDK config
		$this->config = $sdk->config;

		// Set return output method
		$this->returnOutput = isset($this->config['return_output']) ? $this->config['return_output'] : false;

		// Specific target groups
		$this->specificTargetGroups = [7, 8, 9, 10, 12];

		// Init sbb links for each language
		$sbbI18n = [
			'de' => 'https://www.sbb.ch/de/kaufen/pages/fahrplan/fahrplan.xhtml',
			'fr' => 'https://www.sbb.ch/fr/acheter/pages/fahrplan/fahrplan.xhtml',
			'it' => 'https://www.sbb.ch/it/acquistare/pages/fahrplan/fahrplan.xhtml',
			'en' => 'https://www.sbb.ch/en/buying/pages/fahrplan/fahrplan.xhtml',
		];
		$this->sbbLink = $sbbI18n[$this->sdk->langId];

		// Set script url with all params
		if (! empty($_SERVER['REQUEST_URL'])) {
			$this->scriptUrlWithParams = $_SERVER['REQUEST_URL'];
		} elseif (! empty($_SERVER['REQUEST_URI'])) {
			$this->scriptUrlWithParams = $_SERVER['REQUEST_URI'];
		}

		// Set script url without params
		$this->scriptUrl = $this->scriptUrlWithParams;
		$paramStart = ! empty($this->scriptUrlWithParams) ? strpos($this->scriptUrlWithParams, '?') : 0;
		if ($paramStart > 0) {
			$this->scriptUrl = substr($this->scriptUrlWithParams, 0, $paramStart);
		}
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
	 * Show offer filter
	 */
	public function filter(array $params = []): ?string
	{

		// Init template data
		$templateData = [];

		// Init projects only
		$this->projectsOnly = ! empty($params['projects_only']) ? $params['projects_only'] : false;

		// Init url slugs
		$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
		$resetSlug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

		// Set name
		$fieldname = $this->config['form_prefix'] . 'filter';

		// Set action for seo urls
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

			$action = $this->scriptUrl;

			// Clean url from double slashes
			$action = str_replace('//', '/', $action);

			// Clean url from page param
			$action = preg_replace('/\/' . $pageSlug . '\/(\d*)/m', '', $action);

			// Clean url from reset param
			$action = str_replace('/' . $resetSlug, '', $action);

			// Set ending slash
			$action = rtrim($action, '/') . '/';
			
		} else {

			// Set action for default urls
			$action = $this->scriptUrlWithParams;

			// Clean url from page param
			$action = preg_replace('/[\?\&]page=\w*/', '', $action);

			// Clean url from reset param
			$action = preg_replace('/[\?\&]reset=\w*/', '', $action);
		}

		// Show filter
		$templateData['FILTER_SHOW_LINK'] = '<a class="show_filter"><i aria-hidden="true">a</i>' . $this->sdk->lang->get('offer_filter_show') . '</a>';
		$templateData['FILTER_FORM_START'] = '<form action="' . $action . '" method="post" autocomplete="off">';
		$templateData['FILTER_FORM_STOP'] = '</form>';
		$searchVal = (isset($params['selected']['search']) ? ' value="' . (isset($params['selected']['search']) ? $params['selected']['search'] : '') . '"' : '');

		// Filter: free text search
		$templateData['FILTER_TEXT_SEARCH'] = '
			<p class="form_element search">
				<span class="element_wrap">
					<label for="offer_filter_search">' . $this->sdk->lang->get('offer_search') . '</label>
					<input type="text" name="' . $fieldname . '[search]" id="offer_filter_search"' . $searchVal . ' placeholder="' . $this->sdk->lang->get('offer_search') . '" />
					<span class="search_icon" aria-hidden="true">k</span>
				</span>
			</p>
		';

		// Filter: select categories
		if (is_array($params['categories']) && ! empty($params['categories'])) {
			$categoryOptions = '';
			foreach ($params['categories'] as $key1 => $level1) {
				if (is_array($level1) && ! empty($level1)) {
					// Check, if there is more than 1 level down
					$firstLevel2 = reset($level1);
					if (! is_array($firstLevel2)) {
						$categoryOptions .= '
											<span class="label_wrapper" role="group" aria-label="' . $key1 . '">
												<label class="category_title" role="button" aria-pressed="false" tabindex="0">
													<span aria-hidden="true"></span>
													' . $key1 . '
												</label>
										';
					}
					foreach ($level1 as $key2 => $level2) {
						// Level 3
						if (is_array($level2) && ! empty($level2)) {
							$categoryOptions .= '
												<span class="label_wrapper" role="group" aria-label="' . $key2 . '">
													<label class="category_title" role="button" aria-pressed="false" tabindex="0">
														<span aria-hidden="true"></span>
														' . $key2 . '
													</label>
											';
							foreach ($level2 as $key3 => $level3) {
								$checked = (isset($params['selected']['categories']) && is_array($params['selected']['categories']) && in_array($key3, $params['selected']['categories']) ? 'checked="checked"' : '');
								$categoryOptions .= '
													<label for="' . $fieldname . '_categories_' . $key3 . '" role="option" aria-selected="false" tabindex="0">
														<input name="' . $fieldname . '[categories][]" ' . $checked . ' value="' . $key3 . '" type="checkbox" id="' . $fieldname . '_categories_' . $key3 . '">
														<span aria-hidden="true"></span>
														' . $level3 . '
													</label>
												';
							}
							$categoryOptions .= '</span>';
						}
						// Level 2
						else {
							$checked = (isset($params['selected']['categories']) && is_array($params['selected']['categories']) && in_array($key2, $params['selected']['categories']) ? 'checked="checked"' : '');
							$categoryOptions .= '
												<label for="' . $fieldname . '_categories_' . $key2 . '" role="option" aria-selected="false" tabindex="0">
													<input name="' . $fieldname . '[categories][]" ' . $checked . ' value="' . $key2 . '" type="checkbox" id="' . $fieldname . '_categories_' . $key2 . '">
													<span aria-hidden="true"></span>
													' . $level2 . '
												</label>
											';
						}
					}
					// Close group
					if (! is_array($firstLevel2)) {
						$categoryOptions .= '</span>';
					}
				}
			}
			if ($categoryOptions !== '') {
				$templateData['FILTER_CATEGORIES'] = '
					<div class="form_element mega_dropdown">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . (! empty($params['projects_only']) ? $this->sdk->lang->get('projects_all') : $this->sdk->lang->get('offer_all')) . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option" aria-hidden="true"></span>
								<span class="all">' . (! empty($params['projects_only']) ? $this->sdk->lang->get('projects_all') : $this->sdk->lang->get('offer_all')) . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
								<span class="dropdown_filter" role="toolbar">
									<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
									<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
								</span>' . $categoryOptions . '
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: date from and date to
		if (isset($params['show_event_filter']) && ($params['show_event_filter'] == true)) {
			$dateFromVal = (isset($params['selected']['date_from']) && ! empty($params['selected']['date_from']) ? ' value="' . $params['selected']['date_from'] . '"' : '');
			$dateToVal = (isset($params['selected']['date_to']) && ! empty($params['selected']['date_to']) ? ' value="' . $params['selected']['date_to'] . '"' : '');
			$templateData['FILTER_DATES'] = '
				<div class="form_element date_wrap">
					<span class="element_wrap">
						<label for="date_start">' . $this->sdk->lang->get('general_date') . ' ' . $this->sdk->lang->get('general_from') . '</label>
						<i class="icon" aria-hidden="true">e</i>
						<input id="date_start" name="' . $fieldname . '[date_from]"' . $dateFromVal . ' class="flatpickr" type="text" placeholder="' . $this->sdk->lang->get('general_date') . ' ' . $this->sdk->lang->get('general_from') . '">
					</span>
					<span class="element_wrap">
						<label for="date_end">' . $this->sdk->lang->get('general_date') . ' ' . $this->sdk->lang->get('general_to') . '</label>
						<i class="icon" aria-hidden="true">e</i>
						<input id="date_end" name="' . $fieldname . '[date_to]"' . $dateToVal . ' class="flatpickr" type="text" placeholder="' . $this->sdk->lang->get('general_date') . ' ' . $this->sdk->lang->get('general_to') . '">
					</span>
					<div class="cf"></div>
				</div>
			';
		}

		// Filter: target group (hidden only when the export contains projects exclusively)
		if (isset($params['show_target_group_filter']) && ($params['show_target_group_filter'] == true) && empty($params['projects_only'])) {
			$templateData['FILTER_TARGET_GROUPS'] = '';

			if (! empty($this->sdk->model->targetGroups)) {

				$targetGroupGroups = [
					$this->sdk->lang->get('offer_target_group_general_info'),
					$this->sdk->lang->get('offer_target_group_specific_info')
				];

				$targetGroupOptions = '';
				foreach ($targetGroupGroups as $targetGroupIndex => $targetGroupLabel) {

					$groupItems = '';
					foreach ($this->sdk->model->targetGroups as $targetGroupId => $targetLabel) {

						// Check specific target group
						if (in_array($targetGroupId, $this->specificTargetGroups) == (bool)$targetGroupIndex) {

							// Check main target group restriction
							if (empty($this->sdk->systemFilter['target_groups']) || in_array($targetGroupId, $this->sdk->systemFilter['target_groups'])) {
								$targetVal = isset($params['selected']['target_groups']) && in_array($targetGroupId, $params['selected']['target_groups']) ? 'checked="checked"' : '';
								$groupItems .= '
													<label for="' . $fieldname . '_target_groups_' . $targetGroupId . '" role="option" aria-selected="false" tabindex="0">
														<input name="' . $fieldname . '[target_groups][]" id="' . $fieldname . '_target_groups_' . $targetGroupId . '" ' . $targetVal . ' value="' . $targetGroupId . '" type="checkbox">
														<span aria-hidden="true"></span>
														' . htmlspecialchars($targetLabel) . '
													</label>
												';
							}
						}
					}

					if ($groupItems !== '') {
						$targetGroupOptions .= '
										<span class="label_wrapper" role="group" aria-label="' . $targetGroupLabel . '">
											<label class="category_title" role="button" aria-pressed="false" tabindex="0">
												<span aria-hidden="true"></span>
												' . $targetGroupLabel . '
											</label>
									' . $groupItems . '
										</span>
									';
					}
				}

				if ($targetGroupOptions !== '') {
					$templateData['FILTER_TARGET_GROUPS'] = '
						<div class="form_element mega_dropdown filter_target_groups">
							<div class="form_group">
								<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_target_group_general_info') . '">
									<i class="deselect_icon" aria-hidden="true">m</i>
									<span class="selected_option"></span>
									<span class="all">' . $this->sdk->lang->get('offer_target_group_general_info') . '</span>
									<i class="arrow_icon" aria-hidden="true">b</i>
								</h4>
								<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
									<span class="dropdown_filter" role="toolbar">
										<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
										<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
									</span>
									' . $targetGroupOptions . '
								</div>
							</div>
						</div>
					';
				}
			}
		}

		// Filter: fields of activity (hidden only when the export contains projects exclusively)
		if (isset($params['show_fields_of_activity_filter']) && ($params['show_fields_of_activity_filter'] == true) && empty($params['projects_only'])) {
			$templateData['FILTER_FIELDS_OF_ACTIVITY'] = '';

			if (! empty($this->sdk->model->fieldsOfActivity)) {
				$fieldOptions = '';
				foreach ($this->sdk->model->fieldsOfActivity as $fieldId => $fieldLabel) {
					if (empty($this->sdk->systemFilter['fields_of_activity']) || in_array($fieldId, $this->sdk->systemFilter['fields_of_activity'])) {
						$checked = isset($params['selected']['fields_of_activity']) && in_array($fieldId, $params['selected']['fields_of_activity']) ? 'checked="checked"' : '';
						$fieldOptions .= '
									<label for="' . $fieldname . '_fields_of_activity_' . $fieldId . '" role="option" aria-selected="false" tabindex="0">
										<input name="' . $fieldname . '[fields_of_activity][]" id="' . $fieldname . '_fields_of_activity_' . $fieldId . '" ' . $checked . ' value="' . $fieldId . '" type="checkbox">
										<span aria-hidden="true"></span>
										' . htmlspecialchars($fieldLabel) . '
									</label>
								';
					}
				}

				if ($fieldOptions !== '') {
					$templateData['FILTER_FIELDS_OF_ACTIVITY'] = '
						<div class="form_element mega_dropdown filter_fields_of_activity">
							<div class="form_group">
								<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_fields_of_activity') . '">
									<i class="deselect_icon" aria-hidden="true">m</i>
									<span class="selected_option"></span>
									<span class="all">' . $this->sdk->lang->get('offer_fields_of_activity') . '</span>
									<i class="arrow_icon" aria-hidden="true">b</i>
								</h4>
								<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
									<span class="dropdown_filter" role="toolbar">
										<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
										<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
									</span>
									<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_fields_of_activity') . '">' . $fieldOptions . '</span>
								</div>
							</div>
						</div>
					';
				}
			}
		}

		// Filter: municipality
		if (isset($params['show_municipality_filter']) && ($params['show_municipality_filter'] == true)) {

			// Get park municipalities
			if (! empty($params['park_id']) && ($params['park_id'] > 0)) {
				$municipalities = $this->sdk->model->getMunicipalities(['park_id' => $params['park_id']]);
			} else {
				$municipalities = $this->sdk->model->getMunicipalities();
			}

			if (! empty($municipalities) && (count($municipalities) > 1)) {

				// Format municipality options
				$municipalityOptions = '';
				foreach ($municipalities as $municipalityId => $municipality) {
					$checked = isset($params['selected']['municipalities']) && in_array($municipalityId, $params['selected']['municipalities']) ? 'checked' : '';
					$option = '<label role="option" aria-selected="false" tabindex="0">
									<input name="' . $fieldname . '[municipalities][]" ' . $checked . ' value="' . $municipalityId . '" type="checkbox">
									<span aria-hidden="true"></span>
									' . $municipality . '
								</label>';

					$municipalityOptions .= $option;
				}

				$templateData['FILTER_MUNICIPALITIES'] = '
					<div class="form_element mega_dropdown filter_municipalities">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_municipalities_all') . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option"></span>
								<span class="all">' . $this->sdk->lang->get('offer_municipalities_all') . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
								<span class="dropdown_filter" role="toolbar">
									<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
									<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
								</span>
								<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_municipalities_all') . '">' . $municipalityOptions . '</span>
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: accessibility (hidden only when the export contains projects exclusively)
		if (isset($params['show_accessibility_filter']) && ($params['show_accessibility_filter'] == true) && empty($params['hide_accessibility_filter']) && empty($params['projects_only'])) {
			$templateData['FILTER_ACCESSIBILITIES'] = '';

			// Get accessibility list
			$accessibilities = $this->sdk->model->getAccessibilityList();
			if (! empty($accessibilities)) {
				$templateData['FILTER_ACCESSIBILITIES'] = '
					<div class="form_element mega_dropdown filter_accessibilities">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_accessibility') . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option"></span>
								<span class="all">' . $this->sdk->lang->get('offer_accessibility') . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
								<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_accessibility') . '">';
				foreach ($accessibilities as $accessibilityId => $accessibility) {
					$checked = isset($params['selected']['accessibilities']) && in_array($accessibilityId, $params['selected']['accessibilities']) ? 'checked' : '';
					$templateData['FILTER_ACCESSIBILITIES'] .= '
										<label role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[accessibilities][]" ' . $checked . ' value="' . $accessibilityId . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $accessibility . '
										</label>
									';
				}
				$templateData['FILTER_ACCESSIBILITIES'] .= '
								</span>
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: park
		if (! empty($params['users']) && count($params['users']) > 1) {
			$templateData['FILTER_PARKS'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_parks_all') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->sdk->lang->get('offer_parks_all') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_parks_all') . '">';
			foreach ($params['users'] as $parkId => $park) {
				$parkVal = isset($params['selected']['park_id']) && in_array($parkId, $params['selected']['park_id']) ? 'checked="checked"' : '';
				$templateData['FILTER_PARKS'] .= '
										<label for="' . $fieldname . '_park_id_' . $parkId . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[park_id][]" id="' . $fieldname . '_park_id_' . $parkId . '" ' . $parkVal . ' value="' . $parkId . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $park . '
										</label>
									';
			}
			$templateData['FILTER_PARKS'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}

		// Filter: project status
		if (! empty($params['show_project_filter'])) {
			$allProjectStatus = $this->config['project_status_' . $this->sdk->langId];
			$templateData['FILTER_PROJECT'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_project_status') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->sdk->lang->get('offer_project_status') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_project_status') . '">';
			foreach ($allProjectStatus as $statusId => $statusLabel) {
				$projectVal = isset($params['selected']['project_status']) && in_array($statusId, $params['selected']['project_status']) ? 'checked="checked"' : '';
				$templateData['FILTER_PROJECT'] .= '
										<label for="' . $fieldname . '_project_status_' . $statusId . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[project_status][]" id="' . $fieldname . '_project_status_' . $statusId . '" ' . $projectVal . ' value="' . $statusId . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $statusLabel . '
										</label>
									';
			}
			$templateData['FILTER_PROJECT'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}

		// Filter: routes
		if (isset($params['show_route_filter']) && ($params['show_route_filter'] == true)) {

			// Route length
			$templateData['FILTER_ROUTE_LENGTH'] = '
				<div class="form_element range_wrap">
					<input id="route_min" name="' . $fieldname . '[route_length_min]" type="hidden" value="' . (isset($params['selected']['route_length_min']) ? $params['selected']['route_length_min'] : '0') . '">
					<input id="route_max" name="' . $fieldname . '[route_length_max]" type="hidden" value="' . (isset($params['selected']['route_length_max']) ? $params['selected']['route_length_max'] : '50') . '">
					<div class="range"></div>
					<span class="range_text" aria-live="polite">
						<span class="minimum" aria-hidden="true" data-label="' . $this->sdk->lang->get('offer_routes_length') . '"></span>
						<span class="maximum" aria-hidden="true"></span>
						<span class="sr-only" id="range_sr_only"></span>
					</span>
					<div class="cf"></div>
				</div>
			';

			// Route time
			$templateData['FILTER_ROUTE_TIME'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('offer_routes_time_required') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->sdk->lang->get('offer_routes_time_required') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_routes_time_required') . '">';
			foreach ($this->config['route_times'] as $time) {
				$time = html_entity_decode($time);
				$timeChecked = ! empty($params['selected']['time_required']) && in_array($time, $params['selected']['time_required']) ? 'checked="checked"' : '';
				$templateData['FILTER_ROUTE_TIME'] .= '
										<label for="' . $fieldname . '_time_required_' . $time . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[time_required][]" id="' . $fieldname . '_time_required_' . $time . '" ' . $timeChecked . ' value="' . $time . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $time . '
										</label>
									';
			}
			$templateData['FILTER_ROUTE_TIME'] .= '
							</span>
						</div>
					</div>
				</div>
			';

			// Route condition and technic
			$titleCondition = $this->sdk->lang->get('offer_routes_condition');
			$titleTechnic = $this->sdk->lang->get('offer_routes_technic');
			$templateData['FILTER_ROUTE_CONDITION'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->sdk->lang->get('general_select_field') . ': ' . $this->sdk->lang->get('offer_routes_requirements') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option selected_conditions_technics"></span>
							<span class="all">' . $this->sdk->lang->get('offer_routes_requirements') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->sdk->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_routes_requirements') . '">
								<label class="category_title" role="button" aria-pressed="false" tabindex="0">
									<span aria-hidden="true"></span>
									' . $titleCondition . '
								</label>';
			foreach ($this->config['route_levels'] as $level => $levelValue) {
				$levelChecked = isset($params['selected']['level_condition']) && in_array($level, $params['selected']['level_condition']) ? 'checked="checked"' : '';
				$templateData['FILTER_ROUTE_CONDITION'] .= '
										<label for="' . $fieldname . '_level_condition_' . $level . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[level_condition][]" id="' . $fieldname . '_level_condition_' . $level . '" ' . $levelChecked . ' value="' . $level . '" type="checkbox">
											<span aria-hidden="true"></span>
											<span class="hidden" aria-hidden="true">' . $titleCondition[0] . ': </span>' . $levelValue . '
										</label>
									';
			}
			$templateData['FILTER_ROUTE_CONDITION'] .= '
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->sdk->lang->get('offer_routes_requirements') . '">
								<label class="category_title" role="button" aria-pressed="false" tabindex="0">
									<span aria-hidden="true"></span>
									' . $titleTechnic . '
								</label>';
			foreach ($this->config['route_levels'] as $level => $levelValue) {
				$levelChecked = isset($params['selected']['level_technics']) && in_array($level, $params['selected']['level_technics']) ? 'checked="checked"' : '';
				$templateData['FILTER_ROUTE_CONDITION'] .= '
										<label for="' . $fieldname . '_level_technics_' . $level . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[level_technics][]" id="' . $fieldname . '_level_technics_' . $level . '" ' . $levelChecked . ' value="' . $level . '" type="checkbox">
											<span aria-hidden="true"></span>
											<span class="hidden">' . $titleTechnic[0] . ': </span>' . $levelValue . '
										</label>
									';
			}
			$templateData['FILTER_ROUTE_CONDITION'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}


		// Filter: keywords
		$filterKeywords = '';
		if (! empty($this->sdk->config['keyword_filter'][$this->sdk->langId]) && ($this->sdk->filterDisplayKeywords === true)) {
			$allKeywords = $this->sdk->config['keyword_filter'][$this->sdk->langId];
			if (is_array($allKeywords) && (count($allKeywords) > 2)) {

				// Get keyword title and link label to show all entries
				$keywordTitle = $allKeywords[0];
				$keywordAll = $allKeywords[1];
				unset($allKeywords[0]);
				unset($allKeywords[1]);

				$getKeyword = '';

				// Get keyword from URL
				if (isset($_REQUEST['keyword'])) {
					$getKeyword = $_REQUEST['keyword'];
				}

				// Get selected keyword from session
				else if (! empty($_SESSION[$this->sdk->sessionName]['keyword'])) {
					$getKeyword = $_SESSION[$this->sdk->sessionName]['keyword'];
				}

				// Prepare keyword link url
				$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
				$resetSlug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';
				$scriptUrl = $this->scriptUrl;
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

					// Clean url from double slashes
					$scriptUrl = str_replace('//', '/', $this->scriptUrl);

					// Clean url from page param
					$scriptUrl = preg_replace('/\/' . $pageSlug . '\/(\d*)/m', '', $scriptUrl);

					// Clean url from reset param
					$scriptUrl = str_replace('/' . $resetSlug, '', $scriptUrl);

					// Remove last slash
					$scriptUrl = rtrim($scriptUrl, '/');
				}

				if (! empty($allKeywords)) {

					// Show wrapper
					$filterKeywords = '
						<div class="keywords_wrap">
							<h3>' . $keywordTitle . '</h3>
							<ul class="keywords">
								<li class="keyword' . (empty($getKeyword) ? ' active' : '') . '"><a href="' . $scriptUrl . '?keyword=">' . $keywordAll . '</a></li>
					';

					// Show keyword links
					foreach ($allKeywords as $keyword) {

						// Clean keyword
						$cleanKeyword = str_replace('*', '---', $keyword);
						$cleanKeyword = str_replace('/', '-_-', $cleanKeyword);

						// Show keyword
						$filterKeywords .= '
								<li class="keyword' . ((! empty($getKeyword) && ($getKeyword == $cleanKeyword)) ? ' active' : '') . '"><a href="' . $scriptUrl . '?keyword=' . $cleanKeyword . '">' . str_replace('-', ' ', $keyword) . '</a></li>
						';
					}

					// Close wrapper
					$filterKeywords .= '
							</ul>
						</div>
					';
				}
			}
		}
		$templateData['FILTER_KEYWORDS'] = $filterKeywords;


		// Set reset link with seo urls
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

			// Clean reset url from page param
			$scriptUrl = preg_replace('/\/' . $pageSlug . '\/(\d*)/m', '', $this->scriptUrl);

			// Remove slash at the end of the url
			$scriptUrl = rtrim($scriptUrl, '/');

			// Set reset link
			$resetLink = $scriptUrl . '/reset';
		}

		// Set reset link with default urls
		else {
			$resetLink = $this->scriptUrlWithParams;
			if (strstr($resetLink, '?')) {
				$resetLink .= '&reset=1';
			} else {
				$resetLink .= '?reset=1';
			}
		}

		// Filter buttons
		if ($params['filter'] == true) {
			$templateData['FILTER_RESET_BUTTON'] = '<a href="' . $resetLink . '" class="reset_link" title="' . $this->sdk->lang->get('offer_link_filter_reset') . '"><i aria-hidden="true">m</i>' . $this->sdk->lang->get('offer_reset') . '</a>';
		}
		$templateData['FILTER_SUBMIT_BUTTON'] = '<input type="submit" name="' . $fieldname . '[submit]" value="' . $this->sdk->lang->get('general_search') . '" class="button" />';

		// Compile filter template
		$output = $this->sdk->compileTemplate('filter', $templateData);

		// Show output
		return $this->output($output);
	}



	/**
	 * Show offer list
	 */
	public function listOffers(array $offers, bool $inTab = false, int $poi = 0, ?int $originalCategory = null): ?string
	{

		$output = "";
		if (isset($offers['total']) && ($offers['total'] > 0) && isset($offers['data']) && ! empty($offers['data'])) {

			// Output
			$output .= '<div class="entries_wrap listing">';

			// Show total
			if ($inTab == false) {
				$totalRowsLabel = (! empty($this->projectsOnly) ? 'project' : 'offer');
				$totalLabel = ($offers['total'] == 1)
					? $this->sdk->lang->get($totalRowsLabel)
					: $this->sdk->lang->get($totalRowsLabel . 's');
				$output .= '
					<div id="offer_total">
						<span class="offer_count">' . number_format((int) $offers['total'], 0, '.', "'") . ' ' . $totalLabel . '</span>
					</div>
				';
			}

			foreach ($offers['data'] as $offer) {

				if (! empty($offer)) {


					// Prepare detail link with seo urls
					if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
						$offerDetailUrl = $this->getSeoDetailUrl($offer->offer_id, $offer->title);
					}

					// Prepare default detail url
					else {
						$paramName = $this->config['url_param_prefix'] . 'offer';
						$offerDetailUrl = $this->scriptUrl . (strstr($this->scriptUrl, '?') ? '&amp' : '?') . $paramName . '=' . $offer->offer_id;
					}

					// Prepare target
					$linkTarget = '';
					if (($inTab === true) && ! empty($this->config['poi_listing_link_target'])) {
						$linkTarget = ' target="' . $this->config['poi_listing_link_target'] . '"';
					}

					// Allow to get back to routes
					if ($poi > 0) {

						// Seo urls
						if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
							$offerDetailUrl = $this->getSeoDetailUrl($offer->offer_id, $offer->title, $poi);
						}

						// Default urls
						else {
							$offerDetailUrl .= '&amp;poi=' . $poi . '&amp;original_category=' . $originalCategory;
						}

					}

					// Prepare link pre-tag
					$linkStartTag = '<a href="' . $offerDetailUrl . '" class="entry_link"' . $linkTarget . ' title="' . $this->sdk->lang->get('offer_link_to_offer') . ' ' . $offer->title . '">';
					$linkEndTag = '</a>';

					// Prepare dates
					$dateFrom = null;
					$dateTo = null;
					if (isset($offer->date_from) && ! empty($offer->date_from)) {
						$dateFrom = new DateTime($offer->date_from);
					}
					if (isset($offer->date_to) && ! empty($offer->date_to)) {
						$dateTo = new DateTime($offer->date_to);
					}

					// Prepare categories
					$isProject = ! empty($offer->is_project) ? ' is_project' : '';

					// Start offer as article
					$output .= '<article id="offer_' . $offer->offer_id . '" class="listing_entry' . $isProject . '">';

					// Favorites
					$output .= $this->getFavoritesLink($offer->offer_id);

					// Offer image or date
					$hasImage = isset($offer->images) && (count($offer->images) > 0);
					$configThumbnailSize = isset($this->config['overview_thumbnail_size']) ? $this->config['overview_thumbnail_size'] : 'medium';

					// Image
					if ($hasImage == true) {
						$output .= '<div class="pictures" aria-hidden="true">';
						if ($offer->is_hint == true) {
							$output .= '<div class="tipp">' . $this->sdk->lang->get('offer_hint') . '</div>';
						}
						if (($offer->is_park_partner_event == true) || ($offer->is_park_partner == true)) {
							$output .= '<div class="tipp parkpartner">' . $this->sdk->lang->get('offer_park_partner_label') . '</div>';
						}
						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkStartTag;
						}
						$output .= '<img class="attachment offer_images" src="' . $offer->images[0]->{$configThumbnailSize} . '" alt="" />';
						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkEndTag;
						}
						$output .= '</div>';
					} else {
						$output .= '<div class="pictures empty_image">';
						if ($offer->is_hint == true) {
							$output .= '<div class="tipp">' . $this->sdk->lang->get('offer_hint') . '</div>';
						}
						if (($offer->is_park_partner_event == true) || ($offer->is_park_partner == true)) {
							$output .= '<div class="tipp parkpartner">' . $this->sdk->lang->get('offer_park_partner_label') . '</div>';
						}
						if ($offer->root_category == CATEGORY_EVENT) {
							$output .= '<div class="date_wrap">';

							// Prepare dates
							$dateFrom = null;
							$dateTo = null;
							if (isset($offer->date_from) && ! empty($offer->date_from)) {
								$dateFrom = new DateTime($offer->date_from);
							}
							if (isset($offer->date_to) && ! empty($offer->date_to)) {
								$dateTo = new DateTime($offer->date_to);
							}

							// Date from
							if (! empty($dateFrom)) {
								$output .= '
									<div class="date">
										<span>' . $dateFrom->format('d') . '</span>
										<span>' . $this->sdk->lang->get('month' . $dateFrom->format('m')) . '</span>
									</div>
								';
							}

							// Date to
							if (! empty($dateTo) && ($dateFrom->format('dmY') != $dateTo->format('dmY'))) {
								$output .= '
									<div class="date">
										<span>' . $dateTo->format('d') . '</span>
										<span>' . $this->sdk->lang->get('month' . $dateTo->format('m')) . '</span>
									</div>
								';
							}

							$output .= '</div>';
						} elseif (! empty($this->config['placeholder_image'])) {
							$output .= '
								<div class="placeholder_wrap">
									<div class="placeholder_image">
										<img src="' . $this->config['placeholder_image'] . '" width="80" height="80" alt="Logo: Netzwerk Schweizer Pärke">
									</div>
								</div>
							';
						}
						$output .= '</div>';
					}

					$output .= '<div class="description">';

					// Show park name
					if (! empty($this->config['show_park_name'])) {
						$output .= '<div class="introduction">' . $offer->park . '</div>';
					}

					// Lang attribute
					$langAttr = '';
					if ($offer->language != $this->sdk->langId) {
						$langAttr = ' lang="' . $offer->language . '"';
					}

					// Get heading html tag
					$headingTag = 'h3';
					if (! empty($this->config['heading_offer_title_in_overview'])) {
						$headingTag = $this->config['heading_offer_title_in_overview'];
					}

					// Offer title
					$output .= '<' . $headingTag . $langAttr . '>';
					if (! empty($this->sdk->config['show_button_in_overview'])) {
						$output .= $linkStartTag;
					}
					$output .= $offer->title;
					if (! empty($this->sdk->config['show_button_in_overview'])) {
						$output .= $linkEndTag;
					}
					$output .= '</' . $headingTag . '>';

					// Offer location (place only)
					if (
						! empty($this->config['show_event_location_in_overview']) 
						&& 
						! empty($offer->institution_location)
						&&
						($offer->root_category == CATEGORY_EVENT)
					) {
						$output .= '<div class="institution_location">' . $offer->institution_location . '</div>';
					}

					// Route condition
					if (! empty($offer->route_condition_color) && ! empty($offer->route_condition)) {
						$output .= '
							<div class="route_condition ' . $offer->route_condition_color . '">' . $offer->route_condition . '</div>
						';
					}

					// Show dates
					if (isset($offer->date_from) && ($offer->root_category == CATEGORY_EVENT)) {

						// Adjust start date if filter date from is set
						$filterData = $this->sdk->getFilterData();
						$offer->date_from = ParksDate::parksAdjustDateFrom($offer->date_from ?? '', $offer->date_to ?? '', $filterData['date_from'] ?? '');

						// Date from
						$output .= '<span class="date">' . ParksDate::formatOfferDate(['date_from' => $offer->date_from, 'date_to' => $offer->date_to, 'times' => $offer->times], $this->sdk->lang) . '</span>';
					}

					// Show project and research duration
					if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
						$duration = $this->formatProjectDuration($offer->duration_from, $offer->duration_to, $offer->duration_from_month, $offer->duration_to_month, true);
						if (! empty($duration)) {
							$output .= '
								<span class="date">' . $duration . '</span>
							';
						}
					}

					// Offer description
					if (! empty($this->config['show_short_description_in_overview'])) {
						$output .= '<p class="offer_description offer_description_short"' . $langAttr . '>';
						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkStartTag;
						}

						if (! empty($offer->abstract) && (mb_strlen($offer->abstract) > 50)) {
							$offer->abstract = mb_substr($offer->abstract, 0, 50) . '...';
						}
						$output .= ParksFormat::autoTextFormat($offer->abstract);

						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkEndTag;
						}
						$output .= '</p>';
					} else {
						$output .= '<p class="offer_description offer_description_medium"' . $langAttr . '>';
						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkStartTag;
						}

						// Set description medium
						$descriptionMedium = strip_tags($offer->description_medium ?? '', '<a>');
						if ($inTab && (mb_strlen($descriptionMedium) > 250)) {
							$descriptionMedium = mb_substr($descriptionMedium, 0, 250) . '...';
						}
						$output .= ParksFormat::autoTextFormat($descriptionMedium);
						
						if (! empty($this->sdk->config['show_button_in_overview'])) {
							$output .= $linkEndTag;
						}
						$output .= '</p>';
					}
					if (! empty($this->sdk->config['show_button_in_overview'])) {
						$output .= $linkEndTag;
					}

					// Offer price
					if (! empty($offer->online_shop_price)) {
						$output .= '
							<div class="price">
								<span class="currency">CHF</span>
								<span class="value">' . number_format($offer->online_shop_price, 2, '.', "'") . '</span>				
							</div>
						';
					}

					// Offer categories
					if (! empty($offer->categories)) {
						$categories = [];
						$output .= '<div class="categories">';
						foreach ($offer->categories as $category) {
							if (! in_array($category->category_id, $this->config['hidden_categories'])) {
								$categories[] = $category->body;
								$output .= '<span>' . $category->body . '</span>';
							}
						}
						$output .= '</div>';

						// Show route details
						if (! empty($offer->time_required) || (! empty($offer->route_length) && (intval($offer->route_length) > 0))) {
							$output .= '<div class="route_info">';
							if (! empty($offer->time_required) || ! empty($offer->time_required_minutes)) {
								$output .= '<span>' . $this->sdk->lang->get('offer_time_required') . ': ' . ParksDate::activityGetTimeRequired($offer, $this->sdk->lang) . '</span>';
							}
							if (! empty($offer->route_length) && (intval($offer->route_length) > 0)) {
								$output .= '<span>' . $this->sdk->lang->get('offer_route_length') . ': ' . number_format((float) $offer->route_length, 1) . $this->sdk->lang->get('offer_route_length_km') . '</span>';
							}
							$output .= '</div>';
						}
					}

					// Offer keywords
					if (! empty($this->config['show_keywords_in_overview']) && ! empty($offer->keywords)) {
						$offerKeywords = explode(' ', $offer->keywords);
						if (! empty($offerKeywords)) {
							$output .= '<div class="offer_detail_keywords">';
							foreach ($offerKeywords as $keyword) {

								// Prepare keyword
								$labelKeyword = ParksFormat::ucfirstUtf8(str_replace('-', ' ', $keyword));

								// Show keyword
								$output .= '
									<span class="offer_keyword ' . strtolower($keyword) . '">' . $labelKeyword . '</span>
								';
							}
							$output .= '</div>';
						}
					}

					$output .= '<div class="cf"></div>';
					$output .= '</div>';
					$output .= '<div class="cf"></div>';

					// Set link target and link
					$output .= $linkStartTag;
					if (! empty($this->sdk->config['show_button_in_overview'])) {
						$output .= $this->sdk->lang->get('more_informations');
					}
					$output .= $linkEndTag;

					$output .= '</article>';
				}
			}

			$output .= '</div>';
		} else {

			$output .= '<p class="no_results">' . $this->sdk->lang->get('offer_not_found') . '</p>';
		}

		if ($inTab === true) {
			return $output;
		}

		return $this->output($output);
	}



	/**
	 * Show pagination
	 */
	public function pagination(int $page = 1, int $total = 1): ?string
	{

		// Init page
		if ($page <= 0) {
			$page = 1;
		}

		// Init seo urls
		$seoUrls = ! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true);
		if ($seoUrls == true) {

			// Init
			$pageSlug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
			$resetSlug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

			// Clean script url from double slashes
			$scriptUrl = rtrim($this->scriptUrl, '/');
			$scriptUrl = str_replace('//', '/', $scriptUrl);
			$scriptUrl = str_replace('/' . $resetSlug, '', $scriptUrl);

			// Remove page number segments
			if (strstr($scriptUrl, '/' . $pageSlug . '/')) {
				$pageSegmentPos = strpos($scriptUrl, '/' . $pageSlug);
				$scriptUrl = substr($scriptUrl, 0, $pageSegmentPos);
			}
		}

		// Output
		$output = '<div class="offer_pagination" role="navigation" aria-label="' . $this->sdk->lang->get('pagination_label') . '">';

		// Previous link
		if ($page > 1) {
			$paramName = $this->config['url_param_prefix'] . 'page';
			$link = $this->scriptUrlWithParams;

			$output .= '<div class="prev_wrap">';

			// Handle seo urls
			if ($seoUrls == true) {

				// Set page numbers
				$firstLink = $scriptUrl . '/' . $pageSlug . '/1';
				$prevLink = $scriptUrl . '/' . $pageSlug . '/' . ($page - 1);
			}

			// Default urls
			else {

				// Replace page numbers
				if (strstr($this->scriptUrlWithParams, $paramName)) {
					$firstLink = str_replace($paramName . '=' . $page, $paramName . '=1', $this->scriptUrlWithParams);
					$prevLink = str_replace($paramName . '=' . $page, $paramName . '=' . ($page - 1), $this->scriptUrlWithParams);
				}

				// Set page numbers
				else {
					$firstLink = (strstr($link, '?') ? '&' : '?') . $paramName . '=1';
					$prevLink = (strstr($link, '?') ? '&' : '?') . $paramName . '=' . ($page - 1);
				}
			}

			$output .= '
				<a href="' . $firstLink . '" aria-label="' . $this->sdk->lang->get('offer_link_to_first_page') . '">' . $this->sdk->lang->get('offer_first') . '</a>
				<a href="' . $prevLink . '" class="icon" aria-label="' . $this->sdk->lang->get('offer_link_to_previous_page') . '" >o</a>
			';

			$output .= '</div>';
		}

		// Next page link
		if ($page < $total) {
			$paramName = $this->config['url_param_prefix'] . 'page';
			$link = $this->scriptUrlWithParams;

			$output .= '<div class="next_wrap">';

			// Handle seo urls
			if ($seoUrls == true) {

				// Set page numbers
				$nextLink = $scriptUrl . '/' . $pageSlug . '/' . ($page + 1);
				$lastLink = $scriptUrl . '/' . $pageSlug . '/' . $total;
			}

			// Default urls
			else {

				// Replace page numbers
				if (strstr($this->scriptUrlWithParams, $paramName . '=')) {
					$lastLink = str_replace($paramName . '=' . $page, $paramName . '=' . $total, $this->scriptUrlWithParams);
					$nextLink = str_replace($paramName . '=' . $page, $paramName . '=' . ($page + 1), $this->scriptUrlWithParams);
				}

				// Set page numbers
				else {
					$nextLink = $this->scriptUrlWithParams . (strstr($link, '?') ? '&' : '?') . $paramName . '=' . ($page + 1);
					$lastLink = $this->scriptUrlWithParams . (strstr($link, '?') ? '&' : '?') . $paramName . '=' . $total;
				}
			}

			// View
			$output .= '
				<a href="' . $nextLink . '" class="icon" aria-label="' . $this->sdk->lang->get('offer_link_to_next_page') . '">p</a>
				<a href="' . $lastLink . '" aria-label="' . $this->sdk->lang->get('offer_link_to_last_page') . '">' . $this->sdk->lang->get('offer_last') . '</a>
			';

			$output .= '</div>';
		}

		// Page numbers
		if ($total > 1) {
			$paramName = $this->config['url_param_prefix'] . 'page';
			$paginationMaxNumbers = $this->sdk->config['pagination_max_numbers'] ? intval($this->sdk->config['pagination_max_numbers']) : 5;
			$halfPagesStart = floor($paginationMaxNumbers / 2);
			$halfPagesEnd = ceil($paginationMaxNumbers / 2) - 1;
			$end = 0;

			// Calculate start
			$start = intval($page - $halfPagesStart);
			if ($start <= 0) {
				$start = 1;
				$end = $paginationMaxNumbers;
			} elseif ($start < ($page - $halfPagesStart)) {
				$start = ($page - $halfPagesStart);
			}

			// Calculate end
			if (($end == 0) || ($end > $total)) {
				$end = ($page + $halfPagesEnd);
				if ($end > $total) {
					if ($end == 0) {
						$start = $total - $paginationMaxNumbers + 1;
					}
					$end = $total;
				}
			}

			$output .= '<div class="numbers">';
			for ($i = $start; $i <= $end; $i++) {

				// Handle seo urls
				if ($seoUrls == true) {

					// Set link
					$link = $scriptUrl . '/' . $pageSlug . '/' . $i;
				}

				// Default urls
				else {

					// Set link
					$link = $this->scriptUrlWithParams;
					if (strstr($this->scriptUrlWithParams, $paramName)) {
						$link = str_replace($paramName . '=' . $page, $paramName . '=' . $i, $this->scriptUrlWithParams);
					} else {
						$link .= (strstr($link, '?') ? '&' : '?') . $paramName . '=' . $i;
					}
				}

				// View
				$output .= '<a href="' . $link . '"' . (($page == $i) ? ' class="current" aria-current="page"' : '') . ' aria-label="' . $this->sdk->lang->get('offer_link_to_page') . ': ' . $i . ' ' . $this->sdk->lang->get('general_from') . ' ' . $total . '">' . $i . '</a> ';
			}
			$output .= '</div>';
		}


		$output .= '</div>';

		if ($total > 1) {
			return $this->output($output);
		}

		return null;
	}



	/**
	 * Show offer detail page
	 */
	public function detail(object $offer, int $originalOfferId = 0): ?string
	{

		// Init
		$output = '';
		$templateData = [];
		$templateConditions = [];

		// Set view mode into session
		$_SESSION['offer_detail_view'] = true;

		// Lang attribute
		$langAttr = '';
		if ($offer->language != $this->sdk->langId) {
			$langAttr = ' lang="' . $offer->language . '"';
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: title, abstract and description
		|--------------------------------------------------------------------------
		|
		*/

		// Title
		$templateData['OFFER_TITLE'] = $offer->title;

		// Abstract
		$templateData['OFFER_ABSTRACT'] = ! empty($offer->abstract) ? $offer->abstract : '';

		// Description
		$description = '<div class="detail_text"' . $langAttr . '><p>';
		if (! empty($this->config['show_park_name'])) {
			$description .= '<strong>' . $offer->park . '</strong> - ';
		}

		// Route condition
		if (! empty($offer->route_condition_color) && ! empty($offer->route_condition)) {
			$description .= '
				<div class="route_condition ' . $offer->route_condition_color . '">' . $offer->route_condition . '</div>
			';

			// Route condition details
			if (! empty($offer->route_condition_details)) {
				$description .= '
					<div class="route_condition_details"><div class="route_condition_details_inner">' . ParksFormat::autoLink($offer->route_condition_details, 'both', true) . '</div></div>
				';
			}
		}

		// Medium description
		$mediumDescription = trim(strip_tags($offer->description_medium ?? '', '<a>'));
		$description .= '<i>' . ParksFormat::outputText($mediumDescription) . '</i></p>';

		// Long description
		$longDescription = trim($offer->description_long);
		if (! empty($longDescription) && ($longDescription != $mediumDescription)) {

			// Cut medium description from long description
			if (substr($longDescription, 0, strlen($mediumDescription)) == $mediumDescription) {
				$longDescription = trim(str_replace($mediumDescription, '', $longDescription));
			}

			// Show long description
			$description .= '<div class="description">' . ParksFormat::outputText($longDescription) . '</div>';
		}

		// Project and research fields
		if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH]) && empty($longDescription)) {
			$description .= ! empty($offer->project_initial_situation) ? '<h2 class="project_title">' . $this->sdk->lang->get('offer_project_initial_situation') . '</h2><p class="description">' . ParksFormat::outputText($offer->project_initial_situation) . '</p>' : '';
			$description .= ! empty($offer->project_goal) ? '<h2 class="project_title">' . $this->sdk->lang->get('offer_project_goal') . '</h2><p class="description">' . ParksFormat::outputText($offer->project_goal) . '</p>' : '';
			$description .= ! empty($offer->project_further_information) ? '<h2 class="project_title">' . $this->sdk->lang->get('offer_project_further_information') . '</h2><p class="description">' . ParksFormat::outputText($offer->project_further_information) . '</p>' : '';
			$description .= ! empty($offer->project_results) ? '<h2 class="project_title">' . $this->sdk->lang->get('offer_project_results') . '</h2><p class="description">' . ParksFormat::outputText($offer->project_results) . '</p>' : '';
			$description .= ! empty($offer->project_partner) ? '<h2>' . $this->sdk->lang->get('offer_partner') . '</h2><p class="description">' . ParksFormat::outputText($offer->project_partner) . '</p>' : '';
		}

		// Add online shop articles to the description
		if (($offer->online_shop_enabled == true)) {

			// Check included articles
			if (! empty($offer->articles)) {
				$description .= '
					<div class="products_overview">
						<h2 class="overview_title">' . $this->sdk->lang->get('offer_included_products') . '</h2>
				';

				// Iterate all articles
				foreach ($offer->articles as $article) {


					// Article description
					$articleDescription = '';
					$supplierIntro = '';
					$supplierName = '';
					$supplierLinkPrefix = '';
					$supplierLinkSuffix = '';
					$separator = '';


					// Article supplier
					if (! empty($article->supplier_contact)) {

						// Set supplier introduction
						$supplierIntro = '<span class="supplier"><span class="from">' . $this->sdk->lang->get('offer_from') . '</span>';

						// Set supplier name (first line)
						if (! empty($article->supplier_contact)) {
							$supplierName = substr($article->supplier_contact, 0, strpos($article->supplier_contact, "\n"));
						}

						// Find supplier URL in string
						preg_match('!(http|ftp|scp)(s)?:\/\/[a-zA-Z0-9.?%=&_\-/]+!', $article->supplier_contact, $supplierUrl);
						if (! empty($supplierUrl[0])) {
							$supplierLinkPrefix = '<a href="' . $supplierUrl[0] . '" target="_blank" class="supplier_link">';
							$supplierLinkSuffix = '</a>';
						}
					}


					// Supplier and description separator
					if (($supplierName != '') && ($article->article_description != '')) {
						$separator = ', ';
					}


					// Set article description
					$articleDescription = '
						<div class="description">
							<p>
								' . $supplierIntro . '
								' . $supplierLinkPrefix . $supplierName . $supplierLinkSuffix . '</span>'
 . $separator
 . $article->article_description . '
							</p>
						</div>
					';


					// Article labels
					$articleLabels = '';
					if (! empty($article->labels)) {

						// Iterate all labels
						foreach ($article->labels as $label) {

							// Prepare label link
							$labelLinkPrefix = $labelLinkSuffix = '';
							if (! empty($label->label_url)) {
								$labelLinkPrefix = '<a href="' . $label->label_url . '" target="_blank" title="' . $label->label_title . '">';
								$labelLinkSuffix = '</a>';
							}

							// Show park label
							$articleLabels .= '
								<div class="product_label_wrap">
									<div class="product_label">
										' . $labelLinkPrefix . '
											<img src="' . $label->label_icon . '" alt="' . $label->label_title . '">
										' . $labelLinkSuffix . '
									</div>
									<div class="honoured_by">
										' . $labelLinkPrefix . '
											' . $this->sdk->lang->get('offer_product_honoured_by') . ' ' . $label->label_title . '
										' . $labelLinkSuffix . '
									</div>
									<div class="cf"></div>
								</div>
							';
						}
					}


					// Article addons
					$articleAddons = '';
					foreach (['ingredients', 'allergens', 'nutritional_values', 'identity_label', 'quantity_indication'] as $field) {
						if (! empty($article->{'article_' . $field})) {
							$articleAddons .= '
								<div class="addon accordion_entry">
									<div class="accordion_title">
										<span class="text">' . $this->sdk->lang->get('offer_product_article_' . $field) . '</span><i class="icon">+</i>
									</div>
									<div class="accordion_content" style="display: none;">' . nl2br($article->{'article_' . $field}) . '</div>
								</div>
							';
						}
					}


					// Show article
					$description .= '
						<div class="product_entry">
							<div class="product_entry_inner">
								<h3>' . $article->article_title . '</h3>
								' . $articleDescription . '
								' . $articleLabels . '
								<div class="article_addons">
									' . $articleAddons . '
								</div>
							</div>
						</div>
					';
				}

				// Close wrap
				$description .= '			
					</div>
				';
			}


			// Set checkout button
			$templateData['OFFER_ONLINE_SHOP_CHECKOUT_BUTTON'] = '
				<div class="button_wrap">
					<a href="https://hub.parks.swiss/' . $this->sdk->langId . '/online_shop/checkout/' . $offer->offer_id . '" class="button buy_button" target="_blank">' . $this->sdk->lang->get('offer_product_shop_now') . '</a>
				</div>
			';
			$description .= $templateData['OFFER_ONLINE_SHOP_CHECKOUT_BUTTON'];
		}

		$description .= '</div>';

		$templateData['OFFER_DESCRIPTION'] = $description;


		/*
		|--------------------------------------------------------------------------
		| Placeholder: short info by offer type
		|--------------------------------------------------------------------------
		|
		*/
		$shortInfo = '';
		switch ($offer->root_category) {
			case CATEGORY_EVENT:
			case CATEGORY_BOOKING:
				if (! empty($offer->dates)) {

					// Get next date
					$now = new DateTime();
					foreach ($offer->dates as $date) {
						$dateFrom = new DateTime($date->date_from);
						$dateTo = ! empty($date->date_to) ? new DateTime($date->date_to) : null;
						if (($dateFrom > $now) || (! empty($date->date_to) && ($dateTo >= $now))) {
							$nextDate = $date;
							break;
						}
					}

					// Show label with next date
					$label = $this->sdk->lang->get('offer_next_date');
					if (! empty($nextDate)) {
						$nextDate = ParksDate::formatOfferDate(['date_from' => ParksDate::parksMysql2form($nextDate->date_from), 'date_to' => ParksDate::parksMysql2form($nextDate->date_to)], $this->sdk->lang);
						$shortInfo .= '<span>' . $label . ':</span> ' . $nextDate;
					}
				}
				break;
			case CATEGORY_PRODUCT:
				if (! empty($offer->dates)) {
					$firstDate = $offer->dates[0];
					$label = $this->sdk->lang->get('offer_saison');
					$nextDate = ParksDate::formatOfferDate(['date_from' => ParksDate::parksMysql2form($firstDate->date_from), 'date_to' => ParksDate::parksMysql2form($firstDate->date_to)], $this->sdk->lang);
					$shortInfo .= '<span>' . $label . ':</span> ' . $nextDate;
				} else if (! empty($offer->season_months)) {
					$seasonMonths = explode(',', $offer->season_months);
					$seasonMonthLabels = [];
					if (is_array($seasonMonths) && ! empty($seasonMonths)) {
						if (count($seasonMonths) == 12) {
							$shortInfo .= '<span>' . $this->sdk->lang->get('offer_saison') . ':</span> ' . $this->sdk->lang->get('offer_all_season');
						}
						else {
							foreach ($seasonMonths as $month) {
								$seasonMonthLabels[] = $this->sdk->lang->get('month_long_' . $month);
							}
							$shortInfo .= implode(', ', $seasonMonthLabels);
						}
					}
				} else {
					$shortInfo .= '<span>' . $this->sdk->lang->get('offer_saison') . ':</span> ' . $this->sdk->lang->get('offer_all_season');
				}
				break;
			case CATEGORY_ACTIVITY:
				if (! empty($offer->time_required) || ! empty($offer->time_required_minutes)) {
					$shortInfo .= '<span>' . $this->sdk->lang->get('offer_time_required') . ': </span> ' . ParksDate::activityGetTimeRequired($offer, $this->sdk->lang);
				}
				if (isset($offer->route_length) && (intval($offer->route_length) > 0)) {
					$shortInfo .= '<span>' . $this->sdk->lang->get('offer_route_length') . ':</span> ' . number_format((float) $offer->route_length, 1) . ' km ';
				}
				break;
			case CATEGORY_PROJECT:
			case CATEGORY_RESEARCH:
				if (isset($offer->duration_from) && (intval($offer->duration_from) > 0)) {
					$shortInfo .= '<span>' . $this->sdk->lang->get('offer_project_duration') . ':</span> ' . $offer->duration_from;
				}
				if (isset($offer->duration_to) && (intval($offer->duration_to) > 0)) {
					$shortInfo .= ' - ' . $offer->duration_to;
				}
				break;
			default:
				break;
		}
		$templateData['OFFER_SHORT_INFO'] = $shortInfo;


		/*
		|--------------------------------------------------------------------------
		| Placeholder: categories
		|--------------------------------------------------------------------------
		|
		*/
		$templateData['OFFER_CATEGORIES'] = '';
		foreach ($offer->categories as $category) {
			if (! in_array($category->category_id, $this->config['hidden_categories'])) {
				$templateData['OFFER_CATEGORIES'] .= '<span>' . $category->body . '</span>';
			}
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: hyperlinks
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->hyperlinks) && count($offer->hyperlinks) > 0) {
			$links = '';
			foreach ($offer->hyperlinks as $hyperlink) {
				if (in_array($this->sdk->langId, explode(',', $hyperlink->language))) {
					$linkLabel = ! empty($hyperlink->title) ? html_entity_decode($hyperlink->title) : $hyperlink->url;
					$links .= '<a href="' . $hyperlink->url . '" class="external_link" target="_blank" title="' . $this->sdk->lang->get('offer_link_more_infos') . ' ' . $linkLabel . '">' . $linkLabel . '</a><br>';
				}
			}
			if (! empty($links)) {
				$templateData['OFFER_LINKS'] = $this->renderDetailBlock($this->sdk->lang->get('offer_links'), '<p>' . $links . '</p>', 'block offer_links');
			}
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: keywords
		|--------------------------------------------------------------------------
		|
		*/
		$keywords = '';
		if (! empty($offer->keywords)) {
			$offerKeywords = explode(' ', $offer->keywords);
			if (! empty($offerKeywords)) {
				$keywords .= '<div class="offer_detail_keywords">';
				foreach ($offerKeywords as $keyword) {

					// Prepare keyword
					$labelKeyword = ParksFormat::ucfirstUtf8(str_replace('-', ' ', $keyword));

					// Show keyword
					$keywords .= '
						<span class="offer_keyword ' . strtolower($keyword) . '">' . $labelKeyword . '</span>
					';
				}
				$keywords .= '</div>';
			}
		}
		$templateData['OFFER_KEYWORDS'] = $keywords;



		/*
		|--------------------------------------------------------------------------
		| Placeholder: accessibilities
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->accessibilities)) {

			// Init accessibility
			$accessibilities = '';
			$accessibility = $offer->accessibilities;
			$accessiblityLink = $accessibility->ginto_link;

			// Show ratings
			if (! empty($accessibility->ratings)) {
				foreach ($accessibility->ratings as $rating) {
					$label = $rating->{'description_' . $this->sdk->langId};
					$accessibilities .= '
						<a href="' . $accessiblityLink . '" target="_blank">
							<img src="' . $rating->icon_url . '" class="pictogram tooltip" alt="' . $label . '" title="' . $label . '">
						</a>
					';
				}
			}

			// Show OK:GO link
			else if (! empty($accessibility->ginto_icon)) {
				$accessibilities .= '
					<a href="' . $accessiblityLink . '" target="_blank">
						<img src="' . $accessibility->ginto_icon . '" width="50">
					</a>
				';
			}

			// More info link
			$accessibilities .= '<br><a href="' . $accessiblityLink . '" target="_blank">' . $this->sdk->lang->get('offer_more_informations_accessibility') . '</a>';

			// Set template tag
			if (! empty($accessibilities)) {
				$templateData['OFFER_ACCESSIBILITIES'] = $this->renderDetailBlock($this->sdk->lang->get('offer_accessibility'), '<p>' . $accessibilities . '</p>', 'block offer_accessibilities');
			}
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: target groups
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->targetGroups) && is_array($offer->targetGroups) && ! in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {

			// Handle target group restrictions
			$tgMain = [];
			$tgSpecial = [];
			$tgRestrictions = $this->specificTargetGroups;
			foreach ($offer->targetGroups as $targetGroupId => $body) {
				if (in_array($targetGroupId, $tgRestrictions)) {
					$tgSpecial[$targetGroupId] = $body;
				} else {
					$tgMain[$targetGroupId] = $body;
				}
			}

			// Prepare main target groups
			if (! empty($tgMain)) {
				$tgMain = '<ul><li>' . implode('</li><li>', $tgMain) . '</li></ul>';
			} else {
				$tgMain = '';
			}

			// Prepare special target groups
			if (! empty($tgSpecial)) {
				$tgSpecial = '<ul><li>' . implode('</li><li>', $tgSpecial) . '</li></ul>';
			} else {
				$tgSpecial = '';
			}

			if (empty($offer->online_shop_enabled)) {
				$templateData['OFFER_TARGET_GROUPS'] =
					$this->renderDetailBlock($this->sdk->lang->get('offer_target_group_general_info'), $tgMain, 'block main_target_groups')
 . $this->renderDetailBlock($this->sdk->lang->get('offer_target_group_specific_info'), $tgSpecial, 'block specific_target_groups');
			} else {
				$templateData['OFFER_TARGET_GROUPS'] = '';
			}
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholders: addresses
		|--------------------------------------------------------------------------
		|
		*/

		// Suppliers
		if (isset($offer->suppliers) && ! empty($offer->suppliers)) {
			$suppliers = '';
			foreach ($offer->suppliers as $supplier) {
				if ($supplier['is_park_partner'] == 1) {
					$suppliers .= '<div class="partner"><i>' . $this->sdk->lang->get('offer_park_partner') . '</i></div>';
				}
				$suppliers .= '<p>' . ParksFormat::autoTextFormat($supplier['contact']) . '</p>';
			}
			$templateData['OFFER_SUPPLIERS'] = $this->renderDetailBlock($this->sdk->lang->get('offer_supplier'), $suppliers, 'block offer_suppliers');
		}

		// Institution
		if (isset($offer->institution) && ! empty($offer->institution) && ! in_array($offer->root_category, [CATEGORY_EVENT])) {
			if ($offer->institution_is_park_partner == 1) {
				$offer->institution = '<div class="partner"><i>' . $this->sdk->lang->get('offer_park_partner') . '</i></div>' . ParksFormat::autoTextFormat($offer->institution);
			}

			// Set title
			$title = $this->sdk->lang->get('offer_organizer');

			// Set placeholder
			$templateData['OFFER_INSTITUTION'] = $this->renderDetailBlock($title, $offer->institution, 'block offer_institution');
		}

		// Park
		if ($offer->contact_is_park_partner == 1) {
			$offer->contact = '<div class="partner"><i>' . $this->sdk->lang->get('offer_park_partner') . '</i></div>' . ParksFormat::autoTextFormat($offer->contact);
		}
		$templateData['OFFER_CONTACT'] = $this->renderDetailBlock($this->sdk->lang->get('offer_contact'), $offer->contact, 'block offer_contact');


		/*
		|--------------------------------------------------------------------------
		| Placeholder: subscription
		|--------------------------------------------------------------------------
		|
		*/
		if (isset($offer->subscription_mandatory) && ($offer->subscription_mandatory == true)) {
			$templateData['OFFER_SUBSCRIPTION'] = $this->getDetailSubscription($offer);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: POIs
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->poi)) {
			$templateData['OFFER_POI_LIST'] = $this->getPoiContent($offer->poi, $offer->offer_id, $offer->root_category);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: Linked routes
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->linked_routes)) {
			$templateData['OFFER_ROUTE_LIST'] = $this->getPoiContent($offer->linked_routes, $offer->offer_id, $offer->root_category);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholders: detail pages
		|--------------------------------------------------------------------------
		|
		*/
		switch ($offer->root_category) {
			case CATEGORY_EVENT:
				$templateData['OFFER_EVENT_DETAIL'] = $this->getDetailEvent($offer);
				$templateConditions['OFFER_EVENT'] = true;
				break;
			case CATEGORY_PRODUCT:
				$templateData['OFFER_PRODUCT_DETAIL'] = $this->getDetailProduct($offer);
				$templateConditions['OFFER_PRODUCT'] = true;
				break;
			case CATEGORY_BOOKING:
				$templateData['OFFER_BOOKING_DETAIL'] = $this->getDetailBooking($offer);
				$templateConditions['OFFER_BOOKING'] = true;
				break;
			case CATEGORY_ACTIVITY:
				$templateData['OFFER_ACTIVITY_DETAIL'] = $this->getDetailActivity($offer);
				$templateConditions['OFFER_ACTIVITY'] = true;
				break;
			case CATEGORY_PROJECT:
			case CATEGORY_RESEARCH:
				$templateData['OFFER_PROJECT_DETAIL'] = $this->getDetailProject($offer);
				$templateConditions['OFFER_PROJECT'] = true;
				break;
			default:
				break;
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: images
		|--------------------------------------------------------------------------
		|
		*/
		$configImageEnlargement = isset($this->config['image_enlargement']) ? $this->config['image_enlargement'] : false;
		$configThumbnailSize = isset($this->config['detail_thumbnail_size']) ? $this->config['detail_thumbnail_size'] : 'medium';
		if (isset($offer->images) && is_array($offer->images) && ! empty($offer->images)) {
			$templateData['OFFER_IMAGES'] = '<div class="detail_pictures pictures" aria-hidden="true">';
			foreach ($offer->images as $image) {
				$dataCaption = '';
				
				if (! empty($image->copyright)) {
					$imageCopyright = '';
					if (!strstr($image->copyright, '©') && !strstr($image->copyright, '&copy; ')) {
						$imageCopyright .= '&copy;';
					}
					$imageCopyright .= $image->copyright;
					$dataCaption = 'data-caption="' . $imageCopyright . '"';
				}
				
				$templateData['OFFER_IMAGES'] .= '<div class="attachment picture">';
				
				if ($configImageEnlargement === true) {
					$templateData['OFFER_IMAGES'] .= '<a href="' . $image->large . '" class="offer_image" rel="offer_images" ' . $dataCaption . ' data-fancybox="gallery"  aria-hidden="true">';
				}
				
				$templateData['OFFER_IMAGES'] .= '<img src="' . $image->{$configThumbnailSize} . '" alt="" class="offer_detail_image">';
				
				if (! empty($image->copyright)) {
					$templateData['OFFER_IMAGES'] .= '<div class="image_description">' . $imageCopyright . '</div>';
				}

				if ($configImageEnlargement === true) {
					$templateData['OFFER_IMAGES'] .= '</a>';
				}

				$templateData['OFFER_IMAGES'] .= '</div>';
			}
			$templateData['OFFER_IMAGES'] .= '</div>';
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: documents
		|--------------------------------------------------------------------------
		|
		*/
		if (isset($offer->documents) && (count($offer->documents) > 0)) {
			$documents = '';
			foreach ($offer->documents as $document) {
				$documentLabel = ($document->title ? $document->title : $document->url);
				$documents .= '<a href="' . $document->url . '" class="download_link" target="_blank" title="' . $this->sdk->lang->get('offer_link_download') . ' ' . $documentLabel . '">' . $documentLabel . '</a><br>';
			}
			$templateData['OFFER_DOCUMENTS'] = $this->renderDetailBlock($this->sdk->lang->get('offer_documents_public'), $documents, 'block offer_documents');
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: internal informations
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->costs) || ! empty($offer->funding) || ! empty($offer->partner) || ! empty($offer->remarks) || ! empty($offer->hyperlinks_intern) || ! empty($offer->documents_intern)) {

			$internalInfos = '';

			// I18n fields
			foreach (['costs', 'funding', 'partner', 'remarks'] as $key) {
				if (! empty($offer->{$key})) {
					$internalInfos .= trim($this->renderDetailBlock($this->sdk->lang->get('offer_' . $key), $offer->{$key}, 'block ' . $key));
				}
			}

			// Internal hyperlinks
			if (! empty($offer->hyperlinks_intern)) {
				$links = '';
				foreach ($offer->hyperlinks_intern as $hyperlink) {
					if (in_array($this->sdk->langId, explode(',', $hyperlink->language))) {
						$linkLabel = ! empty($hyperlink->title) ? html_entity_decode($hyperlink->title) : $hyperlink->url;
						$links .= '<a href="' . $hyperlink->url . '" class="external_link" target="_blank" title="' . $this->sdk->lang->get('offer_link_more_infos') . ' ' . $linkLabel . '">' . $linkLabel . '</a><br>';
					}
				}
				if (! empty($links)) {
					$internalInfos .= $this->renderDetailBlock($this->sdk->lang->get('offer_internal_hyperlinks'), '<p>' . $links . '</p>', 'block offer_links_intern');
				}
			}

			// Internal documents
			if (! empty($offer->documents_intern)) {
				$documents = '';
				foreach ($offer->documents_intern as $document) {
					$documentLabel = ($document->title ? $document->title : $document->url);
					$documents .= '<a href="' . $document->url . '" class="download_link" target="_blank" title="' . $this->sdk->lang->get('offer_link_download') . ' ' . $documentLabel . '">' . $documentLabel . '</a><br>';
				}
				$internalInfos .= $this->renderDetailBlock($this->sdk->lang->get('offer_internal_documents'), $documents, 'block offer_documents_intern');
			}

			$templateData['OFFER_INTERNAL_INFOS'] = $internalInfos;
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: print button (deprecated)
		|--------------------------------------------------------------------------
		|
		*/
		$templateData['OFFER_PRINT_LINK'] = '';


		/*
		|--------------------------------------------------------------------------
		| Placeholder: back link button
		|--------------------------------------------------------------------------
		|
		*/
		$templateData['OFFER_BACK_LINK'] = '';

		// Javascript close window button
		if (! empty($this->sdk->mapOptions['link_target']) && ($this->sdk->mapOptions['link_target'] == '_blank') && empty($this->sdk->mapOptions['ignore_close_window'])) {
			$templateData['OFFER_BACK_LINK'] = '<a class="close_window back_to_overview" href="javascript:void(0);" title="' . $this->sdk->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->sdk->lang->get('back_to_overview') . '</span></a>';
		}

		// Default back links
		else {

			// Is poi detail
			if (! empty($_GET['poi']) || ! empty($originalOfferId)) {

				// On poi view – set original offer id
				$originalOfferId = ! empty($_GET['poi']) ? intval($_GET['poi']) : intval($originalOfferId);

				// Get original offer (title)
				$originalOffer = $this->sdk->model->getOffer($originalOfferId, true);

				// Get original offer category
				$originalCategoryId = ! empty($_GET['original_category']) ? intval($_GET['original_category']) : $originalOffer->root_category;

				// Set back button label
				$backButtonLabel = ($originalCategoryId != CATEGORY_PROJECT) ? $this->sdk->lang->get('back_to_route') : $this->sdk->lang->get('back_to_project');

				// Javascript close window button
				if (! empty($this->config['poi_listing_link_target']) && ($this->config['poi_listing_link_target'] == '_blank')) {
					$templateData['OFFER_BACK_LINK'] = '<a class="close_window back_to_overview" href="javascript:void(0);" title="' . $this->sdk->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $backButtonLabel . '</span></a>';
				}

				// Back to overview button
				else {

					// Handle seo urls
					if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

						// Set seo back link
						$backLink = $this->getSeoDetailUrl($originalOfferId, $originalOffer->title) . '#tab_5';
					}

					// Default urls
					else {
						$backLink = $this->scriptUrl . (strstr($this->scriptUrl, '?') ? '&amp' : '?') . 'offer=' . $originalOfferId . (isset($_GET['tab']) ? ('#' . $_GET['tab']) : '');
					}

					// Set placeholder
					$templateData['OFFER_BACK_LINK'] = '<a class="back_to_overview" href="' . $backLink . '" title="' . $this->sdk->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $backButtonLabel . '</span></a>';
				}
			}

			// Optional: force browser history back (e.g. external referer)
			elseif (! empty($_GET['force_back_to_referer']) && ! empty($_SERVER['HTTP_REFERER'])) {
				$templateData['OFFER_BACK_LINK'] = '<a class="go_back back_to_overview" href="javascript:void(0);" title="' . $this->sdk->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->sdk->lang->get('back_to_overview') . '</span></a>';
			}

			// Default: overview URL (list/map tab restored by parks.min.js)
			else {
				$backLink = $this->getOverviewBackUrl();
				$templateData['OFFER_BACK_LINK'] = '<a class="back_to_overview" href="' . $backLink . '" title="' . $this->sdk->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->sdk->lang->get('back_to_overview') . '</span></a>';
			}
		}

		// Single offer mode
		if ($this->sdk->singleMode === true) {
			$templateData['OFFER_BACK_LINK'] = '';
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: map
		|--------------------------------------------------------------------------
		|
		*/
		$templateData['OFFER_MAP'] = $this->getDetailMap($offer);


		/*
		|--------------------------------------------------------------------------
		| Merge detail template tags
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($this->detailTemplateTags) && is_array($this->detailTemplateTags)) {
			$templateData = array_merge($templateData, $this->detailTemplateTags);
		}


		/*
		|--------------------------------------------------------------------------
		| Overwrite template data in MyView
		|--------------------------------------------------------------------------
		|
		*/
		$templateData = $this->overwriteTemplateData($templateData, $offer);


		/*
		|--------------------------------------------------------------------------
		| Compile template
		|--------------------------------------------------------------------------
		|
		*/
		$output = $this->sdk->compileTemplate('detail', $templateData, $templateConditions);

		return $this->output($output);
	}



	/**
	 * Get «Add to favorites» link
	 */
	public function getFavoritesLink(int $offerId): string
	{

		$output = '';

		// Check if favorites are enabled
		if (! empty($this->config['favorites_extension_available']) && ! empty($this->config['favorites_script_path'])) {

			if (empty($offerId)) {
				return '';
			}

			// Set link attributes
			$favoriteLinkTitle = in_array($offerId, $this->sdk->favorites) ? $this->sdk->lang->get('favorites_remove') : $this->sdk->lang->get('favorites_add');
			$favoriteClass = in_array($offerId, $this->sdk->favorites) ? 'active' : '';
			
			// Output toggle favorite link
			$output = '
				<div class="favorite ' . $favoriteClass . '">
					<a href="' . $this->config['favorites_script_path'] . '/favorites.php?offer_id=' . $offerId . '" data-offer-id="' . $offerId . '" data-title="' . $favoriteLinkTitle . '" data-label-add="' . $this->sdk->lang->get('favorites_add') . '" data-label-remove="' . $this->sdk->lang->get('favorites_remove') . '" class="tooltip" title="' . $favoriteLinkTitle . '" role="button"><span aria-hidden="true">i</span></a>
				</div>
			';

		}

		return $output;
	}



	/**
	 * Get detail description
	 */
	protected function getOfferDates(object $offer): string
	{

		$dates = '';

		if (isset($offer->dates) && ! in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {

			// Count all dates
			$now = new DateTime();
			$dateCounter = 0;
			$totalDates = 0;
			if (! empty($offer->dates)) {
				foreach ($offer->dates as $date) {

					// Prepare dates
					$dateFrom = new DateTime($date->date_from);
					$dateTo = null;
					if (! empty($date->date_to)) {
						$dateTo = new DateTime($date->date_to);
					}

					// Count only future events (exception: category product)
					if (($now < $dateFrom) || (! empty($dateTo) && ($now < $dateTo)) || ($offer->root_category == CATEGORY_PRODUCT)) {
						$totalDates++;
					}
				}
			}

			// Iterate all dates
			if (! empty($offer->dates)) {
				$dates = '<div class="dates">';
				foreach ($offer->dates as $date) {

					// Prepare dates
					$dateFrom = new DateTime($date->date_from);
					$dateTo = null;
					if (! empty($date->date_to)) {
						$dateTo = new DateTime($date->date_to);
					}

					// Prepare display limit
					$detailDateDisplay = (! empty($this->sdk->config['detail_limit_dates']) && ($this->sdk->config['detail_limit_dates'] > 0)) ? intval($this->sdk->config['detail_limit_dates']) : 4;

					// Show only future events (exception: category product)
					if (($now < $dateFrom) || (! empty($dateTo) && ($now < $dateTo)) || ($offer->root_category == CATEGORY_PRODUCT)) {

						// Count date
						$dateCounter++;

						// Add date
						$dates .= ParksDate::formatOfferDate(['date_from' => ParksDate::parksMysql2form($date->date_from), 'date_to' => ParksDate::parksMysql2form($date->date_to)], $this->sdk->lang) . '<br>';

						// Start hidden layer
						if ($totalDates > $detailDateDisplay) {
							if ($detailDateDisplay == $dateCounter) {
								$dates .= '</div><div class="hidden_content" style="display: none;">';
							}

							// End hidden layer
							if ($totalDates == $dateCounter) {
								$dates .= '</div>';
							}
						}
					}
				}

				// Show more/less buttons
				if ($totalDates > $detailDateDisplay) {
					$dates .= '
						<div class="more_less_buttons" aria-hidden="true">
							<a class="show_more" href="javascript:void(0);">' . $this->sdk->lang->get('show_more') . '</a>
							<a class="show_less" href="javascript:void(0);" style="display: none;">' . $this->sdk->lang->get('show_less') . '</a>
						</div>
					';
				} else {
					$dates .= '</div>';
				}
			} else if (! empty($offer->season_months)) {
				$seasonMonths = explode(',', $offer->season_months);
				$seasonMonthLabels = [];
				if (is_array($seasonMonths) && ! empty($seasonMonths)) {
					if (count($seasonMonths) == 12) {
						$dates .= '<p>' . $this->sdk->lang->get('offer_all_season') . '</p>';
					}
					else {
						foreach ($seasonMonths as $month) {
							$seasonMonthLabels[] = $this->sdk->lang->get('month_long_' . $month);
						}
						$dates .= implode(', ', $seasonMonthLabels);
					}
				}
			} else {
				$dates .= '<p>' . $this->sdk->lang->get('offer_all_season') . '</p>';
			}

			// Date condition
			if (empty($offer->dates) || ($totalDates > 0)) {

				// Set title
				$datesTitle = $this->sdk->lang->get('offer_dates');
				if ($offer->root_category == CATEGORY_BOOKING) {
					$datesTitle = $this->sdk->lang->get('offer_execution_times');
				}
				if (in_array($offer->root_category, [CATEGORY_PRODUCT, CATEGORY_ACTIVITY])) {
					$datesTitle = $this->sdk->lang->get('offer_saison');
				}

				// Output
				$dates = $this->renderDetailBlock($datesTitle, $dates, 'block offer_dates');
			}
		}

		return $dates;
	}



	/**
	 * Overwrite template data before they are loaded
	 */
	public function overwriteTemplateData(array $templateData, object $offer): array
	{

		return $templateData;
	}



	/**
	 * Get POI content
	 */
	protected function getPoiContent(array $pois, int $offerId, ?int $originalCategory): ?string
	{

		$offers = $this->sdk->showOfferPoiList($pois);
		$offers = ['data' => $offers, 'total' => count($offers)];
		$output = $this->listOffers($offers, true, $offerId, $originalCategory);
		return $output;
	}



	/**
	 * Get event detail
	 */
	protected function getDetailEvent(object $offer): string
	{

		// Load template data		
		$templateData['OFFER_ADDITIONAL_INFO'] = $this->renderAdditionalInfos($offer);
		$templateData['OFFER_DATES'] = $this->getOfferDates($offer);
		$templateData['OFFER_EVENT_LOCATION'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_event_location'), $offer->institution, 'block event_location'));
		$templateData['OFFER_EVENT_LOCATION_SHORT'] = ! empty($offer->institution_location) ? $offer->institution_location : '';
		$templateData['OFFER_EVENT_DATE_DETAILS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_date_details'), $offer->details, 'block date_details'));
		$templateData['OFFER_EVENT_LOCATION_DETAILS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_location_details'), $offer->location_details, 'block location_details'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_EVENT_TRANSPORT'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_EVENT_PRICE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return $this->compileOutput($templateData);

	}



	/**
	 * Get product detail
	 */
	protected function getDetailProduct(object $offer): string
	{

		$templateData['OFFER_ADDITIONAL_INFO'] = $this->renderAdditionalInfos($offer);
		if (empty($offer->online_shop_enabled)) {
			$templateData['OFFER_DATES'] = $this->getOfferDates($offer);
		}
		$templateData['OFFER_PRODUCT_OPENING_HOURS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_opening_hours'), $offer->opening_hours, 'block opening_hours'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_PRODUCT_INFRASTRUCTURE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_infrastructure'), $this->getDetailInfrastructure($offer), 'block infrastructure'));

		// Load template data
		if (! empty($offer->online_shop_enabled)) {

			// Show article price
			$templateData['OFFER_PRODUCT_PRICE'] = '
				<div class="block product_price">
					<div class="description">
						<h2>' . $this->sdk->lang->get('offer_product_price') . '</h2>
						<div class="price">
							<span class="currency">CHF</span>
							<span class="value">' . $offer->online_shop_price . '</span>
						</div>
						<div class="order_information_wrap">
							<div class="order_information payment_info">
								<span class="title">' . $this->sdk->lang->get('offer_product_payment_modalities') . ':</span>
								<span class="description">' . $offer->online_shop_payment_terms . '</span>
							</div>
							<div class="order_information delivery_info">
								<span class="title">' . $this->sdk->lang->get('offer_product_delivery_conditions') . ':</span>
								<span class="description">' . $offer->online_shop_delivery_conditions . '</span>
							</div>
						</div>
					</div>
				</div>
			';
		} else {
			$templateData['OFFER_PRODUCT_PRICE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));
		}

		// Compile template data
		return $this->compileOutput($templateData);
	}



	/**
	 * Get booking detail
	 */
	protected function getDetailBooking(object $offer): string
	{

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = $this->renderAdditionalInfos($offer);
		$templateData['OFFER_DATES'] = $this->getOfferDates($offer);
		$templateData['OFFER_BOOKING_GROUPS'] = $this->getDetailGroups($offer);
		$templateData['OFFER_BOOKING_BENEFITS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_benefits'), $offer->benefits, 'block benefits'));
		$templateData['OFFER_BOOKING_REQUIREMENTS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_requirements'), $offer->requirements, 'block requirements'));
		$templateData['OFFER_BOOKING_ACCOMMODATIONS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_accommodation'), $this->getAccommodations($offer), 'block accommodations'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_BOOKING_TRANSPORT'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_BOOKING_PRICE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return $this->compileOutput($templateData);
	}



	/**
	 * Get activity detail
	 */
	protected function getDetailActivity(object $offer): string
	{

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = $this->renderAdditionalInfos($offer);
		$templateData['OFFER_DATES'] = $this->getOfferDates($offer);
		$templateData['OFFER_ACTIVITY_ROUTE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_route_info'), $this->getRouteDetails($offer), 'block route_info rwd-info-block'));
		$templateData['OFFER_ACTIVITY_ARRIVAL'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_arrival'), $this->getRouteStartStop($offer), 'block arrival rwd-info-block'));
		$templateData['OFFER_ACTIVITY_SIGNALIZATION'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_signalization'), $offer->signalization, 'block signalization'));
		$templateData['OFFER_ACTIVITY_SAFETY_INSTRUCTIONS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_safety_instructions'), $offer->safety_instructions, 'block safety_instructions'));
		$templateData['OFFER_ACTIVITY_MATERIAL_RENT'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_material_rent'), $offer->material_rent, 'block material_rent'));
		$templateData['OFFER_ACTIVITY_INFRASTRUCTURE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_infrastructure'), $this->getDetailInfrastructure($offer), 'block infrastructure'));
		$templateData['OFFER_ACTIVITY_PRICE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));
		$templateData['OFFER_ACTIVITY_CATERING'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_catering_informations'), $offer->catering_informations, 'block catering_informations'));

		// Compile template data
		return $this->compileOutput($templateData);
	}



	/**
	 * Get project detail
	 */
	protected function getDetailProject(object $offer): string
	{

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = $this->renderAdditionalInfos($offer);
		$templateData['OFFER_PROJECT_DURATION'] = $this->formatProjectDuration($offer->duration_from, $offer->duration_to, $offer->duration_from_month, $offer->duration_to_month);
		$templateData['OFFER_PROJECT_STATUS'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_project_status'), $this->config['project_status_' . $this->sdk->langId][$offer->project_status], 'block project_status'));

		// Compile template data
		return $this->compileOutput($templateData);
	}



	/**
	 * Get overview map
	 */
	public function getOverviewMap(array $offers, bool $poiDetail = false, int $offerId = 0): ?string
	{

		if (empty($offers)) {
			return '';
		}

		// Collect offers by categories
		$categoriesOffersView = ParksMap::formatOffersByCategoryJs($offers);

		// Full detail URL template — the map only fills {slug} and {offer_id}
		$popupLinkTemplate = $this->getPopupLinkTemplate();

		$mapView = '
			<div id="swiss-parks-map" style="min-height: 600px;"></div>
			<script>
				window.swissParksMapConfig = {
					containerId: 				\'swiss-parks-map\',
					initializeOnLoad: 			' . (! empty($this->sdk->mapOptions['map_initialize_on_load']) ? 'true' : 'false') . ',
					showLayersAtStart: 			' . (! isset($this->sdk->mapOptions['show_layers_at_start']) || ! empty($this->sdk->mapOptions['show_layers_at_start']) ? 'true' : 'false') . ',
					parkPerimeterVisibility: 	' . (! empty($this->sdk->mapOptions['parkperimeter_visibility']) ? 'true' : 'false') . ',
					linkTarget: 				\'' . (! empty($this->sdk->mapOptions['link_target']) ? $this->sdk->mapOptions['link_target'] : '_self') . '\',
					fullHeight: 				' . (! empty($this->sdk->mapOptions['full_height']) ? 'true' : 'false') . ',
					enableScrollProtection: 	' . (! empty($this->sdk->mapOptions['full_height']) ? 'false' : 'true') . ',
					offerLayersNoGrouping: 		' . (! empty($this->sdk->mapOptions['do_not_group_categories_in_legend']) ? 'true' : 'false') . ',
					language: 					\'' . $this->sdk->langId . '\',
					mode: 						\'filter\',
					detailOfferId: 				null,
					statePersistence: 			{ shareableUrl: true, rememberSession: true },
					offersData: 				{ categories: { ' . $categoriesOffersView . ' }},
					popupLinkTemplate: 			\'' . $popupLinkTemplate . '\',
					' . (! empty($this->config['park_id']) ? '
					customWfsLayers: 			[' . $this->getMapLayers() . '],
					parksAbbreviation: 			\'' . $this->config['parks'][$this->config['park_id']] . '\',
					' : '') . '
					' . $this->getHostLayerOptions() . '
					apiKey: 					\'' . $this->config['api_hash'] . '\'
				};
			</script>
		';

		return $this->output($mapView);
	}



	/**
	 * Overview URL for the detail back link (SEO-aware)
	 *
	 * Tab restore (#tab_1 list / #tab_2 map) is handled client-side by parks.min.js.
	 */
	protected function getOverviewBackUrl(): string
	{

		$backLink = $this->scriptUrl;

		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
			$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
			$detailSegmentPos = strpos($this->scriptUrl, '/' . $detailSlug);
			if ($detailSegmentPos !== false) {
				$backLink = substr($this->scriptUrl, 0, $detailSegmentPos);
			}
		}

		return $backLink;
	}



	/**
	 * Get detail map
	 */
	protected function getDetailMap(object $offer): string
	{

		if (empty($offer) || empty($offer->offer_id)) {
			return '';
		}

		// Get POIs of this offer
		$pois = [];
		if (! empty($offer->poi) && (in_array($offer->root_category, [CATEGORY_ACTIVITY, CATEGORY_PROJECT, CATEGORY_RESEARCH]))) {
			$pois = $this->sdk->showOfferPoiList($offer->poi);
		}

		// Collect offers by categories
		$categoriesOffersView = ParksMap::formatOffersByCategoryJs([$offer] + $pois, true);

		// Full detail URL template — the map only fills {slug} and {offer_id}
		$popupLinkTemplate = $this->getPopupLinkTemplate();

		// Prepare map view
		$mapView = '
			<div id="swiss-parks-map" style="min-height: 600px;"></div>
			<div id="elevation-profile-container"></div>
			<script>
				window.swissParksMapConfig = {
					containerId: 				\'swiss-parks-map\',
					initializeOnLoad: 			' . (! empty($this->sdk->mapOptions['map_initialize_on_load']) ? 'true' : 'false') . ',
					showLayersAtStart: 			false,
					parkPerimeterVisibility: 	' . (! empty($this->sdk->mapOptions['parkperimeter_visibility']) ? 'true' : 'false') . ',
					linkTarget: 				\'' . (! empty($this->sdk->mapOptions['link_target']) ? $this->sdk->mapOptions['link_target'] : '_self') . '\',
					fullHeight: 				' . (! empty($this->sdk->mapOptions['full_height']) ? 'true' : 'false') . ',
					enableScrollProtection: 	' . (! empty($this->sdk->mapOptions['full_height']) ? 'false' : 'true') . ',
					language: 					\'' . $this->sdk->langId . '\',
					mode: 						\'detailmap\',
					detailOfferId: 				\'' . $offer->offer_id . '\',
					offersData: 				{ categories: { ' . $categoriesOffersView . ' }},
					popupLinkTemplate: 			\'' . $popupLinkTemplate . '\',
					parksAbbreviation: 			\'' . $this->config['parks'][$offer->park_id] . '\',
					' . $this->getHostLayerOptions() . '
					apiKey: 					\'' . $this->config['api_hash'] . '\',
					disableSidebar: 				true,
					disableCoordinateDisplayBanner: true
				};
			</script>
		';

		return $mapView;

	}



	/**
	 * layersToRemove / layersToAdd from map_options for swissParksMapConfig
	 */
	protected function getHostLayerOptions(): string
	{

		$out = '';

		$remove = ParksMap::mapLayerEntries($this->sdk->mapOptions['layers_to_remove'] ?? []);
		if (! empty($remove)) {
			$out .= 'layersToRemove: ' . json_encode($remove, JSON_UNESCAPED_SLASHES) . ',' . "\n\t\t\t\t\t";
		}

		$add = ParksMap::mapLayerEntries($this->sdk->mapOptions['layers_to_add'] ?? []);
		if (! empty($add)) {
			$out .= 'layersToAdd: ' . json_encode($add, JSON_UNESCAPED_SLASHES) . ',' . "\n\t\t\t\t\t";
		}

		return $out;
	}



	/**
	 * Build the offer detail URL template for the interactive map
	 */
	protected function getPopupLinkTemplate(): string
	{

		$origin = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
			return $origin . $this->getSeoDetailUrl() . '/{slug}-{offer_id}';
		}

		$paramName = $this->config['url_param_prefix'] . 'offer';
		$separator = strstr($this->scriptUrl, '?') ? '&' : '?';

		return $origin . $this->scriptUrl . $separator . $paramName . '={offer_id}';
	}



	/**
	 * Get map layers
	 */
	protected function getMapLayers(): string
	{

		$mapLayers = $this->sdk->model->getCustomLayers();

		if (empty($mapLayers)) {
			return '';
		}
	
		$mapLayerView = '';

		foreach ($mapLayers as $layer) {

			// Popup title: prefer language-specific free-text template, then language-less fallback
			$popupTitle = $layer->i18n[$this->sdk->langId]['popup_title']
				?? (! empty($layer->popup_title) ? $layer->popup_title : null)
				?? ('Layer ' . $layer->layer_position);

			// titleField fallback: first attribute name inside {…}
			$titleField = $popupTitle;
			if (preg_match('/\{([^}]+)\}/', $popupTitle, $matches)) {
				$titleField = $matches[1];
			}

			$popupTitleEscaped = str_replace(["'", "\r\n", "\n"], ["\'", ' ', ' '], $popupTitle);
			$titleFieldEscaped = str_replace(["'", "\r\n", "\n"], ["\'", ' ', ' '], $titleField);

			// Map layer
			$mapLayerView .= '
				{
					layer: {
						id: \'custom-layer-' . md5($layer->map_layer_id) . '\',
						url: \'' . $layer->url . '\',
						title: \'' . ($layer->i18n[$this->sdk->langId]['layer_title'] ?? 'Layer ' . $layer->layer_position) . '\',
						options: { outFields: [\'*\'] }
					},
					target: {
						categoryId: \'' . ($layer->layer_category ?? 'additional') . '\',
						position: ' . $layer->layer_position . ',
					},
					popup: {
						titleTemplate: \'' . $popupTitleEscaped . '\',
						titleField: \'' . $titleFieldEscaped . '\',
						imageUrl: \'' . (! empty($layer->popup_logo) ? $this->config['base_url'] . $layer->popup_logo : '') . '\',
						contentTemplate: \'' . str_replace(["'", "\r\n", "\n"], ["\'", "<br>", "<br>"], trim($layer->i18n[$this->sdk->langId]['popup_content'] ?? '')) . '\',
					},
					visible: ' . ($layer->visible_by_default ? 'true' : 'false') . '
				}' . ($layer->map_layer_id != end($mapLayers)->map_layer_id ? ',' : '') . '
			';
		}

		return $mapLayerView;
	}



	/**
	 * Get subscription detail
	 */
	protected function getDetailSubscription(object $offer): string
	{

		$return = '
			<div class="detail_text">
				<p>
					' . (($offer->subscription_mandatory == 2) ? $this->sdk->lang->get('offer_subscription_info_recommended') : $this->sdk->lang->get('offer_subscription_info_mandatory')) . '
				</p>
		';

		// Subscription details
		if (! empty($offer->subscription_details) && ($offer->online_subscription_enabled == false)) {
			$return .= '
				<p>
					' . ParksFormat::autoTextFormat($offer->subscription_details) . '
				</p>
			';
		}

		// Subscription contact
		if (! empty($offer->subscription_contact)) {
			$return .= '
				<h2>' . $this->sdk->lang->get('offer_subscription_contact') . '</h2>
				<p>
					' . ParksFormat::autoTextFormat($offer->subscription_contact) . '
				</p>
			';
		}

		// Online subscription link
		if ($offer->online_subscription_enabled == true) {
			$return .= '
				<div class="text_right">
					<a class="sign_in button" target="_blank" href="' . $this->config['base_url'] . $this->sdk->langId . '/subscription/subscriber/' . $offer->offer_id . '">' . $this->sdk->lang->get('offer_subscription_subscribe_now') . '</a>
				</div>
			';
		} else if (! empty($offer->subscription_link)) {
			$return .= '
				<div class="text_right">
					<a class="sign_in button" target="_blank" href="' . $offer->subscription_link . '">' . $this->sdk->lang->get('offer_subscription_subscribe_now') . '</a>
				</div>
			';
		}

		$return .= '</div>';

		return $return;
	}



	/**
	 * Get infrastructure details
	 */
	protected function getDetailInfrastructure(object $offer): string
	{

		// Other infrastructure
		$otherInfrastructure = '';
		if (! empty($offer->other_infrastructure)) {
			$otherInfrastructure = '<p>' . ParksFormat::autoTextFormat($offer->other_infrastructure) . '</p>';
		}

		// Infrastructure
		$infrastructure = [];
		if (! empty($offer->number_of_rooms)) {
			$infrastructure[] = sprintf($this->sdk->lang->get('offer_rooms'), $offer->number_of_rooms);
		}
		if (! empty($offer->has_conference_room)) {
			$infrastructure[] = $this->sdk->lang->get('offer_conference_room');
		}
		if (! empty($offer->has_playground)) {
			$infrastructure[] = $this->sdk->lang->get('offer_playground');
		}
		if (! empty($offer->has_picnic_place)) {
			$infrastructure[] = $this->sdk->lang->get('offer_picnic_place');
		}
		if (! empty($offer->has_fireplace)) {
			$infrastructure[] = $this->sdk->lang->get('offer_fireplace');
		}
		if (! empty($offer->has_washrooms)) {
			$infrastructure[] = $this->sdk->lang->get('offer_washroom');
		}
		$infrastructure = (count($infrastructure) > 0) ? '<ul><li>' . implode('</li><li>', $infrastructure) . '</li></ul>' : '';

		// Return details
		return $otherInfrastructure . $infrastructure;
	}



	/**
	 * Get accommodations
	 */
	protected function getAccommodations(object $offer): string
	{

		$accommodations = '';

		if (isset($offer->accommodations) && ! empty($offer->accommodations)) {
			foreach ($offer->accommodations as $accommodation) {
				if (! empty($accommodation['is_park_partner'])) {
					$accommodations .= '<div class="partner"><i aria-hidden="true">' . $this->sdk->lang->get('offer_park_partner') . '</i></div>';
				}
				$accommodations .= '<p>' . ParksFormat::autoTextFormat($accommodation['contact']) . '</p>';
			}
		}

		return $accommodations;
	}



	/**
	 * Get route details
	 */
	protected function getRouteDetails(object $offer): string
	{

		$routeDetails = '';

		if (! empty($offer)) {
			if (! empty($offer->route_length) && ($offer->route_length > 0)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_route_length') . '</dt><dd>' . ((intval($offer->route_length) > 0) ? number_format((float) $offer->route_length, 1) : '') . ' ' . $this->sdk->lang->get('offer_route_length_km') . '</dd>';
			}
			if (! empty($offer->untarred_route_length) && ($offer->untarred_route_length > 0)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_untarred_route_length') . '</dt><dd>' . ((intval($offer->untarred_route_length) > 0) ? $offer->untarred_route_length : '') . ' ' . $this->sdk->lang->get('offer_route_length_km') . '</dd>';
			}
			if (! empty($offer->altitude_differential) && ($offer->altitude_differential > 0)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_altitude_differential') . '</dt><dd>' . $offer->altitude_differential . ' ' . $this->sdk->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->altitude_ascent) && ($offer->altitude_ascent > 0)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_altitude_ascent') . '</dt><dd>' . $offer->altitude_ascent . ' ' . $this->sdk->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->altitude_descent) && ($offer->altitude_descent > 0)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_altitude_descent') . '</dt><dd>' . $offer->altitude_descent . ' ' . $this->sdk->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->time_required) || ! empty($offer->time_required_minutes)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_time_required') . '</dt><dd>' . ParksDate::activityGetTimeRequired($offer, $this->sdk->lang) . '</dd>';
			}
			if (! empty($offer->level_technics)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_level_technics') . '</dt><dd>' . $this->sdk->model->levels[$offer->level_technics] . '</dd>';
			}
			if (! empty($offer->level_condition)) {
				$routeDetails .= '<dt>' . $this->sdk->lang->get('offer_level_condition') . '</dt><dd>' . $this->sdk->model->levels[$offer->level_condition] . '</dd>';
			}
			if (! empty($routeDetails)) {
				$routeDetails = '<dl>' . $routeDetails . '</dl>';
			}
		}

		return $routeDetails;
	}



	/**
	 * Show offer start and stop place
	 */
	protected function getRouteStartStop(object $offer): string
	{

		$startStopPlace = '';

		if (! empty($offer)) {

			if (! empty($offer->start_place_info) || ! empty($offer->goal_place_info)) {
				$startStopPlace .= '<dl>';
			}

			// Start
			if (! empty($offer->start_place_info)) {
				$startStopPlace .= '<dt>' . $this->sdk->lang->get('offer_start_place') . '</dt><dd>' . nl2br($offer->start_place_info);
				if (! empty($offer->start_place_altitude)) {
					$startStopPlace .= ' (' . $this->sdk->lang->get('offer_altitude') . ': ' . $offer->start_place_altitude . ' ' . $this->sdk->lang->get('offer_altitude_meter') . ')';
				}
				$startStopPlace .= '</dd>';
				if (! empty($offer->public_transport_start) && (strlen($offer->public_transport_start) >= $this->config['min_chars_sbb_link'])) {
					$startStopPlace .= '<dt>' . $this->sdk->lang->get('offer_public_transport_stop') . '</dt><dd>' . $offer->public_transport_start . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_start) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a></dd>';
				}
			}

			// Stop / goal
			if (! empty($offer->goal_place_info)) {
				$startStopPlace .= '<div class="content-margin-cf"></div><dt>' . $this->sdk->lang->get('offer_goal_place') . '</dt><dd>' . nl2br($offer->goal_place_info);
				if (! empty($offer->goal_place_altitude)) {
					$startStopPlace .= ' (' . $this->sdk->lang->get('offer_altitude') . ': ' . $offer->goal_place_altitude . ' ' . $this->sdk->lang->get('offer_altitude_meter') . ')';
				}
				$startStopPlace .= '</dd>';
				if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
					$startStopPlace .= '<dt>' . $this->sdk->lang->get('offer_public_transport_stop') . '</dt><dd>' . $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a></dd>';
				}
			}

			if (! empty($offer->start_place_info) || ! empty($offer->goal_place_info)) {
				$startStopPlace .= '</dl>';
			}
		}

		return $startStopPlace;
	}



	/**
	 * Get group details
	 */
	protected function getDetailGroups(object $offer): string
	{

		// Group subscriber
		$groupSubscriber = '';
		if (! empty($offer->min_group_subscriber) || ! empty($offer->max_group_subscriber)) {

			// Min/max subscribers
			if (! empty($offer->min_group_subscriber)) {
				$groupSubscriber .= $this->sdk->lang->get('offer_min_subscriber') . ' ' . $offer->min_group_subscriber . '<br>';
			}
			if (! empty($offer->max_group_subscriber)) {
				$groupSubscriber .= $this->sdk->lang->get('offer_max_subscriber') . ' ' . $offer->max_group_subscriber;
			}

			// Prepare view
			$groupSubscriber = $this->renderDetailBlock($this->sdk->lang->get('offer_number_of_people_groups'), $groupSubscriber, 'block number_of_people_groups');
		}

		// Individual subscriber
		$individualSubscriber = '';
		if (! empty($offer->min_individual_subscriber) || ! empty($offer->max_individual_subscriber)) {

			// Min/max subscribers
			if (! empty($offer->min_individual_subscriber)) {
				$individualSubscriber .= $this->sdk->lang->get('offer_min_subscriber') . ' ' . $offer->min_individual_subscriber . '<br>';
			}
			if (! empty($offer->max_individual_subscriber)) {
				$individualSubscriber .= $this->sdk->lang->get('offer_max_subscriber') . ' ' . $offer->max_individual_subscriber;
			}

			// Prepare view
			$individualSubscriber = $this->renderDetailBlock($this->sdk->lang->get('offer_number_of_people_individual'), $individualSubscriber, 'block number_of_people_individual');
		}

		return $groupSubscriber . $individualSubscriber;
	}



	/**
	 * Render additional informations block
	 */
	protected function renderAdditionalInfos(object $offer): string
	{

		$additionalInfo = [];

		// Prepare options
		if (! empty($offer->barrier_free)) {
			$additionalInfo[] = $this->sdk->lang->get('offer_barrier_free');
		}
		if (! empty($offer->learning_opportunity)) {
			$additionalInfo[] = $this->sdk->lang->get('offer_learning_opportunity');
		}
		if (! empty($offer->child_friendly)) {
			$additionalInfo[] = $this->sdk->lang->get('offer_child_friendly');
		}

		// Prepare output
		if (! empty($additionalInfo)) {
			$additionalInfo = '<p>- ' . implode('<br>- ', $additionalInfo) . '</p>';
		} else {
			$additionalInfo = '';
		}

		// Return string
		if (! empty($offer->additional_informations) || ! empty($additionalInfo)) {
			return $this->renderDetailBlock('', '<p>' . ParksFormat::autoTextFormat($offer->additional_informations) . '</p>' . $additionalInfo, 'block additional_informations');
		}

		return '';
	}



	/**
	 * Format project duration ({M} {Y} - {M} {Y})
	 */
	protected function formatProjectDuration(
		int $fromYear,
		int $toYear,
		?int $fromMonth = null,
		?int $toMonth = null,
		bool $isOverview = false
	): string {

		$output = '';

		if (! empty($fromYear)) {

			// Output if year is not the same:
			// => «From {M} {Y} until {M} {Y}»
			if ($fromYear != $toYear) {

				if (! $isOverview) {
					$output .= $this->sdk->lang->get('offer_from') . ' ';
				}

				$output .= '<strong>';

				if ($fromMonth > 0) {
					$output .= $this->sdk->lang->get('month_long_' . $fromMonth) . ' ';
				}

				if ($isOverview) {
					$output .= $fromYear . ' – ';
				} else {
					$output .= $fromYear . '</strong> ' . $this->sdk->lang->get('offer_to') . ' <strong>';
				}

				if ($toMonth > 0) {
					$output .= $this->sdk->lang->get('month_long_' . $toMonth) . ' ';
				}
				$output .=  $toYear . '</strong>';
			}

			// Output if year is the same
			// => «From {M} until {M} {Y}»
			else {
				if (($fromMonth > 0) && ($toMonth > 0) && ($fromMonth != $toMonth)) {
					if (! $isOverview) {
						$output .= $this->sdk->lang->get('offer_from') . ' ';
					}
					$output .= '<strong>' . $this->sdk->lang->get('month_long_' . $fromMonth) . ' ' . $this->sdk->lang->get('offer_to') . ' ' . $this->sdk->lang->get('month_long_' . $toMonth) . ' ' . $fromYear . '</strong>';
				} else {
					$output .= '<strong>';
					if ($fromMonth > 0) {
						$output .= $this->sdk->lang->get('month_long_' . $fromMonth) . ' ';
					}
					$output .= $fromYear . '</strong>';
				}
			}
		} else if ($isOverview == false) {
			$output = $this->sdk->lang->get('offer_project_no_duration');
		}

		// Return string
		if (! empty($output)) {
			if ($isOverview == false) {
				return $this->renderDetailBlock($this->sdk->lang->get('offer_project_duration'), $output, 'block project_duration');
			} else {
				return $output;
			}
		}

		return '';
	}



	/**
	 * Wrap non-empty string with optional prefix/suffix
	 */
	protected function wrapString(?string $string, string $suffix = "", string $prefix = ""): string
	{

		if (! empty($string)) {
			return $prefix . $string . $suffix;
		}

		return "";
	}



	/**
	 * Render detail content block
	 */
	protected function renderDetailBlock(?string $title, ?string $content, string $class = ""): string
	{

		$return = "";

		// Check html tags
		$hasHtml = false;
		if (! empty($content) && ($content != strip_tags($content))) {
			$hasHtml = true;
		}

		// Output
		if (! empty($content)) {
			$return .= '
				<div class="' . $class . '">
					<div class="description">
						' . (! empty($title) ? '<h2>' . $title . '</h2>' : '') . 
						(($hasHtml == true) ? $content : ParksFormat::autoTextFormat($content)) . '
					</div>
				</div>
			';
		}

		return $return;
	}



	/**
	 * Compile template output
	 */
	protected function compileOutput(array $templateData): string
	{

		$output = '';

		if (! empty($templateData)) {

			// Iterate template placeholders
			foreach ($templateData as $key => $value) {

				// Set template tag
				$this->detailTemplateTags[$key] = $value;

				// Set output
				$output .= $value;
			}

			// Return output
			if (! empty($output)) {
				return $output;
			}
		}

		return '';
	}



	/**
	 * Get seo detail url by offer id and title
	 */
	public function getSeoDetailUrl(int $offerId = 0, string $title = '', ?int $poi = null, bool $includeScriptUrl = true): string
	{

		// Get detail slugs
		$detailSlug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
		$poiSlug = ! empty($this->config['seo_url_poi_slug']) ? $this->config['seo_url_poi_slug'] : '';
		$resetSlug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

		// Generate title slug
		$titleSlug = ParksFormat::urlSlug($title, ['transliterate' => true]);

		// Prepare script url
		$scriptUrl = $this->scriptUrl;

		// Remove params from script url
		$scriptUrl = strtok($scriptUrl, '?');
		$scriptUrl = strtok($scriptUrl, '&');

		// Remove detail segments for POI urls
		if (strstr($scriptUrl, '/' . $detailSlug . '/')) {
			$detailSegmentPos = strpos($scriptUrl, '/' . $detailSlug);
			$scriptUrl = substr($scriptUrl, 0, $detailSegmentPos);
		}

		// Remove double slash
		if (substr($scriptUrl, -1) != '/') {
			$scriptUrl .= '/';
		}

		if (empty($offerId)) {
			return $scriptUrl . $detailSlug;
		}

		// Add poi
		if (! empty($poi)) {
			$poi = '/' . $poiSlug . '/' . intval($poi);
		}

		// Set url
		if ($includeScriptUrl == true) {
			$url = $scriptUrl . $detailSlug . '/' . $titleSlug . '-' . $offerId . $poi;
		} else {
			$url = '/' . $detailSlug . '/' . $titleSlug . '-' . $offerId . $poi;
		}

		// Clean url from double slashes
		$url = str_replace('//', '/', $url);

		// Clean url from reset param
		$url = str_replace('/' . $resetSlug, '', $url);

		// Set detail url
		return $url;
	}


}
