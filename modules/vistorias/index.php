<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: index.php - Listagem de vistorias com filtro por status
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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

try {
    $params = [];
    $where_extra = '';

    if (getCargo() === 'VISTORIADOR') {
        $where_extra = " AND (a.vistoriador_id = :vistoriador_id OR a.vistoriador_id IN (SELECT u2.id FROM usuarios u2 WHERE u2.email = :uemail AND :uemail <> '') OR v.criado_por = :vistoriador_id)";
        $params[':vistoriador_id'] = $_SESSION['usuario_id'];
        $params[':uemail'] = $_SESSION['usuario_email'] ?? '';
    } elseif (getCargo() === 'ANALISTA') {
        $where_extra = " AND EXISTS (SELECT 1 FROM analises_planos ap WHERE ap.embarcacao_id=v.embarcacao_id AND ap.analista_id=:analista_id)";
        $params[':analista_id'] = $_SESSION['usuario_id'];
    } elseif (getCargo() === 'VENDEDOR') {
        $where_extra = " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
        $params[':vendedor_id'] = $_SESSION['usuario_id'];
        $params[':agend_vendedor_id'] = $_SESSION['usuario_id'];
    }
} catch (Exception $e) {
    error_log('Erro ao buscar filtros de vistorias: ' . $e->getMessage());
}

// Buscar vistorias
try {
    $status_filter = '';
    if ($filtro_status === 'APROVADA') {
        $status_filter = " AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')";
    } elseif (!empty($filtro_status) && in_array($filtro_status, ['PENDENTE', 'RETORNO_AS', 'REPROVADA', 'CANCELADA'], true)) {
        $status_filter = " AND v.status = :status";
        $params[':status'] = $filtro_status;
    }

    $sql = "SELECT v.*, a.data_vistoria, a.hora_vistoria, a.local, a.tipo_vistoria,
                   e.nome AS embarcacao_nome, e.tipo AS embarcacao_tipo, e.numero_inscricao,
                   c.nome AS cliente_nome,
                   u.nome AS vistoriador_nome
            FROM vistorias v
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id
            LEFT JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN clientes c ON a.cliente_id = c.id
            LEFT JOIN usuarios u ON a.vistoriador_id = u.id
            WHERE 1=1 {$status_filter} {$where_extra}
            ORDER BY v.criado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vistorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar vistorias: ' . $e->getMessage());
    $vistorias = [];
}

// Buscar agendamentos pendentes ou em andamento para a escala imediata
$agendamentos_escala = [];
try {
    $sqlAg = "SELECT a.id, a.data_vistoria, a.hora_vistoria, a.local, a.tipo_vistoria, a.status AS agendamento_status,
                     e.nome AS embarcacao_nome, COALESCE(NULLIF(e.registro,''), e.numero_inscricao) AS embarcacao_registro,
                     c.nome AS cliente_nome,
                     v.id AS vistoria_id, v.status AS vistoria_status, v.numero AS vistoria_numero
              FROM agendamentos a
              LEFT JOIN embarcacoes e ON a.embarcacao_id = e.id
              LEFT JOIN clientes c ON a.cliente_id = c.id
              LEFT JOIN vistorias v ON v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id = a.id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
              WHERE a.status IN ('pendente', 'confirmado', 'em_andamento')
                AND (v.id IS NULL OR v.status = 'PENDENTE')";
    $paramsAg = [];
    if ($cargo === 'VISTORIADOR') {
        $sqlAg .= " AND (a.vistoriador_id = :uid OR a.vistoriador_id IN (SELECT u2.id FROM usuarios u2 WHERE u2.email = :uemail AND :uemail <> '') OR a.vistoriador_id IS NULL)";
        $paramsAg[':uid'] = $_SESSION['usuario_id'];
        $paramsAg[':uemail'] = $_SESSION['usuario_email'] ?? '';
    }
    $sqlAg .= " ORDER BY a.data_vistoria IS NULL, a.data_vistoria ASC, a.hora_vistoria ASC LIMIT 10";
    $stmtAg = $pdo->prepare($sqlAg);
    $stmtAg->execute($paramsAg);
    $agendamentos_escala = $stmtAg->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao buscar escala de agendamentos em vistorias: ' . $e->getMessage());
    $agendamentos_escala = [];
}

// Contadores para os cards de filtro
try {
    $sql_contadores = "SELECT v.status, COUNT(*) as total FROM vistorias v LEFT JOIN agendamentos a ON v.agendamento_id = a.id WHERE 1=1";
    if ($cargo === 'VISTORIADOR') {
        $sql_contadores .= " AND (a.vistoriador_id = :vistoriador_id OR a.vistoriador_id IN (SELECT u2.id FROM usuarios u2 WHERE u2.email = :uemail AND :uemail <> '') OR v.criado_por = :vistoriador_id)";
    } elseif ($cargo === 'ANALISTA') {
        $sql_contadores .= " AND EXISTS (SELECT 1 FROM analises_planos ap WHERE ap.embarcacao_id=v.embarcacao_id AND ap.analista_id=:analista_id)";
    } elseif ($cargo === 'VENDEDOR') {
        $sql_contadores .= " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
    }
    $sql_contadores .= " GROUP BY v.status";
    
    if ($cargo === 'VISTORIADOR') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id'], ':uemail' => $_SESSION['usuario_email'] ?? '']);
    } elseif ($cargo === 'ANALISTA') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':analista_id' => $_SESSION['usuario_id']]);
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
        'RETORNO_AS' => ['badge-danger', 'fa-calendar-plus', 'Retorno A/S necessário'],
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
            <i class="fas fa-calendar-check" aria-hidden="true"></i> Ver todos agendamentos
        </a>
    </header>

    <?php if (!empty($agendamentos_escala)): ?>
    <!-- Seção: Próximas Vistorias Agendadas & Em Andamento -->
    <section class="inspection-schedule-highlight" style="margin-bottom: 24px;">
        <div style="background: #ffffff; border: 2px solid #0d9488; border-radius: 12px; padding: 18px 20px; box-shadow: 0 4px 14px rgba(13, 148, 136, 0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-calendar-days" style="color: #0d9488;"></i>
                        Vistorias Agendadas & Em Andamento
                    </h2>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;">
                        Inicie ou continue suas inspeções pendentes da escala diretamente pelo ERP web.
                    </p>
                </div>
                <span class="badge" style="background: #ccfbf1; color: #0f766e; font-weight: 700; padding: 4px 10px; border-radius: 999px; font-size: 11.5px;">
                    <?= count($agendamentos_escala) ?> pendente<?= count($agendamentos_escala) === 1 ? '' : 's' ?>
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                <?php foreach ($agendamentos_escala as $agItem): ?>
                    <?php
                    $urlRelatorio = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agItem['id'])
                        . (!empty($agItem['vistoria_id']) ? '&vistoria_id=' . urlencode($agItem['vistoria_id']) : '');
                    $isEmAndamento = ($agItem['vistoria_status'] ?? '') === 'PENDENTE';
                    $dataAgenda = !empty($agItem['data_vistoria']) ? date('d/m/Y', strtotime($agItem['data_vistoria'])) : 'Data a definir';
                    $horaAgenda = !empty($agItem['hora_vistoria']) ? substr($agItem['hora_vistoria'], 0, 5) : '';
                    ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                <span style="font-size: 11px; font-weight: 700; color: #0f766e; background: #e6fffa; padding: 2px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-regular fa-calendar"></i> <?= h($dataAgenda) ?><?= $horaAgenda ? ' às ' . h($horaAgenda) : '' ?>
                                </span>
                                <?php if ($isEmAndamento): ?>
                                    <span style="font-size: 10.5px; font-weight: 800; background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px; border: 1px solid #bae6fd;">
                                        <i class="fa-solid fa-spinner fa-spin"></i> EM ANDAMENTO
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 10.5px; font-weight: 700; background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px;">
                                        <i class="fa-regular fa-clock"></i> AGENDADA
                                    </span>
                                <?php endif; ?>
                            </div>
                            <strong style="font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-ship" style="color: #0f766e;"></i>
                                <?= h($agItem['embarcacao_nome']) ?>
                            </strong>
                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                <?php if (!empty($agItem['embarcacao_registro'])): ?>
                                    <span>Reg: <strong><?= h($agItem['embarcacao_registro']) ?></strong></span> · 
                                <?php endif; ?>
                                <span><?= h($agItem['cliente_nome'] ?: 'Cliente a confirmar') ?></span>
                            </div>
                            <?php if (!empty($agItem['tipo_vistoria'])): ?>
                                <div style="font-size: 11px; color: #475569; margin-top: 4px;">
                                    <i class="fa-solid fa-tag" style="color: #94a3b8;"></i> <?= h($agItem['tipo_vistoria']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <a href="<?= h($urlRelatorio) ?>" class="btn btn-sm" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: <?= $isEmAndamento ? '#0284c7' : '#0d9488' ?>;">
                            <i class="fa-solid <?= $isEmAndamento ? 'fa-pen-to-square' : 'fa-play' ?>"></i>
                            <?= $isEmAndamento ? 'Continuar Vistoria' : 'Iniciar Vistoria' ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <nav class="inspection-status-nav" aria-label="Filtrar vistorias por situação">
        <?php
        $statusFilters = [
            '' => ['fa-list', 'Todas', $total_geral],
            'PENDENTE' => ['fa-clock', 'Pendentes', $contadores['PENDENTE'] ?? 0],
            'APROVADA' => ['fa-check-circle', 'Aprovadas', ($contadores['APROVADA'] ?? 0) + ($contadores['APROVADA_COM_EXIGENCIAS'] ?? 0)],
            'RETORNO_AS' => ['fa-calendar-plus', 'Retornos A/S', $contadores['RETORNO_AS'] ?? 0],
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
