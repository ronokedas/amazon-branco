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

assertRelatorioLayout(!str_contains($arquivo, '<iframe class="admin-review-pdf"'), 'A pre-visualizacao embutida do PDF ainda esta presente.');
assertRelatorioLayout(str_contains($arquivo, 'class="admin-review-document-link"'), 'O acesso ao PDF completo foi removido da revisao.');
assertRelatorioLayout(str_contains($arquivo, 'id="adminRequirementsSearch"'), 'A busca de exigencias da revisao nao foi implementada.');
assertRelatorioLayout(str_contains($arquivo, 'data-requirement-group'), 'Os grupos compactos de exigencias nao foram implementados.');
assertRelatorioLayout(str_contains($arquivo, "'itens' => \$exigencias_as_relatorio"), 'O grupo de exigencias A/S nao foi criado.');
assertRelatorioLayout(str_contains($arquivo, "'itens' => \$exigencias_comuns_relatorio"), 'O grupo de exigencias comuns nao foi criado.');
assertRelatorioLayout(str_contains($arquivo, 'function definirExpansaoReview'), 'Os acordeoes da revisao nao possuem comportamento acessivel.');
assertRelatorioLayout(str_contains($arquivo, 'adminRequirementsNoResults'), 'A busca da revisao nao possui estado sem resultados.');
assertRelatorioLayout(str_contains($arquivo, 'Revise as exigências') && str_contains($arquivo, 'Registre a decisão'), 'A orientacao do fluxo de decisao nao foi adicionada.');

$pdfArquivo = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio_pdf.php');
assertRelatorioLayout($pdfArquivo !== false, 'Não foi possível ler o gerador PDF da vistoria.');
assertRelatorioLayout(
    str_contains($pdfArquivo, "require_once __DIR__ . '/../../includes/certificado_pdf_marca_dagua.php';"),
    'O relatório de vistoria não carrega a marca-dágua compartilhada.'
);
assertRelatorioLayout(
    str_contains($pdfArquivo, 'class RelatorioVistoriaPDF extends CertificadoPdfComMarcaDagua'),
    'A marca-dágua não está aplicada a todas as páginas do relatório de vistoria.'
);

echo "relatorio_vistoria_layout_test: OK\n";
