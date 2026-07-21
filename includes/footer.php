<?php
/**
 * FOOTER DO SISTEMA ERP
 * 
 * Rodapé padrão de todas as páginas
 */
?>
    </main><!-- /page-content -->
    <?php if (estaLogado()): ?>
            <!-- Footer Inferior -->
            <footer class="footer-main">
                <div class="footer-content">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Todos os direitos reservados.</p>
                    <p>Versão 1.0.0</p>
                </div>
            </footer>
        </div><!-- /main-area -->
    </div><!-- /app-shell -->
    <?php endif; ?>

    <!-- Botão Mobile para Sidebar -->
    <?php if (estaLogado()): ?>
    <button class="sidebar-mobile-toggle" onclick="toggleSidebarMobile()" title="Menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Drawer Overlay -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    
    <!-- Drawer -->
    <div class="drawer" id="drawer">
        <div class="drawer-header">
            <h2 class="drawer-title" id="drawerTitle">Novo Registro</h2>
            <button class="drawer-close" onclick="closeDrawer()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="drawer-body" id="drawerBody">
            <!-- conteúdo dinâmico -->
        </div>
    </div>

    <!-- Toggle Sidebar -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        }
    });
    
    // Toggle sidebar em mobile
    function toggleSidebarMobile() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    }
    
    // Fechar sidebar ao clicar no overlay
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar && sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            !e.target.closest('.sidebar-mobile-toggle')) {
            sidebar.classList.remove('active');
        }
    });
    </script>
    
    <!-- Drawer Functions -->
    <script>
    function openDrawer(title, bodyHtml) {
        document.getElementById('drawerTitle').textContent = title;
        document.getElementById('drawerBody').innerHTML = bodyHtml;
        document.getElementById('drawer').classList.add('open');
        document.getElementById('drawerOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDrawer() {
        document.getElementById('drawer').classList.remove('open');
        document.getElementById('drawerOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }
    
    // Fechar drawer com tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDrawer();
        }
    });
    
    // Toggle user dropdown menu
    function toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    }
    
    // Fechar dropdown do usuario ao clicar fora
    document.addEventListener('click', function(e) {
        const userBtn = document.querySelector('.topbar-user');
        const dropdown = document.getElementById('userDropdown');
        
        if (userBtn && dropdown) {
            if (!userBtn.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        }
    });
    </script>
    <?php endif; ?>

    <!-- Toast Container -->
    <div id="toast-container" style="position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none"></div>

    <!-- JavaScript -->
    <script src="<?php echo APP_URL; ?>assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/app.js'); ?>"></script>
    <script src="<?php echo APP_URL; ?>assets/js/erp-v2.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/erp-v2.js'); ?>"></script>
    
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
        const toast = document.createElement('div');
        toast.style.cssText = 'background:#161b22;border:1px solid '+c.border+
            ';border-left:3px solid '+c.border+';border-radius:8px;padding:12px 16px;'+
            'display:flex;align-items:center;gap:10px;min-width:260px;max-width:380px;'+
            'pointer-events:all;box-shadow:0 4px 16px rgba(0,0,0,0.4);'+
            'animation:toastIn 0.2s ease;font-family:Inter,sans-serif;font-size:13px;color:#e6edf3';
        toast.innerHTML = '<i class="fa-solid '+c.icon+'" style="color:'+c.color+';font-size:15px"></i>'+
            '<span>'+message+'</span>'+
            '<button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;'+
            'border:none;color:#6e7681;cursor:pointer;font-size:14px;line-height:1">'+
            '<i class="fa-solid fa-xmark"></i></button>';
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.style.animation = 'toastOut 0.2s ease forwards', duration - 200);
        setTimeout(() => toast.remove(), duration);
    }
    </script>
    
</body>
</html>
