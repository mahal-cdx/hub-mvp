# Limites operacionais, comercial Plus e financeiro

## Limites configuráveis

O administrador controla os limites na página **Projetos**. Os valores iniciais são:

| Etapa | Limite |
|---|---:|
| Leads do captador na fila | 5 |
| Leads do captador em desenvolvimento | 5 |
| Leads do captador abertos no comercial | 10 |
| Projetos ativos por desenvolvedor | 1 |
| Projetos em revisão por desenvolvedor | 5 |
| Prazo da primeira entrega | 12 horas |
| Vendas em atendimento por comercial | 5 |
| Retornos agendados por comercial | 5 |
| Aguardando pagamento por comercial | 5 |

As validações são feitas no servidor dentro das transações que alteram o estágio.

## Prazo de desenvolvimento

Ao assumir uma oportunidade, o prazo é calculado com a configuração vigente. Se a primeira entrega não for enviada até o prazo, a atribuição é removida, um eventual rascunho é arquivado e o lead volta à fila. A ocorrência gera auditoria de sistema.

Projetos em ajustes recebem um novo prazo conforme a configuração vigente.

## Comercial

O resultado **Retorno agendado** exige uma data futura, altera o status da venda e mantém a data no registro principal e no histórico de interações. A tela comercial destaca o próximo contato no topo.

O comercial também pode marcar produtos para a próxima etapa:

- domínio personalizado;
- banco de dados;
- e-mail profissional;
- integração adicional;
- plano de manutenção.

As seleções ficam vinculadas à venda e aparecem no projeto administrativo.

## Financeiro

Pagamentos aprovados geram uma entrada financeira automaticamente. O administrador pode registrar entradas, saídas e despesas manuais, relacionando-as opcionalmente a um projeto. Cada projeto também possui um campo de custo de hospedagem para referência operacional.

## Implantação

```bash
./scripts/migrate.sh
docker compose up -d --build users admin
```

A migration `009_add_operational_limits_extras_finance.sql` é incremental.
