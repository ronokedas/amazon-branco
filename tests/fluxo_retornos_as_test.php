<?php

function assertRetornoAS(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$migration = file_get_contents(__DIR__ . '/../migrations/087_fluxo_retornos_as.sql');
$functions = file_get_contents(__DIR__ . '/../includes/functions.php');
$vistorias = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
$agendamentos = file_get_contents(__DIR__ . '/../modules/agendamentos/actions.php');
$formAgendamento = file_get_contents(__DIR__ . '/../modules/agendamentos/form.php');
$relatorio = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$certificados = file_get_contents(__DIR__ . '/../modules/certificados/wizard_step2.php');
$dashboard = file_get_contents(__DIR__ . '/../modules/dashboard/data.php');

foreach ([$migration,$functions,$vistorias,$agendamentos,$formAgendamento,$relatorio,$certificados,$dashboard] as $arquivo) {
    assertRetornoAS($arquivo !== false, 'Nao foi possivel ler um arquivo do fluxo de retornos A/S.');
}

assertRetornoAS(str_contains($migration, 'CREATE TABLE vistoria_retornos'), 'A migracao nao cria a pendencia auditavel de retorno.');
foreach (['PENDENTE_AGENDAMENTO','AGENDADO','RELATORIO_ENVIADO','CONCLUIDO','CANCELADO'] as $status) {
    assertRetornoAS(str_contains($migration, "'{$status}'"), "Status de retorno ausente: {$status}.");
}
assertRetornoAS(str_contains($migration, 'relatorio_origem_id'), 'O novo agendamento nao fica ligado ao relatorio de origem.');
assertRetornoAS(str_contains($migration, "v.finalidade = 'CUMPRIMENTO_EXIGENCIAS'"), 'A migracao nao relaciona relatorios de cumprimento legados.');

assertRetornoAS(str_contains($functions, 'obterRelatorioVigenteCadeia'), 'A certificacao ainda nao resolve a cadeia entre agendamentos.');
assertRetornoAS(str_contains($functions, 'relatorioNumerosReferenciaCertificado'), 'A referencia dupla de relatorios nao foi centralizada.');
assertRetornoAS(str_contains($functions, "antes_de_suspender=1"), 'A criacao do retorno nao filtra somente A/S.');
assertRetornoAS(str_contains($functions, "status='PENDENTE_AGENDAMENTO'"), 'A validacao nao cria a pendencia de agendamento.');
assertRetornoAS(str_contains($functions, 'gerarNumeroDocumento'), 'O retorno nao recebe novo numero sequencial.');

assertRetornoAS(str_contains($vistorias, 'criarPendenciaRetornoAS'), 'A aprovacao administrativa nao cria retorno para A/S.');
assertRetornoAS(str_contains($vistorias, 'concluirRetornoDoRelatorio'), 'A aprovacao do cumprimento nao conclui a etapa anterior.');
assertRetornoAS(str_contains($agendamentos, 'criarRelatorioCumprimentoAgendamento'), 'A confirmacao do novo agendamento nao cria o relatorio numerado.');
assertRetornoAS(str_contains($agendamentos, "SET status='CANCELADO',motivo_cancelamento=:motivo"), 'O cancelamento do retorno nao exige motivo auditavel.');
assertRetornoAS(substr_count($agendamentos, "relatorio_origem_id']) && \$cargo !== 'ADMIN'") >= 3, 'Criacao, confirmacao ou cancelamento do retorno escapam da permissao ADMIN.');
assertRetornoAS(str_contains($formAgendamento, 'Retorno obrigatório A/S'), 'O formulario nao identifica o retorno obrigatorio.');

assertRetornoAS(str_contains($relatorio, 'Linha do tempo dos relatórios A/S'), 'A cadeia nao aparece na tela do relatorio.');
assertRetornoAS(str_contains($relatorio, 'Validar com A/S'), 'O admin nao recebe a decisao contextual de A/S.');
assertRetornoAS(str_contains($certificados, 'relatorioNumerosReferenciaCertificado'), 'O wizard nao persiste original e cumprimento.');
assertRetornoAS(str_contains($dashboard, 'fluxo_retornos_as'), 'O dashboard nao exibe retornos aguardando agendamento.');

echo "fluxo_retornos_as_test: OK\n";
