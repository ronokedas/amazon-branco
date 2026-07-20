<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/campo_storage.php';

try {
    $pdo->query('SELECT 1')->fetchColumn();
    $s3 = campoStorageS3();
    if (!$s3) throw new RuntimeException('Cliente S3/MinIO indisponivel.');
    $s3->headBucket(['Bucket' => campoStorageBucket()]);
    echo "Banco e armazenamento saudaveis.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Healthcheck falhou: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

