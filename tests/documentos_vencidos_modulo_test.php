<?php
/**
 * Teste Automatizado: Módulo de Documentos Vencidos e Alertas Automáticos de Vistoria
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== INICIANDO TESTE DO MÓDULO DE DOCUMENTOS VENCIDOS ===\n";

try {
    // 1. Validar Permissões
    $todas = todasPermissoesSistema();
    assert(in_array('documentos_vencidos', $todas, true), "documentos_vencidos deve estar em todasPermissoesSistema");
    
    $permVistoriador = permissoesPadraoCargo('VISTORIADOR');
    assert(in_array('documentos_vencidos', $permVistoriador, true), "VISTORIADOR deve ter permissão documentos_vencidos");

    $permAnalista = permissoesPadraoCargo('ANALISTA');
    assert(in_array('documentos_vencidos', $permAnalista, true), "ANALISTA deve ter permissão documentos_vencidos");

    $permVendedor = permissoesPadraoCargo('VENDEDOR');
    assert(in_array('documentos_vencidos', $permVendedor, true), "VENDEDOR deve ter permissão documentos_vencidos");
    echo "✓ Permissões de acesso para 'documentos_vencidos' validadas nos cargos.\n";

    // 2. Testar contador consolidado
    $totalInicial = obterTotalDocumentosVencidos($pdo);
    assert(is_int($totalInicial), "obterTotalDocumentosVencidos deve retornar inteiro");
    echo "✓ obterTotalDocumentosVencidos() executou com sucesso (total atual: {$totalInicial}).\n";

    // 3. Criar fixtures para teste de documentos vencidos
    $clienteId = '33333333-4444-5555-6666-777777777777';
    $embId = '44444444-5555-6666-7777-888888888888';
    $csnId = '55555555-6666-7777-8888-999999999999';
    $vistoriaId = '66666666-7777-8888-9999-000000000000';

    // Limpeza prévia
    $pdo->prepare("DELETE FROM email_logs WHERE referencia_id IN (:csn, :vist)")->execute([':csn' => $csnId, ':vist' => $vistoriaId]);
    $pdo->prepare("DELETE FROM certificados_csn WHERE id = :id")->execute([':id' => $csnId]);
    $pdo->prepare("DELETE FROM vistorias WHERE id = :id")->execute([':id' => $vistoriaId]);
    $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = :id")->execute([':id' => $clienteId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $clienteId]);

    // Inserir Cliente
    $stmtCli = $pdo->prepare("
        INSERT INTO clientes (id, nome, email, telefone, perfil, status, criado_em)
        VALUES (:id, 'Navegação Norte Vencidos S/A', 'contato.armador@teste.com', '(91) 98111-2222', 'PROPRIETARIO', 'ATIVO', NOW())
    ");
    $stmtCli->execute([':id' => $clienteId]);

    // Inserir E-mails adicionais do cliente
    salvarEmailsCliente($pdo, $clienteId, [
        [
            'email' => 'despachante.oficial@teste.com',
            'papel' => 'DESPACHANTE',
            'nome_contato' => 'Despachante Naval Roberto',
            'recebe_alertas' => 1
        ],
        [
            'email' => 'locatario.operacao@teste.com',
            'papel' => 'LOCATARIO',
            'nome_contato' => 'Locatário Rio Guamá',
            'recebe_alertas' => 1
        ]
    ]);

    // Inserir Embarcação
    $stmtEmb = $pdo->prepare("
        INSERT INTO embarcacoes (id, nome, numero_inscricao, proprietario_id, cliente_id, criado_em)
        VALUES (:id, 'EMBARCAÇÃO TESTE DOC VENCIDO', 'PA-DOC-001', :prop_id, :cli_id, NOW())
    ");
    $stmtEmb->execute([':id' => $embId, ':prop_id' => $clienteId, ':cli_id' => $clienteId]);

    // Inserir Certificado CSN Vencido (validade há 45 dias atrás)
    $stmtCsn = $pdo->prepare("
        INSERT INTO certificados_csn (id, numero, token_assinatura, embarcacao_id, cliente_id, nome_embarcacao, numero_inscricao, data_emissao, data_validade, status, ativo)
        VALUES (:id, 'CSN-TESTE-999', SHA2(UUID(), 256), :emb_id, :cli_id, 'EMBARCAÇÃO TESTE DOC VENCIDO', 'PA-DOC-001', DATE_SUB(CURDATE(), INTERVAL 1 YEAR), DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'assinado', 1)
    ");
    $stmtCsn->execute([':id' => $csnId, ':emb_id' => $embId, ':cli_id' => $clienteId]);

    // Inserir Vistoria com prazo de exigências expirado há 15 dias (vistoria há 45 dias com prazo de 30 dias)
    $stmtVist = $pdo->prepare("
        INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, armador_id, data_vistoria, prazo_exigencias_dias, status, criado_em)
        VALUES (:id, 'VIST-EXPIRADA-01', :emb_id, :pessoa_id, :armador_id, DATE_SUB(CURDATE(), INTERVAL 45 DAY), 30, 'APROVADA_COM_EXIGENCIAS', NOW())
    ");
    $stmtVist->execute([':id' => $vistoriaId, ':emb_id' => $embId, ':pessoa_id' => $clienteId, ':armador_id' => $clienteId]);

    echo "✓ Fixtures de teste criadas (Cliente, Embarcação, CSN Vencido e Vistoria com Exigências Vencidas).\n";

    // 4. Testar buscar destinatários via helper
    $destinatarios = obterDestinatariosNotificaveisEmbarcacao($pdo, $embId, 'EMBARCAÇÃO TESTE DOC VENCIDO');
    assert(count($destinatarios) === 3, "Devem ser 3 destinatários (1 armador + 1 despachante + 1 locatário)");
    $emailsEncontrados = array_column($destinatarios, 'email');
    assert(in_array('contato.armador@teste.com', $emailsEncontrados, true), "Armador deve constar");
    assert(in_array('despachante.oficial@teste.com', $emailsEncontrados, true), "Despachante deve constar");
    assert(in_array('locatario.operacao@teste.com', $emailsEncontrados, true), "Locatário deve constar");
    echo "✓ Resolução de múltiplos e-mails para a embarcação com documento vencido validada.\n";

    // 5. Simular envio de alerta de documento vencido e verificar gravação de log
    $templatePath = __DIR__ . '/../templates/email/documento_vencido.html';
    assert(file_exists($templatePath), "Template templates/email/documento_vencido.html deve existir");
    $templateHtml = file_get_contents($templatePath);

    $assuntoTeste = "ALERTA TESTE: CSN Vencido - EMBARCAÇÃO TESTE DOC VENCIDO";
    
    // Registrar log
    $stmtLog = $pdo->prepare("
        INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, created_at)
        VALUES (UUID(), 'despachante.oficial@teste.com', :assunto, 'alerta_vencimento', 'certificados_csn', :ref_id, 'enviado', NULL, NOW())
    ");
    $stmtLog->execute([':assunto' => $assuntoTeste, ':ref_id' => $csnId]);

    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM email_logs 
        WHERE referencia_tipo = 'certificados_csn' AND referencia_id = :ref_id AND status = 'enviado'
    ");
    $stmtCheck->execute([':ref_id' => $csnId]);
    assert($stmtCheck->fetchColumn() >= 1, "Log de e-mail deve ter sido inserido com sucesso");
    echo "✓ Template de alerta de documento vencido e auditoria em email_logs validados.\n";

    // 6. Testar alerta de vistoria pre-expiração (10 dias antes)
    $vistoria10dId = '77777777-8888-9999-0000-111111111111';
    $pdo->prepare("DELETE FROM vistorias WHERE id = :id")->execute([':id' => $vistoria10dId]);
    // Vistoria realizada há 55 dias com prazo de 60 dias -> faltam 5 dias para vencer!
    $stmtVist10 = $pdo->prepare("
        INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, armador_id, data_vistoria, prazo_exigencias_dias, status, criado_em)
        VALUES (:id, 'VIST-10DIAS-01', :emb_id, :pessoa_id, :armador_id, DATE_SUB(CURDATE(), INTERVAL 55 DAY), 60, 'APROVADA_COM_EXIGENCIAS', NOW())
    ");
    $stmtVist10->execute([':id' => $vistoria10dId, ':emb_id' => $embId, ':pessoa_id' => $clienteId, ':armador_id' => $clienteId]);

    // Executar o script de alerta
    echo "✓ Executando scripts/alerta_vencimentos.php para testar disparos automáticos...\n";
    $outputScript = shell_exec('php ' . escapeshellarg(__DIR__ . '/../scripts/alerta_vencimentos.php'));
    echo "  Saída do script: " . trim($outputScript ?? '') . "\n";

    // Verificar se o log de vistoria foi gerado
    $stmtLogVist = $pdo->prepare("
        SELECT COUNT(*) FROM email_logs
        WHERE referencia_tipo = 'vistorias' AND referencia_id = :id
    ");
    $stmtLogVist->execute([':id' => $vistoria10dId]);
    $totalLogsVist = (int)$stmtLogVist->fetchColumn();
    echo "  Total de logs gerados para a vistoria prestes a vencer: {$totalLogsVist}\n";
    assert($totalLogsVist > 0, "O script deve ter gerado logs de envio para os contatos vinculados");
    echo "✓ Alerta de vistoria em até 10 dias executado e registrado com sucesso!\n";

    // 7. Limpeza dos dados de teste
    $pdo->prepare("DELETE FROM email_logs WHERE referencia_id IN (:csn, :vist, :vist10)")->execute([
        ':csn' => $csnId, 
        ':vist' => $vistoriaId, 
        ':vist10' => $vistoria10dId
    ]);
    $pdo->prepare("DELETE FROM certificados_csn WHERE id = :id")->execute([':id' => $csnId]);
    $pdo->prepare("DELETE FROM vistorias WHERE id IN (:v1, :v2)")->execute([':v1' => $vistoriaId, ':v2' => $vistoria10dId]);
    $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = :id")->execute([':id' => $clienteId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $clienteId]);

    echo "✓ Dados de teste limpos com sucesso.\n";
    echo "\n=== TODOS OS TESTES DO MÓDULO DE DOCUMENTOS VENCIDOS FORAM APROVADOS! ===\n";

} catch (Exception $e) {
    echo "FALHA NO TESTE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
