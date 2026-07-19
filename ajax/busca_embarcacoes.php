<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

verificar_sessao();
header('Content-Type: application/json; charset=utf-8');

$termo = trim($_GET['q'] ?? '');
$cliente_id = trim($_GET['cliente_id'] ?? '');

if ($cliente_id === '' && strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

try {
    if ($cliente_id !== '') {
        $sql = "SELECT DISTINCT e.id, e.nome, e.registro
                FROM embarcacoes e
                LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.status='ATIVO'
                WHERE e.ativo = 1
                  AND (
                    ce.cliente_id = :cliente_id
                    OR e.cliente_id = :cliente_id
                    OR e.proprietario_id = :cliente_id
                  )
                ORDER BY e.nome ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $cliente_id]);
    } else {
        $sql = "SELECT id, nome, registro
                FROM embarcacoes
                WHERE ativo = 1
                  AND (nome LIKE :nome OR registro LIKE :registro)
                ORDER BY nome ASC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $busca = '%' . $termo . '%';
        $stmt->execute([
            ':nome' => $busca,
            ':registro' => $busca,
        ]);
    }
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
} catch (Exception $e) {
    echo json_encode([]);
}
