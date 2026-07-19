<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aprovacao_documentos.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    verificar_sessao();
    if (getCargo() !== 'ADMIN') {
        http_response_code(403);
        throw new RuntimeException('Apenas administradores podem aprovar e assinar documentos.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new RuntimeException('Metodo nao permitido.');
    }
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        throw new RuntimeException('Sessao expirada. Atualize a pagina e tente novamente.');
    }

    $result = aprovarDocumentoEletronicamente($pdo, $_POST);
    echo json_encode(['success'=>true, 'message'=>'Documento aprovado e assinado eletronicamente.', 'data'=>$result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(422);
    error_log('Erro na aprovacao eletronica: ' . $e->getMessage());
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
