# New installation guide

This guide describes the first-time setup of the SDK in a new environment.

## Prerequisites

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` extension (bundled with PHP by default), SQLite `>= 3.25`
- cURL extension enabled
- Write permissions for `swiss-parks-sdk/data/` (or your configured `db_path` directory)
- Write permissions for `swiss-parks-sdk/log/` (or your configured `log_directory`)

No database server is required: the SDK stores all data in a local SQLite file that is created automatically on first run.

## Step-by-step

1. Download the latest release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases) (`swiss-parks-php-sdk-<version>.zip`).
2. Upload/copy the `swiss-parks-sdk` directory into your project.
3. Configure `swiss-parks-sdk/config.php`.
   - Set `park_id` and `api_hash` from `https://angebote.paerke.ch/en/settings`.
   - Optionally adjust `db_path` (default: `data/park-offers.sqlite`).
   - See [configuration.md](configuration.md) for optional keys such as `curl_verify_ssl`.
4. Run the first import:
   - CLI: `bash swiss-parks-sdk/bin/sync.sh`
   - Web: `[Your project path]/swiss-parks-sdk/scripts/cron.php` (legacy alias for `sync.php`)
   - The SQLite database file and schema are created automatically.
5. Verify import success:
   - The SQLite file exists at the configured `db_path`.
   - The `offer` table contains entries.
6. Integrate SDK rendering into your project pages based on `example.php`.
   - Add the API bootstrap and rendering calls (`show_offers_filter`, `show_offers_list`, `show_offers_map`, `show_offer_detail`) in your own template/page code.
   - Include the required frontend assets in your template (`<link ...parks.min.css>` and `<script ...parks.min.js>`), as shown in `example.php`.
7. Configure a recurring cronjob (for example every 4 hours).

## Verification checklist

- Filter/list pages render correctly.
- Detail page opens correctly.
- Map loads without JS errors.
- Logs show no critical import errors.

## Related docs

- [Guide index](./index.md)
- [README](../../README.md) (integration overview)
- [SDK reference index](../sdk-reference/index.md)
