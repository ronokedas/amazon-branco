<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/aprovacao_documentos.php';

[, $tipo, $documentoId, $destino] = array_pad($argv, 4, '');
$basePermitida = realpath(BASE_PATH . '/storage/private/documento_artefatos');
$pastaDestino = realpath(dirname($destino));
if ($tipo === '' || $documentoId === '' || !$basePermitida || !$pastaDestino
    || !str_starts_with($pastaDestino . DIRECTORY_SEPARATOR, $basePermitida . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Parâmetros de geração inválidos.\n");
    exit(2);
}

$tipo = strtoupper($tipo);
if ($tipo === 'PROPOSTA') {
    $GLOBALS['PROPOSTA_PDF_ID'] = $documentoId;
    $GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;
    $bytes = require BASE_PATH . '/modules/comercial/pdf.php';
    unset($GLOBALS['PROPOSTA_PDF_ID'], $GLOBALS['PROPOSTA_PDF_RETURN_STRING']);
    if (is_string($bytes) && strlen($bytes) >= 200) file_put_contents($destino, $bytes);
} else {
    aprovacaoDocumentoGerarOriginal($tipo, $documentoId, $destino);
}
if (!is_file($destino) || filesize($destino) < 200) {
    fwrite(STDERR, "O gerador não produziu um PDF válido.\n");
    exit(3);
}
