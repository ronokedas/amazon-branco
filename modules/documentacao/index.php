<?php
/**
 * MODULO: DOCUMENTAÇÃO
 * Arquivo: index.php - Página principal do módulo
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticacao e redirecionar para o primeiro modelo disponivel
verificar_sessao();
$temAlgumDoc = podeAcessar('documentacao')
    || podeAcessar('doc_csn')
    || podeAcessar('doc_cnbl')
    || podeAcessar('doc_cnarq')
    || podeAcessar('doc_nar')
    || podeAcessar('doc_lp')
    || podeAcessar('doc_lc')
    || podeAcessar('doc_cht');

if (!$temAlgumDoc) {
    exigirAcesso('documentacao');
}

// Redirecionamento inteligente para a tela permitida do usuario
if (podeAcessar('doc_csn')) {
    redirecionar(APP_URL . 'documentacao/certificados');
} elseif (podeAcessar('doc_nar')) {
    redirecionar(APP_URL . 'documentacao/nar');
} elseif (podeAcessar('doc_lc')) {
    redirecionar(APP_URL . 'documentacao/lc');
} elseif (podeAcessar('doc_lp')) {
    redirecionar(APP_URL . 'documentacao/lp');
} elseif (podeAcessar('doc_cnbl')) {
    redirecionar(APP_URL . 'documentacao/cnbl');
} elseif (podeAcessar('doc_cnarq')) {
    redirecionar(APP_URL . 'documentacao/cnarq');
} elseif (podeAcessar('doc_cht')) {
    redirecionar(APP_URL . 'documentacao/cht');
}

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
