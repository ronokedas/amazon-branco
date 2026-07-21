<?php
require_once __DIR__ . '/../../config.php'; require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/auth.php'; require_once __DIR__ . '/../../includes/exportacoes_documentos.php';
verificar_sessao(); exigirAcesso('configuracoes');
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!verificarCSRF($_POST['csrf_token']??'')){setMensagem('error','Solicitação inválida.');redirecionar(APP_URL.'configuracoes/exportacoes');}
$action = (string)($_POST['action'] ?? 'criar');
if ($action === 'excluir') {
    $id = trim((string)($_POST['id'] ?? ''));
    $stmt = $pdo->prepare("SELECT * FROM exportacoes_documentos WHERE id=:id AND (status IN ('EXPIRADA','FALHA') OR (status='CONCLUIDA' AND expira_em<=NOW())) LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) { setMensagem('error','Somente exportações expiradas ou com falha podem ser excluídas.'); redirecionar(APP_URL.'configuracoes/exportacoes'); }
    if (!empty($job['caminho_arquivo'])) {
        $arquivo = realpath(BASE_PATH . '/' . ltrim($job['caminho_arquivo'],'/'));
        $base = realpath(BASE_PATH . '/storage/private/exportacoes');
        if ($arquivo && $base && str_starts_with($arquivo,$base.DIRECTORY_SEPARATOR) && is_file($arquivo)) @unlink($arquivo);
    }
    $pdo->prepare('DELETE FROM exportacoes_documentos WHERE id=:id')->execute([':id'=>$id]);
    if(function_exists('log_atividade')) log_atividade('exportacao_documentos_excluida','Exportação '.$id.' excluída.');
    setMensagem('success','Registro de exportação removido.'); redirecionar(APP_URL.'configuracoes/exportacoes');
}
$permitidas=array_keys(exportacaoTipos());$categorias=array_values(array_intersect($permitidas,array_map('strtoupper',(array)($_POST['categorias']??[]))));
if(!$categorias){setMensagem('error','Selecione ao menos uma categoria.');redirecionar(APP_URL.'configuracoes/exportacoes');}
$dataInicio=trim((string)($_POST['data_inicio']??''));$dataFim=trim((string)($_POST['data_fim']??''));
foreach([$dataInicio,$dataFim] as $data)if($data!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data)){setMensagem('error','Informe um período válido.');redirecionar(APP_URL.'configuracoes/exportacoes');}
if($dataInicio&&$dataFim&&$dataInicio>$dataFim){setMensagem('error','A data inicial não pode ser posterior à final.');redirecionar(APP_URL.'configuracoes/exportacoes');}
$filtros=['data_inicio'=>$dataInicio?:null,'data_fim'=>$dataFim?:null,'cliente_id'=>trim((string)($_POST['cliente_id']??''))?:null,'embarcacao_id'=>trim((string)($_POST['embarcacao_id']??''))?:null];
$id=gerarUUID();$pdo->prepare("INSERT INTO exportacoes_documentos(id,solicitado_por,categorias_json,filtros_json) VALUES(:id,:usuario,:categorias,:filtros)")->execute([':id'=>$id,':usuario'=>$_SESSION['usuario_id'],':categorias'=>json_encode($categorias),':filtros'=>json_encode($filtros)]);
if(function_exists('log_atividade'))log_atividade('exportacao_documentos_solicitada','Exportação '.$id.' solicitada.');setMensagem('success','Exportação adicionada à fila. A página atualizará automaticamente.');redirecionar(APP_URL.'configuracoes/exportacoes');
