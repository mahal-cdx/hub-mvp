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

finance="$ROOT/apps/shared/finance.php"
admin="$ROOT/apps/admin/public/index.php"
users="$ROOT/apps/users/public/index.php"

required_functions=(
  points_event_catalog
  save_scoring_rule
  award_points
  save_point_quote
  confirm_manual_payment
  request_withdrawal
  transition_withdrawal
)

for symbol in "${required_functions[@]}"; do
  grep -q "function $symbol" "$finance"
done

grep -q "INSERT IGNORE INTO eventos_pontuacao" "$finance"
grep -q "FOR UPDATE" "$finance"
grep -q "aes-256-gcm" "$finance"
grep -q "Configure as três regras" "$finance"
grep -q "/finance/payment" "$admin"
grep -q "/withdrawals/action" "$admin"
grep -q "/wallet/withdraw" "$users"
grep -q "require_csrf" "$admin"
grep -q "require_csrf" "$users"
grep -q "chave_pix_criptografada" "$ROOT/database/migrations/006_add_secure_payout_destination.sql"

echo "Validação estática do financeiro concluída."
