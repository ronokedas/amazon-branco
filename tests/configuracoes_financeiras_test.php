<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../modules/dashboard/data.php';

if (is_file(__DIR__ . '/../modules/configuracoes/geral.php')) {
    throw new RuntimeException('A pagina antiga de configuracoes gerais ainda existe.');
}

$financeiro = file_get_contents(__DIR__ . '/../modules/configuracoes/financeiro.php');
if ($financeiro === false || !str_contains($financeiro, 'name="meta_mensagem[')) {
    throw new RuntimeException('Cada escritorio nao possui seu proprio campo de recompensa.');
}
if (!str_contains($financeiro, 'data-meta-moeda')) {
    throw new RuntimeException('O campo de meta nao possui formatacao monetaria automatica.');
}

$valoresMoeda = [
    '200.000,00' => 200000.00,
    '1.234,56' => 1234.56,
    '200000.00' => 200000.00,
    '0,00' => 0.00,
];
foreach ($valoresMoeda as $entrada => $esperado) {
    if (abs(financeiroNormalizarMoedaBr($entrada) - $esperado) > 0.001) {
        throw new RuntimeException("Conversao monetaria incorreta para {$entrada}.");
    }
}

$painelConfiguracoes = file_get_contents(__DIR__ . '/../modules/configuracoes/index.php');
if ($painelConfiguracoes !== false && str_contains($painelConfiguracoes, 'configuracoes/geral')) {
    throw new RuntimeException('O painel ainda exibe acesso para a pagina Geral removida.');
}

$_SESSION = [
    'usuario_logado' => true,
    'usuario_id' => 'admin-teste',
    'usuario_cargo' => 'ADMIN',
];
$metaAdmin = dashMetaDoMes($pdo, 'ADMIN', 'admin-teste');
$metaEsperada = (float)$pdo->query("SELECT COALESCE(SUM(fm.valor),0) FROM financeiro_metas_mensais fm JOIN escritorios e ON e.id=fm.escritorio_id AND e.ativo=1 WHERE fm.usuario_id IS NULL AND fm.competencia=DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
if (abs((float)$metaAdmin['valor'] - $metaEsperada) > 0.001) {
    throw new RuntimeException('O dashboard administrativo nao soma as metas dos escritorios.');
}
$mensagensEsperadas = $pdo->query("SELECT COUNT(*) FROM financeiro_metas_mensais fm JOIN escritorios e ON e.id=fm.escritorio_id AND e.ativo=1 WHERE fm.usuario_id IS NULL AND fm.competencia=DATE_FORMAT(CURDATE(),'%Y-%m-01') AND TRIM(COALESCE(fm.mensagem,''))<>''")->fetchColumn();
if ((int)$mensagensEsperadas > 0 && trim((string)$metaAdmin['mensagem']) === '') {
    throw new RuntimeException('O dashboard administrativo nao exibe as recompensas configuradas.');
}

$stmt = $pdo->query("SELECT u.id,u.cargo FROM usuarios u WHERE u.ativo=1 AND u.excluido_em IS NULL AND u.cargo<>'ADMIN' AND EXISTS(SELECT 1 FROM usuario_escritorios ue JOIN escritorios e ON e.id=ue.escritorio_id AND e.ativo=1 WHERE ue.usuario_id=u.id) LIMIT 1");
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    $_SESSION = ['usuario_logado'=>true, 'usuario_id'=>$usuario['id'], 'usuario_cargo'=>$usuario['cargo']];
    $metaUsuario = dashMetaDoMes($pdo, $usuario['cargo'], $usuario['id']);
    $escritorio = financeiroEscritoriosUsuario($pdo, $usuario['id'])[0] ?? null;
    if (!$escritorio || $metaUsuario['escopo'] !== $escritorio['nome']) {
        throw new RuntimeException('O dashboard do usuario nao identificou seu escritorio principal.');
    }
    $stmtMensagem = $pdo->prepare("SELECT COALESCE(mensagem,'') FROM financeiro_metas_mensais WHERE escritorio_id=:escritorio AND usuario_id IS NULL AND competencia=DATE_FORMAT(CURDATE(),'%Y-%m-01') LIMIT 1");
    $stmtMensagem->execute([':escritorio'=>$escritorio['id']]);
    if ((string)$metaUsuario['mensagem'] !== (string)($stmtMensagem->fetchColumn() ?: '')) {
        throw new RuntimeException('O usuario nao recebeu a recompensa do proprio escritorio.');
    }
}

echo "OK: metas e recompensas por escritorio consolidadas no Financeiro.\n";
