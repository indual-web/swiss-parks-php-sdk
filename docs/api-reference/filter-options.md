# Filter options

Reference for keys that can be passed in `$filter`.

## Query and category filters

- `categories` (`int[]`)
  - Limit results to category IDs.
- `offers` (`int[]`)
  - Whitelist results to explicit offer IDs.
- `search` (`string`)
  - Full-text query for title/content fields.
- `keywords` (`string`)
  - Keyword filter expression used by model keyword matching.
- `target_groups` (`int[]`)
  - Limit results to selected target groups.
- `fields_of_activity` (`int[]`)
  - Limit results to selected fields of activity.
- `municipalities` (`int|int[]`)
  - Limit results by municipality IDs.
- `park_id` (`int|int[]`)
  - Force park restriction independent of default runtime park.
- `user` (`string`)
  - Restrict results by park user identifier.
- `exclude_park_ids` (`int[]`)
  - Exclude park IDs from result set.

## Boolean feature filters

- `contact_is_park_partner` (`bool`)
  - `true`: show only offers from park partners.
- `offers_is_park_event` (`bool`)
  - `true`: show only event offers.
- `online_shop_enabled` (`bool`)
  - `true`: show only offers with online shop enabled.
- `barrier_free` (`bool`)
  - `true`: show only barrier-free offers.
- `learning_opportunity` (`bool`)
  - `true`: show only learning opportunity offers.
- `child_friendly` (`bool`)
  - `true`: show only child-friendly offers.
- `has_accessibility_informations` (`bool`)
  - `true`: require offers with accessibility details.
- `is_hint` (`bool`)
  - `true`: limit to hint/tip entries.
- `offers_of_today` (`bool`)
  - `true`: shortcut to force date filter to current day.

## System filters

- `system_filter` (`array`)
  - Hard constraints set by the host system, not intended for end-user changes.
  - `target_groups` (`int[]`)
  - `fields_of_activity` (`int[]`)

Additional structured filters supported by the model/query layer:

- `offer_settings` (`array<string, mixed>`)
  - Attribute-level constraints merged into SQL filtering.
- `accessibilities` (`int[]`)
  - Match selected accessibility IDs.
- `date_from` (`string`)
  - Lower bound for date filter (`Y-m-d` expected by runtime).
- `date_to` (`string`)
  - Upper bound for date filter (`Y-m-d` expected by runtime).
- `time_required` (`string|string[]`)
  - Route/activity time filters.
- `route_length_min` (`int`)
  - Minimum route length.
- `route_length_max` (`int`)
  - Maximum route length.
- `level_technics` (`int|int[]`)
  - Technical difficulty filter.
- `level_condition` (`int|int[]`)
  - Condition/fitness difficulty filter.
- `project_status` (`int|int[]`)
  - Restrict by project status values.
- `force_language` (`string`)
  - Force language in query layer (advanced/internal integrations).

## UI toggles

- `show_keywords_filter` (`bool`)
  - Enables keyword filter control in the filter UI.
- `hide_user_filter` (`bool`)
  - Hides user/park selector in filter UI.
- `hide_accessibility_filter` (`bool`)
  - Hides accessibility controls in filter UI.

## Runtime notes

- Filter values can be sourced from POST, allowed GET params, and session state.
- Allowed GET params in runtime initialization are: `categories`, `target_groups`, `fields_of_activity`, `accessibilities`.
- If `use_sessions` is enabled, active filter state is persisted in session.

## Related docs

- [API reference index](./index.md)
- [ParksAPI methods](./parks-api.md)

