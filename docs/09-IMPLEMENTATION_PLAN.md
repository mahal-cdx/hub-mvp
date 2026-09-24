# 09 — Plano de implementação

## Fase 1 — Fundação confiável

- reescrever migrations de identidade, fluxo e pontos antes do primeiro banco persistente;
- adicionar `schema_migrations` e runner determinístico;
- criar auditoria append-only;
- corrigir healthchecks, imagens PHP e inicialização do Compose;
- separar seeds locais e remover credenciais fixas de qualquer instalação real.

## Fase 2 — Operação principal

- autenticação local e autorização por papel;
- cadastro e enriquecimento de leads;
- qualificação, oportunidade e assunção exclusiva por Desenvolvedor;
- submissão, revisão e aprovação de projetos;
- oferta e fila comercial;
- histórico de contatos.

## Fase 3 — Pontos e financeiro interno

- regras versionadas;
- confirmação manual idempotente de pagamento;
- distribuição transacional de pontos;
- extrato e reconciliação;
- cotação administrada;
- reserva, análise e pagamento de saques;
- estornos e compensações.

## Fase 4 — Integrações

- criação ou vínculo de cobrança no Mercado Pago;
- validação de webhook e consulta ao estado autoritativo;
- adaptador de identidade do Edge;
- contratos para clientes e projetos oficiais;
- observabilidade e reprocessamento de sincronizações.

## Critérios antes de operar valores reais

- testes de concorrência para oportunidade e saque;
- testes de idempotência para pagamento, crédito e estorno;
- restauração comprovada de backup;
- trilha de auditoria consultável;
- limites e permissões revisados;
- procedimento operacional para divergências e saldo devedor.

Cada fase deve produzir migrations reversíveis quando possível, documentação atualizada e evidências de validação em ambiente descartável.
