<?php
/**
 * Teste Automatizado: Suporte a Casas Decimais (com vírgula e ponto) no Desconto de Propostas
 * - Valida normalização no backend de valores como '39,3' e '39.3'
 * - Valida cálculos de subtotal R$ 33.700,00 com desconto de 39,3%
 * - Valida persistência e recuperação no banco de dados
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/comercial/propostas/actions.php';

echo "=== TESTE: DESCONTO COM VÍRGULA / DECIMAL NA PROPOSTA ===\n\n";

// 1. Validar função normalizarDecimalProposta com vírgula e ponto
$v1 = normalizarDecimalProposta('39,3');
$v2 = normalizarDecimalProposta('39.3');
$v3 = normalizarDecimalProposta('39,30');
$v4 = normalizarDecimalProposta('0,5');

if ($v1 !== 39.3 || $v2 !== 39.3 || $v3 !== 39.3 || $v4 !== 0.5) {
    echo "FALHA na normalização de decimais: v1={$v1}, v2={$v2}, v3={$v3}, v4={$v4}\n";
    exit(1);
}
echo "  [✓] Função normalizarDecimalProposta aceita vírgula e ponto perfeitamente.\n";

// 2. Simular cálculo financeiro idêntico ao da imagem do usuário
$subtotalGeral = 33700.00;
$descontoPercentual = normalizarDecimalProposta('39,3');
validarDescontoProposta('perc', $descontoPercentual);

$descontoValor = round($subtotalGeral * ($descontoPercentual / 100), 2);
$valorTotal = round($subtotalGeral - $descontoValor, 2);

echo "Subtotal: R$ " . number_format($subtotalGeral, 2, ',', '.') . "\n";
echo "Desconto: {$descontoPercentual}% = - R$ " . number_format($descontoValor, 2, ',', '.') . "\n";
echo "Total Geral: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";

if ($descontoValor !== 13244.10 || $valorTotal !== 20455.90) {
    echo "FALHA nos cálculos financeiros!\n";
    exit(1);
}
echo "  [✓] Cálculos matemáticos de 39,3% sobre R$ 33.700,00 exatos (Total: R$ 20.455,90).\n";

// 3. Testar gravação e leitura no banco de dados
global $pdo;

$clienteId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();
if (!$clienteId) {
    $clienteId = bin2hex(random_bytes(16));
    $pdo->prepare("INSERT INTO clientes (id, nome, perfil, status, ativo, criado_em) VALUES (?, 'Cliente Teste', 'armador', 'ATIVO', 1, NOW())")->execute([$clienteId]);
}

$idTeste = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("
    INSERT INTO propostas (id, numero, cliente_id, data_emissao, data_validade, valor_total, desconto_percentual, desconto_valor, status)
    VALUES (?, 'TESTE-DESC-DECIMAL', ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, 'rascunho')
");
$stmt->execute([$idTeste, $clienteId, $valorTotal, $descontoPercentual, $descontoValor]);

$stmtBusca = $pdo->prepare("SELECT valor_total, desconto_percentual, desconto_valor FROM propostas WHERE id = ?");
$stmtBusca->execute([$idTeste]);
$prop = $stmtBusca->fetch(PDO::FETCH_ASSOC);

echo "Banco de dados gravou: desconto_percentual = {$prop['desconto_percentual']}%\n";

if ((float)$prop['desconto_percentual'] !== 39.30 || (float)$prop['valor_total'] !== 20455.90) {
    echo "FALHA na persistência do desconto decimal no banco!\n";
    $pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$idTeste]);
    exit(1);
}
echo "  [✓] Banco de dados persistiu e retornou 39.30% com precisão total.\n";

// Limpeza
$pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$idTeste]);

echo "\n===============================================================\n";
echo "TODAS AS VALIDAÇÕES DE DESCONTO DECIMAL PASSARAM COM SUCESSO!\n";
echo "===============================================================\n";
