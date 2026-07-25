<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function assertCiclo(bool $condicao, string $mensagem): void {
    if (!$condicao) throw new RuntimeException($mensagem);
}

$colunas = $pdo->query("SHOW COLUMNS FROM analise_planos_pareceres")->fetchAll(PDO::FETCH_COLUMN);
foreach (['numero','finalidade','submissao_id','relatorio_anterior_id','snapshot_json','validado_por','validado_em'] as $coluna) {
    assertCiclo(in_array($coluna, $colunas, true), "Coluna ausente no relatório de ciclo: {$coluna}");
}
assertCiclo((bool)$pdo->query("SHOW TABLES LIKE 'analise_planos_relatorio_exigencias'")->fetchColumn(), 'Tabela de baixas por ciclo ausente.');

$actions = file_get_contents(__DIR__ . '/../modules/analises_planos/actions.php');
$form = file_get_contents(__DIR__ . '/../modules/analises_planos/form.php');
assertCiclo(!str_contains($form, 'APROVADO_COM_EXIGENCIAS'), 'Interface ainda oferece aprovação com exigências.');
assertCiclo(!str_contains($form, 'name="exigencia_status[]"'), 'Interface ainda permite baixa manual de exigência.');
assertCiclo(str_contains($actions, "gerarNumeroDocumento('RAP-REL','AM-RAP-REL')"), 'Numeração transacional RAP-REL não utilizada.');
assertCiclo(str_contains($actions, 'analisePlanosValidarConclusao'), 'Licença não usa a autorização de saldo zero.');
assertCiclo(str_contains($actions, 'analise_planos_relatorio_exigencias'), 'Publicação não consolida resultados do ciclo.');

echo "analise_planos_relatorios_ciclo_test: OK\n";
