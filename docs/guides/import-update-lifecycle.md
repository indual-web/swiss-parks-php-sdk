# Import and update lifecycle

This guide documents the regular update flow, forced updates, and migration command.

## Standard update

- File: `parks_api/scripts/cron.php`
- Command:
  - `php parks_api/scripts/cron.php`
- Exit code: `0` on success, `1` on import failure or exception (for cron monitoring).

## Forced update

- File: `parks_api/scripts/force_update.php`
- Command:
  - `php parks_api/scripts/force_update.php`
- Exit code: `0` on success, `1` on failure.

## Migration

- File: `parks_api/scripts/migrate.php`
- Command:
  - `php parks_api/scripts/migrate.php`
- Deletes the SQLite database file, recreates it with the current schema, and runs a full import. There are no incremental schema migrations anymore.
- Exit code: `0` when import completes and the API is initialized, `1` otherwise.

## Recommended usage

- Use `cron.php` for regular updates.
- Use `force_update.php` only when needed.
- Run `migrate.php` during version upgrades; it already includes a full import, so no separate forced import is required afterwards.

## Related docs

- [Guide index](./index.md)
- [New installation](./new-installation.md)
- [Upgrade guide](./upgrading.md)
- [Version migrations](./migrations/README.md)
