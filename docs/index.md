# Swiss Parks PHP SDK

The PHP SDK imports offer data from the Swiss Parks XML export into a local SQLite database file and renders filter, list, map, and detail views for server-side PHP integrations. No database server is required.

## Start

Shared for every integration:

- [New installation](./guides/new-installation.md)
- [Core configuration](./guides/configuration.md)

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

- [Upgrade guide](./guides/upgrading.md)
- [v21 → v22 (MySQL to SQLite)](./guides/migrations/21-to-22.md)
- [Version migrations](./guides/migrations/index.md)
- [Import and update lifecycle](./guides/import-update-lifecycle.md)

## Reference

- [SDK reference index](./sdk-reference/index.md) (architecture overview)
- [ParksAPI methods](./sdk-reference/parks-api.md)
- [Filter options](./sdk-reference/filter-options.md)
- [Map options](./sdk-reference/map-options.md)

## Operations

- [Import and update lifecycle](./guides/import-update-lifecycle.md) (`bin/sync.sh`, migrate, cron)
- [Performance](./guides/performance-and-upgrade-strategy.md)

## Maintainers

- Publish a release: push a version tag (`22`, `22.1`) — see [import and update lifecycle](./guides/import-update-lifecycle.md#publish-a-github-release)
- PHPUnit (Git checkout only, not in the release ZIP): `composer install` then `vendor/bin/phpunit` / `composer test`
- Sync smoke: `bash swiss-parks-sdk/bin/sync-smoke.sh` or `--quick`

## All guides

Full file list: [guides index](./guides/index.md)

## Contact

[Network of Swiss Parks](https://www.parks.swiss), [info@parks.swiss](mailto:info@parks.swiss)
