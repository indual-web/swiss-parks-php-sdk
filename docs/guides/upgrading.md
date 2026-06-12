# Upgrade guide

This guide describes the **generic** upgrade workflow for any Parks-API release.

For breaking changes between specific versions, use a [version-specific migration guide](./migrations/README.md).

## Recommended upgrade flow

1. Download the target API/SDK release.
2. Create a full backup of your current API files.
3. Replace SDK core files in your API folder:
   - `autoload.php`
   - `classes/`
   - `database/`
   - `helpers/`
   - `language/`
4. Apply version-specific steps from a [migration guide](./migrations/README.md) if one exists for your upgrade path.
5. Run migration:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`
   - Deletes the SQLite database file, recreates it with the current schema, and runs a full import.
6. Validate functionality end-to-end:
   - filter
   - list
   - map
   - detail
   - browser console
   - logs

## Version-specific migrations

| From | To | Guide |
| --- | --- | --- |
| v21 (MySQL) | v22 (SQLite) | [21 to 22](./migrations/21-to-22.md) |

New guides are added to [migrations/README.md](./migrations/README.md) when a release needs steps beyond the generic flow above.

## Upgrade notes

- Keep customizations outside replaced core paths:
  - `custom/`
  - custom templates
  - `config.php`
- Since the database is only a mirror of the XML export, no data backup is needed for SQLite upgrades; `migrate.php` rebuilds it completely.
- Re-test multilingual behavior and SEO routes when enabled.

## Rollback strategy

If issues appear after upgrade:

1. Restore file backup.
2. Re-run the previous version's cron/import workflow.

For v21 rollback after a failed v22 attempt, see [21 to 22 — Rollback](./migrations/21-to-22.md#7-rollback).

## Related docs

- [Migrations index](./migrations/README.md)
- [Guide index](./index.md)
- [README](../../README.md) (operations overview)
- [API reference index](../api-reference/index.md)
