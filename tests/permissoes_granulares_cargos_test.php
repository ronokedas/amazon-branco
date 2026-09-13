<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function assertPerm(bool $cond, string $msg): void {
    if (!$cond) throw new RuntimeException("FALHA: " . $msg);
}

echo "1. Validando catálogo e padrões canônicos...\n";
$todas = todasPermissoesSistema();
assertPerm(in_array('sgq', $todas, true), 'Permissão sgq deve estar no catálogo');
assertPerm(in_array('protocolos_documentais', $todas, true), 'Permissão protocolos_documentais deve estar no catálogo');
assertPerm(in_array('relatorios_aprovacao', $todas, true), 'Permissão relatorios_aprovacao deve estar no catálogo');

$padraoVistoriador = permissoesPadraoCargo('VISTORIADOR');
assertPerm(in_array('vistorias', $padraoVistoriador, true), 'Vistoriador deve ter vistorias');
assertPerm(in_array('agendamentos', $padraoVistoriador, true), 'Vistoriador deve ter agendamentos');
assertPerm(!in_array('financeiro', $padraoVistoriador, true), 'Vistoriador NÃO deve ter financeiro');
assertPerm(!in_array('analise_planos', $padraoVistoriador, true), 'Vistoriador NÃO deve ter analise_planos');
assertPerm(!in_array('usuarios', $padraoVistoriador, true), 'Vistoriador NÃO deve ter usuarios');
echo "   [✓] Padrão VISTORIADOR validado com sucesso.\n";

$padraoAnalista = permissoesPadraoCargo('ANALISTA');
assertPerm(in_array('analise_planos', $padraoAnalista, true), 'Analista deve ter analise_planos');
assertPerm(in_array('relatorios_aprovacao', $padraoAnalista, true), 'Analista deve ter relatorios_aprovacao');
assertPerm(in_array('protocolos_documentais', $padraoAnalista, true), 'Analista deve ter protocolos_documentais');
assertPerm(!in_array('financeiro', $padraoAnalista, true), 'Analista NÃO deve ter financeiro');
assertPerm(!in_array('usuarios', $padraoAnalista, true), 'Analista NÃO deve ter usuarios');
echo "   [✓] Padrão ANALISTA validado com sucesso.\n";

$padraoVendedor = permissoesPadraoCargo('VENDEDOR');
assertPerm(in_array('comercial', $padraoVendedor, true), 'Vendedor deve ter comercial');
assertPerm(in_array('servicos', $padraoVendedor, true), 'Vendedor deve ter servicos');
assertPerm(!in_array('analise_planos', $padraoVendedor, true), 'Vendedor NÃO deve ter analise_planos');
assertPerm(!in_array('financeiro', $padraoVendedor, true), 'Vendedor NÃO deve ter financeiro');
echo "   [✓] Padrão VENDEDOR validado com sucesso.\n";

echo "2. Testando criação e aplicação automática de permissões padrão...\n";
$testId = 'test-perm-' . bin2hex(random_bytes(4));
$stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, 'Test Perm User', :email, 'hash', 'VISTORIADOR', 1)");
$stmtUser->execute([':id' => $testId, ':email' => $testId . '@teste.com']);

aplicarPermissoesPadraoUsuario($pdo, $testId, 'VISTORIADOR');

$permsGravadas = $pdo->prepare("SELECT permissao, permitido FROM usuario_permissoes WHERE usuario_id = :id");
$permsGravadas->execute([':id' => $testId]);
$mapa = $permsGravadas->fetchAll(PDO::FETCH_KEY_PAIR);

assertPerm((int)($mapa['vistorias'] ?? 0) === 1, 'Vistorias deve estar gravado como 1 no banco');
assertPerm((int)($mapa['agendamentos'] ?? 0) === 1, 'Agendamentos deve estar gravado como 1 no banco');
assertPerm((int)($mapa['financeiro'] ?? 1) === 0, 'Financeiro deve estar explicitamente gravado como 0 no banco');
assertPerm((int)($mapa['analise_planos'] ?? 1) === 0, 'Análise de planos deve estar explicitamente gravado como 0 no banco');
assertPerm((int)($mapa['sgq'] ?? 1) === 0, 'SGQ deve estar explicitamente gravado como 0 no banco');
echo "   [✓] Gravação explícita no banco validada (1s para permitidos, 0s para bloqueados).\n";

echo "3. Testando verificação dinâmica de podeAcessar() na sessão...\n";
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $testId;
$_SESSION['usuario_cargo'] = 'VISTORIADOR';

assertPerm(podeAcessar('vistorias') === true, 'podeAcessar(vistorias) deve ser true');
assertPerm(podeAcessar('financeiro') === false, 'podeAcessar(financeiro) deve ser false');
assertPerm(podeAcessar('sgq') === false, 'podeAcessar(sgq) deve ser false');

// Simular desmarcar vistorias pelo administrador no banco
$pdo->prepare("UPDATE usuario_permissoes SET permitido = 0 WHERE usuario_id = :id AND permissao = 'vistorias'")->execute([':id' => $testId]);
assertPerm(podeAcessar('vistorias') === false, 'Ao desmarcar no banco, podeAcessar(vistorias) DEVE se tornar false imediatamente');

// Simular marcar financeiro no banco
$pdo->prepare("UPDATE usuario_permissoes SET permitido = 1 WHERE usuario_id = :id AND permissao = 'financeiro'")->execute([':id' => $testId]);
assertPerm(podeAcessar('financeiro') === true, 'Ao marcar no banco, podeAcessar(financeiro) DEVE se tornar true imediatamente');
echo "   [✓] Dinâmica em tempo real de marcar/desmarcar comprovada com sucesso.\n";

echo "4. Testando ocultação no menu lateral (sidebar)...\n";
$pdo->prepare("UPDATE usuario_permissoes SET permitido = 0 WHERE usuario_id = :id AND permissao = 'sgq'")->execute([':id' => $testId]);
ob_start();
require __DIR__ . '/../includes/sidebar.php';
$htmlSidebar = ob_get_clean();

assertPerm(!str_contains($htmlSidebar, 'QUALIDADE'), 'O grupo QUALIDADE NÃO deve aparecer no sidebar se sgq for 0');
assertPerm(!str_contains($htmlSidebar, 'sgq/manual'), 'Links do SGQ NÃO devem aparecer se sgq for 0');
echo "   [✓] Menu lateral oculta estritamente o que não está autorizado.\n";

// Limpeza do usuário de teste
$pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $testId]);

echo "\nTODOS OS TESTES DE PERMISSÕES GRANULARES PASSARAM COM SUCESSO (100% OK)!\n";
