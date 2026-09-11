<?php
/**
 * MÓDULO SGQ: GESTÃO DE RISCOS E OPORTUNIDADES (ISO 9001:2015 CLÁUSULA 6.1)
 * Arquivo: modules/sgq/riscos_actions.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

$action = trim((string)($_GET['action'] ?? ''));

function calcularNivelRisco(int $prob, int $impacto): string {
    $score = $prob * $impacto;
    if ($score >= 16) return 'CRITICO';
    if ($score >= 10) return 'ALTO';
    if ($score >= 5)  return 'MEDIO';
    return 'BAIXO';
}

if ($action === 'salvar') {
    $id                = trim((string)($_POST['id'] ?? ''));
    $processoSetor     = trim((string)($_POST['processo_setor'] ?? ''));
    $tipoRisco         = trim((string)($_POST['tipo_risco'] ?? 'AMEACA'));
    $descricaoRisco    = trim((string)($_POST['descricao_risco'] ?? ''));
    $causas            = trim((string)($_POST['causas'] ?? ''));
    $impactoConseq     = trim((string)($_POST['impacto_consequencias'] ?? ''));
    $probabilidade     = max(1, min(5, (int)($_POST['probabilidade'] ?? 3)));
    $impacto           = max(1, min(5, (int)($_POST['impacto'] ?? 3)));
    $acaoMitigacao     = trim((string)($_POST['acao_mitigacao'] ?? ''));
    $responsavelNome   = trim((string)($_POST['responsavel_nome'] ?? ''));
    $prazoRevisao      = trim((string)($_POST['prazo_revisao'] ?? '')) ?: null;
    $statusTratamento  = trim((string)($_POST['status_tratamento'] ?? 'EM_MITIGACAO'));

    if ($descricaoRisco === '' || $processoSetor === '' || $acaoMitigacao === '' || $responsavelNome === '') {
        setMensagem('error', 'Preencha todos os campos obrigatórios (Processo, Descrição, Ação de Mitigação e Responsável).');
        redirecionar(APP_URL . 'sgq/riscos');
    }

    $nivelRisco = calcularNivelRisco($probabilidade, $impacto);

    try {
        if ($id !== '') {
            $stmt = $pdo->prepare("
                UPDATE sgq_matriz_riscos
                SET processo_setor = :processo,
                    tipo_risco = :tipo,
                    descricao_risco = :descricao,
                    causas = :causas,
                    impacto_consequencias = :conseq,
                    probabilidade = :prob,
                    impacto = :impacto,
                    nivel_risco = :nivel,
                    acao_mitigacao = :acao,
                    responsavel_nome = :resp,
                    prazo_revisao = :prazo,
                    status_tratamento = :status
                WHERE id = :id
            ");
            $stmt->execute([
                ':processo'  => $processoSetor,
                ':tipo'      => $tipoRisco,
                ':descricao' => $descricaoRisco,
                ':causas'    => $causas ?: null,
                ':conseq'    => $impactoConseq ?: null,
                ':prob'      => $probabilidade,
                ':impacto'   => $impacto,
                ':nivel'     => $nivelRisco,
                ':acao'      => $acaoMitigacao,
                ':resp'      => $responsavelNome,
                ':prazo'     => $prazoRevisao,
                ':status'    => $statusTratamento,
                ':id'        => $id,
            ]);
            setMensagem('success', 'Risco/Oportunidade atualizado com sucesso!');
        } else {
            $novoId = gerarUUID();
            $codigo = gerarNumeroDocumento('RSK', 'RSK');
            $stmt = $pdo->prepare("
                INSERT INTO sgq_matriz_riscos (
                    id, codigo_risco, processo_setor, tipo_risco, descricao_risco,
                    causas, impacto_consequencias, probabilidade, impacto, nivel_risco,
                    acao_mitigacao, responsavel_nome, prazo_revisao, status_tratamento
                ) VALUES (
                    :id, :cod, :processo, :tipo, :descricao,
                    :causas, :conseq, :prob, :impacto, :nivel,
                    :acao, :resp, :prazo, :status
                )
            ");
            $stmt->execute([
                ':id'        => $novoId,
                ':cod'       => $codigo,
                ':processo'  => $processoSetor,
                ':tipo'      => $tipoRisco,
                ':descricao' => $descricaoRisco,
                ':causas'    => $causas ?: null,
                ':conseq'    => $impactoConseq ?: null,
                ':prob'      => $probabilidade,
                ':impacto'   => $impacto,
                ':nivel'     => $nivelRisco,
                ':acao'      => $acaoMitigacao,
                ':resp'      => $responsavelNome,
                ':prazo'     => $prazoRevisao,
                ':status'    => $statusTratamento,
            ]);
            setMensagem('success', "Risco {$codigo} cadastrado com sucesso!");
        }
    } catch (Exception $e) {
        error_log('Erro ao salvar risco SGQ: ' . $e->getMessage());
        setMensagem('error', 'Falha ao salvar risco: ' . $e->getMessage());
    }

    redirecionar(APP_URL . 'sgq/riscos');
}

if ($action === 'excluir') {
    $id = trim((string)($_POST['id'] ?? ''));
    if ($id !== '') {
        try {
            $stmt = $pdo->prepare("DELETE FROM sgq_matriz_riscos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setMensagem('success', 'Risco excluído da matriz com sucesso!');
        } catch (Exception $e) {
            setMensagem('error', 'Erro ao excluir risco: ' . $e->getMessage());
        }
    }
    redirecionar(APP_URL . 'sgq/riscos');
}

redirecionar(APP_URL . 'sgq/riscos');
