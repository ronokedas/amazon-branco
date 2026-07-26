<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/financeiro_escritorios.php';

$matriz = $pdo->query("SELECT id FROM escritorios WHERE ativo=1 AND (nome='Matriz' OR nome LIKE 'Matriz %') ORDER BY (nome='Matriz') DESC,nome LIMIT 1")->fetchColumn();
if (!$matriz) throw new RuntimeException('A Matriz nao foi criada.');

foreach (['usuarios', 'propostas', 'financeiro_lancamentos'] as $tabela) {
    $semEscritorio = (int)$pdo->query("SELECT COUNT(*) FROM {$tabela} WHERE escritorio_id IS NULL")->fetchColumn();
    if ($semEscritorio !== 0) throw new RuntimeException("Existem registros sem escritorio em {$tabela}.");
}

$semVinculo = (int)$pdo->query("SELECT COUNT(*) FROM usuarios u WHERE u.excluido_em IS NULL AND NOT EXISTS (SELECT 1 FROM usuario_escritorios ue WHERE ue.usuario_id=u.id)")->fetchColumn();
if ($semVinculo !== 0) throw new RuntimeException('Existem usuarios ativos sem vinculo de escritorio.');

$_SESSION = ['usuario_logado'=>true, 'usuario_id'=>'admin-teste', 'usuario_cargo'=>'ADMIN'];
if (financeiroResolverEscritorio($pdo, 'todos') !== 'todos') throw new RuntimeException('ADMIN nao recebeu o consolidado.');
if (financeiroResolverEscritorio($pdo, (string)$matriz) !== $matriz) throw new RuntimeException('ADMIN nao conseguiu selecionar a Matriz.');

$stmt=$pdo->query("SELECT id,escritorio_id,cargo FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND cargo<>'ADMIN' AND escritorio_id IS NOT NULL LIMIT 1");
$usuario=$stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    $_SESSION = ['usuario_logado'=>true, 'usuario_id'=>$usuario['id'], 'usuario_cargo'=>$usuario['cargo']];
    if (financeiroResolverEscritorio($pdo, 'todos') !== $usuario['escritorio_id']) throw new RuntimeException('Usuario comum escapou do proprio escritorio.');
    if (financeiroResolverEscritorio($pdo, '99999999-9999-4999-8999-999999999999') !== $usuario['escritorio_id']) throw new RuntimeException('Parametro adulterado alterou o escritorio do usuario.');

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM usuario_escritorios WHERE usuario_id=:u')->execute([':u'=>$usuario['id']]);
    if (financeiroResolverEscritorio($pdo, null) !== $usuario['escritorio_id']) {
        throw new RuntimeException('O vinculo legado de usuarios.escritorio_id nao foi reconhecido.');
    }
    $pdo->rollBack();

    $outroEscritorio = $pdo->prepare('SELECT id FROM escritorios WHERE ativo=1 AND id<>:id LIMIT 1');
    $outroEscritorio->execute([':id'=>$usuario['escritorio_id']]);
    $outroId = $outroEscritorio->fetchColumn();
    if ($outroId) {
        $pdo->beginTransaction();
        financeiroSalvarVinculosUsuario($pdo, $usuario['id'], [$usuario['escritorio_id'],$outroId], $usuario['escritorio_id']);
        $stmtVinculos=$pdo->prepare('SELECT COUNT(*) FROM usuario_escritorios WHERE usuario_id=:u');$stmtVinculos->execute([':u'=>$usuario['id']]);
        if((int)$stmtVinculos->fetchColumn()!==2) throw new RuntimeException('A gravacao dos multiplos escritorios falhou.');
        if (financeiroResolverEscritorio($pdo, $outroId) !== $outroId) throw new RuntimeException('Usuario com multiplos vinculos nao conseguiu selecionar o segundo escritorio.');
        if (!financeiroPodeAcessarEscritorio($pdo, $outroId)) throw new RuntimeException('Segundo escritorio vinculado nao foi autorizado.');
        $pdo->rollBack();
    }
}

$formFinanceiro = file_get_contents(__DIR__ . '/../modules/financeiro/form.php');
if ($formFinanceiro === false) throw new RuntimeException('Nao foi possivel verificar o formulario financeiro.');
if (!str_contains($formFinanceiro, 'name="comprovantes[]"')
    || preg_match('/<\?php\s+if\s*\(\$isEdicao\):\s*\?>\s*<!--\s*Comprovantes \/ notas/', $formFinanceiro)) {
    throw new RuntimeException('O upload de comprovantes nao esta disponivel na criacao do lancamento.');
}

$actionsUsuarios = file_get_contents(__DIR__ . '/../modules/usuarios/actions.php');
if ($actionsUsuarios === false
    || !preg_match('/INSERT INTO usuarios\s*\([^)]*escritorio_id[^)]*\)\s*VALUES\s*\([^)]*:escritorio[^)]*\)/s', $actionsUsuarios)) {
    throw new RuntimeException('A criacao de usuario nao grava o escritorio principal no INSERT inicial.');
}

$actionsPropostas = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
if ($actionsPropostas === false
    || !str_contains($actionsPropostas, 'token_assinatura, escritorio_id)')
    || !str_contains($actionsPropostas, ':token_assinatura, :escritorio_id)')) {
    throw new RuntimeException('A criacao de proposta nao grava o escritorio no INSERT inicial.');
}
if (!str_contains($actionsPropostas, 'criado_por, escritorio_id, responsavel_usuario_id, proposta_id)')
    || !str_contains($actionsPropostas, ':criado_por, :escritorio, :responsavel, :proposta)')) {
    throw new RuntimeException('A autorizacao interna nao grava o escritorio no lancamento financeiro inicial.');
}

$assinaturaProposta = file_get_contents(__DIR__ . '/../modules/comercial/propostas/assinar.php');
if ($assinaturaProposta === false
    || !str_contains($assinaturaProposta, 'criado_por, escritorio_id, responsavel_usuario_id, proposta_id)')
    || !str_contains($assinaturaProposta, ':criado_por, :escritorio, :responsavel, :proposta)')) {
    throw new RuntimeException('A assinatura publica nao grava o escritorio no lancamento financeiro inicial.');
}

echo "OK: Matriz, vinculos multiplos e isolamento por escritorio validados.\n";
