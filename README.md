# PHP SDK Documentation - Swiss Parks Network

This SDK imports offer data from an XML export into a local MySQL/MariaDB database and renders filter, list, map, and detail views for server-side PHP integrations.

## Documentation structure

- Start here in this `README.md` for setup and workflow overview.
- Detailed guides are in [`docs/guides/index.md`](docs/guides/index.md).
- API details are in [`docs/api-reference/index.md`](docs/api-reference/index.md).

## Quick navigation

- New installation: [`docs/guides/new-installation.md`](docs/guides/new-installation.md)
- Upgrade: [`docs/guides/upgrading.md`](docs/guides/upgrading.md)
- Migration and update lifecycle: [`docs/guides/import-update-lifecycle.md`](docs/guides/import-update-lifecycle.md)
- Core configuration: [`docs/guides/configuration.md`](docs/guides/configuration.md)
- Quick start integration: [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md)
- API reference: [`docs/api-reference/index.md`](docs/api-reference/index.md)

---

## 1) Requirements

- PHP `>= 8.2` and `<= 8.4`
- MySQL or MariaDB
- Write permissions for the log directory (`parks_api/log/` or your configured path)
- PHP cURL extension enabled
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
   - Place the required API release ZIP in `downloads/`.
   - Extract the release ZIP and import the base schema from `database/database.sql`.
2. **Upgrade**
   - Update SDK/API files to the target release.
3. **Migrate**
   - Run `php parks_api/scripts/migrate.php` to apply schema/data migrations.

The base schema `database/database.sql` is part of each downloaded release package.

---

## 4) New Installation

Use the dedicated guide:

- [`docs/guides/new-installation.md`](docs/guides/new-installation.md)

Quick summary:

1. Install SDK files and import `database/database.sql`.
2. Configure `parks_api/config.php` (`api_hash`, DB credentials, `park_id`).
3. Run first import: `php parks_api/scripts/cron.php`.
4. Verify data import and configure regular cron execution.

---

## 5) Upgrade from an older version (example: v21 to v22)

Use the dedicated guide:

- [`docs/guides/upgrading.md`](docs/guides/upgrading.md)

Quick summary:

1. Backup files and database.
2. Replace core SDK files (`autoload.php`, `classes/`).
3. Run migration: `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`.
4. Run forced import: `php /{PATH-TO-YOUR-API-FOLDER}/scripts/force_update.php`.
5. Validate filter/list/map/detail, console output, and logs.

---

## 6) Core Configuration

Configuration details for `parks_api/config.php`:

- [`docs/guides/configuration.md`](docs/guides/configuration.md)

---

## 7) Quick Start Integration

Integration example and implementation basics:

- [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md)

---

## 8) Import and Update Lifecycle

Commands for `cron.php`, `force_update.php`, and migration are documented in:

- [`docs/guides/import-update-lifecycle.md`](docs/guides/import-update-lifecycle.md)

---

## 9) Public API methods for integrators

- [`docs/api-reference/parks-api.md`](docs/api-reference/parks-api.md)

---

## 10) Filter options and capabilities

- [`docs/api-reference/filter-options.md`](docs/api-reference/filter-options.md)

---

## 11) Map options (`$api->map_options`)

- [`docs/api-reference/map-options.md`](docs/api-reference/map-options.md)

---

## 12) Templating and Custom View

- [`docs/guides/templating-and-custom-view.md`](docs/guides/templating-and-custom-view.md)

---

## 13) Output Mode and Multilingual Support

- [`docs/guides/output-and-language.md`](docs/guides/output-and-language.md)

---

## 14) SEO URLs

- [`docs/guides/seo-urls.md`](docs/guides/seo-urls.md)

---

## 15) Favorites

- [`docs/guides/favorites.md`](docs/guides/favorites.md)

---

## 16) Performance and Upgrade Strategy

- [`docs/guides/performance-and-upgrade-strategy.md`](docs/guides/performance-and-upgrade-strategy.md)

---

## 17) Contact

More information:

- [parks.swiss](https://www.parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71  
[info@parks.swiss](mailto:info@parks.swiss)
