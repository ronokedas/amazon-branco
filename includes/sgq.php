<?php
/**
 * SISTEMA DE GESTÃO DA QUALIDADE (SGQ) - ISO 9001:2015 & NORMAM
 * Arquivo: includes/sgq.php
 * 
 * Regras de governança da qualidade, auditoria cadastral, competência técnica,
 * não conformidades (RNC / 5W2H), pesquisa de satisfação e indicadores.
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Registra auditoria cadastral de alteração crítica (ISO 7.5 e 8.2)
 */
function sgqRegistrarAuditoriaCadastral(
    PDO $pdo,
    string $entidadeTipo,
    string $entidadeId,
    string $acao,
    ?array $dadosAnteriores = null,
    ?array $dadosPosteriores = null,
    ?string $motivo = null
): void {
    try {
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $usuarioNome = $_SESSION['usuario_nome'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        // Identificar delta de campos alterados
        $camposAlterados = [];
        if ($acao === 'ALTERACAO' && is_array($dadosAnteriores) && is_array($dadosPosteriores)) {
            foreach ($dadosPosteriores as $campo => $novoValor) {
                $valorAntigo = $dadosAnteriores[$campo] ?? null;
                if ((string)$valorAntigo !== (string)$novoValor) {
                    $camposAlterados[] = $campo;
                }
            }
        } elseif ($acao === 'CRIACAO') {
            $camposAlterados = is_array($dadosPosteriores) ? array_keys($dadosPosteriores) : [];
        }

        $id = gerarUUID();
        $stmt = $pdo->prepare("
            INSERT INTO sgq_auditoria_cadastral (
                id, entidade_tipo, entidade_id, acao,
                dados_anteriores, dados_posteriores, campos_alterados,
                motivo_justificativa, usuario_id, usuario_nome, ip_origem, user_agent, criado_em
            ) VALUES (
                :id, :tipo, :entidade_id, :acao,
                :dados_ant, :dados_post, :campos_alt,
                :motivo, :usuario_id, :usuario_nome, :ip, :ua, :criado_em
            )
        ");

        $stmt->execute([
            ':id' => $id,
            ':tipo' => $entidadeTipo,
            ':entidade_id' => $entidadeId,
            ':acao' => $acao,
            ':dados_ant' => $dadosAnteriores !== null ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null,
            ':dados_post' => $dadosPosteriores !== null ? json_encode($dadosPosteriores, JSON_UNESCAPED_UNICODE) : null,
            ':campos_alt' => json_encode($camposAlterados, JSON_UNESCAPED_UNICODE),
            ':motivo' => $motivo ? trim($motivo) : null,
            ':usuario_id' => $usuarioId,
            ':usuario_nome' => $usuarioNome,
            ':ip' => $ip,
            ':ua' => $userAgent,
            ':criado_em' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('[SGQ AUDITORIA ERRO] ' . $e->getMessage());
    }
}

/**
 * Validação de Prontidão Técnica da Embarcação para Proposta Comercial (ISO 8.2 & NORMAM)
 */
function embarcacaoValidarProntidaoComercial(PDO $pdo, string $embarcacaoId): array
{
    $stmt = $pdo->prepare("
        SELECT id, nome, tipo, tipo_embarcacao_id, comprimento_total, comprimento_casco,
               boca_moldada, boca_maxima, pontal_moldado, arqueacao_bruta,
               possui_propulsao, fabricante_motor, modelo_motor, numero_motor, potencia_kw
        FROM embarcacoes
        WHERE id = :id AND ativo = 1
        LIMIT 1
    ");
    $stmt->execute([':id' => $embarcacaoId]);
    $emb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emb) {
        return [
            'valido' => false,
            'embarcacao_nome' => 'Desconhecida',
            'pendencias' => ['Embarcação não encontrada ou inativa.'],
            'mensagem' => 'A embarcação informada não foi encontrada ou está inativa.',
        ];
    }

    $pendencias = [];
    $nome = $emb['nome'] ?: 'Embarcação #' . substr($embarcacaoId, 0, 8);

    // 1. Comprimento (Total ou Casco)
    $compTotal = (float)($emb['comprimento_total'] ?? 0);
    $compCasco = (float)($emb['comprimento_casco'] ?? 0);
    if ($compTotal <= 0 && $compCasco <= 0) {
        $pendencias[] = 'Comprimento Total ou do Casco não informado';
    }

    // 2. Boca (Moldada ou Máxima)
    $bocaMoldada = (float)($emb['boca_moldada'] ?? 0);
    $bocaMaxima = (float)($emb['boca_maxima'] ?? 0);
    if ($bocaMoldada <= 0 && $bocaMaxima <= 0) {
        $pendencias[] = 'Boca (Moldada ou Máxima) não informada';
    }

    // 3. Arqueação Bruta
    $ab = trim((string)($emb['arqueacao_bruta'] ?? ''));
    if ($ab === '' || $ab === '0') {
        $pendencias[] = 'Arqueação Bruta (AB) obrigatória pela NORMAM não informada';
    }

    // 4. Propulsão
    if (!isset($emb['possui_propulsao']) || $emb['possui_propulsao'] === null) {
        $pendencias[] = 'Declaração de propulsão (Possui Propulsão? Sim/Não) pendente';
    } elseif ((int)$emb['possui_propulsao'] === 1) {
        if (empty($emb['fabricante_motor']) && empty($emb['modelo_motor']) && empty($emb['potencia_kw'])) {
            $pendencias[] = 'Dados do motor (fabricante, modelo ou potência) obrigatórios para embarcação propulsada';
        }
    }

    $valido = empty($pendencias);
    $mensagem = $valido
        ? ''
        : "A embarcação \"{$nome}\" não atende aos requisitos técnicos mínimos (ISO 8.2 / NORMAM): " . implode(', ', $pendencias) . ". Atualize o cadastro da embarcação antes de avançar.";

    return [
        'valido' => $valido,
        'embarcacao_id' => $embarcacaoId,
        'embarcacao_nome' => $nome,
        'pendencias' => $pendencias,
        'mensagem' => $mensagem,
    ];
}

/**
 * Validação de Competência do Vistoriador no Agendamento Operacional (ISO 7.2)
 */
function vistoriadorElegivelParaAgendamento(
    PDO $pdo,
    string $vistoriadorId,
    string $dataVistoria,
    ?string $tipoVistoria = null
): array {
    $stmt = $pdo->prepare("
        SELECT id, nome, cargo, ativo, status_sgq,
               credencial_marinha_numero, credencial_marinha_validade,
               registro_conselho_tipo, registro_conselho_numero, registro_conselho_validade,
               escopo_habilitacao
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $vistoriadorId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        return [
            'elegivel' => false,
            'codigo' => 'VISTORIADOR_NAO_ENCONTRADO',
            'motivo' => 'Vistoriador não encontrado no sistema.',
        ];
    }

    if ((int)$u['ativo'] !== 1) {
        return [
            'elegivel' => false,
            'codigo' => 'VISTORIADOR_INATIVO',
            'motivo' => "O profissional {$u['nome']} encontra-se inativo no sistema.",
        ];
    }

    $statusSgq = (string)($u['status_sgq'] ?? 'QUALIFICADO');
    if ($statusSgq !== 'QUALIFICADO') {
        return [
            'elegivel' => false,
            'codigo' => 'SGQ_STATUS_NAO_QUALIFICADO',
            'motivo' => "O profissional {$u['nome']} está com status \"{$statusSgq}\" no Sistema de Gestão da Qualidade (ISO 7.2).",
        ];
    }

    // Checagem de validade da credencial da Marinha
    if (!empty($u['credencial_marinha_validade'])) {
        if ($u['credencial_marinha_validade'] < $dataVistoria) {
            $dataValidadeFormatada = date('d/m/Y', strtotime($u['credencial_marinha_validade']));
            return [
                'elegivel' => false,
                'codigo' => 'CREDENCIAL_MARINHA_VENCIDA',
                'motivo' => "A credencial da Autoridade Marítima do vistoriador {$u['nome']} venceu em {$dataValidadeFormatada}, sendo anterior à data da vistoria planejada ({$dataVistoria}). Requisito ISO 7.2 e NORMAM violado.",
            ];
        }
    }

    // Checagem de regularidade do conselho (CREA/CFT)
    if (!empty($u['registro_conselho_validade'])) {
        if ($u['registro_conselho_validade'] < $dataVistoria) {
            $dataValidadeFormatada = date('d/m/Y', strtotime($u['registro_conselho_validade']));
            return [
                'elegivel' => false,
                'codigo' => 'CONSELHO_CLASSE_VENCIDO',
                'motivo' => "O registro no conselho ({$u['registro_conselho_tipo']}) do vistoriador {$u['nome']} venceu em {$dataValidadeFormatada}. Necessário comprovação de regularidade para escalação.",
            ];
        }
    }

    return [
        'elegivel' => true,
        'codigo' => 'QUALIFICADO',
        'motivo' => "Profissional {$u['nome']} qualificado e apto perante os requisitos da qualidade.",
        'usuario' => $u,
    ];
}

/**
 * Gera sequencial de RNC oficial (ex: RNC-2026-0001)
 */
function sgqGerarNumeroRNC(PDO $pdo): string
{
    $anoAtual = (int)date('Y');
    $stmt = $pdo->prepare("
        INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero)
        VALUES ('RNC', :ano, 1)
        ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1
    ");
    $stmt->execute([':ano' => $anoAtual]);

    $stmtNum = $pdo->prepare("SELECT ultimo_numero FROM sequenciais_documentos WHERE tipo_documento = 'RNC' AND ano = :ano");
    $stmtNum->execute([':ano' => $anoAtual]);
    $num = (int)$stmtNum->fetchColumn();

    return sprintf('RNC-%d-%04d', $anoAtual, $num);
}

/**
 * Disparo automático de RNC ao aplicar a trava A/S na Auditoria Técnica (ISO 8.7 & 10.2)
 */
function sgqGerarRncAutomaticaPorRetornoAS(
    PDO $pdo,
    string $vistoriaId,
    string $usuarioId,
    ?string $observacoes = null
): ?string {
    try {
        // Obter dados da vistoria e vínculos
        $stmt = $pdo->prepare("
            SELECT v.id, v.numero, v.agendamento_id, v.embarcacao_id, v.pessoa_id, v.armador_id,
                   v.observacoes_tecnicas, v.texto_observacoes_geradas,
                   e.nome AS embarcacao_nome,
                   os.id AS ordem_servico_id,
                   u.nome AS usuario_nome
            FROM vistorias v
            LEFT JOIN embarcacoes e ON e.id = v.embarcacao_id
            LEFT JOIN ordens_servico os ON os.agendamento_id = v.agendamento_id
            LEFT JOIN usuarios u ON u.id = :usuario_id
            WHERE v.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $vistoriaId, ':usuario_id' => $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) return null;

        // Verificar se já não foi criada RNC para esta vistoria
        $stmtCheck = $pdo->prepare("SELECT id FROM sgq_nao_conformidades WHERE vistoria_id = :v_id LIMIT 1");
        $stmtCheck->execute([':v_id' => $vistoriaId]);
        $rncExistente = $stmtCheck->fetchColumn();
        if ($rncExistente) return (string)$rncExistente;

        $rncId = gerarUUID();
        $numeroRnc = sgqGerarNumeroRNC($pdo);
        $embarcacaoNome = $dados['embarcacao_nome'] ?? 'Embarcação';
        $numeroRelatorio = $dados['numero'] ?: $vistoriaId;

        $motivoDesc = trim((string)$observacoes);
        if ($motivoDesc === '') {
            $motivoDesc = trim((string)($dados['texto_observacoes_geradas'] ?? $dados['observacoes_tecnicas'] ?? 'Exigências do tipo A/S registradas em laudo de vistoria.'));
        }

        // Criar Registro de Não Conformidade (RNC)
        $stmtRnc = $pdo->prepare("
            INSERT INTO sgq_nao_conformidades (
                id, numero_rnc, origem, ordem_servico_id, vistoria_id,
                embarcacao_id, cliente_id, classificacao_falha, severidade,
                titulo, descricao_detalhada, analise_causa_raiz,
                status_ciclo_vida, responsavel_abertura_id, responsavel_abertura_nome,
                data_identificacao, data_conclusao_prevista
            ) VALUES (
                :id, :num, 'AUDITORIA_INTERNA_RT', :os_id, :v_id,
                :emb_id, :cli_id, 'EXIGENCIA_AS_IMPEDITIVA', 'CRITICA_IMPEDITIVA',
                :titulo, :descricao, 'Não conformidade impeditiva de segurança identificada em vistoria de campo conforme NORMAM.',
                'ABERTA', :resp_id, :resp_nome,
                NOW(), DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
            )
        ");

        $stmtRnc->execute([
            ':id' => $rncId,
            ':num' => $numeroRnc,
            ':os_id' => $dados['ordem_servico_id'] ?: null,
            ':v_id' => $vistoriaId,
            ':emb_id' => $dados['embarcacao_id'] ?: null,
            ':cli_id' => $dados['pessoa_id'] ?: null,
            ':titulo' => "Bloqueio A/S: Laudo {$numeroRelatorio} - {$embarcacaoNome}",
            ':descricao' => "O Responsável Técnico aplicou a trava impeditiva de Retorno A/S. Motivo: {$motivoDesc}",
            ':resp_id' => $usuarioId,
            ':resp_nome' => $dados['usuario_nome'] ?? 'Auditoria Técnica',
        ]);

        // Criar Plano de Ação 5W2H Automático (ISO 10.2)
        $planoId = gerarUUID();
        $stmtPlano = $pdo->prepare("
            INSERT INTO sgq_planos_acao (
                id, nao_conformidade_id, o_que_fazer_what, por_que_fazer_why,
                onde_fazer_where, quem_fara_who, quando_fara_when, como_fazer_how,
                status_acao
            ) VALUES (
                :id, :rnc_id, :what, :why, :where, :who, DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), :how, 'PENDENTE'
            )
        ");

        $stmtPlano->execute([
            ':id' => $planoId,
            ':rnc_id' => $rncId,
            ':what' => "Realizar agendamento e vistoria de retorno para verificação in loco do cumprimento das exigências A/S do laudo {$numeroRelatorio}.",
            ':why' => 'Eliminar o risco crítico de navegabilidade e atender integralmente aos requisitos da NORMAM antes da emissão do certificado.',
            ':where' => "Bordo da embarcação {$embarcacaoNome}",
            ':who' => 'Setor de Operações / Vistoriador Designado',
            ':how' => 'Reinspeção formal dos pontos apontados com registro fotográfico e emissão de laudo de cumprimento de exigências.',
        ]);

        return $rncId;
    } catch (Throwable $e) {
        error_log('[SGQ AUTO RNC ERRO] ' . $e->getMessage());
        return null;
    }
}

/**
 * Motor de Cálculo dos Indicadores do SGQ (ISO 9.1)
 */
function sgqObterIndicadores(PDO $pdo, ?string $dataInicio = null, ?string $dataFim = null): array
{
    if (empty($dataInicio)) {
        $dataInicio = date('Y-01-01'); // Início do ano corrente
    }
    if (empty($dataFim)) {
        $dataFim = date('Y-m-d');
    }

    // 1. Taxa de Retrabalho Técnico (Relação de Bloqueios A/S sobre Total de Vistorias)
    $stmtVistorias = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_vistorias,
            SUM(CASE WHEN status = 'RETORNO_AS' THEN 1 ELSE 0 END) AS total_retornos_as,
            SUM(CASE WHEN status = 'APROVADA_COM_EXIGENCIAS' THEN 1 ELSE 0 END) AS total_aprovadas_exigencias,
            SUM(CASE WHEN status = 'APROVADA' THEN 1 ELSE 0 END) AS total_aprovadas_diretas
        FROM vistorias
        WHERE data_vistoria BETWEEN :ini AND :fim
    ");
    $stmtVistorias->execute([':ini' => $dataInicio, ':fim' => $dataFim]);
    $dadosVist = $stmtVistorias->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalVistorias = (int)($dadosVist['total_vistorias'] ?? 0);
    $totalAs = (int)($dadosVist['total_retornos_as'] ?? 0);
    $taxaRetrabalhoPercent = $totalVistorias > 0
        ? round(($totalAs / $totalVistorias) * 100, 2)
        : 0.0;

    // 2. Lead Time Médio Operacional (Agendamento confirmado até emissão do certificado)
    // Busca os certificados emitidos e cruza com a data de confirmação do agendamento da OS
    $stmtLeadTime = $pdo->prepare("
        SELECT 
            c.id, c.data_emissao AS data_certificado,
            COALESCE(a.data_vistoria, v.data_vistoria, c.data_emissao) AS data_agendamento
        FROM certificados_csn c
        LEFT JOIN vistorias v ON v.id = c.vistoria_id
        LEFT JOIN agendamentos a ON a.id = v.agendamento_id
        WHERE c.data_emissao BETWEEN :ini AND :fim
    ");
    $stmtLeadTime->execute([':ini' => $dataInicio, ':fim' => $dataFim]);
    $certificadosLead = $stmtLeadTime->fetchAll(PDO::FETCH_ASSOC);

    $somaDias = 0;
    $totalCertificadosApurados = count($certificadosLead);

    foreach ($certificadosLead as $cl) {
        $dtCert = new DateTime($cl['data_certificado']);
        $dtAgend = new DateTime($cl['data_agendamento']);
        $diff = $dtAgend->diff($dtCert);
        $dias = max(1, (int)$diff->format('%a'));
        $somaDias += $dias;
    }

    $leadTimeMedioDias = $totalCertificadosApurados > 0
        ? round($somaDias / $totalCertificadosApurados, 1)
        : 0.0;

    // 3. Índice de Satisfação Geral Ponderado (ISO 9.1.2)
    // Pesos: Técnica (40%), Prazo (35%), Atendimento (25%)
    $stmtSatisfacao = $pdo->prepare("
        SELECT 
            nota_atendimento_comercial,
            nota_qualidade_tecnica,
            nota_cumprimento_prazo,
            nota_nps_geral
        FROM sgq_satisfacao_clientes
        WHERE data_avaliacao BETWEEN :ini AND :fim_dt
    ");
    $stmtSatisfacao->execute([':ini' => $dataInicio . ' 00:00:00', ':fim_dt' => $dataFim . ' 23:59:59']);
    $avaliacoes = $stmtSatisfacao->fetchAll(PDO::FETCH_ASSOC);

    $totalAvaliacoes = count($avaliacoes);
    $somaNotasPonderadas = 0.0;
    $promotores = 0;
    $detratores = 0;
    $neutros = 0;

    foreach ($avaliacoes as $av) {
        $tec = (float)$av['nota_qualidade_tecnica'];
        $prazo = (float)$av['nota_cumprimento_prazo'];
        $atend = (float)$av['nota_atendimento_comercial'];
        $nps = (int)$av['nota_nps_geral'];

        // Fórmula Ponderada: (Téc*0.40) + (Prazo*0.35) + (Atend*0.25)
        $notaPond = ($tec * 0.40) + ($prazo * 0.35) + ($atend * 0.25);
        $somaNotasPonderadas += $notaPond;

        // Classificação NPS
        if ($nps >= 9) $promotores++;
        elseif ($nps >= 7) $neutros++;
        else $detratores++;
    }

    // Normalizado em percentual (0 a 100%) baseado no máximo possível (5.0)
    $indiceGeralSatisfacaoPercent = $totalAvaliacoes > 0
        ? round(($somaNotasPonderadas / ($totalAvaliacoes * 5.0)) * 100, 1)
        : 0.0;

    $npsScore = $totalAvaliacoes > 0
        ? round((($promotores - $detratores) / $totalAvaliacoes) * 100)
        : 0;

    // 4. Panorama Geral de Não Conformidades (RNC)
    $stmtRnc = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_rncs,
            SUM(CASE WHEN status_ciclo_vida = 'ABERTA' THEN 1 ELSE 0 END) AS rncs_abertas,
            SUM(CASE WHEN status_ciclo_vida = 'EM_EXECUCAO' THEN 1 ELSE 0 END) AS rncs_em_execucao,
            SUM(CASE WHEN status_ciclo_vida = 'ENCERRADA_EFICAZ' THEN 1 ELSE 0 END) AS rncs_encerradas
        FROM sgq_nao_conformidades
        WHERE data_identificacao BETWEEN :ini AND :fim_dt
    ");
    $stmtRnc->execute([':ini' => $dataInicio . ' 00:00:00', ':fim_dt' => $dataFim . ' 23:59:59']);
    $dadosRnc = $stmtRnc->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
        'indicador_retrabalho' => [
            'taxa_percentual' => $taxaRetrabalhoPercent,
            'total_vistorias' => $totalVistorias,
            'total_retornos_as' => $totalAs,
            'total_aprovadas_diretas' => (int)($dadosVist['total_aprovadas_diretas'] ?? 0),
            'total_aprovadas_exigencias' => (int)($dadosVist['total_aprovadas_exigencias'] ?? 0),
        ],
        'indicador_lead_time' => [
            'dias_medio' => $leadTimeMedioDias,
            'certificados_concluidos' => $totalCertificadosApurados,
        ],
        'indicador_satisfacao' => [
            'indice_ponderado_percent' => $indiceGeralSatisfacaoPercent,
            'total_avaliacoes' => $totalAvaliacoes,
            'nps_score' => $npsScore,
            'promotores' => $promotores,
            'neutros' => $neutros,
            'detratores' => $detratores,
        ],
        'rncs' => [
            'total' => (int)($dadosRnc['total_rncs'] ?? 0),
            'abertas' => (int)($dadosRnc['rncs_abertas'] ?? 0),
            'em_execucao' => (int)($dadosRnc['rncs_em_execucao'] ?? 0),
            'encerradas' => (int)($dadosRnc['rncs_encerradas'] ?? 0),
        ],
    ];
}
