<?php

declare(strict_types=1);

function uuid_v4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function request_id(): string
{
    static $id;
    return $id ??= uuid_v4();
}

function is_secure_request(): bool
{
    if (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') {
        return true;
    }

    return config('trust_proxy_headers')
        && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'");
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Request-ID: ' . request_id());

    if (is_secure_request()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.gc_maxlifetime', (string) config('session_absolute_timeout'));

    $context = preg_replace('/[^a-z0-9_]/', '', strtolower((string) config('context'))) ?: 'app';
    session_name('hub_' . $context . '_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (bool) config('session_secure'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $now = time();
    $created = (int) ($_SESSION['_created_at'] ?? $now);
    $lastActivity = (int) ($_SESSION['_last_activity'] ?? $now);

    if (($now - $lastActivity) > config('session_idle_timeout')
        || ($now - $created) > config('session_absolute_timeout')) {
        clear_session();
        session_start();
        $created = $now;
    }

    $expectedAgentHash = keyed_hash('user_agent', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512));
    $storedAgentHash = $_SESSION['_user_agent_hash'] ?? null;
    if (is_string($storedAgentHash) && !hash_equals($storedAgentHash, $expectedAgentHash)) {
        clear_session();
        session_start();
        $created = $now;
    }

    $_SESSION['_created_at'] = $created;
    $_SESSION['_last_activity'] = $now;
    $_SESSION['_user_agent_hash'] = $expectedAgentHash;

    $rotatedAt = (int) ($_SESSION['_rotated_at'] ?? $created);
    if (($now - $rotatedAt) >= 900) {
        session_regenerate_id(true);
        $_SESSION['_rotated_at'] = $now;
    } else {
        $_SESSION['_rotated_at'] = $rotatedAt;
    }
}

function clear_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parameters['path'],
            'domain' => $parameters['domain'],
            'secure' => $parameters['secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    session_destroy();
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_verify(mixed $token): bool
{
    $stored = $_SESSION['_csrf'] ?? null;
    if (!is_string($stored) || !is_string($token) || !hash_equals($stored, $token)) {
        return false;
    }

    unset($_SESSION['_csrf']);
    return true;
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function keyed_hash(string $scope, string $value): string
{
    return hash_hmac('sha256', $scope . "\0" . $value, (string) config('app_key'));
}

function client_ip(): string
{
    if (config('trust_proxy_headers')) {
        $forwarded = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
            return $forwarded;
        }
    }

    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

function safe_return_path(mixed $value, string $fallback = '/'): string
{
    if (!is_string($value) || $value === '' || $value[0] !== '/') {
        return $fallback;
    }

    if (str_starts_with($value, '//') || str_contains($value, "\r") || str_contains($value, "\n")) {
        return $fallback;
    }

    $path = parse_url($value, PHP_URL_PATH);
    return is_string($path) && str_starts_with($path, '/') ? $path : $fallback;
}

function require_csrf(): void
{
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        render_error(403, 'A sessão do formulário expirou. Atualize a página e tente novamente.');
    }
}
