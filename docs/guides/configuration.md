[Docs](../index.md) › [Guides](./index.md) › Core configuration

# Core configuration

Full reference for runtime settings in `swiss-parks-sdk/config/config.php`. Keys use snake_case. Keep `config.php` across SDK updates on the park server.

## Required settings

Set these on the server:

- `api_hash`
  - Hash from your export configuration on `angebote.paerke.ch`.
- `park_id`
  - Park ID from the park or export settings. `> 0` scopes one park; `0` or empty is network-wide (all parks).
- `db_path` (default: `writable/data/park-offers.sqlite`)
  - Path to the SQLite database file. Relative paths resolve from `swiss-parks-sdk/`. The file and directory are created automatically; the web server needs write access. Replaces former MySQL credentials.

Optional: create `config.local.php` next to `config.php` to override keys such as `api_hash`, `park_id`, or `base_url` without editing the main config (for example on a staging copy of the site). `autoload.php` loads this file after `config.php` when it exists.

## Network settings

- `base_url` (optional override; runtime default `https://angebote.paerke.ch/`)
  - Base URL of the offer database for XML/JSON exports and related asset links. Not set in shipped `config.php`; `ParksSDK` applies the default. Override only if you were given a different offer-database host (this is not the frontend CDN host for `parks.min.js`).
- `curl_verify_ssl` (default: `true`)
  - Verifies SSL certificates for export requests to `angebote.paerke.ch`. Set to `false` only if import fails with SSL/cURL HTTPS errors on shared hosting.
- `migration_log_url` (default: `https://angebote.paerke.ch/migrate/log_sdk_migration`)
  - Endpoint contacted after a successful `migrate.php` run so the offer platform can record the SDK version in use. Uses `curl_verify_ssl` and your `api_hash`. Leave the default unless you were told otherwise.

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

- `class_view` (default: `ParksView`)
  - View class that renders output (optional subclass, for example `MyView` or `ParksSwissView`).
- `template_folder` (default: `standard`)
  - Template set under `swiss-parks-sdk/templates/`.

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
- `placeholder_image` (default: `https://angebote.paerke.ch/img/placeholder.png`)
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
  - Shows or hides accessibility filter controls. Hidden automatically for `projects_only` exports.
- `hide_accessibility_filter` (per-request filter option, not config)
  - Hides accessibility controls for a single page render when passed in the `$filter` array to `showOffersFilter()`.
- `show_municipality_filter` (default: `true`)
  - Shows or hides the municipality filter.
- `keyword_filter` (default: empty arrays per language)
  - Optional keyword filter labels per language. First entry is the filter title, second is the “show all” label, then keyword values. Example shape: `'de' => ['Kategorien', 'Alle', 'Kinder', …]`.

## Output and CMS embeds

- `return_output` (default: `false`)
  - When `true`, rendering methods return HTML as a string (maps to runtime property `$sdk->returnOutput`). When `false`, methods print directly.
- `soft_errors` (default: `false`)
  - When `true`, missing offer details return an empty string and bootstrap/language failures throw `RuntimeException` instead of `exit()`. Useful for CMS embeds (see [WordPress integration](./wordpress-integration.md)).
- `prevent_css_js_include` (default: `false`)
  - When `false`, map/detail rendering prepends the interactive map library tags from `interactive_map_url` (`style.css`, `swiss-parks-map.umd.js`). When `true`, those tags are omitted and you must load the map library yourself. This does **not** affect `parks.min.css` / `parks.min.js` — those are never auto-included; always load them in your page or CMS enqueue.

## Interactive map

- `interactive_map_url` (default: `https://map.parks.swiss`)
  - Base URL for map assets (`/dist/css/style.css`, `/dist/js/swiss-parks-map.umd.js`). Runtime map behaviour (full height, layers, …) uses `$sdk->mapOptions`, not `config.php`. See [Map options](../sdk-reference/map-options.md).

## Favorites

See [Favorites](./favorites.md).

- `favorites_extension_available` (default: `false`)
  - Enables favorite toggle links and the favorites overview.
- `favorites_script_path` (default: `''`)
  - URL or path to the folder that contains `favorites.php` (no trailing slash), or a CMS endpoint that calls `ParksSDK::toggleFavorite()`.

## API prefixes

- `form_prefix` (default: `''`)
  - Prefix for form field names to avoid clashes with the host CMS.
- `url_param_prefix` (default: `''`)
  - Prefix for query parameters (`offer`, `page`, …) to avoid clashes with the host CMS.

## Languages

- `default_language` (default: `de`)
  - Project convention for the default language; runtime language is still set via `new ParksSDK('<lang>')`.
- `available_languages` (default: `['de', 'fr', 'it', 'en']`)
  - Language files the SDK may load.
- `language_independence` (default: `true`)
  - Controls whether offers without the selected language are still shown via fallback.
- `language_priority` (default: per-language fallback lists)
  - Ordered fallback languages when content is missing in the active language.

## Sessions

- `use_sessions` (default: `true`)
  - Enables SDK session state for filter persistence and favorites (`toggleFavorite` requires this). Autoload starts a PHP session only when enabled and headers are not sent yet.
- `session_name` (default: `parks_api`)
  - Session/cookie namespace prefix (also used for the favorites cookie).

## Logs and date formats

- `log_directory` (default: `writable/log/`)
  - Directory for log files; must be writable by the web server / CLI user.
- `db_date_format` (default: `%d.%m.%Y %H:%i`)
  - Date format used in database-related formatting.
- `php_date_format` (default: `d.m.Y H:i`)
  - Date format used in PHP display helpers.
- `display_weekday_short` (default: `true`)
  - Prefixes offer dates in list and detail output with a translated short weekday (for example `Do, 19.11.2026`). Set to `false` to omit the weekday.

## Related docs

- [Guide index](./index.md)
- [Quick start integration](./quick-start-integration.md)
- [Output mode and multilingual support](./output-and-language.md)
- [SEO URLs](./seo-urls.md)
- [Favorites](./favorites.md)
