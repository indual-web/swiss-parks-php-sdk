# Upgrade guide

This guide describes the **generic** upgrade workflow for any Swiss Parks PHP SDK release.

For breaking changes between specific versions, use a [version-specific migration guide](./migrations/README.md).

## Recommended upgrade flow

### Option A: CLI upgrade script (preferred)

From the `swiss-parks-sdk/` directory on the server (SSH):

```bash
bash bin/upgrade-sdk.sh latest
# or a specific version:
bash bin/upgrade-sdk.sh 22
```

The script:

1. Downloads the release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases)
2. Creates a backup next to `swiss-parks-sdk/` (`swiss-parks-sdk-backup-<timestamp>/`)
3. Replaces SDK core files (`autoload.php`, `classes/`, `database/`, `helpers/`, `language/`, `bin/`, `scripts/`, default templates)
4. Preserves `config.php`, `custom/`, `data/`, `log/`, and custom templates
5. Runs `php scripts/migrate.php` (rebuilds SQLite DB + full import)

Exit code: `0` on success, `1` on failure. Set `PARKS_API_SKIP_MIGRATE=1` to update files only.

Apply version-specific config steps from a [migration guide](./migrations/README.md) **before** running the script when a guide requires manual `config.php` changes.

### Option B: Manual ZIP upgrade

1. Download the target API/SDK release.
2. Create a full backup of your current API files.
3. Replace SDK core files in your API folder:
   - `autoload.php`
   - `classes/`
   - `database/`
   - `helpers/`
   - `language/`
   - `bin/`
4. Apply version-specific steps from a [migration guide](./migrations/README.md) if one exists for your upgrade path.
5. Run migration:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`
   - Deletes the SQLite database file, recreates it with the current schema, and runs a full import.

### Validation (both options)

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
