<?php

function assertRetornoAS(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$migration = file_get_contents(__DIR__ . '/../migrations/087_fluxo_retornos_as.sql');
$migration095 = file_get_contents(__DIR__ . '/../migrations/095_retorno_as_sem_aprovacao.sql');
$migration096 = file_get_contents(__DIR__ . '/../migrations/096_sincronizar_as_checklist.sql');
$functions = file_get_contents(__DIR__ . '/../includes/functions.php');
$vistorias = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
$agendamentos = file_get_contents(__DIR__ . '/../modules/agendamentos/actions.php');
$formAgendamento = file_get_contents(__DIR__ . '/../modules/agendamentos/form.php');
$relatorio = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$certificados = file_get_contents(__DIR__ . '/../modules/certificados/wizard_step2.php');
$dashboard = file_get_contents(__DIR__ . '/../modules/dashboard/data.php');
$dashboardView = file_get_contents(__DIR__ . '/../modules/dashboard/views/admin.php');
$assinaturasActions = file_get_contents(__DIR__ . '/../modules/minhas_assinaturas/actions.php');

foreach ([$migration,$migration095,$migration096,$functions,$vistorias,$agendamentos,$formAgendamento,$relatorio,$certificados,$dashboard,$dashboardView,$assinaturasActions] as $arquivo) {
    assertRetornoAS($arquivo !== false, 'Nao foi possivel ler um arquivo do fluxo de retornos A/S.');
}

assertRetornoAS(str_contains($migration, 'CREATE TABLE vistoria_retornos'), 'A migracao nao cria a pendencia auditavel de retorno.');
foreach (['PENDENTE_AGENDAMENTO','AGENDADO','RELATORIO_ENVIADO','CONCLUIDO','CANCELADO'] as $status) {
    assertRetornoAS(str_contains($migration, "'{$status}'"), "Status de retorno ausente: {$status}.");
}
assertRetornoAS(str_contains($migration, 'relatorio_origem_id'), 'O novo agendamento nao fica ligado ao relatorio de origem.');
assertRetornoAS(str_contains($migration, "v.finalidade = 'CUMPRIMENTO_EXIGENCIAS'"), 'A migracao nao relaciona relatorios de cumprimento legados.');
assertRetornoAS(str_contains($migration095, "'RETORNO_AS'"), 'A migracao 095 nao adiciona o estado impeditivo.');
assertRetornoAS(str_contains($migration095, 'vistoriador_origem_id'), 'A migracao 095 nao registra auditoria do vistoriador.');
assertRetornoAS(str_contains($migration096, 'GREATEST(r.sem_prazo, ve.antes_de_suspender)'), 'A migracao 096 nao preserva marcadores A/S historicos.');

assertRetornoAS(str_contains($functions, 'obterRelatorioVigenteCadeia'), 'A certificacao ainda nao resolve a cadeia entre agendamentos.');
assertRetornoAS(str_contains($functions, 'relatorioNumerosReferenciaCertificado'), 'A referencia dupla de relatorios nao foi centralizada.');
assertRetornoAS(
    str_contains($functions, "vencimento,antes_de_suspender,'pendente',id"),
    'A criacao do retorno nao preserva a classificacao A/S ou comum.'
);
assertRetornoAS(
    str_contains($functions, "WHERE vistoria_id=:origem\n          AND conforme='nao'"),
    'A criacao do retorno nao copia todas as exigencias ainda pendentes.'
);
assertRetornoAS(
    str_contains($functions, 'obterExigenciasComunsPendentesCadeia'),
    'A certificacao nao resolve as exigencias comuns pelo estado efetivo da cadeia.'
);
assertRetornoAS(str_contains($functions, "status='PENDENTE_AGENDAMENTO'"), 'A validacao nao cria a pendencia de agendamento.');
assertRetornoAS(str_contains($functions, 'gerarNumeroDocumento'), 'O retorno nao recebe novo numero sequencial.');

assertRetornoAS(str_contains($vistorias, 'encaminharRelatorioParaRetornoAS'), 'A decisao administrativa nao cria atomicamente o retorno para A/S.');
assertRetornoAS(str_contains($vistorias, "decisao === 'retorno_as'"), 'O endpoint nao reconhece a decisao retorno_as.');
assertRetornoAS(str_contains($vistorias, 'concluirRetornoDoRelatorio'), 'A aprovacao do cumprimento nao conclui a etapa anterior.');
assertRetornoAS(str_contains($vistorias, "dashboard#retornos-as"), 'O encaminhamento A/S nao leva o administrador para o novo agendamento.');
assertRetornoAS(str_contains($vistorias, 'sem_prazo = :sem_prazo_upd'), 'O formulario web nao persiste o marcador A/S na resposta do checklist.');
assertRetornoAS(str_contains($vistorias, '$checklist_sem_prazo_por_id[$cat_id]'), 'O salvamento A/S ainda depende somente do JavaScript e da posicao do item.');
assertRetornoAS(str_contains($agendamentos, 'criarRelatorioCumprimentoAgendamento'), 'A confirmacao do novo agendamento nao cria o relatorio numerado.');
assertRetornoAS(str_contains($agendamentos, "SET status='CANCELADO',motivo_cancelamento=:motivo"), 'O cancelamento do retorno nao exige motivo auditavel.');
assertRetornoAS(substr_count($agendamentos, "relatorio_origem_id']) && \$cargo !== 'ADMIN'") >= 3, 'Criacao, confirmacao ou cancelamento do retorno escapam da permissao ADMIN.');
assertRetornoAS(str_contains($formAgendamento, 'Retorno obrigatório A/S'), 'O formulario nao identifica o retorno obrigatorio.');

assertRetornoAS(str_contains($relatorio, 'Linha do tempo dos relatórios A/S'), 'A cadeia nao aparece na tela do relatorio.');
assertRetornoAS(str_contains($relatorio, 'Encaminhar para Retorno A/S'), 'O admin nao recebe a decisao impeditiva de A/S.');
assertRetornoAS(str_contains($relatorio, 'N&atilde;o aprovar e enviar para Retornos A/S'), 'A decisao impeditiva A/S nao esta explicita perto das outras decisoes.');
assertRetornoAS(str_contains($relatorio, 'checklist_sem_prazo_por_id['), 'O checkbox A/S nao e enviado diretamente pelo formulario.');
assertRetornoAS(str_contains($relatorio, 'COALESCE(r.sem_prazo, 0)'), 'A reabertura do relatorio nao recupera o marcador A/S persistido.');
assertRetornoAS(str_contains($certificados, 'relatorioNumerosReferenciaCertificado'), 'O wizard nao persiste original e cumprimento.');
assertRetornoAS(str_contains($dashboard, 'fluxo_retornos_as'), 'O dashboard nao exibe retornos aguardando agendamento.');
assertRetornoAS(str_contains($dashboardView, 'id="retornos-as"'), 'O dashboard nao possui destino direto para o retorno A/S.');
assertRetornoAS(str_contains($assinaturasActions, 'ele não foi aprovado'), 'A assinatura administrativa ainda informa aprovacao indevida quando ha A/S.');

echo "fluxo_retornos_as_test: OK\n";
