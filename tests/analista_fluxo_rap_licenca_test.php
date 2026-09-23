<?php
/**
 * Teste End-to-End: Fluxo Completo do Analista Naval (RAP -> Exigências -> Parecer Conclusivo -> Licenças LC/LA/LR/LCEC)
 * Valida a regra de bloqueio com exigências pendentes, liberação com 0 pendências, emissão das 4 licenças e geração de PDF.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../includes/emissao_certificados.php';

function assertTest(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE E2E: FLUXO DO ANALISTA NAVAL - RAP & LICENÇAS NORMAM-202\n";
echo "=================================================================\n\n";

// 1. Validar Analista de Teste
echo "1. Verificando perfil do analista (analista@teste.com)...\n";
$stmt = $pdo->prepare("SELECT u.id, u.nome, u.email, u.cargo, r.id as responsavel_id, r.registro_profissional
    FROM usuarios u
    LEFT JOIN responsaveis_assinatura r ON r.usuario_id = u.id AND r.ativo = 1
    WHERE u.email = 'analista@teste.com' LIMIT 1");
$stmt->execute();
$analista = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($analista), "Usuário analista@teste.com encontrado");
assertTest(!empty($analista['responsavel_id']), "Analista possui registro técnico (CREA) habilitado para assinatura");

$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_cargo'] = 'ANALISTA';
$_SESSION['usuario_nome'] = $analista['nome'];

// 2. Preparar Embarcação e Cliente de Teste
echo "\n2. Preparando cliente e embarcação de teste...\n";
$clienteId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();
if (!$clienteId) {
    $clienteId = gerarUUID();
    $pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, ativo, criado_em) VALUES (?, 'Armador Teste Naval', '12.345.678/0001-90', 1, NOW())")
        ->execute([$clienteId]);
}

$stmtEmb = $pdo->prepare("SELECT id FROM embarcacoes WHERE nome = 'B/M TESTE ANALISTA NAVAL' LIMIT 1");
$stmtEmb->execute();
$embarcacaoId = $stmtEmb->fetchColumn();
if (!$embarcacaoId) {
    $embarcacaoId = gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, cliente_id, nome, tipo, material_casco, comprimento_total, boca_moldada, pontal_moldado, calado_maximo_m, porte_bruto, tipo_navegacao, area_navegacao, ativo, criado_em)
        VALUES (?, ?, 'B/M TESTE ANALISTA NAVAL', 'Empurrador', 'Aço Naval', 24.50, 6.80, 2.50, 1.80, 150.00, 'Interior', 'Área 1', 1, NOW())")
        ->execute([$embarcacaoId, $clienteId]);
}
assertTest(!empty($embarcacaoId), "Embarcação de teste pronta com dados dimensionais completos");

// 3. Criar Processo de Análise RAP para LC
echo "\n3. Criando processo de Análise RAP (LC - Licença de Construção)...\n";
$analiseId = gerarUUID();
$numeroProcesso = 'RAP-TEST-' . date('ymdHis');
$pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, analista_id, status, objeto, criado_por, iniciado_em, criado_em)
    VALUES (?, ?, 'LC', 'NORMAM-202', 'EC1', ?, ?, ?, 'EM_ANALISE', 'Construção de embarcação fluvial', ?, NOW(), NOW())")
    ->execute([$analiseId, $numeroProcesso, $embarcacaoId, $clienteId, $analista['id'], $analista['id']]);

$analise = analisePlanosCarregar($pdo, $analiseId);
assertTest($analise['status'] === 'EM_ANALISE', "Processo RAP criado com status EM_ANALISE");

// 4. Testar Bloqueio com Exigência Pendente
echo "\n4. Testando regra de bloqueio: Exigência pendente no RAP...\n";
$exigenciaId = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, status, saneamento_pendente, criado_por, criado_em)
    VALUES (?, ?, 1, 'MEMORIAL DESCRITO', 'Falta memorial de cálculo de borda livre', 'NORMAM-202 Item 3.12', 'PENDENTE', 1, ?, NOW())")
    ->execute([$exigenciaId, $analiseId, $analista['id']]);

$responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);
$bloqueouCorretamente = false;
try {
    analiseAcaoCriarLicenca($pdo, $analise, $responsavel);
} catch (RuntimeException $e) {
    $bloqueouCorretamente = true;
    echo "  [OK] Bloqueio verificado com sucesso: " . $e->getMessage() . "\n";
}
assertTest($bloqueouCorretamente, "Emissão foi estritamente bloqueada devido à exigência pendente");

// 5. Sanar a Exigência e Testar Bloqueio por falta de Parecer Conclusivo
echo "\n5. Sanando exigência e testando exigência de Parecer Conclusivo Aprovado...\n";
$pdo->prepare("UPDATE analise_planos_exigencias SET status = 'CUMPRIDA', saneamento_pendente = 0, observacao_cumprimento = 'Memorial entregue' WHERE id = ?")
    ->execute([$exigenciaId]);

$saldo = analisePlanosSaldoExigencias($pdo, $analiseId);
assertTest($saldo['pendentes'] === 0, "Exigências pendentes zeradas (0 pendências)");

$bloqueouSemParecer = false;
try {
    analiseAcaoCriarLicenca($pdo, $analise, $responsavel);
} catch (RuntimeException $e) {
    $bloqueouSemParecer = true;
    echo "  [OK] Bloqueio sem parecer conclusivo verificado: " . $e->getMessage() . "\n";
}
assertTest($bloqueouSemParecer, "Emissão bloqueada sem Parecer Técnico Conclusivo aprovado");

// 6. Emitir e Publicar Parecer Conclusivo Aprovado
echo "\n6. Emitindo Relatório Técnico RAP Conclusivo Aprovado...\n";
$parecerId = gerarUUID();
$numeroParecer = 'RAP-PAR-' . date('ymdHis');
$pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, resumo, conclusao, responsavel_assinatura_id, criado_por)
    VALUES (?, ?, 1, 'CONCLUSIVO', 'APROVADO', 'PUBLICADO', ?, 'Análise técnica aprovada', 'Conforme NORMAM-202.', ?, ?)")
    ->execute([$parecerId, $analiseId, $numeroParecer, $analista['responsavel_id'], $analista['id']]);

$stmtP = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_pareceres WHERE analise_id = ? AND finalidade = 'CONCLUSIVO' AND resultado = 'APROVADO' AND status = 'PUBLICADO'");
$stmtP->execute([$analiseId]);
assertTest((int)$stmtP->fetchColumn() === 1, "Parecer conclusivo aprovado e publicado no processo");

// 7. Testar Emissão de Licença de Construção (LC)
echo "\n7. Testando emissão da Licença Oficial de Construção (LC)...\n";
$licencaIdLC = analiseAcaoCriarLicenca($pdo, $analise, $responsavel);
assertTest(!empty($licencaIdLC), "Licença de Construção emitida com ID: {$licencaIdLC}");

$stmtLic = $pdo->prepare("SELECT * FROM certificados_lc WHERE id = ?");
$stmtLic->execute([$licencaIdLC]);
$licencaLC = $stmtLic->fetch(PDO::FETCH_ASSOC);

assertTest(str_starts_with($licencaLC['numero_lc'], 'AM-LC-'), "Número segue formato AM-LC-seq/ano: {$licencaLC['numero_lc']}");
assertTest($licencaLC['tipo_licenca'] === 'LC', "Tipo de licença é LC");
assertTest($licencaLC['analise_id'] === $analiseId, "Licença vinculada corretamente ao analise_id");
assertTest((float)$licencaLC['comprimento_total'] === 24.50, "Comprimento total preenchido: {$licencaLC['comprimento_total']} m");
assertTest((float)$licencaLC['boca_moldada'] === 6.80, "Boca moldada preenchida: {$licencaLC['boca_moldada']} m");
assertTest(!empty($licencaLC['assinante_nome']), "Nome do analista assinante preenchido: {$licencaLC['assinante_nome']}");

// 8. Testar Emissão das outras 3 modalidades (LA, LR, LCEC)
echo "\n8. Testando modalidades LA, LR e LCEC...\n";
$modalidades = [
    'LA' => ['prefix' => 'AM-LA-', 'desc' => 'Licença de Alteração'],
    'LR' => ['prefix' => 'AM-LR-', 'desc' => 'Licença de Reclassificação'],
    'LCEC' => ['prefix' => 'AM-EC-', 'desc' => 'Licença para Embarcação Já Construída (LCEC)'],
];

foreach ($modalidades as $mod => $info) {
    $anId = gerarUUID();
    $numProc = "RAP-{$mod}-" . date('ymdHis');
    $pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, analista_id, status, objeto, criado_por, iniciado_em, criado_em)
        VALUES (?, ?, ?, 'NORMAM-202', 'EC1', ?, ?, ?, 'EM_ANALISE', 'Processo Naval {$mod}', ?, NOW(), NOW())")
        ->execute([$anId, $numProc, $mod, $embarcacaoId, $clienteId, $analista['id'], $analista['id']]);

    // Adicionar parecer conclusivo aprovado
    $parId = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, resumo, conclusao, responsavel_assinatura_id, criado_por)
        VALUES (?, ?, 1, 'CONCLUSIVO', 'APROVADO', 'PUBLICADO', ?, 'Aprovado para {$mod}.', 'Conforme NORMAM-202.', ?, ?)")
        ->execute([$parId, $anId, "PAR-{$numProc}", $analista['responsavel_id'], $analista['id']]);

    $anAtual = analisePlanosCarregar($pdo, $anId);
    $licId = analiseAcaoCriarLicenca($pdo, $anAtual, $responsavel);

    $stmtM = $pdo->prepare("SELECT numero_lc, tipo_licenca, analise_id FROM certificados_lc WHERE id = ?");
    $stmtM->execute([$licId]);
    $licM = $stmtM->fetch(PDO::FETCH_ASSOC);

    assertTest(str_starts_with($licM['numero_lc'], $info['prefix']), "{$info['desc']} gerada com prefixo correto ({$info['prefix']}): {$licM['numero_lc']}");
    assertTest($licM['tipo_licenca'] === $mod, "Tipo de licença é {$mod}");
}

// 9. Testar Geração de PDF Oficial da Licença
echo "\n9. Testando geração de PDF oficial da Licença NORMAM-202 (Anexo 3-A)...\n";
$caminhoPdfTeste = sys_get_temp_dir() . '/teste_lc_' . time() . '.pdf';
$salvar_pdf_caminho = $caminhoPdfTeste;
$_GET['id'] = $licencaIdLC;
require __DIR__ . '/../modules/documentacao/lc/pdf.php';

assertTest(is_file($caminhoPdfTeste), "Arquivo PDF da Licença foi gerado com sucesso no disco");
$tamanhoPdf = filesize($caminhoPdfTeste);
assertTest($tamanhoPdf > 5000, "PDF possui tamanho consistente ({$tamanhoPdf} bytes)");
$conteudoPdf = file_get_contents($caminhoPdfTeste);
assertTest(str_starts_with($conteudoPdf, '%PDF-'), "PDF possui cabeçalho válido (%PDF-)");
@unlink($caminhoPdfTeste);

echo "\n=================================================================\n";
echo " SUCESSO: TODOS OS TESTES DO FLUXO DO ANALISTA PASSARAM (100%)!\n";
echo "=================================================================\n";
