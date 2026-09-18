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

// Redirecionamentos transparentes 301 (Etapa 2 - Unificacao dos Cadastros Mestres Navais)
$qsParams = !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : '';
if ($path === 'armadores') {
    header('Location: ' . APP_URL . 'clientes?perfil=armador' . $qsParams, true, 301);
    exit;
}
if ($path === 'armadores/form') {
    header('Location: ' . APP_URL . 'clientes/form?perfil=armador' . $qsParams, true, 301);
    exit;
}
if ($path === 'proprietarios') {
    header('Location: ' . APP_URL . 'clientes?perfil=proprietario' . $qsParams, true, 301);
    exit;
}
if ($path === 'proprietarios/form') {
    header('Location: ' . APP_URL . 'clientes/form?perfil=proprietario' . $qsParams, true, 301);
    exit;
}
if ($path === 'despachantes') {
    header('Location: ' . APP_URL . 'clientes?perfil=despachante' . $qsParams, true, 301);
    exit;
}
if ($path === 'despachantes/form') {
    header('Location: ' . APP_URL . 'clientes/form?perfil=despachante' . $qsParams, true, 301);
    exit;
}

// Redirecionamentos transparentes 301 (Etapa 3 - Desacoplamento do Catalogo de Servicos)
if ($path === 'comercial/servicos') {
    header('Location: ' . APP_URL . 'servicos' . $qsParams, true, 301);
    exit;
}
if ($path === 'comercial/servicos/form') {
    header('Location: ' . APP_URL . 'servicos/form' . $qsParams, true, 301);
    exit;
}

// Mapeamento de rotas para modulos
$rotas = [
    ''              => 'modules/login/index.php',
    'login'         => 'modules/login/index.php',
    'logout'        => 'modules/login/logout.php',
    'portal/login'  => 'modules/portal/login.php',
    'portal/logout' => 'modules/portal/logout.php',
    'portal/recuperar-senha' => 'modules/portal/recuperar_senha.php',
    'portal/redefinir-senha' => 'modules/portal/redefinir_senha.php',
    'portal/trocar-senha' => 'modules/portal/trocar_senha.php',
    'portal' => 'modules/portal/index.php',
    'portal/documentos' => 'modules/portal/documentos.php',
    'portal/embarcacoes' => 'modules/portal/embarcacoes.php',
    'portal/protocolos' => 'modules/portal/protocolos.php',
    'portal/vistorias' => 'modules/portal/vistorias.php',
    'portal/propostas' => 'modules/portal/propostas.php',
    'portal/documentos/pdf' => 'modules/portal/pdf.php',
    'portal/analises-planos' => 'modules/portal/analises_planos.php',
    'portal/analises-planos/actions' => 'modules/portal/analises_planos_actions.php',
    'portal/ouvidoria' => 'modules/portal/ouvidoria.php',
    'portal/ouvidoria/actions' => 'modules/portal/ouvidoria_actions.php',
    'portal/satisfacao/actions' => 'modules/portal/satisfacao_actions.php',
    'sgq' => 'modules/sgq/manual.php',
    'sgq/manual' => 'modules/sgq/manual.php',
    'sgq/apresentacao' => 'modules/sgq/apresentacao.php',
    'sgq/indicadores' => 'modules/sgq/indicadores.php',
    'sgq/nao-conformidades' => 'modules/sgq/nao_conformidades.php',
    'sgq/nao-conformidades/actions' => 'modules/sgq/nao_conformidades_actions.php',
    'sgq/auditoria' => 'modules/sgq/auditoria.php',
    'sgq/riscos' => 'modules/sgq/riscos.php',
    'sgq/riscos/actions' => 'modules/sgq/riscos_actions.php',
    'dashboard'     => 'modules/dashboard/index.php',
    'clientes'          => 'modules/clientes/index.php',
    'clientes/form'     => 'modules/clientes/form.php',
    'clientes/actions'  => 'modules/clientes/actions.php',
    'armadores/actions' => 'modules/clientes/actions.php',
    'proprietarios/actions' => 'modules/clientes/actions.php',
    'despachantes/actions' => 'modules/clientes/actions.php',
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
    'analises-planos/referencias' => 'modules/analises_planos/referencias.php',
    'analises-planos/referencias-actions' => 'modules/analises_planos/referencias_actions.php',
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
    'certificados/vencimentos'      => 'modules/certificados/vencimentos.php',
    'certificados/wizard'           => 'modules/certificados/wizard.php',
    'certificados/wizard_step2'     => 'modules/certificados/wizard_step2.php',
    'servicos'                      => 'modules/servicos/index.php',
    'servicos/form'                 => 'modules/servicos/form.php',
    'servicos/actions'              => 'modules/servicos/actions.php',
    'comercial'                     => 'modules/comercial/index.php',
    'comercial/servicos'            => 'modules/servicos/index.php',
    'comercial/servicos/form'       => 'modules/servicos/form.php',
    'comercial/servicos/actions'    => 'modules/servicos/actions.php',
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
    'gestao-acessos-portal' => 'modules/gestao_acessos_portal/index.php',
    'gestao-acessos-portal/actions' => 'modules/gestao_acessos_portal/actions.php',
    'portal-clientes'       => 'modules/gestao_acessos_portal/index.php',
    'portal-clientes/actions' => 'modules/gestao_acessos_portal/actions.php',
    'configuracoes'             => 'modules/configuracoes/index.php',
    'configuracoes/basicas'     => 'modules/configuracoes/basicas.php',
    'configuracoes/financeiro'  => 'modules/configuracoes/financeiro.php',
    'configuracoes/normam202'   => 'modules/configuracoes/normam202.php',
    'configuracoes/normam202/actions' => 'modules/configuracoes/normam202_actions.php',
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
    'autenticidade'                     => 'modules/autenticidade/index.php',
    'autenticidade/aprovar'             => 'modules/autenticidade/aprovar.php',
    'autenticidade/cancelar'            => 'modules/autenticidade/cancelar.php',
    'documentos/aprovar'             => 'modules/autenticidade/aprovar.php',
    'documentos/cancelar'            => 'modules/autenticidade/cancelar.php',
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
        'analises-planos/referencias' => 'analise_planos', 'analises-planos/referencias-actions' => 'analise_planos',
        'protocolos' => 'protocolos_documentais', 'protocolos/form' => 'protocolos_documentais', 'protocolos/actions' => 'protocolos_documentais', 'protocolos/pdf' => 'protocolos_documentais', 'protocolos/pdf-dossie' => 'protocolos_documentais', 'protocolos/arquivo' => 'protocolos_documentais',
        'protocolos/configuracoes' => 'protocolos_documentais',
        'financeiro' => 'financeiro', 'financeiro/form' => 'financeiro', 'financeiro/actions' => 'financeiro', 'financeiro/relatorios' => 'financeiro', 'financeiro/relatorios/exportar' => 'financeiro',
        'usuarios' => 'usuarios', 'usuarios/form' => 'usuarios', 'usuarios/actions' => 'usuarios',
        'agendamentos' => 'agendamentos', 'agendamentos/form' => 'agendamentos', 'agendamentos/actions' => 'agendamentos', 'agendamentos/os' => 'agendamentos',
        'comercial' => 'comercial', 'comercial/nova' => 'comercial', 'comercial/pdf' => 'comercial', 'comercial/propostas' => 'comercial', 'comercial/propostas/actions' => 'comercial',
        'servicos' => 'servicos', 'servicos/form' => 'servicos', 'servicos/actions' => 'servicos',
        'comercial/servicos' => 'servicos', 'comercial/servicos/form' => 'servicos', 'comercial/servicos/actions' => 'servicos',
        'relatorios' => 'relatorios', 'emails' => 'emails',
        'gestao-acessos-portal' => 'gestao_acessos_portal', 'gestao-acessos-portal/actions' => 'gestao_acessos_portal',
        'portal-clientes' => 'portal_clientes', 'portal-clientes/actions' => 'portal_clientes',
        'configuracoes' => 'configuracoes', 'configuracoes/basicas' => 'configuracoes_basicas', 'configuracoes/financeiro' => 'configuracoes_financeiro', 'configuracoes/normam202' => 'configuracoes_normam202', 'configuracoes/normam202/actions' => 'configuracoes_normam202', 'configuracoes/backup' => 'configuracoes_backup', 'configuracoes/exportacoes' => 'configuracoes_exportacoes', 'configuracoes/exportacoes_actions' => 'configuracoes_exportacoes', 'configuracoes/exportacoes_download' => 'configuracoes_exportacoes', 'configuracoes/actions' => 'configuracoes',
        'responsaveis_assinatura' => 'responsaveis_assinatura', 'responsaveis_assinatura/form' => 'responsaveis_assinatura', 'responsaveis_assinatura/actions' => 'responsaveis_assinatura', 'responsaveis_assinatura/assinatura' => 'responsaveis_assinatura',
        'documentos/aprovar' => 'documentacao', 'documentos/cancelar' => 'documentacao',
        'autenticidade' => 'documentacao', 'autenticidade/aprovar' => 'documentacao', 'autenticidade/cancelar' => 'documentacao',
        'documentacao' => 'documentacao', 'documentacao/aprovacao_relatorios' => 'relatorios_aprovacao',
        'certificados' => 'certificados',
        'certificados/vencimentos' => 'vencimentos_certificados',
        'sgq/manual' => 'sgq', 'sgq/apresentacao' => 'sgq', 'sgq/indicadores' => 'sgq',
        'sgq/nao-conformidades' => 'sgq', 'sgq/nao-conformidades/actions' => 'sgq',
        'sgq/auditoria' => 'sgq', 'sgq/riscos' => 'sgq', 'sgq/riscos/actions' => 'sgq',
    ];
    $permissao_rota = $permissoes_rota[$path] ?? null;
    if (strpos($path, 'documentacao/') === 0 && $path !== 'documentacao/aprovacao_relatorios') $permissao_rota = 'documentacao';
    if (strpos($path, 'certificados/') === 0) {
        $permissao_rota = ($path === 'certificados/vencimentos') ? 'vencimentos_certificados' : 'certificados';
    }
    if (strpos($path, 'sgq') === 0) $permissao_rota = 'sgq';
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
    require_once __DIR__ . '/modules/autenticidade/validar.php';
    exit;
} elseif (strpos($path, 'validar-assinatura/') === 0) {
    $_GET['token'] = substr($path, 19);
    require_once __DIR__ . '/modules/autenticidade/validar_assinatura.php';
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
    ?>
    <!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fluxo de assinatura atualizado</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#eef4f1;font-family:Arial,sans-serif;color:#173d32}.box{max-width:560px;margin:20px;background:#fff;border:1px solid #d7e3de;border-radius:14px;padding:34px;box-shadow:0 12px 35px rgba(0,50,38,.1)}h1{font-size:24px}p{line-height:1.6;color:#5d7069}</style></head><body><main class="box"><h1>Fluxo publico desativado</h1><p>Os documentos tecnicos da Amazon Naval agora sao aprovados e assinados eletronicamente dentro da area administrativa do ERP. Este link antigo nao aceita mais assinaturas.</p></main></body></html>
    <?php
    exit;
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
