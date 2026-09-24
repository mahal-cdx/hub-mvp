# 02 — Arquitetura

## Contextos da aplicação

### Admin

Responsável por usuários e papéis do Hub, regras de pontuação, cotação, revisão de projetos, pagamentos, auditoria e saques.

### Users

Área operacional compartilhada por Captadores, Desenvolvedores e usuários Comerciais. Um usuário pode acumular papéis, mas cada ação é autorizada de acordo com o papel e a atribuição daquele registro.

### Shared

Contém bootstrap, acesso ao banco, autenticação, autorização, serviços de domínio, transações, auditoria e contratos comuns aos dois ambientes.

## Dependências

- MySQL guarda o estado de negócio e o histórico durável.
- Redis atende sessões, cache e filas quando a aplicação precisar.
- Arquivos enviados ficam fora do diretório público e são servidos por uma camada autorizada.
- Mercado Pago será um adaptador externo; o domínio interno não depende do formato do provedor.
- Threeebs Edge será integrado por API quando existir um contrato de identidade e recursos adequado ao Hub.

## Isolamento

O Hub possui banco e usuário SQL próprios. Referências a recursos externos usam UUIDs, origem e data de sincronização, sem foreign keys entre bancos.

No MVP, a identidade é local. A estrutura deve aceitar futuramente uma conta vinculada a `threeebs_identity.usuarios.uuid`. O vínculo precisa ser confirmado por um fluxo autenticado; coincidência de e-mail não prova identidade.

## Consistência

Assumir oportunidade, confirmar pagamento, distribuir pontos, reservar saldo e concluir saque são operações transacionais. Cada comando sensível recebe uma chave de idempotência. O histórico é append-only: correções produzem novos eventos.

## Inicialização

As migrations devem usar um runner versionado com tabela `schema_migrations`. Seeds de demonstração são explícitos e exclusivos de ambiente local. O Compose deve aguardar um healthcheck real do MySQL e usar imagens PHP com as extensões exigidas pela aplicação.
