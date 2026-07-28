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

$retornoTipoPdf = null;
if (($v['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS') {
    $stmtRetornoTipo = $pdo->prepare("SELECT tipo FROM vistoria_retornos
        WHERE relatorio_resultado_id=:relatorio OR agendamento_id=:agendamento
        LIMIT 1");
    $stmtRetornoTipo->execute([
        ':relatorio' => $v['id'],
        ':agendamento' => $v['agendamento_id'],
    ]);
    $retornoTipoPdf = (string)($stmtRetornoTipo->fetchColumn() ?: 'AS');
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
$exigencias = calcularNumerosOrigemExigencias($pdo, $stmtE->fetchAll(PDO::FETCH_ASSOC));

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

function dividirTextoObservacaoPdf(string $texto, int $limite = 1800): array {
    $texto = trim($texto);
    if ($texto === '') return [''];
    $partes = [];
    while (mb_strlen($texto, 'UTF-8') > $limite) {
        $trecho = mb_substr($texto, 0, $limite, 'UTF-8');
        $posQuebra = max(
            (int)mb_strrpos($trecho, "\n", 0, 'UTF-8'),
            (int)mb_strrpos($trecho, ' ', 0, 'UTF-8')
        );
        if ($posQuebra < (int)($limite * 0.65)) $posQuebra = $limite;
        $partes[] = trim(mb_substr($texto, 0, $posQuebra, 'UTF-8'));
        $texto = ltrim(mb_substr($texto, $posQuebra, null, 'UTF-8'));
    }
    if ($texto !== '') $partes[] = $texto;
    return $partes;
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

$blocos = blocosComExigenciasRelatorioPdf(
    (string)($v['agendamento_tipo_vistoria'] ?? ''),
    $blocos_todos,
    $exigencias
);

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
        protected $paginaInicioObservacoes = 0;
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
            } elseif ($this->paginaInicioObservacoes > 0 && $this->PageNo() > $this->paginaInicioObservacoes) {
                $this->SetY(13);
                $this->SetFont('helvetica', 'B', 10);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(0, 6, 'OBSERVAÇÕES (continuação)', 0, 1, 'C');
                $this->SetY(28);
            }
        }
        public function iniciarObservacoes(): void {
            $this->paginaInicioObservacoes = $this->PageNo();
        }
        public function encerrarObservacoes(): void {
            $this->paginaInicioObservacoes = 0;
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
    ? ($retornoTipoPdf === 'AS'
        ? 'Relatório de Cumprimento de Exigências A/S'
        : 'Relatório de Verificação de Exigências')
    : 'Relatório de Vistoria';
$pdf->SetTitle($tituloDocumento . ' - ' . $v['numero']);
$pdf->SetMargins(15, 28, 15);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);

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

// Tabelas de resultado e exigências vigentes
$col_w = [15, 100, 35, 30];
$ehRelatorioCumprimentoPdf = (($v['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS');
$exigenciasCumpridasPdf = $ehRelatorioCumprimentoPdf
    ? array_values(array_filter($exigencias, fn($item) => ($item['status_item'] ?? '') === 'cumprida'))
    : [];
$exigenciasTabelaPdf = $ehRelatorioCumprimentoPdf
    ? array_values(array_filter($exigencias, fn($item) => ($item['status_item'] ?? '') !== 'cumprida'))
    : $exigencias;
$exigenciasTabelaPdf = numerarExigenciasPorSecao($exigenciasTabelaPdf);
$possuiSemVencimentoPdf = count(array_filter(
    $exigenciasTabelaPdf,
    static fn($item) => empty($item['antes_de_suspender']) && empty($item['vencimento'])
)) > 0;
$possuiAsVigentePdf = count(array_filter(
    $exigenciasTabelaPdf,
    static fn($item) => !empty($item['antes_de_suspender'])
)) > 0;
$numeroObservacaoAsPdf = $possuiAsVigentePdf ? ($possuiSemVencimentoPdf ? 3 : 2) : null;

if ($ehRelatorioCumprimentoPdf) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'RESULTADO DA VERIFICAÇÃO', 0, 1, 'C');
}

if ($exigenciasCumpridasPdf) {
    usort($exigenciasCumpridasPdf, static function (array $a, array $b): int {
        $ordemBlocosResultado = ['seco' => 0, 'flutuando' => 1, 'borda_livre' => 2, 'arqueacao' => 3];
        $blocoA = blocoExigenciaNormalizado($a['bloco_vistoria'] ?? null);
        $blocoB = blocoExigenciaNormalizado($b['bloco_vistoria'] ?? null);
        $cmp = ($ordemBlocosResultado[$blocoA] ?? 99) <=> ($ordemBlocosResultado[$blocoB] ?? 99);
        if ($cmp !== 0) return $cmp;
        return ((int)($a['numero_origem'] ?? $a['ordem'] ?? 0))
            <=> ((int)($b['numero_origem'] ?? $b['ordem'] ?? 0));
    });
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell($col_w[0], 6, 'ITEM ANT.', 1, 0, 'C');
    $pdf->Cell($col_w[1], 6, 'Descrição verificada', 1, 0, 'L');
    $pdf->Cell($col_w[2], 6, 'Item da NORMAM', 1, 0, 'C');
    $pdf->Cell($col_w[3], 6, 'Resultado', 1, 1, 'C');
    $blocoResultadoAnterior = null;
    foreach ($exigenciasCumpridasPdf as $itemCumprido) {
        $blocoResultado = blocoExigenciaNormalizado($itemCumprido['bloco_vistoria'] ?? null);
        if ($blocoResultado !== $blocoResultadoAnterior) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(array_sum($col_w), 6, rotuloSecaoExigencia($blocoResultado), 1, 1, 'L', true);
            $blocoResultadoAnterior = $blocoResultado;
        }
        $descricaoCumprida = trim((string)($itemCumprido['descricao'] ?? '')) ?: ($itemCumprido['item'] ?? '');
        $normamCumprida = trim((string)($itemCumprido['item_normam'] ?? '')) ?: ($itemCumprido['item'] ?? '');
        $numeroAnterior = (int)($itemCumprido['numero_origem'] ?: $itemCumprido['ordem']);
        $resultadoCumprida = 'Exigência cumprida';
        $pdf->SetFont('helvetica', '', 8);
        $h = max(
            $pdf->getNumLines($descricaoCumprida, $col_w[1]),
            $pdf->getNumLines($normamCumprida, $col_w[2]),
            $pdf->getNumLines($resultadoCumprida, $col_w[3])
        ) * 4;
        if ($h < 6) $h = 6;
        if ($pdf->GetY() + $h > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) $pdf->AddPage();
        $pdf->MultiCell($col_w[0], $h, (string)$numeroAnterior, 1, 'C', false, 0);
        $pdf->MultiCell($col_w[1], $h, $descricaoCumprida, 1, 'L', false, 0);
        $pdf->MultiCell($col_w[2], $h, $normamCumprida, 1, 'C', false, 0);
        $pdf->MultiCell($col_w[3], $h, $resultadoCumprida, 1, 'C', false, 1);
    }
    $pdf->Ln(6);
} elseif ($ehRelatorioCumprimentoPdf) {
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(array_sum($col_w), 6, 'Nenhuma exigência foi classificada como cumprida.', 1, 1, 'C');
    $pdf->Ln(6);
}

if ($ehRelatorioCumprimentoPdf) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'EXIGÊNCIAS VIGENTES', 0, 1, 'C');
}

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

foreach ($blocos as $bloco_id => $bloco_nome) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $data_v = formatarDataBR($datas_blocos[$bloco_id]);
    $pdf->Cell(array_sum($col_w), 6, $bloco_nome . ' - ' . $data_v, 1, 1, 'L', true);
    
    $itens_bloco = array_filter($exigenciasTabelaPdf, function($e) use ($bloco_id) {
        $b = $e['bloco_vistoria'] ?? 'flutuando';
        return $b === $bloco_id;
    });

    if (empty($itens_bloco)) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(array_sum($col_w), 6, 'Sem Exigências', 1, 1, 'C');
    } else {
        foreach ($itens_bloco as $item) {
            $vencimento = !empty($item['antes_de_suspender'])
                ? "A/S\nVer Obs. " . $numeroObservacaoAsPdf
                : (empty($item['vencimento']) ? '-' : formatarDataBR($item['vencimento']));
            $descricao = ($item['status_item'] ?? '') === 'cumprida_parcial_reescrita'
                ? trim((string)($item['descricao_reescrita'] ?? ''))
                : '';
            $descricao = $descricao !== ''
                ? $descricao
                : (trim((string)($item['descricao'] ?? '')) ?: ($item['item'] ?? ''));
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
            $numeroExigenciaPdf = (int)($item['numero_sequencial_calculado'] ?? 0);
            $pdf->MultiCell($col_w[0], $h, $numeroExigenciaPdf, 1, 'C', false, 0);
            $pdf->MultiCell($col_w[1], $h, $descricao, 1, 'L', false, 0);
            $pdf->MultiCell($col_w[2], $h, $normam, 1, 'C', false, 0);
            $pdf->MultiCell($col_w[3], $h, $vencimento, 1, 'C', false, 1);
        }
    }
}

$pdf->Ln(6);

// Observações em tabela numerada, calculadas da cadeia auditável.
$observacoesPdf = [];
$tipoObjetivo = $ehRelatorioCumprimentoPdf
    ? ($retornoTipoPdf === 'AS' ? 'verificação de cumprimento de exigências A/S' : 'verificação de cumprimento de exigências')
    : 'vistoria técnica';
$objetivo = 'Este relatório tem o objetivo de registrar a ' . $tipoObjetivo
    . ' realizada em ' . formatarDataBR($v['data_vistoria']) . '.';
if (!empty($v['relatorio_anterior_numero'])) {
    $objetivo .= ' É vinculado ao relatório anterior ' . $v['relatorio_anterior_numero'] . '.';
}
$observacoesPdf[] = $objetivo;

if ($possuiSemVencimentoPdf) {
    $observacoesPdf[] = 'As exigências sem data de vencimento não possuem prazo designado neste relatório e devem ser regularizadas conforme as condições da certificação aplicável.';
}
if ($possuiAsVigentePdf) {
    $observacoesPdf[] = 'As exigências identificadas como A/S (Antes de suspender) devem ser cumpridas antes da continuidade da certificação e bloqueiam a emissão ou convalidação dos respectivos Certificados Estatutários enquanto permanecerem pendentes.';
}
$observacaoTecnica = trim((string)($v['observacoes_tecnicas'] ?? ''));
if ($observacaoTecnica !== ''
    && stripos($observacaoTecnica, 'Cumprimento das exigencias pendentes do relatorio') !== 0) {
    $observacoesPdf[] = $observacaoTecnica;
}
$historicoPdf = construirHistoricoComparativoRelatorio($pdo, (string)$v['id']);
if ($historicoPdf !== '') {
    $observacoesPdf[] = "Em relação à cadeia de relatórios de vistorias, evidencia-se:\n" . $historicoPdf;
}

if ($pdf->GetY() + 42 > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
    $pdf->AddPage();
}
$pdf->iniciarObservacoes();
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'OBSERVAÇÕES', 1, 1, 'C');
$linhasHtml = '';
foreach ($observacoesPdf as $indiceObservacao => $textoObservacao) {
    foreach (dividirTextoObservacaoPdf($textoObservacao) as $parteObservacao) {
        $textoHtml = nl2br(htmlspecialchars($parteObservacao, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $linhasHtml .= '<tr nobr="true">'
            . '<td width="8%" align="center">' . ($indiceObservacao + 1) . '</td>'
            . '<td width="92%" align="left">' . $textoHtml . '</td>'
            . '</tr>';
    }
}
$tabelaObservacoes = '<table border="1" cellpadding="4" cellspacing="0">'
    . '<tbody>' . $linhasHtml . '</tbody></table>';
$pdf->SetFont('helvetica', '', 8.5);
$pdf->writeHTML($tabelaObservacoes, true, false, true, false, '');
$pdf->encerrarObservacoes();
$pdf->Ln(4);

// Rodapé de emissão e termo de responsabilidade
$reservarBlocoAssinatura = isset($GLOBALS['APROVACAO_RESPONSAVEL_PDF']);
$alturaBlocoAssinatura = 49.0;
$alturaRodapeAssinado = 88.0;
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
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->MultiCell(0, 4, 'Validação disponível após a assinatura eletrônica.', 0, 'L');
    $pdf->Ln(3);
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
