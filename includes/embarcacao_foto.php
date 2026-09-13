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

function embarcacaoFotoProcessarUpload(PDO $pdo, string $embarcacaoId, array $upload, string $usuarioId, ?string $vistoriaId = null): array
{
    if (empty($embarcacaoId)) {
        throw new InvalidArgumentException('Identificador da embarcação inválido.');
    }
    if (empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('Nenhum arquivo de imagem válido foi enviado.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($upload['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new InvalidArgumentException('Formato de foto inválido. Por favor, envie uma foto em formato JPEG, PNG ou WebP.');
    }

    $tamanho = (int)($upload['size'] ?? 0);
    if ($tamanho > 15 * 1024 * 1024) {
        throw new InvalidArgumentException('O tamanho da foto excede o limite máximo permitido de 15 MB.');
    }

    $binario = file_get_contents($upload['tmp_name']);
    if ($binario === false) {
        throw new RuntimeException('Falha ao ler o conteúdo do arquivo da foto.');
    }

    $fotoId = function_exists('gerarUUID') ? gerarUUID() : bin2hex(random_bytes(16));
    $hash = hash('sha256', $binario);

    $stmtAtual = $pdo->prepare("SELECT foto_chave FROM embarcacoes WHERE id = :id AND ativo = 1 LIMIT 1");
    $stmtAtual->execute([':id' => $embarcacaoId]);
    $chaveAnterior = $stmtAtual->fetchColumn();

    $novaChave = embarcacaoFotoGuardar($binario, $mime, $embarcacaoId, $fotoId);
    $fotoUrl = APP_URL . 'embarcacoes/foto?id=' . rawurlencode($embarcacaoId) . '&v=' . substr($hash, 0, 12);
    $nomeOriginal = substr((string)($upload['name'] ?? 'embarcacao.jpg'), 0, 255);

    $transacaoPropria = !$pdo->inTransaction();
    if ($transacaoPropria) $pdo->beginTransaction();

    try {
        $stmtUpd = $pdo->prepare("UPDATE embarcacoes
            SET foto_chave = :chave,
                foto_url = :url,
                foto_nome_original = :nome,
                foto_mime_type = :mime,
                foto_tamanho_bytes = :tamanho,
                foto_sha256 = :hash,
                foto_atualizada_em = NOW(),
                foto_atualizada_por = :usuario
            WHERE id = :id AND ativo = 1");
        $stmtUpd->execute([
            ':chave'   => $novaChave,
            ':url'     => $fotoUrl,
            ':nome'    => $nomeOriginal,
            ':mime'    => $mime,
            ':tamanho' => $tamanho,
            ':hash'    => $hash,
            ':usuario' => $usuarioId,
            ':id'      => $embarcacaoId,
        ]);

        if (!empty($vistoriaId)) {
            $stmtCheckAnexo = $pdo->prepare("SELECT id FROM vistoria_anexos WHERE vistoria_id = :vistoria_id AND sha256 = :hash LIMIT 1");
            $stmtCheckAnexo->execute([':vistoria_id' => $vistoriaId, ':hash' => $hash]);
            $anexoExistenteId = $stmtCheckAnexo->fetchColumn();

            if ($anexoExistenteId) {
                $stmtAnexoUpd = $pdo->prepare("UPDATE vistoria_anexos
                    SET url_arquivo = :url, chave_arquivo = :chave, nome_original = :nome, mime_type = :mime, tamanho_bytes = :tamanho, capturado_em = NOW()
                    WHERE id = :id");
                $stmtAnexoUpd->execute([
                    ':url'     => $fotoUrl,
                    ':chave'   => $novaChave,
                    ':nome'    => 'FOTO_OFICIAL_' . $nomeOriginal,
                    ':mime'    => $mime,
                    ':tamanho' => $tamanho,
                    ':id'      => $anexoExistenteId,
                ]);
            } else {
                $stmtAnexo = $pdo->prepare("INSERT INTO vistoria_anexos
                    (id, vistoria_id, catalogo_id, url_arquivo, chave_arquivo, nome_original, mime_type, tamanho_bytes, sha256, capturado_em, criado_por)
                    VALUES (:id, :vistoria_id, NULL, :url, :chave, :nome, :mime, :tamanho, :hash, NOW(), :criado_por)");
                $stmtAnexo->execute([
                    ':id'          => $fotoId,
                    ':vistoria_id' => $vistoriaId,
                    ':url'         => $fotoUrl,
                    ':chave'       => $novaChave,
                    ':nome'        => 'FOTO_OFICIAL_' . $nomeOriginal,
                    ':mime'        => $mime,
                    ':tamanho'     => $tamanho,
                    ':hash'        => $hash,
                    ':criado_por'  => $usuarioId,
                ]);
            }
        }

        if ($transacaoPropria) $pdo->commit();

        if ($chaveAnterior && $chaveAnterior !== $novaChave) {
            try { embarcacaoFotoExcluir((string)$chaveAnterior); } catch (Throwable $e) {}
        }

        return [
            'ok'             => true,
            'foto_url'       => $fotoUrl,
            'foto_chave'     => $novaChave,
            'hash'           => $hash,
            'tamanho'        => $tamanho,
            'atualizada_em'  => date('d/m/Y H:i'),
        ];
    } catch (Throwable $e) {
        if ($transacaoPropria && $pdo->inTransaction()) $pdo->rollBack();
        embarcacaoFotoExcluir($novaChave);
        throw $e;
    }
}

function embarcacaoFotoRemover(PDO $pdo, string $embarcacaoId, string $usuarioId): bool
{
    if (empty($embarcacaoId)) return false;

    $stmtAtual = $pdo->prepare("SELECT foto_chave FROM embarcacoes WHERE id = :id AND ativo = 1 LIMIT 1");
    $stmtAtual->execute([':id' => $embarcacaoId]);
    $chaveAnterior = $stmtAtual->fetchColumn();

    $stmtUpd = $pdo->prepare("UPDATE embarcacoes
        SET foto_chave = NULL,
            foto_url = NULL,
            foto_nome_original = NULL,
            foto_mime_type = NULL,
            foto_tamanho_bytes = NULL,
            foto_sha256 = NULL,
            foto_atualizada_em = NOW(),
            foto_atualizada_por = :usuario
        WHERE id = :id AND ativo = 1");
    $stmtUpd->execute([
        ':usuario' => $usuarioId,
        ':id'      => $embarcacaoId,
    ]);

    if ($chaveAnterior) {
        try { embarcacaoFotoExcluir((string)$chaveAnterior); } catch (Throwable $e) {}
    }

    return true;
}

