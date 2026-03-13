# New Installation Guide

This guide describes the first-time setup of the SDK in a new environment.

## Prerequisites

- PHP `>= 8.2` and `<= 8.4`
- MySQL or MariaDB
- cURL extension enabled
- Write permissions for `parks_api/log/` (or your configured `log_directory`)

## Step-by-step

1. Download the latest API release ZIP into your local `downloads/releases` workflow.
2. Upload/copy the `parks_api` directory into your project.
3. Create a database for the SDK.
4. Extract the release package and import `database/database.sql` from the package.
5. Configure `parks_api/config.php`.
   - Set database credentials.
   - Set `park_id`.
   - Set `api_hash` from `https://angebote.paerke.ch/en/settings`.
6. Run the first import:
   - CLI: `php parks_api/scripts/cron.php`
   - Web: `[Your project path]/parks_api/scripts/cron.php`
7. Verify import success:
   - Database/tables exist.
   - `offer` table contains entries.
8. Configure a recurring cronjob (for example every 15 minutes).

## Verification checklist

- Filter/list pages render correctly.
- Detail page opens correctly.
- Map loads without JS errors.
- Logs show no critical import errors.

## Related docs

- `README.md` (integration overview)
- `docs/api-reference/index.md`
