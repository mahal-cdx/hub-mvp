-- Senha inicial de todos os usuários de teste: 123456
-- Trocar/remover essas credenciais antes de qualquer ambiente real.

INSERT INTO funcoes (uuid, chave, nome, descricao) VALUES
    ('00000000-0000-0000-0000-000000000001', 'administrador', 'Administrador', 'Administra usuários, funções, regras e operações.'),
    ('00000000-0000-0000-0000-000000000002', 'captador', 'Captador', 'Realiza a captação e qualificação inicial de leads.'),
    ('00000000-0000-0000-0000-000000000003', 'desenvolvedor', 'Desenvolvedor', 'Assume oportunidades e desenvolve os projetos.'),
    ('00000000-0000-0000-0000-000000000004', 'comercial', 'Comercial', 'Realiza o processo comercial e acompanha as vendas.')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao);

INSERT INTO usuarios (uuid, nome, email, senha_hash, ativo) VALUES
    ('10000000-0000-0000-0000-000000000001', 'Administrador', 'admin@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 1),
    ('10000000-0000-0000-0000-000000000002', 'Captador Teste', 'captador@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 1),
    ('10000000-0000-0000-0000-000000000003', 'Desenvolvedor Teste', 'dev@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 1),
    ('10000000-0000-0000-0000-000000000004', 'Comercial Teste', 'comercial@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 1),
    ('10000000-0000-0000-0000-000000000005', 'Operação Multifuncional', 'operacao@local.test', '$2y$12$buJq4AzhWI1KAdXrB7BdDu.s21u8WTsRI5cZtVc2q0aYxMyx06pFO', 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    ativo = VALUES(ativo);

INSERT IGNORE INTO usuario_funcoes (usuario_uuid, funcao_uuid) VALUES
    ('10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000001'),
    ('10000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000002'),
    ('10000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000003'),
    ('10000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000004'),
    ('10000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000002'),
    ('10000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000003'),
    ('10000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000004');

INSERT INTO carteiras (uuid, usuario_uuid) VALUES
    ('20000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000002'),
    ('20000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000003'),
    ('20000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000004'),
    ('20000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000005')
ON DUPLICATE KEY UPDATE usuario_uuid = VALUES(usuario_uuid);
