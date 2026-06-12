# PHP SDK Documentation - Swiss Parks Network

This PHP SDK imports offer data from an XML export into a local SQLite database file (created automatically, no database server required) and renders filter, list, map, and detail views for server-side PHP integrations.

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

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` extension (bundled with PHP by default), SQLite `>= 3.25`
- Write permissions for the data directory (`parks_api/data/` or your configured `db_path`)
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
   - Download the required API release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases).
   - Extract the release ZIP and copy the `parks_api` directory into your project.
2. **Upgrade**
   - Update SDK/API files to the target release.
3. **Migrate**
   - Run `php parks_api/scripts/migrate.php` to rebuild the SQLite database with the current schema and run a full import.

The SQLite database file is created automatically on first run from `parks_api/database/schema.sql`; no manual schema import is needed.

---

## 4) New Installation

Use the dedicated guide:

- [`docs/guides/new-installation.md`](docs/guides/new-installation.md)

Quick summary:

1. Install SDK files from the release ZIP (`parks_api/` + `example.php`).
2. Configure `parks_api/config.php` (`api_hash`, `park_id`).
3. Run first import: `php parks_api/scripts/cron.php` or via browser (creates the SQLite database automatically).
4. Verify data import and configure regular cron execution.

---

## 5) Upgrade from an older version

- Generic workflow: [`docs/guides/upgrading.md`](docs/guides/upgrading.md)
- Version-specific migrations: [`docs/guides/migrations/README.md`](docs/guides/migrations/README.md)
- **v21 → v22 (MySQL to SQLite):** [`docs/guides/migrations/21-to-22.md`](docs/guides/migrations/21-to-22.md)

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

## 17) Smoke tests and CI (SDK maintainers only)

The offline SQLite/query smoke test lives in `tests/smoke_test.php` and is **not** included in release ZIPs built via `deploy.sh`. SDK maintainers can run it after core changes:

```bash
php tests/smoke_test.php
```

GitHub Actions runs the same test on PHP 8.2–8.4 (see `.github/workflows/tests.yml`). To publish a release, tag the version and push the tag — CI builds the ZIP and uploads it to GitHub Releases (see `.github/workflows/release.yml` and [`docs/guides/release-checklist.md`](docs/guides/release-checklist.md)):

```bash
git tag v22
git push origin v22
```

---

## 18) Contact

More information:

- [parks.swiss](https://www.parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71  
[info@parks.swiss](mailto:info@parks.swiss)
