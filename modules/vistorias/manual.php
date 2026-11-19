<?php
/**
 * Manual Interativo do Usuário - Módulo de Vistorias Técnicas & Campo
 * Arquivo: modules/vistorias/manual.php
 * Passo a passo didático, ilustrado e profissional para operação do vistoriador em campo.
 * Padrão: NORMAM-202/DPC, RIPEAM | Referência visual: modules/protocolos/manual.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargoUsuario = getCargo();
if (!in_array($cargoUsuario, ['VISTORIADOR', 'ADMIN', 'SECRETARIA'], true)) {
    setMensagem('error', 'Acesso negado. O Manual do Vistoriador é de acesso exclusivo para Vistoriadores e Administradores.');
    redirecionar(APP_URL . 'dashboard');
    exit;
}

$titulo_page = 'Manual do Usuário - Vistorias Técnicas de Campo';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual Didático do Vistoriador */
.manual-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 60px;
}

/* Hero Banner */
.manual-hero {
    background: linear-gradient(135deg, #064e3b 0%, #032b23 50%, #041c17 100%);
    border: 1px solid #10b981;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.manual-hero h1 {
    color: #ffffff !important;
}

.manual-hero p {
    color: #e2e8f0 !important;
}

/* Índice e Chips Rápidos */
.manual-index-card {
    background: #ffffff !important;
    border: 1px solid #dce5e2 !important;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.manual-nav-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px;
    color: #0f172a !important;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.manual-nav-pill:hover {
    background: #ecfdf5 !important;
    border-color: #087653 !important;
    color: #087653 !important;
    transform: translateY(-2px);
}

/* Card de Passo (Step Card) */
.step-card {
    background: #ffffff !important;
    border: 1px solid #dce5e2 !important;
    border-radius: 14px;
    padding: 26px;
    margin-bottom: 30px;
    scroll-margin-top: 90px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    color: #1e293b !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.step-card:hover {
    border-color: #087653 !important;
    box-shadow: 0 6px 20px rgba(8, 118, 83, 0.08);
}

.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #087653;
    color: #ffffff !important;
    font-weight: 800;
    font-size: 1.15rem;
    margin-right: 12px;
    box-shadow: 0 2px 8px rgba(8, 118, 83, 0.35);
    flex-shrink: 0;
}

.step-title {
    display: flex;
    align-items: center;
    font-size: 1.35rem;
    color: #087653 !important;
    margin-bottom: 16px;
    font-weight: 700;
}

/* Tipografia dentro do card */
.step-card p,
.step-card ol,
.step-card ul,
.step-card li {
    color: #1e293b !important;
}

.step-card strong,
.step-card b {
    color: #0f172a !important;
}

.text-high-contrast {
    color: #1e293b !important;
}

.text-muted-contrast {
    color: #475569 !important;
}

/* Cards de Destaque / Recursos */
.feature-box {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px;
    padding: 16px;
    height: 100%;
}

.feature-box strong {
    color: #087653 !important;
    display: block;
    margin-bottom: 6px;
    font-size: 0.95rem;
}

.feature-box small,
.feature-box p {
    color: #475569 !important;
    line-height: 1.6;
    margin-bottom: 0;
}

/* Janela de Mockup */
.mockup-window {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 12px;
    overflow: hidden;
    margin: 22px 0;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
}

.mockup-header {
    background: #0f172a !important;
    padding: 11px 18px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.86rem;
    color: #f8fafc !important;
    font-weight: 600;
}

.mockup-dots {
    display: flex;
    gap: 6px;
}

.mockup-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}
.mockup-dot-red { background: #ef4444; }
.mockup-dot-yellow { background: #f59e0b; }
.mockup-dot-green { background: #10b981; }

.mockup-body {
    padding: 22px;
    background: #f8fafc !important;
    color: #1e293b !important;
}

.mockup-panel {
    background: #ffffff !important;
    border: 1px solid #dce5e2 !important;
    border-radius: 8px;
    padding: 16px;
    color: #0f172a !important;
}

/* Callouts / Caixas de Aviso */
.callout-box {
    background: #ecfdf5 !important;
    border-left: 5px solid #087653 !important;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 18px 0;
    font-size: 0.95rem;
    color: #064e3b !important;
    line-height: 1.6;
}

.callout-box strong {
    color: #022c22 !important;
}

.callout-tip {
    background: #fffbeb !important;
    border-left: 5px solid #d97706 !important;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 18px 0;
    font-size: 0.95rem;
    color: #78350f !important;
    line-height: 1.6;
}

.callout-tip strong {
    color: #451a03 !important;
}

.callout-danger {
    background: #fef2f2 !important;
    border-left: 5px solid #ef4444 !important;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 18px 0;
    font-size: 0.95rem;
    color: #991b1b !important;
    line-height: 1.6;
}

.callout-danger strong {
    color: #7f1d1d !important;
}

.callout-info {
    background: #f0f9ff !important;
    border-left: 5px solid #0284c7 !important;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 18px 0;
    font-size: 0.95rem;
    color: #0c4a6e !important;
    line-height: 1.6;
}

.callout-info strong {
    color: #082f49 !important;
}

/* Badges e Tags Específicas */
.badge-as-critico {
    background: #ef4444;
    color: #ffffff !important;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    letter-spacing: 0.04em;
}

.badge-comum {
    background: #f59e0b;
    color: #042019 !important;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.normam-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #ecfdf5;
    border: 1px solid #10b981;
    color: #065f46;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}

/* Botões Demonstrativos */
.btn-demo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 700;
    border: none;
    cursor: default;
    color: #ffffff !important;
}

.btn-demo-primary { background: #087653; color: #fff !important; }
.btn-demo-success { background: #16a34a; color: #fff !important; }
.btn-demo-warning { background: #d97706; color: #fff !important; }
.btn-demo-info    { background: #0284c7; color: #fff !important; }
.btn-demo-danger  { background: #dc2626; color: #fff !important; }
.btn-demo-secondary { background: #475569; color: #fff !important; }

/* Acordeão de FAQ */
.faq-accordion .accordion-item {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    margin-bottom: 10px;
    overflow: hidden;
}

.faq-accordion .accordion-button {
    background: #f8fafc !important;
    color: #0f172a !important;
    font-weight: 700;
    font-size: 0.96rem;
    box-shadow: none !important;
}

.faq-accordion .accordion-button:not(.collapsed) {
    background: #ecfdf5 !important;
    color: #087653 !important;
    border-bottom: 1px solid #cbd5e1;
}

.faq-accordion .accordion-body {
    background: #ffffff !important;
    color: #334155 !important;
    font-size: 0.94rem;
    line-height: 1.7;
    padding: 18px 20px;
}

/* Suporte à Impressão */
@media print {
    .sidebar, .top-header, .manual-nav-pill, .btn-print, .manual-index-card {
        display: none !important;
    }
    .conteudo-principal {
        margin: 0 !important;
        padding: 0 !important;
    }
    .manual-hero {
        background: #087653 !important;
        color: #fff !important;
        box-shadow: none !important;
    }
    .step-card {
        page-break-inside: avoid;
        border: 1px solid #94a3b8 !important;
        background: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
    }
    .mockup-window {
        background: #f8fafc !important;
        border: 1px solid #94a3b8 !important;
        color: #000 !important;
    }
    .mockup-header {
        background: #334155 !important;
        color: #fff !important;
    }
}
</style>

<main class="conteudo-principal">
    <div class="manual-container">

        <!-- CABEÇALHO DO MANUAL (HERO) -->
        <div class="manual-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge" style="background: #56e0ad; color: #042019; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; margin-bottom: 12px; display: inline-block;">
                        <i class="fa-solid fa-anchor"></i> DIRETRIZES DA AUTORIDADE MARÍTIMA &bull; DPC
                    </span>
                    <h1 class="fw-bold mb-2" style="font-size: 2rem;">
                        Manual do Usuário: Vistoriador Naval & Campo
                    </h1>
                    <p class="lead mb-0" style="max-width: 840px; font-size: 1.05rem; line-height: 1.6;">
                        Aprenda passo a passo a rotina do vistoriador em campo: desde o recebimento do agendamento, inspeção offline via celular/tablet, preenchimento do checklist NORMAM, registro de evidências e não conformidades (A/S vs Comuns), até a assinatura eletrônica e homologação do laudo técnico.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-light d-inline-flex align-items-center gap-2 btn-print fw-bold" style="color: #0f172a;">
                        <i class="fa-solid fa-print"></i> Imprimir Guia
                    </button>
                    <a href="<?= APP_URL ?>vistorias" class="btn btn-outline-light d-inline-flex align-items-center gap-2 fw-bold">
                        <i class="fa-solid fa-arrow-left"></i> Voltar às Vistorias
                    </a>
                </div>
            </div>
        </div>

        <!-- CHIPS DE NAVEGAÇÃO RÁPIDA -->
        <div class="manual-index-card card mb-4">
            <div class="card-body p-3">
                <div class="small fw-bold mb-2" style="color: #087653;">
                    <i class="fa-solid fa-list-ol"></i> ÍNDICE RÁPIDO DO MANUAL (CLIQUE PARA IR DIRETO AO PASSO):
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#passo1" class="manual-nav-pill"><i class="fa-solid fa-calendar-check text-success"></i> 1. Agendamento & Escala</a>
                    <a href="#passo2" class="manual-nav-pill"><i class="fa-solid fa-mobile-screen-button text-info"></i> 2. Modo Campo (Offline)</a>
                    <a href="#passo3" class="manual-nav-pill"><i class="fa-solid fa-list-check text-warning"></i> 3. Checklist NORMAM & Categorias</a>
                    <a href="#passo4" class="manual-nav-pill"><i class="fa-solid fa-camera text-success"></i> 4. Fotos & Evidências</a>
                    <a href="#passo5" class="manual-nav-pill"><i class="fa-solid fa-triangle-exclamation text-danger"></i> 5. Regra Crítica: A/S vs Comum</a>
                    <a href="#passo6" class="manual-nav-pill"><i class="fa-solid fa-plus-circle text-primary"></i> 6. Exigências Avulsas</a>
                    <a href="#passo7" class="manual-nav-pill"><i class="fa-solid fa-arrows-rotate text-info"></i> 7. Vistoria de Retorno</a>
                    <a href="#passo8" class="manual-nav-pill"><i class="fa-solid fa-file-signature text-warning"></i> 8. Conclusão & Assinatura</a>
                    <a href="#faq" class="manual-nav-pill"><i class="fa-solid fa-circle-question text-secondary"></i> 9. Perguntas Frequentes (FAQ)</a>
                </div>
            </div>
        </div>

        <!-- NOTA REGULAMENTAR -->
        <div class="callout-box">
            <strong><i class="fa-solid fa-compass"></i> Atenção aos Fundamentos Técnicos Navais:</strong>
            O módulo opera sob as normas da Autoridade Marítima (<strong>NORMAM-201</strong> para Mar Aberto, <strong>NORMAM-202</strong> para Navegação Interior, <strong>NORMAM-211/212</strong> para Borda Livre e Arqueação, <strong>NPCP/NPCF</strong> das Capitanias Fluviais e <strong>RIPEAM</strong> para governo e navegação). 
            <em>Regras de auditoria interna ISO/SGQ são deliberadamente desconsideradas</em> para garantir foco total na segurança da navegação e praticidade para o vistoriador a bordo.
        </div>

        <!-- PASSO 1: AGENDAMENTO & ESCALA -->
        <section id="passo1" class="step-card">
            <div class="step-title">
                <span class="step-badge">1</span> Passo 1: Recebimento do Agendamento & Escala Diária
            </div>
            <p style="line-height: 1.7;">
                Toda vistoria tem início com um agendamento prévio cadastrado no sistema comercial ou administrativo. O vistoriador visualiza diretamente na tela inicial do módulo de Vistorias as inspeções atribuídas a ele.
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No menu lateral esquerdo do sistema, clique em <strong>Vistorias Técnicas</strong> (rota <code>/vistorias</code>).</li>
                <li>No topo da tela, localize o card destacado em verde: <strong>"Vistorias Agendadas & Em Andamento"</strong>.</li>
                <li>Confira a data, hora, nome da embarcação, registro e o local do porto/estaleiro.</li>
                <li>Clique no botão verde <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-play"></i> Iniciar Vistoria</span> (se for nova) ou <span class="btn-demo btn-demo-info"><i class="fa-solid fa-pen-to-square"></i> Continuar Vistoria</span> (se já estava em andamento).</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot mockup-dot-red"></span>
                        <span class="mockup-dot mockup-dot-yellow"></span>
                        <span class="mockup-dot mockup-dot-green"></span>
                    </div>
                    <span>Tela: ERP Web &rarr; Vistorias &rarr; Painel de Escala Imediata</span>
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="mockup-body">
                    <div class="mockup-panel" style="border: 2px solid #087653;">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <strong style="color: #087653; font-size: 1.05rem;">
                                <i class="fa-solid fa-calendar-days"></i> Vistorias Agendadas & Em Andamento
                            </strong>
                            <span class="badge" style="background: #ecfdf5; color: #065f46; border: 1px solid #10b981; font-weight: 700;">1 pendente na sua escala</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span style="font-size: 0.82rem; font-weight: 700; color: #087653; background: #ecfdf5; padding: 2px 8px; border-radius: 4px; border: 1px solid #10b981;">
                                    <i class="fa-regular fa-calendar"></i> 20/11/2026 às 09:00
                                </span>
                                <span class="badge bg-warning text-dark fw-bold">AGENDADA</span>
                            </div>
                            <h4 style="margin: 6px 0; color: #0f172a; font-size: 1.15rem; font-weight: 700;">
                                <i class="fa-solid fa-ship" style="color: #087653;"></i> F/B RIO AMAZONAS IV
                            </h4>
                            <div class="text-muted-contrast small" style="margin: 4px 0 10px 0;">
                                Inscrição: <strong>021-004523-9</strong> &bull; Armador: <strong>Navegações Tapajós Ltda</strong> &bull; Tipo: <strong>Vistoria Periódica / Flutuando</strong>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-play"></i> Iniciar Vistoria</span>
                                <span class="text-muted-contrast small">
                                    <i class="fa-solid fa-location-dot text-danger"></i> Porto de Santana - Macapá/AP
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 2: MODO CAMPO (OFFLINE) -->
        <section id="passo2" class="step-card">
            <div class="step-title">
                <span class="step-badge">2</span> Passo 2: Modo Campo no Celular/Tablet (PWA 100% Offline)
            </div>
            <p style="line-height: 1.7;">
                Nos rios da Amazônia e portos afastados, a conexão com a internet muitas vezes é inexistente. O sistema conta com uma aplicação web progressiva (PWA) projetada especificamente para o vistoriador trabalhar em modo avião:
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong style="color: #d97706;"><i class="fa-solid fa-cloud-arrow-down"></i> 1. No Cais com Sinal</strong>
                        <small>Abra o endereço <code>/campo/</code> no celular ainda com internet. Toque em <strong>Iniciar Vistoria</strong> para baixar o pacote de dados e formulários para o aparelho.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong style="color: #0284c7;"><i class="fa-solid fa-plane-departure"></i> 2. A Bordo Sem Sinal</strong>
                        <small>Ative o modo avião. Todas as notas, fotos e respostas do checklist são gravadas no <strong>IndexedDB local</strong>. Mesmo que a bateria acabe, nada é perdido.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong style="color: #16a34a;"><i class="fa-solid fa-rotate"></i> 3. Sincronização Segura</strong>
                        <small>Ao voltar à área de cobertura, a fila do app envia tudo automaticamente para o servidor central, sem duplicar dados nem apagar nada do celular antes da confirmação.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 3: CHECKLIST NORMAM -->
        <section id="passo3" class="step-card">
            <div class="step-title">
                <span class="step-badge">3</span> Passo 3: Checklist NORMAM Inteligente & Categorias Navais
            </div>
            <p style="line-height: 1.7;">
                O sistema identifica as características da embarcação (Arqueação Bruta, Propulsão e Passageiros) e enquadra nos requisitos específicos da <strong>NORMAM-202/201</strong> (Categorias <code>a</code>, <code>b</code>, <code>c</code>, <code>d</code>, <code>e</code> ou <code>f</code>), dividindo a inspeção em 4 blocos técnicos:
            </p>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="p-3 rounded text-center h-100" style="background: #f0f9ff; border: 1px solid #0284c7;">
                        <i class="fa-solid fa-anchor fa-2x text-primary mb-2"></i>
                        <h6 class="fw-bold mb-1" style="color: #0c4a6e;">Seco</h6>
                        <small style="color: #0369a1;">Casco, leme, hélice, anodos e tomadas de mar.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded text-center h-100" style="background: #ecfdf5; border: 1px solid #10b981;">
                        <i class="fa-solid fa-life-ring fa-2x text-success mb-2"></i>
                        <h6 class="fw-bold mb-1" style="color: #064e3b;">Flutuando</h6>
                        <small style="color: #047857;">Salvatagem, incêndio, esgoto e luzes RIPEAM.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded text-center h-100" style="background: #fffbeb; border: 1px solid #f59e0b;">
                        <i class="fa-solid fa-water fa-2x text-warning mb-2"></i>
                        <h6 class="fw-bold mb-1" style="color: #78350f;">Borda Livre</h6>
                        <small style="color: #b45309;">Marca de Plimsoll, calados vante/ré e portas estanques.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded text-center h-100" style="background: #f0fdf4; border: 1px solid #16a34a;">
                        <i class="fa-solid fa-ruler-combined fa-2x text-success mb-2"></i>
                        <h6 class="fw-bold mb-1" style="color: #14532d;">Arqueação</h6>
                        <small style="color: #15803d;">Medição de Boca, Pontal e Comprimento real.</small>
                    </div>
                </div>
            </div>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Classifique cada item tocando em <span class="btn-demo btn-demo-success">CONFORME</span>, <span class="btn-demo btn-demo-danger">NÃO CONFORME</span> ou <span class="btn-demo btn-demo-secondary">N/A</span>.</li>
                <li>Ao marcar <strong>NÃO CONFORME</strong>, o sistema abre imediatamente os campos de <em>Referência da NORMAM</em>, <em>Observação do defeito</em> e a caixa crítica <em>A/S (Antes de Suspender)</em>.</li>
            </ol>
        </section>

        <!-- PASSO 4: REGISTRO DE FOTOS -->
        <section id="passo4" class="step-card">
            <div class="step-title">
                <span class="step-badge">4</span> Passo 4: Foto Oficial da Embarcação & Evidências Fotográficas
            </div>
            <p style="line-height: 1.7;">
                Uma vistoria naval sem evidência fotográfica nítida não é aceita pela Capitania dos Portos:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="feature-box">
                        <strong style="color: #d97706;"><i class="fa-solid fa-camera"></i> 1. Foto Oficial da Embarcação (Mestre)</strong>
                        <p>
                            Tire enquadrando o <strong>costado completo da embarcação</strong> (proa a popa), com o <strong>nome e porto de inscrição perfeitamente legíveis</strong>. Esta foto vai estampada na capa do laudo oficial pericial.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-box">
                        <strong style="color: #dc2626;"><i class="fa-solid fa-images"></i> 2. Múltiplas Fotos por Item do Checklist</strong>
                        <p>
                            Para cada item não conforme, adicione fotos de contexto e fotos em close-up do defeito (ex.: manômetro do extintor descarregado, trinca na caverna). Se sair tremida, clique no <strong>"X" vermelho</strong> na miniatura para excluir na hora.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 5: A/S VS COMUM -->
        <section id="passo5" class="step-card">
            <div class="step-title">
                <span class="step-badge">5</span> Passo 5: Classificação de Exigências: A/S (Antes de Suspender) vs Comum
            </div>
            <p style="line-height: 1.7;">
                Este é o ponto de maior responsabilidade técnica do vistoriador:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 10px; padding: 20px;">
                        <span class="badge-as-critico mb-2"><i class="fa-solid fa-ban"></i> EXIGÊNCIA A/S</span>
                        <h5 class="fw-bold mb-2" style="color: #991b1b;">"Antes de Suspender" (Embarcação Retida)</h5>
                        <p class="small" style="color: #7f1d1d; line-height: 1.6;">
                            Risco iminente à vida humana, à navegabilidade ou ao meio ambiente. A embarcação <strong>NÃO PODE NAVEGAR</strong> até o retorno.
                        </p>
                        <ul class="small ps-3 mb-0" style="color: #991b1b;">
                            <li>Falta de coletes salva-vidas ou coletes rasgados/vencidos.</li>
                            <li>Extintores despressurizados na praça de máquinas.</li>
                            <li>Leme travado ou trinca passante no casco.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background: #fffbeb; border: 2px solid #f59e0b; border-radius: 10px; padding: 20px;">
                        <span class="badge-comum mb-2"><i class="fa-solid fa-clock"></i> EXIGÊNCIA COMUM</span>
                        <h5 class="fw-bold mb-2" style="color: #78350f;">Com Prazo Regulamentar (60 ou 90 dias)</h5>
                        <p class="small" style="color: #78350f; line-height: 1.6;">
                            Deficiência de menor gravidade funcional ou documental que <strong>não impede a viagem imediata</strong> com segurança.
                        </p>
                        <ul class="small ps-3 mb-0" style="color: #78350f;">
                            <li>Pintura das marcas de calado ou escala desgastada.</li>
                            <li>Placas de instruções de salvatagem desbotadas.</li>
                            <li>Suporte de fixação de extintor solto.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 6: EXIGÊNCIAS AVULSAS -->
        <section id="passo6" class="step-card">
            <div class="step-title">
                <span class="step-badge">6</span> Passo 6: Exigências Avulsas (Fora do Catálogo)
            </div>
            <p style="line-height: 1.7;">
                Caso encontre uma particularidade que não consta no catálogo padrão, utilize a seção <strong>Exigências Avulsas</strong>:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Clique no botão <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-plus"></i> Adicionar Item Avulso</span>.</li>
                <li>Selecione o bloco da vistoria correspondente (Seco, Flutuando, Borda Livre, Arqueação).</li>
                <li>Descreva com precisão técnica: cite o local exato a bordo e a ação corretiva necessária.</li>
                <li>Informe a referência da NORMAM e marque se a exigência é <strong>A/S</strong>.</li>
            </ol>
        </section>

        <!-- PASSO 7: VISTORIA DE RETORNO -->
        <section id="passo7" class="step-card">
            <div class="step-title">
                <span class="step-badge">7</span> Passo 7: Vistoria de Retorno & Cumprimento de Exigências
            </div>
            <p style="line-height: 1.7;">
                Quando o armador sana as pendências, o sistema abre o <strong>Relatório de Verificação de Cumprimento</strong> herdando todas as exigências do laudo pai:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Para cada exigência herdada, selecione o resultado da verificação a bordo:
                    <ul>
                        <li><strong class="text-success">Cumprida:</strong> Armador realizou o reparo e apresentou conforme. Anexe a foto da evidência sanada!</li>
                        <li><strong class="text-warning">Parcialmente Cumprida / Reescrita:</strong> Reparo parcial. Reescreva apenas o saldo restante.</li>
                        <li><strong class="text-danger">Não Cumprida / Transcrita:</strong> O defeito continua ativo. Se for A/S, o navio continua retido!</li>
                    </ul>
                </li>
                <li>Se todas as exigências A/S forem cumpridas, a embarcação é liberada imediatamente e o laudo avança para homologação.</li>
            </ol>
        </section>

        <!-- PASSO 8: CONCLUSÃO & ASSINATURA -->
        <section id="passo8" class="step-card">
            <div class="step-title">
                <span class="step-badge">8</span> Passo 8: Conclusão Técnica, Assinatura Digital & Laudo Oficial
            </div>
            <p style="line-height: 1.7;">
                Para finalizar a inspeção técnica:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;">
                <li><strong>Data da Vistoria:</strong> Confirme a data real da visita a bordo.</li>
                <li><strong>Prazo de Validade:</strong> Selecione <strong>60 dias</strong> ou <strong>90 dias</strong> conforme a Capitania.</li>
                <li><strong>Operador Presente:</strong> Selecione o Armador e digite o nome completo do Mestre da embarcação que acompanhou você.</li>
                <li><strong>Assinatura Eletrônica:</strong> Aplique sua assinatura digital credenciada (grava Hash SHA-256, carimbo de tempo, IP e coordenadas).</li>
                <li><strong>Laudo Oficial em PDF:</strong> Clique em <strong>Imprimir / PDF Oficial</strong> para emitir o laudo com cabeçalho naval da Capitania e QR Code de autenticidade!</li>
            </ol>
        </section>

        <!-- SEÇÃO: DÚVIDAS FREQUENTES (FAQ) -->
        <section class="step-card" id="faq">
            <div class="step-title">
                <i class="fa-solid fa-circle-question fs-4 me-2"></i> Perguntas Frequentes do Vistoriador Naval (FAQ)
            </div>

            <div class="accordion faq-accordion" id="accordionFaqVistoriador">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqVisOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisOne">
                            ❓ O que fazer se a embarcação estiver sem internet durante a vistoria?
                        </button>
                    </h2>
                    <div id="collapseVisOne" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body">
                            Não se preocupe! Use o modo <strong>Campo PWA</strong> (<code>/campo/</code>). Abra a página e toque em <em>Iniciar Vistoria</em> no escritório ou quando tiver sinal antes de ir a bordo. O sistema funciona 100% offline e salvará todas as fotos e notas no aparelho (IndexedDB). Ao voltar para o sinal, ele sincronizará automaticamente sem duplicar nada.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqVisTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisTwo">
                            ❓ Como saber com certeza se devo marcar uma exigência como A/S?
                        </button>
                    </h2>
                    <div id="collapseVisTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body">
                            Faça a pergunta-chave de segurança da navegação: <em>"Se este navio sair navegando agora neste estado, há risco iminente de naufrágio, incêndio incontrolável, alagamento ou perda de vidas humanas?"</em>. Se a resposta for <strong>SIM</strong> (ex.: leme quebrado, rombo no casco, sem coletes ou extintores), marque obrigatoriamente <strong>A/S (Antes de Suspender)</strong>.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqVisThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisThree">
                            ❓ O armador não estava a bordo na hora da vistoria. Quem deve constar como presente?
                        </button>
                    </h2>
                    <div id="collapseVisThree" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body">
                            No campo <em>"Armador na data da Vistoria"</em>, mantenha a empresa armadora titular e preencha o campo <strong>"Nome do operador/responsável presente na vistoria"</strong> com o nome completo do Mestre da embarcação ou preposto do estaleiro que acompanhou a vistoria técnica.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Botões de Ação no Fim do Manual -->
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>vistorias" class="btn btn-primary btn-lg px-4 me-2" style="background: #087653; border-color: #087653;">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Lista de Vistorias
            </a>
            <a href="<?= APP_URL ?>agendamentos" class="btn btn-secondary btn-lg px-4">
                <i class="fa-solid fa-calendar-check"></i> Ver Escala de Agendamentos
            </a>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>