<?php

declare(strict_types=1);

putenv('APP_ENV=test');
putenv('APP_KEY=unit-test-key-with-more-than-thirty-two-characters');
putenv('SESSION_SECURE=false');
putenv('ADMIN_APP_ROOT=' . dirname(__DIR__) . '/apps/admin');

require dirname(__DIR__) . '/apps/shared/config.php';
require dirname(__DIR__) . '/apps/shared/security.php';

session_name('hub_security_test');
session_start();

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALHA: {$message}\n");
        exit(1);
    }
}

$token = csrf_token();
assert_true(strlen($token) === 64, 'token CSRF deve ter 256 bits em hexadecimal');
assert_true(csrf_verify($token), 'token CSRF válido deve ser aceito');
assert_true(!csrf_verify($token), 'token CSRF deve ser de uso único');
assert_true(!csrf_verify('invalid'), 'token CSRF inválido deve ser recusado');

assert_true(safe_return_path('/dashboard') === '/dashboard', 'caminho interno deve ser aceito');
assert_true(safe_return_path('https://evil.example') === '/', 'URL externa deve ser recusada');
assert_true(safe_return_path('//evil.example') === '/', 'URL relativa a host deve ser recusada');
assert_true(safe_return_path("/ok\r\nLocation: https://evil.example") === '/', 'quebra de linha deve ser recusada');

$hashA = keyed_hash('ip', '127.0.0.1');
$hashB = keyed_hash('login_identifier', '127.0.0.1');
assert_true(strlen($hashA) === 64, 'hash deve usar SHA-256');
assert_true($hashA !== $hashB, 'escopos de hash devem ser separados');
assert_true(!str_contains($hashA, '127.0.0.1'), 'hash não deve conter IP bruto');

$fixtureHash = '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO';
assert_true(password_verify('123456', $fixtureHash), 'hash da fixture deve corresponder à senha documentada');

echo "PASS: CSRF, redirecionamento, hashes e fixture de senha validados.\n";
