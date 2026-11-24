[Docs](../index.md) › SEO URLs

# SEO URLs

Enable SEO path routing when your site does not use query parameters for offer detail and pagination.

## Configuration

Set `seo_urls = true`. The path segments (`detail_slug`, `poi_slug`, `page_slug`, `reset_slug` below) come from the `seo_url_*` settings in [configuration](./configuration.md#seo-urls).

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

Your CMS or web server must forward these slug paths to the same PHP endpoint that bootstraps `SwissParksSdk`. The SDK detects detail/pagination from `scriptUrl` (usually `REQUEST_URI`).

For CMS embeds, pass `$pageUrl` into `new SwissParksSdk(...)` so the session is scoped to the offers page. Do **not** replace `scriptUrl` with only the clean page path when `seo_urls` is true — that breaks `isOfferDetail()` and SEO pagination. See [WordPress integration](./wordpress-integration.md).

## Canonical detail URLs

The SDK reads the offer ID only from the number at the end of the detail segment. Without further checks, one offer answers with `200` under many URLs (outdated title slug, `/page/3/` prefix, junk prefix, unknown ID). Call `enforceOfferDetailCanonicalUrl()` before any output to avoid duplicate content and soft 404s:

```php
$sdk = new SwissParksSdk('de');
$sdk->enforceOfferDetailCanonicalUrl('/offers');
```

Behaviour:

- Canonical path: base path + `/{detail_slug}/{title_slug}-{offer_id}` (+ `/{poi_slug}/{poi_id}` for POI URLs). Other paths get a `301` to it; query strings are ignored.
- Base path: the path of `$pageBaseUrl` when given (recommended in CMS embeds, so junk prefixes never become canonical). Without it, the current path minus `/{page_slug}/{n}` and everything from `/{detail_slug}/` on.
- Unknown offer IDs: `404` with a plain page and `exit`; with `soft_errors = true` only the `404` status and a `false` return value, so your CMS can render its 404 page.
- If headers were already sent, no redirect is attempted and the method returns `true`.
- The method only runs when you call it; existing integrations are unchanged.

## WordPress

Rewrite rules and the canonical redirect for WordPress: [WordPress integration](./wordpress-integration.md#seo-urls).

## Related docs

- [Configuration](./configuration.md#seo-urls)
- [WordPress integration](./wordpress-integration.md#seo-urls)
