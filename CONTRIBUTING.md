# Contributing

Thank you for improving the Swiss Parks PHP SDK. External contributions are welcome via pull request.

## Before you start

- Read the [documentation hub](docs/index.md) and [configuration guide](docs/guides/configuration.md).
- This software is licensed for use by member parks of the [Network of Swiss Parks](https://www.parks.swiss). See [LICENSE](LICENSE). By opening a pull request you agree that your contribution may be included in this repository under that license.

## How to contribute

1. Fork the repository and create a branch from `main`.
2. Make a focused change (one concern per PR).
3. Open a pull request against `main` and describe the problem and the fix.

Maintainers review PRs; merge is not guaranteed.

## Local setup (optional)

- PHP `>= 8.2` and `<= 8.5` with `pdo_sqlite` and cURL.
- Copy secrets only into `swiss-parks-sdk/config/config.local.php` (gitignored). Keep placeholders in `config.php`.
- Never commit `api_hash`, `sync_secret`, SQLite databases, or log files.
- Optional DDEV: use `.ddev/config.yaml`. Machine-specific mounts (for example a local map `dist`) stay in untracked overrides.
- Tests: `composer install` then `vendor/bin/phpunit`.
- Smoke check after sync: `bash swiss-parks-sdk/bin/sync-smoke.sh` (needs a real `api_hash` in `config.local.php`).

Integrators who only embed the SDK should use the [release ZIP](https://github.com/indual-web/swiss-parks-php-sdk/releases), not a full git clone.

## What we look for

- Backward compatibility for park websites on the current `SDK_VERSION`
- Docs under `docs/` updated when behaviour, config, or setup changes
- No secrets or personal data in commits, fixtures, or examples

## Questions

[info@parks.swiss](mailto:info@parks.swiss) · [parks.swiss](https://www.parks.swiss)
