<?php
require_once __DIR__ . '/../config.php';

$sqlFile = __DIR__ . '/092_analise_planos_banco_normas.sql';
if (!file_exists($sqlFile)) {
    die("Arquivo SQL não encontrado: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

echo "Executando migração 092...\n";

// Executar linha a linha ou blocos
try {
    $pdo->exec($sql);
    echo "[OK] Migração 092 executada com sucesso!\n";
    
    // Validar contagem
    $count = $pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam")->fetchColumn();
    echo "[OK] Total de referências NORMAM cadastradas no banco: $count\n";
    
    // Registrar na schema_migrations se a tabela existir
    $hasSchema = $pdo->query("SHOW TABLES LIKE 'schema_migrations'")->fetchColumn();
    if ($hasSchema) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO schema_migrations (versao) VALUES ('092_analise_planos_banco_normas.sql')");
        $stmt->execute();
        echo "[OK] Registrado em schema_migrations.\n";
    }
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
