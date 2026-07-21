<?php
require_once __DIR__ . '/../../config.php'; require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/auth.php'; require_once __DIR__ . '/../../includes/exportacoes_documentos.php';
verificar_sessao(); exigirAcesso('configuracoes');$id=trim((string)($_GET['id']??''));
$stmt=$pdo->prepare("SELECT * FROM exportacoes_documentos WHERE id=:id AND status='CONCLUIDA' AND expira_em>NOW() LIMIT 1");$stmt->execute([':id'=>$id]);$job=$stmt->fetch();
if(!$job){http_response_code(404);die('Exportação inexistente ou expirada.');}
$caminhoRelativo=ltrim((string)$job['caminho_arquivo'],'/');
$arquivo=realpath(BASE_PATH.'/'.$caminhoRelativo);$base=realpath(BASE_PATH.'/storage/private/exportacoes');
if(!$arquivo||!$base||!str_starts_with($arquivo,$base.DIRECTORY_SEPARATOR)||!is_file($arquivo)||!is_readable($arquivo)){
    error_log('Exportação indisponível para download: id='.$id.' caminho='.basename($caminhoRelativo));
    http_response_code(404);die('Arquivo não encontrado.');
}
$hashEsperado=strtolower(trim((string)($job['sha256']??'')));
$hashAtual=hash_file('sha256',$arquivo);
if($hashEsperado===''||$hashAtual===false||!hash_equals($hashEsperado,strtolower($hashAtual))){
    error_log('Falha de integridade na exportação: id='.$id);
    http_response_code(409);die('O arquivo de exportação falhou na verificação de integridade. Gere uma nova exportação.');
}
$pdo->prepare("UPDATE exportacoes_documentos SET baixado_em=NOW() WHERE id=:id")->execute([':id'=>$id]);if(function_exists('log_atividade'))log_atividade('exportacao_documentos_download','Download da exportação '.$id);
session_write_close();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.exportacaoSlug($job['nome_arquivo']).'"');header('Content-Length: '.filesize($arquivo));header('Cache-Control: private, no-store');readfile($arquivo);exit;
