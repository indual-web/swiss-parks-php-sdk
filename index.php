<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK — example integration
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Copy this file into your project and adapt language, filters, and layout.
| Before first use, set api_hash and park_id in swiss-parks-sdk/config.php
| and run the import:
| https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/guides/new-installation.md
|
*/


// Available languages: de, fr, it, en
$language = 'de';

require_once('swiss-parks-sdk/autoload.php');

// Optional 2nd argument: alternative api_hash (otherwise config.php is used)
$api = new ParksAPI($language);


// Limit the overview to category IDs, or leave empty for all categories
$categories = [];

// Optional result filters:
// https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/sdk-reference/filter-options.md
$filter = [];


// Optional map behaviour:
// https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/sdk-reference/map-options.md
$api->map_options = [];

?>
<!DOCTYPE html>
<html lang="<?= $language ?>">
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title>Swiss Parks PHP SDK — Example</title>

	<!--
		Required SDK frontend assets (CSS + JS). Host them yourself or keep this CDN.
		Use a parks.min.js that knows container id "swiss-parks-map" (staging-map below).
		Older builds still look for "mapContainer" and the map tab will stay empty.
	-->
	<link href="https://staging-map.angebote.paerke.ch/dist/frontend/v22/css/parks.min.css" rel="stylesheet">
	<script type="module" src="https://staging-map.angebote.paerke.ch/dist/frontend/v22/parks.min.js"></script>

</head>
<body>

	<!-- Example: use is_offer_detail() to switch layout/CSS for the detail page -->
	<div id="parks_offer_wrap" class="<?= $api->is_offer_detail() ? 'detail' : '' ?>">

		<?php if ($api->is_offer_detail()) { ?>

			<?= $api->show_offer_detail() ?>

		<?php } else { ?>

			<?php // Filter form (categories, search, …) ?>
			<?= $api->show_offers_filter($categories, $filter) ?>


			<!--
				Tabs: list and map (markup expected by parks.min.js).
				Active tab is kept in #tab_1 / #tab_2 and restored after detail “back”.
			-->
			<div id="content_top">
				<ul class="tab_list move_offer_total" role="tablist" aria-hidden="true">
					<li data="tab_1" role="tab" class="current" aria-controls="tab_1"><?= $api->lang->get('offer_list') ?></li>
					<li data="tab_2" role="tab" aria-controls="tab_2"><?= $api->lang->get('offer_map') ?></li>
				</ul>
			</div>

			<div id="content">

				<div class="tab_content tab_content_1 show">
					<?= $api->show_offers_list($categories, $filter) ?>
					<?= $api->show_offers_pagination() ?>
				</div>

				<div class="tab_content tab_content_2" aria-hidden="true">
					<?= $api->show_offers_map($categories, $filter) ?>
				</div>

			</div>

		<?php } ?>

	</div>

</body>
</html>
