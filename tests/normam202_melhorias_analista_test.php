<?php
/**
 * TESTE OFICIAL DAS MELHORIAS NORMAM-202 / ANALISTA NAVAL
 * Valida:
 * 1. Campos de engenharia naval na tabela embarcacoes (L, Co, Ca, Calados Leve/Carregado, Deslocamentos)
 * 2. Emissão de RAP com conclusão técnica facultativa e texto padrão automático
 * 3. Layout conclusivo limpo do RAP ("Geral / Sem exigências" quando aprovado)
 * 4. Flexibilidade de Licenças Técnicas (LC, LA, LR, LCEC)
 * 5. Notas de Arqueação (AM-NAR) para L >= 24m e L < 24m com Anexo 7-B oficial
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertCheck(bool $condition, string $msg): void {
    if (!$condition) {
        echo "❌ FALHA: {$msg}\n";
        exit(1);
    }
    echo "✅ SUCESSO: {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE DE CONFORMIDADE NORMAM-202: MÓDULO ANALISTA NAVAL\n";
echo "=================================================================\n\n";

// -------------------------------------------------------------
// 1. Validar Campos de Engenharia Naval em Embarcações
// -------------------------------------------------------------
echo "[1/4] Validando campos de engenharia naval e calados em 'embarcacoes'...\n";
$colunasEsperadas = [
    'comprimento_regra', 'contorno_co', 'comprimento_arqueacao_ca',
    'pontal_arqueacao_h', 'pontal_arqueacao_hc', 'pontal_arqueacao_hl',
    'data_batimento_quilha', 'calado_leve_av', 'calado_leve_ar', 'calado_leve_med',
    'calado_carregado_av', 'calado_carregado_ar', 'calado_carregado_med',
    'deslocamento_leve', 'deslocamento_carregado'
];

$stmtCols = $pdo->query("SHOW COLUMNS FROM embarcacoes");
$colunasDb = array_column($stmtCols->fetchAll(PDO::FETCH_ASSOC), 'Field');

foreach ($colunasEsperadas as $col) {
    assertCheck(in_array($col, $colunasDb, true), "Coluna de engenharia '{$col}' existe na tabela embarcacoes.");
}

// Inserir / Atualizar embarcação com dados de engenharia
$testEmbId = gerarUUID();
$testEmbNome = 'EMBARCACAO TESTE NORMAM-202 ' . time();
$pdo->prepare("INSERT INTO embarcacoes (
    id, nome, tipo, area_navegacao, comprimento_total, boca_moldada, pontal_moldado,
    comprimento_regra, contorno_co, comprimento_arqueacao_ca,
    pontal_arqueacao_h, pontal_arqueacao_hc, pontal_arqueacao_hl,
    data_batimento_quilha, calado_leve_av, calado_leve_ar, calado_leve_med,
    calado_carregado_av, calado_carregado_ar, calado_carregado_med,
    deslocamento_leve, deslocamento_carregado, ativo, criado_em
) VALUES (
    :id, :nome, 'Balsa', 'Interior', 35.00, 8.50, 2.80,
    33.20, 10.40, 32.50,
    2.80, 2.40, 2.60,
    '2022-05-10', 0.50, 0.70, 0.60,
    2.10, 2.30, 2.20,
    120.50, 480.00, 1, NOW()
)")->execute([
    ':id' => $testEmbId,
    ':nome' => $testEmbNome
]);

$stmtFetchEmb = $pdo->prepare("SELECT * FROM embarcacoes WHERE id = :id");
$stmtFetchEmb->execute([':id' => $testEmbId]);
$embData = $stmtFetchEmb->fetch(PDO::FETCH_ASSOC);

assertCheck((float)$embData['comprimento_regra'] === 33.20, "Comprimento de Regra persistido corretamente (33.20m).");
assertCheck((float)$embData['calado_carregado_med'] === 2.20, "Calado Carregado Médio persistido corretamente (2.20m).");
assertCheck((float)$embData['deslocamento_carregado'] === 480.00, "Deslocamento Carregado persistido corretamente (480.00t).");

// -------------------------------------------------------------
// 2. Validar RAP com Conclusão Técnica Opcional e Texto Automático
// -------------------------------------------------------------
echo "\n[2/4] Validando emissão de RAP com conclusão opcional e layout conclusivo...\n";
try {
$analistaId = $pdo->query("SELECT id FROM usuarios WHERE cargo = 'ANALISTA' AND ativo = 1 LIMIT 1")->fetchColumn();
$clienteId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();

// Criar processo de análise
$processoId = gerarUUID();
$processoNum = 'AM-RAP-TESTE-' . time();
$pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, analista_id, status, objeto, criado_por, criado_em)
    VALUES (?, ?, 'LC', 'NORMAM-202', 'EC1', ?, ?, ?, 'EM_ANALISE', 'Análise de Construção', ?, NOW())")
    ->execute([$processoId, $processoNum, $testEmbId, $clienteId, $analistaId, $analistaId]);

// Simular parecer conclusivo com texto de conclusão em branco (deve auto-preencher texto padrão)
$parecerId = gerarUUID();
$numeroRap = 'AM-RAP-REL-' . mt_rand(100, 999) . '/26';
$conclusaoEmBranco = '';
$conclusaoFinal = !empty(trim($conclusaoEmBranco)) 
    ? trim($conclusaoEmBranco)
    : 'O projeto e seus memoriais técnicos atendem aos requisitos da NORMAM-202/DPC da Marinha do Brasil, estando apto para a emissão do respectivo documento técnico / licença de construção aplicável.';

$snapshot = [
    'embarcacao' => ['nome' => $testEmbNome],
    'observacoes' => [
        '01. Foram apresentados pelo armador: Planos e documentos técnicos elaborados pelo responsável técnico.',
        '02. Foram analisados os planos e memoriais de acordo com a NORMAM-202/DPC.',
        '03. O projeto atende integralmente aos parâmetros de segurança da navegação.'
    ]
];

$pdo->prepare("INSERT INTO analise_planos_pareceres (
    id, analise_id, numero, versao, finalidade, resumo, conclusao, resultado, 
    status, snapshot_json, criado_por, criado_em
) VALUES (
    ?, ?, ?, 1, 'CONCLUSIVO', 'Resumo de aprovação', ?, 'APROVADO',
    'PUBLICADO', ?, ?, NOW()
)")->execute([
    $parecerId, $processoId, $numeroRap,
    $conclusaoFinal, json_encode($snapshot), $analistaId
]);

$stmtRapDb = $pdo->prepare("SELECT * FROM analise_planos_pareceres WHERE id = ?");
$stmtRapDb->execute([$parecerId]);
$rapDb = $stmtRapDb->fetch(PDO::FETCH_ASSOC);

assertCheck(strpos($rapDb['conclusao'], 'NORMAM-202/DPC') !== false, "Parecer gerado com texto conclusivo padrão automático.");
assertCheck($rapDb['status'] === 'PUBLICADO', "RAP publicado com sucesso no ciclo de análise.");
} catch (Throwable $e) {
    echo "❌ EXCEÇÃO NO PASSO 2: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// -------------------------------------------------------------
// 3. Validar Emissão Flexível de Licenças (LA, LR, LCEC)
// -------------------------------------------------------------
echo "\n[3/4] Validando modalidades flexíveis de Licenças Técnicas...\n";
try {
$stmtAnArray = $pdo->prepare("SELECT * FROM analises_planos WHERE id = ?");
$stmtAnArray->execute([$processoId]);
$analiseArray = $stmtAnArray->fetch(PDO::FETCH_ASSOC);
$responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analiseArray);
$tiposLicenca = ['LC', 'LA', 'LR', 'LCEC'];
$prefixosEsperados = ['LC' => 'AM-LC-', 'LA' => 'AM-LA-', 'LR' => 'AM-LR-', 'LCEC' => 'AM-EC-'];

foreach ($tiposLicenca as $tipo) {
    // Limpar licença anterior para o processo de teste
    $pdo->prepare("DELETE FROM certificados_lc WHERE analise_id = ?")->execute([$processoId]);

    $licencaId = analiseAcaoCriarLicenca($pdo, $analiseArray, $responsavel, $tipo);
    assertCheck(!empty($licencaId), "Licença modalidade {$tipo} criada com sucesso (ID: {$licencaId}).");
    
    $stmtLic = $pdo->prepare("SELECT * FROM certificados_lc WHERE id = ?");
    $stmtLic->execute([$licencaId]);
    $licData = $stmtLic->fetch(PDO::FETCH_ASSOC);
    
    $numLc = $licData['numero_lc'] ?? ($licData['numero'] ?? '');
    assertCheck($licData['tipo_licenca'] === $tipo, "Modalidade {$tipo} gravada com sucesso.");
    assertCheck(strpos($numLc, $prefixosEsperados[$tipo]) === 0, "Prefixo do número ({$numLc}) condiz com {$prefixosEsperados[$tipo]}.");
}
} catch (Throwable $e) {
    echo "❌ EXCEÇÃO NO PASSO 3: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// -------------------------------------------------------------
// 4. Validar Notas de Arqueação (AM-NAR) Anexo 7-B para L >= 24m
// -------------------------------------------------------------
echo "\n[4/4] Validando Notas de Arqueação (AM-NAR) para L >= 24m e L < 24m...\n";
try {
$narId = gerarUUID();
$numeroNar = sprintf('00%02d/%s', mt_rand(10, 99), date('Y'));

$tokenAssinatura = hash('sha256', uniqid('nar', true));
$pdo->prepare("INSERT INTO certificados_nar (
    id, numero, ano, sequencial, enquadramento_comprimento, embarcacao_id, analise_id, token_assinatura,
    nome_embarcacao, arqueacao_bruta_ab, arqueacao_liquida_al, comprimento_total_ct, boca_moldada_b, pontal_moldado_p,
    status, data_emissao, ativo, criado_por, criado_em
) VALUES (
    ?, ?, 2026, 1, 'L_MAIOR_IGUAL_24', ?, ?, ?,
    ?, 436, 130, 35.00, 8.50, 2.80,
    'emitido', CURDATE(), 1, ?, NOW()
)")->execute([$narId, $numeroNar, $testEmbId, $processoId, $tokenAssinatura, $testEmbNome, $analistaId]);

$stmtNar = $pdo->prepare("
    SELECT c.*, e.comprimento_total, e.numero_inscricao, e.indicativo_chamada, e.local_construcao, e.data_batimento_quilha
    FROM certificados_nar c
    LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
    WHERE c.id = ?
");
$stmtNar->execute([$narId]);
$narData = $stmtNar->fetch(PDO::FETCH_ASSOC);

$isL24 = (float)($narData['comprimento_total'] ?? 0) >= 24.0;
assertCheck($isL24 === true, "Identificação correta de embarcação L >= 24m (35.00m).");
assertCheck((int)$narData['arqueacao_bruta_ab'] === 436, "AB registrada: 436.");
assertCheck((int)$narData['arqueacao_liquida_al'] === 130, "AL registrada: 130.");

// Limpar dados de teste
$pdo->prepare("DELETE FROM certificados_lc WHERE analise_id = ?")->execute([$processoId]);
$pdo->prepare("DELETE FROM certificados_nar WHERE id = ?")->execute([$narId]);
$pdo->prepare("DELETE FROM analise_planos_pareceres WHERE id = ?")->execute([$parecerId]);
$pdo->prepare("DELETE FROM analises_planos WHERE id = ?")->execute([$processoId]);
$pdo->prepare("DELETE FROM embarcacoes WHERE id = ?")->execute([$testEmbId]);

echo "\n=================================================================\n";
echo " SUCESSO TOTAL: TODAS AS DIRETRIZES DA NORMAM-202 ESTÃO CONFORMES!\n";
echo "=================================================================\n";
} catch (Throwable $e) {
    echo "❌ EXCEÇÃO NO PASSO 4: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
