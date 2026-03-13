# Upgrade guide

This guide describes a safe upgrade workflow from an older SDK/API version to a newer release.

## Recommended upgrade flow

1. Download the target API/SDK release.
2. Create a full backup of:
   - current API files
   - MySQL database
3. Replace SDK core files in your API folder:
   - `autoload.php`
   - `classes/`
4. Run migration:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/migrate.php`
5. Run forced import:
   - `php /{PATH-TO-YOUR-API-FOLDER}/scripts/force_update.php`
6. Validate functionality end-to-end:
   - filter
   - list
   - map
   - detail
   - browser console
   - logs

## Example: v21 to v22

Use the same flow above. The critical sequence is:

1. Replace files.
2. Run migration.
3. Run forced import.

## Upgrade notes

- Keep customizations outside replaced core paths:
  - `custom/`
  - custom templates
  - `config.php`
- Always run migration before forced import after a version update.
- Re-test multilingual behavior and SEO routes when enabled.

## Rollback strategy

If issues appear after upgrade:

1. Restore file backup.
2. Restore database backup.
3. Re-run old version cron/import.

## Related docs

- [Guide index](./index.md)
- [README](../../README.md) (operations overview)
- [API reference index](../api-reference/index.md)
