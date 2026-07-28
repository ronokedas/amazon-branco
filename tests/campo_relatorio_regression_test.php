<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertCampoRelatorio(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$blocos = [
    'seco' => 'Vistoria em Seco',
    'flutuando' => 'Vistoria Flutuando',
    'borda_livre' => 'Vistoria de Borda Livre',
    'arqueacao' => 'Vistoria de Arqueação',
];
$exigenciaCampo = [[
    'bloco_vistoria' => 'seco',
    'descricao' => 'Deficiência registrada no Campo',
    'item_normam' => 'NORMAM-202/DPC, item 2.1',
    'vencimento' => '2026-09-30',
]];
$blocosPdf = blocosComExigenciasRelatorioPdf('Vistoria Inicial Flutuando', $blocos, $exigenciaCampo);

assertCampoRelatorio(
    array_keys($blocosPdf) === ['seco', 'flutuando'],
    'O PDF omite um bloco que possui exigencia persistida pelo Campo.'
);
assertCampoRelatorio(
    array_keys(blocosComExigenciasRelatorioPdf('Vistoria Inicial Flutuando', $blocos, [])) === ['flutuando'],
    'O PDF deixou de respeitar os blocos previstos quando nao ha exigencias adicionais.'
);

$relatorio = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$actions = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
$checklistCampo = file_get_contents(__DIR__ . '/../pwa-campo/src/screens/ChecklistScreen.jsx');
$pdf = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio_pdf.php');

assertCampoRelatorio(
    substr_count($relatorio, '<option value="" selected disabled>Selecione o resultado...</option>') === 2,
    'Os formularios editaveis nao iniciam o Resultado Final sem selecao.'
);
assertCampoRelatorio(
    str_contains($relatorio, "'status_vistoria']") && str_contains($relatorio, "'status_vistoria']);"),
    'O Resultado Final ainda pode ser restaurado automaticamente pelo rascunho local.'
);
assertCampoRelatorio(
    str_contains($actions, "trim((string)(\$_POST['status_vistoria'] ?? ''))")
        && str_contains($actions, 'Selecione obrigatoriamente o Resultado Final da Vistoria.'),
    'O backend ainda aceita Resultado Final ausente ou aplica fallback silencioso.'
);
assertCampoRelatorio(
    str_contains($checklistCampo, "onChange(item, 'CONFORME') !== false) onEvidence(item)")
        && str_contains($checklistCampo, "['CONFORME', 'NAO_CONFORME'].includes"),
    'O Campo nao oferece evidencias para itens conformes.'
);
assertCampoRelatorio(
    str_contains($pdf, 'blocosComExigenciasRelatorioPdf(')
        && str_contains($pdf, "\$descricao = (\$item['status_item'] ?? '') === 'cumprida_parcial_reescrita'")
        && str_contains($pdf, "\$item['descricao_reescrita']")
        && str_contains($pdf, "\$normam = trim")
        && str_contains($pdf, 'formatarDataBR($item[\'vencimento\'])'),
    'A tabela do PDF nao preserva descricao, NORMAM e vencimento das exigencias.'
);

echo "campo_relatorio_regression_test: OK\n";
