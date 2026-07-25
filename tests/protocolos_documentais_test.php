<?php
require_once __DIR__.'/../config.php';
function assertProtocolo(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
$migration=file_get_contents(__DIR__.'/../migrations/091_protocolos_documentais.sql');
$helper=file_get_contents(__DIR__.'/../includes/protocolos.php');
$actions=file_get_contents(__DIR__.'/../modules/protocolos/actions.php');
$form=file_get_contents(__DIR__.'/../modules/protocolos/form.php');
$pdf=file_get_contents(__DIR__.'/../modules/protocolos/pdf.php');
$router=file_get_contents(__DIR__.'/../index.php');
foreach([$migration,$helper,$actions,$form,$pdf,$router] as $f)assertProtocolo($f!==false,'Arquivo obrigatório do protocolo não encontrado.');
foreach(['protocolo_dossies','protocolo_movimentacoes','protocolo_movimentacao_itens','protocolo_comprovantes','protocolo_aceites','protocolo_auditoria'] as $t)assertProtocolo(str_contains($migration,'CREATE TABLE '.$t),'Migração não cria '.$t);
foreach(['EM_PREPARACAO','ENVIADO_AO_ORGAO','PROTOCOLADO','EM_ANALISE_NO_ORGAO','EM_EXIGENCIA','A_DISPOSICAO','RETIRADO','ENTREGUE_AO_CLIENTE','ENCERRADO','CANCELADO'] as $s)assertProtocolo(str_contains($migration,"'".$s."'"),'Status ausente: '.$s);
assertProtocolo(str_contains($actions,"gerarNumeroDocumento('PROTOCOLO','AM-PROT')"),'Numeração AM-PROT não usa sequencial transacional.');
assertProtocolo(str_contains($actions,'SELECT id FROM protocolo_dossies WHERE id=:id FOR UPDATE'),'Sequência de movimentação não trava o dossiê.');
assertProtocolo(str_contains($actions,"status='CONFIRMADA',snapshot_json=:snapshot"),'Confirmação não congela snapshot.');
assertProtocolo(!str_contains($actions,"DELETE FROM protocolo_"),'Fluxo não deve apagar protocolo.');
assertProtocolo(str_contains($actions,'protocolo_anterior_id')&&str_contains($form,'Cumprimento de exigência'),'Cumprimento não mantém vínculo anterior.');
assertProtocolo(str_contains($pdf,'hash_equals')&&str_contains($pdf,'Código de validação'),'PDF não valida integridade.');
assertProtocolo(str_contains($router,'protocolo-aceite/')&&str_contains($form,'Gerar link de aceite'),'Aceite público não foi integrado.');
$tabelas=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'protocolo_%'")->fetchAll(PDO::FETCH_COLUMN);
assertProtocolo(count($tabelas)>=8,'Migração 091 não está aplicada.');
$tipo=$pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='protocolo_movimentacoes' AND COLUMN_NAME='snapshot_json'")->fetchColumn();
assertProtocolo($tipo==='json','Snapshot documental não usa JSON nativo.');
$fk=$pdo->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'protocolo_%'")->fetchColumn();
assertProtocolo((int)$fk>=15,'Relações do módulo não estão protegidas por chaves estrangeiras.');
$rotulo=$pdo->query("SELECT nome FROM protocolo_catalogo_documentos WHERE codigo='ART'")->fetchColumn();
assertProtocolo($rotulo==='Anotação de Responsabilidade Técnica (ART)','Catálogo UTF-8 foi corrompido.');
echo "protocolos_documentais_test: OK\n";
