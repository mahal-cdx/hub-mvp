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

function load_active_user(PDO $connection, int $id, string $uuid): ?array
{
    $statement = $connection->prepare(
        "SELECT u.id, u.uuid, u.nome, u.email, GROUP_CONCAT(f.chave ORDER BY f.chave SEPARATOR ',') roles
         FROM usuarios u
         INNER JOIN usuario_funcoes uf ON uf.usuario_id = u.id
         INNER JOIN funcoes f ON f.id = uf.funcao_id
         WHERE u.id = :id AND u.uuid = :uuid AND u.status = 'ativo'
         GROUP BY u.id, u.uuid, u.nome, u.email
         LIMIT 1"
    );
    $statement->execute(['id' => $id, 'uuid' => $uuid]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'uuid' => $row['uuid'],
        'name' => $row['nome'],
        'email' => $row['email'],
        'roles' => array_values(array_filter(explode(',', (string) $row['roles']))),
    ];
}

function require_roles(array $requiredRoles): array
{
    $sessionUser = auth_user();
    if ($sessionUser === null) {
        redirect('/login');
    }

    $user = load_active_user(db(), (int) $sessionUser['id'], (string) $sessionUser['uuid']);
    if ($user === null || array_intersect($requiredRoles, $user['roles']) === []) {
        record_auth_audit('auth.sessao_revogada', $sessionUser['uuid'] ?? null);
        clear_session();
        redirect('/login');
    }

    $_SESSION['auth_user'] = $user;
    return $user;
}

function require_admin(): array
{
    return require_roles(['administrador']);
}

function require_operational_user(): array
{
    return require_roles(['captador', 'desenvolvedor', 'comercial']);
}

function user_has_role(array $user, string $role): bool
{
    return in_array($role, $user['roles'] ?? [], true);
}

function login_is_blocked(PDO $connection, string $identifierHash, string $ipHash): bool
{
    $sql = "SELECT
        SUM(CASE WHEN identificador_hash = :identifier_hash AND resultado <> 'sucesso' THEN 1 ELSE 0 END) identifier_failures,
        SUM(CASE WHEN ip_hash = :ip_hash AND resultado <> 'sucesso' THEN 1 ELSE 0 END) ip_failures
      FROM auth_tentativas
      WHERE ocorrido_em >= (CURRENT_TIMESTAMP(6) - INTERVAL " . LOGIN_WINDOW_MINUTES . " MINUTE)";

    $statement = $connection->prepare($sql);
    $statement->execute(['identifier_hash' => $identifierHash, 'ip_hash' => $ipHash]);
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
            (:uuid, :usuario_id, :identifier_hash, :ip_hash, :result, :request_id)'
    );
    $statement->execute([
        'uuid' => uuid_v4(),
        'usuario_id' => $userId,
        'identifier_hash' => $identifierHash,
        'ip_hash' => $ipHash,
        'result' => $result,
        'request_id' => request_id(),
    ]);
}

function record_auth_audit(string $action, ?string $userUuid, array $metadata = []): void
{
    try {
        audit_event(
            db(),
            $action,
            'sessao_' . config('context'),
            $userUuid ?? '00000000-0000-0000-0000-000000000000',
            $userUuid,
            $metadata + ['contexto' => config('context')]
        );
    } catch (Throwable $error) {
        error_log('Falha ao registrar auditoria de autenticação: ' . $error->getMessage());
    }
}

function attempt_login(string $email, string $password, array $allowedRoles): array
{
    $normalizedEmail = normalize_email($email);
    $identifierHash = keyed_hash('login_identifier:' . config('context'), $normalizedEmail);
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
        "SELECT u.id, u.uuid, u.nome, u.email, u.senha_hash, u.status,
                GROUP_CONCAT(f.chave ORDER BY f.chave SEPARATOR ',') roles
         FROM usuarios u
         LEFT JOIN usuario_funcoes uf ON uf.usuario_id = u.id
         LEFT JOIN funcoes f ON f.id = uf.funcao_id
         WHERE u.email = :email
         GROUP BY u.id, u.uuid, u.nome, u.email, u.senha_hash, u.status
         LIMIT 1"
    );
    $statement->execute(['email' => $normalizedEmail]);
    $row = $statement->fetch();
    $hash = is_array($row) && is_string($row['senha_hash'] ?? null)
        ? $row['senha_hash']
        : DUMMY_PASSWORD_HASH;
    $validPassword = password_verify($password, $hash);
    $roles = is_array($row) ? array_values(array_filter(explode(',', (string) $row['roles']))) : [];
    $authorized = array_intersect($allowedRoles, $roles) !== [];

    if (!$validPassword || !is_array($row) || $row['status'] !== 'ativo' || !$authorized) {
        $result = $validPassword && is_array($row) && !$authorized ? 'sem_permissao' : 'credencial_invalida';
        record_login_attempt($connection, $identifierHash, $ipHash, $result, is_array($row) ? (int) $row['id'] : null);
        record_auth_audit('auth.login_falhou', is_array($row) ? $row['uuid'] : null, ['motivo' => $result]);
        return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    $connection->beginTransaction();
    try {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $rehash = $connection->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
            $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $row['id']]);
        }

        $update = $connection->prepare('UPDATE usuarios SET ultimo_login_em = CURRENT_TIMESTAMP(6) WHERE id = :id');
        $update->execute(['id' => $row['id']]);
        record_login_attempt($connection, $identifierHash, $ipHash, 'sucesso', (int) $row['id']);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $row['id'],
        'uuid' => $row['uuid'],
        'name' => $row['nome'],
        'email' => $row['email'],
        'roles' => $roles,
    ];
    $_SESSION['_created_at'] = time();
    $_SESSION['_last_activity'] = time();
    $_SESSION['_rotated_at'] = time();
    unset($_SESSION['_csrf']);
    record_auth_audit('auth.login_sucesso', $row['uuid']);

    return ['ok' => true, 'message' => null];
}

function attempt_admin_login(string $email, string $password): array
{
    return attempt_login($email, $password, ['administrador']);
}

function attempt_user_login(string $email, string $password): array
{
    return attempt_login($email, $password, ['captador', 'desenvolvedor', 'comercial']);
}

function logout_user(): void
{
    $user = auth_user();
    if ($user !== null) {
        record_auth_audit('auth.logout', $user['uuid']);
    }

    clear_session();
}
