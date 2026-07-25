<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
verificar_sessao();
$stmt=$pdo->prepare('SELECT * FROM notificacoes WHERE usuario_id=:usuario ORDER BY criado_em DESC LIMIT 100');
$stmt->execute([':usuario'=>$_SESSION['usuario_id']]);
$notificacoes=$stmt->fetchAll(PDO::FETCH_ASSOC);
$titulo_page='Notificações';
require_once __DIR__.'/../../includes/header.php';
?>
<div class="conteudo-principal"><section class="notification-card"><div class="tabela-header"><h3><i class="far fa-bell"></i> Minhas notificações</h3><form method="post" action="<?=APP_URL?>notificacoes/actions"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><button class="btn btn-secondary btn-sm">Marcar todas como lidas</button></form></div><?php if(!$notificacoes):?><p>Nenhuma notificação.</p><?php else:?><div class="notification-timeline"><?php foreach($notificacoes as $n):?><div style="<?=$n['lida_em']?'opacity:.65':''?>"><strong><?=h($n['titulo'])?></strong><span><?=formatarDataCompleta($n['criado_em'])?></span><p><?=h($n['mensagem'])?></p><?php if($n['url']):?><a href="<?=h(APP_URL.ltrim($n['url'],'/'))?>">Abrir</a><?php endif?></div><?php endforeach?></div><?php endif?></section></div>
<style>.notification-card{background:#fff;border:1px solid var(--cor-borda,#ddd);border-radius:12px;padding:20px}.notification-timeline>div{border-left:3px solid #2596be;padding:3px 0 18px 15px}.notification-timeline span{display:block;font-size:.85rem;opacity:.7}.notification-timeline p{margin:5px 0}</style>
<?php require_once __DIR__.'/../../includes/footer.php';?>
