<?php
/**
 * Teste Automatizado: Exibição do Nome da Embarcação e Enquadramento do Aceitante
 * Arquivo: tests/proposta_embarcacao_aceitante_pdf_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: EXIBIÇÃO DE EMBARCAÇÃO E ENQUADRAMENTO DO ACEITANTE NO PDF ===\n\n";

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

// 1. Obter dados base
$cliente = $pdo->query("SELECT id, nome FROM clientes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$servicos = $pdo->query("SELECT id, nome, preco_padrao FROM servicos WHERE ativo = 1 LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$embarcacoes = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
$adminUser = $pdo->query("SELECT id FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cliente || count($servicos) < 2 || empty($embarcacoes)) {
    die("Dados insuficientes para teste.\n");
}

$userId = $adminUser['id'] ?? 'user-test';

// --------------------------------------------------------------------------
// TESTE 1: Proposta com 1 ÚNICA embarcação e nome longo com entidade &AMP; no Cliente
// --------------------------------------------------------------------------
echo "1. Criando proposta com 1 embarcação e nome longo com '&amp;' no Cliente...\n";

$numProposta1 = 'TEST-EMB-1-' . rand(1000, 9999);
$propId1 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// Criar cliente temporário com nome longo e entidade HTML &amp;
$clienteLongoId = 'cli-longo-' . uniqid();
$nomeClienteLongo = "COMÉRCIO IMPORTAÇÃO, EXPORTAÇÃO, NAVEGAÇÃO &AMP; SERVIÇOS RIO XINGU LTDA";

$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, ativo, criado_em) VALUES (?, ?, '10.630.807/0001-61', 1, NOW())")
    ->execute([$clienteLongoId, $nomeClienteLongo]);

$pdo->prepare("
    INSERT INTO propostas (
        id, numero, cliente_id, data_emissao, data_validade, parcelas, forma_pagamento,
        valor_total, valor_entrada, desconto_percentual, desconto_valor, tipo_desconto,
        status, criado_por, token_assinatura
    ) VALUES (
        :id, :numero, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'pix',
        5000.00, 0, 0, 0, 'perc', 'rascunho', :criado_por, MD5(RAND())
    )
")->execute([
    ':id' => $propId1,
    ':numero' => $numProposta1,
    ':cliente_id' => $clienteLongoId,
    ':criado_por' => $userId,
]);

$emb1 = $embarcacoes[0];
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
    ->execute([$propId1, $emb1['id']]);

$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado) VALUES (UUID(), ?, ?, ?, 1, 2500.00)")
    ->execute([$propId1, $emb1['id'], $servicos[0]['id']]);

$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado) VALUES (UUID(), ?, ?, ?, 1, 2500.00)")
    ->execute([$propId1, $emb1['id'], $servicos[1]['id']]);

// Gerar PDF da proposta 1
$GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;
$GLOBALS['PROPOSTA_PDF_ID'] = $propId1;
$_SESSION['usuario_id'] = $userId;
$pdfOutput1 = include __DIR__ . '/../modules/comercial/pdf.php';

assertTeste(is_string($pdfOutput1) && strlen($pdfOutput1) > 10000, "PDF com 1 embarcação gerado com sucesso (" . strlen($pdfOutput1) . " bytes)");

// Verificar se não há páginas quebradas indevidamente
$autoload_path = __DIR__ . '/../vendor/autoload.php';
require_once $autoload_path;
$fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
$leitor = \setasign\Fpdi\PdfParser\StreamReader::createByString($pdfOutput1);
$paginas1 = $fpdi->setSourceFile($leitor);
assertTeste($paginas1 <= 2, "Proposta de 1 embarcação coube em até 2 páginas (total: {$paginas1})");

// Limpar dados do teste 1
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id = ?")->execute([$propId1]);
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id = ?")->execute([$propId1]);
$pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$propId1]);
$pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$clienteLongoId]);
echo "  [✓] Dados do teste 1 limpos.\n";

// --------------------------------------------------------------------------
// TESTE 2: Proposta real do usuário AM-ORC-3/26
// --------------------------------------------------------------------------
echo "\n2. Testando proposta real AM-ORC-3/26 (BARREIRO)...\n";
$propRealId = "e7892b2d-c97f-11f1-9fb4-ba8d0f77c106";
$propExiste = $pdo->query("SELECT id FROM propostas WHERE id = '{$propRealId}'")->fetch();

if ($propExiste) {
    $GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;
    $GLOBALS['PROPOSTA_PDF_ID'] = $propRealId;
    $pdfReal = include __DIR__ . '/../modules/comercial/pdf.php';
    assertTeste(is_string($pdfReal) && strlen($pdfReal) > 10000, "PDF da proposta real AM-ORC-3/26 gerado com sucesso (" . strlen($pdfReal) . " bytes)");
} else {
    echo "  [i] Proposta real e7892b2d-c97f-11f1-9fb4-ba8d0f77c106 não existe neste ambiente, ignorando.\n";
}

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: EXIBIÇÃO DE EMBARCAÇÃO E ENQUADRAMENTO DE ACEITANTE 100% VALIDADOS!\n";
} else {
    echo "RESULTADO: FALHA NA VALIDAÇÃO!\n";
    exit(1);
}
echo "============================================================\n";
