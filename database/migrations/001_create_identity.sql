CREATE TABLE IF NOT EXISTS usuarios (
    uuid CHAR(36) NOT NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS funcoes (
    uuid CHAR(36) NOT NULL,
    chave VARCHAR(50) NOT NULL,
    nome VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_funcoes_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS usuario_funcoes (
    usuario_uuid CHAR(36) NOT NULL,
    funcao_uuid CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (usuario_uuid, funcao_uuid),
    CONSTRAINT fk_usuario_funcoes_usuario
        FOREIGN KEY (usuario_uuid) REFERENCES usuarios (uuid)
        ON DELETE CASCADE,
    CONSTRAINT fk_usuario_funcoes_funcao
        FOREIGN KEY (funcao_uuid) REFERENCES funcoes (uuid)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE INDEX idx_usuario_funcoes_funcao ON usuario_funcoes (funcao_uuid);
