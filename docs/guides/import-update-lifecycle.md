# Import and update lifecycle

This guide documents the regular update flow, forced updates, SDK upgrades, and migration.

## Directory layout

- `swiss-parks-sdk/bin/` — shell entry points (SSH / crontab)
- `swiss-parks-sdk/scripts/` — PHP endpoints (CLI and browser)

## Standard sync

- PHP: `swiss-parks-sdk/scripts/sync.php`
- Shell: `swiss-parks-sdk/bin/sync.sh`
- Commands:
  - `bash swiss-parks-sdk/bin/sync.sh`
  - `php swiss-parks-sdk/scripts/sync.php`
- Legacy URL/CLI: `swiss-parks-sdk/scripts/cron.php` (forwards to `sync.php`)
- Exit code: `0` on success, `1` on import failure or exception (for cron monitoring).

## Forced update

- File: `swiss-parks-sdk/scripts/force_update.php`
- Command:
  - `php swiss-parks-sdk/scripts/force_update.php`
- Exit code: `0` on success, `1` on failure.

## SDK version upgrade

- File: `swiss-parks-sdk/bin/upgrade-sdk.sh`
- Command:
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest`
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh 22`
- Downloads the release from GitHub, updates SDK core files (preserves `config.php`, `custom/`, `data/`, `log/`), creates a backup, and runs `migrate.php`.
- Exit code: `0` on success, `1` on failure.
- See [upgrade guide](./upgrading.md) for manual ZIP workflow and version-specific migration steps.

## Migration

- PHP: `swiss-parks-sdk/scripts/migrate.php`
- Shell: `swiss-parks-sdk/bin/migrate.sh`
- Commands:
  - `bash swiss-parks-sdk/bin/migrate.sh`
  - `php swiss-parks-sdk/scripts/migrate.php`
- Deletes the SQLite database file, recreates it with the current schema, and runs a full import. There are no incremental schema migrations anymore.
- Exit code: `0` when import completes and the API is initialized, `1` otherwise.

## Recommended usage

- Use `bin/sync.sh` or `scripts/sync.php` for regular data sync.
- Use `scripts/cron.php` only for existing crontab URLs (legacy alias).
- Use `force_update.php` only when needed.
- Use `bin/upgrade-sdk.sh` for SDK version upgrades (files + `migrate.php` in one step).
- Run `migrate.php` alone when core files are already updated; it includes a full import, so no separate forced import is required afterwards.

## Related docs

- [Guide index](./index.md)
- [New installation](./new-installation.md)
- [Upgrade guide](./upgrading.md)
- [Version migrations](./migrations/README.md)
