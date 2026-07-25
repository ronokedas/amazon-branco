<?php

function assertRelatorioLayout(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$arquivo = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
assertRelatorioLayout($arquivo !== false, 'Não foi possível ler a tela do relatório.');

$posData = strpos($arquivo, 'id="data_vistoria"');
$posPrazo = strpos($arquivo, 'id="prazo_exigencias_dias"');
$posChecklist = strpos($arquivo, 'id="checklist-container"');
assertRelatorioLayout($posData !== false && $posPrazo > $posData && $posChecklist > $posPrazo, 'Data, validade e checklist não estão na ordem definida.');
assertRelatorioLayout(substr_count($arquivo, 'name="prazo_exigencias_dias"') === 2, 'Algum dos fluxos de relatório perdeu o campo de validade.');
assertRelatorioLayout(substr_count($arquivo, 'name="checklist_status[]"') === 1, 'A estrutura de envio do checklist foi alterada.');
assertRelatorioLayout(str_contains($arquivo, 'name="formulario_completo" value="1"'), 'A proteção contra truncamento foi removida.');

foreach (['checklistRespondidos', 'checklistPendentes', 'checklistNaoConformes', 'checklistAS'] as $id) {
    assertRelatorioLayout(str_contains($arquivo, 'id="' . $id . '"'), 'Resumo ausente: ' . $id);
}
foreach (['data-counter="respondidos"', 'data-counter="exigencias"', 'data-counter="as"'] as $marcador) {
    assertRelatorioLayout(str_contains($arquivo, $marcador), 'Contador de categoria ausente: ' . $marcador);
}

assertRelatorioLayout(str_contains($arquivo, 'function atualizarContadoresChecklist()'), 'Atualização em tempo real não foi implementada.');
assertRelatorioLayout(str_contains($arquivo, "header.setAttribute('aria-expanded'"), 'Acordeões não atualizam o estado acessível.');
assertRelatorioLayout(str_contains($arquivo, 'checklistSemResultados'), 'Pesquisa não possui estado sem resultados.');
assertRelatorioLayout(str_contains($arquivo, 'O PDF estará disponível após salvar.'), 'Relatório novo não informa quando o PDF ficará disponível.');
$posSalvar = strpos($arquivo, 'id="btnSalvar"');
assertRelatorioLayout($posSalvar !== false && strpos($arquivo, 'report-footer-pdf', $posSalvar) > $posSalvar, 'PDF não aparece imediatamente depois da ação principal.');

echo "relatorio_vistoria_layout_test: OK\n";
