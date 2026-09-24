<section class="page-head"><div><p class="eyebrow">Administração financeira</p><h1>Controle de saques</h1><p>Aprove a solicitação e, depois da transferência, marque como paga.</p></div></section>
<?php if ($success): ?><div class="alert alert-success" role="status">Saque atualizado com sucesso.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="stack">
<?php if ($withdrawals === []): ?><section class="panel"><p class="empty">Nenhuma solicitação de saque.</p></section><?php endif; ?>
<?php foreach ($withdrawals as $withdrawal): ?>
<article class="panel withdrawal-card">
  <header class="withdrawal-head">
    <div><span class="status"><?= e($withdrawal['status']) ?></span><h2><?= e($withdrawal['usuario_nome']) ?></h2><small><?= e($withdrawal['usuario_email']) ?></small></div>
    <div class="withdrawal-value"><strong>R$ <?= e(number_format((float) $withdrawal['valor_brl'], 2, ',', '.')) ?></strong><small><?= e((string) $withdrawal['pontos_reservados']) ?> pts × R$ <?= e(number_format((float) $withdrawal['valor_ponto_brl'], 6, ',', '.')) ?></small></div>
  </header>
  <dl class="details-grid">
    <div><dt>Chave Pix</dt><dd><?= e($withdrawal['chave_pix_tipo'] ?? '') ?> · <code><?= e($withdrawal['chave_pix']) ?></code></dd></div>
    <div><dt>Solicitado</dt><dd><?= e($withdrawal['solicitado_em']) ?></dd></div>
    <?php if ($withdrawal['referencia_pagamento'] !== null): ?><div><dt>Referência</dt><dd><?= e($withdrawal['referencia_pagamento']) ?></dd></div><?php endif; ?>
    <?php if ($withdrawal['observacoes'] !== null): ?><div><dt>Histórico</dt><dd><?= nl2br(e($withdrawal['observacoes'])) ?></dd></div><?php endif; ?>
  </dl>
  <?php if (in_array($withdrawal['status'], ['solicitado','em_analise','aprovado'], true)): ?>
  <form class="review-form" method="post" action="/withdrawals/action">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="withdrawal_uuid" value="<?= e($withdrawal['uuid']) ?>">
    <label class="field field-full"><span>Observações administrativas</span><textarea name="notes" rows="2"></textarea></label>
    <?php if ($withdrawal['status'] === 'aprovado'): ?><label class="field field-full"><span>Referência da transferência</span><input name="reference" maxlength="190"></label><?php endif; ?>
    <div class="actions field-full">
      <?php if (in_array($withdrawal['status'], ['solicitado','em_analise'], true)): ?><button class="button button-primary" type="submit" name="action" value="approve">Aprovar saque</button><?php endif; ?>
      <?php if ($withdrawal['status'] === 'aprovado'): ?><button class="button button-primary" type="submit" name="action" value="paid">Marcar como pago</button><?php endif; ?>
      <button class="button button-danger" type="submit" name="action" value="reject">Recusar e devolver pontos</button>
    </div>
  </form>
  <?php endif; ?>
</article>
<?php endforeach; ?>
</div>
