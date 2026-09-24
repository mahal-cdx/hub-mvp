<section class="page-head"><div><p class="eyebrow">Minha carteira</p><h1>Pontos e saques</h1><p>A cotação aplicada ao saque é congelada no momento da solicitação.</p></div></section>
<?php if ($success): ?><div class="alert alert-success" role="status">Solicitação de saque registrada.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<section class="wallet-summary">
  <div class="metric-card"><span>Disponível</span><strong><?= e((string) $wallet['saldo_disponivel_pontos']) ?> pts</strong><small>Pode ser solicitado para saque.</small></div>
  <div class="metric-card"><span>Reservado</span><strong><?= e((string) $wallet['saldo_reservado_pontos']) ?> pts</strong><small>Aguardando análise ou pagamento.</small></div>
  <div class="metric-card"><span>Cotação atual</span><strong><?= $quote === null ? 'Indisponível' : 'R$ ' . e(number_format((float) $quote['valor_brl'], 6, ',', '.')) ?></strong><small>Valor de cada ponto agora.</small></div>
</section>

<div class="two-columns finance-section">
<section class="panel">
  <h2>Solicitar saque</h2>
  <?php if ($quote === null): ?><div class="alert alert-error">O administrador ainda não publicou uma cotação vigente.</div>
  <?php elseif ((int) $wallet['saldo_disponivel_pontos'] < 1): ?><p class="empty">Você ainda não possui pontos disponíveis.</p>
  <?php else: ?>
  <form class="stack" method="post" action="/wallet/withdraw" data-withdraw-form data-point-value="<?= e((string) $quote['valor_brl']) ?>">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="request_token" value="<?= e($requestToken) ?>">
    <label class="field"><span>Pontos para sacar</span><input type="number" name="points" min="1" max="<?= e((string) $wallet['saldo_disponivel_pontos']) ?>" required data-withdraw-points></label>
    <div class="withdraw-preview">Valor estimado: <strong data-withdraw-value>R$ 0,00</strong></div>
    <label class="field"><span>Tipo de chave Pix</span><select name="pix_type" required><option value="cpf">CPF</option><option value="cnpj">CNPJ</option><option value="email">E-mail</option><option value="telefone">Telefone</option><option value="aleatoria">Aleatória</option></select></label>
    <label class="field"><span>Chave Pix</span><input name="pix_key" maxlength="190" autocomplete="off" required></label>
    <label class="field"><span>Observações</span><textarea name="notes" rows="3"></textarea></label>
    <button class="button button-primary" type="submit">Reservar pontos e solicitar</button>
  </form>
  <?php endif; ?>
</section>
<section class="panel">
  <h2>Solicitações</h2>
  <div class="stack"><?php if ($withdrawals === []): ?><p class="empty">Nenhum saque solicitado.</p><?php endif; ?>
  <?php foreach ($withdrawals as $withdrawal): ?><article class="item-card"><div><strong>R$ <?= e(number_format((float) $withdrawal['valor_brl'], 2, ',', '.')) ?></strong><small><?= e((string) $withdrawal['pontos_reservados']) ?> pts · cotação R$ <?= e(number_format((float) $withdrawal['valor_ponto_brl'], 6, ',', '.')) ?><br><?= e($withdrawal['chave_pix_mascarada'] ?? '') ?> · <?= e($withdrawal['solicitado_em']) ?></small></div><span class="status"><?= e($withdrawal['status']) ?></span></article><?php endforeach; ?></div>
</section>
</div>

<section class="panel finance-section">
  <h2>Histórico de pontos</h2>
  <div class="stack"><?php if ($history === []): ?><p class="empty">Nenhum lançamento registrado.</p><?php endif; ?>
  <?php foreach ($history as $entry): ?><article class="item-card"><div><strong><?= e($entry['descricao'] ?? $entry['tipo']) ?></strong><small><?= e($entry['created_at']) ?> · <?= e($entry['tipo']) ?></small></div><strong class="<?= (int) $entry['pontos'] >= 0 ? 'points-positive' : 'points-negative' ?>"><?= (int) $entry['pontos'] >= 0 ? '+' : '' ?><?= e((string) $entry['pontos']) ?> pts</strong></article><?php endforeach; ?></div>
</section>
