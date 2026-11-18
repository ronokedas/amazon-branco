<?php
/**
 * Emissão do Ofício Oficial de Encaminhamento e Dossiê Naval Unificado
 * Local: modules/protocolos/pdf_dossie.php
 * Modelo oficial padronizado conforme diretrizes DPC/NORMAM e padrão oficial da Amazon Naval.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$interno = isset($salvar_pdf_dossie_caminho, $dossie_pdf_id);
if (!$interno) protocoloExigirAcesso();
$id = $interno ? (string)$dossie_pdf_id : trim($_GET['id'] ?? '');

$unificado = (isset($dossie_unificado) && $dossie_unificado) || (isset($_GET['unificado']) && (string)$_GET['unificado'] === '1');

$q = $pdo->prepare("SELECT d.*, e.nome embarcacao_nome, e.registro, c.nome cliente_nome,
  u.nome criador_nome, um.nome unidade_nome, um.tipo unidade_tipo, um.cidade unidade_cidade, um.uf unidade_uf,
  um.email unidade_email, um.emails_adicionais unidade_emails_adicionais,
  um.autoridade_nome unidade_autoridade_nome, um.autoridade_cargo unidade_autoridade_cargo,
  uc.nome cancelador_nome
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

// Coleta estrita dos documentos citados
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

// Se não houver itens cadastrados manualmente, vincula as referências diretas do dossiê
if (empty($docsCitados)) {
    if (!empty($d['vistoria_id'])) {
        $qV = $pdo->prepare("SELECT numero FROM vistorias WHERE id = :id");
        $qV->execute([':id' => $d['vistoria_id']]);
        if ($numV = $qV->fetchColumn()) $docsCitados[] = 'AM-REL V: ' . $numV;
    }
    if (!empty($d['analise_id'])) {
        $qA = $pdo->prepare("SELECT numero FROM analises_planos WHERE id = :id");
        $qA->execute([':id' => $d['analise_id']]);
        if ($numA = $qA->fetchColumn()) $docsCitados[] = 'AM-REL AP: ' . $numA;
    }
    if (!empty($d['certificado_tipo']) && !empty($d['certificado_id'])) {
        $docsCitados[] = 'AM-' . strtoupper($d['certificado_tipo']) . ': ' . $d['certificado_id'];
    }
    // Buscar também certificados CSN/CNBL/CNARQ da embarcação que estejam ativos e válidos
    $qC = $pdo->prepare("SELECT 'CSN' tipo, numero FROM certificados_csn WHERE embarcacao_id = :eid AND ativo = 1 ORDER BY criado_em DESC LIMIT 1");
    $qC->execute([':eid' => $d['embarcacao_id']]);
    if ($csn = $qC->fetch(PDO::FETCH_ASSOC)) {
        $docsCitados[] = 'AM-CSN: ' . $csn['numero'];
    }
    $qCnarq = $pdo->prepare("SELECT 'CNARQ' tipo, numero FROM certificados_cnarq WHERE embarcacao_id = :eid AND ativo = 1 ORDER BY criado_em DESC LIMIT 1");
    $qCnarq->execute([':eid' => $d['embarcacao_id']]);
    if ($cnarq = $qCnarq->fetch(PDO::FETCH_ASSOC)) {
        $docsCitados[] = 'AM-CNARQ: ' . $cnarq['numero'];
    }
}
$docsCitados = array_unique(array_filter($docsCitados));
$citacaoDocumentos = !empty($docsCitados) ? implode('; ', $docsCitados) : 'Documentos técnicos de vistoria e certificação constantes dos autos';

// Dados do Cabeçalho e Destinatário do Ofício
$numeroOficio = !empty($d['numero_oficio']) 
    ? $d['numero_oficio'] 
    : 'AM-OF' . str_pad((string)preg_replace('/[^0-9]/', '', $d['numero'] ?: '1'), 3, '0', STR_PAD_LEFT) . '/' . date('Y', strtotime($d['criado_em'] ?: 'now'));

$destinatarioPara = !empty($d['unidade_nome']) 
    ? mb_strtoupper($d['unidade_nome'], 'UTF-8') 
    : 'CAPITANIA DOS PORTOS DA AMAZÔNIA ORIENTAL';

if (!empty($d['destinatario_autoridade'])) {
    $destinatarioAC = mb_strtoupper($d['destinatario_autoridade'], 'UTF-8');
} elseif (!empty($d['unidade_autoridade_nome'])) {
    $cargoAC = !empty($d['unidade_autoridade_cargo']) ? $d['unidade_autoridade_cargo'] . ' – ' : '';
    $destinatarioAC = mb_strtoupper($cargoAC . $d['unidade_autoridade_nome'], 'UTF-8');
} else {
    $destinatarioAC = 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL';
}

$normamRef = !empty($d['normam_referencia']) ? $d['normam_referencia'] : 'NORMAM 202/DPC';

// Data e local por extenso
$cidadeExtenso = !empty($d['unidade_cidade']) ? $d['unidade_cidade'] : 'Belém';
$ufExtenso = !empty($d['unidade_uf']) ? $d['unidade_uf'] : 'PA';
$dataReferencia = !empty($d['assinatura_em']) ? $d['assinatura_em'] : ($d['criado_em'] ?? null);
$dataPorExtenso = $cidadeExtenso . '/' . $ufExtenso . ', ' . formatarDataExtensoNavalSemCidade($dataReferencia);

// Assinante Oficial
$assinanteNome = !empty($d['assinante_nome']) ? mb_strtoupper($d['assinante_nome'], 'UTF-8') : 'THAINARA BARROS';
$assinanteCargo = !empty($d['assinante_cargo']) ? $d['assinante_cargo'] : 'Secretária';
$assinado = !empty($d['assinado']);

if (!function_exists('formatarDataExtensoNavalSemCidade')) {
    function formatarDataExtensoNavalSemCidade(?string $data): string {
        if (!$data) $data = date('Y-m-d');
        $ts = strtotime($data);
        $meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
        $dia = date('d', $ts);
        $mes = $meses[(int)date('m', $ts)] ?? '';
        $ano = date('Y', $ts);
        return "{$dia} de {$mes} de {$ano}";
    }
}

require_once __DIR__ . '/../../includes/certificado_pdf_marca_dagua.php';

if (!class_exists('ProtocoloOficioNavalPdf')) {
    class ProtocoloOficioNavalPdf extends CertificadoPdfComMarcaDagua
    {
        public string $numeroOficio = '';
        public string $codigoIntegridade = '';

        public function Footer(): void
        {
            $this->SetY(-15);
            $this->SetDrawColor(180, 190, 185);
            $this->SetLineWidth(0.3);
            $this->Line(20, $this->GetY(), 190, $this->GetY());
            $this->Ln(1.8);
            $this->SetTextColor(70, 80, 75);
            $this->SetFont('helvetica', '', 7);
            $this->Cell(85, 3.5, 'Ofício ' . $this->numeroOficio . ' · Amazon Certificadora Naval', 0, 0, 'L');
            $this->Cell(65, 3.5, 'Validação Criptográfica: ' . $this->codigoIntegridade, 0, 0, 'C');
            $this->Cell(20, 3.5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
            $this->Ln(3.2);
            $this->SetFont('helvetica', 'I', 6);
            $this->SetTextColor(110, 120, 115);
            $this->Cell(0, 3, 'Documento oficial emitido em conformidade com as diretrizes da DPC / Marinha do Brasil (NORMAM-202).', 0, 0, 'C');
        }
    }
}

// -------------------------------------------------------------
// Função geradora da Página 1 (Ofício Oficial)
// -------------------------------------------------------------
if (!function_exists('gerarPdfOficioPagina1')) {
    function gerarPdfOficioPagina1(
        PDO $pdo,
        array $d,
        string $numeroOficio,
        string $destinatarioPara,
        string $destinatarioAC,
        string $normamRef,
        string $citacaoDocumentos,
        string $dataPorExtenso,
        string $assinanteNome,
        string $assinanteCargo,
        bool $assinado,
        string $codigoIntegridade,
        string $caminhoSaida
    ): void {
        $pdf = new ProtocoloOficioNavalPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->numeroOficio = $numeroOficio;
        $pdf->codigoIntegridade = $codigoIntegridade;
        $pdf->SetCreator('Amazon Certificadora Naval');
        $pdf->SetAuthor('Amazon Certificadora Naval');
        $pdf->SetTitle('Ofício ' . $numeroOficio . ' - ' . $d['embarcacao_nome']);
        $pdf->SetSubject('Encaminhamento de documentos emitidos/aprovados para arquivo nesta OM');
        $pdf->SetMargins(20, 15, 20);
        $pdf->SetAutoPageBreak(false); // Garante rigorosamente PÁGINA ÚNICA
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();

        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 1. Logotipo oficial centralizado (fiel ao modelo oficial do usuário)
        $logoFile = __DIR__ . '/../../img/logo.png';
        if (is_file($logoFile)) {
            $pdf->Image($logoFile, 88, 14, 34, 0, 'PNG', '', '', false, 200, 'C');
            $pdf->SetY(40);
        } else {
            $pdf->SetY(18);
        }

        // 2. Endereço e Contato Centralizados
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(0, 3.8, 'TRAVESSA QUINTINO BOCAIÚVA, Nº 2301 EDIFÍCIO ROGÉLIO FERNANDEZ, SALA 1116. CREMAÇÃO, Belém, PA – (91) 99111-2065', 0, 1, 'C');
        $pdf->Cell(0, 3.8, 'www.amazonnaval.com.br - amazoncertificados@gmail.com', 0, 1, 'C');

        $pdf->Ln(5);

        // 3. Tabela 4x2 Oficial (bordas pretas sólidas finas, exatamente como no modelo do PDF)
        $htmlTabela = '
        <table cellpadding="4" cellspacing="0" style="border-collapse: collapse; font-family: helvetica; width: 100%; border: 0.5px solid #000000;">
            <tr>
                <td width="30%" style="font-size: 8.5pt; font-weight: bold; color: #000000; border: 0.5px solid #000000;">
                    TIPO DE DOCUMENTO
                </td>
                <td width="70%" style="font-size: 8.5pt; color: #000000; border: 0.5px solid #000000;">
                    OFÍCIO ' . (!empty($numeroOficio) ? 'Nº ' . $e($numeroOficio) : '') . '
                </td>
            </tr>
            <tr>
                <td width="30%" style="font-size: 8.5pt; font-weight: bold; color: #000000; border: 0.5px solid #000000;">
                    DESTINATÁRIO (PARA):
                </td>
                <td width="70%" style="font-size: 8.5pt; color: #000000; border: 0.5px solid #000000;">
                    ' . $e($destinatarioPara) . '
                </td>
            </tr>
            <tr>
                <td width="30%" style="font-size: 8.5pt; font-weight: bold; color: #000000; border: 0.5px solid #000000;">
                    À ATENÇÃO (A/C):
                </td>
                <td width="70%" style="font-size: 8.5pt; color: #000000; border: 0.5px solid #000000;">
                    ' . $e($destinatarioAC) . '
                </td>
            </tr>
            <tr>
                <td width="30%" style="font-size: 8.5pt; font-weight: bold; color: #000000; border: 0.5px solid #000000;">
                    ASSUNTO:
                </td>
                <td width="70%" style="font-size: 8.5pt; color: #000000; border: 0.5px solid #000000;">
                    Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM.
                </td>
            </tr>
        </table>
        ';
        $pdf->writeHTML($htmlTabela, true, false, true, false, '');

        $pdf->Ln(6);

        // 4. Corpo Formal com Linha Corrida de Documentos (fiel ao modelo oficial)
        $htmlCorpo = '
        <div style="font-family: helvetica; font-size: 9.5pt; color: #000000; line-height: 1.6;">
            <p style="margin-bottom: 12px;">Prezado Senhor,</p>

            <p style="text-align: justify; margin-bottom: 16px;">
                Atendendo ao disposto no artigo da ' . $e($normamRef) . ', a Entidade Certificadora Amazon Naval vem, através do presente ofício, encaminhar os documentos anexados da seguinte embarcação:
            </p>

            <p style="margin-left: 15px; margin-bottom: 24px; font-weight: bold;">
                • ' . $e(mb_strtoupper($d['embarcacao_nome'], 'UTF-8')) . ' – ' . $e($citacaoDocumentos) . '
            </p>

            <p style="margin-bottom: 18px;">
                ' . $e($dataPorExtenso) . '
            </p>

            <p style="margin-bottom: 12px;">
                Atenciosamente,
            </p>
        </div>
        ';
        $pdf->writeHTML($htmlCorpo, true, false, true, false, '');

        // 5. Bloco de Assinatura
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

        // Se houver imagem da assinatura, estampa discretamente
        if ($temImagemAssinatura && $tmpSigFile) {
            $pdf->Image($tmpSigFile, 84, $yAssinatura, 42, 16, 'PNG', '', '', true, 300, 'C');
            if (str_contains($tmpSigFile, 'sig_of_')) @unlink($tmpSigFile);
            $pdf->SetY($yAssinatura + 17);
        } else {
            $pdf->SetY($yAssinatura + 14);
        }

        // Nome e Cargo Centralizados
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 4.5, $assinanteNome, 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 4.0, $assinanteCargo, 0, 1, 'C');

        $pdf->Output($caminhoSaida, 'F');
    }
}

// Arquivo temporário para a página 1 (Ofício)
$tmpOficioPdf = tempnam(sys_get_temp_dir(), 'oficio_p1_') . '.pdf';
gerarPdfOficioPagina1(
    $pdo,
    $d,
    $numeroOficio,
    $destinatarioPara,
    $destinatarioAC,
    $normamRef,
    $citacaoDocumentos,
    $dataPorExtenso,
    $assinanteNome,
    $assinanteCargo,
    $assinado,
    $codigoIntegridade,
    $tmpOficioPdf
);

// Se não for unificado, envia ou salva o Ofício diretamente
if (!$unificado) {
    if ($interno) {
        copy($tmpOficioPdf, $salvar_pdf_dossie_caminho);
        @unlink($tmpOficioPdf);
        @chmod($salvar_pdf_dossie_caminho, 0666);
        return;
    }
    $nomeArquivoSaida = 'Oficio_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOficio) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nomeArquivoSaida . '"');
    header('Content-Length: ' . filesize($tmpOficioPdf));
    readfile($tmpOficioPdf);
    @unlink($tmpOficioPdf);
    exit;
}

// -------------------------------------------------------------
// MODO UNIFICADO: Concatenar Ofício + Documentos Técnicos Anexos
// -------------------------------------------------------------
$arquivosParaAnexar = [];

// 1. Relatório de Vistoria
if (!empty($d['vistoria_id'])) {
    $tmpVistoria = tempnam(sys_get_temp_dir(), 'vist_anexo_') . '.pdf';
    $id = $d['vistoria_id'];
    $salvar_pdf_caminho = $tmpVistoria;
    try {
        require __DIR__ . '/../vistorias/relatorio_pdf.php';
        if (is_file($tmpVistoria) && filesize($tmpVistoria) > 1000) {
            $arquivosParaAnexar[] = ['caminho' => $tmpVistoria, 'temp' => true];
        }
    } catch (Throwable $e) {
        error_log('Erro ao anexar relatório de vistoria no dossiê: ' . $e->getMessage());
    }
    unset($salvar_pdf_caminho);
}

// 2. Análise de Planos (Parecer)
if (!empty($d['analise_id'])) {
    $qPar = $pdo->prepare("SELECT id, caminho_pdf_final FROM analise_planos_pareceres WHERE analise_id = :aid AND status = 'VALIDADO' ORDER BY criado_em DESC LIMIT 1");
    $qPar->execute([':aid' => $d['analise_id']]);
    if ($par = $qPar->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($par['caminho_pdf_final']) && is_file(__DIR__ . '/../../' . $par['caminho_pdf_final'])) {
            $arquivosParaAnexar[] = ['caminho' => __DIR__ . '/../../' . $par['caminho_pdf_final'], 'temp' => false];
        } else {
            $tmpAnalise = tempnam(sys_get_temp_dir(), 'analise_anexo_') . '.pdf';
            $id = $par['id'];
            $salvar_pdf_caminho = $tmpAnalise;
            try {
                require __DIR__ . '/../analises_planos/parecer_pdf.php';
                if (is_file($tmpAnalise) && filesize($tmpAnalise) > 1000) {
                    $arquivosParaAnexar[] = ['caminho' => $tmpAnalise, 'temp' => true];
                }
            } catch (Throwable $e) {
                error_log('Erro ao anexar parecer de planos no dossiê: ' . $e->getMessage());
            }
            unset($salvar_pdf_caminho);
        }
    }
}

// 3. Certificado CSN
$qCsn = $pdo->prepare("SELECT id, caminho_arquivo_pdf FROM certificados_csn WHERE (id = :cid OR embarcacao_id = :eid) AND ativo = 1 ORDER BY criado_em DESC LIMIT 1");
$qCsn->execute([':cid' => $d['certificado_id'] ?? '', ':eid' => $d['embarcacao_id']]);
if ($csn = $qCsn->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($csn['caminho_arquivo_pdf']) && is_file(__DIR__ . '/../../' . $csn['caminho_arquivo_pdf'])) {
        $arquivosParaAnexar[] = ['caminho' => __DIR__ . '/../../' . $csn['caminho_arquivo_pdf'], 'temp' => false];
    } else {
        $tmpCsn = tempnam(sys_get_temp_dir(), 'csn_anexo_') . '.pdf';
        $id = $csn['id'];
        $salvar_pdf_caminho = $tmpCsn;
        try {
            require __DIR__ . '/../documentacao/certificados/pdf.php';
            if (is_file($tmpCsn) && filesize($tmpCsn) > 1000) {
                $arquivosParaAnexar[] = ['caminho' => $tmpCsn, 'temp' => true];
            }
        } catch (Throwable $e) {
            error_log('Erro ao anexar CSN no dossiê: ' . $e->getMessage());
        }
        unset($salvar_pdf_caminho);
    }
}

// 4. Certificado CNARQ
$qCnarq = $pdo->prepare("SELECT id, caminho_arquivo_pdf FROM certificados_cnarq WHERE (id = :cid OR embarcacao_id = :eid) AND ativo = 1 ORDER BY criado_em DESC LIMIT 1");
$qCnarq->execute([':cid' => $d['certificado_id'] ?? '', ':eid' => $d['embarcacao_id']]);
if ($cnarq = $qCnarq->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($cnarq['caminho_arquivo_pdf']) && is_file(__DIR__ . '/../../' . $cnarq['caminho_arquivo_pdf'])) {
        $arquivosParaAnexar[] = ['caminho' => __DIR__ . '/../../' . $cnarq['caminho_arquivo_pdf'], 'temp' => false];
    } else {
        $tmpCnarq = tempnam(sys_get_temp_dir(), 'cnarq_anexo_') . '.pdf';
        $id = $cnarq['id'];
        $salvar_pdf_caminho = $tmpCnarq;
        try {
            require __DIR__ . '/../documentacao/cnarq/pdf.php';
            if (is_file($tmpCnarq) && filesize($tmpCnarq) > 1000) {
                $arquivosParaAnexar[] = ['caminho' => $tmpCnarq, 'temp' => true];
            }
        } catch (Throwable $e) {
            error_log('Erro ao anexar CNARQ no dossiê: ' . $e->getMessage());
        }
        unset($salvar_pdf_caminho);
    }
}

// 5. Comprovantes / Documentos Anexados ao Dossiê em PDF
foreach ($documentos as $doc) {
    $caminhoRel = $doc['caminho_arquivo'] ?? '';
    if ($caminhoRel && str_ends_with(strtolower($caminhoRel), '.pdf')) {
        $caminhoAbs = __DIR__ . '/../../' . ltrim($caminhoRel, '/\\');
        if (is_file($caminhoAbs)) {
            $arquivosParaAnexar[] = ['caminho' => $caminhoAbs, 'temp' => false];
        }
    }
}

// Usar FPDI para unir Ofício + Anexos
$fpdi = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
$fpdi->SetCreator('Amazon Certificadora Naval');
$fpdi->SetAuthor('Amazon Certificadora Naval');
$fpdi->SetTitle('Dossiê Unificado - ' . $numeroOficio . ' - ' . $d['embarcacao_nome']);
$fpdi->setPrintHeader(false);
$fpdi->setPrintFooter(false);

// Adicionar a Página 1 (Ofício)
$pageCount = $fpdi->setSourceFile($tmpOficioPdf);
for ($p = 1; $p <= $pageCount; $p++) {
    $tplIdx = $fpdi->importPage($p);
    $size = $fpdi->getTemplateSize($tplIdx);
    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $fpdi->useTemplate($tplIdx);
}
@unlink($tmpOficioPdf);

// Adicionar os documentos anexados
foreach ($arquivosParaAnexar as $arqInfo) {
    $arq = $arqInfo['caminho'];
    try {
        $pCount = $fpdi->setSourceFile($arq);
        for ($p = 1; $p <= $pCount; $p++) {
            $tplIdx = $fpdi->importPage($p);
            $size = $fpdi->getTemplateSize($tplIdx);
            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($tplIdx);
        }
    } catch (Throwable $e) {
        error_log('Erro ao concatenar arquivo PDF com FPDI: ' . $e->getMessage());
    }
    if ($arqInfo['temp'] && is_file($arq)) {
        @unlink($arq);
    }
}

// Saída do Dossiê Unificado
if ($interno) {
    $fpdi->Output($salvar_pdf_dossie_caminho, 'F');
    @chmod($salvar_pdf_dossie_caminho, 0666);
    return;
}

$nomeArquivoSaida = 'Dossie_Unificado_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOficio) . '.pdf';
$fpdi->Output($nomeArquivoSaida, 'I');
exit;
