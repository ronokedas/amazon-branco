<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
verificar_sessao();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Sessão expirada.');redirecionar(APP_URL.'notificacoes');}
$stmt=$pdo->prepare('UPDATE notificacoes SET lida_em=COALESCE(lida_em,NOW()) WHERE usuario_id=:usuario');
$stmt->execute([':usuario'=>$_SESSION['usuario_id']]);
setMensagem('success','Notificações marcadas como lidas.');
redirecionar(APP_URL.'notificacoes');
