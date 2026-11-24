# Swiss Parks PHP SDK

Documentation for developers who integrate the SDK into a park website: install, configuration, rendering, sync and upgrades on your server.

The SDK imports offer data from the Swiss Parks XML export into a local SQLite database file and renders filter, list, map and detail views. No database server is required.

## Getting started

1. [New installation](./guides/new-installation.md): ZIP or Composer, first import, cron
2. [Configuration](./guides/configuration.md): config files, merge order and all settings
3. [Quick start integration](./guides/quick-start-integration.md): page code, frontend assets, list/map tabs

Coming from v21 or older: [v21 → v22](./guides/migrations/21-to-22.md)

## Features

- [Templating and custom view](./guides/templating-and-custom-view.md): own templates and view class
- [SEO URLs](./guides/seo-urls.md): path routing and canonical detail URLs
- [Favorites](./guides/favorites.md): favorite links and favorites overview
- [WordPress integration](./guides/wordpress-integration.md): plugin sketch, enqueue, cron, SEO rewrites

## Operations

- [Import and update lifecycle](./guides/import-update-lifecycle.md): when to sync, force, migrate; cron; performance
- [Upgrade](./guides/upgrading.md): later releases for ZIP and Composer installations
- [Troubleshooting](./guides/troubleshooting.md): common problems and fixes

## Reference

- [SwissParksSdk methods](./sdk-reference/swiss-parks-sdk.md): classes, constants and public methods
- [CLI scripts](./guides/bin-scripts.md): commands, options, exit codes and environment variables
- [Filter options](./sdk-reference/filter-options.md): keys for the `$filter` array
- [Map options](./sdk-reference/map-options.md): keys for `$sdk->mapOptions`
- [Template placeholders](./sdk-reference/template-placeholders.md): placeholders and conditions for `detail.tpl` / `filter.tpl`

## Contact

[Network of Swiss Parks](https://www.parks.swiss), [info@parks.swiss](mailto:info@parks.swiss)
