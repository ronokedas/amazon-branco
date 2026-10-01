<?php
/**
 * Componente: Central de Acervo Documental e Versões da Embarcação
 * Local: modules/protocolos/components/acervo_documental.php
 *
 * Responsável por listar, filtrar e disponibilizar todos os documentos
 * gerados e vinculados à embarcação (Propostas, Vistorias com multi-versões,
 * Projetos e Pranchas de Engenharia por revisão REV, Pareceres Técnicos,
 * Certificados Navais e Documentos Externos anexados).
 */
?>

<!-- ============================================== -->
<!-- ABA ACERVO: CENTRAL DE DOCUMENTOS DA EMBARCAÇÃO -->
<!-- ============================================== -->
<div id="pane-acervo" class="prot-tab-pane <?= $abaAtiva === 'acervo' ? 'active' : '' ?>">
    <section class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="m-0">
                        <i class="fa-solid fa-folder-tree"></i> Central de Acervo Documental da Embarcação
                    </h3>
                    <p class="text-secondary small mb-0">
                        Todos os documentos, relatórios com histórico de revisões e certificados oficiais vinculados à embarcação <strong><?= h($d['embarcacao_nome'] ?? '') ?></strong>.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!$somenteLeitura): ?>
                        <button type="button" class="btn btn-sm btn-primary" onclick="importarSelecionadosAcervo()">
                            <i class="fa-solid fa-file-import"></i> Importar Selecionados na Movimentação
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="recarregarAcervoEmbarcacao()">
                        <i class="fa-solid fa-arrows-rotate"></i> Atualizar Acervo
                    </button>
                </div>
            </div>

            <!-- Caixa Informativa Autodidática (Padrão AGENTS.md) -->
            <div class="prot-helper-box info mb-3" style="background: rgba(86, 224, 173, 0.06); border: 1px solid rgba(86, 224, 173, 0.25);">
                <i class="fa-solid fa-circle-info text-accent fs-5 me-2"></i>
                <div class="small">
                    <strong>Gestão de Versões e Fé Pública Naval (NORMAM):</strong><br>
                    Esta central identifica automaticamente propostas comerciais, cada revisão de relatório de vistoria (vistoria inicial e retornos para baixa de exigências), pranchas e memoriais de cálculo do plano de engenharia por revisão (REV 00, REV 01...), e todos os certificados emitidos. Você pode visualizar o PDF original em 1 clique ou selecioná-los para compor o protocolo oficial de entrega ao armador/despachante.
                </div>
            </div>

            <!-- Filtros em Pílulas (Pills / Chips) -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="d-flex flex-wrap gap-1" id="acervo-filtro-chips">
                    <button type="button" class="btn btn-sm btn-outline-secondary active py-1 px-3" data-filtro="TODOS" onclick="filtrarAcervo('TODOS', this)">
                        <i class="fa-solid fa-list-check"></i> Todos (<span id="acervo-count-total"><?= count($acervoEmbarcacao['itens'] ?? []) ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-filtro="PROPOSTAS" onclick="filtrarAcervo('PROPOSTAS', this)">
                        <i class="fa-solid fa-file-invoice-dollar text-success"></i> Propostas (<span id="acervo-count-propostas"><?= $acervoEmbarcacao['resumo']['propostas'] ?? 0 ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-filtro="VISTORIAS" onclick="filtrarAcervo('VISTORIAS', this)">
                        <i class="fa-solid fa-clipboard-check text-info"></i> Vistorias em Campo (<span id="acervo-count-vistorias"><?= $acervoEmbarcacao['resumo']['vistorias'] ?? 0 ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-filtro="PROJETOS" onclick="filtrarAcervo('PROJETOS', this)">
                        <i class="fa-solid fa-compass-drafting text-warning"></i> Engenharia & Planos (<span id="acervo-count-projetos"><?= $acervoEmbarcacao['resumo']['projetos'] ?? 0 ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-filtro="CERTIFICADOS" onclick="filtrarAcervo('CERTIFICADOS', this)">
                        <i class="fa-solid fa-certificate text-danger"></i> Certificados & Licenças (<span id="acervo-count-certificados"><?= $acervoEmbarcacao['resumo']['certificados'] ?? 0 ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-filtro="EXTERNOS" onclick="filtrarAcervo('EXTERNOS', this)">
                        <i class="fa-solid fa-paperclip text-secondary"></i> Anexos Externos (<span id="acervo-count-externos"><?= $acervoEmbarcacao['resumo']['externos'] ?? 0 ?></span>)
                    </button>
                </div>

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control form-control-sm" id="acervo-busca" placeholder="Buscar documento ou versão..." onkeyup="buscarNoAcervo(this.value)">
                    </div>
                </div>
            </div>

            <!-- Tabela / Lista de Documentos do Acervo -->
            <?php if (empty($acervoEmbarcacao['itens'])): ?>
                <div class="text-center py-5 text-secondary" id="acervo-vazio">
                    <i class="fa-regular fa-folder-open fa-3x mb-3 opacity-50"></i>
                    <h5>Nenhum documento encontrado</h5>
                    <p class="small mb-0">Nenhum relatório, proposta ou certificado vinculado à embarcação foi localizado no sistema até o momento.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="tabela-acervo" style="font-size: 0.86rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th style="width: 38px;">
                                    <input type="checkbox" id="acervo-select-all" title="Selecionar todos os visíveis" onchange="selecionarTodosAcervo(this)">
                                </th>
                                <th>Categoria & Documento</th>
                                <th>Versão / Revisão</th>
                                <th>Data / Vigência</th>
                                <th>Situação</th>
                                <th>Detalhes / Referência</th>
                                <th style="text-align: right; min-width: 170px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="acervo-lista-corpo">
                            <?php foreach ($acervoEmbarcacao['itens'] as $it): ?>
                                <?php
                                $badgeGrupoCor = match($it['categoria_grupo']) {
                                    'PROPOSTAS' => 'background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);',
                                    'VISTORIAS' => 'background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);',
                                    'PROJETOS' => 'background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);',
                                    'CERTIFICADOS' => 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);',
                                    default => 'background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);',
                                };
                                $itemJson = json_encode($it, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE);
                                ?>
                                <tr class="item-linha-acervo" 
                                    data-grupo="<?= h($it['categoria_grupo']) ?>" 
                                    data-id="<?= h($it['id']) ?>"
                                    data-item='<?= $itemJson ?>'
                                    style="border-bottom: 1px solid var(--border);">
                                    <td>
                                        <input type="checkbox" class="chk-item-acervo" value="<?= h($it['id']) ?>" onchange="atualizarBotaoImportar()">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" style="<?= $badgeGrupoCor ?> font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                                <?= h($it['categoria_rotulo']) ?>
                                            </span>
                                            <div>
                                                <strong class="d-block text-accent" style="font-size: 0.92rem;"><?= h($it['titulo']) ?></strong>
                                                <?php if (!empty($it['numero'])): ?>
                                                    <span class="text-secondary small">Nº: <?= h($it['numero']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary" style="font-size: 0.76rem; font-weight: 600; color: #f3f4f6;">
                                            <i class="fa-solid fa-code-branch text-accent me-1"></i> <?= h($it['versao_label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fa-regular fa-calendar text-secondary"></i> 
                                            <?= $it['data_documento'] ? date('d/m/Y', strtotime($it['data_documento'])) : '—' ?>
                                        </div>
                                        <?php if (!empty($it['data_validade'])): ?>
                                            <div class="small text-secondary mt-1">
                                                Validade: <strong class="text-warning"><?= date('d/m/Y', strtotime($it['data_validade'])) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary" style="font-size: 0.72rem;">
                                            <?= h($it['status_label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-secondary small"><?= h($it['detalhes'] ?? '—') ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-outline-info" target="_blank" rel="noopener" href="<?= h($it['url_pdf']) ?>" title="Visualizar ou baixar PDF original">
                                                <i class="fa-solid fa-file-pdf"></i> Ver PDF
                                            </a>
                                            <?php if (!$somenteLeitura): ?>
                                                <button type="button" class="btn btn-primary" onclick="importarItemAcervoDireto('<?= h($it['id']) ?>')" title="Adicionar documento à lista da próxima movimentação">
                                                    <i class="fa-solid fa-plus"></i> Importar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
// Dados globais do Acervo da Embarcação
window.acervoDocumentalItens = <?= json_encode($acervoEmbarcacao['itens'] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>;

function filtrarAcervo(grupo, btn) {
    document.querySelectorAll('#acervo-filtro-chips button').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const linhas = document.querySelectorAll('.item-linha-acervo');
    linhas.forEach(linha => {
        if (grupo === 'TODOS' || linha.dataset.grupo === grupo) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
}

function buscarNoAcervo(termo) {
    const termoClean = termo.trim().toLowerCase();
    const linhas = document.querySelectorAll('.item-linha-acervo');
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termoClean) ? '' : 'none';
    });
}

function selecionarTodosAcervo(master) {
    const visiveis = document.querySelectorAll('.item-linha-acervo:not([style*="display: none"]) .chk-item-acervo');
    visiveis.forEach(chk => {
        chk.checked = master.checked;
    });
    atualizarBotaoImportar();
}

function atualizarBotaoImportar() {
    const selecionados = document.querySelectorAll('.chk-item-acervo:checked');
    const btn = document.getElementById('btn-importar-selecionados');
    if (btn) {
        btn.textContent = `Importar Selecionados (${selecionados.length})`;
        btn.disabled = selecionados.length === 0;
    }
}

function importarItemAcervoObjeto(item) {
    if (!item || typeof addDoc !== 'function') return;
    
    // Suporte e Forma inteligente
    const suporte = item.suporte || 'DIGITAL';
    const forma = item.forma || (item.status === 'ASSINADO' || item.status === 'ASSINADA' ? 'NATO_DIGITAL' : 'DIGITALIZADO');
    const revisao = item.versao_label || '';

    addDoc('', item.titulo, {
        suporte: suporte,
        forma: forma,
        categoria: item.categoria_grupo || 'OUTROS',
        arquivo_origem_tipo: item.origem_tipo,
        arquivo_origem_id: item.origem_id,
        arquivo_nome: item.nome_arquivo || item.titulo,
        arquivo_hash: item.hash || '',
        revisao: revisao
    });
}

function importarItemAcervoDireto(itemId) {
    const item = window.acervoDocumentalItens.find(x => x.id === itemId);
    if (!item) return;

    // Muda para a aba de movimentação e adiciona o documento
    trocarAbaDossie('movimentacao');
    importarItemAcervoObjeto(item);

    // Feedback visual
    alert(`Documento "${item.titulo}" adicionado à lista da nova movimentação!`);
}

function importarSelecionadosAcervo() {
    const chks = document.querySelectorAll('.chk-item-acervo:checked');
    if (!chks.length) {
        alert('Selecione pelo menos um documento do acervo para importar.');
        return;
    }

    trocarAbaDossie('movimentacao');
    let importados = 0;
    chks.forEach(chk => {
        const item = window.acervoDocumentalItens.find(x => x.id === chk.value);
        if (item) {
            importarItemAcervoObjeto(item);
            importados++;
        }
    });

    alert(`${importados} documento(s) do acervo foram inseridos na nova movimentação.`);
}

function recarregarAcervoEmbarcacao() {
    const embId = <?= json_encode($d['embarcacao_id'] ?? '') ?>;
    const dossieId = <?= json_encode($id ?? '') ?>;
    if (!embId) return;

    fetch(`<?= APP_URL ?>protocolos/actions?action=obter_acervo_embarcacao&embarcacao_id=${encodeURIComponent(embId)}&dossie_id=${encodeURIComponent(dossieId)}`)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso && data.dados) {
                window.location.reload();
            } else {
                alert('Erro ao atualizar acervo: ' + (data.erro || 'Falha na resposta do servidor.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Não foi possível atualizar o acervo.');
        });
}
</script>
