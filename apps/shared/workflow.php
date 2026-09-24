<?php

declare(strict_types=1);

function valid_web_url(string $url, bool $httpsOnly = false): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $httpsOnly ? $scheme === 'https' : in_array($scheme, ['http', 'https'], true);
}

function wallet_summary(int $userId): array
{
    $statement = db()->prepare(
        'SELECT saldo_disponivel_pontos, saldo_reservado_pontos FROM carteiras WHERE usuario_id = :user_id'
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetch() ?: ['saldo_disponivel_pontos' => 0, 'saldo_reservado_pontos' => 0];
}

function lead_form_payload(array $input, bool $hasReferences = false): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $email = normalize_email((string) ($input['email'] ?? ''));
    $whatsapp = trim((string) ($input['whatsapp'] ?? ''));
    $instagram = trim((string) ($input['instagram'] ?? ''));
    $bio = trim((string) ($input['bio'] ?? ''));
    $bioUrl = trim((string) ($input['bio_url'] ?? ''));
    $origin = trim((string) ($input['origin'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? ''));

    if (strlen($name) < 2 || strlen($name) > 160) {
        throw new InvalidArgumentException('Informe o nome do lead.');
    }
    if ($email === '' && $whatsapp === '' && $instagram === '') {
        throw new InvalidArgumentException('Informe pelo menos e-mail, WhatsApp ou Instagram.');
    }
    if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
        throw new InvalidArgumentException('O e-mail informado é inválido.');
    }
    if ($bioUrl !== '' && (!valid_web_url($bioUrl) || strlen($bioUrl) > 500)) {
        throw new InvalidArgumentException('O link da bio é inválido.');
    }

    $fields = [
        'nome' => 10,
        'email' => $email !== '' ? 15 : 0,
        'whatsapp' => $whatsapp !== '' ? 15 : 0,
        'instagram' => $instagram !== '' ? 10 : 0,
        'bio' => $bio !== '' ? 15 : 0,
        'bio_url' => $bioUrl !== '' ? 10 : 0,
        'origem' => $origin !== '' ? 5 : 0,
        'observacoes' => $notes !== '' ? 10 : 0,
        'referencias_visuais' => $hasReferences ? 10 : 0,
    ];
    $completeness = min(100, array_sum($fields));
    $temperature = $completeness >= 75 ? 'quente' : ($completeness >= 45 ? 'morno' : 'frio');

    return compact(
        'name',
        'email',
        'whatsapp',
        'instagram',
        'bio',
        'bioUrl',
        'origin',
        'notes',
        'fields',
        'completeness',
        'temperature'
    );
}

function insert_lead_contacts(PDO $connection, int $leadId, array $payload, int $actorId): void
{
    $contact = $connection->prepare(
        'INSERT INTO lead_contatos
            (uuid, lead_id, tipo, valor, valor_normalizado, principal, origem, criado_por_usuario_id)
         VALUES
            (:uuid, :lead_id, :type, :value, :normalized, :primary, :origin, :creator_id)'
    );
    $contacts = array_filter([
        'email' => $payload['email'],
        'whatsapp' => $payload['whatsapp'],
        'instagram' => $payload['instagram'],
    ]);
    $first = true;
    foreach ($contacts as $type => $value) {
        $normalized = $type === 'email' ? normalize_email($value) : strtolower(trim($value));
        $contact->execute([
            'uuid' => uuid_v4(),
            'lead_id' => $leadId,
            'type' => $type,
            'value' => $value,
            'normalized' => $normalized,
            'primary' => $first ? 1 : 0,
            'origin' => $payload['origin'] !== '' ? $payload['origin'] : 'cadastro_manual',
            'creator_id' => $actorId,
        ]);
        $first = false;
    }
}

function insert_lead_completeness_revision(
    PDO $connection,
    int $leadId,
    int $actorId,
    array $payload
): void {
    $revision = $connection->prepare(
        'INSERT INTO lead_completude_revisoes
            (uuid, lead_id, autor_usuario_id, regra_versao, percentual, pontos_elegiveis, campos_considerados)
         VALUES
            (:uuid, :lead_id, :author_id, 2, :percentage, :eligible_points, :fields)'
    );
    $revision->execute([
        'uuid' => uuid_v4(),
        'lead_id' => $leadId,
        'author_id' => $actorId,
        'percentage' => $payload['completeness'],
        'eligible_points' => intdiv($payload['completeness'], 20),
        'fields' => json_encode($payload['fields'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
    ]);
}

function lead_workflow_status(array $row): array
{
    if (($row['sale_status'] ?? null) === 'fechada' || $row['opportunity_status'] === 'vendida') {
        return ['key' => 'venda_fechada', 'label' => 'Venda fechada'];
    }
    if (in_array($row['sale_status'] ?? null, ['perdida', 'cancelada'], true)) {
        return ['key' => 'recusado', 'label' => 'Recusado'];
    }
    if ($row['opportunity_status'] === 'em_venda') {
        return ['key' => 'aberto_comercial', 'label' => 'Aberto comercial'];
    }
    if ($row['opportunity_status'] === 'cancelada'
        || in_array($row['project_status'] ?? null, ['reprovado', 'arquivado'], true)) {
        return ['key' => 'recusado', 'label' => 'Recusado'];
    }
    if ($row['opportunity_status'] === 'aberta' && $row['developer_id'] === null) {
        return ['key' => 'na_fila', 'label' => 'Na fila'];
    }
    return ['key' => 'em_desenvolvimento', 'label' => 'Em desenvolvimento'];
}

function list_captor_leads(int $userId): array
{
    $statement = db()->prepare(
        "SELECT l.id, l.uuid, l.nome, l.temperatura, l.created_at,
                o.status opportunity_status, o.desenvolvedor_usuario_id developer_id,
                (SELECT p.status FROM projetos p WHERE p.oportunidade_id = o.id ORDER BY p.id DESC LIMIT 1) project_status,
                (SELECT v.status
                   FROM vendas v
                   INNER JOIN ofertas ofe ON ofe.id = v.oferta_id
                   INNER JOIN projetos ps ON ps.id = ofe.projeto_id
                  WHERE ps.oportunidade_id = o.id
                  ORDER BY v.id DESC LIMIT 1) sale_status,
                (SELECT percentual FROM lead_completude_revisoes cr
                  WHERE cr.lead_id = l.id ORDER BY cr.id DESC LIMIT 1) completude,
                GROUP_CONCAT(CONCAT(lc.tipo, ': ', lc.valor) ORDER BY lc.tipo SEPARATOR ' | ') contatos
         FROM leads l
         INNER JOIN oportunidades o ON o.lead_id = l.id
         LEFT JOIN lead_contatos lc ON lc.lead_id = l.id
         WHERE l.captador_usuario_id = :user_id
         GROUP BY l.id, l.uuid, l.nome, l.temperatura, l.created_at,
                  o.id, o.status, o.desenvolvedor_usuario_id
         ORDER BY l.created_at DESC"
    );
    $statement->execute(['user_id' => $userId]);
    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $status = lead_workflow_status($row);
        $row['workflow_status'] = $status['key'];
        $row['workflow_label'] = $status['label'];
        $row['editable'] = $status['key'] === 'na_fila';
        unset($row['id']);
    }
    unset($row);
    return $rows;
}

function load_editable_lead(string $leadUuid, int $userId): ?array
{
    $statement = db()->prepare(
        "SELECT l.id, l.uuid, l.nome name, l.bio, l.bio_url, l.origem origin, l.observacoes notes,
                MAX(CASE WHEN lc.tipo = 'email' THEN lc.valor END) email,
                MAX(CASE WHEN lc.tipo = 'whatsapp' THEN lc.valor END) whatsapp,
                MAX(CASE WHEN lc.tipo = 'instagram' THEN lc.valor END) instagram
         FROM leads l
         INNER JOIN oportunidades o ON o.lead_id = l.id
         LEFT JOIN lead_contatos lc ON lc.lead_id = l.id
         WHERE l.uuid = :uuid
           AND l.captador_usuario_id = :user_id
           AND o.status = 'aberta'
           AND o.desenvolvedor_usuario_id IS NULL
         GROUP BY l.id, l.uuid, l.nome, l.bio, l.bio_url, l.origem, l.observacoes
         LIMIT 1"
    );
    $statement->execute(['uuid' => $leadUuid, 'user_id' => $userId]);
    $lead = $statement->fetch();
    if (!is_array($lead)) {
        return null;
    }
    $lead['references'] = list_lead_reference_images((int) $lead['id']);
    return $lead;
}

function create_lead(array $input, array $actor, array $uploadBag = []): string
{
    $hasReferences = normalize_uploaded_images($uploadBag) !== [];
    $payload = lead_form_payload($input, $hasReferences);
    $leadUuid = uuid_v4();
    $connection = db();
    $connection->beginTransaction();

    try {
        $lead = $connection->prepare(
            "INSERT INTO leads
                (uuid, captador_usuario_id, nome, bio, bio_url, observacoes, origem, status, temperatura, qualificado_em)
             VALUES
                (:uuid, :captor_id, :name, :bio, :bio_url, :notes, :origin, 'qualificado', :temperature, CURRENT_TIMESTAMP(6))"
        );
        $lead->execute([
            'uuid' => $leadUuid,
            'captor_id' => $actor['id'],
            'name' => $payload['name'],
            'bio' => $payload['bio'] !== '' ? $payload['bio'] : null,
            'bio_url' => $payload['bioUrl'] !== '' ? $payload['bioUrl'] : null,
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
            'origin' => $payload['origin'] !== '' ? $payload['origin'] : null,
            'temperature' => $payload['temperature'],
        ]);
        $leadId = (int) $connection->lastInsertId();

        insert_lead_contacts($connection, $leadId, $payload, (int) $actor['id']);
        insert_lead_completeness_revision($connection, $leadId, (int) $actor['id'], $payload);

        $opportunityUuid = uuid_v4();
        $opportunity = $connection->prepare(
            "INSERT INTO oportunidades (uuid, lead_id, titulo, descricao, status)
             VALUES (:uuid, :lead_id, :title, :description, 'aberta')"
        );
        $opportunity->execute([
            'uuid' => $opportunityUuid,
            'lead_id' => $leadId,
            'title' => 'Projeto para ' . $payload['name'],
            'description' => $payload['bio'] !== '' ? $payload['bio'] : $payload['notes'],
        ]);

        store_lead_reference_images($connection, $leadId, $leadUuid, (int) $actor['id'], $uploadBag);
        audit_event($connection, 'lead.criado', 'lead', $leadUuid, $actor['uuid'], [
            'completude' => $payload['completeness'],
            'temperatura' => $payload['temperature'],
            'oportunidade_uuid' => $opportunityUuid,
        ]);
        award_points($connection, (int) $actor['id'], 'lead_cadastrado', 'lead', $leadUuid, $actor['uuid']);
        $connection->commit();
        return $leadUuid;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function update_lead(array $input, array $actor, array $uploadBag = []): void
{
    $leadUuid = (string) ($input['lead_uuid'] ?? '');
    $connection = db();
    $connection->beginTransaction();

    try {
        $leadId = editable_lead_id($connection, $leadUuid, (int) $actor['id']);
        if ($leadId === null) {
            throw new InvalidArgumentException('Este cadastro não pode mais ser editado.');
        }
        $connection->prepare('SELECT id FROM leads WHERE id = :id FOR UPDATE')->execute(['id' => $leadId]);
        $referenceCount = $connection->prepare(
            'SELECT COUNT(*) FROM lead_referencia_arquivos WHERE lead_id = :lead_id'
        );
        $referenceCount->execute(['lead_id' => $leadId]);
        $hasReferences = (int) $referenceCount->fetchColumn() > 0
            || normalize_uploaded_images($uploadBag) !== [];
        $payload = lead_form_payload($input, $hasReferences);

        $update = $connection->prepare(
            'UPDATE leads
             SET nome = :name, bio = :bio, bio_url = :bio_url, observacoes = :notes,
                 origem = :origin, temperatura = :temperature
             WHERE id = :id'
        );
        $update->execute([
            'name' => $payload['name'],
            'bio' => $payload['bio'] !== '' ? $payload['bio'] : null,
            'bio_url' => $payload['bioUrl'] !== '' ? $payload['bioUrl'] : null,
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
            'origin' => $payload['origin'] !== '' ? $payload['origin'] : null,
            'temperature' => $payload['temperature'],
            'id' => $leadId,
        ]);

        $connection->prepare('DELETE FROM lead_contatos WHERE lead_id = :lead_id')
            ->execute(['lead_id' => $leadId]);
        insert_lead_contacts($connection, $leadId, $payload, (int) $actor['id']);
        insert_lead_completeness_revision($connection, $leadId, (int) $actor['id'], $payload);
        store_lead_reference_images($connection, $leadId, $leadUuid, (int) $actor['id'], $uploadBag);

        $opportunity = $connection->prepare(
            'UPDATE oportunidades SET titulo = :title, descricao = :description WHERE lead_id = :lead_id'
        );
        $opportunity->execute([
            'title' => 'Projeto para ' . $payload['name'],
            'description' => $payload['bio'] !== '' ? $payload['bio'] : $payload['notes'],
            'lead_id' => $leadId,
        ]);

        audit_event($connection, 'lead.atualizado', 'lead', $leadUuid, $actor['uuid'], [
            'completude' => $payload['completeness'],
            'temperatura' => $payload['temperature'],
        ]);
        award_points($connection, (int) $actor['id'], 'lead_enriquecido', 'lead', $leadUuid, $actor['uuid']);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function list_developer_work(int $userId): array
{
    // Oportunidades abertas retornam somente metadados operacionais.
    // Dados do lead são liberados apenas depois da atribuição atômica.
    $open = db()->query(
        "SELECT o.uuid, o.created_at
         FROM oportunidades o
         WHERE o.status = 'aberta' AND o.desenvolvedor_usuario_id IS NULL
         ORDER BY o.created_at
         LIMIT 50"
    )->fetchAll();

    $mineStatement = db()->prepare(
        "SELECT o.uuid, o.titulo, o.descricao oportunidade_descricao, o.status, o.assumida_em,
                l.id lead_id, l.nome lead_nome, l.bio, l.bio_url, l.referencias, l.observacoes,
                p.uuid projeto_uuid, p.nome projeto_nome, p.descricao projeto_descricao,
                p.url_preview, p.status projeto_status,
                GROUP_CONCAT(CONCAT(lc.tipo, ': ', lc.valor) ORDER BY lc.tipo SEPARATOR ' | ') contatos
         FROM oportunidades o
         INNER JOIN leads l ON l.id = o.lead_id
         LEFT JOIN lead_contatos lc ON lc.lead_id = l.id
         LEFT JOIN projetos p
           ON p.oportunidade_id = o.id
          AND p.desenvolvedor_usuario_id = o.desenvolvedor_usuario_id
          AND p.status <> 'arquivado'
         WHERE o.desenvolvedor_usuario_id = :user_id
           AND o.status NOT IN ('vendida','perdida','cancelada')
         GROUP BY o.id, o.uuid, o.titulo, o.descricao, o.status, o.assumida_em,
                  l.id, l.nome, l.bio, l.bio_url, l.referencias, l.observacoes,
                  p.uuid, p.nome, p.descricao, p.url_preview, p.status
         ORDER BY o.updated_at DESC"
    );
    $mineStatement->execute(['user_id' => $userId]);
    $mine = $mineStatement->fetchAll();
    foreach ($mine as &$item) {
        $item['reference_images'] = list_lead_reference_images((int) $item['lead_id']);
        unset($item['lead_id']);
    }
    unset($item);

    return ['open' => $open, 'mine' => $mine];
}
function claim_opportunity(string $uuid, array $actor): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare(
            "UPDATE oportunidades
             SET desenvolvedor_usuario_id = :user_id, status = 'assumida', assumida_em = CURRENT_TIMESTAMP(6)
             WHERE uuid = :uuid AND desenvolvedor_usuario_id IS NULL AND status = 'aberta'"
        );
        $statement->execute(['user_id' => $actor['id'], 'uuid' => $uuid]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('A oportunidade já foi assumida ou não está disponível.');
        }
        audit_event($connection, 'oportunidade.assumida', 'oportunidade', $uuid, $actor['uuid']);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function submit_project(array $input, array $actor): string
{
    $opportunityUuid = (string) ($input['opportunity_uuid'] ?? '');
    $name = trim((string) ($input['project_name'] ?? ''));
    $previewUrl = trim((string) ($input['preview_url'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));

    if (strlen($name) < 2 || strlen($name) > 180) {
        throw new InvalidArgumentException('Informe o nome do projeto.');
    }
    if (!valid_web_url($previewUrl)) {
        throw new InvalidArgumentException('Informe uma URL de preview válida.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            "SELECT o.id, o.status, p.id projeto_id, p.uuid projeto_uuid
             FROM oportunidades o
             LEFT JOIN projetos p
               ON p.oportunidade_id = o.id
              AND p.desenvolvedor_usuario_id = :project_developer_id
              AND p.status NOT IN ('arquivado','reprovado')
             WHERE o.uuid = :uuid AND o.desenvolvedor_usuario_id = :opportunity_developer_id
             FOR UPDATE"
        );
        $find->execute([
            'uuid' => $opportunityUuid,
            'project_developer_id' => $actor['id'],
            'opportunity_developer_id' => $actor['id'],
        ]);
        $work = $find->fetch();
        if (!is_array($work) || !in_array($work['status'], ['assumida','em_desenvolvimento','ajustes'], true)) {
            throw new InvalidArgumentException('Oportunidade indisponível para envio.');
        }

        if ($work['projeto_id'] === null) {
            $projectUuid = uuid_v4();
            $insert = $connection->prepare(
                "INSERT INTO projetos
                    (uuid, oportunidade_id, desenvolvedor_usuario_id, nome, descricao, url_preview, status)
                 VALUES
                    (:uuid, :opportunity_id, :developer_id, :name, :description, :preview_url, 'em_revisao')"
            );
            $insert->execute([
                'uuid' => $projectUuid,
                'opportunity_id' => $work['id'],
                'developer_id' => $actor['id'],
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'preview_url' => $previewUrl,
            ]);
            $projectId = (int) $connection->lastInsertId();
        } else {
            $projectUuid = $work['projeto_uuid'];
            $projectId = (int) $work['projeto_id'];
            $update = $connection->prepare(
                "UPDATE projetos
                 SET nome = :name, descricao = :description, url_preview = :preview_url, status = 'em_revisao'
                 WHERE id = :id"
            );
            $update->execute([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'preview_url' => $previewUrl,
                'id' => $projectId,
            ]);
        }

        $numberStatement = $connection->prepare(
            'SELECT COALESCE(MAX(submissao_numero), 0) + 1 FROM projeto_revisoes WHERE projeto_id = :project_id'
        );
        $numberStatement->execute(['project_id' => $projectId]);
        $submissionNumber = (int) $numberStatement->fetchColumn();

        $revision = $connection->prepare(
            "INSERT INTO projeto_revisoes
                (uuid, projeto_id, submissao_numero, decisao, observacoes, url_preview_snapshot)
             VALUES
                (:uuid, :project_id, :number, 'pendente', :notes, :preview_url)"
        );
        $revision->execute([
            'uuid' => uuid_v4(),
            'project_id' => $projectId,
            'number' => $submissionNumber,
            'notes' => $description !== '' ? $description : null,
            'preview_url' => $previewUrl,
        ]);

        $opportunity = $connection->prepare("UPDATE oportunidades SET status = 'em_revisao' WHERE id = :id");
        $opportunity->execute(['id' => $work['id']]);
        audit_event($connection, 'projeto.enviado_revisao', 'projeto', $projectUuid, $actor['uuid'], [
            'submissao' => $submissionNumber,
        ]);
        $connection->commit();
        return $projectUuid;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function list_pending_project_reviews(): array
{
    return db()->query(
        "SELECT r.uuid revisao_uuid, r.submissao_numero, r.submetido_em, r.observacoes,
                p.uuid projeto_uuid, p.nome projeto_nome, p.url_preview,
                l.nome lead_nome, u.nome desenvolvedor_nome
         FROM projeto_revisoes r
         INNER JOIN projetos p ON p.id = r.projeto_id
         INNER JOIN oportunidades o ON o.id = p.oportunidade_id
         INNER JOIN leads l ON l.id = o.lead_id
         INNER JOIN usuarios u ON u.id = p.desenvolvedor_usuario_id
         WHERE r.decisao = 'pendente'
         ORDER BY r.submetido_em"
    )->fetchAll();
}

function review_project(array $input, array $actor): void
{
    $revisionUuid = (string) ($input['revision_uuid'] ?? '');
    $decision = (string) ($input['decision'] ?? '');
    $notes = trim((string) ($input['notes'] ?? ''));
    $rejectionAction = (string) ($input['rejection_action'] ?? 'archive');

    if (!in_array($decision, ['aprovado','ajustes','reprovado'], true)) {
        throw new InvalidArgumentException('Decisão inválida.');
    }
    if ($decision !== 'aprovado' && strlen($notes) < 3) {
        throw new InvalidArgumentException('Informe o motivo ou as orientações para o desenvolvedor.');
    }
    if ($decision === 'reprovado' && !in_array($rejectionAction, ['requeue','archive'], true)) {
        throw new InvalidArgumentException('Destino da reprovação inválido.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            "SELECT r.id revisao_id, r.decisao, p.id projeto_id, p.uuid projeto_uuid, p.oportunidade_id,
                    p.desenvolvedor_usuario_id
             FROM projeto_revisoes r
             INNER JOIN projetos p ON p.id = r.projeto_id
             WHERE r.uuid = :uuid
             FOR UPDATE"
        );
        $find->execute(['uuid' => $revisionUuid]);
        $review = $find->fetch();
        if (!is_array($review) || $review['decisao'] !== 'pendente') {
            throw new InvalidArgumentException('Revisão indisponível.');
        }

        $updateReview = $connection->prepare(
            'UPDATE projeto_revisoes
             SET decisao = :decision, observacoes = :notes, revisor_usuario_id = :reviewer_id,
                 decidido_em = CURRENT_TIMESTAMP(6)
             WHERE id = :id'
        );
        $updateReview->execute([
            'decision' => $decision,
            'notes' => $notes !== '' ? $notes : null,
            'reviewer_id' => $actor['id'],
            'id' => $review['revisao_id'],
        ]);

        if ($decision === 'aprovado') {
            $value = (float) str_replace(',', '.', (string) ($input['value_brl'] ?? '0'));
            $paymentLink = trim((string) ($input['payment_link'] ?? ''));
            if ($value <= 0 || !valid_web_url($paymentLink, true)) {
                throw new InvalidArgumentException('Para aprovar, informe valor positivo e link HTTPS de pagamento.');
            }

            $projectUpdate = $connection->prepare(
                "UPDATE projetos
                 SET status = 'aprovado', aprovado_por_usuario_id = :reviewer_id,
                     aprovado_em = CURRENT_TIMESTAMP(6)
                 WHERE id = :id"
            );
            $projectUpdate->execute(['reviewer_id' => $actor['id'], 'id' => $review['projeto_id']]);
            $connection->prepare("UPDATE oportunidades SET status = 'em_venda' WHERE id = :id")
                ->execute(['id' => $review['oportunidade_id']]);

            $offerUuid = uuid_v4();
            $offer = $connection->prepare(
                "INSERT INTO ofertas
                    (uuid, projeto_id, criado_por_usuario_id, valor_brl, provedor_pagamento, link_pagamento, status)
                 VALUES
                    (:uuid, :project_id, :creator_id, :value_brl, 'mercado_pago', :payment_link, 'ativa')"
            );
            $offer->execute([
                'uuid' => $offerUuid,
                'project_id' => $review['projeto_id'],
                'creator_id' => $actor['id'],
                'value_brl' => number_format($value, 2, '.', ''),
                'payment_link' => $paymentLink,
            ]);

            $sale = $connection->prepare(
                "INSERT INTO vendas (uuid, oferta_id, status) VALUES (:uuid, :offer_id, 'disponivel')"
            );
            $sale->execute(['uuid' => uuid_v4(), 'offer_id' => $connection->lastInsertId()]);
            award_points(
                $connection,
                (int) $review['desenvolvedor_usuario_id'],
                'projeto_aprovado',
                'projeto',
                (string) $review['projeto_uuid'],
                $actor['uuid']
            );
        } elseif ($decision === 'ajustes') {
            $connection->prepare("UPDATE projetos SET status = 'ajustes' WHERE id = :id")
                ->execute(['id' => $review['projeto_id']]);
            $connection->prepare("UPDATE oportunidades SET status = 'ajustes' WHERE id = :id")
                ->execute(['id' => $review['oportunidade_id']]);
        } else {
            if ($rejectionAction === 'requeue') {
                $connection->prepare("UPDATE projetos SET status = 'arquivado' WHERE id = :id")
                    ->execute(['id' => $review['projeto_id']]);
                $connection->prepare(
                    "UPDATE oportunidades
                     SET status = 'aberta', desenvolvedor_usuario_id = NULL,
                         assumida_em = NULL, encerrada_em = NULL
                     WHERE id = :id"
                )->execute(['id' => $review['oportunidade_id']]);
            } else {
                $connection->prepare("UPDATE projetos SET status = 'reprovado' WHERE id = :id")
                    ->execute(['id' => $review['projeto_id']]);
                $connection->prepare(
                    "UPDATE oportunidades
                     SET status = 'cancelada', encerrada_em = CURRENT_TIMESTAMP(6)
                     WHERE id = :id"
                )->execute(['id' => $review['oportunidade_id']]);
            }
        }

        audit_event($connection, 'projeto.revisado', 'projeto', $review['projeto_uuid'], $actor['uuid'], [
            'decisao' => $decision,
            'destino_reprovacao' => $decision === 'reprovado' ? $rejectionAction : null,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function list_commercial_sales(int $userId): array
{
    // A fila aberta não carrega lead, contatos, valor, preview ou pagamento.
    $available = db()->query(
        "SELECT v.uuid, v.status, v.created_at
         FROM vendas v
         WHERE v.status = 'disponivel' AND v.comercial_usuario_id IS NULL
         ORDER BY v.created_at
         LIMIT 50"
    )->fetchAll();

    $mineStatement = db()->prepare(
        "SELECT v.uuid, v.status, v.assumida_em, v.updated_at,
                o.valor_brl, o.link_pagamento,
                p.nome projeto_nome, p.url_preview, l.nome lead_nome,
                GROUP_CONCAT(CONCAT(lc.tipo, ': ', lc.valor) ORDER BY lc.tipo SEPARATOR ' | ') contatos
         FROM vendas v
         INNER JOIN ofertas o ON o.id = v.oferta_id
         INNER JOIN projetos p ON p.id = o.projeto_id
         INNER JOIN oportunidades op ON op.id = p.oportunidade_id
         INNER JOIN leads l ON l.id = op.lead_id
         LEFT JOIN lead_contatos lc ON lc.lead_id = l.id
         WHERE v.comercial_usuario_id = :user_id
           AND v.status IN ('em_atendimento','aguardando_pagamento')
         GROUP BY v.id, v.uuid, v.status, v.assumida_em, v.updated_at,
                  o.valor_brl, o.link_pagamento, p.nome, p.url_preview, l.nome
         ORDER BY v.updated_at DESC"
    );
    $mineStatement->execute(['user_id' => $userId]);

    return ['available' => $available, 'mine' => $mineStatement->fetchAll()];
}
function claim_sale(string $uuid, array $actor): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare(
            "UPDATE vendas
             SET comercial_usuario_id = :user_id, status = 'em_atendimento', assumida_em = CURRENT_TIMESTAMP(6)
             WHERE uuid = :uuid AND comercial_usuario_id IS NULL AND status = 'disponivel'"
        );
        $statement->execute(['user_id' => $actor['id'], 'uuid' => $uuid]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('A venda já foi assumida ou não está disponível.');
        }
        audit_event($connection, 'venda.assumida', 'venda', $uuid, $actor['uuid']);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function record_sale_interaction(array $input, array $actor): void
{
    $saleUuid = (string) ($input['sale_uuid'] ?? '');
    $channel = (string) ($input['channel'] ?? '');
    $result = (string) ($input['result'] ?? '');
    $notes = trim((string) ($input['notes'] ?? ''));
    $nextContact = trim((string) ($input['next_contact_at'] ?? ''));

    if (!in_array($channel, ['whatsapp','email','instagram','telefone','outro'], true)) {
        throw new InvalidArgumentException('Canal inválido.');
    }
    if (!in_array($result, ['contato_realizado','retorno_agendado','aguardando_pagamento','perdido'], true)) {
        throw new InvalidArgumentException('Resultado inválido.');
    }
    if ($nextContact !== '' && strtotime($nextContact) === false) {
        throw new InvalidArgumentException('Data do próximo contato inválida.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            'SELECT id FROM vendas WHERE uuid = :uuid AND comercial_usuario_id = :user_id FOR UPDATE'
        );
        $find->execute(['uuid' => $saleUuid, 'user_id' => $actor['id']]);
        $saleId = $find->fetchColumn();
        if ($saleId === false) {
            throw new InvalidArgumentException('Venda não encontrada para este usuário.');
        }

        $interaction = $connection->prepare(
            'INSERT INTO interacoes_comerciais
                (uuid, venda_id, comercial_usuario_id, canal, resultado, observacoes, proximo_contato_em)
             VALUES
                (:uuid, :sale_id, :user_id, :channel, :result, :notes, :next_contact)'
        );
        $interaction->execute([
            'uuid' => uuid_v4(),
            'sale_id' => $saleId,
            'user_id' => $actor['id'],
            'channel' => $channel,
            'result' => $result,
            'notes' => $notes !== '' ? $notes : null,
            'next_contact' => $nextContact !== '' ? date('Y-m-d H:i:s', strtotime($nextContact)) : null,
        ]);

        if ($result === 'aguardando_pagamento') {
            $connection->prepare("UPDATE vendas SET status = 'aguardando_pagamento' WHERE id = :id")
                ->execute(['id' => $saleId]);
        } elseif ($result === 'perdido') {
            $connection->prepare(
                "UPDATE vendas SET status = 'perdida', perdida_em = CURRENT_TIMESTAMP(6), perda_motivo = :reason WHERE id = :id"
            )->execute(['reason' => $notes !== '' ? $notes : 'Não informado', 'id' => $saleId]);
        }

        audit_event($connection, 'venda.interacao_registrada', 'venda', $saleUuid, $actor['uuid'], [
            'canal' => $channel,
            'resultado' => $result,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
