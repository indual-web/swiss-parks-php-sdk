[Docs](../index.md) › SwissParksSdk methods

# SwissParksSdk methods

Integrator-facing classes and methods, grouped by purpose. Parameter names match the PHP signatures. Page code example: [quick start integration](../guides/quick-start-integration.md).

## Classes

All classes live in the `SwissParks\Sdk` namespace; import them with `use`, for example `use SwissParks\Sdk\SwissParksSdk;`.

| Class | Role |
| --- | --- |
| `SwissParksSdk` | Entry point: bootstrap, render filter/list/map/detail, sync and migration |
| `View` | HTML rendering; extend it for a [custom view](../guides/templating-and-custom-view.md) |
| `Language` | UI labels and active language |
| `Category` | Constants for root and well-known category IDs |
| `OfferStatus` | Constants for offer status values |
| `Date`, `Format`, `Map` | Static helpers for custom views |

## Constants

- `SwissParksSdk::VERSION` (`string`, for example `'22.0'`): SDK release version. It matches the git tag without `v` (`v22.0`) and is stored in the database `meta` table. A higher value than the stored one means a migration is due.
- `SwissParksSdk::GINTO_INFOS_AVAILABLE` (`int`): value of `offer.ginto_infos_available` when GINTO accessibility infos exist.
- `Category::*` (`SwissParks\Sdk\Category`): root and well-known category IDs, for example `Category::EVENT`, `Category::PRODUCT`, `Category::BOOKING`, `Category::ACTIVITY`, `Category::PROJECT`, `Category::RESEARCH`.
- `OfferStatus::STUB`, `OfferStatus::INACTIVE`, `OfferStatus::ACTIVE` (`SwissParks\Sdk\OfferStatus`): offer status values used by the import.

## Constructor

### `__construct($language = '', $hash = '', $pageUrl = '', $bootstrapRendering = true, $bootstrapSetup = true)`

Creates the SDK instance, loads config (see [configuration](../guides/configuration.md#config-files-and-merge-order)), and optionally bootstraps sessions/filters and view rendering. With `use_sessions = true` and rendering enabled, the constructor starts the PHP session if none is active and headers are not sent yet.

Parameters:

- `$language` (`string`): active UI/content language (`de`, `fr`, `it`, `en`). Invalid values fall back to `de`.
- `$hash` (`string`): optional API hash override; empty uses `config['api_hash']`.
- `$pageUrl` (`string`): stable page URL for session scoping in CMS embeds (for example the offers permalink). Empty uses the current request URL.
- `$bootstrapRendering` (`bool`): when `true`, starts the session, loads templates and prepares the view for HTML output.
- `$bootstrapSetup` (`bool`): when `true`, runs page setup (sessions, filter state, request parsing).

CMS tip: pass `$pageUrl` for session scope and call `setScriptUrl()` with the offers path so forms and links stay on the correct page. See [WordPress integration](../guides/wordpress-integration.md).

For scripts without page rendering, use the static factories [`forScript()` / `forMigration()`](#forscript--formigration).

## Rendering methods

### `showOffersFilter($categories = [], $filter = [], $parkId = null)`

- Renders the filter UI for an overview page.
- Applies `system_filter` hard constraints and selected UI toggles from `$filter`.
- Returns HTML output from `View::filter()`.

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
- Uses `$sdk->mapOptions` for map runtime configuration.

Parameters:

- `$categories` (`array`)
- `$filter` (`array`)
- `$parkId` (`int|null`)

### `showOffersPagination()`

- Renders pagination for the most recent list query context.
- Returns an empty string on detail pages.

### `showOfferDetail($singleOfferId = null)`

- Renders one offer detail page.
- Supports routed detail mode and explicit single-offer mode (`$singleOfferId`).
- If a requested offer does not exist: sends a 404 response and exits by default; with `soft_errors = true` returns an empty string instead.
- The back link returns to the overview URL; the last list/map tab (`#tab_1` / `#tab_2`) is restored by `parks.min.js` via hash + `sessionStorage` (see [Quick start integration](../guides/quick-start-integration.md#listmap-tabs-and-back-navigation)).

Parameters:

- `$singleOfferId` (`int|null`): optional forced detail ID.

### `showFavorites()`

- Renders favorites overview (or detail view if current request is detail).
- Uses visitor favorites from the favorites cookie (JSON offer IDs) / session context.
- If no favorites exist, renders a localized empty message.

### `showTotal()`

- Returns/prints the total **offer count** for the current list context, with a localized `offer` / `offers` label (for example `12 Offers`).
- Useful after list rendering when `$sdk->total` is set. Returns `null` when the total is `0`.

### `showOfferPoiList($poi = null)`

- Resolves POI offer IDs into offer objects (for custom map/detail extensions).

Parameters:

- `$poi` (`array|null`): array of offer IDs used as POIs.

## Template helpers

Relevant for custom views; see [Templating and custom view](../guides/templating-and-custom-view.md).

### `loadTemplate()`

- Loads `detail.tpl` and `filter.tpl` from the configured `template_folder` into `$sdk->template`.

### `compileTemplate($section, $templateData = [], $conditions = [])`

- Compiles a loaded template section (`detail`, `filter`, …) with placeholders and condition blocks.
- Returns the rendered HTML string.

## Data access methods

### `getOffersList($parkId = null, $categories = [], $page = null, $limit = null, $filter = [], $ignoreFilter = false, $returnMinimal = false, $mapMode = false)`

- Returns offers as `array` with keys `data` and `total` (empty when no matches).
- Wraps the internal query pipeline used by overview rendering.

### `getOffers($parkId = null, $categories = [])`

- Thin alias of `getOffersList()` with only park and category scope (default paging/filter behaviour).

### `countOffersByCategory($parkId = null, $categories = [], $filter = [])`

- Returns category counts as `stdClass|false` (`event_count`, `booking_count`, `activity_count`, `product_count`, `project_count`).

### `getFilterCategories($parkId = null, $categories = [], $filter = [])`

- Returns `SQLiteResult|false` with `category_id` rows for categories that have matching offers.

Common parameters:

- `$parkId` (`int|null`): park scope.
- `$categories` (`array`): category scope.
- `$page`, `$limit` (`int|null`): paging controls.
- `$filter` (`array`): same filter structure as rendering methods.
- `$ignoreFilter` (`bool`): bypass active visitor filter state when needed.
- `$returnMinimal` (`bool`): lighter offer payload when full detail fields are not needed.
- `$mapMode` (`bool`): optimize payload for map scenarios.

### `getOfferDetail($offerId)`

- Returns one normalized offer object by ID.

Parameters:

- `$offerId` (`int`)

### `getCategoriesList()`

- Returns all categories.

### `getCategoriesListForSelect()`

- Returns category tree data formatted for select/dropdown use cases.

### `loadExternalXml($url)`

- Downloads an XML URL via cURL and returns `SimpleXMLElement` or `false`. Normally used by the import pipeline, not by page templates.
- Gives up after 10 seconds without a connection, or when the transfer stalls for 60 seconds (for example after a network drop). There is no total time limit, so large exports can still finish.

## Runtime and state methods

### `isOfferDetail()`

- Returns `true` if current request resolves to a detail page.
- Supports both parameter-based and SEO URL detail detection.

### `enforceOfferDetailCanonicalUrl($pageBaseUrl = '')`

- Only acts when `seo_urls = true` and the current URL contains `/{seo_url_detail_slug}/`; otherwise returns `true` and does nothing.
- Sends `301` to the canonical detail path when the request uses an outdated title slug, a pagination segment or another prefix. Query strings are ignored. The `Location` is always a relative path.
- Unknown offer IDs get a `404`: by default a plain HTML page and `exit`; with `soft_errors = true` only the status code is set and the method returns `false`, so the CMS can render its own 404 page.
- Returns `true` when the URL is canonical, or when headers were already sent (then no redirect is attempted).
- Call it before any output; no `isOfferDetail()` check is needed. See [SEO URLs](../guides/seo-urls.md#canonical-detail-urls).

Parameters:

- `$pageBaseUrl` (`string`): trusted URL or path of the offers page (for example the CMS permalink). Its path becomes the canonical base. Empty uses the current path without the pagination and detail segments.

### `isFilterActivated()`

- Returns whether a filter is currently active in runtime state.

### `getFilterData()`

- Returns normalized active filter values from runtime state.

### `toggleFavorite($offerId)`

- Adds/removes one offer ID from favorites.
- Requires `use_sessions = true`; otherwise returns `false` without changing state.
- Persists state in the favorites cookie as JSON (`SameSite=Lax`, `Secure` on HTTPS).
- Returns `true` when added, `false` when removed or not possible.

Parameters:

- `$offerId` (`int`)

### `cleanFavorites()`

- Clears all favorites for the current visitor (cookie reset).

### `setScriptUrl($url)`

- Sets the view `script_url` / `script_url_with_params` used for forms and links.
- Does not change session scoping; pass `$pageUrl` to the `SwissParksSdk` constructor for that.
- Useful in CMS embeds where `REQUEST_URI` is not the offers page permalink.
- Percent-encodes `"`, `'`, `<`, `>`, `` ` `` and spaces, because the URL is printed into HTML attributes. The same applies to the `REQUEST_URI` read by the view.

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
- Writes a plain-text status line to stdout, for example:
  - `sync: ok (mode=incremental, imported=5, deleted=1, offers=120, map_layers=3, cleanup=0)`
  - `sync: fail (Offer import failed. Check logs and api_hash configuration.)`
- On success, `map_layers` / `cleanup` are counts; on step failure they are `fail` (offer import may still succeed). Cleanup only runs after a successful offer import, so a failed import deletes no offers.

Parameters:

- `$force` (`bool`): force full update path in import process.

### `forScript()` / `forMigration()`

Static factories for CLI scripts (no page rendering bootstrap):

- `SwissParksSdk::forScript()` — used by `bin/sync.php` and `public/sync.php` (`--force` / `force=1` for full import)
- `SwissParksSdk::forMigration()` — used by `bin/migrate.php` (also skips page setup; CLI only)

### `useConfigFromArgv($argv)`

- Static helper for CLI scripts. Reads `--config=<path>` from `$argv` and defines `SWISS_PARKS_SDK_CONFIG` with the resolved path, unless the constant already exists.
- Call it before `forScript()` / `forMigration()`.

Parameters:

- `$argv` (`array`): CLI arguments.

### `isMigrationNeeded()`

- Returns `true` when the database has no imported data yet or its stored version is lower than `SwissParksSdk::VERSION`.
- Used by `migrate --if-outdated` (Composer install/update hooks).

### `migrate()`

- Rebuilds the SQLite database: deletes the database file, recreates it with the current schema, and runs a full import.
- On success, may notify `migration_log_url` (see [configuration](../guides/configuration.md)); a failed notify does not fail the migration. The signed report contains the SDK version and the previous version, PHP version and SAPI, operating system, install type (`zip` or `composer`), URL or host name, script path, `park_id`, `api_hash` and the offer counts.
- Returns `true` when the SDK is initialized after import, `false` otherwise.
- Prefer `bin/migrate.sh` / `bin/upgrade-sdk.sh` (ZIP) or `vendor/bin/swiss-parks-sdk migrate` (Composer) over calling this from your page code.

## Related docs

- [Configuration](../guides/configuration.md): `return_output`, `soft_errors` and all other settings
- [Filter options](./filter-options.md)
- [Map options](./map-options.md)
