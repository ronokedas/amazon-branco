<?php
/**
 * MÓDULO: PORTAL DO CLIENTE - SATISFAÇÃO (ISO 9.1.2)
 * Arquivo: modules/portal/satisfacao_actions.php
 * Gravação de pesquisa de satisfação do cliente
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireClienteSenhaDefinitiva();
    $clienteId = clientePortalId();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
        exit;
    }

    $documentoId = trim((string)($_POST['documento_id'] ?? ''));
    $documentoTipo = trim((string)($_POST['documento_tipo'] ?? ''));
    $embarcacaoId = trim((string)($_POST['embarcacao_id'] ?? ''));
    $osId = trim((string)($_POST['ordem_servico_id'] ?? ''));

    $notaAtendimento = max(1, min(5, (int)($_POST['nota_atendimento'] ?? 5)));
    $notaTecnica = max(1, min(5, (int)($_POST['nota_tecnica'] ?? 5)));
    $notaPrazo = max(1, min(5, (int)($_POST['nota_prazo'] ?? 5)));
    $notaNps = max(0, min(10, (int)($_POST['nota_nps'] ?? 10)));
    $comentario = trim(sanitizar($_POST['comentario'] ?? ''));

    // Se OS não foi informada diretamente, tenta localizar pelo documento ou embarcação
    if (empty($osId)) {
        if (!empty($embarcacaoId)) {
            $stmtOs = $pdo->prepare("SELECT id FROM ordens_servico WHERE embarcacao_id = :emb_id ORDER BY created_at DESC LIMIT 1");
            $stmtOs->execute([':emb_id' => $embarcacaoId]);
            $osId = (string)($stmtOs->fetchColumn() ?: '');
        }
    }
    if (empty($osId)) {
        $osId = 'OS-' . substr($documentoId ?: gerarUUID(), 0, 32);
    }

    // Verificar se já avaliou esta OS ou documento
    $stmtCheck = $pdo->prepare("
        SELECT id FROM sgq_satisfacao_clientes
        WHERE ordem_servico_id = :os_id OR (documento_id = :doc_id AND documento_id IS NOT NULL AND documento_id != '')
        LIMIT 1
    ");
    $stmtCheck->execute([':os_id' => $osId, ':doc_id' => $documentoId]);
    $existente = $stmtCheck->fetchColumn();

    if ($existente) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Avaliação já registrada anteriormente. Liberando download...',
            'ja_avaliado' => true
        ]);
        exit;
    }

    $id = gerarUUID();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO sgq_satisfacao_clientes (
            id, ordem_servico_id, cliente_id, embarcacao_id, documento_id, documento_tipo,
            nota_atendimento_comercial, nota_qualidade_tecnica, nota_cumprimento_prazo, nota_nps_geral,
            comentario_elogio_critica, dispositivo_acesso, ip_origem, data_avaliacao
        ) VALUES (
            :id, :os_id, :cli_id, :emb_id, :doc_id, :doc_tipo,
            :nota_atend, :nota_tec, :nota_prazo, :nota_nps,
            :coment, 'PORTAL_WEB', :ip, NOW()
        )
    ");

    $stmt->execute([
        ':id' => $id,
        ':os_id' => $osId,
        ':cli_id' => $clienteId,
        ':emb_id' => $embarcacaoId ?: null,
        ':doc_id' => $documentoId ?: null,
        ':doc_tipo' => $documentoTipo ?: null,
        ':nota_atend' => $notaAtendimento,
        ':nota_tec' => $notaTecnica,
        ':nota_prazo' => $notaPrazo,
        ':nota_nps' => $notaNps,
        ':coment' => $comentario ?: null,
        ':ip' => $ip,
    ]);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Obrigado por avaliar nossos serviços! Seu feedback garante nossa conformidade ISO 9001.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar avaliação: ' . $e->getMessage()]);
}
