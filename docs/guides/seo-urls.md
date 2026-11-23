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

Without SEO URLs, detail and page use query parameters such as `?offer=123` or `?page=2` (with optional `url_param_prefix`). That mode is simpler for many CMS embeds.

## Routing requirements

Your CMS or web server must forward these slug paths to the same PHP endpoint that bootstraps `ParksSDK`. The SDK detects detail/pagination from `scriptUrl` (usually `REQUEST_URI`).

For CMS embeds, pass `$pageUrl` into `new ParksSDK(...)` so the session is scoped to the offers page. Do **not** replace `scriptUrl` with only the clean page path when `seo_urls` is true — that breaks `isOfferDetail()` and SEO pagination. See [WordPress integration](./wordpress-integration.md).

## Canonical detail URLs

The SDK reads the offer ID only from the number at the end of the detail segment. Without further checks, one offer answers with `200` under many URLs (outdated title slug, `/page/3/` prefix, junk prefix, unknown ID). Call `enforceOfferDetailCanonicalUrl()` before any output to avoid duplicate content and soft 404s:

```php
$sdk = new ParksSDK('de');
$sdk->enforceOfferDetailCanonicalUrl('/offers');
```

Behaviour:

- Canonical path: base path + `/{detail_slug}/{title_slug}-{offer_id}` (+ `/{poi_slug}/{poi_id}` for POI URLs). Other paths get a `301` to it; query strings are ignored.
- Base path: the path of `$pageBaseUrl` when given (recommended in CMS embeds, so junk prefixes never become canonical). Without it, the current path minus `/{page_slug}/{n}` and everything from `/{detail_slug}/` on.
- Unknown offer IDs: `404` with a plain page and `exit`; with `soft_errors = true` only the `404` status and a `false` return value, so your CMS can render its 404 page.
- If headers were already sent, no redirect is attempted and the method returns `true`.
- The method only runs when you call it; existing integrations are unchanged.

## WordPress

Default recommendation: keep `seo_urls = false` unless you need pretty paths.

If SEO mode is on, register rewrite rules so the SEO paths still load the page that contains the shortcode (adjust the `offers` slug to your page). Leave `REQUEST_URI` unchanged. Full sketch: [WordPress integration](./wordpress-integration.md#seo-urls).

After adding rules: **Settings → Permalinks → Save**.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
- [Favorites](./favorites.md)
- [WordPress integration](./wordpress-integration.md)
