<?php
$meta = $dashboard['meta'];
$metaVisual = min(100, max(0, (float)$meta['percentual']));
$resumo = $dashboard['resumo_executivo'];
$progressoVistorias = $resumo['vistorias_planejadas'] > 0 ? min(100, round(($resumo['vistorias_mes'] / $resumo['vistorias_planejadas']) * 100)) : 0;
$certVariacao = $resumo['certificados_anterior'] > 0 ? round((($resumo['certificados_mes'] - $resumo['certificados_anterior']) / $resumo['certificados_anterior']) * 100, 1) : null;
$chartMax = max((float)$meta['valor'] * 1.08, max(array_column($dashboard['meses'], 'valor') ?: [1]), 1);
$goalLine = min(96, round(((float)$meta['valor'] / $chartMax) * 100, 1));
$decisoesPendentes = (int)$dashboard['acoes']['assinadas'] + (int)$dashboard['acoes']['aprovacao'];
?>

<section class="admin-flow-focus<?= $decisoesPendentes === 0 ? ' is-clear' : '' ?>" aria-labelledby="admin-flow-focus-title">
    <header>
        <div><span class="admin-flow-focus__pulse"><i class="fa-solid fa-bolt"></i></span><span><h1 id="admin-flow-focus-title">Fluxo que precisa de decisão</h1><p>Atenda estas etapas para manter a operação avançando sem espera.</p></span></div>
        <strong><?= $decisoesPendentes ?> pendência<?= $decisoesPendentes === 1 ? '' : 's' ?> agora</strong>
    </header>
    <div class="admin-flow-focus__lanes">
        <article class="admin-flow-lane is-commercial">
            <div class="admin-flow-lane__heading"><i class="fa-solid fa-file-signature"></i><span><strong>Propostas assinadas</strong><small>Próxima ação: concluir o agendamento</small></span><b><?= (int)$dashboard['acoes']['assinadas'] ?></b></div>
            <div class="admin-flow-items">
                <?php if (!$dashboard['fluxo_assinadas']): ?><p class="admin-flow-empty"><i class="fa-solid fa-circle-check"></i>Nenhuma proposta aguardando agendamento.</p><?php endif; ?>
                <?php foreach ($dashboard['fluxo_assinadas'] as $item):
                    $urlAgendar = $item['agendamento_id'] ? APP_URL.'agendamentos/form?id='.urlencode($item['agendamento_id']) : APP_URL.'agendamentos/form?proposta_id='.urlencode($item['proposta_id']);
                ?>
                <a class="admin-flow-item" href="<?= h($urlAgendar) ?>">
                    <span><strong><?= h($item['numero'] ?: 'Proposta assinada') ?></strong><small><?= h($item['cliente']) ?> · <?= h($item['embarcacao']) ?></small></span>
                    <em><?= $item['assinatura_em'] ? 'Assinada em '.date('d/m H:i',strtotime($item['assinatura_em'])) : 'Assinatura confirmada' ?></em>
                    <b>Agendar vistoria <i class="fa-solid fa-arrow-right"></i></b>
                </a>
                <?php endforeach; ?>
            </div>
        </article>
        <article class="admin-flow-lane is-analysis">
            <div class="admin-flow-lane__heading"><i class="fa-solid fa-clipboard-check"></i><span><strong>Relatórios enviados</strong><small>Próxima ação: analisar e decidir</small></span><b><?= (int)$dashboard['acoes']['aprovacao'] ?></b></div>
            <div class="admin-flow-items">
                <?php if (!$dashboard['fluxo_aprovacoes']): ?><p class="admin-flow-empty"><i class="fa-solid fa-circle-check"></i>Nenhum relatório aguardando análise.</p><?php endif; ?>
                <?php foreach ($dashboard['fluxo_aprovacoes'] as $item): ?>
                <a class="admin-flow-item" href="<?= $item['agendamento_id'] ? APP_URL.'vistorias/relatorio?agendamento_id='.urlencode($item['agendamento_id']) : APP_URL.'vistorias/detalhe?id='.urlencode($item['id']) ?>">
                    <span><strong><?= h($item['embarcacao']) ?></strong><small><?= h($item['numero'] ?: 'Relatório técnico') ?> · <?= h($item['vistoriador']) ?></small></span>
                    <em><?= (int)$item['horas'] < 1 ? 'Enviado agora' : 'Aguardando há '.(int)$item['horas'].'h' ?> · <?= (int)$item['nao_conformes'] ?> exigência<?= (int)$item['nao_conformes'] === 1 ? '' : 's' ?></em>
                    <b>Analisar relatório <i class="fa-solid fa-arrow-right"></i></b>
                </a>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<section class="admin-ref-top" aria-label="Resumo executivo">
    <article class="admin-goal-executive<?= $meta['percentual'] >= 100 ? ' is-complete' : '' ?>">
        <h1>Meta executiva do mês</h1>
        <div class="admin-goal-executive__main">
            <div class="admin-goal-ring" style="--admin-goal:<?= $metaVisual ?>">
                <strong><?= number_format($meta['percentual'], 1, ',', '.') ?>%</strong>
            </div>
            <div class="admin-goal-values">
                <span>Recebido</span>
                <strong><?= formatarMoeda($meta['realizado']) ?></strong>
                <p>de <?= formatarMoeda($meta['valor']) ?></p>
                <small>Meta mensal</small>
            </div>
        </div>
        <?php if ($meta['mensagem'] !== ''): ?>
        <div class="admin-team-message"><i class="fa-solid fa-comment"></i><span><strong>Mensagem da equipe</strong><?= h($meta['mensagem']) ?></span></div>
        <?php endif; ?>
    </article>

    <article class="admin-exec-kpi">
        <i class="fa-solid fa-clipboard-check"></i><span>Vistorias no mês</span><strong><?= $resumo['vistorias_mes'] ?></strong>
        <small>Planejadas: <?= $resumo['vistorias_planejadas'] ?></small><div class="admin-kpi-progress"><i style="width:<?= $progressoVistorias ?>%"></i></div>
    </article>
    <article class="admin-exec-kpi">
        <i class="fa-regular fa-circle-check"></i><span>Certificados emitidos</span><strong><?= $resumo['certificados_mes'] ?></strong>
        <small>No mês anterior: <?= $resumo['certificados_anterior'] ?></small><em class="<?= $certVariacao !== null && $certVariacao < 0 ? 'is-down' : 'is-up' ?>"><?= $certVariacao === null ? 'Sem histórico' : (($certVariacao >= 0 ? '▲ ' : '▼ ') . number_format(abs($certVariacao), 1, ',', '.') . '%') ?></em>
    </article>
    <article class="admin-exec-kpi">
        <i class="fa-solid fa-users"></i><span>Clientes ativos</span><strong><?= $resumo['clientes_ativos'] ?></strong>
        <small>Novos no mês: <?= $resumo['clientes_novos'] ?></small><em class="is-up">▲ base ativa</em>
    </article>
    <article class="admin-exec-kpi">
        <i class="fa-solid fa-dollar-sign"></i><span>Recebimentos no mês</span><strong class="is-money"><?= formatarMoeda($meta['realizado']) ?></strong>
        <small>Mês anterior: <?= formatarMoeda($resumo['receita_anterior']) ?></small><em class="<?= $resumo['variacao_receita'] !== null && $resumo['variacao_receita'] < 0 ? 'is-down' : 'is-up' ?>"><?= $resumo['variacao_receita'] === null ? 'Sem histórico' : (($resumo['variacao_receita'] >= 0 ? '▲ ' : '▼ ') . number_format(abs($resumo['variacao_receita']), 1, ',', '.') . '%') ?></em>
    </article>
</section>

<div class="admin-ref-main">
    <div class="admin-ref-primary">
        <section class="admin-ref-panel admin-action-board">
            <header><h2>Fila de ações</h2></header>
            <div class="admin-action-board__grid">
            <?php foreach ([
                ['Propostas assinadas sem agendamento',$dashboard['acoes']['assinadas'],'agendamentos','fa-file','amber','Ver propostas'],
                ['Vistorias vencidas',$dashboard['acoes']['vencidas'],'agendamentos','fa-calendar-xmark','red','Ver vistorias'],
                ['Aguardando aprovação',$dashboard['acoes']['aprovacao'],'documentacao/aprovacao_relatorios','fa-users','amber','Ver aprovações'],
                ['Documentos para emitir',$dashboard['acoes']['emitir'],'documentacao','fa-file-lines','green','Ver documentos'],
            ] as [$label,$value,$url,$icon,$color,$cta]): ?>
                <a class="admin-action-card is-<?= $color ?>" href="<?= APP_URL . $url ?>">
                    <div><i class="fa-solid <?= $icon ?>"></i><strong><?= (int)$value ?></strong><span><?= h($label) ?></span></div>
                    <footer><?= h($cta) ?><i class="fa-solid fa-chevron-right"></i></footer>
                </a>
            <?php endforeach; ?>
            </div>
        </section>

        <div class="admin-ref-analytics">
            <section class="admin-ref-panel admin-revenue-chart">
                <header><h2>Recebido vs Meta (últimos 6 meses)</h2></header>
                <div class="admin-chart-legend"><span><i></i>Recebido (R$)</span><span><i></i>Meta (R$)</span></div>
                <div class="admin-chart-area">
                    <div class="admin-goal-line" style="bottom:<?= $goalLine ?>%"><span><?= formatarMoeda($meta['valor']) ?></span></div>
                    <?php foreach ($dashboard['meses'] as $m): $barHeight = max(3, round(((float)$m['valor'] / $chartMax) * 100, 1)); ?>
                    <div class="admin-chart-column"><strong><?= formatarMoeda($m['valor']) ?></strong><i style="height:<?= $barHeight ?>%"></i><span><?= h($m['label']) ?></span></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-ref-panel admin-workload-table">
                <header><h2>Carga de trabalho dos vistoriadores</h2></header>
                <div class="admin-workload-head"><span>Vistoriador</span><span>Futuras</span><span>Atrasadas</span><span>Em análise</span></div>
                <?php if (!$dashboard['equipe']): ?><p class="dash-empty">Nenhum vistoriador ativo.</p><?php endif; ?>
                <?php foreach ($dashboard['equipe'] as $u): ?>
                <div class="admin-workload-line"><strong><?= h($u['nome']) ?></strong><span><?= (int)$u['futuras'] ?></span><span class="<?= (int)$u['atrasadas'] > 0 ? 'is-danger' : '' ?>"><?= (int)$u['atrasadas'] ?></span><span><?= (int)$u['relatorios'] ?></span></div>
                <?php endforeach; ?>
                <footer><a href="<?= APP_URL ?>usuarios">Ver todos os vistoriadores <i class="fa-solid fa-chevron-right"></i></a></footer>
            </section>
        </div>
    </div>

    <aside class="admin-ref-rail">
        <section class="admin-ref-panel admin-today-agenda">
            <header><h2><i class="fa-regular fa-calendar"></i>Agenda de hoje</h2><span><?= date('d/m/Y') ?></span></header>
            <div class="admin-agenda-list">
                <?php if (!$dashboard['agenda']): ?><p class="dash-empty">Agenda livre hoje.</p><?php endif; ?>
                <?php foreach ($dashboard['agenda'] as $a): ?>
                <a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($a['id']) ?>"><time><?= $a['hora_vistoria'] ? substr($a['hora_vistoria'], 0, 5) : '--:--' ?></time><i></i><span><strong><?= h($a['embarcacao']) ?></strong><small><?= h($a['tipo_vistoria']) ?> · <?= h($a['vistoriador'] ?: 'Sem responsável') ?></small></span><em>Programada</em></a>
                <?php endforeach; ?>
            </div>
            <footer><a href="<?= APP_URL ?>agendamentos">Ver agenda completa <i class="fa-solid fa-chevron-right"></i></a></footer>
        </section>

        <section class="admin-ref-panel admin-recent-activity" id="admin-activity">
            <header><h2>Atividade recente</h2></header>
            <div>
            <?php foreach ($dashboard['atividade'] as $a): ?>
                <?php if ($a['url']): ?><a href="<?= h($a['url']) ?>"><?php else: ?><article><?php endif; ?>
                    <i class="fa-solid fa-circle-check"></i><span><strong><?= h($a['descricao']) ?></strong><small><?= h($a['usuario'] ?: 'Sistema') ?> · <?= date('H:i', strtotime($a['criado_em'])) ?></small></span><time><?= date('H:i', strtotime($a['criado_em'])) ?></time>
                <?php if ($a['url']): ?></a><?php else: ?></article><?php endif; ?>
            <?php endforeach; ?>
            </div>
            <footer><a href="#admin-activity">Ver todas as atividades <i class="fa-solid fa-chevron-right"></i></a></footer>
        </section>
    </aside>
</div>

<section class="admin-ref-panel admin-recent-inspections" aria-labelledby="admin-recent-inspections-title">
    <header>
        <h2 id="admin-recent-inspections-title">Vistorias recentes</h2>
        <a href="<?= APP_URL ?>vistorias">Ver todas</a>
    </header>

    <?php if (!$dashboard['vistorias_recentes']): ?>
        <div class="admin-inspections-empty"><i class="fa-solid fa-ship"></i><strong>Nenhuma vistoria registrada</strong><span>As novas vistorias aparecerão aqui.</span></div>
    <?php else: ?>
        <div class="admin-inspections-table" role="table" aria-label="Últimas vistorias">
            <div class="admin-inspections-head" role="row">
                <span>Embarcação</span><span>Serviço</span><span>Vistoriador</span><span>Progresso</span><span>Situação</span><span>Prazo</span><span aria-hidden="true"></span>
            </div>
            <?php foreach ($dashboard['vistorias_recentes'] as $vistoria):
                $statusMap = [
                    'PENDENTE' => ['Em andamento', 'progress'],
                    'AGUARDANDO_APROVACAO' => ['Aguardando análise', 'analysis'],
                    'APROVADA' => ['Aprovada', 'approved'],
                    'APROVADA_COM_EXIGENCIAS' => ['Aprovada c/ exigências', 'approved'],
                    'REPROVADA' => ['Reprovada', 'rejected'],
                    'CANCELADA' => ['Cancelada', 'cancelled'],
                ];
                [$statusLabel, $statusClass] = $statusMap[$vistoria['status']] ?? ['Em andamento', 'progress'];
                $dataVistoria = new DateTimeImmutable($vistoria['data_vistoria']);
                $hoje = new DateTimeImmutable('today');
                $dias = (int)$hoje->diff($dataVistoria)->format('%r%a');
                if ($dias < 0) $prazoLabel = 'Vencida';
                elseif ($dias === 0) $prazoLabel = 'Hoje';
                elseif ($dias === 1) $prazoLabel = 'Amanhã';
                else $prazoLabel = 'Em ' . $dias . ' dias';
                $foto = trim((string)($vistoria['foto_url'] ?? '')) ?: APP_URL . 'assets/img/portal-hero-ship.png';
            ?>
            <a class="admin-inspection-row" href="<?= h($vistoria['url']) ?>" role="row" aria-label="Abrir vistoria de <?= h($vistoria['embarcacao']) ?>">
                <span class="admin-inspection-vessel" role="cell">
                    <span class="admin-inspection-thumb<?= $vistoria['foto_url'] ? ' has-field-photo' : '' ?>">
                        <img src="<?= h($foto) ?>" alt="Foto da embarcação <?= h($vistoria['embarcacao']) ?>" loading="lazy">
                        <?php if ((int)$vistoria['total_fotos'] > 0): ?><small><i class="fa-solid fa-camera"></i><?= (int)$vistoria['total_fotos'] ?></small><?php endif; ?>
                    </span>
                    <span><strong><?= h($vistoria['embarcacao']) ?></strong><small><?= h($vistoria['registro'] ?: $vistoria['numero'] ?: 'Sem registro') ?></small></span>
                </span>
                <span class="admin-inspection-service" role="cell"><?= h($vistoria['servico']) ?></span>
                <span role="cell"><?= h($vistoria['vistoriador']) ?></span>
                <span class="admin-inspection-progress" role="cell"><strong><?= (int)$vistoria['progresso'] ?>%</strong><i><b style="width:<?= (int)$vistoria['progresso'] ?>%"></b></i></span>
                <span role="cell"><em class="admin-inspection-status is-<?= $statusClass ?>"><?= h($statusLabel) ?></em></span>
                <span class="admin-inspection-deadline<?= $dias < 0 ? ' is-late' : ($dias <= 1 ? ' is-near' : '') ?>" role="cell"><strong><?= $dataVistoria->format('d/m/Y') ?></strong><small><?= h($prazoLabel) ?></small></span>
                <span class="admin-inspection-open" role="cell"><i class="fa-solid fa-chevron-right"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
