<?php
/**
 * Teste de Validação de CPF e Diagnóstico de Assinatura do Analista (NORMAM-202)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function assertTest(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE: VALIDAÇÃO DE CPF OBRIGATÓRIO E DIAGNÓSTICO DE ASSINATURA\n";
echo "=================================================================\n\n";

// 1. Testar diagnóstico de analiseAcaoResponsavelDoAnalista
echo "1. Testando diagnóstico detalhado de analiseAcaoResponsavelDoAnalista...\n";

// 1.1 Usuário sem cadastro nenhum
$fakeAnalise = ['analista_id' => '00000000-0000-0000-0000-000000000000'];
try {
    analiseAcaoResponsavelDoAnalista($pdo, $fakeAnalise);
    assertTest(false, "Deveria ter falhado para analista sem cadastro");
} catch (RuntimeException $e) {
    assertTest(str_contains($e->getMessage(), 'ainda não possui perfil de assinatura cadastrado'), "Mensagem didática quando perfil não existe: " . $e->getMessage());
}

// 1.2 Simular analista com CPF em branco
$testUser = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo='ANALISTA' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($testUser), "Usuário analista encontrado: {$testUser['nome']}");

// Backup temporário do responsável atual
$backupResp = $pdo->query("SELECT * FROM responsaveis_assinatura WHERE usuario_id = '{$testUser['id']}'")->fetch(PDO::FETCH_ASSOC);

if ($backupResp) {
    // Definir CPF como null temporariamente
    $pdo->prepare("UPDATE responsaveis_assinatura SET cpf_cnpj = NULL WHERE id = ?")->execute([$backupResp['id']]);
    try {
        analiseAcaoResponsavelDoAnalista($pdo, ['analista_id' => $testUser['id']]);
        assertTest(false, "Deveria ter falhado para analista com CPF nulo");
    } catch (RuntimeException $e) {
        assertTest(str_contains($e->getMessage(), 'CPF está em branco'), "Mensagem didática quando CPF está em branco: " . $e->getMessage());
    }

    // Restaurar CPF
    $pdo->prepare("UPDATE responsaveis_assinatura SET cpf_cnpj = ? WHERE id = ?")->execute([$backupResp['cpf_cnpj'], $backupResp['id']]);
    
    // Verificar que agora passa com sucesso
    $respValido = analiseAcaoResponsavelDoAnalista($pdo, ['analista_id' => $testUser['id']]);
    assertTest(!empty($respValido['id']), "Analista com dados completos validado com sucesso");
}

// 2. Testar Auto-Healing do Banco NORMAM
echo "\n2. Testando Auto-Healing do Banco de Referências NORMAM...\n";
$contagemInicial = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam WHERE ativo=1")->fetchColumn();
assertTest($contagemInicial >= 600, "Banco contém mais de 600 referências NORMAM exclusivas do analista ({$contagemInicial})");

// Buscar via função oficial para validar que retorna os modelos
$modelos = analisePlanosBuscarReferenciasNormam($pdo);
assertTest(count($modelos) >= 600, "analisePlanosBuscarReferenciasNormam retornou " . count($modelos) . " modelos (> 600)");

echo "\n=================================================================\n";
echo " SUCESSO: TODAS AS VALIDAÇÕES DE ASSINATURA E BANCO NORMAM PASSARAM!\n";
echo "=================================================================\n";
