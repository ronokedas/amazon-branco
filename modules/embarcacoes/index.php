<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: index.php - Listagem de embarcacoes (ADMIN e VISTORIADOR)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
exigirAcesso('embarcacoes');

// Buscar apenas embarcacoes ativas
try {
    $stmt = $pdo->query("SELECT id, nome, tipo, tipo_embarcacao, numero_inscricao, proprietario, ano, observacoes, foto_url, ativo, criado_em, atualizado_em FROM embarcacoes WHERE ativo = 1 ORDER BY criado_em DESC, nome ASC");
    $embarcacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar embarcacoes: ' . $e->getMessage());
    $embarcacoes = [];
}

$titulo_page = 'Embarcacoes - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <style>
        .embarcacao-identidade{display:flex;align-items:center;gap:11px}.embarcacao-identidade img{width:54px;height:44px;object-fit:cover;border:1px solid var(--cor-borda);border-radius:8px;background:#edf2f0}.embarcacao-identidade span{display:grid;gap:2px}.embarcacao-identidade small{color:var(--cor-texto-secundario)}
    </style>
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-ship"></i> Gerenciar Embarcacoes</h3>
            <a href="<?php echo APP_URL; ?>embarcacoes/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Embarcacao
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar embarcacao</label>
                <input type="text" 
                       id="buscaEmbarcacao" 
                       placeholder="Nome ou número de inscrição..."
                       onkeyup="filtrarTabela('buscaEmbarcacao', 'tabelaEmbarcacoes')">
            </div>
        </div>

        <?php if (empty($embarcacoes)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-ship"></i>
                <h3>Nenhuma embarcacao encontrada</h3>
                <p>Clique em "Nova Embarcacao" para cadastrar a primeira embarcacao.</p>
            </div>
        <?php else: ?>
            <table id="tabelaEmbarcacoes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Número de Inscrição</th>
                        <th>Proprietario</th>
                        <th>Ano</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($embarcacoes as $e): ?>
                    <tr>
                        <td>
                            <span class="embarcacao-identidade">
                                <img src="<?= h($e['foto_url'] ?: APP_URL . 'assets/img/portal-hero-ship.png') ?>" alt="Foto de <?= h($e['nome']) ?>" loading="lazy">
                                <span><strong><?php echo h($e['nome']); ?></strong><small><?= $e['foto_url'] ? 'Foto oficial' : 'Sem foto oficial' ?></small></span>
                            </span>
                        </td>
                        <td><?php echo h($e['tipo_embarcacao'] ?: ($e['tipo'] ?? '-')); ?></td>
                        <td><?php echo h($e['numero_inscricao'] ?? '-'); ?></td>
                        <td><?php echo h($e['proprietario'] ?? '-'); ?></td>
                        <td><?php echo h($e['ano'] ?? '-'); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>embarcacoes/form?id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>embarcacoes/actions?action=desativar&id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Desativar"
                                   onclick="return confirm('Tem certeza que deseja desativar esta embarcacao?')">
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
                Total: <?php echo count($embarcacoes); ?> embarcacao(oes) ativa(s)
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
