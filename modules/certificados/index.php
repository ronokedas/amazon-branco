<?php
/**
 * MODULO: CERTIFICADOS
 * Arquivo: index.php - Central de emissão de certificados
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

exigirAcesso('certificados');

$titulo_page = 'Certificados - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <section class="cert-hero">
        <div class="cert-hero-main">
            <span class="flow-eyebrow"><i class="fas fa-file-signature"></i> Etapa final do fluxo operacional</span>
            <h1>Emissão de Certificados</h1>
            <p>Escolha primeiro o relatório aprovado que vai alimentar o certificado. Depois selecione o modelo do documento e finalize a emissão.</p>
        </div>
        <div class="cert-hero-panel">
            <span>Próxima ação</span>
            <strong>Selecione o relatório</strong>
            <small>Use um relatório aprovado ou aprovado com exigências para seguir com a emissão.</small>
        </div>
    </section>

    <div class="cert-workspace">
        <aside class="cert-flow-sidebar">
            <div class="cert-flow-title">
                <i class="fas fa-route"></i>
                <div>
                    <strong>Fluxo até finalizar</strong>
                    <span>Visão rápida das etapas</span>
                </div>
            </div>

            <ol class="cert-step-list">
                <li class="is-active">
                    <span>01</span>
                    <div>
                        <strong>Relatório</strong>
                        <small>Escolher um relatório aprovado.</small>
                    </div>
                </li>
                <li>
                    <span>02</span>
                    <div>
                        <strong>Modelo</strong>
                        <small>Escolher CSN, CNBL ou CNARQ.</small>
                    </div>
                </li>
                <li>
                    <span>03</span>
                    <div>
                        <strong>Tipo</strong>
                        <small>Definir provisório, condicional ou definitivo quando permitido.</small>
                    </div>
                </li>
                <li>
                    <span>04</span>
                    <div>
                        <strong>Gerar certificado</strong>
                        <small>Escolher assinatura, validade e local de emissão.</small>
                    </div>
                </li>
            </ol>
        </aside>

        <section class="cert-main-panel">
            <div class="cert-panel-header">
                <div>
                    <h2>Escolha o relatório aprovado</h2>
                    <p>Mostramos os 10 relatórios mais recentes. Para encontrar um mais antigo, pesquise por embarcação, nº do relatório ou inscrição.</p>
                </div>
            </div>

            <div class="cert-report-select">
                <label for="busca_relatorio_certificado">Relatório de vistoria <span class="text-danger">*</span></label>
                <div class="cert-search-box">
                    <i class="fas fa-search"></i>
                    <input type="search"
                           id="busca_relatorio_certificado"
                           class="form-control"
                           placeholder="Pesquise por nome da embarcação, nº do relatório ou inscrição..."
                           autocomplete="off">
                    <button type="button" class="btn btn-primary btn-sm" id="abrirRelatoriosCertificado">
                        <i class="fas fa-list-check"></i> Ver recentes
                    </button>
                </div>
                <div class="cert-search-results" id="resultadosRelatorioCertificado"></div>
            </div>

        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('busca_relatorio_certificado');
    const botaoRecentes = document.getElementById('abrirRelatoriosCertificado');
    const resultados = document.getElementById('resultadosRelatorioCertificado');
    let timerBusca = null;

    const mostrarMensagem = (mensagem, tipo = 'empty') => {
        resultados.innerHTML = `<div class="cert-search-${tipo}">${mensagem}</div>`;
    };

    const montarLinha = (item) => {
        const embarcacao = item.embarcacao || item.nome_embarcacao || 'Embarcação sem nome';
        const relatorio = item.numero || item.numero_relatorio || item.relatorio_numero || 'Sem número';
        const inscricao = item.numero_inscricao || 'Inscrição não informada';
        const status = item.status || item.relatorio_status || '';
        const data = item.data_vistoria_formatada || item.data_vistoria || '';
        const destino = `<?= APP_URL ?>documentacao/novo_certificado?agendamento_id=${encodeURIComponent(item.agendamento_id || '')}`;

        const linha = document.createElement('button');
        linha.type = 'button';
        linha.className = 'cert-search-result';
        linha.disabled = !item.agendamento_id;
        linha.innerHTML = `
            <strong>${escapeHtml(embarcacao)}</strong>
            <span>${escapeHtml(relatorio)} · ${escapeHtml(inscricao)}</span>
            <small>${escapeHtml(data)} ${status ? '· ' + escapeHtml(status) : ''}</small>
        `;
        linha.addEventListener('click', () => {
            if (item.agendamento_id) {
                window.location.href = destino;
            }
        });
        return linha;
    };

    const escapeHtml = (valor) => String(valor ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const buscarRelatorios = async (termo = '', recentes = false) => {
        const params = new URLSearchParams();
        if (recentes || !termo.trim()) {
            params.set('recentes', '1');
        } else {
            params.set('q', termo.trim());
        }

        mostrarMensagem('Carregando relatórios aprovados...', 'loading');

        try {
            const resposta = await fetch(`<?= APP_URL ?>ajax/busca_relatorios_aprovados.php?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const dados = await resposta.json();

            const lista = Array.isArray(dados) ? dados : (Array.isArray(dados.data) ? dados.data : []);

            if (lista.length === 0) {
                mostrarMensagem('Nenhum relatório aprovado encontrado.');
                return;
            }

            resultados.innerHTML = '';
            lista.slice(0, 10).forEach((item) => resultados.appendChild(montarLinha(item)));
        } catch (erro) {
            mostrarMensagem('Não foi possível carregar os relatórios agora.');
        }
    };

    botaoRecentes?.addEventListener('click', () => {
        if (input) input.value = '';
        buscarRelatorios('', true);
    });
    input?.addEventListener('input', () => {
        clearTimeout(timerBusca);
        timerBusca = setTimeout(() => buscarRelatorios(input.value), 300);
    });

    buscarRelatorios('', true);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
