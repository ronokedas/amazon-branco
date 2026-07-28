<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/assinaturas_usuarios.php';

verificar_sessao();

$json = ($_POST['action'] ?? '') === 'assinar';
if ($json) {
    header('Content-Type: application/json; charset=UTF-8');
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new RuntimeException('Método não permitido.');
    }
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        throw new RuntimeException('Sessão expirada. Atualize a página.');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'assinar') {
        $tipo = strtoupper(trim((string)($_POST['documento_tipo'] ?? '')));
        $resultado = $tipo === 'RELATORIO'
            ? assinaturaAssinarRelatorio($pdo, $_POST)
            : ($tipo === 'PARECER_PLANOS'
                ? assinaturaAssinarParecer($pdo, $_POST)
                : assinaturaAssinarCertificado($pdo, $_POST));

        if ($tipo === 'RELATORIO') {
            $mensagem = getCargo() === 'ADMIN'
                ? 'Assinatura do vistoriador aplicada pelo administrador. Relatório concluído e certificação liberada.'
                : 'Relatório aprovado assinado com sucesso. Certificação liberada.';
        } elseif ($tipo === 'PARECER_PLANOS') {
            $mensagem = 'Parecer assinado e publicado.';
        } else {
            $mensagem = 'Certificado assinado com sucesso.';
        }

        echo json_encode(
            ['success' => true, 'message' => $mensagem, 'data' => $resultado],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    if ($action === 'reenviar') {
        $tipo = strtoupper(trim((string)($_POST['tipo'] ?? '')));
        $id = trim((string)($_POST['id'] ?? ''));
        $mapa = assinaturaCertificadosMapas()[$tipo] ?? null;
        if (!$mapa) {
            throw new RuntimeException('Documento inválido.');
        }
        $stmt = $pdo->prepare("SELECT c.responsavel_assinatura_id,ra.usuario_id
            FROM {$mapa['table']} c
            JOIN responsaveis_assinatura ra ON ra.id=c.responsavel_assinatura_id
            WHERE c.id=?");
        $stmt->execute([$id]);
        $documento = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$documento
            || (getCargo() !== 'ADMIN'
                && $documento['usuario_id'] !== ($_SESSION['usuario_id'] ?? ''))) {
            throw new RuntimeException('Acesso negado.');
        }
        $resultado = assinaturaEnviarConviteCertificado($pdo, $tipo, $id);
        setMensagem(
            $resultado['success'] ? 'success' : 'warning',
            $resultado['message']
        );
        redirecionar(APP_URL . 'minhas-assinaturas');
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($json) {
        http_response_code(422);
        echo json_encode(
            ['success' => false, 'message' => $e->getMessage()],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    setMensagem('error', $e->getMessage());
    redirecionar(APP_URL . 'minhas-assinaturas');
}
