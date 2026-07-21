<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/feedback.php';
require_once __DIR__ . '/data.php';
requireLogin();
$cargo = getCargo() ?: 'VISTORIADOR';
$usuarioId = $_SESSION['usuario_id'] ?? '';
$dashboard = dashboardLoadData($pdo, $cargo, $usuarioId);
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
    <footer class="role-dashboard__footer">Dados atualizados em <?= date('d/m/Y H:i') ?> · Amazon Certificadora</footer>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
