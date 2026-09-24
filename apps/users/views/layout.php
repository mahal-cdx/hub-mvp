<?php
$pageTitle = ($title ?? config('name')) . ' · ' . config('name');
$authenticated = is_array($user ?? null);
$currentPath = route_path();
$cssVersion = (string) (@filemtime('/var/www/app/public/assets/css/user.css') ?: 1);
$jsVersion = (string) (@filemtime('/var/www/app/public/assets/js/user.js') ?: 1);
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#07100f">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= e($cssVersion) ?>">
  <script defer src="/assets/js/user.js?v=<?= e($jsVersion) ?>"></script>
</head>
<body class="<?= $authenticated ? 'app-page' : 'auth-page' ?>">
<a class="skip-link" href="#main-content">Pular para o conteúdo</a>
<?php if ($authenticated): ?>
<header class="topbar">
  <a class="brand" href="/"><span class="brand-symbol">;3</span><span><strong>Threeebs Hub</strong><small>Operação</small></span></a>
  <nav class="main-nav" aria-label="Ambientes por função">
    <a class="<?= $currentPath === '/' ? 'active' : '' ?>" href="/">Início</a>
    <?php if (user_has_role($user, 'captador')): ?><a class="<?= str_starts_with($currentPath, '/leads') ? 'active' : '' ?>" href="/leads">Leads</a><?php endif; ?>
    <?php if (user_has_role($user, 'desenvolvedor')): ?><a class="<?= str_starts_with($currentPath, '/dev') ? 'active' : '' ?>" href="/dev">Desenvolvimento</a><?php endif; ?>
    <?php if (user_has_role($user, 'comercial')): ?><a class="<?= str_starts_with($currentPath, '/sales') ? 'active' : '' ?>" href="/sales">Vendas</a><?php endif; ?>
    <a class="<?= str_starts_with($currentPath, '/wallet') ? 'active' : '' ?>" href="/wallet">Pontos e saques</a>
  </nav>
  <div class="user-menu"><span><strong><?= e($user['name']) ?></strong><small><?= e(implode(' · ', $user['roles'])) ?></small></span><form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="button button-secondary" type="submit">Sair</button></form></div>
</header>
<?php endif; ?>
<main id="main-content" class="<?= $authenticated ? 'app-main' : 'auth-main' ?>" tabindex="-1"><?= $content ?></main>
<footer class="footer"><span>Threeebs Hub MVP</span><span>Request <?= e(request_id()) ?></span></footer>
</body>
</html>
