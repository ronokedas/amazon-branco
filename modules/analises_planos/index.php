<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
analisePlanosExigirAcesso();

$busca = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');
$params = [];
$where = [];
$cargo = getCargo();
$usuarioId = (string)($_SESSION['usuario_id'] ?? '');
if ($cargo === 'ANALISTA') {
    $where[] = 'ap.analista_id=:usuario';
    $params[':usuario'] = $usuarioId;
} elseif ($cargo === 'VENDEDOR') {
    $where[] = 'ap.vendedor_origem_id=:usuario';
    $params[':usuario'] = $usuarioId;
}
if ($busca !== '') {
    $where[] = '(ap.numero LIKE :busca OR e.nome LIKE :busca OR ap.objeto LIKE :busca OR p.numero LIKE :busca)';
    $params[':busca'] = '%' . $busca . '%';
}
$statusLabels = [
    'AGUARDANDO_AGENDAMENTO' => 'Aguardando agendamento',
    'AGENDADA' => 'Agendada',
    'EM_ANALISE' => 'Em análise',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos',
    'AGUARDANDO_ASSINATURA_ANALISTA' => 'Aguardando assinatura do analista',
    'AGUARDANDO_APROVACAO_ADMIN' => 'Aguardando admin',
    'CONCLUIDA' => 'Concluída',
    'REPROVADA' => 'Reprovada',
    'CANCELADA' => 'Cancelada',
];
if (isset($statusLabels[$status])) {
    $where[] = 'ap.status=:status';
    $params[':status'] = $status;
}
$sql = "SELECT ap.*,e.nome embarcacao_nome,u.nome analista_nome,p.numero proposta_numero,
        (SELECT COUNT(*) FROM analise_planos_exigencias x WHERE x.analise_id=ap.id AND (x.status<>'CUMPRIDA' OR x.saneamento_pendente=1)) exigencias_pendentes
        FROM analises_planos ap
        INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
        LEFT JOIN usuarios u ON u.id=ap.analista_id
        LEFT JOIN propostas p ON p.id=ap.proposta_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY (ap.status="AGUARDANDO_AGENDAMENTO") DESC,ap.prazo_agendado_em IS NULL,ap.prazo_agendado_em,ap.atualizado_em DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$analises = $stmt->fetchAll(PDO::FETCH_ASSOC);

$metricas = ['aguardando'=>0,'hoje'=>0,'atrasadas'=>0,'documentos'=>0,'admin'=>0];
foreach ($analises as $item) {
    if ($item['status'] === 'AGUARDANDO_AGENDAMENTO') $metricas['aguardando']++;
    if ($item['status'] === 'AGUARDANDO_DOCUMENTOS') $metricas['documentos']++;
    if ($item['status'] === 'AGUARDANDO_APROVACAO_ADMIN') $metricas['admin']++;
    if (!empty($item['prazo_agendado_em']) && in_array($item['status'], analisePlanosStatusAtivos(), true)) {
        $data = substr($item['prazo_agendado_em'], 0, 10);
        if ($data === date('Y-m-d')) $metricas['hoje']++;
        elseif ($data < date('Y-m-d')) $metricas['atrasadas']++;
    }
}
$titulo_page = 'Análise de Planos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="conteudo-principal">
 <div class="tabela-container">
  <div class="tabela-header"><div><h3><i class="fas fa-drafting-compass"></i> Análise de Planos</h3><small>Agenda documental separada das vistorias e ordens de serviço.</small></div></div>
  <div class="analise-metrics">
   <div><span>Aguardando agendamento</span><strong><?=$metricas['aguardando']?></strong></div>
   <div><span>Prazo hoje</span><strong><?=$metricas['hoje']?></strong></div>
   <div><span>Atrasadas</span><strong><?=$metricas['atrasadas']?></strong></div>
   <div><span>Aguardando documentos</span><strong><?=$metricas['documentos']?></strong></div>
   <div><span>Aguardando admin</span><strong><?=$metricas['admin']?></strong></div>
  </div>
  <form method="get" class="filtros" style="margin:15px 20px">
   <div class="form-group" style="flex:1"><label>Buscar</label><input name="busca" value="<?=h($busca)?>" placeholder="Número, proposta, embarcação ou objeto"></div>
   <div class="form-group"><label>Situação</label><select name="status"><option value="">Todas</option><?php foreach($statusLabels as $v=>$l):?><option value="<?=$v?>" <?=$status===$v?'selected':''?>><?=h($l)?></option><?php endforeach?></select></div>
   <button class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button><a class="btn btn-secondary" href="<?=APP_URL?>analises-planos">Limpar</a>
  </form>
  <?php if(!$analises):?><div class="tabela-vazia"><i class="fas fa-drafting-compass"></i><h3>Nenhuma análise encontrada</h3><p>As demandas são criadas automaticamente por propostas assinadas com Análise de Planos EC1 ou EC2.</p></div><?php else:?><table><thead><tr><th>Número/Proposta</th><th>Embarcação</th><th>Processo</th><th>Analista</th><th>Prazo</th><th>Situação</th><th></th></tr></thead><tbody>
  <?php foreach($analises as $a):?><tr><td><strong><?=h($a['numero'])?></strong><small style="display:block"><?=h($a['proposta_numero'] ?: 'Processo legado')?></small></td><td><?=h($a['embarcacao_nome'])?></td><td><?=h($a['tipo_processo'] ?: 'A definir')?><small style="display:block"><?=h($a['classe_certificacao'] ?: '')?></small></td><td><?=h($a['analista_nome'] ?: 'Não atribuído')?></td><td><?=!empty($a['prazo_agendado_em'])?formatarDataCompleta($a['prazo_agendado_em']):'—'?></td><td><span class="badge badge-<?=$a['status']==='CONCLUIDA'?'success':($a['status']==='REPROVADA'||$a['status']==='CANCELADA'?'danger':'warning')?>"><?=h($statusLabels[$a['status']]??$a['status'])?></span></td><td><a class="btn btn-primary btn-sm" href="<?=APP_URL?>analises-planos/form?id=<?=urlencode($a['id'])?>"><i class="fas fa-eye"></i> Abrir</a></td></tr><?php endforeach?></tbody></table><?php endif?>
 </div>
</div>
<style>.analise-metrics{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;padding:16px 20px}.analise-metrics>div{padding:14px;border:1px solid var(--cor-borda,#ddd);border-radius:10px}.analise-metrics span{display:block;font-size:.82rem;color:var(--cor-texto-secundario,#667)}.analise-metrics strong{font-size:1.45rem}@media(max-width:900px){.analise-metrics{grid-template-columns:repeat(2,1fr)}}</style>
<?php require_once __DIR__ . '/../../includes/footer.php';?>
