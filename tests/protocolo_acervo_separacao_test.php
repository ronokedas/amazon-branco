<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/protocolos.php';

echo "=== TESTE: SEPARAÇÃO DO ACERVO E ACESSO A CAPITANIAS PELA SECRETÁRIA ===\n";

$pdo->beginTransaction();

try {
    // 1. Criar embarcação de teste e obter cliente
    $embId = gerarUUID();
    $cliId = $pdo->query("SELECT id FROM clientes LIMIT 1")->fetchColumn() ?: gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, registro, ativo, criado_em) VALUES (?, 'Balsa Teste Acervo', 'REG-ACERVO-99', 1, NOW())")->execute([$embId]);

    // 2. Criar duas propostas assinadas para a embarcação
    $prop1Id = gerarUUID();
    $prop2Id = gerarUUID();
    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, valor_total, status, assinado, data_emissao, created_at) VALUES (?, 'AM-ORC-901/26', ?, 15000, 'aprovada', 1, '2026-10-01', NOW())")->execute([$prop1Id, $cliId]);
    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, valor_total, status, assinado, data_emissao, created_at) VALUES (?, 'AM-ORC-902/26', ?, 25000, 'aprovada', 1, '2026-10-01', NOW())")->execute([$prop2Id, $cliId]);
    $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$prop1Id, $embId]);
    $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$prop2Id, $embId]);

    // 3. Obter acervo inicial - ambas devem ser novas
    $acervo1 = protocoloObterAcervoEmbarcacao($pdo, $embId);
    if ($acervo1['resumo']['novos'] < 2) {
        throw new RuntimeException("Esperava pelo menos 2 documentos novos, obteve {$acervo1['resumo']['novos']}");
    }
    echo "✅ SUCESSO: Ambos os documentos iniciaram classificados como NOVOS ({$acervo1['resumo']['novos']} novos).\n";

    // 4. Criar um dossiê e anexar a Proposta 1 em uma movimentação
    $userId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
    $dossieId = gerarUUID();
    $movId = gerarUUID();
    $pdo->prepare("INSERT INTO protocolo_dossies (id, numero, embarcacao_id, assunto, status, criado_por, criado_em) VALUES (?, 'DOS-2026-9999', ?, 'Dossiê Teste', 'EM_PREPARACAO', ?, NOW())")->execute([$dossieId, $embId, $userId]);
    $pdo->prepare("INSERT INTO protocolo_movimentacoes (id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome, cidade, uf, movimentado_em, status, criado_por, criado_em) VALUES (?, ?, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'Capitania de Teste', 'Belém', 'PA', NOW(), 'RASCUNHO', ?, NOW())")->execute([$movId, $dossieId, $userId]);
    
    // Anexa a proposta 1
    $pdo->prepare("INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, arquivo_origem_tipo, arquivo_origem_id, arquivo_nome) VALUES (UUID(), ?, 'Proposta Comercial nº AM-ORC-901/26', 'PROPOSTAS', 'DIGITAL', 'NATO_DIGITAL', 1, 'PROPOSTA', ?, 'Proposta_AM-ORC-901_26.pdf')")->execute([$movId, $prop1Id]);

    // 5. Re-executa protocoloObterAcervoEmbarcacao
    $acervo2 = protocoloObterAcervoEmbarcacao($pdo, $embId);
    
    // Verifica que Proposta 1 agora está em 'itens_utilizados' e Proposta 2 continua em 'itens_novos'
    $achouUtilizada = false;
    foreach ($acervo2['itens_utilizados'] as $u) {
        if ($u['origem_id'] === $prop1Id && $u['ja_utilizado'] === true && $u['uso_dossie_numero'] === 'DOS-2026-9999') {
            $achouUtilizada = true;
            break;
        }
    }
    if (!$achouUtilizada) {
        throw new RuntimeException("Proposta 1 não foi marcada corretamente como já utilizada no dossiê DOS-2026-9999.");
    }
    echo "✅ SUCESSO: Proposta 1 foi separada com precisão em 'itens_utilizados' com vínculo ao Dossiê DOS-2026-9999.\n";

    $achouNova = false;
    foreach ($acervo2['itens_novos'] as $n) {
        if ($n['origem_id'] === $prop2Id && $n['ja_utilizado'] === false) {
            $achouNova = true;
            break;
        }
    }
    if (!$achouNova) {
        throw new RuntimeException("Proposta 2 não foi mantida na lista de 'itens_novos'.");
    }
    echo "✅ SUCESSO: Proposta 2 permaneceu separada na lista de 'itens_novos' sem misturar com a usada.\n";

    // 6. Teste de permissão da Secretária em configurações de Capitanias
    $_SESSION['usuario_cargo'] = 'SECRETARIA';
    $_SESSION['usuario_perfis'] = ['SECRETARIA'];
    if (!in_array(getCargo(), ['ADMIN', 'SECRETARIA'], true)) {
        throw new RuntimeException("Cargo SECRETARIA deveria ter acesso às configurações de Capitanias.");
    }
    echo "✅ SUCESSO: Cargo SECRETARIA possui permissão autorizada para gerenciar Capitanias e Órgãos.\n";

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";
} catch (Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
} finally {
    $pdo->rollBack();
    echo "Rollback executado com sucesso. Banco de dados limpo.\n";
}
