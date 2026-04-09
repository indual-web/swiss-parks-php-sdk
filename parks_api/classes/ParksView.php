<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Default view class for API
|
*/


class ParksView
{


	/**
	 * API
	 */
	public $api;


	/**
	 * Configuration
	 */
	public $config;


	/**
	 * Script url
	 */
	public $script_url;
	public $script_url_with_params;


	/**
	 * Misc
	 */
	public $projects_only;
	public $sbb_link;


	/**
	 * Detail template tags of each offer type
	 */
	public $detail_template_tags;


	/**
	 * View mode
	 * true: Returns the API output as string
	 * false: Echoes the output directly
	 */
	public $return_output;


	/**
	 * Specific target groups
	 */
	public $specific_target_groups;



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
		$this->api = (object)$api;

		// Get api informations
		$this->config = $api->config;

		// Set return output method
		$this->return_output = isset($this->config['return_output']) ? $this->config['return_output'] : false;

		// Specific target groups
		$this->specific_target_groups = array(7, 8, 9, 10, 12);

		// Init sbb links for each language
		$sbb_i18n = array(
			'de' => 'https://www.sbb.ch/de/kaufen/pages/fahrplan/fahrplan.xhtml',
			'fr' => 'https://www.sbb.ch/fr/acheter/pages/fahrplan/fahrplan.xhtml',
			'it' => 'https://www.sbb.ch/it/acquistare/pages/fahrplan/fahrplan.xhtml',
			'en' => 'https://www.sbb.ch/en/buying/pages/fahrplan/fahrplan.xhtml',
		);
		$this->sbb_link = $sbb_i18n[$this->api->lang_id];

		// Set script url with all params
		if (! empty($_SERVER['REQUEST_URL'])) {
			$this->script_url_with_params = $_SERVER['REQUEST_URL'];
		} elseif (! empty($_SERVER['REQUEST_URI'])) {
			$this->script_url_with_params = $_SERVER['REQUEST_URI'];
		}

		// Set script url without params
		$this->script_url = $this->script_url_with_params;
		$param_start = ! empty($this->script_url_with_params) ? strpos($this->script_url_with_params, '?') : 0;
		if ($param_start > 0) {
			$this->script_url = substr($this->script_url_with_params, 0, $param_start);
		}
	}



	/**
	 * Show offer filter
	 *
	 * @access public
	 * @param array $params (default: array())
	 * @return void
	 */
	public function filter($params = [])
	{

		// Init template data
		$template_data = [];

		// Init projects only
		$this->projects_only = ! empty($params['projects_only']) ? $params['projects_only'] : false;

		// Init url slugs
		$page_slug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
		$reset_slug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

		// Set name
		$fieldname = $this->config['form_prefix'] . 'filter';

		// Set action for seo urls
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

			$action = $this->script_url;

			// Clean url from double slashes
			$action = str_replace('//', '/', $action);

			// Clean url from page param
			$action = preg_replace('/\/' . $page_slug . '\/(\d*)/m', '', $action);

			// Clean url from reset param
			$action = str_replace('/' . $reset_slug, '', $action);

			// Set ending slash
			$action = rtrim($action, '/') . '/';
			
		} else {

			// Set action for default urls
			$action = $this->script_url_with_params;

			// Clean url from page param
			$action = preg_replace('/[\?\&]page=\w*/', '', $action);

			// Clean url from reset param
			$action = preg_replace('/[\?\&]reset=\w*/', '', $action);
		}

		// Show filter
		$template_data['FILTER_SHOW_LINK'] = '<a class="show_filter"><i aria-hidden="true">a</i>' . $this->api->lang->get('offer_filter_show') . '</a>';
		$template_data['FILTER_FORM_START'] = '<form action="' . $action . '" method="post" autocomplete="off">';
		$template_data['FILTER_FORM_STOP'] = '</form>';
		$search_val = (isset($params['selected']['search']) ? ' value="' . (isset($params['selected']['search']) ? $params['selected']['search'] : '') . '"' : '');

		// Filter: free text search
		$template_data['FILTER_TEXT_SEARCH'] = '
			<p class="form_element search">
				<span class="element_wrap">
					<label for="offer_filter_search">' . $this->api->lang->get('offer_search') . '</label>
					<input type="text" name="' . $fieldname . '[search]" id="offer_filter_search"' . $search_val . ' placeholder="' . $this->api->lang->get('offer_search') . '" />
					<span class="search_icon" aria-hidden="true">k</span>
				</span>
			</p>
		';

		// Filter: select categories
		if (is_array($params['categories']) && ! empty($params['categories'])) {
			$template_data['FILTER_CATEGORIES'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . (! empty($params['projects_only']) ? $this->api->lang->get('projects_all') : $this->api->lang->get('offer_all')) . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option" aria-hidden="true"></span>
							<span class="all">' . (! empty($params['projects_only']) ? $this->api->lang->get('projects_all') : $this->api->lang->get('offer_all')) . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
							</span>';
			foreach ($params['categories'] as $key1 => $level1) {
				if (is_array($level1) && ! empty($level1)) {
					// Check, if there is more than 1 level down
					$first_level2 = reset($level1);
					if (! is_array($first_level2)) {
						$template_data['FILTER_CATEGORIES'] .= '
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
							$template_data['FILTER_CATEGORIES'] .= '
												<span class="label_wrapper" role="group" aria-label="' . $key2 . '">
													<label class="category_title" role="button" aria-pressed="false" tabindex="0">
														<span aria-hidden="true"></span>
														' . $key2 . '
													</label>
											';
							foreach ($level2 as $key3 => $level3) {
								$checked = (isset($params['selected']['categories']) && is_array($params['selected']['categories']) && in_array($key3, $params['selected']['categories']) ? 'checked="checked"' : '');
								$template_data['FILTER_CATEGORIES'] .= '
													<label for="' . $fieldname . '_categories_' . $key3 . '" role="option" aria-selected="false" tabindex="0">
														<input name="' . $fieldname . '[categories][]" ' . $checked . ' value="' . $key3 . '" type="checkbox" id="' . $fieldname . '_categories_' . $key3 . '">
														<span aria-hidden="true"></span>
														' . $level3 . '
													</label>
												';
							}
							$template_data['FILTER_CATEGORIES'] .= '</span>';
						}
						// Level 2
						else {
							$checked = (isset($params['selected']['categories']) && is_array($params['selected']['categories']) && in_array($key2, $params['selected']['categories']) ? 'checked="checked"' : '');
							$template_data['FILTER_CATEGORIES'] .= '
												<label for="' . $fieldname . '_categories_' . $key2 . '" role="option" aria-selected="false" tabindex="0">
													<input name="' . $fieldname . '[categories][]" ' . $checked . ' value="' . $key2 . '" type="checkbox" id="' . $fieldname . '_categories_' . $key2 . '">
													<span aria-hidden="true"></span>
													' . $level2 . '
												</label>
											';
						}
					}
					// Close group
					if (! is_array($first_level2)) {
						$template_data['FILTER_CATEGORIES'] .= '</span>';
					}
				}
			}
			$template_data['FILTER_CATEGORIES'] .= '
						</div>
					</div>
				</div>
			';
		}

		// Filter: date from and date to
		if (isset($params['show_event_filter']) && ($params['show_event_filter'] == true)) {
			$date_from_val = (isset($params['selected']['date_from']) && ! empty($params['selected']['date_from']) ? ' value="' . $params['selected']['date_from'] . '"' : '');
			$date_to_val = (isset($params['selected']['date_to']) && ! empty($params['selected']['date_to']) ? ' value="' . $params['selected']['date_to'] . '"' : '');
			$template_data['FILTER_DATES'] = '
				<div class="form_element date_wrap">
					<span class="element_wrap">
						<label for="date_start">' . $this->api->lang->get('general_date') . ' ' . $this->api->lang->get('general_from') . '</label>
						<i class="icon" aria-hidden="true">e</i>
						<input id="date_start" name="' . $fieldname . '[date_from]"' . $date_from_val . ' class="flatpickr" type="text" placeholder="' . $this->api->lang->get('general_date') . ' ' . $this->api->lang->get('general_from') . '">
					</span>
					<span class="element_wrap">
						<label for="date_end">' . $this->api->lang->get('general_date') . ' ' . $this->api->lang->get('general_to') . '</label>
						<i class="icon" aria-hidden="true">e</i>
						<input id="date_end" name="' . $fieldname . '[date_to]"' . $date_to_val . ' class="flatpickr" type="text" placeholder="' . $this->api->lang->get('general_date') . ' ' . $this->api->lang->get('general_to') . '">
					</span>
					<div class="cf"></div>
				</div>
			';
		}

		// Filter: target group
		if (isset($params['show_target_group_filter']) && ($params['show_target_group_filter'] == true) && empty($params['show_project_filter'])) {
			$template_data['FILTER_TARGET_GROUPS'] = '';

			if (! empty($this->api->model->target_groups)) {

				$target_group_groups = array(
					$this->api->lang->get('offer_target_group_general_info'),
					$this->api->lang->get('offer_target_group_specific_info')
				);

				$template_data['FILTER_TARGET_GROUPS'] = '
					<div class="form_element mega_dropdown filter_target_groups">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_target_group_general_info') . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option"></span>
								<span class="all">' . $this->api->lang->get('offer_target_group_general_info') . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
								<span class="dropdown_filter" role="toolbar">
									<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
									<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
								</span>
								';
				foreach ($target_group_groups as $target_group_index => $target_group_label) {

					// Target group title
					$template_data['FILTER_TARGET_GROUPS'] .= '
										<span class="label_wrapper" role="group" aria-label="' . $target_group_label . '">
											<label class="category_title" role="button" aria-pressed="false" tabindex="0">
												<span aria-hidden="true"></span>
												' . $target_group_label . '
											</label>
									';

					// Target group items
					foreach ($this->api->model->target_groups as $target_group_id => $target_label) {

						// Check specific target group
						if (in_array($target_group_id, $this->specific_target_groups) == (bool)$target_group_index) {

							// Check main target group restriction
							if (empty($this->api->system_filter['target_groups']) || in_array($target_group_id, $this->api->system_filter['target_groups'])) {
								$target_val = isset($params['selected']['target_groups']) && in_array($target_group_id, $params['selected']['target_groups']) ? 'checked="checked"' : '';
								$template_data['FILTER_TARGET_GROUPS'] .= '
													<label for="' . $fieldname . '_target_groups_' . $target_group_id . '" role="option" aria-selected="false" tabindex="0">
														<input name="' . $fieldname . '[target_groups][]" id="' . $fieldname . '_target_groups_' . $target_group_id . '" ' . $target_val . ' value="' . $target_group_id . '" type="checkbox">
														<span aria-hidden="true"></span>
														' . htmlspecialchars($target_label) . '
													</label>
												';
							}
						}
					}

					$template_data['FILTER_TARGET_GROUPS'] .= '
										</span>
									';
				}
				$template_data['FILTER_TARGET_GROUPS'] .= '
								</span>
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: municipality
		if (isset($params['show_municipality_filter']) && ($params['show_municipality_filter'] == true)) {

			// Get park municipalities
			if (! empty($params['park_id']) && ($params['park_id'] > 0)) {
				$municipalities = $this->api->model->get_municipalities(['park_id' => $params['park_id']]);
			} else {
				$municipalities = $this->api->model->get_municipalities();
			}

			if (! empty($municipalities) && (count($municipalities) > 1)) {

				// Format municipality options
				$municipality_options = '';
				foreach ($municipalities as $municipality_id => $municipality) {
					$checked = isset($params['selected']['municipalities']) && in_array($municipality_id, $params['selected']['municipalities']) ? 'checked' : '';
					$option = '<label role="option" aria-selected="false" tabindex="0">
									<input name="'  . $fieldname . '[municipalities][]" ' . $checked . ' value="' . $municipality_id . '" type="checkbox">
									<span aria-hidden="true"></span>
									' . $municipality . '
								</label>';

					$municipality_options .= $option;
				}

				$template_data['FILTER_MUNICIPALITIES'] = '
					<div class="form_element mega_dropdown filter_municipalities">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_municipalities_all') . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option"></span>
								<span class="all">' . $this->api->lang->get('offer_municipalities_all') . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
								<span class="dropdown_filter" role="toolbar">
									<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
									<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
								</span>
								<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_municipalities_all') . '">' . $municipality_options . '</span>
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: accessibility
		if (isset($params['show_accessibility_filter']) && ($params['show_accessibility_filter'] == true) && empty($params['hide_accessibility_filter']) && empty($params['show_project_filter'])) {
			$template_data['FILTER_ACCESSIBILITIES'] = '';

			// Get accessibility list
			$accessibilities = $this->api->model->get_accessibility_list();
			if (! empty($accessibilities)) {
				$template_data['FILTER_ACCESSIBILITIES'] = '
					<div class="form_element mega_dropdown filter_accessibilities">
						<div class="form_group">
							<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_accessibility') . '">
								<i class="deselect_icon" aria-hidden="true">m</i>
								<span class="selected_option"></span>
								<span class="all">' . $this->api->lang->get('offer_accessibility') . '</span>
								<i class="arrow_icon" aria-hidden="true">b</i>
							</h4>
							<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">';
				foreach ($accessibilities as $accessibility_id => $accessibility) {
					$checked = isset($params['selected']['accessibilities']) && in_array($accessibility_id, $params['selected']['accessibilities']) ? 'checked' : '';
					$template_data['FILTER_ACCESSIBILITIES'] .= '
										<label role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[accessibilities][]" ' . $checked . ' value="' . $accessibility_id . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $accessibility . '
										</label>
									';
				}
				$template_data['FILTER_ACCESSIBILITIES'] .= '
							</div>
						</div>
					</div>
				';
			}
		}

		// Filter: park
		if (! empty($params['users']) && count($params['users']) > 1) {
			$template_data['FILTER_PARKS'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_parks_all') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->api->lang->get('offer_parks_all') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_parks_all') . '">';
			foreach ($params['users'] as $park_id => $park) {
				$park_val = isset($params['selected']['park_id']) && in_array($park_id, $params['selected']['park_id']) ? 'checked="checked"' : '';
				$template_data['FILTER_PARKS'] .= '
										<label for="' . $fieldname . '_park_id_' . $park_id . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[park_id][]" id="' . $fieldname . '_park_id_' . $park_id . '" ' . $park_val . ' value="' . $park_id . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $park . '
										</label>
									';
			}
			$template_data['FILTER_PARKS'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}

		// Filter: project status
		if (! empty($params['show_project_filter'])) {
			$all_project_status = $this->config['project_status_' . $this->api->lang_id];
			$template_data['FILTER_PROJECT'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_project_status') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->api->lang->get('offer_project_status') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_project_status') . '">';
			foreach ($all_project_status as $status_id => $status_label) {
				$project_val = isset($params['selected']['project_status']) && in_array($status_id, $params['selected']['project_status']) ? 'checked="checked"' : '';
				$template_data['FILTER_PROJECT'] .= '
										<label for="' . $fieldname . '_project_status_' . $status_id . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[project_status][]" id="' . $fieldname . '_project_status_' . $status_id . '" ' . $project_val . ' value="' . $status_id . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $status_label . '
										</label>
									';
			}
			$template_data['FILTER_PROJECT'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}

		// Filter: routes
		if (isset($params['show_route_filter']) && ($params['show_route_filter'] == true)) {

			// Route length
			$template_data['FILTER_ROUTE_LENGTH'] = '
				<div class="form_element range_wrap">
					<input id="route_min" name="' . $fieldname . '[route_length_min]" type="hidden" value="' . (isset($params['selected']['route_length_min']) ? $params['selected']['route_length_min'] : '0') . '">
					<input id="route_max" name="' . $fieldname . '[route_length_max]" type="hidden" value="' . (isset($params['selected']['route_length_max']) ? $params['selected']['route_length_max'] : '50') . '">
					<div class="range"></div>
					<span class="range_text" aria-live="polite">
						<span class="minimum" aria-hidden="true" data-label="' . $this->api->lang->get('offer_routes_length') . '"></span>
						<span class="maximum" aria-hidden="true"></span>
						<span class="sr-only" id="range_sr_only"></span>
					</span>
					<div class="cf"></div>
				</div>
			';

			// Route time
			$template_data['FILTER_ROUTE_TIME'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('offer_routes_time_required') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option"></span>
							<span class="all">' . $this->api->lang->get('offer_routes_time_required') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_routes_time_required') . '">';
			foreach ($this->config['route_times'] as $time) {
				$time = html_entity_decode($time);
				$time_checked = ! empty($params['selected']['time_required']) && in_array($time, $params['selected']['time_required']) ? 'checked="checked"' : '';
				$template_data['FILTER_ROUTE_TIME'] .= '
										<label for="' . $fieldname . '_time_required_' . $time . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[time_required][]" id="' . $fieldname . '_time_required_' . $time . '" ' . $time_checked . ' value="' . $time . '" type="checkbox">
											<span aria-hidden="true"></span>
											' . $time . '
										</label>
									';
			}
			$template_data['FILTER_ROUTE_TIME'] .= '
							</span>
						</div>
					</div>
				</div>
			';

			// Route condition and technic
			$title_condition = $this->api->lang->get('offer_routes_condition');
			$title_technic = $this->api->lang->get('offer_routes_technic');
			$template_data['FILTER_ROUTE_CONDITION'] = '
				<div class="form_element mega_dropdown">
					<div class="form_group">
						<h4 aria-haspopup="listbox" aria-expanded="false" role="button" tabindex="0" title="' . $this->api->lang->get('general_select_field') . ': ' . $this->api->lang->get('offer_routes_requirements') . '">
							<i class="deselect_icon" aria-hidden="true">m</i>
							<span class="selected_option selected_conditions_technics"></span>
							<span class="all">' . $this->api->lang->get('offer_routes_requirements') . '</span>
							<i class="arrow_icon" aria-hidden="true">b</i>
						</h4>
						<div class="form_group_dropdown" role="listbox" aria-multiselectable="true">
							<span class="dropdown_filter" role="toolbar">
								<label class="select_all" role="button" tabindex="0"><i aria-hidden="true">f</i>' . $this->api->lang->get('offer_filter_all') . '</label>
								<label class="deselect_all" role="button" tabindex="0"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_filter_none') . '</label>
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_routes_requirements') . '">
								<label class="category_title" role="button" aria-pressed="false" tabindex="0">
									<span aria-hidden="true"></span>
									' . $title_condition . '
								</label>';
			foreach ($this->config['route_levels'] as $level => $level_value) {
				$level_checked = isset($params['selected']['level_condition']) && in_array($level, $params['selected']['level_condition']) ? 'checked="checked"' : '';
				$template_data['FILTER_ROUTE_CONDITION'] .= '
										<label for="' . $fieldname . '_level_condition_' . $level . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[level_condition][]" id="' . $fieldname . '_level_condition_' . $level . '" ' . $level_checked . ' value="' . $level . '" type="checkbox">
											<span aria-hidden="true"></span>
											<span class="hidden" aria-hidden="true">' . $title_condition[0] . ': </span>' . $level_value . '
										</label>
									';
			}
			$template_data['FILTER_ROUTE_CONDITION'] .= '
							</span>
							<span class="label_wrapper" role="group" aria-label="' . $this->api->lang->get('offer_routes_requirements') . '">
								<label class="category_title" role="button" aria-pressed="false" tabindex="0">
									<span aria-hidden="true"></span>
									' . $title_technic . '
								</label>';
			foreach ($this->config['route_levels'] as $level => $level_value) {
				$level_checked = isset($params['selected']['level_technics']) && in_array($level, $params['selected']['level_technics']) ? 'checked="checked"' : '';
				$template_data['FILTER_ROUTE_CONDITION'] .= '
										<label for="' . $fieldname . '_level_technics_' . $level . '" role="option" aria-selected="false" tabindex="0">
											<input name="' . $fieldname . '[level_technics][]" id="' . $fieldname . '_level_technics_' . $level . '" ' . $level_checked . ' value="' . $level . '" type="checkbox">
											<span aria-hidden="true"></span>
											<span class="hidden">' . $title_technic[0] . ': </span>' . $level_value . '
										</label>
									';
			}
			$template_data['FILTER_ROUTE_CONDITION'] .= '
							</span>
						</div>
					</div>
				</div>
			';
		}


		// Filter: keywords
		$filter_keywords = '';
		if (! empty($this->api->config['keyword_filter'][$this->api->lang_id]) && ($this->api->filter_display_keywords === true)) {
			$all_keywords = $this->api->config['keyword_filter'][$this->api->lang_id];
			if (is_array($all_keywords) && (count($all_keywords) > 2)) {

				// Get keyword title and link label to show all entries
				$keyword_title = $all_keywords[0];
				$keyword_all = $all_keywords[1];
				unset($all_keywords[0]);
				unset($all_keywords[1]);

				$get_keyword = '';

				// Get keyword from URL
				if (isset($_REQUEST['keyword'])) {
					$get_keyword = $_REQUEST['keyword'];
				}

				// Get selected keyword from session
				else if (! empty($_SESSION[$this->api->session_name]['keyword'])) {
					$get_keyword = $_SESSION[$this->api->session_name]['keyword'];
				}

				// Prepare keyword link url
				$page_slug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
				$reset_slug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';
				$script_url = $this->script_url;
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

					// Clean url from double slashes
					$script_url = str_replace('//', '/', $this->script_url);

					// Clean url from page param
					$script_url = preg_replace('/\/' . $page_slug . '\/(\d*)/m', '', $script_url);

					// Clean url from reset param
					$script_url = str_replace('/' . $reset_slug, '', $script_url);

					// Remove last slash
					$script_url = rtrim($script_url, '/');
				}

				if (! empty($all_keywords)) {

					// Show wrapper
					$filter_keywords = '
						<div class="keywords_wrap">
							<h3>' . $keyword_title . '</h3>
							<ul class="keywords">
								<li class="keyword' . (empty($get_keyword) ? ' active' : '') . '"><a href="' . $script_url . '?keyword=">' . $keyword_all . '</a></li>
					';

					// Show keyword links
					foreach ($all_keywords as $keyword) {

						// Clean keyword
						$clean_keyword = str_replace('*', '---', $keyword);
						$clean_keyword = str_replace('/', '-_-', $clean_keyword);

						// Show keyword
						$filter_keywords .= '
								<li class="keyword' . ((! empty($get_keyword) && ($get_keyword == $clean_keyword)) ? ' active' : '') . '"><a href="' . $script_url . '?keyword=' . $clean_keyword . '">' . str_replace('-', ' ', $keyword) . '</a></li>
						';
					}

					// Close wrapper
					$filter_keywords .= '
							</ul>
						</div>
					';
				}
			}
		}
		$template_data['FILTER_KEYWORDS'] = $filter_keywords;


		// Set reset link with seo urls
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

			// Clean reset url from page param
			$script_url = preg_replace('/\/' . $page_slug . '\/(\d*)/m', '', $this->script_url);

			// Remove slash at the end of the url
			$script_url = rtrim($script_url, '/');

			// Set reset link
			$reset_link = $script_url . '/reset';
		}

		// Set reset link with default urls
		else {
			$reset_link = $this->script_url_with_params;
			if (strstr($reset_link, '?')) {
				$reset_link .= '&reset=1';
			} else {
				$reset_link .= '?reset=1';
			}
		}

		// Filter buttons
		if ($params['filter'] == true) {
			$template_data['FILTER_RESET_BUTTON'] = '<a href="' . $reset_link . '" class="reset_link" title="' . $this->api->lang->get('offer_link_filter_reset') . '"><i aria-hidden="true">m</i>' . $this->api->lang->get('offer_reset') . '</a>';
		}
		$template_data['FILTER_SUBMIT_BUTTON'] = '<input type="submit" name="' . $fieldname . '[submit]" value="' . $this->api->lang->get('general_search') . '" class="button" />';

		// Compile filter template
		$output = $this->api->compile_template('filter', $template_data);

		// Show output
		if ($this->return_output === true) {
			return $output;
		} else {
			echo $output;
		}
	}



	/**
	 * Show offer list
	 *
	 * @access public
	 * @param array $offers
	 * @param bool $in_tab (default: false)
	 * @param int $poi (default: 0)
	 * @param int $original_category (default: NULL)
	 * @return mixed
	 */
	public function list_offers($offers, $in_tab = false, $poi = 0, $original_category = NULL)
	{
		$output = "";
		if (isset($offers['total']) && ($offers['total'] > 0) && isset($offers['data']) && ! empty($offers['data'])) {

			// Output
			$output .= '<div class="entries_wrap listing">';

			// Show total
			if ($in_tab == false) {
				$total_rows_label = (! empty($this->projects_only) ? 'project' : 'offer');
				$output .= '
					<div id="offer_total">
						<span class="offer_count">' . $offers['total'] . ' ' . (($offers['total'] == 1) ? $this->api->lang->get($total_rows_label) : $this->api->lang->get($total_rows_label . 's')) . '</span>
					</div>
				';
			}

			foreach ($offers['data'] as $offer) {

				if (! empty($offer)) {


					// Prepare detail link with seo urls
					if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
						$offer_detail_url = $this->get_seo_detail_url($offer->offer_id, $offer->title);
					}

					// Prepare default detail url
					else {
						$param_name = $this->config['url_param_prefix'] . 'offer';
						$offer_detail_url = $this->script_url . (strstr($this->script_url, '?') ? '&amp' : '?') . $param_name . '=' . $offer->offer_id;
					}

					// Prepare target
					$link_target = '';
					if (($in_tab === true) && ! empty($this->config['poi_listing_link_target'])) {
						$link_target = ' target="' . $this->config['poi_listing_link_target'] . '"';
					}

					// Allow to get back to routes
					if ($poi > 0) {

						// Seo urls
						if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
							$offer_detail_url = $this->get_seo_detail_url($offer->offer_id, $offer->title, $poi);
						}

						// Default urls
						else {
							$offer_detail_url .= '&amp;poi=' . $poi . '&amp;original_category=' . $original_category;
						}

					}

					// Prepare link pre-tag
					$link_start_tag = '<a href="' . $offer_detail_url . '" class="entry_link"' . $link_target . ' title="' . $this->api->lang->get('offer_link_to_offer') . ' ' . $offer->title . '">';
					$link_end_tag = '</a>';

					// Prepare dates
					$date_from = NULL;
					$date_to = NULL;
					if (isset($offer->date_from) && ! empty($offer->date_from)) {
						$date_from = new DateTime($offer->date_from);
					}
					if (isset($offer->date_to) && ! empty($offer->date_to)) {
						$date_to = new DateTime($offer->date_to);
					}

					// Prepare categories
					$is_project = ! empty($offer->is_project) ? ' is_project' : '';

					// Start offer as article
					$output .= '<article id="offer_' . $offer->offer_id . '" class="listing_entry' . $is_project . '">';

					// Favorites
					$output .= $this->get_favorites_link($offer->offer_id);

					// Offer image or date
					$has_image = isset($offer->images) && (count($offer->images) > 0);
					$config_thumbnail_size = isset($this->config['overview_thumbnail_size']) ? $this->config['overview_thumbnail_size'] : 'medium';

					// Image
					if ($has_image == true) {
						$output .= '<div class="pictures" aria-hidden="true">';
						if ($offer->is_hint == true) {
							$output .= '<div class="tipp">' . $this->api->lang->get('offer_hint') . '</div>';
						}
						if (($offer->is_park_partner_event == true) || ($offer->is_park_partner == true)) {
							$output .= '<div class="tipp parkpartner">' . $this->api->lang->get('offer_park_partner_label') . '</div>';
						}
						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_start_tag;
						}
						$output .= '<img class="attachment offer_images" src="' . $offer->images[0]->{$config_thumbnail_size} . '" alt="" />';
						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_end_tag;
						}
						$output .= '</div>';
					} else {
						$output .= '<div class="pictures empty_image">';
						if ($offer->is_hint == true) {
							$output .= '<div class="tipp">' . $this->api->lang->get('offer_hint') . '</div>';
						}
						if (($offer->is_park_partner_event == true) || ($offer->is_park_partner == true)) {
							$output .= '<div class="tipp parkpartner">' . $this->api->lang->get('offer_park_partner_label') . '</div>';
						}
						if ($offer->root_category == CATEGORY_EVENT) {
							$output .= '<div class="date_wrap">';

							// Prepare dates
							$date_from = NULL;
							$date_to = NULL;
							if (isset($offer->date_from) && ! empty($offer->date_from)) {
								$date_from = new DateTime($offer->date_from);
							}
							if (isset($offer->date_to) && ! empty($offer->date_to)) {
								$date_to = new DateTime($offer->date_to);
							}

							// Date from
							if (! empty($date_from)) {
								$output .= '
									<div class="date">
										<span>' . $date_from->format('d') . '</span>
										<span>' . $this->api->lang->get('month' . $date_from->format('m')) . '</span>
									</div>
								';
							}

							// Date to
							if (! empty($date_to) && ($date_from->format('dmY') != $date_to->format('dmY'))) {
								$output .= '
									<div class="date">
										<span>' . $date_to->format('d') . '</span>
										<span>' . $this->api->lang->get('month' . $date_to->format('m')) . '</span>
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
					$lang_attr = '';
					if ($offer->language != $this->api->lang_id) {
						$lang_attr = ' lang="' . $offer->language . '"';
					}

					// Get heading html tag
					$heading_tag = 'h3';
					if (! empty($this->config['heading_offer_title_in_overview'])) {
						$heading_tag = $this->config['heading_offer_title_in_overview'];
					}

					// Offer title
					$output .= '<' . $heading_tag . $lang_attr . '>';
					if (! empty($this->api->config['show_button_in_overview'])) {
						$output .= $link_start_tag;
					}
					$output .= $offer->title;
					if (! empty($this->api->config['show_button_in_overview'])) {
						$output .= $link_end_tag;
					}
					$output .= '</' . $heading_tag . '>';

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
						$filter_data = $this->api->get_filter_data();
						$offer->date_from = parks_adjust_date_from($offer->date_from ?? '', $offer->date_to ?? '', $filter_data['date_from'] ?? '');

						// Date from
						$output .= '<span class="date">' . parks_show_date(['date_from' => $offer->date_from, 'date_to' => $offer->date_to, 'times' => $offer->times], $this->api->lang) . '</span>';
					}

					// Show project and research duration
					if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
						$duration = $this->_prepare_project_duration($offer->duration_from, $offer->duration_to, $offer->duration_from_month, $offer->duration_to_month, true);
						if (! empty($duration)) {
							$output .= '
								<span class="date">' . $duration . '</span>
							';
						}
					}

					// Offer description
					if (! empty($this->config['show_short_description_in_overview'])) {
						$output .= '<p class="offer_description offer_description_short"' . $lang_attr . '>';
						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_start_tag;
						}

						if (! empty($offer->abstract) && (mb_strlen($offer->abstract) > 50)) {
							$offer->abstract = mb_substr($offer->abstract, 0, 50) . '...';
						}
						$output .= auto_text_format($offer->abstract);

						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_end_tag;
						}
						$output .= '</p>';
					} else {
						$output .= '<p class="offer_description offer_description_medium"' . $lang_attr . '>';
						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_start_tag;
						}

						// Set description medium
						$description_medium = strip_tags($offer->description_medium ?? '', '<a>');
						if ($in_tab && (mb_strlen($description_medium) > 250)) {
							$description_medium = mb_substr($description_medium, 0, 250) . '...';
						}
						$output .= auto_text_format($description_medium);
						
						if (! empty($this->api->config['show_button_in_overview'])) {
							$output .= $link_end_tag;
						}
						$output .= '</p>';
					}
					if (! empty($this->api->config['show_button_in_overview'])) {
						$output .= $link_end_tag;
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
								$output .= '<span>' . $this->api->lang->get('offer_time_required') . ': ' . activity_get_time_required($offer, $this->api->lang) . '</span>';
							}
							if (! empty($offer->route_length) && (intval($offer->route_length) > 0)) {
								$output .= '<span>' . $this->api->lang->get('offer_route_length') . ': ' . $offer->route_length . $this->api->lang->get('offer_route_length_km') . '</span>';
							}
							$output .= '</div>';
						}
					}

					// Offer keywords
					if (! empty($this->config['show_keywords_in_overview']) && ! empty($offer->keywords)) {
						$offer_keywords = explode(' ', $offer->keywords);
						if (! empty($offer_keywords)) {
							$output .= '<div class="offer_detail_keywords">';
							foreach ($offer_keywords as $keyword) {

								// Prepare keyword
								$label_keyword = ucfirst_utf8(str_replace('-', ' ', $keyword));

								// Show keyword
								$output .= '
									<span class="offer_keyword ' . strtolower($keyword) . '">' . $label_keyword . '</span>
								';
							}
							$output .= '</div>';
						}
					}

					$output .= '<div class="cf"></div>';
					$output .= '</div>';
					$output .= '<div class="cf"></div>';

					// Set link target and link
					$output .= $link_start_tag;
					if (! empty($this->api->config['show_button_in_overview'])) {
						$output .= $this->api->lang->get('more_informations');
					}
					$output .= $link_end_tag;

					$output .= '</article>';
				}
			}

			$output .= '</div>';
		} else {

			$output .= '<p class="no_results">' . $this->api->lang->get('offer_not_found') . '</p>';
		}

		if (($in_tab == true) || ($this->return_output == true)) {
			return $output;
		} else {
			echo $output;
		}
	}



	/**
	 * Show pagination
	 *
	 * @access public
	 * @param int $page
	 * @param int $total
	 * @return mixed
	 */
	public function pagination($page = 1, $total = 1)
	{

		// Init page
		if ($page <= 0) {
			$page = 1;
		}

		// Init seo urls
		$seo_urls = ! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true);
		if ($seo_urls == true) {

			// Init
			$page_slug = ! empty($this->config['seo_url_page_slug']) ? $this->config['seo_url_page_slug'] : '';
			$reset_slug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

			// Clean script url from double slashes
			$script_url = rtrim($this->script_url, '/');
			$script_url = str_replace('//', '/', $script_url);
			$script_url = str_replace('/' . $reset_slug, '', $script_url);

			// Remove page number segments
			if (strstr($script_url, '/' . $page_slug . '/')) {
				$page_segment_pos = strpos($script_url, '/' . $page_slug);
				$script_url = substr($script_url, 0, $page_segment_pos);
			}
		}

		// Output
		$output = '<div class="offer_pagination" role="navigation" aria-label="' . $this->api->lang->get('pagination_label') . '">';

		// Previous link
		if ($page > 1) {
			$param_name = $this->config['form_prefix'] . 'page';
			$link = $this->script_url_with_params;

			$output .= '<div class="prev_wrap">';

			// Handle seo urls
			if ($seo_urls == true) {

				// Set page numbers
				$first_link = $script_url . '/' . $page_slug . '/1';
				$prev_link = $script_url . '/' . $page_slug . '/' . ($page - 1);
			}

			// Default urls
			else {

				// Replace page numbers
				if (strstr($this->script_url_with_params, $param_name)) {
					$first_link = str_replace($param_name . '=' . $page, $param_name . '=1', $this->script_url_with_params);
					$prev_link = str_replace($param_name . '=' . $page, $param_name . '=' . ($page - 1), $this->script_url_with_params);
				}

				// Set page numbers
				else {
					$first_link = (strstr($link, '?') ? '&' : '?') . $param_name . '=1';
					$prev_link = (strstr($link, '?') ? '&' : '?') . $param_name . '=' . ($page - 1);
				}
			}

			$output .= '
				<a href="' . $first_link . '" aria-label="' . $this->api->lang->get('offer_link_to_first_page') . '">' . $this->api->lang->get('offer_first') . '</a>
				<a href="' . $prev_link . '" class="icon" aria-label="' . $this->api->lang->get('offer_link_to_previous_page') . '" >o</a>
			';

			$output .= '</div>';
		}

		// Next page link
		if ($page < $total) {
			$param_name = $this->config['form_prefix'] . 'page';
			$link = $this->script_url_with_params;

			$output .= '<div class="next_wrap">';

			// Handle seo urls
			if ($seo_urls == true) {

				// Set page numbers
				$next_link = $script_url . '/' . $page_slug . '/' . ($page + 1);
				$last_link = $script_url . '/' . $page_slug . '/' . $total;
			}

			// Default urls
			else {

				// Replace page numbers
				if (strstr($this->script_url_with_params, $param_name . '=')) {
					$last_link = str_replace($param_name . '=' . $page, $param_name . '=' . $total, $this->script_url_with_params);
					$next_link = str_replace($param_name . '=' . $page, $param_name . '=' . ($page + 1), $this->script_url_with_params);
				}

				// Set page numbers
				else {
					$next_link = $this->script_url_with_params . (strstr($link, '?') ? '&' : '?') . $param_name . '=' . ($page + 1);
					$last_link = $this->script_url_with_params . (strstr($link, '?') ? '&' : '?') . $param_name . '=' . $total;
				}
			}

			// View
			$output .= '
				<a href="' . $next_link . '" class="icon" aria-label="' . $this->api->lang->get('offer_link_to_next_page') . '">p</a>
				<a href="' . $last_link . '" aria-label="' . $this->api->lang->get('offer_link_to_last_page') . '">' . $this->api->lang->get('offer_last') . '</a>
			';

			$output .= '</div>';
		}

		// Page numbers
		if ($total > 1) {
			$param_name = $this->config['form_prefix'] . 'page';
			$pagination_max_numbers = $this->api->config['pagination_max_numbers'] ? intval($this->api->config['pagination_max_numbers']) : 5;
			$half_pages_start = floor($pagination_max_numbers / 2);
			$half_pages_end = ceil($pagination_max_numbers / 2) - 1;
			$end = 0;

			// Calculate start
			$start = intval($page - $half_pages_start);
			if ($start <= 0) {
				$start = 1;
				$end = $pagination_max_numbers;
			} elseif ($start < ($page - $half_pages_start)) {
				$start = ($page - $half_pages_start);
			}

			// Calculate end
			if (($end == 0) || ($end > $total)) {
				$end = ($page + $half_pages_end);
				if ($end > $total) {
					if ($end == 0) {
						$start = $total - $pagination_max_numbers + 1;
					}
					$end = $total;
				}
			}

			$output .= '<div class="numbers">';
			for ($i = $start; $i <= $end; $i++) {

				// Handle seo urls
				if ($seo_urls == true) {

					// Set link
					$link = $script_url . '/' . $page_slug . '/' . $i;
				}

				// Default urls
				else {

					// Set link
					$link = $this->script_url_with_params;
					if (strstr($this->script_url_with_params, $param_name)) {
						$link = str_replace($param_name . '=' . $page, $param_name . '=' . $i, $this->script_url_with_params);
					} else {
						$link .= (strstr($link, '?') ? '&' : '?') . $param_name . '=' . $i;
					}
				}

				// View
				$output .= '<a href="' . $link . '"' . (($page == $i) ? ' class="current" aria-current="page"' : '') . ' aria-label="' . $this->api->lang->get('offer_link_to_page') . ': ' . $i . ' ' . $this->api->lang->get('general_from') . ' ' . $total . '">' . $i . '</a> ';
			}
			$output .= '</div>';
		}


		$output .= '</div>';

		if ($total > 1) {
			// Show output
			if ($this->return_output === true) {
				return $output;
			} else {
				echo $output;
			}
		}
	}



	/**
	 * Show offer detail page
	 *
	 * @access public
	 * @param object $offer
	 * @param int $original_offer_id (default: 0)
	 * @return void
	 */
	public function detail($offer, $original_offer_id = 0)
	{

		// Init
		$output = '';
		$template_data = [];
		$template_conditions = [];

		// Set view mode into session
		$_SESSION['offer_detail_view'] = true;

		// Lang attribute
		$lang_attr = '';
		if ($offer->language != $this->api->lang_id) {
			$lang_attr = ' lang="' . $offer->language . '"';
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: title, abstract and description
		|--------------------------------------------------------------------------
		|
		*/

		// Title
		$template_data['OFFER_TITLE'] = $offer->title;

		// Abstract
		$template_data['OFFER_ABSTRACT'] = ! empty($offer->abstract) ? $offer->abstract : '';

		// Description
		$description = '<div class="detail_text"' . $lang_attr . '><p>';
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
					<div class="route_condition_details"><div class="route_condition_details_inner">' . auto_link($offer->route_condition_details, 'both', true) . '</div></div>
				';
			}
		}

		// Medium description
		$medium_description = trim(strip_tags($offer->description_medium ?? '', '<a>'));
		$description .= '<i>' . output_text($medium_description) . '</i></p>';

		// Long description
		$long_description = trim($offer->description_long);
		if (! empty($long_description) && ($long_description != $medium_description)) {

			// Cut medium description from long description
			if (substr($long_description, 0, strlen($medium_description)) == $medium_description) {
				$long_description = trim(str_replace($medium_description, '', $long_description));
			}

			// Show long description
			$description .= '<p class="description">' . output_text($long_description) . '</p>';
		}

		// Project and research fields
		if (in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH]) && empty($long_description)) {
			$description .= ! empty($offer->project_initial_situation) ? '<h2 class="project_title">' . $this->api->lang->get('offer_project_initial_situation') . '</h2><p class="description">' . output_text($offer->project_initial_situation) . '</p>' : '';
			$description .= ! empty($offer->project_goal) ? '<h2 class="project_title">' . $this->api->lang->get('offer_project_goal') . '</h2><p class="description">' . output_text($offer->project_goal) . '</p>' : '';
			$description .= ! empty($offer->project_further_information) ? '<h2 class="project_title">' . $this->api->lang->get('offer_project_further_information') . '</h2><p class="description">' . output_text($offer->project_further_information) . '</p>' : '';
			$description .= ! empty($offer->project_results) ? '<h2 class="project_title">' . $this->api->lang->get('offer_project_results') . '</h2><p class="description">' . output_text($offer->project_results) . '</p>' : '';
			$description .= ! empty($offer->project_partner) ? '<h2>' . $this->api->lang->get('offer_partner') . '</h2><p class="description">' . output_text($offer->project_partner) . '</p>' : '';
		}

		// Add online shop articles to the description
		if (($offer->online_shop_enabled == true)) {

			// Check included articles
			if (! empty($offer->articles)) {
				$description .= '
					<div class="products_overview">
						<h2 class="overview_title">' . $this->api->lang->get('offer_included_products') . '</h2>
				';

				// Iterate all articles
				foreach ($offer->articles as $article) {


					// Article description
					$article_description = '';
					$supplier_intro = '';
					$supplier_name = '';
					$supplier_link_prefix = '';
					$supplier_link_suffix = '';
					$separator = '';


					// Article supplier
					if (! empty($article->supplier_contact)) {

						// Set supplier introduction
						$supplier_intro = '<span class="supplier"><span class="from">' . $this->api->lang->get('offer_from') . '</span>';

						// Set supplier name (first line)
						if (! empty($article->supplier_contact)) {
							$supplier_name = substr($article->supplier_contact, 0, strpos($article->supplier_contact, "\n"));
						}

						// Find supplier URL in string
						preg_match('!(http|ftp|scp)(s)?:\/\/[a-zA-Z0-9.?%=&_\-/]+!', $article->supplier_contact, $supplier_url);
						if (! empty($supplier_url[0])) {
							$supplier_link_prefix = '<a href="' . $supplier_url[0] . '" target="_blank" class="supplier_link">';
							$supplier_link_suffix = '</a>';
						}
					}


					// Supplier and description separator
					if (($supplier_name != '') && ($article->article_description != '')) {
						$separator = ', ';
					}


					// Set article description
					$article_description = '
						<div class="description">
							<p>
								' . $supplier_intro . '
								' . $supplier_link_prefix . $supplier_name . $supplier_link_suffix . '</span>'
								. $separator
								. $article->article_description . '
							</p>
						</div>
					';


					// Article labels
					$article_labels = '';
					if (! empty($article->labels)) {

						// Iterate all labels
						foreach ($article->labels as $label) {

							// Prepare label link
							$label_link_prefix = $label_link_suffix = '';
							if (! empty($label->label_url)) {
								$label_link_prefix = '<a href="' . $label->label_url . '" target="_blank" title="' . $label->label_title . '">';
								$label_link_suffix = '</a>';
							}

							// Show park label
							$article_labels .= '
								<div class="product_label_wrap">
									<div class="product_label">
										' . $label_link_prefix . '
											<img src="' . $label->label_icon . '" alt="' . $label->label_title . '">
										' . $label_link_suffix . '
									</div>
									<div class="honoured_by">
										' . $label_link_prefix . '
											' . $this->api->lang->get('offer_product_honoured_by') . ' ' . $label->label_title . '
										' . $label_link_suffix . '
									</div>
									<div class="cf"></div>
								</div>
							';
						}
					}


					// Article addons
					$article_addons = '';
					foreach (['ingredients', 'allergens', 'nutritional_values', 'identity_label', 'quantity_indication'] as $field) {
						if (! empty($article->{'article_' . $field})) {
							$article_addons .= '
								<div class="addon accordion_entry">
									<div class="accordion_title">
										<span class="text">' . $this->api->lang->get('offer_product_article_' . $field) . '</span><i class="icon">+</i>
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
								' . $article_description . '
								' . $article_labels . '
								<div class="article_addons">
									' . $article_addons . '
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
			$template_data['OFFER_ONLINE_SHOP_CHECKOUT_BUTTON'] = '
				<div class="button_wrap">
					<a href="https://angebote.paerke.ch/' . $this->api->lang_id . '/online_shop/checkout/' . $offer->offer_id . '" class="button buy_button" target="_blank">' . $this->api->lang->get('offer_product_shop_now') . '</a>
				</div>
			';
			$description .= $template_data['OFFER_ONLINE_SHOP_CHECKOUT_BUTTON'];
		}

		$description .= '</div>';

		$template_data['OFFER_DESCRIPTION'] = $description;


		/*
		|--------------------------------------------------------------------------
		| Placeholder: short info by offer type
		|--------------------------------------------------------------------------
		|
		*/
		$short_info = '';
		switch ($offer->root_category) {
			case CATEGORY_EVENT:
			case CATEGORY_BOOKING:
				if (! empty($offer->dates)) {

					// Get next date
					$now = new DateTime();
					foreach ($offer->dates as $date) {
						$date_from = new DateTime($date->date_from);
						$date_to = ! empty($date->date_to) ? new DateTime($date->date_to) : null;
						if (($date_from > $now) || (! empty($date->date_to) && ($date_to >= $now))) {
							$next_date = $date;
							break;
						}
					}

					// Show label with next date
					$label = $this->api->lang->get('offer_next_date');
					if (! empty($next_date)) {
						$next_date = parks_show_date(['date_from' => parks_mysql2form($next_date->date_from), 'date_to' => parks_mysql2form($next_date->date_to)], $this->api->lang);
						$short_info .= '<span>' . $label . ':</span> ' . $next_date;
					}
				}
				break;
			case CATEGORY_PRODUCT:
				if (! empty($offer->dates)) {
					$first_date = $offer->dates[0];
					$label = $this->api->lang->get('offer_saison');
					$next_date = parks_show_date(['date_from' => parks_mysql2form($first_date->date_from), 'date_to' => parks_mysql2form($first_date->date_to)], $this->api->lang);
					$short_info .= '<span>' . $label . ':</span> ' . $next_date;
				} else if (! empty($offer->season_months)) {
					$season_months = explode(',', $offer->season_months);
					$season_month_labels = [];
					if (is_array($season_months) && ! empty($season_months)) {
						if (count($season_months) == 12) {
							$short_info .= '<span>' . $this->api->lang->get('offer_saison') . ':</span> ' . $this->api->lang->get('offer_all_season');
						}
						else {
							foreach ($season_months as $month) {
								$season_month_labels[] = $this->api->lang->get('month_long_' . $month);
							}
							$short_info .= implode(', ', $season_month_labels);
						}
					}
				} else {
					$short_info .= '<span>' . $this->api->lang->get('offer_saison') . ':</span> ' . $this->api->lang->get('offer_all_season');
				}
				break;
			case CATEGORY_ACTIVITY:
				if (! empty($offer->time_required) || ! empty($offer->time_required_minutes)) {
					$short_info .= '<span>' . $this->api->lang->get('offer_time_required') . ': </span> ' . activity_get_time_required($offer, $this->api->lang);
				}
				if (isset($offer->route_length) && (intval($offer->route_length) > 0)) {
					$short_info .= '<span>' . $this->api->lang->get('offer_route_length') . ':</span> ' . $offer->route_length . ' km ';
				}
				break;
			case CATEGORY_PROJECT:
			case CATEGORY_RESEARCH:
				if (isset($offer->duration_from) && (intval($offer->duration_from) > 0)) {
					$short_info .= '<span>' . $this->api->lang->get('offer_project_duration') . ':</span> ' . $offer->duration_from;
				}
				if (isset($offer->duration_to) && (intval($offer->duration_to) > 0)) {
					$short_info .= ' - ' . $offer->duration_to;
				}
				break;
			default:
				break;
		}
		$template_data['OFFER_SHORT_INFO'] = $short_info;


		/*
		|--------------------------------------------------------------------------
		| Placeholder: categories
		|--------------------------------------------------------------------------
		|
		*/
		$template_data['OFFER_CATEGORIES'] = '';
		foreach ($offer->categories as $category) {
			if (! in_array($category->category_id, $this->config['hidden_categories'])) {
				$template_data['OFFER_CATEGORIES'] .= '<span>' . $category->body . '</span>';
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
				if (in_array($this->api->lang_id, explode(',', $hyperlink->language))) {
					$link_label = ! empty($hyperlink->title) ? html_entity_decode($hyperlink->title) : $hyperlink->url;
					$links .= '<a href="' . $hyperlink->url . '" class="external_link" target="_blank" title="' . $this->api->lang->get('offer_link_more_infos') . ' ' . $link_label . '">' . $link_label . '</a><br>';
				}
			}
			if (! empty($links)) {
				$template_data['OFFER_LINKS'] = $this->_show_text($this->api->lang->get('offer_links'), '<p>' . $links . '</p>', 'block offer_links');
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
			$offer_keywords = explode(' ', $offer->keywords);
			if (! empty($offer_keywords)) {
				$keywords .= '<div class="offer_detail_keywords">';
				foreach ($offer_keywords as $keyword) {

					// Prepare keyword
					$label_keyword = ucfirst_utf8(str_replace('-', ' ', $keyword));

					// Show keyword
					$keywords .= '
						<span class="offer_keyword ' . strtolower($keyword) . '">' . $label_keyword . '</span>
					';
				}
				$keywords .= '</div>';
			}
		}
		$template_data['OFFER_KEYWORDS'] = $keywords;



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
			$accessiblity_link = $accessibility->ginto_link;

			// Show ratings
			if (! empty($accessibility->ratings)) {
				foreach ($accessibility->ratings as $rating) {
					$label = $rating->{'description_' . $this->api->lang_id};
					$accessibilities .= '
						<a href="' . $accessiblity_link . '" target="_blank">
							<img src="' . $rating->icon_url . '" class="pictogram tooltip" alt="' . $label . '" title="' . $label . '">
						</a>
					';
				}
			}

			// Show OK:GO link
			else if (! empty($accessibility->ginto_icon)) {
				$accessibilities .= '
					<a href="' . $accessiblity_link . '" target="_blank">
						<img src="' . $accessibility->ginto_icon . '" width="50">
					</a>
				';
			}

			// More info link
			$accessibilities .= '<br><a href="' . $accessiblity_link . '" target="_blank">' . $this->api->lang->get('offer_more_informations_accessibility') . '</a>';

			// Set template tag
			if (! empty($accessibilities)) {
				$template_data['OFFER_ACCESSIBILITIES'] = $this->_show_text($this->api->lang->get('offer_accessibility'), '<p>' . $accessibilities . '</p>', 'block offer_accessibilities');
			}
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: target groups
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->target_groups) && is_array($offer->target_groups) && ! in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {

			// Handle target group restrictions
			$tg_main = [];
			$tg_special = [];
			$tg_restrictions = $this->specific_target_groups;
			foreach ($offer->target_groups as $target_group_id => $body) {
				if (in_array($target_group_id, $tg_restrictions)) {
					$tg_special[$target_group_id] = $body;
				} else {
					$tg_main[$target_group_id] = $body;
				}
			}

			// Prepare main target groups
			if (! empty($tg_main)) {
				$tg_main = '<ul><li>' . implode('</li><li>', $tg_main) . '</li></ul>';
			} else {
				$tg_main = '';
			}

			// Prepare special target groups
			if (! empty($tg_special)) {
				$tg_special = '<ul><li>' . implode('</li><li>', $tg_special) . '</li></ul>';
			} else {
				$tg_special = '';
			}

			if (empty($offer->online_shop_enabled)) {
				$template_data['OFFER_TARGET_GROUPS'] =
					$this->_show_text($this->api->lang->get('offer_target_group_general_info'), $tg_main, 'block main_target_groups')
					. $this->_show_text($this->api->lang->get('offer_target_group_specific_info'), $tg_special, 'block specific_target_groups');
			} else {
				$template_data['OFFER_TARGET_GROUPS'] = '';
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
					$suppliers .= '<div class="partner"><i>' . $this->api->lang->get('offer_park_partner') . '</i></div>';
				}
				$suppliers .= '<p>' . auto_text_format($supplier['contact']) . '</p>';
			}
			$template_data['OFFER_SUPPLIERS'] = $this->_show_text($this->api->lang->get('offer_supplier'), $suppliers, 'block offer_suppliers');
		}

		// Institution
		if (isset($offer->institution) && ! empty($offer->institution) && ! in_array($offer->root_category, array(CATEGORY_EVENT))) {
			if ($offer->institution_is_park_partner == 1) {
				$offer->institution = '<div class="partner"><i>' . $this->api->lang->get('offer_park_partner') . '</i></div>' . auto_text_format($offer->institution);
			}

			// Set title
			$title = $this->api->lang->get('offer_organizer');

			// Set placeholder
			$template_data['OFFER_INSTITUTION'] = $this->_show_text($title, $offer->institution, 'block offer_institution');
		}

		// Park
		if ($offer->contact_is_park_partner == 1) {
			$offer->contact = '<div class="partner"><i>' . $this->api->lang->get('offer_park_partner') . '</i></div>' . auto_text_format($offer->contact);
		}
		$template_data['OFFER_CONTACT'] = $this->_show_text($this->api->lang->get('offer_contact'), $offer->contact, 'block offer_contact');


		/*
		|--------------------------------------------------------------------------
		| Placeholder: subscription
		|--------------------------------------------------------------------------
		|
		*/
		if (isset($offer->subscription_mandatory) && ($offer->subscription_mandatory == true)) {
			$template_data['OFFER_SUBSCRIPTION'] = $this->_get_detail_subscription($offer);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: POIs
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->poi)) {
			$template_data['OFFER_POI_LIST'] = $this->_get_poi_content($offer->poi, $offer->offer_id, $offer->root_category);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: Linked routes
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->linked_routes)) {
			$template_data['OFFER_ROUTE_LIST'] = $this->_get_poi_content($offer->linked_routes, $offer->offer_id, $offer->root_category);
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholders: detail pages
		|--------------------------------------------------------------------------
		|
		*/
		switch ($offer->root_category) {
			case CATEGORY_EVENT:
				$template_data['OFFER_EVENT_DETAIL'] = $this->_get_detail_event($offer);
				$template_conditions['OFFER_EVENT'] = true;
				break;
			case CATEGORY_PRODUCT:
				$template_data['OFFER_PRODUCT_DETAIL'] = $this->_get_detail_product($offer);
				$template_conditions['OFFER_PRODUCT'] = true;
				break;
			case CATEGORY_BOOKING:
				$template_data['OFFER_BOOKING_DETAIL'] = $this->_get_detail_booking($offer);
				$template_conditions['OFFER_BOOKING'] = true;
				break;
			case CATEGORY_ACTIVITY:
				$template_data['OFFER_ACTIVITY_DETAIL'] = $this->_get_detail_activity($offer);
				$template_conditions['OFFER_ACTIVITY'] = true;
				break;
			case CATEGORY_PROJECT:
			case CATEGORY_RESEARCH:
				$template_data['OFFER_PROJECT_DETAIL'] = $this->_get_detail_project($offer);
				$template_conditions['OFFER_PROJECT'] = true;
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
		$config_image_enlargement = isset($this->config['image_enlargement']) ? $this->config['image_enlargement'] : false;
		$config_thumbnail_size = isset($this->config['detail_thumbnail_size']) ? $this->config['detail_thumbnail_size'] : 'medium';
		if (isset($offer->images) && is_array($offer->images) && ! empty($offer->images)) {
			$template_data['OFFER_IMAGES'] = '<div class="detail_pictures pictures" aria-hidden="true">';
			foreach ($offer->images as $image) {
				$data_caption = '';
				
				if (! empty($image->copyright)) {
					$image_copyright = '';
					if (!strstr($image->copyright, '©') && !strstr($image->copyright, '&copy; ')) {
						$image_copyright .= '&copy;';
					}
					$image_copyright .= $image->copyright;
					$data_caption = 'data-caption="'.$image_copyright.'"';
				}
				
				$template_data['OFFER_IMAGES'] .= '<div class="attachment picture">';
				
				if ($config_image_enlargement === true) {
					$template_data['OFFER_IMAGES'] .= '<a href="' . $image->large . '" class="offer_image" rel="offer_images" '.$data_caption.' data-fancybox="gallery"  aria-hidden="true">';
				}
				
				$template_data['OFFER_IMAGES'] .= '<img src="' . $image->{$config_thumbnail_size} . '" alt="" class="offer_detail_image">';
				
				if (! empty($image->copyright)) {
					$template_data['OFFER_IMAGES'] .= '<div class="image_description">'.$image_copyright.'</div>';
				}

				if ($config_image_enlargement === true) {
					$template_data['OFFER_IMAGES'] .= '</a>';
				}

				$template_data['OFFER_IMAGES'] .= '</div>';
			}
			$template_data['OFFER_IMAGES'] .= '</div>';
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
				$document_label = ($document->title ? $document->title : $document->url);
				$documents .= '<a href="' . $document->url . '" class="download_link" target="_blank" title="' . $this->api->lang->get('offer_link_download') . ' ' . $document_label . '">' . $document_label . '</a><br>';
			}
			$template_data['OFFER_DOCUMENTS'] = $this->_show_text($this->api->lang->get('offer_documents_public'), $documents, 'block offer_documents');
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: internal informations
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($offer->costs) || ! empty($offer->funding) || ! empty($offer->partner) || ! empty($offer->remarks) || ! empty($offer->hyperlinks_intern) || ! empty($offer->documents_intern)) {

			$internal_infos = '';

			// I18n fields
			foreach (['costs', 'funding', 'partner', 'remarks'] as $key) {
				if (! empty($offer->{$key})) {
					$internal_infos .= trim($this->_show_text($this->api->lang->get('offer_' . $key), $offer->{$key}, 'block ' . $key));
				}
			}

			// Internal hyperlinks
			if (! empty($offer->hyperlinks_intern)) {
				$links = '';
				foreach ($offer->hyperlinks_intern as $hyperlink) {
					if (in_array($this->api->lang_id, explode(',', $hyperlink->language))) {
						$link_label = ! empty($hyperlink->title) ? html_entity_decode($hyperlink->title) : $hyperlink->url;
						$links .= '<a href="' . $hyperlink->url . '" class="external_link" target="_blank" title="' . $this->api->lang->get('offer_link_more_infos') . ' ' . $link_label . '">' . $link_label . '</a><br>';
					}
				}
				if (! empty($links)) {
					$internal_infos .= $this->_show_text($this->api->lang->get('offer_internal_hyperlinks'), '<p>' . $links . '</p>', 'block offer_links_intern');
				}
			}

			// Internal documents
			if (! empty($offer->documents_intern)) {
				$documents = '';
				foreach ($offer->documents_intern as $document) {
					$document_label = ($document->title ? $document->title : $document->url);
					$documents .= '<a href="' . $document->url . '" class="download_link" target="_blank" title="' . $this->api->lang->get('offer_link_download') . ' ' . $document_label . '">' . $document_label . '</a><br>';
				}
				$internal_infos .= $this->_show_text($this->api->lang->get('offer_internal_documents'), $documents, 'block offer_documents_intern');
			}

			$template_data['OFFER_INTERNAL_INFOS'] = $internal_infos;
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: print button (deprecated)
		|--------------------------------------------------------------------------
		|
		*/
		$template_data['OFFER_PRINT_LINK'] = '';


		/*
		|--------------------------------------------------------------------------
		| Placeholder: back link button
		|--------------------------------------------------------------------------
		|
		*/
		$template_data['OFFER_BACK_LINK'] = '';

		// Javascript close window button
		if (! empty($this->api->map_options['link_target']) && ($this->api->map_options['link_target'] == '_blank') && empty($this->api->map_options['ignore_close_window'])) {
			$template_data['OFFER_BACK_LINK'] = '<a class="close_window back_to_overview" href="javascript:void(0);" title="' . $this->api->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->api->lang->get('back_to_overview') . '</span></a>';
		}

		// Default back links
		else {

			// Is poi detail
			if (! empty($_GET['poi']) || ! empty($original_offer_id)) {

				// On poi view – set original offer id
				$original_offer_id = ! empty($_GET['poi']) ? intval($_GET['poi']) : intval($original_offer_id);

				// Get original offer (title)
				$original_offer = $this->api->model->get_offer($original_offer_id, true);

				// Get original offer category
				$original_category_id = ! empty($_GET['original_category']) ? intval($_GET['original_category']) : $original_offer->root_category;

				// Set back button label
				$back_button_label = ($original_category_id != CATEGORY_PROJECT) ? $this->api->lang->get('back_to_route') : $this->api->lang->get('back_to_project');

				// Javascript close window button
				if (! empty($this->config['poi_listing_link_target']) && ($this->config['poi_listing_link_target'] == '_blank')) {
					$template_data['OFFER_BACK_LINK'] = '<a class="close_window back_to_overview" href="javascript:void(0);" title="' . $this->api->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $back_button_label . '</span></a>';
				}

				// Back to overview button
				else {

					// Get slug config
					$detail_slug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
					$poi_slug = ! empty($this->config['seo_url_poi_slug']) ? $this->config['seo_url_poi_slug'] : '';

					// Handle seo urls
					if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

						// Set seo back link
						$back_link = $this->get_seo_detail_url($original_offer_id, $original_offer->title) . '#tab_5';
					}

					// Default urls
					else {
						$back_link = $this->script_url . (strstr($this->script_url, '?') ? '&amp' : '?') . 'offer=' . $original_offer_id . (isset($_GET['tab']) ? ('#' . $_GET['tab']) : '');
					}

					// Set placeholder
					$template_data['OFFER_BACK_LINK'] = '<a class="back_to_overview" href="' . $back_link . '" title="' . $this->api->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $back_button_label . '</span></a>';
				}
			}

			// History back button to referer page
			elseif (
				! empty($_SERVER['HTTP_REFERER']) 
				&& 
				(
					! empty($_GET['force_back_to_referer'])
					||
					(
						strstr($_SERVER['HTTP_REFERER'], $this->script_url)
						&&
						! strstr($_SERVER['HTTP_REFERER'], '?offer=')
					)
				)
			) {
				$template_data['OFFER_BACK_LINK'] = '<a class="go_back back_to_overview" href="javascript:void(0);" title="' . $this->api->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->api->lang->get('back_to_overview') . '</span></a>';
			}

			// Default back button with reload, referrer is empty
			else {

				// Set default back link
				$back_link = $this->script_url;

				// Check seo url
				if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {

					// Get detail slug
					$detail_slug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';

					// Remove detail segments
					$detail_segment_pos = strpos($this->script_url, '/' . $detail_slug);
					if ($detail_segment_pos !== false) {
						$back_link = substr($this->script_url, 0, $detail_segment_pos);
					}

				}

				// Set placeholder
				$template_data['OFFER_BACK_LINK'] = '<a class="back_to_overview" href="' . $back_link . '" title="' . $this->api->lang->get('offer_link_back_to_offers') . '"><i aria-hidden="true">c</i><span>' . $this->api->lang->get('back_to_overview') . '</span></a>';
			}
		}

		// Single offer mode
		if ($this->api->single_mode === true) {
			$template_data['OFFER_BACK_LINK'] = '';
		}


		/*
		|--------------------------------------------------------------------------
		| Placeholder: map
		|--------------------------------------------------------------------------
		|
		*/
		$template_data['OFFER_MAP'] = $this->_get_detail_map($offer);


		/*
		|--------------------------------------------------------------------------
		| Merge detail template tags
		|--------------------------------------------------------------------------
		|
		*/
		if (! empty($this->detail_template_tags) && is_array($this->detail_template_tags)) {
			$template_data = array_merge($template_data, $this->detail_template_tags);
		}


		/*
		|--------------------------------------------------------------------------
		| Overwrite template data in MyView
		|--------------------------------------------------------------------------
		|
		*/
		$template_data = $this->overwrite_template_data($template_data, $offer);


		/*
		|--------------------------------------------------------------------------
		| Compile template
		|--------------------------------------------------------------------------
		|
		*/
		$output = $this->api->compile_template('detail', $template_data, $template_conditions);
		if ($this->return_output === true) {
			return $output;
		} else {
			echo $output;
		}
	}



	/**
	 * Get «Add to favorites» link
	 * 
	 * @param int $offer_id
	 * @return string
	 */
	public function get_favorites_link($offer_id) {
		$output = '';

		// Check if favorites are enabled
		if (! empty($this->config['favorites_extension_available']) && ! empty($this->config['favorites_script_path'])) {

			if (empty($offer_id)) {
				return '';
			}

			// Set link attributes
			$favorite_link_title = in_array($offer_id, $this->api->favorites) ? $this->api->lang->get('favorites_remove') : $this->api->lang->get('favorites_add');
			$favorite_class = in_array($offer_id, $this->api->favorites) ? 'active' : '';
			
			// Output toggle favorite link
			$output = '
				<div class="favorite ' . $favorite_class . '">
					<a href="' . $this->config['favorites_script_path'] . '/favorite.php?offer_id=' . $offer_id . '" data-offer-id="' . $offer_id . '" data-title="' . $favorite_link_title . '" data-label-add="' . $this->api->lang->get('favorites_add') . '" data-label-remove="' . $this->api->lang->get('favorites_remove') . '" class="tooltip" title="' . $favorite_link_title . '" role="button"><span aria-hidden="true">i</span></a>
				</div>
			';

		}

		return $output;
	}



	/**
	 * Get detail description
	 *
	 * @access protected
	 * @param object $offer
	 * @return mixed
	 */
	protected function _get_offer_dates($offer)
	{

		$dates = '';

		if (isset($offer->dates) && ! in_array($offer->root_category, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {

			// Count all dates
			$now = new DateTime();
			$date_counter = 0;
			$total_dates = 0;
			if (! empty($offer->dates)) {
				foreach ($offer->dates as $date) {

					// Prepare dates
					$date_from = new DateTime($date->date_from);
					$date_to = NULL;
					if (! empty($date->date_to)) {
						$date_to = new DateTime($date->date_to);
					}

					// Count only future events (exception: category product)
					if (($now < $date_from) || (! empty($date_to) && ($now < $date_to)) || ($offer->root_category == CATEGORY_PRODUCT)) {
						$total_dates++;
					}
				}
			}

			// Iterate all dates
			if (! empty($offer->dates)) {
				$dates = '<div class="dates">';
				foreach ($offer->dates as $date) {

					// Prepare dates
					$date_from = new DateTime($date->date_from);
					$date_to = NULL;
					if (! empty($date->date_to)) {
						$date_to = new DateTime($date->date_to);
					}

					// Prepare display limit
					$detail_date_display = (! empty($this->api->config['detail_limit_dates']) && ($this->api->config['detail_limit_dates'] > 0)) ? intval($this->api->config['detail_limit_dates']) : 4;

					// Show only future events (exception: category product)
					if (($now < $date_from) || (! empty($date_to) && ($now < $date_to)) || ($offer->root_category == CATEGORY_PRODUCT)) {

						// Count date
						$date_counter++;

						// Add date
						$dates .= parks_show_date(['date_from' => parks_mysql2form($date->date_from), 'date_to' => parks_mysql2form($date->date_to)], $this->api->lang) . '<br>';

						// Start hidden layer
						if ($total_dates > $detail_date_display) {
							if ($detail_date_display == $date_counter) {
								$dates .= '</div><div class="hidden_content" style="display: none;">';
							}

							// End hidden layer
							if ($total_dates == $date_counter) {
								$dates .= '</div>';
							}
						}
					}
				}

				// Show more/less buttons
				if ($total_dates > $detail_date_display) {
					$dates .= '
						<div class="more_less_buttons" aria-hidden="true">
							<a class="show_more" href="javascript:void(0);">' . $this->api->lang->get('show_more') . '</a>
							<a class="show_less" href="javascript:void(0);" style="display: none;">' . $this->api->lang->get('show_less') . '</a>
						</div>
					';
				} else {
					$dates .= '</div>';
				}
			} else if (! empty($offer->season_months)) {
				$season_months = explode(',', $offer->season_months);
				$season_month_labels = [];
				if (is_array($season_months) && ! empty($season_months)) {
					if (count($season_months) == 12) {
						$dates .= '<p>' . $this->api->lang->get('offer_all_season') . '</p>';
					}
					else {
						foreach ($season_months as $month) {
							$season_month_labels[] = $this->api->lang->get('month_long_' . $month);
						}
						$dates .= implode(', ', $season_month_labels);
					}
				}
			} else {
				$dates .= '<p>' . $this->api->lang->get('offer_all_season') . '</p>';
			}

			// Date condition
			if (empty($offer->dates) || ($total_dates > 0)) {

				// Set title
				$dates_title = $this->api->lang->get('offer_dates');
				if ($offer->root_category == CATEGORY_BOOKING) {
					$dates_title = $this->api->lang->get('offer_execution_times');
				}
				if (in_array($offer->root_category, [CATEGORY_PRODUCT, CATEGORY_ACTIVITY])) {
					$dates_title = $this->api->lang->get('offer_saison');
				}

				// Output
				$dates = $this->_show_text($dates_title, $dates, 'block offer_dates');
			}
		}

		return $dates;
	}



	/**
	 * Overwrite template data before they are loaded
	 *
	 * @access public
	 * @param mixed $template_data
	 * @return string
	 */
	public function overwrite_template_data($template_data, $offer)
	{
		return $template_data;
	}



	/**
	 * Get POI content
	 *
	 * @access protected
	 * @param array $pois
	 * @param int $offer_id
	 * @param string $original_category
	 * @return string
	 */
	protected function _get_poi_content($pois, $offer_id, $original_category)
	{
		$offers = $this->api->show_offer_poi_list($pois);
		$offers = array('data' => $offers, 'total' => count($offers));
		$output = $this->list_offers($offers, true, $offer_id, $original_category);
		return $output;
	}



	/**
	 * Get event detail
	 *
	 * @access protected
	 * @param object $offer
	 * @return string
	 */
	protected function _get_detail_event($offer)
	{

		// Load template data		
		$template_data['OFFER_ADDITIONAL_INFO'] = $this->_prepare_additional_infos($offer);
		$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);
		$template_data['OFFER_EVENT_LOCATION'] = trim($this->_show_text($this->api->lang->get('offer_event_location'), $offer->institution, 'block event_location'));
		$template_data['OFFER_EVENT_LOCATION_SHORT'] = ! empty($offer->institution_location) ? $offer->institution_location : '';
		$template_data['OFFER_EVENT_DATE_DETAILS'] = trim($this->_show_text($this->api->lang->get('offer_date_details'), $offer->details, 'block date_details'));
		$template_data['OFFER_EVENT_LOCATION_DETAILS'] = trim($this->_show_text($this->api->lang->get('offer_location_details'), $offer->location_details, 'block location_details'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_EVENT_TRANSPORT'] = trim($this->_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbb_link . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->api->lang->get('offer_link_sbb') . '">' . $this->api->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_EVENT_PRICE'] = trim($this->_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return $this->_compile_output($template_data);

	}



	/**
	 * Get product detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_product($offer)
	{
		$template_data['OFFER_ADDITIONAL_INFO'] = $this->_prepare_additional_infos($offer);
		if (empty($offer->online_shop_enabled)) {
			$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);
		}
		$template_data['OFFER_PRODUCT_OPENING_HOURS'] = trim($this->_show_text($this->api->lang->get('offer_opening_hours'), $offer->opening_hours, 'block opening_hours'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim($this->_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbb_link . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->api->lang->get('offer_link_sbb') . '">' . $this->api->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_PRODUCT_INFRASTRUCTURE'] = trim($this->_show_text($this->api->lang->get('offer_infrastructure'), $this->_get_detail_infrastructure($offer), 'block infrastructure'));

		// Load template data
		if (! empty($offer->online_shop_enabled)) {

			// Show article price
			$template_data['OFFER_PRODUCT_PRICE'] = '
				<div class="block product_price">
					<div class="description">
						<h2>' . $this->api->lang->get('offer_product_price') . '</h2>
						<div class="price">
							<span class="currency">CHF</span>
							<span class="value">' . $offer->online_shop_price . '</span>
						</div>
						<div class="order_information_wrap">
							<div class="order_information payment_info">
								<span class="title">' . $this->api->lang->get('offer_product_payment_modalities') . ':</span>
								<span class="description">' . $offer->online_shop_payment_terms . '</span>
							</div>
							<div class="order_information delivery_info">
								<span class="title">' . $this->api->lang->get('offer_product_delivery_conditions') . ':</span>
								<span class="description">' . $offer->online_shop_delivery_conditions . '</span>
							</div>
						</div>
					</div>
				</div>
			';
		} else {
			$template_data['OFFER_PRODUCT_PRICE'] = trim($this->_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));
		}

		// Compile template data
		return $this->_compile_output($template_data);
	}



	/**
	 * Get booking detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_booking($offer)
	{

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = $this->_prepare_additional_infos($offer);
		$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);
		$template_data['OFFER_BOOKING_GROUPS'] = $this->_get_detail_groups($offer);
		$template_data['OFFER_BOOKING_BENEFITS'] = trim($this->_show_text($this->api->lang->get('offer_benefits'), $offer->benefits, 'block benefits'));
		$template_data['OFFER_BOOKING_REQUIREMENTS'] = trim($this->_show_text($this->api->lang->get('offer_requirements'), $offer->requirements, 'block requirements'));
		$template_data['OFFER_BOOKING_ACCOMMODATIONS'] = trim($this->_show_text($this->api->lang->get('offer_accommodation'), $this->_get_accommodations($offer), 'block accommodations'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_BOOKING_TRANSPORT'] = trim($this->_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbb_link . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->api->lang->get('offer_link_sbb') . '">' . $this->api->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_BOOKING_PRICE'] = trim($this->_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return $this->_compile_output($template_data);
	}



	/**
	 * Get activity detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_activity($offer)
	{

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = $this->_prepare_additional_infos($offer);
		$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);
		$template_data['OFFER_ACTIVITY_ROUTE'] = trim($this->_show_text($this->api->lang->get('offer_route_info'), $this->_get_route_details($offer), 'block route_info rwd-info-block'));
		$template_data['OFFER_ACTIVITY_ARRIVAL'] = trim($this->_show_text($this->api->lang->get('offer_arrival'), $this->_get_route_start_stop($offer), 'block arrival rwd-info-block'));
		$template_data['OFFER_ACTIVITY_SIGNALIZATION'] = trim($this->_show_text($this->api->lang->get('offer_signalization'), $offer->signalization, 'block signalization'));
		$template_data['OFFER_ACTIVITY_SAFETY_INSTRUCTIONS'] = trim($this->_show_text($this->api->lang->get('offer_safety_instructions'), $offer->safety_instructions, 'block safety_instructions'));
		$template_data['OFFER_ACTIVITY_MATERIAL_RENT'] = trim($this->_show_text($this->api->lang->get('offer_material_rent'), $offer->material_rent, 'block material_rent'));
		$template_data['OFFER_ACTIVITY_INFRASTRUCTURE'] = trim($this->_show_text($this->api->lang->get('offer_infrastructure'), $this->_get_detail_infrastructure($offer), 'block infrastructure'));
		$template_data['OFFER_ACTIVITY_PRICE'] = trim($this->_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));
		$template_data['OFFER_ACTIVITY_CATERING'] = trim($this->_show_text($this->api->lang->get('offer_catering_informations'), $offer->catering_informations, 'block catering_informations'));

		// Compile template data
		return $this->_compile_output($template_data);
	}



	/**
	 * Get project detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_project($offer)
	{

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = $this->_prepare_additional_infos($offer);
		$template_data['OFFER_PROJECT_DURATION'] = $this->_prepare_project_duration($offer->duration_from, $offer->duration_to, $offer->duration_from_month, $offer->duration_to_month);
		$template_data['OFFER_PROJECT_STATUS'] = trim($this->_show_text($this->api->lang->get('offer_project_status'), $this->config['project_status_' . $this->api->lang_id][$offer->project_status], 'block project_status'));

		// Compile template data
		return $this->_compile_output($template_data);
	}



	/**
	 * Get overview map
	 *
	 * @param array $offers
	 * @param boolean $poi_detail
	 * @param boolean $offer_id
	 * @return string
	 */
	public function _get_overview_map($offers, $poi_detail = false, $offer_id = 0)
	{

		if (empty($offers)) {
			return '';
		}

		// Collect offers by categories
		$categories_offers_view = collect_categories_by_offers($offers);

		// Get offer detail link
		$offer_detail_url = '';
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
			$offer_detail_url = $this->get_seo_detail_url() . '/';
		} else {
			$offer_detail_url = $this->script_url . (strstr($this->script_url, '?') ? '&amp' : '?') . $this->config['url_param_prefix'] . 'offer' . '=';
		}

		$map_view = '
			<div id="mapContainer" style="min-height: 600px;"></div>
			<script>
				window.parksMapConfig = {
					containerId: 				\'mapContainer\',
					initializeOnLoad: 			' . (! empty($this->api->map_options['map_initialize_on_load']) ? 'true' : 'false') . ',
					show_layers_at_start: 		true,
					parkperimeter_visibility: 	' . (! empty($this->api->map_options['parkperimeter_visibility']) ? 'true' : 'false') . ',
					link_target: 				\'_self\',
					full_height: 				' . (! empty($this->api->map_options['full_height']) ? 'true' : 'false') . ',
					language: 					\'' . $this->api->lang_id . '\',
					mode: 						\'filter\',
					syncSettings: 				{ enableUrlSync: false, enableCookieSync: false },
					offersData: 				{ categories: { ' . $categories_offers_view . ' }},
					popup_link_path: 			\'' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $offer_detail_url . '\',
					seo_url:					' . (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) ? 'true' : 'false') . ',
					' . (! empty($this->config['park_id']) ? '
					customWfsLayers: 			[' . $this->_get_map_layers() . '],
					parks_abbreviation: 		\'' . $this->config['parks'][$this->config['park_id']] . '\', 
					' : '') . '
					api_key: 					\'' . $this->config['api_hash'] . '\'
				};
			</script>
		';

		if ($this->return_output == true) {
			return $map_view;
		} else {
			echo $map_view;
			return '';
		}

	}



	/**
	 * Get detail map
	 *
	 * @access protected
	 * @param object $offer
	 * @return string
	 */
	protected function _get_detail_map($offer)
	{

		if (empty($offer) || empty($offer->offer_id)) {
			return '';
		}

		// Get POIs of this offer
		$pois = [];
		if (! empty($offer->poi) && (in_array($offer->root_category, [CATEGORY_ACTIVITY, CATEGORY_PROJECT, CATEGORY_RESEARCH]))) {
			$pois = $this->api->show_offer_poi_list($offer->poi);
		}

		// Collect offers by categories
		$categories_offers_view = collect_categories_by_offers([$offer] + $pois, true);

		// Get offer detail link
		if (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true)) {
			$offer_detail_url = $this->get_seo_detail_url();
		} else {
			$param_name = $this->config['url_param_prefix'] . 'offer';
			$offer_detail_url = $this->script_url . (strstr($this->script_url, '?') ? '&amp' : '?') . $param_name . '=';
		}

		$map_view = '
			<div id="mapContainer" style="min-height: 600px;"></div>
			<div id="elevation-profile-container"></div>
			<script>
				window.parksMapConfig = {
					containerId: 				\'mapContainer\',
					initializeOnLoad: 			' . (! empty($this->api->map_options['map_initialize_on_load']) ? 'true' : 'false') . ',
					show_layers_at_start: 		true,
					parkperimeter_visibility: 	' . (! empty($this->api->map_options['parkperimeter_visibility']) ? 'true' : 'false') . ',
					link_target: 				\'_self\',
					full_height: 				' . (! empty($this->api->map_options['full_height']) ? 'true' : 'false') . ',
					language: 					\'' . $this->api->lang_id . '\',
					mode: 						\'detailmap\',
					detailOfferId: 				\'' . $offer->offer_id . '\',
					offersData: 				{ categories: { ' . $categories_offers_view . ' }},
					popup_link_path: 			\'' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $offer_detail_url . '\',
					seo_url:					' . (! empty($this->config['seo_urls']) && ($this->config['seo_urls'] === true) ? 'true' : 'false') . ',
					parks_abbreviation: 		\'' . $this->config['parks'][$offer->park_id] . '\', 
					api_key: 					\'' . $this->config['api_hash'] . '\',
				};
			</script>
		';

		return $map_view;

	}



	/**
	 * Get map layers
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _get_map_layers()
	{
		$map_layers = $this->api->model->get_custom_layers();

		if (empty($map_layers)) {
			return '';
		}
	
		$map_layer_view = '';

		foreach ($map_layers as $layer) {

			// Popup title
			$popup_title = 'Layer ' . $layer->layer_position;
			if (! empty($layer->popup_title)) {
				$popup_title = str_replace(['{', '}'], '', $layer->popup_title);
			}

			// Map layer
			$map_layer_view .= '
				{
					layer: {
						id: \'custom-layer-' . md5($layer->map_layer_id) . '\',
						url: \'' . $layer->url . '\',
						title: \'' . ($layer->i18n[$this->api->lang_id]['layer_title'] ?? 'Layer ' . $layer->layer_position) . '\',
						options: { outFields: [\'*\'] }
					},
					target: {
						categoryId: \'' . ($layer->layer_category ?? 'additional') . '\',
						position: ' . $layer->layer_position . ',
					},
					popup: {
						titleField: \'' . $popup_title . '\',
						imageUrl: \'' . (! empty($layer->popup_logo) ? $this->config['base_url'] . $layer->popup_logo : '') . '\',
						contentTemplate: \'' . str_replace(["'", "\r\n", "\n"], ["\'", "<br>", "<br>"], trim($layer->i18n[$this->api->lang_id]['popup_content'] ?? '')) . '\',
					},
					visible: ' . ($layer->visible_by_default ? 'true' : 'false') . '
				}' . ($layer->map_layer_id != end($map_layers)->map_layer_id ? ',' : '') . '
			';
		}

		return $map_layer_view;
	}



	/**
	 * Get subscription detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_subscription($offer)
	{

		$return = '
			<div class="detail_text">
				<p>
					' . (($offer->subscription_mandatory == 2) ? $this->api->lang->get('offer_subscription_info_recommended') : $this->api->lang->get('offer_subscription_info_mandatory')) . '
				</p>
		';

		// Subscription details
		if (! empty($offer->subscription_details) && ($offer->online_subscription_enabled == false)) {
			$return .= '
				<p>
					' . auto_text_format($offer->subscription_details) . '
				</p>
			';
		}

		// Subscription contact
		if (! empty($offer->subscription_contact)) {
			$return .= '
				<h2>' . $this->api->lang->get('offer_subscription_contact') . '</h2>
				<p>
					' . auto_text_format($offer->subscription_contact) . '
				</p>
			';
		}

		// Online subscription link
		if ($offer->online_subscription_enabled == true) {
			$return .= '
				<div class="text_right">
					<a class="sign_in button" target="_blank" href="' . $this->config['base_url'] . $this->api->lang_id . '/subscription/subscriber/' . $offer->offer_id . '">' . $this->api->lang->get('offer_subscription_subscribe_now') . '</a>
				</div>
			';
		} else if (! empty($offer->subscription_link)) {
			$return .= '
				<div class="text_right">
					<a class="sign_in button" target="_blank" href="' . $offer->subscription_link . '">' . $this->api->lang->get('offer_subscription_subscribe_now') . '</a>
				</div>
			';
		}

		$return .= '</div>';

		return $return;
	}



	/**
	 * Get infrastructure details
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_infrastructure($offer)
	{

		// Other infrastructure
		$other_infrastructure = '';
		if (! empty($offer->other_infrastructure)) {
			$other_infrastructure = '<p>' . auto_text_format($offer->other_infrastructure) . '</p>';
		}

		// Infrastructure
		$infrastructure = [];
		if (! empty($offer->number_of_rooms)) {
			$infrastructure[] = sprintf($this->api->lang->get('offer_rooms'), $offer->number_of_rooms);
		}
		if (! empty($offer->has_conference_room)) {
			$infrastructure[] = $this->api->lang->get('offer_conference_room');
		}
		if (! empty($offer->has_playground)) {
			$infrastructure[] = $this->api->lang->get('offer_playground');
		}
		if (! empty($offer->has_picnic_place)) {
			$infrastructure[] = $this->api->lang->get('offer_picnic_place');
		}
		if (! empty($offer->has_fireplace)) {
			$infrastructure[] = $this->api->lang->get('offer_fireplace');
		}
		if (! empty($offer->has_washrooms)) {
			$infrastructure[] = $this->api->lang->get('offer_washroom');
		}
		$infrastructure = (count($infrastructure) > 0) ? '<ul><li>' . implode('</li><li>', $infrastructure) . '</li></ul>' : '';

		// Return details
		return $other_infrastructure . $infrastructure;
	}



	/**
	 * Get accommodations
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_accommodations($offer)
	{
		$accommodations = '';

		if (isset($offer->accommodations) && ! empty($offer->accommodations)) {
			foreach ($offer->accommodations as $accommodation) {
				if (! empty($accommodation['is_park_partner'])) {
					$accommodations .= '<div class="partner"><i aria-hidden="true">' . $this->api->lang->get('offer_park_partner') . '</i></div>';
				}
				$accommodations .= '<p>' . auto_text_format($accommodation['contact']) . '</p>';
			}
		}

		return $accommodations;
	}



	/**
	 * Get route details
	 *
	 * @access protected
	 * @param object $offer
	 * @return string
	 */
	protected function _get_route_details($offer)
	{
		$route_details = '';

		if (! empty($offer)) {
			if (! empty($offer->route_length) && ($offer->route_length > 0)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_route_length') . '</dt><dd>' . ((intval($offer->route_length) > 0) ? $offer->route_length : '') . ' ' . $this->api->lang->get('offer_route_length_km') . '</dd>';
			}
			if (! empty($offer->untarred_route_length) && ($offer->untarred_route_length > 0)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_untarred_route_length') . '</dt><dd>' . ((intval($offer->untarred_route_length) > 0) ? $offer->untarred_route_length : '') . ' ' . $this->api->lang->get('offer_route_length_km') . '</dd>';
			}
			if (! empty($offer->altitude_differential) && ($offer->altitude_differential > 0)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_altitude_differential') . '</dt><dd>' . $offer->altitude_differential . ' ' . $this->api->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->altitude_ascent) && ($offer->altitude_ascent > 0)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_altitude_ascent') . '</dt><dd>' . $offer->altitude_ascent . ' ' . $this->api->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->altitude_descent) && ($offer->altitude_descent > 0)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_altitude_descent') . '</dt><dd>' . $offer->altitude_descent . ' ' . $this->api->lang->get('offer_altitude_meter') . '</dd>';
			}
			if (! empty($offer->time_required) || ! empty($offer->time_required_minutes)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_time_required') . '</dt><dd>' . activity_get_time_required($offer, $this->api->lang) . '</dd>';
			}
			if (! empty($offer->level_technics)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_level_technics') . '</dt><dd>' . $this->api->model->levels[$offer->level_technics] . '</dd>';
			}
			if (! empty($offer->level_condition)) {
				$route_details .= '<dt>' . $this->api->lang->get('offer_level_condition') . '</dt><dd>' . $this->api->model->levels[$offer->level_condition] . '</dd>';
			}
			if (! empty($route_details)) {
				$route_details = '<dl>' . $route_details . '</dl>';
			}
		}

		return $route_details;
	}



	/**
	 * Show offer start and stop place
	 *
	 * @access protected
	 * @param object $offer
	 * @return string
	 */
	protected function _get_route_start_stop($offer)
	{
		$start_stop_place = '';

		if (! empty($offer)) {

			if (! empty($offer->start_place_info) || ! empty($offer->goal_place_info)) {
				$start_stop_place .= '<dl>';
			}

			// Start
			if (! empty($offer->start_place_info)) {
				$start_stop_place .= '<dt>' . $this->api->lang->get('offer_start_place') . '</dt><dd>' . nl2br($offer->start_place_info);
				if (! empty($offer->start_place_altitude)) {
					$start_stop_place .= ' (' . $this->api->lang->get('offer_altitude') . ': ' . $offer->start_place_altitude . ' ' . $this->api->lang->get('offer_altitude_meter') . ')';
				}
				$start_stop_place .= '</dd>';
				if (! empty($offer->public_transport_start) && (strlen($offer->public_transport_start) >= $this->config['min_chars_sbb_link'])) {
					$start_stop_place .= '<dt>' . $this->api->lang->get('offer_public_transport_stop') . '</dt><dd>' . $offer->public_transport_start . ' <a href="' . $this->sbb_link . '?nach=' . urlencode($offer->public_transport_start) . '" target="_blank" class="sbb" title="' . $this->api->lang->get('offer_link_sbb') . '">' . $this->api->lang->get('offer_timetable_sbb') . '</a></dd>';
				}
			}

			// Stop / goal
			if (! empty($offer->goal_place_info)) {
				$start_stop_place .= '<div class="content-margin-cf"></div><dt>' . $this->api->lang->get('offer_goal_place') . '</dt><dd>' . nl2br($offer->goal_place_info);
				if (! empty($offer->goal_place_altitude)) {
					$start_stop_place .= ' (' . $this->api->lang->get('offer_altitude') . ': ' . $offer->goal_place_altitude . ' ' . $this->api->lang->get('offer_altitude_meter') . ')';
				}
				$start_stop_place .= '</dd>';
				if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
					$start_stop_place .= '<dt>' . $this->api->lang->get('offer_public_transport_stop') . '</dt><dd>' . $offer->public_transport_stop . ' <a href="' . $this->sbb_link . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->api->lang->get('offer_link_sbb') . '">' . $this->api->lang->get('offer_timetable_sbb') . '</a></dd>';
				}
			}

			if (! empty($offer->start_place_info) || ! empty($offer->goal_place_info)) {
				$start_stop_place .= '</dl>';
			}
		}

		return $start_stop_place;
	}



	/**
	 * Get group details
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_groups($offer)
	{

		// Group subscriber
		$group_subscriber = '';
		if (! empty($offer->min_group_subscriber) || ! empty($offer->max_group_subscriber)) {

			// Min/max subscribers
			if (! empty($offer->min_group_subscriber)) {
				$group_subscriber .= $this->api->lang->get('offer_min_subscriber') . ' ' . $offer->min_group_subscriber . '<br>';
			}
			if (! empty($offer->max_group_subscriber)) {
				$group_subscriber .= $this->api->lang->get('offer_max_subscriber') . ' ' . $offer->max_group_subscriber;
			}

			// Prepare view
			$group_subscriber = $this->_show_text($this->api->lang->get('offer_number_of_people_groups'), $group_subscriber, 'block number_of_people_groups');
		}

		// Individual subscriber
		$individual_subscriber = '';
		if (! empty($offer->min_individual_subscriber) || ! empty($offer->max_individual_subscriber)) {

			// Min/max subscribers
			if (! empty($offer->min_individual_subscriber)) {
				$individual_subscriber .= $this->api->lang->get('offer_min_subscriber') . ' ' . $offer->min_individual_subscriber . '<br>';
			}
			if (! empty($offer->max_individual_subscriber)) {
				$individual_subscriber .= $this->api->lang->get('offer_max_subscriber') . ' ' . $offer->max_individual_subscriber;
			}

			// Prepare view
			$individual_subscriber = $this->_show_text($this->api->lang->get('offer_number_of_people_individual'), $individual_subscriber, 'block number_of_people_individual');
		}

		return $group_subscriber . $individual_subscriber;
	}



	/**
	 * Prepare additional informations
	 *
	 * @param object $offer
	 * @access protected
	 * @return string
	 */
	protected function _prepare_additional_infos($offer)
	{
		$additional_info = [];

		// Prepare options
		if (! empty($offer->barrier_free)) {
			$additional_info[] = $this->api->lang->get('offer_barrier_free');
		}
		if (! empty($offer->learning_opportunity)) {
			$additional_info[] = $this->api->lang->get('offer_learning_opportunity');
		}
		if (! empty($offer->child_friendly)) {
			$additional_info[] = $this->api->lang->get('offer_child_friendly');
		}

		// Prepare output
		if (! empty($additional_info)) {
			$additional_info = '<p>- ' . implode('<br>- ', $additional_info) . '</p>';
		} else {
			$additional_info = '';
		}

		// Return string
		if (! empty($offer->additional_informations) || ! empty($additional_info)) {
			return $this->_show_text('', '<p>' . auto_text_format($offer->additional_informations) . '</p>' . $additional_info, 'block additional_informations');
		}

		return '';
	}



	/**
	 * Show project duration ({M} {Y} - {M} {Y})
	 *
	 * @param int $from_year
	 * @param int $to_year
	 * @param int $from_month
	 * @param int $to_month
	 * @param bool $is_overview
	 * @return string
	 */
	protected function _prepare_project_duration($from_year, $to_year, $from_month = null, $to_month = null, $is_overview = false)
	{
		$output = '';

		if (! empty($from_year)) {

			// Output if year is not the same:
			// => «From {M} {Y} until {M} {Y}»
			if ($from_year != $to_year) {

				if (! $is_overview) {
					$output .= $this->api->lang->get('offer_from') . ' ';
				}

				$output .= '<strong>';

				if ($from_month > 0) {
					$output .= $this->api->lang->get('month_long_' . $from_month) . ' ';
				}

				if ($is_overview) {
					$output .= $from_year . ' – ';
				} else {
					$output .= $from_year . '</strong> ' . $this->api->lang->get('offer_to') . ' <strong>';
				}

				if ($to_month > 0) {
					$output .= $this->api->lang->get('month_long_' . $to_month) . ' ';
				}
				$output .=  $to_year . '</strong>';
			}

			// Output if year is the same
			// => «From {M} until {M} {Y}»
			else {
				if (($from_month > 0) && ($to_month > 0) && ($from_month != $to_month)) {
					if (! $is_overview) {
						$output .= $this->api->lang->get('offer_from') . ' ';
					}
					$output .= '<strong>' . $this->api->lang->get('month_long_' . $from_month) . ' ' . $this->api->lang->get('offer_to') . ' ' . $this->api->lang->get('month_long_' . $to_month) . ' ' . $from_year . '</strong>';
				} else {
					$output .= '<strong>';
					if ($from_month > 0) {
						$output .= $this->api->lang->get('month_long_' . $from_month) . ' ';
					}
					$output .= $from_year . '</strong>';
				}
			}
		} else if ($is_overview == false) {
			$output = $this->api->lang->get('offer_project_no_duration');
		}

		// Return string
		if (! empty($output)) {
			if ($is_overview == false) {
				return $this->_show_text($this->api->lang->get('offer_project_duration'), $output, 'block project_duration');
			} else {
				return $output;
			}
		}

		return '';
	}



	/**
	 * Get string
	 *
	 * @access protected
	 * @param mixed $string
	 * @param string $suffix (default: "")
	 * @param string $prefix (default: "")
	 * @return string
	 */
	protected function _get_string($string, $suffix = "", $prefix = "")
	{
		if (! empty($string)) {
			return $prefix . $string . $suffix;
		}

		return "";
	}



	/**
	 * Prepare detail block
	 *
	 * @access protected
	 * @param mixed $title
	 * @param mixed $content
	 * @param string $class (default: "")
	 * @return string
	 */
	protected function _show_text($title, $content, $class = "")
	{

		$return = "";

		// Check html tags
		$has_html = false;
		if (! empty($content) && ($content != strip_tags($content))) {
			$has_html = true;
		}

		// Output
		if (! empty($content)) {
			$return .= '
				<div class="' . $class . '">
					<div class="description">
						' . (! empty($title) ? '<h2>' . $title . '</h2>' : '') .
						(($has_html == true) ? $content : auto_text_format($content)) . '
					</div>
				</div>
			';
		}

		return $return;
	}



	/**
	 * Compile template output
	 *
	 * @access protected
	 * @param array $template_data
	 * @return string
	 */
	protected function _compile_output($template_data)
	{
		$output = '';

		if (! empty($template_data)) {

			// Iterate template placeholders
			foreach ($template_data as $key => $value) {

				// Set template tag
				$this->detail_template_tags[$key] = $value;

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
	 *
	 * @access public
	 * @param int $offer_id
	 * @param string $title
	 * @param int $poi
	 * @param bool $include_script_url
	 * @return string
	 */
	public function get_seo_detail_url($offer_id = 0, $title = '', $poi = NULL, $include_script_url = true)
	{

		// Get detail slugs
		$detail_slug = ! empty($this->config['seo_url_detail_slug']) ? $this->config['seo_url_detail_slug'] : '';
		$poi_slug = ! empty($this->config['seo_url_poi_slug']) ? $this->config['seo_url_poi_slug'] : '';
		$reset_slug = ! empty($this->config['seo_url_reset_slug']) ? $this->config['seo_url_reset_slug'] : '';

		// Generate title slug
		$title_slug = url_slug($title, array('transliterate' => true));

		// Prepare script url
		$script_url = $this->script_url;

		// Remove params from script url
		$script_url = strtok($script_url, '?');
		$script_url = strtok($script_url, '&');

		// Remove detail segments for POI urls
		if (strstr($script_url, '/' . $detail_slug . '/')) {
			$detail_segment_pos = strpos($script_url, '/' . $detail_slug);
			$script_url = substr($script_url, 0, $detail_segment_pos);
		}

		// Remove double slash
		if (substr($script_url, -1) != '/') {
			$script_url .= '/';
		}

		if (empty($offer_id)) {
			return $script_url . $detail_slug;
		}

		// Add poi
		if (! empty($poi)) {
			$poi = '/' . $poi_slug . '/' . intval($poi);
		}

		// Set url
		if ($include_script_url == true) {
			$url = $script_url . $detail_slug . '/' . $title_slug . '-' . $offer_id . $poi;
		} else {
			$url = '/' . $detail_slug . '/' . $title_slug . '-' . $offer_id . $poi;
		}

		// Clean url from double slashes
		$url = str_replace('//', '/', $url);

		// Clean url from reset param
		$url = str_replace('/' . $reset_slug, '', $url);

		// Set detail url
		return $url;
	}



}