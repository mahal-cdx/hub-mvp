<?php

declare(strict_types=1);

require '/var/www/shared/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = route_path();

if ($method === 'POST') {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $configuredPostMax = trim((string) ini_get('post_max_size'));
    $unit = strtolower(substr($configuredPostMax, -1));
    $postMaxBytes = (int) $configuredPostMax;
    if ($unit === 'g') {
        $postMaxBytes *= 1024 * 1024 * 1024;
    } elseif ($unit === 'm') {
        $postMaxBytes *= 1024 * 1024;
    } elseif ($unit === 'k') {
        $postMaxBytes *= 1024;
    }

    if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes && $_POST === []) {
        render_error(413, 'O envio ultrapassou o limite permitido. Envie no máximo 10 imagens de 25 MB cada.');
    }
}

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

if ($method === 'GET' && preg_match('#^/references/([0-9a-f-]{36})$#', $path, $matches)) {
    $user = require_operational_user();
    send_reference_image($matches[1], $user, isset($_GET['download']));
}

if ($method === 'GET' && in_array($path, ['/leads','/leads/new'], true)) {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }

    $editLead = null;
    $error = null;
    $editUuid = is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '';
    if ($editUuid !== '') {
        $editLead = load_editable_lead($editUuid, (int) $user['id']);
        if ($editLead === null) {
            $error = 'Este cadastro não está mais aberto para edição.';
        }
    }
    $showForm = $path === '/leads/new' || $editUuid !== '';

    render('leads', [
        'title' => $showForm ? ($editLead !== null ? 'Editar lead' : 'Adicionar lead') : 'Meus cadastros',
        'user' => $user,
        'leads' => list_captor_leads((int) $user['id']),
        'editLead' => $editLead,
        'showForm' => $showForm,
        'error' => $error,
        'success' => isset($_GET['saved']),
        'values' => $editLead ?? [],
    ]);
}

if ($method === 'POST' && $path === '/leads') {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }
    require_csrf();
    try {
        $uploads = is_array($_FILES['reference_images'] ?? null) ? $_FILES['reference_images'] : [];
        create_lead($_POST, $user, $uploads);
        redirect('/leads?saved=1');
    } catch (InvalidArgumentException $error) {
        render('leads', [
            'title' => 'Cadastro de leads',
            'user' => $user,
            'leads' => list_captor_leads((int) $user['id']),
            'editLead' => null,
            'error' => $error->getMessage(),
            'success' => false,
            'values' => $_POST,
            'showForm' => true,
        ], 422);
    }
}

if ($method === 'POST' && $path === '/leads/update') {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }
    require_csrf();
    try {
        $uploads = is_array($_FILES['reference_images'] ?? null) ? $_FILES['reference_images'] : [];
        update_lead($_POST, $user, $uploads);
        redirect('/leads?saved=1');
    } catch (InvalidArgumentException $error) {
        $editLead = load_editable_lead((string) ($_POST['lead_uuid'] ?? ''), (int) $user['id']);
        render('leads', [
            'title' => 'Cadastro de leads',
            'user' => $user,
            'leads' => list_captor_leads((int) $user['id']),
            'editLead' => $editLead,
            'error' => $error->getMessage(),
            'success' => false,
            'values' => $_POST + ($editLead ?? []),
            'showForm' => true,
        ], 422);
    }
}

if ($method === 'POST' && $path === '/leads/references/delete') {
    $user = require_operational_user();
    if (!user_has_role($user, 'captador')) {
        render_error(403, 'Seu usuário não possui a função de captador.');
    }
    require_csrf();
    try {
        delete_lead_reference((string) ($_POST['reference_uuid'] ?? ''), $user);
        redirect('/leads?edit=' . rawurlencode((string) ($_POST['lead_uuid'] ?? '')));
    } catch (InvalidArgumentException $error) {
        render('leads', [
            'title' => 'Cadastro de leads',
            'user' => $user,
            'leads' => list_captor_leads((int) $user['id']),
            'editLead' => null,
            'error' => $error->getMessage(),
            'success' => false,
            'values' => [],
        ], 422);
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
    render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'extraProducts' => list_active_extra_products(), 'error' => null, 'success' => isset($_GET['saved'])]);
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
        render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'extraProducts' => list_active_extra_products(), 'error' => $error->getMessage(), 'success' => false], 422);
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
        render('sales', ['title' => 'Vendas', 'user' => $user, 'sales' => list_commercial_sales((int) $user['id']), 'extraProducts' => list_active_extra_products(), 'error' => $error->getMessage(), 'success' => false], 422);
    }
}

if ($method === 'GET' && $path === '/wallet') {
    $user = require_operational_user();
    render('wallet', [
        'title' => 'Pontos e saques',
        'user' => $user,
        'wallet' => wallet_summary((int) $user['id']),
        'quote' => current_point_quote(),
        'history' => list_user_point_history((int) $user['id']),
        'withdrawals' => list_user_withdrawals((int) $user['id']),
        'requestToken' => uuid_v4(),
        'error' => null,
        'success' => isset($_GET['saved']),
    ]);
}

if ($method === 'POST' && $path === '/wallet/withdraw') {
    $user = require_operational_user();
    require_csrf();
    try {
        request_withdrawal($_POST, $user);
        redirect('/wallet?saved=1');
    } catch (InvalidArgumentException $error) {
        render('wallet', [
            'title' => 'Pontos e saques',
            'user' => $user,
            'wallet' => wallet_summary((int) $user['id']),
            'quote' => current_point_quote(),
            'history' => list_user_point_history((int) $user['id']),
            'withdrawals' => list_user_withdrawals((int) $user['id']),
            'requestToken' => (string) ($_POST['request_token'] ?? uuid_v4()),
            'error' => $error->getMessage(),
            'success' => false,
        ], 422);
    }
}

if (in_array($path, ['/login','/logout','/','/leads','/leads/new','/leads/update','/leads/references/delete','/dev','/dev/claim','/dev/submit','/sales','/sales/claim','/sales/contact','/wallet','/wallet/withdraw'], true)) {
    header('Allow: GET, POST');
    render_error(405, 'Método não permitido.');
}

render_error(404, 'Página não encontrada.');
