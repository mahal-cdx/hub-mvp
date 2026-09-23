# 05 — Pontuação, carteira e saques

## Pontuação

As regras devem ser configuráveis pela administração.

Exemplo:

| Evento | Pontos |
|---|---:|
| Lead cadastrado | 5 |
| Lead qualificado | 10 |
| Projeto criado | 15 |
| Projeto aprovado | 20 |
| Venda paga | 30 |

Os valores acima são apenas exemplos.

## Valor do ponto

A administração define o valor monetário atual.

Exemplo:

```text
1 ponto = R$ 0,10
```

Cada lançamento deve guardar o valor do ponto utilizado naquele momento.

Isso impede que uma alteração futura do valor do ponto altere retroativamente créditos antigos.

## Eventos

O sistema deve registrar a origem de cada crédito:

```text
evento
usuário
pontos
valor do ponto
valor em reais
referência do evento
data
status
```

Exemplo:

```text
Venda #123 — pagamento confirmado

Captador       +10 pontos
Desenvolvedor  +20 pontos
Comercial      +30 pontos
```

## Carteira

O saldo exibido deve ser derivado de um extrato auditável.

Não depender apenas de um campo `saldo` como fonte da verdade.

Estados conceituais:

```text
pontos acumulados
valor disponível
valor em saque
valor já pago
```

## Saques

O usuário pode solicitar o saque do saldo disponível.

Estados iniciais:

```text
solicitado
em análise
aprovado
pago
cancelado
```

O administrador controla a aprovação e o pagamento do saque.

## Mercado Pago

O primeiro MVP não precisa consultar automaticamente o Mercado Pago.

Deve existir uma referência para o pagamento e uma confirmação administrativa.

Futuro:

```text
Mercado Pago
    ↓ webhook
Pagamento confirmado
    ↓
Evento financeiro
    ↓
Pontuação
```
