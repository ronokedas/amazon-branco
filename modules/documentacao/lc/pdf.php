<?php
/**
 * PDF oficial — Licença de Construção / Alteração / Reclassificação / LCEC.
 * Modelo oficial padronizado (Anexo 3-A da NORMAM-202/DPC).
 * Inclui Observações Técnicas Oficiais pré-preenchidas e editáveis pelo Analista Naval.
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
require_once __DIR__ . '/../../../includes/certificado_pdf_marca_dagua.php';

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
    class LicencaConstrucaoPDF extends CertificadoPdfComMarcaDagua
    {
        public function Header() {}
        public function Footer() {}
    }
}

// Obter observações da licença ou gerar padrão caso ainda não estejam salvas
$observacoes = trim((string)($c['observacoes'] ?? ''));
if ($observacoes === '' && !empty($c['dados_json'])) {
    $dj = json_decode($c['dados_json'], true);
    if (!empty($dj['observacoes'])) {
        $observacoes = (string)$dj['observacoes'];
    }
}
if ($observacoes === '') {
    $observacoes = gerarObservacoesPadraoLicenca($c, $c['tipo_licenca'] ?? 'LC');
}

$pdf = new LicencaConstrucaoPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Licença ' . $c['tipo_licenca'] . ' - ' . $c['numero_lc']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 7, 10);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.25);

// Brasão da República
$brasao = __DIR__ . '/../../../assets/img/brasao.png';
if (lcImagemValida($brasao)) {
    $pdf->Image($brasao, 14, 7, 20, 20, '', '', '', true, 300);
}

// Cabeçalho Oficial
$pdf->SetXY(38, 6.5);
$pdf->SetFont('helvetica', 'B', 7.8);
$pdf->Cell(157, 4.2, 'ANEXO 3-A - NORMAM 202/DPC', 0, 1, 'C');
$pdf->SetX(38);
$pdf->SetFont('helvetica', 'B', 10.5);
$pdf->Cell(157, 4.8, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetX(38);
$pdf->Cell(157, 4.8, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetX(38);
$pdf->SetFont('helvetica', 'B', 9.2);
$pdf->Cell(157, 4.5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

// Quadro das quatro modalidades oficiais
$tipos = [
    'LC' => ['LICENÇA DE CONSTRUÇÃO', 'AM-LC'],
    'LA' => ['LICENÇA DE ALTERAÇÃO', 'AM-LA'],
    'LR' => ['LICENÇA DE RECLASSIFICAÇÃO', 'AM-LR'],
    'LCEC' => ['LICENÇA DE CONSTRUÇÃO PARA EMBARCAÇÕES JÁ CONSTRUÍDAS (LCEC)', 'AM-EC'],
];
$y = 28.5;
foreach ($tipos as $codigo => [$rotulo, $prefixo]) {
    $altura = $codigo === 'LCEC' ? 7.0 : 5.2;
    $pdf->SetXY(10, $y);
    $pdf->SetFont('helvetica', 'B', 8.0);
    $pdf->Cell(7, $altura, $c['tipo_licenca'] === $codigo ? 'X' : '', 1, 0, 'C');
    $texto = $rotulo;
    if ($codigo === 'LCEC' && !empty($c['data_termino_construcao'])) {
        $texto .= ' — TÉRMINO DA CONSTRUÇÃO: ' . date('d/m/Y', strtotime($c['data_termino_construcao']));
    }
    $pdf->SetFont('helvetica', 'B', $codigo === 'LCEC' ? 6.8 : 7.6);
    $pdf->MultiCell(122, $altura, $texto, 1, 'L', false, 0, '', '', true, 0, false, true, $altura, 'M');
    $pdf->SetFont('helvetica', 'B', 7.6);
    $pdf->Cell(61, $altura, $c['tipo_licenca'] === $codigo ? 'Nº: ' . $c['numero_lc'] : 'Nº: ' . $prefixo . '-___/___', 1, 1, 'C');
    $y += $altura;
}

// Dados principais da embarcação
$y += 1.5;
$rh = 4.8;
$pdf->SetXY(10, $y);
$pdf->SetFont('helvetica', 'B', 7.2);
$pdf->Cell(55, $rh, 'NOME DA EMBARCAÇÃO:', 1, 0, 'L');
$pdf->Cell(135, $rh, h($c['nome_embarcacao']), 1, 1, 'C');
$pdf->SetFont('helvetica', '', 7.0);
$pdf->Cell(55, $rh, 'Tipo de Embarcação (NORMAM 202)', 1, 0, 'L');
$pdf->Cell(75, $rh, h($c['tipo_embarcacao']), 1, 0, 'C');
$pdf->Cell(35, $rh, 'Comprimento Total:', 1, 0, 'L');
$pdf->Cell(25, $rh, lcValor($c['comprimento_total'], ' m'), 1, 1, 'C');
$pdf->Cell(55, $rh, 'Número do Casco:', 1, 0, 'L');
$pdf->Cell(75, $rh, h($c['numero_casco']), 1, 0, 'C');
$pdf->Cell(35, $rh, 'Comprimento PP:', 1, 0, 'L');
$pdf->Cell(25, $rh, lcValor($c['comprimento_pp'], ' m'), 1, 1, 'C');
$pdf->Cell(55, $rh, 'Material do Casco:', 1, 0, 'L');
$pdf->Cell(75, $rh, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell(35, $rh, 'Boca Moldada:', 1, 0, 'L');
$pdf->Cell(25, $rh, lcValor($c['boca_moldada'], ' m'), 1, 1, 'C');
$pdf->Cell(55, $rh, 'Sociedade Classificadora / Certificadora', 1, 0, 'L');
$pdf->Cell(75, $rh, h($c['sociedade_classificadora']), 1, 0, 'C');
$pdf->Cell(35, $rh, 'Pontal Moldado:', 1, 0, 'L');
$pdf->Cell(25, $rh, lcValor($c['pontal_moldado'], ' m'), 1, 1, 'C');
$pdf->Cell(55, $rh, 'Número de Tripulantes:', 1, 0, 'L');
$pdf->Cell(30, $rh, h($c['numero_tripulantes']), 1, 0, 'C');
$pdf->Cell(45, $rh, 'Número de Passageiros:', 1, 0, 'L');
$pdf->Cell(20, $rh, h($c['numero_passageiros']), 1, 0, 'C');
$pdf->Cell(25, $rh, 'Porte Bruto:', 1, 0, 'L');
$pdf->Cell(15, $rh, lcValor($c['porte_bruto'], ' t'), 1, 1, 'C');

// Navegação e atividade
$pdf->Ln(1.5);
$larguras = [45, 45, 55, 45];
$cabecalhos = ['Tipo de Navegação', 'Área de Navegação', 'Atividade / Serviço', 'Propulsão'];
$valores = [$c['tipo_navegacao'], $c['area_navegacao'], $c['atividade_servico'], $c['propulsao']];
$pdf->SetFillColor(238, 238, 238);
$pdf->SetFont('helvetica', 'B', 7.0);
foreach ($cabecalhos as $i => $cab) $pdf->Cell($larguras[$i], 4.2, $cab, 1, $i === 3 ? 1 : 0, 'C', true);
$pdf->SetFont('helvetica', 'B', 7.2);
foreach ($valores as $i => $valor) $pdf->MultiCell($larguras[$i], 7.0, h($valor), 1, 'C', false, $i === 3 ? 1 : 0, '', '', true, 0, false, true, 7.0, 'M');

// Proprietário e estaleiro
$pdf->Ln(1.5);
$pdf->SetFont('helvetica', 'B', 7.2);
$pdf->Cell(130, 4.2, 'PROPRIETÁRIO / ARMADOR:', 1, 0, 'L', true);
$pdf->Cell(60, 4.2, 'CPF/CNPJ: ' . h($c['proprietario_cpf_cnpj']), 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.0);
$pdf->Cell(25, 4.2, 'Nome:', 1, 0, 'L');
$pdf->Cell(165, 4.2, h($c['proprietario_nome']), 1, 1, 'L');
$pdf->Cell(25, 5.0, 'Endereço:', 1, 0, 'L');
$pdf->MultiCell(165, 5.0, h($c['proprietario_endereco']), 1, 'L', false, 1, '', '', true, 0, false, true, 5.0, 'M');

$pdf->Ln(1.0);
$pdf->SetFont('helvetica', 'B', 7.2);
$pdf->Cell(130, 4.2, 'ESTALEIRO / CONSTRUTOR:', 1, 0, 'L', true);
$pdf->Cell(60, 4.2, 'CPF/CNPJ: ' . h($c['estaleiro_cpf_cnpj']), 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.0);
$pdf->Cell(25, 4.2, 'Nome:', 1, 0, 'L');
$pdf->Cell(165, 4.2, h($c['estaleiro_nome']), 1, 1, 'L');
$pdf->Cell(25, 5.0, 'Endereço:', 1, 0, 'L');
$pdf->MultiCell(165, 5.0, h($c['estaleiro_endereco']), 1, 'L', false, 1, '', '', true, 0, false, true, 5.0, 'M');

// SEÇÃO OBSERVAÇÕES (NORMAM-202 ANEXO 3-A)
// Estruturada em caixa enquadrada contínua que preenche a área técnica de forma elegante
$pdf->Ln(1.5);
$pdf->SetFillColor(238, 238, 238);
$pdf->SetFont('helvetica', 'B', 7.4);
$pdf->Cell(190, 4.5, 'OBSERVAÇÕES:', 1, 1, 'L', true);

$obsBoxY = $pdf->GetY();
$obsBoxH = 240.5 - $obsBoxY;
$pdf->Rect(10, $obsBoxY, 190, $obsBoxH);

$obsFont = 6.6;
$obsLineH = 3.0;
if (mb_strlen($observacoes) > 900) {
    $obsFont = 5.8;
    $obsLineH = 2.5;
}
$pdf->SetXY(11, $obsBoxY + 1.2);
$pdf->SetFont('dejavusans', '', $obsFont);
$pdf->MultiCell(188, $obsLineH, $observacoes, 0, 'L');

// Expedição
$pdf->SetXY(10, 241.5);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(190, 4.5, 'Expedido em ' . ($c['local_emissao'] ?: 'Belém-PA') . ', em ' . lcDataExtenso($c['data_emissao']) . '.', 0, 1, 'C');

// Bloco de Assinatura Oficial (Área reservada para o carimbo digital da Autoridade Técnica)
$aprovacao_pdf_layout = ['bloco_pagina' => 1, 'bloco_y' => 248];

// Se o documento estiver em rascunho (sem aprovação digital e sem geração de arquivo oficial),
// exibe um quadro prévio indicando o aguardo da assinatura eletrônica
if (empty($c['assinado']) && !isset($salvar_pdf_caminho)) {
    $pdf->SetDrawColor(180, 185, 183);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect(10, 248, 190, 38);
    $pdf->SetXY(10, 264);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(190, 5, 'DOCUMENTO AGUARDANDO ASSINATURA ELETRÔNICA DO ANALISTA TÉCNICO NAVAL', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
}

// Rodapé de segurança e validação normativa
$pdf->SetXY(10, 287.0);
$pdf->SetFont('helvetica', '', 5.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(190, 2.5, 'Documento assinado eletronicamente conforme normas da Autoridade Marítima (NORMAM-202/DPC Anexo 3-A) · Autenticidade verificável via QR Code', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$nomeArquivo = $c['tipo_licenca'] . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $c['numero_lc']) . '.pdf';
if (isset($salvar_pdf_caminho) && $salvar_pdf_caminho) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nomeArquivo, 'I');
    exit;
}
