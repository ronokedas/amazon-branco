<?php
/**
 * Componente: Identificação e Vínculos do Dossiê Naval
 * Local: modules/protocolos/components/dossie_identificacao.php
 *
 * Responsável pela abertura de novos dossiês, seleção de embarcação e cliente,
 * vínculos inteligentes com processos de análise de planos, vistorias e certificados,
 * além da edição dos dados mestres quando o dossiê já está criado.
 */
?>

<?php if (!$d): ?>
    <!-- ========================================== -->
    <!-- MODO DE CRIAÇÃO DE NOVO DOSSIÊ DE PROTOCOLO -->
    <!-- ========================================== -->
    <div class="prot-helper-box info">
        <i class="fa-solid fa-circle-info text-accent"></i> 
        <strong>Como funciona o Dossiê de Protocolo Naval:</strong><br>
        O dossiê é a pasta viva que acompanha a documentação técnica e legal da embarcação em todo o seu ciclo.
        Ao abri-lo, você vincula a embarcação e a finalidade regulamentar (ex.: NORMAM-201, NORMAM-202 ou RIPEAM).
        A partir daí, registra-se a chegada de documentos físicos (com custódia formal de originais), protocolo na Capitania dos Portos (com número SISAP) e devolução ao armador com recibo e aceite digital.
    </div>

    <section class="card">
        <div class="card-body">
            <h3 class="mb-3" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
                <i class="fa-solid fa-pen-to-square"></i> 1. Identificação e Objeto do Dossiê
            </h3>

            <?php if (!empty($analisePre)): ?>
                <div class="prot-helper-box mb-3" style="background: rgba(86, 224, 173, 0.08); border: 1px solid var(--accent, #56e0ad);">
                    <i class="fa-solid fa-compass-drafting text-accent fs-5 me-2"></i>
                    <div>
                        <strong>Vínculo com Análise de Planos Ativo:</strong> Processo nº <strong><?= h($analisePre['numero']) ?></strong> (<?= h($analisePre['tipo_processo']) ?>).
                        <span class="text-secondary">Embarcação, Armador e Assunto Técnico NORMAM pré-selecionados para trâmite oficial na Capitania dos Portos.</span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= APP_URL ?>protocolos/actions">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="action" value="criar">

                <div class="row g-3 mb-3">
                    <div class="col-md-6 position-relative">
                        <label class="form-label fw-bold" for="busca_embarcacao_input">
                            <i class="fa-solid fa-ship text-accent me-1"></i> Embarcação (Pesquisa Inteligente) *
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: rgba(255,255,255,0.05); border-color: var(--border); color: var(--accent, #56e0ad);">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" 
                                   id="busca_embarcacao_input" 
                                   class="form-control" 
                                   placeholder="Digite o nome ou registro da embarcação..." 
                                   autocomplete="off" 
                                   oninput="aoDigitarBuscaEmbarcacao(this.value)" 
                                   onfocus="aoFocarBuscaEmbarcacao()" 
                                   onkeydown="tratarTecladoBuscaEmbarcacao(event)">
                            <button type="button" class="btn btn-outline-secondary" id="btn-limpar-embarcacao" onclick="limparSelecaoEmbarcacao()" style="display: none;" title="Limpar seleção para buscar outra">
                                ✕ Limpar
                            </button>
                        </div>

                        <!-- Select real sincronizado para submissão do formulário -->
                        <select name="embarcacao_id" id="embarcacao_id" required style="position: absolute; opacity: 0; pointer-events: none; height: 1px; width: 1px;" onchange="sincronizarDadosEmbarcacao(this)">
                            <option value="">-- Selecione a embarcação --</option>
                            <?php foreach ($embarcacoes as $e): ?>
                                <option value="<?= h($e['id']) ?>" 
                                        data-nome="<?= h($e['nome']) ?>"
                                        data-registro="<?= h($e['registro'] ?: '') ?>"
                                        data-cliente="<?= h($e['cliente_id'] ?: '') ?>"
                                        data-cliente-nome="<?= h($e['cliente_nome'] ?: '') ?>"
                                        <?= $preEmb === $e['id'] ? 'selected' : '' ?>>
                                    <?= h($e['nome'] . ($e['registro'] ? ' · ' . $e['registro'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Dropdown flutuante de resultados dinâmicos -->
                        <div id="dropdown-busca-embarcacoes" class="shadow-lg rounded" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; max-height: 280px; overflow-y: auto; background: var(--bg-surface, #0e2a24); border: 1px solid var(--accent, #56e0ad); margin-top: 4px; padding: 4px 0;">
                            <!-- Preenchido via JavaScript -->
                        </div>

                        <div id="embarcacao-selecionada-badge" class="mt-2" style="display: none;"></div>
                        <small class="text-muted d-block mt-1">Digite qualquer letra do nome ou registro da embarcação para filtrar em tempo real.</small>
                        <div id="preview-acervo-resumo" class="mt-2" style="display: none;"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="cliente_id">Cliente / Solicitante</label>
                        <select class="form-control" name="cliente_id" id="cliente_id">
                            <option value="">Usar vínculo cadastral da embarcação</option>
                            <?php 
                            $cliPreSel = !empty($analisePre['emb_cliente_id']) ? $analisePre['emb_cliente_id'] : '';
                            foreach ($clientes as $c): 
                            ?>
                                <option value="<?= h($c['id']) ?>" <?= $cliPreSel === $c['id'] ? 'selected' : '' ?>>
                                    <?= h($c['nome'] . ($c['cpf_cnpj'] ? ' (' . $c['cpf_cnpj'] . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Preenchido automaticamente com o proprietário/armador da embarcação.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="assunto">Assunto / Finalidade do Processo *</label>
                    <?php
                    $assuntoInicial = '';
                    if (!empty($analisePre)) {
                        $assuntoInicial = 'Aprovação de Planos e Memoriais (' . $analisePre['tipo_processo'] . ') - ' . $analisePre['numero'] . ' - ' . ($analisePre['embarcacao_nome'] ?? '');
                    }
                    ?>
                    <input class="form-control" required maxlength="255" name="assunto" id="assunto" 
                           value="<?= h($assuntoInicial) ?>"
                           placeholder="Ex.: Apresentação de Projeto Técnico para Licença de Construção (LC) - NORMAM-202">
                    
                    <!-- Pílulas de Atalho Rápido para Assunto (Usabilidade Autodidática NORMAM) -->
                    <div class="mt-2">
                        <span class="text-secondary small fw-semibold me-1"><i class="fa-solid fa-bolt text-accent"></i> Sugestões Rápidas de Assunto (clique para preencher):</span>
                        <div class="prot-shortcuts-container d-inline-flex flex-wrap gap-1 mt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Aprovação de Planos e Memoriais / NORMAM-202 (Licença de Construção - LC)')">
                                Aprovação NORMAM-202 (LC)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Licença de Alteração / Reclassificação Naval (LA/LR) - NORMAM-202')">
                                Alteração / Reclassificação (LA/LR)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Regularização de Arqueação e Borda Livre (CNARQ / CNBL)')">
                                Arqueação e Borda Livre (CNARQ/CNBL)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Inscrição Inicial de Embarcação no TIE/TIEM')">
                                Inscrição Inicial (TIE/TIEM)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Cumprimento de Notificação / Exigência da Capitania dos Portos')">
                                Cumprimento de Exigência da CP
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="definirAssunto('Renovação de Certificado de Segurança da Navegação (CSN)')">
                                Renovação de CSN
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="unidade_maritima_id">Destino Previsto (Marinha)</label>
                        <select class="form-control" name="unidade_maritima_id" id="unidade_maritima_id">
                            <option value="">Definir no envio à Capitania</option>
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= h($u['id']) ?>">
                                    <?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Capitania, Delegacia ou Agência Fluvial/Marítima.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="analise_id">Análise de Planos Vinculada</label>
                        <select class="form-control" name="analise_id" id="analise_id" onchange="aoMudarAnalise(this)">
                            <option value="">Sem vínculo com análise</option>
                            <?php foreach ($analisesAbertas as $a): ?>
                                <option value="<?= h($a['id']) ?>" data-embarcacao="<?= h($a['embarcacao_id']) ?>" <?= (($_GET['analise_id'] ?? '') === $a['id'] || (!empty($analisePre) && $analisePre['id'] === $a['id'])) ? 'selected' : '' ?>>
                                    <?= h($a['numero'] . ' (' . $a['tipo_processo'] . ' - ' . $a['status'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Processo técnico do engenheiro naval.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="vistoria_id">Vistoria Vinculada</label>
                        <select class="form-control" name="vistoria_id" id="vistoria_id">
                            <option value="">Sem vínculo com vistoria</option>
                            <?php foreach ($vistoriasAbertas as $v): ?>
                                <option value="<?= h($v['id']) ?>" data-embarcacao="<?= h($v['embarcacao_id']) ?>" <?= ($_GET['vistoria_id'] ?? '') === $v['id'] ? 'selected' : '' ?>>
                                    <?= h($v['numero'] . ' (' . ($v['finalidade'] ?: 'Vistoria') . ' - ' . $v['status'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Ordem de vistoria do vistoriador naval.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Certificado Emitido Vinculado</label>
                        <div class="input-group">
                            <select class="form-control" name="certificado_tipo" id="certificado_tipo" style="max-width: 110px;">
                                <option value="">Tipo...</option>
                                <option value="CSN" <?= (($_GET['certificado_tipo'] ?? '') === 'CSN') ? 'selected' : '' ?>>CSN</option>
                                <option value="CNBL" <?= (($_GET['certificado_tipo'] ?? '') === 'CNBL') ? 'selected' : '' ?>>CNBL</option>
                                <option value="CNARQ" <?= (($_GET['certificado_tipo'] ?? '') === 'CNARQ') ? 'selected' : '' ?>>CNARQ</option>
                                <option value="LP" <?= (($_GET['certificado_tipo'] ?? '') === 'LP') ? 'selected' : '' ?>>LP</option>
                                <option value="LC" <?= (($_GET['certificado_tipo'] ?? '') === 'LC') ? 'selected' : '' ?>>LC</option>
                                <option value="CHT" <?= (($_GET['certificado_tipo'] ?? '') === 'CHT') ? 'selected' : '' ?>>CHT</option>
                            </select>
                            <input class="form-control" name="certificado_id" id="certificado_id" 
                                   value="<?= h($_GET['certificado_id'] ?? '') ?>" 
                                   placeholder="UUID ou Nº Certificado">
                        </div>
                        <small class="text-muted">Conexão direta com o documento expedido.</small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-folder-plus"></i> Criar Dossiê de Protocolo
                    </button>
                    <a href="<?= APP_URL ?>protocolos" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

    <script>
    const listaEmbarcacoes = <?= json_encode(array_values(array_map(function($e) {
        return [
            'id' => (string)$e['id'],
            'nome' => (string)$e['nome'],
            'registro' => (string)($e['registro'] ?? ''),
            'cliente_id' => (string)($e['cliente_id'] ?? ''),
            'cliente_nome' => (string)($e['cliente_nome'] ?? ''),
        ];
    }, $embarcacoes)), JSON_UNESCAPED_UNICODE) ?>;

    let indiceFocadoDropdown = -1;

    function normalizarTexto(txt) {
        return (txt || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function destacarTermo(texto, termo) {
        if (!termo || !texto) return texto || '';
        const normTexto = normalizarTexto(texto);
        const normTermo = normalizarTexto(termo);
        const idx = normTexto.indexOf(normTermo);
        if (idx === -1) return texto;
        const antes = texto.substring(0, idx);
        const match = texto.substring(idx, idx + termo.length);
        const depois = texto.substring(idx + termo.length);
        return `${antes}<mark style="background: rgba(86, 224, 173, 0.35); color: #fff; padding: 0 2px; border-radius: 2px;">${match}</mark>${depois}`;
    }

    function aoDigitarBuscaEmbarcacao(termo) {
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');
        const norm = normalizarTexto(termo.trim());
        indiceFocadoDropdown = -1;

        if (!norm) {
            renderizarDropdownBusca(listaEmbarcacoes.slice(0, 15), termo);
            dropdown.style.display = 'block';
            return;
        }

        const filtradas = listaEmbarcacoes.filter(e => {
            const nomeNorm = normalizarTexto(e.nome);
            const regNorm = normalizarTexto(e.registro);
            const cliNorm = normalizarTexto(e.cliente_nome);
            return nomeNorm.includes(norm) || regNorm.includes(norm) || cliNorm.includes(norm);
        });

        renderizarDropdownBusca(filtradas, termo);
        dropdown.style.display = 'block';
    }

    function renderizarDropdownBusca(itens, termo) {
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');
        if (!itens || itens.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                    Nenhuma embarcação encontrada para "<strong>${termo}</strong>".
                </div>
            `;
            return;
        }

        let html = '';
        itens.forEach((it, idx) => {
            const nomeFmt = destacarTermo(it.nome, termo);
            const regFmt = it.registro ? destacarTermo(it.registro, termo) : '';
            const cliFmt = it.cliente_nome ? destacarTermo(it.cliente_nome, termo) : '';

            html += `
                <div class="item-busca-embarcacao px-3 py-2" 
                     data-index="${idx}"
                     style="cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.15s ease;"
                     onmouseenter="this.style.background='rgba(86,224,173,0.12)'"
                     onmouseleave="this.style.background='transparent'"
                     onclick='selecionarEmbarcacaoPeloItem(${JSON.stringify(it).replace(/'/g, "&#39;")})'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong style="color: var(--accent, #56e0ad); font-size: 0.92rem;">
                                <i class="fa-solid fa-ship me-1 text-accent"></i> ${nomeFmt}
                            </strong>
                            ${regFmt ? `<span class="badge bg-secondary ms-2 small" style="font-size: 0.72rem;">${regFmt}</span>` : ''}
                        </div>
                    </div>
                    ${cliFmt ? `<div class="small text-secondary mt-1"><i class="fa-solid fa-user me-1"></i> Armador: ${cliFmt}</div>` : ''}
                </div>
            `;
        });
        dropdown.innerHTML = html;
    }

    function aoFocarBuscaEmbarcacao() {
        const input = document.getElementById('busca_embarcacao_input');
        aoDigitarBuscaEmbarcacao(input.value);
    }

    function tratarTecladoBuscaEmbarcacao(e) {
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');
        if (dropdown.style.display === 'none') return;

        const itens = dropdown.querySelectorAll('.item-busca-embarcacao');
        if (!itens.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            indiceFocadoDropdown = (indiceFocadoDropdown + 1) % itens.length;
            atualizarFocoItemDropdown(itens);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            indiceFocadoDropdown = (indiceFocadoDropdown - 1 + itens.length) % itens.length;
            atualizarFocoItemDropdown(itens);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (indiceFocadoDropdown >= 0 && itens[indiceFocadoDropdown]) {
                itens[indiceFocadoDropdown].click();
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    }

    function atualizarFocoItemDropdown(itens) {
        itens.forEach((el, i) => {
            if (i === indiceFocadoDropdown) {
                el.style.background = 'rgba(86, 224, 173, 0.2)';
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.style.background = 'transparent';
            }
        });
    }

    function selecionarEmbarcacaoPeloItem(item) {
        const input = document.getElementById('busca_embarcacao_input');
        const select = document.getElementById('embarcacao_id');
        const btnLimpar = document.getElementById('btn-limpar-embarcacao');
        const badge = document.getElementById('embarcacao-selecionada-badge');
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');

        input.value = item.nome;
        select.value = item.id;
        btnLimpar.style.display = 'inline-block';

        badge.innerHTML = `
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded small" style="background: rgba(86, 224, 173, 0.15); border: 1px solid var(--accent, #56e0ad); color: var(--text-primary);">
                <i class="fa-solid fa-circle-check text-accent"></i>
                <span>Embarcação Selecionada: <strong>${item.nome}</strong> ${item.registro ? '· ' + item.registro : ''}</span>
            </div>
        `;
        badge.style.display = 'block';
        dropdown.style.display = 'none';

        sincronizarDadosEmbarcacao(select);
    }

    function limparSelecaoEmbarcacao() {
        const input = document.getElementById('busca_embarcacao_input');
        const select = document.getElementById('embarcacao_id');
        const btnLimpar = document.getElementById('btn-limpar-embarcacao');
        const badge = document.getElementById('embarcacao-selecionada-badge');
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');

        input.value = '';
        select.value = '';
        btnLimpar.style.display = 'none';
        badge.style.display = 'none';
        dropdown.style.display = 'none';

        sincronizarDadosEmbarcacao(select);
        input.focus();
    }

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        const container = document.getElementById('busca_embarcacao_input')?.closest('.position-relative');
        const dropdown = document.getElementById('dropdown-busca-embarcacoes');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    function definirAssunto(txt) {
        document.getElementById('assunto').value = txt;
    }
    function aoMudarAnalise(sel) {
        const opt = sel.selectedOptions[0];
        if (!opt || !opt.value) return;
        const txt = opt.textContent.trim();
        const ass = document.getElementById('assunto');
        if (ass && (!ass.value || ass.value.startsWith('Aprovação de Planos') || ass.value.startsWith('Apresentação de Projeto'))) {
            const embNome = document.getElementById('embarcacao_id')?.selectedOptions[0]?.textContent?.trim() || '';
            ass.value = 'Aprovação de Planos e Memoriais - ' + txt.split('(')[0].trim() + (embNome ? ' - ' + embNome.split('·')[0].trim() : '');
        }
    }
    function sincronizarDadosEmbarcacao(select) {
        const opt = select.selectedOptions[0];
        if (opt && opt.dataset.cliente) {
            document.getElementById('cliente_id').value = opt.dataset.cliente;
        }
        const embId = select.value;
        const selAnalise = document.getElementById('analise_id');
        const selVistoria = document.getElementById('vistoria_id');

        for (let i = 1; i < selAnalise.options.length; i++) {
            const o = selAnalise.options[i];
            o.style.display = (!embId || o.dataset.embarcacao === embId) ? '' : 'none';
        }
        for (let i = 1; i < selVistoria.options.length; i++) {
            const o = selVistoria.options[i];
            o.style.display = (!embId || o.dataset.embarcacao === embId) ? '' : 'none';
        }

        const boxPreview = document.getElementById('preview-acervo-resumo');
        if (boxPreview) {
            if (!embId) {
                boxPreview.style.display = 'none';
                boxPreview.innerHTML = '';
            } else {
                boxPreview.style.display = 'block';
                boxPreview.innerHTML = '<span class="text-secondary small"><i class="fa-solid fa-spinner fa-spin text-accent"></i> Localizando acervo documental da embarcação...</span>';
                fetch(`<?= APP_URL ?>protocolos/actions?action=obter_acervo_embarcacao&embarcacao_id=${encodeURIComponent(embId)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.dados && data.dados.resumo) {
                            const r = data.dados.resumo;
                            if (r.total > 0) {
                                let partes = [];
                                if (r.propostas > 0) partes.push(`${r.propostas} Proposta(s)`);
                                if (r.vistorias > 0) partes.push(`${r.vistorias} Vistoria(s)`);
                                if (r.projetos > 0) partes.push(`${r.projetos} Projeto(s)/Plano(s)`);
                                if (r.certificados > 0) partes.push(`${r.certificados} Certificado(s)`);
                                if (r.externos > 0) partes.push(`${r.externos} Anexo(s)`);

                                boxPreview.innerHTML = `
                                    <div class="p-2 rounded small" style="background: rgba(86, 224, 173, 0.08); border: 1px solid var(--accent, #56e0ad); color: var(--text-primary);">
                                        <i class="fa-solid fa-folder-tree text-accent me-1"></i>
                                        <strong>Acervo Assinado Localizado:</strong> <strong>${r.total} documento(s)</strong> disponível(is) (${partes.join(', ')}). Eles estarão prontos para uso no dossiê.
                                    </div>
                                `;
                            } else {
                                boxPreview.innerHTML = `
                                    <div class="p-2 rounded small text-secondary" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border);">
                                        <i class="fa-solid fa-info-circle me-1"></i> Nenhum documento assinado digitalmente vinculado a esta embarcação ainda.
                                    </div>
                                `;
                            }
                        }
                    })
                    .catch(() => {
                        boxPreview.style.display = 'none';
                    });
            }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const embSel = document.getElementById('embarcacao_id');
        if (embSel && embSel.value) {
            const it = listaEmbarcacoes.find(x => x.id === embSel.value);
            if (it) {
                selecionarEmbarcacaoPeloItem(it);
            } else {
                sincronizarDadosEmbarcacao(embSel);
            }
        }
    });
    </script>

<?php else: ?>
    <!-- ========================================== -->
    <!-- MODO DE EDIÇÃO DE DADOS BÁSICOS DO DOSSIÊ   -->
    <!-- ========================================== -->
    <?php if (!$somenteLeitura): ?>
        <div class="collapse mb-4" id="painel-edicao-dossie">
            <div class="card card-body border-accent">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="m-0 text-accent fs-6">
                        <i class="fa-solid fa-pen-to-square"></i> Editar Dados Mestres do Dossiê <?= h($d['numero']) ?>
                    </h4>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="bootstrap.Collapse.getInstance(document.getElementById('painel-edicao-dossie'))?.hide() || document.getElementById('painel-edicao-dossie').classList.remove('show')">✕ Fechar</button>
                </div>

                <form method="post" action="<?= APP_URL ?>protocolos/actions">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="editar_dossie">
                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                    <input type="hidden" name="aba" class="input-aba-ativa" value="<?= h($abaAtiva) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="edit_assunto">Assunto / Finalidade do Processo *</label>
                        <input class="form-control" required maxlength="255" name="assunto" id="edit_assunto" value="<?= h($d['assunto']) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="edit_cliente_id">Cliente / Solicitante</label>
                            <select class="form-control" name="cliente_id" id="edit_cliente_id">
                                <option value="">Sem cliente específico</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= h($c['id']) ?>" <?= $d['cliente_id'] === $c['id'] ? 'selected' : '' ?>>
                                        <?= h($c['nome'] . ($c['cpf_cnpj'] ? ' (' . $c['cpf_cnpj'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="edit_unidade_id">Unidade Marítima (Destino)</label>
                            <select class="form-control" name="unidade_maritima_id" id="edit_unidade_id">
                                <option value="">Não definida</option>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= h($u['id']) ?>" <?= $d['unidade_maritima_id'] === $u['id'] ? 'selected' : '' ?>>
                                        <?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="edit_analise_id">Análise de Planos Vinculada</label>
                            <select class="form-control" name="analise_id" id="edit_analise_id">
                                <option value="">Sem vínculo</option>
                                <?php foreach ($analisesAbertas as $a): ?>
                                    <option value="<?= h($a['id']) ?>" <?= $d['analise_id'] === $a['id'] ? 'selected' : '' ?>>
                                        <?= h($a['numero'] . ' (' . $a['tipo_processo'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="edit_vistoria_id">Vistoria Vinculada</label>
                            <select class="form-control" name="vistoria_id" id="edit_vistoria_id">
                                <option value="">Sem vínculo</option>
                                <?php foreach ($vistoriasAbertas as $v): ?>
                                    <option value="<?= h($v['id']) ?>" <?= $d['vistoria_id'] === $v['id'] ? 'selected' : '' ?>>
                                        <?= h($v['numero'] . ' (' . ($v['finalidade'] ?: 'Vistoria') . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Certificado Vinculado</label>
                            <div class="input-group">
                                <select class="form-control" name="certificado_tipo" style="max-width: 100px;">
                                    <option value="">Tipo...</option>
                                    <option value="CSN" <?= ($d['certificado_tipo'] ?? '') === 'CSN' ? 'selected' : '' ?>>CSN</option>
                                    <option value="CNBL" <?= ($d['certificado_tipo'] ?? '') === 'CNBL' ? 'selected' : '' ?>>CNBL</option>
                                    <option value="CNARQ" <?= ($d['certificado_tipo'] ?? '') === 'CNARQ' ? 'selected' : '' ?>>CNARQ</option>
                                    <option value="LP" <?= ($d['certificado_tipo'] ?? '') === 'LP' ? 'selected' : '' ?>>LP</option>
                                    <option value="LC" <?= ($d['certificado_tipo'] ?? '') === 'LC' ? 'selected' : '' ?>>LC</option>
                                    <option value="CHT" <?= ($d['certificado_tipo'] ?? '') === 'CHT' ? 'selected' : '' ?>>CHT</option>
                                </select>
                                <input class="form-control" name="certificado_id" value="<?= h($d['certificado_id'] ?? '') ?>" placeholder="ID ou Nº Certificado">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações nos Vínculos
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
