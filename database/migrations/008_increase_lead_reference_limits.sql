ALTER TABLE lead_referencia_arquivos
    DROP CHECK chk_lead_referencia_arquivos_tamanho,
    ADD CONSTRAINT chk_lead_referencia_arquivos_tamanho
        CHECK (tamanho_bytes > 0 AND tamanho_bytes <= 26214400);

INSERT INTO schema_migrations (migration, checksum)
VALUES ('008_increase_lead_reference_limits.sql', SHA2('008_increase_lead_reference_limits.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
