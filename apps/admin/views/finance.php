<section class="page-head"><div><p class="eyebrow">Administração financeira</p><h1>Pontos e pagamentos</h1><p>Configure os pontos por tarefa, a cotação atual e valide pagamentos.</p></div></section>
<?php if ($success): ?><div class="alert alert-success" role="status">Alteração registrada com sucesso.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="two-columns">
<section class="panel">
  <h2>Pontos por tarefa</h2>
  <p class="empty">Cada alteração cria uma nova versão e afeta somente eventos futuros.</p>
  <div class="stack">
  <?php foreach ($rules as $rule): ?>
    <form class="item-card finance-rule" method="post" action="/finance/rules">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="event" value="<?= e($rule['event']) ?>">
      <div><strong><?= e($rule['label']) ?></strong><small><?= e($rule['role']) ?> · <?= $rule['version'] === null ? 'Não configurado' : 'versão ' . e((string) $rule['version']) ?></small></div>
      <label class="compact-field"><span>Pontos</span><input type="number" name="points" min="1" max="1000000" value="<?= e((string) ($rule['points'] ?? '')) ?>" required></label>
      <button class="button button-secondary" type="submit">Salvar</button>
    </form>
  <?php endforeach; ?>
  </div>
</section>

<section class="panel">
  <h2>Cotação do ponto</h2>
  <?php if ($quote !== null): ?>
    <div class="quote-highlight"><span>Cotação vigente</span><strong>R$ <?= e(number_format((float) $quote['valor_brl'], 6, ',', '.')) ?></strong><small><?= e($quote['motivo']) ?> · <?= e($quote['definido_por']) ?></small></div>
  <?php else: ?><div class="alert alert-error">Nenhuma cotação vigente. Saques estão indisponíveis.</div><?php endif; ?>
  <form class="stack" method="post" action="/finance/quote">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label class="field"><span>Novo valor em reais por ponto</span><input name="value_brl" inputmode="decimal" placeholder="0,100000" required></label>
    <label class="field"><span>Motivo da alteração</span><input name="reason" maxlength="255" required></label>
    <button class="button button-primary" type="submit">Publicar nova cotação</button>
  </form>
</section>
</div>

<section class="panel finance-section">
  <h2>Pagamentos aguardando validação</h2>
  <p class="empty">A aprovação fecha a venda e credita os bônus configurados para captador, desenvolvedor e comercial uma única vez.</p>
  <div class="card-grid">
  <?php if ($payments === []): ?><p class="empty">Nenhum pagamento aguardando validação.</p><?php endif; ?>
  <?php foreach ($payments as $payment): ?>
    <article class="work-card">
      <span class="status">Aguardando pagamento</span>
      <h3><?= e($payment['projeto_nome']) ?></h3>
      <p><strong>Lead:</strong> <?= e($payment['lead_nome']) ?></p>
      <p><strong>Comercial:</strong> <?= e($payment['comercial_nome'] ?? 'Não atribuído') ?></p>
      <p><strong>Valor:</strong> R$ <?= e(number_format((float) $payment['valor_brl'], 2, ',', '.')) ?></p>
      <a class="button button-secondary" href="<?= e($payment['link_pagamento']) ?>" target="_blank" rel="noopener noreferrer">Abrir link da oferta</a>
      <form class="stack" method="post" action="/finance/payment">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="sale_uuid" value="<?= e($payment['venda_uuid']) ?>">
        <label class="field"><span>Referência do comprovante</span><input name="reference" maxlength="190" required></label>
        <label class="field"><span>Observações</span><textarea name="notes" rows="3"></textarea></label>
        <button class="button button-primary" type="submit">Aprovar pagamento e distribuir pontos</button>
      </form>
    </article>
  <?php endforeach; ?>
  </div>
</section>
