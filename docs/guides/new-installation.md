[Docs](../index.md) › New installation

# New installation

First-time setup of the SDK on a park website.

## ZIP or Composer

| | ZIP | Composer |
| --- | --- | --- |
| Suits | Any hosting, also without SSH | Projects that already use Composer (SSH/CLI needed) |
| SDK location | `swiss-parks-sdk/` in your project | `vendor/indual-web/swiss-parks-php-sdk/` |
| Your settings | `swiss-parks-sdk/config/config.local.php` | Own project config file, for example `config/parks.php` |
| Database, logs, templates, custom view | Inside `swiss-parks-sdk/` (default) | In your project, outside `vendor/` |
| CLI | `bash swiss-parks-sdk/bin/sync.sh`, `migrate.sh` | `vendor/bin/swiss-parks-sdk sync`, `migrate` |
| HTTP sync and favorites endpoints | Shipped in `swiss-parks-sdk/public/` | Small wrapper files in your public folder |
| Updates | `bin/upgrade-sdk.sh` | `composer update` |

## Prerequisites

- PHP `>= 8.2` and `<= 8.5`
- PHP extensions `pdo_sqlite` (SQLite `>= 3.25`), `curl`, `simplexml` and `mbstring`
- Outbound HTTPS to `hub.parks.swiss` (XML/JSON export)
- Write access for the web server and the cron user to the directories of `db_path` and `log_directory`
- `api_hash` and `park_id` from `https://hub.parks.swiss/en/settings`

The SQLite file and its schema are created automatically on the first import.

## ZIP installation

1. Download the latest `swiss-parks-php-sdk-<version>.zip` from [GitHub Releases](https://github.com/indual-web/swiss-parks-php-sdk/releases) and copy the `swiss-parks-sdk/` folder into your project.
2. Create `swiss-parks-sdk/config/config.local.php` with your values. Do not edit `config.php`: it holds the defaults and is replaced on every update. All keys: [configuration](./configuration.md).
   ```php
   <?php

   return [
   	'api_hash' => 'your-export-hash',
   	'park_id' => 1,
   ];
   ```
3. Run the first import:
   ```bash
   bash swiss-parks-sdk/bin/sync.sh
   ```
4. Add the SDK to your pages as shown in [quick start integration](./quick-start-integration.md) (complete example: `index.php` in the ZIP).
5. Schedule the sync, for example every 4 hours:
   ```cron
   0 */4 * * * bash /path/to/swiss-parks-sdk/bin/sync.sh >> /path/to/swiss-parks-sdk/writable/log/cron-sync.log 2>&1
   ```
   Without SSH, use a URL cron against `public/sync.php` instead ([HTTP sync](./import-update-lifecycle.md#http-sync-shared-hosting)).

## Composer installation

1. Require the package:
   ```bash
   composer require indual-web/swiss-parks-php-sdk:^22.0
   ```
2. Create a project config outside `vendor/`, for example `config/parks.php`. Use absolute paths for everything the SDK writes or reads from your project, because `vendor/` is replaced on every update. Read secrets from the environment so they stay out of version control:
   ```php
   <?php

   return [
   	'api_hash' => getenv('SWISS_PARKS_API_HASH') ?: '',
   	'park_id' => 1,
   	'db_path' => dirname(__DIR__) . '/var/swiss-parks/park-offers.sqlite',
   	'log_directory' => dirname(__DIR__) . '/var/swiss-parks/log/',
   ];
   ```
   Own templates and a custom view: [templating and custom view](./templating-and-custom-view.md).
3. Add the migration to the scripts in your project `composer.json`. It builds the database on the first install and after every SDK update; otherwise it skips:
   ```json
   "scripts": {
   	"post-install-cmd": "swiss-parks-sdk migrate --config=config/parks.php --if-outdated",
   	"post-update-cmd": "swiss-parks-sdk migrate --config=config/parks.php --if-outdated"
   }
   ```
   Then run `composer install` once more for the first import.
4. Load the Composer autoloader and the project config in your page code, then continue with [quick start integration](./quick-start-integration.md):
   ```php
   <?php

   use SwissParks\Sdk\SwissParksSdk;

   require __DIR__ . '/../vendor/autoload.php';

   define('SWISS_PARKS_SDK_CONFIG', __DIR__ . '/../config/parks.php');

   $sdk = new SwissParksSdk('de');
   ```
5. Schedule the sync with cron. It is separate from the Composer scripts:
   ```cron
   0 */4 * * * cd /path/to/project && vendor/bin/swiss-parks-sdk sync --config=config/parks.php >> var/swiss-parks/log/cron-sync.log 2>&1
   ```

### Public endpoints

`vendor/` is not public. If you need [HTTP sync](./import-update-lifecycle.md#http-sync-shared-hosting) or [favorites](./favorites.md), add a small wrapper in your public folder that loads your config and includes the shipped endpoint. Example `public/swiss-parks/sync.php`:

```php
<?php

require __DIR__ . '/../../vendor/autoload.php';

define('SWISS_PARKS_SDK_CONFIG', __DIR__ . '/../../config/parks.php');

require __DIR__ . '/../../vendor/indual-web/swiss-parks-php-sdk/swiss-parks-sdk/public/sync.php';
```

`favorites.php` works the same way: include `…/swiss-parks-sdk/public/favorites.php` and set `favorites_script_path` to the public folder of the wrapper (here `/swiss-parks`).

## Verification

- The SQLite file exists at `db_path` and the `offer` table has rows (`bash swiss-parks-sdk/bin/sync-smoke.sh --quick` checks this for ZIP installations).
- Filter, list and detail pages render.
- The map loads without JavaScript errors.
- The log in `log_directory` shows no import errors.

Problems: [troubleshooting](./troubleshooting.md).
