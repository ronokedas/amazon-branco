<?php
/**
 * Teste unitário e de integração para Customização e Persistência dos Modelos de Observação
 * de Licenças Navais (LC, LA, LR, LCEC) pelo Analista Naval.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== INICIANDO TESTE DE MODELOS PERSONALIZADOS DE OBSERVAÇÃO DO ANALISTA ===\n";

$sucessos = 0;
$erros = 0;

function assertCustom(bool $condicao, string $msg) {
    global $sucessos, $erros;
    if ($condicao) {
        echo " [OK] " . $msg . "\n";
        $sucessos++;
    } else {
        echo " [FALHA] " . $msg . "\n";
        $erros++;
    }
}

// 1. Obter usuário analista de teste
try {
    $stmtU = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY id LIMIT 1");
    $analista = $stmtU->fetch(PDO::FETCH_ASSOC);
    if (!$analista) {
        die("Nenhum usuário ativo encontrado para teste.\n");
    }
} catch (Throwable $e) {
    die("Erro ao buscar usuário: " . $e->getMessage() . "\n");
}

$analistaId = $analista['id'];
echo "Analista de Teste: {$analista['nome']} (ID: {$analistaId})\n\n";

// Limpar templates anteriores de teste deste analista
$pdo->prepare("DELETE FROM modelos_observacoes_licenca WHERE usuario_id = :uid")->execute([':uid' => $analistaId]);

// 2. Verificar obtenção dos modelos de fábrica (NORMAM-202)
$padroes = modelosObservacoesLicencaPadrao();
assertCustom(isset($padroes['LC']) && isset($padroes['LA']) && isset($padroes['LR']) && isset($padroes['LCEC']), 'Todos os 4 modelos oficiais da NORMAM-202 existem por padrão');

$tplInicial = obterTemplateObservacoesLicenca('LC', $analistaId);
assertCustom($tplInicial['origem'] === 'normam_padrao', 'Sem personalização, a origem reportada é normam_padrao');

// 3. Simular salvamento de modelo personalizado pelo analista para LC
$customTextoLC = "1 - Data de Batimento de Quilha: {ano_quilha}.\n"
    . "2 - Previsão de Conclusão da Construção: {ano_evento}.\n"
    . "3 - Esta Licença foi emitida com base no Relatório de Análise de Planos n.º {numero_rap}.\n"
    . "4 - Esta licença contempla a construção naval personalizada com normas rigorosas do Analista.\n"
    . "5 - A embarcação possui o comprimento total com rampas de {comprimento_total} m e comprimento do casco de {comprimento_casco} m.\n"
    . "6 - Lotação Máxima a Bordo:\n"
    . "► Para atividade e/ou serviço de turismo: está destinada a operar com {passageiros_turismo} passageiros e {tripulantes} tripulantes;\n"
    . "► Para atividade e/ou serviço de passageiros: está destinada a operar com {passageiros_regular} passageiros e {tripulantes} tripulantes; e\n"
    . "► Para atividade e/ou serviço de passageiros e carga no convés: está destinada a operar com {passageiros_misto} passageiros, {tripulantes} tripulantes, e com {porte_bruto} t. de carga sobre o convés principal.\n"
    . "7 - Singradura: {tempo_singradura}.\n"
    . "8 - {restricao_carga}\n"
    . "9 - CLÁUSULA ADICIONAL DO ANALISTA: Inspeção periódica dos tanques estruturais a cada 6 meses.";

$novoId = gerarUUID();
$stmtIn = $pdo->prepare("INSERT INTO modelos_observacoes_licenca 
    (id, usuario_id, tipo_licenca, titulo, conteudo_template, ativo, criado_em, atualizado_em) 
    VALUES (:id, :uid, 'LC', 'Modelo LC Analista Teste', :conteudo, 1, NOW(), NOW())");
$stmtIn->execute([
    ':id' => $novoId,
    ':uid' => $analistaId,
    ':conteudo' => $customTextoLC
]);

// 4. Testar recuperação do template do analista
$tplAnalista = obterTemplateObservacoesLicenca('LC', $analistaId);
assertCustom($tplAnalista['origem'] === 'analista', 'Após salvar, obterTemplateObservacoesLicenca reporta origem = analista');
assertCustom(strpos($tplAnalista['conteudo'], 'CLÁUSULA ADICIONAL DO ANALISTA') !== false, 'Conteúdo customizado preserva cláusula adicional do analista');

// 5. Testar compilação dinâmica com dados de Embarcação A
$dadosVasoA = [
    'tipo_licenca' => 'LC',
    'ano_construcao' => '2023',
    'data_validade' => '2027-12-31',
    'relatorio_numero' => 'RC-RAP2024/001',
    'comprimento_total' => '35.800',
    'comprimento_casco' => '29.500',
    'numero_tripulantes' => 8,
    'numero_passageiros' => 200,
    'porte_bruto' => 45.0,
];

$compiladoA = compilarObservacoesLicenca($tplAnalista['conteudo'], $dadosVasoA, 'LC');
assertCustom(strpos($compiladoA, 'Batimento de Quilha: 2023') !== false, 'Embarcação A: batimento 2023 compilado no modelo customizado');
assertCustom(strpos($compiladoA, 'Previsão de Conclusão da Construção: 2027') !== false, 'Embarcação A: ano 2027 compilado no modelo customizado');
assertCustom(strpos($compiladoA, 'RC-RAP2024/001') !== false, 'Embarcação A: RAP compilado no modelo customizado');
assertCustom(strpos($compiladoA, '35,800 m') !== false, 'Embarcação A: comprimento total 35,800 m compilado no modelo customizado');
assertCustom(strpos($compiladoA, '29,500 m') !== false, 'Embarcação A: comprimento casco 29,500 m compilado no modelo customizado');
assertCustom(strpos($compiladoA, '08 tripulantes') !== false, 'Embarcação A: 08 tripulantes compilado no modelo customizado');
assertCustom(strpos($compiladoA, '45 t. de carga') !== false, 'Embarcação A: 45 t. de carga compilado no modelo customizado');
assertCustom(strpos($compiladoA, 'CLÁUSULA ADICIONAL DO ANALISTA') !== false, 'Embarcação A: cláusula do analista presente no texto final');

// 6. Testar compilação dinâmica com dados de Embarcação B (diferentes medidas e lotação)
$dadosVasoB = [
    'tipo_licenca' => 'LC',
    'ano_construcao' => '2025',
    'data_validade' => '2029-06-30',
    'relatorio_numero' => 'AM-RAP-999/26',
    'comprimento_total' => '18.250',
    'comprimento_casco' => '16.100',
    'numero_tripulantes' => 4,
    'numero_passageiros' => 60,
    'porte_bruto' => 15.0,
];

$compiladoB = compilarObservacoesLicenca($tplAnalista['conteudo'], $dadosVasoB, 'LC');
assertCustom(strpos($compiladoB, 'Batimento de Quilha: 2025') !== false, 'Embarcação B: batimento 2025 compilado');
assertCustom(strpos($compiladoB, '18,250 m') !== false, 'Embarcação B: comprimento total 18,250 m compilado');
assertCustom(strpos($compiladoB, '16,100 m') !== false, 'Embarcação B: comprimento casco 16,100 m compilado');
assertCustom(strpos($compiladoB, '04 tripulantes') !== false, 'Embarcação B: 04 tripulantes compilado');
assertCustom(strpos($compiladoB, 'AM-RAP-999/26') !== false, 'Embarcação B: RAP AM-RAP-999/26 compilado');
assertCustom(strpos($compiladoB, 'CLÁUSULA ADICIONAL DO ANALISTA') !== false, 'Embarcação B: cláusula do analista mantida');

// 7. Testar retorno via obterTodosTemplatesObservacoesLicenca
$todos = obterTodosTemplatesObservacoesLicenca($analistaId);
assertCustom($todos['LC']['origem'] === 'analista', 'obterTodosTemplates: LC é de origem analista');
assertCustom($todos['LA']['origem'] === 'normam_padrao', 'obterTodosTemplates: LA permanece padrão NORMAM');
assertCustom($todos['LR']['origem'] === 'normam_padrao', 'obterTodosTemplates: LR permanece padrão NORMAM');
assertCustom($todos['LCEC']['origem'] === 'normam_padrao', 'obterTodosTemplates: LCEC permanece padrão NORMAM');

// 8. Testar restauração / exclusão da customização do analista
$pdo->prepare("DELETE FROM modelos_observacoes_licenca WHERE usuario_id = :uid AND tipo_licenca = 'LC'")->execute([':uid' => $analistaId]);
$tplRestaurado = obterTemplateObservacoesLicenca('LC', $analistaId);
assertCustom($tplRestaurado['origem'] === 'normam_padrao', 'Após restauração/exclusão, volta ao normam_padrao');
assertCustom(strpos($tplRestaurado['conteudo'], 'CLÁUSULA ADICIONAL DO ANALISTA') === false, 'Cláusula personalizada não aparece mais no modelo restaurado');

echo "\n=== RESUMO: {$sucessos} SUCESSOS, {$erros} FALHAS ===\n";
if ($erros === 0) {
    echo "TESTES DE MODELOS PERSONALIZADOS CONCLUÍDOS COM 100% DE SUCESSO!\n";
    exit(0);
} else {
    echo "TESTE FALHOU COM {$erros} ERROS!\n";
    exit(1);
}
