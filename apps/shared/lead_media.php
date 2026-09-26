<?php

declare(strict_types=1);

const LEAD_REFERENCE_MAX_FILES = 10;
const LEAD_REFERENCE_MAX_BYTES = 26214400;

function normalize_uploaded_images(array $bag): array
{
    if (!isset($bag['name'], $bag['tmp_name'], $bag['error'], $bag['size'])) {
        return [];
    }

    $names = is_array($bag['name']) ? $bag['name'] : [$bag['name']];
    $tmpNames = is_array($bag['tmp_name']) ? $bag['tmp_name'] : [$bag['tmp_name']];
    $errors = is_array($bag['error']) ? $bag['error'] : [$bag['error']];
    $sizes = is_array($bag['size']) ? $bag['size'] : [$bag['size']];
    $files = [];

    foreach ($names as $index => $name) {
        $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $message = match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uma imagem excedeu o limite de 25 MB.',
                UPLOAD_ERR_PARTIAL => 'Uma imagem foi recebida apenas parcialmente. Tente novamente.',
                default => 'Uma das imagens não pôde ser recebida.',
            };
            throw new InvalidArgumentException($message);
        }

        $files[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($tmpNames[$index] ?? ''),
            'size' => (int) ($sizes[$index] ?? 0),
        ];
    }

    return $files;
}

function list_lead_reference_images(int $leadId): array
{
    $statement = db()->prepare(
        'SELECT uuid, nome_original, mime_type, tamanho_bytes, created_at
         FROM lead_referencia_arquivos
         WHERE lead_id = :lead_id
         ORDER BY created_at, id'
    );
    $statement->execute(['lead_id' => $leadId]);
    return $statement->fetchAll();
}

function store_lead_reference_images(
    PDO $connection,
    int $leadId,
    string $leadUuid,
    int $actorId,
    array $uploadBag
): array {
    $files = normalize_uploaded_images($uploadBag);
    if ($files === []) {
        return [];
    }

    $count = $connection->prepare('SELECT COUNT(*) FROM lead_referencia_arquivos WHERE lead_id = :lead_id');
    $count->execute(['lead_id' => $leadId]);
    if ((int) $count->fetchColumn() + count($files) > LEAD_REFERENCE_MAX_FILES) {
        throw new InvalidArgumentException('Cada lead pode possuir no máximo 10 imagens de referência.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mimeAliases = [
        'image/pjpeg' => 'image/jpeg',
        'image/x-png' => 'image/png',
        'image/apng' => 'image/png',
    ];
    $directory = rtrim((string) config('upload_root'), '/') . '/leads/' . $leadUuid;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível preparar o diretório das referências.');
    }

    $insert = $connection->prepare(
        'INSERT INTO lead_referencia_arquivos
            (uuid, lead_id, enviado_por_usuario_id, nome_original, caminho_storage, mime_type, tamanho_bytes)
         VALUES
            (:uuid, :lead_id, :actor_id, :original_name, :storage_path, :mime_type, :size_bytes)'
    );
    $stored = [];

    try {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($files as $file) {
            if ($file['size'] < 1 || $file['size'] > LEAD_REFERENCE_MAX_BYTES) {
                throw new InvalidArgumentException('Cada imagem deve possuir no máximo 25 MB.');
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new InvalidArgumentException('Upload de imagem inválido.');
            }

            $detectedMime = strtolower((string) $finfo->file($file['tmp_name']));
            $mime = $mimeAliases[$detectedMime] ?? $detectedMime;
            if (!isset($allowed[$mime])) {
                throw new InvalidArgumentException('Use apenas imagens JPG, PNG, WEBP ou GIF.');
            }

            $uuid = uuid_v4();
            $basename = $uuid . '.' . $allowed[$mime];
            $absolutePath = $directory . '/' . $basename;
            $relativePath = 'leads/' . $leadUuid . '/' . $basename;
            if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
                throw new RuntimeException('Não foi possível armazenar uma imagem de referência.');
            }
            chmod($absolutePath, 0640);
            $stored[] = $absolutePath;

            $originalName = trim(basename(str_replace('\\', '/', $file['name'])));
            if ($originalName === '') {
                $originalName = 'referencia.' . $allowed[$mime];
            }

            $insert->execute([
                'uuid' => $uuid,
                'lead_id' => $leadId,
                'actor_id' => $actorId,
                'original_name' => substr($originalName, 0, 255),
                'storage_path' => $relativePath,
                'mime_type' => $mime,
                'size_bytes' => $file['size'],
            ]);
        }
    } catch (Throwable $error) {
        foreach ($stored as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        throw $error;
    }

    return $stored;
}

function editable_lead_id(PDO $connection, string $leadUuid, int $captorId): ?int
{
    $statement = $connection->prepare(
        "SELECT l.id
         FROM leads l
         INNER JOIN oportunidades o ON o.lead_id = l.id
         WHERE l.uuid = :uuid
           AND l.captador_usuario_id = :captor_id
           AND o.status = 'aberta'
           AND o.desenvolvedor_usuario_id IS NULL
         LIMIT 1"
    );
    $statement->execute(['uuid' => $leadUuid, 'captor_id' => $captorId]);
    $id = $statement->fetchColumn();
    return $id === false ? null : (int) $id;
}

function delete_lead_reference(string $referenceUuid, array $actor): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            "SELECT r.id, r.caminho_storage, l.uuid lead_uuid
             FROM lead_referencia_arquivos r
             INNER JOIN leads l ON l.id = r.lead_id
             INNER JOIN oportunidades o ON o.lead_id = l.id
             WHERE r.uuid = :uuid
               AND l.captador_usuario_id = :actor_id
               AND o.status = 'aberta'
               AND o.desenvolvedor_usuario_id IS NULL
             FOR UPDATE"
        );
        $find->execute(['uuid' => $referenceUuid, 'actor_id' => $actor['id']]);
        $reference = $find->fetch();
        if (!is_array($reference)) {
            throw new InvalidArgumentException('A referência não pode mais ser removida.');
        }

        $connection->prepare('DELETE FROM lead_referencia_arquivos WHERE id = :id')
            ->execute(['id' => $reference['id']]);

        $profileStatement = $connection->prepare(
            "SELECT l.id, l.nome name, l.bio, l.bio_url, l.origem origin, l.observacoes notes,
                    MAX(CASE WHEN c.tipo = 'email' THEN c.valor END) email,
                    MAX(CASE WHEN c.tipo = 'whatsapp' THEN c.valor END) whatsapp,
                    MAX(CASE WHEN c.tipo = 'instagram' THEN c.valor END) instagram
             FROM leads l
             LEFT JOIN lead_contatos c ON c.lead_id = l.id
             WHERE l.uuid = :lead_uuid
             GROUP BY l.id, l.nome, l.bio, l.bio_url, l.origem, l.observacoes"
        );
        $profileStatement->execute(['lead_uuid' => $reference['lead_uuid']]);
        $profile = $profileStatement->fetch();
        if (!is_array($profile)) {
            throw new RuntimeException('Não foi possível recalcular o cadastro do lead.');
        }

        $referenceCount = $connection->prepare(
            'SELECT COUNT(*) FROM lead_referencia_arquivos WHERE lead_id = :lead_id'
        );
        $referenceCount->execute(['lead_id' => $profile['id']]);
        $payload = lead_form_payload($profile, (int) $referenceCount->fetchColumn() > 0);

        $connection->prepare('UPDATE leads SET temperatura = :temperature WHERE id = :lead_id')
            ->execute(['temperature' => $payload['temperature'], 'lead_id' => $profile['id']]);
        insert_lead_completeness_revision($connection, (int) $profile['id'], (int) $actor['id'], $payload);

        audit_event($connection, 'lead.referencia_removida', 'lead', $reference['lead_uuid'], $actor['uuid'], [
            'referencia_uuid' => $referenceUuid,
            'completude' => $payload['completeness'],
            'temperatura' => $payload['temperature'],
        ]);
        $connection->commit();

        $path = rtrim((string) config('upload_root'), '/') . '/' . $reference['caminho_storage'];
        if (is_file($path)) {
            unlink($path);
        }
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function reference_image_for_actor(string $referenceUuid, array $actor): ?array
{
    $statement = db()->prepare(
        'SELECT r.caminho_storage, r.nome_original, r.mime_type, r.tamanho_bytes
         FROM lead_referencia_arquivos r
         INNER JOIN leads l ON l.id = r.lead_id
         INNER JOIN oportunidades o ON o.lead_id = l.id
         WHERE r.uuid = :uuid
           AND (l.captador_usuario_id = :captor_id OR o.desenvolvedor_usuario_id = :developer_id)
         LIMIT 1'
    );
    $statement->execute([
        'uuid' => $referenceUuid,
        'captor_id' => $actor['id'],
        'developer_id' => $actor['id'],
    ]);
    $row = $statement->fetch();
    return is_array($row) ? $row : null;
}

function send_reference_image(string $referenceUuid, array $actor, bool $download): never
{
    $reference = reference_image_for_actor($referenceUuid, $actor);
    if ($reference === null) {
        render_error(404, 'Imagem não encontrada.');
    }

    $root = realpath((string) config('upload_root'));
    $path = realpath(rtrim((string) config('upload_root'), '/') . '/' . $reference['caminho_storage']);
    if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
        render_error(404, 'Imagem não encontrada.');
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $reference['nome_original']) ?: 'referencia';
    header('Content-Type: ' . $reference['mime_type']);
    header('Content-Length: ' . (string) filesize($path));
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $safeName . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');
    readfile($path);
    exit;
}
