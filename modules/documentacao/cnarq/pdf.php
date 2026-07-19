<?php
/**
 * MÓDULO: Documentação > Certificados CNARQ
 * PDF fiel ao modelo oficial do Certificado Nacional de Arqueação.
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_cnarq WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die('Certificado não encontrado.');
    }
    $id = $row['id'];
} elseif (empty($id)) {
    die('ID ou token não informado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_cnarq WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) {
    die('Certificado não encontrado.');
}

if (!isset($salvar_pdf_caminho) && (int)($c['assinado'] ?? 0) === 1 && !empty($c['caminho_arquivo_pdf'])) {
    $caminho_fisico = __DIR__ . '/../../../' . $c['caminho_arquivo_pdf'];
    if (file_exists($caminho_fisico)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($caminho_fisico) . '"');
        header('Content-Length: ' . filesize($caminho_fisico));
        readfile($caminho_fisico);
        exit;
    }
}

function cnarqText($valor, string $padrao = ''): string
{
    $valor = trim((string)$valor);
    return $valor !== '' ? $valor : $padrao;
}

function cnarqPdfText($valor): string
{
    return strip_tags(html_entity_decode((string)$valor, ENT_QUOTES, 'UTF-8'));
}

function cnarqImgOk(string $path): bool
{
    return file_exists($path) && filesize($path) > 100;
}

function cnarqDataBR($data): string
{
    return empty($data) ? '' : date('d/m/Y', strtotime($data));
}

function cnarqDataExtenso($data): string
{
    if (empty($data)) {
        return '__ de __________ de ____';
    }
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function cnarqNumero($valor, int $decimais = 3, string $padrao = ''): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }
    return number_format((float)$valor, $decimais, ',', '');
}

function cnarqInteiro($valor, string $padrao = '0'): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }
    return (string)(int)$valor;
}

function cnarqParseLinhas($texto): array
{
    $linhas = preg_split('/\r\n|\r|\n/', (string)$texto);
    $dados = [];
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '') {
            continue;
        }
        $partes = array_map('trim', explode('|', $linha));
        $dados[] = [
            'nome' => $partes[0] ?? $linha,
            'local' => $partes[1] ?? '',
            'comp' => $partes[2] ?? '',
        ];
    }
    return $dados;
}

if (!class_exists('CertificadoCNARQ')) {
    class CertificadoCNARQ extends TCPDF
    {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new CertificadoCNARQ('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CNARQ - ' . $c['numero']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);

$tipo = mb_strtoupper(cnarqText($c['tipo'] ?? '', 'Condicional'), 'UTF-8');
$numero_limpo = str_replace('AM-CNARQ-', '', (string)$c['numero']);
$data_quilha = cnarqText($c['data_quilha'] ?? '', cnarqText($c['ano_construcao'] ?? ''));
$porto = cnarqText($c['porto_inscricao'] ?? '', cnarqText($c['local_emissao'] ?? ''));
$comprimento_regra = cnarqNumero($c['comprimento_lpp'] ?? $c['comprimento_total'] ?? null, 3);
$boca = cnarqNumero($c['boca_moldada'] ?? $c['boca_maxima'] ?? null, 3);
$pontal = cnarqNumero($c['pontal_moldado'] ?? null, 3);
$ab = cnarqText($c['arqueacao_bruta'] ?? '', '0');
$al = cnarqText($c['arqueacao_liquida'] ?? '', '0');
$calado = cnarqNumero($c['calado_moldado_m'] ?? null, 3, '');
$espacosAB = cnarqParseLinhas($c['espacos_incluidos_ab'] ?? '');
$espacosAL = cnarqParseLinhas($c['espacos_incluidos_al'] ?? '');
$espacosExcluidos = cnarqNumero($c['espacos_excluidos_m3'] ?? 0, 2, '0,00') . ' m³';
$dataLocalOriginal = cnarqText($c['data_local_arqueacao_original'] ?? '', cnarqText($c['local_emissao'] ?? '') . ' ' . cnarqDataExtenso($c['data_emissao']));
$dataLocalRearq = cnarqText($c['data_local_ultima_rearqueacao'] ?? '', 'x-x-x-x-x-x');

// =========================
// PÁGINA 1
// =========================
$pdf->AddPage();
$pdf->SetLineWidth(0.45);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Rect(10, 14, 73, 5);
$pdf->SetXY(10, 14.1);
$pdf->Cell(73, 5, 'CERTIFICADO AM-CNARQ - ' . cnarqPdfText($numero_limpo), 0, 0, 'C');

$brasao = __DIR__ . '/../../../assets/img/brasao.png';
if (cnarqImgOk($brasao)) {
    $pdf->Image($brasao, 25, 37, 31, 31, 'PNG', '', '', true, 150);
}

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetXY(58, 29);
$pdf->Cell(108, 7, 'CERTIFICADO NACIONAL DE ARQUEAÇÃO', 0, 1, 'C');
$pdf->Line(70, 36, 156, 36);
$pdf->SetFont('helvetica', 'BI', 10);
$pdf->SetXY(164, 32);
$pdf->Cell(35, 5, '(' . $tipo . ')', 0, 0, 'L');

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetXY(58, 45);
$pdf->Cell(110, 6, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdf->SetX(58);
$pdf->Cell(110, 6, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetX(58);
$pdf->Cell(110, 6, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(58, 69);
$pdf->Cell(110, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

// Identificação
$x = 10; $y = 82; $w = 190;
$cols = [80, 37, 36, 37];
$pdf->Rect($x, $y, $w, 23);
$pdf->Line($x, $y + 10, $x + $w, $y + 10);
$cx = $x;
for ($i = 0; $i < 3; $i++) {
    $cx += $cols[$i];
    $pdf->Line($cx, $y, $cx, $y + 23);
}
$pdf->SetFont('helvetica', '', 9.2);
$headers = ['Nome da Embarcação', 'N° de Inscrição', 'Porto de Inscrição', "Data em que a quilha foi\nbatida (ver nota)"];
$cx = $x;
foreach ($headers as $i => $header) {
    $pdf->SetXY($cx + 1, $y + 2);
    $pdf->MultiCell($cols[$i] - 2, 5, $header, 0, 'C');
    $cx += $cols[$i];
}
$pdf->SetFont('helvetica', 'B', 10);
$values = [
    '"' . mb_strtoupper(cnarqText($c['nome_embarcacao'] ?? '', 'Não informado'), 'UTF-8') . '"',
    cnarqText($c['numero_inscricao'] ?? '', 'Não Fornecido'),
    $porto,
    $data_quilha,
];
$cx = $x;
foreach ($values as $i => $value) {
    $pdf->SetXY($cx + 1, $y + 14);
    $pdf->Cell($cols[$i] - 2, 5, cnarqPdfText($value), 0, 0, 'C');
    $cx += $cols[$i];
}

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(10, 113);
$pdf->Cell(190, 6, 'CARACTERÍSTICAS PRINCIPAIS', 0, 1, 'C');

$y = 123;
$colsDim = [66, 58, 66];
$pdf->Rect(10, $y, 190, 25);
$pdf->Line(10, $y + 12, 200, $y + 12);
$pdf->Line(10 + $colsDim[0], $y, 10 + $colsDim[0], $y + 25);
$pdf->Line(10 + $colsDim[0] + $colsDim[1], $y, 10 + $colsDim[0] + $colsDim[1], $y + 25);
$pdf->SetFont('helvetica', '', 9.5);
$pdf->SetXY(10, $y + 3);
$pdf->Cell($colsDim[0], 5, 'Comprimento de Regra (m)', 0, 0, 'C');
$pdf->Cell($colsDim[1], 5, 'Boca (m)', 0, 0, 'C');
$pdf->MultiCell($colsDim[2], 4.2, "Pontal moldado a meia-nau\naté o convés superior (m)", 0, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(10, $y + 17);
$pdf->Cell($colsDim[0], 5, cnarqPdfText($comprimento_regra), 0, 0, 'C');
$pdf->Cell($colsDim[1], 5, cnarqPdfText($boca), 0, 0, 'C');
$pdf->Cell($colsDim[2], 5, cnarqPdfText($pontal), 0, 0, 'C');

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(10, 158);
$pdf->Cell(190, 6, 'AS ARQUEAÇÕES DA EMBARCAÇÃO SÃO:', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(60, 171);
$pdf->Cell(90, 5, 'ARQUEAÇÃO BRUTA (AB): ' . cnarqPdfText($ab), 0, 1, 'L');
$pdf->SetX(60);
$pdf->Cell(90, 5, 'ARQUEAÇÃO LÍQUIDA (AL): ' . cnarqPdfText($al), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(10, 192);
$pdf->MultiCell(190, 15, 'Certifico que as arqueações desta embarcação foram determinadas de acordo com as disposições da Convenção Internacional sobre Medidas de Arqueações de Embarcações (1969) e das Normas da Autoridade Marítima para Embarcações Empregadas na Navegação Interior.', 0, 'L');
$pdf->SetXY(10, 213);
$pdf->MultiCell(190, 12, 'NOTA: data na qual a quilha foi batida ou estágio equivalente de construção, ou data na qual o navio sofreu alterações ou modificações de maior vulto.', 0, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(10, 240);
$pdf->Cell(190, 6, 'Expedido em ' . cnarqPdfText(cnarqText($c['local_emissao'] ?? '', 'Belém-PA')) . ', em ' . cnarqDataExtenso($c['data_emissao']), 0, 1, 'C');

// =========================
// PÁGINA 2
// =========================
$pdf->AddPage();
$pdf->SetLineWidth(0.45);

$boxX = 10; $boxY = 18; $boxW = 190; $boxH = 196;
$pdf->Rect($boxX, $boxY, $boxW, $boxH);
$pdf->Line($boxX, $boxY + 12, $boxX + $boxW, $boxY + 12);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY($boxX, $boxY + 3);
$pdf->Cell($boxW, 6, 'ESPAÇOS INCLUÍDOS NA ARQUEAÇÃO', 0, 0, 'C');

$topY = $boxY + 12;
$pdf->Line($boxX + 95, $topY, $boxX + 95, $boxY + 122);
$pdf->Line($boxX, $topY + 9, $boxX + $boxW, $topY + 9);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($boxX, $topY + 2);
$pdf->Cell(95, 5, 'ARQUEAÇÃO BRUTA', 0, 0, 'C');
$pdf->Cell(95, 5, 'ARQUEAÇÃO LÍQUIDA', 0, 0, 'C');

$headerY = $topY + 9;
$pdf->Line($boxX, $headerY + 5, $boxX + $boxW, $headerY + 5);
$leftWidths = [36, 29, 30];
$rightWidths = [36, 29, 30];
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($boxX, $headerY + 0.8);
foreach (['NOME DO ESPAÇO', 'LOCAL', 'COMP.'] as $i => $h) {
    $pdf->Cell($leftWidths[$i], 4, $h, 0, 0, 'C');
}
foreach (['NOME DO ESPAÇO', 'LOCAL', 'COMP.'] as $i => $h) {
    $pdf->Cell($rightWidths[$i], 4, $h, 0, 0, 'C');
}
$vxs = [$boxX + 36, $boxX + 65, $boxX + 95, $boxX + 131, $boxX + 160];
foreach ($vxs as $vx) {
    $pdf->Line($vx, $headerY, $vx, $boxY + 122);
}

$rowStartY = $headerY + 5;
$rowH = 8;
$maxRows = 9;
$pdf->SetFont('helvetica', '', 8);
for ($i = 0; $i < $maxRows; $i++) {
    $yy = $rowStartY + ($i * $rowH) + 1.4;
    $abRow = $espacosAB[$i] ?? ['nome' => '', 'local' => '', 'comp' => ''];
    $alRow = $espacosAL[$i] ?? ['nome' => '', 'local' => '', 'comp' => ''];
    $pdf->SetXY($boxX + 1, $yy);
    $pdf->Cell(34, 4, cnarqPdfText($abRow['nome']), 0, 0, 'L');
    $pdf->Cell(29, 4, cnarqPdfText($abRow['local']), 0, 0, 'C');
    $pdf->Cell(30, 4, cnarqPdfText($abRow['comp']), 0, 0, 'C');
    $pdf->Cell(36, 4, cnarqPdfText($alRow['nome']), 0, 0, 'L');
    $pdf->Cell(29, 4, cnarqPdfText($alRow['local']), 0, 0, 'C');
    $pdf->Cell(30, 4, cnarqPdfText($alRow['comp']), 0, 0, 'C');
}

$pdf->Line($boxX, $boxY + 122, $boxX + $boxW, $boxY + 122);
$pdf->Line($boxX + 95, $boxY + 122, $boxX + 95, $boxY + 170);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($boxX, $boxY + 122.5);
$pdf->Cell(95, 5, 'ESPAÇOS EXCLUÍDOS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($boxX);
$pdf->Cell(95, 5, cnarqPdfText($espacosExcluidos), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($boxX + 1, $boxY + 145);
$pdf->MultiCell(93, 12, 'um asterisco (*) deve ser feito naqueles espaços acima discriminados que sejam simultaneamente considerados espaços fechados e excluídos.', 0, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($boxX + 95, $boxY + 122.5);
$pdf->Cell(95, 5, 'NÚMERO DE PASSAGEIROS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($boxX + 96, $boxY + 132);
$pdf->Cell(93, 4, 'Número total de passageiros em camarotes com até 8 beliches', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($boxX + 96);
$pdf->Cell(93, 5, cnarqInteiro($c['passageiros_camarotes'] ?? 0), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($boxX + 96, $boxY + 147);
$pdf->Cell(93, 4, 'Número total dos demais passageiros', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($boxX + 96);
$pdf->Cell(93, 5, cnarqInteiro($c['passageiros_outros'] ?? 0), 0, 1, 'C');

$pdf->Line($boxX + 95, $boxY + 170, $boxX + $boxW, $boxY + 170);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($boxX + 95, $boxY + 171);
$pdf->Cell(95, 5, 'CALADO MOLDADO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($boxX + 95);
$pdf->Cell(95, 5, $calado !== '' ? cnarqPdfText($calado . ' m') : '', 0, 1, 'C');

$infoY = $boxY + 170;
$pdf->Line($boxX, $infoY, $boxX + $boxW, $infoY);
$pdf->Line($boxX, $infoY + 5, $boxX + $boxW, $infoY + 5);
$pdf->Line($boxX, $infoY + 10, $boxX + $boxW, $infoY + 10);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY($boxX + 1, $infoY + 0.6);
$pdf->Cell($boxW - 2, 4, 'DATA E LOCAL DA ARQUEAÇÃO ORIGINAL: ' . cnarqPdfText($dataLocalOriginal), 0, 1, 'L');
$pdf->SetX($boxX + 1);
$pdf->Cell($boxW - 2, 4, 'DATA E LOCAL DA ÚLTIMA REARQUEAÇÃO: ' . cnarqPdfText($dataLocalRearq), 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY($boxX + 1, $infoY + 10.8);
$pdf->Cell(188, 4, 'Observações:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 8.5);
$obs1 = '1. Este Certificado ' . ucfirst(mb_strtolower($tipo, 'UTF-8')) . ' foi emitido com base no Relatório de Vistorias n.º ' . cnarqText($c['relatorio_numero'] ?? '') . '.';
$obs2 = '2. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . cnarqDataBR($c['data_vistoria']) . ' em ' . cnarqText($c['local_vistoria'] ?? '') . '.';
$pdf->SetX($boxX + 1);
$pdf->MultiCell(188, 5, cnarqPdfText($obs1), 0, 'L');
$pdf->SetX($boxX + 1);
$pdf->MultiCell(188, 5, cnarqPdfText($obs2), 0, 'L');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Rect(10, 215, 73, 6);
$pdf->SetXY(10, 215.5);
$pdf->Cell(73, 5, 'ANEXO 7-A - NORMAM 202/DPC', 0, 0, 'C');
$pdf->Rect(85, 215, 115, 6);
$pdf->SetXY(85, 215.5);
$pdf->Cell(115, 5, 'VÁLIDO ATÉ: ' . cnarqDataExtenso($c['data_validade']), 0, 1, 'C');

$nome_arquivo = 'CNARQ_' . str_replace('/', '-', $c['numero']) . '.pdf';
if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nome_arquivo, 'I');
    exit;
}
