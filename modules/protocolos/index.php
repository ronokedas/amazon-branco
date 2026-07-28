<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/protocolos.php';
protocoloExigirAcesso();
protocoloProcessarAlertas($pdo);

$f=['busca'=>trim($_GET['busca']??''),'status'=>trim($_GET['status']??''),'unidade'=>trim($_GET['unidade']??''),'cidade'=>trim($_GET['cidade']??''),'responsavel'=>trim($_GET['responsavel']??''),'inicio'=>trim($_GET['inicio']??''),'fim'=>trim($_GET['fim']??''),'embarcacao_id'=>trim($_GET['embarcacao_id']??'')];
$where=[];$p=[];$uid=(string)($_SESSION['usuario_id']??'');
if(getCargo()!=='ADMIN'){
 $where[]='(d.criado_por=:uid OR EXISTS(SELECT 1 FROM propostas px WHERE px.id=d.proposta_id AND px.criado_por=:uid) OR EXISTS(SELECT 1 FROM analises_planos ax WHERE ax.id=d.analise_id AND ax.analista_id=:uid) OR EXISTS(SELECT 1 FROM vistorias vx JOIN agendamentos gx ON gx.id=vx.agendamento_id WHERE vx.id=d.vistoria_id AND gx.vistoriador_id=:uid))';$p[':uid']=$uid;
}
if($f['busca']!==''){$where[]='(d.numero LIKE :b OR d.assunto LIKE :b OR e.nome LIKE :b OR c.nome LIKE :b)';$p[':b']='%'.$f['busca'].'%';}
$labels=protocoloRotulosStatus();if(isset($labels[$f['status']])){$where[]='d.status=:status';$p[':status']=$f['status'];}
foreach(['unidade'=>'d.unidade_maritima_id','responsavel'=>'d.criado_por','embarcacao_id'=>'d.embarcacao_id'] as $k=>$col)if($f[$k]!==''){$where[]="$col=:$k";$p[":$k"]=$f[$k];}
if($f['cidade']!==''){$where[]='EXISTS(SELECT 1 FROM protocolo_movimentacoes mc WHERE mc.dossie_id=d.id AND mc.cidade LIKE :cidade)';$p[':cidade']='%'.$f['cidade'].'%';}
if($f['inicio']!==''){$where[]='DATE(d.criado_em)>=:inicio';$p[':inicio']=$f['inicio'];}
if($f['fim']!==''){$where[]='DATE(d.criado_em)<=:fim';$p[':fim']=$f['fim'];}
$sql="SELECT d.*,e.nome embarcacao_nome,c.nome cliente_nome,u.nome responsavel_nome,um.nome unidade_nome,
(SELECT COUNT(*) FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status IN('CONFIRMADA','RETIFICADA')) eventos,
(SELECT COUNT(*) FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m2 ON m2.id=i.movimentacao_id WHERE m2.dossie_id=d.id AND i.requer_devolucao=1 AND i.devolvido_em IS NULL) originais_pendentes
FROM protocolo_dossies d JOIN embarcacoes e ON e.id=d.embarcacao_id LEFT JOIN clientes c ON c.id=d.cliente_id LEFT JOIN usuarios u ON u.id=d.criado_por LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id".($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY d.atualizado_em DESC LIMIT 300';
$q=$pdo->prepare($sql);$q->execute($p);$dossies=$q->fetchAll(PDO::FETCH_ASSOC);
$unidades=$pdo->query('SELECT id,nome,cidade,uf FROM protocolo_unidades_maritimas WHERE ativo=1 ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$usuarios=$pdo->query('SELECT id,nome FROM usuarios WHERE ativo=1 AND excluido_em IS NULL ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$titulo_page='Protocolos documentais - ERP';require __DIR__.'/../../includes/header.php';require __DIR__.'/../../includes/sidebar.php';
?>
<style>
.prot-head{display:flex;justify-content:space-between;gap:16px;align-items:center}.prot-filtros{display:grid;grid-template-columns:2fr repeat(4,1fr);gap:10px}.prot-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:16px 0}.prot-kpi{padding:14px;border:1px solid var(--cor-borda,#dce5e1);border-radius:12px;background:#fff}.prot-table{width:100%;border-collapse:collapse}.prot-table th,.prot-table td{padding:12px;border-bottom:1px solid #e6ece9;text-align:left;vertical-align:top}.prot-status{display:inline-block;padding:5px 9px;border-radius:20px;background:#e6f7f0;color:#067b55;font-size:12px;font-weight:700}@media(max-width:900px){.prot-filtros{grid-template-columns:1fr 1fr}.prot-table thead{display:none}.prot-table tr{display:block;border:1px solid #dde6e2;border-radius:10px;margin:10px 0;padding:8px}.prot-table td{display:block;border:0;padding:5px}.prot-head{align-items:flex-start;flex-direction:column}}@media(max-width:520px){.prot-filtros{grid-template-columns:1fr}}
</style>
<main class="conteudo-principal">
 <div class="prot-head"><div><h1><i class="fa-solid fa-arrow-right-arrow-left"></i> Protocolos documentais</h1><p>Custódia, entrada, saída e tramitação dos documentos da embarcação.</p></div><div><?php if(getCargo()==='ADMIN'): ?><a class="btn btn-secondary" href="<?= APP_URL ?>protocolos/configuracoes"><i class="fa-solid fa-gear"></i> Cadastros</a><?php endif; ?> <a class="btn btn-primary" href="<?= APP_URL ?>protocolos/form"><i class="fa-solid fa-plus"></i> Novo dossiê</a></div></div>
 <div class="prot-cards">
  <div class="prot-kpi"><strong><?= count($dossies) ?></strong><br><small>Dossiês encontrados</small></div>
  <div class="prot-kpi"><strong><?= count(array_filter($dossies,fn($x)=>$x['status']==='EM_EXIGENCIA')) ?></strong><br><small>Em exigência</small></div>
  <div class="prot-kpi"><strong><?= array_sum(array_map(fn($x)=>(int)$x['originais_pendentes'],$dossies)) ?></strong><br><small>Originais a devolver</small></div>
 </div>
 <section class="card"><div class="card-body"><form class="prot-filtros" method="get" action="<?= APP_URL ?>protocolos">
  <input class="form-control" name="busca" value="<?= h($f['busca']) ?>" placeholder="Número, embarcação, cliente ou assunto...">
  <select class="form-control" name="status"><option value="">Todas as situações</option><?php foreach($labels as $v=>$l): ?><option value="<?= h($v) ?>" <?= $f['status']===$v?'selected':'' ?>><?= h($l) ?></option><?php endforeach; ?></select>
  <select class="form-control" name="unidade"><option value="">Todas as unidades</option><?php foreach($unidades as $u): ?><option value="<?= h($u['id']) ?>" <?= $f['unidade']===$u['id']?'selected':'' ?>><?= h($u['nome'].' — '.$u['cidade'].'/'.$u['uf']) ?></option><?php endforeach; ?></select>
  <input class="form-control" name="cidade" value="<?= h($f['cidade']) ?>" placeholder="Cidade">
  <select class="form-control" name="responsavel"><option value="">Todos responsáveis</option><?php foreach($usuarios as $u): ?><option value="<?= h($u['id']) ?>" <?= $f['responsavel']===$u['id']?'selected':'' ?>><?= h($u['nome']) ?></option><?php endforeach; ?></select>
  <input class="form-control" type="date" name="inicio" value="<?= h($f['inicio']) ?>" title="Data inicial"><input class="form-control" type="date" name="fim" value="<?= h($f['fim']) ?>" title="Data final">
  <button class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button><a class="btn btn-secondary" href="<?= APP_URL ?>protocolos">Limpar</a>
 </form></div></section>
 <section class="card" style="margin-top:16px"><div class="card-body" style="overflow:auto">
  <table class="prot-table"><thead><tr><th>Protocolo</th><th>Embarcação / cliente</th><th>Situação</th><th>Destino oficial</th><th>Responsável</th><th></th></tr></thead><tbody>
  <?php foreach($dossies as $d): ?><tr>
   <td><strong><?= h($d['numero']) ?></strong><br><small><?= h($d['assunto']) ?> · <?= (int)$d['eventos'] ?> evento(s)</small></td>
   <td><?= h($d['embarcacao_nome']) ?><br><small><?= h($d['cliente_nome']?:'Cliente não informado') ?></small></td>
   <td><span class="prot-status"><?= h($labels[$d['status']]??$d['status']) ?></span><?php if($d['originais_pendentes']): ?><br><small style="color:#a45b00"><?= (int)$d['originais_pendentes'] ?> original(is) pendente(s)</small><?php endif; ?></td>
   <td><?= h($d['unidade_nome']?:'—') ?><br><small><?= $d['protocolo_externo_em']?formatarDataCompleta($d['protocolo_externo_em']):'Atendimento não registrado' ?></small></td>
   <td><?= h($d['responsavel_nome']) ?><br><small><?= formatarDataCompleta($d['atualizado_em']) ?></small></td>
   <td><a class="btn btn-sm btn-primary" href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($d['id']) ?>">Abrir</a></td>
  </tr><?php endforeach; ?><?php if(!$dossies): ?><tr><td colspan="6">Nenhum protocolo encontrado.</td></tr><?php endif; ?></tbody></table>
 </div></section>
</main>
<?php require __DIR__.'/../../includes/footer.php'; ?>
