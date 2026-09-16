<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/certificado_pdf_marca_dagua.php';

$interno = isset($salvar_pdf_caminho, $movimentacao_pdf_id);
if (!$interno) protocoloExigirAcesso();
$movId = $interno ? (string)$movimentacao_pdf_id : trim($_GET['id'] ?? '');

$q = $pdo->prepare("SELECT m.*, d.numero dossie_numero, d.assunto, d.embarcacao_id, d.cliente_id, d.criado_por, d.proposta_id, d.analise_id, d.vistoria_id, e.nome embarcacao_nome, e.registro, c.nome cliente_nome, um.nome unidade_nome, um.tipo unidade_tipo, u.nome responsavel_nome
FROM protocolo_movimentacoes m JOIN protocolo_dossies d ON d.id=m.dossie_id JOIN embarcacoes e ON e.id=d.embarcacao_id LEFT JOIN clientes c ON c.id=d.cliente_id LEFT JOIN protocolo_unidades_maritimas um ON um.id=m.unidade_maritima_id LEFT JOIN usuarios u ON u.id=m.criado_por WHERE m.id=:id");
$q->execute([':id' => $movId]);
$m = $q->fetch(PDO::FETCH_ASSOC);
if (!$m) throw new RuntimeException('Movimentação não encontrada.');

if (!$interno && !protocoloUsuarioPodeAcessar($pdo, $m)) {
    http_response_code(403);
    exit('Acesso negado.');
}
if (!$interno && !in_array($m['status'], ['CONFIRMADA', 'RETIFICADA'], true)) {
    http_response_code(409);
    exit('O PDF somente existe após a confirmação.');
}

if (!$interno && !empty($m['pdf_caminho']) && !isset($_GET['refresh'])) {
    $f = dirname(__DIR__, 2) . '/' . $m['pdf_caminho'];
    if (@is_file($f) && @is_readable($f) && filesize($f) > 50000 && !empty($m['pdf_hash']) && @hash_equals($m['pdf_hash'], (string)@hash_file('sha256', $f))) {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: inline; filename="' . $m['dossie_numero'] . '-' . str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) . '.pdf"');
        readfile($f);
        exit;
    }
}

$itens = protocoloSnapshot($pdo, $movId);
$codigo = strtoupper(substr(hash('sha256', $m['dossie_numero'] . '|' . $m['sequencia'] . '|' . json_encode($itens)), 0, 20));
$seqFormatada = str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT);
$logoPath = dirname(__DIR__, 2) . '/img/logo.png';

if (!class_exists('ComprovanteProtocoloPdf')) {
    class ComprovanteProtocoloPdf extends CertificadoPdfComMarcaDagua
    {
        public string $numeroDossie = '';
        public string $tipoEvento = '';
        public string $sequenciaFormatada = '';
        public string $codigoValida = '';
        public string $logoPath = '';

        public function Header(): void
        {
            if ($this->logoPath !== '' && is_file($this->logoPath)) {
                $this->Image($this->logoPath, 14, 8, 20, 0, 'PNG', '', '', true, 200);
            }
            $this->SetTextColor(8, 118, 83);
            $this->SetFont('helvetica', 'B', 12);
            $this->SetXY(37, 9);
            $this->Cell(100, 5, 'AMAZON NAVAL', 0, 1, 'L');
            $this->SetTextColor(50, 75, 68);
            $this->SetFont('helvetica', '', 7.5);
            $this->SetX(37);
            $this->Cell(100, 4, 'SERVIÇOS DE ENGENHARIA NAVAL & CONSULTORIA', 0, 1, 'L');
            $this->SetFont('helvetica', 'I', 7);
            $this->SetTextColor(85, 110, 102);
            $this->SetX(37);
            $this->Cell(100, 3.5, 'Controle de Tramitação Documental e Custódia Naval', 0, 1, 'L');

            // Badge no canto direito
            $this->SetXY(138, 8);
            $this->SetFillColor(240, 247, 244);
            $this->SetDrawColor(184, 217, 204);
            $this->SetLineWidth(0.3);
            $this->RoundedRect(138, 8, 58, 15, 1.5, '1111', 'DF');
            $this->SetXY(138, 9);
            $this->SetFont('helvetica', 'B', 6.5);
            $this->SetTextColor(8, 118, 83);
            $this->Cell(58, 3.5, 'COMPROVANTE OFICIAL · EVENTO #' . $this->sequenciaFormatada, 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 9);
            $this->SetTextColor(23, 59, 50);
            $this->SetX(138);
            $this->Cell(58, 4.5, $this->tipoEvento, 0, 1, 'C');
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor(70, 95, 87);
            $this->SetX(138);
            $this->Cell(58, 3.5, $this->numeroDossie, 0, 1, 'C');

            // Linha verde inferior do cabeçalho
            $this->SetDrawColor(8, 118, 83);
            $this->SetLineWidth(0.6);
            $this->Line(14, 26, 196, 26);
        }

        public function Footer(): void
        {
            $this->SetY(-14);
            $this->SetDrawColor(200, 215, 210);
            $this->SetLineWidth(0.2);
            $this->Line(14, $this->GetY(), 196, $this->GetY());
            $this->Ln(1.5);
            $this->SetTextColor(85, 105, 98);
            $this->SetFont('helvetica', '', 7);
            $this->Cell(70, 4, $this->numeroDossie . ' · Evento #' . $this->sequenciaFormatada . ' · Amazon Naval', 0, 0, 'L');
            $this->Cell(65, 4, 'Validação: ' . $this->codigoValida, 0, 0, 'C');
            $this->Cell(47, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}

$pdf = new ComprovanteProtocoloPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Amazon Naval');
$pdf->SetAuthor('Amazon Naval');
$pdf->SetTitle('Comprovante ' . $m['dossie_numero'] . '/' . $seqFormatada);
$pdf->numeroDossie = $m['dossie_numero'];
$pdf->tipoEvento = 'COMPROVANTE DE ' . mb_strtoupper($m['tipo'], 'UTF-8');
$pdf->sequenciaFormatada = $seqFormatada;
$pdf->codigoValida = $codigo;
$pdf->logoPath = $logoPath;

$pdf->SetMargins(14, 30, 14);
$pdf->SetHeaderMargin(6);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$linhas = '';
foreach ($itens as $i => $item) {
    $bg = ($i % 2 === 0) ? '#ffffff' : '#f9fcfb';
    $custodiaHtml = $e($item['condicao_documento'] ?: 'Conferido no ato');
    if (!empty($item['requer_devolucao'])) {
        $custodiaHtml .= '<br><span style="color: #b33900; font-weight: bold;">● Original sob custódia (Devolução obrigatória)</span>';
    }
    $linhas .= '
    <tr style="background-color: ' . $bg . ';" nobr="true">
      <td align="center" style="border: 1px solid #d4e3dc; font-size: 8pt;">' . ($i + 1) . '</td>
      <td style="border: 1px solid #d4e3dc; font-size: 8pt;">
        <b>' . $e($item['descricao']) . '</b>' .
        ($item['numero_revisao'] ? '<br><span style="font-size: 7.2pt; color: #557067;">Nº / Revisão: <b>' . $e($item['numero_revisao']) . '</b></span>' : '') . '
      </td>
      <td style="border: 1px solid #d4e3dc; font-size: 7.5pt;">
        ' . $e($item['suporte']) . '<br><span style="color: #557067;">' . $e(str_replace('_', ' ', $item['forma'])) . '</span>
      </td>
      <td align="center" style="border: 1px solid #d4e3dc; font-size: 8pt; font-weight: bold;">' . (int)$item['quantidade'] . '</td>
      <td style="border: 1px solid #d4e3dc; font-size: 7.5pt;">' . $custodiaHtml . '</td>
    </tr>';
}

$dataHoraMov = !empty($m['movimentado_em']) ? date('d/m/Y \à\s H:i', strtotime($m['movimentado_em'])) : 'Não informada';
$registroNaval = !empty($m['registro']) ? ' · ' . $e($m['registro']) : '';
$unidadeMaritima = !empty($m['unidade_nome']) ? $e($m['unidade_nome']) : 'Não aplicável';
$portadorRastreio = trim(($m['portador_nome'] ?? '') . ' ' . ($m['codigo_rastreio'] ?? ''));
$rastreioTexto = $portadorRastreio !== '' ? ' · ' . $e($portadorRastreio) : '';

$html = '
<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #b8d9cc; background-color: #f2f8f5;">
<tr>
  <td width="68%">
    <span style="font-size: 7pt; color: #52756a; font-weight: bold;">ASSUNTO DO DOSSIÊ</span><br>
    <span style="font-size: 9.5pt; color: #087653; font-weight: bold;">' . $e($m['assunto']) . '</span>
  </td>
  <td width="32%" align="right">
    <span style="font-size: 7pt; color: #52756a; font-weight: bold;">DATA / HORA DO REGISTRO</span><br>
    <span style="font-size: 9pt; color: #173b32; font-weight: bold;">' . $dataHoraMov . '</span>
  </td>
</tr>
</table>
<div style="height: 6px;">&nbsp;</div>

<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #d4e3dc; background-color: #ffffff;">
<tr>
  <td width="50%" style="border-right: 1px solid #e0ebe6; border-bottom: 1px solid #e0ebe6; background-color: #f8faf9;">
    <span style="font-size: 7pt; color: #087653; font-weight: bold;">EMBARCAÇÃO</span><br>
    <span style="font-size: 9pt; font-weight: bold; color: #173b32;">' . $e($m['embarcacao_nome']) . $registroNaval . '</span>
  </td>
  <td width="50%" style="border-bottom: 1px solid #e0ebe6; background-color: #f8faf9;">
    <span style="font-size: 7pt; color: #087653; font-weight: bold;">CLIENTE / INTERESSADO</span><br>
    <span style="font-size: 9pt; font-weight: bold; color: #173b32;">' . $e($m['cliente_nome'] ?: 'Não informado') . '</span>
  </td>
</tr>
<tr>
  <td style="border-right: 1px solid #e0ebe6; border-bottom: 1px solid #e0ebe6;">
    <span style="font-size: 7pt; color: #557067;">ORIGEM (QUEM ENTREGOU)</span><br>
    <span style="font-size: 8.5pt; font-weight: bold; color: #173b32;">' . $e($m['origem_nome']) . '</span> <span style="font-size: 7.5pt; color: #557067;">(' . $e($m['origem_tipo']) . ')</span>
  </td>
  <td style="border-bottom: 1px solid #e0ebe6;">
    <span style="font-size: 7pt; color: #557067;">DESTINO (QUEM RECEBEU)</span><br>
    <span style="font-size: 8.5pt; font-weight: bold; color: #173b32;">' . $e($m['destino_nome']) . '</span> <span style="font-size: 7.5pt; color: #557067;">(' . $e($m['destino_tipo']) . ')</span>
  </td>
</tr>
<tr>
  <td style="border-right: 1px solid #e0ebe6;">
    <span style="font-size: 7pt; color: #557067;">LOCALIDADE & MEIO DE ENVIO</span><br>
    <span style="font-size: 8.5pt; color: #173b32;">' . $e($m['cidade'] . '/' . $m['uf'] . ' · ' . $m['meio_envio']) . '</span>
  </td>
  <td>
    <span style="font-size: 7pt; color: #557067;">DESTINO MARÍTIMO / RASTREIO</span><br>
    <span style="font-size: 8.5pt; color: #173b32;">' . $unidadeMaritima . $rastreioTexto . '</span>
  </td>
</tr>
</table>
<div style="height: 8px;">&nbsp;</div>

<div style="font-size: 10pt; font-weight: bold; color: #087653; margin-bottom: 4px;">RELAÇÃO DE DOCUMENTOS TRAMITADOS (' . count($itens) . ')</div>
<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; border: 1px solid #b8d9cc;">
<thead>
  <tr style="background-color: #087653; color: #ffffff;">
    <th width="6%" align="center" style="font-size: 7.5pt; font-weight: bold; border: 1px solid #087653;">#</th>
    <th width="38%" style="font-size: 7.5pt; font-weight: bold; border: 1px solid #087653;">DOCUMENTO APRESENTADO</th>
    <th width="18%" style="font-size: 7.5pt; font-weight: bold; border: 1px solid #087653;">SUPORTE & FORMA</th>
    <th width="8%" align="center" style="font-size: 7.5pt; font-weight: bold; border: 1px solid #087653;">QTD</th>
    <th width="30%" style="font-size: 7.5pt; font-weight: bold; border: 1px solid #087653;">CONDIÇÃO & CUSTÓDIA</th>
  </tr>
</thead>
<tbody>
  ' . $linhas . '
</tbody>
</table>' .
(!empty($m['observacoes']) ? '
<div style="height: 6px;">&nbsp;</div>
<div style="padding: 6px; border-left: 3px solid #087653; background-color: #f7faf9; font-size: 8pt; color: #2d4c42;">
  <b>OBSERVAÇÕES:</b> ' . $e($m['observacoes']) . '
</div>' : '') . '

<div style="height: 15px;">&nbsp;</div>
<table width="100%" cellpadding="0" cellspacing="0">
<tr nobr="true">
  <td width="46%" align="center">
    <div style="border-bottom: 1.5px solid #2d4c42; height: 35px;">&nbsp;</div>
    <div style="padding-top: 5px;">
      <span style="font-size: 8.5pt; font-weight: bold; color: #173b32;">RESPONSÁVEL PELA ENTREGA</span><br>
      <span style="font-size: 7.5pt; color: #507065;">' . $e($m['origem_nome']) . ' (' . $e($m['origem_tipo']) . ')</span>
    </div>
  </td>
  <td width="8%">&nbsp;</td>
  <td width="46%" align="center">
    <div style="border-bottom: 1.5px solid #2d4c42; height: 35px;">&nbsp;</div>
    <div style="padding-top: 5px;">
      <span style="font-size: 8.5pt; font-weight: bold; color: #173b32;">RESPONSÁVEL PELO RECEBIMENTO</span><br>
      <span style="font-size: 7.5pt; color: #507065;">' . $e($m['destino_nome']) . ' (' . $e($m['destino_tipo']) . ')</span>
    </div>
  </td>
</tr>
</table>

<div style="height: 12px;">&nbsp;</div>
<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #cce0d8; background-color: #f4f9f7;" nobr="true">
<tr>
  <td>
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td width="65%">
        <span style="font-size: 7pt; color: #087653; font-weight: bold;">CÓDIGO DE VALIDAÇÃO CRIPTOGRÁFICA (SHA-256):</span><br>
        <span style="font-family: courier, monospace; font-size: 8.5pt; font-weight: bold; color: #173b32;">Código de validação: ' . $codigo . '</span>
      </td>
      <td width="35%" align="right">
        <span style="font-size: 6.8pt; color: #608075;">Emitido digitalmente via Sistema Amazon Naval<br>Chave de Registro e Custódia Naval</span>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="padding-top: 4px; border-top: 1px dashed #d0e2db;">
        <span style="font-size: 6.8pt; color: #527066;">
          Documento gerado pelo sistema Amazon Naval com valor probatório de custódia e tramitação documental.
          Após a confirmação, o conteúdo, a relação documental e o hash criptográfico ficam permanentemente imutáveis.
        </span>
      </td>
    </tr>
    </table>
  </td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

if ($interno) {
    $pdf->Output($salvar_pdf_caminho, 'F');
    @chmod($salvar_pdf_caminho, 0666);
    return;
}

if (!empty($m['pdf_caminho'])) {
    $f = dirname(__DIR__, 2) . '/' . $m['pdf_caminho'];
    $dir = dirname($f);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    if (is_dir($dir) && is_writable($dir)) {
        @$pdf->Output($f, 'F');
        @chmod($f, 0666);
        if (is_file($f)) {
            $novoHash = @hash_file('sha256', $f);
            if ($novoHash && $novoHash !== ($m['pdf_hash'] ?? '')) {
                $pdo->prepare("UPDATE protocolo_movimentacoes SET pdf_hash=:hash WHERE id=:id")->execute([':hash' => $novoHash, ':id' => $m['id']]);
            }
        }
    }
}

$pdf->Output($m['dossie_numero'] . '-' . $seqFormatada . '.pdf', 'I');
exit;
