<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
analisePlanosExigirAcesso();

$busca=trim($_GET['busca']??'');$status=trim($_GET['status']??'');$params=[];$where=[];
if(getCargo()!=='ADMIN'){$where[]='ap.analista_id=:analista';$params[':analista']=$_SESSION['usuario_id'];}
if($busca!==''){$where[]='(ap.numero LIKE :busca OR e.nome LIKE :busca OR ap.objeto LIKE :busca)';$params[':busca']='%'.$busca.'%';}
if(in_array($status,['RASCUNHO','EM_ANALISE','AGUARDANDO_CORRECAO','AGUARDANDO_APROVACAO','CONCLUIDA','REPROVADA','CANCELADA'],true)){$where[]='ap.status=:status';$params[':status']=$status;}
$sql="SELECT ap.*,e.nome embarcacao_nome,u.nome analista_nome,(SELECT COUNT(*) FROM analise_planos_exigencias x WHERE x.analise_id=ap.id AND x.status IN ('PENDENTE','PARCIAL','TRANSCRITA')) exigencias_pendentes FROM analises_planos ap INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id LEFT JOIN usuarios u ON u.id=ap.analista_id".($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY ap.atualizado_em DESC';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$analises=$stmt->fetchAll(PDO::FETCH_ASSOC);
$statusLabels=['RASCUNHO'=>'Rascunho','EM_ANALISE'=>'Em análise','AGUARDANDO_CORRECAO'=>'Aguardando correção','AGUARDANDO_APROVACAO'=>'Aguardando aprovação','CONCLUIDA'=>'Concluída','REPROVADA'=>'Reprovada','CANCELADA'=>'Cancelada'];
$titulo_page='Análise de Planos - ERP Sistema';require_once __DIR__.'/../../includes/header.php';
?>
<div class="conteudo-principal">
 <div class="tabela-container">
  <div class="tabela-header"><h3><i class="fas fa-drafting-compass"></i> Análise de Planos</h3><a class="btn btn-primary btn-sm" href="<?=APP_URL?>analises-planos/form"><i class="fas fa-plus"></i> Nova análise</a></div>
  <form method="get" class="filtros" style="margin:15px 20px">
   <div class="form-group" style="flex:1"><label>Buscar</label><input name="busca" value="<?=h($busca)?>" placeholder="Número, embarcação ou objeto"></div>
   <div class="form-group"><label>Situação</label><select name="status"><option value="">Todas</option><?php foreach($statusLabels as $v=>$l):?><option value="<?=$v?>" <?=$status===$v?'selected':''?>><?=$l?></option><?php endforeach?></select></div>
   <button class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button><a class="btn btn-secondary" href="<?=APP_URL?>analises-planos">Limpar</a>
  </form>
  <?php if(!$analises):?><div class="tabela-vazia"><i class="fas fa-drafting-compass"></i><h3>Nenhuma análise encontrada</h3></div><?php else:?><table><thead><tr><th>Número</th><th>Embarcação</th><th>Processo</th><th>Analista</th><th>Situação</th><th>Exigências</th><th></th></tr></thead><tbody>
  <?php foreach($analises as $a):?><tr><td><strong><?=h($a['numero'])?></strong><small style="display:block"><?=h($a['enquadramento'])?></small></td><td><?=h($a['embarcacao_nome'])?></td><td><?=h($a['tipo_processo'])?><small style="display:block"><?=h($a['objeto'])?></small></td><td><?=h($a['analista_nome'])?></td><td><span class="badge badge-<?=$a['status']==='CONCLUIDA'?'success':($a['status']==='REPROVADA'||$a['status']==='CANCELADA'?'danger':'warning')?>"><?=h($statusLabels[$a['status']]??$a['status'])?></span></td><td class="text-center"><?=(int)$a['exigencias_pendentes']?></td><td><a class="btn btn-primary btn-sm" href="<?=APP_URL?>analises-planos/form?id=<?=urlencode($a['id'])?>"><i class="fas fa-eye"></i> Abrir</a></td></tr><?php endforeach?></tbody></table><?php endif?>
 </div>
</div>
<?php require_once __DIR__.'/../../includes/footer.php';?>
