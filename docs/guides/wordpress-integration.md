# WordPress integration

This SDK is CMS-agnostic. There is no official WordPress plugin in this repository. WordPress developers should wrap the SDK in their own plugin or theme integration.

## Recommended configuration

Set these in `swiss-parks-sdk/config/config.php` (or `config.local.php`):

- `returnOutput`
  - Set to `true` so rendering methods return HTML for shortcodes/templates.
- `soft_errors`
  - Set to `true` in CMS embeds. Missing offers return an empty string; bootstrap/language failures throw `RuntimeException` instead of `exit()`.
- `prevent_css_js_include`
  - Set to `true` and enqueue CSS/JS from the WordPress plugin/theme.
- `use_sessions`
  - Enable only if you need SDK filter session persistence. Autoload starts a PHP session only when this is `true` and headers are not sent yet.
- `url_param_prefix` / `form_prefix`
  - Set unique prefixes (for example `sp_`) to avoid clashes with WordPress query vars.
- `db_path` / `log_directory`
  - Prefer absolute paths under a writable location (for example uploads or a private plugin data dir).

## Minimal bootstrap

```php
<?php
require_once WP_PLUGIN_DIR . '/your-plugin/swiss-parks-sdk/autoload.php';

$language = 'de'; // map from WPML/Polylang if needed
$page_url = get_permalink(); // session scope only

$api = new ParksAPI($language, '', $page_url);
$api->setScriptUrl(parse_url($page_url, PHP_URL_PATH) ?: '/');

if ($api->isOfferDetail()) {
    echo $api->showOfferDetail();
} else {
    echo $api->showOffersFilter();
    echo $api->showOffersList();
    echo $api->showOffersPagination();
}
```

Wrap bootstrap in `try/catch` when `soft_errors` is enabled.

## Plugin responsibilities

The WordPress layer should own:

- Shortcodes or page templates
- `wp_enqueue_style` / `wp_enqueue_script` for `parks.min.css` / `parks.min.js` (and map assets if needed)
- Cron or WP-CLI calling `bin/sync.sh` / `bin/sync.php` (do not expose sync publicly)
- SEO rewrite rules / query vars when `seo_urls` is enabled
- Favorites endpoint wiring via `favorites_script_path`
- A dedicated offers page for filter POST → redirect (PRG). The SDK does not soft-disable that redirect.

## Related docs

- [Quick start integration](./quick-start-integration.md)
- [Core configuration](./configuration.md)
- [Output mode and multilingual support](./output-and-language.md)
- [SEO URLs](./seo-urls.md)
- [Favorites](./favorites.md)
