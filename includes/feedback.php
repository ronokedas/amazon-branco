<?php

function feedbackCategorias(): array { return ['DUVIDA'=>'Dúvida','SUGESTAO'=>'Sugestão','BUG'=>'Bug/erro no sistema','RECLAMACAO'=>'Reclamação','ELOGIO'=>'Elogio']; }
function feedbackPrioridades(): array { return ['BAIXA'=>'Baixa','MEDIA'=>'Média','ALTA'=>'Alta','URGENTE'=>'Urgente']; }
function feedbackStatus(): array { return ['ABERTO'=>'Aberto','RESPONDIDO'=>'Respondido','RESOLVIDO'=>'Resolvido']; }

function feedbackEhAdmin(?string $usuarioId = null): bool
{
    global $pdo;
    if (!$usuarioId) return getCargo() === 'ADMIN';
    $stmt=$pdo->prepare("SELECT 1 FROM usuarios WHERE id=:id AND cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL");
    $stmt->execute([':id'=>$usuarioId]);
    return (bool)$stmt->fetchColumn();
}

function feedbackPodeParticipar(PDO $pdo, string $feedbackId, string $usuarioId): bool
{
    $stmt=$pdo->prepare("SELECT 1 FROM feedbacks f JOIN usuarios u ON u.id=:usuario_join WHERE f.id=:feedback AND (f.remetente_id=:usuario_remetente OR f.destinatario_id=:usuario_destino OR (f.destinatario_id IS NULL AND u.cargo='ADMIN' AND u.ativo=1 AND u.excluido_em IS NULL))");
    $stmt->execute([':usuario_join'=>$usuarioId, ':usuario_remetente'=>$usuarioId, ':usuario_destino'=>$usuarioId, ':feedback'=>$feedbackId]);
    return (bool)$stmt->fetchColumn();
}

function feedbackPodeAuditar(PDO $pdo, string $feedbackId, string $usuarioId): bool
{
    if (!feedbackEhAdmin($usuarioId)) return false;
    $stmt=$pdo->prepare('SELECT 1 FROM feedbacks WHERE id=:id');
    $stmt->execute([':id'=>$feedbackId]);
    return (bool)$stmt->fetchColumn();
}

function feedbackPodeConsultar(PDO $pdo, string $feedbackId, string $usuarioId): bool
{
    return feedbackPodeParticipar($pdo,$feedbackId,$usuarioId) || feedbackPodeAuditar($pdo,$feedbackId,$usuarioId);
}

function feedbackDestinosPermitidos(PDO $pdo, string $usuarioId): array
{
    $stmt=$pdo->prepare('SELECT id,nome,cargo,gestor_id FROM usuarios WHERE id=:id AND ativo=1 AND excluido_em IS NULL');
    $stmt->execute([':id'=>$usuarioId]); $origem=$stmt->fetch();
    if (!$origem) return [];
    if ($origem['cargo']==='ADMIN') {
        $rows=$pdo->query("SELECT id,nome,cargo FROM usuarios WHERE ativo=1 AND excluido_em IS NULL ORDER BY nome")->fetchAll();
        array_unshift($rows,['id'=>'ADMIN','nome'=>'Admin (caixa compartilhada)','cargo'=>'ADMIN']);
        return $rows;
    }
    $r=$pdo->prepare('SELECT escopo,cargo_destino FROM feedback_regras_comunicacao WHERE cargo_origem=:cargo AND ativo=1');
    $r->execute([':cargo'=>$origem['cargo']]); $regras=$r->fetchAll();
    $destinos=[];
    foreach ($regras as $regra) {
        if ($regra['escopo']==='ADMIN') { $destinos['ADMIN']=['id'=>'ADMIN','nome'=>'Admin (caixa compartilhada)','cargo'=>'ADMIN']; continue; }
        $sql="SELECT id,nome,cargo FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND id<>:usuario"; $params=[':usuario'=>$usuarioId];
        if ($regra['escopo']==='TODOS_USUARIOS') { /* A consulta base já representa todos os usuários válidos. */ }
        elseif ($regra['escopo']==='GESTOR_DIRETO') { $sql.=' AND id=:gestor'; $params[':gestor']=$origem['gestor_id'] ?: ''; }
        elseif ($regra['escopo']==='SUBORDINADOS') { $sql.=' AND gestor_id=:gestor_origem'; $params[':gestor_origem']=$usuarioId; }
        elseif ($regra['escopo']==='OUTROS_GESTORES') { $sql.=' AND EXISTS (SELECT 1 FROM usuarios s WHERE s.gestor_id=usuarios.id AND s.ativo=1 AND s.excluido_em IS NULL)'; }
        elseif ($regra['escopo']==='CARGO') { $sql.=' AND cargo=:cargo'; $params[':cargo']=$regra['cargo_destino']; }
        else continue;
        $q=$pdo->prepare($sql.' ORDER BY nome'); $q->execute($params);
        foreach ($q->fetchAll() as $d) $destinos[$d['id']]=$d;
    }
    return array_values($destinos);
}

function feedbackValidarDestino(PDO $pdo, string $usuarioId, ?string $destino): void
{
    $chave=$destino ?: 'ADMIN';
    foreach (feedbackDestinosPermitidos($pdo,$usuarioId) as $d) if ($d['id']===$chave) return;
    throw new RuntimeException('Destinatário não autorizado para o seu cargo.');
}

function feedbackSincronizarParticipantes(PDO $pdo, string $feedbackId, string $remetenteId, ?string $destinatarioId): void
{
    $ids=[$remetenteId];
    if ($destinatarioId) $ids[]=$destinatarioId;
    else $ids=array_merge($ids,$pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL")->fetchAll(PDO::FETCH_COLUMN));
    $stmt=$pdo->prepare('INSERT IGNORE INTO feedback_participantes (feedback_id,usuario_id,lida) VALUES (:feedback,:usuario,:lida)');
    foreach (array_unique($ids) as $id) $stmt->execute([':feedback'=>$feedbackId,':usuario'=>$id,':lida'=>$id===$remetenteId?1:0]);
}

function feedbackMarcarLida(PDO $pdo,string $feedbackId,string $usuarioId): void
{
    if (!feedbackPodeParticipar($pdo,$feedbackId,$usuarioId)) throw new RuntimeException('Conversa não encontrada.');
    $stmt=$pdo->prepare('INSERT INTO feedback_participantes (feedback_id,usuario_id,lida) VALUES (:f,:u,1) ON DUPLICATE KEY UPDATE lida=1, atualizado_em=CURRENT_TIMESTAMP');
    $stmt->execute([':f'=>$feedbackId,':u'=>$usuarioId]);
}

function feedbackNotificarDemais(PDO $pdo,string $feedbackId,string $autorId): void
{
    $stmt=$pdo->prepare('UPDATE feedback_participantes SET lida=IF(usuario_id=:autor,1,0), arquivado_em=NULL WHERE feedback_id=:feedback');
    $stmt->execute([':autor'=>$autorId,':feedback'=>$feedbackId]);
}

function feedbackResumoNaoLidas(PDO $pdo,string $usuarioId,int $limite=3): array
{
    try {
    $q=$pdo->prepare("SELECT COUNT(*) FROM feedback_participantes p WHERE p.usuario_id=:u AND p.lida=0 AND p.arquivado_em IS NULL"); $q->execute([':u'=>$usuarioId]);
    $count=(int)$q->fetchColumn();
    $q=$pdo->prepare("SELECT f.id,f.categoria,f.prioridade,f.atualizado_em,u.nome remetente,(SELECT texto FROM feedback_mensagens WHERE feedback_id=f.id ORDER BY criado_em DESC LIMIT 1) previa FROM feedback_participantes p JOIN feedbacks f ON f.id=p.feedback_id JOIN usuarios u ON u.id=f.remetente_id WHERE p.usuario_id=:u AND p.lida=0 AND p.arquivado_em IS NULL ORDER BY f.atualizado_em DESC LIMIT ".max(1,$limite));
    $q->execute([':u'=>$usuarioId]); return ['count'=>$count,'recentes'=>$q->fetchAll()];
    } catch (Throwable $e) { return ['count'=>0,'recentes'=>[]]; }
}

function feedbackUploadsNormalizar(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) return [];
    $out=[]; foreach ($files['name'] as $i=>$name) if (($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) $out[]=['name'=>$name,'type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$files['size'][$i]??0];
    if (count($out)>5) throw new RuntimeException('Envie no máximo 5 arquivos por mensagem.');
    return $out;
}

function feedbackValidarUpload(array $a): array
{
    if (($a['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($a['tmp_name']??''))) throw new RuntimeException('Falha ao receber um dos anexos.');
    $size=(int)($a['size']??0); if ($size<1 || $size>10*1024*1024) throw new RuntimeException('Cada anexo deve ter no máximo 10 MB.');
    $nome=basename((string)$a['name']); $ext=strtolower(pathinfo($nome,PATHINFO_EXTENSION));
    $map=['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],'pdf'=>['application/pdf'],'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'],'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'],'pptx'=>['application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip'],'csv'=>['text/plain','text/csv','application/csv'],'txt'=>['text/plain']];
    if (!isset($map[$ext])) throw new RuntimeException('Tipo de anexo não permitido.');
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($a['tmp_name']); if (!in_array($mime,$map[$ext],true)) throw new RuntimeException('O conteúdo do arquivo não corresponde à extensão.');
    $head=file_get_contents($a['tmp_name'],false,null,0,8192)?:''; if (preg_match('/<\?(?:php|=)|<script\b|\x4d\x5a\x90|#!\s*\/bin\//i',$head)) throw new RuntimeException('Conteúdo executável bloqueado.');
    if (in_array($ext,['docx','xlsx','pptx'],true)) {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Validação de documentos Office indisponível no servidor.');
        $zip=new ZipArchive(); if ($zip->open($a['tmp_name'])!==true) throw new RuntimeException('Documento Office inválido.');
        $root=['docx'=>'word/','xlsx'=>'xl/','pptx'=>'ppt/'][$ext]; $ok=false;
        for($i=0;$i<$zip->numFiles;$i++){ $n=strtolower((string)$zip->getNameIndex($i)); if(str_starts_with($n,$root))$ok=true; if(str_contains($n,'../')||preg_match('/(?:vbaproject|activex|\.exe$|\.js$|\.bat$|\.sh$)/i',$n)){ $zip->close(); throw new RuntimeException('Documento Office contém conteúdo inseguro.'); }}
        $zip->close(); if(!$ok) throw new RuntimeException('Documento Office incompatível com a extensão.');
    }
    return ['nome'=>mb_substr($nome,0,255),'ext'=>$ext,'mime'=>$mime,'size'=>$size,'sha'=>hash_file('sha256',$a['tmp_name'])];
}

function feedbackGuardarUpload(array $a,string $feedbackId,array $m): string
{
    $key='feedback/'.$feedbackId.'/'.gerarUUID().'.'.$m['ext'];
    if (class_exists('Aws\\S3\\S3Client')) try {
        $s3=new Aws\S3\S3Client(['version'=>'latest','region'=>'us-east-1','endpoint'=>MINIO_ENDPOINT,'use_path_style_endpoint'=>true,'credentials'=>['key'=>MINIO_ACCESS_KEY,'secret'=>MINIO_SECRET_KEY]]); $bucket=defined('MINIO_FEEDBACK_BUCKET')?MINIO_FEEDBACK_BUCKET:'erp-feedback-private';
        try{$s3->headBucket(['Bucket'=>$bucket]);}catch(Throwable $e){$s3->createBucket(['Bucket'=>$bucket]);}
        $s3->putObject(['Bucket'=>$bucket,'Key'=>$key,'SourceFile'=>$a['tmp_name'],'ContentType'=>$m['mime']]); return $key;
    } catch(Throwable $e) { error_log('Feedback MinIO: '.$e->getMessage()); }
    $path=BASE_PATH.'/storage/private/'.$key; if(!is_dir(dirname($path))&&!mkdir(dirname($path),0750,true)&&!is_dir(dirname($path))) throw new RuntimeException('Falha ao preparar armazenamento.');
    if(!move_uploaded_file($a['tmp_name'],$path)) throw new RuntimeException('Falha ao armazenar anexo.'); return 'local:'.$key;
}

function feedbackSalvarAnexos(PDO $pdo,string $feedbackId,string $mensagemId,array $files): void
{
    foreach(feedbackUploadsNormalizar($files) as $a){$m=feedbackValidarUpload($a);$key=feedbackGuardarUpload($a,$feedbackId,$m);$q=$pdo->prepare('INSERT INTO feedback_anexos (id,mensagem_id,nome_arquivo,chave_arquivo,tipo_mime,extensao,tamanho,sha256) VALUES (:id,:msg,:nome,:chave,:mime,:ext,:tam,:sha)');$q->execute([':id'=>gerarUUID(),':msg'=>$mensagemId,':nome'=>$m['nome'],':chave'=>$key,':mime'=>$m['mime'],':ext'=>$m['ext'],':tam'=>$m['size'],':sha'=>$m['sha']]);}
}
