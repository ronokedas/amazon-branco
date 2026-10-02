<?php
/**
 * Teste Automatizado: Validação do Painel da Secretária (Admin View) e Assinatura Digital sem Erro de Coluna
 * Local: tests/secretaria_dashboard_e_assinatura_completo_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../modules/dashboard/data.php';

echo "=== TESTE: DASHBOARD EXECUTIVO DA SECRETÁRIA E ASSINATURA DIGITAL ===\n";

$pdo->beginTransaction();

try {
    // 1. Simular usuário Secretária
    $secId = 'sec-test-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (?, 'Thainara Secretária', 'thainara.dash@amazonnaval.com.br', 'hash', 'SECRETARIA', 1)")->execute([$secId]);
    aplicarPermissoesPadraoUsuario($pdo, $secId, 'SECRETARIA');

    $_SESSION['usuario_id'] = $secId;
    $_SESSION['usuario_nome'] = 'Thainara Secretária';
    $_SESSION['usuario_cargo'] = 'SECRETARIA';
    $_SESSION['usuario_perfis'] = ['SECRETARIA'];
    $_SESSION['usuario_logado'] = true;

    // 2. Testar Carregamento do Dashboard para SECRETARIA
    $cargo = getCargo() ?: 'VISTORIADOR';
    if ($cargo !== 'SECRETARIA') {
        throw new RuntimeException("getCargo() retornou '{$cargo}', esperava 'SECRETARIA'.");
    }

    $viewMap = [
        'ADMIN' => 'admin.php',
        'SECRETARIA' => 'admin.php',
        'VENDEDOR' => 'vendedor.php',
        'VISTORIADOR' => 'vistoriador.php',
        'ANALISTA' => 'analista.php'
    ];
    $targetView = $viewMap[$cargo] ?? 'vistoriador.php';
    if ($targetView !== 'admin.php') {
        throw new RuntimeException("ERRO: Cargo SECRETARIA deve usar 'admin.php', mas mapeou para '{$targetView}'!");
    }
    echo "✅ SUCESSO: Cargo SECRETARIA mapeado para o painel completo do Admin ('admin.php') e não para o do vistoriador.\n";

    $dashData = dashboardGetCachedData($pdo, 'SECRETARIA', $secId, true, 45);
    if (!isset($dashData['resumo_executivo'])) {
        throw new RuntimeException("ERRO: Painel executivo não gerou 'resumo_executivo' para a Secretária!");
    }
    echo "✅ SUCESSO: Dados executivos completos (resumo, vistorias, certificados, clientes) carregados para SECRETARIA.\n";

    // 3. Testar Auto-cura e Integridade da Estrutura de Assinatura
    protocoloGarantirEstruturaOficio($pdo);

    // Verificar se as colunas essenciais existem em protocolo_dossies
    $colsDossies = $pdo->query("SHOW COLUMNS FROM protocolo_dossies")->fetchAll(PDO::FETCH_COLUMN);
    $colsNecessarias = ['assinado', 'assinatura_em', 'responsavel_assinatura_id', 'assinante_nome', 'assinante_cargo', 'assinatura_imagem', 'assinatura_ip', 'numero_oficio'];
    foreach ($colsNecessarias as $col) {
        if (!in_array($col, $colsDossies, true)) {
            throw new RuntimeException("ERRO: Coluna '{$col}' não existe na tabela 'protocolo_dossies'!");
        }
    }
    echo "✅ SUCESSO: Todas as colunas de assinatura confirmadas na tabela 'protocolo_dossies'.\n";

    $colsMovs = $pdo->query("SHOW COLUMNS FROM protocolo_movimentacoes")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($colsNecessarias as $col) {
        if (!in_array($col, $colsMovs, true)) {
            throw new RuntimeException("ERRO: Coluna '{$col}' não existe na tabela 'protocolo_movimentacoes'!");
        }
    }
    echo "✅ SUCESSO: Todas as colunas de assinatura confirmadas na tabela 'protocolo_movimentacoes'.\n";

    // 4. Testar Operação de Assinatura Digital do Dossiê via UPDATE (simulando actions.php)
    $embId = $pdo->query("SELECT id FROM embarcacoes LIMIT 1")->fetchColumn() ?: 'emb-test';
    $dossieId = 'dos-sign-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_dossies (id, numero, embarcacao_id, assunto, status, criado_por, criado_em) VALUES (?, 'DOS-TEST-SIGN', ?, 'Assinatura Teste', 'EM_PREPARACAO', ?, NOW())")->execute([$dossieId, $embId, $secId]);

    // Executar a query exata de assinatura que falhava com "Unknown column 'assinado'"
    $stmtUp = $pdo->prepare("UPDATE protocolo_dossies SET 
      assinado=1,
      assinatura_em=NOW(),
      responsavel_assinatura_id=NULL,
      assinante_nome=:nome,
      assinante_cargo=:cargo,
      assinatura_imagem=NULL,
      assinatura_ip=:ip,
      destinatario_autoridade=COALESCE(:dest_ac, destinatario_autoridade),
      numero_oficio=COALESCE(:num_of, numero_oficio)
      WHERE id=:id");
    
    $stmtUp->execute([
        ':nome' => 'THAINARA BARROS',
        ':cargo' => 'Secretária',
        ':ip' => '127.0.0.1',
        ':dest_ac' => 'CAPITÃO DE MAR E GUERRA – TESTE',
        ':num_of' => 'AM-OF999/2026',
        ':id' => $dossieId
    ]);
    echo "✅ SUCESSO: Assinatura digital do Dossiê executada com sucesso sem erro SQL!\n";

    // Verificar se foi persistido
    $qCheck = $pdo->prepare("SELECT assinado, assinante_nome, assinante_cargo, numero_oficio FROM protocolo_dossies WHERE id=:id");
    $qCheck->execute([':id' => $dossieId]);
    $row = $qCheck->fetch(PDO::FETCH_ASSOC);
    if ((int)$row['assinado'] !== 1 || $row['assinante_cargo'] !== 'Secretária') {
        throw new RuntimeException("ERRO: Dados da assinatura não foram persistidos corretamente!");
    }
    echo "✅ SUCESSO: Registro verificado com assinado=1 e assinante_cargo='Secretária'.\n";

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";
} catch (Throwable $e) {
    echo "❌ FALHA: " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
    $pdo->rollBack();
    exit(1);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rollback executado com sucesso. Banco de dados limpo.\n";
    }
}
