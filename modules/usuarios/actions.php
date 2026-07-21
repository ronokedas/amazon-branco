<?php
/**
 * MODULO: USUARIOS
 * Arquivo: actions.php - Processar acoes (salvar, desativar, excluir)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

// Exigir login e cargo ADMIN
verificar_sessao();
exigirAcesso('usuarios');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'usuarios');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        $id          = trim($_POST['id'] ?? '');
        $nome        = trim($_POST['nome'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $cargo       = $_POST['cargo'] ?? 'VISTORIADOR';
        $senha       = $_POST['senha'] ?? '';
        $confirma    = $_POST['senha_confirma'] ?? '';
        $ativo       = isset($_POST['ativo']) ? 1 : 0;
        $gestorId    = trim($_POST['gestor_id'] ?? '') ?: null;
        $escritoriosIds = array_values(array_unique(array_filter(array_map('trim', (array)($_POST['escritorios_ids'] ?? [])))));
        $escritorioPrincipalId = trim($_POST['escritorio_principal_id'] ?? '');

        // Validacoes
        $erros = [];
        $errosCampos = [];

        if (empty($nome)) {
            $erros[] = 'O nome e obrigatorio.';
            $errosCampos['nome'] = 'Informe o nome do usuario.';
        } elseif (strlen($nome) < 3) {
            $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
            $errosCampos['nome'] = 'Use pelo menos 3 caracteres.';
        }

        if (empty($email)) {
            $erros[] = 'O email e obrigatorio.';
            $errosCampos['email'] = 'Informe o e-mail.';
        } elseif (!validarEmail($email)) {
            $erros[] = 'Email invalido.';
            $errosCampos['email'] = 'Informe um e-mail valido.';
        }

        if (!in_array($cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR', 'ANALISTA'])) {
            $erros[] = 'Cargo invalido.';
            $errosCampos['cargo'] = 'Selecione um cargo valido.';
        }

        if (!$escritoriosIds || !$escritorioPrincipalId || !in_array($escritorioPrincipalId, $escritoriosIds, true)) {
            $erros[] = 'Selecione ao menos um escritorio e defina o escritorio principal.';
            $errosCampos['escritorios_ids'] = 'Revise os escritorios do funcionario.';
        }

        // Se e criacao, senha e obrigatoria
        $isEdicao = !empty($id);
        if ($gestorId && $isEdicao && $gestorId === $id) {
            $erros[] = 'O usuário não pode ser seu próprio gestor.';
            $errosCampos['gestor_id'] = 'Selecione outro gestor.';
        }
        if ($gestorId) {
            $stmtGestor = $pdo->prepare('SELECT id FROM usuarios WHERE id=:id AND ativo=1 AND excluido_em IS NULL');
            $stmtGestor->execute([':id'=>$gestorId]);
            if (!$stmtGestor->fetchColumn()) { $erros[]='Gestor inválido ou inativo.'; $errosCampos['gestor_id']='Selecione um gestor ativo.'; }
            if ($isEdicao) {
                $cursor=$gestorId; $visitados=[];
                while ($cursor && !isset($visitados[$cursor])) { if ($cursor===$id) { $erros[]='O vínculo de gestor criaria um ciclo na hierarquia.'; $errosCampos['gestor_id']='Revise a hierarquia.'; break; } $visitados[$cursor]=1; $s=$pdo->prepare('SELECT gestor_id FROM usuarios WHERE id=:id');$s->execute([':id'=>$cursor]);$cursor=$s->fetchColumn()?:null; }
            }
        }
        if (!$isEdicao) {
            if (empty($senha)) {
                $erros[] = 'A senha e obrigatoria para novos usuarios.';
                $errosCampos['senha'] = 'Informe uma senha.';
            } elseif (strlen($senha) < 6) {
                $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
                $errosCampos['senha'] = 'Use pelo menos 6 caracteres.';
            }
            if ($senha !== $confirma) {
                $erros[] = 'As senhas nao conferem.';
                $errosCampos['senha_confirma'] = 'A confirmacao nao corresponde a senha.';
            }
        } else {
            // Se senha informada na edicao, validar
            if (!empty($senha)) {
                if (strlen($senha) < 6) {
                    $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
                    $errosCampos['senha'] = 'Use pelo menos 6 caracteres.';
                }
                if ($senha !== $confirma) {
                    $erros[] = 'As senhas nao conferem.';
                    $errosCampos['senha_confirma'] = 'A confirmacao nao corresponde a senha.';
                }
            }
        }

        // Verificar email duplicado
        if (empty($erros)) {
            try {
                $sqlCheck = "SELECT id FROM usuarios WHERE email = :email";
                $paramsCheck = [':email' => $email];
                if ($isEdicao) {
                    $sqlCheck .= " AND id <> :id";
                    $paramsCheck[':id'] = $id;
                }
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute($paramsCheck);
                if ($stmtCheck->fetch()) {
                    $erros[] = 'Ja existe um usuario com este email.';
                    $errosCampos['email'] = 'Este e-mail ja esta cadastrado.';
                }
            } catch (Exception $e) {
                error_log('Erro ao verificar email: ' . $e->getMessage());
                $erros[] = 'Erro ao validar dados.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros), $errosCampos);
            // Retornar para o form com os dados
            $url = APP_URL . 'usuarios/form';
            if ($isEdicao) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        $cargoAnterior = null;
        if ($isEdicao) {
            try {
                $stmtCargo = $pdo->prepare('SELECT cargo FROM usuarios WHERE id = :id AND excluido_em IS NULL LIMIT 1');
                $stmtCargo->execute([':id' => $id]);
                $cargoAnterior = $stmtCargo->fetchColumn() ?: null;
            } catch (Throwable $e) {
                $cargoAnterior = null;
            }
        }

        if ($isEdicao && $cargoAnterior === null) {
            setMensagem('error', 'Usuario nao encontrado ou ja excluido.');
            redirecionar(APP_URL . 'usuarios');
        }

        try {
            $pdo->beginTransaction();
            if ($isEdicao) {
                // Atualizar
                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, cargo = :cargo, senha_hash = :senha, ativo = :ativo, gestor_id=:gestor WHERE id = :id AND excluido_em IS NULL");
                    $stmt->execute([
                        ':nome'   => $nome,
                        ':email'  => $email,
                        ':cargo'  => $cargo,
                        ':senha'  => $senhaHash,
                        ':ativo'  => $ativo, ':gestor'=>$gestorId,
                        ':id'     => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, cargo = :cargo, ativo = :ativo, gestor_id=:gestor WHERE id = :id AND excluido_em IS NULL");
                    $stmt->execute([
                        ':nome'   => $nome,
                        ':email'  => $email,
                        ':cargo'  => $cargo,
                        ':ativo'  => $ativo, ':gestor'=>$gestorId,
                        ':id'     => $id
                    ]);
                }
                try {
                    $stmtPerfil = $pdo->prepare("INSERT IGNORE INTO usuario_perfis (usuario_id, perfil) VALUES (:usuario_id, :perfil)");
                    $stmtPerfil->execute([':usuario_id' => $id, ':perfil' => $cargo]);
                } catch (Throwable $e) {
                    // Compatibilidade quando a migration de perfis ainda nao foi aplicada.
                }
                if ($cargoAnterior !== $cargo) {
                    try { aplicarPermissoesPadraoUsuario($pdo, $id, $cargo); } catch (Throwable $e) { error_log('Erro ao aplicar permissoes padrao: '.$e->getMessage()); }
                }
                financeiroSalvarVinculosUsuario($pdo, $id, $escritoriosIds, $escritorioPrincipalId);
                setMensagem('success', 'Usuario atualizado com sucesso!');
            } else {
                // Criar
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $novoUsuarioId = gerarUUID();
                $stmt = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo, gestor_id) VALUES (:id, :nome, :email, :senha, :cargo, :ativo, :gestor)");
                $stmt->execute([
                    ':id'     => $novoUsuarioId,
                    ':nome'   => $nome,
                    ':email'  => $email,
                    ':senha'  => $senhaHash,
                    ':cargo'  => $cargo,
                    ':ativo'  => $ativo, ':gestor'=>$gestorId
                ]);
                try {
                    $stmtPerfil = $pdo->prepare("INSERT IGNORE INTO usuario_perfis (usuario_id, perfil) VALUES (:usuario_id, :perfil)");
                    $stmtPerfil->execute([':usuario_id' => $novoUsuarioId, ':perfil' => $cargo]);
                } catch (Throwable $e) {
                    // Compatibilidade quando a migration de perfis ainda nao foi aplicada.
                }
                try { aplicarPermissoesPadraoUsuario($pdo, $novoUsuarioId, $cargo); } catch (Throwable $e) { error_log('Erro ao aplicar permissoes padrao: '.$e->getMessage()); }
                financeiroSalvarVinculosUsuario($pdo, $novoUsuarioId, $escritoriosIds, $escritorioPrincipalId);
                setMensagem('success', 'Usuario criado com sucesso!');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao salvar usuario: ' . $e->getMessage());
            setMensagem('error', $e instanceof RuntimeException ? $e->getMessage() : 'Erro ao salvar usuario. Tente novamente.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    // ==============================
    // DESATIVAR / ATIVAR
    // ==============================
    case 'alternar_status':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        // Nao permitir desativar a si mesmo
        if ($id === $_SESSION['usuario_id']) {
            setMensagem('error', 'Voce nao pode desativar seu proprio usuario.');
            redirecionar(APP_URL . 'usuarios');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, ativo FROM usuarios WHERE id = :id AND excluido_em IS NULL");
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                setMensagem('error', 'Usuario nao encontrado.');
                redirecionar(APP_URL . 'usuarios');
            }

            $novoStatus = $usuario['ativo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id");
            $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);

            $msgStatus = $novoStatus ? 'ativado' : 'desativado';
            setMensagem('success', "Usuario {$msgStatus} com sucesso!");
        } catch (Exception $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status do usuario.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    // ==============================
    // EXCLUIR ACESSO, PRESERVANDO O HISTORICO
    // ==============================
    case 'excluir':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'usuarios');
        }

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, nome, email, cargo, ativo
                 FROM usuarios
                 WHERE id = :id AND excluido_em IS NULL
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $pdo->rollBack();
                setMensagem('error', 'Usuario nao encontrado ou ja excluido.');
                redirecionar(APP_URL . 'usuarios');
            }

            $autoExclusao = hash_equals((string)$_SESSION['usuario_id'], (string)$id);
            if ($autoExclusao && $usuario['cargo'] === 'ADMIN') {
                $stmtAdministradores = $pdo->query(
                    "SELECT id FROM usuarios
                     WHERE cargo = 'ADMIN' AND excluido_em IS NULL
                     FOR UPDATE"
                );
                $totalAdministradores = count($stmtAdministradores->fetchAll(PDO::FETCH_COLUMN));

                if (!podeExcluirProprioAdministrador($totalAdministradores)) {
                    $pdo->rollBack();
                    setMensagem('error', 'O unico administrador do sistema nao pode excluir a propria conta.');
                    redirecionar(APP_URL . 'usuarios');
                }
            }

            $emailExcluido = 'excluido.' . str_replace('-', '', $id) . '@local.invalid';
            $senhaInutilizavel = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE usuarios
                 SET ativo = 0,
                     email = :email,
                     senha_hash = :senha,
                     excluido_em = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                ':email' => $emailExcluido,
                ':senha' => $senhaInutilizavel,
                ':id' => $id,
            ]);

            $pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM usuario_perfis WHERE usuario_id = :id")->execute([':id' => $id]);

            if (function_exists('log_atividade')) {
                log_atividade('usuario_excluido', 'Acesso do usuario ' . $usuario['nome'] . ' excluido.');
            }

            $pdo->commit();

            if ($autoExclusao) {
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                redirecionar(APP_URL . 'login');
            }

            setMensagem('success', 'Usuario excluido com sucesso!');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao excluir usuario: ' . $e->getMessage());
            setMensagem('error', 'Erro ao excluir usuario. Tente novamente.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'usuarios');
}
