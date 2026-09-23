<?php
/**
 * MÓDULO: Documentação > Notas de Arqueação (AM-NAR)
 * Listagem oficial com filtros e paginação
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/aprovacao_ui.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$busca = trim($_GET['busca'] ?? '');
$filtro_status = trim($_GET['status'] ?? '');
$filtro_enquadramento = trim($_GET['enquadramento'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 15;

$whereBase = "WHERE c.ativo = 1";
$params = [];

if (!empty($busca)) {
    $whereBase .= " AND (c.numero LIKE :b1 OR c.nome_embarcacao LIKE :b2 OR c.armador LIKE :b3)";
    $params[':b1'] = "%{$busca}%";
    $params[':b2'] = "%{$busca}%";
    $params[':b3'] = "%{$busca}%";
}

if (!empty($filtro_status) && in_array($filtro_status, ['rascunho', 'emitido', 'assinado', 'cancelado'], true)) {
    $whereBase .= " AND c.status = :status";
    $params[':status'] = $filtro_status;
}

if (!empty($filtro_enquadramento) && in_array($filtro_enquadramento, ['L_MAIOR_IGUAL_24', 'L_MENOR_24'], true)) {
    $whereBase .= " AND c.enquadramento_comprimento = :enquadramento";
    $params[':enquadramento'] = $filtro_enquadramento;
}

// Total de registros
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM certificados_nar c {$whereBase}");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
$offset = ($paginaAtual - 1) * $porPagina;

// Buscar registros
$sql = "SELECT c.*, e.tipo AS embarcacao_tipo, cl.nome AS cliente_nome, u.nome AS criador_nome
        FROM certificados_nar c
        LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
        LEFT JOIN clientes cl ON cl.id = c.cliente_id
        LEFT JOIN usuarios u ON u.id = c.criado_por
        {$whereBase}
        ORDER BY c.criado_em DESC
        LIMIT {$porPagina} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Notas de Arqueação (AM-NAR) - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <div>
            <h2><i class="fa-solid fa-calculator text-primary"></i> Notas de Arqueação de Embarcações (AM-NAR)</h2>
            <p class="text-muted" style="margin: 4px 0 0; font-size: 0.88rem;">
                Memória oficial de cálculo de arqueação bruta (AB) e líquida (AL) conforme NORMAM-201 e NORMAM-202/DPC.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>documentacao/nar/form" class="btn btn-success">
                <i class="fas fa-plus"></i> Nova Nota de Arqueação
            </a>
        </div>
    </div>

    <!-- Filtros rápidos por enquadramento em 1 clique -->
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="<?= APP_URL ?>documentacao/nar" class="btn btn-sm <?= empty($filtro_enquadramento) ? 'btn-primary' : 'btn-outline-secondary' ?>">
            Todas as Notas
        </a>
        <a href="<?= APP_URL ?>documentacao/nar?enquadramento=L_MAIOR_IGUAL_24" class="btn btn-sm <?= $filtro_enquadramento === 'L_MAIOR_IGUAL_24' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            L ≥ 24 m (Grande Porte)
        </a>
        <a href="<?= APP_URL ?>documentacao/nar?enquadramento=L_MENOR_24" class="btn btn-sm <?= $filtro_enquadramento === 'L_MENOR_24' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            L &lt; 24 m (Médio / Pequeno Porte)
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>documentacao/nar" class="d-flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="busca"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Número AM-NAR, embarcação ou armador..." 
                           value="<?= h($busca) ?>">
                </div>
                <div class="form-group" style="min-width: 180px;">
                    <label for="status"><i class="fas fa-filter"></i> Situação</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todas</option>
                        <option value="rascunho" <?= $filtro_status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="emitido" <?= $filtro_status === 'emitido' ? 'selected' : '' ?>>Emitido</option>
                        <option value="assinado" <?= $filtro_status === 'assinado' ? 'selected' : '' ?>>Assinado</option>
                        <option value="cancelado" <?= $filtro_status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?= APP_URL ?>documentacao/nar" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="tabela-container">
        <?php if (empty($nars)): ?>
            <div class="tabela-vazia" style="padding: 40px; text-align: center;">
                <i class="fa-solid fa-calculator" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px;"></i>
                <h4>Nenhuma Nota de Arqueação encontrada</h4>
                <p class="text-muted">Gere a primeira NAR a partir de uma Análise de Planos (RAP) ou clicando no botão acima.</p>
            </div>
        <?php else: ?>
            <table class="tabela-dados">
                <thead>
                    <tr>
                        <th>Número Oficial</th>
                        <th>Embarcação / Armador</th>
                        <th>Enquadramento</th>
                        <th>Dimensões (Ct × B × P)</th>
                        <th>Arqueação (AB / AL)</th>
                        <th>Situação</th>
                        <th>Emissão</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nars as $nar): ?>
                        <?php
                        $badgeClass = match($nar['status']) {
                            'assinado' => 'badge-success',
                            'emitido' => 'badge-primary',
                            'rascunho' => 'badge-warning',
                            'cancelado' => 'badge-danger',
                            default => 'badge-secondary'
                        };
                        $enqLabel = $nar['enquadramento_comprimento'] === 'L_MENOR_24' ? 'L &lt; 24 m' : 'L ≥ 24 m';
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?= APP_URL ?>documentacao/nar/form?id=<?= urlencode($nar['id']) ?>">
                                        <?= h($nar['numero']) ?>
                                    </a>
                                </strong>
                                <?php if (!empty($nar['analise_id'])): ?>
                                    <small class="d-block text-muted">
                                        <i class="fa-solid fa-link text-primary"></i> Vínculo RAP
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= h($nar['nome_embarcacao']) ?></strong>
                                <small class="d-block text-muted"><?= h($nar['armador'] ?: 'Armador não informado') ?></small>
                            </td>
                            <td>
                                <span class="badge badge-light" style="border: 1px solid #cbd5e1;">
                                    <?= $enqLabel ?>
                                </span>
                            </td>
                            <td>
                                <?= number_format((float)$nar['comprimento_total_ct'], 2, ',', '.') ?> m &times; 
                                <?= number_format((float)$nar['boca_moldada_b'], 2, ',', '.') ?> m &times; 
                                <?= number_format((float)$nar['pontal_moldado_p'], 2, ',', '.') ?> m
                            </td>
                            <td>
                                <strong style="color: #0f766e;">AB <?= (int)$nar['arqueacao_bruta_ab'] ?></strong> / 
                                <span style="color: #0369a1;">AL <?= (int)$nar['arqueacao_liquida_al'] ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucfirst($nar['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= formatarData($nar['data_emissao']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="acoes-tabela" style="display: inline-flex; gap: 6px;">
                                    <a href="<?= APP_URL ?>documentacao/nar/pdf?id=<?= urlencode($nar['id']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-danger" 
                                       title="Visualizar PDF Oficial de 3 Páginas">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>

                                    <a href="<?= APP_URL ?>documentacao/nar/form?id=<?= urlencode($nar['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Editar Dados">
                                        <i class="fa-solid fa-edit"></i>
                                    </a>

                                    <?php if ($nar['status'] !== 'assinado'): ?>
                                        <a href="<?= APP_URL ?>documentacao/nar/assinar?id=<?= urlencode($nar['id']) ?>" 
                                            class="btn btn-sm btn-outline-success" 
                                            title="Assinar Eletronicamente">
                                            <i class="fa-solid fa-signature"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($nar['status'] !== 'assinado'): ?>
                                        <a href="<?= APP_URL ?>documentacao/nar/actions?action=excluir&id=<?= urlencode($nar['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir esta Nota de Arqueação?');"
                                           title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
