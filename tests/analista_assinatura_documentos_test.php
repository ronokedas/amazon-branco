<?php
/**
 * Teste End-to-End: Assinatura Digital do Analista Naval para todos os documentos:
 * - LC (Licença de Construção)
 * - LA (Licença de Alteração)
 * - LR (Licença de Reclassificação)
 * - LCEC (Licença de Embarcação Já Construída)
 * - LP (Licença Provisória)
 * - NAR (Nota de Arqueação)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/aprovacao_documentos.php';
require_once __DIR__ . '/../includes/assinaturas_usuarios.php';

function assertDoc(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
}

echo "=================================================================\n";
echo " TESTE DE ASSINATURA DIGITAL DO ANALISTA NAVAL (NORMAM-202)\n";
echo " LC, LA, LR, LCEC, LP e NAR\n";
echo "=================================================================\n\n";

// 1. Localizar analista ativo com assinatura técnica
$stmt = $pdo->prepare("SELECT ra.*, u.id as user_id, u.nome as user_nome, u.cargo as user_cargo
    FROM responsaveis_assinatura ra
    JOIN usuarios u ON u.id = ra.usuario_id
    WHERE u.cargo = 'ANALISTA' AND ra.ativo = 1 AND u.ativo = 1
      AND ra.assinatura_arquivo IS NOT NULL AND ra.assinatura_arquivo <> ''
    LIMIT 1");
$stmt->execute();
$analista = $stmt->fetch(PDO::FETCH_ASSOC);
assertDoc(!empty($analista), 'Nenhum analista com assinatura ativa encontrado.');

echo "[OK] Analista técnico localizado: {$analista['nome_completo']} ({$analista['registro_profissional']})\n";

// Configurar sessão do analista
$_SESSION['usuario_id'] = $analista['user_id'];
$_SESSION['usuario_nome'] = $analista['nome_completo'];
$_SESSION['usuario_cargo'] = 'ANALISTA';
$_SESSION['usuario_logado'] = true;

// Criar embarcação temporária de teste
$embId = gerarUUID();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, registro, ano, comprimento_total, boca_moldada, pontal_moldado, arqueacao_bruta, material_casco, ativo)
    VALUES (:id, 'B/M TESTE ANALISTA ASSINATURA', 'EMBARCAÇÃO FLUVIAL', 'AM-TEST-999', 2024, 25.50, 6.20, 2.10, 45, 'AÇO', 1)")
    ->execute([':id' => $embId]);

$documentosCriados = [];

try {
    // -------------------------------------------------------------
    // Teste 1: LC (Licença de Construção)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de LC (Construção) ---\n";
    $lcId = gerarUUID();
    $tokenLC = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_lc (id, numero_lc, tipo_licenca, token_assinatura, embarcacao_id, nome_embarcacao, local_emissao, data_emissao, data_validade, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-LC-TEST/26', 'LC', :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 'Belém-PA', '2026-09-26', '2027-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $lcId, ':tok' => $tokenLC, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'LC', 'tabela' => 'certificados_lc', 'id' => $lcId];

    $resLC = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'LC',
        'documento_id' => $lcId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resLC['id']) && !empty($resLC['token']), 'Falha ao aprovar LC.');

    $docLC = $pdo->query("SELECT * FROM certificados_lc WHERE id = '{$lcId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docLC['assinado'] === 1 && $docLC['status'] === 'assinado', 'LC não ficou assinado no banco.');
    assertDoc(!empty($docLC['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docLC['caminho_arquivo_pdf']), 'Arquivo PDF de LC assinado não existe.');
    assertDoc(!empty($docLC['hash_arquivo_pdf']), 'Hash de LC vazio.');
    assertDoc(!empty($docLC['assinante_nome']), 'Nome do assinante não foi gravado na LC.');
    echo "  [OK] LC aprovada e assinada com sucesso! PDF: {$docLC['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 2: LA (Licença de Alteração)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de LA (Alteração) ---\n";
    $laId = gerarUUID();
    $tokenLA = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_lc (id, numero_lc, tipo_licenca, token_assinatura, embarcacao_id, nome_embarcacao, local_emissao, data_emissao, data_validade, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-LA-TEST/26', 'LA', :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 'Belém-PA', '2026-09-26', '2027-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $laId, ':tok' => $tokenLA, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'LA', 'tabela' => 'certificados_lc', 'id' => $laId];

    $resLA = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'LA',
        'documento_id' => $laId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resLA['id']) && !empty($resLA['token']), 'Falha ao aprovar LA.');

    $docLA = $pdo->query("SELECT * FROM certificados_lc WHERE id = '{$laId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docLA['assinado'] === 1 && $docLA['status'] === 'assinado', 'LA não ficou assinado no banco.');
    assertDoc(!empty($docLA['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docLA['caminho_arquivo_pdf']), 'Arquivo PDF de LA assinado não existe.');
    echo "  [OK] LA aprovada e assinada com sucesso! PDF: {$docLA['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 3: LR (Licença de Reclassificação)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de LR (Reclassificação) ---\n";
    $lrId = gerarUUID();
    $tokenLR = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_lc (id, numero_lc, tipo_licenca, token_assinatura, embarcacao_id, nome_embarcacao, local_emissao, data_emissao, data_validade, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-LR-TEST/26', 'LR', :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 'Belém-PA', '2026-09-26', '2027-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $lrId, ':tok' => $tokenLR, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'LR', 'tabela' => 'certificados_lc', 'id' => $lrId];

    $resLR = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'LR',
        'documento_id' => $lrId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resLR['id']) && !empty($resLR['token']), 'Falha ao aprovar LR.');

    $docLR = $pdo->query("SELECT * FROM certificados_lc WHERE id = '{$lrId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docLR['assinado'] === 1 && $docLR['status'] === 'assinado', 'LR não ficou assinado no banco.');
    assertDoc(!empty($docLR['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docLR['caminho_arquivo_pdf']), 'Arquivo PDF de LR assinado não existe.');
    echo "  [OK] LR aprovada e assinada com sucesso! PDF: {$docLR['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 4: LCEC (Construção Embarcação Já Construída)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de LCEC (Embarcação Já Construída) ---\n";
    $lcecId = gerarUUID();
    $tokenLCEC = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_lc (id, numero_lc, tipo_licenca, token_assinatura, embarcacao_id, nome_embarcacao, local_emissao, data_emissao, data_validade, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-EC-TEST/26', 'LCEC', :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 'Belém-PA', '2026-09-26', '2027-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $lcecId, ':tok' => $tokenLCEC, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'LCEC', 'tabela' => 'certificados_lc', 'id' => $lcecId];

    $resLCEC = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'LCEC',
        'documento_id' => $lcecId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resLCEC['id']) && !empty($resLCEC['token']), 'Falha ao aprovar LCEC.');

    $docLCEC = $pdo->query("SELECT * FROM certificados_lc WHERE id = '{$lcecId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docLCEC['assinado'] === 1 && $docLCEC['status'] === 'assinado', 'LCEC não ficou assinado no banco.');
    assertDoc(!empty($docLCEC['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docLCEC['caminho_arquivo_pdf']), 'Arquivo PDF de LCEC assinado não existe.');
    echo "  [OK] LCEC aprovada e assinada com sucesso! PDF: {$docLCEC['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 5: LP (Licença Provisória)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de LP (Licença Provisória) ---\n";
    $lpId = gerarUUID();
    $tokenLP = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_lp (id, numero_lp, token_assinatura, embarcacao_id, nome_embarcacao, tipo_licenca, tipo_embarcacao, comprimento_total, boca_moldada, pontal_moldado, material_casco, data_emissao, validade_data, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-LP-TEST/26', :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 'construção', 'EMBARCAÇÃO FLUVIAL', 25.50, 6.20, 2.10, 'AÇO', '2026-09-26', '2027-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $lpId, ':tok' => $tokenLP, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'LP', 'tabela' => 'certificados_lp', 'id' => $lpId];

    $resLP = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'LP',
        'documento_id' => $lpId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resLP['id']) && !empty($resLP['token']), 'Falha ao aprovar LP.');

    $docLP = $pdo->query("SELECT * FROM certificados_lp WHERE id = '{$lpId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docLP['assinado'] === 1 && $docLP['status'] === 'assinado', 'LP não ficou assinado no banco.');
    assertDoc(!empty($docLP['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docLP['caminho_arquivo_pdf']), 'Arquivo PDF de LP assinado não existe.');
    echo "  [OK] LP aprovada e assinada com sucesso! PDF: {$docLP['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 6: NAR (Nota de Arqueação)
    // -------------------------------------------------------------
    echo "\n--- Testando Assinatura de NAR (Nota de Arqueação) ---\n";
    $narId = gerarUUID();
    $tokenNAR = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO certificados_nar (id, numero, ano, sequencial, token_assinatura, embarcacao_id, nome_embarcacao, arqueacao_bruta_ab, arqueacao_liquida_al, data_emissao, status, assinado, responsavel_assinatura_id, criado_por, ativo)
        VALUES (:id, 'AM-NAR-TEST/26', 2026, 999, :tok, :emb, 'B/M TESTE ANALISTA ASSINATURA', 45, 13, '2026-09-26', 'emitido', 0, :resp, :user, 1)")
        ->execute([':id' => $narId, ':tok' => $tokenNAR, ':emb' => $embId, ':resp' => $analista['id'], ':user' => $analista['user_id']]);
    $documentosCriados[] = ['tipo' => 'NAR', 'tabela' => 'certificados_nar', 'id' => $narId];

    $resNAR = aprovarDocumentoEletronicamente($pdo, [
        'documento_tipo' => 'NAR',
        'documento_id' => $narId,
        'responsavel_id' => $analista['id'],
        'latitude' => -1.4558,
        'longitude' => -48.4902,
        'geo_precisao_m' => 10,
        'csrf_token' => gerarCSRF()
    ]);
    assertDoc(!empty($resNAR['id']) && !empty($resNAR['token']), 'Falha ao aprovar NAR.');

    $docNAR = $pdo->query("SELECT * FROM certificados_nar WHERE id = '{$narId}'")->fetch(PDO::FETCH_ASSOC);
    assertDoc((int)$docNAR['assinado'] === 1 && $docNAR['status'] === 'assinado', 'NAR não ficou assinado no banco.');
    assertDoc(!empty($docNAR['caminho_arquivo_pdf']) && file_exists(__DIR__ . '/../' . $docNAR['caminho_arquivo_pdf']), 'Arquivo PDF de NAR assinado não existe.');
    echo "  [OK] NAR aprovada e assinada com sucesso! PDF: {$docNAR['caminho_arquivo_pdf']}\n";

    // -------------------------------------------------------------
    // Teste 7: Validação Pública de Autenticidade para todos
    // -------------------------------------------------------------
    echo "\n--- Testando Validação Pública de Autenticidade dos Documentos Assinados ---\n";
    foreach ($documentosCriados as $item) {
        $stmtV = $pdo->prepare("SELECT * FROM documento_aprovacoes WHERE documento_tipo = :t AND documento_id = :id AND status = 'APROVADO' LIMIT 1");
        $stmtV->execute([':t' => $item['tipo'], ':id' => $item['id']]);
        $appRow = $stmtV->fetch(PDO::FETCH_ASSOC);
        assertDoc(!empty($appRow), "Registro de aprovação não encontrado para {$item['tipo']}.");
        assertDoc(!empty($appRow['token_validacao']), "Token de validação não encontrado para {$item['tipo']}.");
        assertDoc($appRow['responsavel_nome'] === $analista['nome_completo'], "Nome do responsável diverge em {$item['tipo']}.");
        assertDoc(!empty($appRow['hash_pdf_final']), "Hash PDF final ausente para {$item['tipo']}.");
        
        $arquivoPdf = __DIR__ . '/../' . ltrim($appRow['caminho_pdf_final'], '/');
        assertDoc(file_exists($arquivoPdf), "Arquivo físico de PDF não encontrado: {$arquivoPdf}");
        assertDoc(hash_file('sha256', $arquivoPdf) === $appRow['hash_pdf_final'], "Hash do PDF diverge do hash auditado em {$item['tipo']}.");
        
        echo "  [OK] {$item['tipo']}: Token {$appRow['token_validacao']} e integridade criptográfica verificados!\n";
    }

} finally {
    // Limpeza de testes
    echo "\n--- Limpeza dos Dados de Teste ---\n";
    foreach ($documentosCriados as $item) {
        $pdo->prepare("DELETE FROM documento_aprovacoes WHERE documento_tipo = :t AND documento_id = :id")->execute([':t' => $item['tipo'], ':id' => $item['id']]);
        $pdo->prepare("DELETE FROM {$item['tabela']} WHERE id = :id")->execute([':id' => $item['id']]);
    }
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    echo "  [OK] Limpeza atômica concluída.\n";
}

echo "\n=================================================================\n";
echo " SUCESSO: O ANALISTA NAVAL ASSINA DIGITALMENTE TODOS OS\n";
echo " DOCUMENTOS (LC, LA, LR, LCEC, LP E NAR) CONFORME SOLICITADO!\n";
echo "=================================================================\n";
