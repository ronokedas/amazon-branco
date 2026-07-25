<?php
require_once __DIR__.'/../../config.php';require_once __DIR__.'/../../includes/functions.php';require_once __DIR__.'/../../includes/auth.php';require_once __DIR__.'/../../includes/protocolos.php';protocoloExigirAcesso();
$id=trim($_GET['id']??'');$q=$pdo->prepare('SELECT c.*,d.criado_por,d.proposta_id,d.analise_id,d.vistoria_id FROM protocolo_comprovantes c JOIN protocolo_dossies d ON d.id=c.dossie_id WHERE c.id=:id');$q->execute([':id'=>$id]);$a=$q->fetch(PDO::FETCH_ASSOC);
if(!$a||!protocoloUsuarioPodeAcessar($pdo,$a)){http_response_code(404);exit('Arquivo não encontrado.');}
$abs=dirname(__DIR__,2).'/'.$a['caminho'];if(!is_file($abs)||!hash_equals($a['sha256'],hash_file('sha256',$abs))){http_response_code(410);exit('Arquivo indisponível ou com integridade inválida.');}
header('Content-Type: '.$a['mime_type']);header('Content-Length: '.filesize($abs));header('Content-Disposition: inline; filename="'.str_replace('"','',$a['nome_original']).'"');header('X-Content-Type-Options: nosniff');readfile($abs);exit;
