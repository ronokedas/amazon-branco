<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

$stmt = $pdo->query(
    "SELECT u.id, u.cargo, up.permissao
       FROM usuarios u
       JOIN usuario_permissoes up ON up.usuario_id = u.id AND up.permitido = 1
      WHERE u.cargo <> 'ADMIN' AND u.ativo = 1 AND u.excluido_em IS NULL
      ORDER BY u.id, up.permissao"
);
$concedidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$concedidas) {
    throw new RuntimeException('Nao ha permissao granular concedida a usuario nao administrador para testar.');
}

foreach ($concedidas as $regra) {
    $_SESSION = [
        'usuario_logado' => true,
        'usuario_id' => $regra['id'],
        'usuario_cargo' => $regra['cargo'],
    ];
    if (!podeAcessar($regra['permissao'])) {
        throw new RuntimeException("Permissao concedida foi recusada: {$regra['permissao']}.");
    }
}

$stmt = $pdo->query(
    "SELECT u.id, u.cargo, up.permissao
       FROM usuarios u
       JOIN usuario_permissoes up ON up.usuario_id = u.id AND up.permitido = 0
      WHERE u.cargo <> 'ADMIN' AND u.ativo = 1 AND u.excluido_em IS NULL
      LIMIT 1"
);
$negada = $stmt->fetch(PDO::FETCH_ASSOC);
if ($negada) {
    $_SESSION = [
        'usuario_logado' => true,
        'usuario_id' => $negada['id'],
        'usuario_cargo' => $negada['cargo'],
    ];
    if (podeAcessar($negada['permissao'])) {
        throw new RuntimeException("Permissao negada foi liberada: {$negada['permissao']}.");
    }
}

$guardasEsperadas = [
    'modules/agendamentos/index.php' => 'agendamentos',
    'modules/armadores/index.php' => 'armadores',
    'modules/comercial/index.php' => 'comercial',
    'modules/comercial/servicos/index.php' => 'servicos',
    'modules/certificados/index.php' => 'certificados',
    'modules/configuracoes/index.php' => 'configuracoes',
    'modules/despachantes/index.php' => 'despachantes',
    'modules/documentacao/index.php' => 'documentacao',
    'modules/emails/index.php' => 'emails',
    'modules/embarcacoes/index.php' => 'embarcacoes',
    'modules/portal_clientes/index.php' => 'portal_clientes',
    'modules/proprietarios/index.php' => 'proprietarios',
    'modules/relatorios/index.php' => 'relatorios',
    'modules/vistorias/relatorio.php' => 'vistorias',
    'modules/vistorias/relatorio_pdf.php' => 'vistorias',
    'modules/responsaveis_assinatura/index.php' => 'responsaveis_assinatura',
    'modules/usuarios/index.php' => 'usuarios',
];
foreach ($guardasEsperadas as $arquivo => $modulo) {
    $codigo = file_get_contents(__DIR__ . '/../' . $arquivo);
    if ($codigo === false || (!str_contains($codigo, "exigirAcesso('{$modulo}')") && !str_contains($codigo, "podeAcessar('{$modulo}')"))) {
        throw new RuntimeException("O modulo {$modulo} nao usa a permissao granular na propria pagina.");
    }
}

$novaProposta = file_get_contents(__DIR__ . '/../modules/comercial/nova.php');
if ($novaProposta === false) {
    throw new RuntimeException('Nao foi possivel verificar o formulario de nova proposta.');
}
if (str_contains($novaProposta, 'clientes/form')) {
    throw new RuntimeException('O estado vazio da nova proposta ainda aponta para a rota legada de clientes.');
}
if (!str_contains($novaProposta, 'proprietarios/form')) {
    throw new RuntimeException('O estado vazio da nova proposta nao oferece cadastro de proprietario.');
}

$comercial = file_get_contents(__DIR__ . '/../modules/comercial/index.php');
$actionsPropostas = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
if ($comercial === false || $actionsPropostas === false
    || !str_contains($comercial, "in_array(\$cargo, ['ADMIN', 'VENDEDOR'], true)")
    || !str_contains($actionsPropostas, "\$cargoAtual === 'VENDEDOR' && (\$prop['criado_por'] ?? '') !== \$usuarioAtualId")
    || !str_contains($actionsPropostas, 'financeiroPodeAcessarEscritorio')) {
    throw new RuntimeException('A autorizacao manual de proposta nao protege vendedor, autoria e escritorio.');
}

$roteador = file_get_contents(__DIR__ . '/../index.php');
$pdfVistoria = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio_pdf.php');
if ($roteador === false || $pdfVistoria === false) {
    throw new RuntimeException('Nao foi possivel verificar a protecao dos relatorios de vistoria.');
}
if (str_contains($roteador, "strpos(\$path, 'relatorio_pdf')")
    || !str_contains($roteador, "'vistorias/relatorio_pdf.php' => 'vistorias'")) {
    throw new RuntimeException('O roteador ainda permite acesso publico ao PDF de vistoria.');
}
if (!str_contains($pdfVistoria, "podeAcessar('vistorias')")
    || !str_contains($pdfVistoria, 'agendamento_vistoriador_id')) {
    throw new RuntimeException('O PDF nao protege permissao e escopo do vistoriador.');
}

echo "OK: permissoes concedidas, negadas e guardas dos modulos estao alinhadas.\n";
