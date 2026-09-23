CREATE TABLE IF NOT EXISTS leads (
    uuid CHAR(36) NOT NULL,
    nome VARCHAR(160) NOT NULL,
    email VARCHAR(190) NULL,
    whatsapp VARCHAR(30) NULL,
    instagram VARCHAR(120) NULL,
    tiktok VARCHAR(120) NULL,
    youtube VARCHAR(190) NULL,
    bio TEXT NULL,
    link_bio VARCHAR(500) NULL,
    logo_url VARCHAR(500) NULL,
    imagem_url VARCHAR(500) NULL,
    referencias TEXT NULL,
    observacoes TEXT NULL,
    origem VARCHAR(80) NULL,
    status ENUM('novo','em_analise','oportunidade','descartado') NOT NULL DEFAULT 'novo',
    temperatura ENUM('frio','morno','quente') NOT NULL DEFAULT 'frio',
    captador_uuid CHAR(36) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    KEY idx_leads_status (status),
    KEY idx_leads_captador (captador_uuid),
    CONSTRAINT fk_leads_captador
        FOREIGN KEY (captador_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS oportunidades (
    uuid CHAR(36) NOT NULL,
    lead_uuid CHAR(36) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    status ENUM('aberta','em_desenvolvimento','projeto_pronto','aprovada','em_venda','convertida','perdida') NOT NULL DEFAULT 'aberta',
    valor_estimado_brl DECIMAL(12,2) NULL,
    captador_uuid CHAR(36) NULL,
    desenvolvedor_uuid CHAR(36) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    KEY idx_oportunidades_lead (lead_uuid),
    KEY idx_oportunidades_status (status),
    CONSTRAINT fk_oportunidades_lead
        FOREIGN KEY (lead_uuid) REFERENCES leads (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_oportunidades_captador
        FOREIGN KEY (captador_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL,
    CONSTRAINT fk_oportunidades_desenvolvedor
        FOREIGN KEY (desenvolvedor_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS projetos (
    uuid CHAR(36) NOT NULL,
    oportunidade_uuid CHAR(36) NOT NULL,
    nome VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    url_preview VARCHAR(500) NULL,
    url_producao VARCHAR(500) NULL,
    status ENUM('rascunho','enviado_aprovacao','ajustes','aprovado','reprovado') NOT NULL DEFAULT 'rascunho',
    desenvolvedor_uuid CHAR(36) NULL,
    aprovado_por_uuid CHAR(36) NULL,
    aprovado_em DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    KEY idx_projetos_oportunidade (oportunidade_uuid),
    KEY idx_projetos_status (status),
    CONSTRAINT fk_projetos_oportunidade
        FOREIGN KEY (oportunidade_uuid) REFERENCES oportunidades (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_projetos_desenvolvedor
        FOREIGN KEY (desenvolvedor_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL,
    CONSTRAINT fk_projetos_aprovador
        FOREIGN KEY (aprovado_por_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS vendas (
    uuid CHAR(36) NOT NULL,
    projeto_uuid CHAR(36) NOT NULL,
    comercial_uuid CHAR(36) NULL,
    cliente_nome VARCHAR(160) NOT NULL,
    cliente_email VARCHAR(190) NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    status ENUM('aberta','link_gerado','aguardando_pagamento','paga','cancelada') NOT NULL DEFAULT 'aberta',
    criado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    atualizada_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    KEY idx_vendas_projeto (projeto_uuid),
    KEY idx_vendas_comercial (comercial_uuid),
    KEY idx_vendas_status (status),
    CONSTRAINT fk_vendas_projeto
        FOREIGN KEY (projeto_uuid) REFERENCES projetos (uuid)
        ON DELETE RESTRICT,
    CONSTRAINT fk_vendas_comercial
        FOREIGN KEY (comercial_uuid) REFERENCES usuarios (uuid)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS pagamentos (
    uuid CHAR(36) NOT NULL,
    venda_uuid CHAR(36) NOT NULL,
    provedor VARCHAR(50) NOT NULL DEFAULT 'manual',
    referencia_externa VARCHAR(190) NULL,
    link_pagamento VARCHAR(500) NULL,
    valor_brl DECIMAL(12,2) NOT NULL,
    status ENUM('pendente','aprovado','recusado','cancelado') NOT NULL DEFAULT 'pendente',
    pago_em DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (uuid),
    UNIQUE KEY uq_pagamentos_referencia (provedor, referencia_externa),
    KEY idx_pagamentos_venda (venda_uuid),
    KEY idx_pagamentos_status (status),
    CONSTRAINT fk_pagamentos_venda
        FOREIGN KEY (venda_uuid) REFERENCES vendas (uuid)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
