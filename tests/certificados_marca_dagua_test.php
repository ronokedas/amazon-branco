<?php

function assertCertificadoMarcaDagua(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$marcaDagua = $root . '/img/marca-dagua.png';
$basePdf = $root . '/includes/certificado_pdf_marca_dagua.php';

assertCertificadoMarcaDagua(is_file($marcaDagua), 'Marca-dagua oficial nao encontrada.');
assertCertificadoMarcaDagua(is_file($basePdf), 'Base compartilhada dos certificados nao encontrada.');

require_once $root . '/vendor/autoload.php';
require_once $basePdf;

class CertificadoMarcaDaguaTestePdf extends CertificadoPdfComMarcaDagua
{
    public int $paginasComMarcaDagua = 0;

    protected function desenharMarcaDaguaCertificado(): void
    {
        $this->paginasComMarcaDagua++;
        parent::desenharMarcaDaguaCertificado();
    }
}

$pdf = new CertificadoMarcaDaguaTestePdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->paginasComMarcaDagua = 0;
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 10, 'CERTIFICADO - PAGINA 1', 0, 1, 'C');
$pdf->AddPage();
$pdf->Cell(0, 10, 'CERTIFICADO - PAGINA 2', 0, 1, 'C');
$paginasComMarcaDagua = $pdf->paginasComMarcaDagua;
$conteudo = $pdf->Output('', 'S');

assertCertificadoMarcaDagua($paginasComMarcaDagua === 2, 'A marca-dagua deve ser desenhada em todas as paginas.');
assertCertificadoMarcaDagua(str_starts_with($conteudo, '%PDF-'), 'O PDF de verificacao nao foi gerado.');
assertCertificadoMarcaDagua(strlen($conteudo) > 10000, 'O PDF gerado nao parece conter a imagem da marca-dagua.');

$caminhoAmostra = getenv('CERTIFICADO_PDF_AMOSTRA');
if (is_string($caminhoAmostra) && $caminhoAmostra !== '') {
    assertCertificadoMarcaDagua(
        file_put_contents($caminhoAmostra, $conteudo) !== false,
        'Nao foi possivel salvar a amostra visual do certificado.'
    );
}

$geradores = [
    'CSN' => $root . '/modules/documentacao/certificados/pdf.php',
    'CSN referencia' => $root . '/modules/documentacao/certificados/pdf_modelo_referencia.php',
    'CNBL' => $root . '/modules/documentacao/cnbl/pdf.php',
    'CNARQ' => $root . '/modules/documentacao/cnarq/pdf.php',
    'LP' => $root . '/modules/documentacao/lp/pdf.php',
    'LC' => $root . '/modules/documentacao/lc/pdf.php',
    'CHT' => $root . '/modules/documentacao/cht/pdf.php',
];

foreach ($geradores as $tipo => $arquivo) {
    $fonte = file_get_contents($arquivo);
    assertCertificadoMarcaDagua($fonte !== false, "Nao foi possivel ler o gerador {$tipo}.");
    assertCertificadoMarcaDagua(
        str_contains($fonte, 'extends CertificadoPdfComMarcaDagua'),
        "O certificado {$tipo} nao usa a base compartilhada da marca-dagua."
    );
}

echo "certificados_marca_dagua_test: OK\n";
