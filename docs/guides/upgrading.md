[Docs](../index.md) › [Guides](./index.md) › Upgrade

# Upgrade

How to upgrade an existing SDK installation on a park website.

## Coming from v21?

Most park sites still on **v21** (`parks_api/`, MySQL) must follow the dedicated guide — not the short flow below:

**[Migration: v21 to v22](./migrations/21-to-22.md)**

That guide covers folder rename, config, camelCase API, frontend, cron, migrate, test, and MySQL cleanup.

## Already on v22?

Download releases from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases). Prefer the CLI script on the server (SSH), from inside `swiss-parks-sdk/`:

```bash
bash bin/upgrade-sdk.sh latest
# or a specific version:
bash bin/upgrade-sdk.sh 22.1
```

The script downloads the ZIP, backs up the current SDK folder, updates core files (keeps `config/config.php`, `custom/`, `writable/`), and runs `migrate.php`.

Manual alternative: replace core files from the ZIP yourself, then:

```bash
bash bin/migrate.sh
```

Then check filter, list, map, detail, console, and logs.

Do not edit core SDK files. Keep customizations in `config/config.php`, `custom/`, and your own template folder.

## Older than v21

ZIPs for v14–v21.1: [swiss-parks-php-sdk-legacy](https://github.com/indual-web/swiss-parks-php-sdk-legacy). Upgrade to **v21.1**, then follow [v21 → v22](./migrations/21-to-22.md).

## Related docs

- [v21 → v22](./migrations/21-to-22.md)
- [Migrations index](./migrations/index.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
- [Configuration](./configuration.md)
