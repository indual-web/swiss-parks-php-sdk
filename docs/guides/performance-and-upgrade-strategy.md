# Performance and upgrade strategy

## Performance recommendations

### PHP/server

- Use sufficient `memory_limit` and `max_execution_time` for import jobs.
- Run imports via CLI (cron), not via browser. CLI imports call `set_time_limit(0)` and raise `memory_limit` to `512M` automatically.
- XML is downloaded to a temp file, then parsed with SimpleXML; the in-memory DOM still scales with export size (raise `memory_limit` further for very large hashes if needed).

### Import chunking

- Offers are processed in chunks of **300**.
- Each chunk is committed as its own SQLite transaction (after shared meta-sync: categories, target groups, accessibilities, fields of activity).
- Import logs one summary line per chunk (including offer IDs); per-offer lines are kept for errors and deletions.
- Incremental syncs use `?since=` when not forcing a full update.

### Database

- The SDK uses a local SQLite file (WAL mode), so reads during a running import are non-blocking.
- The shipped schema (`swiss-parks-sdk/database/schema.sql`) contains indexes on common join/filter fields (for example `offer_id`, `park_id`, `offer_date.date_from`, link-table filter columns).
- Existing databases pick up new indexes automatically on connect (`CREATE INDEX IF NOT EXISTS`); a full `migrate.php` rebuild is not required for index-only updates.
- Event offers with multiple dates appear once per date in list views (`filter_offers()` groups events by `offer_date.date_from`).
- Use `EXPLAIN QUERY PLAN` for slow queries and optimize indexes based on real workloads.
- Place the `db_path` directory on fast local storage (avoid network filesystems for SQLite files).

### Read path (list, filter, map)

- Paginated lists use a dedicated count query instead of `COUNT(*) OVER ()` on the full projection.
- Related list data (categories, target groups, dates, images) is batch-loaded with `WHERE IN (...)` instead of per-offer queries.
- Map overview enrichment loads only category links (the map client resolves geometry via the API key).
- Accessibility and subscription joins are added only when the current request needs them.
- Filter UI loads type counts, category IDs and parks in one `filter_meta` query (request-cached).
- Text search uses SQLite FTS5 (`offer_fts`) when available; otherwise falls back to multi-column `LIKE`. The FTS index is created on connect and rebuilt at the end of each import.

### Operations

- Choose a cron interval matching your business needs.
- Monitor logs in `log_directory`.

## Upgrade strategy

1. Download the new SDK version.
2. Update core files.
3. Re-apply/compare only your custom layers (`custom/`, custom templates, `config.php`).
4. Run `bin/migrate.php` (rebuilds the SQLite database and runs a full import).
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
