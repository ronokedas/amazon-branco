<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/embarcacao_foto.php';

verificar_sessao();
$id = trim((string)($_GET['id'] ?? ''));
$stmt = $pdo->prepare("SELECT foto_chave,foto_mime_type,foto_nome_original FROM embarcacoes WHERE id=:id AND ativo=1 LIMIT 1");
$stmt->execute([':id'=>$id]);
$foto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$foto || empty($foto['foto_chave'])) {
    http_response_code(404);
    exit;
}
if (function_exists('log_atividade')) log_atividade('embarcacao_foto_visualizada', 'Foto oficial visualizada: ' . $id);
session_write_close();
embarcacaoFotoEmitir($foto['foto_chave'], $foto['foto_mime_type'] ?: 'image/jpeg', $foto['foto_nome_original'] ?: 'embarcacao');
