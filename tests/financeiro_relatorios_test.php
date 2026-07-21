<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/financeiro_relatorio.php';
require_once __DIR__ . '/../includes/xlsx_export.php';

$admin=$pdo->query("SELECT id,cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$admin)throw new RuntimeException('Administrador de teste não encontrado.');
$_SESSION=['usuario_logado'=>true,'usuario_id'=>$admin['id'],'usuario_cargo'=>'ADMIN'];
$todos=financeiroRelatorioDados($pdo,date('Y-m'),'todos');
if($todos['filtro_escritorio']!=='todos')throw new RuntimeException('Administrador não recebeu o consolidado.');

$somaReceitas=0.0;$somaDespesas=0.0;
foreach($todos['escritorios'] as $e){
    $unidade=financeiroRelatorioDados($pdo,date('Y-m'),$e['id']);
    if($unidade['filtro_escritorio']!==$e['id'])throw new RuntimeException('Filtro por escritório não foi respeitado.');
    $somaReceitas+=$unidade['fluxo']['receita_realizada'];
    $somaDespesas+=$unidade['fluxo']['despesa_realizada'];
}
if(abs($somaReceitas-$todos['fluxo']['receita_realizada'])>0.01)throw new RuntimeException('Receitas das unidades não conciliam com o consolidado.');
if(abs($somaDespesas-$todos['fluxo']['despesa_realizada'])>0.01)throw new RuntimeException('Despesas das unidades não conciliam com o consolidado.');

$naoAdmin=$pdo->query("SELECT u.id,u.cargo FROM usuarios u WHERE u.cargo<>'ADMIN' AND u.ativo=1 AND u.excluido_em IS NULL AND EXISTS(SELECT 1 FROM usuario_escritorios ue JOIN escritorios e ON e.id=ue.escritorio_id AND e.ativo=1 WHERE ue.usuario_id=u.id) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if($naoAdmin){
    $_SESSION=['usuario_logado'=>true,'usuario_id'=>$naoAdmin['id'],'usuario_cargo'=>$naoAdmin['cargo']];
    $limitado=financeiroRelatorioDados($pdo,date('Y-m'),'todos');
    if($limitado['filtro_escritorio']==='todos')throw new RuntimeException('Usuário comum acessou o consolidado de todos os escritórios.');
}

$tmp=tempnam(sys_get_temp_dir(),'xlsx_test_');$xlsx=$tmp.'.xlsx';@unlink($tmp);
xlsxGerar($xlsx,[['nome'=>'Teste','linhas'=>[[['Relatório',1]],[['Valor',2]],[1234.56,4]],'larguras'=>[20]]]);
$zip=new ZipArchive();
if($zip->open($xlsx)!==true||$zip->locateName('xl/workbook.xml')===false||$zip->locateName('xl/styles.xml')===false)throw new RuntimeException('Excel gerado não possui estrutura OOXML válida.');
$workbook=$zip->getFromName('xl/workbook.xml');
if(!is_string($workbook)||!str_contains($workbook,'<sheet name="Teste"'))throw new RuntimeException('Excel gerado perdeu o nome da planilha.');
$zip->close();@unlink($xlsx);

echo "OK: consolidado, filtro por escritório, isolamento e estrutura Excel validados.\n";
