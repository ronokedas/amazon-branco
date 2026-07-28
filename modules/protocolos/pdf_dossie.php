<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/protocolos.php';
require_once __DIR__.'/../../vendor/autoload.php';

$interno=isset($salvar_pdf_dossie_caminho,$dossie_pdf_id);
if(!$interno)protocoloExigirAcesso();
$id=$interno?(string)$dossie_pdf_id:trim($_GET['id']??'');

$q=$pdo->prepare("SELECT d.*,e.nome embarcacao_nome,e.registro,c.nome cliente_nome,
  u.nome criador_nome,um.nome unidade_nome,um.tipo unidade_tipo,uc.nome cancelador_nome
  FROM protocolo_dossies d
  JOIN embarcacoes e ON e.id=d.embarcacao_id
  LEFT JOIN clientes c ON c.id=d.cliente_id
  LEFT JOIN usuarios u ON u.id=d.criado_por
  LEFT JOIN usuarios uc ON uc.id=d.cancelado_por
  LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id
  WHERE d.id=:id");
$q->execute([':id'=>$id]);$d=$q->fetch(PDO::FETCH_ASSOC);
if(!$d){http_response_code(404);exit('Dossiê não encontrado.');}
if(!$interno&&!protocoloUsuarioPodeAcessar($pdo,$d)){http_response_code(403);exit('Acesso negado.');}

$q=$pdo->prepare("SELECT m.*,um.nome unidade_nome,u.nome criador_nome,uc.nome confirmador_nome
  FROM protocolo_movimentacoes m
  LEFT JOIN protocolo_unidades_maritimas um ON um.id=m.unidade_maritima_id
  LEFT JOIN usuarios u ON u.id=m.criado_por
  LEFT JOIN usuarios uc ON uc.id=m.confirmado_por
  WHERE m.dossie_id=:id ORDER BY m.sequencia");
$q->execute([':id'=>$id]);$movimentacoes=$q->fetchAll(PDO::FETCH_ASSOC);

$q=$pdo->prepare("SELECT i.*,m.sequencia FROM protocolo_movimentacao_itens i
  JOIN protocolo_movimentacoes m ON m.id=i.movimentacao_id
  WHERE m.dossie_id=:id ORDER BY m.sequencia,i.criado_em,i.id");
$q->execute([':id'=>$id]);$itens=$q->fetchAll(PDO::FETCH_ASSOC);
$itensPorMovimento=[];foreach($itens as $item)$itensPorMovimento[$item['movimentacao_id']][]=$item;

$q=$pdo->prepare("SELECT c.*,m.sequencia movimentacao_sequencia,u.nome criador_nome
  FROM protocolo_comprovantes c
  LEFT JOIN protocolo_movimentacoes m ON m.id=c.movimentacao_id
  LEFT JOIN usuarios u ON u.id=c.criado_por
  WHERE c.dossie_id=:id ORDER BY c.criado_em,c.id");
$q->execute([':id'=>$id]);$documentos=$q->fetchAll(PDO::FETCH_ASSOC);

$q=$pdo->prepare("SELECT a.*,u.nome usuario_nome FROM protocolo_auditoria a
  LEFT JOIN usuarios u ON u.id=a.usuario_id
  WHERE a.dossie_id=:id ORDER BY a.criado_em,a.id");
$q->execute([':id'=>$id]);$auditoria=$q->fetchAll(PDO::FETCH_ASSOC);

$ordenar=function(&$valor)use(&$ordenar):void{
    if(!is_array($valor))return;
    foreach($valor as &$item)$ordenar($item);
    if(!array_is_list($valor))ksort($valor,SORT_STRING);
};
$baseIntegridade=['dossie'=>$d,'movimentacoes'=>$movimentacoes,'itens'=>$itens,'documentos'=>$documentos,'auditoria'=>$auditoria];
$ordenar($baseIntegridade);
$hashDados=hash('sha256',json_encode($baseIntegridade,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION));
$codigoIntegridade=strtoupper(substr($hashDados,0,24));
$emitidoEm=date('d/m/Y H:i:s');
$labels=protocoloRotulosStatus();

class ProtocoloDossiePdf extends TCPDF
{
    public string $numeroDossie='';
    public string $emitidoEm='';
    public string $codigoIntegridade='';
    public string $logo='';

    public function Header():void
    {
        if($this->logo!==''&&is_file($this->logo))$this->Image($this->logo,14,5,16,0,'PNG','','',true,200);
        $this->SetTextColor(22,58,49);
        $this->SetFont('dejavusans','B',10);
        $this->SetXY(43,9);$this->Cell(153,5,'AMAZON CERTIFICADORA NAVAL',0,1,'R');
        $this->SetFont('dejavusans','',7.5);
        $this->SetX(43);$this->Cell(153,4,'RELATÓRIO DE TRAMITAÇÃO DOCUMENTAL',0,1,'R');
        $this->SetDrawColor(8,118,83);$this->SetLineWidth(.45);$this->Line(14,24,196,24);
    }

    public function Footer():void
    {
        $this->SetY(-14);$this->SetDrawColor(150,165,159);$this->SetLineWidth(.2);$this->Line(14,$this->GetY(),196,$this->GetY());
        $this->Ln(1.5);$this->SetTextColor(65,78,73);$this->SetFont('dejavusans','',6.8);
        $this->Cell(70,4,$this->numeroDossie.' · Emitido em '.$this->emitidoEm,0,0,'L');
        $this->Cell(70,4,'Integridade '.$this->codigoIntegridade,0,0,'C');
        $this->Cell(42,4,'Página '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(),0,0,'R');
    }
}

$pdf=new ProtocoloDossiePdf('P','mm','A4',true,'UTF-8',false,true);
$pdf->numeroDossie=$d['numero'];$pdf->emitidoEm=$emitidoEm;$pdf->codigoIntegridade=$codigoIntegridade;$pdf->logo=__DIR__.'/../../img/logo.png';
$pdf->SetCreator('Amazon Certificadora Naval');
$pdf->SetAuthor('Amazon Certificadora Naval');
$pdf->SetTitle('Relatório de tramitação documental '.$d['numero']);
$pdf->SetSubject('Dossiê de protocolo e tramitação documental');
$pdf->SetKeywords('protocolo, dossiê, tramitação, documentos, integridade');
$pdf->SetMargins(14,29,14);$pdf->SetHeaderMargin(7);$pdf->SetFooterMargin(8);$pdf->SetAutoPageBreak(true,18);
$pdf->setFontSubsetting(true);$pdf->SetFont('dejavusans','',8.4);$pdf->AddPage();

$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$data=fn($v,$hora=true)=>$v?date($hora?'d/m/Y H:i':'d/m/Y',strtotime((string)$v)):'Não informado';
$textoEnum=fn($v)=>ucfirst(mb_strtolower(str_replace('_',' ',(string)$v)));
$eventoAuditoria=fn($v)=>match((string)$v){
    'COMPROVANTE_ANEXADO'=>'Documento anexado (legado)',
    'PROTOCOLO_EXTERNO_REGISTRADO'=>'Registro no órgão (legado)',
    default=>$textoEnum($v),
};
$tamanho=function($bytes):string{$bytes=(int)$bytes;return $bytes>=1048576?number_format($bytes/1048576,2,',','.').' MB':number_format($bytes/1024,1,',','.').' KB';};
$css='<style>
body{font-family:dejavusans;color:#173a31;font-size:8.4pt;line-height:1.35}
h1{font-size:17pt;color:#075d43;margin:0 0 4px 0}h2{font-size:11pt;color:#075d43;margin:13px 0 5px 0}h3{font-size:9.5pt;color:#173a31;margin:8px 0 3px 0}
.sub{color:#52665f;font-size:8pt}.status{background-color:#e7f4ee;color:#075d43;font-weight:bold;padding:4px}
.summary{border:1px solid #bdcec7;background-color:#f4f8f6}.summary td{padding:6px;border-bottom:1px solid #d9e3df}
.label{font-size:7pt;color:#597069}.value{font-size:9pt;color:#173a31;font-weight:bold}
.table{border-collapse:collapse}.table th{background-color:#075d43;color:#ffffff;font-size:7.2pt;font-weight:bold;padding:5px;border:1px solid #075d43}.table td{border:1px solid #ccd8d3;padding:5px;font-size:7.3pt;vertical-align:top}
.muted{color:#60736c}.warn{color:#8a4c00;font-weight:bold}.hash{font-family:dejavusansmono;font-size:6.3pt;color:#40564e}
.event{border:1px solid #aebfb8;background-color:#f8faf9;padding:6px}.note{border:1px solid #ccd8d3;background-color:#f5f7f6;padding:7px}
.sign td{height:42px;border-bottom:1px solid #40564e;text-align:center;vertical-align:bottom;font-size:7.5pt}
</style>';

$html=$css.'<h1>'.$e($d['numero']).'</h1><div class="sub">Relatório consolidado do dossiê · Situação em '.$e($emitidoEm).'</div><br>
<table class="summary" width="100%">
<tr><td width="62%"><span class="label">ASSUNTO</span><br><span class="value">'.$e($d['assunto']).'</span></td><td width="38%"><span class="label">SITUAÇÃO</span><br><span class="status">'.$e($labels[$d['status']]??$d['status']).'</span></td></tr>
<tr><td><span class="label">EMBARCAÇÃO</span><br><span class="value">'.$e($d['embarcacao_nome']).($d['registro']?' · '.$e($d['registro']):'').'</span></td><td><span class="label">CLIENTE / INTERESSADO</span><br><span class="value">'.$e($d['cliente_nome']?:'Não informado').'</span></td></tr>
<tr><td><span class="label">RESPONSÁVEL PELO DOSSIÊ</span><br><span class="value">'.$e($d['criador_nome']?:'Não informado').'</span></td><td><span class="label">ABERTURA / ÚLTIMA ATUALIZAÇÃO</span><br>'.$e($data($d['criado_em'])).' / '.$e($data($d['atualizado_em'])).'</td></tr>
</table>';

$html.='<h2>Registro no órgão</h2>';
if($d['protocolo_externo_em']){
    $html.='<table class="table" width="100%"><thead><tr><th width="48%">Unidade</th><th width="27%">Data e hora</th><th width="25%">Validade indicada</th></tr></thead><tbody><tr nobr="true"><td>'.$e($d['unidade_nome']?:'Não informada').'</td><td>'.$e($data($d['protocolo_externo_em'])).'</td><td>'.$e($data($d['protocolo_externo_validade'],false)).'</td></tr></tbody></table>';
}else $html.='<div class="note">Atendimento no órgão ainda não registrado.</div>';

$html.='<h2>Linha do tempo e documentos apresentados</h2>';
if(!$movimentacoes)$html.='<div class="note">Nenhuma movimentação registrada neste dossiê.</div>';
foreach($movimentacoes as $m){
    $seq=str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT);
    $html.='<div class="event"><b>Evento '.$e($seq).' · '.$e($textoEnum($m['tipo'])).' · '.$e($textoEnum($m['natureza'])).'</b><br>
    '.$e($data($m['movimentado_em'])).' · '.$e($m['cidade'].'/'.$m['uf']).' · '.$e($textoEnum($m['meio_envio'])).' · Status '.$e($textoEnum($m['status'])).'<br>
    <b>Origem:</b> '.$e($m['origem_nome']).' ('.$e($textoEnum($m['origem_tipo'])).') &nbsp; <b>Destino:</b> '.$e($m['destino_nome']).' ('.$e($textoEnum($m['destino_tipo'])).')'.
    ($m['unidade_nome']?'<br><b>Unidade:</b> '.$e($m['unidade_nome']):'').
    ($m['portador_nome']||$m['codigo_rastreio']?'<br><b>Portador/rastreio:</b> '.$e(trim(($m['portador_nome']?:'').' · '.($m['codigo_rastreio']?:''),' ·')):'').
    ($m['observacoes']?'<br><b>Observações:</b> '.$e($m['observacoes']):'').
    '<br><span class="muted">Registrado por '.$e($m['criador_nome']?:'Não informado').($m['confirmado_em']?' · confirmado em '.$e($data($m['confirmado_em'])).' por '.$e($m['confirmador_nome']?:'Não informado'):'').'</span></div>';
    $docsMov=$itensPorMovimento[$m['id']]??[];
    if($docsMov){
        $html.='<table class="table" width="100%"><thead><tr><th width="5%">#</th><th width="35%">Documento</th><th width="18%">Suporte / forma</th><th width="8%">Qtd.</th><th width="17%">Data / revisão</th><th width="17%">Custódia</th></tr></thead><tbody>';
        foreach($docsMov as $i=>$item)$html.='<tr nobr="true"><td>'.($i+1).'</td><td><b>'.$e($item['descricao']).'</b>'.($item['condicao_documento']?'<br><span class="muted">'.$e($item['condicao_documento']).'</span>':'').'</td><td>'.$e($textoEnum($item['suporte'])).'<br>'.$e($textoEnum($item['forma'])).'</td><td align="center">'.(int)$item['quantidade'].'</td><td>'.$e($data($item['data_documento'],false)).($item['numero_revisao']?'<br>'.$e($item['numero_revisao']):'').'</td><td>'.($item['requer_devolucao']?($item['devolvido_em']?'Devolvido em '.$e($data($item['devolvido_em'])):'<span class="warn">Devolução pendente</span>'):'Sem devolução').'</td></tr>';
        $html.='</tbody></table><br>';
    }else $html.='<div class="note">Evento sem documentos declarados.</div><br>';
}

$html.='<h2>Índice dos documentos anexados</h2>';
if($documentos){
    $html.='<table class="table" width="100%"><thead><tr><th width="30%">Arquivo</th><th width="13%">Formato / tamanho</th><th width="12%">Vínculo</th><th width="20%">Inclusão</th><th width="25%">SHA-256</th></tr></thead><tbody>';
    foreach($documentos as $doc)$html.='<tr nobr="true"><td><b>'.$e($doc['nome_original']).'</b></td><td>'.$e(strtoupper(pathinfo($doc['nome_original'],PATHINFO_EXTENSION))?:$doc['mime_type']).'<br>'.$e($tamanho($doc['tamanho_bytes'])).'</td><td>'.($doc['movimentacao_sequencia']?'Evento '.str_pad((string)$doc['movimentacao_sequencia'],2,'0',STR_PAD_LEFT):'Dossiê').'</td><td>'.$e($data($doc['criado_em'])).'<br>'.$e($doc['criador_nome']?:'Não informado').'</td><td class="hash">'.$e($doc['sha256']).'</td></tr>';
    $html.='</tbody></table>';
}else $html.='<div class="note">Nenhum documento foi anexado ao dossiê.</div>';

$originais=array_values(array_filter($itens,fn($item)=>(int)$item['requer_devolucao']===1));
$html.='<h2>Custódia de originais físicos</h2>';
if($originais){
    $html.='<table class="table" width="100%"><thead><tr><th width="12%">Evento</th><th width="53%">Documento</th><th width="35%">Situação</th></tr></thead><tbody>';
    foreach($originais as $item)$html.='<tr nobr="true"><td>Evento '.str_pad((string)$item['sequencia'],2,'0',STR_PAD_LEFT).'</td><td>'.$e($item['descricao']).'</td><td>'.($item['devolvido_em']?'Devolvido em '.$e($data($item['devolvido_em'])):'<span class="warn">Sob custódia - devolução pendente</span>').'</td></tr>';
    $html.='</tbody></table>';
}else $html.='<div class="note">Não há originais com devolução controlada neste dossiê.</div>';

$html.='<h2>Histórico essencial de auditoria</h2>';
if($auditoria){
    $html.='<table class="table" width="100%"><thead><tr><th width="20%">Data e hora</th><th width="24%">Evento</th><th width="20%">Responsável</th><th width="36%">Detalhe</th></tr></thead><tbody>';
    foreach($auditoria as $a)$html.='<tr nobr="true"><td>'.$e($data($a['criado_em'])).'</td><td>'.$e($eventoAuditoria($a['evento'])).'</td><td>'.$e($a['usuario_nome']?:'Acesso público').'</td><td>'.$e($a['detalhe']?:'Sem detalhe').($a['estado_novo']?'<br><span class="muted">Situação: '.$e($textoEnum($a['estado_novo'])).'</span>':'').'</td></tr>';
    $html.='</tbody></table>';
}

if($d['status']==='CANCELADO')$html.='<h2>Cancelamento</h2><div class="note"><b>Cancelado em:</b> '.$e($data($d['cancelado_em'])).' · <b>Responsável:</b> '.$e($d['cancelador_nome']?:'Não informado').'<br><b>Motivo:</b> '.$e($d['cancelado_motivo']?:'Não informado').'</div>';
$encerramento=null;foreach($auditoria as $a)if($a['evento']==='DOSSIE_ENCERRADO')$encerramento=$a;
if($d['status']==='ENCERRADO'&&$encerramento)$html.='<h2>Encerramento</h2><div class="note">Dossiê encerrado em '.$e($data($encerramento['criado_em'])).' por '.$e($encerramento['usuario_nome']?:'Não informado').'.</div>';

$html.='<h2>Conferência e integridade</h2><div class="note">Este relatório representa o estado do dossiê no momento da emissão. Os documentos anexados permanecem armazenados separadamente e podem ser conferidos pelos hashes SHA-256 listados acima.<br><br><b>Código de integridade dos dados:</b> <span class="hash">'.$e($codigoIntegridade).'</span><br><b>SHA-256 dos dados consolidados:</b> <span class="hash">'.$e($hashDados).'</span></div>
<br><table class="sign" width="100%"><tr><td width="45%">Responsável pela conferência</td><td width="10%"></td><td width="45%">Interessado / recebedor</td></tr></table><br>
<table width="100%"><tr><td width="60%">Local: __________________________________________</td><td width="40%">Data: ____ / ____ / ________</td></tr></table>';

$pdf->writeHTML($html,true,false,true,false,'');
$nomeArquivo=preg_replace('/[^A-Za-z0-9._-]+/','-',$d['numero']).'-relatorio-dossie.pdf';
if($interno){$pdf->Output($salvar_pdf_dossie_caminho,'F');return;}
header('X-Content-Type-Options: nosniff');
$pdf->Output($nomeArquivo,'I');exit;
