<section class="page-head">
  <div>
    <p class="eyebrow">Desenvolvimento</p>
    <h1>Desenvolvimento</h1>
    <p>Assuma uma oportunidade, consulte o briefing protegido e envie o preview para aprovação.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert alert-success" role="status">Ação concluída.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<section class="workflow-section" aria-labelledby="dev-opportunities-title">
  <header class="section-heading">
    <div>
      <p class="section-kicker">Fila aberta</p>
      <h2 id="dev-opportunities-title">Oportunidades</h2>
      <p>Os dados do lead e o briefing são liberados somente depois que você assumir.</p>
    </div>
    <span class="count-badge"><?= e((string) count($work['open'])) ?> disponíveis</span>
  </header>

  <div class="vertical-list">
    <?php if ($work['open'] === []): ?>
      <div class="empty-state"><strong>Fila concluída</strong><span>Nenhuma oportunidade disponível agora.</span></div>
    <?php endif; ?>

    <?php foreach ($work['open'] as $index => $item): ?>
      <article class="opportunity-row">
        <div class="opportunity-index" aria-hidden="true"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></div>
        <div class="opportunity-copy">
          <span class="status status-neutral">Disponível</span>
          <h3>Nova oportunidade de desenvolvimento</h3>
          <p>Lead, contatos, referências e briefing protegidos até a atribuição.</p>
          <small>Entrada na fila: <?= e($item['created_at']) ?></small>
        </div>
        <form class="row-action" method="post" action="/dev/claim">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="opportunity_uuid" value="<?= e($item['uuid']) ?>">
          <button class="button button-primary" type="submit">Assumir oportunidade</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="workflow-section" aria-labelledby="my-projects-title">
  <header class="section-heading">
    <div>
      <p class="section-kicker">Área de trabalho</p>
      <h2 id="my-projects-title">Meus projetos</h2>
      <p>Abra um projeto para consultar o briefing e editar o envio.</p>
    </div>
    <span class="count-badge"><?= e((string) count($work['mine'])) ?> projetos</span>
  </header>

  <div class="accordion-list">
    <?php if ($work['mine'] === []): ?>
      <div class="empty-state"><strong>Nenhum projeto atribuído</strong><span>As oportunidades assumidas aparecerão aqui.</span></div>
    <?php endif; ?>

    <?php foreach ($work['mine'] as $item): ?>
      <?php
        $projectStatus = (string) ($item['projeto_status'] ?? $item['status']);
        $canEdit = in_array($item['status'], ['assumida', 'em_desenvolvimento', 'ajustes'], true);
        $contacts = array_values(array_filter(explode(' | ', (string) ($item['contatos'] ?? ''))));
      ?>
      <details class="accordion-card">
        <summary>
          <span class="accordion-main">
            <span class="status"><?= e(str_replace('_', ' ', $projectStatus)) ?></span>
            <strong><?= e($item['projeto_nome'] ?? ('Projeto para ' . $item['lead_nome'])) ?></strong>
            <small><?= e($item['lead_nome']) ?> · assumido em <?= e($item['assumida_em'] ?? '—') ?></small>
          </span>
          <span class="accordion-toggle"><span class="when-closed">Ver projeto</span><span class="when-open">Fechar</span><i aria-hidden="true"></i></span>
        </summary>

        <div class="accordion-body">
          <section class="briefing-block">
            <header><p class="section-kicker">Briefing liberado</p><h3><?= e($item['lead_nome']) ?></h3></header>
            <?php if ($contacts !== []): ?><div class="contact-chips"><?php foreach ($contacts as $contact): ?><span><?= e($contact) ?></span><?php endforeach; ?></div><?php endif; ?>
            <?php if (!empty($item['bio'])): ?><div class="briefing-item"><strong>Sobre o negócio</strong><p><?= nl2br(e($item['bio'])) ?></p></div><?php endif; ?>
            <?php if (!empty($item['oportunidade_descricao'])): ?><div class="briefing-item"><strong>Descrição da oportunidade</strong><p><?= nl2br(e($item['oportunidade_descricao'])) ?></p></div><?php endif; ?>
            <?php if (!empty($item['observacoes'])): ?><div class="briefing-item"><strong>Observações</strong><p><?= nl2br(e($item['observacoes'])) ?></p></div><?php endif; ?>
            <?php if (!empty($item['referencias'])): ?><div class="briefing-item"><strong>Referências</strong><p><?= e($item['referencias']) ?></p></div><?php endif; ?>
          </section>

          <?php if ($canEdit): ?>
            <form class="editor-panel" method="post" action="/dev/submit">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="opportunity_uuid" value="<?= e($item['uuid']) ?>">
              <div class="editor-heading"><div><p class="section-kicker">Entrega</p><h3>Editar e enviar para aprovação</h3></div></div>
              <label class="field"><span>Nome do projeto</span><input name="project_name" maxlength="180" value="<?= e($item['projeto_nome'] ?? ('Site ' . $item['lead_nome'])) ?>" required></label>
              <label class="field"><span>URL de preview</span><input type="url" name="preview_url" value="<?= e($item['url_preview'] ?? '') ?>" placeholder="https://" required></label>
              <label class="field"><span>Notas para revisão</span><textarea name="description" rows="4"><?= e($item['projeto_descricao'] ?? '') ?></textarea></label>
              <button class="button button-primary button-wide" type="submit"><?= $projectStatus === 'ajustes' ? 'Reenviar ajustes' : 'Enviar para aprovação' ?></button>
            </form>
          <?php else: ?>
            <div class="review-state">
              <div><p class="section-kicker">Entrega enviada</p><h3>Aguardando revisão administrativa</h3><p>O formulário será reaberto caso o projeto retorne para ajustes.</p></div>
              <?php if (!empty($item['url_preview'])): ?><a class="button button-secondary" href="<?= e($item['url_preview']) ?>" target="_blank" rel="noopener noreferrer">Abrir preview</a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
</section>
