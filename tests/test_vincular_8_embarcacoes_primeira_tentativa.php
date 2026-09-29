<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cliente_vinculos.php';

echo "=== TESTE: VINCULAR 8 EMBARCAÇÕES NA PRIMEIRA TENTATIVA ===\n\n";

try {
    global $pdo;
    $db = $pdo;

// 1. Criar cliente temporário de teste
$clienteId = bin2hex(random_bytes(16));
$db->prepare("INSERT INTO clientes (id, nome, perfil, status, ativo, criado_em) VALUES (?, 'Cliente Teste 8 Embarcacoes', 'armador', 'ATIVO', 1, NOW())")->execute([$clienteId]);

// 2. Criar 8 embarcações
$nomes = ['RAIZEM V', 'RAIZEM III', 'BARREIRO', 'ESPERANÇA', 'FORTALEZA', 'IRISSANGA', 'SANTO ANTÔNIO', 'INGA'];
$embIds = [];

foreach ($nomes as $i => $nome) {
    $embId = bin2hex(random_bytes(16));
    $db->prepare("INSERT INTO embarcacoes (id, nome, registro, proprietario_id, cliente_id, ativo) VALUES (?, ?, ?, ?, ?, 1)")
       ->execute([$embId, $nome, 'REG-' . bin2hex(random_bytes(3)), $clienteId, $clienteId]);
    $embIds[] = $embId;
}

// 3. Simular que uma embarcação já tinha histórico anterior inativo com chave preenchida (cenário que causava falha no primeiro save)
$db->prepare("INSERT INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, vinculo_ativo_chave, status) VALUES (UUID(), ?, ?, ?, 'INATIVO')")
   ->execute([$clienteId, $embIds[7], $clienteId . ':' . $embIds[7]]);

// 4. Executar sincronizarClienteEmbarcacoes com os 8 IDs (Primeira tentativa!)
echo "Sincronizando 8 embarcações na primeira tentativa...\n";
sincronizarClienteEmbarcacoes($db, $clienteId, $embIds, null);

// 5. Verificar vínculos ativos
$stmt = $db->prepare("SELECT COUNT(*) FROM clientes_embarcacoes WHERE cliente_id = ? AND status = 'ATIVO'");
$stmt->execute([$clienteId]);
$totalAtivos = (int)$stmt->fetchColumn();

echo "Total de vínculos ativos no banco: {$totalAtivos} de 8\n";
if ($totalAtivos === 8) {
    echo "  [✓] Sucesso! Todas as 8 embarcações foram ativadas e vinculadas no PRIMEIRO salvamento!\n";
} else {
    echo "  [✗] Falha! Apenas {$totalAtivos} foram vinculadas.\n";
    exit(1);
}

// 6. Testar chamada idêntica à da action embarcacoes_cliente
$stmt = $db->prepare("
    SELECT DISTINCT e.id, e.nome, COALESCE(e.numero_inscricao, e.registro, '') as registro
    FROM embarcacoes e
    LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND (ce.status = 'ATIVO' OR ce.status IS NULL OR ce.desvinculado_em IS NULL)
    WHERE (ce.cliente_id = :cid1 OR e.proprietario_id = :cid2 OR e.cliente_id = :cid3)
      AND e.excluido_em IS NULL
    ORDER BY e.nome ASC
");
$stmt->execute([':cid1' => $clienteId, ':cid2' => $clienteId, ':cid3' => $clienteId]);
$embarcacoesCarregadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total de embarcações puxadas para proposta: " . count($embarcacoesCarregadas) . " de 8\n";
if (count($embarcacoesCarregadas) === 8) {
    echo "  [✓] Endpoint retorna todas as 8 embarcações perfeitamente!\n";
} else {
    echo "  [✗] Falha no retorno das embarcações.\n";
    exit(1);
}

// Limpeza
$db->prepare("DELETE FROM clientes_embarcacoes WHERE cliente_id = ?")->execute([$clienteId]);
foreach ($embIds as $eid) {
    $db->prepare("DELETE FROM embarcacoes WHERE id = ?")->execute([$eid]);
}
$db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$clienteId]);

echo "\n===============================================================\n";
echo "TESTE CONCLUÍDO COM 100% DE SUCESSO!\n";
echo "===============================================================\n";
} catch (Throwable $e) {
    echo "ERRO NO TESTE: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
