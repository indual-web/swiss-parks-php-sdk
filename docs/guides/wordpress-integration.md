[Docs](../index.md) › [Guides](./index.md) › WordPress integration

# WordPress integration

This SDK is CMS-agnostic. There is **no official WordPress plugin** in this repository. Wrap the SDK in your own plugin (recommended) or theme.

Complete shared setup first: [new installation](./new-installation.md) and [configuration](./configuration.md). Place `swiss-parks-sdk/` inside your plugin directory (or another path your plugin can `require`).

Upgrading from v21: follow [v21 → v22](./migrations/21-to-22.md) (includes a short WordPress checklist).

The host must run **PHP 8.2–8.5** with `pdo_sqlite` (same as the rest of the SDK).

## Recommended configuration

Set these in `swiss-parks-sdk/config/config.php`:

| Key | Recommendation |
| --- | --- |
| `return_output` | `true` — shortcodes must **return** HTML, not echo |
| `soft_errors` | `true` — missing offers return `''`; bootstrap failures throw `RuntimeException` instead of `exit()` |
| `prevent_css_js_include` | `true` if you enqueue the **map** library yourself (see below). `false` injects `map.parks.swiss` CSS/JS into the HTML when a map/detail is rendered |
| `use_sessions` | `true` if you need filter persistence or favorites (`toggleFavorite` requires it) |
| `seo_urls` | Prefer `false` on WordPress (query params `?offer=` / `?page=`). Pretty SEO paths need rewrite rules |
| `url_param_prefix` / `form_prefix` | Unique prefixes (for example `sp_`) to avoid clashes with WordPress query vars |
| `db_path` / `log_directory` | Absolute paths under a writable location (for example under `wp-content/uploads/…`) |
| `favorites_script_path` | Public URL path to the folder that contains `favorites.php` (no trailing slash), when favorites are enabled |

Important:

- The SDK **never** auto-includes `parks.min.css` / `parks.min.js`. You always load those yourself (enqueue in the sketch below).
- `prevent_css_js_include` only controls the **interactive map** assets (`interactive_map_url` → `style.css` / `swiss-parks-map.umd.js`), not the v22 frontend bundle.

Example paths (adjust to your server):

```php
$config['db_path'] = '/var/www/html/wp-content/uploads/swiss-parks-sdk/park-offers.sqlite';
$config['log_directory'] = '/var/www/html/wp-content/uploads/swiss-parks-sdk/log/';
```

## Mini plugin sketch

Example layout: `wp-content/plugins/swiss-parks-offers/` containing `swiss-parks-offers.php` and the `swiss-parks-sdk/` folder. Adapt names and paths to your project.

This sketch assumes `seo_urls = false` (simplest). Pretty SEO URLs are covered later.

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

define('SWISS_PARKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWISS_PARKS_SDK_DIR', SWISS_PARKS_PLUGIN_DIR . 'swiss-parks-sdk/');

/**
 * Start the PHP session before WordPress prints HTML.
 * Add this only when use_sessions is true in config.php.
 * SDK autoload also calls session_start(), but only if headers are still
 * writable — a shortcode usually runs too late.
 */
add_action('init', function () {

	if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
		session_start();
	}
}, 1);

/**
 * Enqueue v22 frontend assets on pages that contain the shortcode.
 */
add_action('wp_enqueue_scripts', function () {

	if (! is_singular()) {
		return;
	}

	$post = get_post();
	if (! $post instanceof WP_Post || ! has_shortcode($post->post_content, 'swiss_parks_offers')) {
		return;
	}

	$css = 'https://hub.parks.swiss/dist/frontend/v22/css/parks.min.css';
	$js = 'https://hub.parks.swiss/dist/frontend/v22/parks.min.js';

	wp_enqueue_style('swiss-parks-sdk', $css, [], null);
	wp_enqueue_script('swiss-parks-sdk', $js, [], null, true);

	// Only if you use the map AND set prevent_css_js_include = true:
	// wp_enqueue_style('swiss-parks-map', 'https://map.parks.swiss/dist/css/style.css', [], null);
	// wp_enqueue_script('swiss-parks-map', 'https://map.parks.swiss/dist/js/swiss-parks-map.umd.js', [], null, true);
});

add_filter('script_loader_tag', function ($tag, $handle, $src) {

	if ($handle === 'swiss-parks-sdk' || $handle === 'swiss-parks-map') {
		return '<script type="module" src="' . esc_url($src) . '"></script>';
	}

	return $tag;
}, 10, 3);

/**
 * Shortcode: [swiss_parks_offers]
 * Put it on a dedicated offers page so filter POST → redirect (PRG) works.
 */
add_shortcode('swiss_parks_offers', function () {

	require_once SWISS_PARKS_SDK_DIR . 'autoload.php';

	$language = 'de'; // map from WPML/Polylang if needed — see output-and-language.md
	$pageUrl = get_permalink() ?: '/';

	try {
		// $pageUrl scopes the PHP session to this offers page (use_sessions)
		$sdk = new ParksSDK($language, '', $pageUrl);

		// Pin forms/links to the page path when not using SEO URLs.
		// Call this after construct: it affects HTML rendered below.
		// Filter POST redirects during bootstrap still use REQUEST_URI
		// (with pretty permalinks that is usually the same page path).
		if (empty($sdk->config['seo_urls'])) {
			$path = parse_url($pageUrl, PHP_URL_PATH);
			$sdk->setScriptUrl(is_string($path) && $path !== '' ? $path : '/');
		}

		if ($sdk->isOfferDetail()) {
			return $sdk->showOfferDetail();
		}

		// List only (no map). For list+map tabs, copy the markup from index.php
		// (ul.tab_list with tab_1 / tab_2) — parks.min.js expects that structure.
		$html = $sdk->showOffersFilter();
		$html .= $sdk->showOffersList();
		$html .= $sdk->showOffersPagination();

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

- Keep `return_output = true` so the shortcode can return HTML.
- Map container id is `swiss-parks-map` when you render a map.
- `has_shortcode()` does not see shortcodes inside some blocks/builders; enqueue on a known page ID if needed.
- For multilingual sites, set `$language` from WPML/Polylang; details in [output and language](./output-and-language.md).

## Sync and cron

Do **not** expose `bin/sync.php` over the public web (`bin/` is CLI only and returns 403 over HTTP).

Prefer system cron or WP-CLI calling the SDK CLI:

```bash
bash /path/to/wp-content/plugins/swiss-parks-offers/swiss-parks-sdk/bin/sync.sh
# full re-import:
bash /path/to/.../swiss-parks-sdk/bin/sync.sh --force
```

On shared hosting without SSH, use `public/sync.php` with `sync_secret` (URL cron). See [HTTP sync](./import-update-lifecycle.md#http-sync-shared-hosting).

See [import and update lifecycle](./import-update-lifecycle.md).

## Favorites and SEO

### Favorites

- Set `favorites_extension_available = true`, `use_sessions = true`, and `favorites_script_path` to the **public URL** of the folder that contains `favorites.php` (for example `/wp-content/plugins/swiss-parks-offers/swiss-parks-sdk/public`).
- The drop-in endpoint has no CSRF token. A custom WordPress AJAX/REST wrapper should use a nonce. See [favorites](./favorites.md).

### SEO URLs

Keep `seo_urls = false` unless you need pretty paths. With `false`, detail links look like `/offers/?offer=123` (plus optional `url_param_prefix`).

If you set `seo_urls = true`:

1. Register rewrite rules so paths like `/offers/offer-detail/…` still load the page that contains the shortcode (change `offers` to your page slug).
2. Do **not** call `setScriptUrl()` with only `/offers` — `isOfferDetail()` reads the detail segment from `scriptUrl` (normally `REQUEST_URI`).
3. Flush permalinks after adding rules: **Settings → Permalinks → Save**.

```php
add_action('init', function () {

	add_rewrite_rule(
		'^offers/offer-detail/(.+)/?',
		'index.php?pagename=offers',
		'top'
	);
	add_rewrite_rule(
		'^offers/page/([0-9]+)/?',
		'index.php?pagename=offers',
		'top'
	);
	add_rewrite_rule(
		'^offers/reset/?',
		'index.php?pagename=offers',
		'top'
	);
});
```

WordPress rewrites are internal; `$_SERVER['REQUEST_URI']` normally still contains the SEO path, which the SDK needs. Patterns: [SEO URLs](./seo-urls.md).

To redirect outdated detail URLs and answer unknown offers with a real 404, call `enforceOfferDetailCanonicalUrl()` with the permalink as `$pageBaseUrl`. The shortcode runs too late for headers, so use `template_redirect` (this bootstraps the SDK a second time per detail request). With `soft_errors = true` an unknown offer returns `false` and WordPress renders its own 404 template:

```php
add_action('template_redirect', function () {

	$post = get_queried_object();
	if (! $post instanceof WP_Post || ! has_shortcode($post->post_content, 'swiss_parks_offers')) {
		return;
	}

	require_once SWISS_PARKS_SDK_DIR . 'autoload.php';

	try {
		$pageUrl = get_permalink($post);
		$sdk = new ParksSDK('de', '', $pageUrl);
		if (! $sdk->enforceOfferDetailCanonicalUrl($pageUrl)) {
			global $wp_query;
			$wp_query->set_404();
			status_header(404);
			nocache_headers();
		}
	} catch (RuntimeException $e) {
		return;
	}
});
```

See [canonical detail URLs](./seo-urls.md#canonical-detail-urls).

## Checklist

- [ ] `api_hash` / `park_id` set; first `bin/sync.sh` succeeded
- [ ] Absolute writable `db_path` and `log_directory`
- [ ] `return_output` / `soft_errors` set as above
- [ ] `parks.min.css` / `parks.min.js` enqueued as `type="module"`
- [ ] Session started on `init` when `use_sessions` is true
- [ ] Shortcode on a dedicated offers page; filter submit works (PRG)
- [ ] Cron/WP-CLI sync scheduled, or `public/sync.php` with `sync_secret`; `bin/` not public
- [ ] Detail, list, and (if used) map/favorites work end-to-end

## Troubleshooting

### Empty offer list

- Confirm sync finished and the SQLite file has rows.
- Check `api_hash` / `park_id` and logs under `log_directory`.
- Re-run `bin/sync.sh --force`.

### Map tab stays empty

- Ensure `parks.min.js` is the v22 build and loaded as `type="module"`.
- Container id must be `swiss-parks-map` (not `mapContainer`).
- Overview tab markup should match `index.php` if you use list/map tabs.
- If `prevent_css_js_include = true`, enqueue the map library assets yourself.

### Filter or session state lost

- Set `use_sessions = true` and start the session on `init` (see sketch), before any HTML output.
- Pass a stable `$pageUrl` into `ParksSDK` for session scope.
- Use unique `url_param_prefix` / `form_prefix`.

### Detail page never opens (SEO mode)

- Do not overwrite `scriptUrl` with only the page permalink when `seo_urls` is true.
- Confirm rewrite rules load the offers page and `REQUEST_URI` still contains `offer-detail`.

### Permission / SQLite errors

- Web server user must write the directory of `db_path` (and create the `.sqlite` file).
- Prefer a path outside the public web root when possible; `uploads` is a common writable compromise.

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
