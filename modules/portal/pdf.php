<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$tipo = strtolower(trim($_GET['tipo'] ?? ''));
$id = trim($_GET['id'] ?? '');
$acao = ($_GET['acao'] ?? 'visualizar') === 'download' ? 'download' : 'visualizar';
$evento = $acao === 'download' ? 'DOWNLOAD' : 'VISUALIZACAO';
$doc = clientePortalDocumento($pdo, (string)clientePortalId(), $tipo, $id);

if (!$doc) {
    clientePortalAuditar($pdo, $evento, null, null, $tipo, $id, false, 'Documento inexistente ou sem vínculo ativo.');
    http_response_code(403);
    die('Documento não encontrado ou sem permissão de acesso.');
}

$configs = clientePortalConfigDocumentos();
if (isset($configs[$tipo])) {
    $rel = $configs[$tipo]['pdf'];
} elseif ($tipo === 'rel_vistoria') {
    $rel = 'vistorias/relatorio_pdf';
} elseif ($tipo === 'parecer_planos') {
    $rel = 'analises_planos/parecer_pdf';
} else {
    http_response_code(404);
    die('Tipo de documento inválido.');
}

$tmp = null;
try {
    $arquivo = clientePortalPdfOficial($pdo, $tipo, $id, $doc);

    if ($arquivo === null) {
        $script = __DIR__ . '/../' . $rel . '.php';
        if (!is_file($script)) {
            throw new RuntimeException('Gerador de PDF não encontrado.');
        }

        $tmpDir = __DIR__ . '/../../tmp/portal';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0750, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Falha ao preparar o documento.');
        }
        $tmp = $tmpDir . '/' . bin2hex(random_bytes(16)) . '.pdf';
        $_GET = ['id' => $id];
        $salvar_pdf_caminho = $tmp;
        $return_pdf_string = false;
        ob_start();
        require $script;
        ob_end_clean();
        if (!is_file($tmp) || filesize($tmp) < 200) {
            throw new RuntimeException('Não foi possível gerar o PDF.');
        }
        $arquivo = $tmp;
    }

    clientePortalAuditar($pdo, $evento, null, $doc['embarcacao_id'], $tipo, $id, true, $tmp === null ? 'Artefato oficial assinado.' : 'PDF de documento ainda não assinado.');
    $nome = preg_replace('/[^A-Za-z0-9._-]/', '-', ($doc['tipo_label'] ?? 'Documento') . '-' . ($doc['numero'] ?? $id)) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($acao === 'download' ? 'attachment' : 'inline') . '; filename="' . $nome . '"');
    header('Content-Length: ' . filesize($arquivo));
    header('Cache-Control: private, no-store');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($arquivo);
} catch (Throwable $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    clientePortalAuditar($pdo, $evento, null, $doc['embarcacao_id'], $tipo, $id, false, $e->getMessage());
    error_log('Erro PDF portal: ' . $e->getMessage());
    http_response_code(500);
    echo 'Não foi possível disponibilizar o documento oficial. Entre em contato com o suporte.';
} finally {
    if ($tmp !== null && is_file($tmp)) {
        @unlink($tmp);
    }
}
exit;
