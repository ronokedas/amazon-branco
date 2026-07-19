<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: geral.php - Configurações Gerais
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Buscar configuracoes gerais (exclui backup_email)
try {
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES ('meta_mensagem', '', 'Mensagem da meta mensal exibida para a equipe')");
    $stmt = $pdo->query("SELECT chave, valor, descricao FROM configuracoes WHERE chave IN ('meta_mensal', 'meta_mensagem') ORDER BY FIELD(chave, 'meta_mensal', 'meta_mensagem')");
    $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $configMap = [];
    foreach ($configuracoes as $c) {
        $configMap[$c['chave']] = $c;
    }
} catch (Exception $e) {
    $configMap = [];
}

$titulo_page = 'Configurações Gerais - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div>
                <h1><i class="fas fa-sliders-h"></i> Configurações Gerais</h1>
                <p>Gerencie os parâmetros gerais e comerciais.</p>
            </div>
            <a href="<?php echo APP_URL; ?>configuracoes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (empty($configMap)): ?>
        <div class="tabela-vazia">
            <i class="fas fa-cogs"></i>
            <h3>Nenhuma configuração encontrada</h3>
            <p>Não foi possível carregar as configurações do sistema.</p>
        </div>
    <?php else: ?>
        <div class="card" style="max-width: 700px;">
            <div class="card-header">
                <h3 style="color: var(--cor-destaque);"><i class="fas fa-sliders-h"></i> Parâmetros</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo APP_URL; ?>configuracoes/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                    <input type="hidden" name="action" value="salvar">
                    <input type="hidden" name="redirect_to" value="configuracoes/geral">

                    <?php foreach ($configMap as $chave => $cfg): ?>
                        <div class="form-group" style="margin-bottom: 22px;">
                            <label for="cfg_<?php echo h($chave); ?>">
                                <?php
                                    $labels = [
                                        'meta_mensal' => 'Meta Mensal (R$)',
                                        'meta_mensagem' => 'Mensagem da meta para a equipe',
                                    ];
                                    echo $labels[$chave] ?? h($cfg['descricao'] ?: $chave);
                                ?>
                            </label>
                            <?php if ($chave === 'meta_mensal'): ?>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span style="font-size: 1.1rem; font-weight: 600; color: var(--cor-destaque);">R$</span>
                                    <input type="number" step="0.01" min="0.01" id="cfg_<?php echo h($chave); ?>"
                                           name="cfg[<?php echo h($chave); ?>]"
                                           value="<?php echo h($cfg['valor']); ?>"
                                           style="flex: 1; padding: 10px 14px; font-size: 1.1rem; font-weight: 600;">
                                </div>
                                <small style="display: block; color: var(--cor-texto-secundario); margin-top: 4px;">
                                    <i class="fas fa-info-circle"></i> 
                                    Valor utilizado para calcular o percentual de meta atingida no Dashboard.
                                </small>
                            <?php elseif ($chave === 'meta_mensagem'): ?>
                                <textarea id="cfg_meta_mensagem" name="cfg[meta_mensagem]" rows="3" maxlength="500"
                                          placeholder="Ex.: Ao batermos a meta, teremos um dia especial com toda a equipe."
                                          style="width: 100%; padding: 10px 14px;"><?php echo h($cfg['valor']); ?></textarea>
                                <small style="display:block;color:var(--cor-texto-secundario);margin-top:4px;"><i class="fas fa-bullhorn"></i> Opcional. Se ficar vazio, nenhuma mensagem será mostrada.</small>
                            <?php else: ?>
                                <input type="text" id="cfg_<?php echo h($chave); ?>" name="cfg[<?php echo h($chave); ?>]" value="<?php echo h($cfg['valor']); ?>">
                                <?php if (!empty($cfg['descricao'])): ?>
                                    <small style="display: block; color: var(--cor-texto-secundario); margin-top: 4px;">
                                        <?php echo h($cfg['descricao']); ?>
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
