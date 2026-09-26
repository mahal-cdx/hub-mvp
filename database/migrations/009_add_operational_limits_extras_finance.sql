CREATE TABLE IF NOT EXISTS configuracoes_operacionais (
    chave VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    valor_inteiro INT UNSIGNED NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    atualizado_por_usuario_id BIGINT UNSIGNED NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (chave),
    CONSTRAINT fk_config_operacionais_usuario
        FOREIGN KEY (atualizado_por_usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO configuracoes_operacionais (chave, valor_inteiro, descricao) VALUES
('captador_na_fila', 5, 'Máximo de leads do captador na fila'),
('captador_em_desenvolvimento', 5, 'Máximo de leads do captador em desenvolvimento'),
('captador_aberto_comercial', 10, 'Máximo de leads do captador abertos no comercial'),
('dev_assumida', 1, 'Máximo de projetos ativos por desenvolvedor'),
('dev_em_revisao', 5, 'Máximo de projetos em revisão por desenvolvedor'),
('dev_prazo_horas', 12, 'Prazo em horas para a primeira entrega'),
('comercial_em_atendimento', 5, 'Máximo de vendas em atendimento por comercial'),
('comercial_retorno_agendado', 5, 'Máximo de retornos agendados por comercial'),
('comercial_aguardando_pagamento', 5, 'Máximo de vendas aguardando pagamento por comercial')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

ALTER TABLE oportunidades
    ADD COLUMN prazo_desenvolvimento_em DATETIME(6) NULL AFTER assumida_em,
    ADD KEY idx_oportunidades_prazo (status, prazo_desenvolvimento_em);

ALTER TABLE vendas
    DROP CHECK chk_vendas_status,
    ADD COLUMN proximo_contato_em DATETIME(6) NULL AFTER assumida_em,
    ADD COLUMN ultimo_resultado VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER proximo_contato_em,
    ADD KEY idx_vendas_comercial_proximo (comercial_usuario_id, status, proximo_contato_em),
    ADD CONSTRAINT chk_vendas_status
        CHECK (status IN ('disponivel','em_atendimento','retorno_agendado','aguardando_pagamento','fechada','perdida','cancelada'));

ALTER TABLE projetos
    ADD COLUMN custo_hospedagem_brl DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER aprovado_em,
    ADD CONSTRAINT chk_projetos_custo_hospedagem CHECK (custo_hospedagem_brl >= 0);

CREATE TABLE IF NOT EXISTS produtos_extras (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    codigo VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    nome VARCHAR(160) NOT NULL,
    descricao VARCHAR(500) NULL,
    valor_sugerido_brl DECIMAL(12,2) NULL,
    status VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'ativo',
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_produtos_extras_uuid (uuid),
    UNIQUE KEY uq_produtos_extras_codigo (codigo),
    CONSTRAINT chk_produtos_extras_status CHECK (status IN ('ativo','inativo')),
    CONSTRAINT chk_produtos_extras_valor CHECK (valor_sugerido_brl IS NULL OR valor_sugerido_brl >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO produtos_extras (uuid, codigo, nome, descricao, valor_sugerido_brl, ordem) VALUES
(UUID(), 'dominio_personalizado', 'Domínio personalizado', 'Configuração e publicação em domínio próprio do cliente.', NULL, 10),
(UUID(), 'banco_dados', 'Banco de dados', 'Estrutura de dados e recursos dinâmicos para o projeto.', NULL, 20),
(UUID(), 'email_profissional', 'E-mail profissional', 'Contas de e-mail vinculadas ao domínio do cliente.', NULL, 30),
(UUID(), 'integracao', 'Integração adicional', 'Integração com serviço ou ferramenta externa.', NULL, 40),
(UUID(), 'manutencao', 'Plano de manutenção', 'Acompanhamento e melhorias após a entrega.', NULL, 50)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), descricao = VALUES(descricao), ordem = VALUES(ordem);

CREATE TABLE IF NOT EXISTS venda_produtos_extras (
    venda_id BIGINT UNSIGNED NOT NULL,
    produto_extra_id BIGINT UNSIGNED NOT NULL,
    marcado_por_usuario_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'interesse',
    valor_negociado_brl DECIMAL(12,2) NULL,
    marcado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (venda_id, produto_extra_id),
    KEY idx_venda_produtos_status (status, updated_at),
    CONSTRAINT fk_venda_produtos_venda FOREIGN KEY (venda_id) REFERENCES vendas (id) ON DELETE CASCADE,
    CONSTRAINT fk_venda_produtos_produto FOREIGN KEY (produto_extra_id) REFERENCES produtos_extras (id) ON DELETE RESTRICT,
    CONSTRAINT fk_venda_produtos_usuario FOREIGN KEY (marcado_por_usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_venda_produtos_status CHECK (status IN ('interesse','confirmado','cancelado')),
    CONSTRAINT chk_venda_produtos_valor CHECK (valor_negociado_brl IS NULL OR valor_negociado_brl >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS movimentacoes_financeiras (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tipo VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    projeto_id BIGINT UNSIGNED NULL,
    venda_id BIGINT UNSIGNED NULL,
    origem_tipo VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NULL,
    origem_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    competencia DATE NOT NULL,
    criado_por_usuario_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_movimentacoes_uuid (uuid),
    UNIQUE KEY uq_movimentacoes_origem (origem_tipo, origem_uuid),
    KEY idx_movimentacoes_tipo_competencia (tipo, competencia),
    KEY idx_movimentacoes_projeto (projeto_id, competencia),
    CONSTRAINT fk_movimentacoes_projeto FOREIGN KEY (projeto_id) REFERENCES projetos (id) ON DELETE SET NULL,
    CONSTRAINT fk_movimentacoes_venda FOREIGN KEY (venda_id) REFERENCES vendas (id) ON DELETE SET NULL,
    CONSTRAINT fk_movimentacoes_usuario FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_movimentacoes_tipo CHECK (tipo IN ('entrada','saida','despesa')),
    CONSTRAINT chk_movimentacoes_valor CHECK (valor_brl > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('009_add_operational_limits_extras_finance.sql', SHA2('009_add_operational_limits_extras_finance.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
