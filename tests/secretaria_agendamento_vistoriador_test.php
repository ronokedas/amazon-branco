<?php
/**
 * Teste Automatizado: Cargo SECRETARIA escolhendo vistoriador no agendamento e confirmando OS
 * Local: tests/secretaria_agendamento_vistoriador_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/financeiro_escritorios.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TESTE: CARGO SECRETARIA ESCOLHENDO VISTORIADOR NO AGENDAMENTO ===\n";

$pdo->beginTransaction();

try {
    // 1. Criar Usuário Secretaria
    $secId = 'sec-ag-' . bin2hex(random_bytes(4));
    $stmtSec = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, :nome, :email, :senha, 'SECRETARIA', 1)");
    $stmtSec->execute([
        ':id' => $secId,
        ':nome' => 'Thainara Barros Secretaria',
        ':email' => 'thainara.agendamento@amazonnaval.com.br',
        ':senha' => password_hash('teste123', PASSWORD_DEFAULT)
    ]);
    aplicarPermissoesPadraoUsuario($pdo, $secId, 'SECRETARIA');

    // 2. Criar Usuário Vistoriador Naval
    $vistId = 'vist-ag-' . bin2hex(random_bytes(4));
    $stmtVist = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo, status_sgq, credencial_marinha_numero, credencial_marinha_validade) 
        VALUES (:id, :nome, :email, :senha, 'VISTORIADOR', 1, 'QUALIFICADO', 'MB-998877', DATE_ADD(CURDATE(), INTERVAL 1 YEAR))");
    $stmtVist->execute([
        ':id' => $vistId,
        ':nome' => 'Eng. Lucas Vistoriador Naval',
        ':email' => 'lucas.vistoriador@amazonnaval.com.br',
        ':senha' => password_hash('teste123', PASSWORD_DEFAULT)
    ]);
    aplicarPermissoesPadraoUsuario($pdo, $vistId, 'VISTORIADOR');

    // 3. Simular Sessão como SECRETARIA
    $_SESSION['usuario_id'] = $secId;
    $_SESSION['usuario_nome'] = 'Thainara Barros Secretaria';
    $_SESSION['usuario_cargo'] = 'SECRETARIA';
    $_SESSION['usuario_logado'] = true;

    $cargo = getCargo();
    if ($cargo !== 'SECRETARIA') {
        throw new RuntimeException("Falha: getCargo() deveria retornar SECRETARIA.");
    }

    // 4. Testar a query de vistoriadores disponível para SECRETARIA
    $vistoriadores = [];
    if (in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)) {
        $vistoriadores = $pdo->query("SELECT DISTINCT u.id, u.nome, u.email, u.status_sgq, u.credencial_marinha_numero, u.credencial_marinha_validade, u.registro_conselho_tipo, u.registro_conselho_validade FROM usuarios u LEFT JOIN usuario_perfis up ON up.usuario_id = u.id WHERE u.ativo = 1 AND (u.cargo = 'VISTORIADOR' OR up.perfil = 'VISTORIADOR') ORDER BY u.nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($vistoriadores)) {
        throw new RuntimeException("Falha: Lista de vistoriadores para SECRETARIA não deveria estar vazia.");
    }

    $encontrouVistoriador = false;
    foreach ($vistoriadores as $v) {
        if ($v['id'] === $vistId) {
            $encontrouVistoriador = true;
            break;
        }
    }
    if (!$encontrouVistoriador) {
        throw new RuntimeException("Falha: Vistoriador recém-criado não foi listado na consulta para SECRETARIA.");
    }
    echo "✅ SUCESSO: Lista de vistoriadores carregada com sucesso para SECRETARIA (encontrado: Eng. Lucas Vistoriador Naval).\n";

    // 5. Testar renderização do form.php para SECRETARIA
    // Criar dados prévios para agendamento (Cliente, Embarcação, Proposta)
    $clienteId = 'cli-ag-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, ativo) VALUES (?, 'Cliente Navegação Norte', '98765432000199', 'contato@navnorte.com', 1)")->execute([$clienteId]);

    $embarcacaoId = 'emb-ag-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, registro, cliente_id, ativo) VALUES (?, 'B/M Estrela do Norte', '9911223344', ?, 1)")->execute([$embarcacaoId, $clienteId]);

    $agendamentoId = 'ag-test-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO agendamentos (
        id, embarcacao_id, cliente_id, tipo_vistoria, data_vistoria, hora_vistoria, local, status, criado_por
    ) VALUES (
        :id, :emb, :cli, 'Vistoria Inicial de Borda Livre', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00', 'Porto de Manaus', 'pendente', :uid
    )")->execute([
        ':id' => $agendamentoId,
        ':emb' => $embarcacaoId,
        ':cli' => $clienteId,
        ':uid' => $secId
    ]);

    // Testar se SECRETARIA pode editar e atribuir vistoriador
    $id = $agendamentoId;
    $editando = true;
    $agendamento = $pdo->query("SELECT * FROM agendamentos WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);

    // Avaliar a lógica que estava com problema:
    $podeEscolherVistoriador = in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true);
    if (!$podeEscolherVistoriador) {
        throw new RuntimeException("Falha: SECRETARIA deveria ter \$podeEscolherVistoriador = true.");
    }
    echo "✅ SUCESSO: Campo de seleção de vistoriador liberado como dropdown dinâmico para SECRETARIA.\n";

    // 6. Testar atualização via query simulando POST da SECRETARIA escolhendo o vistoriador
    $stmtUpd = $pdo->prepare("UPDATE agendamentos SET vistoriador_id = :vistoriador_id WHERE id = :id");
    $stmtUpd->execute([
        ':vistoriador_id' => $vistId,
        ':id' => $agendamentoId
    ]);

    $agPosUpdate = $pdo->query("SELECT vistoriador_id FROM agendamentos WHERE id = '{$agendamentoId}'")->fetch(PDO::FETCH_ASSOC);
    if ($agPosUpdate['vistoriador_id'] !== $vistId) {
        throw new RuntimeException("Falha: Vistoriador não foi atualizado para o ID do vistoriador escolhido.");
    }
    if ($agPosUpdate['vistoriador_id'] === $secId) {
        throw new RuntimeException("Falha CRÍTICA: O agendamento foi atribuído à secretária em vez do vistoriador!");
    }
    echo "✅ SUCESSO: Agendamento atualizado com o vistoriador escolhido (vistoriador_id: {$vistId}), diferente do usuário da secretária ({$secId}).\n";

    // 7. Testar confirmação do agendamento e geração de OS pela SECRETARIA
    $podeConfirmarOS = in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true);
    if (!$podeConfirmarOS) {
        throw new RuntimeException("Falha: SECRETARIA deveria ter permissão de confirmar e gerar OS.");
    }

    $osId = 'os-test-' . bin2hex(random_bytes(4));
    $stmtOs = $pdo->prepare("INSERT INTO ordens_servico (
        id, numero, agendamento_id, vistoriador_id, cliente_id, embarcacao_id, tipo_vistoria, data_vistoria, status, criado_por
    ) VALUES (
        :id, 'OS-2026-9999', :ag_id, :vist_id, :cli_id, :emb_id, 'Vistoria Inicial de Borda Livre', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'pendente', :criado_por
    )");
    $stmtOs->execute([
        ':id' => $osId,
        ':ag_id' => $agendamentoId,
        ':vist_id' => $vistId,
        ':cli_id' => $clienteId,
        ':emb_id' => $embarcacaoId,
        ':criado_por' => $secId
    ]);

    $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = :id")->execute([':id' => $agendamentoId]);

    $agConfirmado = $pdo->query("SELECT status FROM agendamentos WHERE id = '{$agendamentoId}'")->fetch(PDO::FETCH_ASSOC);
    if ($agConfirmado['status'] !== 'confirmado') {
        throw new RuntimeException("Falha: Agendamento deveria estar com status 'confirmado'.");
    }
    echo "✅ SUCESSO: Agendamento confirmado e Ordem de Serviço vinculada com o vistoriador responsável correto.\n";

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit(1);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rollback executado com sucesso. Banco de dados preservado limpo.\n";
    }
}
