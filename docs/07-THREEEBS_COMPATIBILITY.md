# 07 — Integração futura com Threeebs Edge

Referência: [mahal-cdx/threeebs-edge](https://github.com/mahal-cdx/threeebs-edge).

## Responsabilidades

O Edge continua como autoridade sobre identidade Threeebs, clientes e projetos oficiais. O Hub controla captação experimental, produção para venda, operação comercial, regras de pontos, cotação e saques.

## MVP independente

O MVP usa contas locais e banco próprio. Ele precisa operar mesmo quando o Edge estiver indisponível. O usuário SQL do Hub não recebe acesso aos schemas internos do Edge.

Para preparar a integração, entidades relevantes aceitam referências externas:

- `edge_usuario_uuid`;
- `edge_cliente_uuid`;
- `edge_projeto_uuid`;
- origem e data de sincronização.

Essas referências só são preenchidas quando houver vínculo real e verificado.

## Identidade futura

A autenticação futura usa um adaptador que valida a identidade ativa no Edge por API. O Hub mantém seu perfil operacional e seus papéis. O vínculo não pode ser criado apenas por igualdade de e-mail e o Hub nunca armazena a senha do Edge.

## API futura

Contratos versionados devem permitir:

- resolver e validar usuário;
- consultar clientes e projetos autorizados;
- promover um lead convertido para cliente;
- publicar ou associar um projeto vendido;
- receber eventos assinados com idempotência;
- rastrear cada chamada por request ID.

A API aplica escopo mínimo e não expõe tabelas internas. Falhas de sincronização entram em fila para nova tentativa e não apagam o histórico local.
