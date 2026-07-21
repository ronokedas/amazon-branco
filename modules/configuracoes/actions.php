<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: actions.php - Salvar configurações do sistema
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('configuracoes');

$action = $_POST['action'] ?? '';

$redirect_to = $_POST['redirect_to'] ?? 'configuracoes';

if ($action !== 'salvar' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('error', 'Ação inválida.');
    redirecionar(APP_URL . $redirect_to);
}

// Verificar CSRF
if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
    setMensagem('error', 'Token de segurança inválido.');
    redirecionar(APP_URL . $redirect_to);
}

$configs = $_POST['cfg'] ?? [];

if (empty($configs)) {
    setMensagem('error', 'Nenhuma configuração enviada.');
    redirecionar(APP_URL . $redirect_to);
}

try {
    $descricoes = [
        'dados_teste_embarcacoes' => 'Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações',
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO configuracoes (chave, valor, descricao)
         VALUES (:chave, :valor, :descricao)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
    );

    foreach ($configs as $chave => $valor) {
        if (!array_key_exists($chave, $descricoes)) {
            continue;
        }
        $valor = trim((string)$valor);

        if ($chave === 'dados_teste_embarcacoes') {
            $valor = $valor === '1' ? '1' : '0';
        }

        $stmt->execute([
            ':valor' => $valor,
            ':chave' => $chave,
            ':descricao' => $descricoes[$chave],
        ]);
    }

    setMensagem('success', 'Configurações salvas com sucesso!');
} catch (Exception $e) {
    error_log('Erro ao salvar configurações: ' . $e->getMessage());
    setMensagem('error', 'Erro ao salvar configurações. Tente novamente.');
}

redirecionar(APP_URL . $redirect_to);
