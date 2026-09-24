<section class="auth-shell" aria-labelledby="login-title">
  <aside class="auth-story">
    <div class="brand brand-large"><span class="brand-symbol">;3</span><span><strong>Threeebs Hub</strong><small>Operação</small></span></div>
    <div><p class="eyebrow">Ambiente de trabalho</p><h1 id="login-title">Da oportunidade à venda.</h1><p>Acesse as tarefas liberadas para suas funções de captação, desenvolvimento e comercial.</p></div>
    <small>Use somente sua conta individual. Todas as ações são registradas.</small>
  </aside>
  <div class="auth-panel">
    <form class="auth-card" method="post" action="/login">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <header><p class="eyebrow">Threeebs Hub</p><h2>Entrar</h2><p>Use a conta criada pelo administrador.</p></header>
      <?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <label class="field"><span>E-mail</span><input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" maxlength="190" required autofocus></label>
      <label class="field"><span>Senha</span><span class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" maxlength="4096" required><button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Mostrar</button></span></label>
      <button class="button button-primary button-wide" type="submit">Entrar com segurança</button>
    </form>
  </div>
</section>
