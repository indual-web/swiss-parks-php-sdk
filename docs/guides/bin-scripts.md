[Docs](../index.md) › CLI scripts

# CLI scripts (`bin/`)

All runnable tools under `swiss-parks-sdk/bin/`. Run them from the shell only — do not expose this directory over HTTP (`.htaccess` denies access; `index.html` is a 403 fallback). `sync.php` and `migrate.php` also refuse non-CLI requests with HTTP 403.

Prefer the `.sh` wrappers. The `.php` files are the actual entry points when you call PHP directly. Composer installations use `vendor/bin/swiss-parks-sdk` instead (see below).

When to use which script, cron and HTTP sync: [import and update lifecycle](./import-update-lifecycle.md).

## Overview

| Script | Purpose |
| --- | --- |
| `sync.sh` / `sync.php` | Import offers from the XML export (incremental or full); CLI only |
| `sync-smoke.sh` | Smoke check: optional sync, then assert the `offer` table is non-empty |
| `migrate.sh` / `migrate.php` | Delete SQLite DB, recreate schema, full import; CLI only |
| `upgrade-sdk.sh` | Download a release ZIP, update core files, run migrate |
| `swiss-parks-sdk` | Composer CLI entry (`vendor/bin/swiss-parks-sdk`) with the subcommands `migrate` and `sync` |

## `sync.sh` / `sync.php`

Imports offer data into the local SQLite database.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/sync.sh` | Incremental import (`?since=` when `last_import` is set) |
| `bash swiss-parks-sdk/bin/sync.sh --force` | Full import (no `?since=`); `force` is accepted as an alias |
| `php swiss-parks-sdk/bin/sync.php` | Same as `sync.sh` |
| `php swiss-parks-sdk/bin/sync.php --force` | Same as `sync.sh --force` |
| `php swiss-parks-sdk/bin/sync.php --config=<path>` | Use a project config file (sets `SWISS_PARKS_SDK_CONFIG`) |

- Exit code: `0` on success, `1` on import failure or exception (suitable for cron monitoring).
- Stdout: one plain-text line, e.g. `sync: ok (mode=incremental, imported=5, deleted=1, offers=120, map_layers=3, cleanup=0)` or `sync: fail (...)`.
- Typical use: scheduled cron for incremental updates; `--force` when the local DB is incomplete or after a bad incremental run.
- Requires a configured `api_hash` (and usually `park_id`) in `config/config.local.php` or the project config.

## `sync-smoke.sh`

Lightweight health check for sync and the local offer store. Useful after install, in CI, or before trusting cron.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/sync-smoke.sh` | Runs `sync.php`, then asserts `COUNT(*)` on `offer` is greater than `0` |
| `bash swiss-parks-sdk/bin/sync-smoke.sh --quick` | Skips sync; only asserts the offer count |

Exit behaviour:

- `0` and message `sync-smoke: ok (offers=N)` when the check passes.
- `0` and `sync-smoke: skip (...)` when PHP is missing, `api_hash` is empty, or the sync step fails (network/credentials). Skip is intentional so optional environments do not fail hard.
- `1` and `sync-smoke: fail (...)` when the SQLite file is missing or the `offer` table is empty after a successful sync path.

## `migrate.sh` / `migrate.php`

Rebuilds the local database from the current schema and runs a full import.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/migrate.sh` | Wrapper around `migrate.php` |
| `php swiss-parks-sdk/bin/migrate.php` | Deletes the SQLite file, recreates it from `database/schema.sql`, full import |
| `php swiss-parks-sdk/bin/migrate.php --if-outdated` | Migrates only when the database has no imported data yet or its stored version is lower than `SwissParksSdk::VERSION`; otherwise prints `migrate: skip (...)` and exits `0` |
| `php swiss-parks-sdk/bin/migrate.php --config=<path>` | Use a project config file (sets `SWISS_PARKS_SDK_CONFIG`) |

- There are no per-version migration SQL files; the schema source is `swiss-parks-sdk/database/schema.sql` for the current `SwissParksSdk::VERSION`.
- Exit code: `0` when import completes and the SDK is initialized (or the migration was skipped), `1` otherwise.
- Stdout: one plain-text line, e.g. `migrate: ok (sdk_version=22.0, imported=120, offers=120, map_layers=3)` or `migrate: fail (...)`.
- Use after a manual core-file update, or when `upgrade-sdk.sh` is not used. Prefer `upgrade-sdk.sh` when you also need to fetch and replace SDK files.

## `upgrade-sdk.sh`

Downloads a release from GitHub, replaces SDK core files, then runs `migrate.php`.

| Invocation | Behaviour |
| --- | --- |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh` | Same as `latest` |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest` | Latest GitHub release |
| `bash swiss-parks-sdk/bin/upgrade-sdk.sh 22.0` | Specific release version (e.g. `22.0`, `22.1`) |

What it does:

1. Stops if `config/config.local.php` is missing, because `config.php` is replaced in step 4.
2. Downloads the release ZIP (requires `curl`, `unzip`, `rsync`, `php`).
3. Copies the current SDK folder to a timestamped backup next to it (`swiss-parks-sdk-backup-…`).
4. Updates core paths (`autoload.php`, `classes/`, `database/`, `lang/`, `bin/`, `public/`, `templates/standard/`, `config/config.php`).
5. Leaves integrator data alone (`config/config.local.php`, `custom/`, own template folders, `writable/`).
6. Runs `bin/migrate.php` unless skipped.

Environment variables:

| Variable | Meaning |
| --- | --- |
| `SWISS_PARKS_SDK_GITHUB_REPO` | GitHub repo for the download (default `indual-web/swiss-parks-php-sdk`) |
| `SWISS_PARKS_SDK_SKIP_MIGRATE` | Set to `1` to update files only and skip `migrate.php` |

The former names `PARKS_API_GITHUB_REPO` and `PARKS_API_SKIP_MIGRATE` still work.

Exit code: `0` on success, `1` on download, extract, or migrate failure. On migrate failure the backup path is printed so you can restore manually.

## `swiss-parks-sdk` (Composer)

Composer links this script to `vendor/bin/swiss-parks-sdk`. It runs `migrate.php` or `sync.php` from the package and passes all options on.

| Invocation | Behaviour |
| --- | --- |
| `vendor/bin/swiss-parks-sdk migrate --config=config/parks.php` | Same as `migrate.php` with a project config |
| `vendor/bin/swiss-parks-sdk migrate --config=config/parks.php --if-outdated` | Migrates only when needed (for Composer install/update hooks) |
| `vendor/bin/swiss-parks-sdk sync --config=config/parks.php` | Incremental import (for cron) |
| `vendor/bin/swiss-parks-sdk sync --config=config/parks.php --force` | Full import |

- Relative `--config` paths resolve from the current directory.
- Unknown or missing subcommands print the usage and exit `1`.
- See [New installation](./new-installation.md#composer-installation) for the `composer.json` scripts.

## Related docs

- [Import and update lifecycle](./import-update-lifecycle.md)
- [Upgrade](./upgrading.md)
