-- Fixture local com regras e cotação inicial administrável.
-- A cotação não é fixa: novas vigências devem criar novos registros.

INSERT INTO regras_pontuacao
    (uuid, funcao_id, evento_chave, versao, pontos, vigente_desde, ativo, criado_por_usuario_id)
SELECT seed.uuid, f.id, seed.evento_chave, 1, seed.pontos, '2026-01-01 00:00:00.000000', 1, admin.id
FROM (
    SELECT '30000000-0000-0000-0000-000000000001' uuid, 'captador' funcao, 'lead_cadastrado' evento_chave, 5 pontos
    UNION ALL SELECT '30000000-0000-0000-0000-000000000002', 'captador', 'lead_enriquecido', 2
    UNION ALL SELECT '30000000-0000-0000-0000-000000000003', 'desenvolvedor', 'projeto_aprovado', 20
    UNION ALL SELECT '30000000-0000-0000-0000-000000000004', 'captador', 'venda_paga_captador', 10
    UNION ALL SELECT '30000000-0000-0000-0000-000000000005', 'desenvolvedor', 'venda_paga_desenvolvedor', 20
    UNION ALL SELECT '30000000-0000-0000-0000-000000000006', 'comercial', 'venda_paga_comercial', 30
) seed
JOIN funcoes f ON f.chave = seed.funcao
JOIN usuarios admin ON admin.uuid = '10000000-0000-0000-0000-000000000001'
ON DUPLICATE KEY UPDATE pontos = VALUES(pontos), ativo = VALUES(ativo);

INSERT INTO cotacoes_ponto
    (uuid, valor_brl, vigente_desde, definido_por_usuario_id, motivo)
SELECT
    '40000000-0000-0000-0000-000000000001',
    0.100000,
    '2026-01-01 00:00:00.000000',
    admin.id,
    'Cotação inicial da fixture local'
FROM usuarios admin
WHERE admin.uuid = '10000000-0000-0000-0000-000000000001'
ON DUPLICATE KEY UPDATE motivo = VALUES(motivo);
