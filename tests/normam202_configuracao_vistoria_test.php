<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: GERENCIADOR NORMAM-202 E FOTOS OBRIGATÓRIAS ===\n\n";

global $pdo;

function testAssert(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: " . $msg);
    }
}

// 1. Verificar colunas na tabela exigencias_catalogo
$colunas = $pdo->query("SHOW COLUMNS FROM exigencias_catalogo")->fetchAll(PDO::FETCH_COLUMN);
testAssert(in_array('obrigatoria', $colunas), "Coluna 'obrigatoria' deve existir");
testAssert(in_array('exige_foto', $colunas), "Coluna 'exige_foto' deve existir");
testAssert(in_array('ordem_exibicao', $colunas), "Coluna 'ordem_exibicao' deve existir");
echo "[OK] Colunas obrigatoria, exige_foto e ordem_exibicao confirmadas na tabela exigencias_catalogo.\n";

// 2. Verificar novos itens essenciais da NORMAM-202 inseridos
$stmtNovos = $pdo->query("SELECT codigo_interno, descricao, item_normam, obrigatoria, exige_foto FROM exigencias_catalogo WHERE codigo_interno IN ('EX-553', 'EX-554', 'EX-555', 'EX-556') ORDER BY codigo_interno");
$novos = $stmtNovos->fetchAll(PDO::FETCH_ASSOC);
testAssert(count($novos) === 4, "Devem existir os 4 novos itens essenciais da NORMAM-202 (encontrados: " . count($novos) . ")");
foreach ($novos as $n) {
    testAssert((int)$n['obrigatoria'] === 1, "Item {$n['codigo_interno']} deve ser obrigatorio");
    testAssert((int)$n['exige_foto'] === 1, "Item {$n['codigo_interno']} deve exigir foto");
    echo "  - {$n['codigo_interno']}: {$n['descricao']} (NORMAM: {$n['item_normam']}) [OBRIGATÓRIA & FOTO]\n";
}
echo "[OK] Novos itens da NORMAM-202 (EX-553 a EX-556) validados com sucesso.\n";

// 3. Verificar total de itens ativos e itens com foto obrigatória
$totalAtivos = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1")->fetchColumn();
$totalObrigatorias = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND obrigatoria = 1")->fetchColumn();
$totalExigeFoto = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND exige_foto = 1")->fetchColumn();
echo "Estatísticas do Catálogo:\n";
echo "  - Total de exigências ativas: {$totalAtivos}\n";
echo "  - Exigências obrigatórias: {$totalObrigatorias}\n";
echo "  - Exigências com foto obrigatória: {$totalExigeFoto}\n";
testAssert($totalAtivos >= 270, "Total de exigencias ativas deve ser no minimo 270 (atual: {$totalAtivos})");
testAssert($totalObrigatorias >= 40, "Total de exigencias obrigatorias deve ser no minimo 40 (atual: {$totalObrigatorias})");
testAssert($totalExigeFoto >= 40, "Total de exigencias com foto deve ser no minimo 40 (atual: {$totalExigeFoto})");
echo "[OK] Estatísticas de exigências consistentes com NORMAM-202 / ISO 9001.\n";

// 4. Testar toggle de obrigatoriedade e foto via banco
$stmtTestItem = $pdo->query("SELECT id, obrigatoria, exige_foto FROM exigencias_catalogo WHERE codigo_interno = 'EX-553' LIMIT 1");
$testItem = $stmtTestItem->fetch(PDO::FETCH_ASSOC);
$idItem = $testItem['id'];

// Testar toggle_obrigatoria
$novoObrig = $testItem['obrigatoria'] ? 0 : 1;
$stmtUpd = $pdo->prepare("UPDATE exigencias_catalogo SET obrigatoria = :v WHERE id = :id");
$stmtUpd->execute([':v' => $novoObrig, ':id' => $idItem]);
$valCheck = (int)$pdo->query("SELECT obrigatoria FROM exigencias_catalogo WHERE id = '{$idItem}'")->fetchColumn();
testAssert($valCheck === $novoObrig, "Valor de obrigatoria deve ser atualizado");

// Reverter para 1
$stmtUpd->execute([':v' => 1, ':id' => $idItem]);
$valCheck = (int)$pdo->query("SELECT obrigatoria FROM exigencias_catalogo WHERE id = '{$idItem}'")->fetchColumn();
testAssert($valCheck === 1, "Valor de obrigatoria deve retornar para 1");
echo "[OK] Toggle de obrigatoriedade funcionando corretamente.\n";

// 5. Testar validação de foto obrigatória para envio de relatório
$stmtVistoriaCheck = $pdo->prepare("
    SELECT ec.codigo_interno, ec.descricao
    FROM exigencias_catalogo ec
    WHERE ec.id = :id
      AND ec.exige_foto = 1
      AND NOT EXISTS (
          SELECT 1 FROM vistoria_anexos va
          WHERE va.vistoria_id = 'teste-vistoria-inexistente'
            AND va.catalogo_id = ec.id
            AND va.excluido_em IS NULL
      )
");
$stmtVistoriaCheck->execute([':id' => $idItem]);
$faltaFoto = $stmtVistoriaCheck->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($faltaFoto), "Query de validação deve detectar exigência com foto faltante");
echo "[OK] Query de validação de evidência fotográfica obrigatória confirmada.\n";

// 6. Testar inserção auditável de nova exigência via catálogo
$idNova = gerarUUID();
$codNovo = 'EX-TEST-999';
$catPadraoId = $pdo->query("SELECT id FROM exigencias_categorias LIMIT 1")->fetchColumn();

$stmtIns = $pdo->prepare("
    INSERT INTO exigencias_catalogo (id, categoria_id, codigo_interno, descricao, item_normam, bloco_vistoria, obrigatoria, exige_foto, ativo, criado_em)
    VALUES (:id, :cat, :cod, :desc, :normam, 'flutuando', 1, 1, 1, NOW())
");
$stmtIns->execute([
    ':id' => $idNova,
    ':cat' => $catPadraoId,
    ':cod' => $codNovo,
    ':desc' => 'Exigência de teste unitário',
    ':normam' => 'NORMAM-202/DPC, Teste',
]);

$itemCriado = $pdo->query("SELECT * FROM exigencias_catalogo WHERE id = '{$idNova}'")->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($itemCriado), "Item de teste deve ser inserido");
testAssert((int)$itemCriado['obrigatoria'] === 1, "Item deve ser obrigatorio");
testAssert((int)$itemCriado['exige_foto'] === 1, "Item deve exigir foto");

// Soft delete
$pdo->query("UPDATE exigencias_catalogo SET ativo = 0 WHERE id = '{$idNova}'");
$itemInativo = $pdo->query("SELECT ativo FROM exigencias_catalogo WHERE id = '{$idNova}'")->fetchColumn();
testAssert((int)$itemInativo === 0, "Item deve ser inativado");

// Limpeza
$pdo->query("DELETE FROM exigencias_catalogo WHERE id = '{$idNova}'");
echo "[OK] Ciclo de vida de exigência do catálogo (criação, inativação, remoção) verificado.\n";

echo "\nTODOS OS TESTES DO GERENCIADOR NORMAM-202 PASSARAM COM SUCESSO!\n";
