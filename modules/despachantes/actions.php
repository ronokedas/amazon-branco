<?php
/**
 * MODULO: CLIENTES
 * Arquivo: actions.php - Processar POST (insert/update/delete)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cliente_vinculos.php';

verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

// Validar CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'despachantes');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function salvarTiposEmbarcacaoDespachante(PDO $pdo, string $cliente_id, array $tipos_ids): void
{
    $tipos_ids = array_values(array_unique(array_filter(array_map('trim', $tipos_ids))));

    $stmtDel = $pdo->prepare("DELETE FROM clientes_tipos_embarcacao WHERE cliente_id = :cliente_id");
    $stmtDel->execute([':cliente_id' => $cliente_id]);

    if (empty($tipos_ids)) {
        return;
    }

    $stmtValidar = $pdo->prepare("SELECT id FROM tipos_embarcacao WHERE id = :id AND ativo = 1");
    $stmtIns = $pdo->prepare("
        INSERT INTO clientes_tipos_embarcacao (cliente_id, tipo_embarcacao_id)
        VALUES (:cliente_id, :tipo_embarcacao_id)
    ");

    foreach ($tipos_ids as $tipo_id) {
        $stmtValidar->execute([':id' => $tipo_id]);
        if (!$stmtValidar->fetchColumn()) {
            continue;
        }

        $stmtIns->execute([
            ':cliente_id' => $cliente_id,
            ':tipo_embarcacao_id' => $tipo_id,
        ]);
    }
}

switch ($action) {

    case 'inserir':
        try {
            $nome       = sanitizar($_POST['nome'] ?? '');
            $tipo_pessoa = $_POST['tipo_pessoa'] ?? 'PF';
            $cpf_cnpj   = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $perfil     = $_POST['perfil'] ?? 'proprietario';
            $telefone   = sanitizar($_POST['telefone'] ?? '');
            $email      = sanitizar($_POST['email'] ?? '');
            $endereco   = sanitizar($_POST['endereco'] ?? '');
            
            $tipo_recebimento = $_POST['tipo_recebimento'] ?? null;
            $chave_pix        = sanitizar($_POST['chave_pix'] ?? '');
            $banco            = sanitizar($_POST['banco'] ?? '');
            $agencia          = sanitizar($_POST['agencia'] ?? '');
            $conta            = sanitizar($_POST['conta'] ?? '');
            $tipos_embarcacao = $_POST['tipos_embarcacao'] ?? [];
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($nome)) {
                setMensagem('error', 'O nome do despachante é obrigatório.', [
                    'nome' => 'Informe o nome do despachante.',
                ]);
                redirecionar(APP_URL . 'despachantes/form');
            }

            if (!in_array($perfil, ['armador', 'proprietario', 'despachante'])) {
                setMensagem('error', 'Perfil inválido.');
                redirecionar(APP_URL . 'despachantes/form');
            }

            // Validar CPF ou CNPJ se informado
            if (!empty($cpf_cnpj)) {
                if ($tipo_pessoa === 'PF') {
                    if (!validarCPF($cpf_cnpj)) {
                        setMensagem('error', 'CPF inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CPF valido.',
                        ]);
                        redirecionar(APP_URL . 'despachantes/form');
                    }
                } else {
                    if (!validarCNPJ($cpf_cnpj)) {
                        setMensagem('error', 'CNPJ inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CNPJ valido.',
                        ]);
                        redirecionar(APP_URL . 'despachantes/form');
                    }
                }
            }

            $pdo->beginTransaction();

            $cliente_id = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, tipo_recebimento, chave_pix, banco, agencia, conta, criado_por)
                VALUES (:id, :nome, :tipo_pessoa, :cpf_cnpj, :perfil, :telefone, :email, :endereco, :tipo_recebimento, :chave_pix, :banco, :agencia, :conta, :criado_por)
            ");
            $stmt->execute([
                ':id'         => $cliente_id,
                ':nome'       => $nome,
                ':tipo_pessoa' => $tipo_pessoa,
                ':cpf_cnpj'   => $cpf_cnpj ?: null,
                ':perfil'     => $perfil,
                ':telefone'   => $telefone ?: null,
                ':email'      => $email ?: null,
                ':endereco'   => $endereco ?: null,
                ':tipo_recebimento' => $tipo_recebimento ?: null,
                ':chave_pix'  => $chave_pix ?: null,
                ':banco'      => $banco ?: null,
                ':agencia'    => $agencia ?: null,
                ':conta'      => $conta ?: null,
                ':criado_por' => $_SESSION['usuario_id'],
            ]);

            salvarTiposEmbarcacaoDespachante($pdo, $cliente_id, is_array($tipos_embarcacao) ? $tipos_embarcacao : []);
            sincronizarClienteEmbarcacoes($pdo, $cliente_id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);

            $pdo->commit();

            log_atividade('despachante_criado', "Despachante '{$nome}' criado.");
            setMensagem('success', 'Despachante cadastrado com sucesso!');
            redirecionar(APP_URL . 'despachantes');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir despachante: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado no sistema.', [
                    'cpf_cnpj' => 'Este CPF/CNPJ ja esta cadastrado.',
                ]);
            } else {
                setMensagem('error', 'Erro ao cadastrar despachante.');
            }
            redirecionar(APP_URL . 'despachantes/form');
        }
        break;

    case 'editar':
        try {
            $id         = $_POST['id'] ?? '';
            $nome       = sanitizar($_POST['nome'] ?? '');
            $tipo_pessoa = $_POST['tipo_pessoa'] ?? 'PF';
            $cpf_cnpj   = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $perfil     = $_POST['perfil'] ?? 'proprietario';
            $telefone   = sanitizar($_POST['telefone'] ?? '');
            $email      = sanitizar($_POST['email'] ?? '');
            $endereco   = sanitizar($_POST['endereco'] ?? '');
            
            $tipo_recebimento = $_POST['tipo_recebimento'] ?? null;
            $chave_pix        = sanitizar($_POST['chave_pix'] ?? '');
            $banco            = sanitizar($_POST['banco'] ?? '');
            $agencia          = sanitizar($_POST['agencia'] ?? '');
            $conta            = sanitizar($_POST['conta'] ?? '');
            $tipos_embarcacao = $_POST['tipos_embarcacao'] ?? [];
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($id) || empty($nome)) {
                setMensagem('error', 'Dados inválidos.');
                redirecionar(APP_URL . 'despachantes');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE clientes 
                SET nome = :nome,
                    tipo_pessoa = :tipo_pessoa,
                    cpf_cnpj = :cpf_cnpj,
                    perfil = :perfil,
                    telefone = :telefone,
                    email = :email,
                    endereco = :endereco,
                    tipo_recebimento = :tipo_recebimento,
                    chave_pix = :chave_pix,
                    banco = :banco,
                    agencia = :agencia,
                    conta = :conta
                WHERE id = :id AND status = 'ATIVO'
            ");
            $stmt->execute([
                ':nome'       => $nome,
                ':tipo_pessoa' => $tipo_pessoa,
                ':cpf_cnpj'   => $cpf_cnpj ?: null,
                ':perfil'     => $perfil,
                ':telefone'   => $telefone ?: null,
                ':email'      => $email ?: null,
                ':endereco'   => $endereco ?: null,
                ':tipo_recebimento' => $tipo_recebimento ?: null,
                ':chave_pix'  => $chave_pix ?: null,
                ':banco'      => $banco ?: null,
                ':agencia'    => $agencia ?: null,
                ':conta'      => $conta ?: null,
                ':id'         => $id,
            ]);

            salvarTiposEmbarcacaoDespachante($pdo, $id, is_array($tipos_embarcacao) ? $tipos_embarcacao : []);
            sincronizarClienteEmbarcacoes($pdo, $id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);
            sincronizarLoginPortalCliente($pdo, $id);

            $pdo->commit();

            log_atividade('despachante_editado', "Despachante '{$nome}' editado.");
            setMensagem('success', 'Despachante atualizado com sucesso!');
            redirecionar(APP_URL . 'despachantes');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao editar despachante: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado em outro despachante.', [
                    'cpf_cnpj' => 'Este CPF/CNPJ pertence a outro cadastro.',
                ]);
            } else {
                setMensagem('error', 'Erro ao atualizar despachante.');
            }
            redirecionar(APP_URL . 'despachantes/form?id=' . urlencode($id));
        }
        break;

    case 'desativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do despachante não informado.');
                redirecionar(APP_URL . 'despachantes');
            }

            $stmt = $pdo->prepare("UPDATE clientes SET status = 'INATIVO' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_atividade('despachante_desativado', "Despachante ID: {$id} desativado.");
            setMensagem('success', 'Despachante desativado com sucesso!');
            redirecionar(APP_URL . 'despachantes');

        } catch (Exception $e) {
            error_log('Erro ao desativar despachante: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar despachante.');
            redirecionar(APP_URL . 'despachantes');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'despachantes');
        break;
}
