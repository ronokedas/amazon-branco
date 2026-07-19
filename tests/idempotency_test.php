<?php

$_SESSION = [];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['_submission_token'] = '12345678-1234-4234-8234-123456789abc';

require_once __DIR__ . '/../includes/functions.php';

$primeiroEnvio = aceitarEnvioUnico();
$envioRepetido = aceitarEnvioUnico();

$_POST['_submission_token'] = 'abcdefab-1234-4234-8234-123456789abc';
$novoEnvio = aceitarEnvioUnico();

if ($primeiroEnvio !== true || $envioRepetido !== false || $novoEnvio !== true) {
    fwrite(STDERR, 'Falha na protecao de idempotencia.' . PHP_EOL);
    exit(1);
}

$_SESSION = [];
$_SERVER['REQUEST_URI'] = '/cadastro-legado/actions?action=salvar';
$_POST = [
    'csrf_token' => 'csrf-de-teste',
    'action' => 'salvar',
    'nome' => 'Embarcacao Teste',
];

$primeiroEnvioLegado = aceitarEnvioUnico();
$envioLegadoRepetido = aceitarEnvioUnico();
$_POST['nome'] = 'Outra Embarcacao';
$envioLegadoAlterado = aceitarEnvioUnico();

if ($primeiroEnvioLegado !== true || $envioLegadoRepetido !== false || $envioLegadoAlterado !== true) {
    fwrite(STDERR, 'Falha na protecao de formularios legados.' . PHP_EOL);
    exit(1);
}

$_SESSION = [];
$_POST = ['action' => 'sincronizar', 'registro' => '123'];
$apiPrimeiraTentativa = aceitarEnvioUnico();
$apiSegundaTentativa = aceitarEnvioUnico();

if ($apiPrimeiraTentativa !== true || $apiSegundaTentativa !== true) {
    fwrite(STDERR, 'A protecao interferiu em uma integracao sem CSRF.' . PHP_EOL);
    exit(1);
}

echo 'OK: tokens, formularios legados e integracoes foram tratados corretamente.' . PHP_EOL;
