<?php
/**
 * MÓDULO: Documentação > Actions
 * Processar ações de documentos e exigências
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('documentacao');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'baixar_exigencia':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisição inválida.');
            redirecionar(APP_URL . 'dashboard');
        }

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            setMensagem('error', 'Token de segurança inválido.');
            redirecionar(APP_URL . 'dashboard');
        }

        $vistoria_id = $_POST['vistoria_id'] ?? '';
        $stmtRelatorio = $pdo->prepare("SELECT agendamento_id FROM vistorias WHERE id = :id");
        $stmtRelatorio->execute([':id' => $vistoria_id]);
        $agendamentoId = $stmtRelatorio->fetchColumn();
        setMensagem('warning', 'A baixa direta foi substituída pelo Relatório de Verificação de Cumprimento de Exigências.');
        redirecionar($agendamentoId
            ? APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamentoId) . '&vistoria_id=' . urlencode($vistoria_id)
            : APP_URL . 'vistorias');

        $vistoria_id = $_POST['vistoria_id'] ?? '';
        $exigencia_id = $_POST['exigencia_id'] ?? '';

        if (!$vistoria_id || !$exigencia_id) {
            setMensagem('error', 'Dados insuficientes.');
            redirecionar(APP_URL . 'dashboard');
        }

        try {
            $pdo->beginTransaction();

            // 1. Dar baixa na exigência
            $stmt = $pdo->prepare("UPDATE vistoria_exigencias SET conforme = 'sim' WHERE id = :exigencia_id AND vistoria_id = :vistoria_id");
            $stmt->execute([':exigencia_id' => $exigencia_id, ':vistoria_id' => $vistoria_id]);

            // 2. Verificar se ainda existem exigências pendentes
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id AND conforme = 'nao'");
            $stmtCheck->execute([':vistoria_id' => $vistoria_id]);
            $pendentes = $stmtCheck->fetchColumn();

            if ($pendentes == 0) {
                // 3. Se não há mais pendentes, mudar status da vistoria para APROVADA
                $stmtStatus = $pdo->prepare("UPDATE vistorias SET status = 'APROVADA', atualizado_em = NOW() WHERE id = :vistoria_id");
                $stmtStatus->execute([':vistoria_id' => $vistoria_id]);
                
                // Também atualizar a OS se estiver vinculada
                $stmtOS = $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = (SELECT agendamento_id FROM vistorias WHERE id = :vistoria_id) AND status IN ('pendente', 'em_andamento')");
                $stmtOS->execute([':vistoria_id' => $vistoria_id]);

                $msg = 'Exigência baixada. Todas as exigências foram resolvidas e a vistoria agora está APROVADA.';
            } else {
                $msg = 'Exigência baixada com sucesso. Ainda restam exigências pendentes.';
            }

            $pdo->commit();
            setMensagem('success', $msg);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Erro ao baixar exigência: ' . $e->getMessage());
            setMensagem('error', 'Erro ao processar baixa.');
        }

        // Redirecionar para a vistoria correta (precisamos buscar o agendamento_id para voltar ao relatório)
        $stmtV = $pdo->prepare("SELECT agendamento_id FROM vistorias WHERE id = ?");
        $stmtV->execute([$vistoria_id]);
        $v = $stmtV->fetch();
        
        if ($v) {
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . $v['agendamento_id']);
        } else {
            redirecionar(APP_URL . 'dashboard');
        }
        break;

    default:
        setMensagem('error', 'Ação não reconhecida.');
        redirecionar(APP_URL . 'dashboard');
        break;
}
