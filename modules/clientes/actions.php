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
        redirecionar(APP_URL . 'clientes');
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
            $email      = sanitizar($_POST['email'] ?? '');
            $endereco   = sanitizar($_POST['endereco'] ?? '');
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($nome)) {
                setMensagem('error', 'O nome do cliente é obrigatório.');
                redirecionar(APP_URL . 'clientes/form');
            }

            if (!in_array($perfil, ['armador', 'proprietario', 'despachante'])) {
                setMensagem('error', 'Perfil inválido.');
                redirecionar(APP_URL . 'clientes/form');
            }

            // Validar CPF ou CNPJ se informado
            if (!empty($cpf_cnpj)) {
                if ($tipo_pessoa === 'PF') {
                    if (!validarCPF($cpf_cnpj)) {
                        setMensagem('error', 'CPF inválido. Verifique os dígitos.');
                        redirecionar(APP_URL . 'clientes/form');
                    }
                } else {
                    if (!validarCNPJ($cpf_cnpj)) {
                        setMensagem('error', 'CNPJ inválido. Verifique os dígitos.');
                        redirecionar(APP_URL . 'clientes/form');
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

            $pdo->commit();

            log_atividade('cliente_criado', "Cliente '{$nome}' criado.");
            setMensagem('success', 'Cliente cadastrado com sucesso!');
            redirecionar(APP_URL . 'clientes');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir cliente: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado no sistema.');
            } else {
                setMensagem('error', 'Erro ao cadastrar cliente.');
            }
            redirecionar(APP_URL . 'clientes/form');
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
            $embarcacoes_ids = $_POST['embarcacoes_ids'] ?? [];

            if (empty($id) || empty($nome)) {
                setMensagem('error', 'Dados inválidos.');
                redirecionar(APP_URL . 'clientes');
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

            sincronizarClienteEmbarcacoes($pdo, $id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);
            sincronizarLoginPortalCliente($pdo, $id);

            $pdo->commit();

            log_atividade('cliente_editado', "Cliente '{$nome}' editado.");
            setMensagem('success', 'Cliente atualizado com sucesso!');
            redirecionar(APP_URL . 'clientes');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao editar cliente: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado em outro cliente.');
            } else {
                setMensagem('error', 'Erro ao atualizar cliente.');
            }
            redirecionar(APP_URL . 'clientes/form?id=' . urlencode($id));
        }
        break;

    case 'desativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do cliente não informado.');
                redirecionar(APP_URL . 'clientes');
            }

            $stmt = $pdo->prepare("UPDATE clientes SET status = 'INATIVO' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_atividade('cliente_desativado', "Cliente ID: {$id} desativado.");
            setMensagem('success', 'Cliente desativado com sucesso!');
            redirecionar(APP_URL . 'clientes');

        } catch (Exception $e) {
            error_log('Erro ao desativar cliente: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar cliente.');
            redirecionar(APP_URL . 'clientes');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'clientes');
        break;
}
