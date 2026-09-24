-- Fixture local. Senha inicial: 123456
-- Este arquivo só deve ser aplicado quando LOAD_DEMO_SEEDS=true.

INSERT INTO funcoes (uuid, chave, nome, descricao) VALUES
    ('00000000-0000-0000-0000-000000000001', 'administrador', 'Administrador', 'Administra regras e operações do Hub.'),
    ('00000000-0000-0000-0000-000000000002', 'captador', 'Captador', 'Cadastra e enriquece leads.'),
    ('00000000-0000-0000-0000-000000000003', 'desenvolvedor', 'Desenvolvedor', 'Assume oportunidades e cria projetos.'),
    ('00000000-0000-0000-0000-000000000004', 'comercial', 'Comercial', 'Trabalha projetos aprovados e realiza vendas.')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), descricao = VALUES(descricao);

INSERT INTO usuarios (uuid, nome, email, senha_hash, identidade_origem, status) VALUES
    ('10000000-0000-0000-0000-000000000001', 'Administrador', 'admin@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 'local', 'ativo'),
    ('10000000-0000-0000-0000-000000000002', 'Captador Teste', 'captador@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 'local', 'ativo'),
    ('10000000-0000-0000-0000-000000000003', 'Desenvolvedor Teste', 'dev@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 'local', 'ativo'),
    ('10000000-0000-0000-0000-000000000004', 'Comercial Teste', 'comercial@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 'local', 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), status = VALUES(status);

INSERT IGNORE INTO usuario_funcoes (usuario_id, funcao_id, atribuido_por_usuario_id)
SELECT u.id, f.id, admin.id
FROM usuarios u
JOIN funcoes f ON
    (u.uuid = '10000000-0000-0000-0000-000000000001' AND f.chave = 'administrador')
    OR (u.uuid = '10000000-0000-0000-0000-000000000002' AND f.chave = 'captador')
    OR (u.uuid = '10000000-0000-0000-0000-000000000003' AND f.chave = 'desenvolvedor')
    OR (u.uuid = '10000000-0000-0000-0000-000000000004' AND f.chave = 'comercial')
JOIN usuarios admin ON admin.uuid = '10000000-0000-0000-0000-000000000001';

INSERT INTO carteiras (uuid, usuario_id)
SELECT
    CASE u.uuid
        WHEN '10000000-0000-0000-0000-000000000002' THEN '20000000-0000-0000-0000-000000000002'
        WHEN '10000000-0000-0000-0000-000000000003' THEN '20000000-0000-0000-0000-000000000003'
        WHEN '10000000-0000-0000-0000-000000000004' THEN '20000000-0000-0000-0000-000000000004'
    END,
    u.id
FROM usuarios u
WHERE u.uuid IN (
    '10000000-0000-0000-0000-000000000002',
    '10000000-0000-0000-0000-000000000003',
    '10000000-0000-0000-0000-000000000004'
)
ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id);
