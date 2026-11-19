[Docs](../index.md) › [Guides](./index.md) › Performance

# Performance

Practical tips for running the SDK on a park website.

## PHP and server

- Give import jobs enough `memory_limit` and `max_execution_time`.
- Prefer CLI cron (`bin/sync.sh`). CLI imports disable the time limit and raise `memory_limit` to `512M` automatically. On shared hosting without SSH, use token-protected `public/sync.php` (same limits are applied there); do not call `bin/sync.php` over HTTP.
- Very large exports may still need a higher `memory_limit` if sync runs out of memory.

## Import and database

- Prefer incremental cron (`bin/sync.sh`). Use `--force` only when the local database is incomplete or after a failed sync.
- Place `db_path` on fast local storage (avoid network filesystems for SQLite).
- While an import runs, the site can still read the database (WAL mode).
- After an SDK update, prefer `upgrade-sdk.sh` / `migrate.sh` over editing core files. Indexes and schema come with the shipped SDK.

## Operations

- Choose a cron interval that matches how often offers change on your site.
- Watch `log_directory` for import errors.

## Keep upgrades safe

Avoid changing core SDK files. Prefer:

- `config/config.php`
- `custom/*.php`
- `templates/<your-folder>/`

Upgrade workflow: [Upgrade](./upgrading.md). Version-specific steps: [migrations](./migrations/index.md).

## Related docs

- [Guide index](./index.md)
- [Documentation hub](../index.md)
- [Upgrade](./upgrading.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
