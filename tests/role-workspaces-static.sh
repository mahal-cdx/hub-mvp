#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if command -v php >/dev/null 2>&1; then
  while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
  done < <(find "$ROOT/apps" -type f -name '*.php' -print0)
else
  echo "php não encontrado; lint PHP ignorado" >&2
fi

required=(
  "attempt_user_login"
  "require_operational_user"
  "user_has_role"
  "create_managed_user"
  "create_lead"
  "claim_opportunity"
  "submit_project"
  "review_project"
  "claim_sale"
  "record_sale_interaction"
)

for symbol in "${required[@]}"; do
  if ! grep -Rqs "function $symbol" "$ROOT/apps/shared"; then
    echo "Símbolo obrigatório ausente: $symbol" >&2
    exit 1
  fi
done

for route in /leads /dev/claim /dev/submit /sales/claim /sales/contact; do
  grep -q "$route" "$ROOT/apps/users/public/index.php"
done

grep -q "/users/new" "$ROOT/apps/admin/public/index.php"
grep -q "/projects/review" "$ROOT/apps/admin/public/index.php"
grep -q "require_csrf" "$ROOT/apps/users/public/index.php"
grep -q "require_csrf" "$ROOT/apps/admin/public/index.php"

echo "Validação estática dos ambientes por função concluída."
