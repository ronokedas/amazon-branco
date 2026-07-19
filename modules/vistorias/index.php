<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: index.php - Listagem de vistorias com filtro por status
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

// Filtro de status
$filtro_status = $_GET['status'] ?? '';
$cargo = getCargo();

// Buscar vistorias com JOINs para mostrar nomes
try {
    $sql = "SELECT v.id, v.data_vistoria, v.status, v.observacoes, v.criado_em, v.atualizado_em,
                   e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
                   p.nome AS pessoa_nome, p.cpf_cnpj AS pessoa_cpf,
                   u.nome AS criado_por_nome
            FROM vistorias v
            LEFT JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN clientes p ON v.pessoa_id = p.id
            LEFT JOIN usuarios u ON v.criado_por = u.id
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id";

    $params = [];
    $where_extra = '';

    if (getCargo() === 'VISTORIADOR') {
        $where_extra = " AND a.vistoriador_id = :vistoriador_id";
        $params[':vistoriador_id'] = $_SESSION['usuario_id'];
    } elseif (getCargo() === 'ANALISTA') {
        $where_extra = " AND v.status = 'AGUARDANDO_APROVACAO'";
    } elseif (getCargo() === 'VENDEDOR') {
        $where_extra = " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
        $params[':vendedor_id'] = $_SESSION['usuario_id'];
        $params[':agend_vendedor_id'] = $_SESSION['usuario_id'];
    }

    if (!empty($filtro_status) && in_array($filtro_status, ['PENDENTE', 'APROVADA', 'REPROVADA', 'CANCELADA'])) {
        $sql .= " WHERE v.status = :status" . $where_extra;
        $params[':status'] = $filtro_status;
    } elseif ($where_extra !== '') {
        $sql .= " WHERE 1=1" . $where_extra;
    }

    $sql .= " ORDER BY v.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vistorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar vistorias: ' . $e->getMessage());
    $vistorias = [];
}

// Contadores para os cards de filtro
try {
    $sql_contadores = "SELECT v.status, COUNT(*) as total FROM vistorias v LEFT JOIN agendamentos a ON v.agendamento_id = a.id WHERE 1=1";
    if ($cargo === 'VISTORIADOR') {
        $sql_contadores .= " AND a.vistoriador_id = :vistoriador_id";
    } elseif ($cargo === 'ANALISTA') {
        $sql_contadores .= " AND v.status = 'AGUARDANDO_APROVACAO'";
    } elseif ($cargo === 'VENDEDOR') {
        $sql_contadores .= " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
    }
    $sql_contadores .= " GROUP BY v.status";
    
    if ($cargo === 'VISTORIADOR') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } elseif ($cargo === 'VENDEDOR') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':vendedor_id' => $_SESSION['usuario_id'], ':agend_vendedor_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_contadores);
    }
    $contadores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $contadores = [];
}
$total_geral = array_sum($contadores);

function vistoriaStatusMeta(string $status): array
{
    $map = [
        'PENDENTE' => ['badge-warning', 'fa-clock', 'Pendente'],
        'AGUARDANDO_APROVACAO' => ['badge-warning', 'fa-hourglass-half', 'Aguardando aprovação'],
        'APROVADA_COM_EXIGENCIAS' => ['badge-info', 'fa-clipboard-check', 'Aprovada com exigências'],
        'APROVADA' => ['badge-success', 'fa-check-circle', 'Aprovada'],
        'REPROVADA' => ['badge-danger', 'fa-times-circle', 'Reprovada'],
        'CANCELADA' => ['badge-secondary', 'fa-ban', 'Cancelada'],
    ];

    return $map[$status] ?? ['badge-info', 'fa-circle-info', ucfirst(strtolower(str_replace('_', ' ', $status)))];
}

$titulo_page = 'Vistorias - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal inspection-page">
    <header class="inspection-page-header">
        <div>
            <h1>Vistorias</h1>
            <p>Acompanhe a execução, a análise e o resultado de cada vistoria.</p>
        </div>
        <a href="<?php echo APP_URL; ?>agendamentos" class="inspection-new-button">
            <i class="fas fa-calendar-check" aria-hidden="true"></i> Ver agendamentos
        </a>
    </header>

    <nav class="inspection-status-nav" aria-label="Filtrar vistorias por situação">
        <?php
        $statusFilters = [
            '' => ['fa-list', 'Todas', $total_geral],
            'PENDENTE' => ['fa-clock', 'Pendentes', $contadores['PENDENTE'] ?? 0],
            'APROVADA' => ['fa-check-circle', 'Aprovadas', $contadores['APROVADA'] ?? 0],
            'REPROVADA' => ['fa-times-circle', 'Reprovadas', $contadores['REPROVADA'] ?? 0],
            'CANCELADA' => ['fa-ban', 'Canceladas', $contadores['CANCELADA'] ?? 0],
        ];
        foreach ($statusFilters as $statusKey => [$statusIcon, $statusLabel, $statusCount]):
            $active = $filtro_status === $statusKey;
            $href = APP_URL . 'vistorias' . ($statusKey !== '' ? '?status=' . urlencode($statusKey) : '');
        ?>
            <a href="<?php echo h($href); ?>" class="<?php echo $active ? 'is-active' : ''; ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
                <i class="fas <?php echo h($statusIcon); ?>" aria-hidden="true"></i>
                <span><?php echo h($statusLabel); ?></span>
                <strong><?php echo (int) $statusCount; ?></strong>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="inspection-search">
        <i class="fas fa-search" aria-hidden="true"></i>
        <label class="sr-only" for="buscaVistoria">Buscar vistoria</label>
        <input type="search" id="buscaVistoria" placeholder="Buscar por embarcação, pessoa ou registro">
    </div>

    <?php if (empty($vistorias)): ?>
        <div class="inspection-empty">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
            <h2>Nenhuma vistoria encontrada</h2>
            <p>As vistorias são iniciadas pelos agendamentos atribuídos.</p>
        </div>
    <?php else: ?>
        <section class="inspection-desktop-list" aria-label="Lista de vistorias">
            <div class="inspection-list-heading">
                <div>
                    <h2>Vistorias cadastradas</h2>
                    <p><?php echo count($vistorias); ?> registro(s) no filtro atual</p>
                </div>
            </div>
            <table id="tabelaVistorias" data-responsive="off">
                <thead>
                    <tr>
                        <th>Embarcacao</th>
                        <th>Pessoa</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Criado por</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vistorias as $v): ?>
                    <?php $statusMeta = vistoriaStatusMeta((string) $v['status']); ?>
                    <tr data-inspection-search="<?php echo h(strtolower(implode(' ', [$v['embarcacao_nome'] ?? '', $v['embarcacao_registro'] ?? '', $v['pessoa_nome'] ?? '', $v['pessoa_cpf'] ?? '']))); ?>">
                        <td>
                            <strong><?php echo h($v['embarcacao_nome'] ?? 'N/A'); ?></strong>
                            <?php if (!empty($v['embarcacao_registro'])): ?>
                                <br><small class="text-muted">Reg: <?php echo h($v['embarcacao_registro']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo h($v['pessoa_nome'] ?? 'N/A'); ?>
                            <?php if (!empty($v['pessoa_cpf'])): ?>
                                <br><small class="text-muted">CPF: <?php echo h(formatarCPF($v['pessoa_cpf'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarData($v['data_vistoria']); ?></td>
                        <td>
                            <span class="badge <?php echo h($statusMeta[0]); ?>">
                                <i class="fas <?php echo h($statusMeta[1]); ?>"></i> <?php echo h($statusMeta[2]); ?>
                            </span>
                        </td>
                        <td><?php echo h($v['criado_por_nome'] ?? 'N/A'); ?></td>
                        <td><?php echo formatarDataCompleta($v['criado_em']); ?></td>
                        <td>
                            <a href="<?php echo APP_URL; ?>vistorias/detalhe?id=<?php echo urlencode($v['id']); ?>" 
                               class="inspection-table-action" title="Ver detalhes" aria-label="Ver detalhes da vistoria de <?php echo h($v['embarcacao_nome'] ?? 'embarcação'); ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="inspection-mobile-list" aria-label="Vistorias cadastradas">
            <?php foreach ($vistorias as $v): ?>
                <?php $statusMeta = vistoriaStatusMeta((string) $v['status']); ?>
                <article class="inspection-card" data-inspection-search="<?php echo h(strtolower(implode(' ', [$v['embarcacao_nome'] ?? '', $v['embarcacao_registro'] ?? '', $v['pessoa_nome'] ?? '', $v['pessoa_cpf'] ?? '']))); ?>">
                    <div class="inspection-card-top">
                        <div class="inspection-card-icon"><i class="fas fa-ship" aria-hidden="true"></i></div>
                        <div class="inspection-card-title">
                            <span><?php echo formatarData($v['data_vistoria']); ?></span>
                            <h2><?php echo h($v['embarcacao_nome'] ?? 'Embarcação não informada'); ?></h2>
                            <p><?php echo !empty($v['embarcacao_registro']) ? 'Registro ' . h($v['embarcacao_registro']) : 'Sem registro informado'; ?></p>
                        </div>
                        <span class="badge <?php echo h($statusMeta[0]); ?>"><i class="fas <?php echo h($statusMeta[1]); ?>" aria-hidden="true"></i> <?php echo h($statusMeta[2]); ?></span>
                    </div>
                    <div class="inspection-card-meta">
                        <span><i class="fas fa-user" aria-hidden="true"></i><span><small>Cliente</small><?php echo h($v['pessoa_nome'] ?? 'Não informado'); ?></span></span>
                        <span><i class="fas fa-user-check" aria-hidden="true"></i><span><small>Criada por</small><?php echo h($v['criado_por_nome'] ?? 'Não informado'); ?></span></span>
                    </div>
                    <a class="inspection-card-primary" href="<?php echo APP_URL; ?>vistorias/detalhe?id=<?php echo urlencode($v['id']); ?>">
                        Ver detalhes <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <details class="inspection-card-more">
                        <summary>Mais informações <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                        <dl>
                            <div><dt>Documento</dt><dd><?php echo !empty($v['pessoa_cpf']) ? h(formatarCPF($v['pessoa_cpf'])) : 'Não informado'; ?></dd></div>
                            <div><dt>Criada em</dt><dd><?php echo formatarDataCompleta($v['criado_em']); ?></dd></div>
                        </dl>
                    </details>
                </article>
            <?php endforeach; ?>
        </section>
        <p class="inspection-no-results" hidden>Nenhuma vistoria corresponde à busca.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('buscaVistoria');
    const entries = Array.from(document.querySelectorAll('[data-inspection-search]'));
    const empty = document.querySelector('.inspection-no-results');
    if (!input || !entries.length) return;

    input.addEventListener('input', function () {
        const term = this.value.toLocaleLowerCase('pt-BR').trim();
        let visibleCards = 0;
        entries.forEach(function (entry) {
            const show = !term || entry.dataset.inspectionSearch.includes(term);
            entry.hidden = !show;
            if (show && entry.classList.contains('inspection-card')) visibleCards++;
        });
        if (empty) empty.hidden = visibleCards !== 0;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
