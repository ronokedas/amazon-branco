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
    return !empty($lista) ? array_values(array_unique($lista)) : ['Vistoria Técnica'];
}

function obterStatusHistoricoInfo(string $status, string $assinaturaStatus = ''): array
{
    if (in_array($status, ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true) && $assinaturaStatus !== 'ASSINADO') {
        return ['Aprovada · Aguardando Assinatura', 'is-waiting-signature', 'fa-file-signature'];
    }
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
    grid-template-columns: repeat(6, minmax(0, 1fr));
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
.dash-v-kpi--signature .dash-v-kpi__icon { background: #ecfdf5; color: #059669; }
.dash-v-kpi--signature.is-alert-signature {
    border: 2px solid #10b981;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.22);
    animation: pulseSignature 2.5s infinite;
}
.dash-v-kpi--signature.is-alert-signature .dash-v-kpi__icon {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
}
.dash-v-kpi--signature.is-alert-signature .dash-v-kpi__value {
    color: #065f46;
}
.dash-v-kpi--signature.is-alert-signature .dash-v-kpi__sub {
    color: #047857;
    font-weight: 700;
}
@keyframes pulseSignature {
    0%, 100% { box-shadow: 0 4px 14px rgba(16, 185, 129, 0.22); border-color: #10b981; }
    50% { box-shadow: 0 4px 22px rgba(16, 185, 129, 0.45); border-color: #059669; }
}

/* Hero Banner para Assinaturas Pendentes */
.dash-v-signature-section {
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
    border: 2px solid #10b981;
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.18);
    position: relative;
    overflow: hidden;
}
.dash-v-signature-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: #10b981;
}
.dash-v-signature-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid #d1fae5;
}
.dash-v-signature-header__info {
    flex: 1;
    min-width: 280px;
}
.dash-v-signature-pill-pulse {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #dcfce7;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.dash-v-signature-pill-pulse .pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulseDot 1.8s infinite;
}
@keyframes pulseDot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.dash-v-signature-header h2 {
    margin: 0 0 6px 0;
    font-size: 20px;
    font-weight: 800;
    color: #064e3b;
    display: flex;
    align-items: center;
    gap: 10px;
}
.dash-v-signature-header p {
    margin: 0;
    font-size: 13.5px;
    color: #166534;
    line-height: 1.45;
}
.dash-v-btn--all-signatures {
    background: #ffffff;
    border: 1px solid #a7f3d0;
    color: #065f46 !important;
    font-size: 13px;
    font-weight: 700;
}
.dash-v-btn--all-signatures:hover {
    background: #ecfdf5;
    border-color: #059669;
    color: #047857 !important;
}

.dash-v-signature-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.dash-v-signature-warning__content {
    display: flex;
    align-items: center;
    gap: 12px;
}
.dash-v-signature-warning__content > i {
    font-size: 20px;
    color: #d97706;
}
.dash-v-signature-warning__content strong {
    font-size: 13px;
    color: #92400e;
    display: block;
}
.dash-v-signature-warning__content p {
    margin: 2px 0 0 0;
    font-size: 12px;
    color: #b45309;
}
.dash-v-btn--warning-profile {
    background: #d97706;
    border-color: #d97706;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    padding: 7px 14px;
}
.dash-v-btn--warning-profile:hover {
    background: #b45309;
}

.dash-v-signature-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.dash-v-sig-card {
    background: #ffffff;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.08);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dash-v-sig-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.15);
}
.dash-v-sig-card__main {
    flex: 1;
    min-width: 280px;
}
.dash-v-sig-card__badge-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.dash-v-sig-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}
.dash-v-sig-badge--report {
    background: #f1f5f9;
    color: #0f766e;
    border: 1px solid #cbd5e1;
    font-family: monospace;
    font-size: 12px;
}
.dash-v-sig-badge--approved {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.dash-v-sig-badge--warning {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.dash-v-sig-badge--pending {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-weight: 800;
}
.dash-v-sig-card__vessel-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}
.dash-v-sig-card__vessel-title i {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #10b981;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 14px;
    flex-shrink: 0;
}
.dash-v-sig-card__vessel-title h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}
.dash-v-sig-card__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px;
    font-size: 12px;
    color: #475569;
}
.dash-v-sig-card__meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.dash-v-sig-card__meta strong {
    color: #1e293b;
}
.dash-v-sig-card__actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.dash-v-btn--sign-now {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff !important;
    border: none;
    font-size: 13.5px;
    font-weight: 800;
    padding: 10px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dash-v-btn--sign-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.45);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}
.dash-v-btn--view-pdf {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155 !important;
    font-size: 12.5px;
    font-weight: 700;
    padding: 9px 14px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.dash-v-btn--view-pdf:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
}
.dash-v-btn--outline-subtle {
    background: #ffffff;
    border: 1px solid #d1fae5;
    color: #065f46 !important;
    font-size: 12.5px;
    font-weight: 700;
    padding: 9px 14px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.dash-v-btn--outline-subtle:hover {
    background: #ecfdf5;
    border-color: #10b981;
}

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
.history-status-badge.is-waiting-signature { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; font-weight: 800; }
.history-status-badge.is-draft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.empty-state-box {
    padding: 36px 20px;
    text-align: center;
    color: #64748b;
}
.empty-state-box i { font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block; }
.empty-state-box strong { display: block; font-size: 15px; color: #1e293b; margin-bottom: 4px; }

/* Responsividade */
@media (max-width: 1280px) {
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
        <a href="#secaoAssinaturasPendentes" class="dash-v-kpi dash-v-kpi--signature <?= ($dashboard['kpis']['aguardando_assinatura'] ?? 0) > 0 ? 'is-alert-signature' : '' ?>" style="text-decoration: none; color: inherit;">
            <div class="dash-v-kpi__icon"><i class="fa-solid fa-file-signature"></i></div>
            <div class="dash-v-kpi__content">
                <span class="dash-v-kpi__label">Aguardando Assinatura</span>
                <strong class="dash-v-kpi__value"><?= (int)($dashboard['kpis']['aguardando_assinatura'] ?? 0) ?></strong>
                <small class="dash-v-kpi__sub"><?= ($dashboard['kpis']['aguardando_assinatura'] ?? 0) > 0 ? 'Aprovado · Assinar' : 'Nenhuma pendente' ?></small>
            </div>
        </a>
    </section>

    <!-- 2.5 Hero Banner: Relatórios Técnicos Aprovados Aguardando Assinatura -->
    <?php
    $assinaturasPendentes = $dashboard['aguardando_assinatura_lista'] ?? [];
    $qtdAssinaturasPendentes = count($assinaturasPendentes);
    $temAssinaturaConfigurada = !empty($dashboard['minha_assinatura_ativa']);
    ?>
    <?php if ($qtdAssinaturasPendentes > 0): ?>
        <section id="secaoAssinaturasPendentes" class="dash-v-signature-section" aria-label="Relatórios Aprovados Aguardando Assinatura">
            <div class="dash-v-signature-header">
                <div class="dash-v-signature-header__info">
                    <div class="dash-v-signature-pill-pulse">
                        <span class="pulse-dot"></span>
                        <i class="fa-solid fa-bell"></i>
                        <span>AÇÃO OBRIGATÓRIA DA DIRETORIA</span>
                    </div>
                    <h2>
                        <i class="fa-solid fa-file-signature" style="color: #059669;"></i>
                        <?= $qtdAssinaturasPendentes === 1 ? '1 Relatório Técnico Aprovado Aguardando Sua Assinatura' : "{$qtdAssinaturasPendentes} Relatórios Técnicos Aprovados Aguardando Sua Assinatura" ?>
                    </h2>
                    <p>
                        A diretoria técnica revisou e <strong>aprovou</strong> seu relatório de vistoria. Para concluir o trâmite formal e liberar a emissão dos certificados da embarcação, aplique sua assinatura digital/manuscrita com fé pública.
                    </p>
                </div>
                <div class="dash-v-signature-header__action">
                    <a href="<?= APP_URL ?>minhas-assinaturas" class="dash-v-btn dash-v-btn--all-signatures">
                        <i class="fa-solid fa-signature"></i> Ver Minhas Assinaturas (<?= $qtdAssinaturasPendentes ?>)
                    </a>
                </div>
            </div>

            <?php if (!$temAssinaturaConfigurada): ?>
                <div class="dash-v-signature-warning">
                    <div class="dash-v-signature-warning__content">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Assinatura manuscrita ou registro profissional pendentes no seu perfil</strong>
                            <p>Para assinar eletronicamente com validade jurídica perante a Marinha do Brasil (NORMAM), cadastre sua rubrica e número de registro.</p>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>perfil#assinatura" class="dash-v-btn dash-v-btn--warning-profile">
                        <i class="fa-solid fa-pen-nib"></i> Configurar Assinatura no Perfil
                    </a>
                </div>
            <?php endif; ?>

            <div class="dash-v-signature-cards">
                <?php foreach ($assinaturasPendentes as $pend): ?>
                    <?php
                    $dataAprovFmt = !empty($pend['data_aprovacao']) ? date('d/m/Y \à\s H:i', strtotime($pend['data_aprovacao'])) : 'Recente';
                    $dataVistFmt = !empty($pend['data_vistoria']) ? date('d/m/Y', strtotime($pend['data_vistoria'])) : '--/--/----';
                    $urlPdf = APP_URL . 'vistorias/relatorio_pdf?id=' . urlencode($pend['id']);
                    $urlVistoria = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($pend['agendamento_id'] ?? '') . '&vistoria_id=' . urlencode($pend['id']);
                    $isComExigencias = ($pend['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS';
                    ?>
                    <div class="dash-v-sig-card">
                        <div class="dash-v-sig-card__main">
                            <div class="dash-v-sig-card__badge-row">
                                <span class="dash-v-sig-badge dash-v-sig-badge--report">
                                    <i class="fa-solid fa-file-lines"></i> Relatório <?= h($pend['numero'] ?: 'Sem número') ?>
                                </span>
                                <?php if ($isComExigencias): ?>
                                    <span class="dash-v-sig-badge dash-v-sig-badge--warning">
                                        <i class="fa-solid fa-clipboard-check"></i> Aprovado com Exigências
                                    </span>
                                <?php else: ?>
                                    <span class="dash-v-sig-badge dash-v-sig-badge--approved">
                                        <i class="fa-solid fa-circle-check"></i> Aprovado pela Diretoria
                                    </span>
                                <?php endif; ?>
                                <span class="dash-v-sig-badge dash-v-sig-badge--pending">
                                    <i class="fa-solid fa-hourglass-half"></i> Aguardando Sua Assinatura
                                </span>
                            </div>

                            <div class="dash-v-sig-card__vessel-title">
                                <i class="fa-solid fa-ship"></i>
                                <h3><?= h($pend['embarcacao']) ?></h3>
                            </div>

                            <div class="dash-v-sig-card__meta">
                                <span><i class="fa-solid fa-id-card"></i> Inscrição: <strong><?= h($pend['registro']) ?></strong></span>
                                <span><i class="fa-solid fa-user"></i> Cliente: <strong><?= h($pend['cliente'] ?: 'Não informado') ?></strong></span>
                                <span><i class="fa-regular fa-calendar-check"></i> Vistoria: <strong><?= h($dataVistFmt) ?></strong></span>
                                <span><i class="fa-solid fa-user-check"></i> Aprovado por: <strong><?= h($pend['aprovador_nome']) ?></strong> (<?= h($dataAprovFmt) ?>)</span>
                            </div>
                        </div>

                        <div class="dash-v-sig-card__actions">
                            <button type="button" 
                                    class="dash-v-btn dash-v-btn--sign-now js-assinar-dash"
                                    data-id="<?= h($pend['id']) ?>"
                                    data-numero="<?= h($pend['numero']) ?>"
                                    data-embarcacao="<?= h($pend['embarcacao']) ?>"
                                    data-tem-assinatura="<?= $temAssinaturaConfigurada ? '1' : '0' ?>"
                                    title="Assinar este relatório imediatamente">
                                <i class="fa-solid fa-pen-nib"></i>
                                <span>Assinar Agora</span>
                            </button>
                            <a href="<?= h($urlPdf) ?>" target="_blank" class="dash-v-btn dash-v-btn--view-pdf" title="Visualizar prévia do relatório em PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                                <span>Ver PDF</span>
                            </a>
                            <a href="<?= APP_URL ?>minhas-assinaturas" class="dash-v-btn dash-v-btn--outline-subtle" title="Abrir tela de Minhas Assinaturas">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>Minhas Assinaturas</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
                        <span class="hero-tag hero-tag--danger return-as"><i class="fa-solid fa-triangle-exclamation"></i> Retorno A/S — Bloqueio de Navegação</span>
                    <?php elseif (($vistoriaFoco['retorno_tipo'] ?? '') === 'EXIGENCIAS'): ?>
                        <span class="hero-tag hero-tag--warning return-requirements"><i class="fa-solid fa-clipboard-check"></i> Retorno de Exigências</span>
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
                            $assStatus = (string)($v['assinatura_status'] ?? '');
                            [$labelStatus, $classStatus, $iconStatus] = obterStatusHistoricoInfo((string)$v['status'], $assStatus);
                            $tiposHist = extrairTiposVistoria($v['tipo_vistoria']);
                            $pendenteAssinaturaHist = in_array($v['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true) && $assStatus !== 'ASSINADO';
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
                                    <?php if ($pendenteAssinaturaHist): ?>
                                        <a href="<?= APP_URL ?>minhas-assinaturas" class="dash-v-btn" style="font-size: 11.5px; padding: 5px 12px; background: #10b981; color: #ffffff !important; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);" title="Assinar Relatório Aprovado">
                                            <i class="fa-solid fa-pen-nib"></i> Assinar
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= h($urlHist) ?>" class="dash-v-btn dash-v-btn--outline" style="font-size: 11.5px; padding: 5px 10px;" title="Abrir Relatório Técnico">
                                            <i class="fa-solid fa-pen-to-square"></i> Abrir
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Modal de Confirmação de Assinatura no Dashboard -->
<div id="modalAssinaturaDash" style="display:none;position:fixed;inset:0;background:rgba(4,35,28,.65);z-index:10050;align-items:center;justify-content:center;padding:18px">
    <div style="background:#fff;border-radius:14px;width:min(540px,100%);overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,0.25);">
        <div style="padding:20px;background:#064e3b;color:#fff;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-file-signature" style="font-size: 20px;"></i>
            <strong style="font-size:16px;">Confirmar Assinatura do Relatório Técnico</strong>
        </div>
        <div style="padding:22px">
            <p id="dashAssinaturaDocInfo" style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:15px;"></p>
            <p id="dashAssinaturaDescricao" style="font-size:13.5px;color:#475569;margin-bottom:14px;line-height:1.5;">
                Será aplicada a assinatura manuscrita cadastrada no seu perfil profissional. Sua localização geográfica, endereço IP, carimbo de data e hora serão registrados com fé pública e validade perante a NORMAM.
            </p>
            <div id="dashAssinaturaMsg" class="alert" style="display:none;margin-bottom:14px;"></div>
        </div>
        <div style="padding:15px 22px;background:#f8fafc;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-secondary" id="dashCancelarAssinatura">Cancelar</button>
            <button type="button" class="btn btn-success" id="dashConfirmarAssinatura" style="background:#059669;border-color:#059669;font-weight:700;">
                <i class="fa-solid fa-location-dot"></i> Permitir localização e assinar
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    let atualId = null;
    const modal = document.getElementById('modalAssinaturaDash');
    if (!modal) return;
    const msg = document.getElementById('dashAssinaturaMsg');
    const docInfo = document.getElementById('dashAssinaturaDocInfo');
    const confirmar = document.getElementById('dashConfirmarAssinatura');
    const cancelar = document.getElementById('dashCancelarAssinatura');

    function mensagem(texto, isErro) {
        msg.style.display = 'block';
        msg.className = 'alert ' + (isErro ? 'alert-danger' : 'alert-info');
        msg.textContent = texto;
    }

    document.querySelectorAll('.js-assinar-dash').forEach(btn => {
        btn.addEventListener('click', () => {
            const temAssinatura = btn.dataset.temAssinatura === '1';
            if (!temAssinatura) {
                if (confirm('Sua assinatura manuscrita ou registro profissional ainda não estão configurados no perfil. Deseja acessar a página de perfil para cadastrá-los agora?')) {
                    window.location.href = '<?= APP_URL ?>perfil#assinatura';
                }
                return;
            }
            atualId = btn.dataset.id;
            msg.style.display = 'none';
            docInfo.textContent = 'Relatório ' + (btn.dataset.numero || '') + ' — ' + (btn.dataset.embarcacao || '');
            modal.style.display = 'flex';
        });
    });

    if (cancelar) {
        cancelar.onclick = () => {
            modal.style.display = 'none';
            atualId = null;
        };
    }

    if (confirmar) {
        confirmar.onclick = () => {
            if (!atualId) return;
            if (!navigator.geolocation) {
                mensagem('Este navegador não oferece suporte à geolocalização.', true);
                return;
            }
            confirmar.disabled = true;
            mensagem('Obtendo localização geográfica para fé pública...', false);
            navigator.geolocation.getCurrentPosition(pos => {
                const fd = new FormData();
                fd.append('csrf_token', <?= json_encode(gerarCSRF()) ?>);
                fd.append('action', 'assinar');
                fd.append('documento_tipo', 'RELATORIO');
                fd.append('documento_id', atualId);
                fd.append('latitude', pos.coords.latitude);
                fd.append('longitude', pos.coords.longitude);
                fd.append('geo_precisao_m', pos.coords.accuracy || '');
                fetch(<?= json_encode(APP_URL . 'minhas-assinaturas/actions') ?>, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                }).then(async r => {
                    const d = await r.json().catch(() => ({success: false, message: 'Resposta inválida do servidor.'}));
                    if (!r.ok || !d.success) throw new Error(d.message || 'Falha ao processar assinatura.');
                    mensagem(d.message, false);
                    setTimeout(() => location.reload(), 900);
                }).catch(e => {
                    mensagem(e.message, true);
                    confirmar.disabled = false;
                });
            }, err => {
                mensagem(err.code === 1 ? 'A localização é obrigatória pela NORMAM para assinar com fé pública.' : 'Não foi possível obter a localização geográfica.', true);
                confirmar.disabled = false;
            }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 0});
        };
    }
})();
</script>
