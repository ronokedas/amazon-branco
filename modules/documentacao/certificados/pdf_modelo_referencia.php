<?php

if (!class_exists('CsnModeloReferenciaPdf')) {
    class CsnModeloReferenciaPdf extends CertificadoPdfComMarcaDagua
    {
        public function Header() {}
        public function Footer() {}
    }
}

$txt = static function ($v): string {
    $texto = trim((string)($v ?? ''));
    for ($tentativa = 0; $tentativa < 2 && preg_match('/(?:Ã.|Â.|â€|ðŸ)/u', $texto); $tentativa++) {
        $corrigido = @mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');
        if ($corrigido === '' || !preg_match('//u', $corrigido)) break;
        $texto = $corrigido;
    }
    return trim($texto);
};
$dataBr = static fn($v): string => $v ? date('d/m/Y', strtotime((string)$v)) : '';
$dataExtenso = static function ($v): string {
    if (!$v) return '';
    $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $d = new DateTime((string)$v);
    return $d->format('d') . ' de ' . $meses[(int)$d->format('n')] . ' de ' . $d->format('Y');
};
$numero = $txt($c['numero']);
$numeroCabecalho = str_starts_with($numero, 'AM-CSN') ? $numero : 'AM-CSN - ' . $numero;
$nome = $txt($c['nome_embarcacao']);
$emitente = 'AMAZON NAVAL';
$tipoCertificado = mb_strtoupper($txt($c['tipo']), 'UTF-8');
$tipoCertificadoNormalizado = mb_strtolower($tipoCertificado, 'UTF-8');
$isDefinitivo = str_contains($tipoCertificadoNormalizado, 'definitivo');
$motor = $txt($c['fabricante_motor']);
$potencia = $txt($c['potencia_kw']);
$propulsaoInformada = array_key_exists('possui_propulsao', $origem) && $origem['possui_propulsao'] !== null;
$semPropulsao = $propulsaoInformada ? (int)$origem['possui_propulsao'] === 0 : $motor === '';
$motorPdf = $semPropulsao ? 'Embarcação sem Propulsão' : ($motor ?: 'NÃO INFORMADO');
$potenciaPdf = $semPropulsao ? 'NÃO SE APLICA' : ($potencia ?: 'NÃO INFORMADA');
$validadePdf = $txt($c['data_validade']);
$prazoRelatorio = (int)($origem['prazo_exigencias_dias'] ?? 0);
if (in_array($prazoRelatorio, [60, 90], true) && !empty($origem['data_vistoria'])) {
    $dataBaseValidade = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$origem['data_vistoria']);
    if ($dataBaseValidade) $validadePdf = $dataBaseValidade->modify('+' . $prazoRelatorio . ' days')->format('Y-m-d');
}

$pdfRef = new CsnModeloReferenciaPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdfRef->SetCreator(APP_NAME);
$pdfRef->SetAuthor('Amazon Naval Ltda');
$pdfRef->SetTitle('Certificado CSN - ' . $numero);
$pdfRef->setPrintHeader(false);
$pdfRef->setPrintFooter(false);
$pdfRef->SetMargins(0, 0, 0);
$pdfRef->SetAutoPageBreak(false, 0);
$pdfRef->SetDrawColor(0, 0, 0);
$pdfRef->SetTextColor(0, 0, 0);
$pdfRef->SetLineWidth(0.45);

$cellRef = static function ($x, $y, $w, $h, $texto = '', $border = 1, $align = 'C', $style = '', $size = 9) use ($pdfRef, $txt): void {
    $pdfRef->SetFont('helvetica', $style, $size);
    $pdfRef->SetXY($x, $y);
    $pdfRef->MultiCell($w, $h, $txt($texto), $border, $align, false, 0, '', '', true, 0, false, true, $h, 'M');
};
$contem = static function ($origemTexto, array $termos): bool {
    $origemTexto = mb_strtolower((string)$origemTexto, 'UTF-8');
    foreach ($termos as $termo) {
        if (str_contains($origemTexto, mb_strtolower($termo, 'UTF-8'))) return true;
    }
    return false;
};
$fmtNumero = static function ($valor): string {
    if ($valor === null || $valor === '') return '';
    return is_numeric($valor) ? number_format((float)$valor, 2, ',', '.') : trim((string)$valor);
};

$tipoNav = $txt($c['tipo_navegacao'] ?? $origem['tipo_navegacao'] ?? '');
$areaNav = $txt($c['area_navegacao'] ?? $origem['cnbl_area_navegacao'] ?? $origem['area_navegacao'] ?? '');
$marAberto = $contem($tipoNav . ' ' . $areaNav, ['mar aberto', 'longo curso', 'cabotagem', 'apoio marítimo']);
$interior = $contem($tipoNav . ' ' . $areaNav, ['interior', 'área 1', 'area 1', 'área 2', 'area 2']);
$area1 = $contem($areaNav, ['área 1', 'area 1']);
$area2 = $contem($areaNav, ['área 2', 'area 2']);
$x = 10.5;
$w = 189;

// Página 1 - frente
$pdfRef->AddPage();
$cellRef($x, 14, 73, 5, 'CERTIFICADO ' . $numeroCabecalho, 1, 'C', 'B', 9);
$pdfRef->SetFont('helvetica', 'BU', 11.5);
$pdfRef->SetXY(61, 22);
$pdfRef->Cell(104, 7, 'CERTIFICADO DE SEGURANÇA DA NAVEGAÇÃO', 0, 0, 'C');
$pdfRef->SetFont('helvetica', 'BI', 10);
$pdfRef->SetXY(166, 22.5);
$pdfRef->Cell(32, 6, '(' . $tipoCertificado . ')', 0, 0, 'C');

$brasaoRef = __DIR__ . '/../../../assets/img/brasao.png';
if (is_file($brasaoRef)) $pdfRef->Image($brasaoRef, 22, 25, 31, 31, 'PNG', '', '', true, 150);
$pdfRef->SetFont('helvetica', 'B', 13);
$pdfRef->SetXY(66, 30); $pdfRef->Cell(100, 6, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdfRef->SetXY(66, 36); $pdfRef->Cell(100, 6, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdfRef->SetXY(66, 42); $pdfRef->Cell(100, 6, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdfRef->SetFont('helvetica', 'B', 11);
$pdfRef->SetXY(66, 51); $pdfRef->Cell(100, 6, $emitente, 0, 1, 'C');

$y = 60;
$cellRef($x, $y, 87, 8, 'Nome da Embarcação', 1, 'C', '', 9);
$cellRef($x + 87, $y, 51, 8, 'Nº de Inscrição', 1, 'C', '', 9);
$cellRef($x + 138, $y, 51, 8, 'Indicativo de Chamada', 1, 'C', '', 9);
$cellRef($x, $y + 8, 87, 13, $nome ?: 'Não Fornecido', 1, 'C', 'B', 9);
$cellRef($x + 87, $y + 8, 51, 13, $txt($c['numero_inscricao']) ?: 'Não Fornecido', 1, 'C', 'B', 9);
$cellRef($x + 138, $y + 8, 51, 13, $txt($c['indicativo_chamada']) ?: 'x-x-x', 1, 'C', 'B', 9);

$y = 85;
$cellRef($x, $y, 87, 8, 'Atividades ou Serviços', 1, 'C', '', 9);
$cellRef($x + 87, $y, 51, 8, 'Tipo de Embarcação', 1, 'C', '', 9);
$cellRef($x + 138, $y, 51, 8, 'Ano de Construção', 1, 'C', '', 9);
$cellRef($x, $y + 8, 87, 13, $txt($c['atividades_servicos'] ?? $origem['tipo_servico'] ?? ''), 1, 'C', 'B', 8);
$cellRef($x + 87, $y + 8, 51, 13, $txt($c['tipo_embarcacao'] ?? $origem['tipo_embarcacao_nome'] ?? ''), 1, 'C', 'B', 8);
$cellRef($x + 138, $y + 8, 51, 13, $txt($c['ano_construcao']), 1, 'C', 'B', 9);

$y = 110;
$cellRef($x, $y, 43.5, 8, 'Comprimento (m)', 1, 'C', '', 9);
$cellRef($x + 43.5, $y, 43.5, 8, 'Arqueação Bruta', 1, 'C', '', 9);
$cellRef($x + 87, $y, 51, 8, 'Tipo de Navegação', 1, 'C', '', 9);
$cellRef($x + 138, $y, 51, 8, 'Área de Navegação', 1, 'C', '', 9);
$cellRef($x, $y + 8, 43.5, 28, $fmtNumero($c['comprimento_m']), 1, 'C', 'B', 9);
$cellRef($x + 43.5, $y + 8, 43.5, 28, $txt($c['arqueacao_bruta']), 1, 'C', 'B', 9);
$cellRef($x + 87, $y + 8, 51, 14, 'MAR ABERTO' . ($marAberto ? '  X' : '') . "\n" . 'INTERIOR' . ($interior ? '  X' : ''), 1, 'L', '', 8);
$cellRef($x + 87, $y + 22, 51, 14, 'APOIO PORTUÁRIO', 1, 'L', '', 8);
$cellRef($x + 138, $y + 8, 51, 28, 'Longo Curso' . ($contem($areaNav, ['longo curso']) ? '  X' : '') . "\n" . 'Cabotagem' . ($contem($areaNav, ['cabotagem']) ? '  X' : '') . "\n" . 'Apoio Marítimo' . ($contem($areaNav, ['apoio marítimo']) ? '  X' : '') . "\n" . 'Área 1' . ($area1 ? '  X' : '') . "\n" . 'Área 2' . ($area2 ? '  X' : ''), 1, 'L', '', 7);

$y = 150;
$cellRef($x, $y, 138, 8, 'Fabricante, Modelo e Número do Motor', 1, 'C', '', 9);
$cellRef($x + 138, $y, 51, 8, "Potência Propulsiva\nTotal (kW)", 1, 'C', '', 8);
$cellRef($x, $y + 8, 138, 13, $motorPdf, 1, 'C', 'B', 8);
$cellRef($x + 138, $y + 8, 51, 13, $potenciaPdf, 1, 'C', 'B', 9);

$y = 175;
$cellRef($x, $y, 87, 9, 'Material do Casco', 1, 'C', '', 9);
$cellRef($x + 87, $y, 51, 9, "Autorizado a Transportar\nCarga no Convés", 1, 'C', '', 8);
$cellRef($x + 138, $y, 51, 9, "Quantidade Autorizada\nde Passageiros", 1, 'C', '', 8);
$cellRef($x, $y + 9, 87, 13, $txt($c['material_casco']), 1, 'C', 'B', 9);
$cellRef($x + 87, $y + 9, 51, 6.5, 'SIM' . ((int)$c['autorizado_carga'] === 1 ? '  X' : ''), 1, 'L', 'B', 8);
$cellRef($x + 87, $y + 15.5, 51, 6.5, 'NÃO' . ((int)$c['autorizado_carga'] !== 1 ? '  X' : ''), 1, 'L', 'B', 8);
$cellRef($x + 138, $y + 9, 25.5, 13, (string)($c['qtd_passageiros'] ?? '0'), 1, 'C', 'B', 9);
$cellRef($x + 163.5, $y + 9, 25.5, 13, $txt($c['obs_passageiros']) ?: 'Vide verso', 1, 'C', 'B', 8);

$pdfRef->SetFont('helvetica', 'B', 9);
$pdfRef->SetXY($x + 1, 201); $pdfRef->Cell(70, 5, 'A ' . $emitente . ' certifica:', 0, 1, 'L');
$pdfRef->SetFont('helvetica', '', 8.5);
$pdfRef->SetXY($x + 1, 207);
$pdfRef->MultiCell($w - 2, 15, $txt('Que a embarcação objeto de "' . $nome . '" foi objeto de vistoria de ' . ($txt($c['tipo_vistoria_certificado']) ?: 'EMISSÃO') . "\nem conformidade com as disposições regulamentadas pela NORMAM 202 da Diretoria de Portos e Costas.\nA embarcação cumpre os requisitos de acessibilidade para o transporte coletivo aquaviário de passageiros. " . ((int)$c['acessibilidade_sim'] === 1 ? 'SIM X   NÃO' : 'SIM   NÃO X')), 0, 'L');
$pdfRef->SetXY($x + 1, 224);
$pdfRef->MultiCell($w - 2, 12, "As vistorias evidenciaram que seu estado é satisfatório e que cumpre com as prescrições indicadas.\nO presente Certificado será válido até o vencimento indicado, estando sujeito à realização das vistorias anuais e intermediária.", 0, 'L');
$pdfRef->SetFont('helvetica', 'B', 9);
$pdfRef->SetXY($x, 239); $pdfRef->Cell($w, 6, 'Emitido em ' . $txt($c['local_emissao']) . ', em ' . $dataExtenso($c['data_emissao']), 0, 1, 'C');

// Página 2 - verso
$pdfRef->AddPage();
$pdfRef->SetFont('helvetica', 'B', 12);
$pdfRef->SetXY($x, 7); $pdfRef->Cell($w, 7, 'CONVALIDAÇÕES', 0, 1, 'C');
$pdfRef->SetFont('helvetica', '', 9);
$pdfRef->SetXY($x + 1, 18);
$pdfRef->MultiCell($w - 2, 12, 'Certifica-se que a embarcação "' . $nome . '" foi objeto das vistorias a seguir estabelecidas, com resultado satisfatório, nos setores e datas indicadas, respectivamente.', 0, 'L');

$y = 33;
$cols = [44, 29, 36, 36, 44];
$titulos = ['A REALIZAR', 'ENTRE', 'E', "LUGAR E DATA DA\nREALIZAÇÃO", 'VISTORIADOR'];
$cx = $x;
foreach ($cols as $i => $cw) { $cellRef($cx, $y, $cw, 11, $titulos[$i], 1, 'C', 'B', 8); $cx += $cw; }
$convPorNumero = certificadoConvalidacoesPorNumero($convalidacoes);
$tipoEmbarcacaoConvalidacoes = $txt($c['tipo_embarcacao'] ?? $origem['tipo_embarcacao_nome'] ?? $origem['embarcacao_tipo'] ?? '');
$quantidadeConvalidacoes = $isDefinitivo
    ? certificadoAnosValidadePorTipoEmbarcacao($tipoEmbarcacaoConvalidacoes) - 1
    : 4;
$alturaConvalidacao = $quantidadeConvalidacoes > 4 ? 8 : 16;
$fonteConvalidacao = $quantidadeConvalidacoes > 4 ? 7 : 8;
for ($i = 1; $i <= $quantidadeConvalidacoes; $i++) {
    $conv = $convPorNumero[$i] ?? [];
    $ry = $y + 11 + (($i - 1) * $alturaConvalidacao);
    $valores = [$i . 'ª VIST. ANUAL', $dataBr($conv['data_inicio'] ?? ''), $dataBr($conv['data_fim'] ?? '') ?: 'xx/xx/xxxx', $txt($conv['local_data'] ?? ''), $txt($conv['vistoriador'] ?? '')];
    $cx = $x;
    foreach ($cols as $j => $cw) { $cellRef($cx, $ry, $cw, $alturaConvalidacao, $valores[$j], 1, 'C', $j === 0 ? 'B' : '', $fonteConvalidacao); $cx += $cw; }
}

$distMap = [];
foreach ($distribuicao as $d) if (!empty($d['item_codigo'])) $distMap[$d['item_codigo']] = $d;
$fimConvalidacoes = $y + 11 + ($quantidadeConvalidacoes * $alturaConvalidacao);
$y = max(112, $fimConvalidacoes + 4);
$cellRef($x, $y, 94.5, 5, 'DISTRIBUIÇÃO DE PASSAGEIROS', 1, 'C', 'B', 8);
$cellRef($x + 94.5, $y, 94.5, 5, 'DISTRIBUIÇÃO DE CARGAS', 1, 'C', 'B', 8);
$cellRef($x, $y + 5, 72.5, 5, 'LOCAL', 1, 'C', '', 8);
$cellRef($x + 72.5, $y + 5, 22, 5, 'QUANTIDADE', 1, 'C', '', 7);
$cellRef($x + 94.5, $y + 5, 72.5, 5, 'LOCAL', 1, 'C', '', 8);
$cellRef($x + 167, $y + 5, 22, 5, 'QUANTIDADE', 1, 'C', '', 7);
$passageiros = ['passageiros_sentados', 'passageiros_camarote', 'passageiros_redes', 'passageiros_em_pe'];
$cargas = ['porao_carga_01', 'paiol_casco', 'almoxarifado_conves_principal', 'deposito_conves_principal'];
for ($i = 0; $i < 5; $i++) {
    $py = $y + 10 + ($i * 5);
    $p = $i < 4 ? ($distMap[$passageiros[$i]] ?? []) : [];
    $cg = $i < 4 ? ($distMap[$cargas[$i]] ?? []) : [];
    $cellRef($x, $py, 72.5, 5, $txt($p['local_nome'] ?? ''), 1, 'L', '', 7);
    $cellRef($x + 72.5, $py, 22, 5, $txt($p['conves_principal'] ?? $p['quantidade'] ?? ''), 1, 'C', '', 7);
    $cellRef($x + 94.5, $py, 72.5, 5, $txt($cg['local_nome'] ?? ''), 1, 'L', '', 7);
    $cellRef($x + 167, $py, 22, 5, $txt($cg['conves_principal'] ?? $cg['quantidade'] ?? ''), 1, 'C', '', 7);
}

$yObs = max(151, $y + 39);
$alturaObservacoes = max(20, 190 - $yObs);
$pdfRef->Rect($x, $yObs, $w, $alturaObservacoes);
$pdfRef->SetFont('helvetica', 'B', 8);
$pdfRef->SetXY($x + 1, $yObs + 1); $pdfRef->Cell(30, 4, 'Observações:', 0, 1, 'L');
$observacoes = $txt($c['observacoes_verso']);
if ($observacoes === '') {
    $observacoes = '1. Este Certificado ' . ucfirst(mb_strtolower($txt($c['tipo']), 'UTF-8')) . ' foi emitido com base no Relatório de Vistorias n.º ' . $txt($c['relatorio_numero']) . ".\n";
    $observacoes .= '2. Vistoria flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . $dataBr($c['data_vistoria_flutuando']) . ' em ' . $txt($c['local_vistoria']) . '.';
}
$pdfRef->SetFont('helvetica', '', 8);
$pdfRef->SetXY($x + 1, $yObs + 7); $pdfRef->MultiCell($w - 2, max(20, $alturaObservacoes - 9), $observacoes, 0, 'L');
$cellRef($x, 193, $w, 7, 'VISTORIA EM SECO REALIZADA EM: ' . $dataExtenso($c['data_vistoria_seco']), 1, 'C', 'B', 8);
$cellRef($x, 202, $w, 7, 'VÁLIDO ATÉ: ' . $dataExtenso($validadePdf), 1, 'C', 'B', 8);
$cellRef($x, 212, 73, 6, 'ANEXO 8-C - NORMAM 202/DPC', 1, 'C', 'B', 8);

$nomeArquivoRef = 'CSN_' . str_replace('/', '-', $numero) . '.pdf';
if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdfRef->Output($salvar_pdf_caminho, 'F');
    return;
}
$pdfRef->Output($nomeArquivoRef, 'I');
exit;
