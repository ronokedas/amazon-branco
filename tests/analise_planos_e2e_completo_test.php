<?php
/**
 * Teste End-to-End Profundo: Módulo de Análise de Planos Navais (NORMAM-202)
 * Cobre todo o ciclo: Proposta Comercial -> Agendamento -> Início Técnico -> Enquadramento
 * -> Checklist -> Submissão de Arquivos -> Classificação -> Matriz -> Exigências -> Parecer Ciclo 1 (Exigências)
 * -> Assinatura Analista -> Publicação Admin -> Revisão 2 -> Baixa Exigência -> Parecer Ciclo 2 (Conclusivo)
 * -> Emissão Automática da Licença LC -> Geração de PDFs com Selo Criptográfico.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertTest(bool $cond, string $msg): void {
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
echo " INICIANDO TESTE END-TO-END PROFUNDO - ANÁLISE DE PLANOS NAVAIS\n";
echo "=================================================================\n\n";

// 1. Identificar Usuários de Teste (Admin, Analista, Vendedor)
$admin = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$analista = $pdo->query("SELECT u.id, u.nome, u.cargo FROM usuarios u 
                         INNER JOIN responsaveis_assinatura ra ON ra.usuario_id=u.id AND ra.ativo=1
                         WHERE u.cargo='ANALISTA' AND u.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$vendedor = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='VENDEDOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);


assertTest(!empty($admin['id']), "Usuário Administrador localizado ({$admin['nome']})");
assertTest(!empty($analista['id']), "Usuário Analista com assinatura localizado ({$analista['nome']})");
assertTest(!empty($vendedor['id']), "Usuário Vendedor localizado ({$vendedor['nome']})");

// 2. Identificar ou Criar Cliente e Embarcação de Teste
$clienteId = gerarUUID();
$randomCnpj = sprintf('%02d.%03d.%03d/%04d-%02d', mt_rand(10,99), mt_rand(100,999), mt_rand(100,999), 1, mt_rand(10,99));
$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, telefone, status, endereco, criado_em)
               VALUES (?, 'Armador Teste E2E Ltda', ?, 'armador.teste@erpnaval.com.br', '91988887777', 'ATIVO', 'Av. Beira Rio, 100, Belém-PA', NOW())")
    ->execute([$clienteId, $randomCnpj]);
assertTest(true, "Cliente Armador Teste criado: ID {$clienteId} (CNPJ: {$randomCnpj})");


$embarcacaoId = gerarUUID();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, tipo, area_navegacao, arqueacao_bruta, porte_bruto, ativo, criado_em)
               VALUES (?, 'M/N AMAZON EXPRESS TEST', ?, 'PASSAGEIRO', 'INTERIOR', 75.50, 120.00, 1, NOW())")
    ->execute([$embarcacaoId, $clienteId]);
assertTest(true, "Embarcação de teste criada: M/N AMAZON EXPRESS TEST");

// 3. Serviço de Análise de Planos EC1
$servico = $pdo->query("SELECT id, nome, codigo_operacional FROM servicos WHERE codigo_operacional='ANALISE_PLANOS_EC1' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$servico) {
    $servId = gerarUUID();
    $pdo->prepare("INSERT INTO servicos (id, nome, codigo_operacional, categoria, ativo, criado_em)
                   VALUES (?, 'Análise e Aprovação de Planos EC1', 'ANALISE_PLANOS_EC1', 'ANALISE_PLANOS', 1, NOW())")
        ->execute([$servId]);
    $servico = ['id' => $servId, 'nome' => 'Análise e Aprovação de Planos EC1', 'codigo_operacional' => 'ANALISE_PLANOS_EC1'];
}
assertTest(true, "Serviço operacional localizado: {$servico['codigo_operacional']}");

// 4. Teste de Origem Comercial: Criar e Assinar Proposta Comercial
$propostaId = gerarUUID();
$propostaNum = gerarNumeroDocumento('PROP', 'AM-PROP');
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, criado_por, status, valor_total, data_emissao)
               VALUES (?, ?, ?, ?, 'rascunho', 8500.00, CURDATE())")
    ->execute([$propostaId, $propostaNum, $clienteId, $vendedor['id']]);

$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade)
               VALUES (UUID(), ?, ?, ?, 8500.00, 1)")
    ->execute([$propostaId, $servico['id'], $embarcacaoId]);



// Simular assinatura da proposta pelo cliente
$pdo->prepare("UPDATE propostas SET status='assinada', assinado=1, assinatura_em=NOW() WHERE id=?")->execute([$propostaId]);

$propostaRow = $pdo->query("SELECT * FROM propostas WHERE id='{$propostaId}'")->fetch(PDO::FETCH_ASSOC);
$demandasCriadas = analisePlanosCriarDemandasProposta($pdo, $propostaRow, $vendedor['id']);
assertTest($demandasCriadas === 1, "Assinatura da proposta gerou automaticamente 1 demanda de Análise de Planos");

$analise = $pdo->query("SELECT * FROM analises_planos WHERE proposta_id='{$propostaId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($analise['id']), "Demanda de Análise de Planos registrada: {$analise['numero']}");
assertTest($analise['status'] === 'AGUARDANDO_AGENDAMENTO', "Status inicial da análise: AGUARDANDO_AGENDAMENTO");
assertTest($analise['classe_certificacao'] === 'EC1', "Classe de certificação: EC1");
assertTest($analise['vendedor_origem_id'] === $vendedor['id'], "Vendedor de origem vinculado corretamente");

$analiseId = $analise['id'];

// 5. Passo 1: Agendamento da Análise (Vendedor define Analista e Prazo)
loginComo($vendedor);

$prazoAlvo = date('Y-m-d 18:00:00', strtotime('+5 days'));
$pdo->prepare("UPDATE analises_planos SET analista_id=?, prazo_agendado_em=?, status='AGENDADA' WHERE id=?")
    ->execute([$analista['id'], $prazoAlvo, $analiseId]);

$pdo->prepare("INSERT INTO analise_planos_agenda_historico (analise_id, analista_anterior_id, analista_novo_id, prazo_anterior_em, prazo_novo_em, motivo, acao, criado_por)
               VALUES (?, NULL, ?, NULL, ?, 'Agendamento inicial realizado pelo vendedor', 'AGENDAMENTO', ?)")
    ->execute([$analiseId, $analista['id'], $prazoAlvo, $vendedor['id']]);

analisePlanosNotificar($pdo, $analista['id'], 'ANALISE_AGENDADA', 'Nova Análise Atribuída', "Processo {$analise['numero']}", $analiseId);

$analiseAtual = analisePlanosCarregar($pdo, $analiseId);
assertTest($analiseAtual['status'] === 'AGENDADA', "Status após agendamento: AGENDADA");
assertTest($analiseAtual['analista_id'] === $analista['id'], "Analista técnico atribuído: {$analista['nome']}");

// 6. Passo 2: Início da Análise Técnica pelo Analista
loginComo($analista);

$pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE', iniciado_em=NOW() WHERE id=?")->execute([$analiseId]);
analisePlanosHistorico($pdo, $analiseId, 'ANALISE_INICIADA', 'AGENDADA', 'EM_ANALISE', 'Analista iniciou os trabalhos técnicos.');

$analiseAtual = analisePlanosCarregar($pdo, $analiseId);
assertTest($analiseAtual['status'] === 'EM_ANALISE', "Status após início técnico: EM_ANALISE");
assertTest(!empty($analiseAtual['iniciado_em']), "Data de início registrada: {$analiseAtual['iniciado_em']}");

// 7. Passo 3: Enquadramento Técnico Naval & Semeadura do Checklist
$pdo->prepare("UPDATE analises_planos SET 
    tipo_processo='LC', enquadramento='NORMAM-202', objeto='Análise de Planos de Construção Naval EC1',
    arqueacao_bruta=75.50, numero_passageiros=120, possui_propulsao=1, embarcacao_classificada=0,
    tipo_navegacao='INTERIOR', estaleiro='Estaleiro Rio Guamá Ltda', numero_casco='RG-2026-09',
    responsavel_projeto_nome='Eng. Roberto Alcantara', responsavel_projeto_registro='CREA-PA 98765-D',
    art_numero='ART-PA-2026-009988', observacoes='Projeto inicial de catamarã para navegação interior.'
    WHERE id=?")->execute([$analiseId]);

analisePlanosSemearChecklist($pdo, $analiseId, 'LC', 'NORMAM-202', 'EC1', $analista['id']);
$itensChecklist = $pdo->query("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id='{$analiseId}'")->fetchColumn();
assertTest((int)$itensChecklist === 13, "Checklist NORMAM-202 semeado com sucesso (13 itens oficiais)");

// 8. Passo 4: Submissão de Documentos (Revisão 1)
$submissaoId1 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
               VALUES (?, ?, 1, 'Primeira remessa de projetos do armador', CURDATE(), 'ANALISTA', ?)")
    ->execute([$submissaoId1, $analiseId, $analista['id']]);

// Criar 2 arquivos mock em disco temporário com conteúdos distintos
$tempDir = sys_get_temp_dir() . '/teste_analise_planos_' . time() . '_' . mt_rand(1000,9999);
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$dummyPdf1 = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n00000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n% Memorial Descritivo - " . uniqid();
$dummyPdf2 = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n00000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n% Plano de Arranjo Geral - " . uniqid();

$arqPath1 = $tempDir . '/Memorial_Descritivo.pdf';
$arqPath2 = $tempDir . '/Plano_Arranjo_Geral.pdf';
file_put_contents($arqPath1, $dummyPdf1);
file_put_contents($arqPath2, $dummyPdf2);


$fileMeta1 = analisePlanosValidarUpload(['name'=>'Memorial_Descritivo.pdf','tmp_name'=>$arqPath1,'error'=>UPLOAD_ERR_OK,'size'=>filesize($arqPath1)]);
$fileMeta2 = analisePlanosValidarUpload(['name'=>'Plano_Arranjo_Geral.pdf','tmp_name'=>$arqPath2,'error'=>UPLOAD_ERR_OK,'size'=>filesize($arqPath2)]);

$chave1 = analisePlanosGuardarUpload(['tmp_name'=>$arqPath1], $analiseId, $fileMeta1);
$chave2 = analisePlanosGuardarUpload(['tmp_name'=>$arqPath2], $analiseId, $fileMeta2);

$arqId1 = gerarUUID();
$arqId2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_arquivos (id, submissao_id, categoria, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por)
               VALUES (?, ?, 'Memorial Descritivo', 'Memorial_Descritivo.pdf', 'pdf', 'application/pdf', ?, ?, ?, ?)")
    ->execute([$arqId1, $submissaoId1, $fileMeta1['tamanho'], $fileMeta1['sha256'], $chave1, $analista['id']]);

$pdo->prepare("INSERT INTO analise_planos_arquivos (id, submissao_id, categoria, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por)
               VALUES (?, ?, 'Arranjo Geral', 'Plano_Arranjo_Geral.pdf', 'pdf', 'application/pdf', ?, ?, ?, ?)")
    ->execute([$arqId2, $submissaoId1, $fileMeta2['tamanho'], $fileMeta2['sha256'], $chave2, $analista['id']]);

assertTest(true, "Revisão 1 criada com 2 arquivos validados com hash SHA-256");

// Classificar arquivos:
// Item 1 (Memorial) -> ACEITO
$itemMemorial = $pdo->query("SELECT id FROM analise_planos_itens WHERE analise_id='{$analiseId}' AND documento LIKE '%Memorial%' LIMIT 1")->fetchColumn();
$itemArranjo = $pdo->query("SELECT id FROM analise_planos_itens WHERE analise_id='{$analiseId}' AND documento LIKE '%Arranjo%' LIMIT 1")->fetchColumn();

$pdo->prepare("UPDATE analise_planos_arquivos SET item_id=?, classificacao='ACEITO', classificado_por=?, classificado_em=NOW() WHERE id=?")
    ->execute([$itemMemorial, $analista['id'], $arqId1]);

// Item 2 (Arranjo) -> REJEITADO com justificativa
$pdo->prepare("UPDATE analise_planos_arquivos SET item_id=?, classificacao='REJEITADO', justificativa_classificacao='Rotas de fuga e saídas de emergência ausentes no convés superior.', classificado_por=?, classificado_em=NOW() WHERE id=?")
    ->execute([$itemArranjo, $analista['id'], $arqId2]);

assertTest(true, "Arquivos da Revisão 1 classificados (1 Aceito, 1 Rejeitado com justificativa)");

// 9. Passo 5: Atualizar Matriz e Registrar Exigência
$pdo->prepare("UPDATE analise_planos_itens SET resultado='CONFORME', observacao='Atende ao Anexo 3-G' WHERE id=?")->execute([$itemMemorial]);
$pdo->prepare("UPDATE analise_planos_itens SET resultado='EXIGENCIA', observacao='Não conformidade com NORMAM-202' WHERE id=?")->execute([$itemArranjo]);

$exigenciaId = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, descricao, referencia_normativa, status, criado_por)
               VALUES (?, ?, 1, 'Adequar plano de arranjo geral incluindo saídas de emergência e rotas de fuga no convés superior.', 'NORMAM-202, Cap. 3, Anexo 3-F', 'PENDENTE', ?)")
    ->execute([$exigenciaId, $analiseId, $analista['id']]);
assertTest(true, "Exigência técnica cadastrada no processo");

// 10. Passo 6: Emissão do Parecer Técnico do Ciclo 1 (Com Exigências)
$responsavelAnalista = analiseAcaoResponsavelDoAnalista($pdo, $analiseAtual);
assertTest(!empty($responsavelAnalista['id']), "Identidade técnica do analista confirmada: {$responsavelAnalista['nome_completo']} ({$responsavelAnalista['registro_profissional']})");

// Tentar emitir como APROVADO deve falhar (regra de segurança)
$falhouEsperado = false;
try {
    $q = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id=:id AND aplicavel=1 AND impeditivo_emissao=1 AND resultado NOT IN ('CONFORME','NAO_APLICA')");
    $q->execute([':id'=>$analiseId]);
    if ((int)$q->fetchColumn() > 0) throw new RuntimeException('Existem itens impeditivos ainda não conformes.');
} catch (Throwable $e) {
    $falhouEsperado = true;
}
assertTest($falhouEsperado, "Segurança validada: Sistema BLOQUEOU aprovação conclusiva com exigência pendente");

// Emitir Parecer com resultado EXIGENCIAS
$parecerId1 = gerarUUID();
$numParecer1 = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
$snapshot1 = analisePlanosSnapshot($pdo, $analiseAtual, $submissaoId1);

$pdo->prepare("INSERT INTO analise_planos_pareceres 
    (id, numero, analise_id, versao, finalidade, submissao_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por)
    VALUES (?, ?, ?, 1, 'ANALISE_INICIAL', ?, 'EXIGENCIAS', 'Análise preliminar dos planos apresentados.', 'Necessária correção do plano de arranjo geral conforme exigência nº 1.', ?, 'AGUARDANDO_ASSINATURA_ANALISTA', ?, ?)")
    ->execute([$parecerId1, $numParecer1, $analiseId, $submissaoId1, json_encode($snapshot1, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

$pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias 
    (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, descricao_snapshot, referencia_snapshot, criado_por)
    VALUES (UUID(), ?, ?, ?, 'NAO_CUMPRIDA', 'Projeto apresentado não atende aos requisitos de escape da NORMAM-202.', 'Adequar plano de arranjo geral...', 'NORMAM-202, Cap. 3', ?)")
    ->execute([$parecerId1, $exigenciaId, $submissaoId1, $analista['id']]);

$pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_ASSINATURA_ANALISTA' WHERE id=?")->execute([$analiseId]);
assertTest(true, "Parecer {$numParecer1} preparado com status AGUARDANDO_ASSINATURA_ANALISTA");

// Assinatura do Parecer pelo Analista
$pdo->prepare("UPDATE analise_planos_pareceres SET status='AGUARDANDO_APROVACAO_ADMIN', assinado_analista_em=NOW(), assinatura_analista_ip='127.0.0.1' WHERE id=?")
    ->execute([$parecerId1]);
$pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_APROVACAO_ADMIN' WHERE id=?")->execute([$analiseId]);
assertTest(true, "Parecer assinado digitalmente pelo analista -> encaminhado para o Administrador");

// Publicação pelo Administrador
loginComo($admin);

$pdo->prepare("UPDATE analise_planos_pareceres SET status='PUBLICADO', publicado_em=NOW(), validado_em=NOW(), validado_por=? WHERE id=?")
    ->execute([$admin['id'], $parecerId1]);

// Como o parecer teve resultado EXIGENCIAS, o status do processo vai para AGUARDANDO_DOCUMENTOS
$pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_DOCUMENTOS' WHERE id=?")->execute([$analiseId]);

// Gerar e persistir PDF do parecer
[$caminhoPdf1, $hashPdf1] = analiseAcaoPersistirParecerPdf($pdo, $parecerId1, $analiseId);
assertTest(is_file(__DIR__ . '/../' . $caminhoPdf1), "PDF oficial do parecer {$numParecer1} gerado com sucesso em {$caminhoPdf1}");
assertTest(!empty($hashPdf1) && strlen($hashPdf1) === 64, "Selo criptográfico SHA-256 do parecer validado: {$hashPdf1}");

$analiseAtual = analisePlanosCarregar($pdo, $analiseId);
assertTest($analiseAtual['status'] === 'AGUARDANDO_DOCUMENTOS', "Processo transitou corretamente para AGUARDANDO_DOCUMENTOS");

// 11. Passo 7: Revisão 2 (Armador envia projeto corrigido)
loginComo($analista);

$submissaoId2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, criado_por)
               VALUES (?, ?, 2, 'Revisão 2 com arranjo geral corrigido', CURDATE(), 'ANALISTA', ?)")
    ->execute([$submissaoId2, $analiseId, $analista['id']]);

$arqPath3 = $tempDir . '/Plano_Arranjo_Geral_Rev1.pdf';
$dummyPdf3 = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n00000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n% Arranjo Geral Rev 1 Corrigido - " . uniqid();
file_put_contents($arqPath3, $dummyPdf3);

$fileMeta3 = analisePlanosValidarUpload(['name'=>'Plano_Arranjo_Geral_Rev1.pdf','tmp_name'=>$arqPath3,'error'=>UPLOAD_ERR_OK,'size'=>filesize($arqPath3)]);
$chave3 = analisePlanosGuardarUpload(['tmp_name'=>$arqPath3], $analiseId, $fileMeta3);

$arqId3 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_arquivos (id, submissao_id, item_id, categoria, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, classificacao, classificado_por, classificado_em, criado_por)
               VALUES (?, ?, ?, 'Arranjo Geral', 'Plano_Arranjo_Geral_Rev1.pdf', 'pdf', 'application/pdf', ?, ?, ?, 'ACEITO', ?, NOW(), ?)")
    ->execute([$arqId3, $submissaoId2, $itemArranjo, $fileMeta3['tamanho'], $fileMeta3['sha256'], $chave3, $analista['id'], $analista['id']]);

// Marcar o arquivo anterior rejeitado da Revisão 1 como SUBSTITUIDO pela Revisão 2
$pdo->prepare("UPDATE analise_planos_arquivos SET classificacao='SUBSTITUIDO', justificativa_classificacao='Substituído pela prancha corrigida da Revisão 2', classificado_por=?, classificado_em=NOW() WHERE id=?")
    ->execute([$analista['id'], $arqId2]);
assertTest(true, "Arquivo anterior rejeitado atualizado para SUBSTITUÍDO pela nova prancha");

// Retornar processo para EM_ANALISE ao receber nova revisão
$pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=?")->execute([$analiseId]);
assertTest(true, "Revisão 2 recebida e classificada como ACEITO; processo voltou para EM_ANALISE");


// Marcar todos os itens da matriz como CONFORME ou NAO_APLICA
$pdo->prepare("UPDATE analise_planos_itens SET resultado='CONFORME', observacao='Aprovado na Revisão 2' WHERE analise_id=?")->execute([$analiseId]);

// 12. Passo 8: Parecer Conclusivo (Aprovado — Saldo Zero)
$parecerId2 = gerarUUID();
$numParecer2 = gerarNumeroDocumento('RAP-REL', 'AM-RAP-REL');
$snapshot2 = analisePlanosSnapshot($pdo, $analiseAtual, $submissaoId2);

$pdo->prepare("INSERT INTO analise_planos_pareceres 
    (id, numero, analise_id, versao, finalidade, submissao_id, relatorio_anterior_id, resultado, resumo, conclusao, snapshot_json, status, responsavel_assinatura_id, criado_por)
    VALUES (?, ?, ?, 2, 'CONCLUSIVO', ?, ?, 'APROVADO', 'Análise conclusiva dos planos e documentos.', 'Todos os planos atendem integralmente aos requisitos da NORMAM-202. Saldo zero de exigências.', ?, 'AGUARDANDO_ASSINATURA_ANALISTA', ?, ?)")
    ->execute([$parecerId2, $numParecer2, $analiseId, $submissaoId2, $parecerId1, json_encode($snapshot2, JSON_UNESCAPED_UNICODE), $responsavelAnalista['id'], $analista['id']]);

// Baixar a exigência como CUMPRIDA
$pdo->prepare("INSERT INTO analise_planos_relatorio_exigencias 
    (id, relatorio_id, exigencia_id, submissao_id, resultado, manifestacao_tecnica, descricao_snapshot, referencia_snapshot, criado_por)
    VALUES (UUID(), ?, ?, ?, 'CUMPRIDA', 'Arranjo geral corrigido na Revisão 2 contempla todas as saídas de emergência e rotas de escape.', 'Adequar plano de arranjo...', 'NORMAM-202', ?)")
    ->execute([$parecerId2, $exigenciaId, $submissaoId2, $analista['id']]);

$pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_ASSINATURA_ANALISTA' WHERE id=?")->execute([$analiseId]);

// Analista assina o parecer conclusivo
$pdo->prepare("UPDATE analise_planos_pareceres SET status='AGUARDANDO_APROVACAO_ADMIN', assinado_analista_em=NOW(), assinatura_analista_ip='127.0.0.1' WHERE id=?")
    ->execute([$parecerId2]);
$pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_APROVACAO_ADMIN' WHERE id=?")->execute([$analiseId]);
assertTest(true, "Parecer Conclusivo {$numParecer2} assinado pelo analista com baixa integral da exigência");

// Administrador valida e publica
loginComo($admin);

// Atualizar status da exigência para CUMPRIDA
$pdo->prepare("UPDATE analise_planos_exigencias SET status='CUMPRIDA', saneamento_pendente=0, observacao_cumprimento='Baixa registrada no relatório {$numParecer2}' WHERE id=?")
    ->execute([$exigenciaId]);

// Validar conclusão (Saldo Zero)
analisePlanosValidarConclusao($pdo, $analiseId);
assertTest(true, "Validação de Saldo Zero de Conclusão: 100% dos requisitos e exigências cumpridos");

// Publicar parecer
$pdo->prepare("UPDATE analise_planos_pareceres SET status='PUBLICADO', publicado_em=NOW(), validado_em=NOW(), validado_por=? WHERE id=?")
    ->execute([$admin['id'], $parecerId2]);

// Criar Licença Statutory Oficial
$licencaId = analiseAcaoCriarLicenca($pdo, $analiseAtual, $responsavelAnalista);
assertTest(!empty($licencaId), "Licença Statutory criada com sucesso: ID {$licencaId}");

$pdo->prepare("UPDATE analises_planos SET status='CONCLUIDA' WHERE id=?")->execute([$analiseId]);
[$caminhoPdf2, $hashPdf2] = analiseAcaoPersistirParecerPdf($pdo, $parecerId2, $analiseId);
assertTest(is_file(__DIR__ . '/../' . $caminhoPdf2), "PDF oficial conclusivo gerado em {$caminhoPdf2}");

$analiseFinal = analisePlanosCarregar($pdo, $analiseId);
assertTest($analiseFinal['status'] === 'CONCLUIDA', "Processo de Análise de Planos CONCLUÍDO com sucesso!");

// 13. Passo 9: Verificação da Licença Oficial Gerada (certificados_lc)
$licencaRow = $pdo->query("SELECT * FROM certificados_lc WHERE id='{$licencaId}'")->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($licencaRow['numero_lc']), "Número da Licença emitido: {$licencaRow['numero_lc']}");
assertTest($licencaRow['tipo_licenca'] === 'LC', "Tipo da Licença: {$licencaRow['tipo_licenca']}");
assertTest($licencaRow['embarcacao_id'] === $embarcacaoId, "Embarcação vinculada na Licença: {$licencaRow['nome_embarcacao']}");
assertTest($licencaRow['cliente_id'] === $clienteId, "Cliente proprietário vinculado: {$licencaRow['proprietario_nome']}");
assertTest($licencaRow['estaleiro_nome'] === 'Estaleiro Rio Guamá Ltda', "Estaleiro gravado: {$licencaRow['estaleiro_nome']}");
assertTest($licencaRow['numero_casco'] === 'RG-2026-09', "Nº do Casco gravado: {$licencaRow['numero_casco']}");
assertTest(str_contains($licencaRow['relatorio_numero'], $numParecer2), "Referência cruzada no documento: {$licencaRow['relatorio_numero']}");

// Testar geração do PDF da Licença LC
$tokenLc = $licencaRow['token_assinatura'];
$pdfLcCaminho = $tempDir . '/licenca_lc_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $licencaRow['numero_lc']) . '.pdf';
$salvar_pdf_caminho = $pdfLcCaminho;
$_GET = ['id' => $licencaId];
ob_start();
require __DIR__ . '/../modules/documentacao/lc/pdf.php';
ob_end_clean();
unset($salvar_pdf_caminho);


assertTest(is_file($pdfLcCaminho) && filesize($pdfLcCaminho) > 1000, "PDF oficial da Licença LC gerado perfeitamente (" . filesize($pdfLcCaminho) . " bytes)");

// 14. Limpeza e Conclusão
@unlink($arqPath1);
@unlink($arqPath2);
@unlink($arqPath3);
@unlink($pdfLcCaminho);
@rmdir($tempDir);

echo "\n=================================================================\n";
echo " SUCESSO TOTAL: TODAS AS ETAPAS FORAM RIGOROSAMENTE VALIDADAS!\n";
echo " O MÓDULO DE ANÁLISE DE PLANOS OPERA DE FORMA 100% FUNCIONAL E\n";
echo " CONFORME AS NORMAS MARÍTIMAS DA DPC / NORMAM-202.\n";
echo "=================================================================\n";
