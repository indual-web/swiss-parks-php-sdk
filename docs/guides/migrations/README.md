# Version migrations

This folder contains **version-specific migration guides** for upgrades that need more than replacing core files and running `migrate.php`.

Use the generic [upgrade guide](../upgrading.md) for the standard workflow. Open a guide here when your current version is listed below.

## Available migrations

| From | To | Guide | Breaking changes |
| --- | --- | --- | --- |
| v21 (MySQL) | v22 (SQLite) | [21 to 22](./21-to-22.md) | Database layer, `config.php` |

## When to add a new guide

Create a new file `{from}-to-{to}.md` when a release includes at least one of:

- Config key changes (removed/renamed keys, new required values)
- Database or storage changes
- Changed script behavior integrators must know about
- Manual steps beyond «replace core files + run `migrate.php`»

For minor releases without breaking changes, a short note in [upgrading.md](../upgrading.md) is enough.

## Guide template

Copy this structure for new migration guides:

```markdown
# Migration: vX to vY

## Overview
- What changed and who is affected

## Before you start
- Prerequisites, backups, downtime expectations

## Step-by-step migration
1. Backup
2. Config changes (before/after)
3. Replace SDK files (keep vs replace)
4. Run migration command
5. Validate
6. Cleanup (optional)
7. Rollback

## Troubleshooting
- Common errors and fixes

## Related docs
- Links to configuration, upgrade guide, import lifecycle
```

After adding a guide:

1. Add a row to the table in this file.
2. Link it from [upgrading.md](../upgrading.md) under «Version-specific migrations».
3. Update [guides index](../index.md) if needed.
4. Follow the [release checklist](../release-checklist.md) before publishing.
