<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/mailer.php';

verificar_sessao();
exigirAcesso('portal_clientes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
    setMensagem('error', 'Token de segurança inválido.');
    redirecionar(APP_URL . 'portal-clientes');
}

$action = $_POST['action'] ?? '';

if ($action === 'enviar_acesso') {
    $clienteId = trim($_POST['cliente_id'] ?? '');
    $senha = trim($_POST['senha_temporaria'] ?? '');

    if ($clienteId === '' || strlen($senha) < 8) {
        setMensagem('error', 'Informe o cliente e uma senha temporária com pelo menos 8 caracteres.');
        redirecionar(APP_URL . 'portal-clientes');
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, perfil FROM clientes WHERE id = :id AND perfil IN ('proprietario','despachante') AND status = 'ATIVO' LIMIT 1");
        $stmt->execute([':id' => $clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente || empty($cliente['email'])) {
            setMensagem('error', 'Cliente não encontrado ou sem e-mail cadastrado.');
            redirecionar(APP_URL . 'portal-clientes');
        }
        $login = strtolower(trim($cliente['email']));
        $stmtLogin = $pdo->prepare('SELECT cliente_id FROM cliente_portal_acessos WHERE login = :login AND cliente_id <> :cliente LIMIT 1');
        $stmtLogin->execute([':login' => $login, ':cliente' => $clienteId]);
        if ($stmtLogin->fetchColumn()) {
            setMensagem('error', 'Este e-mail já é usado como login por outro acesso do portal.');
            redirecionar(APP_URL . 'portal-clientes?id=' . urlencode($clienteId));
        }

        $pdo->prepare("
            INSERT INTO cliente_portal_acessos (cliente_id, login, senha_hash, ativo, forcar_troca_senha, criado_por)
            VALUES (:cliente_id, :login, :senha_hash, 1, 1, :criado_por)
            ON DUPLICATE KEY UPDATE
                senha_hash = VALUES(senha_hash),
                login = VALUES(login),
                ativo = 1,
                forcar_troca_senha = 1,
                criado_por = VALUES(criado_por)
        ")->execute([
            ':cliente_id' => $clienteId,
            ':login' => $login,
            ':senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            ':criado_por' => $_SESSION['usuario_id'] ?? null,
        ]);

        $html = clientePortalTemplate('portal_acesso', [
            '{{NOME_CLIENTE}}' => h($cliente['nome']),
            '{{LINK_PORTAL}}' => APP_URL . 'portal/login',
            '{{EMAIL_CLIENTE}}' => h($cliente['email']),
            '{{SENHA_TEMPORARIA}}' => h($senha),
            '{{EMAIL_CONTATO}}' => EMAIL_CONTATO,
            '{{TELEFONE_CONTATO}}' => TELEFONE_CONTATO,
        ]);

        $assunto = 'Acesso ao Portal do Cliente';
        $resultado = enviarEmail($cliente['email'], $cliente['nome'], $assunto, $html);

        $pdo->prepare("
            INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
            VALUES (UUID(), :destinatario, :assunto, 'portal_acesso', 'clientes', :referencia_id, :status, :erro, :enviado_por)
        ")->execute([
            ':destinatario' => $cliente['email'],
            ':assunto' => $assunto,
            ':referencia_id' => $clienteId,
            ':status' => $resultado['success'] ? 'enviado' : 'erro',
            ':erro' => $resultado['success'] ? null : $resultado['message'],
            ':enviado_por' => $_SESSION['usuario_id'] ?? null,
        ]);

        if ($resultado['success']) {
            setMensagem('success', 'Acesso criado e enviado para o cliente.');
        } else {
            setMensagem('warning', 'Acesso criado, mas o e-mail não foi enviado: ' . $resultado['message']);
        }
        redirecionar(APP_URL . 'portal-clientes?id=' . urlencode($clienteId));
    } catch (Exception $e) {
        error_log('Erro ao enviar acesso do portal: ' . $e->getMessage());
        setMensagem('error', 'Erro ao criar ou enviar acesso ao portal.');
        redirecionar(APP_URL . 'portal-clientes?id=' . urlencode($clienteId));
    }
}

setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'portal-clientes');
