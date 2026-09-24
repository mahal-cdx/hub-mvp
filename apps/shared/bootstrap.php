<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user_management.php';
require_once __DIR__ . '/lead_media.php';
require_once __DIR__ . '/finance.php';
require_once __DIR__ . '/workflow.php';

date_default_timezone_set(env_value('TZ', 'UTC'));

set_exception_handler(static function (Throwable $error): void {
    error_log($error->__toString());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, private');
        header('X-Request-ID: ' . request_id());
    }

    $message = 'Não foi possível concluir a solicitação. Request ID: ' . request_id() . '.';
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro</title><body><h1>Erro interno</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p></body></html>';
});

send_security_headers();
start_secure_session();
