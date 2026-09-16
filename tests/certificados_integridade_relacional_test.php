<?php
/**
 * Teste de Integridade Relacional dos Certificados (Etapa 0)
 * Verifica:
 * 1. Presença das colunas embarcacao_id e cliente_id em todas as 6 tabelas de certificados.
 * 2. Existência e funcionamento das Foreign Keys com ON DELETE RESTRICT.
 * 3. Preservação imutável do registro histórico de CSN existente (backfill com 100% de integridade e snapshot intacto).
 * 4. Bloqueio de deleção da entidade principal (embarcação/cliente) quando houver certificado vinculado.
 * 5. Código-fonte dos formulários e wizards contendo bindings e campos de seleção de entidade principal.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertRelacional(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException("FALHA DE INTEGRIDADE: " . $mensagem);
    }
}

echo "=== INICIANDO TESTES DE INTEGRIDADE RELACIONAL (ETAPA 0) ===\n";

// 1. Verificação de Colunas em todas as 6 tabelas
$tabelas = [
    'certificados_csn',
    'certificados_cnbl',
    'certificados_cnarq',
    'certificados_lp',
    'certificados_lc',
    'certificados_cht'
];

foreach ($tabelas as $tab) {
    $stmtCols = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = :tab 
          AND COLUMN_NAME IN ('embarcacao_id', 'cliente_id')
    ");
    $stmtCols->execute([':tab' => $tab]);
    $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    assertRelacional(
        in_array('embarcacao_id', $cols),
        "Tabela {$tab} não possui a coluna 'embarcacao_id'."
    );
    assertRelacional(
        in_array('cliente_id', $cols),
        "Tabela {$tab} não possui a coluna 'cliente_id'."
    );
    echo "  [OK] Colunas relacionais presentes em: {$tab}\n";
}

// 2. Verificação de Foreign Keys no INFORMATION_SCHEMA
foreach ($tabelas as $tab) {
    $stmtFk = $pdo->prepare("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :tab
          AND REFERENCED_TABLE_NAME IS NOT NULL
          AND COLUMN_NAME IN ('embarcacao_id', 'cliente_id')
    ");
    $stmtFk->execute([':tab' => $tab]);
    $fks = $stmtFk->fetchAll(PDO::FETCH_ASSOC);

    $refTables = [];
    foreach ($fks as $fk) {
        $refTables[$fk['COLUMN_NAME']] = $fk['REFERENCED_TABLE_NAME'];
    }

    assertRelacional(
        isset($refTables['embarcacao_id']) && $refTables['embarcacao_id'] === 'embarcacoes',
        "Tabela {$tab} não possui FK de embarcacao_id para embarcacoes."
    );
    assertRelacional(
        isset($refTables['cliente_id']) && $refTables['cliente_id'] === 'clientes',
        "Tabela {$tab} não possui FK de cliente_id para clientes."
    );
    echo "  [OK] Foreign Keys ativas em: {$tab} (-> embarcacoes, -> clientes)\n";
}

// 3. Verificação do Certificado Histórico Existente (Backfill & Imutabilidade)
$stmtCsnHist = $pdo->query("SELECT * FROM certificados_csn WHERE id = 'faa23877-c468-4114-9182-3b6157402f0f'");
$csnHist = $stmtCsnHist->fetch(PDO::FETCH_ASSOC);

assertRelacional(!empty($csnHist), "Certificado histórico CSN não encontrado.");
assertRelacional($csnHist['numero'] === 'AM-CSN-1/26', "Número do certificado histórico foi corrompido.");
assertRelacional($csnHist['nome_embarcacao'] === 'barcoteste14', "Snapshot do nome da embarcação foi alterado.");
assertRelacional($csnHist['status'] === 'assinado', "Status do certificado histórico não é 'assinado'.");
assertRelacional((int)$csnHist['assinado'] === 1, "Flag assinado do certificado histórico foi desmarcada.");
assertRelacional($csnHist['embarcacao_id'] === '317ba743-7aa6-4d66-a845-2d4670f126f0', "Backfill da embarcacao_id incorreto no certificado histórico.");
assertRelacional($csnHist['cliente_id'] === '1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', "Backfill do cliente_id incorreto no certificado histórico.");
echo "  [OK] Registro histórico CSN AM-CSN-1/26 preservado intacto com backfill 100% consistente.\n";

// 4. Teste Comportamental de Foreign Key (RESTRICT) em Transação
$pdo->beginTransaction();
try {
    $tempClienteId = '00000000-0000-0000-0000-0000000000c1';
    $tempEmbarcacaoId = '00000000-0000-0000-0000-0000000000e1';
    $tempCertId = '00000000-0000-0000-0000-000000000001';

    // Inserir cliente e embarcação de teste
    $pdo->prepare("INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, status) VALUES (:id, 'Cliente Teste Integridade', 'PJ', '99.999.999/0001-99', 'ATIVO')")
        ->execute([':id' => $tempClienteId]);

    $pdo->prepare("INSERT INTO embarcacoes (id, nome, proprietario_id, ativo) VALUES (:id, 'Embarcação Teste Integridade', :cli, 1)")
        ->execute([':id' => $tempEmbarcacaoId, ':cli' => $tempClienteId]);

    // Inserir certificado LP com vínculo relacional
    $pdo->prepare("
        INSERT INTO certificados_lp (
            id, numero_lp, token_assinatura, embarcacao_id, cliente_id,
            tipo_licenca, nome_embarcacao, data_emissao, status, ativo
        ) VALUES (
            :id, 'AM-LP-TESTE-99/26', 'token-teste-integridade', :emb, :cli,
            'construcao', 'Embarcação Teste Integridade Snapshot', CURDATE(), 'rascunho', 1
        )
    ")->execute([
        ':id' => $tempCertId,
        ':emb' => $tempEmbarcacaoId,
        ':cli' => $tempClienteId
    ]);

    // Testar bloqueio de deleção da embarcação (RESTRICT)
    $restricaoEmbarcacaoOk = false;
    try {
        $pdo->exec("DELETE FROM embarcacoes WHERE id = '{$tempEmbarcacaoId}'");
    } catch (PDOException $e) {
        $restricaoEmbarcacaoOk = true;
    }
    assertRelacional($restricaoEmbarcacaoOk, "Foreign Key RESTRICT não impediu a exclusão da embarcação com certificado ativo!");
    echo "  [OK] FK RESTRICT impediu com sucesso a exclusão de embarcação com certificado vinculado.\n";

    // Testar bloqueio de deleção do cliente (RESTRICT)
    $restricaoClienteOk = false;
    try {
        $pdo->exec("DELETE FROM clientes WHERE id = '{$tempClienteId}'");
    } catch (PDOException $e) {
        $restricaoClienteOk = true;
    }
    assertRelacional($restricaoClienteOk, "Foreign Key RESTRICT não impediu a exclusão do cliente com certificado ativo!");
    echo "  [OK] FK RESTRICT impediu com sucesso a exclusão de cliente com certificado vinculado.\n";

} finally {
    // Reverter transação de teste
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
echo "  [OK] Teste comportamental de integridade relacional revertido sem deixar resíduos.\n";

// 5. Verificação Estática de Código nos Formulários e Ações
$arquivosVerificar = [
    'modules/certificados/wizard_step2.php' => [':embarcacao_id', ':cliente_id'],
    'modules/certificados/wizard_cht.php'   => ['cliente_select', 'embarcacao_id', 'cliente_id'],
    'modules/documentacao/certificados/actions.php' => [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/cnbl/actions.php' => [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/cnarq/actions.php'=> [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/lp/actions.php'   => [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/lc/actions.php'   => [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/cht/actions.php'  => [':embarcacao_id', ':cliente_id'],
    'modules/documentacao/lp/form.php'      => ['name="embarcacao_id"'],
    'modules/documentacao/lc/form.php'      => ['name="embarcacao_id"'],
    'modules/documentacao/cnarq/form.php'   => ['name="embarcacao_id"'],
    'modules/documentacao/cht/form.php'     => ['cliente_select'],
    'modules/analises_planos/actions.php'   => [':embarcacao', ':cliente']
];

foreach ($arquivosVerificar as $arquivo => $termos) {
    $conteudo = file_get_contents(__DIR__ . '/../' . $arquivo);
    foreach ($termos as $termo) {
        assertRelacional(
            str_contains($conteudo, $termo),
            "Arquivo {$arquivo} não contém o termo esperado '{$termo}'."
        );
    }
    echo "  [OK] Verificação de código em {$arquivo}: todos os termos presentes.\n";
}

echo "\n>>> TODOS OS TESTES DA ETAPA 0 PASSARAM COM SUCESSO! <<<\n";
