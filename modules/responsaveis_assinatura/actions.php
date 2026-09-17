<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/assinaturas_usuarios.php';

verificar_sessao();
exigirAcesso('responsaveis_assinatura');

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Token de seguranca invalido. Tente novamente.');redirecionar(APP_URL.'responsaveis_assinatura');}
    $action=$_POST['action']??'';$id=(int)($_POST['id']??0);$nome=trim($_POST['nome_completo']??'');$cpfCnpj=trim($_POST['cpf_cnpj']??'');
    $cargo=trim($_POST['cargo_titulo']??'');$registro=trim($_POST['registro_profissional']??'');$ativo=isset($_POST['ativo'])?(int)$_POST['ativo']:1;
    $email=trim($_POST['email']??'');$usuarioId=trim($_POST['usuario_id']??'');
    $digits=preg_replace('/\D+/','',$cpfCnpj);$validDoc=(strlen($digits)===11&&validarCPF($digits))||(strlen($digits)===14&&validarCNPJ($digits));
    $returnUrl=APP_URL.'responsaveis_assinatura/form'.($id?'?id='.$id:'');
    if($nome===''||$cargo===''||$cpfCnpj===''||$email===''||$usuarioId===''){setMensagem('error','Nome, CPF/CNPJ, cargo, e-mail e usuario vinculado sao obrigatorios.');redirecionar($returnUrl);}
    if(!$validDoc){setMensagem('error','Informe um CPF ou CNPJ valido.');redirecionar($returnUrl);}
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){setMensagem('error','Informe um e-mail valido para notificacoes.');redirecionar($returnUrl);}
    $stmtUsuario=$pdo->prepare("SELECT id FROM usuarios WHERE id=? AND ativo=1 AND excluido_em IS NULL AND cargo IN ('ADMIN','VISTORIADOR','ANALISTA')");$stmtUsuario->execute([$usuarioId]);
    if(!$stmtUsuario->fetchColumn()){setMensagem('error','Selecione um usuario ativo com perfil ADMIN, VISTORIADOR ou ANALISTA.');redirecionar($returnUrl);}
    $stmtDuplicado=$pdo->prepare('SELECT id FROM responsaveis_assinatura WHERE usuario_id=? AND id<>?');$stmtDuplicado->execute([$usuarioId,$id]);
    if($stmtDuplicado->fetchColumn()){setMensagem('error','Este usuario ja possui um perfil de assinatura.');redirecionar($returnUrl);}
    $image=[];
    try{
        if($action==='create'){
            $pdo->beginTransaction();$stmt=$pdo->prepare('INSERT INTO responsaveis_assinatura (nome_completo,cpf_cnpj,email,usuario_id,cargo_titulo,registro_profissional,ativo) VALUES (?,?,?,?,?,?,?)');$stmt->execute([$nome,$cpfCnpj,$email,$usuarioId,$cargo,$registro,$ativo]);
            $newId=(int)$pdo->lastInsertId();$image=salvarImagemAssinaturaResponsavel($_FILES['assinatura_imagem']??[],$newId);if(empty($image))throw new RuntimeException('A assinatura manuscrita e obrigatoria no cadastro.');
            $pdo->prepare('UPDATE responsaveis_assinatura SET assinatura_arquivo=?,assinatura_hash=?,assinatura_atualizada_em=NOW() WHERE id=?')->execute([$image['path'],$image['hash'],$newId]);$pdo->commit();
            setMensagem('success','Responsavel cadastrado com sucesso.');redirecionar(APP_URL.'responsaveis_assinatura');
        }
        if($action==='update'&&$id>0){
            $atual=$pdo->prepare('SELECT usuario_id,ativo FROM responsaveis_assinatura WHERE id=?');$atual->execute([$id]);$atual=$atual->fetch(PDO::FETCH_ASSOC);
            if(!$atual)throw new RuntimeException('Responsavel nao encontrado.');
            if(($atual['usuario_id']??'')!==$usuarioId || ($ativo===0 && (int)$atual['ativo']===1)){
                $pendentes=0;
                foreach(['certificados_csn','certificados_cnbl','certificados_cnarq'] as $tabela){$q=$pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE responsavel_assinatura_id=? AND ativo=1 AND assinado=0 AND status='emitido'");$q->execute([$id]);$pendentes+=(int)$q->fetchColumn();}
                $q=$pdo->prepare("SELECT COUNT(*) FROM vistorias WHERE responsavel_assinatura_id=? AND status='AGUARDANDO_APROVACAO'");$q->execute([$id]);$pendentes+=(int)$q->fetchColumn();
                $q=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_pareceres WHERE responsavel_assinatura_id=? AND status='AGUARDANDO_APROVACAO'");$q->execute([$id]);$pendentes+=(int)$q->fetchColumn();
                if($pendentes>0)throw new RuntimeException('Reatribua os documentos pendentes antes de trocar o usuario ou inativar este responsavel.');
            }
            $image=salvarImagemAssinaturaResponsavel($_FILES['assinatura_imagem']??[],$id);
            $stmt=$pdo->prepare('UPDATE responsaveis_assinatura SET nome_completo=?,cpf_cnpj=?,email=?,usuario_id=?,cargo_titulo=?,registro_profissional=?,ativo=?,assinatura_arquivo=COALESCE(?,assinatura_arquivo),assinatura_hash=COALESCE(?,assinatura_hash),assinatura_atualizada_em=IF(? IS NULL,assinatura_atualizada_em,NOW()) WHERE id=?');
            $stmt->execute([$nome,$cpfCnpj,$email,$usuarioId,$cargo,$registro,$ativo,$image['path']??null,$image['hash']??null,$image['path']??null,$id]);setMensagem('success','Responsavel atualizado com sucesso.');redirecionar(APP_URL.'responsaveis_assinatura');
        }
        throw new RuntimeException('Acao invalida.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if(!empty($image['path'])){$created=__DIR__.'/../../'.ltrim(str_replace(['../','..\\'],'',$image['path']),'/\\');if(is_file($created))@unlink($created);}error_log('Erro ao salvar responsavel de assinatura: '.$e->getMessage());setMensagem('error',$e instanceof RuntimeException?$e->getMessage():'Erro ao salvar responsavel de assinatura.');redirecionar($returnUrl);}
}
redirecionar(APP_URL.'responsaveis_assinatura');
