CREATE TABLE IF NOT EXISTS regras_pontuacao (
    uuid CHAR(36) NOT NULL,
    funcao_uuid CHAR(36) NOT NULL,
    evento VARCHAR(80) NOT NULL,
    pontos INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_regras_pontuacao (funcao_uuid, evento),
    CONSTRAINT fk_regras_pontuacao_funcao
        FOREIGN KEY (funcao_uuid) REFERENCES funcoes (uuid)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS eventos_pontuacao (
    uuid CHAR(36) NOT NULL,
    usuario_uuid CHAR(36) NOT NULL,
    venda_uuid CHAR(36) NULL,
    regra_uuid CHAR(36) NULL,
    evento VARCHAR(80) NOT NULL,
    pontos INT NOT NULL,
    valor_ponto_brl DECIMAL(10,4) NOT NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_evento_pontuacao_usuario_venda_regra (usuario_uuid, venda_uuid, regra_uuid),
    KEY idx_eventos_pontuacao_usuario (usuario_uuid),
    CONSTRAINT fk_eventos_pontuacao_usuario
        FOREIGN KEY (usuario_uuid) REFERENCES usuarios (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_eventos_pontuacao_venda
        FOREIGN KEY (venda_uuid) REFERENCES vendas (uuid)
        ON DELETE SET NULL,
    CONSTRAINT fk_eventos_pontuacao_regra
        FOREIGN KEY (regra_uuid) REFERENCES regras_pontuacao (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS carteiras (
    uuid CHAR(36) NOT NULL,
    usuario_uuid CHAR(36) NOT NULL,
    saldo_brl DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_carteiras_usuario (usuario_uuid),
    CONSTRAINT fk_carteiras_usuario
        FOREIGN KEY (usuario_uuid) REFERENCES usuarios (uuid)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS lancamentos_carteira (
    uuid CHAR(36) NOT NULL,
    carteira_uuid CHAR(36) NOT NULL,
    usuario_uuid CHAR(36) NOT NULL,
    tipo ENUM('credito','debito','estorno') NOT NULL,
    origem VARCHAR(80) NOT NULL,
    origem_uuid CHAR(36) NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    descricao VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    KEY idx_lancamentos_carteira (carteira_uuid),
    KEY idx_lancamentos_usuario (usuario_uuid),
    CONSTRAINT fk_lancamentos_carteira
        FOREIGN KEY (carteira_uuid) REFERENCES carteiras (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_lancamentos_usuario
        FOREIGN KEY (usuario_uuid) REFERENCES usuarios (uuid)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS solicitacoes_saque (
    uuid CHAR(36) NOT NULL,
    usuario_uuid CHAR(36) NOT NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    status ENUM('solicitado','em_analise','aprovado','pago','cancelado') NOT NULL DEFAULT 'solicitado',
    observacoes TEXT NULL,
    analisado_por_uuid CHAR(36) NULL,
    solicitado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    atualizado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    pago_em DATETIME(6) NULL,
    PRIMARY KEY (uuid),
    KEY idx_saques_usuario (usuario_uuid),
    KEY idx_saques_status (status),
    CONSTRAINT fk_saques_usuario
        FOREIGN KEY (usuario_uuid) REFERENCES usuarios (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_saques_analisado_por
        FOREIGN KEY (analisado_por_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
