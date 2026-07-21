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
$wizard = file_get_contents(__DIR__ . '/../modules/certificados/wizard.php');
$wizardStep2 = file_get_contents(__DIR__ . '/../modules/certificados/wizard_step2.php');
$funcoes = file_get_contents(__DIR__ . '/../includes/functions.php');

foreach ([$actions, $relatorio, $endpoint, $wizard, $wizardStep2, $funcoes] as $codigo) {
    assertAprovacao($codigo !== false, 'Nao foi possivel ler um dos arquivos do fluxo.');
}

assertAprovacao(str_contains($actions, "getCargo() !== 'ADMIN'"), 'A decisao administrativa nao esta restrita ao cargo principal ADMIN.');
assertAprovacao(str_contains($endpoint, "getCargo() !== 'ADMIN'"), 'O endpoint de assinatura nao esta restrito ao cargo principal ADMIN.');
assertAprovacao(str_contains($relatorio, 'value="APROVADA"'), 'A opcao Aprovada nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, 'value="APROVADA_COM_EXIGENCIAS"'), 'A opcao Aprovada com exigencias nao aparece na revisao.');
assertAprovacao(str_contains($relatorio, 'name="versao_relatorio"'), 'A revisao nao envia a versao concorrente do relatorio.');
assertAprovacao(str_contains($wizard, "\$bloquear_definitivo = (\$relatorio_status === 'APROVADA_COM_EXIGENCIAS')"), 'O wizard nao oculta Definitivo para relatorio com exigencias.');
assertAprovacao(substr_count($wizardStep2, "\$tipo === 'Definitivo' && \$dados_emb['relatorio_status'] === 'APROVADA_COM_EXIGENCIAS'") >= 3, 'O backend nao protege CSN, CNBL e CNARQ contra emissao definitiva.');
assertAprovacao(str_contains($funcoes, 'relatorioPossuiASPendente'), 'A regra central nao verifica exigencia A/S.');
assertAprovacao(str_contains($funcoes, 'Certificacao bloqueada por exigencia A/S'), 'A regra central nao bloqueia certificacao por A/S.');

echo "Testes do fluxo de aprovacao de relatorios concluidos com sucesso.\n";
