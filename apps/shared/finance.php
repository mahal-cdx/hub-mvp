<?php

declare(strict_types=1);

function points_event_catalog(): array
{
    return [
        'lead_cadastrado' => ['role' => 'captador', 'label' => 'Lead cadastrado'],
        'lead_enriquecido' => ['role' => 'captador', 'label' => 'Lead enriquecido'],
        'projeto_aprovado' => ['role' => 'desenvolvedor', 'label' => 'Projeto aprovado'],
        'venda_paga_captador' => ['role' => 'captador', 'label' => 'Venda paga · captador'],
        'venda_paga_desenvolvedor' => ['role' => 'desenvolvedor', 'label' => 'Venda paga · desenvolvedor'],
        'venda_paga_comercial' => ['role' => 'comercial', 'label' => 'Venda paga · comercial'],
    ];
}

function list_scoring_rules(): array
{
    $rows = db()->query(
        "SELECT r.evento_chave, r.pontos, r.versao, r.vigente_desde, f.chave funcao
         FROM regras_pontuacao r
         INNER JOIN funcoes f ON f.id = r.funcao_id
         INNER JOIN (
             SELECT evento_chave, MAX(versao) versao
             FROM regras_pontuacao
             GROUP BY evento_chave
         ) latest ON latest.evento_chave = r.evento_chave AND latest.versao = r.versao
         ORDER BY r.evento_chave"
    )->fetchAll();

    $indexed = [];
    foreach ($rows as $row) {
        $indexed[$row['evento_chave']] = $row;
    }

    $result = [];
    foreach (points_event_catalog() as $key => $definition) {
        $result[] = [
            'event' => $key,
            'label' => $definition['label'],
            'role' => $definition['role'],
            'points' => isset($indexed[$key]) ? (int) $indexed[$key]['pontos'] : null,
            'version' => isset($indexed[$key]) ? (int) $indexed[$key]['versao'] : null,
            'effective_at' => $indexed[$key]['vigente_desde'] ?? null,
        ];
    }
    return $result;
}

function save_scoring_rule(array $input, array $actor): void
{
    $event = (string) ($input['event'] ?? '');
    $pointsRaw = trim((string) ($input['points'] ?? ''));
    $catalog = points_event_catalog();

    if (!isset($catalog[$event])) {
        throw new InvalidArgumentException('Tarefa de pontuação inválida.');
    }
    if (!ctype_digit($pointsRaw) || (int) $pointsRaw < 1 || (int) $pointsRaw > 1000000) {
        throw new InvalidArgumentException('Informe uma quantidade inteira entre 1 e 1.000.000 pontos.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $role = $connection->prepare('SELECT id FROM funcoes WHERE chave = :role');
        $role->execute(['role' => $catalog[$event]['role']]);
        $roleId = $role->fetchColumn();
        if ($roleId === false) {
            throw new RuntimeException('Função da regra não encontrada.');
        }

        $lock = $connection->prepare(
            'SELECT id, versao FROM regras_pontuacao WHERE evento_chave = :event ORDER BY versao DESC FOR UPDATE'
        );
        $lock->execute(['event' => $event]);
        $current = $lock->fetch();
        $version = is_array($current) ? (int) $current['versao'] + 1 : 1;

        $close = $connection->prepare(
            'UPDATE regras_pontuacao SET ativo = 0, vigente_ate = CURRENT_TIMESTAMP(6)
             WHERE evento_chave = :event AND ativo = 1'
        );
        $close->execute(['event' => $event]);

        $uuid = uuid_v4();
        $insert = $connection->prepare(
            'INSERT INTO regras_pontuacao
                (uuid, funcao_id, evento_chave, versao, pontos, vigente_desde, ativo, criado_por_usuario_id)
             VALUES
                (:uuid, :role_id, :event, :version, :points, CURRENT_TIMESTAMP(6), 1, :actor_id)'
        );
        $insert->execute([
            'uuid' => $uuid,
            'role_id' => $roleId,
            'event' => $event,
            'version' => $version,
            'points' => (int) $pointsRaw,
            'actor_id' => $actor['id'],
        ]);
        audit_event($connection, 'pontuacao.regra_atualizada', 'regra_pontuacao', $uuid, $actor['uuid'], [
            'evento' => $event,
            'pontos' => (int) $pointsRaw,
            'versao' => $version,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function award_points(
    PDO $connection,
    int $userId,
    string $event,
    string $originType,
    string $originUuid,
    ?string $actorUuid = null
): int {
    $catalog = points_event_catalog();
    if (!isset($catalog[$event])) {
        throw new InvalidArgumentException('Evento de pontuação inválido.');
    }

    $rule = $connection->prepare(
        "SELECT r.id, r.pontos
         FROM regras_pontuacao r
         INNER JOIN funcoes f ON f.id = r.funcao_id
         WHERE r.evento_chave = :event
           AND f.chave = :role
           AND r.ativo = 1
           AND r.vigente_desde <= CURRENT_TIMESTAMP(6)
           AND (r.vigente_ate IS NULL OR r.vigente_ate > CURRENT_TIMESTAMP(6))
         ORDER BY r.versao DESC
         LIMIT 1"
    );
    $rule->execute(['event' => $event, 'role' => $catalog[$event]['role']]);
    $activeRule = $rule->fetch();
    if (!is_array($activeRule)) {
        return 0;
    }

    $wallet = $connection->prepare(
        'INSERT IGNORE INTO carteiras (uuid, usuario_id) VALUES (:uuid, :user_id)'
    );
    $wallet->execute(['uuid' => uuid_v4(), 'user_id' => $userId]);

    $idempotency = hash('sha256', implode(':', ['points', $event, $originType, $originUuid, $userId]));
    $eventUuid = uuid_v4();
    $eventInsert = $connection->prepare(
        'INSERT IGNORE INTO eventos_pontuacao
            (uuid, usuario_id, regra_pontuacao_id, evento_chave, origem_tipo, origem_uuid, pontos, idempotency_key, detalhes)
         VALUES
            (:uuid, :user_id, :rule_id, :event, :origin_type, :origin_uuid, :points, :idempotency, :details)'
    );
    $eventInsert->execute([
        'uuid' => $eventUuid,
        'user_id' => $userId,
        'rule_id' => $activeRule['id'],
        'event' => $event,
        'origin_type' => $originType,
        'origin_uuid' => $originUuid,
        'points' => $activeRule['pontos'],
        'idempotency' => $idempotency,
        'details' => json_encode(['regra_evento' => $event], JSON_THROW_ON_ERROR),
    ]);
    if ($eventInsert->rowCount() !== 1) {
        return 0;
    }

    $walletId = $connection->prepare('SELECT id FROM carteiras WHERE usuario_id = :user_id FOR UPDATE');
    $walletId->execute(['user_id' => $userId]);
    $walletValue = $walletId->fetchColumn();
    if ($walletValue === false) {
        throw new RuntimeException('Carteira não encontrada.');
    }

    $ledger = $connection->prepare(
        "INSERT INTO lancamentos_pontos
            (uuid, carteira_id, usuario_id, tipo, pontos, origem_tipo, origem_uuid, idempotency_key, descricao)
         VALUES
            (:uuid, :wallet_id, :user_id, 'credito', :points, :origin_type, :origin_uuid, :idempotency, :description)"
    );
    $ledger->execute([
        'uuid' => uuid_v4(),
        'wallet_id' => $walletValue,
        'user_id' => $userId,
        'points' => $activeRule['pontos'],
        'origin_type' => $originType,
        'origin_uuid' => $originUuid,
        'idempotency' => 'ledger:' . $idempotency,
        'description' => $catalog[$event]['label'],
    ]);
    $connection->prepare(
        'UPDATE carteiras SET saldo_disponivel_pontos = saldo_disponivel_pontos + :points WHERE id = :id'
    )->execute(['points' => $activeRule['pontos'], 'id' => $walletValue]);

    audit_event($connection, 'pontos.creditados', 'evento_pontuacao', $eventUuid, $actorUuid, [
        'usuario_id' => $userId,
        'evento' => $event,
        'pontos' => (int) $activeRule['pontos'],
        'origem_uuid' => $originUuid,
    ]);
    return (int) $activeRule['pontos'];
}

function current_point_quote(bool $forUpdate = false): ?array
{
    $sql = "SELECT c.id, c.uuid, c.valor_brl, c.vigente_desde, c.motivo, u.nome definido_por
            FROM cotacoes_ponto c
            INNER JOIN usuarios u ON u.id = c.definido_por_usuario_id
            WHERE c.vigente_desde <= CURRENT_TIMESTAMP(6)
              AND (c.vigente_ate IS NULL OR c.vigente_ate > CURRENT_TIMESTAMP(6))
            ORDER BY c.vigente_desde DESC
            LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '');
    $row = db()->query($sql)->fetch();
    return is_array($row) ? $row : null;
}

function save_point_quote(array $input, array $actor): void
{
    $normalized = str_replace(',', '.', trim((string) ($input['value_brl'] ?? '')));
    $reason = trim((string) ($input['reason'] ?? ''));
    if (!is_numeric($normalized) || (float) $normalized <= 0 || (float) $normalized > 10000) {
        throw new InvalidArgumentException('Informe um valor válido para o ponto.');
    }
    if (strlen($reason) < 3 || strlen($reason) > 255) {
        throw new InvalidArgumentException('Informe o motivo da nova cotação.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $connection->query(
            'SELECT id FROM cotacoes_ponto
             WHERE vigente_ate IS NULL OR vigente_ate > CURRENT_TIMESTAMP(6)
             FOR UPDATE'
        )->fetchAll();
        $connection->exec(
            'UPDATE cotacoes_ponto SET vigente_ate = CURRENT_TIMESTAMP(6)
             WHERE vigente_ate IS NULL OR vigente_ate > CURRENT_TIMESTAMP(6)'
        );
        $uuid = uuid_v4();
        $insert = $connection->prepare(
            'INSERT INTO cotacoes_ponto
                (uuid, valor_brl, vigente_desde, definido_por_usuario_id, motivo)
             VALUES
                (:uuid, :value_brl, CURRENT_TIMESTAMP(6), :actor_id, :reason)'
        );
        $insert->execute([
            'uuid' => $uuid,
            'value_brl' => number_format((float) $normalized, 6, '.', ''),
            'actor_id' => $actor['id'],
            'reason' => $reason,
        ]);
        audit_event($connection, 'pontos.cotacao_atualizada', 'cotacao_ponto', $uuid, $actor['uuid'], [
            'valor_brl' => number_format((float) $normalized, 6, '.', ''),
            'motivo' => $reason,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function list_pending_payments(): array
{
    return db()->query(
        "SELECT v.uuid venda_uuid, v.status, v.assumida_em, o.valor_brl, o.link_pagamento,
                p.nome projeto_nome, l.nome lead_nome, c.nome comercial_nome
         FROM vendas v
         INNER JOIN ofertas o ON o.id = v.oferta_id
         INNER JOIN projetos p ON p.id = o.projeto_id
         INNER JOIN oportunidades op ON op.id = p.oportunidade_id
         INNER JOIN leads l ON l.id = op.lead_id
         LEFT JOIN usuarios c ON c.id = v.comercial_usuario_id
         WHERE v.status = 'aguardando_pagamento'
         ORDER BY v.updated_at"
    )->fetchAll();
}

function confirm_manual_payment(array $input, array $actor): array
{
    $saleUuid = (string) ($input['sale_uuid'] ?? '');
    $reference = trim((string) ($input['reference'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? ''));
    if ($reference === '' || strlen($reference) > 190) {
        throw new InvalidArgumentException('Informe a referência do comprovante.');
    }
    if (strlen($notes) > 1000) {
        throw new InvalidArgumentException('As observações são muito longas.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            "SELECT v.id venda_id, v.status, v.comercial_usuario_id, o.id oferta_id, o.valor_brl,
                    p.desenvolvedor_usuario_id, op.id oportunidade_id, op.uuid oportunidade_uuid,
                    l.captador_usuario_id
             FROM vendas v
             INNER JOIN ofertas o ON o.id = v.oferta_id
             INNER JOIN projetos p ON p.id = o.projeto_id
             INNER JOIN oportunidades op ON op.id = p.oportunidade_id
             INNER JOIN leads l ON l.id = op.lead_id
             WHERE v.uuid = :uuid
             FOR UPDATE"
        );
        $find->execute(['uuid' => $saleUuid]);
        $sale = $find->fetch();
        if (!is_array($sale) || $sale['status'] !== 'aguardando_pagamento') {
            throw new InvalidArgumentException('A venda não está aguardando pagamento.');
        }
        if ($sale['comercial_usuario_id'] === null) {
            throw new InvalidArgumentException('A venda não possui responsável comercial.');
        }

        $paymentUuid = uuid_v4();
        $idempotency = 'manual-payment:' . $saleUuid;
        $payment = $connection->prepare(
            "INSERT INTO pagamentos
                (uuid, venda_id, provedor, referencia_externa, idempotency_key, valor_brl, status,
                 confirmado_por_usuario_id, confirmado_em)
             VALUES
                (:uuid, :sale_id, 'manual_mercado_pago', :reference, :idempotency, :value_brl, 'aprovado',
                 :actor_id, CURRENT_TIMESTAMP(6))"
        );
        $payment->execute([
            'uuid' => $paymentUuid,
            'sale_id' => $sale['venda_id'],
            'reference' => $reference,
            'idempotency' => $idempotency,
            'value_brl' => $sale['valor_brl'],
            'actor_id' => $actor['id'],
        ]);
        $paymentId = (int) $connection->lastInsertId();

        $event = $connection->prepare(
            'INSERT INTO pagamento_eventos
                (uuid, pagamento_id, ator_usuario_id, tipo, idempotency_key, referencia_externa, dados)
             VALUES
                (:uuid, :payment_id, :actor_id, :type, :idempotency, :reference, :data)'
        );
        $event->execute([
            'uuid' => uuid_v4(),
            'payment_id' => $paymentId,
            'actor_id' => $actor['id'],
            'type' => 'aprovacao_manual',
            'idempotency' => 'payment-event:' . $saleUuid,
            'reference' => $reference,
            'data' => json_encode(['observacoes' => $notes], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);

        $connection->prepare(
            "UPDATE vendas SET status = 'fechada', fechada_em = CURRENT_TIMESTAMP(6) WHERE id = :id"
        )->execute(['id' => $sale['venda_id']]);
        $connection->prepare(
            "UPDATE ofertas SET status = 'inativa', vigente_ate = CURRENT_TIMESTAMP(6) WHERE id = :id"
        )->execute(['id' => $sale['oferta_id']]);
        $connection->prepare(
            "UPDATE oportunidades SET status = 'vendida', encerrada_em = CURRENT_TIMESTAMP(6) WHERE id = :id"
        )->execute(['id' => $sale['oportunidade_id']]);

        $credits = [
            'captador' => award_points($connection, (int) $sale['captador_usuario_id'], 'venda_paga_captador', 'venda', $saleUuid, $actor['uuid']),
            'desenvolvedor' => award_points($connection, (int) $sale['desenvolvedor_usuario_id'], 'venda_paga_desenvolvedor', 'venda', $saleUuid, $actor['uuid']),
            'comercial' => award_points($connection, (int) $sale['comercial_usuario_id'], 'venda_paga_comercial', 'venda', $saleUuid, $actor['uuid']),
        ];
        if (in_array(0, $credits, true)) {
            throw new InvalidArgumentException(
                'Configure as três regras de bônus por venda paga antes de aprovar o pagamento.'
            );
        }
        audit_event($connection, 'pagamento.aprovado_manual', 'pagamento', $paymentUuid, $actor['uuid'], [
            'venda_uuid' => $saleUuid,
            'referencia' => $reference,
            'creditos' => $credits,
        ]);
        $connection->commit();
        return $credits;
    } catch (PDOException $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        if ($error->getCode() === '23000') {
            throw new InvalidArgumentException('Este comprovante ou pagamento já foi registrado.');
        }
        throw $error;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function encrypt_payout_secret(string $value): string
{
    $key = hash('sha256', (string) config('app_key'), true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Não foi possível proteger a chave Pix.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_payout_secret(string $payload): string
{
    $decoded = base64_decode($payload, true);
    if ($decoded === false || strlen($decoded) < 29) {
        return '[indisponível]';
    }
    $iv = substr($decoded, 0, 12);
    $tag = substr($decoded, 12, 16);
    $ciphertext = substr($decoded, 28);
    $value = openssl_decrypt($ciphertext, 'aes-256-gcm', hash('sha256', (string) config('app_key'), true), OPENSSL_RAW_DATA, $iv, $tag);
    return is_string($value) ? $value : '[indisponível]';
}

function mask_payout_key(string $value): string
{
    $length = strlen($value);
    if ($length <= 6) {
        return str_repeat('*', max(1, $length));
    }
    return substr($value, 0, 3) . str_repeat('*', min(12, $length - 6)) . substr($value, -3);
}

function list_user_point_history(int $userId): array
{
    $statement = db()->prepare(
        'SELECT tipo, pontos, descricao, created_at
         FROM lancamentos_pontos
         WHERE usuario_id = :user_id
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function list_user_withdrawals(int $userId): array
{
    $statement = db()->prepare(
        'SELECT uuid, pontos_reservados, valor_ponto_brl, valor_brl, status, chave_pix_mascarada,
                solicitado_em, atualizado_em, pago_em, referencia_pagamento, observacoes
         FROM solicitacoes_saque
         WHERE usuario_id = :user_id
         ORDER BY solicitado_em DESC'
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function request_withdrawal(array $input, array $actor): string
{
    $pointsRaw = trim((string) ($input['points'] ?? ''));
    $pixType = (string) ($input['pix_type'] ?? '');
    $pixKey = trim((string) ($input['pix_key'] ?? ''));
    $token = (string) ($input['request_token'] ?? '');
    if (!ctype_digit($pointsRaw) || (int) $pointsRaw < 1) {
        throw new InvalidArgumentException('Informe uma quantidade válida de pontos.');
    }
    if (!in_array($pixType, ['cpf','cnpj','email','telefone','aleatoria'], true)) {
        throw new InvalidArgumentException('Tipo de chave Pix inválido.');
    }
    if (strlen($pixKey) < 3 || strlen($pixKey) > 190) {
        throw new InvalidArgumentException('Informe uma chave Pix válida.');
    }
    if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
        throw new InvalidArgumentException('Token da solicitação inválido. Atualize a página.');
    }

    $idempotency = 'withdrawal:' . $actor['uuid'] . ':' . $token;
    $connection = db();
    $connection->beginTransaction();
    try {
        $existing = $connection->prepare('SELECT uuid FROM solicitacoes_saque WHERE idempotency_key = :key');
        $existing->execute(['key' => $idempotency]);
        $existingUuid = $existing->fetchColumn();
        if ($existingUuid !== false) {
            $connection->commit();
            return (string) $existingUuid;
        }

        $quote = current_point_quote(true);
        if ($quote === null) {
            throw new InvalidArgumentException('Não há cotação de ponto vigente.');
        }
        $wallet = $connection->prepare(
            'SELECT id, saldo_disponivel_pontos FROM carteiras WHERE usuario_id = :user_id FOR UPDATE'
        );
        $wallet->execute(['user_id' => $actor['id']]);
        $walletRow = $wallet->fetch();
        $points = (int) $pointsRaw;
        if (!is_array($walletRow) || (int) $walletRow['saldo_disponivel_pontos'] < $points) {
            throw new InvalidArgumentException('Saldo de pontos insuficiente.');
        }

        $uuid = uuid_v4();
        $value = round($points * (float) $quote['valor_brl'], 2);
        $insert = $connection->prepare(
            "INSERT INTO solicitacoes_saque
                (uuid, usuario_id, carteira_id, cotacao_ponto_id, pontos_reservados, valor_ponto_brl,
                 valor_brl, status, idempotency_key, chave_pix_mascarada, chave_pix_tipo,
                 chave_pix_criptografada, observacoes)
             VALUES
                (:uuid, :user_id, :wallet_id, :quote_id, :points, :point_value,
                 :value_brl, 'solicitado', :idempotency, :pix_masked, :pix_type,
                 :pix_encrypted, :notes)"
        );
        $insert->execute([
            'uuid' => $uuid,
            'user_id' => $actor['id'],
            'wallet_id' => $walletRow['id'],
            'quote_id' => $quote['id'],
            'points' => $points,
            'point_value' => $quote['valor_brl'],
            'value_brl' => number_format($value, 2, '.', ''),
            'idempotency' => $idempotency,
            'pix_masked' => mask_payout_key($pixKey),
            'pix_type' => $pixType,
            'pix_encrypted' => encrypt_payout_secret($pixKey),
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ]);
        $withdrawalId = (int) $connection->lastInsertId();
        $connection->prepare(
            'UPDATE carteiras
             SET saldo_disponivel_pontos = saldo_disponivel_pontos - :available_points,
                 saldo_reservado_pontos = saldo_reservado_pontos + :reserved_points
             WHERE id = :id'
        )->execute([
            'available_points' => $points,
            'reserved_points' => $points,
            'id' => $walletRow['id'],
        ]);
        $ledger = $connection->prepare(
            "INSERT INTO lancamentos_pontos
                (uuid, carteira_id, usuario_id, tipo, pontos, origem_tipo, origem_uuid, idempotency_key, descricao)
             VALUES
                (:uuid, :wallet_id, :user_id, 'reserva_saque', :points, 'saque', :origin_uuid, :idempotency, 'Reserva para saque')"
        );
        $ledger->execute([
            'uuid' => uuid_v4(),
            'wallet_id' => $walletRow['id'],
            'user_id' => $actor['id'],
            'points' => -$points,
            'origin_uuid' => $uuid,
            'idempotency' => 'ledger:' . $idempotency,
        ]);
        $connection->prepare(
            "INSERT INTO saque_eventos
                (uuid, solicitacao_saque_id, ator_usuario_id, tipo, idempotency_key, dados)
             VALUES
                (:uuid, :withdrawal_id, :actor_id, 'solicitado', :idempotency, :data)"
        )->execute([
            'uuid' => uuid_v4(),
            'withdrawal_id' => $withdrawalId,
            'actor_id' => $actor['id'],
            'idempotency' => 'withdrawal-event:solicitado:' . $uuid,
            'data' => json_encode(['valor_brl' => $value, 'pontos' => $points], JSON_THROW_ON_ERROR),
        ]);
        audit_event($connection, 'saque.solicitado', 'saque', $uuid, $actor['uuid'], [
            'pontos' => $points,
            'valor_ponto_brl' => $quote['valor_brl'],
            'valor_brl' => $value,
            'chave_pix' => mask_payout_key($pixKey),
        ]);
        $connection->commit();
        return $uuid;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function list_admin_withdrawals(): array
{
    $rows = db()->query(
        'SELECT s.*, u.nome usuario_nome, u.email usuario_email
         FROM solicitacoes_saque s
         INNER JOIN usuarios u ON u.id = s.usuario_id
         ORDER BY FIELD(s.status, "solicitado", "em_analise", "aprovado", "pago", "recusado", "cancelado"),
                  s.solicitado_em DESC'
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['chave_pix'] = decrypt_payout_secret((string) ($row['chave_pix_criptografada'] ?? ''));
    }
    unset($row);
    return $rows;
}

function transition_withdrawal(array $input, array $actor): void
{
    $uuid = (string) ($input['withdrawal_uuid'] ?? '');
    $action = (string) ($input['action'] ?? '');
    $notes = trim((string) ($input['notes'] ?? ''));
    $reference = trim((string) ($input['reference'] ?? ''));
    if (!in_array($action, ['approve','reject','paid'], true)) {
        throw new InvalidArgumentException('Ação de saque inválida.');
    }
    if ($action === 'paid' && ($reference === '' || strlen($reference) > 190)) {
        throw new InvalidArgumentException('Informe a referência do pagamento do saque.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $find = $connection->prepare(
            'SELECT * FROM solicitacoes_saque WHERE uuid = :uuid FOR UPDATE'
        );
        $find->execute(['uuid' => $uuid]);
        $withdrawal = $find->fetch();
        if (!is_array($withdrawal)) {
            throw new InvalidArgumentException('Solicitação de saque não encontrada.');
        }
        $wallet = $connection->prepare('SELECT * FROM carteiras WHERE id = :id FOR UPDATE');
        $wallet->execute(['id' => $withdrawal['carteira_id']]);
        $walletRow = $wallet->fetch();
        if (!is_array($walletRow)) {
            throw new RuntimeException('Carteira do saque não encontrada.');
        }

        if ($action === 'approve') {
            if (!in_array($withdrawal['status'], ['solicitado','em_analise'], true)) {
                throw new InvalidArgumentException('Este saque não pode mais ser aprovado.');
            }
            $connection->prepare(
                "UPDATE solicitacoes_saque
                 SET status = 'aprovado', analisado_por_usuario_id = :actor_id,
                     observacoes = CONCAT_WS('\n', observacoes, :notes)
                 WHERE id = :id"
            )->execute(['actor_id' => $actor['id'], 'notes' => $notes ?: null, 'id' => $withdrawal['id']]);
            $eventType = 'aprovado';
        } elseif ($action === 'reject') {
            if (!in_array($withdrawal['status'], ['solicitado','em_analise','aprovado'], true)) {
                throw new InvalidArgumentException('Este saque não pode mais ser recusado.');
            }
            $points = (int) $withdrawal['pontos_reservados'];
            if ((int) $walletRow['saldo_reservado_pontos'] < $points) {
                throw new RuntimeException('Saldo reservado inconsistente.');
            }
            $connection->prepare(
                'UPDATE carteiras
                 SET saldo_disponivel_pontos = saldo_disponivel_pontos + :available_points,
                     saldo_reservado_pontos = saldo_reservado_pontos - :reserved_points
                 WHERE id = :id'
            )->execute([
                'available_points' => $points,
                'reserved_points' => $points,
                'id' => $walletRow['id'],
            ]);
            $connection->prepare(
                "UPDATE solicitacoes_saque
                 SET status = 'recusado', analisado_por_usuario_id = :actor_id,
                     observacoes = CONCAT_WS('\n', observacoes, :notes)
                 WHERE id = :id"
            )->execute(['actor_id' => $actor['id'], 'notes' => $notes ?: 'Recusado pelo administrador', 'id' => $withdrawal['id']]);
            $connection->prepare(
                "INSERT INTO lancamentos_pontos
                    (uuid, carteira_id, usuario_id, tipo, pontos, origem_tipo, origem_uuid, idempotency_key, descricao)
                 VALUES
                    (:uuid, :wallet_id, :user_id, 'liberacao_reserva', :points, 'saque', :origin_uuid, :idempotency, 'Reserva de saque liberada')"
            )->execute([
                'uuid' => uuid_v4(),
                'wallet_id' => $walletRow['id'],
                'user_id' => $withdrawal['usuario_id'],
                'points' => $points,
                'origin_uuid' => $uuid,
                'idempotency' => 'withdrawal-release:' . $uuid,
            ]);
            $eventType = 'recusado';
        } else {
            if ($withdrawal['status'] !== 'aprovado') {
                throw new InvalidArgumentException('Aprove o saque antes de marcá-lo como pago.');
            }
            $points = (int) $withdrawal['pontos_reservados'];
            if ((int) $walletRow['saldo_reservado_pontos'] < $points) {
                throw new RuntimeException('Saldo reservado inconsistente.');
            }
            $connection->prepare(
                'UPDATE carteiras SET saldo_reservado_pontos = saldo_reservado_pontos - :points WHERE id = :id'
            )->execute(['points' => $points, 'id' => $walletRow['id']]);
            $connection->prepare(
                "UPDATE solicitacoes_saque
                 SET status = 'pago', referencia_pagamento = :reference, pago_em = CURRENT_TIMESTAMP(6),
                     analisado_por_usuario_id = :actor_id,
                     observacoes = CONCAT_WS('\n', observacoes, :notes)
                 WHERE id = :id"
            )->execute([
                'reference' => $reference,
                'actor_id' => $actor['id'],
                'notes' => $notes ?: null,
                'id' => $withdrawal['id'],
            ]);
            $eventType = 'pago';
        }

        $event = $connection->prepare(
            'INSERT INTO saque_eventos
                (uuid, solicitacao_saque_id, ator_usuario_id, tipo, idempotency_key, dados)
             VALUES
                (:uuid, :withdrawal_id, :actor_id, :type, :idempotency, :data)'
        );
        $event->execute([
            'uuid' => uuid_v4(),
            'withdrawal_id' => $withdrawal['id'],
            'actor_id' => $actor['id'],
            'type' => $eventType,
            'idempotency' => 'withdrawal-event:' . $eventType . ':' . $uuid,
            'data' => json_encode(['observacoes' => $notes, 'referencia' => $reference], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
        audit_event($connection, 'saque.' . $eventType, 'saque', $uuid, $actor['uuid'], [
            'pontos' => (int) $withdrawal['pontos_reservados'],
            'valor_brl' => $withdrawal['valor_brl'],
            'referencia' => $reference !== '' ? $reference : null,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
