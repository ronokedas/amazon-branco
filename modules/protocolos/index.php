<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
protocoloExigirAcesso();
protocoloProcessarAlertas($pdo);

$aba = trim($_GET['aba'] ?? 'todos');
$f = [
    'busca' => trim($_GET['busca'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'unidade' => trim($_GET['unidade'] ?? ''),
    'cidade' => trim($_GET['cidade'] ?? ''),
    'responsavel' => trim($_GET['responsavel'] ?? ''),
    'inicio' => trim($_GET['inicio'] ?? ''),
    'fim' => trim($_GET['fim'] ?? ''),
    'embarcacao_id' => trim($_GET['embarcacao_id'] ?? '')
];

$where = [];
$p = [];
$uid = (string)($_SESSION['usuario_id'] ?? '');

if (getCargo() !== 'ADMIN') {
    $where[] = '(d.criado_por = :uid1 OR EXISTS(SELECT 1 FROM propostas px WHERE px.id=d.proposta_id AND px.criado_por = :uid2) OR EXISTS(SELECT 1 FROM analises_planos ax WHERE ax.id=d.analise_id AND ax.analista_id = :uid3) OR EXISTS(SELECT 1 FROM vistorias vx JOIN agendamentos gx ON gx.id=vx.agendamento_id WHERE vx.id=d.vistoria_id AND gx.vistoriador_id = :uid4))';
    $p[':uid1'] = $uid;
    $p[':uid2'] = $uid;
    $p[':uid3'] = $uid;
    $p[':uid4'] = $uid;
}

if ($f['busca'] !== '') {
    $where[] = '(d.numero LIKE :b1 OR d.assunto LIKE :b2 OR d.protocolo_externo_numero LIKE :b3 OR e.nome LIKE :b4 OR c.nome LIKE :b5)';
    $termoBusca = '%' . $f['busca'] . '%';
    $p[':b1'] = $termoBusca;
    $p[':b2'] = $termoBusca;
    $p[':b3'] = $termoBusca;
    $p[':b4'] = $termoBusca;
    $p[':b5'] = $termoBusca;
}

$labels = protocoloRotulosStatus();

// Filtro por Aba Rápida
if ($aba === 'EM_PREPARACAO') {
    $where[] = "d.status = 'EM_PREPARACAO'";
} elseif ($aba === 'MARINHA') {
    $where[] = "d.status IN ('ENVIADO_AO_ORGAO', 'PROTOCOLADO', 'EM_ANALISE_NO_ORGAO')";
} elseif ($aba === 'EM_EXIGENCIA') {
    $where[] = "d.status = 'EM_EXIGENCIA'";
} elseif ($aba === 'DISPONIVEL') {
    $where[] = "d.status IN ('A_DISPOSICAO', 'RETIRADO')";
} elseif ($aba === 'CUSTODIA') {
    $where[] = "EXISTS(SELECT 1 FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m2 ON m2.id=i.movimentacao_id WHERE m2.dossie_id=d.id AND i.requer_devolucao=1 AND i.devolvido_em IS NULL)";
} elseif ($aba === 'CONCLUIDO') {
    $where[] = "d.status IN ('ENTREGUE_AO_CLIENTE', 'ENCERRADO')";
} elseif ($f['status'] !== '' && isset($labels[$f['status']])) {
    $where[] = 'd.status=:status';
    $p[':status'] = $f['status'];
}

foreach (['unidade' => 'd.unidade_maritima_id', 'responsavel' => 'd.criado_por', 'embarcacao_id' => 'd.embarcacao_id'] as $k => $col) {
    if ($f[$k] !== '') {
        $where[] = "$col=:$k";
        $p[":$k"] = $f[$k];
    }
}

if ($f['cidade'] !== '') {
    $where[] = 'EXISTS(SELECT 1 FROM protocolo_movimentacoes mc WHERE mc.dossie_id=d.id AND mc.cidade LIKE :cidade)';
    $p[':cidade'] = '%' . $f['cidade'] . '%';
}
if ($f['inicio'] !== '') {
    $where[] = 'DATE(d.criado_em)>=:inicio';
    $p[':inicio'] = $f['inicio'];
}
if ($f['fim'] !== '') {
    $where[] = 'DATE(d.criado_em)<=:fim';
    $p[':fim'] = $f['fim'];
}

$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [5, 10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

// Helper para gerar URLs mantendo filtros e paginação
if (!function_exists('protocoloUrl')) {
    function protocoloUrl(array $overrides = []): string {
        global $aba, $f, $porPagina, $paginaAtual;
        $params = [
            'aba' => ($aba !== '' && $aba !== 'todos') ? $aba : null,
            'busca' => ($f['busca'] ?? '') !== '' ? $f['busca'] : null,
            'status' => ($f['status'] ?? '') !== '' ? $f['status'] : null,
            'unidade' => ($f['unidade'] ?? '') !== '' ? $f['unidade'] : null,
            'cidade' => ($f['cidade'] ?? '') !== '' ? $f['cidade'] : null,
            'responsavel' => ($f['responsavel'] ?? '') !== '' ? $f['responsavel'] : null,
            'inicio' => ($f['inicio'] ?? '') !== '' ? $f['inicio'] : null,
            'fim' => ($f['fim'] ?? '') !== '' ? $f['fim'] : null,
            'embarcacao_id' => ($f['embarcacao_id'] ?? '') !== '' ? $f['embarcacao_id'] : null,
            'por_pagina' => (int)$porPagina !== 15 ? (int)$porPagina : null,
            'pagina' => (int)$paginaAtual > 1 ? (int)$paginaAtual : null,
        ];
        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '' || ($k === 'aba' && $v === 'todos') || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        $qs = http_build_query(array_filter($params, fn($val) => $val !== null && $val !== ''));
        return APP_URL . 'protocolos' . ($qs ? '?' . $qs : '');
    }
}

// Contagem total para paginação com os mesmos filtros
$sqlCount = "SELECT COUNT(*) FROM protocolo_dossies d 
JOIN embarcacoes e ON e.id=d.embarcacao_id 
LEFT JOIN clientes c ON c.id=d.cliente_id 
LEFT JOIN usuarios u ON u.id=d.criado_por 
LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

$qCount = $pdo->prepare($sqlCount);
$qCount->execute($p);
$totalDossies = (int)$qCount->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalDossies / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;

$registroInicio = $totalDossies > 0 ? $offset + 1 : 0;
$registroFim = min($offset + $porPagina, $totalDossies);

$sql = "SELECT d.*, e.nome embarcacao_nome, e.registro embarcacao_registro, c.nome cliente_nome, u.nome responsavel_nome, um.nome unidade_nome,
(SELECT COUNT(*) FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status IN('CONFIRMADA','RETIFICADA')) eventos,
(SELECT COUNT(*) FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m2 ON m2.id=i.movimentacao_id WHERE m2.dossie_id=d.id AND i.requer_devolucao=1 AND i.devolvido_em IS NULL) originais_pendentes
FROM protocolo_dossies d 
JOIN embarcacoes e ON e.id=d.embarcacao_id 
LEFT JOIN clientes c ON c.id=d.cliente_id 
LEFT JOIN usuarios u ON u.id=d.criado_por 
LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY d.atualizado_em DESC LIMIT {$porPagina} OFFSET {$offset}";

$q = $pdo->prepare($sql);
$q->execute($p);
$dossies = $q->fetchAll(PDO::FETCH_ASSOC);

// Totais para Abas e KPIs
$qTotais = $pdo->query("SELECT 
    COUNT(*) total_geral,
    SUM(CASE WHEN d.status = 'EM_PREPARACAO' THEN 1 ELSE 0 END) total_preparacao,
    SUM(CASE WHEN d.status IN ('ENVIADO_AO_ORGAO', 'PROTOCOLADO', 'EM_ANALISE_NO_ORGAO') THEN 1 ELSE 0 END) total_marinha,
    SUM(CASE WHEN d.status = 'EM_EXIGENCIA' THEN 1 ELSE 0 END) total_exigencia,
    SUM(CASE WHEN d.status IN ('A_DISPOSICAO', 'RETIRADO') THEN 1 ELSE 0 END) total_disponivel,
    SUM(CASE WHEN d.status IN ('ENTREGUE_AO_CLIENTE', 'ENCERRADO') THEN 1 ELSE 0 END) total_concluido,
    SUM(CASE WHEN EXISTS(SELECT 1 FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m2 ON m2.id=i.movimentacao_id WHERE m2.dossie_id=d.id AND i.requer_devolucao=1 AND i.devolvido_em IS NULL) THEN 1 ELSE 0 END) total_custodia,
    SUM(CASE WHEN d.protocolo_externo_validade IS NOT NULL AND d.protocolo_externo_validade <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND d.status NOT IN ('ENCERRADO','CANCELADO') THEN 1 ELSE 0 END) total_validade_alerta
FROM protocolo_dossies d");
$totais = $qTotais->fetch(PDO::FETCH_ASSOC) ?: [];

$unidades = $pdo->query('SELECT id,nome,cidade,uf FROM protocolo_unidades_maritimas WHERE ativo=1 ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query('SELECT id,nome FROM usuarios WHERE ativo=1 AND excluido_em IS NULL ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Protocolos documentais - ERP';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>
<style>
/* Ações da Tabela de Protocolos */
.prot-table-actions {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 8px !important;
    white-space: nowrap !important;
}

#tabela-protocolos td:last-child .prot-btn-action,
.prot-table-actions .prot-btn-action {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    height: 36px !important;
    min-height: 36px !important;
    max-height: 36px !important;
    width: auto !important;
    min-width: auto !important;
    padding: 0 14px !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
    text-decoration: none !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
    line-height: 1 !important;
    transition: all 0.18s ease-in-out !important;
    box-shadow: none !important;
}

/* WhatsApp: botão oficial verde */
#tabela-protocolos td:last-child .prot-btn-whatsapp,
.prot-table-actions .prot-btn-whatsapp {
    width: 36px !important;
    min-width: 36px !important;
    max-width: 36px !important;
    height: 36px !important;
    padding: 0 !important;
    background: #25d366 !important;
    border: 1px solid #20ba5a !important;
    color: #ffffff !important;
    font-size: 1.15rem !important;
    box-shadow: 0 2px 6px rgba(37, 211, 102, 0.25) !important;
}
#tabela-protocolos td:last-child .prot-btn-whatsapp:hover,
.prot-table-actions .prot-btn-whatsapp:hover {
    background: #1eb857 !important;
    border-color: #1eb857 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.45) !important;
}

/* PDF Consolidado: botão limpo com ícone clássico de PDF */
#tabela-protocolos td:last-child .prot-btn-pdf,
.prot-table-actions .prot-btn-pdf {
    background: rgba(148, 163, 184, 0.14) !important;
    border: 1px solid rgba(148, 163, 184, 0.35) !important;
    color: var(--text-primary, #334155) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
}
#tabela-protocolos td:last-child .prot-btn-pdf i,
.prot-table-actions .prot-btn-pdf i {
    color: #e11d48 !important;
    font-size: 0.95rem !important;
}
#tabela-protocolos td:last-child .prot-btn-pdf:hover,
.prot-table-actions .prot-btn-pdf:hover {
    background: rgba(148, 163, 184, 0.25) !important;
    border-color: rgba(148, 163, 184, 0.5) !important;
    color: var(--text-primary, #0f172a) !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12) !important;
}

/* Abrir Dossiê: botão primário verde naval */
#tabela-protocolos td:last-child .prot-btn-abrir,
.prot-table-actions .prot-btn-abrir {
    background: #087653 !important;
    border: 1px solid #087653 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(8, 118, 83, 0.25) !important;
}
#tabela-protocolos td:last-child .prot-btn-abrir i,
.prot-table-actions .prot-btn-abrir i {
    color: #ffffff !important;
}
#tabela-protocolos td:last-child .prot-btn-abrir:hover,
.prot-table-actions .prot-btn-abrir:hover {
    background: #065b40 !important;
    border-color: #065b40 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(8, 118, 83, 0.4) !important;
}
/* Componente de Paginação Naval */
.prot-pagination {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.prot-page-item {
    display: inline-block;
}
.prot-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
    background: var(--card-bg, #1e293b);
    color: var(--text-primary, #f8fafc);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.18s ease;
    user-select: none;
}
.prot-page-link:hover:not(.disabled) {
    border-color: #087653;
    color: #ffffff;
    background: rgba(8, 118, 83, 0.25);
}
.prot-page-item.active .prot-page-link {
    background: #087653 !important;
    border-color: #087653 !important;
    color: #ffffff !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(8, 118, 83, 0.4);
}
.prot-page-item.disabled .prot-page-link {
    color: rgba(148, 163, 184, 0.4) !important;
    border-color: rgba(148, 163, 184, 0.15) !important;
    background: rgba(148, 163, 184, 0.05) !important;
    cursor: not-allowed;
    pointer-events: none;
}
.prot-page-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 34px;
    color: var(--text-secondary, #94a3b8);
    font-size: 0.9rem;
}
@media (max-width: 768px) {
    .prot-pagination-footer {
        flex-direction: column;
        align-items: stretch !important;
    }
    .prot-pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>
<main class="conteudo-principal">
    <!-- Cabeçalho da Página -->
    <div class="prot-page-header">
        <div class="prot-page-title">
            <h1><i class="fa-solid fa-arrow-right-arrow-left text-accent"></i> Protocolos Documentais Navais</h1>
            <p>Controle completo de entrada, custódia física, envio à Capitania dos Portos e devolução de documentos.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (getCargo() === 'ADMIN'): ?>
                <a class="btn btn-secondary" href="<?= APP_URL ?>protocolos/configuracoes">
                    <i class="fa-solid fa-gear"></i> Cadastros de Apoio
                </a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?= APP_URL ?>protocolos/form">
                <i class="fa-solid fa-plus"></i> Novo Dossiê
            </a>
        </div>
    </div>

    <!-- Cards de Indicadores (KPIs) -->
    <div class="prot-kpi-grid">
        <div class="prot-kpi-card">
            <div class="prot-kpi-icon primary">
                <i class="fa-solid fa-folder-tree"></i>
            </div>
            <div>
                <div class="prot-kpi-val"><?= (int)($totais['total_geral'] ?? count($dossies)) ?></div>
                <div class="prot-kpi-label">Dossiês Cadastrados</div>
            </div>
        </div>

        <div class="prot-kpi-card">
            <div class="prot-kpi-icon info">
                <i class="fa-solid fa-anchor"></i>
            </div>
            <div>
                <div class="prot-kpi-val"><?= (int)($totais['total_marinha'] ?? 0) ?></div>
                <div class="prot-kpi-label">Em Trâmite na Marinha</div>
            </div>
        </div>

        <div class="prot-kpi-card">
            <div class="prot-kpi-icon warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="prot-kpi-val"><?= (int)($totais['total_exigencia'] ?? 0) ?></div>
                <div class="prot-kpi-label">Em Exigência / Pendente</div>
            </div>
        </div>

        <div class="prot-kpi-card">
            <div class="prot-kpi-icon danger">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div>
                <div class="prot-kpi-val"><?= (int)($totais['total_custodia'] ?? 0) ?></div>
                <div class="prot-kpi-label">Com Originais a Devolver</div>
            </div>
        </div>
    </div>

    <!-- Abas Rápidas de Situação -->
    <div class="prot-tabs-bar">
        <a href="<?= h(protocoloUrl(['aba' => 'todos', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'todos' ? 'active' : '' ?>">
            <i class="fa-solid fa-list"></i> Todos
            <span class="badge bg-secondary"><?= (int)($totais['total_geral'] ?? 0) ?></span>
        </a>
        <a href="<?= h(protocoloUrl(['aba' => 'EM_PREPARACAO', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'EM_PREPARACAO' ? 'active' : '' ?>">
            <i class="fa-solid fa-pen-ruler"></i> Em Preparação
            <span class="badge bg-secondary"><?= (int)($totais['total_preparacao'] ?? 0) ?></span>
        </a>
        <a href="<?= h(protocoloUrl(['aba' => 'MARINHA', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'MARINHA' ? 'active' : '' ?>">
            <i class="fa-solid fa-building-flag"></i> Na Capitania / Órgão
            <span class="badge bg-info"><?= (int)($totais['total_marinha'] ?? 0) ?></span>
        </a>
        <a href="<?= h(protocoloUrl(['aba' => 'EM_EXIGENCIA', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'EM_EXIGENCIA' ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-exclamation"></i> Em Exigência
            <span class="badge bg-warning"><?= (int)($totais['total_exigencia'] ?? 0) ?></span>
        </a>
        <a href="<?= h(protocoloUrl(['aba' => 'CUSTODIA', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'CUSTODIA' ? 'active' : '' ?>">
            <i class="fa-solid fa-box"></i> Custódia de Originais
            <span class="badge bg-danger"><?= (int)($totais['total_custodia'] ?? 0) ?></span>
        </a>
        <a href="<?= h(protocoloUrl(['aba' => 'CONCLUIDO', 'pagina' => 1])) ?>" class="prot-tab-btn <?= $aba === 'CONCLUIDO' ? 'active' : '' ?>">
            <i class="fa-solid fa-check-double"></i> Concluídos
            <span class="badge bg-success"><?= (int)($totais['total_concluido'] ?? 0) ?></span>
        </a>
    </div>

    <!-- Filtros de Busca -->
    <section class="card mb-4">
        <div class="card-body">
            <form class="d-flex flex-column gap-3" method="get" action="<?= APP_URL ?>protocolos">
                <?php if ($aba !== 'todos'): ?>
                    <input type="hidden" name="aba" value="<?= h($aba) ?>">
                <?php endif; ?>
                <?php if ($porPagina !== 15): ?>
                    <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
                <?php endif; ?>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input class="form-control" name="busca" id="filtro-busca" value="<?= h($f['busca']) ?>" placeholder="Número, embarcação, cliente, processo Marinha...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="status">
                            <option value="">Todas as situações</option>
                            <?php foreach ($labels as $v => $l): ?>
                                <option value="<?= h($v) ?>" <?= $f['status'] === $v ? 'selected' : '' ?>><?= h($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="unidade">
                            <option value="">Todas as Capitanias / Unidades</option>
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $f['unidade'] === $u['id'] ? 'selected' : '' ?>><?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input class="form-control" name="cidade" value="<?= h($f['cidade']) ?>" placeholder="Cidade">
                    </div>
                </div>

                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select class="form-control" name="responsavel">
                            <option value="">Todos os responsáveis</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $f['responsavel'] === $u['id'] ? 'selected' : '' ?>><?= h($u['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input class="form-control" type="date" name="inicio" value="<?= h($f['inicio']) ?>" title="Data de abertura inicial">
                    </div>
                    <div class="col-md-2">
                        <input class="form-control" type="date" name="fim" value="<?= h($f['fim']) ?>" title="Data de abertura final">
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter"></i> Filtrar Dossiês
                        </button>
                        <a class="btn btn-secondary" href="<?= APP_URL ?>protocolos">
                            <i class="fas fa-rotate-left"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Tabela de Dossiês -->
    <section class="card">
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table mb-0" id="tabela-protocolos">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border, rgba(255,255,255,0.1));">
                        <th style="padding: 14px 16px;">Protocolo / Dossiê</th>
                        <th style="padding: 14px 16px;">Embarcação & Cliente</th>
                        <th style="padding: 14px 16px;">Situação Atual</th>
                        <th style="padding: 14px 16px;">Destino & Processo Marinha</th>
                        <th style="padding: 14px 16px;">Responsável & Data</th>
                        <th style="padding: 14px 16px; text-align: right; min-width: 220px; width: 220px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dossies as $d): ?>
                        <tr style="border-bottom: 1px solid var(--border, rgba(255,255,255,0.06)); vertical-align: middle;">
                            <td style="padding: 14px 16px;">
                                <div class="fw-bold" style="color: var(--accent, #56e0ad); font-size: 0.95rem;">
                                    <i class="fa-solid fa-folder"></i> <?= h($d['numero']) ?>
                                </div>
                                <div class="text-secondary small mt-1">
                                    <?= h($d['assunto']) ?>
                                </div>
                                <div class="text-tertiary" style="font-size: 0.76rem;">
                                    <i class="fa-solid fa-timeline"></i> <?= (int)$d['eventos'] ?> evento(s) de trâmite
                                </div>
                            </td>

                            <td style="padding: 14px 16px;">
                                <strong><i class="fa-solid fa-ship"></i> <?= h($d['embarcacao_nome']) ?></strong>
                                <?php if (!empty($d['embarcacao_registro'])): ?>
                                    <span class="text-secondary small">(<?= h($d['embarcacao_registro']) ?>)</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-secondary">
                                    <i class="fa-solid fa-user"></i> <?= h($d['cliente_nome'] ?: 'Cliente não informado') ?>
                                </small>
                            </td>

                            <td style="padding: 14px 16px;">
                                <span class="prot-badge-status <?= h($d['status']) ?>">
                                    <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                                    <?= h($labels[$d['status']] ?? $d['status']) ?>
                                </span>
                                <?php if ($d['originais_pendentes']): ?>
                                    <div class="mt-1" style="color: #f59e0b; font-size: 0.78rem; font-weight: 600;">
                                        <i class="fa-solid fa-box-open"></i> <?= (int)$d['originais_pendentes'] ?> original(is) sob custódia
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px 16px;">
                                <div class="fw-semibold">
                                    <i class="fa-solid fa-anchor"></i> <?= h($d['unidade_nome'] ?: 'Não definido') ?>
                                </div>
                                <?php if (!empty($d['protocolo_externo_numero'])): ?>
                                    <div class="text-accent small">
                                        <strong>Proc.:</strong> <?= h($d['protocolo_externo_numero']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-secondary" style="font-size: 0.78rem;">
                                    <?= $d['protocolo_externo_em'] ? 'Atendimento: ' . formatarDataCompleta($d['protocolo_externo_em']) : 'Atendimento pendente' ?>
                                </div>
                                <?php if (!empty($d['protocolo_externo_validade'])): 
                                    $diasVal = (int)ceil((strtotime($d['protocolo_externo_validade']) - time()) / 86400);
                                ?>
                                    <div class="mt-1">
                                        <?php if ($diasVal < 0): ?>
                                            <span class="badge bg-danger text-white" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Prazo expirou há <?= abs($diasVal) ?>d
                                            </span>
                                        <?php elseif ($diasVal === 0): ?>
                                            <span class="badge bg-danger text-white" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-clock"></i> Prazo vence HOJE!
                                            </span>
                                        <?php elseif ($diasVal <= 7): ?>
                                            <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-hourglass-half"></i> Faltam <?= $diasVal ?> dia(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-secondary" style="font-size: 0.75rem;">
                                                <i class="fa-solid fa-calendar-check"></i> Prazo: <?= date('d/m/Y', strtotime($d['protocolo_externo_validade'])) ?> (<?= $diasVal ?>d)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px 16px;">
                                <div class="small">
                                    <i class="fa-solid fa-user-pen"></i> <?= h($d['responsavel_nome'] ?: 'Sistema') ?>
                                </div>
                                <div class="text-secondary" style="font-size: 0.76rem;">
                                    <?= formatarDataCompleta($d['atualizado_em']) ?>
                                </div>
                            </td>

                            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                                <?php
                                $procNum = $d['protocolo_externo_numero'] ?? '';
                                $msgWhatsList = rawurlencode("Olá! Informamos que o processo da embarcação *" . $d['embarcacao_nome'] . "* (Dossiê " . $d['numero'] . ") está em andamento na " . ($d['unidade_nome'] ?: 'Capitania/Delegacia') . ".\nSituação: *" . ($labels[$d['status']] ?? $d['status']) . "*" . ($procNum ? "\nNº Oficial no Órgão: *" . $procNum . "*" : "") . "\n\nAmazon Certificadora");
                                ?>
                                <div class="prot-table-actions">
                                    <a class="prot-btn-action prot-btn-whatsapp" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=<?= $msgWhatsList ?>" title="Avisar cliente via WhatsApp">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                    <a class="prot-btn-action prot-btn-pdf" target="_blank" href="<?= APP_URL ?>protocolos/pdf-dossie?id=<?= urlencode($d['id']) ?>" title="Gerar PDF consolidado do dossiê">
                                        <i class="fa-solid fa-file-pdf"></i> <span>PDF</span>
                                    </a>
                                    <a class="prot-btn-action prot-btn-abrir" href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($d['id']) ?>" title="Abrir tramitação e eventos">
                                        <i class="fa-solid fa-folder-open"></i> <span>Abrir</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$dossies): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i>
                                <p class="mb-1 fw-semibold">Nenhum protocolo encontrado para estes filtros.</p>
                                <small>Clique em "Limpar" ou abra um novo processo com o botão "+ Novo Dossiê".</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Rodapé com Resumo e Paginação -->
        <div class="card-footer prot-pagination-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-top: 1px solid var(--border, rgba(255,255,255,0.1)); background: transparent;">
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <small class="text-secondary" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Mostrando <strong><?= $registroInicio ?></strong> a <strong><?= $registroFim ?></strong> de <strong><?= $totalDossies ?></strong> dossiê(s)
                    <?php if (!empty($f['busca'])): ?>
                        (filtrando por "<strong><?= h($f['busca']) ?></strong>")
                    <?php endif; ?>
                </small>

                <!-- Seletor de itens por página -->
                <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="margin: 0; font-size: 0.8rem; color: var(--text-secondary, #94a3b8); white-space: nowrap;">Exibir:</label>
                    <select id="selectPorPagina" 
                            class="form-select form-select-sm" 
                            style="width: auto; height: 32px; padding: 2px 28px 2px 10px; font-size: 0.82rem; border-radius: 6px; background-color: var(--card-bg, #1e293b); color: var(--text-primary, #f8fafc); border-color: var(--border, rgba(255,255,255,0.15));" 
                            onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?= h(protocoloUrl(['por_pagina' => $qtd, 'pagina' => 1])) ?>" <?= $porPagina === $qtd ? 'selected' : '' ?>>
                                <?= $qtd ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Navegação de Páginas -->
            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de protocolos">
                    <ul class="prot-pagination">
                        <!-- Primeira página -->
                        <li class="prot-page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="prot-page-link" 
                               href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h(protocoloUrl(['pagina' => 1])) ?>" 
                               title="Primeira página">
                                <i class="fa-solid fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="prot-page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="prot-page-link" 
                               href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h(protocoloUrl(['pagina' => $paginaAtual - 1])) ?>" 
                               title="Página anterior">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de números de página -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="prot-page-item"><a class="prot-page-link" href="' . h(protocoloUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="prot-page-ellipsis">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="prot-page-item active"><span class="prot-page-link">' . $p . '</span></li>';
                            } else {
                                echo '<li class="prot-page-item"><a class="prot-page-link" href="' . h(protocoloUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="prot-page-ellipsis">...</li>';
                            }
                            echo '<li class="prot-page-item"><a class="prot-page-link" href="' . h(protocoloUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="prot-page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="prot-page-link" 
                               href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(protocoloUrl(['pagina' => $paginaAtual + 1])) ?>" 
                               title="Próxima página">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="prot-page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="prot-page-link" 
                               href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(protocoloUrl(['pagina' => $totalPaginas])) ?>" 
                               title="Última página">
                                <i class="fa-solid fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
