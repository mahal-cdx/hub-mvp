# 06 — Modelo de dados inicial

O modelo deve começar simples e preparado para API.

Entidades principais:

```text
usuarios
usuario_funcoes

leads
oportunidades

projetos
vendas
pagamentos

regras_pontuacao
eventos_pontuacao

carteiras
lancamentos_carteira
solicitacoes_saque

auditoria
```

## Identificadores

Entidades de domínio devem utilizar UUID.

Referências futuras ao Threeebs devem utilizar identificadores externos, por exemplo:

```text
usuario_uuid
cliente_uuid
projeto_uuid
```

## Pagamento

O pagamento deve guardar pelo menos:

- UUID interno;
- projeto;
- venda;
- valor;
- provedor;
- identificador externo;
- URL de pagamento;
- status;
- data de confirmação.

## Regra financeira

O lançamento de pontos deve guardar o valor monetário aplicado no momento do evento.

Isso cria um histórico imutável para auditoria.

## Não implementar ainda

Não antecipar tabelas complexas de integração do Threeebs antes de definir o contrato da futura API.
