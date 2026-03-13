<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Example for displaying a list of offers using the parks.swiss API
|
*/

// Set current language
$language = 'de';

// Include API
require_once('parks_api/autoload.php');

// Initialize API with default language and optional with an alternative hash
$api = new ParksAPI($language);

?>
<!DOCTYPE html>
<html lang="<?= $language ?>">
<head>
	
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title>parks.swiss API Example</title>

	<!-- Parks API CSS  -->
	<link href="https://angebote.paerke.ch/assets/dist/v22/css/parks.min.css" rel="stylesheet">

	<!-- Parks API JS -->
	<script type="module" src="https://angebote.paerke.ch/assets/dist/v22/parks.min.js"></script>
	
</head>
<body>
	<div id="parks_offer_wrap" class="<?php echo $api->is_offer_detail() ? 'detail' : ''; ?>">
		<?php

		// Map options
		$api->map_options = [
			/*
			'map_initialize_on_load' => false, 				// Load map on page load
			'show_layers_at_start' => false, 				// Show/hide layers at start after loading
			'parkperimeter_visibility' => true,				// Show/hide swiss parks perimeter after loading
			'associated_members_visibility' => true,		// Show/hide associated members layer
			'link_target' => '_blank', 						// Set the link target for offer detail links in the map
			'full_height' => false, 						// Show the map over full window height
			'disable_auto_load_oev' => true,				// Disable auto loading oev layer lower than zoom level 0.3km			
			'map_extent' => array(							// Overwrite init extent on overview maps and set your own
				'xmin' => 2590807.0,
				'ymin' => 1130285.0,
				'xmax' => 2736607.0,
				'ymax' => 1235385.0,
			),
			'do_not_group_categories_in_legend' => true,	// Do not group offer categories in legend
			*/
		];

		// Filter settings
		$filter = [
			/*
			'keywords' => '', 							// Filter by keywords
			'contact_is_park_partner' => 0, 			// 1 == show only offers from park partners
			'target_groups' => array(), 				// Filter by target groups
			'fields_of_activity' => array(), 			// Filter by fields of activity
			'search' => '', 							// Filter by an explicit word
			'online_shop_enabled' => 0, 				// 1 = show online shop products only
			'barrier_free' => 0, 						// 1 = barrier free offers
			'learning_opportunity' => 0, 				// 1 = learning opportunity offers
			'child_friendly' => 0, 						// 1 = child friendly offers
			'is_hint' => 0, 							// 1 = show only hints (Tipps)
			'offers_is_park_event' => 1,				// 1 = show only park events	
			'has_accessibility_informations' => true,	// true = Filter offers with accessibility informations
			'offers' => array(),						// Filter by offer ids
			'show_keywords_filter' => true,				// Show keywords filter
			'hide_user_filter' => false,				// Hide user filter if more than one park is listed as dropdown in the filter
			'hide_accessibility_filter' => false,		// Hide accessibility filter
			'system_filter' => array(					// Additional filter set by your system
				'target_groups' => array(),					// Show only offers for this target groups
				'fields_of_activity' => array()				// Show only offers in this fields of activity
			)
			*/
		];

		// Filter categories
		$categories = [];

		// Show detail page
		if ($api->is_offer_detail()) {
			$api->show_offer_detail();
		}

		// Show offer listing
		else {
			// Show offer filter
			$api->show_offers_filter($categories, $filter);
			?>
			<div id="content_top">
				<ul class="tab_list move_offer_total" role="tablist" aria-hidden="true">
					<li data="tab_1" role="tab" class="current" aria-controls="tab_1"><?php echo $api->lang->get('offer_list'); ?></li>
					<li data="tab_2" role="tab" class="" aria-controls="tab_2"><?php echo $api->lang->get('offer_map'); ?></li>
				</ul>
			</div>
			<div id="content">
				<div class="tab_content tab_content_1 show">
					<?php
					$api->show_offers_list($categories, $filter);
					$api->show_offers_pagination();
					?>
				</div>
				<div class="tab_content tab_content_2" aria-hidden="true">
					<?php $api->show_offers_map($categories, $filter); ?>
				</div>
			</div>
			<?php
		}
		?>
	</div>

</body>
</html>