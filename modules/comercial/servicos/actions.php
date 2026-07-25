<?php
/**
 * MÓDULO: COMERCIAL > SERVIÇOS
 * Arquivo: actions.php - Processar POST (insert/update/soft delete)
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

verificar_sessao();
if (!podeAcessar('servicos')) {
    setMensagem('error', 'Acesso negado. Você não tem permissão para gerenciar serviços.');
    redirecionar(APP_URL . 'dashboard');
}

// Validar CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'comercial/servicos');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'inserir':
        try {
            $nome       = sanitizar($_POST['nome'] ?? '');
            $descricao  = sanitizar($_POST['descricao'] ?? '');
            $certificado_modelo = strtoupper(trim((string)($_POST['certificado_modelo'] ?? '')));
            $certificado_modelo = in_array($certificado_modelo, ['CSN', 'CNBL', 'CNARQ'], true) ? $certificado_modelo : null;
            $preco_raw  = $_POST['preco_padrao'] ?? '0,00';

            if (empty($nome)) {
                setMensagem('error', 'O nome do serviço é obrigatório.', [
                    'nome' => 'Informe o nome do servico.',
                ]);
                redirecionar(APP_URL . 'comercial/servicos/form');
            }

            // Converter preço do formato brasileiro para decimal
            $preco_padrao = converterMoedaDecimal($preco_raw);

            $stmt = $pdo->prepare("
                INSERT INTO servicos (id, nome, descricao, certificado_modelo, preco_padrao, criado_por)
                VALUES (UUID(), :nome, :descricao, :certificado_modelo, :preco_padrao, :criado_por)
            ");
            $stmt->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao ?: null,
                ':certificado_modelo' => $certificado_modelo,
                ':preco_padrao' => $preco_padrao,
                ':criado_por'   => $_SESSION['usuario_id'],
            ]);

            log_atividade('servico_criado', "Serviço '{$nome}' criado.");
            setMensagem('success', 'Serviço cadastrado com sucesso!');
            redirecionar(APP_URL . 'comercial/servicos');

        } catch (Exception $e) {
            error_log('Erro ao inserir serviço: ' . $e->getMessage());
            setMensagem('error', 'Erro ao cadastrar serviço.');
            redirecionar(APP_URL . 'comercial/servicos/form');
        }
        break;

    case 'editar':
        try {
            $id         = $_POST['id'] ?? '';
            $nome       = sanitizar($_POST['nome'] ?? '');
            $descricao  = sanitizar($_POST['descricao'] ?? '');
            $certificado_modelo = strtoupper(trim((string)($_POST['certificado_modelo'] ?? '')));
            $certificado_modelo = in_array($certificado_modelo, ['CSN', 'CNBL', 'CNARQ'], true) ? $certificado_modelo : null;
            $preco_raw  = $_POST['preco_padrao'] ?? '0,00';
            $ativo      = isset($_POST['ativo']) ? 1 : 0;

            if (empty($id) || empty($nome)) {
                setMensagem('error', 'Dados inválidos.');
                redirecionar(APP_URL . 'comercial/servicos');
            }

            // Converter preço do formato brasileiro para decimal
            $preco_padrao = converterMoedaDecimal($preco_raw);

            $stmt = $pdo->prepare("
                UPDATE servicos
                SET nome = :nome,
                    descricao = :descricao,
                    certificado_modelo = :certificado_modelo,
                    preco_padrao = :preco_padrao,
                    ativo = :ativo
                WHERE id = :id
            ");
            $stmt->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao ?: null,
                ':certificado_modelo' => $certificado_modelo,
                ':preco_padrao' => $preco_padrao,
                ':ativo'        => $ativo,
                ':id'           => $id,
            ]);

            log_atividade('servico_editado', "Serviço '{$nome}' editado.");
            setMensagem('success', 'Serviço atualizado com sucesso!');
            redirecionar(APP_URL . 'comercial/servicos');

        } catch (Exception $e) {
            error_log('Erro ao editar serviço: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atualizar serviço.');
            redirecionar(APP_URL . 'comercial/servicos/form?id=' . urlencode($id));
        }
        break;

    case 'desativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do serviço não informado.');
                redirecionar(APP_URL . 'comercial/servicos');
            }

            // Buscar nome para o log
            $stmtNome = $pdo->prepare("SELECT nome FROM servicos WHERE id = :id");
            $stmtNome->execute([':id' => $id]);
            $nomeServico = $stmtNome->fetchColumn() ?: 'Desconhecido';

            $stmt = $pdo->prepare("UPDATE servicos SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_atividade('servico_desativado', "Serviço '{$nomeServico}' desativado (soft delete).");
            setMensagem('success', 'Serviço desativado com sucesso!');
            redirecionar(APP_URL . 'comercial/servicos');

        } catch (Exception $e) {
            error_log('Erro ao desativar serviço: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar serviço.');
            redirecionar(APP_URL . 'comercial/servicos');
        }
        break;

    case 'reativar':
        try {
            $id = $_GET['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do serviço não informado.');
                redirecionar(APP_URL . 'comercial/servicos');
            }

            // Buscar nome para o log
            $stmtNome = $pdo->prepare("SELECT nome FROM servicos WHERE id = :id");
            $stmtNome->execute([':id' => $id]);
            $nomeServico = $stmtNome->fetchColumn() ?: 'Desconhecido';

            $stmt = $pdo->prepare("UPDATE servicos SET ativo = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_atividade('servico_reativado', "Serviço '{$nomeServico}' reativado.");
            setMensagem('success', 'Serviço reativado com sucesso!');
            redirecionar(APP_URL . 'comercial/servicos');

        } catch (Exception $e) {
            error_log('Erro ao reativar serviço: ' . $e->getMessage());
            setMensagem('error', 'Erro ao reativar serviço.');
            redirecionar(APP_URL . 'comercial/servicos');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'comercial/servicos');
        break;
}

/**
 * Converte uma string de valor monetário brasileiro (ex: "1.500,00")
 * para um valor decimal que pode ser armazenado no banco (ex: 1500.00).
 *
 * @param string $valor Valor no formato brasileiro (ex: "1.500,00", "1500", "1.500")
 * @return float Valor decimal
 */
function converterMoedaDecimal($valor) {
    // Remove tudo que não for dígito, vírgula, ponto ou sinal negativo
    $valor = preg_replace('/[^\d,.\-]/', '', $valor);
    // Se tiver vírgula e ponto, a vírgula é dos centavos
    if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
        // Cenário: 1.500,00 -> remove pontos, troca vírgula por ponto
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } elseif (strpos($valor, ',') !== false) {
        // Só vírgula: pode ser 1500,00 ou 15,00
        $valor = str_replace(',', '.', $valor);
    }
    // Se tiver só ponto: 1500.00 -> mantém
    return round((float)$valor, 2);
}
