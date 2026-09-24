CREATE TABLE IF NOT EXISTS eventos_auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ocorrido_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    ator_origem VARCHAR(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ator_usuario_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    acao VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    entidade_tipo VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    entidade_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    request_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NULL,
    idempotency_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    antes_json JSON NULL,
    depois_json JSON NULL,
    metadata_json JSON NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eventos_auditoria_uuid (uuid),
    UNIQUE KEY uq_eventos_auditoria_idempotency (idempotency_key),
    KEY idx_eventos_auditoria_entidade_data (entidade_tipo, entidade_uuid, ocorrido_em),
    KEY idx_eventos_auditoria_ator_data (ator_usuario_uuid, ocorrido_em),
    KEY idx_eventos_auditoria_acao_data (acao, ocorrido_em),
    KEY idx_eventos_auditoria_request (request_id),
    CONSTRAINT chk_eventos_auditoria_origem CHECK (ator_origem IN ('usuario','sistema','webhook','integracao'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('004_create_audit.sql', SHA2('004_create_audit.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
