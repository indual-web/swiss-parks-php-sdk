# Favorites module

This module is optional. If enabled, each offer can show an add/remove favorite link for visitors.

## What it does

- Adds a favorite toggle link in offer listings.
- Stores selected favorites client-side in a cookie.
- Allows rendering a favorites overview page for the current visitor.

## Enable/disable behavior

- `favorites_extension_available = true` enables the feature.
- `favorites_script_path` must point to the folder that contains `favorites.php` (for example `/plugins/swiss-parks-sdk/public`), or to a CMS endpoint URL.
- `use_sessions = true` must be enabled for persistent favorites behavior.
- `session_name` defines the cookie/session prefix used by the favorites module.
- If disabled, no favorite link is shown in offer listings.

## Storage and lifecycle

- Favorites are stored in a cookie named `<session_name>_favorites`.
- The cookie value is JSON (offer ID map), handled by `ParksFavorites`. Invalid or legacy non-JSON payloads are ignored (empty favorites).
- Cookie flags: `path=/`, `SameSite=Lax`, and `Secure` when the request is HTTPS.
- The cookie is updated when visitors add/remove favorites.
- A clean action is available to clear all favorites.
- There is no CSRF token on the drop-in endpoint; WordPress/CMS wrappers should use their own nonce if they expose a custom favorites URL.

## Endpoints and methods

- Script endpoint: `swiss-parks-sdk/public/favorites.php`
  - Toggle favorite: `favorites.php?offer_id=<id>`
  - Clear all: `favorites.php?action=clean`
- API methods:
  - `toggleFavorite($offer_id)`
  - `showFavorites()`
  - `cleanFavorites()`

## Related docs

- [Guide index](./index.md)
- [SEO URLs](./seo-urls.md)
