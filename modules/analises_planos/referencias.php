<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

analisePlanosExigirAcesso();

$filtro_categoria = trim($_GET['categoria'] ?? '');
$filtro_busca = trim($_GET['busca'] ?? '');

$referencias = analisePlanosBuscarReferenciasNormam($pdo, [
    'categoria' => $filtro_categoria,
    'busca' => $filtro_busca,
]);

$categoriasPadrao = analisePlanosCategoriasNormam();

// KPIs
$totalReferencias = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam WHERE ativo=1")->fetchColumn();
$totalCategorias = (int)$pdo->query("SELECT COUNT(DISTINCT categoria) FROM analise_planos_referencias_normam WHERE ativo=1")->fetchColumn();
$normam202Count = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam WHERE ativo=1 AND norma='NORMAM-202'")->fetchColumn();

$titulo_pagina = 'Banco de Referências da NORMAM - Analista Naval';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper" style="padding: 20px 24px;">
    <!-- Topo da página -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <a href="<?= APP_URL ?>analises-planos" class="btn btn-sm btn-outline-secondary" title="Voltar para Análise de Planos">
                    <i class="fa-solid fa-arrow-left"></i> Voltar
                </a>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
                    <i class="fa-solid fa-book-bookmark text-primary"></i> Banco de Normas & Referências NORMAM
                </h1>
            </div>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0;">
                Catálogo técnico de exigências e normas navais do Analista Naval (DPC / NORMAM-202 / RIPEAM). Utilizado na emissão de RAPs e pareceres.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="abrirModalReferencia()" style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus"></i> Nova Referência NORMAM
            </button>
        </div>
    </div>

    <!-- Cards de Indicadores (KPIs) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="width: 44px; height: 44px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
                <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #64748b; display: block;">Total no Banco</span>
                <strong style="font-size: 1.4rem; color: #0f172a;"><?= $totalReferencias ?></strong>
                <small style="display: block; font-size: 0.72rem; color: #10b981;"><i class="fa-solid fa-check"></i> Acervo ativo</small>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="width: 44px; height: 44px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #64748b; display: block;">Categorias Técnicas</span>
                <strong style="font-size: 1.4rem; color: #0f172a;"><?= $totalCategorias ?></strong>
                <small style="display: block; font-size: 0.72rem; color: #64748b;">Pranchas & Memoriais</small>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="width: 44px; height: 44px; border-radius: 8px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                <i class="fa-solid fa-anchor"></i>
            </div>
            <div>
                <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #64748b; display: block;">Base NORMAM-202</span>
                <strong style="font-size: 1.4rem; color: #0f172a;"><?= $normam202Count ?></strong>
                <small style="display: block; font-size: 0.72rem; color: #15803d;"><i class="fa-solid fa-shield-halved"></i> DPC Marinha do Brasil</small>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros e Busca Rápida -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form method="get" action="<?= APP_URL ?>analises-planos/referencias" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 240px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar por palavra-chave ou norma
                </label>
                <input type="text" name="busca" value="<?= h($filtro_busca) ?>" class="form-control" placeholder="Ex.: passadiço, boias, machado, trim, borda livre...">
            </div>

            <div style="flex: 1; min-width: 220px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                    <i class="fa-solid fa-filter"></i> Filtrar por Categoria Documental
                </label>
                <select name="categoria" class="form-control">
                    <option value="">Todas as Categorias (<?= $totalReferencias ?>)</option>
                    <?php foreach ($categoriasPadrao as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $filtro_categoria === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 38px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <?php if ($filtro_busca !== '' || $filtro_categoria !== ''): ?>
                    <a href="<?= APP_URL ?>analises-planos/referencias" class="btn btn-outline-secondary" style="height: 38px;" title="Limpar filtros">
                        <i class="fa-solid fa-xmark"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Atalhos Rápidos por Categoria (Chips/Pills) -->
        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
            <span style="font-size: 0.74rem; font-weight: 600; color: #64748b; margin-right: 4px;">Atalhos rápidos:</span>
            <?php 
            $categoriasAtalhos = ['GERAL', 'MEMORIAL DESCRITO', 'NOTAS DE ARQUEAÇÃO', 'NOTAS DE BORDA LIVRE', 'ESTUDO DE ESTABILIDADE', 'PLANOS DE LINHAS', 'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE'];
            foreach ($categoriasAtalhos as $catAtalho): 
            ?>
                <a href="<?= APP_URL ?>analises-planos/referencias?categoria=<?= urlencode($catAtalho) ?>" 
                   class="btn btn-sm <?= $filtro_categoria === $catAtalho ? 'btn-primary' : 'btn-outline-secondary' ?>"
                   style="font-size: 0.72rem; padding: 2px 8px; border-radius: 12px;">
                    <?= h($catAtalho) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabela de Referências -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <strong style="font-size: 0.95rem; color: #1e293b;">
                <i class="fa-solid fa-list text-primary"></i> Referências Encontradas (<?= count($referencias) ?>)
            </strong>
            <small class="text-muted">Clique em "Editar" para atualizar a redação ou a norma.</small>
        </div>

        <?php if (empty($referencias)): ?>
            <div style="padding: 40px 20px; text-align: center; color: #64748b;">
                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: #334155;">Nenhuma referência encontrada</h3>
                <p style="font-size: 0.85rem; margin: 0;">Tente remover os filtros ou cadastre uma nova referência normativa.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" style="margin: 0; font-size: 0.85rem;">
                    <thead style="background: #f8fafc; color: #475569; font-size: 0.78rem; text-transform: uppercase;">
                        <tr>
                            <th style="width: 140px; padding: 10px 14px;">Categoria</th>
                            <th style="width: 220px; padding: 10px 14px;">Referência Normativa</th>
                            <th style="padding: 10px 14px;">Título & Descrição da Exigência</th>
                            <th style="width: 120px; text-align: center; padding: 10px 14px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referencias as $ref): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px; vertical-align: top;">
                                    <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.72rem; padding: 4px 8px; border-radius: 4px; display: inline-block; word-break: break-word;">
                                        <?= h($ref['categoria']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: top;">
                                    <strong style="color: #0f172a; display: block; font-size: 0.82rem;">
                                        <i class="fa-solid fa-scale-balanced text-primary" style="font-size: 0.75rem;"></i> <?= h($ref['referencia_normativa']) ?>
                                    </strong>
                                    <?php if (!empty($ref['norma'])): ?>
                                        <small style="color: #64748b; font-size: 0.72rem;"><?= h($ref['norma']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: top;">
                                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 3px;">
                                        <?= h($ref['titulo']) ?>
                                    </div>
                                    <div style="color: #475569; font-size: 0.8rem; line-height: 1.4;">
                                        <?= h($ref['descricao_padrao']) ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: top; text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center;">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary" 
                                                title="Editar Referência"
                                                onclick='editarReferencia(<?= json_encode($ref, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="post" action="<?= APP_URL ?>analises-planos/referencias-actions" onsubmit="return confirm('Deseja realmente excluir esta referência normativa?');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                            <input type="hidden" name="action" value="excluir">
                                            <input type="hidden" name="id" value="<?= h($ref['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Cadastro e Edição de Referência NORMAM -->
<div id="modalReferencia" class="modal" tabindex="-1" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; width: 100%; max-width: 650px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 id="modalTitulo" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a;">
                <i class="fa-solid fa-book-bookmark text-primary"></i> Nova Referência NORMAM
            </h3>
            <button type="button" onclick="fecharModalReferencia()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="post" action="<?= APP_URL ?>analises-planos/referencias-actions" style="padding: 20px;">
            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
            <input type="hidden" name="action" value="salvar">
            <input type="hidden" name="id" id="ref_id" value="">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Categoria Documental *
                    </label>
                    <select name="categoria" id="ref_categoria" class="form-control" required>
                        <?php foreach ($categoriasPadrao as $cat): ?>
                            <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted" style="font-size: 0.72rem;">Prancha ou memorial associado.</small>
                </div>

                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Norma Regulamentadora *
                    </label>
                    <input type="text" name="norma" id="ref_norma" class="form-control" value="NORMAM-202" required placeholder="Ex.: NORMAM-202">
                    <small class="text-muted" style="font-size: 0.72rem;">Ex.: NORMAM-202, RIPEAM-72, etc.</small>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                    Referência Formal da Norma (Artigo / Anexo / Item) *
                </label>
                <input type="text" name="referencia_normativa" id="ref_referencia_normativa" class="form-control" required placeholder="Ex.: NORMAM-202, Item 3, b) do Anexo 3-F.">
                <small class="text-muted" style="font-size: 0.72rem;">Texto exato da referência que será impresso na coluna REFERÊNCIA do laudo.</small>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                    Título Curto / Assunto da Exigência *
                </label>
                <input type="text" name="titulo" id="ref_titulo" class="form-control" required placeholder="Ex.: Ângulo de visibilidade no passadiço">
                <small class="text-muted" style="font-size: 0.72rem;">Usado para localizar a exigência com facilidade no formulário de análise.</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                    Descrição Padrão da Exigência Técnica *
                </label>
                <textarea name="descricao_padrao" id="ref_descricao_padrao" class="form-control" rows="4" required placeholder="Ex.: Apresentar ângulo de visibilidade no passadiço conforme parâmetros regulamentares..."></textarea>
                <small class="text-muted" style="font-size: 0.72rem;">Texto modelo que será sugerido ao analista ao criar novas exigências.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                <button type="button" class="btn btn-outline-secondary" onclick="fecharModalReferencia()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar Referência
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalReferencia() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-book-bookmark text-primary"></i> Nova Referência NORMAM';
    document.getElementById('ref_id').value = '';
    document.getElementById('ref_categoria').value = 'GERAL';
    document.getElementById('ref_norma').value = 'NORMAM-202';
    document.getElementById('ref_referencia_normativa').value = '';
    document.getElementById('ref_titulo').value = '';
    document.getElementById('ref_descricao_padrao').value = '';
    document.getElementById('modalReferencia').style.display = 'flex';
}

function editarReferencia(ref) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square text-primary"></i> Editar Referência NORMAM';
    document.getElementById('ref_id').value = ref.id || '';
    document.getElementById('ref_categoria').value = ref.categoria || 'GERAL';
    document.getElementById('ref_norma').value = ref.norma || 'NORMAM-202';
    document.getElementById('ref_referencia_normativa').value = ref.referencia_normativa || '';
    document.getElementById('ref_titulo').value = ref.titulo || '';
    document.getElementById('ref_descricao_padrao').value = ref.descricao_padrao || '';
    document.getElementById('modalReferencia').style.display = 'flex';
}

function fecharModalReferencia() {
    document.getElementById('modalReferencia').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
