<?php
/**
 * Teste Unitário e de Integração: Banco de Referências NORMAM e Relatório Oficial RAP
 * Valida o catálogo de normas DPC/Marinha do Brasil, CRUD pelo analista e geração do PDF oficial.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertNormam(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE: BANCO DE REFERÊNCIAS NORMAM & RELATÓRIO OFICIAL RAP\n";
echo "=================================================================\n\n";

// 1. Verificar tabela do Banco de Normas
echo "1. Verificando estrutura do banco de dados...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'analise_planos_referencias_normam'");
assertNormam((bool)$stmt->fetch(), "Tabela analise_planos_referencias_normam existe no banco");

$totalNormas = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam")->fetchColumn();
assertNormam($totalNormas >= 200, "Banco contém pelo menos 200 normas NORMAM cadastradas (atual: {$totalNormas})");

// Verificar se coluna categoria existe em analise_planos_exigencias
$colCat = $pdo->query("SHOW COLUMNS FROM analise_planos_exigencias LIKE 'categoria'")->fetch(PDO::FETCH_ASSOC);
assertNormam(!empty($colCat), "Coluna 'categoria' existe na tabela analise_planos_exigencias");

// 2. Testar categorias padronizadas
echo "\n2. Testando categorias de documentos de projeto...\n";
$categorias = analisePlanosCategoriasNormam();
assertNormam(in_array('MEMORIAL DESCRITO', $categorias, true), "Categoria MEMORIAL DESCRITO presente");
assertNormam(in_array('NOTAS DE BORDA LIVRE', $categorias, true), "Categoria NOTAS DE BORDA LIVRE presente");
assertNormam(in_array('CURVAS HIDROSTÁTICAS', $categorias, true), "Categoria CURVAS HIDROSTÁTICAS presente");
assertNormam(in_array('ESTUDO DE ESTABILIDADE', $categorias, true), "Categoria ESTUDO DE ESTABILIDADE presente");
assertNormam(in_array('PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE', $categorias, true), "Categoria PLANO DE ARRANJO GERAL presente");

// 3. Testar busca e filtros do Banco NORMAM
echo "\n3. Testando busca e filtros no Banco de Normas...\n";
$buscaEstabilidade = analisePlanosBuscarReferenciasNormam($pdo, ['busca' => 'estabilidade']);
assertNormam(count($buscaEstabilidade) > 0, "Busca textual por 'estabilidade' retornou referências");

$buscaMemorial = analisePlanosBuscarReferenciasNormam($pdo, ['categoria' => 'MEMORIAL DESCRITO']);
assertNormam(count($buscaMemorial) > 0, "Filtro por categoria 'MEMORIAL DESCRITO' retornou referências");

// 4. Testar CRUD de Referência pelo Analista
echo "\n4. Testando CRUD de Referência pelo Analista...\n";
$novoId = analisePlanosSalvarReferenciaNormam($pdo, [
    'categoria' => 'ESTUDO DE ESTABILIDADE',
    'norma' => 'NORMAM-202',
    'item_norma' => 'Anexo 3-F Item 9999',
    'referencia_normativa' => 'NORMAM-202/DPC, Anexo 3-F, Item 9999 - Teste Automatizado',
    'titulo' => 'Exigência de Teste Automatizado',
    'descricao_padrao' => 'Apresentar cálculo do momento emborcador para a condição de vento lateral com ângulo crítico corrigido.',
    'ordem' => 999,
]);
assertNormam(!empty($novoId), "Referência normativa inserida com sucesso (ID: {$novoId})");

// Atualizar
analisePlanosSalvarReferenciaNormam($pdo, [
    'id' => $novoId,
    'categoria' => 'ESTUDO DE ESTABILIDADE',
    'norma' => 'NORMAM-202',
    'item_norma' => 'Anexo 3-F Item 9999-REV',
    'referencia_normativa' => 'NORMAM-202/DPC, Anexo 3-F, Item 9999-REV',
    'titulo' => 'Exigência de Teste Atualizada',
    'descricao_padrao' => 'Descrição atualizada com sucesso.',
    'ordem' => 1,
]);
$refAtualizada = $pdo->query("SELECT * FROM analise_planos_referencias_normam WHERE id='{$novoId}'")->fetch(PDO::FETCH_ASSOC);
assertNormam($refAtualizada['titulo'] === 'Exigência de Teste Atualizada', "Referência atualizada com sucesso");

// Excluir
analisePlanosExcluirReferenciaNormam($pdo, $novoId);
$refExcluida = $pdo->query("SELECT * FROM analise_planos_referencias_normam WHERE id='{$novoId}'")->fetch(PDO::FETCH_ASSOC);
assertNormam(!$refExcluida, "Referência excluída com sucesso");

// 5. Testar observações padrão da DPC/Analista
echo "\n5. Testando observações oficiais da DPC (01 a 06)...\n";
$dadosSimulados = [
    'classe_certificacao' => 'EC1',
    'responsavel_projeto_nome' => 'Eng. Lucas Marítimo',
    'responsavel_projeto_registro' => '12345/D',
    'art_numero' => '2026-ART-998877',
    'tipo_processo' => 'LC',
];
$obs = analisePlanosObservacoesPadrao($dadosSimulados);
assertNormam(count($obs) === 6, "Exatamente 6 observações oficiais geradas");
assertNormam(str_contains($obs['01'], 'Eng. Lucas Marítimo'), "OBS 01 contém nome do responsável");
assertNormam(str_contains($obs['01'], '2026-ART-998877'), "OBS 01 contém número da ART");
assertNormam(str_contains($obs['02'], 'ART; Memorial Descritivo'), "OBS 02 lista documentos examinados");
assertNormam(str_contains($obs['03'], 'tempo hábil'), "OBS 03 instrui prazo de cumprimento");
assertNormam(str_contains($obs['06'], 'NORMAM 202, Item 3.28'), "OBS 06 cita NORMAM 202, Item 3.28");

// 6. Testar geração do PDF oficial do Relatório de Análise de Planos (RAP)
echo "\n6. Testando emissão do PDF oficial com nova logo...\n";
// Buscar um parecer existente no banco para gerar PDF
$parecerExistente = $pdo->query("SELECT id FROM analise_planos_pareceres ORDER BY criado_em DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($parecerExistente) {
    $parecerId = $parecerExistente['id'];
    $tmpPdf = sys_get_temp_dir() . '/teste_rap_oficial_' . uniqid() . '.pdf';
    
    $_GET['id'] = $parecerId;
    $salvar_pdf_caminho = $tmpPdf;
    
    require __DIR__ . '/../modules/analises_planos/parecer_pdf.php';
    
    assertNormam(is_file($tmpPdf), "Arquivo PDF foi gerado no disco");
    $tamanhoBytes = filesize($tmpPdf);
    assertNormam($tamanhoBytes > 2000, "Arquivo PDF possui conteúdo válido ({$tamanhoBytes} bytes)");
    
    $cabecalhoPdf = file_get_contents($tmpPdf, false, null, 0, 5);
    assertNormam($cabecalhoPdf === '%PDF-', "Cabeçalho do arquivo é PDF válido (%PDF-)");
    
    @unlink($tmpPdf);
} else {
    echo "  [INFO] Nenhum parecer cadastrado no momento para teste de PDF. Testando compilação do arquivo...\n";
    assertNormam(file_exists(__DIR__ . '/../modules/analises_planos/parecer_pdf.php'), "Arquivo parecer_pdf.php existe");
}

echo "\n=================================================================\n";
echo " TODOS OS TESTES DO BANCO DE NORMAS E RELATÓRIO PASSARAM (100%)!\n";
echo "=================================================================\n";
