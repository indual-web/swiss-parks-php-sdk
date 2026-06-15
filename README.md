# PHP SDK Documentation - Swiss Parks Network

This PHP SDK imports offer data from an XML export into a local SQLite database file (created automatically, no database server required) and renders filter, list, map, and detail views for server-side PHP integrations.

## Documentation structure

- Start here in this `README.md` for setup and workflow overview.
- Detailed guides are in [`docs/guides/index.md`](docs/guides/index.md).
- SDK details are in [`docs/sdk-reference/index.md`](docs/sdk-reference/index.md).

## Quick navigation

- New installation: [`docs/guides/new-installation.md`](docs/guides/new-installation.md)
- Upgrade: [`docs/guides/upgrading.md`](docs/guides/upgrading.md)
- Migration and update lifecycle: [`docs/guides/import-update-lifecycle.md`](docs/guides/import-update-lifecycle.md)
- Core configuration: [`docs/guides/configuration.md`](docs/guides/configuration.md)
- Quick start integration: [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md)
- SDK reference: [`docs/sdk-reference/index.md`](docs/sdk-reference/index.md)

---

## 1) Requirements

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` extension (bundled with PHP by default), SQLite `>= 3.25`
- Write permissions for the data directory (`swiss-parks-sdk/data/` or your configured `db_path`)
- Write permissions for the log directory (`swiss-parks-sdk/log/` or your configured path)
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
   - Download the required API release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases).
   - Extract the release ZIP and copy the `swiss-parks-sdk` directory into your project.
2. **Upgrade**
   - CLI: `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest` (downloads release, updates core files, runs migration)
   - Or update SDK/API files manually from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases)
3. **Migrate** (if not using `upgrade-sdk.sh`)
   - Run `php swiss-parks-sdk/scripts/migrate.php` to rebuild the SQLite database with the current schema and run a full import.

The SQLite database file is created automatically on first run from `swiss-parks-sdk/database/schema.sql`; no manual schema import is needed.

---

## 4) New Installation

Use the dedicated guide:

- [`docs/guides/new-installation.md`](docs/guides/new-installation.md)

Quick summary:

1. Install SDK files from the release ZIP (`swiss-parks-sdk/` + `example.php`).
2. Configure `swiss-parks-sdk/config.php` (`api_hash`, `park_id`).
3. Run first import: `bash swiss-parks-sdk/bin/sync.sh` or via browser at `scripts/cron.php` (creates the SQLite database automatically).
4. Verify data import and configure regular cron execution (`bin/sync.sh`).

---

## 5) Upgrade from an older version

- Generic workflow: [`docs/guides/upgrading.md`](docs/guides/upgrading.md)
- Version-specific migrations: [`docs/guides/migrations/README.md`](docs/guides/migrations/README.md)
- **v21 → v22 (MySQL to SQLite):** [`docs/guides/migrations/21-to-22.md`](docs/guides/migrations/21-to-22.md)

---

## 6) Core Configuration

Configuration details for `swiss-parks-sdk/config.php`:

- [`docs/guides/configuration.md`](docs/guides/configuration.md)

---

## 7) Quick Start Integration

Integration example and implementation basics:

- [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md)

---

## 8) Import and Update Lifecycle

Commands for `sync.php`, `force_update.php`, and migration are documented in:

- [`docs/guides/import-update-lifecycle.md`](docs/guides/import-update-lifecycle.md)

---

## 9) ParksAPI methods for integrators

- [`docs/sdk-reference/parks-api.md`](docs/sdk-reference/parks-api.md)

---

## 10) Filter options and capabilities

- [`docs/sdk-reference/filter-options.md`](docs/sdk-reference/filter-options.md)

---

## 11) Map options (`$api->map_options`)

- [`docs/sdk-reference/map-options.md`](docs/sdk-reference/map-options.md)

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
