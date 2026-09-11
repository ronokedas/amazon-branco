<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE
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
    $whereSql .= " AND (usuario_nome LIKE :busca OR motivo_justificativa LIKE :busca OR entidade_id LIKE :busca)";
    $params[':busca'] = '%' . $busca . '%';
}

$sql = "
    SELECT * FROM sgq_auditoria_cadastral
    {$whereSql}
    ORDER BY criado_em DESC
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Trilha de Auditoria Cadastral (ISO 7.5) - SGQ';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<main class="app-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 font-weight-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-info mr-2"></i> Trilha de Auditoria Cadastral</h2>
                <p class="text-muted mb-0">Controle de Informação Documentada (ISO 7.5) com histórico imutável de antes e depois.</p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label class="small font-weight-bold">Busca textual</label>
                        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Usuário, justificativa ou ID..." value="<?= h($busca) ?>">
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label class="small font-weight-bold">Entidade</label>
                        <select name="tipo" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="EMBARCACAO" <?= $filtroTipo === 'EMBARCACAO' ? 'selected' : '' ?>>Embarcação</option>
                            <option value="CLIENTE" <?= $filtroTipo === 'CLIENTE' ? 'selected' : '' ?>>Cliente / Proprietário</option>
                            <option value="ARMADOR" <?= $filtroTipo === 'ARMADOR' ? 'selected' : '' ?>>Armador</option>
                            <option value="DESPACHANTE" <?= $filtroTipo === 'DESPACHANTE' ? 'selected' : '' ?>>Despachante</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label class="small font-weight-bold">Ação</label>
                        <select name="acao" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="CRIACAO" <?= $filtroAcao === 'CRIACAO' ? 'selected' : '' ?>>Criação</option>
                            <option value="ALTERACAO" <?= $filtroAcao === 'ALTERACAO' ? 'selected' : '' ?>>Alteração</option>
                            <option value="INATIVACAO" <?= $filtroAcao === 'INATIVACAO' ? 'selected' : '' ?>>Inativação</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fa-solid fa-filter"></i></button>
                        <a href="<?= APP_URL ?>sgq/auditoria" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-xmark"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead class="thead-light">
                            <tr>
                                <th>Data / Hora</th>
                                <th>Entidade</th>
                                <th>Operação</th>
                                <th>Responsável</th>
                                <th>Motivo / Justificativa</th>
                                <th>Campos Alterados</th>
                                <th>Antes vs Depois</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Nenhum registro de auditoria encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></td>
                                        <td><strong><?= h($l['entidade_tipo']) ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?= $l['acao'] === 'CRIACAO' ? 'success' : ($l['acao'] === 'ALTERACAO' ? 'warning' : 'danger') ?>">
                                                <?= h($l['acao']) ?>
                                            </span>
                                        </td>
                                        <td><?= h($l['usuario_nome'] ?: 'Sistema') ?> <small class="text-muted">(IP: <?= h($l['ip_origem']) ?>)</small></td>
                                        <td><?= h($l['motivo_justificativa'] ?: '-') ?></td>
                                        <td>
                                            <?php 
                                            $campos = json_decode($l['campos_alterados'] ?? '[]', true) ?: [];
                                            echo !empty($campos) ? implode(', ', array_slice($campos, 0, 5)) . (count($campos) > 5 ? '...' : '') : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="alert(<?= htmlspecialchars(json_encode([
                                                'Antes' => json_decode($l['dados_anteriores'] ?? 'null'),
                                                'Depois' => json_decode($l['dados_posteriores'] ?? 'null')
                                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?>)">
                                                <i class="fa-solid fa-code mr-1"></i> Ver JSON
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
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
