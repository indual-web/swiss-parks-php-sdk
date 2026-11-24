[Docs](../index.md) › Import and update lifecycle

# Import and update lifecycle

How offer data gets into the local SQLite database and how to keep it current. Commands, options and exit codes: [CLI scripts](./bin-scripts.md).

## Where things live

| | ZIP installation | Composer installation |
| --- | --- | --- |
| CLI | `swiss-parks-sdk/bin/` | `vendor/bin/swiss-parks-sdk` |
| Config | `swiss-parks-sdk/config/config.local.php` | Project config, for example `config/parks.php` |
| Database and logs | `swiss-parks-sdk/writable/` (default) | `db_path` / `log_directory` in your project |
| HTTP endpoints | `swiss-parks-sdk/public/` (`sync.php`, `favorites.php`) | Own wrappers ([public endpoints](./new-installation.md#public-endpoints)) |

## Which action when

| Situation | Action |
| --- | --- |
| Regular updates | Incremental sync from cron, for example every 4 hours. It fetches only offers changed since the last import |
| Local data incomplete or a sync failed | Full sync with `--force` (HTTP: `force=1`) |
| SDK update | Migration: deletes the SQLite file, recreates the schema from `database/schema.sql` and runs a full import. `upgrade-sdk.sh` and the Composer scripts run it automatically ([upgrade](./upgrading.md)) |
| Check after install or in monitoring | `sync-smoke.sh` (ZIP installations) |

Every release raises `SwissParksSdk::VERSION` and needs one migration. There are no incremental schema migrations.

## Cron

Prefer CLI cron. Both commands exit with `1` on failure, so cron monitoring can alert on it.

```cron
# ZIP installation
0 */4 * * * bash /path/to/swiss-parks-sdk/bin/sync.sh >> /path/to/swiss-parks-sdk/writable/log/cron-sync.log 2>&1

# Composer installation
0 */4 * * * cd /path/to/project && vendor/bin/swiss-parks-sdk sync --config=config/parks.php >> var/swiss-parks/log/cron-sync.log 2>&1
```

Choose an interval that matches how often offers change.

## HTTP sync (shared hosting)

Use this only when the host cannot run CLI cron (no SSH). `bin/` is never callable over HTTP.

1. Set a long random `sync_secret` in your config ([configuration](./configuration.md#database-and-sync)).
2. Point the host's URL cron at `public/sync.php` (Composer: your [wrapper](./new-installation.md#public-endpoints)):

```text
https://example.com/path/to/swiss-parks-sdk/public/sync.php?token=YOUR_SYNC_SECRET
https://example.com/path/to/swiss-parks-sdk/public/sync.php?token=YOUR_SYNC_SECRET&force=1
```

The header `X-Sync-Token: YOUR_SYNC_SECRET` can replace the `token` parameter.

| Response | Meaning |
| --- | --- |
| `403` | `sync_secret` is empty (endpoint disabled) or the token is wrong |
| `200` and `sync: ok (...)` | Sync succeeded |
| `500` and `sync: fail (...)` | Sync failed; see the log |

Leave `sync_secret` empty on servers with CLI cron so the endpoint stays closed.

## Performance

- CLI and HTTP sync disable the time limit and raise `memory_limit` to `512M`. Very large exports may need more.
- Downloads give up after 10 seconds without a connection or 60 seconds without data, so a network drop cannot block cron forever.
- Place `db_path` on fast local storage, not on a network filesystem.
- Pages keep reading the database while a sync runs (SQLite WAL mode).
- Use `--force` only when needed; incremental syncs are much faster.
