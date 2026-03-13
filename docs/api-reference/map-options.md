# Map Options

Reference for keys that can be passed in `$api->map_options`.

## Supported options

The current PHP renderer (`ParksView`) forwards these keys into `window.parksMapConfig`:

- `map_initialize_on_load` (`bool`)
  - Start map initialization immediately on page load.
- `parkperimeter_visibility` (`bool`)
  - Show/hide park perimeter overlay on initial render.
- `full_height` (`bool`)
  - Enable full-height map display mode.

## Commonly used example keys

These keys are present in `example.php` comments and may be used depending on map frontend bundle/version:

- `show_layers_at_start` (`bool`)
- `associated_members_visibility` (`bool`)
- `link_target` (`string`)
- `disable_auto_load_oev` (`bool`)
- `map_extent` (`array{xmin: float, ymin: float, xmax: float, ymax: float}`)
- `do_not_group_categories_in_legend` (`bool`)

## Compatibility notes

- In the current PHP renderer, `show_layers_at_start` and `link_target` are set internally in generated JS and are not dynamically read from `$api->map_options`.
- Advanced keys listed above should be validated in your project runtime (frontend map package + SDK version) before relying on them for behavior-critical features.

## Example

```php
$api->map_options = [
    'map_initialize_on_load' => false,
    'parkperimeter_visibility' => true,
    'full_height' => true,
];
```

