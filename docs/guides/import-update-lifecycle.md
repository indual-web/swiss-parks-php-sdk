[Docs](../index.md) › [Guides](./index.md) › Import and update lifecycle

# Import and update lifecycle

This guide documents sync, forced full import, SDK upgrades, and migration.

## Directory layout

- `swiss-parks-sdk/bin/` — CLI only (`sync`, `sync-smoke`, `migrate`, `upgrade-sdk`); full reference: [CLI scripts (`bin/`)](./bin-scripts.md)
- `swiss-parks-sdk/public/` — HTTP endpoints (`favorites.php`)
- `swiss-parks-sdk/config/` — `config.dist.php` + `config.php` (+ optional `config.local.php` for local overrides)
- `swiss-parks-sdk/writable/` — SQLite DB and logs

## Sync

- Commands:
  - `bash swiss-parks-sdk/bin/sync.sh` — incremental (`?since=` when `last_import` is set)
  - `bash swiss-parks-sdk/bin/sync.sh --force` — full import (no `?since=`)
  - `php swiss-parks-sdk/bin/sync.php`
  - `php swiss-parks-sdk/bin/sync.php --force`
- Exit code: `0` on success, `1` on import failure or exception (for cron monitoring).

Use `--force` when the local DB is incomplete or after a bad incremental run.

## SDK version upgrade

- File: `swiss-parks-sdk/bin/upgrade-sdk.sh`
- Command:
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest`
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh 22`
- Downloads the release ZIP from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases), updates SDK core files (preserves `config/config.php`, `custom/`, `writable/`), creates a backup, and runs `bin/migrate.php`.
- Exit code: `0` on success, `1` on failure.
- See [Upgrade](./upgrading.md) for the manual ZIP workflow and version-specific migration steps.

## Migration

- Commands:
  - `bash swiss-parks-sdk/bin/migrate.sh`
  - `php swiss-parks-sdk/bin/migrate.php`
- Deletes the SQLite database file, recreates it with the current schema, and runs a full import. There are no incremental schema migrations anymore.
- Exit code: `0` when import completes and the SDK is initialized, `1` otherwise.

## Scripts overview

| Script | Purpose |
| --- | --- |
| `bin/sync.sh` | Incremental cron import (`--force` for full re-import) |
| `bin/sync-smoke.sh` | Smoke check after sync (or `--quick` without sync) |
| `bin/migrate.sh` | Rebuild SQLite schema + full import after a manual core update |
| `bin/upgrade-sdk.sh` | Download release, update core files, run migrate |

Flags, exit codes, and environment variables: [CLI scripts (`bin/`)](./bin-scripts.md).

## Recommended usage

- Cron: `bin/sync.sh` (incremental).
- Incomplete data: `bin/sync.sh --force`.
- Health check: `bin/sync-smoke.sh` (or `--quick` if sync already ran).
- Schema / SDK upgrade: `bin/upgrade-sdk.sh` or `bin/migrate.sh` when core files are already updated.
- Favorites HTTP endpoint (optional): `public/favorites.php`.

## Related docs

- [CLI scripts (`bin/`)](./bin-scripts.md)
- [Guide index](./index.md)
- [New installation](./new-installation.md)
- [Upgrade](./upgrading.md)
- [Version migrations](./migrations/index.md)
