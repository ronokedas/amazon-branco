<?php
/**
 * MODULO: CLIENTES
 * Arquivo: index.php - Listagem de armadores (ADMIN e VISTORIADOR)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao
verificar_sessao();
$cargo = getCargo();
exigirAcesso('armadores');

// Buscar armadores ativos com total de embarcacoes vinculadas
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(ce.id) AS total_embarcacoes
        FROM clientes c
        LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status='ATIVO'
        WHERE c.perfil = 'armador' AND c.status = 'ATIVO'
        GROUP BY c.id
        ORDER BY c.criado_em DESC, c.nome ASC
    ");
    $armadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar armadores: ' . $e->getMessage());
    $armadores = [];
}

$titulo_page = 'Armadors - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-user-tie"></i> Gerenciar Armadors</h3>
            <a href="<?php echo APP_URL; ?>armadores/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Armador
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar armador</label>
                <input type="text" 
                       id="buscaArmador" 
                       placeholder="Nome, CPF/CNPJ ou email..." 
                       onkeyup="filtrarTabela('buscaArmador', 'tabelaArmadors')">
            </div>
        </div>

        <?php if (empty($armadores)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-user-tie"></i>
                <h3>Nenhum armador encontrado</h3>
                <p>Clique em "Novo Armador" para cadastrar o primeiro armador.</p>
            </div>
        <?php else: ?>
            <table id="tabelaArmadors">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Perfil</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Embarcações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($armadores as $c): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($c['nome']); ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $c['perfil'] === 'armador' ? 'primary' : ($c['perfil'] === 'despachante' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst(h($c['perfil'])); ?>
                            </span>
                        </td>
                        <td><?php echo h($c['cpf_cnpj'] ?? '-'); ?></td>
                        <td><?php echo h($c['telefone'] ?? '-'); ?></td>
                        <td><?php echo h($c['email'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo (int)$c['total_embarcacoes']; ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>armadores/form?id=<?php echo urlencode($c['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>armadores/actions?action=desativar&id=<?php echo urlencode($c['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Desativar"
                                   onclick="return confirm('Tem certeza que deseja desativar este armador?')">
                                    <i class="fas fa-ban"></i>
                                </a>
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
                Total: <?php echo count($armadores); ?> armador(s) ativo(s)
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
