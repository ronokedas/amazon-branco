<?php
/**
 * MÓDULO: Documentação > Licença Provisória (LP)
 * PDF baseado no modelo oficial: CERTIFICADOS-AMAZON-PROCESSO-R5(LP)-BALSA.xlsx
 *
 * Suporta:
 * - ?id=UUID    acesso administrativo
 * - ?token=...  acesso público para assinatura/visualização
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_lp WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        die('Licença não encontrada.');
    }
    $id = $row['id'];
} elseif (empty($id)) {
    die('ID ou token não informado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_lp WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die('Licença não encontrada.');
}

if (!isset($salvar_pdf_caminho) && (int)$c['assinado'] === 1 && !empty($c['caminho_arquivo_pdf'])) {
    $caminho_fisico = __DIR__ . '/../../../' . $c['caminho_arquivo_pdf'];
    if (file_exists($caminho_fisico)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($caminho_fisico) . '"');
        header('Content-Length: ' . filesize($caminho_fisico));
        readfile($caminho_fisico);
        exit;
    }
}

$autoload_path = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die('Autoloader do Composer não encontrado.');
}
require_once $autoload_path;
require_once __DIR__ . '/../../../includes/certificado_pdf_marca_dagua.php';

function lpTxt($valor, $padrao = '') {
    $valor = trim((string)($valor ?? ''));
    return $valor !== '' ? $valor : $padrao;
}

function lpDataBR($data) {
    if (empty($data)) {
        return '';
    }
    return date('d/m/Y', strtotime($data));
}

function lpDataExtenso($data) {
    if (empty($data)) {
        return '___ de ______________ de ______';
    }

    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];

    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function lpNumero($valor) {
    if ($valor === null || $valor === '') {
        return '';
    }

    $numero = (float)$valor;
    $formatado = number_format($numero, 2, ',', '');
    return rtrim(rtrim($formatado, '0'), ',');
}

function lpImgOK($caminho) {
    return file_exists($caminho) && filesize($caminho) > 100;
}

function lpConverterAssinaturaParaJpeg($dados) {
    if (!function_exists('imagecreatefromstring')) {
        return $dados;
    }

    $img = @imagecreatefromstring($dados);
    if ($img === false) {
        return $dados;
    }

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

if (!class_exists('CertificadoLP')) {
    class CertificadoLP extends CertificadoPdfComMarcaDagua {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new CertificadoLP('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Licença Provisória - ' . $c['numero_lp']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 12, 15);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();

$brasao = __DIR__ . '/../../../assets/img/brasao.png';
$logo = __DIR__ . '/../../../assets/img/logo.png';

$x = 15;
$w = 180;

// Cabeçalho oficial
if (lpImgOK($brasao)) {
    $pdf->Image($brasao, 30, 12, 24, 24, 'PNG', '', '', true, 150);
}

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetXY(15, 13);
$pdf->Cell($w, 5, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell($w, 5, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell($w, 7, 'LICENÇA PROVISÓRIA PARA INICIAR CONSTRUÇÃO / ALTERAÇÃO', 0, 1, 'C');

$validade = lpDataBR($c['validade_data']);
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w, 6, 'NÚMERO DA LICENÇA: ' . lpTxt($c['numero_lp']) . ' - VÁLIDA ATÉ: ' . $validade, 0, 1, 'C');

// Texto principal
$nome_embarcacao = mb_strtoupper(lpTxt($c['nome_embarcacao'], 'NÃO INFORMADO'), 'UTF-8');
$tipo_embarcacao = mb_strtoupper(lpTxt($c['tipo_embarcacao'], 'NÃO INFORMADO'), 'UTF-8');
$numero_casco = lpTxt($c['numero_casco'], '-');

$pdf->SetY(57);
$pdf->SetFont('helvetica', '', 10);
$texto = 'Concede-se autorização para início de construção da embarcação "' . $nome_embarcacao . '" do tipo ' . $tipo_embarcacao . ', com número de casco - ' . $numero_casco . ' -, com as seguintes características:';
$pdf->MultiCell($w, 7, $texto, 0, 'L');

// Características
$pdf->SetY(83);
$linhas = [
    ['A)', 'Comprimento Total:', lpNumero($c['comprimento_total'])],
    ['B)', 'Boca Moldada:', lpNumero($c['boca_moldada'])],
    ['C)', 'Pontal Moldado:', lpNumero($c['pontal_moldado'])],
    ['D)', 'Material do Casco:', lpTxt($c['material_casco'])],
];

foreach ($linhas as $linha) {
    $pdf->SetX(27);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(12, 8, $linha[0], 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(54, 8, $linha[1], 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 8, $linha[2], 0, 1, 'L');
}

// Estaleiro / Construtor
$pdf->SetY(122);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(72, 7, 'ESTALEIRO / CONSTRUTOR:', 0, 0, 'L');
$pdf->Cell(55, 7, '', 0, 0, 'L');
$pdf->Cell(22, 7, 'CPF/CNPJ:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(31, 7, lpTxt($c['estaleiro_cpf_cnpj']), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(16, 7, 'Nome:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(164, 7, lpTxt($c['estaleiro_nome'], 'Não informado'), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(22, 7, 'Endereço:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$endereco = lpTxt($c['estaleiro_endereco'], 'Não informado');
$pdf->MultiCell(158, 7, $endereco, 0, 'L');

// Observações / exigências
$pdf->SetY(154);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w, 7, 'OBSERVAÇÕES / EXIGÊNCIAS:', 0, 1, 'L');

$observacoes = trim((string)($c['observacoes_exigencias'] ?? ''));
if ($observacoes === '') {
    $data_req = !empty($c['data_requerimento']) ? lpDataBR($c['data_requerimento']) : lpDataBR($c['data_emissao']);
    $requerente = lpTxt($c['proprietario_nome'], 'interessado');
    $observacoes = "1. A emissão da licença provisória não exime o interessado da obtenção da licença de construção definitiva, prevista no item 3.7.1. d), da NORMAM 202/DPC.\n\n";
    $observacoes .= "2. Licença Provisória para Iniciar Construção emitida com base no requerimento apresentado por {$requerente}, datado em {$data_req}.";
}

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($w, 6, $observacoes, 0, 'L');

// Local e data
$pdf->SetY(222);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($w, 6, 'Expedido em Santarém-PA, ' . lpDataExtenso($c['data_emissao']), 0, 1, 'C');

$nome_arquivo = 'LP_' . str_replace('/', '-', $c['numero_lp']) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nome_arquivo, 'I');
    exit;
}
