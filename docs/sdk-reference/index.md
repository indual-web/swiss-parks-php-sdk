[Docs](../index.md) › SDK reference

# SDK reference

Integrator-facing API and options for embedding the Swiss Parks PHP SDK on a park website.

## Main classes you work with

| Class | Role |
| --- | --- |
| `ParksAPI` | Entry point: bootstrap, render filter/list/map/detail, sync helpers |
| `ParksView` | HTML rendering; override via `custom/MyView.php` when needed |
| `ParksLanguage` | UI labels and active language |

Helpers you may use in custom views: `ParksDate`, `ParksFormat`, `ParksMap`.

## Scope

- Public `ParksAPI` methods used in typical integrations
- Filter keys for `$filter` arrays
- Map keys for `$api->mapOptions`

## Pages

- [ParksAPI methods](./parks-api.md)
- [Filter options](./filter-options.md)
- [Map options](./map-options.md)
