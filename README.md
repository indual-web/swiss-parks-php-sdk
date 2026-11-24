# Swiss Parks PHP SDK

PHP SDK that imports Swiss Parks offer XML into a local SQLite database and renders filter, list, map, and detail views. No database server is required.

For developers integrating the SDK into a park website. Full documentation: [`docs/index.md`](docs/index.md)

## Requirements

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` (SQLite `>= 3.25`)
- Write access for `swiss-parks-sdk/writable/data/` and `writable/log/` (or your configured paths)
- PHP `curl`, `simplexml`, `mbstring` and outbound HTTPS to `hub.parks.swiss`

## Install

- ZIP: download a [release](https://github.com/indual-web/swiss-parks-php-sdk/releases) and copy `swiss-parks-sdk/` into your project
- Composer: `composer require indual-web/swiss-parks-php-sdk:^22.0`

Both paths are described in [`docs/guides/new-installation.md`](docs/guides/new-installation.md).

## Developer paths

| Path | Start here |
| --- | --- |
| Documentation overview | [`docs/index.md`](docs/index.md) |
| First install | [`docs/guides/new-installation.md`](docs/guides/new-installation.md) |
| Configuration | [`docs/guides/configuration.md`](docs/guides/configuration.md) |
| PHP integration | [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md) |
| WordPress | [`docs/guides/wordpress-integration.md`](docs/guides/wordpress-integration.md) |
| Upgrade | [`docs/guides/upgrading.md`](docs/guides/upgrading.md) |
| API reference | [`docs/sdk-reference/swiss-parks-sdk.md`](docs/sdk-reference/swiss-parks-sdk.md) |
| Contributing (PRs) | [`CONTRIBUTING.md`](CONTRIBUTING.md) |

## Coming from v21 or older?

Upgrade directly to v22, no matter which version you are on. You do not need to install the versions in between.

v22 is not a drop-in replacement. Plan for:

- Folder `parks_api/` → `swiss-parks-sdk/`, own settings in `config/config.local.php` (returns an array)
- Classes in the namespace `SwissParks\Sdk`, entry class `SwissParksSdk`
- MySQL → SQLite (`db_path`); data is re-imported from XML, not migrated
- Public API camelCase (`show_offers_filter` → `showOffersFilter`)
- Frontend `…/dist/frontend/v22/` as `type="module"`; map container `swiss-parks-map`
- Cron `bin/sync.sh` (shared hosting without SSH: `public/sync.php` + `sync_secret`); favorites `public/favorites.php`
- From v20 or older only: update custom templates (new and removed placeholders), see [coming from v20 or older](docs/guides/migrations/21-to-22.md#coming-from-v20-or-older)

Step-by-step: [`docs/guides/migrations/21-to-22.md`](docs/guides/migrations/21-to-22.md)

Older packages (v14–v21.1) are archived for reference only at [swiss-parks-php-sdk-legacy](https://github.com/indual-web/swiss-parks-php-sdk-legacy).

## Contact

[parks.swiss](https://www.parks.swiss) · [info@parks.swiss](mailto:info@parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71
