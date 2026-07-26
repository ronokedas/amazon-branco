<?php
$hoje = date('Y-m-d');
$agenda = $dashboard['agenda_prioritaria'] ?? [];
$historico = $dashboard['historico'] ?? [];

function dashboardStatusAgenda(array $agendamento, string $hoje): array
{
    if (empty($agendamento['data_vistoria'])) return ['Data a definir', 'neutral'];
    if ($agendamento['data_vistoria'] < $hoje) return ['Atrasada', 'late'];
    if ($agendamento['data_vistoria'] === $hoje) return ['Hoje', 'today'];
    if (($agendamento['vistoria_status'] ?? '') === 'PENDENTE') return ['Em andamento', 'progress'];
    return ['Próxima', 'next'];
}

function dashboardStatusHistorico(string $status): array
{
    return match ($status) {
        'APROVADA' => ['Aprovada', 'approved'],
        'APROVADA_COM_EXIGENCIAS' => ['Aprovada com exigências', 'approved'],
        'RETORNO_AS' => ['Retorno A/S necessário', 'rejected'],
        'AGUARDANDO_APROVACAO' => ['Aguardando aprovação', 'waiting'],
        'REPROVADA' => ['Reprovada', 'rejected'],
        default => ['Rascunho', 'draft'],
    };
}
?>

<header class="role-dashboard__header inspector-dashboard__header">
    <div>
        <h1>Minha operação em campo</h1>
        <p>Acompanhe seus próximos agendamentos e execute as vistorias pelo Amazon Campo.</p>
    </div>
    <a class="inspector-dashboard__all-link" href="<?= APP_URL ?>vistorias">
        <i class="fa-solid fa-list-check"></i> Ver todas as vistorias
    </a>
</header>

<div class="inspector-dashboard__main">
    <div class="inspector-dashboard__primary">
    <section class="inspector-schedule" aria-labelledby="inspector-schedule-title">
        <header>
            <div>
                <h2 id="inspector-schedule-title">Próximas vistorias</h2>
                <p>Ordenadas pela data e horário do agendamento.</p>
            </div>
            <span><?= count($agenda) ?> agendamento<?= count($agenda) === 1 ? '' : 's' ?></span>
        </header>

        <?php if (!$agenda): ?>
            <div class="inspector-schedule__empty">
                <i class="fa-regular fa-calendar-check"></i>
                <strong>Nenhuma vistoria pendente</strong>
                <span>Quando um novo agendamento for atribuído, ele aparecerá aqui.</span>
            </div>
        <?php else: ?>
            <div class="inspector-schedule__head" aria-hidden="true">
                <span>Data e hora</span><span>Embarcação</span><span>Tipo de vistoria</span><span>Local</span><span>Status</span><span></span>
            </div>
            <div class="inspector-schedule__list">
                <?php foreach ($agenda as $index => $a):
                    [$statusLabel, $statusClass] = dashboardStatusAgenda($a, $hoje);
                    $data = !empty($a['data_vistoria']) ? new DateTimeImmutable($a['data_vistoria']) : null;
                    $url = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($a['id'])
                        . (!empty($a['vistoria_id']) ? '&vistoria_id=' . urlencode($a['vistoria_id']) : '');
                ?>
                    <a class="inspector-schedule__row is-<?= $statusClass ?><?= $index === 0 ? ' is-first' : '' ?>" href="<?= h($url) ?>">
                        <span class="inspector-schedule__date">
                            <i class="fa-regular fa-calendar"></i>
                            <span><strong><?= $data ? $data->format('d/m/Y') : 'A definir' ?></strong><small><?= !empty($a['hora_vistoria']) ? substr($a['hora_vistoria'], 0, 5) : 'Sem horário' ?></small></span>
                        </span>
                        <span class="inspector-schedule__vessel">
                            <strong><?= h($a['embarcacao']) ?></strong>
                            <small><?= h($a['registro'] ?: $a['cliente'] ?: 'Sem inscrição informada') ?></small>
                        </span>
                        <span class="inspector-schedule__type"><?= h($a['tipo_vistoria'] ?: 'Vistoria técnica') ?></span>
                        <span class="inspector-schedule__location"><i class="fa-solid fa-location-dot"></i><?= h($a['local'] ?: 'Local a definir') ?></span>
                        <span><em class="inspector-status is-<?= $statusClass ?>"><?= h($statusLabel) ?></em></span>
                        <i class="fa-solid fa-chevron-right inspector-schedule__open"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a class="inspector-schedule__footer-link" href="<?= APP_URL ?>vistorias">Ver todas as vistorias <i class="fa-solid fa-arrow-right"></i></a>
    </section>

    <section class="inspector-history" aria-labelledby="inspector-history-title">
        <header>
            <div><h2 id="inspector-history-title">Histórico recente</h2><p>Últimos relatórios movimentados por você.</p></div>
            <a href="<?= APP_URL ?>vistorias">Ver histórico completo</a>
        </header>
        <?php if (!$historico): ?>
            <div class="inspector-history__empty">Nenhum relatório registrado até o momento.</div>
        <?php else: ?>
            <div class="inspector-history__head" aria-hidden="true">
                <span>Data</span><span>Embarcação</span><span>Tipo de vistoria</span><span>Relatório</span><span>Resultado</span><span></span>
            </div>
            <div class="inspector-history__list">
                <?php foreach ($historico as $v):
                    [$statusLabel, $statusClass] = dashboardStatusHistorico((string)$v['status']);
                    $url = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($v['agendamento_id']) . '&vistoria_id=' . urlencode($v['id']);
                    $dataHistorico = !empty($v['data_vistoria']) ? date('d/m/Y', strtotime($v['data_vistoria'])) : 'Sem data';
                ?>
                    <a class="inspector-history__row" href="<?= h($url) ?>">
                        <span><?= h($dataHistorico) ?></span>
                        <strong><?= h($v['embarcacao']) ?></strong>
                        <span><?= h($v['tipo_vistoria'] ?: 'Vistoria técnica') ?></span>
                        <span><?= h($v['numero'] ?: 'S/N') ?></span>
                        <em class="inspector-history__status is-<?= $statusClass ?>"><?= h($statusLabel) ?></em>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    </div>

    <aside class="inspector-dashboard__rail">
        <section class="campo-launcher">
            <div class="campo-launcher__intro">
                <span><i class="fa-solid fa-mobile-screen-button"></i></span>
                <div><h2>Execute vistorias em campo</h2><p>Registre dados, evidências e finalize a vistoria diretamente pelo aplicativo.</p></div>
            </div>
            <a href="<?= APP_URL ?>campo/" target="_blank" rel="noopener">
                Abrir Amazon Campo <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
            <small>O acesso é exclusivo para o perfil VISTORIADOR.</small>
        </section>

        <section class="inspector-metrics" aria-label="Resumo da operação">
            <?php
            $metricas = [
                ['Atrasadas', $dashboard['kpis']['atrasadas'], 'fa-clock-rotate-left', 'late'],
                ['Hoje', $dashboard['kpis']['hoje'], 'fa-calendar-day', 'today'],
                ['Próximas', $dashboard['kpis']['proximas'], 'fa-calendar-check', 'next'],
                ['Rascunhos', $dashboard['kpis']['rascunhos'], 'fa-clipboard', 'draft'],
                ['Aguardando aprovação', $dashboard['kpis']['enviados'], 'fa-user-check', 'waiting'],
            ];
            foreach ($metricas as [$label, $valor, $icone, $classe]):
            ?>
                <article class="inspector-metric is-<?= $classe ?>">
                    <i class="fa-solid <?= $icone ?>"></i>
                    <span><small><?= h($label) ?></small><strong><?= (int)$valor ?></strong></span>
                </article>
            <?php endforeach; ?>
        </section>
    </aside>
</div>
