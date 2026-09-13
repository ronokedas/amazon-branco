<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../modules/dashboard/data.php';

function assertTeste(bool $cond, string $msg): void {
    if (!$cond) throw new RuntimeException("FALHA: " . $msg);
}

echo "=== TESTE: CRIAÇÃO DIRETA E DASHBOARD DO ANALISTA DE PLANOS ===\n";

// 1. Obter ou criar um usuário analista para teste
$analista = $pdo->query("SELECT id, nome, email FROM usuarios WHERE cargo = 'ANALISTA' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$analista) {
    $analistaId = gerarUUID();
    $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, 'Analista Teste', 'analista.teste@amazon.com', 'hash', 'ANALISTA', 1)")
        ->execute([':id' => $analistaId]);
    $analista = ['id' => $analistaId, 'nome' => 'Analista Teste', 'email' => 'analista.teste@amazon.com'];
}

// 2. Obter uma embarcação ativa
$embarcacao = $pdo->query("SELECT id, nome, cliente_id, proprietario_id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTeste(!empty($embarcacao), "Nenhuma embarcação ativa encontrada para teste.");

// 3. Simular sessão do usuário
$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_nome'] = $analista['nome'];
$_SESSION['usuario_email'] = $analista['email'];
$_SESSION['usuario_cargo'] = 'ANALISTA';
$_SESSION['usuario_logado'] = true;

// 4. Executar inserção direta simulando a ação 'criar_analise'
$novoId = gerarUUID();
$numero = gerarNumeroDocumento('RAP', 'AM-RAP');
$tipoProcesso = 'LC';
$enquadramento = 'NORMAM-202';
$classeCertificacao = 'EC1';
$objeto = 'Análise de Planos Direta - Teste Automatizado';
$prazo = date('Y-m-d 18:00:00');
$solicitanteId = $embarcacao['cliente_id'] ?? $embarcacao['proprietario_id'] ?? null;

$stmtInsert = $pdo->prepare("INSERT INTO analises_planos (
    id, numero, embarcacao_id, solicitante_id, tipo_processo, enquadramento,
    classe_certificacao, objeto, analista_id, status, prazo_agendado_em, criado_por
) VALUES (
    :id, :numero, :embarcacao, :solicitante, :tipo, :norma,
    :classe, :objeto, :analista, 'AGENDADA', :prazo, :criado_por
)");
$stmtInsert->execute([
    ':id' => $novoId,
    ':numero' => $numero,
    ':embarcacao' => $embarcacao['id'],
    ':solicitante' => $solicitanteId,
    ':tipo' => $tipoProcesso,
    ':norma' => $enquadramento,
    ':classe' => $classeCertificacao,
    ':objeto' => $objeto,
    ':analista' => $analista['id'],
    ':prazo' => $prazo,
    ':criado_por' => $analista['id'],
]);

// 5. Semear checklist
analisePlanosSemearChecklist($pdo, $novoId, $tipoProcesso, $enquadramento, $classeCertificacao, $analista['id']);

// 6. Verificar se os itens foram semeados
$itensCount = (int)$pdo->query("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id = '{$novoId}'")->fetchColumn();
assertTeste($itensCount > 5, "Checklist normativo NORMAM-202 não gerou os itens esperados (total: {$itensCount}).");
echo "[OK] Análise de Planos {$numero} criada e {$itensCount} itens da NORMAM-202 gerados.\n";

// 7. Testar Dashboard do Analista
$dashboardData = dashboardLoadData($pdo, 'ANALISTA', $analista['id']);
assertTeste(isset($dashboardData['kpis']['atribuidas']) && $dashboardData['kpis']['atribuidas'] >= 1, "KPI de atribuídas não refletiu o processo criado.");
assertTeste(isset($dashboardData['fila_planos']) && count($dashboardData['fila_planos']) >= 1, "Fila de planos do analista veio vazia.");
$encontrouNaFila = false;
foreach ($dashboardData['fila_planos'] as $item) {
    if ($item['id'] === $novoId) {
        $encontrouNaFila = true;
        break;
    }
}
assertTeste($encontrouNaFila, "Processo {$numero} não apareceu na fila prioritária do dashboard do analista.");
echo "[OK] Dashboard do Analista retornou {$dashboardData['kpis']['atribuidas']} processo(s) e fila prioritária correta.\n";

// 8. Testar renderização do HTML do dashboard do analista
$dashboard = $dashboardData;
ob_start();
require __DIR__ . '/../modules/dashboard/views/analista.php';
$html = ob_get_clean();
assertTeste(strpos($html, $numero) !== false, "HTML do dashboard não renderizou o número {$numero}.");
assertTeste(strpos($html, 'Minha Central de Análise de Planos') !== false, "HTML do dashboard não renderizou o título correto.");
echo "[OK] Renderização visual do dashboard do analista validada com sucesso (" . strlen($html) . " bytes).\n";

// 9. Limpar dados do teste
$pdo->prepare("DELETE FROM analise_planos_itens WHERE analise_id = :id")->execute([':id' => $novoId]);
$pdo->prepare("DELETE FROM analises_planos WHERE id = :id")->execute([':id' => $novoId]);
echo "[OK] Limpeza dos dados de teste concluída.\n";

echo "\nTODOS OS TESTES DE ANÁLISE DE PLANOS E DASHBOARD PASSARAM COM SUCESSO!\n";
