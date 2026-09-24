<?php
$pageTitle = ($title ?? config('name')) . ' · ' . config('name');
$authenticated = is_array($user ?? null);
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#07100f">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script defer src="/assets/js/admin.js"></script>
</head>
<body class="<?= $authenticated ? 'app-page' : 'auth-page' ?>">
  <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
  <?php if ($authenticated): ?>
    <header class="topbar">
      <a class="brand" href="/" aria-label="Threeebs Hub">
        <span class="brand-symbol">;3</span>
        <span><strong>Threeebs Hub</strong><small>Administração</small></span>
      </a>
      <div class="user-menu">
        <span><strong><?= e($user['name']) ?></strong><small>Administrador</small></span>
        <form method="post" action="/logout">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <button class="button button-secondary" type="submit">Sair</button>
        </form>
      </div>
    </header>
  <?php endif; ?>

  <main id="main-content" class="<?= $authenticated ? 'admin-main' : 'auth-main' ?>" tabindex="-1">
    <?= $content ?>
  </main>

  <footer class="footer">
    <span>Threeebs Hub MVP</span>
    <span>Request <?= e(request_id()) ?></span>
  </footer>
</body>
</html>
