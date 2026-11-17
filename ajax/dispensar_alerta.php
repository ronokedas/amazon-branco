<?php
/**
 * AJAX: Dispensar Alerta Operacional do Usuário
 * Registra a dispensa do alerta no banco e invalida o cache do dashboard
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

verificar_sessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? '';
if (!$usuarioId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão inválida.']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verificarCSRF($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança expirado.']);
    exit;
}

$tipoAlerta = trim((string)($_POST['alerta_tipo'] ?? ''));
$referenciaId = trim((string)($_POST['referencia_id'] ?? ''));

if ($tipoAlerta === '' || $referenciaId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO alertas_dispensados (usuario_id, alerta_tipo, referencia_id, dispensado_em)
        VALUES (:uid, :tipo, :ref, NOW())
        ON DUPLICATE KEY UPDATE dispensado_em = NOW()
    ");
    $stmt->execute([
        ':uid' => $usuarioId,
        ':tipo' => $tipoAlerta,
        ':ref' => $referenciaId,
    ]);

    invalidarCacheDashboard($usuarioId);

    echo json_encode(['success' => true, 'message' => 'Alerta dispensado com sucesso.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar dispensa de alerta: ' . $e->getMessage()]);
}
