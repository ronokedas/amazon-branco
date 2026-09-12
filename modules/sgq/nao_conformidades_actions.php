<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE
 * Arquivo: modules/sgq/nao_conformidades_actions.php
 * Processamento de ações de RNC e Planos 5W2H
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'sgq/nao-conformidades');
    }
}

$action = $_POST['action'] ?? '';
$usuarioId = $_SESSION['usuario_id'] ?? null;
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário SGQ';

switch ($action) {

    case 'criar_rnc':
        try {
            $titulo = trim(sanitizar($_POST['titulo'] ?? ''));
            $descricao = trim(sanitizar($_POST['descricao_detalhada'] ?? ''));
            $origem = trim(sanitizar($_POST['origem'] ?? 'INSPECAO_CAMPO'));
            $severidade = trim(sanitizar($_POST['severidade'] ?? 'MEDIA'));
            $dataPrevista = $_POST['data_conclusao_prevista'] ?? null;

            if ($titulo === '' || $descricao === '') {
                throw new Exception('Título e descrição detalhada são obrigatórios.');
            }

            $numeroRnc = sgqGerarNumeroRNC($pdo);
            $rncId = gerarUUID();

            $stmt = $pdo->prepare("
                INSERT INTO sgq_nao_conformidades (
                    id, numero_rnc, origem, severidade, titulo, descricao_detalhada,
                    status_ciclo_vida, responsavel_abertura_id, responsavel_abertura_nome,
                    data_identificacao, data_conclusao_prevista
                ) VALUES (
                    :id, :num, :origem, :severidade, :titulo, :desc,
                    'ABERTA', :resp_id, :resp_nome, NOW(), :data_prev
                )
            ");

            $stmt->execute([
                ':id' => $rncId,
                ':num' => $numeroRnc,
                ':origem' => $origem,
                ':severidade' => $severidade,
                ':titulo' => $titulo,
                ':desc' => $descricao,
                ':resp_id' => $usuarioId,
                ':resp_nome' => $usuarioNome,
                ':data_prev' => $dataPrevista ?: null,
            ]);

            setMensagem('success', "Não Conformidade {$numeroRnc} aberta com sucesso!");
            redirecionar(APP_URL . 'sgq/nao-conformidades?id=' . urlencode($rncId));
        } catch (Exception $e) {
            error_log('[SGQ CRIAR RNC ERRO] ' . $e->getMessage());
            setMensagem('error', 'Erro ao abrir RNC: ' . $e->getMessage());
            redirecionar(APP_URL . 'sgq/nao-conformidades');
        }
        break;

    case 'salvar_causa_raiz':
        try {
            $id = trim($_POST['id'] ?? '');
            $causaRaiz = trim(sanitizar($_POST['analise_causa_raiz'] ?? ''));
            $statusCiclo = trim(sanitizar($_POST['status_ciclo_vida'] ?? ''));

            if ($id === '') {
                throw new Exception('Ocorrência inválida.');
            }

            $sql = "UPDATE sgq_nao_conformidades SET analise_causa_raiz = :causa, status_ciclo_vida = :status";
            $params = [
                ':causa' => $causaRaiz ?: null,
                ':status' => $statusCiclo,
                ':id' => $id,
            ];

            if ($statusCiclo === 'ENCERRADA_EFICAZ') {
                $sql .= ", encerrada_em = NOW(), encerrada_por = :enc_por";
                $params[':enc_por'] = $usuarioId;
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            setMensagem('success', 'Diagnóstico e status da ocorrência atualizados com sucesso!');
            redirecionar(APP_URL . 'sgq/nao-conformidades?id=' . urlencode($id));
        } catch (Exception $e) {
            error_log('[SGQ SALVAR CAUSA ERRO] ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar diagnóstico: ' . $e->getMessage());
            redirecionar(APP_URL . 'sgq/nao-conformidades' . (!empty($id) ? '?id=' . urlencode($id) : ''));
        }
        break;

    case 'adicionar_plano_5w2h':
        try {
            $rncId = trim($_POST['nao_conformidade_id'] ?? '');
            $what = trim(sanitizar($_POST['o_que_fazer_what'] ?? ''));
            $why = trim(sanitizar($_POST['por_que_fazer_why'] ?? ''));
            $where = trim(sanitizar($_POST['onde_fazer_where'] ?? ''));
            $who = trim(sanitizar($_POST['quem_fara_who'] ?? ''));
            $when = $_POST['quando_fara_when'] ?? '';
            $how = trim(sanitizar($_POST['como_fazer_how'] ?? ''));
            $howMuch = (float)($_POST['quanto_custa_how_much'] ?? 0);

            if ($rncId === '' || $what === '' || $who === '' || $when === '') {
                throw new Exception('Preencha os campos obrigatórios (O que fazer, Responsável e Prazo).');
            }

            $planoId = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO sgq_planos_acao (
                    id, nao_conformidade_id, o_que_fazer_what, por_que_fazer_why,
                    onde_fazer_where, quem_fara_who, quando_fara_when, como_fazer_how,
                    quanto_custa_how_much, status_acao
                ) VALUES (
                    :id, :rnc_id, :what, :why, :where, :who, :when, :how, :how_much, 'PENDENTE'
                )
            ");

            $stmt->execute([
                ':id' => $planoId,
                ':rnc_id' => $rncId,
                ':what' => $what,
                ':why' => $why ?: null,
                ':where' => $where ?: null,
                ':who' => $who,
                ':when' => $when,
                ':how' => $how ?: null,
                ':how_much' => $howMuch,
            ]);

            // Atualiza status da RNC para PLANO_ACAO_DEFINIDO se estiver ABERTA
            $pdo->prepare("UPDATE sgq_nao_conformidades SET status_ciclo_vida = 'PLANO_ACAO_DEFINIDO' WHERE id = :id AND status_ciclo_vida = 'ABERTA'")->execute([':id' => $rncId]);

            setMensagem('success', 'Ação corretiva registrada com sucesso!');
            redirecionar(APP_URL . 'sgq/nao-conformidades?id=' . urlencode($rncId));
        } catch (Exception $e) {
            error_log('[SGQ ADICIONAR PLANO ERRO] ' . $e->getMessage());
            setMensagem('error', 'Erro ao registrar ação: ' . $e->getMessage());
            redirecionar(APP_URL . 'sgq/nao-conformidades' . (!empty($rncId) ? '?id=' . urlencode($rncId) : ''));
        }
        break;

    case 'concluir_acao_5w2h':
        try {
            $planoId = trim($_POST['plano_id'] ?? '');
            $rncId = trim($_POST['rnc_id'] ?? '');

            if ($planoId === '') {
                throw new Exception('Ação inválida.');
            }

            $stmt = $pdo->prepare("
                UPDATE sgq_planos_acao
                SET status_acao = 'CONCLUIDA',
                    eficacia_aprovada_por = :usuario_id,
                    eficacia_data = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':id' => $planoId,
            ]);

            setMensagem('success', 'Ação corretiva concluída com sucesso!');
            redirecionar(APP_URL . 'sgq/nao-conformidades?id=' . urlencode($rncId));
        } catch (Exception $e) {
            error_log('[SGQ CONCLUIR PLANO ERRO] ' . $e->getMessage());
            setMensagem('error', 'Erro ao concluir ação: ' . $e->getMessage());
            redirecionar(APP_URL . 'sgq/nao-conformidades');
        }
        break;

    default:
        setMensagem('error', 'Ação não reconhecida.');
        redirecionar(APP_URL . 'sgq/nao-conformidades');
}
