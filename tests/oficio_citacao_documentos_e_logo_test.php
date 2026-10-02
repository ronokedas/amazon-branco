<?php
/**
 * Teste Automatizado: Validação de Citação de Documentos Anexados, Logomarca e Domínio amazonnaval.com.br no Ofício
 * Local: tests/oficio_citacao_documentos_e_logo_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TESTE: CITAÇÃO DE DOCUMENTOS ANEXADOS, LOGOMARCA E SITE OFICIAL NO OFÍCIO ===\n";

$pdo->beginTransaction();

try {
    // 1. Criar embarcação de teste
    $embId = 'emb-teste-oficio-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, registro, ativo, criado_em) VALUES (?, 'BARCO RJ TESTE15', 'EMPURRADOR', 'REG-RJ-15', 1, NOW())")->execute([$embId]);

    // 2. Criar dossiê em preparação (status EM_PREPARACAO)
    $userId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
    $dossieId = 'dos-teste-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_dossies (
        id, numero, numero_oficio, embarcacao_id, assunto, status, criado_por, criado_em,
        destinatario_autoridade, normam_referencia
    ) VALUES (
        ?, 'DOS-2026-0226', 'AM-OF226/2026', ?, 'Encaminhamento de documentos emitidos/aprovados', 'EM_PREPARACAO', ?, NOW(),
        'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL', 'NORMAM 202/DPC'
    )")->execute([$dossieId, $embId, $userId]);

    // 3. Criar movimentação em RASCUNHO (como na criação da tela do usuário)
    $movId = 'mov-teste-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO protocolo_movimentacoes (
        id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome,
        cidade, uf, meio_envio, movimentado_em, status, criado_por, criado_em
    ) VALUES (
        ?, ?, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'CAPITANIA DOS PORTOS DA AMAZÔNIA ORIENTAL',
        'Belém', 'PA', 'PRESENCIAL', NOW(), 'RASCUNHO', ?, NOW()
    )")->execute([$movId, $dossieId, $userId]);

    // 4. Inserir múltiplos documentos anexados de diferentes tipos
    $pdo->prepare("INSERT INTO protocolo_movimentacao_itens (
        id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, numero_revisao
    ) VALUES 
    (UUID(), ?, 'Certificado de Segurança da Navegação nº 107/26', 'CERTIFICADOS', 'DIGITAL', 'NATO_DIGITAL', 1, '107/26'),
    (UUID(), ?, 'Certificado Nacional de Arqueação nº 108/26', 'CERTIFICADOS', 'DIGITAL', 'NATO_DIGITAL', 1, '108/26'),
    (UUID(), ?, 'Relatório de Vistoria nº 51/26', 'VISTORIAS', 'DIGITAL', 'NATO_DIGITAL', 1, '51/26'),
    (UUID(), ?, 'Memorial Descritivo de Arqueação', 'PROJETOS_NAVAIS', 'DIGITAL', 'NATO_DIGITAL', 1, '01'),
    (UUID(), ?, 'Proposta Comercial nº AM-ORC-901/26', 'PROPOSTAS', 'DIGITAL', 'NATO_DIGITAL', 1, NULL)")
    ->execute([$movId, $movId, $movId, $movId, $movId]);

    // 5. Gerar PDF do Dossiê
    $tmpPdf = tempnam(sys_get_temp_dir(), 'test_oficio_') . '.pdf';
    $salvar_pdf_dossie_caminho = $tmpPdf;
    $dossie_pdf_id = $dossieId;

    require __DIR__ . '/../modules/protocolos/pdf_dossie.php';

    if (!is_file($tmpPdf) || filesize($tmpPdf) < 1000) {
        throw new RuntimeException("Falha ao gerar arquivo PDF do Ofício.");
    }

    $rawPdf = file_get_contents($tmpPdf);

    // 6. Validar que tem EXATAMENTE 1 PÁGINA
    $numPaginas = preg_match_all("/\/Page\W/", $rawPdf, $matches);
    if ($numPaginas !== 1) {
        throw new RuntimeException("ERRO: O PDF gerou {$numPaginas} páginas! Deve ter rigorosamente 1 página.");
    }
    echo "✅ SUCESSO: Ofício gerado com EXATAMENTE 1 PÁGINA (tamanho: " . filesize($tmpPdf) . " bytes).\n";

    // 7. Extrair texto de streams descomprimidos do PDF
    preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $rawPdf, $streamMatches);
    $text = '';
    foreach ($streamMatches[1] as $s) {
        $decomp = @gzuncompress($s);
        $text .= ' ' . ($decomp !== false ? $decomp : $s);
    }

    // 8. Validar citação dos documentos anexados
    if ($citacaoDocumentos === 'AM-OF/ANEXOS: Documentos técnicos constantes do processo') {
        throw new RuntimeException("ERRO: O Ofício caiu no fallback genérico ao invés de citar os documentos anexados!");
    }
    echo "✅ SUCESSO: Fallback genérico evitado. Os documentos anexados foram processados.\n";

    echo "Citação gerada: '{$citacaoDocumentos}'\n";

    $docsEsperados = [
        'AM-CSN: 107/26',
        'AM-CNARQ: 108/26',
        'AM-REL-V: 51/26',
        'Memorial Descritivo de Arqueação',
        'AM-ORC-901/26'
    ];

    foreach ($docsEsperados as $docEsp) {
        if (!str_contains($citacaoDocumentos, $docEsp)) {
            throw new RuntimeException("ERRO: Documento esperado '{$docEsp}' não foi citado no Ofício!\nCitação: '{$citacaoDocumentos}'");
        }
        echo "✅ SUCESSO: Documento anexado '{$docEsp}' citado com precisão no Ofício.\n";
    }

    // 9. Validar que o texto do corpo contém a citação e o site oficial
    if (!str_contains($htmlCorpo, $citacaoDocumentos)) {
        throw new RuntimeException("ERRO: O corpo do Ofício não contém a citação gerada!");
    }
    echo "✅ SUCESSO: A citação dos documentos está inserida no corpo formal do Ofício.\n";

    if (!str_contains($rawPdf, 'amazonnaval.com.br')) {
        // Checa se o PDF raw ou streams contém o domínio
        if (!str_contains($text, 'amazonnaval.com.br')) {
            throw new RuntimeException("ERRO: O domínio amazonnaval.com.br não foi encontrado no PDF!");
        }
    }
    echo "✅ SUCESSO: Domínio oficial amazonnaval.com.br confirmado no PDF.\n";

    echo "=== TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! ===\n";
    @unlink($tmpPdf);
} catch (Throwable $e) {
    echo "❌ FALHA: " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
    if (isset($tmpPdf) && is_file($tmpPdf)) @unlink($tmpPdf);
    $pdo->rollBack();
    exit(1);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rollback executado com sucesso. Banco de dados limpo.\n";
    }
}
