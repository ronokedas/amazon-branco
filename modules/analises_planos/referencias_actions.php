<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

verificar_sessao();
exigirAcesso('analise_planos');

$cargo = getCargo();
if (!in_array($cargo, ['ANALISTA', 'ADMIN'], true)) {
    http_response_code(403);
    die('Acesso permitido exclusivamente ao Analista Naval e Administrador.');
}

$acao = $_POST['action'] ?? $_GET['action'] ?? '';
$usuarioId = (string)($_SESSION['usuario_id'] ?? '');

// Endpoint AJAX para busca dinâmica
if ($acao === 'buscar_ajax') {
    header('Content-Type: application/json; charset=utf-8');
    $categoria = trim($_GET['categoria'] ?? '');
    $busca = trim($_GET['busca'] ?? '');
    
    $refs = analisePlanosBuscarReferenciasNormam($pdo, [
        'categoria' => $categoria,
        'busca' => $busca,
    ]);
    
    echo json_encode(['sucesso' => true, 'dados' => $refs], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
    setMensagem('error', 'Sessão ou token de segurança inválido.');
    redirecionar(APP_URL . 'analises-planos/referencias');
}

try {
    if ($acao === 'salvar') {
        $id = analisePlanosSalvarReferenciaNormam($pdo, [
            'id' => $_POST['id'] ?? '',
            'categoria' => $_POST['categoria'] ?? 'GERAL',
            'norma' => $_POST['norma'] ?? 'NORMAM-202',
            'item_norma' => $_POST['item_norma'] ?? '',
            'referencia_normativa' => $_POST['referencia_normativa'] ?? '',
            'titulo' => $_POST['titulo'] ?? '',
            'descricao_padrao' => $_POST['descricao_padrao'] ?? '',
            'ordem' => (int)($_POST['ordem'] ?? 0),
        ], $usuarioId);
        
        setMensagem('success', 'Referência normativa da NORMAM salva com sucesso.');
        redirecionar(APP_URL . 'analises-planos/referencias');
    }

    if ($acao === 'excluir') {
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            throw new InvalidArgumentException('ID inválido para exclusão.');
        }
        analisePlanosExcluirReferenciaNormam($pdo, $id);
        setMensagem('success', 'Referência normativa excluída com sucesso.');
        redirecionar(APP_URL . 'analises-planos/referencias');
    }

    throw new InvalidArgumentException('Ação não reconhecida.');
} catch (Throwable $e) {
    setMensagem('error', 'Erro: ' . $e->getMessage());
    redirecionar(APP_URL . 'analises-planos/referencias');
}
