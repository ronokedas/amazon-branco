<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

verificar_sessao();
exigirAcesso('gestao_acessos_portal');

$busca = trim($_GET['busca'] ?? '');
$perfil = in_array($_GET['perfil'] ?? '', ['proprietario', 'despachante'], true) ? $_GET['perfil'] : '';
$selecionadoId = trim($_GET['id'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 12;

$params = [];
$whereBusca = '';
if ($busca !== '') {
    $whereBusca = " AND (c.nome LIKE :busca_nome OR c.email LIKE :busca_email OR c.cpf_cnpj LIKE :busca_doc)";
    $params[':busca_nome'] = '%' . $busca . '%';
    $params[':busca_email'] = '%' . $busca . '%';
    $params[':busca_doc'] = '%' . $busca . '%';
}
$wherePerfil = $perfil ? ' AND c.perfil = :perfil' : " AND c.perfil IN ('proprietario','despachante')";
if ($perfil) $params[':perfil'] = $perfil;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM clientes c WHERE c.status = 'ATIVO' {$wherePerfil} {$whereBusca}");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

/* KPI counts */
$stmtKpi = $pdo->query("
    SELECT
        COUNT(DISTINCT c.id) AS total_clientes,
        COUNT(DISTINCT CASE WHEN a.ativo = 1 THEN c.id END) AS total_ativos,
        COUNT(DISTINCT CASE WHEN a.ativo = 0 THEN c.id END) AS total_bloqueados,
        COUNT(DISTINCT CASE WHEN a.cliente_id IS NULL THEN c.id END) AS total_sem_acesso
    FROM clientes c
    LEFT JOIN cliente_portal_acessos a ON a.cliente_id = c.id
    WHERE c.status = 'ATIVO' AND c.perfil IN ('proprietario','despachante')
");
$kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT c.id, c.nome, c.email, c.cpf_cnpj, c.telefone, c.perfil,
           (
               SELECT COUNT(DISTINCT e.id)
               FROM embarcacoes e
               LEFT JOIN clientes_embarcacoes ce2 ON ce2.embarcacao_id = e.id AND ce2.cliente_id = c.id AND ce2.status = 'ATIVO'
               WHERE e.ativo = 1
                 AND (e.proprietario_id = c.id OR e.cliente_id = c.id OR ce2.cliente_id IS NOT NULL)
           ) AS total_embarcacoes,
           a.login, a.ativo AS portal_ativo,
           a.forcar_troca_senha,
           a.ultimo_login_em,
           a.atualizado_em AS portal_atualizado_em
    FROM clientes c
    LEFT JOIN cliente_portal_acessos a ON a.cliente_id = c.id
    WHERE c.status = 'ATIVO'
      {$wherePerfil}
      {$whereBusca}
    GROUP BY c.id
    ORDER BY c.nome ASC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$proprietarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selecionado = null;
$embarcacoesSelecionado = [];
if ($selecionadoId !== '') {
    $stmtSel = $pdo->prepare("
        SELECT c.*, a.ativo AS portal_ativo, a.forcar_troca_senha, a.ultimo_login_em, a.login AS portal_login
        FROM clientes c
        LEFT JOIN cliente_portal_acessos a ON a.cliente_id = c.id
        WHERE c.id = :id AND c.perfil IN ('proprietario','despachante') AND c.status = 'ATIVO'
        LIMIT 1
    ");
    $stmtSel->execute([':id' => $selecionadoId]);
    $selecionado = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($selecionado) {
        $embarcacoesSelecionado = clientePortalEmbarcacoes($pdo, $selecionado['id'], $selecionado['perfil']);
    }
}

$titulo_page = 'Gestão de Acessos ao Portal - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* ========== GESTÃO ACESSOS PORTAL – REDESIGN v2 (Light Theme) ========== */
/* Scoped overrides: neutralize old dark-themed portal-admin styles */

.gap-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 0;
}

/* Header */
.gap-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.gap-header-left h2 {
    font-size: 22px;
    font-weight: 700;
    color: var(--erp-text);
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.gap-header-left h2 i {
    color: var(--erp-green-600);
    font-size: 20px;
}
.gap-header-left p {
    margin: 0;
    font-size: 13px;
    color: var(--erp-text-muted);
}
.gap-header-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* KPI Strip */
.gap-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
.gap-kpi {
    background: var(--erp-surface);
    border: 1px solid var(--erp-border);
    border-radius: var(--erp-radius);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow .2s, border-color .2s;
    cursor: default;
}
.gap-kpi:hover {
    box-shadow: var(--erp-shadow);
    border-color: var(--erp-border-strong);
}
.gap-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 18px;
    flex-shrink: 0;
}
.gap-kpi-icon.total { background: #edf6ff; color: #2879c8; }
.gap-kpi-icon.ativo { background: #e8f8f2; color: #14875f; }
.gap-kpi-icon.bloqueado { background: #fff0f0; color: #dc3f43; }
.gap-kpi-icon.pendente { background: #fff6e8; color: #c57913; }
.gap-kpi-data { min-width: 0; }
.gap-kpi-data .gap-kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--erp-text);
    line-height: 1.1;
}
.gap-kpi-data .gap-kpi-label {
    font-size: 12px;
    color: var(--erp-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 2px;
}

/* Search & Filter Bar */
.gap-filters {
    background: var(--erp-surface);
    border: 1px solid var(--erp-border);
    border-radius: var(--erp-radius);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.gap-search-wrap {
    flex: 1;
    min-width: 260px;
    position: relative;
}
.gap-search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--erp-text-muted);
    font-size: 14px;
    pointer-events: none;
}
.gap-search-wrap input {
    width: 100%;
    height: var(--erp-control-h);
    padding: 0 14px 0 40px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    font-size: 14px;
    color: var(--erp-text);
    background: var(--erp-surface-subtle);
    transition: border-color .2s, box-shadow .2s;
}
.gap-search-wrap input:focus {
    outline: none;
    border-color: var(--erp-green-600);
    box-shadow: 0 0 0 3px rgba(11, 155, 112, .12);
}
.gap-filters select {
    height: var(--erp-control-h);
    padding: 0 32px 0 14px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    font-size: 13px;
    color: var(--erp-text);
    background: var(--erp-surface-subtle);
    min-width: 180px;
    cursor: pointer;
    appearance: auto;
}
.gap-filters .gap-btn-search {
    height: var(--erp-control-h);
    padding: 0 20px;
    border: none;
    border-radius: var(--radius-btn);
    background: var(--erp-green-600);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background .2s, box-shadow .2s;
}
.gap-filters .gap-btn-search:hover {
    background: var(--erp-green-700);
    box-shadow: 0 2px 8px rgba(11, 155, 112, .18);
}
.gap-filters .gap-btn-clear {
    height: var(--erp-control-h);
    padding: 0 16px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    background: var(--erp-surface);
    color: var(--erp-text-muted);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: background .15s, border-color .15s;
}
.gap-filters .gap-btn-clear:hover {
    background: var(--erp-surface-subtle);
    border-color: var(--erp-border-strong);
    color: var(--erp-text);
}

/* Layout */
.gap-body {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
    align-items: start;
}

/* Table */
.gap-table-wrap {
    background: var(--erp-surface);
    border: 1px solid var(--erp-border);
    border-radius: var(--erp-radius);
    overflow: hidden;
}
.gap-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.gap-table thead th {
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--erp-text-muted);
    background: var(--erp-surface-subtle);
    border-bottom: 1px solid var(--erp-border);
    white-space: nowrap;
    text-align: left;
}
.gap-table tbody tr {
    border-bottom: 1px solid var(--erp-border);
    transition: background .15s;
    cursor: default;
}
.gap-table tbody tr:last-child { border-bottom: none; }
.gap-table tbody tr:hover { background: var(--erp-surface-subtle); }
.gap-table tbody tr.gap-selected {
    background: rgba(11, 155, 112, .06);
    border-left: 3px solid var(--erp-green-600);
}
.gap-table tbody td {
    padding: 14px 16px;
    font-size: 14px;
    color: var(--erp-text);
    vertical-align: middle;
    white-space: normal;
    word-break: break-word;
}
.gap-table .gap-col-nome { width: 30%; }
.gap-table .gap-col-perfil { width: 12%; text-align: center; }
.gap-table .gap-col-email { width: 24%; }
.gap-table .gap-col-barcos { width: 10%; text-align: center; }
.gap-table .gap-col-status { width: 14%; text-align: center; }
.gap-table .gap-col-acoes { width: 10%; text-align: center; }

.gap-nome-cell strong {
    display: block;
    font-size: 14px;
    color: var(--erp-text);
    font-weight: 600;
}
.gap-nome-cell .gap-doc {
    font-size: 12px;
    color: var(--erp-text-muted);
    margin-top: 2px;
}

/* Badges / Pills */
.gap-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: .02em;
}
.gap-badge i { font-size: 10px; }
.gap-badge-proprietario { background: #edf6ff; color: #2879c8; }
.gap-badge-despachante { background: #f3edff; color: #7c3aed; }
.gap-badge-ativo { background: #e8f8f2; color: #14875f; }
.gap-badge-bloqueado { background: #fff0f0; color: #dc3f43; }
.gap-badge-nao-criado { background: #f5f5f5; color: #888; }
.gap-badge-barcos {
    background: var(--erp-surface-subtle);
    color: var(--erp-text);
    font-weight: 800;
    min-width: 28px;
    justify-content: center;
}

/* Action buttons */
.gap-btn-manage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid var(--erp-border);
    border-radius: 9px;
    background: var(--erp-surface);
    color: var(--erp-green-600);
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
}
.gap-btn-manage:hover {
    background: var(--erp-green-600);
    color: #fff;
    border-color: var(--erp-green-600);
    box-shadow: 0 2px 8px rgba(11, 155, 112, .2);
}
.gap-btn-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid var(--erp-border);
    border-radius: 9px;
    background: var(--erp-surface);
    font-size: 14px;
    cursor: pointer;
    transition: all .2s;
}
.gap-btn-toggle.lock { color: var(--erp-danger); }
.gap-btn-toggle.lock:hover { background: #fff0f0; border-color: var(--erp-danger); }
.gap-btn-toggle.unlock { color: var(--erp-success); }
.gap-btn-toggle.unlock:hover { background: #e8f8f2; border-color: var(--erp-success); }

.gap-actions-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

/* Empty State */
.gap-empty-row td {
    padding: 48px 16px !important;
    text-align: center !important;
    color: var(--erp-text-muted);
    font-size: 14px;
}
.gap-empty-row i {
    display: block;
    font-size: 32px;
    color: var(--erp-border-strong);
    margin-bottom: 10px;
}

/* Pagination */
.gap-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid var(--erp-border);
    background: var(--erp-surface-subtle);
    font-size: 13px;
    color: var(--erp-text-muted);
}
.gap-pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}
.gap-pagination-controls a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    background: var(--erp-surface);
    color: var(--erp-text);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .15s;
}
.gap-pagination-controls a:hover {
    border-color: var(--erp-green-600);
    color: var(--erp-green-600);
}
.gap-pagination-controls strong {
    font-size: 13px;
    color: var(--erp-text);
    padding: 0 6px;
}

/* ========== Detail Panel ========== */
.gap-detail {
    background: var(--erp-surface);
    border: 1px solid var(--erp-border);
    border-radius: var(--erp-radius);
    padding: 0;
    position: sticky;
    top: calc(var(--topbar-h) + 18px);
    overflow: hidden;
}

/* Empty detail */
.gap-detail-empty {
    padding: 48px 24px;
    text-align: center;
    color: var(--erp-text-muted);
}
.gap-detail-empty i {
    font-size: 40px;
    color: var(--erp-border-strong);
    margin-bottom: 14px;
    display: block;
}
.gap-detail-empty p {
    margin: 0;
    font-size: 13.5px;
    line-height: 1.6;
    color: var(--erp-text-muted);
}

/* Detail header */
.gap-detail-header {
    padding: 20px 22px;
    border-bottom: 1px solid var(--erp-border);
    background: var(--erp-surface-subtle);
}
.gap-detail-header h4 {
    margin: 0 0 4px;
    font-size: 17px;
    font-weight: 700;
    color: var(--erp-text);
}
.gap-detail-header .gap-detail-email {
    font-size: 13px;
    color: var(--erp-text-muted);
    margin: 0;
    word-break: break-all;
}

/* Detail metrics row */
.gap-detail-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-bottom: 1px solid var(--erp-border);
}
.gap-detail-metric {
    padding: 14px 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.gap-detail-metric:first-child {
    border-right: 1px solid var(--erp-border);
}
.gap-detail-metric i {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 14px;
    flex-shrink: 0;
}
.gap-detail-metric i.fa-ship { background: #edf6ff; color: #2879c8; }
.gap-detail-metric i.fa-clock { background: #fff6e8; color: #c57913; }
.gap-detail-metric-data strong {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: var(--erp-text);
    line-height: 1.2;
}
.gap-detail-metric-data small {
    font-size: 11px;
    color: var(--erp-text-muted);
    text-transform: uppercase;
    letter-spacing: .03em;
    font-weight: 600;
}

/* Detail body content */
.gap-detail-body {
    padding: 18px 22px;
}

/* Status panel */
.gap-status-panel {
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 12px;
}
.gap-status-panel.ativo {
    background: #e8f8f2;
    border: 1px solid #b8e8d5;
}
.gap-status-panel.bloqueado {
    background: #fff0f0;
    border: 1px solid #fbc8c8;
}
.gap-status-panel.nao-criado {
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
}
.gap-status-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 13.5px;
}
.gap-status-label.ativo { color: #14875f; }
.gap-status-label.bloqueado { color: #dc3f43; }
.gap-status-label.nao-criado { color: #888; }

.gap-status-panel .gap-status-btn {
    padding: 7px 16px;
    border-radius: var(--radius-btn);
    font-size: 12.5px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: opacity .15s, box-shadow .15s;
}
.gap-status-panel .gap-status-btn:hover { opacity: .88; }
.gap-status-btn.danger { background: #dc3f43; color: #fff; }
.gap-status-btn.success { background: #14875f; color: #fff; }

/* Boats section */
.gap-section-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--erp-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 8px;
}
.gap-boats-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 18px;
}
.gap-boat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    background: var(--erp-surface-subtle);
    border: 1px solid var(--erp-border);
    color: var(--erp-text);
}
.gap-boat-chip i { color: var(--erp-green-600); font-size: 11px; }
.gap-no-boats {
    font-size: 12.5px;
    color: var(--erp-text-muted);
    font-style: italic;
    margin-bottom: 18px;
}

/* Credentials section */
.gap-divider {
    height: 1px;
    background: var(--erp-border);
    margin: 0 0 18px;
    border: 0;
}
.gap-cred-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: var(--erp-text);
    margin: 0 0 14px;
}
.gap-cred-title i {
    color: var(--erp-green-600);
    font-size: 15px;
}
.gap-form-group {
    margin-bottom: 14px;
}
.gap-form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--erp-text);
    margin-bottom: 5px;
}
.gap-password-row {
    display: flex;
    gap: 8px;
}
.gap-password-row input {
    flex: 1;
    height: var(--erp-control-h);
    padding: 0 14px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    font-size: 14px;
    font-family: 'SF Mono', 'Consolas', monospace;
    font-weight: 700;
    letter-spacing: .08em;
    color: var(--erp-text);
    background: var(--erp-surface-subtle);
}
.gap-password-row input:focus {
    outline: none;
    border-color: var(--erp-green-600);
    box-shadow: 0 0 0 3px rgba(11, 155, 112, .12);
}
.gap-btn-generate {
    height: var(--erp-control-h);
    padding: 0 14px;
    border: 1px solid var(--erp-border);
    border-radius: var(--radius-btn);
    background: var(--erp-surface);
    color: var(--erp-text-muted);
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    white-space: nowrap;
    font-weight: 600;
}
.gap-btn-generate:hover {
    background: var(--erp-surface-subtle);
    border-color: var(--erp-green-600);
    color: var(--erp-green-600);
}
.gap-form-hint {
    font-size: 11.5px;
    color: var(--erp-text-muted);
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.gap-form-hint i { color: var(--erp-warning); font-size: 11px; }

.gap-btn-submit {
    width: 100%;
    height: 46px;
    border: none;
    border-radius: var(--radius-btn);
    background: var(--erp-green-600);
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background .2s, box-shadow .2s;
    margin-top: 4px;
}
.gap-btn-submit:hover {
    background: var(--erp-green-700);
    box-shadow: 0 4px 14px rgba(11, 155, 112, .22);
}
.gap-btn-submit:disabled {
    background: var(--erp-border);
    color: var(--erp-text-muted);
    cursor: not-allowed;
    box-shadow: none;
}

/* Responsive */
@media (max-width: 1400px) {
    .gap-body {
        grid-template-columns: 1fr;
    }
    .gap-detail {
        position: static;
    }
}
@media (max-width: 900px) {
    .gap-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .gap-kpis {
        grid-template-columns: 1fr;
    }
    .gap-filters {
        flex-direction: column;
        align-items: stretch;
    }
    .gap-search-wrap { min-width: 0; }
}

/* Hide old portal-admin-layout / detail styles when inside the new page */
.conteudo-principal:has(.gap-page) .portal-admin-layout,
.conteudo-principal:has(.gap-page) .portal-admin-detail,
.conteudo-principal:has(.gap-page) .portal-admin-table-container,
.conteudo-principal:has(.gap-page) .portal-admin-table,
.conteudo-principal:has(.gap-page) .portal-empty {
    all: unset;
}

/* Mobile table card mode */
@media (max-width: 900px) {
    .gap-table-wrap { overflow-x: auto; }
    .gap-table { min-width: 700px; }
}
</style>

<div class="conteudo-principal">
    <div class="gap-page">

        <!-- Header -->
        <div class="gap-header">
            <div class="gap-header-left">
                <h2><i class="fas fa-user-shield"></i> Gestão de Acessos ao Portal</h2>
                <p>Controle de logins, senhas, liberações e bloqueios de acesso para clientes e despachantes.</p>
            </div>
            <div class="gap-header-right">
                <a href="<?php echo APP_URL; ?>portal/login" target="_blank" class="gap-btn-clear" style="text-decoration:none">
                    <i class="fas fa-arrow-up-right-from-square"></i> Abrir Portal
                </a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="gap-kpis">
            <div class="gap-kpi">
                <div class="gap-kpi-icon total"><i class="fas fa-users"></i></div>
                <div class="gap-kpi-data">
                    <div class="gap-kpi-value"><?php echo (int)$kpi['total_clientes']; ?></div>
                    <div class="gap-kpi-label">Total de Clientes</div>
                </div>
            </div>
            <div class="gap-kpi">
                <div class="gap-kpi-icon ativo"><i class="fas fa-check-circle"></i></div>
                <div class="gap-kpi-data">
                    <div class="gap-kpi-value"><?php echo (int)$kpi['total_ativos']; ?></div>
                    <div class="gap-kpi-label">Acessos Ativos</div>
                </div>
            </div>
            <div class="gap-kpi">
                <div class="gap-kpi-icon bloqueado"><i class="fas fa-ban"></i></div>
                <div class="gap-kpi-data">
                    <div class="gap-kpi-value"><?php echo (int)$kpi['total_bloqueados']; ?></div>
                    <div class="gap-kpi-label">Bloqueados</div>
                </div>
            </div>
            <div class="gap-kpi">
                <div class="gap-kpi-icon pendente"><i class="fas fa-clock"></i></div>
                <div class="gap-kpi-data">
                    <div class="gap-kpi-value"><?php echo (int)$kpi['total_sem_acesso']; ?></div>
                    <div class="gap-kpi-label">Sem Acesso</div>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <form method="GET" class="gap-filters">
            <div class="gap-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="busca" value="<?php echo h($busca); ?>" placeholder="Buscar por nome, e-mail ou CPF/CNPJ...">
            </div>
            <select name="perfil">
                <option value="">Todos os perfis</option>
                <option value="proprietario" <?php echo $perfil === 'proprietario' ? 'selected' : ''; ?>>Proprietários / Armadores</option>
                <option value="despachante" <?php echo $perfil === 'despachante' ? 'selected' : ''; ?>>Despachantes</option>
            </select>
            <button type="submit" class="gap-btn-search"><i class="fas fa-search"></i> Buscar</button>
            <a class="gap-btn-clear" href="<?php echo APP_URL; ?>gestao-acessos-portal"><i class="fas fa-rotate-left"></i> Limpar</a>
        </form>

        <!-- Body: Table + Detail -->
        <div class="gap-body">

            <!-- Table -->
            <div class="gap-table-wrap">
                <table class="gap-table">
                    <colgroup>
                        <col class="gap-col-nome">
                        <col class="gap-col-perfil">
                        <col class="gap-col-email">
                        <col class="gap-col-barcos">
                        <col class="gap-col-status">
                        <col class="gap-col-acoes">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Cliente / Razão</th>
                            <th style="text-align:center">Perfil</th>
                            <th>E-mail</th>
                            <th style="text-align:center">Barcos</th>
                            <th style="text-align:center">Status</th>
                            <th style="text-align:center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($proprietarios)): ?>
                        <tr class="gap-empty-row">
                            <td colspan="6">
                                <i class="fas fa-inbox"></i>
                                Nenhum cliente encontrado com os filtros informados.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($proprietarios as $p): ?>
                        <tr class="<?php echo $selecionadoId === $p['id'] ? 'gap-selected' : ''; ?>">
                            <td>
                                <div class="gap-nome-cell">
                                    <strong><?php echo h($p['nome']); ?></strong>
                                    <?php if ($p['cpf_cnpj']): ?>
                                        <div class="gap-doc"><?php echo h($p['cpf_cnpj']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <span class="gap-badge gap-badge-<?php echo h($p['perfil']); ?>">
                                    <?php echo h(ucfirst($p['perfil'])); ?>
                                </span>
                            </td>
                            <td style="font-size:13px; color: var(--erp-text-muted);">
                                <?php echo h($p['email'] ?: '—'); ?>
                            </td>
                            <td style="text-align:center">
                                <span class="gap-badge gap-badge-barcos">
                                    <i class="fas fa-ship" style="font-size:10px; color: var(--erp-green-600);"></i>
                                    <?php echo (int)$p['total_embarcacoes']; ?>
                                </span>
                            </td>
                            <td style="text-align:center">
                                <?php if ($p['portal_ativo'] === null): ?>
                                    <span class="gap-badge gap-badge-nao-criado"><i class="fas fa-minus-circle"></i> Pendente</span>
                                <?php elseif ((int)$p['portal_ativo'] === 1): ?>
                                    <span class="gap-badge gap-badge-ativo"><i class="fas fa-check-circle"></i> Ativo</span>
                                <?php else: ?>
                                    <span class="gap-badge gap-badge-bloqueado"><i class="fas fa-ban"></i> Bloqueado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="gap-actions-cell">
                                    <a class="gap-btn-manage" title="Gerenciar acesso de <?php echo h($p['nome']); ?>"
                                       href="<?php echo APP_URL; ?>gestao-acessos-portal?id=<?php echo urlencode($p['id']); ?>&busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina; ?>">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <?php if ($p['portal_ativo'] !== null): ?>
                                        <form method="POST" action="<?php echo APP_URL; ?>gestao-acessos-portal/actions" class="d-inline" onsubmit="return confirm('<?php echo (int)$p['portal_ativo'] === 1 ? 'Deseja realmente BLOQUEAR o acesso deste cliente?' : 'Deseja LIBERAR o acesso deste cliente?'; ?>')">
                                            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                            <input type="hidden" name="action" value="alternar_status">
                                            <input type="hidden" name="cliente_id" value="<?php echo h($p['id']); ?>">
                                            <input type="hidden" name="novo_status" value="<?php echo (int)$p['portal_ativo'] === 1 ? '0' : '1'; ?>">
                                            <?php if ((int)$p['portal_ativo'] === 1): ?>
                                                <button type="submit" class="gap-btn-toggle lock" title="Bloquear acesso">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="gap-btn-toggle unlock" title="Liberar acesso">
                                                    <i class="fas fa-lock-open"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPaginas > 1): ?>
                    <div class="gap-pagination">
                        <span><?php echo $totalRegistros; ?> clientes encontrados</span>
                        <div class="gap-pagination-controls">
                            <?php if ($pagina > 1): ?>
                                <a href="?busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina - 1; ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            <strong>Pág. <?php echo $pagina; ?> / <?php echo $totalPaginas; ?></strong>
                            <?php if ($pagina < $totalPaginas): ?>
                                <a href="?busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina + 1; ?>">
                                    Próxima <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Detail Panel -->
            <div class="gap-detail" data-testid="portal-clientes-detalhe">
                <?php if (!$selecionado): ?>
                    <div class="gap-detail-empty">
                        <i class="fas fa-user-gear"></i>
                        <p>Selecione um cliente ou despachante na lista ao lado para gerenciar credenciais, liberar ou bloquear o acesso ao portal.</p>
                    </div>
                <?php else: ?>
                    <!-- Header -->
                    <div class="gap-detail-header">
                        <h4><?php echo h($selecionado['nome']); ?></h4>
                        <p class="gap-detail-email"><?php echo h($selecionado['email'] ?: 'Sem e-mail cadastrado'); ?></p>
                    </div>

                    <!-- Metrics -->
                    <div class="gap-detail-metrics">
                        <div class="gap-detail-metric">
                            <i class="fas fa-ship"></i>
                            <div class="gap-detail-metric-data">
                                <strong><?php echo count($embarcacoesSelecionado); ?></strong>
                                <small>Embarcações</small>
                            </div>
                        </div>
                        <div class="gap-detail-metric">
                            <i class="fas fa-clock"></i>
                            <div class="gap-detail-metric-data">
                                <strong><?php echo !empty($selecionado['ultimo_login_em']) ? date('d/m/y H:i', strtotime($selecionado['ultimo_login_em'])) : 'Nunca'; ?></strong>
                                <small>Último acesso</small>
                            </div>
                        </div>
                    </div>

                    <div class="gap-detail-body">
                        <!-- Status -->
                        <?php if ($selecionado['portal_ativo'] === null): ?>
                            <div class="gap-status-panel nao-criado">
                                <span class="gap-status-label nao-criado"><i class="fas fa-minus-circle"></i> Nenhum acesso criado</span>
                            </div>
                        <?php elseif ((int)$selecionado['portal_ativo'] === 1): ?>
                            <div class="gap-status-panel ativo">
                                <span class="gap-status-label ativo"><i class="fas fa-check-circle"></i> Acesso Liberado</span>
                                <form method="POST" action="<?php echo APP_URL; ?>gestao-acessos-portal/actions" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                    <input type="hidden" name="action" value="alternar_status">
                                    <input type="hidden" name="cliente_id" value="<?php echo h($selecionado['id']); ?>">
                                    <input type="hidden" name="novo_status" value="0">
                                    <button type="submit" class="gap-status-btn danger" onclick="return confirm('Confirma o bloqueio imediato do acesso deste cliente?')">
                                        <i class="fas fa-ban"></i> Bloquear
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="gap-status-panel bloqueado">
                                <span class="gap-status-label bloqueado"><i class="fas fa-lock"></i> Acesso Bloqueado</span>
                                <form method="POST" action="<?php echo APP_URL; ?>gestao-acessos-portal/actions" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                    <input type="hidden" name="action" value="alternar_status">
                                    <input type="hidden" name="cliente_id" value="<?php echo h($selecionado['id']); ?>">
                                    <input type="hidden" name="novo_status" value="1">
                                    <button type="submit" class="gap-status-btn success">
                                        <i class="fas fa-lock-open"></i> Liberar
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Boats -->
                        <div class="gap-section-label">Embarcações Vinculadas</div>
                        <?php if (empty($embarcacoesSelecionado)): ?>
                            <div class="gap-no-boats">Nenhuma embarcação ativa vinculada.</div>
                        <?php else: ?>
                            <div class="gap-boats-list">
                                <?php foreach ($embarcacoesSelecionado as $emb): ?>
                                    <span class="gap-boat-chip"><i class="fas fa-ship"></i> <?php echo h($emb['nome']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Credentials Form -->
                        <hr class="gap-divider">
                        <h5 class="gap-cred-title">
                            <i class="fas fa-key"></i>
                            <?php echo $selecionado['portal_ativo'] === null ? 'Criar Acesso ao Portal' : 'Redefinir / Enviar Nova Senha'; ?>
                        </h5>

                        <form method="POST" action="<?php echo APP_URL; ?>gestao-acessos-portal/actions">
                            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                            <input type="hidden" name="action" value="enviar_acesso">
                            <input type="hidden" name="cliente_id" value="<?php echo h($selecionado['id']); ?>">

                            <div class="gap-form-group">
                                <label for="senha_temporaria">Senha temporária</label>
                                <div class="gap-password-row">
                                    <input type="text" id="senha_temporaria" name="senha_temporaria" value="<?php echo h(clientePortalGerarSenhaFacil()); ?>" minlength="8" maxlength="20" required>
                                    <button class="gap-btn-generate" type="button" onclick="gerarSenhaPortal()">
                                        <i class="fas fa-wand-magic-sparkles"></i> Gerar
                                    </button>
                                </div>
                                <div class="gap-form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    O cliente será obrigado a trocar essa senha no primeiro acesso.
                                </div>
                            </div>

                            <button type="submit" class="gap-btn-submit" <?php echo empty($selecionado['email']) ? 'disabled' : ''; ?>>
                                <i class="fas fa-envelope"></i>
                                <?php echo empty($selecionado['email']) ? 'Cadastre o e-mail primeiro' : 'Enviar credenciais por e-mail'; ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
function gerarSenhaPortal() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let senha = '';
    for (let i = 0; i < 8; i++) {
        senha += chars[Math.floor(Math.random() * chars.length)];
    }
    document.getElementById('senha_temporaria').value = senha;
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
