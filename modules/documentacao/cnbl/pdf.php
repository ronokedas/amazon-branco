<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * PDF fiel ao modelo oficial do Certificado Nacional de Borda Livre.
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_cnbl WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die('Certificado não encontrado.');
    }
    $id = $row['id'];
} elseif (empty($id)) {
    die('ID ou token não informado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_cnbl WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) {
    die('Certificado não encontrado.');
}

$stmt_revisao_conv = $pdo->prepare("SELECT MAX(atualizado_em) FROM cert_convalidacoes WHERE certificado_id = :id AND tipo_certificado = 'CNBL'");
$stmt_revisao_conv->execute([':id' => $id]);
$convalidacoes_revisadas_em = $stmt_revisao_conv->fetchColumn();
$convalidacoes_revisadas = !empty($convalidacoes_revisadas_em)
    && !empty($c['assinatura_em'])
    && strtotime($convalidacoes_revisadas_em) > strtotime($c['assinatura_em']);

// Depois da aprovação, o PDF oficial é o artefato imutável armazenado.
// Não execute novamente o gerador-base, pois ele não contém o bloco de auditoria.
if (!isset($salvar_pdf_caminho) && !$convalidacoes_revisadas && (int)($c['assinado'] ?? 0) === 1 && !empty($c['caminho_arquivo_pdf'])) {
    $base_sistema = realpath(__DIR__ . '/../../../');
    $caminho_fisico = realpath(__DIR__ . '/../../../' . ltrim((string)$c['caminho_arquivo_pdf'], '/\\'));

    $arquivo_dentro_do_sistema = $base_sistema !== false
        && $caminho_fisico !== false
        && str_starts_with(strtolower($caminho_fisico), strtolower($base_sistema . DIRECTORY_SEPARATOR));

    if (!$arquivo_dentro_do_sistema || !is_file($caminho_fisico)) {
        http_response_code(409);
        die('O PDF oficial assinado não foi encontrado. Solicite a reemissão do documento.');
    }

    $hash_esperado = strtolower(trim((string)($c['hash_arquivo_pdf'] ?? '')));
    $hash_atual = hash_file('sha256', $caminho_fisico);
    if ($hash_esperado !== '' && !hash_equals($hash_esperado, $hash_atual)) {
        http_response_code(409);
        die('A verificação de integridade do PDF oficial falhou.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($caminho_fisico) . '"');
    header('Content-Length: ' . filesize($caminho_fisico));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($caminho_fisico);
    exit;
}

$tipo_embarcacao_convalidacoes = $c['tipo_embarcacao'] ?? '';
if (!empty($c['vistoria_id'])) {
    $stmtTipoConv = $pdo->prepare("
        SELECT COALESCE(te.nome, e.tipo_embarcacao) AS tipo_embarcacao_real
        FROM vistorias v
        JOIN agendamentos a ON v.agendamento_id = a.id
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN tipos_embarcacao te ON e.tipo_embarcacao_id = te.id
        WHERE v.id = :vistoria_id
        LIMIT 1
    ");
    $stmtTipoConv->execute([':vistoria_id' => $c['vistoria_id']]);
    $tipoEmbarcacaoReal = $stmtTipoConv->fetchColumn();
    if (!empty($tipoEmbarcacaoReal)) {
        $tipo_embarcacao_convalidacoes = $tipoEmbarcacaoReal;
    }
}

$stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNBL' ORDER BY id");
$stmt_conv->execute([':cert_id' => $id]);
$convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

function cnblText($valor, string $padrao = ''): string
{
    $valor = trim((string)$valor);
    return $valor !== '' ? $valor : $padrao;
}

function cnblPdfText($valor): string
{
    return strip_tags(html_entity_decode((string)$valor, ENT_QUOTES, 'UTF-8'));
}

function cnblDataBR($data): string
{
    if (empty($data)) {
        return '';
    }
    return date('d/m/Y', strtotime($data));
}

function cnblDataExtenso($data): string
{
    if (empty($data)) {
        return '__ de __________ de ____';
    }

    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function cnblMedida($valor, string $padrao = ''): string
{
    $valor = trim((string)$valor);
    if ($valor === '') {
        return $padrao;
    }
    if (preg_match('/\bmm\b/i', $valor)) {
        return $valor;
    }
    return preg_match('/^[0-9]+([,.][0-9]+)?$/', $valor) ? $valor . ' mm' : $valor;
}

function cnblNorm($valor): string
{
    $valor = trim((string)$valor);
    $map = [
        'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','á'=>'A','à'=>'A','â'=>'A','ã'=>'A','ä'=>'A',
        'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','é'=>'E','è'=>'E','ê'=>'E','ë'=>'E',
        'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','í'=>'I','ì'=>'I','î'=>'I','ï'=>'I',
        'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','ó'=>'O','ò'=>'O','ô'=>'O','õ'=>'O','ö'=>'O',
        'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','ú'=>'U','ù'=>'U','û'=>'U','ü'=>'U',
        'Ç'=>'C','ç'=>'C'
    ];
    return mb_strtoupper(strtr($valor, $map), 'UTF-8');
}

function cnblTipoLetra(array $c): string
{
    $tipo = cnblNorm($c['tipo_embarcacao'] ?? '');
    $atividade = cnblNorm($c['atividades_servicos'] ?? '');

    if (preg_match('/^[ABCDE]$/', $tipo)) {
        return $tipo;
    }
    if (str_contains($tipo . ' ' . $atividade, 'CARGA GERAL')) {
        return 'A';
    }
    if (str_contains($tipo . ' ' . $atividade, 'TANQUE')) {
        return 'B';
    }
    if (str_contains($tipo . ' ' . $atividade, 'GRANEL')) {
        return 'C';
    }
    if (str_contains($tipo . ' ' . $atividade, 'PASSAGEIRO')) {
        return 'D';
    }
    if (str_contains($tipo . ' ' . $atividade, 'EMPURRADOR') || str_contains($tipo . ' ' . $atividade, 'EMPURRADO')) {
        return 'E';
    }
    return '';
}

function cnblAreaNumero($area): string
{
    $area = cnblNorm($area);
    if (str_contains($area, 'AREA 1')) {
        return '1';
    }
    if (str_contains($area, 'AREA 2')) {
        return '2';
    }
    return '';
}

function cnblImgOk(string $path): bool
{
    return file_exists($path) && filesize($path) > 100;
}

if (!class_exists('CertificadoCNBL')) {
    class CertificadoCNBL extends TCPDF
    {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new CertificadoCNBL('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CNBL - ' . $c['numero']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);

$tipo_certificado = cnblText($c['tipo'] ?? '', 'Condicional');
$tipo_label = '(' . mb_strtoupper($tipo_certificado, 'UTF-8') . ')';
$numero_limpo = str_replace('AM-CNBL-', '', (string)$c['numero']);
$porto = cnblText($c['porto_inscricao'] ?? '', cnblText($c['local_emissao'] ?? ''));
$tipo_letra = cnblTipoLetra($c);
$area_numero = cnblAreaNumero($c['area_navegacao'] ?? '');
$numero_disco = $area_numero !== '' ? $area_numero : '2';
$marca_disco_codigo = preg_replace('/[^A-Z0-9]/', '', cnblNorm($c['marca_disco'] ?? 'AM'));
$marca_disco_codigo = strlen($marca_disco_codigo) >= 2 ? $marca_disco_codigo : 'AM';
$marca_disco_esquerda = mb_substr($marca_disco_codigo, 0, 1, 'UTF-8');
$marca_disco_direita = mb_substr($marca_disco_codigo, 1, 1, 'UTF-8');

$medCentroDisco = cnblMedida($c['centro_disco_situado'] ?? '', '___ mm');
$medMarcaArea1 = cnblMedida($c['marca_linha_carga_area1'] ?? '', '___ mm');
$medMarcaArea2 = cnblMedida($c['marca_linha_carga_area2'] ?? '', '___ mm');
$medAresta = cnblMedida($c['aresta_superior_linha_conves'] ?? '', '___ mm');
$medBicoProa = cnblMedida($c['dist_linha_conves_bico_proa'] ?? '', '___ mm');
$medAguaSalgada = cnblMedida($c['acrescimo_agua_salgada'] ?? '', '___ mm');

// =========================
// PÁGINA 1
// =========================
$pdf->AddPage();
$pdf->SetLineWidth(0.35);

// Número do certificado
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Rect(10, 14, 73, 5);
$pdf->SetXY(10, 14.1);
$pdf->Cell(73, 5, 'CERTIFICADO AM-CNBL - ' . cnblPdfText($numero_limpo), 0, 0, 'C');

// Brasão
$brasao = __DIR__ . '/../../../assets/img/brasao.png';
if (cnblImgOk($brasao)) {
    $pdf->Image($brasao, 25, 35, 31, 31, 'PNG', '', '', true, 150);
}

// Cabeçalho central
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(58, 26);
$pdf->Cell(110, 6, 'CERTIFICADO NACIONAL DE BORDA LIVRE', 0, 1, 'C');
$pdf->Line(67, 32.2, 160, 32.2);
$pdf->SetX(58);
$pdf->Cell(110, 6, 'PARA NAVEGAÇÃO INTERIOR', 0, 1, 'C');
$pdf->Line(77, 38.2, 150, 38.2);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(58);
$pdf->Cell(110, 5, '(EMITIDO DE ACORDO COM A NORMAM-202)', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetXY(58, 48);
$pdf->Cell(110, 6, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdf->SetX(58);
$pdf->Cell(110, 6, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->SetX(58);
$pdf->Cell(110, 6, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(58, 70);
$pdf->Cell(110, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');

$pdf->SetFont('helvetica', 'BI', 10);
$pdf->SetXY(164, 31);
$pdf->Cell(35, 5, $tipo_label, 0, 0, 'L');

// Tabela principal
$x = 10; $y = 79; $w = 190;
$cols = [80, 37, 36, 37];
$pdf->SetLineWidth(0.45);
$pdf->Rect($x, $y, $w, 21);
$pdf->Line($x, $y + 8, $x + $w, $y + 8);
$cx = $x;
for ($i = 0; $i < 3; $i++) {
    $cx += $cols[$i];
    $pdf->Line($cx, $y, $cx, $y + 21);
}
$pdf->SetFont('helvetica', '', 10);
$labels = ['Nome da Embarcação', 'N° de Inscrição', 'Porto de Inscrição', 'Arqueação Bruta'];
$cx = $x;
foreach ($labels as $i => $label) {
    $pdf->SetXY($cx, $y + 1.5);
    $pdf->Cell($cols[$i], 5, $label, 0, 0, 'C');
    $cx += $cols[$i];
}
$pdf->SetFont('helvetica', 'B', 10);
$values = [
    '"' . mb_strtoupper(cnblText($c['nome_embarcacao'] ?? '', 'Não informado'), 'UTF-8') . '"',
    cnblText($c['numero_inscricao'] ?? '', 'Não Fornecido'),
    $porto,
    cnblText($c['arqueacao_bruta'] ?? '', 'Não Fornecido')
];
$cx = $x;
foreach ($values as $i => $value) {
    $pdf->SetXY($cx + 1, $y + 11.5);
    $pdf->Cell($cols[$i] - 2, 5, cnblPdfText($value), 0, 0, 'C');
    $cx += $cols[$i];
}

// Quadro atividade/tipo/área
$y = 104;
$pdf->Rect($x, $y, $w, 18);
$pdf->Line($x, $y + 6, $x + $w, $y + 6);
$pdf->Line($x, $y + 12, $x + $w, $y + 12);
$labelW = 65;
$pdf->Line($x + $labelW, $y, $x + $labelW, $y + 18);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY($x + 1, $y + 0.8);
$pdf->Cell($labelW - 2, 5, 'Atividade ou Serviço:', 0, 0, 'L');
$pdf->SetXY($x + $labelW + 1, $y + 0.8);
$pdf->Cell($w - $labelW - 2, 5, cnblPdfText(cnblText($c['atividades_servicos'] ?? '')), 0, 0, 'L');
$pdf->SetXY($x + 1, $y + 6.8);
$pdf->Cell($labelW - 2, 5, 'Tipo de Embarcação:', 0, 0, 'L');

$tipoX = $x + $labelW;
$tipoCellW = ($w - $labelW) / 5;
for ($i = 1; $i < 5; $i++) {
    $pdf->Line($tipoX + ($tipoCellW * $i), $y + 6, $tipoX + ($tipoCellW * $i), $y + 12);
}
$pdf->SetFont('helvetica', 'B', 10);
foreach (['A', 'B', 'C', 'D', 'E'] as $i => $letra) {
    $pdf->SetXY($tipoX + ($tipoCellW * $i), $y + 6.8);
    $texto = ($letra === $tipo_letra ? 'X   ' : '    ') . $letra;
    $pdf->Cell($tipoCellW, 5, $texto, 0, 0, 'C');
}

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY($x + 1, $y + 12.8);
$pdf->Cell($labelW - 2, 5, 'Área de Navegação Interior:', 0, 0, 'L');
$areaX = $x + $labelW;
$areaW = ($w - $labelW) / 2;
$pdf->Line($areaX + $areaW, $y + 12, $areaX + $areaW, $y + 18);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($areaX, $y + 12.8);
$pdf->Cell($areaW, 5, ($area_numero === '1' ? 'X   ' : '    ') . 'Área 1', 0, 0, 'C');
$pdf->SetXY($areaX + $areaW, $y + 12.8);
$pdf->Cell($areaW, 5, ($area_numero === '2' ? 'X   ' : '    ') . 'Área 2', 0, 0, 'C');

// Medições
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(11, 127);
$pdf->Cell(120, 5, 'DISTÂNCIA DA PARTE SUPERIOR DA LINHA DO CONVÉS ATÉ:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$measureRows = [
    ['CENTRO DO DISCO:', $medCentroDisco],
    ['MARCA DA LINHA DE CARGA PARA A ÁREA 1:', $medMarcaArea1],
    ['MARCA DA LINHA DE CARGA PARA A ÁREA 2:', $medMarcaArea2],
];
$yy = 136;
foreach ($measureRows as [$label, $value]) {
    $pdf->SetXY(35, $yy);
    $pdf->Cell(70, 5, $label, 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(32, 5, cnblPdfText($value), 0, 0, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $yy += 6;
}

// Diagrama oficial do Disco de Plimsoll
$diagramY = 164;
$diagramaOficial = __DIR__ . '/../../../img/imagem-pdf.png';
if (cnblImgOk($diagramaOficial)) {
    $imgX = 65;
    $imgY = $diagramY - 1;
    $imgW = 88;
    $imgH = 26.5;
    $pdf->Image($diagramaOficial, $imgX, $imgY, $imgW, $imgH, 'PNG', '', '', true, 150);

    // A imagem oficial é usada como base, mas os identificadores do disco são
    // variáveis: letras da entidade certificadora e número da área aplicável.
    $scaleX = $imgW / 574;
    $scaleY = $imgH / 173;
    $drawDiscLabel = function (float $px, float $py, float $pw, float $ph, string $text, float $fontSize) use ($pdf, $imgX, $imgY, $scaleX, $scaleY) {
        $x = $imgX + ($px * $scaleX);
        $y = $imgY + ($py * $scaleY);
        $w = $pw * $scaleX;
        $h = $ph * $scaleY;

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x - 0.6, $y - 0.3, $w + 1.2, $h + 0.9, 'F');
        $pdf->SetFont('helvetica', 'B', $fontSize);
        $pdf->SetXY($x - 0.7, $y - 1.1);
        $pdf->Cell($w + 1.4, $h + 2.0, cnblPdfText($text), 0, 0, 'C');
    };

    $drawDiscLabel(27, 70, 21, 22, $marca_disco_esquerda, 13);
    $drawDiscLabel(111, 60, 17, 26, $numero_disco, 13.5);
    $drawDiscLabel(193, 70, 22, 22, $marca_disco_direita, 13);

    $pdf->SetFillColor(255, 255, 255);
} else {
    $pdf->SetLineWidth(1.0);
    $pdf->Line(68, $diagramY, 91, $diagramY);
    $pdf->SetLineWidth(0.8);
    $pdf->Circle(79.5, $diagramY + 12.5, 8.7);
    $pdf->Line(66, $diagramY + 12.5, 90.6, $diagramY + 12.5);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Text(64.2, $diagramY + 6.3, cnblPdfText($marca_disco_esquerda));
    $pdf->Text(90.3, $diagramY + 6.3, cnblPdfText($marca_disco_direita));
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetXY(74.7, $diagramY + 5.8);
    $pdf->Cell(9, 7, cnblPdfText($numero_disco), 0, 0, 'C');
}

// Texto técnico com valores sublinhados
$baseY = 196;

$linhaAFont = 8.8;
$linhaAInicio = 'A ARESTA SUP. DA LINHA DO CONVÉS ESTÁ SITUADA A';
$linhaAFim = 'DA FACE SUPERIOR DO CONVÉS AO LADO';
$linhaAX = 10;
$linhaAValorW = 30;

$pdf->SetFont('helvetica', '', $linhaAFont);
$linhaAInicioW = $pdf->GetStringWidth($linhaAInicio) + 1.5;
$pdf->SetXY($linhaAX, $baseY);
$pdf->Cell($linhaAInicioW, 5, $linhaAInicio, 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', $linhaAFont);
$pdf->Cell($linhaAValorW, 5, cnblPdfText($medAresta), 'B', 0, 'C');
$pdf->SetFont('helvetica', '', $linhaAFont);
$pdf->Cell(200 - ($linhaAX + $linhaAInicioW + $linhaAValorW), 5, $linhaAFim, 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9.3);
$pdf->SetXY(11, $baseY + 7);
$pdf->Cell(68, 5, 'O CENTRO DO DISCO ESTÁ SITUADO A', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9.3);
$pdf->Cell(34, 5, cnblPdfText($medBicoProa), 'B', 0, 'C');
$pdf->SetFont('helvetica', '', 9.3);
$pdf->Cell(55, 5, 'DO BICO DE PROA.', 0, 1, 'L');

$pdf->SetXY(11, $baseY + 14);
$pdf->Cell(102, 5, 'ACRÉSCIMO PARA NAVEGAÇÃO EM ÁGUA SALGADA', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9.3);
$pdf->SetXY(113, $baseY + 14);
$pdf->Cell(34, 5, cnblPdfText($medAguaSalgada), 'B', 0, 'C');
$pdf->SetFont('helvetica', '', 9.3);
$pdf->SetXY(148, $baseY + 14);
$pdf->Cell(52, 5, 'ABAIXO DO DISCO DE PLIMSOLL.', 0, 1, 'L');

// Texto de certificação
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(10, 221);
$pdf->MultiCell(190, 10, 'O PRESENTE CERTIFICADO É EXPEDIDO PARA ATESTAR QUE A EMBARCAÇÃO FOI VISTORIADA E QUE A SUA BORDA LIVRE E LINHA DE CARGA INDICADAS FORAM APOSTAS E SERÃO CONTROLADAS CONFORME AS DISPOSIÇÕES EM VIGOR.', 0, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(10, 238);
$pdf->Cell(190, 6, 'VÁLIDO até: ' . cnblDataExtenso($c['data_validade']), 0, 1, 'C');
$pdf->SetXY(10, 248);
$pdf->Cell(190, 6, 'Expedido em ' . cnblPdfText(cnblText($c['local_emissao'] ?? '', 'Belém-PA')) . ', em ' . cnblDataExtenso($c['data_emissao']), 0, 1, 'C');

// =========================
// PÁGINA 2
// =========================
$pdf->AddPage();
$pdf->SetLineWidth(0.45);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetXY(10, 17);
$pdf->Cell(190, 7, 'CONVALIDAÇÕES', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(10, 33);
$pdf->MultiCell(190, 12, 'Este documento é para certificar que a vistoria requerida pela NORMAM-202/DPC foi efetuada e que a embarcação se encontrava de acordo com as prescrições relevantes da Norma.', 0, 'L');

// Tabela de convalidações
$tx = 10; $ty = 58; $tw = 190;
$nomesConv = certificadoNomesConvalidacoes($tipo_embarcacao_convalidacoes);
$qtdConv = count($nomesConv);
$headerH = 12; $rowH = $qtdConv > 4 ? 10 : 18;
$tcols = [44, 29, 36, 36, 45];
$pdf->Rect($tx, $ty, $tw, $headerH + ($rowH * $qtdConv));
$pdf->Line($tx, $ty + $headerH, $tx + $tw, $ty + $headerH);
for ($i = 1; $i <= $qtdConv; $i++) {
    $pdf->Line($tx, $ty + $headerH + ($rowH * $i), $tx + $tw, $ty + $headerH + ($rowH * $i));
}
$cx = $tx;
for ($i = 0; $i < count($tcols) - 1; $i++) {
    $cx += $tcols[$i];
    $pdf->Line($cx, $ty, $cx, $ty + $headerH + ($rowH * $qtdConv));
}

$headers = ['A REALIZAR', 'ENTRE', 'E', "LUGAR E DATA DA\nREALIZAÇÃO", 'VISTORIADOR'];
$cx = $tx;
$pdf->SetFont('helvetica', 'B', 10);
foreach ($headers as $i => $header) {
    $pdf->SetXY($cx, $ty + 2.2);
    $pdf->MultiCell($tcols[$i], 5, $header, 0, 'C');
    $cx += $tcols[$i];
}

$convByNumero = certificadoConvalidacoesPorNumero($convalidacoes);
$usarMapaConvalidacoes = !empty($convByNumero);
foreach ($nomesConv as $i => $nome) {
    $rowY = $ty + $headerH + ($rowH * $i);
    $numeroVistoria = $i + 1;
    $conv = $usarMapaConvalidacoes ? ($convByNumero[$numeroVistoria] ?? []) : ($convalidacoes[$i] ?? []);
    $dados = [
        $nome,
        cnblDataBR($conv['data_inicio'] ?? ''),
        cnblDataBR($conv['data_fim'] ?? ''),
        cnblText($conv['local_data'] ?? ''),
        cnblText($conv['vistoriador'] ?? ''),
    ];
    $cx = $tx;
    foreach ($dados as $j => $dado) {
        $pdf->SetFont('helvetica', $j === 0 || $j === 2 ? 'B' : '', $qtdConv > 4 ? 8 : 9);
        $pdf->SetXY($cx + 1, $rowY + ($qtdConv > 4 ? 2.5 : 6));
        $pdf->Cell($tcols[$j] - 2, 5, cnblPdfText($dado), 0, 0, 'C');
        $cx += $tcols[$j];
    }
}

// Observações
$obsY = $ty + $headerH + ($rowH * $qtdConv) + 8;
$obsH = max(30, 210 - $obsY);
$pdf->Rect(10, $obsY, 190, $obsH);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(11, $obsY + 2);
$pdf->Cell(188, 5, 'Observações:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$obs1 = '1. Este Certificado ' . $tipo_certificado . ' foi emitido com base no Relatório de Vistorias n.º ' . cnblText($c['relatorio_numero'] ?? '') . '.';
$obs2 = '2. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . cnblDataBR($c['data_vistoria']) . ' em ' . cnblText($c['local_vistoria'] ?? '') . '.';
$pdf->SetX(11);
$pdf->MultiCell(188, 5, cnblPdfText($obs1), 0, 'L');
$pdf->SetX(11);
$pdf->MultiCell(188, 5, cnblPdfText($obs2), 0, 'L');

// Identificação do anexo acima da faixa reservada para aprovação auditável.
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Rect(10, 212, 73, 6);
$pdf->SetXY(10, 212.5);
$pdf->Cell(73, 5, 'ANEXO 6-A - NORMAM 202/DPC', 0, 0, 'C');

$nome_arquivo = 'CNBL_' . str_replace('/', '-', $c['numero']) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nome_arquivo, 'I');
    exit;
}
