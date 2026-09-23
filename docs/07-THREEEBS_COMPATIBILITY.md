# 07 — Compatibilidade com Threeebs Edge

Referência:

https://github.com/mahal-cdx/threeebs-edge

## Estratégia

Este projeto é independente.

Não deve importar o banco do Edge nem depender das aplicações do Edge para funcionar.

A compatibilidade será feita por contratos:

```text
UUID
timestamps
status explícitos
identificadores externos
API futura
```

## Relações futuras

Conceitualmente:

```text
Threeebs Identity
        ↓
usuario_uuid

Threeebs Control
        ↓
cliente_uuid
projeto_uuid
```

O módulo comercial poderá futuramente consumir esses recursos por API.

## Banco

O banco deste projeto deve possuir apenas as entidades necessárias ao seu próprio domínio.

Não duplicar toda a estrutura do Threeebs.

## Integração futura

```text
Sales Foundation
       │
       │ API
       ▼
Threeebs
```

A integração deve ser implementada depois que o domínio interno estiver estável.
