<?php

declare(strict_types=1);

require '/var/www/shared/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = route_path();

if ($method === 'GET' && $path === '/login') {
    if (auth_check()) {
        redirect('/');
    }

    render('login', [
        'title' => 'Entrar',
        'error' => null,
        'email' => '',
        'user' => null,
    ]);
}

if ($method === 'POST' && $path === '/login') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        render_error(403, 'A sessão do formulário expirou. Atualize a página e tente novamente.');
    }

    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $result = attempt_admin_login($email, $password);

    if ($result['ok']) {
        redirect(safe_return_path($_POST['return_to'] ?? '/'));
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

    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        render_error(403, 'A sessão expirou. Atualize a página e tente novamente.');
    }

    logout_admin();
    redirect('/login');
}

if ($method === 'GET' && $path === '/') {
    $user = require_admin();
    $connection = db();

    $metrics = [
        'leads' => (int) $connection->query("SELECT COUNT(*) FROM leads WHERE status <> 'descartado'")->fetchColumn(),
        'revisoes' => (int) $connection->query("SELECT COUNT(*) FROM projeto_revisoes WHERE decisao = 'pendente'")->fetchColumn(),
        'vendas' => (int) $connection->query("SELECT COUNT(*) FROM vendas WHERE status IN ('disponivel','em_atendimento','aguardando_pagamento')")->fetchColumn(),
        'saques' => (int) $connection->query("SELECT COUNT(*) FROM solicitacoes_saque WHERE status IN ('solicitado','em_analise','aprovado')")->fetchColumn(),
    ];

    $events = $connection->query(
        'SELECT acao, entidade_tipo, ocorrido_em
         FROM eventos_auditoria
         ORDER BY ocorrido_em DESC
         LIMIT 8'
    )->fetchAll();

    render('dashboard', [
        'title' => 'Visão geral',
        'user' => $user,
        'metrics' => $metrics,
        'events' => $events,
    ]);
}

if ($path === '/login' || $path === '/logout' || $path === '/') {
    header('Allow: GET, POST');
    render_error(405, 'Método não permitido.');
}

render_error(404, 'Página não encontrada.');
