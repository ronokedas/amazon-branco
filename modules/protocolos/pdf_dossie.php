<?php
/**
 * Emissão do Ofício Oficial de Encaminhamento e Dossiê Naval
 * Local: modules/protocolos/pdf_dossie.php
 * Modelo oficial em PÁGINA ÚNICA padronizado conforme diretrizes DPC/NORMAM e padrão da Amazon Naval.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$interno = isset($salvar_pdf_dossie_caminho, $dossie_pdf_id);
if (!$interno) protocoloExigirAcesso();
$id = $interno ? (string)$dossie_pdf_id : trim($_GET['id'] ?? '');

$q = $pdo->prepare("SELECT d.*, e.nome embarcacao_nome, e.registro, c.nome cliente_nome,
  u.nome criador_nome, um.nome unidade_nome, um.tipo unidade_tipo, uc.nome cancelador_nome
  FROM protocolo_dossies d
  JOIN embarcacoes e ON e.id = d.embarcacao_id
  LEFT JOIN clientes c ON c.id = d.cliente_id
  LEFT JOIN usuarios u ON u.id = d.criado_por
  LEFT JOIN usuarios uc ON uc.id = d.cancelado_por
  LEFT JOIN protocolo_unidades_maritimas um ON um.id = d.unidade_maritima_id
  WHERE d.id = :id");
$q->execute([':id' => $id]);
$d = $q->fetch(PDO::FETCH_ASSOC);

if (!$d) {
    http_response_code(404);
    exit('Dossiê não encontrado.');
}
if (!$interno && !protocoloUsuarioPodeAcessar($pdo, $d)) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Carregar movimentações
$q = $pdo->prepare("SELECT m.*, um.nome unidade_nome, u.nome criador_nome, uc.nome confirmador_nome
  FROM protocolo_movimentacoes m
  LEFT JOIN protocolo_unidades_maritimas um ON um.id = m.unidade_maritima_id
  LEFT JOIN usuarios u ON u.id = m.criado_por
  LEFT JOIN usuarios uc ON uc.id = m.confirmado_por
  WHERE m.dossie_id = :id ORDER BY m.sequencia");
$q->execute([':id' => $id]);
$movimentacoes = $q->fetchAll(PDO::FETCH_ASSOC);

// Carregar itens de movimentação
$q = $pdo->prepare("SELECT i.*, m.sequencia, m.tipo AS mov_tipo, m.natureza AS mov_natureza, m.status AS mov_status
  FROM protocolo_movimentacao_itens i
  JOIN protocolo_movimentacoes m ON m.id = i.movimentacao_id
  WHERE m.dossie_id = :id 
  ORDER BY m.sequencia, i.criado_em, i.id");
$q->execute([':id' => $id]);
$itens = $q->fetchAll(PDO::FETCH_ASSOC);

// Carregar comprovantes/documentos
$q = $pdo->prepare("SELECT c.*, m.sequencia movimentacao_sequencia, u.nome criador_nome
  FROM protocolo_comprovantes c
  LEFT JOIN protocolo_movimentacoes m ON m.id = c.movimentacao_id
  LEFT JOIN usuarios u ON u.id = c.criado_por
  WHERE c.dossie_id = :id ORDER BY c.criado_em, c.id");
$q->execute([':id' => $id]);
$documentos = $q->fetchAll(PDO::FETCH_ASSOC);

// Carregar auditoria
$q = $pdo->prepare("SELECT a.*, u.nome usuario_nome FROM protocolo_auditoria a
  LEFT JOIN usuarios u ON u.id = a.usuario_id
  WHERE a.dossie_id = :id ORDER BY a.criado_em, a.id");
$q->execute([':id' => $id]);
$auditoria = $q->fetchAll(PDO::FETCH_ASSOC);

// Cálculo de Hash de Integridade
$ordenar = function (&$valor) use (&$ordenar): void {
    if (!is_array($valor)) return;
    foreach ($valor as &$item) $ordenar($item);
    if (!array_is_list($valor)) ksort($valor, SORT_STRING);
};
$baseIntegridade = ['dossie' => $d, 'movimentacoes' => $movimentacoes, 'itens' => $itens, 'documentos' => $documentos, 'auditoria' => $auditoria];
$ordenar($baseIntegridade);
$hashDados = hash('sha256', json_encode($baseIntegridade, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
$codigoIntegridade = strtoupper(substr($hashDados, 0, 24));

// Coleta estrita dos documentos enviados
$docsCitados = [];
foreach ($itens as $it) {
    if (empty($it['mov_status']) || $it['mov_status'] !== 'CANCELADA') {
        $docsCitados[] = formatarCitacaoItemNaval($it);
    }
}
foreach ($documentos as $doc) {
    $nomeDoc = trim($doc['nome_original'] ?? '');
    if ($nomeDoc !== '') {
        $docsCitados[] = $nomeDoc;
    }
}
$docsCitados = array_unique(array_filter($docsCitados));

if (empty($docsCitados)) {
    if (!empty($d['analise_id'])) {
        $qA = $pdo->prepare("SELECT numero FROM analises_planos WHERE id = :id");
        $qA->execute([':id' => $d['analise_id']]);
        if ($numA = $qA->fetchColumn()) $docsCitados[] = 'AM-REL:AP ' . $numA;
    }
    if (!empty($d['vistoria_id'])) {
        $qV = $pdo->prepare("SELECT numero FROM vistorias WHERE id = :id");
        $qV->execute([':id' => $d['vistoria_id']]);
        if ($numV = $qV->fetchColumn()) $docsCitados[] = 'AM-REL-V: ' . $numV;
    }
    if (!empty($d['certificado_tipo']) && !empty($d['certificado_id'])) {
        $docsCitados[] = 'AM-' . strtoupper($d['certificado_tipo']) . ': ' . $d['certificado_id'];
    }
    if (!empty($d['proposta_id'])) {
        $qP = $pdo->prepare("SELECT numero FROM propostas WHERE id = :id");
        $qP->execute([':id' => $d['proposta_id']]);
        if ($numP = $qP->fetchColumn()) $docsCitados[] = 'AM-PROP: ' . $numP;
    }
}

$citacaoDocumentos = !empty($docsCitados) ? implode('; ', $docsCitados) : 'AM-OF/ANEXOS: Documentos técnicos constantes do processo';

// Dados do Cabeçalho e Destinatário do Ofício
$numeroOficio = !empty($d['numero_oficio']) 
    ? $d['numero_oficio'] 
    : 'AM-OF' . str_pad((string)preg_replace('/[^0-9]/', '', $d['numero'] ?: '1'), 3, '0', STR_PAD_LEFT) . '/' . date('Y', strtotime($d['criado_em'] ?: 'now'));

$destinatarioPara = !empty($d['unidade_nome']) 
    ? mb_strtoupper($d['unidade_nome'], 'UTF-8') 
    : 'CAPITANIA DOS PORTOS DA AMAZÔNIA ORIENTAL';

$destinatarioAC = !empty($d['destinatario_autoridade']) 
    ? mb_strtoupper($d['destinatario_autoridade'], 'UTF-8') 
    : 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL';

$normamRef = !empty($d['normam_referencia']) ? $d['normam_referencia'] : 'NORMAM 202/DPC';
$dataPorExtenso = formatarDataExtensoNaval(!empty($d['assinatura_em']) ? $d['assinatura_em'] : ($d['criado_em'] ?? null));

// Assinante Oficial
$assinanteNome = !empty($d['assinante_nome']) ? mb_strtoupper($d['assinante_nome'], 'UTF-8') : 'THAINARA BARROS';
$assinanteCargo = !empty($d['assinante_cargo']) ? $d['assinante_cargo'] : 'Secretária';
$assinado = !empty($d['assinado']);

if (!class_exists('ProtocoloOficioNavalPdf')) {
    class ProtocoloOficioNavalPdf extends TCPDF
    {
        public string $numeroOficio = '';
        public string $codigoIntegridade = '';

        public function Footer(): void
        {
            $this->SetY(-14);
            $this->SetDrawColor(13, 73, 65);
            $this->SetLineWidth(0.3);
            $this->Line(16, $this->GetY(), 194, $this->GetY());
            $this->Ln(1.5);
            $this->SetTextColor(85, 105, 98);
            $this->SetFont('helvetica', '', 7);
            $this->Cell(85, 3.5, 'Ofício ' . $this->numeroOficio . ' · Amazon Certificadora Naval', 0, 0, 'L');
            $this->Cell(70, 3.5, 'Validação Criptográfica: ' . $this->codigoIntegridade, 0, 0, 'C');
            $this->Cell(23, 3.5, 'Página 1 de 1', 0, 0, 'R');
            $this->Ln(3.2);
            $this->SetFont('helvetica', 'I', 6);
            $this->SetTextColor(120, 135, 130);
            $this->Cell(0, 3, 'Documento oficial emitido em conformidade com as diretrizes da DPC / Marinha do Brasil (NORMAM-202).', 0, 0, 'C');
        }
    }
}

$pdf = new ProtocoloOficioNavalPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->numeroOficio = $numeroOficio;
$pdf->codigoIntegridade = $codigoIntegridade;
$pdf->SetCreator('Amazon Certificadora Naval');
$pdf->SetAuthor('Amazon Certificadora Naval');
$pdf->SetTitle('Ofício ' . $numeroOficio . ' - ' . $d['embarcacao_nome']);
$pdf->SetSubject('Encaminhamento de documentos emitidos/aprovados para arquivo nesta OM');
$pdf->SetMargins(16, 12, 16);
$pdf->SetAutoPageBreak(false); // Garante rigorosamente PÁGINA ÚNICA
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// =========================================================================
// OFÍCIO OFICIAL AMAZON NAVAL (PÁGINA ÚNICA ESTILIZADA E MODERNA)
// =========================================================================

// 1. Logotipo oficial centralizado (destacado e proporcional ao modelo oficial)
$logoFile = __DIR__ . '/../../img/logo.png';
if (is_file($logoFile)) {
    $pdf->Image($logoFile, 91, 7.5, 28, 0, 'PNG', '', '', true, 300, 'C');
    $pdf->SetY(37);
} else {
    $pdf->SetY(12);
}

// 2. Cabeçalho Institucional
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(13, 73, 65); // Verde naval oficial da Amazon
$pdf->Cell(0, 5, 'AMAZON NAVAL', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->SetTextColor(85, 105, 98);
$pdf->Cell(0, 3.5, 'ENTIDADE CERTIFICADORA NAVAL CREDENCIADA PELA DPC / MARINHA DO BRASIL', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7.2);
$pdf->SetTextColor(55, 65, 60);
$pdf->Cell(0, 3.4, 'TRAVESSA QUINTINO BOCAIÚVA, Nº 2301, EDIFÍCIO ROGÉLIO FERNANDEZ, SALA 1116. CREMAÇÃO,', 0, 1, 'C');
$pdf->Cell(0, 3.4, 'BELÉM, PA – CEP: 66063-015 · FONE: (91) 99111-2065', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 7.2);
$pdf->SetTextColor(13, 73, 65);
$pdf->Cell(0, 3.4, 'www.amazonnaval.com.br · amazoncertificados@gmail.com', 0, 1, 'C');

// Linha decorativa naval dupla (Verde e Dourado)
$pdf->Ln(2);
$yLinha = $pdf->GetY();
$pdf->SetDrawColor(13, 73, 65);
$pdf->SetLineWidth(0.55);
$pdf->Line(16, $yLinha, 194, $yLinha);
$pdf->SetDrawColor(184, 157, 82); // Dourado naval sutil
$pdf->SetLineWidth(0.25);
$pdf->Line(16, $yLinha + 0.8, 194, $yLinha + 0.8);

$pdf->Ln(3.5);

// 3. Tabela Estruturada do Ofício (Modernizada combinando com a identidade visual)
$htmlTabela = '
<table cellpadding="4.5" cellspacing="0" style="border-collapse: collapse; font-family: helvetica; width: 100%; border: 0.8px solid #0d4941;">
    <tr>
        <td width="48%" style="font-size: 8.5pt; font-weight: bold; background-color: #f4f8f6; color: #0d4941; border: 0.8px solid #0d4941;">
            TIPO DE DOCUMENTO: <span style="color: #111111;">OFÍCIO</span>
        </td>
        <td width="52%" style="font-size: 8.5pt; font-weight: bold; background-color: #f4f8f6; color: #0d4941; border: 0.8px solid #0d4941;">
            NÚM. DOC.: <span style="color: #111111;">' . $e($numeroOficio) . '</span>
        </td>
    </tr>
    <tr>
        <td width="15%" style="font-size: 8.5pt; font-weight: bold; background-color: #f4f8f6; color: #0d4941; border: 0.8px solid #0d4941;">
            PARA:
        </td>
        <td width="85%" style="font-size: 8.5pt; color: #111111; border: 0.8px solid #0d4941;">
            ' . $e($destinatarioPara) . '
        </td>
    </tr>
    <tr>
        <td width="15%" style="font-size: 8.5pt; font-weight: bold; background-color: #f4f8f6; color: #0d4941; border: 0.8px solid #0d4941;">
            A/C:
        </td>
        <td width="85%" style="font-size: 8.5pt; font-weight: bold; color: #111111; border: 0.8px solid #0d4941;">
            ' . $e($destinatarioAC) . '
        </td>
    </tr>
    <tr>
        <td width="15%" style="font-size: 8.5pt; font-weight: bold; background-color: #f4f8f6; color: #0d4941; border: 0.8px solid #0d4941;">
            ASSUNTO:
        </td>
        <td width="85%" style="font-size: 8.5pt; color: #111111; border: 0.8px solid #0d4941;">
            Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM.
        </td>
    </tr>
</table>
';
$pdf->writeHTML($htmlTabela, true, false, true, false, '');

$pdf->Ln(6);

// 4. Texto Formal do Ofício
$htmlCorpo = '
<div style="font-family: helvetica; font-size: 9.5pt; color: #1a1a1a; line-height: 1.65;">
    <p style="margin-bottom: 12px; font-weight: bold; color: #0d4941;">Prezado Senhor,</p>

    <p style="text-align: justify; margin-bottom: 16px;">
        Atendendo ao disposto no artigo pertinente da <b>' . $e($normamRef) . '</b>, a Entidade Certificadora Amazon Naval vem, através do presente ofício, encaminhar os documentos anexados da seguinte embarcação:
    </p>

    <div style="margin-bottom: 18px; padding: 8px 12px; background-color: #f9fbfb; border-left: 3px solid #0d4941; border-radius: 3px;">
        • <b style="color: #0d4941;">' . $e(mb_strtoupper($d['embarcacao_nome'], 'UTF-8')) . '</b> – <span style="color: #222222;">' . $e($citacaoDocumentos) . '</span>
    </div>

    <p style="margin-bottom: 16px;">
        ' . $e($dataPorExtenso) . '
    </p>

    <p style="margin-bottom: 6px;">
        Atenciosamente,
    </p>
</div>
';
$pdf->writeHTML($htmlCorpo, true, false, true, false, '');

// 5. Quadro de Assinatura Digital do Ofício
$pdf->Ln(4);
$yAssinatura = $pdf->GetY();

$temImagemAssinatura = false;
$tmpSigFile = null;

if (!empty($d['assinatura_imagem'])) {
    $imgData = $d['assinatura_imagem'];
    if (str_starts_with($imgData, 'data:image')) {
        $imgData = substr($imgData, strpos($imgData, ',') + 1);
        $decoded = base64_decode($imgData);
        if ($decoded !== false) {
            $tmpSigFile = tempnam(sys_get_temp_dir(), 'sig_of_') . '.png';
            file_put_contents($tmpSigFile, $decoded);
            $temImagemAssinatura = true;
        }
    } elseif (is_file(__DIR__ . '/../../' . $imgData)) {
        $tmpSigFile = __DIR__ . '/../../' . $imgData;
        $temImagemAssinatura = true;
    }
} elseif (!empty($d['responsavel_assinatura_id'])) {
    $qResp = $pdo->prepare("SELECT assinatura_arquivo FROM responsaveis_assinatura WHERE id = :id LIMIT 1");
    $qResp->execute([':id' => $d['responsavel_assinatura_id']]);
    $arq = $qResp->fetchColumn();
    if ($arq && is_file(__DIR__ . '/../../' . $arq)) {
        $tmpSigFile = __DIR__ . '/../../' . $arq;
        $temImagemAssinatura = true;
    }
}

if ($temImagemAssinatura && $tmpSigFile) {
    $pdf->Image($tmpSigFile, 82, $yAssinatura, 46, 17, 'PNG', '', '', true, 300, 'C');
    if (str_contains($tmpSigFile, 'sig_of_')) @unlink($tmpSigFile);
    $pdf->SetY($yAssinatura + 18);
} else {
    $pdf->SetY($yAssinatura + 12);
    $pdf->SetDrawColor(13, 73, 65);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(68, $pdf->GetY(), 142, $pdf->GetY());
    $pdf->Ln(1.5);
}

// Nome e Cargo do Assinante
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(13, 73, 65);
$pdf->Cell(0, 4.5, $assinanteNome, 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(60, 75, 70);
$pdf->Cell(0, 4, $assinanteCargo, 0, 1, 'C');

// Carimbo / Selo de Autenticidade Digital
if ($assinado) {
    $dtAss = !empty($d['assinatura_em']) ? date('d/m/Y \à\s H:i:s', strtotime($d['assinatura_em'])) : date('d/m/Y H:i:s');
    $pdf->Ln(1.5);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetTextColor(8, 118, 83);
    $pdf->Cell(0, 3.5, 'Documento Assinado Digitalmente com Fé Pública via Sistema Amazon Naval', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(90, 105, 100);
    $pdf->Cell(0, 3.2, 'Data: ' . $dtAss . ' · IP: ' . ($d['assinatura_ip'] ?: '127.0.0.1') . ' · Hash SHA-256: ' . $codigoIntegridade, 0, 1, 'C');
} else {
    $pdf->Ln(1.5);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetTextColor(130, 140, 135);
    $pdf->Cell(0, 3.5, '(Ofício oficial emitido aguardando assinatura digital no sistema)', 0, 1, 'C');
}

// Finalização do PDF
if ($interno) {
    $pdf->Output($salvar_pdf_dossie_caminho, 'F');
    @chmod($salvar_pdf_dossie_caminho, 0666);
    return;
}

$nomeArquivoSaida = 'Oficio_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOficio) . '.pdf';
$pdf->Output($nomeArquivoSaida, 'I');
exit;
