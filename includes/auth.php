<?php
/**
 * AUTENTICACAO DO SISTEMA ERP
 * 
 * Funcoes de verificacao de sessao e permissoes
 */

// Verificar se esta logado
function estaLogado() {
    return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
}

// Verificar cargo do usuario logado
function getCargo() {
    return $_SESSION['usuario_cargo'] ?? null;
}

// Perfis múltiplos convivem com o campo legado `cargo` durante a migração.
function getPerfisUsuario(?string $usuarioId = null): array {
    global $pdo;
    $usuarioId = $usuarioId ?: ($_SESSION['usuario_id'] ?? null);
    if (!$usuarioId) return [];

    $perfis = [];
    try {
        $stmt = $pdo->prepare("SELECT perfil FROM usuario_perfis WHERE usuario_id = :id ORDER BY perfil");
        $stmt->execute([':id' => $usuarioId]);
        $perfis = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        // Compatibilidade antes da migration 057.
    }

    $cargo = getCargo();
    if ($cargo && !in_array($cargo, $perfis, true)) $perfis[] = $cargo;
    return array_values(array_unique($perfis));
}

function temPerfil(string $perfil, ?string $usuarioId = null): bool {
    $perfis = getPerfisUsuario($usuarioId);
    // Administradores podem operar qualquer perfil sem uma segunda conta.
    return in_array('ADMIN', $perfis, true) || in_array($perfil, $perfis, true);
}

/** Lista canônica de todas as permissões granulares do sistema */
function todasPermissoesSistema(): array {
    return [
        'dashboard',
        'vistorias',
        'agendamentos',
        'analise_planos',
        'relatorios_aprovacao',
        'protocolos_documentais',
        'certificados',
        'vencimentos_certificados',
        'documentacao',
        'clientes',
        'embarcacoes',
        'armadores',
        'proprietarios',
        'despachantes',
        'comercial',
        'servicos',
        'financeiro',
        'emails',
        'gestao_acessos_portal',
        'portal_clientes',
        'relatorios',
        'sgq',
        'usuarios',
        'configuracoes',
        'configuracoes_normam202',
        'configuracoes_basicas',
        'configuracoes_financeiro',
        'configuracoes_backup',
        'configuracoes_exportacoes',
        'responsaveis_assinatura',
    ];
}

/**
 * Verifica se o usuário pode acessar um sub-módulo de configurações.
 * Aceita acesso se tiver a permissão completa do módulo pai OU a permissão granular do sub-módulo.
 * Exemplo: podeAcessarOuSub('configuracoes', 'configuracoes_normam202')
 *   → true se o usuário tiver 'configuracoes' OU 'configuracoes_normam202'
 */
function podeAcessarOuSub(string $moduloPai, string $subModulo): bool {
    return podeAcessar($moduloPai) || podeAcessar($subModulo);
}

/** Exige permissão do módulo pai OU do sub-módulo. Redireciona se ambos forem negados. */
function exigirAcessoOuSub(string $moduloPai, string $subModulo, string $destino = 'dashboard'): void {
    requireLogin();
    if (podeAcessarOuSub($moduloPai, $subModulo)) return;

    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . $destino);
}

/** Módulos iniciais mínimos e essenciais para cada cargo naval. O administrador pode personalizar depois. */
function permissoesPadraoCargo(string $cargo): array {
    return match ($cargo) {
        'VISTORIADOR' => ['dashboard', 'vistorias', 'agendamentos', 'clientes', 'embarcacoes', 'documentacao', 'configuracoes_normam202', 'vencimentos_certificados'],
        'ANALISTA' => ['dashboard', 'analise_planos', 'relatorios_aprovacao', 'protocolos_documentais', 'clientes', 'embarcacoes', 'armadores', 'proprietarios', 'vistorias', 'certificados', 'vencimentos_certificados', 'documentacao', 'configuracoes_normam202'],
        'VENDEDOR' => ['dashboard', 'comercial', 'servicos', 'clientes', 'embarcacoes', 'armadores', 'proprietarios', 'despachantes', 'agendamentos', 'emails', 'vencimentos_certificados'],
        'ADMIN' => todasPermissoesSistema(),
        default => ['dashboard'],
    };
}

/** Inicializa as permissões no banco para o novo usuário com 1 para o padrão do cargo e 0 para os demais */
function aplicarPermissoesPadraoUsuario(PDO $pdo, string $usuarioId, string $cargo): void {
    if ($cargo === 'ADMIN') return;
    $padrao = permissoesPadraoCargo($cargo);
    $stmt = $pdo->prepare('INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:usuario_id, :permissao, :permitido) ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)');
    foreach (todasPermissoesSistema() as $permissao) {
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':permissao' => $permissao,
            ':permitido' => in_array($permissao, $padrao, true) ? 1 : 0
        ]);
    }
}

/** Permissões individuais definidas pelo administrador. */
function podeAcessar(string $modulo): bool {
    if (!estaLogado()) return false;
    if (getCargo() === 'ADMIN') return true;

    // Compatibilidade bidirecional com a unificação de cadastros:
    // 1. Permissão 'clientes' concede acesso a clientes, armadores, proprietários e despachantes.
    // 2. Qualquer permissão legada ativa concede acesso a 'clientes'.
    if ($modulo === 'clientes') {
        return podeAcessar('clientes_interno') || podeAcessar('armadores_interno') || podeAcessar('proprietarios_interno') || podeAcessar('despachantes_interno');
    }
    if (in_array($modulo, ['armadores', 'proprietarios', 'despachantes'], true)) {
        return podeAcessar('clientes_interno') || podeAcessar($modulo . '_interno');
    }
    if ($modulo === 'gestao_acessos_portal' || $modulo === 'portal_clientes') {
        return podeAcessar('gestao_acessos_portal_interno') || podeAcessar('portal_clientes_interno');
    }
    if (str_starts_with($modulo, 'configuracoes_') && $modulo !== 'configuracoes') {
        if (podeAcessar('configuracoes')) {
            return true;
        }
    }

    if (str_ends_with($modulo, '_interno')) {
        $modulo = substr($modulo, 0, -8);
    }

    global $pdo;
    $usuarioId = $_SESSION['usuario_id'] ?? '';
    if ($usuarioId && $pdo) {
        try {
            $stmt = $pdo->prepare('SELECT permitido FROM usuario_permissoes WHERE usuario_id = :usuario_id AND permissao = :permissao LIMIT 1');
            $stmt->execute([':usuario_id' => $usuarioId, ':permissao' => $modulo]);
            $valor = $stmt->fetchColumn();
            if ($valor !== false) return (int)$valor === 1;
        } catch (Throwable $e) {
            // Compatibilidade enquanto a tabela de permissões ainda não existe.
        }
    }

    // Mantém as permissões atuais até o administrador salvar a matriz de acesso.
    $cargo = getCargo();
    if (in_array($modulo, ['documentacao', 'financeiro'], true)) {
        try {
            $coluna = 'acesso_' . $modulo;
            $stmt = $pdo->prepare("SELECT {$coluna} FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $usuarioId]);
            return (int)$stmt->fetchColumn() === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    return in_array($modulo, permissoesPadraoCargo($cargo), true);
}

/** Exige a mesma permissao granular usada pelo roteador e pela barra lateral. */
function exigirAcesso(string $modulo, string $destino = 'dashboard'): void {
    requireLogin();
    if (podeAcessar($modulo)) return;

    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . $destino);
}

// Legado mantido abaixo por compatibilidade de leitura em instalações antigas.
function podeAcessarLegado($modulo) {
    if (!estaLogado()) {
        return false;
    }
    
    $cargo = getCargo();
    
    // ADMIN tem acesso a tudo
    if ($cargo === 'ADMIN') {
        return true;
    }
    
    // Verificação específica para módulos baseada em configuração (tabela usuarios)
    if ($modulo === 'documentacao' || $modulo === 'financeiro') {
        global $pdo;
        if (isset($_SESSION['usuario_id']) && $pdo) {
            try {
                $coluna = 'acesso_' . $modulo; // 'acesso_documentacao' ou 'acesso_financeiro'
                $stmt = $pdo->prepare("SELECT {$coluna} FROM usuarios WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['usuario_id']]);
                $tem_acesso = $stmt->fetchColumn();
                if ((int)$tem_acesso === 1) {
                    return true;
                }
            } catch (Exception $e) {
                // Silenciar erro
            }
        }
        return false; // Sem acesso explícito, bloqueia
    }

    // Permissões por cargo (default deny: cargos desconhecidos não acessam nada).
    // Cargos válidos do sistema: ADMIN, VENDEDOR, VISTORIADOR, ANALISTA
    // (ver modules/usuarios/actions.php).
    switch ($cargo) {
        case 'ADMIN':
            return true;

        case 'VENDEDOR':
            $modulosPermitidos = [
                'dashboard',
                'clientes',
                'embarcacoes',
                'pessoas',
                'vistorias',
                'agendamentos',
                'comercial',
                'emails'
            ];
            return in_array($modulo, $modulosPermitidos, true);

        case 'VISTORIADOR':
            $modulosPermitidos = [
                'dashboard',
                'login',
                'embarcacoes',
                'pessoas',
                'vistorias'
            ];
            return in_array($modulo, $modulosPermitidos, true);

        case 'ANALISTA':
            $modulosPermitidos = [
                'dashboard',
                'vistorias',
                'documentacao'
            ];
            return in_array($modulo, $modulosPermitidos, true);

        default:
            // Cargo desconhecido/nulo: negar acesso por segurança.
            return false;
    }
}

// Redirecionar para login se nao estiver logado
function requireLogin() {
    if (!estaLogado()) {
        header('Location: ' . APP_URL . 'login');
        exit;
    }
}

// Redirecionar se usuario nao tiver permissao
function requireCargo($cargoRequerido) {
    requireLogin();
    
    $cargo = getCargo();
    
    if (is_array($cargoRequerido)) {
        // Aceita array de cargos: ['ADMIN', 'VENDEDOR']
        if (!in_array($cargo, $cargoRequerido)) {
            header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
            exit;
        }
    } else {
        // Aceita string simples: 'ADMIN'
        if ($cargo !== $cargoRequerido) {
            header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
            exit;
        }
    }
}

/**
 * Rate Limiting de Login: Máximo de 5 tentativas consecutivas incorretas em 15 minutos por IP ou E-mail.
 * Retorna mensagem descritiva se bloqueado, ou null se liberado.
 */
function loginVerificarRateLimit(PDO $pdo, string $email, string $ip): ?string {
    try {
        $janelaMinutos = 15;
        $maxTentativas = 5;
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) 
             FROM login_tentativas 
             WHERE sucesso = 0 
               AND criado_em >= (NOW() - INTERVAL :minutos MINUTE)
               AND (ip = :ip OR (email = :email AND email != ''))"
        );
        $stmt->execute([
            ':minutos' => $janelaMinutos,
            ':ip' => $ip,
            ':email' => $email
        ]);
        $falhas = (int)$stmt->fetchColumn();

        if ($falhas >= $maxTentativas) {
            return "Muitas tentativas incorretas de login. Por segurança, o acesso para este usuário/endereço está temporariamente bloqueado por {$janelaMinutos} minutos.";
        }
    } catch (Throwable $e) {
        error_log("Erro ao verificar rate limit de login: " . $e->getMessage());
    }
    return null;
}

/**
 * Registra a tentativa de login (sucesso ou falha) e limpa registros antigos.
 */
function loginRegistrarTentativa(PDO $pdo, string $email, string $ip, bool $sucesso): void {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO login_tentativas (ip, email, sucesso, criado_em) 
             VALUES (:ip, :email, :sucesso, NOW())"
        );
        $stmt->execute([
            ':ip' => $ip,
            ':email' => $email,
            ':sucesso' => $sucesso ? 1 : 0
        ]);

        // Se logou com sucesso, limpa as falhas anteriores para esse par ip/email
        if ($sucesso) {
            $stmtClear = $pdo->prepare(
                "DELETE FROM login_tentativas WHERE ip = :ip OR (email = :email AND email != '')"
            );
            $stmtClear->execute([
                ':ip' => $ip,
                ':email' => $email
            ]);
        }

        // Limpeza probabilística de registros com mais de 24 horas (1 em 50 requisições)
        if (random_int(1, 50) === 1) {
            $pdo->exec("DELETE FROM login_tentativas WHERE criado_em < (NOW() - INTERVAL 24 HOUR)");
        }
    } catch (Throwable $e) {
        error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
    }
}

// Inicializar sessao para o usuario
function login($usuario) {
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_cargo'] = $usuario['cargo'];
    $_SESSION['versao_sessao'] = (int)($usuario['versao_sessao'] ?? 1);
    $_SESSION['usuario_logado'] = true;
    $_SESSION['login_time'] = time();
}

// Encerrar sessao
function logout() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_unset();
        session_destroy();
    }
    header('Location: ' . APP_URL . 'login');
    exit;
}

// Verificar se a sessao e o usuario continuam validos.
// Invalida a sessao imediatamente se o usuario for desativado, excluido, tiver cargo alterado ou permissoes revogadas.
function verificarSessao() {
    if (!estaLogado()) {
        logout();
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare(
            "SELECT ativo, excluido_em, cargo, versao_sessao
             FROM usuarios
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $_SESSION['usuario_id'] ?? '']);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        $versaoSessao = (int)($usuario['versao_sessao'] ?? 0);
        $versaoEsperada = (int)($_SESSION['versao_sessao'] ?? 0);

        if (
            !$usuario ||
            (int)$usuario['ativo'] !== 1 ||
            $usuario['excluido_em'] !== null ||
            $versaoSessao !== $versaoEsperada ||
            (isset($_SESSION['usuario_cargo']) && $usuario['cargo'] !== $_SESSION['usuario_cargo'])
        ) {
            logout();
        }
    } catch (Throwable $e) {
        error_log('Erro ao validar sessao do usuario: ' . $e->getMessage());
    }

    // Mantido apenas como registro da ultima atividade, sem causar logout.
    $_SESSION['login_time'] = time();
}

// Alias para compatibilidade com o modulo
function verificar_sessao() {
    verificarSessao();
    requireLogin();
}

// Verificar se o usuario logado possui o cargo especificado
function verificar_cargo($cargoRequerido) {
    requireCargo($cargoRequerido);
}

// Verificar se o usuario logado e VENDEDOR
function is_vendedor() {
    return getCargo() === 'VENDEDOR';
}

// Obter usuario logado por ID
function getUsuarioLogado() {
    global $pdo;
    if (!estaLogado()) return null;
    $stmt = $pdo->prepare("SELECT id, nome, email, cargo, ativo FROM usuarios WHERE id = :id AND excluido_em IS NULL LIMIT 1");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
