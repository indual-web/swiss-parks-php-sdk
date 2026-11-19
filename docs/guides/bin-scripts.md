[Docs](../index.md) › [Guides](./index.md) › CLI scripts (`bin/`)

# CLI scripts (`bin/`)

All runnable tools under `swiss-parks-sdk/bin/`. Run them from the shell only — do not expose this directory over HTTP (`.htaccess` denies access; `index.html` is a 403 fallback).

Prefer the `.sh` wrappers. The `.php` files are the actual entry points when you call PHP directly.

Workflow context: [Import and update lifecycle](./import-update-lifecycle.md).

## Overview

| Script | Purpose |
| --- | --- |
| `sync.sh` / `sync.php` | Import offers from the XML export (incremental or full) |
| `sync-smoke.sh` | Smoke check: optional sync, then assert the `offer` table is non-empty |
| `migrate.sh` / `migrate.php` | Delete SQLite DB, recreate schema, full import |
| `upgrade-sdk.sh` | Download a release ZIP, update core files, run migrate |

## `sync.sh` / `sync.php`

Imports offer data into the local SQLite database.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/sync.sh` | Incremental import (`?since=` when `last_import` is set) |
| `bash swiss-parks-sdk/bin/sync.sh --force` | Full import (no `?since=`); `force` is accepted as an alias |
| `php swiss-parks-sdk/bin/sync.php` | Same as `sync.sh` |
| `php swiss-parks-sdk/bin/sync.php --force` | Same as `sync.sh --force` |

- Exit code: `0` on success, `1` on import failure or exception (suitable for cron monitoring).
- Stdout: one plain-text line, e.g. `sync: ok (mode=incremental, imported=5, deleted=1, offers=120, map_layers=3, cleanup=0)` or `sync: fail (...)`.
- Typical use: scheduled cron for incremental updates; `--force` when the local DB is incomplete or after a bad incremental run.
- Requires a configured `api_hash` (and usually `park_id`) in `config/config.php`.

## `sync-smoke.sh`

Lightweight health check for sync and the local offer store. Useful after install, in CI, or before trusting cron.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/sync-smoke.sh` | Runs `sync.php`, then asserts `COUNT(*)` on `offer` is greater than `0` |
| `bash swiss-parks-sdk/bin/sync-smoke.sh --quick` | Skips sync; only asserts the offer count |

Exit behaviour:

- `0` and message `sync-smoke: ok (offers=N)` when the check passes.
- `0` and `sync-smoke: skip (...)` when PHP is missing, `api_hash` is empty/placeholder (`insert-your-hash-here`), or the sync step fails (network/credentials). Skip is intentional so optional environments do not fail hard.
- `1` and `sync-smoke: fail (...)` when the SQLite file is missing or the `offer` table is empty after a successful sync path.

## `migrate.sh` / `migrate.php`

Rebuilds the local database from the current schema and runs a full import.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/migrate.sh` | Wrapper around `migrate.php` |
| `php swiss-parks-sdk/bin/migrate.php` | Deletes the SQLite file, recreates it from `database/schema.sql`, full import |

- There are no per-version migration SQL files; the schema source is `swiss-parks-sdk/database/schema.sql` for the current `SDK_VERSION`.
- Exit code: `0` when import completes and the SDK is initialized, `1` otherwise.
- Use after a manual core-file update, or when `upgrade-sdk.sh` is not used. Prefer `upgrade-sdk.sh` when you also need to fetch and replace SDK files.

## `upgrade-sdk.sh`

Downloads a release from GitHub, replaces SDK core files, then runs `migrate.php`.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh` | Same as `latest` |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest` | Latest GitHub release |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh 22` | Specific release version (e.g. `22`, `22.1`) |

What it does:

1. Downloads the release ZIP (requires `curl`, `unzip`, `rsync`, `php`).
2. Copies the current SDK folder to a timestamped backup next to it (`swiss-parks-sdk-backup-…`).
3. Updates core paths (`autoload.php`, `classes/`, `database/`, `lang/`, `bin/`, `public/`, shipped `templates/`, `config/config.dist.php`).
4. Leaves integrator data alone (`config/config.php`, `custom/`, `writable/`).
5. Runs `bin/migrate.php` unless skipped.

Environment variables:

| Variable | Meaning |
| --- | --- |
| `PARKS_API_GITHUB_REPO` | GitHub repo for the download (default `indual-web/swiss-parks-php-sdk`) |
| `PARKS_API_SKIP_MIGRATE` | Set to `1` to update files only and skip `migrate.php` |

Exit code: `0` on success, `1` on download, extract, or migrate failure. On migrate failure the backup path is printed so you can restore manually.

See also: [Upgrade](./upgrading.md), [Version migrations](./migrations/index.md).

## Related docs

- [Import and update lifecycle](./import-update-lifecycle.md)
- [New installation](./new-installation.md)
- [Upgrade](./upgrading.md)
- [Guide index](./index.md)
