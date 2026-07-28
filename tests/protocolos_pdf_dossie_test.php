<?php
require_once __DIR__.'/../config.php';

function assertPdfDossie(bool $ok,string $msg):void
{
    if(!$ok)throw new RuntimeException($msg);
}

$dossiePdfId=(string)$pdo->query('SELECT id FROM protocolo_dossies ORDER BY criado_em DESC LIMIT 1')->fetchColumn();
assertPdfDossie($dossiePdfId!=='','É necessário ao menos um dossiê para testar o PDF consolidado.');

$diretorio=__DIR__.'/../tmp/pdfs';
if(!is_dir($diretorio)&&!mkdir($diretorio,0750,true)&&!is_dir($diretorio))throw new RuntimeException('Não foi possível preparar tmp/pdfs.');
$salvarPdfDossieCaminho=$diretorio.'/protocolo-dossie-teste.pdf';

$salvar_pdf_dossie_caminho=$salvarPdfDossieCaminho;
$dossie_pdf_id=$dossiePdfId;
require __DIR__.'/../modules/protocolos/pdf_dossie.php';

assertPdfDossie(is_file($salvarPdfDossieCaminho),'PDF consolidado não foi criado.');
assertPdfDossie(filesize($salvarPdfDossieCaminho)>5000,'PDF consolidado parece vazio.');
$cabecalho=file_get_contents($salvarPdfDossieCaminho,false,null,0,5);
assertPdfDossie($cabecalho==='%PDF-','Arquivo gerado não é um PDF válido.');
unlink($salvarPdfDossieCaminho);
echo "protocolos_pdf_dossie_test: OK\n";
