# 06 — Modelo de dados

## Identidade e autorização

- `usuarios`
- `funcoes`
- `usuario_funcoes`

`usuarios` deve aceitar identidade local e vínculo opcional com um UUID verificado do Edge. Credenciais externas não são copiadas.

## Captação e produção

- `leads`
- `lead_contatos`
- `lead_revisoes_completude`
- `oportunidades`
- `projetos`
- `projeto_revisoes`

Contatos são normalizados por tipo. Revisões de completude guardam regra, campos considerados e autor. Assunção de oportunidade registra responsável e horário.

## Comercial e pagamento

- `ofertas`
- `vendas`
- `interacoes_comerciais`
- `pagamentos`
- `pagamento_eventos`

Projeto, oferta, venda e pagamento têm ciclos independentes. Eventos externos usam referência e chave idempotente obrigatórias. Estornos são novos eventos.

## Pontos e saques

- `regras_pontuacao`
- `eventos_pontuacao`
- `lancamentos_pontos`
- `cotacoes_ponto`
- `solicitacoes_saque`
- `saque_eventos`

A carteira é apurada pelo extrato. Solicitações guardam os pontos reservados e a cotação usada.

## Histórico

- `eventos_auditoria`

A auditoria registra ator, origem, ação, entidade, request ID, estado anterior e posterior quando apropriado. Segredos, tokens e dados sensíveis completos não entram no log.

## Identificadores

Entidades públicas usam UUID canônico. IDs numéricos internos podem existir para desempenho. Referências entre bancos ou serviços usam UUID sem foreign key cruzada. Vínculos opcionais com Edge incluem origem e data de sincronização.
