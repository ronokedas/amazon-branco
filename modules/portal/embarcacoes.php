<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);
$titulo_page = 'Embarcações - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Embarcações</h1>
        <p>Consulte as embarcações vinculadas ao seu acesso e seus respectivos documentos.</p>
    </div>
    <div class="portal-page-header-mark"><i class="fas fa-ship"></i></div>
</section>

<?php if (!$embarcacoes): ?>
    <section class="portal-panel">
        <div class="portal-empty">
            <i class="fas fa-ship"></i>
            <h2>Nenhuma embarcação vinculada</h2>
            <p>Entre em contato com a certificadora caso espere encontrar uma embarcação aqui.</p>
        </div>
    </section>
<?php else: ?>
    <section class="portal-boats-grid">
        <?php foreach ($embarcacoes as $emb): ?>
            <article class="portal-boat-card">
                <i class="fas fa-ship"></i>
                <div>
                    <h2><?php echo h($emb['nome']); ?></h2>
                    <div class="portal-boat-meta">
                        <span><strong>Registro:</strong> <?php echo h($emb['registro'] ?: ($emb['numero_inscricao'] ?: '-')); ?></span>
                        <span><strong>Tipo:</strong> <?php echo h($emb['tipo_embarcacao'] ?: '-'); ?></span>
                    </div>
                    <a class="btn btn-primary btn-sm" href="<?php echo APP_URL; ?>portal/documentos?embarcacao_id=<?php echo urlencode($emb['id']); ?>">
                        <i class="fas fa-file-lines"></i> Ver documentos
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
