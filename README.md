# Sales Foundation

Base inicial independente para uma linha de processo comercial que futuramente poderá se transformar em uma API e integrar-se ao Threeebs.

> **Estado:** Fundação / PoC. Este repositório contém apenas a infraestrutura inicial e a documentação do domínio. A aplicação ainda não foi implementada.

## Objetivo

Construir uma operação simples:

**Captação → Oportunidade → Desenvolvimento → Projeto pronto → Comercial → Pagamento → Pontuação → Carteira → Saque**

O projeto é independente do Threeebs neste primeiro momento, mas a modelagem deve preservar compatibilidade futura com os identificadores e conceitos do `threeebs-edge`.

## Stack e arquitetura

A base segue a mesma família tecnológica utilizada pelo `threeebs-edge`:

- PHP 8.3 + Apache
- MySQL 8.4
- Redis 8
- Docker Compose
- estrutura preparada para migrations, seeds, storage e código compartilhado

Existem dois ambientes PHP:

1. **Admin** — administração da operação, usuários, permissões, regras de pontuação, pagamentos e saques.
2. **Users** — ambiente operacional compartilhado por usuários com as funções **Captador, Desenvolvedor e Comercial**.

Um mesmo usuário pode possuir mais de uma dessas funções. A atribuição das funções é controlada pelo administrador.

## O que esta fundação contém

- `docker-compose.yml` com os serviços iniciais;
- `.env.example`;
- estrutura de diretórios para aplicação, banco, infraestrutura e storage;
- documentação de domínio em `docs/`;
- nenhum segredo;
- nenhuma regra de negócio implementada ainda.

## Documentação

Comece por:

1. [Visão geral do projeto](docs/01-PROJECT_OVERVIEW.md)
2. [Arquitetura](docs/02-ARCHITECTURE.md)
3. [Usuários e funções](docs/03-USERS_AND_ROLES.md)
4. [Processo comercial](docs/04-SALES_PROCESS.md)
5. [Pontuação, carteira e saques](docs/05-SCORING_WALLET_WITHDRAWALS.md)
6. [Modelo de dados inicial](docs/06-DATA_MODEL.md)
7. [Compatibilidade com Threeebs Edge](docs/07-THREEEBS_COMPATIBILITY.md)
8. [Instalação local](docs/08-LOCAL_SETUP.md)

## Referência arquitetural

A referência tecnológica e estrutural é:

https://github.com/mahal-cdx/threeebs-edge

O projeto não copia a aplicação do Edge. Ele apenas adota a mesma linha de infraestrutura e organização, mantendo seu próprio domínio e banco.

## Primeiros passos

```bash
cp .env.example .env
docker compose up -d
docker compose ps
```

Nesta etapa os containers podem subir sem uma aplicação funcional, pois as pastas de aplicação estão deliberadamente vazias.

## Próxima fase

A próxima implementação deve começar pela fundação de banco e autenticação, antes das telas de operação.

Não implementar integração com Mercado Pago ou API do Threeebs antes de fechar o modelo interno de pagamentos, eventos de pontuação, carteira e saques.
