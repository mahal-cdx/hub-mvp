<?php

declare(strict_types=1);

const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_IDENTIFIER_LIMIT = 5;
const LOGIN_IP_LIMIT = 20;
const DUMMY_PASSWORD_HASH = '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO';

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function require_admin(): array
{
    $user = auth_user();
    if ($user === null || !in_array('administrador', $user['roles'] ?? [], true)) {
        redirect('/login');
    }

    return $user;
}

function login_is_blocked(PDO $connection, string $identifierHash, string $ipHash): bool
{
    $sql = "SELECT
        SUM(CASE WHEN identificador_hash = :identifier_hash AND resultado <> 'sucesso' THEN 1 ELSE 0 END) identifier_failures,
        SUM(CASE WHEN ip_hash = :ip_hash AND resultado <> 'sucesso' THEN 1 ELSE 0 END) ip_failures
      FROM auth_tentativas
      WHERE ocorrido_em >= (CURRENT_TIMESTAMP(6) - INTERVAL " . LOGIN_WINDOW_MINUTES . " MINUTE)";

    $statement = $connection->prepare($sql);
    $statement->execute([
        'identifier_hash' => $identifierHash,
        'ip_hash' => $ipHash,
    ]);
    $counts = $statement->fetch() ?: [];

    return (int) ($counts['identifier_failures'] ?? 0) >= LOGIN_IDENTIFIER_LIMIT
        || (int) ($counts['ip_failures'] ?? 0) >= LOGIN_IP_LIMIT;
}

function record_login_attempt(
    PDO $connection,
    string $identifierHash,
    string $ipHash,
    string $result,
    ?int $userId = null
): void {
    $statement = $connection->prepare(
        'INSERT INTO auth_tentativas
            (uuid, usuario_id, identificador_hash, ip_hash, resultado, request_id)
         VALUES
            (:uuid, :usuario_id, :identificador_hash, :ip_hash, :resultado, :request_id)'
    );
    $statement->execute([
        'uuid' => uuid_v4(),
        'usuario_id' => $userId,
        'identificador_hash' => $identifierHash,
        'ip_hash' => $ipHash,
        'resultado' => $result,
        'request_id' => request_id(),
    ]);
}

function record_auth_audit(string $action, ?string $userUuid, array $metadata = []): void
{
    try {
        $statement = db()->prepare(
            'INSERT INTO eventos_auditoria
                (uuid, ator_origem, ator_usuario_uuid, acao, entidade_tipo, entidade_uuid, request_id, ip_hash, metadata_json)
             VALUES
                (:uuid, :ator_origem, :ator_usuario_uuid, :acao, :entidade_tipo, :entidade_uuid, :request_id, :ip_hash, :metadata_json)'
        );
        $statement->execute([
            'uuid' => uuid_v4(),
            'ator_origem' => $userUuid === null ? 'sistema' : 'usuario',
            'ator_usuario_uuid' => $userUuid,
            'acao' => $action,
            'entidade_tipo' => 'sessao_admin',
            'entidade_uuid' => $userUuid ?? '00000000-0000-0000-0000-000000000000',
            'request_id' => request_id(),
            'ip_hash' => keyed_hash('ip', client_ip()),
            'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $error) {
        error_log('Falha ao registrar auditoria de autenticação: ' . $error->getMessage());
    }
}

function attempt_admin_login(string $email, string $password): array
{
    $normalizedEmail = normalize_email($email);
    $identifierHash = keyed_hash('login_identifier', $normalizedEmail);
    $ipHash = keyed_hash('ip', client_ip());
    $connection = db();

    if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)
        || strlen($normalizedEmail) > 190
        || $password === ''
        || strlen($password) > 4096) {
        password_verify($password, DUMMY_PASSWORD_HASH);
        record_login_attempt($connection, $identifierHash, $ipHash, 'credencial_invalida');
        return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    if (login_is_blocked($connection, $identifierHash, $ipHash)) {
        password_verify($password, DUMMY_PASSWORD_HASH);
        record_login_attempt($connection, $identifierHash, $ipHash, 'bloqueado');
        return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    $statement = $connection->prepare(
        "SELECT
            u.id, u.uuid, u.nome, u.email, u.senha_hash, u.status,
            EXISTS(
                SELECT 1
                FROM usuario_funcoes uf
                INNER JOIN funcoes f ON f.id = uf.funcao_id
                WHERE uf.usuario_id = u.id AND f.chave = 'administrador'
            ) AS is_admin
         FROM usuarios u
         WHERE u.email = :email
         LIMIT 1"
    );
    $statement->execute(['email' => $normalizedEmail]);
    $user = $statement->fetch();
    $hash = is_array($user) && is_string($user['senha_hash'] ?? null)
        ? $user['senha_hash']
        : DUMMY_PASSWORD_HASH;
    $passwordValid = password_verify($password, $hash);

    if (!$passwordValid || !is_array($user) || $user['status'] !== 'ativo') {
        record_login_attempt(
            $connection,
            $identifierHash,
            $ipHash,
            'credencial_invalida',
            is_array($user) ? (int) $user['id'] : null
        );
        record_auth_audit('auth.login_falhou', is_array($user) ? $user['uuid'] : null, ['motivo' => 'credencial_invalida']);
        return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    if ((int) $user['is_admin'] !== 1) {
        record_login_attempt($connection, $identifierHash, $ipHash, 'sem_permissao', (int) $user['id']);
        record_auth_audit('auth.login_falhou', $user['uuid'], ['motivo' => 'sem_permissao']);
        return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    $connection->beginTransaction();
    try {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $rehash = $connection->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
            $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
        }

        $update = $connection->prepare('UPDATE usuarios SET ultimo_login_em = CURRENT_TIMESTAMP(6) WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        record_login_attempt($connection, $identifierHash, $ipHash, 'sucesso', (int) $user['id']);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'uuid' => $user['uuid'],
        'name' => $user['nome'],
        'email' => $user['email'],
        'roles' => ['administrador'],
    ];
    $_SESSION['_created_at'] = time();
    $_SESSION['_last_activity'] = time();
    unset($_SESSION['_csrf']);
    record_auth_audit('auth.login_sucesso', $user['uuid']);

    return ['ok' => true, 'message' => null];
}

function logout_admin(): void
{
    $user = auth_user();
    if ($user !== null) {
        record_auth_audit('auth.logout', $user['uuid']);
    }

    clear_session();
}
