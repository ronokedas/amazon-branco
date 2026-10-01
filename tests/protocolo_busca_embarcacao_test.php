<?php
/**
 * Teste de Busca Inteligente de Embarcações (Letra a Letra)
 */

require_once __DIR__ . '/../config.php';

echo "=== TESTANDO PESQUISA INTELIGENTE DE EMBARCAÇÕES ===\n";

$embarcacoes = $pdo->query("SELECT e.id, e.nome, e.registro, COALESCE(e.cliente_id, e.proprietario_id) cliente_id, c.nome cliente_nome 
                            FROM embarcacoes e 
                            LEFT JOIN clientes c ON c.id = COALESCE(e.cliente_id, e.proprietario_id) 
                            WHERE e.ativo = 1 
                            ORDER BY e.nome")->fetchAll(PDO::FETCH_ASSOC);

echo "Total de embarcações ativas cadastradas: " . count($embarcacoes) . PHP_EOL;

function testarFiltro(array $lista, string $termo): array {
    $normTermo = mb_strtolower(trim($termo), 'UTF-8');
    return array_values(array_filter($lista, function($e) use ($normTermo) {
        $nome = mb_strtolower($e['nome'] ?? '', 'UTF-8');
        $reg = mb_strtolower($e['registro'] ?? '', 'UTF-8');
        return str_contains($nome, $normTermo) || str_contains($reg, $normTermo);
    }));
}

// Simular digitação letra a letra: 'p', 'po', 'pos', 'post', 'posto'
$letras = ['p', 'po', 'pos', 'post', 'posto'];
foreach ($letras as $t) {
    $res = testarFiltro($embarcacoes, $t);
    echo "Termo digitado: '{$t}' -> " . count($res) . " embarcação(ões) encontrada(s)." . PHP_EOL;
}

echo "=== TESTE DE PESQUISA CONCLUÍDO COM SUCESSO! ===\n";
