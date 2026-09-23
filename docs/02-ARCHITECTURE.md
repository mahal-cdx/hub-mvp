# 02 — Arquitetura

## Ambientes PHP

### Admin

Responsável por:

- usuários;
- funções e permissões;
- leads;
- oportunidades;
- projetos;
- aprovações;
- regras de pontuação;
- valor monetário do ponto;
- pagamentos;
- saques;
- auditoria.

### Users

Ambiente utilizado pelos usuários operacionais:

- Captador;
- Desenvolvedor;
- Comercial.

O mesmo usuário pode possuir uma ou várias funções.

## Serviços Docker

```text
admin  ─────┐
users  ─────┼── MySQL
             └── Redis
```

Os dois ambientes PHP compartilham a mesma base de infraestrutura, mas possuem contextos de aplicação diferentes.

## Diretórios

```text
apps/
├── admin/
├── users/
└── shared/

database/
├── migrations/
└── seeds/

infrastructure/
└── apache/

storage/
├── logs/
└── uploads/

docs/
```

A estrutura está preparada para crescer sem misturar documentação, domínio, banco e infraestrutura.
