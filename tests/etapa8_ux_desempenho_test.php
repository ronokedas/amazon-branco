<?php
/**
 * Teste Automatizado - Etapa 8: Limpeza de Menu, Notificações, Desempenho/Cache e Usabilidade Autodidática (NORMAM)
 * Arquivo: tests/etapa8_ux_desempenho_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/dashboard/data.php';

function assertEtapa8(bool $condicao, string $mensagem): void {
    if (!$condicao) {
        echo "FAIL: {$mensagem}\n";
        exit(1);
    }
}

echo "=== INICIANDO TESTES DA ETAPA 8 (MENU, NOTIFICACOES, CACHE/DESEMPENHO, UX AUTODIDATICA) ===\n\n";

// -------------------------------------------------------------
// 1. VERIFICACAO DO SIDEBAR E CENTRAL DE RELATORIOS
// -------------------------------------------------------------
echo "1. Validando Sidebar e Central de Relatórios...\n";
$sidebarCode = file_get_contents(__DIR__ . '/../includes/sidebar.php');
assertEtapa8(str_contains($sidebarCode, 'Aprovação Vistorias'), "Sidebar deve rotular a fila técnica como 'Aprovação Vistorias'.");
assertEtapa8(!str_contains($sidebarCode, 'data-label="Relatórios"'), "Sidebar não deve ter 'Relatórios' genérico apontando para aprovação de relatórios.");

$relatoriosCode = file_get_contents(__DIR__ . '/../modules/relatorios/index.php');
assertEtapa8(str_contains($relatoriosCode, "exigirAcesso('relatorios')"), "modules/relatorios/index.php deve manter controle de acesso 'relatorios'.");
assertEtapa8(str_contains($relatoriosCode, 'Central de Relatórios Operacionais'), "modules/relatorios/index.php deve servir como Hub Central de Relatórios.");
assertEtapa8(str_contains($relatoriosCode, 'vistorias') && str_contains($relatoriosCode, 'financeiro/relatorios') && str_contains($relatoriosCode, 'protocolos'), "Hub de relatórios deve direcionar para vistorias, financeiro e protocolos.");
echo "   -> Sidebar e Central de Relatórios: OK\n\n";

// -------------------------------------------------------------
// 2. VERIFICACAO VISUAL E ESTRUTURAL DE NOTIFICACOES
// -------------------------------------------------------------
echo "2. Validando Módulo de Notificações...\n";
$notifCode = file_get_contents(__DIR__ . '/../modules/notificacoes/index.php');
assertEtapa8(str_contains($notifCode, "require_once __DIR__ . '/../../includes/sidebar.php'"), "modules/notificacoes/index.php deve incluir o sidebar.php.");
assertEtapa8(str_contains($notifCode, 'id="mainContent"'), "modules/notificacoes/index.php deve usar container mainContent padronizado.");
assertEtapa8(str_contains($notifCode, 'Minhas Notificações'), "modules/notificacoes/index.php deve ter cabeçalho padronizado.");
assertEtapa8(str_contains($notifCode, 'filtro=todas') && str_contains($notifCode, 'filtro=nao_lidas') && str_contains($notifCode, 'filtro=lidas'), "Notificações deve ter abas de filtros rápidos (todas, não lidas, lidas).");
assertEtapa8(str_contains($notifCode, 'gerarCSRF()'), "Notificações deve conter CSRF token para marcar como lidas.");
echo "   -> Módulo de Notificações: OK\n\n";

// -------------------------------------------------------------
// 3. INDICES MYSQL DE ALTA PERFORMANCE (MIGRATION 109)
// -------------------------------------------------------------
echo "3. Validando Índices Compostos no MySQL (Migration 109)...\n";
$checkIndex = function(PDO $pdo, string $tabela, string $indexName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :tab AND index_name = :idx");
    $stmt->execute([':tab' => $tabela, ':idx' => $indexName]);
    return (bool)$stmt->fetchColumn();
};

assertEtapa8($checkIndex($pdo, 'financeiro_lancamentos', 'idx_fin_ativo_tipo_status_data'), "Índice idx_fin_ativo_tipo_status_data deve existir em financeiro_lancamentos.");
assertEtapa8($checkIndex($pdo, 'vistorias', 'idx_vistorias_status_data'), "Índice idx_vistorias_status_data deve existir em vistorias.");
assertEtapa8($checkIndex($pdo, 'vistorias', 'idx_vistorias_status_aprovacao'), "Índice idx_vistorias_status_aprovacao deve existir em vistorias.");
assertEtapa8($checkIndex($pdo, 'agendamentos', 'idx_agendamentos_status_data'), "Índice idx_agendamentos_status_data deve existir em agendamentos.");
assertEtapa8($checkIndex($pdo, 'vistoria_exigencias', 'idx_exigencias_status_vencimento'), "Índice idx_exigencias_status_vencimento deve existir em vistoria_exigencias.");
assertEtapa8($checkIndex($pdo, 'certificados_csn', 'idx_csn_ativo_status_emissao'), "Índice idx_csn_ativo_status_emissao deve existir em certificados_csn.");
assertEtapa8($checkIndex($pdo, 'certificados_cnbl', 'idx_cnbl_ativo_status_emissao'), "Índice idx_cnbl_ativo_status_emissao deve existir em certificados_cnbl.");
assertEtapa8($checkIndex($pdo, 'certificados_cnarq', 'idx_cnarq_ativo_status_emissao'), "Índice idx_cnarq_ativo_status_emissao deve existir em certificados_cnarq.");
echo "   -> Todos os índices compostos de alta performance confirmados no MySQL: OK\n\n";

// -------------------------------------------------------------
// 4. CACHE TEMPORARIO INTELIGENTE E MEDICAO DE DESEMPENHO
// -------------------------------------------------------------
echo "4. Validando Cache Temporário do Dashboard e Precisão dos Dados...\n";

// Limpar cache anterior
dashboardInvalidarCache();

// 4.1 Carregamento direto no Banco (sem cache)
$t0 = microtime(true);
$dadosDb = dashboardLoadData($pdo, 'ADMIN', '00000000-0000-0000-0000-000000000001');
$tDb = (microtime(true) - $t0) * 1000;

// 4.2 Primeira chamada (armazena cache)
$dadosPrimeira = dashboardGetCachedData($pdo, 'ADMIN', '00000000-0000-0000-0000-000000000001', false);

// 4.3 Segunda chamada (lê do cache)
$t1 = microtime(true);
$dadosCache = dashboardGetCachedData($pdo, 'ADMIN', '00000000-0000-0000-0000-000000000001', false);
$tCache = (microtime(true) - $t1) * 1000;

echo "   -> Tempo de execução direto no banco: " . round($tDb, 2) . " ms\n";
echo "   -> Tempo de execução através do cache: " . round($tCache, 2) . " ms\n";

assertEtapa8($dadosCache['_cache']['cached'] === true, "A segunda chamada deve retornar dado em cache.");
assertEtapa8($tCache < $tDb, "O tempo de cache ({$tCache}ms) deve ser menor que o tempo de banco ({$tDb}ms).");

// 4.4 Comparação estrita de números e dados (sem metadados de cache)
$dadosDbComp = $dadosDb;
$dadosCacheComp = $dadosCache;
unset($dadosDbComp['_cache'], $dadosCacheComp['_cache']);

assertEtapa8(json_encode($dadosDbComp) === json_encode($dadosCacheComp), "Os dados retornados pelo cache devem ser 100% IDÊNTICOS aos do banco.");
echo "   -> Integridade analítica: 100% exata sem distorção de contadores ou valores financeiros.\n";

// 4.5 Teste de Invalidação forçada via ?refresh=1
$dadosForcados = dashboardGetCachedData($pdo, 'ADMIN', '00000000-0000-0000-0000-000000000001', true);
assertEtapa8($dadosForcados['_cache']['cached'] === false, "Chamada com forceRefresh=true deve ignorar o cache e recalcular.");

// 4.6 Teste de Invalidação explícita
dashboardInvalidarCache('ADMIN', '00000000-0000-0000-0000-000000000001');
$dadosAposInval = dashboardGetCachedData($pdo, 'ADMIN', '00000000-0000-0000-0000-000000000001', false);
assertEtapa8($dadosAposInval['_cache']['cached'] === false, "Após invalidar, o cache deve ser regenerado.");
echo "   -> Cache temporário e invalidação sob demanda: OK\n\n";

// -------------------------------------------------------------
// 5. USABILIDADE AUTODIDATICA CONFORME AGENTS.MD
// -------------------------------------------------------------
echo "5. Validando Usabilidade Autodidática (NORMAM / NPCP / RIPEAM)...\n";

// 5.1 Serviços
$servicosCode = file_get_contents(__DIR__ . '/../modules/servicos/form.php');
assertEtapa8(str_contains($servicosCode, 'aplicarModeloRapido'), "modules/servicos/form.php deve ter função aplicarModeloRapido para atalhos em 1 clique.");
assertEtapa8(str_contains($servicosCode, 'CSN Periódica (NORMAM-202)'), "modules/servicos/form.php deve ter pill CSN NORMAM-202.");
assertEtapa8(str_contains($servicosCode, 'Borda Livre CNBL (NORMAM-201)'), "modules/servicos/form.php deve ter pill CNBL NORMAM-201.");
assertEtapa8(str_contains($servicosCode, 'Arqueação CNARQ (NORMAM-201)'), "modules/servicos/form.php deve ter pill CNARQ NORMAM-201.");
assertEtapa8(str_contains($servicosCode, 'Licença Provisória LP (NORMAM-202)'), "modules/servicos/form.php deve ter pill LP NORMAM-202.");
assertEtapa8(str_contains($servicosCode, 'Licença Construção LC (NORMAM-202)'), "modules/servicos/form.php deve ter pill LC NORMAM-202.");
assertEtapa8(str_contains($servicosCode, 'Habitabilidade CHT (NORMAM-202)'), "modules/servicos/form.php deve ter pill CHT NORMAM-202.");

// 5.2 Clientes / Armadores
$clientesCode = file_get_contents(__DIR__ . '/../modules/clientes/form.php');
assertEtapa8(str_contains($clientesCode, 'function aoMudarPerfil(perfil)'), "modules/clientes/form.php deve implementar aoMudarPerfil.");
assertEtapa8(str_contains($clientesCode, 'NORMAM-201/202'), "modules/clientes/form.php deve conter orientação autodidática com referência NORMAM.");
assertEtapa8(str_contains($clientesCode, 'Capitania (NPCP)'), "modules/clientes/form.php deve conter orientação autodidática sobre Despachante e NPCP.");

// 5.3 Protocolos / Dossiês
$dossieCode = file_get_contents(__DIR__ . '/../modules/protocolos/components/dossie_identificacao.php');
assertEtapa8(str_contains($dossieCode, 'prot-shortcuts-container'), "dossie_identificacao.php deve conter atalhos rápidos de assunto naval.");
assertEtapa8(str_contains($dossieCode, 'sincronizarDadosEmbarcacao'), "dossie_identificacao.php deve conter vínculo inteligente de cliente por embarcação.");

echo "   -> Usabilidade Autodidática NORMAM nos módulos operacionais: OK\n\n";

echo "=== TODOS OS TESTES DA ETAPA 8 PASSARAM COM 100% DE SUCESSO! ===\n";
