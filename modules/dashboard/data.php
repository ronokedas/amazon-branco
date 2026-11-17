<?php

require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

function dashScalar(PDO $pdo, string $sql, array $params = [], float $fallback = 0): float
{
    try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return (float)$stmt->fetchColumn(); }
    catch (Throwable $e) { error_log('Dashboard scalar: ' . $e->getMessage()); return $fallback; }
}

function dashRows(PDO $pdo, string $sql, array $params = []): array
{
    try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; }
    catch (Throwable $e) { error_log('Dashboard rows: ' . $e->getMessage()); return []; }
}

function dashMetaDoMes(PDO $pdo, string $cargo, string $usuarioId): array
{
    $competencia = date('Y-m-01');
    $inicio = $competencia;
    $fim = date('Y-m-t');
    $escritorioId = null;

    if ($cargo !== 'ADMIN' && $cargo !== 'SECRETARIA') {
        $escritorios = financeiroEscritoriosUsuario($pdo, $usuarioId);
        $escritorioId = $escritorios[0]['id'] ?? null;
    }

    if ($cargo === 'ADMIN' || $cargo === 'SECRETARIA') {
        $meta = dashScalar($pdo, "SELECT COALESCE(SUM(fm.valor),0) FROM financeiro_metas_mensais fm JOIN escritorios e ON e.id=fm.escritorio_id AND e.ativo=1 WHERE fm.usuario_id IS NULL AND fm.competencia=:competencia", [':competencia'=>$competencia]);
        $realizado = dashScalar($pdo, "SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status='PAGO' AND data BETWEEN :inicio AND :fim", [':inicio'=>$inicio, ':fim'=>$fim]);
        $mensagens = dashRows($pdo, "SELECT e.nome,fm.mensagem FROM financeiro_metas_mensais fm JOIN escritorios e ON e.id=fm.escritorio_id AND e.ativo=1 WHERE fm.usuario_id IS NULL AND fm.competencia=:competencia AND TRIM(COALESCE(fm.mensagem,''))<>'' ORDER BY e.nome", [':competencia'=>$competencia]);
        $mensagem = implode(' • ', array_map(static fn(array $item): string => $item['nome'] . ': ' . $item['mensagem'], $mensagens));
    } elseif ($escritorioId) {
        $metaConfigurada = dashRows($pdo, 'SELECT valor,mensagem FROM financeiro_metas_mensais WHERE usuario_id IS NULL AND escritorio_id=:escritorio AND competencia=:competencia LIMIT 1', [':escritorio'=>$escritorioId, ':competencia'=>$competencia]);
        $meta = (float)($metaConfigurada[0]['valor'] ?? 0);
        $mensagem = trim((string)($metaConfigurada[0]['mensagem'] ?? ''));
        $realizado = dashScalar($pdo, "SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status='PAGO' AND escritorio_id=:escritorio AND data BETWEEN :inicio AND :fim", [':escritorio'=>$escritorioId, ':inicio'=>$inicio, ':fim'=>$fim]);
    } else {
        $meta = 0.0;
        $realizado = 0.0;
        $mensagem = '';
    }

    $escopo = ($cargo === 'ADMIN' || $cargo === 'SECRETARIA') ? 'todos os escritórios' : (string)($escritorios[0]['nome'] ?? 'seu escritório');
    return ['valor'=>$meta, 'realizado'=>$realizado, 'mensagem'=>$mensagem, 'escopo'=>$escopo];
}

function dashActivityUrl(array $log, ?PDO $pdo = null): ?string
{
    $acao = strtolower((string)($log['acao'] ?? ''));
    $descricao = (string)($log['descricao'] ?? '');

    // 1. Propostas
    if (preg_match('/Proposta\s+([A-Z0-9-]+\/\d+)/i', $descricao, $m)) {
        return APP_URL . 'comercial?busca=' . urlencode($m[1]);
    }
    if (str_contains($acao, 'proposta')) {
        return APP_URL . 'comercial';
    }

    // 2. Relatórios e Vistorias
    if (preg_match('/agendamento\s+ID:\s*([0-9a-f-]{36})/i', $descricao, $m)) {
        return APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($m[1]);
    }
    if (preg_match('/Relatorio\s+ID\s+([0-9a-f-]{36})/i', $descricao, $m)) {
        $vid = $m[1];
        if ($pdo) {
            try {
                $st = $pdo->prepare("SELECT agendamento_id FROM vistorias WHERE id = :id LIMIT 1");
                $st->execute([':id' => $vid]);
                $agId = $st->fetchColumn();
                if ($agId) {
                    return APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agId) . '&vistoria_id=' . urlencode($vid);
                }
            } catch (Throwable $e) {}
        }
        return APP_URL . 'vistorias/relatorio?vistoria_id=' . urlencode($vid);
    }
    if (preg_match('/(AM-REL-V-[0-9]+\/[0-9]+)/i', $descricao, $m)) {
        $num = $m[1];
        if ($pdo) {
            try {
                $st = $pdo->prepare("SELECT id, agendamento_id FROM vistorias WHERE numero = :num LIMIT 1");
                $st->execute([':num' => $num]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode((string)$row['agendamento_id']) . '&vistoria_id=' . urlencode((string)$row['id']);
                }
            } catch (Throwable $e) {}
        }
        return APP_URL . 'vistorias';
    }

    // 3. Agendamentos
    if (str_contains($acao, 'agendamento')) {
        if (preg_match('/[0-9a-f]{8}-[0-9a-f-]{27}/i', $descricao, $m)) {
            return APP_URL . 'agendamentos/form?id=' . urlencode($m[0]);
        }
        return APP_URL . 'agendamentos';
    }

    // 4. Certificados
    if (str_contains($acao, 'certificado') || str_contains($acao, 'documento_aprovado')) {
        return APP_URL . 'certificados';
    }

    // 5. Clientes / Proprietários / Armadores
    if (str_contains($acao, 'cliente') || str_contains($acao, 'proprietario') || str_contains($acao, 'armador') || str_contains($acao, 'despachante')) {
        return APP_URL . 'clientes';
    }

    // 6. Embarcações
    if (str_contains($acao, 'embarcacao')) {
        return APP_URL . 'embarcacoes';
    }

    if (str_contains($acao, 'vistoria')) return APP_URL . 'vistorias';
    if (str_contains($acao, 'usuario')) return APP_URL . 'usuarios';

    return null;
}

function formatarLogAtividadeDidatico(array $log, ?PDO $pdo = null): array
{
    $acao = (string)($log['acao'] ?? '');
    $descricao = (string)($log['descricao'] ?? '');
    $usuario = (string)($log['usuario'] ?? 'Sistema');
    $criadoEm = (string)($log['criado_em'] ?? '');

    $url = dashActivityUrl($log, $pdo);
    $icone = 'fa-solid fa-circle-info';
    $corIcone = '#64748b';
    $titulo = $descricao;
    $detalhe = '';

    // Cache simples de relatório para enriquecer UUIDs crus
    static $cacheRelatorios = [];
    $obterDadosRelatorio = function(string $uuid) use ($pdo, &$cacheRelatorios): ?array {
        if (isset($cacheRelatorios[$uuid])) return $cacheRelatorios[$uuid];
        if (!$pdo) return null;
        try {
            $st = $pdo->prepare("SELECT v.numero, e.nome as embarcacao FROM vistorias v JOIN embarcacoes e ON e.id = v.embarcacao_id WHERE v.id = :id LIMIT 1");
            $st->execute([':id' => $uuid]);
            $res = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            $cacheRelatorios[$uuid] = $res;
            return $res;
        } catch (Throwable $e) {
            return null;
        }
    };

    // Assinaturas de relatórios técnicos
    if (str_contains($acao, 'assinatura') || str_contains($descricao, 'responsavel tecnico')) {
        $icone = 'fa-solid fa-file-signature';
        $corIcone = '#10b981'; // esmeralda
        if (preg_match('/Relatorio ID\s+([0-9a-f-]{36})/i', $descricao, $m)) {
            $info = $obterDadosRelatorio($m[1]);
            if ($info && !empty($info['numero'])) {
                $titulo = "Assinatura do Relatório Técnico {$info['numero']}";
                $detalhe = "Embarcação: {$info['embarcacao']} · Assinado com fé pública pelo vistoriador.";
            } else {
                $titulo = "Assinatura do Relatório Técnico concluída";
                $detalhe = "Termo de responsabilidade técnica assinado digitalmente.";
            }
        } else {
            $titulo = "Assinatura de documento registrada";
            $detalhe = $descricao;
        }
    }
    // Aprovação ou decisão administrativa
    elseif (str_contains($acao, 'decisao_admin') || str_contains($descricao, 'definido como')) {
        $icone = 'fa-solid fa-stamp';
        $corIcone = '#0284c7'; // azul celeste
        $statusMap = [
            'APROVADA' => 'Aprovado sem exigências',
            'APROVADA_COM_EXIGENCIAS' => 'Aprovado com Exigências',
            'RETORNO_AS' => 'Retorno A/S solicitado',
            'REPROVADA' => 'Reprovado',
        ];
        $statusTxt = 'Decisão da Diretoria registrada';
        foreach ($statusMap as $k => $v) {
            if (str_contains($descricao, $k)) {
                $statusTxt = $v;
                break;
            }
        }
        if (preg_match('/Relatorio ID\s+([0-9a-f-]{36})/i', $descricao, $m)) {
            $info = $obterDadosRelatorio($m[1]);
            if ($info && !empty($info['numero'])) {
                $titulo = "Relatório {$info['numero']} ({$info['embarcacao']})";
                $detalhe = "Decisão executiva: {$statusTxt}.";
            } else {
                $titulo = "Relatório Técnico analisado pela Diretoria";
                $detalhe = "Resultado: {$statusTxt}.";
            }
        } else {
            $titulo = "Decisão administrativa registrada";
            $detalhe = $statusTxt;
        }
    }
    // Relatório técnico salvo / enviado
    elseif (str_contains($acao, 'relatorio') || str_contains(strtolower($descricao), 'relatorio')) {
        $icone = 'fa-solid fa-clipboard-check';
        $corIcone = '#10b981';
        if (preg_match('/(AM-REL-V-[0-9]+\/[0-9]+)/i', $descricao, $m)) {
            $titulo = "Relatório Técnico {$m[1]} registrado";
            if (str_contains($descricao, 'AGUARDANDO_APROVACAO')) {
                $detalhe = "Enviado pelo vistoriador e aguardando aprovação da diretoria.";
            } else {
                $detalhe = "Dados técnicos e checklist atualizados.";
            }
        } else {
            $titulo = "Relatório Técnico de Vistoria movimentado";
            $detalhe = "Atualização de checklist ou dados de inspeção.";
        }
    }
    // Propostas comerciais
    elseif (str_contains($acao, 'proposta') || str_contains($descricao, 'Proposta AM-ORC')) {
        $icone = 'fa-solid fa-file-invoice-dollar';
        $corIcone = '#8b5cf6'; // roxo
        if (preg_match('/Proposta\s+([A-Z0-9-]+\/\d+)/i', $descricao, $m)) {
            $numProp = $m[1];
            if (str_contains($descricao, 'criada')) {
                $titulo = "Proposta {$numProp} criada";
            } elseif (str_contains($descricao, 'editada') || str_contains($descricao, 'atualizado')) {
                $titulo = "Proposta {$numProp} atualizada";
            } elseif (str_contains($descricao, 'aprovada') || str_contains($descricao, 'autorizada')) {
                $titulo = "Proposta {$numProp} autorizada";
            } else {
                $titulo = "Proposta Comercial {$numProp}";
            }
            // Extrair cliente se houver
            if (preg_match("/cliente\s*'([^']+)'/i", $descricao, $cm)) {
                $detalhe = "Cliente: {$cm[1]}";
            } elseif (preg_match('/Total:\s*(R\$\s*[\d\.,]+)/i', $descricao, $vm)) {
                $detalhe = "Valor final: {$vm[1]}";
            } else {
                $detalhe = "Movimentação na proposta comercial.";
            }
        } else {
            $titulo = "Proposta comercial movimentada";
            $detalhe = $descricao;
        }
    }
    // Certificados estatutários
    elseif (str_contains($acao, 'certificado') || str_contains($descricao, 'Certificado') || str_contains($descricao, 'Licença')) {
        $icone = 'fa-solid fa-award';
        $corIcone = '#f59e0b'; // âmbar / dourado
        $titulo = "Certificado Estatutário Emitido";
        if (preg_match('/(AM-[A-Z]+-[0-9]+\/[0-9]+)/i', $descricao, $m)) {
            $titulo = "Certificado {$m[1]}";
        }
        $detalhe = preg_replace('/[0-9a-f]{8}-[0-9a-f-]{27}/i', '', $descricao);
        $detalhe = trim(preg_replace('/\s+/', ' ', $detalhe));
    }
    // Clientes / Armadores
    elseif (str_contains($acao, 'cliente') || str_contains($acao, 'proprietario') || str_contains($descricao, 'Cliente')) {
        $icone = 'fa-solid fa-user-check';
        $corIcone = '#06b6d4'; // ciano
        $titulo = "Cadastro de Cliente / Armador";
        if (preg_match("/'([^']+)'/i", $descricao, $m)) {
            $detalhe = "Entidade: {$m[1]}";
        } else {
            $detalhe = $descricao;
        }
    }
    // Agendamentos
    elseif (str_contains($acao, 'agendamento')) {
        $icone = 'fa-solid fa-calendar-check';
        $corIcone = '#0d9488'; // teal
        $titulo = "Agendamento de Vistoria";
        $detalhe = "Inspeção operacional agendada ou atualizada na escala.";
    }

    return [
        'titulo' => $titulo,
        'detalhe' => $detalhe,
        'url' => $url,
        'icone' => $icone,
        'cor_icone' => $corIcone,
        'usuario' => $usuario,
        'criado_em' => $criadoEm,
        'acao_original' => $acao,
        'descricao_original' => $descricao,
    ];
}

function dashboardLoadData(PDO $pdo, string $cargo, string $usuarioId): array
{
    $metaMes = dashMetaDoMes($pdo, $cargo, $usuarioId);
    $meta = (float)$metaMes['valor'];
    $recebido = (float)$metaMes['realizado'];
    $base = ['meta'=>['valor'=>$meta,'realizado'=>$recebido,'percentual'=>$meta>0?round(($recebido/$meta)*100,1):0.0,'mensagem'=>$metaMes['mensagem'],'escopo'=>$metaMes['escopo']]];
    $params = [':uid'=>$usuarioId];

    if ($cargo === 'VISTORIADOR') {
        $uEmail = trim((string)($_SESSION['usuario_email'] ?? ''));
        $vistoriadorIds = array_values(array_filter([$usuarioId]));
        if ($uEmail !== '') {
            try {
                $stmtIds = $pdo->prepare("SELECT id FROM usuarios WHERE email = :mail");
                $stmtIds->execute([':mail' => $uEmail]);
                $idsEncontrados = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($idsEncontrados)) {
                    $vistoriadorIds = array_values(array_unique(array_merge($vistoriadorIds, $idsEncontrados)));
                }
            } catch (Throwable $e) {}
        }
        $escapedIds = "'" . implode("','", array_map('addslashes', $vistoriadorIds)) . "'";
        $wVist = " a.vistoriador_id IN ({$escapedIds}) ";
        $wHist = " (a.vistoriador_id IN ({$escapedIds}) OR v.criado_por IN ({$escapedIds})) ";
        $params = [];

        $base['kpis'] = [
            'atrasadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria<CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'hoje'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria=CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'proximas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria>CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'aguardando'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado') AND a.data_vistoria>=CURDATE() AND v.id IS NULL",$params),
            'rascunhos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE {$wVist} AND v.status='PENDENTE'",$params),
            'enviados'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE {$wVist} AND v.status='AGUARDANDO_APROVACAO'",$params),
            'aguardando_assinatura'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id LEFT JOIN agendamentos a ON a.id=v.agendamento_id WHERE {$wHist} AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') AND COALESCE(v.assinatura_status,'PENDENTE')<>'ASSINADO'",$params),
            'concluidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE {$wVist} AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA') AND v.data_aprovacao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params),
        ];
        $base['aguardando_assinatura_lista'] = dashRows($pdo, "SELECT 
                v.id, v.numero, v.agendamento_id, v.data_vistoria, v.status, v.assinatura_status, v.finalidade,
                v.data_aprovacao, v.aprovado_por,
                e.nome AS embarcacao, COALESCE(NULLIF(e.registro,''), e.numero_inscricao, 'N/D') AS registro,
                c.nome AS cliente, COALESCE(NULLIF(a.tipo_vistoria,''), 'Vistoria Técnica') AS tipo_vistoria,
                COALESCE(u_aprovador.nome, 'Diretoria Técnica') AS aprovador_nome
            FROM vistorias v
            JOIN embarcacoes e ON e.id = v.embarcacao_id
            LEFT JOIN agendamentos a ON a.id = v.agendamento_id
            LEFT JOIN clientes c ON c.id = a.cliente_id
            LEFT JOIN usuarios u_aprovador ON u_aprovador.id = v.aprovado_por
            WHERE {$wHist}
              AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')
              AND COALESCE(v.assinatura_status,'PENDENTE')<>'ASSINADO'
            ORDER BY COALESCE(v.data_aprovacao, v.atualizado_em, v.criado_em) DESC", $params);
        $base['minha_assinatura_ativa'] = (bool)dashScalar($pdo, "SELECT COUNT(*) FROM responsaveis_assinatura WHERE usuario_id IN ({$escapedIds}) AND ativo = 1 AND assinatura_arquivo IS NOT NULL AND assinatura_arquivo <> '' AND assinatura_hash IS NOT NULL AND assinatura_hash <> ''", $params);
        $base['agenda_prioritaria'] = dashRows($pdo,"SELECT
                a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.status,
                e.nome embarcacao,COALESCE(NULLIF(e.registro,''),e.numero_inscricao) registro,
                c.nome cliente,v.id vistoria_id,v.status vistoria_status,
                vr.tipo retorno_tipo,vo.numero relatorio_origem_numero
            FROM agendamentos a
            LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id
            LEFT JOIN clientes c ON c.id=a.cliente_id
            LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2
                WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1)
            LEFT JOIN vistoria_retornos vr ON vr.agendamento_id=a.id
            LEFT JOIN vistorias vo ON vo.id=vr.relatorio_origem_id
            WHERE {$wVist}
              AND a.status IN ('pendente','confirmado','em_andamento')
              AND (v.id IS NULL OR v.status='PENDENTE')
            ORDER BY CASE vr.tipo WHEN 'AS' THEN 0 WHEN 'EXIGENCIAS' THEN 1 ELSE 2 END,
                     a.data_vistoria IS NULL,a.data_vistoria,a.hora_vistoria,a.created_at
            LIMIT 8",$params);
        $base['fila'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.status,e.nome embarcacao,v.id vistoria_id,v.numero,v.status vistoria_status,v.finalidade FROM agendamentos a LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND ((a.status IN ('pendente','confirmado','em_andamento') AND (v.id IS NULL OR v.status='PENDENTE')) OR (v.finalidade='CUMPRIMENTO_EXIGENCIAS' AND v.status='PENDENTE')) ORDER BY (v.finalidade='CUMPRIMENTO_EXIGENCIAS') DESC,(a.data_vistoria<CURDATE()) DESC,a.data_vistoria,a.hora_vistoria LIMIT 8",$params);
        $base['atribuicoes'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.created_at,e.nome embarcacao,e.registro,c.nome cliente FROM agendamentos a LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN clientes c ON c.id=a.cliente_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado') AND v.id IS NULL ORDER BY a.data_vistoria IS NULL,a.data_vistoria,a.hora_vistoria,a.created_at DESC LIMIT 4",$params);
        $base['proxima'] = null;
        foreach ($base['fila'] as $itemFila) {
            if (($itemFila['finalidade'] ?? 'VISTORIA') !== 'CUMPRIMENTO_EXIGENCIAS') {
                $base['proxima'] = $itemFila;
                break;
            }
        }
        $base['agenda_hoje'] = array_values(array_filter($base['fila'], fn($a)=>($a['finalidade'] ?? 'VISTORIA') !== 'CUMPRIMENTO_EXIGENCIAS' && $a['data_vistoria']===date('Y-m-d')));
        $base['agenda_semana'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.tipo_vistoria,e.nome embarcacao FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE {$wVist} AND a.status IN ('pendente','confirmado','em_andamento') AND (v.id IS NULL OR v.status='PENDENTE') AND a.data_vistoria BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) ORDER BY a.data_vistoria,a.hora_vistoria LIMIT 8",$params);
        $base['historico'] = dashRows($pdo,"SELECT v.id,v.numero,v.agendamento_id,v.data_vistoria,v.status,v.assinatura_status,v.finalidade,e.nome embarcacao,a.tipo_vistoria,EXISTS(SELECT 1 FROM vistorias vs WHERE vs.relatorio_anterior_id=v.id AND vs.status<>'CANCELADA') substituido FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE {$wHist} ORDER BY COALESCE(v.atualizado_em,v.criado_em) DESC LIMIT 6",$params);
        
        $paramsAlerta = array_merge($params, [':uid_alerta' => $usuarioId]);
        $base['relatorios_prontos_certificacao'] = dashRows($pdo, "SELECT
            v.id, v.numero, v.agendamento_id, v.embarcacao_id, v.data_vistoria, v.atualizado_em,
            e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
            c.nome AS cliente_nome, u.nome AS vistoriador_nome, v.assinatura_em, v.status
        FROM vistorias v
        JOIN embarcacoes e ON e.id = v.embarcacao_id
        LEFT JOIN agendamentos a ON a.id = v.agendamento_id
        LEFT JOIN clientes c ON c.id = COALESCE(a.cliente_id, e.proprietario_id)
        LEFT JOIN usuarios u ON u.id = COALESCE(a.vistoriador_id, v.criado_por)
        WHERE {$wHist}
          AND v.status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS')
          AND v.assinatura_status = 'ASSINADO'
          AND (
              EXISTS (
                  SELECT 1 FROM propostas_servicos ps
                  JOIN servicos s ON s.id = ps.servico_id
                  WHERE ps.proposta_id = a.proposta_id
                    AND s.certificado_modelo IN ('CSN', 'CNBL', 'CNARQ', 'LP', 'LC')
              )
              OR EXISTS (
                  SELECT 1 FROM servicos s_avulso
                  WHERE s_avulso.nome = a.tipo_vistoria
                    AND s_avulso.certificado_modelo IN ('CSN', 'CNBL', 'CNARQ', 'LP', 'LC')
              )
          )
          AND NOT EXISTS (
              SELECT 1 FROM alertas_dispensados ad
              WHERE ad.usuario_id = :uid_alerta
                AND ad.alerta_tipo = 'RELATORIO_PRONTO_CERTIFICADO'
                AND ad.referencia_id = v.id
          )
          AND NOT EXISTS (
              SELECT 1 FROM (
                  SELECT vistoria_id, relatorio_numero FROM certificados_csn WHERE ativo = 1
                  UNION ALL
                  SELECT vistoria_id, relatorio_numero FROM certificados_cnbl WHERE ativo = 1
                  UNION ALL
                  SELECT vistoria_id, relatorio_numero FROM certificados_cnarq WHERE ativo = 1
                  UNION ALL
                  SELECT vistoria_id, NULL AS relatorio_numero FROM certificados_lp WHERE ativo = 1
                  UNION ALL
                  SELECT vistoria_id, relatorio_numero FROM certificados_lc WHERE ativo = 1
              ) cert
              WHERE cert.vistoria_id = v.id 
                 OR (v.numero IS NOT NULL AND v.numero <> '' AND cert.relatorio_numero = v.numero)
          )
        ORDER BY COALESCE(v.assinatura_em, v.atualizado_em) DESC
        LIMIT 4", $paramsAlerta);
        return $base;
    }

    if ($cargo === 'ANALISTA') {
        $base['kpis'] = [
            'atribuidas' => (int)dashScalar($pdo, "SELECT COUNT(*) FROM analises_planos WHERE analista_id = :uid AND status IN ('AGUARDANDO_AGENDAMENTO','AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN')", [':uid' => $usuarioId]),
            'hoje' => (int)dashScalar($pdo, "SELECT COUNT(*) FROM analises_planos WHERE analista_id = :uid AND status IN ('AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS') AND DATE(prazo_agendado_em) = CURDATE()", [':uid' => $usuarioId]),
            'atrasadas' => (int)dashScalar($pdo, "SELECT COUNT(*) FROM analises_planos WHERE analista_id = :uid AND status IN ('AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS') AND DATE(prazo_agendado_em) < CURDATE()", [':uid' => $usuarioId]),
            'aguardando_docs' => (int)dashScalar($pdo, "SELECT COUNT(*) FROM analises_planos WHERE analista_id = :uid AND status = 'AGUARDANDO_DOCUMENTOS'", [':uid' => $usuarioId]),
            'concluidas_mes' => (int)dashScalar($pdo, "SELECT COUNT(*) FROM analises_planos WHERE analista_id = :uid AND status = 'CONCLUIDA' AND atualizado_em BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())", [':uid' => $usuarioId]),
        ];

        $base['fila_planos'] = dashRows($pdo, "SELECT ap.id, ap.numero, ap.objeto, ap.tipo_processo, ap.enquadramento, ap.classe_certificacao, ap.status, ap.prazo_agendado_em, ap.iniciado_em,
                     e.nome AS embarcacao_nome, c.nome AS solicitante_nome,
                     (SELECT COUNT(*) FROM analise_planos_exigencias x WHERE x.analise_id=ap.id AND x.status NOT IN ('CUMPRIDA','TRANSCRITA')) AS exigencias_pendentes,
                     (SELECT COUNT(*) FROM analise_planos_submissoes s WHERE s.analise_id=ap.id) AS total_revisoes
              FROM analises_planos ap
              INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
              LEFT JOIN clientes c ON c.id=ap.solicitante_id
              WHERE (ap.analista_id = :uid OR ap.analista_id IS NULL)
                AND ap.status NOT IN ('CONCLUIDA','REPROVADA','CANCELADA')
              ORDER BY ap.prazo_agendado_em IS NULL, ap.prazo_agendado_em ASC, ap.atualizado_em DESC
              LIMIT 12", [':uid' => $usuarioId]);

        $base['historico_pareceres'] = dashRows($pdo, "SELECT p.id, p.numero, p.finalidade, p.resultado, p.status, p.criado_em,
                     ap.id AS analise_id, ap.numero AS processo_numero, e.nome AS embarcacao_nome
              FROM analise_planos_pareceres p
              INNER JOIN analises_planos ap ON ap.id=p.analise_id
              INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
              WHERE p.criado_por = :u1 OR ap.analista_id = :u2
              ORDER BY p.criado_em DESC
              LIMIT 8", [':u1' => $usuarioId, ':u2' => $usuarioId]);

        // Fila auxiliar de vistorias caso também participe de aprovação
        $base['fila_vistorias'] = dashRows($pdo, "SELECT v.id, v.numero, v.agendamento_id, v.atualizado_em, e.nome AS embarcacao,
                     COALESCE(u.nome,'Sem vistoriador vinculado') AS vistoriador,
                     COUNT(DISTINCT va.id) AS fotos,
                     COUNT(DISTINCT CASE WHEN ve.conforme='nao' THEN ve.id END) AS nao_conformes,
                     TIMESTAMPDIFF(HOUR,v.atualizado_em,NOW()) AS horas
              FROM vistorias v
              JOIN embarcacoes e ON e.id=v.embarcacao_id
              LEFT JOIN agendamentos a ON a.id=v.agendamento_id
              LEFT JOIN usuarios u ON u.id=a.vistoriador_id
              LEFT JOIN vistoria_anexos va ON va.vistoria_id=v.id
              LEFT JOIN vistoria_exigencias ve ON ve.vistoria_id=v.id
              WHERE v.status='AGUARDANDO_APROVACAO'
                AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA')
              GROUP BY v.id, v.numero, v.agendamento_id, v.atualizado_em, e.nome, u.nome
              ORDER BY v.atualizado_em
              LIMIT 8");

        return $base;
    }

    if ($cargo === 'VENDEDOR') {
        $statuses = ['rascunho', 'enviada', 'assinada', 'recusada', 'cancelada'];
        $funil = array_fill_keys($statuses, 0);
        foreach (dashRows($pdo, "SELECT status, COUNT(*) total FROM propostas WHERE criado_por = :uid AND status IN ('rascunho','enviada','assinada','recusada','cancelada') GROUP BY status", $params) as $grupo) {
            $funil[$grupo['status']] = (int)$grupo['total'];
        }
        
        // Aguardando agendamento: Propostas assinadas criadas pelo vendedor que ainda não possuem vistoria agendada
        $funil['aguardando_agendamento'] = (int)dashScalar($pdo, "
            SELECT COUNT(*) 
            FROM propostas p 
            WHERE p.criado_por = :uid 
              AND p.assinado = 1 
              AND NOT EXISTS (
                  SELECT 1 FROM agendamentos a 
                  WHERE a.proposta_id = p.id 
                    AND a.status <> 'cancelado' 
                    AND a.data_vistoria IS NOT NULL 
                    AND a.vistoriador_id IS NOT NULL
              )
        ", $params);

        // Métricas do mês corrente
        $emitidas = (int)dashScalar($pdo, "SELECT COUNT(*) FROM propostas WHERE criado_por = :uid AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())", $params);
        $valorEmitidas = (float)dashScalar($pdo, "SELECT COALESCE(SUM(valor_total),0) FROM propostas WHERE criado_por = :uid AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())", $params);

        $assinadas = (int)dashScalar($pdo, "SELECT COUNT(*) FROM propostas WHERE criado_por = :uid AND assinado = 1 AND COALESCE(assinatura_em, created_at) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())", $params);
        $valorAssinadas = (float)dashScalar($pdo, "SELECT COALESCE(SUM(valor_total),0) FROM propostas WHERE criado_por = :uid AND assinado = 1 AND COALESCE(assinatura_em, created_at) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())", $params);

        // Receitas pagas (contribuição para a meta)
        $contrib = (float)dashScalar($pdo, "
            SELECT COALESCE(SUM(
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado 
                     WHEN fl.status = 'PAGO' THEN fl.valor_original 
                     ELSE 0 END
            ), 0)
            FROM financeiro_lancamentos fl 
            LEFT JOIN (
                SELECT lancamento_id, SUM(valor_pago) AS total_baixado
                FROM financeiro_historico_baixas
                GROUP BY lancamento_id
            ) b ON b.lancamento_id = fl.id
            LEFT JOIN propostas p ON p.id = fl.proposta_id 
            WHERE fl.ativo = 1 AND fl.tipo = 'RECEITA' 
              AND (fl.status = 'PAGO' OR b.total_baixado > 0)
              AND (fl.criado_por = :uid OR fl.responsavel_usuario_id = :uid2 OR p.criado_por = :uid3)
              AND fl.data BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        // Total a receber pendente
        $receber = (float)dashScalar($pdo, "
            SELECT COALESCE(SUM(fl.saldo_devedor), 0) 
            FROM financeiro_lancamentos fl 
            LEFT JOIN propostas p ON p.id = fl.proposta_id 
            WHERE fl.ativo = 1 AND fl.tipo = 'RECEITA' AND fl.status IN ('PENDENTE', 'PARCIAL') 
              AND (fl.criado_por = :uid OR fl.responsavel_usuario_id = :uid2 OR p.criado_por = :uid3)
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        // Total vencido
        $vencido = (float)dashScalar($pdo, "
            SELECT COALESCE(SUM(fl.saldo_devedor), 0) 
            FROM financeiro_lancamentos fl 
            LEFT JOIN propostas p ON p.id = fl.proposta_id 
            WHERE fl.ativo = 1 AND fl.tipo = 'RECEITA' AND fl.status IN ('PENDENTE', 'PARCIAL') 
              AND fl.data_vencimento < CURDATE()
              AND (fl.criado_por = :uid OR fl.responsavel_usuario_id = :uid2 OR p.criado_por = :uid3)
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        // Total de agendamentos ativos
        $totalAgendamentos = (int)dashScalar($pdo, "
            SELECT COUNT(*) 
            FROM agendamentos a 
            LEFT JOIN propostas p ON p.id = a.proposta_id 
            WHERE (a.vendedor_id = :uid OR a.criado_por = :uid2 OR p.criado_por = :uid3) 
              AND a.status IN ('pendente', 'confirmado', 'em_andamento')
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        $base['kpis'] = [
            'emitidas_mes'            => $emitidas,
            'valor_emitidas_mes'      => $valorEmitidas,
            'assinadas_mes'           => $assinadas,
            'valor_assinadas_mes'     => $valorAssinadas,
            'aguardando_agendamento'  => $funil['aguardando_agendamento'],
            'agendamentos_ativos'     => $totalAgendamentos,
            'financeiro_receber'      => $receber,
            'financeiro_recebido_mes' => $contrib,
            'financeiro_vencido'      => $vencido,
        ];

        $base['funil'] = $funil;
        $base['conversao'] = $emitidas ? round(($assinadas / $emitidas) * 100, 1) : 0;
        $base['contribuicao'] = $meta > 0 ? round(($contrib / $meta) * 100, 1) : 0;

        // Fila Crítica: Propostas assinadas aguardando agendamento de vistoria
        $base['fila_agendamentos'] = dashRows($pdo, "
            SELECT p.id AS proposta_id, p.numero, p.valor_total, p.assinatura_em, p.created_at,
                   c.id AS cliente_id, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                   COALESCE((SELECT GROUP_CONCAT(DISTINCT e.nome SEPARATOR ', ') FROM propostas_embarcacoes pe JOIN embarcacoes e ON e.id = pe.embarcacao_id WHERE pe.proposta_id = p.id), 'Embarcação da proposta') AS embarcacao_nome,
                   (SELECT a.id FROM agendamentos a WHERE a.proposta_id = p.id AND a.status <> 'cancelado' LIMIT 1) AS agendamento_id
            FROM propostas p
            JOIN clientes c ON c.id = p.cliente_id
            WHERE p.criado_por = :uid
              AND p.assinado = 1
              AND NOT EXISTS (
                  SELECT 1 FROM agendamentos ac 
                  WHERE ac.proposta_id = p.id 
                    AND ac.status <> 'cancelado' 
                    AND ac.data_vistoria IS NOT NULL 
                    AND ac.vistoriador_id IS NOT NULL
              )
            ORDER BY p.assinatura_em DESC, p.created_at DESC
            LIMIT 5
        ", [':uid' => $usuarioId]);

        $base['prioridade'] = $base['fila_agendamentos'][0] ?? null;

        // Próximos Agendamentos de Vistoria
        $base['proximos_agendamentos'] = dashRows($pdo, "
            SELECT a.id, a.data_vistoria, a.hora_vistoria, a.local, a.tipo_vistoria, a.status,
                   e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
                   c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                   u.nome AS vistoriador_nome, u.telefone AS vistoriador_telefone,
                   p.numero AS proposta_numero
            FROM agendamentos a
            JOIN embarcacoes e ON e.id = a.embarcacao_id
            LEFT JOIN clientes c ON c.id = a.cliente_id
            LEFT JOIN usuarios u ON u.id = a.vistoriador_id
            LEFT JOIN propostas p ON p.id = a.proposta_id
            WHERE (a.vendedor_id = :uid OR a.criado_por = :uid2 OR p.criado_por = :uid3)
              AND a.status IN ('pendente', 'confirmado', 'em_andamento')
            ORDER BY a.data_vistoria IS NULL, (a.data_vistoria < CURDATE()) DESC, a.data_vistoria ASC, a.hora_vistoria ASC
            LIMIT 5
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        // Controle Financeiro: Títulos recentes das propostas do vendedor
        $base['financeiro_recentes'] = dashRows($pdo, "
            SELECT fl.id, fl.descricao, fl.valor, fl.status, fl.data_vencimento, fl.data,
                   c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.chave_pix,
                   p.numero AS proposta_numero
            FROM financeiro_lancamentos fl
            LEFT JOIN clientes c ON c.id = fl.cliente_id
            LEFT JOIN propostas p ON p.id = fl.proposta_id
            WHERE fl.ativo = 1 AND fl.tipo = 'RECEITA'
              AND (fl.criado_por = :uid OR fl.responsavel_usuario_id = :uid2 OR p.criado_por = :uid3)
            ORDER BY fl.data_vencimento IS NULL, fl.data_vencimento DESC, fl.criado_em DESC
            LIMIT 5
        ", [':uid' => $usuarioId, ':uid2' => $usuarioId, ':uid3' => $usuarioId]);

        // Minhas Propostas Recentes
        $base['recentes'] = dashRows($pdo, "
            SELECT p.id, p.numero, p.valor_total, p.status, p.data_emissao, p.updated_at, p.created_at,
                   c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                   COALESCE((SELECT GROUP_CONCAT(DISTINCT e.nome SEPARATOR ', ') FROM propostas_embarcacoes pe JOIN embarcacoes e ON e.id = pe.embarcacao_id WHERE pe.proposta_id = p.id), 'Embarcação da proposta') AS embarcacao_nome
            FROM propostas p
            JOIN clientes c ON c.id = p.cliente_id
            WHERE p.criado_por = :uid
            ORDER BY p.updated_at DESC, p.created_at DESC
            LIMIT 8
        ", [':uid' => $usuarioId]);

        $base['acompanhamentos'] = array_values(array_filter($base['recentes'], fn($p) => in_array($p['status'], ['rascunho', 'enviada', 'assinada'], true)));

        // Carteira rápida de clientes ativos para novos negócios
        $base['carteira_clientes'] = dashRows($pdo, "
            SELECT c.id, c.nome, c.perfil, c.telefone, c.email, c.cpf_cnpj,
                   COUNT(DISTINCT ce.id) AS total_embarcacoes
            FROM clientes c
            LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status = 'ATIVO'
            WHERE c.ativo = 1 AND c.excluido_em IS NULL
            GROUP BY c.id
            ORDER BY c.criado_em DESC, c.nome ASC
            LIMIT 5
        ");

        return $base;
    }

    // ADMIN
    $certificadosMesSql = "SELECT SUM(total) FROM (SELECT COUNT(*) total FROM certificados_cht WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_cnarq WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_cnbl WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_csn WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_lc WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_lp WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())) certificados";
    $certificadosAnteriorSql = "SELECT SUM(total) FROM (SELECT COUNT(*) total FROM certificados_cht WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_cnarq WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_cnbl WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_csn WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_lc WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_lp WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))) certificados";
    $receitaAnterior = dashScalar($pdo,"SELECT COALESCE(SUM(CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN fl.status = 'PAGO' THEN fl.valor_original ELSE 0 END),0) FROM financeiro_lancamentos fl LEFT JOIN (SELECT lancamento_id, SUM(valor_pago) AS total_baixado FROM financeiro_historico_baixas GROUP BY lancamento_id) b ON b.lancamento_id = fl.id WHERE fl.ativo=1 AND fl.tipo='RECEITA' AND (fl.status='PAGO' OR b.total_baixado > 0) AND fl.data BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))");
    $receitaMesAtual = (float)dashScalar($pdo,"SELECT COALESCE(SUM(CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN fl.status = 'PAGO' THEN fl.valor_original ELSE 0 END),0) FROM financeiro_lancamentos fl LEFT JOIN (SELECT lancamento_id, SUM(valor_pago) AS total_baixado FROM financeiro_historico_baixas GROUP BY lancamento_id) b ON b.lancamento_id = fl.id WHERE fl.ativo=1 AND fl.tipo='RECEITA' AND (fl.status='PAGO' OR b.total_baixado > 0) AND fl.data BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())");
    $base['resumo_executivo'] = [
        'vistorias_mes'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE data_vistoria BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())"),
        'vistorias_planejadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status<>'cancelado' AND data_vistoria BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())"),
        'certificados_mes'=>(int)dashScalar($pdo,$certificadosMesSql),
        'certificados_anterior'=>(int)dashScalar($pdo,$certificadosAnteriorSql),
        'clientes_ativos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM clientes WHERE status='ATIVO'"),
        'clientes_novos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM clientes WHERE status='ATIVO' AND criado_em BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND DATE_ADD(LAST_DAY(CURDATE()),INTERVAL 1 DAY)"),
        'embarcacoes_total'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM embarcacoes WHERE ativo=1"),
        'analises_em_aberto'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM analises_planos WHERE status NOT IN ('CONCLUIDA','REPROVADA','CANCELADA')"),
        'dossies_total'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM protocolo_dossies"),
        'dossies_tramite'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM protocolo_dossies WHERE status IN ('ENVIADO_AO_ORGAO', 'PROTOCOLADO', 'EM_ANALISE_NO_ORGAO', 'EM_EXIGENCIA')"),
        'dossies_custodia'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM protocolo_movimentacao_itens WHERE requer_devolucao = 1 AND devolvido_em IS NULL"),
        'financeiro_receber'=>(float)dashScalar($pdo,"SELECT COALESCE(SUM(saldo_devedor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status IN ('PENDENTE','PARCIAL')"),
        'financeiro_recebido'=>$receitaMesAtual,
        'receita_anterior'=>$receitaAnterior,
        'variacao_receita'=>$receitaAnterior>0?round((($receitaMesAtual-$receitaAnterior)/$receitaAnterior)*100,1):null,
    ];
    $base['analises_recentes'] = dashRows($pdo, "SELECT 
        ap.id, ap.numero, ap.objeto, ap.tipo_processo, ap.status, 
        e.nome embarcacao, e.registro, c.nome solicitante, ap.prazo_agendado_em,
        u_an.nome analista_nome,
        s_serv.nome servico_nome,
        (SELECT COUNT(*) FROM analise_planos_arquivos ar JOIN analise_planos_submissoes s ON s.id = ar.submissao_id WHERE s.analise_id = ap.id AND s.origem IN ('PORTAL', 'SECRETARIA')) as arquivos_armador,
        (SELECT COUNT(*) FROM analise_planos_arquivos ar JOIN analise_planos_submissoes s ON s.id = ar.submissao_id WHERE s.analise_id = ap.id) as arquivos_total
        FROM analises_planos ap 
        JOIN embarcacoes e ON e.id = ap.embarcacao_id 
        LEFT JOIN clientes c ON c.id = ap.solicitante_id 
        LEFT JOIN usuarios u_an ON u_an.id = ap.analista_id
        LEFT JOIN servicos s_serv ON s_serv.id = ap.servico_id
        WHERE ap.status NOT IN ('CONCLUIDA','REPROVADA','CANCELADA') 
        ORDER BY ap.atualizado_em DESC LIMIT 6");
    $base['dossies_recentes'] = dashRows($pdo, "SELECT d.id, d.numero, d.assunto, d.status, d.protocolo_externo_numero, e.nome embarcacao, d.criado_em FROM protocolo_dossies d LEFT JOIN embarcacoes e ON e.id = d.embarcacao_id ORDER BY d.criado_em DESC LIMIT 4");
    $base['acoes']=['assinadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM propostas p WHERE p.assinado=1 AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.proposta_id=p.id AND a.status<>'cancelado' AND a.data_vistoria IS NOT NULL AND a.vistoriador_id IS NOT NULL)"),'vencidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status IN ('pendente','confirmado','em_andamento') AND data_vistoria<CURDATE()"),'aprovacao'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA')"),'retornos_as'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistoria_retornos WHERE status='PENDENTE_AGENDAMENTO'"),'emitir'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') AND v.assinatura_status='ASSINADO' AND NOT EXISTS (SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1 AND ve.conforme='nao' AND ve.status_item<>'cumprida')"),'documentos_pendentes'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM analises_planos ap WHERE ap.status NOT IN ('CONCLUIDA','REPROVADA','CANCELADA') AND NOT EXISTS (SELECT 1 FROM analise_planos_submissoes s JOIN analise_planos_arquivos ar ON ar.submissao_id = s.id WHERE s.analise_id = ap.id AND s.origem IN ('PORTAL', 'SECRETARIA'))")];
    $base['fluxo_documentos_pendentes'] = dashRows($pdo, "SELECT 
        ap.id, ap.numero, ap.objeto, ap.status, e.nome embarcacao, e.registro, c.nome cliente,
        u_an.nome analista_nome, s_serv.nome servico_nome
        FROM analises_planos ap 
        JOIN embarcacoes e ON e.id = ap.embarcacao_id 
        LEFT JOIN clientes c ON c.id = ap.solicitante_id 
        LEFT JOIN usuarios u_an ON u_an.id = ap.analista_id
        LEFT JOIN servicos s_serv ON s_serv.id = ap.servico_id
        WHERE ap.status NOT IN ('CONCLUIDA','REPROVADA','CANCELADA') 
        AND NOT EXISTS (SELECT 1 FROM analise_planos_submissoes s JOIN analise_planos_arquivos ar ON ar.submissao_id = s.id WHERE s.analise_id = ap.id AND s.origem IN ('PORTAL', 'SECRETARIA'))
        ORDER BY ap.created_at ASC LIMIT 4");
    $base['fluxo_assinadas'] = dashRows($pdo,"SELECT 
        p.id proposta_id, p.numero, p.assinatura_em, c.nome cliente,
        a.id agendamento_id, a.data_vistoria, a.vistoriador_id,
        COALESCE(e.nome,(SELECT GROUP_CONCAT(DISTINCT ep.nome ORDER BY ep.nome SEPARATOR ', ') FROM propostas_embarcacoes pe JOIN embarcacoes ep ON ep.id=pe.embarcacao_id WHERE pe.proposta_id=p.id),'Embarcação da proposta') embarcacao,
        (SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON s.id = ps.servico_id WHERE ps.proposta_id = p.id AND (s.executor_cargo IN ('VISTORIADOR', 'AMBOS') OR s.executor_cargo IS NULL)) AS qtd_vistoria,
        (SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON s.id = ps.servico_id WHERE ps.proposta_id = p.id AND s.executor_cargo = 'ANALISTA') AS qtd_analise,
        (SELECT ap.id FROM analises_planos ap WHERE ap.proposta_id = p.id AND ap.status NOT IN ('CANCELADA', 'REPROVADA') LIMIT 1) AS analise_id
    FROM propostas p 
    JOIN clientes c ON c.id=p.cliente_id 
    LEFT JOIN agendamentos a ON a.id=(SELECT a2.id FROM agendamentos a2 WHERE a2.proposta_id=p.id AND a2.status<>'cancelado' ORDER BY a2.created_at DESC LIMIT 1) 
    LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id 
    WHERE p.assinado=1 
      AND (
          -- Se tem vistoria, checa se agendamento com vistoriador foi concluido
          ((SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON s.id = ps.servico_id WHERE ps.proposta_id = p.id AND (s.executor_cargo IN ('VISTORIADOR', 'AMBOS') OR s.executor_cargo IS NULL)) > 0
           AND NOT EXISTS (SELECT 1 FROM agendamentos ac WHERE ac.proposta_id=p.id AND ac.status<>'cancelado' AND ac.data_vistoria IS NOT NULL AND ac.vistoriador_id IS NOT NULL))
          OR
          -- Se tem analise e nao tem vistoria, checa se analise foi criada
          ((SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON s.id = ps.servico_id WHERE ps.proposta_id = p.id AND (s.executor_cargo IN ('VISTORIADOR', 'AMBOS') OR s.executor_cargo IS NULL)) = 0
           AND (SELECT COUNT(*) FROM propostas_servicos ps JOIN servicos s ON s.id = ps.servico_id WHERE ps.proposta_id = p.id AND s.executor_cargo = 'ANALISTA') > 0
           AND NOT EXISTS (SELECT 1 FROM analises_planos ap WHERE ap.proposta_id = p.id AND ap.status NOT IN ('CANCELADA', 'REPROVADA')))
      )
    ORDER BY COALESCE(p.assinatura_em,p.updated_at,p.created_at) ASC LIMIT 4");
    $base['fluxo_aprovacoes'] = dashRows($pdo,"SELECT v.id,v.numero,v.agendamento_id,v.atualizado_em,e.nome embarcacao,COALESCE(u.nome,'Sem vistoriador') vistoriador,TIMESTAMPDIFF(HOUR,v.atualizado_em,NOW()) horas,(SELECT COUNT(*) FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.conforme='nao') nao_conformes,(SELECT COUNT(*) FROM vistoria_anexos va WHERE va.vistoria_id=v.id) fotos FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id LEFT JOIN agendamentos a ON a.id=v.agendamento_id LEFT JOIN usuarios u ON u.id=a.vistoriador_id WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA') ORDER BY v.atualizado_em ASC LIMIT 4");
    $base['fluxo_retornos_as'] = dashRows($pdo,"SELECT vr.relatorio_origem_id,vr.tipo,v.numero,e.nome embarcacao,vr.criado_em FROM vistoria_retornos vr JOIN vistorias v ON v.id=vr.relatorio_origem_id JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE vr.status='PENDENTE_AGENDAMENTO' ORDER BY CASE vr.tipo WHEN 'AS' THEN 0 ELSE 1 END,vr.criado_em LIMIT 4");
    $aprovadas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')"); $analisadas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA')");
    $base['kpis']=['agenda_hoje'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE data_vistoria=CURDATE() AND status<>'cancelado'"),'execucao'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status='em_andamento'"),'exigencias_vencidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistoria_exigencias WHERE status_item='pendente' AND vencimento<CURDATE()"),'aprovacao'=>$analisadas?round(($aprovadas/$analisadas)*100):0];
    $base['agenda']=dashRows($pdo,"SELECT a.id,a.hora_vistoria,a.tipo_vistoria,e.nome embarcacao,u.nome vistoriador FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN usuarios u ON u.id=a.vistoriador_id WHERE a.data_vistoria=CURDATE() AND a.status<>'cancelado' ORDER BY a.hora_vistoria LIMIT 6");
    $base['equipe']=dashRows($pdo,"SELECT u.id,u.nome,COUNT(DISTINCT CASE WHEN a.data_vistoria>=CURDATE() AND a.status IN ('pendente','confirmado','em_andamento') THEN a.id END) futuras,COUNT(DISTINCT CASE WHEN a.data_vistoria<CURDATE() AND a.status IN ('pendente','confirmado','em_andamento') THEN a.id END) atrasadas,COUNT(DISTINCT CASE WHEN v.status='AGUARDANDO_APROVACAO' THEN v.id END) relatorios FROM usuarios u LEFT JOIN agendamentos a ON a.vistoriador_id=u.id LEFT JOIN vistorias v ON v.agendamento_id=a.id WHERE u.ativo=1 AND u.cargo='VISTORIADOR' GROUP BY u.id,u.nome ORDER BY atrasadas DESC,futuras DESC");
    $rawAtividades = dashRows($pdo, "SELECT l.acao, l.descricao, l.criado_em, u.nome usuario FROM logs_atividade l LEFT JOIN usuarios u ON u.id = l.usuario_id ORDER BY l.criado_em DESC LIMIT 6");
    $base['atividade'] = [];
    foreach ($rawAtividades as $rawAtiv) {
        $base['atividade'][] = formatarLogAtividadeDidatico($rawAtiv, $pdo);
    }

    $base['relatorios_prontos_certificacao'] = dashRows($pdo, "SELECT
        v.id, v.numero, v.agendamento_id, v.embarcacao_id, v.data_vistoria, v.atualizado_em,
        e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
        c.nome AS cliente_nome, u.nome AS vistoriador_nome, v.assinatura_em, v.status
    FROM vistorias v
    JOIN embarcacoes e ON e.id = v.embarcacao_id
    LEFT JOIN agendamentos a ON a.id = v.agendamento_id
    LEFT JOIN clientes c ON c.id = COALESCE(a.cliente_id, e.proprietario_id)
    LEFT JOIN usuarios u ON u.id = COALESCE(a.vistoriador_id, v.criado_por)
    WHERE v.status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS')
      AND v.assinatura_status = 'ASSINADO'
          AND (
              EXISTS (
                  SELECT 1 FROM propostas_servicos ps
                  JOIN servicos s ON s.id = ps.servico_id
                  WHERE ps.proposta_id = a.proposta_id
                    AND s.certificado_modelo IN ('CSN', 'CNBL', 'CNARQ', 'LP', 'LC')
              )
              OR EXISTS (
                  SELECT 1 FROM servicos s_avulso
                  WHERE s_avulso.nome = a.tipo_vistoria
                    AND s_avulso.certificado_modelo IN ('CSN', 'CNBL', 'CNARQ', 'LP', 'LC')
              )
          )
      AND NOT EXISTS (
          SELECT 1 FROM alertas_dispensados ad
          WHERE ad.usuario_id = :uid_alerta
            AND ad.alerta_tipo = 'RELATORIO_PRONTO_CERTIFICADO'
            AND ad.referencia_id = v.id
      )
      AND NOT EXISTS (
          SELECT 1 FROM (
              SELECT vistoria_id, relatorio_numero FROM certificados_csn WHERE ativo = 1
              UNION ALL
              SELECT vistoria_id, relatorio_numero FROM certificados_cnbl WHERE ativo = 1
              UNION ALL
              SELECT vistoria_id, relatorio_numero FROM certificados_cnarq WHERE ativo = 1
              UNION ALL
              SELECT vistoria_id, NULL AS relatorio_numero FROM certificados_lp WHERE ativo = 1
              UNION ALL
              SELECT vistoria_id, relatorio_numero FROM certificados_lc WHERE ativo = 1
          ) cert
          WHERE cert.vistoria_id = v.id 
             OR (v.numero IS NOT NULL AND v.numero <> '' AND cert.relatorio_numero = v.numero)
      )
    ORDER BY COALESCE(v.assinatura_em, v.atualizado_em) DESC
    LIMIT 6", [':uid_alerta' => $usuarioId]);
    $receitasPorMes=[];
    foreach(dashRows($pdo,"SELECT DATE_FORMAT(fl.data,'%Y-%m') mes, SUM(CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN fl.status = 'PAGO' THEN fl.valor_original ELSE 0 END) valor FROM financeiro_lancamentos fl LEFT JOIN (SELECT lancamento_id, SUM(valor_pago) AS total_baixado FROM financeiro_historico_baixas GROUP BY lancamento_id) b ON b.lancamento_id = fl.id WHERE fl.ativo=1 AND fl.tipo='RECEITA' AND (fl.status='PAGO' OR b.total_baixado > 0) AND fl.data>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY DATE_FORMAT(fl.data,'%Y-%m')") as $receitaMes) $receitasPorMes[$receitaMes['mes']] = (float)$receitaMes['valor'];
    $base['meses']=[]; for($i=5;$i>=0;$i--){$ini=date('Y-m-01',strtotime("-$i months"));$chave=date('Y-m',strtotime($ini));$base['meses'][]=['label'=>date('m/Y',strtotime($ini)),'valor'=>$receitasPorMes[$chave]??0];}
    $base['vistorias_recentes'] = dashRows($pdo, "SELECT
        v.id, v.numero, v.agendamento_id, v.status, v.data_vistoria,
        e.nome embarcacao, e.registro,
        COALESCE(NULLIF(a.tipo_vistoria, ''), 'Vistoria tecnica') servico,
        COALESCE(u.nome, 'Nao atribuido') vistoriador,
        (SELECT COUNT(*) FROM vistoria_checklist_respostas r WHERE r.vistoria_id = v.id) respondidos,
        (SELECT COUNT(*) FROM exigencias_catalogo ec WHERE ec.ativo = 1) total_itens,
        e.foto_url,
        (SELECT COUNT(*) FROM vistoria_anexos va WHERE va.vistoria_id = v.id AND va.excluido_em IS NULL) total_fotos
        ,EXISTS(SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1 AND ve.conforme='nao' AND ve.status_item<>'cumprida') possui_as
      FROM vistorias v
      JOIN embarcacoes e ON e.id = v.embarcacao_id
      LEFT JOIN agendamentos a ON a.id = v.agendamento_id
      LEFT JOIN usuarios u ON u.id = a.vistoriador_id
      ORDER BY COALESCE(v.mobile_finalizada_em, v.atualizado_em, v.criado_em) DESC
      LIMIT 6");
    foreach ($base['vistorias_recentes'] as &$vistoriaRecente) {
        $respondidos = (int)$vistoriaRecente['respondidos'];
        $totalItens = (int)$vistoriaRecente['total_itens'];
        if (in_array($vistoriaRecente['status'], ['AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS', 'RETORNO_AS', 'REPROVADA'], true)) {
            $vistoriaRecente['progresso'] = 100;
        } elseif ($respondidos > 0 && $totalItens > 0) {
            $vistoriaRecente['progresso'] = min(100, (int)round(($respondidos / $totalItens) * 100));
        } else {
            $vistoriaRecente['progresso'] = 0;
        }
        $vistoriaRecente['url'] = $vistoriaRecente['agendamento_id']
            ? APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($vistoriaRecente['agendamento_id'])
            : APP_URL . 'vistorias/detalhe?id=' . urlencode($vistoriaRecente['id']);
    }
    unset($vistoriaRecente);
    return $base;
}

/**
 * Carrega dados do dashboard com cache temporário inteligente (TTL padrão 45s).
 * Garante tempo de resposta sub-5ms em navegações frequentes sem alterar nenhum dado.
 */
function dashboardGetCachedData(PDO $pdo, string $cargo, string $usuarioId, bool $forceRefresh = false, int $ttlSeconds = 45): array
{
    $cacheDir = sys_get_temp_dir() . '/erp_cache_dashboard';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }

    $cacheKey = 'dash_' . md5($cargo . '_' . $usuarioId . '_' . date('Y-m-d'));
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

    if (!$forceRefresh && file_exists($cacheFile)) {
        $mtime = filemtime($cacheFile);
        $age = time() - $mtime;
        if ($age < $ttlSeconds) {
            $content = file_get_contents($cacheFile);
            $cachedData = json_decode($content, true);
            if (is_array($cachedData)) {
                $cachedData['_cache'] = [
                    'cached' => true,
                    'cached_at' => $mtime,
                    'age' => $age,
                ];
                return $cachedData;
            }
        }
    }

    $data = dashboardLoadData($pdo, $cargo, $usuarioId);
    $data['_cache'] = [
        'cached' => false,
        'cached_at' => time(),
        'age' => 0,
    ];

    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    return $data;
}

/**
 * Invalida cache do dashboard para atualização imediata
 */
function dashboardInvalidarCache(?string $cargo = null, ?string $usuarioId = null): void
{
    $cacheDir = sys_get_temp_dir() . '/erp_cache_dashboard';
    if (!is_dir($cacheDir)) {
        return;
    }

    if ($cargo !== null && $usuarioId !== null) {
        $cacheKey = 'dash_' . md5($cargo . '_' . $usuarioId . '_' . date('Y-m-d'));
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        return;
    }

    $files = glob($cacheDir . '/dash_*.json');
    if ($files) {
        foreach ($files as $f) {
            @unlink($f);
        }
    }
}

