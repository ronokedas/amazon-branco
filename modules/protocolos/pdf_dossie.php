<?php
/**
 * Emissão do Ofício Oficial de Encaminhamento e Dossiê Naval
 * Local: modules/protocolos/pdf_dossie.php
 * Modelo padronizado conforme diretrizes DPC/NORMAM e padrão da Amazon Naval.
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
$emitidoEm = date('d/m/Y H:i:s');
$labels = protocoloRotulosStatus();

$docsCitados = [];
foreach ($itens as $it) {
    // Filtrar somente itens de eventos de saída/envio ou confirmados
    if (empty($it['mov_status']) || in_array($it['mov_status'], ['CONFIRMADA', 'RETIFICADA'], true)) {
        $docsCitados[] = formatarCitacaoItemNaval($it);
    }
}
$docsCitados = array_unique(array_filter($docsCitados));

if (empty($docsCitados)) {
    // Se ainda não houver itens anexados na saída, buscar vínculos diretos da ficha do dossiê
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
}

$citacaoDocumentos = !empty($docsCitados) ? implode('; ', $docsCitados) : 'AM-OF/ANEXOS: Conforme manifesto em anexo';

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

class ProtocoloOficioNavalPdf extends TCPDF
{
    public string $numeroOficio = '';
    public string $codigoIntegridade = '';

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetDrawColor(200, 205, 203);
        $this->SetLineWidth(0.2);
        $this->Line(18, $this->GetY(), 192, $this->GetY());
        $this->Ln(1.2);
        $this->SetTextColor(90, 100, 95);
        $this->SetFont('helvetica', '', 7);
        $this->Cell(85, 4, 'Ofício ' . $this->numeroOficio . ' · Amazon Certificadora Naval', 0, 0, 'L');
        $this->Cell(55, 4, 'Autenticidade: ' . $this->codigoIntegridade, 0, 0, 'C');
        $this->Cell(34, 4, 'Pág. ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new ProtocoloOficioNavalPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->numeroOficio = $numeroOficio;
$pdf->codigoIntegridade = $codigoIntegridade;
$pdf->SetCreator('Amazon Certificadora Naval');
$pdf->SetAuthor('Amazon Certificadora Naval');
$pdf->SetTitle('Ofício ' . $numeroOficio . ' - ' . $d['embarcacao_nome']);
$pdf->SetSubject('Encaminhamento de documentos emitidos/aprovados para arquivo nesta OM');
$pdf->SetMargins(18, 16, 18);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ==========================================
// PÁGINA 1: O OFÍCIO OFICIAL (ESPELHO DA FOTO)
// ==========================================

// Logo centralizada
$logoFile = __DIR__ . '/../../img/logo.png';
if (is_file($logoFile)) {
    $pdf->Image($logoFile, 95, 12, 20, 0, 'PNG', '', '', true, 300, 'C');
    $pdf->SetY(33);
} else {
    $pdf->SetY(16);
}

// Cabeçalho da Entidade Certificadora
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(20, 30, 25);
$pdf->Cell(0, 5, 'AMAZON NAVAL', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7.6);
$pdf->SetTextColor(50, 60, 55);
$pdf->Cell(0, 4, 'TRAVESSA QUINTINO BOCAIÚVA, Nº 2301 EDIFÍCIO ROGÉLIO FERNANDEZ, SALA 1116. CREMAÇÃO,', 0, 1, 'C');
$pdf->Cell(0, 4, 'BELÉM, PA – (91) 99111-2065.', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 7.6);
$pdf->Cell(0, 4, 'www.amazonaval.com.br - amazoncertificados@gmail.com', 0, 1, 'C');

$pdf->Ln(4);

// Tabela do Ofício (Idêntica ao modelo operacional da empresa)
$htmlTabela = '
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; border-color: #222222; font-family: helvetica; width: 100%;">
    <tr>
        <td width="48%" style="font-size: 8.5pt; font-weight: bold; background-color: #ffffff;">Tipo de Documento: OFÍCIO</td>
        <td width="52%" style="font-size: 8.5pt; font-weight: bold; background-color: #ffffff;">Núm. Doc.: ' . $e($numeroOficio) . '</td>
    </tr>
    <tr>
        <td width="16%" style="font-size: 8.5pt; font-weight: bold; background-color: #ffffff;">Para:</td>
        <td width="84%" style="font-size: 8.5pt; background-color: #ffffff;">' . $e($destinatarioPara) . '</td>
    </tr>
    <tr>
        <td width="16%" style="font-size: 8.5pt; font-weight: bold; background-color: #ffffff;">A/C:</td>
        <td width="84%" style="font-size: 8.5pt; background-color: #ffffff;">' . $e($destinatarioAC) . '</td>
    </tr>
    <tr>
        <td width="16%" style="font-size: 8.5pt; font-weight: bold; background-color: #ffffff;">Assunto:</td>
        <td width="84%" style="font-size: 8.5pt; background-color: #ffffff;">Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM.</td>
    </tr>
</table>
';
$pdf->writeHTML($htmlTabela, true, false, true, false, '');

$pdf->Ln(6);

// Texto formal do Ofício
$htmlCorpo = '
<div style="font-family: helvetica; font-size: 9.5pt; color: #111111; line-height: 1.6;">
    <p style="margin-bottom: 14px;">Prezado Senhor,</p>

    <p style="text-align: justify; text-indent: 0px; margin-bottom: 18px;">
        Atendendo ao disposto no artigo da ' . $e($normamRef) . ', a Entidade Certificadora Amazon Naval vem, através do presente ofício, encaminhar os documentos anexados da seguinte embarcação:
    </p>

    <div style="margin-bottom: 22px; padding-left: 4px;">
        • <b>' . $e(mb_strtoupper($d['embarcacao_nome'], 'UTF-8')) . '</b> – ' . $e($citacaoDocumentos) . '
    </div>

    <p style="margin-bottom: 18px;">
        ' . $e($dataPorExtenso) . '
    </p>

    <p style="margin-bottom: 10px;">
        Atenciosamente,
    </p>
</div>
';
$pdf->writeHTML($htmlCorpo, true, false, true, false, '');

// Quadro de Assinatura Digital do Ofício
$pdf->Ln(4);
$yAssinatura = $pdf->GetY();

// Processar imagem de assinatura se existir
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
    // Centralizar imagem da assinatura
    $pdf->Image($tmpSigFile, 80, $yAssinatura, 50, 20, 'PNG', '', '', true, 200, 'C');
    if (str_contains($tmpSigFile, 'sig_of_')) @unlink($tmpSigFile);
    $pdf->SetY($yAssinatura + 21);
} else {
    $pdf->SetY($yAssinatura + 14);
    $pdf->SetDrawColor(40, 40, 40);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(68, $pdf->GetY(), 142, $pdf->GetY());
    $pdf->Ln(1.5);
}

// Nomes e Cargo
$pdf->SetFont('helvetica', 'B', 9.5);
$pdf->SetTextColor(20, 25, 22);
$pdf->Cell(0, 4.5, $assinanteNome, 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(60, 65, 62);
$pdf->Cell(0, 4, $assinanteCargo, 0, 1, 'C');

if ($assinado) {
    $dtAss = !empty($d['assinatura_em']) ? date('d/m/Y \à\s H:i:s', strtotime($d['assinatura_em'])) : date('d/m/Y H:i:s');
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(8, 118, 83);
    $pdf->Cell(0, 3.5, 'Documento Assinado Digitalmente via Sistema Amazon Naval', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(90, 105, 100);
    $pdf->Cell(0, 3.2, 'Assinado em ' . $dtAss . ' · IP: ' . ($d['assinatura_ip'] ?: '127.0.0.1') . ' · Hash: ' . $codigoIntegridade, 0, 1, 'C');
} else {
    $pdf->Ln(1.5);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetTextColor(140, 145, 142);
    $pdf->Cell(0, 3.5, '(Ofício emitido aguardando assinatura digital no sistema)', 0, 1, 'C');
}

// =========================================================================
// PÁGINA 2: MANIFESTO DE ENTREGA E CUSTÓDIA DOCUMENTAL (SEGURANÇA NAVAL)
// =========================================================================
if (!empty($itens) || !empty($documentos)) {
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(8, 118, 83);
    $pdf->Cell(0, 6, 'ANEXO AO OFÍCIO: MANIFESTO DE ITENS E CUSTÓDIA DOCUMENTAL', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(60, 75, 70);
    $pdf->Cell(0, 4, 'Relação discriminada dos documentos técnicos e oficiais vinculados à embarcação ' . $d['embarcacao_nome'], 0, 1, 'L');
    $pdf->Ln(3);

    $linhasTabela = '';
    $seq = 1;
    foreach ($itens as $it) {
        $linhasTabela .= '
        <tr nobr="true" style="font-size: 7.5pt;">
            <td align="center" style="border: 1px solid #c8d8d2; padding: 4px;">' . $seq++ . '</td>
            <td style="border: 1px solid #c8d8d2; padding: 4px;"><b>' . $e($it['descricao']) . '</b>' . (!empty($it['numero_revisao']) ? '<br><span style="color: #557067;">Ref/Rev: ' . $e($it['numero_revisao']) . '</span>' : '') . '</td>
            <td style="border: 1px solid #c8d8d2; padding: 4px;">' . $e(str_replace('_', ' ', $it['categoria'] ?: 'DOCUMENTO')) . '</td>
            <td style="border: 1px solid #c8d8d2; padding: 4px;">' . $e($it['suporte']) . ' · ' . $e(str_replace('_', ' ', $it['forma'])) . '</td>
            <td align="center" style="border: 1px solid #c8d8d2; padding: 4px;"><b>' . (int)$it['quantidade'] . '</b></td>
            <td style="border: 1px solid #c8d8d2; padding: 4px;">' . $e($it['condicao_documento'] ?: 'Conferido no ato') . ($it['requer_devolucao'] ? '<br><b style="color: #b33900;">● Exige devolução</b>' : '') . '</td>
        </tr>';
    }

    $htmlManifesto = '
    <table cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; border: 1px solid #087653;">
        <thead>
            <tr style="background-color: #087653; color: #ffffff; font-size: 7.5pt; font-weight: bold;">
                <th width="6%" align="center" style="border: 1px solid #087653;">#</th>
                <th width="38%" style="border: 1px solid #087653;">Documento / Relatório</th>
                <th width="18%" style="border: 1px solid #087653;">Categoria</th>
                <th width="16%" style="border: 1px solid #087653;">Suporte/Forma</th>
                <th width="6%" align="center" style="border: 1px solid #087653;">Qtd</th>
                <th width="16%" style="border: 1px solid #087653;">Condição / Custódia</th>
            </tr>
        </thead>
        <tbody>
            ' . $linhasTabela . '
        </tbody>
    </table>
    ';
    $pdf->writeHTML($htmlManifesto, true, false, true, false, '');

    $pdf->Ln(6);
    $htmlSeguranca = '
    <table cellpadding="5" cellspacing="0" style="border: 1px solid #b8d9cc; background-color: #f7fbf9; width: 100%;">
        <tr>
            <td width="75%" style="font-size: 7.2pt; color: #334d44;">
                <strong style="color: #087653;">FÉ PÚBLICA E VALIDAÇÃO DE AUTENTICIDADE:</strong><br>
                Este manifesto e o ofício a que se vincula foram emitidos sob as regras da Marinha do Brasil (DPC/NORMAM) e possuem código criptográfico único e imutável SHA-256.<br>
                Chave de integridade: <code>' . $codigoIntegridade . '</code>
            </td>
            <td width="25%" align="center" style="font-size: 6.8pt; color: #557067;">
                Certificadora Amazon Naval<br>
                Belém/PA - Brasil
            </td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($htmlSeguranca, true, false, true, false, '');
}

// Finalização
if ($interno) {
    $pdf->Output($salvar_pdf_dossie_caminho, 'F');
    @chmod($salvar_pdf_dossie_caminho, 0666);
    return;
}

$nomeArquivoSaida = 'Oficio_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOficio) . '.pdf';
$pdf->Output($nomeArquivoSaida, 'I');
exit;
