<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('APP_URL')) {
    define('APP_URL', 'https://example.test');
}

require_once __DIR__ . '/../includes/aprovacao_pdf.php';

use setasign\Fpdi\Tcpdf\Fpdi;

function assertAprovacaoPdfLayout(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'amazon_naval_aprovacao_pdf_' . bin2hex(random_bytes(5));
if (!mkdir($workDir, 0700, true) && !is_dir($workDir)) {
    throw new RuntimeException('Nao foi possivel criar o diretorio temporario do teste.');
}

try {
    $original = $workDir . DIRECTORY_SEPARATOR . 'original.pdf';
    $base = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $base->setPrintHeader(false);
    $base->setPrintFooter(false);
    $base->AddPage();
    $base->SetXY(10, 10);
    $base->Cell(190, 5, 'PAGINA UM', 0, 1);
    $base->AddPage();
    $base->SetXY(10, 10);
    $base->Cell(190, 5, 'PAGINA DOIS', 0, 1);
    $base->Output($original, 'F');

    $context = [
        'token_validacao' => 'token-de-teste',
        'responsavel_nome' => 'Responsavel de Teste',
        'responsavel_cpf_cnpj' => '000.000.000-00',
        'responsavel_cargo' => 'Engenheiro Naval',
        'responsavel_registro' => 'CREA 123',
        'aprovador_nome' => 'Administrador',
        'data_hora_formatada' => '23/07/2026 12:00:00 (America/Sao_Paulo, UTC-03:00)',
        'latitude' => '-1.45580000',
        'longitude' => '-48.49020000',
        'geo_precisao_m' => '10.00',
        'ip' => '127.0.0.1',
        'hash_pdf_original' => hash_file('sha256', $original),
    ];

    foreach (['CSN', 'CNBL', 'CNARQ'] as $type) {
        $destination = $workDir . DIRECTORY_SEPARATOR . strtolower($type) . '.pdf';
        aprovacaoPdfCriarComBloco($original, $destination, $context + ['documento_tipo' => $type]);
        $reader = new Fpdi();
        assertAprovacaoPdfLayout($reader->setSourceFile($destination) === 2, $type . ' ganhou uma pagina adicional.');
    }

    $reportDestination = $workDir . DIRECTORY_SEPARATOR . 'relatorio.pdf';
    aprovacaoPdfCriarComBloco($original, $reportDestination, $context + [
        'documento_tipo' => 'RELATORIO',
        'bloco_pagina' => 1,
        'bloco_y' => 150.0,
    ]);
    $reader = new Fpdi();
    assertAprovacaoPdfLayout($reader->setSourceFile($reportDestination) === 2, 'O relatorio ganhou uma pagina exclusiva para a assinatura.');

    $otherDestination = $workDir . DIRECTORY_SEPARATOR . 'outro.pdf';
    aprovacaoPdfCriarComBloco($original, $otherDestination, $context + ['documento_tipo' => 'LP']);
    $reader = new Fpdi();
    assertAprovacaoPdfLayout($reader->setSourceFile($otherDestination) === 3, 'O comportamento dos demais documentos foi alterado.');

    echo "aprovacao_pdf_layout_test: OK\n";
} finally {
    unset($reader, $base);
    gc_collect_cycles();
    foreach (glob($workDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    if (is_dir($workDir)) {
        @rmdir($workDir);
    }
}
