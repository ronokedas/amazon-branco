<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
verificar_sessao();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, usuario_id, assinatura_arquivo, assinatura_hash FROM responsaveis_assinatura WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r || empty($r['assinatura_arquivo'])) {
    http_response_code(404);
    exit;
}

$ehProprioUsuario = (!empty($r['usuario_id']) && $r['usuario_id'] === ($_SESSION['usuario_id'] ?? ''));
if (!podeAcessar('responsaveis_assinatura') && !$ehProprioUsuario) {
    http_response_code(403);
    exit;
}

$file = __DIR__ . '/../../' . ltrim(str_replace(['../', '..\\'], '', $r['assinatura_arquivo']), '/\\');
if (!is_file($file) || !hash_equals((string)$r['assinatura_hash'], hash_file('sha256', $file))) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private,max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;
