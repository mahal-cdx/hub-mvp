# 08 — Instalação local

## Requisitos

- Docker
- Docker Compose
- Git

## Preparação

```bash
cp .env.example .env
```

Edite as senhas do `.env`.

## Subir a infraestrutura

```bash
docker compose up -d
```

Verificar:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f admin
docker compose logs -f users
```

## Portas padrão

| Serviço | Porta |
|---|---:|
| Admin PHP | 6021 |
| Users PHP | 6022 |
| MySQL | 6023 |
| Redis | 6024 |

As portas podem ser alteradas no `.env`.

## Estado esperado

Neste primeiro pacote, as aplicações PHP estão vazias propositalmente.

A infraestrutura existe para que a próxima etapa possa começar pela implementação da fundação de banco, autenticação e contratos do domínio.

## Segurança

Não versione:

```text
.env
storage/
credenciais
tokens
senhas
```

Em ambiente público, não exponha MySQL e Redis sem uma necessidade operacional clara.
