# Core configuration (`parks_api/config.php`)

This guide documents the most important runtime settings in `parks_api/config.php`.

## Required settings

Set these in `parks_api/config.php` on your server (keep this file across SDK updates):

- `api_hash`
  - Hash from your export configuration on `angebote.paerke.ch`.
- `park_id`
  - Enter the park ID; you can obtain it by contacting the respective park.
- `db_path`
  - Path to the SQLite database file (default: `data/park-offers.sqlite`). Relative paths are resolved relative to the `parks_api/` folder. The file and its directory are created automatically; the web server needs write permissions. Replaces the former MySQL credentials (`db_hostname`, `db_port`, `db_username`, `db_password`, `db_database`).

Optional for SDK development in git: create `config.local.php` next to `config.php` and override keys such as `api_hash` and `park_id` locally without committing secrets. `autoload.php` loads this file after `config.php` when it exists; the file is listed in `.gitignore`.

## Network settings

- `curl_verify_ssl` (default: `true`)
  - Verifies SSL certificates for export requests to `angebote.paerke.ch`.
  - If import/update fails with SSL or cURL HTTPS errors on shared hosting, set to `false` in `config.php` as a fallback.
- `migration_log_url` (default: `https://angebote.paerke.ch/migrate/log_api_migration`)
  - Endpoint for `log_migration()` after a successful `migrate.php` run. Also uses `curl_verify_ssl`.

## Runtime mode: single park vs network-wide

- `park_id`
  - `> 0`: integration for one specific park.
  - `0` or empty: network-wide integration (all available parks).

## Common display/runtime options

- `class_view` (for example `MyView`)
  - Defines which view class renders output.
- `template_folder` (for example `standard`)
  - Selects the template set inside `parks_api/template/`.
- `prevent_css_js_include`
  - Prevents automatic inclusion of SDK CSS/JS assets.
- `return_output`
  - Return generated HTML (`true`) or print directly (`false`).
- `use_sessions`, `session_name`
  - Enables SDK session-based state and sets namespace/cookie prefix.
- `language_independence`, `language_priority`
  - Controls multilingual fallback behavior.

## Practical starter configuration

Frequently tuned options:

- `return_output`
  - Controls whether API methods return HTML (`true`) or print directly (`false`).
- `prevent_css_js_include`
  - Disables automatic SDK CSS/JS includes when your project loads assets manually.
- `use_sessions`
  - Enables SDK session state for filter persistence and favorites behavior.
- `session_name`
  - Defines the session/cookie prefix used by SDK state and favorites.
- `offers_per_page`
  - Sets how many offers are rendered per overview page.
- `show_route_filter`
  - Shows or hides the route filter in the filter UI.
- `show_target_group_filter`
  - Shows or hides the target group filter.
  - Automatically hidden when the export contains **only** projects (`projects_only`); shown alongside the project status filter when mixed offer types exist.
- `show_fields_of_activity_filter` (default: `true`)
  - Shows or hides the fields of activity filter.
  - Automatically hidden when the export contains **only** projects (`projects_only`).
- `show_accessibility_filter`
  - Shows or hides accessibility filter controls.
  - Automatically hidden when the export contains **only** projects (`projects_only`).
- `hide_accessibility_filter` (per-request filter option, not config)
  - Hides accessibility controls for a single page render when passed in the `$filter` array to `show_offers_filter()`.
- `show_municipality_filter`
  - Shows or hides the municipality filter.
- `show_event_location_in_overview`
  - Displays event location in overview cards.
- `show_short_description_in_overview`
  - Displays short descriptions in overview cards.
- `show_keywords_in_overview`
  - Displays offer keywords in overview cards.
- `show_button_in_overview`
  - Displays the detail/action button in overview cards.
- `overview_thumbnail_size`, `detail_thumbnail_size`
  - Sets image sizes for overview and detail templates.
- `heading_offer_title_in_overview`
  - Defines the HTML heading tag used for offer titles in overview templates.
- `image_enlargement`
  - Enables enlarged image links (for lightbox/fancybox-like integrations).

## Related docs

- [Guide index](./index.md)
- [Quick start integration](./quick-start-integration.md)
- [Output mode and multilingual support](./output-and-language.md)
