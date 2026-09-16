<?php
/**
 * Menu lateral inteligente e executivo orientado pelas diretrizes navais NORMAM/DPC e permissões do usuário.
 */
$cargo = getCargo();
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = rtrim((string)parse_url($request_uri, PHP_URL_PATH), '/');
$app_folder = '/' . basename(__DIR__ . '/..');
if (strpos($path, $app_folder) === 0) $path = substr($path, strlen($app_folder));
$pagina_atual = ltrim($path, '/') ?: 'dashboard';
if (!function_exists('isActive')) {
    function isActive($page, $pagina_atual) { return $pagina_atual === $page ? ' active' : ''; }
}
?>
<style>
/* Busca rápida no menu lateral */
.sidebar-search-wrap {
    position: relative;
    margin: 6px 12px 12px;
}
.sidebar-search-input {
    width: 100%;
    height: 34px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(86, 224, 173, 0.18);
    border-radius: 9px;
    padding: 0 28px 0 32px;
    font-size: 0.8rem;
    color: var(--text-primary, #ffffff);
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.sidebar-search-input:focus {
    background: rgba(255, 255, 255, 0.07);
    border-color: #56e0ad;
    box-shadow: 0 0 10px rgba(86, 224, 173, 0.25);
}
.sidebar-search-input::placeholder {
    color: rgba(230, 237, 243, 0.42);
    font-size: 0.76rem;
}
.sidebar-search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    color: rgba(86, 224, 173, 0.65);
    pointer-events: none;
}
.sidebar-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.45);
    cursor: pointer;
    font-size: 0.75rem;
    padding: 4px;
    display: none;
}
.sidebar-search-clear:hover {
    color: #ffffff;
}
.sidebar.collapsed .sidebar-search-wrap {
    display: none;
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo-area">
        <a href="<?= APP_URL ?>dashboard" class="logo-title" aria-label="Amazon Certificadora - Início">
            <img src="<?= APP_URL ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora" class="sidebar-brand-logo">
            <img src="<?= APP_URL ?>img/logo-amazon-icon.svg" alt="" class="sidebar-brand-icon" aria-hidden="true">
        </a>
        <button class="btn-sidebar-toggle" id="sidebar-toggle" title="Recolher / Expandir Menu"><i class="fa-solid fa-chevron-left"></i></button>
    </div>

    <!-- Campo de Busca Rápida no Menu (Ctrl+K) -->
    <div class="sidebar-search-wrap">
        <i class="fa-solid fa-magnifying-glass sidebar-search-icon"></i>
        <input type="text" class="sidebar-search-input" id="sidebarSearch" placeholder="Buscar no menu (Ctrl+K)..." autocomplete="off" spellcheck="false">
        <button type="button" class="sidebar-search-clear" id="sidebarSearchClear" aria-label="Limpar busca"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <nav class="sidebar-nav" id="sidebarNav">
        <!-- 1. PAINEL PRINCIPAL -->
        <?php if (podeAcessar('dashboard')): ?>
            <a href="<?= APP_URL ?>dashboard" class="nav-item<?= isActive('dashboard',$pagina_atual) ?>" data-label="Dashboard">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        <?php endif; ?>

        <!-- 2. OPERAÇÃO NAVAL (Ciclo cronológico de vistorias e emissão) -->
        <?php if (podeAcessar('agendamentos') || podeAcessar('vistorias') || podeAcessar('relatorios_aprovacao') || podeAcessar('certificados') || podeAcessar('documentacao')): ?>
            <div class="nav-group-label">OPERAÇÃO NAVAL</div>
            <?php if (podeAcessar('agendamentos')): ?>
                <a href="<?= APP_URL ?>agendamentos" class="nav-item<?= isActive('agendamentos',$pagina_atual) ?>" data-label="Agendamentos">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span class="nav-text">Agendamentos</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('vistorias')): ?>
                <a href="<?= APP_URL ?>vistorias" class="nav-item<?= (isActive('vistorias',$pagina_atual) || (strpos($pagina_atual,'vistorias')===0 && strpos($pagina_atual,'vistorias/aprovacao')===false && strpos($pagina_atual,'documentacao/aprovacao_relatorios')===false)) ? ' active' : '' ?>" data-label="Vistorias">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span class="nav-text">Vistorias Técnicas</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('relatorios_aprovacao')): ?>
                <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="nav-item<?= isActive('documentacao/aprovacao_relatorios',$pagina_atual) ?>" data-label="Aprovação Vistorias">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <span class="nav-text">Aprovação Vistorias</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('certificados')): ?>
                <a href="<?= APP_URL ?>certificados" class="nav-item<?= isActive('certificados',$pagina_atual) ?>" data-label="Certificados">
                    <i class="fa-solid fa-award"></i>
                    <span class="nav-text">Certificados</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('documentacao')): ?>
                <div class="nav-group">
                    <a href="#" class="nav-item<?= (strpos($pagina_atual,'documentacao')===0 && strpos($pagina_atual,'documentacao/aprovacao_relatorios')===false) ? ' active' : '' ?>" data-label="Modelos Estatutários" onclick="this.parentElement.querySelector('.nav-submenu').classList.toggle('open');this.querySelector('.nav-chevron')?.classList.toggle('rotated');return false;">
                        <i class="fa-solid fa-file-shield"></i>
                        <span class="nav-text">Modelos Estatutários</span>
                        <i class="fa-solid fa-chevron-down nav-chevron<?= (strpos($pagina_atual,'documentacao')===0 && strpos($pagina_atual,'documentacao/aprovacao_relatorios')===false) ? ' rotated' : '' ?>"></i>
                    </a>
                    <div class="nav-submenu<?= (strpos($pagina_atual,'documentacao')===0 && strpos($pagina_atual,'documentacao/aprovacao_relatorios')===false) ? ' open' : '' ?>">
                        <a href="<?= APP_URL ?>documentacao/certificados" class="nav-item nav-subitem<?= isActive('documentacao/certificados',$pagina_atual) ?>">Certificados (CSN)</a>
                        <a href="<?= APP_URL ?>documentacao/cnbl" class="nav-item nav-subitem<?= isActive('documentacao/cnbl',$pagina_atual) ?>">CNBL</a>
                        <a href="<?= APP_URL ?>documentacao/cnarq" class="nav-item nav-subitem<?= isActive('documentacao/cnarq',$pagina_atual) ?>">CNARQ</a>
                        <a href="<?= APP_URL ?>documentacao/lp" class="nav-item nav-subitem<?= isActive('documentacao/lp',$pagina_atual) ?>">LP</a>
                        <a href="<?= APP_URL ?>documentacao/lc" class="nav-item nav-subitem<?= isActive('documentacao/lc',$pagina_atual) ?>">LC</a>
                        <a href="<?= APP_URL ?>documentacao/cht" class="nav-item nav-subitem<?= isActive('documentacao/cht',$pagina_atual) ?>">CHT</a>
                    </div>
                </div>
            <?php endif; ?>
            <a href="<?= APP_URL ?>minhas-assinaturas" class="nav-item<?= isActive('minhas-assinaturas',$pagina_atual) ?>" data-label="Minhas assinaturas">
                <i class="fa-solid fa-file-signature"></i>
                <span class="nav-text">Minhas assinaturas</span>
            </a>
        <?php else: ?>
            <a href="<?= APP_URL ?>minhas-assinaturas" class="nav-item<?= isActive('minhas-assinaturas',$pagina_atual) ?>" data-label="Minhas assinaturas">
                <i class="fa-solid fa-file-signature"></i>
                <span class="nav-text">Minhas assinaturas</span>
            </a>
        <?php endif; ?>

        <!-- 3. ENGENHARIA & CAPITANIA (Projetos e órgãos reguladores) -->
        <?php if (podeAcessar('analise_planos') || podeAcessar('protocolos_documentais')): ?>
            <div class="nav-group-label">ENGENHARIA & CAPITANIA</div>
            <?php if (podeAcessar('analise_planos')): ?>
                <a href="<?= APP_URL ?>analises-planos" class="nav-item<?= strpos($pagina_atual,'analises-planos')===0?' active':'' ?>" data-label="Análise de Planos">
                    <i class="fa-solid fa-drafting-compass"></i>
                    <span class="nav-text">Análise de Planos</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('protocolos_documentais')): ?>
                <a href="<?= APP_URL ?>protocolos" class="nav-item<?= strpos($pagina_atual,'protocolos')===0?' active':'' ?>" data-label="Protocolos documentais">
                    <i class="fa-solid fa-folder-tree"></i>
                    <span class="nav-text">Protocolos & Dossiês</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 4. CADASTROS NAVAIS -->
        <?php if (podeAcessar('embarcacoes') || podeAcessar('clientes') || podeAcessar('armadores') || podeAcessar('proprietarios') || podeAcessar('despachantes')): ?>
            <div class="nav-group-label">CADASTROS</div>
            <?php if (podeAcessar('embarcacoes')): ?>
                <a href="<?= APP_URL ?>embarcacoes" class="nav-item<?= isActive('embarcacoes',$pagina_atual) ?>" data-label="Embarcações">
                    <i class="fa-solid fa-ship"></i>
                    <span class="nav-text">Embarcações</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('clientes') || podeAcessar('armadores') || podeAcessar('proprietarios') || podeAcessar('despachantes')): ?>
                <a href="<?= APP_URL ?>clientes" class="nav-item<?= (strpos($pagina_atual,'clientes')===0 || strpos($pagina_atual,'armadores')===0 || strpos($pagina_atual,'proprietarios')===0 || strpos($pagina_atual,'despachantes')===0)?' active':'' ?>" data-label="Clientes e Atores">
                    <i class="fa-solid fa-users-gear"></i>
                    <span class="nav-text">Clientes & Atores</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 5. COMERCIAL -->
        <?php if (podeAcessar('comercial') || podeAcessar('servicos')): ?>
            <div class="nav-group-label">COMERCIAL</div>
            <?php if (podeAcessar('comercial')): ?>
                <a href="<?= APP_URL ?>comercial" class="nav-item<?= isActive('comercial',$pagina_atual) ?>" data-label="Propostas">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span class="nav-text">Propostas</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('servicos')): ?>
                <a href="<?= APP_URL ?>servicos" class="nav-item<?= (strpos($pagina_atual,'servicos')===0 || strpos($pagina_atual,'comercial/servicos')===0)?' active':'' ?>" data-label="Serviços">
                    <i class="fa-solid fa-list-check"></i>
                    <span class="nav-text">Serviços</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 6. FINANCEIRO -->
        <?php if (podeAcessar('financeiro')): ?>
            <div class="nav-group-label">FINANCEIRO</div>
            <a href="<?= APP_URL ?>financeiro" class="nav-item<?= (isActive('financeiro',$pagina_atual) && !isActive('financeiro/relatorios',$pagina_atual)) ?>" data-label="Lançamentos">
                <i class="fa-solid fa-coins"></i>
                <span class="nav-text">Lançamentos</span>
            </a>
            <a href="<?= APP_URL ?>financeiro/relatorios" class="nav-item<?= isActive('financeiro/relatorios',$pagina_atual) ?>" data-label="Relatórios financeiros">
                <i class="fa-solid fa-chart-line"></i>
                <span class="nav-text">Relatórios</span>
            </a>
        <?php endif; ?>

        <!-- 7. COMUNICAÇÃO & PORTAL (Substituição profissional ao termo 'WORKSPACE') -->
        <?php if (podeAcessar('gestao_acessos_portal') || podeAcessar('portal_clientes') || podeAcessar('emails')): ?>
            <div class="nav-group-label">COMUNICAÇÃO & PORTAL</div>
            <?php if (podeAcessar('gestao_acessos_portal') || podeAcessar('portal_clientes')): ?>
                <a href="<?= APP_URL ?>gestao-acessos-portal" class="nav-item<?= (isActive('gestao-acessos-portal',$pagina_atual) || isActive('portal-clientes',$pagina_atual)) ?>" data-label="Gestão de Acessos ao Portal">
                    <i class="fa-solid fa-user-shield"></i>
                    <span class="nav-text">Acessos ao Portal</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('emails')): ?>
                <a href="<?= APP_URL ?>emails" class="nav-item<?= isActive('emails',$pagina_atual) ?>" data-label="E-mails">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span class="nav-text">E-mails</span>
                </a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>feedback" class="nav-item<?= strpos($pagina_atual,'feedback')===0?' active':'' ?>" data-label="Central de Feedback">
                <i class="fa-regular fa-comments"></i>
                <span class="nav-text">Feedback</span>
            </a>
        <?php else: ?>
            <a href="<?= APP_URL ?>feedback" class="nav-item<?= strpos($pagina_atual,'feedback')===0?' active':'' ?>" data-label="Central de Feedback">
                <i class="fa-regular fa-comments"></i>
                <span class="nav-text">Feedback</span>
            </a>
        <?php endif; ?>

        <?php if (podeAcessar('sgq')): ?>
            <!-- Auditoria ISO 9001 desacoplada do núcleo naval -->
            <div class="nav-group-label">GESTÃO DA QUALIDADE</div>
            <div class="nav-group">
                <?php $sgqAtivo = strpos($pagina_atual, 'sgq') === 0; ?>
                <a href="#" class="nav-item<?= $sgqAtivo ? ' active' : '' ?>" data-label="Auditoria ISO 9001" onclick="this.parentElement.querySelector('.nav-submenu').classList.toggle('open');this.querySelector('.nav-chevron')?.classList.toggle('rotated');return false;">
                    <i class="fa-solid fa-stamp"></i>
                    <span class="nav-text">Auditoria ISO 9001</span>
                    <i class="fa-solid fa-chevron-down nav-chevron<?= $sgqAtivo ? ' rotated' : '' ?>"></i>
                </a>
                <div class="nav-submenu<?= $sgqAtivo ? ' open' : '' ?>">
                    <a href="<?= APP_URL ?>sgq/manual" class="nav-item nav-subitem<?= isActive('sgq/manual', $pagina_atual) ?>">Manual & Política SGQ</a>
                    <a href="<?= APP_URL ?>sgq/apresentacao" target="_blank" class="nav-item nav-subitem<?= isActive('sgq/apresentacao', $pagina_atual) ?>">Apresentação SGQ (PDF)</a>
                    <a href="<?= APP_URL ?>sgq/indicadores" class="nav-item nav-subitem<?= isActive('sgq/indicadores', $pagina_atual) ?>">Indicadores da Qualidade</a>
                    <a href="<?= APP_URL ?>sgq/nao-conformidades" class="nav-item nav-subitem<?= ($pagina_atual === 'sgq/nao-conformidades' && empty($_GET['origem'])) ? ' active' : '' ?>">Não Conformidades (RNC)</a>
                    <a href="<?= APP_URL ?>sgq/nao-conformidades?origem=RECLAMACAO_CLIENTE" class="nav-item nav-subitem<?= ($pagina_atual === 'sgq/nao-conformidades' && ($_GET['origem'] ?? '') === 'RECLAMACAO_CLIENTE') ? ' active' : '' ?>">Reclamações / Ouvidoria</a>
                    <a href="<?= APP_URL ?>sgq/riscos" class="nav-item nav-subitem<?= isActive('sgq/riscos', $pagina_atual) ?>">Gestão de Riscos (ISO 6.1)</a>
                    <a href="<?= APP_URL ?>sgq/auditoria" class="nav-item nav-subitem<?= isActive('sgq/auditoria', $pagina_atual) ?>">Trilha de Auditoria Cadastral</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- 9. CONFIGURAÇÕES -->
        <?php if (podeAcessar('configuracoes') || podeAcessar('usuarios') || podeAcessar('configuracoes_normam202')): ?>
            <div class="nav-group-label">CONFIGURAÇÕES</div>
            <?php if (podeAcessar('configuracoes') || podeAcessar('usuarios')): ?>
                <a href="<?= APP_URL ?>configuracoes" class="nav-item<?= (strpos($pagina_atual,'configuracoes')===0 && strpos($pagina_atual,'configuracoes/normam202')!==0 || strpos($pagina_atual,'usuarios')===0) ? ' active' : '' ?>" data-label="Configurações">
                    <i class="fa-solid fa-sliders"></i>
                    <span class="nav-text">Configurações</span>
                </a>
            <?php endif; ?>
            <?php if (podeAcessar('configuracoes_normam202')): ?>
                <a href="<?= APP_URL ?>configuracoes/normam202" class="nav-item<?= strpos($pagina_atual, 'configuracoes/normam202') === 0 ? ' active' : '' ?>" data-label="Exigências NORMAM-202">
                    <i class="fa-solid fa-list-check"></i>
                    <span class="nav-text">NORMAM-202</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>login?action=logout" class="sidebar-logout" title="Sair do Sistema" data-label="Sair"><i class="fa-solid fa-right-from-bracket"></i><span class="nav-text">Sair</span></a></div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
(function() {
    try {
        var nav = document.getElementById('sidebarNav') || document.querySelector('.sidebar-nav');
        var aside = document.getElementById('sidebar');
        if (!nav) return;

        // 1. Restaurar posição de rolagem salva
        var savedNavScroll = sessionStorage.getItem('erp_sidebar_nav_scroll');
        if (savedNavScroll !== null) {
            nav.scrollTop = parseInt(savedNavScroll, 10);
            if (aside && sessionStorage.getItem('erp_sidebar_scroll')) {
                aside.scrollTop = parseInt(sessionStorage.getItem('erp_sidebar_scroll'), 10);
            }
        }

        // 2. Garantir que o item ativo (.active) esteja sempre visível na barra de rolagem
        var activeItem = nav.querySelector('.nav-item.active, .nav-subitem.active');
        if (activeItem) {
            var rect = activeItem.getBoundingClientRect();
            var navRect = nav.getBoundingClientRect();
            if (rect.top < navRect.top || rect.bottom > navRect.bottom) {
                activeItem.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
            }
        }

        // 3. Salvar posição ao rolar ou clicar
        var savePos = function() {
            try {
                if (nav) sessionStorage.setItem('erp_sidebar_nav_scroll', nav.scrollTop);
                if (aside) sessionStorage.setItem('erp_sidebar_scroll', aside.scrollTop);
            } catch (err) {}
        };

        var scrollTimer;
        nav.addEventListener('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(savePos, 60);
        }, { passive: true });

        if (aside) {
            aside.addEventListener('scroll', function() {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(savePos, 60);
            }, { passive: true });

            // Capturar clique em links da sidebar antes da navegação
            aside.addEventListener('click', function(e) {
                if (e.target.closest('a')) {
                    savePos();
                }
            }, { capture: true });
        }

        window.addEventListener('beforeunload', savePos);

        // 4. Mecanismo de Busca Inteligente em Tempo Real no Menu (Ctrl+K / /)
        var searchInput = document.getElementById('sidebarSearch');
        var searchClear = document.getElementById('sidebarSearchClear');
        if (searchInput) {
            function filtrarMenu() {
                var term = searchInput.value.toLowerCase().trim();
                if (searchClear) searchClear.style.display = term ? 'block' : 'none';

                var subItems = Array.from(document.querySelectorAll('#sidebarNav .nav-subitem'));
                var navGroups = Array.from(document.querySelectorAll('#sidebarNav .nav-group'));
                var navDirectItems = Array.from(document.querySelectorAll('#sidebarNav > .nav-item'));
                var allLabels = Array.from(document.querySelectorAll('#sidebarNav .nav-group-label'));

                if (!term) {
                    // Restaurar exibição padrão
                    document.querySelectorAll('#sidebarNav .nav-item, #sidebarNav .nav-subitem, #sidebarNav .nav-group, #sidebarNav .nav-group-label').forEach(function(el) {
                        el.style.display = '';
                    });
                    return;
                }

                // A. Filtrar subitens
                subItems.forEach(function(sub) {
                    var text = (sub.textContent || '').toLowerCase();
                    var matches = text.indexOf(term) !== -1;
                    sub.style.display = matches ? 'flex' : 'none';
                });

                // B. Filtrar grupos colapsáveis
                navGroups.forEach(function(grp) {
                    var mainItem = grp.querySelector('.nav-item');
                    var mainText = (mainItem ? (mainItem.getAttribute('data-label') || mainItem.textContent) : '').toLowerCase();
                    var hasSubMatch = Array.from(grp.querySelectorAll('.nav-subitem')).some(function(s) {
                        return s.style.display !== 'none';
                    });

                    if (mainText.indexOf(term) !== -1 || hasSubMatch) {
                        grp.style.display = 'block';
                        if (mainItem) mainItem.style.display = 'flex';
                        var submenu = grp.querySelector('.nav-submenu');
                        if (submenu) submenu.classList.add('open');
                    } else {
                        grp.style.display = 'none';
                    }
                });

                // C. Filtrar itens diretos
                navDirectItems.forEach(function(item) {
                    var label = (item.getAttribute('data-label') || item.textContent || '').toLowerCase();
                    item.style.display = label.indexOf(term) !== -1 ? 'flex' : 'none';
                });

                // D. Ocultar cabeçalhos de grupos que não possuem nenhum item correspondente
                var navElements = Array.from(nav.children);
                var activeLabel = null;
                var groupHasMatches = false;

                navElements.forEach(function(el) {
                    if (el.classList.contains('nav-group-label')) {
                        if (activeLabel) {
                            activeLabel.style.display = groupHasMatches ? '' : 'none';
                        }
                        activeLabel = el;
                        groupHasMatches = false;
                    } else if (el.classList.contains('nav-item') || el.classList.contains('nav-group')) {
                        if (el.style.display !== 'none') {
                            groupHasMatches = true;
                        }
                    }
                });
                if (activeLabel) {
                    activeLabel.style.display = groupHasMatches ? '' : 'none';
                }
            }

            searchInput.addEventListener('input', filtrarMenu);

            if (searchClear) {
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    filtrarMenu();
                    searchInput.focus();
                });
            }

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
                if (e.key === 'Escape' && document.activeElement === searchInput) {
                    searchInput.value = '';
                    filtrarMenu();
                    searchInput.blur();
                }
            });
        }
    } catch (e) {}
})();
</script>
