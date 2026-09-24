CREATE TABLE IF NOT EXISTS regras_pontuacao (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    funcao_id BIGINT UNSIGNED NOT NULL,
    evento_chave VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    versao SMALLINT UNSIGNED NOT NULL,
    pontos INT UNSIGNED NOT NULL,
    vigente_desde DATETIME(6) NOT NULL,
    vigente_ate DATETIME(6) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por_usuario_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_regras_pontuacao_uuid (uuid),
    UNIQUE KEY uq_regras_pontuacao_versao (funcao_id, evento_chave, versao),
    KEY idx_regras_pontuacao_vigencia (evento_chave, ativo, vigente_desde, vigente_ate),
    CONSTRAINT fk_regras_pontuacao_funcao FOREIGN KEY (funcao_id) REFERENCES funcoes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_regras_pontuacao_criador FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT chk_regras_pontuacao_pontos CHECK (pontos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cotacoes_ponto (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    valor_brl DECIMAL(12,6) NOT NULL,
    vigente_desde DATETIME(6) NOT NULL,
    vigente_ate DATETIME(6) NULL,
    definido_por_usuario_id BIGINT UNSIGNED NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_cotacoes_ponto_uuid (uuid),
    KEY idx_cotacoes_ponto_vigencia (vigente_desde, vigente_ate),
    CONSTRAINT fk_cotacoes_ponto_definidor FOREIGN KEY (definido_por_usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_cotacoes_ponto_valor CHECK (valor_brl > 0),
    CONSTRAINT chk_cotacoes_ponto_periodo CHECK (vigente_ate IS NULL OR vigente_ate > vigente_desde)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS eventos_pontuacao (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    regra_pontuacao_id BIGINT UNSIGNED NOT NULL,
    evento_chave VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    origem_tipo VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    origem_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pontos INT UNSIGNED NOT NULL,
    idempotency_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    detalhes JSON NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_eventos_pontuacao_uuid (uuid),
    UNIQUE KEY uq_eventos_pontuacao_idempotency (idempotency_key),
    KEY idx_eventos_pontuacao_usuario_data (usuario_id, created_at),
    KEY idx_eventos_pontuacao_origem (origem_tipo, origem_uuid),
    CONSTRAINT fk_eventos_pontuacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT fk_eventos_pontuacao_regra FOREIGN KEY (regra_pontuacao_id) REFERENCES regras_pontuacao (id) ON DELETE RESTRICT,
    CONSTRAINT chk_eventos_pontuacao_pontos CHECK (pontos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS carteiras (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    saldo_disponivel_pontos BIGINT NOT NULL DEFAULT 0,
    saldo_reservado_pontos BIGINT NOT NULL DEFAULT 0,
    reconciliado_em DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_carteiras_uuid (uuid),
    UNIQUE KEY uq_carteiras_usuario (usuario_id),
    CONSTRAINT fk_carteiras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_carteiras_saldos CHECK (saldo_disponivel_pontos >= 0 AND saldo_reservado_pontos >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS lancamentos_pontos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    carteira_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pontos BIGINT NOT NULL,
    origem_tipo VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    origem_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    idempotency_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    descricao VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_lancamentos_pontos_uuid (uuid),
    UNIQUE KEY uq_lancamentos_pontos_idempotency (idempotency_key),
    KEY idx_lancamentos_pontos_carteira_data (carteira_id, created_at),
    KEY idx_lancamentos_pontos_usuario_data (usuario_id, created_at),
    KEY idx_lancamentos_pontos_origem (origem_tipo, origem_uuid),
    CONSTRAINT fk_lancamentos_pontos_carteira FOREIGN KEY (carteira_id) REFERENCES carteiras (id) ON DELETE RESTRICT,
    CONSTRAINT fk_lancamentos_pontos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_lancamentos_pontos_tipo CHECK (tipo IN ('credito','estorno','reserva_saque','liberacao_reserva','saque_pago','ajuste')),
    CONSTRAINT chk_lancamentos_pontos_quantidade CHECK (pontos <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS solicitacoes_saque (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    carteira_id BIGINT UNSIGNED NOT NULL,
    cotacao_ponto_id BIGINT UNSIGNED NOT NULL,
    pontos_reservados BIGINT UNSIGNED NOT NULL,
    valor_ponto_brl DECIMAL(12,6) NOT NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    status VARCHAR(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'solicitado',
    idempotency_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    chave_pix_mascarada VARCHAR(255) NULL,
    observacoes TEXT NULL,
    analisado_por_usuario_id BIGINT UNSIGNED NULL,
    referencia_pagamento VARCHAR(190) NULL,
    solicitado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    atualizado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    pago_em DATETIME(6) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_solicitacoes_saque_uuid (uuid),
    UNIQUE KEY uq_solicitacoes_saque_idempotency (idempotency_key),
    UNIQUE KEY uq_solicitacoes_saque_referencia (referencia_pagamento),
    KEY idx_solicitacoes_saque_usuario_status (usuario_id, status),
    KEY idx_solicitacoes_saque_status_data (status, solicitado_em),
    CONSTRAINT fk_solicitacoes_saque_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitacoes_saque_carteira FOREIGN KEY (carteira_id) REFERENCES carteiras (id) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitacoes_saque_cotacao FOREIGN KEY (cotacao_ponto_id) REFERENCES cotacoes_ponto (id) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitacoes_saque_analisador FOREIGN KEY (analisado_por_usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT chk_solicitacoes_saque_pontos CHECK (pontos_reservados > 0),
    CONSTRAINT chk_solicitacoes_saque_valores CHECK (valor_ponto_brl > 0 AND valor_brl > 0),
    CONSTRAINT chk_solicitacoes_saque_status CHECK (status IN ('solicitado','em_analise','aprovado','pago','cancelado','recusado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS saque_eventos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    solicitacao_saque_id BIGINT UNSIGNED NOT NULL,
    ator_usuario_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    idempotency_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    dados JSON NULL,
    ocorrido_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_saque_eventos_uuid (uuid),
    UNIQUE KEY uq_saque_eventos_idempotency (idempotency_key),
    KEY idx_saque_eventos_solicitacao_data (solicitacao_saque_id, ocorrido_em),
    CONSTRAINT fk_saque_eventos_solicitacao FOREIGN KEY (solicitacao_saque_id) REFERENCES solicitacoes_saque (id) ON DELETE RESTRICT,
    CONSTRAINT fk_saque_eventos_ator FOREIGN KEY (ator_usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('003_create_scoring_wallet.sql', SHA2('003_create_scoring_wallet.sql:v2', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
