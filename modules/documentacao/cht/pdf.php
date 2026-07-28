<?php
/**
 * PDF oficial — Certificado de Homologação de Empresa ou Profissional.
 * Modelo reproduzido da aba CHT do processo R5.
 */
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$id = trim($_GET['id'] ?? '');
$token = trim($_GET['token'] ?? '');

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id FROM certificados_cht WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token]);
    $id = (string)($stmt->fetchColumn() ?: '');
}
if ($id === '') {
    die('ID ou token não informado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_cht WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) {
    die('Certificado CHT não encontrado.');
}

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
require_once __DIR__ . '/../../../includes/certificado_pdf_marca_dagua.php';

if (!function_exists('chtDataExtenso')) {
    function chtDataExtenso(?string $data): string
    {
        if (!$data) return '';
        $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
        $dt = new DateTime($data);
        return (int)$dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
    }
}

if (!function_exists('chtImagemValida')) {
    function chtImagemValida(string $path): bool
    {
        return is_file($path) && filesize($path) > 100;
    }
}

if (!class_exists('CHTDocumentoPDF')) {
    class CHTDocumentoPDF extends CertificadoPdfComMarcaDagua
    {
        public function Header() {}
        public function Footer() {}
    }
}

$numero = $c['numero_certificado'] ?: $c['numero_relatorio_ht'];
$relatorio = $c['relatorio_homologacao_numero'] ?: $c['numero_relatorio_ht'];
$logo = __DIR__ . '/../../../assets/img/logo.png';

$pdf = new CHTDocumentoPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CHT - ' . $numero);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 12, 15);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(0, 0, 0);

// Identificação superior
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(15, 12);
$pdf->Cell(105, 7, h($numero), 1, 0, 'C');
if (chtImagemValida($logo)) {
    $pdf->Image($logo, 158, 8, 34, 20, '', '', '', true, 300);
}

// Título oficial
$pdf->SetXY(15, 34);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->MultiCell(180, 13, "CERTIFICADO DE HOMOLOGAÇÃO DE EMPRESA\nOU PROFISSIONAL PRESTADOR DE SERVIÇOS", 0, 'C', false, 1);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(180, 5, '(Emitido conforme Procedimento Interno de Homologação Técnica da AMAZON NAVAL LTDA)', 0, 'C');

$pdf->SetXY(15, 61);
$pdf->SetFont('helvetica', '', 9.2);
$texto = 'A AMAZON NAVAL LTDA., na qualidade de Organização Reconhecida / Entidade Certificadora, certifica que a empresa ou profissional abaixo identificado encontra-se HOMOLOGADO para execução de serviços técnicos conforme discriminado neste documento, no âmbito dos processos conduzidos pela certificadora, após análise documental, verificação técnica e atendimento aos critérios internos de homologação estabelecidos pela organização.';
$pdf->MultiCell(180, 5, $texto, 0, 'J');

// Identificação do homologado
$pdf->SetXY(15, 91);
$pdf->SetFillColor(226, 239, 233);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(180, 7, 'EMPRESA / PROFISSIONAL HOMOLOGADO', 1, 1, 'C', true);
$pdf->SetFont('helvetica', 'B', 11);
$identificado = '"' . mb_strtoupper((string)$c['profissional_empresa'], 'UTF-8') . "\"\nCPF/CNPJ: " . ($c['cpf_cnpj'] ?: 'NÃO INFORMADO');
$pdf->MultiCell(180, 16, $identificado, 1, 'C', false, 1, '', '', true, 0, false, true, 16, 'M');

$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(180, 5, 'para execução da seguinte atividade técnica:', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(245, 245, 245);
$pdf->MultiCell(180, 14, mb_strtoupper((string)$c['atividade_homologada'], 'UTF-8'), 1, 'C', true, 1, '', '', true, 0, false, true, 14, 'M');

// Condições do modelo oficial
$pdf->SetY(142);
$pdf->SetFont('helvetica', '', 7.8);
$notas = [
    '1) A presente homologação reconhece que o profissional/empresa apresentou documentação técnica, qualificação profissional e requisitos mínimos compatíveis com a atividade homologada, conforme procedimento interno da AMAZON NAVAL LTDA.',
    '2) A homologação não transfere ao terceiro a responsabilidade técnica final pelos processos conduzidos pela certificadora, permanecendo a aceitação técnica dos ensaios, relatórios e serviços sob responsabilidade da AMAZON NAVAL LTDA.',
    '3) Esta homologação poderá ser suspensa, cancelada ou não renovada em caso de vencimento documental, perda de qualificação técnica, irregularidades identificadas ou descumprimento das normas aplicáveis.',
    '4) Certificado emitido conforme Relatório de Homologação Técnica nº ' . $relatorio . '.',
];
foreach ($notas as $nota) {
    $pdf->MultiCell(180, 4.2, $nota, 0, 'J');
    $pdf->Ln(1.2);
}
if (!empty($c['observacoes'])) {
    $pdf->SetFont('helvetica', 'I', 7.5);
    $pdf->MultiCell(180, 4, 'Observação: ' . $c['observacoes'], 0, 'L');
}

$pdf->SetY(208);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(180, 5, 'VÁLIDO ATÉ: ' . (!empty($c['data_validade']) ? date('d/m/Y', strtotime($c['data_validade'])) : 'NÃO INFORMADO'), 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5, 'Expedido em ' . ($c['local_emissao'] ?: 'Belém-PA') . ', em ' . chtDataExtenso($c['data_emissao']), 0, 1, 'C');

$nomeArquivo = 'CHT_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $numero) . '.pdf';
if (isset($salvar_pdf_caminho) && $salvar_pdf_caminho) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nomeArquivo, 'I');
    exit;
}
