<?php
/**
 * TESTE OFICIAL DE CONFORMIDADE:
 * Perfil do Analista Naval e Granularidade de Submódulos de Documentação
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function assertTrue(bool $condition, string $msg): void {
    if (!$condition) {
        throw new RuntimeException("FALHA: {$msg}");
    }
}

echo "1. Validando catálogo do sistema e granularidade de submódulos de documentos...\n";
$todasPerms = todasPermissoesSistema();
$submodulosEsperados = ['doc_csn', 'doc_cnbl', 'doc_cnarq', 'doc_nar', 'doc_lp', 'doc_lc', 'doc_cht'];
foreach ($submodulosEsperados as $sub) {
    assertTrue(in_array($sub, $todasPerms, true), "Submódulo {$sub} deve constar no catálogo todasPermissoesSistema()");
}
echo "   [✓] Todos os 7 submódulos de documentos estão no catálogo canônico.\n";

echo "2. Validando padrão do cargo ANALISTA...\n";
$padraoAnalista = permissoesPadraoCargo('ANALISTA');
assertTrue(in_array('analise_planos', $padraoAnalista, true), "Analista deve ter analise_planos");
assertTrue(in_array('doc_nar', $padraoAnalista, true), "Analista deve ter doc_nar (Notas de Arqueação)");
assertTrue(in_array('doc_lp', $padraoAnalista, true), "Analista deve ter doc_lp (Licença Provisória)");
assertTrue(in_array('doc_lc', $padraoAnalista, true), "Analista deve ter doc_lc (Licenças LC, LA, LR, LCEC)");
assertTrue(!in_array('doc_csn', $padraoAnalista, true), "Analista NÃO pode ter doc_csn");
assertTrue(!in_array('doc_cnbl', $padraoAnalista, true), "Analista NÃO pode ter doc_cnbl");
assertTrue(!in_array('doc_cnarq', $padraoAnalista, true), "Analista NÃO pode ter doc_cnarq");
assertTrue(!in_array('doc_cht', $padraoAnalista, true), "Analista NÃO pode ter doc_cht");
assertTrue(!in_array('embarcacoes', $padraoAnalista, true), "Analista NÃO pode ter embarcacoes");
assertTrue(!in_array('clientes', $padraoAnalista, true), "Analista NÃO pode ter clientes");
assertTrue(!in_array('protocolos_documentais', $padraoAnalista, true), "Analista NÃO pode ter protocolos_documentais");
assertTrue(!in_array('certificados', $padraoAnalista, true), "Analista NÃO pode ter certificados");
assertTrue(!in_array('vistorias', $padraoAnalista, true), "Analista NÃO pode ter vistorias");
echo "   [✓] Matriz padrão do Analista validada com rigor.\n";

echo "3. Validando autorização em tempo real (podeAcessar) para usuário Analista no banco...\n";
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cargo = 'ANALISTA' AND ativo = 1 AND excluido_em IS NULL LIMIT 1");
$stmt->execute();
$analistaId = $stmt->fetchColumn();
assertTrue(!empty($analistaId), "Deve existir ao menos um usuário Analista para o teste");

$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $analistaId;
$_SESSION['usuario_cargo'] = 'ANALISTA';

// Permitidos
assertTrue(podeAcessar('analise_planos'), "Analista deve ter podeAcessar('analise_planos') === true");
assertTrue(podeAcessar('doc_nar'), "Analista deve ter podeAcessar('doc_nar') === true");
assertTrue(podeAcessar('doc_lp'), "Analista deve ter podeAcessar('doc_lp') === true");
assertTrue(podeAcessar('doc_lc'), "Analista deve ter podeAcessar('doc_lc') === true");

// Bloqueados
assertTrue(!podeAcessar('doc_csn'), "Analista deve ter podeAcessar('doc_csn') === false");
assertTrue(!podeAcessar('doc_cnbl'), "Analista deve ter podeAcessar('doc_cnbl') === false");
assertTrue(!podeAcessar('doc_cnarq'), "Analista deve ter podeAcessar('doc_cnarq') === false");
assertTrue(!podeAcessar('embarcacoes'), "Analista deve ter podeAcessar('embarcacoes') === false");
assertTrue(!podeAcessar('clientes'), "Analista deve ter podeAcessar('clientes') === false");
assertTrue(!podeAcessar('protocolos_documentais'), "Analista deve ter podeAcessar('protocolos_documentais') === false");
assertTrue(!podeAcessar('certificados'), "Analista deve ter podeAcessar('certificados') === false");
echo "   [✓] podeAcessar() cumpre 100% dos bloqueios e liberações para o Analista.\n";

echo "4. Validando renderização da Sidebar para o Analista...\n";
ob_start();
require __DIR__ . '/../includes/sidebar.php';
$html = ob_get_clean();

assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/certificados"') === false, "Sidebar NÃO deve conter link para CSN");
assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/cnbl"') === false, "Sidebar NÃO deve conter link para CNBL");
assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/cnarq"') === false, "Sidebar NÃO deve conter link para CNARQ");
assertTrue(strpos($html, 'href="' . APP_URL . 'embarcacoes"') === false, "Sidebar NÃO deve conter link para Embarcações");
assertTrue(strpos($html, 'href="' . APP_URL . 'clientes"') === false, "Sidebar NÃO deve conter link para Clientes");
assertTrue(strpos($html, 'href="' . APP_URL . 'protocolos"') === false, "Sidebar NÃO deve conter link para Protocolos");
assertTrue(strpos($html, 'href="' . APP_URL . 'vistorias"') === false, "Sidebar NÃO deve conter link para Vistorias");

assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/nar"') !== false, "Sidebar DEVE conter link para NAR");
assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/lp"') !== false, "Sidebar DEVE conter link para LP");
assertTrue(strpos($html, 'href="' . APP_URL . 'documentacao/lc"') !== false, "Sidebar DEVE conter link para LC");
assertTrue(strpos($html, 'href="' . APP_URL . 'analises-planos"') !== false, "Sidebar DEVE conter link para Análise de Planos");
echo "   [✓] Sidebar do Analista oculta estritamente os módulos proibidos e exibe apenas os autorizados.\n";

echo "5. Validando acesso total do Administrador...\n";
$_SESSION['usuario_cargo'] = 'ADMIN';
$_SESSION['usuario_id'] = 'dd121661-feb4-42f6-895a-68eb0608d1e4';
foreach ($submodulosEsperados as $sub) {
    assertTrue(podeAcessar($sub), "Admin deve poder acessar {$sub}");
}
assertTrue(podeAcessar('embarcacoes'), "Admin deve poder acessar embarcacoes");
assertTrue(podeAcessar('clientes'), "Admin deve poder acessar clientes");
assertTrue(podeAcessar('protocolos_documentais'), "Admin deve poder acessar protocolos_documentais");
echo "   [✓] Administrador preserva acesso irrestrito a todos os módulos e submódulos.\n";

echo "\n>>> TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! <<<\n";
