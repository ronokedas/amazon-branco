<?php
/**
 * Teste Automatizado: Observações Oficiais e Pré-Preenchimento nas Licenças Navais (LC, LA, LR, LCEC)
 * NORMAM-202 Anexo 3-A
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/emissao_certificados.php';

echo "=== INICIANDO TESTE DE OBSERVAÇÕES NAS LICENÇAS NAVAIS ===\n";

$falhas = 0;
$sucessos = 0;

function assertCond($cond, $nome) {
    global $falhas, $sucessos;
    if ($cond) {
        echo " [OK] {$nome}\n";
        $sucessos++;
    } else {
        echo " [FAIL] {$nome}\n";
        $falhas++;
    }
}

// ----------------------------------------------------
// 1. Testar gerador de observações padrão para LA
// ----------------------------------------------------
$dadosAnalista = [
    'ano' => 2022,
    'relatorio_numero' => 'RC-RAP05223/2026',
    'comprimento_total' => 32.56,
    'comprimento_casco' => 26.95,
    'numero_passageiros' => 173,
    'numero_tripulantes' => 6,
    'porte_bruto' => 30,
    'passageiros_turismo' => 262,
    'passageiros_regular' => 173,
    'passageiros_misto' => 108,
];

$obsLA = gerarObservacoesPadraoLicenca($dadosAnalista, 'LA');

assertCond(strpos($obsLA, '1 - Data de Batimento de Quilha: 2022.') !== false, 'Item 1 Batimento de Quilha 2022 presente no LA');
assertCond(strpos($obsLA, '2 - Data de Alteração: ' . date('Y') . '.') !== false, 'Item 2 Data de Alteração presente no LA');
assertCond(strpos($obsLA, '3 - Esta Licença foi emitida com base no Relatório de Análise de Planos n.º RC-RAP05223/2026.') !== false, 'Item 3 RAP presente no LA');
assertCond(strpos($obsLA, '4 - Esta licença contempla as alterações') !== false, 'Item 4 contemplação de alterações presente no LA');
assertCond(strpos($obsLA, 'Alteração do layout da área dos passageiros do convés principal') !== false, 'Item 4 sub-item layout presente no LA');
assertCond(strpos($obsLA, 'Adequação das dimensões da casaria') !== false, 'Item 4 sub-item casaria presente no LA');
assertCond(strpos($obsLA, 'Alteração Arqueação Bruta (AB) e Arqueação Líquida (AL)') !== false, 'Item 4 sub-item AB/AL presente no LA');
assertCond(strpos($obsLA, '5 - A embarcação possui o comprimento total com rampas de 32,560 m e comprimento do casco de 26,950 m.') !== false, 'Item 5 comprimentos total e casco presentes no LA');
assertCond(strpos($obsLA, '6 - Lotação Máxima a Bordo:') !== false, 'Item 6 Lotação máxima presente');
assertCond(strpos($obsLA, 'turismo: está destinada a operar com 262 passageiros e 06 tripulantes') !== false, 'Item 6 turismo presente com 262 passageiros e 06 tripulantes');
assertCond(strpos($obsLA, 'passageiros: está destinada a operar com 173 passageiros e 06 tripulantes') !== false, 'Item 6 passageiros presente com 173 passageiros e 06 tripulantes');
assertCond(strpos($obsLA, 'passageiros e carga no convés: está destinada a operar com 108 passageiros, 06 tripulantes, e com 30 t. de carga') !== false, 'Item 6 passageiros e carga no convés presente');
assertCond(strpos($obsLA, '7 - Tempo de Singradura: inferior a 12 h.') !== false, 'Item 7 Tempo de Singradura presente');
assertCond(strpos($obsLA, '8 - A embarcação não poderá transportar carga nos porões.') !== false, 'Item 8 Restrição de carga nos porões presente');

// ----------------------------------------------------
// 2. Testar gerador para os demais modelos (LC, LR, LCEC)
// ----------------------------------------------------
$obsLC = gerarObservacoesPadraoLicenca($dadosAnalista, 'LC');
assertCond(strpos($obsLC, 'Previsão de Conclusão da Construção') !== false, 'Modelo LC contém Previsão de Conclusão');
assertCond(strpos($obsLC, 'Esta licença contempla a construção da embarcação') !== false, 'Modelo LC contempla construção');

$obsLR = gerarObservacoesPadraoLicenca($dadosAnalista, 'LR');
assertCond(strpos($obsLR, 'Data de Reclassificação') !== false, 'Modelo LR contém Data de Reclassificação');
assertCond(strpos($obsLR, 'Esta licença contempla a reclassificação da embarcação') !== false, 'Modelo LR contempla reclassificação');

$obsLCEC = gerarObservacoesPadraoLicenca(array_merge($dadosAnalista, ['data_termino_construcao' => '2024-05-10']), 'LCEC');
assertCond(strpos($obsLCEC, 'Data de Conclusão da Construção (LCEC): 2024.') !== false, 'Modelo LCEC contém Data de Conclusão baseada no término');
assertCond(strpos($obsLCEC, 'Esta licença contempla a regularização de embarcação já construída (LCEC)') !== false, 'Modelo LCEC contempla regularização');

// ----------------------------------------------------
// 3. Teste de banco de dados e persistência
// ----------------------------------------------------
$usuarioAdmin = $pdo->query("SELECT id FROM usuarios WHERE ativo = 1 ORDER BY id LIMIT 1")->fetchColumn();
$embTesteId = gerarUUID();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, ano, comprimento_total, comprimento_casco, boca_moldada, pontal_moldado, numero_tripulantes, numero_passageiros_n1, porte_bruto, ativo) 
    VALUES (:id, 'EMBARCACAO TESTE OBS', 'Balsa', 2022, 32.56, 26.95, 6.80, 2.40, 6, 173, 30.0, 1)")
    ->execute([':id' => $embTesteId]);

$clienteTesteId = $pdo->query("SELECT id FROM clientes WHERE status = 'ATIVO' LIMIT 1")->fetchColumn();

// Teste de inserção com observações customizadas
$licTesteId = gerarUUID();
$obsCustom = "1 - Data de Batimento de Quilha: 2020.\n2 - Data de Alteração: 2026.\n3 - Relatório nº RC-001/26.\n4 - Alterações aprovadas.\n5 - Comp: 32,560 m.\n6 - Lotação: 173 pass.\n7 - Singradura: 8h.\n8 - Sem carga nos porões.";

$pdo->prepare("INSERT INTO certificados_lc (id, numero_lc, token_assinatura, tipo_licenca, nome_embarcacao, embarcacao_id, cliente_id, data_emissao, observacoes, status, ativo)
    VALUES (:id, 'AM-LA-TESTE/26', :token, 'LA', 'EMBARCACAO TESTE OBS', :emb_id, :cli_id, CURDATE(), :obs, 'emitido', 1)")
    ->execute([
        ':id' => $licTesteId,
        ':token' => bin2hex(random_bytes(32)),
        ':emb_id' => $embTesteId,
        ':cli_id' => $clienteTesteId,
        ':obs' => $obsCustom,
    ]);

$salvo = $pdo->query("SELECT observacoes FROM certificados_lc WHERE id = '{$licTesteId}'")->fetchColumn();
assertCond($salvo === $obsCustom, 'Observações personalizadas salvas e recuperadas com sucesso na coluna observacoes');

// ----------------------------------------------------
// 4. Teste de geração do PDF com as observações
// ----------------------------------------------------
$pdfDestino = sys_get_temp_dir() . '/licenca_teste_obs_' . time() . '.pdf';
$_GET['id'] = $licTesteId;
$salvar_pdf_caminho = $pdfDestino;

ob_start();
require __DIR__ . '/../modules/documentacao/lc/pdf.php';
$output = ob_get_clean();

assertCond(file_exists($pdfDestino), 'Arquivo PDF da Licença gerado com sucesso');
assertCond(filesize($pdfDestino) > 1000, 'Tamanho do PDF válido (> 1KB): ' . filesize($pdfDestino) . ' bytes');

// Limpeza dos dados de teste
$pdo->exec("DELETE FROM certificados_lc WHERE id = '{$licTesteId}'");
$pdo->exec("DELETE FROM embarcacoes WHERE id = '{$embTesteId}'");
@unlink($pdfDestino);

echo "\n=== RESUMO: {$sucessos} SUCESSOS, {$falhas} FALHAS ===\n";
if ($falhas > 0) {
    exit(1);
}
echo "TESTES DE OBSERVAÇÕES DE LICENÇAS FINALIZADOS COM ÊXITO TOTAL!\n";
