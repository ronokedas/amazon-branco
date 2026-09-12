<?php
/**
 * MÓDULO: CONFIGURAÇÕES - GERENCIADOR NORMAM-202
 * Arquivo: modules/configuracoes/normam202_actions.php
 * Controlador para CRUD, Toggles AJAX e Auditoria SGQ de Exigências NORMAM-202
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sgq.php';

verificar_sessao();
exigirAcesso('configuracoes');

$action = $_REQUEST['action'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// =========================================================================
// AÇÃO: OBTER DADOS DO ITEM EM JSON (PARA MODAL DE EDIÇÃO)
// =========================================================================
if ($action === 'obter') {
    header('Content-Type: application/json');
    $id = trim($_GET['id'] ?? '');
    if (!$id) {
        echo json_encode(['success' => false, 'mensagem' => 'ID inválido']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM exigencias_catalogo WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'mensagem' => 'Exigência não encontrada']);
        exit;
    }

    echo json_encode(['success' => true, 'dados' => $item]);
    exit;
}

// =========================================================================
// AÇÃO: TOGGLE AJAX OBRIGATÓRIA
// =========================================================================
if ($action === 'toggle_obrigatoria') {
    header('Content-Type: application/json');
    $id = trim($_POST['id'] ?? '');
    $valor = !empty($_POST['valor']) ? 1 : 0;

    if (!$id) {
        echo json_encode(['success' => false, 'mensagem' => 'ID não informado']);
        exit;
    }

    $stmtAntes = $pdo->prepare("SELECT id, codigo_interno, descricao, obrigatoria, exige_foto FROM exigencias_catalogo WHERE id = :id");
    $stmtAntes->execute([':id' => $id]);
    $antes = $stmtAntes->fetch(PDO::FETCH_ASSOC);

    if (!$antes) {
        echo json_encode(['success' => false, 'mensagem' => 'Exigência não encontrada']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET obrigatoria = :valor WHERE id = :id");
    $stmt->execute([':valor' => $valor, ':id' => $id]);

    $depois = $antes;
    $depois['obrigatoria'] = $valor;

    // Registrar no Histórico de Alterações Técnicas (Auditoria Naval)
    sgqRegistrarAuditoriaCadastral(
        $pdo,
        'exigencias_catalogo',
        $id,
        'ALTERACAO',
        $antes,
        $depois,
        'Alteração de status de obrigatoriedade da exigência NORMAM-202 (' . ($valor ? 'Ativado' : 'Desativado') . ')'
    );

    echo json_encode(['success' => true, 'obrigatoria' => $valor, 'codigo' => $antes['codigo_interno']]);
    exit;
}

// =========================================================================
// AÇÃO: TOGGLE AJAX EXIGE FOTO
// =========================================================================
if ($action === 'toggle_foto') {
    header('Content-Type: application/json');
    $id = trim($_POST['id'] ?? '');
    $valor = !empty($_POST['valor']) ? 1 : 0;

    if (!$id) {
        echo json_encode(['success' => false, 'mensagem' => 'ID não informado']);
        exit;
    }

    $stmtAntes = $pdo->prepare("SELECT id, codigo_interno, descricao, obrigatoria, exige_foto FROM exigencias_catalogo WHERE id = :id");
    $stmtAntes->execute([':id' => $id]);
    $antes = $stmtAntes->fetch(PDO::FETCH_ASSOC);

    if (!$antes) {
        echo json_encode(['success' => false, 'mensagem' => 'Exigência não encontrada']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET exige_foto = :valor WHERE id = :id");
    $stmt->execute([':valor' => $valor, ':id' => $id]);

    $depois = $antes;
    $depois['exige_foto'] = $valor;

    // Registrar no Histórico de Alterações Técnicas (Auditoria Naval)
    sgqRegistrarAuditoriaCadastral(
        $pdo,
        'exigencias_catalogo',
        $id,
        'ALTERACAO',
        $antes,
        $depois,
        'Alteração de exigência de foto comprobatória (' . ($valor ? 'Foto Obrigatória' : 'Foto Opcional') . ')'
    );

    echo json_encode(['success' => true, 'exige_foto' => $valor, 'codigo' => $antes['codigo_interno']]);
    exit;
}

// =========================================================================
// AÇÃO: TOGGLE AJAX ATIVO / INATIVO
// =========================================================================
if ($action === 'toggle_ativo') {
    header('Content-Type: application/json');
    $id = trim($_POST['id'] ?? '');
    $valor = !empty($_POST['valor']) ? 1 : 0;

    if (!$id) {
        echo json_encode(['success' => false, 'mensagem' => 'ID não informado']);
        exit;
    }

    $stmtAntes = $pdo->prepare("SELECT id, codigo_interno, descricao, ativo FROM exigencias_catalogo WHERE id = :id");
    $stmtAntes->execute([':id' => $id]);
    $antes = $stmtAntes->fetch(PDO::FETCH_ASSOC);

    if (!$antes) {
        echo json_encode(['success' => false, 'mensagem' => 'Exigência não encontrada']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET ativo = :valor WHERE id = :id");
    $stmt->execute([':valor' => $valor, ':id' => $id]);

    $depois = $antes;
    $depois['ativo'] = $valor;

    sgqRegistrarAuditoriaCadastral(
        $pdo,
        'exigencias_catalogo',
        $id,
        'ALTERACAO',
        $antes,
        $depois,
        'Alteração de ativação da exigência no checklist (' . ($valor ? 'Ativada' : 'Inativada') . ')'
    );

    echo json_encode(['success' => true, 'ativo' => $valor, 'codigo' => $antes['codigo_interno']]);
    exit;
}

// =========================================================================
// AÇÃO: SALVAR OU ATUALIZAR EXIGÊNCIA
// =========================================================================
if ($action === 'salvar') {
    validarCSRF();

    $id = trim($_POST['id'] ?? '');
    $codigo_interno = trim($_POST['codigo_interno'] ?? '');
    $categoria_id = trim($_POST['categoria_id'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $item_normam = trim($_POST['item_normam'] ?? '');
    $bloco_vistoria = trim($_POST['bloco_vistoria'] ?? 'flutuando');
    $prazo_padrao_dias = !empty($_POST['prazo_padrao_dias']) ? (int)$_POST['prazo_padrao_dias'] : 15;
    $obrigatoria = !empty($_POST['obrigatoria']) ? 1 : 0;
    $exige_foto = !empty($_POST['exige_foto']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

    $app_a = !empty($_POST['aplicabilidade_a']) ? 1 : 0;
    $app_b = !empty($_POST['aplicabilidade_b']) ? 1 : 0;
    $app_c = !empty($_POST['aplicabilidade_c']) ? 1 : 0;
    $app_d = !empty($_POST['aplicabilidade_d']) ? 1 : 0;
    $app_e = !empty($_POST['aplicabilidade_e']) ? 1 : 0;
    $app_f = !empty($_POST['aplicabilidade_f']) ? 1 : 0;

    if (empty($descricao)) {
        setMensagem('error', 'A descrição da exigência é obrigatória.');
        redirecionar(APP_URL . 'configuracoes/normam202');
    }

    if (empty($categoria_id)) {
        setMensagem('error', 'Selecione uma categoria válida para a exigência.');
        redirecionar(APP_URL . 'configuracoes/normam202');
    }

    // Auto-gerar código se não fornecido
    if (empty($codigo_interno)) {
        $stmtMax = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo_interno, 4) AS UNSIGNED)) FROM exigencias_catalogo WHERE codigo_interno LIKE 'EX-%'");
        $maxNum = (int)$stmtMax->fetchColumn();
        $codigo_interno = 'EX-' . ($maxNum > 0 ? $maxNum + 1 : 557);
    }

    try {
        if (!empty($id)) {
            // Edição de registro existente
            $stmtAntes = $pdo->prepare("SELECT * FROM exigencias_catalogo WHERE id = :id");
            $stmtAntes->execute([':id' => $id]);
            $antes = $stmtAntes->fetch(PDO::FETCH_ASSOC);

            if (!$antes) {
                setMensagem('error', 'Exigência não encontrada para edição.');
                redirecionar(APP_URL . 'configuracoes/normam202');
            }

            $stmtUpdate = $pdo->prepare("UPDATE exigencias_catalogo SET
                codigo_interno = :codigo_interno,
                categoria_id = :categoria_id,
                descricao = :descricao,
                item_normam = :item_normam,
                bloco_vistoria = :bloco_vistoria,
                tipo_vistoria = :tipo_vistoria,
                prazo_padrao_dias = :prazo_padrao_dias,
                obrigatoria = :obrigatoria,
                exige_foto = :exige_foto,
                ativo = :ativo,
                aplicabilidade_a = :app_a,
                aplicabilidade_b = :app_b,
                aplicabilidade_c = :app_c,
                aplicabilidade_d = :app_d,
                aplicabilidade_e = :app_e,
                aplicabilidade_f = :app_f
                WHERE id = :id");

            $stmtUpdate->execute([
                ':codigo_interno' => $codigo_interno,
                ':categoria_id' => $categoria_id,
                ':descricao' => $descricao,
                ':item_normam' => $item_normam,
                ':bloco_vistoria' => $bloco_vistoria,
                ':tipo_vistoria' => $bloco_vistoria,
                ':prazo_padrao_dias' => $prazo_padrao_dias,
                ':obrigatoria' => $obrigatoria,
                ':exige_foto' => $exige_foto,
                ':ativo' => $ativo,
                ':app_a' => $app_a,
                ':app_b' => $app_b,
                ':app_c' => $app_c,
                ':app_d' => $app_d,
                ':app_e' => $app_e,
                ':app_f' => $app_f,
                ':id' => $id
            ]);

            $stmtDepois = $pdo->prepare("SELECT * FROM exigencias_catalogo WHERE id = :id");
            $stmtDepois->execute([':id' => $id]);
            $depois = $stmtDepois->fetch(PDO::FETCH_ASSOC);

            sgqRegistrarAuditoriaCadastral(
                $pdo,
                'exigencias_catalogo',
                $id,
                'ALTERACAO',
                $antes,
                $depois,
                "Edição de dados da exigência {$codigo_interno} (NORMAM-202)"
            );

            setMensagem('success', "Exigência {$codigo_interno} atualizada com sucesso!");
        } else {
            // Criação de nova exigência
            $novoId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmtInsert = $pdo->prepare("INSERT INTO exigencias_catalogo (
                id, codigo_interno, categoria_id, descricao, item_normam, 
                bloco_vistoria, tipo_vistoria, prazo_padrao_dias, obrigatoria, exige_foto, ativo,
                aplicabilidade_a, aplicabilidade_b, aplicabilidade_c, aplicabilidade_d, aplicabilidade_e, aplicabilidade_f
            ) VALUES (
                :id, :codigo_interno, :categoria_id, :descricao, :item_normam,
                :bloco_vistoria, :tipo_vistoria, :prazo_padrao_dias, :obrigatoria, :exige_foto, :ativo,
                :app_a, :app_b, :app_c, :app_d, :app_e, :app_f
            )");

            $stmtInsert->execute([
                ':id' => $novoId,
                ':codigo_interno' => $codigo_interno,
                ':categoria_id' => $categoria_id,
                ':descricao' => $descricao,
                ':item_normam' => $item_normam,
                ':bloco_vistoria' => $bloco_vistoria,
                ':tipo_vistoria' => $bloco_vistoria,
                ':prazo_padrao_dias' => $prazo_padrao_dias,
                ':obrigatoria' => $obrigatoria,
                ':exige_foto' => $exige_foto,
                ':ativo' => $ativo,
                ':app_a' => $app_a,
                ':app_b' => $app_b,
                ':app_c' => $app_c,
                ':app_d' => $app_d,
                ':app_e' => $app_e,
                ':app_f' => $app_f
            ]);

            $stmtCriado = $pdo->prepare("SELECT * FROM exigencias_catalogo WHERE id = :id");
            $stmtCriado->execute([':id' => $novoId]);
            $criado = $stmtCriado->fetch(PDO::FETCH_ASSOC);

            sgqRegistrarAuditoriaCadastral(
                $pdo,
                'exigencias_catalogo',
                $novoId,
                'CRIACAO',
                null,
                $criado,
                "Inclusão de nova exigência {$codigo_interno} no catálogo NORMAM-202"
            );

            setMensagem('success', "Nova exigência {$codigo_interno} cadastrada com sucesso!");
        }
    } catch (Exception $e) {
        error_log('Erro ao salvar exigencia NORMAM-202: ' . $e->getMessage());
        setMensagem('error', 'Erro ao gravar exigência no banco de dados: ' . $e->getMessage());
    }

    redirecionar(APP_URL . 'configuracoes/normam202');
}

// =========================================================================
// AÇÃO: INATIVAR EXIGÊNCIA (EXCLUSÃO LÓGICA / SOFT DELETE)
// =========================================================================
if ($action === 'inativar') {
    validarCSRF();
    $id = trim($_POST['id'] ?? '');
    $motivo = trim($_POST['motivo'] ?? 'Inativação administrativa via configurações');

    if (!$id) {
        setMensagem('error', 'Identificador da exigência não fornecido.');
        redirecionar(APP_URL . 'configuracoes/normam202');
    }

    $stmtAntes = $pdo->prepare("SELECT * FROM exigencias_catalogo WHERE id = :id");
    $stmtAntes->execute([':id' => $id]);
    $antes = $stmtAntes->fetch(PDO::FETCH_ASSOC);

    if (!$antes) {
        setMensagem('error', 'Exigência não encontrada.');
        redirecionar(APP_URL . 'configuracoes/normam202');
    }

    $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET ativo = 0 WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $depois = $antes;
    $depois['ativo'] = 0;

    sgqRegistrarAuditoriaCadastral(
        $pdo,
        'exigencias_catalogo',
        $id,
        'INATIVACAO',
        $antes,
        $depois,
        "Inativação da exigência {$antes['codigo_interno']}: {$motivo}"
    );

    setMensagem('success', "Exigência {$antes['codigo_interno']} inativada com sucesso.");
    redirecionar(APP_URL . 'configuracoes/normam202');
}

// Fallback: Redirecionar para tela principal
redirecionar(APP_URL . 'configuracoes/normam202');
