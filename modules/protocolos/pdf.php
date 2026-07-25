<?php
require_once __DIR__.'/../../config.php';require_once __DIR__.'/../../includes/functions.php';require_once __DIR__.'/../../includes/auth.php';require_once __DIR__.'/../../includes/protocolos.php';
$interno=isset($salvar_pdf_caminho,$movimentacao_pdf_id);
if(!$interno)protocoloExigirAcesso();
$movId=$interno?(string)$movimentacao_pdf_id:trim($_GET['id']??'');
$q=$pdo->prepare("SELECT m.*,d.numero dossie_numero,d.assunto,d.embarcacao_id,d.cliente_id,d.criado_por,d.proposta_id,d.analise_id,d.vistoria_id,e.nome embarcacao_nome,e.registro,c.nome cliente_nome,um.nome unidade_nome,um.tipo unidade_tipo,u.nome responsavel_nome
FROM protocolo_movimentacoes m JOIN protocolo_dossies d ON d.id=m.dossie_id JOIN embarcacoes e ON e.id=d.embarcacao_id LEFT JOIN clientes c ON c.id=d.cliente_id LEFT JOIN protocolo_unidades_maritimas um ON um.id=m.unidade_maritima_id LEFT JOIN usuarios u ON u.id=m.criado_por WHERE m.id=:id");
$q->execute([':id'=>$movId]);$m=$q->fetch(PDO::FETCH_ASSOC);if(!$m)throw new RuntimeException('Movimentação não encontrada.');
if(!$interno&&!protocoloUsuarioPodeAcessar($pdo,$m)){http_response_code(403);exit('Acesso negado.');}
if(!$interno&&!in_array($m['status'],['CONFIRMADA','RETIFICADA'],true)){http_response_code(409);exit('O PDF somente existe após a confirmação.');}
if(!$interno&&$m['pdf_caminho']){
 $f=dirname(__DIR__,2).'/'.$m['pdf_caminho'];if(is_file($f)&&$m['pdf_hash']&&hash_equals($m['pdf_hash'],hash_file('sha256',$f))){header('Content-Type: application/pdf');header('Content-Length: '.filesize($f));header('Content-Disposition: inline; filename="'.$m['dossie_numero'].'-'.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT).'.pdf"');readfile($f);exit;}
 http_response_code(410);exit('PDF congelado indisponível ou com integridade inválida.');
}
$itens=protocoloSnapshot($pdo,$movId);$codigo=strtoupper(substr(hash('sha256',$m['dossie_numero'].'|'.$m['sequencia'].'|'.json_encode($itens)),0,20));
require_once __DIR__.'/../../vendor/autoload.php';
$pdf=new TCPDF('P','mm','A4',true,'UTF-8',false);$pdf->SetCreator('Amazon Naval');$pdf->SetAuthor('Amazon Naval');$pdf->SetTitle('Comprovante '.$m['dossie_numero'].'/'.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT));$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(14,13,14);$pdf->SetAutoPageBreak(true,14);$pdf->AddPage();
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$linhas='';foreach($itens as $i=>$item){$linhas.='<tr><td>'.($i+1).'</td><td><b>'.$e($item['descricao']).'</b>'.($item['numero_revisao']?'<br><small>Nº/revisão: '.$e($item['numero_revisao']).'</small>':'').'</td><td>'.$e($item['suporte']).'<br>'.$e(str_replace('_',' ',$item['forma'])).'</td><td align="center">'.(int)$item['quantidade'].'</td><td>'.$e($item['condicao_documento']?:'Conferido').($item['requer_devolucao']?'<br><b>Devolução obrigatória</b>':'').'</td></tr>';}
$html='<style>body{font-family:helvetica;color:#173b32;font-size:9.5pt}h1{font-size:18pt;color:#087653;margin:0}.brand{color:#087653;font-size:10pt}.box{border:1px solid #cfddd7;background-color:#f5faf8;padding:8px}.meta td{padding:5px;border-bottom:1px solid #dde8e3}.docs{border-collapse:collapse}.docs th{background-color:#087653;color:#fff;padding:6px;font-size:8.5pt}.docs td{border:1px solid #d8e4df;padding:6px;font-size:8.5pt}.sign td{height:55px;border-bottom:1px solid #405b52;text-align:center;vertical-align:bottom}.code{font-family:courier;font-size:8pt;color:#536a62}</style>
<div class="brand"><b>AMAZON NAVAL</b> · Controle de tramitação documental</div><h1>Comprovante de '.$e($m['tipo']).'</h1>
<p class="box"><b>Dossiê:</b> '.$e($m['dossie_numero']).' &nbsp; <b>Evento:</b> '.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT).'<br><b>Assunto:</b> '.$e($m['assunto']).'</p>
<table class="meta" width="100%"><tr><td width="50%"><b>Embarcação</b><br>'.$e($m['embarcacao_nome']).($m['registro']?' · '.$e($m['registro']):'').'</td><td><b>Cliente</b><br>'.$e($m['cliente_nome']?:'Não informado').'</td></tr>
<tr><td><b>Origem</b><br>'.$e($m['origem_nome']).' ('.$e($m['origem_tipo']).')</td><td><b>Destino</b><br>'.$e($m['destino_nome']).' ('.$e($m['destino_tipo']).')</td></tr>
<tr><td><b>Data/hora da movimentação</b><br>'.date('d/m/Y H:i',strtotime($m['movimentado_em'])).'</td><td><b>Local e meio</b><br>'.$e($m['cidade'].'/'.$m['uf'].' · '.$m['meio_envio']).'</td></tr>
<tr><td><b>Unidade marítima</b><br>'.$e($m['unidade_nome']?:'Não aplicável').'</td><td><b>Portador/rastreio</b><br>'.$e(trim(($m['portador_nome']?:'').' '.($m['codigo_rastreio']?:''))?:'Não informado').'</td></tr></table>
<h2>Relação de documentos</h2><table class="docs" width="100%"><thead><tr><th width="6%">#</th><th width="39%">Documento</th><th width="18%">Suporte/forma</th><th width="8%">Qtd.</th><th width="29%">Condição/custódia</th></tr></thead><tbody>'.$linhas.'</tbody></table>
'.($m['observacoes']?'<h2>Observações</h2><p>'.$e($m['observacoes']).'</p>':'').'
<table class="sign" width="100%"><tr><td width="45%">Responsável pela entrega</td><td width="10%"></td><td width="45%">Responsável pelo recebimento</td></tr></table>
<p class="code">Código de validação: '.$codigo.'<br>Documento gerado pelo sistema Amazon Naval. Após a confirmação, conteúdo, relação documental e hash ficam imutáveis.</p>';
$pdf->writeHTML($html,true,false,true,false,'');
if($interno){$pdf->Output($salvar_pdf_caminho,'F');return;}
$pdf->Output($m['dossie_numero'].'-'.str_pad((string)$m['sequencia'],2,'0',STR_PAD_LEFT).'.pdf','I');exit;
