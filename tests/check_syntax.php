<?php
$files = [
    __DIR__ . '/../includes/protocolos.php',
    __DIR__ . '/../modules/protocolos/form.php',
    __DIR__ . '/../modules/protocolos/components/dossie_identificacao.php',
    __DIR__ . '/../modules/protocolos/components/movimentacoes_historico.php',
    __DIR__ . '/../modules/protocolos/components/modal_assinar_oficio.php',
    __DIR__ . '/../modules/protocolos/actions.php',
    __DIR__ . '/../modules/protocolos/pdf_dossie.php',
    __DIR__ . '/../modules/protocolos/pdf.php'
];

$errors = 0;
foreach ($files as $f) {
    $cmd = 'php -l ' . escapeshellarg($f);
    $output = [];
    $returnVar = 0;
    exec($cmd, $output, $returnVar);
    $rel = basename(dirname($f)) . '/' . basename($f);
    if ($returnVar === 0) {
        echo "[OK] {$rel}" . PHP_EOL;
    } else {
        echo "[ERRO] {$rel}: " . implode(' ', $output) . PHP_EOL;
        $errors++;
    }
}

exit($errors > 0 ? 1 : 0);
