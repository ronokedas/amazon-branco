<?php $meta=$dashboard['meta']; $percent=min(100,max(0,(float)$meta['percentual'])); ?>
<?php
$meses = [1=>'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$mesAtual = $meses[(int)date('n')];
?>
<section class="goal-card<?= $meta['percentual']>=100?' is-complete':'' ?><?= $meta['mensagem']!==''?' has-message':'' ?>">
    <div class="goal-card__ring" style="--goal:<?= $percent ?>"><strong><?= number_format($meta['percentual'],1,',','.') ?>%</strong></div>
    <div class="goal-card__body"><span>Meta de <?= h($meta['escopo']) ?> em <?= $mesAtual ?></span><h2><?= $meta['percentual']>=100?'Meta alcançada!':'Estamos avançando juntos' ?></h2><div class="goal-card__bar"><i style="width:<?= $percent ?>%"></i></div>
    <?php if ($cargo==='ADMIN'): ?><p><b><?= formatarMoeda($meta['realizado']) ?></b> recebidos de <?= formatarMoeda($meta['valor']) ?></p><?php else: ?><p>Acompanhe o progresso da equipe neste mês.</p><?php endif; ?></div>
    <?php if($meta['mensagem']!==''): ?><div class="goal-card__message"><i class="fa-solid fa-bullhorn"></i><span><?= h($meta['mensagem']) ?></span></div><?php endif; ?>
</section>
