[Docs](../index.md) › WordPress integration

# WordPress integration

This SDK is CMS-agnostic. There is **no official WordPress plugin** in this repository. Wrap the SDK in your own plugin (recommended) or theme.

Complete shared setup first: [new installation](./new-installation.md) and [configuration](./configuration.md). Place `swiss-parks-sdk/` inside your plugin directory (or another path your plugin can `require`), or install it with Composer (see [Composer and Bedrock](#composer-and-bedrock)).

Upgrading from v21: follow [v21 → v22](./migrations/21-to-22.md).

## Recommended configuration

Set these in `swiss-parks-sdk/config/config.local.php` (ZIP) or your project config (Composer):

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

`prevent_css_js_include` only controls the interactive map assets. `parks.min.css` / `parks.min.js` are never added by the SDK; the sketch below enqueues them ([frontend assets](./quick-start-integration.md#frontend-assets)).

Example paths (adjust to your server):

```php
<?php

return [
	'return_output' => true,
	'soft_errors' => true,
	'db_path' => '/var/www/html/wp-content/uploads/swiss-parks-sdk/park-offers.sqlite',
	'log_directory' => '/var/www/html/wp-content/uploads/swiss-parks-sdk/log/',
];
```

## Mini plugin sketch

Example layout: `wp-content/plugins/swiss-parks-offers/` containing `swiss-parks-offers.php` and, for a ZIP installation, the `swiss-parks-sdk/` folder. Adapt names and paths to your project.

This sketch assumes `seo_urls = false` (simplest). Pretty SEO URLs are covered later.

```php
<?php
/**
 * Plugin Name: Swiss Parks Offers
 * Description: Embeds the Swiss Parks PHP SDK (not an official parks.swiss plugin).
 * Version: 1.0.0
 */

use SwissParks\Sdk\SwissParksSdk;

if (! defined('ABSPATH')) {
	exit;
}

// ZIP installation: SDK folder inside the plugin
require_once __DIR__ . '/swiss-parks-sdk/autoload.php';

// Composer installation (instead of the line above): Bedrock loads vendor/autoload.php already,
// a plugin with its own composer.json requires it here. Then point to the project config:
// require_once __DIR__ . '/vendor/autoload.php';
// define('SWISS_PARKS_SDK_CONFIG', '/path/to/config/parks.php');

/**
 * Start the PHP session before WordPress prints HTML.
 * Add this only when use_sessions is true in your config.
 * The SwissParksSdk constructor also calls session_start(), but only if
 * headers are still writable — a shortcode usually runs too late.
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

	$language = 'de'; // map from WPML/Polylang if needed
	$pageUrl = get_permalink() ?: '/';

	try {
		// $pageUrl scopes the PHP session to this offers page (use_sessions)
		$sdk = new SwissParksSdk($language, '', $pageUrl);

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
- `has_shortcode()` does not see shortcodes inside some blocks/builders; enqueue on a known page ID if needed.
- For multilingual sites, set `$language` from WPML/Polylang ([languages](./configuration.md#languages)).

## Composer and Bedrock

Setups such as [Bedrock](https://roots.io/bedrock/) or a plugin with its own `composer.json` can install the SDK as a Composer package. Use the Composer lines in the sketch above and follow the [Composer installation](./new-installation.md#composer-installation): project config with absolute paths outside `vendor/`, Composer scripts for the migration, cron with `vendor/bin/swiss-parks-sdk sync` and [wrappers](./new-installation.md#public-endpoints) for favorites and HTTP sync.

Use only one copy of the SDK per site. If two plugins each bundle the SDK (ZIP or their own `vendor/`), the classes are loaded from whichever copy registers first, which may be an older version.

## Sync and cron

Run the sync from system cron or WP-CLI. Commands and HTTP sync for hosting without SSH: [import and update lifecycle](./import-update-lifecycle.md#cron).

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

	try {
		$pageUrl = get_permalink($post);
		$sdk = new SwissParksSdk('de', '', $pageUrl);
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

- [ ] `api_hash` / `park_id` set; first sync succeeded
- [ ] Absolute writable `db_path` and `log_directory`
- [ ] `return_output` / `soft_errors` set as above
- [ ] `parks.min.css` / `parks.min.js` enqueued as `type="module"`
- [ ] Session started on `init` when `use_sessions` is true
- [ ] Shortcode on a dedicated offers page; filter submit works (PRG)
- [ ] Sync scheduled with system cron, or HTTP sync with `sync_secret`
- [ ] Detail, list, and (if used) map/favorites work end-to-end

## Troubleshooting

See [CMS embeds and WordPress](./troubleshooting.md#cms-embeds-and-wordpress) and the other sections in [troubleshooting](./troubleshooting.md).
