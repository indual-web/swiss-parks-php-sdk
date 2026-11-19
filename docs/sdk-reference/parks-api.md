[Docs](../index.md) › [SDK reference](./index.md) › ParksAPI methods

# ParksAPI methods

Integrator-facing methods from `ParksAPI`, grouped by purpose. Parameter names match the PHP signatures.

## Constructor

### `__construct($language = '', $hash = '', $page_url = '', $bootstrap_rendering = true, $bootstrap_setup = true)`

Creates the API instance, loads config, and optionally bootstraps sessions/filters and view rendering.

Parameters:

- `$language` (`string`): active UI/content language (`de`, `fr`, `it`, `en`). Invalid values fall back to `de`.
- `$hash` (`string`): optional API hash override; empty uses `config['api_hash']`.
- `$page_url` (`string`): stable page URL for session scoping in CMS embeds (for example the offers permalink). Empty uses the current request URL.
- `$bootstrap_rendering` (`bool`): when `true`, loads templates and prepares the view for HTML output.
- `$bootstrap_setup` (`bool`): when `true`, runs page setup (sessions, filter state, request parsing).

CMS tip: pass `$page_url` for session scope and call `setScriptUrl()` with the offers path so forms and links stay on the correct page. See [WordPress integration](../guides/wordpress-integration.md).

Static factories without page rendering:

- `ParksAPI::forScript()` — CLI sync (`bin/sync.php`)
- `ParksAPI::forMigration()` — CLI migrate (`bin/migrate.php`)

## Rendering methods

### `showOffersFilter($categories = [], $filter = [], $parkId = null)`

- Renders the filter UI for an overview page.
- Applies `system_filter` hard constraints and selected UI toggles from `$filter`.
- Returns HTML output from `ParksView::filter()`.

Parameters:

- `$categories` (`array`): category IDs to scope filter/list/map context.
- `$filter` (`array`): filter values and UI flags (see [filter options](./filter-options.md)).
- `$parkId` (`int|null`): explicit park override; falls back to configured/default park.

### `showOffersList($categories = [], $filter = [], $parkId = null)`

- Renders the offer overview list.
- Reads pagination from request (`?page=` or SEO page slug mode).
- Updates internal pagination state used by `showOffersPagination()`.

Parameters:

- `$categories` (`array`)
- `$filter` (`array`)
- `$parkId` (`int|null`)

### `showOffersMap($categories = [], $filter = [], $parkId = null)`

- Renders the overview map for the current result set.
- Uses `$api->mapOptions` for map runtime configuration.

Parameters:

- `$categories` (`array`)
- `$filter` (`array`)
- `$parkId` (`int|null`)

### `showOffersPagination()`

- Renders pagination for the most recent list query context.
- Returns an empty string on detail pages.

### `showOfferDetail($single_offer_id = null)`

- Renders one offer detail page.
- Supports routed detail mode and explicit single-offer mode (`$single_offer_id`).
- If a requested offer does not exist: sends a 404 response and exits by default; with `soft_errors = true` returns an empty string instead.
- The back link returns to the overview URL; the last list/map tab (`#tab_1` / `#tab_2`) is restored by `parks.min.js` via hash + `sessionStorage` (see [Quick start integration](../guides/quick-start-integration.md#listmap-tab-and-back-navigation)).

Parameters:

- `$single_offer_id` (`int|null`): optional forced detail ID.

### `showFavorites()`

- Renders favorites overview (or detail view if current request is detail).
- Uses visitor favorites from the favorites cookie (JSON offer IDs) / session context.
- If no favorites exist, renders a localized empty message.

### `showTotal()`

- Returns/prints the total **offer count** for the current list context, with a localized `offer` / `offers` label (for example `12 Offers`).
- Useful after list rendering when `$api->total` is set. Returns `null` when the total is `0`.

### `showOfferPoiList($poi = null)`

- Utility method to resolve POI offer IDs into offer objects.
- Mainly used by map/detail rendering internals and custom extensions.

Parameters:

- `$poi` (`array|null`): array of offer IDs used as POIs.

## Template helpers

Relevant for custom views; see [Templating and custom view](../guides/templating-and-custom-view.md).

### `loadTemplate()`

- Loads `detail.tpl` and `filter.tpl` from the configured `template_folder` into `$api->template`.

### `compileTemplate($section, $template_data = [], $conditions = [])`

- Compiles a loaded template section (`detail`, `filter`, …) with placeholders and condition blocks.
- Returns the rendered HTML string.

## Data access methods

### `getOffersList($parkId = null, $categories = [], $page = null, $limit = null, $filter = [], $ignore_filter = false, $return_minimal = false, $map_mode = false)`

- Returns offers as `array` with keys `data` and `total` (empty when no matches).
- Wraps the internal query pipeline used by overview rendering.

### `getOffers($parkId = null, $categories = [])`

- Thin alias of `getOffersList()` with only park and category scope (default paging/filter behaviour).

### `countOffersByCategory($parkId = null, $categories = [], $filter = [])`

- Returns category counts as `stdClass|false` (`event_count`, `booking_count`, `activity_count`, `product_count`, `project_count`).
- Used internally to decide which filter UI elements to show.

### `getFilterCategories($parkId = null, $categories = [], $filter = [])`

- Returns `ParksSQLiteResult|false` with `category_id` rows for categories that have matching offers.

Common parameters:

- `$parkId` (`int|null`): park scope.
- `$categories` (`array`): category scope.
- `$page`, `$limit` (`int|null`): paging controls.
- `$filter` (`array`): same filter structure as rendering methods.
- `$ignore_filter` (`bool`): bypass active visitor filter state when needed.
- `$return_minimal` (`bool`): lighter offer payload when full detail fields are not needed.
- `$map_mode` (`bool`): optimize payload for map scenarios.

### `getOfferDetail($offer_id)`

- Returns one normalized offer object by ID.

Parameters:

- `$offer_id` (`int`)

### `getCategoriesList()`

- Returns all categories.

### `getCategoriesListForSelect()`

- Returns category tree data formatted for select/dropdown use cases.

### `loadExternalXml($url)`

- Downloads an XML URL via cURL into a temp file and returns `SimpleXMLElement` or `false`.
- Used by import; available for custom tooling. CLI raises the memory limit for large exports.

## Runtime and state methods

### `isOfferDetail()`

- Returns `true` if current request resolves to a detail page.
- Supports both parameter-based and SEO URL detail detection.

### `isFilterActivated()`

- Returns whether a filter is currently active in runtime state.

### `getFilterData()`

- Returns normalized active filter values from runtime state.

### `toggleFavorite($offer_id)`

- Adds/removes one offer ID from favorites.
- Requires `use_sessions = true`; otherwise returns `false` without changing state.
- Persists state in the favorites cookie as JSON (`SameSite=Lax`, `Secure` on HTTPS).
- Returns `true` when added, `false` when removed or not possible.

Parameters:

- `$offer_id` (`int`)

### `cleanFavorites()`

- Clears all favorites for the current visitor (cookie reset).

### `setScriptUrl($url)`

- Sets the view `script_url` / `script_url_with_params` used for forms and links.
- Does not change session scoping; pass `$page_url` to the `ParksAPI` constructor for that.
- Useful in CMS embeds where `REQUEST_URI` is not the offers page permalink.

Parameters:

- `$url` (`string`): path or path with query string.

### `abort($message)`

- Prints `$message` and exits by default.
- With `soft_errors = true`, throws `RuntimeException` instead (CMS-safe bootstrap/language failures).

Parameters:

- `$message` (`string`)

## Import methods

### `update($force = false)`

- Runs synchronization from XML export endpoints.
- Returns `true` on successful offer import, `false` on failure.
- Outputs JSON status (`status`, `message`) to stdout. The legacy key `messsage` is still included for backward compatibility.
- Sets `Content-Type: application/json` only for non-CLI requests (browser cron).

Parameters:

- `$force` (`bool`): force full update path in import process.

### `forScript()` / `forMigration()`

Static factories for CLI scripts (no page rendering bootstrap):

- `ParksAPI::forScript()` — used by `sync.php` (`--force` for full import)
- `ParksAPI::forMigration()` — used by `migrate.php` (also skips page setup)

### `migrate()`

- Rebuilds the SQLite database: deletes the database file, recreates it with the current schema, and runs a full import.
- On success, reports the migration via `logMigration()` (failure to log does not fail the migration).
- Returns `true` when the SDK is initialized after import, `false` otherwise.

### `logMigration(int $version_to = 0)`

- Called automatically after a successful `migrate()`.
- Reports the upgrade to `migration_log_url` using your `api_hash` (skipped when `api_hash` is empty).
- Returns `true` on HTTP 2xx from the log endpoint; otherwise writes a local log entry and returns `false`.
- `$version_to`: target SDK version; defaults to the shipped SDK version when `0`.

## Output behavior note

Rendering methods honor `config['return_output']` (runtime `$api->returnOutput`):

- `false` (default): HTML is printed directly.
- `true`: HTML is returned as string (useful for CMS composition/output buffering).

`soft_errors` is independent of `return_output`. See [WordPress integration](../guides/wordpress-integration.md).

## Minimal integration example

```php
$api = new ParksAPI('en');
$categories = [];
$filter = [];

if ($api->isOfferDetail()) {
    echo $api->showOfferDetail();
} else {
    echo $api->showOffersFilter($categories, $filter);
    echo $api->showOffersList($categories, $filter);
    echo $api->showOffersPagination();
}
```

## Related docs

- [SDK reference index](./index.md)
- [Filter options](./filter-options.md)
- [Map options](./map-options.md)
