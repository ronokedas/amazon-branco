<?php
$kpis = $dashboard['kpis'] ?? [];
$filaPlanos = $dashboard['fila_planos'] ?? [];
$historicoPareceres = $dashboard['historico_pareceres'] ?? [];
$filaVistorias = $dashboard['fila_vistorias'] ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Analista';

$planoUrgente = null;
foreach ($filaPlanos as $item) {
    if (!empty($item['prazo_agendado_em'])) {
        $dataPrazo = substr($item['prazo_agendado_em'], 0, 10);
        if ($dataPrazo <= date('Y-m-d')) {
            $planoUrgente = $item;
            break;
        }
    }
}
if (!$planoUrgente && !empty($filaPlanos)) {
    $planoUrgente = $filaPlanos[0];
}

$statusMap = [
    'AGUARDANDO_AGENDAMENTO' => ['label' => 'Aguardando agendamento', 'badge' => 'badge-warning', 'icon' => 'fa-calendar'],
    'AGENDADA' => ['label' => 'Agendada', 'badge' => 'badge-info', 'icon' => 'fa-clock'],
    'EM_ANALISE' => ['label' => 'Em análise técnica', 'badge' => 'badge-primary', 'icon' => 'fa-compass-drafting'],
    'AGUARDANDO_DOCUMENTOS' => ['label' => 'Aguardando documentos', 'badge' => 'badge-warning', 'icon' => 'fa-file-arrow-up'],
    'AGUARDANDO_ASSINATURA_ANALISTA' => ['label' => 'Assinatura pendente', 'badge' => 'badge-warning', 'icon' => 'fa-file-signature'],
    'AGUARDANDO_APROVACAO_ADMIN' => ['label' => 'Aprovação da diretoria', 'badge' => 'badge-info', 'icon' => 'fa-user-check'],
    'CONCLUIDA' => ['label' => 'Concluída / Aprovada', 'badge' => 'badge-success', 'icon' => 'fa-circle-check'],
    'REPROVADA' => ['label' => 'Reprovada', 'badge' => 'badge-danger', 'icon' => 'fa-circle-xmark'],
    'CANCELADA' => ['label' => 'Cancelada', 'badge' => 'badge-secondary', 'icon' => 'fa-ban'],
];
?>

<div class="dash-analista-container">
    <!-- Header Operacional -->
    <header class="dash-analista-header">
        <div class="dash-analista-header__titles">
            <div class="dash-analista-badge"><i class="fa-solid fa-compass-drafting"></i> MESA DE ENGENHARIA NAVAL</div>
            <h1>Minha Central de Análise de Planos</h1>
            <p>Olá, <strong><?= h($usuarioNome) ?></strong>. Acompanhe as pranchas, cálculos de estabilidade, matriz normativa e emissão de pareceres.</p>
        </div>
        <div class="dash-analista-header__actions">
            <a href="<?= APP_URL ?>analises-planos/form" class="btn btn-primary btn-nova-analise">
                <i class="fa-solid fa-plus"></i> Nova Análise de Planos
            </a>
            <a href="<?= APP_URL ?>analises-planos" class="btn btn-secondary">
                <i class="fa-solid fa-list-check"></i> Todas as Análises
            </a>
        </div>
    </header>

    <!-- Alerta de Urgência de Prazo -->
    <?php if ($planoUrgente): ?>
        <?php
        $prazoRaw = $planoUrgente['prazo_agendado_em'] ?? '';
        $isVencido = !empty($prazoRaw) && substr($prazoRaw, 0, 10) < date('Y-m-d');
        $isHoje = !empty($prazoRaw) && substr($prazoRaw, 0, 10) === date('Y-m-d');
        ?>
        <section class="plano-urgente-card <?= $isVencido ? 'is-vencido' : ($isHoje ? 'is-hoje' : '') ?>">
            <div class="plano-urgente-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="plano-urgente-content">
                <span class="plano-urgente-tag"><?= $isVencido ? 'PRAZO EXPIRADO' : ($isHoje ? 'PRAZO HOJE' : 'PRÓXIMO PROCESSO NA FILA') ?></span>
                <h2><?= h($planoUrgente['numero']) ?> · <?= h($planoUrgente['embarcacao_nome']) ?></h2>
                <p>
                    <strong><?= h($planoUrgente['tipo_processo'] ?: 'Processo') ?> (<?= h($planoUrgente['classe_certificacao'] ?: 'NORMAM-202') ?>)</strong>
                    <?php if (!empty($planoUrgente['solicitante_nome'])): ?>
                        · Cliente: <?= h($planoUrgente['solicitante_nome']) ?>
                    <?php endif; ?>
                    <?php if (!empty($prazoRaw)): ?>
                        · Prazo: <strong><?= date('d/m/Y H:i', strtotime($prazoRaw)) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div class="plano-urgente-action">
                <a href="<?= APP_URL ?>analises-planos/form?id=<?= urlencode($planoUrgente['id']) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Analisar Agora
                </a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Faixa de Indicadores (KPIs) -->
    <section class="analista-metrics-grid">
        <article class="metric-card metric-card--primary">
            <div class="metric-card__icon"><i class="fa-solid fa-folder-open"></i></div>
            <div class="metric-card__data">
                <span>Atribuídas a Você</span>
                <strong><?= (int)($kpis['atribuidas'] ?? 0) ?></strong>
                <small>Processos em andamento</small>
            </div>
        </article>

        <article class="metric-card metric-card--warning">
            <div class="metric-card__icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="metric-card__data">
                <span>Prazo Hoje</span>
                <strong><?= (int)($kpis['hoje'] ?? 0) ?></strong>
                <small>Para entregar hoje</small>
            </div>
        </article>

        <article class="metric-card <?= ((int)($kpis['atrasadas'] ?? 0) > 0) ? 'metric-card--danger' : 'metric-card--muted' ?>">
            <div class="metric-card__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="metric-card__data">
                <span>Atrasadas</span>
                <strong><?= (int)($kpis['atrasadas'] ?? 0) ?></strong>
                <small><?= ((int)($kpis['atrasadas'] ?? 0) > 0) ? 'Requer atenção imediata' : 'Nenhuma pendência' ?></small>
            </div>
        </article>

        <article class="metric-card metric-card--amber">
            <div class="metric-card__icon"><i class="fa-solid fa-file-arrow-up"></i></div>
            <div class="metric-card__data">
                <span>Aguardando Documentos</span>
                <strong><?= (int)($kpis['aguardando_docs'] ?? 0) ?></strong>
                <small>Na mão do projetista/estaleiro</small>
            </div>
        </article>

        <article class="metric-card metric-card--success">
            <div class="metric-card__icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="metric-card__data">
                <span>Concluídas no Mês</span>
                <strong><?= (int)($kpis['concluidas_mes'] ?? 0) ?></strong>
                <small>Aprovadas neste ciclo</small>
            </div>
        </article>
    </section>

    <!-- Grade Principal: Fila de Trabalho e Histórico -->
    <div class="analista-main-grid">
        <!-- Coluna 1: Fila de Análises Prioritárias -->
        <section class="analista-card analista-card--fila">
            <div class="analista-card__header">
                <div>
                    <h2><i class="fa-solid fa-list-check"></i> Fila de Análise Técnica de Planos</h2>
                    <p>Processos organizados por prioridade e prazo de entrega.</p>
                </div>
                <span class="badge-total"><?= count($filaPlanos) ?> na fila</span>
            </div>

            <?php if (empty($filaPlanos)): ?>
                <div class="analista-empty-state">
                    <i class="fa-solid fa-drafting-compass"></i>
                    <h3>Nenhum plano pendente de análise</h3>
                    <p>Todos os processos atribuídos a você foram concluídos ou você está sem demandas ativas no momento.</p>
                    <a href="<?= APP_URL ?>analises-planos/form" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Abrir Nova Análise
                    </a>
                </div>
            <?php else: ?>
                <div class="tabela-planos-wrap">
                    <table class="tabela-planos">
                        <thead>
                            <tr>
                                <th>Processo / Embarcação</th>
                                <th>Tipo / Norma</th>
                                <th>Prazo Limite</th>
                                <th>Situação</th>
                                <th>Exigências</th>
                                <th style="text-align:right">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filaPlanos as $p): ?>
                                <?php
                                $st = $statusMap[$p['status']] ?? ['label' => $p['status'], 'badge' => 'badge-secondary', 'icon' => 'fa-circle'];
                                $prazoItem = $p['prazo_agendado_em'] ?? '';
                                $atrasadoItem = !empty($prazoItem) && substr($prazoItem, 0, 10) < date('Y-m-d');
                                $hojeItem = !empty($prazoItem) && substr($prazoItem, 0, 10) === date('Y-m-d');
                                ?>
                                <tr>
                                    <td>
                                        <div class="plano-info-cell">
                                            <strong><?= h($p['numero']) ?></strong>
                                            <span><?= h($p['embarcacao_nome']) ?></span>
                                            <?php if (!empty($p['solicitante_nome'])): ?>
                                                <small class="text-muted"><i class="fa-solid fa-user"></i> <?= h($p['solicitante_nome']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="tag-tipo-processo"><?= h($p['tipo_processo'] ?: 'LC') ?></span>
                                        <small class="tag-classe-processo"><?= h($p['classe_certificacao'] ?: 'EC1') ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($prazoItem)): ?>
                                            <div class="prazo-badge <?= $atrasadoItem ? 'is-atrasado' : ($hojeItem ? 'is-hoje' : '') ?>">
                                                <i class="fa-solid fa-clock"></i>
                                                <?= date('d/m/Y', strtotime($prazoItem)) ?>
                                                <small><?= date('H:i', strtotime($prazoItem)) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">A definir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $st['badge'] ?>">
                                            <i class="fa-solid <?= $st['icon'] ?>"></i> <?= h($st['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ((int)$p['exigencias_pendentes'] > 0): ?>
                                            <span class="badge-exigencias-alerta">
                                                <i class="fa-solid fa-triangle-exclamation"></i> <?= (int)$p['exigencias_pendentes'] ?> pendente(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-exigencias-ok">
                                                <i class="fa-solid fa-check"></i> Sem pendências
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right">
                                        <a href="<?= APP_URL ?>analises-planos/form?id=<?= urlencode($p['id']) ?>" class="btn btn-primary btn-sm btn-analisar">
                                            <i class="fa-solid fa-pen-ruler"></i> Analisar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Coluna 2: Pareceres Recentes & Histórico -->
        <aside class="analista-sidebar-col">
            <section class="analista-card analista-card--historico">
                <div class="analista-card__header">
                    <div>
                        <h3><i class="fa-solid fa-file-signature"></i> Pareceres & Relatórios</h3>
                        <p>Últimos relatórios técnicos emitidos.</p>
                    </div>
                </div>

                <?php if (empty($historicoPareceres)): ?>
                    <p class="text-muted" style="padding:15px;text-align:center">Nenhum relatório técnico emitido recentemente.</p>
                <?php else: ?>
                    <div class="historico-pareceres-list">
                        <?php foreach ($historicoPareceres as $par): ?>
                            <article class="parecer-item">
                                <div class="parecer-item__info">
                                    <strong><?= h($par['numero'] ?: 'Relatório Técnico') ?></strong>
                                    <span><?= h($par['embarcacao_nome']) ?> (<?= h($par['processo_numero']) ?>)</span>
                                    <small><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($par['criado_em'])) ?></small>
                                </div>
                                <div class="parecer-item__actions">
                                    <span class="badge badge-sm <?= $par['status'] === 'PUBLICADO' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= h($par['status']) ?>
                                    </span>
                                    <a href="<?= APP_URL ?>analises-planos/parecer-pdf?id=<?= urlencode($par['id']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Visualizar PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!empty($filaVistorias) && podeAcessar('relatorios_aprovacao')): ?>
                <section class="analista-card analista-card--vistorias">
                    <div class="analista-card__header">
                        <div>
                            <h3><i class="fa-solid fa-clipboard-check"></i> Vistorias para Homologação</h3>
                            <p>Relatórios de vistoriadores aguardando aprovação.</p>
                        </div>
                    </div>
                    <?php $old = $filaVistorias[0]; ?>
                    <div class="role-priority is-analysis<?= $old['horas']>=48?' is-overdue':'' ?>" style="padding:12px;background:#f8fafc;border-radius:8px">
                        <span><strong><?= h($old['embarcacao']) ?></strong> (<?= h($old['vistoriador']) ?>)</span>
                        <p style="margin:4px 0;font-size:0.8rem">Enviado há <?= floor($old['horas']/24) ?>d <?= $old['horas']%24 ?>h · <?= (int)$old['nao_conformes'] ?> exigência(s)</p>
                        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($old['agendamento_id']) ?>&vistoria_id=<?= urlencode($old['id']) ?>" class="btn btn-primary btn-sm">Analisar relatório</a>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Acesso rápido a Normas e Referências -->
            <section class="analista-card analista-card--normas">
                <div class="analista-card__header">
                    <h3><i class="fa-solid fa-book-bookmark"></i> Referências Rápidas</h3>
                </div>
                <div class="normas-links">
                    <a href="<?= APP_URL ?>configuracoes/normam202" class="norma-link-item">
                        <i class="fa-solid fa-list-check"></i>
                        <div>
                            <strong>NORMAM-202/DPC</strong>
                            <small>Embarcações na Navegação Interior</small>
                        </div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <a href="<?= APP_URL ?>documentacao/lc" class="norma-link-item">
                        <i class="fa-solid fa-award"></i>
                        <div>
                            <strong>Licenças de Construção (LC)</strong>
                            <small>Emissão e homologação técnica</small>
                        </div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </section>
        </aside>
    </div>
</div>

<style>
/* Estilos Exclusivos da Mesa de Análise de Planos (Design System Amazon Naval) */
.dash-analista-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
    padding-bottom: 30px;
}

.dash-analista-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.dash-analista-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #0d4a40;
    background: #e6f4f1;
    padding: 4px 10px;
    border-radius: 6px;
    margin-bottom: 8px;
    letter-spacing: 0.04em;
}
.dash-analista-header h1 {
    font-size: 1.55rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
}
.dash-analista-header p {
    color: #64748b;
    margin: 0;
    font-size: 0.92rem;
}
.dash-analista-header__actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Card de Plano Urgente */
.plano-urgente-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #2563eb;
    border-radius: 10px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}
.plano-urgente-card.is-hoje {
    border-left-color: #f59e0b;
    background: #fffdf5;
}
.plano-urgente-card.is-vencido {
    border-left-color: #ef4444;
    background: #fef2f2;
}
.plano-urgente-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.plano-urgente-card.is-hoje .plano-urgente-icon { background: #fef3c7; color: #d97706; }
.plano-urgente-card.is-vencido .plano-urgente-icon { background: #fee2e2; color: #dc2626; }
.plano-urgente-content {
    flex: 1;
}
.plano-urgente-tag {
    font-size: 0.7rem;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.05em;
    display: block;
    margin-bottom: 4px;
}
.plano-urgente-card.is-hoje .plano-urgente-tag { color: #b45309; }
.plano-urgente-card.is-vencido .plano-urgente-tag { color: #b91c1c; }
.plano-urgente-content h2 {
    font-size: 1.15rem;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.plano-urgente-content p {
    margin: 0;
    font-size: 0.88rem;
    color: #475569;
}

/* Faixa de Métricas */
.analista-metrics-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}
.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.metric-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.metric-card--primary .metric-card__icon { background: #e0f2fe; color: #0284c7; }
.metric-card--warning .metric-card__icon { background: #fef3c7; color: #d97706; }
.metric-card--danger .metric-card__icon { background: #fee2e2; color: #dc2626; }
.metric-card--amber .metric-card__icon { background: #fdf4ff; color: #a855f7; }
.metric-card--success .metric-card__icon { background: #dcfce7; color: #16a34a; }
.metric-card--muted .metric-card__icon { background: #f1f5f9; color: #94a3b8; }

.metric-card__data {
    display: flex;
    flex-direction: column;
}
.metric-card__data span {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
}
.metric-card__data strong {
    font-size: 1.6rem;
    color: #0f172a;
    font-weight: 700;
    line-height: 1.2;
    margin: 2px 0;
}
.metric-card__data small {
    font-size: 0.72rem;
    color: #94a3b8;
}

/* Grade Principal */
.analista-main-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 22px;
}
.analista-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.analista-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.analista-card__header h2, .analista-card__header h3 {
    font-size: 1.15rem;
    color: #0f172a;
    margin: 0 0 4px 0;
    font-weight: 700;
}
.analista-card__header p {
    color: #64748b;
    font-size: 0.82rem;
    margin: 0;
}
.badge-total {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
}

/* Tabela de Planos */
.tabela-planos-wrap {
    overflow-x: auto;
}
.tabela-planos {
    width: 100%;
    border-collapse: collapse;
}
.tabela-planos th {
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #64748b;
    padding: 10px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.tabela-planos td {
    padding: 14px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
    vertical-align: middle;
}
.plano-info-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.plano-info-cell strong {
    color: #0f172a;
    font-size: 0.95rem;
}
.plano-info-cell span {
    color: #334155;
    font-weight: 500;
}
.tag-tipo-processo {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 0.75rem;
    padding: 2px 7px;
    border-radius: 4px;
}
.tag-classe-processo {
    display: block;
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 3px;
}
.prazo-badge {
    display: inline-flex;
    flex-direction: column;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
}
.prazo-badge.is-atrasado { color: #dc2626; font-weight: 700; }
.prazo-badge.is-hoje { color: #d97706; font-weight: 700; }
.badge-exigencias-alerta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #b45309;
    background: #fef3c7;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}
.badge-exigencias-ok {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #15803d;
    background: #dcfce7;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

/* Coluna Lateral */
.analista-sidebar-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.historico-pareceres-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.parecer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 8px;
    gap: 10px;
}
.parecer-item__info {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}
.parecer-item__info strong {
    font-size: 0.88rem;
    color: #0f172a;
}
.parecer-item__info span {
    font-size: 0.8rem;
    color: #475569;
}
.parecer-item__info small {
    font-size: 0.72rem;
    color: #94a3b8;
}
.parecer-item__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.normas-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.norma-link-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.15s ease;
}
.norma-link-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.norma-link-item > i:first-child {
    font-size: 1.1rem;
    color: #0d4a40;
}
.norma-link-item div {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.norma-link-item div strong {
    font-size: 0.86rem;
}
.norma-link-item div small {
    font-size: 0.74rem;
    color: #64748b;
}
.norma-link-item > i:last-child {
    font-size: 0.75rem;
    color: #94a3b8;
}

.analista-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}
.analista-empty-state i {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 12px;
}
.analista-empty-state h3 {
    color: #0f172a;
    font-size: 1.1rem;
    margin: 0 0 6px 0;
}
.analista-empty-state p {
    font-size: 0.88rem;
    margin: 0 0 16px 0;
}

@media (max-width: 1100px) {
    .analista-metrics-grid { grid-template-columns: repeat(3, 1fr); }
    .analista-main-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .dash-analista-header { flex-direction: column; align-items: flex-start; }
    .analista-metrics-grid { grid-template-columns: 1fr 1fr; }
    .plano-urgente-card { flex-direction: column; align-items: flex-start; }
}
</style>
