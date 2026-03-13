# Map options

Reference for keys that can be passed in `$api->map_options`.

## Options

- `map_initialize_on_load` (`bool`)
  - Starts map initialization directly on page load.
- `show_layers_at_start` (`bool`)
  - Controls whether map layers are visible when the map is initialized.
- `parkperimeter_visibility` (`bool`)
  - Shows or hides the park perimeter overlay on initial render.
- `associated_members_visibility` (`bool`)
  - Shows or hides the associated members layer.
- `link_target` (`string`)
  - Defines the target for offer links from map interactions (for example `_self` or `_blank`).
- `full_height` (`bool`)
  - Enables full-height map display mode.
- `disable_auto_load_oev` (`bool`)
  - Disables automatic loading of public transport overlays/data.
- `map_extent` (`array{xmin: float, ymin: float, xmax: float, ymax: float}`)
  - Overrides the default map extent with a custom bounding box.
- `do_not_group_categories_in_legend` (`bool`)
  - Prevents category grouping in the map legend.

## Compatibility notes

- In `example.php`, all map options are shown as commented examples.
- In the current PHP renderer, `show_layers_at_start` and `link_target` are set internally in generated JS and are not dynamically read from `$api->map_options`.
- Some keys depend on the frontend map bundle/version in use. Validate behavior in your project runtime before relying on it for behavior-critical features.

## Example

```php
$api->map_options = [
    'map_initialize_on_load' => false,
    'parkperimeter_visibility' => true,
    'full_height' => true,
];
```

## Related docs

- [API reference index](./index.md)
- [ParksAPI methods](./parks-api.md)

