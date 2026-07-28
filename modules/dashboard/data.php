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

    if ($cargo !== 'ADMIN') {
        $escritorios = financeiroEscritoriosUsuario($pdo, $usuarioId);
        $escritorioId = $escritorios[0]['id'] ?? null;
    }

    if ($cargo === 'ADMIN') {
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

    $escopo = $cargo === 'ADMIN' ? 'todos os escritórios' : (string)($escritorios[0]['nome'] ?? 'seu escritório');
    return ['valor'=>$meta, 'realizado'=>$realizado, 'mensagem'=>$mensagem, 'escopo'=>$escopo];
}

function dashActivityUrl(array $log): ?string
{
    $acao = strtolower((string)($log['acao'] ?? ''));
    $descricao = (string)($log['descricao'] ?? '');
    if (str_contains($acao, 'agendamento') && preg_match('/[0-9a-f]{8}-[0-9a-f-]{27}/i', $descricao, $m)) return APP_URL.'agendamentos/form?id='.urlencode($m[0]);
    if (str_contains(strtolower($descricao), 'relatorio tecnico') && preg_match('/agendamento ID:\s*([0-9a-f-]{36})/i', $descricao, $m)) return APP_URL.'vistorias/relatorio?agendamento_id='.urlencode($m[1]);
    if (preg_match('/Proposta\s+([A-Z0-9-]+\/\d+)/i', $descricao, $m)) return APP_URL.'comercial?busca='.urlencode($m[1]);
    if (str_contains($acao, 'certificado')) return APP_URL.'certificados';
    if (str_contains($acao, 'vistoria')) return APP_URL.'vistorias';
    if (str_contains($acao, 'usuario')) return APP_URL.'usuarios';
    return null;
}

function dashboardLoadData(PDO $pdo, string $cargo, string $usuarioId): array
{
    $metaMes = dashMetaDoMes($pdo, $cargo, $usuarioId);
    $meta = (float)$metaMes['valor'];
    $recebido = (float)$metaMes['realizado'];
    $base = ['meta'=>['valor'=>$meta,'realizado'=>$recebido,'percentual'=>$meta>0?round(($recebido/$meta)*100,1):0.0,'mensagem'=>$metaMes['mensagem'],'escopo'=>$metaMes['escopo']]];
    $params = [':uid'=>$usuarioId];

    if ($cargo === 'VISTORIADOR') {
        $base['kpis'] = [
            'atrasadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria<CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'hoje'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria=CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'proximas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado','em_andamento') AND a.data_vistoria>CURDATE() AND (v.id IS NULL OR v.status='PENDENTE')",$params),
            'aguardando'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos a LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado') AND a.data_vistoria>=CURDATE() AND v.id IS NULL",$params),
            'rascunhos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE a.vistoriador_id=:uid AND v.status='PENDENTE'",$params),
            'enviados'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE a.vistoriador_id=:uid AND v.status='AGUARDANDO_APROVACAO'",$params),
            'concluidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE a.vistoriador_id=:uid AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA') AND v.data_aprovacao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params),
        ];
        $base['agenda_prioritaria'] = dashRows($pdo,"SELECT
                a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.status,
                e.nome embarcacao,COALESCE(NULLIF(e.registro,''),e.numero_inscricao) registro,
                c.nome cliente,v.id vistoria_id,v.status vistoria_status,
                vr.tipo retorno_tipo,vo.numero relatorio_origem_numero
            FROM agendamentos a
            JOIN embarcacoes e ON e.id=a.embarcacao_id
            JOIN clientes c ON c.id=a.cliente_id
            LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2
                WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1)
            LEFT JOIN vistoria_retornos vr ON vr.agendamento_id=a.id
            LEFT JOIN vistorias vo ON vo.id=vr.relatorio_origem_id
            WHERE a.vistoriador_id=:uid
              AND a.status IN ('pendente','confirmado','em_andamento')
              AND (v.id IS NULL OR v.status='PENDENTE')
            ORDER BY CASE vr.tipo WHEN 'AS' THEN 0 WHEN 'EXIGENCIAS' THEN 1 ELSE 2 END,
                     a.data_vistoria IS NULL,a.data_vistoria,a.hora_vistoria,a.created_at
            LIMIT 8",$params);
        $base['fila'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.status,e.nome embarcacao,v.id vistoria_id,v.numero,v.status vistoria_status,v.finalidade FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND ((a.status IN ('pendente','confirmado','em_andamento') AND (v.id IS NULL OR v.status='PENDENTE')) OR (v.finalidade='CUMPRIMENTO_EXIGENCIAS' AND v.status='PENDENTE')) ORDER BY (v.finalidade='CUMPRIMENTO_EXIGENCIAS') DESC,(a.data_vistoria<CURDATE()) DESC,a.data_vistoria,a.hora_vistoria LIMIT 8",$params);
        $base['atribuicoes'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.local,a.tipo_vistoria,a.created_at,e.nome embarcacao,e.registro,c.nome cliente FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id JOIN clientes c ON c.id=a.cliente_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado') AND v.id IS NULL ORDER BY a.data_vistoria IS NULL,a.data_vistoria,a.hora_vistoria,a.created_at DESC LIMIT 4",$params);
        $base['proxima'] = null;
        foreach ($base['fila'] as $itemFila) {
            if (($itemFila['finalidade'] ?? 'VISTORIA') !== 'CUMPRIMENTO_EXIGENCIAS') {
                $base['proxima'] = $itemFila;
                break;
            }
        }
        $base['agenda_hoje'] = array_values(array_filter($base['fila'], fn($a)=>($a['finalidade'] ?? 'VISTORIA') !== 'CUMPRIMENTO_EXIGENCIAS' && $a['data_vistoria']===date('Y-m-d')));
        $base['agenda_semana'] = dashRows($pdo,"SELECT a.id,a.data_vistoria,a.hora_vistoria,a.tipo_vistoria,e.nome embarcacao FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN vistorias v ON v.id=(SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC,v2.id DESC LIMIT 1) WHERE a.vistoriador_id=:uid AND a.status IN ('pendente','confirmado','em_andamento') AND (v.id IS NULL OR v.status='PENDENTE') AND a.data_vistoria BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) ORDER BY a.data_vistoria,a.hora_vistoria LIMIT 8",$params);
        $base['historico'] = dashRows($pdo,"SELECT v.id,v.numero,v.agendamento_id,v.data_vistoria,v.status,v.finalidade,e.nome embarcacao,a.tipo_vistoria,EXISTS(SELECT 1 FROM vistorias vs WHERE vs.relatorio_anterior_id=v.id AND vs.status<>'CANCELADA') substituido FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE a.vistoriador_id=:uid ORDER BY COALESCE(v.atualizado_em,v.criado_em) DESC LIMIT 6",$params);
        return $base;
    }

    if ($cargo === 'ANALISTA') {
        $base['fila'] = dashRows($pdo,"SELECT v.id,v.numero,v.agendamento_id,v.atualizado_em,e.nome embarcacao,COALESCE(u.nome,'Sem vistoriador vinculado') vistoriador,COUNT(DISTINCT va.id) fotos,COUNT(DISTINCT CASE WHEN ve.conforme='nao' THEN ve.id END) nao_conformes,TIMESTAMPDIFF(HOUR,v.atualizado_em,NOW()) horas FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id LEFT JOIN agendamentos a ON a.id=v.agendamento_id LEFT JOIN usuarios u ON u.id=a.vistoriador_id LEFT JOIN vistoria_anexos va ON va.vistoria_id=v.id LEFT JOIN vistoria_exigencias ve ON ve.vistoria_id=v.id WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA') GROUP BY v.id,v.numero,v.agendamento_id,v.atualizado_em,e.nome,u.nome ORDER BY v.atualizado_em LIMIT 12");
        $base['kpis'] = ['aguardando'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA')"),'mais_antigo'=>(int)($base['fila'][0]['horas']??0),'nao_conformes'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistoria_exigencias ve JOIN vistorias v ON v.id=ve.vistoria_id WHERE v.status='AGUARDANDO_APROVACAO' AND ve.conforme='nao' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA')"),'concluidas_mes'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE aprovado_por=:uid AND data_aprovacao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params)];
        $base['vencidas'] = count(array_filter($base['fila'],fn($r)=>(int)$r['horas']>=48));
        $base['historico'] = dashRows($pdo,"SELECT v.numero,v.data_aprovacao,v.status,e.nome embarcacao FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE v.aprovado_por=:uid ORDER BY v.data_aprovacao DESC LIMIT 8",$params);
        return $base;
    }

    if ($cargo === 'VENDEDOR') {
        $statuses=['rascunho','enviada','assinada','recusada']; $funil=array_fill_keys($statuses,0);
        foreach(dashRows($pdo,"SELECT status,COUNT(*) total FROM propostas WHERE criado_por=:uid AND status IN ('rascunho','enviada','assinada','recusada') GROUP BY status",$params) as $grupo) $funil[$grupo['status']] = (int)$grupo['total'];
        $funil['aguardando_agendamento']=(int)dashScalar($pdo,"SELECT COUNT(*) FROM propostas p WHERE p.criado_por=:uid AND p.assinado=1 AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.proposta_id=p.id AND a.status<>'cancelado' AND a.data_vistoria IS NOT NULL AND a.vistoriador_id IS NOT NULL)",$params);
        $emitidas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM propostas WHERE criado_por=:uid AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params);
        $assinadas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM propostas WHERE criado_por=:uid AND assinado=1 AND COALESCE(assinatura_em,created_at) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params);
        $contrib= dashScalar($pdo,"SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status='PAGO' AND criado_por=:uid AND data BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",$params);
        $base['funil']=$funil; $base['conversao']=$emitidas?round(($assinadas/$emitidas)*100,1):0; $base['contribuicao']=$meta>0?round(($contrib/$meta)*100,1):0;
        $base['prioridade']=dashRows($pdo,"SELECT DISTINCT p.id proposta_id,a.id agendamento_id,p.numero,c.nome cliente,COALESCE(e.nome,ep.nome,'Embarcação da proposta') embarcacao,p.assinatura_em FROM propostas p JOIN clientes c ON c.id=p.cliente_id LEFT JOIN agendamentos a ON a.proposta_id=p.id AND a.status<>'cancelado' LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN propostas_embarcacoes pe ON pe.proposta_id=p.id LEFT JOIN embarcacoes ep ON ep.id=pe.embarcacao_id WHERE p.criado_por=:uid AND p.assinado=1 AND NOT EXISTS (SELECT 1 FROM agendamentos ac WHERE ac.proposta_id=p.id AND ac.status<>'cancelado' AND ac.data_vistoria IS NOT NULL AND ac.vistoriador_id IS NOT NULL) ORDER BY p.assinatura_em DESC LIMIT 1",$params)[0]??null;
        $base['recentes']=dashRows($pdo,"SELECT p.id,p.numero,p.status,p.data_emissao,p.updated_at,c.nome cliente FROM propostas p JOIN clientes c ON c.id=p.cliente_id WHERE p.criado_por=:uid ORDER BY p.updated_at DESC LIMIT 8",$params);
        $base['acompanhamentos']=array_values(array_filter($base['recentes'],fn($p)=>in_array($p['status'],['rascunho','enviada','assinada'],true)));
        return $base;
    }

    // ADMIN
    $certificadosMesSql = "SELECT SUM(total) FROM (SELECT COUNT(*) total FROM certificados_cht WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_cnarq WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_cnbl WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_csn WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_lc WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) UNION ALL SELECT COUNT(*) FROM certificados_lp WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())) certificados";
    $certificadosAnteriorSql = "SELECT SUM(total) FROM (SELECT COUNT(*) total FROM certificados_cht WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_cnarq WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_cnbl WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_csn WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_lc WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) UNION ALL SELECT COUNT(*) FROM certificados_lp WHERE ativo=1 AND status<>'cancelado' AND data_emissao BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))) certificados";
    $receitaAnterior = dashScalar($pdo,"SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status='PAGO' AND data BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m-01') AND LAST_DAY(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))");
    $base['resumo_executivo'] = [
        'vistorias_mes'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE data_vistoria BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())"),
        'vistorias_planejadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status<>'cancelado' AND data_vistoria BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())"),
        'certificados_mes'=>(int)dashScalar($pdo,$certificadosMesSql),
        'certificados_anterior'=>(int)dashScalar($pdo,$certificadosAnteriorSql),
        'clientes_ativos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM clientes WHERE status='ATIVO'"),
        'clientes_novos'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM clientes WHERE status='ATIVO' AND criado_em BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND DATE_ADD(LAST_DAY(CURDATE()),INTERVAL 1 DAY)"),
        'receita_anterior'=>$receitaAnterior,
        'variacao_receita'=>$receitaAnterior>0?round((($recebido-$receitaAnterior)/$receitaAnterior)*100,1):null,
    ];
    $base['acoes']=['assinadas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM propostas p WHERE p.assinado=1 AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.proposta_id=p.id AND a.status<>'cancelado' AND a.data_vistoria IS NOT NULL AND a.vistoriador_id IS NOT NULL)"),'vencidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status IN ('pendente','confirmado','em_andamento') AND data_vistoria<CURDATE()"),'aprovacao'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA')"),'retornos_as'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistoria_retornos WHERE status='PENDENTE_AGENDAMENTO'"),'emitir'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias v WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') AND v.assinatura_status='ASSINADO' AND NOT EXISTS (SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1 AND ve.conforme='nao' AND ve.status_item<>'cumprida')")];
    $base['fluxo_assinadas'] = dashRows($pdo,"SELECT p.id proposta_id,p.numero,p.assinatura_em,c.nome cliente,a.id agendamento_id,a.data_vistoria,a.vistoriador_id,COALESCE(e.nome,(SELECT GROUP_CONCAT(DISTINCT ep.nome ORDER BY ep.nome SEPARATOR ', ') FROM propostas_embarcacoes pe JOIN embarcacoes ep ON ep.id=pe.embarcacao_id WHERE pe.proposta_id=p.id),'Embarcação da proposta') embarcacao FROM propostas p JOIN clientes c ON c.id=p.cliente_id LEFT JOIN agendamentos a ON a.id=(SELECT a2.id FROM agendamentos a2 WHERE a2.proposta_id=p.id AND a2.status<>'cancelado' ORDER BY a2.created_at DESC LIMIT 1) LEFT JOIN embarcacoes e ON e.id=a.embarcacao_id WHERE p.assinado=1 AND NOT EXISTS (SELECT 1 FROM agendamentos ac WHERE ac.proposta_id=p.id AND ac.status<>'cancelado' AND ac.data_vistoria IS NOT NULL AND ac.vistoriador_id IS NOT NULL) ORDER BY COALESCE(p.assinatura_em,p.updated_at,p.created_at) ASC LIMIT 4");
    $base['fluxo_aprovacoes'] = dashRows($pdo,"SELECT v.id,v.numero,v.agendamento_id,v.atualizado_em,e.nome embarcacao,COALESCE(u.nome,'Sem vistoriador') vistoriador,TIMESTAMPDIFF(HOUR,v.atualizado_em,NOW()) horas,(SELECT COUNT(*) FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.conforme='nao') nao_conformes,(SELECT COUNT(*) FROM vistoria_anexos va WHERE va.vistoria_id=v.id) fotos FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id LEFT JOIN agendamentos a ON a.id=v.agendamento_id LEFT JOIN usuarios u ON u.id=a.vistoriador_id WHERE v.status='AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA') ORDER BY v.atualizado_em ASC LIMIT 4");
    $base['fluxo_retornos_as'] = dashRows($pdo,"SELECT vr.relatorio_origem_id,vr.tipo,v.numero,e.nome embarcacao,vr.criado_em FROM vistoria_retornos vr JOIN vistorias v ON v.id=vr.relatorio_origem_id JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE vr.status='PENDENTE_AGENDAMENTO' ORDER BY CASE vr.tipo WHEN 'AS' THEN 0 ELSE 1 END,vr.criado_em LIMIT 4");
    $aprovadas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')"); $analisadas=(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistorias WHERE status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA')");
    $base['kpis']=['agenda_hoje'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE data_vistoria=CURDATE() AND status<>'cancelado'"),'execucao'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM agendamentos WHERE status='em_andamento'"),'exigencias_vencidas'=>(int)dashScalar($pdo,"SELECT COUNT(*) FROM vistoria_exigencias WHERE status_item='pendente' AND vencimento<CURDATE()"),'aprovacao'=>$analisadas?round(($aprovadas/$analisadas)*100):0];
    $base['agenda']=dashRows($pdo,"SELECT a.id,a.hora_vistoria,a.tipo_vistoria,e.nome embarcacao,u.nome vistoriador FROM agendamentos a JOIN embarcacoes e ON e.id=a.embarcacao_id LEFT JOIN usuarios u ON u.id=a.vistoriador_id WHERE a.data_vistoria=CURDATE() AND a.status<>'cancelado' ORDER BY a.hora_vistoria LIMIT 6");
    $base['equipe']=dashRows($pdo,"SELECT u.id,u.nome,COUNT(DISTINCT CASE WHEN a.data_vistoria>=CURDATE() AND a.status IN ('pendente','confirmado','em_andamento') THEN a.id END) futuras,COUNT(DISTINCT CASE WHEN a.data_vistoria<CURDATE() AND a.status IN ('pendente','confirmado','em_andamento') THEN a.id END) atrasadas,COUNT(DISTINCT CASE WHEN v.status='AGUARDANDO_APROVACAO' THEN v.id END) relatorios FROM usuarios u LEFT JOIN agendamentos a ON a.vistoriador_id=u.id LEFT JOIN vistorias v ON v.agendamento_id=a.id WHERE u.ativo=1 AND u.cargo='VISTORIADOR' GROUP BY u.id,u.nome ORDER BY atrasadas DESC,futuras DESC");
    $base['atividade']=dashRows($pdo,"SELECT l.acao,l.descricao,l.criado_em,u.nome usuario FROM logs_atividade l LEFT JOIN usuarios u ON u.id=l.usuario_id ORDER BY l.criado_em DESC LIMIT 5");
    foreach ($base['atividade'] as &$atividade) $atividade['url'] = dashActivityUrl($atividade);
    unset($atividade);
    $receitasPorMes=[];
    foreach(dashRows($pdo,"SELECT DATE_FORMAT(data,'%Y-%m') mes,SUM(valor) valor FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status='PAGO' AND data>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY DATE_FORMAT(data,'%Y-%m')") as $receitaMes) $receitasPorMes[$receitaMes['mes']] = (float)$receitaMes['valor'];
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
