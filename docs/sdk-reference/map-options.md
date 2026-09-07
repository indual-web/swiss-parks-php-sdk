# Map options

Reference for keys that can be passed in `$api->map_options`.

## Options

- `map_initialize_on_load` (`bool`)
  - Starts map initialization directly on page load.
- `show_layers_at_start` (`bool`)
  - Controls whether map layers are visible when the map is initialized. Read by the renderer into `showLayersAtStart`.
- `parkperimeter_visibility` (`bool`)
  - Shows or hides the park perimeter overlay on initial render (`parkPerimeterVisibility`).
- `associated_members_visibility` (`bool`)
  - Shows or hides the associated members layer.
- `link_target` (`string`)
  - Defines the target for offer links from map interactions (for example `_self` or `_blank`). Read into `linkTarget`; defaults to `_self` in the renderer.
- `full_height` (`bool`)
  - Enables full-height map display mode (`fullHeight`).
- `disable_auto_load_oev` (`bool`)
  - Disables automatic loading of public transport overlays/data.
- `map_extent` (`array{xmin: float, ymin: float, xmax: float, ymax: float}`)
  - Overrides the default map extent with a custom bounding box.
- `do_not_group_categories_in_legend` (`bool`)
  - Prevents category grouping in the map legend.

## Generated `swissParksMapConfig` notes

The renderer writes camelCase keys that match the interactive map schema. Notably:

- Container id is `swiss-parks-map` (not `mapContainer`).
- `popupLinkOrigin` is the page origin only. The map composes the detail path from `seoUrl`, language and `apiKey` — custom path prefixes (`popupLinkPath`) are no longer supported.
- `statePersistence` defaults to `{ shareableUrl: false, rememberSession: false }` so the configured park extent can win on load.
- Detail maps use `window.swissParksMapConfig` with `mode: 'detailmap'` and camelCase keys throughout.

## Compatibility notes

- In `example.php`, all map options are shown as commented examples.
- Some keys depend on the frontend map bundle/version in use. Validate behavior in your project runtime before relying on it for behavior-critical features.

## Example

```php
$api->map_options = [
    'map_initialize_on_load' => false,
    'show_layers_at_start' => true,
    'parkperimeter_visibility' => true,
    'link_target' => '_self',
    'full_height' => true,
];
```

## Related docs

- [SDK reference index](./index.md)
- [ParksAPI methods](./parks-api.md)
