<?php
require_once __DIR__.'/../../config.php'; require_once __DIR__.'/../../includes/functions.php'; require_once __DIR__.'/../../includes/auth.php'; require_once __DIR__.'/../../includes/feedback.php';
verificar_sessao();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Requisição inválida.');redirecionar(APP_URL.'feedback');}
$u=(string)$_SESSION['usuario_id']; $action=$_POST['action']??'';
try{
 if($action==='criar'){
  $dest=$_POST['destinatario_id']??'ADMIN'; $dest=$dest==='ADMIN'?null:$dest; feedbackValidarDestino($pdo,$u,$dest);
  $cat=$_POST['categoria']??'';$prio=$_POST['prioridade']??'';$texto=trim($_POST['texto']??'');
  if(!isset(feedbackCategorias()[$cat])||!isset(feedbackPrioridades()[$prio])||$texto==='')throw new RuntimeException('Preencha destinatário, categoria, prioridade e mensagem.');
  if(mb_strlen($texto)>20000)throw new RuntimeException('A mensagem deve ter no máximo 20.000 caracteres.');
  $uploads=feedbackUploadsNormalizar($_FILES['anexos']??[]); foreach($uploads as $a)feedbackValidarUpload($a);
  $pdo->beginTransaction();$fid=gerarUUID();$mid=gerarUUID();
  $q=$pdo->prepare('INSERT INTO feedbacks (id,remetente_id,destinatario_id,categoria,prioridade) VALUES (:id,:r,:d,:c,:p)');$q->execute([':id'=>$fid,':r'=>$u,':d'=>$dest,':c'=>$cat,':p'=>$prio]);
  $q=$pdo->prepare('INSERT INTO feedback_mensagens (id,feedback_id,autor_id,texto) VALUES (:id,:f,:a,:t)');$q->execute([':id'=>$mid,':f'=>$fid,':a'=>$u,':t'=>$texto]);
  feedbackSincronizarParticipantes($pdo,$fid,$u,$dest); feedbackSalvarAnexos($pdo,$fid,$mid,$_FILES['anexos']??[]);$pdo->commit();
  setMensagem('success','Conversa criada com sucesso.');redirecionar(APP_URL.'feedback/conversa?id='.urlencode($fid));
 }
 $fid=trim($_POST['feedback_id']??'');if(!feedbackPodeParticipar($pdo,$fid,$u))throw new RuntimeException('Conversa não encontrada.');
 if($action==='responder'){
  $texto=trim($_POST['texto']??'');if($texto==='')throw new RuntimeException('Escreva uma mensagem.');if(mb_strlen($texto)>20000)throw new RuntimeException('A mensagem deve ter no máximo 20.000 caracteres.');
  $uploads=feedbackUploadsNormalizar($_FILES['anexos']??[]);foreach($uploads as $a)feedbackValidarUpload($a);
  $pdo->beginTransaction();$q=$pdo->prepare('SELECT remetente_id FROM feedbacks WHERE id=:id FOR UPDATE');$q->execute([':id'=>$fid]);$rem=$q->fetchColumn();$status=$u===$rem?'ABERTO':'RESPONDIDO';$mid=gerarUUID();
  $q=$pdo->prepare('INSERT INTO feedback_mensagens (id,feedback_id,autor_id,texto) VALUES (:id,:f,:a,:t)');$q->execute([':id'=>$mid,':f'=>$fid,':a'=>$u,':t'=>$texto]);feedbackSalvarAnexos($pdo,$fid,$mid,$_FILES['anexos']??[]);
  $q=$pdo->prepare('UPDATE feedbacks SET status=:s,atualizado_em=CURRENT_TIMESTAMP WHERE id=:id');$q->execute([':s'=>$status,':id'=>$fid]);feedbackNotificarDemais($pdo,$fid,$u);$pdo->commit();setMensagem('success','Resposta enviada.');
 }elseif($action==='status'){$novo=$_POST['status']??'';if(!in_array($novo,['ABERTO','RESOLVIDO'],true))throw new RuntimeException('Estado inválido.');$q=$pdo->prepare('UPDATE feedbacks SET status=:s,atualizado_em=CURRENT_TIMESTAMP WHERE id=:id');$q->execute([':s'=>$novo,':id'=>$fid]);setMensagem('success',$novo==='RESOLVIDO'?'Conversa resolvida.':'Conversa reaberta.');
 }elseif($action==='arquivar'){$q=$pdo->prepare('UPDATE feedback_participantes SET arquivado_em=IF(arquivado_em IS NULL,CURRENT_TIMESTAMP,NULL),lida=1 WHERE feedback_id=:f AND usuario_id=:u');$q->execute([':f'=>$fid,':u'=>$u]);setMensagem('success','Preferência de arquivamento atualizada.');}
 else throw new RuntimeException('Ação inválida.');
 redirecionar(APP_URL.'feedback/conversa?id='.urlencode($fid));
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Feedback: '.$e->getMessage());setMensagem('error',$e instanceof RuntimeException?$e->getMessage():'Não foi possível concluir a operação.');redirecionar(APP_URL.'feedback');}
