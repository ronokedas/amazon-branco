<?php
/**
 * TESTES AUTOMATIZADOS: REGRAS DO ANALISTA NAVAL PARA MODALIDADES DE CERTIFICADOS
 * Arquivo: tests/certificados_modalidades_regras_analista_test.php
 * 
 * Valida rigorosamente:
 * 1. Condicional: 60 ou 90 dias (máx. 90d), permite exigências comuns sem A/S (bloqueia se houver A/S na vistoria ou no RAP).
 * 2. Provisório: Teto total de 180 dias da vistoria em seco menos dias do condicional; exige RAP aprovado e todas exigências físicas cumpridas (permitindo apenas inscrição/TI/PRPM).
 * 3. Definitivo: Validade por tipo de serviço (balsas 10 anos, empurradores 8 anos, motor 5 anos), exige RAP aprovado, 0 exigências pendentes e inscrição concluída.
 * 4. Helpers de classificação documental vs física e de cálculo de saldo.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/emissao_certificados.php';

function assertRegra(bool $condicao, string $msg): void
{
    if (!$condicao) {
        echo "\n[FALHA DE REGRA NAVAL] {$msg}\n";
        throw new RuntimeException($msg);
    }
}

echo "========================================================================\n";
echo "TESTE: REGRAS DO ANALISTA NAVAL - CERTIFICADOS (CONDICIONAL / PROVISÓRIO / DEFINITIVO)\n";
echo "========================================================================\n\n";

// -------------------------------------------------------------
// 1. TESTES UNITÁRIOS DE CLASSIFICAÇÃO CARTORIAL VS FÍSICA
// -------------------------------------------------------------
echo "[1/4] Testando helpers de classificação de exigências e anos de validade...\n";

assertRegra(ehExigenciaInscricaoCartorial('Pedido de inscrição da embarcação na Capitania') === true, 'Falha ao identificar pedido de inscrição');
assertRegra(ehExigenciaInscricaoCartorial('Atualização do Título de Inscrição (TIE/TIEM)') === true, 'Falha ao identificar TIE/TIEM');
assertRegra(ehExigenciaInscricaoCartorial('Apresentar comprovante de PRPM') === true, 'Falha ao identificar PRPM');
assertRegra(ehExigenciaInscricaoCartorial('Atualização de TI na Capitania dos Portos') === true, 'Falha ao identificar atualização de TI');
assertRegra(ehExigenciaInscricaoCartorial('Registro da embarcação na Agência Fluvial') === true, 'Falha ao identificar registro');

assertRegra(ehExigenciaInscricaoCartorial('Substituir extintor de incêndio vencido na praça de máquinas') === false, 'Extintor não pode ser classificado como inscrição');
assertRegra(ehExigenciaInscricaoCartorial('Completar dotação de coletes salva-vidas classe III') === false, 'Coletes não podem ser classificados como inscrição');
assertRegra(ehExigenciaInscricaoCartorial('Reparar solda com trinca no convés principal') === false, 'Estrutural não pode ser classificado como inscrição');

assertRegra(certificadoAnosValidadePorTipoEmbarcacao('Balsa') === 10, 'Balsa deve ter 10 anos');
assertRegra(certificadoAnosValidadePorTipoEmbarcacao('Chata de Carga') === 10, 'Chata deve ter 10 anos');
assertRegra(certificadoAnosValidadePorTipoEmbarcacao('Empurrador Fluvial') === 8, 'Empurrador deve ter 8 anos');
assertRegra(certificadoAnosValidadePorTipoEmbarcacao('Rebocador') === 8, 'Rebocador deve ter 8 anos');
assertRegra(certificadoAnosValidadePorTipoEmbarcacao('Lancha de Passageiros') === 5, 'Lancha deve ter 5 anos');

echo "  -> OK: Helpers unitários respondendo 100% de acordo com as normas da Marinha.\n\n";

// -------------------------------------------------------------
// 2. TESTES DE CÁLCULO DE SALDO DO PROVISÓRIO (180 DIAS - CONDICIONAL)
// -------------------------------------------------------------
echo "[2/4] Testando cálculo do saldo de validade do Certificado Provisório...\n";

// Teste sem condicional prévio (180 dias integrais)
$saldoZero = obterSaldoDiasValidadeProvisorio($pdo, 'sem-emb-id', '2026-01-01');
assertRegra($saldoZero['dias_totais_normam'] === 180, 'Dias totais NORMAM deve ser 180');
assertRegra($saldoZero['dias_usados_condicional'] === 0, 'Dias de condicional deve ser 0');
assertRegra($saldoZero['dias_remanescentes'] === 180, 'Dias remanescentes deve ser 180');
assertRegra($saldoZero['data_limite_normam'] === '2026-06-30', 'Data limite deve ser 30/06/2026');

echo "  -> OK: Cálculo base de 180 dias verificado com sucesso.\n\n";

// -------------------------------------------------------------
// 3. TESTES DE INTEGRAÇÃO TRANSAÇÃO: VISTORIA + RAP + CERTIFICADOS
// -------------------------------------------------------------
echo "[3/4] Testando regras de elegibilidade e bloqueios por modalidade...\n";

$pdo->beginTransaction();

try {
    $clienteId = gerarUUID();
    $embId = gerarUUID();
    $propId = gerarUUID();
    $servicoId = gerarUUID();
    $agendId = gerarUUID();
    $vistoriaId = gerarUUID();
    $rapId = gerarUUID();
    $usuarioId = gerarUUID();

    // 1. Inserir Usuário e Responsável de Assinatura
    $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) 
                   VALUES (:id, 'Vistoriador Naval Teste', 'vistoriador.modalidades@teste.com', 'hash', 'VISTORIADOR', 1)")
        ->execute([':id' => $usuarioId]);

    $pdo->prepare("INSERT INTO responsaveis_assinatura (uuid, usuario_id, nome_completo, cargo_titulo, registro_profissional, email, assinatura_arquivo, assinatura_hash, ativo)
                   VALUES (:uuid, :uid, 'Vistoriador Naval Teste', 'Engenheiro Naval', 'CREA 12345/D-PA', 'vistoriador.modalidades@teste.com', 'sig_test.png', 'hash123', 1)")
        ->execute([':uuid' => gerarUUID(), ':uid' => $usuarioId]);
    $respAssinaturaId = (int)$pdo->lastInsertId();

    // 2. Inserir Cliente e Embarcação
    $pdo->prepare("INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, status, ativo) 
                   VALUES (:id, 'Navegação Solimões Teste', 'PJ', '11.222.333/0001-99', 'armador', 'ATIVO', 1)")
        ->execute([':id' => $clienteId]);

    $pdo->prepare("INSERT INTO embarcacoes (id, proprietario_id, nome, registro, numero_inscricao, tipo_embarcacao, possui_propulsao, fabricante_motor, modelo_motor, numero_motor, potencia_kw, ativo)
                   VALUES (:id, :cid, 'EMPURRADOR MODALIDADES 2026', '021-999888', '021-999888', 'Empurrador', 1, 'Scania', 'DI13', 'SC12345', '450', 1)")
        ->execute([':id' => $embId, ':cid' => $clienteId]);

    // 3. Inserir Proposta e Agendamento
    $pdo->prepare("INSERT INTO servicos (id, nome, certificado_modelo, ativo) VALUES (:id, 'Vistoria Inicial Seco', 'CSN', 1)")
        ->execute([':id' => $servicoId]);

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, armador_id, status, valor_total, data_emissao) VALUES (:id, 'PROP-TEST-001/26', :cid, :aid, 'aprovada', 5000.00, CURDATE())")
        ->execute([':id' => $propId, ':cid' => $clienteId, ':aid' => $clienteId]);

    $pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, preco_aplicado, quantidade) VALUES (UUID(), :pid, :eid, :sid, 5000.00, 1)")
        ->execute([':pid' => $propId, ':eid' => $embId, ':sid' => $servicoId]);

    $dataVistoria = date('Y-m-d');
    $pdo->prepare("INSERT INTO agendamentos (id, proposta_id, embarcacao_id, cliente_id, tipo_vistoria, data_vistoria, status)
                   VALUES (:id, :pid, :eid, :cid, 'Vistoria Inicial Seco', :data_v, 'confirmado')")
        ->execute([':id' => $agendId, ':pid' => $propId, ':eid' => $embId, ':cid' => $clienteId, ':data_v' => $dataVistoria]);

    // 4. Inserir Vistoria Aprovada com Exigências e Assinada
    $pdo->prepare("INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, agendamento_id, data_vistoria, prazo_exigencias_dias, status, assinatura_status, responsavel_assinatura_id)
                   VALUES (:id, 'VIST-MODAL-001/26', :eid, :cid, :aid, :data_v, 60, 'APROVADA_COM_EXIGENCIAS', 'ASSINADO', :rid)")
        ->execute([':id' => $vistoriaId, ':eid' => $embId, ':cid' => $clienteId, ':aid' => $agendId, ':data_v' => $dataVistoria, ':rid' => $respAssinaturaId]);

    // 5. Inserir Processo de RAP para a Embarcação
    $pdo->prepare("INSERT INTO analises_planos (id, numero, proposta_id, embarcacao_id, solicitante_id, tipo_processo, objeto, status, criado_por)
                   VALUES (:id, 'RAP-MODAL-001/26', :pid, :eid, :cid, 'LC', 'Análise de Estabilidade e Estrutural', 'EM_ANALISE', :uid)")
        ->execute([':id' => $rapId, ':pid' => $propId, ':eid' => $embId, ':cid' => $clienteId, ':uid' => $usuarioId]);

    // Inserir exigência física comum na vistoria (ex: coletes salva-vidas)
    $exFisicaId = gerarUUID();
    $pdo->prepare("INSERT INTO vistoria_exigencias (id, vistoria_id, ordem, item, descricao, conforme, status_item, antes_de_suspender, bloco_vistoria)
                   VALUES (:id, :vid, 1, 'Salvatagem', 'Completar dotação com 4 coletes salva-vidas', 'nao', 'pendente', 0, 'seco')")
        ->execute([':id' => $exFisicaId, ':vid' => $vistoriaId]);

    echo "  [Cenário A] Vistoria com Exigência Física comum (sem A/S) + RAP em análise...\n";

    // A.1 Tentar emitir Definitivo -> Deve BLOQUEAR
    $resDefinitivo = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Definitivo', $embId);
    assertRegra($resDefinitivo['permitido'] === false, 'Definitivo deveria estar bloqueado por exigência pendente');
    echo "    -> [OK] Definitivo bloqueado com sucesso devido a exigências pendentes.\n";

    // A.2 Tentar emitir Provisório -> Deve BLOQUEAR porque a exigência pendente é FÍSICA e RAP não aprovado
    $resProvisorio = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Provisório', $embId);
    assertRegra($resProvisorio['permitido'] === false, 'Provisório deveria estar bloqueado por pendência física/RAP');
    echo "    -> [OK] Provisório bloqueado com sucesso devido a exigência física e RAP pendente.\n";

    // A.3 Tentar emitir Condicional -> Deve PERMITIR (exigência física comum sem A/S é permitida no condicional)
    $resCondicional = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Condicional', $embId);
    assertRegra($resCondicional['permitido'] === true, 'Condicional deveria ser permitido com exigência comum sem A/S');
    echo "    -> [OK] Condicional liberado com sucesso para exigências comuns sem A/S.\n";

    // A.4 Emitir Certificado Condicional de 60 dias
    $dadosCSNCond = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Condicional',
        'responsavel_assinatura_id' => $respAssinaturaId,
        'local_emissao' => 'Belém-PA',
        'data_emissao' => $dataVistoria,
        'data_validade' => date('Y-m-d', strtotime('+60 days', strtotime($dataVistoria))),
    ];
    $certCond = emitirCertificadoUnificado($pdo, 'CSN', $dadosCSNCond, $usuarioId);
    assertRegra(!empty($certCond['id']), 'Falha ao emitir Certificado CSN Condicional');
    echo "    -> [OK] Certificado CSN Condicional emitido com sucesso: {$certCond['numero']}.\n";

    // -------------------------------------------------------------
    // Cenário B: Bloqueio Estrito se houver exigência A/S no RAP
    // -------------------------------------------------------------
    echo "\n  [Cenário B] Testando bloqueio quando o RAP possui exigência A/S grave...\n";
    $exRapASId = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, categoria, ordem, descricao, as_impeditivo, status, saneamento_pendente, criado_por)
                   VALUES (:id, :aid, 'ESTABILIDADE', 1, 'Cálculo de estabilidade em avaria inconcluso - Risco grave', 1, 'PENDENTE', 0, :uid)")
        ->execute([':id' => $exRapASId, ':aid' => $rapId, ':uid' => $usuarioId]);

    $resCondAS = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Condicional', $embId);
    assertRegra($resCondAS['permitido'] === false, 'Condicional NÃO pode ser emitido se o RAP tiver A/S');
    echo "    -> [OK] Emissão do Condicional estritamente bloqueada por exigência A/S no RAP.\n";

    // Sanar a exigência A/S do RAP
    $pdo->prepare("UPDATE analise_planos_exigencias SET status = 'CUMPRIDA' WHERE id = :id")->execute([':id' => $exRapASId]);

    // -------------------------------------------------------------
    // Cenário C: Provisório - Cumprir exigências físicas, restando apenas INSCRIÇÃO
    // -------------------------------------------------------------
    echo "\n  [Cenário C] Cumprindo exigências físicas e deixando apenas exigência de INSCRIÇÃO (TI/PRPM) com RAP aprovado...\n";

    // Marcar exigência física da vistoria como cumprida
    $pdo->prepare("UPDATE vistoria_exigencias SET conforme = 'sim', status_item = 'cumprida' WHERE id = :id")->execute([':id' => $exFisicaId]);

    // Inserir exigência de inscrição da embarcação
    $exInscId = gerarUUID();
    $pdo->prepare("INSERT INTO vistoria_exigencias (id, vistoria_id, ordem, item, descricao, conforme, status_item, antes_de_suspender, bloco_vistoria)
                   VALUES (:id, :vid, 2, 'Documental / Capitania', 'Apresentar protocolo do pedido de inscrição da embarcação / TIE definitivo', 'nao', 'pendente', 0, 'seco')")
        ->execute([':id' => $exInscId, ':vid' => $vistoriaId]);

    // Aprovar e concluir o processo de RAP
    $pdo->prepare("UPDATE analises_planos SET status = 'CONCLUIDA' WHERE id = :id")->execute([':id' => $rapId]);
    $pdo->prepare("INSERT INTO analise_planos_pareceres (id, analise_id, versao, finalidade, resultado, status, numero, resumo, conclusao, responsavel_assinatura_id, criado_por)
                   VALUES (:id, :aid, 1, 'CONCLUSIVO', 'APROVADO', 'PUBLICADO', 'PAR-MODAL-001/26', 'Resumo', 'Parecer conclusivo aprovado', :rid, :uid)")
        ->execute([':id' => gerarUUID(), ':aid' => $rapId, ':rid' => $respAssinaturaId, ':uid' => $usuarioId]);

    // Testar Provisório agora:
    $resProvInsc = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Provisório', $embId);
    assertRegra($resProvInsc['permitido'] === true, 'Provisório DEVE ser liberado quando resta apenas exigência de inscrição e RAP aprovado!');
    echo "    -> [OK] Provisório liberado com sucesso: RAP aprovado e restando apenas exigência de inscrição.\n";

    // Testar se Definitivo continua bloqueado pela pendência de inscrição
    $resDefInsc = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Definitivo', $embId);
    assertRegra($resDefInsc['permitido'] === false, 'Definitivo NÃO pode ser emitido enquanto houver pendência de inscrição');
    echo "    -> [OK] Definitivo corretamente bloqueado porque a inscrição ainda não foi finalizada.\n";

    // Testar abatimento dos 60 dias do Condicional anterior no cálculo do Provisório
    $saldoComCond = obterSaldoDiasValidadeProvisorio($pdo, $embId, $dataVistoria, $vistoriaId);
    assertRegra($saldoComCond['dias_usados_condicional'] === 60, "Dias usados no condicional deve ser 60, obtido: {$saldoComCond['dias_usados_condicional']}");
    assertRegra($saldoComCond['dias_remanescentes'] === 120, "Saldo de dias remanescentes para o Provisório deve ser 120 (180 - 60), obtido: {$saldoComCond['dias_remanescentes']}");
    echo "    -> [OK] Dedução de validade do Provisório: 180 dias - 60 dias usados = {$saldoComCond['dias_remanescentes']} dias restantes.\n";

    // Emitir o Certificado Provisório
    $dadosCSNProv = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Provisório',
        'responsavel_assinatura_id' => $respAssinaturaId,
        'local_emissao' => 'Belém-PA',
        'data_emissao' => $dataVistoria,
    ];
    $certProv = emitirCertificadoUnificado($pdo, 'CSN', $dadosCSNProv, $usuarioId);
    assertRegra(!empty($certProv['id']), 'Falha ao emitir Certificado CSN Provisório');
    echo "    -> [OK] Certificado CSN Provisório emitido com sucesso: {$certProv['numero']}.\n";

    // -------------------------------------------------------------
    // Cenário D: Definitivo - Cumprir inscrição, zero exigências
    // -------------------------------------------------------------
    echo "\n  [Cenário D] Armador cumpriu inscrição na Capitania (0 exigências pendentes)...\n";
    $pdo->prepare("UPDATE vistoria_exigencias SET conforme = 'sim', status_item = 'cumprida' WHERE id = :id")->execute([':id' => $exInscId]);

    $resDef100 = avaliarElegibilidadeModalidadeCertificado($pdo, $vistoriaId, 'Definitivo', $embId);
    assertRegra($resDef100['permitido'] === true, 'Definitivo DEVE ser liberado com 0 pendências e RAP aprovado!');
    echo "    -> [OK] Definitivo liberado com sucesso: projeto aprovado e zero pendências.\n";

    // Emitir Certificado Definitivo
    $dadosCSNDef = [
        'vistoria_id' => $vistoriaId,
        'tipo' => 'Definitivo',
        'responsavel_assinatura_id' => $respAssinaturaId,
        'local_emissao' => 'Belém-PA',
        'data_emissao' => $dataVistoria,
    ];
    $certDef = emitirCertificadoUnificado($pdo, 'CSN', $dadosCSNDef, $usuarioId);
    assertRegra(!empty($certDef['id']), 'Falha ao emitir Certificado CSN Definitivo');
    echo "    -> [OK] Certificado CSN Definitivo emitido com sucesso: {$certDef['numero']}.\n";

    // Verificar se gerou janelas de convalidação anual para o Definitivo (8 anos para empurrador)
    $stmtConv = $pdo->prepare("SELECT COUNT(*) FROM csn_convalidacoes WHERE certificado_id = :id");
    $stmtConv->execute([':id' => $certDef['id']]);
    $qtdConv = (int)$stmtConv->fetchColumn();
    assertRegra($qtdConv === 7, "Empurrador (8 anos) deve ter 7 janelas anuais de convalidação, obtido: {$qtdConv}");
    echo "    -> [OK] Convalidações anuais geradas corretamente: {$qtdConv} vistorias anuais registradas.\n";

} catch (Throwable $e) {
    echo "\n  [FALHA] ERRO NO TESTE: " . $e->getMessage() . " na linha " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
    throw $e;
} finally {
    // Reverter transação de teste para preservar o banco de dados
    $pdo->rollBack();
    echo "\n  -> Transação de teste revertida (Rollback). Nenhuma alteração persistida no banco.\n";
}

echo "\n========================================================================\n";
echo "SUCESSO: TODOS OS TESTES DAS REGRAS DO ANALISTA NAVAL FORAM APROVADOS (100%)!\n";
echo "========================================================================\n";
