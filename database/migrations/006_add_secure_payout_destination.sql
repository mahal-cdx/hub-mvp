ALTER TABLE solicitacoes_saque
    ADD COLUMN chave_pix_tipo VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER chave_pix_mascarada,
    ADD COLUMN chave_pix_criptografada TEXT NULL AFTER chave_pix_tipo;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('006_add_secure_payout_destination.sql', SHA2('006_add_secure_payout_destination.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
