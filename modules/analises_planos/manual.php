<?php
/**
 * Manual Interativo do Usuário - Módulo de Análise de Planos & Engenharia Naval
 * Arquivo: modules/analises_planos/manual.php
 * Passo a passo didático, ilustrado e profissional para operação técnica naval.
 * Padrão: NORMAM-202/DPC, RIPEAM | Referência visual: modules/protocolos/manual.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargoUsuario = getCargo();
if (!in_array($cargoUsuario, ['ANALISTA', 'ADMIN', 'SECRETARIA'], true)) {
    setMensagem('error', 'Acesso negado. O Manual do Analista é de acesso exclusivo para Analistas e Administradores.');
    redirecionar(APP_URL . 'dashboard');
    exit;
}

$titulo_page = 'Manual do Usuário - Análise de Planos & Engenharia Naval';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual Didático do Analista */
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

.feature-box small {
    color: #475569 !important;
    line-height: 1.6;
    display: block;
}

/* Janela de Mockup (Simulador de Telas do Sistema) */
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
    padding: 14px;
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
    .sidebar, .top-header, .manual-nav-pill, #btn-imprimir-manual, .manual-index-card {
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
        color: #000 !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .mockup-window {
        background: #f8fafc !important;
        color: #000 !important;
        border: 1px solid #94a3b8 !important;
    }
    .mockup-header {
        background: #334155 !important;
        color: #fff !important;
    }
}
</style>

<main class="conteudo-principal">
    <div class="manual-container">

        <!-- Banner Principal de Apresentação (Hero) -->
        <div class="manual-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge" style="background: #56e0ad; color: #042019; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; margin-bottom: 12px; display: inline-block;">
                        <i class="fa-solid fa-compass-drafting"></i> GUIA PRÁTICO PASSO A PASSO &bull; ENGENHARIA NAVAL
                    </span>
                    <h1 class="fw-bold mb-2" style="font-size: 2rem;">
                        Manual do Usuário: Análise de Planos & Mesa Técnica
                    </h1>
                    <p class="lead mb-0" style="max-width: 840px; font-size: 1.05rem; line-height: 1.6;">
                        Aprenda passo a passo a controlar pranchas de projeto, auditar os 11 itens da NORMAM-202, homologar relatórios de vistoria de campo e emitir pareceres e certificados estatutários com fé pública.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light d-inline-flex align-items-center gap-2 fw-bold" id="btn-imprimir-manual" onclick="window.print()" style="color: #0f172a;">
                        <i class="fa-solid fa-print"></i> Imprimir Manual
                    </button>
                    <a href="<?= APP_URL ?>analises-planos" class="btn btn-outline-light d-inline-flex align-items-center gap-2 fw-bold">
                        <i class="fa-solid fa-arrow-left"></i> Voltar às Análises
                    </a>
                </div>
            </div>
        </div>

        <!-- Menu Rápido com Navegação Suave (Chips) -->
        <div class="manual-index-card card mb-4">
            <div class="card-body p-3">
                <div class="small fw-bold mb-2" style="color: #087653;">
                    <i class="fa-solid fa-list-ol"></i> ÍNDICE RÁPIDO DO MANUAL (CLIQUE PARA IR DIRETO AO PASSO):
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#o-que-e" class="manual-nav-pill">
                        <i class="fa-solid fa-circle-question text-info"></i> O que é a Mesa de Análise?
                    </a>
                    <a href="#passo1" class="manual-nav-pill">
                        <i class="fa-solid fa-table-columns text-success"></i> Passo 1: Painel & Fila de Prazos
                    </a>
                    <a href="#passo2" class="manual-nav-pill">
                        <i class="fa-solid fa-folder-plus text-warning"></i> Passo 2: Abertura & Enquadramento
                    </a>
                    <a href="#passo3" class="manual-nav-pill">
                        <i class="fa-solid fa-layer-group text-info"></i> Passo 3: Pranchas & NORMAM-202
                    </a>
                    <a href="#passo4" class="manual-nav-pill">
                        <i class="fa-solid fa-file-pen text-danger"></i> Passo 4: Exigências & Parecer Técnico
                    </a>
                    <a href="#passo5" class="manual-nav-pill">
                        <i class="fa-solid fa-clipboard-check text-success"></i> Passo 5: Homologação de Vistorias
                    </a>
                    <a href="#passo6" class="manual-nav-pill">
                        <i class="fa-solid fa-stamp text-primary"></i> Passo 6: Certificados Estatutários
                    </a>
                    <a href="#passo7" class="manual-nav-pill">
                        <i class="fa-solid fa-signature text-warning"></i> Passo 7: Assinatura Digital
                    </a>
                    <a href="#faq" class="manual-nav-pill">
                        <i class="fa-solid fa-circle-nodes text-secondary"></i> Dúvidas Frequentes
                    </a>
                </div>
            </div>
        </div>

        <!-- SEÇÃO: O QUE É A MESA DE ANÁLISE? -->
        <section class="step-card" id="o-que-e">
            <div class="step-title">
                <i class="fa-solid fa-circle-question fs-4 me-2"></i> O que é a Central de Análise de Planos?
            </div>
            <p style="font-size: 1rem; line-height: 1.7;">
                A <strong>Mesa de Engenharia Naval</strong> é o coração regulamentar da Amazon Naval. É nela que os <strong>Engenheiros Navais e Analistas Técnicos</strong> avaliam memoriais de cálculo, planos de linhas, estabilidade intacta/em avaria, arranjo geral e projetos de construção ou alteração de embarcações, emitindo os pareceres exigidos pela <strong>Capitania dos Portos</strong> conforme a <strong>NORMAM-202</strong> e <strong>NORMAM-201</strong>.
            </p>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong><i class="fa-solid fa-compass-drafting"></i> 1. Matriz Normativa dos 11 Itens</strong>
                        <small>Auditoria detalhada de cada projeto: linhas, arranjo, capacidade, estabilidade, borda livre, esgoto, incêndio, máquinas e regras do RIPEAM.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong><i class="fa-solid fa-clipboard-check"></i> 2. Homologação de Campo</strong>
                        <small>Revisão pericial das vistorias feitas pelos vistoriadores nos portos e rios, conferindo fotos georreferenciadas e cumprimento de normas.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <strong><i class="fa-solid fa-stamp"></i> 3. Certificação Oficial</strong>
                        <small>Geração de pareceres técnicos, CSN (Certificado de Segurança), CNBL (Borda Livre) e CNARQ (Arqueação) com assinatura digital criptográfica.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 1: PAINEL & FILA DE PRAZOS -->
        <section class="step-card" id="passo1">
            <div class="step-title">
                <span class="step-badge">1</span> Passo 1: Como Interpretar a Central de Análise e a Fila de Prazos
            </div>
            <p style="line-height: 1.7;">
                Ao entrar no sistema como Analista, a sua tela inicial (<code>/dashboard</code>) organiza o trabalho por urgência temporal. Para operar o painel com agilidade:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li><strong>Confira o Banner de Processo Urgente:</strong> Se houver processo com prazo vencido ou vencendo hoje, um alerta no topo destacará o barco e o cliente. Clique direto no botão verde <span class="btn-demo btn-demo-success"><i class="fa-solid fa-magnifying-glass"></i> Analisar Agora</span>.</li>
                <li><strong>Avalie os 5 Indicadores (KPIs):</strong>
                    <ul>
                        <li><strong class="text-primary">Atribuídas a Você:</strong> Quantidade total de processos sob sua responsabilidade técnica.</li>
                        <li><strong class="text-warning">Prazo Hoje:</strong> Processos que devem ser analisados e despachados até o fim do expediente.</li>
                        <li><strong class="text-danger">Atrasadas:</strong> Processos com prazo estourado perante a Capitania dos Portos (prioridade máxima).</li>
                        <li><strong>Aguardando Documentos:</strong> Processos em que o projetista ou armador precisa enviar pranchas corrigidas.</li>
                        <li><strong class="text-success">Concluídas no Mês:</strong> Pareceres emitidos e homologados no ciclo atual.</li>
                    </ul>
                </li>
                <li><strong>Fila de Prioridades:</strong> A tabela inferior lista os processos ordenados por data fatal de entrega. Clique em <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-compass-drafting"></i> Analisar</span> na linha do processo para abrir o dossiê de pranchas.</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot mockup-dot-red"></span>
                        <span class="mockup-dot mockup-dot-yellow"></span>
                        <span class="mockup-dot mockup-dot-green"></span>
                    </div>
                    <span>Tela: Minha Central de Análise de Planos (/dashboard)</span>
                    <i class="fa-solid fa-window-maximize"></i>
                </div>
                <div class="mockup-body">
                    <div style="background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <span class="badge bg-danger mb-1"><i class="fa-solid fa-clock"></i> PRAZO EXPIRADO</span>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong style="color: #991b1b; font-size: 1.1rem;">AM-RAP-16/26 &bull; POSTO MACEDÃO I</strong>
                                <div class="small" style="color: #7f1d1d;">LC (EC1) &bull; Armador: Trairi Comércio de Petróleo &bull; Prazo: Hoje</div>
                            </div>
                            <span class="btn-demo btn-demo-success"><i class="fa-solid fa-magnifying-glass"></i> Analisar Agora</span>
                        </div>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col"><div class="p-2 rounded bg-white" style="border: 1px solid #087653;"><strong class="text-success fs-5 d-block">45</strong><small class="text-muted">Atribuídas</small></div></div>
                        <div class="col"><div class="p-2 rounded bg-white" style="border: 1px solid #d97706;"><strong class="text-warning fs-5 d-block">2</strong><small class="text-muted">Prazo Hoje</small></div></div>
                        <div class="col"><div class="p-2 rounded bg-white" style="border: 1px solid #ef4444;"><strong class="text-danger fs-5 d-block">1</strong><small class="text-muted">Atrasadas</small></div></div>
                        <div class="col"><div class="p-2 rounded bg-white" style="border: 1px solid #16a34a;"><strong class="text-success fs-5 d-block">18</strong><small class="text-muted">Concluídas</small></div></div>
                    </div>
                </div>
            </div>

            <div class="callout-box">
                <strong><i class="fa-solid fa-lightbulb"></i> Dica Operacional:</strong>
                Ao clicar em qualquer card de KPI no topo da listagem de análises, a tabela se auto-filtra instantaneamente em 1 clique, sem precisar digitar nada no campo de busca!
            </div>
        </section>

        <!-- PASSO 2: ABERTURA & ENQUADRAMENTO -->
        <section class="step-card" id="passo2">
            <div class="step-title">
                <span class="step-badge">2</span> Passo 2: Como Abrir uma Nova Análise de Planos e Fazer o Enquadramento
            </div>
            <p style="line-height: 1.7;">
                Quando um novo projeto naval der entrada no escritório ou estaleiro, cadastre a análise técnica seguindo estes passos:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No topo da tela da Central, clique no botão verde <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-plus"></i> Nova Análise de Planos</span>.</li>
                <li><strong>Escolha a Embarcação:</strong> Selecione o barco na lista. O sistema carrega automaticamente o Armador, Porto de Inscrição e Arqueação Bruta!</li>
                <li><strong>Selecione o Tipo de Processo:</strong>
                    <ul>
                        <li><strong style="color: #087653;">LC (Licença de Construção):</strong> Embarcação nova em início de obra.</li>
                        <li><strong style="color: #d97706;">LA (Licença de Alteração):</strong> Modificações estruturais, troca de motor ou aumento de convés.</li>
                        <li><strong style="color: #0284c7;">LR (Licença de Reclassificação):</strong> Alteração de área de navegação ou atividade.</li>
                        <li><strong style="color: #475569;">LCEC:</strong> Regularização de construção já executada perante a Capitania.</li>
                    </ul>
                </li>
                <li><strong>Defina a Classe da Embarcação:</strong>
                    <ul>
                        <li><strong>EC1 (Embarcação Classe 1):</strong> Embarcações de maior porte que exigem o pacote documental completo da NORMAM-202 (estabilidade intacta, avaria, planos estruturais e linhas).</li>
                        <li><strong>EC2 (Embarcação Classe 2):</strong> Embarcações de médio e pequeno porte com exigências simplificadas.</li>
                    </ul>
                </li>
                <li><strong>Vincule o Autor do Projeto & ART:</strong> Digite o nome do Engenheiro Projetista, número do CREA/CFT e número da ART de projeto.</li>
                <li>Clique no botão verde <strong>Salvar e Iniciar Análise</strong>.</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot mockup-dot-red"></span>
                        <span class="mockup-dot mockup-dot-yellow"></span>
                        <span class="mockup-dot mockup-dot-green"></span>
                    </div>
                    <span>Formulário de Abertura & Enquadramento Regulamentar</span>
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div class="mockup-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color: #087653;"><i class="fa-solid fa-ship"></i> Embarcação:</label>
                            <div class="mockup-panel">
                                <strong>BALSA RIO TAPAJÓS</strong> &bull; Inscrição: 021-008742-1
                            </div>
                            <small class="text-muted-contrast">👉 Armador carregado automaticamente: Tapajós Navegações Ltda</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="color: #087653;">Tipo de Processo:</label>
                            <div class="mockup-panel">
                                <strong>LC &mdash; Construção</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="color: #087653;">Classe:</label>
                            <div class="mockup-panel">
                                <strong>EC1 &bull; Completa</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 3: GESTÃO DE PRANCHAS & NORMAM-202 -->
        <section class="step-card" id="passo3">
            <div class="step-title">
                <span class="step-badge">3</span> Passo 3: Gestão de Pranchas, Cálculos e a Matriz dos 11 Itens NORMAM-202
            </div>
            <p style="line-height: 1.7;">
                Dentro do processo aberto, acesse a aba <strong>Pranchas & Cálculos</strong>. Cada arquivo enviado pelo projetista passa por auditoria e controle de revisão:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li><strong>Upload de Pranchas (PDF ou DWG):</strong> Faça o upload das pranchas arquitetônicas (Arranjo Geral, Seção Mestra, Plano de Linhas, Capacidades, Combate a Incêndio).</li>
                <li><strong>Controle de Revisões (R0, R1, R2):</strong> Toda vez que o projetista reenviar um desenho corrigido, o sistema armazena a versão anterior no histórico para auditoria.</li>
                <li><strong>Classificação Obrigatória de Cada Documento:</strong>
                    <div class="d-flex gap-2 my-2 flex-wrap">
                        <span class="badge bg-success p-2"><i class="fa-solid fa-check"></i> ACEITO</span>
                        <span class="badge bg-warning text-dark p-2"><i class="fa-solid fa-rotate"></i> SUBSTITUÍDO</span>
                        <span class="badge bg-danger p-2"><i class="fa-solid fa-xmark"></i> REJEITADO</span>
                    </div>
                    <ul>
                        <li><strong class="text-success">Aceito:</strong> O documento atende integralmente à NORMAM e às regras de cálculo.</li>
                        <li><strong class="text-warning">Substituído:</strong> Prancha superada por uma nova revisão mais recente.</li>
                        <li><strong class="text-danger">Rejeitado:</strong> O documento tem erro de cálculo ou desconformidade, gerando exigência técnica.</li>
                    </ul>
                </li>
                <li><strong>Auditoria dos 11 Itens da NORMAM-202:</strong> Confira o checklist dos 11 itens regulamentares (Linhas, Arranjo, Estrutura, Estabilidade, Borda Livre, Salvatagem, Incêndio, Esgoto, Máquinas, Elétrica e RIPEAM).</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot mockup-dot-red"></span>
                        <span class="mockup-dot mockup-dot-yellow"></span>
                        <span class="mockup-dot mockup-dot-green"></span>
                    </div>
                    <span>Matriz de Auditoria Documental &bull; NORMAM-202/DPC</span>
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="mockup-body">
                    <table class="table table-sm table-bordered mb-0 bg-white" style="font-size: 0.88rem; border: 1px solid #cbd5e1;">
                        <thead style="background: #f1f5f9; color: #087653; border-bottom: 2px solid #087653;">
                            <tr>
                                <th># Item</th>
                                <th>Prancha / Memorial</th>
                                <th>Revisão</th>
                                <th>Status da Avaliação</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>01</strong></td>
                                <td>Plano de Arranjo Geral</td>
                                <td><span class="badge bg-secondary">R1</span></td>
                                <td><span class="badge bg-success">ACEITO</span></td>
                                <td><span class="btn-demo btn-demo-info py-1 px-2"><i class="fa-solid fa-eye"></i> Visualizar</span></td>
                            </tr>
                            <tr>
                                <td><strong>02</strong></td>
                                <td>Memorial de Estabilidade Intacta</td>
                                <td><span class="badge bg-secondary">R0</span></td>
                                <td><span class="badge bg-danger">REJEITADO</span></td>
                                <td><span class="btn-demo btn-demo-warning py-1 px-2"><i class="fa-solid fa-triangle-exclamation"></i> Ver Exigência</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- PASSO 4: EXIGÊNCIAS & PARECER TÉCNICO -->
        <section class="step-card" id="passo4">
            <div class="step-title">
                <span class="step-badge">4</span> Passo 4: Como Registrar Exigências e Publicar o Parecer Técnico Naval
            </div>
            <p style="line-height: 1.7;">
                Na engenharia naval, <strong>nenhuma exigência pode ser apagada ou baixada manualmente</strong> sem que haja um parecer oficial de ciclo que justifique a decisão perante a Autoridade Marítima:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Na aba <strong>Exigências Técnicas</strong>, clique no botão <span class="btn-demo btn-demo-warning"><i class="fa-solid fa-plus"></i> Nova Exigência</span> para registrar itens a corrigir.</li>
                <li>Informe o artigo da NORMAM (ex.: <em>NORMAM-202/DPC, Cap. 02, Item 2.4 &mdash; Ângulo de Alagamento</em>) e descreva o apontamento com precisão matemática.</li>
                <li><strong>Ciclo Documental:</strong> Quando o projetista enviar a memória de cálculo revisada, altere a prancha para <em>Aceito</em> e clique em <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-file-contract"></i> Emitir Parecer de Ciclo</span>.</li>
                <li>No editor do parecer, declare expressamente o saneamento da exigência e a conformidade técnica.</li>
                <li>Clique em <strong>Publicar Parecer Técnico</strong>. O documento recebe número oficial (ex.: <code>AM-RAP-16/26</code>), carimbo com hash SHA-256 e entra no dossiê oficial da Capitania!</li>
            </ol>

            <div class="callout-tip">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> Regra de Fé Pública Naval:</strong>
                Uma vez publicado, o parecer técnico é imutável. Qualquer nova manifestação técnica posterior gera um novo parecer de revisão sequencial (ex.: Parecer Complementar R1).
            </div>
        </section>

        <!-- PASSO 5: HOMOLOGAÇÃO DE VISTORIAS DE CAMPO -->
        <section class="step-card" id="passo5">
            <div class="step-title">
                <span class="step-badge">5</span> Passo 5: Como Homologar Laudos de Vistoria de Campo
            </div>
            <p style="line-height: 1.7;">
                O Analista Naval tem papel fiscalizador sobre o trabalho dos vistoriadores de campo. Todo laudo pericial precisa ser homologado pela Mesa Técnica antes de liberar a embarcação:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No menu superior ou lateral, acesse a fila de <strong>Aprovação de Vistorias</strong>.</li>
                <li>Abra o relatório técnico submetido pelo vistoriador.</li>
                <li><strong>Audite as Fotos Técnicas:</strong>
                    <ul>
                        <li>Costado da embarcação com nome e porto de inscrição nítidos.</li>
                        <li>Selo e data de validade dos extintores de incêndio da praça de máquinas.</li>
                        <li>Dotação física de coletes salva-vidas e luzes do RIPEAM.</li>
                    </ul>
                </li>
                <li><strong>Tome uma das 4 Decisões Regulamentares:</strong>
                    <div class="row g-2 my-3">
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center" style="background: #ecfdf5; border: 1px solid #10b981;">
                                <strong class="text-success d-block mb-1">APROVADA</strong>
                                <small style="color: #064e3b;">Tudo conforme. Libera certificado definitivo.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center" style="background: #fffbeb; border: 1px solid #f59e0b;">
                                <strong class="text-warning d-block mb-1">APROVADA C/ EXIGÊNCIAS</strong>
                                <small style="color: #78350f;">Exigências comuns. Prazo de 60/90 dias.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center" style="background: #fef2f2; border: 1px solid #ef4444;">
                                <strong class="text-danger d-block mb-1">RETORNO A/S</strong>
                                <small style="color: #991b1b;">Exigência impeditiva. Navio retido até retorno.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center" style="background: #f8fafc; border: 1px solid #64748b;">
                                <strong class="text-secondary d-block mb-1">REPROVADA</strong>
                                <small style="color: #334155;">Não atende requisitos mínimos de segurança.</small>
                            </div>
                        </div>
                    </div>
                </li>
                <li>Clique em <strong>Confirmar Homologação Técnica</strong>.</li>
            </ol>
        </section>

        <!-- PASSO 6: CERTIFICADOS ESTATUTÁRIOS -->
        <section class="step-card" id="passo6">
            <div class="step-title">
                <span class="step-badge">6</span> Passo 6: Como Emitir Certificados Estatutários (CSN, CNBL, CNARQ)
            </div>
            <p style="line-height: 1.7;">
                Com a análise do plano ou laudo de vistoria homologado, o engenheiro gera os certificados estatutários através do assistente (Wizard) em 3 etapas simples:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No card do processo homologado, clique no botão azul <span class="btn-demo btn-demo-info"><i class="fa-solid fa-stamp"></i> Emitir Certificado</span>.</li>
                <li><strong>Escolha o Modelo Estatutário:</strong>
                    <ul>
                        <li><strong style="color: #087653;">CSN:</strong> Certificado de Segurança da Navegação (Embarcações com propulsão e passageiros/carga).</li>
                        <li><strong style="color: #087653;">CNBL:</strong> Certificado Nacional de Borda Livre (Balsas, empurradores e navios mercantes).</li>
                        <li><strong style="color: #087653;">CNARQ:</strong> Certificado Nacional de Arqueação (Arqueação Bruta e Líquida).</li>
                        <li><strong style="color: #087653;">CHT:</strong> Certificado de Homologação e Teste de tração estática/tração de amarras.</li>
                    </ul>
                </li>
                <li><strong>Tipo de Validade:</strong>
                    <ul>
                        <li><strong class="text-success">Definitivo:</strong> Vigência plena regulamentar (1 a 5 anos conforme a NORMAM).</li>
                        <li><strong class="text-warning">Provisório / Condicional:</strong> Vigência de 60 ou 90 dias quando houver exigências comuns pendentes de saneamento.</li>
                    </ul>
                </li>
                <li>Revise os limites de carga, lotação máxima e área de navegação (Interior 1 ou Interior 2) e clique em <strong>Gerar Certificado Oficial</strong>.</li>
            </ol>
        </section>

        <!-- PASSO 7: ASSINATURA DIGITAL & FÉ PÚBLICA -->
        <section class="step-card" id="passo7">
            <div class="step-title">
                <span class="step-badge">7</span> Passo 7: Assinatura Digital Criptográfica na Mesa Técnica
            </div>
            <p style="line-height: 1.7;">
                Todos os pareceres, notas de cumprimento e certificados exigem a assinatura digital do responsável técnico:
            </p>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots">
                        <span class="mockup-dot mockup-dot-red"></span>
                        <span class="mockup-dot mockup-dot-yellow"></span>
                        <span class="mockup-dot mockup-dot-green"></span>
                    </div>
                    <span>Mesa de Assinaturas Digitais &bull; Validação por QR Code</span>
                    <i class="fa-solid fa-fingerprint"></i>
                </div>
                <div class="mockup-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-2" style="color: #087653;"><i class="fa-solid fa-shield-halved"></i> Fé Pública & Rastreabilidade Naval</h5>
                            <p class="text-muted-contrast small mb-2" style="line-height: 1.6;">
                                Ao clicar em <strong>Assinar Digitalmente</strong>, o sistema calcula o Hash SHA-256 do documento, grava data/hora oficial, coordenadas geográficas e carimbo de tempo. Um QR Code de validação pública é impresso no rodapé de todas as vias.
                            </p>
                            <span class="badge bg-success p-2"><i class="fa-solid fa-qrcode"></i> Validável pela Capitania dos Portos via Celular</span>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 rounded bg-white" style="border: 1px solid #cbd5e1; display: inline-block;">
                                <div style="font-family: 'Brush Script MT', cursive; font-size: 24px; color: #087653; border-bottom: 2px solid #087653; padding: 0 16px;">
                                    Eng. Naval Responsável
                                </div>
                                <div style="font-size: 9px; font-weight: 700; color: #475569; margin-top: 4px;">
                                    CREA/CFT &bull; ASSINATURA ELETRÔNICA ICP
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO: DÚVIDAS FREQUENTES (FAQ) -->
        <section class="step-card" id="faq">
            <div class="step-title">
                <i class="fa-solid fa-circle-question fs-4 me-2"></i> Perguntas Frequentes do Analista Naval (FAQ)
            </div>

            <div class="accordion faq-accordion" id="accordionFaqAnalista">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqAnOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnOne">
                            ❓ O que fazer quando uma exigência técnica for sanada pelo projetista?
                        </button>
                    </h2>
                    <div id="collapseAnOne" class="accordion-collapse collapse" data-bs-parent="#accordionFaqAnalista">
                        <div class="accordion-body">
                            Nunca apague a exigência original! Registre o recebimento da nova revisão da prancha (ex.: R1), faça o upload do arquivo revisado, classifique como <em>Aceito</em> e emita o parecer de ciclo registrando formalmente a manifestação de cumprimento. A rastreabilidade histórica é mandatória para a Capitania dos Portos.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqAnTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnTwo">
                            ❓ O Analista pode consultar o laudo e as fotos de campo do Vistoriador?
                        </button>
                    </h2>
                    <div id="collapseAnTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFaqAnalista">
                        <div class="accordion-body">
                            Sim! Todo processo de análise vinculado à embarcação permite ao Analista acessar o relatório e o PDF da vistoria técnica correspondente, inspecionando todas as evidências fotográficas em alta resolução anexadas pelo vistoriador em campo.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqAnThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnThree">
                            ❓ Qual é o prazo fatal para resposta de exigências da Capitania dos Portos?
                        </button>
                    </h2>
                    <div id="collapseAnThree" class="accordion-collapse collapse" data-bs-parent="#accordionFaqAnalista">
                        <div class="accordion-body">
                            Conforme o regulamento das Capitanias e a NORMAM-202, o prazo padrão de resposta a notas de exigência é de <strong>30 dias corridos</strong> a contar da notificação formal. O sistema monitora esse prazo no contador de dias e exibe o processo como urgente caso atinja a faixa crítica de 5 dias restantes.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Botões de Ação no Fim do Manual -->
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>analises-planos" class="btn btn-primary btn-lg px-4 me-2" style="background: #087653; border-color: #087653;">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Fila de Análise de Planos
            </a>
            <a href="<?= APP_URL ?>analises-planos/form" class="btn btn-success btn-lg px-4" style="background: #16a34a; border-color: #16a34a;">
                <i class="fa-solid fa-plus"></i> Abrir Nova Análise de Planos
            </a>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>