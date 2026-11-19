[Docs](../index.md) › [Guides](./index.md) › WordPress integration

# WordPress integration

This SDK is CMS-agnostic. There is **no official WordPress plugin** in this repository. Wrap the SDK in your own plugin (recommended) or theme.

Complete shared setup first: [new installation](./new-installation.md) and [configuration](./configuration.md). Place `swiss-parks-sdk/` inside your plugin directory (or another path your plugin can `require`).

Upgrading from v21: follow [v21 → v22](./migrations/21-to-22.md) (includes a short WordPress checklist).

The host must run **PHP 8.2–8.5** with `pdo_sqlite` (same as the rest of the SDK).

## Recommended configuration

Set these in `swiss-parks-sdk/config/config.php` (or `config.local.php`):

| Key | Recommendation |
| --- | --- |
| `return_output` | `true` — shortcodes must **return** HTML, not echo |
| `soft_errors` | `true` — missing offers return `''`; bootstrap failures throw `RuntimeException` instead of `exit()` |
| `prevent_css_js_include` | `true` — load CSS/JS via `wp_enqueue_*` instead of SDK auto-includes |
| `use_sessions` | `true` if you need filter persistence or favorites (`toggleFavorite` requires it) |
| `url_param_prefix` / `form_prefix` | Unique prefixes (for example `sp_`) to avoid clashes with WordPress query vars |
| `db_path` / `log_directory` | Absolute paths under a writable location (for example under `wp-content/uploads/…`) |
| `favorites_script_path` | URL/path to your favorites endpoint folder (or CMS wrapper) when favorites are enabled |

Example paths (adjust to your server):

```php
$config['db_path'] = '/var/www/html/wp-content/uploads/swiss-parks-sdk/park-offers.sqlite';
$config['log_directory'] = '/var/www/html/wp-content/uploads/swiss-parks-sdk/log/';
```

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
 * Start the PHP session before WordPress prints HTML.
 * Autoload only starts a session if headers are still writable; a shortcode
 * runs too late for that. Required when use_sessions is true.
 */
add_action('init', function () {

	if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
		session_start();
	}
}, 1);

/**
 * Enqueue v22 assets only on pages that contain the shortcode.
 * Swap the staging host for production before go-live (see index.php).
 */
add_action('wp_enqueue_scripts', function () {

	if (! is_singular()) {
		return;
	}

	$post = get_post();
	if (! $post instanceof WP_Post || ! has_shortcode($post->post_content, 'swiss_parks_offers')) {
		return;
	}

	$css = 'https://staging-map.angebote.paerke.ch/dist/frontend/v22/css/parks.min.css';
	$js = 'https://staging-map.angebote.paerke.ch/dist/frontend/v22/parks.min.js';

	wp_enqueue_style('swiss-parks-sdk', $css, [], null);

	// Classic enqueue + type=module (works on current WordPress versions).
	// On WordPress 6.5+ you may use wp_enqueue_script_module() instead.
	wp_enqueue_script('swiss-parks-sdk', $js, [], null, true);
});

add_filter('script_loader_tag', function ($tag, $handle, $src) {

	if ($handle === 'swiss-parks-sdk') {
		return '<script type="module" src="' . esc_url($src) . '"></script>';
	}

	return $tag;
}, 10, 3);

/**
 * Shortcode: [swiss_parks_offers]
 * Put it on a dedicated offers page so filter POST → redirect (PRG) works.
 */
add_shortcode('swiss_parks_offers', function () {

	require_once SPO_SDK_DIR . 'autoload.php';

	$language = 'de'; // map from WPML/Polylang if needed — see output-and-language.md
	$page_url = get_permalink() ?: '/';

	try {
		// $page_url scopes the PHP session to this offers page
		$api = new ParksAPI($language, '', $page_url);

		// Do not call setScriptUrl() with only the page path when seo_urls is true:
		// isOfferDetail() and SEO pagination read the detail/page segments from
		// scriptUrl (normally REQUEST_URI). Overwriting with /offers/ breaks that.
		// Without SEO URLs, pin forms/links to the page permalink path:
		if (empty($api->config['seo_urls'])) {
			$path = parse_url($page_url, PHP_URL_PATH);
			$api->setScriptUrl(is_string($path) && $path !== '' ? $path : '/');
		}

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

- Keep `return_output = true` so the shortcode can return HTML.
- Overview markup for list/map tabs must match what `parks.min.js` expects (see SDK `index.php`). Map container id is `swiss-parks-map`.
- `has_shortcode()` does not see shortcodes inside some blocks/builders; enqueue on a known page ID if needed.
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

### Favorites

- Set `favorites_extension_available = true`, `favorites_script_path`, and `use_sessions = true` (`toggleFavorite` does nothing without sessions).
- Wire `public/favorites.php` or a WordPress AJAX/REST wrapper that calls `ParksAPI::toggleFavorite()`. Custom endpoints should use a WordPress nonce; the drop-in `favorites.php` has no CSRF token. See [favorites](./favorites.md).

### SEO URLs

Default is `seo_urls = false` (query params `?offer=` / `?page=`). That is the simplest WordPress setup.

If you enable `seo_urls`, WordPress must route paths like `/offers/offer-detail/…` and `/offers/page/…` to the same page that contains the shortcode, while leaving `REQUEST_URI` as the SEO path. Example (page slug `offers` — change to match your page):

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

After adding or changing rules: **Settings → Permalinks → Save** (flushes rewrites). Do not call `setScriptUrl()` with only `/offers` when SEO mode is on (see sketch above). Patterns: [SEO URLs](./seo-urls.md).

## Checklist

- [ ] `api_hash` / `park_id` set; first `bin/sync.sh` succeeded
- [ ] Absolute writable `db_path` and `log_directory`
- [ ] CMS config flags above applied
- [ ] Session started on `init` when `use_sessions` is true
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

- Set `use_sessions = true` and start the session on `init` (see sketch), before any HTML output.
- Pass a stable `$page_url` into `ParksAPI` for session scope.
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
