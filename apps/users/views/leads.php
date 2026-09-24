<section class="page-head"><div><p class="eyebrow">Captação</p><h1>Leads</h1><p>Quanto melhor o cadastro, maior a completude registrada.</p></div></section>
<?php if ($success): ?><div class="alert alert-success" role="status">Lead cadastrado e enviado para oportunidades.</div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="two-columns">
<form class="panel form-grid" method="post" action="/leads">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<h2>Novo lead</h2>
<label class="field field-full"><span>Nome *</span><input name="name" maxlength="160" value="<?= e((string) ($values['name'] ?? '')) ?>" required></label>
<label class="field"><span>E-mail</span><input type="email" name="email" maxlength="190" value="<?= e((string) ($values['email'] ?? '')) ?>"></label>
<label class="field"><span>WhatsApp</span><input name="whatsapp" maxlength="80" value="<?= e((string) ($values['whatsapp'] ?? '')) ?>"></label>
<label class="field"><span>Instagram</span><input name="instagram" maxlength="190" value="<?= e((string) ($values['instagram'] ?? '')) ?>"></label>
<label class="field"><span>Temperatura</span><select name="temperature"><option value="frio">Frio</option><option value="morno">Morno</option><option value="quente">Quente</option></select></label>
<label class="field field-full"><span>Origem</span><input name="origin" maxlength="120" placeholder="Instagram, indicação..." value="<?= e((string) ($values['origin'] ?? '')) ?>"></label>
<label class="field field-full"><span>Bio / negócio</span><textarea name="bio" rows="3"><?= e((string) ($values['bio'] ?? '')) ?></textarea></label>
<label class="field field-full"><span>Observações e referências</span><textarea name="notes" rows="4"><?= e((string) ($values['notes'] ?? '')) ?></textarea></label>
<button class="button button-primary field-full" type="submit">Cadastrar lead</button>
</form>
<section class="panel"><h2>Meus cadastros</h2><div class="stack"><?php if ($leads === []): ?><p class="empty">Nenhum lead cadastrado.</p><?php endif; ?><?php foreach ($leads as $lead): ?><article class="item-card"><div><strong><?= e($lead['nome']) ?></strong><small><?= e($lead['contatos'] ?? 'Sem contato') ?></small></div><span class="status"><?= e($lead['status']) ?> · <?= e($lead['temperatura']) ?></span></article><?php endforeach; ?></div></section>
</div>
