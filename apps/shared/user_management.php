<?php

declare(strict_types=1);

function assignable_roles(): array
{
    return [
        'administrador' => 'Administrador',
        'captador' => 'Cadastro de leads',
        'desenvolvedor' => 'Desenvolvimento',
        'comercial' => 'Comercial e vendas',
    ];
}

function normalize_role_selection(mixed $roles): array
{
    $selected = is_array($roles) ? array_values(array_unique(array_map('strval', $roles))) : [];
    return array_values(array_intersect(array_keys(assignable_roles()), $selected));
}

function list_managed_users(): array
{
    return db()->query(
        "SELECT u.uuid, u.nome, u.email, u.status, u.ultimo_login_em,
                GROUP_CONCAT(f.chave ORDER BY f.chave SEPARATOR ',') roles
         FROM usuarios u
         LEFT JOIN usuario_funcoes uf ON uf.usuario_id = u.id
         LEFT JOIN funcoes f ON f.id = uf.funcao_id
         GROUP BY u.id, u.uuid, u.nome, u.email, u.status, u.ultimo_login_em
         ORDER BY u.created_at DESC"
    )->fetchAll();
}

function create_managed_user(array $input, array $actor): string
{
    $name = trim((string) ($input['name'] ?? ''));
    $email = normalize_email((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $confirmation = (string) ($input['password_confirmation'] ?? '');
    $roles = normalize_role_selection($input['roles'] ?? []);

    if (strlen($name) < 2 || strlen($name) > 150) {
        throw new InvalidArgumentException('Informe um nome entre 2 e 150 caracteres.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        throw new InvalidArgumentException('Informe um e-mail válido.');
    }
    if (strlen($password) < 12 || strlen($password) > 4096) {
        throw new InvalidArgumentException('A senha deve possuir pelo menos 12 caracteres.');
    }
    if (!hash_equals($password, $confirmation)) {
        throw new InvalidArgumentException('A confirmação da senha não corresponde.');
    }
    if ($roles === []) {
        throw new InvalidArgumentException('Selecione pelo menos uma função.');
    }

    $connection = db();
    $uuid = uuid_v4();
    $connection->beginTransaction();

    try {
        $statement = $connection->prepare(
            "INSERT INTO usuarios
                (uuid, nome, email, senha_hash, identidade_origem, status, email_verificado_em)
             VALUES
                (:uuid, :name, :email, :password_hash, 'local', 'ativo', CURRENT_TIMESTAMP(6))"
        );
        $statement->execute([
            'uuid' => $uuid,
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $userId = (int) $connection->lastInsertId();

        $roleStatement = $connection->prepare(
            'INSERT INTO usuario_funcoes (usuario_id, funcao_id, atribuido_por_usuario_id)
             SELECT :user_id, id, :actor_id FROM funcoes WHERE chave = :role'
        );
        foreach ($roles as $role) {
            $roleStatement->execute(['user_id' => $userId, 'actor_id' => $actor['id'], 'role' => $role]);
        }

        if (array_intersect($roles, ['captador', 'desenvolvedor', 'comercial']) !== []) {
            $wallet = $connection->prepare('INSERT INTO carteiras (uuid, usuario_id) VALUES (:uuid, :user_id)');
            $wallet->execute(['uuid' => uuid_v4(), 'user_id' => $userId]);
        }

        audit_event($connection, 'usuario.criado', 'usuario', $uuid, $actor['uuid'], ['funcoes' => $roles]);
        $connection->commit();
        return $uuid;
    } catch (PDOException $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        if ($error->getCode() === '23000') {
            throw new InvalidArgumentException('Já existe um usuário com esse e-mail.');
        }
        throw $error;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function update_managed_user(string $uuid, array $input, array $actor): void
{
    $roles = normalize_role_selection($input['roles'] ?? []);
    $status = (string) ($input['status'] ?? 'ativo');

    if ($roles === []) {
        throw new InvalidArgumentException('Selecione pelo menos uma função.');
    }
    if (!in_array($status, ['ativo', 'bloqueado', 'inativo'], true)) {
        throw new InvalidArgumentException('Status inválido.');
    }
    if ($uuid === $actor['uuid'] && (!in_array('administrador', $roles, true) || $status !== 'ativo')) {
        throw new InvalidArgumentException('Você não pode remover ou bloquear o próprio acesso administrativo.');
    }

    $connection = db();
    $connection->beginTransaction();

    try {
        $find = $connection->prepare('SELECT id FROM usuarios WHERE uuid = :uuid FOR UPDATE');
        $find->execute(['uuid' => $uuid]);
        $userId = $find->fetchColumn();
        if ($userId === false) {
            throw new InvalidArgumentException('Usuário não encontrado.');
        }

        $update = $connection->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
        $update->execute(['status' => $status, 'id' => $userId]);

        $delete = $connection->prepare('DELETE FROM usuario_funcoes WHERE usuario_id = :id');
        $delete->execute(['id' => $userId]);

        $insert = $connection->prepare(
            'INSERT INTO usuario_funcoes (usuario_id, funcao_id, atribuido_por_usuario_id)
             SELECT :user_id, id, :actor_id FROM funcoes WHERE chave = :role'
        );
        foreach ($roles as $role) {
            $insert->execute(['user_id' => $userId, 'actor_id' => $actor['id'], 'role' => $role]);
        }

        if (array_intersect($roles, ['captador', 'desenvolvedor', 'comercial']) !== []) {
            $wallet = $connection->prepare(
                'INSERT IGNORE INTO carteiras (uuid, usuario_id) VALUES (:uuid, :user_id)'
            );
            $wallet->execute(['uuid' => uuid_v4(), 'user_id' => $userId]);
        }

        audit_event($connection, 'usuario.funcoes_atualizadas', 'usuario', $uuid, $actor['uuid'], [
            'funcoes' => $roles,
            'status' => $status,
        ]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
