# Releasing (Internal)

This project is maintained by an internal team.

## Goal

Use a low-effort release process:

- Keep historical ZIP files as legacy archive material.
- Start a clean tag/release process from now on.
- Do not retro-tag every old version.

## Legacy Versions

Older versions may exist as ZIP files in `zip/`.
These are considered historical artifacts and do not need full retroactive Git tagging.

## Release Process (from now on)

1. Prepare changes on your release branch.
2. Update version references (if applicable).
3. Create release commit.
4. Create Git tag (example: `v22.0.0`).
5. Create GitHub release from the tag.
6. Attach build ZIP as release asset (optional, recommended).

## Minimal Retro-Tagging (Optional)

If needed, only tag major milestones (example: `v20`, `v21`, `v21.1`).
No need to tag every historical patch release.

## Naming Recommendation

- Tags: `vMAJOR.MINOR.PATCH` (example: `v22.1.0`)
- Release title: `Parks API v22.1.0`
- ZIP asset: `Parks-API-22.1.0.zip`
