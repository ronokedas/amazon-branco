<?php
/**
 * Manual Interativo do Usuário - Módulo do Vistoriador Naval
 * Arquivo: modules/vistorias/manual.php
 * Guia Prático Passo a Passo (Campo, Checklist NORMAM & Emissão de Laudos Técnicos)
 * Padrão: NORMAM-201/202/211, RIPEAM, NPCP/NPCF | Sem requisitos ISO/SGQ
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissão de acesso a vistorias
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Você não tem permissão para acessar este módulo.');
    redirecionar(APP_URL . 'dashboard');
}

$titulo_page = 'Guia Prático Passo a Passo — Módulo do Vistoriador Naval';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual do Vistoriador */
.manual-container {
    max-width: 1240px;
    margin: 0 auto;
    padding-bottom: 70px;
}

.manual-hero {
    background: linear-gradient(135deg, rgba(13, 148, 136, 0.25) 0%, rgba(6, 44, 38, 0.85) 100%);
    border: 1px solid #14b8a6;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.manual-hero::before {
    content: "";
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(20, 184, 166, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.manual-pills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.manual-nav-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(20, 184, 166, 0.3);
    border-radius: 10px;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.manual-nav-pill:hover {
    background: rgba(20, 184, 166, 0.2);
    border-color: #2dd4bf;
    color: #2dd4bf;
    transform: translateY(-2px);
}

.manual-nav-pill i {
    color: #2dd4bf;
}

.step-card {
    background: var(--bg-surface, #0f2420);
    border: 1px solid rgba(20, 184, 166, 0.22);
    border-radius: 14px;
    padding: 26px;
    margin-bottom: 32px;
    scroll-margin-top: 90px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.22);
    transition: border-color 0.2s ease;
}

.step-card:hover {
    border-color: rgba(20, 184, 166, 0.45);
}

.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #0d9488;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.15rem;
    margin-right: 12px;
    flex-shrink: 0;
    box-shadow: 0 2px 10px rgba(13, 148, 136, 0.45);
}

.step-title {
    display: flex;
    align-items: center;
    font-size: 1.35rem;
    color: #2dd4bf;
    margin-bottom: 16px;
    font-weight: 700;
}

.mockup-window {
    background: #061915;
    border: 1px solid rgba(20, 184, 166, 0.28);
    border-radius: 12px;
    overflow: hidden;
    margin: 20px 0;
    box-shadow: 0 8px 26px rgba(0,0,0,0.4);
}

.mockup-header {
    background: #0a2620;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(20, 184, 166, 0.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #94a3b8;
}

.mockup-dots {
    display: flex;
    gap: 6px;
}

.mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #475569;
}

.mockup-body {
    padding: 22px;
}

.callout-box {
    border-radius: 10px;
    padding: 16px 20px;
    margin: 18px 0;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    font-size: 0.92rem;
    line-height: 1.55;
}

.callout-info {
    background: rgba(14, 165, 233, 0.12);
    border-left: 4px solid #0284c7;
    color: #bae6fd;
}

.callout-warning {
    background: rgba(245, 158, 11, 0.12);
    border-left: 4px solid #f59e0b;
    color: #fde68a;
}

.callout-danger {
    background: rgba(239, 68, 68, 0.14);
    border-left: 4px solid #ef4444;
    color: #fecaca;
}

.callout-success {
    background: rgba(16, 185, 129, 0.12);
    border-left: 4px solid #10b981;
    color: #a7f3d0;
}

.normam-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(20, 184, 166, 0.15);
    border: 1px solid rgba(45, 212, 191, 0.35);
    color: #2dd4bf;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.rule-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(20, 184, 166, 0.35);
    border-radius: 10px;
    padding: 16px;
    margin: 14px 0;
}

.badge-as-critico {
    background: #ef4444;
    color: #ffffff;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    letter-spacing: 0.04em;
}

.badge-comum {
    background: #f59e0b;
    color: #1e1b4b;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.checklist-example-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #09211c;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 10px;
}

.checklist-btn-group {
    display: flex;
    gap: 6px;
}

.demo-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: default;
}

.demo-btn-ok {
    background: #059669;
    color: #ffffff;
}

.demo-btn-nok {
    background: #dc2626;
    color: #ffffff;
}

.demo-btn-na {
    background: #475569;
    color: #cbd5e1;
}

@media print {
    .sidebar, .navbar, .manual-nav-pill, .btn-print, .inspection-page-header a {
        display: none !important;
    }
    .manual-container {
        max-width: 100% !important;
        padding: 0 !important;
    }
    .step-card {
        page-break-inside: avoid;
        border: 1px solid #ccc !important;
        background: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
    }
    .mockup-window {
        background: #f8fafc !important;
        border: 1px solid #ccc !important;
        color: #000 !important;
    }
    .mockup-header {
        background: #e2e8f0 !important;
        color: #000 !important;
    }
    .step-title, h1, h2, h3, h4 {
        color: #0f172a !important;
    }
}
</style>

<div class="conteudo-principal">
    <div class="manual-container">

        <!-- CABEÇALHO DO MANUAL (HERO) -->
        <div class="manual-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="normam-tag mb-2">
                        <i class="fa-solid fa-anchor"></i> DIRETRIZES DA AUTORIDADE MARÍTIMA DO BRASIL (DPC)
                    </span>
                    <h1 class="text-white mt-1 mb-2" style="font-size: 2rem; font-weight: 800;">
                        Manual Prático do Vistoriador Naval
                    </h1>
                    <p class="text-white-50 mb-0" style="font-size: 1.05rem; max-width: 820px; line-height: 1.5;">
                        Guia passo a passo ilustrado para a rotina do vistoriador em campo: do recebimento do agendamento à inspeção offline, classificação técnica do checklist NORMAM (A/S vs Comuns), registro de fotos e assinatura do laudo técnico oficial.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-light btn-sm d-flex align-items-center gap-2 btn-print" style="border-radius: 8px; font-weight: 600;">
                        <i class="fa-solid fa-print"></i> Imprimir Guia
                    </button>
                    <a href="<?= APP_URL ?>vistorias" class="btn btn-success btn-sm d-flex align-items-center gap-2" style="background: #0d9488; border-color: #0d9488; border-radius: 8px; font-weight: 600;">
                        <i class="fa-solid fa-arrow-left"></i> Voltar às Vistorias
                    </a>
                </div>
            </div>

            <!-- CHIPS DE NAVEGAÇÃO RÁPIDA -->
            <div class="manual-pills-container">
                <a href="#passo1" class="manual-nav-pill"><i class="fa-solid fa-calendar-check"></i> 1. Agendamento & Escala</a>
                <a href="#passo2" class="manual-nav-pill"><i class="fa-solid fa-mobile-screen-button"></i> 2. Modo Campo (Offline)</a>
                <a href="#passo3" class="manual-nav-pill"><i class="fa-solid fa-list-check"></i> 3. Checklist NORMAM & Blocos</a>
                <a href="#passo4" class="manual-nav-pill"><i class="fa-solid fa-camera"></i> 4. Fotos & Evidências</a>
                <a href="#passo5" class="manual-nav-pill"><i class="fa-solid fa-triangle-exclamation"></i> 5. Regra Crítica: A/S vs Comum</a>
                <a href="#passo6" class="manual-nav-pill"><i class="fa-solid fa-plus-circle"></i> 6. Exigências Avulsas</a>
                <a href="#passo7" class="manual-nav-pill"><i class="fa-solid fa-arrows-rotate"></i> 7. Vistoria de Retorno</a>
                <a href="#passo8" class="manual-nav-pill"><i class="fa-solid fa-file-signature"></i> 8. Conclusão & Assinatura</a>
                <a href="#passo9" class="manual-nav-pill"><i class="fa-solid fa-circle-question"></i> 9. Perguntas Frequentes (FAQ)</a>
            </div>
        </div>

        <!-- NOTA REGULAMENTAR DA AUTORIDADE MARÍTIMA -->
        <div class="callout-box callout-info">
            <i class="fa-solid fa-compass fa-2x mt-1 text-info"></i>
            <div>
                <strong>Atenção aos Fundamentos Técnicos Navais:</strong>
                O módulo opera sob as normas da Autoridade Marítima (<strong>NORMAM-201</strong> para Mar Aberto, <strong>NORMAM-202</strong> para Navegação Interior, <strong>NORMAM-211/212</strong> para Borda Livre e Arqueação, <strong>NPCP/NPCF</strong> das Capitanias Fluviais e <strong>RIPEAM</strong> para governo e navegação). 
                <em>Regras de auditoria interna ISO/SGQ são deliberadamente desconsideradas</em> para garantir foco total na segurança da navegação e praticidade para o vistoriador a bordo.
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PASSO 1: AGENDAMENTO & ESCALA -->
        <!-- ========================================================================= -->
        <section id="passo1" class="step-card">
            <div class="step-title">
                <span class="step-badge">1</span>
                <span>Recebimento do Agendamento & Escala Diária</span>
            </div>
            <p class="text-white-50">
                Toda vistoria tem início com um agendamento prévio cadastrado no sistema comercial ou administrativo. O vistoriador visualiza diretamente na tela inicial do módulo de Vistorias as inspeções atribuídas a ele.
            </p>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot" style="background:#ef4444;"></span>
                        <span class="mockup-dot" style="background:#f59e0b;"></span>
                        <span class="mockup-dot" style="background:#10b981;"></span>
                    </div>
                    <span>Tela: ERP Web &rarr; Vistorias &rarr; Painel de Escala Imediata</span>
                </div>
                <div class="mockup-body">
                    <div style="background: #0b2b24; border: 2px solid #0d9488; border-radius: 10px; padding: 16px;">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <strong style="color: #2dd4bf; font-size: 1.05rem;">
                                <i class="fa-solid fa-calendar-days"></i> Vistorias Agendadas & Em Andamento
                            </strong>
                            <span class="badge" style="background: #ccfbf1; color: #0f766e; font-weight: 700;">1 pendente na sua escala</span>
                        </div>
                        <div style="background: #081d18; border: 1px solid rgba(20, 184, 166, 0.3); border-radius: 8px; padding: 14px;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span style="font-size: 0.8rem; font-weight: 700; color: #2dd4bf; background: rgba(45, 212, 191, 0.15); padding: 2px 8px; border-radius: 4px;">
                                    <i class="fa-regular fa-calendar"></i> 20/11/2026 às 09:00
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 800; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px;">
                                    AGENDADA
                                </span>
                            </div>
                            <h4 style="margin: 4px 0; color: #fff; font-size: 1.1rem;">
                                <i class="fa-solid fa-ship" style="color: #2dd4bf;"></i> F/B RIO AMAZONAS IV
                            </h4>
                            <div style="font-size: 0.85rem; color: #94a3b8; margin: 4px 0 10px 0;">
                                Inscrição: <strong>021-004523-9</strong> &middot; Armador: <strong>Navegações Tapajós Ltda</strong> &middot; Tipo: <strong>Vistoria Periódica / Flutuando</strong>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success" style="background: #0d9488; border: none; font-weight: 700;">
                                    <i class="fa-solid fa-play"></i> Iniciar Vistoria
                                </button>
                                <span class="text-white-50 small align-self-center">
                                    <i class="fa-solid fa-location-dot text-danger"></i> Porto de Santana - Macapá/AP
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rule-box">
                <strong class="text-white"><i class="fa-solid fa-lightbulb text-warning"></i> Como agir na prática:</strong>
                <ol class="text-white-50 mb-0 mt-2" style="line-height: 1.6;">
                    <li>Ao chegar no estaleiro, porto ou rampa, confirme os dados da embarcação (nome no costado e número de inscrição na placa).</li>
                    <li>Clique em <strong>Iniciar Vistoria</strong> para carregar o formulário técnico da inspeção.</li>
                    <li>Se a vistoria já tiver sido iniciada antes, o botão aparecerá em azul como <strong>Continuar Vistoria</strong>, preservando tudo o que você já preencheu.</li>
                </ol>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 2: MODO CAMPO & APLICATIVO OFFLINE (PWA) -->
        <!-- ========================================================================= -->
        <section id="passo2" class="step-card">
            <div class="step-title">
                <span class="step-badge">2</span>
                <span>Modo Campo no Celular/Tablet (PWA 100% Offline)</span>
            </div>
            <p class="text-white-50">
                Nos rios da Amazônia e terminais afastados, a conexão com a internet muitas vezes é inexistente. O sistema conta com uma aplicação web progressiva (PWA) projetada especificamente para o vistoriador trabalhar em modo avião.
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 10px; padding: 18px; height: 100%;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                            <i class="fa-solid fa-cloud-arrow-down fa-lg"></i>
                            <strong>1. No Cais com Sinal</strong>
                        </div>
                        <p class="text-white-50 small mb-0">
                            Abra o endereço <code>/campo/</code> no celular ainda com sinal. Localize a embarcação e clique em <strong>Iniciar Vistoria</strong>. O aplicativo baixa o checklist, histórico anterior e dados cadastrais no armazenamento seguro do aparelho.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 10px; padding: 18px; height: 100%;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-info">
                            <i class="fa-solid fa-plane-departure fa-lg"></i>
                            <strong>2. A Bordo Sem Sinal</strong>
                        </div>
                        <p class="text-white-50 small mb-0">
                            Pode ativar o modo avião sem receio. Todas as notas técnicas, fotos da câmera e respostas do checklist são gravadas no <strong>IndexedDB local</strong>. Mesmo que a bateria acabe ou o navegador feche, nada é perdido.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 10px; padding: 18px; height: 100%;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-success">
                            <i class="fa-solid fa-rotate fa-lg"></i>
                            <strong>3. Sincronização Segura</strong>
                        </div>
                        <p class="text-white-50 small mb-0">
                            Ao retornar à área de cobertura, a fila do aplicativo sincroniza tudo automaticamente com o servidor central, sem gerar duplicidade de dados e garantindo a data/hora original da coleta.
                        </p>
                    </div>
                </div>
            </div>

            <div class="callout-box callout-success mt-3">
                <i class="fa-solid fa-shield-halved fa-2x mt-1 text-success"></i>
                <div>
                    <strong>Garantia Anti-Perda de Evidências:</strong>
                    Nenhuma foto ou item do checklist é apagado do seu aparelho até que o servidor confirme o recebimento íntegro e responda com o identificador definitivo da gravação.
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 3: CHECKLIST NORMAM & BLOCOS TÉCNICOS -->
        <!-- ========================================================================= -->
        <section id="passo3" class="step-card">
            <div class="step-title">
                <span class="step-badge">3</span>
                <span>Checklist NORMAM Inteligente & Categorias Navais</span>
            </div>
            <p class="text-white-50">
                O sistema identifica automaticamente as características da embarcação (Arqueação Bruta, Propulsão e Capacidade de Passageiros) e enquadra nos requisitos específicos da <strong>NORMAM-202/201</strong> (Categorias <code>a</code>, <code>b</code>, <code>c</code>, <code>d</code>, <code>e</code> ou <code>f</code>).
            </p>

            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-md-3">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-anchor fa-2x text-warning mb-2"></i>
                        <h6 class="text-white mb-1">Vistoria em Seco</h6>
                        <small class="text-white-50">Casco, chapeamento, fundo, leme, hélice, anodos de zinco e tomadas de mar.</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-life-ring fa-2x text-info mb-2"></i>
                        <h6 class="text-white mb-1">Vistoria Flutuando</h6>
                        <small class="text-white-50">Salvatagem (coletes/boias), combate a incêndio, esgoto, estanqueidade e RIPEAM.</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-water fa-2x text-primary mb-2"></i>
                        <h6 class="text-white mb-1">Borda Livre</h6>
                        <small class="text-white-50">Marca de Plimsoll soldada, calados vante/ré, escotilhas e portas estanques.</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                        <i class="fa-solid fa-ruler-combined fa-2x text-success mb-2"></i>
                        <h6 class="text-white mb-1">Arqueação</h6>
                        <small class="text-white-50">Conferência das dimensões principais (Boca, Pontal, Comprimento) para o CNARQ.</small>
                    </div>
                </div>
            </div>

            <h5 class="text-white mt-4 mb-2"><i class="fa-solid fa-sliders text-accent"></i> Como classificar cada item na prática:</h5>

            <div class="checklist-example-row">
                <div>
                    <span class="normam-tag">ITEM 03.1</span>
                    <strong class="text-white ms-2">Dotação de Coletes Salva-Vidas (NORMAM-202, Cap. 04)</strong>
                    <div class="text-white-50 small mt-1">Coletes classe III/IV em quantidade suficiente para passageiros e tripulantes, com apito e fitas retrorrefletivas.</div>
                </div>
                <div class="checklist-btn-group">
                    <span class="demo-btn demo-btn-ok"><i class="fa-solid fa-check"></i> CONFORME</span>
                    <span class="demo-btn demo-btn-nok" style="opacity: 0.4;">NÃO CONFORME</span>
                    <span class="demo-btn demo-btn-na" style="opacity: 0.4;">N/A</span>
                </div>
            </div>

            <div class="checklist-example-row">
                <div>
                    <span class="normam-tag">ITEM 05.2</span>
                    <strong class="text-white ms-2">Luzes de Navegação e Governo (RIPEAM - Regras 21 a 24)</strong>
                    <div class="text-white-50 small mt-1">Luzes de bordo (boreste/bombordo), alcançado e mastro com setores de visibilidade desobstruídos.</div>
                </div>
                <div class="checklist-btn-group">
                    <span class="demo-btn demo-btn-ok" style="opacity: 0.4;">CONFORME</span>
                    <span class="demo-btn demo-btn-nok"><i class="fa-solid fa-triangle-exclamation"></i> NÃO CONFORME</span>
                    <span class="demo-btn demo-btn-na" style="opacity: 0.4;">N/A</span>
                </div>
            </div>

            <div class="checklist-example-row">
                <div>
                    <span class="normam-tag">ITEM 08.4</span>
                    <strong class="text-white ms-2">Balsa Inflável Salva-Vidas com Dispositivo Hidrostático</strong>
                    <div class="text-white-50 small mt-1">Exigível apenas para navegação de mar aberto (NORMAM-201) ou embarcações especiais.</div>
                </div>
                <div class="checklist-btn-group">
                    <span class="demo-btn demo-btn-ok" style="opacity: 0.4;">CONFORME</span>
                    <span class="demo-btn demo-btn-nok" style="opacity: 0.4;">NÃO CONFORME</span>
                    <span class="demo-btn demo-btn-na"><i class="fa-solid fa-ban"></i> N/A</span>
                </div>
            </div>

            <div class="callout-box callout-warning">
                <i class="fa-solid fa-triangle-exclamation fa-2x mt-1 text-warning"></i>
                <div>
                    <strong>A regra de ouro ao marcar "NÃO CONFORME":</strong>
                    O sistema abrirá imediatamente os campos de <strong>Referência da NORMAM</strong>, <strong>Observação Curta</strong> (que sairá impressa no laudo) e a caixa seletora <strong>A/S — Antes de Suspender</strong>. Preencha sempre de forma clara e objetiva para que o armador saiba exatamente o que precisa reparar.
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 4: FOTOS & EVIDÊNCIAS TÉCNICAS -->
        <!-- ========================================================================= -->
        <section id="passo4" class="step-card">
            <div class="step-title">
                <span class="step-badge">4</span>
                <span>Foto Oficial da Embarcação & Evidências Fotográficas</span>
            </div>
            <p class="text-white-50">
                Uma vistoria naval sem registro fotográfico nítido não tem validade técnica perante a Capitania dos Portos. O sistema divide as fotos em duas categorias fundamentais:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.35); border-radius: 10px; padding: 18px; height: 100%;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                            <i class="fa-solid fa-camera fa-lg"></i>
                            <h5 class="text-white mb-0">1. Foto Oficial da Embarcação</h5>
                        </div>
                        <p class="text-white-50 small" style="line-height: 1.6;">
                            Localizada logo no topo da tela da vistoria. Deve ser tirada enquadrando o <strong>costado completo da embarcação</strong> flutuando ou em seco, com o <strong>nome e porto de inscrição perfeitamente legíveis</strong>.
                        </p>
                        <ul class="text-white-50 small mb-0 ps-3">
                            <li>Atualiza a foto principal do cadastro da embarcação no ERP.</li>
                            <li>É impressa com destaque na primeira página do laudo pericial.</li>
                            <li>Evite fotos cortadas, muito distantes ou tiradas contra o sol forte.</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.35); border-radius: 10px; padding: 18px; height: 100%;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-danger">
                            <i class="fa-solid fa-images fa-lg"></i>
                            <h5 class="text-white mb-0">2. Fotos de Evidências por Item</h5>
                        </div>
                        <p class="text-white-50 small" style="line-height: 1.6;">
                            Abaixo de cada item do checklist marcado como <em>Não Conforme</em> (ou para comprovar conformidades críticas), existe a área de anexos com suporte a <strong>múltiplas fotos</strong>.
                        </p>
                        <ul class="text-white-50 small mb-0 ps-3">
                            <li>Tire fotos aproximadas do problema (ex.: manômetro do extintor despressurizado, avaria na pá do hélice, folga no leme).</li>
                            <li>O sistema comprime as imagens mantendo a nitidez para otimizar o envio no rio.</li>
                            <li>Se a foto sair tremida, clique no ícone <strong>"X" vermelho</strong> na miniatura para excluir na hora.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 5: A/S VS COMUM (CRITÉRIO CRÍTICO) -->
        <!-- ========================================================================= -->
        <section id="passo5" class="step-card">
            <div class="step-title">
                <span class="step-badge">5</span>
                <span>Classificação de Exigências: A/S (Antes de Suspender) vs Comum</span>
            </div>
            <p class="text-white-50">
                Este é o ponto de maior responsabilidade técnica do vistoriador naval. Um erro de classificação pode paralisar indevidamente uma embarcação ou, pior, liberar uma embarcação sem condições de segurança.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background: rgba(239, 68, 68, 0.08); border: 2px solid #ef4444; border-radius: 12px; padding: 20px; height: 100%;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-as-critico"><i class="fa-solid fa-ban"></i> EXIGÊNCIA A/S</span>
                            <span class="text-danger small font-monospace">IMPEDITIVA</span>
                        </div>
                        <h4 class="text-white mb-2">"Antes de Suspender" (Embarcação Retida)</h4>
                        <p class="text-white-50 small" style="line-height: 1.6;">
                            Significa que a embarcação apresenta <strong>risco grave e iminente à vida humana, à navegação ou ao meio ambiente</strong>. Ela está legalmente proibida de desatracar e navegar até que o reparo seja feito e verificado.
                        </p>
                        <div class="rule-box" style="border-color: rgba(239, 68, 68, 0.4);">
                            <strong class="text-danger small"><i class="fa-solid fa-list-ul"></i> Exemplos Típicos de A/S:</strong>
                            <ul class="text-white-50 small mb-0 ps-3 mt-1">
                                <li>Falta grave de coletes salva-vidas ou coletes vencidos/rasgados.</li>
                                <li>Extintores de incêndio descarregados na praça de máquinas.</li>
                                <li>Trinca com infiltração ou rombo no chapeamento do casco.</li>
                                <li>Travamento do sistema de governo (leme ou madre inoperante).</li>
                                <li>Falta total de luzes de navegação obrigatórias para viagem noturna.</li>
                            </ul>
                        </div>
                        <div class="callout-box callout-danger mt-3 mb-0" style="padding: 10px 14px;">
                            <small><strong>Efeito no ERP:</strong> Bloqueia a emissão de CSN/Certificados e gera automaticamente a exigência de uma nova vistoria de retorno (Status <code>RETORNO_AS</code>).</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div style="background: rgba(245, 158, 11, 0.08); border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; height: 100%;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-comum"><i class="fa-solid fa-clock"></i> EXIGÊNCIA COMUM</span>
                            <span class="text-warning small font-monospace">PRAZO: 60 OU 90 DIAS</span>
                        </div>
                        <h4 class="text-white mb-2">Exigência com Prazo Regulamentar</h4>
                        <p class="text-white-50 small" style="line-height: 1.6;">
                            Deficiência de menor gravidade funcional ou documental que <strong>não impede a viagem imediata</strong> com segurança, mas que deve ser sanada pelo armador no prazo concedido.
                        </p>
                        <div class="rule-box" style="border-color: rgba(245, 158, 11, 0.4);">
                            <strong class="text-warning small"><i class="fa-solid fa-list-ul"></i> Exemplos Típicos de Exigência Comum:</strong>
                            <ul class="text-white-50 small mb-0 ps-3 mt-1">
                                <li>Pintura das marcas de calado ou escala de borda livre desgastada.</li>
                                <li>Quadro de instruções de abandono ou salvatagem pouco visível.</li>
                                <li>Suporte de extintor necessitando de aperto ou fixação.</li>
                                <li>Substituição de lanternas de emergência auxiliares.</li>
                                <li>Pequenos retoques de estanqueidade em caixilhos não críticos.</li>
                            </ul>
                        </div>
                        <div class="callout-box callout-warning mt-3 mb-0" style="padding: 10px 14px;">
                            <small><strong>Efeito no ERP:</strong> Permite que o laudo saia como <code>APROVADA_COM_EXIGÊNCIAS</code>, autorizando o armador a navegar provisoriamente dentro do prazo regulamentar.</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 6: EXIGÊNCIAS AVULSAS -->
        <!-- ========================================================================= -->
        <section id="passo6" class="step-card">
            <div class="step-title">
                <span class="step-badge">6</span>
                <span>Exigências Avulsas (Deficiências Fora do Catálogo)</span>
            </div>
            <p class="text-white-50">
                Caso encontre a bordo uma não conformidade técnica específica daquela embarcação que não consta nos itens padronizados do catálogo, utilize a seção <strong>Exigências Avulsas</strong>.
            </p>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                    </div>
                    <span>Tabela Dinâmica de Exigências Avulsas</span>
                </div>
                <div class="mockup-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong class="text-white small"><i class="fa-solid fa-plus-circle text-accent"></i> Itens Avulsos Adicionados</strong>
                        <button type="button" class="btn btn-sm btn-primary" style="background:#0d9488; border:none; font-size:0.8rem;">
                            <i class="fa-solid fa-plus"></i> Adicionar Item Avulso
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm table-bordered mb-0" style="font-size:0.82rem; background:#061d18;">
                            <thead>
                                <tr style="border-bottom:2px solid #0d9488; color:#2dd4bf;">
                                    <th style="width:40px; text-align:center;">#</th>
                                    <th style="width:160px;">Bloco da Vistoria</th>
                                    <th>Descrição Técnica da Exigência</th>
                                    <th style="width:180px;">Item da NORMAM</th>
                                    <th style="width:140px; text-align:center;">A/S?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center font-monospace">1</td>
                                    <td>Vistoria Flutuando</td>
                                    <td>Vazamento na gaxeta do eixo propulsor de bombordo com gotejamento excessivo.</td>
                                    <td class="font-monospace text-info">NORMAM-202, Cap. 03</td>
                                    <td class="text-center"><span class="badge-as-critico">SIM (A/S)</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rule-box">
                <strong class="text-white"><i class="fa-solid fa-circle-check text-success"></i> Boas Práticas de Redação Técnica:</strong>
                <p class="text-white-50 small mb-0 mt-1">
                    Escreva sempre o <strong>local exato</strong> da avaria (bombordo, boreste, proa, popa, praça de máquinas), o <strong>problema verificado</strong> e a <strong>ação corretiva necessária</strong> (ex.: "Revisar vedação da gaxeta e substituir anéis de gaxeta do eixo propulsor").
                </p>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 7: VISTORIA DE RETORNO & CUMPRIMENTO -->
        <!-- ========================================================================= -->
        <section id="passo7" class="step-card">
            <div class="step-title">
                <span class="step-badge">7</span>
                <span>Vistoria de Retorno & Cumprimento de Exigências</span>
            </div>
            <p class="text-white-50">
                Quando uma vistoria anterior gerou exigências (especialmente A/S), o armador realiza as manutenções e solicita a verificação de cumprimento. O sistema abre automaticamente o <strong>Relatório de Cumprimento de Exigências</strong> herdando a cadeia histórica rastreável.
            </p>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                    </div>
                    <span>Cadeia de Rastreabilidade & Triagem de Cumprimento</span>
                </div>
                <div class="mockup-body">
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <h6 style="color: #f59e0b; margin: 0 0 4px 0;">
                            <i class="fa-solid fa-clipboard-check"></i> Relatório de Verificação de Cumprimento (Retorno A/S)
                        </h6>
                        <small class="text-white-50">Vinculado ao Laudo Anterior: <strong>REL-2026-0042</strong></small>
                    </div>

                    <div style="background: #09211c; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-white">Exigência Herdada #1: Recarregar 2 extintores de CO2 da praça de máquinas</strong>
                            <span class="badge-as-critico">ORIGEM A/S</span>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <label class="small text-white-50 mb-1">Resultado da verificação a bordo:</label>
                                <select class="form-select form-select-sm" style="background:#061915; border-color:#0d9488; color:#fff;">
                                    <option selected>Cumprida (extintores novos apresentados)</option>
                                    <option>Parcialmente cumprida / reescrita</option>
                                    <option>Não cumprida / transcrita</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="small text-white-50 mb-1">Evidência fotográfica do reparo:</label>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-success small d-inline-flex align-items-center gap-1 p-2">
                                        <i class="fa-solid fa-camera"></i> 1 foto anexada (selo INMETRO visível)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rule-box">
                <strong class="text-white"><i class="fa-solid fa-arrow-right-arrow-left text-accent"></i> Regra da Cadeia de Retorno:</strong>
                <ul class="text-white-50 small mb-0 mt-1 ps-3" style="line-height: 1.6;">
                    <li><strong>Se todas as exigências A/S foram marcadas como "Cumprida"</strong>: A embarcação é liberada imediatamente e o laudo segue para homologação com status de <strong>Aprovada</strong>.</li>
                    <li><strong>Se alguma exigência A/S continuar "Não cumprida"</strong>: A embarcação permanece impedida de navegar e um novo retorno precisará ser agendado.</li>
                    <li><strong>Se surgirem novos defeitos durante a visita</strong>: Utilize o botão <em>"Adicionar Nova Exigência Nesta Visita"</em> para documentar a nova deficiência encontrada.</li>
                </ul>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 8: CONCLUSÃO, ASSINATURA & LAUDO PDF -->
        <!-- ========================================================================= -->
        <section id="passo8" class="step-card">
            <div class="step-title">
                <span class="step-badge">8</span>
                <span>Conclusão Técnica, Assinatura Digital & Laudo Oficial</span>
            </div>
            <p class="text-white-50">
                Com todo o checklist e fotos conferidos, o vistoriador finaliza o laudo informando a data, prazo de validade, o operador presente a bordo e aplicando sua assinatura digital com fé pública.
            </p>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 14px; height: 100%;">
                        <strong class="text-accent small d-block mb-1"><i class="fa-solid fa-calendar-day"></i> Prazo de Validade</strong>
                        <p class="text-white-50 small mb-2">Selecione <strong>60 dias</strong> ou <strong>90 dias</strong> conforme a complexidade e a norma da Capitania. Isso define a vigência provisória dos certificados estatutários.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 14px; height: 100%;">
                        <strong class="text-accent small d-block mb-1"><i class="fa-solid fa-user-check"></i> Responsável Presente</strong>
                        <p class="text-white-50 small mb-2">Selecione o <strong>Armador</strong> ou digite o nome completo do <strong>Operador / Mestre da Embarcação</strong> que acompanhou a vistoria técnica a bordo.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #08211b; border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 14px; height: 100%;">
                        <strong class="text-accent small d-block mb-1"><i class="fa-solid fa-flag-checkered"></i> Encaminhamento</strong>
                        <p class="text-white-50 small mb-2">Selecione <strong>Aguardando Aprovação</strong> para enviar o laudo diretamente para a Mesa Técnica de Engenharia homologar.</p>
                    </div>
                </div>
            </div>

            <!-- MOCKUP ASSINATURA -->
            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                        <span class="mockup-dot"></span>
                    </div>
                    <span>Assinatura Digital do Vistoriador com Carimbo Criptográfico</span>
                </div>
                <div class="mockup-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <h5 class="text-white mb-1"><i class="fa-solid fa-fingerprint text-accent"></i> Assinatura Eletrônica Naval</h5>
                            <p class="text-white-50 small mb-2" style="line-height: 1.5;">
                                A assinatura eletrônica é vinculada ao perfil credenciado do vistoriador. O sistema calcula automaticamente o <strong>Hash SHA-256</strong>, carimbo de data/hora oficial e endereço IP/geolocalização, assegurando fé pública e irrefutabilidade perante a Capitania dos Portos.
                            </p>
                            <span class="badge bg-success"><i class="fa-solid fa-qrcode"></i> QR Code de Autenticidade Gerado no Rodapé</span>
                        </div>
                        <div class="col-md-5 text-center">
                            <div style="background: #ffffff; padding: 12px; border-radius: 8px; display: inline-block;">
                                <div style="font-family: 'Brush Script MT', cursive, sans-serif; font-size: 26px; color: #022c22; border-bottom: 2px solid #022c22; padding: 0 20px;">
                                    Eng. Vistoriador Responsável
                                </div>
                                <div style="font-size: 9px; color: #475569; font-weight: 700; margin-top: 4px;">
                                    CREA/CFT VÁLIDO &middot; CARIMBO DE TEMPO ICP-BRASIL
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="callout-box callout-info mt-3">
                <i class="fa-solid fa-file-pdf fa-2x mt-1 text-info"></i>
                <div>
                    <strong>Emissão Imediata do Laudo em PDF:</strong>
                    Após salvar e assinar, o laudo oficial em PDF pode ser gerado no botão <strong>"Imprimir / PDF Oficial"</strong>. O documento traz o brasão, enquadramento da Marinha, todas as não conformidades com fotos coloridas, parecer técnico e link público de verificação de autenticidade.
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- PASSO 9: FAQ INTERATIVO & GUIA RÁPIDO -->
        <!-- ========================================================================= -->
        <section id="passo9" class="step-card">
            <div class="step-title">
                <span class="step-badge">9</span>
                <span>Perguntas Frequentes do Vistoriador (FAQ & Resolução Rápida)</span>
            </div>

            <div class="accordion" id="accordionFaqVistoriador">
                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O que fazer se a embarcação estiver sem internet durante a vistoria?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-white-50 small" style="line-height: 1.6;">
                            Não se preocupe! Use o modo <strong>Campo PWA</strong> (<code>/campo/</code>). Certifique-se apenas de abrir a página e tocar em <em>Iniciar Vistoria</em> no escritório ou quando tiver sinal antes de ir a bordo. O sistema funciona 100% offline e salvará todas as fotos e notas no celular. Ao voltar para o hotel ou escritório com Wi-Fi, abra o app e ele sincronizará automaticamente.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ Como saber quando marcar uma exigência como A/S ou como Comum?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-white-50 small" style="line-height: 1.6;">
                            Faça a pergunta-chave de segurança: <em>"Se este navio sair navegando agora neste estado, há risco iminente de naufrágio, incêndio incontrolável, alagamento ou perda de vidas?"</em>. Se a resposta for <strong>SIM</strong> (ex.: leme travado, rombo no casco, sem coletes ou extintores), marque obrigatoriamente <strong>A/S (Antes de Suspender)</strong>. Se for um item de desgaste, identificação, plaqueta ou pintura que não impede a viagem, marque como <strong>Comum</strong> (com prazo de 60 ou 90 dias).
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O armador não estava presente no porto. Quem deve assinar como presente?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-white-50 small" style="line-height: 1.6;">
                            No campo <em>"Armador na data da Vistoria"</em>, mantenha a empresa armadora selecionada e preencha o campo <strong>"Nome do operador/responsável presente na vistoria"</strong> com o nome completo do Mestre da embarcação, marinheiro de convés ou representante do estaleiro que acompanhou a inspeção.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ Posso tirar várias fotos de uma mesma não conformidade?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-white-50 small" style="line-height: 1.6;">
                            <strong>Sim, com certeza!</strong> O sistema permite múltiplas fotos por item do checklist. Recomenda-se tirar uma foto em plano geral (para contextualizar a localização a bordo) e uma ou mais fotos em close-up com boa iluminação mostrando o detalhe do defeito ou da placa de identificação.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O que acontece depois que finalizo e envio o laudo?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="faqFive" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-white-50 small" style="line-height: 1.6;">
                            O laudo entra instantaneamente na fila da Mesa Técnica de Engenharia (módulo <em>Aprovação de Vistorias</em>). O Engenheiro Naval / Analista responsável audita as evidências fotográficas, confirma a conformidade normativa e procede com a emissão do <strong>CSN (Certificado de Segurança da Navegação)</strong>, <strong>CNBL</strong> ou <strong>CNARQ</strong>, além de disponibilizar a via oficial em PDF para o cliente e para a Capitania dos Portos.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- BOTÕES FINAIS DE AÇÃO -->
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>vistorias" class="btn btn-primary btn-lg px-4 me-2" style="background: #0d9488; border-color: #0d9488; font-weight: 700;">
                <i class="fa-solid fa-clipboard-list me-1"></i> Acessar Minhas Vistorias
            </a>
            <a href="<?= APP_URL ?>agendamentos" class="btn btn-outline-light btn-lg px-4" style="font-weight: 600;">
                <i class="fa-solid fa-calendar-check me-1"></i> Ver Escala de Agendamentos
            </a>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
