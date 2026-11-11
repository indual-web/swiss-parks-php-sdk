# New installation guide

This guide describes the first-time setup of the SDK in a new environment.

## Prerequisites

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` extension (bundled with PHP by default), SQLite `>= 3.25`
- cURL extension enabled
- Write permissions for `swiss-parks-sdk/writable/data/` (or your configured `db_path` directory)
- Write permissions for `swiss-parks-sdk/writable/log/` (or your configured `log_directory`)

No database server is required: the SDK stores all data in a local SQLite file that is created automatically on first run.

## Step-by-step

1. Download the latest release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases) (`swiss-parks-php-sdk-<version>.zip`).
2. Upload/copy the `swiss-parks-sdk` directory into your project.
3. Configure `swiss-parks-sdk/config/config.php`.
   - Set `park_id` and `api_hash` from `https://angebote.paerke.ch/en/settings`.
   - Optionally adjust `db_path` (default: `writable/data/park-offers.sqlite`).
   - See [configuration.md](./configuration.md) for optional keys such as `curl_verify_ssl`.
4. Run the first import:
   - CLI: `bash swiss-parks-sdk/bin/sync.sh` (or `bash swiss-parks-sdk/bin/sync.sh --force` for a full import)
   - The SQLite database file and schema are created automatically.
5. Verify import success:
   - The SQLite file exists at the configured `db_path`.
   - The `offer` table contains entries.
6. Integrate SDK rendering into your project pages based on `index.php`.
   - Add the SDK bootstrap and rendering calls (`showOffersFilter`, `showOffersList`, `showOffersMap`, `showOfferDetail`) in your own template/page code.
   - Include the required frontend assets in your template (`parks.min.css` and `parks.min.js` from the `v22` frontend path), as shown in `index.php`.
   - Until the public release, the example uses the staging host `https://staging-map.angebote.paerke.ch/dist/frontend/v22/…`. Switch to the production CDN host before going live.
   - Use a `parks.min.js` build that looks for map container id `swiss-parks-map` (older builds expect `mapContainer` and leave the map tab empty).
7. Configure a recurring cronjob (for example every 4 hours).

## Verification checklist

- Filter/list pages render correctly.
- Detail page opens correctly.
- Map loads without JS errors.
- Logs show no critical import errors.

## Related docs

- [Guide index](./index.md)
- [Documentation overview](../index.md)
- [SDK reference index](../sdk-reference/index.md)
