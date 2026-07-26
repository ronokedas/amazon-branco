<?php
/**
 * MÓDULO: Vistorias
 * Geração de PDF — Relatório de Vistoria
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$requisicaoExterna = !isset($salvar_pdf_caminho);
if ($requisicaoExterna) {
    verificar_sessao();
    if (!podeAcessar('vistorias')) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

$id = trim((string)($_GET['id'] ?? ''));

if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $id)) {
    http_response_code(400);
    exit('Identificador de relatorio invalido.');
}

// Buscar vistoria
$stmt = $pdo->prepare("
    SELECT v.*, 
           e.nome AS embarcacao_nome, e.porto_inscricao,
           c.nome AS cliente_nome,
           arm.nome AS armador_nome,
           a.local AS local_vistoria, a.data_vistoria AS a_data_vistoria, a.tipo_vistoria AS agendamento_tipo_vistoria,
           a.vistoriador_id AS agendamento_vistoriador_id,
           va.numero AS relatorio_anterior_numero,
           u.nome AS assinante_nome, '' AS assinante_registro, 'Engenheiro Naval' AS assinante_titulo
    FROM vistorias v
    JOIN embarcacoes e ON v.embarcacao_id = e.id
    LEFT JOIN clientes c ON v.pessoa_id = c.id
    LEFT JOIN clientes arm ON v.armador_id = arm.id
    LEFT JOIN agendamentos a ON v.agendamento_id = a.id
    LEFT JOIN vistorias va ON v.relatorio_anterior_id = va.id
    LEFT JOIN usuarios u ON v.criado_por = u.id
    WHERE v.id = :id
");
$stmt->execute([':id' => $id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$v) {
    http_response_code(404);
    exit("Relatório não encontrado.");
}

$cadeiaPdf = obterCadeiaRelatorios($pdo, (string)$v['id']);
$relatorioRaizNumero = '';
$etapaRetorno = 0;
foreach ($cadeiaPdf as $indicePdf => $itemPdf) {
    if ($indicePdf === 0) $relatorioRaizNumero = (string)($itemPdf['numero'] ?? '');
    if ((string)$itemPdf['id'] === (string)$v['id']) $etapaRetorno = $indicePdf;
}

if ($requisicaoExterna && getCargo() === 'VISTORIADOR'
    && (string)($v['agendamento_vistoriador_id'] ?? '') !== (string)($_SESSION['usuario_id'] ?? '')) {
    http_response_code(403);
    exit('Acesso negado. Este relatorio nao esta atribuido a voce.');
}

if ($requisicaoExterna) {
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
}

// Depois da aprovacao, o relatorio e imutavel e sempre deve servir o artefato auditado.
if (!isset($salvar_pdf_caminho)) {
    $stmtAudit = $pdo->prepare("SELECT caminho_pdf_final, hash_pdf_final FROM documento_aprovacoes WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='APROVADO' ORDER BY versao DESC LIMIT 1");
    $stmtAudit->execute([':id'=>$id]);
    $auditPdf = $stmtAudit->fetch(PDO::FETCH_ASSOC);
    if ($auditPdf && !empty($auditPdf['caminho_pdf_final'])) {
        $arquivoAuditado = __DIR__ . '/../../' . ltrim(str_replace(['../','..\\'], '', $auditPdf['caminho_pdf_final']), '/\\');
        if (is_file($arquivoAuditado) && hash_equals((string)$auditPdf['hash_pdf_final'], hash_file('sha256',$arquivoAuditado))) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Relatorio-aprovado.pdf"');
            header('Content-Length: '.filesize($arquivoAuditado));
            readfile($arquivoAuditado);
            exit;
        }
    }
    $stmtAssinatura = $pdo->prepare("SELECT caminho_pdf_assinado,hash_pdf_assinado FROM documento_assinaturas WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='ASSINADO' ORDER BY versao DESC LIMIT 1");
    $stmtAssinatura->execute([':id'=>$id]);
    $assinaturaPdf=$stmtAssinatura->fetch(PDO::FETCH_ASSOC);
    if($assinaturaPdf&&!empty($assinaturaPdf['caminho_pdf_assinado'])){
        $arquivoAssinado=__DIR__.'/../../'.ltrim(str_replace(['../','..\\'],'',$assinaturaPdf['caminho_pdf_assinado']),'/\\');
        if(is_file($arquivoAssinado)&&hash_equals((string)$assinaturaPdf['hash_pdf_assinado'],hash_file('sha256',$arquivoAssinado))){header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="Relatorio-assinado.pdf"');header('Content-Length: '.filesize($arquivoAssinado));readfile($arquivoAssinado);exit;}
    }
}

// Buscar exigências
$stmtE = $pdo->prepare("
    SELECT ex.*, COALESCE(ex.item_normam, c.item_normam) AS item_normam
    FROM vistoria_exigencias ex
    LEFT JOIN exigencias_catalogo c ON ex.catalogo_id = c.id
    WHERE ex.vistoria_id = :id
    ORDER BY ex.ordem ASC
");
$stmtE->execute([':id' => $id]);
$exigencias = $stmtE->fetchAll(PDO::FETCH_ASSOC);

// Carregar autoloader do Composer (inclui TCPDF automaticamente)
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Autoloader do Composer não encontrado.");
}
require_once $autoload_path;

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function dataPorExtenso($data) {
    if (empty($data)) return '___/___/______';
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function formatarDataBR($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

function normalizarTipoVistoriaPdf(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    return $ascii !== false ? $ascii : $texto;
}

function blocosDisponiveisRelatorioPdf(string $tipoVistoria, array $todos): array
{
    $texto = normalizarTipoVistoriaPdf($tipoVistoria);
    $blocos = [];

    if (strpos($texto, 'seco') !== false) {
        $blocos['seco'] = $todos['seco'];
    }
    if (strpos($texto, 'flutu') !== false || strpos($texto, 'agua') !== false || strpos($texto, 'licenca provisoria') !== false) {
        $blocos['flutuando'] = $todos['flutuando'];
    }
    if (strpos($texto, 'borda') !== false || strpos($texto, 'cnbl') !== false) {
        $blocos['borda_livre'] = $todos['borda_livre'];
    }
    if (strpos($texto, 'arquea') !== false || strpos($texto, 'cnarq') !== false) {
        $blocos['arqueacao'] = $todos['arqueacao'];
    }

    return !empty($blocos) ? $blocos : $todos;
}

// Buscar assinante responsável técnico ativo do banco de dados
$assinante_nome = $v['assinante_nome'] ?? 'RESPONSÁVEL TÉCNICO';
$assinante_titulo = 'Engenheiro Naval';
$assinante_registro = '';

if (!empty($GLOBALS['APROVACAO_RESPONSAVEL_PDF']) && is_array($GLOBALS['APROVACAO_RESPONSAVEL_PDF'])) {
    $respRow = $GLOBALS['APROVACAO_RESPONSAVEL_PDF'];
    $assinante_nome = $respRow['nome_completo'] ?? $assinante_nome;
    $assinante_titulo = $respRow['cargo_titulo'] ?? $assinante_titulo;
    $assinante_registro = $respRow['registro_profissional'] ?? '';
} else try {
    $stmtResp = $pdo->query("SELECT nome_completo, cargo_titulo, registro_profissional FROM responsaveis_assinatura WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
    $respRow = $stmtResp->fetch(PDO::FETCH_ASSOC);
    if ($respRow) {
        $assinante_nome = $respRow['nome_completo'];
        $assinante_titulo = $respRow['cargo_titulo'];
        $assinante_registro = $respRow['registro_profissional'];
    }
} catch (Exception $e) {
    // Mantém fallback do criador
}

// Determinar as datas de cada bloco de vistoria de forma inteligente
$blocos_todos = [
    'seco' => 'Vistoria em Seco',
    'flutuando' => 'Vistoria Flutuando',
    'borda_livre' => 'Vistoria de Borda Livre',
    'arqueacao' => 'Vistoria de Arqueação'
];

$blocos = blocosDisponiveisRelatorioPdf((string)($v['agendamento_tipo_vistoria'] ?? ''), $blocos_todos);
$blocos_permitidos = array_keys($blocos);
$exigencias = array_values(array_filter($exigencias, function ($ex) use ($blocos_permitidos) {
    $bloco = $ex['bloco_vistoria'] ?? 'flutuando';
    return in_array($bloco, $blocos_permitidos, true);
}));

$datas_blocos = [];
foreach (array_keys($blocos) as $b_id) {
    $tem_na_atual = false;
    foreach ($exigencias as $ex) {
        $b = $ex['bloco_vistoria'] ?? 'flutuando';
        if ($b === $b_id) {
            $tem_na_atual = true;
            break;
        }
    }
    
    if ($tem_na_atual) {
        $datas_blocos[$b_id] = $v['data_vistoria'];
    } else {
        try {
            $stmtDataB = $pdo->prepare("
                SELECT v.data_vistoria 
                FROM vistorias v
                INNER JOIN vistoria_exigencias ex ON ex.vistoria_id = v.id
                WHERE v.embarcacao_id = :embarcacao_id 
                  AND v.id != :id_atual
                  AND ex.bloco_vistoria = :bloco
                ORDER BY v.data_vistoria DESC LIMIT 1
            ");
            $stmtDataB->execute([
                ':embarcacao_id' => $v['embarcacao_id'],
                ':id_atual' => $id,
                ':bloco' => $b_id
            ]);
            $resDataB = $stmtDataB->fetchColumn();
            if ($resDataB) {
                $datas_blocos[$b_id] = $resDataB;
            }
        } catch (Exception $e) {
            // Ignora erro de banco
        }
    }
    
    if (empty($datas_blocos[$b_id])) {
        $datas_blocos[$b_id] = $v['data_vistoria'];
    }
}

// ============================================
// CRIAR PDF
// ============================================

if (!class_exists('RelatorioVistoriaPDF')) {
    class RelatorioVistoriaPDF extends TCPDF {
        protected $numero;
        public function __construct($numero) {
            parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
            $this->numero = $numero;
        }
        public function Header() {
            if ($this->PageNo() == 1) {
                // Logo no cabeçalho (igual ao original: x=13, y=10, w=34)
                $logo_path = __DIR__ . '/../../assets/img/logo.png';
                if (file_exists($logo_path) && filesize($logo_path) > 100) {
                    $this->Image($logo_path, 13, 10, 34, 0, 'PNG', '', '', true, 150);
                }

                // Linha superior (depois do logo até o fim da margem direita)
                $this->Line(48, 14, 195, 14);

                $this->SetY(17);
                $this->SetFont('helvetica', 'B', 14);
                $this->SetTextColor(0, 0, 0);
                
                // RELATÓRIO DE VISTORIAS (x=50)
                $this->SetX(48);
                $this->Cell(90, 8, 'RELATÓRIO DE VISTORIAS', 0, 0, 'L');
                
                // Número do relatório (x=157)
                $this->SetFont('helvetica', 'B', 11);
                $this->SetX(157);
                $this->Cell(38, 8, $this->numero, 0, 1, 'R');
                
                // Linha inferior
                $this->Line(48, 26, 195, 26);
                
                // Garante que o Y fique posicionado abaixo do Header para não vazar texto
                $this->SetY(35);
            }
        }
        public function Footer() {
            // Vazio para não imprimir rodapé
        }
        
        // Remove a marca d'água invisível nativa do TCPDF
        protected function _puttcpdfbadge() {
            return;
        }
    }
}

$pdf = new RelatorioVistoriaPDF(h($v['numero']));
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$tituloDocumento = (($v['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS')
    ? 'Relatório de Cumprimento de Exigências A/S'
    : 'Relatório de Vistoria';
$pdf->SetTitle($tituloDocumento . ' - ' . $v['numero']);
$pdf->SetMargins(15, 28, 15);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);

if (($v['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS') {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(153, 83, 0);
    $pdf->Cell(0, 7, mb_strtoupper('Relatório de cumprimento de exigências A/S'), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Relatório anterior: ' . ($v['relatorio_anterior_numero'] ?: $v['relatorio_anterior_id']), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Relatório raiz: ' . ($relatorioRaizNumero ?: '-') . ' | Etapa do retorno: ' . $etapaRetorno, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
}

// Informações básicas (Embarcação, Armador, etc) formatadas em duas colunas (rótulo e valor)
$pdf->Ln(5);

$pdf->SetTextColor(0, 0, 0);

$label_w = 65; // Largura para o rótulo
$value_w = 115; // Largura para o valor

// Embarcação
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Embarcação:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . mb_strtoupper(h($v['embarcacao_nome'])), 0, 1, 'L');
$pdf->Ln(1);

// Armador
$operador_relatorio = trim((string)($v['operador_nome'] ?? '')) ?: ($v['armador_nome'] ?? $v['cliente_nome']);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Operador:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . mb_strtoupper(h($operador_relatorio)), 0, 1, 'L');
$pdf->Ln(1);

// Porto de Inscrição
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Porto de Inscrição:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . h($v['porto_inscricao'] ?? 'BELÉM - PA'), 0, 1, 'L');
$pdf->Ln(1);

// Local da Vistoria
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Local da Vistoria:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . h($v['local_vistoria'] ?? 'BELÉM - PA'), 0, 1, 'L');

$pdf->Ln(6);

// Tabela de Exigências
$col_w = [15, 100, 35, 30];

// Função para desenhar cabeçalho da tabela
function printTableHeader($pdf, $col_w) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    // Borda superior e inferior
    $pdf->Cell($col_w[0], 6, 'ITEM', 1, 0, 'C', true);
    $pdf->Cell($col_w[1], 6, 'Descrição das Exigências', 1, 0, 'L', true);
    $pdf->Cell($col_w[2], 6, 'Item da NORMAM', 1, 0, 'C', true);
    $pdf->Cell($col_w[3], 6, 'Vencimento', 1, 1, 'C', true);
}

printTableHeader($pdf, $col_w);

$numero_item_pdf = 1;
foreach ($blocos as $bloco_id => $bloco_nome) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $data_v = formatarDataBR($datas_blocos[$bloco_id]);
    $pdf->Cell(array_sum($col_w), 6, $bloco_nome . ' - ' . $data_v, 1, 1, 'L', true);
    
    $itens_bloco = array_filter($exigencias, function($e) use ($bloco_id) {
        $b = $e['bloco_vistoria'] ?? 'flutuando';
        return $b === $bloco_id;
    });

    if (empty($itens_bloco)) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(array_sum($col_w), 6, 'Sem Exigências', 1, 1, 'C');
    } else {
        foreach ($itens_bloco as $item) {
            $vencimento = !empty($item['antes_de_suspender'])
                ? "A/S\nVer Obs. 2"
                : (empty($item['vencimento']) ? '-' : formatarDataBR($item['vencimento']));
            $descricao = trim((string)($item['descricao'] ?? '')) ?: ($item['item'] ?? '');
            $normam = trim((string)($item['item_normam'] ?? '')) ?: ($item['item'] ?? '');
            
            $pdf->SetFont('helvetica', '', 8);
            
            // Calcular altura necessária
            $nb_desc = $pdf->getNumLines($descricao, $col_w[1]);
            $nb_normam = $pdf->getNumLines($normam, $col_w[2]);
            $nb_venc = $pdf->getNumLines($vencimento, $col_w[3]);
            $h = max($nb_desc, $nb_normam, $nb_venc) * 4;
            if ($h < 6) $h = 6;
            
            // Verifica quebra de página
            if ($pdf->GetY() + $h > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
                $pdf->AddPage();
                printTableHeader($pdf, $col_w);
            }
            
            $startY = $pdf->GetY();
            
            // Desenhar linhas com borda completa
            $pdf->MultiCell($col_w[0], $h, $numero_item_pdf++, 1, 'C', false, 0);
            $pdf->MultiCell($col_w[1], $h, $descricao, 1, 'L', false, 0);
            $pdf->MultiCell($col_w[2], $h, $normam, 1, 'C', false, 0);
            $pdf->MultiCell($col_w[3], $h, $vencimento, 1, 'C', false, 1);
        }
    }
}

$pdf->Ln(6);

// Observações
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'OBSERVAÇÕES', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);

// Lógica de Observações Comparativas (Baseado no histórico real)
$obs_counter = 1;

if (!empty($v['relatorio_anterior_id'])) {
    // Obter agrupamentos da vistoria ATUAL
    $transcritas = [];
    $reescritas = [];
    $inseridas = [];
    $cumpridas = [];
    
    foreach ($exigencias as $ex) {
        if ($ex['status_item'] === 'cumprida') {
            $cumpridas[] = $ex['ordem'];
        } elseif ($ex['status_item'] === 'nao_cumprida_transcrita' || $ex['status_item'] === 'pendente') {
            $transcritas[] = $ex['ordem'];
        } elseif ($ex['status_item'] === 'cumprida_parcial_reescrita') {
            $reescritas[] = $ex['ordem'];
        } elseif ($ex['status_item'] === 'inserida') {
            $inseridas[] = $ex['ordem'];
        }
    }
    
    // Simplificando a exibição para bater com o layout. Se tiver dados comparativos, os exibimos
    $pdf->Ln(2); // Espaço antes para evitar colar
    $pdf->Cell(0, 5, "{$obs_counter}. Em relação ao relatório de vistorias anterior, evidencia-se:", 0, 1, 'L');
    $obs_counter++;
    $pdf->Ln(1); // Espaço explícito para não vazar texto
    
    // Separamos por bloco para ser mais fiel (opcional), mas vamos listar num fluxo simples e limpo
    if (count($cumpridas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $cumpridas) . " foram CUMPRIDAS.", 0, 'L');
    }
    if (count($transcritas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $transcritas) . " não foram cumpridas e, portanto, foram TRANSCRITAS neste relatório, e receberam novo sequencial.", 0, 'L');
    }
    if (count($reescritas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $reescritas) . " foram cumpridas parcialmente e, portanto, foram REESCRITAS neste relatório, e receberam novo sequencial.", 0, 'L');
    }
    if (count($inseridas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $inseridas) . " foram INSERIDAS neste relatório.", 0, 'L');
    }
    
    $pdf->Ln(2);
}

// Observações Técnicas (livre)
if (!empty($v['observacoes_tecnicas'])) {
    $pdf->MultiCell(0, 4.5, "{$obs_counter}. " . $v['observacoes_tecnicas'], 0, 'L');
    $obs_counter++;
    $pdf->Ln(2);
}

if (!empty($v['texto_observacoes_geradas'])) {
    $pdf->MultiCell(0, 4.5, "{$obs_counter}. " . $v['texto_observacoes_geradas'], 0, 'L');
    $obs_counter++;
    $pdf->Ln(2);
}

// Observação fixa (Obs. 2)
$obs_2 = "As exigências identificadas como A/S (Antes de suspender) devem ser cumpridas antes da continuidade da certificação e bloqueiam a emissão ou convalidação dos respectivos Certificados Estatutários enquanto permanecerem pendentes.";
$pdf->MultiCell(0, 4.5, "Obs. 2: " . $obs_2, 0, 'L');
$pdf->Ln(8);

// Rodapé de emissão e termo de responsabilidade
$reservarBlocoAssinatura = isset($GLOBALS['APROVACAO_RESPONSAVEL_PDF']);
$alturaBlocoAssinatura = 39.0;
$alturaRodapeAssinado = 76.0;
if ($reservarBlocoAssinatura
    && $pdf->GetY() + $alturaRodapeAssinado > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
    $pdf->AddPage();
}
$pdf->SetFont('helvetica', 'B', 8);
$emissao = $v['data_emissao'] ?? date('Y-m-d');
$pdf->Cell(0, 6, 'RELATÓRIO EMITIDO EM: ' . mb_strtoupper(dataPorExtenso($emissao)), 0, 1, 'L');

if ($reservarBlocoAssinatura) {
    $aprovacao_pdf_layout = [
        'bloco_pagina' => $pdf->PageNo(),
        'bloco_y' => $pdf->GetY() + 2.0,
    ];
    $pdf->SetY($aprovacao_pdf_layout['bloco_y'] + $alturaBlocoAssinatura + 3.0);
} else {
    $pdf->Ln(4);
}

$texto_responsabilidade = "A aprovação das vistorias realizadas para a emissão ou validação de um Certificado serão válidas apenas para o momento em que forem efetuadas. A partir de então, e durante todo o período de validade do Certificado, os proprietários, armadores, comandantes ou mestres segundo as circunstâncias do caso, serão os responsáveis pela manutenção das condições de segurança, de maneira a garantirem que a embarcação e seus equipamentos não constituam um perigo para sua própria segurança, para a de terceiros ou do meio ambiente.";
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 4, $texto_responsabilidade, 0, 'J');

$nome_arquivo_amigavel = 'Relatorio-' . str_replace('/', '-', h($v['numero'])) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} elseif (isset($return_pdf_string) && $return_pdf_string) {
    $pdf_content = $pdf->Output('', 'S');
} else {
    $pdf->Output($nome_arquivo_amigavel, 'I');
}
