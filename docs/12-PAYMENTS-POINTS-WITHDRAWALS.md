# Pagamentos, pontos e saques

## Confirmação manual de pagamento

Quando o comercial marca a venda como `aguardando_pagamento`, ela aparece em **Administração → Financeiro**. O administrador informa a referência do comprovante e confirma o pagamento.

A confirmação ocorre em uma única transação:

1. cria o pagamento aprovado e seu evento;
2. fecha a venda;
3. encerra a oferta e a oportunidade;
4. credita os bônus vigentes para captador, desenvolvedor e comercial;
5. registra a auditoria.

A venda somente é confirmada quando as três regras de bônus por venda paga estão configuradas. As chaves de idempotência impedem crédito repetido.

## Regras de pontos

O administrador define pontos para:

- lead cadastrado;
- lead enriquecido (regra disponível para evolução do formulário);
- projeto aprovado;
- venda paga para o captador;
- venda paga para o desenvolvedor;
- venda paga para o comercial.

Alterar uma regra encerra sua vigência e cria uma nova versão. Eventos anteriores mantêm a regra e a pontuação que receberam.

O cadastro de lead e a aprovação de projeto passam a gerar os créditos de tarefa. A confirmação da venda gera os três bônus de venda paga.

## Cotação

O administrador publica o valor atual de um ponto em reais. A cotação não altera saldos em pontos nem solicitações antigas.

Ao solicitar um saque, o sistema grava uma cópia da cotação. Portanto, o valor em reais do saque não muda, mesmo que uma nova cotação seja publicada depois.

## Ciclo do saque

1. O usuário informa pontos, tipo e chave Pix.
2. Os pontos saem do saldo disponível e entram no saldo reservado.
3. O administrador aprova ou recusa.
4. Se recusar, os pontos reservados voltam ao saldo disponível.
5. Se aprovar, o administrador realiza a transferência e informa a referência.
6. Ao marcar como pago, os pontos são removidos do saldo reservado.

Usuário e administrador visualizam o histórico e o estado atual da solicitação.

## Proteção da chave Pix

A chave Pix é armazenada com AES-256-GCM usando uma chave derivada de `APP_KEY`. Listagens comuns exibem somente a versão mascarada; o valor completo é descriptografado apenas no ambiente administrativo de saques.

A `APP_KEY` deve permanecer estável. Trocá-la sem um processo de rotação torna destinos de pagamento antigos ilegíveis.

## Limites atuais

- confirmação do comprovante é manual;
- a transferência do saque é externa ao sistema;
- Mercado Pago ainda não possui webhook;
- `lead_enriquecido` está configurável, mas será conectado quando existir uma ação específica de enriquecimento.
