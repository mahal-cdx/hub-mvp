# 03 — Usuários e funções

## Usuário

Existe uma única entidade de usuário.

A função é uma relação entre usuário e papel operacional.

Funções iniciais:

- `administrador`
- `captador`
- `desenvolvedor`
- `comercial`

## Múltiplas funções

Exemplo:

```text
Usuário Marcelo
├── captador
├── desenvolvedor
└── comercial
```

Não criar três contas para essa pessoa.

## Administração

Somente administradores podem definir quais funções um usuário possui.

## Separação

Funções não são módulos.

```text
FUNÇÕES
├── Administrador
├── Captador
├── Desenvolvedor
└── Comercial

MÓDULOS
├── Leads
├── Oportunidades
├── Projetos
├── Vendas
├── Pontuação
├── Carteira
└── Saques
```
