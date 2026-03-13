# PHP SDK Documentation - Swiss Parks Network

This SDK imports offer data from an XML export into a local MySQL/MariaDB database and renders it as filter, list, map, and detail views. It is designed for server-side PHP integration and can be extended via templates and custom view classes.

---

## 1) Requirements

- PHP `>= 8.2` and `<= 8.4`
- MySQL or MariaDB
- Write permissions for the log directory (`parks_api/log/` or your configured path)
- PHP cURL extension enabled (required for external data fetches)
- Outbound HTTPS access to `angebote.paerke.ch` and related API endpoints
- URL file access via PHP stream wrappers (for `file_get_contents()` based sync calls)

---

## 2) Architecture Overview

- **Orchestration**
  - `ParksAPI` is the main integration entry point.
- **Import layer**
  - `ParksImport` imports offers and synchronizes additional API data sources into the local database.
- **Data/filter layer**
  - `ParksModel` handles SQL filtering, aggregation, and query logic.
- **Rendering layer**
  - `ParksView` renders filter, list, map, pagination, and detail output.
  - `custom/MyView.php` can override methods from `ParksView`.
- **Language layer**
  - `ParksLanguage` loads translation labels and runtime language context.

---

## 3) Version Workflow

1. **Install**
   - Place the required API release ZIP in `downloads/` (local workspace).
   - Extract the release ZIP and import the base schema from `database/database.sql` (inside the package).
2. **Upgrade**
   - Update SDK/API files to the target release.
3. **Migrate**
   - Run `php parks_api/scripts/migrate.php` to apply schema/data migrations defined in `parks_api/classes/ParksMigration.php`.

Note:
- The base schema `database/database.sql` is part of each downloaded release package.

---

## 4) New Installation

1. Download the latest API version ZIP from `downloads/releases`.
2. Upload/copy the `parks_api` directory into your project or content management system.
3. Create a database.
4. Extract the release package and import `database/database.sql` from that package.
5. Configure `parks_api/config.php`.
   - Obtain a valid hash value from `https://angebote.paerke.ch/en/settings`. This hash is used to generate the XML export file that supplies your data.
6. Run the first import:
   - CLI: `php parks_api/scripts/cron.php`
   - Web: `[Your project path]/parks_api/scripts/cron.php`
7. Verify that the database has been created and that the `offer` table contains data after import.
8. Configure a regular cronjob (for example every 15 minutes).

---

## 5) Upgrade from an older version (example: v21 to v22)

### Step-by-step upgrade

1. Download API version `22`.
2. Create a full backup of your current API files.
3. Create a full backup of your MySQL database.
4. In `/{PATH-TO-YOUR-API-FOLDER}/`, replace:
   - `autoload.php`
   - `classes/`
5. Execute the migration script:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`
6. Execute the cron script and force a full import of all offers:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/force_update.php`
7. Validate functionality:
   - Check filter/list/map/detail pages.
   - Check browser console for JavaScript errors.
   - Check API logs and test the complete website.

### Notes

- Keep your custom files (`custom/`, custom templates, `config.php`) outside replaced core paths.
- Always run migration before the forced import after a version upgrade.

---

## 6) Core Configuration (`parks_api/config.php`)

### Required settings

- `api_hash`
  - Hash from your export configuration on `angebote.paerke.ch`.
- `park_id`
  - Enter the park ID; you can obtain it by contacting the respective park.
- `db_hostname`, `db_username`, `db_password`, `db_database`
  - Database connection credentials.

### Runtime mode: single park vs network-wide

- `park_id`
  - `> 0`: integration for one specific park.
  - `0` or empty: network-wide integration (all available parks).

### Common display/runtime options

- `class_view` (for example `MyView`)
  - Defines which view class renders output. Use a custom class to override default rendering methods.
- `template_folder` (for example `standard`)
  - Selects the template set inside `parks_api/template/`.
- `prevent_css_js_include`
  - Prevents automatic inclusion of SDK CSS/JS assets. Enable only if your project includes required assets manually.
- `return_output`
  - Controls output mode: return generated HTML as string (`true`) or print directly (`false`).
- `use_sessions`, `session_name`
  - Enables SDK session-based state and defines the namespace/cookie prefix used for filter and favorites state.
- `language_independence`, `language_priority`
  - Controls multilingual fallback behavior, including whether related-language content can be shown and which language is preferred first.

### Practical starter configuration

Frequently tuned options in `parks_api/config.php`:

- `return_output` - Important for CMS integrations: set explicitly whether API methods should return HTML strings (`true`) or print directly (`false`).
- `prevent_css_js_include` - Use this only if you include the map scripts manually in your project; in standard integrations this should usually stay unchanged.
- `use_sessions` - Controls SDK session-based state (filters, favorites behavior), not global PHP session bootstrap.
- `session_name` - Prefix for SDK session namespace and favorites cookie naming.
- `offers_per_page` - Number of offers displayed per overview page.
- `show_route_filter` - Enables the route filter in the filter UI.
- `show_target_group_filter` - Enables the target group filter.
- `show_accessibility_filter` - Enables the accessibility filter.
- `show_municipality_filter` - Enables the municipality filter.
- `show_event_location_in_overview` - Shows event location in overview cards.
- `show_short_description_in_overview` - Shows short offer descriptions in overview cards.
- `show_keywords_in_overview` - Shows offer keywords in overview cards.
- `show_button_in_overview` - Shows the detail/action button in overview cards.
- `overview_thumbnail_size`, `detail_thumbnail_size` - Controls image size in overview/detail templates.
- `heading_offer_title_in_overview` - Defines the HTML heading tag for offer titles in overviews.
- `image_enlargement` - Enables enlarged image links (for lightbox/fancybox integrations).

---

## 7) Quick Start Integration

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

---

## 8) Import and Update Lifecycle

### Standard update

- File: `parks_api/scripts/cron.php`
- Command:
  - `php parks_api/scripts/cron.php`

### Forced update

- File: `parks_api/scripts/force_update.php`
- Command:
  - `php parks_api/scripts/force_update.php`

### Migration

- File: `parks_api/scripts/migrate.php`
- Command:
  - `php parks_api/scripts/migrate.php`

Recommended:
- Use `cron.php` for regular updates.
- Use `force_update.php` only when needed.

---

## 9) Public API methods for integrators

Most relevant methods from `ParksAPI`:

- `show_offers_filter($categories = [], $filter = [], $park_id = null)`
  - Renders the interactive filter UI for the current context.
- `show_offers_list($categories = [], $filter = [], $park_id = null)`
  - Renders the offer overview list based on active category/filter constraints.
- `show_offers_map($categories = [], $filter = [], $park_id = null)`
  - Renders the map including matching offer markers and map controls.
- `show_offers_pagination()`
  - Renders pagination for the current list result.
- `show_offer_detail($single_offer_id = null)`
  - Renders the detail page for one offer (by routed or explicit offer ID).
- `show_favorites()`
  - Renders the favorites list for the current visitor.
- `toggle_favorite($offer_id)`
  - Adds/removes one offer from the visitor favorites store.
- `clean_favorites()`
  - Clears all stored favorites for the current visitor.
- `is_offer_detail()`
  - Returns whether the current request targets a detail view.
- `is_filter_activated()`
  - Returns whether any relevant user/system filter is currently active.
- `get_filter_data()`
  - Returns normalized filter state currently used by the SDK.

Data access (instead of direct rendering):

- `get_offers_list(...)`
  - Returns offers as data array instead of rendering HTML.
- `get_offer_detail($offer_id)`
  - Returns the normalized detail data for one offer ID.
- `get_categories_list()`
  - Returns categories as list data for custom integrations.
- `get_categories_list_for_select()`
  - Returns categories preformatted for select/dropdown controls.

Import and maintenance:

- `update($force = false)`
  - Runs the import/update process (`$force = true` triggers a full refresh path).
- `migrate()`
  - Executes schema/data migrations for the current SDK version.

---

## 10) Filter options and capabilities

You can pass these keys in `$filter` (among others):

- `keywords`
  - Filters by keyword IDs/values depending on integration setup.
- `search`
  - Full-text search term for offer title/description fields.
- `target_groups` (array)
  - Limits results to selected target groups.
- `fields_of_activity` (array)
  - Limits results to selected fields of activity.
- `offers` (array of offer IDs)
  - Whitelists results to explicit offer IDs.
- `contact_is_park_partner`
  - Filters to offers from park partners.
- `offers_is_park_event`
  - Filters to event-type offers.
- `online_shop_enabled`
  - Filters to offers with online booking/shop availability.
- `barrier_free`
  - Filters to offers marked as barrier-free.
- `learning_opportunity`
  - Filters to offers flagged as learning opportunity.
- `child_friendly`
  - Filters to child/family friendly offers.
- `has_accessibility_informations`
  - Filters to offers with accessibility information available.
- `is_hint`
  - Filters to hint/info entries (where supported by dataset).
- `system_filter` (hard filter constraints from your system)
  - Applies non-user-overridable constraints from your integration.
  - `target_groups` (array)
    - Hard-limits allowed target groups.
  - `fields_of_activity` (array)
    - Hard-limits allowed fields of activity.
- UI-related toggles:
  - `show_keywords_filter`
    - Shows/hides the keywords filter control in the UI.
  - `hide_user_filter`
    - Hides the free-text search/user filter control.
  - `hide_accessibility_filter`
    - Hides the accessibility filter controls in the UI.

Note:
- Visitor-selected filter values (for example form POST values) are managed internally by the SDK via session/filter state.

---

## 11) Map options (`$api->map_options`)

Typical options:

- `map_initialize_on_load`
  - Initializes the map immediately on page load.
- `show_layers_at_start`
  - Enables configured map layers by default on first render.
- `parkperimeter_visibility`
  - Shows/hides the park perimeter layer.
- `associated_members_visibility`
  - Shows/hides associated members layer (if data is available).
- `link_target`
  - Defines target attribute for map popup/detail links (for example `_self`, `_blank`).
- `full_height`
  - Expands map container to full available height layout.
- `disable_auto_load_oev`
  - Disables automatic loading of public transport overlays/data.
- `map_extent` (`xmin`, `ymin`, `xmax`, `ymax`)
  - Sets initial map bounding box in projected map coordinates.
- `do_not_group_categories_in_legend`
  - Keeps legend entries ungrouped by category.

See `example.php` for a practical setup.

---

## 12) Templating

Templates are located in:

- `parks_api/template/standard/`
- optionally custom folders, for example `parks_api/template/your-template/`

Configuration:

- `config['template_folder'] = 'your-template';`

Important:
- Do not edit core templates directly.
- Create your own template folder based on `standard`.

---

## 13) Overriding Methods (Custom View)

### Step 1: Activate a custom view class

In `config.php`:

```php
$config['class_view'] = "MyView";
```

### Step 2: Use your custom class

File: `parks_api/custom/MyView.php`  
This class extends `ParksView`.

### Step 3: Override targeted methods

Common extension points:

- `overwrite_template_data($template_data, $offer)`
  - Fine-grained template data changes before rendering.
- Full rendering methods from `ParksView`, for example:
  - `filter(...)`
  - `list_offers(...)`
  - `detail(...)`
  - helper methods like `_get_detail_event(...)`, `_get_detail_product(...)`, etc.

Best practices:
- Override only methods you really need.
- Prefer extending behavior with `parent::...`.
- Keep customizations in `custom/`; keep core files update-safe.

Additional example:
- `parks_api/custom/ParksSwissView.php`

---

## 14) Output Mode

- `return_output = false` (default)
  - Methods print output directly (`echo`).
- `return_output = true`
  - Methods return HTML as string.

Useful for:
- CMS integrations with output buffering
- Composed layouts where HTML is injected manually

---

## 15) Multilingual Support

Language files:

- `parks_api/language/de.php`
- `parks_api/language/fr.php`
- `parks_api/language/it.php`
- `parks_api/language/en.php`

Runtime behavior:

- The active SDK language is passed via `new ParksAPI('<lang>')` (for example `de`, `fr`, `it`, `en`).
- If the language is missing/invalid, the SDK falls back to `de`.
- `available_languages` defines which language files can be loaded.
- `language_independence` and `language_priority` control data fallback behavior across languages.
- `default_language` in `config.php` can be used as a project convention, but runtime selection is constructor-driven.

---

## 16) SEO URLs

If your environment uses SEO routing:

- `seo_urls = true`
- define slugs:
  - `seo_url_detail_slug`
  - `seo_url_poi_slug`
  - `seo_url_page_slug`
  - `seo_url_reset_slug`

Routing notes:

- Detail links are parsed via the configured detail slug and offer IDs in the URL path.
- Pagination/reset handling depends on the configured page/reset slugs.
- Ensure your CMS/router forwards these slug paths to the SDK integration endpoint.

---

## 17) Favorites Module

This module is optional. If enabled, each offer can show an "add/remove favorite" link for visitors.

### What it does

- Adds a favorite toggle link in offer listings.
- Stores selected favorites client-side in a cookie.
- Allows rendering a favorites overview page for the current visitor.

### Enable/disable behavior

- `favorites_extension_available = true` enables the feature.
- `favorites_script_path` must point to your API script folder.
- `use_sessions = true` must be enabled for persistent favorites behavior.
- `session_name` defines the cookie/session prefix used by the favorites module.
- The favorite link is rendered when `favorites_extension_available` and `favorites_script_path` are set.
- Full favorite toggle/persistence behavior requires `use_sessions = true`.
- If disabled, no favorite link is shown in offer listings.

### Storage and lifecycle

- Favorites are stored in a cookie named `<session_name>_favorites`.
- The cookie stores offer IDs and is updated when visitors add/remove favorites.
- A clean action is available to clear all favorites.

### Endpoints and methods

- Script endpoint: `parks_api/scripts/favorite.php`
  - Toggle favorite: `favorite.php?offer_id=<id>`
  - Clear all: `favorite.php?action=clean`
- API methods:
  - `toggle_favorite($offer_id)`
  - `show_favorites()`
  - `clean_favorites()`

### Integration note

- Not every park needs favorites. Keep the feature disabled if you do not need visitor bookmark lists.
- `parks.swiss` uses this pattern as a reference implementation for visitor favorites.

---

## 18) Performance Recommendations

### PHP/server

- Use sufficient `memory_limit` and `max_execution_time` for import jobs.
- Run imports via CLI (cron), not via browser.

### Database

- Ensure proper indexes on common join/filter fields (for example `offer_id`, `park_id`, language/link table columns).
- Use `EXPLAIN` for slow queries and optimize indexes based on real workloads.

### Operations

- Choose a cron interval matching your business needs.
- Monitor logs in `log_directory`.

---

## 19) Upgrade Strategy

1. Download the new SDK version.
2. Update core files.
3. Re-apply/compare only your custom layers (`custom/`, custom templates, `config.php`).
4. Run migration if required.
5. Validate:
   - filter
   - list
   - map
   - detail
   - multilingual behavior

---

## 20) Project Rule for Maintainability

Avoid changing core files whenever possible.  
Use these extension points instead:

- `config.php`
- `custom/*.php`
- `template/<your-folder>/`

This keeps upgrades safe and reduces merge risk.

---

## 21) Contact

More information:

- [parks.swiss](https://www.parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71  
[info@parks.swiss](mailto:info@parks.swiss)
