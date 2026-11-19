[Docs](../index.md) › [Guides](./index.md) › New installation

# New installation

This guide describes the first-time setup of the SDK in a new environment.

## Prerequisites

- PHP `>= 8.2` and `<= 8.5`
- PHP `pdo_sqlite` extension (bundled with PHP by default), SQLite `>= 3.25`
- cURL extension enabled
- Outbound HTTPS to `angebote.paerke.ch` (XML/JSON export)
- Write permissions for `swiss-parks-sdk/writable/data/` (or your configured `db_path` directory)
- Write permissions for `swiss-parks-sdk/writable/log/` (or your configured `log_directory`)

No database server is required: the SDK stores all data in a local SQLite file that is created automatically on first run.

## Step-by-step

1. Download the latest release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases) (`swiss-parks-php-sdk-<version>.zip`).
2. Upload/copy the `swiss-parks-sdk` directory into your project.
3. Configure `swiss-parks-sdk/config/config.php`.
   - Set `park_id` and `api_hash` from `https://angebote.paerke.ch/en/settings`.
   - Optionally adjust `db_path` (default: `writable/data/park-offers.sqlite`).
   - See [configuration](./configuration.md) for optional keys such as `curl_verify_ssl`.
4. Run the first import:
   - CLI: `bash swiss-parks-sdk/bin/sync.sh` (or `bash swiss-parks-sdk/bin/sync.sh --force` for a full import)
   - The SQLite database file and schema are created automatically.
5. Verify import success:
   - The SQLite file exists at the configured `db_path`.
   - The `offer` table contains entries.
6. Integrate SDK rendering into your project pages based on `index.php`.
   - Add the SDK bootstrap and rendering calls (`showOffersFilter`, `showOffersList`, `showOffersMap`, `showOfferDetail`) in your own template/page code.
   - Include the required frontend assets in your template (`parks.min.css` and `parks.min.js` from the `v22` frontend path), as shown in `index.php`.
   - Load assets from `https://angebote.paerke.ch/dist/frontend/v22/…` (see `index.php`).
   - Use a `parks.min.js` build that looks for map container id `swiss-parks-map` (older builds expect `mapContainer` and leave the map tab empty).
7. Configure a recurring cronjob (for example every 4 hours). Prefer CLI. See [import and update lifecycle](./import-update-lifecycle.md).

```cron
0 */4 * * * bash /path/to/swiss-parks-sdk/bin/sync.sh >> /path/to/swiss-parks-sdk/writable/log/cron-sync.log 2>&1
```

Without SSH, set `sync_secret` and schedule a URL cron against `public/sync.php?token=…` ([HTTP sync](./import-update-lifecycle.md#http-sync-shared-hosting)).

## Verification checklist

- Filter/list pages render correctly.
- Detail page opens correctly.
- Map loads without JS errors.
- Logs show no critical import errors.

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| Empty list / empty SQLite | Run `bash swiss-parks-sdk/bin/sync.sh --force`; confirm `api_hash` / `park_id` and rows in the `offer` table. |
| Sync fails with SSL/cURL HTTPS errors | Set `curl_verify_ssl = false` only as a hosting fallback; confirm outbound HTTPS to `angebote.paerke.ch`. |
| Cannot create DB or write logs | Fix write permissions on `db_path` and `log_directory` (web server and cron user). |
| Map tab empty | Use the v22 `parks.min.js` as `type="module"` and container id `swiss-parks-map` (not `mapContainer`). |
| Assets/CDN 404 or wrong host | Use `https://angebote.paerke.ch/dist/frontend/v22/…` as in `index.php`. |

WordPress-specific issues: [WordPress integration](./wordpress-integration.md#troubleshooting).

## Related docs

- [Guide index](./index.md)
- [Documentation overview](../index.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
- [SDK reference index](../sdk-reference/index.md)
