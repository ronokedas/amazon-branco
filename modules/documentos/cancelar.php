<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aprovacao_documentos.php';

header('Content-Type: application/json; charset=UTF-8');
try {
    verificar_sessao();
    if (getCargo() !== 'ADMIN') { http_response_code(403); throw new RuntimeException('Apenas administradores podem cancelar documentos aprovados.'); }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); throw new RuntimeException('Metodo nao permitido.'); }
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) { http_response_code(419); throw new RuntimeException('Sessao expirada. Atualize a pagina.'); }
    cancelarAprovacaoDocumento($pdo, (string)($_POST['documento_tipo'] ?? ''), (string)($_POST['documento_id'] ?? ''), (string)($_POST['motivo'] ?? ''));
    echo json_encode(['success'=>true, 'message'=>'Documento cancelado. O PDF e a auditoria foram preservados.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(422);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
