<?php

function assertPropostaPdfIdentidade(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$logoPath = $root . '/img/logo.png';
$watermarkPath = $root . '/img/marca-dagua.png';
$pdfSourcePath = $root . '/modules/comercial/pdf.php';

assertPropostaPdfIdentidade(is_file($logoPath), 'Logo oficial nao encontrada.');
assertPropostaPdfIdentidade(is_file($watermarkPath), 'Marca-dagua nao encontrada.');

$logoInfo = getimagesize($logoPath);
$watermarkInfo = getimagesize($watermarkPath);
assertPropostaPdfIdentidade($logoInfo !== false && $watermarkInfo !== false, 'Nao foi possivel ler os arquivos de imagem.');
assertPropostaPdfIdentidade(
    $logoInfo[0] === $watermarkInfo[0] && $logoInfo[1] === $watermarkInfo[1],
    'A marca-dagua nao preserva as dimensoes da logo.'
);
assertPropostaPdfIdentidade($watermarkInfo['mime'] === 'image/png', 'A marca-dagua deve ser PNG.');

assertPropostaPdfIdentidade(extension_loaded('gd'), 'A extensao GD e necessaria para validar a marca-dagua.');
$watermark = imagecreatefrompng($watermarkPath);
assertPropostaPdfIdentidade($watermark !== false, 'Nao foi possivel abrir a marca-dagua com transparencia.');

$hasVisiblePixel = false;
$hasTransparentPixel = false;
$width = imagesx($watermark);
$height = imagesy($watermark);

for ($y = 0; $y < $height; $y += 8) {
    for ($x = 0; $x < $width; $x += 8) {
        $rgba = imagecolorsforindex($watermark, imagecolorat($watermark, $x, $y));
        assertPropostaPdfIdentidade(
            $rgba['red'] === $rgba['green'] && $rgba['green'] === $rgba['blue'],
            'A marca-dagua deve estar em escala de cinza.'
        );
        $hasVisiblePixel = $hasVisiblePixel || $rgba['alpha'] < 127;
        $hasTransparentPixel = $hasTransparentPixel || $rgba['alpha'] > 115;
    }
}

imagedestroy($watermark);
assertPropostaPdfIdentidade($hasVisiblePixel, 'A marca-dagua ficou totalmente transparente.');
assertPropostaPdfIdentidade($hasTransparentPixel, 'A marca-dagua nao possui a transparencia esperada.');

$pdfSource = file_get_contents($pdfSourcePath);
assertPropostaPdfIdentidade($pdfSource !== false, 'Nao foi possivel ler o gerador da proposta.');
assertPropostaPdfIdentidade(
    str_contains($pdfSource, '$this->desenharCabecalho();'),
    'O cabecalho personalizado nao esta aplicado na finalizacao das paginas.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, '$this->desenharRodape();'),
    'O rodape personalizado nao esta aplicado na finalizacao das paginas.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, '$pdfFinal->desenharIdentidadeFinal();'),
    'A identidade visual final nao esta sendo aplicada ao PDF consolidado.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, '$this->SetMargins(15, 42, 15);'),
    'A margem superior nao reserva o espaco do cabecalho.'
);
assertPropostaPdfIdentidade(
    substr_count($pdfSource, "__DIR__ . '/../../img/logo.png'") === 2,
    'Todos os usos da logo na proposta devem apontar para a fonte oficial.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, '$this->Image($logo_path, 17, 14.5, 18, 0,') &&
    str_contains($pdfSource, '$pdf->Image($logo_path2, 142, $assinaturaY + 14, 18, 0,'),
    'As logos do cabecalho e do proponente nao estao centralizadas nas areas reservadas.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, "__DIR__ . '/../../img/marca-dagua.png'"),
    'O gerador nao referencia a marca-dagua oficial.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, 'public function AddPage(') &&
    str_contains($pdfSource, '$this->desenharMarcaDagua();'),
    'A marca-dagua deve ser repetida pelo ciclo de criacao de paginas.'
);
assertPropostaPdfIdentidade(
    str_contains($pdfSource, 'StreamReader::createByString($conteudoBase)') &&
    str_contains($pdfSource, '$pdfFinal->importPage($pagina)'),
    'A identidade final deve ser aplicada depois que a paginacao estiver fechada.'
);

echo "proposta_pdf_identidade_visual_test: OK\n";
