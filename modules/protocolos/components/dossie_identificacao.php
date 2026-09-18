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
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="embarcacao_id">Embarcação *</label>
                        <select class="form-control" required name="embarcacao_id" id="embarcacao_id" onchange="sincronizarDadosEmbarcacao(this)">
                            <option value="">-- Selecione a embarcação --</option>
                            <?php foreach ($embarcacoes as $e): ?>
                                <option value="<?= h($e['id']) ?>" 
                                        data-cliente="<?= h($e['cliente_id']) ?>" 
                                        <?= $preEmb === $e['id'] ? 'selected' : '' ?>>
                                    <?= h($e['nome'] . ($e['registro'] ? ' · ' . $e['registro'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">A embarcação vinculada determina o histórico técnico, vistorias e armador responsável.</small>
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
    }
    document.addEventListener('DOMContentLoaded', function() {
        const embSel = document.getElementById('embarcacao_id');
        if (embSel && embSel.value) sincronizarDadosEmbarcacao(embSel);
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
