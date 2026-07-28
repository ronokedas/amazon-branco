<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/protocolos.php';
protocoloExigirAcesso();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Sessão expirada.');redirecionar(APP_URL.'protocolos');}
$acao=trim($_POST['action']??'');$id=trim($_POST['id']??$_POST['dossie_id']??'');
$voltar=fn(?string $x=null)=>APP_URL.($x?'protocolos/form?id='.urlencode($x):'protocolos');
try{
 if($acao==='criar'){
 $emb=trim($_POST['embarcacao_id']??'');$assunto=trim($_POST['assunto']??'');if(!$emb||!$assunto)throw new InvalidArgumentException('Informe embarcação e assunto.');
  $q=$pdo->prepare('SELECT COALESCE(cliente_id,proprietario_id) FROM embarcacoes WHERE id=:id');$q->execute([':id'=>$emb]);$cliente=$q->fetchColumn();if($cliente===false)throw new RuntimeException('Embarcação inválida.');
  $proposta=trim($_POST['proposta_id']??'')?:null;$analise=trim($_POST['analise_id']??'')?:null;$vistoria=trim($_POST['vistoria_id']??'')?:null;
  if(getCargo()!=='ADMIN'){
   $permitido=false;$uid=(string)$_SESSION['usuario_id'];
   if($proposta){$q=$pdo->prepare('SELECT 1 FROM propostas p JOIN propostas_embarcacoes pe ON pe.proposta_id=p.id WHERE p.id=:id AND pe.embarcacao_id=:emb AND p.criado_por=:u');$q->execute([':id'=>$proposta,':emb'=>$emb,':u'=>$uid]);$permitido=(bool)$q->fetchColumn();}
   if(!$permitido&&$analise){$q=$pdo->prepare('SELECT 1 FROM analises_planos WHERE id=:id AND embarcacao_id=:emb AND (analista_id=:u OR vendedor_origem_id=:u)');$q->execute([':id'=>$analise,':emb'=>$emb,':u'=>$uid]);$permitido=(bool)$q->fetchColumn();}
   if(!$permitido&&$vistoria){$q=$pdo->prepare('SELECT 1 FROM vistorias v JOIN agendamentos a ON a.id=v.agendamento_id WHERE v.id=:id AND v.embarcacao_id=:emb AND (a.vistoriador_id=:u OR a.vendedor_id=:u)');$q->execute([':id'=>$vistoria,':emb'=>$emb,':u'=>$uid]);$permitido=(bool)$q->fetchColumn();}
   if(!$permitido)throw new RuntimeException('Vincule um processo ao qual você já possui acesso.');
  }
  $pdo->beginTransaction();$numero=gerarNumeroDocumento('PROTOCOLO','AM-PROT');$id=gerarUUID();
  $q=$pdo->prepare("INSERT INTO protocolo_dossies(id,numero,embarcacao_id,cliente_id,assunto,servico_id,proposta_id,analise_id,vistoria_id,certificado_tipo,certificado_id,criado_por)VALUES(:id,:numero,:emb,:cliente,:assunto,:servico,:proposta,:analise,:vistoria,:ctipo,:cid,:usuario)");
  $q->execute([':id'=>$id,':numero'=>$numero,':emb'=>$emb,':cliente'=>$cliente?:null,':assunto'=>$assunto,':servico'=>trim($_POST['servico_id']??'')?:null,':proposta'=>$proposta,':analise'=>$analise,':vistoria'=>$vistoria,':ctipo'=>trim($_POST['certificado_tipo']??'')?:null,':cid'=>trim($_POST['certificado_id']??'')?:null,':usuario'=>$_SESSION['usuario_id']]);
  protocoloAuditar($pdo,$id,null,'DOSSIE_CRIADO',null,'EM_PREPARACAO',$numero);$pdo->commit();setMensagem('success','Dossiê '.$numero.' criado.');redirecionar($voltar($id));
 }
 if($id==='')throw new RuntimeException('Dossiê não informado.');$d=protocoloCarregar($pdo,$id,in_array($acao,['adicionar_movimentacao','confirmar','registro_orgao','encerrar','cancelar'],true));
 if(in_array($d['status'],['ENCERRADO','CANCELADO'],true)&&!in_array($acao,['criar_aceite'],true))throw new RuntimeException('Dossiê encerrado ou cancelado é somente leitura.');
 if($acao==='adicionar_movimentacao'){
  $tipo=trim($_POST['tipo']??'');$nat=trim($_POST['natureza']??'');$tipos=['ENTRADA','SAIDA'];$nats=['RECEBIMENTO_CLIENTE','ENVIO_ORGAO','RETORNO_ORGAO','CUMPRIMENTO_EXIGENCIA','RETIRADA_ORGAO','ENTREGA_CLIENTE','TRANSFERENCIA_INTERNA','OUTRA'];
  if(!in_array($tipo,$tipos,true)||!in_array($nat,$nats,true))throw new InvalidArgumentException('Tipo ou natureza inválidos.');
  $origemTipo=trim($_POST['origem_tipo']??'');$destinoTipo=trim($_POST['destino_tipo']??'');$partes=['CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO'];
  if(!in_array($origemTipo,$partes,true)||!in_array($destinoTipo,$partes,true))throw new InvalidArgumentException('Origem ou destino inválidos.');
  $cidade=trim($_POST['cidade']??'');$uf=strtoupper(trim($_POST['uf']??''));$origem=trim($_POST['origem_nome']??'');$destino=trim($_POST['destino_nome']??'');if(!$cidade||!preg_match('/^[A-Z]{2}$/',$uf)||!$origem||!$destino)throw new InvalidArgumentException('Informe origem, destino, cidade e UF.');
  $meio=trim($_POST['meio_envio']??'PRESENCIAL');if(!in_array($meio,['PRESENCIAL','EMAIL','PORTAL','CORREIOS','TRANSPORTADORA','MENSAGEIRO','OUTRO'],true))throw new InvalidArgumentException('Meio de envio inválido.');
  $unidade=trim($_POST['unidade_maritima_id']??'')?:null;if(in_array($destinoTipo,['CAPITANIA','DELEGACIA','AGENCIA'],true)&&!$unidade)throw new InvalidArgumentException('Selecione a unidade marítima destinatária.');
  $key=trim($_POST['idempotency_key']??'');if($key==='')throw new InvalidArgumentException('Identificador da operação ausente.');
  $pdo->beginTransaction();$q=$pdo->prepare('SELECT id FROM protocolo_dossies WHERE id=:id FOR UPDATE');$q->execute([':id'=>$id]);if(!$q->fetchColumn())throw new RuntimeException('Dossiê não encontrado.');$q=$pdo->prepare('SELECT COALESCE(MAX(sequencia),0)+1 FROM protocolo_movimentacoes WHERE dossie_id=:id');$q->execute([':id'=>$id]);$seq=(int)$q->fetchColumn();$mov=gerarUUID();
  $q=$pdo->prepare("INSERT INTO protocolo_movimentacoes(id,dossie_id,sequencia,tipo,natureza,origem_tipo,origem_nome,destino_tipo,destino_nome,unidade_maritima_id,cidade,uf,meio_envio,portador_nome,codigo_rastreio,movimentado_em,observacoes,retifica_movimentacao_id,protocolo_anterior_id,idempotency_key,criado_por)VALUES(:id,:dossie,:seq,:tipo,:nat,:ot,:onome,:dt,:dnome,:unidade,:cidade,:uf,:meio,:portador,:rastreio,:data,:obs,:retifica,:anterior,:chave,:usuario)");
  $q->execute([':id'=>$mov,':dossie'=>$id,':seq'=>$seq,':tipo'=>$tipo,':nat'=>$nat,':ot'=>$origemTipo,':onome'=>$origem,':dt'=>$destinoTipo,':dnome'=>$destino,':unidade'=>$unidade,':cidade'=>$cidade,':uf'=>$uf,':meio'=>$meio,':portador'=>trim($_POST['portador_nome']??'')?:null,':rastreio'=>trim($_POST['codigo_rastreio']??'')?:null,':data'=>str_replace('T',' ',trim($_POST['movimentado_em']??''))?:date('Y-m-d H:i:s'),':obs'=>trim($_POST['observacoes']??'')?:null,':retifica'=>trim($_POST['retifica_movimentacao_id']??'')?:null,':anterior'=>trim($_POST['protocolo_anterior_id']??'')?:null,':chave'=>$key,':usuario'=>$_SESSION['usuario_id']]);
  $cats=$_POST['item_catalogo_id']??[];$desc=$_POST['item_descricao']??[];$ins=$pdo->prepare('INSERT INTO protocolo_movimentacao_itens(id,movimentacao_id,catalogo_id,descricao,categoria,suporte,forma,quantidade,numero_revisao,data_documento,condicao_documento,requer_devolucao,arquivo_origem_tipo,arquivo_origem_id,arquivo_nome,arquivo_hash,observacao)VALUES(UUID(),:mov,:catalogo,:descricao,:categoria,:suporte,:forma,:qtd,:rev,:data,:condicao,:devolve,:atipo,:aid,:anome,:hash,:obs)');
  foreach($desc as $i=>$descricao){$descricao=trim($descricao);if($descricao==='')continue;$cat=null;$forma=trim($_POST['item_forma'][$i]??'ORIGINAL');if(!in_array($forma,['ORIGINAL','COPIA_SIMPLES','COPIA_AUTENTICADA','NATO_DIGITAL','DIGITALIZADO'],true))throw new InvalidArgumentException('Forma documental inválida.');$catalogo=trim($cats[$i]??'')?:null;if($catalogo){$qc=$pdo->prepare('SELECT categoria,nome FROM protocolo_catalogo_documentos WHERE id=:id AND ativo=1');$qc->execute([':id'=>$catalogo]);$cat=$qc->fetch(PDO::FETCH_ASSOC);if(!$cat)throw new InvalidArgumentException('Documento de catálogo inválido.');}elseif(mb_strtolower($descricao)==='outro documento')throw new InvalidArgumentException('Identifique o outro documento apresentado.');$ins->execute([':mov'=>$mov,':catalogo'=>$catalogo,':descricao'=>$descricao,':categoria'=>$cat['categoria']??trim($_POST['item_categoria'][$i]??'OUTROS'),':suporte'=>($_POST['item_suporte'][$i]??'FISICO')==='DIGITAL'?'DIGITAL':'FISICO',':forma'=>$forma,':qtd'=>max(1,(int)($_POST['item_quantidade'][$i]??1)),':rev'=>trim($_POST['item_revisao'][$i]??'')?:null,':data'=>trim($_POST['item_data'][$i]??'')?:null,':condicao'=>trim($_POST['item_condicao'][$i]??'')?:null,':devolve'=>!empty($_POST['item_devolucao'][$i])?1:0,':atipo'=>trim($_POST['item_arquivo_tipo'][$i]??'')?:null,':aid'=>trim($_POST['item_arquivo_id'][$i]??'')?:null,':anome'=>trim($_POST['item_arquivo_nome'][$i]??'')?:null,':hash'=>trim($_POST['item_arquivo_hash'][$i]??'')?:null,':obs'=>trim($_POST['item_observacao'][$i]??'')?:null]);}
  $q=$pdo->prepare('SELECT COUNT(*) FROM protocolo_movimentacao_itens WHERE movimentacao_id=:id');$q->execute([':id'=>$mov]);if(!(int)$q->fetchColumn())throw new RuntimeException('Adicione ao menos um documento à movimentação.');
  protocoloAuditar($pdo,$id,$mov,'MOVIMENTACAO_RASCUNHO',null,'RASCUNHO','Evento '.str_pad((string)$seq,2,'0',STR_PAD_LEFT));$pdo->commit();setMensagem('success','Movimentação criada. Confira e confirme para congelar os dados.');redirecionar($voltar($id).'#mov-'.$mov);
 }
 if($acao==='confirmar'){
  $mov=trim($_POST['movimentacao_id']??'');$pdo->beginTransaction();$q=$pdo->prepare('SELECT id FROM protocolo_dossies WHERE id=:id FOR UPDATE');$q->execute([':id'=>$id]);if(!$q->fetchColumn())throw new RuntimeException('Dossiê não encontrado.');$q=$pdo->prepare("SELECT * FROM protocolo_movimentacoes WHERE id=:mov AND dossie_id=:dossie AND status='RASCUNHO' FOR UPDATE");$q->execute([':mov'=>$mov,':dossie'=>$id]);$m=$q->fetch(PDO::FETCH_ASSOC);if(!$m)throw new RuntimeException('Movimentação não está disponível para confirmação.');
  if(in_array($m['destino_tipo'],['CAPITANIA','DELEGACIA','AGENCIA'],true)&&(!$m['unidade_maritima_id']||!$m['cidade']))throw new RuntimeException('Envio à Marinha exige unidade e cidade.');
  $snapshot=protocoloSnapshot($pdo,$mov);if(!$snapshot)throw new RuntimeException('A movimentação não possui documentos.');
  $rel='storage/protocolos/'.date('Y').'/'.$id.'/evento-'.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT).'.pdf';$abs=__DIR__.'/../../'.$rel;if(!is_dir(dirname($abs)))mkdir(dirname($abs),0750,true);
  $salvar_pdf_caminho=$abs;$movimentacao_pdf_id=$mov;require __DIR__.'/pdf.php';if(!is_file($abs)||filesize($abs)<200)throw new RuntimeException('Falha ao gerar o PDF do protocolo.');$hash=hash_file('sha256',$abs);
  $novo=protocoloStatusPorNatureza($m['natureza'],$d['status']);$q=$pdo->prepare("UPDATE protocolo_movimentacoes SET status='CONFIRMADA',snapshot_json=:snapshot,pdf_caminho=:pdf,pdf_hash=:hash,confirmado_por=:usuario,confirmado_em=NOW() WHERE id=:id AND status='RASCUNHO'");$q->execute([':snapshot'=>json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),':pdf'=>$rel,':hash'=>$hash,':usuario'=>$_SESSION['usuario_id'],':id'=>$mov]);if($q->rowCount()!==1)throw new RuntimeException('A movimentação já foi confirmada.');
  if($m['retifica_movimentacao_id'])$pdo->prepare("UPDATE protocolo_movimentacoes SET status='RETIFICADA' WHERE id=:id AND dossie_id=:dossie AND status='CONFIRMADA'")->execute([':id'=>$m['retifica_movimentacao_id'],':dossie'=>$id]);
  $pdo->prepare('UPDATE protocolo_dossies SET status=:status,unidade_maritima_id=COALESCE(:unidade,unidade_maritima_id) WHERE id=:id')->execute([':status'=>$novo,':unidade'=>$m['unidade_maritima_id'],':id'=>$id]);protocoloAuditar($pdo,$id,$mov,'MOVIMENTACAO_CONFIRMADA',$d['status'],$novo,'Evento '.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT),$hash);$pdo->commit();protocoloNotificarAdmins($pdo,'PROTOCOLO_MOVIMENTADO','Protocolo movimentado',$d['numero'].' recebeu um evento '.$m['tipo'].'.',$id);setMensagem('success','Movimentação confirmada e PDF congelado.');redirecionar($voltar($id));
 }
 if($acao==='registro_orgao'){
  $data=trim($_POST['protocolo_externo_em']??'');$unidade=trim($_POST['unidade_maritima_id']??'');if(!$data||!$unidade)throw new InvalidArgumentException('Informe a unidade e a data do atendimento no órgão.');
  $q=$pdo->prepare('SELECT 1 FROM protocolo_unidades_maritimas WHERE id=:id AND ativo=1');$q->execute([':id'=>$unidade]);if(!$q->fetchColumn())throw new InvalidArgumentException('Unidade marítima inválida.');
  $pdo->beginTransaction();$pdo->prepare("UPDATE protocolo_dossies SET protocolo_externo_em=:data,protocolo_externo_validade=:validade,unidade_maritima_id=:unidade,status='PROTOCOLADO' WHERE id=:id")->execute([':data'=>str_replace('T',' ',$data),':validade'=>trim($_POST['validade']??'')?:null,':unidade'=>$unidade,':id'=>$id]);
  protocoloAuditar($pdo,$id,null,'REGISTRO_ORGAO',$d['status'],'PROTOCOLADO','Atendimento registrado em '.str_replace('T',' ',$data));$pdo->commit();setMensagem('success','Atendimento no órgão registrado.');redirecionar($voltar($id));
 }
 if($acao==='criar_aceite'){
  $mov=trim($_POST['movimentacao_id']??'');$q=$pdo->prepare("SELECT 1 FROM protocolo_movimentacoes WHERE id=:id AND dossie_id=:dossie AND status='CONFIRMADA'");$q->execute([':id'=>$mov,':dossie'=>$id]);if(!$q->fetchColumn())throw new RuntimeException('Movimentação confirmada não encontrada.');$token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
  $pdo->prepare("INSERT INTO protocolo_aceites(id,movimentacao_id,token_hash,expira_em,criado_por)VALUES(UUID(),:mov,:hash,DATE_ADD(NOW(),INTERVAL 15 DAY),:usuario) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expira_em=VALUES(expira_em),criado_por=VALUES(criado_por),nome=NULL,documento_mascarado=NULL,termo_aceito=0,ip=NULL,aceito_em=NULL")->execute([':mov'=>$mov,':hash'=>$hash,':usuario'=>$_SESSION['usuario_id']]);protocoloAuditar($pdo,$id,$mov,'ACEITE_CRIADO',null,'PENDENTE');setMensagem('success','Link de aceite: '.APP_URL.'protocolo-aceite/'.$token);redirecionar($voltar($id));
 }
 if($acao==='anexar_documentos'){
  $mov=trim($_POST['movimentacao_id']??'')?:null;
  if($mov){$q=$pdo->prepare("SELECT 1 FROM protocolo_movimentacoes WHERE id=:id AND dossie_id=:dossie AND status IN ('CONFIRMADA','RETIFICADA')");$q->execute([':id'=>$mov,':dossie'=>$id]);if(!$q->fetchColumn())throw new RuntimeException('Movimentação confirmada não encontrada.');}
  $arquivos=protocoloNormalizarArquivos($_FILES['documentos']??[]);$arquivos=array_values(array_filter($arquivos,fn($a)=>(int)($a['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE));
  if(!$arquivos)throw new InvalidArgumentException('Selecione ao menos um documento.');if(count($arquivos)>10)throw new InvalidArgumentException('Envie no máximo 10 documentos por vez.');
  $validados=[];foreach($arquivos as $arquivo)$validados[]=['arquivo'=>$arquivo,'meta'=>protocoloValidarArquivo($arquivo)];
  $novosCaminhos=[];$pdo->beginTransaction();
  try{
   $ins=$pdo->prepare("INSERT INTO protocolo_comprovantes(id,dossie_id,movimentacao_id,tipo,nome_original,mime_type,tamanho_bytes,sha256,caminho,criado_por)VALUES(UUID(),:dossie,:mov,'DOCUMENTO',:nome,:mime,:tam,:hash,:caminho,:usuario)");
   foreach($validados as $item){$caminho=protocoloGuardarArquivo($item['arquivo'],$item['meta'],$id);$novosCaminhos[]=$caminho;$ins->execute([':dossie'=>$id,':mov'=>$mov,':nome'=>$item['meta']['nome'],':mime'=>$item['meta']['mime'],':tam'=>$item['meta']['tam'],':hash'=>$item['meta']['hash'],':caminho'=>$caminho,':usuario'=>$_SESSION['usuario_id']]);protocoloAuditar($pdo,$id,$mov,'DOCUMENTO_ANEXADO',null,'DOCUMENTO',$item['meta']['nome'],$item['meta']['hash']);}
   $pdo->commit();
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();foreach($novosCaminhos as $rel){$abs=dirname(__DIR__,2).'/'.$rel;if(is_file($abs))@unlink($abs);}throw $e;}
  setMensagem('success',count($validados).' documento(s) anexado(s) e protegido(s) contra substituição.');redirecionar($voltar($id));
 }
 if($acao==='registrar_devolucao'){
  $item=trim($_POST['item_id']??'');$q=$pdo->prepare("SELECT i.id,m.id movimentacao_id FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m ON m.id=i.movimentacao_id WHERE i.id=:item AND m.dossie_id=:dossie AND m.status IN('CONFIRMADA','RETIFICADA') AND i.requer_devolucao=1 AND i.devolvido_em IS NULL");$q->execute([':item'=>$item,':dossie'=>$id]);$it=$q->fetch(PDO::FETCH_ASSOC);if(!$it)throw new RuntimeException('Original pendente não encontrado.');
  $pdo->prepare('UPDATE protocolo_movimentacao_itens SET devolvido_em=NOW() WHERE id=:id AND devolvido_em IS NULL')->execute([':id'=>$item]);protocoloAuditar($pdo,$id,$it['movimentacao_id'],'ORIGINAL_DEVOLVIDO',null,'DEVOLVIDO','Baixa de custódia do original '.$item);setMensagem('success','Devolução do original registrada na custódia.');redirecionar($voltar($id));
 }
 if($acao==='andamento_orgao'){
  $novo=trim($_POST['novo_status']??'');$permitidos=['PROTOCOLADO','EM_ANALISE_NO_ORGAO','EM_EXIGENCIA','A_DISPOSICAO','RETIRADO'];if(!in_array($novo,$permitidos,true))throw new InvalidArgumentException('Andamento inválido.');
  $obs=trim($_POST['andamento_observacao']??'');if(!$obs)throw new InvalidArgumentException('Descreva a informação recebida do órgão.');
  $pdo->prepare('UPDATE protocolo_dossies SET status=:status WHERE id=:id')->execute([':status'=>$novo,':id'=>$id]);protocoloAuditar($pdo,$id,null,'ANDAMENTO_ORGAO',$d['status'],$novo,$obs);setMensagem('success','Andamento do órgão registrado na auditoria.');redirecionar($voltar($id));
 }
 if($acao==='encerrar'){$pdo->prepare("UPDATE protocolo_dossies SET status='ENCERRADO' WHERE id=:id")->execute([':id'=>$id]);protocoloAuditar($pdo,$id,null,'DOSSIE_ENCERRADO',$d['status'],'ENCERRADO');setMensagem('success','Dossiê encerrado.');redirecionar($voltar($id));}
 if($acao==='cancelar'){$motivo=trim($_POST['motivo']??'');if(!$motivo)throw new InvalidArgumentException('Informe o motivo do cancelamento.');$pdo->prepare("UPDATE protocolo_dossies SET status='CANCELADO',cancelado_motivo=:motivo,cancelado_por=:usuario,cancelado_em=NOW() WHERE id=:id")->execute([':motivo'=>$motivo,':usuario'=>$_SESSION['usuario_id'],':id'=>$id]);protocoloAuditar($pdo,$id,null,'DOSSIE_CANCELADO',$d['status'],'CANCELADO',$motivo);setMensagem('success','Dossiê cancelado sem apagar o histórico.');redirecionar($voltar($id));}
 throw new RuntimeException('Ação inválida.');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Erro em protocolos: '.$e->getMessage());setMensagem('error',$e->getMessage());redirecionar($voltar($id?:null));}
