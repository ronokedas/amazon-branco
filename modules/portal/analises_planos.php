<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
requireClienteSenhaDefinitiva();
$clienteId=(string)clientePortalId();
$embarcacaoIds=clientePortalEmbarcacaoIds($pdo,$clienteId);
$analises=[];
if($embarcacaoIds){
    $params=[];$in=clientePortalSqlIn($embarcacaoIds,'ap_emb_',$params);
    $sql="SELECT ap.id,ap.numero,ap.tipo_processo,ap.enquadramento,ap.status,ap.prazo_agendado_em,e.nome embarcacao_nome,u.nome analista_nome,
          (SELECT COALESCE(MAX(s.revisao),0) FROM analise_planos_submissoes s WHERE s.analise_id=ap.id) ultima_revisao
          FROM analises_planos ap JOIN embarcacoes e ON e.id=ap.embarcacao_id LEFT JOIN usuarios u ON u.id=ap.analista_id
          WHERE ap.embarcacao_id IN ({$in}) AND ap.status IN ('EM_ANALISE','AGUARDANDO_DOCUMENTOS')
          ORDER BY ap.atualizado_em DESC";
    $stmt=$pdo->prepare($sql);$stmt->execute($params);$analises=$stmt->fetchAll(PDO::FETCH_ASSOC);
}
$titulo_page='Enviar documentos da análise';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header"><div><h1>Análise de Planos</h1><p>Envie uma nova revisão sem substituir os arquivos já analisados.</p></div><a class="btn btn-secondary" href="<?=APP_URL?>portal/documentos">Meus documentos</a></section>
<section class="portal-panel">
 <?php if(!$analises):?><div class="portal-empty"><i class="fas fa-folder-open"></i><h2>Nenhuma análise recebendo documentos</h2><p>Quando o analista solicitar uma revisão, ela aparecerá aqui.</p></div><?php else:?>
 <?php foreach($analises as $a):?><article class="portal-analysis-card"><div><strong><?=h($a['numero'])?> · <?=h($a['embarcacao_nome'])?></strong><span><?=h($a['tipo_processo']?:'Processo em enquadramento')?> · <?=h($a['status'])?> · Analista: <?=h($a['analista_nome']?:'A definir')?></span></div><form method="post" enctype="multipart/form-data" action="<?=APP_URL?>portal/analises-planos/actions"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="analise_id" value="<?=h($a['id'])?>"><label>Descrição da revisão</label><input name="descricao" maxlength="500" required placeholder="Ex.: correção solicitada no parecer"><label>Categoria</label><select name="categoria"><?php foreach(analisePlanosCategoriasPadrao() as $cat):?><option><?=h($cat)?></option><?php endforeach?></select><label>Arquivos</label><input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx"><button class="btn btn-primary"><i class="fas fa-upload"></i> Enviar nova revisão</button></form></article><?php endforeach?>
 <?php endif?>
</section>
<style>.portal-analysis-card{display:grid;grid-template-columns:minmax(240px,.8fr) 2fr;gap:24px;padding:20px 0;border-bottom:1px solid #dce6e2}.portal-analysis-card>div{display:flex;flex-direction:column;gap:6px}.portal-analysis-card form{display:grid;grid-template-columns:1fr 220px;gap:10px}.portal-analysis-card form label{font-size:.8rem}.portal-analysis-card form input[type=file],.portal-analysis-card form button{grid-column:1/-1}@media(max-width:800px){.portal-analysis-card{grid-template-columns:1fr}.portal-analysis-card form{grid-template-columns:1fr}}</style>
<?php require_once __DIR__ . '/../../includes/portal_footer.php';?>
