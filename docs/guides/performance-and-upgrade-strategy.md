# Performance and upgrade strategy

## Performance recommendations

### PHP/server

- Use sufficient `memory_limit` and `max_execution_time` for import jobs.
- Run imports via CLI (cron), not via browser.

### Database

- The SDK uses a local SQLite file (WAL mode), so reads during a running import are non-blocking.
- The shipped schema (`swiss-parks-sdk/database/schema.sql`) already contains indexes on common join/filter fields (for example `offer_id`, language/link table columns).
- Event offers with multiple dates appear once per date in list views (`filter_offers()` groups events by `offer_date.date_from`).
- Use `EXPLAIN QUERY PLAN` for slow queries and optimize indexes based on real workloads.
- Place the `db_path` directory on fast local storage (avoid network filesystems for SQLite files).

### Operations

- Choose a cron interval matching your business needs.
- Monitor logs in `log_directory`.

## Upgrade strategy

1. Download the new SDK version.
2. Update core files.
3. Re-apply/compare only your custom layers (`custom/`, custom templates, `config.php`).
4. Run `scripts/migrate.php` (rebuilds the SQLite database and runs a full import).
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

- [Guide index](./index.md)
- [Upgrade guide](./upgrading.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
