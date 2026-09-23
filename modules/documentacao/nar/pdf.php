<?php
/**
 * MÓDULO: Documentação > Notas de Arqueação (AM-NAR)
 * PDF Oficial em 3 Páginas conforme padrão Amazon Naval e DPC / NORMAM
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/certificado_pdf_marca_dagua.php';

$id = trim($_GET['id'] ?? '');
$token = trim($_GET['token'] ?? '');

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_nar WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token]);
    $id = (string)($stmt->fetchColumn() ?: '');
}

if (empty($id)) {
    die('ID ou token não informado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_nar WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$nar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nar) {
    die('Nota de Arqueação não encontrada.');
}

// Se já assinado e existe PDF congelado em disco, entregar diretamente
if (!isset($salvar_pdf_caminho) && !empty($nar['assinado']) && !empty($nar['caminho_arquivo_pdf'])) {
    $caminhoFisico = __DIR__ . '/../../../' . ltrim(str_replace(['../', '..\\'], '', $nar['caminho_arquivo_pdf']), '/\\');
    if (is_file($caminhoFisico)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nar['numero']) . '.pdf"');
        header('Content-Length: ' . filesize($caminhoFisico));
        readfile($caminhoFisico);
        exit;
    }
}

// Funções auxiliares de formatação
function narFmtNum($val, int $dec = 2, string $sufixo = ''): string {
    if ($val === null || $val === '') return '0,00' . $sufixo;
    return number_format((float)$val, $dec, ',', '.') . $sufixo;
}

function narDataExtenso(?string $data): string {
    if (!$data) return '';
    $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
    $dt = new DateTime($data);
    return (int)$dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

// Subclasse TCPDF para Notas de Arqueação
if (!class_exists('NotasArqueacaoPDF')) {
    class NotasArqueacaoPDF extends CertificadoPdfComMarcaDagua {
        public string $numeroNar = '';
        public string $nomeEmbarcacao = '';
        public string $enquadramentoTexto = '';

        public function Header() {
            $logo = __DIR__ . '/../../../img/logo.png';
            if (!is_file($logo)) {
                $logo = __DIR__ . '/../../../assets/img/logo.png';
            }

            // Marca Oficial da Empresa
            if (is_file($logo)) {
                $this->Image($logo, 15, 10, 22, 22, 'PNG', '', '', true, 300);
            }

            // Título Oficial Centralizado
            $this->SetXY(40, 9);
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(15, 23, 42);
            $this->Cell(115, 6, 'NOTAS PARA ARQUEAÇÃO DE EMBARCAÇÕES', 0, 1, 'C');

            $this->SetX(40);
            $this->SetFont('helvetica', 'B', 8.5);
            $this->SetTextColor(71, 85, 105);
            $this->Cell(115, 4.5, $this->enquadramentoTexto, 0, 1, 'C');

            $this->SetX(40);
            $this->SetFont('helvetica', 'B', 7.5);
            $this->SetTextColor(100, 116, 139);
            $this->Cell(115, 4, 'NOME DA EMBARCAÇÃO: ' . mb_strtoupper($this->nomeEmbarcacao, 'UTF-8'), 0, 1, 'C');

            // Caixa do Número AM-NAR à Direita
            $this->SetXY(158, 10);
            $this->SetDrawColor(13, 73, 65);
            $this->SetLineWidth(0.3);
            $this->SetFillColor(240, 253, 250);
            $this->Rect(158, 10, 37, 18, 'DF');

            $this->SetXY(158, 12);
            $this->SetFont('helvetica', 'B', 7.5);
            $this->SetTextColor(13, 73, 65);
            $this->Cell(37, 4, 'AM-NAR:', 0, 1, 'C');

            $this->SetXY(158, 17);
            $this->SetFont('helvetica', 'B', 9);
            $this->SetTextColor(15, 23, 42);
            $this->Cell(37, 5, $this->numeroNar, 0, 1, 'C');

            // Linha divisória horizontal
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            $this->Line(15, 33, 195, 33);
        }

        public function Footer() {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 7.5);
            $this->SetTextColor(148, 163, 184);
            $this->Cell(100, 5, 'Amazon Naval Certificação & Engenharia Naval · Documento Autêntico NORMAM', 0, 0, 'L');
            $this->Cell(80, 5, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}

$pdf = new NotasArqueacaoPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Nota de Arqueação ' . $nar['numero']);

$enqTexto = ($nar['enquadramento_comprimento'] === 'L_MENOR_24') ? '(L < 24 m)' : '(L > ou = 24 m)';
$pdf->numeroNar = $nar['numero'];
$pdf->nomeEmbarcacao = $nar['nome_embarcacao'];
$pdf->enquadramentoTexto = $enqTexto;

$pdf->SetMargins(15, 36, 15);
$pdf->SetAutoPageBreak(false, 0);

// =========================================================================
// PÁGINA 1: CARACTERÍSTICAS GERAIS, CASCO, TRIPULAÇÃO E CALCULADAS
// =========================================================================
$pdf->AddPage();
$pdf->SetTextColor(15, 23, 42);
$pdf->SetDrawColor(148, 163, 184);
$pdf->SetLineWidth(0.2);

// Estilos de caixa
$hLinha = 5.2;

// --- 1. Características Gerais ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetFillColor(241, 245, 249);
$pdf->Cell(180, 5.5, ' 1. Características Gerais', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Nome da Embarcação:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(140, $hLinha, ' ' . $nar['nome_embarcacao'], 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Armador:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(140, $hLinha, ' ' . ($nar['armador'] ?: 'x-x-x'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Construtor:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(140, $hLinha, ' ' . ($nar['construtor'] ?: 'x-x-x'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Número do Casco:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['numero_casco'] ?: 'x-x-x'), 'T', 0);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Material do Casco:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['material_casco'] ?: 'AÇO'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Tipo:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['tipo_embarcacao'] ?: 'BALSA'), 'T', 0);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Classificação:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['classificacao'] ?: 'CARGA GERAL'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Porto de Inscrição:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['porto_inscricao'] ?: 'BELÉM - PA'), 'T', 0);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(40, $hLinha, ' Ano de Construção / Quilha:', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(50, $hLinha, ' ' . ($nar['data_construcao_quilha'] ?: date('Y')), 'TR', 1);
$pdf->Cell(180, 0, '', 'T', 1);

$pdf->Ln(2);

// --- 2. Características do Casco ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 2. Características do Casco', 1, 1, 'L', true);

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(36, $hLinha, ' Ct = ' . narFmtNum($nar['comprimento_total_ct'], 2, ' m'), 'LTB', 0);
$pdf->Cell(36, $hLinha, ' L = ' . narFmtNum($nar['comprimento_regra_l'], 2, ' m'), 'LTB', 0);
$pdf->Cell(36, $hLinha, ' Lpp = ' . narFmtNum($nar['comprimento_lpp'], 2, ' m'), 'LTB', 0);
$pdf->Cell(36, $hLinha, ' B = ' . narFmtNum($nar['boca_moldada_b'], 2, ' m'), 'LTB', 0);
$pdf->Cell(36, $hLinha, ' P = ' . narFmtNum($nar['pontal_moldado_p'], 2, ' m'), 'LTRB', 1);

// Tabela de Calados
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(90, 5, ' Calado Leve (Hl):', 'LT', 0);
$pdf->Cell(90, 5, ' Calado Carregado (Hc):', 'LTR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(30, 4.5, ' AV = ' . narFmtNum($nar['calado_leve_av'], 3, ' m'), 'L', 0);
$pdf->Cell(30, 4.5, ' AR = ' . narFmtNum($nar['calado_leve_ar'], 3, ' m'), '', 0);
$pdf->Cell(30, 4.5, ' Médio = ' . narFmtNum($nar['calado_leve_medio'], 3, ' m'), 'R', 0);

$pdf->Cell(30, 4.5, ' AV = ' . narFmtNum($nar['calado_carregado_av'], 3, ' m'), '', 0);
$pdf->Cell(30, 4.5, ' AR = ' . narFmtNum($nar['calado_carregado_ar'], 3, ' m'), '', 0);
$pdf->Cell(30, 4.5, ' Médio = ' . narFmtNum($nar['calado_carregado_medio'], 3, ' m'), 'R', 1);
$pdf->Cell(180, 0, '', 'T', 1);

$pdf->Ln(2);

// --- 3. Tripulantes e Passageiros ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 3. Tripulantes e Passageiros', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(110, $hLinha, ' Número de Tripulantes =', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(70, $hLinha, ' ' . (int)$nar['numero_tripulantes'], 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(110, $hLinha, ' N1 (Nº. de Passageiros em camarotes que tenham menos de oito beliches) =', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(70, $hLinha, ' ' . (int)$nar['n1_passageiros_camarotes'], 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(110, $hLinha, ' N2 (Nº. dos demais Passageiros) =', 'LTB', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(70, $hLinha, ' ' . (int)$nar['n2_demais_passageiros'], 'TRB', 1);

$pdf->Ln(2);

// --- 4. Características Calculadas ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 4. Características Calculadas', 1, 1, 'L', true);

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(180, 5, ' Deslocamentos:', 'LTR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(60, 4.5, ' Carregado: ' . narFmtNum($nar['deslocamento_carregado'], 3, ' t'), 'L', 0);
$pdf->Cell(60, 4.5, ' Leve: ' . narFmtNum($nar['deslocamento_leve'], 3, ' t'), '', 0);
$pdf->Cell(60, 4.5, ' Porte Bruto: ' . narFmtNum($nar['porte_bruto'], 3, ' t'), 'R', 1);

$pdf->Cell(120, $hLinha, ' Espaços Fechados abaixo do Convés Superior', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, $hLinha, narFmtNum($nar['espacos_fechados_abaixo_conves'], 2, ' m³'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, $hLinha, ' Espaços Fechados acima do Convés Superior', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, $hLinha, narFmtNum($nar['espacos_fechados_acima_conves'], 2, ' m³'), 'TR', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, $hLinha, ' Espaços Excluídos', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, $hLinha, narFmtNum($nar['espacos_excluidos'], 2, ' m³'), 'TR', 1);

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(120, $hLinha, ' V (Volume Total dos Espaços Fechados)', 'LT', 0);
$pdf->Cell(60, $hLinha, narFmtNum($nar['volume_total_fechado_v'], 2, ' m³'), 'TR', 1);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(13, 73, 65);
$pdf->Cell(120, 6, ' ARQUEAÇÃO BRUTA (AB)', 'LTB', 0);
$pdf->Cell(60, 6, ' AB = ' . (int)$nar['arqueacao_bruta_ab'], 'TRB', 1);

$pdf->SetTextColor(15, 23, 42);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, $hLinha, ' Vc (Volume dos Espaços de Carga)', 'LT', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, $hLinha, narFmtNum($nar['volume_espacos_carga_vc'], 2, ' m³'), 'TR', 1);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(3, 105, 161);
$pdf->Cell(120, 6, ' ARQUEAÇÃO LÍQUIDA (AL)', 'LTB', 0);
$pdf->Cell(60, 6, ' AL = ' . (int)$nar['arqueacao_liquida_al'], 'TRB', 1);
$pdf->SetTextColor(15, 23, 42);

// Bloco de Assinatura da Página 1
$pdf->SetY(248);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(180, 5, $nar['local_emissao'] . ', em ' . narDataExtenso($nar['data_emissao']) . '.', 0, 1, 'L');

$pdf->SetY(254);
$pdf->SetX(110);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(85, 4.5, $nar['assinante_nome'] ?: 'JERSON DA SILVA ALMEIDA', 0, 1, 'C');
$pdf->SetX(110);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(85, 4, $nar['assinante_titulo'] ?: 'TECNÓLOGO NAVAL', 0, 1, 'C');
$pdf->SetX(110);
$pdf->Cell(85, 4, 'CREA: ' . ($nar['assinante_registro'] ?: '22181-AM'), 0, 1, 'C');


// =========================================================================
// PÁGINA 2: MEMÓRIA DE CÁLCULO (AB / AL) E 4. OBSERVAÇÕES / NOTAS
// =========================================================================
$pdf->AddPage();
$pdf->SetTextColor(15, 23, 42);
$pdf->SetDrawColor(148, 163, 184);
$pdf->SetLineWidth(0.2);

// --- 5. Arqueação Bruta ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 5. Arqueação Bruta', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(180, 4.5, ' a) Identifique os Espaços Fechados; (Ver Anexo)', 'LR', 1);
$pdf->Cell(180, 4.5, ' b) Identifique os Espaços Excluídos;', 'LR', 1);
$pdf->Cell(120, 4.5, ' c) Espaços Fechados abaixo do Convés Superior =', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.5, narFmtNum($nar['espacos_fechados_abaixo_conves'], 2, ' m³'), 'R', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, 4.5, ' d) Espaços Fechados acima do Convés Superior =', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.5, narFmtNum($nar['espacos_fechados_acima_conves'], 2, ' m³'), 'R', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, 4.5, ' e) Espaços Excluídos =', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.5, narFmtNum($nar['espacos_excluidos'], 2, ' m³'), 'R', 1);

$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(120, 4.5, ' f) Espaços Fechados (V) =', 'L', 0);
$pdf->Cell(60, 4.5, narFmtNum($nar['volume_total_fechado_v'], 2, ' m³'), 'R', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, 4.5, ' g) Com V - obtém-se o valor de K1:', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.5, 'K1 = ' . narFmtNum($nar['coeficiente_k1'], 4), 'R', 1);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(13, 73, 65);
$pdf->Cell(120, 5.5, ' h) AB = K1 x V', 'LTB', 0);
$pdf->Cell(60, 5.5, 'AB = ' . (int)$nar['arqueacao_bruta_ab'], 'TRB', 1);
$pdf->SetTextColor(15, 23, 42);

$pdf->Ln(2);

// --- 6. Arqueação Líquida ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 6. Arqueação Líquida', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(180, 4.2, ' a) Identifique os Espaços de Carga; (Ver Anexo)', 'LR', 1);
$pdf->Cell(120, 4.2, ' b) Espaços de Carga (Vc) =', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.2, narFmtNum($nar['volume_espacos_carga_vc'], 2, ' m³'), 'R', 1);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(120, 4.2, ' c) Com Vc - obtém-se o valor de K2:', 'L', 0);
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->Cell(60, 4.2, 'K2 = ' . narFmtNum($nar['coeficiente_k2'], 4), 'R', 1);

$nTotal = (int)$nar['n1_passageiros_camarotes'] + (int)$nar['n2_demais_passageiros'];
$isNulo = $nTotal < 13;
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(180, 4.2, ' d) N1 + N2 = ' . $nTotal, 'LR', 1);
$pdf->Cell(180, 4.2, '    (' . ($isNulo ? '  X  ' : '     ') . ') menor que 13, logo N1 e N2 nulos - Utilizar = 0', 'LR', 1);
$pdf->Cell(180, 4.2, '    (' . (!$isNulo ? '  X  ' : '     ') . ') maior ou igual a 13, usar N1 e N2', 'LR', 1);

// Cálculos das Expressões I, II, III
$caladoCalc = (float)($nar['calado_carregado_medio'] ?: $nar['calado_leve_medio'] ?: 1.0);
$pontalCalc = (float)($nar['pontal_moldado_p'] ?: 1.0);
$exp1 = pow((4 * $caladoCalc) / (3 * $pontalCalc), 2);
$exp1Usar = $exp1 > 1.0 ? 1.0 : $exp1;
$isExp1Maior = $exp1 > 1.0;

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(180, 4.2, ' e) Cálculo das expressões das Notas:', 'LR', 1);
$pdf->Cell(180, 4.2, '    I) (4H / 3P)² = ' . narFmtNum($exp1, 2) . '    Utilizar = ' . narFmtNum($exp1Usar, 2), 'LR', 1);
$pdf->Cell(180, 4.2, '       (' . (!$isExp1Maior ? '  X  ' : '     ') . ') Valor calculado menor ou igual a 1, usar o valor calculado', 'LR', 1);
$pdf->Cell(180, 4.2, '       (' . ($isExp1Maior ? '  X  ' : '     ') . ') Valor calculado maior do que 1, usar a unidade', 'LR', 1);

$k2 = (float)$nar['coeficiente_k2'];
$vc = (float)$nar['volume_espacos_carga_vc'];
$ab = (int)$nar['arqueacao_bruta_ab'];
$termo2 = $k2 * $vc * $exp1Usar;
$limite25 = 0.25 * $ab;
$usarLimite25 = $termo2 <= $limite25;
$termo2Usar = $usarLimite25 ? $limite25 : $termo2;

$pdf->Cell(180, 4.2, '    II) K2Vc (4H / 3P)² = ' . narFmtNum($termo2, 2) . '    Utilizar = ' . narFmtNum($termo2Usar, 2), 'LR', 1);
$pdf->Cell(180, 4.2, '        (' . ($usarLimite25 ? '  X  ' : '     ') . ') Valor calculado menor ou igual a 0,25 AB, usar 0,25 AB = ' . narFmtNum($limite25, 2), 'LR', 1);
$pdf->Cell(180, 4.2, '        (' . (!$usarLimite25 ? '  X  ' : '     ') . ') Valor calculado maior do que 0,25 AB, usar o valor calculado', 'LR', 1);

$limite30 = 0.30 * $ab;
$pdf->Cell(180, 4.2, '    III) 0,30 AB = ' . narFmtNum($limite30, 2), 'LR', 1);

// AL Final
$fatorN = (!$isNulo) ? (1.25 * ($ab + 10000) / 10000 * ((int)$nar['n1_passageiros_camarotes'] + ((int)$nar['n2_demais_passageiros'] / 10.0))) : 0.0;
$alCalc = $termo2Usar + $fatorN;
$alMinimaUsada = $alCalc < $limite30;

$pdf->Cell(180, 4.2, ' f) Cálculo da Arqueação Líquida: AL = K2Vc (4H/3P)² + 1,25 x (AB + 10.000)/10.000 x (N1 + N2/10)', 'LR', 1);
$pdf->Cell(180, 4.2, '    AL calculada = ' . narFmtNum($alCalc, 2), 'LR', 1);
$pdf->Cell(180, 4.2, ' g) Comparar com 30% da AB:', 'LR', 1);
$pdf->Cell(180, 4.2, '    (' . (!$alMinimaUsada ? '  X  ' : '     ') . ') AL calculada maior ou igual a 30% da AB, usar o valor calculado.', 'LR', 1);
$pdf->Cell(180, 4.2, '    (' . ($alMinimaUsada ? '  X  ' : '     ') . ') AL calculada menor que 30% da AB, usar AL = 30% AB.', 'LR', 1);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(3, 105, 161);
$pdf->Cell(180, 5, '    AL OFICIAL = ' . (int)$nar['arqueacao_liquida_al'], 'LRB', 1);
$pdf->SetTextColor(15, 23, 42);

$pdf->Ln(2);

// --- 4. OBSERVAÇÕES / NOTAS TÉCNICAS (CONFORME MODELO RODRIGO BRUNO E AMAZON) ---
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' 4. Observações / NOTAS Técnicas', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7.5);
$obsTexto = trim($nar['observacoes_notas'] ?? '');
if ($obsTexto === '') {
    $obsTexto = '- x - x - x - x -';
}
$pdf->MultiCell(180, 4, $obsTexto, 1, 'J', false, 1);

// Bloco de Assinatura da Página 2
$pdf->SetY(248);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(180, 5, $nar['local_emissao'] . ', em ' . narDataExtenso($nar['data_emissao']) . '.', 0, 1, 'L');

$pdf->SetY(254);
$pdf->SetX(110);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(85, 4.5, $nar['assinante_nome'] ?: 'JERSON DA SILVA ALMEIDA', 0, 1, 'C');
$pdf->SetX(110);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(85, 4, $nar['assinante_titulo'] ?: 'TECNÓLOGO NAVAL', 0, 1, 'C');
$pdf->SetX(110);
$pdf->Cell(85, 4, 'CREA: ' . ($nar['assinante_registro'] ?: '22181-AM'), 0, 1, 'C');


// =========================================================================
// PÁGINA 3: ANEXO - ESPAÇOS INCLUÍDOS NA ARQUEAÇÃO (VOLUMES E CASARIAS)
// =========================================================================
$pdf->AddPage();
$pdf->SetTextColor(15, 23, 42);
$pdf->SetDrawColor(148, 163, 184);
$pdf->SetLineWidth(0.2);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(180, 6, 'ANEXO - Espaços Incluídos na Arqueação', 0, 1, 'C');
$pdf->Ln(1);

// A) Espaços Fechados
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(180, 5.5, ' A)  Espaços Fechados', 1, 1, 'L', true);

// a.1) Volumes Abaixo do Convés Principal
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(180, 5, ' a.1) Volumes Abaixo do Convés Principal:', 'LR', 1);

$volumesAbaixo = !empty($nar['volumes_abaixo_conves_json']) ? json_decode($nar['volumes_abaixo_conves_json'], true) : [];
if (empty($volumesAbaixo)) {
    $volumesAbaixo = [['descricao' => 'Volume do casco mais tosamento', 'volume' => $nar['espacos_fechados_abaixo_conves']]];
}

$romanos = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii'];
$pdf->SetFont('helvetica', '', 7.5);
foreach ($volumesAbaixo as $idx => $vItem) {
    $r = $romanos[$idx] ?? ($idx + 1);
    $desc = $vItem['descricao'] ?: 'Volume do casco';
    $vol = (float)($vItem['volume'] ?? 0);
    $pdf->Cell(15, 4.5, ' ' . $r . ')', 'L', 0);
    $pdf->Cell(115, 4.5, $desc, 0, 0);
    $pdf->Cell(20, 4.5, 'Vi =', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell(30, 4.5, narFmtNum($vol, 2, ' m³'), 'R', 1, 'R');
    $pdf->SetFont('helvetica', '', 7.5);
}

// Preencher linhas vazias se tiver menos de 3
for ($k = count($volumesAbaixo); $k < 3; $k++) {
    $r = $romanos[$k];
    $pdf->Cell(15, 4.5, ' ' . $r . ')', 'L', 0);
    $pdf->Cell(115, 4.5, '- x -', 0, 0);
    $pdf->Cell(20, 4.5, 'V' . $r . ' =', 0, 0, 'R');
    $pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 5, ' TOTAL VI', 'LTB', 0);
$pdf->Cell(50, 5, narFmtNum($nar['espacos_fechados_abaixo_conves'], 2, ' m³'), 'TRB', 1, 'R');

$pdf->SetFont('helvetica', 'I', 7.5);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(180, 5, ' Obs.: ' . ($nar['metodo_obtencao_abaixo'] ?: 'Volume obtido com a utilização de curvas hidrostáticas.'), 'LRB', 1);
$pdf->SetTextColor(15, 23, 42);

$pdf->Ln(2);

// a.2) Volumes Acima do Convés Superior (Casarias)
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(180, 5, ' a.2) Volumes Acima do Convés Superior (Casarias):', 'LR', 1);

$volumesAcima = !empty($nar['volumes_acima_conves_json']) ? json_decode($nar['volumes_acima_conves_json'], true) : [];
if (empty($volumesAcima)) {
    $volumesAcima = [
        ['descricao' => 'Casaria do Convés Principal Completa.', 'volume' => 0],
        ['descricao' => 'Casaria do Convés Superior.', 'volume' => 0],
        ['descricao' => 'Casaria do convés intermediário', 'volume' => 0],
        ['descricao' => 'Casaria do convés comando.', 'volume' => 0]
    ];
}

$pdf->SetFont('helvetica', '', 7.5);
foreach ($volumesAcima as $idx => $vItem) {
    $r = $romanos[$idx] ?? ($idx + 1);
    $desc = $vItem['descricao'] ?: 'Casaria';
    $vol = (float)($vItem['volume'] ?? 0);
    $pdf->Cell(15, 4.5, ' ' . $r . ')', 'L', 0);
    $pdf->Cell(115, 4.5, $desc, 0, 0);
    $pdf->Cell(20, 4.5, 'V' . $r . ' =', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell(30, 4.5, narFmtNum($vol, 2, ' m³'), 'R', 1, 'R');
    $pdf->SetFont('helvetica', '', 7.5);
}

// Completar até 6 se necessário
for ($k = count($volumesAcima); $k < 6; $k++) {
    $r = $romanos[$k];
    $pdf->Cell(15, 4.5, ' ' . $r . ')', 'L', 0);
    $pdf->Cell(115, 4.5, '- x -', 0, 0);
    $pdf->Cell(20, 4.5, 'V' . $r . ' =', 0, 0, 'R');
    $pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 5, ' TOTAL VII', 'LTB', 0);
$pdf->Cell(50, 5, narFmtNum($nar['espacos_fechados_acima_conves'], 2, ' m³'), 'TRB', 1, 'R');

$pdf->SetFont('helvetica', 'I', 7.5);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(180, 5, ' Obs.: ' . ($nar['metodo_obtencao_acima'] ?: 'Volume obtido com a utilização de formas geométricas.'), 'LRB', 1);
$pdf->SetTextColor(15, 23, 42);

// Volume Total VT
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetTextColor(13, 73, 65);
$pdf->Cell(130, 5.5, ' Volume Total dos Espaços Fechados: VT = VI + VII', 'LTB', 0);
$pdf->Cell(50, 5.5, narFmtNum($nar['volume_total_fechado_v'], 2, ' m³'), 'TRB', 1, 'R');
$pdf->SetTextColor(15, 23, 42);

$pdf->Ln(2);

// B) Espaços Excluídos
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(180, 5, ' B)  Espaços Excluídos:', 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(15, 4.5, ' i)', 'L', 0);
$pdf->Cell(115, 4.5, '- x -', 0, 0);
$pdf->Cell(20, 4.5, 'Vi =', 0, 0, 'R');
$pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');

$pdf->Cell(15, 4.5, ' ii)', 'L', 0);
$pdf->Cell(115, 4.5, '- x -', 0, 0);
$pdf->Cell(20, 4.5, 'Vii =', 0, 0, 'R');
$pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 5, ' TOTAL VB', 'LTB', 0);
$pdf->Cell(50, 5, narFmtNum($nar['espacos_excluidos'], 2, ' m³'), 'TRB', 1, 'R');

$pdf->Ln(2);

// C) Espaços de Carga
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(180, 5, ' C)  Espaços de Carga:', 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(15, 4.5, ' i)', 'L', 0);
$pdf->Cell(115, 4.5, '- x -', 0, 0);
$pdf->Cell(20, 4.5, 'Vi =', 0, 0, 'R');
$pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');

$pdf->Cell(15, 4.5, ' ii)', 'L', 0);
$pdf->Cell(115, 4.5, '- x -', 0, 0);
$pdf->Cell(20, 4.5, 'Vii =', 0, 0, 'R');
$pdf->Cell(30, 4.5, '0,00 m³', 'R', 1, 'R');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(130, 5, ' TOTAL VC', 'LTB', 0);
$pdf->Cell(50, 5, narFmtNum($nar['volume_espacos_carga_vc'], 2, ' m³'), 'TRB', 1, 'R');

// Bloco de Assinatura da Página 3
$pdf->SetY(248);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(180, 5, $nar['local_emissao'] . ', em ' . narDataExtenso($nar['data_emissao']) . '.', 0, 1, 'L');

$pdf->SetY(254);
$pdf->SetX(110);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell(85, 4.5, $nar['assinante_nome'] ?: 'JERSON DA SILVA ALMEIDA', 0, 1, 'C');
$pdf->SetX(110);
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Cell(85, 4, $nar['assinante_titulo'] ?: 'TECNÓLOGO NAVAL', 0, 1, 'C');
$pdf->SetX(110);
$pdf->Cell(85, 4, 'CREA: ' . ($nar['assinante_registro'] ?: '22181-AM'), 0, 1, 'C');

// Saída do PDF
if (isset($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
    return;
} else {
    $nomeArquivo = 'AM-NAR-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $nar['numero']) . '.pdf';
    $pdf->Output($nomeArquivo, 'I');
    exit;
}
