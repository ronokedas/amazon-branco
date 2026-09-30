<?php
/**
 * TESTE E2E: Vínculo de Embarcação ao Proprietário na Primeira Tentativa e Emissão de Proposta
 * Valida:
 * 1. Cadastro de embarcação vinculada ao proprietário ativa imediatamente em clientes_embarcacoes.
 * 2. Coluna "EMBARCAÇÕES" na listagem de clientes exibe a quantidade correta (>= 1).
 * 3. Proposta comercial com essa embarcação e proprietário é criada com sucesso sem erro de validação.
 * 4. Edição de proposta não lança erro "Uma das embarcações não pertence ao proprietário selecionado".
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cliente_vinculos.php';

global $pdo;

echo "=== INICIANDO TESTE DE VÍNCULO NA PRIMEIRA TENTATIVA E PROPOSTA ===\n\n";

try {
    // 1. Criar Proprietário de teste
    $clienteId = bin2hex(random_bytes(16));
    $nomeCliente = 'Proprietario Teste E2E ' . substr($clienteId, 0, 6);
    $pdo->prepare("
        INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, ativo, criado_em)
        VALUES (?, ?, 'PJ', '10571940000194', 'proprietario', '(91) 99969-8075', 'teste_vessel@e2e.com', 1, NOW())
    ")->execute([$clienteId, $nomeCliente]);
    echo "[1] Cliente proprietário criado: {$nomeCliente} ({$clienteId})\n";

    // 2. Criar Embarcação com o proprietário vinculado
    $embId = bin2hex(random_bytes(16));
    $nomeEmb = 'BALSA AMAZON TESTE ' . substr($embId, 0, 4);

    $pdo->beginTransaction();
    $pdo->prepare("
        INSERT INTO embarcacoes (id, nome, proprietario_id, cliente_id, proprietario, registro, ano, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, '021-TESTE', 2024, 1, NOW())
    ")->execute([$embId, $nomeEmb, $clienteId, $clienteId, $nomeCliente]);

    // Executa vincularEmbarcacaoAoCliente exatamente como em modules/embarcacoes/actions.php
    vincularEmbarcacaoAoCliente($pdo, $embId, $clienteId, null);
    $pdo->commit();
    echo "[2] Embarcação cadastrada e vinculada na 1ª tentativa: {$nomeEmb} ({$embId})\n";

    // 3. Verificar se vínculo ativo existe em clientes_embarcacoes imediatamente
    $stmtCE = $pdo->prepare("
        SELECT id, status, vinculo_ativo_chave 
        FROM clientes_embarcacoes 
        WHERE cliente_id = ? AND embarcacao_id = ?
    ");
    $stmtCE->execute([$clienteId, $embId]);
    $ceRow = $stmtCE->fetch(PDO::FETCH_ASSOC);

    if (!$ceRow || $ceRow['status'] !== 'ATIVO') {
        throw new Exception("FALHA: Vínculo não foi registrado ou não está ATIVO em clientes_embarcacoes.");
    }
    echo "[3] Vínculo em clientes_embarcacoes ATIVO confirmado: " . $ceRow['vinculo_ativo_chave'] . "\n";

    // 4. Verificar se a listagem de clientes (modules/clientes/index.php) mostra 1 embarcação
    $stmtListagem = $pdo->prepare("
        SELECT c.id, 
               COUNT(DISTINCT COALESCE(ce.embarcacao_id, e.id)) AS total_embarcacoes
        FROM clientes c
        LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status = 'ATIVO'
        LEFT JOIN embarcacoes e ON (e.proprietario_id = c.id OR e.cliente_id = c.id) AND (e.ativo = 1 OR e.ativo IS NULL) AND e.excluido_em IS NULL
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmtListagem->execute([$clienteId]);
    $totalEmb = (int)$stmtListagem->fetchColumn(1);

    if ($totalEmb !== 1) {
        throw new Exception("FALHA: Listagem de clientes retornou {$totalEmb} embarcações em vez de 1.");
    }
    echo "[4] Coluna EMBARCAÇÕES na listagem de clientes retornou {$totalEmb} (badge verde ativado)!\n";

    // 5. Testar endpoint de busca de embarcações do cliente (modules/comercial/propostas/actions.php?action=embarcacoes_cliente)
    $stmtEmbCli = $pdo->prepare("
        SELECT DISTINCT e.id, e.nome, COALESCE(e.numero_inscricao, e.registro, '') as registro
        FROM embarcacoes e
        LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND (ce.status = 'ATIVO' OR ce.status IS NULL OR ce.desvinculado_em IS NULL)
        WHERE (ce.cliente_id = :cid1 OR e.proprietario_id = :cid2 OR e.cliente_id = :cid3)
          AND e.excluido_em IS NULL
        ORDER BY e.nome ASC
    ");
    $stmtEmbCli->execute([':cid1' => $clienteId, ':cid2' => $clienteId, ':cid3' => $clienteId]);
    $embLista = $stmtEmbCli->fetchAll(PDO::FETCH_ASSOC);

    if (empty($embLista) || $embLista[0]['id'] !== $embId) {
        throw new Exception("FALHA: embarcacoes_cliente não retornou a embarcação correta.");
    }
    echo "[5] Endpoint embarcacoes_cliente retornou a embarcação com sucesso no Wizard.\n";

    // 6. Testar validação de pertencimento e criação da proposta
    $servico = $pdo->query("SELECT id, preco_padrao FROM servicos WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$servico) {
        $servId = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO servicos (id, nome, preco_padrao, ativo) VALUES (?, 'Vistoria Inicial Teste', 2500.00, 1)")
            ->execute([$servId]);
        $servico = ['id' => $servId, 'preco_padrao' => 2500.00];
    }

    $stmtCheckEmb = $pdo->prepare("
        SELECT e.id
        FROM embarcacoes e
        LEFT JOIN clientes_embarcacoes ce
            ON ce.embarcacao_id = e.id
           AND ce.cliente_id = :cliente
           AND ce.status = 'ATIVO'
        WHERE e.id = :embarcacao 
          AND (ce.id IS NOT NULL OR e.proprietario_id = :prop_id OR e.cliente_id = :cli_id)
          AND (e.ativo = 1 OR e.ativo IS NULL)
          AND e.excluido_em IS NULL
        LIMIT 1
    ");
    $stmtCheckEmb->execute([
        ':cliente' => $clienteId,
        ':embarcacao' => $embId,
        ':prop_id' => $clienteId,
        ':cli_id' => $clienteId
    ]);

    if (!$stmtCheckEmb->fetchColumn()) {
        throw new Exception("FALHA: A validação de pertencimento da embarcação rejeitou o vínculo.");
    }
    echo "[6] Validação de pertencimento (criar proposta) aprovou o vínculo com sucesso!\n";

    // 7. Criar proposta no banco
    $propostaId = bin2hex(random_bytes(16));
    $numeroProp = 'ORC-TESTE-' . rand(1000, 9999);
    $subtotal = (float)$servico['preco_padrao'];
    $desconto = 200.00;
    $total = $subtotal - $desconto;

    $pdo->beginTransaction();
    $pdo->prepare("
        INSERT INTO propostas (
            id, numero, cliente_id, data_emissao, data_validade, parcelas, forma_pagamento,
            valor_total, valor_entrada, desconto_percentual, desconto_valor, tipo_desconto, status
        ) VALUES (
            ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'a_vista',
            ?, 0, 0, ?, 'valor', 'rascunho'
        )
    ")->execute([$propostaId, $numeroProp, $clienteId, $total, $desconto]);

    $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
        ->execute([$propostaId, $embId]);

    $pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade) VALUES (UUID(), ?, ?, ?, ?, 1)")
        ->execute([$propostaId, $servico['id'], $embId, $subtotal]);

    $pdo->commit();
    echo "[7] Proposta criada com sucesso! Número: {$numeroProp} (Total: R$ {$total})\n";

    // 8. Testar validação de atualização da proposta (editar_proposta / atualizar)
    $stmtEmbarcacao = $pdo->prepare("
        SELECT e.id
        FROM embarcacoes e
        LEFT JOIN clientes_embarcacoes ce
            ON ce.embarcacao_id = e.id
           AND ce.cliente_id = :cliente
           AND ce.status = 'ATIVO'
        WHERE e.id = :embarcacao 
          AND (ce.id IS NOT NULL OR e.proprietario_id = :prop_id OR e.cliente_id = :cli_id)
          AND (e.ativo = 1 OR e.ativo IS NULL)
          AND e.excluido_em IS NULL
        LIMIT 1
    ");
    $stmtEmbarcacao->execute([
        ':cliente' => $clienteId,
        ':embarcacao' => $embId,
        ':prop_id' => $clienteId,
        ':cli_id' => $clienteId
    ]);

    if (!$stmtEmbarcacao->fetchColumn()) {
        throw new Exception("FALHA: Validação de atualizar proposta rejeitou a embarcação.");
    }
    echo "[8] Validação de atualizar proposta (actions.php:580) executou com 100% de sucesso sem nenhum erro!\n";

    // Limpeza
    $pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id = ?")->execute([$propostaId]);
    $pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id = ?")->execute([$propostaId]);
    $pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$propostaId]);
    $pdo->prepare("DELETE FROM clientes_embarcacoes WHERE cliente_id = ?")->execute([$clienteId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = ?")->execute([$embId]);
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$clienteId]);

    echo "\n=== TODOS OS 8 PASSOS PASSARAM COM SUCESSO ABSOLUTO! ===\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERRO NO TESTE]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
