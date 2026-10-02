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

require_once __DIR__ . '/../../includes/certificado_pdf_marca_dagua.php';

if (!class_exists('ProtocoloOficioNavalPdf')) {
    class ProtocoloOficioNavalPdf extends CertificadoPdfComMarcaDagua
    {
        public string $numeroOficio = '';
        public string $codigoIntegridade = '';

        public function Footer(): void
        {
            $this->SetY(-14);
            $this->SetDrawColor(0, 61, 52);
            $this->SetLineWidth(0.4);
            $this->Line(16, $this->GetY(), 194, $this->GetY());
            $this->Ln(1.5);
            $this->SetTextColor(0, 61, 52);
            $this->SetFont('helvetica', 'B', 7);
            $this->Cell(85, 3.5, 'Ofício ' . $this->numeroOficio . ' · Amazon Certificadora Naval', 0, 0, 'L');
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor(85, 105, 98);
            $this->Cell(70, 3.5, 'Validação Criptográfica: ' . $this->codigoIntegridade, 0, 0, 'C');
            $this->Cell(23, 3.5, 'Página 1 de 1', 0, 0, 'R');
            $this->Ln(3.2);
            $this->SetFont('helvetica', 'I', 6);
            $this->SetTextColor(100, 120, 115);
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
$pdf->SetMargins(16, 10, 16);
$pdf->SetAutoPageBreak(false); // Garante rigorosamente PÁGINA ÚNICA
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// =========================================================================
// OFÍCIO OFICIAL AMAZON NAVAL (PÁGINA ÚNICA - PADRÃO CORPORATIVO NAVAL)
// =========================================================================

// 1. Faixa Superior Institucional (Inspirada no modelo de proposta)
$pdf->SetFillColor(0, 61, 52); // Verde escuro institucional #003D34
$pdf->Rect(16, 7, 178, 2.5, 'F');
$pdf->SetFillColor(197, 160, 89); // Filete dourado da bússola naval #C5A059
$pdf->Rect(16, 9.5, 178, 0.6, 'F');

// 2. Logotipo oficial centralizado
$logoFile = __DIR__ . '/../../img/logo.png';
if (is_file($logoFile)) {
    $pdf->Image($logoFile, 94.5, 12, 21, 0, 'PNG', '', '', false, 150);
    $pdf->SetY(33.5);
} else {
    $pdf->SetY(14);
}

// 3. Cabeçalho Institucional Oficial Harmonizado com a Logo
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 61, 52);
$pdf->Cell(0, 4.5, 'AMAZON NAVAL', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->SetTextColor(0, 96, 78);
$pdf->Cell(0, 3.5, 'ENTIDADE CERTIFICADORA NAVAL & ENGENHARIA', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 6.8);
$pdf->SetTextColor(60, 80, 75);
$pdf->Cell(0, 3.0, 'TRAVESSA QUINTINO BOCAIÚVA, Nº 2301 EDIFÍCIO ROGÉLIO FERNANDEZ, SALA 1116. CREMAÇÃO, Belém, PA – (91) 99111-2065', 0, 1, 'C');
$pdf->Cell(0, 3.0, 'www.amazonnaval.com.br - amazoncertificados@gmail.com', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetDrawColor(0, 61, 52);
$pdf->SetLineWidth(0.4);
$pdf->Line(16, $pdf->GetY(), 194, $pdf->GetY());
$pdf->Ln(2.5);

// 4. Tabela Estruturada do Ofício (Colunas no padrão da proposta com fundo suave institucional)
$htmlTabela = '
<table cellpadding="3.5" cellspacing="0" style="border-collapse: collapse; font-family: helvetica; width: 100%; border: 0.5px solid #003D34;">
    <tr>
        <td width="30%" style="font-size: 8pt; font-weight: bold; color: #003D34; background-color: #E7F3EE; border: 0.5px solid #003D34;">
            TIPO DE DOCUMENTO
        </td>
        <td width="70%" style="font-size: 8pt; color: #1F2925; background-color: #FFFFFF; border: 0.5px solid #003D34;">
            <b>OFÍCIO Nº</b> ' . $e($numeroOficio) . '
        </td>
    </tr>
    <tr>
        <td width="30%" style="font-size: 8pt; font-weight: bold; color: #003D34; background-color: #E7F3EE; border: 0.5px solid #003D34;">
            DESTINATÁRIO (PARA):
        </td>
        <td width="70%" style="font-size: 8pt; color: #1F2925; background-color: #FFFFFF; border: 0.5px solid #003D34;">
            ' . $e($destinatarioPara) . '
        </td>
    </tr>
    <tr>
        <td width="30%" style="font-size: 8pt; font-weight: bold; color: #003D34; background-color: #E7F3EE; border: 0.5px solid #003D34;">
            À ATENÇÃO (A/C):
        </td>
        <td width="70%" style="font-size: 8pt; color: #1F2925; background-color: #FFFFFF; border: 0.5px solid #003D34;">
            ' . $e($destinatarioAC) . '
        </td>
    </tr>
    <tr>
        <td width="30%" style="font-size: 8pt; font-weight: bold; color: #003D34; background-color: #E7F3EE; border: 0.5px solid #003D34;">
            ASSUNTO:
        </td>
        <td width="70%" style="font-size: 8pt; color: #1F2925; background-color: #FFFFFF; border: 0.5px solid #003D34;">
            Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM.
        </td>
    </tr>
</table>
';
$pdf->writeHTML($htmlTabela, true, false, true, false, '');

$pdf->Ln(3.5);

// 5. Corpo Formal e Tabela Estruturada de Documentos Técnicos ("colunas e tabelas")
$htmlCorpo = '
<div style="font-family: helvetica; font-size: 8.8pt; color: #1F2925; line-height: 1.45;">
    <p style="margin-bottom: 6px;">Prezado Senhor,</p>

    <p style="text-align: justify; margin-bottom: 8px;">
        Atendendo ao disposto no artigo da ' . $e($normamRef) . ', a Entidade Certificadora Amazon Naval vem, através do presente ofício, encaminhar os documentos anexados da seguinte embarcação:
    </p>

    <table cellpadding="4" cellspacing="0" style="border-collapse: collapse; font-family: helvetica; width: 100%; border: 0.5px solid #003D34; margin-bottom: 8px;">
        <thead>
            <tr style="background-color: #003D34; color: #FFFFFF;">
                <th width="32%" style="font-size: 7.8pt; font-weight: bold; border: 0.5px solid #003D34; text-align: left;">
                    EMBARCAÇÃO VINCULADA
                </th>
                <th width="68%" style="font-size: 7.8pt; font-weight: bold; border: 0.5px solid #003D34; text-align: left;">
                    DOCUMENTOS TÉCNICOS ENCAMINHADOS
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="32%" style="font-size: 8pt; color: #1F2925; background-color: #F8FCFA; border: 0.5px solid #B8D9CC; vertical-align: top;">
                    <b style="color: #003D34; font-size: 8.5pt;">' . $e(mb_strtoupper($d['embarcacao_nome'], 'UTF-8')) . '</b>' .
                    (!empty($d['registro']) ? '<br><span style="font-size: 7.2pt; color: #557067;">Reg. Marinha: ' . $e($d['registro']) . '</span>' : '') .
                    (!empty($d['cliente_nome']) ? '<br><span style="font-size: 7.2pt; color: #557067;">Armador: ' . $e($d['cliente_nome']) . '</span>' : '') . '
                </td>
                <td width="68%" style="font-size: 8pt; color: #1F2925; background-color: #FFFFFF; border: 0.5px solid #B8D9CC; vertical-align: top; line-height: 1.4;">
                    • ' . $e($citacaoDocumentos) . '
                </td>
            </tr>
        </tbody>
    </table>

    <p style="margin-bottom: 6px;">
        ' . $e($dataPorExtenso) . '
    </p>

    <p style="margin-bottom: 4px;">
        Atenciosamente,
    </p>
</div>
';
$pdf->writeHTML($htmlCorpo, true, false, true, false, '');

// 6. Quadro de Assinatura Delimitado e Chancela Digital (Estilo Proposta)
$pdf->Ln(2);
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
    $pdf->Image($tmpSigFile, 84, $yAssinatura, 42, 15, 'PNG', '', '', true, 300, 'C');
    if (str_contains($tmpSigFile, 'sig_of_')) @unlink($tmpSigFile);
    $pdf->SetY($yAssinatura + 16);
} else {
    $pdf->SetY($yAssinatura + 8);
    $pdf->SetDrawColor(0, 61, 52);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(70, $pdf->GetY(), 140, $pdf->GetY());
    $pdf->Ln(1.5);
}

// Nome e Cargo do Assinante
$pdf->SetFont('helvetica', 'B', 9.5);
$pdf->SetTextColor(0, 61, 52);
$pdf->Cell(0, 4.2, $assinanteNome, 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(74, 107, 99);
$pdf->Cell(0, 3.8, $assinanteCargo, 0, 1, 'C');

// Carimbo / Selo de Autenticidade Digital
$pdf->Ln(1.5);
if ($assinado) {
    $dtAss = !empty($d['assinatura_em']) ? date('d/m/Y \à\s H:i:s', strtotime($d['assinatura_em'])) : date('d/m/Y H:i:s');
    $htmlBadge = '
    <table cellpadding="3" cellspacing="0" style="margin: 0 auto; width: 75%; border: 0.5px solid #B8D9CC; background-color: #E7F3EE; text-align: center;">
        <tr>
            <td style="font-size: 6.8pt; font-weight: bold; color: #087653;">
                CHANCELA DIGITAL COM FÉ PÚBLICA NAVAL · SISTEMA AMAZON NAVAL
            </td>
        </tr>
        <tr>
            <td style="font-size: 6.2pt; color: #4A6B63;">
                Data: ' . $e($dtAss) . ' · IP: ' . $e($d['assinatura_ip'] ?: '127.0.0.1') . ' · Validação Criptográfica: ' . $e($codigoIntegridade) . '
            </td>
        </tr>
    </table>';
} else {
    $htmlBadge = '
    <table cellpadding="3" cellspacing="0" style="margin: 0 auto; width: 75%; border: 0.5px solid #D0DDD8; background-color: #F8FAF9; text-align: center;">
        <tr>
            <td style="font-size: 6.5pt; font-style: italic; color: #6A857D;">
                (Ofício oficial emitido no sistema aguardando assinatura digital)
            </td>
        </tr>
    </table>';
}
$pdf->writeHTML($htmlBadge, true, false, true, false, '');

// Finalização do PDF
if ($interno) {
    $pdf->Output($salvar_pdf_dossie_caminho, 'F');
    @chmod($salvar_pdf_dossie_caminho, 0666);
    return;
}

$nomeArquivoSaida = 'Oficio_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOficio) . '.pdf';
$pdf->Output($nomeArquivoSaida, 'I');
exit;
