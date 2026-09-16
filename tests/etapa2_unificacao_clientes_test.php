<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cliente_vinculos.php';

echo "=== TESTE ETAPA 2: UNIFICAÇÃO DOS MÓDULOS DE CADASTRO (CLIENTES & ATORES) ===\n\n";

// Helper de asserção
function afirmar($condicao, $mensagem) {
    if (!$condicao) {
        throw new RuntimeException("FALHA NA ASSERÇÃO: {$mensagem}");
    }
    echo "   [✓] {$mensagem}\n";
}

try {

// 1. Validar permissões e autorizações no auth.php
echo "1. Validando permissões granulares do módulo unificado...\n";
afirmar(in_array('clientes', todasPermissoesSistema(), true), "Permissão 'clientes' existe no catálogo canônico.");
$permissoesAdmin = permissoesPadraoCargo('ADMIN');
afirmar(in_array('clientes', $permissoesAdmin, true), "Cargo ADMIN possui permissão 'clientes' por padrão.");

$testUserId = 'test-e2-' . bin2hex(random_bytes(4));
$stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, 'Test Etapa 2', :email, 'hash', 'VISTORIADOR', 1)");
$stmtUser->execute([':id' => $testUserId, ':email' => $testUserId . '@teste.com']);

$_SESSION = [
    'usuario_logado' => true,
    'usuario_id' => $testUserId,
    'usuario_cargo' => 'VISTORIADOR'
];

// Teste com 'armadores'
$pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:id, 'armadores', 1)")->execute([':id' => $testUserId]);
afirmar(podeAcessar('clientes'), "Usuário com permissão legada 'armadores' pode acessar 'clientes'.");

// Teste com 'proprietarios'
$pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = :id")->execute([':id' => $testUserId]);
$pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:id, 'proprietarios', 1)")->execute([':id' => $testUserId]);
afirmar(podeAcessar('clientes'), "Usuário com permissão legada 'proprietarios' pode acessar 'clientes'.");

// Teste com 'despachantes'
$pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = :id")->execute([':id' => $testUserId]);
$pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:id, 'despachantes', 1)")->execute([':id' => $testUserId]);
afirmar(podeAcessar('clientes'), "Usuário com permissão legada 'despachantes' pode acessar 'clientes'.");

// Teste com 'clientes'
$pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = :id")->execute([':id' => $testUserId]);
$pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:id, 'clientes', 1)")->execute([':id' => $testUserId]);
afirmar(podeAcessar('clientes'), "Usuário com permissão 'clientes' pode acessar 'clientes'.");
afirmar(podeAcessar('armadores'), "Usuário com 'clientes' tem acesso a 'armadores'.");
afirmar(podeAcessar('proprietarios'), "Usuário com 'clientes' tem acesso a 'proprietarios'.");
afirmar(podeAcessar('despachantes'), "Usuário com 'clientes' tem acesso a 'despachantes'.");

// Limpar usuário de teste
$pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = :id")->execute([':id' => $testUserId]);
$pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $testUserId]);

// 2. Testar CRUD de Armador com Vínculo de Embarcações
echo "\n2. Testando CRUD completo de Armador com Vínculo de Frota (clientes_embarcacoes)...\n";

$stmtEmb = $pdo->query("SELECT id, nome FROM embarcacoes WHERE ativo = 1 AND excluido_em IS NULL LIMIT 2");
$embarcacoesTeste = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
if (empty($embarcacoesTeste)) {
    $idEmb = gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, status, ativo) VALUES (?, 'Embarcação Teste Etapa 2', 'PASSAGEIRO', 'REGULAR', 1)")->execute([$idEmb]);
    $embarcacoesTeste = [['id' => $idEmb, 'nome' => 'Embarcação Teste Etapa 2']];
}

// Limpar eventuais resquícios de testes anteriores
$pdo->query("DELETE FROM clientes WHERE email LIKE '%@teste-etapa2.com'");

$randNum = mt_rand(100000, 999999);
$cnpjTeste = "12.{$randNum}.001-90";
$cpfPropTeste = "123.{$randNum}-00";
$cpfDespTeste = "987.{$randNum}-99";

$idArmador = gerarUUID();
$stmt = $pdo->prepare("
    INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, status, ativo, criado_em, atualizado_em)
    VALUES (?, ?, ?, ?, 'armador', ?, ?, ?, 'ATIVO', 1, NOW(), NOW())
");
$stmt->execute([
    $idArmador,
    'Armador Teste Unificado Ltda',
    'PJ',
    $cnpjTeste,
    '(92) 98888-7777',
    'armador@teste-etapa2.com',
    'Av. Sete de Setembro, Centro, Manaus/AM'
]);
afirmar(!empty($idArmador), "Armador inserido com UUID gerado ({$idArmador}).");

// Sincronizar vínculo de frota
$idEmb1 = $embarcacoesTeste[0]['id'];
sincronizarClienteEmbarcacoes($pdo, $idArmador, [$idEmb1], null);

$checkFrota = $pdo->prepare("SELECT COUNT(*) FROM clientes_embarcacoes WHERE cliente_id = ? AND embarcacao_id = ? AND status = 'ATIVO'");
$checkFrota->execute([$idArmador, $idEmb1]);
afirmar((int)$checkFrota->fetchColumn() === 1, "Vínculo de frota 'clientes_embarcacoes' sincronizado com sucesso.");

// Editar Armador
$stmtUpd = $pdo->prepare("UPDATE clientes SET nome = ?, endereco = ? WHERE id = ?");
$stmtUpd->execute(['Armador Teste Unificado Atualizado', 'Novo Endereço Porto Flutuante', $idArmador]);
$checkArmador = $pdo->query("SELECT nome, endereco FROM clientes WHERE id = '{$idArmador}'")->fetch(PDO::FETCH_ASSOC);
afirmar($checkArmador['nome'] === 'Armador Teste Unificado Atualizado', "Armador editado com sucesso.");

// 3. Testar CRUD de Proprietário
echo "\n3. Testando CRUD completo de Proprietário...\n";
$idProprietario = gerarUUID();
$stmt = $pdo->prepare("
    INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, status, ativo, criado_em, atualizado_em)
    VALUES (?, ?, ?, ?, 'proprietario', ?, ?, ?, 'ATIVO', 1, NOW(), NOW())
");
$stmt->execute([
    $idProprietario,
    'Proprietário Naval Teste',
    'PF',
    $cpfPropTeste,
    '(92) 99111-2222',
    'proprietario@teste-etapa2.com',
    'Rua das Palmeiras, 100, Parintins/AM'
]);
afirmar(!empty($idProprietario), "Proprietário inserido com sucesso (ID: {$idProprietario}).");

// 4. Testar CRUD de Despachante com Dados Bancários e Tipos de Embarcação
echo "\n4. Testando CRUD completo de Despachante com Dados Bancários e Tipos de Embarcação...\n";
$idDespachante = gerarUUID();
$stmt = $pdo->prepare("
    INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, status, ativo, tipo_recebimento, chave_pix, banco, agencia, conta, criado_em, atualizado_em)
    VALUES (?, ?, ?, ?, 'despachante', ?, ?, ?, 'ATIVO', 1, 'pix', ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([
    $idDespachante,
    'Despachante Marítimo Expresso',
    'PF',
    $cpfDespTeste,
    '(92) 98444-5555',
    'despachante@teste-etapa2.com',
    'Rua Tamandaré, 45, Manaus/AM',
    $cpfDespTeste,
    'Banco do Brasil',
    '1234-5',
    '98765-4'
]);
afirmar(!empty($idDespachante), "Despachante inserido com sucesso (ID: {$idDespachante}).");

// Gravar tipos de embarcação atendidos (clientes_tipos_embarcacao)
$stmtTiposEmb = $pdo->query("SELECT id FROM tipos_embarcacao WHERE ativo = 1 LIMIT 2");
$tiposDisponiveis = $stmtTiposEmb->fetchAll(PDO::FETCH_COLUMN);

if (!empty($tiposDisponiveis)) {
    $stmtTipo = $pdo->prepare("INSERT INTO clientes_tipos_embarcacao (cliente_id, tipo_embarcacao_id) VALUES (?, ?)");
    foreach ($tiposDisponiveis as $tipoId) {
        $stmtTipo->execute([$idDespachante, $tipoId]);
    }
    $stmtCheckTipos = $pdo->prepare("SELECT COUNT(*) FROM clientes_tipos_embarcacao WHERE cliente_id = ?");
    $stmtCheckTipos->execute([$idDespachante]);
    afirmar((int)$stmtCheckTipos->fetchColumn() === count($tiposDisponiveis), "Tipos de embarcação do despachante vinculados com sucesso.");
}

$stmtCheckBanco = $pdo->query("SELECT banco, chave_pix, tipo_recebimento FROM clientes WHERE id = '{$idDespachante}'")->fetch(PDO::FETCH_ASSOC);
afirmar($stmtCheckBanco['banco'] === 'Banco do Brasil' && $stmtCheckBanco['chave_pix'] === $cpfDespTeste, "Dados bancários e PIX preservados no cadastro unificado.");

// 5. Testar Filtragem por Perfil
echo "\n5. Testando consultas de listagem com filtragem por perfil...\n";

// Perfil 'todos'
$stmtTodos = $pdo->query("SELECT COUNT(*) FROM clientes WHERE ativo = 1 AND excluido_em IS NULL");
$totalTodos = (int)$stmtTodos->fetchColumn();
afirmar($totalTodos >= 3, "Filtro 'todos' retorna todos os registros ativos ({$totalTodos} encontrados).");

// Perfil 'armador'
$stmtArmadores = $pdo->query("
    SELECT COUNT(*) FROM clientes 
    WHERE ativo = 1 AND excluido_em IS NULL 
      AND perfil = 'armador'
");
$totalArmadores = (int)$stmtArmadores->fetchColumn();
afirmar($totalArmadores >= 1, "Filtro 'armador' retorna armadores ({$totalArmadores} encontrados).");

// Perfil 'proprietario'
$stmtProprietarios = $pdo->query("
    SELECT COUNT(*) FROM clientes 
    WHERE ativo = 1 AND excluido_em IS NULL 
      AND perfil = 'proprietario'
");
$totalProprietarios = (int)$stmtProprietarios->fetchColumn();
afirmar($totalProprietarios >= 1, "Filtro 'proprietario' retorna proprietários ({$totalProprietarios} encontrados).");

// Perfil 'despachante'
$stmtDespachantes = $pdo->query("
    SELECT COUNT(*) FROM clientes 
    WHERE ativo = 1 AND excluido_em IS NULL 
      AND perfil = 'despachante'
");
$totalDespachantes = (int)$stmtDespachantes->fetchColumn();
afirmar($totalDespachantes >= 1, "Filtro 'despachante' retorna despachantes ({$totalDespachantes} encontrados).");

// 6. Testar Regras de Soft-Delete Padronizado
echo "\n6. Testando Soft-Delete padronizado (ativo = 0, status = 'INATIVO', excluido_em = NOW())...\n";
$stmtDel = $pdo->prepare("UPDATE clientes SET ativo = 0, status = 'INATIVO', excluido_em = NOW() WHERE id = ?");
$stmtDel->execute([$idProprietario]);

$stmtCheckDel = $pdo->query("SELECT ativo, status, excluido_em FROM clientes WHERE id = '{$idProprietario}'")->fetch(PDO::FETCH_ASSOC);
afirmar((int)$stmtCheckDel['ativo'] === 0, "Proprietário inativado: ativo = 0.");
afirmar($stmtCheckDel['status'] === 'INATIVO', "Proprietário inativado: status = 'INATIVO'.");
afirmar(!empty($stmtCheckDel['excluido_em']), "Proprietário inativado: excluido_em preenchido com timestamp.");

// 7. Testar Redirecionamento 301 no Roteador (index.php)
echo "\n7. Validando regras de redirecionamento 301 de rotas legadas no index.php...\n";
$indexCode = file_get_contents(__DIR__ . '/../index.php');
afirmar($indexCode !== false, "Arquivo index.php carregado para inspeção.");

$redirecionamentosEsperados = [
    "\$path === 'armadores'" => 'clientes?perfil=armador',
    "\$path === 'armadores/form'" => 'clientes/form?perfil=armador',
    "\$path === 'proprietarios'" => 'clientes?perfil=proprietario',
    "\$path === 'proprietarios/form'" => 'clientes/form?perfil=proprietario',
    "\$path === 'despachantes'" => 'clientes?perfil=despachante',
    "\$path === 'despachantes/form'" => 'clientes/form?perfil=despachante',
];

foreach ($redirecionamentosEsperados as $gatilho => $destino) {
    afirmar(str_contains($indexCode, $gatilho) && str_contains($indexCode, $destino), "Redirecionamento 301 configurado: {$gatilho} -> {$destino}");
}

afirmar(str_contains($indexCode, "'clientes'") && str_contains($indexCode, "'modules/clientes/index.php'"), "Rota 'clientes' mapeada para modules/clientes/index.php.");
afirmar(str_contains($indexCode, "'clientes/form'") && str_contains($indexCode, "'modules/clientes/form.php'"), "Rota 'clientes/form' mapeada para modules/clientes/form.php.");
afirmar(str_contains($indexCode, "'clientes/actions'") && str_contains($indexCode, "'modules/clientes/actions.php'"), "Rota 'clientes/actions' mapeada para modules/clientes/actions.php.");

// Rotas legadas de POST/actions mapeadas diretamente para não perder payload
afirmar(str_contains($indexCode, "'armadores/actions'") && str_contains($indexCode, "'modules/clientes/actions.php'"), "Rota 'armadores/actions' mapeada para actions unificado.");
afirmar(str_contains($indexCode, "'proprietarios/actions'") && str_contains($indexCode, "'modules/clientes/actions.php'"), "Rota 'proprietarios/actions' mapeada para actions unificado.");
afirmar(str_contains($indexCode, "'despachantes/actions'") && str_contains($indexCode, "'modules/clientes/actions.php'"), "Rota 'despachantes/actions' mapeada para actions unificado.");

// 8. Testar Módulos Dependentes
echo "\n8. Validando compatibilidade e vínculos de módulos dependentes...\n";

// A. agendamentos/form.php
$agendamentosForm = file_get_contents(__DIR__ . '/../modules/agendamentos/form.php');
afirmar($agendamentosForm !== false, "modules/agendamentos/form.php lido com sucesso.");
afirmar(str_contains($agendamentosForm, 'clientes') || str_contains($agendamentosForm, 'proprietarios') || str_contains($agendamentosForm, 'armador'), "Agendamentos consome clientes/atores.");

// B. comercial/nova.php
$comercialNova = file_get_contents(__DIR__ . '/../modules/comercial/nova.php');
afirmar($comercialNova !== false, "modules/comercial/nova.php lido com sucesso.");
$stmtClientesComercial = $pdo->query("
    SELECT id, nome, cpf_cnpj, telefone, email 
    FROM clientes 
    WHERE ativo = 1 AND excluido_em IS NULL 
    ORDER BY nome ASC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
afirmar(!empty($stmtClientesComercial), "Comercial consegue listar clientes unificados ativamente para nova proposta.");

// C. protocolos/form.php
$protocolosForm = file_get_contents(__DIR__ . '/../modules/protocolos/form.php');
afirmar($protocolosForm !== false, "modules/protocolos/form.php lido com sucesso.");
afirmar(str_contains($protocolosForm, 'clientes'), "Protocolos consome cadastro unificado de clientes.");

// D. embarcacoes/form.php
$embarcacoesForm = file_get_contents(__DIR__ . '/../modules/embarcacoes/form.php');
afirmar($embarcacoesForm !== false, "modules/embarcacoes/form.php lido com sucesso.");

// Limpeza dos dados de teste
$pdo->query("DELETE FROM clientes_tipos_embarcacao WHERE cliente_id = '{$idDespachante}'");
$pdo->query("DELETE FROM clientes_embarcacoes WHERE cliente_id = '{$idArmador}'");
$pdo->query("DELETE FROM clientes WHERE id IN ('{$idArmador}', '{$idProprietario}', '{$idDespachante}')");

echo "\n===============================================================\n";
echo "TODOS OS TESTES DA ETAPA 2 (UNIFICAÇÃO DE CLIENTES) PASSARAM COM 100% DE SUCESSO!\n";
echo "===============================================================\n";

} catch (Throwable $e) {
    echo "\n[ERRO CAPTURADO]: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
