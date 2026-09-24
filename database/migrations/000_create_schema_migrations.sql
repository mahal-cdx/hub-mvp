CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    applied_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO schema_migrations (migration, checksum)
VALUES ('000_create_schema_migrations.sql', SHA2('000_create_schema_migrations.sql:v1', 256))
ON DUPLICATE KEY UPDATE migration = VALUES(migration);
