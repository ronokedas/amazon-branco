<?php
/**
 * MÓDULO: CERTIFICADOS
 * Arquivo: vencimentos.php - Monitoramento Centralizado de Vencimento de Certificados Navais
 * 
 * Normas de Referência: NORMAM-201 / NORMAM-202 (DPC / Marinha do Brasil)
 * Consolida CSN, CNBL, CNARQ, LP, LC e CHT em uma visão operacional unificada.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();

if (!podeAcessar('vencimentos_certificados')) {
    setMensagem('error', 'Acesso negado para este módulo.');
    redirecionar(APP_URL . 'dashboard');
}

// -------------------------------------------------------------
// Parâmetros de Filtro e Paginação
// -------------------------------------------------------------
$filtroPrazo = trim((string)($_GET['prazo'] ?? 'padrao')); // 'padrao' = <= 60 dias + vencidos
$filtroModelo = strtoupper(trim((string)($_GET['modelo'] ?? 'TODOS')));
$busca = trim((string)($_GET['q'] ?? ''));
$ordem = trim((string)($_GET['ordem'] ?? 'validade_asc'));

$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = max(10, min(100, (int)($_GET['por_pagina'] ?? 25)));

// -------------------------------------------------------------
// Montagem da Query Unificada de Certificados
// -------------------------------------------------------------
$subqueryUnion = "
    SELECT 
        'CSN' AS modelo,
        'Certificado de Segurança da Navegação' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/certificados/pdf' AS rota_pdf,
        'documentacao/certificados/form' AS rota_form
    FROM certificados_csn c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL AND c.data_validade != '0000-00-00'

    UNION ALL

    SELECT 
        'CNBL' AS modelo,
        'Certificado Nacional de Borda Livre' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/cnbl/pdf' AS rota_pdf,
        'documentacao/cnbl' AS rota_form
    FROM certificados_cnbl c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL AND c.data_validade != '0000-00-00'

    UNION ALL

    SELECT 
        'CNARQ' AS modelo,
        'Certificado Nacional de Arqueação' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/cnarq/pdf' AS rota_pdf,
        'documentacao/cnarq' AS rota_form
    FROM certificados_cnarq c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL AND c.data_validade != '0000-00-00'

    UNION ALL

    SELECT 
        'LP' AS modelo,
        'Licença Provisória' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_lp, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.validade_data AS data_validade,
        c.status,
        DATEDIFF(c.validade_data, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/lp/pdf' AS rota_pdf,
        'documentacao/lp' AS rota_form
    FROM certificados_lp c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.validade_data IS NOT NULL AND c.validade_data != '0000-00-00'

    UNION ALL

    SELECT 
        'LC' AS modelo,
        'Licença de Construção' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_lc, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/lc/pdf' AS rota_pdf,
        'documentacao/lc' AS rota_form
    FROM certificados_lc c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL AND c.data_validade != '0000-00-00'

    UNION ALL

    SELECT 
        'CHT' AS modelo,
        'Certificado de Homologação Técnica' AS modelo_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_certificado, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(emb.nome, ''), 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        cli.perfil AS cliente_perfil,
        'documentacao/cht/pdf' AS rota_pdf,
        'documentacao/cht' AS rota_form
    FROM certificados_cht c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL AND c.data_validade != '0000-00-00'
";

// -------------------------------------------------------------
// Consulta de Totais e KPIs (Sem filtros para manter os contadores globais)
// -------------------------------------------------------------
$kpis = [
    'total_geral' => 0,
    'vencidos' => 0,
    'critico_15' => 0,
    'urgente_30' => 0,
    'atencao_60' => 0,
    'planejamento_90' => 0,
    'vigentes' => 0
];

try {
    $sqlKpis = "
        SELECT 
            COUNT(*) AS total_geral,
            SUM(CASE WHEN dias_restantes < 0 THEN 1 ELSE 0 END) AS vencidos,
            SUM(CASE WHEN dias_restantes BETWEEN 0 AND 15 THEN 1 ELSE 0 END) AS critico_15,
            SUM(CASE WHEN dias_restantes BETWEEN 16 AND 30 THEN 1 ELSE 0 END) AS urgente_30,
            SUM(CASE WHEN dias_restantes BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS atencao_60,
            SUM(CASE WHEN dias_restantes BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS planejamento_90,
            SUM(CASE WHEN dias_restantes > 90 THEN 1 ELSE 0 END) AS vigentes
        FROM ({$subqueryUnion}) AS unificado
    ";
    $stmtKpi = $pdo->query($sqlKpis);
    $kpiResult = $stmtKpi->fetch(PDO::FETCH_ASSOC);
    if ($kpiResult) {
        $kpis['total_geral']     = (int)($kpiResult['total_geral'] ?? 0);
        $kpis['vencidos']        = (int)($kpiResult['vencidos'] ?? 0);
        $kpis['critico_15']      = (int)($kpiResult['critico_15'] ?? 0);
        $kpis['urgente_30']      = (int)($kpiResult['urgente_30'] ?? 0);
        $kpis['atencao_60']      = (int)($kpiResult['atencao_60'] ?? 0);
        $kpis['planejamento_90'] = (int)($kpiResult['planejamento_90'] ?? 0);
        $kpis['vigentes']        = (int)($kpiResult['vigentes'] ?? 0);
    }
} catch (Exception $e) {
    error_log('Erro ao calcular KPIs de vencimentos: ' . $e->getMessage());
}

// -------------------------------------------------------------
// Cláusulas WHERE dinâmicas para a listagem filtrada
// -------------------------------------------------------------
$whereConditions = [];
$params = [];

// Filtro de Prazo
switch ($filtroPrazo) {
    case 'padrao':
        // Janela crítica operacional: Vencidos até 60 dias
        $whereConditions[] = "u.dias_restantes <= 60";
        break;
    case 'vencidos':
        $whereConditions[] = "u.dias_restantes < 0";
        break;
    case 'critico_15':
        $whereConditions[] = "u.dias_restantes BETWEEN 0 AND 15";
        break;
    case 'urgente_30':
        $whereConditions[] = "u.dias_restantes BETWEEN 16 AND 30";
        break;
    case 'atencao_60':
        $whereConditions[] = "u.dias_restantes BETWEEN 31 AND 60";
        break;
    case 'planejamento_90':
        $whereConditions[] = "u.dias_restantes BETWEEN 61 AND 90";
        break;
    case 'vigentes':
        $whereConditions[] = "u.dias_restantes > 90";
        break;
    case 'todos':
    default:
        // Não restringe prazo
        break;
}

// Filtro de Modelo
if ($filtroModelo !== 'TODOS' && in_array($filtroModelo, ['CSN', 'CNBL', 'CNARQ', 'LP', 'LC', 'CHT'], true)) {
    $whereConditions[] = "u.modelo = :modelo";
    $params[':modelo'] = $filtroModelo;
}

// Busca Textual
if ($busca !== '') {
    $whereConditions[] = "(u.nome_embarcacao LIKE :busca OR u.numero_inscricao LIKE :busca OR u.numero_documento LIKE :busca OR u.cliente_nome LIKE :busca)";
    $params[':busca'] = '%' . $busca . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// -------------------------------------------------------------
// Ordenação
// -------------------------------------------------------------
$orderBy = match ($ordem) {
    'validade_desc'  => 'u.data_validade DESC, u.nome_embarcacao ASC',
    'embarcacao_asc' => 'u.nome_embarcacao ASC, u.data_validade ASC',
    'cliente_asc'    => 'u.cliente_nome ASC, u.data_validade ASC',
    default          => 'u.data_validade ASC, u.nome_embarcacao ASC', // Mais próximos primeiro
};

// -------------------------------------------------------------
// Paginação e Contagem de Registros Filtrados
// -------------------------------------------------------------
$totalFiltrados = 0;
try {
    $sqlCount = "SELECT COUNT(*) FROM ({$subqueryUnion}) AS u {$whereClause}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalFiltrados = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {
    error_log('Erro ao contar certificados filtrados: ' . $e->getMessage());
}

$totalPaginas = max(1, (int)ceil($totalFiltrados / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;

$certificados = [];
try {
    $sqlList = "
        SELECT u.* 
        FROM ({$subqueryUnion}) AS u 
        {$whereClause} 
        ORDER BY {$orderBy} 
        LIMIT {$porPagina} OFFSET {$offset}
    ";
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $certificados = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar certificados por vencimento: ' . $e->getMessage());
}

// Auxiliar para gerar query string de filtros preservando estado
function buildFilterUrl(array $overrides = []): string {
    global $filtroPrazo, $filtroModelo, $busca, $ordem, $porPagina;
    $params = [
        'prazo'      => $overrides['prazo'] ?? $filtroPrazo,
        'modelo'     => $overrides['modelo'] ?? $filtroModelo,
        'q'          => $overrides['q'] ?? $busca,
        'ordem'      => $overrides['ordem'] ?? $ordem,
        'por_pagina' => $overrides['por_pagina'] ?? $porPagina,
        'pagina'     => $overrides['pagina'] ?? 1,
    ];
    return APP_URL . 'certificados/vencimentos?' . http_build_query($params);
}

$titulo_page = 'Vencimentos de Certificados Navais - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* -------------------------------------------------------------
 * ESTILOS: MONITOR DE VENCIMENTOS NAVAIS
 * ------------------------------------------------------------- */
.venc-shell {
    padding: 24px;
    max-width: 1600px;
    margin: 0 auto;
}

/* Header & Contexto Autodidático */
.venc-hero {
    background: linear-gradient(135deg, #0d2744 0%, #153e6b 100%);
    border-radius: 12px;
    padding: 24px 30px;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(13, 39, 68, 0.15);
}

.venc-hero-title h1 {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.venc-hero-title p {
    margin: 0;
    color: #cbd8e8;
    font-size: 0.95rem;
    max-width: 780px;
}

.venc-hero-badge {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    padding: 10px 16px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.venc-hero-badge span {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #a4c4e8;
}

.venc-hero-badge strong {
    font-size: 1.15rem;
    color: #ffffff;
}

/* KPI Cards com Filtragem Rápida */
.venc-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.venc-kpi-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 18px 20px;
    border: 1px solid #e1e7ec;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none !important;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    position: relative;
    overflow: hidden;
}

.venc-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}

.venc-kpi-card.is-active {
    border-width: 2px;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
}

.venc-kpi-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
}

.venc-kpi-card.kpi-vencidos::before { background: #dc3545; }
.venc-kpi-card.kpi-critico::before  { background: #fd7e14; }
.venc-kpi-card.kpi-urgente::before  { background: #e0a800; }
.venc-kpi-card.kpi-atencao::before  { background: #0dcaf0; }
.venc-kpi-card.kpi-vigente::before  { background: #198754; }
.venc-kpi-card.kpi-total::before    { background: #0d6efd; }

.venc-kpi-card.is-active.kpi-vencidos { border-color: #dc3545; }
.venc-kpi-card.is-active.kpi-critico  { border-color: #fd7e14; }
.venc-kpi-card.is-active.kpi-urgente  { border-color: #e0a800; }
.venc-kpi-card.is-active.kpi-atencao  { border-color: #0dcaf0; }
.venc-kpi-card.is-active.kpi-vigente  { border-color: #198754; }
.venc-kpi-card.is-active.kpi-total    { border-color: #0d6efd; }

.venc-kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.venc-kpi-title {
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #5d7186;
}

.venc-kpi-icon {
    font-size: 1.25rem;
    opacity: 0.75;
}

.venc-kpi-card.kpi-vencidos .venc-kpi-icon { color: #dc3545; }
.venc-kpi-card.kpi-critico  .venc-kpi-icon { color: #fd7e14; }
.venc-kpi-card.kpi-urgente  .venc-kpi-icon { color: #e0a800; }
.venc-kpi-card.kpi-atencao  .venc-kpi-icon { color: #0dcaf0; }
.venc-kpi-card.kpi-vigente  .venc-kpi-icon { color: #198754; }
.venc-kpi-card.kpi-total    .venc-kpi-icon { color: #0d6efd; }

.venc-kpi-value {
    font-size: 1.85rem;
    font-weight: 700;
    color: #1a2a3a;
    line-height: 1;
    margin-bottom: 4px;
}

.venc-kpi-subtitle {
    font-size: 0.75rem;
    color: #7d8d9d;
}

/* Painel de Filtros e Pesquisa */
.venc-controls-panel {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e1e7ec;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.venc-filter-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #edf1f5;
}

.venc-tab-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.84rem;
    font-weight: 500;
    color: #495057;
    background: #f1f4f8;
    border: 1px solid transparent;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}

.venc-tab-pill:hover {
    background: #e2e7ef;
    color: #0b2239;
}

.venc-tab-pill.active {
    background: #0d2744;
    color: #ffffff;
    font-weight: 600;
}

.venc-tab-pill .pill-count {
    background: rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    padding: 1px 7px;
    font-size: 0.75rem;
}

.venc-tab-pill.active .pill-count {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

.venc-search-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px;
}

.venc-search-group {
    flex: 1;
    min-width: 250px;
}

.venc-search-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 4px;
}

.venc-select-group {
    min-width: 160px;
}

.venc-select-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 4px;
}

/* Tabela de Certificados */
.venc-table-card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e1e7ec;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.venc-table-header {
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e1e7ec;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.venc-table-header-info {
    font-size: 0.88rem;
    color: #495057;
}

.venc-table-responsive {
    overflow-x: auto;
}

.venc-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}

.venc-table th {
    background: #f1f4f8;
    color: #334155;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 14px;
    border-bottom: 1px solid #cbd5e1;
    white-space: nowrap;
}

.venc-table td {
    padding: 14px;
    border-bottom: 1px solid #edf2f7;
    font-size: 0.88rem;
    color: #1e293b;
    vertical-align: middle;
}

.venc-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Badges e Pílulas */
.badge-doc-csn   { background: #e0e7ff; color: #3730a3; font-weight: 700; }
.badge-doc-cnbl  { background: #e0f2fe; color: #0369a1; font-weight: 700; }
.badge-doc-cnarq { background: #f3e8ff; color: #6b21a8; font-weight: 700; }
.badge-doc-lp    { background: #fef3c7; color: #92400e; font-weight: 700; }
.badge-doc-lc    { background: #ffedd5; color: #9a3412; font-weight: 700; }
.badge-doc-cht   { background: #dcfce7; color: #166534; font-weight: 700; }

.venc-urgency-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
}

.urgency-vencido  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.urgency-critico  { background: #ffedd5; color: #c2410c; border: 1px solid #fdba74; }
.urgency-urgente  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.urgency-atencao  { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.urgency-plano    { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.urgency-vigente  { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }

/* Botões de Ação Especiais */
.btn-whatsapp {
    background-color: #25d366;
    border-color: #25d366;
    color: #ffffff !important;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    font-size: 0.8rem;
    border-radius: 5px;
    transition: all 0.15s ease;
}

.btn-whatsapp:hover {
    background-color: #1eb956;
    border-color: #1eb956;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(37, 211, 102, 0.35);
}

.btn-copy-msg {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 5px 9px;
    font-size: 0.8rem;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-copy-msg:hover {
    background-color: #e2e8f0;
    color: #0f172a;
}

/* Modal de Cópia / Notificação */
.venc-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    z-index: 10050;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.venc-modal-backdrop.is-open {
    display: flex;
}

.venc-modal-box {
    background: #ffffff;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: vencModalIn 0.18s ease-out;
}

@keyframes vencModalIn {
    from { opacity: 0; transform: scale(0.96); }
    to   { opacity: 1; transform: scale(1); }
}

.venc-modal-header {
    padding: 18px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.venc-modal-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.venc-modal-body {
    padding: 20px 24px;
}

.venc-modal-footer {
    padding: 14px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.venc-copy-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px;
    font-size: 0.9rem;
    font-family: inherit;
    line-height: 1.5;
    background: #f8fafc;
    resize: vertical;
    min-height: 140px;
}

/* Toast de Confirmação */
#vencToast {
    visibility: hidden;
    min-width: 250px;
    background-color: #1e293b;
    color: #fff;
    text-align: center;
    border-radius: 8px;
    padding: 12px 16px;
    position: fixed;
    z-index: 10100;
    left: 50%;
    bottom: 30px;
    transform: translateX(-50%);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    font-size: 0.9rem;
}

#vencToast.show {
    visibility: visible;
    animation: fadein 0.3s, fadeout 0.3s 2.2s;
}

@keyframes fadein {
    from { bottom: 0; opacity: 0; }
    to { bottom: 30px; opacity: 1; }
}

@keyframes fadeout {
    from { bottom: 30px; opacity: 1; }
    to { bottom: 0; opacity: 0; }
}
</style>

<div class="main-content venc-shell" id="mainContent">

    <!-- Hero / Cabeçalho Autodidático -->
    <div class="venc-hero">
        <div class="venc-hero-title">
            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94b8e0; display: block; margin-bottom: 4px;">
                <i class="fas fa-compass"></i> Gestão da Frota & Autoridade Marítima (DPC / NORMAM)
            </span>
            <h1><i class="fas fa-clock-rotate-left"></i> Vencimento de Certificados Navais</h1>
            <p>
                Acompanhamento centralizado de validades estatutárias (CSN, CNBL, CNARQ, LP, LC e CHT). 
                Monitore prazos de renovação, evite notificações ou retenções pela Capitania dos Portos e avise os proprietários via WhatsApp em 1 clique.
            </p>
        </div>
        <div class="venc-hero-badge">
            <span>Certificados Ativos</span>
            <strong><?php echo number_format($kpis['total_geral'], 0, ',', '.'); ?> documentos</strong>
            <small style="color: #cbd8e8; margin-top: 2px;">Monitorados em tempo real</small>
        </div>
    </div>

    <!-- Cards de Indicadores (KPIs) Acionáveis -->
    <div class="venc-kpi-grid">
        <!-- Vencidos -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'vencidos', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-vencidos <?php echo $filtroPrazo === 'vencidos' ? 'is-active' : ''; ?>">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">Já Vencidos</span>
                <i class="fas fa-triangle-exclamation venc-kpi-icon"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['vencidos']; ?></div>
            <div class="venc-kpi-subtitle">Vistoria imediata requerida</div>
        </a>

        <!-- Crítico (<= 15 dias) -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'critico_15', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-critico <?php echo $filtroPrazo === 'critico_15' ? 'is-active' : ''; ?>">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">Até 15 Dias</span>
                <i class="fas fa-bell venc-kpi-icon"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['critico_15']; ?></div>
            <div class="venc-kpi-subtitle">Risco iminente de paralisação</div>
        </a>

        <!-- Urgente (16 a 30 dias) -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'urgente_30', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-urgente <?php echo $filtroPrazo === 'urgente_30' ? 'is-active' : ''; ?>">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">16 a 30 Dias</span>
                <i class="fas fa-calendar-xmark venc-kpi-icon"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['urgente_30']; ?></div>
            <div class="venc-kpi-subtitle">Janela regulamentar NORMAM</div>
        </a>

        <!-- Atenção (31 a 60 dias) -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'atencao_60', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-atencao <?php echo $filtroPrazo === 'atencao_60' ? 'is-active' : ''; ?>">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">31 a 60 Dias</span>
                <i class="fas fa-clock venc-kpi-icon"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['atencao_60']; ?></div>
            <div class="venc-kpi-subtitle">Contato comercial e prévia</div>
        </a>

        <!-- Planejamento (61 a 90 dias) -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'planejamento_90', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-plano <?php echo $filtroPrazo === 'planejamento_90' ? 'is-active' : ''; ?>" style="border-left: 6px solid #6c757d;">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">61 a 90 Dias</span>
                <i class="fas fa-calendar-check venc-kpi-icon" style="color: #6c757d;"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['planejamento_90']; ?></div>
            <div class="venc-kpi-subtitle">Planejamento preventivo</div>
        </a>

        <!-- Vigentes (> 90 dias) -->
        <a href="<?php echo buildFilterUrl(['prazo' => 'vigentes', 'pagina' => 1]); ?>" 
           class="venc-kpi-card kpi-vigente <?php echo $filtroPrazo === 'vigentes' ? 'is-active' : ''; ?>">
            <div class="venc-kpi-header">
                <span class="venc-kpi-title">Vigentes (> 90d)</span>
                <i class="fas fa-shield-check venc-kpi-icon"></i>
            </div>
            <div class="venc-kpi-value"><?php echo $kpis['vigentes']; ?></div>
            <div class="venc-kpi-subtitle">Situação regularizada</div>
        </a>
    </div>

    <!-- Painel de Controles, Filtros e Pesquisa -->
    <div class="venc-controls-panel">
        <!-- Abas de Prazo em Chips/Pills -->
        <div class="venc-filter-tabs">
            <a href="<?php echo buildFilterUrl(['prazo' => 'padrao', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'padrao' ? 'active' : ''; ?>" 
               title="Certificados que vencem em até 60 dias ou já venceram">
                <i class="fas fa-fire-flame-curved"></i> Críticos e Urgentes (≤ 60 dias)
                <span class="pill-count"><?php echo ($kpis['vencidos'] + $kpis['critico_15'] + $kpis['urgente_30'] + $kpis['atencao_60']); ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'vencidos', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'vencidos' ? 'active' : ''; ?>">
                <i class="fas fa-circle-xmark text-danger"></i> Vencidos
                <span class="pill-count"><?php echo $kpis['vencidos']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'critico_15', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'critico_15' ? 'active' : ''; ?>">
                <i class="fas fa-bell text-warning"></i> Até 15 dias
                <span class="pill-count"><?php echo $kpis['critico_15']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'urgente_30', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'urgente_30' ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i> 16 a 30 dias
                <span class="pill-count"><?php echo $kpis['urgente_30']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'atencao_60', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'atencao_60' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-day"></i> 31 a 60 dias
                <span class="pill-count"><?php echo $kpis['atencao_60']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'planejamento_90', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'planejamento_90' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i> 61 a 90 dias
                <span class="pill-count"><?php echo $kpis['planejamento_90']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'vigentes', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'vigentes' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle text-success"></i> Vigentes (> 90d)
                <span class="pill-count"><?php echo $kpis['vigentes']; ?></span>
            </a>

            <a href="<?php echo buildFilterUrl(['prazo' => 'todos', 'pagina' => 1]); ?>" 
               class="venc-tab-pill <?php echo $filtroPrazo === 'todos' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i> Todos
                <span class="pill-count"><?php echo $kpis['total_geral']; ?></span>
            </a>
        </div>

        <!-- Formulário de Filtros Dinâmicos -->
        <form method="GET" action="<?php echo APP_URL; ?>certificados/vencimentos" class="venc-search-form">
            <input type="hidden" name="prazo" value="<?php echo h($filtroPrazo); ?>">

            <!-- Busca Textual -->
            <div class="venc-search-group">
                <label for="campoBusca"><i class="fas fa-search"></i> Pesquisar</label>
                <input type="text" 
                       id="campoBusca" 
                       name="q" 
                       value="<?php echo h($busca); ?>" 
                       class="form-control" 
                       placeholder="Nome da embarcação, inscrição, cliente ou nº certificado...">
                <small class="text-muted">Filtre por qualquer termo identificador naval.</small>
            </div>

            <!-- Modelo Estatutário -->
            <div class="venc-select-group">
                <label for="selectModelo"><i class="fas fa-file-shield"></i> Modelo Estatutário</label>
                <select id="selectModelo" name="modelo" class="form-control" onchange="this.form.submit()">
                    <option value="TODOS" <?php echo $filtroModelo === 'TODOS' ? 'selected' : ''; ?>>Todos os Modelos</option>
                    <option value="CSN" <?php echo $filtroModelo === 'CSN' ? 'selected' : ''; ?>>CSN (Segurança)</option>
                    <option value="CNBL" <?php echo $filtroModelo === 'CNBL' ? 'selected' : ''; ?>>CNBL (Borda Livre)</option>
                    <option value="CNARQ" <?php echo $filtroModelo === 'CNARQ' ? 'selected' : ''; ?>>CNARQ (Arqueação)</option>
                    <option value="LP" <?php echo $filtroModelo === 'LP' ? 'selected' : ''; ?>>LP (Lic. Provisória)</option>
                    <option value="LC" <?php echo $filtroModelo === 'LC' ? 'selected' : ''; ?>>LC (Lic. Construção)</option>
                    <option value="CHT" <?php echo $filtroModelo === 'CHT' ? 'selected' : ''; ?>>CHT (Homologação)</option>
                </select>
                <small class="text-muted">NORMAM-201 / NORMAM-202</small>
            </div>

            <!-- Ordenação -->
            <div class="venc-select-group">
                <label for="selectOrdem"><i class="fas fa-arrow-down-short-wide"></i> Ordenar Por</label>
                <select id="selectOrdem" name="ordem" class="form-control" onchange="this.form.submit()">
                    <option value="validade_asc" <?php echo $ordem === 'validade_asc' ? 'selected' : ''; ?>>Validade (Mais urgentes)</option>
                    <option value="validade_desc" <?php echo $ordem === 'validade_desc' ? 'selected' : ''; ?>>Validade (Mais distantes)</option>
                    <option value="embarcacao_asc" <?php echo $ordem === 'embarcacao_asc' ? 'selected' : ''; ?>>Embarcação (A-Z)</option>
                    <option value="cliente_asc" <?php echo $ordem === 'cliente_asc' ? 'selected' : ''; ?>>Cliente (A-Z)</option>
                </select>
                <small class="text-muted">Critério de visualização</small>
            </div>

            <!-- Itens por Página -->
            <div class="venc-select-group" style="min-width: 100px;">
                <label for="selectPorPagina">Itens</label>
                <select id="selectPorPagina" name="por_pagina" class="form-control" onchange="this.form.submit()">
                    <option value="15" <?php echo $porPagina === 15 ? 'selected' : ''; ?>>15</option>
                    <option value="25" <?php echo $porPagina === 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $porPagina === 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $porPagina === 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>

            <!-- Botões de Ação -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" title="Filtrar resultados">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <?php if ($busca !== '' || $filtroModelo !== 'TODOS' || $filtroPrazo !== 'padrao'): ?>
                    <a href="<?php echo APP_URL; ?>certificados/vencimentos" class="btn btn-outline-secondary" title="Limpar filtros">
                        <i class="fas fa-eraser"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Certificados com Ações Diretas -->
    <div class="venc-table-card">
        <div class="venc-table-header">
            <div class="venc-table-header-info">
                <strong><?php echo number_format($totalFiltrados, 0, ',', '.'); ?></strong> certificados encontrados
                <?php if ($busca !== ''): ?>
                    para a busca <em>"<?php echo h($busca); ?>"</em>
                <?php endif; ?>
            </div>

            <div>
                <small class="text-muted">
                    <i class="fas fa-circle-info"></i> Clique em <strong>Avisar</strong> para abrir WhatsApp ou copiar a mensagem NORMAM.
                </small>
            </div>
        </div>

        <div class="venc-table-responsive">
            <table class="venc-table table">
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>Nº Certificado</th>
                        <th>Embarcação</th>
                        <th>Cliente / Contato</th>
                        <th>Validade</th>
                        <th>Prazo Restante</th>
                        <th class="text-center" style="min-width: 220px;">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificados)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div style="color: #64748b;">
                                    <i class="fas fa-shield-heart" style="font-size: 2.5rem; margin-bottom: 12px; color: #94a3b8; display: block;"></i>
                                    <h5>Nenhum certificado encontrado para os critérios selecionados</h5>
                                    <p class="text-muted" style="max-width: 480px; margin: 0 auto;">
                                        Tente alterar o filtro de prazo nas abas acima ou utilize o botão <strong>Limpar</strong> para retornar à visão geral.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certificados as $cert): 
                            $dias = (int)$cert['dias_restantes'];
                            $dataValidadeFormatada = !empty($cert['data_validade']) ? date('d/m/Y', strtotime($cert['data_validade'])) : 'N/D';
                            $dataEmissaoFormatada = !empty($cert['data_emissao']) ? date('d/m/Y', strtotime($cert['data_emissao'])) : '-';

                            // Badges do Modelo
                            $badgeModeloClass = match ($cert['modelo']) {
                                'CSN'   => 'badge-doc-csn',
                                'CNBL'  => 'badge-doc-cnbl',
                                'CNARQ' => 'badge-doc-cnarq',
                                'LP'    => 'badge-doc-lp',
                                'LC'    => 'badge-doc-lc',
                                'CHT'   => 'badge-doc-cht',
                                default => 'badge-secondary'
                            };

                            // Urgência e Situação Temporal
                            if ($dias < 0) {
                                $urgenciaClass = 'urgency-vencido';
                                $urgenciaIcon = 'fa-triangle-exclamation';
                                $urgenciaTexto = 'Vencido há ' . abs($dias) . ' dia' . (abs($dias) > 1 ? 's' : '');
                                $statusTitulo = 'Crítico: Vencido';
                            } elseif ($dias === 0) {
                                $urgenciaClass = 'urgency-vencido';
                                $urgenciaIcon = 'fa-triangle-exclamation';
                                $urgenciaTexto = 'Vence HOJE';
                                $statusTitulo = 'Crítico: Vence Hoje';
                            } elseif ($dias <= 15) {
                                $urgenciaClass = 'urgency-critico';
                                $urgenciaIcon = 'fa-bell';
                                $urgenciaTexto = 'Vence em ' . $dias . ' dia' . ($dias > 1 ? 's' : '');
                                $statusTitulo = 'Urgente: Até 15 dias';
                            } elseif ($dias <= 30) {
                                $urgenciaClass = 'urgency-urgente';
                                $urgenciaIcon = 'fa-calendar-xmark';
                                $urgenciaTexto = 'Vence em ' . $dias . ' dias';
                                $statusTitulo = 'Atenção: Até 30 dias';
                            } elseif ($dias <= 60) {
                                $urgenciaClass = 'urgency-atencao';
                                $urgenciaIcon = 'fa-clock';
                                $urgenciaTexto = 'Vence em ' . $dias . ' dias';
                                $statusTitulo = 'Programar: Até 60 dias';
                            } elseif ($dias <= 90) {
                                $urgenciaClass = 'urgency-plano';
                                $urgenciaIcon = 'fa-calendar';
                                $urgenciaTexto = 'Vence em ' . $dias . ' dias';
                                $statusTitulo = 'Planejamento: Até 90 dias';
                            } else {
                                $urgenciaClass = 'urgency-vigente';
                                $urgenciaIcon = 'fa-shield-check';
                                $urgenciaTexto = 'Vigente (' . $dias . ' dias)';
                                $statusTitulo = 'Em dia';
                            }

                            // Tratamento do Telefone / WhatsApp
                            $telLimpo = preg_replace('/\D/', '', $cert['cliente_telefone'] ?? '');
                            $temTelefone = false;
                            $linkWhats = '#';
                            $whatsFormatado = '';
                            if (strlen($telLimpo) >= 10) {
                                $temTelefone = true;
                                if (!str_starts_with($telLimpo, '55') && (strlen($telLimpo) === 10 || strlen($telLimpo) === 11)) {
                                    $whatsFormatado = '55' . $telLimpo;
                                } else {
                                    $whatsFormatado = $telLimpo;
                                }
                            }

                            // Mensagem Padrão Naval NORMAM para WhatsApp / Cópia
                            $nomeCliente = trim($cert['cliente_nome'] ?? 'Cliente');
                            $primeiroNome = trim(explode(' ', $nomeCliente)[0]);
                            $nomeEmbarcacao = trim($cert['nome_embarcacao']);
                            $numInscricao = trim($cert['numero_inscricao']);
                            $modeloCert = $cert['modelo'];
                            $numCert = $cert['numero_documento'];

                            if ($dias < 0) {
                                $prazoMsg = "encontra-se *VENCIDO desde {$dataValidadeFormatada}* (há " . abs($dias) . " dias)";
                                $chamadaAcao = "Para evitar impedimento de despacho, autos de infração e retenção perante a Capitania dos Portos (NORMAM), precisamos regularizar a vistoria da embarcação com urgência.";
                            } elseif ($dias === 0) {
                                $prazoMsg = "*VENCE HOJE ({$dataValidadeFormatada})*";
                                $chamadaAcao = "Para manter a regularidade na Capitania dos Portos (NORMAM) e a segurança da navegação, precisamos emitir o agendamento de vistoria imediatamente.";
                            } else {
                                $prazoMsg = "está próximo do vencimento em *{$dataValidadeFormatada}* (restam *{$dias} dias*)";
                                $chamadaAcao = "Para manter a regularidade perante a Capitania dos Portos (NORMAM) e garantir a segurança da navegação, gostaríamos de programar a vistoria técnica de renovação.";
                            }

                            $textoMensagem = "Prezado(a) *{$primeiroNome}*, tudo bem?\n\nEntramos em contato da equipe técnica da *Amazon Certificadora Naval* para informar que o certificado *{$modeloCert} nº {$numCert}* da sua embarcação *{$nomeEmbarcacao}* (Inscrição: *{$numInscricao}*) {$prazoMsg}.\n\n{$chamadaAcao}\n\nPodemos emitir a proposta de renovação e reservar a data da vistoria? Como prefere proceder?";

                            if ($temTelefone) {
                                $linkWhats = "https://api.whatsapp.com/send?phone={$whatsFormatado}&text=" . rawurlencode($textoMensagem);
                            }

                            // Links operacionais
                            $linkProposta = APP_URL . 'comercial/nova?cliente_id=' . urlencode((string)$cert['cliente_id']) . '&embarcacao_id=' . urlencode((string)$cert['embarcacao_id']);
                            $linkAgendamento = APP_URL . 'agendamentos/form?cliente_id=' . urlencode((string)$cert['cliente_id']) . '&embarcacao_id=' . urlencode((string)$cert['embarcacao_id']) . '&tipo_vistoria=' . urlencode("Renovação de {$modeloCert} - " . $nomeEmbarcacao);
                            $linkPdf = APP_URL . $cert['rota_pdf'] . '?id=' . urlencode((string)$cert['id']);
                        ?>
                            <tr>
                                <!-- Modelo -->
                                <td>
                                    <span class="badge <?php echo $badgeModeloClass; ?>" style="font-size: 0.82rem; padding: 5px 9px;">
                                        <?php echo h($cert['modelo']); ?>
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.72rem; margin-top: 3px;">
                                        <?php echo h($cert['referencia_normam']); ?>
                                    </small>
                                </td>

                                <!-- Nº Certificado -->
                                <td>
                                    <strong style="color: #0f172a;"><?php echo h($cert['numero_documento']); ?></strong>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                        Emissão: <?php echo $dataEmissaoFormatada; ?>
                                    </small>
                                </td>

                                <!-- Embarcação -->
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;"><?php echo h($cert['nome_embarcacao']); ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-anchor"></i> Inscr: <strong><?php echo h($cert['numero_inscricao']); ?></strong>
                                    </small>
                                </td>

                                <!-- Cliente / Contato -->
                                <td>
                                    <div style="font-weight: 600; color: #334155;">
                                        <?php echo h($cert['cliente_nome'] ?: 'Não vinculado'); ?>
                                    </div>
                                    <?php if (!empty($cert['cliente_telefone'])): ?>
                                        <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;">
                                            <i class="fas fa-phone"></i> <?php echo h($cert['cliente_telefone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cert['cliente_perfil'])): ?>
                                        <span class="badge badge-light" style="font-size: 0.7rem; border: 1px solid #cbd5e1;">
                                            <?php echo ucfirst(h($cert['cliente_perfil'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Validade -->
                                <td>
                                    <div style="font-weight: 700; color: <?php echo $dias < 0 ? '#b91c1c' : '#1e293b'; ?>;">
                                        <?php echo $dataValidadeFormatada; ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        Status: <?php echo ucfirst(h($cert['status'])); ?>
                                    </small>
                                </td>

                                <!-- Urgência / Prazo Restante -->
                                <td>
                                    <div class="venc-urgency-badge <?php echo $urgenciaClass; ?>" title="<?php echo $statusTitulo; ?>">
                                        <i class="fas <?php echo $urgenciaIcon; ?>"></i>
                                        <span><?php echo $urgenciaTexto; ?></span>
                                    </div>
                                </td>

                                <!-- Ações Rápidas -->
                                <td>
                                    <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                        <!-- WhatsApp Direto -->
                                        <?php if ($temTelefone): ?>
                                            <a href="<?php echo $linkWhats; ?>" 
                                               target="_blank" 
                                               class="btn-whatsapp" 
                                               title="Avisar cliente via WhatsApp">
                                                <i class="fab fa-whatsapp"></i> Avisar
                                            </a>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    disabled 
                                                    title="Cliente sem telefone/celular cadastrado">
                                                <i class="fab fa-whatsapp"></i> S/ Tel
                                            </button>
                                        <?php endif; ?>

                                        <!-- Botão Modal de Cópia de Mensagem -->
                                        <button type="button" 
                                                class="btn-copy-msg" 
                                                title="Visualizar e copiar mensagem formatada"
                                                onclick="abrirModalMensagem(<?php echo htmlspecialchars(json_encode([
                                                    'cliente' => $cert['cliente_nome'],
                                                    'telefone' => $cert['cliente_telefone'],
                                                    'embarcacao' => $cert['nome_embarcacao'],
                                                    'documento' => $cert['modelo'] . ' nº ' . $cert['numero_documento'],
                                                    'validade' => $dataValidadeFormatada,
                                                    'dias' => $dias,
                                                    'mensagem' => $textoMensagem,
                                                    'linkWhats' => $linkWhats,
                                                    'temWhats' => $temTelefone
                                                ]), ENT_QUOTES, 'UTF-8'); ?>)">
                                            <i class="fas fa-copy"></i>
                                        </button>

                                        <!-- Gerar Proposta -->
                                        <a href="<?php echo $linkProposta; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Gerar Proposta Comercial de Renovação">
                                            <i class="fas fa-file-signature"></i> Proposta
                                        </a>

                                        <!-- Agendar Vistoria -->
                                        <a href="<?php echo $linkAgendamento; ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Agendar Vistoria Técnica Direta">
                                            <i class="fas fa-calendar-plus"></i> Vistoria
                                        </a>

                                        <!-- Ver PDF Original -->
                                        <a href="<?php echo $linkPdf; ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           title="Visualizar Certificado Emitido (PDF)">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
            <div style="padding: 16px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 0.85rem; color: #64748b;">
                    Mostrando página <strong><?php echo $paginaAtual; ?></strong> de <strong><?php echo $totalPaginas; ?></strong> 
                    (total de <?php echo number_format($totalFiltrados, 0, ',', '.'); ?> certificados)
                </div>

                <ul class="pagination mb-0" style="margin: 0; gap: 4px;">
                    <?php if ($paginaAtual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['pagina' => 1]); ?>" title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['pagina' => $paginaAtual - 1]); ?>" title="Página anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $inicioNav = max(1, $paginaAtual - 2);
                    $fimNav = min($totalPaginas, $paginaAtual + 2);
                    for ($p = $inicioNav; $p <= $fimNav; $p++): ?>
                        <li class="page-item <?php echo $p === $paginaAtual ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildFilterUrl(['pagina' => $p]); ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['pagina' => $paginaAtual + 1]); ?>" title="Próxima página">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['pagina' => $totalPaginas]); ?>" title="Última página">
                                <i class="fas fa-angles-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Visualização e Cópia da Mensagem -->
<div class="venc-modal-backdrop" id="modalMensagemBackdrop" onclick="fecharModalMensagem(event)">
    <div class="venc-modal-box" onclick="event.stopPropagation()">
        <div class="venc-modal-header">
            <h4><i class="fab fa-whatsapp text-success"></i> Notificação Naval ao Cliente</h4>
            <button type="button" class="btn-close" onclick="fecharModalMensagem()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
        <div class="venc-modal-body">
            <div style="margin-bottom: 12px; font-size: 0.85rem; color: #475569;">
                <div>Cliente: <strong id="modalNomeCliente">-</strong></div>
                <div>Embarcação: <strong id="modalNomeEmbarcacao">-</strong></div>
                <div>Documento: <strong id="modalNomeDocumento">-</strong> | Vencimento: <strong id="modalDataValidade">-</strong></div>
            </div>

            <label for="modalTextoMensagem" style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                Texto formatado para envio (WhatsApp / E-mail):
            </label>
            <textarea id="modalTextoMensagem" class="venc-copy-textarea" readonly></textarea>
            <small class="text-muted" style="display: block; margin-top: 6px;">
                Você pode copiar o texto acima e colar em conversas particulares ou clicar em "Enviar WhatsApp".
            </small>
        </div>
        <div class="venc-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalMensagem()">
                Fechar
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="copiarTextoModal()">
                <i class="fas fa-copy"></i> Copiar Mensagem
            </button>
            <a href="#" id="modalBtnAbrirWhats" target="_blank" class="btn-whatsapp" style="font-size: 0.85rem; padding: 6px 12px;">
                <i class="fab fa-whatsapp"></i> Abrir no WhatsApp
            </a>
        </div>
    </div>
</div>

<!-- Toast Notificação -->
<div id="vencToast"><i class="fas fa-check-circle text-success"></i> Mensagem copiada para a área de transferência!</div>

<script>
// Manipulação do Modal de Notificação
function abrirModalMensagem(dados) {
    document.getElementById('modalNomeCliente').textContent = dados.cliente || 'Não identificado';
    document.getElementById('modalNomeEmbarcacao').textContent = dados.embarcacao || '-';
    document.getElementById('modalNomeDocumento').textContent = dados.documento || '-';
    document.getElementById('modalDataValidade').textContent = dados.validade || '-';
    document.getElementById('modalTextoMensagem').value = dados.mensagem || '';
    
    const btnWhats = document.getElementById('modalBtnAbrirWhats');
    if (dados.temWhats && dados.linkWhats && dados.linkWhats !== '#') {
        btnWhats.href = dados.linkWhats;
        btnWhats.style.display = 'inline-flex';
    } else {
        btnWhats.style.display = 'none';
    }

    document.getElementById('modalMensagemBackdrop').classList.add('is-open');
}

function fecharModalMensagem(event) {
    if (!event || event.target === document.getElementById('modalMensagemBackdrop') || !event.target) {
        document.getElementById('modalMensagemBackdrop').classList.remove('is-open');
    }
}

function copiarTextoModal() {
    const textarea = document.getElementById('modalTextoMensagem');
    textarea.select();
    textarea.setSelectionRange(0, 99999);
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textarea.value).then(mostrarToast);
    } else {
        document.execCommand('copy');
        mostrarToast();
    }
}

function mostrarToast() {
    const toast = document.getElementById('vencToast');
    toast.className = 'show';
    setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 2500);
}

// Fechar com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalMensagem();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
