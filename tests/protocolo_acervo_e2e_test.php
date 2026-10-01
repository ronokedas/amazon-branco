<?php
/**
 * Teste E2E do Módulo de Protocolos, Modelo de Ofício Naval, Assinatura Digital e Filtro de Assinados
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/protocolos.php';

function assercao(bool $condicao, string $mensagem): void {
    if (!$condicao) {
        echo "❌ FALHA: {$mensagem}\n";
        exit(1);
    }
    echo "✅ SUCESSO: {$mensagem}\n";
}

echo "=== INICIANDO TESTE E2E DO OFÍCIO NAVAL E ASSINATURA DIGITAL ===\n";

$embId = 'e2e-test-emb-' . bin2hex(random_bytes(4));
$cliId = 'e2e-test-cli-' . bin2hex(random_bytes(4));

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO clientes(id, nome, cpf_cnpj, ativo) VALUES(:id, 'Cliente Armador Teste', '12345678901234', 1)")
        ->execute([':id' => $cliId]);

    $pdo->prepare("INSERT INTO embarcacoes(id, nome, registro, cliente_id, ativo) VALUES(:id, 'POSTO ICCAR 40', 'REG-PROTO-999', :cli, 1)")
        ->execute([':id' => $embId, ':cli' => $cliId]);

    $userId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();

    // 1. Inserir Proposta NÃO assinada (Rascunho) - NÃO DEVE SER PUXADA
    $propRascunhoId = 'e2e-prop-rasc-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO propostas(id, numero, cliente_id, data_emissao, valor_total, status, assinado, escritorio_id, criado_por) VALUES(:id, 'PROP-RASC-001', :cli, CURDATE(), 4000.00, 'rascunho', 0, '00000000-0000-4000-8000-000000000100', :u)")
        ->execute([':id' => $propRascunhoId, ':cli' => $cliId, ':u' => $userId]);
    $pdo->prepare("INSERT INTO propostas_embarcacoes(id, proposta_id, embarcacao_id) VALUES(UUID(), :pid, :eid)")
        ->execute([':pid' => $propRascunhoId, ':eid' => $embId]);

    // 2. Inserir Proposta Assinada Digitalmente - DEVE SER PUXADA
    $propAssinadaId = 'e2e-prop-ass-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO propostas(id, numero, cliente_id, data_emissao, valor_total, status, assinado, assinatura_em, assinante_nome, escritorio_id, criado_por) VALUES(:id, 'PROP-ASS-002', :cli, CURDATE(), 7500.00, 'assinada', 1, NOW(), 'Armador Teste', '00000000-0000-4000-8000-000000000100', :u)")
        ->execute([':id' => $propAssinadaId, ':cli' => $cliId, ':u' => $userId]);
    $pdo->prepare("INSERT INTO propostas_embarcacoes(id, proposta_id, embarcacao_id) VALUES(UUID(), :pid, :eid)")
        ->execute([':pid' => $propAssinadaId, ':eid' => $embId]);

    // 3. Inserir Vistoria Rascunho / Sem Assinatura - NÃO DEVE SER PUXADA
    $vistNaoAssinadaId = 'e2e-vist-nao-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO vistorias(id, numero, embarcacao_id, finalidade, data_vistoria, status, mobile_versao, criado_por) VALUES(:id, 'AM-VIS-PEND/26', :eid, 'VISTORIA', CURDATE(), 'PENDENTE', 1, :u)")
        ->execute([':id' => $vistNaoAssinadaId, ':eid' => $embId, ':u' => $userId]);

    // 4. Inserir Vistorias Assinadas Digitalmente - DEVEM SER PUXADAS
    $vist1Id = 'e2e-vist-1-' . bin2hex(random_bytes(4));
    $vist2Id = 'e2e-vist-2-' . bin2hex(random_bytes(4));

    $pdo->prepare("INSERT INTO vistorias(id, numero, embarcacao_id, finalidade, data_vistoria, status, mobile_versao, assinatura_status, assinatura_em, criado_por) VALUES(:id, '51/26', :eid, 'VISTORIA', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'APROVADA_COM_EXIGENCIAS', 1, 'ASSINADO', NOW(), :u)")
        ->execute([':id' => $vist1Id, ':eid' => $embId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO vistorias(id, numero, embarcacao_id, relatorio_anterior_id, finalidade, data_vistoria, status, mobile_versao, assinatura_status, assinatura_em, criado_por) VALUES(:id, '52/26', :eid, :ant, 'CUMPRIMENTO_EXIGENCIAS', CURDATE(), 'APROVADA', 2, 'ASSINADO', NOW(), :u)")
        ->execute([':id' => $vist2Id, ':eid' => $embId, ':ant' => $vist1Id, ':u' => $userId]);

    // 5. Inserir Parecer Técnico Publicado e Assinado pelo Analista
    $analiseId = 'e2e-analise-' . bin2hex(random_bytes(4));
    $submissaoId = 'e2e-subm-' . bin2hex(random_bytes(4));
    $arqId = 'e2e-arq-' . bin2hex(random_bytes(4));
    $parId = 'e2e-par-' . bin2hex(random_bytes(4));

    $pdo->prepare("INSERT INTO analises_planos(id, numero, embarcacao_id, tipo_processo, enquadramento, objeto, status, criado_por) VALUES(:id, '56/26', :eid, 'LC', 'NORMAM-202', 'Licença de Construção', 'CONCLUIDA', :u)")
        ->execute([':id' => $analiseId, ':eid' => $embId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO analise_planos_submissoes(id, analise_id, revisao, recebido_em, origem, criado_por) VALUES(:id, :aid, 1, CURDATE(), 'ANALISTA', :u)")
        ->execute([':id' => $submissaoId, ':aid' => $analiseId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO analise_planos_arquivos(id, submissao_id, categoria, classificacao, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por) VALUES(:id, :sid, 'ARRANJO_GERAL', 'ACEITO', 'Plano_Arranjo_Geral_REV01.pdf', 'pdf', 'application/pdf', 245000, 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'chave-test', :u)")
        ->execute([':id' => $arqId, ':sid' => $submissaoId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO analise_planos_pareceres(id, numero, analise_id, versao, finalidade, resultado, resumo, conclusao, status, assinado_analista_em, criado_por) VALUES(:id, '56/26', :aid, 1, 'CONCLUSIVO', 'APROVADO', 'Resumo parecer', 'Conclusao favoravel', 'PUBLICADO', NOW(), :u)")
        ->execute([':id' => $parId, ':aid' => $analiseId, ':u' => $userId]);

    // 6. Inserir Certificados Navais Assinados
    $certCsnId = 'e2e-csn-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO certificados_csn(id, numero, embarcacao_id, nome_embarcacao, token_assinatura, data_emissao, data_validade, status, assinado, ativo, criado_por) VALUES(:id, '107/26', :eid, 'POSTO ICCAR 40', 'token-csn-test-1234', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 'emitido', 1, 1, :u)")
        ->execute([':id' => $certCsnId, ':eid' => $embId, ':u' => $userId]);

    $certCnarqId = 'e2e-cnarq-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO certificados_cnarq(id, numero, embarcacao_id, nome_embarcacao, token_assinatura, data_emissao, data_validade, status, assinado, ativo, criado_por) VALUES(:id, '108/26', :eid, 'POSTO ICCAR 40', 'token-cnarq-test-1234', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 'emitido', 1, 1, :u)")
        ->execute([':id' => $certCnarqId, ':eid' => $embId, ':u' => $userId]);

    $certNarId = 'e2e-nar-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO certificados_nar(id, numero, ano, sequencial, embarcacao_id, nome_embarcacao, token_assinatura, data_emissao, status, assinado, ativo, criado_por) VALUES(:id, '51/26', 2026, 51, :eid, 'POSTO ICCAR 40', 'token-nar-test-1234', CURDATE(), 'emitido', 1, 1, :u)")
        ->execute([':id' => $certNarId, ':eid' => $embId, ':u' => $userId]);

    // 7. Testar Filtro Estrito: Apenas Documentos Assinados
    $acervo = protocoloObterAcervoEmbarcacao($pdo, $embId);

    assercao($acervo['resumo']['propostas'] === 1, "Apenas 1 proposta (a assinada) foi puxada; rascunho ignorado.");
    assercao($acervo['resumo']['vistorias'] === 2, "Apenas as 2 vistorias assinadas foram puxadas; vistoria pendente ignorada.");
    assercao($acervo['resumo']['projetos'] === 2, "Prancha aceita e parecer publicado foram puxados.");
    assercao($acervo['resumo']['certificados'] === 3, "3 Certificados oficiais assinados (CSN, CNARQ, NAR) foram puxados.");

    // 8. Criar Dossiê de Teste e Movimentação de Saída com seleção estrita
    $dossieId = 'e2e-dossie-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_dossies(id, numero, numero_oficio, destinatario_autoridade, normam_referencia, embarcacao_id, cliente_id, assunto, status, criado_por) 
                   VALUES(:id, 'AM-PROT-E2E-TEST', 'AM-OF020/2026', 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL', 'NORMAM 202/DPC', :eid, :cid, 'Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM', 'EM_PREPARACAO', :u)")
        ->execute([':id' => $dossieId, ':eid' => $embId, ':cid' => $cliId, ':u' => $userId]);

    $movId = 'e2e-mov-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_movimentacoes(id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome, destinatario_autoridade, numero_oficio, cidade, uf, meio_envio, movimentado_em, status, criado_por)
                   VALUES(:id, :did, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'CAPITANIA DOS PORTOS DA AMAZÔNIA ORIENTAL', 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL', 'AM-OF020/2026', 'Belém', 'PA', 'PRESENCIAL', NOW(), 'CONFIRMADA', :u)")
        ->execute([':id' => $movId, ':did' => $dossieId, ':u' => $userId]);

    // Inserir os 5 itens exatamente como na foto do usuário:
    // AM-REL-V: 51/26; AM-CSN:107/26; AM-CNARQ: 108/26; AM-REL:AP 56/26; AM-NARQ: 51/26
    $itensMov = [
        ['desc' => 'Relatório de Vistoria Naval', 'rev' => '51/26', 'cat' => 'VISTORIA'],
        ['desc' => 'Certificado de Segurança da Navegação', 'rev' => '107/26', 'cat' => 'CERTIFICADOS'],
        ['desc' => 'Certificado Nacional de Arqueação', 'rev' => '108/26', 'cat' => 'CERTIFICADOS'],
        ['desc' => 'Parecer Técnico de Análise de Planos', 'rev' => '56/26', 'cat' => 'ANALISE_PLANOS'],
        ['desc' => 'Nota de Arqueação', 'rev' => '51/26', 'cat' => 'CERTIFICADOS'],
    ];

    $insIt = $pdo->prepare("INSERT INTO protocolo_movimentacao_itens(id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, numero_revisao) VALUES(UUID(), :mid, :d, :c, 'DIGITAL', 'NATO_DIGITAL', 1, :r)");
    foreach ($itensMov as $it) {
        $insIt->execute([':mid' => $movId, ':d' => $it['desc'], ':c' => $it['cat'], ':r' => $it['rev']]);
    }

    // 9. Simular Assinatura Digital do Ofício
    $pdo->prepare("UPDATE protocolo_dossies SET assinado = 1, assinatura_em = NOW(), assinante_nome = 'THAINARA BARROS', assinante_cargo = 'Secretária', assinatura_ip = '192.168.1.100' WHERE id = :id")
        ->execute([':id' => $dossieId]);

    $pdo->prepare("UPDATE protocolo_movimentacoes SET assinado = 1, assinatura_em = NOW(), assinante_nome = 'THAINARA BARROS', assinante_cargo = 'Secretária', assinatura_ip = '192.168.1.100' WHERE id = :id")
        ->execute([':id' => $movId]);

    // 10. Gerar PDF do Ofício e verificar integridade
    $salvar_pdf_dossie_caminho = sys_get_temp_dir() . '/oficio_dossie_e2e.pdf';
    $dossie_pdf_id = $dossieId;
    require __DIR__ . '/../modules/protocolos/pdf_dossie.php';

    assercao(is_file($salvar_pdf_dossie_caminho), "Arquivo PDF do Ofício gerado com sucesso.");
    $tamanhoPdf = filesize($salvar_pdf_dossie_caminho);
    assercao($tamanhoPdf > 3000, "PDF do Ofício possui tamanho válido ({$tamanhoPdf} bytes).");
    @unlink($salvar_pdf_dossie_caminho);

    // 11. Gerar PDF da Movimentação de Saída e verificar integridade
    $salvar_pdf_caminho = sys_get_temp_dir() . '/oficio_saida_e2e.pdf';
    $movimentacao_pdf_id = $movId;
    require __DIR__ . '/../modules/protocolos/pdf.php';

    assercao(is_file($salvar_pdf_caminho), "Arquivo PDF do Ofício de Saída gerado com sucesso.");
    $tamanhoSaida = filesize($salvar_pdf_caminho);
    assercao($tamanhoSaida > 3000, "PDF da Saída possui tamanho válido ({$tamanhoSaida} bytes).");
    @unlink($salvar_pdf_caminho);

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";

} catch (Throwable $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} finally {
    $pdo->rollBack();
    echo "Rollback executado com sucesso. Banco de dados limpo.\n";
}
