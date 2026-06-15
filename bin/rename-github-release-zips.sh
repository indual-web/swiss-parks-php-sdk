#!/bin/bash
set -euo pipefail

# Renames Parks-API-<version>.zip release assets to swiss-parks-php-sdk-<version>.zip
# on GitHub Releases. Requires a token with repo scope.
#
# Usage:
#   GITHUB_TOKEN=ghp_... bash bin/rename-github-release-zips.sh
#   GITHUB_TOKEN=ghp_... bash bin/rename-github-release-zips.sh --dry-run

GITHUB_REPO="${PARKS_API_GITHUB_REPO:-indual-web/swiss-parks-php-sdk}"
TOKEN="${GITHUB_TOKEN:-${GH_TOKEN:-}}"
DRY_RUN=0
LOCAL_RELEASES_DIR="$(cd "$(dirname "$0")/.." && pwd)/releases"

if [ "${1:-}" = "--dry-run" ]; then
	DRY_RUN=1
fi

if [ -z "$TOKEN" ]; then
	echo "Error: set GITHUB_TOKEN or GH_TOKEN with repo scope." >&2
	exit 1
fi

api_get()
{
	curl -fsSL \
		-H "Authorization: Bearer $TOKEN" \
		-H "Accept: application/vnd.github+json" \
		-H "X-GitHub-Api-Version: 2022-11-28" \
		"https://api.github.com/$1"
}

api_delete()
{
	curl -fsSL -X DELETE \
		-H "Authorization: Bearer $TOKEN" \
		-H "Accept: application/vnd.github+json" \
		-H "X-GitHub-Api-Version: 2022-11-28" \
		"https://api.github.com/$1" >/dev/null
}

upload_asset()
{
	local release_id="$1"
	local file_path="$2"
	local file_name="$3"

	curl -fsSL -X POST \
		-H "Authorization: Bearer $TOKEN" \
		-H "Content-Type: application/octet-stream" \
		-H "Accept: application/vnd.github+json" \
		--data-binary @"$file_path" \
		"https://uploads.github.com/repos/${GITHUB_REPO}/releases/${release_id}/assets?name=${file_name}" >/dev/null
}

RELEASES_JSON="$(api_get "repos/${GITHUB_REPO}/releases?per_page=100")"

while IFS=$'\t' read -r release_id tag_name; do
	echo "Release ${tag_name} (id ${release_id})"

	ASSETS_JSON="$(printf '%s' "$RELEASES_JSON" | python3 -c "
import json, sys
releases = json.load(sys.stdin)
for r in releases:
    if str(r['id']) == sys.argv[1]:
        print(json.dumps(r.get('assets') or []))
        break
" "$release_id")"

	while IFS=$'\t' read -r asset_id asset_name; do
		[ -n "$asset_id" ] || continue

		if [[ ! "$asset_name" =~ ^Parks-API-(.+)\.zip$ ]]; then
			continue
		fi

		version="${BASH_REMATCH[1]}"
		new_name="swiss-parks-php-sdk-${version}.zip"

		if printf '%s' "$ASSETS_JSON" | python3 -c "
import json, sys
assets = json.load(sys.stdin)
sys.exit(0 if any(a['name'] == sys.argv[1] for a in assets) else 1)
" "$new_name"; then
			echo "  skip ${asset_name} -> ${new_name} (already exists)"
			continue
		fi

		local_file="${LOCAL_RELEASES_DIR}/${new_name}"
		if [ ! -f "$local_file" ]; then
			local_file="${LOCAL_RELEASES_DIR}/${asset_name}"
		fi

		if [ ! -f "$local_file" ]; then
			echo "  skip ${asset_name} (no local file in releases/)" >&2
			continue
		fi

		echo "  ${asset_name} -> ${new_name}"

		if [ "$DRY_RUN" -eq 1 ]; then
			echo "    would upload from ${local_file}"
			echo "    would delete asset id ${asset_id}"
			continue
		fi

		upload_asset "$release_id" "$local_file" "$new_name"
		api_delete "repos/${GITHUB_REPO}/releases/assets/${asset_id}"
	done < <(printf '%s' "$ASSETS_JSON" | python3 -c "
import json, sys
for a in json.load(sys.stdin):
    print(f\"{a['id']}\t{a['name']}\")
")

done < <(printf '%s' "$RELEASES_JSON" | python3 -c "
import json, sys
for r in json.load(sys.stdin):
    print(f\"{r['id']}\t{r['tag_name']}\")
")
