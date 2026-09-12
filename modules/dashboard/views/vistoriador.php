<?php
$hoje = date('Y-m-d');
$agenda = $dashboard['agenda_prioritaria'] ?? [];
$historico = $dashboard['historico'] ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Vistoriador';

// Vistoria prioritária / em foco (primeira da lista)
$vistoriaFoco = $agenda[0] ?? null;

function formatarDataHoraAgenda(?string $data, ?string $hora): array
{
    if (empty($data)) return ['A definir', '--', '---', ''];
    $dt = new DateTimeImmutable($data);
    $meses = [
        '01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
        '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
        '09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ'
    ];
    $dia = $dt->format('d');
    $mes = $meses[$dt->format('m')] ?? strtoupper($dt->format('M'));
    $ano = $dt->format('Y');
    $horaFmt = !empty($hora) ? substr($hora, 0, 5) : '';
    return ["{$dia}/{$dt->format('m')}/{$ano}", $dia, $mes, $horaFmt];
}

function extrairTiposVistoria(?string $tiposRaw): array
{
    if (empty($tiposRaw)) return ['Vistoria Técnica'];
    $lista = array_filter(array_map('trim', explode(',', $tiposRaw)));
    return !empty($lista) ? $lista : ['Vistoria Técnica'];
}

function obterStatusHistoricoInfo(string $status): array
{
    return match ($status) {
        'APROVADA' => ['Aprovada', 'is-approved', 'fa-circle-check'],
        'APROVADA_COM_EXIGENCIAS' => ['Aprovada com Exigências', 'is-approved-warning', 'fa-clipboard-check'],
        'RETORNO_AS' => ['Retorno A/S Necessário', 'is-rejected', 'fa-triangle-exclamation'],
        'AGUARDANDO_APROVACAO' => ['Aguardando Aprovação', 'is-waiting', 'fa-clock'],
        'REPROVADA' => ['Reprovada', 'is-rejected', 'fa-circle-xmark'],
        default => ['Rascunho em Edição', 'is-draft', 'fa-pen-to-square'],
    };
}
?>

<style>
/* ==========================================================================
   PAINEL DO VISTORIADOR - DESIGN SYSTEM V3 (ALTA VISIBILIDADE & HIERARQUIA)
   ========================================================================== */
.dash-v-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
    padding-bottom: 30px;
}

/* 1. Header do Vistoriador */
.dash-v-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.dash-v-header__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #0f766e;
    background: #ccfbf1;
    padding: 4px 10px;
    border-radius: 999px;
    margin-bottom: 6px;
}
.dash-v-header h1 {
    margin: 0 0 4px 0;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.dash-v-header p {
    margin: 0;
    font-size: 13.5px;
    color: #475569;
}
.dash-v-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.18s ease;
    cursor: pointer;
}
.dash-v-btn--outline {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155 !important;
}
.dash-v-btn--outline:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
}

/* 2. Top KPIs Grid (Horizontal) */
.dash-v-kpis {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
}
.dash-v-kpi {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dash-v-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px -2px rgba(15, 23, 42, 0.08);
}
.dash-v-kpi__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 18px;
    flex-shrink: 0;
}
.dash-v-kpi--late .dash-v-kpi__icon { background: #fee2e2; color: #dc2626; }
.dash-v-kpi--late.is-alert { border-color: #fca5a5; background: #fffaf0; }
.dash-v-kpi--today .dash-v-kpi__icon { background: #fef3c7; color: #d97706; }
.dash-v-kpi--next .dash-v-kpi__icon { background: #ccfbf1; color: #0f766e; }
.dash-v-kpi--draft .dash-v-kpi__icon { background: #e0f2fe; color: #0284c7; }
.dash-v-kpi--waiting .dash-v-kpi__icon { background: #ede9fe; color: #7c3aed; }

.dash-v-kpi__content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.dash-v-kpi__label {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-v-kpi__value {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.15;
}
.dash-v-kpi__sub {
    font-size: 10.5px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}

/* 3. Hero Card: Próxima Vistoria / Vistoria em Foco */
.dash-v-hero {
    background: linear-gradient(135deg, #ffffff 0%, #f8fbf9 100%);
    border: 2px solid #0d9488;
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 8px 20px -4px rgba(13, 148, 136, 0.12);
    position: relative;
    overflow: hidden;
}
.dash-v-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: #0d9488;
}
.dash-v-hero__top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e6efeb;
}
.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.hero-tag--progress { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.hero-tag--danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.hero-tag--next { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

.dash-v-hero__date {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #334155;
    background: #f1f5f9;
    padding: 5px 12px;
    border-radius: 8px;
}
.dash-v-hero__date i { color: #0f766e; }

.dash-v-hero__body {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 24px;
}
.dash-v-hero__vessel {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}
.dash-v-hero__vessel > i {
    width: 48px;
    height: 48px;
    background: #0f766e;
    color: #ffffff;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(15, 118, 110, 0.25);
}
.dash-v-hero__vessel h2 {
    margin: 0 0 6px 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.dash-v-hero__vessel-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px;
    font-size: 12.5px;
    color: #64748b;
}
.dash-v-hero__vessel-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.dash-v-hero__vessel-meta strong {
    color: #1e293b;
}

.dash-v-hero__types label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}
.hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}
.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
}
.hero-chip i { color: #0d9488; font-size: 10px; }

.dash-v-hero__cta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}
.dash-v-btn--cta {
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 800;
    color: #ffffff !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 6px 16px rgba(15, 118, 110, 0.28);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dash-v-btn--cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(15, 118, 110, 0.38);
}
.dash-v-btn--continue {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
}
.dash-v-btn--start {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
}
.dash-v-btn--cta span { font-size: 14px; }
.dash-v-btn--cta small { font-size: 11px; font-weight: 500; opacity: 0.9; margin-top: 2px; }

/* 4. Lista de Próximas Vistorias */
.dash-v-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.dash-v-card__header {
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    background: #fafbfc;
}
.dash-v-card__header-info h2 {
    margin: 0 0 2px 0;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-v-card__header-info p {
    margin: 0;
    font-size: 12.5px;
    color: #64748b;
}

.dash-v-schedule-list {
    display: flex;
    flex-direction: column;
}
.dash-v-schedule-item {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    align-items: center;
    gap: 18px;
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s ease;
}
.dash-v-schedule-item:last-child {
    border-bottom: none;
}
.dash-v-schedule-item:hover {
    background: #f8fafc;
}

.calendar-box {
    width: 72px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    text-align: center;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.calendar-box__month {
    background: #0f766e;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.05em;
    padding: 3px 0;
    text-transform: uppercase;
}
.calendar-box__day {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    padding-top: 2px;
}
.calendar-box__time {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    padding-bottom: 4px;
}

.schedule-details h3 {
    margin: 0 0 5px 0;
    font-size: 15.5px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.schedule-details-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    font-size: 12.5px;
    color: #64748b;
    margin-bottom: 8px;
}
.schedule-details-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.schedule-details-meta strong {
    color: #334155;
}
.schedule-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.schedule-chip {
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    padding: 3px 8px;
    border-radius: 5px;
}

.schedule-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 999px;
    white-space: nowrap;
}
.status-pill.is-progress { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
.status-pill.is-next { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
.status-pill.is-late { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.status-pill.is-today { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.status-pill.is-as { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

.btn-schedule-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 8px;
    background: #0f766e;
    color: #ffffff !important;
    text-decoration: none;
    transition: background 0.15s ease;
    white-space: nowrap;
}
.btn-schedule-action:hover {
    background: #115e59;
}
.btn-schedule-action.is-continue {
    background: #0284c7;
}
.btn-schedule-action.is-continue:hover {
    background: #0369a1;
}

/* 5. Tabela de Histórico Recente */
.history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.history-table th {
    background: #f8fafc;
    padding: 12px 18px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.history-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}
.history-table tr:hover td {
    background: #fbfcfe;
}
.history-table tr:last-child td {
    border-bottom: none;
}
.history-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    white-space: nowrap;
}
.history-status-badge.is-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.history-status-badge.is-approved-warning { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.history-status-badge.is-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.history-status-badge.is-waiting { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.history-status-badge.is-draft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.empty-state-box {
    padding: 36px 20px;
    text-align: center;
    color: #64748b;
}
.empty-state-box i { font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block; }
.empty-state-box strong { display: block; font-size: 15px; color: #1e293b; margin-bottom: 4px; }

/* Responsividade */
@media (max-width: 1100px) {
    .dash-v-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .dash-v-hero__body {
        grid-template-columns: 1fr;
    }
    .dash-v-hero__cta {
        align-items: flex-start;
    }
}
@media (max-width: 768px) {
    .dash-v-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .dash-v-schedule-item {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .schedule-actions {
        justify-content: space-between;
    }
    .history-table th:nth-child(3),
    .history-table td:nth-child(3) {
        display: none;
    }
}
@media (max-width: 480px) {
    .dash-v-kpis {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dash-v-container">

    <!-- 1. Header do Vistoriador -->
    <header class="dash-v-header">
        <div class="dash-v-header__info">
            <span class="dash-v-header__badge"><i class="fa-solid fa-clipboard-user"></i> Painel do Vistoriador</span>
            <h1>Minha Operação de Vistorias</h1>
            <p>Acompanhe sua escala de inspeções e preencha os relatórios técnicos diretamente pelo ERP web.</p>
        </div>
        <div class="dash-v-header__actions">
            <a href="<?= APP_URL ?>vistorias" class="dash-v-btn dash-v-btn--outline">
                <i class="fa-solid fa-list-check"></i> Ver Todas as Vistorias
            </a>
        </div>
    </header>

    <!-- 2. KPIs Horizontais em Destaque -->
    <section class="dash-v-kpis" aria-label="Indicadores Operacionais">
        <div class="dash-v-kpi dash-v-kpi--late <?= ($dashboard['kpis']['atrasadas'] ?? 0) > 0 ? 'is-alert' : '' ?>">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Atrasadas</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['atrasadas'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub"><?= ($dashboard['kpis']['atrasadas'] ?? 0) > 0 ? 'Atenção necessária' : 'Nenhuma em atraso' ?></small>
            </div>
        </div>
        <div class="dash-v-kpi dash-v-kpi--today">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Vistorias de Hoje</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['hoje'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub"><?= ($dashboard['kpis']['hoje'] ?? 0) > 0 ? 'Para realizar hoje' : 'Sem vistorias hoje' ?></small>
            </div>
        </div>
        <div class="dash-v-kpi dash-v-kpi--next">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Próximas Agendadas</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['proximas'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub">Na sua escala futura</small>
            </div>
        </div>
        <div class="dash-v-kpi dash-v-kpi--draft">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-pen-to-square"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Rascunhos em Aberto</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['rascunhos'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub">Em andamento no ERP</small>
            </div>
        </div>
        <div class="dash-v-kpi dash-v-kpi--waiting">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Aguardando Aprovação</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['enviados'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub">Em análise técnica</small>
            </div>
        </div>
    </section>

    <!-- 3. Card Hero: Vistoria em Foco / Próxima Ação Principal -->
    <?php if ($vistoriaFoco): ?>
        <?php
        $urlFoco = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($vistoriaFoco['id'])
            . (!empty($vistoriaFoco['vistoria_id']) ? '&vistoria_id=' . urlencode($vistoriaFoco['vistoria_id']) : '');
        [$dataFmtFoco, $diaFoco, $mesFoco, $horaFmtFoco] = formatarDataHoraAgenda($vistoriaFoco['data_vistoria'], $vistoriaFoco['hora_vistoria']);
        $tiposFoco = extrairTiposVistoria($vistoriaFoco['tipo_vistoria']);
        $isPendenteFoco = ($vistoriaFoco['vistoria_status'] ?? '') === 'PENDENTE';
        $isRetornoAsFoco = ($vistoriaFoco['retorno_tipo'] ?? '') === 'AS';
        ?>
        <section class="dash-v-hero" aria-label="Vistoria em Destaque">
            <div class="dash-v-hero__top">
                <div class="dash-v-hero__tag">
                    <?php if ($isRetornoAsFoco): ?>
                        <span class="hero-tag hero-tag--danger"><i class="fa-solid fa-triangle-exclamation"></i> Retorno A/S — Bloqueio de Navegação</span>
                    <?php elseif ($isPendenteFoco): ?>
                        <span class="hero-tag hero-tag--progress"><i class="fa-solid fa-spinner fa-spin"></i> Vistoria em Andamento no ERP Web</span>
                    <?php else: ?>
                        <span class="hero-tag hero-tag--next"><i class="fa-solid fa-star"></i> Próxima Vistoria da Escala</span>
                    <?php endif; ?>
                </div>
                <div class="dash-v-hero__date">
                    <i class="fa-regular fa-calendar"></i>
                    <span>Agendada para <strong><?= h($dataFmtFoco) ?></strong><?= $horaFmtFoco ? ' às <strong>' . h($horaFmtFoco) . '</strong>' : '' ?></span>
                </div>
            </div>

            <div class="dash-v-hero__body">
                <div class="dash-v-hero__main-info">
                    <div class="dash-v-hero__vessel">
                        <i class="fa-solid fa-ship"></i>
                        <div>
                            <h2><?= h($vistoriaFoco['embarcacao']) ?></h2>
                            <div class="dash-v-hero__vessel-meta">
                                <span><i class="fa-solid fa-id-card"></i> Reg.: <strong><?= h($vistoriaFoco['registro'] ?: 'Sem inscrição informada') ?></strong></span>
                                <span><i class="fa-solid fa-user"></i> Cliente: <strong><?= h($vistoriaFoco['cliente'] ?: 'Não informado') ?></strong></span>
                                <span><i class="fa-solid fa-location-dot"></i> Local: <strong><?= h($vistoriaFoco['local'] ?: 'A definir') ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="dash-v-hero__types">
                        <label>Serviços / Vistorias do Agendamento:</label>
                        <div class="hero-chips">
                            <?php foreach ($tiposFoco as $tf): ?>
                                <span class="hero-chip"><i class="fa-solid fa-circle-check"></i> <?= h($tf) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="dash-v-hero__cta">
                    <?php if ($isPendenteFoco): ?>
                        <a href="<?= h($urlFoco) ?>" class="dash-v-btn dash-v-btn--cta dash-v-btn--continue">
                            <span><i class="fa-solid fa-pen-to-square"></i> Continuar Vistoria no ERP</span>
                            <small>Abrir Relatório Técnico Web <i class="fa-solid fa-arrow-right"></i></small>
                        </a>
                    <?php else: ?>
                        <a href="<?= h($urlFoco) ?>" class="dash-v-btn dash-v-btn--cta dash-v-btn--start">
                            <span><i class="fa-solid fa-play"></i> Iniciar Vistoria no ERP</span>
                            <small>Abrir Relatório Técnico Web <i class="fa-solid fa-arrow-right"></i></small>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- 4. Lista Completa de Próximas Vistorias da Agenda -->
    <section class="dash-v-card" aria-label="Agenda de Vistorias">
        <header class="dash-v-card__header">
            <div class="dash-v-card__header-info">
                <h2><i class="fa-solid fa-calendar-days" style="color: #0f766e;"></i> Escala de Próximas Vistorias</h2>
                <p>Todas as vistorias atribuídas a você ordenadas por data e prioridade.</p>
            </div>
            <span class="badge badge-info" style="font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 999px;">
                <?= count($agenda) ?> vistoria<?= count($agenda) === 1 ? '' : 's' ?> na agenda
            </span>
        </header>

        <?php if (empty($agenda)): ?>
            <div class="empty-state-box">
                <i class="fa-regular fa-calendar-check"></i>
                <strong>Nenhuma vistoria pendente na sua escala</strong>
                <span>Novos agendamentos atribuídos a você aparecerão automaticamente aqui.</span>
            </div>
        <?php else: ?>
            <div class="dash-v-schedule-list">
                <?php foreach ($agenda as $item): ?>
                    <?php
                    $urlItem = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($item['id'])
                        . (!empty($item['vistoria_id']) ? '&vistoria_id=' . urlencode($item['vistoria_id']) : '');
                    [$dataFmt, $dia, $mes, $horaFmt] = formatarDataHoraAgenda($item['data_vistoria'], $item['hora_vistoria']);
                    $tiposItem = extrairTiposVistoria($item['tipo_vistoria']);
                    $isEmAndamento = ($item['vistoria_status'] ?? '') === 'PENDENTE';
                    $isAS = ($item['retorno_tipo'] ?? '') === 'AS';
                    $isAtrasada = !empty($item['data_vistoria']) && $item['data_vistoria'] < $hoje;
                    $isHoje = !empty($item['data_vistoria']) && $item['data_vistoria'] === $hoje;
                    ?>
                    <div class="dash-v-schedule-item">
                        <div class="calendar-box">
                            <div class="calendar-box__month"><?= h($mes) ?></div>
                            <div class="calendar-box__day"><?= h($dia) ?></div>
                            <div class="calendar-box__time"><?= $horaFmt ? h($horaFmt) : 'SEM HORA' ?></div>
                        </div>

                        <div class="schedule-details">
                            <h3>
                                <i class="fa-solid fa-ship" style="color: #0f766e; font-size: 14px;"></i>
                                <?= h($item['embarcacao']) ?>
                            </h3>
                            <div class="schedule-details-meta">
                                <span><i class="fa-solid fa-id-card"></i> <?= h($item['registro'] ?: 'Sem inscrição') ?></span>
                                <span><i class="fa-solid fa-user"></i> Cliente: <strong><?= h($item['cliente'] ?: 'Não informado') ?></strong></span>
                                <span><i class="fa-solid fa-location-dot"></i> Local: <strong><?= h($item['local'] ?: 'A definir') ?></strong></span>
                            </div>
                            <div class="schedule-chips">
                                <?php foreach ($tiposItem as $tipo): ?>
                                    <span class="schedule-chip"><?= h($tipo) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="schedule-actions">
                            <?php if ($isAS): ?>
                                <span class="status-pill is-as"><i class="fa-solid fa-triangle-exclamation"></i> Retorno A/S</span>
                            <?php elseif ($isAtrasada): ?>
                                <span class="status-pill is-late"><i class="fa-solid fa-clock-rotate-left"></i> Atrasada</span>
                            <?php elseif ($isHoje): ?>
                                <span class="status-pill is-today"><i class="fa-solid fa-calendar-day"></i> Hoje</span>
                            <?php elseif ($isEmAndamento): ?>
                                <span class="status-pill is-progress"><i class="fa-solid fa-spinner fa-spin"></i> Em Andamento</span>
                            <?php else: ?>
                                <span class="status-pill is-next"><i class="fa-solid fa-check"></i> Próxima</span>
                            <?php endif; ?>

                            <a href="<?= h($urlItem) ?>" class="btn-schedule-action <?= $isEmAndamento ? 'is-continue' : '' ?>">
                                <i class="fa-solid <?= $isEmAndamento ? 'fa-pen-to-square' : 'fa-play' ?>"></i>
                                <?= $isEmAndamento ? 'Continuar Vistoria' : 'Iniciar Vistoria' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 5. Histórico Recente de Relatórios Técnicos -->
    <section class="dash-v-card" aria-label="Histórico Recente">
        <header class="dash-v-card__header">
            <div class="dash-v-card__header-info">
                <h2><i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i> Histórico Recente de Relatórios</h2>
                <p>Últimos relatórios técnicos emitidos ou movimentados por você.</p>
            </div>
            <a href="<?= APP_URL ?>vistorias" class="dash-v-btn dash-v-btn--outline" style="font-size: 12px; padding: 6px 12px;">
                Ver Histórico Completo <i class="fa-solid fa-arrow-right"></i>
            </a>
        </header>

        <?php if (empty($historico)): ?>
            <div class="empty-state-box">
                <i class="fa-regular fa-folder-open"></i>
                <strong>Nenhum relatório registrado até o momento</strong>
                <span>Seus relatórios técnicos salvos e concluídos ficarão arquivados aqui.</span>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Data</th>
                            <th>Embarcação</th>
                            <th>Tipos de Vistoria</th>
                            <th style="width: 140px;">Nº Relatório</th>
                            <th style="width: 170px; text-align: center;">Resultado / Status</th>
                            <th style="width: 130px; text-align: right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $v): ?>
                            <?php
                            $urlHist = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($v['agendamento_id']) . '&vistoria_id=' . urlencode($v['id']);
                            $urlDetalhe = APP_URL . 'vistorias/detalhe?id=' . urlencode($v['id']);
                            $dataHist = !empty($v['data_vistoria']) ? date('d/m/Y', strtotime($v['data_vistoria'])) : 'Sem data';
                            [$labelStatus, $classStatus, $iconStatus] = obterStatusHistoricoInfo((string)$v['status']);
                            $tiposHist = extrairTiposVistoria($v['tipo_vistoria']);
                            ?>
                            <tr>
                                <td>
                                    <strong style="color: #0f172a;"><?= h($dataHist) ?></strong>
                                </td>
                                <td>
                                    <strong style="font-size: 13.5px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-ship" style="color: #0f766e; font-size: 12px;"></i>
                                        <?= h($v['embarcacao']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span style="color: #475569; font-size: 12px;">
                                        <?= h(implode(', ', array_slice($tiposHist, 0, 3))) . (count($tiposHist) > 3 ? ' +' . (count($tiposHist) - 3) . ' mais' : '') ?>
                                    </span>
                                </td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-weight: 700; color: #0f766e; font-size: 11.5px;">
                                        <?= h($v['numero'] ?: 'RASCUNHO') ?>
                                    </code>
                                </td>
                                <td style="text-align: center;">
                                    <span class="history-status-badge <?= h($classStatus) ?>">
                                        <i class="fa-solid <?= h($iconStatus) ?>"></i>
                                        <?= h($labelStatus) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= h($urlHist) ?>" class="dash-v-btn dash-v-btn--outline" style="font-size: 11.5px; padding: 5px 10px;" title="Abrir Relatório Técnico">
                                        <i class="fa-solid fa-pen-to-square"></i> Abrir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
