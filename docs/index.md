# Swiss Parks PHP SDK

Documentation for developers who integrate the SDK into a park website. It covers install, configuration, rendering, sync, and upgrades on your server — not SDK release or maintenance workflows.

The PHP SDK imports offer data from the Swiss Parks XML export into a local SQLite database file and renders filter, list, map, and detail views for server-side PHP integrations. No database server is required.

## Start

Shared for every integration:

- [New installation](./guides/new-installation.md) (first v22 install)
- [Core configuration](./guides/configuration.md)
- [Upgrade](./guides/upgrading.md)

Coming from v21: [v21 → v22](./guides/migrations/21-to-22.md)

Then choose a path below.

## PHP integration

- [Quick start integration](./guides/quick-start-integration.md)
- [Templating and custom view](./guides/templating-and-custom-view.md)
- [Output mode and multilingual support](./guides/output-and-language.md)
- [SEO URLs](./guides/seo-urls.md)
- [Favorites](./guides/favorites.md)

## WordPress

There is no official WordPress plugin. Wrap the SDK in your own plugin or theme:

- [WordPress integration](./guides/wordpress-integration.md) (config, mini-plugin sketch, enqueue, cron, troubleshooting)

Related: [configuration](./guides/configuration.md), [SEO URLs](./guides/seo-urls.md), [favorites](./guides/favorites.md), [output and language](./guides/output-and-language.md)

## Upgrade

- From v21: [v21 → v22](./guides/migrations/21-to-22.md)
- Already on v22: [Upgrade](./guides/upgrading.md)
- Older packages (v14–v21.1): [migrations index](./guides/migrations/index.md) · [legacy archive](https://github.com/indual-web/swiss-parks-php-sdk-legacy)
- Sync/cron: [import and update lifecycle](./guides/import-update-lifecycle.md)

## Reference

- [SDK reference index](./sdk-reference/index.md)
- [ParksSDK methods](./sdk-reference/parks-sdk.md)
- [Filter options](./sdk-reference/filter-options.md)
- [Map options](./sdk-reference/map-options.md)

## Operations

- [Import and update lifecycle](./guides/import-update-lifecycle.md) (`bin/sync.sh`, migrate, cron)
- [Performance](./guides/performance-and-upgrade-strategy.md)

## All guides

Full file list: [guides index](./guides/index.md)

## Contact

[Network of Swiss Parks](https://www.parks.swiss), [info@parks.swiss](mailto:info@parks.swiss)
