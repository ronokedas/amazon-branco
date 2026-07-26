<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: actions.php - Processar acoes (salvar vistoria, alterar status, salvar relatorio)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/aprovacao_documentos.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (in_array($action, ['salvar', 'salvar_wizard'], true)) {
    setMensagem('error', 'Não é permitido criar vistoria avulsa. Inicie-a pelo agendamento atribuído.');
    redirecionar(APP_URL . 'agendamentos');
}

function normalizarTipoVistoriaAction(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    return $ascii !== false ? $ascii : $texto;
}

function blocosPermitidosRelatorioAction(string $tipoVistoria): array
{
    $texto = normalizarTipoVistoriaAction($tipoVistoria);
    $blocos = [];

    if (strpos($texto, 'seco') !== false) {
        $blocos[] = 'seco';
    }
    if (strpos($texto, 'flutu') !== false || strpos($texto, 'agua') !== false || strpos($texto, 'licenca provisoria') !== false) {
        $blocos[] = 'flutuando';
    }
    if (strpos($texto, 'borda') !== false || strpos($texto, 'cnbl') !== false) {
        $blocos[] = 'borda_livre';
    }
    if (strpos($texto, 'arquea') !== false || strpos($texto, 'cnarq') !== false) {
        $blocos[] = 'arqueacao';
    }

    return !empty($blocos) ? $blocos : ['seco', 'flutuando', 'borda_livre', 'arqueacao'];
}

function bloquearMutacaoRelatorioAuditavel(PDO $pdo, string $vistoriaId, string $destino): void
{
    global $action;
    if ($vistoriaId === '') return;
    $stmt = $pdo->prepare("SELECT 1 FROM documento_aprovacoes WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status IN ('APROVADO','CANCELADO') LIMIT 1");
    $stmt->execute([':id'=>$vistoriaId]);
    if ($stmt->fetchColumn()) {
        setMensagem('error', 'Este relatorio possui uma aprovacao auditavel e e imutavel. Para corrigir, cancele e emita um novo documento.');
        redirecionar($destino);
    }
    $stmt = $pdo->prepare("SELECT status FROM vistorias WHERE id=:id LIMIT 1");
    $stmt->execute([':id'=>$vistoriaId]);
    $statusRelatorio = (string)$stmt->fetchColumn();
    $reaberturaFormal = $action === 'alterar_status'
        && in_array($statusRelatorio, ['REPROVADA','CANCELADA'], true)
        && (string)($_POST['status'] ?? '') === 'PENDENTE';
    if ($action !== 'aprovar_ou_reprovar' && !$reaberturaFormal && in_array($statusRelatorio, ['APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA','CANCELADA'], true)) {
        setMensagem('error', 'Este relatorio esta finalizado e nao pode mais ser alterado. O administrador deve devolve-lo formalmente para correcao.');
        redirecionar($destino);
    }
    if ($action !== 'aprovar_ou_reprovar' && !$reaberturaFormal) {
        $stmt = $pdo->prepare("SELECT 1 FROM documento_assinaturas WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='ASSINADO' LIMIT 1");
        $stmt->execute([':id'=>$vistoriaId]);
        if ($stmt->fetchColumn()) {
            setMensagem('error', 'Este relatorio ja foi assinado e esta congelado. O administrador deve devolve-lo para correcao antes de qualquer alteracao.');
            redirecionar($destino);
        }
    }
}

switch ($action) {

    // ==============================
    // INICIAR VERIFICACAO DE CUMPRIMENTO DE EXIGENCIAS A/S
    // ==============================
    case 'iniciar_cumprimento_exigencias':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
            setMensagem('error', 'Requisicao invalida ou token de seguranca expirado.');
            redirecionar(APP_URL . 'vistorias');
        }

        $relatorioOrigemId = trim($_POST['vistoria_id'] ?? '');
        try {
            $pdo->beginTransaction();
            $stmtOrigem = $pdo->prepare("SELECT v.*, a.vistoriador_id, a.tipo_vistoria
                FROM vistorias v
                INNER JOIN agendamentos a ON a.id = v.agendamento_id
                WHERE v.id = :id FOR UPDATE");
            $stmtOrigem->execute([':id' => $relatorioOrigemId]);
            $origem = $stmtOrigem->fetch(PDO::FETCH_ASSOC);
            if (!$origem || (string)$origem['status'] !== 'RETORNO_AS') {
                throw new Exception('O relatorio de origem precisa estar encaminhado para Retorno A/S.');
            }
            $vigente = obterRelatorioVigenteCadeia($pdo, (string)$origem['id']);
            if (!$vigente || $vigente['id'] !== $origem['id']) {
                throw new Exception('Este relatorio ja foi substituido por um relatorio mais recente.');
            }
            if (getCargo() !== 'ADMIN') {
                throw new Exception('Somente o administrador pode iniciar e agendar o retorno A/S.');
            }
            if (!relatorioPossuiASPendente($pdo, $origem['id'])) {
                throw new Exception('O relatorio nao possui exigencia A/S pendente.');
            }

            criarPendenciaRetornoAS($pdo, (string)$origem['id'], (string)($_SESSION['usuario_id'] ?? ''));
            $pdo->commit();
            setMensagem('info', 'Informe a data, o local e o vistoriador para o retorno de cumprimento A/S.');
            redirecionar(APP_URL . 'agendamentos/form?relatorio_origem_id=' . urlencode((string)$origem['id']));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setMensagem('error', $e->getMessage());
            redirecionar(APP_URL . 'vistorias');
        }
        break;

    // ==============================
    // SALVAR VISTORIA (WIZARD)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $embarcacao_id = trim($_POST['embarcacao_id'] ?? '');
        $pessoa_id     = trim($_POST['pessoa_id'] ?? '');
        $armador_id    = trim($_POST['armador_id'] ?? '');
        $data_vistoria = trim($_POST['data_vistoria'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');

        // Validacoes
        $erros = [];

        if (empty($embarcacao_id)) {
            $erros[] = 'Selecione uma embarcacao.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, ativo FROM embarcacoes WHERE id = :id");
                $stmt->execute([':id' => $embarcacao_id]);
                $emb = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$emb) {
                    $erros[] = 'Embarcacao nao encontrada.';
                } elseif (!$emb['ativo']) {
                    $erros[] = 'Embarcacao inativa. Nao e possivel criar vistoria para embarcacao inativa.';
                }
            } catch (Exception $e) {
                error_log('Erro ao validar embarcacao: ' . $e->getMessage());
                $erros[] = 'Erro ao validar embarcacao.';
            }
        }

        if (empty($pessoa_id)) {
            $erros[] = 'Selecione um responsável (Proprietário).';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, status FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $pessoa_id]);
                $pes = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pes) {
                    $erros[] = 'Responsável não encontrado.';
                } elseif ($pes['status'] !== 'ATIVO') {
                    $erros[] = 'Responsável inativo. Não é possível criar vistoria com contato inativo.';
                }
            } catch (Exception $e) {
                error_log('Erro ao validar responsável: ' . $e->getMessage());
                $erros[] = 'Erro ao validar responsável.';
            }
        }

        if (empty($data_vistoria)) {
            $erros[] = 'A data da vistoria e obrigatoria.';
        } else {
            $dataObj = DateTime::createFromFormat('Y-m-d', $data_vistoria);
            if (!$dataObj || $dataObj->format('Y-m-d') !== $data_vistoria) {
                $erros[] = 'Data da vistoria invalida.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            redirecionar(APP_URL . 'vistorias/nova');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO vistorias (id, embarcacao_id, pessoa_id, armador_id, data_vistoria, observacoes, status, criado_por) VALUES (:id, :embarcacao_id, :pessoa_id, :armador_id, :data_vistoria, :observacoes, 'PENDENTE', :criado_por)");
            $stmt->execute([
                ':id'            => gerarUUID(),
                ':embarcacao_id' => $embarcacao_id,
                ':pessoa_id'     => $pessoa_id,
                ':armador_id'    => !empty($armador_id) ? $armador_id : null,
                ':data_vistoria' => $data_vistoria,
                ':observacoes'   => $observacoes,
                ':criado_por'    => $_SESSION['usuario_id']
            ]);

            unset($_SESSION['wizard_embarcacao_id']);
            unset($_SESSION['wizard_pessoa_id']);
            unset($_SESSION['wizard_armador_id']);

            log_atividade('vistoria_criada', "Vistoria criada para embarcacao ID: {$embarcacao_id}.");
            setMensagem('success', 'Vistoria criada com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao salvar vistoria: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar vistoria. Tente novamente.');
        }

        redirecionar(APP_URL . 'vistorias');
        break;

    // ==============================
    // ATRIBUIR VISTORIADOR E DATA
    // ==============================
    case 'atribuir_vistoriador':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        if (getCargo() !== 'ADMIN') {
            setMensagem('error', 'Apenas administradores podem atribuir vistoriador.');
            redirecionar(APP_URL . 'vistorias');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $id             = trim($_POST['id'] ?? '');
        $vistoriador_id = trim($_POST['vistoriador_id'] ?? '');
        $data_vistoria  = trim($_POST['data_vistoria'] ?? '');
        bloquearMutacaoRelatorioAuditavel($pdo, $id, APP_URL . 'vistorias/detalhe?id=' . urlencode($id));

        if (empty($id) || empty($vistoriador_id) || empty($data_vistoria)) {
            setMensagem('error', 'Preencha todos os campos obrigatórios.');
            redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        }

        try {
            $pdo->beginTransaction();

            $stmtV = $pdo->prepare("SELECT agendamento_id FROM vistorias WHERE id = :id");
            $stmtV->execute([':id' => $id]);
            $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$vistoria) {
                throw new Exception('Vistoria não encontrada.');
            }

            // Atualiza a vistoria
            $stmtUpdV = $pdo->prepare("UPDATE vistorias SET data_vistoria = :data_vistoria WHERE id = :id");
            $stmtUpdV->execute([
                ':data_vistoria' => $data_vistoria,
                ':id'            => $id
            ]);

            // Se tiver agendamento, atualiza também
            if (!empty($vistoria['agendamento_id'])) {
                $stmtUpdAg = $pdo->prepare("UPDATE agendamentos SET vistoriador_id = :vistoriador_id, data_vistoria = :data_vistoria WHERE id = :agendamento_id");
                $stmtUpdAg->execute([
                    ':vistoriador_id' => $vistoriador_id,
                    ':data_vistoria'  => $data_vistoria,
                    ':agendamento_id' => $vistoria['agendamento_id']
                ]);
            } else {
                // Criar agendamento se não existir
                $stmtVData = $pdo->prepare("SELECT embarcacao_id, pessoa_id FROM vistorias WHERE id = :id");
                $stmtVData->execute([':id' => $id]);
                $vData = $stmtVData->fetch(PDO::FETCH_ASSOC);

                $novo_agendamento_id = gerarUUID();
                $stmtNewAg = $pdo->prepare("
                    INSERT INTO agendamentos (id, cliente_id, embarcacao_id, vistoriador_id, data_vistoria, status, tipo_vistoria, criado_por)
                    VALUES (:id, :cliente_id, :embarcacao_id, :vistoriador_id, :data_vistoria, 'pendente', 'Vistoria Avulsa', :criado_por)
                ");
                $stmtNewAg->execute([
                    ':id' => $novo_agendamento_id,
                    ':cliente_id' => $vData['pessoa_id'],
                    ':embarcacao_id' => $vData['embarcacao_id'],
                    ':vistoriador_id' => $vistoriador_id,
                    ':data_vistoria' => $data_vistoria,
                    ':criado_por' => $_SESSION['usuario_id']
                ]);

                // Atualizar a vistoria com o novo agendamento
                $stmtUpdV2 = $pdo->prepare("UPDATE vistorias SET agendamento_id = :agendamento_id WHERE id = :id");
                $stmtUpdV2->execute([
                    ':agendamento_id' => $novo_agendamento_id,
                    ':id' => $id
                ]);
            }

            $pdo->commit();
            log_atividade('vistoria_agendada', "Vistoria ID: {$id} agendada para {$data_vistoria} com vistoriador {$vistoriador_id}.");
            setMensagem('success', 'Vistoria agendada e vistoriador atribuído com sucesso!');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao atribuir vistoriador: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atribuir vistoriador.');
        }

        redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        break;

    // ==============================
    // ALTERAR STATUS (APENAS ADMIN)
    // ==============================
    case 'alterar_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        if (getCargo() !== 'ADMIN') {
            setMensagem('error', 'Apenas administradores podem alterar o status da vistoria.');
            redirecionar(APP_URL . 'vistorias');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $id            = trim($_POST['id'] ?? '');
        $novo_status   = trim($_POST['status'] ?? '');
        $resultado     = trim($_POST['resultado'] ?? '');
        bloquearMutacaoRelatorioAuditavel($pdo, $id, APP_URL . 'vistorias/detalhe?id=' . urlencode($id));

        // Status alinhados com o fluxo real do relatorio tecnico
        // (salvar_relatorio / aprovacao_relatorios usam os mesmos valores).
        $statusesValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'REPROVADA', 'CANCELADA'];

        $erros = [];
        if (empty($id)) $erros[] = 'ID da vistoria invalido.';
        if (!in_array($novo_status, $statusesValidos)) $erros[] = 'Status invalido.';

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            redirecionar(APP_URL . 'vistorias');
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE vistorias SET status = :status, resultado = :resultado WHERE id = :id");
            $stmt->execute([
                ':status'   => $novo_status,
                ':resultado' => $resultado,
                ':id'       => $id
            ]);
            if ($novo_status === 'PENDENTE') {
                $pdo->prepare("UPDATE documento_assinaturas SET status='CANCELADO',cancelado_em=NOW(),cancelado_por=:usuario,motivo_cancelamento='Relatorio devolvido formalmente para correcao.' WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='ASSINADO'")->execute([':usuario'=>$_SESSION['usuario_id'], ':id'=>$id]);
                $pdo->prepare("UPDATE vistorias SET assinatura_status='CANCELADO',assinatura_em=NULL,responsavel_assinatura_id=NULL,aprovado_por=NULL,data_aprovacao=NULL WHERE id=:id")->execute([':id'=>$id]);
            }
            $pdo->commit();

            log_atividade('vistoria_status', "Vistoria ID: {$id} alterada para status {$novo_status}.");
            setMensagem('success', 'Status da vistoria atualizado com sucesso!');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao alterar status da vistoria: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status da vistoria.');
        }

        redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        break;

    // ==============================
    // SALVAR DADOS DO WIZARD (PARCIAL)
    // ==============================
    case 'salvar_wizard':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        $passo = intval($_POST['passo'] ?? 1);
        $embarcacao_id = trim($_POST['embarcacao_id'] ?? '');
        $pessoa_id     = trim($_POST['pessoa_id'] ?? '');
        $armador_id    = trim($_POST['armador_id'] ?? '');
        $data_vistoria = trim($_POST['data_vistoria'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');

        if ($passo >= 1) $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
        if ($passo >= 2) $_SESSION['wizard_pessoa_id'] = $pessoa_id;
        if ($passo >= 3) $_SESSION['wizard_armador_id'] = $armador_id;
        $_SESSION['wizard_data_vistoria'] = $data_vistoria;
        $_SESSION['wizard_observacoes']   = $observacoes;

        $proximo = $passo + 1;
        if ($proximo > 3) $proximo = 3;

        redirecionar(APP_URL . 'vistorias/nova?passo=' . $proximo);
        break;

    // ==============================
    // SALVAR RELATORIO TECNICO (EXPANSAO FASE 3)
    // ==============================
    case 'salvar_relatorio':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $agendamento_id = $_POST['agendamento_id'] ?? '';
        if (($_POST['formulario_completo'] ?? '') !== '1') {
            setMensagem('error', 'O relatorio possui muitos campos e o envio foi interrompido pelo servidor. Tente novamente apos reiniciar o ambiente.');
            $destino = $agendamento_id !== ''
                ? APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id)
                : APP_URL . 'agendamentos';
            redirecionar($destino);
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $vistoria_id          = $_POST['vistoria_id'] ?? '';
        if (getCargo() !== 'VISTORIADOR') {
            setMensagem('error', 'Somente o vistoriador atribuido pode alterar o conteudo e as exigencias do relatorio.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        bloquearMutacaoRelatorioAuditavel($pdo, $vistoria_id, APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        $armador_id           = trim($_POST['armador_id'] ?? '');
        $operador_nome        = trim($_POST['operador_nome'] ?? '');
        $data_vistoria        = trim($_POST['data_vistoria'] ?? '');
        $observacoes_tecnicas = trim($_POST['observacoes_tecnicas'] ?? '');
        $status_vistoria      = $_POST['status_vistoria'] ?? 'PENDENTE';
        $prazo_exigencias_dias = (int)($_POST['prazo_exigencias_dias'] ?? 0);
        $prazo_padrao_exigencias = null;
        
        // Avulsas
        $itens                = $_POST['exigencia_item'] ?? [];
        $descricoes           = $_POST['exigencia_descricao'] ?? [];
        $status_items         = $_POST['status_item'] ?? [];
        $observacoes_exig     = $_POST['exigencia_observacao'] ?? [];
        $exigencias_sem_prazo = $_POST['exigencia_sem_prazo'] ?? [];
        $exigencias_blocos    = $_POST['exigencia_bloco'] ?? [];
        $exigencia_ids        = $_POST['exigencia_id'] ?? [];
        $ordens               = $_POST['exigencia_ordem'] ?? [];
        
        // Checklist
        $checklist_ids        = $_POST['checklist_id'] ?? [];
        $checklist_status     = $_POST['checklist_status'] ?? [];
        $checklist_obs        = $_POST['checklist_observacao'] ?? [];
        $checklist_sem_prazo  = $_POST['checklist_sem_prazo'] ?? [];
        $checklist_sem_prazo_por_id = $_POST['checklist_sem_prazo_por_id'] ?? [];
        $checklist_item_normam= $_POST['checklist_item_normam'] ?? [];

        if (empty($agendamento_id)) {
            setMensagem('error', 'Agendamento nao informado.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $statusValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'REPROVADA', 'CANCELADA'];
        if (!in_array($status_vistoria, $statusValidos)) {
            setMensagem('error', 'Status de vistoria invalido.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        
        if (getCargo() === 'VISTORIADOR' && in_array($status_vistoria, ['REPROVADA', 'CANCELADA'])) {
            setMensagem('error', 'Vistoriadores só podem salvar relatórios como Pendente ou Aguardando Aprovação.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }

        // Guard: impedir edicao de relatorio técnico já finalizado.
        // VISTORIADOR não pode alterar vistoria com status terminal (homologada/reprovada).
        // ADMIN mantém liberdade para corrigir/reabrir quando necessário.
        $statusTerminais = ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'RETORNO_AS', 'REPROVADA'];
        if (!empty($vistoria_id)) {
            $stmtStatusAtual = $pdo->prepare("SELECT status FROM vistorias WHERE id = :id LIMIT 1");
            $stmtStatusAtual->execute([':id' => $vistoria_id]);
            $status_atual = $stmtStatusAtual->fetchColumn();
            if ($status_atual && in_array($status_atual, $statusTerminais, true) && getCargo() !== 'ADMIN') {
                setMensagem('error', 'Este relatório já está finalizado (' . $status_atual . ') e não pode ser editado por vistoriadores.');
                redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
            }
        }

        // O relatorio de cumprimento atualiza somente as exigencias copiadas, preservando
        // integralmente o relatorio aprovado que lhe deu origem.
        if (!empty($vistoria_id)) {
            $stmtFinalidade = $pdo->prepare("SELECT finalidade, numero, status FROM vistorias WHERE id = :id LIMIT 1");
            $stmtFinalidade->execute([':id' => $vistoria_id]);
            $relatorioCumprimento = $stmtFinalidade->fetch(PDO::FETCH_ASSOC);
            if (($relatorioCumprimento['finalidade'] ?? '') === 'CUMPRIMENTO_EXIGENCIAS') {
                try {
                    $pdo->beginTransaction();
                    $stmtAg = $pdo->prepare("SELECT vistoriador_id FROM agendamentos WHERE id = :id FOR UPDATE");
                    $stmtAg->execute([':id' => $agendamento_id]);
                    $vistoriadorAtribuido = $stmtAg->fetchColumn();
                    if (getCargo() !== 'ADMIN' && $vistoriadorAtribuido !== ($_SESSION['usuario_id'] ?? '')) {
                        throw new Exception('Somente o vistoriador atribuido pode preencher este relatorio.');
                    }
                    if (!in_array($relatorioCumprimento['status'], ['PENDENTE', 'AGUARDANDO_APROVACAO'], true)) {
                        throw new Exception('Este relatorio de cumprimento ja foi finalizado e esta preservado para auditoria.');
                    }
                    if (getCargo() === 'VISTORIADOR' && !in_array($status_vistoria, ['PENDENTE', 'AGUARDANDO_APROVACAO'], true)) {
                        throw new Exception('O vistoriador deve salvar ou enviar o relatorio para aprovacao.');
                    }

                    $statusCumprimento = $_POST['cumprimento_status'] ?? [];
                    $observacoesCumprimento = $_POST['cumprimento_observacao'] ?? [];
                    $prazoCumprimentoValido = in_array($prazo_exigencias_dias, [60, 90], true);
                    // Se a validade estiver ausente, salve todo o preenchimento como
                    // rascunho e impeça somente o envio para análise.
                    $statusCumprimentoSalvar = $prazoCumprimentoValido ? $status_vistoria : 'PENDENTE';
                    $statusPermitidos = ['pendente', 'cumprida', 'nao_cumprida_transcrita', 'cumprida_parcial_reescrita'];
                    $stmtAtualizar = $pdo->prepare("UPDATE vistoria_exigencias
                        SET status_item = :status_item,
                            conforme = :conforme,
                            observacao = :observacao
                        WHERE id = :id AND vistoria_id = :vistoria_id");
                    $stmtIds = $pdo->prepare("SELECT id FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                    $stmtIds->execute([':vistoria_id' => $vistoria_id]);
                    $idsEsperados = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
                    if (!$idsEsperados) throw new Exception('O relatorio nao possui exigencias para verificar.');

                    $decisoesLog = [];
                    foreach ($idsEsperados as $exigenciaId) {
                        $situacao = $statusCumprimento[$exigenciaId] ?? 'pendente';
                        if (!in_array($situacao, $statusPermitidos, true)) $situacao = 'pendente';
                        $observacaoCumprimento = trim((string)($observacoesCumprimento[$exigenciaId] ?? ''));
                        if ($statusCumprimentoSalvar === 'AGUARDANDO_APROVACAO' && $observacaoCumprimento === '') {
                            throw new Exception('Descreva as evidencias verificadas para cada exigencia herdada antes de enviar.');
                        }
                        $stmtAtualizar->execute([
                            ':status_item' => $situacao,
                            ':conforme' => $situacao === 'cumprida' ? 'sim' : 'nao',
                            ':observacao' => $observacaoCumprimento ?: null,
                            ':id' => $exigenciaId,
                            ':vistoria_id' => $vistoria_id,
                        ]);
                        $decisoesLog[] = $exigenciaId . ':' . $situacao;
                    }

                    $novasDescricoes = $_POST['nova_exigencia_descricao'] ?? [];
                    $novosItensNormam = $_POST['nova_exigencia_item_normam'] ?? [];
                    $novasObservacoes = $_POST['nova_exigencia_observacao'] ?? [];
                    $novasAs = $_POST['nova_exigencia_as'] ?? [];
                    $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem),0) FROM vistoria_exigencias WHERE vistoria_id=:id');
                    $stmtOrdem->execute([':id' => $vistoria_id]);
                    $proximaOrdem = (int)$stmtOrdem->fetchColumn();
                    $stmtNovaExigencia = $pdo->prepare("INSERT INTO vistoria_exigencias
                        (id,vistoria_id,ordem,item,descricao,conforme,observacao,item_normam,
                         antes_de_suspender,status_item)
                        VALUES (UUID(),:vistoria,:ordem,:item,:descricao,'nao',:observacao,:item_normam,:as,'pendente')");
                    foreach ($novasDescricoes as $indiceNova => $descricaoNova) {
                        $descricaoNova = trim((string)$descricaoNova);
                        if ($descricaoNova === '') continue;
                        $observacaoNova = trim((string)($novasObservacoes[$indiceNova] ?? ''));
                        if ($statusCumprimentoSalvar === 'AGUARDANDO_APROVACAO' && $observacaoNova === '') {
                            throw new Exception('Descreva a verificacao da nova exigencia antes de enviar.');
                        }
                        $proximaOrdem++;
                        $stmtNovaExigencia->execute([
                            ':vistoria' => $vistoria_id,
                            ':ordem' => $proximaOrdem,
                            ':item' => trim((string)($novosItensNormam[$indiceNova] ?? '')) ?: null,
                            ':descricao' => $descricaoNova,
                            ':observacao' => $observacaoNova ?: null,
                            ':item_normam' => trim((string)($novosItensNormam[$indiceNova] ?? '')) ?: null,
                            ':as' => !empty($novasAs[$indiceNova]) ? 1 : 0,
                        ]);
                        $decisoesLog[] = 'nova:' . $proximaOrdem;
                    }

                    $stmtAtualizaRelatorio = $pdo->prepare("UPDATE vistorias
                        SET data_vistoria = :data_vistoria,
                            prazo_exigencias_dias = CASE
                                WHEN :prazo IN (60, 90) THEN :prazo_valido
                                ELSE prazo_exigencias_dias
                            END,
                            observacoes_tecnicas = :observacoes,
                            status = :status
                        WHERE id = :id");
                    $stmtAtualizaRelatorio->execute([
                        ':data_vistoria' => $data_vistoria ?: date('Y-m-d'),
                        ':prazo' => $prazo_exigencias_dias,
                        ':prazo_valido' => $prazo_exigencias_dias,
                        ':observacoes' => $observacoes_tecnicas ?: null,
                        ':status' => $statusCumprimentoSalvar,
                        ':id' => $vistoria_id,
                    ]);
                    if ($statusCumprimentoSalvar === 'AGUARDANDO_APROVACAO') {
                        $pdo->prepare("UPDATE vistoria_retornos
                            SET status='RELATORIO_ENVIADO',relatorio_resultado_id=:relatorio
                            WHERE agendamento_id=:agendamento")
                            ->execute([':relatorio' => $vistoria_id, ':agendamento' => $agendamento_id]);
                    }
                    $pdo->commit();
                    log_atividade('relatorio_cumprimento_salvo', "Relatorio {$relatorioCumprimento['numero']} salvo com status {$statusCumprimentoSalvar}. Decisoes: " . implode(', ', $decisoesLog));
                    if (!$prazoCumprimentoValido) {
                        setMensagem('error', 'Os dados foram salvos como pendentes. Selecione a validade do relatorio: 60 ou 90 dias.');
                        redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode($vistoria_id));
                    }
                    setMensagem('success', $statusCumprimentoSalvar === 'AGUARDANDO_APROVACAO'
                        ? 'Relatorio de cumprimento enviado para analise.'
                        : 'Relatorio de cumprimento salvo.');
                    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode($vistoria_id) . '&salvo=1');
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    setMensagem('error', 'Erro ao salvar cumprimento: ' . $e->getMessage());
                    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode($vistoria_id));
                }
            }
        }

        if (!in_array($prazo_exigencias_dias, [60, 90], true)) {
            setMensagem('error', 'Selecione obrigatoriamente o prazo de validade do relatório: 60 ou 90 dias. O preenchimento foi preservado neste navegador.');
            $destinoErro = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id);
            if ($vistoria_id !== '') $destinoErro .= '&vistoria_id=' . urlencode($vistoria_id);
            redirecionar($destinoErro);
        }

        try {
            $pdo->beginTransaction();

            $stmtAg = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmtAg->execute([':id' => $agendamento_id]);
            $ag = $stmtAg->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                throw new Exception('Agendamento nao encontrado.');
            }

            if (($ag['vistoriador_id'] ?? '') !== ($_SESSION['usuario_id'] ?? '')) throw new Exception('Acesso negado. Este agendamento nao esta atribuido a voce.');
            if ($vistoria_id !== '') {
                $stmtEdicao = $pdo->prepare('SELECT v.*, a.vistoriador_id FROM vistorias v LEFT JOIN agendamentos a ON a.id=v.agendamento_id WHERE v.id=:id FOR UPDATE');
                $stmtEdicao->execute([':id' => $vistoria_id]);
                $vistoriaEdicao = $stmtEdicao->fetch(PDO::FETCH_ASSOC);
                if (!$vistoriaEdicao) throw new Exception('Relatorio nao encontrado.');
                $regraEdicao = avaliarEdicaoRelatorio($pdo, $vistoriaEdicao, (string)$_SESSION['usuario_id'], getCargo());
                if (!$regraEdicao['permitido']) throw new Exception($regraEdicao['mensagem']);
            }

            if ($prazo_exigencias_dias > 0) {
                $data_base_prazo = $data_vistoria ?: ($ag['data_vistoria'] ?? date('Y-m-d'));
                $data_base_obj = DateTimeImmutable::createFromFormat('!Y-m-d', $data_base_prazo);
                if (!$data_base_obj) {
                    throw new Exception('Data da vistoria invalida para calcular o prazo das exigencias.');
                }
                $prazo_padrao_exigencias = $data_base_obj
                    ->modify('+' . $prazo_exigencias_dias . ' days')
                    ->format('Y-m-d');
            }

            if (getCargo() === 'VISTORIADOR' && ($ag['vistoriador_id'] ?? '') !== ($_SESSION['usuario_id'] ?? '')) {
                throw new Exception('Acesso negado. Este agendamento não está atribuído a você.');
            }

            $blocos_permitidos_relatorio = blocosPermitidosRelatorioAction((string)($ag['tipo_vistoria'] ?? ''));

            // Somente um agendamento formal de retorno A/S cria continuidade.
            // Vistorias comuns são raízes independentes, mesmo para a mesma embarcação.
            $relatorio_anterior_id = trim((string)($ag['relatorio_origem_id'] ?? '')) ?: null;
            $finalidade_relatorio = $relatorio_anterior_id !== null
                ? 'CUMPRIMENTO_EXIGENCIAS'
                : 'VISTORIA';

            // Gerar texto automático
            $txt_gerado = "";
            $cumpridas = [];
            $transcritas = [];
            if (!empty($itens)) {
                foreach ($itens as $i => $item) {
                    $st = $status_items[$i] ?? 'inserida';
                    $ordem = (int)($ordens[$i] ?? ($i + 1));
                    if ($st === 'cumprida') {
                        $cumpridas[] = $ordem;
                    } elseif ($st === 'nao_cumprida_transcrita' || $st === 'cumprida_parcial_reescrita') {
                        $transcritas[] = $ordem;
                    }
                }
            }
            if (!empty($cumpridas)) {
                $txt_gerado .= "As exigências n.º " . implode(', ', $cumpridas) . " foram CUMPRIDAS.\n";
            }
            if (!empty($transcritas)) {
                $txt_gerado .= "As exigências n.º " . implode(', ', $transcritas) . " foram TRANSCRITAS ou REESCRITAS.";
            }

            if (empty($vistoria_id)) {
                // Gerar numero do relatorio conforme o tipo de vistoria
                $is_arqueacao = stripos($ag['tipo_vistoria'], 'arquea') !== false;
                $numero_relatorio = $is_arqueacao
                    ? gerarNumeroDocumento('REL-AP', 'AM-REL-AP')
                    : gerarNumeroDocumento('REL-V', 'AM-REL-V');

                // Criar nova vistoria com numero
                $vistoria_id = gerarUUID();
                $stmtV = $pdo->prepare("
                    INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, armador_id, operador_nome, agendamento_id, data_vistoria, prazo_exigencias_dias, observacoes_tecnicas, status, criado_por, relatorio_anterior_id, finalidade, texto_observacoes_geradas)
                    VALUES (:id, :numero, :embarcacao_id, :pessoa_id, :armador_id, :operador_nome, :agendamento_id, :data_vistoria, :prazo_exigencias_dias, :obs_tecnicas, :status, :criado_por, :rel_ant, :finalidade, :txt_gerado)
                ");
                $stmtV->execute([
                    ':id'             => $vistoria_id,
                    ':numero'         => $numero_relatorio,
                    ':embarcacao_id'  => $ag['embarcacao_id'],
                    ':pessoa_id'      => $ag['cliente_id'],
                    ':armador_id'     => $armador_id ?: null,
                    ':operador_nome'  => $operador_nome ?: null,
                    ':agendamento_id' => $agendamento_id,
                    ':data_vistoria'  => $data_vistoria ?: $ag['data_vistoria'],
                    ':prazo_exigencias_dias' => $prazo_exigencias_dias ?: null,
                    ':obs_tecnicas'   => $observacoes_tecnicas ?: null,
                    ':status'         => $status_vistoria,
                    ':criado_por'     => $_SESSION['usuario_id'],
                    ':rel_ant'        => $relatorio_anterior_id,
                    ':finalidade'     => $finalidade_relatorio,
                    ':txt_gerado'     => $txt_gerado ?: null,
                ]);
            } else {
                // Obter numero existente
                $stmtCheck = $pdo->prepare("SELECT numero FROM vistorias WHERE id = :id");
                $stmtCheck->execute([':id' => $vistoria_id]);
                $numero_relatorio = $stmtCheck->fetchColumn() ?: '';

                // Atualizar vistoria existente
                $stmtV = $pdo->prepare("
                    UPDATE vistorias
                    SET armador_id = :armador_id,
                        operador_nome = :operador_nome,
                        data_vistoria = :data_vistoria,
                        prazo_exigencias_dias = :prazo_exigencias_dias,
                        observacoes_tecnicas = :obs_tecnicas, status = :status,
                        aprovado_por = IF(:status_check IN ('APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA'), :aprovador, aprovado_por),
                        data_aprovacao = IF(:status2 IN ('APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA'), NOW(), data_aprovacao),
                        texto_observacoes_geradas = :txt_gerado
                    WHERE id = :id
                ");
                $stmtV->execute([
                    ':armador_id'   => $armador_id ?: null,
                    ':operador_nome' => $operador_nome ?: null,
                    ':data_vistoria'=> $data_vistoria ?: $ag['data_vistoria'],
                    ':prazo_exigencias_dias' => $prazo_exigencias_dias ?: null,
                    ':obs_tecnicas' => $observacoes_tecnicas ?: null,
                    ':status'       => $status_vistoria,
                    ':status_check' => $status_vistoria,
                    ':aprovador'    => $_SESSION['usuario_id'],
                    ':status2'      => $status_vistoria,
                    ':txt_gerado'   => $txt_gerado ?: null,
                    ':id'           => $vistoria_id,
                ]);

                // Remover exigencias antigas para reinserir
                $stmtDel = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                $stmtDel->execute([':vistoria_id' => $vistoria_id]);

                // Remover respostas antigas do checklist para gravar apenas o estado atual do formulario.
                $stmtDelChecklist = $pdo->prepare("DELETE FROM vistoria_checklist_respostas WHERE vistoria_id = :vistoria_id");
                $stmtDelChecklist->execute([':vistoria_id' => $vistoria_id]);
            }

            // Inserir respostas do Checklist e Exigencias associadas
            $ordem_global = 1;
            
            $stmtEx = $pdo->prepare("
                INSERT INTO vistoria_exigencias (id, vistoria_id, catalogo_id, bloco_vistoria, ordem, item, descricao, conforme, observacao, item_normam, vencimento, antes_de_suspender, status_item)
                VALUES (UUID(), :vistoria_id, :catalogo_id, :bloco_vistoria, :ordem, :item, :descricao, :conforme, :observacao, :item_normam, :vencimento, :antes_de_suspender, :status_item)
            ");
            
            if (!empty($checklist_ids)) {
                $stmtChecklist = $pdo->prepare("
                    INSERT INTO vistoria_checklist_respostas
                        (id, vistoria_id, catalogo_id, status, observacao, vencimento, item_normam, sem_prazo)
                    VALUES
                        (UUID(), :vistoria_id, :catalogo_id, :status, :observacao, :vencimento, :item_normam, :sem_prazo)
                    ON DUPLICATE KEY UPDATE
                        status = :status_upd,
                        observacao = :obs_upd,
                        vencimento = :venc_upd,
                        item_normam = :item_normam_upd,
                        sem_prazo = :sem_prazo_upd
                ");
                
                $stmtCatFetch = $pdo->prepare("SELECT descricao, item_normam, bloco_vistoria FROM exigencias_catalogo WHERE id = :id");
                
                foreach ($checklist_ids as $i => $cat_id) {
                    $status_resp = trim($checklist_status[$i] ?? '');
                    if (empty($status_resp)) continue; // não respondeu
                    
                    $obs = trim($checklist_obs[$i] ?? '') ?: null;
                    // O checkbox nomeado por catalogo e a fonte principal. O
                    // hidden indexado fica como compatibilidade com rascunhos
                    // e clientes anteriores.
                    $sem_prazo = ($checklist_sem_prazo_por_id[$cat_id] ?? '0') === '1'
                        || ($checklist_sem_prazo[$i] ?? '0') === '1';
                    $venc = ($status_resp === 'NAO_CONFORME' && !$sem_prazo) ? $prazo_padrao_exigencias : null;
                    $item_normam = trim($checklist_item_normam[$i] ?? '') ?: null;
                    
                    $stmtChecklist->execute([
                        ':vistoria_id' => $vistoria_id,
                        ':catalogo_id' => $cat_id,
                        ':status'      => $status_resp,
                        ':observacao'  => $obs,
                        ':vencimento'  => $venc,
                        ':item_normam' => $item_normam,
                        ':sem_prazo'   => $sem_prazo ? 1 : 0,
                        ':status_upd'  => $status_resp,
                        ':obs_upd'     => $obs,
                        ':venc_upd'    => $venc,
                        ':item_normam_upd' => $item_normam,
                        ':sem_prazo_upd' => $sem_prazo ? 1 : 0
                    ]);
                    
                    // Se nao conforme, adiciona a vistoria_exigencias automaticamente
                    if ($status_resp === 'NAO_CONFORME') {
                        $stmtCatFetch->execute([':id' => $cat_id]);
                        $cat_dados = $stmtCatFetch->fetch(PDO::FETCH_ASSOC);
                        $bloco_catalogo = $cat_dados['bloco_vistoria'] ?? 'flutuando';
                        if (!in_array($bloco_catalogo, $blocos_permitidos_relatorio, true)) {
                            continue;
                        }
                        
                        $item_texto = 'Item do Checklist';
                        if ($cat_dados && $cat_dados['item_normam']) {
                            $item_texto = 'Item Normam: ' . $cat_dados['item_normam'];
                        }
                        
                        $stmtEx->execute([
                            ':vistoria_id' => $vistoria_id,
                            ':catalogo_id' => $cat_id,
                            ':bloco_vistoria' => $bloco_catalogo,
                            ':ordem'       => $ordem_global++,
                            ':item'        => $item_texto,
                            ':descricao'   => $cat_dados['descricao'] ?? 'Sem descrição',
                            ':conforme'    => 'nao',
                            ':observacao'  => $obs,
                            ':item_normam'  => $item_normam ?: ($cat_dados['item_normam'] ?? null),
                            ':status_item' => 'pendente',
                            ':vencimento'  => $venc,
                            ':antes_de_suspender' => $sem_prazo ? 1 : 0,
                        ]);
                    }
                }
            }

            // Inserir exigencias Avulsas
            $total_avulsas = max(count($itens), count($descricoes));
            if ($total_avulsas > 0) {
                for ($i = 0; $i < $total_avulsas; $i++) {
                    $item = trim($itens[$i] ?? '');
                    $descricao_avulsa = trim($descricoes[$i] ?? '');
                    if (empty($descricao_avulsa) && empty($item)) continue;

                    $status_it = $status_items[$i] ?? 'inserida';
                    $sem_prazo = ($exigencias_sem_prazo[$i] ?? '0') === '1';
                    $venc = $sem_prazo ? null : $prazo_padrao_exigencias;
                    $bloco_avulso = $exigencias_blocos[$i] ?? 'flutuando';
                    if (!in_array($bloco_avulso, ['seco', 'flutuando', 'borda_livre', 'arqueacao'], true)) {
                        $bloco_avulso = $blocos_permitidos_relatorio[0] ?? 'flutuando';
                    }
                    if (!in_array($bloco_avulso, $blocos_permitidos_relatorio, true)) {
                        $bloco_avulso = $blocos_permitidos_relatorio[0] ?? 'flutuando';
                    }
                    $conforme = 'na';
                    if ($status_it === 'cumprida') $conforme = 'sim';
                    elseif (in_array($status_it, ['pendente', 'nao_cumprida_transcrita', 'cumprida_parcial_reescrita'])) $conforme = 'nao';

                    $stmtEx->execute([
                        ':vistoria_id' => $vistoria_id,
                        ':catalogo_id' => null,
                        ':bloco_vistoria' => $bloco_avulso,
                        ':ordem'       => $ordem_global++,
                        ':item'        => $item ?: $descricao_avulsa,
                        ':descricao'   => $descricao_avulsa ?: $item,
                        ':conforme'    => $conforme,
                        ':observacao'  => trim($observacoes_exig[$i] ?? '') ?: null,
                        ':item_normam'  => $item ?: null,
                        ':status_item' => $status_it,
                        ':vencimento'  => $venc,
                        ':antes_de_suspender' => $sem_prazo ? 1 : 0,
                    ]);
                }
            }

            // REGRA: Se status for APROVADA ou REPROVADA, avancar OS para Executado
            if (in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'])) {
                $stmtOs = $pdo->prepare("
                    UPDATE ordens_servico
                    SET status = 'executado'
                    WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')
                ");
                $stmtOs->execute([':agendamento_id' => $agendamento_id]);

                $stmtAgUpd = $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id");
                $stmtAgUpd->execute([':id' => $agendamento_id]);
            }

            $pdo->commit();

            log_atividade('relatorio_salvo', "Relatorio tecnico {$numero_relatorio} salvo para agendamento ID: {$agendamento_id}. Status: {$status_vistoria}.");
            $msg = 'Relatorio tecnico salvo com sucesso!';
            if (in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'])) {
                $msg .= ' Ordem de Servico avancada para EXECUTADA. Certificados liberados.';
            }
            setMensagem('success', $msg);
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode($vistoria_id) . '&salvo=1');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao salvar relatorio: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            setMensagem('error', 'Erro ao salvar relatorio tecnico: ' . $e->getMessage());
            $destinoErro = APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id);
            if ($vistoria_id !== '') $destinoErro .= '&vistoria_id=' . urlencode($vistoria_id);
            redirecionar($destinoErro);
        }
        break;

    // ==============================
    // ADICIONAR EXIGENCIA MANUAL NA ANALISE
    // ==============================
    case 'adicionar_exigencia_analista':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        setMensagem('error', 'Administradores e analistas podem revisar, mas somente o vistoriador atribuido pode alterar exigencias.');
        redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');

        $vistoria_id = trim($_POST['vistoria_id'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $item_normam = trim($_POST['item_normam'] ?? '');
        $observacao = trim($_POST['observacao'] ?? '');
        $bloco = trim($_POST['bloco_vistoria'] ?? 'flutuando');
        $sem_prazo = isset($_POST['sem_prazo']);
        bloquearMutacaoRelatorioAuditavel($pdo, $vistoria_id, APP_URL . 'documentacao/aprovacao_relatorios');

        if (empty($vistoria_id) || empty($descricao)) {
            setMensagem('error', 'Informe a descricao da exigencia manual.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        if (!in_array($bloco, ['seco', 'flutuando', 'borda_livre', 'arqueacao'], true)) {
            $bloco = 'flutuando';
        }

        try {
            $pdo->beginTransaction();

            $stmtV = $pdo->prepare("SELECT id, agendamento_id, status FROM vistorias WHERE id = :id FOR UPDATE");
            $stmtV->execute([':id' => $vistoria_id]);
            $vistoriaAnalise = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$vistoriaAnalise) {
                throw new Exception('Relatorio nao encontrado.');
            }
            if (($vistoriaAnalise['status'] ?? '') !== 'AGUARDANDO_APROVACAO') {
                throw new Exception('So e possivel adicionar exigencia em relatorio aguardando aprovacao.');
            }

            $stmtOrdem = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
            $stmtOrdem->execute([':vistoria_id' => $vistoria_id]);
            $ordem = (int)$stmtOrdem->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO vistoria_exigencias
                    (id, vistoria_id, catalogo_id, bloco_vistoria, ordem, item, descricao, conforme, observacao, item_normam, vencimento, antes_de_suspender, status_item)
                VALUES
                    (UUID(), :vistoria_id, NULL, :bloco, :ordem, :item, :descricao, 'nao', :observacao, :item_normam, NULL, :antes_de_suspender, 'pendente')
            ");
            $stmt->execute([
                ':vistoria_id' => $vistoria_id,
                ':bloco' => $bloco,
                ':ordem' => $ordem,
                ':item' => $item_normam ?: $descricao,
                ':descricao' => $descricao,
                ':observacao' => $observacao ?: null,
                ':item_normam' => $item_normam ?: null,
                ':antes_de_suspender' => $sem_prazo ? 1 : 0,
            ]);
            $pdo->commit();
            log_atividade('exigencia_manual_analista', "Exigencia manual adicionada na vistoria ID {$vistoria_id}.");
            setMensagem('success', 'Exigencia manual adicionada ao relatorio.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($vistoriaAnalise['agendamento_id']));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao adicionar exigencia manual do analista: ' . $e->getMessage());
            setMensagem('error', 'Erro ao adicionar exigencia manual: ' . $e->getMessage());
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }
        break;

    // ==============================
    // DECISOES ADMINISTRATIVAS SEM APROVACAO ELETRONICA (APENAS ADMIN)
    // ==============================
    case 'aprovar_ou_reprovar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        if (getCargo() !== 'ADMIN') {
            setMensagem('error', 'Apenas administradores podem tomar a decisao final do relatorio.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $id = $_POST['id'] ?? '';
        $decisao = $_POST['decisao'] ?? '';
        $status_vistoria = $_POST['status_vistoria'] ?? '';
        $resultado_relatorio = strtoupper(trim((string)($_POST['resultado_relatorio'] ?? '')));
        $versao_relatorio = trim((string)($_POST['versao_relatorio'] ?? ''));
        $observacao = sanitizar($_POST['observacao_admin'] ?? '');
        bloquearMutacaoRelatorioAuditavel($pdo, $id, APP_URL . 'documentacao/aprovacao_relatorios');

        if (empty($id)) {
            setMensagem('error', 'ID da vistoria invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $aprovando = $decisao === 'aprovar';
        $encaminhandoAs = $decisao === 'retorno_as';
        $statusesValidosAdmin = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'REPROVADA', 'CANCELADA'];
        if (empty($status_vistoria)) {
            if ($decisao === 'reprovar') {
                $status_vistoria = 'REPROVADA';
            }
        }

        if (!$aprovando && !$encaminhandoAs && !in_array($status_vistoria, $statusesValidosAdmin, true)) {
            setMensagem('error', 'Resultado final da vistoria invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        if ($status_vistoria === 'REPROVADA' && empty($observacao)) {
            setMensagem('error', 'A observacao e obrigatoria ao reprovar um relatorio.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $agendamento_id = null;

        try {
            $pdo->beginTransaction();

            $stmtV = $pdo->prepare("SELECT * FROM vistorias WHERE id = :id FOR UPDATE");
            $stmtV->execute([':id' => $id]);
            $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);
            if (!$vistoria) {
                throw new Exception('Vistoria nao encontrada.');
            }
            if (($vistoria['status'] ?? '') !== 'AGUARDANDO_APROVACAO') {
                throw new Exception('Somente relatorios aguardando aprovacao podem receber esta decisao.');
            }

            $agendamento_id = $vistoria['agendamento_id'] ?? null;
            if (empty($agendamento_id)) {
                throw new Exception('O relatorio precisa estar vinculado a um agendamento para concluir o fluxo.');
            }
            // Retornos A/S possuem novos agendamentos; a vigência é da cadeia.
            $relatorioVigente = obterRelatorioVigenteCadeia($pdo, (string)$id);
            if (!$relatorioVigente || $relatorioVigente['id'] !== $id) {
                $numeroVigente = trim((string)($relatorioVigente['numero'] ?? ''));
                throw new Exception(
                    'Este relatorio foi substituido. Abra o relatorio vigente'
                    . ($numeroVigente !== '' ? ' ' . $numeroVigente : '')
                    . ' para registrar a decisao.'
                );
            }

            if ($aprovando || $encaminhandoAs) {
                if (($vistoria['assinatura_status'] ?? 'PENDENTE') !== 'ASSINADO') {
                    throw new Exception('O relatorio precisa ser assinado antes da decisao administrativa.');
                }
                $resumoAprovacao = aprovacaoRelatorioResumoExigencias($pdo, $id);
                if ($aprovando && (int)$resumoAprovacao['pendentes_as'] > 0) {
                    throw new Exception('O relatorio possui A/S pendente e nao pode ser aprovado. Encaminhe-o para Retorno A/S.');
                }
                $resultadoValidar = $encaminhandoAs ? 'RETORNO_AS' : $resultado_relatorio;
                aprovacaoRelatorioValidarResultado($resumoAprovacao, $resultadoValidar, $versao_relatorio);
                $status_vistoria = $encaminhandoAs ? 'RETORNO_AS' : (string)$resumoAprovacao['status_esperado'];
            }

            $statusFinalizaFluxo = $aprovando || $encaminhandoAs || in_array($status_vistoria, ['REPROVADA','CANCELADA'], true);
            if (!$aprovando && in_array($status_vistoria, ['PENDENTE','REPROVADA','CANCELADA'], true) && ($vistoria['assinatura_status'] ?? '') === 'ASSINADO') {
                $pdo->prepare("UPDATE documento_assinaturas SET status='CANCELADO',cancelado_em=NOW(),cancelado_por=:usuario,motivo_cancelamento=:motivo WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='ASSINADO'")->execute([':usuario'=>$_SESSION['usuario_id'],':motivo'=>$observacao?:'Relatorio devolvido para correcao.',':id'=>$id]);
                $pdo->prepare("UPDATE vistorias SET assinatura_status='CANCELADO',assinatura_em=NULL,responsavel_assinatura_id=NULL WHERE id=:id")->execute([':id'=>$id]);
            }
            if ($encaminhandoAs) {
                encaminharRelatorioParaRetornoAS(
                    $pdo,
                    $vistoria,
                    (string)$_SESSION['usuario_id'],
                    $observacao ?: null
                );
            } else {
                $stmt = $pdo->prepare("
                    UPDATE vistorias
                    SET status = :status,
                        observacao_admin = :obs,
                        aprovado_por = IF(:finaliza = 1, :aprovador, aprovado_por),
                        data_aprovacao = IF(:finaliza_data = 1, NOW(), data_aprovacao)
                    WHERE id = :id AND status='AGUARDANDO_APROVACAO'
                ");
                $stmt->execute([
                    ':status' => $status_vistoria,
                    ':obs' => $observacao ?: null,
                    ':finaliza' => $statusFinalizaFluxo ? 1 : 0,
                    ':aprovador' => $_SESSION['usuario_id'],
                    ':finaliza_data' => $statusFinalizaFluxo ? 1 : 0,
                    ':id' => $id
                ]);
                if ($stmt->rowCount() !== 1) {
                    throw new Exception('O relatorio foi alterado por outra operacao.');
                }
            }

            if ($status_vistoria === 'CANCELADA') {
                if (($vistoria['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS') {
                    $stmtCancelarRetorno = $pdo->prepare("UPDATE vistoria_retornos
                        SET status='CANCELADO',
                            motivo_cancelamento=:motivo,
                            cancelado_por=:usuario,
                            cancelado_em=NOW()
                        WHERE relatorio_resultado_id=:relatorio
                           OR agendamento_id=:agendamento");
                    $stmtCancelarRetorno->execute([
                        ':motivo' => $observacao ?: 'Relatorio de cumprimento cancelado pelo administrador.',
                        ':usuario' => $_SESSION['usuario_id'],
                        ':relatorio' => $id,
                        ':agendamento' => $agendamento_id,
                    ]);
                    if ($stmtCancelarRetorno->rowCount() !== 1) {
                        throw new Exception('O retorno A/S foi alterado por outra operacao.');
                    }
                    $origemReabrir = trim((string)($vistoria['relatorio_anterior_id'] ?? ''));
                    if ($origemReabrir === '') {
                        throw new Exception('O relatorio de cumprimento nao possui origem auditavel.');
                    }
                    criarPendenciaRetornoAS($pdo, $origemReabrir, (string)$_SESSION['usuario_id']);
                }
                $pdo->prepare("UPDATE ordens_servico
                    SET status='cancelado'
                    WHERE agendamento_id=:agendamento AND status<>'cancelado'")
                    ->execute([':agendamento' => $agendamento_id]);
                $pdo->prepare("UPDATE agendamentos
                    SET status='cancelado'
                    WHERE id=:agendamento AND status<>'cancelado'")
                    ->execute([':agendamento' => $agendamento_id]);
            }

            if ($aprovando) {
                if (($vistoria['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS') {
                    concluirRetornoDoRelatorio($pdo, $id);
                }
            }

            if ($agendamento_id && $statusFinalizaFluxo && $status_vistoria !== 'CANCELADA') {
                $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')")->execute([':agendamento_id' => $agendamento_id]);
                $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $agendamento_id]);
            }

            $pdo->commit();

            log_atividade('relatorio_decisao_admin', "Relatorio ID {$id} definido como {$status_vistoria}.");
            $mensagensStatus = [
                'PENDENTE' => 'Relatorio mantido como pendente.',
                'AGUARDANDO_APROVACAO' => 'Relatorio mantido aguardando aprovacao.',
                'APROVADA' => 'Relatorio aprovado com sucesso.',
                'APROVADA_COM_EXIGENCIAS' => 'Relatorio aprovado com exigencias com sucesso.',
                'RETORNO_AS' => 'Relatorio encaminhado para Retorno A/S. Agende a nova visita pelo painel.',
                'REPROVADA' => 'Relatorio reprovado. Agendamento concluido.',
                'CANCELADA' => 'Relatorio cancelado.'
            ];
            setMensagem($status_vistoria === 'REPROVADA' ? 'error' : 'success', $mensagensStatus[$status_vistoria] ?? 'Resultado final salvo.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao salvar decisao admin da vistoria: ' . $e->getMessage());
            $mensagensRevisao = [
                'O relatorio ou suas exigencias foram alterados. Atualize a pagina e revise novamente antes de aprovar.',
                'O relatorio possui A/S pendente e nao pode ser aprovado. Encaminhe-o para Retorno A/S.',
                'O relatorio possui A/S pendente e deve ser encaminhado para Retorno A/S.',
                'O relatorio possui exigencias comuns abertas e deve ser aprovado com exigencias.',
                'O relatorio nao possui exigencias abertas e deve ser aprovado sem exigencias.',
                'Somente relatorios aguardando aprovacao podem receber esta decisao.',
                'Somente o relatorio vigente da cadeia pode receber uma decisao.',
            ];
            $mensagemErro = $e->getMessage();
            $mensagemPublica = in_array($mensagemErro, $mensagensRevisao, true)
                || str_starts_with($mensagemErro, 'Este relatorio foi substituido.')
                ? $mensagemErro
                : 'Erro ao processar decisao do relatorio. Tente novamente.';
            setMensagem('error', $mensagemPublica);
            if (isset($relatorioVigente) && !empty($relatorioVigente['id']) && $relatorioVigente['id'] !== $id) {
                redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id='
                    . urlencode((string)$relatorioVigente['agendamento_id'])
                    . '&vistoria_id=' . urlencode((string)$relatorioVigente['id']));
            }
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode((string)$agendamento_id) . '&vistoria_id=' . urlencode($id));
        }

        if ($aprovando) {
            $liberacao = avaliarLiberacaoCertificacao($pdo, $id);
            if (!empty($liberacao['permitido'])) {
                redirecionar(APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode((string)$agendamento_id)
                    . '&vistoria_id=' . urlencode($id));
            }
            setMensagem('warning', $liberacao['mensagem'] ?? 'Certificacao permanece bloqueada.');
            if (($liberacao['possui_as'] ?? false) === true) {
                redirecionar(APP_URL . 'agendamentos/form?relatorio_origem_id=' . urlencode($id));
            }
        }
        if ($encaminhandoAs) {
            redirecionar(APP_URL . 'dashboard#retornos-as');
        }
        redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode($id));
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'vistorias');
}
