<?php
/** Listagem responsiva de agendamentos. */
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

try {
    $where = [];
    $params = [];
    if ($cargo === 'VISTORIADOR') {
        $where[] = 'a.vistoriador_id = :vistoriador_id';
        $params[':vistoriador_id'] = $usuario_id;
    }
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

    $sql = "
        SELECT a.*, c.nome AS cliente_nome, e.nome AS embarcacao_nome,
               u.nome AS vistoriador_nome, os.id AS os_id, os.numero AS os_numero,
               os.status AS os_status, v.status AS vistoria_status
        FROM agendamentos a
        LEFT JOIN vistorias v ON v.id = (
            SELECT v2.id FROM vistorias v2
             WHERE v2.agendamento_id = a.id
             ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1
        )
        INNER JOIN clientes c ON a.cliente_id = c.id
        INNER JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN usuarios u ON a.vistoriador_id = u.id
        LEFT JOIN ordens_servico os ON os.agendamento_id = a.id
    ";
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY COALESCE(a.data_vistoria, DATE(a.created_at)) DESC, a.hora_vistoria DESC, COALESCE(a.updated_at, a.created_at) DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

$total_agendamentos = count($agendamentos);
$total_pendentes = count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente'));
$total_hoje = count(array_filter($agendamentos, fn($a) => ($a['data_vistoria'] ?? '') === date('Y-m-d')));
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
            <div class="schedule-list-heading"><div><h2>Todos os agendamentos</h2><p><?= $total_agendamentos ?> registro<?= $total_agendamentos === 1 ? '' : 's' ?> encontrado<?= $total_agendamentos === 1 ? '' : 's' ?></p></div></div>
            <div class="schedule-table-wrap">
                <table id="tabelaAgendamentos" class="schedule-table" data-responsive="off">
                    <thead><tr><th>Data e local</th><th>Cliente / embarcação</th><th>Tipo de vistoria</th><th>Vistoriador</th><th>Status</th><th>OS</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($agendamentos as $a): $st = $status_labels[$a['status']] ?? ['label' => ucfirst($a['status']), 'class' => 'neutral']; ?>
                        <tr>
                            <td><strong><?= !empty($a['data_vistoria']) ? formatarData($a['data_vistoria']) : 'Sem data' ?></strong><small><?= !empty($a['hora_vistoria']) ? h(substr($a['hora_vistoria'], 0, 5)) : 'Horário não definido' ?><?= !empty($a['local']) ? ' · ' . h(agendaTexto($a['local'])) : '' ?></small></td>
                            <td><strong><?= h(agendaTexto($a['embarcacao_nome'])) ?></strong><small><?= h(agendaTexto($a['cliente_nome'])) ?></small></td>
                            <td><?= h(agendaTexto($a['tipo_vistoria'] ?: 'Não informado')) ?></td>
                            <td><?= h(agendaTexto($a['vistoriador_nome'] ?: 'Não definido')) ?></td>
                            <td><span class="schedule-status schedule-status--<?= h($st['class']) ?>"><?= h($st['label']) ?></span></td>
                            <td><?= !empty($a['os_id']) ? '<a class="schedule-os" href="' . APP_URL . 'agendamentos/os?id=' . urlencode($a['os_id']) . '">' . h($a['os_numero']) . '</a>' : '<span class="schedule-muted">–</span>' ?></td>
                            <td><div class="schedule-table-actions"><a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($a['id']) ?>" title="Abrir relatório"><i class="fa-solid fa-clipboard-list"></i></a><?php if ($cargo !== 'VISTORIADOR'): ?><a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($a['id']) ?>" title="Editar"><i class="fa-solid fa-pen"></i></a><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="schedule-mobile-list" aria-label="Lista de agendamentos">
            <?php foreach ($agendamentos as $a):
                $st = $status_labels[$a['status']] ?? ['label' => ucfirst($a['status']), 'class' => 'neutral'];
                $data_iso = $a['data_vistoria'] ?? '';
                $dia = $data_iso ? date('d', strtotime($data_iso)) : '–';
                $meses = ['01'=>'JAN','02'=>'FEV','03'=>'MAR','04'=>'ABR','05'=>'MAI','06'=>'JUN','07'=>'JUL','08'=>'AGO','09'=>'SET','10'=>'OUT','11'=>'NOV','12'=>'DEZ'];
                $mes = $data_iso ? ($meses[date('m', strtotime($data_iso))] ?? '') : 'DATA';
            ?>
                <article class="schedule-card">
                    <div class="schedule-card-top">
                        <time datetime="<?= h($data_iso) ?>"><strong><?= $dia ?></strong><span><?= $mes ?></span></time>
                        <div class="schedule-card-heading"><span><?= !empty($a['hora_vistoria']) ? h(substr($a['hora_vistoria'], 0, 5)) : 'Sem horário' ?></span><h2><?= h(agendaTexto($a['embarcacao_nome'])) ?></h2><p><?= h(agendaTexto($a['cliente_nome'])) ?></p></div>
                        <span class="schedule-status schedule-status--<?= h($st['class']) ?>"><?= h($st['label']) ?></span>
                    </div>
                    <div class="schedule-card-meta">
                        <span><i class="fa-solid fa-location-dot"></i><?= h(agendaTexto($a['local'] ?: 'Local não informado')) ?></span>
                        <span><i class="fa-solid fa-clipboard-check"></i><?= h(agendaTexto($a['tipo_vistoria'] ?: 'Tipo não informado')) ?></span>
                        <span><i class="fa-solid fa-user-check"></i><?= h(agendaTexto($a['vistoriador_nome'] ?: 'Vistoriador não definido')) ?></span>
                    </div>
                    <a class="schedule-card-primary" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($a['id']) ?>"><i class="fa-solid fa-clipboard-list"></i> Abrir relatório da vistoria</a>
                    <details class="schedule-card-more">
                        <summary>Detalhes e ações <i class="fa-solid fa-chevron-down"></i></summary>
                        <div class="schedule-card-actions">
                            <?php if (!empty($a['os_id'])): ?><a href="<?= APP_URL ?>agendamentos/os?id=<?= urlencode($a['os_id']) ?>"><i class="fa-solid fa-file-lines"></i> Ver OS <?= h($a['os_numero']) ?></a><?php endif; ?>
                            <?php if ($cargo !== 'VISTORIADOR'): ?><a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($a['id']) ?>"><i class="fa-solid fa-pen"></i> Editar agendamento</a><?php endif; ?>
                            <?php if ($a['status'] === 'pendente' && in_array($cargo, ['ADMIN', 'VENDEDOR'], true)): ?>
                                <form method="post" action="<?= APP_URL ?>agendamentos/actions" onsubmit="return confirm('Confirmar agendamento e gerar Ordem de Serviço?')"><input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>"><input type="hidden" name="action" value="confirmar"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><button type="submit"><i class="fa-solid fa-check-double"></i> Confirmar e gerar OS</button></form>
                            <?php endif; ?>
                            <?php if (in_array($a['status'], ['pendente', 'confirmado'], true) && $cargo !== 'VISTORIADOR'): ?>
                                <form method="post" action="<?= APP_URL ?>agendamentos/actions" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?')"><input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>"><input type="hidden" name="action" value="cancelar"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><button type="submit" class="is-danger"><i class="fa-solid fa-ban"></i> Cancelar agendamento</button></form>
                            <?php endif; ?>
                        </div>
                    </details>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<script>
document.querySelector('.schedule-filter-toggle')?.addEventListener('click', function () {
    const filters = document.getElementById('scheduleFilters');
    const open = filters.classList.toggle('is-open');
    this.setAttribute('aria-expanded', String(open));
    this.querySelector('.fa-chevron-down, .fa-chevron-up').className = `fa-solid fa-chevron-${open ? 'up' : 'down'}`;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
