[Docs](../index.md) › [Guides](./index.md) › SEO URLs

# SEO URLs

Enable SEO path routing when your site does not use query parameters for offer detail and pagination.

## Configuration

- `seo_urls` (default: `false`) — set to `true` to enable.
- Slug defaults (override in `config.php` if needed):
  - `seo_url_detail_slug` — `offer-detail`
  - `seo_url_poi_slug` — `poi`
  - `seo_url_page_slug` — `page`
  - `seo_url_reset_slug` — `reset`
- `url_param_prefix` — when SEO mode is off (or for some internal params), query keys are `{prefix}offer` and `{prefix}page`.

## URL patterns

With `scriptUrl` as the offers page path (for example `/offers/`):

| Purpose | Pattern |
| --- | --- |
| Offer detail | `{scriptUrl}/{detail_slug}/{title_slug}-{offer_id}` |
| POI on detail | `{scriptUrl}/{detail_slug}/{title_slug}-{offer_id}/{poi_slug}/{poi_id}` |
| Pagination | `{scriptUrl}/{page_slug}/{n}` |
| Reset filter | path contains `/{reset_slug}` |

Examples with defaults:

- `/offers/offer-detail/bergwanderung-123`
- `/offers/offer-detail/bergwanderung-123/poi/456`
- `/offers/page/2`

Without SEO URLs, detail and page use query parameters such as `?offer=123` or `?page=2` (with optional `url_param_prefix`).

## Routing requirements

Your CMS or web server must forward these slug paths to the same PHP endpoint that bootstraps `ParksAPI`. The SDK parses `REQUEST_URI` / `scriptUrl` for the configured slugs and offer IDs.

Also set a stable page URL for CMS embeds: pass `$page_url` into `new ParksAPI(...)` and call `setScriptUrl()` with the offers permalink path. See [ParksAPI methods](../sdk-reference/parks-api.md).

## WordPress rewrite sketch

Register rules that map SEO paths onto a dedicated offers page (adjust page slug and query vars to your setup). Flush rewrites after changing rules.

```php
add_action('init', function () {

	// Detail: /offers/offer-detail/{slug}-{id}
	add_rewrite_rule(
		'^offers/offer-detail/([^/]+)/?',
		'index.php?pagename=offers&sp_seo_detail=$matches[1]',
		'top'
	);

	// Pagination: /offers/page/{n}
	add_rewrite_rule(
		'^offers/page/([0-9]+)/?',
		'index.php?pagename=offers&sp_seo_page=$matches[1]',
		'top'
	);
});

add_filter('query_vars', function ($vars) {

	$vars[] = 'sp_seo_detail';
	$vars[] = 'sp_seo_page';

	return $vars;
});
```

The shortcode or template still bootstraps `ParksAPI` on that page; the SDK reads the path from `REQUEST_URI` / `setScriptUrl()`. You do not need to map `sp_seo_*` into SDK APIs unless you build a custom router. Prefer letting WordPress serve the same page for `/offers/...` so `REQUEST_URI` keeps the SEO path.

After adding rules: visit **Settings → Permalinks** and save (or `flush_rewrite_rules()` once).

More WordPress context: [WordPress integration](./wordpress-integration.md).

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
- [Favorites](./favorites.md)
- [WordPress integration](./wordpress-integration.md)
