<?php

require_once __DIR__ . '/../includes/aprovacao_documentos.php';

function assertAprovacao(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

function assertAprovacaoFalha(callable $acao, string $trecho): void
{
    try {
        $acao();
    } catch (Throwable $e) {
        assertAprovacao(str_contains($e->getMessage(), $trecho), 'Mensagem inesperada: ' . $e->getMessage());
        return;
    }
    throw new RuntimeException('A validacao deveria ter rejeitado a operacao.');
}

$semExigencias = [
    'pendentes' => 0,
    'status_esperado' => 'APROVADA',
    'versao' => hash('sha256', 'sem-exigencias'),
];
$comExigencias = [
    'pendentes' => 2,
    'status_esperado' => 'APROVADA_COM_EXIGENCIAS',
    'versao' => hash('sha256', 'com-exigencias'),
];

aprovacaoRelatorioValidarResultado($semExigencias, 'APROVADA', $semExigencias['versao']);
aprovacaoRelatorioValidarResultado($comExigencias, 'APROVADA_COM_EXIGENCIAS', $comExigencias['versao']);

assertAprovacaoFalha(
    fn() => aprovacaoRelatorioValidarResultado($comExigencias, 'APROVADA', $comExigencias['versao']),
    'deve ser aprovado com exigencias'
);
assertAprovacaoFalha(
    fn() => aprovacaoRelatorioValidarResultado($semExigencias, 'APROVADA_COM_EXIGENCIAS', $semExigencias['versao']),
    'deve ser aprovado sem exigencias'
);
assertAprovacaoFalha(
    fn() => aprovacaoRelatorioValidarResultado($semExigencias, 'APROVADA', hash('sha256', 'alterado')),
    'foram alterados'
);

$actions = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
$relatorio = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$endpoint = file_get_contents(__DIR__ . '/../modules/documentos/aprovar.php');
$approvalDomain = file_get_contents(__DIR__ . '/../includes/aprovacao_documentos.php');
$approvalUi = file_get_contents(__DIR__ . '/../includes/aprovacao_ui.php');
$detalhe = file_get_contents(__DIR__ . '/../modules/vistorias/detalhe.php');
$certificados = file_get_contents(__DIR__ . '/../modules/documentacao/certificados/index.php');
$wizard = file_get_contents(__DIR__ . '/../modules/certificados/wizard.php');
$wizardStep2 = file_get_contents(__DIR__ . '/../modules/certificados/wizard_step2.php');
$funcoes = file_get_contents(__DIR__ . '/../includes/functions.php');

foreach ([$actions, $relatorio, $endpoint, $approvalDomain, $approvalUi, $detalhe, $certificados, $wizard, $wizardStep2, $funcoes] as $codigo) {
    assertAprovacao($codigo !== false, 'Nao foi possivel ler um dos arquivos do fluxo.');
}

assertAprovacao(str_contains($actions, "getCargo() !== 'ADMIN'"), 'A decisao administrativa nao esta restrita ao cargo principal ADMIN.');
assertAprovacao(str_contains($endpoint, "getCargo() !== 'ADMIN'"), 'O endpoint de assinatura nao esta restrito ao cargo principal ADMIN.');
assertAprovacao(str_contains($relatorio, 'value="APROVADA"'), 'A opcao Aprovada nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, 'value="APROVADA_COM_EXIGENCIAS"'), 'A opcao Aprovada com exigencias nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, 'name="versao_relatorio"'), 'A revisao nao envia a versao concorrente do relatorio.');
assertAprovacao(str_contains($relatorio, 'name="decisao" value="aprovar"'), 'A revisao nao oferece aprovacao direta do relatorio.');
assertAprovacao(str_contains($relatorio, 'Aprovar com exig&ecirc;ncias'), 'O botao contextual de aprovacao com exigencias nao aparece.');
assertAprovacao(!str_contains($relatorio, "renderBotaoAprovacaoDocumento(\$pdo,'RELATORIO'"), 'O relatorio ainda oferece assinatura eletronica.');
assertAprovacao(!str_contains($relatorio, 'renderAprovacaoUi'), 'O modal de assinatura ainda e carregado no relatorio.');
assertAprovacao(str_contains($actions, 'aprovacaoRelatorioValidarResultado'), 'A aprovacao direta nao valida status e versao no servidor.');
assertAprovacao(str_contains($actions, 'documentacao/novo_certificado?agendamento_id='), 'A aprovacao liberada nao segue para os certificados.');
assertAprovacao(str_contains($endpoint, "=== 'RELATORIO'"), 'O endpoint de assinatura ainda aceita novas aprovacoes de relatorios.');
assertAprovacao(str_contains($approvalDomain, 'Relatorios de vistoria nao exigem assinatura eletronica.'), 'O dominio de assinatura ainda aceita relatorios diretamente.');
assertAprovacao(str_contains($approvalUi, "\$tipo==='RELATORIO'?false"), 'A interface generica ainda habilita assinatura de relatorio.');
assertAprovacao(!str_contains($detalhe, "\$cargo === 'ANALISTA' && \$vistoria['status'] === 'AGUARDANDO_APROVACAO'"), 'O caminho legado ainda permite decisao pelo analista.');
assertAprovacao(str_contains($certificados, "renderBotaoAprovacaoDocumento(\$pdo,'CSN'"), 'A assinatura dos certificados foi removida indevidamente.');
assertAprovacao(str_contains($wizard, "\$bloquear_definitivo = (\$relatorio_status === 'APROVADA_COM_EXIGENCIAS')"), 'O wizard nao oculta Definitivo para relatorio com exigencias.');
assertAprovacao(substr_count($wizardStep2, "\$tipo === 'Definitivo' && \$dados_emb['relatorio_status'] === 'APROVADA_COM_EXIGENCIAS'") >= 3, 'O backend nao protege CSN, CNBL e CNARQ contra emissao definitiva.');
assertAprovacao(str_contains($funcoes, 'relatorioPossuiASPendente'), 'A regra central nao verifica exigencia A/S.');
assertAprovacao(str_contains($funcoes, 'Certificacao bloqueada por exigencia A/S'), 'A regra central nao bloqueia certificacao por A/S.');

echo "Testes do fluxo de aprovacao de relatorios concluidos com sucesso.\n";
