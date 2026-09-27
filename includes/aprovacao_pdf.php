<?php

require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

function aprovacaoPdfTextoSeguro($valor): string
{
    return trim((string)$valor);
}

function aprovacaoPdfUrlValidacao(string $token, string $tipoDocumento): string
{
    $base = rtrim((string)APP_URL, '/');
    $host = strtolower((string)parse_url($base, PHP_URL_HOST));
    $scheme = strtolower((string)parse_url($base, PHP_URL_SCHEME));
    $developmentHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    if (!$developmentHost && $scheme !== 'https') {
        throw new RuntimeException('Configure APP_URL com HTTPS antes de aprovar documentos em producao.');
    }
    $rota = strtoupper(trim($tipoDocumento)) === 'RELATORIO_ASSINADO'
        ? '/validar-assinatura/'
        : '/validar/';
    return $base . $rota . rawurlencode($token);
}

function aprovacaoPdfCriarComBloco(string $origem, string $destino, array $a): void
{
    if (!is_file($origem) || filesize($origem) < 200) {
        throw new RuntimeException('PDF original invalido para aprovacao.');
    }

    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Amazon Naval ERP');
    $pdf->SetAuthor('Amazon Naval');
    $pdf->SetTitle('Documento aprovado eletronicamente');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->SetMargins(0, 0, 0);

    $pageCount = $pdf->setSourceFile($origem);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $template = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($template);
        $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
        $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);
    }

    // Certificados e relatorios navais com area reservada na pagina correspondente.
    // Nao criam pagina adicional; o bloco oficial e estampado diretamente na area reservada.
    $tipoDocumento = strtoupper((string)($a['documento_tipo'] ?? ''));
    $tiposBlocoCompacto = ['CSN', 'CNBL', 'CNARQ', 'LC', 'LA', 'LR', 'LCEC', 'LP', 'NAR', 'RELATORIO'];
    if (in_array($tipoDocumento, ['CSN', 'CNBL', 'CNARQ', 'LC', 'LA', 'LR', 'LCEC', 'LP'], true)) {
        $pdf->setPage(1);
    } elseif ($tipoDocumento === 'NAR') {
        $paginaBloco = (int)($a['bloco_pagina'] ?? 3);
        $pdf->setPage(max(1, min($pageCount, $paginaBloco)));
    } elseif ($tipoDocumento === 'RELATORIO') {
        $paginaBloco = (int)($a['bloco_pagina'] ?? $pageCount);
        $pdf->setPage(max(1, min($pageCount, $paginaBloco)));
    } else {
        $pdf->AddPage('P', 'A4');
    }

    if (in_array($tipoDocumento, $tiposBlocoCompacto, true)) {
        $layoutPorDocumento = [
            'CSN'   => ['x' => 15.0, 'y' => 247.0, 'w' => 180.0, 'h' => 39.0],
            'CNBL'  => ['x' => 15.0, 'y' => 252.0, 'w' => 180.0, 'h' => 39.0],
            'CNARQ' => ['x' => 15.0, 'y' => 248.0, 'w' => 180.0, 'h' => 39.0],
            'LC'    => ['x' => 10.0, 'y' => 248.0, 'w' => 190.0, 'h' => 38.0],
            'LA'    => ['x' => 10.0, 'y' => 248.0, 'w' => 190.0, 'h' => 38.0],
            'LR'    => ['x' => 10.0, 'y' => 248.0, 'w' => 190.0, 'h' => 38.0],
            'LCEC'  => ['x' => 10.0, 'y' => 248.0, 'w' => 190.0, 'h' => 38.0],
            'LP'    => ['x' => 15.0, 'y' => 245.0, 'w' => 180.0, 'h' => 38.0],
            'NAR'   => ['x' => 15.0, 'y' => 180.0, 'w' => 180.0, 'h' => 38.0],
        ];

        if ($tipoDocumento === 'RELATORIO') {
            $x = 15.0;
            $y = (float)($a['bloco_y'] ?? 222.0);
            $w = 180.0;
            $h = 49.0;
        } else {
            $cfg = $layoutPorDocumento[$tipoDocumento] ?? ['x' => 15.0, 'y' => 248.0, 'w' => 180.0, 'h' => 38.0];
            $x = $cfg['x'];
            $y = !empty($a['bloco_y']) ? (float)$a['bloco_y'] : $cfg['y'];
            $w = $cfg['w'];
            $h = $cfg['h'];
        }

        $signatureColumn = 42.0;
        $qrColumn = 29.0;
        $padding = 3.0;

        $pdf->SetDrawColor(160, 166, 163);
        $pdf->SetLineWidth(0.25);
        $pdf->Rect($x, $y, $w, $h);
        $pdf->Line($x + $signatureColumn, $y, $x + $signatureColumn, $y + $h);
        $pdf->Line($x + $w - $qrColumn, $y, $x + $w - $qrColumn, $y + $h);

        $signature = (string)($a['assinatura_caminho_absoluto'] ?? '');
        if ($signature !== '' && is_file($signature)) {
            $pdf->Image($signature, $x + 4, $y + 5, 34, 13, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }
        $pdf->SetTextColor(65, 70, 68);
        $pdf->SetFont('helvetica', 'I', 5.1);
        $pdf->SetXY($x + 2, $y + 20.0);
        $pdf->MultiCell($signatureColumn - 4, 2.8, 'Representação visual da assinatura', 0, 'C');

        $rx = $x + $signatureColumn + $padding;
        $rw = $w - $signatureColumn - $qrColumn - (2 * $padding);
        $pdf->SetTextColor(20, 35, 31);
        $pdf->SetXY($rx, $y + 2.0);
        $pdf->SetFont('helvetica', 'B', 6.8);
        $pdf->MultiCell($rw, 3.0, 'DOCUMENTO APROVADO E ASSINADO ELETRONICAMENTE', 0, 'L');

        $lines = [
            'Responsável técnico: ' . aprovacaoPdfTextoSeguro($a['responsavel_nome'] ?? ''),
            'CPF/CNPJ: ' . aprovacaoPdfTextoSeguro($a['responsavel_cpf_cnpj'] ?? ''),
            'Cargo/função: ' . aprovacaoPdfTextoSeguro($a['responsavel_cargo'] ?? ''),
            !empty($a['responsavel_registro']) ? 'Registro profissional: ' . aprovacaoPdfTextoSeguro($a['responsavel_registro']) : null,
            $tipoDocumento === 'RELATORIO'
                ? 'Aprovação administrativa: ' . aprovacaoPdfTextoSeguro($a['aprovador_nome'] ?? '')
                : 'Aprovado por: ' . aprovacaoPdfTextoSeguro($a['aprovador_nome'] ?? ''),
            $tipoDocumento === 'RELATORIO'
                ? 'Assinatura aplicada por: ' . aprovacaoPdfTextoSeguro($a['executor_assinatura_nome'] ?? $a['responsavel_nome'] ?? '')
                : null,
            'Data e hora: ' . aprovacaoPdfTextoSeguro($a['data_hora_formatada'] ?? ''),
            'Geolocalização: ' . aprovacaoPdfTextoSeguro($a['latitude'] ?? '') . ', ' . aprovacaoPdfTextoSeguro($a['longitude'] ?? '') . (!empty($a['geo_precisao_m']) ? ' (precisão ' . aprovacaoPdfTextoSeguro($a['geo_precisao_m']) . ' m)' : ''),
            'Endereço IP: ' . aprovacaoPdfTextoSeguro($a['ip'] ?? ''),
        ];
        $lines = array_values(array_filter($lines, static fn($v) => $v !== null));
        $pdf->SetXY($rx, $y + 5.5);
        $pdf->SetFont('helvetica', '', $tipoDocumento === 'RELATORIO' ? 5.0 : 5.3);
        $pdf->MultiCell($rw, $tipoDocumento === 'RELATORIO' ? 2.15 : 2.35, implode("\n", $lines), 0, 'L');

        $pdf->SetXY($rx, $y + 25.8);
        $pdf->SetFont('helvetica', 'B', 5.0);
        $pdf->Cell($rw, 2.3, 'SHA-256 DO PDF ORIGINAL APROVADO', 0, 1, 'L');
        $pdf->SetX($rx);
        $pdf->SetFont('courier', '', 4.6);
        $pdf->MultiCell($rw, 2.2, aprovacaoPdfTextoSeguro($a['hash_pdf_original'] ?? ''), 0, 'L');

        $tipoValidacao = (string)($a['tipo_validacao'] ?? $tipoDocumento);
        $validationUrl = aprovacaoPdfUrlValidacao((string)$a['token_validacao'], $tipoValidacao);
        $qrStyle = ['border' => 0, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
        $qrX = $x + $w - $qrColumn + (($qrColumn - 20.0) / 2);
        $pdf->write2DBarcode($validationUrl, 'QRCODE,M', $qrX, $y + 4.0, 20, 20, $qrStyle, 'N');
        $pdf->SetFont('helvetica', '', 5.1);
        $pdf->SetTextColor(65, 70, 68);
        $pdf->SetXY($x + $w - $qrColumn + 1, $y + 25.0);
        $pdf->MultiCell($qrColumn - 2, 3, 'Escaneie para validar', 0, 'C');

        if ($tipoDocumento === 'RELATORIO') {
            $pdf->SetTextColor(20, 35, 31);
            $pdf->SetXY($rx, $y + 36.0);
            $pdf->SetFont('helvetica', '', 5.0);
            $urlHtml = '<span>A autenticidade deste documento pode ser conferida em:</span><br>'
                . '<a href="' . htmlspecialchars($validationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                . htmlspecialchars($validationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</a>';
            $pdf->writeHTMLCell($rw, 11.0, $rx, $y + 36.0, $urlHtml, 0, 0, false, true, 'L', true);
        }

        $pdf->Output($destino, 'F');
        return;
    }

    $x = 15.0;
    $y = 222.0;
    $w = 180.0;
    $h = 60.0;
    $left = 52.0;
    $padding = 4.0;

    $pdf->SetDrawColor(160, 166, 163);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->Line($x + $left, $y, $x + $left, $y + $h);

    $tipoValidacao = (string)($a['tipo_validacao'] ?? $tipoDocumento);
    $validationUrl = aprovacaoPdfUrlValidacao((string)$a['token_validacao'], $tipoValidacao);
    $qrStyle = ['border' => 0, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
    $pdf->write2DBarcode($validationUrl, 'QRCODE,M', $x + 14, $y + 4, 24, 24, $qrStyle, 'N');
    $pdf->SetFont('helvetica', '', 6.3);
    $pdf->SetTextColor(65, 70, 68);
    $pdf->SetXY($x + 3, $y + 29);
    $pdf->MultiCell($left - 6, 4, 'Escaneie para validar', 0, 'C');

    $signature = (string)($a['assinatura_caminho_absoluto'] ?? '');
    if ($signature !== '' && is_file($signature)) {
        $pdf->Image($signature, $x + 5, $y + 36, 42, 14, '', '', '', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->SetXY($x + 3, $y + 52);
    $pdf->SetFont('helvetica', 'I', 5.8);
    $pdf->MultiCell($left - 6, 3, 'Representação visual da assinatura', 0, 'C');

    $rx = $x + $left + $padding;
    $rw = $w - $left - (2 * $padding);
    $pdf->SetTextColor(20, 35, 31);
    $pdf->SetXY($rx, $y + 4);
    $pdf->SetFont('helvetica', 'B', 8.7);
    $pdf->MultiCell($rw, 5, 'DOCUMENTO APROVADO E ASSINADO ELETRONICAMENTE', 0, 'L');

    $lines = [
        'Responsável técnico: ' . aprovacaoPdfTextoSeguro($a['responsavel_nome'] ?? ''),
        'CPF/CNPJ: ' . aprovacaoPdfTextoSeguro($a['responsavel_cpf_cnpj'] ?? ''),
        'Cargo/função: ' . aprovacaoPdfTextoSeguro($a['responsavel_cargo'] ?? ''),
        !empty($a['responsavel_registro']) ? 'Registro profissional: ' . aprovacaoPdfTextoSeguro($a['responsavel_registro']) : null,
        'Aprovado por: ' . aprovacaoPdfTextoSeguro($a['aprovador_nome'] ?? ''),
        'Data e hora: ' . aprovacaoPdfTextoSeguro($a['data_hora_formatada'] ?? ''),
        'Geolocalização: ' . aprovacaoPdfTextoSeguro($a['latitude'] ?? '') . ', ' . aprovacaoPdfTextoSeguro($a['longitude'] ?? '') . (!empty($a['geo_precisao_m']) ? ' (precisão ' . aprovacaoPdfTextoSeguro($a['geo_precisao_m']) . ' m)' : ''),
        'Endereço IP: ' . aprovacaoPdfTextoSeguro($a['ip'] ?? ''),
    ];
    $lines = array_values(array_filter($lines, static fn($v) => $v !== null));
    $pdf->SetXY($rx, $y + 13);
    $pdf->SetFont('helvetica', '', 7.1);
    $pdf->MultiCell($rw, 3.6, implode("\n", $lines), 0, 'L');

    $pdf->SetXY($rx, $y + 44);
    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->Cell($rw, 3.5, 'SHA-256 DO PDF ORIGINAL APROVADO', 0, 1, 'L');
    $pdf->SetX($rx);
    $pdf->SetFont('courier', '', 6.1);
    $pdf->MultiCell($rw, 3.2, aprovacaoPdfTextoSeguro($a['hash_pdf_original'] ?? ''), 0, 'L');
    $pdf->Output($destino, 'F');
}
