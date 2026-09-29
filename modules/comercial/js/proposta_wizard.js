/**
 * Lógica do Wizard de Propostas Comerciais
 * ERP Sistema Certificadora Amazon
 */

// ============ ESTADO GLOBAL ============
let clienteSelecionadoData = null;
let responsavelFechamentoNomeData = '';
let responsavelFechamentoTelefoneData = '';
let embarcacoesCarregadas = []; // { id, nome, registro }
let embarcacaoSelecionadaId = null;
let servicosSelecionadosPorEmbarcacao = {};
let clientePasso2CarregadoId = null;

// ============ NAVEGAÇÃO DO WIZARD ============
function temServicoSelecionado() {
    return Object.values(servicosSelecionadosPorEmbarcacao).some(servicos => Object.keys(servicos).length > 0);
}

function atualizarEstadoAvancoServicos() {
    const possuiServico = temServicoSelecionado();
    const financeiroValido = validarDescontoPercentual();
    const botao = document.getElementById('btnPasso2');
    const aviso = document.getElementById('avisoServicosObrigatorios');
    if (botao) {
        botao.disabled = !possuiServico || !financeiroValido;
        botao.title = !possuiServico
            ? 'Selecione pelo menos um serviço para continuar'
            : (!financeiroValido ? 'Corrija o desconto percentual para continuar' : '');
    }
    if (aviso) aviso.style.display = possuiServico ? 'none' : 'block';
}

function irParaPasso(numero) {
    if (numero === 3 && !temServicoSelecionado()) {
        atualizarEstadoAvancoServicos();
        return;
    }
    if (numero === 3 && !validarDescontoPercentual(true)) {
        atualizarEstadoAvancoServicos();
        document.getElementById('descontoGlobalDisplay')?.focus();
        return;
    }

    document.querySelectorAll('.wizard-panel').forEach(p => p.style.display = 'none');
    document.getElementById('passo' + numero).style.display = 'block';

    // Atualiza stepper
    document.querySelectorAll('.wizard-step').forEach(step => {
        const s = parseInt(step.dataset.step);
        step.classList.remove('active');
        step.style.opacity = (s <= numero) ? '1' : '0.5';
        const numEl = step.querySelector('.step-number');
        const lblEl = step.querySelector('.step-label');
        if (s <= numero) {
            numEl.style.background = 'var(--cor-destaque)';
            numEl.style.color = '#fff';
            lblEl.style.color = 'var(--cor-destaque)';
            lblEl.style.fontWeight = '600';
        } else {
            numEl.style.background = 'var(--cor-borda)';
            numEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.fontWeight = '500';
        }
        if (s === numero) {
            step.classList.add('active');
            step.style.borderBottomColor = 'var(--cor-destaque)';
        } else {
            step.style.borderBottomColor = 'transparent';
        }
    });

    // Ações específicas
    if (numero === 2) carregarPasso2();
    if (numero === 3) montarRevisao();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============ PASSO 1: CLIENTE ============
function filtrarClientes() {
    const termo = document.getElementById('buscaClienteWizard').value.toLowerCase();
    document.querySelectorAll('.cliente-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(termo) ? 'flex' : 'none';
    });
}

function clienteSelecionado(radio) {
    document.querySelectorAll('.cliente-card').forEach(c => {
        c.classList.remove('is-selected');
        c.style.borderColor = 'var(--cor-borda)';
        c.style.background = 'var(--cor-fundo)';
    });
    const card = radio.closest('.cliente-card');
    card.classList.add('is-selected');
    card.style.borderColor = 'var(--cor-destaque)';
    card.style.background = 'rgba(46,204,113,0.08)';

    clienteSelecionadoData = {
        id: radio.value,
        nome: radio.dataset.nome,
        perfil: radio.dataset.perfil,
        cpfcnpj: radio.dataset.cpfcnpj
    };
    document.getElementById('dadosCliente').value = JSON.stringify(clienteSelecionadoData);
    atualizarPasso1();
    embarcacoesCarregadas = [];
    embarcacaoSelecionadaId = null;
    servicosSelecionadosPorEmbarcacao = {};
    clientePasso2CarregadoId = null;
    atualizarEstadoAvancoServicos();
}

function atualizarPasso1() {
    responsavelFechamentoNomeData = document.getElementById('responsavel_fechamento_nome')?.value?.trim() || '';
    responsavelFechamentoTelefoneData = document.getElementById('responsavel_fechamento_telefone')?.value?.trim() || '';
    const btnPasso1 = document.getElementById('btnPasso1');
    if (btnPasso1) {
        btnPasso1.disabled = !clienteSelecionadoData || (typeof ESCRITORIO_DISPONIVEL !== 'undefined' && !ESCRITORIO_DISPONIVEL);
    }
}

function formatarTelefoneResponsavel(input) {
    const numeros = input.value.replace(/\D/g, '').slice(0, 11);
    if (!numeros) {
        input.value = '';
        return;
    }

    if (numeros.length <= 2) {
        input.value = `(${numeros}`;
        return;
    }

    const ddd = numeros.slice(0, 2);
    const telefone = numeros.slice(2);
    const tamanhoPrefixo = numeros.length === 11 ? 5 : 4;
    const prefixo = telefone.slice(0, tamanhoPrefixo);
    const sufixo = telefone.slice(tamanhoPrefixo);
    input.value = `(${ddd}) ${prefixo}${sufixo ? '-' + sufixo : ''}`;
}

// ============ PASSO 2: SERVIÇOS POR EMBARCAÇÃO ============
function carregarPasso2(forcar = false) {
    if (!clienteSelecionadoData) return;

    if (!forcar && clientePasso2CarregadoId === clienteSelecionadoData.id && embarcacoesCarregadas.length > 0) {
        document.getElementById('passo2ClienteNome').textContent = clienteSelecionadoData.nome;
        construirGradeServicos(embarcacoesCarregadas);
        return;
    }

    document.getElementById('paso2Loading').style.display = 'block';
    document.getElementById('paso2Content').style.display = 'none';
    document.getElementById('paso2Vazio').style.display = 'none';
    document.getElementById('totaisPainel').style.display = 'none';
    document.getElementById('passo2ClienteNome').textContent = clienteSelecionadoData.nome;

    let url = (typeof APP_URL !== 'undefined' ? APP_URL : '') + 'comercial/propostas/actions?action=embarcacoes_cliente&cliente_id=' + encodeURIComponent(clienteSelecionadoData.id);
    if (typeof PROPOSTA_ID_EDICAO !== 'undefined' && PROPOSTA_ID_EDICAO) {
        url += '&proposta_id=' + encodeURIComponent(PROPOSTA_ID_EDICAO);
    }
    if (typeof EMBARCACAO_URL_INICIAL !== 'undefined' && EMBARCACAO_URL_INICIAL) {
        url += '&embarcacao_id=' + encodeURIComponent(EMBARCACAO_URL_INICIAL);
    }
    url += '&_t=' + Date.now();

    fetch(url, { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            document.getElementById('paso2Loading').style.display = 'none';

            if (!data.embarcacoes || data.embarcacoes.length === 0) {
                document.getElementById('paso2Vazio').style.display = 'block';
                embarcacoesCarregadas = [];
                return;
            }

            embarcacoesCarregadas = data.embarcacoes;
            const primeiraComServico = data.embarcacoes.find(emb => {
                return Object.keys(servicosSelecionadosPorEmbarcacao[emb.id] || {}).length > 0;
            });
            const embUrl = (typeof EMBARCACAO_URL_INICIAL !== 'undefined' && EMBARCACAO_URL_INICIAL)
                ? data.embarcacoes.find(e => e.id === EMBARCACAO_URL_INICIAL)
                : null;
            if (!embarcacaoSelecionadaId || !data.embarcacoes.some(e => e.id === embarcacaoSelecionadaId)) {
                embarcacaoSelecionadaId = primeiraComServico?.id || embUrl?.id || data.embarcacoes[0]?.id || null;
            }
            clientePasso2CarregadoId = clienteSelecionadoData.id;
            construirGradeServicos(data.embarcacoes);
        })
        .catch(err => {
            document.getElementById('paso2Loading').style.display = 'none';
            document.getElementById('paso2Vazio').style.display = 'block';
            document.getElementById('paso2Vazio').querySelector('p').textContent = 'Erro ao carregar embarcações.';
            console.error(err);
        });
}

let filtroSituacaoEmbarcacoesAtual = 'todas';

function construirGradeServicos(embarcacoes) {
    const container = document.getElementById('paso2Content');
    container.innerHTML = renderizarSeletorEmbarcacoes(embarcacoes) + '<div id="servicosEmbarcacaoAtual"></div>';
    document.getElementById('paso2Content').style.display = 'block';
    document.getElementById('totaisPainel').style.display = 'block';
    renderizarServicosEmbarcacaoAtual();
    atualizarTotais();
}

function renderizarSeletorEmbarcacoes(embarcacoes) {
    let totalComServicos = 0;
    embarcacoes.forEach(emb => {
        const res = obterResumoEmbarcacao(emb.id);
        if (res.qtd > 0) totalComServicos++;
    });

    let html = `
    <div class="card" style="margin-bottom: 18px;">
        <div class="card-header emb-selector-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 18px;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <i class="fas fa-ship" style="color: var(--cor-destaque); font-size: 1.15rem;"></i>
                <h3 style="margin: 0; font-size: 1.05rem; color: var(--cor-texto);">Escolha a embarcação para definir serviços</h3>
                <span class="badge" id="embContadorGeral" style="font-size: 0.82rem; padding: 4px 12px; border-radius: 20px; font-weight: 700; background: ${totalComServicos > 0 ? 'rgba(46,204,113,0.18)' : 'rgba(120,120,120,0.14)'}; color: ${totalComServicos > 0 ? 'var(--cor-destaque)' : 'var(--cor-texto-secundario)'}; border: 1px solid ${totalComServicos > 0 ? 'rgba(46,204,113,0.35)' : 'var(--cor-borda)'};">
                    <i class="fas fa-list-check" style="margin-right: 4px;"></i> ${totalComServicos} de ${embarcacoes.length} com serviços
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="carregarPasso2(true)" title="Recarregar embarcações vinculadas" style="padding: 2px 8px; font-size: 0.8rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
            ${embarcacoes.length > 3 ? `
            <div class="emb-filtro-container" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--cor-texto-secundario);"></i>
                    <input type="text" id="filtroEmbarcacoesPasso2" placeholder="Buscar embarcação..." 
                           oninput="filtrarEmbarcacoesPasso2(this.value)"
                           style="padding: 6px 10px 6px 28px; font-size: 0.85rem; border-radius: 6px; border: 1px solid var(--cor-borda); background: var(--cor-fundo); color: var(--cor-texto); width: 175px;">
                </div>
                <div class="btn-group btn-group-sm" role="group" style="display: flex; gap: 4px;">
                    <button type="button" class="btn btn-sm emb-filtro-btn ${filtroSituacaoEmbarcacoesAtual === 'todas' ? 'is-active' : ''}" data-filtro="todas" onclick="aplicarFiltroRapidoEmbarcacoes('todas', this)">Todas (${embarcacoes.length})</button>
                    <button type="button" class="btn btn-sm emb-filtro-btn ${filtroSituacaoEmbarcacoesAtual === 'com_servicos' ? 'is-active' : ''}" data-filtro="com_servicos" onclick="aplicarFiltroRapidoEmbarcacoes('com_servicos', this)">Com serviços (<span id="qtdFiltroComServicos">${totalComServicos}</span>)</button>
                    <button type="button" class="btn btn-sm emb-filtro-btn ${filtroSituacaoEmbarcacoesAtual === 'pendentes' ? 'is-active' : ''}" data-filtro="pendentes" onclick="aplicarFiltroRapidoEmbarcacoes('pendentes', this)">Pendentes (<span id="qtdFiltroPendentes">${embarcacoes.length - totalComServicos}</span>)</button>
                </div>
            </div>
            ` : ''}
        </div>
        <div class="card-body" style="padding: 16px;">
            <div class="embarcacao-selector-grid" id="embarcacaoSelectorGrid">`;

    embarcacoes.forEach(emb => {
        const resumo = obterResumoEmbarcacao(emb.id);
        const selecionada = embarcacaoSelecionadaId === emb.id;
        const temServicos = resumo.qtd > 0;

        html += `
            <button type="button" class="embarcacao-select-card ${selecionada ? 'is-selected' : ''} ${temServicos ? 'has-services' : 'no-services'}" 
                    data-emb-id="${escAttr(emb.id)}" 
                    onclick="selecionarEmbarcacaoServicos('${escAttr(emb.id)}')">
                <span class="embarcacao-select-icon ${temServicos ? 'is-configured' : ''}">
                    <i class="fas fa-ship"></i>
                    ${temServicos ? '<i class="fas fa-check-circle emb-icon-check"></i>' : ''}
                </span>
                <span class="embarcacao-select-main">
                    <strong title="${escAttr(emb.nome)}">${esc(emb.nome)}</strong>
                    <small>${emb.registro ? esc(emb.registro) : 'Sem registro informado'}</small>
                </span>
                <span class="embarcacao-select-summary">
                    <span class="emb-status-pill ${temServicos ? 'pill-success' : 'pill-muted'}" id="embPill_${escAttr(emb.id)}">
                        ${temServicos ? '<i class="fas fa-check"></i> <b>' + resumo.qtd + ' serviço(s)</b>' : '<i class="far fa-circle"></i> Sem serviços'}
                    </span>
                    <b id="embTotal_${escAttr(emb.id)}" class="${temServicos ? 'text-success' : ''}">${formatarMoeda(resumo.total)}</b>
                </span>
            </button>`;
    });

    html += `</div></div></div>`;
    return html;
}

function selecionarEmbarcacaoServicos(embId) {
    embarcacaoSelecionadaId = embId;
    construirGradeServicos(embarcacoesCarregadas);
}

function atualizarCardEmbarcacaoSeletor(embId) {
    const card = document.querySelector(`.embarcacao-select-card[data-emb-id="${embId}"]`);
    if (!card) return;

    const resumo = obterResumoEmbarcacao(embId);
    const temServicos = resumo.qtd > 0;

    // Atualiza classes do card
    if (temServicos) {
        card.classList.add('has-services');
        card.classList.remove('no-services');
    } else {
        card.classList.remove('has-services');
        card.classList.add('no-services');
    }

    // Atualiza Pill de status
    const pill = document.getElementById('embPill_' + embId);
    if (pill) {
        pill.className = `emb-status-pill ${temServicos ? 'pill-success' : 'pill-muted'}`;
        pill.innerHTML = temServicos
            ? `<i class="fas fa-check"></i> <b>${resumo.qtd} serviço(s)</b>`
            : `<i class="far fa-circle"></i> Sem serviços`;
    }

    // Atualiza Total em reais
    const totalEl = document.getElementById('embTotal_' + embId);
    if (totalEl) {
        totalEl.textContent = formatarMoeda(resumo.total);
        if (temServicos) {
            totalEl.classList.add('text-success');
        } else {
            totalEl.classList.remove('text-success');
        }
    }

    // Atualiza ícone com selo de check
    const iconWrap = card.querySelector('.embarcacao-select-icon');
    if (iconWrap) {
        if (temServicos) {
            iconWrap.classList.add('is-configured');
            if (!iconWrap.querySelector('.emb-icon-check')) {
                iconWrap.insertAdjacentHTML('beforeend', '<i class="fas fa-check-circle emb-icon-check"></i>');
            }
        } else {
            iconWrap.classList.remove('is-configured');
            const checkIcon = iconWrap.querySelector('.emb-icon-check');
            if (checkIcon) checkIcon.remove();
        }
    }

    // Atualiza contadores globais do cabeçalho
    atualizarContadoresProgressoEmbarcacoes();
}

function atualizarContadoresProgressoEmbarcacoes() {
    let totalComServicos = 0;
    embarcacoesCarregadas.forEach(emb => {
        const res = obterResumoEmbarcacao(emb.id);
        if (res.qtd > 0) totalComServicos++;
    });

    const contadorGeral = document.getElementById('embContadorGeral');
    if (contadorGeral) {
        contadorGeral.innerHTML = `<i class="fas fa-list-check" style="margin-right: 4px;"></i> ${totalComServicos} de ${embarcacoesCarregadas.length} com serviços`;
        contadorGeral.style.background = totalComServicos > 0 ? 'rgba(46,204,113,0.18)' : 'rgba(120,120,120,0.14)';
        contadorGeral.style.color = totalComServicos > 0 ? 'var(--cor-destaque)' : 'var(--cor-texto-secundario)';
        contadorGeral.style.borderColor = totalComServicos > 0 ? 'rgba(46,204,113,0.35)' : 'var(--cor-borda)';
    }

    const qtdCom = document.getElementById('qtdFiltroComServicos');
    if (qtdCom) qtdCom.textContent = totalComServicos;

    const qtdPend = document.getElementById('qtdFiltroPendentes');
    if (qtdPend) qtdPend.textContent = embarcacoesCarregadas.length - totalComServicos;
}

function aplicarFiltroRapidoEmbarcacoes(tipo, btn) {
    filtroSituacaoEmbarcacoesAtual = tipo;
    document.querySelectorAll('.emb-filtro-btn').forEach(b => b.classList.remove('is-active'));
    if (btn) btn.classList.add('is-active');
    filtrarEmbarcacoesPasso2(document.getElementById('filtroEmbarcacoesPasso2')?.value || '');
}

function filtrarEmbarcacoesPasso2(termo) {
    termo = (termo || '').toLowerCase().trim();
    document.querySelectorAll('.embarcacao-select-card').forEach(card => {
        const embId = card.dataset.embId;
        const resumo = obterResumoEmbarcacao(embId);
        const temServicos = resumo.qtd > 0;

        let atendeSituacao = true;
        if (filtroSituacaoEmbarcacoesAtual === 'com_servicos') {
            atendeSituacao = temServicos;
        } else if (filtroSituacaoEmbarcacoesAtual === 'pendentes') {
            atendeSituacao = !temServicos;
        }

        const texto = card.textContent.toLowerCase();
        const atendeTermo = !termo || texto.includes(termo);

        card.style.display = (atendeSituacao && atendeTermo) ? 'grid' : 'none';
    });
}

function renderizarServicosEmbarcacaoAtual() {
    const area = document.getElementById('servicosEmbarcacaoAtual');
    if (!area) return;

    if (!embarcacaoSelecionadaId) {
        area.innerHTML = `
            <div class="tabela-vazia" style="margin-bottom: 20px;">
                <i class="fas fa-mouse-pointer"></i>
                <h3>Selecione uma embarcação</h3>
                <p>Depois de escolher a embarcação, a lista de serviços aparece aqui. Você pode voltar e escolher outra embarcação depois.</p>
            </div>`;
        return;
    }

    const emb = embarcacoesCarregadas.find(e => e.id === embarcacaoSelecionadaId);
    if (!emb) {
        area.innerHTML = '';
        return;
    }

    let html = `
        <div class="card embarcacao-bloco" data-emb-id="${escAttr(emb.id)}" style="margin-bottom: 20px;">
            <div class="card-header" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-list-check" style="color: var(--cor-destaque);"></i>
                <h3 style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;">Serviços para ${esc(emb.nome)} ${emb.registro ? '<small class="text-muted">(' + esc(emb.registro) + ')</small>' : ''}</h3>
            </div>
            <div class="card-body emb-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--cor-borda);">
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                            <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

    ALL_SERVICOS.forEach(s => {
        const estado = servicosSelecionadosPorEmbarcacao[emb.id]?.[s.id] || null;
        const checked = !!estado;
        const qtd = estado?.qtd || 1;
        const preco = checked && Number.isFinite(Number(estado?.preco))
            ? Number(estado.preco)
            : (parseFloat(s.preco_padrao) || 0);
        const subtotal = checked ? preco * qtd : 0;
        html += `
            <tr class="servico-linha" style="border-bottom: 1px solid var(--cor-borda); ${checked ? 'background: rgba(46,204,113,0.05);' : ''}">
                <td style="padding: 8px 12px; text-align: center;">
                    <input type="checkbox" class="check-servico" data-emb-id="${escAttr(emb.id)}" data-serv-id="${escAttr(s.id)}"
                           onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);" ${checked ? 'checked' : ''}>
                </td>
                <td style="padding: 8px 12px;">
                    <span style="font-weight: 500;">${esc(s.nome)}</span>
                    ${s.descricao ? '<br><small class="text-muted">' + esc(s.descricao.length > 60 ? s.descricao.substring(0, 60) + '...' : s.descricao) + '</small>' : ''}
                </td>
                <td style="padding: 8px 12px; text-align: center;">
                    <input type="number" value="${qtd}" min="1" max="99" data-emb-id="${escAttr(emb.id)}" data-serv-id="${escAttr(s.id)}"
                           class="qtd-servico" onchange="servicoQtdChanged(this)" onfocus="this.select()"
                           style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;" ${checked ? '' : 'disabled'}>
                </td>
                <td style="padding: 8px 12px; text-align: right;">
                    <span style="font-weight: 500;">${formatarMoeda(preco)}</span>
                </td>
                <td style="padding: 8px 12px; text-align: right;">
                    <span id="sub_${escAttr(emb.id)}_${escAttr(s.id)}" data-preco="${preco}" style="font-weight: 600; color: var(--cor-destaque);">${formatarMoeda(subtotal)}</span>
                </td>
            </tr>`;
    });

    html += '</tbody></table></div></div>';
    area.innerHTML = html;
}

function servicoToggled(checkbox) {
    const embId = checkbox.dataset.embId;
    const servId = checkbox.dataset.servId;
    const linha = checkbox.closest('tr');
    const qtdInput = linha.querySelector('.qtd-servico');

    if (checkbox.checked) {
        linha.style.background = 'rgba(46,204,113,0.05)';
        qtdInput.disabled = false;
        qtdInput.value = 1;
        salvarServicoSelecionado(embId, servId, qtdInput.value);
    } else {
        linha.style.background = '';
        qtdInput.disabled = true;
        qtdInput.value = 0;
        removerServicoSelecionado(embId, servId);
    }

    atualizarSubtotalServico(embId, servId);
    atualizarCardEmbarcacaoSeletor(embId);
    atualizarTotais();
}

function servicoQtdChanged(input) {
    const embId = input.dataset.embId;
    const servId = input.dataset.servId;
    const linha = input.closest('tr');
    const checkbox = linha.querySelector('.check-servico');
    if (checkbox.checked) {
        salvarServicoSelecionado(embId, servId, input.value);
    }
    atualizarSubtotalServico(embId, servId);
    atualizarCardEmbarcacaoSeletor(embId);
    atualizarTotais();
}

function salvarServicoSelecionado(embId, servId, qtdValor) {
    if (!servicosSelecionadosPorEmbarcacao[embId]) {
        servicosSelecionadosPorEmbarcacao[embId] = {};
    }
    const estadoAtual = servicosSelecionadosPorEmbarcacao[embId][servId] || {};
    const servicoCatalogo = ALL_SERVICOS.find(s => String(s.id) === String(servId));
    servicosSelecionadosPorEmbarcacao[embId][servId] = {
        qtd: Math.max(1, parseInt(qtdValor) || 1),
        preco: Number.isFinite(Number(estadoAtual.preco))
            ? Number(estadoAtual.preco)
            : (parseFloat(servicoCatalogo?.preco_padrao) || 0)
    };
    atualizarEstadoAvancoServicos();
}

function removerServicoSelecionado(embId, servId) {
    if (!servicosSelecionadosPorEmbarcacao[embId]) return;
    delete servicosSelecionadosPorEmbarcacao[embId][servId];
    if (Object.keys(servicosSelecionadosPorEmbarcacao[embId]).length === 0) {
        delete servicosSelecionadosPorEmbarcacao[embId];
    }
    atualizarEstadoAvancoServicos();
}

function obterResumoEmbarcacao(embId) {
    const selecionados = servicosSelecionadosPorEmbarcacao[embId] || {};
    let total = 0;
    let qtd = 0;

    Object.entries(selecionados).forEach(([servId, estado]) => {
        const servico = ALL_SERVICOS.find(s => String(s.id) === String(servId));
        if (!servico) return;
        const quantidade = Math.max(1, parseInt(estado.qtd) || 1);
        const preco = Number.isFinite(Number(estado.preco))
            ? Number(estado.preco)
            : (parseFloat(servico.preco_padrao) || 0);
        total += preco * quantidade;
        qtd++;
    });

    return { total, qtd };
}

function atualizarSubtotalServico(embId, servId) {
    const linha = document.querySelector(`.check-servico[data-emb-id="${embId}"][data-serv-id="${servId}"]`)?.closest('tr');
    if (!linha) return;
    const checkbox = linha.querySelector('.check-servico');
    const qtdInput = linha.querySelector('.qtd-servico');
    const subEl = document.getElementById('sub_' + embId + '_' + servId);
    if (!subEl) return;

    if (!checkbox.checked) {
        subEl.textContent = formatarMoeda(0);
        return;
    }

    const preco = parseFloat(subEl.dataset.preco) || 0;
    const qtd = Math.max(1, parseInt(qtdInput.value) || 1);
    qtdInput.value = qtd;
    const subtotal = preco * qtd;
    subEl.textContent = formatarMoeda(subtotal);
}

// ============ TOTAIS E CÁLCULOS FINANCEIROS ============
function formatarNumeroPtBr(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarCampoMoedaPorValor(inputId, valor) {
    const input = document.getElementById(inputId);
    if (input) input.value = formatarNumeroPtBr(valor);
}

function mascararMoeda(input, hiddenId) {
    const digitos = input.value.replace(/\D/g, '').slice(0, 14);
    const valor = digitos ? Number(digitos) / 100 : 0;
    input.value = formatarNumeroPtBr(valor);
    const hidden = document.getElementById(hiddenId);
    if (hidden) hidden.value = valor.toFixed(2);
    atualizarTotais();
}

function normalizarPercentualVisivel(valor) {
    if (valor === '' || valor === null || valor === undefined) return '';
    let texto = String(valor).replace(/\./g, ',').replace(/[^\d,]/g, '');
    if (texto === '') return '';

    const temVirgula = texto.includes(',');
    const partes = texto.split(',');
    let inteiro = partes.shift() || '';

    // Remove zeros à esquerda (ex: "05" -> "5", "0" -> "0")
    inteiro = inteiro.replace(/^0+(?=\d)/, '').slice(0, 3);
    if (inteiro === '' && temVirgula) {
        inteiro = '0';
    }

    const decimal = partes.join('').slice(0, 2);

    if (temVirgula) {
        return (inteiro || '0') + ',' + decimal;
    }
    return inteiro;
}

function mascararDesconto(input) {
    const tipo = document.getElementById('tipoDesconto')?.value || 'perc';
    if (tipo === 'valor') {
        mascararMoeda(input, 'descontoGlobal');
        return;
    }

    const valorOriginal = input.value;
    const posOriginal = input.selectionStart;
    const normalizado = normalizarPercentualVisivel(valorOriginal);

    if (input.value !== normalizado) {
        input.value = normalizado;
        if (posOriginal !== null) {
            const diferenca = normalizado.length - valorOriginal.length;
            const novaPos = Math.max(0, Math.min(normalizado.length, posOriginal + diferenca));
            try {
                input.setSelectionRange(novaPos, novaPos);
            } catch (e) {}
        }
    }

    const valor = parseFloat(input.value.replace(',', '.')) || 0;
    document.getElementById('descontoGlobal').value = valor.toFixed(2);
    validarDescontoPercentual(false);
    atualizarTotais();
}

function finalizarEdicaoDesconto(input) {
    const tipo = document.getElementById('tipoDesconto')?.value || 'perc';
    if (tipo === 'valor') {
        mascararMoeda(input, 'descontoGlobal');
        return;
    }

    if (input.value.endsWith(',')) {
        input.value = input.value.slice(0, -1);
    }
    const normalizado = normalizarPercentualVisivel(input.value);
    input.value = normalizado;
    const valor = parseFloat(input.value.replace(',', '.')) || 0;
    document.getElementById('descontoGlobal').value = valor.toFixed(2);
    validarDescontoPercentual(true);
    atualizarTotais();
}

function validarDescontoPercentual(anunciar = false) {
    const tipo = document.getElementById('tipoDesconto')?.value || 'perc';
    const valor = parseFloat(document.getElementById('descontoGlobal')?.value) || 0;
    const invalido = tipo === 'perc' && valor >= 100;
    const visivel = document.getElementById('descontoGlobalDisplay');
    const wrapper = visivel?.closest('.discount-input-wrap');
    const erro = document.getElementById('descontoErro');

    visivel?.setAttribute('aria-invalid', invalido ? 'true' : 'false');
    wrapper?.classList.toggle('is-invalid', invalido);
    if (erro) erro.hidden = !invalido;

    if (anunciar && invalido && erro) {
        erro.textContent = 'O desconto percentual deve ser menor que 100%. O maior valor permitido é 99,99%.';
    }
    return !invalido;
}

function setTipoDesconto(tipo) {
    const tipoSeguro = tipo === 'valor' ? 'valor' : 'perc';
    const select = document.getElementById('tipoDesconto');
    const prefixo = document.getElementById('descontoPrefixo');
    const input = document.getElementById('descontoGlobalDisplay');
    const hidden = document.getElementById('descontoGlobal');

    if (select) select.value = tipoSeguro;
    if (prefixo) prefixo.textContent = tipoSeguro === 'valor' ? 'R$' : '%';
    if (input) {
        input.placeholder = '0,00';
        input.inputMode = tipoSeguro === 'valor' ? 'numeric' : 'decimal';
        input.value = formatarNumeroPtBr(parseFloat(hidden?.value) || 0);
    }

    document.querySelectorAll('.discount-mode-btn').forEach(btn => {
        const ativo = btn.dataset.discountType === tipoSeguro;
        btn.classList.toggle('is-active', ativo);
        btn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    });

    atualizarTotais();
}

function obterValorEntrada(totalGeral) {
    const entradaInput = document.getElementById('valorEntrada');
    if (!entradaInput) return 0;

    let valorEntrada = parseFloat(entradaInput.value) || 0;
    if (valorEntrada < 0) valorEntrada = 0;
    if (valorEntrada > totalGeral) {
        valorEntrada = totalGeral;
        entradaInput.value = totalGeral.toFixed(2);
        formatarCampoMoedaPorValor('valorEntradaDisplay', totalGeral);
    }

    return valorEntrada;
}

function atualizarTotais() {
    let subtotalGeral = 0;
    embarcacoesCarregadas.forEach(emb => {
        const resumo = obterResumoEmbarcacao(emb.id);
        subtotalGeral += resumo.total;
        const embTotalEl = document.getElementById('embTotal_' + emb.id);
        if (embTotalEl) {
            embTotalEl.textContent = formatarMoeda(resumo.total);
            const summarySmall = embTotalEl.closest('.embarcacao-select-summary')?.querySelector('small');
            if (summarySmall) summarySmall.textContent = resumo.qtd + ' serviço(s)';
        }
    });

    const tipoDesconto = document.getElementById('tipoDesconto')?.value || 'perc';
    const descInput = document.getElementById('descontoGlobal');
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = parseFloat(descInput?.value) || 0;
        descontoValor = descontoPerc < 100 ? subtotalGeral * (descontoPerc / 100) : 0;
    } else {
        descontoValor = parseFloat(descInput?.value) || 0;
        if (descontoValor > subtotalGeral && subtotalGeral > 0) {
            descontoValor = subtotalGeral;
            descInput.value = subtotalGeral.toFixed(2);
            formatarCampoMoedaPorValor('descontoGlobalDisplay', subtotalGeral);
        }
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);

    const subtotalEl = document.getElementById('subtotal');
    if (subtotalEl) subtotalEl.textContent = formatarMoeda(subtotalGeral);

    const descValorEl = document.getElementById('descontoValor');
    if (descValorEl) {
        descValorEl.textContent = tipoDesconto === 'perc'
            ? descontoPerc.toFixed(2).replace('.', ',') + '% = - ' + formatarMoeda(descontoValor)
            : '- ' + formatarMoeda(descontoValor) + ' (' + descontoPerc.toFixed(2).replace('.', ',') + '%)';
    }

    const totalGeralEl = document.getElementById('totalGeral');
    if (totalGeralEl) totalGeralEl.textContent = formatarMoeda(totalGeral);

    const parcelas = parseInt(document.getElementById('parcelas')?.value) || 1;
    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    const entradaResumo = document.getElementById('entradaResumo');
    if (entradaResumo) {
        entradaResumo.textContent = valorEntrada > 0
            ? 'Entrada de ' + formatarMoeda(valorEntrada) + ' e saldo restante de ' + formatarMoeda(saldoRestante)
            : 'Sem entrada informada';
    }

    let ph = '';
    if (valorEntrada > 0) {
        ph += `<div style="padding: 3px 0;">Entrada imediata: <strong>${formatarMoeda(valorEntrada)}</strong></div>`;
    }
    for (let i = 1; i <= parcelas; i++) {
        ph += `<div style="padding: 3px 0;">Parcela ${i}/<strong>${parcelas}: ${formatarMoeda(valorParcela)}</strong></div>`;
    }
    const parcelasInfoEl = document.getElementById('parcelasInfo');
    if (parcelasInfoEl) parcelasInfoEl.innerHTML = ph;
    atualizarEstadoAvancoServicos();
}

// ============ PASSO 3: REVISÃO ============
function montarRevisao() {
    const dadosServicos = [];
    let subtotalGeral = 0;

    embarcacoesCarregadas.forEach(embData => {
        const selecionados = servicosSelecionadosPorEmbarcacao[embData.id] || {};
        const servicosDaEmb = [];
        let embTotal = 0;

        Object.entries(selecionados).forEach(([servId, estado]) => {
            const servico = ALL_SERVICOS.find(s => String(s.id) === String(servId));
            if (!servico) return;
            const preco = Number.isFinite(Number(estado.preco))
                ? Number(estado.preco)
                : (parseFloat(servico.preco_padrao) || 0);
            const qtd = Math.max(1, parseInt(estado.qtd) || 1);
            const subtotal = preco * qtd;
            embTotal += subtotal;
            servicosDaEmb.push({ servico_id: servId, nome: servico.nome, preco, qtd, subtotal, quantidade: qtd });
        });

        if (servicosDaEmb.length > 0) {
            dadosServicos.push({
                embarcacao_id: embData.id,
                embarcacao_nome: embData.nome,
                embarcacao_registro: embData.registro || 'N/I',
                total: embTotal,
                servicos: servicosDaEmb
            });
            subtotalGeral += embTotal;
        }
    });

    document.getElementById('dadosServicosJson').value = JSON.stringify(dadosServicos);

    const tipoDesconto = document.getElementById('tipoDesconto').value;
    const descInput = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = descInput;
        descontoValor = subtotalGeral * (descontoPerc / 100);
    } else {
        descontoValor = Math.min(subtotalGeral, descInput);
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;

    document.getElementById('reviewCliente').innerHTML = `
        <strong>${clienteSelecionadoData?.nome || ''}</strong><br>
        <small class="text-muted">Perfil: ${clienteSelecionadoData?.perfil || ''} &middot; CPF/CNPJ: ${clienteSelecionadoData?.cpfcnpj || ''}</small>`;
    document.getElementById('reviewResponsavelFechamento').innerHTML = `
        <strong>${esc(responsavelFechamentoNomeData) || 'Não informado'}</strong><br>
        <small class="text-muted">Telefone: ${esc(responsavelFechamentoTelefoneData) || 'Não informado'}</small>`;

    let revEmbHtml = '';
    dadosServicos.forEach(ds => {
        revEmbHtml += `
        <div style="margin-bottom: 15px; padding: 12px; background: var(--cor-sidebar); border-radius: 8px; border: 1px solid var(--cor-borda);">
            <h5 style="color: var(--cor-destaque); margin-bottom: 8px;">
                <i class="fas fa-ship"></i> ${esc(ds.embarcacao_nome)}
                ${ds.embarcacao_registro !== 'N/I' ? '<small class="text-muted">(' + esc(ds.embarcacao_registro) + ')</small>' : ''}
            </h5>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid var(--cor-borda);">
                    <th style="text-align: left; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem;">Serviço</th>
                    <th style="text-align: center; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 50px;">Qtd</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Unit.</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Subtotal</th>
                </tr></thead><tbody>`;
        ds.servicos.forEach(sv => {
            revEmbHtml += `<tr style="border-bottom: 1px solid var(--cor-borda);">
                <td style="padding: 6px;">${esc(sv.nome)}</td>
                <td style="text-align: center; padding: 6px;">${sv.qtd}</td>
                <td style="text-align: right; padding: 6px;">${formatarMoeda(sv.preco)}</td>
                <td style="text-align: right; padding: 6px; font-weight: 600;">${formatarMoeda(sv.subtotal)}</td>
            </tr>`;
        });
        revEmbHtml += `<tr><td colspan="3" style="text-align: right; padding: 6px; font-weight: 600;">Total da Embarcação:</td>
            <td style="text-align: right; padding: 6px; font-weight: 700; color: var(--cor-destaque);">${formatarMoeda(ds.total)}</td></tr>`;
        revEmbHtml += '</tbody></table></div>';
    });
    document.getElementById('reviewPorEmbarcacao').innerHTML = revEmbHtml || '<p class="text-muted">Nenhum serviço selecionado.</p>';

    document.getElementById('rSubtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('rDescontoPerc').textContent = descontoPerc.toFixed(2).replace('.', ',');
    document.getElementById('rDesconto').textContent = '- ' + formatarMoeda(descontoValor);
    document.getElementById('rEntrada').textContent = formatarMoeda(valorEntrada);
    document.getElementById('rSaldo').textContent = formatarMoeda(saldoRestante);
    document.getElementById('rTotalGeral').textContent = formatarMoeda(totalGeral);

    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    let rph = valorEntrada > 0 ? `Entrada de <strong>${formatarMoeda(valorEntrada)}</strong>` : '';
    for (let i = 1; i <= parcelas; i++) {
        if (rph) rph += ' &middot; ';
        rph += `${i}x de <strong>${formatarMoeda(valorParcela)}</strong>`;
    }
    document.getElementById('rParcelas').innerHTML = rph;

    document.getElementById('reviewLoading').style.display = 'none';
    document.getElementById('reviewContent').style.display = 'block';
}

function formatarMoeda(valor) {
    return 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function escAttr(str) {
    return esc(String(str)).replace(/'/g, '&#39;');
}

function deveIgnorarEnterWizard(event) {
    const alvo = event.target;
    const tag = (alvo?.tagName || '').toLowerCase();
    const tipo = (alvo?.type || '').toLowerCase();

    if (event.key !== 'Enter') return true;
    if (event.ctrlKey || event.altKey || event.shiftKey || event.metaKey) return true;
    if (alvo?.isContentEditable) return true;
    if (['textarea', 'select', 'button', 'a'].includes(tag)) return true;
    if (tag === 'input' && !['checkbox', 'radio'].includes(tipo)) {
        event.preventDefault();
        return true;
    }

    return false;
}

function obterPassoAtualWizard() {
    const painelVisivel = Array.from(document.querySelectorAll('.wizard-panel')).find(painel => {
        const style = window.getComputedStyle(painel);
        return style.display !== 'none' && style.visibility !== 'hidden';
    });
    const match = painelVisivel?.id?.match(/^passo(\d+)$/);
    return match ? parseInt(match[1], 10) : 1;
}

function avancarWizardComEnter(event) {
    if (deveIgnorarEnterWizard(event)) return;

    const passoAtual = obterPassoAtualWizard();
    let botao = null;

    if (passoAtual === 1) botao = document.getElementById('btnPasso1');
    if (passoAtual === 2) botao = document.getElementById('btnPasso2');
    if (passoAtual === 3) botao = document.querySelector('#passo3 button[type="submit"]');

    if (!botao || botao.disabled) return;

    event.preventDefault();
    botao.click();
}

// ============ INICIALIZAÇÃO ============
document.addEventListener('DOMContentLoaded', () => {
    const clienteMarcado = document.querySelector('input[name="cliente_id"]:checked');
    if (clienteMarcado) clienteSelecionado(clienteMarcado);
    if (typeof MODO_EDICAO !== 'undefined' && MODO_EDICAO) {
        servicosSelecionadosPorEmbarcacao = JSON.parse(JSON.stringify(SERVICOS_EDICAO_INICIAIS));
        atualizarEstadoAvancoServicos();
    }
    setTipoDesconto(document.getElementById('tipoDesconto')?.value || 'perc');
    formatarCampoMoedaPorValor('valorEntradaDisplay', parseFloat(document.getElementById('valorEntrada')?.value) || 0);
    atualizarPasso1();
});

document.addEventListener('keydown', avancarWizardComEnter);
document.getElementById('wizardForm')?.addEventListener('submit', event => {
    if (validarDescontoPercentual(true)) return;
    event.preventDefault();
    irParaPasso(2);
    document.getElementById('descontoGlobalDisplay')?.focus();
});
