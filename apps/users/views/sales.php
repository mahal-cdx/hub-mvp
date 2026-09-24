<section class="page-head">
  <div>
    <p class="eyebrow">Comercial</p>
    <h1>Vendas</h1>
    <p>Assuma uma oportunidade para liberar o projeto, os contatos e o link de pagamento.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert alert-success" role="status">Ação comercial registrada.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<section class="workflow-section" aria-labelledby="sales-active-title">
  <header class="section-heading">
    <div>
      <p class="section-kicker">Minha carteira</p>
      <h2 id="sales-active-title">Em atendimento</h2>
      <p>Leads que você assumiu e está acompanhando.</p>
    </div>
    <span class="count-badge"><?= e((string) count($sales['mine'])) ?> ativos</span>
  </header>

  <div class="accordion-list">
    <?php if ($sales['mine'] === []): ?>
      <div class="empty-state"><strong>Nenhum atendimento ativo</strong><span>Assuma uma oportunidade comercial para começar.</span></div>
    <?php endif; ?>

    <?php foreach ($sales['mine'] as $sale): ?>
      <?php $contacts = array_values(array_filter(explode(' | ', (string) ($sale['contatos'] ?? '')))); ?>
      <details class="accordion-card sales-accordion">
        <summary>
          <span class="accordion-main">
            <span class="status"><?= e(str_replace('_', ' ', $sale['status'])) ?></span>
            <strong><?= e($sale['projeto_nome']) ?></strong>
            <small><?= e($sale['lead_nome']) ?> · R$ <?= e(number_format((float) $sale['valor_brl'], 2, ',', '.')) ?></small>
          </span>
          <span class="accordion-toggle"><span class="when-closed">Abrir atendimento</span><span class="when-open">Fechar</span><i aria-hidden="true"></i></span>
        </summary>

        <div class="accordion-body sales-body">
          <section class="lead-access-card">
            <div><p class="section-kicker">Dados liberados</p><h3><?= e($sale['lead_nome']) ?></h3></div>
            <div class="contact-chips"><?php if ($contacts === []): ?><span>Sem contatos cadastrados</span><?php endif; ?><?php foreach ($contacts as $contact): ?><span><?= e($contact) ?></span><?php endforeach; ?></div>
            <div class="sale-value"><span>Valor da oferta</span><strong>R$ <?= e(number_format((float) $sale['valor_brl'], 2, ',', '.')) ?></strong></div>
            <div class="actions">
              <a class="button button-secondary" href="<?= e($sale['url_preview']) ?>" target="_blank" rel="noopener noreferrer">Ver projeto</a>
              <a class="button button-primary" href="<?= e($sale['link_pagamento']) ?>" target="_blank" rel="noopener noreferrer">Abrir pagamento</a>
            </div>
          </section>

          <form class="editor-panel" method="post" action="/sales/contact">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="sale_uuid" value="<?= e($sale['uuid']) ?>">
            <div class="editor-heading"><div><p class="section-kicker">Acompanhamento</p><h3>Registrar atendimento</h3></div></div>
            <div class="form-grid">
              <label class="field"><span>Canal</span><select name="channel"><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option><option value="instagram">Instagram</option><option value="telefone">Telefone</option><option value="outro">Outro</option></select></label>
              <label class="field"><span>Resultado</span><select name="result"><option value="contato_realizado">Contato realizado</option><option value="retorno_agendado">Retorno agendado</option><option value="aguardando_pagamento">Aguardando pagamento</option><option value="perdido">Perdido</option></select></label>
              <label class="field field-full"><span>Próximo contato</span><input type="datetime-local" name="next_contact_at"></label>
              <label class="field field-full"><span>Observações</span><textarea name="notes" rows="4"></textarea></label>
            </div>
            <button class="button button-primary button-wide" type="submit">Registrar atendimento</button>
          </form>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<section class="workflow-section" aria-labelledby="sales-opportunities-title">
  <header class="section-heading">
    <div>
      <p class="section-kicker">Fila comercial</p>
      <h2 id="sales-opportunities-title">Oportunidades</h2>
      <p>Contato, valor, projeto e pagamento permanecem protegidos até a atribuição.</p>
    </div>
    <span class="count-badge"><?= e((string) count($sales['available'])) ?> disponíveis</span>
  </header>

  <div class="vertical-list">
    <?php if ($sales['available'] === []): ?>
      <div class="empty-state"><strong>Nenhuma oportunidade disponível</strong><span>Novas vendas aprovadas aparecerão nesta fila.</span></div>
    <?php endif; ?>

    <?php foreach ($sales['available'] as $index => $sale): ?>
      <article class="opportunity-row">
        <div class="opportunity-index" aria-hidden="true"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></div>
        <div class="opportunity-copy">
          <span class="status status-neutral">Disponível</span>
          <h3>Nova oportunidade comercial</h3>
          <p>Dados do lead, valor, preview e pagamento liberados após assumir.</p>
          <small>Entrada na fila: <?= e($sale['created_at']) ?></small>
        </div>
        <form class="row-action" method="post" action="/sales/claim">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="sale_uuid" value="<?= e($sale['uuid']) ?>">
          <button class="button button-primary" type="submit">Assumir venda</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>
