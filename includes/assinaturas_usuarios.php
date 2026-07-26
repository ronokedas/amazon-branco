<?php

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/aprovacao_documentos.php';

function assinaturaCertificadosMapas(): array
{
    return [
        'CSN'=>['table'=>'certificados_csn','label'=>'CSN','number'=>'numero','list'=>'documentacao/certificados'],
        'CNBL'=>['table'=>'certificados_cnbl','label'=>'CNBL','number'=>'numero','list'=>'documentacao/cnbl'],
        'CNARQ'=>['table'=>'certificados_cnarq','label'=>'CNARQ','number'=>'numero','list'=>'documentacao/cnarq'],
    ];
}

function assinaturaResponsavelUsuario(PDO $pdo, string $usuarioId, bool $completo = true): ?array
{
    $sql="SELECT ra.*,u.nome usuario_nome,u.cargo usuario_cargo,u.email usuario_email
            FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id
           WHERE ra.usuario_id=:usuario AND ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL";
    if($completo)$sql.=" AND ra.email IS NOT NULL AND ra.email<>'' AND ra.assinatura_arquivo IS NOT NULL AND ra.assinatura_arquivo<>'' AND ra.assinatura_hash IS NOT NULL AND ra.assinatura_hash<>''";
    $stmt=$pdo->prepare($sql.' LIMIT 1');$stmt->execute([':usuario'=>$usuarioId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
    return $row?:null;
}

function assinaturaResponsavelValidoParaDocumento(PDO $pdo, int $responsavelId, string $criadorId = ''): array
{
    $stmt=$pdo->prepare("SELECT ra.*,u.cargo usuario_cargo,u.ativo usuario_ativo,u.excluido_em
        FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id WHERE ra.id=:id AND ra.ativo=1");
    $stmt->execute([':id'=>$responsavelId]);$resp=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$resp || !(int)$resp['usuario_ativo'] || $resp['excluido_em']!==null || !filter_var($resp['email'],FILTER_VALIDATE_EMAIL) || empty($resp['assinatura_arquivo']) || empty($resp['assinatura_hash'])) throw new RuntimeException('O responsavel precisa ter usuario ativo, e-mail e assinatura completos.');
    if($resp['usuario_cargo']==='ANALISTA' && $criadorId!=='' && $resp['usuario_id']!==$criadorId) throw new RuntimeException('Analistas somente podem assinar documentos criados por eles.');
    return $resp;
}

function assinaturaRegistrarEmail(PDO $pdo,string $destinatario,string $tipo,string $id,bool $sucesso,string $erro=''): void
{
    $stmt=$pdo->prepare("INSERT INTO email_logs (id,destinatario,assunto,tipo,referencia_tipo,referencia_id,status,mensagem_erro,enviado_por) VALUES (:id,:dest,:assunto,'assinatura',:ref,:refid,:status,:erro,:usuario)");
    $stmt->execute([':id'=>gerarUUID(),':dest'=>$destinatario,':assunto'=>'Documento aguardando assinatura - '.$tipo,':ref'=>$tipo,':refid'=>$id,':status'=>$sucesso?'enviado':'erro',':erro'=>$sucesso?null:substr($erro,0,1000),':usuario'=>$_SESSION['usuario_id']??null]);
}

function assinaturaCriarConviteCertificado(PDO $pdo,string $tipo,string $id,int $responsavelId): array
{
    $tipo=strtoupper($tipo);if(!isset(assinaturaCertificadosMapas()[$tipo]))throw new InvalidArgumentException('Tipo de certificado invalido.');
    $stmt=$pdo->prepare("SELECT ra.usuario_id,ra.email FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id WHERE ra.id=:id AND ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL");
    $stmt->execute([':id'=>$responsavelId]);$resp=$stmt->fetch(PDO::FETCH_ASSOC);if(!$resp||!filter_var($resp['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Responsavel sem usuario ativo ou e-mail valido.');
    $pdo->prepare("UPDATE assinatura_convites SET status='CANCELADO',cancelado_em=NOW(),cancelado_por=:usuario WHERE documento_tipo=:tipo AND documento_id=:doc AND status IN ('ATIVO','PROCESSANDO')")->execute([':usuario'=>$_SESSION['usuario_id']??null,':tipo'=>$tipo,':doc'=>$id]);
    $token=bin2hex(random_bytes(32));$conviteId=gerarUUID();$expira=(new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))->modify('+7 days');
    $ins=$pdo->prepare("INSERT INTO assinatura_convites (id,documento_tipo,documento_id,responsavel_id,usuario_id,token_hash,email_destinatario,status,expira_em) VALUES (:id,:tipo,:doc,:resp,:usuario,:hash,:email,'ATIVO',:expira)");
    $ins->execute([':id'=>$conviteId,':tipo'=>$tipo,':doc'=>$id,':resp'=>$responsavelId,':usuario'=>$resp['usuario_id'],':hash'=>hash('sha256',$token),':email'=>$resp['email'],':expira'=>$expira->format('Y-m-d H:i:s')]);
    return ['id'=>$conviteId,'token'=>$token,'link'=>APP_URL.'assinatura-certificado/'.$token,'expira_em'=>$expira->format('Y-m-d H:i:s')];
}

function assinaturaConvitePorToken(PDO $pdo,string $token,bool $bloquear=false): ?array
{
    if(!preg_match('/^[a-f0-9]{64}$/i',$token))return null;
    $sql="SELECT ac.*,ra.nome_completo,ra.cargo_titulo,ra.registro_profissional,ra.assinatura_arquivo,ra.assinatura_hash,u.nome usuario_nome,u.ativo usuario_ativo,u.excluido_em FROM assinatura_convites ac JOIN responsaveis_assinatura ra ON ra.id=ac.responsavel_id JOIN usuarios u ON u.id=ac.usuario_id WHERE ac.token_hash=:hash LIMIT 1".($bloquear?' FOR UPDATE':'');
    $stmt=$pdo->prepare($sql);$stmt->execute([':hash'=>hash('sha256',$token)]);$convite=$stmt->fetch(PDO::FETCH_ASSOC);if(!$convite)return null;
    $mapa=assinaturaCertificadosMapas()[$convite['documento_tipo']]??null;if(!$mapa)return null;
    $docStmt=$pdo->prepare("SELECT c.*,c.{$mapa['number']} numero FROM {$mapa['table']} c WHERE c.id=:id LIMIT 1".($bloquear?' FOR UPDATE':''));$docStmt->execute([':id'=>$convite['documento_id']]);$doc=$docStmt->fetch(PDO::FETCH_ASSOC);if(!$doc)return null;
    return array_merge($convite,['documento'=>$doc,'mapa'=>$mapa]);
}

function assinaturaConviteDisponivel(array $convite): bool
{
    $doc=$convite['documento']??[];
    return ($convite['status']??'')==='ATIVO' && strtotime((string)$convite['expira_em'])>=time() && (int)($convite['usuario_ativo']??0)===1 && ($convite['excluido_em']??null)===null && (int)($doc['ativo']??0)===1 && (int)($doc['assinado']??0)===0 && ($doc['status']??'')==='emitido' && (int)($doc['responsavel_assinatura_id']??0)===(int)$convite['responsavel_id'];
}

function assinaturaEnviarConviteCertificado(PDO $pdo,string $tipo,string $id,?array $convite=null): array
{
    $tipo=strtoupper($tipo);$mapa=assinaturaCertificadosMapas()[$tipo]??null;if(!$mapa)throw new InvalidArgumentException('Tipo de certificado invalido.');
    $stmt=$pdo->prepare("SELECT c.*,ra.nome_completo,ra.email,ra.usuario_id,ra.assinatura_arquivo,ra.assinatura_hash,u.cargo usuario_cargo,u.ativo usuario_ativo,u.excluido_em
        FROM {$mapa['table']} c JOIN responsaveis_assinatura ra ON ra.id=c.responsavel_assinatura_id JOIN usuarios u ON u.id=ra.usuario_id WHERE c.id=:id AND c.ativo=1");
    $stmt->execute([':id'=>$id]);$doc=$stmt->fetch(PDO::FETCH_ASSOC);if(!$doc)throw new RuntimeException('Certificado ou responsavel nao encontrado.');
    assinaturaResponsavelValidoParaDocumento($pdo,(int)$doc['responsavel_assinatura_id'],(string)($doc['criado_por']??''));
    if((int)$doc['assinado']===1)throw new RuntimeException('Este certificado ja foi assinado.');
    if($convite===null){$pdo->beginTransaction();try{$convite=assinaturaCriarConviteCertificado($pdo,$tipo,$id,(int)$doc['responsavel_assinatura_id']);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
    $link=(string)$convite['link'];
    $template=file_get_contents(__DIR__.'/../templates/email/assinatura.html');
    $html=str_replace(
        ['{{NOME_CLIENTE}}','{{TIPO_CERTIFICADO}}','{{NUMERO_CERTIFICADO}}','{{EMBARCACAO_NOME}}','{{DATA_EMISSAO}}','{{DATA_VALIDADE}}','{{LINK_ASSINATURA}}','{{EMAIL_CONTATO}}','{{TELEFONE_CONTATO}}'],
        [h($doc['nome_completo']),h($tipo),h($doc[$mapa['number']]??''),h($doc['nome_embarcacao']??''),!empty($doc['data_emissao'])?date('d/m/Y',strtotime($doc['data_emissao'])):'—',!empty($doc['data_validade'])?date('d/m/Y',strtotime($doc['data_validade'])):'—',h($link),h(defined('EMAIL_CONTATO')?EMAIL_CONTATO:''),h(defined('TELEFONE_CONTATO')?TELEFONE_CONTATO:'')],
        $template?:''
    );
    $resultado=enviarEmail($doc['email'],$doc['nome_completo'],'Assinatura pendente - '.$tipo.' '.($doc[$mapa['number']]??''),$html);
    assinaturaRegistrarEmail($pdo,$doc['email'],$tipo,$id,(bool)$resultado['success'],(string)$resultado['message']);
    if(!empty($convite['id']))$pdo->prepare('UPDATE assinatura_convites SET enviado_em=IF(:ok=1,NOW(),enviado_em) WHERE id=:id')->execute([':ok'=>$resultado['success']?1:0,':id'=>$convite['id']]);
    return $resultado+['link'=>$link];
}

function assinaturaPendencias(PDO $pdo,string $usuarioId,string $cargo): array
{
    $itens=[];$resp=assinaturaResponsavelUsuario($pdo,$usuarioId,false);
    if($resp){foreach(assinaturaCertificadosMapas() as $tipo=>$m){$extra=$tipo==='LC'?',analise_id':'';$stmt=$pdo->prepare("SELECT id,{$m['number']} numero,nome_embarcacao,data_emissao,status,token_assinatura,criado_por{$extra} FROM {$m['table']} WHERE responsavel_assinatura_id=:resp AND ativo=1 AND assinado=0 AND status='emitido' ORDER BY criado_em DESC");$stmt->execute([':resp'=>$resp['id']]);foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){if($cargo==='ANALISTA'&&$r['criado_por']!==$usuarioId){$permitidaPorAnalise=false;if($tipo==='LC'&&!empty($r['analise_id'])){$q=$pdo->prepare('SELECT COUNT(*) FROM analises_planos WHERE id=:id AND analista_id=:usuario');$q->execute([':id'=>$r['analise_id'],':usuario'=>$usuarioId]);$permitidaPorAnalise=(int)$q->fetchColumn()===1;}if(!$permitidaPorAnalise)continue;}$r['tipo']=$tipo;$r['responsavel_id']=$resp['id'];$r['origem']=$m['list'];$itens[]=$r;}}}
    if(in_array($cargo,['ADMIN','VISTORIADOR'],true)&&$resp){$sql="SELECT v.id,v.numero,e.nome nome_embarcacao,v.data_vistoria data_emissao,v.status,a.vistoriador_id,v.criado_por FROM vistorias v JOIN embarcacoes e ON e.id=v.embarcacao_id LEFT JOIN agendamentos a ON a.id=v.agendamento_id WHERE v.status='AGUARDANDO_APROVACAO' AND COALESCE(v.assinatura_status,'PENDENTE')<>'ASSINADO'";$params=[];if($cargo==='VISTORIADOR'){$sql.=' AND a.vistoriador_id=:usuario';$params[':usuario']=$usuarioId;}$sql.=' ORDER BY v.atualizado_em DESC';$stmt=$pdo->prepare($sql);$stmt->execute($params);foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){$r['tipo']='RELATORIO';$r['responsavel_id']=$resp['id'];$r['origem']='vistorias/relatorio?vistoria_id='.urlencode($r['id']);$itens[]=$r;}}
    usort($itens,static fn($a,$b)=>strcmp((string)$b['data_emissao'],(string)$a['data_emissao']));return $itens;
}

function assinaturaAutorizarCertificado(PDO $pdo,string $tipo,string $id,string $usuarioId): array
{
    $mapa=assinaturaCertificadosMapas()[$tipo]??null;if(!$mapa)throw new InvalidArgumentException('Tipo invalido.');
    $stmt=$pdo->prepare("SELECT c.*,ra.usuario_id,u.cargo usuario_cargo FROM {$mapa['table']} c JOIN responsaveis_assinatura ra ON ra.id=c.responsavel_assinatura_id JOIN usuarios u ON u.id=ra.usuario_id WHERE c.id=:id AND c.ativo=1 FOR UPDATE");$stmt->execute([':id'=>$id]);$doc=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$doc||$doc['usuario_id']!==$usuarioId)throw new RuntimeException('Este documento nao esta atribuido ao seu perfil de assinatura.');
    if($doc['usuario_cargo']==='ANALISTA'&&$doc['criado_por']!==$usuarioId){$permitidaPorAnalise=false;if($tipo==='LC'&&!empty($doc['analise_id'])){$q=$pdo->prepare('SELECT COUNT(*) FROM analises_planos WHERE id=:id AND analista_id=:usuario');$q->execute([':id'=>$doc['analise_id'],':usuario'=>$usuarioId]);$permitidaPorAnalise=(int)$q->fetchColumn()===1;}if(!$permitidaPorAnalise)throw new RuntimeException('Analistas somente podem assinar documentos técnicos atribuídos a eles.');}
    if((int)$doc['assinado']===1||$doc['status']!=='emitido')throw new RuntimeException('Este certificado nao esta pendente de assinatura.');return $doc;
}

function assinaturaAssinarCertificado(PDO $pdo,array $input): array
{
    $tipo=strtoupper(trim((string)($input['documento_tipo']??'')));$id=trim((string)($input['documento_id']??''));$usuario=(string)($_SESSION['usuario_id']??'');
    if(!isset(assinaturaCertificadosMapas()[$tipo])||$id===''||$usuario==='')throw new InvalidArgumentException('Documento invalido.');
    $pdo->beginTransaction();try{$doc=assinaturaAutorizarCertificado($pdo,$tipo,$id,$usuario);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    $input['responsavel_id']=$doc['responsavel_assinatura_id'];return aprovarDocumentoEletronicamente($pdo,$input);
}

function assinaturaAssinarCertificadoPorConvite(PDO $pdo,string $token,array $input): array
{
    if(($input['aceite']??'')!=='1')throw new RuntimeException('Confirme que leu o documento e autoriza a assinatura.');
    $pdo->beginTransaction();
    try{
        $convite=assinaturaConvitePorToken($pdo,$token,true);if(!$convite||!assinaturaConviteDisponivel($convite))throw new RuntimeException('Este convite e invalido, expirou ou ja foi utilizado.');
        $upd=$pdo->prepare("UPDATE assinatura_convites SET status='PROCESSANDO' WHERE id=:id AND status='ATIVO'");$upd->execute([':id'=>$convite['id']]);if($upd->rowCount()!==1)throw new RuntimeException('Este convite ja esta sendo utilizado.');
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    try{
        $dados=$input;$dados['documento_tipo']=$convite['documento_tipo'];$dados['documento_id']=$convite['documento_id'];$dados['responsavel_id']=$convite['responsavel_id'];
        $resultado=aprovarDocumentoEletronicamente($pdo,$dados,['usuario_id'=>$convite['usuario_id'],'usuario_nome'=>$convite['usuario_nome'],'metodo'=>'EMAIL_MAGIC_LINK','convite_id'=>$convite['id']]);
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE assinatura_convites SET status='UTILIZADO',utilizado_em=NOW(),aprovacao_id=:aprovacao WHERE id=:id AND status='PROCESSANDO'")->execute([':aprovacao'=>$resultado['id'],':id'=>$convite['id']]);
        $pdo->prepare("UPDATE assinatura_convites SET status='CANCELADO',cancelado_em=NOW() WHERE documento_tipo=:tipo AND documento_id=:doc AND id<>:id AND status IN ('ATIVO','PROCESSANDO')")->execute([':tipo'=>$convite['documento_tipo'],':doc'=>$convite['documento_id'],':id'=>$convite['id']]);
        $pdo->commit();return $resultado;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();try{$pdo->prepare("UPDATE assinatura_convites SET status=IF(expira_em>NOW(),'ATIVO','CANCELADO'),cancelado_em=IF(expira_em<=NOW(),NOW(),cancelado_em) WHERE id=:id AND status='PROCESSANDO'")->execute([':id'=>$convite['id']]);}catch(Throwable $ignored){}throw $e;}
}

function assinaturaAssinarParecer(PDO $pdo,array $input): array
{
    throw new RuntimeException('Assine o parecer dentro do processo atribuído no módulo de Análise de Planos.');
}

function assinaturaAssinarRelatorio(PDO $pdo,array $input): array
{
    $id=trim((string)($input['documento_id']??''));$usuario=(string)($_SESSION['usuario_id']??'');$cargo=getCargo();
    $lat=filter_var($input['latitude']??null,FILTER_VALIDATE_FLOAT);$lng=filter_var($input['longitude']??null,FILTER_VALIDATE_FLOAT);$prec=filter_var($input['geo_precisao_m']??null,FILTER_VALIDATE_FLOAT);
    if($id===''||$usuario===''||$lat===false||$lng===false)throw new InvalidArgumentException('Relatorio e geolocalizacao sao obrigatorios.');
    $resp=assinaturaResponsavelUsuario($pdo,$usuario,true);if(!$resp)throw new RuntimeException('Seu usuario nao possui perfil de assinatura completo.');
    $stmt=$pdo->prepare("SELECT v.*,a.vistoriador_id,e.nome embarcacao_nome FROM vistorias v LEFT JOIN agendamentos a ON a.id=v.agendamento_id JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE v.id=:id");$stmt->execute([':id'=>$id]);$v=$stmt->fetch(PDO::FETCH_ASSOC);if(!$v||$v['status']!=='AGUARDANDO_APROVACAO')throw new RuntimeException('O relatorio nao esta aguardando assinatura.');
    if($cargo==='VISTORIADOR'&&$v['vistoriador_id']!==$usuario)throw new RuntimeException('Vistoriadores somente podem assinar os proprios relatorios.');if($cargo!=='ADMIN'&&$cargo!=='VISTORIADOR')throw new RuntimeException('Seu perfil nao pode assinar relatorios de vistoria.');
    $q=$pdo->prepare("SELECT COUNT(*) FROM documento_assinaturas WHERE documento_tipo='RELATORIO' AND documento_id=:id AND status='ASSINADO'");$q->execute([':id'=>$id]);if($q->fetchColumn())throw new RuntimeException('Este relatorio ja possui assinatura ativa.');
    $tz=new DateTimeZone('America/Sao_Paulo');$now=new DateTimeImmutable('now',$tz);$uuid=gerarUUID();$token=bin2hex(random_bytes(32));$year=$now->format('Y');$dirRel='storage/documentos_assinados/'.$year.'/relatorio/';$dirAbs=__DIR__.'/../'.$dirRel;if(!is_dir($dirAbs)&&!mkdir($dirAbs,0750,true)&&!is_dir($dirAbs))throw new RuntimeException('Falha ao preparar armazenamento.');$tmp=__DIR__.'/../tmp/pdfs/';if(!is_dir($tmp))mkdir($tmp,0750,true);
    $originalTmp=$tmp.$uuid.'_original.pdf';$finalTmp=$tmp.$uuid.'_assinado.pdf';$originalAbs=$dirAbs.$uuid.'_original.pdf';$finalAbs=$dirAbs.$uuid.'.pdf';
    $GLOBALS['APROVACAO_RESPONSAVEL_PDF']=$resp;$pdfLayout=aprovacaoDocumentoGerarOriginal('RELATORIO',$id,$originalTmp);unset($GLOBALS['APROVACAO_RESPONSAVEL_PDF']);$hashOriginal=hash_file('sha256',$originalTmp);$sigAbs=__DIR__.'/../'.ltrim($resp['assinatura_arquivo'],'/\\');
    $ctx=['documento_tipo'=>'RELATORIO','token_validacao'=>$token,'responsavel_nome'=>$resp['nome_completo'],'responsavel_cpf_cnpj'=>$resp['cpf_cnpj'],'responsavel_cargo'=>$resp['cargo_titulo'],'responsavel_registro'=>$resp['registro_profissional'],'aprovador_nome'=>$resp['usuario_nome'],'data_hora_formatada'=>$now->format('d/m/Y H:i:s').' (America/Sao_Paulo)','latitude'=>(string)$lat,'longitude'=>(string)$lng,'geo_precisao_m'=>$prec===false?null:(string)$prec,'ip'=>obterIpCliente(),'hash_pdf_original'=>$hashOriginal,'assinatura_caminho_absoluto'=>$sigAbs,'bloco_pagina'=>$pdfLayout['bloco_pagina']??null,'bloco_y'=>$pdfLayout['bloco_y']??null];
    aprovacaoPdfCriarComBloco($originalTmp,$finalTmp,$ctx);$hashFinal=hash_file('sha256',$finalTmp);rename($originalTmp,$originalAbs);rename($finalTmp,$finalAbs);
    $pdo->beginTransaction();try{$ver=$pdo->prepare("SELECT COALESCE(MAX(versao),0)+1 FROM documento_assinaturas WHERE documento_tipo='RELATORIO' AND documento_id=:id FOR UPDATE");$ver->execute([':id'=>$id]);$versao=(int)$ver->fetchColumn();$ins=$pdo->prepare("INSERT INTO documento_assinaturas (id,documento_tipo,documento_id,versao,responsavel_id,usuario_id,assinatura_arquivo,assinatura_hash,hash_pdf_original,hash_pdf_assinado,caminho_pdf_original,caminho_pdf_assinado,token_validacao,latitude,longitude,geo_precisao_m,ip,user_agent,status,assinado_em) VALUES (:id,'RELATORIO',:doc,:versao,:resp,:usuario,:arquivo,:hashassinatura,:original,:final,:caminhooriginal,:caminhofinal,:token,:lat,:lng,:prec,:ip,:ua,'ASSINADO',:data)");$ins->execute([':id'=>$uuid,':doc'=>$id,':versao'=>$versao,':resp'=>$resp['id'],':usuario'=>$usuario,':arquivo'=>$resp['assinatura_arquivo'],':hashassinatura'=>$resp['assinatura_hash'],':original'=>$hashOriginal,':final'=>$hashFinal,':caminhooriginal'=>$dirRel.$uuid.'_original.pdf',':caminhofinal'=>$dirRel.$uuid.'.pdf',':token'=>$token,':lat'=>$lat,':lng'=>$lng,':prec'=>$prec===false?null:$prec,':ip'=>obterIpCliente(),':ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,500),':data'=>$now->format('Y-m-d H:i:s')]);$pdo->prepare("UPDATE vistorias SET responsavel_assinatura_id=:resp,assinatura_status='ASSINADO',assinatura_em=:data WHERE id=:id")->execute([':resp'=>$resp['id'],':data'=>$now->format('Y-m-d H:i:s'),':id'=>$id]);if($cargo==='ADMIN'){$res=aprovacaoRelatorioResumoExigencias($pdo,$id);if((int)($res['pendentes_as']??0)===0){$pdo->prepare("UPDATE vistorias SET status=:status,aprovado_por=:usuario,data_aprovacao=:data WHERE id=:id")->execute([':status'=>$res['status_esperado'],':usuario'=>$usuario,':data'=>$now->format('Y-m-d H:i:s'),':id'=>$id]);if(($v['finalidade']??'VISTORIA')==='CUMPRIMENTO_EXIGENCIAS'){concluirRetornoDoRelatorio($pdo,$id);}if(!empty($v['agendamento_id'])){$pdo->prepare("UPDATE ordens_servico SET status='executado' WHERE agendamento_id=:agendamento AND status IN ('pendente','em_andamento')")->execute([':agendamento'=>$v['agendamento_id']]);$pdo->prepare("UPDATE agendamentos SET status='concluido' WHERE id=:agendamento")->execute([':agendamento'=>$v['agendamento_id']]);}}}$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();@unlink($originalAbs);@unlink($finalAbs);throw $e;}
    return ['token'=>$token,'validation_url'=>APP_URL.'validar-assinatura/'.$token,'admin_aprovou'=>$cargo==='ADMIN'&&((int)($res['pendentes_as']??0)===0)];
}
