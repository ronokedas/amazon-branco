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
exigirAcesso('proprietarios');

// Validar CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'proprietarios');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'inserir':
        try {
            $nome       = sanitizar($_POST['nome'] ?? '');
            $tipo_pessoa = $_POST['tipo_pessoa'] ?? 'PF';
            $cpf_cnpj   = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $perfil     = $_POST['perfil'] ?? 'proprietario';
            $telefone   = sanitizar($_POST['telefone'] ?? '');
            $email      = strtolower(trim($_POST['email'] ?? ''));
            $endereco   = sanitizar($_POST['endereco'] ?? '');
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($nome)) {
                setMensagem('error', 'O nome do proprietario é obrigatório.', [
                    'nome' => 'Informe o nome do proprietario.',
                ]);
                redirecionar(APP_URL . 'proprietarios/form');
            }

            if ($perfil === 'proprietario' && empty($email)) {
                setMensagem('error', 'O email do proprietário é obrigatório para futuro acesso ao portal.', [
                    'email' => 'Informe o e-mail do proprietario.',
                ]);
                redirecionar(APP_URL . 'proprietarios/form');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setMensagem('error', 'Informe um endereço de e-mail válido.', [
                    'email' => 'Use o formato nome@empresa.com.br.',
                ]);
                redirecionar(APP_URL . 'proprietarios/form');
            }

            if (!in_array($perfil, ['armador', 'proprietario', 'despachante'])) {
                setMensagem('error', 'Perfil inválido.');
                redirecionar(APP_URL . 'proprietarios/form');
            }

            // Validar CPF ou CNPJ se informado
            if (!empty($cpf_cnpj)) {
                if ($tipo_pessoa === 'PF') {
                    if (!validarCPF($cpf_cnpj)) {
                        setMensagem('error', 'CPF inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CPF valido.',
                        ]);
                        redirecionar(APP_URL . 'proprietarios/form');
                    }
                } else {
                    if (!validarCNPJ($cpf_cnpj)) {
                        setMensagem('error', 'CNPJ inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CNPJ valido.',
                        ]);
                        redirecionar(APP_URL . 'proprietarios/form');
                    }
                }
            }

            $pdo->beginTransaction();

            $cliente_id = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, criado_por)
                VALUES (:id, :nome, :tipo_pessoa, :cpf_cnpj, :perfil, :telefone, :email, :endereco, :criado_por)
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
                ':criado_por' => $_SESSION['usuario_id'],
            ]);

            sincronizarClienteEmbarcacoes($pdo, $cliente_id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);
            // Atualizar o proprietario estruturado da embarcacao.
            if (!empty($embarcacoes_ids)) {
                $stmtUpdEmb = $pdo->prepare("
                    UPDATE embarcacoes 
                    SET proprietario_id = :cliente_id, proprietario = :nome 
                    WHERE id = :embarcacao_id
                ");
                foreach ($embarcacoes_ids as $emb_id) {
                    $stmtUpdEmb->execute([
                        ':cliente_id'    => $cliente_id,
                        ':nome'          => $nome,
                        ':embarcacao_id' => $emb_id,
                    ]);
                }
            }

            $pdo->commit();

            log_atividade('proprietario_criado', "Proprietário '{$nome}' criado.");
            setMensagem('success', 'Proprietário cadastrado com sucesso!');
            redirecionar(APP_URL . 'proprietarios');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir proprietario: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado no sistema.', [
                    'cpf_cnpj' => 'Este CPF/CNPJ ja esta cadastrado.',
                ]);
            } else {
                setMensagem('error', 'Erro ao cadastrar proprietario.');
            }
            redirecionar(APP_URL . 'proprietarios/form');
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
            $email      = strtolower(trim($_POST['email'] ?? ''));
            $endereco   = sanitizar($_POST['endereco'] ?? '');
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($id) || empty($nome)) {
                setMensagem('error', 'Dados inválidos.');
                redirecionar(APP_URL . 'proprietarios');
            }

            if ($perfil === 'proprietario' && empty($email)) {
                setMensagem('error', 'O email do proprietário é obrigatório para futuro acesso ao portal.');
                redirecionar(APP_URL . 'proprietarios/form?id=' . urlencode($id));
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setMensagem('error', 'Informe um endereço de e-mail válido.', [
                    'email' => 'Use o formato nome@empresa.com.br.',
                ]);
                redirecionar(APP_URL . 'proprietarios/form?id=' . urlencode($id));
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
                    endereco = :endereco
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
                ':id'         => $id,
            ]);

            // Limpar vinculo estruturado em embarcacoes (aquelas que eram deste proprietario e nao foram marcadas)
            $placeholders = '';
            $paramsClear = [':id' => $id];
            if (!empty($embarcacoes_ids)) {
                $in = [];
                foreach ($embarcacoes_ids as $index => $emb_id) {
                    $key = ':emb_' . $index;
                    $in[] = $key;
                    $paramsClear[$key] = $emb_id;
                }
                $placeholders = " AND id NOT IN (" . implode(',', $in) . ")";
            }
            $stmtClearEmb = $pdo->prepare("UPDATE embarcacoes SET proprietario_id = NULL, proprietario = NULL WHERE proprietario_id = :id" . $placeholders);
            $stmtClearEmb->execute($paramsClear);

            sincronizarClienteEmbarcacoes($pdo, $id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);
            sincronizarLoginPortalCliente($pdo, $id);
            if (!empty($embarcacoes_ids)) {
                $stmtUpdEmb = $pdo->prepare("
                    UPDATE embarcacoes 
                    SET proprietario_id = :cliente_id, proprietario = :nome 
                    WHERE id = :embarcacao_id
                ");
                foreach ($embarcacoes_ids as $emb_id) {
                    $stmtUpdEmb->execute([
                        ':cliente_id'    => $id,
                        ':nome'          => $nome,
                        ':embarcacao_id' => $emb_id,
                    ]);
                }
            }

            $pdo->commit();

            log_atividade('proprietario_editado', "Proprietário '{$nome}' editado.");
            setMensagem('success', 'Proprietário atualizado com sucesso!');
            redirecionar(APP_URL . 'proprietarios');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao editar proprietario: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado em outro proprietario.', [
                    'cpf_cnpj' => 'Este CPF/CNPJ pertence a outro cadastro.',
                ]);
            } else {
                setMensagem('error', 'Erro ao atualizar proprietario.');
            }
            redirecionar(APP_URL . 'proprietarios/form?id=' . urlencode($id));
        }
        break;

    case 'desativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do proprietario não informado.');
                redirecionar(APP_URL . 'proprietarios');
            }

            $stmt = $pdo->prepare("UPDATE clientes SET status = 'INATIVO' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_atividade('proprietario_desativado', "Proprietário ID: {$id} desativado.");
            setMensagem('success', 'Proprietário desativado com sucesso!');
            redirecionar(APP_URL . 'proprietarios');

        } catch (Exception $e) {
            error_log('Erro ao desativar proprietario: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar proprietario.');
            redirecionar(APP_URL . 'proprietarios');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'proprietarios');
        break;
}
