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

For CMS embeds, pass `$page_url` into `new ParksSDK(...)` so the session is scoped to the offers page. Do **not** replace `scriptUrl` with only the clean page path when `seo_urls` is true — that breaks `isOfferDetail()` and SEO pagination. See [WordPress integration](./wordpress-integration.md).

## WordPress

Default recommendation: keep `seo_urls = false` unless you need pretty paths.

If SEO mode is on, register rewrite rules so the SEO paths still load the page that contains the shortcode (adjust the `offers` slug to your page). Leave `REQUEST_URI` unchanged. Full sketch: [WordPress integration](./wordpress-integration.md#seo-urls).

After adding rules: **Settings → Permalinks → Save**.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
- [Favorites](./favorites.md)
- [WordPress integration](./wordpress-integration.md)
