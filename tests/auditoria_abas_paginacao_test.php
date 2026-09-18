<?php
/**
 * Teste Automatizado: Auditoria de Preservação de Abas e Paginação em Todo o ERP
 * Arquivo: tests/auditoria_abas_paginacao_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== INICIANDO TESTE: AUDITORIA DE PRESERVAÇÃO DE ABAS E PAGINAÇÃO ===\n\n";

$passou = true;

function testAssert(bool $condicao, string $mensagem): void {
    global $passou;
    if ($condicao) {
        echo "  [✓] {$mensagem}\n";
    } else {
        echo "  [✗] FALHA: {$mensagem}\n";
        $passou = false;
    }
}

// -------------------------------------------------------------
// 1. AUDITORIA DE PRESERVAÇÃO DE ABAS EM ANALISES_PLANOS
// -------------------------------------------------------------
echo "1. Validando preservação de abas em Analises de Planos...\n";

$formAnalises = file_get_contents(__DIR__ . '/../modules/analises_planos/form.php');
$actionsAnalises = file_get_contents(__DIR__ . '/../modules/analises_planos/actions.php');

testAssert(strpos($formAnalises, 'input-aba-ativa') !== false, "Formulário de Análises de Planos contém inputs '.input-aba-ativa'");
testAssert(strpos($formAnalises, 'trocarAbaAnalise') !== false, "Função JS trocarAbaAnalise implementada para sincronizar abas");
testAssert(strpos($formAnalises, 'sessionStorage.setItem') !== false, "Persistência em sessionStorage implementada para abas de análises");
testAssert(strpos($formAnalises, 'window.history.replaceState') !== false, "URL sincronizada via replaceState na troca de abas de análises");
testAssert(strpos($actionsAnalises, '$aba =') !== false && strpos($actionsAnalises, '&aba=') !== false, "actions.php de análises preserva e propaga o parâmetro 'aba' em todos os retornos");

// -------------------------------------------------------------
// 2. AUDITORIA DE PRESERVAÇÃO DE ABAS EM PROTOCOLOS (DOSSIÊS)
// -------------------------------------------------------------
echo "\n2. Validando preservação de abas em Protocolos (Dossiês)...\n";

$formProtocolos = file_get_contents(__DIR__ . '/../modules/protocolos/form.php');
$actionsProtocolos = file_get_contents(__DIR__ . '/../modules/protocolos/actions.php');

testAssert(strpos($formProtocolos, 'input-aba-ativa') !== false, "Formulário de Protocolos contém inputs '.input-aba-ativa'");
testAssert(strpos($formProtocolos, 'trocarAbaDossie') !== false, "Função JS trocarAbaDossie sincroniza abas ativas");
testAssert(strpos($formProtocolos, 'sessionStorage.setItem') !== false, "sessionStorage armazena aba de dossiês");
testAssert(strpos($actionsProtocolos, '$aba =') !== false && strpos($actionsProtocolos, '&aba=') !== false, "actions.php de protocolos preserva e propaga o parâmetro 'aba'");

$partesProtocolos = [
    'dossie_identificacao.php',
    'tramite_oficial.php',
    'custodia_originais.php',
    'auditoria_aceite.php',
    'movimentacoes_historico.php'
];
foreach ($partesProtocolos as $parte) {
    $conteudoParte = file_get_contents(__DIR__ . '/../modules/protocolos/components/' . $parte);
    testAssert(strpos($conteudoParte, 'input-aba-ativa') !== false || strpos($conteudoParte, 'name="aba"') !== false, "Componente {$parte} inclui input oculto de aba");
}

// -------------------------------------------------------------
// 3. AUDITORIA DE PRESERVAÇÃO DE ABAS EM EMBARCAÇÕES & CNBL
// -------------------------------------------------------------
echo "\n3. Validando preservação de abas em Embarcações e CNBL...\n";

$formEmbarcacao = file_get_contents(__DIR__ . '/../modules/embarcacoes/form.php');
$actionsEmbarcacao = file_get_contents(__DIR__ . '/../modules/embarcacoes/actions.php');
testAssert(strpos($formEmbarcacao, 'name="aba"') !== false, "Formulário de Embarcações contém campo hidden 'aba'");
testAssert(strpos($formEmbarcacao, 'openTab') !== false && strpos($formEmbarcacao, 'sessionStorage') !== false, "openTab sincroniza URL e sessionStorage em embarcações");
testAssert(strpos($actionsEmbarcacao, 'aba') !== false, "actions.php de embarcações preserva parâmetro aba em erros de validação");

$formCnbl = file_get_contents(__DIR__ . '/../modules/documentacao/cnbl/form.php');
$actionsCnbl = file_get_contents(__DIR__ . '/../modules/documentacao/cnbl/actions.php');
testAssert(strpos($formCnbl, 'name="aba"') !== false, "Formulário de CNBL contém campo hidden 'aba'");
testAssert(strpos($actionsCnbl, '&aba=tab-convalidacoes') !== false, "actions.php de CNBL redireciona preservando aba de convalidações");

// -------------------------------------------------------------
// 4. AUDITORIA DE PAGINAÇÃO IMPLEMENTADA NOS MÓDULOS
// -------------------------------------------------------------
echo "\n4. Validando paginação completa nos módulos...\n";

$modulosPaginacao = [
    'Vistorias' => [
        'arquivo' => __DIR__ . '/../modules/vistorias/index.php',
        'classe_nav' => 'paginacao-vistorias',
        'url_helper' => 'vistoriaUrl',
    ],
    'Embarcações' => [
        'arquivo' => __DIR__ . '/../modules/embarcacoes/index.php',
        'classe_nav' => 'paginacao-embarcacoes',
        'url_helper' => 'embarcacaoUrl',
    ],
    'Propostas Comerciais' => [
        'arquivo' => __DIR__ . '/../modules/comercial/propostas/index.php',
        'classe_nav' => 'paginacao-propostas',
        'url_helper' => 'propostaUrl',
    ],
    'Certificados CSN' => [
        'arquivo' => __DIR__ . '/../modules/documentacao/certificados/index.php',
        'classe_nav' => 'paginacao-certificados',
        'url_helper' => 'csnUrl',
    ],
    'Certificados CNBL' => [
        'arquivo' => __DIR__ . '/../modules/documentacao/cnbl/index.php',
        'classe_nav' => 'paginacao-certificados',
        'url_helper' => 'cnblUrl',
    ],
    'Certificados CNARQ' => [
        'arquivo' => __DIR__ . '/../modules/documentacao/cnarq/index.php',
        'classe_nav' => 'paginacao-certificados',
        'url_helper' => 'cnarqUrl',
    ],
    'Certificados CHT' => [
        'arquivo' => __DIR__ . '/../modules/documentacao/cht/index.php',
        'classe_nav' => 'paginacao-certificados',
        'url_helper' => 'chtUrl',
    ],
    'Agendamentos' => [
        'arquivo' => __DIR__ . '/../modules/agendamentos/index.php',
        'classe_nav' => 'paginacao-agendamentos',
        'url_helper' => 'agendamentoUrl',
    ],
    'Usuários & Funcionários' => [
        'arquivo' => __DIR__ . '/../modules/usuarios/index.php',
        'classe_nav' => 'paginacao-usuarios',
        'url_helper' => 'usuarioUrl',
    ],
];

foreach ($modulosPaginacao as $nome => $dados) {
    $conteudo = file_get_contents($dados['arquivo']);
    testAssert(strpos($conteudo, '$paginaAtual') !== false, "{$nome}: Define variável \$paginaAtual");
    testAssert(strpos($conteudo, '$porPagina') !== false, "{$nome}: Define variável \$porPagina com limites");
    testAssert(strpos($conteudo, $dados['url_helper']) !== false, "{$nome}: Possui closure auxiliar {$dados['url_helper']} para URLs");
    testAssert(strpos($conteudo, 'LIMIT :limite OFFSET :offset') !== false, "{$nome}: Utiliza LIMIT/OFFSET com PDO bind seguro");
    testAssert(strpos($conteudo, $dados['classe_nav']) !== false, "{$nome}: Renderiza lista de paginação '.{$dados['classe_nav']}'");
    testAssert(strpos($conteudo, 'selectPorPagina') !== false, "{$nome}: Possui seletor dinâmico de itens por página");
}

// -------------------------------------------------------------
// RESULTADO FINAL
// -------------------------------------------------------------
echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: TODOS OS TESTES DE AUDITORIA DE ABAS E PAGINAÇÃO PASSARAM COM 100% DE SUCESSO!\n";
    echo "============================================================\n";
    exit(0);
} else {
    echo "RESULTADO: ALGUNS TESTES FALHARAM. VERIFIQUE AS MENSAGENS ACIMA.\n";
    echo "============================================================\n";
    exit(1);
}
