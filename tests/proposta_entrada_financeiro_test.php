<?php
/**
 * Teste Automatizado: Entrada / Pagamento à Vista de Propostas Comerciais no Financeiro
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/financeiro_escritorios.php';
require_once __DIR__ . '/../modules/comercial/propostas/actions.php';

echo "=== TESTANDO ENTRADA / PAGAMENTO À VISTA DE PROPOSTAS NO FINANCEIRO ===\n\n";

$clienteId = 'teste-cli-' . substr(bin2hex(random_bytes(4)), 0, 8);
$escritorioId = '00000000-0000-4000-8000-000000000100';
$vendedorId = 'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5';

// 1. Criar cliente de teste
$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, ativo) VALUES (:id, 'Cliente Teste Entrada', '00011122233', 1)")
    ->execute([':id' => $clienteId]);

try {
    // --- CASO 1: Proposta com entrada parcial (Total R$ 6.000, Entrada R$ 2.000) ---
    echo "[1/3] Testando proposta com entrada parcial (Total R$ 6.000, Entrada R$ 2.000)...\n";
    $prop1Id = 'teste-prop-1-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $prop1Numero = 'AM-PROP-TEST-01';
    
    $prop1 = [
        'id' => $prop1Id,
        'numero' => $prop1Numero,
        'cliente_id' => $clienteId,
        'escritorio_id' => $escritorioId,
        'criado_por' => $vendedorId,
        'valor_total' => 6000.00,
        'valor_entrada' => 2000.00,
        'forma_pagamento' => 'pix',
        'parcelas' => 2,
    ];

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, escritorio_id, criado_por, valor_total, valor_entrada, forma_pagamento, parcelas, data_emissao, status, assinado)
        VALUES (:id, :num, :cli, :esc, :user, 6000.00, 2000.00, 'pix', 2, CURDATE(), 'assinada', 1)")
        ->execute([':id' => $prop1Id, ':num' => $prop1Numero, ':cli' => $clienteId, ':esc' => $escritorioId, ':user' => $vendedorId]);

    gerarEfeitosPropostaAssinada($pdo, $prop1, $vendedorId, true);

    $stmtLanc1 = $pdo->prepare("SELECT * FROM financeiro_lancamentos WHERE proposta_id = :pid");
    $stmtLanc1->execute([':pid' => $prop1Id]);
    $lanc1 = $stmtLanc1->fetch(PDO::FETCH_ASSOC);

    if (!$lanc1) throw new RuntimeException('Lançamento financeiro do Caso 1 não foi criado.');
    if ($lanc1['status'] !== 'PARCIAL') throw new RuntimeException("Status esperado: PARCIAL. Obtido: {$lanc1['status']}");
    if ((float)$lanc1['valor_original'] !== 6000.00) throw new RuntimeException("Valor original esperado: 6000.00. Obtido: {$lanc1['valor_original']}");
    if ((float)$lanc1['saldo_devedor'] !== 4000.00) throw new RuntimeException("Saldo devedor esperado: 4000.00. Obtido: {$lanc1['saldo_devedor']}");

    $stmtBaixas1 = $pdo->prepare("SELECT * FROM financeiro_historico_baixas WHERE lancamento_id = :lid");
    $stmtBaixas1->execute([':lid' => $lanc1['id']]);
    $baixas1 = $stmtBaixas1->fetchAll(PDO::FETCH_ASSOC);

    if (count($baixas1) !== 1) throw new RuntimeException('Deveria haver exatamente 1 baixa para a entrada.');
    if ((float)$baixas1[0]['valor_pago'] !== 2000.00) throw new RuntimeException("Valor da baixa esperado: 2000.00. Obtido: {$baixas1[0]['valor_pago']}");
    if ($baixas1[0]['forma_pagamento'] !== 'pix') throw new RuntimeException("Forma da baixa esperada: pix. Obtido: {$baixas1[0]['forma_pagamento']}");
    echo "  -> OK: Lançamento PARCIAL criado, saldo devedor R$ 4.000 e baixa de entrada de R$ 2.000 confirmada.\n";

    // --- CASO 2: Proposta 100% à vista (Total R$ 4.500, Entrada R$ 4.500) ---
    echo "[2/3] Testando proposta 100% quitada à vista (Total R$ 4.500, Entrada R$ 4.500)...\n";
    $prop2Id = 'teste-prop-2-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $prop2Numero = 'AM-PROP-TEST-02';

    $prop2 = [
        'id' => $prop2Id,
        'numero' => $prop2Numero,
        'cliente_id' => $clienteId,
        'escritorio_id' => $escritorioId,
        'criado_por' => $vendedorId,
        'valor_total' => 4500.00,
        'valor_entrada' => 4500.00,
        'forma_pagamento' => 'a_vista',
        'parcelas' => 1,
    ];

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, escritorio_id, criado_por, valor_total, valor_entrada, forma_pagamento, parcelas, data_emissao, status, assinado)
        VALUES (:id, :num, :cli, :esc, :user, 4500.00, 4500.00, 'a_vista', 1, CURDATE(), 'assinada', 1)")
        ->execute([':id' => $prop2Id, ':num' => $prop2Numero, ':cli' => $clienteId, ':esc' => $escritorioId, ':user' => $vendedorId]);

    gerarEfeitosPropostaAssinada($pdo, $prop2, $vendedorId, true);

    $stmtLanc2 = $pdo->prepare("SELECT * FROM financeiro_lancamentos WHERE proposta_id = :pid");
    $stmtLanc2->execute([':pid' => $prop2Id]);
    $lanc2 = $stmtLanc2->fetch(PDO::FETCH_ASSOC);

    if (!$lanc2) throw new RuntimeException('Lançamento financeiro do Caso 2 não foi criado.');
    if ($lanc2['status'] !== 'PAGO') throw new RuntimeException("Status esperado: PAGO. Obtido: {$lanc2['status']}");
    if ((float)$lanc2['saldo_devedor'] !== 0.00) throw new RuntimeException("Saldo devedor esperado: 0.00. Obtido: {$lanc2['saldo_devedor']}");

    $stmtBaixas2 = $pdo->prepare("SELECT * FROM financeiro_historico_baixas WHERE lancamento_id = :lid");
    $stmtBaixas2->execute([':lid' => $lanc2['id']]);
    $baixas2 = $stmtBaixas2->fetchAll(PDO::FETCH_ASSOC);

    if (count($baixas2) !== 1) throw new RuntimeException('Deveria haver exatamente 1 baixa integral.');
    if ((float)$baixas2[0]['valor_pago'] !== 4500.00) throw new RuntimeException("Valor da baixa esperado: 4500.00. Obtido: {$baixas2[0]['valor_pago']}");
    echo "  -> OK: Lançamento PAGO criado, saldo devedor R$ 0 e baixa integral de R$ 4.500 confirmada.\n";

    // --- CASO 3: Proposta sem entrada (Total R$ 3.000, Entrada R$ 0.00) ---
    echo "[3/3] Testando proposta sem entrada (Total R$ 3.000, Entrada R$ 0.00)...\n";
    $prop3Id = 'teste-prop-3-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $prop3Numero = 'AM-PROP-TEST-03';

    $prop3 = [
        'id' => $prop3Id,
        'numero' => $prop3Numero,
        'cliente_id' => $clienteId,
        'escritorio_id' => $escritorioId,
        'criado_por' => $vendedorId,
        'valor_total' => 3000.00,
        'valor_entrada' => 0.00,
        'forma_pagamento' => 'parcelado',
        'parcelas' => 3,
    ];

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, escritorio_id, criado_por, valor_total, valor_entrada, forma_pagamento, parcelas, data_emissao, status, assinado)
        VALUES (:id, :num, :cli, :esc, :user, 3000.00, 0.00, 'parcelado', 3, CURDATE(), 'assinada', 1)")
        ->execute([':id' => $prop3Id, ':num' => $prop3Numero, ':cli' => $clienteId, ':esc' => $escritorioId, ':user' => $vendedorId]);

    gerarEfeitosPropostaAssinada($pdo, $prop3, $vendedorId, true);

    $stmtLanc3 = $pdo->prepare("SELECT * FROM financeiro_lancamentos WHERE proposta_id = :pid");
    $stmtLanc3->execute([':pid' => $prop3Id]);
    $lanc3 = $stmtLanc3->fetch(PDO::FETCH_ASSOC);

    if (!$lanc3) throw new RuntimeException('Lançamento financeiro do Caso 3 não foi criado.');
    if ($lanc3['status'] !== 'PENDENTE') throw new RuntimeException("Status esperado: PENDENTE. Obtido: {$lanc3['status']}");
    if ((float)$lanc3['saldo_devedor'] !== 3000.00) throw new RuntimeException("Saldo devedor esperado: 3000.00. Obtido: {$lanc3['saldo_devedor']}");

    $stmtBaixas3 = $pdo->prepare("SELECT COUNT(*) FROM financeiro_historico_baixas WHERE lancamento_id = :lid");
    $stmtBaixas3->execute([':lid' => $lanc3['id']]);
    if ((int)$stmtBaixas3->fetchColumn() !== 0) throw new RuntimeException('Não deveria haver baixas para proposta sem entrada.');
    echo "  -> OK: Lançamento PENDENTE criado com saldo integral e zero baixas.\n";

    // --- TESTE DE CÁLCULO DE RECEITA NO FINANCEIRO ---
    echo "\nValidando agregação de receitas no módulo Financeiro (index.php)...\n";
    $sqlTotais = "
        SELECT
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' THEN
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN l.status = 'PAGO' THEN l.valor_original ELSE 0 END
            ELSE 0 END), 0) AS total_receitas,
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' AND l.status IN ('PENDENTE', 'PARCIAL') THEN l.saldo_devedor ELSE 0 END), 0) AS total_a_receber
        FROM financeiro_lancamentos l
        LEFT JOIN (
            SELECT lancamento_id, SUM(valor_pago) AS total_baixado
            FROM financeiro_historico_baixas
            GROUP BY lancamento_id
        ) b ON b.lancamento_id = l.id
        WHERE l.ativo = 1 AND l.status != 'CANCELADO' AND l.cliente_id = :cid
    ";
    $stmtT = $pdo->prepare($sqlTotais);
    $stmtT->execute([':cid' => $clienteId]);
    $totaisCli = $stmtT->fetch(PDO::FETCH_ASSOC);

    // Esperado para o cliente:
    // Receitas pagas = 2000 (prop1) + 4500 (prop2) = 6500.00
    // A receber = 4000 (prop1) + 3000 (prop3) = 7000.00
    $recEsperada = 6500.00;
    $aRecEsperado = 7000.00;

    if (abs((float)$totaisCli['total_receitas'] - $recEsperada) > 0.01) {
        throw new RuntimeException("Total receitas esperado: {$recEsperada}. Obtido: {$totaisCli['total_receitas']}");
    }
    if (abs((float)$totaisCli['total_a_receber'] - $aRecEsperado) > 0.01) {
        throw new RuntimeException("Total a receber esperado: {$aRecEsperado}. Obtido: {$totaisCli['total_a_receber']}");
    }
    echo "  -> OK: Total receitas realizada (R$ 6.500,00) e total a receber pendente (R$ 7.000,00) 100% corretos!\n";

} finally {
    // Limpeza atômica dos dados de teste
    $pdo->prepare("DELETE FROM financeiro_historico_baixas WHERE lancamento_id IN (SELECT id FROM financeiro_lancamentos WHERE cliente_id = :cid)")->execute([':cid' => $clienteId]);
    $pdo->prepare("DELETE FROM financeiro_lancamentos WHERE cliente_id = :cid")->execute([':cid' => $clienteId]);
    $pdo->prepare("DELETE FROM propostas WHERE cliente_id = :cid")->execute([':cid' => $clienteId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :cid")->execute([':cid' => $clienteId]);
}

echo "\n=== TODOS OS TESTES DE ENTRADA / PAGAMENTO À VISTA FORAM APROVADOS COM SUCESSO! ===\n";
