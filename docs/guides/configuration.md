# Core Configuration (`parks_api/config.php`)

This guide documents the most important runtime settings in `parks_api/config.php`.

## Required settings

- `api_hash`
  - Hash from your export configuration on `angebote.paerke.ch`.
- `park_id`
  - Enter the park ID; you can obtain it by contacting the respective park.
- `db_hostname`, `db_username`, `db_password`, `db_database`
  - Database connection credentials.

## Runtime mode: single park vs network-wide

- `park_id`
  - `> 0`: integration for one specific park.
  - `0` or empty: network-wide integration (all available parks).

## Common display/runtime options

- `class_view` (for example `MyView`)
  - Defines which view class renders output.
- `template_folder` (for example `standard`)
  - Selects the template set inside `parks_api/template/`.
- `prevent_css_js_include`
  - Prevents automatic inclusion of SDK CSS/JS assets.
- `return_output`
  - Return generated HTML (`true`) or print directly (`false`).
- `use_sessions`, `session_name`
  - Enables SDK session-based state and sets namespace/cookie prefix.
- `language_independence`, `language_priority`
  - Controls multilingual fallback behavior.

## Practical starter configuration

Frequently tuned options:

- `return_output`
- `prevent_css_js_include`
- `use_sessions`
- `session_name`
- `offers_per_page`
- `show_route_filter`
- `show_target_group_filter`
- `show_accessibility_filter`
- `show_municipality_filter`
- `show_event_location_in_overview`
- `show_short_description_in_overview`
- `show_keywords_in_overview`
- `show_button_in_overview`
- `overview_thumbnail_size`, `detail_thumbnail_size`
- `heading_offer_title_in_overview`
- `image_enlargement`

## Related docs

- [Quick start integration](./quick-start-integration.md)
- [Output mode and multilingual support](./output-and-language.md)
