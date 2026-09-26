# Privacidade e layout das filas operacionais

## Princípio

Uma oportunidade aberta deve revelar somente o necessário para que o membro decida assumi-la. Dados pessoais e comerciais são liberados somente depois de uma atribuição atômica bem-sucedida.

Ocultar os dados apenas com CSS não é suficiente. Por isso, as consultas das filas abertas também foram reduzidas.

## Desenvolvimento

### Oportunidades

A fila aberta retorna somente:

- UUID interno necessário para assumir;
- data de entrada na fila.

Nome do lead, contatos, bio, observações e referências não são consultados antes da atribuição.

### Meus projetos

Depois de assumir, o desenvolvedor recebe:

- lead e contatos;
- briefing, observações e referências;
- dados do projeto e preview;
- formulário de envio ou reenvio.

Os projetos são apresentados verticalmente em sanfonas. Projetos em revisão ficam em modo de acompanhamento; o formulário reabre quando há ajustes.

## Comercial

### Em atendimento

Vendas atribuídas exibem em sanfonas:

- nome e contatos do lead;
- nome e preview do projeto;
- valor da oferta;
- link de pagamento;
- formulário de acompanhamento.

### Oportunidades

Antes de assumir, a fila comercial não consulta nem renderiza:

- lead;
- contatos;
- valor;
- preview;
- link de pagamento.

## Validação

O teste `tests/operational-privacy.php` verifica que os campos sensíveis não aparecem nas consultas ou nos trechos de interface das filas abertas.
