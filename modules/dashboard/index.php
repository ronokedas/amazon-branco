<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
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
    <?php require __DIR__ . '/views/' . ($viewMap[$cargo] ?? 'vistoriador.php'); ?>
    <footer class="role-dashboard__footer">Dados atualizados em <?= date('d/m/Y H:i') ?> · Amazon Certificadora</footer>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
