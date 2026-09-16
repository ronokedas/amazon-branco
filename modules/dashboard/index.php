<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/feedback.php';
require_once __DIR__ . '/data.php';
requireLogin();
$cargo = getCargo() ?: 'VISTORIADOR';
$usuarioId = $_SESSION['usuario_id'] ?? '';
$forceRefresh = !empty($_GET['refresh']);
$dashboard = dashboardGetCachedData($pdo, $cargo, $usuarioId, $forceRefresh, 45);
$viewMap = ['ADMIN'=>'admin.php','VENDEDOR'=>'vendedor.php','VISTORIADOR'=>'vistoriador.php','ANALISTA'=>'analista.php'];
$titulo_page = match($cargo) { 'ADMIN'=>'Central de comando', 'VENDEDOR'=>'Painel comercial', 'ANALISTA'=>'Central de análise', default=>'Minha operação' } . ' - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<main class="role-dashboard role-dashboard--<?= strtolower(h($cargo)) ?>" id="mainContent">
    <?php $feedbackDashboard=feedbackResumoNaoLidas($pdo,$usuarioId,3); if($feedbackDashboard['count']): ?>
    <section class="feedback-dashboard-card"><div><i class="fa-regular fa-comments"></i><span><strong><?= $feedbackDashboard['count'] ?> conversa<?= $feedbackDashboard['count']===1?'':'s' ?> com novidades</strong><small><?php foreach($feedbackDashboard['recentes'] as $i=>$r):?><?= $i?' · ':'' ?><?=h($r['remetente'])?>: <?=h(mb_strimwidth($r['previa'],0,45,'…'))?><?php endforeach;?></small></span></div><a href="<?=APP_URL?>feedback">Abrir Central <i class="fa-solid fa-arrow-right"></i></a></section>
    <?php endif; ?>
    <?php require __DIR__ . '/views/' . ($viewMap[$cargo] ?? 'vistoriador.php'); ?>
    <footer class="role-dashboard__footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span>
            Dados atualizados em <?= date('d/m/Y H:i', $dashboard['_cache']['cached_at'] ?? time()) ?>
            <?php if (!empty($dashboard['_cache']['cached'])): ?>
                <small class="text-muted" style="margin-left: 6px;">(em cache <?= ($dashboard['_cache']['age'] ?? 0) ?>s)</small>
            <?php endif; ?>
            · Amazon Certificadora
        </span>
        <a href="<?= APP_URL ?>dashboard?refresh=1" class="btn btn-sm btn-secondary" style="font-size: 0.8rem; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;" title="Recalcular indicadores em tempo real">
            <i class="fa-solid fa-arrows-rotate"></i> Atualizar dados
        </a>
    </footer>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
