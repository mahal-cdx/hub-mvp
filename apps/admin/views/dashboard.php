<section class="page-heading">
  <div>
    <p class="eyebrow">Visão geral</p>
    <h1>Olá, <?= e($user['name']) ?>.</h1>
    <p>Acompanhe os pontos que exigem atenção na operação do Hub.</p>
  </div>
  <span class="status-badge"><i></i>Sessão protegida</span>
</section>

<section class="metric-grid" aria-label="Indicadores">
  <article class="metric-card"><span>Leads ativos</span><strong><?= e($metrics['leads']) ?></strong><small>Captação e qualificação</small></article>
  <article class="metric-card"><span>Revisões pendentes</span><strong><?= e($metrics['revisoes']) ?></strong><small>Projetos aguardando decisão</small></article>
  <article class="metric-card"><span>Fila comercial</span><strong><?= e($metrics['vendas']) ?></strong><small>Projetos disponíveis ou em contato</small></article>
  <article class="metric-card"><span>Saques pendentes</span><strong><?= e($metrics['saques']) ?></strong><small>Solicitações que exigem análise</small></article>
</section>

<section class="panel-grid">
  <article class="panel">
    <header><div><p class="eyebrow">Etapas</p><h2>Fluxo operacional</h2></div></header>
    <ol class="process-list">
      <li><span>01</span><div><strong>Captação</strong><small>Cadastro e enriquecimento do lead</small></div></li>
      <li><span>02</span><div><strong>Desenvolvimento</strong><small>Assunção e criação do preview</small></div></li>
      <li><span>03</span><div><strong>Aprovação</strong><small>Revisão, oferta e link de pagamento</small></div></li>
      <li><span>04</span><div><strong>Comercial</strong><small>Contato, pagamento e distribuição</small></div></li>
    </ol>
  </article>

  <article class="panel">
    <header><div><p class="eyebrow">Auditoria</p><h2>Eventos recentes</h2></div></header>
    <?php if ($events === []): ?>
      <p class="empty-state">Nenhum evento registrado até o momento.</p>
    <?php else: ?>
      <ul class="event-list">
        <?php foreach ($events as $event): ?>
          <li>
            <span><?= e(str_replace('.', ' · ', $event['acao'])) ?></span>
            <small><?= e($event['entidade_tipo']) ?> · <?= e($event['ocorrido_em']) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </article>
</section>
