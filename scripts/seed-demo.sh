#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${project_root}"

app_env="$(docker compose exec -T mysql sh -c 'printf %s "$APP_ENV"' 2>/dev/null || true)"
if [[ "${app_env}" == "production" ]]; then
    echo "Seeds de demonstração não podem ser executados em production." >&2
    exit 1
fi

for seed_path in database/seeds/*.sql; do
    echo "Aplicando $(basename "${seed_path}")"
    docker compose exec -T mysql sh -c         'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; mysql -uroot "$MYSQL_DATABASE"'         < "${seed_path}"
done
