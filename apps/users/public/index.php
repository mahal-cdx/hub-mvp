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
    $result = attempt_user_login($email, $password);
    if ($result['ok']) {
        redirect('/');
    }
    render('login', ['title' => 'Entrar', 'error' => $result['message'], 'email' => normalize_email($email), 'user' => null], 401);
}

if ($method === 'POST' && $path === '/logout') {
    require_operational_user();
    require_csrf();
    logout_user();
    redirect('/login');
}

if ($method === 'GET' && $path === '/') {
    $user = require_operational_user();
    $wallet = wallet_summary((int) $user['id']);
    render('dashboard', ['title' => 'Meu ambiente', 'user' => $user, 'wallet' => $wallet]);
}

if ($method === 'GET' && $path === '/leads') {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }
    render('leads', ['title' => 'Cadastro de leads', 'user' => $user, 'leads' => list_captor_leads((int) $user['id']), 'error' => null, 'success' => isset($_GET['saved']), 'values' => []]);
}

if ($method === 'POST' && $path === '/leads') {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }
    require_csrf();
    try {
        create_lead($_POST, $user);
        redirect('/leads?saved=1');
    } catch (InvalidArgumentException $error) {
        render('leads', ['title' => 'Cadastro de leads', 'user' => $user, 'leads' => list_captor_leads((int) $user['id']), 'error' => $error->getMessage(), 'success' => false, 'values' => $_POST], 422);
    }
}

if ($method === 'GET' && $path === '/dev') {
    $user = require_operational_user();
    if (!user_has_role($user, 'desenvolvedor')) {
        render_error(403, 'Seu usuário não possui a função de desenvolvedor.');
    }
    render('developer', ['title' => 'Desenvolvimento', 'user' => $user, 'work' => list_developer_work((int) $user['id']), 'error' => null, 'success' => isset($_GET['saved'])]);
}

if ($method === 'POST' && $path === '/dev/claim') {
    $user = require_operational_user();
    if (!user_has_role($user, 'desenvolvedor')) {
        render_error(403, 'Seu usuário não possui a função de desenvolvedor.');
    }
    require_csrf();
    try {
        claim_opportunity((string) ($_POST['opportunity_uuid'] ?? ''), $user);
        redirect('/dev?saved=1');
    } catch (InvalidArgumentException $error) {
        render('developer', ['title' => 'Desenvolvimento', 'user' => $user, 'work' => list_developer_work((int) $user['id']), 'error' => $error->getMessage(), 'success' => false], 422);
    }
}

if ($method === 'POST' && $path === '/dev/submit') {
    $user = require_operational_user();
    if (!user_has_role($user, 'desenvolvedor')) {
        render_error(403, 'Seu usuário não possui a função de desenvolvedor.');
    }
    require_csrf();
    try {
        submit_project($_POST, $user);
        redirect('/dev?saved=1');
    } catch (InvalidArgumentException $error) {
        render('developer', ['title' => 'Desenvolvimento', 'user' => $user, 'work' => list_developer_work((int) $user['id']), 'error' => $error->getMessage(), 'success' => false], 422);
    }
}

if ($method === 'GET' && $path === '/sales') {
    $user = require_operational_user();
    if (!user_has_role($user, 'comercial')) {
        render_error(403, 'Seu usuário não possui a função comercial.');
    }
    render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'error' => null, 'success' => isset($_GET['saved'])]);
}

if ($method === 'POST' && $path === '/sales/claim') {
    $user = require_operational_user();
    if (!user_has_role($user, 'comercial')) {
        render_error(403, 'Seu usuário não possui a função comercial.');
    }
    require_csrf();
    try {
        claim_sale((string) ($_POST['sale_uuid'] ?? ''), $user);
        redirect('/sales?saved=1');
    } catch (InvalidArgumentException $error) {
        render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'error' => $error->getMessage(), 'success' => false], 422);
    }
}

if ($method === 'POST' && $path === '/sales/contact') {
    $user = require_operational_user();
    if (!user_has_role($user, 'comercial')) {
        render_error(403, 'Seu usuário não possui a função comercial.');
    }
    require_csrf();
    try {
        record_sale_interaction($_POST, $user);
        redirect('/sales?saved=1');
    } catch (InvalidArgumentException $error) {
        render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'error' => $error->getMessage(), 'success' => false], 422);
    }
}

if (in_array($path, ['/login','/logout','/','/leads','/dev','/dev/claim','/dev/submit','/sales','/sales/claim','/sales/contact'], true)) {
    header('Allow: GET, POST');
    render_error(405, 'Método não permitido.');
}

render_error(404, 'Página não encontrada.');
