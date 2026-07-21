<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_relatorio.php';

verificar_sessao();
exigirAcesso('financeiro');

$formato=strtolower(trim((string)($_GET['formato']??'')));
if(!in_array($formato,['pdf','xlsx'],true)){http_response_code(400);die('Formato de exportação inválido.');}

try{$r=financeiroRelatorioDados($pdo,$_GET['mes']??date('Y-m'),$_GET['escritorio_id']??null);}
catch(Throwable $e){http_response_code(400);die(h($e->getMessage()));}
$escopoArquivo=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$r['escopo_nome'])?:$r['escopo_nome'];
$nomeBase='relatorio-financeiro-'.trim(preg_replace('/[^a-z0-9-]+/i','-',strtolower($escopoArquivo)),'-').'-'.$r['mes'];

if($formato==='pdf'){
    require_once __DIR__ . '/../../vendor/autoload.php';
    $pdf=new TCPDF('L','mm','A4',true,'UTF-8',false);
    $pdf->SetCreator('Sistema Amazon');$pdf->SetAuthor('Sistema Amazon');$pdf->SetTitle('Relatório Financeiro');
    $pdf->SetMargins(10,12,10);$pdf->SetAutoPageBreak(true,12);$pdf->setPrintHeader(false);$pdf->setPrintFooter(true);$pdf->AddPage();
    $moeda=static fn($v)=>'R$ '.number_format((float)$v,2,',','.');
    $estilos='<style>h1{color:#08734d;font-size:20px}h2{color:#173b32;font-size:13px;margin-top:12px}.meta{color:#5f706a;font-size:9px}.kpis td{border:1px solid #d9e5e0;background-color:#f4faf7;font-size:10px;padding:7px}.kpis b{color:#08734d;font-size:13px}table.data{border-collapse:collapse;width:100%;font-size:8px}table.data th{background-color:#08734d;color:#fff;font-weight:bold;padding:5px;border:1px solid #08734d}table.data td{padding:4px;border-bottom:1px solid #d9e5e0}.right{text-align:right}.danger{color:#b2352b}</style>';
    $html=$estilos;
    $html.='<h1>Relatório Financeiro</h1><p class="meta"><b>Escopo:</b> '.h($r['escopo_nome']).' &nbsp; | &nbsp; <b>Competência:</b> '.date('m/Y',strtotime($r['inicio'])).' &nbsp; | &nbsp; <b>Gerado em:</b> '.date('d/m/Y H:i').'</p>';
    $saldo=$r['fluxo']['receita_realizada']-$r['fluxo']['despesa_realizada'];
    $html.='<table class="kpis"><tr><td>Recebido<br><b>'.$moeda($r['kpis']['recebido']).'</b></td><td>A receber no período<br><b>'.$moeda($r['kpis']['a_receber']).'</b></td><td>Inadimplência atual<br><b class="danger">'.$moeda($r['kpis']['inadimplente']).'</b></td><td>Saldo realizado<br><b>'.$moeda($saldo).'</b></td></tr></table>';
    $html.='<h2>Fluxo de caixa</h2><table class="data"><thead><tr><th>Indicador</th><th>Previsto</th><th>Realizado</th></tr></thead><tbody>';
    $html.='<tr><td>Receitas</td><td class="right">'.$moeda($r['fluxo']['receita_prevista']).'</td><td class="right">'.$moeda($r['fluxo']['receita_realizada']).'</td></tr>';
    $html.='<tr><td>Despesas</td><td class="right">'.$moeda($r['fluxo']['despesa_prevista']).'</td><td class="right">'.$moeda($r['fluxo']['despesa_realizada']).'</td></tr>';
    $html.='<tr><td><b>Saldo</b></td><td class="right"><b>'.$moeda($r['fluxo']['receita_prevista']-$r['fluxo']['despesa_prevista']).'</b></td><td class="right"><b>'.$moeda($saldo).'</b></td></tr></tbody></table>';
    $html.='<h2>Evolução financeira - 6 meses</h2><table class="data"><thead><tr><th>Mês</th><th>Receitas</th><th>Despesas</th><th>Saldo</th></tr></thead><tbody>';
    foreach($r['evolucao']['labels'] as $i=>$label)$html.='<tr><td>'.h($label).'</td><td class="right">'.$moeda($r['evolucao']['receitas'][$i]).'</td><td class="right">'.$moeda($r['evolucao']['despesas'][$i]).'</td><td class="right">'.$moeda($r['evolucao']['receitas'][$i]-$r['evolucao']['despesas'][$i]).'</td></tr>';
    $html.='</tbody></table>';
    $pdf->writeHTML($html,true,false,true,false,'');
    $pdf->AddPage();
    $html=$estilos.'<h1>Detalhamento Financeiro</h1><p class="meta"><b>Escopo:</b> '.h($r['escopo_nome']).' &nbsp; | &nbsp; <b>Competência:</b> '.date('m/Y',strtotime($r['inicio'])).'</p>';
    $html.='<h2>Resultado por escritório</h2><table class="data"><thead><tr><th>#</th><th>Escritório</th><th>Recebido</th><th>Despesas</th><th>Saldo</th><th>A receber (geral)</th><th>Meta</th><th>Atingimento</th></tr></thead><tbody>';
    foreach($r['comparativo'] as $i=>$e)$html.='<tr><td>'.($i+1).'</td><td>'.h($e['nome'].' - '.$e['cidade'].'/'.$e['uf']).'</td><td class="right">'.$moeda($e['recebido']).'</td><td class="right">'.$moeda($e['despesas']).'</td><td class="right">'.$moeda($e['saldo']).'</td><td class="right">'.$moeda($e['aberto']).'</td><td class="right">'.$moeda($e['meta']).'</td><td class="right">'.number_format($e['atingimento'],1,',','.').'%</td></tr>';
    $html.='</tbody></table><h2>Ranking de vendedores</h2><table class="data"><thead><tr><th>#</th><th>Vendedor</th><th>Escritório</th><th>Recebido</th><th>Vendas</th><th>Ticket médio</th><th>A receber</th><th>Inadimplente</th><th>Variação</th></tr></thead><tbody>';
    foreach($r['ranking'] as $i=>$v)$html.='<tr><td>'.($i+1).'</td><td>'.h($v['nome']).'</td><td>'.h($v['escritorio_nome']).'</td><td class="right">'.$moeda($v['recebido']).'</td><td class="right">'.(int)$v['vendas_qtd'].'</td><td class="right">'.$moeda($v['ticket']).'</td><td class="right">'.$moeda($v['aberto']).'</td><td class="right">'.$moeda($v['vencido']).'</td><td class="right">'.number_format($v['variacao'],1,',','.').'%</td></tr>';
    $html.='</tbody></table><h2>Contas a receber prioritárias</h2><table class="data"><thead><tr><th>Cliente</th><th>Descrição</th><th>Escritório</th><th>Vendedor</th><th>Vencimento</th><th>Status</th><th>Saldo</th></tr></thead><tbody>';
    foreach($r['cobrancas'] as $c)$html.='<tr><td>'.h($c['cliente']).'</td><td>'.h(mb_strimwidth($c['descricao'],0,60,'...')).'</td><td>'.h($c['escritorio']).'</td><td>'.h($c['vendedor']?:'-').'</td><td>'.date('d/m/Y',strtotime($c['data_vencimento'])).'</td><td>'.((int)$c['dias_atraso']>0?(int)$c['dias_atraso'].' dias em atraso':$c['status']).'</td><td class="right">'.$moeda($c['saldo_devedor']).'</td></tr>';
    $html.='</tbody></table>';
    $pdf->writeHTML($html,true,false,true,false,'');
    $conteudo=$pdf->Output($nomeBase.'.pdf','S');
    header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$nomeBase.'.pdf"');header('Content-Length: '.strlen($conteudo));echo $conteudo;exit;
}

require_once __DIR__ . '/../../includes/xlsx_export.php';
$resumo=[
    [['Relatório Financeiro',1]],
    ['Escopo',$r['escopo_nome']],['Competência',date('m/Y',strtotime($r['inicio']))],['Gerado em',date('d/m/Y H:i')],[],
    [['Indicador',2],['Valor',2]],
    ['Recebido no período',[$r['kpis']['recebido'],4]],
    ['Despesas realizadas',[$r['fluxo']['despesa_realizada'],4]],
    ['Saldo realizado',[$r['fluxo']['receita_realizada']-$r['fluxo']['despesa_realizada'],4,'B7-B8']],
    ['A receber no período',[$r['kpis']['a_receber'],4]],
    ['Inadimplência atual',[$r['kpis']['inadimplente'],4]],
    ['Receitas previstas',[$r['fluxo']['receita_prevista'],4]],
    ['Despesas previstas',[$r['fluxo']['despesa_prevista'],4]],[],
    ['Maior desempenho',$r['melhor']['nome']??'Sem dados'],
    ['Menor desempenho',$r['menor']['nome']??'Sem dados'],
];
$linhasEscritorios=[[['Resultado por escritório',1]],[['Posição',2],['Escritório',2],['Cidade/UF',2],['Recebido',2],['Despesas',2],['Saldo',2],['A receber (geral)',2],['Meta',2],['Atingimento',2]]];
foreach($r['comparativo'] as $i=>$e)$linhasEscritorios[]=[[$i+1,6],[$e['nome'],6],[$e['cidade'].'/'.$e['uf'],6],[$e['recebido'],4],[$e['despesas'],4],[$e['saldo'],4,'D'.($i+3).'-E'.($i+3)],[$e['aberto'],4],[$e['meta'],4],[$e['atingimento']/100,5]];
$linhasVendedores=[[['Ranking de vendedores',1]],[['Posição',2],['Vendedor',2],['Escritório',2],['Recebido',2],['Vendas',2],['Valor vendido',2],['Ticket médio',2],['A receber (geral)',2],['Inadimplente',2],['Variação',2]]];
foreach($r['ranking'] as $i=>$v)$linhasVendedores[]=[[$i+1,6],[$v['nome'],6],[$v['escritorio_nome'],6],[$v['recebido'],4],[$v['vendas_qtd'],6],[$v['vendas_valor'],4],[$v['ticket'],4],[$v['aberto'],4],[$v['vencido'],4],[$v['variacao']/100,5]];
$linhasCobrancas=[[['Contas a receber prioritárias',1]],[['Cliente',2],['Descrição',2],['Escritório',2],['Vendedor',2],['Vencimento',2],['Status',2],['Dias em atraso',2],['Saldo',2]]];
foreach($r['cobrancas'] as $c)$linhasCobrancas[]=[[$c['cliente'],6],[$c['descricao'],6],[$c['escritorio'],6],[$c['vendedor']?:'-',6],[date('d/m/Y',strtotime($c['data_vencimento'])),6],[$c['status'],6],[(int)$c['dias_atraso'],6],[$c['saldo_devedor'],4]];
$linhasEvolucao=[[['Evolução - 6 meses',1]],[['Mês',2],['Receitas',2],['Despesas',2],['Saldo',2]]];
foreach($r['evolucao']['labels'] as $i=>$label){$row=$i+3;$linhasEvolucao[]=[[$label,6],[$r['evolucao']['receitas'][$i],4],[$r['evolucao']['despesas'][$i],4],[$r['evolucao']['receitas'][$i]-$r['evolucao']['despesas'][$i],4,'B'.$row.'-C'.$row]];}
$temp=tempnam(sys_get_temp_dir(),'relatorio_financeiro_');if($temp===false)throw new RuntimeException('Não foi possível preparar o Excel.');$arquivo=$temp.'.xlsx';@unlink($temp);
xlsxGerar($arquivo,[
    ['nome'=>'Resumo','linhas'=>$resumo,'larguras'=>[28,24],'congelar'=>0],
    ['nome'=>'Escritórios','linhas'=>$linhasEscritorios,'larguras'=>[10,24,18,17,17,17,17,17,15]],
    ['nome'=>'Vendedores','linhas'=>$linhasVendedores,'larguras'=>[10,24,25,17,12,17,17,17,17,14]],
    ['nome'=>'Contas a Receber','linhas'=>$linhasCobrancas,'larguras'=>[24,42,20,22,14,14,15,17]],
    ['nome'=>'Evolução','linhas'=>$linhasEvolucao,'larguras'=>[15,20,20,20]],
]);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$nomeBase.'.xlsx"');header('Content-Length: '.filesize($arquivo));readfile($arquivo);@unlink($arquivo);exit;
