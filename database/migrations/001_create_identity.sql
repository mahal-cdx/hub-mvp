CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NULL,
    identidade_origem VARCHAR(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'local',
    edge_usuario_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    status VARCHAR(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'ativo',
    email_verificado_em DATETIME(6) NULL,
    ultimo_login_em DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_uuid (uuid),
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_edge_uuid (edge_usuario_uuid),
    KEY idx_usuarios_status (status),
    CONSTRAINT chk_usuarios_origem CHECK (identidade_origem IN ('local','threeebs_edge')),
    CONSTRAINT chk_usuarios_status CHECK (status IN ('ativo','bloqueado','inativo')),
    CONSTRAINT chk_usuarios_identidade CHECK (
        (identidade_origem = 'local' AND senha_hash IS NOT NULL)
        OR (identidade_origem = 'threeebs_edge' AND edge_usuario_uuid IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS funcoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    chave VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    nome VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_funcoes_uuid (uuid),
    UNIQUE KEY uq_funcoes_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS usuario_funcoes (
    usuario_id BIGINT UNSIGNED NOT NULL,
    funcao_id BIGINT UNSIGNED NOT NULL,
    atribuido_por_usuario_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (usuario_id, funcao_id),
    KEY idx_usuario_funcoes_funcao (funcao_id),
    KEY idx_usuario_funcoes_atribuido_por (atribuido_por_usuario_id),
    CONSTRAINT fk_usuario_funcoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_funcoes_funcao FOREIGN KEY (funcao_id) REFERENCES funcoes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_funcoes_atribuido_por FOREIGN KEY (atribuido_por_usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('001_create_identity.sql', SHA2('001_create_identity.sql:v2', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
