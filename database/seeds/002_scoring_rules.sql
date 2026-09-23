-- Regra inicial: 1 ponto = R$ 0,10.
-- Os pontos são creditados somente após pagamento aprovado.

INSERT INTO regras_pontuacao (uuid, funcao_uuid, evento, pontos, ativo) VALUES
    ('30000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002', 'venda_paga', 10, 1),
    ('30000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', 'venda_paga', 20, 1),
    ('30000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000004', 'venda_paga', 30, 1)
ON DUPLICATE KEY UPDATE
    pontos = VALUES(pontos),
    ativo = VALUES(ativo);
