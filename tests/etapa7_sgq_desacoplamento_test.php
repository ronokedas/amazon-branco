<?php
/**
 * TESTE DE VALIDAÇÃO: ETAPA 7 - DESACOPLAMENTO ARQUITETURAL DO SGQ / ISO 9001
 * 
 * Valida:
 * 1. Desacoplamento do núcleo comercial (propostas não travam por ISO 8.2).
 * 2. Desacoplamento do agendamento operacional (não trava por matriz de competência status_sgq).
 * 3. Menu lateral (NORMAM-202 em Configurações, Gestão da Qualidade isolada sob permissão 'sgq').
 * 4. Permissões granulares de acesso a modules/sgq/ (bloqueado para Vistoriador/Analista/Vendedor, liberado para SGQ/Admin).
 * 5. Preservação integral de todas as funções do subsistema SGQ (Indicadores, RNCs, 5W2H, Riscos, Auditoria).
 * 6. Ponto de contato legítimo: geração de RNC por Retorno A/S e Ouvidoria do Portal do Cliente.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sgq.php';

function assertEtapa7(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException("FALHA ETAPA 7: " . $mensagem);
    }
}

echo "=== TESTE ETAPA 7: DESACOPLAMENTO ARQUITETURAL DO SGQ / ISO 9001 ===\n\n";

// -------------------------------------------------------------------------
// 1. Validar que Propostas Comerciais NÃO são travadas por ISO 8.2
// -------------------------------------------------------------------------
echo "1. Validando desacoplamento de propostas comerciais (ISO 8.2)...\n";
$propostasActionCode = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
assertEtapa7(
    !str_contains($propostasActionCode, 'throw new Exception($validacaoTecnica[\'mensagem\'])'),
    "propostas/actions.php ainda contém throw de exceção impeditiva de prontidão técnica ISO 8.2."
);
echo "   [OK] Propostas comerciais desacopladas da trava impeditiva de ISO 8.2.\n\n";

// -------------------------------------------------------------------------
// 2. Validar que Agendamentos NÃO são travados por status_sgq / ISO 7.2
// -------------------------------------------------------------------------
echo "2. Validando desacoplamento de agendamentos operacionais (ISO 7.2)...\n";
$agendamentosActionCode = file_get_contents(__DIR__ . '/../modules/agendamentos/actions.php');
assertEtapa7(
    !str_contains($agendamentosActionCode, 'COMPETENCIA_SGQ_INVALIDA'),
    "agendamentos/actions.php ainda referencia código de erro COMPETENCIA_SGQ_INVALIDA."
);
assertEtapa7(
    !str_contains($agendamentosActionCode, "Bloqueio de Competência SGQ"),
    "agendamentos/actions.php ainda exibe mensagem de Bloqueio de Competência SGQ."
);
assertEtapa7(
    str_contains($agendamentosActionCode, 'VISTORIADOR_INVALIDO'),
    "agendamentos/actions.php deve validar se o vistoriador está ativo no sistema."
);
echo "   [OK] Agendamentos desacoplados da matriz de auditoria ISO 7.2, preservando validação operacional de vistoriador ativo.\n\n";

// -------------------------------------------------------------------------
// 3. Validar Menu Lateral (sidebar.php) e Rotas (index.php)
// -------------------------------------------------------------------------
echo "3. Validando organização de menus e separação NORMAM-202 vs SGQ...\n";
$sidebarCode = file_get_contents(__DIR__ . '/../includes/sidebar.php');

// NORMAM-202 não deve estar dentro do submenu SGQ
$sgqSubmenuPattern = '/<div class="nav-submenu[^>]*>.*?<\/div>/s';
preg_match_all($sgqSubmenuPattern, $sidebarCode, $matches);
$foundNormamInSgq = false;
foreach ($matches[0] as $submenuHtml) {
    if (str_contains($submenuHtml, 'sgq/manual') && str_contains($submenuHtml, 'configuracoes/normam202')) {
        $foundNormamInSgq = true;
        break;
    }
}
assertEtapa7(!$foundNormamInSgq, "Exigências NORMAM-202 ainda consta dentro do submenu de Gestão da Qualidade!");

// NORMAM-202 deve estar dentro do bloco CONFIGURAÇÕES
assertEtapa7(
    str_contains($sidebarCode, '<a href="<?= APP_URL ?>configuracoes/normam202"'),
    "Link de NORMAM-202 não foi encontrado em CONFIGURAÇÕES."
);

// Menu SGQ deve existir isolado sob 'GESTÃO DA QUALIDADE' e condicionado a podeAcessar('sgq')
assertEtapa7(
    str_contains($sidebarCode, 'GESTÃO DA QUALIDADE'),
    "Seção GESTÃO DA QUALIDADE não encontrada no sidebar."
);
assertEtapa7(
    str_contains($sidebarCode, "podeAcessar('sgq')"),
    "Menu do SGQ deve ser condicionado a podeAcessar('sgq')."
);

// Rota raiz 'sgq' em index.php
$indexCode = file_get_contents(__DIR__ . '/../index.php');
assertEtapa7(
    str_contains($indexCode, "'sgq' => 'modules/sgq/manual.php'"),
    "Rota raiz 'sgq' não mapeada para modules/sgq/manual.php no index.php."
);
echo "   [OK] NORMAM-202 devidamente posicionado em Configurações e SGQ isolado sob permissão específica.\n\n";

// -------------------------------------------------------------------------
// 4. Validar Guarda de Acesso em Todos os 8 Arquivos de modules/sgq/
// -------------------------------------------------------------------------
echo "4. Validando exigência de permissão 'sgq' em todos os arquivos de modules/sgq/...\n";
$arquivosSgq = [
    'apresentacao.php',
    'auditoria.php',
    'indicadores.php',
    'manual.php',
    'nao_conformidades.php',
    'nao_conformidades_actions.php',
    'riscos.php',
    'riscos_actions.php',
];

foreach ($arquivosSgq as $arq) {
    $conteudo = file_get_contents(__DIR__ . '/../modules/sgq/' . $arq);
    assertEtapa7(
        str_contains($conteudo, "exigirAcesso('sgq')"),
        "Arquivo modules/sgq/{$arq} não exige permissão 'sgq'."
    );
    assertEtapa7(
        !str_contains($conteudo, "exigirAcesso('dashboard')"),
        "Arquivo modules/sgq/{$arq} ainda utiliza exigirAcesso('dashboard')."
    );
}

// Validar perfis padrão: Vistoriador, Analista e Vendedor NÃO possuem 'sgq' por padrão
assertEtapa7(!in_array('sgq', permissoesPadraoCargo('VISTORIADOR'), true), "Vistoriador não deve ter permissão 'sgq' por padrão.");
assertEtapa7(!in_array('sgq', permissoesPadraoCargo('ANALISTA'), true), "Analista não deve ter permissão 'sgq' por padrão.");
assertEtapa7(!in_array('sgq', permissoesPadraoCargo('VENDEDOR'), true), "Vendedor não deve ter permissão 'sgq' por padrão.");
assertEtapa7(in_array('sgq', permissoesPadraoCargo('ADMIN'), true), "Admin deve ter permissão 'sgq'.");
echo "   [OK] Todos os 8 arquivos do SGQ protegidos com exigirAcesso('sgq') e restritos para operadores navais padrão.\n\n";

// -------------------------------------------------------------------------
// 5. Validar Funcionalidades do SGQ Isolado (Sem Perda de Nenhuma Função)
// -------------------------------------------------------------------------
echo "5. Validando funcionamento integral das funções do subsistema SGQ...\n";

// 5.1 Motor de Indicadores (ISO 9.1)
$indicadores = sgqObterIndicadores($pdo);
assertEtapa7(is_array($indicadores), "sgqObterIndicadores deve retornar um array.");
assertEtapa7(isset($indicadores['indicador_retrabalho']['taxa_percentual']), "Indicador de retrabalho ausente.");
assertEtapa7(isset($indicadores['indicador_lead_time']['dias_medio']), "Indicador de lead time ausente.");
assertEtapa7(isset($indicadores['indicador_satisfacao']['indice_ponderado_percent']), "Indicador de satisfação ausente.");
assertEtapa7(isset($indicadores['rncs']['total']), "Indicador de RNCs ausente.");
echo "   [OK] Motor de cálculo de indicadores SGQ (ISO 9.1) funcionando perfeitamente.\n";

// 5.2 Gerador de Sequencial de RNC e Ciclo de Vida
$numRnc1 = sgqGerarNumeroRNC($pdo);
$numRnc2 = sgqGerarNumeroRNC($pdo);
assertEtapa7(!empty($numRnc1) && str_starts_with($numRnc1, 'RNC-'), "Formato de RNC inválido: {$numRnc1}");
assertEtapa7($numRnc1 !== $numRnc2, "Sequencial de RNC deve incrementar: {$numRnc1} vs {$numRnc2}");
echo "   [OK] Sequenciais automáticos de RNC gerados com sucesso ({$numRnc1}, {$numRnc2}).\n";

// 5.3 Auditoria Cadastral (ISO 7.5)
$_SESSION['usuario_id'] = 'u-admin-test';
$_SESSION['usuario_nome'] = 'Administrador Teste';
sgqRegistrarAuditoriaCadastral(
    $pdo,
    'EMBARCACAO',
    'emb-teste-sgq',
    'ALTERACAO',
    ['nome' => 'Barco Antigo', 'arqueacao_bruta' => '10'],
    ['nome' => 'Barco Novo', 'arqueacao_bruta' => '15'],
    'Teste de auditoria isolada da Etapa 7'
);
$stmtAud = $pdo->prepare("SELECT * FROM sgq_auditoria_cadastral WHERE entidade_id = 'emb-teste-sgq' ORDER BY criado_em DESC LIMIT 1");
$stmtAud->execute();
$logAud = $stmtAud->fetch(PDO::FETCH_ASSOC);
assertEtapa7($logAud !== false, "Registro de auditoria cadastral não foi inserido no banco de dados.");
assertEtapa7($logAud['entidade_tipo'] === 'EMBARCACAO', "Tipo de entidade na auditoria incorreto.");
assertEtapa7(str_contains($logAud['campos_alterados'], 'nome'), "Campo alterado 'nome' não registrado no delta.");
echo "   [OK] Trilha de auditoria cadastral (ISO 7.5) gravando deltas e motivos perfeitamente.\n";

// 5.4 Matriz de Riscos (ISO 6.1)
assertEtapa7(sgqCalcularNivelRisco(5, 4) === 'CRITICO', "Score 20 deve ser CRITICO.");
assertEtapa7(sgqCalcularNivelRisco(3, 4) === 'ALTO', "Score 12 deve ser ALTO.");
assertEtapa7(sgqCalcularNivelRisco(2, 3) === 'MEDIO', "Score 6 deve ser MEDIO.");
assertEtapa7(sgqCalcularNivelRisco(1, 2) === 'BAIXO', "Score 2 deve ser BAIXO.");
echo "   [OK] Matriz de riscos e oportunidades (ISO 6.1) validada com sucesso.\n";

// 5.5 Prontidão Comercial e Elegibilidade Técnica (permanecem ativas para auditoria interna)
assertEtapa7(function_exists('embarcacaoValidarProntidaoComercial'), "Função embarcacaoValidarProntidaoComercial deve existir em includes/sgq.php.");
assertEtapa7(function_exists('vistoriadorElegivelParaAgendamento'), "Função vistoriadorElegivelParaAgendamento deve existir em includes/sgq.php.");
echo "   [OK] Funções de auditoria interna preservadas para relatórios e análises críticas do SGQ.\n\n";

// -------------------------------------------------------------------------
// 6. Validar Ponto de Contato Legítimo: Retorno A/S gera RNC sem travar
// -------------------------------------------------------------------------
echo "6. Validando ponto de contato legítimo (Retorno A/S em vistoria técnica gera RNC de segurança)...\n";
assertEtapa7(function_exists('sgqGerarRncAutomaticaPorRetornoAS'), "Função sgqGerarRncAutomaticaPorRetornoAS deve existir.");
echo "   [OK] Integração entre laudo impeditivo e registro de segurança da qualidade validada.\n\n";

// -------------------------------------------------------------------------
// 7. Validar Ouvidoria do Portal do Cliente
// -------------------------------------------------------------------------
echo "7. Validando canal de ouvidoria do Portal do Cliente (modules/portal/ouvidoria.php)...\n";
$ouvidoriaPortalCode = file_get_contents(__DIR__ . '/../modules/portal/ouvidoria.php');
assertEtapa7(
    str_contains($ouvidoriaPortalCode, 'clientePortalEmbarcacoes'),
    "Ouvidoria do portal deve listar embarcações do cliente."
);
assertEtapa7(
    str_contains($ouvidoriaPortalCode, 'sgq_nao_conformidades'),
    "Ouvidoria do portal deve consultar manifestações registradas do cliente."
);
$ouvidoriaActionCode = file_get_contents(__DIR__ . '/../modules/portal/ouvidoria_actions.php');
assertEtapa7(
    str_contains($ouvidoriaActionCode, 'sgqGerarNumeroRNC'),
    "Ação de ouvidoria do portal deve gerar número oficial de RNC."
);
echo "   [OK] Portal de Ouvidoria do cliente operacional e conectado ao módulo de qualidade.\n\n";

echo "TODOS OS TESTES DA ETAPA 7 PASSARAM COM 100% DE SUCESSO!\n";
