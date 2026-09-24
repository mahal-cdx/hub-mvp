# Enriquecimento de leads e referências visuais

## Objetivo

Permitir que o captador construa um cadastro progressivamente mais rico enquanto a oportunidade ainda não foi assumida, entregando ao desenvolvedor todas as referências necessárias sem expor informações antes da assunção.

## Regras do cadastro

- `link da bio` é opcional e aceita apenas URLs HTTP/HTTPS válidas;
- é possível selecionar ou colar até 12 imagens JPG, PNG, WEBP ou GIF por envio;
- cada imagem pode ter no máximo 10 MB e recebe nome aleatório no armazenamento privado;
- o captador pode editar dados e remover referências somente enquanto a oportunidade está aberta e sem desenvolvedor;
- após a assunção, o cadastro fica congelado para preservar o contexto usado pelo desenvolvedor.

A temperatura não é escolhida pelo usuário. Ela deriva da completude:

| Informação | Peso |
|---|---:|
| Nome | 10 |
| E-mail | 15 |
| WhatsApp | 15 |
| Instagram | 10 |
| Bio | 15 |
| Link da bio | 10 |
| Origem | 5 |
| Observações | 10 |
| Referências visuais | 10 |

- frio: menos de 45%;
- morno: de 45% a 74%;
- quente: 75% ou mais.

Toda criação, atualização e remoção de referência gera histórico de completude e auditoria.

## Status exibido ao captador

| Status | Significado |
|---|---|
| Na fila | oportunidade aberta e ainda não assumida |
| Em desenvolvimento | desenvolvedor assumiu a oportunidade |
| Aberto comercial | projeto aprovado e disponível/em atendimento comercial |
| Recusado | oportunidade ou venda encerrada sem sucesso |
| Venda fechada | pagamento aprovado |

## Experiência do desenvolvedor

As oportunidades públicas continuam sem contatos ou referências. Depois de assumir, o desenvolvedor pode:

- consultar a bio, link da bio e imagens de referência;
- abrir a imagem, baixá-la ou copiá-la para a área de transferência;
- abrir o editor externo em `https://3eb.site/parceiro`;
- editar a entrega enquanto o projeto está assumido ou voltou para ajustes.

Depois de enviar para aprovação, o formulário fica bloqueado até que o administrador solicite ajustes.

## Reprovação administrativa

A revisão diferencia três decisões:

1. **Solicitar ajustes:** mantém o projeto com o mesmo desenvolvedor e reabre a edição.
2. **Reprovar e reabrir a fila:** arquiva a tentativa recusada, remove a atribuição e devolve a oportunidade à fila.
3. **Arquivar definitivamente:** encerra a oportunidade como cancelada.

A separação evita que uma reprovação técnica descarte um lead que ainda pode ser atendido por outro desenvolvedor.

## Implantação

A migration `007_add_lead_bio_and_references.sql` é incremental. Não é necessário apagar os volumes do MVP:

```bash
./scripts/migrate.sh
docker compose up -d --build users admin
```

Uploads ficam em `storage/uploads`, fora do Git, e são servidos somente por rota autenticada e autorizada.
