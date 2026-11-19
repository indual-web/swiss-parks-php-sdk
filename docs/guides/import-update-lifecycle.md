[Docs](../index.md) › [Guides](./index.md) › Import and update lifecycle

# Import and update lifecycle

This guide documents sync, forced full import, SDK upgrades, and migration.

## Directory layout

- `swiss-parks-sdk/bin/` — CLI only (`sync`, `migrate`, `upgrade-sdk`)
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

## Publish a GitHub release

Releases are created from version tags. Pushing a tag runs `.github/workflows/release.yml`, which builds the ZIP via `deploy.sh` and publishes it as a GitHub Release asset.

- Preferred tags: `22`, `22.1` (optional `v` prefix is accepted)
- Asset name: `swiss-parks-php-sdk-<version>.zip`
- Local fallback (without CI): `bash ./deploy.sh 22`

```bash
git tag 22
git push origin 22
```

## SDK version upgrade

- File: `swiss-parks-sdk/bin/upgrade-sdk.sh`
- Command:
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh latest`
  - `bash swiss-parks-sdk/bin/upgrade-sdk.sh 22`
- Downloads the release from GitHub, updates SDK core files (preserves `config/config.php`, `custom/`, `writable/`), creates a backup, and runs `bin/migrate.php`.
- Exit code: `0` on success, `1` on failure.
- See [upgrade guide](./upgrading.md) for manual ZIP workflow and version-specific migration steps.

## Migration

- Commands:
  - `bash swiss-parks-sdk/bin/migrate.sh`
  - `php swiss-parks-sdk/bin/migrate.php`
- Deletes the SQLite database file, recreates it with the current schema, and runs a full import. There are no incremental schema migrations anymore.
- Exit code: `0` when import completes and the SDK is initialized, `1` otherwise.

## Who runs which script

| Audience | Script | Purpose |
| --- | --- | --- |
| Integrator | `bin/sync.sh` | Incremental cron import (`--force` for full re-import) |
| Integrator | `bin/migrate.sh` | Rebuild SQLite schema + full import after a manual core update |
| Integrator | `bin/upgrade-sdk.sh` | Download release, update core files, run migrate |
| Maintainer / Git checkout | `bin/sync-smoke.sh` | Local smoke test (`--quick` for a shorter run); not shipped for production cron |

## Recommended usage

- Cron: `bin/sync.sh` (incremental).
- Incomplete data: `bin/sync.sh --force`.
- Schema / SDK upgrade: `bin/upgrade-sdk.sh` or `bin/migrate.sh` when core files are already updated.
- Smoke check (Git checkout): `bin/sync-smoke.sh` or `bin/sync-smoke.sh --quick`.
- Favorites HTTP endpoint (optional): `public/favorites.php`.

## Related docs

- [Guide index](./index.md)
- [New installation](./new-installation.md)
- [Upgrade](./upgrading.md)
- [Version migrations](./migrations/index.md)
