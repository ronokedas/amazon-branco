<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
require_once __DIR__ . '/../../includes/assinaturas_usuarios.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$id = trim($_GET['id'] ?? '');
$stmt = $pdo->prepare("SELECT p.*,
    ap.numero processo_numero, ap.tipo_processo, ap.enquadramento, ap.objeto, ap.estaleiro, ap.numero_casco,
    ap.responsavel_projeto_nome, ap.responsavel_projeto_registro, ap.art_numero, ap.observacoes,
    ap.classe_certificacao, ap.arqueacao_bruta analise_ab,
    e.nome embarcacao_nome, e.registro, e.numero_inscricao, e.tipo_embarcacao, e.tipo embarcacao_tipo,
    e.comprimento_total, e.comprimento_casco, e.boca_moldada, e.boca_maxima, e.pontal_moldado, e.arqueacao_bruta embarcacao_ab,
    c.nome solicitante_nome, u.nome analista_nome,
    ra.nome_completo responsavel_nome, ra.cargo_titulo responsavel_cargo, ra.registro_profissional responsavel_registro,
    ra.cpf_cnpj responsavel_cpf_cnpj, ra.assinatura_arquivo, ra.assinatura_hash,
    u_val.nome admin_validador_nome
    FROM analise_planos_pareceres p
    INNER JOIN analises_planos ap ON ap.id=p.analise_id
    INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
    LEFT JOIN clientes c ON c.id=ap.solicitante_id
    LEFT JOIN usuarios u ON u.id=ap.analista_id
    LEFT JOIN responsaveis_assinatura ra ON ra.id=p.responsavel_assinatura_id
    LEFT JOIN usuarios u_val ON u_val.id=p.validado_por
    WHERE p.id=:id");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    die('Relatório não encontrado.');
}

// Emissão direta de PDF arquivado caso exista
if (!isset($salvar_pdf_caminho)) {
    require_once __DIR__ . '/../../includes/auth.php';
    verificar_sessao();
    if (!analisePlanosPodeGerenciar()) {
        http_response_code(403);
        die('Acesso negado.');
    }
    $ap = null;
    if (!empty($p['caminho_pdf_final']) && !empty($p['hash_pdf_final'])) {
        $ap = ['caminho_pdf_final' => $p['caminho_pdf_final'], 'hash_pdf_final' => $p['hash_pdf_final']];
    } else {
        $audit = $pdo->prepare("SELECT caminho_pdf_final,hash_pdf_final FROM documento_aprovacoes WHERE documento_tipo='PARECER_PLANOS' AND documento_id=:id AND status='APROVADO' ORDER BY versao DESC LIMIT 1");
        $audit->execute([':id' => $id]);
        $ap = $audit->fetch(PDO::FETCH_ASSOC);
    }
    if ($ap && $ap['caminho_pdf_final']) {
        $path = __DIR__ . '/../../' . ltrim(str_replace(['../', '..\\'], '', $ap['caminho_pdf_final']), '/\\');
        if (is_file($path) && hash_equals($ap['hash_pdf_final'], hash_file('sha256', $path))) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', $p['numero']) . '-v' . $p['versao'] . '.pdf"');
            readfile($path);
            exit;
        }
    }
}

// Buscar exigências do relatório agrupadas por categoria
$ex = $pdo->prepare("SELECT re.*, COALESCE(re.as_snapshot, e.as_impeditivo, 0) AS as_impeditivo, e.ordem, COALESCE(NULLIF(TRIM(e.categoria), ''), 'GERAL') AS categoria, e.descricao AS desc_original, e.referencia_normativa AS ref_original
    FROM analise_planos_relatorio_exigencias re
    INNER JOIN analise_planos_exigencias e ON e.id=re.exigencia_id
    WHERE re.relatorio_id=:id
    ORDER BY categoria ASC, e.ordem ASC, re.id ASC");
$ex->execute([':id' => $p['id']]);
$exigencias = $ex->fetchAll(PDO::FETCH_ASSOC);

$numeroRap = $p['numero'] ?: ($p['processo_numero'] . ' · histórico v' . $p['versao']);
$numLimpo = trim(preg_replace('/^(?:AM-)?RAP-(?:AP|REL)[:\s-]*/i', '', $numeroRap));
$codigoOficial = 'AM-RAP-AP: ' . ($numLimpo ?: $numeroRap);
$embarcacaoNome = $p['embarcacao_nome'];

if (!class_exists('RelatorioAnalisePlanosPdf')) {
    class RelatorioAnalisePlanosPdf extends TCPDF {
        public string $codigoOficial = '';
        public string $embarcacaoNome = '';

        public function Header() {
            $logo = __DIR__ . '/../../img/logo.png';
            if ($this->getPage() === 1) {
                // Nova Marca Oficial da Empresa: Logo circular com elmo e escudo
                if (is_file($logo)) {
                    $this->Image($logo, 15, 10, 26, 26, 'PNG', '', '', true, 300);
                }
                // Caixa do Título Oficial do Relatório
                $this->SetDrawColor(13, 73, 65);
                $this->SetLineWidth(0.4);
                $this->SetFillColor(255, 255, 255);
                $this->Rect(44, 10, 151, 14, 'DF');
                $this->SetXY(44, 13);
                $this->SetFont('helvetica', 'B', 12.5);
                $this->SetTextColor(13, 73, 65);
                $this->Cell(151, 8, 'RELATÓRIO DE ANÁLISE DE PLANOS', 0, 1, 'C');

                // Caixa do Número do Relatório (Padrão Oficial AM-RAP-AP)
                $this->Rect(44, 25.5, 151, 10.5, 'DF');
                $this->SetXY(44, 27.5);
                $this->SetFont('helvetica', 'B', 10);
                $this->SetTextColor(13, 73, 65);
                $this->Cell(151, 7, $this->codigoOficial, 0, 1, 'C');
            } else {
                // Cabeçalho das páginas seguintes
                if (is_file($logo)) {
                    $this->Image($logo, 15, 8, 14, 14, 'PNG', '', '', true, 300);
                }
                $this->SetFont('helvetica', 'B', 9);
                $this->SetTextColor(13, 73, 65);
                $this->SetXY(32, 9);
                $this->Cell(163, 5, 'AMAZON CERTIFICADORA NAVAL · RELATÓRIO DE ANÁLISE DE PLANOS', 0, 1, 'L');
                $this->SetFont('helvetica', '', 7.5);
                $this->SetTextColor(85, 105, 98);
                $this->SetX(32);
                $this->Cell(163, 4, $this->codigoOficial . ' · Embarcação: ' . $this->embarcacaoNome, 0, 1, 'L');
                $this->SetDrawColor(13, 73, 65);
                $this->SetLineWidth(0.3);
                $this->Line(15, 23.5, 195, 23.5);
            }
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 7.5);
            $this->SetTextColor(120, 130, 125);
            $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages() . ' · Documento emitido em conformidade com as diretrizes da DPC/Marinha do Brasil (NORMAM-202).', 0, 0, 'C');
        }
    }
}

$pdf = new RelatorioAnalisePlanosPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->codigoOficial = $codigoOficial;
$pdf->embarcacaoNome = (string)$embarcacaoNome;
$pdf->SetCreator('Amazon Certificadora Naval');
$pdf->SetAuthor('Amazon Certificadora Naval');
$pdf->SetTitle('Relatório de Análise de Planos - ' . $codigoOficial);
$pdf->SetSubject('Relatório Técnico de Engenharia Naval - NORMAM-202');
$pdf->SetKeywords('NORMAM-202, RAP, Análise de Planos, Marinha do Brasil, Engenharia Naval');
$pdf->SetMargins(15, 42, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetFont('helvetica', '', 8.5);
$pdf->AddPage();

// Variáveis de Dimensões
$compTotal = !empty($p['comprimento_total']) ? number_format((float)$p['comprimento_total'], 2, ',', '.') : (!empty($p['comprimento_casco']) ? number_format((float)$p['comprimento_casco'], 2, ',', '.') : '-');
$bocaMold = !empty($p['boca_moldada']) ? number_format((float)$p['boca_moldada'], 2, ',', '.') : (!empty($p['boca_maxima']) ? number_format((float)$p['boca_maxima'], 2, ',', '.') : '-');
$pontalMold = !empty($p['pontal_moldado']) ? number_format((float)$p['pontal_moldado'], 2, ',', '.') : '-';
$abVal = !empty($p['analise_ab']) ? number_format((float)$p['analise_ab'], 2, ',', '.') : (!empty($p['embarcacao_ab']) ? number_format((float)$p['embarcacao_ab'], 1, ',', '.') : '-');

$tipoEmb = $p['tipo_embarcacao'] ?: ($p['embarcacao_tipo'] ?: 'NAVAL');
$dataInscricao = date('d/m/Y', strtotime($p['publicado_em'] ?: $p['criado_em']));
$registroInscricao = $p['registro'] ?: ($p['numero_inscricao'] ?: 'A DEFINIR');

$tipoDescricao = match($p['tipo_processo']) {
    'LC' => 'CONSTRUÇÃO (LC)',
    'LA' => 'ALTERAÇÃO (LA)',
    'LR' => 'RECLASSIFICAÇÃO (LR)',
    'LCEC' => 'EMBARCAÇÃO JÁ CONSTRUÍDA (LCEC)',
    default => $p['tipo_processo']
};

// Conteúdo HTML do Relatório
$html = '
<style>
  th { font-family: helvetica; }
  td { font-family: helvetica; }
</style>

<table border="1" cellpadding="3.5" cellspacing="0" style="border-collapse:collapse; width:100%; border-color:#0D4941; margin-bottom:8px;">
  <tr style="background-color:#0D4941; color:#ffffff; font-weight:bold; font-size:9pt;">
    <th colspan="4" style="text-align:left;"> 1. IDENTIFICAÇÃO DO PROCESSO E DA EMBARCAÇÃO</th>
  </tr>
  <tr style="font-size:8pt;">
    <td width="22%" style="background-color:#F1F5F3; font-weight:bold;">Embarcação:</td>
    <td width="43%"><b>' . h($p['embarcacao_nome']) . '</b></td>
    <td width="15%" style="background-color:#F1F5F3; font-weight:bold;">Nº Inscrição:</td>
    <td width="20%">' . h($registroInscricao) . '</td>
  </tr>
  <tr style="font-size:8pt;">
    <td style="background-color:#F1F5F3; font-weight:bold;">Tipo de Processo:</td>
    <td>' . h($tipoDescricao) . '</td>
    <td style="background-color:#F1F5F3; font-weight:bold;">Enquadramento:</td>
    <td>' . h($p['enquadramento'] ?: 'NORMAM-202') . ' (' . h($p['classe_certificacao'] ?: 'EC1') . ')</td>
  </tr>
  <tr style="font-size:8pt;">
    <td style="background-color:#F1F5F3; font-weight:bold;">Proprietário / Armador:</td>
    <td>' . h($p['solicitante_nome'] ?: 'Não informado') . '</td>
    <td style="background-color:#F1F5F3; font-weight:bold;">Nº Casco / Estaleiro:</td>
    <td>' . h($p['numero_casco'] ?: '-') . ' / ' . h($p['estaleiro'] ?: '-') . '</td>
  </tr>
  <tr style="font-size:8pt;">
    <td style="background-color:#F1F5F3; font-weight:bold;">Autor do Projeto:</td>
    <td>' . h($p['responsavel_projeto_nome'] ?: 'Engenheiro Naval') . '</td>
    <td style="background-color:#F1F5F3; font-weight:bold;">ART / CREA:</td>
    <td>' . h($p['art_numero'] ?: '-') . '</td>
  </tr>
  <tr style="font-size:8pt;">
    <td style="background-color:#F1F5F3; font-weight:bold;">Dimensões Principais:</td>
    <td colspan="3">
      Comp. Total: <b>' . ($compTotal !== '-' ? $compTotal . ' m' : '-') . '</b> &nbsp;|&nbsp;
      Boca Moldada: <b>' . ($bocaMold !== '-' ? $bocaMold . ' m' : '-') . '</b> &nbsp;|&nbsp;
      Pontal Moldado: <b>' . ($pontalMold !== '-' ? $pontalMold . ' m' : '-') . '</b> &nbsp;|&nbsp;
      AB: <b>' . $abVal . '</b>
    </td>
  </tr>
</table>

<!-- Seção 2: Exigências para Aprovação -->
<table border="1" cellpadding="3.5" cellspacing="0" style="border-collapse:collapse; width:100%; border-color:#0D4941; margin-bottom:4px;">
  <tr style="background-color:#0D4941; color:#ffffff; font-weight:bold; font-size:9pt;">
    <th style="text-align:left;"> 2. RESULTADO DA ANÁLISE DE PLANOS E EXIGÊNCIAS TÉCNICAS</th>
  </tr>
</table>
<div style="font-size:8pt; margin-top:2px; margin-bottom:6px; color:#334155;">
  A seguir, são apresentadas as exigências técnicas resultantes da conferência documental do projeto com base na NORMAM-202/DPC e RIPEAM:
</div>
';

// Agrupar exigências por Categoria
$exigenciasPorCategoria = [];
if (!empty($exigencias)) {
    foreach ($exigencias as $exItem) {
        $cat = trim($exItem['categoria'] ?? 'GERAL') ?: 'GERAL';
        $exigenciasPorCategoria[$cat][] = $exItem;
    }
}

$globalItemNum = 1;
if (!empty($exigenciasPorCategoria)) {
    foreach ($exigenciasPorCategoria as $catNome => $itensCat) {
        $html .= '
        <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%; border-color:#0D4941; margin-bottom:8px;">
          <tr style="background-color:#EAF0EB; color:#0D4941; font-weight:bold; font-size:8.5pt;">
            <th colspan="5" style="text-align:center;">' . h(strtoupper($catNome)) . '</th>
          </tr>
          <tr style="background-color:#F1F5F3; color:#0D4941; font-weight:bold; font-size:7.8pt; text-align:center;">
            <th width="7%">ITEM</th>
            <th width="11%">CONDIÇÃO</th>
            <th width="45%" style="text-align:left;">DESCRIÇÃO</th>
            <th width="22%" style="text-align:left;">REFERÊNCIA</th>
            <th width="15%">VENCIMENTO</th>
          </tr>';
        foreach ($itensCat as $k => $itemEx) {
            $desc = $itemEx['descricao_snapshot'] ?: $itemEx['desc_original'];
            $ref = $itemEx['referencia_snapshot'] ?: ($itemEx['ref_original'] ?: 'NORMAM-202/DPC');
            $numExib = str_pad((string)$globalItemNum, 2, '0', STR_PAD_LEFT);
            $globalItemNum++;
            $isAS = !empty($itemEx['as_impeditivo']);
            $colCondicao = $isAS 
                ? '<span style="color:#b91c1c; font-weight:bold;">A/S (Grave)</span>' 
                : '<span style="color:#475569;">Comum</span>';
            $bgRow = $isAS ? 'background-color:#fff5f5;' : '';
            $html .= '
          <tr nobr="true" style="font-size:7.8pt; ' . $bgRow . '">
            <td width="7%" style="text-align:center; font-weight:bold;">' . $numExib . '</td>
            <td width="11%" style="text-align:center;">' . $colCondicao . '</td>
            <td width="45%" style="text-align:justify; line-height:1.3;">' . nl2br(h($desc)) . '</td>
            <td width="22%" style="line-height:1.3;">' . h($ref) . '</td>
            <td width="15%" style="text-align:center;">Ver OBS. 3</td>
          </tr>';
        }
        $html .= '</table>';
    }
    $html .= '<div style="font-size:6.8pt; color:#475569; margin-top:-4px; margin-bottom:8px; line-height:1.3;">
      <b>* Legenda da Condição A/S:</b> Exigência de caráter grave (Ação/Assunto Suspensivo) que <u>suspende e impede a emissão de licenças e certificados da embarcação</u> até o seu cumprimento e saneamento integral. Exigências comuns não suspendem a emissão da licença operacional da embarcação.
    </div>';
} else {
    // Relatório Conclusivo Sem Exigências
    $html .= '
    <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%; border-color:#0D4941; margin-bottom:8px;">
      <tr style="background-color:#F1F5F3; color:#0D4941; font-weight:bold; font-size:7.8pt; text-align:center;">
        <th width="7%">ITEM</th>
        <th width="11%">CONDIÇÃO</th>
        <th width="45%" style="text-align:left;">DESCRIÇÃO</th>
        <th width="22%" style="text-align:left;">REFERÊNCIA</th>
        <th width="15%">VENCIMENTO</th>
      </tr>
      <tr nobr="true" style="font-size:8pt;">
        <td width="7%" style="text-align:center; font-weight:bold;">01</td>
        <td width="11%" style="text-align:center; color:#059669; font-weight:bold;">Regular</td>
        <td width="45%" style="color:#059669; font-weight:bold;">Sem Exigências Pendentes</td>
        <td width="22%" style="text-align:center;">-</td>
        <td width="15%" style="text-align:center;">-</td>
      </tr>
    </table>';
}

// Seção 3: Observações Padronizadas da DPC/Analista
$observacoes = analisePlanosObservacoesPadrao($p);
$html .= '
<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%; border-color:#0D4941; margin-bottom:10px;">
  <tr style="background-color:#0D4941; color:#ffffff; font-weight:bold; font-size:9pt;">
    <th style="text-align:left;"> 3. OBSERVAÇÕES</th>
  </tr>';
foreach ($observacoes as $num => $obsTexto) {
    $html .= '
  <tr nobr="true" style="font-size:7.8pt;">
    <td style="text-align:justify; line-height:1.35; padding:5px 8px;">
      <b>' . h($num) . '.</b> ' . h($obsTexto) . '
    </td>
  </tr>';
}
$html .= '</table>';

// Seção 4: Data e Local
$dataExtenso = date('d/m/Y', strtotime($p['publicado_em'] ?: ($p['assinado_analista_em'] ?: date('Y-m-d'))));
$html .= '
<div nobr="true" style="margin-top:6px;">
  <div style="text-align:right; font-size:8.5pt; margin-bottom:12px; color:#1e293b;">
    Belém - PA, ' . h($dataExtenso) . '.
  </div>
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');

// Bloco de Assinatura e Autenticação Criptográfica
$isAssinado = !empty($p['assinado_analista_em']) || in_array($p['status'], ['AGUARDANDO_APROVACAO_ADMIN', 'PUBLICADO'], true);

if ($isAssinado) {
    if ($pdf->GetY() + 42 > 275) {
        $pdf->AddPage();
    }
    $sigY = $pdf->GetY() + 2;
    $sigX = 15;
    $sigW = 180;
    $sigH = 34;
    $colImgW = 50;
    $colQrW = 28;

    $pdf->SetDrawColor(13, 73, 65);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFillColor(248, 250, 249);
    $pdf->RoundedRect($sigX, $sigY, $sigW, $sigH, 2, '1111', 'DF');

    $sigFileRel = $p['assinatura_arquivo'] ?? '';
    $sigFileAbs = $sigFileRel ? __DIR__ . '/../../' . ltrim(str_replace(['../', '..\\'], '', $sigFileRel), '/\\') : '';

    if ($sigFileAbs && is_file($sigFileAbs)) {
        if (function_exists('garantirAssinaturaTransparente')) {
            garantirAssinaturaTransparente($sigFileAbs);
        }
        $pdf->Image($sigFileAbs, $sigX + 3, $sigY + 3, 44, 0, 'PNG', '', '', false, 300);
    } else {
        $pdf->SetXY($sigX + 2, $sigY + 8);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell($colImgW - 4, 8, '[Assinatura Cadastrada]', 0, 0, 'C');
    }

    $pdf->SetXY($sigX + 1, $sigY + 22);
    $pdf->SetFont('helvetica', 'I', 5.8);
    $pdf->SetTextColor(90, 100, 95);
    $pdf->Cell($colImgW, 3.5, 'Representação gráfica da assinatura', 0, 1, 'C');

    $pdf->SetXY($sigX + 1, $sigY + 26);
    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->SetTextColor(13, 73, 65);
    $pdf->Cell($colImgW, 4, 'ASSINADO DIGITALMENTE', 0, 1, 'C');

    $pdf->SetDrawColor(205, 215, 210);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($sigX + $colImgW, $sigY + 2, $sigX + $colImgW, $sigY + $sigH - 2);

    $textX = $sigX + $colImgW + 3;
    $textW = $sigW - $colImgW - $colQrW - 6;

    $pdf->SetXY($textX, $sigY + 2.5);
    $pdf->SetFont('helvetica', 'B', 7.2);
    $pdf->SetTextColor(13, 73, 65);
    $pdf->Cell($textW, 4, 'CHANCELA TÉCNICA NAVAL · ASSINATURA QUALIFICADA', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(30, 40, 35);

    $dtAssinatura = !empty($p['assinado_analista_em']) ? date('d/m/Y H:i:s', strtotime($p['assinado_analista_em'])) : date('d/m/Y H:i:s');
    $dtPublicado = !empty($p['publicado_em']) ? date('d/m/Y H:i:s', strtotime($p['publicado_em'])) : null;
    $signatario = $p['responsavel_nome'] ?: ($p['analista_nome'] ?: 'Analista Naval');
    $cargoReg = trim(($p['responsavel_cargo'] ?: 'TECNÓLOGO NAVAL / ANALISTA NAVAL') . ' | ' . ($p['responsavel_registro'] ?: 'CREA/Conselho Regional'));

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.5, 'Signatário: ' . $signatario . (!empty($p['responsavel_cpf_cnpj']) ? ' · CPF: ' . $p['responsavel_cpf_cnpj'] : ''), 0, 1, 'L');

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.5, 'Qualificação: ' . $cargoReg, 0, 1, 'L');

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.5, 'Data/Hora: ' . $dtAssinatura . ' (Horário de Brasília)', 0, 1, 'L');

    if (!empty($p['assinatura_hash'])) {
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetTextColor(95, 105, 100);
        $pdf->Cell($textW, 3.2, 'SHA-256: ' . substr($p['assinatura_hash'], 0, 36) . '...', 0, 1, 'L');
    }

    if ($p['status'] === 'PUBLICADO') {
        $validador = $p['admin_validador_nome'] ?: 'Administrador';
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', 'B', 6.2);
        $pdf->SetTextColor(13, 73, 65);
        $pdf->Cell($textW, 3.5, 'Homologação: Aprovado por ' . $validador . ($dtPublicado ? ' em ' . $dtPublicado : ''), 0, 1, 'L');
    } else {
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', 'I', 6.2);
        $pdf->SetTextColor(170, 95, 0);
        $pdf->Cell($textW, 3.5, 'Situação: Assinado tecnicamente · Aguardando homologação', 0, 1, 'L');
    }

    // Coluna QR Code na direita
    $qrX = $sigX + $sigW - $colQrW;
    $pdf->Line($qrX - 1, $sigY + 2, $qrX - 1, $sigY + $sigH - 2);

    $qrUrl = APP_URL . 'validar/' . urlencode($p['id']);
    $pdf->write2DBarcode($qrUrl, 'QRCODE,L', $qrX + 3, $sigY + 3, 22, 22, [
        'border' => false,
        'padding' => 0,
        'fgcolor' => [13, 73, 65],
        'bgcolor' => false
    ], 'N');

    $pdf->SetXY($qrX, $sigY + 26);
    $pdf->SetFont('helvetica', 'B', 5.2);
    $pdf->SetTextColor(13, 73, 65);
    $pdf->Cell($colQrW, 3.5, 'VERIFICAR AUTENTICIDADE', 0, 1, 'C');

    $pdf->SetY($sigY + $sigH + 2);
} else {
    $pdf->Ln(2);
    $pdf->SetFillColor(254, 243, 199);
    $pdf->SetDrawColor(217, 119, 6);
    $pdf->SetTextColor(146, 64, 14);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell(0, 7, 'DOCUMENTO PRELIMINAR · AGUARDANDO ASSINATURA ELETRÔNICA DO RESPONSÁVEL TÉCNICO', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
}

$nome = 'Relatorio-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $numeroRap) . '.pdf';
if (isset($salvar_pdf_caminho) && $salvar_pdf_caminho) {
    $dir = dirname($salvar_pdf_caminho);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        @chmod($dir, 0775);
    }
    $pdf->Output($salvar_pdf_caminho, 'F');
} elseif (!empty($return_pdf_string)) {
    $pdf_content = $pdf->Output('', 'S');
} else {
    $pdf->Output($nome, 'I');
}

