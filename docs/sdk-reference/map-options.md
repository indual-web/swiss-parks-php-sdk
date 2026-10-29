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
  - Enables full-height map display mode (`fullHeight`). Also sets `enableScrollProtection` to the inverse (`false` when full height, `true` otherwise).
- `disable_auto_load_oev` (`bool`)
  - Disables automatic loading of public transport overlays/data.
- `map_extent` (`array{xmin: float, ymin: float, xmax: float, ymax: float}`)
  - Overrides the default map extent with a custom bounding box.
- `do_not_group_categories_in_legend` (`bool`)
  - Prevents category grouping in the map layer menu. Emitted as `offerLayersNoGrouping` in `swissParksMapConfig`.
- `layers_to_remove` (`string[]` or `array{layerId: string}[]`)
  - Built-in layers to hide from the map menu (they are visible by default). See [Built-in layers](#built-in-layers).
  - Emitted as `layersToRemove: [{ layerId: '…' }, …]` on overview and detail maps.
- `layers_to_add` (`string[]` or `array{layerId: string}[]`)
  - Built-in layers that are hidden by default and should appear in the menu. See [Built-in layers](#built-in-layers).
  - Emitted as `layersToAdd: [{ layerId: '…' }, …]` on overview and detail maps.

## Built-in layers

Layer ids come from the interactive map (`swiss-parks-map`). Pass the ids you need; the SDK does not expand shortcuts.

Same options exist in the offer-database export configuration (iFrame tab) for embeds. There, trail checkboxes expand trail + closure automatically. In the SDK you list each id explicitly.

### `layers_to_remove` (hide layers that are on by default)

| Theme | Layer ids |
| --- | --- |
| Hiking | `hiking-trails`, `hiking-trails-sperrungen` |
| Cycling | `biking-trails`, `biking-trails-sperrungen` |
| Mountain bike | `mountainbiking-trails`, `mountainbiking-trails-sperrungen` |
| Skating | `skating-trails`, `skating-trails-sperrungen` |
| Bike sharing | `shared-mobility-bikes` |

To hide a trail theme completely, include **both** the trail id and its `*-sperrungen` closure id.

### `layers_to_add` (show layers that are off by default)

| Group | Layer ids |
| --- | --- |
| Nature inventories | `smaragd-gebiete`, `auengebiete`, `bln-gebiete`, `jagdbanngebiete`, `flachmoore`, `hochmoore`, `moorlandschaften`, `vogelreservate` |
| Culture inventories | `ivs-historische-verkehrswege`, `ivs-nationale-verkehrswege`, `ivs-regionale-verkehrswege`, `ivs-wegbegleiter` |
| Administration | `landesgrenze`, `kantonsgrenze`, `gemeindegrenze` |
| Mobility | `shared-mobility-cars` |

Not listed here (and therefore not controlled via these options): layers that stay visible by default without a hide checkbox in the export UI, e.g. UNESCO sites, ISOS, wildlife rest areas (`wildruhezonen`), public transport stops.

## Generated `swissParksMapConfig` notes

The renderer writes camelCase keys that match the interactive map schema. Notably:

- Container id is `swiss-parks-map` (not `mapContainer`).
- `popupLinkTemplate` is the full offer detail URL. The map only substitutes `{slug}` and `{offer_id}`. With SEO URLs this is `{origin}{seoDetailBase}/{slug}-{offer_id}`; otherwise `{origin}{scriptUrl}?offer={offer_id}` (respecting `url_param_prefix`). The old keys `popupLinkOrigin` and `seoUrl` are no longer emitted.
- Overview maps set `statePersistence` to `{ shareableUrl: true, rememberSession: true }` so the view and layers stay in the URL and session.
- Detail maps use `window.swissParksMapConfig` with `mode: 'detailmap'` and camelCase keys throughout. They also set `disableSidebar` and `disableCoordinateDisplayBanner` to `true`.
- When set, `layers_to_remove` / `layers_to_add` become `layersToRemove` / `layersToAdd` (arrays of `{ layerId }`). Empty or omitted options are left out of the config object.

## Assets

`ParksAPI::_load_maps_api()` loads only the map CSS and the ES module JS (`type="module"`). React and ArcGIS assets are bundled inside `swiss-parks-map`; do not load React 18 or ArcGIS 4.x CSS from the host page.

Base URL comes from `interactive_map_url` in `config.php` (default: `https://map.parks.swiss`). Assets: `{interactive_map_url}/dist/css/style.css` and `{interactive_map_url}/dist/js/swiss-parks-map.umd.js`.

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
    'layers_to_remove' => [
        'hiking-trails',
        'hiking-trails-sperrungen',
        'shared-mobility-bikes',
    ],
    'layers_to_add' => [
        'bln-gebiete',
        'shared-mobility-cars',
    ],
];
```

## Related docs

- [SDK reference index](./index.md)
- [ParksAPI methods](./parks-api.md)
