<?php
/**
 * MÓDULO: COMERCIAL > SERVIÇOS
 * Arquivo: index.php - Listagem de serviços e preços
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Exigir login e permissão
verificar_sessao();
if (!podeAcessar('servicos')) {
    setMensagem('error', 'Acesso negado. Você não tem permissão para gerenciar serviços.');
    redirecionar(APP_URL . 'dashboard');
}

// Buscar serviços (ativos e inativos)
try {
    $stmt = $pdo->query("
        SELECT s.*, 
               u.nome AS criado_por_nome
        FROM servicos s
        LEFT JOIN usuarios u ON u.id = s.criado_por
        ORDER BY s.ativo DESC, s.nome ASC
    ");
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar serviços: ' . $e->getMessage());
    $servicos = [];
}

$titulo_page = 'Serviços - ERP Sistema';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-cogs"></i> Gerenciar Serviços</h3>
            <a href="<?php echo APP_URL; ?>comercial/servicos/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Serviço
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar serviço</label>
                <input type="text"
                       id="buscaServico"
                       placeholder="Nome do serviço..."
                       onkeyup="filtrarTabela('buscaServico', 'tabelaServicos')">
            </div>
        </div>

        <?php if (empty($servicos)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-cogs"></i>
                <h3>Nenhum serviço encontrado</h3>
                <p>Clique em "Novo Serviço" para cadastrar o primeiro serviço.</p>
            </div>
        <?php else: ?>
            <table id="tabelaServicos">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Certificado</th>
                        <th>Preço Padrão</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $s): ?>
                    <tr class="<?php echo $s['ativo'] ? '' : 'linha-inativa'; ?>">
                        <td>
                            <strong><?php echo h($s['nome']); ?></strong>
                        </td>
                        <td>
                            <small class="text-muted"><?php echo h(mb_strlen($s['descricao'] ?? '') > 80 ? mb_substr($s['descricao'], 0, 80) . '...' : ($s['descricao'] ?? '-')); ?></small>
                        </td>
                        <td><?php echo !empty($s['certificado_modelo']) ? '<span class="badge badge-info">' . h($s['certificado_modelo']) . '</span>' : '<span class="text-muted">-</span>'; ?></td>
                        <td>
                            <span class="preco-destaque"><?php echo formatarMoeda($s['preco_padrao']); ?></span>
                        </td>
                        <td>
                            <?php if ($s['ativo']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Ativo
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-ban"></i> Inativo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>comercial/servicos/form?id=<?php echo urlencode($s['id']); ?>"
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($s['ativo']): ?>
                                    <a href="<?php echo APP_URL; ?>comercial/servicos/actions?action=desativar&id=<?php echo urlencode($s['id']); ?>"
                                       class="btn btn-danger btn-sm"
                                       title="Desativar"
                                       onclick="return confirm('Tem certeza que deseja desativar este serviço?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo APP_URL; ?>comercial/servicos/actions?action=reativar&id=<?php echo urlencode($s['id']); ?>"
                                       class="btn btn-success btn-sm"
                                       title="Reativar"
                                       onclick="return confirm('Deseja reativar este serviço?')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="card-footer" style="padding: 12px 20px;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Total: <?php echo count($servicos); ?> serviço(s) |
                Ativos: <?php echo count(array_filter($servicos, fn($s) => $s['ativo'] == 1)); ?>
            </small>
        </div>
    </div>
</div>

<style>
.linha-inativa {
    opacity: 0.55;
}
.preco-destaque {
    font-weight: 600;
    color: var(--cor-destaque);
    font-size: 1rem;
}
</style>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
