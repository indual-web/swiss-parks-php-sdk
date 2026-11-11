# Swiss Parks PHP SDK

PHP SDK that imports Swiss Parks offer XML into a local SQLite database and renders filter, list, map, and detail views. No database server is required.

Full documentation: [`docs/index.md`](docs/index.md)

## Requirements

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` (SQLite `>= 3.25`)
- Write access for `swiss-parks-sdk/writable/data/` and `writable/log/` (or your configured paths)
- PHP cURL and outbound HTTPS to `angebote.paerke.ch`

## Developer paths

| Path | Start here |
| --- | --- |
| Documentation hub | [`docs/index.md`](docs/index.md) |
| PHP integration | [`docs/guides/quick-start-integration.md`](docs/guides/quick-start-integration.md) |
| WordPress | [`docs/guides/wordpress-integration.md`](docs/guides/wordpress-integration.md) |
| Upgrade | [`docs/guides/upgrading.md`](docs/guides/upgrading.md) |

First install: [`docs/guides/new-installation.md`](docs/guides/new-installation.md) · Config: [`docs/guides/configuration.md`](docs/guides/configuration.md) · Reference: [`docs/sdk-reference/index.md`](docs/sdk-reference/index.md)

## Coming from v21?

v22 is not a drop-in replace. Plan for:

- Folder `parks_api/` → `swiss-parks-sdk/`, config at `config/config.php`
- MySQL → SQLite (`db_path`); data is re-imported from XML, not migrated
- Public API camelCase (`show_offers_filter` → `showOffersFilter`)
- Frontend `…/dist/frontend/v22/` as `type="module"`; map container `swiss-parks-map`
- Cron `bin/sync.sh`; favorites `public/favorites.php`

Step-by-step: [`docs/guides/migrations/21-to-22.md`](docs/guides/migrations/21-to-22.md)

## Contact

[parks.swiss](https://www.parks.swiss) · [info@parks.swiss](mailto:info@parks.swiss)

**Network of Swiss Parks**  
Monbijoustrasse 61, CH-3007 Bern  
+41 (0)31 381 10 71
