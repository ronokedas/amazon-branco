<?php
/**
 * MODULO: CLIENTES
 * Arquivo: index.php - Listagem de despachantes (ADMIN e VISTORIADOR)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao
verificar_sessao();
$cargo = getCargo();
exigirAcesso('despachantes');

// Buscar despachantes ativos com total de embarcacoes vinculadas
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(DISTINCT ce.id) AS total_embarcacoes,
               GROUP_CONCAT(DISTINCT te.nome ORDER BY te.nome SEPARATOR ', ') AS tipos_atendidos
        FROM clientes c
        LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status='ATIVO'
        LEFT JOIN clientes_tipos_embarcacao cte ON cte.cliente_id = c.id
        LEFT JOIN tipos_embarcacao te ON te.id = cte.tipo_embarcacao_id
        WHERE c.perfil = 'despachante' AND c.status = 'ATIVO'
        GROUP BY c.id
        ORDER BY c.criado_em DESC, c.nome ASC
    ");
    $despachantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar despachantes: ' . $e->getMessage());
    $despachantes = [];
}

$titulo_page = 'Despachantes - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-user-tie"></i> Gerenciar Despachantes</h3>
            <a href="<?php echo APP_URL; ?>despachantes/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Despachante
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar despachante</label>
                <input type="text" 
                       id="buscaDespachante" 
                       placeholder="Nome, CPF/CNPJ ou email..." 
                       onkeyup="filtrarTabela('buscaDespachante', 'tabelaDespachantes')">
            </div>
        </div>

        <?php if (empty($despachantes)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-user-tie"></i>
                <h3>Nenhum despachante encontrado</h3>
                <p>Clique em "Novo Despachante" para cadastrar o primeiro despachante.</p>
            </div>
        <?php else: ?>
            <table id="tabelaDespachantes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Perfil</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Tipos atendidos</th>
                        <th>Embarcações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($despachantes as $c): ?>
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
                        <td><?php echo !empty($c['tipos_atendidos']) ? h($c['tipos_atendidos']) : '<span class="text-muted">Não informado</span>'; ?></td>
                        <td class="text-center"><?php echo (int)$c['total_embarcacoes']; ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>despachantes/form?id=<?php echo urlencode($c['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>despachantes/actions?action=desativar&id=<?php echo urlencode($c['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Desativar"
                                   onclick="return confirm('Tem certeza que deseja desativar este despachante?')">
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
                Total: <?php echo count($despachantes); ?> despachante(s) ativo(s)
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
