# 01 — Visão do produto

## Objetivo

Validar uma linha operacional que transforma informações de um lead em um projeto pronto para venda, distribui responsabilidades entre participantes e recompensa contribuições verificáveis.

O MVP precisa responder quatro perguntas:

1. Informações mais completas aumentam a qualidade da oportunidade?
2. O processo permite que um desenvolvedor produza um projeto a partir do cadastro?
3. O comercial consegue trabalhar uma fila de projetos aprovados com preço, pagamento e contato?
4. A pontuação cria incentivo sem perder controle financeiro e histórico?

## Fluxo principal

`Lead → qualificação → desenvolvimento → revisão → projeto aprovado → comercial → pagamento → pontos → saque`

Cada etapa possui responsável, entrada, saída e evento de auditoria.

## Princípios

- Completude do cadastro e temperatura comercial são medidas diferentes.
- Um projeto só chega ao Comercial depois da aprovação administrativa e do vínculo de pagamento.
- Venda e pagamento possuem estados separados.
- Pontos pertencem a um extrato imutável.
- A cotação do ponto pode variar e fica congelada no saque.
- Operações financeiras e de aprovação precisam ser idempotentes e auditáveis.
- O MVP deve funcionar sem depender do Threeebs Edge.
- A futura integração acontece por contratos de API e UUIDs estáveis.

## Evolução esperada

Após validar o MVP, o Hub poderá consumir usuários, clientes e projetos autorizados do ecossistema Threeebs e oferecer oportunidades a afiliados. Essa evolução não altera a autoridade do Edge sobre identidade e clientes, nem a autoridade do Hub sobre o processo comercial, pontos e saques.
