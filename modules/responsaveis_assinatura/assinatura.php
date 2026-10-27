<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
verificar_sessao();

$id = (int)($_GET['id'] ?? 0);
$usuarioIdParam = trim((string)($_GET['usuario_id'] ?? ''));

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT id, usuario_id, assinatura_arquivo, assinatura_hash FROM responsaveis_assinatura WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
} elseif ($usuarioIdParam !== '') {
    $stmt = $pdo->prepare('SELECT id, usuario_id, assinatura_arquivo, assinatura_hash FROM responsaveis_assinatura WHERE usuario_id = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$usuarioIdParam]);
} else {
    $myUid = (string)($_SESSION['usuario_id'] ?? '');
    $stmt = $pdo->prepare('SELECT id, usuario_id, assinatura_arquivo, assinatura_hash FROM responsaveis_assinatura WHERE usuario_id = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$myUid]);
}
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r || empty($r['assinatura_arquivo'])) {
    http_response_code(404);
    exit;
}

$ehProprioUsuario = (!empty($r['usuario_id']) && (string)$r['usuario_id'] === (string)($_SESSION['usuario_id'] ?? ''));
if (!podeAcessar('responsaveis_assinatura') && !$ehProprioUsuario) {
    http_response_code(403);
    exit;
}

$baseDir = defined('BASE_PATH') ? BASE_PATH : realpath(__DIR__ . '/../..');
$cleanRel = ltrim(str_replace(['../', '..\\'], '', (string)$r['assinatura_arquivo']), '/\\');
$file = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanRel);

if (!is_file($file)) {
    // Fallback relativo direto
    $fileAlt = __DIR__ . '/../../' . $cleanRel;
    if (is_file($fileAlt)) {
        $file = $fileAlt;
    } else {
        http_response_code(404);
        exit;
    }
}

if (!empty($r['assinatura_hash']) && !hash_equals((string)$r['assinatura_hash'], (string)hash_file('sha256', $file))) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Content-Length: ' . (string)filesize($file));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;

