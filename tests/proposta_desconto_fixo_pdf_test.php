<?php
/**
 * Teste Automatizado: Desconto Fixo (R$) vs Percentual (%) na Proposta e no PDF
 * Arquivo: tests/proposta_desconto_fixo_pdf_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: DESCONTO FIXO (R$) NA PROPOSTA E NO PDF ===\n\n";

$passou = true;

function assertTeste(bool $condicao, string $mensagem): void {
    global $passou;
    if ($condicao) {
        echo "  [✓] {$mensagem}\n";
    } else {
        echo "  [✗] FALHA: {$mensagem}\n";
        $passou = false;
    }
}

// 1. Obter cliente e serviço para os testes
$cliente = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    die("Nenhum cliente ativo encontrado.\n");
}
$clienteId = $cliente['id'];

$servico = $pdo->query("SELECT id, nome, preco_padrao FROM servicos WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$servico) {
    die("Nenhum serviço ativo encontrado.\n");
}
$servicoId = $servico['id'];

$embarcacao = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$embarcacaoId = $embarcacao['id'] ?? null;

$adminUser = $pdo->query("SELECT id FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$userId = $adminUser['id'] ?? 'user-test';

// --------------------------------------------------------------------------
// TESTE 1: Salvar proposta com Desconto em Valor Fixo (R$ 350,00)
// --------------------------------------------------------------------------
echo "1. Criando proposta com Desconto de Valor Fixo (R$ 350,00)...\n";

$numPropostaFixo = 'TEST-DESC-FIXO-' . rand(1000, 9999);
$subtotal = 5000.00;
$descontoFixo = 350.00;
$descontoPercentualCalc = round(($descontoFixo / $subtotal) * 100, 2); // 7.00%
$valorTotal = $subtotal - $descontoFixo; // 4650.00

$propIdFixo = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$stmtInsert = $pdo->prepare("
    INSERT INTO propostas (
        id, numero, cliente_id, data_emissao, data_validade, parcelas, forma_pagamento,
        valor_total, valor_entrada, desconto_percentual, desconto_valor, tipo_desconto,
        status, criado_por, token_assinatura
    ) VALUES (
        :id, :numero, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'a_vista',
        :valor_total, 0, :desconto_percentual, :desconto_valor, 'valor',
        'rascunho', :criado_por, MD5(RAND())
    )
");
$stmtInsert->execute([
    ':id' => $propIdFixo,
    ':numero' => $numPropostaFixo,
    ':cliente_id' => $clienteId,
    ':valor_total' => $valorTotal,
    ':desconto_percentual' => $descontoPercentualCalc,
    ':desconto_valor' => $descontoFixo,
    ':criado_por' => $userId,
]);

// Adicionar serviço
$pdo->prepare("
    INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado)
    VALUES (UUID(), :pid, :eid, :sid, 1, :preco)
")->execute([
    ':pid' => $propIdFixo,
    ':eid' => $embarcacaoId,
    ':sid' => $servicoId,
    ':preco' => $subtotal,
]);

if ($embarcacaoId) {
    $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
        ->execute([$propIdFixo, $embarcacaoId]);
}

// Verificar banco
$stmtVerif = $pdo->prepare("SELECT tipo_desconto, desconto_valor, desconto_percentual FROM propostas WHERE id = ?");
$stmtVerif->execute([$propIdFixo]);
$salvoFixo = $stmtVerif->fetch(PDO::FETCH_ASSOC);

assertTeste($salvoFixo['tipo_desconto'] === 'valor', "Coluna tipo_desconto gravou 'valor'");
assertTeste((float)$salvoFixo['desconto_valor'] === 350.00, "Coluna desconto_valor gravou 350.00");

// 2. Renderizar PDF e validar que NÃO exibe porcentagem no label de desconto
echo "\n2. Gerando PDF da proposta com desconto em valor fixo...\n";
$_SESSION['usuario_id'] = $userId;
$_SESSION['usuario_nivel'] = 'administrador';
$_GET['id'] = $propIdFixo;
$GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;
$GLOBALS['PROPOSTA_PDF_ID'] = $propIdFixo;

try {
    $pdfOutputFixo = include __DIR__ . '/../modules/comercial/pdf.php';
    $pdfGerouFixo = is_string($pdfOutputFixo) && strlen($pdfOutputFixo) > 1000;
} catch (Throwable $e) {
    $pdfGerouFixo = false;
    echo "  [✗] Erro na geração do PDF: " . $e->getMessage() . "\n";
}

assertTeste($pdfGerouFixo, "PDF gerado com sucesso para desconto em valor fixo (" . strlen((string)$pdfOutputFixo) . " bytes)");
assertTeste(($GLOBALS['ULTIMO_LABEL_DESCONTO'] ?? '') === 'DESCONTO', "Label do desconto no PDF é 'DESCONTO' (sem porcentagem)");
assertTeste((float)($GLOBALS['ULTIMO_VALOR_DESCONTO'] ?? 0) === 350.00, "Valor do desconto no PDF é exatamente R$ 350,00");

// --------------------------------------------------------------------------
// TESTE 2: Salvar proposta com Desconto em Porcentagem (15%)
// --------------------------------------------------------------------------
echo "\n3. Criando proposta com Desconto em Porcentagem (15%)...\n";

$numPropostaPerc = 'TEST-DESC-PERC-' . rand(1000, 9999);
$descontoPerc = 15.00;
$descontoValorPerc = round($subtotal * 0.15, 2); // 750.00
$valorTotalPerc = $subtotal - $descontoValorPerc; // 4250.00

$propIdPerc = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$stmtInsert = $pdo->prepare("
    INSERT INTO propostas (
        id, numero, cliente_id, data_emissao, data_validade, parcelas, forma_pagamento,
        valor_total, valor_entrada, desconto_percentual, desconto_valor, tipo_desconto,
        status, criado_por, token_assinatura
    ) VALUES (
        :id, :numero, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'a_vista',
        :valor_total, 0, :desconto_percentual, :desconto_valor, 'perc',
        'rascunho', :criado_por, MD5(RAND())
    )
");
$stmtInsert->execute([
    ':id' => $propIdPerc,
    ':numero' => $numPropostaPerc,
    ':cliente_id' => $clienteId,
    ':valor_total' => $valorTotalPerc,
    ':desconto_percentual' => $descontoPerc,
    ':desconto_valor' => $descontoValorPerc,
    ':criado_por' => $userId,
]);
$pdo->prepare("UPDATE propostas SET tipo_desconto = 'perc' WHERE id = ?")->execute([$propIdPerc]);

// Adicionar serviço
$pdo->prepare("
    INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado)
    VALUES (UUID(), :pid, :eid, :sid, 1, :preco)
")->execute([
    ':pid' => $propIdPerc,
    ':eid' => $embarcacaoId,
    ':sid' => $servicoId,
    ':preco' => $subtotal,
]);

// 4. Renderizar PDF com porcentagem
echo "\n4. Gerando PDF da proposta com desconto percentual...\n";
$_GET['id'] = $propIdPerc;
$GLOBALS['PROPOSTA_PDF_ID'] = $propIdPerc;

try {
    $pdfOutputPerc = include __DIR__ . '/../modules/comercial/pdf.php';
    $pdfGerouPerc = is_string($pdfOutputPerc) && strlen($pdfOutputPerc) > 1000;
} catch (Throwable $e) {
    $pdfGerouPerc = false;
    echo "  [✗] Erro na geração do PDF: " . $e->getMessage() . "\n";
}

assertTeste($pdfGerouPerc, "PDF gerado com sucesso para desconto percentual (" . strlen((string)$pdfOutputPerc) . " bytes)");
assertTeste(($GLOBALS['ULTIMO_LABEL_DESCONTO'] ?? '') === 'DESCONTO (15,00%)', "Label do desconto no PDF é 'DESCONTO (15,00%)'");
assertTeste((float)($GLOBALS['ULTIMO_VALOR_DESCONTO'] ?? 0) === 750.00, "Valor do desconto percentual no PDF é exatamente R$ 750,00");

// Limpar propostas de teste
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id IN (?, ?)")->execute([$propIdFixo, $propIdPerc]);
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id IN (?, ?)")->execute([$propIdFixo, $propIdPerc]);
$pdo->prepare("DELETE FROM propostas WHERE id IN (?, ?)")->execute([$propIdFixo, $propIdPerc]);
echo "  [✓] Dados de teste limpos com sucesso.\n";

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: SUPORTE A VALOR FIXO DE DESCONTO VALIDADO COM 100% DE SUCESSO!\n";
} else {
    echo "RESULTADO: FALHA EM UMA OU MAIS VALIDAÇÕES!\n";
    exit(1);
}
echo "============================================================\n";
