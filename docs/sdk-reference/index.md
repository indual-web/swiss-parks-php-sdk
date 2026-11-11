[Docs](../index.md) › SDK reference

# SDK reference

This reference documents the integrator-facing surface of the Swiss Parks PHP SDK.

## Architecture

| Layer | Role |
| --- | --- |
| `ParksAPI` | Main integration entry point (bootstrap, render, sync helpers) |
| `ParksImport` | Imports offers and related export data into the local SQLite database |
| `ParksModel` | SQL filtering, aggregation, and query logic |
| `ParksView` | Renders filter, list, map, pagination, and detail HTML (`custom/MyView.php` can override) |
| `ParksLanguage` | Translation labels and runtime language context |

Static helpers used by views and custom code: `ParksDate`, `ParksFormat`, `ParksMap`.

## Scope

- Public integration methods from `ParksAPI` used in typical project integration.
- Filter keys for `$filter` arrays.
- Map configuration keys for `$api->mapOptions`.

## Pages

- [ParksAPI methods](./parks-api.md)
- [Filter options](./filter-options.md)
- [Map options](./map-options.md)

## Source of truth

This reference is based on:

- `swiss-parks-sdk/classes/ParksAPI.php`
- `swiss-parks-sdk/config/config.php`
- `index.php`
