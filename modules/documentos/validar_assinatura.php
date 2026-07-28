<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$token = trim((string)($_GET['token'] ?? ''));
$stmt = $pdo->prepare("SELECT da.*,
        ra.nome_completo,ra.cargo_titulo,ra.registro_profissional,
        executor.nome AS executor_nome,
        aprovador.nome AS aprovador_nome,
        v.numero AS documento_numero,
        v.data_emissao,
        v.data_aprovacao
    FROM documento_assinaturas da
    JOIN responsaveis_assinatura ra ON ra.id=da.responsavel_id
    LEFT JOIN usuarios executor ON executor.id=da.usuario_id
    LEFT JOIN vistorias v ON da.documento_tipo='RELATORIO' AND v.id=da.documento_id
    LEFT JOIN usuarios aprovador ON aprovador.id=v.aprovado_por
    WHERE da.token_validacao=:token
    LIMIT 1");
$stmt->execute([':token' => $token]);
$assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
$hashCalculado = false;

if (!$assinatura) {
    http_response_code(404);
    $estado = 'nao_encontrado';
    $titulo = 'Documento não encontrado';
    $mensagem = 'O token informado não corresponde a um documento assinado.';
} elseif ((string)$assinatura['status'] === 'CANCELADO') {
    $resultadoIntegridade = avaliarIntegridadeAssinaturaPublica($assinatura, __DIR__ . '/../../');
    http_response_code($resultadoIntegridade['http']);
    $estado = $resultadoIntegridade['estado'];
    $titulo = $resultadoIntegridade['titulo'];
    $mensagem = $resultadoIntegridade['mensagem'];
    $hashCalculado = $resultadoIntegridade['hash_calculado'];
} else {
    $resultadoIntegridade = avaliarIntegridadeAssinaturaPublica($assinatura, __DIR__ . '/../../');
    http_response_code($resultadoIntegridade['http']);
    $estado = $resultadoIntegridade['estado'];
    $titulo = $resultadoIntegridade['titulo'];
    $mensagem = $resultadoIntegridade['mensagem'];
    $hashCalculado = $resultadoIntegridade['hash_calculado'];
}

$classesEstado = [
    'valido' => 'ok',
    'cancelado' => 'cancelado',
    'falha' => 'falha',
    'nao_encontrado' => 'neutro',
];
$classeEstado = $classesEstado[$estado] ?? 'neutro';
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Validação pública de documento</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#eef4f1;color:#173b33;padding:30px 16px}
        .card{max-width:760px;margin:auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 12px 35px #0002}
        h1{margin:0 0 8px}.ok{color:#078454}.cancelado,.falha{color:#b42318}.neutro{color:#475467}
        .mensagem{margin:0 0 24px;color:#475467}.dados{display:grid;grid-template-columns:210px 1fr;border:1px solid #d6e2dd;border-radius:10px;overflow:hidden}
        .dados dt,.dados dd{margin:0;padding:11px 13px;border-bottom:1px solid #e5ece9}.dados dt{font-weight:700;background:#f7faf8}.dados dd{overflow-wrap:anywhere}
        .dados dt:last-of-type,.dados dd:last-of-type{border-bottom:0}code{font-size:12px}
        @media(max-width:620px){.dados{grid-template-columns:1fr}.dados dt{border-bottom:0}.card{padding:20px}}
    </style>
</head>
<body>
<main class="card">
    <h1 class="<?= h($classeEstado) ?>"><?= h($titulo) ?></h1>
    <p class="mensagem"><?= h($mensagem) ?></p>
    <?php if ($assinatura): ?>
        <dl class="dados">
            <dt>Número do documento</dt>
            <dd><?= h($assinatura['documento_numero'] ?: $assinatura['documento_id']) ?></dd>
            <dt>Tipo</dt>
            <dd><?= h($assinatura['documento_tipo']) ?></dd>
            <dt>Emissão</dt>
            <dd><?= h(!empty($assinatura['data_emissao']) ? date('d/m/Y', strtotime($assinatura['data_emissao'])) : 'Não informada') ?></dd>
            <dt>Situação</dt>
            <dd><?= h($titulo) ?></dd>
            <dt>Responsável técnico</dt>
            <dd><?= h(trim($assinatura['nome_completo'] . ' · ' . $assinatura['cargo_titulo'], ' ·')) ?></dd>
            <dt>Aprovação administrativa</dt>
            <dd><?= h($assinatura['aprovador_nome'] ?: 'Não identificada') ?></dd>
            <dt>Assinatura aplicada por</dt>
            <dd><?= h($assinatura['executor_nome'] ?: $assinatura['nome_completo']) ?></dd>
            <dt>Assinado em</dt>
            <dd><?= h(formatarDataCompleta($assinatura['assinado_em'])) ?></dd>
            <dt>SHA-256 registrado</dt>
            <dd><code><?= h($assinatura['hash_pdf_assinado']) ?></code></dd>
            <dt>SHA-256 recalculado</dt>
            <dd><code><?= h($hashCalculado ?: 'Arquivo indisponível') ?></code></dd>
        </dl>
    <?php endif; ?>
</main>
</body>
</html>
