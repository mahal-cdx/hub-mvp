#!/usr/bin/env bash
set -Eeuo pipefail

database_dir=/opt/hub/database
export MYSQL_PWD="${MYSQL_ROOT_PASSWORD}"

apply_sql() {
    local file_path="$1"
    mysql --protocol=socket -uroot "${MYSQL_DATABASE}" < "${file_path}"
}

apply_sql "${database_dir}/migrations/000_create_schema_migrations.sql"

for migration_path in "${database_dir}"/migrations/*.sql; do
    migration_name="$(basename "${migration_path}")"

    if [[ "${migration_name}" == "000_create_schema_migrations.sql" ]]; then
        continue
    fi

    applied="$(mysql --protocol=socket -uroot "${MYSQL_DATABASE}" -Nse         "SELECT COUNT(*) FROM schema_migrations WHERE migration = '${migration_name}'")"

    if [[ "${applied}" == "0" ]]; then
        echo "Aplicando migration ${migration_name}"
        apply_sql "${migration_path}"
    fi
done

if [[ "${LOAD_DEMO_SEEDS:-false}" == "true" ]]; then
    echo "Carregando seeds de demonstração"
    for seed_path in "${database_dir}"/seeds/*.sql; do
        apply_sql "${seed_path}"
    done
else
    echo "Seeds de demonstração desativados"
fi
