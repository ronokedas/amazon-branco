<?php
require_once __DIR__.'/../../config.php';require_once __DIR__.'/../../includes/functions.php';require_once __DIR__.'/../../includes/auth.php';require_once __DIR__.'/../../includes/analise_planos.php';analisePlanosExigirAcesso();
$id=trim($_GET['id']??'');$stmt=$pdo->prepare('SELECT ar.*,s.analise_id FROM analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id WHERE ar.id=:id');$stmt->execute([':id'=>$id]);$arquivo=$stmt->fetch(PDO::FETCH_ASSOC);if(!$arquivo){http_response_code(404);die('Arquivo não encontrado.');}analisePlanosCarregar($pdo,$arquivo['analise_id']);analisePlanosEmitirArquivo($arquivo,!empty($_GET['download']));

