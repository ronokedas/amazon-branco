<?php

function assertPropostaEdicao(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

$wizard = file_get_contents(__DIR__ . '/../modules/comercial/nova.php');
foreach (glob(__DIR__ . '/../modules/comercial/components/*.php') as $comp) {
    $wizard .= "\n" . file_get_contents($comp);
}
if (file_exists(__DIR__ . '/../modules/comercial/js/proposta_wizard.js')) {
    $wizard .= "\n" . file_get_contents(__DIR__ . '/../modules/comercial/js/proposta_wizard.js');
}
$actions = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
$comercial = file_get_contents(__DIR__ . '/../modules/comercial/index.php');
$detalhes = file_get_contents(__DIR__ . '/../modules/comercial/propostas/index.php');
$componenteParcelas = file_get_contents(__DIR__ . '/../modules/comercial/components/proposta_embarcacoes_servicos.php');
$jsParcelas = file_get_contents(__DIR__ . '/../modules/comercial/js/proposta_wizard.js');
$pdfProposta = file_get_contents(__DIR__ . '/../modules/comercial/pdf.php');

assertPropostaEdicao($wizard !== false, 'Wizard de propostas não encontrado.');
assertPropostaEdicao($actions !== false, 'Actions de propostas não encontrado.');

assertPropostaEdicao(
    str_contains($wizard, 'id="descontoGlobalDisplay"')
    && str_contains($wizard, 'type="hidden" id="descontoGlobal" name="desconto_global"'),
    'O desconto não separa o campo formatado do valor normalizado.'
);
assertPropostaEdicao(
    str_contains($wizard, 'id="valorEntradaDisplay"')
    && str_contains($wizard, 'type="hidden" id="valorEntrada" name="valor_entrada"'),
    'A entrada não separa o campo formatado do valor normalizado.'
);
assertPropostaEdicao(
    str_contains($wizard, 'valor >= 100')
    && str_contains($wizard, 'O desconto percentual deve ser menor que 100%'),
    'A interface não bloqueia desconto percentual de 100% ou mais.'
);
assertPropostaEdicao(
    str_contains($actions, "case 'atualizar':")
    && str_contains($actions, "status = 'rascunho' AND assinado = 0")
    && str_contains($actions, 'FOR UPDATE'),
    'A atualização não protege o rascunho contra status/assinatura e concorrência.'
);
assertPropostaEdicao(
    str_contains($actions, "if (\$tipo === 'perc' && \$valor >= 100)")
    && str_contains($actions, 'validarDescontoProposta($tipoDesconto, $descontoInput)'),
    'O servidor não rejeita desconto percentual de 100% ou mais.'
);
assertPropostaEdicao(
    str_contains($actions, '$precosAntigos[$chave]')
    && str_contains($actions, 'array_key_exists($chave, $precosAntigos)'),
    'A edição não preserva os preços aplicados aos serviços existentes.'
);
assertPropostaEdicao(
    str_contains($comercial, 'comercial/nova?id=')
    && str_contains($detalhes, 'comercial/nova?id='),
    'As telas de propostas não expõem a edição do rascunho.'
);
assertPropostaEdicao(
    str_contains($componenteParcelas, 'for ($i = 1; $i <= 24; $i++)'),
    'O seletor de parcelas não disponibiliza todas as opções de 1x até 24x.'
);
assertPropostaEdicao(
    substr_count($actions, 'min(24, (int)($_POST[') === 2,
    'A criação e a edição não aplicam o limite de 24 parcelas no servidor.'
);
assertPropostaEdicao(
    substr_count($jsParcelas, 'Math.min(24, parseInt(document.getElementById') >= 2
    && substr_count($jsParcelas, 'const valorBaseCentavos = Math.floor(saldoParceladoCentavos / parcelas);') === 2,
    'A prévia financeira não limita a 24 parcelas ou não distribui os centavos sem perda.'
);
assertPropostaEdicao(
    str_contains($pdfProposta, 'for ($i = 1; $i <= $parcelas; $i++)')
    && str_contains($pdfProposta, '$valorBaseCentavos = intdiv($saldoParceladoCentavos, $parcelas);'),
    'O PDF não gera todas as parcelas ou não distribui o saldo exato em centavos.'
);

foreach ([
    ['saldo' => 100.00, 'parcelas' => 11],
    ['saldo' => 1000.01, 'parcelas' => 24],
    ['saldo' => 999.99, 'parcelas' => 23],
    ['saldo' => 0.13, 'parcelas' => 8],
] as $cenario) {
    $saldoCentavos = (int)round($cenario['saldo'] * 100);
    $baseCentavos = intdiv($saldoCentavos, $cenario['parcelas']);
    $extras = $saldoCentavos % $cenario['parcelas'];
    $somaCentavos = 0;
    $menorParcela = PHP_INT_MAX;
    $maiorParcela = 0;
    for ($i = 1; $i <= $cenario['parcelas']; $i++) {
        $valorCentavos = $baseCentavos + ($i <= $extras ? 1 : 0);
        $somaCentavos += $valorCentavos;
        $menorParcela = min($menorParcela, $valorCentavos);
        $maiorParcela = max($maiorParcela, $valorCentavos);
    }
    assertPropostaEdicao(
        $somaCentavos === $saldoCentavos && ($maiorParcela - $menorParcela) <= 1,
        "O cálculo de {$cenario['parcelas']} parcelas não distribui o saldo exato em centavos."
    );
}

echo "propostas_edicao_validacao_test: OK\n";
