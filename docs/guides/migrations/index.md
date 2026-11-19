[Docs](../../index.md) › [Guides](../index.md) › Migrations

# Version migrations

Version-specific steps when an upgrade needs more than replacing core files and running `migrate.php`.

Use the generic [Upgrade](../upgrading.md) workflow for a normal update. Open a guide below when your current version is listed.

## Available migrations

| From | To | Guide | Breaking changes |
| --- | --- | --- | --- |
| v21 (MySQL) | v22 (SQLite) | [21 to 22](./21-to-22.md) | SQLite + folder layout, `config/config.php`, camelCase API, frontend `v22`, cron/favorites paths |

## Older packages (v14–v21.1)

Packages **before v22** (folder `parks_api/`) are frozen and live in a separate archive repository:

- [swiss-parks-php-sdk-legacy](https://github.com/indual-web/swiss-parks-php-sdk-legacy)

ZIPs for v14–v21.1 are under `releases/` there, with per-version notes under `docs/migrations/`. Prefer **v21.1**, then follow [21 to 22](./21-to-22.md).
