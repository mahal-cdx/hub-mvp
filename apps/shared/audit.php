<?php

declare(strict_types=1);

function audit_event(
    PDO $connection,
    string $action,
    string $entityType,
    string $entityUuid,
    ?string $actorUuid,
    array $metadata = []
): void {
    $statement = $connection->prepare(
        'INSERT INTO eventos_auditoria
            (uuid, ator_origem, ator_usuario_uuid, acao, entidade_tipo, entidade_uuid, request_id, ip_hash, metadata_json)
         VALUES
            (:uuid, :ator_origem, :ator_usuario_uuid, :acao, :entidade_tipo, :entidade_uuid, :request_id, :ip_hash, :metadata_json)'
    );
    $statement->execute([
        'uuid' => uuid_v4(),
        'ator_origem' => $actorUuid === null ? 'sistema' : 'usuario',
        'ator_usuario_uuid' => $actorUuid,
        'acao' => $action,
        'entidade_tipo' => $entityType,
        'entidade_uuid' => $entityUuid,
        'request_id' => request_id(),
        'ip_hash' => keyed_hash('ip', client_ip()),
        'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
    ]);
}
