<?php
/**
 * MODULO: CONTRATOS
 * Arquivo: index.php - Listagem de contratos
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
// Admin e vendedor podem acessar o comercial/contratos
if (getCargo() !== 'ADMIN' && getCargo() !== 'VENDEDOR') {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

// Filtros
$busca = $_GET['busca'] ?? '';
$status = $_GET['status'] ?? '';

// Query base
$sql = "SELECT c.*, cl.nome as cliente_nome, pr.numero as proposta_numero
        FROM contratos c
        JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN propostas pr ON c.proposta_id = pr.id
        WHERE c.ativo = 1";
$params = [];

if ($busca) {
    $sql .= " AND (c.numero LIKE :busca1 OR cl.nome LIKE :busca2 OR pr.numero LIKE :busca3)";
    $termoBusca = "%{$busca}%";
    $params[':busca1'] = $termoBusca;
    $params[':busca2'] = $termoBusca;
    $params[':busca3'] = $termoBusca;
}

if ($status) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY c.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contratos = $stmt->fetchAll();

$titulo_page = 'Contratos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="list-page-header">
        <div>
            <h1>Contratos</h1>
            <p class="subtitle">Gerenciamento de contratos de prestação de serviços</p>
        </div>
        <div class="page-actions">
            <a href="<?= APP_URL ?>contratos/form" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Contrato
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <form method="GET" class="d-flex gap-2 w-100" style="align-items: flex-end;">
            <div class="form-group mb-0" style="flex: 2;">
                <label for="busca">Buscar (Cliente, Nº Contrato, Proposta)</label>
                <div class="search-input-wrapper" style="max-width: 100%;">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="busca" name="busca" value="<?= h($busca) ?>" placeholder="Digite para buscar...">
                </div>
            </div>
            
            <div class="form-group mb-0" style="flex: 1;">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="MINUTA" <?= $status === 'MINUTA' ? 'selected' : '' ?>>Minuta</option>
                    <option value="AGUARDANDO_ASSINATURA" <?= $status === 'AGUARDANDO_ASSINATURA' ? 'selected' : '' ?>>Aguardando Assinatura</option>
                    <option value="ASSINADO" <?= $status === 'ASSINADO' ? 'selected' : '' ?>>Assinado</option>
                    <option value="CANCELADO" <?= $status === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="form-group mb-0">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="<?= APP_URL ?>contratos" class="btn btn-secondary" title="Limpar Filtros"><i class="fa-solid fa-eraser"></i></a>
            </div>
        </form>
    </div>

    <!-- Listagem -->
    <div class="data-table-wrapper">
        <?php if (count($contratos) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nº Contrato</th>
                            <th>Cliente</th>
                            <th>Proposta</th>
                            <th>Emissão</th>
                            <th>Validade Assinatura</th>
                            <th>Valor/Parc.</th>
                            <th>Frequência</th>
                            <th>Status</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contratos as $c): 
                            
                            $badge_class = 'badge-secondary';
                            switch ($c['status']) {
                                case 'MINUTA': $badge_class = 'badge-secondary'; break;
                                case 'AGUARDANDO_ASSINATURA': $badge_class = 'badge-warning'; break;
                                case 'ASSINADO': $badge_class = 'badge-success'; break;
                                case 'CANCELADO': $badge_class = 'badge-danger'; break;
                            }
                        ?>
                            <tr>
                                <td class="td-primary"><strong><?= $c['numero'] ? h($c['numero']) : '<span class="em-dash">S/N</span>' ?></strong></td>
                                <td><?= h($c['cliente_nome']) ?></td>
                                <td><?= $c['proposta_numero'] ? h($c['proposta_numero']) : '<span class="em-dash">—</span>' ?></td>
                                <td><?= $c['data_emissao'] ? formatarData($c['data_emissao']) : '<span class="em-dash">—</span>' ?></td>
                                <td><?= $c['data_vencimento'] ? formatarData($c['data_vencimento']) : '<span class="em-dash">—</span>' ?></td>
                                <td><?= $c['valor_total'] ? formatarMoeda($c['valor_total']) : '<span class="em-dash">—</span>' ?></td>
                                <td><?= h($c['frequencia']) ?></td>
                                <td>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= str_replace('_', ' ', $c['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="td-actions">
                                        <a href="<?= APP_URL ?>contratos/view?id=<?= $c['id'] ?>" class="btn-action" title="Visualizar / Imprimir">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if ($c['status'] !== 'ASSINADO'): ?>
                                        <a href="<?= APP_URL ?>contratos/form?id=<?= $c['id'] ?>" class="btn-action" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="<?= APP_URL ?>contratos/actions?action=excluir&id=<?= $c['id'] ?>&csrf_token=<?= gerarCSRF() ?>" class="btn-action danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este contrato?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <h3 class="empty-title">Nenhum contrato encontrado</h3>
                <p class="empty-desc">Você ainda não tem contratos criados ou a busca não retornou resultados.</p>
                <a href="<?= APP_URL ?>contratos/form" class="btn btn-primary mt-3">
                    <i class="fa-solid fa-plus"></i> Criar Primeiro Contrato
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
