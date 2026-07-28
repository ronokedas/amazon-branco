<?php
require_once __DIR__ . '/../includes/functions.php';

function assertNumeracaoSecoes(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$itens = [
    ['id' => 'f-nova', 'bloco_vistoria' => 'flutuando', 'ordem' => 20],
    ['id' => 's-5', 'bloco_vistoria' => 'seco', 'ordem' => 5, 'numero_origem' => 5, 'exigencia_origem_id' => 'o5'],
    ['id' => 'f-3', 'bloco_vistoria' => 'flutuando', 'ordem' => 3, 'numero_origem' => 3, 'exigencia_origem_id' => 'o3'],
    ['id' => 's-1', 'bloco_vistoria' => 'seco', 'ordem' => 1, 'numero_origem' => 1, 'exigencia_origem_id' => 'o1'],
    ['id' => 's-3', 'bloco_vistoria' => 'seco', 'ordem' => 3, 'numero_origem' => 3, 'exigencia_origem_id' => 'o3s'],
    ['id' => 'f-1', 'bloco_vistoria' => 'flutuando', 'ordem' => 1, 'numero_origem' => 1, 'exigencia_origem_id' => 'o1f'],
    ['id' => 's-2', 'bloco_vistoria' => 'seco', 'ordem' => 2, 'numero_origem' => 2, 'exigencia_origem_id' => 'o2'],
];

$numerados = numerarExigenciasPorSecao($itens);
$porId = array_column($numerados, null, 'id');

assertNumeracaoSecoes($porId['s-1']['numero_sequencial_calculado'] === 1, 'Seco nao iniciou em 1.');
assertNumeracaoSecoes($porId['s-2']['numero_sequencial_calculado'] === 2, 'Seco perdeu a ordem anterior.');
assertNumeracaoSecoes($porId['s-3']['numero_sequencial_calculado'] === 3, 'Seco perdeu a sequencia.');
assertNumeracaoSecoes($porId['s-5']['numero_sequencial_calculado'] === 4, 'A lacuna anterior nao foi compactada.');
assertNumeracaoSecoes($porId['f-1']['numero_sequencial_calculado'] === 1, 'Flutuando nao reiniciou em 1.');
assertNumeracaoSecoes($porId['f-3']['numero_sequencial_calculado'] === 2, 'Flutuando nao compactou a lacuna.');
assertNumeracaoSecoes($porId['f-nova']['numero_sequencial_calculado'] === 3, 'Item novo nao ficou depois dos herdados.');

echo "numeracao_exigencias_secoes_test: OK\n";
