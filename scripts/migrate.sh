#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${project_root}"

docker compose exec -T mysql sh -c     'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; mysql -uroot "$MYSQL_DATABASE"'     < database/migrations/000_create_schema_migrations.sql

for migration_path in database/migrations/*.sql; do
    migration_name="$(basename "${migration_path}")"

    if [[ "${migration_name}" == "000_create_schema_migrations.sql" ]]; then
        continue
    fi

    query="SELECT COUNT(*) FROM schema_migrations WHERE migration = '${migration_name}'"
    applied="$(docker compose exec -T mysql sh -c         'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; mysql -uroot "$MYSQL_DATABASE" -Nse "$1"'         sh "${query}")"

    if [[ "${applied}" == "0" ]]; then
        echo "Aplicando ${migration_name}"
        docker compose exec -T mysql sh -c             'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; mysql -uroot "$MYSQL_DATABASE"'             < "${migration_path}"
    else
        echo "Ignorando ${migration_name}: já aplicada"
    fi
done
