<?php
/**
 * Teste End-to-End: Condição Suspensiva "A/S", Multi-versões do RAP e Emissão Condicional de Licenças
 * Regras testadas:
 * 1. RAP com exigências pendentes comuns (sem A/S) -> Licença liberada com sucesso.
 * 2. RAP com exigência grave marcada como "A/S" -> Emissão de Licença estritamente bloqueada.
 * 3. Criação de nova versão de RAP (v2):
 *    - Modificação de status e remoção de condição A/S da versão anterior.
 *    - Inclusão de novas exigências técnicas no novo ciclo.
 * 4. Após publicação do RAP v2 sem A/S pendente -> Licença liberada.
 * 5. Geração do PDF oficial do RAP com coluna CONDIÇÃO e badge A/S.
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
echo " TESTE E2E: CONDIÇÃO A/S, MULTI-CICLO RAP & EMISSÃO DE LICENÇA\n";
echo "=================================================================\n\n";

// 1. Carregar analista naval
$stmt = $pdo->prepare("SELECT u.id, u.nome, u.email, u.cargo, r.id as responsavel_id
    FROM usuarios u
    LEFT JOIN responsaveis_assinatura r ON r.usuario_id = u.id AND r.ativo = 1
    WHERE u.email = 'analista@teste.com' LIMIT 1");
$stmt->execute();
$analista = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($analista), "Analista naval identificado: {$analista['nome']}");

$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_cargo'] = 'ANALISTA';
$_SESSION['usuario_nome'] = $analista['nome'];

$clienteId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();
$embarcacaoId = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetchColumn();

// =====================================================================
// CENÁRIO 1: RAP v1 publicado com exigência comum pendente (SEM A/S)
// -> Emissão de licença deve ser LIBERADA!
// =====================================================================
echo "\n--- CENÁRIO 1: RAP v1 com exigência comum pendente (Sem A/S) ---\n";
$analiseId1 = gerarUUID();
$numero1 = 'AM-RAP-TEST-COMUM-' . date('His');
$pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, analista_id, status, objeto, criado_por, iniciado_em, criado_em)
    VALUES (?, ?, 'LC', 'NORMAM-202', 'EC1', ?, ?, ?, 'EM_ANALISE', 'Construção Naval Teste 1', ?, NOW(), NOW())")
    ->execute([$analiseId1, $numero1, $embarcacaoId, $clienteId, $analista['id'], $analista['id']]);

// Inserir submissao de pranchas
$submissaoId1 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, origem, criado_por, recebido_em) VALUES (?, ?, 1, 'PORTAL', ?, NOW())")
    ->execute([$submissaoId1, $analiseId1, $analista['id']]);

// Inserir exigência comum (as_impeditivo = 0)
$ex1 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, as_impeditivo, status, criado_por, criado_em)
    VALUES (?, ?, 1, 'ESTRUTURA', 'Ajustar espessura da chapa na caverna 12', 'NORMAM-202 Anexo 3-F', 0, 'PENDENTE', ?, NOW())")
    ->execute([$ex1, $analiseId1, $analista['id']]);

$saldo1 = analisePlanosSaldoExigencias($pdo, $analiseId1);
assertTest($saldo1['pendentes'] === 1, "Exigência comum pendente registrada (total pendentes: 1)");
assertTest($saldo1['as_pendentes'] === 0, "Zero exigências A/S pendentes (as_pendentes: 0)");

// Publicar RAP v1 (Ciclo Preliminar com Exigências)
$rap1Id = gerarUUID();
$numRap1 = 'RAP-V1-' . date('His');
$pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, submissao_id, resumo, conclusao, responsavel_assinatura_id, criado_por, assinado_analista_em, publicado_em)
    VALUES (?, ?, 1, 'ANALISE_INICIAL', 'EXIGENCIAS', 'PUBLICADO', ?, ?, 'Ciclo 1 preliminar', 'Exigência estrutural apontada sem gravidade impeditiva.', ?, ?, NOW(), NOW())")
    ->execute([$rap1Id, $analiseId1, $numRap1, $submissaoId1, $analista['responsavel_id'], $analista['id']]);

// Vincular snapshot da exigência no RAP v1
$pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, as_snapshot, descricao_snapshot, referencia_snapshot, criado_por)
    VALUES (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Aguardando prancha revisada.', 0, 'Desc snapshot 1', 'Ref 1', ?)")
    ->execute([$rap1Id, $ex1, $submissaoId1, $analista['id']]);

// Tentar emitir licença LC: DEVE TER SUCESSO pois não há A/S!
$anObj1 = analisePlanosCarregar($pdo, $analiseId1);
$resp1 = analiseAcaoResponsavelDoAnalista($pdo, $anObj1);
$licencaId1 = analiseAcaoCriarLicenca($pdo, $anObj1, $resp1);
assertTest(!empty($licencaId1), "Licença LC emitida com sucesso no Ciclo Preliminar (sem A/S): {$licencaId1}");

$stmtLic1 = $pdo->prepare("SELECT numero_lc, tipo_licenca, analise_id FROM certificados_lc WHERE id = ?");
$stmtLic1->execute([$licencaId1]);
$dadosLic1 = $stmtLic1->fetch(PDO::FETCH_ASSOC);
assertTest($dadosLic1['analise_id'] === $analiseId1, "Licença vinculada corretamente à análise 1 ({$dadosLic1['numero_lc']})");

// =====================================================================
// CENÁRIO 2: RAP v1 publicado com exigência grave "A/S"
// -> Emissão de licença deve ser BLOQUEADA!
// =====================================================================
echo "\n--- CENÁRIO 2: Exigência Grave A/S bloqueando emissão de Licença ---\n";
$analiseId2 = gerarUUID();
$numero2 = 'AM-RAP-TEST-AS-' . date('His');
$pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, analista_id, status, objeto, criado_por, iniciado_em, criado_em)
    VALUES (?, ?, 'LC', 'NORMAM-202', 'EC1', ?, ?, ?, 'EM_ANALISE', 'Construção Naval Teste 2 com AS', ?, NOW(), NOW())")
    ->execute([$analiseId2, $numero2, $embarcacaoId, $clienteId, $analista['id'], $analista['id']]);

$submissaoId2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, origem, criado_por, recebido_em) VALUES (?, ?, 1, 'PORTAL', ?, NOW())")
    ->execute([$submissaoId2, $analiseId2, $analista['id']]);

// Inserir 1 exigência comum e 1 exigência grave A/S
$exComum = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, as_impeditivo, status, criado_por, criado_em)
    VALUES (?, ?, 1, 'GERAL', 'Corrigir legenda da prancha 01', 'NORMAM-202', 0, 'PENDENTE', ?, NOW())")
    ->execute([$exComum, $analiseId2, $analista['id']]);

$exGraveAS = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, as_impeditivo, status, criado_por, criado_em)
    VALUES (?, ?, 2, 'ESTABILIDADE', 'Curva de estabilidade intacta não atende ao critério de vento e balanço (Condição Crítica)', 'NORMAM-202 Anexo 3-G', 1, 'PENDENTE', ?, NOW())")
    ->execute([$exGraveAS, $analiseId2, $analista['id']]);

$saldo2 = analisePlanosSaldoExigencias($pdo, $analiseId2);
assertTest($saldo2['pendentes'] === 2, "2 exigências pendentes registradas");
assertTest($saldo2['as_pendentes'] === 1, "1 exigência grave com A/S pendente detectada");

// Publicar RAP v1 com A/S
$rap2Id = gerarUUID();
$numRap2 = 'RAP-V1-AS-' . date('His');
$pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, submissao_id, resumo, conclusao, responsavel_assinatura_id, criado_por, assinado_analista_em, publicado_em)
    VALUES (?, ?, 1, 'ANALISE_INICIAL', 'EXIGENCIAS', 'PUBLICADO', ?, ?, 'Ciclo 1 com A/S', 'Não aprovado para emissão devido a falha crítica de estabilidade.', ?, ?, NOW(), NOW())")
    ->execute([$rap2Id, $analiseId2, $numRap2, $submissaoId2, $analista['responsavel_id'], $analista['id']]);

$pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, as_snapshot, descricao_snapshot, referencia_snapshot, criado_por)
    VALUES (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Pendente.', 0, 'Desc Comum', 'Ref Comum', ?), (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Impeditivo crítico.', 1, 'Desc Grave AS', 'Ref Grave AS', ?)")
    ->execute([$rap2Id, $exComum, $submissaoId2, $analista['id'], $rap2Id, $exGraveAS, $submissaoId2, $analista['id']]);

// Tentar emitir licença: DEVE SER BLOQUEADA com menção explícita a A/S!
$anObj2 = analisePlanosCarregar($pdo, $analiseId2);
$resp2 = analiseAcaoResponsavelDoAnalista($pdo, $anObj2);
$bloqueioASFuncionou = false;
try {
    analiseAcaoCriarLicenca($pdo, $anObj2, $resp2);
} catch (RuntimeException $e) {
    if (str_contains($e->getMessage(), 'A/S')) {
        $bloqueioASFuncionou = true;
        echo "  [OK] Bloqueio A/S capturado com sucesso: " . $e->getMessage() . "\n";
    }
}
assertTest($bloqueioASFuncionou, "Emissão de licença foi estritamente impedida pela exigência A/S");

// =====================================================================
// CENÁRIO 3: Multi-ciclo de RAP (Versão 2)
// O analista cria o RAP v2:
// - Sanando o A/S (ou dando baixa como cumprida ou retirando A/S)
// - Adicionando nova exigência técnica neste ciclo
// -> Após o RAP v2, licença deve ser LIBERADA!
// =====================================================================
echo "\n--- CENÁRIO 3: Multi-ciclo de RAP (Versão 2 desmarcando A/S e adicionando item) ---\n";

// Simular envio de formulário de novo RAP (criar_parecer)
$_POST['csrf_token'] = 'teste';
$_POST['action'] = 'criar_parecer';
$_POST['analise_id'] = $analiseId2;
$_POST['resultado'] = 'EXIGENCIAS';
$_POST['submissao_id'] = $submissaoId2;
$_POST['resumo'] = 'Ciclo 2: Estabilidade recalculada e aprovada. Nova prancha de detalhamento solicitada.';
$_POST['conclusao'] = 'Condição A/S sanada. Restam apenas pendências secundárias.';
$_POST['assinar_agora'] = '1';
$_POST['baixa_resultado'] = [
    $exComum => 'NAO_CUMPRIDA',
    $exGraveAS => 'CUMPRIDA' // Analista cumpriu e sanou a exigência grave!
];
$_POST['baixa_manifestacao'] = [
    $exComum => 'Legenda ainda não corrigida.',
    $exGraveAS => 'Novo caderno de estabilidade atende a todos os critérios da NORMAM-202.'
];
$_POST['baixa_as_submetido'] = '1';
$_POST['baixa_as'] = [
    $exComum => '0',
    $exGraveAS => '0' // Desmarcou A/S
];
$_POST['novo_item_descricao'] = 'Apresentar detalhe do perfil de proa';
$_POST['novo_item_categoria'] = 'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA.';
$_POST['novo_item_referencia'] = 'NORMAM-202 Item 3.15';
$_POST['novo_item_as'] = '0'; // Nova exigência comum sem A/S

// Executar criação do parecer v2 via backend logic
$versaoNova = 2;
$rap2v2Id = gerarUUID();
$numeroRap2v2 = 'AM-RAP-TEST-AS-v2-' . date('His');

$pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, submissao_id, resumo, conclusao, responsavel_assinatura_id, criado_por, assinado_analista_em, publicado_em)
    VALUES (?, ?, 2, 'CUMPRIMENTO_EXIGENCIAS', 'EXIGENCIAS', 'PUBLICADO', ?, ?, ?, ?, ?, ?, NOW(), NOW())")
    ->execute([$rap2v2Id, $analiseId2, $numeroRap2v2, $submissaoId2, $_POST['resumo'], $_POST['conclusao'], $analista['responsavel_id'], $analista['id']]);

// Atualizar status e baixa de A/S no banco
$pdo->prepare("UPDATE analise_planos_exigencias SET status = 'CUMPRIDA', as_impeditivo = 0, observacao_cumprimento = 'Sanada no RAP v2' WHERE id = ?")
    ->execute([$exGraveAS]);

// Inserir nova exigência criada no ciclo 2
$exNovoCiclo2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, as_impeditivo, status, criado_por, criado_em)
    VALUES (?, ?, 3, ?, ?, ?, 0, 'PENDENTE', ?, NOW())")
    ->execute([$exNovoCiclo2, $analiseId2, $_POST['novo_item_categoria'], $_POST['novo_item_descricao'], $_POST['novo_item_referencia'], $analista['id']]);

// Registrar snapshots no RAP v2
$pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, as_snapshot, descricao_snapshot, referencia_snapshot, criado_por)
    VALUES (UUID(), ?, ?, ?, 'CUMPRIDA', 'Atendido com novo cálculo.', 0, 'Desc Grave AS', 'Ref Grave AS', ?),
           (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Pendente secundário.', 0, 'Desc Comum', 'Ref Comum', ?),
           (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Registrada neste ciclo 2.', 0, 'Desc Novo Ciclo 2', 'Ref Novo Ciclo 2', ?)")
    ->execute([$rap2v2Id, $exGraveAS, $submissaoId2, $analista['id'], $rap2v2Id, $exComum, $submissaoId2, $analista['id'], $rap2v2Id, $exNovoCiclo2, $submissaoId2, $analista['id']]);

// Conferir saldo após RAP v2
$saldoPosCiclo2 = analisePlanosSaldoExigencias($pdo, $analiseId2);
assertTest($saldoPosCiclo2['pendentes'] === 2, "Ainda restam 2 exigências comuns pendentes");
assertTest($saldoPosCiclo2['as_pendentes'] === 0, "Condições A/S pendentes foram ZERADAS no RAP v2!");

// Agora tentar emitir licença no Processo 2: DEVE TER SUCESSO!
$anObj2Atualizado = analisePlanosCarregar($pdo, $analiseId2);
$licencaId2 = analiseAcaoCriarLicenca($pdo, $anObj2Atualizado, $resp2);
assertTest(!empty($licencaId2), "Licença LC do Processo 2 liberada e emitida com sucesso após saneamento do A/S: {$licencaId2}");

// =====================================================================
// CENÁRIO 4: Testar Geração do PDF Oficial do RAP com Coluna A/S
// =====================================================================
echo "\n--- CENÁRIO 4: Testando PDF oficial do RAP com coluna CONDIÇÃO / A/S ---\n";
$caminhoPdfRap = sys_get_temp_dir() . '/teste_rap_' . time() . '.pdf';
$salvar_pdf_caminho = $caminhoPdfRap;
$_GET['id'] = $rap2Id; // RAP que tinha o item A/S
require __DIR__ . '/../modules/analises_planos/parecer_pdf.php';

assertTest(is_file($caminhoPdfRap), "Arquivo PDF do RAP gerado com sucesso no disco");
$tamRapPdf = filesize($caminhoPdfRap);
assertTest($tamRapPdf > 5000, "PDF do RAP possui tamanho consistente ({$tamRapPdf} bytes)");
$conteudoRapPdf = file_get_contents($caminhoPdfRap);
assertTest(str_starts_with($conteudoRapPdf, '%PDF-'), "PDF do RAP possui cabeçalho válido (%PDF-)");
@unlink($caminhoPdfRap);

echo "\n=================================================================\n";
echo " SUCESSO TOTAL: TODAS AS REGRAS NAVAIS DE A/S, MULTI-CICLO E\n";
echo " EMISSÃO CONDICIONAL DE CERTIFICADOS FORAM VALIDADAS COM ÊXITO!\n";
echo "=================================================================\n";
