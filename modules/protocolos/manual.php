<?php
/**
 * Manual Interativo do Usuário - Módulo de Protocolos e Dossiês Navais
 * Arquivo: modules/protocolos/manual.php
 * Criado especialmente para usuários leigos com passo a passo didático, ilustrado e autoexplicativo.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
protocoloExigirAcesso();

$titulo_page = 'Manual do Usuário - Protocolos e Dossiês Navais';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* Estilos Exclusivos do Manual Didático */
.manual-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 60px;
}
.manual-hero {
    background: linear-gradient(135deg, rgba(8, 118, 83, 0.3) 0%, rgba(7, 31, 27, 0.8) 100%);
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
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(86, 224, 173, 0.25);
    border-radius: 10px;
    color: var(--text-primary, #ffffff);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s ease;
}
.manual-nav-pill:hover {
    background: rgba(86, 224, 173, 0.15);
    border-color: var(--accent, #56e0ad);
    color: var(--accent, #56e0ad);
    transform: translateY(-2px);
}
.step-card {
    background: var(--bg-surface, #0c2b24);
    border: 1px solid rgba(86, 224, 173, 0.2);
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
    padding: 20px;
}
.callout-box {
    background: rgba(86, 224, 173, 0.08);
    border-left: 4px solid var(--accent, #56e0ad);
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.92rem;
}
.callout-tip {
    background: rgba(245, 158, 11, 0.08);
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 0.92rem;
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
}
.btn-demo-primary { background: #087653; color: #fff; }
.btn-demo-success { background: #16a34a; color: #fff; }
.btn-demo-warning { background: #eab308; color: #042019; }
.btn-demo-secondary { background: #334155; color: #fff; }

@media print {
    .sidebar, .top-header, .manual-nav-pill, #btn-imprimir-manual {
        display: none !important;
    }
    .conteudo-principal {
        margin: 0 !important;
        padding: 0 !important;
    }
    .step-card {
        page-break-inside: avoid;
        border: 1px solid #ccc !important;
        color: #000 !important;
        background: #fff !important;
    }
    .mockup-window {
        background: #f8fafc !important;
        color: #000 !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<main class="conteudo-principal">
    <div class="manual-container">
        <!-- Banner Principal de Apresentação -->
        <div class="manual-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge" style="background: var(--accent, #56e0ad); color: #042019; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; margin-bottom: 12px; display: inline-block;">
                        <i class="fa-solid fa-graduation-cap"></i> GUIA RÁPIDO PARA LEIGOS
                    </span>
                    <h1 class="text-white fw-bold mb-2" style="font-size: 2rem;">
                        Manual do Usuário: Protocolos e Dossiês Navais
                    </h1>
                    <p class="text-secondary lead mb-0" style="max-width: 820px; font-size: 1.05rem;">
                        Aprenda passo a passo como juntar os documentos de uma embarcação, assinar o ofício formal da secretária e enviar tudo por e-mail para a Capitania dos Portos em poucos cliques.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary d-inline-flex align-items-center gap-2" id="btn-imprimir-manual" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Imprimir Manual
                    </button>
                    <a href="<?= APP_URL ?>protocolos" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Voltar ao Sistema
                    </a>
                </div>
            </div>
        </div>

        <!-- Menu Rápido com Navegação Suave -->
        <div class="card mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 12px;">
            <div class="card-body p-3">
                <div class="small fw-bold text-accent mb-2">
                    <i class="fa-solid fa-list-ol"></i> ÍNDICE RÁPIDO DO MANUAL (CLIQUE PARA IR DIRETO AO PASSO):
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#o-que-e" class="manual-nav-pill">
                        <i class="fa-solid fa-circle-question text-info"></i> O que é um Dossiê?
                    </a>
                    <a href="#passo1" class="manual-nav-pill">
                        <i class="fa-solid fa-folder-plus text-accent"></i> Passo 1: Abrir Novo Dossiê
                    </a>
                    <a href="#passo2" class="manual-nav-pill">
                        <i class="fa-solid fa-file-signature text-warning"></i> Passo 2: Assinar o Ofício
                    </a>
                    <a href="#passo3" class="manual-nav-pill">
                        <i class="fa-solid fa-file-pdf text-danger"></i> Passo 3: Visualizar Ofício e PDF Unificado
                    </a>
                    <a href="#passo4" class="manual-nav-pill">
                        <i class="fa-solid fa-paper-plane text-success"></i> Passo 4: Enviar p/ Capitania por E-mail
                    </a>
                    <a href="#passo5" class="manual-nav-pill">
                        <i class="fa-solid fa-stamp text-info"></i> Passo 5: Acompanhar e Registrar SISAP
                    </a>
                    <a href="#passo6" class="manual-nav-pill">
                        <i class="fa-solid fa-building-flag text-accent"></i> Passo 6: Cadastrar Capitanias
                    </a>
                    <a href="#faq" class="manual-nav-pill">
                        <i class="fa-solid fa-circle-nodes text-warning"></i> Dúvidas Frequentes
                    </a>
                </div>
            </div>
        </div>

        <!-- SEÇÃO: O que é um Dossiê? -->
        <section class="step-card" id="o-que-e">
            <div class="step-title">
                <i class="fa-solid fa-circle-question fs-4 me-2"></i> O que é o Módulo de Protocolos e Dossiês?
            </div>
            <p style="font-size: 1rem; line-height: 1.7;">
                Pense no <strong>Dossiê</strong> como uma <strong>pasta digital inteligente</strong> da embarcação. Sempre que a Amazon Naval precisa protocolar documentos na <strong>Capitania dos Portos</strong>, <strong>Delegacia</strong> ou <strong>Agência da Marinha</strong> (por exemplo, após uma vistoria aprovada ou aprovação de projeto técnico de engenharia), é neste módulo que tudo acontece.
            </p>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-accent d-block mb-1"><i class="fa-solid fa-file-shield"></i> 1. Ofício Oficial em 1 Folha</strong>
                        <small class="text-secondary">O sistema gera o modelo oficial idêntico ao exigido pela Capitania dos Portos (com timbrado, dados da autoridade A/C, normas NORMAM e assinatura da secretária).</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-accent d-block mb-1"><i class="fa-solid fa-file-circle-check"></i> 2. Dossiê Completo Unificado</strong>
                        <small class="text-secondary">O sistema junta automaticamente o Ofício na folha 1 e em seguida todos os relatórios de vistoria e certificados emitidos em um único arquivo PDF.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-accent d-block mb-1"><i class="fa-solid fa-paper-plane"></i> 3. Envio por E-mail em 1 Clique</strong>
                        <small class="text-secondary">Você não precisa baixar o PDF no computador para depois abrir o e-mail: o próprio sistema envia direto para o e-mail oficial da Capitania com cópia para quem você quiser.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 1: Abrir Novo Dossiê -->
        <section class="step-card" id="passo1">
            <div class="step-title">
                <span class="step-badge">1</span> Passo 1: Como Abrir um Novo Dossiê para a Embarcação
            </div>
            <p>Para começar um processo de protocolo para um barco, siga estas 3 etapas simples:</p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No menu lateral esquerdo do sistema, clique em <strong>Protocolos & Dossiês</strong>.</li>
                <li>No topo da tela que se abrir, clique no botão verde <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-plus"></i> Novo Dossiê</span>.</li>
                <li>Na tela de abertura, preencha apenas estes dados básicos:
                    <ul>
                        <li><strong>Embarcação:</strong> Escolha o barco na lista fixa ou digite o nome no campo de busca.</li>
                        <li><strong>Cliente / Armador:</strong> É preenchido automaticamente ao escolher o barco!</li>
                        <li><strong>Capitania / Unidade de Destino:</strong> Escolha a Capitania (ex.: <em>Capitania dos Portos da Amazônia Oriental - CPAOR Belém</em>).</li>
                        <li><strong>Assunto:</strong> Use os atalhos rápidos de 1 clique logo abaixo do campo (ex.: <em>"Apresentação de Relatório de Vistoria"</em> ou <em>"Aprovação de Planos NORMAM-202"</em>).</li>
                    </ul>
                </li>
                <li>Clique no botão verde <strong>Salvar e Abrir Dossiê</strong> no final da página.</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots"><span class="mockup-dot"></span><span class="mockup-dot"></span><span class="mockup-dot"></span></div>
                    <span>Tela de Abertura do Dossiê - Formulário Simplificado</span>
                    <i class="fa-solid fa-window-maximize"></i>
                </div>
                <div class="mockup-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-accent fw-bold"><i class="fa-solid fa-ship"></i> Embarcação:</label>
                            <div class="p-2 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid var(--accent, #56e0ad); color: #fff;">
                                <strong>POSTO MACEDÃO I</strong> (Registro: 0211012688)
                            </div>
                            <small class="text-muted">👉 O sistema já carrega o armador e todos os laudos vinculados!</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-accent fw-bold"><i class="fa-solid fa-landmark"></i> Capitania de Destino:</label>
                            <div class="p-2 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                Capitania dos Portos da Amazônia Oriental (Belém/PA)
                            </div>
                            <small class="text-muted">👉 Já traz o e-mail oficial e o nome do Capitão dos Portos!</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="callout-box">
                <strong class="text-accent"><i class="fa-solid fa-lightbulb"></i> Dica de Ouro:</strong>
                Se você já estiver na tela de <strong>Vistorias Técnicas</strong> ou <strong>Análise de Planos</strong>, existe um botão de atalho que já abre o dossiê com tudo pré-preenchido, sem você precisar digitar nada!
            </div>
        </section>

        <!-- PASSO 2: Assinar o Ofício Oficial -->
        <section class="step-card" id="passo2">
            <div class="step-title">
                <span class="step-badge">2</span> Passo 2: Como Assinar o Ofício Oficial de Encaminhamento
            </div>
            <p>
                O <strong>Ofício</strong> é o documento formal timbrado que vai na frente de todos os laudos técnicos. Ele precisa ser assinado pela <strong>Secretária</strong> (ex.: Thainara Barros) ou pelo Engenheiro/Responsável Técnico.
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Dentro da página do Dossiê, olhe no cabeçalho superior direito.</li>
                <li>Se o ofício ainda não foi assinado, você verá um botão amarelo piscante: <span class="btn-demo btn-demo-warning"><i class="fa-solid fa-file-signature"></i> Assinar Digitalmente</span>. Clique nele!</li>
                <li>Uma janela bonita se abrirá na sua tela:
                    <ul>
                        <li>O <strong>Número do Ofício</strong> já vem sugerido automaticamente (ex.: <em>AM-OF048/2026</em>).</li>
                        <li>A autoridade de destino (A/C) já vem preenchida com o nome oficial do Comandante da Capitania.</li>
                        <li>O responsável já vem selecionado como <strong>Thainara Barros — Secretária</strong>.</li>
                    </ul>
                </li>
                <li>Você pode usar a assinatura cadastrada no sistema ou desenhar a rubrica na hora no quadro de assinatura com o mouse ou o dedo no celular/tablet.</li>
                <li>Clique no botão verde <strong>Confirmar Assinatura Oficial</strong>.</li>
            </ol>

            <div class="callout-box">
                <strong class="text-accent"><i class="fa-solid fa-shield-halved"></i> Fé Pública e Segurança:</strong>
                Assim que você assina, o sistema carimba o documento com data, hora e código de validação criptográfica (Hash SHA-256), tornando o documento oficial e protegido contra qualquer alteração indevida.
            </div>
        </section>

        <!-- PASSO 3: Visualizar Ofício e PDF Unificado -->
        <section class="step-card" id="passo3">
            <div class="step-title">
                <span class="step-badge">3</span> Passo 3: Como Visualizar o Ofício e o Dossiê Completo
            </div>
            <p>
                Antes de mandar para a Marinha, você pode conferir os documentos gerados pelo sistema. No topo do dossiê existe o botão azul com opções de visualização:
            </p>

            <div class="row g-3 my-2">
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-white d-block mb-1">
                            <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-file-pdf"></i> Visualizar Ofício (PDF)</span>
                        </strong>
                        <p class="text-secondary small mb-0 mt-2">
                            Abre em uma nova aba exatamente a <strong>Página 1 oficial</strong> (Folha do Ofício timbrado com tabela 4x2, destinatário A/C, lista dos documentos anexados e assinatura formal da secretária).
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-white d-block mb-1">
                            <span class="btn-demo btn-demo-primary"><i class="fa-solid fa-file-circle-check"></i> Dossiê Completo Unificado</span>
                        </strong>
                        <p class="text-secondary small mb-0 mt-2">
                            É o modelo que a Capitania mais gosta! O sistema junta em <strong>um único arquivo PDF</strong> o Ofício na primeira folha seguido pelo Relatório de Vistoria, Parecer Técnico de Engenharia e Certificados emitidos (CSN, CNARQ, etc.).
                        </p>
                    </div>
                </div>
            </div>

            <div class="callout-tip">
                <strong style="color: #f59e0b;"><i class="fa-solid fa-triangle-exclamation"></i> Importante:</strong>
                Você pode abrir esses PDFs para conferência, baixar no seu computador ou imprimir caso o cliente prefira levar uma via física na Capitania!
            </div>
        </section>

        <!-- PASSO 4: Enviar por E-mail à Capitania -->
        <section class="step-card" id="passo4">
            <div class="step-title">
                <span class="step-badge">4</span> Passo 4: Como Enviar os Documentos por E-mail para a Capitania (1 Clique)
            </div>
            <p>
                Esta é a parte mais mágica e prática do sistema! Você não precisa abrir o Gmail ou Outlook para mandar os arquivos. O ERP faz tudo sozinho:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>No cabeçalho do Dossiê, clique no botão verde chamativo: <span class="btn-demo btn-demo-success"><i class="fa-solid fa-paper-plane"></i> Enviar p/ Capitania por E-mail</span>.</li>
                <li>Uma tela de envio se abrirá com quase tudo já preenchido:
                    <ul>
                        <li><strong>E-mail Oficial da Capitania:</strong> O sistema já coloca o e-mail cadastrado da Capitania (ex.: <em>cpaor.protocolo@marinha.mil.br</em>). Se quiser mudar ou corrigir, você pode!</li>
                        <li><strong>E-mails em Cópia (CC):</strong> Quer que o cliente/armador ou o despachante receba uma cópia? Basta clicar nos botões de atalho:
                            <span class="badge bg-info text-dark">+ Cliente</span> ou <span class="badge bg-info text-dark">+ Cópia Secretaria Amazon</span>.</li>
                        <li><strong>Assunto:</strong> Já vem padronizado com o número do Ofício e o nome do barco.</li>
                        <li><strong>Documento Anexo:</strong> Deixe marcada a opção recomendada: <em>"Dossiê Completo Unificado (Ofício + Laudos e Certificados em PDF único)"</em>.</li>
                        <li><strong>Mensagem:</strong> Já vem redigida uma mensagem formal e educada nos padrões da Marinha.</li>
                    </ul>
                </li>
                <li>Clique no botão verde: <strong>Disparar E-mail com Documentos</strong>.</li>
            </ol>

            <div class="mockup-window">
                <div class="mockup-header">
                    <div class="mockup-dots"><span class="mockup-dot"></span><span class="mockup-dot"></span><span class="mockup-dot"></span></div>
                    <span>Janela de Envio por E-mail - Simples e Direta</span>
                    <i class="fa-solid fa-envelope-circle-check text-accent"></i>
                </div>
                <div class="mockup-body">
                    <div class="mb-3">
                        <label class="text-accent fw-bold small"><i class="fa-solid fa-at"></i> E-mail Oficial da Capitania:</label>
                        <div class="p-2 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid var(--accent, #56e0ad); color: #56e0ad; font-family: monospace;">
                            cpaor.protocolo@marinha.mil.br
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-accent fw-bold small"><i class="fa-solid fa-copy"></i> Cópias (CC):</label>
                        <div class="p-2 rounded text-secondary" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); font-family: monospace;">
                            cliente.armador@email.com, amazoncertificados@gmail.com
                        </div>
                    </div>
                    <div class="p-2 rounded text-center" style="background: rgba(8, 118, 83, 0.2); border: 1px dashed var(--accent, #56e0ad);">
                        <i class="fa-solid fa-paperclip text-accent me-1"></i>
                        <strong class="text-white">Anexo Automático:</strong> Dossie_Unificado_AM-OF048_2026.pdf (PDF de Alta Qualidade)
                    </div>
                </div>
            </div>

            <div class="callout-box">
                <strong class="text-accent"><i class="fa-solid fa-clock-rotate-left"></i> Registro Automático na Linha do Tempo:</strong>
                Assim que o e-mail é enviado, o sistema atualiza o status do dossiê para <strong>"Enviado ao Órgão"</strong> e registra na Linha do Tempo o dia, o horário exato e quem fez o envio. Tudo fica gravado para segurança da empresa.
            </div>
        </section>

        <!-- PASSO 5: Acompanhar e Registrar SISAP -->
        <section class="step-card" id="passo5">
            <div class="step-title">
                <span class="step-badge">5</span> Passo 5: O que Fazer Depois do Envio (Acompanhar e Notificar)
            </div>
            <p>
                Após o envio por e-mail, existem duas ações simples para manter o cliente e a empresa informados:
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-accent d-block mb-1">
                            <i class="fa-brands fa-whatsapp text-success fs-5 me-1"></i> Avisar o Cliente no WhatsApp
                        </strong>
                        <p class="text-secondary small mb-2">
                            No topo da tela do dossiê, clique no botão verde <strong>Notificar Cliente</strong>. O sistema abre o WhatsApp com uma mensagem pronta avisando ao armador que os documentos foram protocolados na Capitania.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                        <strong class="text-accent d-block mb-1">
                            <i class="fa-solid fa-stamp text-info fs-5 me-1"></i> Registrar Número SISAP da Marinha
                        </strong>
                        <p class="text-secondary small mb-2">
                            Quando a Capitania responder confirmando o recebimento ou gerar o número de processo no sistema SISAP, vá na aba <strong>Trâmite Oficial</strong> e anote o número do processo para consulta futura.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PASSO 6: Cadastrar Capitanias -->
        <section class="step-card" id="passo6">
            <div class="step-title">
                <span class="step-badge">6</span> Passo 6: Como Cadastrar ou Atualizar as Capitanias e Delegacias
            </div>
            <p>
                Se a Capitania mudar de e-mail ou se você precisar cadastrar uma nova Delegacia ou Agência Fluvial:
            </p>

            <ol style="line-height: 1.8; font-size: 1rem;">
                <li>Na tela principal de Protocolos, clique no botão <span class="btn-demo btn-demo-secondary"><i class="fa-solid fa-building-flag"></i> Capitanias & Apoio</span>.</li>
                <li>Na aba <strong>Capitanias & Órgãos</strong>, você verá a lista de todas as unidades já cadastradas (Belém, Manaus, Santana, Santarém, etc.).</li>
                <li>Para alterar uma existente, clique no botão <strong>Editar</strong> ao lado dela.</li>
                <li>Para criar uma nova, preencha o formulário rápido com:
                    <ul>
                        <li><strong>Nome do Órgão:</strong> ex.: <em>Capitania Fluvial de Santarém</em>.</li>
                        <li><strong>Autoridade Titular (A/C no Ofício):</strong> Nome do Comandante ou Capitão dos Portos (ex.: <em>Alexandre Batista Pimentel</em>).</li>
                        <li><strong>Cargo da Autoridade:</strong> ex.: <em>Capitão de Mar e Guerra</em> ou <em>Capitão de Corveta</em>.</li>
                        <li><strong>E-mail Oficial:</strong> O e-mail do protocolo daquela cidade.</li>
                        <li><strong>Cidade e UF:</strong> A localidade da unidade.</li>
                    </ul>
                </li>
                <li>Clique em <strong>Salvar Unidade Marítima</strong>. Pronto! Na próxima vez que fizer um ofício, tudo virá automático!</li>
            </ol>
        </section>

        <!-- SEÇÃO: Dúvidas Frequentes (FAQ) -->
        <section class="step-card" id="faq">
            <div class="step-title">
                <i class="fa-solid fa-circle-question fs-4 me-2"></i> Perguntas Frequentes (Tire suas Dúvidas)
            </div>

            <div class="accordion" id="accordionFaq">
                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 8px;">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ O que fazer se a Capitania apontar uma exigência técnica?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionFaq">
                        <div class="accordion-body text-secondary small" style="line-height: 1.6;">
                            Vá na aba <strong>Trâmite Oficial</strong> do dossiê e mude a situação para <strong>"Em Exigência"</strong>. O sistema alertará a equipe técnica e criará o histórico para responder a exigência e protocolar o cumprimento.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 8px;">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ Posso enviar documentos para mais de uma Capitania?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionFaq">
                        <div class="accordion-body text-secondary small" style="line-height: 1.6;">
                            Sim! Cada dossiê é vinculado a uma Capitania principal, mas na janela de envio por e-mail você pode colocar quantos e-mails de cópia quiser no campo <strong>E-mails em Cópia (CC)</strong>, separados por vírgula.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 8px;">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ Onde vejo se o e-mail realmente foi entregue?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionFaq">
                        <div class="accordion-body text-secondary small" style="line-height: 1.6;">
                            Na aba <strong>Linha do Tempo</strong> do próprio dossiê, aparece um card verde com o título <strong>"Envio Oficial por E-mail à Capitania"</strong> com data, hora, quem enviou, para qual e-mail foi e o arquivo anexado.
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(86, 224, 173, 0.2); border-radius: 8px;">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="background: transparent; color: #fff; font-weight: 600;">
                            ❓ E se o cliente entregou um documento físico original (ex.: TIE ou Documento de Registro)?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionFaq">
                        <div class="accordion-body text-secondary small" style="line-height: 1.6;">
                            Vá na aba <strong>Custódia de Originais</strong>. Ao registrar a entrada do documento, marque a opção <em>"Requer devolução ao cliente"</em>. O sistema manterá o documento em alerta de custódia e gerará um recibo com link para o cliente assinar no celular quando você devolver o original!
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Botões de Ação no Fim do Manual -->
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>protocolos" class="btn btn-primary btn-lg px-4 me-2">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Lista de Protocolos
            </a>
            <a href="<?= APP_URL ?>protocolos/form" class="btn btn-success btn-lg px-4" style="background: #087653; border-color: #087653;">
                <i class="fa-solid fa-plus"></i> Abrir um Novo Dossiê Agora
            </a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
