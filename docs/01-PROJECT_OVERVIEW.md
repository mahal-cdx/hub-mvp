# 01 — Visão geral

## Propósito

Este projeto é um módulo independente de operação comercial. Ele não é, neste primeiro momento, um módulo do Threeebs.

A finalidade inicial é criar uma linha de processo comercial funcional que possa ser utilizada isoladamente e que, futuramente, possa expor uma API para integração com o Threeebs.

## Linha principal

```text
LEAD
  ↓
OPORTUNIDADE
  ↓
DESENVOLVIMENTO
  ↓
PROJETO PRONTO
  ↓
COMERCIAL
  ↓
LINK DE PAGAMENTO
  ↓
PAGAMENTO CONFIRMADO
  ↓
EVENTOS DE PONTUAÇÃO
  ↓
CARTEIRA
  ↓
SOLICITAÇÃO DE SAQUE
```

## Princípios

- Independência do Threeebs no MVP.
- Compatibilidade futura por UUIDs e contratos bem definidos.
- Regras financeiras auditáveis.
- Pontuação desacoplada das tabelas de usuários.
- Pagamento confirmado como gatilho financeiro.
- Funções atribuídas a usuários, não contas separadas por função.
