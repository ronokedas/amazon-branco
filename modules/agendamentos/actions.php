<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: actions.php - Processar POST/GET (insert/update/confirmar/gerar OS/cancelar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('agendamentos');

// Rota AJAX: buscar dados da proposta
if (isset($_GET['action']) && $_GET['action'] === 'buscar_proposta') {
    header('Content-Type: application/json; charset=utf-8');
    $proposta_id = $_GET['proposta_id'] ?? '';
    
    if (empty($proposta_id)) {
        echo json_encode(['success' => false, 'message' => 'ID da proposta não informado.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.cliente_id, p.responsavel_fechamento_nome, p.responsavel_fechamento_telefone, c.nome AS cliente_nome
            FROM propostas p
            INNER JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $proposta_id]);
        $proposta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$proposta) {
            echo json_encode(['success' => false, 'message' => 'Proposta não encontrada.']);
            exit;
        }
        
        // Buscar embarcações da proposta
        $stmtEmb = $pdo->prepare("
            SELECT e.id, e.nome 
            FROM propostas_embarcacoes pe
            INNER JOIN embarcacoes e ON pe.embarcacao_id = e.id
            WHERE pe.proposta_id = :id
        ");
        $stmtEmb->execute([':id' => $proposta_id]);
        $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar serviços da proposta
        $stmtSrv = $pdo->prepare("
            SELECT s.nome 
            FROM propostas_servicos ps
            INNER JOIN servicos s ON ps.servico_id = s.id
            WHERE ps.proposta_id = :id
        ");
        $stmtSrv->execute([':id' => $proposta_id]);
        $servicos = array_column($stmtSrv->fetchAll(PDO::FETCH_ASSOC), 'nome');
        
        echo json_encode([
            'success'          => true,
            'cliente_id'       => $proposta['cliente_id'],
            'cliente_nome'     => $proposta['cliente_nome'],
            'contato_nome'     => $proposta['responsavel_fechamento_nome'] ?? null,
            'contato_telefone' => $proposta['responsavel_fechamento_telefone'] ?? null,
            'embarcacoes'      => $embarcacoes,
            'embarcacao_id'    => !empty($embarcacoes) ? $embarcacoes[0]['id'] : null,
            'tipo_vistoria'    => !empty($servicos) ? implode(', ', $servicos) : '',
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log('Erro ao buscar proposta: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno.']);
        exit;
    }
}

// Validar CSRF token para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'agendamentos');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function validarCamposAgendamento(string $data_vistoria, string $embarcacao_id, string $cliente_id, string $tipo_vistoria): array
{
    $errosCampos = [];

    if (empty($cliente_id)) {
        $errosCampos['cliente_id'] = 'Selecione o cliente.';
    }

    if (empty($embarcacao_id)) {
        $errosCampos['embarcacao_id'] = 'Selecione a embarcação.';
    }

    if (empty($tipo_vistoria)) {
        $errosCampos['tipo_vistoria'] = 'Informe o tipo de vistoria.';
    }

    if (empty($data_vistoria)) {
        $errosCampos['data_vistoria'] = 'Informe a data da vistoria.';
    } elseif ($data_vistoria < date('Y-m-d')) {
        $errosCampos['data_vistoria'] = 'A data da vistoria não pode ser no passado.';
    }

    return $errosCampos;
}

function obterTipoVistoriaDaProposta(PDO $pdo, ?string $proposta_id): string
{
    if (empty($proposta_id)) {
        return '';
    }

    $stmt = $pdo->prepare("
        SELECT s.nome
        FROM propostas_servicos ps
        INNER JOIN servicos s ON ps.servico_id = s.id
        WHERE ps.proposta_id = :id
        ORDER BY s.nome ASC
    ");
    $stmt->execute([':id' => $proposta_id]);
    $servicos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nome');

    return !empty($servicos) ? implode(', ', $servicos) : '';
}

function obterDadosResponsavelDaProposta(PDO $pdo, ?string $proposta_id): array
{
    if (empty($proposta_id)) {
        return ['contato_nome' => null, 'contato_telefone' => null];
    }

    $stmt = $pdo->prepare("SELECT responsavel_fechamento_nome, responsavel_fechamento_telefone FROM propostas WHERE id = :id");
    $stmt->execute([':id' => $proposta_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'contato_nome' => $dados['responsavel_fechamento_nome'] ?? null,
        'contato_telefone' => $dados['responsavel_fechamento_telefone'] ?? null,
    ];
}

function obterAgendamento(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    return $agendamento ?: null;
}

function horaVistoriaValida(?string $hora_vistoria): bool
{
    if (empty($hora_vistoria)) {
        return true;
    }

    if (!preg_match('/^([01]\d|2[0-3]):([03]0)(?::00)?$/', $hora_vistoria, $matches)) {
        return false;
    }

    return in_array($matches[2], ['00', '30'], true);
}

switch ($action) {

    // ==================== INSERIR ====================
    case 'inserir':
        try {
            $proposta_id     = !empty($_POST['proposta_id']) ? $_POST['proposta_id'] : null;
            $embarcacao_id   = $_POST['embarcacao_id'] ?? '';
            $cliente_id      = $_POST['cliente_id'] ?? '';
            $armador_id      = $_POST['armador_id'] ?? null;
            $operador_nome   = trim(sanitizar($_POST['operador_nome'] ?? ''));
            $tipo_vistoria   = sanitizar($_POST['tipo_vistoria'] ?? '');
            $data_vistoria   = $_POST['data_vistoria'] ?? '';
            $hora_vistoria   = $_POST['hora_vistoria'] ?? null;
            $local           = sanitizar($_POST['local'] ?? '');
            $contato_nome    = sanitizar($_POST['contato_nome'] ?? '');
            $contato_telefone = preg_replace('/\D/', '', $_POST['contato_telefone'] ?? '') ?: null;
            $observacoes     = sanitizar($_POST['observacoes'] ?? '');
            $vistoriador_id  = $_POST['vistoriador_id'] ?? null;
            $vendedor_id     = $_POST['vendedor_id'] ?? null;
            $relatorio_origem_id = trim((string)($_POST['relatorio_origem_id'] ?? ''));

            if ($relatorio_origem_id !== '' && $cargo !== 'ADMIN') {
                throw new RuntimeException('Somente o administrador pode criar retorno de cumprimento A/S.');
            }

            if (!empty($proposta_id) && $relatorio_origem_id === '') {
                $tipoVistoriaProposta = obterTipoVistoriaDaProposta($pdo, $proposta_id);
                if (!empty($tipoVistoriaProposta)) {
                    $tipo_vistoria = $tipoVistoriaProposta;
                }
                $responsavelProposta = obterDadosResponsavelDaProposta($pdo, $proposta_id);
                if (!empty($responsavelProposta['contato_nome'])) {
                    $contato_nome = $responsavelProposta['contato_nome'];
                }
                if (!empty($responsavelProposta['contato_telefone'])) {
                    $contato_telefone = preg_replace('/\D/', '', $responsavelProposta['contato_telefone']) ?: null;
                }
            }
            if (!horaVistoriaValida($hora_vistoria)) {
                setMensagem('error', 'Selecione um horário redondo, de meia em meia hora.');
                redirecionar(APP_URL . 'agendamentos/form');
            }
            $hora_vistoria = !empty($hora_vistoria) ? substr($hora_vistoria, 0, 5) : null;

            if ($cargo === 'VISTORIADOR') {
                $vistoriador_id = $_SESSION['usuario_id'];
            }

            $errosCampos = validarCamposAgendamento($data_vistoria, $embarcacao_id, $cliente_id, $tipo_vistoria);
            if (empty($vistoriador_id)) {
                $errosCampos['vistoriador_id'] = 'Selecione o vistoriador responsável.';
            }
            if (!empty($errosCampos)) {
                setMensagem('error', 'Revise os campos destacados e tente novamente.', $errosCampos);
                redirecionar(APP_URL . 'agendamentos/form');
            }

            if (empty($embarcacao_id) || empty($cliente_id) || empty($tipo_vistoria) || empty($data_vistoria)) {
                setMensagem('error', 'Preencha todos os campos obrigatórios (cliente, embarcação, tipo e data).');
                redirecionar(APP_URL . 'agendamentos/form');
            }

            // Validar que a data da vistoria não está no passado
            if ($data_vistoria < date('Y-m-d')) {
                setMensagem('error', 'A data da vistoria não pode ser no passado.');
                redirecionar(APP_URL . 'agendamentos/form');
            }

            // Se VENDEDOR, atribuir vendedor_id automaticamente
            if ($cargo === 'VENDEDOR') {
                $vendedor_id = $_SESSION['usuario_id'];
            }

            if ($relatorio_origem_id !== '') {
                $stmtOrigem = $pdo->prepare("SELECT v.embarcacao_id,COALESCE(a.cliente_id,v.pessoa_id) cliente_id
                    FROM vistorias v LEFT JOIN agendamentos a ON a.id=v.agendamento_id
                    WHERE v.id=:id AND v.status='APROVADA_COM_EXIGENCIAS'
                      AND EXISTS (
                        SELECT 1 FROM vistoria_exigencias ve
                        WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1
                          AND ve.conforme='nao' AND ve.status_item<>'cumprida'
                      ) LIMIT 1");
                $stmtOrigem->execute([':id' => $relatorio_origem_id]);
                $origem = $stmtOrigem->fetch(PDO::FETCH_ASSOC);
                if (!$origem || $origem['embarcacao_id'] !== $embarcacao_id || $origem['cliente_id'] !== $cliente_id) {
                    throw new RuntimeException('Os dados do retorno A/S não correspondem ao relatório de origem.');
                }
                $stmtRetorno = $pdo->prepare("SELECT status FROM vistoria_retornos WHERE relatorio_origem_id=:id LIMIT 1");
                $stmtRetorno->execute([':id' => $relatorio_origem_id]);
                if ($stmtRetorno->fetchColumn() !== 'PENDENTE_AGENDAMENTO') {
                    throw new RuntimeException('Este retorno A/S já possui agendamento ou foi concluído.');
                }
                $tipo_vistoria = 'Cumprimento de A/S';
            }

            $pdo->beginTransaction();
            if ($relatorio_origem_id !== '') {
                $stmtRetornoLock = $pdo->prepare("SELECT id,status,agendamento_id
                    FROM vistoria_retornos
                    WHERE relatorio_origem_id=:origem
                    FOR UPDATE");
                $stmtRetornoLock->execute([':origem' => $relatorio_origem_id]);
                $retornoLock = $stmtRetornoLock->fetch(PDO::FETCH_ASSOC);
                if (!$retornoLock || $retornoLock['status'] !== 'PENDENTE_AGENDAMENTO'
                    || !empty($retornoLock['agendamento_id'])) {
                    throw new RuntimeException('Este retorno A/S ja possui agendamento ou foi concluido.');
                }
            }
            $novoAgendamentoId = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO agendamentos (
                    id, proposta_id, relatorio_origem_id, embarcacao_id, cliente_id, armador_id, operador_nome, vistoriador_id, vendedor_id,
                    tipo_vistoria, data_vistoria, hora_vistoria, local,
                    contato_nome, contato_telefone, status, observacoes, criado_por
                ) VALUES (
                    :id, :proposta_id, :relatorio_origem_id, :embarcacao_id, :cliente_id, :armador_id, :operador_nome, :vistoriador_id, :vendedor_id,
                    :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                    :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                )
            ");
            $stmt->execute([
                ':id'               => $novoAgendamentoId,
                ':proposta_id'     => $proposta_id,
                ':relatorio_origem_id' => $relatorio_origem_id ?: null,
                ':embarcacao_id'   => $embarcacao_id,
                ':cliente_id'      => $cliente_id,
                ':armador_id'      => $armador_id ?: null,
                ':operador_nome'   => $operador_nome ?: null,
                ':vistoriador_id'  => $vistoriador_id ?: null,
                ':vendedor_id'     => $vendedor_id ?: null,
                ':tipo_vistoria'   => $tipo_vistoria,
                ':data_vistoria'   => $data_vistoria,
                ':hora_vistoria'   => $hora_vistoria ?: null,
                ':local'           => $local ?: null,
                ':contato_nome'    => $contato_nome ?: null,
                ':contato_telefone' => $contato_telefone ?: null,
                ':observacoes'     => $observacoes ?: null,
                ':criado_por'      => $_SESSION['usuario_id'],
            ]);
            if ($relatorio_origem_id !== '') {
                $stmtVincularRetorno = $pdo->prepare("UPDATE vistoria_retornos
                    SET status='AGENDADO',agendamento_id=:agendamento
                    WHERE relatorio_origem_id=:origem
                      AND status='PENDENTE_AGENDAMENTO'
                      AND agendamento_id IS NULL");
                $stmtVincularRetorno->execute([
                    ':agendamento' => $novoAgendamentoId,
                    ':origem' => $relatorio_origem_id,
                ]);
                if ($stmtVincularRetorno->rowCount() !== 1) {
                    throw new RuntimeException('O retorno A/S foi agendado por outra operacao.');
                }
            }
            $pdo->commit();

            log_atividade('agendamento_criado', "Agendamento de {$tipo_vistoria} criado para data {$data_vistoria}.");
            setMensagem('success', 'Agendamento criado com sucesso!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao criar agendamento.');
            redirecionar(APP_URL . 'agendamentos/form');
        }
        break;

    // ==================== EDITAR ====================
    case 'editar':
        try {
        // VISTORIADOR não pode editar agendamentos
        if ($cargo === 'VISTORIADOR') {
            setMensagem('error', 'Acesso negado. Vistoriadores não podem editar agendamentos.');
            redirecionar(APP_URL . 'agendamentos');
        }
            $id              = $_POST['id'] ?? '';
            $proposta_id     = !empty($_POST['proposta_id']) ? $_POST['proposta_id'] : null;
            $embarcacao_id   = $_POST['embarcacao_id'] ?? '';
            $cliente_id      = $_POST['cliente_id'] ?? '';
            $armador_id      = $_POST['armador_id'] ?? null;
            $operador_nome   = trim(sanitizar($_POST['operador_nome'] ?? ''));
            $tipo_vistoria   = sanitizar($_POST['tipo_vistoria'] ?? '');
            $data_vistoria   = $_POST['data_vistoria'] ?? '';
            $hora_vistoria   = $_POST['hora_vistoria'] ?? null;
            $local           = sanitizar($_POST['local'] ?? '');
            $contato_nome    = sanitizar($_POST['contato_nome'] ?? '');
            $contato_telefone = preg_replace('/\D/', '', $_POST['contato_telefone'] ?? '') ?: null;
            $observacoes     = sanitizar($_POST['observacoes'] ?? '');
            $vistoriador_id  = $_POST['vistoriador_id'] ?? null;
            $vendedor_id     = $_POST['vendedor_id'] ?? null;

            $agendamentoAtual = !empty($id) ? obterAgendamento($pdo, $id) : null;
            if ($agendamentoAtual && !empty($agendamentoAtual['relatorio_origem_id']) && $cargo !== 'ADMIN') {
                throw new RuntimeException('Somente o administrador pode alterar um retorno A/S.');
            }
            if ($agendamentoAtual && !empty($agendamentoAtual['proposta_id'])) {
                $proposta_id = $agendamentoAtual['proposta_id'];
                $embarcacao_id = $agendamentoAtual['embarcacao_id'];
                $cliente_id = $agendamentoAtual['cliente_id'];
                $vendedor_id = $agendamentoAtual['vendedor_id'] ?? $vendedor_id;
            }

            if (!empty($proposta_id)) {
                $tipoVistoriaProposta = obterTipoVistoriaDaProposta($pdo, $proposta_id);
                if (!empty($tipoVistoriaProposta)) {
                    $tipo_vistoria = $tipoVistoriaProposta;
                }
                $responsavelProposta = obterDadosResponsavelDaProposta($pdo, $proposta_id);
                if (!empty($responsavelProposta['contato_nome'])) {
                    $contato_nome = $responsavelProposta['contato_nome'];
                }
                if (!empty($responsavelProposta['contato_telefone'])) {
                    $contato_telefone = preg_replace('/\D/', '', $responsavelProposta['contato_telefone']) ?: null;
                }
            }
            if (!horaVistoriaValida($hora_vistoria)) {
                setMensagem('error', 'Selecione um horário redondo, de meia em meia hora.');
                redirecionar(APP_URL . 'agendamentos/form?id=' . urlencode($id));
            }
            $hora_vistoria = !empty($hora_vistoria) ? substr($hora_vistoria, 0, 5) : null;

            if ($cargo === 'VISTORIADOR') {
                $vistoriador_id = $_SESSION['usuario_id'];
            }

            $errosCampos = validarCamposAgendamento($data_vistoria, $embarcacao_id, $cliente_id, $tipo_vistoria);
            if (empty($id)) {
                $errosCampos['id'] = 'Agendamento não informado.';
            }
            if (empty($vistoriador_id)) {
                $errosCampos['vistoriador_id'] = 'Selecione o vistoriador responsável.';
            }

            if (!empty($errosCampos)) {
                setMensagem('error', 'Revise os campos destacados e tente novamente.', $errosCampos);
                $destino = !empty($id)
                    ? APP_URL . 'agendamentos/form?id=' . urlencode($id)
                    : APP_URL . 'agendamentos';
                redirecionar($destino);
            }

            if (empty($id) || empty($embarcacao_id) || empty($cliente_id) || empty($tipo_vistoria) || empty($data_vistoria)) {
                setMensagem('error', 'Dados incompletos para atualização.');
                redirecionar(APP_URL . 'agendamentos');
            }

            if ($cargo === 'VISTORIADOR') {
                $vistoriador_id = $_SESSION['usuario_id'];
            }

            $stmt = $pdo->prepare("
                UPDATE agendamentos 
                SET proposta_id = :proposta_id,
                    embarcacao_id = :embarcacao_id,
                    cliente_id = :cliente_id,
                    armador_id = :armador_id,
                    operador_nome = :operador_nome,
                    vistoriador_id = :vistoriador_id,
                    vendedor_id = :vendedor_id,
                    tipo_vistoria = :tipo_vistoria,
                    data_vistoria = :data_vistoria,
                    hora_vistoria = :hora_vistoria,
                    local = :local,
                    contato_nome = :contato_nome,
                    contato_telefone = :contato_telefone,
                    observacoes = :observacoes
                WHERE id = :id
            ");
            $stmt->execute([
                ':proposta_id'     => $proposta_id,
                ':embarcacao_id'   => $embarcacao_id,
                ':cliente_id'      => $cliente_id,
                ':armador_id'      => $armador_id ?: null,
                ':operador_nome'   => $operador_nome ?: null,
                ':vistoriador_id'  => $vistoriador_id ?: null,
                ':vendedor_id'     => $vendedor_id ?: null,
                ':tipo_vistoria'   => $tipo_vistoria,
                ':data_vistoria'   => $data_vistoria,
                ':hora_vistoria'   => $hora_vistoria ?: null,
                ':local'           => $local ?: null,
                ':contato_nome'    => $contato_nome ?: null,
                ':contato_telefone' => $contato_telefone ?: null,
                ':observacoes'     => $observacoes ?: null,
                ':id'              => $id,
            ]);

            log_atividade('agendamento_editado', "Agendamento ID: {$id} atualizado.");
            setMensagem('success', 'Agendamento atualizado com sucesso!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            error_log('Erro ao editar agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atualizar agendamento.');
            redirecionar(APP_URL . 'agendamentos/form?id=' . urlencode($id));
        }
        break;

    // ==================== CONFIRMAR E GERAR OS ====================
    case 'confirmar':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                setMensagem('error', 'Requisição inválida.');
                redirecionar(APP_URL . 'agendamentos');
            }

            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                setMensagem('error', 'ID do agendamento não informado.');
                redirecionar(APP_URL . 'agendamentos');
            }

            // Buscar dados do agendamento
            $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ag = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                setMensagem('error', 'Agendamento não encontrado.');
                redirecionar(APP_URL . 'agendamentos');
            }
            if (!empty($ag['relatorio_origem_id']) && $cargo !== 'ADMIN') {
                setMensagem('error', 'Somente o administrador pode confirmar e gerar a OS de um retorno A/S.');
                redirecionar(APP_URL . 'agendamentos');
            }

            if ($ag['status'] !== 'pendente') {
                setMensagem('error', 'Apenas agendamentos pendentes podem ser confirmados. Status atual: ' . $ag['status']);
                redirecionar(APP_URL . 'agendamentos');
            }

            if (empty($ag['vistoriador_id'])) {
                setMensagem('error', 'Antes de confirmar, atribua um vistoriador responsável ao agendamento.');
                redirecionar(APP_URL . 'agendamentos/form?id=' . urlencode($id));
            }

            // Verificar se já existe OS para este agendamento
            $stmtCheck = $pdo->prepare("SELECT id FROM ordens_servico WHERE agendamento_id = :agendamento_id");
            $stmtCheck->execute([':agendamento_id' => $id]);
            $os_existente = $stmtCheck->fetch();

            if ($os_existente) {
                setMensagem('error', 'Já existe uma Ordem de Serviço para este agendamento.');
                redirecionar(APP_URL . 'agendamentos');
            }

            $pdo->beginTransaction();

            // Gerar número da OS
            $numero_os = gerarNumeroDocumento('OS', 'AM-OS');

            // Atualizar status do agendamento para confirmado
            $stmtUpd = $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = :id");
            $stmtUpd->execute([':id' => $id]);

            // Criar OS
            $stmtOs = $pdo->prepare("
                INSERT INTO ordens_servico (
                    id, numero, agendamento_id, proposta_id, embarcacao_id,
                    cliente_id, vistoriador_id, tipo_vistoria, data_vistoria,
                    hora_vistoria, local, contato_nome, contato_telefone,
                    status, observacoes, criado_por
                ) VALUES (
                    UUID(), :numero, :agendamento_id, :proposta_id, :embarcacao_id,
                    :cliente_id, :vistoriador_id, :tipo_vistoria, :data_vistoria,
                    :hora_vistoria, :local, :contato_nome, :contato_telefone,
                    'pendente', :observacoes, :criado_por
                )
            ");
            $stmtOs->execute([
                ':numero'           => $numero_os,
                ':agendamento_id'   => $id,
                ':proposta_id'      => $ag['proposta_id'],
                ':embarcacao_id'    => $ag['embarcacao_id'],
                ':cliente_id'       => $ag['cliente_id'],
                ':vistoriador_id'   => $ag['vistoriador_id'],
                ':tipo_vistoria'    => $ag['tipo_vistoria'],
                ':data_vistoria'    => $ag['data_vistoria'],
                ':hora_vistoria'    => $ag['hora_vistoria'],
                ':local'            => $ag['local'],
                ':contato_nome'     => $ag['contato_nome'],
                ':contato_telefone' => $ag['contato_telefone'],
                ':observacoes'      => $ag['observacoes'],
                ':criado_por'       => $_SESSION['usuario_id'],
            ]);

            $relatorioCumprimentoId = criarRelatorioCumprimentoAgendamento(
                $pdo,
                $ag,
                (string)($_SESSION['usuario_id'] ?? '')
            );
            $pdo->commit();

            log_atividade('os_gerada', "OS {$numero_os} gerada a partir do agendamento ID: {$id}.");
            if ($relatorioCumprimentoId) {
                log_atividade('relatorio_cumprimento_criado', "Relatorio {$relatorioCumprimentoId} criado para o retorno A/S do agendamento {$id}.");
            }
            // ============================================================
            // DISPARO AUTOMÁTICO DE E-MAIL DE CONFIRMAÇÃO
            // ============================================================
            try {
                $stmtEmail = $pdo->prepare("
                    SELECT c.nome AS cliente_nome, c.email AS cliente_email,
                           e.nome AS embarcacao_nome
                    FROM agendamentos a
                    INNER JOIN clientes c ON c.id = a.cliente_id
                    INNER JOIN embarcacoes e ON e.id = a.embarcacao_id
                    WHERE a.id = :id
                ");
                $stmtEmail->execute([':id' => $id]);
                $dadosEmail = $stmtEmail->fetch(PDO::FETCH_ASSOC);

                if (!empty($dadosEmail['cliente_email'])) {
                    $templatePath = __DIR__ . '/../../templates/email/agendamento.html';
                    if (file_exists($templatePath)) {
                        $htmlBody = file_get_contents($templatePath);

                        $hora = !empty($ag['hora_vistoria']) ? substr($ag['hora_vistoria'], 0, 5) . 'h' : 'A confirmar';
                        $local = !empty($ag['local']) ? $ag['local'] : 'A definir';
                        $contatoNome = !empty($ag['contato_nome']) ? $ag['contato_nome'] : 'Não informado';
                        $contatoTel = !empty($ag['contato_telefone']) ? $ag['contato_telefone'] : '-';
                        $obsHtml = !empty($ag['observacoes'])
                            ? '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; margin: 15px 0;"><strong>Observações:</strong><br>' . h($ag['observacoes']) . '</div>'
                            : '';

                        $replacements = [
                            '{{NOME_CLIENTE}}'     => h($dadosEmail['cliente_nome']),
                            '{{TIPO_VISTORIA}}'    => h($ag['tipo_vistoria']),
                            '{{EMBARCACAO_NOME}}'  => h($dadosEmail['embarcacao_nome']),
                            '{{DATA_VISTORIA}}'    => date('d/m/Y', strtotime($ag['data_vistoria'])),
                            '{{HORA_VISTORIA}}'    => $hora,
                            '{{LOCAL}}'            => $local,
                            '{{OS_NUMERO}}'        => $numero_os,
                            '{{CONTATO_NOME}}'     => $contatoNome,
                            '{{CONTATO_TELEFONE}}' => $contatoTel,
                            '{{OBSERVACOES}}'      => $obsHtml,
                            '{{EMAIL_CONTATO}}'    => EMAIL_CONTATO,
                            '{{TELEFONE_CONTATO}}' => TELEFONE_CONTATO,
                        ];
                        $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

                        require_once __DIR__ . '/../../includes/mailer.php';

                        $resultado = enviarEmail(
                            $dadosEmail['cliente_email'],
                            $dadosEmail['cliente_nome'],
                            'Confirmação de Agendamento - ' . $ag['tipo_vistoria'],
                            $htmlBody
                        );

                        // Registrar no log de e-mails
                        $stmtLog = $pdo->prepare("
                            INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                            VALUES (UUID(), :destinatario, :assunto, 'agendamento', 'agendamentos', :referencia_id, :status, :mensagem_erro, :enviado_por)
                        ");
                        $stmtLog->execute([
                            ':destinatario'  => $dadosEmail['cliente_email'],
                            ':assunto'       => 'Confirmação de Agendamento - ' . $ag['tipo_vistoria'],
                            ':referencia_id' => $id,
                            ':status'        => $resultado['success'] ? 'enviado' : 'erro',
                            ':mensagem_erro' => $resultado['success'] ? null : $resultado['message'],
                            ':enviado_por'   => $_SESSION['usuario_id'],
                        ]);
                    }
                }
            } catch (Exception $emailErr) {
                error_log('Erro ao enviar e-mail de confirmação de agendamento: ' . $emailErr->getMessage());
            }

            setMensagem('success', "Agendamento confirmado! Ordem de Serviço <strong>{$numero_os}</strong> gerada com sucesso.");
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao confirmar agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao gerar Ordem de Serviço.');
            redirecionar(APP_URL . 'agendamentos');
        }
        break;

    // ==================== CANCELAR ====================
    case 'cancelar':
        try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisição inválida.');
            redirecionar(APP_URL . 'agendamentos');
        }

        // VISTORIADOR não pode cancelar agendamentos
        if ($cargo === 'VISTORIADOR') {
            setMensagem('error', 'Acesso negado. Vistoriadores não podem cancelar agendamentos.');
            redirecionar(APP_URL . 'agendamentos');
        }
            $id = $_POST['id'] ?? '';
            $motivoCancelamento = trim(sanitizar($_POST['motivo_cancelamento'] ?? ''));

            if (empty($id)) {
                setMensagem('error', 'ID do agendamento não informado.');
                redirecionar(APP_URL . 'agendamentos');
            }

            // Buscar agendamento
            $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ag = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                setMensagem('error', 'Agendamento não encontrado.');
                redirecionar(APP_URL . 'agendamentos');
            }

            if (!in_array($ag['status'], ['pendente', 'confirmado'])) {
                setMensagem('error', 'Apenas agendamentos pendentes ou confirmados podem ser cancelados.');
                redirecionar(APP_URL . 'agendamentos');
            }
            if (!empty($ag['relatorio_origem_id']) && $cargo !== 'ADMIN') {
                setMensagem('error', 'Somente o administrador pode cancelar um retorno A/S.');
                redirecionar(APP_URL . 'agendamentos');
            }
            if (!empty($ag['relatorio_origem_id']) && $motivoCancelamento === '') {
                setMensagem('error', 'Informe o motivo do cancelamento do retorno A/S.');
                redirecionar(APP_URL . 'agendamentos');
            }

            $pdo->beginTransaction();

            // Cancelar agendamento
            $stmtUpd = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id");
            $stmtUpd->execute([':id' => $id]);

            // Cancelar OS vinculada se existir
            $stmtOs = $pdo->prepare("UPDATE ordens_servico SET status = 'cancelado' WHERE agendamento_id = :agendamento_id AND status != 'cancelado'");
            $stmtOs->execute([':agendamento_id' => $id]);
            if (!empty($ag['relatorio_origem_id'])) {
                $pdo->prepare("UPDATE vistorias v
                    JOIN vistoria_retornos vr ON vr.relatorio_resultado_id=v.id
                    SET v.status='CANCELADA'
                    WHERE vr.agendamento_id=:agendamento AND v.status='PENDENTE'")
                    ->execute([':agendamento' => $id]);
                $pdo->prepare("UPDATE vistoria_retornos
                    SET status='CANCELADO',motivo_cancelamento=:motivo,
                        cancelado_por=:usuario,cancelado_em=NOW()
                    WHERE agendamento_id=:agendamento")
                    ->execute([
                        ':motivo' => $motivoCancelamento,
                        ':usuario' => $_SESSION['usuario_id'],
                        ':agendamento' => $id,
                    ]);
            }

            $pdo->commit();

            log_atividade('agendamento_cancelado', "Agendamento ID: {$id} cancelado.");
            setMensagem('success', 'Agendamento cancelado com sucesso!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao cancelar agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao cancelar agendamento.');
            redirecionar(APP_URL . 'agendamentos');
        }
        break;

    // ==================== ALTERAR STATUS (via POST) ====================
    case 'atualizar_status':
        try {
            $id     = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? '';

            $status_validos = ['pendente', 'confirmado', 'em_andamento', 'concluido', 'cancelado'];
            if (empty($id) || !in_array($status, $status_validos)) {
                setMensagem('error', 'Dados inválidos para alteração de status.');
                redirecionar(APP_URL . 'agendamentos');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE agendamentos SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            // Se estiver marcando como concluído, atualizar a OS para em_andamento
            if ($status === 'concluido') {
                $stmtOs = $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status = 'pendente'");
                $stmtOs->execute([':agendamento_id' => $id]);
            }

            $pdo->commit();

            log_atividade('agendamento_status', "Agendamento ID: {$id} alterado para status {$status}.");
            setMensagem('success', 'Status do agendamento atualizado!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atualizar status.');
            redirecionar(APP_URL . 'agendamentos');
        }
        break;

    default:
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'agendamentos');
        break;
}
