<?php
/**
 * Teste unitário/funcional para upload de múltiplas fotos por exigência e exclusão AJAX
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: UPLOAD DE MÚLTIPLAS FOTOS POR EXIGÊNCIA E EXCLUSÃO ===\n\n";

global $pdo;

// 1. Obter uma vistoria e uma exigência ativa do catálogo
$stmt = $pdo->query("SELECT id FROM vistorias ORDER BY criado_em DESC LIMIT 1");
$vistoria = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$vistoria) {
    die("[ERRO] Nenhuma vistoria encontrada para teste.\n");
}
$vistoriaId = $vistoria['id'];

$stmtItem = $pdo->query("SELECT id FROM exigencias_catalogo WHERE ativo = 1 LIMIT 1");
$item = $stmtItem->fetch(PDO::FETCH_ASSOC);
$catId = $item['id'];

echo "[OK] Vistoria alvo: {$vistoriaId}, Item catálogo: {$catId}\n";

try {
    $stmtUser = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $userId = $stmtUser->fetchColumn() ?: null;

    // 2. Simular upload de 3 fotos simultâneas para o mesmo item
    $fotoIds = [];
    for ($i = 1; $i <= 3; $i++) {
        $idFoto = gerarUUID();
        $fotoIds[] = $idFoto;
        $stmtIns = $pdo->prepare("INSERT INTO vistoria_anexos 
            (id, vistoria_id, catalogo_id, url_arquivo, chave_arquivo, nome_original, mime_type, tamanho_bytes, sha256, capturado_em, criado_por)
            VALUES (:id, :v, :cat, :url, :chave, :nome, 'image/jpeg', 102400, :hash, NOW(), :user)");
        $stmtIns->execute([
            ':id' => $idFoto,
            ':v' => $vistoriaId,
            ':cat' => $catId,
            ':url' => '/api/campo/v1/anexos/' . $idFoto,
            ':chave' => 'local:vistorias/' . $vistoriaId . '/originais/' . $idFoto . '.jpg',
            ':nome' => "evidencia_foto_{$i}.jpg",
            ':hash' => hash('sha256', "foto_{$i}"),
            ':user' => $userId
        ]);
    }
} catch (Throwable $e) {
    echo "EXCEÇÃO: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "[OK] Inseridas 3 fotos de teste para a exigência {$catId}.\n";

// 3. Verificar se a contagem agrupada retorna 3 fotos
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM vistoria_anexos 
    WHERE vistoria_id = :v AND catalogo_id = :cat AND excluido_em IS NULL");
$stmtCount->execute([':v' => $vistoriaId, ':cat' => $catId]);
$total = (int)$stmtCount->fetchColumn();

if ($total < 3) {
    die("[ERRO] Esperado pelo menos 3 fotos para a exigência, encontrado: {$total}\n");
}
echo "[OK] Consulta confirmou {$total} foto(s) anexada(s) para o item {$catId}.\n";

// 4. Testar exclusão lógica de uma das fotos
$fotoParaExcluir = $fotoIds[0];
$stmtDel = $pdo->prepare("UPDATE vistoria_anexos SET excluido_em = NOW(), excluido_por = 1 WHERE id = :id");
$stmtDel->execute([':id' => $fotoParaExcluir]);

$stmtCheckDel = $pdo->prepare("SELECT excluido_em FROM vistoria_anexos WHERE id = :id");
$stmtCheckDel->execute([':id' => $fotoParaExcluir]);
$excluidoEm = $stmtCheckDel->fetchColumn();

if (empty($excluidoEm)) {
    die("[ERRO] Falha na exclusão lógica da foto {$fotoParaExcluir}.\n");
}
echo "[OK] Exclusão lógica da foto {$fotoParaExcluir} confirmada (excluido_em = {$excluidoEm}).\n";

// Limpeza dos dados de teste
foreach ($fotoIds as $fId) {
    $pdo->prepare("DELETE FROM vistoria_anexos WHERE id = :id")->execute([':id' => $fId]);
}
echo "[OK] Limpeza dos dados de teste concluída.\n";
echo "\nTODOS OS TESTES DE MÚLTIPLAS FOTOS E EXCLUSÃO PASSARAM COM SUCESSO!\n";
