<?php

$files = glob(__DIR__ . '/*_test.php');
sort($files);
$passed = 0;
$failed = 0;
$failures = [];

echo "===============================================================\n";
echo "EXECUTANDO TODOS OS TESTES DO SISTEMA ERP AMAZON (" . count($files) . " SUÍTES)\n";
echo "===============================================================\n\n";

foreach ($files as $f) {
    $nome = basename($f);
    echo "Executando {$nome}... ";
    passthru("php " . escapeshellarg($f), $code);
    if ($code === 0) {
        $passed++;
        echo " [OK]\n";
    } else {
        $failed++;
        $failures[] = $nome;
        echo " [FALHOU]\n";
    }
}

echo "\n===============================================================\n";
echo "RESULTADO FINAL: TOTAL PASSARAM: {$passed} | TOTAL FALHARAM: {$failed}\n";
if (!empty($failures)) {
    echo "FALHAS: " . implode(', ', $failures) . "\n";
}
echo "===============================================================\n";

exit($failed > 0 ? 1 : 0);
