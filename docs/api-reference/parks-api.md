# ParksAPI methods

Integrator-facing methods from `ParksAPI`, grouped by purpose.

## Rendering methods

### `show_offers_filter($categories = [], $filter = [], $park_id = NULL)`

- Renders the filter UI for an overview page.
- Applies `system_filter` hard constraints and selected UI toggles from `$filter`.
- Returns HTML output from `ParksView::filter()`.

Parameters:

- `$categories` (`array`): category IDs to scope filter/list/map context.
- `$filter` (`array`): filter values and UI flags (see `filter-options.md`).
- `$park_id` (`int|null`): explicit park override; falls back to configured/default park.

### `show_offers_list($categories = [], $filter = [], $park_id = NULL)`

- Renders the offer overview list.
- Reads pagination from request (`?page=` or SEO page slug mode).
- Updates internal pagination state used by `show_offers_pagination()`.

Parameters:

- `$categories` (`array`)
- `$filter` (`array`)
- `$park_id` (`int|null`)

### `show_offers_map($categories = [], $filter = [], $park_id = NULL)`

- Renders the overview map for the current result set.
- Uses `$api->map_options` for map runtime configuration.

Parameters:

- `$categories` (`array`)
- `$filter` (`array`)
- `$park_id` (`int|null`)

### `show_offers_pagination()`

- Renders pagination for the most recent list query context.
- Returns an empty string on detail pages.

### `show_offer_detail($single_offer_id = NULL)`

- Renders one offer detail page.
- Supports routed detail mode and explicit single-offer mode (`$single_offer_id`).
- Sends a 404 response and exits if a requested offer does not exist.

Parameters:

- `$single_offer_id` (`int|null`): optional forced detail ID.

### `show_favorites()`

- Renders favorites overview (or detail view if current request is detail).
- Uses visitor favorites from the favorites cookie/session context.
- If no favorites exist, renders a localized empty message.

### `show_total()`

- Returns/prints total pages label text (localized `offer/offers` label).
- Useful after list rendering where total pages are known.

### `show_offer_poi_list($poi = NULL)`

- Utility method to resolve POI offer IDs into offer objects.
- Mainly used by map/detail rendering internals and custom extensions.

Parameters:

- `$poi` (`array|null`): array of offer IDs used as POIs.

## Data access methods

### `get_offers_list($park_id = NULL, $categories = [], $page = NULL, $limit = NULL, $filter = [], $ignore_filter = false, $return_minimal = false, $only_count_categories = false, $map_mode = false)`

- Returns offers as data instead of rendering HTML.
- Wraps the internal query pipeline used by overview rendering.

Common parameters:

- `$park_id` (`int|null`): park scope.
- `$categories` (`array`): category scope.
- `$page`, `$limit` (`int|null`): paging controls.
- `$filter` (`array`): same filter structure as rendering methods.
- `$ignore_filter` (`bool`): bypass active visitor filter state when needed.
- `$map_mode` (`bool`): optimize payload for map scenarios.

### `get_offer_detail($offer_id)`

- Returns one normalized offer object by ID.

Parameters:

- `$offer_id` (`int`)

### `get_categories_list()`

- Returns all categories.

### `get_categories_list_for_select()`

- Returns category tree data formatted for select/dropdown use cases.

## Runtime and state methods

### `is_offer_detail()`

- Returns `true` if current request resolves to a detail page.
- Supports both parameter-based and SEO URL detail detection.

### `is_filter_activated()`

- Returns whether a filter is currently active in runtime state.

### `get_filter_data()`

- Returns normalized active filter values from runtime state.

### `toggle_favorite($offer_id)`

- Adds/removes one offer ID from favorites.
- Persists state in the favorites cookie.
- Returns `true` when added, `false` when removed or not possible.

Parameters:

- `$offer_id` (`int`)

### `clean_favorites()`

- Clears all favorites for the current visitor (cookie reset).

## Import and maintenance methods

### `update($force = false)`

- Runs synchronization from XML export endpoints.
- Outputs JSON status to the response (`status`, `messsage`).

Parameters:

- `$force` (`bool`): force full update path in import process.

### `migrate()`

- Runs database migration steps for the current SDK version.

## Output behavior note

Rendering methods call view methods that honor `config['return_output']`:

- `false` (default): HTML is printed directly.
- `true`: HTML is returned as string (useful for CMS composition/output buffering).

## Minimal integration example

```php
$api = new ParksAPI('en');
$categories = [];
$filter = [];

if ($api->is_offer_detail()) {
    echo $api->show_offer_detail();
} else {
    echo $api->show_offers_filter($categories, $filter);
    echo $api->show_offers_list($categories, $filter);
    echo $api->show_offers_pagination();
}
```

## Related docs

- [API reference index](./index.md)
- [Filter options](./filter-options.md)
- [Map options](./map-options.md)

