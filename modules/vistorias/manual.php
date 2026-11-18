<?php
/**
 * Manual Interativo do Usuário - Módulo do Vistoriador Naval
 * Arquivo: modules/vistorias/manual.php
 * Guia Prático Passo a Passo (Campo, Checklist NORMAM & Emissão de Laudos Técnicos)
 * Padrão: NORMAM-201/202/211, RIPEAM, NPCP/NPCF | Referência visual: modules/protocolos/manual.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e validar perfil exclusivo do Vistoriador (ou Administrador)
verificar_sessao();
$cargoUsuario = getCargo();
if (!in_array($cargoUsuario, ['VISTORIADOR', 'ADMIN', 'SECRETARIA'], true)) {
    setMensagem('error', 'Acesso negado. O Manual do Vistoriador é de acesso exclusivo para Vistoriadores e Administradores.');
    redirecionar(APP_URL . 'dashboard');
    exit;
}

$titulo_page = 'Guia Prático Passo a Passo — Módulo do Vistoriador Naval';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual Didático do Vistoriador (Padrão Protocolos) */
.manual-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 60px;
}

.manual-hero {
    background: linear-gradient(135deg, rgba(8, 118, 83, 0.35) 0%, rgba(7, 31, 27, 0.85) 100%);
    border: 1px solid var(--accent, #56e0ad);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.manual-nav-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(86, 224, 173, 0.28);
    border-radius: 10px;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.manual-nav-pill:hover {
    background: rgba(86, 224, 173, 0.18);
    border-color: var(--accent, #56e0ad);
    color: var(--accent, #56e0ad);
    transform: translateY(-2px);
}

.step-card {
    background: var(--bg-surface, #0c2b24);
    border: 1px solid rgba(86, 224, 173, 0.22);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 30px;
    scroll-margin-top: 90px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    transition: border-color 0.2s ease;
}

.step-card:hover {
    border-color: rgba(86, 224, 173, 0.45);
}

.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #087653;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.15rem;
    margin-right: 12px;
    box-shadow: 0 2px 8px rgba(8, 118, 83, 0.4);
    flex-shrink: 0;
}

.step-title {
    display: flex;
    align-items: center;
    font-size: 1.35rem;
    color: var(--accent, #56e0ad);
    margin-bottom: 15px;
    font-weight: 700;
}

.mockup-window {
    background: #051613;
    border: 1px solid rgba(86, 224, 173, 0.25);
    border-radius: 12px;
    overflow: hidden;
    margin: 20px 0;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}

.mockup-header {
    background: #08211b;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(86, 224, 173, 0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #cbd5e1;
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
    padding: 20px;
    color: #f1f5f9;
}

.callout-box {
    background: rgba(86, 224, 173, 0.1);
    border-left: 4px solid var(--accent, #56e0ad);
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.95rem;
    color: #e2e8f0;
    line-height: 1.6;
}

.callout-tip {
    background: rgba(245, 158, 11, 0.1);
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.95rem;
    color: #fef08a;
    line-height: 1.6;
}

.callout-danger {
    background: rgba(239, 68, 68, 0.12);
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.95rem;
    color: #fecaca;
    line-height: 1.6;
}

.callout-info {
    background: rgba(14, 165, 233, 0.12);
    border-left: 4px solid #0284c7;
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.95rem;
    color: #bae6fd;
    line-height: 1.6;
}

.normam-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(86, 224, 173, 0.15);
    border: 1px solid rgba(86, 224, 173, 0.35);
    color: #56e0ad;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.rule-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(86, 224, 173, 0.35);
    border-radius: 10px;
    padding: 16px;
    margin: 14px 0;
    color: #f1f5f9;
}

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
    color: #ffffff;
}

.btn-demo-primary { background: #087653; color: #fff; }
.btn-demo-success { background: #16a34a; color: #fff; }
.btn-demo-warning { background: #eab308; color: #042019; }
.btn-demo-info    { background: #0284c7; color: #fff; }
.btn-demo-danger  { background: #dc2626; color: #fff; }
.btn-demo-secondary { background: #334155; color: #fff; }

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

.text-high-contrast {
    color: #f1f5f9 !important;
}

.text-muted-contrast {
    color: #cbd5e1 !important;
}

@media print {
    .sidebar, .top-header, .manual-nav-pill, .btn-print, .inspection-page-header a {
        display: none !important;
    }
    .conteudo-principal {
        margin: 0 !important;
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
}
</style>

<main class="conteudo-principal">
    <div class="manual-container">

        <!-- CABEÇALHO DO MANUAL (HERO) -->
        <div class="manual-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge" style="background: var(--accent, #56e0ad); color: #042019; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; margin-bottom: 12px; display: inline-block;">
                        <i class="fa-solid fa-anchor"></i> DIRETRIZES DA AUTORIDADE MARÍTIMA &bull; DPC
                    </span>
                    <h1 class="text-white fw-bold mb-2" style="font-size: 2rem;">
                        Manual do Usuário: Vistoriador Naval & Campo
                    </h1>
                    <p class="text-muted-contrast lead mb-0" style="max-width: 840px; font-size: 1.05rem; line-height: 1.6;">
                        Aprenda passo a passo a rotina do vistoriador em campo: desde o recebimento do agendamento, inspeção offline via celular/tablet, preenchimento do checklist NORMAM, registro de evidências e não conformidades (A/S vs Comuns), até a assinatura eletrônica e homologação do laudo técnico.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-secondary d-inline-flex align-items-center gap-2 btn-print">
                        <i class="fa-solid fa-print"></i> Imprimir Guia
                    </button>
                    <a href="<?= APP_URL ?>vistorias" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: #087653; border-color: #087653;">
                        <i class="fa-solid fa-arrow-left"></i> Voltar às Vistorias
                    </a>
                </div>
            </div>
        </div>

        <!-- CHIPS DE NAVEGAÇÃO RÁPIDA -->
        <div class="card mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 12px;">
            <div class="card-body p-3">
                <div class="small fw-bold text-accent mb-2">
                    <i class="fa-solid fa-list-ol"></i> ÍNDICE RÁPIDO DO MANUAL (CLIQUE PARA IR DIRETO AO PASSO):
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#passo1" class="manual-nav-pill"><i class="fa-solid fa-calendar-check text-accent"></i> 1. Agendamento & Escala</a>
                    <a href="#passo2" class="manual-nav-pill"><i class="fa-solid fa-mobile-screen-button text-info"></i> 2. Modo Campo (Offline)</a>
                    <a href="#passo3" class="manual-nav-pill"><i class="fa-solid fa-list-check text-warning"></i> 3. Checklist NORMAM & Categorias</a>
                    <a href="#passo4" class="manual-nav-pill"><i class="fa-solid fa-camera text-success"></i> 4. Fotos & Evidências</a>
                    <a href="#passo5" class="manual-nav-pill"><i class="fa-solid fa-triangle-exclamation text-danger"></i> 5. Regra Crítica: A/S vs Comum</a>
                    <a href="#passo6" class="manual-nav-pill"><i class="fa-solid fa-plus-circle text-accent"></i> 6. Exigências Avulsas</a>
                    <a href="#passo7" class="manual-nav-pill"><i class="fa-solid fa-arrows-rotate text-info"></i> 7. Vistoria de Retorno</a>
                    <a href="#passo8" class="manual-nav-pill"><i class="fa-solid fa-file-signature text-warning"></i> 8. Conclusão & Assinatura</a>
                    <a href="#faq" class="manual-nav-pill"><i class="fa-solid fa-circle-question text-info"></i> 9. Perguntas Frequentes (FAQ)</a>
                </div>
            </div>
        </div>

        <!-- NOTA REGULAMENTAR -->
        <div class="callout-box">
            <strong class="text-accent"><i class="fa-solid fa-compass"></i> Atenção aos Fundamentos Técnicos Navais:</strong>
            O módulo opera sob as normas da Autoridade Marítima (<strong>NORMAM-201</strong> para Mar Aberto, <strong>NORMAM-202</strong> para Navegação Interior, <strong>NORMAM-211/212</strong> para Borda Livre e Arqueação, <strong>NPCP/NPCF</strong> das Capitanias Fluviais e <strong>RIPEAM</strong> para governo e navegação). 
            <em>Regras de auditoria interna ISO/SGQ são deliberadamente desconsideradas</em> para garantir foco total na segurança da navegação e praticidade para o vistoriador a bordo.
        </div>

        <!-- PASSO 1: AGENDAMENTO & ESCALA -->
        <section id="passo1" class="step-card">
            <div class="step-title">
                <span class="step-badge">1</span> Passo 1: Recebimento do Agendamento & Escala Diária
            </div>
            <p class="text-high-contrast" style="line-height: 1.7;">
                Toda vistoria tem início com um agendamento prévio cadastrado no sistema comercial ou administrativo. O vistoriador visualiza diretamente na tela inicial do módulo de Vistorias as inspeções atribuídas a ele.
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;" class="text-high-contrast">
                <li>No menu lateral esquerdo do sistema, clique em <strong>Vistorias Técnicas</strong> (rota <code>/vistorias</code>).</li>
                <li>No topo da tela, localize o card destacado em verde: <strong>"Vistorias Agendadas & Em Andamento"</strong>.</li>
                <li>Confira a data, hora, nome da embarcação, registro e o local do porto/estaleiro.</li>
                <li>Clique no botão verde <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-play"></i> Iniciar Vistoria</span> (se for nova) ou <span class="btn-demo btn-demo-info"><i class="fa-solid fa-pen-to-square"></i> Continuar Vistoria</span> (se já estava em andamento).</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots"><span class="mockup-dot"></span><span class="mockup-dot"></span><span class="mockup-dot"></span></div>
                    <span>Tela: ERP Web &rarr; Vistorias &rarr; Painel de Escala Imediata</span>
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="mockup-body">
                    <div style="background: #08211b; border: 2px solid var(--accent, #56e0ad); border-radius: 10px; padding: 16px;">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <strong style="color: var(--accent, #56e0ad); font-size: 1.05rem;">
                                <i class="fa-solid fa-calendar-days"></i> Vistorias Agendadas & Em Andamento
                            </strong>
                            <span class="badge" style="background: #ccfbf1; color: #0f766e; font-weight: 700;">1 pendente na sua escala</span>
                        </div>
                        <div style="background: #051613; border: 1px solid rgba(86, 224, 173, 0.3); border-radius: 8px; padding: 14px;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--accent, #56e0ad); background: rgba(86, 224, 173, 0.15); padding: 2px 8px; border-radius: 4px;">
                                    <i class="fa-regular fa-calendar"></i> 20/11/2026 às 09:00
                                </span>
                                <span class="badge bg-warning text-dark fw-bold">AGENDADA</span>
                            </div>
                            <h4 style="margin: 4px 0; color: #ffffff; font-size: 1.15rem;">
                                <i class="fa-solid fa-ship" style="color: var(--accent, #56e0ad);"></i> F/B RIO AMAZONAS IV
                            </h4>
                            <div class="text-muted-contrast small" style="margin: 4px 0 10px 0;">
                                Inscrição: <strong class="text-white">021-004523-9</strong> &bull; Armador: <strong class="text-white">Navegações Tapajós Ltda</strong> &bull; Tipo: <strong class="text-white">Vistoria Periódica / Flutuando</strong>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-play"></i> Iniciar Vistoria</span>
                                <span class="text-muted-contrast small align-self-center">
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
            <p class="text-high-contrast" style="line-height: 1.7;">
                Nos rios da Amazônia e portos afastados, a conexão com a internet muitas vezes é inexistente. O sistema conta com uma aplicação web progressiva (PWA) projetada especificamente para o vistoriador trabalhar em modo avião:
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.25);">
                        <strong class="text-warning d-block mb-1"><i class="fa-solid fa-cloud-arrow-down"></i> 1. No Cais com Sinal</strong>
                        <small class="text-muted-contrast">Abra o endereço <code>/campo/</code> no celular ainda com internet. Toque em <strong>Iniciar Vistoria</strong> para baixar o pacote de dados e fotos para o aparelho.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.25);">
                        <strong class="text-info d-block mb-1"><i class="fa-solid fa-plane-departure"></i> 2. A Bordo Sem Sinal</strong>
                        <small class="text-muted-contrast">Ative o modo avião. Todas as notas, fotos e respostas do checklist são gravadas no <strong>IndexedDB local</strong>. Mesmo que a bateria acabe, nada é perdido.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.25);">
                        <strong class="text-success d-block mb-1"><i class="fa-solid fa-rotate"></i> 3. Sincronização Segura</strong>
                        <small class="text-muted-contrast">Ao voltar à área de cobertura, a fila do app envia tudo automaticamente para o servidor central, sem duplicar dados nem apagar nada do celular antes da confirmação.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 3: CHECKLIST NORMAM -->
        <section id="passo3" class="step-card">
            <div class="step-title">
                <span class="step-badge">3</span> Passo 3: Checklist NORMAM Inteligente & Categorias Navais
            </div>
            <p class="text-high-contrast" style="line-height: 1.7;">
                O sistema identifica as características da embarcação (Arqueação Bruta, Propulsão e Passageiros) e enquadra nos requisitos específicos da <strong>NORMAM-202/201</strong> (Categorias <code>a</code>, <code>b</code>, <code>c</code>, <code>d</code>, <code>e</code> ou <code>f</code>), dividindo a inspeção em 4 blocos técnicos:
            </p>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="p-2 rounded text-center" style="background: rgba(2, 132, 199, 0.15); border: 1px solid #0284c7;">
                        <i class="fa-solid fa-anchor fa-2x text-info mb-1"></i>
                        <h6 class="text-white mb-0">Seco</h6>
                        <small class="text-muted-contrast">Casco, leme, hélice, anodos e tomadas de mar.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 rounded text-center" style="background: rgba(13, 148, 136, 0.15); border: 1px solid #0d9488;">
                        <i class="fa-solid fa-life-ring fa-2x text-accent mb-1"></i>
                        <h6 class="text-white mb-0">Flutuando</h6>
                        <small class="text-muted-contrast">Salvatagem, incêndio, esgoto e luzes RIPEAM.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 rounded text-center" style="background: rgba(217, 119, 6, 0.15); border: 1px solid #d97706;">
                        <i class="fa-solid fa-water fa-2x text-warning mb-1"></i>
                        <h6 class="text-white mb-0">Borda Livre</h6>
                        <small class="text-muted-contrast">Marca de Plimsoll, calados vante/ré e portas estanques.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 rounded text-center" style="background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a;">
                        <i class="fa-solid fa-ruler-combined fa-2x text-success mb-1"></i>
                        <h6 class="text-white mb-0">Arqueação</h6>
                        <small class="text-muted-contrast">Medição de Boca, Pontal e Comprimento real.</small>
                    </div>
                </div>
            </div>

            <ol style="line-height: 1.8; font-size: 1rem;" class="text-high-contrast">
                <li>Classifique cada item tocando em <span class="btn-demo btn-demo-success">CONFORME</span>, <span class="btn-demo btn-demo-danger">NÃO CONFORME</span> ou <span class="btn-demo btn-demo-secondary">N/A</span>.</li>
                <li>Ao marcar <strong>NÃO CONFORME</strong>, o sistema abre imediatamente os campos de <em>Referência da NORMAM</em>, <em>Observação do defeito</em> e a caixa crítica <em>A/S (Antes de Suspender)</em>.</li>
            </ol>
        </section>

        <!-- PASSO 4: REGISTRO DE FOTOS -->
        <section id="passo4" class="step-card">
            <div class="step-title">
                <span class="step-badge">4</span> Passo 4: Foto Oficial da Embarcação & Evidências Fotográficas
            </div>
            <p class="text-high-contrast" style="line-height: 1.7;">
                Uma vistoria naval sem evidência fotográfica nítida não é aceita pela Capitania dos Portos:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.25);">
                        <strong class="text-warning d-block mb-1"><i class="fa-solid fa-camera"></i> 1. Foto Oficial da Embarcação (Mestre)</strong>
                        <p class="text-muted-contrast small mb-0" style="line-height: 1.6;">
                            Tire enquadrando o <strong>costado completo da embarcação</strong> (proa a popa), com o <strong>nome e porto de inscrição perfeitamente legíveis</strong>. Esta foto vai estampada na capa do laudo oficial pericial.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.25);">
                        <strong class="text-danger d-block mb-1"><i class="fa-solid fa-images"></i> 2. Múltiplas Fotos por Item do Checklist</strong>
                        <p class="text-muted-contrast small mb-0" style="line-height: 1.6;">
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
            <p class="text-high-contrast" style="line-height: 1.7;">
                Este é o ponto de maior responsabilidade técnica do vistoriador:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; border-radius: 10px; padding: 18px;">
                        <span class="badge-as-critico mb-2"><i class="fa-solid fa-ban"></i> EXIGÊNCIA A/S</span>
                        <h5 class="text-white mb-2">"Antes de Suspender" (Embarcação Retida)</h5>
                        <p class="text-muted-contrast small" style="line-height: 1.6;">
                            Risco iminente à vida humana, à navegabilidade ou ao meio ambiente. A embarcação <strong>NÃO PODE NAVEGAR</strong> até o retorno.
                        </p>
                        <ul class="text-muted-contrast small ps-3 mb-0">
                            <li>Falta de coletes salva-vidas ou coletes rasgados/vencidos.</li>
                            <li>Extintores despressurizados na praça de máquinas.</li>
                            <li>Leme travado ou trinca passante no casco.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background: rgba(245, 158, 11, 0.1); border: 2px solid #f59e0b; border-radius: 10px; padding: 18px;">
                        <span class="badge-comum mb-2"><i class="fa-solid fa-clock"></i> EXIGÊNCIA COMUM</span>
                        <h5 class="text-white mb-2">Com Prazo Regulamentar (60 ou 90 dias)</h5>
                        <p class="text-muted-contrast small" style="line-height: 1.6;">
                            Deficiência de menor gravidade funcional ou documental que <strong>não impede a viagem imediata</strong> com segurança.
                        </p>
                        <ul class="text-muted-contrast small ps-3 mb-0">
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
            <p class="text-high-contrast" style="line-height: 1.7;">
                Caso encontre uma particularidade que não consta no catálogo padrão, utilize a seção <strong>Exigências Avulsas</strong>:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;" class="text-high-contrast">
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
            <p class="text-high-contrast" style="line-height: 1.7;">
                Quando o armador sana as pendências, o sistema abre o <strong>Relatório de Verificação de Cumprimento</strong> herdando todas as exigências do laudo pai:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;" class="text-high-contrast">
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
            <p class="text-high-contrast" style="line-height: 1.7;">
                Para finalizar a inspeção técnica:
            </p>
            <ol style="line-height: 1.8; font-size: 1rem;" class="text-high-contrast">
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

            <div class="accordion" id="accordionFaqVistoriador">
                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqVisOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisOne" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O que fazer se a embarcação estiver sem internet durante a vistoria?
                        </button>
                    </h2>
                    <div id="collapseVisOne" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-muted-contrast small" style="line-height: 1.7;">
                            Não se preocupe! Use o modo <strong>Campo PWA</strong> (<code>/campo/</code>). Abra a página e toque em <em>Iniciar Vistoria</em> no escritório ou quando tiver sinal antes de ir a bordo. O sistema funciona 100% offline e salvará todas as fotos e notas no aparelho (IndexedDB). Ao voltar para o sinal, ele sincronizará automaticamente sem duplicar nada.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqVisTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisTwo" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ Como saber com certeza se devo marcar uma exigência como A/S?
                        </button>
                    </h2>
                    <div id="collapseVisTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-muted-contrast small" style="line-height: 1.7;">
                            Faça a pergunta-chave de segurança da navegação: <em>"Se este navio sair navegando agora neste estado, há risco iminente de naufrágio, incêndio incontrolável, alagamento ou perda de vidas humanas?"</em>. Se a resposta for <strong>SIM</strong> (ex.: leme quebrado, rombo no casco, sem coletes ou extintores), marque obrigatoriamente <strong>A/S (Antes de Suspender)</strong>.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.25); border-radius: 8px;">
                    <h2 class="accordion-header" id="faqVisThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVisThree" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O armador não estava a bordo na hora da vistoria. Quem deve constar como presente?
                        </button>
                    </h2>
                    <div id="collapseVisThree" class="accordion-collapse collapse" data-bs-parent="#accordionFaqVistoriador">
                        <div class="accordion-body text-muted-contrast small" style="line-height: 1.7;">
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
