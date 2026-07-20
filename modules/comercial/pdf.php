<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: pdf.php - Gerar PDF da proposta no modelo Amazon Naval
 * Usa TCPDF (biblioteca FPDF extendida via Composer)
 *
 * ACESSO: ?id=UUID (requer login ADMIN)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$proposta_id = $GLOBALS['PROPOSTA_PDF_ID'] ?? ($_GET['id'] ?? '');
if (empty($proposta_id)) {
    die("ID da proposta não informado.");
}

// ============================================
// BUSCAR DADOS DA PROPOSTA
// ============================================
$stmt = $pdo->prepare("
    SELECT p.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
           c.telefone AS cliente_telefone, c.email AS cliente_email,
           c.endereco AS cliente_endereco, c.perfil AS cliente_perfil
    FROM propostas p
    INNER JOIN clientes c ON c.id = p.cliente_id
    WHERE p.id = :id
");
$stmt->execute([':id' => $proposta_id]);
$proposta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposta) {
    die("Proposta não encontrada.");
}

// Buscar embarcações vinculadas
$stmtEmb = $pdo->prepare("
    SELECT e.id, e.nome, e.registro, e.comprimento_total, e.boca_moldada,
           e.pontal_moldado, e.tipo_embarcacao, e.material_casco
    FROM propostas_embarcacoes pe
    INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
    WHERE pe.proposta_id = :pid
    ORDER BY e.nome ASC
");
$stmtEmb->execute([':pid' => $proposta_id]);
$embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

// Buscar serviços com seus respectivos preços e quantidades, agrupados por embarcação
$stmtServ = $pdo->prepare("
    SELECT ps.*, s.nome AS servico_nome, s.descricao AS servico_descricao
    FROM propostas_servicos ps
    INNER JOIN servicos s ON s.id = ps.servico_id
    WHERE ps.proposta_id = :pid
    ORDER BY ps.embarcacao_id, s.nome ASC
");
$stmtServ->execute([':pid' => $proposta_id]);
$servicos_todos = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

// Agrupar serviços por embarcação
$servicos_por_embarcacao = [];
foreach ($servicos_todos as $sv) {
    $embId = $sv['embarcacao_id'] ?? 'sem_embarcacao';
    if (!isset($servicos_por_embarcacao[$embId])) {
        $servicos_por_embarcacao[$embId] = [];
    }
    $servicos_por_embarcacao[$embId][] = $sv;
}

// Buscar nome do criador (usuário)
$criadorNome = 'Amazon Naval Ltda';
if (!empty($proposta['criado_por'])) {
    $stmtUser = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :uid");
    $stmtUser->execute([':uid' => $proposta['criado_por']]);
    $userRow = $stmtUser->fetch();
    if ($userRow) {
        $criadorNome = $userRow['nome'];
    }
}

// ============================================
// CARREGAR TCPDF (Autoloader do Composer)
// ============================================
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Autoloader do Composer não encontrado.");
}
require_once $autoload_path;

// ============================================
// FUNÇÕES AUXILIARES
// ============================================
function dataExtenso($data) {
    if (empty($data)) return '___/___/______';
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function formatarMoedaPDF($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function dataBR($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

function valorPorExtenso($valor) {
    $valor = (float)$valor;
    $inteiro = floor($valor);
    $centavos = round(($valor - $inteiro) * 100);

    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    $dezenas = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $especiais = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    if ($inteiro == 0 && $centavos == 0) return 'zero real';
    if ($inteiro == 0) return $centavos . ($centavos == 1 ? ' centavo' : ' centavos');

    $extenso = '';

    // Milhares
    if ($inteiro >= 1000) {
        $milhar = floor($inteiro / 1000);
        $inteiro %= 1000;
        if ($milhar == 1) {
            $extenso .= 'um mil';
        } else {
            $extenso .= $unidades[$milhar] . ' mil';
        }
        if ($inteiro > 0) $extenso .= ' e ';
    }

    // Centenas
    if ($inteiro >= 100) {
        $c = floor($inteiro / 100);
        $inteiro %= 100;
        if ($inteiro == 0 && $c == 1) {
            $extenso .= 'cem';
        } else {
            $extenso .= $centenas[$c];
        }
        if ($inteiro > 0) $extenso .= ' e ';
    }

    // Dezenas e unidades
    if ($inteiro >= 10 && $inteiro <= 19) {
        $extenso .= $especiais[$inteiro - 10];
    } else {
        if ($inteiro >= 20) {
            $d = floor($inteiro / 10);
            $inteiro %= 10;
            $extenso .= $dezenas[$d];
            if ($inteiro > 0) $extenso .= ' e ';
        }
        if ($inteiro > 0) {
            $extenso .= $unidades[$inteiro];
        }
    }

    $extenso .= $valor == 1 ? ' real' : ' reais';

    if ($centavos > 0) {
        $extenso .= ' e ' . $centavos . ($centavos == 1 ? ' centavo' : ' centavos');
    }

    return $extenso;
}

// ============================================
// CLASSE PDF PERSONALIZADA
// ============================================
class PropostaPDF extends TCPDF {
    protected $proposta;
    protected $numero;
    protected $totalPages;

    public function __construct($proposta) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->proposta = $proposta;
        $this->numero  = $proposta['numero'];

        $this->SetCreator(APP_NAME);
        $this->SetAuthor('Amazon Naval Ltda');
        $this->SetTitle('Proposta de Serviço - ' . $proposta['numero']);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 20);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    /**
     * Desenha o cabeçalho padrão que se repete em todas as páginas
     */
    public function desenharCabecalho() {
        // Linha superior decorativa azul escuro
        $this->SetFillColor(0, 51, 102);
        $this->Rect(15, 10, 180, 3, 'F');

        // Logo Amazon Naval (esquerda)
        $logo_path = __DIR__ . '/../../assets/img/logo.png';
        if (file_exists($logo_path) && filesize($logo_path) > 100) {
            $this->Image($logo_path, 17, 16, 22, 22, 'PNG', '', '', true, 150);
        }

        // Título central
        $this->SetY(16);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 7, 'PROPOSTA DE SERVIÇO', 0, 1, 'C');

        // Nome da empresa
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(20, 80, 140);
        $this->Cell(0, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

        // Número da proposta
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 5, $this->numero, 0, 1, 'R');

        // Linha separadora
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY() + 1, 195, $this->GetY() + 1);
        $this->Ln(4);
    }

    /**
     * Desenha o rodapé com número de página
     */
    public function desenharRodape() {
        $this->SetY(-18);
        // Linha separadora
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.3);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);

        // Contato
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(80, 4, 'AMAZON NAVAL LTDA', 0, 0, 'L');
        $this->Cell(0, 4, 'CEL/WhatsApp: (91) 99111-2065', 0, 0, 'R');
        $this->Ln(3);
        $this->Cell(0, 4, 'VICTAL DONAZAN NETO', 0, 0, 'R');
        $this->Ln(3);

        // Número da página
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    public function Header() {
        $this->desenharCabecalho();
    }

    public function Footer() {
        $this->desenharRodape();
    }
}

// ============================================
// CRIAR PDF
// ============================================
$pdf = new PropostaPDF($proposta);

// ==================== PÁGINA 1 ====================
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);

// --- BLOCO: NOME DA EMBARCAÇÃO ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 5, 'NOME DA EMBARCAÇÃO', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

// Listar todas as embarcações
$embarcacoesNomes = [];
foreach ($embarcacoes as $emb) {
    $linhaNome = '"' . mb_strtoupper($emb['nome']) . '"';
    if (!empty($emb['registro'])) {
        $linhaNome .= '     Registro: ' . $emb['registro'];
    }
    $embarcacoesNomes[] = $linhaNome;
}

if (empty($embarcacoesNomes)) {
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, '(Nenhuma embarcação vinculada)', 0, 1, 'L');
} else {
    foreach ($embarcacoesNomes as $i => $nomeEmb) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $nomeEmb, 0, 1, 'L');
    }
}

$pdf->Ln(2);

// --- BLOCO: CONTRATANTE / CONTRATADA (duas colunas) ---
$colEsq = 90;
$colDir = 90;

// CONTRATANTE
$yInicio = $pdf->GetY();
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell($colEsq, 6, 'CONTRATANTE:', 1, 0, 'L', true);
$pdf->Cell($colDir, 6, 'CONTRATADA:', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

// Nome contratante
$clienteNome = mb_strtoupper($proposta['cliente_nome'] ?? '');
$pdf->Cell($colEsq, 5, $clienteNome, 1, 0, 'L', true);
$pdf->Cell($colDir, 5, 'AMAZON NAVAL LTDA', 1, 1, 'L', true);

// CPF/CNPJ
$cpfcnpj = !empty($proposta['cliente_cpfcnpj']) ? 'CNPJ/CPF: ' . $proposta['cliente_cpfcnpj'] : '';
$pdf->Cell($colEsq, 5, $cpfcnpj, 1, 0, 'L', true);
$pdf->Cell($colDir, 5, 'CNPJ: 60.360.061/0001-91', 1, 1, 'L', true);

// Telefone / WhatsApp
$telCliente = !empty($proposta['cliente_telefone']) ? 'Tel: ' . $proposta['cliente_telefone'] : '';
$pdf->Cell($colEsq, 5, $telCliente, 1, 0, 'L', true);
$pdf->Cell($colDir, 5, 'CEL/WhatsApp: (91) 99111-2065', 1, 1, 'L', true);

// Email
$emailCliente = !empty($proposta['cliente_email']) ? 'Email: ' . $proposta['cliente_email'] : '';
$pdf->Cell($colEsq, 5, $emailCliente, 1, 0, 'L', true);
$pdf->Cell($colDir, 5, 'VICTAL DONAZAN NETO', 1, 1, 'L', true);

// Data
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($colEsq, 5, 'DATA: ' . dataBR($proposta['data_emissao']), 1, 0, 'L', true);
$pdf->Cell($colDir, 5, '', 1, 1, 'L', true);

$pdf->Ln(5);

// --- BLOCO: DADOS TÉCNICOS DA EMBARCAÇÃO ---
if (!empty($embarcacoes)) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(0, 51, 102);
    $pdf->Cell(0, 5, 'DADOS TÉCNICOS DA EMBARCAÇÃO', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(1);

    // Tabela de dados técnicos para cada embarcação
    foreach ($embarcacoes as $emb) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(230, 235, 245);
        $pdf->Cell(180, 5, mb_strtoupper($emb['nome']), 1, 1, 'L', true);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);

        $colW = [60, 60, 60];
        $comprimento = !empty($emb['comprimento_total']) ? number_format((float)$emb['comprimento_total'], 3, ',', '.') . ' m' : 'N/I';
        $boca = !empty($emb['boca_moldada']) ? number_format((float)$emb['boca_moldada'], 3, ',', '.') . ' m' : 'N/I';
        $pontal = !empty($emb['pontal_moldado']) ? number_format((float)$emb['pontal_moldado'], 3, ',', '.') . ' m' : 'N/I';

        $pdf->Cell($colW[0], 5, 'COMPRIMENTO: ' . $comprimento, 1, 0, 'L', true);
        $pdf->Cell($colW[1], 5, 'BOCA: ' . $boca, 1, 0, 'L', true);
        $pdf->Cell($colW[2], 5, 'PONTAL: ' . $pontal, 1, 1, 'L', true);

        $tipoEmb = !empty($emb['tipo_embarcacao']) ? $emb['tipo_embarcacao'] : 'N/I';
        $material = !empty($emb['material_casco']) ? $emb['material_casco'] : 'N/I';

        $pdf->Cell($colW[0], 5, 'TIPO: ' . $tipoEmb, 1, 0, 'L', true);
        $pdf->Cell($colW[1] + $colW[2], 5, 'MATERIAL DO CASCO: ' . $material, 1, 1, 'L', true);
    }

    $pdf->Ln(5);
}

// --- BLOCO: OBJETO DA PROPOSTA E SERVIÇOS ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 51, 102);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 7, 'OBJETO DA PROPOSTA E SERVIÇOS', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, 'Prestação de serviços técnicos especializados de engenharia naval, incluindo vistorias técnicas, análises de planos, emissão de certificados e demais serviços correlatos, conforme especificações e quantidades indicadas abaixo.', 0, 'L');
$pdf->Ln(2);

// --- TABELA DE SERVIÇOS ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(0, 51, 102);
$pdf->SetTextColor(255, 255, 255);

$wServ = [10, 100, 20, 30, 30];
$pdf->Cell($wServ[0], 6, 'Nº', 1, 0, 'C', true);
$pdf->Cell($wServ[1], 6, 'DESCRIÇÃO DOS SERVIÇOS', 1, 0, 'C', true);
$pdf->Cell($wServ[2], 6, 'QTD', 1, 0, 'C', true);
$pdf->Cell($wServ[3], 6, 'PREÇO UNIT.', 1, 0, 'C', true);
$pdf->Cell($wServ[4], 6, 'SUBTOTAL', 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);

$contador = 0;
$subtotalGeral = 0;

if (empty($servicos_todos)) {
    $pdf->Cell(array_sum($wServ), 6, 'Nenhum serviço selecionado.', 1, 1, 'C');
} else {
    foreach ($servicos_por_embarcacao as $embId => $servicos) {
        // Nome da embarcação como subcabeçalho
        $nomeEmbServ = '';
        foreach ($embarcacoes as $e) {
            if ($e['id'] === $embId) {
                $nomeEmbServ = $e['nome'] . (!empty($e['registro']) ? ' (' . $e['registro'] . ')' : '');
                break;
            }
        }
        if ($embId === 'sem_embarcacao') {
            $nomeEmbServ = 'Serviços Gerais';
        }

        if (!empty($nomeEmbServ) && count($servicos_por_embarcacao) > 1) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(240, 243, 250);
            $pdf->SetTextColor(0, 51, 102);
            $pdf->Cell(array_sum($wServ), 5, mb_strtoupper($nomeEmbServ), 1, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 8);
        }

        foreach ($servicos as $sv) {
            $contador++;
            $preco = (float)$sv['preco_aplicado'];
            $qtd   = (int)$sv['quantidade'];
            $sub   = round($preco * $qtd, 2);
            $subtotalGeral += $sub;

            // Altura da linha baseada no tamanho do nome
            $nomeServ = $sv['servico_nome'];
            $hLinha = 6;

            $pdf->SetFillColor(($contador % 2 == 0) ? 250 : 255, 251, 252);
            $pdf->Cell($wServ[0], $hLinha, $contador, 1, 0, 'C', true);
            $pdf->Cell($wServ[1], $hLinha, $nomeServ, 1, 0, 'L', true);
            $pdf->Cell($wServ[2], $hLinha, $qtd, 1, 0, 'C', true);
            $pdf->Cell($wServ[3], $hLinha, 'R$ ' . formatarMoedaPDF($preco), 1, 0, 'R', true);
            $pdf->Cell($wServ[4], $hLinha, 'R$ ' . formatarMoedaPDF($sub), 1, 1, 'R', true);
        }
    }
}

// --- TOTAIS ---
$descontoPerc = (float)($proposta['desconto_percentual'] ?? 0);
$descontoValor = round($subtotalGeral * ($descontoPerc / 100), 2);
$totalGeral = round($subtotalGeral - $descontoValor, 2);

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 9);

// Linha Subtotal
$colTotalLabel = $wServ[0] + $wServ[1] + $wServ[2]; // 130
$colTotalValor = $wServ[3] + $wServ[4]; // 60

$pdf->SetFillColor(245, 245, 250);
$pdf->Cell($colTotalLabel, 6, 'TOTAL DOS SERVIÇOS', 1, 0, 'R', true);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($colTotalValor, 6, 'R$ ' . formatarMoedaPDF($subtotalGeral), 1, 1, 'R', true);

// Linha Desconto (se houver)
if ($descontoValor > 0) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($colTotalLabel, 6, 'DESCONTO (' . number_format($descontoPerc, 2, ',', '.') . '%)', 1, 0, 'R', true);
    $pdf->SetTextColor(180, 0, 0);
    $pdf->Cell($colTotalValor, 6, '- R$ ' . formatarMoedaPDF($descontoValor), 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
}

// Linha Total Geral
$pdf->SetFillColor(0, 51, 102);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($colTotalLabel, 7, 'TOTAL GERAL', 1, 0, 'R', true);
$pdf->Cell($colTotalValor, 7, 'R$ ' . formatarMoedaPDF($totalGeral), 1, 1, 'R', true);

// Valor por extenso
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(array_sum($wServ), 5, '(' . mb_strtoupper(valorPorExtenso($totalGeral)) . ')', 0, 1, 'R');

// Validado até
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 5, 'VALIDADE DA PROPOSTA: ' . dataBR($proposta['data_validade'] ?? ''), 0, 1, 'L', true);

$pdf->Ln(10);

// --- BLOCO: PARCELAS E FORMA DE PAGAMENTO ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 51, 102);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 7, 'FORMA DE PAGAMENTO', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$parcelas = (int)$proposta['parcelas'];
$valorParcela = ($parcelas > 0) ? round($totalGeral / $parcelas, 2) : $totalGeral;

// Ajustar última parcela para fechar valor exato
$somaParcelas = $valorParcela * ($parcelas - 1);
$ultimaParcela = round($totalGeral - $somaParcelas, 2);
$valorEntrada = round((float)($proposta['valor_entrada'] ?? 0), 2);
if ($valorEntrada > $totalGeral) {
    $valorEntrada = $totalGeral;
}
$saldoParcelado = max(0, round($totalGeral - $valorEntrada, 2));
$parcelas = max(1, $parcelas);
$valorParcela = ($parcelas > 0) ? round($saldoParcelado / $parcelas, 2) : $saldoParcelado;
$somaParcelas = $valorParcela * max(0, ($parcelas - 1));
$ultimaParcela = round($saldoParcelado - $somaParcelas, 2);

$pdf->SetFont('helvetica', '', 8);
$formaPagamentoTexto = '';
switch ($proposta['forma_pagamento'] ?? 'parcelado') {
    case 'a_vista':  $formaPagamentoTexto = 'À Vista'; break;
    case 'parcelado': $formaPagamentoTexto = 'Parcelado'; break;
    case 'boleto':   $formaPagamentoTexto = 'Boleto Bancário'; break;
    case 'pix':      $formaPagamentoTexto = 'PIX'; break;
    default:         $formaPagamentoTexto = 'Parcelado';
}

$resumoParcelas = $parcelas . 'x';
if ($valorEntrada > 0) {
    $resumoParcelas .= ' + entrada';
}
$pdf->Cell(0, 5, 'Forma de pagamento: ' . $formaPagamentoTexto . ' | Parcelas: ' . $resumoParcelas, 0, 1, 'L');
$pdf->Ln(2);

// Tabela de parcelas
$colParc = [30, 50, 60, 40];
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(0, 51, 102);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($colParc[0], 6, 'PARCELA', 1, 0, 'C', true);
$pdf->Cell($colParc[1], 6, 'CONDIÇÕES DAS PARCELAS', 1, 0, 'C', true);
$pdf->Cell($colParc[2], 6, 'VENCIMENTO', 1, 0, 'C', true);
$pdf->Cell($colParc[3], 6, 'VALOR', 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);
$linhaParcela = 1;
if ($valorEntrada > 0) {
    $pdf->SetFillColor(255, 251, 252);
    $pdf->Cell($colParc[0], 6, 'Entrada', 1, 0, 'C', true);
    $pdf->Cell($colParc[1], 6, 'Entrada (ato da assinatura)', 1, 0, 'L', true);
    $pdf->Cell($colParc[2], 6, 'Na assinatura', 1, 0, 'C', true);
    $pdf->Cell($colParc[3], 6, 'R$ ' . formatarMoedaPDF($valorEntrada), 1, 1, 'R', true);
}

for ($i = 1; $i <= $parcelas; $i++) {
    $valor = ($i == $parcelas) ? $ultimaParcela : $valorParcela;
    $vencimento = $i === 1 ? '30 dias após assinatura' : ($i * 30) . ' dias após assinatura';
    $condicao = ($i == 1) ? '1ª parcela' : (($i == $parcelas) ? 'Última parcela' : 'Parcela intermediária');

    $pdf->SetFillColor(($linhaParcela % 2 == 0) ? 250 : 255, 251, 252);
    $pdf->Cell($colParc[0], 6, $i . '/' . $parcelas, 1, 0, 'C', true);
    $pdf->Cell($colParc[1], 6, $condicao, 1, 0, 'L', true);
    $pdf->Cell($colParc[2], 6, $vencimento, 1, 0, 'C', true);
    $pdf->Cell($colParc[3], 6, 'R$ ' . formatarMoedaPDF($valor), 1, 1, 'R', true);
    $linhaParcela++;
}

// Total parcelado
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(245, 245, 250);
$pdf->Cell($colParc[0] + $colParc[1] + $colParc[2], 6, 'TOTAL', 1, 0, 'R', true);
$pdf->Cell($colParc[3], 6, 'R$ ' . formatarMoedaPDF($totalGeral), 1, 1, 'R', true);

$pdf->Ln(3);

// --- DADOS BANCÁRIOS ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 6, 'DADOS BANCÁRIOS', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);

$pdf->MultiCell(0, 4, "O pagamento, quando acordado a ser realizado por transferência ou depósito, deverá ser efetuado na conta corrente do Banco Inter 077, Agência 0001, Conta Corrente 0533429765, Código do operador 70723325, ou PIX 60.360.061/0001-91.", 0, 'L');
$pdf->Ln(2);

// --- DESPESAS EVENTUAIS ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 6, 'DESPESAS EVENTUAIS', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);

$pdf->MultiCell(0, 4, "Todas as despesas de deslocamento (uber ou táxi) e alimentação dentro da cidade de BELÉM/PA deverão ser pagas pelo ACEITANTE.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "Havendo necessidade de realização de vistorias no Sábado ou Domingo ou Feriado, o ACEITANTE deverá arcar com o valor de R\$300,00 (trezentos reais) a diária, a ser pago de forma integral via PIX ou transferência bancária, no dia da realização da vistoria.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "Todas as despesas com passagens (aéreas, terrestres ou fluviais), combustível, alimentação, hospedagem, entre outras, fora da cidade de BELÉM/PA deverão ser arcadas pelo ACEITANTE.", 0, 'L');
$pdf->Ln(4);

// --- PRAZO DE VALIDADE DA PROPOSTA ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 6, '5.0 - PRAZO DE VALIDADE DA PROPOSTA DE SERVIÇOS', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, "O prazo de validade desta proposta de serviços é de 30 (trinta) dias contados a partir da sua data de emissão, sendo que, posteriormente a esse prazo, o PROPONENTE não se obrigará a manter as condições e os preços por ventura compactuados.", 0, 'L');
$pdf->Ln(3);

// --- SERVIÇOS A REALIZAR ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 6, '6.0 - SERVIÇOS A REALIZAR', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, "O agendamento das vistorias ficará condicionado à apresentação prévia do projeto da embarcação, em meio físico ou digital, devidamente acompanhado de todas as informações e documentos necessários para análise técnica.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "Os agendamentos para realização dos serviços deverão ser feitos com a devida antecedência, sendo que as orientações dadas pelo PROPONENTE deverão ser seguidas pelo ACEITANTE.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "No caso do ACEITANTE não cumprir os prazos dos certificados mencionados no item acima, será realizada uma nova PROPOSTA DE SERVIÇOS.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "No caso de excepcional desistência na conclusão dos serviços por parte do ACEITANTE, este se compromete a arcar com as despesas já realizadas e não pagas, bem como o valor residual acordado e não pago desta proposta de serviços.", 0, 'L');

$pdf->Ln(10);

// --- DO FORO ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 6, '7.0 - DO FORO', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, "Fica eleito o foro da comarca de BELÉM/PA para nele serem dirimidas as dúvidas porventura surgidas no fiel cumprimento deste instrumento.", 0, 'L');
$pdf->Ln(5);

// --- ACEITE FORMAL ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 51, 102);
$pdf->SetFillColor(230, 235, 245);
$pdf->Cell(0, 7, '8.0 - ACEITE FORMAL', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, "Pela presente proposta de serviços, a CONTRATADA se compromete a prestar os serviços descritos na Cláusula 1.0 (OBJETO DA PROPOSTA E SERVIÇOS), mediante as condições de pagamento constantes na Cláusula 4.0 (CONDIÇÕES DE PAGAMENTO), e o ACEITANTE se compromete a efetuar o pagamento conforme acordado.", 0, 'L');
$pdf->Ln(1);
$pdf->MultiCell(0, 4, "O ACEITANTE declara estar ciente e de acordo com todos os termos e condições estipulados na presente proposta de serviços.", 0, 'L');

$pdf->Ln(8);

// --- ÁREA DE ASSINATURAS ---
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}
$assinaturaY = $pdf->GetY();

// Quadro da assinatura CONTRATANTE (esquerda)
$pdf->SetDrawColor(0, 51, 102);
$pdf->SetLineWidth(0.5);
$pdf->Rect(15, $assinaturaY, 88, 60);
$pdf->SetDrawColor(180, 190, 210);
$pdf->SetLineWidth(0.2);
$pdf->Rect(17, $assinaturaY + 2, 84, 56);

// Quadro da assinatura CONTRATADA (direita)
$pdf->SetDrawColor(0, 51, 102);
$pdf->SetLineWidth(0.5);
$pdf->Rect(107, $assinaturaY, 88, 60);
$pdf->SetDrawColor(180, 190, 210);
$pdf->SetLineWidth(0.2);
$pdf->Rect(109, $assinaturaY + 2, 84, 56);

// Título ACEITANTE (esquerda)
$pdf->SetXY(17, $assinaturaY + 4);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(84, 5, 'ACEITANTE', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(17, $assinaturaY + 9);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(84, 5, $clienteNome, 0, 1, 'C');

// Título PROPONENTE (direita)
$pdf->SetXY(109, $assinaturaY + 4);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(84, 5, 'PROPONENTE', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(109, $assinaturaY + 9);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(84, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

// Linha de assinatura CONTRATANTE
$pdf->SetDrawColor(80, 80, 80);
$pdf->SetLineWidth(0.3);
$pdf->Line(25, $assinaturaY + 40, 95, $assinaturaY + 40);

// Linha de assinatura CONTRATADA
$pdf->Line(115, $assinaturaY + 40, 187, $assinaturaY + 40);

// Label abaixo das linhas
$pdf->SetXY(25, $assinaturaY + 42);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(70, 4, 'Assinatura do ACEITANTE', 0, 0, 'C');

$pdf->SetXY(115, $assinaturaY + 42);
$pdf->Cell(72, 4, 'Assinatura do PROPONENTE', 0, 0, 'C');

// Nome abaixo
$pdf->SetXY(25, $assinaturaY + 47);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(80, 80, 80);
$cpfCnpjLimpo = !empty($proposta['cliente_cpfcnpj']) ? 'CPF/CNPJ: ' . $proposta['cliente_cpfcnpj'] : '';
$pdf->Cell(70, 4, $cpfCnpjLimpo, 0, 0, 'C');

$pdf->SetXY(115, $assinaturaY + 47);
$pdf->Cell(72, 4, 'CPF/CNPJ: 60.360.061/0001-91', 0, 0, 'C');

// Logo Amazon Naval no quadro direito
$logo_path2 = __DIR__ . '/../../assets/img/logo.png';
if (file_exists($logo_path2) && filesize($logo_path2) > 100) {
    $pdf->Image($logo_path2, 135, $assinaturaY + 14, 18, 18, 'PNG', '', '', true, 150);
}

// Imagem da Assinatura do Cliente
if (!empty($proposta['assinado'])) {
    $imagemAssinatura = carregarImagemAssinatura(
        $proposta['assinatura_imagem'] ?? '',
        $proposta['assinatura_url'] ?? ''
    );

    if ($imagemAssinatura !== null) {
        $tmp_file = tempnam(sys_get_temp_dir(), 'sig_') . '.' . $imagemAssinatura['extensao'];
        file_put_contents($tmp_file, $imagemAssinatura['bytes'], LOCK_EX);
        $pdf->Image(
            $tmp_file,
            25,
            $assinaturaY + 14,
            55,
            25,
            $imagemAssinatura['tipo_pdf'],
            '',
            '',
            true,
            150
        );
        @unlink($tmp_file);
    } elseif (!empty($proposta['assinatura_url'])) {
        // Nova abordagem: baixar URL
        $url = $proposta['assinatura_url'];
        
        $decoded = false;
        
        // Se a URL for um fallback local (uploads), vamos ler o arquivo localmente
        if (strpos($url, '/uploads/assinaturas/') !== false) {
            $parsed = parse_url($url);
            $localFilePath = UPLOADS_PATH . str_replace('/uploads/', '', $parsed['path']);
            // Tenta remover o diretorio base se ele estiver no path
            $localFilePath = preg_replace('/.*\/uploads\//', UPLOADS_PATH, $url);
            
            if (file_exists($localFilePath)) {
                $decoded = file_get_contents($localFilePath);
            }
        }
        
        // Se ainda não leu e é MinIO
        if ($decoded === false && class_exists('Aws\S3\S3Client') && strpos($url, 'erp-storage') !== false) {
            // Ajustar URL para uso interno no Docker (se necessário)
            $url = str_replace(['http://localhost:9002', 'http://localhost:9000'], 'http://minio:9000', $url);
            try {
                $s3 = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => 'us-east-1',
                    'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
                    'use_path_style_endpoint' => true,
                    'credentials' => [
                        'key'    => defined('MINIO_ACCESS_KEY') ? MINIO_ACCESS_KEY : 'erp_minio_admin',
                        'secret' => defined('MINIO_SECRET_KEY') ? MINIO_SECRET_KEY : 'erp_minio_pass_2026',
                    ],
                ]);
                
                // Ex: http://minio:9000/erp-storage/assinaturas/propostas/abc_123.png
                $parsedUrl = parse_url($url);
                $path = ltrim($parsedUrl['path'], '/');
                $bucket = defined('MINIO_BUCKET') ? MINIO_BUCKET : 'erp-storage';
                // Remove o nome do bucket do path para pegar a key
                $key = preg_replace('/^' . preg_quote($bucket, '/') . '\//', '', $path);
                
                $result = $s3->getObject([
                    'Bucket' => $bucket,
                    'Key'    => $key
                ]);
                $decoded = (string)$result['Body'];
            } catch (Exception $e) {
                // Tenta fallback
                error_log("Erro S3 no PDF: " . $e->getMessage());
            }
        }
        
        if ($decoded === false || empty($decoded)) {
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "Accept-language: en\r\n"
                ],
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ];
            $context = stream_context_create($opts);
            $decoded = @file_get_contents($url, false, $context);
        }
        
        if ($decoded !== false && !empty($decoded)) {
            $tmp_file = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
            file_put_contents($tmp_file, $decoded);
            $pdf->Image($tmp_file, 25, $assinaturaY + 14, 55, 25, 'PNG', '', '', true, 150);
            @unlink($tmp_file);
        }
    } elseif (!empty($proposta['assinatura_imagem'])) {
        // Fallback legado Base64
        $img_data = $proposta['assinatura_imagem'];
        if (preg_match('/^data:image\/(\w+);base64,/', $img_data, $type)) {
            $img_data = substr($img_data, strpos($img_data, ',') + 1);
        }
        $decoded = base64_decode($img_data);
        if ($decoded !== false) {
            $tmp_file = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
            file_put_contents($tmp_file, $decoded);
            $pdf->Image($tmp_file, 25, $assinaturaY + 14, 55, 25, 'PNG', '', '', true, 150);
            @unlink($tmp_file);
        }
    }
}

// Data e local no rodapé dos quadros
$pdf->SetXY(17, $assinaturaY + 54);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(84, 4, 'BELÉM/PA, ' . dataBR($proposta['data_emissao']), 0, 0, 'C');

$pdf->SetXY(109, $assinaturaY + 54);
$pdf->Cell(84, 4, 'BELÉM/PA, ' . dataBR($proposta['data_emissao']), 0, 0, 'C');

// Se tiver observações personalizadas, exibir
if (!empty($proposta['observacoes'])) {
    $pdf->Ln(65);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(0, 51, 102);
    $pdf->SetFillColor(230, 235, 245);
    $pdf->Cell(0, 6, 'OBSERVAÇÕES', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 4, $proposta['observacoes'], 0, 'L');
}

// --- QR Code + Link (rodapé da página) ---
if (!empty($proposta['token_assinatura'])) {
    $pdf->Ln(10);
    $link_assinatura = APP_URL . 'assinar/' . $proposta['token_assinatura'];
    $qr_y = $pdf->GetY();
    
    // Verificar se cabe na página
    if ($qr_y > 250) {
        $pdf->AddPage();
        $qr_y = $pdf->GetY();
    }
    
    try {
        $qr = new TCPDF2DBarcode($link_assinatura, 'QRCODE,M');
        $qr_png = $qr->getBarcodePngData(3, 3, array(0, 0, 0));
        $qr_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($qr_file, $qr_png);
        $pdf->Image($qr_file, 15, $qr_y, 15, 15, 'PNG');
        @unlink($qr_file);
        $pdf->SetXY(32, $qr_y);
    } catch (Exception $e) {
        $pdf->SetXY(15, $qr_y);
    }
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(80, 5, 'Link de assinatura: ' . $link_assinatura, 0, 1, 'L');
    
    if ($proposta['assinado']) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetX(32);
        $pdf->SetTextColor(0, 100, 0);
        $pdf->Cell(0, 5, 'Documento assinado digitalmente por ' . h($proposta['assinante_nome']) . ' em ' . date('d/m/Y H:i:s', strtotime($proposta['assinatura_em'])), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    } else {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetX(32);
        $pdf->Cell(0, 5, 'Acesse o link para assinar este documento.', 0, 1, 'L');
    }
}

// ============================================
// SAÍDA DO PDF
// ============================================
$nomeArquivo = 'Proposta_' . str_replace('/', '-', $proposta['numero']) . '.pdf';
if (!empty($GLOBALS['PROPOSTA_PDF_RETURN_STRING'])) {
    return $pdf->Output($nomeArquivo, 'S');
}

$pdf->Output($nomeArquivo, 'I');
exit;
