<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

if (clienteEstaLogado()) {
    header('Location: ' . APP_URL . (clientePortalForcarTrocaSenha() ? 'portal/trocar-senha' : 'portal'));
    exit;
}

$erro_msg = '';
$email = strtolower(trim($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro_msg = 'Preencha e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro_msg = 'Informe um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.nome, c.email, c.perfil, a.senha_hash, a.ativo, a.forcar_troca_senha
                FROM clientes c
                INNER JOIN cliente_portal_acessos a ON a.cliente_id = c.id
                WHERE a.login = :email
                  AND c.perfil IN ('proprietario','despachante')
                  AND c.status = 'ATIVO'
                  AND a.ativo = 1
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($senha, $row['senha_hash'])) {
                loginCliente($row, $row);
                $pdo->prepare("UPDATE cliente_portal_acessos SET ultimo_login_em = NOW() WHERE cliente_id = :id")
                    ->execute([':id' => $row['id']]);
                clientePortalAuditar($pdo, 'LOGIN_SUCESSO', $row['id'], null, null, null, true, 'Perfil: '.$row['perfil']);
                header('Location: ' . APP_URL . (clientePortalForcarTrocaSenha() ? 'portal/trocar-senha' : 'portal'));
                exit;
            }

            clientePortalAuditar($pdo, 'LOGIN_FALHA', $row['id'] ?? null, null, null, null, false, 'Login informado: '.substr($email,0,190));
            $erro_msg = 'E-mail ou senha incorretos.';
        } catch (Exception $e) {
            error_log('Erro no login do portal: ' . $e->getMessage());
            $erro_msg = 'Não foi possível entrar agora. Tente novamente.';
        }
    }
}

$titulo_page = 'Entrar no Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-auth">
    <div class="portal-auth-card">
        <div class="portal-auth-logo-wrap">
            <img src="<?php echo APP_URL; ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora" class="portal-auth-logo">
        </div>
        <h1>Portal do Cliente</h1>
        <p>Acesse certificados e relatórios das suas embarcações</p>

        <?php if ($erro_msg): ?>
            <div class="message error"><i class="fas fa-circle-exclamation"></i><span><?php echo h($erro_msg); ?></span></div>
        <?php endif; ?>

        <form method="POST" class="form-padrao">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="seu.email@exemplo.com" value="<?php echo h($email); ?>">
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <div class="portal-auth-options">
                <label>
                    <input type="checkbox" checked>
                    <span>Lembrar-me</span>
                </label>
                <a class="portal-auth-link" href="<?php echo APP_URL; ?>portal/recuperar-senha">Esqueci minha senha</a>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Entrar</button>
        </form>
    </div>
    <div class="portal-auth-note">
        <span>© <?php echo date('Y'); ?> Amazon Certificadora. Todos os direitos reservados.</span>
    </div>
</section>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
