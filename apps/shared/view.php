<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function route_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $normalized = '/' . trim((string) $path, '/');
    return $normalized === '/' ? '/' : rtrim($normalized, '/');
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . safe_return_path($path), true, $status);
    exit;
}

function render(string $view, array $data = [], int $status = 200): never
{
    http_response_code($status);
    extract($data, EXTR_SKIP);

    ob_start();
    require config('app_root') . '/views/' . $view . '.php';
    $content = (string) ob_get_clean();

    require config('app_root') . '/views/layout.php';
    exit;
}

function render_error(int $status, string $message): never
{
    render('error', [
        'title' => 'Erro',
        'message' => $message,
        'user' => auth_user(),
    ], $status);
}
