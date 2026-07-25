<?php
/**
 * MODULO: LOGIN
 * Arquivo: index.php - Pagina de autenticacao
 */

// Configuracao ja foi carregada pelo roteador (index.php)
// Sessao ja foi iniciada em config.php

// Importar funcoes e auth
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Processar logout (antes de qualquer redirecionamento)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

// Se ja estiver logado, redirecionar para dashboard
if (estaLogado()) {
    header('Location: ' . APP_URL . 'dashboard');
    exit;
}

// Processar formulario de login
$erro_msg = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $senha    = $_POST['senha'] ?? '';
    
    // Validar campos
    if (empty($email) || empty($senha)) {
        $erro_msg = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro_msg = 'Email invalido.';
    } else {
        // Buscar usuario no banco
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                // Login bem-sucedido - usar funcao do auth.php
                login($usuario);
                
                $retorno = (string)($_SESSION['login_return_to'] ?? '');
                unset($_SESSION['login_return_to']);
                $destino = str_starts_with($retorno, 'minhas-assinaturas') ? $retorno : 'dashboard';
                header('Location: ' . APP_URL . $destino);
                exit;
            } else {
                $erro_msg = 'Email ou senha incorretos.';
            }
        } catch (Exception $e) {
            $erro_msg = 'Erro de conexao. Tente novamente.';
            error_log('Erro no login: ' . $e->getMessage());
        }
    }
}

// Incluir header (modificado para login)
$titulo_page = 'Login - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Conteudo da pagina de login -->
<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <img src="<?php echo APP_URL; ?>img/logo-amazon-certificadora.svg"
                 alt="Amazon Certificadora"
                 class="login-brand-logo">
        </div>

        <?php if (!empty($erro_msg)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo h($erro_msg); ?></span>
                <button class="close-msg" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="seu@email.com" 
                       required 
                       autocomplete="email"
                       value="<?php echo h($email); ?>">
            </div>

            <div class="form-group">
                <label for="senha">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <div class="password-input">
                    <input type="password" 
                           id="senha" 
                           name="senha" 
                           placeholder="••••••••" 
                           required 
                           autocomplete="current-password">
                    <button type="button" class="toggle-senha" onclick="toggleSenha('senha', 'icone-senha')">
                        <i class="fas fa-eye" id="icone-senha"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="login-footer">
            <p><i class="fas fa-shield-alt"></i> Sistema protegido com criptografia</p>
            <p class="version">v1.0.0</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
