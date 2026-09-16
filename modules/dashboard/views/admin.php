<?php
/**
 * MÓDULO: DASHBOARD
 * Arquivo: views/admin.php - Central de Comando Executiva da Diretoria Naval
 * Reformulado: Foco no que realmente importa, zero redundância, métricas navais reais.
 */

$meta = $dashboard['meta'] ?? ['valor' => 0, 'realizado' => 0, 'percentual' => 0];
$resumo = $dashboard['resumo_executivo'] ?? [];
$acoes = $dashboard['acoes'] ?? [];

$totalAssinadas = (int)($acoes['assinadas'] ?? 0);
$totalAprovacoes = (int)($acoes['aprovacao'] ?? 0);
$totalRetornos = (int)($acoes['retornos_as'] ?? 0);
$totalCustodia = (int)($resumo['dossies_custodia'] ?? 0);
$totalPendencias = $totalAssinadas + $totalAprovacoes + $totalRetornos;

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Administrador';
?>

<style>
/* ========================================================
 * DASHBOARD EXECUTIVO DO ADMIN - DESIGN SYSTEM NAVAL V3
 * ======================================================== */
.dash-admin-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 24px;
}

/* 1. Header Executivo */
.dash-admin-header {
    background: var(--bg-surface, #071f1b);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
    border-radius: 14px;
    padding: 22px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.dash-admin-header__info h1 {
    margin: 0 0 6px;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--cor-texto, #ffffff);
    display: flex;
    align-items: center;
    gap: 10px;
}

.dash-admin-header__info p {
    margin: 0;
    color: var(--cor-texto-secundario, #94a3b8);
    font-size: 0.88rem;
}

.dash-admin-header__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.dash-admin-header__actions .btn {
    font-size: 0.82rem;
    font-weight: 600;
    padding: 8px 14px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* 2. Grid de KPIs Principais */
.dash-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}

.dash-kpi-card {
    background: var(--bg-surface, #071f1b);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.08));
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none !important;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
}

.dash-kpi-card:hover {
    transform: translateY(-3px);
    border-color: var(--cor-destaque, #56e0ad);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
}

.dash-kpi-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.dash-kpi-card__icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.dash-kpi-card__title {
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--cor-texto-secundario, #94a3b8);
}

.dash-kpi-card__value {
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--cor-texto, #ffffff);
    line-height: 1.1;
    margin-bottom: 4px;
}

.dash-kpi-card__sub {
    font-size: 0.76rem;
    color: var(--cor-texto-secundario, #94a3b8);
    display: flex;
    align-items: center;
    gap: 5px;
}

.dash-kpi-card__sub strong {
    color: var(--cor-destaque, #56e0ad);
}

/* Cores temáticas dos KPIs */
.kpi-frota .dash-kpi-card__icon { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
.kpi-vistorias .dash-kpi-card__icon { background: rgba(86, 224, 173, 0.15); color: #56e0ad; }
.kpi-certificados .dash-kpi-card__icon { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
.kpi-engenharia .dash-kpi-card__icon { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.kpi-financeiro .dash-kpi-card__icon { background: rgba(34, 197, 94, 0.15); color: #4ade80; }

/* 3. Painel de Ações Críticas & Decisões */
.dash-decisions-panel {
    background: var(--bg-surface, #071f1b);
    border: 1px solid <?= $totalPendencias > 0 ? 'rgba(245, 158, 11, 0.35)' : 'var(--border, rgba(255, 255, 255, 0.08))' ?>;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
}

.dash-decisions-header {
    background: <?= $totalPendencias > 0 ? 'rgba(245, 158, 11, 0.08)' : 'rgba(255, 255, 255, 0.02)' ?>;
    padding: 14px 22px;
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.dash-decisions-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--cor-texto, #ffffff);
}

.dash-decisions-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
}

.dash-decisions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1px;
    background: var(--border, rgba(255, 255, 255, 0.06));
}

.dash-decision-col {
    background: var(--bg-surface, #071f1b);
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dash-decision-col__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.dash-decision-col__title {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--cor-texto, #ffffff);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-decision-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.08));
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    text-decoration: none !important;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.dash-decision-item:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: var(--cor-destaque, #56e0ad);
}

.dash-decision-item__vessel {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--cor-texto, #ffffff);
}

.dash-decision-item__meta {
    font-size: 0.76rem;
    color: var(--cor-texto-secundario, #94a3b8);
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.dash-decision-item__cta {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--cor-destaque, #56e0ad);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 2px;
}

.dash-decision-empty {
    color: var(--cor-texto-secundario, #94a3b8);
    font-size: 0.82rem;
    padding: 16px 0;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* 4. Layout 65% / 35% */
.dash-main-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
}

@media (max-width: 1024px) {
    .dash-main-layout {
        grid-template-columns: 1fr;
    }
}

.dash-card-section {
    background: var(--bg-surface, #071f1b);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.08));
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.dash-card-section:last-child {
    margin-bottom: 0;
}

.dash-card-header {
    padding: 14px 20px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.06));
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dash-card-header h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--cor-texto, #ffffff);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-card-header a {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--cor-destaque, #56e0ad);
    text-decoration: none;
}

.dash-card-header a:hover {
    text-decoration: underline;
}

/* Tabela de Vistorias Recentes */
.dash-vistorias-table {
    width: 100%;
    border-collapse: collapse;
}

.dash-vistorias-table th {
    padding: 10px 16px;
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--cor-texto-secundario, #94a3b8);
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.06));
    text-align: left;
}

.dash-vistorias-table td {
    padding: 12px 16px;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.04));
    color: var(--cor-texto, #ffffff);
    vertical-align: middle;
}

.dash-vistorias-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

.dash-vessel-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dash-vessel-thumb {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    background: #000;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
}

.dash-status-badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-block;
    white-space: nowrap;
}

.status-andamento { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
.status-analise { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.status-aprovada { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
.status-retorno { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

/* Sub-grid de Engenharia e Capitania */
.dash-subgrid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
    background: var(--border, rgba(255, 255, 255, 0.06));
}

@media (max-width: 768px) {
    .dash-subgrid {
        grid-template-columns: 1fr;
    }
}

.dash-subcol {
    background: var(--bg-surface, #071f1b);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dash-subitem {
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.025);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.05));
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none !important;
    font-size: 0.82rem;
    color: var(--cor-texto, #ffffff);
    transition: background 0.15s;
}

.dash-subitem:hover {
    background: rgba(255, 255, 255, 0.06);
    color: var(--cor-destaque, #56e0ad);
}

/* Coluna Lateral */
.dash-agenda-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.05));
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none !important;
    color: var(--cor-texto, #ffffff);
    transition: background 0.15s;
}

.dash-agenda-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.dash-agenda-time {
    padding: 4px 8px;
    background: rgba(86, 224, 173, 0.15);
    color: var(--cor-destaque, #56e0ad);
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
}

.dash-activity-item {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.04));
    display: flex;
    gap: 10px;
    font-size: 0.8rem;
    color: var(--cor-texto-secundario, #94a3b8);
}

.dash-activity-item strong {
    color: var(--cor-texto, #ffffff);
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}
</style>

<div class="dash-admin-container">

    <!-- 1. Header Executivo da Central de Comando -->
    <header class="dash-admin-header">
        <div class="dash-admin-header__info">
            <h1><i class="fa-solid fa-compass-drafting text-accent"></i> Central de Comando da Diretoria</h1>
            <p>Olá, <strong><?= h($usuarioNome) ?></strong>. Visão executiva da frota naval, controle técnico de vistorias, engenharia, trâmites e finanças.</p>
        </div>
        <div class="dash-admin-header__actions">
            <a href="<?= APP_URL ?>agendamentos/form" class="btn btn-primary">
                <i class="fa-solid fa-calendar-plus"></i> Novo Agendamento
            </a>
            <a href="<?= APP_URL ?>analises-planos/form" class="btn btn-secondary">
                <i class="fa-solid fa-drafting-compass"></i> Nova Análise
            </a>
            <a href="<?= APP_URL ?>protocolos/form" class="btn btn-secondary">
                <i class="fa-solid fa-folder-plus"></i> Novo Dossiê
            </a>
            <a href="<?= APP_URL ?>certificados" class="btn btn-secondary">
                <i class="fa-solid fa-award"></i> Certificados
            </a>
        </div>
    </header>

    <!-- 2. Grid de KPIs Operacionais Navais Modernos -->
    <section class="dash-kpi-grid">
        <!-- Frota Naval -->
        <a href="<?= APP_URL ?>embarcacoes" class="dash-kpi-card kpi-frota">
            <div class="dash-kpi-card__top">
                <span class="dash-kpi-card__title">Frota Naval</span>
                <div class="dash-kpi-card__icon"><i class="fa-solid fa-ship"></i></div>
            </div>
            <div class="dash-kpi-card__value"><?= (int)($resumo['embarcacoes_total'] ?? 0) ?></div>
            <div class="dash-kpi-card__sub">Embarcações · <strong><?= (int)($resumo['clientes_ativos'] ?? 0) ?> clientes</strong></div>
        </a>

        <!-- Vistorias no Mês -->
        <a href="<?= APP_URL ?>vistorias" class="dash-kpi-card kpi-vistorias">
            <div class="dash-kpi-card__top">
                <span class="dash-kpi-card__title">Vistorias Técnicas</span>
                <div class="dash-kpi-card__icon"><i class="fa-solid fa-clipboard-check"></i></div>
            </div>
            <div class="dash-kpi-card__value"><?= (int)($resumo['vistorias_mes'] ?? 0) ?></div>
            <div class="dash-kpi-card__sub">No mês · <strong><?= (int)($dashboard['kpis']['aprovacao'] ?? 100) ?>% aprovação</strong></div>
        </a>

        <!-- Certificados Emitidos -->
        <a href="<?= APP_URL ?>certificados" class="dash-kpi-card kpi-certificados">
            <div class="dash-kpi-card__top">
                <span class="dash-kpi-card__title">Certificados Emitidos</span>
                <div class="dash-kpi-card__icon"><i class="fa-solid fa-award"></i></div>
            </div>
            <div class="dash-kpi-card__value"><?= (int)($resumo['certificados_mes'] ?? 0) ?></div>
            <div class="dash-kpi-card__sub">Com selo e validação QR Code</div>
        </a>

        <!-- Engenharia & Dossiês -->
        <a href="<?= APP_URL ?>analises-planos" class="dash-kpi-card kpi-engenharia">
            <div class="dash-kpi-card__top">
                <span class="dash-kpi-card__title">Engenharia & Planos</span>
                <div class="dash-kpi-card__icon"><i class="fa-solid fa-compass-drafting"></i></div>
            </div>
            <div class="dash-kpi-card__value"><?= (int)($resumo['analises_em_aberto'] ?? 0) ?></div>
            <div class="dash-kpi-card__sub">Planos em aberto · <strong><?= (int)($resumo['dossies_total'] ?? 0) ?> dossiês</strong></div>
        </a>

        <!-- Saúde Financeira -->
        <a href="<?= APP_URL ?>financeiro" class="dash-kpi-card kpi-financeiro">
            <div class="dash-kpi-card__top">
                <span class="dash-kpi-card__title">Contas a Receber</span>
                <div class="dash-kpi-card__icon"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="dash-kpi-card__value" style="font-size: 1.35rem;">
                <?= formatarMoeda((float)($resumo['financeiro_receber'] ?? 0)) ?>
            </div>
            <div class="dash-kpi-card__sub">
                Recebido no mês: <strong><?= formatarMoeda((float)($resumo['financeiro_recebido'] ?? 0)) ?></strong>
            </div>
        </a>
    </section>

    <!-- 3. Painel de Decisões & Ações Operacionais (Zero Redundância) -->
    <section class="dash-decisions-panel">
        <div class="dash-decisions-header">
            <h2 class="dash-decisions-title">
                <i class="fa-solid fa-bolt" style="color: <?= $totalPendencias > 0 ? '#f59e0b' : 'var(--cor-destaque, #56e0ad)' ?>;"></i>
                Fila de Ações & Decisões Operacionais
            </h2>
            <?php if ($totalPendencias > 0): ?>
                <span class="dash-decisions-badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <?= $totalPendencias ?> pendência<?= $totalPendencias === 1 ? '' : 's' ?> que precisa<?= $totalPendencias === 1 ? '' : 'm' ?> de ação
                </span>
            <?php else: ?>
                <span class="dash-decisions-badge" style="background: rgba(86, 224, 173, 0.15); color: var(--cor-destaque, #56e0ad); border: 1px solid rgba(86, 224, 173, 0.3);">
                    <i class="fa-solid fa-circle-check"></i> Operação 100% em dia
                </span>
            <?php endif; ?>
        </div>

        <div class="dash-decisions-grid">
            <!-- Coluna 1: Retornos de Exigências / A/S (Mais Crítico) -->
            <div class="dash-decision-col" id="retornos-as">
                <div class="dash-decision-col__header">
                    <span class="dash-decision-col__title">
                        <i class="fa-solid fa-triangle-exclamation text-warning"></i> Retornos de Exigências
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.08);"><?= $totalRetornos ?></span>
                </div>
                <?php if (empty($dashboard['fluxo_retornos_as'])): ?>
                    <div class="dash-decision-empty">
                        <i class="fa-solid fa-circle-check text-success"></i> Nenhum retorno pendente de agendamento.
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['fluxo_retornos_as'] as $ret): 
                        $retClass = ($ret['tipo'] ?? '') === 'AS' ? 'return-as' : 'return-requirements';
                    ?>
                        <a href="<?= APP_URL ?>agendamentos/form?relatorio_origem_id=<?= urlencode($ret['relatorio_origem_id']) ?>" class="dash-decision-item <?= $retClass ?>" style="border-left: 3px solid #f59e0b;">
                            <div class="dash-decision-item__vessel"><?= h($ret['embarcacao']) ?></div>
                            <div class="dash-decision-item__meta">
                                <span><?= h($ret['numero']) ?></span> · 
                                <strong style="color: <?= ($ret['tipo'] ?? '') === 'AS' ? '#ef4444' : '#fbbf24' ?>;">
                                    <?= ($ret['tipo'] ?? '') === 'AS' ? 'Retorno A/S (Impeditivo)' : 'Retorno de Exigências' ?>
                                </strong>
                            </div>
                            <div class="dash-decision-item__cta">Agendar retorno <i class="fa-solid fa-arrow-right"></i></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Coluna 2: Relatórios Aguardando Homologação/Aprovação -->
            <div class="dash-decision-col">
                <div class="dash-decision-col__header">
                    <span class="dash-decision-col__title">
                        <i class="fa-solid fa-clipboard-check text-info"></i> Aprovação de Vistorias
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.08);"><?= $totalAprovacoes ?></span>
                </div>
                <?php if (empty($dashboard['fluxo_aprovacoes'])): ?>
                    <div class="dash-decision-empty">
                        <i class="fa-solid fa-circle-check text-success"></i> Nenhum relatório aguardando análise.
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['fluxo_aprovacoes'] as $item): ?>
                        <a href="<?= $item['agendamento_id'] ? APP_URL.'vistorias/relatorio?agendamento_id='.urlencode($item['agendamento_id']).'&vistoria_id='.urlencode($item['id']) : APP_URL.'vistorias/detalhe?id='.urlencode($item['id']) ?>" class="dash-decision-item" style="border-left: 3px solid #0284c7;">
                            <div class="dash-decision-item__vessel"><?= h($item['embarcacao']) ?></div>
                            <div class="dash-decision-item__meta">
                                <span><?= h($item['vistoriador']) ?></span> · 
                                <span><?= (int)$item['nao_conformes'] ?> exigências</span>
                            </div>
                            <div class="dash-decision-item__cta">Analisar relatório <i class="fa-solid fa-arrow-right"></i></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Coluna 3: Propostas Assinadas (Converter em Agendamento) -->
            <div class="dash-decision-col">
                <div class="dash-decision-col__header">
                    <span class="dash-decision-col__title">
                        <i class="fa-solid fa-file-signature text-success"></i> Propostas Assinadas
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.08);"><?= $totalAssinadas ?></span>
                </div>
                <?php if (empty($dashboard['fluxo_assinadas'])): ?>
                    <div class="dash-decision-empty">
                        <i class="fa-solid fa-circle-check text-success"></i> Nenhuma proposta aguardando agendamento.
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['fluxo_assinadas'] as $prop): 
                        $urlAgendar = $prop['agendamento_id'] ? APP_URL.'agendamentos/form?id='.urlencode($prop['agendamento_id']) : APP_URL.'agendamentos/form?proposta_id='.urlencode($prop['proposta_id']);
                    ?>
                        <a href="<?= h($urlAgendar) ?>" class="dash-decision-item" style="border-left: 3px solid #10b981;">
                            <div class="dash-decision-item__vessel"><?= h($prop['numero'] ?: 'Proposta assinada') ?></div>
                            <div class="dash-decision-item__meta">
                                <span><?= h($prop['cliente']) ?></span> · <span><?= h($prop['embarcacao']) ?></span>
                            </div>
                            <div class="dash-decision-item__cta">Agendar vistoria <i class="fa-solid fa-arrow-right"></i></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 4. Grid Principal da Operação (65% / 35%) -->
    <div class="dash-main-layout">
        <!-- Coluna Esquerda: Vistorias Recentes e Engenharia/Capitania -->
        <div>
            <!-- Vistorias Recentes -->
            <section class="dash-card-section">
                <header class="dash-card-header">
                    <h2><i class="fa-solid fa-ship text-accent"></i> Vistorias Recentes da Frota</h2>
                    <a href="<?= APP_URL ?>vistorias">Ver todas <i class="fa-solid fa-chevron-right"></i></a>
                </header>

                <?php if (empty($dashboard['vistorias_recentes'])): ?>
                    <div style="padding: 30px; text-align: center; color: var(--cor-texto-secundario);">
                        <i class="fa-solid fa-ship fa-2x mb-2 opacity-25"></i>
                        <p style="margin: 0;">Nenhuma vistoria registrada recentemente.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="dash-vistorias-table">
                            <thead>
                                <tr>
                                    <th>Embarcação</th>
                                    <th>Serviço</th>
                                    <th>Vistoriador</th>
                                    <th>Situação</th>
                                    <th>Data</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['vistorias_recentes'] as $vistoria):
                                    $statusMap = [
                                        'PENDENTE' => ['Em andamento', 'status-andamento'],
                                        'AGUARDANDO_APROVACAO' => ['Aguardando análise', 'status-analise'],
                                        'APROVADA' => ['Aprovada', 'status-aprovada'],
                                        'APROVADA_COM_EXIGENCIAS' => ['Aprovada c/ exigências', 'status-aprovada'],
                                        'RETORNO_AS' => ['Retorno A/S', 'status-retorno'],
                                        'REPROVADA' => ['Reprovada', 'status-retorno'],
                                        'CANCELADA' => ['Cancelada', 'status-andamento'],
                                    ];
                                    [$statusLabel, $statusClass] = $statusMap[$vistoria['status']] ?? ['Em andamento', 'status-andamento'];
                                    $foto = trim((string)($vistoria['foto_url'] ?? '')) ?: APP_URL . 'assets/img/portal-hero-ship.png';
                                    $dataVistoria = !empty($vistoria['data_vistoria']) ? date('d/m/Y', strtotime($vistoria['data_vistoria'])) : '--/--/----';
                                ?>
                                <tr>
                                    <td>
                                        <div class="dash-vessel-cell">
                                            <img src="<?= h($foto) ?>" alt="" class="dash-vessel-thumb">
                                            <div>
                                                <strong style="color: var(--cor-texto); display: block;"><?= h($vistoria['embarcacao']) ?></strong>
                                                <small style="color: var(--cor-texto-secundario); font-size: 0.75rem;"><?= h($vistoria['registro'] ?: $vistoria['numero'] ?: 'Sem registro') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: var(--cor-texto-secundario);">
                                            <?= h(mb_strimwidth($vistoria['servico'], 0, 32, '…')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.82rem; font-weight: 500;">
                                            <?= h($vistoria['vistoriador']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dash-status-badge <?= $statusClass ?>">
                                            <?= h($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: var(--cor-texto-secundario);">
                                            <?= $dataVistoria ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= h($vistoria['url']) ?>" class="btn btn-sm btn-outline-secondary" style="padding: 3px 8px;" title="Abrir Vistoria">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Engenharia & Protocolos Navais -->
            <section class="dash-card-section">
                <header class="dash-card-header">
                    <h2><i class="fa-solid fa-folder-tree text-accent"></i> Engenharia & Dossiês na Capitania</h2>
                    <div style="display: flex; gap: 10px;">
                        <a href="<?= APP_URL ?>analises-planos">Análises (<?= (int)($resumo['analises_em_aberto'] ?? 0) ?>)</a>
                        <span style="color: var(--border);">·</span>
                        <a href="<?= APP_URL ?>protocolos">Dossiês (<?= (int)($resumo['dossies_total'] ?? 0) ?>)</a>
                    </div>
                </header>

                <div class="dash-subgrid">
                    <!-- Coluna Análises de Planos -->
                    <div class="dash-subcol">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--cor-texto-secundario); text-transform: uppercase;">
                            <i class="fa-solid fa-compass-drafting text-accent"></i> Processos de Engenharia
                        </div>
                        <?php if (empty($dashboard['analises_recentes'])): ?>
                            <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; padding: 12px 0;">
                                Nenhuma análise em aberto.
                            </div>
                        <?php else: ?>
                            <?php foreach ($dashboard['analises_recentes'] as $an): ?>
                                <a href="<?= APP_URL ?>analises-planos/form?id=<?= urlencode($an['id']) ?>" class="dash-subitem">
                                    <div>
                                        <strong style="display: block;"><?= h($an['embarcacao']) ?></strong>
                                        <small style="color: var(--cor-texto-secundario);"><?= h($an['numero']) ?> · <?= h(mb_strimwidth($an['objeto'], 0, 24, '…')) ?></small>
                                    </div>
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; font-size: 0.7rem;">
                                        <?= h($an['status']) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Coluna Dossiês Navais -->
                    <div class="dash-subcol">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--cor-texto-secundario); text-transform: uppercase;">
                            <i class="fa-solid fa-folder-open text-accent"></i> Dossiês & Trâmite SISAP
                        </div>
                        <?php if (empty($dashboard['dossies_recentes'])): ?>
                            <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; padding: 12px 0;">
                                Nenhum dossiê registrado.
                            </div>
                        <?php else: ?>
                            <?php foreach ($dashboard['dossies_recentes'] as $dos): ?>
                                <a href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($dos['id']) ?>" class="dash-subitem">
                                    <div>
                                        <strong style="display: block;"><?= h($dos['numero']) ?></strong>
                                        <small style="color: var(--cor-texto-secundario);"><?= h($dos['embarcacao'] ?: 'Geral') ?> · <?= h(mb_strimwidth($dos['assunto'], 0, 24, '…')) ?></small>
                                    </div>
                                    <span class="badge" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8; font-size: 0.7rem;">
                                        <?= h($dos['status']) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Coluna Direita (340px): Agenda de Hoje, Vistoriadores em Campo e Feed de Auditoria -->
        <div>
            <!-- Agenda Operacional de Hoje -->
            <section class="dash-card-section">
                <header class="dash-card-header">
                    <h2><i class="fa-regular fa-calendar-check text-accent"></i> Agenda de Hoje</h2>
                    <span><?= date('d/m/Y') ?></span>
                </header>
                <?php if (empty($dashboard['agenda'])): ?>
                    <div style="padding: 24px; text-align: center; color: var(--cor-texto-secundario); font-size: 0.85rem;">
                        <i class="fa-regular fa-calendar fa-2x mb-2 opacity-25"></i>
                        <p style="margin: 0 0 10px;">Sem vistorias agendadas para hoje.</p>
                        <a href="<?= APP_URL ?>agendamentos/form" class="btn btn-sm btn-outline-primary" style="font-size: 0.78rem;">
                            + Agendar Vistoria
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['agenda'] as $ag): ?>
                        <a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($ag['id']) ?>" class="dash-agenda-item">
                            <span class="dash-agenda-time">
                                <?= $ag['hora_vistoria'] ? substr($ag['hora_vistoria'], 0, 5) : '--:--' ?>
                            </span>
                            <div>
                                <strong style="display: block; font-size: 0.88rem;"><?= h($ag['embarcacao']) ?></strong>
                                <small style="color: var(--cor-texto-secundario); font-size: 0.75rem;">
                                    <?= h($ag['tipo_vistoria']) ?> · <?= h($ag['vistoriador'] ?: 'Sem vistoriador') ?>
                                </small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <div style="padding: 10px; text-align: center; border-top: 1px solid var(--border, rgba(255,255,255,0.05));">
                        <a href="<?= APP_URL ?>agendamentos" style="font-size: 0.78rem; color: var(--cor-destaque, #56e0ad); font-weight: 600;">
                            Ver calendário completo <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Vistoriadores & Carga Técnica -->
            <section class="dash-card-section">
                <header class="dash-card-header">
                    <h2><i class="fa-solid fa-users-gear text-accent"></i> Vistoriadores em Campo</h2>
                    <a href="<?= APP_URL ?>usuarios">Equipe</a>
                </header>
                <?php if (empty($dashboard['equipe'])): ?>
                    <div style="padding: 20px; text-align: center; color: var(--cor-texto-secundario); font-size: 0.85rem;">
                        Nenhum vistoriador ativo cadastrado.
                    </div>
                <?php else: ?>
                    <div style="padding: 10px 16px;">
                        <?php foreach ($dashboard['equipe'] as $vist): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border, rgba(255,255,255,0.04)); font-size: 0.82rem;">
                                <div style="font-weight: 600; color: var(--cor-texto);">
                                    <i class="fa-solid fa-user-tie" style="color: var(--cor-texto-secundario); margin-right: 6px;"></i>
                                    <?= h($vist['nome']) ?>
                                </div>
                                <div style="display: flex; gap: 8px; font-size: 0.74rem;">
                                    <span class="badge" style="background: rgba(86, 224, 173, 0.1); color: var(--cor-destaque, #56e0ad);" title="Vistorias Futuras">
                                        <?= (int)$vist['futuras'] ?> fut.
                                    </span>
                                    <?php if ((int)$vist['atrasadas'] > 0): ?>
                                        <span class="badge bg-danger" title="Vistorias em Atraso">
                                            <?= (int)$vist['atrasadas'] ?> atr.
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Atividade Recente do Sistema -->
            <section class="dash-card-section">
                <header class="dash-card-header">
                    <h2><i class="fa-solid fa-clock-rotate-left text-accent"></i> Atividades Recentes</h2>
                </header>
                <?php if (empty($dashboard['atividade'])): ?>
                    <div style="padding: 20px; text-align: center; color: var(--cor-texto-secundario); font-size: 0.85rem;">
                        Nenhuma atividade recente registrada.
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['atividade'] as $ativ): ?>
                        <div class="dash-activity-item">
                            <i class="fa-solid fa-circle-check text-accent" style="margin-top: 3px; font-size: 0.85rem;"></i>
                            <div>
                                <strong><?= h($ativ['descricao']) ?></strong>
                                <span><?= h($ativ['usuario'] ?: 'Sistema') ?> · <?= date('d/m H:i', strtotime($ativ['criado_em'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
