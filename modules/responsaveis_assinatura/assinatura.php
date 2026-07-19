<?php
require_once __DIR__ . '/../../config.php';require_once __DIR__ . '/../../includes/auth.php';verificar_sessao();verificar_cargo('ADMIN');
$id=(int)($_GET['id']??0);$stmt=$pdo->prepare('SELECT assinatura_arquivo,assinatura_hash FROM responsaveis_assinatura WHERE id=?');$stmt->execute([$id]);$r=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$r||empty($r['assinatura_arquivo'])){http_response_code(404);exit;}$file=__DIR__.'/../../'.ltrim(str_replace(['../','..\\'],'',$r['assinatura_arquivo']),'/\\');
if(!is_file($file)||!hash_equals((string)$r['assinatura_hash'],hash_file('sha256',$file))){http_response_code(404);exit;}header('Content-Type: image/png');header('Content-Length: '.filesize($file));header('Cache-Control: private,max-age=300');header('X-Content-Type-Options: nosniff');readfile($file);exit;
