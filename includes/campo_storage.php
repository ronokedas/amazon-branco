<?php

/**
 * Armazenamento privado das evidencias produzidas pelo aplicativo de campo.
 *
 * O bucket permanece privado. As imagens sempre sao entregues pelo PHP depois
 * da verificacao de permissao, nunca por uma URL publica do MinIO.
 */

$campoStorageAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($campoStorageAutoload)) {
    require_once $campoStorageAutoload;
}

function campoStorageBucket(): string {
    return defined('MINIO_CAMPO_BUCKET') ? MINIO_CAMPO_BUCKET : 'erp-campo-private';
}

function campoStorageS3(): ?\Aws\S3\S3Client {
    static $cliente = false;
    if ($cliente instanceof \Aws\S3\S3Client) return $cliente;
    if (!class_exists('Aws\\S3\\S3Client')) return null;

    $cliente = new \Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => defined('MINIO_ACCESS_KEY') ? MINIO_ACCESS_KEY : 'erp_minio_admin',
            'secret' => defined('MINIO_SECRET_KEY') ? MINIO_SECRET_KEY : 'erp_minio_pass_2026',
        ],
    ]);
    return $cliente;
}

function campoStorageGarantirBucket(): void {
    $s3 = campoStorageS3();
    if (!$s3) {
        throw new RuntimeException('O cliente de armazenamento S3/MinIO nao esta instalado.');
    }

    $bucket = campoStorageBucket();
    try {
        $s3->headBucket(['Bucket' => $bucket]);
    } catch (Throwable $e) {
        $s3->createBucket(['Bucket' => $bucket]);
        $s3->waitUntil('BucketExists', ['Bucket' => $bucket]);
    }
}

function campoStorageBaixarPara(string $chave, string $destino): void {
    if (str_starts_with($chave, 'local:')) {
        $origem = BASE_PATH . '/storage/private/' . substr($chave, 6);
        if (!is_file($origem) || !copy($origem, $destino)) {
            throw new RuntimeException('Evidencia local nao encontrada.');
        }
        return;
    }

    campoStorageGarantirBucket();
    campoStorageS3()->getObject([
        'Bucket' => campoStorageBucket(),
        'Key' => $chave,
        'SaveAs' => $destino,
    ]);
    if (!is_file($destino) || filesize($destino) === 0) {
        throw new RuntimeException('O armazenamento retornou uma evidencia vazia.');
    }
}

