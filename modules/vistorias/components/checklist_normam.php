            <!-- ===== CHECKLIST DINAMICO ===== -->
            <section class="checklist-editor-section report-work-card" id="checklist-editor-section">
                <div class="report-section-heading">
                    <div><i class="fas fa-clipboard-check"></i><span><strong>Checklist de Vistoria</strong><small>Classifique cada item como Conforme, Não Conforme ou N/A.</small></span></div>
                </div>

                <div class="checklist-summary" aria-live="polite">
                    <div><span class="checklist-summary-icon is-progress"><i class="fas fa-list-check"></i></span><span><small>Respondidos</small><strong id="checklistRespondidos">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-conforme"><i class="fas fa-circle-check"></i></span><span><small>Conformes</small><strong id="checklistConformes">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-pending"><i class="fas fa-clock"></i></span><span><small>Pendentes</small><strong id="checklistPendentes">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-danger"><i class="fas fa-triangle-exclamation"></i></span><span><small>Não conformes</small><strong id="checklistNaoConformes">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-as"><i class="fas fa-ban"></i></span><span><small>Exigências A/S</small><strong id="checklistAS">0</strong></span></div>
                </div>

                <!-- Barra de Ferramentas e Filtros Rápidos do Vistoriador -->
                <div class="checklist-toolbar">
                    <div class="checklist-toolbar-left">
                        <button type="button" class="btn-tool" onclick="expandirTodasCategorias()" title="Abrir todas as categorias do checklist">
                            <i class="fas fa-angles-down"></i> Expandir Todas
                        </button>
                        <button type="button" class="btn-tool" onclick="recolherTodasCategorias()" title="Recolher todas as categorias do checklist">
                            <i class="fas fa-angles-up"></i> Recolher Todas
                        </button>
                    </div>
                    <div class="checklist-filter-pills" role="tablist" aria-label="Filtro rápido do checklist">
                        <button type="button" class="btn-filter-pill active" data-filter="todos" onclick="filtrarChecklistStatus('todos', this)">
                            <i class="fas fa-list-check"></i> Todos
                        </button>
                        <button type="button" class="btn-filter-pill is-pending" data-filter="pendentes" onclick="filtrarChecklistStatus('pendentes', this)">
                            <i class="fas fa-clock"></i> Só Pendentes <span class="pill-badge" id="pillPendentes">0</span>
                        </button>
                        <button type="button" class="btn-filter-pill is-conforme" data-filter="conforme" onclick="filtrarChecklistStatus('conforme', this)">
                            <i class="fas fa-circle-check"></i> Conformes <span class="pill-badge" id="pillConformes">0</span>
                        </button>
                        <button type="button" class="btn-filter-pill is-danger" data-filter="nao_conforme" onclick="filtrarChecklistStatus('nao_conforme', this)">
                            <i class="fas fa-triangle-exclamation"></i> Exigências <span class="pill-badge" id="pillNaoConformes">0</span>
                        </button>
                        <button type="button" class="btn-filter-pill" data-filter="na" onclick="filtrarChecklistStatus('na', this)">
                            <i class="fas fa-ban"></i> N/A <span class="pill-badge" id="pillNA">0</span>
                        </button>
                    </div>
                </div>

                <div class="checklist-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <label class="sr-only" for="buscaChecklist">Pesquisar item do checklist</label>
                    <input type="search" id="buscaChecklist" class="form-control" placeholder="Pesquisar exigência, descrição ou referência NORMAM...">
                </div>

                <div id="checklist-container">
                    <?php foreach ($checklist_categorias as $cat): ?>
                    <div class="checklist-section" data-cat="<?= $cat['id'] ?>" data-total="<?= count($cat['itens']) ?>">
                        <button type="button" class="checklist-header" aria-expanded="false" aria-controls="cat_<?= $cat['id'] ?>" onclick="toggleSection('cat_<?= $cat['id'] ?>', this)">
                            <span class="checklist-category-name"><?= h($cat['nome']) ?></span>
                            <span class="checklist-category-metrics">
                                <span class="category-progress"><b data-counter="respondidos">0</b>/<?= count($cat['itens']) ?> respondidos</span>
                                <span class="category-issues" data-badge="exigencias"><b data-counter="exigencias">0</b> exigências</span>
                                <span class="category-as is-hidden" data-badge="as"><b data-counter="as">0</b> A/S</span>
                            </span>
                            <i class="fas fa-chevron-down icone-toggle"></i>
                        </button>
                        <div class="checklist-body" id="cat_<?= $cat['id'] ?>">
                            <?php foreach ($cat['itens'] as $item):
                                $resp = $checklist_respostas[$item['id']] ?? null;
                                $status = $resp['status'] ?? '';
                                $obs = $resp['observacao'] ?? '';
                                $venc = $resp['vencimento'] ?? '';
                                $semPrazo = ($status === 'NAO_CONFORME' && !empty($resp['sem_prazo']));
                            ?>
                            <div class="checklist-item" data-id="<?= $item['id'] ?>" data-text="<?= htmlspecialchars(strtolower($item['codigo_interno'] . ' ' . $item['descricao'] . ' ' . $item['item_normam'])) ?>">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                    <span class="item-codigo" style="font-family: monospace; font-weight: 700; color: #0f172a; font-size: 11.5px;"><?= h($item['codigo_interno']) ?></span>
                                </div>
                                <div class="item-text"><?= h($item['descricao']) ?></div>
                                <?php if($item['item_normam']): ?>
                                    <span class="item-normam">Normam: <?= h($item['item_normam']) ?></span>
                                <?php endif; ?>

                                <div class="item-actions">
                                    <button type="button" class="btn-toggle conforme <?= $status === 'CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'CONFORME', this)">CONFORME</button>
                                    <button type="button" class="btn-toggle nao-conforme <?= $status === 'NAO_CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_CONFORME', this)">NÃO CONFORME</button>
                                    <button type="button" class="btn-toggle na <?= $status === 'NAO_SE_APLICA' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_SE_APLICA', this)">N/A</button>
                                </div>

                                <input type="hidden" name="checklist_id[]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="checklist_status[]" id="status_<?= $item['id'] ?>" value="<?= h($status) ?>">

                                <div class="item-details" id="details_<?= $item['id'] ?>" style="display: <?= $status === 'NAO_CONFORME' ? 'block' : 'none' ?>;">
                                    <label>Referência da NORMAM (Sobrescreve o padrão do catálogo)</label>
                                    <input type="text" name="checklist_item_normam[]" id="normam_<?= $item['id'] ?>" value="<?= h($resp['item_normam'] ?? $item['item_normam'] ?? '') ?>" placeholder="Ex: NORMAM-202/DPC, Cap. 02, Item 2.1.">

                                    <label>Observação curta (vai para o relatório)</label>
                                    <input type="text" name="checklist_observacao[]" id="obs_<?= $item['id'] ?>" value="<?= h($obs) ?>" placeholder="Especifique o problema encontrado...">

                                    <input type="hidden" name="checklist_sem_prazo[]" id="sem_prazo_<?= $item['id'] ?>" value="<?= $semPrazo ? '1' : '0' ?>">
                                    <label style="display:flex; align-items:center; gap:8px; margin-top: 10px;">
                                        <input type="checkbox"
                                               class="checklist-sem-prazo"
                                               name="checklist_sem_prazo_por_id[<?= h($item['id']) ?>]"
                                               value="1"
                                               data-target="sem_prazo_<?= $item['id'] ?>"
                                               <?= $semPrazo ? 'checked' : '' ?>>
                                        A/S — Antes de suspender
                                    </label>
                                </div>

                                <!-- Bloco de Evidência Fotográfica da Norma (Múltiplas Fotos) -->
                                <div class="item-foto-box" id="foto_box_<?= $item['id'] ?>" style="display: <?= in_array($status, ['CONFORME', 'NAO_CONFORME']) ? 'block' : 'none' ?>; margin-top: 10px; padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                        <label style="margin: 0; font-size: 11.5px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-camera" style="color: #64748b;"></i>
                                            Fotos da Evidência <span style="color: #64748b; font-size: 11px;">(Múltiplas fotos)</span>
                                        </label>
                                        <span id="foto_badge_<?= $item['id'] ?>" style="font-size: 11px; color: <?= !empty($fotos_por_catalogo[$item['id']]) ? '#15803d' : '#64748b' ?>; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid <?= !empty($fotos_por_catalogo[$item['id']]) ? 'fa-circle-check' : 'fa-images' ?>"></i>
                                            <span id="foto_count_<?= $item['id'] ?>"><?= count($fotos_por_catalogo[$item['id']] ?? []) ?></span> foto(s) anexada(s)
                                        </span>
                                    </div>

                                    <!-- Fotos já salvas na vistoria com opção de exclusão individual -->
                                    <div id="fotos_existentes_<?= $item['id'] ?>" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: <?= !empty($fotos_por_catalogo[$item['id']]) ? '8px' : '0' ?>;">
                                        <?php if (!empty($fotos_por_catalogo[$item['id']])): ?>
                                            <?php foreach ($fotos_por_catalogo[$item['id']] as $fotoExistente): ?>
                                                <?php $fotoUrl = APP_URL . 'api/campo/v1/anexos/' . rawurlencode((string)$fotoExistente['id']); ?>
                                                <div class="foto-thumb-card" id="foto_card_<?= h($fotoExistente['id']) ?>" style="position: relative; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; width: 68px; height: 68px; background: #0f172a;" title="<?= h($fotoExistente['nome_original'] ?: 'Evidência salva') ?>">
                                                    <a href="<?= h($fotoUrl) ?>" target="_blank" rel="noopener noreferrer">
                                                        <img src="<?= h($fotoUrl) ?>" alt="Evidência" style="width: 100%; height: 100%; object-fit: cover;">
                                                    </a>
                                                    <button type="button" 
                                                            onclick="excluirFotoExistente('<?= h($fotoExistente['id']) ?>', '<?= h($item['id']) ?>', this)" 
                                                            title="Excluir esta foto"
                                                            style="position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; border-radius: 50%; background: rgba(220, 38, 38, 0.95); color: #fff; border: none; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                                                        <i class="fa-solid fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Previews de novas fotos adicionadas antes de salvar -->
                                    <div id="foto_preview_<?= $item['id'] ?>" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;"></div>

                                    <!-- Botões para tirar foto com a câmera do celular ou adicionar da galeria (acumula sem perder) -->
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <!-- Input Master que envia no formulário POST -->
                                        <input type="file"
                                               name="checklist_foto[<?= h($item['id']) ?>][]"
                                               id="foto_input_<?= $item['id'] ?>"
                                               accept="image/*"
                                               multiple
                                               data-item-id="<?= h($item['id']) ?>"
                                               data-ja-tem-foto="<?= !empty($fotos_por_catalogo[$item['id']]) ? '1' : '0' ?>"
                                               class="checklist-foto-input"
                                               style="display: none;">

                                        <!-- Input para Câmera Direta do Celular (Android / iPhone) -->
                                        <input type="file"
                                               id="foto_camera_<?= $item['id'] ?>"
                                               accept="image/*"
                                               capture="environment"
                                               style="display: none;"
                                               onchange="adicionarFotosItem('<?= $item['id'] ?>', this)">

                                        <!-- Input para Galeria / Múltiplas Fotos -->
                                        <input type="file"
                                               id="foto_galeria_<?= $item['id'] ?>"
                                               accept="image/*"
                                               multiple
                                               style="display: none;"
                                               onchange="adicionarFotosItem('<?= $item['id'] ?>', this)">

                                        <button type="button" 
                                                class="btn btn-sm"
                                                onclick="abrirCameraItem('<?= $item['id'] ?>')"
                                                title="Abrir a câmera do smartphone para fotografar a evidência agora"
                                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; padding: 6px 13px; border-radius: 6px; font-weight: 700; cursor: pointer; background: #0284c7; border: 1px solid #0284c7; color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                            <i class="fa-solid fa-camera"></i> Tirar Foto (Câmera)
                                        </button>

                                        <button type="button" 
                                                class="btn btn-sm"
                                                onclick="abrirGaleriaItem('<?= $item['id'] ?>')"
                                                title="Escolher fotos existentes na galeria do aparelho"
                                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; background: #fff; border: 1px solid #cbd5e1; color: #334155;">
                                            <i class="fa-solid fa-images"></i> Galeria
                                        </button>

                                        <span style="font-size: 11px; color: #64748b;">
                                            Fotografe direto pela câmera ou anexe da galeria (acumulativas).
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="checklistSemResultados" class="checklist-no-results is-hidden"><i class="fas fa-search"></i><strong>Nenhum item encontrado</strong><span>Tente pesquisar usando outra palavra ou referência.</span></div>
            </section>

<!-- Botão Flutuante Simples: Voltar às Categorias / Topo -->
<style>
.btn-voltar-categorias-fab {
    position: fixed !important;
    bottom: 24px !important;
    right: 24px !important;
    z-index: 999999 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    padding: 10px 18px !important;
    border-radius: 50px !important;
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45), 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    font-weight: 700 !important;
    font-size: 0.88rem !important;
    cursor: pointer !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease !important;
}

.btn-voltar-categorias-fab:hover {
    transform: translateY(-3px) scale(1.03) !important;
    box-shadow: 0 10px 25px rgba(5, 150, 105, 0.55), 0 4px 12px rgba(0, 0, 0, 0.4) !important;
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}
</style>

<button type="button" 
        id="btn-voltar-categorias-fab" 
        class="btn-voltar-categorias-fab" 
        onclick="scrollToTopChecklist()" 
        title="Voltar ao Topo do Checklist / Categorias">
    <i class="fa-solid fa-arrow-up"></i>
    <span>Voltar às Categorias</span>
</button>

<script>
function scrollToTopChecklist() {
    try {
        var target = document.getElementById('checklist-editor-section') || document.querySelector('.checklist-editor-section');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (e) {}
}
window.scrollToTopChecklist = scrollToTopChecklist;
</script>
