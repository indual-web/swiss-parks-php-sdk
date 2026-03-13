# Import and update lifecycle

This guide documents the regular update flow, forced updates, and migration command.

## Standard update

- File: `parks_api/scripts/cron.php`
- Command:
  - `php parks_api/scripts/cron.php`

## Forced update

- File: `parks_api/scripts/force_update.php`
- Command:
  - `php parks_api/scripts/force_update.php`

## Migration

- File: `parks_api/scripts/migrate.php`
- Command:
  - `php parks_api/scripts/migrate.php`

## Recommended usage

- Use `cron.php` for regular updates.
- Use `force_update.php` only when needed.
- Run `migrate.php` during version upgrades before forced import.

## Related docs

- [Guide index](./index.md)
- [New installation](./new-installation.md)
- [Upgrade guide](./upgrading.md)
