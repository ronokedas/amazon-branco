<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
requireClienteSenhaDefinitiva();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Sessão expirada.');header('Location: '.APP_URL.'portal/analises-planos');exit;}
$clienteId=(string)clientePortalId();$analiseId=trim($_POST['analise_id']??'');
try{
    $ids=clientePortalEmbarcacaoIds($pdo,$clienteId);if(!$ids)throw new RuntimeException('Nenhuma embarcação vinculada.');
    $params=[':id'=>$analiseId];$in=clientePortalSqlIn($ids,'upload_emb_',$params);
    $stmt=$pdo->prepare("SELECT ap.* FROM analises_planos ap WHERE ap.id=:id AND ap.embarcacao_id IN ({$in}) AND ap.status IN ('EM_ANALISE','AGUARDANDO_DOCUMENTOS') LIMIT 1");
    $stmt->execute($params);$analise=$stmt->fetch(PDO::FETCH_ASSOC);if(!$analise)throw new RuntimeException('Análise indisponível ou sem vínculo ativo.');
    $arquivos=$_FILES['arquivos']??null;if(!$arquivos||!is_array($arquivos['name']??null))throw new RuntimeException('Selecione pelo menos um arquivo.');
    $preparados=[];foreach($arquivos['name'] as $i=>$nome){if(($arquivos['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;$arquivo=['name'=>$nome,'type'=>$arquivos['type'][$i]??'','tmp_name'=>$arquivos['tmp_name'][$i]??'','error'=>$arquivos['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$arquivos['size'][$i]??0];$preparados[]=[$arquivo,analisePlanosValidarUpload($arquivo)];}
    if(!$preparados)throw new RuntimeException('Selecione ao menos um arquivo válido.');
    $pdo->beginTransaction();$q=$pdo->prepare('SELECT COALESCE(MAX(revisao),0)+1 FROM analise_planos_submissoes WHERE analise_id=:id FOR UPDATE');$q->execute([':id'=>$analiseId]);$rev=(int)$q->fetchColumn();$sub=gerarUUID();
    $pdo->prepare("INSERT INTO analise_planos_submissoes(id,analise_id,revisao,descricao,recebido_em,origem,portal_cliente_id,criado_por)VALUES(:id,:analise,:rev,:descricao,CURDATE(),'PORTAL',:cliente,NULL)")->execute([':id'=>$sub,':analise'=>$analiseId,':rev'=>$rev,':descricao'=>trim($_POST['descricao']??'')?:'Revisão enviada pelo portal',':cliente'=>$clienteId]);
    $ins=$pdo->prepare("INSERT INTO analise_planos_arquivos(id,submissao_id,categoria,nome_original,extensao,mime_type,tamanho_bytes,sha256,chave_arquivo,criado_por)VALUES(:id,:sub,:categoria,:nome,:ext,:mime,:tam,:hash,:chave,NULL)");
    foreach($preparados as [$arquivo,$meta]){$chave=analisePlanosGuardarUpload($arquivo,$analiseId,$meta);$ins->execute([':id'=>gerarUUID(),':sub'=>$sub,':categoria'=>trim($_POST['categoria']??'Outros'),':nome'=>$meta['nome'],':ext'=>$meta['extensao'],':mime'=>$meta['mime'],':tam'=>$meta['tamanho'],':hash'=>$meta['sha256'],':chave'=>$chave]);}
    $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id'=>$analiseId]);
    analisePlanosHistorico($pdo,$analiseId,'REVISAO_PORTAL_RECEBIDA',$analise['status'],'EM_ANALISE','Revisão '.$rev.' enviada pelo portal com '.count($preparados).' arquivo(s).',(string)$analise['criado_por']);
    analisePlanosNotificar($pdo,$analise['analista_id'],'REVISAO_PORTAL_RECEBIDA','Nova revisão recebida pelo portal',$analise['numero'].' recebeu a revisão '.$rev.'.',$analiseId,'analises-planos/form?id='.urlencode($analiseId));
    clientePortalAuditar($pdo,'UPLOAD_ANALISE',null,$analise['embarcacao_id'],'ANALISE_PLANOS',$analiseId,true,'Revisão '.$rev.' com '.count($preparados).' arquivo(s).');$pdo->commit();
    setMensagem('success','Revisão enviada com sucesso. Os arquivos anteriores foram preservados.');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();clientePortalAuditar($pdo,'UPLOAD_ANALISE',null,null,'ANALISE_PLANOS',$analiseId,false,$e->getMessage());setMensagem('error',$e->getMessage());}
header('Location: '.APP_URL.'portal/analises-planos');exit;
