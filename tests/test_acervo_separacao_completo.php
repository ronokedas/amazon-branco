<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/protocolos.php';

echo "=== TESTANDO SEPARACAO DE ACERVO ===\n";

try {
$embId = 'emb_test_separacao_' . uniqid();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, ano, ativo) VALUES (?, 'Barco Teste Acervo', 'EMBARCACAO', 2024, 1)")->execute([$embId]);

$cliId = $pdo->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
$usrId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();

$numProp1 = 'AM-ORC-' . rand(1000, 9999) . '/26';
$numProp2 = 'AM-ORC-' . rand(1000, 9999) . '/26';

// 1. Criar Proposta Comercial assinada
$prop1Id = 'prop_test_1_' . uniqid();
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, data_emissao, status, assinado, valor_total, created_at) VALUES (?, ?, ?, '2026-09-28', 'aprovada', 1, 5000.00, NOW())")->execute([$prop1Id, $numProp1, $cliId]);
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$prop1Id, $embId]);

// 2. Criar Proposta Comercial 2
$prop2Id = 'prop_test_2_' . uniqid();
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, data_emissao, status, assinado, valor_total, created_at) VALUES (?, ?, ?, '2026-09-17', 'aprovada', 1, 3000.00, NOW())")->execute([$prop2Id, $numProp2, $cliId]);
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$prop2Id, $embId]);

// 3. Criar Dossiê não assinado (Rascunho / Em preparação) vinculado à Proposta 2
$dosId = 'dos_test_' . uniqid();
$numDos = 'AM-PROT-' . rand(1000, 9999) . '/26';
$pdo->prepare("INSERT INTO protocolo_dossies (id, numero, embarcacao_id, proposta_id, assunto, status, assinado, criado_por, criado_em) VALUES (?, ?, ?, ?, 'Dossie Teste Proposta 2', 'EM_PREPARACAO', 0, ?, NOW())")->execute([$dosId, $numDos, $embId, $prop2Id, $usrId]);

// 4. Criar Movimentação não assinada
$movId = 'mov_test_' . uniqid();
$pdo->prepare("INSERT INTO protocolo_movimentacoes (id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome, cidade, uf, status, assinado, criado_por, movimentado_em) VALUES (?, ?, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon', 'CAPITANIA', 'Capitania', 'Manaus', 'AM', 'RASCUNHO', 0, ?, NOW())")->execute([$movId, $dosId, $usrId]);

// 5. Vincular a Proposta 1 a essa movimentação
$pdo->prepare("INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, arquivo_origem_tipo, arquivo_origem_id, arquivo_nome) VALUES (UUID(), ?, 'Proposta Comercial nº AM-ORC-4/26', 'PROPOSTAS', 'DIGITAL', 'NATO_DIGITAL', 1, 'PROPOSTA', ?, 'Proposta_AM-ORC-4_26.pdf')")->execute([$movId, $prop1Id]);

// 6. Adicionar comprovante externo no dossiê
$comp1Id = 'comp_test_' . uniqid();
$pdo->prepare("INSERT INTO protocolo_comprovantes (id, dossie_id, tipo, nome_original, mime_type, tamanho_bytes, sha256, caminho, criado_por, criado_em) VALUES (?, ?, 'DOCUMENTO', 'AM-PROT-10-26-relatorio-dossie.pdf', 'application/pdf', 1024, 'abc123hash', 'storage/test.pdf', ?, NOW())")->execute([$comp1Id, $dosId, $usrId]);

// Obter acervo
$acervo = protocoloObterAcervoEmbarcacao($pdo, $embId);

echo "Total itens: " . count($acervo['itens']) . "\n";
echo "Novos: " . count($acervo['itens_novos']) . "\n";
echo "Utilizados: " . count($acervo['itens_utilizados']) . "\n";

foreach ($acervo['itens'] as $it) {
    echo "Item: {$it['titulo']} | Status: " . ($it['ja_utilizado'] ? 'UTILIZADO (' . $it['uso_dossie_numero'] . ')' : 'NOVO') . "\n";
}

// Asserções formais
if (count($acervo['itens']) !== 3) {
    throw new RuntimeException("Esperado 3 itens no acervo, obtido: " . count($acervo['itens']));
}
if (count($acervo['itens_utilizados']) !== 3) {
    throw new RuntimeException("Esperado 3 itens utilizados no acervo, obtido: " . count($acervo['itens_utilizados']));
}
if (count($acervo['itens_novos']) !== 0) {
    throw new RuntimeException("Esperado 0 itens novos no acervo, obtido: " . count($acervo['itens_novos']));
}

echo "TODAS AS ASSERCOES PASSARAM COM SUCESSO!\n";

} finally {
    // Limpeza garantida
    if (isset($dosId)) $pdo->prepare("DELETE FROM protocolo_comprovantes WHERE dossie_id = ?")->execute([$dosId]);
    if (isset($movId)) $pdo->prepare("DELETE FROM protocolo_movimentacao_itens WHERE movimentacao_id = ?")->execute([$movId]);
    if (isset($movId)) $pdo->prepare("DELETE FROM protocolo_movimentacoes WHERE id = ?")->execute([$movId]);
    if (isset($dosId)) $pdo->prepare("DELETE FROM protocolo_dossies WHERE id = ?")->execute([$dosId]);
    if (isset($embId)) $pdo->prepare("DELETE FROM propostas_embarcacoes WHERE embarcacao_id = ?")->execute([$embId]);
    if (isset($prop1Id, $prop2Id)) $pdo->prepare("DELETE FROM propostas WHERE id IN (?, ?)")->execute([$prop1Id, $prop2Id]);
    if (isset($embId)) $pdo->prepare("DELETE FROM embarcacoes WHERE id = ?")->execute([$embId]);
}
echo "=== FIM DO TESTE: OK ===\n";

