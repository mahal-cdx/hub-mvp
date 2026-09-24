<?php

declare(strict_types=1);

require '/var/www/shared/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = route_path();

if ($method === 'GET' && $path === '/login') {
    if (auth_check()) {
        redirect('/');
    }
    render('login', ['title' => 'Entrar', 'error' => null, 'email' => '', 'user' => null]);
}

if ($method === 'POST' && $path === '/login') {
    require_csrf();
    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $result = attempt_admin_login($email, $password);
    if ($result['ok']) {
        redirect('/');
    }
    render('login', [
        'title' => 'Entrar',
        'error' => $result['message'],
        'email' => normalize_email($email),
        'user' => null,
    ], 401);
}

if ($method === 'POST' && $path === '/logout') {
    require_admin();
    require_csrf();
    logout_user();
    redirect('/login');
}

if ($method === 'GET' && $path === '/') {
    $user = require_admin();
    $connection = db();
    $metrics = [
        'users' => (int) $connection->query("SELECT COUNT(*) FROM usuarios WHERE status = 'ativo'")->fetchColumn(),
        'leads' => (int) $connection->query("SELECT COUNT(*) FROM leads WHERE status <> 'descartado'")->fetchColumn(),
        'revisoes' => (int) $connection->query("SELECT COUNT(*) FROM projeto_revisoes WHERE decisao = 'pendente'")->fetchColumn(),
        'vendas' => (int) $connection->query("SELECT COUNT(*) FROM vendas WHERE status IN ('disponivel','em_atendimento','aguardando_pagamento')")->fetchColumn(),
    ];
    $events = $connection->query(
        'SELECT acao, entidade_tipo, ocorrido_em FROM eventos_auditoria ORDER BY ocorrido_em DESC LIMIT 8'
    )->fetchAll();
    render('dashboard', compact('user', 'metrics', 'events') + ['title' => 'Visão geral']);
}

if ($method === 'GET' && $path === '/users') {
    $user = require_admin();
    render('users', [
        'title' => 'Usuários',
        'user' => $user,
        'users' => list_managed_users(),
        'availableRoles' => assignable_roles(),
        'error' => null,
        'success' => isset($_GET['saved']),
    ]);
}

if ($method === 'GET' && $path === '/users/new') {
    $user = require_admin();
    render('user-new', [
        'title' => 'Novo usuário',
        'user' => $user,
        'availableRoles' => assignable_roles(),
        'error' => null,
        'values' => [],
    ]);
}

if ($method === 'POST' && $path === '/users/new') {
    $user = require_admin();
    require_csrf();
    try {
        create_managed_user($_POST, $user);
        redirect('/users?saved=1');
    } catch (InvalidArgumentException $error) {
        render('user-new', [
            'title' => 'Novo usuário',
            'user' => $user,
            'availableRoles' => assignable_roles(),
            'error' => $error->getMessage(),
            'values' => $_POST,
        ], 422);
    }
}

if ($method === 'POST' && preg_match('#^/users/([0-9a-f-]{36})/roles$#', $path, $matches)) {
    $user = require_admin();
    require_csrf();
    try {
        update_managed_user($matches[1], $_POST, $user);
        redirect('/users?saved=1');
    } catch (InvalidArgumentException $error) {
        render('users', [
            'title' => 'Usuários',
            'user' => $user,
            'users' => list_managed_users(),
            'availableRoles' => assignable_roles(),
            'error' => $error->getMessage(),
            'success' => false,
        ], 422);
    }
}

if ($method === 'GET' && $path === '/projects') {
    $user = require_admin();
    render('projects', [
        'title' => 'Revisão de projetos',
        'user' => $user,
        'reviews' => list_pending_project_reviews(),
        'error' => null,
        'success' => isset($_GET['saved']),
    ]);
}

if ($method === 'POST' && $path === '/projects/review') {
    $user = require_admin();
    require_csrf();
    try {
        review_project($_POST, $user);
        redirect('/projects?saved=1');
    } catch (InvalidArgumentException $error) {
        render('projects', [
            'title' => 'Revisão de projetos',
            'user' => $user,
            'reviews' => list_pending_project_reviews(),
            'error' => $error->getMessage(),
            'success' => false,
        ], 422);
    }
}

if (in_array($path, ['/login','/logout','/users','/users/new','/projects','/projects/review','/'], true)) {
    header('Allow: GET, POST');
    render_error(405, 'Método não permitido.');
}

render_error(404, 'Página não encontrada.');
