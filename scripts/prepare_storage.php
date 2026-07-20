<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/campo_storage.php';

try {
    campoStorageGarantirBucket();
    echo 'Bucket privado de evidencias preparado: ' . campoStorageBucket() . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO ao preparar armazenamento de evidencias: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

