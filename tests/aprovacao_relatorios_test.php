<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/aprovacao_documentos.php';
require_once __DIR__ . '/../includes/functions.php';

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
    'pendentes_as' => 0,
    'versao' => hash('sha256', 'com-exigencias'),
];
$comAs = [
    'pendentes' => 1,
    'pendentes_as' => 1,
    'status_esperado' => 'RETORNO_AS',
    'versao' => hash('sha256', 'com-as'),
];
$semExigencias['pendentes_as'] = 0;

aprovacaoRelatorioValidarResultado($semExigencias, 'APROVADA', $semExigencias['versao']);
aprovacaoRelatorioValidarResultado($comExigencias, 'APROVADA_COM_EXIGENCIAS', $comExigencias['versao']);
aprovacaoRelatorioValidarResultado($comAs, 'RETORNO_AS', $comAs['versao']);

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
assertAprovacaoFalha(
    fn() => aprovacaoRelatorioValidarResultado($comAs, 'APROVADA_COM_EXIGENCIAS', $comAs['versao']),
    'deve ser encaminhado para Retorno A/S'
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
assertAprovacao(str_contains($relatorio, "'APROVADA_COM_EXIGENCIAS'"), 'A opcao Aprovada com exigencias nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, "'RETORNO_AS'"), 'A opcao impeditiva de Retorno A/S nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, 'name="versao_relatorio"'), 'A revisao nao envia a versao concorrente do relatorio.');
assertAprovacao(str_contains($relatorio, "name=\"decisao\"") && str_contains($relatorio, "'aprovar'"), 'A revisao nao oferece aprovacao direta do relatorio.');
assertAprovacao(str_contains($relatorio, 'Aprovar com exig&ecirc;ncias'), 'O botao contextual de aprovacao com exigencias nao aparece.');
assertAprovacao(str_contains($relatorio, 'Encaminhar para Retorno A/S'), 'O botao impeditivo de Retorno A/S nao aparece.');
assertAprovacao(!str_contains($relatorio, "renderBotaoAprovacaoDocumento(\$pdo,'RELATORIO'"), 'O relatorio ainda oferece assinatura eletronica.');
assertAprovacao(!str_contains($relatorio, 'renderAprovacaoUi'), 'O modal de assinatura ainda e carregado no relatorio.');
assertAprovacao(str_contains($actions, 'aprovacaoRelatorioValidarResultado'), 'A aprovacao direta nao valida status e versao no servidor.');
assertAprovacao(str_contains($actions, "assinatura_status'] ?? 'PENDENTE') !== 'ASSINADO'"), 'A aprovacao administrativa nao exige assinatura previa do relatorio.');
assertAprovacao(str_contains($actions, "UPDATE documento_assinaturas SET status='CANCELADO'"), 'A devolucao nao cancela a assinatura anterior do relatorio.');
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

$baseEditavel = ['status'=>'PENDENTE','assinatura_status'=>'PENDENTE','vistoriador_id'=>'vistoriador-teste'];
assertAprovacao(avaliarEdicaoRelatorio($pdo, $baseEditavel, 'vistoriador-teste', 'VISTORIADOR')['permitido'], 'Relatorio pendente deveria permanecer editavel pelo vistoriador atribuido.');
$aguardandoEditavel = array_merge($baseEditavel, ['status'=>'AGUARDANDO_APROVACAO']);
assertAprovacao(avaliarEdicaoRelatorio($pdo, $aguardandoEditavel, 'vistoriador-teste', 'VISTORIADOR')['permitido'], 'Aguardando aprovacao sem assinatura deveria permanecer editavel.');
assertAprovacao(!avaliarEdicaoRelatorio($pdo, array_merge($aguardandoEditavel, ['assinatura_status'=>'ASSINADO']), 'vistoriador-teste', 'VISTORIADOR')['permitido'], 'Relatorio assinado nao pode permanecer editavel.');
foreach (['APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA','CANCELADA'] as $statusFinal) {
    assertAprovacao(!avaliarEdicaoRelatorio($pdo, array_merge($baseEditavel, ['status'=>$statusFinal]), 'vistoriador-teste', 'VISTORIADOR')['permitido'], "Status final {$statusFinal} nao foi congelado.");
}
assertAprovacao(!avaliarEdicaoRelatorio($pdo, $aguardandoEditavel, 'admin-teste', 'ADMIN')['permitido'], 'Admin nao pode editar o conteudo do relatorio.');
assertAprovacao(!avaliarEdicaoRelatorio($pdo, $aguardandoEditavel, 'analista-teste', 'ANALISTA')['permitido'], 'Analista nao pode editar o conteudo do relatorio.');
assertAprovacao(str_contains($relatorio, 'Visualizar PDF do relat'), 'O PDF nao esta exposto para relatorio persistido.');
assertAprovacao(str_contains($relatorio, 'Assinar como substituto'), 'A revisao admin nao oferece assinatura substituta para relatorio sem assinatura.');
assertAprovacao(str_contains($actions, "getCargo() !== 'VISTORIADOR'"), 'O endpoint de salvamento nao restringe alteracoes ao vistoriador.');

echo "Testes do fluxo de aprovacao de relatorios concluidos com sucesso.\n";
