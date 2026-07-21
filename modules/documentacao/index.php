<?php
/**
 * MODULO: DOCUMENTAÇÃO
 * Arquivo: index.php - Página principal do módulo
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticacao
exigirAcesso('documentacao');

$titulo_page = 'Documentação - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Documentação</h1>
            <p class="page-subtitle">Certificados e documentos técnicos</p>
        </div>
    </div>

    <div class="empty-state">
        <div class="empty-icon">
            <i class="fa-solid fa-book-open"></i>
        </div>
        <h3 class="empty-title">Módulo de Documentação</h3>
        <p class="empty-desc">Selecione uma opção no menu lateral para acessar os certificados e documentos.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
