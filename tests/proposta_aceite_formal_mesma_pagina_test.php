<?php
/**
 * Teste Automatizado: Validação de 8.0 Aceite Formal, Assinaturas e QR Code na mesma página
 * Garante que nenhuma página órfã seja criada para o QR Code e que o bloco permaneça indivisível.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: ACEITE FORMAL, QUADROS E QR CODE NA MESMA PÁGINA (SEM PÁGINA ÓRFÃ) ===\n\n";

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
$cliente = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$servicos = $pdo->query("SELECT id, nome, preco_padrao FROM servicos WHERE ativo = 1 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$embarcacoes = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$adminUser = $pdo->query("SELECT id FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cliente || empty($servicos)) {
    die("Dados insuficientes para teste.\n");
}

$clienteId = $cliente['id'];
$userId = $adminUser['id'] ?? 'user-test';

// 2. Criar proposta de teste com 46 itens de serviço para simular o caso real da imagem
echo "1. Criando proposta com 46 itens de serviço distribuídos por embarcações...\n";

$numProposta = 'TEST-ORC-46-' . rand(1000, 9999);
$propId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
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
        :id, :numero, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'pix',
        39000.00, 19499.21, 39.91, 25900.00, 'perc',
        'rascunho', :criado_por, MD5(RAND())
    )
");
$stmtInsert->execute([
    ':id' => $propId,
    ':numero' => $numProposta,
    ':cliente_id' => $clienteId,
    ':criado_por' => $userId,
]);

$servicos = $pdo->query("SELECT id, nome, preco_padrao FROM servicos WHERE ativo = 1 LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
$embarcacoes = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

// Inserir 46 serviços divididos entre 8 embarcações (máximo 6 por embarcação)
$stmtServ = $pdo->prepare("
    INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado)
    VALUES (UUID(), :pid, :eid, :sid, 1, :preco)
");

$embIds = array_column($embarcacoes, 'id');
$totalInseridos = 0;
$embIndex = 0;

while ($totalInseridos < 46 && !empty($embIds)) {
    $currentEmb = $embIds[$embIndex % count($embIds)];
    $servIndex = (int)floor($totalInseridos / count($embIds));
    $sv = $servicos[$servIndex % count($servicos)];
    
    $stmtServ->execute([
        ':pid' => $propId,
        ':eid' => $currentEmb,
        ':sid' => $sv['id'],
        ':preco' => 1410.86,
    ]);
    
    $totalInseridos++;
    $embIndex++;
}

foreach ($embIds as $eid) {
    if ($eid) {
        $pdo->prepare("INSERT IGNORE INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
            ->execute([$propId, $eid]);
    }
}

echo "   Proposta criada com sucesso.\n";

// 3. Renderizar PDF
echo "\n2. Gerando PDF e analisando estrutura de páginas...\n";
$_SESSION['usuario_id'] = $userId;
$_SESSION['usuario_nivel'] = 'administrador';
$_GET['id'] = $propId;
$GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;
$GLOBALS['PROPOSTA_PDF_ID'] = $propId;

try {
    $pdfString = include __DIR__ . '/../modules/comercial/pdf.php';
    $tamanhoBytes = is_string($pdfString) ? strlen($pdfString) : 0;
    assertTeste($tamanhoBytes > 5000, "PDF binário retornado com sucesso ({$tamanhoBytes} bytes)");

    // Contar total de páginas reais usando setSourceFile do FPDI
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    require_once $autoload_path;
    $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
    $leitor = \setasign\Fpdi\PdfParser\StreamReader::createByString($pdfString);
    $totalPaginas = $fpdi->setSourceFile($leitor);

    echo "   Total de páginas geradas no PDF: {$totalPaginas}\n";
    assertTeste($totalPaginas === 3, "PDF tem exatamente 3 páginas (página órfã 4 eliminada com sucesso!)");
} catch (Throwable $e) {
    echo "  [✗] Erro: " . $e->getMessage() . "\n";
    $passou = false;
}

// Limpar dados de teste
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id = ?")->execute([$propId]);
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id = ?")->execute([$propId]);
$pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$propId]);
echo "  [✓] Dados de teste limpos.\n";

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: ACEITE FORMAL, ASSINATURAS E QR CODE UNIFICADOS COM 100% DE SUCESSO!\n";
} else {
    echo "RESULTADO: FALHA NA VALIDAÇÃO!\n";
    exit(1);
}
echo "============================================================\n";
