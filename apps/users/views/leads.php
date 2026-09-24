<?php
$isEditing = is_array($editLead ?? null);
$formAction = $isEditing ? '/leads/update' : '/leads';
$formTitle = $isEditing ? 'Editar cadastro aberto' : 'Cadastrar novo lead';
?>
<section class="page-head">
  <div>
    <p class="eyebrow">Captação</p>
    <h1>Cadastro de leads</h1>
    <p>Complete o máximo de informações possível. A temperatura é calculada automaticamente pela riqueza do cadastro.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert alert-success" role="status">Cadastro atualizado e fluxo sincronizado.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="lead-layout">
  <section class="workflow-section lead-form-section">
    <header class="section-heading">
      <div><p class="section-kicker"><?= $isEditing ? 'Cadastro aberto' : 'Nova oportunidade' ?></p><h2><?= e($formTitle) ?></h2><p><?= $isEditing ? 'Você pode enriquecer os dados enquanto o lead estiver na fila.' : 'O cadastro entra automaticamente na fila de desenvolvimento.' ?></p></div>
      <?php if ($isEditing): ?><a class="button button-secondary" href="/leads">Cancelar edição</a><?php endif; ?>
    </header>

    <form class="lead-form" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" data-lead-form data-existing-references="<?= $isEditing && ($editLead['references'] ?? []) !== [] ? '1' : '0' ?>">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <?php if ($isEditing): ?><input type="hidden" name="lead_uuid" value="<?= e($editLead['uuid']) ?>"><?php endif; ?>

      <div class="completeness-card">
        <div><span>Completude estimada</span><strong data-completeness-value>0%</strong></div>
        <div class="completeness-track"><i data-completeness-bar></i></div>
        <small data-temperature-label>Temperatura calculada: frio</small>
      </div>

      <div class="form-grid">
        <label class="field field-full"><span>Nome *</span><input name="name" maxlength="160" value="<?= e((string) ($values['name'] ?? '')) ?>" required data-completeness-weight="10"></label>
        <label class="field"><span>E-mail</span><input type="email" name="email" maxlength="190" value="<?= e((string) ($values['email'] ?? '')) ?>" data-completeness-weight="15"></label>
        <label class="field"><span>WhatsApp</span><input name="whatsapp" maxlength="80" value="<?= e((string) ($values['whatsapp'] ?? '')) ?>" data-completeness-weight="15"></label>
        <label class="field"><span>Instagram</span><input name="instagram" maxlength="190" value="<?= e((string) ($values['instagram'] ?? '')) ?>" data-completeness-weight="10"></label>
        <label class="field"><span>Origem</span><input name="origin" maxlength="120" placeholder="Instagram, indicação..." value="<?= e((string) ($values['origin'] ?? '')) ?>" data-completeness-weight="5"></label>
        <label class="field field-full"><span>Link da bio</span><input type="url" name="bio_url" maxlength="500" placeholder="https://..." value="<?= e((string) ($values['bio_url'] ?? '')) ?>" data-completeness-weight="10"></label>
        <label class="field field-full"><span>Bio / negócio</span><textarea name="bio" rows="4" data-completeness-weight="15"><?= e((string) ($values['bio'] ?? '')) ?></textarea></label>
        <label class="field field-full"><span>Observações e referências textuais</span><textarea name="notes" rows="4" data-completeness-weight="10"><?= e((string) ($values['notes'] ?? '')) ?></textarea></label>
      </div>

      <section class="reference-uploader">
        <div><p class="section-kicker">Referências visuais</p><h3>Imagens do projeto</h3><p>Selecione várias imagens ou copie uma imagem e cole na área abaixo.</p></div>
        <label class="file-picker">
          <span>Selecionar imagens</span>
          <input type="file" name="reference_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-reference-input>
        </label>
        <div class="paste-zone" tabindex="0" data-image-paste>
          <strong>Cole imagens aqui</strong>
          <span>Use Ctrl+V ou ⌘+V. Máximo de 12 arquivos, 10 MB cada.</span>
        </div>
        <div class="reference-preview" data-reference-preview></div>
      </section>

      <?php if ($isEditing && ($editLead['references'] ?? []) !== []): ?>
        <section class="existing-references">
          <h3>Referências já salvas</h3>
          <div class="reference-grid">
            <?php foreach ($editLead['references'] as $reference): ?>
              <article class="reference-tile">
                <img src="/references/<?= e($reference['uuid']) ?>" alt="<?= e($reference['nome_original']) ?>">
                <div><strong><?= e($reference['nome_original']) ?></strong><small><?= e(number_format((int) $reference['tamanho_bytes'] / 1048576, 2, ',', '.')) ?> MB</small></div>
                <button class="button button-danger button-small" type="submit" form="delete-reference-<?= e($reference['uuid']) ?>">Remover</button>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <button class="button button-primary button-wide" type="submit"><?= $isEditing ? 'Salvar informações' : 'Cadastrar lead' ?></button>
    </form>

    <?php if ($isEditing): ?>
      <?php foreach (($editLead['references'] ?? []) as $reference): ?>
        <form id="delete-reference-<?= e($reference['uuid']) ?>" method="post" action="/leads/references/delete">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="lead_uuid" value="<?= e($editLead['uuid']) ?>">
          <input type="hidden" name="reference_uuid" value="<?= e($reference['uuid']) ?>">
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <section class="workflow-section lead-list-section">
    <header class="section-heading">
      <div><p class="section-kicker">Acompanhamento</p><h2>Meus cadastros</h2><p>Status atualizado conforme o lead avança no processo.</p></div>
      <span class="count-badge"><?= e((string) count($leads)) ?> leads</span>
    </header>

    <div class="lead-list">
      <?php if ($leads === []): ?><div class="empty-state"><strong>Nenhum lead cadastrado</strong><span>O primeiro cadastro aparecerá aqui.</span></div><?php endif; ?>
      <?php foreach ($leads as $lead): ?>
        <article class="lead-row">
          <div class="lead-row-main">
            <span class="status status-<?= e($lead['workflow_status']) ?>"><?= e($lead['workflow_label']) ?></span>
            <h3><?= e($lead['nome']) ?></h3>
            <p><?= e($lead['contatos'] ?? 'Sem contato') ?></p>
            <div class="lead-meta">
              <span><?= e((string) round((float) ($lead['completude'] ?? 0))) ?>% completo</span>
              <span>Temperatura: <?= e($lead['temperatura']) ?></span>
            </div>
          </div>
          <div class="lead-row-action">
            <?php if ($lead['editable']): ?><a class="button button-secondary" href="/leads?edit=<?= e($lead['uuid']) ?>">Editar e enriquecer</a><?php else: ?><span class="locked-label">Cadastro bloqueado pelo fluxo</span><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
