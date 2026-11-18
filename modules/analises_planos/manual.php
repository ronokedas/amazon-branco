<?php
/**
 * Manual Interativo do Usuário - Módulo de Análise de Planos & Engenharia Naval
 * Arquivo: modules/analises_planos/manual.php
 * Guia Prático Passo a Passo (Mesa Técnica, NORMAM-202, Pareceres e Homologação)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (!estaLogado()) {
    redirecionar(APP_URL . 'login');
}

$titulo_page = 'Guia Prático Passo a Passo — Módulo do Analista Naval';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual do Analista */
.manual-container {
    max-width: 1240px;
    margin: 0 auto;
    padding-bottom: 70px;
}

.manual-hero-analista {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(6, 44, 38, 0.85) 100%);
    border: 1px solid #3b82f6;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.manual-hero-analista::before {
    content: "";
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.manual-pills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.manual-nav-pill-blue {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.35);
    border-radius: 10px;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.manual-nav-pill-blue:hover {
    background: rgba(59, 130, 246, 0.2);
    border-color: #60a5fa;
    color: #93c5fd;
    transform: translateY(-2px);
}

.manual-nav-pill-blue i {
    color: #60a5fa;
}

.step-card {
    background: var(--bg-surface, #0d2127);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-radius: 14px;
    padding: 26px;
    margin-bottom: 32px;
    scroll-margin-top: 90px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.22);
    transition: border-color 0.2s ease;
}

.step-card:hover {
    border-color: rgba(59, 130, 246, 0.5);
}

.step-badge-blue {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #2563eb;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.15rem;
    margin-right: 12px;
    flex-shrink: 0;
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.45);
}

.step-title {
    display: flex;
    align-items: center;
    font-size: 1.35rem;
    color: #93c5fd;
    margin-bottom: 16px;
    font-weight: 700;
}

.mockup-window {
    background: #05141c;
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 12px;
    overflow: hidden;
    margin: 20px 0;
    box-shadow: 0 8px 26px rgba(0,0,0,0.4);
}

.mockup-header {
    background: #091e2b;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
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
    background: rgba(37, 99, 235, 0.18);
    border: 1px solid rgba(96, 165, 250, 0.4);
    color: #93c5fd;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.rule-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(59, 130, 246, 0.4);
    border-radius: 10px;
    padding: 16px;
    margin: 14px 0;
}

@media print {
    .sidebar, .navbar, .manual-nav-pill-blue, .btn-print, a[href*="vistorias"] {
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
}
</style>

<div class="conteudo-principal">
    <div class="manual-container">

        <!-- CABEÇALHO HERO -->
        <div class="manual-hero-analista">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="normam-tag mb-2">
                        <i class="fa-solid fa-compass-drafting"></i> MESA DE ENGENHARIA NAVAL &bull; NORMAM-202/DPC
                    </span>
                    <h1 class="text-white mt-1 mb-2" style="font-size: 2rem; font-weight: 800;">
                        Manual Prático do Analista Naval
                    </h1>
                    <p class="text-white-50 mb-0" style="font-size: 1.05rem; max-width: 820px; line-height: 1.5;">
                        Guia operacional passo a passo para a Mesa Técnica de Engenharia: gestão de pranchas, cálculos de estabilidade, matriz normativa de 11 itens da NORMAM-202, homologação de laudos de vistoria e emissão de pareceres e certificados com fé pública.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-light btn-sm d-flex align-items-center gap-2 btn-print" style="border-radius: 8px; font-weight: 600;">
                        <i class="fa-solid fa-print"></i> Imprimir Guia
                    </button>
                    <a href="<?= APP_URL ?>vistorias/manual" class="btn btn-outline-info btn-sm d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 600;">
                        <i class="fa-solid fa-clipboard-user"></i> Ver Manual do Vistoriador
                    </a>
                    <a href="<?= APP_URL ?>analises-planos" class="btn btn-primary btn-sm d-flex align-items-center gap-2" style="background: #2563eb; border-color: #2563eb; border-radius: 8px; font-weight: 600;">
                        <i class="fa-solid fa-arrow-left"></i> Voltar às Análises
                    </a>
                </div>
            </div>

            <!-- CHIPS DE NAVEGAÇÃO -->
            <div class="manual-pills-container">
                <a href="#passo1" class="manual-nav-pill-blue"><i class="fa-solid fa-table-columns"></i> 1. Dashboard & Fila</a>
                <a href="#passo2" class="manual-nav-pill-blue"><i class="fa-solid fa-folder-plus"></i> 2. Abertura & Enquadramento</a>
                <a href="#passo3" class="manual-nav-pill-blue"><i class="fa-solid fa-layer-group"></i> 3. Pranchas & NORMAM-202</a>
                <a href="#passo4" class="manual-nav-pill-blue"><i class="fa-solid fa-file-pen"></i> 4. Exigências & Parecer</a>
                <a href="#passo5" class="manual-nav-pill-blue"><i class="fa-solid fa-clipboard-check"></i> 5. Homologação de Vistorias</a>
                <a href="#passo6" class="manual-nav-pill-blue"><i class="fa-solid fa-stamp"></i> 6. Certificados Estatutários</a>
                <a href="#passo7" class="manual-nav-pill-blue"><i class="fa-solid fa-file-signature"></i> 7. Assinatura Digital</a>
                <a href="#passo8" class="manual-nav-pill-blue"><i class="fa-solid fa-circle-question"></i> 8. Guia Rápido & FAQ</a>
            </div>
        </div>

        <!-- 1. DASHBOARD DO ANALISTA -->
        <section id="passo1" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">1</span>
                <span>Dashboard do Analista & Gestão da Fila de Prazos</span>
            </div>
            <p class="text-white-50">
                A tela inicial (<code>/dashboard</code>) é a central de comando da Mesa Técnica. Ela organiza os processos por urgência temporal e status oficial de tramitação.
            </p>
            <div class="row g-3">
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid #3b82f6; border-radius:8px; padding:14px; text-align:center;">
                        <strong style="font-size:1.8rem; color:#60a5fa; display:block;">Atribuídos</strong>
                        <small class="text-white-50">Processos ativos sob sua responsabilidade técnica.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid #f59e0b; border-radius:8px; padding:14px; text-align:center;">
                        <strong style="font-size:1.8rem; color:#fbbf24; display:block;">Prazo Hoje</strong>
                        <small class="text-white-50">Processos com entrega agendada para o dia corrente.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid #ef4444; border-radius:8px; padding:14px; text-align:center;">
                        <strong style="font-size:1.8rem; color:#f87171; display:block;">Atrasadas</strong>
                        <small class="text-white-50">Processos estourados &mdash; prioridade máxima de ação.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid #10b981; border-radius:8px; padding:14px; text-align:center;">
                        <strong style="font-size:1.8rem; color:#34d399; display:block;">Concluídas</strong>
                        <small class="text-white-50">Pareceres publicados e homologados no mês.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. ABERTURA & ENQUADRAMENTO -->
        <section id="passo2" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">2</span>
                <span>Abertura de Processo & Enquadramento Regulamentar</span>
            </div>
            <p class="text-white-50">
                Ao cadastrar ou receber uma nova análise de planos, o engenheiro deve realizar o enquadramento do tipo de processo e da classe da embarcação conforme a NORMAM-202:
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background:#081e28; border:1px solid rgba(59,130,246,0.3); border-radius:8px; padding:16px;">
                        <h6 class="text-info"><i class="fa-solid fa-tag"></i> Tipos de Processo Naval</h6>
                        <ul class="text-white-50 small ps-3 mb-0" style="line-height:1.6;">
                            <li><b>LC (Licença de Construção):</b> Embarcação nova em fase de projeto inicial.</li>
                            <li><b>LA (Licença de Alteração):</b> Modificações estruturais, motorização ou convés.</li>
                            <li><b>LR (Licença de Reclassificação):</b> Mudança de atividade ou área de navegação.</li>
                            <li><b>LCEC:</b> Construção já executada / regularização perante a Capitania.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:#081e28; border:1px solid rgba(59,130,246,0.3); border-radius:8px; padding:16px;">
                        <h6 class="text-warning"><i class="fa-solid fa-shapes"></i> Classes de Embarcação</h6>
                        <ul class="text-white-50 small ps-3 mb-0" style="line-height:1.6;">
                            <li><b>EC1 (Embarcação de Maior Porte):</b> Exige plano de linhas, estabilidade intacta/em avaria e 11 itens NORMAM-202 completos.</li>
                            <li><b>EC2 (Embarcação de Médio/Menor Porte):</b> Requisitos dimensionais e estabilidade simplificados.</li>
                            <li><b>Autor do Projeto & ART:</b> Obrigatória vinculação de CREA/CFT com número de ART e termo de responsabilidade técnica.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. GESTÃO DE PRANCHAS & MATRIZ NORMAM-202 -->
        <section id="passo3" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">3</span>
                <span>Gestão de Revisões & Matriz Normativa dos 11 Itens</span>
            </div>
            <p class="text-white-50">
                Cada prancha arquitetônica (Arranjo Geral, Seção Mestra, Linhas, Capacidades, Combate a Incêndio) passa por triagem com histórico de revisões (R0, R1, R2). A NORMAM-202 exige a validação formal de 11 macroitens:
            </p>
            <div class="rule-box">
                <strong class="text-white"><i class="fa-solid fa-list-check text-info"></i> Classificação Formal de Cada Documento Analisado:</strong>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    <span class="badge bg-success py-2 px-3">ACEITO &mdash; Atende aos critérios técnicos e regulamentares</span>
                    <span class="badge bg-warning text-dark py-2 px-3">SUBSTITUÍDO &mdash; Versão superada por nova revisão</span>
                    <span class="badge bg-danger py-2 px-3">REJEITADO &mdash; Não conforme, gera exigência técnica</span>
                </div>
            </div>
        </section>

        <!-- 4. HOMOLOGAÇÃO DE VISTORIAS -->
        <section id="passo5" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">4</span>
                <span>Homologação de Laudos de Vistoria de Campo</span>
            </div>
            <p class="text-white-50">
                O Analista Naval atua como segundo par de olhos da segurança náutica. Nenhum laudo técnico de vistoriador entra em vigor sem a homologação da Mesa Técnica.
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background:rgba(16,185,129,0.1); border:1px solid #10b981; border-radius:8px; padding:14px;">
                        <h6 class="text-success"><i class="fa-solid fa-circle-check"></i> Homologação Aprovada</h6>
                        <small class="text-white-50">Audita as fotos do casco, salva-vidas e extintores. Estando em conformidade, libera imediatamente a emissão do <b>CSN</b>, <b>CNBL</b> ou <b>CNARQ</b>.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:rgba(239,68,68,0.1); border:1px solid #ef4444; border-radius:8px; padding:14px;">
                        <h6 class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Retorno A/S ou Não Homologado</h6>
                        <small class="text-white-50">Se houver exigência A/S sem comprovação cabal de reparo, a homologação mantém o navio retido e encaminha para vistoria de cumprimento.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. CERTIFICADOS ESTATUTÁRIOS -->
        <section id="passo6" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">5</span>
                <span>Emissão de Certificados Estatutários (CSN, CNBL, CNARQ)</span>
            </div>
            <p class="text-white-50">
                Após homologar a vistoria ou concluir a análise do plano, o engenheiro emite os documentos oficiais da Capitania dos Portos através do assistente (Wizard):
            </p>
            <div class="row g-2">
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:10px;">
                        <strong class="text-white d-block">CSN</strong>
                        <small class="text-white-50">Certificado de Segurança da Navegação.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:10px;">
                        <strong class="text-white d-block">CNBL</strong>
                        <small class="text-white-50">Certificado Nacional de Borda Livre.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:10px;">
                        <strong class="text-white d-block">CNARQ</strong>
                        <small class="text-white-50">Certificado Nacional de Arqueação.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background:#091e2b; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:10px;">
                        <strong class="text-white d-block">CHT</strong>
                        <small class="text-white-50">Certificado de Homologação e Teste.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. ASSINATURA DIGITAL -->
        <section id="passo7" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">6</span>
                <span>Mesa de Assinaturas Digitais & Fé Pública</span>
            </div>
            <p class="text-white-50">
                A aposição da rubrica e carimbo técnico do Analista confere valor jurídico pleno perante a Capitania dos Portos e Capitania Fluvial da Amazônia:
            </p>
            <div class="callout-box callout-info">
                <i class="fa-solid fa-fingerprint fa-2x mt-1 text-info"></i>
                <div>
                    <strong>Criptografia SHA-256 e QR Code:</strong> Cada parecer e certificado assinado gera automaticamente um hash de integridade de 256 bits, carimbo de tempo ICP-Brasil, coordenadas geográficas e QR Code de consulta pública no rodapé.
                </div>
            </div>
        </section>

        <!-- 7. FAQ -->
        <section id="passo8" class="step-card">
            <div class="step-title">
                <span class="step-badge-blue">7</span>
                <span>Perguntas Frequentes & Resolução Rápida (FAQ)</span>
            </div>
            <div class="accordion" id="accordionFaqAnalista">
                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(59,130,246,0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqAnOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnOne" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O que fazer quando uma exigência técnica for sanada pelo projetista?
                        </button>
                    </h2>
                    <div id="collapseAnOne" class="accordion-collapse collapse" data-bs-parent="#accordionFaqAnalista">
                        <div class="accordion-body text-white-50 small" style="line-height:1.6;">
                            Nunca apague a exigência original! Registre o recebimento da nova revisão da prancha (ex.: R1), faça o upload do arquivo revisado, classifique como <em>Aceito</em> e emita o parecer de ciclo registrando a manifestação de cumprimento.
                        </div>
                    </div>
                </div>
                <div class="accordion-item" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(59,130,246,0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqAnTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnTwo" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O Analista pode consultar o laudo e as fotos de campo do Vistoriador?
                        </button>
                    </h2>
                    <div id="collapseAnTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFaqAnalista">
                        <div class="accordion-body text-white-50 small" style="line-height:1.6;">
                            Sim! Todo processo de análise vinculado à embarcação permite ao Analista acessar o relatório e o PDF da vistoria técnica correspondente com todas as evidências fotográficas em alta resolução.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- BOTÕES FINAIS -->
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>vistorias/manual" class="btn btn-outline-info btn-lg px-4 me-2" style="font-weight: 700;">
                <i class="fa-solid fa-clipboard-user me-1"></i> Abrir Manual do Vistoriador
            </a>
            <a href="<?= APP_URL ?>analises-planos" class="btn btn-primary btn-lg px-4" style="background: #2563eb; border-color: #2563eb; font-weight: 700;">
                <i class="fa-solid fa-compass-drafting me-1"></i> Central de Análise de Planos
            </a>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
