# 04 — Processo operacional

## 1. Cadastro e enriquecimento

O Captador inicia o lead com pelo menos uma forma válida de contato. Pode registrar nome, e-mail, WhatsApp, Instagram, TikTok, YouTube, site, bio, logo, foto, referências, origem e observações.

O sistema calcula completude usando campos e pesos versionados. Enriquecimento pontua somente a contribuição nova prevista na regra, evitando crédito repetido pelo mesmo campo. Temperatura comercial é registrada separadamente.

## 2. Qualificação e oportunidade

O lead pode permanecer em análise, ser qualificado ou descartado com motivo. Um lead qualificado origina uma oportunidade disponível para desenvolvimento.

## 3. Desenvolvimento

Um Desenvolvedor assume a oportunidade em operação atômica. O sistema impede atribuição simultânea a dois desenvolvedores. O responsável cria o projeto, informa a URL de preview e o envia para revisão.

## 4. Revisão administrativa

Cada submissão gera uma revisão. O Administrador aprova, reprova ou devolve para ajustes, sempre com ator, data e observação. Aprovação não equivale a venda.

## 5. Oferta e pagamento

O Administrador define o valor e vincula um link de pagamento ao projeto aprovado. No MVP, o link pode ser criado no painel do Mercado Pago e informado manualmente. A integração futura poderá criar cobranças e receber webhooks.

## 6. Comercial

O projeto entra na fila comercial quando está aprovado e possui oferta ativa. Um vendedor assume o atendimento e registra cada contato, canal, resultado e próximo retorno. Tentativas anteriores permanecem no histórico.

## 7. Confirmação e distribuição

Venda e pagamento têm estados próprios. A confirmação manual inicial exige referência e evidência. Depois, um webhook validado poderá confirmar o pagamento. Uma chave idempotente impede que o mesmo pagamento distribua pontos mais de uma vez.

Quando o pagamento é confirmado, as regras vigentes geram créditos independentes para Captador, Desenvolvedor e Comercial. Cancelamentos e estornos geram eventos compensatórios; registros anteriores não são apagados.
