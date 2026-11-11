# WordPress integration

This SDK is CMS-agnostic. There is **no official WordPress plugin** in this repository. Wrap the SDK in your own plugin (recommended) or theme.

Complete shared setup first: [new installation](./new-installation.md) and [configuration](./configuration.md). Place `swiss-parks-sdk/` inside your plugin directory (or another path your plugin can `require`).

## Recommended configuration

Set these in `swiss-parks-sdk/config/config.php` (or `config.local.php`):

| Key | Recommendation |
| --- | --- |
| `returnOutput` | `true` — rendering methods return HTML for shortcodes/templates |
| `soft_errors` | `true` — missing offers return `''`; bootstrap/language failures throw `RuntimeException` instead of `exit()` |
| `prevent_css_js_include` | `true` — enqueue CSS/JS from WordPress instead of SDK auto-includes |
| `use_sessions` | Enable only if you need SDK filter session persistence |
| `url_param_prefix` / `form_prefix` | Unique prefixes (for example `sp_`) to avoid clashes with WordPress query vars |
| `db_path` / `log_directory` | Absolute paths under a writable location (uploads or a private plugin data dir) |
| `favorites_script_path` | URL/path to your favorites endpoint folder (or CMS wrapper) when favorites are enabled |

## Mini plugin sketch

Example layout: `wp-content/plugins/swiss-parks-offers/` containing `swiss-parks-offers.php` and the `swiss-parks-sdk/` folder. Adapt names and paths to your project.

```php
<?php
/**
 * Plugin Name: Swiss Parks Offers
 * Description: Embeds the Swiss Parks PHP SDK (not an official parks.swiss plugin).
 * Version: 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

define('SPO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPO_SDK_DIR', SPO_PLUGIN_DIR . 'swiss-parks-sdk/');

/**
 * Enqueue SDK frontend assets (v22). Swap staging host for production before go-live.
 * See index.php in the SDK release for the current URLs.
 */
add_action('wp_enqueue_scripts', function () {

	if (! is_singular()) {
		return;
	}

	$css = 'https://staging-map.angebote.paerke.ch/dist/frontend/v22/css/parks.min.css';
	$js = 'https://staging-map.angebote.paerke.ch/dist/frontend/v22/parks.min.js';

	wp_enqueue_style('swiss-parks-sdk', $css, [], null);
	wp_enqueue_script('swiss-parks-sdk', $js, [], null, true);

	// parks.min.js is an ES module
	add_filter('script_loader_tag', function ($tag, $handle, $src) {

		if ($handle === 'swiss-parks-sdk') {
			return '<script type="module" src="' . esc_url($src) . '"></script>';
		}

		return $tag;
	}, 10, 3);
});

/**
 * Shortcode: [swiss_parks_offers]
 * Use on a dedicated offers page so filter POST → redirect (PRG) works.
 */
add_shortcode('swiss_parks_offers', function () {

	require_once SPO_SDK_DIR . 'autoload.php';

	$language = 'de'; // map from WPML/Polylang if needed — see output-and-language.md
	$page_url = get_permalink() ?: '/';

	try {
		$api = new ParksAPI($language, '', $page_url);
		$api->setScriptUrl(parse_url($page_url, PHP_URL_PATH) ?: '/');

		if ($api->isOfferDetail()) {
			return $api->showOfferDetail();
		}

		$html = $api->showOffersFilter();
		$html .= $api->showOffersList();
		$html .= $api->showOffersPagination();
		// Optional map: $html .= $api->showOffersMap();

		return $html;
	} catch (RuntimeException $e) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			return '<!-- Swiss Parks SDK: ' . esc_html($e->getMessage()) . ' -->';
		}

		return '';
	}
});
```

Notes:

- Keep `returnOutput = true` so shortcodes can return HTML.
- Overview markup for list/map tabs must match what `parks.min.js` expects (see SDK `index.php`). Map container id is `swiss-parks-map`.
- For multilingual sites, set `$language` from WPML/Polylang; details in [output and language](./output-and-language.md).

## Sync and cron

Do **not** expose `bin/sync.php` over the public web.

Prefer system cron or WP-CLI calling the SDK CLI:

```bash
bash /path/to/wp-content/plugins/swiss-parks-offers/swiss-parks-sdk/bin/sync.sh
# full re-import:
bash /path/to/.../swiss-parks-sdk/bin/sync.sh --force
```

See [import and update lifecycle](./import-update-lifecycle.md).

## Favorites and SEO

- Favorites: set `favorites_script_path` and wire `public/favorites.php` (or a WordPress AJAX/REST wrapper). See [favorites](./favorites.md).
- SEO URLs: if `seo_urls` is enabled, register rewrite rules / query vars in WordPress. See [SEO URLs](./seo-urls.md).

## Checklist

- [ ] `api_hash` / `park_id` set; first `bin/sync.sh` succeeded
- [ ] Absolute writable `db_path` and `log_directory`
- [ ] CMS config flags above applied
- [ ] Shortcode on a dedicated offers page; filter submit works (PRG)
- [ ] `v22` CSS/JS enqueued with `type="module"`; map id `swiss-parks-map` if map is used
- [ ] Cron/WP-CLI sync scheduled; not reachable anonymously
- [ ] Detail, list, and (if used) favorites work end-to-end

## Troubleshooting

### Empty offer list

- Confirm sync finished and the SQLite file has rows.
- Check `api_hash` / `park_id` and logs under `log_directory`.
- Re-run `bin/sync.sh --force`.

### Map tab stays empty

- Ensure `parks.min.js` is the v22 build and loaded as `type="module"`.
- Container id must be `swiss-parks-map` (not `mapContainer`).
- Overview tab markup should match `index.php`.

### Filter or session state lost

- Enable `use_sessions` only when needed; ensure headers are not sent before autoload.
- Pass a stable `$page_url` into `ParksAPI` and call `setScriptUrl()` for the offers permalink path.
- Use unique `url_param_prefix` / `form_prefix`.

### Permission / SQLite errors

- Web server user must write the directory of `db_path` (and create the `.sqlite` file).
- Prefer a path outside the public web root when possible.

### Fatal `exit` / white screen on missing offer

- Set `soft_errors = true` and wrap bootstrap in `try/catch` as in the sketch.

## Related docs

- [Quick start integration](./quick-start-integration.md)
- [Core configuration](./configuration.md)
- [Output mode and multilingual support](./output-and-language.md)
- [SEO URLs](./seo-urls.md)
- [Favorites](./favorites.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
- [v21 → v22 migration](./migrations/21-to-22.md)
