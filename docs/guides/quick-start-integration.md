[Docs](../index.md) › Quick start integration

# Quick start integration

How to render the SDK on a page. A complete example with list/map tabs is `index.php` in the release ZIP.

## Page code

Load the SDK, then render either the detail or the overview:

```php
<?php

use SwissParks\Sdk\SwissParksSdk;

// ZIP installation
require_once __DIR__ . '/swiss-parks-sdk/autoload.php';

// Composer installation (instead of the line above)
// require __DIR__ . '/../vendor/autoload.php';
// define('SWISS_PARKS_SDK_CONFIG', __DIR__ . '/../config/parks.php');

$sdk = new SwissParksSdk('en');

$categories = [];
$filter = [];

if ($sdk->isOfferDetail()) {
	$sdk->showOfferDetail();
} else {
	$sdk->showOffersFilter($categories, $filter);
	$sdk->showOffersList($categories, $filter);
	$sdk->showOffersPagination();
}
```

- `$categories` limits the overview to category IDs, `$filter` sets filter values and UI toggles ([filter options](../sdk-reference/filter-options.md)).
- Methods print their HTML. Set `return_output = true` to get strings instead ([configuration](./configuration.md#output-and-cms-embeds)).
- All methods: [SwissParksSdk methods](../sdk-reference/swiss-parks-sdk.md).

## Frontend assets

The SDK never adds `parks.min.css` and `parks.min.js` itself. Load the v22 bundle on every page that renders SDK output, with the script as module:

```html
<link href="https://hub.parks.swiss/dist/frontend/v22/css/parks.min.css" rel="stylesheet">
<script type="module" src="https://hub.parks.swiss/dist/frontend/v22/parks.min.js"></script>
```

- The map container id is `swiss-parks-map`. Older builds looked for `mapContainer` and leave the map tab empty.
- The interactive map library (`map.parks.swiss`) is added automatically when a map is rendered, unless `prevent_css_js_include = true` ([configuration](./configuration.md#output-and-cms-embeds)).

## List/map tabs and back navigation

`parks.min.js` stores the active overview tab in the URL hash (`#tab_1` list, `#tab_2` map) and mirrors it in `sessionStorage`. When the detail back link returns to the overview without a hash (typical in CMS embeds), the last tab is restored.

This works in plain PHP, WordPress and other CMS embeds as long as:

- The overview uses the tab markup from `index.php` (`ul.tab_list.move_offer_total` with `tab_1` / `tab_2`)
- Detail and overview run in the same browser tab, so `sessionStorage` is shared
- The v22 `parks.min.js` is loaded on overview and detail pages

Detail-only hashes such as `#tab_5` (POI → route) are left unchanged.
