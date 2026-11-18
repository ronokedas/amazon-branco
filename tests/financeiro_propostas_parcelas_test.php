<?php
require_once __DIR__ . '/../includes/financeiro_propostas.php';

function assertFinanceiroPropostaParcela(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

function somarAgendaEmCentavos(array $agenda): int
{
    $total = 0;
    foreach ($agenda as $item) {
        $total += (int)round((float)$item['valor'] * 100);
    }
    return $total;
}

// 11 parcelas sem entrada: cada uma vence em múltiplos de 30 dias.
$agenda11 = financeiroMontarAgendaParcelasProposta(11000.00, 0.00, 11, '2026-11-18');
assertFinanceiroPropostaParcela(count($agenda11) === 11, 'A proposta de 11x deve gerar exatamente 11 lançamentos.');
assertFinanceiroPropostaParcela(somarAgendaEmCentavos($agenda11) === 1100000, 'A soma das 11 parcelas deve fechar R$ 11.000,00.');
foreach ($agenda11 as $i => $item) {
    $numero = $i + 1;
    $dataEsperada = (new DateTimeImmutable('2026-11-18'))->modify('+' . ($numero * 30) . ' days')->format('Y-m-d');
    assertFinanceiroPropostaParcela($item['tipo'] === 'PARCELA', 'Todos os lançamentos sem entrada devem ser parcelas.');
    assertFinanceiroPropostaParcela((int)$item['numero_parcela'] === $numero && (int)$item['total_parcelas'] === 11, 'A numeração da parcela deve ser sequencial (1/11 a 11/11).');
    assertFinanceiroPropostaParcela($item['data_vencimento'] === $dataEsperada, 'O vencimento deve ocorrer a cada 30 dias após a assinatura.');
    assertFinanceiroPropostaParcela($item['status'] === 'PENDENTE', 'As parcelas futuras devem iniciar pendentes.');
}

// Entrada e 24 parcelas: a entrada é registrada separadamente e o saldo é dividido em centavos.
$agenda24 = financeiroMontarAgendaParcelasProposta(10000.01, 1000.00, 24, '2026-11-18');
assertFinanceiroPropostaParcela(count($agenda24) === 25, 'Com entrada, devem existir 1 lançamento de entrada e 24 parcelas.');
assertFinanceiroPropostaParcela($agenda24[0]['tipo'] === 'ENTRADA' && (int)$agenda24[0]['numero_parcela'] === 0, 'A entrada deve ser identificada pelo número zero.');
assertFinanceiroPropostaParcela($agenda24[0]['status'] === 'PAGO' && (float)$agenda24[0]['valor'] === 1000.00, 'A entrada deve nascer quitada pelo valor recebido.');
assertFinanceiroPropostaParcela(somarAgendaEmCentavos($agenda24) === 1000001, 'Entrada mais parcelas devem fechar R$ 10.000,01.');
$parcelas24 = array_values(array_filter($agenda24, static fn(array $item): bool => $item['tipo'] === 'PARCELA'));
assertFinanceiroPropostaParcela(count($parcelas24) === 24, 'A agenda deve conter as 24 parcelas contratadas.');
assertFinanceiroPropostaParcela((float)$parcelas24[0]['valor'] === 375.01, 'O primeiro centavo excedente deve ser distribuído sem alterar o total.');
assertFinanceiroPropostaParcela((float)$parcelas24[23]['valor'] === 375.00, 'A última parcela deve manter valor positivo e correto.');

// Valores pequenos não podem gerar parcelas negativas por arredondamento.
$agendaCentavos = financeiroMontarAgendaParcelasProposta(0.13, 0.00, 8, '2026-11-18');
assertFinanceiroPropostaParcela(count($agendaCentavos) === 8, 'O caso de centavos deve manter a quantidade de parcelas.');
assertFinanceiroPropostaParcela(somarAgendaEmCentavos($agendaCentavos) === 13, 'O caso de centavos deve preservar o valor total.');
foreach ($agendaCentavos as $item) {
    assertFinanceiroPropostaParcela((int)round((float)$item['valor'] * 100) > 0, 'Nenhuma parcela pode ficar negativa ou sem valor quando há centavos suficientes.');
}

// O limite do sistema permanece em 24 parcelas.
$agendaLimite = financeiroMontarAgendaParcelasProposta(24.00, 0.00, 25, '2026-11-18');
assertFinanceiroPropostaParcela(count($agendaLimite) === 24, 'A agenda não pode ultrapassar 24 parcelas.');

// Confere a integração entre assinatura digital, autorização interna e listagem financeira.
$raiz = dirname(__DIR__);
$actionsPropostas = file_get_contents($raiz . '/modules/comercial/propostas/actions.php');
$assinaturaProposta = file_get_contents($raiz . '/modules/comercial/propostas/assinar.php');
$financeiroIndex = file_get_contents($raiz . '/modules/financeiro/index.php');
$pdfProposta = file_get_contents($raiz . '/modules/comercial/pdf.php');
$migration = file_get_contents($raiz . '/migrations/137_financeiro_parcelas_propostas.sql');
assertFinanceiroPropostaParcela(
    is_string($actionsPropostas)
    && str_contains($actionsPropostas, 'gerarLancamentosFinanceirosProposta($pdo, $prop, $criado_por, $manual, $dataReferencia)'),
    'A aprovação interna deve chamar o gerador comum de parcelas.'
);
assertFinanceiroPropostaParcela(
    is_string($assinaturaProposta) && str_contains($assinaturaProposta, 'gerarLancamentosFinanceirosProposta('),
    'A assinatura digital deve chamar o gerador comum de parcelas.'
);
assertFinanceiroPropostaParcela(
    is_string($financeiroIndex)
    && str_contains($financeiroIndex, 'numero_parcela, total_parcelas')
    && str_contains($financeiroIndex, 'data-label="Parcela"'),
    'A listagem financeira deve exibir a identificação das parcelas.'
);
assertFinanceiroPropostaParcela(
    is_string($pdfProposta)
    && str_contains($pdfProposta, '$valorBaseCentavos = intdiv($saldoParceladoCentavos, $parcelas);'),
    'O PDF deve distribuir os centavos pelo mesmo critério da agenda financeira.'
);
assertFinanceiroPropostaParcela(
    is_string($migration)
    && str_contains($migration, 'numero_parcela')
    && str_contains($migration, 'total_parcelas')
    && str_contains($migration, 'uk_financeiro_proposta_parcela'),
    'A migration deve persistir a numeração das parcelas com proteção contra duplicidade.'
);

echo "financeiro_propostas_parcelas_test: OK\n";
