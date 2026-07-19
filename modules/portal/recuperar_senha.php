<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/mailer.php';

$enviado = false;
$email = strtolower(trim($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verificarCSRF($_POST['csrf_token'] ?? '') && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.nome, c.email
                FROM clientes c
                INNER JOIN cliente_portal_acessos a ON a.cliente_id = c.id AND a.ativo = 1
                WHERE a.login = :email
                  AND c.perfil IN ('proprietario','despachante')
                  AND c.status = 'ATIVO'
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $pdo->prepare("UPDATE cliente_password_resets SET usado_em = NOW() WHERE cliente_id = :cliente_id AND usado_em IS NULL")
                    ->execute([':cliente_id' => $cliente['id']]);
                $pdo->prepare("
                    INSERT INTO cliente_password_resets (id, cliente_id, token_hash, expira_em)
                    VALUES (UUID(), :cliente_id, :token_hash, DATE_ADD(NOW(), INTERVAL 2 HOUR))
                ")->execute([
                    ':cliente_id' => $cliente['id'],
                    ':token_hash' => $tokenHash,
                ]);

                $link = APP_URL . 'portal/redefinir-senha?token=' . urlencode($token);
                $html = clientePortalTemplate('portal_recuperar_senha', [
                    '{{NOME_CLIENTE}}' => h($cliente['nome']),
                    '{{LINK_REDEFINICAO}}' => $link,
                    '{{EMAIL_CONTATO}}' => EMAIL_CONTATO,
                    '{{TELEFONE_CONTATO}}' => TELEFONE_CONTATO,
                ]);
                $resultado = enviarEmail($cliente['email'], $cliente['nome'], 'Redefinição de senha do Portal do Cliente', $html);
                $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                    VALUES (UUID(), :destinatario, :assunto, 'portal_recuperacao_senha', 'clientes', :referencia_id, :status, :erro, NULL)
                ")->execute([
                    ':destinatario' => $cliente['email'],
                    ':assunto' => 'Redefinição de senha do Portal do Cliente',
                    ':referencia_id' => $cliente['id'],
                    ':status' => $resultado['success'] ? 'enviado' : 'erro',
                    ':erro' => $resultado['success'] ? null : $resultado['message'],
                ]);
            }
        } catch (Exception $e) {
            error_log('Erro na recuperacao de senha do portal: ' . $e->getMessage());
        }
    }
    $enviado = true;
}

$titulo_page = 'Recuperar senha - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-auth">
    <div class="portal-auth-card">
        <h1>Recuperar senha</h1>
        <?php if ($enviado): ?>
            <div class="message success"><i class="fas fa-circle-check"></i><span>Se o e-mail estiver cadastrado, enviaremos um link de redefinição.</span></div>
            <a class="btn btn-secondary btn-full" href="<?php echo APP_URL; ?>portal/login">Voltar para o login</a>
        <?php else: ?>
            <p>Informe o e-mail cadastrado para receber o link de redefinição.</p>
            <form method="POST" class="form-padrao">
                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo h($email); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-envelope"></i> Enviar link</button>
            </form>
            <a class="portal-auth-link" href="<?php echo APP_URL; ?>portal/login">Voltar para o login</a>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
