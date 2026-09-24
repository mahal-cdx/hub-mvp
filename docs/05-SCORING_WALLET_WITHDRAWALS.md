# 05 — Pontos, carteira e saques

## Regras de pontuação

As regras são configuráveis, versionadas e possuem vigência. Exemplos de eventos:

- lead cadastrado;
- lead enriquecido;
- projeto aprovado;
- venda paga: bônus do Captador;
- venda paga: bônus do Desenvolvedor;
- venda paga: bônus do Comercial.

Alterar uma regra cria uma nova versão. Créditos antigos preservam regra, evento, origem e quantidade aplicada.

## Extrato de pontos

Pontos são a unidade da carteira. Cada lançamento imutável informa usuário, tipo, quantidade assinada, origem, regra, chave de idempotência e data.

Tipos iniciais:

- crédito;
- estorno;
- reserva para saque;
- liberação de reserva;
- saque pago;
- ajuste administrativo justificado.

O saldo é derivado do extrato. Um campo materializado pode acelerar consultas, mas precisa ser reconciliável e não é a fonte única da verdade.

## Cotação

A Administração publica cotações com valor em reais, início de vigência e motivo. Não existe valor fixo e uma nova cotação não altera créditos nem saques anteriores.

## Saque

Ao solicitar saque, o sistema executa uma única transação:

1. valida os pontos disponíveis;
2. seleciona a cotação vigente;
3. reserva os pontos;
4. guarda pontos, cotação, valor unitário e total em reais;
5. cria a solicitação e o lançamento de reserva.

O valor fica congelado nessa solicitação. Cancelamento libera a reserva uma vez. Pagamento do saque exige referência ou comprovante único. Se uma venda for estornada depois do saque, o sistema registra a compensação e uma possível dívida, preservando todo o histórico.
