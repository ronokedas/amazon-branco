<?php
require_once __DIR__ . '/cliente_portal.php';
header('Content-Type: text/html; charset=UTF-8');
$titulo_page = $titulo_page ?? 'Portal do Cliente - ' . APP_NAME;
$portalRequestUri = $_SERVER['REQUEST_URI'] ?? '';
$portalDocumentosAtivo = strpos($portalRequestUri, '/portal/documentos') !== false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($titulo_page); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/erp-v2.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/erp-v2.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="portal-body">
<?php if (clienteEstaLogado()): ?>
    <header class="portal-topbar">
        <div class="portal-topbar-inner">
            <button class="portal-menu-button" type="button" aria-label="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="portal-brand" href="<?php echo APP_URL; ?>portal">
                <img src="<?php echo APP_URL; ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora">
            </a>
            <nav class="portal-nav">
                <a class="<?php echo !$portalDocumentosAtivo ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>portal">Portal do Cliente</a>
                <a class="<?php echo $portalDocumentosAtivo ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>portal/documentos">Meus documentos</a>
                <a href="<?php echo APP_URL; ?>portal/documentos">Embarcações</a>
            </nav>
            <div class="portal-top-actions" aria-hidden="true">
                <span><i class="fa-regular fa-bell"></i></span>
                <span><i class="fa-regular fa-circle-question"></i></span>
            </div>
            <div class="portal-user-chip">
                <span><?php echo h(strtoupper(substr(clientePortalNome(), 0, 1))); ?></span>
                <div>
                    <strong><?php echo h(clientePortalNome()); ?></strong>
                    <small>Cliente</small>
                </div>
                <a href="<?php echo APP_URL; ?>portal/logout" title="Sair"><i class="fa-solid fa-chevron-down"></i></a>
            </div>
        </div>
    </header>
<?php endif; ?>
<main class="portal-page">
<?php
$msg = function_exists('getMensagem') ? getMensagem() : null;
if ($msg):
?>
    <div class="message message-<?php echo h($msg['tipo']); ?>">
        <i class="fas fa-info-circle"></i>
        <span><?php echo h($msg['texto']); ?></span>
    </div>
<?php endif; ?>
