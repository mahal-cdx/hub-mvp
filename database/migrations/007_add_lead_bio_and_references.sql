ALTER TABLE leads
    ADD COLUMN bio_url VARCHAR(500) NULL AFTER bio;

ALTER TABLE projetos
    DROP INDEX uq_projetos_oportunidade,
    ADD KEY idx_projetos_oportunidade (oportunidade_id);

CREATE TABLE IF NOT EXISTS lead_referencia_arquivos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    lead_id BIGINT UNSIGNED NOT NULL,
    enviado_por_usuario_id BIGINT UNSIGNED NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho_storage VARCHAR(500) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    mime_type VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_lead_referencia_arquivos_uuid (uuid),
    UNIQUE KEY uq_lead_referencia_arquivos_path (caminho_storage),
    KEY idx_lead_referencia_arquivos_lead (lead_id, created_at),
    CONSTRAINT fk_lead_referencia_arquivos_lead
        FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
    CONSTRAINT fk_lead_referencia_arquivos_usuario
        FOREIGN KEY (enviado_por_usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT chk_lead_referencia_arquivos_tamanho
        CHECK (tamanho_bytes > 0 AND tamanho_bytes <= 10485760),
    CONSTRAINT chk_lead_referencia_arquivos_mime
        CHECK (mime_type IN ('image/jpeg','image/png','image/webp','image/gif'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('007_add_lead_bio_and_references.sql', SHA2('007_add_lead_bio_and_references.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
