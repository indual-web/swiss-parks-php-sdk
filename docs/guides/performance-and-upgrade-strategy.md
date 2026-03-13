# Performance and Upgrade Strategy

## Performance recommendations

### PHP/server

- Use sufficient `memory_limit` and `max_execution_time` for import jobs.
- Run imports via CLI (cron), not via browser.

### Database

- Ensure proper indexes on common join/filter fields (for example `offer_id`, `park_id`, language/link table columns).
- Use `EXPLAIN` for slow queries and optimize indexes based on real workloads.

### Operations

- Choose a cron interval matching your business needs.
- Monitor logs in `log_directory`.

## Upgrade strategy

1. Download the new SDK version.
2. Update core files.
3. Re-apply/compare only your custom layers (`custom/`, custom templates, `config.php`).
4. Run migration if required.
5. Validate:
   - filter
   - list
   - map
   - detail
   - multilingual behavior

## Project rule for maintainability

Avoid changing core files whenever possible.
Use these extension points instead:

- `config.php`
- `custom/*.php`
- `template/<your-folder>/`

This keeps upgrades safe and reduces merge risk.

## Related docs

- [Upgrade guide](./upgrading.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
