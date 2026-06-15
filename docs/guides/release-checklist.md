# Release checklist (maintainers)

Use this checklist before publishing a new Swiss Parks PHP SDK release ZIP.

## 1. Version and code

- [ ] Set `API_VERSION` in `parks_api/autoload.php`
- [ ] Update schema in `parks_api/database/schema.sql` if the release requires DB changes
- [ ] Add or update a [version migration guide](./migrations/README.md) for breaking upgrades

## 2. Validation before release

- [ ] `php parks_api/scripts/migrate.php` (exit code `0`)
- [ ] `bash parks_api/bin/sync.sh` (exit code `0`)
- [ ] Validate on a test page:
  - filter
  - list
  - map
  - detail
  - browser console (no JS errors)
- [ ] Check logs in `parks_api/log/`

## 3. Publish release

- [ ] Tag and push to trigger the GitHub Actions release workflow (`.github/workflows/release.yml`):
  - `git tag <version>` (example: `git tag 22`)
  - `git push origin <version>`
- [ ] CI builds `swiss-parks-php-sdk-<version>.zip` via `deploy.sh` and publishes a [GitHub Release](https://github.com/indual-web/swiss-parks-php-sdk/releases)
- [ ] Verify the release asset:
  - contains `parks_api/` and `example.php`
  - does **not** contain `docs/`, `releases/`, `data/`, `log/`, `config.local.php`
- [ ] Optional local build: `bash deploy.sh <version>` (output in `releases/`, gitignored)

### One-time: rename legacy release ZIPs on GitHub

Older releases used `Parks-API-<version>.zip`. To rename existing GitHub release assets:

```bash
GITHUB_TOKEN=ghp_... bash bin/rename-github-release-zips.sh --dry-run
GITHUB_TOKEN=ghp_... bash bin/rename-github-release-zips.sh
```

Local archives in `releases/` should use `swiss-parks-php-sdk-<version>.zip` (already renamed in the repo).

## 4. Documentation

- [ ] Update [upgrading.md](./upgrading.md) and migration guides if needed
- [ ] Update [README.md](../../README.md) release notes section
- [ ] Add entry to `CHANGELOG.md` when present

## Related docs

- [Upgrade guide](./upgrading.md)
- [Version migrations](./migrations/README.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
