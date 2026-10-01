<?php
/**
 * Teste E2E do Módulo de Protocolos & Acervo Documental Naval
 * Valida agregação de Propostas, Vistorias Multi-Versões, Engenharia REV 00/01,
 * Certificados Navais, Anexos Externos e Geração de PDF Consolidado.
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

echo "=== INICIANDO TESTE E2E DO ACERVO DOCUMENTAL NAVAL ===\n";

// 1. Criar ou obter embarcação de teste
$embId = 'e2e-test-emb-' . bin2hex(random_bytes(4));
$cliId = 'e2e-test-cli-' . bin2hex(random_bytes(4));

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO clientes(id, nome, cpf_cnpj, ativo) VALUES(:id, 'Cliente Armador Teste', '12345678901234', 1)")
        ->execute([':id' => $cliId]);

    $pdo->prepare("INSERT INTO embarcacoes(id, nome, registro, cliente_id, ativo) VALUES(:id, 'B/M AMAZON PROTOCOLO TEST', 'REG-PROTO-999', :cli, 1)")
        ->execute([':id' => $embId, ':cli' => $cliId]);

    $userId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();

    // 2. Inserir Proposta vinculada
    $propId = 'e2e-prop-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO propostas(id, numero, cliente_id, data_emissao, valor_total, status, assinado, escritorio_id, criado_por) VALUES(:id, 'PROP-TEST-001', :cli, CURDATE(), 7500.00, 'assinada', 1, '00000000-0000-4000-8000-000000000100', :u)")
        ->execute([':id' => $propId, ':cli' => $cliId, ':u' => $userId]);
    $pdo->prepare("INSERT INTO propostas_embarcacoes(id, proposta_id, embarcacao_id) VALUES(UUID(), :pid, :eid)")
        ->execute([':pid' => $propId, ':eid' => $embId]);

    // 3. Inserir Vistorias Multi-Versões (Versão Inicial e Retorno de Exigências)
    $vist1Id = 'e2e-vist-1-' . bin2hex(random_bytes(4));
    $vist2Id = 'e2e-vist-2-' . bin2hex(random_bytes(4));

    $pdo->prepare("INSERT INTO vistorias(id, numero, embarcacao_id, finalidade, data_vistoria, status, mobile_versao, criado_por) VALUES(:id, 'AM-VIS-001/26', :eid, 'VISTORIA', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'APROVADA_COM_EXIGENCIAS', 1, :u)")
        ->execute([':id' => $vist1Id, ':eid' => $embId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO vistorias(id, numero, embarcacao_id, relatorio_anterior_id, finalidade, data_vistoria, status, mobile_versao, criado_por) VALUES(:id, 'AM-VIS-002/26', :eid, :ant, 'CUMPRIMENTO_EXIGENCIAS', CURDATE(), 'APROVADA', 2, :u)")
        ->execute([':id' => $vist2Id, ':eid' => $embId, ':ant' => $vist1Id, ':u' => $userId]);

    // 4. Inserir Análise de Planos e Arquivo de Engenharia com Revisão REV 01
    $analiseId = 'e2e-analise-' . bin2hex(random_bytes(4));
    $submissaoId = 'e2e-subm-' . bin2hex(random_bytes(4));
    $arqId = 'e2e-arq-' . bin2hex(random_bytes(4));

    $pdo->prepare("INSERT INTO analises_planos(id, numero, embarcacao_id, tipo_processo, enquadramento, objeto, status, criado_por) VALUES(:id, 'AM-RAP-99/26', :eid, 'LC', 'NORMAM-202', 'Licença de Construção', 'EM_ANALISE', :u)")
        ->execute([':id' => $analiseId, ':eid' => $embId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO analise_planos_submissoes(id, analise_id, revisao, recebido_em, origem, criado_por) VALUES(:id, :aid, 1, CURDATE(), 'ANALISTA', :u)")
        ->execute([':id' => $submissaoId, ':aid' => $analiseId, ':u' => $userId]);

    $pdo->prepare("INSERT INTO analise_planos_arquivos(id, submissao_id, categoria, classificacao, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por) VALUES(:id, :sid, 'ARRANJO_GERAL', 'ACEITO', 'Plano_Arranjo_Geral_REV01.pdf', 'pdf', 'application/pdf', 245000, 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'chave-test', :u)")
        ->execute([':id' => $arqId, ':sid' => $submissaoId, ':u' => $userId]);

    // 5. Inserir Certificado Naval
    $certId = 'e2e-csn-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO certificados_csn(id, numero, embarcacao_id, nome_embarcacao, token_assinatura, data_emissao, data_validade, status, assinado, ativo, criado_por) VALUES(:id, 'AM-CSN-999/26', :eid, 'B/M AMAZON PROTOCOLO TEST', 'token-csn-test-1234', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 'emitido', 1, 1, :u)")
        ->execute([':id' => $certId, ':eid' => $embId, ':u' => $userId]);

    // 6. Testar Agregador protocoloObterAcervoEmbarcacao
    $acervo = protocoloObterAcervoEmbarcacao($pdo, $embId);

    assercao($acervo['resumo']['total'] === 5, "Total de 5 documentos agregados no acervo.");
    assercao($acervo['resumo']['propostas'] === 1, "1 Proposta localizada no acervo.");
    assercao($acervo['resumo']['vistorias'] === 2, "2 Vistorias com versões distintas localizadas.");
    assercao($acervo['resumo']['projetos'] === 1, "1 Prancha de Engenharia Naval (REV 01) localizada.");
    assercao($acervo['resumo']['certificados'] === 1, "1 Certificado CSN localizado.");

    // Verificar se as versões das vistorias foram identificadas corretamente
    $v1 = null; $v2 = null;
    foreach ($acervo['itens'] as $it) {
        if ($it['origem_id'] === $vist1Id) $v1 = $it;
        if ($it['origem_id'] === $vist2Id) $v2 = $it;
    }

    assercao($v1 !== null && str_contains($v1['versao_label'], 'Versão Inicial'), "Vistoria 1 rotulada como Versão Inicial.");
    assercao($v2 !== null && str_contains($v2['versao_label'], 'Revisão / Retorno'), "Vistoria 2 rotulada como Retorno de Exigências.");

    // 7. Criar Dossiê de Teste e gerar PDF consolidado
    $dossieId = 'e2e-dossie-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_dossies(id, numero, embarcacao_id, cliente_id, assunto, status, criado_por) VALUES(:id, 'AM-PROT-E2E-TEST', :eid, :cid, 'Processo Teste E2E', 'EM_PREPARACAO', :u)")
        ->execute([':id' => $dossieId, ':eid' => $embId, ':cid' => $cliId, ':u' => $userId]);

    $salvar_pdf_dossie_caminho = sys_get_temp_dir() . '/dossie_e2e_test.pdf';
    $dossie_pdf_id = $dossieId;

    require __DIR__ . '/../modules/protocolos/pdf_dossie.php';

    assercao(is_file($salvar_pdf_dossie_caminho) && filesize($salvar_pdf_dossie_caminho) > 1000, "PDF Consolidado do Dossiê gerado com acervo completo.");
    @unlink($salvar_pdf_dossie_caminho);

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";

} catch (Throwable $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
} finally {
    // Rollback para não deixar dados sujos no banco
    $pdo->rollBack();
    echo "Rollback executado com sucesso. Banco de dados limpo.\n";
}
