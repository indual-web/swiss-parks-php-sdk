# SEO URLs

If your environment uses SEO routing:

- `seo_urls = true`
- define slugs:
  - `seo_url_detail_slug`
  - `seo_url_poi_slug`
  - `seo_url_page_slug`
  - `seo_url_reset_slug`

Routing notes:

- Detail links are parsed via the configured detail slug and offer IDs in the URL path.
- Pagination/reset handling depends on the configured page/reset slugs.
- Ensure your CMS/router forwards these slug paths to the SDK integration endpoint.

## Related docs

- [Guide index](./index.md)
- [Favorites](./favorites.md)
