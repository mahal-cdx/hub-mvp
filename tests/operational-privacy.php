<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$workflow = file_get_contents($root . '/apps/shared/workflow.php');
$developer = file_get_contents($root . '/apps/users/views/developer.php');
$sales = file_get_contents($root . '/apps/users/views/sales.php');

if (!is_string($workflow) || !is_string($developer) || !is_string($sales)) {
    fwrite(STDERR, "Não foi possível ler os arquivos operacionais.\n");
    exit(1);
}

function segment(string $source, string $start, string $end): string
{
    $from = strpos($source, $start);
    $to = $from === false ? false : strpos($source, $end, $from + strlen($start));
    if ($from === false || $to === false) {
        throw new RuntimeException("Marcadores ausentes: {$start} / {$end}");
    }
    return substr($source, $from, $to - $from);
}

function assert_absent(string $content, array $forbidden, string $context): void
{
    foreach ($forbidden as $field) {
        if (str_contains($content, $field)) {
            fwrite(STDERR, "Campo sensível {$field} exposto em {$context}.\n");
            exit(1);
        }
    }
}

$developerOpenQuery = segment($workflow, '$open = db()->query(', '$mineStatement = db()->prepare(');
assert_absent(
    $developerOpenQuery,
    ['lead_nome', 'lead_contatos', 'l.bio', 'l.referencias', 'l.observacoes'],
    'consulta de oportunidades de desenvolvimento'
);

$salesFunction = segment($workflow, 'function list_commercial_sales', 'function claim_sale');
$salesOpenQuery = segment($salesFunction, '$available = db()->query(', '$mineStatement = db()->prepare(');
assert_absent(
    $salesOpenQuery,
    ['lead_nome', 'lead_contatos', 'valor_brl', 'link_pagamento', 'url_preview'],
    'consulta de oportunidades comerciais'
);

$developerOpenView = segment($developer, 'id="dev-opportunities-title"', 'id="my-projects-title"');
assert_absent($developerOpenView, ['lead_nome', 'contatos', 'bio', 'referencias'], 'tela aberta de desenvolvimento');

$salesOpenView = substr($sales, (int) strpos($sales, 'id="sales-opportunities-title"'));
assert_absent($salesOpenView, ['lead_nome', 'contatos', 'valor_brl', 'link_pagamento', 'url_preview'], 'tela aberta comercial');

if (!str_contains($developer, '<details class="accordion-card">')
    || !str_contains($sales, '<details class="accordion-card sales-accordion">')) {
    fwrite(STDERR, "Sanfonas operacionais ausentes.\n");
    exit(1);
}

echo "Privacidade e layout operacional validados.\n";
