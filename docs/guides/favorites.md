# Favorites module

This module is optional. If enabled, each offer can show an add/remove favorite link for visitors.

## What it does

- Adds a favorite toggle link in offer listings.
- Stores selected favorites client-side in a cookie.
- Allows rendering a favorites overview page for the current visitor.

## Enable/disable behavior

- `favorites_extension_available = true` enables the feature.
- `favorites_script_path` must point to your API script folder.
- `use_sessions = true` must be enabled for persistent favorites behavior.
- `session_name` defines the cookie/session prefix used by the favorites module.
- If disabled, no favorite link is shown in offer listings.

## Storage and lifecycle

- Favorites are stored in a cookie named `<session_name>_favorites`.
- The cookie stores offer IDs and is updated when visitors add/remove favorites.
- A clean action is available to clear all favorites.

## Endpoints and methods

- Script endpoint: `parks_api/scripts/favorite.php`
  - Toggle favorite: `favorite.php?offer_id=<id>`
  - Clear all: `favorite.php?action=clean`
- API methods:
  - `toggle_favorite($offer_id)`
  - `show_favorites()`
  - `clean_favorites()`

## Related docs

- [Guide index](./index.md)
- [SEO URLs](./seo-urls.md)
