<section class="auth-shell" aria-labelledby="login-title">
  <aside class="auth-story">
    <div class="brand brand-large">
      <span class="brand-symbol">;3</span>
      <span><strong>Threeebs Hub</strong><small>Administração</small></span>
    </div>
    <div>
      <p class="eyebrow">Operação central</p>
      <h1 id="login-title">Controle o processo completo.</h1>
      <p>Revise projetos, acompanhe vendas, configure pontos e mantenha cada decisão registrada.</p>
    </div>
    <small>Acesso exclusivo para administradores autorizados.</small>
  </aside>

  <div class="auth-panel">
    <form class="auth-card" method="post" action="/login" autocomplete="on">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="return_to" value="/">

      <header>
        <p class="eyebrow">Bem-vindo de volta</p>
        <h2>Acesse o painel</h2>
        <p>Use sua conta administrativa do Hub.</p>
      </header>

      <?php if (is_string($error) && $error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <label class="field">
        <span>E-mail</span>
        <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" inputmode="email" maxlength="190" required autofocus>
      </label>

      <label class="field">
        <span>Senha</span>
        <span class="password-field">
          <input id="password" type="password" name="password" autocomplete="current-password" maxlength="4096" required>
          <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Mostrar</button>
        </span>
      </label>

      <button class="button button-primary button-wide" type="submit">Entrar com segurança</button>
      <p class="form-note">Tentativas são limitadas e registradas para proteger a operação.</p>
    </form>
  </div>
</section>
