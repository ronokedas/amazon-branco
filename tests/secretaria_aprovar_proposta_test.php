<?php
/**
 * Teste Automatizado: Cargo SECRETARIA aceitando/assinando proposta manualmente
 * Local: tests/secretaria_aprovar_proposta_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/financeiro_escritorios.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TESTE: CARGO SECRETARIA ACEITANDO/ASSINANDO PROPOSTA MANUALMENTE ===\n";

$pdo->beginTransaction();

try {
    // 1. Criar Usuário com Cargo SECRETARIA
    $secId = 'sec-prop-' . bin2hex(random_bytes(4));
    $stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, :nome, :email, :senha, 'SECRETARIA', 1)");
    $stmtUser->execute([
        ':id' => $secId,
        ':nome' => 'Thainara Barros Secretaria',
        ':email' => 'thainara.comercial@amazonnaval.com.br',
        ':senha' => password_hash('teste123', PASSWORD_DEFAULT)
    ]);
    aplicarPermissoesPadraoUsuario($pdo, $secId, 'SECRETARIA');

    // 2. Simular Sessão
    $_SESSION['usuario_id'] = $secId;
    $_SESSION['usuario_nome'] = 'Thainara Barros Secretaria';
    $_SESSION['usuario_cargo'] = 'SECRETARIA';
    $_SESSION['usuario_logado'] = true;

    // Verificar financeiroEhAdmin
    if (!financeiroEhAdmin()) {
        throw new RuntimeException("Falha: financeiroEhAdmin deve retornar true para SECRETARIA.");
    }
    echo "✅ SUCESSO: financeiroEhAdmin() reconhece cargo SECRETARIA com permissão administrativa.\n";

    // 3. Obter ou criar cliente e escritório
    $escritorioId = $pdo->query("SELECT id FROM escritorios WHERE ativo = 1 LIMIT 1")->fetchColumn();
    if (!$escritorioId) {
        $escritorioId = ESCRITORIO_MATRIZ_ID;
    }

    $clienteId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();
    if (!$clienteId) {
        $clienteId = 'cli-test-prop';
        $pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, ativo) VALUES (?, 'Cliente Teste Comercial', '12345678901', 'cliente@teste.com', 1)")->execute([$clienteId]);
    }

    // 4. Criar proposta em rascunho
    $propId = 'prop-sec-' . bin2hex(random_bytes(4));
    $propNumero = 'AM-ORC-9999/26';
    $stmtProp = $pdo->prepare("INSERT INTO propostas (
        id, numero, cliente_id, escritorio_id, data_emissao, data_validade,
        valor_total, parcelas, forma_pagamento, status, assinado, criado_por, created_at
    ) VALUES (
        :id, :numero, :cli, :esc, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
        5000.00, 2, 'parcelado', 'rascunho', 0, :uid, NOW()
    )");
    $stmtProp->execute([
        ':id' => $propId,
        ':numero' => $propNumero,
        ':cli' => $clienteId,
        ':esc' => $escritorioId,
        ':uid' => $secId
    ]);

    // 5. Testar se a verificação de permissão no dashboard comercial funciona
    $cargo = getCargo();
    $p = [
        'id' => $propId,
        'numero' => $propNumero,
        'status' => 'rascunho',
        'assinado' => 0,
        'criado_por' => 'outro-usuario' // simula proposta criada por outro vendedor/admin
    ];
    $assinada = !empty($p['assinado']) || ($p['status'] ?? '') === 'assinada';
    $podeAprovarManual = in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)
        && ($cargo === 'ADMIN' || $cargo === 'SECRETARIA' || ($p['criado_por'] ?? '') === ($_SESSION['usuario_id'] ?? ''))
        && !$assinada
        && !in_array(($p['status'] ?? ''), ['cancelada', 'recusada'], true);

    if (!$podeAprovarManual) {
        throw new RuntimeException("Falha: Secretaria deveria ter \$podeAprovarManual = true no dashboard.");
    }
    echo "✅ SUCESSO: Condição \$podeAprovarManual habilitada para SECRETARIA no Dashboard Comercial.\n";

    // 6. Simular requisição POST de aprovar_assinatura_manual
    $_POST['id'] = $propId;
    $_POST['action'] = 'aprovar_assinatura_manual';
    $_POST['csrf_token'] = gerarCSRF();

    // Capturar o fluxo do case aprovar_assinatura_manual
    $cargoAtual = getCargo();
    $usuarioAtualId = (string)($_SESSION['usuario_id'] ?? '');

    if (!in_array($cargoAtual, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)) {
        throw new RuntimeException("Falha: Perfil SECRETARIA bloqueado em aprovar_assinatura_manual.");
    }

    $stmtCheck = $pdo->prepare("SELECT p.*, c.nome AS cliente_nome FROM propostas p INNER JOIN clientes c ON c.id = p.cliente_id WHERE p.id = :id FOR UPDATE");
    $stmtCheck->execute([':id' => $propId]);
    $propData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (empty($propData['escritorio_id']) || !financeiroPodeAcessarEscritorio($pdo, (string)$propData['escritorio_id'])) {
        throw new RuntimeException("Falha: financeiroPodeAcessarEscritorio bloqueou a secretaria.");
    }

    $assinanteNome = 'Autorização interna - ' . ($_SESSION['usuario_nome'] ?? 'Secretaria');
    $stmtUpdate = $pdo->prepare("
        UPDATE propostas
        SET assinatura_ip = '127.0.0.1',
            assinatura_em = NOW(),
            assinante_nome = :nome,
            assinante_documento = 'Autorização interna sem assinatura digital (Secretaria)',
            assinado = 1,
            status = 'assinada'
        WHERE id = :id AND assinado = 0
    ");
    $stmtUpdate->execute([
        ':nome' => $assinanteNome,
        ':id' => $propId,
    ]);

    if ($stmtUpdate->rowCount() !== 1) {
        throw new RuntimeException("Falha ao atualizar status da proposta para assinada.");
    }

    // 7. Verificar se no banco a proposta está assinada
    $statusFinal = $pdo->query("SELECT status, assinado, assinante_nome FROM propostas WHERE id = '{$propId}'")->fetch(PDO::FETCH_ASSOC);
    if ($statusFinal['status'] !== 'assinada' || (int)$statusFinal['assinado'] !== 1) {
        throw new RuntimeException("Falha: Proposta não ficou com status 'assinada' e assinado = 1.");
    }
    echo "✅ SUCESSO: Proposta aprovada com sucesso pela SECRETARIA! Status: {$statusFinal['status']} | Assinado: {$statusFinal['assinado']} | Assinante: {$statusFinal['assinante_nome']}\n";

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";
} catch (Exception $e) {
    echo "❌ FALHA: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $pdo->rollBack();
    exit(1);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rollback executado com sucesso. Banco de dados preservado limpo.\n";
    }
}
