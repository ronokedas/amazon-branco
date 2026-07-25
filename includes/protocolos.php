<?php

function protocoloExigirAcesso(): void
{
    exigirAcesso('protocolos_documentais');
}

function protocoloUsuarioPodeAcessar(PDO $pdo, array $dossie): bool
{
    if (getCargo() === 'ADMIN') return true;
    if (!podeAcessar('protocolos_documentais')) return false;
    $usuario = (string)($_SESSION['usuario_id'] ?? '');
    if ($usuario === '') return false;
    if (($dossie['criado_por'] ?? '') === $usuario) return true;
    if (!empty($dossie['proposta_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM propostas WHERE id=:id AND criado_por=:usuario');
        $q->execute([':id'=>$dossie['proposta_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    if (!empty($dossie['analise_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM analises_planos WHERE id=:id AND analista_id=:usuario');
        $q->execute([':id'=>$dossie['analise_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    if (!empty($dossie['vistoria_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM vistorias v INNER JOIN agendamentos a ON a.id=v.agendamento_id WHERE v.id=:id AND a.vistoriador_id=:usuario');
        $q->execute([':id'=>$dossie['vistoria_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    return false;
}

function protocoloCarregar(PDO $pdo, string $id, bool $lock=false): array
{
    $q=$pdo->prepare("SELECT d.*,e.nome embarcacao_nome,c.nome cliente_nome,u.nome criador_nome,
        um.nome unidade_nome,um.tipo unidade_tipo,um.url_consulta
        FROM protocolo_dossies d INNER JOIN embarcacoes e ON e.id=d.embarcacao_id
        LEFT JOIN clientes c ON c.id=d.cliente_id LEFT JOIN usuarios u ON u.id=d.criado_por
        LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id
        WHERE d.id=:id".($lock?' FOR UPDATE':''));
    $q->execute([':id'=>$id]);$d=$q->fetch(PDO::FETCH_ASSOC);
    if(!$d)throw new RuntimeException('Dossiê de protocolo não encontrado.');
    if(!protocoloUsuarioPodeAcessar($pdo,$d))throw new RuntimeException('Você não possui acesso a este protocolo.');
    return $d;
}

function protocoloAuditar(PDO $pdo,string $dossieId,?string $movId,string $evento,?string $anterior,?string $novo,string $detalhe='',?string $hash=null):void
{
    $q=$pdo->prepare('INSERT INTO protocolo_auditoria(dossie_id,movimentacao_id,evento,usuario_id,perfil,ip,estado_anterior,estado_novo,detalhe,hash_referencia)VALUES(:dossie,:mov,:evento,:usuario,:perfil,:ip,:anterior,:novo,:detalhe,:hash)');
    $q->execute([':dossie'=>$dossieId,':mov'=>$movId,':evento'=>$evento,':usuario'=>$_SESSION['usuario_id']??null,':perfil'=>getCargo(),':ip'=>obterIpCliente(),':anterior'=>$anterior,':novo'=>$novo,':detalhe'=>$detalhe?:null,':hash'=>$hash]);
}

function protocoloStatusPorNatureza(string $natureza,string $atual):string
{
    return match($natureza){
        'ENVIO_ORGAO','CUMPRIMENTO_EXIGENCIA'=>'ENVIADO_AO_ORGAO',
        'RETORNO_ORGAO'=>'EM_EXIGENCIA',
        'RETIRADA_ORGAO'=>'RETIRADO',
        'ENTREGA_CLIENTE'=>'ENTREGUE_AO_CLIENTE',
        default=>$atual,
    };
}

function protocoloRotulosStatus(): array
{
    return [
        'EM_PREPARACAO'=>'Em preparação','ENVIADO_AO_ORGAO'=>'Enviado ao órgão',
        'PROTOCOLADO'=>'Protocolado','EM_ANALISE_NO_ORGAO'=>'Em análise no órgão',
        'EM_EXIGENCIA'=>'Em exigência','A_DISPOSICAO'=>'Documento à disposição',
        'RETIRADO'=>'Retirado','ENTREGUE_AO_CLIENTE'=>'Entregue ao cliente',
        'ENCERRADO'=>'Encerrado','CANCELADO'=>'Cancelado',
    ];
}

function protocoloMascararDocumento(string $documento): string
{
    $valor=preg_replace('/\D+/','',$documento);
    if(strlen($valor)<5)return str_repeat('*',strlen($valor));
    return substr($valor,0,3).str_repeat('*',max(2,strlen($valor)-5)).substr($valor,-2);
}

function protocoloNotificarAdmins(PDO $pdo,string $evento,string $titulo,string $mensagem,string $dossieId):void
{
    $ids=$pdo->query("SELECT id FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND cargo='ADMIN'")->fetchAll(PDO::FETCH_COLUMN);
    $q=$pdo->prepare("INSERT INTO notificacoes(id,usuario_id,evento,titulo,mensagem,referencia_tipo,referencia_id,url)VALUES(UUID(),:usuario,:evento,:titulo,:mensagem,'PROTOCOLO',:referencia,:url)");
    foreach($ids as $id)$q->execute([':usuario'=>$id,':evento'=>$evento,':titulo'=>$titulo,':mensagem'=>$mensagem,':referencia'=>$dossieId,':url'=>'protocolos/form?id='.urlencode($dossieId)]);
}

function protocoloProcessarAlertas(PDO $pdo): int
{
    if(getCargo()!=='ADMIN')return 0;
    try{$cfg=$pdo->query('SELECT chave,valor FROM protocolo_configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR);}catch(Throwable $e){return 0;}
    $semComp=max(1,(int)($cfg['dias_sem_comprovante']??3));$semOficial=max(1,(int)($cfg['dias_sem_protocolo_oficial']??3));$validade=max(1,(int)($cfg['dias_alerta_validade']??15));
    $sql="SELECT d.id,d.numero,d.status,d.protocolo_externo_validade,
      EXISTS(SELECT 1 FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status='CONFIRMADA' AND m.tipo='SAIDA' AND m.confirmado_em<DATE_SUB(NOW(),INTERVAL {$semComp} DAY) AND NOT EXISTS(SELECT 1 FROM protocolo_comprovantes c WHERE c.movimentacao_id=m.id AND c.tipo IN('RECIBO','COMPROVANTE_ENTREGA'))) sem_comprovante,
      EXISTS(SELECT 1 FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status='CONFIRMADA' AND m.natureza IN('ENVIO_ORGAO','CUMPRIMENTO_EXIGENCIA') AND m.confirmado_em<DATE_SUB(NOW(),INTERVAL {$semOficial} DAY)) AND d.protocolo_externo_numero IS NULL sem_oficial,
      EXISTS(SELECT 1 FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m ON m.id=i.movimentacao_id WHERE m.dossie_id=d.id AND m.status IN('CONFIRMADA','RETIFICADA') AND i.requer_devolucao=1 AND i.devolvido_em IS NULL) original_pendente
      FROM protocolo_dossies d WHERE d.status NOT IN('ENCERRADO','CANCELADO')";
    $rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);$criados=0;
    $admins=$pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $ins=$pdo->prepare("INSERT INTO notificacoes(id,usuario_id,evento,titulo,mensagem,referencia_tipo,referencia_id,url)
      SELECT UUID(),:usuario,:evento,:titulo,:mensagem,'PROTOCOLO',:ref,:url FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM notificacoes WHERE usuario_id=:usuario2 AND evento=:evento2 AND referencia_id=:ref2 AND lida_em IS NULL)");
    foreach($rows as $r){$eventos=[];
      if($r['sem_comprovante'])$eventos['PROTOCOLO_SEM_COMPROVANTE']='Saída sem comprovante de recebimento';
      if($r['sem_oficial'])$eventos['PROTOCOLO_SEM_NUMERO_OFICIAL']='Envio à Marinha ainda sem número oficial';
      if($r['status']==='EM_EXIGENCIA')$eventos['PROTOCOLO_EM_EXIGENCIA']='Processo documental em exigência';
      if($r['status']==='A_DISPOSICAO')$eventos['PROTOCOLO_A_DISPOSICAO']='Documento disponível para retirada';
      if($r['original_pendente'])$eventos['PROTOCOLO_ORIGINAL_PENDENTE']='Documento original ainda sob custódia';
      if($r['protocolo_externo_validade']&&strtotime($r['protocolo_externo_validade'])<=strtotime("+{$validade} days"))$eventos['PROTOCOLO_VALIDADE']='Validade do protocolo próxima';
      foreach($eventos as $evento=>$titulo)foreach($admins as $admin){$ins->execute([':usuario'=>$admin,':evento'=>$evento,':titulo'=>$titulo,':mensagem'=>$r['numero'].' requer acompanhamento.',':ref'=>$r['id'],':url'=>'protocolos/form?id='.$r['id'],':usuario2'=>$admin,':evento2'=>$evento,':ref2'=>$r['id']]);$criados+=$ins->rowCount();}
    }return $criados;
}

function protocoloValidarArquivo(array $arquivo):array
{
    if(($arquivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||empty($arquivo['tmp_name']))throw new RuntimeException('Selecione um comprovante válido.');
    $tam=(int)($arquivo['size']??0);if($tam<1||$tam>15*1024*1024)throw new RuntimeException('O comprovante deve ter no máximo 15 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $permitidos=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
    if(!isset($permitidos[$mime]))throw new RuntimeException('Envie comprovante em PDF, JPG ou PNG.');
    return ['mime'=>$mime,'ext'=>$permitidos[$mime],'tam'=>$tam,'hash'=>hash_file('sha256',$arquivo['tmp_name']),'nome'=>mb_substr(basename($arquivo['name']??'comprovante'),0,255)];
}

function protocoloGuardarArquivo(array $arquivo,array $meta,string $dossieId):string
{
    $rel='storage/protocolos/'.date('Y').'/'.$dossieId.'/'.bin2hex(random_bytes(16)).'.'.$meta['ext'];
    $abs=dirname(__DIR__).'/'.$rel;if(!is_dir(dirname($abs))&&!mkdir(dirname($abs),0750,true)&&!is_dir(dirname($abs)))throw new RuntimeException('Não foi possível preparar o armazenamento.');
    if(!move_uploaded_file($arquivo['tmp_name'],$abs))throw new RuntimeException('Não foi possível guardar o comprovante.');
    return $rel;
}

function protocoloSnapshot(PDO $pdo,string $movId):array
{
    $q=$pdo->prepare('SELECT * FROM protocolo_movimentacao_itens WHERE movimentacao_id=:id ORDER BY criado_em,id');
    $q->execute([':id'=>$movId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
