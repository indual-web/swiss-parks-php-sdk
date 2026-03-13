# PHP SDK Documentation - Swiss Parks Network

This SDK imports offer data from an XML export into a local MySQL/MariaDB database and renders it as filter, list, map, and detail views. It is designed for server-side PHP integration and can be extended via templates and custom view classes.

---

## 1) Requirements

- PHP `>= 7.4` and `<= 8.4`
- MySQL or MariaDB
- Write permissions for the log directory (`parks_api/log/` or your configured path)
- Access to frontend assets (if not auto-included):
  - `parks.min.css`
  - `parks.min.js`

---

## 2) Architecture Overview

- **Import layer**
  - `ParksImport` fetches XML data and stores it in the local database.
- **Data/filter layer**
  - `ParksModel` handles SQL filtering, aggregation, and query logic.
- **Rendering layer**
  - `ParksView` renders filter, list, map, pagination, and detail output.
  - `custom/MyView.php` can override methods from `ParksView`.
- **Orchestration**
  - `ParksAPI` is the main integration entry point.

---

## 3) Installation

1. Download the SDK from `https://angebote.paerke.ch/en/settings`.
2. Upload/copy the project to your web server.
3. Create a database.
4. Import `database/database.sql`.
5. Configure `parks_api/config.php`.
6. Run the first import:
   - CLI: `php parks_api/scripts/cron.php`
7. Configure a regular cronjob (for example every 15 minutes).

---

## 4) Core Configuration (`parks_api/config.php`)

### Required settings

- `api_hash`
  - Hash from your export configuration on `angebote.paerke.ch`.
- `db_hostname`, `db_username`, `db_password`, `db_database`
  - Database connection credentials.

### Runtime mode: single park vs network-wide

- `park_id`
  - `> 0`: integration for one specific park.
  - `0` or empty: network-wide integration (all available parks).

### Common display/runtime options

- `class_view` (for example `MyView`)
- `template_folder` (for example `standard`)
- `offers_per_page`
- `show_route_filter`
- `show_target_group_filter`
- `show_accessibility_filter`
- `show_municipality_filter`
- `prevent_css_js_include`
- `return_output`
- `use_sessions`, `session_name`
- `language_independence`, `language_priority`

---

## 5) Quick Start Integration

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

## 6) Import and Update Lifecycle

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

## 7) Public API Methods for Integrators

Most relevant methods from `ParksAPI`:

- `show_offers_filter($categories = [], $filter = [], $park_id = null)`
- `show_offers_list($categories = [], $filter = [], $park_id = null)`
- `show_offers_map($categories = [], $filter = [], $park_id = null)`
- `show_offers_pagination()`
- `show_offer_detail($single_offer_id = null)`
- `show_favorites()`
- `toggle_favorite($offer_id)`
- `clean_favorites()`
- `is_offer_detail()`
- `is_filter_activated()`
- `get_filter_data()`

Data access (instead of direct rendering):

- `get_offers_list(...)`
- `get_offer_detail($offer_id)`
- `get_categories_list()`
- `get_categories_list_for_select()`

Import and maintenance:

- `update($force = false)`
- `migrate()`

---

## 8) Filter Options and Capabilities

You can pass these keys in `$filter` (among others):

- `keywords`
- `search`
- `target_groups` (array)
- `fields_of_activity` (array)
- `offers` (array of offer IDs)
- `contact_is_park_partner`
- `offers_is_park_event`
- `online_shop_enabled`
- `barrier_free`
- `learning_opportunity`
- `child_friendly`
- `has_accessibility_informations`
- `is_hint`
- `system_filter` (hard filter constraints from your system)
  - `target_groups` (array)
  - `fields_of_activity` (array)
- UI-related toggles:
  - `show_keywords_filter`
  - `hide_user_filter`
  - `hide_accessibility_filter`

Note:
- Visitor-selected filter values (for example form POST values) are managed internally by the SDK via session/filter state.

---

## 9) Map Options (`$api->map_options`)

Typical options:

- `map_initialize_on_load`
- `show_layers_at_start`
- `parkperimeter_visibility`
- `associated_members_visibility`
- `link_target`
- `full_height`
- `disable_auto_load_oev`
- `map_extent` (`xmin`, `ymin`, `xmax`, `ymax`)
- `do_not_group_categories_in_legend`

See `example.php` for a practical setup.

---

## 10) Templating

Templates are located in:

- `parks_api/template/standard/`
- optionally custom folders, for example `parks_api/template/your-template/`

Configuration:

- `config['template_folder'] = 'your-template';`

Important:
- Do not edit core templates directly.
- Create your own template folder based on `standard`.

---

## 11) Overriding Methods (Custom View)

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

## 12) Output Mode

- `return_output = false` (default)
  - Methods print output directly (`echo`).
- `return_output = true`
  - Methods return HTML as string.

Useful for:
- CMS integrations with output buffering
- Composed layouts where HTML is injected manually

---

## 13) Multilingual Support

Language files:

- `parks_api/language/de.php`
- `parks_api/language/fr.php`
- `parks_api/language/it.php`
- `parks_api/language/en.php`

Controlled by:

- `default_language`
- `available_languages`
- `language_independence`
- `language_priority`

The SDK can fall back to alternative languages depending on your configuration.

---

## 14) SEO URLs

If your environment uses SEO routing:

- `seo_urls = true`
- define slugs:
  - `seo_url_detail_slug`
  - `seo_url_poi_slug`
  - `seo_url_page_slug`
  - `seo_url_reset_slug`

The SDK handles detail/pagination/reset URL parsing accordingly.

---

## 15) Favorites Module

Configuration:

- `favorites_extension_available`
- `favorites_script_path`

Script endpoint:

- `parks_api/scripts/favorite.php`

API methods:

- `toggle_favorite($offer_id)`
- `show_favorites()`
- `clean_favorites()`

---

## 16) Performance Recommendations

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

## 17) Upgrade Strategy

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

## 18) Project Rule for Maintainability

Avoid changing core files whenever possible.  
Use these extension points instead:

- `config.php`
- `custom/*.php`
- `template/<your-folder>/`

This keeps upgrades safe and reduces merge risk.

---

## 19) Contact

More information:

- [parks.swiss](https://www.parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71  
[info@parks.swiss](mailto:info@parks.swiss)
