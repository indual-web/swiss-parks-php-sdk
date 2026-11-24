[Docs](../index.md) › Configuration

# Configuration

Full reference for runtime settings. Keys use snake_case. The defaults live in `swiss-parks-sdk/config/config.php`; set your own values in a separate file (see below).

## Config files and merge order

Every config file returns a PHP array. The SDK merges them in this order, and a later file replaces whole keys of an earlier one (`array_replace`):

1. `swiss-parks-sdk/config/config.php` — SDK defaults with comments. Do not edit it: SDK updates replace it.
2. `swiss-parks-sdk/config/config.local.php` — optional, your values for ZIP installations. Not part of the release, so updates keep it.
3. Project config — optional, a file outside the SDK folder whose absolute path is set in the `SWISS_PARKS_SDK_CONFIG` constant (or environment variable). Intended for Composer installations, where the SDK lives in `vendor/`.

Put only the keys you change into your file:

```php
<?php

return [
	'api_hash' => 'your-export-hash',
	'park_id' => 1,
	'sync_secret' => 'a-long-random-string',
];
```

Array values such as `language_priority` or `keyword_filter` are replaced as a whole, so repeat every entry you want to keep.

Set the project config before creating the SDK. The constant takes precedence over the environment variable:

```php
define('SWISS_PARKS_SDK_CONFIG', __DIR__ . '/config/parks.php');
```

CLI scripts accept `--config=<path>` instead (see [bin scripts](./bin-scripts.md)). If `SWISS_PARKS_SDK_CONFIG` is set but the file is missing or does not return an array, the SDK aborts with an error message (or throws `RuntimeException` when `soft_errors` is already `true` in an earlier file).

## Required settings

Set these in your own config file:

- `api_hash`
  - Hash from your export configuration on `hub.parks.swiss`. While it is empty, the SDK skips the initial import (for example on development machines without a hash); `migrate` then exits with `1`. After a change the next sync runs a full import instead of an incremental one.
- `park_id`
  - Park ID from the park or export settings. `> 0` scopes one park; `0` or empty is network-wide (all parks).

## Database and sync

- `db_path` (default: `writable/data/park-offers.sqlite`)
  - Path to the SQLite database file. Relative paths resolve from `swiss-parks-sdk/`; use an absolute path outside `vendor/` for Composer installations. The file and directory are created automatically; the web server and the cron user need write access.
- `sync_secret` (default: empty)
  - Shared secret for the HTTP sync endpoint `public/sync.php` (URL cron on hosting without SSH). Leave empty to disable HTTP sync. Use a long random value when enabled. See [HTTP sync](./import-update-lifecycle.md#http-sync-shared-hosting).

## Network settings

- `base_url` (optional override; runtime default `https://hub.parks.swiss/`)
  - Base URL of the offer database for XML/JSON exports and related asset links. Not set in the shipped defaults; `SwissParksSdk` applies the default. Override only if you were given a different offer-database host (this is not the frontend CDN host for `parks.min.js`).
- `curl_verify_ssl` (default: `true`)
  - Verifies SSL certificates for export requests to `hub.parks.swiss`. Set to `false` only if import fails with SSL/cURL HTTPS errors on shared hosting.
- `migration_log_url` (default: `https://hub.parks.swiss/migrate/log_sdk_migration`)
  - Endpoint that receives a signed report after a successful migration, so the offer platform knows which SDK version runs where (contents: [`migrate()`](../sdk-reference/swiss-parks-sdk.md#migrate)). Uses `curl_verify_ssl` and your `api_hash`. Leave the default unless you were told otherwise.

## SEO URLs

See [SEO URLs](./seo-urls.md) for patterns and CMS routing.

- `seo_urls` (default: `false`)
  - Enables SEO path routing for detail, pagination, and reset.
- `seo_url_detail_slug` (default: `offer-detail`)
  - Path segment for offer detail URLs.
- `seo_url_poi_slug` (default: `poi`)
  - Path segment for POI detail URLs.
- `seo_url_page_slug` (default: `page`)
  - Path segment for pagination.
- `seo_url_reset_slug` (default: `reset`)
  - Path segment that clears the active filter.

## View and template

- `class_view` (default: `\SwissParks\Sdk\View::class`)
  - Fully qualified name of the view class that renders output. Set it to your own subclass of `SwissParks\Sdk\View`, for example `MyView::class`. See [Templating and custom view](./templating-and-custom-view.md).
- `template_folder` (default: `standard`)
  - Name of a template set under `swiss-parks-sdk/templates/`, or an absolute path to your own template folder. Use an absolute path outside `vendor/` for Composer installations.

## Display and filter UI

- `always_show_filter` (default: `false`)
  - Shows the filter UI on detail pages as well as overviews.
- `show_route_filter` (default: `true`)
  - Shows or hides the route filter.
- `show_target_group_filter` (default: `true`)
  - Shows or hides the target group filter. Hidden automatically when the export contains only projects (`projects_only`); shown with the project status filter when mixed types exist.
- `show_fields_of_activity_filter` (default: `true`)
  - Shows or hides the fields of activity filter. Hidden automatically for `projects_only` exports.
- `show_route_filter_min_count` (default: `5`)
  - Minimum activity count before the route filter is shown.
- `offers_per_page` (default: `10`)
  - Offers rendered per overview page.
- `n_th_point` (default: `2`)
  - Keep every n-th route point on the overview map (speeds up large maps).
- `n_th_point_min_count` (default: `100`)
  - Routes with fewer points than this are not shortened.
- `pagination_max_numbers` (default: `5`)
  - How many page numbers the overview pagination shows.
- `overview_thumbnail_size` (default: `medium`)
  - Image size for overview cards.
- `detail_thumbnail_size` (default: `large`)
  - Image size for detail pages.
- `placeholder_image` (default: `https://hub.parks.swiss/img/placeholder.png`)
  - Fallback image when an offer has no image.
- `image_enlargement` (default: `true`)
  - Enables enlarged image links (lightbox/fancybox-style integrations).
- `detail_limit_dates` (default: `5`)
  - How many dates are shown on the detail page before a “show more” control.
- `filter_keywords_with_and` (default: `false`)
  - When `true`, keyword filters combine with AND; default is OR.
- `show_park_name` (default: `false`)
  - Shows the park name on overview and detail.
- `show_event_location_in_overview` (default: `true`)
  - Shows event location on overview cards.
- `show_short_description_in_overview` (default: `false`)
  - Shows short descriptions on overview cards.
- `show_keywords_in_overview` (default: `false`)
  - Shows offer keywords on overview cards.
- `show_button_in_overview` (default: `false`)
  - Shows the detail/action button on overview cards.
- `poi_listing_link_target` (default: `''`)
  - `target` attribute for POI listing links (for example `_blank`).
- `heading_offer_title_in_overview` (default: `h3`)
  - HTML heading tag for offer titles in overview templates.
- `show_accessibility_filter` (default: `true`)
  - Shows or hides accessibility filter controls. Hidden automatically for `projects_only` exports. To hide them on a single page only, use the filter option `hide_accessibility_filter` ([filter options](../sdk-reference/filter-options.md#ui-toggles)).
- `show_municipality_filter` (default: `true`)
  - Shows or hides the municipality filter.
- `keyword_filter` (default: empty arrays per language)
  - Optional keyword filter labels per language. First entry is the filter title, second is the “show all” label, then keyword values. Example shape: `'de' => ['Kategorien', 'Alle', 'Kinder', …]`.

## Output and CMS embeds

- `return_output` (default: `false`)
  - When `false`, rendering methods print HTML directly. When `true`, they return it as a string, for CMS shortcodes, output buffering or layouts that inject HTML manually. Can also be changed at runtime with `$sdk->returnOutput`.
- `soft_errors` (default: `false`)
  - When `true`, missing offer details return an empty string and bootstrap/language failures throw `RuntimeException` instead of `exit()`. Independent of `return_output`; CMS embeds usually set both to `true` (see [WordPress integration](./wordpress-integration.md)).
- `prevent_css_js_include` (default: `false`)
  - When `false`, map/detail rendering prepends the interactive map library tags from `interactive_map_url` (`style.css`, `swiss-parks-map.umd.js`). When `true`, those tags are omitted and you must load the map library yourself. This does **not** affect `parks.min.css` / `parks.min.js` — those are never auto-included; always load them in your page or CMS enqueue.

## Interactive map

- `interactive_map_url` (default: `https://map.parks.swiss`)
  - Base URL for map assets (`/dist/css/style.css`, `/dist/js/swiss-parks-map.umd.js`). Runtime map behaviour (full height, layers, …) uses `$sdk->mapOptions`, not the config. See [Map options](../sdk-reference/map-options.md).

## Favorites

See [Favorites](./favorites.md).

- `favorites_extension_available` (default: `false`)
  - Enables favorite toggle links and the favorites overview.
- `favorites_script_path` (default: `''`)
  - URL or path to the folder that contains `favorites.php` (no trailing slash), or a CMS endpoint that calls `SwissParksSdk::toggleFavorite()`.

## API prefixes

- `form_prefix` (default: `''`)
  - Prefix for form field names to avoid clashes with the host CMS.
- `url_param_prefix` (default: `''`)
  - Prefix for query parameters (`offer`, `page`, …) to avoid clashes with the host CMS.

## Languages

The active language is passed to the constructor: `new SwissParksSdk('fr')`. Missing or invalid values fall back to `de`. UI labels come from `swiss-parks-sdk/lang/<language>.php`.

- `default_language` (default: `de`)
  - Project convention for the default language; it does not change the language passed to the constructor.
- `available_languages` (default: `['de', 'fr', 'it', 'en']`)
  - Language files the SDK may load.
- `language_independence` (default: `true`)
  - Controls whether offers without the selected language are still shown via fallback.
- `language_priority` (default: per-language fallback lists)
  - Ordered fallback languages when content is missing in the active language.

## Sessions

- `use_sessions` (default: `true`)
  - Enables SDK session state for filter persistence and favorites (`toggleFavorite` requires this). The `SwissParksSdk` constructor starts a PHP session only when enabled, page rendering is bootstrapped, no session is active and headers are not sent yet.
- `session_name` (default: `parks_api`)
  - Session/cookie namespace prefix (also used for the favorites cookie).

## Logs and date formats

- `log_directory` (default: `writable/log/`)
  - Directory for log files, with trailing slash. Relative paths resolve from `swiss-parks-sdk/`; use an absolute path outside `vendor/` for Composer installations. Created if missing; must be writable by the web server / CLI user.
- `db_date_format` (default: `%d.%m.%Y %H:%i`)
  - Date format used in database-related formatting.
- `php_date_format` (default: `d.m.Y H:i`)
  - Date format used in PHP display helpers.
- `display_weekday_short` (default: `true`)
  - Prefixes offer dates in list and detail output with a translated short weekday (for example `Do, 19.11.2026`). Set to `false` to omit the weekday.

## Related docs

- [New installation](./new-installation.md)
- [Quick start integration](./quick-start-integration.md)
