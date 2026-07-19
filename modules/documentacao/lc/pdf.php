<?php
/**
 * PDF oficial — Licença de Construção / Alteração / Reclassificação / LCEC.
 * Modelo reproduzido da aba LC do processo R5 (Anexo 3-A da NORMAM-202/DPC).
 */
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$id = trim($_GET['id'] ?? '');
$token = trim($_GET['token'] ?? '');
if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id FROM certificados_lc WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token]);
    $id = (string)($stmt->fetchColumn() ?: '');
}
if ($id === '') die('ID ou token não informado.');

$stmt = $pdo->prepare("SELECT * FROM certificados_lc WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) die('Licença não encontrada.');

if (!isset($salvar_pdf_caminho) && !empty($c['assinado']) && !empty($c['caminho_arquivo_pdf'])) {
    $arquivo = __DIR__ . '/../../../' . $c['caminho_arquivo_pdf'];
    if (is_file($arquivo)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($arquivo) . '"');
        header('Content-Length: ' . filesize($arquivo));
        readfile($arquivo);
        exit;
    }
}

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!function_exists('lcDataExtenso')) {
    function lcDataExtenso(?string $data): string
    {
        if (!$data) return '';
        $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
        $dt = new DateTime($data);
        return (int)$dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
    }
}
if (!function_exists('lcValor')) {
    function lcValor($valor, string $sufixo = ''): string
    {
        if ($valor === null || $valor === '') return '';
        if (is_numeric($valor)) {
            $numero = number_format((float)$valor, 2, ',', '');
            $numero = preg_replace('/,00$/', '', $numero);
            return $numero . $sufixo;
        }
        return (string)$valor . $sufixo;
    }
}
if (!function_exists('lcImagemValida')) {
    function lcImagemValida(string $path): bool
    {
        return is_file($path) && filesize($path) > 100;
    }
}
if (!class_exists('LicencaConstrucaoPDF')) {
    class LicencaConstrucaoPDF extends TCPDF
    {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new LicencaConstrucaoPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Licença ' . $c['tipo_licenca'] . ' - ' . $c['numero_lc']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 8, 10);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.25);

$brasao = __DIR__ . '/../../../assets/img/brasao.png';
$logo = __DIR__ . '/../../../assets/img/logo.png';
if (lcImagemValida($brasao)) {
    $pdf->Image($brasao, 15, 10, 24, 24, '', '', '', true, 300);
}

$pdf->SetXY(44, 8);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(151, 5, 'ANEXO 3-A - NORMAM 202/DPC', 0, 1, 'C');
$pdf->SetX(44);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(151, 6, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetX(44);
$pdf->Cell(151, 6, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetX(44);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(151, 6, 'AMAZON NAVAL LTDA', 0, 1, 'C');

// Quadro das quatro modalidades oficiais
$tipos = [
    'LC' => ['LICENÇA DE CONSTRUÇÃO', 'AM-LC'],
    'LA' => ['LICENÇA DE ALTERAÇÃO', 'AM-LA'],
    'LR' => ['LICENÇA DE RECLASSIFICAÇÃO', 'AM-LR'],
    'LCEC' => ['LICENÇA DE CONSTRUÇÃO PARA EMBARCAÇÕES JÁ CONSTRUÍDAS (LCEC)', 'AM-EC'],
];
$y = 37;
foreach ($tipos as $codigo => [$rotulo, $prefixo]) {
    $altura = $codigo === 'LCEC' ? 10 : 7;
    $pdf->SetXY(10, $y);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(7, $altura, $c['tipo_licenca'] === $codigo ? 'X' : '', 1, 0, 'C');
    $texto = $rotulo;
    if ($codigo === 'LCEC' && !empty($c['data_termino_construcao'])) {
        $texto .= ' — TÉRMINO DA CONSTRUÇÃO: ' . date('d/m/Y', strtotime($c['data_termino_construcao']));
    }
    $pdf->SetFont('helvetica', 'B', $codigo === 'LCEC' ? 7.3 : 8.5);
    $pdf->MultiCell(122, $altura, $texto, 1, 'L', false, 0, '', '', true, 0, false, true, $altura, 'M');
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(61, $altura, $c['tipo_licenca'] === $codigo ? 'Nº: ' . $c['numero_lc'] : 'Nº: ' . $prefixo . '-___/___', 1, 1, 'C');
    $y += $altura;
}

// Dados principais da embarcação
$y += 3;
$pdf->SetXY(10, $y);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(55, 7, 'NOME DA EMBARCAÇÃO:', 1, 0, 'L');
$pdf->Cell(135, 7, h($c['nome_embarcacao']), 1, 1, 'C');
$pdf->SetFont('helvetica', '', 7.8);
$pdf->Cell(55, 7, 'Tipo de Embarcação (NORMAM 202)', 1, 0, 'L');
$pdf->Cell(75, 7, h($c['tipo_embarcacao']), 1, 0, 'C');
$pdf->Cell(35, 7, 'Comprimento Total:', 1, 0, 'L');
$pdf->Cell(25, 7, lcValor($c['comprimento_total'], ' m'), 1, 1, 'C');
$pdf->Cell(55, 7, 'Número do Casco:', 1, 0, 'L');
$pdf->Cell(75, 7, h($c['numero_casco']), 1, 0, 'C');
$pdf->Cell(35, 7, 'Comprimento PP:', 1, 0, 'L');
$pdf->Cell(25, 7, lcValor($c['comprimento_pp'], ' m'), 1, 1, 'C');
$pdf->Cell(55, 7, 'Material do Casco:', 1, 0, 'L');
$pdf->Cell(75, 7, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell(35, 7, 'Boca Moldada:', 1, 0, 'L');
$pdf->Cell(25, 7, lcValor($c['boca_moldada'], ' m'), 1, 1, 'C');
$pdf->Cell(55, 7, 'Sociedade Classificadora / Certificadora', 1, 0, 'L');
$pdf->Cell(75, 7, h($c['sociedade_classificadora']), 1, 0, 'C');
$pdf->Cell(35, 7, 'Pontal Moldado:', 1, 0, 'L');
$pdf->Cell(25, 7, lcValor($c['pontal_moldado'], ' m'), 1, 1, 'C');
$pdf->Cell(55, 7, 'Número de Tripulantes:', 1, 0, 'L');
$pdf->Cell(30, 7, h($c['numero_tripulantes']), 1, 0, 'C');
$pdf->Cell(45, 7, 'Número de Passageiros:', 1, 0, 'L');
$pdf->Cell(20, 7, h($c['numero_passageiros']), 1, 0, 'C');
$pdf->Cell(25, 7, 'Porte Bruto:', 1, 0, 'L');
$pdf->Cell(15, 7, lcValor($c['porte_bruto'], ' t'), 1, 1, 'C');

// Navegação e atividade
$pdf->Ln(3);
$larguras = [45, 45, 55, 45];
$cabecalhos = ['Tipo de Navegação', 'Área de Navegação', 'Atividade / Serviço', 'Propulsão'];
$valores = [$c['tipo_navegacao'], $c['area_navegacao'], $c['atividade_servico'], $c['propulsao']];
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('helvetica', 'B', 7.5);
foreach ($cabecalhos as $i => $cab) $pdf->Cell($larguras[$i], 6, $cab, 1, $i === 3 ? 1 : 0, 'C', true);
$pdf->SetFont('helvetica', 'B', 8);
foreach ($valores as $i => $valor) $pdf->MultiCell($larguras[$i], 12, h($valor), 1, 'C', false, $i === 3 ? 1 : 0, '', '', true, 0, false, true, 12, 'M');

// Proprietário e estaleiro
$pdf->Ln(3);
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 6, 'PROPRIETÁRIO / ARMADOR:', 1, 0, 'L', true);
$pdf->Cell(60, 6, 'CPF/CNPJ: ' . h($c['proprietario_cpf_cnpj']), 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.8);
$pdf->Cell(25, 6, 'Nome:', 1, 0, 'L');
$pdf->Cell(165, 6, h($c['proprietario_nome']), 1, 1, 'L');
$pdf->Cell(25, 8, 'Endereço:', 1, 0, 'L');
$pdf->MultiCell(165, 8, h($c['proprietario_endereco']), 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 6, 'ESTALEIRO / CONSTRUTOR:', 1, 0, 'L', true);
$pdf->Cell(60, 6, 'CPF/CNPJ: ' . h($c['estaleiro_cpf_cnpj']), 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.8);
$pdf->Cell(25, 6, 'Nome:', 1, 0, 'L');
$pdf->Cell(165, 6, h($c['estaleiro_nome']), 1, 1, 'L');
$pdf->Cell(25, 8, 'Endereço:', 1, 0, 'L');
$pdf->MultiCell(165, 8, h($c['estaleiro_endereco']), 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(190, 5, 'Expedido em ' . ($c['local_emissao'] ?: 'Belém-PA') . ', em ' . lcDataExtenso($c['data_emissao']), 0, 1, 'C');

$nomeArquivo = $c['tipo_licenca'] . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $c['numero_lc']) . '.pdf';
if (isset($salvar_pdf_caminho) && $salvar_pdf_caminho) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nomeArquivo, 'I');
    exit;
}
