<?php

declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);
    return $value === false || $value === ''
        ? $default
        : filter_var($value, FILTER_VALIDATE_BOOL);
}

function config(?string $key = null): mixed
{
    static $values;

    if ($values === null) {
        $environment = env_value('APP_ENV', 'development');
        $appKey = env_value('APP_KEY');

        if (strlen($appKey) < 32) {
            throw new RuntimeException('APP_KEY deve possuir pelo menos 32 caracteres.');
        }

        $values = [
            'name' => env_value('APP_NAME', 'Threeebs Hub'),
            'environment' => $environment,
            'debug' => $environment !== 'production' && env_bool('APP_DEBUG', false),
            'url' => rtrim(env_value('APP_URL', 'http://localhost:6041'), '/'),
            'app_key' => $appKey,
            'app_root' => env_value('ADMIN_APP_ROOT', '/var/www/app'),
            'session_secure' => env_bool('SESSION_SECURE', $environment === 'production'),
            'session_idle_timeout' => max(300, (int) env_value('SESSION_IDLE_TIMEOUT', '1800')),
            'session_absolute_timeout' => max(1800, (int) env_value('SESSION_ABSOLUTE_TIMEOUT', '28800')),
            'trust_proxy_headers' => env_bool('TRUST_PROXY_HEADERS', false),
            'db' => [
                'host' => env_value('DB_HOST', 'mysql'),
                'port' => (int) env_value('DB_PORT', '3306'),
                'database' => env_value('DB_DATABASE', 'hub'),
                'user' => env_value('DB_USER', 'hub_app'),
                'password' => env_value('DB_PASSWORD'),
            ],
        ];
    }

    return $key === null ? $values : ($values[$key] ?? null);
}
