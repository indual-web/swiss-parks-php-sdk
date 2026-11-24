[Docs](../index.md) › Troubleshooting

# Troubleshooting

Common problems and fixes. Check the log in `log_directory` first; import errors are written there.

## Import and database

| Problem | Fix |
| --- | --- |
| Empty offer list | Check `api_hash` and `park_id`, then run a full sync (`sync.sh --force` or `vendor/bin/swiss-parks-sdk sync --force`) and confirm rows in the `offer` table |
| SSL or cURL errors during sync | Confirm outbound HTTPS to `hub.parks.swiss`. Set `curl_verify_ssl = false` only as a last resort on broken hosting |
| Log shows `XML download failed` | The line contains the curl error code and HTTP status. Curl error `28` (timeout) means the hub sent no data for 10 minutes; retry the sync later |
| SQLite file or log cannot be written | Give the web server and the cron user write access to the directories of `db_path` and `log_directory`. In WordPress, a folder under `wp-content/uploads/` is a common choice |
| `The configuration file … does not exist or does not return an array.` | Check the path in `SWISS_PARKS_SDK_CONFIG` or `--config=`, and that the file ends with `return [ … ];` |

## Display

| Problem | Fix |
| --- | --- |
| Map tab stays empty | Load the v22 `parks.min.js` as `type="module"`, use the container id `swiss-parks-map` and the tab markup from `index.php` ([frontend assets](./quick-start-integration.md#frontend-assets)). With `prevent_css_js_include = true`, load the map library yourself |
| Styles or scripts return 404 | Use the URLs under `https://hub.parks.swiss/dist/frontend/v22/` ([frontend assets](./quick-start-integration.md#frontend-assets)) |
| Favorite links return 404 | Set `favorites_script_path` to the folder with `favorites.php`, or your wrapper or CMS endpoint ([favorites](./favorites.md)) |

## CMS embeds and WordPress

| Problem | Fix |
| --- | --- |
| White screen or `exit` on a missing offer | Set `soft_errors = true` and wrap the SDK in `try/catch` ([WordPress sketch](./wordpress-integration.md#mini-plugin-sketch)) |
| Filter or session state is lost | Set `use_sessions = true`, start the session before any output (WordPress: on `init`), pass a stable `$pageUrl` to the constructor and use unique `url_param_prefix` / `form_prefix` |
| Detail page never opens with SEO URLs | Do not call `setScriptUrl()` with only the page path when `seo_urls = true`; make sure the rewrite rules load the offers page and `REQUEST_URI` still contains the detail slug ([SEO URLs](./seo-urls.md)) |

## Upgrade from v21

| Problem | Fix |
| --- | --- |
| `Class "SwissParksSdk" not found` | Add `use SwissParks\Sdk\SwissParksSdk;` ([step 4](./migrations/21-to-22.md#4-update-your-php-code)) |
| `Call to undefined method …show_offers_list()` | Rename the methods to camelCase ([step 4](./migrations/21-to-22.md#4-update-your-php-code)) |
| Settings from the v21 `config.php` have no effect | v22 ignores that file; move the settings to `config/config.local.php` as an array ([step 2](./migrations/21-to-22.md#2-fix-config)) |
