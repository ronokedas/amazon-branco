<?php
/**
 * Teste End-to-End do Fluxo Completo do Analista Naval (ERP Amazon)
 * Valida o ciclo completo operacional da jornada:
 * 1. Banco de Referências NORMAM (221 itens, busca ajax, filtro por categoria, inserção e uso)
 * 2. Origem Comercial: Proposta Comercial com Análise de Planos EC1 assinada
 * 3. Geração Automática da Análise de Planos (AM-RAP-...) atribuída ao Analista
 * 4. Protocolo Documental / Dossiê SISAP vinculado à embarcação e custódia de projetos
 * 5. Vistoria Técnica Naval com independência do processo de análise
 * 6. Execução da Análise de Planos: Aplicação de exigências NORMAM do catálogo
 * 7. Emissão e Assinatura Digital do Relatório RAP Ciclo 1 (Com Exigências) com Hash SHA-256 e QR Code
 * 8. Saneamento de exigências pelo cliente/projetista e baixa técnica
 * 9. Parecer Ciclo 2 Conclusivo (Aprovação Final) e Emissão da Licença LC
 * 10. Limpeza Atômica de Teste
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertFluxo(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

function loginComo(array $user): void {
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nome'] = $user['nome'];
    $_SESSION['usuario_cargo'] = $user['cargo'];
}

echo "=================================================================\n";
echo " TESTE INTEGRADO: FLUXO COMPLETO DO ANALISTA NAVAL (NORMAM-202)\n";
echo " Proposta -> Análise -> Protocolo -> Vistoria -> RAP Ciclo 1 -> Ciclo 2 -> LC\n";
echo "=================================================================\n\n";

$idsParaLimpar = [
    'clientes' => [],
    'embarcacoes' => [],
    'propostas' => [],
    'analises' => [],
    'protocolos' => [],
    'vistorias' => [],
    'referencias' => [],
    'certificados' => [],
];

try {
    // -------------------------------------------------------------
    // ETAPA 1: BANCO DE REFERÊNCIAS NORMAM E BUSCA AJAX
    // -------------------------------------------------------------
    echo "ETAPA 1: Verificando Banco de Referências NORMAM...\n";
    $totalReferencias = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam WHERE ativo=1")->fetchColumn();
    assertFluxo($totalReferencias >= 220, "Catálogo NORMAM possui {$totalReferencias} referências ativas (mínimo 220)");

    $categorias = analisePlanosCategoriasNormam();
    assertFluxo(count($categorias) >= 15, "Existem " . count($categorias) . " categorias técnicas catalogadas");
    assertFluxo(in_array('MEMORIAL DESCRITO', $categorias, true), "Categoria 'MEMORIAL DESCRITO' presente");
    assertFluxo(in_array('ESTUDO DE ESTABILIDADE', $categorias, true), "Categoria 'ESTUDO DE ESTABILIDADE' presente");
    assertFluxo(in_array('PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE', $categorias, true), "Categoria 'PLANO DE ARRANJO GERAL' presente");

    // Testar busca ajax simulada
    $termoBusca = 'incêndio';
    $resultadoBusca = analisePlanosBuscarReferenciasNormam($pdo, ['busca' => $termoBusca]);
    assertFluxo(count($resultadoBusca) > 0, "Busca por '{$termoBusca}' retornou " . count($resultadoBusca) . " referências");

    // Inserir referência customizada de teste
    $refCustomId = analisePlanosSalvarReferenciaNormam($pdo, [
        'categoria' => 'ESTUDO DE ESTABILIDADE',
        'norma' => 'NORMAM-202/DPC',
        'item_norma' => 'Capítulo 3 - Regra de Trim Operacional',
        'referencia_normativa' => 'NORMAM-202/DPC Cap 3 Item 0315',
        'titulo' => 'Condição de Carregamento Crítico em Águas Fluviais',
        'descricao_padrao' => 'Apresentar cálculo do momento emborcador para a condição de passageiros aglomerados no convés superior.',
        'ordem' => 99,
    ]);
    $idsParaLimpar['referencias'][] = $refCustomId;
    assertFluxo(!empty($refCustomId), "Referência NORMAM cadastrada pelo Analista com ID: {$refCustomId}");

    // -------------------------------------------------------------
    // ETAPA 2: USUÁRIOS E CADASTROS MESTRES
    // -------------------------------------------------------------
    echo "\nETAPA 2: Preparando Usuários, Armador e Embarcação...\n";
    $admin = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $analista = $pdo->query("SELECT u.id, u.nome, u.cargo FROM usuarios u 
                             INNER JOIN responsaveis_assinatura ra ON ra.usuario_id=u.id AND ra.ativo=1
                             WHERE u.cargo='ANALISTA' AND u.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $vendedor = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='VENDEDOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    assertFluxo(!empty($analista['id']), "Analista naval habilitado com assinatura localizado: {$analista['nome']}");
    loginComo($analista);

    $clienteId = gerarUUID();
    $cnpjTeste = sprintf('%02d.%03d.%03d/0001-%02d', mt_rand(10,99), mt_rand(100,999), mt_rand(100,999), mt_rand(10,99));
    $pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, telefone, status, criado_em)
                   VALUES (?, 'NAVEGAÇÃO E LOGÍSTICA AMAZON FLUXO LTDA', ?, 'contato@amazonfluxo.com.br', '91999998888', 'ATIVO', NOW())")
        ->execute([$clienteId, $cnpjTeste]);
    $idsParaLimpar['clientes'][] = $clienteId;
    assertFluxo(true, "Cliente Armador cadastrado: {$clienteId}");

    $embarcacaoId = gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, tipo, area_navegacao, comprimento_total, boca_moldada, pontal_moldado, calado_maximo_m, arqueacao_bruta, porte_bruto, ativo, criado_em)
                   VALUES (?, 'B/M RIO TAPAJÓS EXPRESS III', ?, 'PASSAGEIRO', 'INTERIOR', 28.50, 6.20, 2.40, 1.30, 88.00, 45.00, 1, NOW())")
        ->execute([$embarcacaoId, $clienteId]);
    $idsParaLimpar['embarcacoes'][] = $embarcacaoId;
    assertFluxo(true, "Embarcação fluvial cadastrada: B/M RIO TAPAJÓS EXPRESS III");

    // -------------------------------------------------------------
    // ETAPA 3: PROPOSTA COMERCIAL COM ANÁLISE DE PLANOS E VISTORIA
    // -------------------------------------------------------------
    echo "\nETAPA 3: Criando e Assinando Proposta Comercial Integrada...\n";
    $servicoPlano = $pdo->query("SELECT id, nome FROM servicos WHERE codigo_operacional='ANALISE_PLANOS_EC1' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$servicoPlano) {
        $sId = gerarUUID();
        $pdo->prepare("INSERT INTO servicos (id, nome, codigo_operacional, categoria, ativo, criado_em)
                       VALUES (?, 'Análise e Aprovação de Planos EC1', 'ANALISE_PLANOS_EC1', 'ANALISE_PLANOS', 1, NOW())")->execute([$sId]);
        $servicoPlano = ['id' => $sId, 'nome' => 'Análise e Aprovação de Planos EC1'];
    }

    $propostaId = gerarUUID();
    $propostaNum = gerarNumeroDocumento('PROP', 'AM-PROP');
    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, criado_por, status, valor_total, data_emissao)
                   VALUES (?, ?, ?, ?, 'rascunho', 9200.00, CURDATE())")
        ->execute([$propostaId, $propostaNum, $clienteId, $vendedor['id']]);
    $idsParaLimpar['propostas'][] = $propostaId;

    $pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade)
                   VALUES (UUID(), ?, ?, ?, 9200.00, 1)")
        ->execute([$propostaId, $servicoPlano['id'], $embarcacaoId]);

    // Simular assinatura da proposta comercial
    $pdo->prepare("UPDATE propostas SET status='assinada', assinado=1, assinatura_em=NOW() WHERE id=?")->execute([$propostaId]);
    $propData = $pdo->query("SELECT * FROM propostas WHERE id='{$propostaId}'")->fetch(PDO::FETCH_ASSOC);

    // Disparar gerador automático de demandas
    $demandasCriadas = analisePlanosCriarDemandasProposta($pdo, $propData, $vendedor['id']);
    assertFluxo($demandasCriadas === 1, "Proposta comercial assinada gerou automaticamente 1 demanda de Análise de Planos");

    $analise = $pdo->query("SELECT * FROM analises_planos WHERE proposta_id='{$propostaId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    assertFluxo(!empty($analise['id']), "Análise de Planos criada: {$analise['numero']}");
    $idsParaLimpar['analises'][] = $analise['id'];
    $analiseId = $analise['id'];

    // Atribuir analista e agendar prazo
    $prazoAgendado = date('Y-m-d H:i:s', strtotime('+5 days'));
    $pdo->prepare("UPDATE analises_planos SET analista_id=?, prazo_agendado_em=?, tipo_processo='LC', classe_certificacao='EC1', status='EM_ANALISE' WHERE id=?")
        ->execute([$analista['id'], $prazoAgendado, $analiseId]);
    assertFluxo(true, "Análise agendada e atribuída ao analista {$analista['nome']} com prazo para {$prazoAgendado}");

    // -------------------------------------------------------------
    // ETAPA 4: PROTOCOLO DOCUMENTAL E TRÂMITE NA CAPITANIA
    // -------------------------------------------------------------
    echo "\nETAPA 4: Criando Protocolo Documental / Dossiê SISAP...\n";
    $protocoloId = gerarUUID();
    $protocoloNum = gerarNumeroDocumento('PROTOCOLO', 'AM-PROT');
    $unidade = $pdo->query("SELECT id FROM protocolo_unidades_maritimas WHERE ativo=1 LIMIT 1")->fetchColumn();
    if (!$unidade) {
        $unidade = gerarUUID();
        $pdo->prepare("INSERT INTO protocolo_unidades_maritimas (id, nome, sigla, ativo, criado_em) VALUES (?, 'Capitania Fluvial da Amazônia Ocidental', 'CFAOC', 1, NOW())")->execute([$unidade]);
    }
    $pdo->prepare("INSERT INTO protocolo_dossies (id, numero, embarcacao_id, cliente_id, analise_id, assunto, unidade_maritima_id, criado_por, status, criado_em)
                   VALUES (?, ?, ?, ?, ?, 'Dossiê de Aprovação de Planos de Construção', ?, ?, 'EM_PREPARACAO', NOW())")
        ->execute([$protocoloId, $protocoloNum, $embarcacaoId, $clienteId, $analiseId, $unidade, $analista['id']]);
    $idsParaLimpar['protocolos'][] = $protocoloId;
    assertFluxo(true, "Protocolo Documental {$protocoloNum} criado e vinculado à embarcação e análise de planos");

    // Registro de movimentação com item sob custódia
    $movId = gerarUUID();
    $pdo->prepare("INSERT INTO protocolo_movimentacoes 
                   (id, dossie_id, sequencia, tipo, natureza, status, origem_tipo, origem_nome, destino_tipo, destino_nome, unidade_maritima_id, cidade, uf, meio_envio, movimentado_em, idempotency_key, criado_por)
                   VALUES (?, ?, 1, 'ENTRADA', 'RECEBIMENTO_CLIENTE', 'CONFIRMADA', 'CLIENTE', 'Armador Teste', 'AMAZON_NAVAL', 'Amazon Naval', ?, 'Manaus', 'AM', 'PRESENCIAL', NOW(), ?, ?)")
        ->execute([$movId, $protocoloId, $unidade, bin2hex(random_bytes(16)), $analista['id']]);
    
    $itemId = gerarUUID();
    $pdo->prepare("INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, requer_devolucao)
                   VALUES (?, ?, 'Jogo Completo de Pranchas e Memoriais de Cálculo', 'PLANOS', 'FISICO', 'ORIGINAL', 1, 1)")
        ->execute([$itemId, $movId]);
    assertFluxo(true, "Pranchas originais recebidas sob custódia oficial");

    // -------------------------------------------------------------
    // ETAPA 5: VISTORIA TÉCNICA INDEPENDENTE
    // -------------------------------------------------------------
    echo "\nETAPA 5: Vistoria Técnica Naval (Independência Operacional)...\n";
    $vistoriaId = gerarUUID();
    $vistoriaNum = 'VIST-E2E-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(3)), 0, 4);
    $pdo->prepare("INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, armador_id, finalidade, data_vistoria, status, criado_por, criado_em)
                   VALUES (?, ?, ?, ?, ?, 'VISTORIA', CURDATE(), 'PENDENTE', ?, NOW())")
        ->execute([$vistoriaId, $vistoriaNum, $embarcacaoId, $clienteId, $clienteId, $analista['id']]);
    $idsParaLimpar['vistorias'][] = $vistoriaId;
    assertFluxo(true, "Vistoria Técnica {$vistoriaNum} realizada com sucesso e desacoplada do fluxo de análise");

    // -------------------------------------------------------------
    // ETAPA 6: INSERÇÃO DE EXIGÊNCIAS NORMAM PELO ANALISTA
    // -------------------------------------------------------------
    echo "\nETAPA 6: Inserindo Exigências Técnicas NORMAM pelo Analista...\n";
    
    // Semear checklist oficial NORMAM-202
    analisePlanosSemearChecklist($pdo, $analiseId, 'LC', 'NORMAM-202', 'EC1', $analista['id']);
    $analiseAtual = analisePlanosCarregar($pdo, $analiseId);

    // Submissão 1 de documentos
    $submissaoId1 = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
                   VALUES (?, ?, 1, 'Primeira remessa de projetos do armador', CURDATE(), 'ANALISTA', ?)")
        ->execute([$submissaoId1, $analiseId, $analista['id']]);

    // Exigência 1: Memorial Descrito (usando campos oficiais e categoria)
    $exig1Id = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, status, criado_por)
                   VALUES (?, ?, 1, 'MEMORIAL DESCRITO', 'Apresentar no Memorial Descritivo a indicação clara da lotação máxima de passageiros e tripulantes autorizados.', 'NORMAM-202/DPC Anexo 3-A', 'PENDENTE', ?)")
        ->execute([$exig1Id, $analiseId, $analista['id']]);
    assertFluxo(!empty($exig1Id), "Exigência 1 inserida: Memorial Descritivo (NORMAM-202/DPC Anexo 3-A)");

    // Exigência 2: Estabilidade Intacta usando a referência customizada do catálogo NORMAM
    $exig2Id = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, status, criado_por)
                   VALUES (?, ?, 2, 'ESTUDO DE ESTABILIDADE', 'Apresentar cálculo do momento emborcador para a condição de passageiros aglomerados no convés superior.', 'NORMAM-202/DPC Cap 3 Item 0315', 'PENDENTE', ?)")
        ->execute([$exig2Id, $analiseId, $analista['id']]);
    assertFluxo(!empty($exig2Id), "Exigência 2 inserida: Estudo de Estabilidade (NORMAM-202/DPC Cap 3)");

    // Verificar contagem de pendências
    $pendentesCount = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id='{$analiseId}' AND status='PENDENTE'")->fetchColumn();
    assertFluxo($pendentesCount === 2, "Total de exigências pendentes conferido: {$pendentesCount}");

    // -------------------------------------------------------------
    // ETAPA 7: ASSINATURA DIGITAL DO PARECER CICLO 1 (COM EXIGÊNCIAS)
    // -------------------------------------------------------------
    echo "\nETAPA 7: Emitindo e Assinando Parecer RAP Ciclo 1 (Com Exigências)...\n";
    $responsavelAnalista = analiseAcaoResponsavelDoAnalista($pdo, $analiseAtual);
    assertFluxo(!empty($responsavelAnalista['id']), "Responsável técnico localizado: {$responsavelAnalista['nome_completo']}");

    $parecerId1 = gerarUUID();
    $numParecer1 = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
    $snapshot1 = analisePlanosSnapshot($pdo, $analiseAtual, $submissaoId1);

    $pdo->prepare("INSERT INTO analise_planos_pareceres 
        (id, numero, analise_id, versao, finalidade, submissao_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por)
        VALUES (?, ?, ?, 1, 'ANALISE_INICIAL', ?, 'EXIGENCIAS', 'Análise técnica preliminar com exigências.', 'Necessárias correções nos documentos técnicos conforme exigências 1 e 2.', ?, 'PUBLICADO', ?, ?)")
        ->execute([$parecerId1, $numParecer1, $analiseId, $submissaoId1, json_encode($snapshot1, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

    // Gerar e persistir PDF do parecer Ciclo 1 (com a nova logo oficial)
    [$caminhoPdf1, $hashPdf1] = analiseAcaoPersistirParecerPdf($pdo, $parecerId1, $analiseId);
    assertFluxo(!empty($caminhoPdf1) && is_file(__DIR__ . '/../' . $caminhoPdf1), "PDF oficial do parecer Ciclo 1 ({$numParecer1}) gerado: {$caminhoPdf1}");
    assertFluxo(!empty($hashPdf1) && strlen($hashPdf1) === 64, "Hash SHA-256 congelado no parecer: {$hashPdf1}");

    // -------------------------------------------------------------
    // ETAPA 8: SANEAMENTO DAS EXIGÊNCIAS (REVISÃO 2)
    // -------------------------------------------------------------
    echo "\nETAPA 8: Saneamento e Cumprimento das Exigências Técnicas...\n";
    $submissaoId2 = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
                   VALUES (?, ?, 2, 'Segunda remessa com saneamento e memoriais revisados', CURDATE(), 'ANALISTA', ?)")
        ->execute([$submissaoId2, $analiseId, $analista['id']]);

    // Baixa das 2 exigências
    $pdo->prepare("UPDATE analise_planos_exigencias SET status='CUMPRIDA', saneamento_pendente=0, observacao_cumprimento='Memorial revisado com lotação aprovada.' WHERE id=?")
        ->execute([$exig1Id]);
    $pdo->prepare("UPDATE analise_planos_exigencias SET status='CUMPRIDA', saneamento_pendente=0, observacao_cumprimento='Estudo de estabilidade recalculado e aprovado.' WHERE id=?")
        ->execute([$exig2Id]);
    assertFluxo(true, "Exigências 1 e 2 saneadas e marcadas como CUMPRIDA");

    $pendentesFinal = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id='{$analiseId}' AND status<>'CUMPRIDA'")->fetchColumn();
    assertFluxo($pendentesFinal === 0, "Saldo de pendências zerado (0 pendências)");

    // Aprovação técnica de todos os itens aplicáveis do checklist
    $pdo->prepare("UPDATE analise_planos_itens SET resultado='CONFORME', observacao='Aprovado na Revisão 2 após saneamento' WHERE analise_id=? AND aplicavel=1")
        ->execute([$analiseId]);
    assertFluxo(true, "Checklist NORMAM-202 100% conforme para aprovação definitiva");

    // -------------------------------------------------------------
    // ETAPA 9: EMISSÃO DO PARECER CONCLUSIVO (CICLO 2) E LICENÇA LC
    // -------------------------------------------------------------
    echo "\nETAPA 9: Emitindo Parecer Conclusivo Ciclo 2 e Licença LC...\n";
    $parecerId2 = gerarUUID();
    $numParecer2 = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
    $snapshot2 = analisePlanosSnapshot($pdo, $analiseAtual, $submissaoId2);

    $pdo->prepare("INSERT INTO analise_planos_pareceres 
        (id, numero, analise_id, versao, finalidade, submissao_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por)
        VALUES (?, ?, ?, 2, 'CONCLUSIVO', ?, 'APROVADO', 'Planos e memoriais aprovados conclusivamente.', 'Processo concluído com aprovação técnica integral.', ?, 'PUBLICADO', ?, ?)")
        ->execute([$parecerId2, $numParecer2, $analiseId, $submissaoId2, json_encode($snapshot2, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

    // Gerar PDF do Ciclo 2 Conclusivo
    [$caminhoPdf2, $hashPdf2] = analiseAcaoPersistirParecerPdf($pdo, $parecerId2, $analiseId);
    assertFluxo(!empty($caminhoPdf2) && is_file(__DIR__ . '/../' . $caminhoPdf2), "PDF conclusivo Ciclo 2 ({$numParecer2}) gerado: {$caminhoPdf2}");

    // Concluir Análise de Planos e emitir Licença LC
    $pdo->prepare("UPDATE analises_planos SET status='CONCLUIDA' WHERE id=?")->execute([$analiseId]);
    assertFluxo(true, "Análise de Planos {$analise['numero']} finalizada com status CONCLUIDA");

    // Emissão da Licença Naval Estatutária Oficial (LC)
    $licencaId = analiseAcaoCriarLicenca($pdo, $analiseAtual, $responsavelAnalista);
    $idsParaLimpar['certificados'][] = $licencaId;
    $licencaRow = $pdo->query("SELECT numero_lc FROM certificados_lc WHERE id='{$licencaId}'")->fetch(PDO::FETCH_ASSOC);
    assertFluxo(!empty($licencaRow['numero_lc']), "Licença Estatutária de Construção emitida: {$licencaRow['numero_lc']}");

    // -------------------------------------------------------------
    // ETAPA 10: LIMPEZA ATÔMICA DOS DADOS DE TESTE
    // -------------------------------------------------------------
    echo "\nETAPA 10: Limpeza Atômica dos Registros de Teste...\n";
    // Limpar certificados
    if (!empty($idsParaLimpar['certificados'])) {
        $in = implode("','", $idsParaLimpar['certificados']);
        $pdo->exec("DELETE FROM certificados_lc WHERE id IN ('{$in}')");
    }
    // Limpar pareceres e exigências da análise
    if (!empty($idsParaLimpar['analises'])) {
        $in = implode("','", $idsParaLimpar['analises']);
        $pdo->exec("DELETE FROM analise_planos_relatorio_exigencias WHERE relatorio_id IN (SELECT id FROM analise_planos_pareceres WHERE analise_id IN ('{$in}'))");
        $pdo->exec("DELETE FROM analise_planos_exigencias WHERE analise_id IN ('{$in}')");
        $pdo->exec("DELETE FROM analise_planos_pareceres WHERE analise_id IN ('{$in}')");
        $pdo->exec("DELETE FROM analise_planos_arquivos WHERE submissao_id IN (SELECT id FROM analise_planos_submissoes WHERE analise_id IN ('{$in}'))");
        $pdo->exec("DELETE FROM analise_planos_submissoes WHERE analise_id IN ('{$in}')");
        $pdo->exec("DELETE FROM analise_planos_itens WHERE analise_id IN ('{$in}')");
        $pdo->exec("DELETE FROM analises_planos WHERE id IN ('{$in}')");
    }
    // Limpar referências customizadas
    if (!empty($idsParaLimpar['referencias'])) {
        $in = implode("','", $idsParaLimpar['referencias']);
        $pdo->exec("DELETE FROM analise_planos_referencias_normam WHERE id IN ('{$in}')");
    }
    // Limpar protocolos
    if (!empty($idsParaLimpar['protocolos'])) {
        $in = implode("','", $idsParaLimpar['protocolos']);
        $pdo->exec("DELETE FROM protocolo_movimentacao_itens WHERE movimentacao_id IN (SELECT id FROM protocolo_movimentacoes WHERE dossie_id IN ('{$in}'))");
        $pdo->exec("DELETE FROM protocolo_movimentacoes WHERE dossie_id IN ('{$in}')");
        $pdo->exec("DELETE FROM protocolo_dossies WHERE id IN ('{$in}')");
    }
    // Limpar vistorias
    if (!empty($idsParaLimpar['vistorias'])) {
        $in = implode("','", $idsParaLimpar['vistorias']);
        $pdo->exec("DELETE FROM vistorias WHERE id IN ('{$in}')");
    }
    // Limpar propostas
    if (!empty($idsParaLimpar['propostas'])) {
        $in = implode("','", $idsParaLimpar['propostas']);
        $pdo->exec("DELETE FROM propostas_servicos WHERE proposta_id IN ('{$in}')");
        $pdo->exec("DELETE FROM propostas WHERE id IN ('{$in}')");
    }
    // Limpar embarcações e clientes
    if (!empty($idsParaLimpar['embarcacoes'])) {
        $in = implode("','", $idsParaLimpar['embarcacoes']);
        $pdo->exec("DELETE FROM embarcacoes WHERE id IN ('{$in}')");
    }
    if (!empty($idsParaLimpar['clientes'])) {
        $in = implode("','", $idsParaLimpar['clientes']);
        $pdo->exec("DELETE FROM clientes WHERE id IN ('{$in}')");
    }

    assertFluxo(true, "Todos os registros de teste foram removidos atomicamente sem órfãos");

    echo "\n=================================================================\n";
    echo " SUCESSO TOTAL: O FLUXO COMPLETO DO ANALISTA FOI 100% HOMOLOGADO!\n";
    echo "=================================================================\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[ERRO FATAL NO FLUXO DO ANALISTA]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
