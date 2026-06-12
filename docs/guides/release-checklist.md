# Release checklist (maintainers)

Use this checklist before publishing a new Parks-API release ZIP.

## 1. Version and code

- [ ] Set `API_VERSION` in `parks_api/autoload.php`
- [ ] Update schema in `parks_api/database/schema.sql` if the release requires DB changes
- [ ] Add or update a [version migration guide](./migrations/README.md) for breaking upgrades

## 2. Automated tests

- [ ] Run offline smoke test:
  - `php tests/smoke_test.php`
- [ ] Confirm GitHub Actions workflow passes (`.github/workflows/tests.yml`)

## 3. Manual validation

- [ ] `php parks_api/scripts/migrate.php` (exit code `0`)
- [ ] `bash parks_api/bin/sync.sh` (exit code `0`)
- [ ] Validate on a test page:
  - filter
  - list
  - map
  - detail
  - browser console (no JS errors)
- [ ] Check logs in `parks_api/log/`

## 4. Publish release

- [ ] Tag and push to trigger the GitHub Actions release workflow (`.github/workflows/release.yml`):
  - `git tag v<version>` (example: `git tag v22`)
  - `git push origin v<version>`
- [ ] CI runs smoke test, builds `Parks-API-<version>.zip` via `deploy.sh`, and publishes a [GitHub Release](https://github.com/indual-web/swiss-parks-php-sdk/releases)
- [ ] Verify the release asset:
  - contains `parks_api/` and `example.php`
  - does **not** contain `docs/`, `tests/`, `releases/`, `data/`, `log/`, `config.local.php`
- [ ] Optional local build: `bash deploy.sh <version>` (output in `releases/`, gitignored)

## 5. Documentation

- [ ] Update [upgrading.md](./upgrading.md) and migration guides if needed
- [ ] Update [README.md](../../README.md) release notes section
- [ ] Add entry to `CHANGELOG.md` when present

## Related docs

- [Upgrade guide](./upgrading.md)
- [Version migrations](./migrations/README.md)
- [Import and update lifecycle](./import-update-lifecycle.md)
