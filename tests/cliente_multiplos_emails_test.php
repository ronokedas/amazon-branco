<?php
/**
 * Teste Automatizado: Múltiplos E-mails no Cadastro de Clientes / Proprietários
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== INICIANDO TESTE DE MÚLTIPLOS E-MAILS DE CLIENTES ===\n";

try {
    // 1. Criar um cliente de teste (Proprietário)
    $clienteId = '11111111-2222-3333-4444-555555555555';
    $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = :id")->execute([':id' => $clienteId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $clienteId]);

    $stmt = $pdo->prepare("
        INSERT INTO clientes (id, nome, email, telefone, perfil, status, criado_em)
        VALUES (:id, 'Armador Teste Múltiplos E-mails Ltda', 'principal.proprietario@teste.com', '(91) 98888-0000', 'PROPRIETARIO', 'ATIVO', NOW())
    ");
    $stmt->execute([':id' => $clienteId]);
    echo "✓ Cliente de teste criado com e-mail principal: principal.proprietario@teste.com\n";

    // 2. Salvar lista de e-mails adicionais (Despachante, Locatário, Projetista)
    $emailsTeste = [
        [
            'email' => 'despachante.naval@teste.com',
            'papel' => 'DESPACHANTE',
            'nome_contato' => 'Despachante Carlos',
            'recebe_alertas' => 1
        ],
        [
            'email' => 'locatario.balsa@teste.com',
            'papel' => 'LOCATARIO',
            'nome_contato' => 'Locatário Transportes Norte',
            'recebe_alertas' => 1
        ],
        [
            'email' => 'projetista.naval@teste.com',
            'papel' => 'PROJETISTA',
            'nome_contato' => 'Eng. Projetista Marcos',
            'recebe_alertas' => 0 // Não deve receber alertas automáticos
        ],
        [
            'email' => 'financeiro@teste.com',
            'papel' => 'FINANCEIRO',
            'nome_contato' => 'Financeiro Faturamento',
            'recebe_alertas' => 1
        ]
    ];

    $salvou = salvarEmailsCliente($pdo, $clienteId, $emailsTeste);
    assert($salvou === true, "salvarEmailsCliente deve retornar true");
    echo "✓ salvarEmailsCliente() gravou os 4 e-mails adicionais com sucesso.\n";

    // 3. Obter e-mails adicionais e verificar contagem e dados
    $emailsGravados = obterEmailsCliente($pdo, $clienteId);
    assert(count($emailsGravados) === 4, "Devem existir exatamente 4 e-mails gravados na tabela cliente_emails");
    $papeis = array_column($emailsGravados, 'papel');
    assert(in_array('DESPACHANTE', $papeis, true), "DESPACHANTE deve estar presente");
    assert(in_array('LOCATARIO', $papeis, true), "LOCATARIO deve estar presente");
    assert(in_array('PROJETISTA', $papeis, true), "PROJETISTA deve estar presente");
    assert(in_array('FINANCEIRO', $papeis, true), "FINANCEIRO deve estar presente");
    echo "✓ obterEmailsCliente() recuperou os 4 e-mails com papéis corretos.\n";

    // 4. Testar obterDestinatariosNotificaveisCliente
    // Deve conter o principal + os 3 marcados com recebe_alertas = 1 (ignorar o projetista que tem recebe_alertas=0)
    $destinatariosCli = obterDestinatariosNotificaveisCliente($pdo, $clienteId);
    assert(count($destinatariosCli) === 4, "Devem ser 4 destinatários notificáveis (1 principal + 3 adicionais com alerta=1)");
    $emailsDest = array_column($destinatariosCli, 'email');
    assert(in_array('principal.proprietario@teste.com', $emailsDest, true), "Principal deve constar como notificável");
    assert(in_array('despachante.naval@teste.com', $emailsDest, true), "Despachante deve constar como notificável");
    assert(in_array('locatario.balsa@teste.com', $emailsDest, true), "Locatário deve constar como notificável");
    assert(in_array('financeiro@teste.com', $emailsDest, true), "Financeiro deve constar como notificável");
    assert(!in_array('projetista.naval@teste.com', $emailsDest, true), "Projetista com recebe_alertas=0 NÃO deve constar");
    echo "✓ obterDestinatariosNotificaveisCliente() filtrou corretamente quem recebe alertas.\n";

    // 5. Testar obterDestinatariosNotificaveisEmbarcacao vinculando uma embarcação ao cliente
    $embId = '22222222-3333-4444-5555-666666666666';
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    $stmtEmb = $pdo->prepare("
        INSERT INTO embarcacoes (id, nome, numero_inscricao, proprietario_id, cliente_id, criado_em)
        VALUES (:id, 'BALSA TESTE MULTI EMAILS', 'PA-999999', :prop_id, :cli_id, NOW())
    ");
    $stmtEmb->execute([':id' => $embId, ':prop_id' => $clienteId, ':cli_id' => $clienteId]);

    $destinatariosEmb = obterDestinatariosNotificaveisEmbarcacao($pdo, $embId, 'BALSA TESTE MULTI EMAILS');
    assert(count($destinatariosEmb) === 4, "A embarcação deve herdar os destinatários do cliente/proprietário");
    echo "✓ obterDestinatariosNotificaveisEmbarcacao() resolveu com sucesso todos os destinatários da embarcação.\n";

    // 6. Atualizar lista removendo 2 e alterando 1
    $emailsAtualizados = [
        [
            'email' => 'despachante.naval@teste.com',
            'papel' => 'DESPACHANTE',
            'nome_contato' => 'Despachante Carlos Atualizado',
            'recebe_alertas' => 1
        ],
        [
            'email' => 'operacional.barcarena@teste.com',
            'papel' => 'OPERACIONAL',
            'nome_contato' => 'Supervisor Barcarena',
            'recebe_alertas' => 1
        ]
    ];
    salvarEmailsCliente($pdo, $clienteId, $emailsAtualizados);
    $emailsGravados2 = obterEmailsCliente($pdo, $clienteId);
    assert(count($emailsGravados2) === 2, "Agora devem existir exatamente 2 e-mails adicionais gravados");
    echo "✓ Atualização/sincronização de e-mails do cliente validada com sucesso.\n";

    // Limpeza
    $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = :id")->execute([':id' => $clienteId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $clienteId]);
    echo "✓ Dados de teste limpos com sucesso.\n";

    echo "\n=== TODOS OS TESTES DE MÚLTIPLOS E-MAILS FORAM APROVADOS! ===\n";

} catch (Exception $e) {
    echo "FALHA NO TESTE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
