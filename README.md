# Threeebs Hub MVP

Módulo experimental para validar uma operação completa de captação, desenvolvimento e venda de projetos digitais.

> **Estado atual:** fundação. A aplicação ainda não foi implementada e as migrations existentes serão revistas antes da primeira execução persistente.

## Processo do produto

1. Um **Captador** cadastra um lead e recebe pontos conforme a qualidade das informações.
2. Um **Desenvolvedor** assume a oportunidade, cria o projeto e informa o link de preview.
3. Um **Administrador** revisa o projeto, solicita ajustes ou aprova.
4. Após a aprovação, o administrador vincula preço e link de pagamento do Mercado Pago.
5. Um usuário **Comercial** assume o atendimento e tenta concluir a venda.
6. Quando o pagamento é confirmado, Captador, Desenvolvedor e Comercial recebem os pontos previstos nas regras.
7. Os pontos podem ser convertidos em dinheiro conforme a cotação vigente no momento da solicitação de saque.

Todas as transições, revisões, contatos, créditos, reservas, estornos e saques devem possuir histórico auditável.

## Direção arquitetural

O Hub funciona como módulo independente durante o MVP:

- PHP 8.3 e Apache;
- MySQL 8.4;
- Redis 8;
- Docker Compose;
- aplicações separadas em `apps/admin`, `apps/users` e código comum em `apps/shared`;
- banco, permissões, pontuação, auditoria e ciclo comercial próprios.

A arquitetura segue os padrões do [Threeebs Edge](https://github.com/mahal-cdx/threeebs-edge) para facilitar a integração futura. A ligação será feita por API e UUIDs estáveis. O Hub não deve acessar diretamente os schemas internos do Edge nem copiar suas credenciais.

## Documentação

1. [Visão do produto](docs/01-PROJECT_OVERVIEW.md)
2. [Arquitetura](docs/02-ARCHITECTURE.md)
3. [Usuários e papéis](docs/03-USERS_AND_ROLES.md)
4. [Processo operacional](docs/04-SALES_PROCESS.md)
5. [Pontos, carteira e saques](docs/05-SCORING_WALLET_WITHDRAWALS.md)
6. [Modelo de dados](docs/06-DATA_MODEL.md)
7. [Integração futura com o Edge](docs/07-THREEEBS_COMPATIBILITY.md)
8. [Instalação local](docs/08-LOCAL_SETUP.md)
9. [Plano de implementação](docs/09-IMPLEMENTATION_PLAN.md)

## Limites desta fase

O repositório ainda não entrega autenticação, telas, API, integração com Mercado Pago, crédito de pontos ou saques funcionais. As migrations e seeds presentes representam uma primeira proposta e não devem ser executados em produção antes do rework descrito na documentação.
