# Quick start integration

Minimal page integration:

```php
<?php
$language = 'en';
require_once('swiss-parks-sdk/autoload.php');
$api = new ParksAPI($language);

$categories = [];
$filter = [];

if ($api->is_offer_detail()) {
    $api->show_offer_detail();
} else {
    $api->show_offers_filter($categories, $filter);
    $api->show_offers_list($categories, $filter);
    $api->show_offers_pagination();
}
```

A complete example including tabs and map is available in `index.php`.

## List/map tab and back navigation

`parks.min.js` stores the active overview tab in the URL hash (`#tab_1` list, `#tab_2` map) and mirrors it in `sessionStorage`. When the detail back link returns to the overview without a hash (typical in CMS embeds), the last list/map tab is restored.

This works in plain PHP, WordPress, and other CMS embeds as long as:

- Overview uses the list/map tab markup expected by `parks.min.js` (see `index.php`: `ul.tab_list.move_offer_total` with `tab_1` / `tab_2`)
- Detail and overview run in the same browser tab (so `sessionStorage` is shared)
- A current `parks.min.js` is loaded on both overview and detail pages

The detail back link points to the overview URL. `parks.min.js` appends `#tab_1` or `#tab_2` from `sessionStorage` when present. Detail-only hashes such as `#tab_5` (POI → route) are left unchanged.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
- [ParksAPI methods](../sdk-reference/parks-api.md)
