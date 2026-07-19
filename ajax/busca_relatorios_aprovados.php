<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

verificar_sessao();
header('Content-Type: application/json; charset=utf-8');

$termo = trim($_GET['q'] ?? '');
$id = trim($_GET['id'] ?? '');
$recentes = ($_GET['recentes'] ?? '') === '1';

try {
    if ($id !== '') {
        $stmt = $pdo->prepare("
            SELECT v.id,
                   v.agendamento_id,
                   v.numero,
                   v.data_vistoria,
                   v.status,
                   e.nome AS nome_embarcacao,
                   e.numero_inscricao
            FROM vistorias v
            JOIN agendamentos a ON v.agendamento_id = a.id
            JOIN embarcacoes e ON a.embarcacao_id = e.id
            WHERE v.id = :id
              AND v.status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS')
              AND v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id = v.agendamento_id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
              AND NOT EXISTS (SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id = v.id AND ve.antes_de_suspender = 1 AND ve.conforme = 'nao' AND ve.status_item <> 'cumprida')
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($resultado ? [$resultado] : []);
        exit;
    }

    if (strlen($termo) < 2 && !$recentes) {
        echo json_encode([]);
        exit;
    }

    $whereBusca = "";
    $params = [];

    if (!$recentes) {
        $whereBusca = "AND (
              v.numero LIKE :busca_numero
              OR e.nome LIKE :busca_nome
              OR e.numero_inscricao LIKE :busca_inscricao
          )";
        $busca = '%' . $termo . '%';
        $params[':busca_numero'] = $busca;
        $params[':busca_nome'] = $busca;
        $params[':busca_inscricao'] = $busca;
    }

    $stmt = $pdo->prepare("
        SELECT v.id,
               v.agendamento_id,
               v.numero,
               v.data_vistoria,
               v.status,
               e.nome AS nome_embarcacao,
               e.numero_inscricao
        FROM vistorias v
        JOIN agendamentos a ON v.agendamento_id = a.id
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        WHERE v.status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS')
          AND v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id = v.agendamento_id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
          AND NOT EXISTS (SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id = v.id AND ve.antes_de_suspender = 1 AND ve.conforme = 'nao' AND ve.status_item <> 'cumprida')
          {$whereBusca}
        ORDER BY v.data_vistoria DESC
        LIMIT " . ($recentes ? "10" : "20") . "
    ");

    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
