#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${project_root}"

find apps/shared apps/admin -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php tests/security-unit.php

grep -q "session_regenerate_id(true)" apps/shared/auth.php
grep -q "password_verify" apps/shared/auth.php
grep -q "ATTR_EMULATE_PREPARES => false" apps/shared/database.php
grep -q "Content-Security-Policy" infrastructure/apache/000-default.conf
grep -q "csrf_verify" apps/admin/public/index.php

echo "PASS: lint PHP e controles essenciais de autenticação validados."
