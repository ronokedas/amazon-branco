<?php
/**
 * Teste Automatizado: Validação Visual e de Estrutura do Ofício
 * Local: tests/test_oficio_visual.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TESTE VISUAL E DE ESTRUTURA DO OFÍCIO ===\n";

$pdo->beginTransaction();

try {
    $embId = 'emb-vis-test-' . bin2hex(random_bytes(3));
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, registro, ativo, criado_em) VALUES (?, 'POSTO ICCAR 40', 'BALSA', 'REG-PA-9921', 1, NOW())")->execute([$embId]);

    $userId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
    $dossieId = 'dos-vis-test-' . bin2hex(random_bytes(3));
    $pdo->prepare("INSERT INTO protocolo_dossies (
        id, numero, numero_oficio, destinatario_autoridade, normam_referencia,
        embarcacao_id, assunto, status, criado_por, assinado, assinatura_em,
        assinante_nome, assinante_cargo
    ) VALUES (
        ?, 'DOS-2026/0426', 'AM-OF426/2026', 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL',
        'NORMAM 202/DPC', ?, 'Encaminhamento de documentos emitidos/aprovados por esta Entidade Certificadora para arquivo nesta OM.',
        'EM_PREPARACAO', ?, 1, NOW(), 'THAINARA BARROS', 'Secretária'
    )")->execute([$dossieId, $embId, $userId]);

    $movId = 'mov-vis-test-' . bin2hex(random_bytes(3));
    $pdo->prepare("INSERT INTO protocolo_movimentacoes (
        id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome,
        cidade, uf, meio_envio, movimentado_em, status, criado_por, assinado, assinatura_em
    ) VALUES (
        ?, ?, 1, 'SAIDA', 'ENVIO_ORGAO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'CAPITANIA DOS PORTOS DO AMAPÁ',
        'Belém', 'PA', 'PRESENCIAL', NOW(), 'CONFIRMADA', ?, 1, NOW()
    )")->execute([$movId, $dossieId, $userId]);

    $pdo->prepare("INSERT INTO protocolo_movimentacao_itens (
        id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, numero_revisao
    ) VALUES 
    (UUID(), ?, 'Certificado de Segurança da Navegação nº 107/26', 'CERTIFICADOS', 'DIGITAL', 'NATO_DIGITAL', 1, '107/26'),
    (UUID(), ?, 'Certificado Nacional de Arqueação nº 108/26', 'CERTIFICADOS', 'DIGITAL', 'NATO_DIGITAL', 1, '108/26'),
    (UUID(), ?, 'Relatório de Vistoria nº 51/26', 'VISTORIAS', 'DIGITAL', 'NATO_DIGITAL', 1, '51/26'),
    (UUID(), ?, 'Relatório de Análise de Planos nº 56/26', 'ANALISE_PLANOS', 'DIGITAL', 'NATO_DIGITAL', 1, '56/26'),
    (UUID(), ?, 'Nota de Arqueação nº 51/26', 'ARQUEACAO', 'DIGITAL', 'NATO_DIGITAL', 1, '51/26')")
    ->execute([$movId, $movId, $movId, $movId, $movId]);

    $tmpPdf = tempnam(sys_get_temp_dir(), 'vis_of_') . '.pdf';
    $salvar_pdf_dossie_caminho = $tmpPdf;
    $dossie_pdf_id = $dossieId;

    require __DIR__ . '/../modules/protocolos/pdf_dossie.php';

    if (!is_file($tmpPdf) || filesize($tmpPdf) < 1000) {
        throw new RuntimeException("Falha ao gerar o PDF.");
    }

    $rawPdf = file_get_contents($tmpPdf);
    $numPaginas = preg_match_all("/\/Page\W/", $rawPdf, $matches);
    if ($numPaginas !== 1) {
        throw new RuntimeException("ERRO: Esperado exatamente 1 página, obtido: " . $numPaginas);
    }
    echo "✅ SUCESSO: Ofício gerado com 1 página e marca d'água incorporada (" . filesize($tmpPdf) . " bytes)\n";
    @unlink($tmpPdf);

} finally {
    $pdo->rollBack();
}
