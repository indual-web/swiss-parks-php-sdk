<?php
/*
| Swiss Parks PHP SDK — example integration
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
| Copy this file into your project and adapt language, filters, and layout.
| Before first use, set api_hash and park_id in swiss-parks-sdk/config/config.local.php
| and run the import:
| https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/guides/new-installation.md
|
*/
use SwissParks\Sdk\SwissParksSdk;


// Available languages: de, fr, it, en
$language = 'de';

require_once('swiss-parks-sdk/autoload.php');

// Optional 2nd argument: alternative api_hash (otherwise the configured api_hash is used)
$sdk = new SwissParksSdk($language);


// Limit the overview to category IDs, or leave empty for all categories
$categories = [];

// Optional result filters:
// https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/sdk-reference/filter-options.md
$filter = [];


// Optional map behaviour:
// https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/sdk-reference/map-options.md
$sdk->mapOptions = [];


// SEO URLs only: redirect outdated detail URLs (301) and answer unknown offers with 404
// Must run before any HTML output
$sdk->enforceOfferDetailCanonicalUrl();

?>
<!DOCTYPE html>
<html lang="<?= $language ?>">
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title>Swiss Parks PHP SDK — Example</title>

	<!--
		Required SDK frontend assets (CSS + JS) for SDK line v22:
		- parks.min.css / parks.min.js under …/dist/frontend/v22/
		- parks.min.js must target container id "swiss-parks-map"
		  Older builds still look for "mapContainer" and the map tab stays empty
	-->
	<link href="https://hub.parks.swiss/dist/frontend/v22/css/parks.min.css" rel="stylesheet">
	<script type="module" src="https://hub.parks.swiss/dist/frontend/v22/parks.min.js"></script>

</head>
<body>

	<!-- Example: use isOfferDetail() to switch layout/CSS for the detail page -->
	<div id="parks_offer_wrap" class="<?= $sdk->isOfferDetail() ? 'detail' : '' ?>">

		<?php if ($sdk->isOfferDetail()) { ?>

			<?= $sdk->showOfferDetail() ?>

		<?php } else { ?>

			<?php // Filter form (categories, search, …) ?>
			<?= $sdk->showOffersFilter($categories, $filter) ?>


			<!--
				Tabs: list and map (markup expected by parks.min.js).
				Active tab is kept in #tab_1 / #tab_2 and restored after detail “back”.
			-->
			<div id="content_top">
				<ul class="tab_list move_offer_total" role="tablist" aria-hidden="true">
					<li data="tab_1" role="tab" class="current" aria-controls="tab_1"><?= $sdk->lang->get('offer_list') ?></li>
					<li data="tab_2" role="tab" aria-controls="tab_2"><?= $sdk->lang->get('offer_map') ?></li>
				</ul>
			</div>

			<div id="content">

				<div class="tab_content tab_content_1 show">
					<?= $sdk->showOffersList($categories, $filter) ?>
					<?= $sdk->showOffersPagination() ?>
				</div>

				<div class="tab_content tab_content_2" aria-hidden="true">
					<?= $sdk->showOffersMap($categories, $filter) ?>
				</div>

			</div>

		<?php } ?>

	</div>

</body>
</html>
