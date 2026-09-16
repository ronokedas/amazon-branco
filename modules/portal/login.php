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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal do Cliente - Amazon Certificadora. Acesse certificados, relatórios e documentos das suas embarcações.">
    <title><?php echo h($titulo_page); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ========== PORTAL LOGIN – PREMIUM REDESIGN ========== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f4f7f6;
            color: #18352f;
            overflow: hidden;
        }

        /* ===== LEFT PANEL – Brand / Visual ===== */
        .login-brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            background: linear-gradient(165deg, #032b23 0%, #063c30 35%, #042f26 70%, #021d18 100%);
            overflow: hidden;
        }

        /* Decorative gradients */
        .login-brand-panel::before {
            content: "";
            position: absolute;
            width: 600px;
            height: 600px;
            top: -120px;
            right: -180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(46, 211, 155, .12), transparent 70%);
            pointer-events: none;
        }
        .login-brand-panel::after {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            bottom: -80px;
            left: -100px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(46, 211, 155, .08), transparent 70%);
            pointer-events: none;
        }

        .login-brand-content {
            position: relative;
            z-index: 1;
            max-width: 420px;
            text-align: left;
        }

        .login-brand-logo {
            width: 260px;
            max-height: 80px;
            margin-bottom: 48px;
            filter: drop-shadow(0 8px 24px rgba(0,0,0,.2));
        }

        .login-brand-content h1 {
            font-size: 38px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: -.03em;
            margin-bottom: 16px;
        }

        .login-brand-content h1 span {
            display: block;
            background: linear-gradient(135deg, #2ed39b, #56e0b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-brand-content p {
            color: rgba(255, 255, 255, .7);
            font-size: 16px;
            line-height: 1.65;
            margin-bottom: 42px;
        }

        /* Feature pills */
        .login-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .login-feature {
            display: flex;
            align-items: center;
            gap: 14px;
            color: rgba(255, 255, 255, .85);
            font-size: 14px;
            font-weight: 500;
        }
        .login-feature-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(46, 211, 155, .12);
            color: #2ed39b;
            font-size: 16px;
            flex-shrink: 0;
            border: 1px solid rgba(46, 211, 155, .15);
        }

        /* ===== RIGHT PANEL – Login Form ===== */
        .login-form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 40px;
            background: #ffffff;
            overflow-y: auto;
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }



        .login-form-header {
            margin-bottom: 36px;
        }
        .login-form-header .login-greeting {
            font-size: 13px;
            font-weight: 700;
            color: #0b9b70;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 8px;
        }
        .login-form-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: #18352f;
            margin-bottom: 8px;
        }
        .login-form-header p {
            font-size: 14.5px;
            color: #63756f;
            line-height: 1.5;
        }

        /* Error message */
        .login-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            margin-bottom: 22px;
            border-radius: 10px;
            border: 1px solid #fbc8c8;
            background: #fff0f0;
            color: #dc3f43;
            font-size: 13.5px;
            font-weight: 600;
            animation: loginShake .4s ease;
        }
        .login-error i { font-size: 16px; flex-shrink: 0; }

        @keyframes loginShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        /* Form */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .login-field {
            position: relative;
        }
        .login-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
            color: #18352f;
        }
        .login-field .login-input-wrap {
            position: relative;
        }
        .login-field .login-input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #80908b;
            font-size: 15px;
            pointer-events: none;
            transition: color .2s;
        }
        .login-field input {
            width: 100%;
            height: 50px;
            padding: 0 16px 0 46px;
            border: 1.5px solid #dce5e2;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14.5px;
            color: #18352f;
            background: #f8faf9;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .login-field input::placeholder {
            color: #9aada6;
        }
        .login-field input:focus {
            outline: none;
            border-color: #0b9b70;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(11, 155, 112, .1);
        }
        .login-field input:focus + .login-input-icon,
        .login-field input:focus ~ .login-input-icon {
            color: #0b9b70;
        }
        /* Password toggle */
        .login-toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #80908b;
            font-size: 15px;
            cursor: pointer;
            padding: 4px;
            transition: color .2s;
        }
        .login-toggle-pw:hover { color: #0b9b70; }

        /* Options row */
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: -4px;
        }
        .login-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #63756f;
            font-weight: 500;
        }
        .login-remember input {
            width: 18px;
            height: 18px;
            border: 1.5px solid #c8d5d1;
            border-radius: 5px;
            appearance: none;
            background: #fff;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all .2s;
        }
        .login-remember input:checked {
            background: #0b9b70;
            border-color: #0b9b70;
        }
        .login-remember input:checked::after {
            content: "✓";
            color: #fff;
            font-size: 11px;
            font-weight: 900;
        }
        .login-forgot {
            font-size: 13px;
            font-weight: 700;
            color: #0b9b70;
            text-decoration: none;
            transition: color .15s;
        }
        .login-forgot:hover { color: #087b5c; text-decoration: underline; }

        /* Submit */
        .login-submit {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #0b9b70, #087b5c);
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all .25s;
            box-shadow: 0 4px 16px rgba(11, 155, 112, .2);
            margin-top: 6px;
        }
        .login-submit:hover {
            background: linear-gradient(135deg, #087b5c, #065e46);
            box-shadow: 0 8px 28px rgba(11, 155, 112, .3);
            transform: translateY(-1px);
        }
        .login-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(11, 155, 112, .2);
        }

        /* Footer */
        .login-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12.5px;
            color: #9aada6;
        }
        .login-footer a {
            color: #0b9b70;
            text-decoration: none;
            font-weight: 600;
        }

        /* Divider */
        .login-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 4px 0;
            color: #b0c0ba;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .login-divider::before,
        .login-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #dce5e2;
        }

        /* Security badge */
        .login-security {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 10px 16px;
            border-radius: 8px;
            background: #f4f7f6;
            font-size: 12px;
            color: #80908b;
            font-weight: 500;
        }
        .login-security i {
            color: #0b9b70;
            font-size: 13px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 960px) {
            body {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                overflow-y: auto;
            }
            .login-brand-panel { display: none; }
            .login-mobile-header { display: flex; }
            .login-form-panel {
                min-height: 0;
                padding: 28px 24px 32px;
                justify-content: flex-start;
            }
            .login-form-container { max-width: 440px; }
            .login-form-header { margin-bottom: 24px; }
            .login-form-header .login-greeting { font-size: 12px; margin-bottom: 6px; }
            .login-form-header h2 { font-size: 22px; margin-bottom: 4px; }
            .login-form-header p { font-size: 13px; }
            .login-form { gap: 16px; }
            .login-field input { height: 46px; font-size: 14px; }
            .login-submit { height: 48px; font-size: 14px; margin-top: 4px; }
            .login-footer { margin-top: 28px; }
            .login-security { margin-top: 16px; }
        }

        @media (max-width: 480px) {
            .login-form-panel { padding: 20px 20px 28px; }
            .login-form-header h2 { font-size: 20px; }
            .login-form-header p { font-size: 12.5px; }
            .login-options { gap: 10px; }
            .login-field label { font-size: 12px; margin-bottom: 5px; }
            .login-field input { height: 44px; padding-left: 42px; font-size: 13.5px; border-radius: 9px; }
            .login-field .login-input-icon { left: 13px; font-size: 14px; }
            .login-submit { height: 46px; border-radius: 9px; }
            .login-mobile-header { padding: 28px 24px 24px; }
            .login-mobile-header-logo { width: 140px; }
            .login-mobile-header h3 { font-size: 16px; }
            .login-mobile-header p { font-size: 12px; }
            .login-footer { font-size: 11.5px; }
        }

        /* Mobile header banner */
        .login-mobile-header {
            display: none;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 36px 28px 28px;
            background: linear-gradient(165deg, #063c30, #032b23);
            position: relative;
            overflow: hidden;
        }
        .login-mobile-header::before {
            content: "";
            position: absolute;
            width: 300px; height: 300px;
            top: -100px; right: -80px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(46,211,155,.1), transparent 70%);
            pointer-events: none;
        }
        .login-mobile-header-logo {
            width: 160px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.15));
        }
        .login-mobile-header h3 {
            position: relative; z-index: 1;
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 4px;
        }
        .login-mobile-header h3 span {
            color: #2ed39b;
        }
        .login-mobile-header p {
            position: relative; z-index: 1;
            color: rgba(255,255,255,.65);
            font-size: 13px;
            font-weight: 400;
            margin: 0;
        }

        /* Subtle entrance animation */
        @keyframes loginFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-form-container {
            animation: loginFadeUp .5s ease-out;
        }
        .login-brand-content {
            animation: loginFadeUp .6s ease-out .1s both;
        }
    </style>
</head>
<body>
    <!-- Left: Brand Panel -->
    <div class="login-brand-panel">
        <div class="login-brand-content">
            <img src="<?php echo APP_URL; ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora" class="login-brand-logo">

            <h1>Bem-vindo ao<br><span>Portal do Cliente</span></h1>
            <p>Gerencie certificados, relatórios de vistoria e documentos técnicos das suas embarcações com segurança e praticidade.</p>

            <div class="login-features">
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="fas fa-file-certificate"></i></div>
                    Acesso a certificados e relatórios de vistoria
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="fas fa-ship"></i></div>
                    Consulta de embarcações e documentos técnicos
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="fas fa-paper-plane"></i></div>
                    Envio de planos para análise e aprovação
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="fas fa-shield-halved"></i></div>
                    Ambiente seguro e auditado
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Header Banner (visible only on mobile) -->
    <div class="login-mobile-header">
        <img src="<?php echo APP_URL; ?>img/logo-amazon-sidebar.svg" alt="Amazon Certificadora" class="login-mobile-header-logo">
        <h3>Portal do <span>Cliente</span></h3>
        <p>Certificados, relatórios e documentos</p>
    </div>

    <!-- Right: Login Form -->
    <div class="login-form-panel">
        <div class="login-form-container">

            <div class="login-form-header">
                <div class="login-greeting">Portal do Cliente</div>
                <h2>Acesse sua conta</h2>
                <p>Entre com suas credenciais para acessar certificados, documentos e relatórios.</p>
            </div>

            <?php if ($erro_msg): ?>
                <div class="login-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo h($erro_msg); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" autocomplete="on">
                <div class="login-field">
                    <label for="email">E-mail</label>
                    <div class="login-input-wrap">
                        <input type="email" id="email" name="email" required autocomplete="email"
                               placeholder="seu.email@exemplo.com" value="<?php echo h($email); ?>">
                        <i class="fas fa-envelope login-input-icon"></i>
                    </div>
                </div>

                <div class="login-field">
                    <label for="senha">Senha</label>
                    <div class="login-input-wrap">
                        <input type="password" id="senha" name="senha" required autocomplete="current-password"
                               placeholder="••••••••">
                        <i class="fas fa-lock login-input-icon"></i>
                        <button type="button" class="login-toggle-pw" onclick="togglePassword()" title="Mostrar/ocultar senha" aria-label="Mostrar ou ocultar senha">
                            <i class="fas fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="login-remember">
                        <input type="checkbox" checked> Lembrar-me
                    </label>
                    <a href="<?php echo APP_URL; ?>portal/recuperar-senha" class="login-forgot">Esqueci minha senha</a>
                </div>

                <button type="submit" class="login-submit">
                    <i class="fas fa-arrow-right-to-bracket"></i> Entrar
                </button>
            </form>

            <div class="login-security">
                <i class="fas fa-lock"></i>
                Conexão protegida • Seus dados estão seguros
            </div>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> Amazon Certificadora. Todos os direitos reservados.
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const inp = document.getElementById('senha');
        const icon = document.getElementById('pwIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            inp.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Auto-focus first empty field
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        const senhaField = document.getElementById('senha');
        if (!emailField.value) emailField.focus();
        else senhaField.focus();
    });
    </script>
</body>
</html>
