<?php

function assertRetornoExigencias(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$migration = file_get_contents(__DIR__ . '/../migrations/098_retornos_exigencias_reescrita.sql');
$functions = file_get_contents(__DIR__ . '/../includes/functions.php');
$vistoriasActions = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
$relatorio = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$pdf = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio_pdf.php');
$agenda = file_get_contents(__DIR__ . '/../modules/agendamentos/index.php');
$dashboardData = file_get_contents(__DIR__ . '/../modules/dashboard/data.php');
$dashboardView = file_get_contents(__DIR__ . '/../modules/dashboard/views/vistoriador.php');
$dashboardAdmin = file_get_contents(__DIR__ . '/../modules/dashboard/views/admin.php');
$dashboardAnalista = file_get_contents(__DIR__ . '/../modules/dashboard/views/analista.php');

foreach ([$migration,$functions,$vistoriasActions,$relatorio,$pdf,$agenda,$dashboardData,$dashboardView,$dashboardAdmin,$dashboardAnalista] as $arquivo) {
    assertRetornoExigencias($arquivo !== false, 'Nao foi possivel ler um arquivo do fluxo de retorno por exigencias.');
}

foreach (['tipo ENUM(\'AS\',\'EXIGENCIAS\')','descricao_reescrita','numero_origem','numero_sequencial'] as $trecho) {
    assertRetornoExigencias(str_contains($migration, $trecho), 'Migracao incompleta: ' . $trecho);
}
assertRetornoExigencias(str_contains($functions, 'function criarPendenciaRetorno('), 'Retorno ainda nao foi generalizado.');
assertRetornoExigencias(str_contains($functions, 'obterRelatorioCertificavelCadeia'), 'Certificacao nao separa relatorio certificavel do ultimo rascunho.');
assertRetornoExigencias(str_contains($functions, 'recalcularSequencialExigenciasRelatorio'), 'Novo sequencial nao foi centralizado.');
assertRetornoExigencias(str_contains($functions, 'numerarExigenciasPorSecao'), 'Numeracao por secao nao foi centralizada.');
assertRetornoExigencias(str_contains($functions, 'construirHistoricoComparativoRelatorio'), 'Historico completo da cadeia nao foi implementado.');
assertRetornoExigencias(str_contains($vistoriasActions, '$tipoRetorno'), 'Endpoint nao recebe o tipo de retorno.');
assertRetornoExigencias(str_contains($vistoriasActions, '$descricoesReescritas'), 'Descricao reescrita nao e persistida.');
assertRetornoExigencias(!str_contains($vistoriasActions, 'Descreva as evidencias verificadas para cada exigencia herdada'), 'Observacao herdada continua obrigatoria.');
assertRetornoExigencias(str_contains($relatorio, 'Reagendar retorno de exigências'), 'Admin nao recebeu a acao de reagendamento comum.');
assertRetornoExigencias(str_contains($relatorio, 'cumprimento_descricao_reescrita'), 'Formulario nao possui descricao reescrita.');
assertRetornoExigencias(
    str_contains($relatorio, 'Resultado da verificação — exigências cumpridas')
        && str_contains($relatorio, '$exigencias_cumpridas_relatorio'),
    'A consulta final do relatorio nao exibe separadamente as exigencias cumpridas.'
);
assertRetornoExigencias(str_contains($relatorio, "getElementById('buscaChecklist')?.addEventListener"), 'Checklist ausente ainda interrompe o JavaScript da reescrita.');
assertRetornoExigencias(str_contains($relatorio, "getElementById('avulsaEmpty')?.classList"), 'Tabela avulsa ausente ainda interrompe o JavaScript do relatorio de retorno.');
assertRetornoExigencias(str_contains($relatorio, "select.addEventListener('change', atualizarCamposReescrita)"), 'Mudanca do resultado nao aciona o campo de reescrita.');
assertRetornoExigencias(!preg_match('/cumprimento_observacao\\[[^\\]]+\\][^>]*required/', $relatorio), 'Observacao continua obrigatoria no HTML.');
assertRetornoExigencias(!str_contains($relatorio, 'name="cumprimento_observacao['), 'Observacao herdada continua editavel.');
assertRetornoExigencias(!str_contains($relatorio, 'name="nova_exigencia_observacao['), 'Observacao de nova exigencia continua editavel.');
assertRetornoExigencias(str_contains($relatorio, 'Observação registrada no relatório anterior'), 'Contexto da observacao anterior nao aparece como somente leitura.');
assertRetornoExigencias(!str_contains($vistoriasActions, '$_POST[\'cumprimento_observacao\']'), 'Payload legado ainda sobrescreve a observacao no filho.');
assertRetornoExigencias(str_contains($pdf, 'RESULTADO DA VERIFICAÇÃO'), 'PDF nao separa o resultado da verificacao.');
assertRetornoExigencias(str_contains($pdf, 'EXIGÊNCIAS VIGENTES'), 'PDF nao separa exigencias vigentes.');
assertRetornoExigencias(str_contains($pdf, 'Exigência cumprida'), 'Rotulo de cumprimento ausente do PDF.');
assertRetornoExigencias(!str_contains($pdf, "'Relatório anterior: '"), 'O cabecalho do retorno ainda exibe dados especiais do relatorio anterior.');
assertRetornoExigencias(str_contains($pdf, "É vinculado ao relatório anterior"), 'A observacao nao referencia o numero do relatorio anterior.');
assertRetornoExigencias(str_contains($pdf, '<table border="1" cellpadding="4"'), 'Observacoes nao usam tabela numerada.');
assertRetornoExigencias(!str_contains($pdf, "\$v['texto_observacoes_geradas']"), 'PDF ainda usa o texto legado de observacoes.');
assertRetornoExigencias(str_contains($agenda, 'RETORNO - EXIGÊNCIAS'), 'Agenda nao identifica retorno comum.');
assertRetornoExigencias(str_contains($dashboardView, 'return-as') && str_contains($dashboardView, 'return-requirements'), 'Dashboard nao distingue os retornos.');
assertRetornoExigencias(!str_contains($dashboardData, "base['tarefas_as']"), 'Consulta nao renderizada tarefas_as continua ativa.');
assertRetornoExigencias(str_contains($relatorio, '$stmtRelatorioRetorno->fetchColumn() ?: null'), 'A abertura sem vistoria_id ainda tenta duplicar um relatorio de retorno existente.');
assertRetornoExigencias(
    str_contains($relatorio, 'relatorio_origem_id=:origem_id OR relatorio_resultado_id=:resultado_id'),
    'A consulta do tipo de retorno ainda reutiliza placeholder nomeado e falha com HY093.'
);
assertRetornoExigencias(str_contains($dashboardAdmin, "'&vistoria_id='.urlencode(\$item['id'])"), 'Dashboard admin nao abre o relatorio exato enviado para aprovacao.');
assertRetornoExigencias(str_contains($dashboardAnalista, '&vistoria_id=<?= urlencode($old[\'id\']) ?>'), 'Atalho do analista nao abre o relatorio exato enviado para aprovacao.');

echo "fluxo_retornos_exigencias_test: OK\n";
