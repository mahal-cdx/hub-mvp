CREATE TABLE IF NOT EXISTS auth_tentativas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    identificador_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    resultado VARCHAR(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    request_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ocorrido_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_tentativas_uuid (uuid),
    KEY idx_auth_tentativas_identificador_data (identificador_hash, ocorrido_em),
    KEY idx_auth_tentativas_ip_data (ip_hash, ocorrido_em),
    KEY idx_auth_tentativas_usuario_data (usuario_id, ocorrido_em),
    CONSTRAINT fk_auth_tentativas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT chk_auth_tentativas_resultado CHECK (resultado IN ('sucesso','credencial_invalida','sem_permissao','bloqueado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('005_create_auth_security.sql', SHA2('005_create_auth_security.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
