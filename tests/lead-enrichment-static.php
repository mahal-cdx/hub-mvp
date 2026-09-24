<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static fn (string $path): string => file_get_contents($root . '/' . $path) ?: '';
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FALHOU: {$message}\n");
        exit(1);
    }
};

$migration = $read('database/migrations/007_add_lead_bio_and_references.sql');
$limitsMigration = $read('database/migrations/008_increase_lead_reference_limits.sql');
$workflow = $read('apps/shared/workflow.php');
$media = $read('apps/shared/lead_media.php');
$routes = $read('apps/users/public/index.php');
$leads = $read('apps/users/views/leads.php');
$developer = $read('apps/users/views/developer.php');
$admin = $read('apps/admin/views/projects.php');
$javascript = $read('apps/users/public/assets/app.js');
$dockerfile = $read('infrastructure/apache/Dockerfile');
$compose = $read('docker-compose.yml');

$assert(str_contains($migration, 'bio_url') && str_contains($migration, 'lead_referencia_arquivos'), 'migration de bio e referências');
$assert(!preg_match('/name=["\']temperature["\']/', $leads), 'temperatura não pode ser selecionada manualmente');
$assert(str_contains($workflow, "'referencias_visuais' => \$hasReferences ? 10 : 0"), 'referências entram na completude');
$assert(str_contains($workflow, "\$temperature = \$completeness >= 75"), 'temperatura deriva da completude');
$assert(str_contains($workflow, "o.status = 'aberta'") && str_contains($workflow, 'o.desenvolvedor_usuario_id IS NULL'), 'edição limitada ao lead aberto');
$assert(str_contains($media, 'finfo') && str_contains($media, '26214400') && str_contains($media, 'LEAD_REFERENCE_MAX_FILES = 10'), 'upload valida MIME, 10 arquivos e 25 MB');
$assert(str_contains($limitsMigration, '26214400'), 'migration amplia o limite persistido para 25 MB');
$assert(str_contains($routes, '/references/') && str_contains($routes, 'send_reference_image'), 'imagens passam por rota autorizada');
$assert(str_contains($developer, 'data-copy-image') && str_contains($developer, 'https://3eb.site/parceiro'), 'desenvolvedor pode copiar e abrir editor');
$assert(str_contains($developer, "\$projectStatus === 'ajustes'"), 'ajustes reabrem edição');
$assert(str_contains($admin, 'name="rejection_action"'), 'administrador escolhe destino da reprovação');
$assert(str_contains($workflow, "\$rejectionAction === 'requeue'") && str_contains($workflow, "status = 'cancelada'"), 'reprovação pode reenfileirar ou arquivar');
$assert(str_contains($javascript, 'pasteImages') && str_contains($javascript, "addEventListener('paste'"), 'colagem de imagens implementada');
$assert(str_contains($leads, 'data-image-dialog') && str_contains($leads, 'Adicionar imagens'), 'seletor de imagens usa modal');
$assert(str_contains($routes, 'render_error(413'), 'excesso do POST recebe resposta amigável');
$assert(str_contains($dockerfile, 'upload_max_filesize=25M') && str_contains($dockerfile, 'post_max_size=260M') && str_contains($dockerfile, 'max_file_uploads=10') && str_contains($compose, '/var/www/storage/uploads'), 'runtime preparado para 10 uploads de 25 MB');

fwrite(STDOUT, "OK: enriquecimento de leads validado.\n");
