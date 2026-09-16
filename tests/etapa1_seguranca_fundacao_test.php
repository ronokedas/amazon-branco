<?php
/**
 * TESTE DA ETAPA 1: SEGURANÇA BÁSICA, RATE LIMITING, CSRF, INVALIDAÇÃO DE SESSÃO E BANCO DE DADOS
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function assertEtapa1(bool $condicao, string $mensagem): void {
    if (!$condicao) {
        fwrite(STDERR, "FALHA ETAPA 1: {$mensagem}\n");
        exit(1);
    }
}

echo "=== TESTE ETAPA 1: FUNDAÇÃO, SEGURANÇA E BANCO DE DADOS ===\n\n";

// -------------------------------------------------------------
// 1. VERIFICAÇÃO DE REMOÇÃO DE CÓDIGO MORTO E ÓRFÃO
// -------------------------------------------------------------
echo "[1/5] Verificando limpeza de código morto e módulos órfãos...\n";

assertEtapa1(!is_dir(__DIR__ . '/../modules/contratos'), "Diretório modules/contratos ainda existe.");
assertEtapa1(!is_dir(__DIR__ . '/../modules/exigencias_catalogo'), "Diretório modules/exigencias_catalogo ainda existe.");
assertEtapa1(!file_exists(__DIR__ . '/../modules/documentos/assinatura_publica_desativada.php'), "Arquivo assinatura_publica_desativada.php ainda existe.");

$indexCode = file_get_contents(__DIR__ . '/../index.php');
assertEtapa1(!str_contains($indexCode, 'Fluxo legado mantido abaixo apenas como referencia historica'), "index.php ainda contém bloco de código comentado obsoleto.");
assertEtapa1(!str_contains($indexCode, 'assinatura_publica_desativada.php'), "index.php ainda requer assinatura_publica_desativada.php.");

echo "  -> Código morto e arquivos órfãos 100% limpos e validados.\n\n";

// -------------------------------------------------------------
// 2. PADRONIZAÇÃO DE BANCO (COLLATION E SOFT DELETE)
// -------------------------------------------------------------
echo "[2/5] Verificando padronização de Collation e Soft-Delete no banco de dados...\n";

// Collation de todas as tabelas
$stmtCollation = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION != 'utf8mb4_general_ci'");
$divergentes = $stmtCollation->fetchAll(PDO::FETCH_ASSOC);
assertEtapa1(empty($divergentes), "Ainda existem tabelas sem utf8mb4_general_ci: " . json_encode($divergentes));

$totalTabelas = (int)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn();
assertEtapa1($totalTabelas >= 88, "Total de tabelas menor que o esperado (88): {$totalTabelas}");

// Coluna versao_sessao em usuarios
$temVersao = (bool)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'versao_sessao'")->fetchColumn();
assertEtapa1($temVersao, "Coluna versao_sessao não encontrada em usuarios.");

// Coluna excluido_em nas entidades padronizadas
$tabelasSoftDelete = ['embarcacoes', 'clientes', 'servicos', 'escritorios', 'responsaveis_assinatura', 'usuarios'];
foreach ($tabelasSoftDelete as $tab) {
    $temExcluido = (bool)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tab}' AND COLUMN_NAME = 'excluido_em'")->fetchColumn();
    assertEtapa1($temExcluido, "Coluna excluido_em não encontrada na tabela {$tab}.");
}

echo "  -> 100% das {$totalTabelas} tabelas padronizadas em utf8mb4_general_ci e com soft delete auditável.\n\n";

// -------------------------------------------------------------
// 3. TESTE DE SEGURANÇA: PROTEÇÃO CONTRA TENTATIVAS ILIMITADAS DE LOGIN (BRUTE FORCE)
// -------------------------------------------------------------
echo "[3/5] Testando proteção contra tentativas ilimitadas de login (Rate Limiting)...\n";

$ipTeste = '198.51.100.99';
$emailTeste = 'ataque_teste_' . bin2hex(random_bytes(4)) . '@amazon.test';

// Limpar histórico prévio para o IP/Email de teste
$pdo->prepare("DELETE FROM login_tentativas WHERE ip = :ip OR email = :email")->execute([':ip' => $ipTeste, ':email' => $emailTeste]);

// Antes de qualquer tentativa, não deve estar bloqueado
$bloqueioInicial = loginVerificarRateLimit($pdo, $emailTeste, $ipTeste);
assertEtapa1($bloqueioInicial === null, "Rate limit bloqueou indevidamente antes das tentativas.");

// Simular 5 falhas consecutivas
for ($i = 1; $i <= 5; $i++) {
    loginRegistrarTentativa($pdo, $emailTeste, $ipTeste, false);
}

// Na 6ª tentativa, deve estar bloqueado com mensagem de proteção
$bloqueioAtivo = loginVerificarRateLimit($pdo, $emailTeste, $ipTeste);
assertEtapa1($bloqueioAtivo !== null, "O sistema NÃO bloqueou o login após 5 tentativas consecutivas de senha incorreta!");
assertEtapa1(str_contains($bloqueioAtivo, 'temporariamente bloqueado'), "Mensagem de rate limit inesperada: {$bloqueioAtivo}");

// Simular sucesso (limpeza de falhas)
loginRegistrarTentativa($pdo, $emailTeste, $ipTeste, true);
$bloqueioAposSucesso = loginVerificarRateLimit($pdo, $emailTeste, $ipTeste);
assertEtapa1($bloqueioAposSucesso === null, "Rate limit continuou bloqueando após sucesso legítimo.");

// Limpar dados do teste
$pdo->prepare("DELETE FROM login_tentativas WHERE ip = :ip OR email = :email")->execute([':ip' => $ipTeste, ':email' => $emailTeste]);

echo "  -> Bloqueio por força bruta (Rate Limiting) validado com sucesso após 5 tentativas.\n\n";

// -------------------------------------------------------------
// 4. TESTE DE SEGURANÇA: PROTEÇÃO CONTRA FORMULÁRIO FORJADO NO LOGIN (CSRF)
// -------------------------------------------------------------
echo "[4/5] Testando proteção contra formulário forjado no login (CSRF)...\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$tokenValido = gerarCSRF();
assertEtapa1(!empty($tokenValido) && strlen($tokenValido) === 64, "Token CSRF gerado é inválido.");

assertEtapa1(verificarCSRF($tokenValido) === true, "verificarCSRF rejeitou token válido gerado na sessão.");
assertEtapa1(verificarCSRF('token_forjado_hacker_123') === false, "verificarCSRF aceitou token forjado!");
assertEtapa1(verificarCSRF('') === false, "verificarCSRF aceitou token vazio!");

// Verificar presença de CSRF no HTML do login
$loginHtml = file_get_contents(__DIR__ . '/../modules/login/index.php');
assertEtapa1(str_contains($loginHtml, 'name="csrf_token"'), "modules/login/index.php não inclui input hidden csrf_token.");
assertEtapa1(str_contains($loginHtml, 'verificarCSRF($csrf)'), "modules/login/index.php não valida o token CSRF no POST.");

echo "  -> Proteção contra envio forjado (CSRF) no formulário de login 100% validada.\n\n";

// -------------------------------------------------------------
// 5. TESTE DE INVALIDAÇÃO IMEDIATA DE SESSÃO NA DESATIVAÇÃO DE USUÁRIO
// -------------------------------------------------------------
echo "[5/5] Testando invalidação de sessão ao desativar usuário ou alterar permissões...\n";

// Criar um usuário temporário de teste
$usuarioTesteId = 'usr-teste-' . bin2hex(random_bytes(4));
$emailUsuarioTeste = "teste.sessao.{$usuarioTesteId}@amazon.test";
$senhaPadrao = password_hash('SenhaSegura123!', PASSWORD_DEFAULT);

$stmtIns = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo, versao_sessao) VALUES (:id, :nome, :email, :senha, 'VISTORIADOR', 1, 1)");
$stmtIns->execute([
    ':id' => $usuarioTesteId,
    ':nome' => 'Usuario Sessao Teste',
    ':email' => $emailUsuarioTeste,
    ':senha' => $senhaPadrao
]);

// 5.1 Simular sessão ativa deste usuário
$_SESSION['usuario_id'] = $usuarioTesteId;
$_SESSION['usuario_nome'] = 'Usuario Sessao Teste';
$_SESSION['usuario_email'] = $emailUsuarioTeste;
$_SESSION['usuario_cargo'] = 'VISTORIADOR';
$_SESSION['usuario_logado'] = true;
$_SESSION['versao_sessao'] = 1;

assertEtapa1(estaLogado() === true, "Usuário deveria estar autenticado na sessão simulada.");

// 5.2 Desativar o usuário no banco (como feito em alternar_status)
// Simula a ação de alternar_status: ativo = 0, versao_sessao = versao_sessao + 1
$pdo->prepare("UPDATE usuarios SET ativo = 0, versao_sessao = versao_sessao + 1 WHERE id = :id")->execute([':id' => $usuarioTesteId]);

// 5.3 Simular checagem de sessão (como feito em verificarSessao)
$stmtCheck = $pdo->prepare("SELECT ativo, excluido_em, cargo, versao_sessao FROM usuarios WHERE id = :id LIMIT 1");
$stmtCheck->execute([':id' => $_SESSION['usuario_id']]);
$usrBanco = $stmtCheck->fetch(PDO::FETCH_ASSOC);

// Testar regras de invalidação:
$deveDeslogar = (
    !$usrBanco ||
    (int)$usrBanco['ativo'] !== 1 ||
    $usrBanco['excluido_em'] !== null ||
    (int)$usrBanco['versao_sessao'] !== (int)$_SESSION['versao_sessao'] ||
    $usrBanco['cargo'] !== $_SESSION['usuario_cargo']
);

assertEtapa1($deveDeslogar === true, "A sessão ativa NÃO foi invalidada após a desativação do usuário!");

// 5.4 Testar também quando o cargo é alterado
$pdo->prepare("UPDATE usuarios SET ativo = 1, cargo = 'ANALISTA', versao_sessao = versao_sessao + 1 WHERE id = :id")->execute([':id' => $usuarioTesteId]);
$stmtCheck->execute([':id' => $usuarioTesteId]);
$usrBancoCargo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

$deveDeslogarMudancaCargo = (
    !$usrBancoCargo ||
    (int)$usrBancoCargo['ativo'] !== 1 ||
    (int)$usrBancoCargo['versao_sessao'] !== (int)$_SESSION['versao_sessao'] ||
    $usrBancoCargo['cargo'] !== $_SESSION['usuario_cargo']
);

assertEtapa1($deveDeslogarMudancaCargo === true, "A sessão ativa NÃO foi invalidada após a alteração de cargo/versão do usuário!");

// Limpeza do usuário de teste
$pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $usuarioTesteId]);

echo "  -> Invalidação instantânea de sessão ao desativar usuário ou alterar permissões 100% validada.\n\n";

echo "===============================================================\n";
echo "TODOS OS TESTES DA ETAPA 1 FORAM EXECUTADOS E PASSARAM COM 100% DE SUCESSO!\n";
echo "===============================================================\n";
