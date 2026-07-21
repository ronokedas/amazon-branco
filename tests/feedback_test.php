<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/feedback.php';

$admin=$pdo->query("SELECT id,nome,cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL LIMIT 1")->fetch();
$comum=$pdo->query("SELECT id,nome,cargo FROM usuarios WHERE cargo<>'ADMIN' AND ativo=1 AND excluido_em IS NULL LIMIT 1")->fetch();
if(!$admin||!$comum) throw new RuntimeException('O teste requer um administrador e um usuário comum ativos.');

$_SESSION=['usuario_logado'=>true,'usuario_id'=>$comum['id'],'usuario_cargo'=>$comum['cargo']];
$destinos=feedbackDestinosPermitidos($pdo,$comum['id']);
if(!array_filter($destinos,fn($d)=>$d['id']==='ADMIN')) throw new RuntimeException('Usuário comum não recebeu a caixa Admin configurada.');
$outro=array_values(array_filter($destinos,fn($d)=>$d['id']!=='ADMIN'&&$d['id']!==$comum['id']));
if(!$outro) throw new RuntimeException('Usuário comum não recebeu outros usuários ativos como destinatários.');
if(array_filter($destinos,fn($d)=>$d['id']===$comum['id'])) throw new RuntimeException('O próprio usuário apareceu como destinatário.');
try { feedbackValidarDestino($pdo,$comum['id'],'id-forjado'); throw new RuntimeException('Destino forjado foi aceito.'); } catch(RuntimeException $e) { if($e->getMessage()==='Destino forjado foi aceito.') throw $e; }

$pdo->beginTransaction();
try {
    $fid=gerarUUID();$mid=gerarUUID();
    $q=$pdo->prepare("INSERT INTO feedbacks (id,remetente_id,destinatario_id,categoria,prioridade) VALUES (:id,:r,NULL,'DUVIDA','MEDIA')");$q->execute([':id'=>$fid,':r'=>$comum['id']]);
    $q=$pdo->prepare('INSERT INTO feedback_mensagens (id,feedback_id,autor_id,texto) VALUES (:id,:f,:a,:t)');$q->execute([':id'=>$mid,':f'=>$fid,':a'=>$comum['id'],':t'=>'Mensagem de teste']);
    feedbackSincronizarParticipantes($pdo,$fid,$comum['id'],null);
    $resumo=feedbackResumoNaoLidas($pdo,$admin['id']);if($resumo['count']<1)throw new RuntimeException('Admin não foi notificado na caixa compartilhada.');
    feedbackMarcarLida($pdo,$fid,$admin['id']);
    $q=$pdo->prepare('SELECT lida FROM feedback_participantes WHERE feedback_id=:f AND usuario_id=:u');$q->execute([':f'=>$fid,':u'=>$admin['id']]);if((int)$q->fetchColumn()!==1)throw new RuntimeException('A abertura não marcou a conversa como lida.');
    feedbackNotificarDemais($pdo,$fid,$admin['id']);
    $q=$pdo->prepare('SELECT lida FROM feedback_participantes WHERE feedback_id=:f AND usuario_id=:u');$q->execute([':f'=>$fid,':u'=>$comum['id']]);if((int)$q->fetchColumn()!==0)throw new RuntimeException('A resposta não notificou o outro participante.');
    $pdo->rollBack();
} catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();throw $e; }

echo "OK: destinos, caixa Admin, leitura e notificações de feedback validados.\n";
