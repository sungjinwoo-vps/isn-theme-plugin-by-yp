#!/bin/sh
set -eu

PROJECT_ROOT="/Users/yp/Projects/infosecnexus"
DOCKER_PATH="/usr/local/bin:/Applications/Docker.app/Contents/Resources/bin:/Applications/Docker.app/Contents/Resources/cli-plugins:/usr/bin:/bin:/usr/sbin:/sbin"

cd "$PROJECT_ROOT"

PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp term create category "Cybersecurity" --porcelain >/dev/null 2>&1 || true
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp term create category "Critical CVEs" --porcelain >/dev/null 2>&1 || true
PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp term create category "Infrastructure" --porcelain >/dev/null 2>&1 || true

CONTENT="Editorial placeholder: the July 14, 2026 article body was not supplied between CONTENT_START and CONTENT_END. Missing citations and any potentially internal Manyreach information remain pending editorial approval."

PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp post create \
  --post_type=post \
  --post_status=draft \
  --post_title="Daily Cybersecurity & Infrastructure Brief - July 14, 2026" \
  --post_content="$CONTENT" \
  --post_category="$(PATH="$DOCKER_PATH" docker compose --profile cli run --rm wpcli wp term list category --field=term_id --name=Cybersecurity | tail -n 1)"
