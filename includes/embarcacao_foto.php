<?php

// A API de campo também usa o cliente S3 abaixo; carregá-lo aqui mantém
// fotos oficiais e evidências no mesmo armazenamento privado quando disponível.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function embarcacaoFotoS3(): ?\Aws\S3\S3Client
{
    if (!class_exists(\Aws\S3\S3Client::class) || !defined('MINIO_ACCESS_KEY') || !defined('MINIO_SECRET_KEY')) return null;
    return new \Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
        'use_path_style_endpoint' => true,
        'credentials' => ['key' => MINIO_ACCESS_KEY, 'secret' => MINIO_SECRET_KEY],
    ]);
}

function embarcacaoFotoBucket(): string
{
    return defined('MINIO_CAMPO_BUCKET') ? MINIO_CAMPO_BUCKET : 'erp-campo-private';
}

function embarcacaoFotoGuardar(string $binario, string $mime, string $embarcacaoId, string $fotoId): string
{
    $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime] ?? 'bin';
    $chave = 'embarcacoes/' . $embarcacaoId . '/foto-oficial/' . $fotoId . '.' . $ext;
    $s3 = embarcacaoFotoS3();
    if ($s3) {
        try { $s3->headBucket(['Bucket'=>embarcacaoFotoBucket()]); }
        catch (Throwable $e) { $s3->createBucket(['Bucket'=>embarcacaoFotoBucket()]); }
        $s3->putObject(['Bucket'=>embarcacaoFotoBucket(), 'Key'=>$chave, 'Body'=>$binario, 'ContentType'=>$mime]);
        return $chave;
    }
    $arquivo = BASE_PATH . '/storage/private/' . $chave;
    $diretorio = dirname($arquivo);
    if (!is_dir($diretorio) && !mkdir($diretorio, 0750, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Falha ao preparar o armazenamento da foto da embarcação.');
    }
    if (file_put_contents($arquivo, $binario) === false) throw new RuntimeException('Falha ao armazenar a foto da embarcação.');
    return 'local:' . $chave;
}

function embarcacaoFotoExcluir(?string $chave): void
{
    if (!$chave) return;
    if (str_starts_with($chave, 'local:')) {
        $arquivo = BASE_PATH . '/storage/private/' . substr($chave, 6);
        if (is_file($arquivo)) @unlink($arquivo);
        return;
    }
    $s3 = embarcacaoFotoS3();
    if ($s3) $s3->deleteObject(['Bucket'=>embarcacaoFotoBucket(), 'Key'=>$chave]);
}

function embarcacaoFotoEmitir(string $chave, string $mime, string $nome): never
{
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nome ?: 'embarcacao') . '"');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    if (str_starts_with($chave, 'local:')) {
        $arquivo = BASE_PATH . '/storage/private/' . substr($chave, 6);
        if (!is_file($arquivo)) { http_response_code(404); exit; }
        header('Content-Length: ' . filesize($arquivo));
        readfile($arquivo);
        exit;
    }
    $s3 = embarcacaoFotoS3();
    if (!$s3) { http_response_code(503); exit; }
    $resultado = $s3->getObject(['Bucket'=>embarcacaoFotoBucket(), 'Key'=>$chave]);
    echo $resultado['Body'];
    exit;
}
