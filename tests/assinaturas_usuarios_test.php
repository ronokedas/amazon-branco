<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/assinaturas_usuarios.php';

function assertAssinatura(bool $condicao,string $mensagem): void {if(!$condicao)throw new RuntimeException($mensagem);}

$colunas=$pdo->query("SELECT CONCAT(TABLE_NAME,'.',COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND ((TABLE_NAME='responsaveis_assinatura' AND COLUMN_NAME IN ('email','usuario_id')) OR (TABLE_NAME='vistorias' AND COLUMN_NAME IN ('assinatura_status','assinatura_em'))) ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
foreach(['responsaveis_assinatura.email','responsaveis_assinatura.usuario_id','vistorias.assinatura_em','vistorias.assinatura_status'] as $esperada)assertAssinatura(in_array($esperada,$colunas,true),'Coluna ausente: '.$esperada);
assertAssinatura((bool)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documento_assinaturas'")->fetchColumn(),'Tabela documento_assinaturas ausente.');
assertAssinatura((bool)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='assinatura_convites'")->fetchColumn(),'Tabela assinatura_convites ausente.');
$colunasConvite=$pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='assinatura_convites'")->fetchAll(PDO::FETCH_COLUMN);
foreach(['token_hash','expira_em','status','utilizado_em','autenticacao_metodo','aprovacao_id'] as $coluna)assertAssinatura(in_array($coluna,$colunasConvite,true),'Coluna de convite ausente: '.$coluna);
$candidato=null;
foreach(assinaturaCertificadosMapas() as $tipoConvite=>$mapaConvite){$q=$pdo->query("SELECT c.id,c.responsavel_assinatura_id FROM {$mapaConvite['table']} c JOIN responsaveis_assinatura ra ON ra.id=c.responsavel_assinatura_id JOIN usuarios u ON u.id=ra.usuario_id WHERE c.ativo=1 AND c.assinado=0 AND c.status='emitido' AND ra.ativo=1 AND ra.email IS NOT NULL AND ra.email<>'' AND u.ativo=1 AND u.excluido_em IS NULL LIMIT 1");$row=$q->fetch(PDO::FETCH_ASSOC);if($row){$candidato=[$tipoConvite,$row];break;}}
if($candidato){$pdo->beginTransaction();try{[$tipoConvite,$docConvite]=$candidato;$primeiro=assinaturaCriarConviteCertificado($pdo,$tipoConvite,$docConvite['id'],(int)$docConvite['responsavel_assinatura_id']);$segundo=assinaturaCriarConviteCertificado($pdo,$tipoConvite,$docConvite['id'],(int)$docConvite['responsavel_assinatura_id']);$s=$pdo->prepare('SELECT id,status,token_hash,expira_em FROM assinatura_convites WHERE id IN (?,?) ORDER BY criado_em,id');$s->execute([$primeiro['id'],$segundo['id']]);$convitesTeste=$s->fetchAll(PDO::FETCH_ASSOC);assertAssinatura(count($convitesTeste)===2,'Reenvio nao criou dois registros auditaveis.');$porId=array_column($convitesTeste,null,'id');assertAssinatura($porId[$primeiro['id']]['status']==='CANCELADO'&&$porId[$segundo['id']]['status']==='ATIVO','Reenvio nao invalidou o convite anterior.');assertAssinatura($porId[$segundo['id']]['token_hash']===hash('sha256',$segundo['token'])&&!str_contains($porId[$segundo['id']]['token_hash'],$segundo['token']),'Token nao foi armazenado exclusivamente como hash.');$dias=(strtotime($porId[$segundo['id']]['expira_em'])-time())/86400;assertAssinatura($dias>6.9&&$dias<=7.1,'Convite nao expira em sete dias.');$pdo->rollBack();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}

$servico=file_get_contents(__DIR__.'/../includes/assinaturas_usuarios.php');$wizard=file_get_contents(__DIR__.'/../modules/certificados/wizard_step2.php');$painel=file_get_contents(__DIR__.'/../modules/minhas_assinaturas/index.php');$actions=file_get_contents(__DIR__.'/../modules/minhas_assinaturas/actions.php');$router=file_get_contents(__DIR__.'/../index.php');$cadastro=file_get_contents(__DIR__.'/../modules/responsaveis_assinatura/actions.php');
foreach([$servico,$wizard,$painel,$actions,$router,$cadastro] as $codigo)assertAssinatura($codigo!==false,'Nao foi possivel ler arquivo do fluxo.');
assertAssinatura(str_contains($cadastro,'Este usuario ja possui um perfil de assinatura.'),'Cadastro nao bloqueia vinculo duplicado.');
assertAssinatura(str_contains($wizard,"assinaturaEnviarConviteCertificado(\$pdo, 'CSN'")&&str_contains($wizard,"assinaturaEnviarConviteCertificado(\$pdo, 'CNBL'")&&str_contains($wizard,"assinaturaEnviarConviteCertificado(\$pdo, 'CNARQ'"),'Wizard nao envia convites para os tres certificados.');
assertAssinatura(str_contains($servico,"\$doc['usuario_id']!==\$usuarioId")||str_contains($servico,"\$doc['usuario_id'] !== \$usuarioId"),'Assinatura de certificado nao confere usuario atribuido.');
assertAssinatura(str_contains($servico,"cargo==='VISTORIADOR'&&\$v['vistoriador_id']!==\$usuario"),'Vistoriador nao esta limitado ao proprio relatorio.');
assertAssinatura(str_contains($servico,'Assine o parecer dentro do processo atribuído'), 'Parecer do analista ainda pode contornar o fluxo próprio de assinatura.');
assertAssinatura(str_contains($router,"login_return_to")&&str_contains($router,"validar-assinatura/"),'Roteador nao preserva retorno autenticado ou validacao.');
assertAssinatura(str_contains($painel,'Permitir localização e assinar')&&str_contains($actions,'verificarCSRF'),'Painel ou endpoint nao exige confirmacao segura.');

$paginaPublica=file_get_contents(__DIR__.'/../modules/assinaturas_publicas/certificado.php');$confirmacaoPublica=file_get_contents(__DIR__.'/../modules/assinaturas_publicas/confirmar.php');$previewPublica=file_get_contents(__DIR__.'/../modules/assinaturas_publicas/preview.php');
assertAssinatura(str_contains($router,'assinatura-certificado/')&&$paginaPublica!==false&&$confirmacaoPublica!==false&&$previewPublica!==false,'Rotas publicas do convite nao foram criadas.');
assertAssinatura(str_contains($servico,"hash('sha256',\$token)")&&str_contains($servico,"modify('+7 days')"),'Convite nao usa token em hash com validade de sete dias.');
assertAssinatura(str_contains($paginaPublica,'Li o documento e autorizo')&&str_contains($paginaPublica,'Prévia completa do certificado'),'Pagina publica nao exige aceite ou nao mostra o PDF.');
assertAssinatura(str_contains($confirmacaoPublica,'verificarCSRF')&&str_contains($servico,"status='PROCESSANDO'"),'Confirmacao publica nao possui CSRF ou trava de concorrencia.');
assertAssinatura(str_contains($servico,'EMAIL_MAGIC_LINK'),'Auditoria nao registra autenticacao por link de e-mail.');

echo "OK: estrutura, vinculos, convites e guardas de assinatura estao presentes.\n";
