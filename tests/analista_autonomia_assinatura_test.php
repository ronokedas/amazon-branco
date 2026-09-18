<?php
require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertAutonomia(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE DE AUTONOMIA TÉCNICA DO ANALISTA NAVAL (NORMAM-202)\n";
echo " Assinatura Direta -> Publicado Imediato Sem Admin\n";
echo "=================================================================\n\n";

$analista = $pdo->query("SELECT u.id, u.nome, u.cargo FROM usuarios u 
                         INNER JOIN responsaveis_assinatura ra ON ra.usuario_id=u.id AND ra.ativo=1
                         WHERE u.cargo='ANALISTA' AND u.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

assertAutonomia(!empty($analista['id']), "Analista com assinatura digital localizado: {$analista['nome']}");
$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_nome'] = $analista['nome'];
$_SESSION['usuario_cargo'] = 'ANALISTA';
$_SESSION['usuario_logado'] = true;
$responsavelAnalista = analiseAcaoResponsavelDoAnalista($pdo, ['analista_id' => $analista['id']]);

// 1. Criar cliente e embarcação de teste
$clienteId = gerarUUID();
$embarcacaoId = gerarUUID();
$analiseId = gerarUUID();
$parecerId = null;
$parecerId2 = null;

try {
    $cnpj = '98.765.' . rand(100, 999) . '/0001-' . rand(10, 99);
    $pdo->prepare("INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, status, criado_por) VALUES (?, 'Armador Autonomia Test', 'PJ', ?, 'ativo', ?)")
        ->execute([$clienteId, $cnpj, $analista['id']]);

    $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, numero_inscricao, cliente_id, proprietario_id, arqueacao_bruta, criado_por) 
                   VALUES (?, 'B/M AUTONOMIA EXPRESS', 'CARGA', '021-998877', ?, ?, 45, ?)")
        ->execute([$embarcacaoId, $clienteId, $clienteId, $analista['id']]);

    // 2. Criar análise de planos
    $numAnalise = gerarNumeroDocumento('RAP', 'AM-RAP');
    $pdo->prepare("INSERT INTO analises_planos (id, numero, embarcacao_id, solicitante_id, tipo_processo, enquadramento, classe_certificacao, objeto, analista_id, status, criado_por)
                   VALUES (?, ?, ?, ?, 'LC', 'NORMAM-202', 'EC1', 'Análise Técnica de Autonomia', ?, 'EM_ANALISE', ?)")
        ->execute([$analiseId, $numAnalise, $embarcacaoId, $clienteId, $analista['id'], $analista['id']]);

    $analise = analisePlanosCarregar($pdo, $analiseId);

    // 3. Criar submissão e arquivos
    $submissaoId = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
                   VALUES (?, ?, 1, 'Revisão 1 de teste', CURDATE(), 'ANALISTA', ?)")
        ->execute([$submissaoId, $analiseId, $analista['id']]);

    // 4. Criar exigência
    $exigId = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, descricao, referencia_normativa, status, ordem, criado_por)
                   VALUES (?, ?, 'Ajustar cálculo de borda livre', 'NORMAM-202/DPC Cap 3', 'PENDENTE', 1, ?)")
        ->execute([$exigId, $analiseId, $analista['id']]);

    // 5. Testar finalização direta via analiseAcaoFinalizarParecer
    $parecerId = gerarUUID();
    $numParecer = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
    $snapshot = analisePlanosSnapshot($pdo, $analise, $submissaoId);

    $pdo->prepare("INSERT INTO analise_planos_pareceres (
        id, numero, analise_id, versao, finalidade, submissao_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por
    ) VALUES (?, ?, ?, 1, 'ANALISE_INICIAL', ?, 'EXIGENCIAS', 'Resumo do teste', 'Conclusão técnica com exigência', ?, 'AGUARDANDO_ASSINATURA_ANALISTA', ?, ?)")
        ->execute([$parecerId, $numParecer, $analiseId, $submissaoId, json_encode($snapshot, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

    // Inserir registro de exigência no parecer com baixa
    $pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, descricao_snapshot, referencia_snapshot, criado_por)
                   VALUES (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Exigência permanece pendente no ciclo 1.', 'Ajustar cálculo de borda livre', 'NORMAM-202/DPC Cap 3', ?)")
        ->execute([$parecerId, $exigId, $submissaoId, $analista['id']]);

    $parecerRow = $pdo->query("SELECT * FROM analise_planos_pareceres WHERE id='{$parecerId}'")->fetch(PDO::FETCH_ASSOC);

    // Executa a assinatura do analista
    $novoStatus = analiseAcaoFinalizarParecer($pdo, $analise, $parecerRow, $responsavelAnalista, $analista['id']);

    assertAutonomia($novoStatus === 'AGUARDANDO_DOCUMENTOS', "Processo transitou diretamente para AGUARDANDO_DOCUMENTOS sem passar por aprovação de admin");

    $parecerPos = $pdo->query("SELECT status, assinado_analista_em, publicado_em, validado_por, caminho_pdf_final, hash_pdf_final FROM analise_planos_pareceres WHERE id='{$parecerId}'")->fetch(PDO::FETCH_ASSOC);
    assertAutonomia($parecerPos['status'] === 'PUBLICADO', "Parecer passou imediatamente para status PUBLICADO");
    assertAutonomia(!empty($parecerPos['assinado_analista_em']), "Data de assinatura do analista registrada: {$parecerPos['assinado_analista_em']}");
    assertAutonomia(!empty($parecerPos['publicado_em']), "Data de publicação registrada: {$parecerPos['publicado_em']}");
    assertAutonomia($parecerPos['validado_por'] === $analista['id'], "Validado diretamente pelo analista");
    assertAutonomia(!empty($parecerPos['caminho_pdf_final']) && is_file(__DIR__ . '/../' . $parecerPos['caminho_pdf_final']), "PDF oficial gerado imediatamente: {$parecerPos['caminho_pdf_final']}");
    assertAutonomia(strlen($parecerPos['hash_pdf_final']) === 64, "Selo criptográfico SHA-256 congelado no PDF");

    // 6. Ciclo 2: Saneamento e Parecer Conclusivo
    $submissaoId2 = gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
                   VALUES (?, ?, 2, 'Revisão 2 com saneamento', CURDATE(), 'ANALISTA', ?)")
        ->execute([$submissaoId2, $analiseId, $analista['id']]);

    $analiseAtualizada = analisePlanosCarregar($pdo, $analiseId);

    $parecerId2 = gerarUUID();
    $numParecer2 = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
    $snapshot2 = analisePlanosSnapshot($pdo, $analiseAtualizada, $submissaoId2);

    $pdo->prepare("INSERT INTO analise_planos_pareceres (
        id, numero, analise_id, versao, finalidade, submissao_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por
    ) VALUES (?, ?, ?, 2, 'CONCLUSIVO', ?, 'APROVADO', 'Resumo conclusivo', 'Todos os planos aprovados com conformidade NORMAM-202.', ?, 'AGUARDANDO_ASSINATURA_ANALISTA', ?, ?)")
        ->execute([$parecerId2, $numParecer2, $analiseId, $submissaoId2, json_encode($snapshot2, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

    $pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, descricao_snapshot, referencia_snapshot, criado_por)
                   VALUES (UUID(), ?, ?, ?, 'CUMPRIDA', 'Exigência integralmente sanada na Revisão 2.', 'Ajustar cálculo de borda livre', 'NORMAM-202/DPC Cap 3', ?)")
        ->execute([$parecerId2, $exigId, $submissaoId2, $analista['id']]);

    $parecerRow2 = $pdo->query("SELECT * FROM analise_planos_pareceres WHERE id='{$parecerId2}'")->fetch(PDO::FETCH_ASSOC);

    // Assinatura do parecer conclusivo
    $statusFinal = analiseAcaoFinalizarParecer($pdo, $analiseAtualizada, $parecerRow2, $responsavelAnalista, $analista['id']);
    assertAutonomia($statusFinal === 'CONCLUIDA', "Processo transitou diretamente para CONCLUIDA na assinatura conclusiva do analista");

    $licenca = $pdo->query("SELECT * FROM certificados_lc WHERE analise_id='{$analiseId}'")->fetch(PDO::FETCH_ASSOC);
    assertAutonomia(!empty($licenca['numero_lc']), "Licença Naval LC emitida automaticamente com número: {$licenca['numero_lc']}");

} finally {
    // Limpeza garantida
    $pdo->exec("DELETE FROM certificados_lc WHERE analise_id='{$analiseId}'");
    $pdo->exec("DELETE FROM analise_planos_relatorio_exigencias WHERE relatorio_id IN ('{$parecerId}', '{$parecerId2}')");
    $pdo->exec("DELETE FROM analise_planos_exigencias WHERE analise_id='{$analiseId}'");
    $pdo->exec("DELETE FROM analise_planos_pareceres WHERE analise_id='{$analiseId}'");
    $pdo->exec("DELETE FROM analise_planos_submissoes WHERE analise_id='{$analiseId}'");
    $pdo->exec("DELETE FROM analises_planos WHERE id='{$analiseId}'");
    $pdo->exec("DELETE FROM embarcacoes WHERE id='{$embarcacaoId}'");
    $pdo->exec("DELETE FROM clientes WHERE id='{$clienteId}'");
}

echo "\n=================================================================\n";
echo " SUCESSO: AUTONOMIA DO ANALISTA NAVAL TOTALMENTE COMPROVADA!\n";
echo "=================================================================\n";
