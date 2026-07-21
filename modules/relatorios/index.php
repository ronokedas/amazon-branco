<?php
/**
 * MODULO: RELATÓRIOS
 * Arquivo: index.php - Página placeholder
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticacao
exigirAcesso('relatorios');

$titulo_page = 'Relatórios - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Relatórios</h1>
            <p class="page-subtitle">Módulo em desenvolvimento</p>
        </div>
    </div>

    <div class="empty-state">
        <div class="empty-icon">
            <i class="fa-solid fa-chart-column"></i>
        </div>
        <h3 class="empty-title">Módulo de Relatórios</h3>
        <p class="empty-desc">Este módulo está em desenvolvimento e estará disponível em breve.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
