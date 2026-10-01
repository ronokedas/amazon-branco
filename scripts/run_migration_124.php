<?php
require_once __DIR__ . '/../config.php';

echo "Aplicando migração 124 (Cargo SECRETARIA)...\n";

try {
    $sql = file_get_contents(__DIR__ . '/../migrations/124_cargo_secretaria.sql');
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $q) {
        if (!empty($q)) {
            $pdo->exec($q);
        }
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        versao VARCHAR(255) PRIMARY KEY,
        executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare("INSERT IGNORE INTO schema_migrations (versao) VALUES ('124_cargo_secretaria.sql')");
    $stmt->execute();
    
    echo "✅ Migração 124 aplicada com sucesso!\n";
} catch (Exception $e) {
    echo "❌ Erro ao aplicar migração 124: " . $e->getMessage() . "\n";
    exit(1);
}
