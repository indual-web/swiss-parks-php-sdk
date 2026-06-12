# Upgrade guide

This guide describes a safe upgrade workflow from an older SDK/API version to a newer release.

## Recommended upgrade flow

1. Download the target API/SDK release.
2. Create a full backup of your current API files.
3. Replace SDK core files in your API folder:
   - `autoload.php`
   - `classes/`
   - `database/`
   - `helpers/`
   - `language/`
4. Run migration:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`
   - This deletes the SQLite database file, recreates it with the current schema, and runs a full import.
5. Validate functionality end-to-end:
   - filter
   - list
   - map
   - detail
   - browser console
   - logs

## Breaking change: MySQL replaced by SQLite

Since the switch to SQLite, no database server is used anymore. When upgrading from a MySQL-based version:

1. Update `config.php`: remove `db_hostname`, `db_port`, `db_username`, `db_password`, `db_database` and add `db_path` (default: `data/park-offers.sqlite`).
2. Ensure the web server has write permissions on the `db_path` directory (`parks_api/data/` by default).
3. Run `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php` — all data is re-imported automatically from the XML export.
4. The old MySQL database is no longer used and can be removed after validation.

## Upgrade notes

- Keep customizations outside replaced core paths:
  - `custom/`
  - custom templates
  - `config.php`
- Since the database is only a mirror of the XML export, no data backup is needed; `migrate.php` rebuilds it completely.
- Re-test multilingual behavior and SEO routes when enabled.

## Rollback strategy

If issues appear after upgrade:

1. Restore file backup.
2. Re-run old version cron/import (the SQLite database is rebuilt automatically).

## Related docs

- [Guide index](./index.md)
- [README](../../README.md) (operations overview)
- [API reference index](../api-reference/index.md)
