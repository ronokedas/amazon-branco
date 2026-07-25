<?php
/**
 * MÓDULO: Documentação > Certificados CSN
 * Geração de PDF — Certificado de Segurança da Navegação (Anexo 10-E)
 */

require_once __DIR__ . '/../../../config.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

require_once __DIR__ . '/../../../includes/functions.php';

if (!empty($token_publico)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_csn WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die("Certificado não encontrado.");
    }
    $id = $row['id'];
} elseif (empty($id)) {
    die("ID ou token não informado.");
}

$stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Certificado não encontrado.");
}

$stmt_revisao_conv = $pdo->prepare("SELECT MAX(atualizado_em) FROM csn_convalidacoes WHERE certificado_id = :id");
$stmt_revisao_conv->execute([':id' => $id]);
$convalidacoes_revisadas_em = $stmt_revisao_conv->fetchColumn();
$convalidacoes_revisadas = !empty($convalidacoes_revisadas_em)
    && !empty($c['assinatura_em'])
    && strtotime($convalidacoes_revisadas_em) > strtotime($c['assinatura_em']);

if (!isset($salvar_pdf_caminho) && !$convalidacoes_revisadas && (int)$c['assinado'] === 1 && !empty($c['caminho_arquivo_pdf'])) {
    $caminho_fisico = __DIR__ . '/../../../' . $c['caminho_arquivo_pdf'];
    if (file_exists($caminho_fisico)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($caminho_fisico) . '"');
        header('Content-Length: ' . filesize($caminho_fisico));
        readfile($caminho_fisico);
        exit;
    }
}

$stmt_dist = $pdo->prepare("SELECT * FROM csn_distribuicao_passageiros WHERE certificado_id = :cert_id ORDER BY id");
$stmt_dist->execute([':cert_id' => $id]);
$distribuicao = $stmt_dist->fetchAll(PDO::FETCH_ASSOC);

$stmt_conv = $pdo->prepare("SELECT * FROM csn_convalidacoes WHERE certificado_id = :cert_id ORDER BY id");
$stmt_conv->execute([':cert_id' => $id]);
$convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

$origem = [];
if (!empty($c['vistoria_id'])) {
    $stmt_origem = $pdo->prepare("
        SELECT e.tipo_servico, e.cnbl_area_navegacao, e.area_navegacao, e.tipo_navegacao,
               e.possui_propulsao, e.fabricante_motor, e.modelo_motor, e.numero_motor, e.potencia_kw,
               v.data_vistoria, v.prazo_exigencias_dias,
               e.tipo_embarcacao AS embarcacao_tipo, te.nome AS tipo_embarcacao_nome
        FROM vistorias v
        JOIN embarcacoes e ON e.id = v.embarcacao_id
        LEFT JOIN tipos_embarcacao te ON te.id = e.tipo_embarcacao_id
        WHERE v.id = :vistoria_id
        LIMIT 1
    ");
    $stmt_origem->execute([':vistoria_id' => $c['vistoria_id']]);
    $origem = $stmt_origem->fetch(PDO::FETCH_ASSOC) ?: [];
}

$autoload_path = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Autoloader do Composer não encontrado.");
}
require_once $autoload_path;

// O modelo visual solicitado é o Anexo 8-C da NORMAM-202/DPC, conforme
// docs/exemplos/exemplo-pdf-CSN.pdf. O renderizador isolado abaixo preserva
// este arquivo como compatibilidade para certificados antigos.
require __DIR__ . '/pdf_modelo_referencia.php';
return;

function csnText($valor): string {
    return trim((string)($valor ?? ''));
}

function csnDataBR($data): string {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

function csnDataExtensoPartes($data): array {
    if (empty($data)) return ['', '', ''];
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return [$dt->format('d'), $meses[(int)$dt->format('n')], $dt->format('Y')];
}

function csnFmtNum($valor): string {
    if ($valor === null || $valor === '') return '';
    if (is_numeric($valor)) {
        return number_format((float)$valor, 2, ',', '.');
    }
    return csnText($valor);
}

function csnImgOK($path): bool {
    return file_exists($path) && filesize($path) > 100;
}

function csnCheck(bool $marcado): string {
    return $marcado ? 'X' : '';
}

function csnContem($origem, array $termos): bool {
    $texto = mb_strtolower((string)$origem, 'UTF-8');
    foreach ($termos as $termo) {
        if (strpos($texto, mb_strtolower($termo, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

function csnConverterImagemParaJpeg($dados) {
    if (!function_exists('imagecreatefromstring')) return $dados;
    $img = @imagecreatefromstring($dados);
    if ($img === false) return $dados;
    $w = imagesx($img);
    $h = imagesy($img);
    $nova = imagecreatetruecolor($w, $h);
    $branco = imagecolorallocate($nova, 255, 255, 255);
    imagefill($nova, 0, 0, $branco);
    imagecopy($nova, $img, 0, 0, 0, 0, $w, $h);
    ob_start();
    imagejpeg($nova, null, 90);
    $jpeg = ob_get_clean();
    imagedestroy($img);
    imagedestroy($nova);
    return $jpeg;
}

if (!class_exists('CertificadoCSN')) {
    class CertificadoCSN extends TCPDF {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new CertificadoCSN('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CSN - ' . $c['numero']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$left = 24;
$right = 196;
$width = $right - $left;
$line = 0.2;

$cell = function($x, $y, $w, $h, $txt = '', $border = 1, $align = 'C', $font = ['', '', 9], $fill = false) use ($pdf) {
    [$family, $style, $size] = $font;
    $pdf->SetFont($family ?: 'helvetica', $style, $size);
    $pdf->SetXY($x, $y);
    $pdf->MultiCell($w, $h, $txt, $border, $align, $fill, 0, '', '', true, 0, false, true, $h, 'M');
};

$lineText = function($x, $y, $w, $label, $value, $fontSize = 9) use ($pdf) {
    $pdf->SetFont('helvetica', '', $fontSize);
    $pdf->SetXY($x, $y);
    $pdf->Cell(0, 5, $label, 0, 0, 'L');
    $labelWidth = $pdf->GetStringWidth($label) + 1;
    $pdf->Line($x + $labelWidth, $y + 4.2, $x + $w, $y + 4.2);
    if ($value !== '') {
        $pdf->SetXY($x + $labelWidth + 1, $y - 0.2);
        $pdf->Cell($w - $labelWidth - 1, 5, $value, 0, 0, 'L');
    }
};

$drawFooter = function($pageLabel) {};

$emitente = csnText($c['emitente'] ?? '');
$atividadesServicos = csnText($c['atividades_servicos'] ?? '') ?: csnText($origem['tipo_servico'] ?? '');
$tipoEmbarcacao = csnText($c['tipo_embarcacao'] ?? '') ?: csnText($origem['tipo_embarcacao_nome'] ?? $origem['embarcacao_tipo'] ?? '');
$tipoNav = csnText($c['tipo_navegacao'] ?? '') ?: csnText($origem['tipo_navegacao'] ?? '');
$areaNav = csnText($origem['cnbl_area_navegacao'] ?? '') ?: (csnText($c['area_navegacao'] ?? '') ?: csnText($origem['area_navegacao'] ?? ''));
$isMarAberto = csnContem($tipoNav . ' ' . $areaNav, ['mar aberto', 'longo curso', 'cabotagem', 'costeiro', 'apoio marítimo']);
$isInterior = csnContem($tipoNav . ' ' . $areaNav, ['interior', 'área 1', 'area 1', 'área 2', 'area 2']);
$isArea1 = csnContem($areaNav, ['área 1', 'area 1']);
$isArea2 = csnContem($areaNav, ['área 2', 'area 2']);
$normam = csnText($c['normam_aplicavel'] ?? '');
$tipoVistoria = csnText($c['tipo_vistoria_certificado'] ?? '');
[$diaEmissao, $mesEmissao, $anoEmissao] = csnDataExtensoPartes($c['data_emissao'] ?? '');

// Página 1
$pdf->AddPage();
$pdf->SetLineWidth($line);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetXY(160, 14);
$pdf->Cell(35, 5, 'NORMAM-202/DPC', 0, 0, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(61, 20);
$pdf->Cell(85, 5, 'CERTIFICADO DE SEGURANÇA DA NAVEGAÇÃO', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(142, 28);
$pdf->Cell(22, 5, 'NÚMERO', 0, 0, 'R');
$pdf->Line(166, 32.5, 196, 32.5);
$pdf->SetXY(167, 27.8);
$pdf->Cell(28, 5, csnText($c['numero']), 0, 0, 'C');

$brasao = __DIR__ . '/../../../assets/img/brasao.png';
if (csnImgOK($brasao)) {
    $pdf->Image($brasao, 31, 37, 32, 32, 'PNG', '', '', true, 150);
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(68, 39);
$pdf->Cell(118, 8, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 130);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetXY(93, 51);
$pdf->Cell(68, 6, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(91, 61);
$pdf->Cell(72, 5, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->Line(73, 77, 147, 77);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetXY(98, 78);
$pdf->Cell(25, 4, 'EMITENTE', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY(74, 72);
$pdf->Cell(72, 5, $emitente, 0, 0, 'C');

$y = 85;
$cell($left, $y, 86, 5, 'Nome da Embarcação', 1, 'C', ['helvetica', '', 9]);
$cell($left + 86, $y, 43, 5, 'Nº de Inscrição', 1, 'C', ['helvetica', '', 9]);
$cell($left + 129, $y, 43, 5, 'Indicativo de Chamada', 1, 'C', ['helvetica', '', 9]);
$cell($left, $y + 5, 86, 7, csnText($c['nome_embarcacao']), 1, 'C', ['helvetica', 'B', 9]);
$cell($left + 86, $y + 5, 43, 7, csnText($c['numero_inscricao']), 1, 'C', ['helvetica', '', 9]);
$cell($left + 129, $y + 5, 43, 7, csnText($c['indicativo_chamada']), 1, 'C', ['helvetica', '', 9]);

$y = 100;
$cell($left, $y, 86, 5, 'Atividades ou Serviços', 1, 'C', ['helvetica', '', 9]);
$cell($left + 86, $y, 43, 5, 'Tipo de Embarcação', 1, 'C', ['helvetica', '', 9]);
$cell($left + 129, $y, 43, 5, 'Ano de Construção', 1, 'C', ['helvetica', '', 9]);
$cell($left, $y + 5, 86, 7, $atividadesServicos, 1, 'C', ['helvetica', '', 8]);
$cell($left + 86, $y + 5, 43, 7, $tipoEmbarcacao, 1, 'C', ['helvetica', '', 8]);
$cell($left + 129, $y + 5, 43, 7, csnText($c['ano_construcao']), 1, 'C', ['helvetica', '', 8]);

$y = 115;
$cell($left, $y, 44, 5, 'Comprimento', 1, 'C', ['helvetica', '', 9]);
$cell($left + 44, $y, 43, 5, 'Arqueação Bruta', 1, 'C', ['helvetica', '', 9]);
$cell($left + 87, $y, 85, 5, 'Área de navegação', 1, 'C', ['helvetica', '', 9]);
$cell($left, $y + 5, 44, 22, csnFmtNum($c['comprimento_m']), 1, 'C', ['helvetica', '', 9]);
$cell($left + 44, $y + 5, 43, 22, csnText($c['arqueacao_bruta']), 1, 'C', ['helvetica', '', 9]);
$cell($left + 87, $y + 5, 85, 10, csnCheck($isMarAberto) . ' MAR ABERTO', 1, 'L', ['helvetica', '', 9]);
$cell($left + 87, $y + 15, 43, 12, csnCheck($isInterior) . ' INTERIOR', 1, 'L', ['helvetica', '', 9]);
$cell($left + 130, $y + 15, 42, 6, csnCheck($isArea1) . ' ÁREA 1', 1, 'L', ['helvetica', '', 9]);
$cell($left + 130, $y + 21, 42, 6, csnCheck($isArea2) . ' ÁREA 2', 1, 'L', ['helvetica', '', 9]);

$y = 147;
$cell($left, $y, 86, 5, 'Fabricante, Modelo e Número do Motor', 1, 'C', ['helvetica', '', 9]);
$cell($left + 86, $y, 86, 5, 'Potência Propulsiva Total (kW)', 1, 'C', ['helvetica', '', 9]);
$cell($left, $y + 5, 86, 10, csnText($c['fabricante_motor']), 1, 'C', ['helvetica', '', 8]);
$cell($left + 86, $y + 5, 86, 10, csnText($c['potencia_kw']), 1, 'C', ['helvetica', '', 8]);

$y = 166;
$cell($left, $y, 44, 12, 'Material do Casco', 1, 'C', ['helvetica', '', 9]);
$cell($left + 44, $y, 43, 12, "Autorizado a Transportar\nCarga no Convés", 1, 'C', ['helvetica', '', 9]);
$cell($left + 87, $y, 85, 12, 'Quantidade Autorizada de Passageiros (4)', 1, 'C', ['helvetica', '', 9]);
$cell($left, $y + 12, 44, 16, csnText($c['material_casco']), 1, 'C', ['helvetica', '', 8]);
$cell($left + 44, $y + 12, 43, 8, csnCheck((int)$c['autorizado_carga'] === 1) . ' SIM', 1, 'L', ['helvetica', '', 9]);
$cell($left + 44, $y + 20, 43, 8, csnCheck((int)$c['autorizado_carga'] !== 1) . ' NÃO', 1, 'L', ['helvetica', '', 9]);
$cell($left + 87, $y + 12, 85, 8, (string)($c['qtd_passageiros'] ?? ''), 1, 'C', ['helvetica', '', 9]);
$cell($left + 87, $y + 20, 85, 8, '(Vide o verso)', 1, 'C', ['helvetica', '', 9]);

$y = 194;
$lineText(73, $y, 66, 'A(1)', $emitente, 9);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(139, $y - 0.2);
$pdf->Cell(58, 5, 'certifica que a embarcação', 0, 0, 'R');
$lineText($left, $y + 8, 80, '', csnText($c['nome_embarcacao']), 9);
$pdf->SetXY(105, $y + 7.8);
$pdf->Cell(36, 5, 'foi objeto da vistoria (2)', 0, 0, 'L');
$lineText(143, $y + 8, 38, '', $tipoVistoria, 9);
$pdf->SetXY(182, $y + 7.8);
$pdf->Cell(14, 5, 'em', 0, 0, 'L');
$pdf->SetXY($left, $y + 14);
$pdf->Cell(72, 5, 'conformidade com as disposições regulamentadas pela', 0, 0, 'L');
$pdf->Line(99, $y + 18.2, 137, $y + 18.2);
$pdf->SetXY(99, $y + 13.9);
$pdf->Cell(38, 5, $normam, 0, 0, 'C');
$pdf->SetXY(139, $y + 14);
$pdf->Cell(56, 5, 'da Diretoria de Portos e Costas.', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 7);
$pdf->SetXY(80, $y + 21);
$pdf->Cell(60, 4, '(NORMAM-01 ou NORMAM-02)', 0, 0, 'C');

$y = 218;
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($left, $y);
$pdf->MultiCell(160, 4, '(3) A embarcação cumpre os requisitos de acessibilidade para o transporte coletivo aquaviário de passageiros.', 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(184, $y);
$pdf->Cell(6, 4, csnCheck((int)$c['acessibilidade_sim'] === 1) . ' SIM', 0, 0, 'L');
$pdf->SetXY(184, $y + 6);
$pdf->Cell(6, 4, csnCheck((int)$c['acessibilidade_nao'] === 1) . ' NÃO', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(29, 231);
$pdf->MultiCell(168, 5, "As vistorias evidenciaram que seu estado é satisfatório e que cumpre com as prescrições indicadas.\nO presente Certificado será válido até o vencimento indicado, estando sujeito a realização das vistorias anuais e intermediária que deverão ficar registradas entre as datas limites estabelecidas.", 0, 'J');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(30, 252);
$pdf->Cell(18, 5, 'Emitido em', 0, 0, 'L');
$pdf->Line(48, 256.2, 79, 256.2);
$pdf->SetXY(49, 251.8);
$pdf->Cell(29, 5, csnText($c['local_emissao']), 0, 0, 'C');
$pdf->SetXY(80, 252);
$pdf->Cell(8, 5, ', em', 0, 0, 'L');
$pdf->Line(90, 256.2, 100, 256.2);
$pdf->SetXY(91, 251.8);
$pdf->Cell(8, 5, $diaEmissao, 0, 0, 'C');
$pdf->SetXY(101, 252);
$pdf->Cell(6, 5, 'de', 0, 0, 'L');
$pdf->Line(108, 256.2, 132, 256.2);
$pdf->SetXY(109, 251.8);
$pdf->Cell(22, 5, $mesEmissao, 0, 0, 'C');
$pdf->SetXY(134, 252);
$pdf->Cell(6, 5, 'de', 0, 0, 'L');
$pdf->Line(141, 256.2, 154, 256.2);
$pdf->SetXY(142, 251.8);
$pdf->Cell(11, 5, $anoEmissao, 0, 0, 'C');

$pdf->SetFont('helvetica', '', 6);
$pdf->SetXY($left, 267);
$pdf->MultiCell(88, 3.2, "(1) Capitania, Delegacia, Agência, Certificadora ou Sociedade Classificadora que emitir o Certificado.\n(2) Indicar se Inicial ou de Renovação.\n(3) Requisitos de acessibilidade.\n(4) Campo obrigatório para embarcações que transportam passageiros / passageiros e carga", 0, 'L');

$drawFooter('- 10-E-1 -');

// Página 2
$pdf->AddPage();
$pdf->SetLineWidth($line);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(160, 14);
$pdf->Cell(35, 5, 'NORMAM-202/DPC', 0, 0, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(78, 20);
$pdf->Cell(55, 5, 'CONVALIDAÇÕES', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($left, 33);
$pdf->MultiCell($width, 5, 'Certifica-se que a embarcação foi objeto das vistorias a seguir estabelecidas, com resultado satisfatório, nos setores e datas indicadas, respectivamente.', 0, 'J');

$y = 48;
$col = [49, 30, 22, 36, 35];
$cell($left, $y, $col[0], 14, 'A REALIZAR', 1, 'C', ['helvetica', 'B', 9]);
$cell($left + $col[0], $y, $col[1], 14, 'ENTRE', 1, 'C', ['helvetica', 'B', 9]);
$cell($left + $col[0] + $col[1], $y, $col[2], 14, 'E', 1, 'C', ['helvetica', 'B', 9]);
$cell($left + $col[0] + $col[1] + $col[2], $y, $col[3], 14, "LUGAR E DATA DE\nREALIZAÇÃO", 1, 'C', ['helvetica', 'B', 9]);
$cell($left + $col[0] + $col[1] + $col[2] + $col[3], $y, $col[4], 14, 'VISTORIADOR', 1, 'C', ['helvetica', 'B', 9]);

$convMap = certificadoConvalidacoesPorNumero($convalidacoes);
$isBalsa = certificadoTipoEmbarcacaoEhBalsa($tipoEmbarcacao);
$linhasConv = [];
if ($isBalsa) {
    for ($i = 1; $i <= 9; $i++) {
        $linhasConv[] = ["{$i}ª VIST. ANUAL", $i];
    }
} else {
    $linhasConv = [
        ['1ª VIST. ANUAL', 1],
        ['2ª VIST. ANUAL', 2],
        ["VISTORIA INTERMEDIÁRIA\n(não aplicável para navegação interior)", null],
        ['3ª VIST. ANUAL', 3],
        ['4ª VIST. ANUAL', 4],
    ];
}
$alturaConv = $isBalsa ? 8 : 13;
$fonteConv = $isBalsa ? 7 : 9;
$y += 14;
foreach ($linhasConv as $linhaConv) {
    [$nomeConv, $numConv] = $linhaConv;
    $conv = $numConv ? ($convMap[$numConv] ?? []) : [];
    $cell($left, $y, $col[0], $alturaConv, $nomeConv, 1, 'C', ['helvetica', '', $numConv ? $fonteConv : 8]);
    $cell($left + $col[0], $y, $col[1], $alturaConv, csnDataBR($conv['data_inicio'] ?? ''), 1, 'C', ['helvetica', '', 7]);
    $cell($left + $col[0] + $col[1], $y, $col[2], $alturaConv, csnDataBR($conv['data_fim'] ?? ''), 1, 'C', ['helvetica', '', 7]);
    $cell($left + $col[0] + $col[1] + $col[2], $y, $col[3], $alturaConv, csnText($conv['local_data'] ?? ''), 1, 'C', ['helvetica', '', 7]);
    $cell($left + $col[0] + $col[1] + $col[2] + $col[3], $y, $col[4], $alturaConv, csnText($conv['vistoriador'] ?? ''), 1, 'C', ['helvetica', '', 7]);
    $y += $alturaConv;
}

$boxY = max(127, $y + 4);
$pdf->Rect($left, $boxY, $width, 139);
$distY = $boxY + 9;
$distX = $left + 1;
$distW = $width - 2;
$cell($distX, $distY, $distW, 5, 'DISTRIBUIÇÃO DE PASSAGEIROS / CARGA (t)', 1, 'C', ['helvetica', 'B', 9]);
$cell($distX, $distY + 5, 47, 5, '', 1, 'C', ['helvetica', 'B', 8]);
$cell($distX + 47, $distY + 5, 38, 5, 'CONVÉS PRINCIPAL', 1, 'C', ['helvetica', 'B', 8]);
$cell($distX + 85, $distY + 5, 37, 5, 'CONVÉS SUPERIOR', 1, 'C', ['helvetica', 'B', 8]);
$cell($distX + 122, $distY + 5, 48, 5, 'ÁREA DE LAZER', 1, 'C', ['helvetica', 'B', 8]);

$distMap = [];
foreach ($distribuicao as $d) {
    if (!empty($d['item_codigo'])) $distMap[$d['item_codigo']] = $d;
}
$linhasDist = [
    ['passageiros_sentados', 'Passageiros sentados', 5],
    ['passageiros_camarote', 'Passageiros em camarote', 5],
    ['passageiros_redes', 'Passageiros em redes', 5],
    ['passageiros_em_pe', 'Passageiros em pé', 5],
    ['porao_carga_01', "Porão de carga 01 (carga\ngeral)", 10],
    ['paiol_casco', "Paiol no casco (mantimentos e\nmateriais diversos)", 10],
    ['almoxarifado_conves_principal', "Almoxarifado no convés\nprincipal", 10],
    ['deposito_conves_principal', 'Depósito no convés principal', 5],
    ['deposito_conves_superior', 'Depósito no convés superior', 5],
];
$rowY = $distY + 10;
foreach ($linhasDist as $linhaDist) {
    [$codigoDist, $rotuloDist, $alturaDist] = $linhaDist;
    $d = $distMap[$codigoDist] ?? [];
    $legacy = empty($d['item_codigo'] ?? '') ? ($d['quantidade'] ?? '') : '';
    $cell($distX, $rowY, 47, $alturaDist, $rotuloDist, 1, 'L', ['helvetica', '', 8]);
    $cell($distX + 47, $rowY, 38, $alturaDist, csnText($d['conves_principal'] ?? $legacy), 1, 'C', ['helvetica', '', 8]);
    $cell($distX + 85, $rowY, 37, $alturaDist, csnText($d['conves_superior'] ?? ''), 1, 'C', ['helvetica', '', 8]);
    $cell($distX + 122, $rowY, 48, $alturaDist, csnText($d['area_lazer'] ?? ''), 1, 'C', ['helvetica', '', 8]);
    $rowY += $alturaDist;
}

$obsY = $rowY + 3;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($left + 1, $obsY);
$pdf->Cell(35, 5, 'OBSERVAÇÕES:', 0, 0, 'L');
$pdf->SetXY($left + 1, $obsY + 7);
$pdf->MultiCell($width - 2, max(16, 250 - ($obsY + 7)), csnText($c['observacoes_verso'] ?? ''), 0, 'L');

[$diaVal, $mesVal, $anoVal] = csnDataExtensoPartes($c['data_validade'] ?? '');
$pdf->SetXY($left + 1, 254);
$pdf->Cell(18, 5, 'Válido até:', 0, 0, 'L');
$pdf->Line($left + 20, 258.2, $left + 32, 258.2);
$pdf->SetXY($left + 21, 253.8);
$pdf->Cell(10, 5, $diaVal, 0, 0, 'C');
$pdf->SetXY($left + 34, 254);
$pdf->Cell(6, 5, 'de', 0, 0, 'L');
$pdf->Line($left + 40, 258.2, $left + 70, 258.2);
$pdf->SetXY($left + 41, 253.8);
$pdf->Cell(28, 5, $mesVal, 0, 0, 'C');
$pdf->SetXY($left + 72, 254);
$pdf->Cell(6, 5, 'de', 0, 0, 'L');
$pdf->Line($left + 78, 258.2, $left + 95, 258.2);
$pdf->SetXY($left + 79, 253.8);
$pdf->Cell(15, 5, $anoVal, 0, 0, 'C');

$drawFooter('- 10-E-2 -');

$nome_arquivo = 'CSN_' . str_replace('/', '-', $c['numero']) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nome_arquivo, 'I');
    exit;
}
