<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: actions.php - Processar POST/GET (insert/update/confirmar/gerar OS/cancelar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

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
        
        // Buscar embarcações da proposta com metadados
        $stmtEmb = $pdo->prepare("
            SELECT e.id, e.nome, e.registro, e.numero_inscricao, e.tipo, e.propulsao
            FROM propostas_embarcacoes pe
            INNER JOIN embarcacoes e ON pe.embarcacao_id = e.id
            WHERE pe.proposta_id = :id
            ORDER BY e.nome ASC
        ");
        $stmtEmb->execute([':id' => $proposta_id]);
        $embarcacoesLista = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        // Estruturar dados detalhados por embarcação
        $embarcacoesDetalhadas = [];
        $totalServicosVistoriaGeral = [];
        $totalServicosAnaliseGeral = [];

        foreach ($embarcacoesLista as $emb) {
            $embId = $emb['id'];

            // Serviços de VISTORIA da embarcação (DISTINCT)
            $stmtSrvEmb = $pdo->prepare("
                SELECT DISTINCT s.nome 
                FROM propostas_servicos ps
                INNER JOIN servicos s ON ps.servico_id = s.id
                WHERE ps.proposta_id = :id
                  AND (ps.embarcacao_id = :emb_id OR ps.embarcacao_id IS NULL)
                  AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
                ORDER BY s.nome ASC
            ");
            $stmtSrvEmb->execute([':id' => $proposta_id, ':emb_id' => $embId]);
            $servicosEmb = array_column($stmtSrvEmb->fetchAll(PDO::FETCH_ASSOC), 'nome');

            // Serviços de ANÁLISE DE PLANOS da embarcação (DISTINCT)
            $stmtAnaEmb = $pdo->prepare("
                SELECT DISTINCT s.nome 
                FROM propostas_servicos ps
                INNER JOIN servicos s ON ps.servico_id = s.id
                WHERE ps.proposta_id = :id
                  AND (ps.embarcacao_id = :emb_id OR ps.embarcacao_id IS NULL)
                  AND COALESCE(s.codigo_operacional,'') IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
                ORDER BY s.nome ASC
            ");
            $stmtAnaEmb->execute([':id' => $proposta_id, ':emb_id' => $embId]);
            $servicosAnaliseEmb = array_column($stmtAnaEmb->fetchAll(PDO::FETCH_ASSOC), 'nome');

            foreach ($servicosEmb as $sNome) {
                if (!in_array($sNome, $totalServicosVistoriaGeral, true)) $totalServicosVistoriaGeral[] = $sNome;
            }
            foreach ($servicosAnaliseEmb as $sNome) {
                if (!in_array($sNome, $totalServicosAnaliseGeral, true)) $totalServicosAnaliseGeral[] = $sNome;
            }

            // Agendamento existente para esta embarcação
            $stmtAgEmb = $pdo->prepare("
                SELECT id, data_vistoria, hora_vistoria, local, vistoriador_id, analista_id, prazo_analise, observacoes, tipo_vistoria, status
                FROM agendamentos
                WHERE proposta_id = :id AND embarcacao_id = :emb_id AND status <> 'cancelado'
                LIMIT 1
            ");
            $stmtAgEmb->execute([':id' => $proposta_id, ':emb_id' => $embId]);
            $agRowEmb = $stmtAgEmb->fetch(PDO::FETCH_ASSOC) ?: [];

            // Análise de planos existente para esta embarcação
            $stmtApEmb = $pdo->prepare("
                SELECT id, numero, analista_id, prazo_agendado_em, status
                FROM analises_planos
                WHERE proposta_id = :id AND embarcacao_id = :emb_id
                ORDER BY criado_em DESC LIMIT 1
            ");
            $stmtApEmb->execute([':id' => $proposta_id, ':emb_id' => $embId]);
            $apRowEmb = $stmtApEmb->fetch(PDO::FETCH_ASSOC) ?: [];

            $analistaEmbId = !empty($agRowEmb['analista_id']) ? $agRowEmb['analista_id'] : ($apRowEmb['analista_id'] ?? null);
            $prazoEmbFormatado = !empty($agRowEmb['prazo_analise'])
                ? date('Y-m-d\TH:i', strtotime($agRowEmb['prazo_analise']))
                : (!empty($apRowEmb['prazo_agendado_em']) ? date('Y-m-d\TH:i', strtotime($apRowEmb['prazo_agendado_em'])) : null);

            $tipoTextoEmb = !empty($servicosEmb) ? implode(', ', $servicosEmb) : (!empty($servicosAnaliseEmb) ? implode(', ', $servicosAnaliseEmb) : 'Vistoria Geral');

            $embarcacoesDetalhadas[] = [
                'id'                => $embId,
                'nome'              => $emb['nome'],
                'registro'          => $emb['registro'] ?: ($emb['numero_inscricao'] ?: ''),
                'tipo'              => $emb['tipo'] ?? '',
                'propulsao'         => $emb['propulsao'] ?? '',
                'tem_vistoria'      => !empty($servicosEmb),
                'tem_analise'       => !empty($servicosAnaliseEmb),
                'servicos_vistoria' => $servicosEmb,
                'servicos_analise'  => $servicosAnaliseEmb,
                'tipo_vistoria'     => $tipoTextoEmb,
                'agendamento_id'    => $agRowEmb['id'] ?? null,
                'data_vistoria'     => $agRowEmb['data_vistoria'] ?? '',
                'hora_vistoria'     => !empty($agRowEmb['hora_vistoria']) ? substr($agRowEmb['hora_vistoria'], 0, 5) : '',
                'local'             => $agRowEmb['local'] ?? '',
                'vistoriador_id'    => $agRowEmb['vistoriador_id'] ?? '',
                'analista_id'       => $analistaEmbId,
                'prazo_analise'     => $prazoEmbFormatado,
                'observacoes'       => $agRowEmb['observacoes'] ?? '',
                'analise_plano_id'  => $apRowEmb['id'] ?? null,
                'analise_plano_num' => $apRowEmb['numero'] ?? null,
            ];
        }

        $temVistoriaGeral = !empty($totalServicosVistoriaGeral);
        $temAnaliseGeral = !empty($totalServicosAnaliseGeral);

        if ($temVistoriaGeral) {
            $tipoVistoriaTextoGeral = implode(', ', $totalServicosVistoriaGeral);
        } elseif ($temAnaliseGeral) {
            $tipoVistoriaTextoGeral = implode(', ', $totalServicosAnaliseGeral);
        } else {
            $tipoVistoriaTextoGeral = 'Vistoria Geral';
        }

        // Buscar dados de análise técnica pré-existente
        $stmtAp = $pdo->prepare("SELECT analista_id, prazo_agendado_em FROM analises_planos WHERE proposta_id = :id ORDER BY criado_em DESC LIMIT 1");
        $stmtAp->execute([':id' => $proposta_id]);
        $apRow = $stmtAp->fetch(PDO::FETCH_ASSOC) ?: [];
        $analistaIdAtual = $apRow['analista_id'] ?? null;
        $prazoAnaliseAtual = !empty($apRow['prazo_agendado_em']) ? date('Y-m-d\TH:i', strtotime($apRow['prazo_agendado_em'])) : null;

        // Verificar se já existe agendamento rascunho/pendente desta proposta para atualizar ao invés de duplicar
        $agendamentoIdExistente = null;
        if (function_exists('proximoAgendamentoPendenteProposta')) {
            $agendamentoIdExistente = proximoAgendamentoPendenteProposta($pdo, $proposta_id);
        } else {
            $stmtAg = $pdo->prepare("SELECT id FROM agendamentos WHERE proposta_id = :id AND status = 'pendente' LIMIT 1");
            $stmtAg->execute([':id' => $proposta_id]);
            $agendamentoIdExistente = $stmtAg->fetchColumn() ?: null;
        }

        echo json_encode([
            'success'           => true,
            'is_multi'          => count($embarcacoesDetalhadas) > 1,
            'agendamento_id'    => $agendamentoIdExistente,
            'cliente_id'        => $proposta['cliente_id'],
            'cliente_nome'      => $proposta['cliente_nome'],
            'contato_nome'      => $proposta['responsavel_fechamento_nome'] ?? null,
            'contato_telefone'  => $proposta['responsavel_fechamento_telefone'] ?? null,
            'embarcacoes'       => $embarcacoesDetalhadas,
            'embarcacao_id'     => !empty($embarcacoesDetalhadas) ? $embarcacoesDetalhadas[0]['id'] : null,
            'tipo_vistoria'     => $tipoVistoriaTextoGeral,
            'tem_vistoria'      => $temVistoriaGeral,
            'tem_analise'       => $temAnaliseGeral,
            'servicos_vistoria' => $totalServicosVistoriaGeral,
            'servicos_analise'  => $totalServicosAnaliseGeral,
            'analista_id'       => $analistaIdAtual,
            'prazo_analise'     => $prazoAnaliseAtual,
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

function validarCamposAgendamento(string $data_vistoria, string $embarcacao_id, string $cliente_id, string $tipo_vistoria, bool $requerDataVistoria = true): array
{
    $errosCampos = [];

    if (empty($cliente_id)) {
        $errosCampos['cliente_id'] = 'Selecione o cliente.';
    }

    if (empty($embarcacao_id)) {
        $errosCampos['embarcacao_id'] = 'Selecione a embarcação.';
    }

    if (empty($tipo_vistoria)) {
        $errosCampos['tipo_vistoria'] = 'Informe o tipo de serviço / vistoria.';
    }

    if ($requerDataVistoria) {
        if (empty($data_vistoria)) {
            $errosCampos['data_vistoria'] = 'Informe a data da vistoria.';
        } elseif ($data_vistoria < date('Y-m-d')) {
            $errosCampos['data_vistoria'] = 'A data da vistoria não pode ser no passado.';
        }
    }

    return $errosCampos;
}

function obterTipoVistoriaDaProposta(PDO $pdo, ?string $proposta_id, ?string $embarcacao_id = null): string
{
    if (empty($proposta_id)) {
        return '';
    }

    $whereEmb = '';
    $params = [':id' => $proposta_id];
    if (!empty($embarcacao_id)) {
        $whereEmb = ' AND (ps.embarcacao_id = :emb_id OR ps.embarcacao_id IS NULL) ';
        $params[':emb_id'] = $embarcacao_id;
    }

    // Primeiro busca se há serviços de vistoria de campo
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.nome
        FROM propostas_servicos ps
        INNER JOIN servicos s ON ps.servico_id = s.id
        WHERE ps.proposta_id = :id
          {$whereEmb}
          AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
        ORDER BY s.nome ASC
    ");
    $stmt->execute($params);
    $servicos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nome');

    if (!empty($servicos)) {
        return implode(', ', $servicos);
    }

    // Se só houver serviços de análise de planos
    $stmtAna = $pdo->prepare("
        SELECT DISTINCT s.nome
        FROM propostas_servicos ps
        INNER JOIN servicos s ON ps.servico_id = s.id
        WHERE ps.proposta_id = :id
          {$whereEmb}
          AND COALESCE(s.codigo_operacional,'') IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
        ORDER BY s.nome ASC
    ");
    $stmtAna->execute($params);
    $servicosAna = array_column($stmtAna->fetchAll(PDO::FETCH_ASSOC), 'nome');

    return !empty($servicosAna) ? implode(', ', $servicosAna) : '';
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


function sincronizarAnalisesPlanosAgendamento(PDO $pdo, ?string $proposta_id, ?string $embarcacao_id, ?string $analista_id, ?string $prazo_analise, ?string $usuario_id): void
{
    if (empty($proposta_id) || empty($analista_id)) {
        return;
    }

    if (!empty($embarcacao_id)) {
            $stmtDemandas = $pdo->prepare("SELECT * FROM analises_planos WHERE proposta_id = :prop AND (embarcacao_id = :emb OR embarcacao_id IS NULL)");
            $stmtDemandas->execute([':prop' => $proposta_id, ':emb' => $embarcacao_id]);
        } else {
            $stmtDemandas = $pdo->prepare("SELECT * FROM analises_planos WHERE proposta_id = :prop");
            $stmtDemandas->execute([':prop' => $proposta_id]);
        }
    $demandas = $stmtDemandas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($demandas as $d) {
        $apId = $d['id'];
        $prazoFormatado = !empty($prazo_analise) ? date('Y-m-d H:i:s', strtotime($prazo_analise)) : null;
        $novoStatus = in_array($d['status'], ['AGUARDANDO_AGENDAMENTO', 'AGENDADA'], true) ? 'AGENDADA' : $d['status'];
        $acaoAgenda = empty($d['prazo_agendado_em']) ? 'AGENDAMENTO' : ($analista_id !== $d['analista_id'] ? 'REATRIBUICAO' : 'REAGENDAMENTO');

        $pdo->prepare("UPDATE analises_planos SET analista_id = :analista, prazo_agendado_em = COALESCE(:prazo, prazo_agendado_em), status = :status WHERE id = :id")
            ->execute([
                ':analista' => $analista_id,
                ':prazo' => $prazoFormatado,
                ':status' => $novoStatus,
                ':id' => $apId
            ]);

        if ($prazoFormatado) {
            $pdo->prepare("INSERT INTO analise_planos_agenda_historico
                (analise_id, analista_anterior_id, analista_novo_id, prazo_anterior_em, prazo_novo_em, motivo, acao, criado_por)
                VALUES (:analise, :ant_id, :novo_id, :prazo_ant, :prazo_novo, 'Definido no agendamento da proposta', :acao, :usuario)")
                ->execute([
                    ':analise' => $apId,
                    ':ant_id' => $d['analista_id'],
                    ':novo_id' => $analista_id,
                    ':prazo_ant' => $d['prazo_agendado_em'],
                    ':prazo_novo' => $prazoFormatado,
                    ':acao' => $acaoAgenda,
                    ':usuario' => $usuario_id ?: $analista_id
                ]);
        }

        if ($analista_id !== $d['analista_id']) {
            if (function_exists('analisePlanosNotificar')) {
                analisePlanosNotificar(
                    $pdo,
                    $analista_id,
                    'ANALISE_AGENDADA',
                    'Nova Análise de Planos atribuída',
                    "Você foi atribuído à análise {$d['numero']}" . ($prazoFormatado ? " com prazo até " . date('d/m/Y H:i', strtotime($prazoFormatado)) : "") . ".",
                    $apId,
                    'analises-planos/form?id=' . urlencode($apId)
                );
            }
        }
    }
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
            $vistoriador_id  = !empty($_POST['vistoriador_id']) ? trim($_POST['vistoriador_id']) : null;
            $analista_id     = !empty($_POST['analista_id']) ? trim($_POST['analista_id']) : null;
            $prazo_analise   = !empty($_POST['prazo_analise']) ? trim($_POST['prazo_analise']) : null;
            $vendedor_id     = $_POST['vendedor_id'] ?? null;
            $relatorio_origem_id = trim((string)($_POST['relatorio_origem_id'] ?? ''));
            $motivo_reatribuicao = trim(sanitizar($_POST['motivo_reatribuicao'] ?? ''));
            $vistoriador_origem_id = null;

            if ($relatorio_origem_id !== '' && !in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {
                throw new RuntimeException('Somente o administrador ou secretaria pode criar retorno de cumprimento A/S.');
            }

            if (!empty($proposta_id) && $relatorio_origem_id === '') {
                $tipoVistoriaProposta = obterTipoVistoriaDaProposta($pdo, $proposta_id, $embarcacao_id);
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

            // Se foi enviado pacote multi-embarcações para a proposta
            $multiJson = $_POST['agendamentos_multi_json'] ?? '';
            $multiEntries = !empty($multiJson) ? json_decode($multiJson, true) : null;
            if (is_array($multiEntries) && count($multiEntries) > 1 && !empty($proposta_id)) {
                $pdo->beginTransaction();
                foreach ($multiEntries as $entry) {
                    $vEmbId = $entry['embarcacao_id'] ?? '';
                    if (empty($vEmbId)) continue;

                    $vAgId = !empty($entry['agendamento_id']) ? trim($entry['agendamento_id']) : null;
                    $vData = !empty($entry['data_vistoria']) ? trim($entry['data_vistoria']) : null;
                    $vHora = !empty($entry['hora_vistoria']) ? substr(trim($entry['hora_vistoria']), 0, 5) : null;
                    $vLocal = sanitizar($entry['local'] ?? '');
                    $vVistoriadorId = !empty($entry['vistoriador_id']) ? trim($entry['vistoriador_id']) : null;
                    $vAnalistaId = !empty($entry['analista_id']) ? trim($entry['analista_id']) : null;
                    $vPrazoAnalise = !empty($entry['prazo_analise']) ? trim($entry['prazo_analise']) : null;
                    $vObs = sanitizar($entry['observacoes'] ?? '');

                    if ($cargo === 'VISTORIADOR') {
                        $vVistoriadorId = $_SESSION['usuario_id'];
                    }

                    $tipoVistoriaEmb = obterTipoVistoriaDaProposta($pdo, $proposta_id, $vEmbId);
                    if (empty($tipoVistoriaEmb)) {
                        $tipoVistoriaEmb = 'Vistoria Geral';
                    }

                    if (empty($vAgId)) {
                        $stmtCheckEmbAg = $pdo->prepare("SELECT id FROM agendamentos WHERE proposta_id = :prop AND embarcacao_id = :emb AND status <> 'cancelado' LIMIT 1");
                        $stmtCheckEmbAg->execute([':prop' => $proposta_id, ':emb' => $vEmbId]);
                        $vAgId = $stmtCheckEmbAg->fetchColumn() ?: null;
                    }

                    $prazoAnaliseSql = !empty($vPrazoAnalise) ? date('Y-m-d H:i:s', strtotime($vPrazoAnalise)) : null;

                    if (!empty($vAgId)) {
                        $stmtUpAg = $pdo->prepare("
                            UPDATE agendamentos SET
                                cliente_id = :cliente_id,
                                armador_id = :armador_id,
                                operador_nome = :operador_nome,
                                vistoriador_id = :vistoriador_id,
                                analista_id = :analista_id,
                                prazo_analise = :prazo_analise,
                                vendedor_id = COALESCE(:vendedor_id, vendedor_id),
                                tipo_vistoria = :tipo_vistoria,
                                data_vistoria = :data_vistoria,
                                hora_vistoria = :hora_vistoria,
                                local = :local,
                                contato_nome = :contato_nome,
                                contato_telefone = :contato_telefone,
                                observacoes = :observacoes
                            WHERE id = :id
                        ");
                        $stmtUpAg->execute([
                            ':cliente_id'       => $cliente_id,
                            ':armador_id'       => $armador_id ?: null,
                            ':operador_nome'    => $operador_nome ?: null,
                            ':vistoriador_id'   => $vVistoriadorId ?: null,
                            ':analista_id'      => $vAnalistaId ?: null,
                            ':prazo_analise'    => $prazoAnaliseSql,
                            ':vendedor_id'      => $vendedor_id ?: null,
                            ':tipo_vistoria'    => $tipoVistoriaEmb,
                            ':data_vistoria'    => $vData ?: null,
                            ':hora_vistoria'    => $vHora ?: null,
                            ':local'            => $vLocal ?: null,
                            ':contato_nome'     => $contato_nome ?: null,
                            ':contato_telefone' => $contato_telefone ?: null,
                            ':observacoes'      => $vObs ?: null,
                            ':id'               => $vAgId,
                        ]);
                    } else {
                        $novoId = gerarUUID();
                        $stmtInAg = $pdo->prepare("
                            INSERT INTO agendamentos (
                                id, proposta_id, embarcacao_id, cliente_id, armador_id, operador_nome,
                                vistoriador_id, analista_id, prazo_analise, vendedor_id,
                                tipo_vistoria, data_vistoria, hora_vistoria, local,
                                contato_nome, contato_telefone, status, observacoes, criado_por
                            ) VALUES (
                                :id, :proposta_id, :embarcacao_id, :cliente_id, :armador_id, :operador_nome,
                                :vistoriador_id, :analista_id, :prazo_analise, :vendedor_id,
                                :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                                :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                            )
                        ");
                        $stmtInAg->execute([
                            ':id'               => $novoId,
                            ':proposta_id'      => $proposta_id,
                            ':embarcacao_id'    => $vEmbId,
                            ':cliente_id'       => $cliente_id,
                            ':armador_id'       => $armador_id ?: null,
                            ':operador_nome'    => $operador_nome ?: null,
                            ':vistoriador_id'   => $vVistoriadorId ?: null,
                            ':analista_id'      => $vAnalistaId ?: null,
                            ':prazo_analise'    => $prazoAnaliseSql,
                            ':vendedor_id'      => $vendedor_id ?: null,
                            ':tipo_vistoria'    => $tipoVistoriaEmb,
                            ':data_vistoria'    => $vData ?: null,
                            ':hora_vistoria'    => $vHora ?: null,
                            ':local'            => $vLocal ?: null,
                            ':contato_nome'     => $contato_nome ?: null,
                            ':contato_telefone' => $contato_telefone ?: null,
                            ':observacoes'      => $vObs ?: null,
                            ':criado_por'       => $_SESSION['usuario_id'],
                        ]);
                    }

                    if (!empty($vAnalistaId)) {
                        sincronizarAnalisesPlanosAgendamento($pdo, $proposta_id, $vEmbId, $vAnalistaId, $vPrazoAnalise, $_SESSION['usuario_id'] ?? null);
                    }
                }
                $pdo->commit();

                log_atividade('agendamento_multiplo', "Agendamentos da proposta {$proposta_id} atualizados para múltiplas embarcações.");
                setMensagem('success', 'Agendamentos das embarcações definidos e salvos com sucesso!');
                redirecionar(APP_URL . 'agendamentos');
            }

            // Identificar escopo da demanda: vistoria de campo, analise de planos, ou ambos
            $temVistoria = true;
            $temAnalise = false;
            if (!empty($proposta_id)) {
                $stmtSrvCheck = $pdo->prepare("SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON ps.servico_id = s.id WHERE ps.proposta_id = :id AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')");
                $stmtSrvCheck->execute([':id' => $proposta_id]);
                $temVistoria = ((int)$stmtSrvCheck->fetchColumn()) > 0;

                $stmtAnaCheck = $pdo->prepare("SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON ps.servico_id = s.id WHERE ps.proposta_id = :id AND COALESCE(s.codigo_operacional,'') IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')");
                $stmtAnaCheck->execute([':id' => $proposta_id]);
                $temAnalise = ((int)$stmtAnaCheck->fetchColumn()) > 0;
            } else {
                $temAnalise = !empty($analista_id) || (stripos($tipo_vistoria, 'Análise de Planos') !== false || stripos($tipo_vistoria, 'Analise de Planos') !== false);
                $temVistoria = !empty($vistoriador_id) || !$temAnalise;
            }

            if (!$temVistoria && $temAnalise) {
                if (empty($data_vistoria)) {
                    $data_vistoria = !empty($prazo_analise) ? date('Y-m-d', strtotime($prazo_analise)) : date('Y-m-d');
                }
                if (empty($hora_vistoria) && !empty($prazo_analise)) {
                    $hora_vistoria = date('H:i', strtotime($prazo_analise));
                }
            }

            if ($temVistoria && !horaVistoriaValida($hora_vistoria)) {
                setMensagem('error', 'Selecione um horário redondo, de meia em meia hora.');
                redirecionar(APP_URL . 'agendamentos/form');
            }
            $hora_vistoria = !empty($hora_vistoria) ? substr($hora_vistoria, 0, 5) : null;

            if ($cargo === 'VISTORIADOR') {
                $vistoriador_id = $_SESSION['usuario_id'];
            }

            $errosCampos = validarCamposAgendamento($data_vistoria, $embarcacao_id, $cliente_id, $tipo_vistoria, $temVistoria);

            if ($temVistoria) {
                if (empty($vistoriador_id)) {
                    $errosCampos['vistoriador_id'] = 'Selecione o vistoriador responsável.';
                } else {
                    $stmtCheckVist = $pdo->prepare("SELECT id, ativo, nome FROM usuarios WHERE id = :id LIMIT 1");
                    $stmtCheckVist->execute([':id' => $vistoriador_id]);
                    $uVist = $stmtCheckVist->fetch(PDO::FETCH_ASSOC);
                    if (!$uVist || (int)$uVist['ativo'] !== 1) {
                        $errosCampos['vistoriador_id'] = 'O vistoriador selecionado não foi encontrado ou está inativo.';
                    }
                }
            } else {
                $vistoriador_id = null;
            }

            if ($temAnalise) {
                if (empty($analista_id)) {
                    $errosCampos['analista_id'] = 'Selecione o analista naval responsável pela análise de planos.';
                } else {
                    $stmtCheckAna = $pdo->prepare("SELECT id, ativo, nome FROM usuarios WHERE id = :id LIMIT 1");
                    $stmtCheckAna->execute([':id' => $analista_id]);
                    $uAna = $stmtCheckAna->fetch(PDO::FETCH_ASSOC);
                    if (!$uAna || (int)$uAna['ativo'] !== 1) {
                        $errosCampos['analista_id'] = 'O analista selecionado não foi encontrado ou está inativo.';
                    }
                }
                if (empty($prazo_analise)) {
                    $errosCampos['prazo_analise'] = 'Informe o prazo limite para a análise técnica.';
                }
            }

            if (!empty($errosCampos)) {
                setMensagem('error', 'Erro no agendamento: ' . implode(' ', $errosCampos), $errosCampos);
                redirecionar(APP_URL . 'agendamentos/form');
            }

            if (empty($embarcacao_id) || empty($cliente_id) || empty($tipo_vistoria)) {
                setMensagem('error', 'Preencha todos os campos obrigatórios (cliente, embarcação e tipo).');
                redirecionar(APP_URL . 'agendamentos/form');
            }

            if ($temVistoria && $data_vistoria < date('Y-m-d')) {
                setMensagem('error', 'A data da vistoria não pode ser no passado.');
                redirecionar(APP_URL . 'agendamentos/form');
            }

            if ($cargo === 'VENDEDOR') {
                $vendedor_id = $_SESSION['usuario_id'];
            }

            if ($relatorio_origem_id !== '') {
                $stmtOrigem = $pdo->prepare("SELECT v.embarcacao_id,COALESCE(a.cliente_id,v.pessoa_id) cliente_id,
                        a.vistoriador_id,vr.tipo retorno_tipo
                    FROM vistorias v
                    JOIN vistoria_retornos vr ON vr.relatorio_origem_id=v.id
                    LEFT JOIN agendamentos a ON a.id=v.agendamento_id
                    WHERE v.id=:id
                      AND ((vr.tipo='AS' AND v.status='RETORNO_AS')
                        OR (vr.tipo='EXIGENCIAS' AND v.status='APROVADA_COM_EXIGENCIAS'))
                      AND NOT EXISTS (
                        SELECT 1 FROM vistorias vf
                        WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA'
                      ) LIMIT 1");
                $stmtOrigem->execute([':id' => $relatorio_origem_id]);
                $origem = $stmtOrigem->fetch(PDO::FETCH_ASSOC);
                if (!$origem || $origem['embarcacao_id'] !== $embarcacao_id || $origem['cliente_id'] !== $cliente_id) {
                    throw new RuntimeException('Os dados do retorno A/S não correspondem ao relatório de origem.');
                }
                $vistoriador_origem_id = trim((string)($origem['vistoriador_id'] ?? ''));
                if ($vistoriador_origem_id === '') {
                    throw new RuntimeException('O relatório de origem não possui vistoriador responsável.');
                }
                if ((string)$vistoriador_id !== $vistoriador_origem_id && $motivo_reatribuicao === '') {
                    throw new RuntimeException('Informe o motivo da troca do vistoriador responsável.');
                }
                $stmtRetorno = $pdo->prepare("SELECT status FROM vistoria_retornos WHERE relatorio_origem_id=:id LIMIT 1");
                $stmtRetorno->execute([':id' => $relatorio_origem_id]);
                if ($stmtRetorno->fetchColumn() !== 'PENDENTE_AGENDAMENTO') {
                    throw new RuntimeException('Este retorno A/S já possui agendamento ou foi concluído.');
                }
                $tipo_vistoria = $origem['retorno_tipo'] === 'AS'
                    ? 'Cumprimento de A/S'
                    : 'Retorno de exigencias';
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

            // Se foi informada proposta e não é retorno A/S, verificar se já existe rascunho de agendamento criado para ela
            $agendamentoAlvoId = null;
            if (!empty($proposta_id) && empty($relatorio_origem_id)) {
                $stmtCheckAg = $pdo->prepare("SELECT id FROM agendamentos WHERE proposta_id = :prop AND embarcacao_id = :emb AND status = 'pendente' LIMIT 1");
                $stmtCheckAg->execute([':prop' => $proposta_id, ':emb' => $embarcacao_id]);
                $agendamentoAlvoId = $stmtCheckAg->fetchColumn() ?: null;
            }

            $prazoAnaliseSql = !empty($prazo_analise) ? date('Y-m-d H:i:s', strtotime($prazo_analise)) : null;

            if ($agendamentoAlvoId) {
                $novoAgendamentoId = $agendamentoAlvoId;
                $stmt = $pdo->prepare("
                    UPDATE agendamentos SET
                        armador_id = :armador_id,
                        operador_nome = :operador_nome,
                        vistoriador_id = :vistoriador_id,
                        analista_id = :analista_id,
                        prazo_analise = :prazo_analise,
                        vendedor_id = COALESCE(:vendedor_id, vendedor_id),
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
                    ':armador_id'       => $armador_id ?: null,
                    ':operador_nome'    => $operador_nome ?: null,
                    ':vistoriador_id'   => $vistoriador_id ?: null,
                    ':analista_id'      => $analista_id ?: null,
                    ':prazo_analise'    => $prazoAnaliseSql,
                    ':vendedor_id'      => $vendedor_id ?: null,
                    ':tipo_vistoria'    => $tipo_vistoria,
                    ':data_vistoria'    => $data_vistoria ?: null,
                    ':hora_vistoria'    => $hora_vistoria ?: null,
                    ':local'            => $local ?: null,
                    ':contato_nome'     => $contato_nome ?: null,
                    ':contato_telefone' => $contato_telefone ?: null,
                    ':observacoes'      => $observacoes ?: null,
                    ':id'               => $novoAgendamentoId,
                ]);
            } else {
                $novoAgendamentoId = gerarUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO agendamentos (
                        id, proposta_id, relatorio_origem_id, embarcacao_id, cliente_id, armador_id, operador_nome,
                        vistoriador_id, analista_id, prazo_analise, vendedor_id,
                        tipo_vistoria, data_vistoria, hora_vistoria, local,
                        contato_nome, contato_telefone, status, observacoes, criado_por
                    ) VALUES (
                        :id, :proposta_id, :relatorio_origem_id, :embarcacao_id, :cliente_id, :armador_id, :operador_nome,
                        :vistoriador_id, :analista_id, :prazo_analise, :vendedor_id,
                        :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                        :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                    )
                ");
                $stmt->execute([
                    ':id'               => $novoAgendamentoId,
                    ':proposta_id'      => $proposta_id,
                    ':relatorio_origem_id' => $relatorio_origem_id ?: null,
                    ':embarcacao_id'    => $embarcacao_id,
                    ':cliente_id'       => $cliente_id,
                    ':armador_id'       => $armador_id ?: null,
                    ':operador_nome'    => $operador_nome ?: null,
                    ':vistoriador_id'   => $vistoriador_id ?: null,
                    ':analista_id'      => $analista_id ?: null,
                    ':prazo_analise'    => $prazoAnaliseSql,
                    ':vendedor_id'      => $vendedor_id ?: null,
                    ':tipo_vistoria'    => $tipo_vistoria,
                    ':data_vistoria'    => $data_vistoria ?: null,
                    ':hora_vistoria'    => $hora_vistoria ?: null,
                    ':local'            => $local ?: null,
                    ':contato_nome'     => $contato_nome ?: null,
                    ':contato_telefone' => $contato_telefone ?: null,
                    ':observacoes'      => $observacoes ?: null,
                    ':criado_por'       => $_SESSION['usuario_id'],
                ]);
            }

            if ($relatorio_origem_id !== '') {
                $stmtVincularRetorno = $pdo->prepare("UPDATE vistoria_retornos
                    SET status='AGENDADO',agendamento_id=:agendamento,
                        vistoriador_origem_id=COALESCE(vistoriador_origem_id,:vistoriador_origem),
                        vistoriador_retorno_id=:vistoriador_retorno,
                        motivo_reatribuicao=:motivo,
                        reatribuido_por=:reatribuido_por,
                        reatribuido_em=:reatribuido_em
                    WHERE relatorio_origem_id=:origem
                      AND status='PENDENTE_AGENDAMENTO'
                      AND agendamento_id IS NULL");
                $stmtVincularRetorno->execute([
                    ':agendamento' => $novoAgendamentoId,
                    ':origem' => $relatorio_origem_id,
                    ':vistoriador_origem' => $vistoriador_origem_id,
                    ':vistoriador_retorno' => $vistoriador_id,
                    ':motivo' => (string)$vistoriador_id !== $vistoriador_origem_id ? $motivo_reatribuicao : null,
                    ':reatribuido_por' => (string)$vistoriador_id !== $vistoriador_origem_id ? $_SESSION['usuario_id'] : null,
                    ':reatribuido_em' => (string)$vistoriador_id !== $vistoriador_origem_id ? date('Y-m-d H:i:s') : null,
                ]);
                if ($stmtVincularRetorno->rowCount() !== 1) {
                    throw new RuntimeException('O retorno A/S foi agendado por outra operacao.');
                }
            }

            // Sincronizar processos de análise de planos vinculados à proposta
            sincronizarAnalisesPlanosAgendamento($pdo, $proposta_id, $embarcacao_id, $analista_id, $prazo_analise, $_SESSION['usuario_id'] ?? null);

            $pdo->commit();

            log_atividade('agendamento_criado', "Agendamento de {$tipo_vistoria} criado.");
            setMensagem('success', 'Agendamento criado com sucesso!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao inserir agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao criar agendamento: ' . $e->getMessage());
            redirecionar(APP_URL . 'agendamentos/form');
        }
        break;

    // ==================== EDITAR ====================
    case 'editar':
        $id = $_POST['id'] ?? '';
        $fluxoProposta = (string)($_POST['fluxo_proposta'] ?? '') === '1';
        $destinoFormulario = APP_URL . 'agendamentos/form?id=' . urlencode($id)
            . ($fluxoProposta ? '&fluxo_proposta=1' : '');
        try {
            if ($cargo === 'VISTORIADOR') {
                setMensagem('error', 'Acesso negado. Vistoriadores não podem editar agendamentos.');
                redirecionar(APP_URL . 'agendamentos');
            }
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
            $vistoriador_id  = !empty($_POST['vistoriador_id']) ? trim($_POST['vistoriador_id']) : null;
            $analista_id     = !empty($_POST['analista_id']) ? trim($_POST['analista_id']) : null;
            $prazo_analise   = !empty($_POST['prazo_analise']) ? trim($_POST['prazo_analise']) : null;
            $vendedor_id     = $_POST['vendedor_id'] ?? null;
            $motivo_reatribuicao = trim(sanitizar($_POST['motivo_reatribuicao'] ?? ''));

            $agendamentoAtual = !empty($id) ? obterAgendamento($pdo, $id) : null;
            if ($agendamentoAtual && !empty($agendamentoAtual['relatorio_origem_id']) && !in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {
                throw new RuntimeException('Somente o administrador ou secretaria pode alterar um retorno A/S.');
            }
            if ($agendamentoAtual && !empty($agendamentoAtual['proposta_id'])) {
                $proposta_id = $agendamentoAtual['proposta_id'];
                $embarcacao_id = $agendamentoAtual['embarcacao_id'];
                $cliente_id = $agendamentoAtual['cliente_id'];
                $vendedor_id = $agendamentoAtual['vendedor_id'] ?? $vendedor_id;
            }

            if (!empty($proposta_id)) {
                $tipoVistoriaProposta = obterTipoVistoriaDaProposta($pdo, $proposta_id, $embarcacao_id);
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

            // Se foi enviado pacote multi-embarcações para a proposta
            $multiJson = $_POST['agendamentos_multi_json'] ?? '';
            $multiEntries = !empty($multiJson) ? json_decode($multiJson, true) : null;
            if (is_array($multiEntries) && count($multiEntries) > 1 && !empty($proposta_id)) {
                $pdo->beginTransaction();
                foreach ($multiEntries as $entry) {
                    $vEmbId = $entry['embarcacao_id'] ?? '';
                    if (empty($vEmbId)) continue;

                    $vAgId = !empty($entry['agendamento_id']) ? trim($entry['agendamento_id']) : null;
                    $vData = !empty($entry['data_vistoria']) ? trim($entry['data_vistoria']) : null;
                    $vHora = !empty($entry['hora_vistoria']) ? substr(trim($entry['hora_vistoria']), 0, 5) : null;
                    $vLocal = sanitizar($entry['local'] ?? '');
                    $vVistoriadorId = !empty($entry['vistoriador_id']) ? trim($entry['vistoriador_id']) : null;
                    $vAnalistaId = !empty($entry['analista_id']) ? trim($entry['analista_id']) : null;
                    $vPrazoAnalise = !empty($entry['prazo_analise']) ? trim($entry['prazo_analise']) : null;
                    $vObs = sanitizar($entry['observacoes'] ?? '');

                    if ($cargo === 'VISTORIADOR') {
                        $vVistoriadorId = $_SESSION['usuario_id'];
                    }

                    $tipoVistoriaEmb = obterTipoVistoriaDaProposta($pdo, $proposta_id, $vEmbId);
                    if (empty($tipoVistoriaEmb)) {
                        $tipoVistoriaEmb = 'Vistoria Geral';
                    }

                    if (empty($vAgId)) {
                        $stmtCheckEmbAg = $pdo->prepare("SELECT id FROM agendamentos WHERE proposta_id = :prop AND embarcacao_id = :emb AND status <> 'cancelado' LIMIT 1");
                        $stmtCheckEmbAg->execute([':prop' => $proposta_id, ':emb' => $vEmbId]);
                        $vAgId = $stmtCheckEmbAg->fetchColumn() ?: null;
                    }

                    $prazoAnaliseSql = !empty($vPrazoAnalise) ? date('Y-m-d H:i:s', strtotime($vPrazoAnalise)) : null;

                    if (!empty($vAgId)) {
                        $stmtUpAg = $pdo->prepare("
                            UPDATE agendamentos SET
                                cliente_id = :cliente_id,
                                armador_id = :armador_id,
                                operador_nome = :operador_nome,
                                vistoriador_id = :vistoriador_id,
                                analista_id = :analista_id,
                                prazo_analise = :prazo_analise,
                                vendedor_id = COALESCE(:vendedor_id, vendedor_id),
                                tipo_vistoria = :tipo_vistoria,
                                data_vistoria = :data_vistoria,
                                hora_vistoria = :hora_vistoria,
                                local = :local,
                                contato_nome = :contato_nome,
                                contato_telefone = :contato_telefone,
                                observacoes = :observacoes
                            WHERE id = :id
                        ");
                        $stmtUpAg->execute([
                            ':cliente_id'       => $cliente_id,
                            ':armador_id'       => $armador_id ?: null,
                            ':operador_nome'    => $operador_nome ?: null,
                            ':vistoriador_id'   => $vVistoriadorId ?: null,
                            ':analista_id'      => $vAnalistaId ?: null,
                            ':prazo_analise'    => $prazoAnaliseSql,
                            ':vendedor_id'      => $vendedor_id ?: null,
                            ':tipo_vistoria'    => $tipoVistoriaEmb,
                            ':data_vistoria'    => $vData ?: null,
                            ':hora_vistoria'    => $vHora ?: null,
                            ':local'            => $vLocal ?: null,
                            ':contato_nome'     => $contato_nome ?: null,
                            ':contato_telefone' => $contato_telefone ?: null,
                            ':observacoes'      => $vObs ?: null,
                            ':id'               => $vAgId,
                        ]);
                    } else {
                        $novoId = gerarUUID();
                        $stmtInAg = $pdo->prepare("
                            INSERT INTO agendamentos (
                                id, proposta_id, embarcacao_id, cliente_id, armador_id, operador_nome,
                                vistoriador_id, analista_id, prazo_analise, vendedor_id,
                                tipo_vistoria, data_vistoria, hora_vistoria, local,
                                contato_nome, contato_telefone, status, observacoes, criado_por
                            ) VALUES (
                                :id, :proposta_id, :embarcacao_id, :cliente_id, :armador_id, :operador_nome,
                                :vistoriador_id, :analista_id, :prazo_analise, :vendedor_id,
                                :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                                :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                            )
                        ");
                        $stmtInAg->execute([
                            ':id'               => $novoId,
                            ':proposta_id'      => $proposta_id,
                            ':embarcacao_id'    => $vEmbId,
                            ':cliente_id'       => $cliente_id,
                            ':armador_id'       => $armador_id ?: null,
                            ':operador_nome'    => $operador_nome ?: null,
                            ':vistoriador_id'   => $vVistoriadorId ?: null,
                            ':analista_id'      => $vAnalistaId ?: null,
                            ':prazo_analise'    => $prazoAnaliseSql,
                            ':vendedor_id'      => $vendedor_id ?: null,
                            ':tipo_vistoria'    => $tipoVistoriaEmb,
                            ':data_vistoria'    => $vData ?: null,
                            ':hora_vistoria'    => $vHora ?: null,
                            ':local'            => $vLocal ?: null,
                            ':contato_nome'     => $contato_nome ?: null,
                            ':contato_telefone' => $contato_telefone ?: null,
                            ':observacoes'      => $vObs ?: null,
                            ':criado_por'       => $_SESSION['usuario_id'],
                        ]);
                    }

                    if (!empty($vAnalistaId)) {
                        sincronizarAnalisesPlanosAgendamento($pdo, $proposta_id, $vEmbId, $vAnalistaId, $vPrazoAnalise, $_SESSION['usuario_id'] ?? null);
                    }
                }
                $pdo->commit();

                log_atividade('agendamento_multiplo', "Agendamentos da proposta {$proposta_id} atualizados para múltiplas embarcações.");
                setMensagem('success', 'Agendamentos das embarcações definidos e salvos com sucesso!');
                redirecionar(APP_URL . 'agendamentos');
            }

            $temVistoria = true;
            $temAnalise = false;
            if (!empty($proposta_id)) {
                $stmtSrvCheck = $pdo->prepare("SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON ps.servico_id = s.id WHERE ps.proposta_id = :id AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')");
                $stmtSrvCheck->execute([':id' => $proposta_id]);
                $temVistoria = ((int)$stmtSrvCheck->fetchColumn()) > 0;

                $stmtAnaCheck = $pdo->prepare("SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON ps.servico_id = s.id WHERE ps.proposta_id = :id AND COALESCE(s.codigo_operacional,'') IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')");
                $stmtAnaCheck->execute([':id' => $proposta_id]);
                $temAnalise = ((int)$stmtAnaCheck->fetchColumn()) > 0;
            } else {
                $temAnalise = !empty($analista_id) || (stripos($tipo_vistoria, 'Análise de Planos') !== false || stripos($tipo_vistoria, 'Analise de Planos') !== false);
                $temVistoria = !empty($vistoriador_id) || !$temAnalise;
            }

            if (!$temVistoria && $temAnalise) {
                if (empty($data_vistoria)) {
                    $data_vistoria = !empty($prazo_analise) ? date('Y-m-d', strtotime($prazo_analise)) : date('Y-m-d');
                }
                if (empty($hora_vistoria) && !empty($prazo_analise)) {
                    $hora_vistoria = date('H:i', strtotime($prazo_analise));
                }
            }

            if ($temVistoria && !horaVistoriaValida($hora_vistoria)) {
                setMensagem('error', 'Selecione um horário redondo, de meia em meia hora.');
                redirecionar($destinoFormulario);
            }
            $hora_vistoria = !empty($hora_vistoria) ? substr($hora_vistoria, 0, 5) : null;

            $errosCampos = validarCamposAgendamento($data_vistoria, $embarcacao_id, $cliente_id, $tipo_vistoria, $temVistoria);
            if (empty($id)) {
                $errosCampos['id'] = 'Agendamento não informado.';
            }

            if ($temVistoria) {
                if (empty($vistoriador_id)) {
                    $errosCampos['vistoriador_id'] = 'Selecione o vistoriador responsável.';
                } else {
                    $stmtCheckVist = $pdo->prepare("SELECT id, ativo, nome FROM usuarios WHERE id = :id LIMIT 1");
                    $stmtCheckVist->execute([':id' => $vistoriador_id]);
                    $uVist = $stmtCheckVist->fetch(PDO::FETCH_ASSOC);
                    if (!$uVist || (int)$uVist['ativo'] !== 1) {
                        $errosCampos['vistoriador_id'] = 'O vistoriador selecionado não foi encontrado ou está inativo.';
                    }
                }
            } else {
                $vistoriador_id = null;
            }

            if ($temAnalise) {
                if (empty($analista_id)) {
                    $errosCampos['analista_id'] = 'Selecione o analista naval responsável pela análise de planos.';
                } else {
                    $stmtCheckAna = $pdo->prepare("SELECT id, ativo, nome FROM usuarios WHERE id = :id LIMIT 1");
                    $stmtCheckAna->execute([':id' => $analista_id]);
                    $uAna = $stmtCheckAna->fetch(PDO::FETCH_ASSOC);
                    if (!$uAna || (int)$uAna['ativo'] !== 1) {
                        $errosCampos['analista_id'] = 'O analista selecionado não foi encontrado ou está inativo.';
                    }
                }
                if (empty($prazo_analise)) {
                    $errosCampos['prazo_analise'] = 'Informe o prazo limite para a análise técnica.';
                }
            }

            if (!empty($errosCampos)) {
                setMensagem('error', 'Erro no agendamento: ' . implode(' ', $errosCampos), $errosCampos);
                $destino = !empty($id) ? $destinoFormulario : APP_URL . 'agendamentos';
                redirecionar($destino);
            }

            if (empty($id) || empty($embarcacao_id) || empty($cliente_id) || empty($tipo_vistoria)) {
                setMensagem('error', 'Dados incompletos para atualização.');
                redirecionar(APP_URL . 'agendamentos');
            }

            $pdo->beginTransaction();
            $vistoriadorOrigemRetorno = null;
            if ($agendamentoAtual && !empty($agendamentoAtual['relatorio_origem_id'])) {
                $stmtRetornoAudit = $pdo->prepare("SELECT vr.vistoriador_origem_id,a0.vistoriador_id vistoriador_original
                    FROM vistoria_retornos vr
                    JOIN vistorias v0 ON v0.id=vr.relatorio_origem_id
                    LEFT JOIN agendamentos a0 ON a0.id=v0.agendamento_id
                    WHERE vr.agendamento_id=:agendamento FOR UPDATE");
                $stmtRetornoAudit->execute([':agendamento' => $id]);
                $retornoAudit = $stmtRetornoAudit->fetch(PDO::FETCH_ASSOC);
                if (!$retornoAudit) throw new RuntimeException('O vínculo auditável do retorno A/S não foi encontrado.');
                $vistoriadorOrigemRetorno = trim((string)($retornoAudit['vistoriador_origem_id'] ?: $retornoAudit['vistoriador_original']));
                if ((string)$vistoriador_id !== $vistoriadorOrigemRetorno && $motivo_reatribuicao === '') {
                    throw new RuntimeException('Informe o motivo da troca do vistoriador responsável.');
                }
            }

            $prazoAnaliseSql = !empty($prazo_analise) ? date('Y-m-d H:i:s', strtotime($prazo_analise)) : null;

            $stmt = $pdo->prepare("
                UPDATE agendamentos 
                SET proposta_id = :proposta_id,
                    embarcacao_id = :embarcacao_id,
                    cliente_id = :cliente_id,
                    armador_id = :armador_id,
                    operador_nome = :operador_nome,
                    vistoriador_id = :vistoriador_id,
                    analista_id = :analista_id,
                    prazo_analise = :prazo_analise,
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
                ':analista_id'     => $analista_id ?: null,
                ':prazo_analise'   => $prazoAnaliseSql,
                ':vendedor_id'     => $vendedor_id ?: null,
                ':tipo_vistoria'   => $tipo_vistoria,
                ':data_vistoria'   => $data_vistoria ?: null,
                ':hora_vistoria'   => $hora_vistoria ?: null,
                ':local'           => $local ?: null,
                ':contato_nome'    => $contato_nome ?: null,
                ':contato_telefone' => $contato_telefone ?: null,
                ':observacoes'     => $observacoes ?: null,
                ':id'              => $id,
            ]);

            if ($agendamentoAtual && !empty($agendamentoAtual['relatorio_origem_id'])) {
                $stmtAudit = $pdo->prepare("UPDATE vistoria_retornos
                    SET vistoriador_origem_id=COALESCE(vistoriador_origem_id,:origem),
                        vistoriador_retorno_id=:retorno,
                        motivo_reatribuicao=:motivo,
                        reatribuido_por=:usuario,
                        reatribuido_em=:data
                    WHERE agendamento_id=:agendamento");
                $trocou = (string)$vistoriador_id !== (string)$vistoriadorOrigemRetorno;
                $stmtAudit->execute([
                    ':origem' => $vistoriadorOrigemRetorno,
                    ':retorno' => $vistoriador_id,
                    ':motivo' => $trocou ? $motivo_reatribuicao : null,
                    ':usuario' => $trocou ? $_SESSION['usuario_id'] : null,
                    ':data' => $trocou ? date('Y-m-d H:i:s') : null,
                    ':agendamento' => $id,
                ]);
            }

            // Sincronizar analises de planos
            sincronizarAnalisesPlanosAgendamento($pdo, $proposta_id, $embarcacao_id, $analista_id, $prazo_analise, $_SESSION['usuario_id'] ?? null);

            $proximoAgendamentoId = null;
            if ($fluxoProposta && !empty($proposta_id)) {
                $proximoAgendamentoId = proximoAgendamentoPendenteProposta(
                    $pdo,
                    (string)$proposta_id,
                    (string)$id
                );
            }
            $pdo->commit();

            log_atividade('agendamento_editado', "Agendamento ID: {$id} atualizado.");
            if ($fluxoProposta && !empty($proposta_id)) {
                if ($proximoAgendamentoId !== null) {
                    setMensagem('success', 'Agendamento atualizado. Continue com o próximo agendamento desta proposta.');
                    redirecionar(
                        APP_URL . 'agendamentos/form?id=' . urlencode($proximoAgendamentoId) . '&fluxo_proposta=1'
                    );
                }

                setMensagem('success', 'Todos os agendamentos desta proposta foram preenchidos.');
                redirecionar(APP_URL . 'agendamentos');
            }

            setMensagem('success', 'Agendamento atualizado com sucesso!');
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao editar agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atualizar agendamento: ' . $e->getMessage());
            redirecionar($destinoFormulario);
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

            $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ag = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                setMensagem('error', 'Agendamento não encontrado.');
                redirecionar(APP_URL . 'agendamentos');
            }
            if (!empty($ag['relatorio_origem_id']) && !in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {
                setMensagem('error', 'Somente o administrador ou secretaria pode confirmar e gerar a OS de um retorno A/S.');
                redirecionar(APP_URL . 'agendamentos');
            }

            if ($ag['status'] !== 'pendente') {
                setMensagem('error', 'Apenas agendamentos pendentes podem ser confirmados. Status atual: ' . $ag['status']);
                redirecionar(APP_URL . 'agendamentos');
            }

            // Identificar se tem vistoria ou apenas analise de planos
            $temVistoria = true;
            if (!empty($ag['proposta_id'])) {
                $stmtCheckVist = $pdo->prepare("SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON ps.servico_id = s.id WHERE ps.proposta_id = :id AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')");
                $stmtCheckVist->execute([':id' => $ag['proposta_id']]);
                $temVistoria = ((int)$stmtCheckVist->fetchColumn()) > 0;
            } else {
                $temVistoria = stripos((string)$ag['tipo_vistoria'], 'Análise de Planos') === false && stripos((string)$ag['tipo_vistoria'], 'Analise de Planos') === false;
            }

            if ($temVistoria && empty($ag['vistoriador_id'])) {
                setMensagem('error', 'Antes de confirmar, atribua um vistoriador responsável ao agendamento.');
                redirecionar(APP_URL . 'agendamentos/form?id=' . urlencode($id));
            }

            if (!$temVistoria && empty($ag['analista_id'])) {
                setMensagem('error', 'Antes de confirmar, atribua o analista responsável pela análise técnica.');
                redirecionar(APP_URL . 'agendamentos/form?id=' . urlencode($id));
            }

            $pdo->beginTransaction();

            $stmtAgLock = $pdo->prepare('SELECT * FROM agendamentos WHERE id=:id FOR UPDATE');
            $stmtAgLock->execute([':id' => $id]);
            $ag = $stmtAgLock->fetch(PDO::FETCH_ASSOC);
            if (!$ag || (string)$ag['status'] !== 'pendente') {
                throw new RuntimeException('O agendamento foi confirmado ou alterado por outra operação.');
            }

            // Atualizar status do agendamento para confirmado
            $stmtUpd = $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = :id AND status='pendente'");
            $stmtUpd->execute([':id' => $id]);
            if ($stmtUpd->rowCount() !== 1) {
                throw new RuntimeException('O agendamento foi confirmado por outra operação.');
            }

            $numero_os = null;
            if ($temVistoria && !empty($ag['vistoriador_id'])) {
                $stmtOsLock = $pdo->prepare('SELECT id FROM ordens_servico WHERE agendamento_id=:id LIMIT 1 FOR UPDATE');
                $stmtOsLock->execute([':id' => $id]);
                if ($stmtOsLock->fetchColumn()) {
                    throw new RuntimeException('Já existe uma Ordem de Serviço para este agendamento.');
                }

                // Gerar número da OS
                $numero_os = gerarNumeroDocumento('OS', 'AM-OS');

                // Criar OS de vistoria em campo
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
                    ':data_vistoria'    => $ag['data_vistoria'] ?: date('Y-m-d'),
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
                log_atividade('os_gerada', "OS {$numero_os} gerada a partir do agendamento ID: {$id}.");
                if ($relatorioCumprimentoId) {
                    log_atividade('relatorio_cumprimento_criado', "Relatorio {$relatorioCumprimentoId} criado para o retorno A/S do agendamento {$id}.");
                }
            }

            if (!empty($ag['analista_id'])) {
                sincronizarAnalisesPlanosAgendamento(
                    $pdo,
                    $ag['proposta_id'] ?? null,
                    $ag['embarcacao_id'] ?? null,
                    $ag['analista_id'],
                    $ag['prazo_analise'] ?? null,
                    $_SESSION['usuario_id'] ?? null
                );
            }

            $pdo->commit();

            if ($numero_os) {
                setMensagem('success', "Agendamento confirmado com sucesso! Ordem de Serviço {$numero_os} gerada para o vistoriador.");
            } else {
                setMensagem('success', "Agendamento técnico de Análise de Planos confirmado com sucesso!");
            }
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao confirmar agendamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao confirmar agendamento: ' . $e->getMessage());
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
            if (!empty($ag['relatorio_origem_id']) && !in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {
                setMensagem('error', 'Somente o administrador ou secretaria pode cancelar um retorno A/S.');
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
