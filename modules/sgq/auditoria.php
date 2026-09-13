<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/auditoria.php
 * Trilha de Auditoria Cadastral (ISO 7.5 - Informação Documentada e Rastreabilidade)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

$filtroTipo = trim((string)($_GET['tipo'] ?? ''));
$filtroAcao = trim((string)($_GET['acao'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));

$params = [];
$whereSql = "WHERE 1=1";

if ($filtroTipo !== '') {
    $whereSql .= " AND entidade_tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}
if ($filtroAcao !== '') {
    $whereSql .= " AND acao = :acao";
    $params[':acao'] = $filtroAcao;
}
if ($busca !== '') {
    $whereSql .= " AND (usuario_nome LIKE :busca1 OR motivo_justificativa LIKE :busca2 OR entidade_id LIKE :busca3)";
    $termoBusca = '%' . $busca . '%';
    $params[':busca1'] = $termoBusca;
    $params[':busca2'] = $termoBusca;
    $params[':busca3'] = $termoBusca;
}

$sql = "
    SELECT * FROM sgq_auditoria_cadastral
    {$whereSql}
    ORDER BY criado_em DESC
    LIMIT 150
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais rápidos
$totaisAuditoria = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN acao = 'CRIACAO' THEN 1 ELSE 0 END) AS criacoes,
        SUM(CASE WHEN acao = 'ALTERACAO' THEN 1 ELSE 0 END) AS alteracoes,
        SUM(CASE WHEN acao = 'INATIVACAO' THEN 1 ELSE 0 END) AS inativacoes
    FROM sgq_auditoria_cadastral
")->fetch(PDO::FETCH_ASSOC);

$titulo_page = 'Trilha de Auditoria Cadastral (ISO 7.5) - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal" style="padding: 24px; max-width: 1320px; margin: 0 auto;">

    <!-- Cabeçalho Principal -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="margin: 0; font-size: 1.45rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <span style="width: 38px; height: 38px; border-radius: 9px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </span>
                Trilha de Auditoria Cadastral (ISO 7.5)
            </h2>
            <p style="margin: 5px 0 0; color: #64748b; font-size: 0.88rem;">
                Registro imutável de alterações críticas (antes vs depois), garantindo integridade e rastreabilidade para auditorias ISO e NORMAM.
            </p>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <span style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-shield-check"></i> Trilha Imutável Ativa
            </span>
        </div>
    </div>

    <!-- Mini-cards de Resumo da Auditoria -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total de Registros Auditados</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-top: 4px;"><?= (int)($totaisAuditoria['total'] ?? 0) ?></div>
            <small style="color: #64748b; font-size: 11px;">Histórico permanente</small>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <span style="font-size: 11px; font-weight: 700; color: #16a34a; text-transform: uppercase;">Cadastros Criados</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #16a34a; margin-top: 4px;"><?= (int)($totaisAuditoria['criacoes'] ?? 0) ?></div>
            <small style="color: #64748b; font-size: 11px;">Entidades iniciadas no sistema</small>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <span style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase;">Modificações / Retificações</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #d97706; margin-top: 4px;"><?= (int)($totaisAuditoria['alteracoes'] ?? 0) ?></div>
            <small style="color: #64748b; font-size: 11px;">Com rastreio de campos antes/depois</small>
        </div>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <span style="font-size: 11px; font-weight: 700; color: #dc2626; text-transform: uppercase;">Inativações de Entidades</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626; margin-top: 4px;"><?= (int)($totaisAuditoria['inativacoes'] ?? 0) ?></div>
            <small style="color: #64748b; font-size: 11px;">Preservação do histórico normativo</small>
        </div>
    </div>

    <!-- Filtros e Busca -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 14px; align-items: flex-end;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Busca Textual</label>
                <input type="text" name="busca" class="form-control" placeholder="Usuário, justificativa ou ID da entidade..." value="<?= h($busca) ?>" style="height: 38px; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tipo de Entidade</label>
                <select name="tipo" class="form-control" style="height: 38px; font-size: 13px;">
                    <option value="">Todas as Entidades</option>
                    <option value="EMBARCACAO" <?= $filtroTipo === 'EMBARCACAO' ? 'selected' : '' ?>>Embarcação</option>
                    <option value="CLIENTE" <?= $filtroTipo === 'CLIENTE' ? 'selected' : '' ?>>Cliente / Proprietário</option>
                    <option value="ARMADOR" <?= $filtroTipo === 'ARMADOR' ? 'selected' : '' ?>>Armador</option>
                    <option value="DESPACHANTE" <?= $filtroTipo === 'DESPACHANTE' ? 'selected' : '' ?>>Despachante</option>
                    <option value="USUARIO" <?= $filtroTipo === 'USUARIO' ? 'selected' : '' ?>>Usuário / Vistoriador</option>
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Operação</label>
                <select name="acao" class="form-control" style="height: 38px; font-size: 13px;">
                    <option value="">Todas as Operações</option>
                    <option value="CRIACAO" <?= $filtroAcao === 'CRIACAO' ? 'selected' : '' ?>>Criação</option>
                    <option value="ALTERACAO" <?= $filtroAcao === 'ALTERACAO' ? 'selected' : '' ?>>Alteração</option>
                    <option value="INATIVACAO" <?= $filtroAcao === 'INATIVACAO' ? 'selected' : '' ?>>Inativação</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 18px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>sgq/auditoria" class="btn btn-outline-secondary" style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-xmark"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela Principal da Trilha de Auditoria -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Data / Hora</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Entidade Auditada</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Operação</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Responsável & IP</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Motivo / Justificativa</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Campos Modificados</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Comparativo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b;">
                                <i class="fa-solid fa-inbox" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                Nenhum registro de auditoria cadastral encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <?php 
                            $acaoCores = [
                                'CRIACAO'    => ['bg' => '#f0fdf4', 'color' => '#15803d', 'border' => '#bbf7d0', 'label' => 'Criação'],
                                'ALTERACAO'  => ['bg' => '#fffbeb', 'color' => '#b45309', 'border' => '#fde68a', 'label' => 'Alteração'],
                                'INATIVACAO' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Inativação'],
                            ];
                            $corAcao = $acaoCores[$l['acao']] ?? ['bg' => '#f8fafc', 'color' => '#475569', 'border' => '#e2e8f0', 'label' => $l['acao']];
                            $campos = json_decode($l['campos_alterados'] ?? '[]', true) ?: [];
                            $dadosAntes = json_decode($l['dados_anteriores'] ?? 'null', true);
                            $dadosDepois = json_decode($l['dados_posteriores'] ?? 'null', true);
                            ?>
                            <tr style="border-top: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-size: 13px; color: #0f172a; white-space: nowrap;">
                                    <strong><?= date('d/m/Y', strtotime($l['criado_em'])) ?></strong>
                                    <small style="display: block; color: #64748b;"><?= date('H:i:s', strtotime($l['criado_em'])) ?></small>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #1e293b;">
                                    <span style="font-weight: 700; color: #0f172a;"><?= h($l['entidade_tipo']) ?></span>
                                    <small style="display: block; color: #64748b; font-family: monospace; font-size: 11px;">ID: <?= h(substr($l['entidade_id'], 0, 8)) ?>...</small>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="background: <?= $corAcao['bg'] ?>; color: <?= $corAcao['color'] ?>; border: 1px solid <?= $corAcao['border'] ?>; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block;">
                                        <?= $corAcao['label'] ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #334155;">
                                    <strong><?= h($l['usuario_nome'] ?: 'Sistema Automático') ?></strong>
                                    <small style="display: block; color: #64748b; font-size: 11px;">IP: <?= h($l['ip_origem'] ?: '127.0.0.1') ?></small>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #475569; max-width: 250px;">
                                    <?= h($l['motivo_justificativa'] ?: 'Operação padrão de cadastro.') ?>
                                </td>
                                <td style="padding: 14px 16px; font-size: 12px; color: #0284c7;">
                                    <?php if (!empty($campos)): ?>
                                        <span style="background: #e0f2fe; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                            <?= count($campos) ?> campo(s):
                                        </span>
                                        <span style="color: #475569; margin-left: 4px;">
                                            <?= h(implode(', ', array_slice($campos, 0, 4))) . (count($campos) > 4 ? '...' : '') ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; font-size: 12px;" onclick="abrirModalAuditoria(<?= htmlspecialchars(json_encode([
                                        'id' => $l['id'],
                                        'entidade' => $l['entidade_tipo'],
                                        'acao' => $l['acao'],
                                        'usuario' => $l['usuario_nome'] ?: 'Sistema',
                                        'data' => date('d/m/Y H:i:s', strtotime($l['criado_em'])),
                                        'motivo' => $l['motivo_justificativa'] ?: '-',
                                        'campos' => $campos,
                                        'antes' => $dadosAntes,
                                        'depois' => $dadosDepois,
                                    ]), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fa-solid fa-magnifying-glass"></i> Comparar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL COMPARATIVO ANTES VS DEPOIS (ISO 7.5) -->
<div id="modalAuditoria" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(2px);">
    <div style="background: #ffffff; width: 100%; max-width: 860px; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h4 id="auditoriaModalTitulo" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-code-compare" style="color: #0284c7;"></i> Comparativo Detalhado (Antes vs Depois)
                </h4>
                <small id="auditoriaModalSub" style="color: #64748b; font-size: 12px; margin-top: 2px; display: block;"></small>
            </div>
            <button type="button" onclick="fecharModalAuditoria()" style="background: transparent; border: none; font-size: 20px; color: #94a3b8; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div style="padding: 24px; overflow-y: auto; flex: 1;">
            <div id="auditoriaInfoBox" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px;"></div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Bloco ANTES -->
                <div style="border: 1px solid #fecaca; border-radius: 8px; overflow: hidden;">
                    <div style="background: #fef2f2; padding: 8px 12px; border-bottom: 1px solid #fecaca; font-size: 12px; font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> Estado Anterior (Antes da Alteração)
                    </div>
                    <pre id="auditoriaAntesJson" style="margin: 0; padding: 12px; font-size: 12px; background: #fff; color: #334155; max-height: 380px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; word-break: break-all;"></pre>
                </div>

                <!-- Bloco DEPOIS -->
                <div style="border: 1px solid #bbf7d0; border-radius: 8px; overflow: hidden;">
                    <div style="background: #f0fdf4; padding: 8px 12px; border-bottom: 1px solid #bbf7d0; font-size: 12px; font-weight: 700; color: #166534; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-right"></i> Estado Posterior (Novo Registro Gravado)
                    </div>
                    <pre id="auditoriaDepoisJson" style="margin: 0; padding: 12px; font-size: 12px; background: #fff; color: #334155; max-height: 380px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; word-break: break-all;"></pre>
                </div>
            </div>
        </div>

        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalAuditoria()" style="padding: 8px 20px;">
                Fechar Visualização
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalAuditoria(d) {
    document.getElementById('auditoriaModalTitulo').innerHTML = '<i class="fa-solid fa-code-compare" style="color: #0284c7;"></i> Comparativo: ' + d.entidade + ' (' + d.acao + ')';
    document.getElementById('auditoriaModalSub').innerText = 'Auditado em ' + d.data + ' por ' + d.usuario;
    
    let infoHtml = '<strong>Responsável:</strong> ' + d.usuario + ' &nbsp;|&nbsp; <strong>Data:</strong> ' + d.data + '<br>';
    infoHtml += '<strong>Motivo da Alteração:</strong> ' + d.motivo + '<br>';
    if (d.campos && d.campos.length > 0) {
        infoHtml += '<strong>Campos Modificados:</strong> <span style="color:#0284c7;font-weight:600;">' + d.campos.join(', ') + '</span>';
    }
    document.getElementById('auditoriaInfoBox').innerHTML = infoHtml;

    document.getElementById('auditoriaAntesJson').innerText = d.antes ? JSON.stringify(d.antes, null, 2) : '(Nenhum registro anterior - Criação Nova)';
    document.getElementById('auditoriaDepoisJson').innerText = d.depois ? JSON.stringify(d.depois, null, 2) : '(Registro inativado / removido)';

    const modal = document.getElementById('modalAuditoria');
    modal.style.display = 'flex';
}

function fecharModalAuditoria() {
    const modal = document.getElementById('modalAuditoria');
    modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
