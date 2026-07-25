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

/** Módulos iniciais recomendados para cada cargo. O administrador pode personalizar depois. */
function permissoesPadraoCargo(string $cargo): array {
    return match ($cargo) {
        'VENDEDOR' => ['dashboard', 'embarcacoes', 'armadores', 'proprietarios', 'despachantes', 'vistorias', 'agendamentos', 'analise_planos', 'comercial', 'servicos', 'emails'],
        'VISTORIADOR' => ['dashboard', 'vistorias', 'embarcacoes', 'certificados', 'documentacao'],
        'ANALISTA' => ['dashboard', 'vistorias', 'analise_planos'],
        default => [],
    };
}

/** Ativa os padrões sem remover liberações adicionais feitas pelo administrador. */
function aplicarPermissoesPadraoUsuario(PDO $pdo, string $usuarioId, string $cargo): void {
    if ($cargo === 'ADMIN') return;
    $stmt = $pdo->prepare('INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:usuario_id, :permissao, 1) ON DUPLICATE KEY UPDATE permitido = 1');
    foreach (permissoesPadraoCargo($cargo) as $permissao) {
        $stmt->execute([':usuario_id' => $usuarioId, ':permissao' => $permissao]);
    }
}

/** Permissões individuais definidas pelo administrador. */
function podeAcessar(string $modulo): bool {
    if (!estaLogado()) return false;
    if (getCargo() === 'ADMIN') return true;

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

// Inicializar sessao para o usuario
function login($usuario) {
    session_regenerate_id(true);
    unset($_SESSION['campo_login_em']);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_cargo'] = $usuario['cargo'];
    $_SESSION['usuario_logado'] = true;
    $_SESSION['login_time'] = time();
}

// Encerrar sessao
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . 'login');
    exit;
}

// Verificar se a sessao e o usuario continuam validos.
// Nao ha encerramento automatico por tempo de inatividade.
function verificarSessao() {
    if (!estaLogado()) {
        logout();
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare(
            "SELECT ativo, excluido_em
             FROM usuarios
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $_SESSION['usuario_id'] ?? '']);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario || (int)$usuario['ativo'] !== 1 || $usuario['excluido_em'] !== null) {
            logout();
        }
    } catch (Throwable $e) {
        error_log('Erro ao validar sessao do usuario: ' . $e->getMessage());
        // Uma indisponibilidade momentanea do banco nao significa que a
        // autenticacao deixou de ser valida. Encerrar a sessao aqui fazia o
        // usuario voltar aleatoriamente ao login ao navegar entre modulos.
        // A verificacao sera refeita normalmente na proxima requisicao.
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
