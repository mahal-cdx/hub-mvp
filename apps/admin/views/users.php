<section class="page-heading">
  <div><p class="eyebrow">Equipe</p><h1>Usuários e funções</h1><p>Defina quem cadastra leads, desenvolve projetos e trabalha as vendas.</p></div>
  <a class="button button-primary" href="/users/new">Novo usuário</a>
</section>

<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" role="status">Alterações salvas.</div><?php endif; ?>

<section class="stack">
  <?php foreach ($users as $managed): $selected = array_filter(explode(',', (string) $managed['roles'])); ?>
    <article class="panel user-row">
      <div class="user-identity">
        <span class="avatar"><?= e(strtoupper(substr($managed['nome'], 0, 1))) ?></span>
        <div><strong><?= e($managed['nome']) ?></strong><small><?= e($managed['email']) ?></small></div>
      </div>
      <form class="role-form" method="post" action="/users/<?= e($managed['uuid']) ?>/roles">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="role-options">
          <?php foreach ($availableRoles as $key => $label): ?>
            <label class="check-chip"><input type="checkbox" name="roles[]" value="<?= e($key) ?>" <?= in_array($key, $selected, true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
          <?php endforeach; ?>
        </div>
        <label class="compact-field"><span>Status</span><select name="status">
          <?php foreach (['ativo' => 'Ativo', 'bloqueado' => 'Bloqueado', 'inativo' => 'Inativo'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $managed['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select></label>
        <button class="button button-secondary" type="submit">Salvar</button>
      </form>
    </article>
  <?php endforeach; ?>
</section>
