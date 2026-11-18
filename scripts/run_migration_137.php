<?php
/**
 * Aplica a migration 137: agenda de parcelas de propostas no Financeiro.
 * Seguro para reexecução: verifica colunas, índice e registro da migration.
 *
 * Execute manualmente no ambiente de destino após backup do banco:
 * php scripts/run_migration_137.php
 */
require_once __DIR__ . '/../config.php';

$versao = '137_financeiro_parcelas_propostas.sql';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        versao VARCHAR(255) PRIMARY KEY,
        executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmtMigracao = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE versao = :versao');
    $stmtMigracao->execute([':versao' => $versao]);
    if ((int)$stmtMigracao->fetchColumn() > 0) {
        echo "Migration 137 já está registrada. Nada a fazer.\n";
        exit(0);
    }

    $colunas = [
        'numero_parcela' => "SMALLINT UNSIGNED NULL DEFAULT NULL AFTER proposta_id",
        'total_parcelas' => "SMALLINT UNSIGNED NULL DEFAULT NULL AFTER numero_parcela",
    ];

    foreach ($colunas as $nome => $definicao) {
        $stmtColuna = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'financeiro_lancamentos'
              AND COLUMN_NAME = :coluna
        ");
        $stmtColuna->execute([':coluna' => $nome]);
        if ((int)$stmtColuna->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE financeiro_lancamentos ADD COLUMN {$nome} {$definicao}");
            echo "Coluna {$nome} criada.\n";
        } else {
            echo "Coluna {$nome} já existe.\n";
        }
    }

    $stmtIndice = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'financeiro_lancamentos'
          AND INDEX_NAME = 'uk_financeiro_proposta_parcela'
    ");
    $stmtIndice->execute();
    if ((int)$stmtIndice->fetchColumn() === 0) {
        $duplicados = (int)$pdo->query("
            SELECT COUNT(*) FROM (
                SELECT proposta_id, numero_parcela
                FROM financeiro_lancamentos
                WHERE proposta_id IS NOT NULL AND numero_parcela IS NOT NULL
                GROUP BY proposta_id, numero_parcela
                HAVING COUNT(*) > 1
            ) duplicados
        ")->fetchColumn();
        if ($duplicados > 0) {
            throw new RuntimeException('Existem parcelas duplicadas por proposta. O índice único não foi criado.');
        }
        $pdo->exec("
            ALTER TABLE financeiro_lancamentos
            ADD UNIQUE KEY uk_financeiro_proposta_parcela (proposta_id, numero_parcela)
        ");
        echo "Índice único de parcela por proposta criado.\n";
    } else {
        echo "Índice uk_financeiro_proposta_parcela já existe.\n";
    }

    $stmtRegistro = $pdo->prepare('INSERT IGNORE INTO schema_migrations (versao) VALUES (:versao)');
    $stmtRegistro->execute([':versao' => $versao]);
    echo "Migration 137 aplicada e registrada com sucesso.\n";
} catch (Throwable $e) {
    error_log('Erro na migration 137: ' . $e->getMessage());
    fwrite(STDERR, 'Falha na migration 137: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
