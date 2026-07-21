<?php
/** Menu lateral orientado pelas permissões individuais do usuário. */
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
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo-area">
        <a href="<?= APP_URL ?>dashboard" class="logo-title" aria-label="Amazon Certificadora - Início">
            <img src="<?= APP_URL ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora" class="sidebar-brand-logo">
            <img src="<?= APP_URL ?>img/logo-amazon-icon.svg" alt="" class="sidebar-brand-icon" aria-hidden="true">
        </a>
        <button class="btn-sidebar-toggle" id="sidebar-toggle"><i class="fa-solid fa-chevron-left"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group-label">OPERAÇÃO</div>
        <?php if (podeAcessar('dashboard')): ?><a href="<?= APP_URL ?>dashboard" class="nav-item<?= isActive('dashboard',$pagina_atual) ?>" data-label="Dashboard"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Dashboard</span></a><?php endif; ?>
        <?php if (podeAcessar('vistorias')): ?><a href="<?= APP_URL ?>vistorias" class="nav-item<?= isActive('vistorias',$pagina_atual) ?>" data-label="Vistorias"><i class="fa-solid fa-clipboard-check"></i><span class="nav-text">Vistorias</span></a><?php endif; ?>
        <?php if (podeAcessar('analise_planos')): ?><a href="<?= APP_URL ?>analises-planos" class="nav-item<?= strpos($pagina_atual,'analises-planos')===0?' active':'' ?>" data-label="Análise de Planos"><i class="fa-solid fa-drafting-compass"></i><span class="nav-text">Análise de Planos</span></a><?php endif; ?>
        <?php if (podeAcessar('relatorios_aprovacao')): ?><a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="nav-item<?= isActive('documentacao/aprovacao_relatorios',$pagina_atual) ?>" data-label="Relatórios"><i class="fa-solid fa-file-circle-check"></i><span class="nav-text">Relatórios</span></a><?php endif; ?>
        <?php if (podeAcessar('agendamentos')): ?><a href="<?= APP_URL ?>agendamentos" class="nav-item<?= isActive('agendamentos',$pagina_atual) ?>" data-label="Agendamentos"><i class="fa-solid fa-calendar-days"></i><span class="nav-text">Agendamentos</span></a><?php endif; ?>
        <?php if (podeAcessar('certificados')): ?><a href="<?= APP_URL ?>certificados" class="nav-item<?= isActive('certificados',$pagina_atual) ?>" data-label="Certificados"><i class="fa-solid fa-award"></i><span class="nav-text">Certificados</span></a><?php endif; ?>

        <?php if (podeAcessar('embarcacoes') || podeAcessar('armadores') || podeAcessar('proprietarios') || podeAcessar('despachantes')): ?><div class="nav-group-label">CADASTROS</div><?php endif; ?>
        <?php if (podeAcessar('embarcacoes')): ?><a href="<?= APP_URL ?>embarcacoes" class="nav-item<?= isActive('embarcacoes',$pagina_atual) ?>" data-label="Embarcações"><i class="fa-solid fa-ship"></i><span class="nav-text">Embarcações</span></a><?php endif; ?>
        <?php if (podeAcessar('armadores')): ?><a href="<?= APP_URL ?>armadores" class="nav-item<?= isActive('armadores',$pagina_atual) ?>" data-label="Armadores"><i class="fa-solid fa-building-user"></i><span class="nav-text">Armadores</span></a><?php endif; ?>
        <?php if (podeAcessar('proprietarios')): ?><a href="<?= APP_URL ?>proprietarios" class="nav-item<?= isActive('proprietarios',$pagina_atual) ?>" data-label="Proprietários"><i class="fa-solid fa-id-card"></i><span class="nav-text">Proprietários</span></a><?php endif; ?>
        <?php if (podeAcessar('despachantes')): ?><a href="<?= APP_URL ?>despachantes" class="nav-item<?= isActive('despachantes',$pagina_atual) ?>" data-label="Despachantes"><i class="fa-solid fa-briefcase"></i><span class="nav-text">Despachantes</span></a><?php endif; ?>

        <?php if (podeAcessar('comercial') || podeAcessar('servicos')): ?><div class="nav-group-label">COMERCIAL</div><?php endif; ?>
        <?php if (podeAcessar('comercial')): ?><a href="<?= APP_URL ?>comercial" class="nav-item<?= isActive('comercial',$pagina_atual) ?>" data-label="Propostas"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Propostas</span></a><?php endif; ?>
        <?php if (podeAcessar('servicos')): ?><a href="<?= APP_URL ?>comercial/servicos" class="nav-item<?= isActive('comercial/servicos',$pagina_atual) ?>" data-label="Serviços"><i class="fa-solid fa-list-check"></i><span class="nav-text">Serviços</span></a><?php endif; ?>

        <?php if (podeAcessar('financeiro')): ?><div class="nav-group-label">FINANCEIRO</div><a href="<?= APP_URL ?>financeiro" class="nav-item<?= isActive('financeiro',$pagina_atual) ?>" data-label="Lançamentos"><i class="fa-solid fa-coins"></i><span class="nav-text">Lançamentos</span></a><a href="<?= APP_URL ?>financeiro/relatorios" class="nav-item<?= isActive('financeiro/relatorios',$pagina_atual) ?>" data-label="Relatórios financeiros"><i class="fa-solid fa-chart-line"></i><span class="nav-text">Relatórios</span></a><?php endif; ?>

        <?php if (podeAcessar('documentacao')): ?><div class="nav-group-label">WORKSPACE</div><div class="nav-group"><a href="#" class="nav-item<?= strpos($pagina_atual,'documentacao')===0?' active':'' ?>" data-label="Documentação" onclick="this.parentElement.querySelector('.nav-submenu').classList.toggle('open');return false;"><i class="fa-solid fa-folder-open"></i><span class="nav-text">Documentação</span><i class="fa-solid fa-chevron-down nav-chevron"></i></a><div class="nav-submenu<?= strpos($pagina_atual,'documentacao')===0?' open':'' ?>"><a href="<?= APP_URL ?>documentacao/certificados" class="nav-item nav-subitem<?= isActive('documentacao/certificados',$pagina_atual) ?>">Certificados (CSN)</a><a href="<?= APP_URL ?>documentacao/cnbl" class="nav-item nav-subitem<?= isActive('documentacao/cnbl',$pagina_atual) ?>">CNBL</a><a href="<?= APP_URL ?>documentacao/cnarq" class="nav-item nav-subitem<?= isActive('documentacao/cnarq',$pagina_atual) ?>">CNARQ</a><a href="<?= APP_URL ?>documentacao/lp" class="nav-item nav-subitem<?= isActive('documentacao/lp',$pagina_atual) ?>">LP</a><a href="<?= APP_URL ?>documentacao/lc" class="nav-item nav-subitem<?= isActive('documentacao/lc',$pagina_atual) ?>">LC</a><a href="<?= APP_URL ?>documentacao/cht" class="nav-item nav-subitem<?= isActive('documentacao/cht',$pagina_atual) ?>">CHT</a></div></div><?php endif; ?>
        <?php if (podeAcessar('emails')): ?><a href="<?= APP_URL ?>emails" class="nav-item<?= isActive('emails',$pagina_atual) ?>" data-label="E-mails"><i class="fa-solid fa-paper-plane"></i><span class="nav-text">E-mails</span></a><?php endif; ?>
        <?php if (podeAcessar('portal_clientes')): ?><a href="<?= APP_URL ?>portal-clientes" class="nav-item<?= isActive('portal-clientes',$pagina_atual) ?>" data-label="Portal do Cliente"><i class="fa-solid fa-user-shield"></i><span class="nav-text">Portal do Cliente</span></a><?php endif; ?>
        <a href="<?= APP_URL ?>feedback" class="nav-item<?= strpos($pagina_atual,'feedback')===0?' active':'' ?>" data-label="Central de Feedback"><i class="fa-regular fa-comments"></i><span class="nav-text">Feedback</span></a>
        <?php if (podeAcessar('usuarios')): ?><a href="<?= APP_URL ?>usuarios" class="nav-item<?= isActive('usuarios',$pagina_atual) ?>" data-label="Usuários"><i class="fa-solid fa-users-gear"></i><span class="nav-text">Usuários</span></a><?php endif; ?>
        <?php if (podeAcessar('configuracoes')): ?><a href="<?= APP_URL ?>configuracoes" class="nav-item<?= isActive('configuracoes',$pagina_atual) ?>" data-label="Configurações"><i class="fa-solid fa-sliders"></i><span class="nav-text">Configurações</span></a><?php endif; ?>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>login?action=logout" class="sidebar-logout" title="Sair do Sistema" data-label="Sair"><i class="fa-solid fa-right-from-bracket"></i><span class="nav-text">Sair</span></a></div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
