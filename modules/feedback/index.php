<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/feedback.php';
verificar_sessao();

$u=(string)$_SESSION['usuario_id'];
$isAdmin=getCargo()==='ADMIN';
$aba=$isAdmin && ($_GET['aba']??'')==='auditoria' ? 'auditoria' : 'minhas';
if($isAdmin){
    $sync=$pdo->prepare("INSERT IGNORE INTO feedback_participantes (feedback_id,usuario_id,lida) SELECT id,:u,1 FROM feedbacks WHERE destinatario_id IS NULL");
    $sync->execute([':u'=>$u]);
}
$destinos=feedbackDestinosPermitidos($pdo,$u);
$status=$_GET['status']??'';$categoria=$_GET['categoria']??'';$prioridade=$_GET['prioridade']??'';
$busca=trim($_GET['q']??'');$arquivadas=($_GET['arquivadas']??'')==='1';
$remetente=trim($_GET['remetente_id']??'');$destinatario=trim($_GET['destinatario_filtro_id']??'');$cargo=trim($_GET['cargo']??'');
$dataInicio=trim($_GET['data_inicio']??'');$dataFim=trim($_GET['data_fim']??'');

if($aba==='auditoria'){
    $where=['1=1'];$params=[];
    if(isset(feedbackStatus()[$status])){$where[]='f.status=:status';$params[':status']=$status;}
    if(isset(feedbackCategorias()[$categoria])){$where[]='f.categoria=:categoria';$params[':categoria']=$categoria;}
    if(isset(feedbackPrioridades()[$prioridade])){$where[]='f.prioridade=:prioridade';$params[':prioridade']=$prioridade;}
    if($remetente!==''){$where[]='f.remetente_id=:remetente';$params[':remetente']=$remetente;}
    if($destinatario==='ADMIN'){$where[]='f.destinatario_id IS NULL';}
    elseif($destinatario!==''){$where[]='f.destinatario_id=:destinatario';$params[':destinatario']=$destinatario;}
    if(in_array($cargo,['ADMIN','VENDEDOR','VISTORIADOR','ANALISTA'],true)){$where[]='(r.cargo=:cargo_r OR d.cargo=:cargo_d)';$params[':cargo_r']=$cargo;$params[':cargo_d']=$cargo;}
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$dataInicio)){$where[]='f.criado_em>=:inicio';$params[':inicio']=$dataInicio.' 00:00:00';}
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$dataFim)){$where[]='f.criado_em<=:fim';$params[':fim']=$dataFim.' 23:59:59';}
    if($busca!==''){$where[]='EXISTS (SELECT 1 FROM feedback_mensagens fm WHERE fm.feedback_id=f.id AND fm.texto LIKE :busca)';$params[':busca']='%'.$busca.'%';}
    $sql="SELECT f.*,1 lida,NULL arquivado_em,r.nome remetente_nome,r.cargo remetente_cargo,d.nome destinatario_nome,d.cargo destinatario_cargo,(SELECT texto FROM feedback_mensagens WHERE feedback_id=f.id ORDER BY criado_em DESC LIMIT 1) previa FROM feedbacks f JOIN usuarios r ON r.id=f.remetente_id LEFT JOIN usuarios d ON d.id=f.destinatario_id WHERE ".implode(' AND ',$where).' ORDER BY f.atualizado_em DESC LIMIT 300';
}else{
    $where=['p.usuario_id=:u',$arquivadas?'p.arquivado_em IS NOT NULL':'p.arquivado_em IS NULL'];$params=[':u'=>$u];
    if(isset(feedbackStatus()[$status])){$where[]='f.status=:status';$params[':status']=$status;}
    if(isset(feedbackCategorias()[$categoria])){$where[]='f.categoria=:categoria';$params[':categoria']=$categoria;}
    if($busca!==''){$where[]='EXISTS (SELECT 1 FROM feedback_mensagens fm WHERE fm.feedback_id=f.id AND fm.texto LIKE :busca)';$params[':busca']='%'.$busca.'%';}
    $sql="SELECT f.*,p.lida,p.arquivado_em,r.nome remetente_nome,r.cargo remetente_cargo,d.nome destinatario_nome,d.cargo destinatario_cargo,(SELECT texto FROM feedback_mensagens WHERE feedback_id=f.id ORDER BY criado_em DESC LIMIT 1) previa FROM feedback_participantes p JOIN feedbacks f ON f.id=p.feedback_id JOIN usuarios r ON r.id=f.remetente_id LEFT JOIN usuarios d ON d.id=f.destinatario_id WHERE ".implode(' AND ',$where).' ORDER BY f.atualizado_em DESC LIMIT 200';
}
$q=$pdo->prepare($sql);$q->execute($params);$feedbacks=$q->fetchAll();
$usuariosAtivos=$isAdmin?$pdo->query("SELECT id,nome,cargo FROM usuarios WHERE ativo=1 AND excluido_em IS NULL ORDER BY nome")->fetchAll():[];
$metricas=null;if($isAdmin){$metricas=['admin'=>(int)$pdo->query("SELECT COUNT(*) FROM feedbacks WHERE destinatario_id IS NULL AND status IN ('ABERTO','RESPONDIDO')")->fetchColumn(),'internas'=>(int)$pdo->query("SELECT COUNT(*) FROM feedbacks WHERE destinatario_id IS NOT NULL")->fetchColumn(),'media'=>$pdo->query("SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,f.criado_em,(SELECT MIN(m.criado_em) FROM feedback_mensagens m JOIN usuarios a ON a.id=m.autor_id WHERE m.feedback_id=f.id AND a.cargo='ADMIN'))),1) FROM feedbacks f WHERE f.destinatario_id IS NULL")->fetchColumn()];}
$titulo_page='Central de Feedback';require __DIR__.'/../../includes/header.php';
?>
<div class="feedback-page"><header class="feedback-title"><div><h1><i class="fa-regular fa-comments"></i> Central de Feedback</h1><p>Mensagens internas e histórico de atendimento.</p></div></header>
<?php if($isAdmin):?><nav class="feedback-tabs" aria-label="Visões da Central"><a class="<?=$aba==='minhas'?'active':''?>" href="<?=APP_URL?>feedback">Minhas conversas</a><a class="<?=$aba==='auditoria'?'active':''?>" href="<?=APP_URL?>feedback?aba=auditoria"><i class="fa-solid fa-shield-halved"></i> Auditoria</a></nav><?php endif;?>
<?php if($metricas):?><div class="feedback-metrics"><article><strong><?=$metricas['admin']?></strong><span>atendimentos da caixa Admin</span></article><article><strong><?=$metricas['internas']?></strong><span>conversas entre usuários</span></article><article><strong><?=h($metricas['media']??'—')?></strong><span>horas até 1ª resposta</span></article></div><?php endif;?>
<?php if($aba==='auditoria'):?>
<section class="feedback-card feedback-audit"><div class="feedback-audit-notice"><i class="fa-solid fa-eye"></i><span><strong>Modo somente auditoria</strong> A consulta não altera leitura, notificações, participantes ou estado da conversa.</span></div>
<form class="feedback-audit-filters" method="get"><input type="hidden" name="aba" value="auditoria"><input name="q" value="<?=h($busca)?>" placeholder="Buscar texto, telefone ou informação"><select name="remetente_id"><option value="">Todos os remetentes</option><?php foreach($usuariosAtivos as $x):?><option value="<?=h($x['id'])?>" <?=$remetente===$x['id']?'selected':''?>><?=h($x['nome'])?></option><?php endforeach;?></select><select name="destinatario_filtro_id"><option value="">Todos os destinatários</option><option value="ADMIN" <?=$destinatario==='ADMIN'?'selected':''?>>Caixa Admin</option><?php foreach($usuariosAtivos as $x):?><option value="<?=h($x['id'])?>" <?=$destinatario===$x['id']?'selected':''?>><?=h($x['nome'])?></option><?php endforeach;?></select><select name="cargo"><option value="">Todos os cargos</option><?php foreach(['ADMIN','VENDEDOR','VISTORIADOR','ANALISTA'] as $x):?><option value="<?=$x?>" <?=$cargo===$x?'selected':''?>><?=$x?></option><?php endforeach;?></select><select name="status"><option value="">Todos os estados</option><?php foreach(feedbackStatus() as $k=>$v):?><option value="<?=$k?>" <?=$status===$k?'selected':''?>><?=h($v)?></option><?php endforeach;?></select><select name="prioridade"><option value="">Todas as prioridades</option><?php foreach(feedbackPrioridades() as $k=>$v):?><option value="<?=$k?>" <?=$prioridade===$k?'selected':''?>><?=h($v)?></option><?php endforeach;?></select><select name="categoria"><option value="">Todas as categorias</option><?php foreach(feedbackCategorias() as $k=>$v):?><option value="<?=$k?>" <?=$categoria===$k?'selected':''?>><?=h($v)?></option><?php endforeach;?></select><input type="date" name="data_inicio" value="<?=h($dataInicio)?>" title="Data inicial"><input type="date" name="data_fim" value="<?=h($dataFim)?>" title="Data final"><button class="btn">Filtrar</button></form>
<?php else:?>
<div class="feedback-layout"><section class="feedback-card"><h2>Nova conversa</h2><div class="feedback-compliance"><i class="fa-solid fa-shield-halved"></i> As mensagens internas podem ser consultadas pelos administradores para fins de segurança e conformidade.</div><form method="post" action="<?=APP_URL?>feedback/actions" enctype="multipart/form-data" data-feedback-upload><input type="hidden" name="csrf_token" value="<?=h(gerarCSRF())?>"><input type="hidden" name="action" value="criar"><label>Pesquisar destinatário<input type="search" data-feedback-user-search placeholder="Digite nome ou cargo" autocomplete="off"></label><label>Destinatário<select name="destinatario_id" data-feedback-user-select required><option value="">Selecione...</option><?php foreach($destinos as $d):?><option value="<?=h($d['id'])?>"><?=h($d['nome'])?> · <?=h($d['cargo'])?></option><?php endforeach;?></select></label><div class="feedback-fields"><label>Categoria<select name="categoria" required><?php foreach(feedbackCategorias() as $k=>$v):?><option value="<?=$k?>"><?=h($v)?></option><?php endforeach;?></select></label><label>Prioridade<select name="prioridade" required><?php foreach(feedbackPrioridades() as $k=>$v):?><option value="<?=$k?>" <?=$k==='MEDIA'?'selected':''?>><?=h($v)?></option><?php endforeach;?></select></label></div><label>Mensagem<textarea name="texto" rows="6" maxlength="20000" required></textarea></label><label>Anexos <small>(até 5, 10 MB cada)</small><input type="file" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx,.pptx,.csv,.txt"></label><div class="feedback-preview"></div><button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Enviar</button></form></section>
<section class="feedback-card feedback-list"><form class="feedback-filters" method="get"><input name="q" value="<?=h($busca)?>" placeholder="Buscar nas mensagens"><select name="status"><option value="">Todos os estados</option><?php foreach(feedbackStatus() as $k=>$v):?><option value="<?=$k?>" <?=$status===$k?'selected':''?>><?=h($v)?></option><?php endforeach;?></select><select name="categoria"><option value="">Todas as categorias</option><?php foreach(feedbackCategorias() as $k=>$v):?><option value="<?=$k?>" <?=$categoria===$k?'selected':''?>><?=h($v)?></option><?php endforeach;?></select><label class="feedback-check"><input type="checkbox" name="arquivadas" value="1" <?=$arquivadas?'checked':''?>> Arquivadas</label><button class="btn">Filtrar</button></form>
<?php endif;?>
<div class="feedback-results"><?php if(!$feedbacks):?><div class="feedback-empty"><i class="fa-regular fa-message"></i><p>Nenhuma conversa encontrada.</p></div><?php endif;?><?php foreach($feedbacks as $f):?><a class="feedback-row <?=$f['lida']?'':'is-unread'?>" href="<?=APP_URL?>feedback/conversa?id=<?=urlencode($f['id'])?><?=$aba==='auditoria'?'&modo=auditoria':''?>"><span class="feedback-dot"></span><span><strong><?=h($f['remetente_nome'])?> → <?=h($f['destinatario_nome']?:'Admin')?> · <?=h(feedbackCategorias()[$f['categoria']])?></strong><small><?=h(mb_strimwidth($f['previa'],0,115,'…'))?></small></span><span><em class="feedback-priority p-<?=strtolower($f['prioridade'])?>"><?=h(feedbackPrioridades()[$f['prioridade']])?></em><small><?=date('d/m H:i',strtotime($f['atualizado_em']))?></small></span></a><?php endforeach;?></div></section><?php if($aba!=='auditoria'):?></div><?php endif;?></div>
<?php require __DIR__.'/../../includes/footer.php';?>
