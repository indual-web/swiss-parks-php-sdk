[Docs](../index.md) › Upgrade

# Upgrade

How to update an existing v22 installation. Coming from v21 or older: [v21 → v22](./migrations/21-to-22.md), no versions in between are needed.

## Versions

Releases are tagged `vX.Y` (for example `v22.0`, `v22.1`). Every release, minor ones included, raises `SwissParksSdk::VERSION` and needs one migration: the SQLite database is rebuilt and fully imported. The upgrade paths below run it automatically.

Keep all customizations outside the SDK core so updates never overwrite them:

- Settings in `config/config.local.php` (ZIP) or your project config (Composer)
- Custom view classes in `custom/` (ZIP) or your project (Composer)
- Templates in an own folder ([templating](./templating-and-custom-view.md))

## ZIP installation

Run the upgrade script on the server, from inside `swiss-parks-sdk/`:

```bash
bash bin/upgrade-sdk.sh latest
# or a specific version:
bash bin/upgrade-sdk.sh 22.1
```

It backs up the SDK folder, replaces the core files including `config/config.php`, keeps your settings, `custom/`, own templates and `writable/`, and runs the migration. Details: [`upgrade-sdk.sh`](./bin-scripts.md#upgrade-sdksh).

Without the script: replace the core files from the [release ZIP](https://github.com/indual-web/swiss-parks-php-sdk/releases) yourself (`autoload.php`, `classes/`, `database/`, `lang/`, `bin/`, `public/`, `templates/standard/`, `config/config.php`), then run `bash bin/migrate.sh`.

## Composer installation

```bash
composer update indual-web/swiss-parks-php-sdk
```

The `post-update-cmd` script from the [Composer installation](./new-installation.md#composer-installation) runs `migrate --if-outdated`: the database is rebuilt once after the update, later runs skip. Use `^22.0` as version constraint to receive all v22 releases.

## After the upgrade

Check filter, list, map and detail pages, the browser console and the log.

## Older packages

Packages before v22 (v14–v21.1, folder `parks_api/`) are archived for reference only in [swiss-parks-php-sdk-legacy](https://github.com/indual-web/swiss-parks-php-sdk-legacy). To reach v22, skip them and follow [v21 → v22](./migrations/21-to-22.md).
