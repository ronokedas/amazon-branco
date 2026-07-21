<?php
/**
 * MÓDULO: COMERCIAL
 * Arquivo: index.php - Dashboard Comercial com indicadores e listagem de propostas
 * Acesso: ADMIN (completo) | VISTORIADOR (apenas visualização)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('comercial');

// ============================================
// FILTROS
// ============================================
$filtro_status   = $_GET['status'] ?? 'todos';
$filtro_busca    = trim($_GET['busca'] ?? '');
$filtro_data_ini = $_GET['data_ini'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$proposta_foco_id = trim($_GET['nova_proposta'] ?? $_GET['proposta'] ?? '');
$modo_pos_criacao = !empty($_GET['nova_proposta']);
$modo_foco_proposta = !empty($proposta_foco_id);
$itens_por_pagina = 10;
$pagina_atual_lista = max(1, (int)($_GET['pagina'] ?? 1));
$offset_lista = ($pagina_atual_lista - 1) * $itens_por_pagina;
$total_propostas_lista = 0;
$total_paginas_lista = 1;

$where = [];
$params = [];

if ($cargo === 'VENDEDOR') {
    $where[] = 'p.criado_por = :vendedor_id';
    $params[':vendedor_id'] = $_SESSION['usuario_id'];
}

if ($modo_foco_proposta) {
    $where[] = 'p.id = :proposta_foco_id';
    $params[':proposta_foco_id'] = $proposta_foco_id;
}

if (!$modo_foco_proposta && $filtro_status !== 'todos') {
    $where[] = 'p.status = :status';
    $params[':status'] = $filtro_status;
}

if (!$modo_foco_proposta && !empty($filtro_busca)) {
    $where[] = '(c.nome LIKE :busca OR p.numero LIKE :busca2)';
    $params[':busca']  = '%' . $filtro_busca . '%';
    $params[':busca2'] = '%' . $filtro_busca . '%';
}

if (!$modo_foco_proposta && !empty($filtro_data_ini)) {
    $where[] = 'p.data_emissao >= :data_ini';
    $params[':data_ini'] = $filtro_data_ini;
}

if (!$modo_foco_proposta && !empty($filtro_data_fim)) {
    $where[] = 'p.data_emissao <= :data_fim';
    $params[':data_fim'] = $filtro_data_fim;
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

// ============================================
// INDICADORES COMERCIAIS (APENAS ADMIN)
// ============================================
$indicadores = [];
if ($cargo === 'ADMIN') {
    // Mês atual
    $mesAtual = date('Y-m');

    try {
        // Propostas emitidas no mês. Este indicador mede volume comercial,
        // independentemente de a proposta ter sido aceita ou não.
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM propostas WHERE DATE_FORMAT(data_emissao, '%Y-%m') = :mes");
        $stmt->execute([':mes' => $mesAtual]);
        $totalPropostasMes = (int)$stmt->fetchColumn();

        // Valor emitido no mês (pipeline), independentemente do status.
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_total), 0) FROM propostas WHERE DATE_FORMAT(data_emissao, '%Y-%m') = :mes");
        $stmt->execute([':mes' => $mesAtual]);
        $valorPropostasEmitidasMes = (float)$stmt->fetchColumn();

        // Vendas contratadas no mês: somente propostas efetivamente assinadas.
        // A data da assinatura define o mês da venda. O fallback para created_at
        // mantém compatibilidade com assinaturas históricas sem data registrada.
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor_total), 0) AS valor
            FROM propostas
            WHERE assinado = 1
              AND DATE_FORMAT(COALESCE(assinatura_em, created_at), '%Y-%m') = :mes
        ");
        $stmt->execute([':mes' => $mesAtual]);
        $vendasAssinadasMes = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['quantidade' => 0, 'valor' => 0];
        $assinadasMes = (int)$vendasAssinadasMes['quantidade'];
        $valorAssinadoMes = (float)$vendasAssinadasMes['valor'];

        // Conversão da safra de propostas emitidas no mês: quantas delas já
        // foram assinadas, mesmo que o aceite tenha ocorrido depois da emissão.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM propostas
            WHERE assinado = 1
              AND DATE_FORMAT(data_emissao, '%Y-%m') = :mes
        ");
        $stmt->execute([':mes' => $mesAtual]);
        $assinadasDasEmitidasMes = (int)$stmt->fetchColumn();

        // Meta mensal consolidada dos escritorios ativos.
        $metaMensal = 0.00;
        try {
            $stmtMeta = $pdo->prepare("SELECT COALESCE(SUM(fm.valor),0) FROM financeiro_metas_mensais fm JOIN escritorios e ON e.id=fm.escritorio_id AND e.ativo=1 WHERE fm.usuario_id IS NULL AND fm.competencia=:competencia");
            $stmtMeta->execute([':competencia'=>date('Y-m-01')]);
            $metaValorDb = $stmtMeta->fetchColumn();
            if ($metaValorDb !== false) {
                $metaMensal = (float)$metaValorDb;
            }
        } catch (Exception $e) {
            error_log('Erro ao carregar metas dos escritorios: ' . $e->getMessage());
        }
        $percMeta = ($metaMensal > 0) ? round(($valorAssinadoMes / $metaMensal) * 100, 1) : 0;

        // Taxa de conversão baseada em aceite/assinatura, não no status
        // intermediário "aprovada".
        $taxaConversao = ($totalPropostasMes > 0) ? round(($assinadasDasEmitidasMes / $totalPropostasMes) * 100, 1) : 0;

        // Total geral de propostas (todos os tempos)
        $stmt = $pdo->query("SELECT COUNT(*) FROM propostas");
        $totalGeralProp = (int)$stmt->fetchColumn();

        $indicadores = [
            'total_mes'        => $totalPropostasMes,
            'valor_emitido_mes'=> $valorPropostasEmitidasMes,
            'valor_assinado_mes' => $valorAssinadoMes,
            'meta_mensal'      => $metaMensal,
            'perc_meta'        => $percMeta,
            'assinadas_mes'    => $assinadasMes,
            'assinadas_emitidas_mes' => $assinadasDasEmitidasMes,
            'taxa_conversao'   => $taxaConversao,
            'total_geral'      => $totalGeralProp,
        ];
    } catch (Exception $e) {
        error_log('Erro ao carregar indicadores comerciais: ' . $e->getMessage());
        $indicadores = [
            'total_mes'       => 0,
            'valor_emitido_mes' => 0,
            'valor_assinado_mes' => 0,
            'meta_mensal'     => 0.00,
            'perc_meta'       => 0,
            'assinadas_mes'   => 0,
            'assinadas_emitidas_mes' => 0,
            'taxa_conversao'  => 0,
            'total_geral'     => 0,
        ];
    }
}

// ============================================
// BUSCAR PROPOSTAS
// ============================================
try {
    $sqlCount = "SELECT COUNT(*)
            FROM propostas p
            INNER JOIN clientes c ON c.id = p.cliente_id
            {$sqlWhere}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_propostas_lista = (int)$stmtCount->fetchColumn();
    $total_paginas_lista = max(1, (int)ceil($total_propostas_lista / $itens_por_pagina));
    if ($pagina_atual_lista > $total_paginas_lista) {
        $pagina_atual_lista = $total_paginas_lista;
        $offset_lista = ($pagina_atual_lista - 1) * $itens_por_pagina;
    }

    $sql = "SELECT p.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj
            FROM propostas p
            INNER JOIN clientes c ON c.id = p.cliente_id
            {$sqlWhere}
            ORDER BY p.created_at DESC";
    if (!$modo_foco_proposta) {
        $sql .= " LIMIT {$itens_por_pagina} OFFSET {$offset_lista}";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $propostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar embarcações de cada proposta (consulta separada para evitar GROUP_CONCAT pesado)
    $embarcacoesPorProposta = [];
    if (!empty($propostas)) {
        $ids = array_column($propostas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEmb = $pdo->prepare("
            SELECT pe.proposta_id, e.nome, e.registro
            FROM propostas_embarcacoes pe
            INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
            WHERE pe.proposta_id IN ({$placeholders})
            ORDER BY e.nome ASC
        ");
        $stmtEmb->execute(array_values($ids));
        $vinculos = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vinculos as $v) {
            $pid = $v['proposta_id'];
            if (!isset($embarcacoesPorProposta[$pid])) {
                $embarcacoesPorProposta[$pid] = [];
            }
            $embarcacoesPorProposta[$pid][] = $v['nome'] . (!empty($v['registro']) ? ' (' . $v['registro'] . ')' : '');
        }
    }
} catch (Exception $e) {
    error_log('Erro ao listar propostas: ' . $e->getMessage());
    $propostas = [];
    $embarcacoesPorProposta = [];
}

// Status com labels e cores
$statusConfig = [
    'rascunho'  => ['label' => 'Rascunho',  'cor' => 'secondary'],
    'enviada'   => ['label' => 'Enviada',   'cor' => 'info'],
    'aprovada'  => ['label' => 'Aprovada',  'cor' => 'success'],
    'assinada'  => ['label' => 'Assinada',  'cor' => 'success'],
    'recusada'  => ['label' => 'Recusada',  'cor' => 'danger'],
    'cancelada' => ['label' => 'Cancelada', 'cor' => 'warning'],
];

$propostaFoco = ($modo_foco_proposta && !empty($propostas)) ? $propostas[0] : null;

function comercialPageUrl(int $pagina): string {
    $query = $_GET;
    unset($query['nova_proposta'], $query['proposta']);
    $query['pagina'] = $pagina;
    return APP_URL . 'comercial?' . http_build_query($query);
}

$titulo_page = 'Comercial - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">

    <!-- Cabeçalho -->
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-chart-line"></i> Módulo Comercial</h1>
            <p>Gerencie propostas comerciais, serviços e acompanhe indicadores.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Proposta
            </a>
            <a href="<?php echo APP_URL; ?>comercial/servicos" class="btn btn-secondary">
                <i class="fas fa-cogs"></i> Serviços
            </a>
        </div>
    </div>

    <?php if ($cargo === 'ADMIN'): ?>
    <!-- ===== INDICADORES COMERCIAIS ===== -->
    <div class="cards-grid" data-testid="indicadores-comerciais"
         data-total-propostas="<?php echo $indicadores['total_mes']; ?>"
         data-assinadas-emitidas="<?php echo $indicadores['assinadas_emitidas_mes']; ?>"
         data-valor-emitido="<?php echo number_format($indicadores['valor_emitido_mes'], 2, '.', ''); ?>"
         data-valor-assinado="<?php echo number_format($indicadores['valor_assinado_mes'], 2, '.', ''); ?>"
         data-meta="<?php echo number_format($indicadores['meta_mensal'], 2, '.', ''); ?>"
         data-percentual-meta="<?php echo number_format($indicadores['perc_meta'], 1, '.', ''); ?>"
         style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 25px;">
        <!-- Card: Propostas do Mês -->
        <div class="card" style="border-left: 4px solid #2ECC71;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(46,204,113,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-file-invoice" style="font-size: 1.3rem; color: #2ECC71;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Propostas do Mês</small>
                    <span data-testid="total-propostas-mes" style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo $indicadores['total_mes']; ?></span>
                    <small data-testid="conversao-propostas-mes" style="display: block; color: var(--cor-texto-secundario); margin-top: 2px;">
                        <?php echo $indicadores['assinadas_emitidas_mes']; ?> assinadas (<?php echo number_format($indicadores['taxa_conversao'], 1, ',', '.'); ?>%)
                    </small>
                </div>
            </div>
        </div>

        <!-- Card: Vendas assinadas no mês -->
        <div class="card" style="border-left: 4px solid #3498DB;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(52,152,219,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-dollar-sign" style="font-size: 1.3rem; color: #3498DB;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Vendas Assinadas no Mês</small>
                    <span data-testid="valor-vendas-assinadas" style="display: block; white-space: nowrap; font-size: clamp(1.25rem, 1.65vw, 1.6rem); letter-spacing: -0.025em; font-weight: 700; color: var(--cor-texto);">R$ <?php echo number_format($indicadores['valor_assinado_mes'], 2, ',', '.'); ?></span>
                    <small data-testid="valor-propostas-emitidas" style="display: block; color: var(--cor-texto-secundario); margin-top: 2px;">
                        <?php echo $indicadores['assinadas_mes']; ?> venda(s) contratada(s) · R$ <?php echo number_format($indicadores['valor_emitido_mes'], 2, ',', '.'); ?> emitidos
                    </small>
                </div>
            </div>
        </div>

        <!-- Card: Meta Mensal -->
        <div class="card" style="border-left: 4px solid #F39C12;">
            <div style="padding: 18px 20px;">
                <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 4px;">Meta Mensal (R$ <?php echo number_format($indicadores['meta_mensal'], 2, ',', '.'); ?>)</small>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span data-testid="percentual-meta" style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo number_format($indicadores['perc_meta'], 1, ',', '.'); ?>%</span>
                </div>
                <!-- Barra de progresso -->
                <div style="margin-top: 10px; background: var(--cor-borda); border-radius: 6px; height: 8px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $indicadores['perc_meta']); ?>%; height: 100%; background: linear-gradient(90deg, #F39C12, #E67E22); border-radius: 6px; transition: width 0.6s ease;"></div>
                </div>
                <small data-testid="restante-meta" style="display: block; color: var(--cor-texto-secundario); margin-top: 6px;">
                    Restam R$ <?php echo number_format(max(0, $indicadores['meta_mensal'] - $indicadores['valor_assinado_mes']), 2, ',', '.'); ?> para atingir a meta
                </small>
            </div>
        </div>

        <!-- Card: Total Geral -->
        <div class="card" style="border-left: 4px solid #9B59B6;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(155,89,182,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-archive" style="font-size: 1.3rem; color: #9B59B6;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Total Geral de Propostas</small>
                    <span style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo $indicadores['total_geral']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($propostaFoco): ?>
    <?php
        $statusFoco = $statusConfig[$propostaFoco['status']] ?? ['label' => $propostaFoco['status'], 'cor' => 'secondary'];
        $embarcacoesFoco = $embarcacoesPorProposta[$propostaFoco['id']] ?? [];
    ?>
    <div data-testid="proposta-foco-card" style="margin-bottom: 22px; border: 1px solid rgba(52, 152, 219, 0.45); border-left: 6px solid #3498DB; border-radius: 8px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.16), rgba(46, 204, 113, 0.08)); overflow: hidden;">
        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; align-items: center;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span style="display: inline-flex; align-items: center; gap: 7px; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: #7DD3FC;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $modo_pos_criacao ? 'Proposta criada agora' : 'Proposta em foco'; ?>
                    </span>
                    <span class="badge badge-<?php echo $statusFoco['cor']; ?>"><?php echo h($statusFoco['label']); ?></span>
                </div>
                <h2 style="margin: 0 0 8px; color: var(--cor-texto); font-size: 1.45rem;">
                    <?php echo h($propostaFoco['numero']); ?> - <?php echo h($propostaFoco['cliente_nome']); ?>
                </h2>
                <div style="display: flex; flex-wrap: wrap; gap: 14px; color: var(--cor-texto-secundario);">
                    <span><i class="fas fa-ship"></i> <?php echo !empty($embarcacoesFoco) ? h(implode(', ', $embarcacoesFoco)) : 'Embarcacao nao informada'; ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($propostaFoco['data_emissao'])); ?></span>
                    <span><i class="fas fa-dollar-sign"></i> R$ <?php echo number_format((float)$propostaFoco['valor_total'], 2, ',', '.'); ?></span>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px; min-width: 220px;">
                <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($propostaFoco['id']); ?>"
                   class="btn btn-primary"
                   data-testid="proposta-foco-pdf"
                   target="_blank"
                   style="font-size: 1.05rem; padding: 13px 18px; display: inline-flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 10px 24px rgba(52, 152, 219, 0.28);">
                    <i class="fas fa-file-pdf"></i> Abrir PDF da Proposta
                </a>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <a href="<?php echo APP_URL; ?>comercial/propostas?id=<?php echo urlencode($propostaFoco['id']); ?>&visualizar=1"
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-eye"></i> Detalhes
                    </a>
                    <a href="<?php echo APP_URL; ?>comercial" class="btn btn-secondary btn-sm" data-testid="proposta-foco-ver-todas">
                        <i class="fas fa-list"></i> Ver todas
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($modo_foco_proposta): ?>
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        <i class="fas fa-exclamation-triangle"></i>
        Nao encontrei a proposta solicitada. <a href="<?php echo APP_URL; ?>comercial">Ver todas as propostas</a>.
    </div>
    <?php endif; ?>

    <!-- ===== LISTAGEM DE PROPOSTAS ===== -->
    <div class="tabela-container">
        <div class="tabela-header">
            <h3>
                <i class="fas fa-file-invoice"></i>
                <?php echo $modo_foco_proposta ? 'Proposta selecionada' : 'Propostas'; ?>
            </h3>
            <div style="display: flex; gap: 8px; align-items: center;">
                <?php if ($modo_foco_proposta): ?>
                    <a href="<?php echo APP_URL; ?>comercial" class="btn btn-secondary btn-sm">
                        <i class="fas fa-list"></i> Ver todas
                    </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nova Proposta
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <?php if (!$modo_foco_proposta): ?>
        <div class="filtros" style="margin: 15px 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="buscaProposta" placeholder="Nome do cliente ou número da proposta..."
                       value="<?php echo h($filtro_busca); ?>"
                       onkeyup="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label><i class="fas fa-filter"></i> Status</label>
                <select id="filtroStatus" onchange="filtrarPropostas()">
                    <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($statusConfig as $val => $cfg): ?>
                        <option value="<?php echo $val; ?>" <?php echo $filtro_status === $val ? 'selected' : ''; ?>>
                            <?php echo $cfg['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Início</label>
                <input type="date" id="filtroDataIni" value="<?php echo h($filtro_data_ini); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Fim</label>
                <input type="date" id="filtroDataFim" value="<?php echo h($filtro_data_fim); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 90px; display: flex; align-items: flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()" style="width: 100%;">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($propostas)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-invoice"></i>
                <h3>Nenhuma proposta encontrada</h3>
                <p>
                    <?php if ($filtro_status !== 'todos' || !empty($filtro_busca)): ?>
                        Nenhum resultado para os filtros aplicados. Tente limpar os filtros.
                    <?php else: ?>
                        Clique em "Nova Proposta" para criar a primeira proposta do sistema.
                    <?php endif; ?>
                </p>
                <?php if ($cargo === 'ADMIN'): ?>
                <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova Proposta
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="commercial-list">
                <?php foreach ($propostas as $p): ?>
                <?php
                    $pid = $p['id'];
                    $embarcacoesLista = $embarcacoesPorProposta[$pid] ?? [];
                    $embNomes = !empty($embarcacoesLista) ? implode(', ', $embarcacoesLista) : 'N/I';
                    $statusCfg = $statusConfig[$p['status']] ?? ['label' => $p['status'], 'cor' => 'secondary'];
                    $assinada = !empty($p['assinado']) || ($p['status'] ?? '') === 'assinada';
                    $podeAprovarManual = in_array($cargo, ['ADMIN', 'VENDEDOR'], true)
                        && ($cargo === 'ADMIN' || ($p['criado_por'] ?? '') === ($_SESSION['usuario_id'] ?? ''))
                        && !$assinada
                        && !in_array(($p['status'] ?? ''), ['cancelada', 'recusada'], true);
                ?>
                <article class="proposal-row<?php echo ($modo_foco_proposta && $pid === $proposta_foco_id) ? ' is-focus' : ''; ?>" id="proposta-<?php echo h($pid); ?>">
                    <div class="proposal-main">
                        <div class="proposal-number">
                            <span><?php echo h($p['numero']); ?></span>
                            <small><?php echo date('d/m/Y', strtotime($p['data_emissao'])); ?></small>
                        </div>
                        <div class="proposal-client">
                            <strong><?php echo h($p['cliente_nome']); ?></strong>
                            <small><?php echo h($p['cliente_cpfcnpj'] ?? ''); ?></small>
                        </div>
                        <div class="proposal-boat">
                            <small>EMBARCA&Ccedil;&Atilde;O</small>
                            <span><?php echo h($embNomes); ?></span>
                        </div>
                    </div>

                    <div class="proposal-finance">
                        <strong>R$ <?php echo number_format((float)$p['valor_total'], 2, ',', '.'); ?></strong>
                        <small><?php echo (int)$p['parcelas']; ?>x</small>
                    </div>

                    <div class="proposal-state">
                        <span class="commercial-status commercial-status-<?php echo h($statusCfg['cor']); ?>">
                            <?php echo h($statusCfg['label']); ?>
                        </span>
                        <?php if ($assinada): ?>
                            <small><i class="fas fa-signature"></i> Assinada</small>
                        <?php endif; ?>
                    </div>

                    <div class="proposal-actions">
                        <a href="<?php echo APP_URL; ?>comercial/propostas?id=<?php echo urlencode($pid); ?>&visualizar=1"
                           class="proposal-action proposal-action-view" title="Detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($pid); ?>"
                           class="proposal-action proposal-action-pdf" title="Abrir PDF" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <?php if ($cargo === 'ADMIN' && ($p['status'] ?? '') !== 'cancelada'): ?>
                            <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions"
                                  onsubmit="return confirm('Enviar proposta <?php echo h(addslashes($p['numero'])); ?> por e-mail para o cliente?')">
                                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                <input type="hidden" name="action" value="enviar_proposta">
                                <input type="hidden" name="id" value="<?php echo h($pid); ?>">
                                <button type="submit" class="proposal-action proposal-action-email" title="Enviar por e-mail">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (!empty($p['token_assinatura'])): ?>
                            <a href="<?php echo APP_URL; ?>assinar/<?php echo urlencode($p['token_assinatura']); ?>"
                               class="proposal-action proposal-action-sign" title="Abrir link de assinatura" target="_blank">
                                <i class="fas fa-signature"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($podeAprovarManual): ?>
                            <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions"
                                  onsubmit="return confirm('Autorizar <?php echo h(addslashes($p['numero'])); ?> sem a assinatura digital do cliente? A proposta será marcada como assinada e criará os lançamentos e agendamentos.');">
                                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                <input type="hidden" name="action" value="aprovar_assinatura_manual">
                                <input type="hidden" name="id" value="<?php echo h($pid); ?>">
                                <button type="submit" class="proposal-action proposal-action-approve" title="Autorizar e marcar como assinada" aria-label="Autorizar <?php echo h($p['numero']); ?> sem assinatura digital">
                                    <i class="fas fa-circle-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Rodapé com totalizador -->
        <div class="card-footer commercial-footer">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                <?php if ($modo_foco_proposta): ?>
                    Proposta selecionada
                <?php else: ?>
                    Mostrando <?php echo $total_propostas_lista > 0 ? ($offset_lista + 1) : 0; ?>-<?php echo min($offset_lista + count($propostas), $total_propostas_lista); ?> de <?php echo $total_propostas_lista; ?> proposta(s)
                <?php endif; ?>
                <?php if ($filtro_status !== 'todos'): ?>
                    com status "<?php echo $statusConfig[$filtro_status]['label'] ?? $filtro_status; ?>"
                <?php endif; ?>
            </small>
            <?php
            $somaLista = array_sum(array_column($propostas, 'valor_total'));
            ?>
            <small class="text-muted">
                <strong>Soma desta p&aacute;gina:</strong> R$ <?php echo number_format($somaLista, 2, ',', '.'); ?>
            </small>
        </div>
        <?php if (!$modo_foco_proposta && $total_paginas_lista > 1): ?>
            <nav class="commercial-pagination" aria-label="Paginação de propostas">
                <a class="page-step<?php echo $pagina_atual_lista <= 1 ? ' is-disabled' : ''; ?>"
                   href="<?php echo $pagina_atual_lista <= 1 ? '#' : h(comercialPageUrl($pagina_atual_lista - 1)); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php
                $inicioPag = max(1, $pagina_atual_lista - 2);
                $fimPag = min($total_paginas_lista, $pagina_atual_lista + 2);
                for ($pag = $inicioPag; $pag <= $fimPag; $pag++):
                ?>
                    <a class="page-number<?php echo $pag === $pagina_atual_lista ? ' is-active' : ''; ?>"
                       href="<?php echo h(comercialPageUrl($pag)); ?>">
                        <?php echo $pag; ?>
                    </a>
                <?php endfor; ?>
                <a class="page-step<?php echo $pagina_atual_lista >= $total_paginas_lista ? ' is-disabled' : ''; ?>"
                   href="<?php echo $pagina_atual_lista >= $total_paginas_lista ? '#' : h(comercialPageUrl($pagina_atual_lista + 1)); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        <?php endif; ?>
    </div>
</div>

<style>
.commercial-list {
    display: grid;
    gap: 10px;
    padding: 14px 16px 6px;
}
.proposal-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 150px 120px minmax(210px, auto);
    gap: 14px;
    align-items: center;
    padding: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    background: rgba(255,255,255,0.025);
}
.proposal-row:hover,
.proposal-row.is-focus {
    border-color: rgba(86,224,173,0.34);
    background: rgba(86,224,173,0.055);
}
.proposal-main {
    display: grid;
    grid-template-columns: 130px minmax(180px, 1fr) minmax(190px, 1fr);
    gap: 14px;
    align-items: center;
    min-width: 0;
}
.proposal-number span,
.proposal-client strong,
.proposal-boat span,
.proposal-finance strong {
    display: block;
    color: var(--cor-texto);
}
.proposal-number span {
    font-family: monospace;
    font-weight: 800;
    letter-spacing: 0;
}
.proposal-number small,
.proposal-client small,
.proposal-boat small,
.proposal-finance small,
.proposal-state small {
    display: block;
    color: var(--cor-texto-secundario);
    margin-top: 3px;
}
.proposal-client,
.proposal-boat {
    min-width: 0;
}
.proposal-client strong,
.proposal-boat span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.proposal-boat small {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: rgba(125,211,252,0.92);
}
.proposal-finance {
    text-align: right;
}
.proposal-finance strong {
    color: #56e0ad;
    font-size: 1rem;
}
.proposal-state {
    display: grid;
    gap: 4px;
    justify-items: start;
    min-width: 120px;
}
.commercial-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 82px;
    white-space: nowrap;
    min-height: 28px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 800;
    line-height: 1;
}
.commercial-status-success { background: rgba(46,204,113,0.16); color: #56e0ad; border: 1px solid rgba(86,224,173,0.36); }
.commercial-status-info { background: rgba(52,152,219,0.16); color: #7dd3fc; border: 1px solid rgba(125,211,252,0.34); }
.commercial-status-secondary { background: rgba(148,163,184,0.14); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.24); }
.commercial-status-danger { background: rgba(231,76,60,0.16); color: #ff8b81; border: 1px solid rgba(255,139,129,0.34); }
.commercial-status-warning { background: rgba(243,156,18,0.16); color: #f6c177; border: 1px solid rgba(246,193,119,0.34); }
.proposal-actions {
    display: flex;
    flex-wrap: nowrap;
    justify-content: flex-end;
    align-items: center;
    gap: 7px;
    min-width: 210px;
}
.proposal-actions form {
    margin: 0;
}
.proposal-action {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
    flex: 0 0 auto;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 8px 16px rgba(0,0,0,0.10);
    transition: transform 0.16s, box-shadow 0.16s, border-color 0.16s, background 0.16s, color 0.16s;
}
.proposal-action:hover {
    transform: translateY(-1px);
    color: #fff;
}
.proposal-action-view { background: rgba(148,163,184,0.18); color: #e2e8f0; border-color: rgba(148,163,184,0.28); }
.proposal-action-pdf { background: linear-gradient(135deg, rgba(239,68,68,0.28), rgba(127,29,29,0.34)); border-color: rgba(248,113,113,0.50); color: #ffe4e4; }
.proposal-action-email { background: linear-gradient(135deg, rgba(59,130,246,0.28), rgba(30,64,175,0.34)); border-color: rgba(96,165,250,0.50); color: #e0efff; }
.proposal-action-sign { background: linear-gradient(135deg, rgba(34,197,94,0.24), rgba(14,116,144,0.24)); border-color: rgba(125,211,252,0.48); color: #d7fbff; box-shadow: 0 10px 24px rgba(59,130,246,0.14); }
.proposal-action-approve { background: linear-gradient(135deg, #22c55e, #56e0ad); border-color: rgba(86,224,173,0.64); color: #042014; box-shadow: 0 10px 24px rgba(34,197,94,0.22); }
.proposal-action:hover.proposal-action-view,
.proposal-action:hover.proposal-action-pdf,
.proposal-action:hover.proposal-action-email,
.proposal-action:hover.proposal-action-sign,
.proposal-action:hover.proposal-action-approve {
    box-shadow: 0 12px 24px rgba(0,0,0,0.18);
}
.commercial-footer {
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.commercial-pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
    padding: 0 20px 18px;
}
.page-number,
.page-step {
    min-width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border: 1px solid var(--cor-borda);
    border-radius: 8px;
    color: var(--cor-texto);
    text-decoration: none;
    font-weight: 700;
}
.page-number.is-active {
    background: #56e0ad;
    border-color: #56e0ad;
    color: #041512;
}
.page-step.is-disabled {
    opacity: 0.35;
    pointer-events: none;
}
@media (max-width: 1080px) {
    .proposal-row {
        grid-template-columns: 1fr;
    }
    .proposal-main {
        grid-template-columns: 1fr 1fr;
    }
    .proposal-finance {
        text-align: left;
    }
    .proposal-actions {
        justify-content: flex-start;
    }
}
@media (max-width: 680px) {
    .proposal-main {
        grid-template-columns: 1fr;
    }
    .proposal-client strong,
    .proposal-boat span {
        white-space: normal;
    }
}
</style>

<script>
function filtrarPropostas() {
    const status   = document.getElementById('filtroStatus').value;
    const busca    = document.getElementById('buscaProposta').value.trim();
    const dataIni  = document.getElementById('filtroDataIni').value;
    const dataFim  = document.getElementById('filtroDataFim').value;

    let url = '<?php echo APP_URL; ?>comercial?';
    const params = [];

    if (status !== 'todos') {
        params.push('status=' + encodeURIComponent(status));
    }
    if (busca !== '') {
        params.push('busca=' + encodeURIComponent(busca));
    }
    if (dataIni !== '') {
        params.push('data_ini=' + encodeURIComponent(dataIni));
    }
    if (dataFim !== '') {
        params.push('data_fim=' + encodeURIComponent(dataFim));
    }

    window.location.href = url + params.join('&');
}

function limparFiltros() {
    window.location.href = '<?php echo APP_URL; ?>comercial';
}

// Permite buscar pressionando Enter no campo de busca
document.getElementById('buscaProposta')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        filtrarPropostas();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
