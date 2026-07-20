<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: relatorios.php - Dashboard Financeiro (Fluxo, Cobrancas, Inadimplencia)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$mes_atual = date('Y-m');
$filtro_mes = $_GET['mes'] ?? $mes_atual;

// Definir data inicio e fim do mes selecionado
$data_ini = $filtro_mes . '-01';
$data_fim = date('Y-m-t', strtotime($data_ini));
$escritorios = financeiroEscritoriosPermitidos($pdo);
$podeSelecionarEscritorio = financeiroEhAdmin() || count($escritorios) > 1;
try { $filtro_escritorio = financeiroResolverEscritorio($pdo, $_GET['escritorio_id'] ?? null); }
catch(Throwable $e){setMensagem('error',$e->getMessage());redirecionar(APP_URL.'dashboard');}
$scopeSql = $filtro_escritorio === 'todos' ? '' : ' AND escritorio_id = :escritorio';
$scopeParams = $filtro_escritorio === 'todos' ? [] : [':escritorio'=>$filtro_escritorio];

// 1. INADIMPLENCIA (Atrasados vs Pagos no periodo, ou geral vencidos)
$sqlInadimplencia = "
    SELECT 
        SUM(CASE WHEN status = 'PENDENTE' AND data_vencimento < CURRENT_DATE THEN valor ELSE 0 END) as total_atrasado,
        SUM(CASE WHEN status = 'PENDENTE' AND data_vencimento >= CURRENT_DATE AND data_vencimento <= :fim THEN valor ELSE 0 END) as total_a_vencer,
        SUM(CASE WHEN status = 'PAGO' AND data >= :ini AND data <= :fim2 THEN valor ELSE 0 END) as total_recebido
    FROM financeiro_lancamentos 
    WHERE tipo = 'RECEITA' AND ativo = 1 {$scopeSql}
";
$stmtInad = $pdo->prepare($sqlInadimplencia);
$stmtInad->execute(array_merge([':ini' => $data_ini, ':fim' => $data_fim, ':fim2' => $data_fim],$scopeParams));
$inad = $stmtInad->fetch();

// 2. FLUXO DE CAIXA (Soma geral ate o fim do mes, considerando o que ja foi PAGO ou que ainda vai entrar/sair no mes)
$sqlFluxoPrevisto = "
    SELECT tipo, SUM(valor) as total
    FROM financeiro_lancamentos
    WHERE ativo = 1 AND status != 'CANCELADO' AND data_vencimento >= :ini AND data_vencimento <= :fim {$scopeSql}
    GROUP BY tipo
";
$stmtFluxoP = $pdo->prepare($sqlFluxoPrevisto);
$stmtFluxoP->execute(array_merge([':ini' => $data_ini, ':fim' => $data_fim],$scopeParams));
$fluxoPrevistoArr = $stmtFluxoP->fetchAll(PDO::FETCH_KEY_PAIR);
$receitaPrevista = floatval($fluxoPrevistoArr['RECEITA'] ?? 0);
$despesaPrevista = floatval($fluxoPrevistoArr['DESPESA'] ?? 0);

$sqlFluxoRealizado = "
    SELECT tipo, SUM(valor) as total
    FROM financeiro_lancamentos
    WHERE ativo = 1 AND status = 'PAGO' AND data >= :ini AND data <= :fim {$scopeSql}
    GROUP BY tipo
";
$stmtFluxoR = $pdo->prepare($sqlFluxoRealizado);
$stmtFluxoR->execute(array_merge([':ini' => $data_ini, ':fim' => $data_fim],$scopeParams));
$fluxoRealizadoArr = $stmtFluxoR->fetchAll(PDO::FETCH_KEY_PAIR);
$receitaRealizada = floatval($fluxoRealizadoArr['RECEITA'] ?? 0);
$despesaRealizada = floatval($fluxoRealizadoArr['DESPESA'] ?? 0);

// 3. MAIORES CONTAS A RECEBER (visao operacional para cobranca)
$scopeCobranca = $filtro_escritorio === 'todos' ? '' : ' AND l.escritorio_id=:escritorio';
$sqlCobrancas = "
    SELECT l.id,l.descricao,l.saldo_devedor,l.data_vencimento,l.status,
           COALESCE(c.nome,'Cliente não informado') cliente,
           e.nome escritorio,u.nome vendedor,
           GREATEST(DATEDIFF(CURDATE(),l.data_vencimento),0) dias_atraso
    FROM financeiro_lancamentos l
    LEFT JOIN clientes c ON c.id=l.cliente_id
    JOIN escritorios e ON e.id=l.escritorio_id
    LEFT JOIN usuarios u ON u.id=l.responsavel_usuario_id
    WHERE l.ativo=1 AND l.tipo='RECEITA' AND l.status IN('PENDENTE','PARCIAL') {$scopeCobranca}
    ORDER BY (l.data_vencimento<CURDATE()) DESC, dias_atraso DESC, l.saldo_devedor DESC
    LIMIT 10
";
$stmtCobrancas=$pdo->prepare($sqlCobrancas);$stmtCobrancas->execute($scopeParams);$maioresCobrancas=$stmtCobrancas->fetchAll(PDO::FETCH_ASSOC);

// 4. EVOLUCAO MENSAL (Ultimos 6 meses)
$evolucao = [];
for ($i = 5; $i >= 0; $i--) {
    // Corrigido bug no strtotime que quebrava o grafico
    $m = date('Y-m', strtotime("-$i months", strtotime($data_ini)));
    $m_ini = $m . '-01';
    $m_fim = date('Y-m-t', strtotime($m_ini));

    $sqlMes = "SELECT tipo, SUM(valor) as total FROM financeiro_lancamentos WHERE ativo = 1 AND status = 'PAGO' AND data >= :ini AND data <= :fim {$scopeSql} GROUP BY tipo";
    $stmtMes = $pdo->prepare($sqlMes);
    $stmtMes->execute(array_merge([':ini' => $m_ini, ':fim' => $m_fim],$scopeParams));
    $resMes = $stmtMes->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $evolucao['labels'][] = date('m/Y', strtotime($m_ini));
    $evolucao['receitas'][] = floatval($resMes['RECEITA'] ?? 0);
    $evolucao['despesas'][] = floatval($resMes['DESPESA'] ?? 0);
}

function financeiroRecebidoPeriodo(PDO $pdo, string $tipo, string $inicio, string $fim, string $campo, string $id, ?string $escritorioId = null): float {
    if (!in_array($campo, ['escritorio_id','responsavel_usuario_id'], true)) return 0.0;
    $scope = $escritorioId ? ' AND l.escritorio_id=:esc1' : '';
    $scope2 = $escritorioId ? ' AND l.escritorio_id=:esc2' : '';
    $sql="SELECT COALESCE(SUM(valor),0) FROM (
        SELECT b.valor_pago valor FROM financeiro_historico_baixas b JOIN financeiro_lancamentos l ON l.id=b.lancamento_id
        WHERE l.ativo=1 AND l.tipo=:tipo AND l.{$campo}=:id AND b.data_pagamento BETWEEN :ini1 AND :fim1 {$scope}
        UNION ALL
        SELECT l.valor_original valor FROM financeiro_lancamentos l WHERE l.ativo=1 AND l.tipo=:tipo2 AND l.{$campo}=:id2
          AND l.status='PAGO' AND l.data BETWEEN :ini2 AND :fim2 {$scope2}
          AND NOT EXISTS(SELECT 1 FROM financeiro_historico_baixas bx WHERE bx.lancamento_id=l.id)
    ) recebido";
    $params=[':tipo'=>$tipo,':id'=>$id,':ini1'=>$inicio,':fim1'=>$fim,':tipo2'=>$tipo,':id2'=>$id,':ini2'=>$inicio,':fim2'=>$fim];
    if($escritorioId){$params[':esc1']=$escritorioId;$params[':esc2']=$escritorioId;}
    $stmt=$pdo->prepare($sql);$stmt->execute($params);
    return (float)$stmt->fetchColumn();
}

$escritoriosPainel=$filtro_escritorio==='todos'?$escritorios:array_values(array_filter($escritorios,fn($e)=>$e['id']===$filtro_escritorio));
$comparativoEscritorios=[];
foreach($escritoriosPainel as $e){
    $rec=financeiroRecebidoPeriodo($pdo,'RECEITA',$data_ini,$data_fim,'escritorio_id',$e['id']);
    $desp=financeiroRecebidoPeriodo($pdo,'DESPESA',$data_ini,$data_fim,'escritorio_id',$e['id']);
    $stmt=$pdo->prepare('SELECT valor FROM financeiro_metas_mensais WHERE escritorio_id=:e AND usuario_id IS NULL AND competencia=:c ORDER BY atualizado_em DESC LIMIT 1');$stmt->execute([':e'=>$e['id'],':c'=>$data_ini]);$meta=(float)($stmt->fetchColumn()?:0);
    $stmt=$pdo->prepare("SELECT COALESCE(SUM(saldo_devedor),0) FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status IN('PENDENTE','PARCIAL') AND escritorio_id=:e");$stmt->execute([':e'=>$e['id']]);$aberto=(float)$stmt->fetchColumn();
    $comparativoEscritorios[]=$e+['recebido'=>$rec,'despesas'=>$desp,'saldo'=>$rec-$desp,'meta'=>$meta,'atingimento'=>$meta>0?($rec/$meta*100):0,'aberto'=>$aberto];
}
usort($comparativoEscritorios,fn($a,$b)=>$b['recebido']<=>$a['recebido']);

$sqlVendedores="SELECT u.id,u.nome,COALESCE((SELECT GROUP_CONCAT(DISTINCT e.nome ORDER BY e.nome SEPARATOR ', ') FROM usuario_escritorios ue JOIN escritorios e ON e.id=ue.escritorio_id WHERE ue.usuario_id=u.id),'Sem vínculo') escritorio_nome FROM usuarios u WHERE u.cargo='VENDEDOR'";
$paramsVendedores=[];
if($filtro_escritorio!=='todos'){
    $sqlVendedores.=" AND (EXISTS(SELECT 1 FROM usuario_escritorios ue WHERE ue.usuario_id=u.id AND ue.escritorio_id=:vinculo_e) OR EXISTS(SELECT 1 FROM financeiro_lancamentos lh WHERE lh.responsavel_usuario_id=u.id AND lh.escritorio_id=:historico_e))";
    $paramsVendedores[':vinculo_e']=$filtro_escritorio;$paramsVendedores[':historico_e']=$filtro_escritorio;
}
$sqlVendedores.=" AND (u.ativo=1 OR EXISTS(SELECT 1 FROM financeiro_lancamentos l WHERE l.responsavel_usuario_id=u.id AND l.data BETWEEN :ini AND :fim" . ($filtro_escritorio!=='todos'?' AND l.escritorio_id=:mov_e':'') . ")) ORDER BY u.nome";
if($filtro_escritorio!=='todos')$paramsVendedores[':mov_e']=$filtro_escritorio;
$paramsVendedores[':ini']=$data_ini;$paramsVendedores[':fim']=$data_fim;$stmt=$pdo->prepare($sqlVendedores);$stmt->execute($paramsVendedores);$vendedores=$stmt->fetchAll(PDO::FETCH_ASSOC);
$inicioAnterior=date('Y-m-01',strtotime($data_ini.' -1 month'));$fimAnterior=date('Y-m-t',strtotime($inicioAnterior));$ranking=[];
foreach($vendedores as $v){
    $escopoRanking=$filtro_escritorio==='todos'?null:$filtro_escritorio;
    $recebido=financeiroRecebidoPeriodo($pdo,'RECEITA',$data_ini,$data_fim,'responsavel_usuario_id',$v['id'],$escopoRanking);
    $anterior=financeiroRecebidoPeriodo($pdo,'RECEITA',$inicioAnterior,$fimAnterior,'responsavel_usuario_id',$v['id'],$escopoRanking);
    $scopeProp=$escopoRanking?' AND escritorio_id=:e':'';$paramsProp=[':u'=>$v['id'],':ini'=>$data_ini.' 00:00:00',':fim'=>$data_fim.' 23:59:59'];if($escopoRanking)$paramsProp[':e']=$escopoRanking;
    $stmt=$pdo->prepare("SELECT COUNT(*) qtd,COALESCE(SUM(valor_total),0) total FROM propostas WHERE criado_por=:u {$scopeProp} AND assinado=1 AND COALESCE(assinatura_em,created_at) BETWEEN :ini AND :fim");$stmt->execute($paramsProp);$vendas=$stmt->fetch(PDO::FETCH_ASSOC);
    $scopePend=$escopoRanking?' AND escritorio_id=:e':'';$paramsPend=[':u'=>$v['id']];if($escopoRanking)$paramsPend[':e']=$escopoRanking;
    $stmt=$pdo->prepare("SELECT COALESCE(SUM(saldo_devedor),0) aberto,COALESCE(SUM(CASE WHEN data_vencimento<CURDATE() THEN saldo_devedor ELSE 0 END),0) vencido FROM financeiro_lancamentos WHERE ativo=1 AND tipo='RECEITA' AND status IN('PENDENTE','PARCIAL') AND responsavel_usuario_id=:u {$scopePend}");$stmt->execute($paramsPend);$pend=$stmt->fetch(PDO::FETCH_ASSOC);
    $qtd=(int)$vendas['qtd'];$ranking[]=$v+['recebido'=>$recebido,'vendas_qtd'=>$qtd,'vendas_valor'=>(float)$vendas['total'],'ticket'=>$qtd?(float)$vendas['total']/$qtd:0,'aberto'=>(float)$pend['aberto'],'vencido'=>(float)$pend['vencido'],'variacao'=>$anterior>0?(($recebido-$anterior)/$anterior*100):($recebido>0?100:0)];
}
usort($ranking,fn($a,$b)=>$b['recebido']<=>$a['recebido']);

$titulo_page = 'Relatorios Financeiros - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Import Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content">
    <div class="page-header d-flex" style="justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Dashboard Financeiro</h1>
            <p class="page-subtitle">Fluxo de caixa, cobranças e inadimplência</p>
        </div>
        <div>
            <form method="GET" class="d-flex gap-2" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
                <select name="escritorio_id" class="form-control bg-dark text-light border-secondary" style="flex:1 1 220px;min-width:220px" <?= $podeSelecionarEscritorio?'':'disabled' ?>><?php if(financeiroEhAdmin()): ?><option value="todos" <?= $filtro_escritorio==='todos'?'selected':'' ?>>Todos os escritórios</option><?php endif ?><?php foreach($escritorios as $e): ?><option value="<?= h($e['id']) ?>" <?= $filtro_escritorio===$e['id']?'selected':'' ?>><?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></option><?php endforeach ?></select><?php if(!$podeSelecionarEscritorio): ?><input type="hidden" name="escritorio_id" value="<?= h($filtro_escritorio) ?>"><?php endif ?>
                <input type="month" name="mes" value="<?= h($filtro_mes) ?>" class="form-control bg-dark text-light border-secondary" style="flex:1 1 180px;min-width:180px">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
            </form>
        </div>
    </div>

    <section class="card mb-3"><div class="card-header"><h3><i class="fa-solid fa-building"></i> Resultado por escritório</h3></div><div class="card-body" style="overflow:auto"><table class="data-table" style="min-width:900px"><thead><tr><th>#</th><th>Escritório</th><th class="text-right">Recebido</th><th class="text-right">Despesas</th><th class="text-right">Saldo</th><th class="text-right">A receber</th><th class="text-right">Meta</th><th>Atingimento</th></tr></thead><tbody><?php foreach($comparativoEscritorios as $i=>$e): ?><tr><td><?= $i+1 ?></td><td><strong><?= h($e['nome']) ?></strong><br><small><?= h($e['cidade'].'/'.$e['uf']) ?></small></td><td class="text-right text-success"><?= formatarMoeda($e['recebido']) ?></td><td class="text-right text-danger"><?= formatarMoeda($e['despesas']) ?></td><td class="text-right"><strong><?= formatarMoeda($e['saldo']) ?></strong></td><td class="text-right"><?= formatarMoeda($e['aberto']) ?></td><td class="text-right"><?= formatarMoeda($e['meta']) ?></td><td><span class="badge <?= $e['meta']>0&&$e['atingimento']>=100?'badge-success':'badge-warning' ?>"><?= number_format($e['atingimento'],1,',','.') ?>%</span></td></tr><?php endforeach ?><?php if(!$comparativoEscritorios): ?><tr><td colspan="8" class="text-muted">Nenhum escritório disponível.</td></tr><?php endif ?></tbody></table></div></section>

    <section class="card mb-3"><div class="card-header"><h3><i class="fa-solid fa-ranking-star"></i> Desempenho dos vendedores</h3></div><div class="card-body" style="overflow:auto"><table class="data-table" style="min-width:1080px"><thead><tr><?php foreach(['#','Vendedor','Recebido','Vendas assinadas','Ticket médio','A receber','Inadimplente','Mês anterior'] as $cab): ?><th style="white-space:nowrap"><?= h($cab) ?></th><?php endforeach ?></tr></thead><tbody><?php foreach($ranking as $i=>$v): ?><tr><td><strong><?= $i+1 ?>º</strong><?php if($i===0&&$v['recebido']>0): ?><br><span class="badge badge-success">Destaque</span><?php elseif($v['recebido']<=0): ?><br><span class="badge badge-warning">Sem recebimento</span><?php endif ?></td><td><strong><?= h($v['nome']) ?></strong><br><small><?= h($v['escritorio_nome']) ?></small></td><td class="text-right text-success"><strong><?= formatarMoeda($v['recebido']) ?></strong></td><td><?= $v['vendas_qtd'] ?> venda(s)<br><small><?= formatarMoeda($v['vendas_valor']) ?></small></td><td class="text-right"><?= formatarMoeda($v['ticket']) ?></td><td class="text-right"><?= formatarMoeda($v['aberto']) ?></td><td class="text-right text-danger"><?= formatarMoeda($v['vencido']) ?></td><td><span class="badge <?= $v['variacao']>=0?'badge-success':'badge-danger' ?>"><?= $v['variacao']>=0?'+':'' ?><?= number_format($v['variacao'],1,',','.') ?>%</span></td></tr><?php endforeach ?><?php if(!$ranking): ?><tr><td colspan="8" class="text-muted">Nenhum vendedor ou movimento no período.</td></tr><?php endif ?></tbody></table></div></section>

    <!-- CARDS DE INADIMPLENCIA / RECEITAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="stat-info">
                <h4>Recebido no Mes</h4>
                <div class="stat-valor">R$ <?= number_format($inad['total_recebido'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info">
                <h4>A Receber (Mes)</h4>
                <div class="stat-valor">R$ <?= number_format($inad['total_a_vencer'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card" style="border: 1px solid var(--cor-erro);">
            <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="stat-info">
                <h4 style="color: var(--cor-erro);">Inadimplencia (Atrasado Geral)</h4>
                <div class="stat-valor" style="color: var(--cor-erro);">R$ <?= number_format($inad['total_atrasado'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- FLUXO DE CAIXA -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fa-solid fa-money-bill-transfer"></i> Fluxo de Caixa (<?= date('m/Y', strtotime($data_ini)) ?>)</h3>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th class="text-right">Previsto</th>
                            <th class="text-right">Realizado (Pago)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong class="text-success">(+) Receitas</strong></td>
                            <td class="text-right text-success">R$ <?= number_format($receitaPrevista, 2, ',', '.') ?></td>
                            <td class="text-right text-success">R$ <?= number_format($receitaRealizada, 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td><strong class="text-danger">(-) Despesas</strong></td>
                            <td class="text-right text-danger">R$ <?= number_format($despesaPrevista, 2, ',', '.') ?></td>
                            <td class="text-right text-danger">R$ <?= number_format($despesaRealizada, 2, ',', '.') ?></td>
                        </tr>
                        <tr style="background: rgba(255,255,255,0.05);">
                            <td><strong>(=) Saldo</strong></td>
                            <td class="text-right"><strong>R$ <?= number_format($receitaPrevista - $despesaPrevista, 2, ',', '.') ?></strong></td>
                            <td class="text-right"><strong>R$ <?= number_format($receitaRealizada - $despesaRealizada, 2, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MAIORES CONTAS A RECEBER -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fa-solid fa-file-invoice-dollar"></i> Maiores contas a receber</h3>
            </div>
            <div class="card-body" style="overflow:auto;max-height:380px">
                <table class="data-table" style="min-width:720px"><thead><tr><th>Cliente / lançamento</th><th>Escritório</th><th>Vencimento</th><th>Situação</th><th class="text-right">Saldo</th><th></th></tr></thead><tbody>
                <?php foreach($maioresCobrancas as $cobranca): $atrasada=(int)$cobranca['dias_atraso']>0; ?>
                <tr><td><strong><?= h($cobranca['cliente']) ?></strong><br><small><?= h(mb_strimwidth($cobranca['descricao'],0,48,'...')) ?><?= $cobranca['vendedor']?' · '.h($cobranca['vendedor']):'' ?></small></td><td><?= h($cobranca['escritorio']) ?></td><td><?= formatarData($cobranca['data_vencimento']) ?></td><td><span class="badge <?= $atrasada?'badge-danger':'badge-warning' ?>"><?= $atrasada?(int)$cobranca['dias_atraso'].' dia(s) em atraso':($cobranca['status']==='PARCIAL'?'Parcial':'A vencer') ?></span></td><td class="text-right"><strong><?= formatarMoeda($cobranca['saldo_devedor']) ?></strong></td><td><a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>financeiro/form?id=<?= urlencode($cobranca['id']) ?>" title="Abrir lançamento"><i class="fas fa-arrow-right"></i></a></td></tr>
                <?php endforeach ?>
                <?php if(!$maioresCobrancas): ?><tr><td colspan="6" class="text-muted">Nenhuma conta pendente. Tudo em dia neste filtro.</td></tr><?php endif ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <!-- EVOLUCAO 6 MESES -->
    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-line"></i> Evolucao de Receitas vs Despesas (Realizado nos ultimos 6 meses)</h3>
        </div>
        <div class="card-body" style="position: relative; height: 350px;">
            <canvas id="evolucaoChart"></canvas>
        </div>
    </div>

</div>

<script>
// Dados Evolucao
const evoLabels = <?= json_encode($evolucao['labels']) ?>;
const evoReceitas = <?= json_encode($evolucao['receitas']) ?>;
const evoDespesas = <?= json_encode($evolucao['despesas']) ?>;

new Chart(document.getElementById('evolucaoChart'), {
    type: 'bar',
    data: {
        labels: evoLabels,
        datasets: [
            {
                label: 'Receitas',
                data: evoReceitas,
                backgroundColor: '#39d353',
                borderRadius: 4
            },
            {
                label: 'Despesas',
                data: evoDespesas,
                backgroundColor: '#f85149',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#e6edf3' } }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: '#8b949e' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#8b949e' }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
