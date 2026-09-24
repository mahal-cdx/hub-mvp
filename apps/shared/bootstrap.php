<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set(env_value('TZ', 'UTC'));

set_exception_handler(static function (Throwable $error): void {
    error_log($error->__toString());

    if (!headers_sent()) {
        send_security_headers();
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $message = config('debug')
        ? 'Falha interna. Consulte o log usando o request ID ' . request_id() . '.'
        : 'Não foi possível concluir a solicitação.';
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro</title><body><h1>Erro interno</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p></body></html>';
});

send_security_headers();
start_secure_session();
