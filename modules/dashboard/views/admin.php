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
$totalDocumentosPendentes = (int)($acoes['documentos_pendentes'] ?? 0);
$totalPendencias = $totalAssinadas + $totalAprovacoes + $totalRetornos + $totalDocumentosPendentes;

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Administrador';
$usuarioCargo = function_exists('getCargo') ? getCargo() : ($_SESSION['usuario_cargo'] ?? null);
$isSecretaria = in_array($usuarioCargo, ['SECRETARIA', 'ATENDIMENTO'], true);
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
            <a href="<?= APP_URL ?>comercial/nova" class="btn btn-primary">
                <i class="fa-solid fa-file-circle-plus"></i> Gerar Proposta
            </a>
            <?php if ($isSecretaria): ?>
                <a href="<?= APP_URL ?>agendamentos/form" class="btn btn-secondary" title="Cadastrar novo agendamento de vistoria ou análise">
                    <i class="fa-solid fa-calendar-plus"></i> Novo Agendamento
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>analises-planos/form" class="btn btn-secondary">
                    <i class="fa-solid fa-drafting-compass"></i> Nova Análise
                </a>
            <?php endif; ?>
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

            <!-- Coluna 4: Pranchas e Documentos Pendentes -->
            <div class="dash-decision-col" id="documentos-pendentes">
                <div class="dash-decision-col__header">
                    <span class="dash-decision-col__title">
                        <i class="fa-solid fa-cloud-arrow-up text-primary"></i> Pranchas Pendentes
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.08);"><?= $totalDocumentosPendentes ?></span>
                </div>
                <?php if (empty($dashboard['fluxo_documentos_pendentes'])): ?>
                    <div class="dash-decision-empty">
                        <i class="fa-solid fa-circle-check text-success"></i> Todas as embarcações com pranchas anexadas.
                    </div>
                <?php else: ?>
                    <?php foreach ($dashboard['fluxo_documentos_pendentes'] as $docP): ?>
                        <div class="dash-decision-item" style="border-left: 3px solid #0284c7; cursor:pointer;" onclick="abrirModalAnexoSecretaria('<?= h($docP['id']) ?>', '<?= h(addslashes($docP['embarcacao'])) ?>', '<?= h(addslashes($docP['numero'])) ?>', '<?= h(addslashes($docP['analista_nome'] ?? 'Analista Técnico')) ?>')" title="Clique para anexar pranchas e documentos do armador">
                            <div class="dash-decision-item__vessel"><?= h($docP['embarcacao']) ?></div>
                            <div class="dash-decision-item__meta">
                                <span><?= h($docP['numero']) ?></span> · 
                                <strong style="color: #38bdf8;">
                                    <?= h($docP['analista_nome'] ? 'Analista: ' . $docP['analista_nome'] : 'Sem analista') ?>
                                </strong>
                            </div>
                            <div class="dash-decision-item__cta" style="color:#38bdf8;">Anexar pranchas <i class="fa-solid fa-paperclip"></i></div>
                        </div>
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
                            <?php foreach ($dashboard['analises_recentes'] as $an): 
                                $armadorFiles = (int)($an['arquivos_armador'] ?? 0);
                            ?>
                                <div class="dash-subitem" style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                    <a href="<?= APP_URL ?>analises-planos/form?id=<?= urlencode($an['id']) ?>" style="flex:1; text-decoration:none; color:inherit; display:flex; flex-direction:column; min-width:0;">
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <strong style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= h($an['embarcacao']) ?></strong>
                                            <?php if ($armadorFiles === 0): ?>
                                                <span class="badge" style="background:rgba(239, 68, 68, 0.2); color:#f87171; font-size:0.68rem; padding:2px 6px;" title="Nenhuma prancha recebida do armador">Sem Pranchas</span>
                                            <?php else: ?>
                                                <span class="badge" style="background:rgba(2, 132, 199, 0.2); color:#38bdf8; font-size:0.68rem; padding:2px 6px;" title="<?= $armadorFiles ?> documento(s) recebido(s) do armador"><i class="fa-solid fa-file-check"></i> <?= $armadorFiles ?> doc(s)</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: var(--cor-texto-secundario); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= h($an['numero']) ?> · <?= h($an['servico_nome'] ?: mb_strimwidth($an['objeto'], 0, 24, '…')) ?>
                                            <?php if (!empty($an['analista_nome'])): ?>
                                                · <span style="color:#94a3b8;"><i class="fa-solid fa-user-gear"></i> <?= h($an['analista_nome']) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </a>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; font-size: 0.68rem; padding:4px 6px;">
                                            <?= h($an['status']) ?>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; padding:4px 8px; border-radius:6px; white-space:nowrap;" onclick="abrirModalAnexoSecretaria('<?= h($an['id']) ?>', '<?= h(addslashes($an['embarcacao'])) ?>', '<?= h(addslashes($an['numero'])) ?>', '<?= h(addslashes($an['analista_nome'] ?? 'Analista Técnico')) ?>')" title="Anexar pranchas e documentos do armador">
                                            <i class="fa-solid fa-paperclip"></i> Anexar
                                        </button>
                                    </div>
                                </div>
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

<!-- Modal Autodidático: Anexar Pranchas e Documentos do Armador pela Secretaria -->
<div id="modalAnexoSecretaria" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:99999; overflow-y:auto; padding:20px 10px;">
    <div style="max-width: 620px; margin: 30px auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.35); overflow:hidden; color:#1e293b;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background: #f8fafc;">
            <div>
                <h3 style="margin:0; font-size:1.15rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-cloud-arrow-up text-primary"></i> Anexar Pranchas do Armador
                </h3>
                <small class="text-muted" style="display:block; margin-top:3px; font-size:0.8rem; color:#64748b;">
                    Os arquivos serão enviados diretamente para a mesa técnica do Analista Naval (Pranchas Recebidas do Armador).
                </small>
            </div>
            <button type="button" onclick="fecharModalAnexoSecretaria()" style="background:transparent; border:none; color:#64748b; font-size:1.4rem; cursor:pointer;" title="Fechar">&times;</button>
        </div>
        
        <form method="post" action="<?= APP_URL ?>analises-planos/actions" enctype="multipart/form-data" style="padding: 24px; background:#fff;" onsubmit="return validarEnvioAnexoSecretaria(this);">
            <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
            <input type="hidden" name="action" value="adicionar_submissao">
            <input type="hidden" name="analise_id" id="modal_analise_id" value="">
            <input type="hidden" name="retorno_dashboard" value="1">

            <!-- Card de identificação da embarcação e processo -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#166534; font-weight:700; letter-spacing:0.5px;">Embarcação em Análise</span>
                    <strong id="modal_embarcacao_nome" style="display:block; font-size:1.1rem; color:#14532d;">--</strong>
                </div>
                <div style="text-align:right;">
                    <span id="modal_processo_numero" class="badge bg-secondary" style="font-size:0.8rem;">--</span>
                    <small id="modal_analista_nome" style="display:block; font-size:0.78rem; color:#15803d; margin-top:2px; font-weight:600;">Analista: --</small>
                </div>
            </div>

            <!-- Categoria NORMAM -->
            <div class="form-group mb-3">
                <label for="modal_categoria" style="font-size:0.88rem; font-weight:600; color:#1e293b; margin-bottom:6px; display:block;">
                    Categoria da Prancha / Documento *
                </label>
                <select name="categoria" id="modal_categoria" required class="form-control" style="width:100%; height:42px; border-radius:8px; background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; padding:6px 12px; font-size:0.9rem;">
                    <option value="Memorial Descritivo">Memorial Descritivo (NORMAM-202)</option>
                    <option value="Plano de Arranjo Geral">Plano de Arranjo Geral / Segurança / Luzes</option>
                    <option value="ART / CREA">ART / Termo de Responsabilidade Técnica</option>
                    <option value="Plano de Linhas e Curvas">Plano de Linhas e Curvas Hidrostáticas</option>
                    <option value="Estudo de Estabilidade">Estudo de Estabilidade / Folheto de Trim</option>
                    <option value="Seção Mestra / Estrutural">Plano de Seção Mestra e Perfil Estrutural</option>
                    <option value="Cálculos de Arqueação e Borda Livre">Notas de Arqueação / Borda Livre</option>
                    <option value="Documentos Gerais do Armador" selected>Documentos Gerais / Projeto Naval</option>
                </select>
                <small class="text-muted" style="display:block; margin-top:4px; font-size:0.76rem; color:#64748b;">
                    Selecione a disciplina naval correspondente ao arquivo que está sendo entregue pelo cliente.
                </small>
            </div>

            <!-- Data de Recebimento e Descrição -->
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px; margin-bottom:16px;">
                <div class="form-group">
                    <label for="modal_recebido_em" style="font-size:0.88rem; font-weight:600; color:#1e293b; margin-bottom:6px; display:block;">
                        Data Recebimento *
                    </label>
                    <input type="date" name="recebido_em" id="modal_recebido_em" value="<?= date('Y-m-d') ?>" required class="form-control" style="width:100%; height:42px; border-radius:8px; background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; padding:6px 12px; font-size:0.9rem;">
                </div>
                <div class="form-group">
                    <label for="modal_descricao" style="font-size:0.88rem; font-weight:600; color:#1e293b; margin-bottom:6px; display:block;">
                        Origem / Observações (Opcional)
                    </label>
                    <input type="text" name="descricao" id="modal_descricao" placeholder="Ex: Entregue via WhatsApp pelo armador" maxlength="255" class="form-control" style="width:100%; height:42px; border-radius:8px; background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; padding:6px 12px; font-size:0.9rem;">
                </div>
            </div>

            <!-- Upload de Arquivos (PDF / DWG) -->
            <div class="form-group mb-4">
                <label style="font-size:0.88rem; font-weight:600; color:#1e293b; margin-bottom:6px; display:block;">
                    Selecionar Arquivos (PDF, DWG, DXF, DOCX, ZIP, Imagens) *
                </label>
                <div id="modal_dropzone" style="border: 2px dashed #0284c7; border-radius: 10px; padding: 22px; text-align: center; background: #f0f9ff; cursor: pointer; transition: background 0.2s;" onclick="document.getElementById('modal_input_arquivos').click()">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.2rem; color: #0284c7; margin-bottom: 8px;"></i>
                    <p style="margin: 0; font-size: 0.95rem; color: #0f172a; font-weight: 600;">Clique para escolher ou arraste as pranchas aqui</p>
                    <small style="color: #64748b; font-size: 0.78rem;">Suporta arquivos de até 100MB (PDF, DWG, DXF, ZIP, etc.)</small>
                    <input type="file" name="arquivos[]" id="modal_input_arquivos" multiple style="display:none;" onchange="aoSelecionarArquivosSecretaria(this)">
                </div>
                <div id="modal_lista_arquivos" style="display:none;"></div>
            </div>

            <!-- Ações -->
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:18px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalAnexoSecretaria()">Cancelar</button>
                <button type="submit" id="btn_submit_anexo_secretaria" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Pranchas para o Analista
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAnexoSecretaria(analiseId, embarcacaoNome, processoNumero, analistaNome) {
    var modal = document.getElementById('modalAnexoSecretaria');
    if (!modal) return;
    document.getElementById('modal_analise_id').value = analiseId;
    document.getElementById('modal_embarcacao_nome').textContent = embarcacaoNome || 'Embarcação';
    document.getElementById('modal_processo_numero').textContent = processoNumero || '';
    document.getElementById('modal_analista_nome').textContent = 'Analista: ' + (analistaNome || 'A definir');
    document.getElementById('modal_lista_arquivos').style.display = 'none';
    document.getElementById('modal_lista_arquivos').innerHTML = '';
    document.getElementById('modal_input_arquivos').value = '';
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function fecharModalAnexoSecretaria() {
    var modal = document.getElementById('modalAnexoSecretaria');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function formatarTamanhoArquivo(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function obterIconeArquivoSecretaria(nome) {
    var ext = (nome && nome.indexOf('.') !== -1) ? nome.split('.').pop().toLowerCase() : '';
    switch (ext) {
        case 'pdf':
            return {
                icon: 'fa-solid fa-file-pdf',
                color: '#dc2626',
                bg: '#fef2f2',
                border: '#fecaca',
                label: 'PDF'
            };
        case 'dwg':
        case 'dxf':
            return {
                icon: 'fa-solid fa-compass-drafting',
                color: '#0284c7',
                bg: '#f0f9ff',
                border: '#bae6fd',
                label: ext.toUpperCase()
            };
        case 'doc':
        case 'docx':
            return {
                icon: 'fa-solid fa-file-word',
                color: '#2563eb',
                bg: '#eff6ff',
                border: '#bfdbfe',
                label: 'WORD'
            };
        case 'xls':
        case 'xlsx':
        case 'csv':
            return {
                icon: 'fa-solid fa-file-excel',
                color: '#16a34a',
                bg: '#f0fdf4',
                border: '#bbf7d0',
                label: 'EXCEL'
            };
        case 'zip':
        case 'rar':
        case '7z':
            return {
                icon: 'fa-solid fa-file-zipper',
                color: '#d97706',
                bg: '#fffbeb',
                border: '#fde68a',
                label: 'COMPACTADO'
            };
        case 'png':
        case 'jpg':
        case 'jpeg':
            return {
                icon: 'fa-solid fa-file-image',
                color: '#7c3aed',
                bg: '#f5f3ff',
                border: '#ddd6fe',
                label: 'IMAGEM'
            };
        default:
            return {
                icon: 'fa-solid fa-file-lines',
                color: '#475569',
                bg: '#f8fafc',
                border: '#e2e8f0',
                label: (ext ? ext.toUpperCase() : 'DOC')
            };
    }
}

function aoSelecionarArquivosSecretaria(input) {
    var lista = document.getElementById('modal_lista_arquivos');
    if (!input.files || input.files.length === 0) {
        lista.style.display = 'none';
        lista.innerHTML = '';
        return;
    }

    var totalBytes = 0;
    for (var i = 0; i < input.files.length; i++) {
        totalBytes += input.files[i].size;
    }

    var html = '<div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:12px; margin-top:12px;">';
    
    // Cabeçalho da listagem em alto contraste
    html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">';
    html += '  <div style="font-size:0.86rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:6px;">';
    html += '    <i class="fa-solid fa-circle-check" style="color:#10b981;"></i>';
    html += '    <span>' + input.files.length + ' arquivo' + (input.files.length > 1 ? 's prontos' : ' pronto') + ' para envio</span>';
    html += '  </div>';
    html += '  <span style="font-size:0.75rem; font-weight:600; color:#475569; background:#e2e8f0; padding:2px 8px; border-radius:12px;">';
    html += '    Total: ' + formatarTamanhoArquivo(totalBytes);
    html += '  </span>';
    html += '</div>';

    // Lista visual de arquivos
    html += '<div style="max-height:220px; overflow-y:auto; padding-right:4px; display:flex; flex-direction:column; gap:8px;">';

    for (var i = 0; i < input.files.length; i++) {
        var f = input.files[i];
        var info = obterIconeArquivoSecretaria(f.name);
        var sizeText = formatarTamanhoArquivo(f.size);
        var nomeLimpo = f.name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        html += '<div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between; gap:10px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">';
        
        // Esquerda: Ícone grande colorido + Detalhes do arquivo em texto escuro
        html += '  <div style="display:flex; align-items:center; gap:10px; min-width:0; flex:1;">';
        html += '    <div style="width:36px; height:36px; border-radius:8px; background:' + info.bg + '; border:1px solid ' + info.border + '; display:flex; align-items:center; justify-content:center; flex-shrink:0;">';
        html += '      <i class="' + info.icon + '" style="font-size:1.25rem; color:' + info.color + ';"></i>';
        html += '    </div>';
        html += '    <div style="min-width:0; flex:1;">';
        html += '      <div style="font-size:0.86rem; font-weight:600; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + nomeLimpo + '">';
        html +=          nomeLimpo;
        html += '      </div>';
        html += '      <div style="font-size:0.74rem; color:#64748b; display:flex; align-items:center; gap:6px; margin-top:2px;">';
        html += '        <span style="background:' + info.bg + '; color:' + info.color + '; font-weight:700; font-size:0.68rem; padding:1px 5px; border-radius:4px; border:1px solid ' + info.border + ';">' + info.label + '</span>';
        html += '        <span>' + sizeText + '</span>';
        html += '      </div>';
        html += '    </div>';
        html += '  </div>';

        // Direita: Botão de remoção individual
        html += '  <button type="button" onclick="removerArquivoSecretaria(' + i + ')" title="Remover este arquivo" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; width:28px; height:28px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; flex-shrink:0; transition:all 0.15s;" onmouseover="this.style.color=\'#ef4444\'; this.style.borderColor=\'#fca5a5\'; this.style.background=\'#fef2f2\';" onmouseout="this.style.color=\'#94a3b8\'; this.style.borderColor=\'#e2e8f0\'; this.style.background=\'#f8fafc\';">';
        html += '    <i class="fa-solid fa-xmark"></i>';
        html += '  </button>';

        html += '</div>';
    }

    html += '</div></div>';

    lista.innerHTML = html;
    lista.style.display = 'block';
}

function removerArquivoSecretaria(index) {
    var input = document.getElementById('modal_input_arquivos');
    if (!input || !input.files) return;

    try {
        var dt = new DataTransfer();
        for (var i = 0; i < input.files.length; i++) {
            if (i !== index) {
                dt.items.add(input.files[i]);
            }
        }
        input.files = dt.files;
    } catch (e) {
        console.warn('DataTransfer não suportado', e);
    }
    aoSelecionarArquivosSecretaria(input);
}

function validarEnvioAnexoSecretaria(form) {
    var input = document.getElementById('modal_input_arquivos');
    if (!input.files || input.files.length === 0) {
        alert('Por favor, selecione ao menos uma prancha ou documento PDF/DWG para enviar.');
        return false;
    }
    var btn = document.getElementById('btn_submit_anexo_secretaria');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando arquivos...';
    return true;
}

// Fechar com ESC ou clicando fora
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModalAnexoSecretaria();
});
document.addEventListener('click', function(e) {
    var modal = document.getElementById('modalAnexoSecretaria');
    if (modal && e.target === modal) fecharModalAnexoSecretaria();
});

// Drag & Drop na Dropzone de Pranchas
(function() {
    var dropzone = document.getElementById('modal_dropzone');
    if (!dropzone) return;
    ['dragenter', 'dragover'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.background = '#e0f2fe';
            dropzone.style.borderColor = '#0369a1';
        }, false);
    });
    ['dragleave'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.background = '#f0f9ff';
            dropzone.style.borderColor = '#0284c7';
        }, false);
    });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.style.background = '#f0f9ff';
        dropzone.style.borderColor = '#0284c7';
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            var input = document.getElementById('modal_input_arquivos');
            input.files = e.dataTransfer.files;
            aoSelecionarArquivosSecretaria(input);
        }
    }, false);
})();
</script>
