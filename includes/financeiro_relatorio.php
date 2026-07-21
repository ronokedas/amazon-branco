<?php

require_once __DIR__ . '/financeiro_escritorios.php';

function financeiroRelatorioRealizado(PDO $pdo, string $tipo, string $inicio, string $fim, ?string $escritorioId = null, ?string $usuarioId = null): float
{
    $filtros1 = '';
    $filtros2 = '';
    $params = [':tipo1'=>$tipo, ':ini1'=>$inicio, ':fim1'=>$fim, ':tipo2'=>$tipo, ':ini2'=>$inicio, ':fim2'=>$fim];
    if ($escritorioId) {
        $filtros1 .= ' AND l.escritorio_id=:esc1';
        $filtros2 .= ' AND l.escritorio_id=:esc2';
        $params[':esc1']=$escritorioId; $params[':esc2']=$escritorioId;
    }
    if ($usuarioId) {
        $filtros1 .= ' AND l.responsavel_usuario_id=:usuario1';
        $filtros2 .= ' AND l.responsavel_usuario_id=:usuario2';
        $params[':usuario1']=$usuarioId; $params[':usuario2']=$usuarioId;
    }
    $sql = "SELECT COALESCE(SUM(valor),0) FROM (
        SELECT b.valor_pago valor
          FROM financeiro_historico_baixas b
          JOIN financeiro_lancamentos l ON l.id=b.lancamento_id
         WHERE l.ativo=1 AND l.tipo=:tipo1 AND b.data_pagamento BETWEEN :ini1 AND :fim1 {$filtros1}
        UNION ALL
        SELECT l.valor_original valor
          FROM financeiro_lancamentos l
         WHERE l.ativo=1 AND l.tipo=:tipo2 AND l.status='PAGO' AND l.data BETWEEN :ini2 AND :fim2 {$filtros2}
           AND NOT EXISTS(SELECT 1 FROM financeiro_historico_baixas bx WHERE bx.lancamento_id=l.id)
    ) movimentos";
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    return (float)$stmt->fetchColumn();
}

function financeiroRelatorioDados(PDO $pdo, ?string $mesSolicitado, ?string $escritorioSolicitado): array
{
    $competencia = financeiroCompetencia((string)$mesSolicitado);
    $mes = substr($competencia, 0, 7);
    $inicio = $competencia;
    $fim = date('Y-m-t', strtotime($inicio));
    $escritorios = financeiroEscritoriosPermitidos($pdo);
    $podeSelecionar = financeiroEhAdmin() || count($escritorios) > 1;
    $filtroEscritorio = financeiroResolverEscritorio($pdo, $escritorioSolicitado);
    $escritorioId = $filtroEscritorio === 'todos' ? null : $filtroEscritorio;
    $scope = $escritorioId ? ' AND l.escritorio_id=:escritorio' : '';
    $scopeParams = $escritorioId ? [':escritorio'=>$escritorioId] : [];
    $escritorioSelecionado = null;
    foreach ($escritorios as $escritorio) if ($escritorio['id'] === $escritorioId) $escritorioSelecionado=$escritorio;
    $escopoNome = $filtroEscritorio === 'todos' ? 'Todos os escritórios' : (($escritorioSelecionado['nome'] ?? 'Escritório') . ' - ' . ($escritorioSelecionado['cidade'] ?? '') . '/' . ($escritorioSelecionado['uf'] ?? ''));

    $receitaRealizada = financeiroRelatorioRealizado($pdo, 'RECEITA', $inicio, $fim, $escritorioId);
    $despesaRealizada = financeiroRelatorioRealizado($pdo, 'DESPESA', $inicio, $fim, $escritorioId);

    $stmt=$pdo->prepare("SELECT tipo,COALESCE(SUM(valor_original),0) total FROM financeiro_lancamentos l WHERE l.ativo=1 AND l.status<>'CANCELADO' AND l.data_vencimento BETWEEN :inicio AND :fim {$scope} GROUP BY tipo");
    $stmt->execute(array_merge([':inicio'=>$inicio,':fim'=>$fim],$scopeParams));
    $previsto=$stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $stmt=$pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN l.tipo='RECEITA' AND l.status IN('PENDENTE','PARCIAL') AND l.data_vencimento<CURDATE() THEN l.saldo_devedor ELSE 0 END),0) inadimplente,
        COALESCE(SUM(CASE WHEN l.tipo='RECEITA' AND l.status IN('PENDENTE','PARCIAL') AND l.data_vencimento BETWEEN :inicio AND :fim THEN l.saldo_devedor ELSE 0 END),0) a_receber_periodo
        FROM financeiro_lancamentos l WHERE l.ativo=1 {$scope}");
    $stmt->execute(array_merge([':inicio'=>$inicio,':fim'=>$fim],$scopeParams));
    $pendencias=$stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt=$pdo->prepare("SELECT l.id,l.descricao,l.saldo_devedor,l.data_vencimento,l.status,
           COALESCE(c.nome,'Cliente não informado') cliente,e.nome escritorio,u.nome vendedor,
           GREATEST(DATEDIFF(CURDATE(),l.data_vencimento),0) dias_atraso
      FROM financeiro_lancamentos l
      LEFT JOIN clientes c ON c.id=l.cliente_id
      JOIN escritorios e ON e.id=l.escritorio_id
      LEFT JOIN usuarios u ON u.id=l.responsavel_usuario_id
     WHERE l.ativo=1 AND l.tipo='RECEITA' AND l.status IN('PENDENTE','PARCIAL') {$scope}
     ORDER BY (l.data_vencimento<CURDATE()) DESC,dias_atraso DESC,l.saldo_devedor DESC LIMIT 20");
    $stmt->execute($scopeParams); $cobrancas=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $evolucao=['labels'=>[],'receitas'=>[],'despesas'=>[]];
    for($i=5;$i>=0;$i--){
        $mesEvolucao=date('Y-m',strtotime("-{$i} months",strtotime($inicio)));
        $inicioEvolucao=$mesEvolucao.'-01'; $fimEvolucao=date('Y-m-t',strtotime($inicioEvolucao));
        $evolucao['labels'][]=date('m/Y',strtotime($inicioEvolucao));
        $evolucao['receitas'][]=financeiroRelatorioRealizado($pdo,'RECEITA',$inicioEvolucao,$fimEvolucao,$escritorioId);
        $evolucao['despesas'][]=financeiroRelatorioRealizado($pdo,'DESPESA',$inicioEvolucao,$fimEvolucao,$escritorioId);
    }

    $escritoriosPainel=$filtroEscritorio==='todos'?$escritorios:array_values(array_filter($escritorios,fn($e)=>$e['id']===$filtroEscritorio));
    $comparativo=[];
    foreach($escritoriosPainel as $e){
        $recebido=financeiroRelatorioRealizado($pdo,'RECEITA',$inicio,$fim,$e['id']);
        $despesas=financeiroRelatorioRealizado($pdo,'DESPESA',$inicio,$fim,$e['id']);
        $stmt=$pdo->prepare('SELECT valor FROM financeiro_metas_mensais WHERE escritorio_id=:e AND usuario_id IS NULL AND competencia=:c ORDER BY atualizado_em DESC LIMIT 1');$stmt->execute([':e'=>$e['id'],':c'=>$inicio]);$meta=(float)($stmt->fetchColumn()?:0);
        $stmt=$pdo->prepare("SELECT COALESCE(SUM(saldo_devedor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status IN('PENDENTE','PARCIAL') AND escritorio_id=:e");$stmt->execute([':e'=>$e['id']]);$aberto=(float)$stmt->fetchColumn();
        $comparativo[]=$e+['recebido'=>$recebido,'despesas'=>$despesas,'saldo'=>$recebido-$despesas,'meta'=>$meta,'atingimento'=>$meta>0?($recebido/$meta*100):0,'aberto'=>$aberto];
    }
    usort($comparativo,fn($a,$b)=>$b['recebido']<=>$a['recebido']);

    $sqlVendedores="SELECT u.id,u.nome,COALESCE((SELECT GROUP_CONCAT(DISTINCT e.nome ORDER BY e.nome SEPARATOR ', ') FROM usuario_escritorios ue JOIN escritorios e ON e.id=ue.escritorio_id WHERE ue.usuario_id=u.id),'Sem vínculo') escritorio_nome FROM usuarios u WHERE u.cargo='VENDEDOR'";
    $paramsVendedores=[];
    if($escritorioId){
        $sqlVendedores.=" AND (EXISTS(SELECT 1 FROM usuario_escritorios ue WHERE ue.usuario_id=u.id AND ue.escritorio_id=:vinculo) OR EXISTS(SELECT 1 FROM financeiro_lancamentos lh WHERE lh.responsavel_usuario_id=u.id AND lh.escritorio_id=:historico))";
        $paramsVendedores[':vinculo']=$escritorioId; $paramsVendedores[':historico']=$escritorioId;
    }
    $sqlVendedores.=" AND (u.ativo=1 OR EXISTS(SELECT 1 FROM financeiro_lancamentos l WHERE l.responsavel_usuario_id=u.id AND l.data BETWEEN :inicio AND :fim".($escritorioId?' AND l.escritorio_id=:movimento':'').")) ORDER BY u.nome";
    if($escritorioId)$paramsVendedores[':movimento']=$escritorioId;
    $paramsVendedores[':inicio']=$inicio; $paramsVendedores[':fim']=$fim;
    $stmt=$pdo->prepare($sqlVendedores);$stmt->execute($paramsVendedores);$vendedores=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $inicioAnterior=date('Y-m-01',strtotime($inicio.' -1 month'));$fimAnterior=date('Y-m-t',strtotime($inicioAnterior));$ranking=[];
    foreach($vendedores as $v){
        $recebido=financeiroRelatorioRealizado($pdo,'RECEITA',$inicio,$fim,$escritorioId,$v['id']);
        $anterior=financeiroRelatorioRealizado($pdo,'RECEITA',$inicioAnterior,$fimAnterior,$escritorioId,$v['id']);
        $scopeProp=$escritorioId?' AND escritorio_id=:e':'';$paramsProp=[':u'=>$v['id'],':ini'=>$inicio.' 00:00:00',':fim'=>$fim.' 23:59:59'];if($escritorioId)$paramsProp[':e']=$escritorioId;
        $stmt=$pdo->prepare("SELECT COUNT(*) qtd,COALESCE(SUM(valor_total),0) total FROM propostas WHERE criado_por=:u {$scopeProp} AND assinado=1 AND COALESCE(assinatura_em,created_at) BETWEEN :ini AND :fim");$stmt->execute($paramsProp);$vendas=$stmt->fetch(PDO::FETCH_ASSOC);
        $scopePend=$escritorioId?' AND escritorio_id=:e':'';$paramsPend=[':u'=>$v['id']];if($escritorioId)$paramsPend[':e']=$escritorioId;
        $stmt=$pdo->prepare("SELECT COALESCE(SUM(saldo_devedor),0) aberto,COALESCE(SUM(CASE WHEN data_vencimento<CURDATE() THEN saldo_devedor ELSE 0 END),0) vencido FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status IN('PENDENTE','PARCIAL') AND responsavel_usuario_id=:u {$scopePend}");$stmt->execute($paramsPend);$pend=$stmt->fetch(PDO::FETCH_ASSOC);
        $qtd=(int)$vendas['qtd'];$ranking[]=$v+['recebido'=>$recebido,'vendas_qtd'=>$qtd,'vendas_valor'=>(float)$vendas['total'],'ticket'=>$qtd?(float)$vendas['total']/$qtd:0,'aberto'=>(float)$pend['aberto'],'vencido'=>(float)$pend['vencido'],'variacao'=>$anterior>0?(($recebido-$anterior)/$anterior*100):($recebido>0?100:0)];
    }
    usort($ranking,fn($a,$b)=>$b['recebido']<=>$a['recebido']);

    return [
        'mes'=>$mes,'inicio'=>$inicio,'fim'=>$fim,'escritorios'=>$escritorios,'pode_selecionar'=>$podeSelecionar,
        'filtro_escritorio'=>$filtroEscritorio,'escopo_nome'=>trim($escopoNome,' -/'),'comparativo'=>$comparativo,'ranking'=>$ranking,
        'melhor'=>$ranking[0]??null,'menor'=>$ranking?end($ranking):null,'cobrancas'=>$cobrancas,'evolucao'=>$evolucao,
        'kpis'=>['recebido'=>$receitaRealizada,'a_receber'=>(float)($pendencias['a_receber_periodo']??0),'inadimplente'=>(float)($pendencias['inadimplente']??0)],
        'fluxo'=>['receita_prevista'=>(float)($previsto['RECEITA']??0),'despesa_prevista'=>(float)($previsto['DESPESA']??0),'receita_realizada'=>$receitaRealizada,'despesa_realizada'=>$despesaRealizada],
    ];
}
