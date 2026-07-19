<?php
/**
 * HEADER DO SISTEMA ERP
 * 
 * Cabecalho padrao de todas as paginas
 */
require_once __DIR__ . '/auth.php';

// Garantir encoding UTF-8
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $titulo_page ?? APP_NAME; ?></title>
    
    <!-- CSS do Sistema -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/erp-v2.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/erp-v2.css'); ?>">
    
    <!-- Font Awesome para icones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    <!-- Toast Notification Function -->
    <script>
    function showToast(message, type = 'success', duration = 4000) {
        const colors = {
            success: { bg: 'rgba(57,211,83,0.12)', border: '#39d353', color: '#39d353', icon: 'fa-check-circle' },
            error: { bg: 'rgba(248,81,73,0.12)', border: '#f85149', color: '#f85149', icon: 'fa-circle-xmark' },
            warn: { bg: 'rgba(210,153,34,0.12)', border: '#d29922', color: '#d29922', icon: 'fa-triangle-exclamation' },
            info: { bg: 'rgba(88,166,255,0.12)', border: '#58a6ff', color: '#58a6ff', icon: 'fa-circle-info' },
        };
        const c = colors[type] || colors.success;
        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.style.cssText = 'background:rgba(9,15,14,0.98);border:1px solid '+c.border+
            ';border-left:4px solid '+c.border+';border-radius:10px;padding:14px 16px;'+
            'display:flex;align-items:flex-start;gap:10px;min-width:320px;max-width:480px;'+
            'pointer-events:all;box-shadow:0 10px 28px rgba(0,0,0,0.48);'+
            'animation:toastIn 0.2s ease;font-family:Inter,sans-serif;font-size:13px;color:#e6edf3;'+
            'line-height:1.45;backdrop-filter:blur(8px);word-break:break-word';
        toast.innerHTML = '<i class="fa-solid '+c.icon+'" style="color:'+c.color+';font-size:15px;margin-top:2px;flex:0 0 auto"></i>'+
            '<span style="flex:1 1 auto;">'+escapeHtml(message)+'</span>'+
            '<button type="button" aria-label="Fechar" onclick="this.parentElement.remove()" style="margin-left:auto;background:none;'+
            'border:none;color:#9aa4b2;cursor:pointer;font-size:14px;line-height:1;padding:0 2px 0 8px;flex:0 0 auto">'+
            '<i class="fa-solid fa-xmark"></i></button>';
        container.appendChild(toast);
        setTimeout(() => toast.style.animation = 'toastOut 0.2s ease forwards', duration - 200);
        setTimeout(() => toast.remove(), duration);
    }
    </script>

    <!-- Toast Messages from PHP Session -->
    <?php if (isset($_SESSION['msg_sucesso'])): ?>
    <script>showToast('<?= addslashes($_SESSION['msg_sucesso']) ?>', 'success');</script>
    <?php unset($_SESSION['msg_sucesso']); endif; ?>

    <?php if (isset($_SESSION['msg_erro'])): ?>
    <script>showToast('<?= addslashes($_SESSION['msg_erro']) ?>', 'error');</script>
    <?php unset($_SESSION['msg_erro']); endif; ?>

    <?php if (isset($_SESSION['msg_aviso'])): ?>
    <script>showToast('<?= addslashes($_SESSION['msg_aviso']) ?>', 'warn');</script>
    <?php unset($_SESSION['msg_aviso']); endif; ?>

    <?php if (isset($_SESSION['msg_info'])): ?>
    <script>showToast('<?= addslashes($_SESSION['msg_info']) ?>', 'info');</script>
    <?php unset($_SESSION['msg_info']); endif; ?>

    <?php if (estaLogado()): ?>
    <!-- Layout Shell -->
    <div class="app-shell">
        
        <!-- Sidebar -->
        <?php if (file_exists(__DIR__ . '/sidebar.php')) require_once __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-area">
            <!-- Header Superior (Topbar) -->
            <?php $isAdminDashboard = getCargo() === 'ADMIN' && preg_match('#/dashboard/?(?:\?.*)?$#', $_SERVER['REQUEST_URI'] ?? ''); ?>
            <header class="topbar<?= $isAdminDashboard ? ' topbar--admin-dashboard' : '' ?>">
                <?php if ($isAdminDashboard): ?>
                <div class="topbar-page-title"><i class="fa-solid fa-bars"></i><strong>Dashboard Administrativo</strong></div>
                <?php else: ?>
                <div class="topbar-search" style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="buscaGlobal" placeholder="Buscar cliente, embarcacao, certificado..." autocomplete="off">
                    <span class="search-shortcut">Ctrl K</span>
                    <div class="search-results" id="searchResults"></div>
                </div>
                <?php endif; ?>
                <div class="topbar-right">
                    <?php if ($isAdminDashboard): ?><time class="topbar-current-date"><i class="fa-regular fa-calendar"></i><?= date('d/m/Y') ?></time><div class="topbar-separator"></div><?php endif; ?>
                    <button class="btn-notification" title="Notificações" onclick="showToast('Nenhuma notificação no momento.', 'info')">
                        <i class="fa-regular fa-bell"></i>
                    </button>
                    <div class="topbar-separator"></div>
                    <div class="topbar-user" onclick="toggleUserMenu()">
                        <div class="topbar-avatar"><?php echo strtoupper(substr($_SESSION['usuario_nome'] ?? 'A', 0, 1)); ?></div>
                        <span class="topbar-username"><?php echo h($_SESSION['usuario_nome'] ?? 'Usuário'); ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <a href="<?php echo APP_URL; ?>perfil"><i class="fa-solid fa-user"></i> Meu Perfil</a>
                            <a href="<?php echo APP_URL; ?>configuracoes"><i class="fa-solid fa-gear"></i> Configurações</a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo APP_URL; ?>login?action=logout" class="text-error"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
                        </div>
                    </div>
                </div>
            </header>
    <?php endif; ?>

            <!-- Mensagens -->
            <?php
            if (function_exists('getMensagem')) {
                $msg = getMensagem();
                if ($msg):
                    $icones = [
                        'success' => 'fa-check-circle',
                        'error'   => 'fa-exclamation-circle',
                        'warning' => 'fa-exclamation-triangle',
                        'info'    => 'fa-info-circle'
                    ];
                    $icon = $icones[$msg['tipo']] ?? 'fa-info-circle';
            ?>
            <div class="message message-<?php echo h($msg['tipo']); ?>" id="mensagem" onclick="this.remove()">
                <i class="fas <?php echo $icon; ?>"></i>
                <span><?php echo h($msg['texto']); ?></span>
                <button class="close-msg" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php if ($msg['tipo'] === 'error' && (!empty($msg['valores']) || !empty($msg['campos']))): ?>
            <script>
            window.__formFeedback = <?php echo json_encode([
                'valores' => $msg['valores'] ?? [],
                'campos' => $msg['campos'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            </script>
            <?php endif; ?>
            <?php
                endif;
            }
            ?>

    <script>
    // Busca Global
    let buscaTimeout;
    const buscaInput = document.getElementById('buscaGlobal');
    const buscaResults = document.getElementById('searchResults');

    if (buscaInput) {
        buscaInput.addEventListener('input', function() {
            clearTimeout(buscaTimeout);
            const q = this.value.trim();
            if (q.length < 2) {
                buscaResults.classList.remove('show');
                return;
            }
            buscaTimeout = setTimeout(() => {
                fetch('<?php echo APP_URL; ?>busca-global?q=' + encodeURIComponent(q))
                    .then(r => {
                        if (!r.ok) throw new Error('Erro HTTP');
                        return r.json();
                    })
                    .then(data => {
                        if (data.erro || !data || data.length === 0) {
                            buscaResults.innerHTML = '<div class="search-result-empty">Nenhum resultado encontrado</div>';
                            buscaResults.classList.add('show');
                            return;
                        }
                        const baseUrl = '<?php echo APP_URL; ?>';
                        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                        })[char]);
                        buscaResults.innerHTML = data.map(item => {
                            const icones = { embarcacao: 'fa-ship', proprietario: 'fa-user', armador: 'fa-anchor', despachante: 'fa-briefcase', vistoria: 'fa-clipboard-check' };
                            const icon = icones[item.tipo] || 'fa-search';
                            return '<a href="' + baseUrl + encodeURI(item.url) + '" class="search-result-item">' +
                                '<i class="fa-solid ' + icon + '"></i>' +
                                '<span class="search-result-nome">' + escapeHtml(item.nome) + '</span>' +
                                '<span class="search-result-tipo">' + escapeHtml(item.tipo) + '</span>' +
                                '</a>';
                        }).join('');
                        buscaResults.classList.add('show');
                    })
                    .catch(() => {
                        buscaResults.innerHTML = '<div class="search-result-empty">Erro ao buscar</div>';
                        buscaResults.classList.add('show');
                    });
            }, 300);
        });

        buscaInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const primeiro = buscaResults.querySelector('.search-result-item');
                if (primeiro) {
                    window.location.href = primeiro.getAttribute('href');
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (!buscaInput.parentElement.contains(e.target)) {
                buscaResults.classList.remove('show');
            }
        });

        // Atalho Ctrl+K / ⌘K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                buscaInput.focus();
            }
            if (e.key === 'Escape') {
                buscaResults.classList.remove('show');
                buscaInput.blur();
            }
        });
    }
    </script>

            <!-- Conteudo Principal -->
            <main class="page-content">
