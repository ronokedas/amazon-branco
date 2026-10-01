<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    echo "Iniciando migração 123 (Ofício e Assinatura Digital de Protocolos)..." . PHP_EOL;

    // Colunas para protocolo_dossies
    $colsDossies = $pdo->query('SHOW COLUMNS FROM protocolo_dossies')->fetchAll(PDO::FETCH_COLUMN);

    $novasDossies = [
        'destinatario_autoridade' => 'VARCHAR(255) NULL',
        'numero_oficio' => 'VARCHAR(50) NULL',
        'normam_referencia' => "VARCHAR(50) NULL DEFAULT 'NORMAM 202/DPC'",
        'assinado' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'assinatura_em' => 'DATETIME NULL',
        'responsavel_assinatura_id' => 'INT NULL',
        'assinante_nome' => 'VARCHAR(200) NULL',
        'assinante_cargo' => 'VARCHAR(200) NULL',
        'assinante_registro' => 'VARCHAR(100) NULL',
        'assinatura_imagem' => 'LONGTEXT NULL',
        'assinatura_ip' => 'VARCHAR(45) NULL'
    ];

    foreach ($novasDossies as $col => $def) {
        if (!in_array($col, $colsDossies, true)) {
            $pdo->exec("ALTER TABLE protocolo_dossies ADD COLUMN {$col} {$def}");
            echo "protocolo_dossies: adicionada coluna '{$col}'." . PHP_EOL;
        } else {
            echo "protocolo_dossies: coluna '{$col}' já existe." . PHP_EOL;
        }
    }

    // Colunas para protocolo_movimentacoes
    $colsMov = $pdo->query('SHOW COLUMNS FROM protocolo_movimentacoes')->fetchAll(PDO::FETCH_COLUMN);

    $novasMov = [
        'destinatario_autoridade' => 'VARCHAR(255) NULL',
        'numero_oficio' => 'VARCHAR(50) NULL',
        'assinado' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'assinatura_em' => 'DATETIME NULL',
        'responsavel_assinatura_id' => 'INT NULL',
        'assinante_nome' => 'VARCHAR(200) NULL',
        'assinante_cargo' => 'VARCHAR(200) NULL',
        'assinante_registro' => 'VARCHAR(100) NULL',
        'assinatura_imagem' => 'LONGTEXT NULL',
        'assinatura_ip' => 'VARCHAR(45) NULL'
    ];

    foreach ($novasMov as $col => $def) {
        if (!in_array($col, $colsMov, true)) {
            $pdo->exec("ALTER TABLE protocolo_movimentacoes ADD COLUMN {$col} {$def}");
            echo "protocolo_movimentacoes: adicionada coluna '{$col}'." . PHP_EOL;
        } else {
            echo "protocolo_movimentacoes: coluna '{$col}' já existe." . PHP_EOL;
        }
    }

    echo "Migração 123 concluída com sucesso!" . PHP_EOL;
} catch (Throwable $e) {
    echo "Erro na migração 123: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
