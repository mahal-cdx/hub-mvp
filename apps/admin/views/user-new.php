<section class="page-heading">
  <div><p class="eyebrow">Equipe</p><h1>Criar usuário</h1><p>A conta poderá acessar somente os ambientes correspondentes às funções selecionadas.</p></div>
  <a class="button button-secondary" href="/users">Voltar</a>
</section>

<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<form class="panel form-grid" method="post" action="/users/new">
  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
  <label class="field"><span>Nome</span><input name="name" maxlength="150" value="<?= e($values['name'] ?? '') ?>" required></label>
  <label class="field"><span>E-mail</span><input type="email" name="email" maxlength="190" value="<?= e($values['email'] ?? '') ?>" autocomplete="off" required></label>
  <label class="field"><span>Senha temporária</span><input type="password" name="password" minlength="12" maxlength="4096" autocomplete="new-password" required></label>
  <label class="field"><span>Confirmar senha</span><input type="password" name="password_confirmation" minlength="12" maxlength="4096" autocomplete="new-password" required></label>
  <fieldset class="role-fieldset"><legend>Funções</legend>
    <?php foreach ($availableRoles as $key => $label): ?>
      <label class="check-chip"><input type="checkbox" name="roles[]" value="<?= e($key) ?>" <?= in_array($key, (array) ($values['roles'] ?? []), true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
    <?php endforeach; ?>
  </fieldset>
  <div class="form-actions"><button class="button button-primary" type="submit">Criar usuário</button></div>
</form>
