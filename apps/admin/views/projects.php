<section class="page-heading">
  <div><p class="eyebrow">Operação</p><h1>Projetos</h1><p>Revise entregas, acompanhe vendas, produtos extras e os limites do funil.</p></div>
</section>

<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" role="status">Alteração registrada.</div><?php endif; ?>

<section class="panel admin-section">
  <header class="panel-heading"><div><p class="eyebrow">Capacidade</p><h2>Limites operacionais</h2><p>Esses valores controlam automaticamente as filas de captação, desenvolvimento e comercial.</p></div></header>
  <form class="settings-grid" method="post" action="/projects/settings">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <?php foreach ($operationalSettings as $setting): ?>
      <label class="field"><span><?= e($setting['label']) ?></span><input type="number" name="<?= e($setting['key']) ?>" min="<?= e((string) $setting['min']) ?>" max="<?= e((string) $setting['max']) ?>" value="<?= e((string) $setting['value']) ?>" required></label>
    <?php endforeach; ?>
    <button class="button button-primary settings-submit" type="submit">Salvar limites</button>
  </form>
</section>

<section class="admin-section">
  <header class="section-title"><div><p class="eyebrow">Qualidade</p><h2>Aguardando revisão</h2></div><span class="count-badge"><?= e((string) count($reviews)) ?></span></header>
  <?php if ($reviews === []): ?>
    <div class="panel empty-state">Nenhum projeto aguardando revisão.</div>
  <?php else: ?>
    <div class="stack">
      <?php foreach ($reviews as $review): ?>
        <article class="panel review-card">
          <header><div><p class="eyebrow">Submissão <?= e($review['submissao_numero']) ?></p><h2><?= e($review['projeto_nome']) ?></h2></div><a class="button button-secondary" href="<?= e($review['url_preview']) ?>" target="_blank" rel="noopener noreferrer">Abrir preview</a></header>
          <p>Lead: <strong><?= e($review['lead_nome']) ?></strong> · Dev: <?= e($review['desenvolvedor_nome']) ?></p>
          <form class="review-form" method="post" action="/projects/review">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="revision_uuid" value="<?= e($review['revisao_uuid']) ?>">
            <label class="field"><span>Decisão</span><select name="decision" required><option value="aprovado">Aprovar e enviar ao comercial</option><option value="ajustes">Solicitar ajustes ao mesmo desenvolvedor</option><option value="reprovado">Reprovar projeto</option></select></label>
            <label class="field"><span>Destino se reprovado</span><select name="rejection_action"><option value="requeue">Reabrir para outro desenvolvedor</option><option value="archive">Arquivar definitivamente</option></select></label>
            <label class="field"><span>Valor da oferta (R$)</span><input name="value_brl" inputmode="decimal" placeholder="1500,00"></label>
            <label class="field"><span>Link HTTPS do Mercado Pago</span><input type="url" name="payment_link" placeholder="https://..."></label>
            <label class="field field-full"><span>Motivo ou orientações</span><textarea name="notes" rows="3"><?= e($review['observacoes'] ?? '') ?></textarea></label>
            <button class="button button-primary" type="submit">Registrar decisão</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="admin-section">
  <header class="section-title"><div><p class="eyebrow">Portfólio</p><h2>Projetos aprovados e comerciais</h2><p>Inclui o status da venda, interesses em produtos extras e custo de hospedagem.</p></div><span class="count-badge"><?= e((string) count($projects)) ?></span></header>
  <div class="admin-project-grid">
    <?php if ($projects === []): ?><div class="panel empty-state">Nenhum projeto aprovado.</div><?php endif; ?>
    <?php foreach ($projects as $project): ?>
      <article class="panel admin-project-card">
        <header><div><span class="status"><?= e(str_replace('_', ' ', $project['venda_status'] ?? $project['projeto_status'])) ?></span><h3><?= e($project['nome']) ?></h3></div><?php if (!empty($project['url_preview'])): ?><a class="button button-secondary" href="<?= e($project['url_preview']) ?>" target="_blank" rel="noopener noreferrer">Preview</a><?php endif; ?></header>
        <p><strong>Lead:</strong> <?= e($project['lead_nome']) ?> · <strong>Dev:</strong> <?= e($project['desenvolvedor_nome']) ?></p>
        <div class="plus-box"><span>Produtos Plus</span><strong><?= e($project['produtos_extras'] ?: 'Nenhum interesse marcado') ?></strong></div>
        <?php if (!empty($project['proximo_contato_em'])): ?><p><strong>Próximo contato:</strong> <?= e($project['proximo_contato_em']) ?></p><?php endif; ?>
        <form class="inline-cost-form" method="post" action="/projects/cost">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="project_uuid" value="<?= e($project['uuid']) ?>">
          <label class="compact-field"><span>Custo de hospedagem (R$)</span><input name="hosting_cost_brl" inputmode="decimal" value="<?= e(number_format((float) $project['custo_hospedagem_brl'], 2, ',', '.')) ?>"></label>
          <button class="button button-secondary" type="submit">Salvar custo</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-section">
  <header class="section-title"><div><p class="eyebrow">Comercial</p><h2>Vendas perdidas</h2></div><span class="count-badge"><?= e((string) count($lostSales)) ?></span></header>
  <div class="stack">
    <?php if ($lostSales === []): ?><div class="panel empty-state">Nenhuma venda perdida.</div><?php endif; ?>
    <?php foreach ($lostSales as $sale): ?>
      <article class="panel lost-sale-card">
        <div><span class="status status-danger">Perdida</span><h3><?= e($sale['projeto_nome']) ?></h3><p><?= e($sale['lead_nome']) ?> · Comercial: <?= e($sale['comercial_nome'] ?? 'Não atribuído') ?></p></div>
        <div><strong>Motivo</strong><p><?= e($sale['perda_motivo'] ?? 'Não informado') ?></p><small><?= e($sale['perdida_em'] ?? '') ?></small></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
