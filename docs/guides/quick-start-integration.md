# Quick start integration

Minimal page integration:

```php
<?php
$language = 'en';
require_once('parks_api/autoload.php');
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

A complete example including tabs and map is available in `example.php`.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
- [ParksAPI methods](../api-reference/parks-api.md)
