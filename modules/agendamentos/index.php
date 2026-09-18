<?php
/** Listagem responsiva de agendamentos com paginação. */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('agendamentos');

$usuario_id = $_SESSION['usuario_id'];
$filtro_status = trim($_GET['status'] ?? '');
$filtro_data = trim($_GET['data'] ?? '');
$busca = trim($_GET['busca'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$agendamentoUrl = function(array $novos = []) use (&$filtro_status, &$filtro_data, &$busca, &$paginaAtual, &$porPagina): string {
    $params = [
        'status' => $filtro_status !== '' ? $filtro_status : null,
        'data' => $filtro_data !== '' ? $filtro_data : null,
        'busca' => $busca !== '' ? $busca : null,
        'por_pagina' => (int)$porPagina !== 15 ? (int)$porPagina : null,
        'pagina' => (int)$paginaAtual > 1 ? (int)$paginaAtual : null,
    ];
    foreach ($novos as $k => $v) {
        if ($v === null || $v === '' || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $query = http_build_query($params);
    return APP_URL . 'agendamentos' . ($query ? '?' . $query : '');
};

function agendaTexto(?string $texto): string
{
    $texto = (string)$texto;
    if ($texto !== '' && preg_match('/(?:Ã.|Â.)/u', $texto)) {
        $corrigido = @iconv('UTF-8', 'Windows-1252//IGNORE', $texto);
        if ($corrigido !== false && mb_check_encoding($corrigido, 'UTF-8')) $texto = $corrigido;
    }
    return strtr($texto, [
        'Navega??o' => 'Navegação', 'Amaz?nia' => 'Amazônia', 'Par?' => 'Pará',
        'Bel?m' => 'Belém', 'Santar?m' => 'Santarém',
    ]);
}

$totalFiltrados = 0;
$totalPaginas = 1;
$offset = 0;
$registroInicio = 0;
$registroFim = 0;
$agendamentos = [];

// KPIs
$kpi_total = 0;
$kpi_hoje = 0;
$kpi_pendentes = 0;

try {
    $where = [];
    $params = [];
    $escapedAgIds = "''";
    if ($cargo === 'VISTORIADOR') {
        $uEmail = trim((string)($_SESSION['usuario_email'] ?? ''));
        $vistoriadorIds = array_values(array_filter([$usuario_id]));
        if ($uEmail !== '') {
            try {
                $stmtIds = $pdo->prepare("SELECT id FROM usuarios WHERE email = :mail");
                $stmtIds->execute([':mail' => $uEmail]);
                $idsEncontrados = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($idsEncontrados)) {
                    $vistoriadorIds = array_values(array_unique(array_merge($vistoriadorIds, $idsEncontrados)));
                }
            } catch (Throwable $e) {}
        }
        $escapedAgIds = "'" . implode("','", array_map('addslashes', $vistoriadorIds)) . "'";
        $where[] = "(a.vistoriador_id IN ({$escapedAgIds}) OR a.vistoriador_id IS NULL)";
    }

    // Calcular KPIs
    $sqlKpi = "SELECT COUNT(*) AS total,
                      SUM(CASE WHEN a.status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                      SUM(CASE WHEN a.data_vistoria = CURDATE() THEN 1 ELSE 0 END) AS hoje
               FROM agendamentos a";
    if ($cargo === 'VISTORIADOR') {
        $sqlKpi .= " WHERE (a.vistoriador_id IN ({$escapedAgIds}) OR a.vistoriador_id IS NULL)";
    }
    $kpiRow = $pdo->query($sqlKpi)->fetch(PDO::FETCH_ASSOC);
    $kpi_total = (int)($kpiRow['total'] ?? 0);
    $kpi_pendentes = (int)($kpiRow['pendentes'] ?? 0);
    $kpi_hoje = (int)($kpiRow['hoje'] ?? 0);

    if ($filtro_status !== '') {
        $where[] = 'a.status = :status';
        $params[':status'] = $filtro_status;
    }
    if ($filtro_data !== '') {
        $where[] = 'a.data_vistoria = :data_vistoria';
        $params[':data_vistoria'] = $filtro_data;
    }
    if ($busca !== '') {
        $where[] = '(c.nome LIKE :busca1 OR e.nome LIKE :busca2 OR a.tipo_vistoria LIKE :busca3 OR a.local LIKE :busca4)';
        foreach ([':busca1', ':busca2', ':busca3', ':busca4'] as $chave) $params[$chave] = '%' . $busca . '%';
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // Contagem total dos registros filtrados
    $sqlCount = "SELECT COUNT(*)
                 FROM agendamentos a
                 LEFT JOIN clientes c ON a.cliente_id = c.id
                 LEFT JOIN embarcacoes e ON a.embarcacao_id = e.id
                 LEFT JOIN usuarios u ON a.vistoriador_id = u.id
                 {$whereSql}";
    $stmtCount = $pdo->prepare($sqlCount);
    foreach ($params as $k => $v) {
        $stmtCount->bindValue($k, $v);
    }
    $stmtCount->execute();
    $totalFiltrados = (int)$stmtCount->fetchColumn();

    $totalPaginas = max(1, (int)ceil($totalFiltrados / $porPagina));
    if ($paginaAtual > $totalPaginas) {
        $paginaAtual = $totalPaginas;
    }
    $offset = ($paginaAtual - 1) * $porPagina;
    $registroInicio = $totalFiltrados > 0 ? $offset + 1 : 0;
    $registroFim = min($offset + $porPagina, $totalFiltrados);

    $sql = "
        SELECT a.*, c.nome AS cliente_nome, e.nome AS embarcacao_nome,
               u.nome AS vistoriador_nome, os.id AS os_id, os.numero AS os_numero,
               os.status AS os_status, v.id AS vistoria_id, v.status AS vistoria_status,
               vr.tipo AS retorno_tipo, vo.numero AS relatorio_origem_numero
        FROM agendamentos a
        LEFT JOIN vistorias v ON v.id = (
            SELECT v2.id FROM vistorias v2
             WHERE v2.agendamento_id = a.id
             ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1
        )
        LEFT JOIN clientes c ON a.cliente_id = c.id
        LEFT JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN usuarios u ON a.vistoriador_id = u.id
        LEFT JOIN ordens_servico os ON os.agendamento_id = a.id
        LEFT JOIN vistoria_retornos vr ON vr.agendamento_id = a.id
        LEFT JOIN vistorias vo ON vo.id = vr.relatorio_origem_id
        {$whereSql}
        ORDER BY CASE vr.tipo WHEN 'AS' THEN 0 WHEN 'EXIGENCIAS' THEN 1 ELSE 2 END,
            COALESCE(a.data_vistoria, DATE(a.created_at)) DESC, a.hora_vistoria DESC,
            COALESCE(a.updated_at, a.created_at) DESC
        LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao listar agendamentos: ' . $e->getMessage());
    $agendamentos = [];
}

$status_labels = [
    'pendente' => ['label' => 'Pendente', 'class' => 'warning'],
    'confirmado' => ['label' => 'Confirmado', 'class' => 'info'],
    'em_andamento' => ['label' => 'Em andamento', 'class' => 'progress'],
    'concluido' => ['label' => 'Concluído', 'class' => 'success'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'danger'],
];
$retorno_labels = [
    'AS' => ['label' => 'RETORNO A/S', 'class' => 'as'],
    'EXIGENCIAS' => ['label' => 'RETORNO - EXIGÊNCIAS', 'class' => 'requirements'],
];

$total_agendamentos = $kpi_total;
$total_pendentes = $kpi_pendentes;
$total_hoje = $kpi_hoje;
$filtros_ativos = ($filtro_status !== '' ? 1 : 0) + ($filtro_data !== '' ? 1 : 0) + ($busca !== '' ? 1 : 0);

$titulo_page = 'Agendamentos - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="schedule-page">
    <header class="schedule-page-header">
        <div>
            <h1>Agendamentos</h1>
            <p>Organize as próximas vistorias e acompanhe cada compromisso.</p>
        </div>
        <?php if ($cargo !== 'VISTORIADOR'): ?>
            <a href="<?= APP_URL ?>agendamentos/form" class="schedule-new-button"><i class="fa-solid fa-plus"></i> Novo agendamento</a>
        <?php endif; ?>
    </header>

    <section class="schedule-summary" aria-label="Resumo dos agendamentos">
        <div><span>Total</span><strong><?= $total_agendamentos ?></strong></div>
        <div><span>Hoje</span><strong><?= $total_hoje ?></strong></div>
        <div><span>Pendentes</span><strong><?= $total_pendentes ?></strong></div>
    </section>

    <button type="button" class="schedule-filter-toggle" aria-expanded="<?= $filtros_ativos ? 'true' : 'false' ?>" aria-controls="scheduleFilters">
        <span><i class="fa-solid fa-sliders"></i> Filtros<?= $filtros_ativos ? ' (' . $filtros_ativos . ')' : '' ?></span>
        <i class="fa-solid fa-chevron-down"></i>
    </button>
    <form id="scheduleFilters" class="schedule-filters <?= $filtros_ativos ? 'is-open' : '' ?>" method="get" action="<?= APP_URL ?>agendamentos">
        <?php if ($porPagina !== 15): ?>
            <input type="hidden" name="por_pagina" value="<?= (int)$porPagina ?>">
        <?php endif; ?>
        <label><span>Buscar</span><div class="schedule-input-icon"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="busca" value="<?= h($busca) ?>" placeholder="Cliente, embarcação, tipo ou local"></div></label>
        <label><span>Status</span><select name="status"><option value="">Todos os status</option><?php foreach ($status_labels as $valor => $info): ?><option value="<?= h($valor) ?>" <?= $filtro_status === $valor ? 'selected' : '' ?>><?= h($info['label']) ?></option><?php endforeach; ?></select></label>
        <label><span>Data</span><input type="date" name="data" value="<?= h($filtro_data) ?>"></label>
        <button type="submit" class="schedule-filter-submit"><i class="fa-solid fa-filter"></i> Aplicar filtros</button>
        <?php if ($filtros_ativos): ?><a href="<?= APP_URL ?>agendamentos" class="schedule-filter-clear">Limpar</a><?php endif; ?>
    </form>

    <?php if (empty($agendamentos)): ?>
        <section class="schedule-empty"><i class="fa-regular fa-calendar-check"></i><h2>Nenhum agendamento encontrado</h2><p><?= $cargo === 'VISTORIADOR' ? 'Você verá aqui apenas os agendamentos atribuídos a você.' : 'Ajuste os filtros ou crie um novo agendamento.' ?></p><?php if ($cargo !== 'VISTORIADOR'): ?><a href="<?= APP_URL ?>agendamentos/form">Novo agendamento</a><?php endif; ?></section>
    <?php else: ?>
        <section class="schedule-desktop-list">
            <div class="schedule-list-heading">
                <div>
                    <h2>Todos os agendamentos</h2>
                    <p><?= $totalFiltrados ?> registro<?= $totalFiltrados === 1 ? '' : 's' ?> encontrado<?= $totalFiltrados === 1 ? '' : 's' ?> <?= $totalFiltrados > 0 ? '• Exibindo ' . $registroInicio . ' a ' . $registroFim : '' ?></p>
                </div>
            </div>
            <div class="schedule-table-wrap">
                <table id="tabelaAgendamentos" class="schedule-table" data-responsive="off">
                    <thead><tr><th>Data e local</th><th>Cliente / embarcação</th><th>Tipo de vistoria</th><th>Vistoriador</th><th>Status</th><th>OS</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($agendamentos as $a):
                        $st = $status_labels[$a['status']] ?? ['label' => ucfirst($a['status']), 'class' => 'neutral'];
                        $retorno = $retorno_labels[$a['retorno_tipo'] ?? ''] ?? null;
                    ?>
                        <tr class="<?= $retorno ? 'schedule-return-row is-' . h($retorno['class']) : '' ?>">
                            <td><strong><?= !empty($a['data_vistoria']) ? formatarData($a['data_vistoria']) : 'Sem data' ?></strong><small><?= !empty($a['hora_vistoria']) ? h(substr($a['hora_vistoria'], 0, 5)) : 'Horário não definido' ?><?= !empty($a['local']) ? ' · ' . h(agendaTexto($a['local'])) : '' ?></small></td>
                            <td><strong><?= h(agendaTexto($a['embarcacao_nome'])) ?></strong><small><?= h(agendaTexto($a['cliente_nome'])) ?></small></td>
                            <td><?php if ($retorno): ?><span class="schedule-return-badge is-<?= h($retorno['class']) ?>"><?= h($retorno['label']) ?></span><?php endif; ?><?= h(agendaTexto($a['tipo_vistoria'] ?: 'Não informado')) ?><?php if (!empty($a['relatorio_origem_numero'])): ?><small>Origem: <?= h($a['relatorio_origem_numero']) ?></small><?php endif; ?></td>
                            <td><?= h(agendaTexto($a['vistoriador_nome'] ?: 'Não definido')) ?></td>
                            <td><span class="schedule-status schedule-status--<?= h($st['class']) ?>"><?= h($st['label']) ?></span></td>
                            <td><?= !empty($a['os_id']) ? '<a class="schedule-os" href="' . APP_URL . 'agendamentos/os?id=' . urlencode($a['os_id']) . '">' . h($a['os_numero']) . '</a>' : '<span class="schedule-muted">–</span>' ?></td>
                            <td><div class="schedule-table-actions"><a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($a['id']) ?><?= !empty($a['vistoria_id']) ? '&amp;vistoria_id=' . urlencode($a['vistoria_id']) : '' ?>" title="Abrir relatório"><i class="fa-solid fa-clipboard-list"></i></a><?php if ($cargo !== 'VISTORIADOR'): ?><a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($a['id']) ?>" title="Editar"><i class="fa-solid fa-pen"></i></a><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="schedule-mobile-list" aria-label="Lista de agendamentos">
            <?php foreach ($agendamentos as $a):
                $st = $status_labels[$a['status']] ?? ['label' => ucfirst($a['status']), 'class' => 'neutral'];
                $retorno = $retorno_labels[$a['retorno_tipo'] ?? ''] ?? null;
                $data_iso = $a['data_vistoria'] ?? '';
                $dia = $data_iso ? date('d', strtotime($data_iso)) : '–';
                $meses = ['01'=>'JAN','02'=>'FEV','03'=>'MAR','04'=>'ABR','05'=>'MAI','06'=>'JUN','07'=>'JUL','08'=>'AGO','09'=>'SET','10'=>'OUT','11'=>'NOV','12'=>'DEZ'];
                $mes = $data_iso ? ($meses[date('m', strtotime($data_iso))] ?? '') : 'DATA';
            ?>
                <article class="schedule-card<?= $retorno ? ' schedule-return-card is-' . h($retorno['class']) : '' ?>">
                    <div class="schedule-card-top">
                        <time datetime="<?= h($data_iso) ?>"><strong><?= $dia ?></strong><span><?= $mes ?></span></time>
                        <div class="schedule-card-heading"><span><?= !empty($a['hora_vistoria']) ? h(substr($a['hora_vistoria'], 0, 5)) : 'Sem horário' ?></span><h2><?= h(agendaTexto($a['embarcacao_nome'])) ?></h2><p><?= h(agendaTexto($a['cliente_nome'])) ?></p></div>
                        <span class="schedule-status schedule-status--<?= h($st['class']) ?>"><?= h($st['label']) ?></span>
                    </div>
                    <?php if ($retorno): ?><span class="schedule-return-badge is-<?= h($retorno['class']) ?>"><?= h($retorno['label']) ?></span><?php endif; ?>
                    <div class="schedule-card-meta">
                        <span><i class="fa-solid fa-location-dot"></i><?= h(agendaTexto($a['local'] ?: 'Local não informado')) ?></span>
                        <span><i class="fa-solid fa-clipboard-check"></i><?= h(agendaTexto($a['tipo_vistoria'] ?: 'Tipo não informado')) ?></span>
                        <span><i class="fa-solid fa-user-check"></i><?= h(agendaTexto($a['vistoriador_nome'] ?: 'Vistoriador não definido')) ?></span>
                    </div>
                    <a class="schedule-card-primary" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($a['id']) ?><?= !empty($a['vistoria_id']) ? '&amp;vistoria_id=' . urlencode($a['vistoria_id']) : '' ?>"><i class="fa-solid fa-clipboard-list"></i> Abrir relatório da vistoria</a>
                    <details class="schedule-card-more">
                        <summary>Detalhes e ações <i class="fa-solid fa-chevron-down"></i></summary>
                        <div class="schedule-card-actions">
                            <?php if (!empty($a['os_id'])): ?><a href="<?= APP_URL ?>agendamentos/os?id=<?= urlencode($a['os_id']) ?>"><i class="fa-solid fa-file-lines"></i> Ver OS <?= h($a['os_numero']) ?></a><?php endif; ?>
                            <?php if ($cargo !== 'VISTORIADOR'): ?><a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($a['id']) ?>"><i class="fa-solid fa-pen"></i> Editar agendamento</a><?php endif; ?>
                            <?php if ($a['status'] === 'pendente' && in_array($cargo, ['ADMIN', 'VENDEDOR'], true)): ?>
                                <form method="post" action="<?= APP_URL ?>agendamentos/actions" onsubmit="return confirm('Confirmar agendamento e gerar Ordem de Serviço?')"><input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>"><input type="hidden" name="action" value="confirmar"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><button type="submit"><i class="fa-solid fa-check-double"></i> Confirmar e gerar OS</button></form>
                            <?php endif; ?>
                            <?php if (in_array($a['status'], ['pendente', 'confirmado'], true) && $cargo !== 'VISTORIADOR'): ?>
                                <form method="post" action="<?= APP_URL ?>agendamentos/actions" onsubmit="const motivo=prompt('Informe o motivo do cancelamento:');if(!motivo||!motivo.trim())return false;this.motivo_cancelamento.value=motivo.trim();return confirm('Tem certeza que deseja cancelar este agendamento?')"><input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>"><input type="hidden" name="action" value="cancelar"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><input type="hidden" name="motivo_cancelamento" value=""><button type="submit" class="is-danger"><i class="fa-solid fa-ban"></i> Cancelar agendamento</button></form>
                            <?php endif; ?>
                        </div>
                    </details>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Controles de Paginação -->
        <div class="schedule-paginacao-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-top: 24px; padding: 14px 18px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="font-size: 0.85rem; color: #64748b;">
                    Mostrando <strong><?= $registroInicio ?></strong> a <strong><?= $registroFim ?></strong> de <strong><?= $totalFiltrados ?></strong> agendamentos
                </span>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="font-size: 0.82rem; color: #64748b; margin: 0;">Exibir:</label>
                    <select id="selectPorPagina" class="form-control form-control-sm" style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #cbd5e1;" onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?= h($agendamentoUrl(['por_pagina' => $qtd, 'pagina' => 1])) ?>" <?= $porPagina === $qtd ? 'selected' : '' ?>>
                                <?= $qtd ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de agendamentos">
                    <ul class="paginacao-agendamentos" style="display: flex; align-items: center; gap: 5px; margin: 0; padding: 0; list-style: none;">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h($agendamentoUrl(['pagina' => 1])) ?>" title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h($agendamentoUrl(['pagina' => $paginaAtual - 1])) ?>" title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de páginas -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($agendamentoUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link active-link" style="background: var(--cor-primaria, #0d9488); color: #ffffff; border-color: var(--cor-primaria, #0d9488); font-weight: 700;">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($agendamentoUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($agendamentoUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($agendamentoUrl(['pagina' => $paginaAtual + 1])) ?>" title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($agendamentoUrl(['pagina' => $totalPaginas])) ?>" title="Última página">
                                <i class="fas fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<style>
.paginacao-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 0.84rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}
.paginacao-link:hover:not(.active-link):not(.disabled) {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.paginacao-item.disabled .paginacao-link {
    opacity: 0.45;
    cursor: not-allowed;
    background: #f8fafc;
}
</style>

<script>
document.querySelector('.schedule-filter-toggle')?.addEventListener('click', function () {
    const filters = document.getElementById('scheduleFilters');
    const open = filters.classList.toggle('is-open');
    this.setAttribute('aria-expanded', String(open));
    this.querySelector('.fa-chevron-down, .fa-chevron-up').className = `fa-solid fa-chevron-${open ? 'up' : 'down'}`;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
