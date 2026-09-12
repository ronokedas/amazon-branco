<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/apresentacao.php
 * Visualizador e Distribuidor da Apresentação em Slides / PDF de Auditoria
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

$pdfPath = __DIR__ . '/../../docs/APRESENTACAO_SGQ_ISO_9001_AUDITORIA.pdf';
$htmlPath = __DIR__ . '/../../docs/apresentacao_iso_9001.html';

// Se for solicitado diretamente o PDF
if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
    if (file_exists($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Apresentacao_SGQ_ISO_9001_Amazon.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($pdfPath);
        exit;
    }
}

// Se solicitado download forçado
if (isset($_GET['download']) && $_GET['download'] == '1') {
    if (file_exists($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Apresentacao_SGQ_ISO_9001_Amazon.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        exit;
    }
}

// Caso padrão: Exibir os slides HTML interativos
if (file_exists($htmlPath)) {
    // Carregar o HTML dos slides
    include $htmlPath;
    exit;
} else {
    // Redirecionar para o manual caso não exista
    header('Location: ' . APP_URL . 'sgq/manual');
    exit;
}
