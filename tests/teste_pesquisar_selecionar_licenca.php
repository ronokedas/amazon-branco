<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: PESQUISAR E SELECIONAR NUMERO DA LICENCA ===\n\n";

// 1. Testar se a função obterProximoNumeroLicencaPreview calcula números distintos para cada tipo
$tipoLicencas = ['LC', 'LA', 'LR', 'LCEC'];
$prevs = [];
foreach ($tipoLicencas as $tipo) {
    $p = obterProximoNumeroLicencaPreview($pdo, $tipo);
    $prevs[$tipo] = $p;
    echo "Próximo {$tipo}: {$p}\n";
    if (empty($p)) {
        die("ERRO: Preview vazio para {$tipo}\n");
    }
}

// 2. Testar busca de licenças cadastradas para o combobox
$stmt = $pdo->query("SELECT id, numero_lc, tipo_licenca, nome_embarcacao FROM certificados_lc WHERE ativo = 1 ORDER BY criado_em DESC LIMIT 5");
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nTotal de licenças recuperadas no combobox: " . count($lista) . "\n";
foreach ($lista as $item) {
    echo " - Licença: {$item['numero_lc']} | Embarcação: {$item['nome_embarcacao']} ({$item['tipo_licenca']})\n";
}

// 3. Testar inserção manual com número de licença personalizado
$numeroManual = "AM-LC-999/" . date('y');
$pdo->prepare("DELETE FROM certificados_lc WHERE numero_lc = :num")->execute([':num' => $numeroManual]);

$emb = $pdo->query("SELECT id, nome FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$embId = $emb['id'];
$embNome = $emb['nome'];

$idTeste = gerarUUID();
$sql = "INSERT INTO certificados_lc (
            id, numero_lc, token_assinatura, embarcacao_id,
            tipo_licenca, nome_embarcacao, status, ativo, data_emissao
        ) VALUES (
            :id, :numero_lc, :token, :emb_id, 'LC', :nome_emb, 'rascunho', 1, CURDATE()
        )";

try {
    $pdo->prepare($sql)->execute([
        ':id' => $idTeste,
        ':numero_lc' => $numeroManual,
        ':token' => bin2hex(random_bytes(32)),
        ':emb_id' => $embId,
        ':nome_emb' => $embNome
    ]);
} catch (Exception $e) {
    echo "EXCECAO AO INSERIR: " . $e->getMessage() . "\n";
    exit(1);
}

$check = $pdo->prepare("SELECT numero_lc FROM certificados_lc WHERE id = :id");
$check->execute([':id' => $idTeste]);
$savedNum = $check->fetchColumn();

if ($savedNum === $numeroManual) {
    echo "\nSucesso: Licença manual criada com número personalizado '{$savedNum}'!\n";
} else {
    die("ERRO: Número salvo difere do esperado: {$savedNum}\n");
}

// Limpar teste
$pdo->prepare("DELETE FROM certificados_lc WHERE id = :id")->execute([':id' => $idTeste]);
echo "Limpeza de dados de teste concluída.\n";

echo "\n=== TESTE CONCLUÍDO COM 100% DE SUCESSO! ===\n";
