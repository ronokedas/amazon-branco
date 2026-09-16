<?php
/**
 * MODULO: CLIENTES
 * Arquivo: actions.php - Processar ações cadastrais unificadas (inserir, editar, desativar)
 * Suporta todos os perfis navais: Armador, Proprietário e Despachante
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cliente_vinculos.php';

verificar_sessao();
exigirAcesso('clientes');

// Validar CSRF token em requisicoes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'clientes');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Salva os tipos de embarcacao atendidos pelo despachante
 */
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
            $nome             = sanitizar($_POST['nome'] ?? '');
            $tipo_pessoa      = $_POST['tipo_pessoa'] ?? 'PF';
            $cpf_cnpj         = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $perfil           = $_POST['perfil'] ?? 'proprietario';
            $telefone         = sanitizar($_POST['telefone'] ?? '');
            $email            = sanitizar($_POST['email'] ?? '');
            $endereco         = sanitizar($_POST['endereco'] ?? '');
            
            // Dados financeiros (dados bancários / PIX)
            $tipo_recebimento = $_POST['tipo_recebimento'] ?? null;
            $chave_pix        = sanitizar($_POST['chave_pix'] ?? '');
            $banco            = sanitizar($_POST['banco'] ?? '');
            $agencia          = sanitizar($_POST['agencia'] ?? '');
            $conta            = sanitizar($_POST['conta'] ?? '');
            
            // Vínculos
            $tipos_embarcacao = $_POST['tipos_embarcacao'] ?? [];
            $embarcacoes_ids  = $_POST['embarcacoes_ids'] ?? [];

            if (empty($nome)) {
                setMensagem('error', 'O nome / razão social é obrigatório.', [
                    'nome' => 'Informe o nome do cliente.',
                ]);
                redirecionar(APP_URL . 'clientes/form?perfil=' . urlencode($perfil));
            }

            if (!in_array($perfil, ['armador', 'proprietario', 'despachante'], true)) {
                setMensagem('error', 'Perfil selecionado é inválido.');
                redirecionar(APP_URL . 'clientes/form');
            }

            // Validar CPF ou CNPJ se informado
            if (!empty($cpf_cnpj)) {
                if ($tipo_pessoa === 'PF') {
                    if (!validarCPF($cpf_cnpj)) {
                        setMensagem('error', 'CPF inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CPF válido.',
                        ]);
                        redirecionar(APP_URL . 'clientes/form?perfil=' . urlencode($perfil));
                    }
                } else {
                    if (!validarCNPJ($cpf_cnpj)) {
                        setMensagem('error', 'CNPJ inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CNPJ válido.',
                        ]);
                        redirecionar(APP_URL . 'clientes/form?perfil=' . urlencode($perfil));
                    }
                }
            }

            $pdo->beginTransaction();

            $cliente_id = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO clientes (
                    id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco,
                    tipo_recebimento, chave_pix, banco, agencia, conta, ativo, criado_por
                ) VALUES (
                    :id, :nome, :tipo_pessoa, :cpf_cnpj, :perfil, :telefone, :email, :endereco,
                    :tipo_recebimento, :chave_pix, :banco, :agencia, :conta, 1, :criado_por
                )
            ");
            $stmt->execute([
                ':id'               => $cliente_id,
                ':nome'             => $nome,
                ':tipo_pessoa'      => $tipo_pessoa,
                ':cpf_cnpj'         => $cpf_cnpj ?: null,
                ':perfil'           => $perfil,
                ':telefone'         => $telefone ?: null,
                ':email'            => $email ?: null,
                ':endereco'         => $endereco ?: null,
                ':tipo_recebimento' => in_array($tipo_recebimento, ['pix', 'cc'], true) ? $tipo_recebimento : null,
                ':chave_pix'        => $chave_pix ?: null,
                ':banco'            => $banco ?: null,
                ':agencia'          => $agencia ?: null,
                ':conta'            => $conta ?: null,
                ':criado_por'       => $_SESSION['usuario_id'] ?? null,
            ]);

            // Salvar vínculos de embarcações
            sincronizarClienteEmbarcacoes($pdo, $cliente_id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);

            // Salvar tipos de embarcação atendidos (específico de despachante)
            if ($perfil === 'despachante' || !empty($tipos_embarcacao)) {
                salvarTiposEmbarcacaoDespachante($pdo, $cliente_id, is_array($tipos_embarcacao) ? $tipos_embarcacao : []);
            }

            // Auditoria cadastral do SGQ
            if (function_exists('sgqRegistrarAuditoriaCadastral')) {
                sgqRegistrarAuditoriaCadastral($pdo, 'CLIENTE', $cliente_id, 'CRIACAO', null, [
                    'nome' => $nome, 'tipo_pessoa' => $tipo_pessoa, 'cpf_cnpj' => $cpf_cnpj,
                    'perfil' => $perfil, 'telefone' => $telefone, 'email' => $email, 'endereco' => $endereco
                ], "Cadastro de {$perfil} no sistema");
            }

            $pdo->commit();

            log_atividade('cliente_criado', "Cliente/Ator Naval '{$nome}' ({$perfil}) criado.");
            setMensagem('success', ucfirst($perfil) . ' cadastrado com sucesso!');
            redirecionar(APP_URL . 'clientes?perfil=' . urlencode($perfil));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir cliente: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado no sistema.', [
                    'cpf_cnpj' => 'Este CPF/CNPJ já está cadastrado.',
                ]);
            } else {
                setMensagem('error', 'Erro ao cadastrar cliente.');
            }
            $perfilParam = isset($perfil) ? '?perfil=' . urlencode($perfil) : '';
            redirecionar(APP_URL . 'clientes/form' . $perfilParam);
        }
        break;

    case 'editar':
        try {
            $id               = $_POST['id'] ?? '';
            $nome             = sanitizar($_POST['nome'] ?? '');
            $tipo_pessoa      = $_POST['tipo_pessoa'] ?? 'PF';
            $cpf_cnpj         = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $perfil           = $_POST['perfil'] ?? 'proprietario';
            $telefone         = sanitizar($_POST['telefone'] ?? '');
            $email            = sanitizar($_POST['email'] ?? '');
            $endereco         = sanitizar($_POST['endereco'] ?? '');
            
            // Dados financeiros
            $tipo_recebimento = $_POST['tipo_recebimento'] ?? null;
            $chave_pix        = sanitizar($_POST['chave_pix'] ?? '');
            $banco            = sanitizar($_POST['banco'] ?? '');
            $agencia          = sanitizar($_POST['agencia'] ?? '');
            $conta            = sanitizar($_POST['conta'] ?? '');
            
            // Vínculos
            $tipos_embarcacao = $_POST['tipos_embarcacao'] ?? [];
            $embarcacoes_ids  = $_POST['embarcacoes_ids'] ?? [];

            if (empty($id) || empty($nome)) {
                setMensagem('error', 'Dados inválidos para atualização.');
                redirecionar(APP_URL . 'clientes');
            }

            if (!in_array($perfil, ['armador', 'proprietario', 'despachante'], true)) {
                setMensagem('error', 'Perfil selecionado é inválido.');
                redirecionar(APP_URL . 'clientes/form?id=' . urlencode($id));
            }

            // Validar CPF ou CNPJ se informado
            if (!empty($cpf_cnpj)) {
                if ($tipo_pessoa === 'PF') {
                    if (!validarCPF($cpf_cnpj)) {
                        setMensagem('error', 'CPF inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CPF válido.',
                        ]);
                        redirecionar(APP_URL . 'clientes/form?id=' . urlencode($id));
                    }
                } else {
                    if (!validarCNPJ($cpf_cnpj)) {
                        setMensagem('error', 'CNPJ inválido. Verifique os dígitos.', [
                            'cpf_cnpj' => 'Informe um CNPJ válido.',
                        ]);
                        redirecionar(APP_URL . 'clientes/form?id=' . urlencode($id));
                    }
                }
            }

            $stmtAnt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
            $stmtAnt->execute([':id' => $id]);
            $dadosAntigos = $stmtAnt->fetch(PDO::FETCH_ASSOC) ?: [];

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
                WHERE id = :id AND ativo = 1
            ");
            $dadosNovos = [
                ':nome'             => $nome,
                ':tipo_pessoa'      => $tipo_pessoa,
                ':cpf_cnpj'         => $cpf_cnpj ?: null,
                ':perfil'           => $perfil,
                ':telefone'         => $telefone ?: null,
                ':email'            => $email ?: null,
                ':endereco'         => $endereco ?: null,
                ':tipo_recebimento' => in_array($tipo_recebimento, ['pix', 'cc'], true) ? $tipo_recebimento : null,
                ':chave_pix'        => $chave_pix ?: null,
                ':banco'            => $banco ?: null,
                ':agencia'          => $agencia ?: null,
                ':conta'            => $conta ?: null,
                ':id'               => $id,
            ];
            $stmt->execute($dadosNovos);

            // Sincronizar vínculos de embarcações
            sincronizarClienteEmbarcacoes($pdo, $id, is_array($embarcacoes_ids) ? $embarcacoes_ids : [], $_SESSION['usuario_id'] ?? null);

            // Sincronizar tipos de embarcação atendidos
            salvarTiposEmbarcacaoDespachante($pdo, $id, $perfil === 'despachante' ? (is_array($tipos_embarcacao) ? $tipos_embarcacao : []) : []);

            // Sincronizar login no Portal do Cliente caso email tenha mudado
            sincronizarLoginPortalCliente($pdo, $id);

            // Auditoria cadastral do SGQ
            if (function_exists('sgqRegistrarAuditoriaCadastral')) {
                sgqRegistrarAuditoriaCadastral($pdo, 'CLIENTE', $id, 'ALTERACAO', $dadosAntigos, $dadosNovos, $_POST['motivo_alteracao'] ?? "Atualização de dados cadastrais de {$perfil}");
            }

            $pdo->commit();

            log_atividade('cliente_editado', "Cliente/Ator Naval '{$nome}' ({$perfil}) editado.");
            setMensagem('success', ucfirst($perfil) . ' atualizado com sucesso!');
            redirecionar(APP_URL . 'clientes?perfil=' . urlencode($perfil));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao editar cliente: ' . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                setMensagem('error', 'CPF/CNPJ já cadastrado em outro cliente.');
            } else {
                setMensagem('error', 'Erro ao atualizar dados.');
            }
            redirecionar(APP_URL . 'clientes/form?id=' . urlencode($id));
        }
        break;

    case 'desativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do registro não informado.');
                redirecionar(APP_URL . 'clientes');
            }

            $stmtAnt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
            $stmtAnt->execute([':id' => $id]);
            $clienteAntigo = $stmtAnt->fetch(PDO::FETCH_ASSOC);

            if (!$clienteAntigo) {
                setMensagem('error', 'Registro não encontrado.');
                redirecionar(APP_URL . 'clientes');
            }

            $perfilAtual = $clienteAntigo['perfil'] ?? 'proprietario';

            $pdo->beginTransaction();

            // Soft delete unificado
            $stmt = $pdo->prepare("UPDATE clientes SET status = 'INATIVO', ativo = 0, excluido_em = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Inativar vínculos de embarcações preservando histórico
            $stmtVinculos = $pdo->prepare("
                UPDATE clientes_embarcacoes 
                SET status = 'INATIVO', 
                    vinculo_ativo_chave = NULL,
                    desvinculado_em = NOW(), 
                    desvinculado_por = :usuario 
                WHERE cliente_id = :id AND status = 'ATIVO'
            ");
            $stmtVinculos->execute([
                ':usuario' => $_SESSION['usuario_id'] ?? null,
                ':id'      => $id,
            ]);

            if (function_exists('sgqRegistrarAuditoriaCadastral')) {
                sgqRegistrarAuditoriaCadastral($pdo, 'CLIENTE', $id, 'INATIVACAO', $clienteAntigo, null, "Inativação de cadastro de {$perfilAtual}");
            }

            $pdo->commit();

            log_atividade('cliente_desativado', "Cliente/Ator Naval ID {$id} ({$perfilAtual}) desativado.");
            setMensagem('success', ucfirst($perfilAtual) . ' desativado com sucesso!');
            redirecionar(APP_URL . 'clientes?perfil=' . urlencode($perfilAtual));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao desativar cliente: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar registro.');
            redirecionar(APP_URL . 'clientes');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida solicitada.');
        redirecionar(APP_URL . 'clientes');
        break;
}
