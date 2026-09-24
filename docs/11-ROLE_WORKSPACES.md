# Ambientes por função

Esta entrega adiciona o primeiro fluxo operacional navegável do Hub MVP.

## Perfis e permissões

| Função | Ambiente | Ações |
| --- | --- | --- |
| Administrador | `admin` | Criar usuários, combinar funções, ativar/bloquear contas, revisar projetos e criar oferta |
| Captador | `users/leads` | Cadastrar lead com contato, origem, temperatura e contexto |
| Desenvolvedor | `users/dev` | Ver oportunidades livres, assumir uma delas e enviar preview |
| Comercial | `users/sales` | Ver ofertas aprovadas, assumir venda e registrar atendimento |

Um usuário pode acumular funções operacionais. A navegação mostra somente os ambientes liberados.

## Fluxo implementado

1. O administrador cria uma conta e seleciona uma ou mais funções.
2. O captador cadastra um lead. O sistema registra a completude e abre uma oportunidade.
3. O desenvolvedor assume a oportunidade de forma atômica e envia o projeto para revisão.
4. O administrador aprova, solicita ajustes ou reprova. Ao aprovar, informa valor e link HTTPS de pagamento.
5. O sistema cria oferta e venda disponível.
6. O comercial assume a venda e registra canal, resultado, observações e próximo contato.

## Segurança e rastreabilidade

- sessões separadas entre administração e operação;
- senha com no mínimo 12 caracteres no cadastro administrativo;
- CSRF em todas as mutações;
- consultas preparadas e validação de URLs;
- autorização conferida novamente no banco a cada requisição;
- bloqueio de corrida ao assumir oportunidades e vendas;
- log de autenticação e eventos relevantes no histórico de auditoria;
- links externos abertos com isolamento de origem.

A identidade visual é inspirada no `threeebs-identity`. A autenticação permanece sob responsabilidade do Hub.

## Escopo desta etapa

A carteira é criada e exibida, mas os créditos de pontuação e a confirmação automática do Mercado Pago não fazem parte desta entrega. Esses eventos serão implementados com idempotência em uma etapa posterior.
