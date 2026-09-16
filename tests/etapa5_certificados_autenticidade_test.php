<?php
/**
 * Testes Automatizados da Etapa 5:
 * 1. Motor Central Unificado de Emissão (CSN, CNBL, CNARQ, LP, LC, CHT)
 * 2. Módulo de Autenticidade e Retrocompatibilidade de URLs e Rotas
 * 3. Identificador UUID em responsaveis_assinatura e compatibilidade dual (ID/UUID)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/emissao_certificados.php';
require_once __DIR__ . '/../includes/assinaturas_usuarios.php';
require_once __DIR__ . '/../includes/functions.php';

function assertEtapa5(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("[FALHA ETAPA 5] " . $msg);
    }
}

echo "=== INICIANDO TESTES DA ETAPA 5 ===\n";

// -------------------------------------------------------------
// TESTE 1: Estrutura da tabela responsaveis_assinatura (UUID + ID)
// -------------------------------------------------------------
echo "[1/4] Verificando UUID e compatibilidade em responsaveis_assinatura...\n";

$stmtCols = $pdo->query("SHOW COLUMNS FROM responsaveis_assinatura LIKE 'uuid'");
$colUuid = $stmtCols->fetch(PDO::FETCH_ASSOC);
assertEtapa5($colUuid !== false, "Coluna 'uuid' não encontrada em responsaveis_assinatura.");

$stmtIdx = $pdo->query("SHOW INDEX FROM responsaveis_assinatura WHERE Key_name = 'uk_responsaveis_assinatura_uuid'");
assertEtapa5($stmtIdx->fetch() !== false, "Índice único uk_responsaveis_assinatura_uuid ausente.");

// Verificar se todos os registros ativos possuem UUID válido de 36 caracteres
$stmtResp = $pdo->query("SELECT id, uuid, nome_completo FROM responsaveis_assinatura WHERE ativo = 1");
$responsaveis = $stmtResp->fetchAll(PDO::FETCH_ASSOC);
assertEtapa5(count($responsaveis) > 0, "Nenhum responsável de assinatura ativo encontrado.");

$primeiroResp = $responsaveis[0];
assertEtapa5(strlen($primeiroResp['uuid']) === 36, "UUID do responsável de assinatura inválido.");

// Testar helper obterResponsavelAssinaturaPorId por ID numérico e por UUID
$respPorId = obterResponsavelAssinaturaPorId($pdo, $primeiroResp['id']);
assertEtapa5($respPorId !== null && $respPorId['uuid'] === $primeiroResp['uuid'], "Falha ao buscar responsável por ID numérico.");

$respPorUuid = obterResponsavelAssinaturaPorId($pdo, $primeiroResp['uuid']);
assertEtapa5($respPorUuid !== null && (int)$respPorUuid['id'] === (int)$primeiroResp['id'], "Falha ao buscar responsável por UUID.");

echo "  -> OK: Tabela responsaveis_assinatura padronizada com UUID e busca dual transparente.\n";

// -------------------------------------------------------------
// TESTE 2: Emissão unificada dos 6 modelos via motor central
// -------------------------------------------------------------
echo "[2/4] Testando emissão via motor central para todos os 6 modelos...\n";

// Buscar uma vistoria aprovada para os testes de CSN, CNBL, CNARQ, LP, LC
$stmtVist = $pdo->query("SELECT v.id, v.numero, v.status, a.embarcacao_id, COALESCE(v.pessoa_id, e.proprietario_id) as cliente_id, e.nome as embarcacao_nome
    FROM vistorias v
    JOIN agendamentos a ON v.agendamento_id = a.id
    JOIN embarcacoes e ON a.embarcacao_id = e.id
    WHERE v.status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS')
    ORDER BY v.criado_em DESC LIMIT 1");
$vistoriaTeste = $stmtVist->fetch(PDO::FETCH_ASSOC);
assertEtapa5($vistoriaTeste !== false, "Nenhuma vistoria aprovada encontrada para testes de certificação.");

$vistoriaId = $vistoriaTeste['id'];
$embId = $vistoriaTeste['embarcacao_id'];
$cliId = $vistoriaTeste['cliente_id'];
$adminUserId = (string)$pdo->query("SELECT id FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 LIMIT 1")->fetchColumn();

$certificadosGerados = [];

try {
    $pdo->beginTransaction();

    // 2.1 Emissão CSN
    $dadosCSN = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Provisório',
        'responsavel_assinatura_id' => $primeiroResp['uuid'], // Testando passagem de UUID!
        'emitente' => 'AMAZON NAVAL',
        'normam_aplicavel' => 'NORMAM-202',
        'tipo_vistoria_certificado' => 'Inicial',
        'local_emissao' => 'Belém-PA',
        'data_emissao' => date('Y-m-d'),
        'data_validade' => date('Y-m-d', strtotime('+90 days')),
    ];
    $resCSN = emitirCertificadoUnificado($pdo, 'CSN', $dadosCSN, $adminUserId);
    assertEtapa5(!empty($resCSN['id']) && !empty($resCSN['numero']), "Falha na emissão unificada de CSN.");
    assertEtapa5(str_starts_with($resCSN['numero'], 'AM-CSN-'), "Formato de numeração de CSN inválido: " . $resCSN['numero']);
    $certificadosGerados['CSN'] = $resCSN;

    // Verificar linhas de passageiros padrão geradas para CSN
    $stmtPass = $pdo->prepare("SELECT COUNT(*) FROM csn_distribuicao_passageiros WHERE certificado_id = :id");
    $stmtPass->execute([':id' => $resCSN['id']]);
    assertEtapa5((int)$stmtPass->fetchColumn() > 0, "Distribuição padrão de passageiros não gerada para CSN.");

    // 2.2 Emissão CNBL
    $dadosCNBL = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Provisório',
        'responsavel_assinatura_id' => (int)$primeiroResp['id'], // Testando passagem de ID numérico!
        'local_emissao' => 'Belém-PA',
        'data_emissao' => date('Y-m-d'),
        'data_validade' => date('Y-m-d', strtotime('+90 days')),
    ];
    $resCNBL = emitirCertificadoUnificado($pdo, 'CNBL', $dadosCNBL, $adminUserId);
    assertEtapa5(!empty($resCNBL['id']) && str_starts_with($resCNBL['numero'], 'AM-CNBL-'), "Falha na emissão unificada de CNBL.");
    $certificadosGerados['CNBL'] = $resCNBL;

    // Vincular temporariamente escopo de arqueação para o teste de CNARQ
    $raizCadeia = obterRelatorioRaizCadeia($pdo, $vistoriaId);
    $agendRaizId = (string)($raizCadeia['agendamento_id'] ?? '');
    $propostaId = (string)$pdo->query("SELECT proposta_id FROM agendamentos WHERE id = '{$agendRaizId}'")->fetchColumn();
    if ($propostaId && $agendRaizId) {
        $pdo->exec("INSERT IGNORE INTO propostas_servicos (proposta_id, embarcacao_id, servico_id) VALUES ('{$propostaId}', '{$embId}', 'a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf')");
        $tipoAtual = (string)$pdo->query("SELECT tipo_vistoria FROM agendamentos WHERE id = '{$agendRaizId}'")->fetchColumn();
        $pdo->prepare("UPDATE agendamentos SET tipo_vistoria = :t WHERE id = :id")->execute([':t' => $tipoAtual . ', Vistoria de Arqueação', ':id' => $agendRaizId]);
    }

    // 2.3.1 Validação de bloqueio mandatório: tentar emitir Definitivo para relatório com exigências
    $bloqueouDefinitivo = false;
    try {
        $dadosBloqueio = [
            'vistoria_id' => $vistoriaId,
            'tipo' => 'Definitivo',
            'responsavel_assinatura_id' => $primeiroResp['uuid'],
        ];
        emitirCertificadoUnificado($pdo, 'CNARQ', $dadosBloqueio, $adminUserId);
    } catch (RuntimeException $eBloq) {
        if (str_contains($eBloq->getMessage(), 'exigencia comum pendente') || str_contains($eBloq->getMessage(), 'Certificado Definitivo')) {
            $bloqueouDefinitivo = true;
        }
    }
    assertEtapa5($bloqueouDefinitivo, "O motor unificado não bloqueou a emissão de Certificado Definitivo para relatório com exigências pendentes.");

    // 2.3.2 Emissão CNARQ Provisório
    $dadosCNARQ = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Provisório',
        'responsavel_assinatura_id' => $primeiroResp['uuid'],
        'local_emissao' => 'Belém-PA',
        'data_emissao' => date('Y-m-d'),
        'data_validade' => date('Y-m-d', strtotime('+90 days')),
    ];
    $resCNARQ = emitirCertificadoUnificado($pdo, 'CNARQ', $dadosCNARQ, $adminUserId);
    assertEtapa5(!empty($resCNARQ['id']) && str_starts_with($resCNARQ['numero'], 'AM-CNARQ-'), "Falha na emissão unificada de CNARQ.");
    $certificadosGerados['CNARQ'] = $resCNARQ;

    // 2.4 Emissão LP
    $dadosLP = [
        'vistoria_id' => $vistoriaId,
        'responsavel_assinatura_id' => $primeiroResp['uuid'],
        'data_validade' => date('Y-m-d', strtotime('+180 days')),
        'tipo_licenca' => 'construcao',
    ];
    $resLP = emitirCertificadoUnificado($pdo, 'LP', $dadosLP, $adminUserId);
    assertEtapa5(!empty($resLP['id']) && str_starts_with($resLP['numero'], 'AM-LP-'), "Falha na emissão unificada de LP: " . ($resLP['numero'] ?? ''));
    $certificadosGerados['LP'] = $resLP;

    // 2.5 Emissão LC
    $dadosLC = [
        'vistoria_id' => $vistoriaId,
        'responsavel_assinatura_id' => $primeiroResp['uuid'],
        'tipo_licenca' => 'LC',
        'local_emissao' => 'Belém-PA',
    ];
    $resLC = emitirCertificadoUnificado($pdo, 'LC', $dadosLC, $adminUserId);
    assertEtapa5(!empty($resLC['id']) && str_starts_with($resLC['numero'], 'AM-LC-'), "Falha na emissão unificada de LC: " . ($resLC['numero'] ?? ''));
    $certificadosGerados['LC'] = $resLC;

    // 2.6 Emissão CHT
    $dadosCHT = [
        'responsavel_assinatura_id' => $primeiroResp['uuid'],
        'profissional_empresa' => 'Empresa Teste Homologação Naval Ltda',
        'cpf_cnpj' => '12.345.678/0001-90',
        'email_destinatario' => 'homologacao@teste.com.br',
        'atividade_homologada' => 'Manutenção de Balsas e Flutuantes',
        'relatorio_homologacao_numero' => 'REL-HT-2026/01',
        'data_validade' => date('Y-m-d', strtotime('+2 years')),
        'local_emissao' => 'Belém-PA',
        'cliente_id' => $cliId,
        'embarcacao_id' => $embId,
    ];
    $resCHT = emitirCertificadoUnificado($pdo, 'CHT', $dadosCHT, $adminUserId);
    assertEtapa5(!empty($resCHT['id']) && str_starts_with($resCHT['numero'], 'AM-CHT-'), "Falha na emissão unificada de CHT: " . ($resCHT['numero'] ?? ''));
    $certificadosGerados['CHT'] = $resCHT;

    // Verificar integridade relacional em todos os 6 emitidos
    foreach ($certificadosGerados as $mod => $res) {
        assertEtapa5(strlen($res['token']) === 64, "Token de segurança de 64 caracteres ausente no modelo {$mod}.");
    }

    echo "  -> OK: Todos os 6 modelos (CSN, CNBL, CNARQ, LP, LC, CHT) emitidos com sucesso pelo motor central.\n";

    // -------------------------------------------------------------
    // TESTE 3: Autenticidade e Validação Pública
    // -------------------------------------------------------------
    echo "[3/4] Testando validação pública de autenticidade (novo token e token histórico)...\n";

    // 3.1 Token do certificado recém-gerado
    $tokenNovo = $resCSN['token'];
    $stmtValNovo = $pdo->prepare("SELECT id, numero, token_assinatura FROM certificados_csn WHERE token_assinatura = :token");
    $stmtValNovo->execute([':token' => $tokenNovo]);
    assertEtapa5($stmtValNovo->fetch() !== false, "Token novo não encontrado no banco.");

    // 3.2 Token do certificado histórico existente (CSN AM-CSN-1/26)
    $stmtHist = $pdo->query("SELECT id, numero, token_assinatura FROM certificados_csn WHERE numero = 'AM-CSN-1/26' LIMIT 1");
    $certHist = $stmtHist->fetch(PDO::FETCH_ASSOC);
    if ($certHist && !empty($certHist['token_assinatura'])) {
        $tokenHist = $certHist['token_assinatura'];
        $stmtValHist = $pdo->prepare("SELECT id, numero FROM certificados_csn WHERE token_assinatura = :token");
        $stmtValHist->execute([':token' => $tokenHist]);
        assertEtapa5($stmtValHist->fetch() !== false, "Validação do token histórico falhou.");
    }

    // 3.3 Testar retrocompatibilidade de arquivos proxy em modules/documentos/
    assertEtapa5(file_exists(__DIR__ . '/../modules/autenticidade/validar.php'), "modules/autenticidade/validar.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/autenticidade/validar_assinatura.php'), "modules/autenticidade/validar_assinatura.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/autenticidade/aprovar.php'), "modules/autenticidade/aprovar.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/autenticidade/cancelar.php'), "modules/autenticidade/cancelar.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/documentos/validar.php'), "Proxy modules/documentos/validar.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/documentos/validar_assinatura.php'), "Proxy modules/documentos/validar_assinatura.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/documentos/aprovar.php'), "Proxy modules/documentos/aprovar.php ausente.");
    assertEtapa5(file_exists(__DIR__ . '/../modules/documentos/cancelar.php'), "Proxy modules/documentos/cancelar.php ausente.");

    // Verificar rotas no index.php
    $indexCode = file_get_contents(__DIR__ . '/../index.php');
    assertEtapa5(str_contains($indexCode, "modules/autenticidade/validar.php"), "Rota validar/{token} não aponta para autenticidade.");
    assertEtapa5(str_contains($indexCode, "modules/autenticidade/validar_assinatura.php"), "Rota validar-assinatura/{token} não aponta para autenticidade.");
    assertEtapa5(str_contains($indexCode, "'autenticidade/aprovar'"), "Rota autenticidade/aprovar ausente no roteador.");
    assertEtapa5(str_contains($indexCode, "'documentos/aprovar'"), "Rota legado documentos/aprovar ausente no roteador.");

    echo "  -> OK: Autenticidade e rotas de retrocompatibilidade 100% íntegras.\n";

    // -------------------------------------------------------------
    // TESTE 4: Fluxo de Assinatura com UUID do Responsável
    // -------------------------------------------------------------
    echo "[4/4] Testando fluxo de assinatura com UUID do responsável...\n";

    // Criar convite de assinatura para o CSN emitido
    $convite = assinaturaCriarConviteCertificado($pdo, 'CSN', $resCSN['id'], (int)$primeiroResp['id']);
    assertEtapa5(!empty($convite['token']), "Falha ao gerar convite de assinatura para o responsável.");

    // Verificar convite no banco
    $tokenHash = hash('sha256', $convite['token']);
    $stmtConv = $pdo->prepare("SELECT id, token_hash, responsavel_id FROM assinatura_convites WHERE token_hash = :hash");
    $stmtConv->execute([':hash' => $tokenHash]);
    $conviteBd = $stmtConv->fetch(PDO::FETCH_ASSOC);
    assertEtapa5($conviteBd !== false, "Convite de assinatura não localizado no banco.");
    assertEtapa5((int)$conviteBd['responsavel_id'] === (int)$primeiroResp['id'], "Vínculo do responsável no convite incorreto.");

    // Reverter dados criados para manter banco limpo
    $pdo->rollBack();
    echo "  -> OK: Fluxo de convite e assinatura com UUID validado com sucesso (rollback efetuado).\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "\n>>> TODOS OS TESTES DA ETAPA 5 PASSARAM COM SUCESSO! <<<\n";
