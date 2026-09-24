<section class="page-heading">
  <div><p class="eyebrow">Qualidade</p><h1>Revisão de projetos</h1><p>Aprove o preview e disponibilize a oferta para a equipe comercial.</p></div>
</section>

<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" role="status">Revisão registrada.</div><?php endif; ?>

<?php if ($reviews === []): ?>
  <section class="panel empty-state">Nenhum projeto aguardando revisão.</section>
<?php else: ?>
  <section class="stack">
    <?php foreach ($reviews as $review): ?>
      <article class="panel review-card">
        <header><div><p class="eyebrow">Submissão <?= e($review['submissao_numero']) ?></p><h2><?= e($review['projeto_nome']) ?></h2></div><a class="button button-secondary" href="<?= e($review['url_preview']) ?>" target="_blank" rel="noopener noreferrer">Abrir preview</a></header>
        <p>Lead: <strong><?= e($review['lead_nome']) ?></strong> · Dev: <?= e($review['desenvolvedor_nome']) ?></p>
        <form class="review-form" method="post" action="/projects/review">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="revision_uuid" value="<?= e($review['revisao_uuid']) ?>">
          <label class="field"><span>Decisão</span><select name="decision" required><option value="aprovado">Aprovar e enviar ao comercial</option><option value="ajustes">Solicitar ajustes ao mesmo desenvolvedor</option><option value="reprovado">Reprovar projeto</option></select></label>
          <label class="field"><span>Destino se reprovado</span><select name="rejection_action"><option value="requeue">Reabrir oportunidade para outro desenvolvedor</option><option value="archive">Arquivar definitivamente</option></select></label>
          <label class="field"><span>Valor da oferta (R$)</span><input name="value_brl" inputmode="decimal" placeholder="1500,00"></label>
          <label class="field"><span>Link HTTPS do Mercado Pago</span><input type="url" name="payment_link" placeholder="https://..."></label>
          <label class="field field-full"><span>Motivo ou orientações</span><textarea name="notes" rows="3"><?= e($review['observacoes'] ?? '') ?></textarea></label>
          <button class="button button-primary" type="submit">Registrar decisão</button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
