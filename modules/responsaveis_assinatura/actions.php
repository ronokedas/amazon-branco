<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('responsaveis_assinatura');

function salvarImagemAssinaturaResponsavel(array $arquivo, int $responsavelId): array
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no upload da assinatura.');
    if (($arquivo['size'] ?? 0) < 1 || $arquivo['size'] > 2 * 1024 * 1024) throw new RuntimeException('A assinatura deve ter no maximo 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    if (!in_array($mime, ['image/png','image/jpeg'], true)) throw new RuntimeException('Envie a assinatura em PNG ou JPEG.');
    $bytes = file_get_contents($arquivo['tmp_name']);
    $source = $bytes !== false ? @imagecreatefromstring($bytes) : false;
    if (!$source) throw new RuntimeException('A imagem da assinatura esta corrompida ou e invalida.');
    $sourceW=imagesx($source);$sourceH=imagesy($source);
    if($sourceW<100||$sourceH<30){imagedestroy($source);throw new RuntimeException('A imagem da assinatura tem resolucao muito baixa.');}
    $canvasW=900;$canvasH=300;$margin=18;$target=imagecreatetruecolor($canvasW,$canvasH);
    imagealphablending($target,false);imagesavealpha($target,true);$transparent=imagecolorallocatealpha($target,255,255,255,127);imagefill($target,0,0,$transparent);
    $scale=min(($canvasW-2*$margin)/$sourceW,($canvasH-2*$margin)/$sourceH);$drawW=max(1,(int)round($sourceW*$scale));$drawH=max(1,(int)round($sourceH*$scale));
    imagealphablending($target,true);imagecopyresampled($target,$source,(int)(($canvasW-$drawW)/2),(int)(($canvasH-$drawH)/2),0,0,$drawW,$drawH,$sourceW,$sourceH);
    $relativeDir='storage/private/assinaturas_responsaveis/'.$responsavelId.'/';$absoluteDir=__DIR__.'/../../'.$relativeDir;
    if(!is_dir($absoluteDir)&&!mkdir($absoluteDir,0750,true)&&!is_dir($absoluteDir)){imagedestroy($source);imagedestroy($target);throw new RuntimeException('Nao foi possivel preparar o armazenamento da assinatura.');}
    $relative=$relativeDir.date('Ymd_His').'_'.bin2hex(random_bytes(8)).'.png';$absolute=__DIR__.'/../../'.$relative;
    if(!imagepng($target,$absolute,6)){imagedestroy($source);imagedestroy($target);throw new RuntimeException('Nao foi possivel salvar a assinatura.');}
    imagedestroy($source);imagedestroy($target);return ['path'=>$relative,'hash'=>hash_file('sha256',$absolute)];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Token de seguranca invalido. Tente novamente.');redirecionar(APP_URL.'responsaveis_assinatura');}
    $action=$_POST['action']??'';$id=(int)($_POST['id']??0);$nome=trim($_POST['nome_completo']??'');$cpfCnpj=trim($_POST['cpf_cnpj']??'');
    $cargo=trim($_POST['cargo_titulo']??'');$registro=trim($_POST['registro_profissional']??'');$ativo=isset($_POST['ativo'])?(int)$_POST['ativo']:1;
    $digits=preg_replace('/\D+/','',$cpfCnpj);$validDoc=(strlen($digits)===11&&validarCPF($digits))||(strlen($digits)===14&&validarCNPJ($digits));
    $returnUrl=APP_URL.'responsaveis_assinatura/form'.($id?'?id='.$id:'');
    if($nome===''||$cargo===''||$cpfCnpj===''){setMensagem('error','Nome completo, CPF/CNPJ e cargo/titulo sao obrigatorios.');redirecionar($returnUrl);}
    if(!$validDoc){setMensagem('error','Informe um CPF ou CNPJ valido.');redirecionar($returnUrl);}
    $image=[];
    try{
        if($action==='create'){
            $pdo->beginTransaction();$stmt=$pdo->prepare('INSERT INTO responsaveis_assinatura (nome_completo,cpf_cnpj,cargo_titulo,registro_profissional,ativo) VALUES (?,?,?,?,?)');$stmt->execute([$nome,$cpfCnpj,$cargo,$registro,$ativo]);
            $newId=(int)$pdo->lastInsertId();$image=salvarImagemAssinaturaResponsavel($_FILES['assinatura_imagem']??[],$newId);if(empty($image))throw new RuntimeException('A assinatura manuscrita e obrigatoria no cadastro.');
            $pdo->prepare('UPDATE responsaveis_assinatura SET assinatura_arquivo=?,assinatura_hash=?,assinatura_atualizada_em=NOW() WHERE id=?')->execute([$image['path'],$image['hash'],$newId]);$pdo->commit();
            setMensagem('success','Responsavel cadastrado com sucesso.');redirecionar(APP_URL.'responsaveis_assinatura');
        }
        if($action==='update'&&$id>0){
            $image=salvarImagemAssinaturaResponsavel($_FILES['assinatura_imagem']??[],$id);
            $stmt=$pdo->prepare('UPDATE responsaveis_assinatura SET nome_completo=?,cpf_cnpj=?,cargo_titulo=?,registro_profissional=?,ativo=?,assinatura_arquivo=COALESCE(?,assinatura_arquivo),assinatura_hash=COALESCE(?,assinatura_hash),assinatura_atualizada_em=IF(? IS NULL,assinatura_atualizada_em,NOW()) WHERE id=?');
            $stmt->execute([$nome,$cpfCnpj,$cargo,$registro,$ativo,$image['path']??null,$image['hash']??null,$image['path']??null,$id]);setMensagem('success','Responsavel atualizado com sucesso.');redirecionar(APP_URL.'responsaveis_assinatura');
        }
        throw new RuntimeException('Acao invalida.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if(!empty($image['path'])){$created=__DIR__.'/../../'.ltrim(str_replace(['../','..\\'],'',$image['path']),'/\\');if(is_file($created))@unlink($created);}error_log('Erro ao salvar responsavel de assinatura: '.$e->getMessage());setMensagem('error',$e instanceof RuntimeException?$e->getMessage():'Erro ao salvar responsavel de assinatura.');redirecionar($returnUrl);}
}
redirecionar(APP_URL.'responsaveis_assinatura');
