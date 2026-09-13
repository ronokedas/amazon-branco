<?php
/**
 * MÓDULO SGQ: GESTÃO DE RISCOS E OPORTUNIDADES (ISO 9001:2015 CLÁUSULA 6.1)
 * Arquivo: modules/sgq/riscos.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

$filtroTipo   = trim((string)($_GET['tipo'] ?? ''));
$filtroNivel  = trim((string)($_GET['nivel'] ?? ''));
$filtroStatus = trim((string)($_GET['status'] ?? ''));
$busca        = trim((string)($_GET['busca'] ?? ''));

$whereSql = "WHERE 1=1";
$params = [];

if ($filtroTipo !== '') {
    $whereSql .= " AND tipo_risco = :tipo";
    $params[':tipo'] = $filtroTipo;
}
if ($filtroNivel !== '') {
    $whereSql .= " AND nivel_risco = :nivel";
    $params[':nivel'] = $filtroNivel;
}
if ($filtroStatus !== '') {
    $whereSql .= " AND status_tratamento = :status";
    $params[':status'] = $filtroStatus;
}
if ($busca !== '') {
    $whereSql .= " AND (codigo_risco LIKE :busca1 OR descricao_risco LIKE :busca2 OR processo_setor LIKE :busca3 OR responsavel_nome LIKE :busca4)";
    $termoBusca = '%' . $busca . '%';
    $params[':busca1'] = $termoBusca;
    $params[':busca2'] = $termoBusca;
    $params[':busca3'] = $termoBusca;
    $params[':busca4'] = $termoBusca;
}

$stmt = $pdo->prepare("
    SELECT * FROM sgq_matriz_riscos
    {$whereSql}
    ORDER BY FIELD(nivel_risco, 'CRITICO', 'ALTO', 'MEDIO', 'BAIXO'), criado_em DESC
");
$stmt->execute($params);
$riscos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totais = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN nivel_risco IN ('CRITICO', 'ALTO') THEN 1 ELSE 0 END) AS criticos_altos,
        SUM(CASE WHEN nivel_risco = 'MEDIO' THEN 1 ELSE 0 END) AS medios,
        SUM(CASE WHEN status_tratamento IN ('MITIGADO', 'RESIDUAL_ACEITO') THEN 1 ELSE 0 END) AS mitigados
    FROM sgq_matriz_riscos
")->fetch(PDO::FETCH_ASSOC);

$titulo_page = 'Gestão de Riscos e Oportunidades (ISO 6.1) - SGQ';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: var(--cor-destaque); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shield-virus"></i> Matriz de Riscos e Oportunidades
            </h2>
            <p class="text-muted" style="margin: 4px 0 0; font-size: 0.88rem;">
                Mapeamento preventivo, análise de impacto e planos de mitigação operacional e regulatório (ISO 9001:2015 Cláusula 6.1 & NORMAM).
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirModalRisco()">
            <i class="fas fa-plus"></i> Novo Risco / Oportunidade
        </button>
    </div>

    <!-- Cards de Métricas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--cor-primaria); background: var(--cor-fundo-card);">
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--cor-texto-secundario);">Total de Riscos Mapeados</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--cor-texto-principal); margin: 4px 0;"><?= (int)($totais['total'] ?? 0) ?></div>
            <small class="text-muted">Riscos e oportunidades cadastrados</small>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #f85149; background: var(--cor-fundo-card);">
            <div style="font-size: 0.8rem; text-transform: uppercase; color: #f85149;">Nível Crítico & Alto</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: #f85149; margin: 4px 0;"><?= (int)($totais['criticos_altos'] ?? 0) ?></div>
            <small class="text-muted">Exigem planos de ação imediatos</small>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #d29922; background: var(--cor-fundo-card);">
            <div style="font-size: 0.8rem; text-transform: uppercase; color: #d29922;">Nível Médio</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: #d29922; margin: 4px 0;"><?= (int)($totais['medios'] ?? 0) ?></div>
            <small class="text-muted">Monitoramento em revisões do SGQ</small>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3fb950; background: var(--cor-fundo-card);">
            <div style="font-size: 0.8rem; text-transform: uppercase; color: #3fb950;">Mitigados / Aceitos</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: #3fb950; margin: 4px 0;"><?= (int)($totais['mitigados'] ?? 0) ?></div>
            <small class="text-muted">Ações preventivas implantadas</small>
        </div>
    </div>

    <!-- Filtros de Consulta -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="<?= APP_URL ?>sgq/riscos" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.8rem; margin-bottom: 4px; display: block;">Busca</label>
                <input type="text" name="busca" class="form-control" placeholder="Buscar por código, descrição, setor..." value="<?= h($busca) ?>">
            </div>
            <div style="width: 160px;">
                <label style="font-size: 0.8rem; margin-bottom: 4px; display: block;">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos os Tipos</option>
                    <option value="AMEACA" <?= $filtroTipo === 'AMEACA' ? 'selected' : '' ?>>Ameaça (Risco Negativo)</option>
                    <option value="OPORTUNIDADE" <?= $filtroTipo === 'OPORTUNIDADE' ? 'selected' : '' ?>>Oportunidade (Positivo)</option>
                </select>
            </div>
            <div style="width: 150px;">
                <label style="font-size: 0.8rem; margin-bottom: 4px; display: block;">Nível de Risco</label>
                <select name="nivel" class="form-control">
                    <option value="">Todos os Níveis</option>
                    <option value="CRITICO" <?= $filtroNivel === 'CRITICO' ? 'selected' : '' ?>>CRÍTICO (16-25)</option>
                    <option value="ALTO" <?= $filtroNivel === 'ALTO' ? 'selected' : '' ?>>ALTO (10-15)</option>
                    <option value="MEDIO" <?= $filtroNivel === 'MEDIO' ? 'selected' : '' ?>>MÉDIO (5-9)</option>
                    <option value="BAIXO" <?= $filtroNivel === 'BAIXO' ? 'selected' : '' ?>>BAIXO (1-4)</option>
                </select>
            </div>
            <div style="width: 160px;">
                <label style="font-size: 0.8rem; margin-bottom: 4px; display: block;">Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos os Status</option>
                    <option value="IDENTIFICADO" <?= $filtroStatus === 'IDENTIFICADO' ? 'selected' : '' ?>>Identificado</option>
                    <option value="EM_MITIGACAO" <?= $filtroStatus === 'EM_MITIGACAO' ? 'selected' : '' ?>>Em Mitigação</option>
                    <option value="MITIGADO" <?= $filtroStatus === 'MITIGADO' ? 'selected' : '' ?>>Mitigado</option>
                    <option value="RESIDUAL_ACEITO" <?= $filtroStatus === 'RESIDUAL_ACEITO' ? 'selected' : '' ?>>Residual Aceito</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-secondary" style="height: 38px;">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>sgq/riscos" class="btn btn-outline" style="height: 38px; display: inline-flex; align-items: center;">Limpar</a>
            </div>
        </form>
    </div>

    <!-- Tabela de Riscos -->
    <div class="card">
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--cor-borda); text-align: left;">
                        <th style="padding: 12px 16px;">Código / Tipo</th>
                        <th style="padding: 12px 16px;">Processo / Setor</th>
                        <th style="padding: 12px 16px;">Descrição do Risco & Causa</th>
                        <th style="padding: 12px 16px; text-align: center;">Matriz (P x I)</th>
                        <th style="padding: 12px 16px;">Ação de Mitigação</th>
                        <th style="padding: 12px 16px;">Responsável / Prazo</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riscos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--cor-texto-secundario);">
                            <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                            Nenhum risco ou oportunidade cadastrado para os filtros selecionados.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($riscos as $r): ?>
                    <?php
                        $badgeNivel = match ($r['nivel_risco']) {
                            'CRITICO' => ['bg' => 'rgba(248,81,73,0.15)', 'color' => '#f85149', 'label' => 'CRÍTICO'],
                            'ALTO'    => ['bg' => 'rgba(219,109,40,0.15)', 'color' => '#db6d28', 'label' => 'ALTO'],
                            'MEDIO'   => ['bg' => 'rgba(210,153,34,0.15)', 'color' => '#d29922', 'label' => 'MÉDIO'],
                            default   => ['bg' => 'rgba(88,166,255,0.15)', 'color' => '#58a6ff', 'label' => 'BAIXO'],
                        };
                        $badgeTipo = $r['tipo_risco'] === 'OPORTUNIDADE'
                            ? ['bg' => 'rgba(63,185,80,0.15)', 'color' => '#3fb950', 'label' => 'Oportunidade']
                            : ['bg' => 'rgba(248,81,73,0.1)', 'color' => '#f85149', 'label' => 'Ameaça'];
                    ?>
                    <tr style="border-bottom: 1px solid var(--cor-borda);">
                        <td style="padding: 12px 16px;">
                            <strong><?= h($r['codigo_risco']) ?></strong><br>
                            <span class="badge" style="background: <?= $badgeTipo['bg'] ?>; color: <?= $badgeTipo['color'] ?>; font-size: 0.72rem;">
                                <?= $badgeTipo['label'] ?>
                            </span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span style="font-weight: 500;"><?= h($r['processo_setor']) ?></span>
                        </td>
                        <td style="padding: 12px 16px; max-width: 280px;">
                            <div style="font-weight: 500;"><?= h($r['descricao_risco']) ?></div>
                            <?php if (!empty($r['causas'])): ?>
                            <small class="text-muted" style="display: block; margin-top: 2px;">
                                <em>Causa:</em> <?= h(substr($r['causas'], 0, 90)) ?><?= strlen($r['causas']) > 90 ? '...' : '' ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="font-size: 0.85rem; font-weight: 600;">P:<?= (int)$r['probabilidade'] ?> × I:<?= (int)$r['impacto'] ?></span><br>
                            <span class="badge" style="background: <?= $badgeNivel['bg'] ?>; color: <?= $badgeNivel['color'] ?>; font-size: 0.75rem; font-weight: 700; margin-top: 4px;">
                                <?= $badgeNivel['label'] ?> (<?= (int)$r['probabilidade'] * (int)$r['impacto'] ?>)
                            </span>
                        </td>
                        <td style="padding: 12px 16px; max-width: 260px; font-size: 0.85rem;">
                            <?= nl2br(h($r['acao_mitigacao'])) ?>
                        </td>
                        <td style="padding: 12px 16px; font-size: 0.85rem;">
                            <i class="fas fa-user-circle" style="opacity: 0.7;"></i> <?= h($r['responsavel_nome']) ?><br>
                            <small class="text-muted">
                                <?= !empty($r['prazo_revisao']) ? 'Revisão: ' . date('d/m/Y', strtotime($r['prazo_revisao'])) : 'Sem prazo fixado' ?>
                            </small>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="badge" style="background: rgba(110,118,129,0.15); color: var(--cor-texto-principal); font-size: 0.75rem;">
                                <?= h(str_replace('_', ' ', $r['status_tratamento'])) ?>
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                            <button type="button" class="btn btn-sm btn-secondary" data-risco="<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>" onclick="editarRiscoElemento(this)" title="Editar Risco">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="<?= APP_URL ?>sgq/riscos/actions?action=excluir" style="display: inline;" onsubmit="return confirm('Deseja realmente remover este risco da matriz?');">
                                <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline text-danger" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Adicionar / Editar Risco -->
<div id="modalRisco" class="modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.65); align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cor-borda); padding: 16px 20px;">
            <h3 id="modalTitulo" style="margin: 0; color: var(--cor-destaque); font-size: 1.15rem;">
                <i class="fas fa-shield-alt"></i> Novo Risco / Oportunidade (ISO 6.1)
            </h3>
            <button type="button" onclick="fecharModalRisco()" style="background: none; border: none; font-size: 1.4rem; color: var(--cor-texto-secundario); cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>sgq/riscos/actions?action=salvar" style="padding: 20px;">
            <input type="hidden" id="risco_id" name="id" value="">

            <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Tipo de Risco *</label>
                    <select id="risco_tipo" name="tipo_risco" class="form-control" required>
                        <option value="AMEACA">Ameaça (Risco Negativo)</option>
                        <option value="OPORTUNIDADE">Oportunidade (Positivo)</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Processo / Setor *</label>
                    <input type="text" id="risco_processo" name="processo_setor" class="form-control" placeholder="Ex: Operação de Campo, Vistoria, TI" required>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Descrição do Risco / Evento *</label>
                <input type="text" id="risco_descricao" name="descricao_risco" class="form-control" placeholder="Ex: Queda de comunicação no terminal flutuante" required>
            </div>

            <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Causa Raiz / Fatores Contribuintes</label>
                    <textarea id="risco_causas" name="causas" class="form-control" rows="2" placeholder="O que pode causar este evento?"></textarea>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Impactos / Consequências</label>
                    <textarea id="risco_conseq" name="impacto_consequencias" class="form-control" rows="2" placeholder="Qual o dano potencial ao cliente/certificação?"></textarea>
                </div>
            </div>

            <!-- Matriz de Severidade e Probabilidade -->
            <div style="background: rgba(56, 139, 253, 0.05); border: 1px solid rgba(88, 166, 255, 0.2); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--cor-destaque); margin-bottom: 10px;">
                    <i class="fas fa-calculator"></i> Matriz de Risco (Probabilidade × Impacto)
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 12px; align-items: center;">
                    <div>
                        <label style="font-size: 0.8rem; display: block; margin-bottom: 2px;">Probabilidade (1 a 5)</label>
                        <select id="risco_prob" name="probabilidade" class="form-control" onchange="atualizarPreviewRisco()">
                            <option value="1">1 - Muito Rara</option>
                            <option value="2">2 - Pouco Provável</option>
                            <option value="3" selected>3 - Provável</option>
                            <option value="4">4 - Muito Provável</option>
                            <option value="5">5 - Frequente</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; display: block; margin-bottom: 2px;">Impacto (1 a 5)</label>
                        <select id="risco_impacto" name="impacto" class="form-control" onchange="atualizarPreviewRisco()">
                            <option value="1">1 - Desprezível</option>
                            <option value="2">2 - Menor</option>
                            <option value="3" selected>3 - Moderado</option>
                            <option value="4">4 - Maior</option>
                            <option value="5">5 - Catastrófico</option>
                        </select>
                    </div>
                    <div style="text-align: center; padding: 8px; border-radius: 6px; background: rgba(0,0,0,0.2);">
                        <div style="font-size: 0.75rem; color: var(--cor-texto-secundario);">Nível Calculado</div>
                        <div id="previewNivelRisco" style="font-weight: 700; font-size: 1rem; color: #d29922;">MÉDIO (Score: 9)</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Ação Preventiva / Mitigação *</label>
                <textarea id="risco_acao" name="acao_mitigacao" class="form-control" rows="3" placeholder="Ação detalhada para mitigar a ameaça ou capturar a oportunidade..." required></textarea>
            </div>

            <div class="grid-3" style="display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Responsável *</label>
                    <input type="text" id="risco_resp" name="responsavel_nome" class="form-control" placeholder="Nome / Cargo" required>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Prazo de Revisão</label>
                    <input type="date" id="risco_prazo" name="prazo_revisao" class="form-control">
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Status Tratamento</label>
                    <select id="risco_status" name="status_tratamento" class="form-control">
                        <option value="IDENTIFICADO">Identificado</option>
                        <option value="EM_MITIGACAO" selected>Em Mitigação</option>
                        <option value="MITIGADO">Mitigado</option>
                        <option value="RESIDUAL_ACEITO">Residual Aceito</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="fecharModalRisco()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar na Matriz
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalRisco() {
    document.getElementById('risco_id').value = '';
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-shield-alt"></i> Novo Risco / Oportunidade (ISO 6.1)';
    document.getElementById('risco_processo').value = '';
    document.getElementById('risco_descricao').value = '';
    document.getElementById('risco_causas').value = '';
    document.getElementById('risco_conseq').value = '';
    document.getElementById('risco_prob').value = '3';
    document.getElementById('risco_impacto').value = '3';
    document.getElementById('risco_acao').value = '';
    document.getElementById('risco_resp').value = '';
    document.getElementById('risco_prazo').value = '';
    document.getElementById('risco_status').value = 'EM_MITIGACAO';
    atualizarPreviewRisco();
    document.getElementById('modalRisco').style.display = 'flex';
}

function fecharModalRisco() {
    document.getElementById('modalRisco').style.display = 'none';
}

function editarRiscoElemento(btn) {
    try {
        const r = JSON.parse(btn.getAttribute('data-risco'));
        editarRisco(r);
    } catch (e) {
        console.error('Erro ao ler risco:', e);
    }
}

function editarRisco(r) {
    document.getElementById('risco_id').value = r.id;
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Risco: ' + r.codigo_risco;
    document.getElementById('risco_tipo').value = r.tipo_risco;
    document.getElementById('risco_processo').value = r.processo_setor;
    document.getElementById('risco_descricao').value = r.descricao_risco;
    document.getElementById('risco_causas').value = r.causas || '';
    document.getElementById('risco_conseq').value = r.impacto_consequencias || '';
    document.getElementById('risco_prob').value = r.probabilidade;
    document.getElementById('risco_impacto').value = r.impacto;
    document.getElementById('risco_acao').value = r.acao_mitigacao;
    document.getElementById('risco_resp').value = r.responsavel_nome;
    document.getElementById('risco_prazo').value = r.prazo_revisao || '';
    document.getElementById('risco_status').value = r.status_tratamento;
    atualizarPreviewRisco();
    document.getElementById('modalRisco').style.display = 'flex';
}

function atualizarPreviewRisco() {
    const prob = parseInt(document.getElementById('risco_prob').value || 3);
    const imp = parseInt(document.getElementById('risco_impacto').value || 3);
    const score = prob * imp;
    const el = document.getElementById('previewNivelRisco');
    if (score >= 16) {
        el.style.color = '#f85149';
        el.innerText = 'CRÍTICO (Score: ' + score + ')';
    } else if (score >= 10) {
        el.style.color = '#db6d28';
        el.innerText = 'ALTO (Score: ' + score + ')';
    } else if (score >= 5) {
        el.style.color = '#d29922';
        el.innerText = 'MÉDIO (Score: ' + score + ')';
    } else {
        el.style.color = '#58a6ff';
        el.innerText = 'BAIXO (Score: ' + score + ')';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
