<?php
/**
 * Teste Automatizado: Cargo SECRETARIA, Permissões Totais e Ofício em Página Única
 * Local: tests/secretaria_e_oficio_pagina_unica_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== INICIANDO TESTE DO CARGO SECRETARIA E OFÍCIO EM PÁGINA ÚNICA ===\n";

$pdo->beginTransaction();

try {
    // 1. Criar Usuário com Cargo SECRETARIA
    $secId = 'sec-test-' . bin2hex(random_bytes(6));
    $stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, :nome, :email, :senha, 'SECRETARIA', 1)");
    $stmtUser->execute([
        ':id' => $secId,
        ':nome' => 'Thainara Barros Secretária Teste',
        ':email' => 'thainara.teste@amazonnaval.com.br',
        ':senha' => password_hash('teste123', PASSWORD_DEFAULT)
    ]);
    
    // Aplicar permissões padrão do cargo
    aplicarPermissoesPadraoUsuario($pdo, $secId, 'SECRETARIA');

    // 2. Simular Sessão da Secretária
    $_SESSION['usuario_id'] = $secId;
    $_SESSION['usuario_nome'] = 'Thainara Barros Secretária Teste';
    $_SESSION['usuario_cargo'] = 'SECRETARIA';
    $_SESSION['usuario_logado'] = true;

    // Verificar permissões
    $modulosParaTestar = ['dashboard', 'vistorias', 'analise_planos', 'protocolos_documentais', 'certificados', 'clientes', 'embarcacoes', 'configuracoes', 'usuarios'];
    foreach ($modulosParaTestar as $mod) {
        if (!podeAcessar($mod)) {
            throw new Exception("Falha: Cargo SECRETARIA não teve acesso liberado ao módulo '{$mod}'.");
        }
    }
    echo "✅ SUCESSO: Cargo SECRETARIA possui acesso total liberado a todos os módulos por padrão.\n";

    // 3. Cadastrar/Configurar Assinatura da Secretária em responsaveis_assinatura
    $stmtResp = $pdo->prepare("INSERT INTO responsaveis_assinatura (nome_completo, cargo_titulo, registro_profissional, cpf_cnpj, email, usuario_id, ativo) VALUES (:nome, 'Secretária', NULL, '123.456.789-00', 'thainara.teste@amazonnaval.com.br', :uid, 1)");
    $stmtResp->execute([
        ':nome' => 'Thainara Barros Secretária Teste',
        ':uid' => $secId
    ]);
    $respId = (int)$pdo->lastInsertId();

    if ($respId <= 0) {
        throw new Exception("Falha ao registrar responsável de assinatura para Secretária.");
    }
    echo "✅ SUCESSO: Secretária vinculada e cadastrada com sucesso como responsável de assinatura.\n";

    // 4. Criar Dossiê e Movimentação para Teste de PDF
    $embId = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetchColumn();
    if (!$embId) {
        $embId = 'emb-sec-test';
        $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, ativo) VALUES (:id, 'EMBARCAÇÃO TESTE OFÍCIO', 'BALSA', 1)")->execute([':id' => $embId]);
    }

    $dossieId = 'dos-sec-' . bin2hex(random_bytes(4));
    $stmtDos = $pdo->prepare("INSERT INTO protocolo_dossies (
        id, numero, numero_oficio, destinatario_autoridade, normam_referencia,
        embarcacao_id, assunto, status, criado_por, assinado, assinatura_em,
        responsavel_assinatura_id, assinante_nome, assinante_cargo
    ) VALUES (
        :id, 'DOS-2026/099', 'AM-OF020/2026', 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL',
        'NORMAM 202/DPC', :emb, 'Encaminhamento de documentos emitidos', 'EM_PREPARACAO', :uid,
        1, NOW(), :resp_id, 'THAINARA BARROS', 'Secretária'
    )");
    $stmtDos->execute([
        ':id' => $dossieId,
        ':emb' => $embId,
        ':uid' => $secId,
        ':resp_id' => $respId
    ]);

    // Criar Movimentação de Saída
    $movId = 'mov-sec-' . bin2hex(random_bytes(4));
    $stmtMov = $pdo->prepare("INSERT INTO protocolo_movimentacoes (
        id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome,
        destinatario_autoridade, numero_oficio, cidade, uf, meio_envio, movimentado_em, status, criado_por,
        assinado, assinatura_em, responsavel_assinatura_id, assinante_nome, assinante_cargo
    ) VALUES (
        :id, :dos, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'CAPITANIA DOS PORTOS DA AMAZÔNIA ORIENTAL',
        'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL', 'AM-OF020/2026', 'Belém', 'PA', 'PRESENCIAL', NOW(), 'CONFIRMADA', :uid,
        1, NOW(), :resp_id, 'THAINARA BARROS', 'Secretária'
    )");
    $stmtMov->execute([
        ':id' => $movId,
        ':dos' => $dossieId,
        ':uid' => $secId,
        ':resp_id' => $respId
    ]);

    // Inserir item enviado
    $stmtItem = $pdo->prepare("INSERT INTO protocolo_movimentacao_itens (
        id, movimentacao_id, descricao, categoria, suporte, forma, quantidade
    ) VALUES (
        :id, :mov, 'Relatório de Vistoria de Arqueação (AM-REL-V: 51/26)', 'RELATORIO_TECNICO', 'DIGITAL', 'COPIA_SIMPLES', 1
    )");
    $stmtItem->execute([
        ':id' => 'item-sec-1',
        ':mov' => $movId
    ]);

    // 5. Testar Geração do PDF do Ofício do Dossiê e Validar que tem EXATAMENTE 1 PÁGINA
    $tmpPdfDossie = tempnam(sys_get_temp_dir(), 'pdf_dos_') . '.pdf';
    $salvar_pdf_dossie_caminho = $tmpPdfDossie;
    $dossie_pdf_id = $dossieId;

    require __DIR__ . '/../modules/protocolos/pdf_dossie.php';

    if (!is_file($tmpPdfDossie) || filesize($tmpPdfDossie) < 1000) {
        throw new Exception("Falha ao gerar o arquivo PDF do Ofício do Dossiê.");
    }

    // Contar páginas no PDF gerado
    $pdfContent = file_get_contents($tmpPdfDossie);
    $numPaginasDossie = preg_match_all("/\/Page\W/", $pdfContent, $matches);
    if ($numPaginasDossie !== 1) {
        throw new Exception("ERRO: O PDF do Ofício gerou {$numPaginasDossie} páginas, mas deve ter rigorosamente 1 PÁGINA!");
    }
    echo "✅ SUCESSO: Ofício do Dossiê gerado com EXATAMENTE 1 PÁGINA (tamanho: " . filesize($tmpPdfDossie) . " bytes).\n";
    @unlink($tmpPdfDossie);

    // 6. Testar Geração do PDF do Ofício de Saída e Validar que tem EXATAMENTE 1 PÁGINA
    $tmpPdfSaida = tempnam(sys_get_temp_dir(), 'pdf_sai_') . '.pdf';
    $salvar_pdf_caminho = $tmpPdfSaida;
    $movimentacao_pdf_id = $movId;

    require __DIR__ . '/../modules/protocolos/pdf.php';

    if (!is_file($tmpPdfSaida) || filesize($tmpPdfSaida) < 1000) {
        throw new Exception("Falha ao gerar o arquivo PDF do Ofício de Saída.");
    }

    $pdfContentSaida = file_get_contents($tmpPdfSaida);
    $numPaginasSaida = preg_match_all("/\/Page\W/", $pdfContentSaida, $matchesSaida);
    if ($numPaginasSaida !== 1) {
        throw new Exception("ERRO: O PDF do Ofício de Saída gerou {$numPaginasSaida} páginas, mas deve ter rigorosamente 1 PÁGINA!");
    }
    echo "✅ SUCESSO: Ofício de Saída gerado com EXATAMENTE 1 PÁGINA (tamanho: " . filesize($tmpPdfSaida) . " bytes).\n";
    @unlink($tmpPdfSaida);

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";
} catch (Exception $e) {
    echo "❌ FALHA NO TESTE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $pdo->rollBack();
    exit(1);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rollback executado com sucesso. Banco de dados preservado limpo.\n";
    }
}
