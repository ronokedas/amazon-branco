<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: index.php - Listagem com filtros (data, tipo, categoria) e cards de resumo
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Filtros
$filtro_tipo      = $_GET['tipo'] ?? '';
$filtro_data_ini  = $_GET['data_ini'] ?? '';
$filtro_data_fim  = $_GET['data_fim'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$escritorios = financeiroEscritoriosPermitidos($pdo);
$podeSelecionarEscritorio = financeiroEhAdmin() || count($escritorios) > 1;
$escritoriosPorId=[];foreach($escritorios as $e)$escritoriosPorId[$e['id']]=$e;
try { $filtro_escritorio = financeiroResolverEscritorio($pdo, $_GET['escritorio_id'] ?? null); }
catch (Throwable $e) { setMensagem('error',$e->getMessage()); redirecionar(APP_URL.'dashboard'); }

// Paginação
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));

// Helper para URLs mantendo filtros e paginação
if (!function_exists('financeiroUrlPaginacao')) {
    function financeiroUrlPaginacao(array $overrides = []): string {
        global $filtro_tipo, $filtro_data_ini, $filtro_data_fim, $filtro_categoria, $filtro_escritorio, $porPagina, $paginaAtual;
        $params = [
            'escritorio_id' => $filtro_escritorio !== 'todos' ? $filtro_escritorio : (financeiroEhAdmin() ? 'todos' : null),
            'tipo'          => !empty($filtro_tipo) ? $filtro_tipo : null,
            'data_ini'      => !empty($filtro_data_ini) ? $filtro_data_ini : null,
            'data_fim'      => !empty($filtro_data_fim) ? $filtro_data_fim : null,
            'categoria'     => !empty($filtro_categoria) ? $filtro_categoria : null,
            'por_pagina'    => $porPagina !== 15 ? $porPagina : null,
            'pagina'        => $paginaAtual > 1 ? $paginaAtual : null,
        ];
        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '' || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        $qs = http_build_query(array_filter($params, fn($val) => $val !== null && $val !== ''));
        return APP_URL . 'financeiro' . ($qs ? '?' . $qs : '');
    }
}

// Construir query com filtros
$parcelasFinanceirasDisponiveis = false;
try {
    $stmtColunasParcelas = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'financeiro_lancamentos'
          AND COLUMN_NAME IN ('numero_parcela', 'total_parcelas')
    ");
    $parcelasFinanceirasDisponiveis = (int)$stmtColunasParcelas->fetchColumn() === 2;
} catch (Throwable $e) {
    error_log('Não foi possível verificar as colunas de parcelas do Financeiro: ' . $e->getMessage());
}
$camposParcelas = $parcelasFinanceirasDisponiveis
    ? 'numero_parcela, total_parcelas'
    : 'NULL AS numero_parcela, NULL AS total_parcelas';

$whereLancamentos = "WHERE ativo = 1";
$params = [];
if ($filtro_escritorio !== 'todos') { $whereLancamentos .= ' AND escritorio_id = :escritorio'; $params[':escritorio']=$filtro_escritorio; }

if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
    $whereLancamentos .= " AND tipo = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_data_ini)) {
    $whereLancamentos .= " AND data >= :data_ini";
    $params[':data_ini'] = $filtro_data_ini;
}

if (!empty($filtro_data_fim)) {
    $whereLancamentos .= " AND data <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
}

if (!empty($filtro_categoria)) {
    $whereLancamentos .= " AND categoria LIKE :categoria";
    $params[':categoria'] = '%' . $filtro_categoria . '%';
}

// Obter contagem total de registros filtrados para a paginação
$totalLancamentos = 0;
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM financeiro_lancamentos {$whereLancamentos}");
    $stmtCount->execute($params);
    $totalLancamentos = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {
    error_log('Erro ao contar lancamentos: ' . $e->getMessage());
    $totalLancamentos = 0;
}

$totalPaginas = max(1, (int)ceil($totalLancamentos / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;
$registroInicio = $totalLancamentos > 0 ? $offset + 1 : 0;
$registroFim = min($offset + $porPagina, $totalLancamentos);

$sql = "SELECT id, escritorio_id, responsavel_usuario_id, tipo, descricao, valor, valor_original, saldo_devedor, status, data, data_vencimento, {$camposParcelas}, categoria, observacoes, criado_em, atualizado_em FROM financeiro_lancamentos {$whereLancamentos} ORDER BY data DESC, criado_em DESC LIMIT :limite OFFSET :offset";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar lancamentos: ' . $e->getMessage());
    $lancamentos = [];
}

// Calcular totais de caixa e valores em aberto (usando os mesmos filtros).
try {
    $sqlTotais = "
        SELECT
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' THEN
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN l.status = 'PAGO' THEN l.valor_original ELSE 0 END
            ELSE 0 END), 0) AS total_receitas,
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' AND l.status IN ('PENDENTE', 'PARCIAL') THEN l.saldo_devedor ELSE 0 END), 0) AS total_a_receber,
            COALESCE(SUM(CASE WHEN l.tipo = 'DESPESA' THEN
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN l.status = 'PAGO' THEN l.valor_original ELSE 0 END
            ELSE 0 END), 0) AS total_despesas
        FROM financeiro_lancamentos l
        LEFT JOIN (
            SELECT lancamento_id, SUM(valor_pago) AS total_baixado
            FROM financeiro_historico_baixas
            GROUP BY lancamento_id
        ) b ON b.lancamento_id = l.id
        WHERE l.ativo = 1 AND l.status != 'CANCELADO'
    ";
    $paramsTotais = [];
    if ($filtro_escritorio !== 'todos') { $sqlTotais .= ' AND l.escritorio_id = :escritorio'; $paramsTotais[':escritorio']=$filtro_escritorio; }

    if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
        $sqlTotais .= " AND l.tipo = :tipo";
        $paramsTotais[':tipo'] = $filtro_tipo;
    }
    if (!empty($filtro_data_ini)) {
        $sqlTotais .= " AND l.data >= :data_ini";
        $paramsTotais[':data_ini'] = $filtro_data_ini;
    }
    if (!empty($filtro_data_fim)) {
        $sqlTotais .= " AND l.data <= :data_fim";
        $paramsTotais[':data_fim'] = $filtro_data_fim;
    }
    if (!empty($filtro_categoria)) {
        $sqlTotais .= " AND l.categoria LIKE :categoria";
        $paramsTotais[':categoria'] = '%' . $filtro_categoria . '%';
    }

    $stmtTotais = $pdo->prepare($sqlTotais);
    $stmtTotais->execute($paramsTotais);
    $totais = $stmtTotais->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $totais = [];
}

$totalReceitas = floatval($totais['total_receitas'] ?? 0);
$totalAReceber = floatval($totais['total_a_receber'] ?? 0);
$totalDespesas = floatval($totais['total_despesas'] ?? 0);
$saldo = $totalReceitas - $totalDespesas;

// Buscar categorias distintas para o filtro
try {
    $sqlCategorias="SELECT DISTINCT categoria FROM financeiro_lancamentos WHERE categoria IS NOT NULL AND categoria != ''";
    $paramsCategorias=[];
    if($filtro_escritorio!=='todos'){$sqlCategorias.=' AND escritorio_id=:escritorio';$paramsCategorias[':escritorio']=$filtro_escritorio;}
    $sqlCategorias.=' ORDER BY categoria ASC';$stmtCategorias=$pdo->prepare($sqlCategorias);$stmtCategorias->execute($paramsCategorias);$categorias=$stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categorias = [];
}

$formasPagamento = [
    'parcelado' => 'Parcelado (cartão / boleto parcelado)',
    'a_vista' => 'À Vista',
    'boleto' => 'Boleto Bancário',
    'pix' => 'PIX',
];

$csrf = gerarCSRF();

$titulo_page = 'Financeiro - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container finance-page">
        <div class="tabela-header">
            <h3><i class="fas fa-dollar-sign"></i> Financeiro</h3>
            <a href="<?php echo APP_URL; ?>financeiro/form?escritorio_id=<?= urlencode($filtro_escritorio) ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Lancamento
            </a>
        </div>

        <!-- Cards de resumo -->
        <section class="finance-summary-grid" aria-label="Resumo financeiro">
            <!-- Total Receitas -->
            <article class="finance-summary-card is-income">
                <div class="finance-summary-content">
                    <div class="finance-summary-icon"><i class="fas fa-arrow-up"></i>
                    </div>
                    <div>
                        <div class="finance-summary-label">Receitas</div>
                        <div class="finance-summary-value"><?php echo formatarMoeda($totalReceitas); ?></div>
                    </div>
                </div>
            </article>

            <!-- A Receber -->
            <article class="finance-summary-card is-receivable">
                <div class="finance-summary-content">
                    <div class="finance-summary-icon"><i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="finance-summary-label">A receber</div>
                        <div class="finance-summary-value"><?php echo formatarMoeda($totalAReceber); ?></div>
                    </div>
                </div>
            </article>

            <!-- Total Despesas -->
            <article class="finance-summary-card is-expense">
                <div class="finance-summary-content">
                    <div class="finance-summary-icon"><i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <div class="finance-summary-label">Despesas</div>
                        <div class="finance-summary-value"><?php echo formatarMoeda($totalDespesas); ?></div>
                    </div>
                </div>
            </article>

            <!-- Saldo -->
            <article class="finance-summary-card is-balance <?= $saldo >= 0 ? 'is-positive' : 'is-negative' ?>">
                <div class="finance-summary-content">
                    <div class="finance-summary-icon"><i class="fas fa-balance-scale"></i>
                    </div>
                    <div>
                        <div class="finance-summary-label">Saldo</div>
                        <div class="finance-summary-value"><?php echo formatarMoeda($saldo); ?></div>
                    </div>
                </div>
            </article>
        </section>

        <!-- Filtros -->
        <form method="GET" action="<?php echo APP_URL; ?>financeiro" class="finance-filter-form">
            <?php if ($porPagina !== 15): ?>
                <input type="hidden" name="por_pagina" value="<?php echo (int)$porPagina; ?>">
            <?php endif; ?>
            <div class="finance-filter-fields">
                <div class="form-group finance-filter-office"><label><i class="fas fa-building"></i> Escritório</label><select name="escritorio_id" <?= $podeSelecionarEscritorio?'':'disabled' ?>><?php if(financeiroEhAdmin()): ?><option value="todos" <?= $filtro_escritorio==='todos'?'selected':'' ?>>Todos os escritórios</option><?php endif ?><?php foreach($escritorios as $e): ?><option value="<?= h($e['id']) ?>" <?= $filtro_escritorio===$e['id']?'selected':'' ?>><?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></option><?php endforeach ?></select><?php if(!$podeSelecionarEscritorio): ?><input type="hidden" name="escritorio_id" value="<?= h($filtro_escritorio) ?>"><?php endif ?></div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="RECEITA" <?php echo $filtro_tipo === 'RECEITA' ? 'selected' : ''; ?>>Receita</option>
                        <option value="DESPESA" <?php echo $filtro_tipo === 'DESPESA' ? 'selected' : ''; ?>>Despesa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Data Início</label>
                    <input type="date" name="data_ini" value="<?php echo h($filtro_data_ini); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo h($filtro_data_fim); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-folder"></i> Categoria</label>
                    <input type="text" name="categoria" value="<?php echo h($filtro_categoria); ?>" placeholder="Buscar...">
                </div>
                <div class="form-group finance-filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
                <?php if (!empty($filtro_tipo) || !empty($filtro_data_ini) || !empty($filtro_data_fim) || !empty($filtro_categoria) || $filtro_escritorio!=='todos'): ?>
                <div class="form-group finance-filter-actions">
                    <a href="<?php echo financeiroUrlPaginacao(['tipo' => '', 'data_ini' => '', 'data_fim' => '', 'categoria' => '', 'pagina' => 1, 'escritorio_id'=>financeiroEhAdmin()?'todos':$filtro_escritorio]); ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($lancamentos)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-dollar-sign"></i>
                <h3>Nenhum lancamento encontrado</h3>
                <p>Clique em "Novo Lancamento" para cadastrar o primeiro.</p>
            </div>
        <?php else: ?>
            <div class="finance-table-scroll" tabindex="0" aria-label="Tabela de lançamentos financeiros.">
            <table class="finance-transactions-table table-sticky-actions">
                <thead>
                    <tr>
                        <?php if($filtro_escritorio==='todos'): ?><th>Escritório</th><?php endif ?>
                        <th>Data / Vencimento</th>
                        <th>Parcela</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lancamentos as $l): ?>
                    <tr>
                        <?php if($filtro_escritorio==='todos'): ?><td><strong><?= h($escritoriosPorId[$l['escritorio_id']]['nome']??'Escritório') ?></strong></td><?php endif ?>
                        <td data-label="Data / vencimento">
                            <?php echo formatarData($l['data']); ?>
                            <?php if (!empty($l['data_vencimento']) && $l['data_vencimento'] !== $l['data']): ?>
                                <br><small class="text-muted">Vencimento: <?php echo formatarData($l['data_vencimento']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Parcela">
                            <?php if ($l['numero_parcela'] !== null): ?>
                                <?php if ((int)$l['numero_parcela'] === 0): ?>
                                    <span class="badge badge-info">Entrada</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><?php echo (int)$l['numero_parcela']; ?>/<?php echo (int)$l['total_parcelas']; ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="finance-transaction-type">
                            <?php if ($l['tipo'] === 'RECEITA'): ?>
                                <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Receita</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Despesa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($l['status'] === 'PAGO'): ?>
                                <span class="badge badge-success">Pago</span>
                            <?php elseif ($l['status'] === 'PARCIAL'): ?>
                                <span class="badge" style="background:#6f42c1;color:#fff;">Parcial</span>
                            <?php elseif ($l['status'] === 'PENDENTE'): ?>
                                <span class="badge badge-warning">Pendente</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Cancelado</span>
                            <?php endif; ?>
                        </td>
                        <td class="finance-transaction-description">
                            <strong title="<?php echo h($l['descricao']); ?>"><?php echo h($l['descricao']); ?></strong>
                            <?php if (!empty($l['observacoes'])): ?>
                                <small class="text-muted" title="<?php echo h($l['observacoes']); ?>"><?php echo h(mb_strimwidth($l['observacoes'], 0, 75, '...')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($l['categoria'])): ?>
                                <span class="badge badge-info"><?php echo h($l['categoria']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="finance-transaction-value <?= $l['tipo'] === 'RECEITA' ? 'is-income' : 'is-expense' ?>">
                            <?php $valorExibido = $l['status'] === 'PAGO' ? $l['valor_original'] : $l['saldo_devedor']; ?>
                            <strong><?php echo $l['tipo'] === 'RECEITA' ? '+' : '-'; ?> <?php echo formatarMoeda($valorExibido); ?></strong>
                            <?php if ($l['status'] === 'PARCIAL'): ?>
                                <br><small class="text-muted">de <?php echo formatarMoeda($l['valor_original']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="finance-transaction-actions text-right">
                            <div class="finance-action-buttons">
                                <?php if (in_array($l['status'], ['PENDENTE', 'PARCIAL'], true)): ?>
                                <button type="button"
                                        class="btn-baixa-financeiro js-abrir-baixa"
                                        title="Registrar baixa neste lançamento (dar entrada/saída de valor em caixa)"
                                        data-tooltip="Registrar Baixa / Pagamento"
                                        aria-label="Baixar lançamento <?php echo h($l['descricao']); ?>"
                                        data-id="<?php echo h($l['id']); ?>"
                                        data-descricao="<?php echo h($l['descricao']); ?>"
                                        data-saldo="<?php echo h(number_format((float)$l['saldo_devedor'], 2, '.', '')); ?>"
                                        data-tipo="<?php echo h($l['tipo']); ?>">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Baixar</span>
                                </button>
                                <?php elseif ($l['status'] === 'PAGO'): ?>
                                <span class="badge-quitado" title="Lançamento quitado integralmente">
                                    <i class="fas fa-check-double"></i> Quitado
                                </span>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>financeiro/form?id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar lançamento">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>financeiro/actions?action=excluir&id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-danger btn-sm" title="Excluir lançamento"
                                   onclick="return confirm('Excluir este lancamento?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="finance-scroll-hint"><i class="fas fa-arrows-left-right"></i> Arraste a tabela para o lado para ver todas as colunas.</p>
        <?php endif; ?>

        <!-- Rodapé com Paginação e Resumo Financeiro -->
        <div class="card-footer finance-page-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-top: 1px solid var(--cor-borda);">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando <strong><?php echo $registroInicio; ?></strong> a <strong><?php echo $registroFim; ?></strong> de <strong><?php echo $totalLancamentos; ?></strong> lançamento(s)
                    <?php if ($filtro_escritorio !== 'todos'): ?>
                        em <strong><?= h($escritoriosPorId[$filtro_escritorio]['nome'] ?? 'Escritório') ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($filtro_tipo)): ?>
                        (tipo: <strong><?php echo ucfirst(strtolower($filtro_tipo)); ?></strong>)
                    <?php endif; ?>
                </small>

                <!-- Seletor de Itens por Página -->
                <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <label for="selectPorPaginaFinanceiro" style="margin: 0; font-size: 0.8rem; color: var(--cor-texto-secundario); white-space: nowrap;">Exibir:</label>
                    <select id="selectPorPaginaFinanceiro" 
                            class="form-control form-control-sm" 
                            style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 4px;" 
                            onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?php echo h(financeiroUrlPaginacao(['por_pagina' => $qtd, 'pagina' => 1])); ?>" <?php echo $porPagina === $qtd ? 'selected' : ''; ?>>
                                <?php echo $qtd; ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Navegação de Páginas -->
            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas do financeiro">
                    <ul class="paginacao-financeiro" style="display: flex; align-items: center; gap: 5px; margin: 0; padding: 0; list-style: none;">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h(financeiroUrlPaginacao(['pagina' => 1])); ?>" 
                               title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h(financeiroUrlPaginacao(['pagina' => $paginaAtual - 1])); ?>" 
                               title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de páginas -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(financeiroUrlPaginacao(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link active-link" style="background: var(--cor-primaria, #1e4d3f); color: #fff; border-color: var(--cor-primaria, #1e4d3f);">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(financeiroUrlPaginacao(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(financeiroUrlPaginacao(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(financeiroUrlPaginacao(['pagina' => $paginaAtual + 1])); ?>" 
                               title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(financeiroUrlPaginacao(['pagina' => $totalPaginas])); ?>" 
                               title="Última página">
                                <i class="fas fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="modalBaixa" class="financeiro-modal" aria-hidden="true">
    <div class="financeiro-modal__backdrop" data-fechar-baixa></div>
    <section class="financeiro-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloModalBaixa">
        <header class="financeiro-modal__header">
            <div class="financeiro-modal__title-area">
                <span id="subtituloModalBaixa" class="financeiro-modal__eyebrow">Registrar movimentação</span>
                <h3 id="tituloModalBaixa" class="financeiro-modal__title">
                    <i class="fas fa-check-circle text-success"></i> Baixar lançamento
                </h3>
            </div>
            <button type="button" class="financeiro-modal__close" data-fechar-baixa aria-label="Fechar" title="Fechar janela">
                <i class="fas fa-times"></i>
            </button>
        </header>
        <form method="POST" action="<?php echo APP_URL; ?>financeiro/actions?action=baixar" id="formBaixa" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
            <input type="hidden" name="escritorio_id" value="<?= h($filtro_escritorio) ?>">
            <input type="hidden" name="lancamento_id" id="baixaLancamentoId">
            <div class="financeiro-modal__body">
                <!-- Card de Resumo do Lançamento em Fundo Claro e Nítido -->
                <div class="baixa-resumo">
                    <div class="baixa-resumo__item">
                        <span class="baixa-resumo__label"><i class="fas fa-file-invoice text-muted"></i> Lançamento</span>
                        <strong id="baixaDescricao" class="baixa-resumo__desc"></strong>
                    </div>
                    <div class="baixa-resumo__item" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                        <span class="baixa-resumo__label"><i class="fas fa-wallet text-muted"></i> Saldo devedor atual</span>
                        <strong id="baixaSaldo" class="baixa-resumo__saldo"></strong>
                    </div>
                </div>

                <!-- Campo de Valor a Baixar -->
                <div class="form-group mb-3">
                    <label for="baixaValor" class="financeiro-form-label">
                        <i class="fas fa-money-bill-wave text-success"></i> 
                        <span id="baixaValorLabel">Valor a pagar/receber</span> <span class="text-danger">*</span>
                    </label>
                    <div class="input-money-container">
                        <span class="input-money-symbol">R$</span>
                        <input type="text" id="baixaValor" name="valor_pago" class="input-money-field" inputmode="decimal" required maxlength="15" placeholder="0,00">
                    </div>
                    <small class="financeiro-help-text">Digite exatamente o valor que entrou ou saiu do caixa.</small>
                    
                    <!-- Previsão do Saldo Restante -->
                    <div id="baixaSaldoRestante" class="baixa-saldo-restante" aria-live="polite">
                        <span class="baixa-saldo-restante__text">
                            <i class="fas fa-calculator mr-1"></i> Após esta baixa, ficará faltando:
                        </span>
                        <strong id="baixaSaldoRestanteValor" class="baixa-saldo-restante__val">R$ 0,00</strong>
                    </div>
                </div>

                <!-- Grid de Data e Forma de Pagamento -->
                <div class="financeiro-grid-2">
                    <div class="form-group">
                        <label for="baixaData" class="financeiro-form-label">
                            <i class="fas fa-calendar-check text-primary"></i> Data do pagamento <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="baixaData" name="data_pagamento" class="financeiro-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="baixaFormaPagamento" class="financeiro-form-label">
                            <i class="fas fa-credit-card text-primary"></i> Forma de pagamento <span class="text-danger">*</span>
                        </label>
                        <select id="baixaFormaPagamento" name="forma_pagamento" class="financeiro-control" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($formasPagamento as $valorForma => $nomeForma): ?>
                                <option value="<?php echo h($valorForma); ?>"><?php echo h($nomeForma); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Upload de Comprovante -->
                <div class="form-group mb-0">
                    <label for="baixaComprovantes" class="financeiro-form-label">
                        <i class="fas fa-paperclip text-muted"></i> Comprovante de Pagamento
                    </label>
                    <div class="financeiro-upload-wrapper">
                        <input type="file"
                               id="baixaComprovantes"
                               name="comprovantes[]"
                               class="financeiro-file-input"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                               multiple>
                    </div>
                    <small class="financeiro-help-text">PDF ou imagem, até 10MB por arquivo. O anexo ficará disponível no histórico deste lançamento.</small>
                    <div id="baixaArquivosSelecionados" class="baixa-arquivos" hidden></div>
                </div>
            </div>
            <footer class="financeiro-modal__footer">
                <button type="button" class="btn-modal-cancelar" data-fechar-baixa>Cancelar</button>
                <button type="submit" class="btn-modal-confirmar" id="confirmarBaixa">
                    <i class="fas fa-check"></i> Confirmar baixa
                </button>
            </footer>
        </form>
    </section>
</div>

<style>
.finance-page { overflow: visible !important; }
.finance-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:15px; margin:15px 20px; }
.finance-summary-card { min-width:0; padding:18px; border:1px solid var(--cor-borda); border-left:4px solid currentColor; border-radius:12px; background:linear-gradient(135deg,var(--cor-painel),var(--cor-sidebar)); }
.finance-summary-card.is-income,.finance-summary-card.is-balance.is-positive { color:var(--cor-sucesso); }
.finance-summary-card.is-receivable { color:var(--status-pending,#d29922); }
.finance-summary-card.is-expense,.finance-summary-card.is-balance.is-negative { color:var(--cor-erro); }
.finance-summary-content { display:flex; align-items:center; gap:12px; min-width:0; }
.finance-summary-icon { width:45px; height:45px; flex:0 0 45px; display:grid; place-items:center; border-radius:10px; color:currentColor; background:color-mix(in srgb,currentColor 15%,transparent); font-size:1.2rem; }
.finance-summary-label { color:var(--cor-texto-secundario); font-size:.8rem; font-weight:600; text-transform:uppercase; }
.finance-summary-value { margin-top:3px; color:currentColor; font-size:clamp(1.2rem,2vw,1.4rem); font-weight:700; white-space:nowrap; }
.finance-filter-form { margin:0 20px 15px; }
.finance-filter-fields { display:grid; grid-template-columns:minmax(190px,1.6fr) repeat(4,minmax(120px,1fr)) auto auto; gap:10px; align-items:end; }
.finance-filter-fields .form-group { min-width:0; margin:0; }
.finance-filter-fields label { display:block; margin-bottom:5px; font-size:.8rem; }
.finance-filter-fields select,.finance-filter-fields input { width:100%; min-height:42px; padding:8px 10px; box-sizing:border-box; }
.finance-filter-actions .btn { min-height:42px; white-space:nowrap; }
.finance-table-scroll { width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; scrollbar-gutter:stable; position:relative; }
.finance-scroll-hint { display:none; margin:8px 20px 0; color:var(--cor-texto-secundario); font-size:.78rem; }
.finance-transactions-table { width:100% !important; min-width:960px !important; table-layout:auto !important; border-collapse:separate !important; border-spacing:0 !important; }
.finance-transactions-table th,.finance-transactions-table td { padding:12px 14px !important; vertical-align:middle !important; border-bottom:1px solid var(--cor-borda) !important; background:#ffffff; }
.finance-transactions-table thead th { background:#f8faf9 !important; color:#475569 !important; font-size:0.82rem !important; font-weight:700 !important; text-transform:uppercase !important; letter-spacing:0.04em !important; white-space:nowrap !important; }
.finance-transactions-table tbody tr:hover td { background-color:#f8fafc !important; }
.finance-transaction-description { max-width:280px; min-width:180px; }
.finance-transaction-description strong { display:block; line-height:1.35; word-break:break-word; }
.finance-transaction-value { min-width:115px !important; white-space:nowrap !important; }
.finance-transaction-value strong { font-size:0.98rem; }.finance-transaction-value.is-income{color:var(--cor-sucesso)}.finance-transaction-value.is-expense{color:var(--cor-erro)}
.finance-transactions-table th:last-child,
.finance-transactions-table td:last-child {
    position:sticky !important;
    right:0 !important;
    z-index:4 !important;
    min-width:160px !important;
    width:160px !important;
    text-align:right !important;
    box-shadow:-4px 0 10px rgba(15,23,42,0.07) !important;
    background:#ffffff !important;
}
.finance-transactions-table thead th:last-child { background:#f1f5f9 !important; }
.finance-transactions-table tbody tr:hover td:last-child { background:#f1f5f9 !important; }
.finance-action-buttons { display:inline-flex; align-items:center; justify-content:flex-end; gap:6px; flex-wrap:nowrap; }

/* Paginação Financeiro */
.paginacao-financeiro { display:flex; align-items:center; gap:5px; margin:0; padding:0; list-style:none; }
.paginacao-item { display:inline-block; }
.paginacao-link {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:34px;
    height:34px;
    padding:0 8px;
    border-radius:6px;
    border:1px solid var(--cor-borda, #e2e8f0);
    background:var(--cor-card-bg, #ffffff);
    color:var(--cor-texto, #334155);
    font-size:0.85rem;
    font-weight:500;
    text-decoration:none;
    transition:all 0.15s ease;
    user-select:none;
}
.paginacao-link:hover:not(.disabled) {
    border-color:var(--cor-primaria, #1e4d3f);
    color:var(--cor-primaria, #1e4d3f);
    background:rgba(30,77,63,0.06);
}
.paginacao-item.active .paginacao-link {
    background:var(--cor-primaria, #1e4d3f) !important;
    border-color:var(--cor-primaria, #1e4d3f) !important;
    color:#ffffff !important;
    font-weight:700;
    box-shadow:0 2px 4px rgba(30,77,63,0.25);
}
.paginacao-item.disabled .paginacao-link {
    color:#cbd5e1 !important;
    border-color:#f1f5f9 !important;
    background:#f8fafc !important;
    cursor:not-allowed;
    opacity:0.7;
}
.paginacao-ellipsis {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:28px;
    height:34px;
    color:#94a3b8;
    font-size:0.9rem;
}
.finance-page-footer { padding:14px 20px; }
@media (max-width:1180px) { .finance-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-filter-fields{grid-template-columns:repeat(3,minmax(0,1fr))}.finance-filter-office{grid-column:span 2}.finance-filter-actions{width:100%}.finance-filter-actions .btn{width:100%;justify-content:center} }
@media (min-width:769px) and (max-width:1400px) { .finance-scroll-hint{display:block} }
@media (max-width:768px) { .finance-page{overflow:visible!important}.finance-summary-grid{grid-template-columns:1fr;margin:12px}.finance-summary-card{padding:15px}.finance-filter-form{margin:0 12px 14px}.finance-filter-fields{grid-template-columns:1fr}.finance-filter-office{grid-column:auto}.finance-filter-actions .btn{width:100%}.finance-table-scroll{overflow:visible}.finance-transactions-table,.finance-transactions-table tbody,.finance-transactions-table tr,.finance-transactions-table td{display:block!important;width:100%!important;min-width:0!important;max-width:100%!important}.finance-transactions-table thead{display:none!important}.finance-transactions-table{background:transparent!important}.finance-transactions-table tbody{background:transparent!important}.finance-transactions-table tbody tr{margin:0 12px 12px;padding:12px 14px;overflow:hidden;border:1px solid var(--cor-borda)!important;border-radius:12px;background:var(--cor-painel);box-shadow:0 2px 10px rgba(14,52,43,.035)}.finance-transactions-table td{min-height:38px;padding:8px 0!important;display:grid!important;grid-template-columns:minmax(96px,38%) minmax(0,1fr);align-items:start;gap:12px;border:0!important;border-bottom:1px solid var(--cor-borda)!important;background:transparent!important;text-align:left!important;white-space:normal!important;overflow-wrap:anywhere!important}.finance-transactions-table td:last-child{border-bottom:0!important}.finance-transactions-table td::before{content:attr(data-label);color:var(--cor-texto-secundario);font-size:10px;font-weight:800;letter-spacing:.045em;text-transform:uppercase}.finance-transactions-table td[data-label="Acoes"],.finance-transactions-table td[data-label="Ações"]{grid-template-columns:1fr}.finance-action-buttons{display:flex;gap:8px}.finance-action-buttons .btn{min-width:42px;min-height:42px}.finance-page-footer{padding:12px}.finance-page-footer small{display:block;line-height:1.65} }
@media (max-width:390px) { .finance-transactions-table td{grid-template-columns:1fr;gap:4px}.finance-transactions-table td::before{margin-bottom:2px} }

/* ==============================================================
   DESIGN DO MODAL DE BAIXA FINANCEIRA (TEMA CLARO & ALTO CONTRASTE)
   ============================================================== */
.financeiro-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.financeiro-modal.is-open {
    display: flex;
}
.financeiro-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    animation: modalFadeBackdrop 0.2s ease-out;
}
@keyframes modalFadeBackdrop {
    from { opacity: 0; }
    to { opacity: 1; }
}
.financeiro-modal__dialog {
    position: relative;
    width: min(540px, 100%);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.04);
    animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}
@keyframes modalPopIn {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.financeiro-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 24px;
    background: #ffffff !important;
    border-bottom: 1px solid #f1f5f9;
    position: sticky;
    top: 0;
    z-index: 5;
}
.financeiro-modal__title-area {
    display: flex;
    flex-direction: column;
}
.financeiro-modal__eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #059669 !important;
    margin-bottom: 3px;
}
.financeiro-modal__title {
    margin: 0 !important;
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    display: flex;
    align-items: center;
    gap: 8px;
}
.financeiro-modal__close {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    border-radius: 8px;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.financeiro-modal__close:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
    transform: rotate(90deg);
}
.financeiro-modal__body {
    padding: 24px;
    background: #ffffff !important;
    color: #1e293b !important;
}

/* Card de Resumo do Lançamento */
.baixa-resumo {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 20px;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
}
.baixa-resumo__item {
    display: flex;
    flex-direction: column;
}
.baixa-resumo__label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b !important;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}
.baixa-resumo__desc {
    color: #0f172a !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
    line-height: 1.45;
}
.baixa-resumo__saldo {
    font-size: 1.5rem !important;
    font-weight: 800 !important;
    color: #047857 !important;
    letter-spacing: -0.02em;
}

/* Rótulos e Campos de Formulário */
.financeiro-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600 !important;
    font-size: 0.88rem !important;
    color: #334155 !important;
    margin-bottom: 6px;
}
.financeiro-help-text {
    display: block;
    margin-top: 4px;
    color: #64748b !important;
    font-size: 0.78rem;
    line-height: 1.35;
}

/* Campo de Valor com Prefixo R$ em Destaque */
.input-money-container {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.input-money-symbol {
    position: absolute;
    left: 14px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #059669;
    pointer-events: none;
}
.input-money-field {
    width: 100% !important;
    min-height: 48px !important;
    padding: 8px 14px 8px 46px !important;
    font-size: 1.35rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    background: #ffffff !important;
    border: 2px solid #cbd5e1 !important;
    border-radius: 10px !important;
    outline: none !important;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.input-money-field:focus {
    border-color: #059669 !important;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15) !important;
}

/* Card de Previsão de Saldo Restante */
.baixa-saldo-restante {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 10px;
    padding: 12px 16px;
    border: 1px solid #bbf7d0 !important;
    border-radius: 10px;
    background: #f0fdf4 !important;
    transition: all 0.2s ease;
}
.baixa-saldo-restante__text {
    font-size: 0.85rem;
    font-weight: 600;
    color: #166534 !important;
    display: flex;
    align-items: center;
    gap: 5px;
}
.baixa-saldo-restante__val {
    font-size: 1.2rem !important;
    font-weight: 800 !important;
    color: #047857 !important;
}
.baixa-saldo-restante.is-error {
    border-color: #fecaca !important;
    background: #fef2f2 !important;
}
.baixa-saldo-restante.is-error .baixa-saldo-restante__text {
    color: #991b1b !important;
}
.baixa-saldo-restante.is-error .baixa-saldo-restante__val {
    color: #dc2626 !important;
}

/* Grid com Data e Forma de Pagamento */
.financeiro-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 16px;
    margin-bottom: 16px;
}
.financeiro-control {
    width: 100% !important;
    min-height: 42px !important;
    padding: 8px 12px !important;
    font-size: 0.92rem !important;
    color: #0f172a !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    outline: none !important;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.financeiro-control:focus {
    border-color: #059669 !important;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12) !important;
}

/* Upload de Comprovante */
.financeiro-upload-wrapper {
    margin-top: 4px;
}
.financeiro-file-input {
    width: 100% !important;
    min-height: 40px !important;
    padding: 7px 10px !important;
    font-size: 0.85rem !important;
    color: #475569 !important;
    background: #f8fafc !important;
    border: 1px dashed #94a3b8 !important;
    border-radius: 8px !important;
    cursor: pointer;
    box-sizing: border-box;
}
.financeiro-file-input:hover {
    background: #f1f5f9 !important;
    border-color: #059669 !important;
}
.baixa-arquivos {
    margin-top: 8px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    font-size: 0.85rem;
}
.baixa-arquivos div + div {
    margin-top: 4px;
}

/* Rodapé do Modal */
.financeiro-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 18px 24px;
    background: #f8fafc !important;
    border-top: 1px solid #f1f5f9;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    position: sticky;
    bottom: 0;
    z-index: 5;
}
.btn-modal-cancelar {
    min-height: 42px;
    padding: 0 20px;
    background: #ffffff !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-modal-cancelar:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
    border-color: #94a3b8 !important;
}
.btn-modal-confirmar {
    min-height: 42px;
    padding: 0 22px;
    background: #059669 !important;
    color: #ffffff !important;
    border: 1px solid #047857 !important;
    border-radius: 8px !important;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
    transition: all 0.15s ease;
}
.btn-modal-confirmar:hover:not(:disabled) {
    background: #047857 !important;
    box-shadow: 0 4px 8px rgba(5, 150, 105, 0.28);
    transform: translateY(-1px);
}
.btn-modal-confirmar:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

@media (max-width: 540px) {
    .financeiro-modal { padding: 12px; }
    .financeiro-modal__header, .financeiro-modal__body { padding: 16px; }
    .financeiro-grid-2 { grid-template-columns: 1fr; gap: 10px; }
    .financeiro-modal__footer {
        padding: 14px 16px;
        flex-direction: column-reverse;
        align-items: stretch;
    }
    .btn-modal-cancelar, .btn-modal-confirmar {
        width: 100%;
        justify-content: center;
    }
    .baixa-saldo-restante {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBaixa');
    const idInput = document.getElementById('baixaLancamentoId');
    const descricao = document.getElementById('baixaDescricao');
    const saldo = document.getElementById('baixaSaldo');
    const valor = document.getElementById('baixaValor');
    const label = document.getElementById('baixaValorLabel');
    const subtitulo = document.getElementById('subtituloModalBaixa');
    const formaPagamento = document.getElementById('baixaFormaPagamento');
    const comprovantes = document.getElementById('baixaComprovantes');
    const arquivosSelecionados = document.getElementById('baixaArquivosSelecionados');
    const saldoRestanteBox = document.getElementById('baixaSaldoRestante');
    const saldoRestanteValor = document.getElementById('baixaSaldoRestanteValor');
    const confirmarBaixa = document.getElementById('confirmarBaixa');
    const formatar = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    let ultimoFoco = null;
    let saldoAtualModal = 0;

    function valorDigitado() {
        const normalizado = valor.value.replace(/\./g, '').replace(',', '.');
        return Number(normalizado || 0);
    }

    function atualizarSaldoRestante() {
        const recebido = valorDigitado();
        const excedeu = recebido > saldoAtualModal;
        const restante = Math.max(0, saldoAtualModal - recebido);
        saldoRestanteValor.textContent = formatar.format(restante);
        saldoRestanteBox.classList.toggle('is-error', excedeu);
        valor.setCustomValidity(excedeu ? 'O valor recebido não pode ser maior que o saldo devedor.' : '');
        confirmarBaixa.disabled = recebido <= 0 || excedeu;
    }

    function fecharModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (ultimoFoco) ultimoFoco.focus();
    }

    document.querySelectorAll('.js-abrir-baixa').forEach(function (botao) {
        botao.addEventListener('click', function () {
            const saldoAtual = Number(botao.dataset.saldo);
            const receita = botao.dataset.tipo === 'RECEITA';
            saldoAtualModal = saldoAtual;
            ultimoFoco = botao;
            idInput.value = botao.dataset.id;
            descricao.textContent = botao.dataset.descricao;
            saldo.textContent = formatar.format(saldoAtual);
            valor.value = saldoAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            formaPagamento.value = '';
            comprovantes.value = '';
            arquivosSelecionados.hidden = true;
            arquivosSelecionados.innerHTML = '';
            label.textContent = receita ? 'Valor a receber' : 'Valor a pagar';
            subtitulo.textContent = receita ? 'Registrar recebimento' : 'Registrar pagamento';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            atualizarSaldoRestante();
            window.setTimeout(function () { valor.focus(); valor.select(); }, 0);
        });
    });

    valor.addEventListener('input', function () {
        const digitos = valor.value.replace(/\D/g, '').slice(0, 12);
        const centavos = Number(digitos || 0) / 100;
        valor.value = centavos.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        atualizarSaldoRestante();
    });

    comprovantes.addEventListener('change', function () {
        const arquivos = Array.from(comprovantes.files || []);
        arquivosSelecionados.hidden = arquivos.length === 0;
        arquivosSelecionados.innerHTML = arquivos.map(function (arquivo) {
            return '<div><i class="fas fa-file"></i> ' + arquivo.name.replace(/[<>&"']/g, '') + '</div>';
        }).join('');
    });

    modal.querySelectorAll('[data-fechar-baixa]').forEach(function (elemento) {
        elemento.addEventListener('click', fecharModal);
    });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && modal.classList.contains('is-open')) fecharModal();
    });
    document.getElementById('formBaixa').addEventListener('submit', function (evento) {
        atualizarSaldoRestante();
        if (!idInput.value || !valor.value || !formaPagamento.value || !valor.checkValidity()) evento.preventDefault();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
