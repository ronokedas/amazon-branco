<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function assertAnalisePlanos(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

assertAnalisePlanos(analisePlanosTiposPermitidos() === ['LC', 'LCEC', 'LA', 'LR'], 'Os quatro processos permitidos foram alterados.');
assertAnalisePlanos(!in_array('OUTRO', analisePlanosTiposPermitidos(), true), 'OUTRO ainda é aceito pela regra de domínio.');
assertAnalisePlanos(analisePlanosNormasPermitidas() === ['NORMAM-202'], 'Somente NORMAM-202 deve ser aceita em processos novos.');
assertAnalisePlanos(analisePlanosChecklist('LC', 'NORMAM-201', 'EC1') === [], 'Checklist novo aceitou NORMAM-201.');
assertAnalisePlanos(analisePlanosChecklist('LA', 'NORMAM-202', 'EC2') !== [], 'Checklist LA/EC2 não foi criado.');
assertAnalisePlanos(analisePlanosChecklist('OUTRO', 'NORMAM-202', 'EC1') === [], 'Checklist aceitou processo OUTRO.');

$ec1 = analisePlanosAvaliarAplicabilidade([
    'enquadramento' => 'NORMAM-202', 'tipo_processo' => 'LC', 'classe_certificacao' => 'EC1',
]);
assertAnalisePlanos($ec1['permitido'] === true, 'Documento EC1 aplicável foi bloqueado.');
$ec2 = analisePlanosAvaliarAplicabilidade([
    'enquadramento' => 'NORMAM-202', 'tipo_processo' => 'LC', 'classe_certificacao' => 'EC2',
    'embarcacao_tipo' => 'CARGA', 'arqueacao_bruta' => 30,
]);
assertAnalisePlanos($ec2['permitido'] === false, 'EC2 comum recebeu licença indevidamente.');
$rebocadorFuturo = analisePlanosAvaliarAplicabilidade([
    'enquadramento' => 'NORMAM-202', 'tipo_processo' => 'LC', 'classe_certificacao' => 'EC2',
    'embarcacao_tipo' => 'REBOCADOR', 'arqueacao_bruta' => 30,
], new DateTimeImmutable('2026-11-01', new DateTimeZone('America/Sao_Paulo')));
assertAnalisePlanos($rebocadorFuturo['permitido'] === true, 'Exceção futura de rebocador/empurrador não foi aplicada.');
assertAnalisePlanos(analisePlanosTransicaoPermitida('AGUARDANDO_AGENDAMENTO', 'AGENDADA'), 'Agendamento inicial não permitido.');
assertAnalisePlanos(analisePlanosTransicaoPermitida('AGUARDANDO_DOCUMENTOS', 'EM_ANALISE'), 'Retorno documental não permitido.');
assertAnalisePlanos(!analisePlanosTransicaoPermitida('CONCLUIDA', 'EM_ANALISE'), 'Processo concluído pode ser reaberto indevidamente.');

$colunas = $pdo->query("SELECT COLUMN_NAME,COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='analises_planos'
      AND COLUMN_NAME IN ('tipo_processo','enquadramento','proposta_id','servico_id','vendedor_origem_id','prazo_agendado_em')")
    ->fetchAll(PDO::FETCH_KEY_PAIR);
assertAnalisePlanos(count($colunas) === 6, 'Migração do domínio de análise não está completa.');
assertAnalisePlanos(!str_contains((string)$colunas['tipo_processo'], 'OUTRO'), 'Banco ainda aceita OUTRO em tipo_processo.');

$tabelas = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('analise_planos_agenda_historico','notificacoes')")
    ->fetchAll(PDO::FETCH_COLUMN);
assertAnalisePlanos(count($tabelas) === 2, 'Agenda documental ou notificações não foram migradas.');

$licenca = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='certificados_lc' AND COLUMN_NAME='analise_id'")->fetchColumn();
assertAnalisePlanos((int)$licenca === 1, 'Licença não possui vínculo explícito com a análise.');

$form = file_get_contents(__DIR__ . '/../modules/analises_planos/form.php');
assertAnalisePlanos(!str_contains($form, '<option value="OUTRO"'), 'OUTRO continua visível no formulário.');

$proposta = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
assertAnalisePlanos(str_contains($proposta, 'analisePlanosCriarDemandasProposta'), 'Assinatura comercial não cria demanda de análise.');
assertAnalisePlanos(str_contains($proposta, 'ANALISE_PLANOS_EC1') && str_contains($proposta, 'ANALISE_PLANOS_EC2'), 'Serviços de análise não usam códigos estáveis.');

$vistoria = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
assertAnalisePlanos(str_contains($vistoria, 'analista_id=:usuario'), 'Consulta de vistoria do analista não está limitada à atribuição.');

echo "analise_planos_fluxo_test: OK\n";
