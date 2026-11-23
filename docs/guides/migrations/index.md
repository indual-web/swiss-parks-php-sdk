[Docs](../../index.md) › [Guides](../index.md) › Migrations

# Version migrations

Version-specific steps when an upgrade needs more than replacing core files and running `migrate.php`.

From v21 or older: jump directly to v22 with [21 to 22](./21-to-22.md). Later updates within v22: [Upgrade](../upgrading.md).

## Available migrations

| From | To | Guide | Breaking changes |
| --- | --- | --- | --- |
| v21 or older (MySQL) | v22 (SQLite) | [21 to 22](./21-to-22.md) | SQLite + folder layout, `config/config.php`, camelCase API, frontend `v22`, cron/favorites paths |

## Older packages (v14–v21.1)

Packages **before v22** (folder `parks_api/`) are frozen and live in a separate archive repository:

- [swiss-parks-php-sdk-legacy](https://github.com/indual-web/swiss-parks-php-sdk-legacy)

ZIPs for v14–v21.1 are under `releases/` there, with per-version notes under `docs/migrations/`. They are archive only: to reach v22, skip them and follow [21 to 22](./21-to-22.md) directly.
