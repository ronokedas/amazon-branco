<?php
/**
 * ERP SISTEMA DE GESTAO
 * Arquivo: index.php - Roteador principal
 */

// Configuracao do sistema
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Impedir que duplo clique, recarregamento ou repeticao de rede execute
// novamente o mesmo formulario de gravacao.
if (!aceitarEnvioUnico()) {
    responderEnvioDuplicado();
}

// Capturar a URI solicitada
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remover a barra final
$path = rtrim($path, '/');

// Remover o prefixo da pasta do projeto (ex: /sistema/dashboard -> /dashboard)
$app_folder = '/' . basename(__DIR__);
if (strpos($path, $app_folder) === 0) {
    $path = substr($path, strlen($app_folder));
}
$path = ltrim($path, '/');

// Evita erro de console nos navegadores e reutiliza o ícone da aplicação de campo.
if ($path === 'favicon.ico') {
    header('Location: ' . APP_URL . 'campo/icon.svg', true, 302);
    exit;
}

// A antiga pagina Geral foi consolidada na configuracao financeira.
if ($path === 'configuracoes/geral') {
    header('Location: ' . APP_URL . 'configuracoes/financeiro', true, 302);
    exit;
}

// Mapeamento de rotas para modulos
$rotas = [
    ''              => 'modules/login/index.php',
    'login'         => 'modules/login/index.php',
    'portal/login'  => 'modules/portal/login.php',
    'portal/logout' => 'modules/portal/logout.php',
    'portal/recuperar-senha' => 'modules/portal/recuperar_senha.php',
    'portal/redefinir-senha' => 'modules/portal/redefinir_senha.php',
    'portal/trocar-senha' => 'modules/portal/trocar_senha.php',
    'portal' => 'modules/portal/index.php',
    'portal/documentos' => 'modules/portal/documentos.php',
    'portal/embarcacoes' => 'modules/portal/embarcacoes.php',
    'portal/documentos/pdf' => 'modules/portal/pdf.php',
    'portal/analises-planos' => 'modules/portal/analises_planos.php',
    'portal/analises-planos/actions' => 'modules/portal/analises_planos_actions.php',
    'dashboard'     => 'modules/dashboard/index.php',
    'armadores'          => 'modules/armadores/index.php',
    'armadores/form'     => 'modules/armadores/form.php',
    'armadores/actions'  => 'modules/armadores/actions.php',
    'proprietarios'          => 'modules/proprietarios/index.php',
    'proprietarios/form'     => 'modules/proprietarios/form.php',
    'proprietarios/actions'  => 'modules/proprietarios/actions.php',
    'despachantes'          => 'modules/despachantes/index.php',
    'despachantes/form'     => 'modules/despachantes/form.php',
    'despachantes/actions'  => 'modules/despachantes/actions.php',
    'embarcacoes'          => 'modules/embarcacoes/index.php',
    'embarcacoes/form'     => 'modules/embarcacoes/form.php',
    'embarcacoes/actions'  => 'modules/embarcacoes/actions.php',
    'embarcacoes/foto'     => 'modules/embarcacoes/foto.php',
    'vistorias'          => 'modules/vistorias/index.php',
    'vistorias/nova'     => 'modules/vistorias/nova.php',
    'vistorias/detalhe'  => 'modules/vistorias/detalhe.php',
    'vistorias/actions'  => 'modules/vistorias/actions.php',
    'vistorias/relatorio' => 'modules/vistorias/relatorio.php',
    'vistorias/relatorio_pdf' => 'modules/vistorias/relatorio_pdf.php',
    'vistorias/relatorio_pdf.php' => 'modules/vistorias/relatorio_pdf.php',
    'analises-planos' => 'modules/analises_planos/index.php',
    'analises-planos/form' => 'modules/analises_planos/form.php',
    'analises-planos/actions' => 'modules/analises_planos/actions.php',
    'analises-planos/arquivo' => 'modules/analises_planos/arquivo.php',
    'analises-planos/parecer-pdf' => 'modules/analises_planos/parecer_pdf.php',
    'protocolos' => 'modules/protocolos/index.php',
    'protocolos/form' => 'modules/protocolos/form.php',
    'protocolos/actions' => 'modules/protocolos/actions.php',
    'protocolos/pdf' => 'modules/protocolos/pdf.php',
    'protocolos/pdf-dossie' => 'modules/protocolos/pdf_dossie.php',
    'protocolos/arquivo' => 'modules/protocolos/arquivo.php',
    'protocolos/configuracoes' => 'modules/protocolos/configuracoes.php',
    'financeiro'          => 'modules/financeiro/index.php',
    'financeiro/form'     => 'modules/financeiro/form.php',
    'financeiro/actions'  => 'modules/financeiro/actions.php',
    'financeiro/relatorios' => 'modules/financeiro/relatorios.php',
    'financeiro/relatorios/exportar' => 'modules/financeiro/relatorios_exportar.php',
    'usuarios'      => 'modules/usuarios/index.php',
    'usuarios/form' => 'modules/usuarios/form.php',
    'usuarios/actions' => 'modules/usuarios/actions.php',
    'documentacao'                      => 'modules/documentacao/index.php',
    'documentacao/certificados'         => 'modules/documentacao/certificados/index.php',
    'documentacao/certificados/form'    => 'modules/documentacao/certificados/form.php',
    'documentacao/certificados/actions' => 'modules/documentacao/certificados/actions.php',
    'documentacao/certificados/pdf'     => 'modules/documentacao/certificados/pdf.php',
    'documentacao/cnbl'                 => 'modules/documentacao/cnbl/index.php',
    'documentacao/cnbl/form'            => 'modules/documentacao/cnbl/form.php',
    'documentacao/cnbl/actions'         => 'modules/documentacao/cnbl/actions.php',
    'documentacao/cnbl/pdf'             => 'modules/documentacao/cnbl/pdf.php',
    'documentacao/cnarq'                => 'modules/documentacao/cnarq/index.php',
    'documentacao/cnarq/form'           => 'modules/documentacao/cnarq/form.php',
    'documentacao/cnarq/actions'        => 'modules/documentacao/cnarq/actions.php',
    'documentacao/cnarq/pdf'            => 'modules/documentacao/cnarq/pdf.php',
    'documentacao/lp'                   => 'modules/documentacao/lp/index.php',
    'documentacao/lp/form'              => 'modules/documentacao/lp/form.php',
    'documentacao/lp/actions'           => 'modules/documentacao/lp/actions.php',
    'documentacao/lp/pdf'               => 'modules/documentacao/lp/pdf.php',
    'documentacao/lc'                   => 'modules/documentacao/lc/index.php',
    'documentacao/lc/form'              => 'modules/documentacao/lc/form.php',
    'documentacao/lc/actions'           => 'modules/documentacao/lc/actions.php',
    'documentacao/lc/pdf'               => 'modules/documentacao/lc/pdf.php',
    'documentacao/cht'                  => 'modules/documentacao/cht/index.php',
    'documentacao/cht/form'             => 'modules/documentacao/cht/form.php',
    'documentacao/cht/actions'          => 'modules/documentacao/cht/actions.php',
    'documentacao/cht/pdf'              => 'modules/documentacao/cht/pdf.php',
    'documentacao/aprovacao_relatorios' => 'modules/documentacao/aprovacao_relatorios.php',
    'documentacao/novo_certificado'     => 'modules/documentacao/novo_certificado.php',
    'documentacao/baixa_exigencias'     => 'modules/documentacao/baixa_exigencias.php',
    'certificados'                  => 'modules/certificados/index.php',
    'certificados/wizard'           => 'modules/certificados/wizard.php',
    'certificados/wizard_step2'     => 'modules/certificados/wizard_step2.php',
    'comercial'                     => 'modules/comercial/index.php',
    'comercial/servicos'            => 'modules/comercial/servicos/index.php',
    'comercial/servicos/form'       => 'modules/comercial/servicos/form.php',
    'comercial/servicos/actions'    => 'modules/comercial/servicos/actions.php',
    'comercial/nova'                => 'modules/comercial/nova.php',
    'comercial/pdf'                 => 'modules/comercial/pdf.php',
    'comercial/propostas'           => 'modules/comercial/propostas/index.php',
    'comercial/propostas/actions'   => 'modules/comercial/propostas/actions.php',
    'relatorios'                    => 'modules/relatorios/index.php',
    'agendamentos'          => 'modules/agendamentos/index.php',
    'agendamentos/form'     => 'modules/agendamentos/form.php',
    'agendamentos/actions'  => 'modules/agendamentos/actions.php',
    'agendamentos/os'       => 'modules/agendamentos/os.php',
    'emails'                => 'modules/emails/index.php',
    'portal-clientes'       => 'modules/portal_clientes/index.php',
    'portal-clientes/actions' => 'modules/portal_clientes/actions.php',
    'configuracoes'             => 'modules/configuracoes/index.php',
    'configuracoes/basicas'     => 'modules/configuracoes/basicas.php',
    'configuracoes/financeiro'  => 'modules/configuracoes/financeiro.php',
    'configuracoes/backup'      => 'modules/configuracoes/backup.php',
    'configuracoes/exportacoes' => 'modules/configuracoes/exportacoes.php',
    'configuracoes/exportacoes_actions' => 'modules/configuracoes/exportacoes_actions.php',
    'configuracoes/exportacoes_download' => 'modules/configuracoes/exportacoes_download.php',
    'configuracoes/actions'     => 'modules/configuracoes/actions.php',
    'configuracoes/backup_actions' => 'modules/configuracoes/backup_actions.php',
    'responsaveis_assinatura'         => 'modules/responsaveis_assinatura/index.php',
    'responsaveis_assinatura/form'    => 'modules/responsaveis_assinatura/form.php',
    'responsaveis_assinatura/actions' => 'modules/responsaveis_assinatura/actions.php',
    'responsaveis_assinatura/assinatura' => 'modules/responsaveis_assinatura/assinatura.php',
    'minhas-assinaturas'                => 'modules/minhas_assinaturas/index.php',
    'minhas-assinaturas/actions'        => 'modules/minhas_assinaturas/actions.php',
    'documentos/aprovar'             => 'modules/documentos/aprovar.php',
    'documentos/cancelar'            => 'modules/documentos/cancelar.php',
    'busca-global'              => 'ajax/busca_global.php',
    'ajax/busca_cidades.php'    => 'ajax/busca_cidades.php',
    'perfil'                    => 'modules/perfil/index.php',
    'feedback'                  => 'modules/feedback/index.php',
    'feedback/conversa'         => 'modules/feedback/conversa.php',
    'feedback/actions'          => 'modules/feedback/actions.php',
    'feedback/contador'         => 'modules/feedback/contador.php',
    'feedback/arquivo'          => 'modules/feedback/arquivo.php',
    'feedback/configuracoes'    => 'modules/feedback/configuracoes.php',
    'notificacoes'              => 'modules/notificacoes/index.php',
    'notificacoes/actions'      => 'modules/notificacoes/actions.php',
];

// Se nao esta logado, sempre ir para login (exceto proprio login)
if (!isset($_SESSION['usuario_logado']) && $path !== '' && $path !== 'login') {
    // Verificar se é rota pública de assinatura
    $is_rota_publica = (strpos($path, 'assinar/') === 0 || strpos($path, 'assinatura-certificado/') === 0 || strpos($path, 'validar/') === 0 || strpos($path, 'validar-assinatura/') === 0 || strpos($path, 'protocolo-aceite/') === 0);
    if ($path === 'portal' || strpos($path, 'portal/') === 0) {
        $is_rota_publica = true;
    }
    if (strpos($path, 'api/campo/v1') === 0) {
        // A API responde 401 em JSON; não deve ser convertida em HTML de login.
        $is_rota_publica = true;
    }
    
    // Somente PDFs de documentos com token proprio podem ser publicos.
    // Relatorios tecnicos de vistoria exigem sessao e permissao.
    if (!$is_rota_publica && strpos($path, '/pdf') !== false && !empty($_GET['token'])) {
        $is_rota_publica = true;
    }
    
    if (!$is_rota_publica) {
        if ($path === 'minhas-assinaturas') {
            $_SESSION['login_return_to'] = 'minhas-assinaturas' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        }
        $path = '';
    }
}

// Se esta logado e acessa raiz ou login, redirecionar para dashboard
// Exceto quando eh logout
if (isset($_SESSION['usuario_logado']) && ($path === '' || $path === 'login')) {
    if (!isset($_GET['action']) || $_GET['action'] !== 'logout') {
        header('Location: ' . APP_URL . 'dashboard');
        exit;
    }
}

// Verificar se a rota existe
if (strpos($path, 'api/campo/v1') === 0) {
    require_once __DIR__ . '/modules/campo/api.php';
} elseif (isset($rotas[$path])) {
    $permissoes_rota = [
        'dashboard' => 'dashboard',
        'armadores' => 'armadores', 'armadores/form' => 'armadores', 'armadores/actions' => 'armadores',
        'proprietarios' => 'proprietarios', 'proprietarios/form' => 'proprietarios', 'proprietarios/actions' => 'proprietarios',
        'despachantes' => 'despachantes', 'despachantes/form' => 'despachantes', 'despachantes/actions' => 'despachantes',
        'embarcacoes' => 'embarcacoes', 'embarcacoes/form' => 'embarcacoes', 'embarcacoes/actions' => 'embarcacoes', 'embarcacoes/foto' => 'embarcacoes',
        'vistorias' => 'vistorias', 'vistorias/nova' => 'vistorias', 'vistorias/detalhe' => 'vistorias', 'vistorias/actions' => 'vistorias', 'vistorias/relatorio' => 'vistorias', 'vistorias/relatorio_pdf' => 'vistorias', 'vistorias/relatorio_pdf.php' => 'vistorias',
        'analises-planos' => 'analise_planos', 'analises-planos/form' => 'analise_planos', 'analises-planos/actions' => 'analise_planos', 'analises-planos/arquivo' => 'analise_planos', 'analises-planos/parecer-pdf' => 'analise_planos',
        'protocolos' => 'protocolos_documentais', 'protocolos/form' => 'protocolos_documentais', 'protocolos/actions' => 'protocolos_documentais', 'protocolos/pdf' => 'protocolos_documentais', 'protocolos/pdf-dossie' => 'protocolos_documentais', 'protocolos/arquivo' => 'protocolos_documentais',
        'protocolos/configuracoes' => 'protocolos_documentais',
        'financeiro' => 'financeiro', 'financeiro/form' => 'financeiro', 'financeiro/actions' => 'financeiro', 'financeiro/relatorios' => 'financeiro', 'financeiro/relatorios/exportar' => 'financeiro',
        'usuarios' => 'usuarios', 'usuarios/form' => 'usuarios', 'usuarios/actions' => 'usuarios',
        'agendamentos' => 'agendamentos', 'agendamentos/form' => 'agendamentos', 'agendamentos/actions' => 'agendamentos', 'agendamentos/os' => 'agendamentos',
        'comercial' => 'comercial', 'comercial/nova' => 'comercial', 'comercial/pdf' => 'comercial', 'comercial/propostas' => 'comercial', 'comercial/propostas/actions' => 'comercial',
        'comercial/servicos' => 'servicos', 'comercial/servicos/form' => 'servicos', 'comercial/servicos/actions' => 'servicos',
        'relatorios' => 'relatorios', 'emails' => 'emails', 'portal-clientes' => 'portal_clientes', 'portal-clientes/actions' => 'portal_clientes',
        'configuracoes' => 'configuracoes', 'configuracoes/basicas' => 'configuracoes', 'configuracoes/financeiro' => 'configuracoes', 'configuracoes/backup' => 'configuracoes', 'configuracoes/exportacoes' => 'configuracoes', 'configuracoes/exportacoes_actions' => 'configuracoes', 'configuracoes/exportacoes_download' => 'configuracoes', 'configuracoes/actions' => 'configuracoes',
        'responsaveis_assinatura' => 'responsaveis_assinatura', 'responsaveis_assinatura/form' => 'responsaveis_assinatura', 'responsaveis_assinatura/actions' => 'responsaveis_assinatura', 'responsaveis_assinatura/assinatura' => 'responsaveis_assinatura',
        'documentos/aprovar' => 'documentacao', 'documentos/cancelar' => 'documentacao',
        'documentacao' => 'documentacao', 'documentacao/aprovacao_relatorios' => 'relatorios_aprovacao',
        'certificados' => 'certificados',
    ];
    $permissao_rota = $permissoes_rota[$path] ?? null;
    if (strpos($path, 'documentacao/') === 0 && $path !== 'documentacao/aprovacao_relatorios') $permissao_rota = 'documentacao';
    if (strpos($path, 'certificados/') === 0) $permissao_rota = 'certificados';
    if ($permissao_rota !== null && !podeAcessar($permissao_rota)) {
        setMensagem('error', 'Acesso negado para este módulo.');
        redirecionar(APP_URL . 'dashboard');
    }
    require_once __DIR__ . '/' . $rotas[$path];
} elseif (strpos($path, 'protocolo-aceite/') === 0) {
    $_GET['token'] = substr($path, strlen('protocolo-aceite/'));
    require_once __DIR__ . '/modules/protocolos/aceite.php';
    exit;
} elseif (strpos($path, 'validar/') === 0) {
    $_GET['token'] = substr($path, 8);
    require_once __DIR__ . '/modules/documentos/validar.php';
    exit;
} elseif (strpos($path, 'validar-assinatura/') === 0) {
    $_GET['token'] = substr($path, 19);
    require_once __DIR__ . '/modules/documentos/validar_assinatura.php';
    exit;
} elseif (strpos($path, 'assinatura-certificado/') === 0) {
    $partesAssinatura = explode('/', substr($path, 23));
    $_GET['token'] = $partesAssinatura[0] ?? '';
    $acaoAssinatura = $partesAssinatura[1] ?? '';
    if ($acaoAssinatura === 'pdf') require_once __DIR__ . '/modules/assinaturas_publicas/preview.php';
    elseif ($acaoAssinatura === 'confirmar') require_once __DIR__ . '/modules/assinaturas_publicas/confirmar.php';
    elseif ($acaoAssinatura === '') require_once __DIR__ . '/modules/assinaturas_publicas/certificado.php';
    else { http_response_code(404); echo 'Página não encontrada.'; }
    exit;
} elseif (strpos($path, 'assinar/') === 0) {
    // Rota pública de assinatura: assinar/{token_assinatura}
    $_GET['token'] = substr($path, 8); // Remover "assinar/"
    // Verificar se o token pertence a propostas
    $stmt_check_prop = $pdo->prepare("SELECT COUNT(*) as total FROM propostas WHERE token_assinatura = :token");
    $stmt_check_prop->execute([':token' => $_GET['token']]);
    $check_prop = $stmt_check_prop->fetch(PDO::FETCH_ASSOC);
    if ($check_prop && $check_prop['total'] > 0) {
        require_once __DIR__ . '/modules/comercial/propostas/assinar.php';
        exit;
    }

    // O fluxo publico de assinatura permanece exclusivo das propostas.
    // Certificados tecnicos agora sao aprovados por administradores autenticados.
    http_response_code(410);
    require_once __DIR__ . '/modules/documentos/assinatura_publica_desativada.php';
    exit;

    /* Fluxo legado mantido abaixo apenas como referencia historica.
    // Verificar se o token pertence ao CHT, LC, LP, CNBL, CNARQ ou CSN
    $stmt_check_cht = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cht WHERE token_assinatura = :token AND ativo = 1");
    $stmt_check_cht->execute([':token' => $_GET['token']]);
    $check_cht = $stmt_check_cht->fetch(PDO::FETCH_ASSOC);
    if ($check_cht && $check_cht['total'] > 0) {
        require_once __DIR__ . '/modules/documentacao/cht/assinar.php';
    } else {
        $stmt_check_lc = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lc WHERE token_assinatura = :token AND ativo = 1");
        $stmt_check_lc->execute([':token' => $_GET['token']]);
        $check_lc = $stmt_check_lc->fetch(PDO::FETCH_ASSOC);
        if ($check_lc && $check_lc['total'] > 0) {
            require_once __DIR__ . '/modules/documentacao/lc/assinar.php';
        } else {
            $stmt_check_lp = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lp WHERE token_assinatura = :token AND ativo = 1");
            $stmt_check_lp->execute([':token' => $_GET['token']]);
            $check_lp = $stmt_check_lp->fetch(PDO::FETCH_ASSOC);
            if ($check_lp && $check_lp['total'] > 0) {
                require_once __DIR__ . '/modules/documentacao/lp/assinar.php';
            } else {
                $stmt_check_cnbl = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnbl WHERE token_assinatura = :token AND ativo = 1");
                $stmt_check_cnbl->execute([':token' => $_GET['token']]);
                $check_cnbl = $stmt_check_cnbl->fetch(PDO::FETCH_ASSOC);
                if ($check_cnbl && $check_cnbl['total'] > 0) {
                    require_once __DIR__ . '/modules/documentacao/cnbl/assinar.php';
                } else {
                    $stmt_check_cnarq = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnarq WHERE token_assinatura = :token AND ativo = 1");
                    $stmt_check_cnarq->execute([':token' => $_GET['token']]);
                    $check_cnarq = $stmt_check_cnarq->fetch(PDO::FETCH_ASSOC);
                    if ($check_cnarq && $check_cnarq['total'] > 0) {
                        require_once __DIR__ . '/modules/documentacao/cnarq/assinar.php';
                    } else {
                        require_once __DIR__ . '/modules/documentacao/certificados/assinar.php';
                    }
                }
            }
        }
    }
    */
} else {
    // 404 - Pagina nao encontrada
    http_response_code(404);
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="error-page">
        <div class="error-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>404</h1>
            <h2>Pagina nao encontrada</h2>
            <p>A pagina que voce procura nao existe ou foi removida.</p>
            <a href="<?php echo APP_URL; ?>login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Ir para Login
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}
