<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/exportacoes_documentos.php';
verificar_sessao(); exigirAcesso('configuracoes');
$clientes=$pdo->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll();
$embarcacoes=$pdo->query("SELECT id,nome,registro FROM embarcacoes ORDER BY nome")->fetchAll();
$jobs=$pdo->query("SELECT ex.*,u.nome solicitante,(ex.status='CONCLUIDA' AND ex.expira_em>NOW()) disponivel FROM exportacoes_documentos ex INNER JOIN usuarios u ON u.id=ex.solicitado_por ORDER BY solicitado_em DESC LIMIT 30")->fetchAll();
$temProcessando=(bool)array_filter($jobs,fn($job)=>in_array($job['status'],['AGUARDANDO','PROCESSANDO'],true));
$titulo_page='Exportação de documentos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="conteudo-principal">
  <div class="welcome-section"><div><h1><i class="fas fa-file-archive"></i> Exportação de documentos</h1><p>Gere pacotes organizados dos documentos produzidos pelo sistema. O backup SQL continua separado.</p></div></div>
  <div class="card" style="margin-bottom:22px"><div class="card-body">
    <form method="post" action="<?=APP_URL?>configuracoes/exportacoes_actions" class="form-padrao" id="formExportacao">
      <input type="hidden" name="csrf_token" value="<?=h(gerarCSRF())?>"><input type="hidden" name="action" value="criar">
      <h3>Categorias</h3><label for="selecionarTudo" style="display:block;margin:10px 0"><input type="checkbox" id="selecionarTudo"> <strong>Selecionar tudo</strong></label>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin-bottom:20px">
        <?php foreach(exportacaoTipos() as $tipo=>$label): ?><label style="padding:12px;border:1px solid var(--cor-borda);border-radius:8px"><input class="categoria-exportacao" type="checkbox" name="categorias[]" value="<?=h($tipo)?>"> <?=h($label)?></label><?php endforeach?>
      </div>
      <h3>Filtros opcionais</h3><div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px">
        <div class="form-group"><label for="exportacao-data-inicio">Data inicial</label><input id="exportacao-data-inicio" class="form-control" type="date" name="data_inicio"></div>
        <div class="form-group"><label for="exportacao-data-fim">Data final</label><input id="exportacao-data-fim" class="form-control" type="date" name="data_fim"></div>
        <div class="form-group"><label for="exportacao-cliente">Cliente</label><select id="exportacao-cliente" class="form-control" name="cliente_id"><option value="">Todos</option><?php foreach($clientes as $c):?><option value="<?=h($c['id'])?>"><?=h($c['nome'])?></option><?php endforeach?></select></div>
        <div class="form-group"><label for="exportacao-embarcacao">Embarcação</label><select id="exportacao-embarcacao" class="form-control" name="embarcacao_id"><option value="">Todas</option><?php foreach($embarcacoes as $e):?><option value="<?=h($e['id'])?>"><?=h($e['nome'].' · '.$e['registro'])?></option><?php endforeach?></select></div>
      </div><button class="btn btn-primary" type="submit"><i class="fas fa-cogs"></i> Preparar ZIP</button>
    </form>
  </div>
  <div class="card"><div class="card-body"><h3>Exportações recentes</h3>
    <div class="table-responsive"><table class="table"><thead><tr><th>Solicitada</th><th>Categorias</th><th>Status</th><th>Arquivos</th><th>Tamanho</th><th>Ação</th></tr></thead><tbody>
    <?php foreach($jobs as $job):$cats=json_decode($job['categorias_json'],true)?:[];$disponivel=(bool)$job['disponivel'];$podeExcluir=in_array($job['status'],['EXPIRADA','FALHA'],true)||($job['status']==='CONCLUIDA'&&!$disponivel);?><tr><td><?=h(exportacaoDataHoraLocal($job['solicitado_em']))?><br><small><?=h($job['solicitante'])?></small></td><td><?=h(implode(', ',$cats))?></td><td><span class="badge"><?=h($job['status'])?></span><?php if($job['erro']):?><br><small class="text-danger"><?=h($job['erro'])?></small><?php endif?></td><td><?=number_format((int)$job['quantidade_arquivos'],0,',','.')?></td><td><?=$job['tamanho_bytes']?h(number_format($job['tamanho_bytes']/1048576,1,',','.')).' MB':'—'?></td><td><?php if($disponivel):?><a class="btn btn-success btn-sm export-download-button" href="<?=APP_URL?>configuracoes/exportacoes_download?id=<?=h($job['id'])?>"><i class="fas fa-download"></i> Baixar</a><?php elseif($podeExcluir):?><form method="post" action="<?=APP_URL?>configuracoes/exportacoes_actions" onsubmit="return confirm('Excluir este registro de exportação?')"><input type="hidden" name="csrf_token" value="<?=h(gerarCSRF())?>"><input type="hidden" name="action" value="excluir"><input type="hidden" name="id" value="<?=h($job['id'])?>"><button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Excluir</button></form><?php else:?>—<?php endif?></td></tr><?php endforeach?>
    <?php if(!$jobs):?><tr><td colspan="6" class="text-center">Nenhuma exportação solicitada.</td></tr><?php endif?></tbody></table></div>
  </div></div>
</div>
<script>document.getElementById('selecionarTudo').addEventListener('change',e=>document.querySelectorAll('.categoria-exportacao').forEach(i=>i.checked=e.target.checked));document.getElementById('formExportacao').addEventListener('submit',e=>{if(!document.querySelector('.categoria-exportacao:checked')){e.preventDefault();alert('Selecione ao menos uma categoria.')}});<?php if($temProcessando):?>setTimeout(()=>location.reload(),8000);<?php endif?></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
