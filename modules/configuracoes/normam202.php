<?php
/**
 * MÓDULO: CONFIGURAÇÕES - GERENCIADOR NORMAM-202 & CHECKLIST
 * Arquivo: modules/configuracoes/normam202.php
 * Gestão Administrativa de Exigências da NORMAM-202, Obrigatoriedades e Evidências Fotográficas
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('configuracoes');

$titulo_page = 'Catálogo de Exigências NORMAM-202 - Configurações';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Filtros da consulta
$filtro_busca = trim($_GET['busca'] ?? '');
$filtro_categoria = trim($_GET['categoria_id'] ?? '');
$filtro_bloco = trim($_GET['bloco'] ?? '');
$filtro_tipo = trim($_GET['tipo'] ?? ''); // 'todas', 'obrigatorias', 'fotos', 'inativas'

// Obter categorias para os filtros e modal
$stmtCategorias = $pdo->query("SELECT id, nome FROM exigencias_categorias ORDER BY nome ASC");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Métricas do Topo
$totalGeral = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo")->fetchColumn();
$totalAtivas = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1")->fetchColumn();
$totalObrigatorias = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND obrigatoria = 1")->fetchColumn();
$totalExigeFoto = (int)$pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND exige_foto = 1")->fetchColumn();

// Montar Query de Busca
$where = ["1 = 1"];
$params = [];

if ($filtro_tipo === 'inativas') {
    $where[] = "e.ativo = 0";
} else {
    $where[] = "e.ativo = 1";
    if ($filtro_tipo === 'obrigatorias') {
        $where[] = "e.obrigatoria = 1";
    } elseif ($filtro_tipo === 'fotos') {
        $where[] = "e.exige_foto = 1";
    }
}

if (!empty($filtro_busca)) {
    $where[] = "(e.codigo_interno LIKE :busca OR e.descricao LIKE :busca OR e.item_normam LIKE :busca)";
    $params[':busca'] = '%' . $filtro_busca . '%';
}

if (!empty($filtro_categoria)) {
    $where[] = "e.categoria_id = :categoria_id";
    $params[':categoria_id'] = $filtro_categoria;
}

if (!empty($filtro_bloco)) {
    $where[] = "e.bloco_vistoria = :bloco";
    $params[':bloco'] = $filtro_bloco;
}

$sql = "SELECT e.*, c.nome AS categoria_nome 
        FROM exigencias_catalogo e
        LEFT JOIN exigencias_categorias c ON e.categoria_id = c.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY e.obrigatoria DESC, e.exige_foto DESC, e.codigo_interno ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$exigencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="conteudo-principal" style="padding: 24px; max-width: 1400px; margin: 0 auto;">

    <!-- Cabeçalho Principal -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span style="background: #e0f2fe; color: #0284c7; padding: 6px 10px; border-radius: 8px; font-size: 1.1rem;">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                <h1 style="margin: 0; font-size: 1.45rem; font-weight: 700; color: #0f172a;">
                    Catálogo de Exigências NORMAM-202 & Checklist
                </h1>
            </div>
            <p style="margin: 0; color: #64748b; font-size: 0.88rem;">
                Gerenciamento dinâmico dos itens de vistoria técnica naval e referências normativas (NORMAM / DPC - Marinha do Brasil).
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-primary" onclick="abrirModalNovaExigencia()" style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; font-weight: 600; background: #0b5944; border-color: #0b5944;">
                <i class="fa-solid fa-plus"></i> Nova Exigência NORMAM
            </button>
            <a href="<?= APP_URL ?>configuracoes" class="btn btn-outline-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px;">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Cards de Métricas e Resumo -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            <div style="color: #64748b; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total no Catálogo</div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 4px 0;"><?= $totalAtivas ?> <span style="font-size: 0.85rem; font-weight: 500; color: #94a3b8;">/ <?= $totalGeral ?> itens</span></div>
            <div style="font-size: 11px; color: #059669;"><i class="fa-solid fa-check"></i> Itens normativos homologados</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            <div style="color: #c2410c; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Normas Obrigatórias</div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #ea580c; margin: 4px 0;"><?= $totalObrigatorias ?></div>
            <div style="font-size: 11px; color: #9a3412;"><i class="fa-solid fa-shield-halved"></i> Resposta compulsória no checklist</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #bae6fd; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            <div style="color: #0369a1; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Fotos Comprobatórias</div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #0284c7; margin: 4px 0;"><?= $totalExigeFoto ?></div>
            <div style="font-size: 11px; color: #075985;"><i class="fa-solid fa-camera"></i> Anexo obrigatório para envio</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            <div style="color: #64748b; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Categorias Técnicas</div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 4px 0;"><?= count($categorias) ?></div>
            <div style="font-size: 11px; color: #64748b;"><i class="fa-solid fa-layer-group"></i> Estrutura, Máquinas, Salvatagem...</div>
        </div>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 20px;">
        <form method="GET" action="<?= APP_URL ?>configuracoes/normam202" style="display: grid; grid-template-columns: 2fr 1.3fr 1.2fr 1.2fr auto; gap: 14px; align-items: flex-end;">
            <div>
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase;">Buscar por Código ou Descrição</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
                    <input type="text" name="busca" value="<?= h($filtro_busca) ?>" placeholder="Ex.: colete, extintor, EX-344, Cap. 04..." class="form-control form-control-sm" style="padding-left: 34px; height: 38px; border-radius: 6px;">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase;">Categoria Técnica</label>
                <select name="categoria_id" class="form-control form-control-sm" style="height: 38px; border-radius: 6px;">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= h($cat['id']) ?>" <?= $filtro_categoria === $cat['id'] ? 'selected' : '' ?>><?= h($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase;">Bloco de Vistoria</label>
                <select name="bloco" class="form-control form-control-sm" style="height: 38px; border-radius: 6px;">
                    <option value="">Todos os Blocos</option>
                    <option value="flutuando" <?= $filtro_bloco === 'flutuando' ? 'selected' : '' ?>>Flutuando</option>
                    <option value="seco" <?= $filtro_bloco === 'seco' ? 'selected' : '' ?>>Seco</option>
                    <option value="borda_livre" <?= $filtro_bloco === 'borda_livre' ? 'selected' : '' ?>>Borda Livre</option>
                    <option value="arqueacao" <?= $filtro_bloco === 'arqueacao' ? 'selected' : '' ?>>Arqueação</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase;">Filtro Especial</label>
                <select name="tipo" class="form-control form-control-sm" style="height: 38px; border-radius: 6px;">
                    <option value="" <?= empty($filtro_tipo) ? 'selected' : '' ?>>Todas as Ativas</option>
                    <option value="obrigatorias" <?= $filtro_tipo === 'obrigatorias' ? 'selected' : '' ?>>Apenas Obrigatórias</option>
                    <option value="fotos" <?= $filtro_tipo === 'fotos' ? 'selected' : '' ?>>Apenas Exigem Foto</option>
                    <option value="inativas" <?= $filtro_tipo === 'inativas' ? 'selected' : '' ?>>Itens Inativados</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 16px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>configuracoes/normam202" class="btn btn-outline-secondary btn-sm" style="height: 38px; display: inline-flex; align-items: center; justify-content: center; width: 38px;" title="Limpar Filtros">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Exigências -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; font-weight: 700; color: #334155;">
                Exibindo <?= count($exigencias) ?> exigência(s) encontrada(s)
            </div>
            <div style="font-size: 12px; color: #64748b;">
                <span style="display: inline-flex; align-items: center; gap: 4px; margin-right: 12px;"><i class="fa-solid fa-shield-halved" style="color: #ea580c;"></i> Obrigatória</span>
                <span style="display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-camera" style="color: #0284c7;"></i> Exige Foto</span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 1px solid #cbd5e1; color: #475569; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.5px;">
                        <th style="padding: 12px 16px; width: 90px;">Código</th>
                        <th style="padding: 12px 16px; width: 150px;">Categoria</th>
                        <th style="padding: 12px 16px;">Descrição da Exigência & Referência NORMAM</th>
                        <th style="padding: 12px 16px; width: 110px; text-align: center;">Bloco</th>
                        <th style="padding: 12px 16px; width: 120px; text-align: center;">Obrigatória?</th>
                        <th style="padding: 12px 16px; width: 120px; text-align: center;">Exige Foto?</th>
                        <th style="padding: 12px 16px; width: 90px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; width: 110px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exigencias)): ?>
                        <tr>
                            <td colspan="8" style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                                <i class="fa-solid fa-magnifying-glass" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                Nenhuma exigência encontrada com os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exigencias as $ex): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <td style="padding: 12px 16px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                    <?= h($ex['codigo_interno']) ?>
                                </td>

                                <td style="padding: 12px 16px; color: #475569;">
                                    <span style="font-size: 11.5px; font-weight: 600;"><?= h($ex['categoria_nome'] ?: 'Sem Categoria') ?></span>
                                </td>

                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 600; color: #1e293b; line-height: 1.4; margin-bottom: 4px;">
                                        <?= h($ex['descricao']) ?>
                                    </div>
                                    <?php if (!empty($ex['item_normam'])): ?>
                                        <span style="background: #e2e8f0; color: #334155; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-book" style="color: #64748b;"></i> <?= h($ex['item_normam']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 12px 16px; text-align: center;">
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 12px; font-size: 10.5px; font-weight: 600;">
                                        <?= ucfirst(h($ex['bloco_vistoria'] ?: 'flutuando')) ?>
                                    </span>
                                </td>

                                <!-- Switch Obrigatória -->
                                <td style="padding: 12px 16px; text-align: center;">
                                    <label class="switch-toggle" style="cursor: pointer; display: inline-block;">
                                        <input type="checkbox" onchange="alternarObrigatoria('<?= $ex['id'] ?>', this.checked)" <?= !empty($ex['obrigatoria']) ? 'checked' : '' ?>>
                                        <span class="slider-toggle <?= !empty($ex['obrigatoria']) ? 'slider-orange' : '' ?>"></span>
                                    </label>
                                </td>

                                <!-- Switch Exige Foto -->
                                <td style="padding: 12px 16px; text-align: center;">
                                    <label class="switch-toggle" style="cursor: pointer; display: inline-block;">
                                        <input type="checkbox" onchange="alternarFoto('<?= $ex['id'] ?>', this.checked)" <?= !empty($ex['exige_foto']) ? 'checked' : '' ?>>
                                        <span class="slider-toggle <?= !empty($ex['exige_foto']) ? 'slider-blue' : '' ?>"></span>
                                    </label>
                                </td>

                                <!-- Status Ativo/Inativo -->
                                <td style="padding: 12px 16px; text-align: center;">
                                    <?php if (!empty($ex['ativo'])): ?>
                                        <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: 700;">Ativo</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: 700;">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Ações -->
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 6px;">
                                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="editarExigencia('<?= $ex['id'] ?>')" title="Editar Exigência">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if (!empty($ex['ativo'])): ?>
                                            <button type="button" class="btn btn-xs btn-outline-danger" onclick="abrirModalInativar('<?= $ex['id'] ?>', '<?= h($ex['codigo_interno']) ?>')" title="Inativar Exigência">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-xs btn-outline-success" onclick="alternarAtivo('<?= $ex['id'] ?>', true)" title="Reativar Exigência">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE CRIAÇÃO / EDIÇÃO DE EXIGÊNCIA -->
<!-- ========================================================================= -->
<div id="modalExigencia" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; border-radius: 14px; width: 700px; max-width: 92vw; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalExigenciaTitulo" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus-circle" style="color: #0b5944;"></i> Nova Exigência NORMAM-202
            </h3>
            <button type="button" onclick="fecharModalExigencia()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <form method="POST" action="<?= APP_URL ?>configuracoes/normam202/actions" style="display: flex; flex-direction: column; overflow-y: auto; flex: 1;">
            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
            <input type="hidden" name="action" value="salvar">
            <input type="hidden" name="id" id="modal_id" value="">

            <div style="padding: 24px; display: grid; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Código Interno</label>
                        <input type="text" name="codigo_interno" id="modal_codigo_interno" class="form-control" placeholder="Ex: EX-557" style="font-family: monospace;">
                        <small style="color: #94a3b8; font-size: 10.5px;">Deixe vazio para auto-gerar</small>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Categoria Técnica *</label>
                        <select name="categoria_id" id="modal_categoria_id" class="form-control" required>
                            <option value="">Selecione uma categoria...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= h($cat['id']) ?>"><?= h($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Referência NORMAM-202 *</label>
                        <input type="text" name="item_normam" id="modal_item_normam" class="form-control" placeholder="Ex: NORMAM-202/DPC, Cap. 04, Item 4.13" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Bloco de Vistoria</label>
                        <select name="bloco_vistoria" id="modal_bloco_vistoria" class="form-control">
                            <option value="flutuando">Flutuando</option>
                            <option value="seco">Seco</option>
                            <option value="borda_livre">Borda Livre</option>
                            <option value="arqueacao">Arqueação</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Descrição Completa da Exigência *</label>
                    <textarea name="descricao" id="modal_descricao" class="form-control" rows="3" placeholder="Descreva tecnicamente o item inspecionado conforme regulamentação da Autoridade Marítima..." required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Prazo Padrão (Dias)</label>
                        <input type="number" name="prazo_padrao_dias" id="modal_prazo_padrao_dias" value="15" min="1" max="90" class="form-control">
                    </div>

                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #ea580c; cursor: pointer;">
                            <input type="checkbox" name="obrigatoria" id="modal_obrigatoria" value="1" style="width: 18px; height: 18px;">
                            <span><i class="fa-solid fa-shield-halved"></i> Item Obrigatório</span>
                        </label>
                        <small style="color: #64748b; font-size: 10.5px;">Trava resposta compulsória</small>
                    </div>

                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #0284c7; cursor: pointer;">
                            <input type="checkbox" name="exige_foto" id="modal_exige_foto" value="1" style="width: 18px; height: 18px;">
                            <span><i class="fa-solid fa-camera"></i> Exigir Foto</span>
                        </label>
                        <small style="color: #64748b; font-size: 10.5px;">Evidência fotográfica mandante</small>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase;">Aplicabilidade em Áreas de Navegação</label>
                    <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                        <label style="font-size: 11.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="aplicabilidade_a" id="modal_app_a" value="1" checked> Área A (Abrigada)</label>
                        <label style="font-size: 11.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="aplicabilidade_b" id="modal_app_b" value="1" checked> Área B (Parcial)</label>
                        <label style="font-size: 11.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="aplicabilidade_c" id="modal_app_c" value="1" checked> Área C</label>
                        <label style="font-size: 11.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="aplicabilidade_d" id="modal_app_d" value="1" checked> Área D</label>
                    </div>
                </div>
            </div>

            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalExigencia()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background: #0b5944; border-color: #0b5944; font-weight: 600; padding: 8px 22px;">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Exigência
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE INATIVAÇÃO COM AUDITORIA SGQ -->
<!-- ========================================================================= -->
<div id="modalInativar" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; border-radius: 12px; width: 480px; max-width: 90vw; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 16px 20px; border-bottom: 1px solid #fecaca; background: #fef2f2; color: #991b1b; display: flex; align-items: center; gap: 8px; font-weight: 700;">
            <i class="fa-solid fa-triangle-exclamation"></i> Inativação de Exigência NORMAM-202
        </div>

        <form method="POST" action="<?= APP_URL ?>configuracoes/normam202/actions" style="padding: 20px;">
            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
            <input type="hidden" name="action" value="inativar">
            <input type="hidden" name="id" id="inativar_id" value="">

            <p style="font-size: 13px; color: #334155; margin-bottom: 14px;">
                Tem certeza que deseja inativar a exigência <strong id="inativar_codigo" style="font-family: monospace; color: #991b1b;"></strong> do catálogo ativo de vistorias?
            </p>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px;">Motivo / Justificativa da Inativação (Histórico Técnico) *</label>
                <textarea name="motivo" class="form-control" rows="3" required placeholder="Ex: Norma revogada por atualização da NORMAM ou substituída pela exigência EX-XXX..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalInativar()">Cancelar</button>
                <button type="submit" class="btn btn-danger" style="font-weight: 600;">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- Estilos CSS dos Toggles e Badges -->
<style>
.switch-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
}
.switch-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider-toggle {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .25s;
    border-radius: 20px;
}
.slider-toggle:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .25s;
    border-radius: 50%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
input:checked + .slider-toggle {
    background-color: #0b5944;
}
input:checked + .slider-orange {
    background-color: #ea580c !important;
}
input:checked + .slider-blue {
    background-color: #0284c7 !important;
}
input:checked + .slider-toggle:before {
    transform: translateX(16px);
}
</style>

<!-- Scripts de Interação e AJAX -->
<script>
function abrirModalNovaExigencia() {
    document.getElementById('modalExigenciaTitulo').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: #0b5944;"></i> Nova Exigência NORMAM-202';
    document.getElementById('modal_id').value = '';
    document.getElementById('modal_codigo_interno').value = '';
    document.getElementById('modal_categoria_id').value = '';
    document.getElementById('modal_item_normam').value = '';
    document.getElementById('modal_bloco_vistoria').value = 'flutuando';
    document.getElementById('modal_descricao').value = '';
    document.getElementById('modal_prazo_padrao_dias').value = '15';
    document.getElementById('modal_obrigatoria').checked = false;
    document.getElementById('modal_exige_foto').checked = false;
    document.getElementById('modalExigencia').style.display = 'flex';
}

function fecharModalExigencia() {
    document.getElementById('modalExigencia').style.display = 'none';
}

function abrirModalInativar(id, codigo) {
    document.getElementById('inativar_id').value = id;
    document.getElementById('inativar_codigo').innerText = codigo;
    document.getElementById('modalInativar').style.display = 'flex';
}

function fecharModalInativar() {
    document.getElementById('modalInativar').style.display = 'none';
}

function editarExigencia(id) {
    fetch('<?= APP_URL ?>configuracoes/normam202/actions?action=obter&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.mensagem || 'Erro ao obter dados da exigência');
                return;
            }
            const d = res.dados;
            document.getElementById('modalExigenciaTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #0284c7;"></i> Editar Exigência ' + d.codigo_interno;
            document.getElementById('modal_id').value = d.id;
            document.getElementById('modal_codigo_interno').value = d.codigo_interno || '';
            document.getElementById('modal_categoria_id').value = d.categoria_id || '';
            document.getElementById('modal_item_normam').value = d.item_normam || '';
            document.getElementById('modal_bloco_vistoria').value = d.bloco_vistoria || 'flutuando';
            document.getElementById('modal_descricao').value = d.descricao || '';
            document.getElementById('modal_prazo_padrao_dias').value = d.prazo_padrao_dias || '15';
            document.getElementById('modal_obrigatoria').checked = parseInt(d.obrigatoria) === 1;
            document.getElementById('modal_exige_foto').checked = parseInt(d.exige_foto) === 1;
            document.getElementById('modalExigencia').style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            alert('Falha ao comunicar com o servidor.');
        });
}

function alternarObrigatoria(id, valor) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('valor', valor ? 1 : 0);

    fetch('<?= APP_URL ?>configuracoes/normam202/actions?action=toggle_obrigatoria', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) alert(res.mensagem || 'Erro ao alterar obrigatoriedade');
    })
    .catch(e => {
        console.error(e);
        alert('Erro ao processar alteração.');
    });
}

function alternarFoto(id, valor) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('valor', valor ? 1 : 0);

    fetch('<?= APP_URL ?>configuracoes/normam202/actions?action=toggle_foto', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) alert(res.mensagem || 'Erro ao alterar exigência de foto');
    })
    .catch(e => {
        console.error(e);
        alert('Erro ao processar alteração.');
    });
}

function alternarAtivo(id, valor) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('valor', valor ? 1 : 0);

    fetch('<?= APP_URL ?>configuracoes/normam202/actions?action=toggle_ativo', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) window.location.reload();
        else alert(res.mensagem || 'Erro ao reativar item');
    })
    .catch(e => {
        console.error(e);
        alert('Erro ao processar alteração.');
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
