<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/feedback.php';

$admin=$pdo->query("SELECT id,cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL LIMIT 1")->fetch();
$usuarios=$pdo->query("SELECT id,cargo FROM usuarios WHERE cargo<>'ADMIN' AND ativo=1 AND excluido_em IS NULL LIMIT 2")->fetchAll();
if(!$admin||count($usuarios)<2) throw new RuntimeException('O teste requer um administrador e dois usuários comuns ativos.');

$pdo->beginTransaction();
try {
    $fid=gerarUUID();$mid=gerarUUID();
    $q=$pdo->prepare("INSERT INTO feedbacks (id,remetente_id,destinatario_id,categoria,prioridade) VALUES (:id,:r,:d,'DUVIDA','MEDIA')");
    $q->execute([':id'=>$fid,':r'=>$usuarios[0]['id'],':d'=>$usuarios[1]['id']]);
    $q=$pdo->prepare('INSERT INTO feedback_mensagens (id,feedback_id,autor_id,texto) VALUES (:id,:f,:a,:t)');
    $q->execute([':id'=>$mid,':f'=>$fid,':a'=>$usuarios[0]['id'],':t'=>'Contato 99999-0000 para localizar pela auditoria']);
    feedbackSincronizarParticipantes($pdo,$fid,$usuarios[0]['id'],$usuarios[1]['id']);

    if(feedbackPodeParticipar($pdo,$fid,$admin['id'])) throw new RuntimeException('Admin auditor foi incluído como participante.');
    if(!feedbackPodeAuditar($pdo,$fid,$admin['id'])||!feedbackPodeConsultar($pdo,$fid,$admin['id'])) throw new RuntimeException('Admin não recebeu acesso de auditoria.');
    $q=$pdo->prepare('SELECT COUNT(*) FROM feedback_participantes WHERE feedback_id=:f AND usuario_id=:u');$q->execute([':f'=>$fid,':u'=>$admin['id']]);
    if((int)$q->fetchColumn()!==0) throw new RuntimeException('A auditoria criou estado de leitura para o Admin.');
    if(feedbackPodeConsultar($pdo,$fid,'usuario-inexistente')) throw new RuntimeException('Usuário externo recebeu acesso à conversa.');
    $pdo->rollBack();
} catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();throw $e; }

echo "OK: auditoria administrativa é somente consulta e não altera participantes.\n";
