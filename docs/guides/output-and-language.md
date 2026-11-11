# Output mode and multilingual support

## Output mode

- `returnOutput = false` (default)
  - Methods print output directly (`echo`).
- `returnOutput = true`
  - Methods return HTML as string.

Useful for:

- CMS integrations with output buffering.
- Composed layouts where HTML is injected manually.

For WordPress embeds, combine `returnOutput = true` with `soft_errors = true`. See [WordPress integration](./wordpress-integration.md).

## Multilingual support

Language files:

- `swiss-parks-sdk/lang/de.php`
- `swiss-parks-sdk/lang/fr.php`
- `swiss-parks-sdk/lang/it.php`
- `swiss-parks-sdk/lang/en.php`

Runtime behavior:

- The active SDK language is passed via `new ParksAPI('<lang>')` (for example `de`, `fr`, `it`, `en`).
- If the language is missing/invalid, the SDK falls back to `de`.
- `available_languages` defines which language files can be loaded.
- `language_independence` and `language_priority` control data fallback behavior across languages.
- `default_language` in `config.php` can be used as a project convention, but runtime selection is constructor-driven.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
