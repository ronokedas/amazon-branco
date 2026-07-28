<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/functions.php';
$token=trim((string)($_GET['token']??''));
$stmt=$pdo->prepare("SELECT da.*,ra.nome_completo,ra.cargo_titulo,ra.registro_profissional,
                            executor.nome executor_nome,aprovador.nome aprovador_nome
                     FROM documento_assinaturas da
                     JOIN responsaveis_assinatura ra ON ra.id=da.responsavel_id
                     LEFT JOIN usuarios executor ON executor.id=da.usuario_id
                     LEFT JOIN vistorias v ON da.documento_tipo='RELATORIO' AND v.id=da.documento_id
                     LEFT JOIN usuarios aprovador ON aprovador.id=v.aprovado_por
                     WHERE da.token_validacao=:token AND da.status='ASSINADO'");
$stmt->execute([':token'=>$token]);
$a=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$a){http_response_code(404);exit('Assinatura não encontrada.');}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Validar assinatura</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Arial;background:#eef4f1;color:#173b33;padding:30px}.card{max-width:720px;margin:auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 12px 35px #0002}.ok{color:#078454}</style></head><body><div class="card"><h1 class="ok">Assinatura válida</h1><p><b>Documento:</b> <?=h($a['documento_tipo'].' · '.$a['documento_id'])?></p><p><b>Responsável técnico:</b> <?=h($a['nome_completo'].' · '.$a['cargo_titulo'])?></p><?php if($a['documento_tipo']==='RELATORIO'):?><p><b>Aprovação administrativa:</b> <?=h($a['aprovador_nome']?:'Não identificada')?></p><p><b>Assinatura aplicada por:</b> <?=h($a['executor_nome']?:$a['nome_completo'])?></p><?php endif;?><p><b>Data:</b> <?=formatarDataCompleta($a['assinado_em'])?></p><p><b>Hash:</b> <code><?=h($a['hash_pdf_assinado'])?></code></p></div></body></html>
